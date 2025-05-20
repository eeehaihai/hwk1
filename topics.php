<?php
// 开启会话
session_start();

// 设置响应类型为JSON
header('Content-Type: application/json');

// 引入工具函数
require_once 'utils.php';

// 话题数据文件
$topicsFile = 'topics.json';

// 创建话题
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查用户是否登录
    if (!isUserLoggedIn()) {
        jsonResponse([
            'success' => false,
            'message' => '请先登录'
        ]);
    }
    
    // 获取话题数据
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
    $anonymous = isset($_POST['anonymous']) ? (bool)$_POST['anonymous'] : false;
    $top = isset($_POST['top']) ? (bool)$_POST['top'] : false;
    $disableComment = isset($_POST['disable_comment']) ? (bool)$_POST['disable_comment'] : false;
    
    // 简单验证
    if (empty($title) || empty($category) || empty($content)) {
        jsonResponse([
            'success' => false,
            'message' => '标题、分类和内容不能为空'
        ]);
    }
    
    // 处理标签
    $cleanTags = [];
    foreach ($tags as $tag) {
        $tag = trim($tag);
        if (!empty($tag)) {
            $cleanTags[] = $tag;
        }
    }
    
    // 创建话题对象
    $topicId = uniqid(); // 首先生成ID，以便用于图片存储路径
    $uploadedImages = handleFileUploads('images', 'topic', $topicId);

    $topic = [
        'id' => $topicId,
        'title' => $title,
        'category' => $category,
        'categoryName' => getCategoryName($category),
        'content' => $content,
        'tags' => $cleanTags,
        'images' => $uploadedImages, // 添加图片路径
        'anonymous' => $anonymous,
        'top' => $top,
        'disableComment' => $disableComment,
        'author' => $anonymous ? '匿名用户' : getCurrentUser(),
        'timestamp' => time() * 1000, // JavaScript时间戳格式
        'views' => 0,
        'likes' => 0,
        'comments' => 0
    ];
    
    // 读取话题文件
    $topics = readJsonFile($topicsFile, []);
    
    // 添加新话题
    $topics[] = $topic;
    
    // 保存到文件
    $result = saveJsonFile($topicsFile, $topics);
    if ($result !== false) {
        jsonResponse([
            'success' => true,
            'message' => '话题发布成功',
            'topicId' => $topic['id']
        ]);
    } else {
        // 检查文件权限
        $errorMsg = '话题发布失败，无法保存数据';
        if (!is_writable($topicsFile) && file_exists($topicsFile)) {
            $errorMsg .= '（文件无写入权限）';
        } else if (!is_writable(dirname($topicsFile))) {
            $errorMsg .= '（目录无写入权限）';
        }
        
        jsonResponse([
            'success' => false,
            'message' => $errorMsg
        ]);
    }
} 
// 获取话题列表
else if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id'])) {
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'newest';
    
    // 读取话题文件
    $topics = readJsonFile($topicsFile, []);
    
    // 按分类筛选
    if ($category !== 'all') {
        $filteredTopics = [];
        foreach ($topics as $topic) {
            if ($topic['category'] === $category) {
                $filteredTopics[] = $topic;
            }
        }
        $topics = $filteredTopics;
    }
    
    // 按要求排序
    if ($sortBy === 'newest') {
        // 按发布时间降序排序
        usort($topics, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
    } else if ($sortBy === 'hottest') {
        // 计算一周内的热度
        $oneWeekAgo = (time() - 7 * 24 * 60 * 60) * 1000; // 一周前的JavaScript时间戳
        
        // 计算热度分数 - 基于一周内的评论数+点赞数
        foreach ($topics as &$topic) {
            if ($topic['timestamp'] >= $oneWeekAgo) {
                // 一周内的帖子，热度 = 评论数 + 点赞数
                $topic['hotScore'] = $topic['comments'] + $topic['likes'];
            } else {
                // 超过一周的帖子热度为0
                $topic['hotScore'] = 0;
            }
        }
        
        // 按热度分数降序排序
        usort($topics, function($a, $b) {
            return $b['hotScore'] - $a['hotScore'];
        });
        
        // 移除临时字段
        foreach ($topics as &$topic) {
            unset($topic['hotScore']);
        }
    } else if ($sortBy === 'featured') {
        $filteredTopics = [];
        foreach ($topics as $topic) {
            if (isset($topic['top']) && $topic['top']) {
                $filteredTopics[] = $topic;
            }
        }
        $topics = $filteredTopics;
    }
    
    jsonResponse([
        'success' => true,
        'topics' => $topics
    ]);
}
// 获取单个话题详情
else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $topicId = $_GET['id'];
    
    // 读取话题文件
    $topics = readJsonFile($topicsFile);
    if (empty($topics)) {
        jsonResponse([
            'success' => false,
            'message' => '话题数据文件不存在或为空'
        ]);
    }
    
    // 查找指定话题
    $topic = null;
    foreach ($topics as &$t) {
        if ($t['id'] === $topicId) {
            // 增加浏览量
            $t['views']++;
            $topic = $t;
            break;
        }
    }
    
    // 保存更新后的话题列表（浏览量增加）
    if ($topic) {
        saveJsonFile($topicsFile, $topics);
        
        jsonResponse([
            'success' => true,
            'topic' => $topic
        ]);
    } else {
        jsonResponse([
            'success' => false,
            'message' => '话题不存在'
        ]);
    }
} else {
    jsonResponse([
        'success' => false,
        'message' => '请求方法不允许或参数错误'
    ]);
}
?>
