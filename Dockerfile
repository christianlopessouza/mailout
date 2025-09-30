FROM php:8.4-fpm

# System deps + PHP extensions
RUN apt-get update && apt-get install -y \
    nginx supervisor curl git unzip zip \
    libpng-dev libonig-dev libxml2-dev libzip-dev libpq-dev \
 && docker-php-ext-install \
    pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd \
 && rm -rf /var/lib/apt/lists/*

# (Opcional) cliente MySQL para testes dentro do container
# RUN apt-get update && apt-get install -y default-mysql-client && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# App
WORKDIR /var/www
COPY . .

# Permissões Laravel
RUN chown -R www-data:www-data /var/www \
 && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# PHP ini (upload limits)
RUN echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size = 55M" >> /usr/local/etc/php/conf.d/uploads.ini

# NGINX
COPY docker/nginx/nginx.conf /etc/nginx/sites-available/default
RUN rm -f /etc/nginx/sites-enabled/default \
 && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Scripts de inicialização
COPY docker/*.sh /usr/local/bin/
RUN sed -i 's/\r$//' /usr/local/bin/*.sh \
 && chmod +x /usr/local/bin/*.sh

# Dependências PHP e caches do Laravel
RUN composer install --optimize-autoloader --no-dev \
 && php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord"]