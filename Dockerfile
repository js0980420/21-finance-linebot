FROM php:8.2-fpm-alpine

LABEL "language"="php"
LABEL "framework"="laravel"

WORKDIR /app

RUN apk add --no-cache nginx mysql-client postgresql-client git unzip icu-dev libzip-dev oniguruma-dev freetype-dev libjpeg-turbo-dev libpng-dev

RUN docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd opcache
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

COPY . /app

RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache || true
RUN chmod -R 775 /app/storage /app/bootstrap/cache || true

RUN composer config -g repo.packagist composer https://repo.packagist.org

RUN mkdir -p /app/database && touch /app/database/database.sqlite

RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction; fi

COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/conf.d/default.conf
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD wget --quiet --tries=1 --timeout=5 http://localhost:8080/health || exit 1

CMD ["/entrypoint.sh"]
