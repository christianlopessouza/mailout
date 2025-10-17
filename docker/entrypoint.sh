#!/bin/bash
set -e

echo "[ENTRYPOINT] Iniciando container Laravel..."

echo "Removendo config cache antigo (se existir)..."
php artisan config:clear

echo "Ajustando permissões de cache e storage..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "Ajustando permissões de log..."
touch /var/www/storage/logs/laravel.log
chown www-data:www-data /var/www/storage/logs/laravel.log
chmod 664 /var/www/storage/logs/laravel.log

echo "[ENTRYPOINT] Executando migrations..."
php /var/www/artisan migrate --force

echo "[ENTRYPOINT] Inicializando serviço..."
exec "$@"
