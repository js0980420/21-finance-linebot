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

# 複製專案檔案
COPY . /app

# 進入 Laravel 目錄並安裝依賴
WORKDIR /app/laravel-complete
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# 回到根目錄
WORKDIR /app

# 設定 Laravel 文件權限
RUN if [ -d "/app/laravel-complete/storage" ]; then \
        chown -R www-data:www-data /app/laravel-complete/storage /app/laravel-complete/bootstrap/cache; \
        chmod -R 775 /app/laravel-complete/storage /app/laravel-complete/bootstrap/cache; \
    fi

# 配置 Nginx
RUN echo 'server { \n\
    listen 8080; \n\
    server_name localhost; \n\
    root /app; \n\
    index index.php index.html; \n\
    location / { \n\
        try_files $uri $uri/ /index.php?$query_string; \n\
    } \n\
    location ~ \.php$ { \n\
        fastcgi_pass 127.0.0.1:9000; \n\
        fastcgi_index index.php; \n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \n\
        include fastcgi_params; \n\
        fastcgi_read_timeout 300; \n\
    } \n\
    location /health { \n\
        access_log off; \n\
        return 200 "healthy\n"; \n\
        add_header Content-Type text/plain; \n\
    } \n\
}' > /etc/nginx/conf.d/default.conf

# 暴露端口
EXPOSE 8080

# 健康檢查 (檢查 PHP-FPM 和 Nginx 服務)
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD wget --quiet --tries=1 --timeout=5 http://localhost:8080/health || exit 1

# 啟動 Nginx 和 PHP-FPM
CMD sh -c "php-fpm && nginx -g 'daemon off;'" 