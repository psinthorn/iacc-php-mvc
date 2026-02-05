FROM php:7.4-fpm

# Install required PHP extensions
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

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY ./iacc /var/www/html

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Configure PHP-FPM to listen on all interfaces (required for Docker networking)
RUN sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
