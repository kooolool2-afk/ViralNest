#!/usr/bin/env bash
# Builds the Hostinger upload package: dist/viralnest-hostinger-<version>.zip
# Contains app/ (code, empty private storage) and public/ (web root). Never includes
# config.php, logs, sessions, CVs, uploads, tests, Node files or Git data.
set -euo pipefail
cd "$(dirname "$0")/.."
VERSION=$(php -r "preg_match(\"/VN_VERSION', '([^']+)/\", file_get_contents('app/bootstrap.php'), \$m); echo \$m[1];")
OUT=dist
STAGE=$(mktemp -d)
mkdir -p "$OUT" "$STAGE/app" "$STAGE/public"

cp app/bootstrap.php app/cron.php app/config.sample.php app/.htaccess "$STAGE/app/"
cp -R app/src app/views app/migrations app/data "$STAGE/app/"
for d in cache logs sessions cv portfolio; do mkdir -p "$STAGE/app/storage/$d"; touch "$STAGE/app/storage/$d/.gitkeep"; done
cp app/storage/.htaccess "$STAGE/app/storage/"

cp public/index.php public/.htaccess "$STAGE/public/"
cp -R public/assets "$STAGE/public/"
mkdir -p "$STAGE/public/uploads"
cp public/uploads/.htaccess "$STAGE/public/uploads/"

# Safety checks: nothing secret or personal in the package.
if find "$STAGE" -name 'config.php' -o -name '*.log' -o -name '*.sql.gz' | grep -q .; then echo "Refusing: secret or log file in package" >&2; exit 1; fi
if [ -n "$(find "$STAGE/app/storage/cv" -name '*.pdf')" ]; then echo "Refusing: CV files in package" >&2; exit 1; fi
if [ -n "$(find "$STAGE/app/storage/portfolio" -type f ! -name .gitkeep)" ]; then echo "Refusing: portfolio files in package" >&2; exit 1; fi

ZIP="$OUT/viralnest-hostinger-$VERSION.zip"
rm -f "$ZIP"
(cd "$STAGE" && zip -qr -X "$OLDPWD/$ZIP" app public)
rm -rf "$STAGE"
echo "$ZIP ($(du -h "$ZIP" | cut -f1))"
