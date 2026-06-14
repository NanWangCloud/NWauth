<?php
/**
 * 管理员密码紧急重置工具
 * 使用说明：访问此文件即可将 admin 账号的密码重置为 123456
 * 安全提示：重置成功后请立即删除此文件！
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';

global $pdo;

$new_password_plain = '123456';
$new_password_hash = password_hash($new_password_plain, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE username = 'admin' OR id = 1 LIMIT 1");
    $stmt->execute([$new_password_hash]);
    
    if ($stmt->rowCount() > 0) {
        $success = "管理员密码已成功重置！<br>新账号：admin<br>新密码：{$new_password_plain}<br><br><b style='color:red;'>安全警告：请务必立即从服务器上删除此文件 (admin/emergency_reset.php)！</b>";
    } else {
        $error = "重置失败：未找到管理员账号，或者密码已经是 123456。";
    }
} catch (Exception $e) {
    $error = "系统错误：" . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>管理员密码重置</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; background: #f4f7f6; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 400px; text-align: center; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .btn { display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>密码重置工具</h2>
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <a href="login.php" class="btn">前往登录</a>
        <?php else: ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
