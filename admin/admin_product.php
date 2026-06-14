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

// 锁定单软件 ID 1
$software_id = 1;

// 确保单软件记录存在
$stmt = $pdo->prepare("SELECT * FROM software WHERE id = ?");
$stmt->execute([$software_id]);
$sw = $stmt->fetch();

if (!$sw) {
    $pdo->prepare("INSERT INTO software (id, name, version, price_per_month) VALUES (?, ?, ?, ?)")
        ->execute([$software_id, '我的应用', '1.0.0', 10.00]);
    $stmt->execute([$software_id]);
    $sw = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $name = trim($_POST['name'] ?? '');
        $price_month = isset($_POST['price_month_enabled']) ? (float)($_POST['price_month'] ?? 0) : null;
        $price_quarter = isset($_POST['price_quarter_enabled']) ? (float)($_POST['price_quarter'] ?? 0) : null;
        $price_half_year = isset($_POST['price_half_year_enabled']) ? (float)($_POST['price_half_year'] ?? 0) : null;
        $price_year = isset($_POST['price_year_enabled']) ? (float)($_POST['price_year'] ?? 0) : null;
        $price_3year = isset($_POST['price_3year_enabled']) ? (float)($_POST['price_3year'] ?? 0) : null;
        $price_permanent = isset($_POST['price_permanent_enabled']) ? (float)($_POST['price_permanent'] ?? 0) : null;

        if ($name === '') {
            $error = '产品名称不能为空';
        } else {
            $stmt = $pdo->prepare("UPDATE software SET name=?, price_month=?, price_quarter=?, price_half_year=?, price_year=?, price_3year=?, price_permanent=? WHERE id=?");
            $stmt->execute([$name, $price_month, $price_quarter, $price_half_year, $price_year, $price_3year, $price_permanent, $software_id]);
            logAdminAction('更新产品多周期定价', "名称: $name");
            $success = '产品信息更新成功！';
            
            // 重新获取数据
            $stmt = $pdo->prepare("SELECT * FROM software WHERE id = ?");
            $stmt->execute([$software_id]);
            $sw = $stmt->fetch();
        }
    }
}

$page_title = '产品管理';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-box me-2"></i>核心产品配置</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">产品名称</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($sw['name']); ?>" required>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">授权价格与周期设置 (勾选开启)</h6>
                        <div class="row g-3">
                            <?php 
                            $periods = [
                                'month' => '月度授权',
                                'quarter' => '季度授权',
                                'half_year' => '半年授权',
                                'year' => '年度授权',
                                '3year' => '三年授权',
                                'permanent' => '永久授权'
                            ];
                            foreach ($periods as $key => $label):
                                $is_enabled = !empty($sw['price_'.$key]);
                                $price = $is_enabled ? $sw['price_'.$key] : '0.00';
                            ?>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-3">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="price_<?php echo $key; ?>_enabled" <?php echo $is_enabled ? 'checked' : ''; ?>>
                                            <label class="form-check-label small fw-bold"><?php echo $label; ?></label>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">¥</span>
                                            <input type="number" name="price_<?php echo $key; ?>" step="0.01" class="form-control" value="<?php echo $price; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text smaller">不勾选的周期将不会在购买页展示。</div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-5 fw-bold rounded-pill">
                            <i class="fas fa-save me-2"></i> 保存产品配置
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
