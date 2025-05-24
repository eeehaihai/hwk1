<?php
// 开启会话
session_start();

// 设置响应类型为JSON
header('Content-Type: application/json');

// 检查是否为POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取用户提交的数据
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // 简单验证
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => '用户名和密码不能为空'
        ]);
        exit;
    }
    
    // 读取用户数据文件
    $usersFile = 'users.json';
    if (!file_exists($usersFile)) {
        echo json_encode([
            'success' => false,
            'message' => '用户数据文件不存在'
        ]);
        exit;
    }
    
    $usersData = file_get_contents($usersFile);
    $users = json_decode($usersData, true);
    
    // 验证用户
    $authenticated = false;
    if (isset($users['users']) && is_array($users['users'])) { // 确保 users 结构正确
        foreach ($users['users'] as $user) {
            if ($user['username'] === $username && $user['password'] === $password) { // 注意：实际项目中密码应哈希比较
                $authenticated = true;
                break;
            }
        }
    }
    
    // 处理验证结果
    if ($authenticated) {
        // 设置会话
        $_SESSION['login'] = $username;
        
        echo json_encode([
            'success' => true,
            'message' => '登录成功'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '用户名或密码错误'
        ]);
    }
} else {
    // 非POST请求返回错误
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
}
?>
