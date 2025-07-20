# 🚀 快速開始指南 - Herd 版本

## ⚡ 5分鐘快速設置

### 1. 確認 Herd 環境
```bash
# 確認 Herd 正在運行
# 確認 MySQL 服務啟動
# 確認 PHP 可用
php --version
```

### 2. 建立資料庫
有兩種方式建立資料庫：

#### 方式 A: 使用 phpMyAdmin (推薦)
1. 開啟 Herd
2. 點擊 "Open phpMyAdmin"
3. 建立新資料庫 `form_receiver`
4. 匯入 `create_database.sql` 檔案

#### 方式 B: 使用命令列
```bash
# 進入專案目錄
cd "C:\Users\js098\Project\Claude Code\21-finance-linebot"

# 執行 SQL 腳本
mysql -u root -p < create_database.sql
```

### 3. 設置 Herd 站點
```bash
# 在專案目錄執行
herd link form-receiver

# 或設定自訂域名
herd link form-receiver.test
```

### 4. 測試安裝
開啟瀏覽器訪問：
- **http://form-receiver.test/laravel-simple.php** (主要 API)
- **http://form-receiver.test/test-page.php** (測試頁面)

## 📡 API 測試

### 健康檢查
```bash
curl http://form-receiver.test/laravel-simple.php/health
```

### 表單提交測試
```bash
curl -X POST http://form-receiver.test/laravel-simple.php/api/test-form \
  -H "Content-Type: application/json" \
  -d '{
    "name": "測試客戶",
    "phone": "0912345678",
    "email": "test@example.com",
    "area": "台北市",
    "message": "測試訊息"
  }'
```

### 查看提交記錄
```bash
curl http://form-receiver.test/laravel-simple.php/api/submissions
```

## 🌐 網站來源設定

### 測試網站設定
對於測試，來源會自動設為 `test`：
```javascript
// 前端 JavaScript
fetch('/laravel-simple.php/api/test-form', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        name: '客戶姓名',
        phone: '0912345678',
        area: '台北市'
    })
});
```

### 其他網站整合
#### 易付生活網站
```html
<!-- 在 easypay-life.com.tw 的表單中 -->
<form action="http://form-receiver.test/laravel-simple.php/api/easypay-contact" method="POST">
    <input type="text" name="name" placeholder="姓名">
    <input type="tel" name="phone" placeholder="電話">
    <input type="email" name="email" placeholder="Email">
    <input type="text" name="lineId" placeholder="LINE ID">
    <select name="area">
        <option value="台北市">台北市</option>
        <option value="新北市">新北市</option>
    </select>
    <textarea name="message" placeholder="訊息"></textarea>
    <button type="submit">提交</button>
</form>
```

#### AJAX 提交
```javascript
// 任何網站都可以用 AJAX 提交
function submitForm(formData) {
    fetch('http://form-receiver.test/laravel-simple.php/api/form', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('提交成功！');
        }
    });
}
```

## 🔧 來源識別規則

系統會自動識別表單來源：

1. **手動指定** (最高優先)
   ```json
   { "source": "test" }
   ```

2. **域名自動識別**
   - `easypay-life.com.tw` → `easypay_life`
   - `mortgage-master.com.tw` → `mortgage_master`
   - `localhost` → `localhost_test`

3. **Referer 解析**
   - 自動從 HTTP Referer 標頭提取域名

## 📊 管理界面功能

訪問 http://form-receiver.test/test-page.php 可以：

- ✅ 測試表單提交
- ✅ 即時查看提交記錄
- ✅ 篩選不同來源
- ✅ 查看統計資料
- ✅ 清除測試資料

## 🔗 LINE Bot 整合

### 查看客戶資料
LINE Bot 可以透過 API 查詢：
```javascript
// 查詢今日客戶
fetch('/laravel-simple.php/api/submissions?source=easypay_life&limit=10')
.then(response => response.json())
.then(data => {
    // 格式化為 LINE 訊息
    const message = formatCustomerList(data.submissions);
    // 發送 LINE 訊息
});
```

### 業務員通知
當有新表單提交時，可以自動通知相關業務員：
```php
// 在 laravel-simple.php 中添加通知邏輯
function notifySalesTeam($submissionData) {
    // 根據地區找到負責業務員
    // 發送 LINE 推播訊息
}
```

## 🗄️ 資料庫結構

主要表格：
- `form_submissions` - 表單提交記錄
- `sales_line_accounts` - 業務員 LINE 帳號對應
- `form_sync_logs` - 同步日誌
- `line_query_logs` - LINE 查詢記錄

## 🚨 故障排除

### 常見問題

1. **資料庫連線失敗**
   - 檢查 Herd MySQL 是否啟動
   - 確認資料庫名稱為 `form_receiver`
   - 檢查 `laravel-simple.php` 中的連線設定

2. **API 回傳錯誤**
   - 檢查 PHP 錯誤日誌
   - 確認 Herd 站點設定正確
   - 測試基本的 `/health` 端點

3. **表單提交失敗**
   - 檢查 Content-Type 標頭
   - 確認必填欄位 (姓名、電話或Email)
   - 查看瀏覽器開發者工具

4. **來源識別不正確**
   - 檢查 HTTP Referer 標頭
   - 手動指定 source 參數測試
   - 查看 API 回應中的 source 欄位

### 除錯方法
```php
// 在 laravel-simple.php 中啟用除錯
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 查看錯誤日誌
tail -f /path/to/php/error.log
```

## 📈 效能建議

1. **資料庫最佳化**
   - 已建立必要索引
   - 定期清理舊資料

2. **快取策略**
   - 統計資料可考慮快取
   - 使用 Redis 或檔案快取

3. **安全性**
   - 生產環境使用 HTTPS
   - 設定適當的 CORS 政策
   - 加強輸入驗證

## 📞 技術支援

如果遇到問題：
1. 檢查 `QUICK_START.md` 故障排除章節
2. 查看 API 回應和錯誤訊息
3. 測試基本的健康檢查端點
4. 確認 Herd 環境設定正確

## 🎯 下一步

設置完成後可以：
1. 整合更多網站來源
2. 設定自動化業務員分配
3. 建立 LINE Bot 推播通知
4. 擴展 CRM 系統整合
5. 設定監控和警報系統