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
        // Create database and table if not exists
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $createTable = "
        CREATE TABLE IF NOT EXISTS mortgage_form_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name VARCHAR(255),
            phone VARCHAR(20),
            line_id VARCHAR(255),
            region VARCHAR(255),
            source_url TEXT,
            utm_source VARCHAR(255),
            utm_medium VARCHAR(255),
            utm_campaign VARCHAR(255),
            ip_address VARCHAR(45),
            user_agent TEXT,
            referrer_url TEXT,
            status VARCHAR(50) DEFAULT 'pending',
            assigned_to VARCHAR(255),
            email VARCHAR(255),
            message TEXT,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTable);
    } else {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    // Get submissions
    $stmt = $pdo->query("SELECT * FROM mortgage_form_submissions ORDER BY submitted_at DESC");
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transform to dashboard format
    $applications = [];
    foreach ($submissions as $index => $submission) {
        $applications[] = [
            'id' => $submission['id'],
            'case_number' => 'CASE' . str_pad($submission['id'], 6, '0', STR_PAD_LEFT),
            'application_date' => $submission['submitted_at'] ?: date('Y-m-d H:i:s'),
            'customer_name' => $submission['customer_name'] ?: '',
            'phone' => $submission['phone'] ?: '',
            'region' => $submission['region'] ?: '',
            'line_id' => $submission['line_id'] ?: '',
            
            // 新欄位
            'sales_staff_name' => $submission['assigned_to'] ?: '',
            'website' => determineWebsiteFromSource($submission),
            'channel' => determineChannelFromSource($submission),
            'lead_status' => '',
            'follow_status' => '',
            'notes' => '',
            'submission_status' => '',
            'case_status' => '',
            'approved_amount' => '',
            'disbursed_amount' => '',
            'disbursement_status' => '',
            
            // 相容性欄位
            'applicant_name' => $submission['customer_name'] ?: '',
            'applicant_line_name' => $submission['line_id'] ?: $submission['customer_name'] ?: '',
            'email' => $submission['email'] ?: '',
            'source_website' => determineWebsiteFromSource($submission),
            'loan_type' => '',
            'collateral_item' => '',
            'monthly_payment' => '',
            'installment_periods' => '',
            'status' => 'pending',
            'can_submit' => false,
            'has_negotiated' => false
        ];
    }
    
    echo json_encode($applications, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}

function determineWebsiteFromSource($submission) {
    $sourceUrl = $submission['source_url'] ?: '';
    $utmSource = $submission['utm_source'] ?: '';
    
    // 根據 URL 或 UTM 來源判斷網站代號
    if (strpos($sourceUrl, 'easypay-life.com.tw') !== false) {
        return 'test'; // 易付生活網站 - 暫時改為 test 供客戶展示
    }
    
    // 可以根據不同的來源設定不同的網站代號
    switch ($utmSource) {
        case 'facebook': return 'G02';
        case 'google': return 'G03';
        case 'line': return 'G04';
        default: return 'G01';
    }
}

function determineChannelFromSource($submission) {
    $sourceUrl = $submission['source_url'] ?: '';
    
    if (strpos($sourceUrl, 'contact') !== false) {
        return '表單';
    }
    
    if ($submission['line_id']) {
        return 'Line@';
    }
    
    return '表單'; // 預設為表單
}
?>