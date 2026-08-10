#!/bin/sh
# Bootstraps the container before handing over to the real command.
set -e

cd /var/www/html

# Volume mounts can start empty, so recreate the writable skeleton Laravel needs.
mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/app/private \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force

DB_FILE=$(sed -n 's/^DB_DATABASE=//p' .env | tail -n 1)
[ -n "$DB_FILE" ] || DB_FILE=/var/www/html/database/database.sqlite
if [ "$DB_FILE" != ":memory:" ] && [ ! -f "$DB_FILE" ]; then
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    FRESH_DB=1
fi

php artisan migrate --force

# Only seed a brand new database, so restarts never overwrite test data.
if [ -n "$FRESH_DB" ]; then
    php artisan db:seed --force
fi

php artisan storage:link --quiet || true
php artisan optimize:clear

exec "$@"
