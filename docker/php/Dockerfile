FROM php:8.5-fpm

# 1. Системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    autoconf \
    build-essential \
    && docker-php-ext-install pdo_pgsql pgsql bcmath gd intl mbstring xml zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Установка Redis из исходного кода (GitHub master)
RUN git clone https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis

# 3. Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Рабочая директория
WORKDIR /var/www/html

# 5. ИСПРАВЛЕНИЕ: Разрешаем Git работать в этой папке внутри контейнера
RUN git config --global --add safe.directory /var/www/html

# 6. Установка прав
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]