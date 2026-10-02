#!/bin/sh
# Prepares the app for the container's main process. storage/ is usually a
# bind-mounted volume, so its directory layout cannot be baked into the image.
set -e

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan storage:link --force >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    su -s /bin/sh www-data -c "php artisan migrate --force"
fi

su -s /bin/sh www-data -c "php artisan optimize"

exec "$@"
