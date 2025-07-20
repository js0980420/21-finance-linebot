# 使用官方 PHP 8.2 FPM Alpine 映像
FROM php:8.2-fpm-alpine

# 設定語言標籤讓 Zeabur 自動辨識為 PHP
LABEL "language"="php"
LABEL "framework"="laravel"

# 設定工作目錄
WORKDIR /app

# 安裝系統依賴和 PHP 擴展
RUN apk add --no-cache \
    nginx \
    mysql-client \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    onig-dev \
    gd \
    postgresql-dev && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl bcmath gd opcache && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    rm -rf /var/cache/apk/*

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 複製 Laravel 專案檔案
COPY . /app

# 安裝 Composer 依賴
RUN composer install --no-dev --optimize-autoloader --no-interaction && \
    php artisan optimize

# 設定 Nginx
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache && \
    chmod -R 775 /app/storage /app/bootstrap/cache

# 暴露端口
EXPOSE 8080

# 健康檢查 (檢查 PHP-FPM 和 Nginx 服務)
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD wget --quiet --tries=1 --timeout=5 http://localhost:8080/health || exit 1

# 啟動 Nginx 和 PHP-FPM
CMD sh -c "php-fpm && nginx -g 'daemon off;'" 