<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// agent.php
require_once '../core/auth.php';
requireLogin();

global $pdo;

// Check if user is an agent
$stmt = $pdo->prepare("SELECT u.*, al.discount, al.name as level_name FROM users u LEFT JOIN agent_levels al ON u.agent_level = al.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user['is_agent']) {
    $discount_rate = 1.0;
    $level_name = '普通用户';
    $invitees = [];
    $stats = ['total_sales' => 0, 'total_revenue' => 0];
    $total_commission = 0;
} else {
    $discount_rate = $user['discount'] ?: 1.0;
    $level_name = $user['level_name'] ?: '普通用户';

    // Fetch agent's invitees
    $stmt = $pdo->prepare("SELECT id, username, balance, created_at FROM users WHERE inviter_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $invitees = $stmt->fetchAll();

    // Fetch agent's commission
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_sales, SUM(software.price_per_month) as total_revenue
        FROM licenses
        JOIN users ON licenses.user_id = users.id
        JOIN software ON licenses.software_id = software.id
        WHERE users.inviter_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();

    $commission_rate = 0.2; // 20% commission
    $total_commission = ($stats['total_revenue'] ?? 0) * $commission_rate;
}

// 处理代购
$buy_error = '';
$buy_success = '';
$software = $pdo->query("SELECT * FROM software LIMIT 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $buy_error = 'CSRF 验证失败';
    } else {
        if ($_POST['action'] === 'buy_for_others') {
            $months = (int)($_POST['months'] ?? 1);
            $total_cost = $software['price_per_month'] * $months;
            $final_cost = $total_cost * $discount_rate;

            if ($_SESSION['balance'] < $final_cost) {
                $buy_error = '余额不足，请充值！需要 ¥' . number_format($final_cost, 2);
            } else {
                $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$final_cost, $_SESSION['user_id']]);
                logFinance($_SESSION['user_id'], 'consume', $final_cost, '代理代购卡密');
                $_SESSION['balance'] -= $final_cost;

                    $card_key = generateKey('CDK');
                    $stmt = $pdo->prepare("INSERT INTO cards (card_key, type, value, software_id) VALUES (?, 'software_time', ?, ?)");
                    $stmt->execute([$card_key, $months * 30, $software['id']]);

                    $pdo->commit();
                    
                    // 发送邮件通知
                    trigger_notification($_SESSION['user_id'], 'agent_gen', ['key' => $card_key]);
                    
                    $buy_success = "购买成功！卡密为：<code class='user-select-all'>$card_key</code> (有效期 " . ($months * 30) . " 天)";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $buy_error = '购买失败: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'upgrade_agent') {
            $level_id = (int)$_POST['level_id'];
            $stmt = $pdo->prepare("SELECT * FROM agent_levels WHERE id = ?");
            $stmt->execute([$level_id]);
            $target_level = $stmt->fetch();
            
            if (!$target_level) {
                $buy_error = '等级不存在';
            } elseif ($target_level['min_recharge'] > $_SESSION['balance']) {
                $buy_error = '您的余额不足以升级到此等级，需 ¥' . number_format($target_level['min_recharge'], 2);
            } else {
                $pdo->prepare("UPDATE users SET agent_level = ?, is_agent = 1 WHERE id = ?")
                    ->execute([$level_id, $_SESSION['user_id']]);
                logFinance($_SESSION['user_id'], 'consume', 0, '自助升级代理等级: ' . $target_level['name']);
                
                // 发送邮件通知
                trigger_notification($_SESSION['user_id'], 'agent_up', ['level_name' => $target_level['name']]);
                
                set_flash_message('success', '等级升级成功！');
                redirect('user/agent.php');
            }
        }
    }
}

$all_levels = $pdo->query("SELECT * FROM agent_levels ORDER BY id ASC")->fetchAll();
$page_title = '代理中心 - 授权管理系统';

ob_start();
?>

