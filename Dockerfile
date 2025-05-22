# Utiliser l'image PHP 8.3 FPM Alpine
FROM php:8.3-fpm-alpine

# Mettre à jour les dépôts et installer les dépendances nécessaires pour Symfony et composer
RUN apk update && apk --no-cache add \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    zip \
    git \
    bash \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql xml opcache intl mbstring \
    && apk del libpng-dev libjpeg-turbo-dev freetype-dev

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet dans le conteneur
COPY . .

# Vider le cache de Composer avant d'exécuter la commande
RUN composer clear-cache

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Exécuter Composer pour installer les dépendances (mode verbeux pour avoir plus d'infos en cas d'erreur)
RUN composer install --no-dev --optimize-autoloader --prefer-dist -vvv

# Nettoyer les fichiers APK inutiles
RUN rm -rf /var/cache/apk/*

# Exposer le port 9000 pour PHP-FPM
EXPOSE 9000

# Lancer PHP-FPM
CMD ["php-fpm"]
