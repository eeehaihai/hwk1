<?php
// 开启会话
session_start();

// 设置响应类型为JSON
header('Content-Type: application/json');

// 检查用户是否已登录
if (isset($_SESSION['login'])) {
    echo json_encode([
        'loggedIn' => true,
        'username' => $_SESSION['login']
    ]);
} else {
    echo json_encode([
        'loggedIn' => false
    ]);
}
?>
