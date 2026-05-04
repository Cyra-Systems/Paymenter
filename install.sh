#!/usr/bin/env bash
# install.sh — Fresh Paymenter installation pipeline.
#
#   Usage: ./install.sh
#
# Use this on a fresh server (after you've cloned the repo and configured
# your web server). It walks through the initial setup that ./build.sh
# assumes is already done:
#
#   1. .env from .env.example (if missing)
#   2. Generate APP_KEY (if missing)
#   3. Verify required PHP extensions
#   4. Install composer + npm dependencies
#   5. Build both theme bundles
#   6. Run migrations
#   7. Set storage / bootstrap/cache permissions
#
# After this completes, run `php artisan app:init` to create your admin user
# and finish setup.

set -euo pipefail

cd "$(dirname "$0")"

step() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!! %s\033[0m\n' "$*"; }
fail() { printf '\033[1;31mxx %s\033[0m\n' "$*"; exit 1; }

# 1. Bootstrap .env
step "[1/7] Preparing .env"
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        echo "    -> .env created from .env.example"
        warn "Edit .env now to set DB_CONNECTION, DB_*, MAIL_*, APP_URL,"
        warn "then re-run ./install.sh."
        exit 0
    else
        fail "Neither .env nor .env.example exist. Bad checkout?"
    fi
fi

if ! grep -qE '^DB_CONNECTION=' .env; then
    fail "DB_CONNECTION not set in .env. Add e.g. DB_CONNECTION=mysql and re-run."
fi

# 2. Required PHP extensions
step "[2/7] Verifying PHP extensions"
required_ext=(intl json mbstring pdo zip)
db_conn=$(grep -E '^DB_CONNECTION=' .env | head -1 | cut -d'=' -f2 | tr -d '"' | tr -d "'" | tr -d ' ')
case "$db_conn" in
    mysql)  required_ext+=(pdo_mysql) ;;
    pgsql)  required_ext+=(pdo_pgsql) ;;
    sqlite) required_ext+=(pdo_sqlite) ;;
    *)      warn "Unknown DB_CONNECTION='$db_conn' — skipping driver check" ;;
esac

missing=()
for ext in "${required_ext[@]}"; do
    if ! php -m | grep -qiE "^${ext}$"; then
        missing+=("$ext")
    fi
done
if [ ${#missing[@]} -gt 0 ]; then
    fail "Missing PHP extensions: ${missing[*]}. Install via your package manager (e.g. apt install php-${missing[0]})."
fi
echo "    -> all required extensions present"

# 3. PHP dependencies
step "[3/7] Installing PHP dependencies (composer)"
composer install --no-dev --optimize-autoloader --no-interaction

# 4. APP_KEY
step "[4/7] Generating APP_KEY (if missing)"
if grep -qE '^APP_KEY=base64:' .env; then
    echo "    -> APP_KEY already set"
else
    php artisan key:generate --force
fi

# 5. JS dependencies
step "[5/7] Installing JS dependencies (npm)"
if [ -f package-lock.json ]; then
    npm ci
else
    npm install
fi

# 6. Frontend build + storage link
step "[6/7] Building themes + linking storage"
for theme in calaentar default; do
    if [ -d "themes/$theme" ]; then
        echo "    -> $theme"
        npm run build "$theme"
    fi
done
php artisan storage:link --force || true

# 7. Migrations
step "[7/7] Running database migrations"
php artisan migrate --force

# Permissions hint
if [ -d storage ] && [ -d bootstrap/cache ]; then
    warn "If running under a web server, make storage/ and bootstrap/cache/"
    warn "writable by the web user (e.g. chown -R www-data:www-data .)"
fi

printf '\n\033[1;32mInstall complete.\033[0m\n'
printf 'Next steps:\n'
printf '  1. Run `php artisan app:init` to create the admin user.\n'
printf '  2. Visit your site to confirm it loads.\n'
printf '  3. From now on use ./build.sh to deploy updates.\n'
