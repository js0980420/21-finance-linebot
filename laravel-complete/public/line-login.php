<?php
session_start();

// LINE Bot 設定 - 從環境變數或直接設定
$lineChannelId = '2007625772';  // 請替換為您的 Channel ID
// 動態設定 redirect URI (支援本地和 Zeabur)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$redirectUri = $protocol . $host . '/line-callback.php';
$state = bin2hex(random_bytes(16)); // 防止CSRF攻擊的隨機字串

// 儲存state到session
$_SESSION['line_state'] = $state;

// LINE Login URL
$lineLoginUrl = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $lineChannelId,
    'redirect_uri' => $redirectUri,
    'state' => $state,
    'scope' => 'profile openid',
    'nonce' => bin2hex(random_bytes(16))
]);

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LINE 登入 - 貸款案件管理系統</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            background: linear-gradient(135deg, #00c851 0%, #007bff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .line-login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #00c851;
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .line-login-btn:hover {
            background: #00a041;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 200, 81, 0.3);
        }
        
        .line-icon {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .alternative {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .alt-btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            margin: 5px;
        }
        
        .alt-btn:hover {
            background: #545b62;
        }
        
        .info-box {
            background: #e8f5e8;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-size: 12px;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">📱</div>
        <h1>貸款案件管理系統</h1>
        <p class="subtitle">請使用 LINE 登入來訪問儀表板</p>
        
        <a href="<?php echo htmlspecialchars($lineLoginUrl); ?>" class="line-login-btn">
            <span class="line-icon">📱</span>
            使用 LINE 登入
        </a>
        
        <div class="info-box">
            <strong>✓ 安全登入</strong><br>
            使用您的 LINE 帳號安全登入<br>
            我們不會儲存您的密碼資訊
        </div>
        
        <div class="alternative">
            <p style="color: #666; font-size: 14px;">其他登入方式：</p>
            <a href="crm-login.php" class="alt-btn">👤 角色登入</a>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 15px;">
            ❌ 登入失敗：<?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>