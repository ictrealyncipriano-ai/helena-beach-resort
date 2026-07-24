#!/bin/bash
set -e

export LOG_CHANNEL=stderr

php artisan config:cache
php artisan storage:link --force 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan migrate --force
php artisan db:seed --force || true

exec apache2-foreground
