# Pilot Academy — deployment

A plain PHP + Laravel app. No Node build step is required for the public site
(Tailwind is loaded from a CDN, Filament ships its own compiled assets).

## Requirements on the server

- PHP 8.2+ with extensions: `mbstring`, `openssl`, `pdo`, `pdo_sqlite` (or
  `pdo_pgsql`), `fileinfo`, `curl`, `intl`, `zip`
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

# Use a file-based SQLite database (simplest):
touch database/database.sqlite
# In .env set:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://<your-domain>
#   DB_CONNECTION=sqlite
#   DB_DATABASE=/var/www/pilot-academy/database/database.sqlite

php artisan migrate --force --seed
php artisan filament:assets
php artisan storage:link

# Make storage & the sqlite file writable by the web user (e.g. www-data):
chown -R www-data:www-data storage bootstrap/cache database

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Point a server block at `public/` and you're live. Admin panel: `/admin`
(seeded login `admin@pilot.local` / `password` — change it immediately).

## Updating after a change

```bash
cd /var/www/pilot-academy
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan filament:assets
php artisan optimize        # re-cache config/routes/views
```

> Run `migrate` **before** anyone uses the site, and roll the code back
> **before** the migration if you have to undo a release. Between new code
> landing and its migration running, any page whose query names a new column
> returns a 500 — `/search` does exactly that for `lessons.transcript` in 1.2.0.
> The gap is normally a couple of seconds; it becomes an outage only if the
> migration fails or is skipped.

> Version numbers live in `config/app.php` (`'version'`, shown at the bottom of
> the admin sidebar) and in the heading in `docs/CHANGELOG.md`. Tag the merge
> commit on `laravel` to match, e.g. `git tag v1.2.0 && git push origin v1.2.0`.

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
