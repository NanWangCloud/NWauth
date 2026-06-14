<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireAdmin();

$success = '';
$error = '';
global $pdo;
$admin_id = $_SESSION['admin_id'];

// 获取当前管理员信息
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($email !== '' && !validate_input($email, 'email')) {
            $error = '邮箱格式不正确';
        } elseif ($new_password !== '' && !validate_input($new_password, 'password')) {
            $error = '密码格式不正确 (6-30位)';
        } elseif ($new_password !== '' && $new_password !== $confirm_password) {
            $error = '两次输入的密码不一致';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 更新昵称和邮箱
                $stmt = $pdo->prepare("UPDATE admins SET nickname = ?, email = ? WHERE id = ?");
                $stmt->execute([$nickname, $email, $admin_id]);
                
                // 如果填写了新密码，则更新密码
                if ($new_password !== '') {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $admin_id]);
                }
                
                $pdo->commit();
                $success = '资料更新成功！';
                
                // 刷新本地管理员信息
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
                $stmt->execute([$admin_id]);
                $admin = $stmt->fetch();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = '更新失败: ' . $e->getMessage();
            }
        }
    }
}

$page_title = '管理员资料修改';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0">修改管理员资料</h5>
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
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">用户名 (不可修改)</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($admin['username']); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">显示名字 (昵称)</label>
                        <input type="text" name="nickname" class="form-control" value="<?php echo htmlspecialchars($admin['nickname'] ?? ''); ?>" placeholder="设置一个好听的名字">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">电子邮箱</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>" placeholder="用于接收通知或管理">
                    </div>

                    <hr class="my-4">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">新密码 (不改请留空)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="6-30位新密码">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">确认新密码</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="重复输入新密码">
                    </div>

                    <div class="text-end pt-3">
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
render_layout($content, $page_title, 'admin');
?>
