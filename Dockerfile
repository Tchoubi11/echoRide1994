FROM php:8.3-fpm-alpine

# 1. Installation des dépendances système et PHP nécessaires
RUN apk add --no-cache \
    bash \
    git \
    zip \
    unzip \
    libpng \
    libjpeg-turbo \
    freetype \
    icu \
    icu-libs \
    libxml2 \
    oniguruma \
    && apk add --no-cache --virtual .build-deps \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    intl \
    mbstring \
    xml \
    opcache \
    gd \
    && apk del .build-deps

# 2. Installer Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 3. Définir le dossier de travail
WORKDIR /var/www/html

# 4. Copier les fichiers de l'application
COPY . .

# 5. Définir les droits
RUN chown -R www-data:www-data /var/www/html

# 6. Explication (facultative) du port PHP-FPM exposé
EXPOSE 9000

CMD ["php-fpm"]
