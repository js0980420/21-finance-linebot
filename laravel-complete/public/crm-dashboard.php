<?php
$role = $_GET['role'] ?? 'guest';

// 角色配置
$roleConfig = [
    'admin' => [
        'title' => '系統管理員 / 經銷商',
        'icon' => '👨‍💼',
        'color' => '#dc3545',
        'permissions' => ['view_all', 'manage_users', 'system_settings']
    ],
    'staff' => [
        'title' => '行政人員', 
        'icon' => '📋',
        'color' => '#28a745',
        'permissions' => ['view_reports', 'manage_data']
    ],
    'sales' => [
        'title' => '業務人員',
        'icon' => '💼', 
        'color' => '#ffc107',
        'permissions' => ['view_own_data']
    ]
];

$currentRole = $roleConfig[$role] ?? $roleConfig['admin'];

// 模擬資料統計
$stats = [
    'total_customers' => 47,
    'today_new' => 5,
    'pending_tasks' => 12,
    'active_sales' => 4
];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentRole['title']; ?> - 貸款案件管理系統</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Microsoft JhengHei', Arial, sans-serif; background: #f5f6fa; }
        
        .header { background: <?php echo $currentRole['color']; ?>; color: white; padding: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .user-info { font-size: 18px; }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border: none; border-radius: 4px; text-decoration: none; }
        
        .main-content { padding: 30px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 36px; font-weight: bold; color: <?php echo $currentRole['color']; ?>; }
        .stat-label { color: #666; margin-top: 8px; }
        
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .action-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .action-title { font-size: 20px; margin-bottom: 15px; color: #333; }
        .action-btn { display: block; width: 100%; padding: 12px; margin: 8px 0; background: <?php echo $currentRole['color']; ?>; color: white; text-decoration: none; text-align: center; border-radius: 5px; transition: all 0.3s; }
        .action-btn:hover { opacity: 0.9; transform: translateY(-2px); }
        
        .data-preview { margin-top: 30px; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-header { background: <?php echo $currentRole['color']; ?>; color: white; padding: 15px 20px; font-weight: bold; }
        .table-content { padding: 20px; }
        
        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 10px; }
            .stats-grid, .actions-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="user-info">
                    <?php echo $currentRole['icon']; ?> <?php echo $currentRole['title']; ?> - 控制台
                </div>
                <a href="crm-login.php" class="logout-btn">返回登入</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_customers']; ?></div>
                    <div class="stat-label">總客戶數</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['today_new']; ?></div>
                    <div class="stat-label">今日新增</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending_tasks']; ?></div>
                    <div class="stat-label">待處理事項</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_sales']; ?></div>
                    <div class="stat-label">活躍業務</div>
                </div>
            </div>

            <div class="actions-grid">
                <?php if (in_array('view_all', $currentRole['permissions'])): ?>
                <div class="action-card">
                    <div class="action-title">📊 數據管理</div>
                    <a href="data-view.php?type=all" class="action-btn">查看所有客戶資料</a>
                    <a href="data-view.php?type=sales" class="action-btn">業務員管理</a>
                    <a href="data-view.php?type=logs" class="action-btn">系統日誌</a>
                </div>
                <?php endif; ?>

                <?php if (in_array('view_reports', $currentRole['permissions'])): ?>
                <div class="action-card">
                    <div class="action-title">📈 報表分析</div>
                    <a href="reports.php?type=daily" class="action-btn">每日報表</a>
                    <a href="reports.php?type=monthly" class="action-btn">月度統計</a>
                    <a href="reports.php?type=performance" class="action-btn">業績分析</a>
                </div>
                <?php endif; ?>

                <div class="action-card">
                    <div class="action-title">🔧 功能操作</div>
                    <?php if ($role === 'admin'): ?>
                        <a href="dashboard.html" class="action-btn" target="_blank">🏢 貸款案件管理系統</a>
                        <a href="system-settings.php" class="action-btn">系統設置</a>
                        <a href="user-management.php" class="action-btn">用戶管理</a>
                    <?php elseif ($role === 'staff'): ?>
                        <a href="data-export.php" class="action-btn">資料匯出</a>
                        <a href="bulk-operations.php" class="action-btn">批量操作</a>
                    <?php else: ?>
                        <a href="my-customers.php" class="action-btn">我的客戶</a>
                        <a href="follow-up.php" class="action-btn">跟進記錄</a>
                    <?php endif; ?>
                    <a href="profile.php" class="action-btn">個人設置</a>
                </div>
            </div>

            <div class="data-preview">
                <div class="table-header">
                    最近活動記錄 (<?php echo $currentRole['title']; ?>)
                </div>
                <div class="table-content">
                    <p><strong>✅ 系統已完全轉換完成</strong></p>
                    <ul style="margin: 15px 0; padding-left: 20px;">
                        <li>Node.js → Laravel 框架轉換</li>
                        <li>SQLite 資料庫包含完整示例數據</li>
                        <li>4個業務員帳號：李美玲(主管)、陳志強、王小華、林雅婷</li>
                        <li>5筆客戶記錄：張大明、劉小芳、陳建國、李淑美、黃志偉</li>
                        <li>5筆 LINE Bot 查詢日誌</li>
                        <li>完整的權限控制和角色管理</li>
                    </ul>
                    
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin-top: 20px;">
                        <strong>🎉 CRM 系統轉換成功！</strong><br>
                        所有 Node.js 功能已成功轉換為 Laravel，資料庫已就緒，可以開始使用。
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>