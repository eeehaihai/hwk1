<?php
// 开启会话
session_start();

// 引入工具函数
require_once 'utils.php';

// 检查用户是否已登录
if (!isUserLoggedIn()) {
    jsonResponse([
        'success' => false,
        'message' => '请先登录'
    ]);
}

// 检查是否为POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
}

// 获取帖子ID
$topicId = isset($_POST['topicId']) ? $_POST['topicId'] : '';

if (empty($topicId)) {
    jsonResponse([
        'success' => false,
        'message' => '帖子ID不能为空'
    ]);
}

// 当前登录用户
$currentUser = getCurrentUser();

// 话题数据文件
$topicsFile = 'topics.json';

// 评论文件
$commentsFile = 'comments_' . $topicId . '.json';

// 读取话题数据
$topics = readJsonFile($topicsFile);
if (empty($topics)) {
    jsonResponse([
        'success' => false,
        'message' => '话题数据文件不存在或为空'
    ]);
}

// 查找话题
$topicIndex = -1;
foreach ($topics as $index => $topic) {
    if ($topic['id'] === $topicId) {
        $topicIndex = $index;
        
        // 检查用户权限
        if (!userCanModifyTopic($topic, $currentUser)) {
            jsonResponse([
                'success' => false,
                'message' => '您没有权限删除此帖子'
            ]);
        }
        break;
    }
}

// 如果找不到帖子
if ($topicIndex === -1) {
    jsonResponse([
        'success' => false,
        'message' => '帖子不存在'
    ]);
}

// 删除帖子
array_splice($topics, $topicIndex, 1);

// 保存更新后的话题数据
if (saveJsonFile($topicsFile, $topics)) {
    // 如果存在评论文件，也可以删除
    if (file_exists($commentsFile)) {
        unlink($commentsFile);
    }
    
    jsonResponse([
        'success' => true,
        'message' => '帖子删除成功'
    ]);
} else {
    jsonResponse([
        'success' => false,
        'message' => '帖子删除失败，无法保存数据'
    ]);
}
?>
