# 專案端口配置

本文檔詳細說明了專案中所有服務的端口配置。

## 生產環境 (`docker-compose.yml`)

在生產環境中，端口通過環境變數 `${VARIABLE_NAME}` 進行配置。

| 服務 | 外部端口 | 內部端口 | 描述 |
| --- | --- | --- | --- |
| `frontend` | `${FRONTEND_PORT}` | `3000` | 前端應用程序 |
| `backend` | `${BACKEND_PORT}` | `8000` | 後端 API |
| `mysql` | `${DATABASE_PORT}` | `3306` | MySQL 資料庫 |
| `redis` | `${REDIS_PORT}` | `6379` | Redis 服務 |
| `phpmyadmin` | `${PHPMYADMIN_PORT}` | `80` | phpMyAdmin |

## 開發環境 (`docker-compose.dev.yml`)

在開發環境中，端口同樣通過環境變數進行配置，但前端服務的內部端口有所不同。

| 服務 | 外部端口 | 內部端口 | 描述 |
| --- | --- | --- | --- |
| `frontend` | `${FRONTEND_PORT}` | `9122` | 前端開發伺服器 |
| `backend` | `${BACKEND_PORT}` | `8000` | 後端 API |
| `mysql` | `${DATABASE_PORT}` | `3306` | MySQL 資料庫 |
| `redis` | `${REDIS_PORT}` | `6379` | Redis 服務 |
| `phpmyadmin` | `${PHPMYADMIN_PORT}` | `80` | phpMyAdmin |

## 前端開發伺服器 (`frontend/package.json`)

前端開發伺服器可以通過不同的 `npm` 命令啟動，並監聽不同的端口。

| 命令 | 端口 | 描述 |
| --- | --- | --- |
| `dev` | `9122` | 標準開發模式 |
| `dev:local` | `9121` | 本地開發，連接本地後端 API |
| `dev:prod-api` | `3301` | 開發模式，連接到生產環境的 API |

## 後端資料庫配置 (`backend/config/database.php`)

後端資料庫連接的默認端口配置如下：

| 資料庫驅動 | 默認端口 |
| --- | --- |
| `mysql` | `3306` |
| `pgsql` | `5432` |
| `sqlsrv` | `1433` |
| `redis` | `6379` |
