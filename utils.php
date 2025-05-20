<?php
// 通用PHP函数库

/**
 * 获取分类名称
 */
function getCategoryName($category) {
    $categoryNames = [
        'campus_life' => '校园生活',
        'study' => '学习交流',
        'secondhand' => '二手交易',
        'lost_found' => '失物招领'
    ];
    
    return isset($categoryNames[$category]) ? $categoryNames[$category] : '其他';
}

/**
 * 获取分类列表
 */
function getCategoryList() {
    return [
        'campus_life' => '校园生活',
        'study' => '学习交流',
        'secondhand' => '二手交易',
        'lost_found' => '失物招领'
    ];
}

/**
 * 从文件中读取JSON数据
 */
function readJsonFile($filePath, $defaultValue = []) {
    if (file_exists($filePath)) {
        $data = file_get_contents($filePath);
        if ($data !== false) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    }
    return $defaultValue;
}

/**
 * 将数据保存为JSON文件
 */
function saveJsonFile($filePath, $data) {
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); // 添加 JSON_UNESCAPED_UNICODE
    if ($jsonData === false) {
        error_log("JSON编码失败: " . json_last_error_msg());
        return false;
    }
    
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0755, true)) {
            error_log("无法创建目录: $directory");
            return false;
        }
    }
    
    // 检查目录是否可写
    if (!is_writable($directory)) {
        error_log("目录不可写: $directory");
        chmod($directory, 0755); // 尝试修改权限
    }
    
    // 检查文件是否存在且不可写
    if (file_exists($filePath) && !is_writable($filePath)) {
        error_log("文件存在但不可写: $filePath");
        chmod($filePath, 0644); // 尝试修改权限
    }
    
    $result = file_put_contents($filePath, $jsonData);
    if ($result === false) {
        error_log("写入文件失败: $filePath");
    }
    return $result !== false;
}

/**
 * 返回JSON响应
 */
function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * 检查用户是否已登录
 */
function isUserLoggedIn() {
    return isset($_SESSION['login']);
}

/**
 * 获取当前登录用户名
 */
function getCurrentUser() {
    return $_SESSION['login'] ?? null;
}

/**
 * 检查用户是否有权限操作话题
 */
function userCanModifyTopic($topic, $username) {
    return $username === 'admin' || $topic['author'] === $username;
}

/**
 * 查找话题
 */
function findTopic($topicId, &$topics) {
    foreach ($topics as &$topic) {
        if ($topic['id'] === $topicId) {
            return [true, &$topic];
        }
    }
    return [false, null];
}

/**
 * 处理文件上传
 * @param string $fileInputName 表单中文件输入的名称 (e.g., 'images')
 * @param string $type 'topic' 或 'comment'
 * @param string|null $entityId topicId (用于创建子目录)
 * @return array 上传成功的文件相对路径数组，失败则为空数组
 */
function handleFileUploads($fileInputName, $type, $entityId = null) {
    $uploadedFilesPaths = [];
    // 检查是否有文件上传
    if (isset($_FILES[$fileInputName]) && !empty($_FILES[$fileInputName]['name'][0])) {
        $baseUploadDir = 'uploads/'; // 基础上传目录
        $targetDir = $baseUploadDir;

        if ($type === 'topic') {
            $targetDir .= 'topics/';
            if ($entityId) {
                $targetDir .= $entityId . '/';
            }
        } elseif ($type === 'comment') {
            $targetDir .= 'comments/';
            if ($entityId) { // $entityId 在这里是 topicId
                $targetDir .= $entityId . '/';
            }
        } else {
            error_log("无效的文件上传类型: " . $type);
            return []; // 无效类型
        }

        // 创建目录（如果不存在）
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                error_log("无法创建上传目录: " . $targetDir);
                return []; // 返回空数组表示失败
            }
        }

        $files = $_FILES[$fileInputName];
        $numFiles = count($files['name']);

        for ($i = 0; $i < $numFiles; $i++) {
            // 检查是否有错误
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $files['name'][$i];
                $tmpName = $files['tmp_name'][$i];
                $fileType = $files['type'][$i];
                $fileSize = $files['size'][$i];

                // 简单文件类型检查
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($fileType, $allowedTypes)) {
                    error_log("不支持的文件类型: " . $fileType . " 文件名: " . $fileName);
                    continue; // 跳过此文件
                }

                // 文件大小检查 (例如 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    error_log("文件太大: " . $fileName . " 大小: " . $fileSize);
                    continue; // 跳过此文件
                }

                // 生成唯一文件名以避免覆盖
                $uniqueFileName = uniqid() . '_' . basename($fileName);
                $targetFilePath = $targetDir . $uniqueFileName;

                // 移动上传的文件
                if (move_uploaded_file($tmpName, $targetFilePath)) {
                    $uploadedFilesPaths[] = $targetFilePath; // 存储相对路径
                } else {
                    error_log("无法移动上传的文件: " . $fileName . " 到 " . $targetFilePath . ". PHP错误: " . error_get_last()['message']);
                }
            } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) { // 忽略没有文件被上传的错误
                error_log("文件上传错误: " . $files['name'][$i] . " - 错误代码: " . $files['error'][$i]);
            }
        }
    }
    return $uploadedFilesPaths;
}
?>
