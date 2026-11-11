#!/usr/bin/env bash
#
# Crea el parell de claus Ed25519 que signa les versions OTA (docs/OTA.md).
#
#   - La clau privada va al secret OTA_SIGNING_KEY del repositori de GitHub
#     (Settings → Secrets and variables → Actions). No la pugis mai al repositori.
#   - La clau pública va a OTA_PUBLIC_KEY, al .env de cada web que hagi
#     d'acceptar les actualitzacions (staging i producció).
#
# Ús (des de l'arrel del repositori): bash scripts/ota-keygen.sh [carpeta-de-sortida]
set -euo pipefail

OUT="${1:-.}"
KEY="$OUT/ota-signing-key.pem"

if [[ -e "$KEY" ]]; then
    echo "$KEY ja existeix; no se sobreescriu." >&2
    exit 1
fi

# macOS porta LibreSSL, que no té Ed25519: es recorre al sodium de PHP (la
# mateixa biblioteca amb què la web verifica) i s'escriu el mateix PEM PKCS#8.
if [[ -z "${OTA_KEYGEN_FORCE_PHP:-}" ]] && openssl genpkey -algorithm ed25519 -out "$KEY" 2>/dev/null; then
    PUBLIC="$(openssl pkey -in "$KEY" -pubout -outform DER | tail -c 32 | base64 | tr -d '\n')"
elif command -v php >/dev/null && php -r 'exit(function_exists("sodium_crypto_sign_keypair") ? 0 : 1);'; then
    : > "$KEY"
    PUBLIC="$(php -r '
        $pair = sodium_crypto_sign_keypair();
        $seed = substr(sodium_crypto_sign_secretkey($pair), 0, 32);
        $der = hex2bin("302e020100300506032b657004220420") . $seed;
        file_put_contents($argv[1], "-----BEGIN PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PRIVATE KEY-----\n");
        echo base64_encode(sodium_crypto_sign_publickey($pair));
    ' "$KEY")"
else
    echo "Cal OpenSSL 3 (brew install openssl@3) o PHP amb l'extensió sodium." >&2
    exit 1
fi
chmod 600 "$KEY"

echo "Clau privada: $KEY"
echo "  → enganxa'n tot el contingut al secret OTA_SIGNING_KEY de GitHub i després esborra el fitxer."
echo
echo "Clau pública (afegeix-la al .env de cada web):"
echo "OTA_PUBLIC_KEY=$PUBLIC"
