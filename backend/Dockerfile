FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk update && apk add --no-cache \
    supervisor \
    mysql-client \
    zip \
    unzip \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    && rm -rf /var/cache/apk/* # 清理 apk 快取

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Explicitly enable pdo_mysql
RUN docker-php-ext-enable pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code first
COPY . .

# 建立 Laravel 必要可寫目錄並設定權限 (在 COPY . . 之後執行，確保對所有複製的檔案生效)
RUN set -eux; \
    mkdir -p bootstrap/cache \
             storage/framework/{cache,data,sessions,views} \
             storage/logs \
             storage/framework/views; \
    chown -R www-data:www-data . ; \
    chmod -R 775 bootstrap storage; \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install PHP dependencies
# Handle potential lock file sync issues and platform requirements
# Remove outdated lock file and install dependencies with platform requirement ignoring
# 這裡的 composer install 應該在 chown 之後，確保生成的 vendor 檔案權限正確
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-grpc

# --- 新增以下行來建立 www-data 使用者和群組 ---
# 確保 www-data group 和 user 存在 (Alpine Linux)
RUN addgroup -g 82 www-data && \
    adduser -u 82 -D -S -G www-data www-data
# --- 結束新增 ---

# 清除任何現有的 Laravel 快取，並確保以 www-data 使用者執行
# 注意：這在建置時運行，所以來自 docker-compose 的環境變數將不可用
# 如果需要，我們需要在運行時清除快取
USER www-data # 切換到 www-data 使用者
RUN php artisan config:clear || true
RUN php artisan route:clear || true
RUN php artisan view:clear || true
USER root # 切換回 root 以執行後續的系統級操作 (例如 COPY Nginx config)

# Copy Nginx configuration and startup script
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/startup.sh /startup.sh

# Make startup script executable
RUN chmod +x /startup.sh

# Create Nginx, supervisor, and Nginx log directories
RUN mkdir -p /var/log/supervisor /run/nginx /var/log/nginx

EXPOSE 8000

CMD ["/startup.sh"]
