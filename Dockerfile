FROM php:8.3-fpm-alpine

# 1. Installer les dépendances système + PHP
RUN apk add --no-cache \
    bash git zip unzip \
    libpng libjpeg-turbo freetype icu libxml2 oniguruma \
    && apk add --no-cache --virtual .build-deps \
    icu-dev libpng-dev libjpeg-turbo-dev freetype-dev libxml2-dev oniguruma-dev autoconf g++ make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql intl mbstring xml opcache gd \
    && apk del .build-deps

# 2. Copier Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 3. Définir le dossier de travail
WORKDIR /var/www/html

# 4. Copier les fichiers nécessaires (ATTENTION À .dockerignore)
COPY . .

# 5. Installer les dépendances PHP (si vendor absent)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# 6. Droits pour www-data
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
