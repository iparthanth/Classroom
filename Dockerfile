FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql && \
    a2dismod mpm_event && \
    a2enmod mpm_prefork rewrite

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs

EXPOSE 80