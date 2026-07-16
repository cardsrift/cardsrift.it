#!/usr/bin/env bash
# Deploy the built theme to production via FTP (Aruba).
# Uploads ONLY public/wp-content/themes/cardsrift/ — never core, plugins, or uploads.
# Usage: npm run deploy            (build + incremental upload)
#        npm run deploy -- --dry-run   (show what would be uploaded, upload nothing)
set -euo pipefail

cd "$(dirname "$0")/.."

ENV_FILE=".env.deploy"
LOCAL_DIR="public/wp-content/themes/cardsrift"

if [ ! -f "$ENV_FILE" ]; then
	echo "✗ $ENV_FILE not found. Copy .env.deploy.example to .env.deploy and fill in the FTP credentials."
	exit 1
fi

# shellcheck disable=SC1090
source "$ENV_FILE"

: "${FTP_HOST:?FTP_HOST missing in $ENV_FILE}"
: "${FTP_USER:?FTP_USER missing in $ENV_FILE}"
: "${FTP_PASS:?FTP_PASS missing in $ENV_FILE}"
: "${FTP_REMOTE_PATH:?FTP_REMOTE_PATH missing in $ENV_FILE}"
FTP_PROTOCOL="${FTP_PROTOCOL:-ftp}"
FTP_VERIFY_CERT="${FTP_VERIFY_CERT:-true}"

if [ ! -d "$LOCAL_DIR" ] || [ ! -f "$LOCAL_DIR/style.css" ]; then
	echo "✗ Built theme not found at $LOCAL_DIR — run 'npm run wp-build' first."
	exit 1
fi

DRY_RUN=""
if [ "${1:-}" = "--dry-run" ]; then
	DRY_RUN="--dry-run"
	echo "── DRY RUN: nothing will be uploaded ──"
fi

echo "Deploying $LOCAL_DIR → $FTP_PROTOCOL://$FTP_HOST$FTP_REMOTE_PATH"

# sed redacts credentials that lftp embeds in printed URLs (e.g. in --dry-run output)
lftp -u "$FTP_USER","$FTP_PASS" "$FTP_PROTOCOL://$FTP_HOST" <<EOF 2>&1 | sed -E 's#(ftps?://)[^@/[:space:]]+@#\1***@#g'
set cmd:fail-exit true
set ssl:verify-certificate $FTP_VERIFY_CERT
set ftp:ssl-allow true
set net:max-retries 2
set net:timeout 30
# Preflight: fails fast on broken connection/TLS or wrong FTP_REMOTE_PATH,
# so mirror can never mistake an unreachable server for an empty one.
cls "$FTP_REMOTE_PATH/" > /dev/null
mirror -R --delete --only-newer --parallel=4 --verbose $DRY_RUN \
	--exclude-glob .DS_Store \
	"$LOCAL_DIR" "$FTP_REMOTE_PATH"
bye
EOF

echo "✓ Deploy complete."
