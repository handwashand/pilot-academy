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

DB_DRIVER=$(sed -n 's/^DB_CONNECTION=//p' .env | tail -n 1)
[ -n "$DB_DRIVER" ] || DB_DRIVER=sqlite

if [ "$DB_DRIVER" = "sqlite" ]; then
    DB_FILE=$(sed -n 's/^DB_DATABASE=//p' .env | tail -n 1)
    [ -n "$DB_FILE" ] || DB_FILE=/var/www/html/database/database.sqlite
    if [ "$DB_FILE" != ":memory:" ] && [ ! -f "$DB_FILE" ]; then
        mkdir -p "$(dirname "$DB_FILE")"
        touch "$DB_FILE"
        FRESH_DB=1
    fi
else
    # A server-backed database may still be starting. Compose already waits on
    # its healthcheck, but a bare `docker run` does not.
    i=0
    until php artisan db:monitor >/dev/null 2>&1 || [ "$i" -ge 30 ]; do
        i=$((i + 1))
        sleep 2
    done

    # No migrations table yet means nothing has ever run here, so it is safe
    # to seed. Never infer this from the database file existing.
    php artisan migrate:status >/dev/null 2>&1 || FRESH_DB=1
fi

php artisan migrate --force

# Only seed a brand new database, so restarts never overwrite test data.
if [ -n "$FRESH_DB" ]; then
    php artisan db:seed --force
fi

php artisan storage:link --quiet || true
php artisan optimize:clear

exec "$@"
