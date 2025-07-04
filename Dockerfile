# Use the official PHP 8.2 Alpine image as the base image
FROM php:8.2-cli-alpine

# Set working directory
WORKDIR /var/www/html

# Install required PHP extensions and dependencies
RUN apk add --no-cache \
    curl \
    libzip-dev \
    git \
    && docker-php-ext-install \
    zip \
    pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Clone the PHP script from your GitHub repository
RUN git clone --branch dev https://github.com/tssaltan/ws-backuper/ .

# Update Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Verify Composer installation
RUN composer --version

RUN chmod +x /var/www/html/backuper.sh
RUN chmod +x /var/www/html/backuper-container.sh

# Set default command
ENTRYPOINT  ["sh", "-c", "/var/www/html/backuper-container.sh"]