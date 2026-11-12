#!/usr/bin/env bash
#
# Restaura una còpia feta amb deploy/cloudpanel/backup.sh (docs/COPIES.md).
#
# SUBSTITUEIX la base de dades i els fitxers de Moodle del lloc pels de la
# còpia. S'executa com l'usuari del lloc, des de l'arrel del repositori:
#
#   bash deploy/cloudpanel/restore.sh /ruta/a/BACKUP_DIR/20270112-031500
#
# Demana que s'escrigui el nom de la base de dades per confirmar-ho
# (o RESTORE_CONFIRM=<nom de la base de dades> per fer-ho sense preguntar).
# No toca .env: el de la còpia queda a la carpeta de la còpia, com a «env».
# Els fitxers actuals no s'esborren: es mouen al costat, a
# <MOODLE_DATAROOT>.abans-<data>, i s'han d'esborrar a mà quan tot funcioni.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"
PHP=("${PHP_BIN:-php}" -d max_input_vars=5000 -d memory_limit=512M)
SOURCE="${1:-}"
[[ -n "$SOURCE" && -d "$SOURCE" ]] || { echo "Ús: bash deploy/cloudpanel/restore.sh <carpeta de la còpia>" >&2; exit 1; }
[[ -f .env ]] || { echo "Falta .env." >&2; exit 1; }
for file in db.sql.gz moodledata.tar.gz SHA256SUMS; do
    [[ -f "$SOURCE/$file" ]] || { echo "La còpia no té $file." >&2; exit 1; }
done

# Llegeix una variable de .env sense executar-lo.
env_value() {
    local line
    line="$(grep -E "^$1=" .env | tail -n1 || true)"
    line="${line#*=}"
    line="${line%\"}"; line="${line#\"}"
    printf '%s' "$line"
}

DATAROOT="$(env_value MOODLE_DATAROOT)"
DB_HOST="$(env_value DB_HOST)"
DB_PORT="$(env_value DB_PORT)"
DB_NAME="$(env_value DB_NAME)"
DB_USER="$(env_value DB_USER)"
DB_PASS="$(env_value DB_PASS)"
[[ -n "$DATAROOT" && -n "$DB_NAME" ]] || { echo "Falten MOODLE_DATAROOT o DB_NAME a .env." >&2; exit 1; }

CLIENT="$(command -v mariadb || command -v mysql || true)"
[[ -n "$CLIENT" ]] || { echo "No es troba el client mariadb ni mysql." >&2; exit 1; }

echo "== Comprovació de la còpia"
(cd "$SOURCE" && sha256sum --check --quiet SHA256SUMS)
gzip -t "$SOURCE/db.sql.gz"
gzip -t "$SOURCE/moodledata.tar.gz"

echo "Es substituirà la base de dades «$DB_NAME» i $DATAROOT per la còpia $(basename "$SOURCE")."
CONFIRM="${RESTORE_CONFIRM:-}"
if [[ -z "$CONFIRM" ]]; then
    read -r -p "Escriu el nom de la base de dades per continuar: " CONFIRM
fi
[[ "$CONFIRM" == "$DB_NAME" ]] || { echo "No s'ha confirmat. No s'ha canviat res." >&2; exit 1; }

CNF="$(mktemp)"
trap 'rm -f "$CNF"' EXIT
printf '[client]\nuser=%s\npassword=%s\nhost=%s\n' "$DB_USER" "$DB_PASS" "${DB_HOST:-127.0.0.1}" > "$CNF"
[[ -n "$DB_PORT" ]] && printf 'port=%s\n' "$DB_PORT" >> "$CNF"

HAS_MOODLE=""
if [[ -f moodle/admin/cli/maintenance.php ]] && "${PHP[@]}" moodle/admin/cli/cfg.php --name=version >/dev/null 2>&1; then
    HAS_MOODLE=1
    "${PHP[@]}" moodle/admin/cli/maintenance.php --enable
fi

echo "== Base de dades"
# Es buida abans: la còpia només recrea les taules que tenia.
TABLES="$("$CLIENT" --defaults-extra-file="$CNF" -N -e "SELECT table_name FROM information_schema.tables WHERE table_schema = '$DB_NAME'" "$DB_NAME")"
if [[ -n "$TABLES" ]]; then
    {
        echo "SET FOREIGN_KEY_CHECKS = 0;"
        while read -r table; do echo "DROP TABLE IF EXISTS \`$table\`;"; done <<< "$TABLES"
        echo "SET FOREIGN_KEY_CHECKS = 1;"
    } | "$CLIENT" --defaults-extra-file="$CNF" "$DB_NAME"
fi
gzip -dc "$SOURCE/db.sql.gz" | "$CLIENT" --defaults-extra-file="$CNF" "$DB_NAME"

echo "== Fitxers de Moodle"
if [[ -d "$DATAROOT" ]]; then
    mv "$DATAROOT" "$DATAROOT.abans-$(date +%Y%m%d-%H%M%S)"
fi
mkdir -p "$(dirname "$DATAROOT")"
tar -C "$(dirname "$DATAROOT")" -xzf "$SOURCE/moodledata.tar.gz"
# La còpia es pot haver fet amb un altre nom de carpeta.
EXTRACTED="$(tar -tzf "$SOURCE/moodledata.tar.gz" | head -n1 | cut -d/ -f1)"
if [[ "$(dirname "$DATAROOT")/$EXTRACTED" != "$DATAROOT" ]]; then
    mv "$(dirname "$DATAROOT")/$EXTRACTED" "$DATAROOT"
fi

if [[ -n "$HAS_MOODLE" || -f moodle/admin/cli/purge_caches.php ]]; then
    echo "== Moodle"
    "${PHP[@]}" moodle/admin/cli/purge_caches.php
    # Si el codi és més nou que la còpia, hi aplica les actualitzacions pendents.
    "${PHP[@]}" moodle/admin/cli/upgrade.php --non-interactive
    "${PHP[@]}" moodle/admin/cli/maintenance.php --disable
fi

echo "Restaurat des de $SOURCE."
if [[ -s "$SOURCE/commit" ]]; then
    echo "La còpia es va fer amb el codi $(cat "$SOURCE/commit") i Moodle $(cat "$SOURCE/moodle-version" 2>/dev/null)."
fi
echo "Quan hagis comprovat que tot funciona, esborra la carpeta $DATAROOT.abans-*."
