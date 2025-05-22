FROM php:8.3-fpm-alpine

# 1. Mise à jour et installation des dépendances système
RUN apk update && apk add --no-cache \
    bash \
    git \
    zip \
    unzip \
    icu \
    icu-libs \
    icu-data-full \
    oniguruma \
    libpng \
    libjpeg-turbo \
    freetype \
    libxml2 \
    && apk add --no-cache --virtual .build-deps \
    autoconf \
    g++ \
    make \
    icu-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    && docker-php-ext-configure gd \
      --with-freetype \
      --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
      intl \
      pdo \
      pdo_mysql \
      opcache \
      xml \
      mbstring \
      gd \
    && apk del .build-deps

# 2. Dossier de travail
WORKDIR /var/www/html

# 3. Copier les fichiers de l'application
COPY . .

# 4. Droits pour www-data
RUN chown -R www-data:www-data /var/www/html

# 5. Exposer le port PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
