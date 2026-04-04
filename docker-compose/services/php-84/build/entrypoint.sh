#!/bin/bash
set -e

# ─── Ensure .env exists ───────────────────────────────────────────────────────
if [ ! -f /var/www/.env ]; then
    echo "[entrypoint] Copying .env.example → .env"
    cp /var/www/.env.example /var/www/.env
fi

# ─── Generate app key if missing ─────────────────────────────────────────────
if [ -z "$(grep -E '^APP_KEY=.+' /var/www/.env)" ]; then
    echo "[entrypoint] Generating application key..."
    php artisan key:generate --force
fi

# ─── Install Composer dependencies ───────────────────────────────────────────
if [ ! -f /var/www/vendor/autoload.php ]; then
    echo "[entrypoint] Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# ─── Fix storage permissions ──────────────────────────────────────────────────
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# ─── Wait for PostgreSQL ──────────────────────────────────────────────────────
DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"
echo "[entrypoint] Waiting for PostgreSQL at $DB_HOST:$DB_PORT..."
until (echo > /dev/tcp/$DB_HOST/$DB_PORT) 2>/dev/null; do
    sleep 2
done
echo "[entrypoint] PostgreSQL is ready."

# ─── Artisan worker mode (horizon, scheduler, queue) ─────────────────────────
# If a command was passed (e.g. "php artisan horizon"), run it directly.
if [ "$1" = "php" ]; then
    echo "[entrypoint] Worker mode — running: $*"
    exec "$@"
fi

# ─── Web container: install Node deps, build assets, migrate, start FPM ──────
echo "[entrypoint] Installing Node dependencies..."
npm install --no-audit --no-fund

echo "[entrypoint] Building frontend assets..."
npm run build

echo "[entrypoint] Running migrations..."
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] Starting PHP-FPM..."
exec php-fpm
