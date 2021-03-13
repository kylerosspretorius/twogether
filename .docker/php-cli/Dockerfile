FROM php:7.4-fpm-alpine

RUN apk add --no-cache \
    unzip \
    libxml2-dev \
    libmcrypt-dev \
    freetype-dev \
    libpng-dev \
    libzip-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    bcmath \
    pcntl
