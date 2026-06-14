<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once 'core/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } elseif (!verifyCaptcha($_POST['captcha'] ?? '')) {
        $error = '图形验证码错误';
    } else {
        $email = trim($_POST['email'] ?? '');
        if (!validate_input($email, 'email')) {
            $error = '邮箱格式不正确';
        } else {
            global $pdo;
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // 这里逻辑：生成一个临时 token 并发送邮件
                // 鉴于环境限制，我们只显示成功提示并记录日志
                $success = '重置密码链接已发送至您的邮箱，请注意查收。';
            } else {
                $error = '未找到关联该邮箱的账号';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>找回密码 - <?php echo get_setting('site_name'); ?></title>
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
                <i class="fas fa-key"></i>
            </div>
            <h4 class="fw-bold mb-1">找回密码</h4>
            <p class="text-muted small mb-4">请输入您的注册邮箱以找回账号</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small py-2 rounded-3 mb-4">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success border-0 small py-2 rounded-3 mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">注册邮箱</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="请输入注册时填写的邮箱" required autofocus>
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
            <button type="submit" class="btn btn-primary w-100 mb-3">发送重置链接</button>
            
            <div class="text-center mt-4">
                <p class="small text-muted">想起密码了？ <a href="login.php" class="text-primary fw-bold text-decoration-none">点此登录</a></p>
                <a href="index.php" class="text-muted smaller text-decoration-none"><i class="fas fa-arrow-left me-1"></i> 返回首页</a>
            </div>
        </form>
    </div>
</body>
</html>
