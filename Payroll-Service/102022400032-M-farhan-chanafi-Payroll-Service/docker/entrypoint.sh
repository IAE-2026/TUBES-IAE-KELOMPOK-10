#!/bin/sh
set -e

cd /app

# Buat file SQLite jika belum ada
mkdir -p /app/database
if [ ! -f "/app/database/database.sqlite" ]; then
    touch /app/database/database.sqlite
    echo "[payroll-service] SQLite database dibuat."
fi

# Buat storage dirs
mkdir -p /app/storage/logs \
         /app/storage/framework/cache \
         /app/storage/framework/sessions \
         /app/storage/framework/views

# Generate APP_KEY jika belum ada di env
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Jalankan migrasi
php artisan migrate --force
echo "[payroll-service] Migrasi selesai."

# Start server
echo "[payroll-service] Starting on 0.0.0.0:8000 ..."
exec php artisan serve --host=0.0.0.0 --port=8000
