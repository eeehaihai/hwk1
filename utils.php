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
 * 获取分类列表
 */
function getCategoryList() {
    return [
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
        error_log("JSON编码失败: " . json_last_error_msg());
        return false;
    }
    
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            error_log("无法创建目录: $directory");
            return false;
        }
    }
    
    // 检查目录是否可写
    if (!is_writable($directory)) {
        error_log("目录不可写: $directory");
        chmod($directory, 0755); // 尝试修改权限
    }
    
    // 检查文件是否存在且不可写
    if (file_exists($filePath) && !is_writable($filePath)) {
        error_log("文件存在但不可写: $filePath");
        chmod($filePath, 0644); // 尝试修改权限
    }
    
    $result = file_put_contents($filePath, $jsonData);
    if ($result === false) {
        error_log("写入文件失败: $filePath");
    }
    return $result !== false;
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

/**
 * 检查用户是否有权限操作话题
 */
function userCanModifyTopic($topic, $username) {
    return $username === 'admin' || $topic['author'] === $username;
}

/**
 * 查找话题
 */
function findTopic($topicId, &$topics) {
    foreach ($topics as &$topic) {
        if ($topic['id'] === $topicId) {
            return [true, &$topic];
        }
    }
    return [false, null];
}
?>
