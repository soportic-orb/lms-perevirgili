#!/usr/bin/env bash
#
# Instal·la o actualitza el Campus PSPV en un lloc de CloudPanel (docs/CLOUDPANEL.md).
#
# S'executa SEMPRE com l'usuari del lloc (mai com a root), des de l'arrel del
# repositori clonat a ~/htdocs/<domini>, amb el fitxer .env ja omplert:
#
#   bash deploy/cloudpanel/install.sh
#
# Es pot tornar a executar: baixa el core a l'etiqueta de .moodle-version, copia
# els plugins propis, instal·la Moodle si la base de dades és buida o hi aplica
# les actualitzacions pendents, i deixa a punt els idiomes i la memòria cau.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
# La CLI de PHP no rep els ajustos del lloc de CloudPanel: Moodle exigeix max_input_vars >= 5000.
PHP=("${PHP_BIN:-php}" -d max_input_vars=5000 -d memory_limit=512M)
cd "$ROOT"

if [[ "$(id -u)" -eq 0 && -z "${PSPV_ALLOW_ROOT:-}" ]]; then
    echo "No l'executis com a root: fes servir l'usuari del lloc (su - <usuari>)." >&2
    exit 1
fi
[[ -f .env ]] || { echo "Falta .env: copia .env.example a .env i omple'l (docs/CLOUDPANEL.md)." >&2; exit 1; }

# Llegeix una variable de .env sense executar-lo.
env_value() {
    local line
    line="$(grep -E "^$1=" .env | tail -n1 || true)"
    line="${line#*=}"
    line="${line%\"}"; line="${line#\"}"
    printf '%s' "$line"
}

DATAROOT="$(env_value MOODLE_DATAROOT)"
[[ -n "$DATAROOT" ]] || { echo "Falta MOODLE_DATAROOT a .env." >&2; exit 1; }
case "$DATAROOT" in
    "$ROOT"/*) echo "MOODLE_DATAROOT ha d'estar fora de la carpeta web ($ROOT)." >&2; exit 1 ;;
esac
mkdir -p "$DATAROOT"
chmod 700 .env

echo "== Versió de PHP"
"${PHP[@]}" -r 'if (version_compare(PHP_VERSION, "8.3.0", "<")) { fwrite(STDERR, "Moodle 5.3 exigeix PHP 8.3 o superior; aquest és " . PHP_VERSION . ". Defineix PHP_BIN=php8.4\n"); exit(1); } echo PHP_VERSION, "\n";'

echo "== Core de Moodle"
bash bin/moodle-fetch.sh

echo "== Plugins propis"
bash bin/sync-plugins.sh

echo "== Base de dades"
if "${PHP[@]}" moodle/admin/cli/cfg.php --name=version >/dev/null 2>&1; then
    "${PHP[@]}" moodle/admin/cli/maintenance.php --enable
    "${PHP[@]}" moodle/admin/cli/upgrade.php --non-interactive
    "${PHP[@]}" moodle/admin/cli/maintenance.php --disable
else
    ADMIN_PASS="$(env_value MOODLE_ADMIN_PASS)"
    [[ -n "$ADMIN_PASS" ]] || { echo "Falta MOODLE_ADMIN_PASS a .env per crear l'administrador." >&2; exit 1; }
    "${PHP[@]}" moodle/admin/cli/install_database.php --lang=ca --agree-license \
        --fullname="Campus PSPV" --shortname="Campus PSPV" --summary="" \
        --adminuser="$(env_value MOODLE_ADMIN_USER)" --adminpass="$ADMIN_PASS" \
        --adminemail="$(env_value MOODLE_ADMIN_EMAIL)"
    echo "Moodle instal·lat. Esborra MOODLE_ADMIN_PASS de .env i canvia la contrasenya en el primer accés."
fi

echo "== Idiomes i memòria cau"
"${PHP[@]}" moodle/public/local/pspvcore/cli/setup.php

echo "== Accés: cohort, categories, rols, OAuth 2 i RGPD"
"${PHP[@]}" moodle/public/local/pspvaccess/cli/setup.php
"${PHP[@]}" moodle/admin/cli/purge_caches.php

echo "== Actualitzacions OTA"
"${PHP[@]}" moodle/public/local/pspvcore/cli/ota.php --status

echo
echo "Fet. Recorda el cron del lloc a CloudPanel (docs/CLOUDPANEL.md, pas 1.5)."
