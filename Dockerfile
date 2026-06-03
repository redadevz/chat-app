# Local development image for the chat app (Laravel 12 + Craftable PRO).
# Single PHP container that serves the app via `php artisan serve`.
# Vite/HMR runs in a separate `node` service (see docker-compose.yml).
FROM php:8.4-cli

# System libraries needed to compile the PHP extensions below.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libicu-dev \
        libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_sqlite \
        mbstring \
        bcmath \
        gd \
        zip \
        intl \
        exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer (copied from the official image — no global install needed).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# First-run setup (vendor install, app key, sqlite, migrate) lives here so the
# image stays generic and the bind-mounted source drives everything.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
