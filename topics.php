<?php
// 开启会话
session_start();

// 设置响应类型为JSON
header('Content-Type: application/json');

// 话题数据文件
$topicsFile = 'topics.json';

// 创建话题
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查用户是否登录
    if (!isset($_SESSION['login'])) {
        echo json_encode([
            'success' => false,
            'message' => '请先登录'
        ]);
        exit;
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
        echo json_encode([
            'success' => false,
            'message' => '标题、分类和内容不能为空'
        ]);
        exit;
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
    $topic = [
        'id' => uniqid(),
        'title' => $title,
        'category' => $category,
        'categoryName' => getCategoryName($category),
        'content' => $content,
        'tags' => $cleanTags,
        'anonymous' => $anonymous,
        'top' => $top,
        'disableComment' => $disableComment,
        'author' => $anonymous ? '匿名用户' : $_SESSION['login'],
        'timestamp' => time() * 1000, // JavaScript时间戳格式
        'views' => 0,
        'likes' => 0,
        'comments' => 0
    ];
    
    // 读取话题文件
    $topics = [];
    if (file_exists($topicsFile)) {
        $topicsData = file_get_contents($topicsFile);
        if ($topicsData !== false) {
            $topics = json_decode($topicsData, true);
            if (!is_array($topics)) {
                $topics = [];
            }
        }
    }
    
    // 添加新话题
    $topics[] = $topic;
    
    // 确保目录存在且有写入权限
    $directory = dirname($topicsFile);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // 保存到文件
    $jsonData = json_encode($topics, JSON_PRETTY_PRINT);
    if ($jsonData === false) {
        echo json_encode([
            'success' => false,
            'message' => '话题发布失败，JSON编码错误'
        ]);
        exit;
    }
    
    $result = file_put_contents($topicsFile, $jsonData);
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => '话题发布成功',
            'topicId' => $topic['id']
        ]);
    } else {
        // 检查文件权限
        $errorMsg = '话题发布失败，无法保存数据';
        if (!is_writable($topicsFile) && file_exists($topicsFile)) {
            $errorMsg .= '（文件无写入权限）';
        } else if (!is_writable($directory)) {
            $errorMsg .= '（目录无写入权限）';
        }
        
        echo json_encode([
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
    if (file_exists($topicsFile)) {
        $topicsData = file_get_contents($topicsFile);
        $topics = json_decode($topicsData, true);
        if (!is_array($topics)) {
            $topics = [];
        }
    } else {
        $topics = [];
    }
    
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
        // 计算一周内的点赞数并排序
        $oneWeekAgo = (time() - 7 * 24 * 60 * 60) * 1000; // 一周前的JavaScript时间戳
        
        // 计算热度分数 - 对于一周内发布的帖子，其点赞数权重更高
        foreach ($topics as &$topic) {
            // 如果是一周内发布的帖子，其点赞数权重为1.5倍
            if ($topic['timestamp'] >= $oneWeekAgo) {
                $topic['hotScore'] = $topic['likes'] * 1.5;
            } else {
                $topic['hotScore'] = $topic['likes'];
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
    
    echo json_encode([
        'success' => true,
        'topics' => $topics
    ]);
}
// 获取单个话题详情
else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $topicId = $_GET['id'];
    
    // 读取话题文件
    if (file_exists($topicsFile)) {
        $topicsData = file_get_contents($topicsFile);
        $topics = json_decode($topicsData, true);
        if (!is_array($topics)) {
            $topics = [];
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
            file_put_contents($topicsFile, json_encode($topics, JSON_PRETTY_PRINT));
            
            echo json_encode([
                'success' => true,
                'topic' => $topic
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => '话题不存在'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => '话题数据文件不存在'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许或参数错误'
    ]);
}

// 辅助函数：根据分类代码获取分类名称
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
?>
