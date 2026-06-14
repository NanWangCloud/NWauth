<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireAdmin();

global $pdo;
$type = $_GET['type'] ?? 'all';
$where = "";
$params = [];

if ($type !== 'all') {
    $where = "WHERE type = ?";
    $params[] = $type;
}

$stmt = $pdo->prepare("
    SELECT f.*, u.username 
    FROM finance_logs f 
    JOIN users u ON f.user_id = u.id 
    $where 
    ORDER BY f.created_at DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$page_title = '财务明细';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">财务流水记录</h4>
    <div class="btn-group">
        <a href="?type=all" class="btn btn-sm <?php echo $type == 'all' ? 'btn-primary' : 'btn-light border'; ?>">全部</a>
        <a href="?type=recharge" class="btn btn-sm <?php echo $type == 'recharge' ? 'btn-primary' : 'btn-light border'; ?>">充值</a>
        <a href="?type=consume" class="btn btn-sm <?php echo $type == 'consume' ? 'btn-primary' : 'btn-light border'; ?>">消费</a>
        <a href="?type=commission" class="btn btn-sm <?php echo $type == 'commission' ? 'btn-primary' : 'btn-light border'; ?>">佣金</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr>
                        <th>用户</th>
                        <th>类型</th>
                        <th>变动金额</th>
                        <th>变动后余额</th>
                        <th>备注</th>
                        <th>时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($log['username']); ?></strong></td>
                            <td>
                                <?php
                                $badges = [
                                    'recharge' => ['bg-success', '充值'],
                                    'consume' => ['bg-danger', '消费'],
                                    'commission' => ['bg-warning text-dark', '佣金'],
                                    'refund' => ['bg-info', '退款']
                                ];
                                $b = $badges[$log['type']];
                                ?>
                                <span class="badge <?php echo $b[0]; ?> bg-opacity-10 <?php echo str_contains($b[0], 'text-dark') ? 'text-dark' : str_replace('bg-', 'text-', $b[0]); ?>">
                                    <?php echo $b[1]; ?>
                                </span>
                            </td>
                            <td class="fw-bold <?php echo in_array($log['type'], ['recharge', 'commission']) ? 'text-success' : 'text-danger'; ?>">
                                <?php echo in_array($log['type'], ['recharge', 'commission']) ? '+' : '-'; ?>
                                ¥<?php echo number_format($log['amount'], 2); ?>
                            </td>
                            <td class="text-muted">¥<?php echo number_format($log['balance_after'], 2); ?></td>
                            <td class="small"><?php echo htmlspecialchars($log['remark']); ?></td>
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
