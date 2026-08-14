FROM php:8.2-cli

RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/logs && \
    chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs

WORKDIR /var/www/html

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html"]