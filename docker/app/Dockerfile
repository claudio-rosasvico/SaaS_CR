FROM php:8.3-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    git curl zip unzip bash shadow libpng-dev libjpeg-turbo-dev libwebp-dev libxpm-dev oniguruma-dev \
    icu-dev libzip-dev zlib-dev autoconf g++ make \
    poppler-utils

# Extensiones PHP
RUN docker-php-ext-configure gd --with-jpeg --with-webp
RUN docker-php-ext-install pdo_mysql mbstring intl zip bcmath exif gd pcntl

# Redis ext
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Usuario no root (opcional, útil para permisos en bind mounts)
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

WORKDIR /var/www

# Opcional: php.ini básico
RUN { \
  echo "memory_limit=512M"; \
  echo "upload_max_filesize=32M"; \
  echo "post_max_size=32M"; \
  echo "max_execution_time=120"; \
} > /usr/local/etc/php/conf.d/zz-app.ini

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]
