FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions (GD with JPEG/Freetype support for QR codes, plus PDO MySQL)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Enable .htaccess overrides for Apache
RUN echo '<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Custom PHP settings for uploads, timezone, and execution limits
RUN echo 'upload_max_filesize = 50M\n\
post_max_size = 50M\n\
memory_limit = 256M\n\
max_execution_time = 120\n\
date.timezone = Asia/Kuala_Lumpur\n' > /usr/local/etc/php/conf.d/custom.ini

# Get Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install Composer dependencies first to leverage Docker layer caching
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader || true

# Copy application source
COPY . /var/www/html/

# Ensure uploads directory structure exists and is writable by the Apache user (www-data)
RUN mkdir -p uploads/buying_requests \
             uploads/claims \
             uploads/landing \
             uploads/lost_and_found \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
