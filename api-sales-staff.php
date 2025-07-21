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
    // Always return sample data for demo purposes
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
    
} catch (Exception $e) {
    // Fallback data
    echo json_encode([
        [
            'id' => 1,
            'name' => '系統管理員',
            'department' => '業務部',
            'sales_code' => 'SYS',
            'line_user_id' => 'SYSTEM'
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>