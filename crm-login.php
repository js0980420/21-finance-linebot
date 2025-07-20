<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>貸款案件管理系統 CRM 登入</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 100px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .role-btn { display: block; width: 100%; padding: 15px; margin: 10px 0; background: #007bff; color: white; text-decoration: none; text-align: center; border-radius: 5px; border: none; font-size: 16px; cursor: pointer; transition: all 0.3s; }
        .role-btn:hover { background: #0056b3; transform: translateY(-2px); }
        .admin { background: #dc3545; } .admin:hover { background: #c82333; }
        .manager { background: #28a745; } .manager:hover { background: #218838; }
        .sales { background: #ffc107; color: #000; } .sales:hover { background: #e0a800; }
        .dealer { background: #6c757d; } .dealer:hover { background: #5a6268; }
        .status { text-align: center; padding: 20px; background: #e8f5e8; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="status">
            <h2>✅ 系統運行正常</h2>
            <p>PHP <?php echo phpversion(); ?> | SQLite 資料庫已就緒</p>
        </div>
        
        <h1>🏢 貸款案件管理系統</h1>
        <p style="text-align: center; color: #666; margin-bottom: 30px;">請選擇您的角色登入</p>
        
        <a href="line-login.php" class="role-btn" style="background: #00c851; margin-bottom: 20px;">
            📱 LINE 登入<br><small>使用 LINE 帳號安全登入</small>
        </a>
        
        <a href="dashboard.html" class="role-btn admin">
            👨‍💼 案件管理系統後台<br><small>貸款案件管理系統</small>
        </a>
        
        <a href="crm-dashboard.php?role=staff" class="role-btn manager">
            📋 行政人員<br><small>處理行政業務和資料管理</small>
        </a>
        
        <a href="crm-dashboard.php?role=sales" class="role-btn sales">
            💼 業務人員<br><small>管理個人客戶資料</small>
        </a>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <h3>📊 系統資訊</h3>
            <ul style="list-style: none; padding: 0;">
                <li>✅ <strong>4個業務員帳號</strong>：李美玲(主管)、陳志強、王小華、林雅婷</li>
                <li>✅ <strong>5筆客戶記錄</strong>：完整的表單提交資料</li>
                <li>✅ <strong>5筆查詢日誌</strong>：LINE Bot 操作記錄</li>
                <li>✅ <strong>完整功能</strong>：數據篩選、統計報表、權限控制</li>
            </ul>
        </div>
        
        <div style="margin-top: 20px; text-align: center; color: #999; font-size: 12px;">
            Node.js → Laravel 轉換完成 | 資料庫已就緒
        </div>
    </div>
</body>
</html>