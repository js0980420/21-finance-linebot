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
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
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

# 配置 Nginx - 支援根目錄 PHP 和 Laravel 子目錄
RUN echo 'server {' > /etc/nginx/conf.d/default.conf && \
    echo '    listen 8080;' >> /etc/nginx/conf.d/default.conf && \
    echo '    server_name localhost;' >> /etc/nginx/conf.d/default.conf && \
    echo '    root /app;' >> /etc/nginx/conf.d/default.conf && \
    echo '    index index.php index.html;' >> /etc/nginx/conf.d/default.conf && \
    echo '    charset utf-8;' >> /etc/nginx/conf.d/default.conf && \
    echo '' >> /etc/nginx/conf.d/default.conf && \
    echo '    # 根目錄 PHP 文件處理' >> /etc/nginx/conf.d/default.conf && \
    echo '    location / {' >> /etc/nginx/conf.d/default.conf && \
    echo '        try_files $uri $uri/ /index.php?$query_string;' >> /etc/nginx/conf.d/default.conf && \
    echo '    }' >> /etc/nginx/conf.d/default.conf && \
    echo '' >> /etc/nginx/conf.d/default.conf && \
    echo '    # Laravel 子目錄支援' >> /etc/nginx/conf.d/default.conf && \
    echo '    location /laravel-complete/ {' >> /etc/nginx/conf.d/default.conf && \
    echo '        alias /app/laravel-complete/public/;' >> /etc/nginx/conf.d/default.conf && \
    echo '        try_files $uri $uri/ @laravel;' >> /etc/nginx/conf.d/default.conf && \
    echo '    }' >> /etc/nginx/conf.d/default.conf && \
    echo '' >> /etc/nginx/conf.d/default.conf && \
    echo '    location @laravel {' >> /etc/nginx/conf.d/default.conf && \
    echo '        rewrite ^/laravel-complete/(.*)$ /laravel-complete/public/index.php?/$1 last;' >> /etc/nginx/conf.d/default.conf && \
    echo '    }' >> /etc/nginx/conf.d/default.conf && \
    echo '' >> /etc/nginx/conf.d/default.conf && \
    echo '    # PHP 文件處理' >> /etc/nginx/conf.d/default.conf && \
    echo '    location ~ \.php$ {' >> /etc/nginx/conf.d/default.conf && \
    echo '        fastcgi_pass 127.0.0.1:9000;' >> /etc/nginx/conf.d/default.conf && \
    echo '        fastcgi_index index.php;' >> /etc/nginx/conf.d/default.conf && \
    echo '        fastcgi_buffers 16 16k;' >> /etc/nginx/conf.d/default.conf && \
    echo '        fastcgi_buffer_size 32k;' >> /etc/nginx/conf.d/default.conf && \
    echo '        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;' >> /etc/nginx/conf.d/default.conf && \
    echo '        include fastcgi_params;' >> /etc/nginx/conf.d/default.conf && \
    echo '        fastcgi_read_timeout 300;' >> /etc/nginx/conf.d/default.conf && \
    echo '    }' >> /etc/nginx/conf.d/default.conf && \
    echo '' >> /etc/nginx/conf.d/default.conf && \
    echo '    # 靜態文件緩存' >> /etc/nginx/conf.d/default.conf && \
    echo '    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {' >> /etc/nginx/conf.d/default.conf && \
    echo '        expires 1y;' >> /etc/nginx/conf.d/default.conf && \
    echo '        add_header Cache-Control "public, immutable";' >> /etc/nginx/conf.d/default.conf && \
    echo '    }' >> /etc/nginx/conf.d/default.conf && \
    echo '' >> /etc/nginx/conf.d/default.conf && \
    echo '    # 健康檢查' >> /etc/nginx/conf.d/default.conf && \
    echo '    location /health {' >> /etc/nginx/conf.d/default.conf && \
    echo '        access_log off;' >> /etc/nginx/conf.d/default.conf && \
    echo '        return 200 "healthy\n";' >> /etc/nginx/conf.d/default.conf && \
    echo '        add_header Content-Type text/plain;' >> /etc/nginx/conf.d/default.conf && \
    echo '    }' >> /etc/nginx/conf.d/default.conf && \
    echo '' >> /etc/nginx/conf.d/default.conf && \
    echo '    # 隱藏敏感文件' >> /etc/nginx/conf.d/default.conf && \
    echo '    location ~ /\. {' >> /etc/nginx/conf.d/default.conf && \
    echo '        deny all;' >> /etc/nginx/conf.d/default.conf && \
    echo '    }' >> /etc/nginx/conf.d/default.conf && \
    echo '}' >> /etc/nginx/conf.d/default.conf

# 暴露端口
EXPOSE 8080

# 健康檢查
HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD wget --quiet --tries=1 --timeout=5 http://localhost:8080/health || exit 1

# 啟動 Nginx 和 PHP-FPM
CMD sh -c "php-fpm && nginx -g 'daemon off;'"