FROM composer:2 AS dependencies
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.3-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY docker/ports.conf /etc/apache2/ports.conf
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/render-entrypoint
COPY --from=dependencies /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

RUN chmod +x /usr/local/bin/render-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache public/uploads/products \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads

EXPOSE 10000
ENTRYPOINT ["/usr/local/bin/render-entrypoint"]
CMD ["apache2-foreground"]
