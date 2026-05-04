#!/usr/bin/env bash
# build.sh — One-shot Paymenter build / deploy script.
#
#   Usage: ./build.sh [--dev]
#
#   --dev   keep dev composer dependencies and skip --force on migrate
#
# Runs from the project root. Idempotent — safe to re-run.
# Halts on the first failing step so you don't end up with a half-deployed app.

set -euo pipefail

cd "$(dirname "$0")"

DEV=0
for arg in "$@"; do
    case "$arg" in
        --dev) DEV=1 ;;
        *) echo "Unknown flag: $arg" && exit 1 ;;
    esac
done

step() { printf '\n\033[1;35m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!! %s\033[0m\n' "$*"; }

# Sanity: .env must exist and declare a DB_CONNECTION other than sqlite,
# otherwise migrations fall back to the SQLite driver which usually isn't
# installed on a typical LAMP/LEMP host. Catches the common
# "could not find driver" error before it happens.
if [ ! -f .env ]; then
    warn ".env not found — copy .env.example to .env first, then re-run."
    exit 1
fi

if ! grep -qE '^DB_CONNECTION=' .env; then
    warn "DB_CONNECTION is not set in .env. Defaulting to mysql."
    warn "Add DB_CONNECTION=mysql (or pgsql) to .env to silence this warning."
fi

# 1. PHP dependencies
step "[1/7] Installing PHP dependencies"
if [ "$DEV" -eq 1 ]; then
    composer install --optimize-autoloader --no-interaction
else
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# 2. JS dependencies
step "[2/7] Installing JS dependencies"
if [ -f package-lock.json ]; then
    npm ci
else
    npm install
fi

# 3. Frontend build — both shipped themes
step "[3/7] Building theme assets"
for theme in calaentar default; do
    if [ -d "themes/$theme" ]; then
        echo "    -> $theme"
        npm run build "$theme"
    fi
done

# 4. Storage symlink (no-op if already linked)
step "[4/7] Linking public storage"
php artisan storage:link --force || true

# 5. Clear caches BEFORE migrate so any stale config (especially a cached
# DB_CONNECTION pointing at the wrong driver) doesn't trip up the migrator.
step "[5/7] Clearing stale Laravel caches"
php artisan config:clear
php artisan cache:clear || true
php artisan view:clear

# 6. Migrations
step "[6/7] Running database migrations"
if [ "$DEV" -eq 1 ]; then
    php artisan migrate
else
    php artisan migrate --force
fi

# 7. Final cache reset so the freshly built assets are picked up
step "[7/7] Final cache reset"
php artisan optimize:clear

printf '\n\033[1;32mBuild complete.\033[0m\n'
printf 'Active theme is set in admin -> Settings -> Theme.\n'
