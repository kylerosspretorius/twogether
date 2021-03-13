FROM php:7.4-fpm-alpine

WORKDIR /app

COPY src /app/src
COPY public /app/public
COPY vendor /app/vendor
COPY init.php /app
