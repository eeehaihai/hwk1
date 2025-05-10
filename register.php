<?php
// 设置响应类型为JSON
header('Content-Type: application/json');

// 检查是否为POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取用户提交的注册数据
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $nickname = isset($_POST['nickname']) ? $_POST['nickname'] : '';
    
    // 简单验证
    if (empty($username) || empty($password) || empty($email) || empty($phone)) {
        echo json_encode([
            'success' => false,
            'message' => '请填写所有必填字段'
        ]);
        exit;
    }
    
    // 验证用户名格式
    if (!preg_match('/^[a-zA-Z0-9_]{5,20}$/', $username)) {
        echo json_encode([
            'success' => false,
            'message' => '用户名必须是5-20位字母、数字或下划线'
        ]);
        exit;
    }
    
    // 验证密码长度
    if (strlen($password) < 8) {
        echo json_encode([
            'success' => false,
            'message' => '密码长度必须大于或等于8位'
        ]);
        exit;
    }
    
    // 验证邮箱格式
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => '请输入有效的电子邮箱地址'
        ]);
        exit;
    }
    
    // 验证手机号格式
    if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
        echo json_encode([
            'success' => false,
            'message' => '请输入有效的11位手机号码'
        ]);
        exit;
    }
    
    // 读取用户数据文件
    $usersFile = 'users.json';
    if (file_exists($usersFile)) {
        $usersData = file_get_contents($usersFile);
        $users = json_decode($usersData, true);
    } else {
        $users = ['users' => []];
    }
    
    // 检查用户名是否已存在
    foreach ($users['users'] as $user) {
        if ($user['username'] === $username) {
            echo json_encode([
                'success' => false,
                'message' => '用户名已被使用'
            ]);
            exit;
        }
    }
    
    // 添加新用户
    $users['users'][] = [
        'username' => $username,
        'password' => $password,
        'email' => $email,
        'phone' => $phone,
        'nickname' => $nickname,
        'registerTime' => date('Y-m-d H:i:s')
    ];
    
    // 保存到文件
    if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
        echo json_encode([
            'success' => true,
            'message' => '注册成功'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '注册失败，无法保存用户数据'
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
