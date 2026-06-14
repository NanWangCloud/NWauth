<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireAdmin();

global $pdo;
$stmt = $pdo->query("
    SELECT l.*, a.username as admin_name 
    FROM admin_logs l 
    JOIN admins a ON l.admin_id = a.id 
    ORDER BY l.created_at DESC 
    LIMIT 100
");
$logs = $stmt->fetchAll();

$page_title = '操作日志';
ob_start();
?>

<h4 class="fw-bold mb-4">管理员操作审计</h4>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr>
                        <th>管理员</th>
                        <th>操作行为</th>
                        <th>操作对象</th>
                        <th>IP 地址</th>
                        <th>时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><span class="badge bg-dark"><?php echo htmlspecialchars($log['admin_name']); ?></span></td>
                            <td><span class="text-primary fw-medium"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td><code class="small text-muted"><?php echo htmlspecialchars($log['target'] ?: '-'); ?></code></td>
                            <td class="small text-muted"><?php echo $log['ip']; ?></td>
                            <td class="text-muted small"><?php echo $log['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'admin');
?>
