# 使用官方 Node.js 18 LTS 映像
FROM node:18-alpine

# 設定語言標籤讓 Zeabur 自動辨識
LABEL "language"="nodejs"
LABEL "framework"="express"

# 設定工作目錄
WORKDIR /app

# 複製 package.json 和 package-lock.json
COPY package*.json ./

# 安裝依賴套件 (生產環境) - 使用新的 npm 語法
RUN npm ci --omit=dev && npm cache clean --force

# 複製應用程式檔案
COPY . .

# 暴露端口 (Node.js 常用端口)
EXPOSE 3000

# 健康檢查 (單行語法，避免Windows換行問題)
HEALTHCHECK CMD node healthcheck.js || exit 1

# 啟動應用程式
CMD ["npm", "start"] 