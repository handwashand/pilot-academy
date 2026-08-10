FROM php:8.4-cli

# The base image already bundles mbstring, dom/xml, sqlite3 and pdo_sqlite.
# Added here: zip (composer), gd (dompdf certificates + QR codes), intl, bcmath.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl zip \
    && rm -rf /var/lib/apt/lists/*

# The base image ships no php.ini at all, which leaves short_open_tag on --
# Blade then mis-compiles the `<?xml` literal in the sitemap view.
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

# Dependency layer first so edits to app code do not re-download packages.
# Laravel's post-autoload-dump scripts need the app itself, which is not here
# yet, so skip the scripts and the autoloader until after the code is copied.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . ./

# Config goes in .env, never the container environment: real env vars outrank
# phpunit.xml's <env> block, which would silently run the suite against the
# live SQLite file instead of :memory:.
RUN cp -n .env.example .env \
    && sed -i \
        -e 's|^APP_URL=.*|APP_URL=http://localhost:8000|' \
        -e 's|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|' \
        -e 's|^# DB_DATABASE=.*|DB_DATABASE=/data/database.sqlite|' \
        .env \
    && composer dump-autoload --optimize

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint && chmod +x /usr/local/bin/entrypoint

EXPOSE 8000

ENTRYPOINT ["entrypoint"]
# --no-reload is what lets PHP_CLI_SERVER_WORKERS apply; without concurrent
# workers the built-in server serialises Filament's many asset requests.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
