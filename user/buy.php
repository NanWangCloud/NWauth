<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// user/buy.php
require_once '../core/auth.php';
requireLogin();

$error = '';
$success = '';

global $pdo;
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.*, al.discount FROM users u LEFT JOIN agent_levels al ON u.agent_level = al.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();
$discount_rate = ($user_info['is_agent'] && $user_info['discount']) ? $user_info['discount'] : 1.0;

$software = $pdo->query("SELECT * FROM software LIMIT 1")->fetch();

if (!$software) {
    set_flash_message('danger', '系统暂未发布任何软件授权。');
    redirect('user/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } elseif (!verifyCaptcha($_POST['captcha'] ?? '')) {
        $error = '图形验证码错误';
    } else {
        $period = $_POST['period'] ?? 'month';
        $qq = trim($_POST['qq'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $coupon_code = trim($_POST['coupon_code'] ?? '');

        $price_field = "price_$period";
        if (!isset($software[$price_field]) || is_null($software[$price_field])) {
            $error = '该购买周期暂未开启！';
        } elseif (!validate_input($qq, 'qq')) {
            $error = 'QQ 格式不正确 (5-11位数字)';
        } elseif (!validate_input($domain, 'domain')) {
            $error = '域名格式不正确 (仅支持单域名，不可包含 * 或多域名)';
        } else {
            $base_price = (float)$software[$price_field];
            $total_cost = $base_price * $discount_rate;
            $discount = 0;

            // 处理优惠券
            if ($coupon_code !== '') {
                $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_used = 0 AND (expires_at IS NULL OR expires_at > NOW())");
                $stmt->execute([$coupon_code]);
                $coupon = $stmt->fetch();
                if ($coupon) {
                    if ($total_cost >= $coupon['min_amount']) {
                        if ($coupon['discount_type'] === 'amount') {
                            $discount = $coupon['value'];
                        } else {
                            $discount = $total_cost * ($coupon['value'] / 100);
                        }
                        $total_cost -= $discount;
                        if ($total_cost < 0) $total_cost = 0;
                    } else {
                        $error = '优惠券不满足最低使用金额：¥' . $coupon['min_amount'];
                    }
                } else {
                    $error = '优惠券无效或已过期';
                }
            }

            if (!$error) {
                if ($_SESSION['balance'] < $total_cost) {
                    $error = '余额不足，请充值！当前余额: ' . $_SESSION['balance'] . ' 元';
                } else {
                    $pdo->beginTransaction();
                    try {
                        // 1. Deduct balance
                        $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                        $stmt->execute([$total_cost, $_SESSION['user_id']]);
                        logFinance($_SESSION['user_id'], 'consume', $total_cost, '购买授权: ' . $software['name'] . " ($period)");
                        $_SESSION['balance'] -= $total_cost;

                        // 2. Mark coupon as used
                        if (isset($coupon) && $coupon) {
                            $stmt = $pdo->prepare("UPDATE coupons SET is_used = 1, used_by = ? WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id'], $coupon['id']]);
                        }

                        // 3. Add license
                        $period_map = [
                            'month' => '+30 days',
                            'quarter' => '+90 days',
                            'half_year' => '+180 days',
                            'year' => '+365 days',
                            '3year' => '+1095 days',
                            'permanent' => '+99 years'
                        ];
                        $expiry = (new DateTime())->modify($period_map[$period])->format('Y-m-d H:i:s');
                        $license_key = generateKey('LIC');
                        $stmt = $pdo->prepare("INSERT INTO licenses (user_id, software_id, license_key, qq, domain, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $software['id'], $license_key, $qq, $domain, $expiry]);

                        // 4. Log purchase
                        $stmt = $pdo->prepare("INSERT INTO purchase_logs (user_id, software_id, months, amount) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$_SESSION['user_id'], $software['id'], 0, $total_cost]); 

                        $pdo->commit();
                        
                        // 发送通知
                        trigger_notification($user_id, 'buy', ['software_name' => $software['name'], 'duration' => $period, 'key' => $license_key]);
                        
                        $success = "购买成功！已花费 " . number_format($total_cost, 2) . " 元购买了授权。";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = '购买失败: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$page_title = '购买授权 - ' . $software['name'];

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-header bg-primary text-white py-4" style="background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%) !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($software['name']); ?></h3>
                        <p class="mb-0 opacity-75">官方授权正版授权购买</p>
                    </div>
                    <div class="text-end">
                        <div class="small opacity-75">当前余额</div>
                        <h4 class="fw-bold mb-0">¥ <?php echo number_format($_SESSION['balance'], 2); ?></h4>
                    </div>
                </div>
            </div>
            <div class="card-body p-4 p-lg-5">
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 shadow-sm mb-4">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small">授权 QQ</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fab fa-qq"></i></span>
                                <input type="text" name="qq" class="form-control border-start-0" placeholder="5-11位 QQ 号" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small">授权域名</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-globe"></i></span>
                                <input type="text" name="domain" class="form-control border-start-0" placeholder="example.com (单域名)" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small">购买周期</label>
                            <select name="period" class="form-select" onchange="updatePriceDisplay()">
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
                                    if (!is_null($software['price_'.$key])):
                                ?>
                                <option value="<?php echo $key; ?>" data-price="<?php echo $software['price_'.$key] * $discount_rate; ?>"><?php echo $label; ?></option>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small">优惠券代码 (可选)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-ticket-alt"></i></span>
                                <input type="text" name="coupon_code" class="form-control border-start-0" placeholder="如有优惠券请在此输入">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small">图形验证码</label>
                        <div class="input-group">
                            <input type="text" name="captcha" class="form-control" placeholder="请输入验证码" required>
                            <span class="input-group-text p-0 border-0">
                                <img src="../core/captcha.php" alt="captcha" onclick="this.src='../core/captcha.php?'+Math.random()" style="cursor:pointer; height:38px;">
                            </span>
                        </div>
                    </div>

                    <div class="p-4 bg-light rounded-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 fw-bold mb-0">应付总计</span>
                            <span class="h3 fw-bold text-primary mb-0">¥ <span id="totalPrice">0.00</span></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm" style="background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%) !important;">
                        <i class="fas fa-shopping-cart me-2"></i> 立即购买授权
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updatePriceDisplay() {
    const select = document.querySelector('select[name="period"]');
    const option = select.options[select.selectedIndex];
    const price = option.getAttribute('data-price');
    document.getElementById('totalPrice').innerText = parseFloat(price).toFixed(2);
}
document.addEventListener('DOMContentLoaded', updatePriceDisplay);
</script>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'user');
?>
