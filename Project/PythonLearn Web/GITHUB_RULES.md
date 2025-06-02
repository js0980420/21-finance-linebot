# 📋 GitHub倉庫管理規則

## 🎯 倉庫定位與用途

### 基本信息
- **倉庫名稱**: `python-collaboration`
- **倉庫URL**: https://github.com/js0980420/python-collaboration/
- **主要用途**: Render雲端部署專用倉庫
- **管理原則**: 保持倉庫乾淨整潔，只包含部署必需檔案
- **分支策略**: 統一使用 `main` 分支，不使用 `master` 分支

### 🎯 倉庫目標
- 專門用於Render雲端平台部署
- 包含Python協作教學平台的核心功能
- 提供完整的部署文檔和指南
- 維持高品質的代碼和文檔標準

## 📁 允許上傳的檔案清單（僅限10個核心檔案）

### ✅ 核心部署檔案（必須包含）
1. **server.js** - Node.js主服務器檔案
2. **package.json** - Node.js依賴配置
3. **package-lock.json** - 依賴鎖定檔案
4. **render.yaml** - Render部署配置

### ✅ 前端檔案
5. **public/index.html** - 學生端主頁面
6. **public/teacher-dashboard.html** - 教師監控後台

### ✅ 文檔檔案
7. **README.md** - 專案說明文檔
8. **DEPLOYMENT_GUIDE.md** - 部署指南
9. **TEACHER_GUIDE.md** - 教師使用指南
10. **GITHUB_RULES.md** - 本規則文檔

## 🚫 禁止上傳的檔案類型

### ❌ 本地開發檔案（保留在本地，不要刪除）
- `*.php` - PHP後端檔案（保留在本地）
- `php/` - PHP目錄
- `mysql/` - MySQL數據庫檔案
- `websocket_version/` - WebSocket版本檔案
- `c:/xampp/` - XAMPP相關檔案
- `ai_config.json` - AI配置檔案
- `chat_api_handler.php` - 聊天API處理器
- `code_sync_handler.php` - 代碼同步處理器
- `ai_api_handler.php` - AI API處理器
- `websocket_server.php` - WebSocket服務器

### ❌ Node.js相關
- `node_modules/` - 依賴包目錄
- `npm-debug.log*` - NPM調試日誌
- `.npm` - NPM快取

### ❌ 批次腳本和配置
- `*.bat` - Windows批次檔案
- `*.cmd` - 命令檔案
- `*.sh` - Shell腳本
- `.htaccess` - Apache配置
- `deploy.bat` - 部署腳本

### ❌ IDE和編輯器檔案
- `.vscode/` - VS Code配置
- `.idea/` - IntelliJ IDEA配置
- `*.swp`, `*.swo` - Vim臨時檔案
- `.cursorrules` - Cursor規則檔案

### ❌ 臨時和備份檔案
- `*.tmp`, `*.temp` - 臨時檔案
- `*.bak`, `*.backup` - 備份檔案
- `*.old` - 舊版本檔案
- `*.log` - 日誌檔案

### ❌ 壓縮檔案
- `*.zip`, `*.tar.gz`, `*.rar`, `*.7z`

### ❌ 其他文檔檔案
- `CONFLICT_TESTING_GUIDE.md`
- `COLLABORATION_TEST_GUIDE.md`
- `CHANGELOG.md`
- `CLEANUP_SUMMARY.md`
- `AI助教*.md`

## 📋 提交規範

### 🏷️ 提交消息格式
```
<類型>: <簡短描述>

<詳細描述>（可選）

<相關問題>（可選）
```

### 📝 提交類型
- `feat`: 新功能
- `fix`: 錯誤修復
- `docs`: 文檔更新
- `style`: 代碼格式調整
- `refactor`: 代碼重構
- `deploy`: 部署相關
- `config`: 配置更新

### 📋 提交消息範例
```bash
feat: 添加教師監控後台功能

- 新增教師監控界面
- 實現即時學生代碼查看
- 添加房間管理功能

Closes #123
```

## 🔄 分支管理策略

### 🌿 分支結構
- **main** - 主分支，用於生產部署（唯一分支）
- 不使用 `master` 分支
- 不創建其他分支，統一在 `main` 分支開發

### 🔀 合併規則
1. 直接推送到 `main` 分支
2. 確保所有測試通過
3. 保持提交歷史清晰
4. 每次推送前先拉取最新代碼

## 🛡️ 安全規則

### 🔐 敏感信息保護
- 絕不提交API密鑰、密碼等敏感信息
- 使用環境變數管理配置
- 定期檢查提交歷史中的敏感信息

### 🔍 代碼審查要點
- 檢查是否有敏感信息洩露
- 確認代碼品質和規範
- 驗證功能完整性
- 檢查文檔更新

