# Zeabur 部署指南

## 🚨 解決資料庫連線問題

您遇到的錯誤：`Error: connect ECONNREFUSED ::1:3306` 表示無法連接到 MySQL 資料庫。

### 問題原因
1. **MySQL 服務未啟動**：Zeabur 上的 MySQL 服務還沒有部署或啟動
2. **環境變數錯誤**：資料庫連線參數設置不正確
3. **網路配置問題**：應用程式無法訪問資料庫服務

## 🔧 解決步驟

### 步驟 1: 部署 MySQL 資料庫

1. **在 Zeabur 控制台**
   - 點擊「Add Service」
   - 選擇「Database」→「MySQL 8.0」
   - 等待部署完成（通常需要 2-3 分鐘）

2. **獲取資料庫連線資訊**
   ```
   Database Host: {your-mysql-service-name}
   Database Port: 3306
   Database Name: {auto-generated-or-custom}
   Username: root
   Password: {auto-generated}
   ```

### 步驟 2: 設置環境變數

在 Zeabur 專案的環境變數中設置：

```env
# 資料庫連線
DB_HOST={your-mysql-service-name}
DB_PORT=3306
DB_USER=root
DB_PASSWORD={your-mysql-password}
DB_NAME={your-database-name}
DB_SSL=false

# Line Bot
LINE_CHANNEL_ACCESS_TOKEN={your-line-token}
LINE_CHANNEL_SECRET={your-line-secret}

# OpenAI
OPENAI_API_KEY={your-openai-key}

# 其他
NODE_ENV=production
```

### 步驟 3: 重新部署應用程式

1. 確保環境變數已保存
2. 觸發重新部署（推送新的 commit 或手動重啟）
3. 查看部署日誌確認連線狀況

## 📋 正確的部署順序

### 1. 先部署資料庫服務
```bash
# 在 Zeabur 控制台
1. 新增 MySQL 8.0 服務
2. 等待部署完成
3. 記錄連線資訊
```

### 2. 設置環境變數
```bash
# 重要：確保使用正確的服務名稱作為 DB_HOST
DB_HOST=mysql  # 或您的 MySQL 服務名稱
```

### 3. 部署應用程式
```bash
# 推送程式碼或觸發重新部署
git push origin main
```

## 🔍 常見問題排除

### Q1: 仍然無法連接資料庫？
**檢查項目：**
- ✅ MySQL 服務狀態是否為「Running」
- ✅ 環境變數是否正確設置
- ✅ DB_HOST 是否使用正確的服務名稱（而非 localhost）

### Q2: 顯示「演示模式」而非資料庫模式？
**原因：** 缺少必要的環境變數
```bash
# 確保這三個環境變數都已設置
DB_HOST=mysql
DB_USER=root
DB_PASSWORD=your_password
```

### Q3: 連線超時或拒絕連線？
**可能原因：**
1. MySQL 服務尚未完全啟動（等待 2-3 分鐘）
2. 網路配置問題（檢查 Zeabur 服務間通訊）
3. 防火牆設置（通常 Zeabur 會自動處理）

## 🚀 推薦部署流程

### 方案 A: 完整資料庫模式（生產環境）
```
1. 部署 MySQL 8.0 → 2. 設置環境變數 → 3. 部署應用程式
```

### 方案 B: 演示模式（測試環境）
```
1. 不設置資料庫環境變數 → 2. 直接部署應用程式
```

## 📊 部署狀態檢查

### 檢查資料庫連線狀態
訪問：`https://your-app-url.zeabur.app/health`

**成功回應：**
```json
{
  "status": "ok",
  "service": "貸款案件管理系統",
  "database": "connected",
  "mode": "production"
}
```

**演示模式回應：**
```json
{
  "status": "ok",
  "service": "貸款案件管理系統",
  "database": "demo",
  "mode": "demo"
}
```

## 🔐 安全性建議

### 生產環境環境變數
```env
# 使用強密碼
DB_PASSWORD=Strong_Password_123!

# 啟用 SSL（如果 Zeabur 支援）
DB_SSL=true

# 設置正確的環境
NODE_ENV=production
```

### 開發環境
```env
# 使用演示模式
# 不設置 DB_* 環境變數即可自動切換到演示模式
```

## 🛠️ 故障排除指令

### 在 Zeabur 日誌中查看的關鍵信息
```
✅ 正常啟動：
🔗 嘗試連接資料庫...
📍 資料庫主機: mysql:3306
✅ 資料庫連線成功
🏗️ 開始建立資料表...
✅ 資料表建立完成

❌ 連線失敗：
🔗 嘗試連接資料庫...
📍 資料庫主機: mysql:3306
❌ 資料庫初始化失敗: connect ECONNREFUSED
🔄 切換到演示模式...
```

## 📞 技術支援

如果按照上述步驟仍無法解決問題：

1. **檢查 Zeabur 服務狀態**：確保 MySQL 服務正常運行
2. **查看完整日誌**：檢查是否有其他錯誤訊息
3. **重新創建服務**：刪除並重新部署 MySQL 服務
4. **聯絡 Zeabur 支援**：如果是平台問題

---

## ✅ 成功部署檢查清單

- [ ] MySQL 8.0 服務已部署並運行
- [ ] 環境變數已正確設置（DB_HOST, DB_USER, DB_PASSWORD）
- [ ] 應用程式顯示「資料庫連線成功」
- [ ] `/health` 端點回應正常
- [ ] Line Bot 功能可正常使用
- [ ] 網頁版管理系統可訪問

**🎉 部署成功後，您的 Line Bot + CRM 系統就可以在 Zeabur 上穩定運行了！** 