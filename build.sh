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

# 1. PHP dependencies
step "[1/6] Installing PHP dependencies"
if [ "$DEV" -eq 1 ]; then
    composer install --optimize-autoloader --no-interaction
else
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# 2. JS dependencies
step "[2/6] Installing JS dependencies"
if [ -f package-lock.json ]; then
    npm ci
else
    npm install
fi

# 3. Frontend build — both shipped themes
step "[3/6] Building theme assets"
for theme in calaentar default; do
    if [ -d "themes/$theme" ]; then
        echo "    -> $theme"
        npm run build "$theme"
    fi
done

# 4. Storage symlink (no-op if already linked)
step "[4/6] Linking public storage"
php artisan storage:link --force || true

# 5. Migrations
step "[5/6] Running database migrations"
if [ "$DEV" -eq 1 ]; then
    php artisan migrate
else
    php artisan migrate --force
fi

# 6. Clear caches so the new code/assets actually serve
step "[6/6] Clearing Laravel caches"
php artisan optimize:clear

printf '\n\033[1;32mBuild complete.\033[0m\n'
printf 'Active theme is set in admin -> Settings -> Theme.\n'
