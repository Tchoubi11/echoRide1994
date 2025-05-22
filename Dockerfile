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
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-png-dir=/usr/include \
    && docker-php-ext-install gd pdo pdo_mysql xml opcache \
    && apk del libpng-dev libjpeg-turbo-dev freetype-dev

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet dans le conteneur
COPY . .

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Nettoyer les fichiers APK inutiles
RUN rm -rf /var/cache/apk/*

# Exposer le port 9000 pour PHP-FPM
EXPOSE 9000

# Lancer PHP-FPM
CMD ["php-fpm"]
