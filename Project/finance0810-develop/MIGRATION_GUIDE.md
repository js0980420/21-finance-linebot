# Migration 管理指南

## 📋 Migration 執行順序規則

### 🎯 核心原則
1. **絕對不能更改現有 migration 檔案的時間戳**
2. **新 migration 必須使用當前時間創建**
3. **有依賴關係的 migration 必須按順序執行**

## 📊 當前 Migration 分組

### 第一階段：核心架構 (2024_01_01)
```
000001 - users (用戶表) ✅ 基礎
000003 - customers (客戶表) ✅ 依賴：users
000004 - customer_cases (客戶案例) ✅ 依賴：users, customers
000005 - bank_records (銀行記錄) ✅ 依賴：customers
000006 - chat_conversations (聊天記錄) ✅ 依賴：customers
000007 - customer_activities (客戶活動) ✅ 依賴：customers
```

### 第二階段：功能增強 (2024_12_16 - 2025_01_05)
```
2024_12_16_000001 - 聊天功能增強
2025_01_01_000001-000010 - 狀態管理和版本控制
2025_01_02_000001-000002 - 版本追蹤系統
2025_01_03_000001 - 聊天系統綜合修復
2025_01_05_000001 - 狀態列舉最終修復
```

### 第三階段：權限和客戶管理 (2025_08)
```
2025_08_10_140040 - 權限表 (Spatie Permission)
2025_08_12_000001-000006 - 客戶識別和潛在客戶
2025_08_13_000001 - LINE 整合設定
2025_08_16_000001-105001 - 潛在客戶增強和角色確保
2025_08_19_000001-000003 - 聊天版本和索引優化
2025_08_20_000001-141900 - 潛在客戶案例和架構最終修復
```

### 第四階段：整合功能 (2025_08_28 - 2025_09_10)
```
2025_08_28_000001 - Webhook 執行日誌
2025_08_29_000001-000002 - LINE Webhook 設定和客戶版本修復
2025_09_01_000001-000003 - LINE 用戶和網站管理
2025_09_10_000001 - 網站欄位映射
```

## 🔧 安全協作流程

### ✅ 可以安全執行的操作：
1. **新增 migration**：使用 `php artisan make:migration`
2. **修改未執行的 migration**：如果還沒有在生產環境執行
3. **添加索引**：不影響資料結構
4. **新增欄位**：使用 `nullable()` 或設定預設值

### ❌ 絕對禁止的操作：
1. **更改已執行 migration 的時間戳**
2. **刪除已在生產環境執行的 migration**
3. **修改已執行 migration 的 up() 方法**
4. **更改外鍵約束順序**

## 🚀 協作最佳實踐

### 新增 Migration 流程：
```bash
# 1. 確保本地資料庫是最新的
php artisan migrate:status

# 2. 創建新 migration (自動產生正確時間戳)
php artisan make:migration create_new_table

# 3. 編寫 migration 內容
# 4. 測試執行
php artisan migrate --pretend

# 5. 實際執行
php artisan migrate

# 6. 確認可以回滾
php artisan migrate:rollback --dry-run
```

### 團隊協作檢查清單：
- [ ] Migration 命名清楚易懂
- [ ] 包含完整的 up() 和 down() 方法
- [ ] 測試過執行和回滾
- [ ] 檢查是否有依賴關係
- [ ] 在測試環境驗證過
- [ ] 更新相關文檔

## 🆘 緊急修復指南

### 如果 Migration 執行失敗：
1. **不要強制修改時間戳**
2. **創建新的修復 migration**
3. **使用 migration 的 down() 方法回滾**
4. **記錄問題和解決方案**

### 常見問題解決：
```bash
# 檢查 migration 狀態
php artisan migrate:status

# 重置特定 migration
php artisan migrate:rollback --step=1

# 刷新並重新執行
php artisan migrate:refresh

# 查看 SQL 不實際執行
php artisan migrate --pretend
```

## 📞 協作溝通

### 需要協調的情況：
1. **跨表關聯變更**
2. **大型資料結構調整**
3. **生產環境 migration**
4. **效能影響評估**

### 溝通檢查點：
- 🔄 **變更前**：討論影響範圍
- 🧪 **測試中**：分享測試結果
- ✅ **完成後**：確認執行成功
- 📚 **文檔**：更新相關說明

記住：**Migration 的順序就像建築的地基，一旦打好就不能隨意更動！** 🏗️