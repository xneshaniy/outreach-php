#!/bin/bash
# Hostinger Build Script for Laravel Application
# This script runs during Hostinger deployment

set -e  # Exit on any error

echo "=== Starting Laravel Deployment Build ==="

# 1. Install Composer dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Install npm dependencies
echo "Installing npm dependencies..."
npm ci

# 3. Build frontend assets with Vite
echo "Building frontend assets..."
npm run build

# 4. Verify build output
if [ ! -f "public/build/manifest.json" ]; then
    echo "ERROR: Vite build failed - manifest.json not found"
    exit 1
fi

echo "=== Build completed successfully ==="
echo "Assets available in public/build/"
ls -la public/build/