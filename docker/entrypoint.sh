#!/bin/bash
set -e

echo "[ENTRYPOINT] Iniciando container Laravel..."

echo "Limpando todos os caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Ajustando permissões de cache e storage..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "Ajustando permissões de log..."
touch /var/www/storage/logs/laravel.log
chown www-data:www-data /var/www/storage/logs/laravel.log
chmod 664 /var/www/storage/logs/laravel.log

echo "[ENTRYPOINT] Executando migrations..."
php /var/www/artisan migrate --force

echo "Limpando cache do Nginx..."
rm -rf /var/cache/nginx/* 2>/dev/null || true
rm -rf /var/run/nginx.pid 2>/dev/null || true

echo "[ENTRYPOINT] Inicializando serviço..."
exec "$@"
