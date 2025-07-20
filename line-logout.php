<?php
session_start();

// 清除LINE用戶session
if (isset($_SESSION['line_user'])) {
    unset($_SESSION['line_user']);
}

// 清除整個session
session_destroy();

// 返回成功狀態
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => '已成功登出']);
?>