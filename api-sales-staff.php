<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Connect to SQLite database
    $dbPath = __DIR__ . '/laravel-complete/database/database.sqlite';
    
    if (!file_exists($dbPath)) {
        // Return default sales staff if no database
        $salesStaff = [
            [
                'id' => 1,
                'name' => '李美玲',
                'department' => '業務部',
                'sales_code' => 'S001',
                'line_user_id' => 'U1234567890'
            ],
            [
                'id' => 2,
                'name' => '陳志強',
                'department' => '業務部',
                'sales_code' => 'S002',
                'line_user_id' => 'U2345678901'
            ],
            [
                'id' => 3,
                'name' => '王小華',
                'department' => '業務部',
                'sales_code' => 'S003',
                'line_user_id' => 'U3456789012'
            ],
            [
                'id' => 4,
                'name' => '林雅婷',
                'department' => '業務部',
                'sales_code' => 'S004',
                'line_user_id' => 'U4567890123'
            ]
        ];
        
        echo json_encode($salesStaff, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if sales_line_accounts table exists
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='sales_line_accounts'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        // Create table with sample data
        $createTable = "
        CREATE TABLE sales_line_accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sales_name VARCHAR(255),
            sales_code VARCHAR(50),
            line_user_id VARCHAR(255),
            department VARCHAR(255),
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTable);
        
        // Insert sample data
        $insertData = "
        INSERT INTO sales_line_accounts (sales_name, sales_code, line_user_id, department) VALUES
        ('李美玲', 'S001', 'U1234567890', '業務部'),
        ('陳志強', 'S002', 'U2345678901', '業務部'),
        ('王小華', 'S003', 'U3456789012', '業務部'),
        ('林雅婷', 'S004', 'U4567890123', '業務部')
        ";
        $pdo->exec($insertData);
    }
    
    // Get sales staff
    $stmt = $pdo->query("SELECT * FROM sales_line_accounts WHERE is_active = 1 ORDER BY sales_code");
    $salesStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transform to required format
    $result = [];
    foreach ($salesStaff as $staff) {
        $result[] = [
            'id' => $staff['id'],
            'name' => $staff['sales_name'],
            'department' => $staff['department'] ?: '業務部',
            'sales_code' => $staff['sales_code'],
            'line_user_id' => $staff['line_user_id']
        ];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>