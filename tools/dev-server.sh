#!/usr/bin/env bash
# Local development server: http://localhost:8080 (needs PHP 8.1+ and a MySQL/MariaDB database
# configured in app/config.php with 'env' => 'dev').
cd "$(dirname "$0")/.." && PHP_CLI_SERVER_WORKERS=${PHP_CLI_SERVER_WORKERS:-4} exec php -S 127.0.0.1:${PORT:-8080} -t public tools/router.php
