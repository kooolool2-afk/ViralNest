#!/usr/bin/env bash
# Local WordPress for development/testing of the ViralNest theme + plugin.
# Requirements: PHP 8.1+ (mysqli, gd, intl recommended), MariaDB/MySQL, a WordPress core copy.
# Usage: WP_DIR=/path/to/wordpress DB_NAME=vn_local DB_USER=vn DB_PASS=secret ./tools/setup-local.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WP_DIR="${WP_DIR:-$HOME/wp-local}"
DB_NAME="${DB_NAME:-vn_local}"; DB_USER="${DB_USER:-vn}"; DB_PASS="${DB_PASS:?Set DB_PASS for the local database user}"; DB_HOST="${DB_HOST:-127.0.0.1}"
URL="${URL:-http://localhost:8080}"
ADMIN_PASS="${ADMIN_PASS:-$(php -r 'echo bin2hex(random_bytes(8));')}"

[ -f "$WP_DIR/wp-settings.php" ] || { echo "WordPress core not found in $WP_DIR"; exit 1; }
ln -sfn "$ROOT/wp-content/plugins/viralnest-core" "$WP_DIR/wp-content/plugins/viralnest-core"
ln -sfn "$ROOT/wp-content/themes/viralnest" "$WP_DIR/wp-content/themes/viralnest"

if [ ! -f "$WP_DIR/wp-config.php" ]; then
  php -r '
    $c = file_get_contents($argv[1] . "/wp-config-sample.php");
    $c = str_replace(["database_name_here","username_here","password_here","localhost"], [$argv[2],$argv[3],$argv[4],$argv[5]], $c);
    $salt = function () { return bin2hex(random_bytes(32)); };
    $c = preg_replace_callback("/put your unique phrase here/", $salt, $c);
    $c = str_replace("define( \x27WP_DEBUG\x27, false );", "define( \x27WP_DEBUG\x27, true );\ndefine( \x27WP_DEBUG_LOG\x27, true );\ndefine( \x27WP_DEBUG_DISPLAY\x27, false );\ndefine( \x27WP_ENVIRONMENT_TYPE\x27, \x27local\x27 );", $c);
    file_put_contents($argv[1] . "/wp-config.php", $c);
  ' "$WP_DIR" "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST"
fi

php "$ROOT/tools/install-wp.php" "$WP_DIR" "$URL" "$ADMIN_PASS"
echo "Start the server:  php -S localhost:8080 -t \"$WP_DIR\" \"$ROOT/tools/router.php\""
echo "Admin: $URL/wp-admin  user: admin  password: $ADMIN_PASS"
