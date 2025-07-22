#!/bin/sh

# 啟動 PHP-FPM
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
echo "Starting nginx..."
nginx -g 'daemon off;'