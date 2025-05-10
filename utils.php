<?php
// 通用PHP函数库

/**
 * 获取分类名称
 */
function getCategoryName($category) {
    $categoryNames = [
        'campus_life' => '校园生活',
        'study' => '学习交流',
        'emotion' => '情感树洞',
        'career' => '就业考研',
        'entertainment' => '娱乐休闲',
        'secondhand' => '二手交易',
        'lost_found' => '失物招领',
        'suggestion' => '建议反馈',
        'other' => '其他'
    ];
    
    return isset($categoryNames[$category]) ? $categoryNames[$category] : '其他';
}

/**
 * 从文件中读取JSON数据
 */
function readJsonFile($filePath, $defaultValue = []) {
    if (file_exists($filePath)) {
        $data = file_get_contents($filePath);
        if ($data !== false) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    }
    return $defaultValue;
}

/**
 * 将数据保存为JSON文件
 */
function saveJsonFile($filePath, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT);
    if ($jsonData === false) {
        return false;
    }
    
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    return file_put_contents($filePath, $jsonData);
}

/**
 * 返回JSON响应
 */
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * 检查用户是否已登录
 */
function isUserLoggedIn() {
    return isset($_SESSION['login']);
}

/**
 * 获取当前登录用户名
 */
function getCurrentUser() {
    return $_SESSION['login'] ?? null;
}
?>
