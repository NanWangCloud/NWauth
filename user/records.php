<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// user/records.php
require_once '../core/auth.php';
requireLogin();

global $pdo;
$user_id = $_SESSION['user_id'];

// 获取购买记录
$stmt = $pdo->prepare("
    SELECT pl.*, s.name as software_name 
    FROM purchase_logs pl 
    JOIN software s ON pl.software_id = s.id 
    WHERE pl.user_id = ? 
    ORDER BY pl.created_at DESC
");
$stmt->execute([$user_id]);
$purchase_logs = $stmt->fetchAll();

// 获取兑换记录 (从 cards 表)
$stmt = $pdo->prepare("
    SELECT c.*, s.name as software_name 
    FROM cards c 
    LEFT JOIN software s ON c.software_id = s.id 
    WHERE c.used_by = ? 
    ORDER BY c.used_at DESC
");
$stmt->execute([$user_id]);
$exchange_logs = $stmt->fetchAll();

$page_title = '消费记录 - 授权管理系统';

ob_start();
?>

<div class="row">
    <!-- 购买记录 -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-shopping-bag me-2"></i>购买授权记录</h5>
                <span class="badge bg-primary rounded-pill"><?php echo count($purchase_logs); ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>软件名称</th>
                                <th>时长</th>
                                <th>金额</th>
                                <th>日期</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchase_logs)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">暂无购买记录</td></tr>
                            <?php else: ?>
                                <?php foreach ($purchase_logs as $log): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($log['software_name']); ?></strong></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $log['months']; ?> 个月</span></td>
                                        <td class="text-danger fw-bold">¥<?php echo number_format($log['amount'], 2); ?></td>
                                        <td class="text-muted small"><?php echo date('m-d H:i', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 兑换记录 -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-success"><i class="fas fa-ticket-alt me-2"></i>卡密兑换记录</h5>
                <span class="badge bg-success rounded-pill"><?php echo count($exchange_logs); ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>卡密类型</th>
                                <th>面值</th>
                                <th>日期</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($exchange_logs)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">暂无兑换记录</td></tr>
                            <?php else: ?>
                                <?php foreach ($exchange_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <span class="small">
                                                <?php if ($log['type'] === 'balance'): ?>
                                                    <i class="fas fa-wallet text-warning me-1"></i> 余额充值
                                                <?php else: ?>
                                                    <i class="fas fa-cube text-primary me-1"></i> <?php echo htmlspecialchars($log['software_name'] ?? '未知软件'); ?>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo (float)$log['value']; ?> <?php echo $log['type'] === 'balance' ? '元' : '天'; ?></strong></td>
                                        <td class="text-muted small"><?php echo date('m-d H:i', strtotime($log['used_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'user');

/**
 * 作者：任意
 * qq：2908286914
 */
?>