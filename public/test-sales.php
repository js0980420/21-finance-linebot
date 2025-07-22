<?php
// Simple test script for sales staff data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Connect to SQLite database
    $pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if sales_line_accounts table exists and get data
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('sales_line_accounts', $tables)) {
        $stmt = $pdo->query("SELECT * FROM sales_line_accounts WHERE is_active = 1");
        $salesStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to required format
        $result = [];
        foreach ($salesStaff as $staff) {
            $result[] = [
                'id' => $staff['id'],
                'name' => $staff['sales_name'],
                'department' => '業務部',
                'sales_code' => $staff['sales_code'],
                'line_user_id' => $staff['line_user_id']
            ];
        }
        
        // If no real data, create sample data
        if (empty($result)) {
            $result = [
                ['id' => 1, 'name' => '張業務', 'department' => '業務部', 'sales_code' => 'S001', 'line_user_id' => 'U001'],
                ['id' => 2, 'name' => '李業務', 'department' => '業務部', 'sales_code' => 'S002', 'line_user_id' => 'U002'],
                ['id' => 3, 'name' => '王業務', 'department' => '業務部', 'sales_code' => 'S003', 'line_user_id' => 'U003'],
                ['id' => 4, 'name' => '陳業務', 'department' => '業務部', 'sales_code' => 'S004', 'line_user_id' => 'U004'],
                ['id' => 5, 'name' => '林業務', 'department' => '業務部', 'sales_code' => 'S005', 'line_user_id' => 'U005']
            ];
        }
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        // Return sample data if table doesn't exist
        $result = [
            ['id' => 1, 'name' => '張業務', 'department' => '業務部', 'sales_code' => 'S001', 'line_user_id' => 'U001'],
            ['id' => 2, 'name' => '李業務', 'department' => '業務部', 'sales_code' => 'S002', 'line_user_id' => 'U002'],
            ['id' => 3, 'name' => '王業務', 'department' => '業務部', 'sales_code' => 'S003', 'line_user_id' => 'U003'],
            ['id' => 4, 'name' => '陳業務', 'department' => '業務部', 'sales_code' => 'S004', 'line_user_id' => 'U004'],
            ['id' => 5, 'name' => '林業務', 'department' => '業務部', 'sales_code' => 'S005', 'line_user_id' => 'U005']
        ];
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>