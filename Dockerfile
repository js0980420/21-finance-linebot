FROM php:8.2-fpm-alpine

LABEL "language"="php"
LABEL "framework"="laravel"

WORKDIR /app

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
    postgresql-dev \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring zip exif pcntl bcmath gd opcache \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && rm -rf /var/cache/apk/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . /app

# Create SQLite database file before composer install
RUN touch /app/database/database.sqlite

RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi

WORKDIR /app

RUN if [ -d "/app/storage" ]; then \
        chown -R www-data:www-data /app/storage /app/bootstrap/cache; \
        chmod -R 775 /app/storage /app/bootstrap/cache; \
    fi

RUN mkdir -p /etc/nginx/conf.d/

RUN cat > /etc/nginx/conf.d/default.conf << 'EOF'
server {
    listen 8080;
    server_name localhost;
    root /app/public;
    index index.php index.html;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    location ~ /\. {
        deny all;
    }
}
EOF

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD wget --quiet --tries=1 --timeout=5 http://localhost:8080/health || exit 1

CMD sh -c "php-fpm && nginx -g 'daemon off;'"
