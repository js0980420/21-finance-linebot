<?php
// Webhook receiver for form submissions from easypay-life.com.tw
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
    // Log all incoming requests
    $logFile = __DIR__ . '/../storage/logs/webhook.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $requestData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'headers' => getallheaders(),
        'get' => $_GET,
        'post' => $_POST,
        'raw_input' => file_get_contents('php://input'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    file_put_contents($logFile, json_encode($requestData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    
    // Connect to SQLite database
    $pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle form data
    $formData = [];
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $rawInput = file_get_contents('php://input');
        
        // Try to parse JSON
        if (!empty($rawInput)) {
            $jsonData = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $formData = $jsonData;
            }
        }
        
        // Merge POST data
        $formData = array_merge($formData, $_POST);
        
        // Extract form fields (handle different field name variations)
        $customerName = $formData['name'] ?? $formData['customer_name'] ?? $formData['姓名'] ?? '';
        $phone = $formData['phone'] ?? $formData['電話'] ?? $formData['手機'] ?? '';
        $region = $formData['region'] ?? $formData['area'] ?? $formData['地區'] ?? '';
        $lineId = $formData['line_id'] ?? $formData['line'] ?? $formData['Line'] ?? '';
        $email = $formData['email'] ?? $formData['信箱'] ?? '';
        $message = $formData['message'] ?? $formData['備註'] ?? $formData['內容'] ?? '';
        
        // Only process if we have required fields
        if (!empty($customerName) && !empty($phone)) {
            
            // Check if mortgage_form_submissions table exists
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='mortgage_form_submissions'");
            $tableExists = $stmt->fetch();
            
            if (!$tableExists) {
                // Create table if it doesn't exist
                $createTable = "
                CREATE TABLE mortgage_form_submissions (
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
            }
            
            // Insert form submission
            $stmt = $pdo->prepare("
                INSERT INTO mortgage_form_submissions 
                (customer_name, phone, line_id, region, email, message, source_url, ip_address, user_agent, referrer_url, submitted_at, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $now = date('Y-m-d H:i:s');
            $sourceUrl = $_SERVER['HTTP_REFERER'] ?? 'https://easypay-life.com.tw/contact/';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $referrer = $_SERVER['HTTP_REFERER'] ?? '';
            
            $stmt->execute([
                $customerName,
                $phone,
                $lineId,
                $region,
                $email,
                $message,
                $sourceUrl,
                $ipAddress,
                $userAgent,
                $referrer,
                $now,
                $now,
                $now
            ]);
            
            $submissionId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => '表單提交成功',
                'submission_id' => $submissionId,
                'data_received' => [
                    'customer_name' => $customerName,
                    'phone' => $phone,
                    'region' => $region,
                    'line_id' => $lineId
                ]
            ], JSON_UNESCAPED_UNICODE);
            
        } else {
            echo json_encode([
                'success' => false,
                'message' => '缺少必填欄位：姓名和電話',
                'received_data' => $formData
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } else {
        // GET request - show status
        echo json_encode([
            'status' => 'webhook receiver ready',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoint' => 'POST /webhook-receiver.php',
            'required_fields' => ['name/customer_name', 'phone'],
            'optional_fields' => ['line_id', 'region', 'email', 'message']
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
}
?>