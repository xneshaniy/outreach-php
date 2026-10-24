#!/usr/bin/env bash
# Build script for the Hostinger PHP application.
#
# public/build/ (Vite output) is committed to git, so Node.js is NOT required
# on the server. Run this from the repository root after `git pull`.

set -euo pipefail

echo "=== Outreach CRM: Hostinger build ==="

command -v php >/dev/null 2>&1 || { echo "ERROR: php is not available in PATH"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "ERROR: composer is not available in PATH"; exit 1; }

echo "Installing PHP dependencies (no dev packages)..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Checking prebuilt frontend assets..."
if [ ! -f "public/build/manifest.json" ]; then
    echo "ERROR: public/build/manifest.json is missing."
    echo "Run 'npm ci && npm run build' locally, commit public/build, and redeploy."
    exit 1
fi

echo "Preparing writable directories..."
mkdir -p storage/logs \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         bootstrap/cache

echo "Rebuilding Laravel caches..."
php artisan optimize:clear
php artisan optimize

echo "=== Build completed successfully ==="
php artisan --version
ls -la public/build/manifest.json
