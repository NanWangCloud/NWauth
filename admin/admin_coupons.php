<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireAdmin();

$error = '';
$success = '';

global $pdo;

// 处理生成优惠券
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $count = (int)($_POST['count'] ?? 1);
        $type = $_POST['type'] ?? 'amount';
        $value = (float)($_POST['value'] ?? 0);
        $min_amount = (float)($_POST['min_amount'] ?? 0);
        $expiry_days = (int)($_POST['expiry_days'] ?? 0);
        
        if ($value <= 0) {
            $error = '优惠金额/比例必须大于 0';
        } else {
            $pdo->beginTransaction();
            try {
                for ($i = 0; $i < $count; $i++) {
                    $code = 'CPN-' . strtoupper(bin2hex(random_bytes(4)));
                    $expires_at = $expiry_days > 0 ? date('Y-m-d H:i:s', strtotime("+$expiry_days days")) : null;
                    
                    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, value, min_amount, expires_at) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $type, $value, $min_amount, $expires_at]);
                }
                logAdminAction('批量生成优惠券', "数量: $count, 类型: $type, 面值: $value");
                $pdo->commit();
                $success = "成功生成 $count 张优惠券！";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = '生成失败: ' . $e->getMessage();
            }
        }
    }
}

// 处理删除
if (isset($_GET['delete'])) {
    if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([(int)$_GET['delete']]);
        $success = '优惠券已删除。';
    }
}

// 获取优惠券列表
$coupons = $pdo->query("SELECT c.*, u.username as used_by_name FROM coupons c LEFT JOIN users u ON c.used_by = u.id ORDER BY c.created_at DESC")->fetchAll();

$page_title = '优惠券管理';

ob_start();
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-plus-circle me-2"></i>批量生成</h5>
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
                    <input type="hidden" name="action" value="generate">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">优惠类型</label>
                        <select name="type" class="form-select">
                            <option value="amount">固定金额 (元)</option>
                            <option value="percent">折扣比例 (%)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">面值 (金额/百分比)</label>
                        <input type="number" name="value" step="0.01" class="form-control" placeholder="例如: 10" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">最低使用金额 (元)</label>
                        <input type="number" name="min_amount" step="0.01" class="form-control" value="0.00">
                        <div class="form-text smaller">满多少元可用，0 为不限制。</div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label small fw-bold">有效天数</label>
                            <input type="number" name="expiry_days" class="form-control" value="0">
                            <div class="form-text smaller">0 为永久有效。</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">生成数量</label>
                            <input type="number" name="count" class="form-control" value="1" min="1" max="100">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm">
                        <i class="fas fa-magic me-2"></i> 立即批量生成
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-list me-2"></i>券码列表</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>券码</th>
                                <th>优惠额度</th>
                                <th>门槛</th>
                                <th>状态</th>
                                <th>到期</th>
                                <th class="text-end">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">暂无优惠券记录</td></tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $c): ?>
                                    <tr>
                                        <td><code class="text-dark fw-bold"><?php echo $c['code']; ?></code></td>
                                        <td>
                                            <strong><?php echo $c['discount_type'] === 'amount' ? '¥'.$c['value'] : $c['value'].'%'; ?></strong>
                                        </td>
                                        <td class="small text-muted">¥<?php echo $c['min_amount']; ?></td>
                                        <td>
                                            <?php if ($c['is_used']): ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2 py-1">
                                                    已由 <?php echo htmlspecialchars($c['used_by_name']); ?> 使用
                                                </span>
                                            <?php elseif ($c['expires_at'] && strtotime($c['expires_at']) < time()): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1">已过期</span>
                                            <?php else: ?>
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">可使用</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo $c['expires_at'] ? date('Y-m-d', strtotime($c['expires_at'])) : '永久有效'; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="admin_coupons.php?delete=<?php echo $c['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="text-danger" onclick="return confirm('确定要删除吗？')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
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
render_layout($content, $page_title, 'admin');
?>