<div class="row g-4 mb-4">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary">代理等级购买 / 升级</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <?php foreach ($all_levels as $lv): ?>
                        <div class="col-md-4">
                            <div class="card h-100 border <?php echo ($user['agent_level'] == $lv['id']) ? 'border-primary' : ''; ?> shadow-none">
                                <div class="card-body text-center p-4">
                                    <h6 class="fw-bold text-muted small mb-2"><?php echo htmlspecialchars($lv['name']); ?></h6>
                                    <h3 class="fw-bold mb-3 text-primary"><?php echo $lv['discount'] * 10; ?> 折</h3>
                                    <div class="small text-muted mb-4">升级门槛：¥ <?php echo number_format($lv['min_recharge'], 2); ?></div>
                                    
                                    <?php if ($user['agent_level'] == $lv['id']): ?>
                                        <button class="btn btn-primary btn-sm w-100 rounded-pill disabled" disabled>当前等级</button>
                                    <?php else: ?>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="upgrade_agent">
                                            <input type="hidden" name="level_id" value="<?php echo $lv['id']; ?>">
                                            <button type="submit" class="btn btn-outline-primary btn-sm w-100 rounded-pill" <?php echo ($lv['min_recharge'] > $_SESSION['balance']) ? 'disabled' : ''; ?>>
                                                <?php echo ($lv['min_recharge'] > $_SESSION['balance']) ? '余额不足' : '立即升级'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4 <?php echo (!$user['is_agent']) ? 'd-none' : ''; ?>">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body p-4 text-center">
                <h2 class="fw-bold mb-1"><?php echo $stats['total_sales'] ?? 0; ?></h2>
                <div class="small opacity-75 fw-bold text-uppercase">累计销售量</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-warning text-dark">
            <div class="card-body p-4 text-center">
                <h2 class="fw-bold mb-1">¥ <?php echo number_format($total_commission, 2); ?></h2>
                <div class="small opacity-75 fw-bold text-uppercase">累计预估提成</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4 <?php echo (!$user['is_agent']) ? 'd-none' : ''; ?>">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary">代购授权卡密</h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">作为代理，您可以以优惠价格购买授权卡密，然后将其售卖给您的客户。</p>
                
                <?php if ($buy_error): ?>
                    <div class="alert alert-danger border-0 small mb-4"><?php echo $buy_error; ?></div>
                <?php endif; ?>
                <?php if ($buy_success): ?>
                    <div class="alert alert-success border-0 small mb-4"><?php echo $buy_success; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="buy_for_others">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">购买时长</label>
                            <select name="months" class="form-select" id="agentBuyMonths">
                                <option value="1">1 个月 (30天)</option>
                                <option value="3">3 个月 (90天)</option>
                                <option value="6">6 个月 (180天)</option>
                                <option value="12">12 个月 (365天)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light p-2 rounded border text-center">
                                <div class="text-muted small">代理价 (<?php echo $level_name; ?>)</div>
                                <div class="fw-bold text-primary">¥ <span id="agentPrice"><?php echo number_format($software['price_per_month'] * $discount_rate, 2); ?></span></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                                <i class="fas fa-shopping-bag me-1"></i> 购买
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary">代理权益</h5>
            </div>
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div>
                        <div class="fw-bold small">拿货折扣 (<?php echo $level_name; ?>)</div>
                        <div class="text-muted smaller">所有授权卡密 <?php echo $discount_rate * 10; ?> 折优惠</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const basePrice = <?php echo $software['price_per_month']; ?>;
const discount = <?php echo $discount_rate; ?>;
if (document.getElementById('agentBuyMonths')) {
    document.getElementById('agentBuyMonths').addEventListener('change', function() {
        const months = parseInt(this.value);
        const price = (basePrice * months * discount).toFixed(2);
        document.getElementById('agentPrice').innerText = price;
    });
}
</script>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'user');

/**
 * 作者：任意
 * qq：2908286914
 */
?>