<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// login.php
require_once 'core/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } elseif (!verifyCaptcha($_POST['captcha'] ?? '')) {
        $error = '图形验证码错误';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!validate_input($username, 'username')) {
            $error = '用户名格式不正确 (3-20位字母/数字/下划线)';
        } elseif (!validate_input($password, 'password')) {
            $error = '密码格式不正确 (6-30位字符)';
        } else {
            $login_res = login($username, $password);
            if ($login_res['status']) {
                redirect('user/dashboard.php');
            } else {
                $error = $login_res['msg'];
            }
        }
    }
}

if (isLoggedIn()) {
    if (isAdminLoggedIn()) {
        redirect(get_setting('admin_path', 'admin') . '/admin.php');
    } else {
        redirect('user/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>用户登录 - <?php echo get_setting('site_name'); ?></title>
    <!-- 高速 CDN 镜像 -->
    <link href="https://cdn.staticfile.org/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.staticfile.org/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-gradient: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-card { width: 100%; max-width: 420px; padding: 2.5rem; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); background: white; margin: 15px; }
        .brand-logo { width: 64px; height: 64px; background: var(--primary-gradient); color: white; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 1.5rem; box-shadow: 0 8px 15px rgba(59, 130, 246, 0.3); }
        .form-control { padding: 0.75rem 1.2rem; border-radius: 12px; border: 1px solid #e2e8f0; background-color: #fbfcfe; }
        .form-control:focus { background-color: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .btn-primary { padding: 0.8rem; border-radius: 12px; font-weight: 700; background: var(--primary-gradient); border: none; transition: all 0.2s; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center">
            <div class="brand-logo">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h4 class="fw-bold mb-1">欢迎回来</h4>
            <p class="text-muted small mb-4">登录您的账号以继续</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small py-2 rounded-3 mb-4">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">用户名</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" placeholder="请输入用户名" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">登录密码</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="请输入密码" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">图形验证码</label>
                <div class="input-group">
                    <input type="text" name="captcha" class="form-control" placeholder="请输入验证码" required>
                    <span class="input-group-text p-0 border-0 ms-2 bg-transparent">
                        <img src="core/captcha.php" alt="captcha" onclick="this.src='core/captcha.php?'+Math.random()" style="cursor:pointer; height:45px; border-radius:10px;">
                    </span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">立即登录</button>
            
            <div class="text-center mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="forgot_password.php" class="small text-muted text-decoration-none">忘记密码？</a>
                    <p class="small text-muted mb-0">还没有账号？ <a href="register.php" class="text-primary fw-bold text-decoration-none">立即注册</a></p>
                </div>
                <a href="index.php" class="text-muted smaller text-decoration-none"><i class="fas fa-arrow-left me-1"></i> 返回首页</a>
            </div>
        </form>
    </div>
</body>
</html>
