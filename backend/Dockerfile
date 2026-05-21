FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    gettext \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        opcache \
        pcntl \
        mbstring \
        intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Cache composer dependencies separately for faster rebuilds
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-dev --no-interaction --prefer-dist

# Copy application source
COPY . .

# Finalise autoloader
RUN composer dump-autoload --optimize --no-dev

# Permissions
RUN mkdir -p /var/www/storage/logs /var/www/storage/framework/cache /var/www/storage/framework/sessions /var/www/storage/framework/views \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Copy docker config files
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy startup scripts
COPY docker/start.sh /start.sh
COPY docker/start-reverb.sh /start-reverb.sh
COPY docker/start-worker.sh /start-worker.sh
RUN chmod +x /start.sh /start-reverb.sh /start-worker.sh

EXPOSE 8000

CMD ["/start.sh"]
