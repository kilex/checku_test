FROM php:7.1.11-fpm

RUN apt-get update
RUN apt-get install -y autoconf pkg-config libssl-dev
RUN pecl install mongodb-1.5.0
RUN docker-php-ext-install bcmath
RUN echo "extension=mongodb.so" >> /usr/local/etc/php/conf.d/mongodb.ini

# Install Laravel dependencies
RUN apt-get install -y libmcrypt-dev

RUN docker-php-ext-install iconv mcrypt mbstring \
    && docker-php-ext-install zip

# redis

RUN curl -sS https://getcomposer.org/download/1.7.2/composer.phar > composer.phar && chmod +x composer.phar

COPY composer.json /var/www/html/composer.json
COPY composer.lock /var/www/html/composer.lock
# COPY composer.phar /var/www/html/composer.phar

WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN php composer.phar install --prefer-dist --no-autoloader --no-scripts
COPY . /var/www/html

RUN php composer.phar dump-autoload