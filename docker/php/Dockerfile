FROM php:8.5-fpm

# Системные зависимости
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
    && docker-php-ext-install pdo_pgsql pgsql bcmath gd intl mbstring xml zip pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка Redis из исходного кода (GitHub master)
RUN git clone https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/phpredis

# MongoDB PHP driver. PECL's channel sometimes answers "No releases available",
# so install from a pinned tarball instead of `pecl install mongodb`.
RUN curl -fsSL https://pecl.php.net/get/mongodb-2.5.5.tgz -o /tmp/mongodb-2.5.5.tgz \
    && pecl install /tmp/mongodb-2.5.5.tgz \
    && docker-php-ext-enable mongodb \
    && rm -f /tmp/mongodb-2.5.5.tgz

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Рабочая директория
WORKDIR /var/www/html

# Разрешаем Git работать в этой папке внутри контейнера
RUN git config --global --add safe.directory /var/www/html

# Установка прав
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]