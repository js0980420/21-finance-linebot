FROM php:8.2-fpm-alpine

LABEL "language"="php"
LABEL "framework"="laravel"

WORKDIR /app

# 安裝相依套件與 nginx
RUN apk add --no-cache nginx mysql-client git unzip icu-dev libzip-dev oniguruma-dev freetype-dev libjpeg-turbo-dev libpng-dev postgresql-dev
RUN docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl bcmath gd opcache
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# 安裝 composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 複製程式碼
COPY . /app

# Composer install
RUN if [ -f composer.json ]; then composer install --no-dev --optimize-autoloader --no-interaction; fi

# Laravel setup
RUN if [ -f .env ]; then \
        php artisan key:generate --force && \
        php artisan migrate --force && \
        php artisan config:cache && \
        php artisan route:cache; \
    fi

# 資料夾權限 - 設定所有重要目錄
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public || true
RUN chmod -R 777 /app/storage /app/bootstrap/cache /app/public || true

# 建立 Nginx 設定
RUN mkdir -p /etc/nginx/conf.d/
COPY nginx.conf /etc/nginx/nginx.conf
COPY default.conf /etc/nginx/conf.d/default.conf

# 建立 PHP-FPM 設定
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# 複製啟動腳本
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD wget --quiet --tries=1 --timeout=5 http://localhost:8080/health || exit 1

CMD ["/start.sh"]
