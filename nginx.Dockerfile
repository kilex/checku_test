FROM nginx:latest

RUN rm /etc/nginx/conf.d/*

COPY ./docker/php.conf /etc/nginx/conf.d/php.conf
COPY ./docker/fastcgi-php.conf /etc/nginx/fastcgi-php.conf

WORKDIR /var/www/html/public
COPY ./public /var/www/html/public