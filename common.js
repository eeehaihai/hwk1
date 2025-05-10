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

// 保存新帖子到localStorage
function savePost(post) {
    const id = generateUniqueId();
    const key = `post_${id}`;
    post.timestamp = Date.now();
    post.author = getCurrentUser();
    localStorage.setItem(key, JSON.stringify(post));
    return id;
}

// 生成唯一ID
function generateUniqueId() {
    return Date.now().toString() + Math.floor(Math.random() * 1000).toString();
}

// 获取所有帖子
function getAllPosts(callback) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'topics.php', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    callback(response.topics);
                } else {
                    console.error('获取帖子失败:', response.message);
                    callback([]);
                }
            } catch (e) {
                console.error('解析帖子数据出错:', e);
                callback([]);
            }
        } else {
            console.error('HTTP错误:', xhr.status);
            callback([]);
        }
    };
    
    xhr.onerror = function() {
        console.error('网络错误');
        callback([]);
    };
    
    xhr.send();
}

// 更新导航栏用户状态
function updateNavUserStatus() {
    const userActions = document.querySelector('.user-actions');
    if (!userActions) return;
    
    // 发送请求到服务器检查会话状态
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'check_session.php', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                
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
            } catch (e) {
                console.error('解析会话数据出错:', e);
                // 回退到本地存储检查
                fallbackToLocalSession();
            }
        } else {
            console.error('HTTP错误:', xhr.status);
            // 回退到本地存储检查
            fallbackToLocalSession();
        }
    };
    
    xhr.onerror = function() {
        console.error('网络错误');
        // 回退到本地存储检查
        fallbackToLocalSession();
    };
    
    xhr.send();
    
    // 回退函数：使用本地存储检查登录状态
    function fallbackToLocalSession() {
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
    }
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

function padZero(num) {
    return num < 10 ? '0' + num : num;
}