## 📊 維護規則

### 🧹 定期清理
- 每月檢查並清理不必要的檔案
- 更新依賴包到最新穩定版本
- 檢查並修復安全漏洞

### 📈 版本管理
- 使用語義化版本號（Semantic Versioning）
- 為重要版本創建Release
- 在README.md中記錄重要變更

### 🔄 同步規則
- 本地開發檔案保留在本地，不上傳到此倉庫
- 定期同步Render部署相關的更新
- 確保文檔與代碼同步更新

## ⚠️ 重要注意事項

### 🚨 嚴禁操作
1. **不要刪除本地檔案** - 本地的PHP、MySQL等檔案是完整功能的一部分
2. **不要上傳本地開發檔案** - 保持倉庫專一性
3. **不要創建其他分支** - 統一使用main分支
4. **不要上傳大型檔案** - 保持倉庫輕量化

### 📞 聯絡方式
如有疑問或需要協助，請聯絡：
- **GitHub Issues**: 在倉庫中創建Issue
- **Email**: support@pythonteaching.com

---

## 🔧 常用版本控制指令

### 📥 初始設置
```bash
# 克隆倉庫
git clone https://github.com/js0980420/python-collaboration.git
cd python-collaboration

# 設置用戶信息
git config user.name "您的名稱"
git config user.email "您的郵箱"

# 檢查遠端倉庫
git remote -v
```

### 📤 日常操作
```bash
# 檢查狀態
git status

# 查看變更
git diff

# 添加檔案到暫存區
git add .                    # 添加所有變更
git add server.js           # 添加特定檔案
git add public/             # 添加整個目錄

# 提交變更
git commit -m "feat: 添加新功能"
git commit -m "fix: 修復衝突檢測問題"
git commit -m "docs: 更新部署指南"

# 推送到遠端
git push origin main

# 拉取最新代碼
git pull origin main
```

### 🔄 同步操作
```bash
# 獲取遠端最新信息
git fetch origin

# 查看分支狀態
git branch -a

# 強制同步遠端（謹慎使用）
git reset --hard origin/main

# 查看提交歷史
git log --oneline
git log --graph --oneline --all
```

### 🛠️ 檔案管理
```bash
# 查看檔案狀態
git ls-files

# 移除檔案（從Git追蹤中移除）
git rm --cached 檔案名
git rm -r --cached 目錄名/

# 重命名檔案
git mv 舊檔名 新檔名

# 查看檔案變更歷史
git log --follow 檔案名
```

### 🔍 檢查和診斷
```bash
# 檢查遠端連接
git remote show origin

# 查看配置
git config --list

# 檢查倉庫大小
git count-objects -vH

# 查看最近的提交
git show HEAD
git show HEAD~1
```

### 🚨 緊急操作
```bash
# 撤銷最後一次提交（保留變更）
git reset --soft HEAD~1

# 撤銷最後一次提交（丟棄變更）
git reset --hard HEAD~1

# 撤銷特定檔案的變更
git checkout -- 檔案名

# 強制推送（謹慎使用）
git push --force origin main
```

### 📋 .gitignore 建議內容
```gitignore
# Node.js
node_modules/
npm-debug.log*
yarn-debug.log*
yarn-error.log*

# 本地開發檔案
*.php
php/
mysql/
websocket_version/
ai_config.json
chat_api_handler.php
code_sync_handler.php
ai_api_handler.php
websocket_server.php

# 批次腳本
*.bat
*.cmd
*.sh
deploy.bat

# IDE檔案
.vscode/
.idea/
*.swp
*.swo
.cursorrules

# 臨時檔案
*.tmp
*.temp
*.bak
*.backup
*.old
*.log

# 壓縮檔案
*.zip
*.tar.gz
*.rar
*.7z

# 其他文檔
CONFLICT_TESTING_GUIDE.md
COLLABORATION_TEST_GUIDE.md
CHANGELOG.md
CLEANUP_SUMMARY.md
AI助教*.md

# 系統檔案
.DS_Store
Thumbs.db
```

### 🎯 快速部署流程
```bash
# 1. 檢查當前狀態
git status

# 2. 添加核心檔案
git add server.js package.json package-lock.json render.yaml
git add public/index.html public/teacher-dashboard.html
git add README.md DEPLOYMENT_GUIDE.md TEACHER_GUIDE.md GITHUB_RULES.md

# 3. 提交變更
git commit -m "deploy: 更新Render部署檔案"

# 4. 推送到GitHub
git push origin main

# 5. 檢查Render自動部署狀態
```

**記住：保持倉庫乾淨整潔是團隊協作的基礎！** 🚀 