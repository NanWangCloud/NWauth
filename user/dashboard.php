<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// user/dashboard.php
require_once '../core/auth.php';
requireLogin();

// 处理登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

global $pdo;
$user_id = $_SESSION['user_id'];

// 处理修改授权信息
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_license_info') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'CSRF 验证失败');
    } else {
        $license_id = (int)$_POST['license_id'];
        $new_qq = trim($_POST['new_qq'] ?? '');
        $new_domain = trim($_POST['new_domain'] ?? '');
        $fee = (float)get_setting('change_detail_fee', '10.00');
        
        if ($new_qq === '' || $new_domain === '') {
            set_flash_message('danger', 'QQ 和域名不能为空');
        } elseif (!validate_input($new_qq, 'qq')) {
            set_flash_message('danger', 'QQ 格式不正确 (5-11位数字)');
        } elseif (!validate_input($new_domain, 'domain')) {
            set_flash_message('danger', '域名格式不正确 (仅支持单域名，不可包含 * 或多域名)');
        } elseif ($_SESSION['balance'] < $fee) {
            set_flash_message('danger', '余额不足以支付修改手续费 ¥' . number_format($fee, 2));
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
                $stmt->execute([$fee, $user_id]);
                logFinance($user_id, 'consume', $fee, '修改授权资料: ' . $license_id);
                $_SESSION['balance'] -= $fee;

                $stmt = $pdo->prepare("UPDATE licenses SET qq = ?, domain = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$new_qq, $new_domain, $license_id, $user_id]);

                $pdo->commit();
                trigger_notification($user_id, 'mod', ['key' => $license_id]);
                
                set_flash_message('success', '授权信息修改成功！已扣除手续费 ¥' . number_format($fee, 2));
                redirect('user/dashboard.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash_message('danger', '修改失败: ' . $e->getMessage());
            }
        }
    }
}

// 处理删除授权 (退费)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_license') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'CSRF 验证失败');
    } else {
        $license_id = (int)$_POST['license_id'];
        $stmt = $pdo->prepare("SELECT l.* FROM licenses l WHERE l.id = ? AND l.user_id = ? AND l.status = 1");
        $stmt->execute([$license_id, $user_id]);
        $license = $stmt->fetch();

        if (!$license) {
            set_flash_message('danger', '授权不存在或已失效');
        } else {
            $refund_ratio = (float)get_setting('license_refund_ratio', '0.8');
            $stmt = $pdo->prepare("SELECT amount FROM purchase_logs WHERE user_id = ? AND software_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$user_id, $license['software_id']]);
            $last_purchase_amount = (float)$stmt->fetchColumn();
            
            $refund_amount = $last_purchase_amount * $refund_ratio;

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ? AND user_id = ?");
                $stmt->execute([$license_id, $user_id]);

                if ($refund_amount > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$refund_amount, $user_id]);
                    logFinance($user_id, 'refund', $refund_amount, '主动删除授权退费: ' . $license_id);
                    $_SESSION['balance'] += $refund_amount;
                }

                $pdo->commit();
                set_flash_message('success', '授权已删除，退还余额 ¥' . number_format($refund_amount, 2));
                redirect('user/dashboard.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                set_flash_message('danger', '操作失败: ' . $e->getMessage());
            }
        }
    }
}

