FROM php:7.1-apache

RUN apt-get update
RUN apt-get install -y curl
RUN apt-get install -y git
RUN apt-get install -y g++
RUN apt-get install -y libicu-dev
RUN apt-get install -y autoconf
RUN apt-get install -y make
RUN apt-get install -y openssl
RUN apt-get install -y libssl-dev
RUN apt-get install -y libcurl4-openssl-dev
RUN apt-get install -y pkg-config
RUN apt-get install -y libsasl2-dev

RUN docker-php-ext-install -j$(nproc) intl
RUN docker-php-ext-configure intl
RUN docker-php-ext-install -j$(nproc) opcache
RUN docker-php-ext-configure opcache
RUN docker-php-ext-install -j$(nproc) pdo
RUN docker-php-ext-configure pdo
RUN docker-php-ext-install -j$(nproc) pdo_mysql
RUN docker-php-ext-configure pdo_mysql
RUN pecl install mongodb
RUN echo "extension=mongodb.so" >> /usr/local/etc/php/conf.d/mongodb.ini

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app