# 🔄 Python協作教學平台 - 完整衝突檢測版

[![部署狀態](https://img.shields.io/badge/部署-Render-brightgreen)](https://render.com)
[![Node.js版本](https://img.shields.io/badge/Node.js-16%2B-green)](https://nodejs.org)
[![授權](https://img.shields.io/badge/授權-MIT-blue)](LICENSE)

一個功能完整的Python多人協作教學平台，具備智能衝突檢測、教師監控後台和AI助教功能。

## 🌟 核心特色

### 🔄 智能衝突檢測系統
- **自動版本衝突檢測**: 當多人同時編輯時自動觸發
- **三種解決方案**: 重新載入、強制更新、聊天討論
- **AI協助分析**: 智能分析衝突原因和合併建議
- **協作提醒**: 即時顯示其他用戶正在編輯

### 👨‍🏫 教師監控後台
- **實時統計儀表板**: 活躍房間、在線學生、衝突統計
- **監視器效果**: 像監控錄影一樣的即時更新
- **廣播系統**: 向指定房間發送通知
- **房間管理**: 關閉不當使用的房間

### 🤖 AI助教功能
- **程式碼分析**: 提供程式碼品質評分和建議
- **錯誤檢查**: 自動檢測語法和邏輯錯誤
- **改進建議**: 提供最佳實踐和優化建議
- **衝突協助**: 智能分析版本衝突並提供解決方案

### ⚡ 即時協作
- **毫秒級同步**: WebSocket實現即時代碼同步
- **游標追蹤**: 顯示其他用戶的編輯位置
- **聊天系統**: 支援文字聊天和代碼討論
- **用戶管理**: 實時顯示在線用戶列表

## 🚀 快速開始

### 在線體驗
- **學生端**: [https://your-app-name.onrender.com](https://your-app-name.onrender.com)
- **教師後台**: [https://your-app-name.onrender.com/teacher](https://your-app-name.onrender.com/teacher)

### 本地運行

```bash
# 克隆倉庫
git clone https://github.com/your-username/python-collaboration-demo-render.git
cd python-collaboration-demo-render

# 安裝依賴
npm install

# 設置環境變數（可選）
export OPENAI_API_KEY=your_openai_api_key_here

# 啟動服務器
npm start
```

訪問 `http://localhost:3000` 開始使用！

## 🧪 功能測試

### 衝突檢測測試

1. **開啟兩個瀏覽器窗口**（建議使用無痕模式）
2. **使用不同用戶名加入同一房間**
3. **同時編輯代碼觸發衝突**
4. **體驗三種衝突解決方案**

### 教師監控測試

1. **訪問教師後台** `/teacher`
2. **查看實時統計和房間監控**
3. **測試廣播和房間管理功能**

### AI助教測試

1. **在編輯器中輸入Python代碼**
2. **點擊AI助教按鈕測試各種功能**

## 📁 專案結構

```
render_demo/
├── server.js                    # 主服務器（包含所有功能）
├── package.json                 # Node.js依賴配置
├── public/
│   ├── index.html              # 學生端主頁面
│   └── teacher-dashboard.html  # 教師監控後台
├── DEPLOYMENT_GUIDE.md         # 詳細部署指南
├── TEACHER_GUIDE.md            # 教師使用指南
├── CONFLICT_TESTING_GUIDE.md   # 衝突測試指南
├── deploy.bat                  # Windows快速部署腳本
└── README.md                   # 專案說明
```

## 🔧 技術架構

- **後端**: Node.js + Express + WebSocket
- **前端**: HTML5 + CSS3 + JavaScript + Bootstrap + CodeMirror
- **AI功能**: OpenAI API (GPT-3.5-turbo)
- **部署**: Render雲端平台
- **版本控制**: Git + GitHub

## 🌐 部署指南

### 快速部署（Windows）

1. **執行部署腳本**:
   ```bash
   cd render_demo
   deploy.bat
   ```

2. **按照提示輸入GitHub用戶名**

3. **在Render創建Web Service**:
   - 訪問 [render.com](https://render.com)
   - 選擇您的GitHub倉庫
   - 配置環境變數 `OPENAI_API_KEY`

### 手動部署

詳細步驟請參考 [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

## 📚 使用指南

### 學生使用

1. **加入房間**: 輸入房間名稱和您的名稱
2. **協作編程**: 與其他學生一起編輯Python代碼
3. **處理衝突**: 當出現版本衝突時選擇合適的解決方案
4. **使用AI助教**: 獲得程式碼分析和改進建議
5. **聊天討論**: 與同伴討論程式碼和解決問題

### 教師使用

詳細指南請參考 [TEACHER_GUIDE.md](TEACHER_GUIDE.md)

1. **訪問監控後台**: `/teacher`
2. **監控學生活動**: 查看所有房間和學生狀態
3. **廣播通知**: 向學生發送重要消息
4. **管理房間**: 關閉不當使用的房間

## 🎯 衝突檢測功能

### 自動檢測機制

- **版本追蹤**: 每次代碼變更都有版本號
- **衝突觸發**: 當客戶端版本低於服務器版本時觸發
- **即時通知**: 立即顯示衝突解決選項

### 三種解決方案

1. **🔄 重新載入最新版本**
   - 放棄當前修改，載入服務器最新代碼
   - 適用於發現其他人的修改更好的情況

2. **⚡ 繼續我的編輯（強制更新）**
   - 用自己的代碼覆蓋服務器版本
   - ⚠️ 注意：會覆蓋其他人的工作，需謹慎使用

3. **💬 複製到聊天討論區**
   - 將衝突代碼複製到聊天室供大家討論
   - 推薦方案：團隊協商解決衝突

### AI協助分析

- **衝突原因分析**: 智能分析兩個版本的差異
- **合併建議**: 提供具體的代碼合併方案
- **最佳實踐**: 建議避免未來衝突的方法

## 🤖 AI助教功能

### 程式碼分析
- 提供程式碼品質評分（0-100分）
- 分析程式碼結構和邏輯
- 給出改進建議

### 錯誤檢查
- 自動檢測語法錯誤
- 識別邏輯問題
- 提供修正建議

### 改進建議
- 程式碼優化建議
- 最佳實踐推薦
- 性能改進提示

## 📊 教師監控功能

### 實時統計
- 活躍房間數量
- 在線學生數量
- 衝突發生次數
- 代碼編輯次數

### 房間監控
- 查看每個房間的學生列表
- 實時代碼預覽
- 聊天記錄監控
- 衝突狀態顯示

### 管理功能
- 廣播系統（四種消息類型）
- 房間關閉功能
- 活動日誌記錄

## 🔒 安全特性

- **輸入驗證**: 嚴格驗證用戶輸入
- **XSS防護**: 防止跨站腳本攻擊
- **API密鑰保護**: 使用環境變數存儲敏感信息
- **連接管理**: 自動清理不活躍連接

## 📈 性能特性

- **WebSocket優化**: 毫秒級實時同步
- **內存管理**: 自動清理過期數據
- **連接池**: 高效的連接管理
- **負載均衡**: 支援多實例部署

## 🛠️ 開發指南

### 本地開發

```bash
# 安裝依賴
npm install

# 開發模式（自動重啟）
npm run dev

# 生產模式
npm start
```

### 環境變數

```bash
OPENAI_API_KEY=your_openai_api_key_here  # OpenAI API密鑰
NODE_ENV=production                      # 環境模式
PORT=3000                               # 服務器端口
```

### API端點

- `GET /` - 學生端主頁面
- `GET /teacher` - 教師監控後台
- `GET /api/status` - 服務器狀態
- `WebSocket /` - 即時通信

## 🤝 貢獻指南

歡迎提交Issue和Pull Request！

1. Fork本倉庫
2. 創建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交變更 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 開啟Pull Request

## 📄 授權

本專案採用MIT授權 - 詳見 [LICENSE](LICENSE) 文件

## 🙏 致謝

- [CodeMirror](https://codemirror.net/) - 程式碼編輯器
- [Bootstrap](https://getbootstrap.com/) - UI框架
- [OpenAI](https://openai.com/) - AI功能支援
- [Render](https://render.com/) - 雲端部署平台

## 📞 聯繫我們

- **專案首頁**: [GitHub Repository](https://github.com/your-username/python-collaboration-demo-render)
- **線上演示**: [Live Demo](https://your-app-name.onrender.com)
- **問題回報**: [GitHub Issues](https://github.com/your-username/python-collaboration-demo-render/issues)

---

**🎓 這不僅是一個技術展示，更是真正有教學價值的協作工具！**

讓學生在真實的協作環境中學習程式設計，體驗版本控制、衝突解決和團隊合作的重要性。 