// 获取用户的活动授权
$stmt = $pdo->prepare("
    SELECT l.*, s.name as software_name, s.version 
    FROM licenses l 
    JOIN software s ON l.software_id = s.id 
    WHERE l.user_id = ? AND l.status = 1
");
$stmt->execute([$user_id]);
$my_licenses = $stmt->fetchAll();

// 获取统计数据
$stmt = $pdo->prepare("SELECT COUNT(*) FROM licenses WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_licenses = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(amount) FROM finance_logs WHERE user_id = ? AND type = 'consume'");
$stmt->execute([$user_id]);
$total_spent = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending_feedback = $stmt->fetchColumn();

// 获取用户信息
$stmt = $pdo->prepare("SELECT nickname, balance, is_agent, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
$_SESSION['balance'] = $user_data['balance'];
$_SESSION['is_agent'] = $user_data['is_agent'];

$page_title = '我的控制台';

ob_start();
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-primary text-white" style="background: var(--primary-gradient) !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-white bg-opacity-25 p-2 rounded">
                        <i class="fas fa-wallet fa-lg"></i>
                    </div>
                    <span class="small opacity-75 fw-bold">账户余额</span>
                </div>
                <h3 class="fw-bold mb-0">¥ <?php echo number_format($_SESSION['balance'], 2); ?></h3>
                <div class="mt-3">
                    <a href="recharge.php" class="btn btn-sm btn-light rounded-pill px-3">立即充值</a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 text-success p-2 rounded">
                        <i class="fas fa-key fa-lg"></i>
                    </div>
                    <span class="small text-muted fw-bold">我的授权</span>
                </div>
                <h3 class="fw-bold mb-0"><?php echo $total_licenses; ?> <small class="text-muted h6">个</small></h3>
                <p class="text-muted smaller mb-0 mt-2">其中 <?php echo count($my_licenses); ?> 个正在使用中</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-warning bg-opacity-10 text-warning p-2 rounded">
                        <i class="fas fa-shopping-cart fa-lg"></i>
                    </div>
                    <span class="small text-muted fw-bold">累计消费</span>
                </div>
                <h3 class="fw-bold mb-0">¥ <?php echo number_format($total_spent, 2); ?></h3>
                <p class="text-muted smaller mb-0 mt-2">基于所有购买记录</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="bg-info bg-opacity-10 text-info p-2 rounded">
                        <i class="fas fa-comment-dots fa-lg"></i>
                    </div>
                    <span class="small text-muted fw-bold">工单反馈</span>
                </div>
                <h3 class="fw-bold mb-0"><?php echo $pending_feedback; ?> <small class="text-muted h6">个</small></h3>
                <p class="text-muted smaller mb-0 mt-2">待处理的反馈建议</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-dark text-white overflow-hidden shadow-sm border-0" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;">
            <div class="card-body p-4 position-relative">
                <div class="position-relative z-1">
                    <h4 class="fw-bold mb-1">欢迎回来, <?php echo htmlspecialchars($user_data['nickname'] ?: $_SESSION['username']); ?>!</h4>
                    <p class="mb-0 opacity-75">注册时间：<?php echo $user_data['created_at']; ?> · 代理身份：<?php echo $user_data['is_agent'] ? '<span class="text-warning fw-bold">合作伙伴</span>' : '普通用户'; ?></p>
                </div>
                <i class="fas fa-user-astronaut position-absolute end-0 bottom-0 opacity-25 m-n3" style="font-size: 6rem;"></i>
            </div>
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">我的软件授权</h4>
    <a href="buy.php" class="btn btn-primary btn-sm rounded-pill px-3">
        <i class="fas fa-plus me-1"></i> 购买新授权
    </a>
</div>

<div class="row g-4">
    <?php if (empty($my_licenses)): ?>
        <div class="col-12">
            <div class="card border-0 bg-light py-5 text-center">
                <div class="card-body">
                    <i class="fas fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted">您目前还没有任何活动授权</h5>
                    <p class="text-muted mb-4">购买授权后即可在这里看到它们</p>
                    <a href="buy.php" class="btn btn-primary rounded-pill px-4">前往购买</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($my_licenses as $lic): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                                <i class="fas fa-cube fa-lg"></i>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2">使用中</span>
                        </div>
                        <h5 class="fw-bold mb-1 text-truncate"><?php echo htmlspecialchars($lic['software_name']); ?></h5>
                        <p class="text-muted small mb-3">版本: v<?php echo $lic['version']; ?></p>
                        
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">授权密钥</span>
                                <a href="javascript:;" class="text-primary text-decoration-none copy-btn" onclick="copyToClipboard('<?php echo $lic['license_key']; ?>')">复制</a>
                            </div>
                            <code class="d-block text-break text-dark fw-bold"><?php echo $lic['license_key']; ?></code>
                        </div>

                        <div class="small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="far fa-clock me-1"></i> 到期时间</span>
                                <span class="fw-medium"><?php echo date('Y-m-d', strtotime($lic['expires_at'])); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="fab fa-qq me-1"></i> 授权 QQ</span>
                                <span class="fw-medium"><?php echo htmlspecialchars($lic['qq']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="fas fa-globe me-1"></i> 授权域名</span>
                                <span class="fw-medium text-truncate ms-2" style="max-width: 150px;"><?php echo htmlspecialchars($lic['domain']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><i class="fas fa-desktop me-1"></i> 绑定机器</span>
                                <span class="fw-medium text-truncate ms-2" style="max-width: 120px;">
                                    <?php echo $lic['hwid'] ? htmlspecialchars($lic['hwid']) : '<span class="text-warning">未绑定</span>'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-light border flex-grow-1" 
                                    onclick="openChangeModal(<?php echo $lic['id']; ?>, '<?php echo htmlspecialchars($lic['qq']); ?>', '<?php echo htmlspecialchars($lic['domain']); ?>')">
                                <i class="fas fa-edit me-1"></i> 修改
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger border flex-grow-1" 
                                    onclick="confirmDeleteLicense(<?php echo $lic['id']; ?>)">
                                <i class="fas fa-trash-alt me-1"></i> 删除
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="changeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">修改授权资料</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="change_license_info">
                    <input type="hidden" name="license_id" id="modalLicenseId">
                    
                    <div class="alert alert-info border-0 small">
                        <i class="fas fa-info-circle me-1"></i> 修改授权信息需要支付 <strong>¥<?php echo get_setting('change_detail_fee', '10.00'); ?></strong> 手续费。
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">新授权 QQ</label>
                        <input type="text" name="new_qq" id="modalQQ" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">新授权域名</label>
                        <input type="text" name="new_domain" id="modalDomain" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary px-4">支付并修改</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteLicenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">删除授权并退款</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4 text-center">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="delete_license">
                    <input type="hidden" name="license_id" id="deleteLicenseId">
                    
                    <div class="text-danger mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x"></i>
                    </div>
                    <h6 class="fw-bold">确定要删除该授权吗？</h6>
                    <p class="text-muted small">删除后授权将立即失效。系统将按最后一次购买金额的 <strong><?php echo (get_setting('license_refund_ratio', '0.8') * 100); ?>%</strong> 退还至您的账户余额。</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-danger px-4">确定删除</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openChangeModal(id, qq, domain) {
    document.getElementById('modalLicenseId').value = id;
    document.getElementById('modalQQ').value = qq;
    document.getElementById('modalDomain').value = domain;
    new bootstrap.Modal(document.getElementById('changeModal')).show();
}
function confirmDeleteLicense(id) {
    document.getElementById('deleteLicenseId').value = id;
    new bootstrap.Modal(document.getElementById('deleteLicenseModal')).show();
}
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('复制成功');
    });
}
</script>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'user');
?>
