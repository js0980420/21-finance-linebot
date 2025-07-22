<?php
session_start();

// 載入環境變數
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// LINE Bot 設定
$lineChannelId = '2007625772';  // 請替換為您的 Channel ID
$lineChannelSecret = $_ENV['LINE_CHANNEL_SECRET'] ?? '7d430abd5422c86a23be4ea1514dc3b7';
// 動態設定 redirect URI (支援本地和 Zeabur)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$redirectUri = $protocol . $host . '/line-callback.php';

try {
    // 驗證state參數防止CSRF攻擊
    if (!isset($_GET['state']) || !isset($_SESSION['line_state']) || $_GET['state'] !== $_SESSION['line_state']) {
        throw new Exception('Invalid state parameter');
    }
    
    // 檢查是否有授權碼
    if (!isset($_GET['code'])) {
        throw new Exception('Authorization code not received');
    }
    
    $authCode = $_GET['code'];
    
    // 交換 access token
    $tokenUrl = 'https://api.line.me/oauth2/v2.1/token';
    $tokenData = [
        'grant_type' => 'authorization_code',
        'code' => $authCode,
        'redirect_uri' => $redirectUri,
        'client_id' => $lineChannelId,
        'client_secret' => $lineChannelSecret
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $tokenResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to get access token: ' . $tokenResponse);
    }
    
    $tokenData = json_decode($tokenResponse, true);
    if (!isset($tokenData['access_token'])) {
        throw new Exception('Access token not found in response');
    }
    
    $accessToken = $tokenData['access_token'];
    
    // 獲取用戶資料
    $profileUrl = 'https://api.line.me/v2/profile';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $profileUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $profileResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to get user profile: ' . $profileResponse);
    }
    
    $userProfile = json_decode($profileResponse, true);
    
    // 儲存用戶資訊到session
    $_SESSION['line_user'] = [
        'user_id' => $userProfile['userId'],
        'display_name' => $userProfile['displayName'],
        'picture_url' => $userProfile['pictureUrl'] ?? '',
        'login_time' => time()
    ];
    
    // 可選：將用戶資訊儲存到資料庫
    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 檢查用戶表是否存在，不存在則創建
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='line_users'");
        if (!$stmt->fetch()) {
            $createTable = "
                CREATE TABLE line_users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    line_user_id VARCHAR(255) UNIQUE,
                    display_name VARCHAR(255),
                    picture_url TEXT,
                    role VARCHAR(50) DEFAULT 'user',
                    last_login DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $pdo->exec($createTable);
        }
        
        // 插入或更新用戶資料
        $stmt = $pdo->prepare("
            INSERT OR REPLACE INTO line_users (line_user_id, display_name, picture_url, last_login, updated_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userProfile['userId'],
            $userProfile['displayName'],
            $userProfile['pictureUrl'] ?? '',
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        error_log('Database error: ' . $e->getMessage());
    }
    
    // 登入成功，重導向到儀表板
    header('Location: dashboard.html?line_login=success');
    exit;
    
} catch (Exception $e) {
    error_log('LINE Login Error: ' . $e->getMessage());
    header('Location: line-login.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>