<?php
// 开启会话
session_start();

// 设置响应类型为JSON
header('Content-Type: application/json');

// 评论数据文件前缀
$commentsFilePrefix = 'comments_';

// 发表评论
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查用户是否登录
    if (!isset($_SESSION['login'])) {
        echo json_encode([
            'success' => false,
            'message' => '请先登录'
        ]);
        exit;
    }
    
    // 获取评论数据
    $topicId = isset($_POST['topicId']) ? $_POST['topicId'] : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $anonymous = isset($_POST['anonymous']) ? (bool)$_POST['anonymous'] : false;
    
    // 简单验证
    if (empty($topicId) || empty($content)) {
        echo json_encode([
            'success' => false,
            'message' => '话题ID和评论内容不能为空'
        ]);
        exit;
    }
    
    // 创建评论对象
    $comment = [
        'id' => uniqid(),
        'content' => $content,
        'author' => $anonymous ? '匿名用户' : $_SESSION['login'],
        'timestamp' => time() * 1000, // JavaScript时间戳格式
        'likes' => 0
    ];
    
    // 评论文件名
    $commentsFile = $commentsFilePrefix . $topicId . '.json';
    
    // 读取评论文件
    $comments = [];
    if (file_exists($commentsFile)) {
        $commentsData = file_get_contents($commentsFile);
        $comments = json_decode($commentsData, true);
        if (!is_array($comments)) {
            $comments = [];
        }
    }
    
    // 添加新评论
    $comments[] = $comment;
    
    // 保存到文件
    if (file_put_contents($commentsFile, json_encode($comments, JSON_PRETTY_PRINT))) {
        // 更新话题评论数
        updateTopicCommentCount($topicId);
        
        echo json_encode([
            'success' => true,
            'message' => '评论发表成功',
            'comment' => $comment
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '评论发表失败，无法保存数据'
        ]);
    }
} 
// 获取话题的评论
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $topicId = isset($_GET['topicId']) ? $_GET['topicId'] : '';
    
    if (empty($topicId)) {
        echo json_encode([
            'success' => false,
            'message' => '话题ID不能为空'
        ]);
        exit;
    }
    
    // 评论文件名
    $commentsFile = $commentsFilePrefix . $topicId . '.json';
    
    // 读取评论文件
    if (file_exists($commentsFile)) {
        $commentsData = file_get_contents($commentsFile);
        $comments = json_decode($commentsData, true);
        if (!is_array($comments)) {
            $comments = [];
        }
        
        echo json_encode([
            'success' => true,
            'comments' => $comments
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'comments' => []
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许或参数错误'
    ]);
}

// 更新话题评论数
function updateTopicCommentCount($topicId) {
    $topicsFile = 'topics.json';
    
    if (file_exists($topicsFile)) {
        $topicsData = file_get_contents($topicsFile);
        $topics = json_decode($topicsData, true);
        if (!is_array($topics)) {
            return;
        }
        
        foreach ($topics as &$topic) {
            if ($topic['id'] === $topicId) {
                $topic['comments']++;
                break;
            }
        }
        
        file_put_contents($topicsFile, json_encode($topics, JSON_PRETTY_PRINT));
    }
}
?>
