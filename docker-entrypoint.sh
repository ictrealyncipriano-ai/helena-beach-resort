#!/bin/bash
set -e

php artisan migrate --force

if [ "$(php -r 'echo \App\Models\User::count();' 2>/dev/null || echo 0)" -eq 0 ]; then
    php artisan db:seed --force
fi

exec apache2-foreground
