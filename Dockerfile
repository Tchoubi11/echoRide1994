FROM php:8.3-fpm-alpine

# Installation des dépendances nécessaires à PHP et à Composer
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    libicu-dev \
    zip \
    git \
    bash

# Configuration et installation de l'extension GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Installation des autres extensions PHP requises
RUN docker-php-ext-install pdo pdo_mysql intl mbstring

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet dans le conteneur
COPY . .

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Nettoyer le cache Composer + installer les dépendances
RUN composer clear-cache && \
    composer install --no-dev --optimize-autoloader --prefer-dist

# Nettoyer les fichiers APK inutiles
RUN rm -rf /var/cache/apk/*

# Exposer le port 9000 pour PHP-FPM
EXPOSE 9000

# Lancer PHP-FPM
CMD ["php-fpm"]
