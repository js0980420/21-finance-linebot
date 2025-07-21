# 使用官方 PHP 8.2 FPM Alpine 映像
FROM php:8.2-fpm-alpine

# 設定語言標籤讓 Zeabur 自動辨識為 PHP
LABEL "language"="php"
LABEL "framework"="laravel"

# 設定工作目錄
WORKDIR /app

# 安裝系統依賴和 PHP 擴展 (完全重寫避免快取問題)
RUN apk add --no-cache \
    nginx \
    mysql-client \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    postgresql-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl bcmath gd opcache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && rm -rf /var/cache/apk/*

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 複製專案檔案
COPY . /app

# 進入 Laravel 目錄並安裝依賴 (如果存在)
WORKDIR /app/laravel-complete
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

# 回到根目錄
WORKDIR /app

# 設定 Laravel 文件權限 (如果目錄存在)
RUN if [ -d "/app/laravel-complete/storage" ]; then \
        chown -R www-data:www-data /app/laravel-complete/storage /app/laravel-complete/bootstrap/cache; \
        chmod -R 775 /app/laravel-complete/storage /app/laravel-complete/bootstrap/cache; \
    fi

# 配置 Nginx - 支援混合架構 (根目錄 PHP + Laravel 子目錄)
RUN cat > /etc/nginx/conf.d/default.conf << 'EOF'
server {
    listen 8080;
    server_name localhost;
    root /app;
    index index.php index.html;
    charset utf-8;

    # 根目錄 PHP 文件處理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Laravel 子目錄支援
    location /laravel-complete/ {
        alias /app/laravel-complete/public/;
        try_files $uri $uri/ @laravel;
    }

    location @laravel {
        rewrite ^/laravel-complete/(.*)$ /laravel-complete/public/index.php?/$1 last;
    }

    # PHP 文件處理
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # 靜態文件緩存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 健康檢查
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    # 隱藏敏感文件
    location ~ /\. {
        deny all;
    }
}
EOF

# 暴露端口
EXPOSE 8080

# 健康檢查
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD wget --quiet --tries=1 --timeout=5 http://localhost:8080/health || exit 1

# 啟動 Nginx 和 PHP-FPM
CMD sh -c "php-fpm && nginx -g 'daemon off;'"