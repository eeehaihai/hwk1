<?php
// 开启会话
session_start();

// 设置响应类型为JSON
header('Content-Type: application/json');

// 检查用户是否已登录
if (!isset($_SESSION['login'])) {
    echo json_encode([
        'success' => false,
        'message' => '请先登录'
    ]);
    exit;
}

// 检查是否为POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
    exit;
}

// 获取帖子ID
$topicId = isset($_POST['topicId']) ? $_POST['topicId'] : '';

if (empty($topicId)) {
    echo json_encode([
        'success' => false,
        'message' => '帖子ID不能为空'
    ]);
    exit;
}

// 当前登录用户
$currentUser = $_SESSION['login'];

// 话题数据文件
$topicsFile = 'topics.json';

// 评论文件
$commentsFile = 'comments_' . $topicId . '.json';

// 读取话题数据
if (!file_exists($topicsFile)) {
    echo json_encode([
        'success' => false,
        'message' => '话题数据文件不存在'
    ]);
    exit;
}

$topicsData = file_get_contents($topicsFile);
$topics = json_decode($topicsData, true);

if (!is_array($topics)) {
    echo json_encode([
        'success' => false,
        'message' => '话题数据格式错误'
    ]);
    exit;
}

// 查找并检查权限
$topicIndex = -1;
$isAuthor = false;

foreach ($topics as $index => $topic) {
    if ($topic['id'] === $topicId) {
        $topicIndex = $index;
        // 检查当前用户是否为作者
        if ($topic['author'] === $currentUser) {
            $isAuthor = true;
        }
        break;
    }
}

// 如果找不到帖子
if ($topicIndex === -1) {
    echo json_encode([
        'success' => false,
        'message' => '帖子不存在'
    ]);
    exit;
}

// 如果不是作者（也不是管理员）
if (!$isAuthor && $currentUser !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => '您没有权限删除此帖子'
    ]);
    exit;
}

// 删除帖子
array_splice($topics, $topicIndex, 1);

// 保存更新后的话题数据
if (file_put_contents($topicsFile, json_encode($topics, JSON_PRETTY_PRINT))) {
    // 如果存在评论文件，也可以删除
    if (file_exists($commentsFile)) {
        unlink($commentsFile);
    }
    
    echo json_encode([
        'success' => true,
        'message' => '帖子删除成功'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '帖子删除失败，无法保存数据'
    ]);
}
?>
