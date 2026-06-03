#!/usr/bin/env bash
# First-run setup for the dev container. Idempotent — safe to run on every boot.
set -e

cd /var/www/html

# 1. Ensure .env exists (copy the example on a fresh checkout).
if [ ! -f .env ]; then
  echo "[entrypoint] .env missing — copying .env.example"
  cp .env.example .env
fi

# 2. Install PHP dependencies if vendor/ isn't mounted/populated yet.
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
  echo "[entrypoint] installing composer dependencies"
  composer install --no-interaction --prefer-dist
fi

# 3. Generate an app key if one isn't set.
if ! grep -q "^APP_KEY=base64:" .env; then
  echo "[entrypoint] generating APP_KEY"
  php artisan key:generate --force
fi

# 4. Ensure the SQLite database file exists.
if [ ! -f database/database.sqlite ]; then
  echo "[entrypoint] creating sqlite database file"
  mkdir -p database
  touch database/database.sqlite
fi

# 5. Run migrations (no-op if already migrated).
echo "[entrypoint] running migrations"
php artisan migrate --force

# 6. Clear stale caches so config picks up the container env.
php artisan optimize:clear || true

echo "[entrypoint] ready — starting: $*"
exec "$@"
