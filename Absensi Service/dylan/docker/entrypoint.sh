#!/bin/bash
set -e

cd /var/www

# Set APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Wait for DB
echo "Waiting for database..."
until php -r "new PDO('pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
    sleep 2
    echo "Still waiting..."
done
echo "Database ready!"

# Run migrations
php artisan migrate --force

# Provide stable Swagger docs. Regeneration is optional because the static
# OpenAPI file is the source of truth for this service.
mkdir -p storage/api-docs
cp public/openapi.json storage/api-docs/api-docs.json
if [ "${L5_SWAGGER_GENERATE_ALWAYS:-false}" = "true" ]; then
    php artisan l5-swagger:generate || true
fi

# Permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"
