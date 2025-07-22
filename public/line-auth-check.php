<?php
session_start();
header('Content-Type: application/json');

// 檢查是否有LINE登入
if (isset($_SESSION['line_user'])) {
    echo json_encode([
        'authenticated' => true,
        'user' => $_SESSION['line_user'],
        'role' => 'line_user'
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}
?>