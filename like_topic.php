<?php
// 开启会话
session_start();

// 设置响应类型为JSON
header('Content-Type: application/json');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
    exit;
}

// 获取话题ID
$topicId = isset($_POST['topicId']) ? $_POST['topicId'] : '';

if (empty($topicId)) {
    echo json_encode([
        'success' => false,
        'message' => '话题ID不能为空'
    ]);
    exit;
}

// 话题数据文件
$topicsFile = 'topics.json';

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

// 查找并更新点赞数
$found = false;
$likes = 0;

foreach ($topics as &$topic) {
    if ($topic['id'] === $topicId) {
        $found = true;
        $topic['likes']++;
        $likes = $topic['likes'];
        break;
    }
}

if (!$found) {
    echo json_encode([
        'success' => false,
        'message' => '话题不存在'
    ]);
    exit;
}

// 保存更新后的话题数据
if (file_put_contents($topicsFile, json_encode($topics, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true,
        'message' => '点赞成功',
        'likes' => $likes
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => '点赞失败，无法保存数据'
    ]);
}
?>
