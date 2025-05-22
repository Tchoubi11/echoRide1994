FROM php:8.3-fpm-alpine

# Ajouter explicitement le dépôt community et mettre à jour les indices
RUN apk update && \
    apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    zip \
    git \
    bash \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql xml opcache intl mbstring \
    && apk del .build-deps

# Nettoyage des paquets inutiles (si tu n'as pas besoin de Perl)
RUN apk del perl perl-error

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet dans le conteneur
COPY . .

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installer les dépendances via Composer
RUN composer install --no-dev --optimize-autoloader --prefer-dist && ls -la vendor

# Nettoyer les fichiers APK inutiles
RUN rm -rf /var/cache/apk/*

# Exposer le port 9000 pour PHP-FPM
EXPOSE 9000

# Lancer PHP-FPM
CMD ["php-fpm"]
