#!/usr/bin/env bash
# Deploy the built theme to production (Aruba) via FTP.
# Uploads ONLY public/wp-content/themes/cardsrift/ — never core, plugins, or uploads.
# Usage: npm run deploy            (build + upload + integrity verification)
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
FTP_VERIFY_CERT="${FTP_VERIFY_CERT:-false}"

if [ ! -d "$LOCAL_DIR" ] || [ ! -f "$LOCAL_DIR/style.css" ]; then
	echo "✗ Built theme not found at $LOCAL_DIR — run 'npm run wp-build' first."
	exit 1
fi

DRY_RUN=""
if [ "${1:-}" = "--dry-run" ]; then
	DRY_RUN="--dry-run"
	echo "── DRY RUN: nothing will be uploaded ──"
fi

# Shared lftp settings.
# ftp:ssl-protect-data false — Aruba's TLS data channel silently stores files
# >~10KB as 0 bytes (server replies "226 complete" anyway). Credentials stay
# TLS-encrypted on the control channel; file data travels in plain FTP.
LFTP_SETTINGS="set cmd:fail-exit true
set ssl:verify-certificate $FTP_VERIFY_CERT
set ftp:ssl-allow true
set ftp:ssl-protect-data false
set net:max-retries 2
set net:timeout 30"

echo "Deploying $LOCAL_DIR → $FTP_PROTOCOL://$FTP_HOST$FTP_REMOTE_PATH"

# sed redacts credentials that lftp embeds in printed URLs (e.g. in --dry-run output)
lftp -u "$FTP_USER","$FTP_PASS" "$FTP_PROTOCOL://$FTP_HOST" <<EOF 2>&1 | sed -E 's#(ftps?://)[^@/[:space:]]+@#\1***@#g'
$LFTP_SETTINGS
# Preflight: fails fast on broken connection/TLS or wrong FTP_REMOTE_PATH,
# so mirror can never mistake an unreachable server for an empty one.
cls "$FTP_REMOTE_PATH/" > /dev/null
mirror -R --delete --parallel=4 --verbose $DRY_RUN \
	--exclude-glob .DS_Store \
	"$LOCAL_DIR" "$FTP_REMOTE_PATH"
bye
EOF

# Post-upload integrity verification: a size/date-based dry-run must plan ZERO
# operations. Catches servers that acknowledge uploads but store corrupt/empty
# files (seen on Aruba with the TLS data channel).
if [ -z "$DRY_RUN" ]; then
	echo "Verifying upload integrity..."
	# "mkdir -p <root>" is always planned defensively by mirror — not a mismatch.
	PENDING=$(lftp -u "$FTP_USER","$FTP_PASS" "$FTP_PROTOCOL://$FTP_HOST" <<EOF2 2>&1 | grep -vE '^mkdir -p ' | grep -cE '^(get|put|rm|rmdir|mkdir) ' || true
$LFTP_SETTINGS
mirror -R --delete --dry-run --exclude-glob .DS_Store "$LOCAL_DIR" "$FTP_REMOTE_PATH"
bye
EOF2
)
	if [ "$PENDING" -gt 0 ]; then
		echo "✗ Integrity check FAILED: $PENDING remote file(s) do not match local after upload."
		echo "  The server may be storing corrupt/empty files. Re-run the deploy; if it persists, contact the host."
		exit 1
	fi
	echo "✓ Integrity check passed: remote matches local."
fi

echo "✓ Deploy complete."
