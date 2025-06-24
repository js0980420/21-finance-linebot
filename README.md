# 中租經銷商 Line Bot CRM 系統

## 功能特色

### 核心功能
- 🤖 **Line Bot 整合**: 客戶可透過 Line@ 進行所有操作
- 🧠 **AI 智能客服**: 整合 OpenAI GPT 提供智能回應
- 👥 **客戶管理**: 自動記錄客戶資訊及來源追蹤
- 📊 **業務分配**: 自動分配客戶給業務人員
- 📅 **定期提醒**: 每週自動提醒業務追蹤客戶
- 🔒 **資訊安全**: 採用加密通訊及權限控制
- 🌐 **來源追蹤**: 支援 UTM 參數追蹤客戶來源

### 貸款服務
- 🚗 汽車貸款
- 🏍️ 機車貸款  
- 📱 手機貸款

## 技術架構

- **後端**: Node.js + Express
- **資料庫**: MySQL 8.0
- **Line SDK**: @line/bot-sdk
- **AI 服務**: OpenAI GPT-3.5-turbo
- **部署平台**: Zeabur
- **容器化**: Docker

## 快速部署

### 1. 環境設定

**使用 Zeabur 環境變數**（不需要 .env 檔案）：
```env
LINE_CHANNEL_ACCESS_TOKEN=your_line_channel_access_token
LINE_CHANNEL_SECRET=your_line_channel_secret
OPENAI_API_KEY=your_openai_api_key
DB_HOST=your_db_host
DB_USER=your_db_user
DB_PASSWORD=your_db_password
DB_NAME=finance_crm
FRONTEND_URL=your_frontend_url
JWT_SECRET=your_jwt_secret
```

### 2. 本地開發

安裝依賴：
```bash
npm install
```

啟動開發模式：
```bash
npm run dev
```

### 3. Zeabur 部署

1. 推送程式碼至 Git 倉庫
2. 在 Zeabur 控制台建立新專案
3. 連接 Git 倉庫
4. 設定環境變數
5. 一鍵部署

## 資料庫架構

### 主要資料表

1. **customers**: 客戶基本資料
2. **sales_staff**: 業務人員資料
3. **customer_sales_assignments**: 客戶業務分配關係
4. **follow_up_records**: 追蹤記錄
5. **form_submissions**: 表單提交記錄
6. **verification_tokens**: 驗證Token

## API 端點

- `POST /webhook`: Line Bot Webhook
- `POST /api/form-submit`: 表單提交
- `GET /health`: 健康檢查

## Line Bot 功能

### 主要指令
- `註冊` / `加入`: 開始註冊流程
- `查詢` / `狀態`: 查詢客戶資訊
- `貸款` / `申請`: 顯示貸款選項
- `聯絡` / `業務`: 聯絡專屬業務

### 自動化功能
- 新用戶自動歡迎訊息
- 表單提交後自動分配業務
- 每週一自動提醒業務追蹤客戶
- 客戶 Line 名稱自動更新

## 安全性

- Helmet.js 安全標頭
- Express Rate Limiting 請求限制
- JWT Token 驗證
- 環境變數加密
- 非 root 用戶運行容器

## 監控與維護

- Docker 健康檢查
- 應用程式健康檢查端點
- 定期資料庫備份（建議）
- 日誌記錄與監控

## 擴展性

系統設計支援未來擴展：
- 微服務架構準備
- API 模組化設計
- 資料庫索引優化
- 快取機制準備

## 支援

如有技術問題或需要協助，請聯絡開發團隊。 