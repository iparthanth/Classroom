FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs && \
    a2enmod rewrite

EXPOSE 80