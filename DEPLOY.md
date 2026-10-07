# Pilot Academy — deployment

A plain PHP + Laravel app. **No Node build step runs on the server**: the Vite
bundle is compiled in CI and committed to `public/build/`, so `git pull` brings
the stylesheets with the code.

> Both the student site and the `/admin` panel now depend on that committed
> bundle. The panel loads its own theme (`resources/css/filament/admin/theme.css`)
> through Vite, and a **missing or stale `public/build/manifest.json` takes the
> whole panel down with "Unable to locate file in Vite manifest"** — not just
> the styling. Never deploy a `public/build/` that CI has not rebuilt for the
> commit you are shipping, and never delete it to "free space".

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
> `/search` does exactly that for `lessons.transcript` in 2.0.0. Normally that
> gap is a couple of seconds; it becomes an outage if the migration is skipped
> or fails.

**Rolling a release back.** The safe order depends on what the migration did:

- **It only added** columns or tables (2.0.0 is this kind) — **revert the code
  first**, then `php artisan migrate:rollback --step=<n>`. The extra columns are
  harmless to the older code, which simply ignores them.
- **It dropped or renamed** anything — **roll the migration back first**, then
  revert the code, because the older code needs those columns to exist. Restore
  the `pg_dump` above if the rollback cannot recreate the data.

> Version numbers live in `config/app.php` (`'version'`, shown at the bottom of
> the admin sidebar) and in the heading in `docs/CHANGELOG.md`. Tag the merge
> commit on `laravel` to match, e.g. `git tag v2.0.0 && git push origin v2.0.0`.

## Descript video translation (optional)

Translates the speech in uploaded lesson videos. **Off by default**; leave it off
until it has been proven on the live account — see
`docs/descript-integration.md`, steps 0.5, 0.6 and 9.

The token can be added by an admin under **Settings → Integrations** (stored encrypted; once saved there, that page's switch decides). Or set it in `.env` (never in the repository):

```bash
DESCRIPT_ENABLED=true
DESCRIPT_API_TOKEN=<from Descript, Settings → API>
DESCRIPT_API_BASE_URL=https://descriptapi.com/v1
DESCRIPT_PROJECT_FOLDER="Pilot Academy/Transcriptions"
DESCRIPT_TEAM_ACCESS=view      # what drive members may do with each project: view, comment or edit
DESCRIPT_TIMEOUT_SECONDS=30
```

then `php8.4 artisan optimize` so the cached config picks it up.

The environment values enable and connect the integration for the whole site;
they do not grant anyone access to it. In the panel, an admin must open
**People → Users**, open each permitted staff account, and tick **Use Descript
video translation** under **Extra permissions**. Admins and creators do not
receive this credit-using right automatically. Starting a translation also
requires an acknowledgement in its confirmation dialog.

`APP_URL` must be the real public address: Descript fetches each video from
`APP_URL/storage/…` itself. On a server it cannot reach, the app uploads the file
instead, which ties up a web request for the length of the upload.

Each finished translation also leaves a private audio page for that composition in the Descript drive (its subtitles are how the translated words are read back); the API cannot delete them, so tidy them there if you wish.

There is no queue worker, so translations move on when an editor presses
**Check progress** on the lesson — or when this runs. Safe to run as often as
you like; it never pays for a translation twice:

```bash
php8.4 artisan descript:sync
```

To have it run on its own, a cron line every five minutes does it:

```cron
*/5 * * * * cd /var/www/pilot-academy && php8.4 artisan descript:sync >> /dev/null 2>&1
```

**Rollback:** set `DESCRIPT_ENABLED=false` and run `optimize` again. The buttons
disappear; every translation already saved stays, and lessons keep showing it.

## Translation providers: DeepL, ChatGPT, DeepSeek (optional)

Nothing to set on the server. An admin opens **Settings → Integrations**, pastes
each provider's API token and switches it on; the token is stored encrypted in
the database (`ai_providers`, created by `php8.4 artisan migrate --force`) and
is sent only to that provider's own address. Create each key with a spending
limit on the provider's site. **Back up the database and keep `APP_KEY`**: the
tokens are encrypted with it, so a new key makes saved tokens unreadable (paste
them again). A DeepL key in `.env` (`DEEPL_ENABLED`, `DEEPL_API_KEY`) still works
when none is saved on the page. Anyone who may use a provider is chosen under
**People → Users → Extra permissions**.

**Rollback:** switch the provider off on the page. The buttons disappear;
saved translations and manual editing are untouched.

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
