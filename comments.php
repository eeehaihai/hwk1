<?php
// 开启会话
session_start();

// 引入工具函数
require_once 'utils.php';

// 评论数据文件前缀
$commentsFilePrefix = 'comments_';

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
    $comment = [
        'id' => uniqid(),
        'content' => $content,
        'author' => $anonymous ? '匿名用户' : getCurrentUser(),
        'timestamp' => time() * 1000, // JavaScript时间戳格式
        'likes' => 0
    ];
    
    // 评论文件名
    $commentsFile = $commentsFilePrefix . $topicId . '.json';
    
    // 读取评论文件
    $comments = readJsonFile($commentsFile, []);
    
    // 添加新评论
    $comments[] = $comment;
    
    // 保存到文件
    $saveResult = saveJsonFile($commentsFile, $comments);
    if ($saveResult) {
        // 更新话题评论数
        updateTopicCommentCount($topicId);
        
        jsonResponse([
            'success' => true,
            'message' => '评论发表成功',
            'comment' => $comment
        ]);
    } else {
        // 获取更详细的错误信息
        $errorMsg = '评论发表失败，无法保存数据';
        
        // 检查文件和目录权限
        if (!is_writable(dirname($commentsFile))) {
            $errorMsg .= "（目录无写入权限: " . dirname($commentsFile) . "）";
        } elseif (file_exists($commentsFile) && !is_writable($commentsFile)) {
            $errorMsg .= "（文件无写入权限: $commentsFile）";
        }
        
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
    
    // 评论文件名
    $commentsFile = $commentsFilePrefix . $topicId . '.json';
    
    // 读取评论文件
    $comments = readJsonFile($commentsFile, []);
    
    jsonResponse([
        'success' => true,
        'comments' => $comments
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
