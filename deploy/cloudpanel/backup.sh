#!/usr/bin/env bash
#
# Còpia de seguretat del Campus PSPV en un lloc de CloudPanel (docs/COPIES.md):
# la base de dades, els fitxers de Moodle (MOODLE_DATAROOT) i el fitxer .env.
#
# S'executa com l'usuari del lloc, des de l'arrel del repositori, normalment
# des d'una tasca de cron de CloudPanel:
#
#   bash deploy/cloudpanel/backup.sh
#
# Llegeix de .env: BACKUP_DIR (on es guarden) i BACKUP_KEEP_DAYS (quants dies
# es conserven; 14 si no es diu). Cada còpia és una carpeta amb la data:
#
#   BACKUP_DIR/20270112-031500/db.sql.gz
#   BACKUP_DIR/20270112-031500/moodledata.tar.gz
#   BACKUP_DIR/20270112-031500/env
#   BACKUP_DIR/20270112-031500/SHA256SUMS
#
# En acabar bé escriu BACKUP_DIR/last-backup.json, que és el que vigila la
# comprovació «Còpies de seguretat del servidor» de l'informe d'estat. Si
# qualsevol pas falla, la còpia a mitges s'esborra i l'script acaba amb error.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"
[[ -f .env ]] || { echo "Falta .env." >&2; exit 1; }

# Llegeix una variable de .env sense executar-lo.
env_value() {
    local line
    line="$(grep -E "^$1=" .env | tail -n1 || true)"
    line="${line#*=}"
    line="${line%\"}"; line="${line#\"}"
    printf '%s' "$line"
}

BACKUP_DIR="$(env_value BACKUP_DIR)"
KEEP_DAYS="$(env_value BACKUP_KEEP_DAYS)"
KEEP_DAYS="${KEEP_DAYS:-14}"
DATAROOT="$(env_value MOODLE_DATAROOT)"
DB_HOST="$(env_value DB_HOST)"
DB_PORT="$(env_value DB_PORT)"
DB_NAME="$(env_value DB_NAME)"
DB_USER="$(env_value DB_USER)"
DB_PASS="$(env_value DB_PASS)"

[[ -n "$BACKUP_DIR" ]] || { echo "Falta BACKUP_DIR a .env." >&2; exit 1; }
[[ "$KEEP_DAYS" =~ ^[0-9]+$ && "$KEEP_DAYS" -ge 1 ]] || { echo "BACKUP_KEEP_DAYS ha de ser un nombre de dies." >&2; exit 1; }
[[ -d "$DATAROOT" ]] || { echo "MOODLE_DATAROOT no existeix: $DATAROOT" >&2; exit 1; }
case "$BACKUP_DIR" in
    "$ROOT"/*) echo "BACKUP_DIR ha d'estar fora de la carpeta web ($ROOT)." >&2; exit 1 ;;
    "$DATAROOT"/*|"$DATAROOT") echo "BACKUP_DIR no pot ser dins de MOODLE_DATAROOT." >&2; exit 1 ;;
esac

DUMP="$(command -v mariadb-dump || command -v mysqldump || true)"
[[ -n "$DUMP" ]] || { echo "No es troba mariadb-dump ni mysqldump." >&2; exit 1; }

umask 077
mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y%m%d-%H%M%S)"
TARGET="$BACKUP_DIR/$STAMP"
mkdir "$TARGET"
trap 'rm -rf "$TARGET"; echo "La còpia ha fallat i s'\''ha esborrat." >&2' ERR

# La contrasenya va en un fitxer temporal perquè no surti a la llista de processos.
CNF="$(mktemp)"
trap 'rm -f "$CNF"' EXIT
printf '[client]\nuser=%s\npassword=%s\nhost=%s\n' "$DB_USER" "$DB_PASS" "${DB_HOST:-127.0.0.1}" > "$CNF"
[[ -n "$DB_PORT" ]] && printf 'port=%s\n' "$DB_PORT" >> "$CNF"

echo "== Base de dades"
"$DUMP" --defaults-extra-file="$CNF" --single-transaction --quick --routines --no-tablespaces "$DB_NAME" \
    | gzip > "$TARGET/db.sql.gz"
# Un bolcat que s'ha tallat no acaba amb la marca de final.
gzip -dc "$TARGET/db.sql.gz" | tail -n 5 | grep -q "Dump completed" \
    || { echo "El bolcat de la base de dades és incomplet." >&2; false; }

echo "== Fitxers de Moodle"
# Les memòries cau, les sessions i els temporals es regeneren sols.
tar -C "$(dirname "$DATAROOT")" -czf "$TARGET/moodledata.tar.gz" \
    --exclude="$(basename "$DATAROOT")/cache" \
    --exclude="$(basename "$DATAROOT")/localcache" \
    --exclude="$(basename "$DATAROOT")/sessions" \
    --exclude="$(basename "$DATAROOT")/temp" \
    --exclude="$(basename "$DATAROOT")/trashdir" \
    --exclude="$(basename "$DATAROOT")/lock" \
    "$(basename "$DATAROOT")"

echo "== Configuració"
cp .env "$TARGET/env"
git rev-parse --verify --quiet HEAD > "$TARGET/commit" 2>/dev/null || true
tr -d '[:space:]' < .moodle-version > "$TARGET/moodle-version"

(cd "$TARGET" && sha256sum db.sql.gz moodledata.tar.gz env > SHA256SUMS)

echo "== Neteja de còpies de més de $KEEP_DAYS dies"
find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -name '20[0-9][0-9]*-*' -mtime +"$KEEP_DAYS" -print -exec rm -rf {} +

SIZE="$(du -sk "$TARGET" | cut -f1)"
printf '{"time": %s, "name": "%s", "sizekb": %s}\n' "$(date +%s)" "$STAMP" "$SIZE" > "$BACKUP_DIR/last-backup.json.tmp"
mv "$BACKUP_DIR/last-backup.json.tmp" "$BACKUP_DIR/last-backup.json"
# El servidor web ha de poder llegir la marca per a l'informe d'estat; les còpies, no.
chmod 644 "$BACKUP_DIR/last-backup.json" 2>/dev/null || true

trap - ERR
echo "Còpia feta: $TARGET ($((SIZE / 1024)) MB)"
