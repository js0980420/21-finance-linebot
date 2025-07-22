#!/bin/sh
set -e

echo "Starting Laravel application..."

# 建立 SQLite 檔案（如果設定用 SQLite）
if [ "$DB_CONNECTION" = "sqlite" ] || [ ! -n "$DB_CONNECTION" ]; then
  echo "Setting up SQLite database..."
  mkdir -p /app/database
  if [ ! -f /app/database/database.sqlite ]; then
    touch /app/database/database.sqlite
    echo "Created database.sqlite file"
  fi
  chmod 664 /app/database/database.sqlite
  chown www-data:www-data /app/database/database.sqlite
fi

# 修正資料夾權限
echo "Setting up permissions..."
chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public /app/database || true
chmod -R 775 /app/storage /app/bootstrap/cache /app/public /app/database || true

# 安全匯入 .env
if [ ! -f /app/.env ]; then
  if [ -f /app/.env.example ]; then
    echo "Copying .env.example to .env"
    cp /app/.env.example /app/.env
  fi
fi

# 如果尚未生成 Laravel key，則產生
if [ -z "$APP_KEY" ]; then
  echo "Generating Laravel application key..."
  php artisan key:generate --force
fi

# 執行 migrate（生產環境可註解此行）
echo "Running database migrations..."
php artisan migrate --force || echo "Migration failed, continuing..."

# 快取最佳化（視需求調整）
echo "Optimizing Laravel caches..."
php artisan config:cache || echo "Config cache failed, continuing..."
php artisan route:cache || echo "Route cache failed, continuing..."
php artisan view:cache || echo "View cache failed, continuing..."

echo "Starting PHP-FPM..."
# 啟動 PHP-FPM 在背景
php-fpm -D

# 等待 PHP-FPM 啟動
sleep 2

# 檢查 PHP-FPM 是否正在運行
if ! pgrep -x "php-fpm" > /dev/null; then
    echo "PHP-FPM failed to start"
    exit 1
fi

echo "PHP-FPM started successfully"

# 啟動 nginx (前台運行)
echo "Starting Nginx..."
nginx -g 'daemon off;'