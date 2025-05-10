<?php
// 开启会话
session_start();

// 引入工具函数
require_once 'utils.php';

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
}

// 获取话题ID
$topicId = isset($_POST['topicId']) ? $_POST['topicId'] : '';

if (empty($topicId)) {
    jsonResponse([
        'success' => false,
        'message' => '话题ID不能为空'
    ]);
}

// 话题数据文件
$topicsFile = 'topics.json';

// 读取话题数据
$topics = readJsonFile($topicsFile);
if (empty($topics)) {
    jsonResponse([
        'success' => false,
        'message' => '话题数据文件不存在或为空'
    ]);
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
    jsonResponse([
        'success' => false,
        'message' => '话题不存在'
    ]);
}

// 保存更新后的话题数据
if (saveJsonFile($topicsFile, $topics)) {
    jsonResponse([
        'success' => true,
        'message' => '点赞成功',
        'likes' => $likes
    ]);
} else {
    jsonResponse([
        'success' => false,
        'message' => '点赞失败，无法保存数据'
    ]);
}
?>
