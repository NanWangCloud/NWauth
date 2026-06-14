<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// user/recharge.php
require_once '../core/auth.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $card_key = strtoupper(trim($_POST['card_key'] ?? ''));

        if (!validate_input($card_key, 'card_key', 10, 50)) {
            $error = '卡密格式不正确！';
        } else {
            global $pdo;
            $stmt = $pdo->prepare("SELECT * FROM cards WHERE card_key = ? AND is_used = 0");
            $stmt->execute([$card_key]);
            $card = $stmt->fetch();

            if ($card) {
                $pdo->beginTransaction();
                try {
                    // 1. Mark card as used
                    $stmt = $pdo->prepare("UPDATE cards SET is_used = 1, used_by = ?, used_at = NOW() WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $card['id']]);

                    if ($card['type'] === 'balance') {
                        // 2. Add balance to user
                        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                        $stmt->execute([$card['value'], $_SESSION['user_id']]);
                        logFinance($_SESSION['user_id'], 'recharge', $card['value'], '卡密充值: ' . $card['card_key']);
                        $_SESSION['balance'] += $card['value'];
                        set_flash_message('success', "充值成功！余额已增加 " . number_format($card['value'], 2) . " 元。");
                        redirect('user/recharge.php');
                    } elseif ($card['type'] === 'software_time') {
                        // 3. Add time to software license
                        $software_id = $card['software_id'];
                        $days = (int)$card['value'];

                        // Check if user already has a license for this software
                        $stmt = $pdo->prepare("SELECT * FROM licenses WHERE user_id = ? AND software_id = ?");
                        $stmt->execute([$_SESSION['user_id'], $software_id]);
                        $license = $stmt->fetch();

                        if ($license) {
                            // Extend existing license
                            $current_expiry = new DateTime($license['expires_at'] > date('Y-m-d H:i:s') ? $license['expires_at'] : 'now');
                            $current_expiry->modify("+$days days");
                            $new_expiry = $current_expiry->format('Y-m-d H:i:s');
                            
                            $stmt = $pdo->prepare("UPDATE licenses SET expires_at = ?, status = 1 WHERE id = ?");
                            $stmt->execute([$new_expiry, $license['id']]);
                        } else {
                            // Create new license
                            $expiry = (new DateTime())->modify("+$days days")->format('Y-m-d H:i:s');
                            $license_key = generateKey('LIC');
                            $stmt = $pdo->prepare("INSERT INTO licenses (user_id, software_id, license_key, expires_at) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$_SESSION['user_id'], $software_id, $license_key, $expiry]);
                        }
                        set_flash_message('success', "卡密激活成功！已增加 " . $days . " 天授权时长。");
                        redirect('user/recharge.php');
                    }

                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = '处理失败: ' . $e->getMessage();
                }
            } else {
                $error = '卡密无效或已被使用！';
            }
        }
    }
}

$page_title = '卡密充值 - 授权管理系统';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fas fa-credit-card me-2"></i> 卡密充值/激活
                </h5>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small mb-4">
                    请输入您购买的卡密进行充值或激活软件。卡密通常由代理商提供或在自动发卡平台购买。
                </p>

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
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">充值卡密</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="text" name="card_key" class="form-control form-control-lg border-start-0 ps-0" 
                                   placeholder="例如: BALANCE-XXXX-XXXX-XXXX" required 
                                   style="font-family: 'Courier New', Courier, monospace; letter-spacing: 1px;">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm">
                        <i class="fas fa-bolt me-2"></i> 立即激活卡密
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold small mb-2 text-muted">温馨提示:</h6>
                    <ul class="text-muted small ps-3 mb-0">
                        <li class="mb-1">余额类卡密将直接增加您的账户余额，可用于购买任意授权。</li>
                        <li class="mb-1">软件类卡密将直接激活或延长对应软件的授权时长。</li>
                        <li>卡密一经使用立即失效，请勿向他人泄露未使用的卡密。</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card border-0 bg-primary bg-opacity-10 mt-4 shadow-none">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="bg-primary text-white p-2 rounded-3 me-3">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0">遇到问题？</h6>
                    <p class="text-muted small mb-0">如果卡密无法使用，请联系您的代理商或我们的客服。</p>
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