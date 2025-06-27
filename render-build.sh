#!/usr/bin/env bash
# Exit on error
set -o errexit

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Generate Laravel app key
php artisan key:generate

# Run database migrations (if needed)
php artisan migrate --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
