FROM php:8.3-fpm-alpine

# 1. Install base + dev dependencies
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

# 2. Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# 3. Set working directory
WORKDIR /var/www/html

# 4. Copy app source code
COPY . .

# 5. Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# 6. Set permissions
RUN chown -R www-data:www-data /var/www/html

# 7. Expose port
EXPOSE 9000

# 8. Run PHP-FPM
CMD ["php-fpm"]
