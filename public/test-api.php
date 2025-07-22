<?php
// Simple test script to check database data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Connect to SQLite database
    $pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if mortgage_form_submissions table exists and get data
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('mortgage_form_submissions', $tables)) {
        $stmt = $pdo->query("SELECT * FROM mortgage_form_submissions ORDER BY created_at DESC LIMIT 10");
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to case management format
        $applications = [];
        foreach ($submissions as $index => $submission) {
            $applications[] = [
                'id' => $submission['id'],
                'case_number' => 'CASE' . str_pad($submission['id'], 6, '0', STR_PAD_LEFT),
                'application_date' => $submission['created_at'] ?: $submission['submitted_at'] ?: date('Y-m-d H:i:s'),
                'customer_name' => $submission['customer_name'] ?: '測試客戶' . ($index + 1),
                'phone' => $submission['phone'] ?: '0912345678',
                'region' => $submission['region'] ?: '台北市',
                'line_id' => $submission['line_id'] ?: '',
                'sales_staff_name' => $submission['assigned_to'] ?: '',
                'website' => 'G01',
                'channel' => '表單',
                'lead_status' => '',
                'follow_status' => '',
                'notes' => '',
                'submission_status' => '',
                'case_status' => '',
                'approved_amount' => '',
                'disbursed_amount' => '',
                'disbursement_status' => ''
            ];
        }
        
        // If no real data, create sample data
        if (empty($applications)) {
            for ($i = 1; $i <= 5; $i++) {
                $applications[] = [
                    'id' => $i,
                    'case_number' => 'CASE' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'application_date' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                    'customer_name' => '測試客戶' . $i,
                    'phone' => '09123456' . ($i + 10),
                    'region' => ['台北市', '新北市', '桃園市', '台中市', '高雄市'][$i-1],
                    'line_id' => 'line_user_' . $i,
                    'sales_staff_name' => ['張業務', '李業務', '王業務', '陳業務', '林業務'][$i-1],
                    'website' => 'G0' . $i,
                    'channel' => ['表單', '專線', 'Line@'][rand(0,2)],
                    'lead_status' => ['客服', '有效客', '無效客'][rand(0,2)],
                    'follow_status' => ['追蹤中', '聯繫不到'][rand(0,1)],
                    'notes' => '測試備註 ' . $i,
                    'submission_status' => $i <= 2 ? '已送件' : '',
                    'case_status' => $i <= 2 ? ['核准', '附條件', '婉拒'][rand(0,2)] : '',
                    'approved_amount' => $i <= 2 ? rand(100, 500) * 1000 : '',
                    'disbursed_amount' => $i <= 2 ? rand(80, 450) * 1000 : '',
                    'disbursement_status' => $i <= 2 ? ['已撥款', '未撥款'][rand(0,1)] : ''
                ];
            }
        }
        
        echo json_encode($applications, JSON_UNESCAPED_UNICODE);
    } else {
        // Return sample data if table doesn't exist
        $applications = [];
        for ($i = 1; $i <= 5; $i++) {
            $applications[] = [
                'id' => $i,
                'case_number' => 'CASE' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'application_date' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                'customer_name' => '測試客戶' . $i,
                'phone' => '09123456' . ($i + 10),
                'region' => ['台北市', '新北市', '桃園市', '台中市', '高雄市'][$i-1],
                'line_id' => 'line_user_' . $i,
                'sales_staff_name' => ['張業務', '李業務', '王業務', '陳業務', '林業務'][$i-1],
                'website' => 'G0' . $i,
                'channel' => ['表單', '專線', 'Line@'][rand(0,2)],
                'lead_status' => ['客服', '有效客', '無效客'][rand(0,2)],
                'follow_status' => ['追蹤中', '聯繫不到'][rand(0,1)],
                'notes' => '測試備註 ' . $i,
                'submission_status' => $i <= 2 ? '已送件' : '',
                'case_status' => $i <= 2 ? ['核准', '附條件', '婉拒'][rand(0,2)] : '',
                'approved_amount' => $i <= 2 ? rand(100, 500) * 1000 : '',
                'disbursed_amount' => $i <= 2 ? rand(80, 450) * 1000 : '',
                'disbursement_status' => $i <= 2 ? ['已撥款', '未撥款'][rand(0,1)] : ''
            ];
        }
        echo json_encode($applications, JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>