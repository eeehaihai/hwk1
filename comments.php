<?php
// 开启会话
session_start();

// 引入工具函数
require_once 'utils.php';

// 统一的评论数据文件
$commentsFile = 'comments.json';

// 发表评论
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查用户是否登录
    if (!isUserLoggedIn()) {
        jsonResponse([
            'success' => false,
            'message' => '请先登录'
        ]);
    }
    
    // 获取评论数据
    $topicId = isset($_POST['topicId']) ? $_POST['topicId'] : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $anonymous = isset($_POST['anonymous']) ? (bool)$_POST['anonymous'] : false;
    
    // 简单验证
    if (empty($topicId) || empty($content)) {
        jsonResponse([
            'success' => false,
            'message' => '话题ID和评论内容不能为空'
        ]);
    }
    
    // 创建评论对象
    $commentId = uniqid(); // 为评论生成唯一ID
    // 使用 topicId 作为 entityId 来组织评论图片到对应话题的文件夹下
    $uploadedImages = handleFileUploads('images', 'comment', $topicId); 

    $comment = [
        'id' => $commentId,
        // 'topicId' => $topicId, // 如果需要，可以存储topicId，但目前comments.json结构是以topicId为键
        'content' => $content,
        'images' => $uploadedImages, // 添加图片路径
        'author' => $anonymous ? '匿名用户' : getCurrentUser(),
        'timestamp' => time() * 1000, // JavaScript时间戳格式
        'likes' => 0
    ];
    
    // 读取所有评论
    $allComments = readJsonFile($commentsFile, []);
    
    // 如果没有当前话题的评论数组，创建一个
    if (!isset($allComments[$topicId])) {
        $allComments[$topicId] = [];
    }
    
    // 添加新评论到对应话题
    $allComments[$topicId][] = $comment;
    
    // 保存到文件
    $saveResult = saveJsonFile($commentsFile, $allComments);
    if ($saveResult) {
        // 更新话题评论数
        updateTopicCommentCount($topicId);
        
        jsonResponse([
            'success' => true,
            'message' => '评论发表成功',
            'comment' => $comment
        ]);
    } else {
        // 使用新的工具函数获取详细错误信息
        $errorMsg = getDetailedSaveError($commentsFile, '评论发表失败，无法保存数据');
        jsonResponse([
            'success' => false,
            'message' => $errorMsg
        ]);
    }
} 
// 获取话题的评论
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $topicId = isset($_GET['topicId']) ? $_GET['topicId'] : '';
    
    if (empty($topicId)) {
        jsonResponse([
            'success' => false,
            'message' => '话题ID不能为空'
        ]);
    }
    
    // 读取所有评论
    $allComments = readJsonFile($commentsFile, []);
    
    // 获取特定话题的评论，如果不存在则返回空数组
    $topicComments = isset($allComments[$topicId]) ? $allComments[$topicId] : [];
    
    jsonResponse([
        'success' => true,
        'comments' => $topicComments
    ]);
} else {
    jsonResponse([
        'success' => false,
        'message' => '请求方法不允许或参数错误'
    ]);
}

// 更新话题评论数
function updateTopicCommentCount($topicId) {
    $topicsFile = 'topics.json';
    
    $topics = readJsonFile($topicsFile);
    if (empty($topics)) {
        return;
    }
    
    foreach ($topics as &$topic) {
        if ($topic['id'] === $topicId) {
            $topic['comments']++;
            break;
        }
    }
    
    saveJsonFile($topicsFile, $topics);
}
?>
