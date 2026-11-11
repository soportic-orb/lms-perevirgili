#!/usr/bin/env bash
#
# Empaqueta una versió OTA (docs/OTA.md) amb tots els plugins propis de plugins/:
#
#   dist/ota/ota-bundle.zip          les carpetes dels plugins, amb la ruta que tenen dins moodle/public
#   dist/ota/ota-manifest.json       versió, commit, sha256 del paquet i carpetes que substitueix
#   dist/ota/ota-manifest.json.sig   signatura Ed25519 del manifest (només si es passa una clau)
#
# Ús: bash scripts/ota-bundle.sh <versió> [clau-de-signatura.pem]
set -euo pipefail

VERSION="${1:?ús: ota-bundle.sh <versió> [clau-de-signatura.pem]}"
KEY="${2:-}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="$ROOT/dist/ota"

mkdir -p "$OUT"
for file in ota-bundle.zip ota-manifest.json ota-manifest.json.sig; do
    if [[ -e "$OUT/$file" ]]; then unlink "$OUT/$file"; fi
done

# Un plugin és qualsevol carpeta de plugins/ amb version.php. La web només
# accepta carpetes de plugins "pspv" (local_pspvcore\ota\updater).
cd "$ROOT/plugins"
ROOTS=()
while IFS= read -r file; do
    ROOTS+=("$(dirname "$file")")
done < <(find . -name version.php -not -path '*/tests/*' -not -path '*/node_modules/*' | sed 's|^\./||' | sort)

[[ ${#ROOTS[@]} -gt 0 ]] || { echo "No hi ha cap plugin a plugins/" >&2; exit 1; }
for root in "${ROOTS[@]}"; do
    [[ "$(basename "$root")" =~ ^pspv[a-z0-9]*$ ]] || { echo "El plugin $root no té el prefix pspv" >&2; exit 1; }
done

# Sense entrades de directori, node_modules ni fitxers del sistema.
zip -qrDX "$OUT/ota-bundle.zip" "${ROOTS[@]}" -x '*/node_modules/*' '*.DS_Store' '*/.git*'
cd "$ROOT"

SHA="$(sha256sum "$OUT/ota-bundle.zip" | cut -d' ' -f1)"
COMMIT="$(git rev-parse --verify --quiet HEAD || echo unknown)"
FILES="$(unzip -Z1 "$OUT/ota-bundle.zip" | wc -l | tr -d ' ')"
ROOTS_JSON="$(printf '"%s", ' "${ROOTS[@]}")"

cat > "$OUT/ota-manifest.json" <<JSON
{
    "format": 1,
    "version": "$VERSION",
    "commit": "$COMMIT",
    "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "moodle": "$(tr -d '[:space:]' < "$ROOT/.moodle-version")",
    "sha256": "$SHA",
    "files": $FILES,
    "roots": [${ROOTS_JSON%, }]
}
JSON

if [[ -n "$KEY" ]]; then
    openssl pkeyutl -sign -inkey "$KEY" -rawin -in "$OUT/ota-manifest.json" | base64 | tr -d '\n' > "$OUT/ota-manifest.json.sig"
fi

echo "OTA $VERSION: ${#ROOTS[@]} plugins, $FILES fitxers, sha256 $SHA"
