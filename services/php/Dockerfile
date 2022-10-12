FROM php:8.1-fpm

RUN apt update && apt install -y \
    gnupg \
    g++ \
    procps \
    openssl \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev  \
    libonig-dev \
    libxslt1-dev \
    acl \
    chromium

RUN docker-php-ext-configure gd --with-jpeg --with-freetype

# Enable sockets for rabbitmq
RUN docker-php-ext-install pdo pdo_mysql zip xsl gd intl opcache exif mbstring sockets

# Install node
# RUN curl -sL https://deb.nodesource.com/setup_14.x | bash && apt -y install nodejs

WORKDIR /var/www/html

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -sS https://get.symfony.com/cli/installer | bash
