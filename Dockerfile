FROM php:8.2-fpm-alpine

LABEL "language"="php"
LABEL "framework"="laravel"

WORKDIR /app

RUN apk add --no-cache nginx mysql-client git unzip icu-dev libzip-dev oniguruma-dev freetype-dev libjpeg-turbo-dev libpng-dev postgresql-dev
RUN docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl bcmath gd opcache
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /app

RUN mkdir -p /app/database && touch /app/database/database.sqlite

RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction; fi

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache || true
RUN chmod -R 775 /app/storage /app/bootstrap/cache || true

RUN mkdir -p /etc/nginx/conf.d/
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/conf.d/default.conf

COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD wget --quiet --tries=1 --timeout=5 http://localhost:8080/health || exit 1

CMD ["/entrypoint.sh"]
