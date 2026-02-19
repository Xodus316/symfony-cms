FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    pkg-config \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli \
    zip \
    intl \
    opcache \
    bcmath

# Install MongoDB extension
RUN pecl install mongodb-1.21.0 \
    && docker-php-ext-enable mongodb

# Install Redis extension
RUN pecl install redis-6.1.0 \
    && docker-php-ext-enable redis

# Install APCu
RUN pecl install apcu-5.1.24 \
    && docker-php-ext-enable apcu

# Install and configure Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/symfony

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set proper permissions
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
RUN chown -R www-data:www-data /var/www

USER www-data

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
