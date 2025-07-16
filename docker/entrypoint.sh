#!/bin/bash
echo "Caching config..."
php artisan config:clear
php artisan config:cache

echo "Laravel log config..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

touch /var/www/storage/logs/laravel.log
chown www-data:www-data /var/www/storage/logs/laravel.log
chmod 664 /var/www/storage/logs/laravel.log

until php /var/www/artisan db:show &>/dev/null; do
  echo "[ENTRYPOINT] Waiting for database..."
  sleep 2
done

php /var/www/artisan migrate --force

exec "$@"
