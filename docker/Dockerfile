FROM php:8.4-apache

# Install PHP extensions that OpenCart needs
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install libraries needed for image processing and zip files
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libavif-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
    && docker-php-ext-install gd zip opcache

# Enable Apache modules (rewrite, compression, caching, security headers)
RUN a2enmod rewrite deflate expires headers

# Allow .htaccess overrides (required for SEO URLs, caching, security)
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Install Xdebug (for debugging and performance profiling)
RUN pecl install xdebug && docker-php-ext-enable xdebug