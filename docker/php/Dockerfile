FROM php:8.3-fpm-alpine

# Extensions PHP requises
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    openssl-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        gd \
        intl \
        mbstring \
        opcache

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuration PHP production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
 && echo "upload_max_filesize=10M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=12M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/memory.ini \
 && echo "max_execution_time=30" >> /usr/local/etc/php/conf.d/timeout.ini \
 && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/security.ini \
 && echo "session.use_strict_mode=1" >> /usr/local/etc/php/conf.d/security.ini \
 && echo "session.cookie_httponly=1" >> /usr/local/etc/php/conf.d/security.ini \
 && echo "session.cookie_secure=1" >> /usr/local/etc/php/conf.d/security.ini \
 && echo "session.cookie_samesite=Lax" >> /usr/local/etc/php/conf.d/security.ini

WORKDIR /var/www/koonect

# Créer les répertoires de stockage
RUN mkdir -p storage/uploads/articles \
             storage/uploads/authors \
             storage/uploads/tmp \
             storage/cache/pages \
             storage/cache/queries \
             storage/logs \
    && chown -R www-data:www-data storage

COPY . .
RUN chown -R www-data:www-data /var/www/koonect

USER www-data
