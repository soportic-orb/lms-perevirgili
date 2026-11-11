#!/usr/bin/env bash
#
# Descarrega el core de Moodle a moodle/, a l'etiqueta fixada a .moodle-version,
# hi instal·la les dependències de Composer i hi enllaça config/config.php.
# No toca mai els plugins propis: aquests es copien amb bin/sync-plugins.sh.
#
# Ús (des de l'arrel del repositori):
#   bash bin/moodle-fetch.sh          # producció: sense dependències de desenvolupament
#   bash bin/moodle-fetch.sh --dev    # local i CI: amb PHPUnit i Behat (o MOODLE_FETCH_DEV=1)
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TAG="$(tr -d '[:space:]' < "$ROOT/.moodle-version")"
REPO="${MOODLE_REPOSITORY:-https://github.com/moodle/moodle.git}"
DIR="$ROOT/moodle"

COMPOSER_ARGS=(install --no-interaction --no-progress)
if [[ "${1:-}" != "--dev" && -z "${MOODLE_FETCH_DEV:-}" ]]; then
    COMPOSER_ARGS+=(--no-dev --classmap-authoritative)
fi

if [[ ! -d "$DIR/.git" ]]; then
    echo "Clonant Moodle $TAG…"
    git clone --quiet --depth 1 --branch "$TAG" "$REPO" "$DIR"
else
    CURRENT="$(git -C "$DIR" describe --tags --exact-match 2>/dev/null || echo desconeguda)"
    if [[ "$CURRENT" != "$TAG" ]]; then
        echo "Canviant el core de $CURRENT a $TAG…"
        git -C "$DIR" fetch --quiet --depth 1 origin "refs/tags/$TAG:refs/tags/$TAG"
        git -C "$DIR" checkout --quiet --force "$TAG"
    else
        echo "El core ja és a $TAG."
    fi
fi

# Dependències PHP del core (Moodle 5.x no porta vendor/ al repositori).
if command -v composer >/dev/null 2>&1; then
    composer --working-dir="$DIR" "${COMPOSER_ARGS[@]}"
elif command -v docker >/dev/null 2>&1; then
    docker run --rm -u "$(id -u):$(id -g)" -v "$DIR":/app -w /app composer:2 \
        "${COMPOSER_ARGS[@]}" --ignore-platform-reqs
else
    echo "Cal Composer (o Docker) per instal·lar les dependències de Moodle." >&2
    exit 1
fi

ln -sfn ../config/config.php "$DIR/config.php"
echo "Moodle $TAG a punt a $DIR"
