FROM php:8.4-apache

# Install PHP extensions that OpenCart needs
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install libraries needed for image processing and zip files
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip opcache

# Enable Apache mod_rewrite (needed for SEO-friendly URLs)
RUN a2enmod rewrite

# Install Xdebug (for debugging and performance profiling)
RUN pecl install xdebug && docker-php-ext-enable xdebug