#!/bin/bash
set -e

export LOG_CHANNEL=stderr

php artisan config:cache
php artisan migrate --force
php artisan db:seed --force 2>/dev/null || true

exec apache2-foreground
