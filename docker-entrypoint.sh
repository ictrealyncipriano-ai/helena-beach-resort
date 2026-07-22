#!/bin/bash
set -e

php artisan migrate --force
php artisan db:seed --force 2>/dev/null || true

exec apache2-foreground
