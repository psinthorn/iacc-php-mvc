FROM php:8.2-fpm

# Install required PHP extensions and system dependencies
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install mysqli pdo pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better Docker layer caching
COPY composer.json ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist 2>/dev/null || true

# Copy application files
COPY . /var/www/html

# Install/update Composer dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Configure PHP-FPM to listen on all interfaces (required for Docker networking)
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
