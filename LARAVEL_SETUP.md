# Laravel 版本設定指南

## 系統要求

- PHP 8.2+
- Composer
- MySQL 8.0+
- LINE Bot SDK

## 安裝步驟

### 1. 複製環境變數檔案
```bash
cp .env.laravel .env
```

### 2. 編輯 .env 檔案
```env
# 資料庫設定
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=finance_crm
DB_USERNAME=root
DB_PASSWORD=123456

# LINE Bot 設定
LINE_CHANNEL_ACCESS_TOKEN=你的LINE_ACCESS_TOKEN
LINE_CHANNEL_SECRET=你的LINE_SECRET

# 其他設定
APP_URL=http://localhost
TIMEZONE=Asia/Taipei
```

### 3. 進入 Laravel 目錄並安裝依賴
```bash
cd laravel-complete
composer install
```

### 4. 生成應用程式金鑰
```bash
php artisan key:generate
```

### 5. 建立資料庫
在 MySQL Workbench 中執行：
```sql
CREATE DATABASE finance_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. 運行資料庫遷移
```bash
php artisan migrate
```

### 7. 創建業務員帳號（可選）
```sql
INSERT INTO sales_line_accounts (
    sales_code, sales_name, line_user_id, 
    is_manager, responsible_regions, is_active
) VALUES (
    'S001', '張業務', '你的LINE_USER_ID',
    false, '["台北市", "新北市"]', true
);
```

### 8. 啟動開發伺服器
```bash
php artisan serve
```

伺服器將在 http://localhost:8000 啟動

## 功能頁面

- **首頁**: http://localhost:8000
- **管理後台**: http://localhost:8000/admin
- **房貸表單**: http://localhost:8000/mortgage-form
- **健康檢查**: http://localhost:8000/health
- **統計 API**: http://localhost:8000/api/stats

## API 端點

### 表單提交
- `POST /api/mortgage-form` - 手動表單提交
- `POST /api/mortgage-form-webhook` - Webhook 表單提交

### LINE Bot
- `POST /webhook` - LINE Bot Webhook

### 統計資料
- `GET /api/stats` - 獲取表單統計

## LINE Bot 設定

1. 在 LINE Developers Console 設定 Webhook URL：
   ```
   https://your-domain.com/webhook
   ```

2. 設定 Channel Access Token 和 Channel Secret

3. 啟用 Messaging API

## 部署注意事項

### 生產環境設定
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Nginx 設定
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/laravel-complete/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Apache 設定
確保啟用 mod_rewrite 模組，Laravel 已包含 .htaccess 檔案。

## 疑難排解

### 權限問題
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 快取清除
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 資料庫連線問題
1. 確認 MySQL 服務正在運行
2. 檢查資料庫連線設定
3. 確認資料庫用戶權限

## 功能說明

### 表單提交流程
1. 客戶填寫房貸表單
2. 系統驗證並儲存資料
3. 自動分配給業務員
4. 發送 LINE 通知給業務員

### LINE Bot 功能
- **業務員查詢**: 客戶列表、今日客戶、統計報表
- **客戶互動**: 自動回覆、服務介紹
- **搜尋功能**: 按姓名、電話、LINE ID 搜尋

### 管理後台功能
- 客戶資料管理
- 統計報表查看
- 篩選和搜尋
- 資料匯出（待實作）

## 開發建議

1. 使用 Laravel 的 Eloquent ORM 進行資料庫操作
2. 遵循 Laravel 的 MVC 架構
3. 使用 Blade 模板引擎
4. 實作適當的錯誤處理和日誌記錄
5. 添加單元測試和功能測試