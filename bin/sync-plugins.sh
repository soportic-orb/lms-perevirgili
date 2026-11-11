#!/usr/bin/env bash
#
# Copia els plugins propis de plugins/ a moodle/public/ (mateixa ruta relativa:
# plugins/local/pspvcore → moodle/public/local/pspvcore). Cada carpeta de plugin
# se substitueix sencera amb un parell de reanomenats; la resta del core no es toca.
#
# En local amb Docker no cal: docker-compose munta cada plugin al seu lloc.
#
# Ús: bash bin/sync-plugins.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/plugins"
DEST="$ROOT/moodle/public"

[[ -d "$DEST" ]] || { echo "No hi ha $DEST: executa abans bin/moodle-fetch.sh" >&2; exit 1; }

# Un plugin és qualsevol carpeta amb version.php.
find "$SRC" -name version.php -not -path '*/tests/*' -not -path '*/node_modules/*' -print0 | while IFS= read -r -d '' file; do
    dir="$(dirname "$file")"
    rel="${dir#"$SRC"/}"
    target="$DEST/$rel"
    new="$target.sync-new"
    old="$target.sync-old"

    [[ -n "$rel" && "$rel" != "$dir" ]] || { echo "Ruta de plugin inesperada: $dir" >&2; exit 1; }
    mkdir -p "$(dirname "$target")"
    if [[ -d "$new" ]]; then rm -r "$new"; fi
    if [[ -d "$old" ]]; then rm -r "$old"; fi
    cp -R "$dir" "$new"
    if [[ -d "$target" ]]; then mv "$target" "$old"; fi
    mv "$new" "$target"
    if [[ -d "$old" ]]; then rm -r "$old"; fi
    echo "  $rel"
done
