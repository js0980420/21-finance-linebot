FROM php:8.2-fpm-alpine

LABEL "language"="php"
LABEL "framework"="laravel"

WORKDIR /app

# Install system dependencies
RUN apk update && apk add --no-cache \
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
    wget \
    curl \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl bcmath gd opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy all application files
COPY . /app/

# Create entrypoint script with fixed paths
RUN cat > /app/entrypoint.sh << 'EOF'
#!/bin/sh
set -e

# Change to app directory first
cd /app

# Check if we have Laravel files
if [ ! -f "/app/artisan" ]; then
  echo "Error: artisan file not found in /app"
  ls -la /app/
  exit 1
fi

# Database setup
if [ "$DB_CONNECTION" = "sqlite" ]; then
  if [ ! -f /app/database/database.sqlite ]; then
    mkdir -p /app/database
    touch /app/database/database.sqlite
  fi
fi

# Set permissions
chown -R www-data:www-data /app/storage /app/bootstrap/cache || true
chmod -R 775 /app/storage /app/bootstrap/cache || true

# Laravel artisan commands (with proper working directory)
if [ -f /app/.env ]; then
  cd /app && php artisan key:generate --force
fi

cd /app && php artisan config:cache || true
cd /app && php artisan route:cache || true
cd /app && php artisan view:cache || true

# Start services
php-fpm &
nginx -g "daemon off;"
EOF

# Set proper permissions
RUN chmod +x /app/entrypoint.sh && \
    chown -R www-data:www-data /app

# Create necessary directories
RUN mkdir -p /app/storage/logs /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views \
    /app/bootstrap/cache /app/database && \
    touch /app/database/database.sqlite

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts || true

# Copy nginx configuration
RUN if [ -f /app/nginx.conf ]; then cp /app/nginx.conf /etc/nginx/conf.d/default.conf; fi

# Final permission fix
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

EXPOSE 8080

CMD ["/app/entrypoint.sh"]
