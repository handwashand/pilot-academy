# Pilot Academy — deployment

A plain PHP + Laravel app. No Node build step is required for the public site
(Tailwind is loaded from a CDN, Filament ships its own compiled assets).

## Requirements on the server

- PHP 8.4 with extensions: `mbstring`, `openssl`, `pdo`, **`pdo_pgsql`**,
  `fileinfo`, `curl`, `intl`, `zip`, `gd`, `bcmath`
  (`pdo_sqlite` is still needed if you are migrating an old SQLite database —
  see `docs/postgres-cutover.md`)
- PostgreSQL 14+
- Composer 2
- nginx (or Apache) + a PHP-FPM pool

## First deploy

```bash
cd /var/www
git clone -b laravel https://github.com/handwashand/pilot-academy.git
cd pilot-academy

composer install --no-dev --optimize-autoloader

cp .env.example .env
php artisan key:generate

# Create the database and its role first:
#   sudo -u postgres createuser --pwprompt pilot
#   sudo -u postgres createdb --owner=pilot pilot_academy
#
# In .env set:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://<your-domain>
#   DB_CONNECTION=pgsql
#   DB_HOST=127.0.0.1
#   DB_PORT=5432
#   DB_DATABASE=pilot_academy
#   DB_USERNAME=pilot
#   DB_PASSWORD=<the password you just set>
#   DB_SSLMODE=require        # only when the database is on another host

php artisan migrate --force --seed
php artisan filament:assets
php artisan storage:link

# Make storage writable by the web user (e.g. www-data):
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Point a server block at `public/` and you're live. Admin panel: `/admin`
(seeded login `admin@pilot.local` / `password` — change it immediately).

## Updating after a change

```bash
cd /var/www/pilot-academy

# Back up first — migrations sometimes drop a column after backfilling it,
# and a code revert without a matching migrate:rollback will not start.
pg_dump -Fc "$(sed -n 's/^DB_DATABASE=//p' .env | tail -n1)" \
    > "backup-$(date +%F-%H%M).dump"

git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan filament:assets
php artisan optimize        # re-cache config/routes/views
```

> **Run `migrate` before anyone uses the site.** Between new code landing and
> its migration running, any page whose query names a new column returns a 500 —
> `/search` does exactly that for `lessons.transcript` in 1.2.0. Normally that
> gap is a couple of seconds; it becomes an outage if the migration is skipped
> or fails.

**Rolling a release back.** The safe order depends on what the migration did:

- **It only added** columns or tables (1.2.0 is this kind) — **revert the code
  first**, then `php artisan migrate:rollback --step=<n>`. The extra columns are
  harmless to the older code, which simply ignores them.
- **It dropped or renamed** anything — **roll the migration back first**, then
  revert the code, because the older code needs those columns to exist. Restore
  the `pg_dump` above if the rollback cannot recreate the data.

> Version numbers live in `config/app.php` (`'version'`, shown at the bottom of
> the admin sidebar) and in the heading in `docs/CHANGELOG.md`. Tag the merge
> commit on `laravel` to match, e.g. `git tag v1.2.0 && git push origin v1.2.0`.

## Moving an existing SQLite database to PostgreSQL

One-time cut-over for a server still on the old SQLite file. Full runbook with
verification and rollback: `docs/postgres-cutover.md`.

## nginx server block (subdomain example)

```nginx
server {
    listen 80;
    server_name academy.example.com;
    root /var/www/pilot-academy/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Then add HTTPS with `certbot --nginx -d academy.example.com`.
