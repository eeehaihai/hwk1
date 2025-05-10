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
    sessionStorage.removeItem('login');
    window.location.href = 'home.html';
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
function getAllPosts() {
    const posts = [];
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key.startsWith('post_')) {
            try {
                const post = JSON.parse(localStorage.getItem(key));
                post.id = key.replace('post_', '');
                posts.push(post);
            } catch (e) {
                console.error('解析帖子数据出错:', e);
            }
        }
    }
    // 按时间倒序排列
    return posts.sort((a, b) => b.timestamp - a.timestamp);
}

// 更新导航栏用户状态
function updateNavUserStatus() {
    const userActions = document.querySelector('.user-actions');
    if (!userActions) return;
    
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
