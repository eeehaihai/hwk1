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
// 统一的评论数据文件
$commentsFile = 'comments.json';

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
    // 从 comments.json 中删除与此话题相关的评论
    $allComments = readJsonFile($commentsFile, []);
    if (isset($allComments[$topicId])) {
        unset($allComments[$topicId]);
        saveJsonFile($commentsFile, $allComments); // 保存更新后的评论数据
    }
    
    // 删除话题相关的图片目录 (如果存在)
    $topicImageDir = 'uploads/topics/' . $topicId . '/';
    if (is_dir($topicImageDir)) {
        // 简单的递归删除目录函数 (实际项目中可能需要更健壮的实现)
        function deleteDirectory($dir) {
            if (!file_exists($dir)) {
                return true;
            }
            if (!is_dir($dir)) {
                return unlink($dir);
            }
            foreach (scandir($dir) as $item) {
                if ($item == '.' || $item == '..') {
                    continue;
                }
                if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                    return false;
                }
            }
            return rmdir($dir);
        }
        deleteDirectory($topicImageDir);
    }
    // 删除评论相关的图片目录 (如果存在)
    $commentImageDir = 'uploads/comments/' . $topicId . '/';
     if (is_dir($commentImageDir)) {
        // 使用上面定义的 deleteDirectory 函数
        deleteDirectory($commentImageDir);
    }

    jsonResponse([
        'success' => true,
        'message' => '帖子删除成功'
    ]);
} else {
    jsonResponse([
        'success' => false,
        'message' => getDetailedSaveError($topicsFile, '帖子删除失败，无法保存数据')
    ]);
}
?>
