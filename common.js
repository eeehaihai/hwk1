// 检查用户是否已登录
function isLoggedIn() {
    return sessionStorage.getItem('login') !== null;
}

// 获取当前登录的用户名
function getCurrentUser() {
    return sessionStorage.getItem('login');
}

// 退出登录
function logout() {
    // 发送请求到服务器删除会话
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'logout.php', true);
    
    xhr.onload = function() {
        // 无论服务器响应如何，都清除本地会话存储
        sessionStorage.removeItem('login');
        window.location.href = 'home.html';
    };
    
    xhr.send();
}

// 获取所有帖子
function getAllPosts(callback) {
    sendAjaxRequest('GET', 'topics.php', null, function(response) {
        if (response && response.success) {
            callback(response.topics);
        } else {
            console.error('获取帖子失败:', response ? response.message : '未知错误');
            callback([]);
        }
    }, function() {
        callback([]);
    });
}

// 发送AJAX请求的通用函数
function sendAjaxRequest(method, url, data, successCallback, errorCallback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    
    if (method === 'POST' && !(data instanceof FormData)) {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                successCallback(response);
            } catch (e) {
                console.error('解析响应数据出错:', e);
                if (errorCallback) errorCallback();
            }
        } else {
            console.error('HTTP错误:', xhr.status);
            if (errorCallback) errorCallback();
        }
    };
    
    xhr.onerror = function() {
        console.error('网络错误');
        if (errorCallback) errorCallback();
    };
    
    if (data instanceof FormData) {
        xhr.send(data);
    } else if (data) {
        // 将对象转换为URL编码格式
        let params = Object.keys(data).map(key => 
            encodeURIComponent(key) + '=' + encodeURIComponent(data[key])
        ).join('&');
        xhr.send(params);
    } else {
        xhr.send();
    }
}

// 更新导航栏用户状态
function updateNavUserStatus() {
    const userActions = document.querySelector('.user-actions');
    if (!userActions) return;
    
    // 发送请求到服务器检查会话状态
    sendAjaxRequest('GET', 'check_session.php', null, function(response) {
        if (response.loggedIn) {
            // 已登录状态
            sessionStorage.setItem('login', response.username);
            userActions.innerHTML = `
                <span class="welcome-text">欢迎, ${response.username}</span>
                <a href="#" onclick="logout()" class="logout-btn">退出登录</a>
            `;
        } else {
            // 未登录状态
            sessionStorage.removeItem('login');
            userActions.innerHTML = `
                <a href="login.html" class="login-btn">登录</a>
                <a href="register.html" class="register-btn">注册</a>
            `;
        }
    }, function() {
        // 回退到本地存储检查
        if (isLoggedIn()) {
            userActions.innerHTML = `
                <span class="welcome-text">欢迎, ${getCurrentUser()}</span>
                <a href="#" onclick="logout()" class="logout-btn">退出登录</a>
            `;
        } else {
            userActions.innerHTML = `
                <a href="login.html" class="login-btn">登录</a>
                <a href="register.html" class="register-btn">注册</a>
            `;
        }
    });
}

// 检查登录状态，如果需要登录但未登录，则重定向到登录页面
function checkLoginRequired() {
    if (!isLoggedIn()) {
        alert('请先登录');
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

// 格式化时间戳为可读形式
function formatTime(timestamp) {
    const date = new Date(timestamp);
    return `${date.getFullYear()}-${padZero(date.getMonth() + 1)}-${padZero(date.getDate())} ${padZero(date.getHours())}:${padZero(date.getMinutes())}`;
}

// 数字补零
function padZero(num) {
    return num < 10 ? '0' + num : num;
}

// 安全处理HTML内容
function safeHtml(content) {
    return content
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/\n/g, '<br>');
}

// 截断长文本
function truncateText(text, maxLength = 200) {
    if (text.length <= maxLength) return text;
    
    // 查找接近最大长度的最后一个空格
    const lastSpace = text.substring(0, maxLength + 50).lastIndexOf(' ');
    const cutPoint = lastSpace > maxLength * 0.9 ? lastSpace : maxLength;
    
    return text.substring(0, cutPoint) + '...';
}
