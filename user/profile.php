<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireLogin();

$success = '';
$error = '';
global $pdo;
$user_id = $_SESSION['user_id'];

// 获取当前用户信息
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // 通知设置
        $notify_settings = [
            'login' => isset($_POST['notify_login']),
            'buy' => isset($_POST['notify_buy']),
            'mod' => isset($_POST['notify_mod']),
            'bind' => isset($_POST['notify_bind']),
            'agent_up' => isset($_POST['notify_agent_up']),
            'agent_gen' => isset($_POST['notify_agent_gen'])
        ];

        if ($email !== '' && !validate_input($email, 'email')) {
            $error = '邮箱格式不正确';
        } elseif ($new_password !== '' && !validate_input($new_password, 'password')) {
            $error = '密码格式不正确 (6-30位)';
        } elseif ($new_password !== '' && $new_password !== $confirm_password) {
            $error = '两次输入的密码不一致';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 更新昵称、邮箱和通知设置
                $stmt = $pdo->prepare("UPDATE users SET nickname = ?, email = ?, email_notify_settings = ? WHERE id = ?");
                $stmt->execute([$nickname, $email, json_encode($notify_settings), $user_id]);
                
                // 如果填写了新密码，则更新密码
                if ($new_password !== '') {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                }
                
                $pdo->commit();
                $success = '资料更新成功！';
                
                // 刷新本地用户信息
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = '更新失败: ' . $e->getMessage();
            }
        }
    }
}

// 获取当前用户通知设置
$user_notify_settings = json_decode($user['email_notify_settings'] ?? '{}', true);
$notifies = [
    'login' => '登录提醒',
    'buy' => '购买授权成功',
    'mod' => '资料修改提醒',
    'bind' => '授权绑定提醒',
    'agent_up' => '代理等级提升',
    'agent_gen' => '下级授权生成'
];
foreach ($notifies as $key => $name) {
    if (!isset($user_notify_settings[$key])) $user_notify_settings[$key] = true;
}

$page_title = '个人资料修改';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">修改我的资料</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success border-0 small mb-4">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 small mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">基本信息</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">用户名 (不可修改)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">显示名字 (昵称)</label>
                                <input type="text" name="nickname" class="form-control" value="<?php echo htmlspecialchars($user['nickname'] ?? ''); ?>" placeholder="设置一个好听的名字">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">电子邮箱</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="用于找回密码或接收通知">
                            </div>
                        </div>

                        <div class="col-md-6 border-start">
                            <h6 class="fw-bold text-primary mb-3">邮件通知设置</h6>
                            <div class="row">
                                <?php foreach ($notifies as $key => $name): ?>
                                <div class="col-12 mb-2">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_<?php echo $key; ?>" <?php echo $user_notify_settings[$key] ? 'checked' : ''; ?>>
                                        <label class="form-check-label small"><?php echo $name; ?></label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold text-primary mb-3">安全修改</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">新密码 (不改请留空)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="6-30位新密码">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">确认新密码</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="重复输入新密码">
                        </div>
                    </div>

                    <div class="text-end pt-4">
                        <button type="submit" class="btn btn-primary px-5 fw-bold rounded-pill">
                            <i class="fas fa-save me-2"></i> 保存修改
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'user');
?>
