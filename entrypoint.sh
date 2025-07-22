```sh
#!/bin/sh
set -e

if [ "$DB_CONNECTION" = "sqlite" ]; then
  if [ ! -f /app/database/database.sqlite ]; then
    mkdir -p /app/database
    touch /app/database/database.sqlite
  fi
fi

chown -R www-data:www-data /app/storage /app/bootstrap/cache || true
chmod -R 775 /app/storage /app/bootstrap/cache || true

if [ -f /app/.env ]; then
  php artisan key:generate --force
fi

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

php-fpm &
nginx -g "daemon off;"
```
