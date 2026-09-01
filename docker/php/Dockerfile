FROM php:8.5-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libmagickwand-dev \
    libpq-dev \
    libzip-dev \
    unzip \
    ghostscript \
    && rm -rf /var/lib/apt/lists/*

# Настройка и установка расширения GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Установка ImageMagick (Imagick)
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Установка остальных расширений PHP
RUN docker-php-ext-install pdo_pgsql pgsql redis zip pcntl bcmath opcache

# Создание директории для логов PHP
RUN mkdir -p /var/log/php \
    && chown -R www-data:www-data /var/log/php

# Копирование php.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Копирование policy.xml для ImageMagick
COPY docker/php/imagemagick/policy.xml /etc/ImageMagick-6/policy.xml

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Права на storage и cache
RUN chown -R www-data:www-data /var/www/html || true
