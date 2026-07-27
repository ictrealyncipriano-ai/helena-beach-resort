#!/bin/bash
set -e

export LOG_CHANNEL=stderr

php artisan config:cache
php artisan storage:link --force 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan migrate --force

# Seed on every deploy (seeders use firstOrCreate, so they won't overwrite user data)
php artisan db:seed --force || php artisan db:seed --force --class=FaqSeeder || true

exec apache2-foreground
