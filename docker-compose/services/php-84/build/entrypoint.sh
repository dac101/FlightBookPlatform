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
# chown is intentionally skipped: macOS bind mounts do not permit ownership
# changes from inside Docker containers. chmod 777 ensures all container
# processes (php-fpm, horizon, scheduler) can read and write freely in dev.
chmod -R 777 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

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

# ─── Reprocess aviation_stack files on first boot ────────────────────────────
FIRST_BOOT_FLAG=/var/www/storage/app/.first-boot-done
if [ ! -f "$FIRST_BOOT_FLAG" ]; then
    echo "[entrypoint] First boot detected — reprocessing aviation_stack files..."
    php artisan flights:reprocess && touch "$FIRST_BOOT_FLAG"
fi

echo "[entrypoint] Starting PHP-FPM..."
exec php-fpm
