#!/bin/sh
set -eu

port="${PORT:-10000}"
sed -i "s/10000/${port}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

php artisan migrate --force
php artisan app:sync-admin

exec "$@"
