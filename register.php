<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// register.php
require_once 'core/auth.php';

$error = '';

// 处理发送邮箱验证码 (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'send_code') {
    header('Content-Type: application/json');
    $email = trim($_GET['email'] ?? '');
    if (!validate_input($email, 'email')) {
        echo json_encode(['status' => false, 'msg' => '邮箱格式不正确']);
        exit;
    }
    
    // 检查频率 (60秒)
    if (isset($_SESSION['reg_email_time']) && (time() - $_SESSION['reg_email_time'] < 60)) {
        echo json_encode(['status' => false, 'msg' => '请 60 秒后再试']);
        exit;
    }

    if (send_email_code($email, 'reg')) {
        echo json_encode(['status' => true, 'msg' => '验证码已发送至您的邮箱']);
    } else {
        echo json_encode(['status' => false, 'msg' => '邮件发送失败，请检查后台 SMTP 配置']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } elseif (!verifyCaptcha($_POST['captcha'] ?? '')) {
        $error = '图形验证码错误';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $email_code = trim($_POST['email_code'] ?? '');

        // 邮箱验证逻辑
        $email_verify_enabled = get_setting('reg_email_verify', '0') === '1';
        $email_suffix = get_setting('reg_email_suffix', '');

        if (!validate_input($username, 'username')) {
            $error = '用户名格式不正确 (3-20位字母数字下划线)';
        } elseif (!validate_input($password, 'password')) {
            $error = '密码格式不正确 (6-30位)';
        } elseif ($password !== $confirm_password) {
            $error = '两次输入的密码不一致';
        } elseif (!validate_input($email, 'email')) {
            $error = '邮箱格式不正确';
        } elseif ($email_verify_enabled && (empty($_SESSION['reg_email_code']) || $email_code !== $_SESSION['reg_email_code'] || $email !== $_SESSION['reg_email_target'])) {
            $error = '邮箱验证码错误或邮箱不匹配';
        } else {
            // 检查邮箱后缀限制
            if (!empty($email_suffix)) {
                $allowed_suffixes = explode(',', $email_suffix);
                $is_suffix_allowed = false;
                foreach ($allowed_suffixes as $suffix) {
                    if (str_ends_with($email, trim($suffix))) {
                        $is_suffix_allowed = true;
                        break;
                    }
                }
                if (!$is_suffix_allowed) {
                    $error = '不支持该邮箱后缀，允许的后缀：' . $email_suffix;
                }
            }

            if (!$error) {
                if (register($username, $password, $email)) {
                    set_flash_message('success', '注册成功，请登录！');
                    redirect('login.php');
                } else {
                    $error = '注册失败，用户名或邮箱可能已存在';
                }
            }
        }
    }
}

if (isLoggedIn()) {
    redirect('user/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>账号注册 - <?php echo get_setting('site_name'); ?></title>
    <!-- 高速 CDN 镜像 -->
    <link href="https://cdn.staticfile.org/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.staticfile.org/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-gradient: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .login-card { width: 100%; max-width: 450px; padding: 2.5rem; border: none; border-radius: 20px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); background: white; margin: 15px; }
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
                <i class="fas fa-user-plus"></i>
            </div>
            <h4 class="fw-bold mb-1">加入我们</h4>
            <p class="text-muted small mb-4">创建您的正版授权账号</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small py-2 rounded-3 mb-4">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">设置用户名</label>
                <input type="text" name="username" class="form-control" placeholder="3-20位字母/数字/下划线" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">设置邮箱</label>
                <div class="input-group">
                    <input type="email" name="email" id="email" class="form-control" placeholder="请输入有效邮箱" required>
                    <?php if (get_setting('reg_email_verify', '0') === '1'): ?>
                    <button type="button" id="sendCodeBtn" class="btn btn-outline-primary small" onclick="sendEmailCode()">获取验证码</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (get_setting('reg_email_verify', '0') === '1'): ?>
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">邮箱验证码</label>
                <input type="text" name="email_code" class="form-control" placeholder="请输入 6 位邮箱验证码" required>
            </div>
            <?php endif; ?>
            <div class="row mb-3">
                <div class="col-6">
                    <label class="form-label small fw-bold text-muted">设置密码</label>
                    <input type="password" name="password" class="form-control" placeholder="6-30位" required>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-bold text-muted">确认密码</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="重复输入" required>
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
            <button type="submit" class="btn btn-primary w-100 mb-3">立即注册账号</button>
            
            <div class="text-center mt-4">
                <p class="small text-muted">已有账号？ <a href="login.php" class="text-primary fw-bold text-decoration-none">点此登录</a></p>
                <a href="index.php" class="text-muted smaller text-decoration-none"><i class="fas fa-arrow-left me-1"></i> 返回首页</a>
            </div>
        </form>
    </div>

    <script>
    function sendEmailCode() {
        const email = document.getElementById('email').value;
        const btn = document.getElementById('sendCodeBtn');
        if (!email) {
            alert('请先输入邮箱');
            return;
        }
        
        btn.disabled = true;
        btn.innerText = '发送中...';
        
        fetch(`register.php?action=send_code&email=${encodeURIComponent(email)}`)
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    let seconds = 60;
                    const timer = setInterval(() => {
                        seconds--;
                        if (seconds <= 0) {
                            clearInterval(timer);
                            btn.disabled = false;
                            btn.innerText = '获取验证码';
                        } else {
                            btn.innerText = `${seconds}s 后重发`;
                        }
                    }, 1000);
                    alert(data.msg);
                } else {
                    btn.disabled = false;
                    btn.innerText = '获取验证码';
                    alert(data.msg);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerText = '获取验证码';
                alert('网络请求失败');
            });
    }
    </script>
</body>
</html>
