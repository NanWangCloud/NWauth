<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireAdmin();

global $pdo;
$username = $_SESSION['admin_username'];

// 获取真实统计数据
$stats = [];
$stats['total_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['today_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$stats['pending_feedback'] = (int)$pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 0")->fetchColumn();
$stats['total_software'] = (int)$pdo->query("SELECT COUNT(*) FROM software")->fetchColumn();
$stats['total_balance'] = (float)$pdo->query("SELECT SUM(balance) FROM users")->fetchColumn();
$stats['total_licenses'] = (int)$pdo->query("SELECT COUNT(*) FROM licenses WHERE status = 1")->fetchColumn();

$page_title = '数据概览';

ob_start();
?>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-inline-flex mb-3">
                    <i class="fas fa-users fa-2x"></i>
                </div>
                <h2 class="fw-bold mb-1"><?php echo $stats['total_users']; ?></h2>
                <div class="text-muted small fw-bold text-uppercase">总用户数</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-inline-flex mb-3">
                    <i class="fas fa-user-plus fa-2x"></i>
                </div>
                <h2 class="fw-bold mb-1"><?php echo $stats['today_users']; ?></h2>
                <div class="text-muted small fw-bold text-uppercase">今日新增</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle d-inline-flex mb-3">
                    <i class="fas fa-wallet fa-2x"></i>
                </div>
                <h2 class="fw-bold mb-1">¥<?php echo number_format($stats['total_balance'], 2); ?></h2>
                <div class="text-muted small fw-bold text-uppercase">全站总余额</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-white border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle d-inline-flex mb-3">
                    <i class="fas fa-key fa-2x"></i>
                </div>
                <h2 class="fw-bold mb-1"><?php echo $stats['total_licenses']; ?></h2>
                <div class="text-muted small fw-bold text-uppercase">活动授权数</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0">最近注册用户</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>用户 ID</th>
                                <th>用户名</th>
                                <th>余额</th>
                                <th>身份</th>
                                <th>注册日期</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
                            foreach ($recent_users as $u):
                            ?>
                                <tr>
                                    <td>#<?php echo $u['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                    <td class="text-success fw-bold">¥<?php echo number_format($u['balance'], 2); ?></td>
                                    <td>
                                        <span class="badge <?php echo $u['is_agent'] ? 'bg-warning text-dark' : 'bg-primary bg-opacity-10 text-primary'; ?> rounded-pill px-3 py-1 fw-bold">
                                            <?php echo $u['is_agent'] ? '代理商' : '普通用户'; ?>
                                        </span>
                                    </td>
                                    <td class="text-muted"><?php echo date('Y-m-d H:i', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">待处理事项</h5>
                <div class="list-group list-group-flush bg-transparent">
                    <a href="admin_feedback.php" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center border-0 px-0 py-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning text-white p-2 rounded-3 me-3"><i class="fas fa-comment-dots"></i></div>
                            <div>
                                <h6 class="fw-bold mb-0">待处理反馈</h6>
                                <p class="text-muted small mb-0">需要您的回复</p>
                            </div>
                        </div>
                        <span class="badge bg-warning rounded-pill px-3 py-2 text-dark fw-bold"><?php echo $stats['pending_feedback']; ?></span>
                    </a>
                    <a href="admin_cards.php" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center border-0 px-0 py-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white p-2 rounded-3 me-3"><i class="fas fa-credit-card"></i></div>
                            <div>
                                <h6 class="fw-bold mb-0">卡密管理</h6>
                                <p class="text-muted small mb-0">查看卡密库存</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                    <a href="admin_software.php" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center border-0 px-0 py-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-info text-white p-2 rounded-3 me-3"><i class="fas fa-cube"></i></div>
                            <div>
                                <h6 class="fw-bold mb-0">软件更新</h6>
                                <p class="text-muted small mb-0">发布新版本</p>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'admin');

/**
 * 作者：任意
 * qq：2908286914
 */
?>