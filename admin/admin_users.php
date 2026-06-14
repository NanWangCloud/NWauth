<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
require_once '../core/auth.php';
requireAdmin();
$success = '';
$error = '';
global $pdo;
if (isset($_GET['get_licenses'])) {
    $user_id = (int)$_GET['get_licenses'];
    $stmt = $pdo->prepare("SELECT l.*, s.name as software_name FROM licenses l JOIN software s ON l.software_id = s.id WHERE l.user_id = ?");
    $stmt->execute([$user_id]);
    $licenses = $stmt->fetchAll();
    if (empty($licenses)) {
        echo '<div class="text-center py-4 text-muted">该用户暂无授权</div>';
    } else {
        echo '<div class="list-group list-group-flush">';
        foreach ($licenses as $lic) {
            $lic_json = htmlspecialchars(json_encode($lic), ENT_QUOTES, 'UTF-8');
            echo '<div class="list-group-item d-flex justify-content-between align-items-center py-3 border-bottom-0 mb-2 bg-light rounded-3">
                    <div>
                        <h6 class="fw-bold mb-1">'.$lic['software_name'].'</h6>
                        <div class="small text-muted">
                            QQ: '.($lic['qq'] ?: '未填').' | 域名: '.($lic['domain'] ?: '未填').'<br>
                            到期: '.$lic['expires_at'].'
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick=\'openEditLicense('.$lic_json.')\'>编辑</button>
                  </div>';
        }
        echo '</div>';
    }
    exit;
}
if (isset($_GET['switch_back'])) {
    if (isset($_SESSION['impersonated_by_admin'])) {
        $admin_id = $_SESSION['impersonated_by_admin'];
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        if ($admin) {
            unset($_SESSION['impersonated_by_admin']);
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['is_admin'] = true;
            set_flash_message('success', '已返回管理员身份');
            redirect(get_setting('admin_path', 'admin') . '/admin_users.php');
        }
    }
}

if (isset($_GET['login_as'])) {
    if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $user_id = (int)$_GET['login_as'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if ($user) {
            // 安全增强：保留管理员身份标识
            $_SESSION['impersonated_by_admin'] = $_SESSION['admin_id'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = false;
            set_flash_message('success', '已进入模拟登录模式：' . $user['username']);
            redirect('user/dashboard.php');
        } else {
            $error = '用户不存在';
        }
    }
}

// 处理创建用户
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $agent_level = (int)$_POST['agent_level'];

        if (!validate_input($username, 'username')) {
            $error = '用户名格式不正确 (3-20位字母/数字/下划线)';
        } elseif (mb_strlen($password) < 6) {
            $error = '密码至少 6 位';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            try {
                // 确保提供所有列以防止插入失败
                $stmt = $pdo->prepare("INSERT INTO users (username, password, email, nickname, agent_level, is_agent, balance, email_notify_settings, inviter_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $username, 
                    $hashedPassword, 
                    $email ?: null, 
                    $nickname ?: $username, 
                    $agent_level, 
                    ($agent_level > 0 ? 1 : 0), 
                    0.00,
                    null,
                    null
                ]);
                logAdminAction('创建新用户', "用户名: $username");
                $success = '用户创建成功！';
            } catch (PDOException $e) {
                $error = '创建失败：' . $e->getMessage();
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_balance') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $user_id = (int)$_POST['user_id'];
        $amount = (float)$_POST['amount'];
        if ($amount < -1000000 || $amount > 1000000) {
            $error = '单次调整金额限制在 ±1,000,000 以内';
        } else {
            $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$amount, $user_id]);
            logFinance($user_id, $amount > 0 ? 'recharge' : 'consume', abs($amount), '管理员后台手动调整余额');
            logAdminAction('调整用户余额', "用户ID: $user_id, 金额: $amount");
            $success = '余额调整成功！';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $user_id = (int)$_POST['user_id'];
        $agent_level = (int)$_POST['agent_level'];
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE users SET agent_level = ?, is_agent = ?, nickname = ?, email = ? WHERE id = ?")
                ->execute([$agent_level, ($agent_level > 0 ? 1 : 0), $nickname, $email, $user_id]);
            if ($password !== '') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $user_id]);
                logAdminAction('重置用户密码', "用户ID: $user_id");
            }
            logAdminAction('修改用户信息', "用户ID: $user_id, 等级: $agent_level");
            $pdo->commit();
            $success = '用户信息更新成功！';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = '更新失败: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_mass_email') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $user_ids = $_POST['user_id']; // 'all' or comma-separated IDs
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($subject === '' || $content === '') {
            $error = '标题和内容不能为空';
        } else {
            if ($user_ids === 'all') {
                $stmt = $pdo->query("SELECT email FROM users WHERE email IS NOT NULL AND email != ''");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $ids = array_map('intval', explode(',', $user_ids));
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("SELECT email FROM users WHERE id IN ($placeholders) AND email IS NOT NULL AND email != ''");
                $stmt->execute($ids);
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            $count = 0;
            foreach ($recipients as $to) {
                if ($to && send_email($to, $subject, $content)) {
                    $count++;
                }
            }
            logAdminAction('发送邮件推送', "对象数量: " . count($recipients) . ", 成功数量: $count");
            $success = "邮件推送完成，成功发送至 $count 个邮箱。";
        }
    }
}

if (isset($_GET['delete_user'])) {
    if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $user_id = (int)$_GET['delete_user'];
        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM licenses WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM finance_logs WHERE user_id = ?")->execute([$user_id]);
            $pdo->prepare("DELETE FROM feedback WHERE user_id = ?")->execute([$user_id]);
            $pdo->commit();
            logAdminAction('删除用户', "用户ID: $user_id");
            $success = '用户及其相关数据已彻底删除！';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = '删除失败: ' . $e->getMessage();
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_license') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $license_id = (int)$_POST['license_id'];
        $qq = trim($_POST['qq'] ?? '');
        $domain = trim($_POST['domain'] ?? '');
        $hwid = trim($_POST['hwid'] ?? '');
        $expires_at = $_POST['expires_at'];
        $pdo->prepare("UPDATE licenses SET qq = ?, domain = ?, hwid = ?, expires_at = ? WHERE id = ?")
            ->execute([$qq, $domain, $hwid, $expires_at, $license_id]);
        logAdminAction('编辑用户授权', "授权ID: $license_id, QQ: $qq, 域名: $domain");
        $success = '授权信息更新成功！';
    }
}
$query = "SELECT u.*, al.name as level_name 
          FROM users u 
          LEFT JOIN agent_levels al ON u.agent_level = al.id 
          ORDER BY u.id DESC";
$users = $pdo->query($query)->fetchAll();
$agent_levels = $pdo->query("SELECT * FROM agent_levels")->fetchAll();
$page_title = '用户管理';
ob_start();
?>
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
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">所有注册用户</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm rounded-pill" onclick="openCreateUserModal()">
                <i class="fas fa-user-plus me-1"></i> 创建用户
            </button>
            <button class="btn btn-primary btn-sm rounded-pill" onclick="openMassEmailModal('selected')">
                <i class="fas fa-paper-plane me-1"></i> 批量推送
            </button>
            <button class="btn btn-dark btn-sm rounded-pill" onclick="openMassEmailModal('all')">
                全体推送
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>角色/等级</th>
                        <th>余额</th>
                        <th>注册时间</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input user-checkbox" value="<?php echo $u['id']; ?>"></td>
                            <td data-label="ID">#<?php echo $u['id']; ?></td>
                            <td data-label="用户名">
                                <div class="d-flex align-items-center justify-content-end justify-content-md-start">
                                    <div class="bg-light p-2 rounded-circle me-3 d-none d-md-block">
                                        <i class="fas fa-user text-muted"></i>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($u['nickname'] ?? '未设置昵称'); ?> · 
                                            <?php echo htmlspecialchars($u['email'] ?? '未绑定邮箱'); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td data-label="角色/等级">
                                <div class="d-flex flex-column gap-1 align-items-end align-items-md-start">
                                    <?php if ($u['is_agent']): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 fw-bold">
                                            <?php echo htmlspecialchars($u['level_name'] ?? '代理商'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-1 fw-bold">
                                            普通用户
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td data-label="余额">
                                <span class="text-success fw-bold">¥<?php echo number_format($u['balance'], 2); ?></span>
                            </td>
                            <td data-label="注册时间" class="text-muted small">
                                <?php echo date('Y-m-d H:i', strtotime($u['created_at'])); ?>
                            </td>
                            <td data-label="操作" class="text-end">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-light border" onclick="openBalanceModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>', <?php echo $u['balance']; ?>)">
                                        <i class="fas fa-wallet"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick='openEditUserModal(<?php echo json_encode($u); ?>)'>
                                        <i class="fas fa-user-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick='openLicenseModal(<?php echo $u['id']; ?>, "<?php echo htmlspecialchars($u['username']); ?>")'>
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <a href="admin_users.php?login_as=<?php echo $u['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="btn btn-sm btn-light border" title="免密登录">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-light border text-primary" onclick="openMassEmailModal(<?php echo $u['id']; ?>)" title="发送邮件">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <a href="admin_users.php?delete_user=<?php echo $u['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" class="btn btn-sm btn-light border text-danger" title="删除用户" onclick="return confirm('确定要删除该用户及其所有数据吗？此操作不可恢复！')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">编辑用户: <span id="editUserTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">显示名字 (昵称)</label>
                        <input type="text" name="nickname" id="editNickname" class="form-control" placeholder="昵称">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">绑定邮箱</label>
                        <input type="email" name="email" id="editEmail" class="form-control" placeholder="email@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">修改密码 (不改请留空)</label>
                        <input type="password" name="password" class="form-control" placeholder="******">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-12" id="agentLevelGroup">
                            <label class="form-label small fw-bold">代理等级 (设为"无"则取消代理身份)</label>
                            <select name="agent_level" id="editAgentLevel" class="form-select">
                                <option value="0">无 (普通用户)</option>
                                <?php foreach ($agent_levels as $al): ?>
                                    <option value="<?php echo $al['id']; ?>"><?php echo htmlspecialchars($al['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary px-4">保存修改</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="licenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">管理授权 - <span id="licenseUserTitle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="licenseListContent" class="p-4">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="editLicenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">编辑授权详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="edit_license">
                    <input type="hidden" name="license_id" id="editLicenseId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">授权 QQ</label>
                        <input type="text" name="qq" id="editLicenseQQ" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">授权域名</label>
                        <input type="text" name="domain" id="editLicenseDomain" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">绑定机器码 (HWID)</label>
                        <input type="text" name="hwid" id="editLicenseHWID" class="form-control">
                        <div class="form-text small">留空代表解绑机器。</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">到期时间</label>
                        <input type="datetime-local" name="expires_at" id="editLicenseExpiry" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary px-4">确认更新</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- 创建用户模态框 -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">创建新用户</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="create_user">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">用户名</label>
                        <input type="text" name="username" class="form-control" required placeholder="3-20位字母/数字">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">登录密码</label>
                        <input type="password" name="password" class="form-control" required placeholder="至少6位">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">显示名字 (昵称)</label>
                        <input type="text" name="nickname" class="form-control" placeholder="选填">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">电子邮箱</label>
                        <input type="email" name="email" class="form-control" placeholder="选填">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">代理等级</label>
                        <select name="agent_level" class="form-select">
                            <option value="0">普通用户</option>
                            <?php foreach ($agent_levels as $lv): ?>
                                <option value="<?php echo $lv['id']; ?>"><?php echo htmlspecialchars($lv['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary px-4">立即创建</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="massEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">邮件推送</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="send_mass_email">
                    <input type="hidden" name="user_id" id="emailUserId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">邮件标题</label>
                        <input type="text" name="subject" class="form-control" placeholder="请输入邮件标题" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">邮件内容 (支持 HTML)</label>
                        <textarea name="content" class="form-control" rows="8" placeholder="请输入邮件内容..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary px-4">立即发送</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

function openCreateUserModal() {
    new bootstrap.Modal(document.getElementById('createUserModal')).show();
}

function openMassEmailModal(target) {
    let userIds = '';
    if (target === 'selected') {
        const selected = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) {
            alert('请先勾选要推送的用户');
            return;
        }
        userIds = selected.join(',');
    } else if (target === 'all') {
        userIds = 'all';
    } else {
        userIds = target; // single ID
    }
    
    document.getElementById('emailUserId').value = userIds;
    new bootstrap.Modal(document.getElementById('massEmailModal')).show();
}
function openEditUserModal(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editUserTitle').innerText = user.username;
    document.getElementById('editNickname').value = user.nickname || '';
    document.getElementById('editEmail').value = user.email || '';
    document.getElementById('editAgentLevel').value = user.agent_level || 0;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
function openLicenseModal(userId, username) {
    document.getElementById('licenseUserTitle').innerText = username;
    const modal = new bootstrap.Modal(document.getElementById('licenseModal'));
    modal.show();
    fetch('admin_users.php?get_licenses=' + userId)
        .then(res => res.text())
        .then(html => {
            document.getElementById('licenseListContent').innerHTML = html;
        });
}
function openEditLicense(lic) {
    document.getElementById('editLicenseId').value = lic.id;
    document.getElementById('editLicenseQQ').value = lic.qq;
    document.getElementById('editLicenseDomain').value = lic.domain;
    document.getElementById('editLicenseHWID').value = lic.hwid || '';
    const date = new Date(lic.expires_at);
    const formatted = date.toISOString().slice(0, 16);
    document.getElementById('editLicenseExpiry').value = formatted;
    new bootstrap.Modal(document.getElementById('editLicenseModal')).show();
}
</script>
<div class="modal fade" id="balanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">余额调整 - <span id="modalUsername"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="adjust_balance">
                    <input type="hidden" name="user_id" id="modalUserId">
                    <div class="mb-3 text-center">
                        <div class="text-muted small mb-1">当前余额</div>
                        <h3 class="fw-bold text-primary">¥<span id="modalCurrentBalance"></span></h3>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">调整金额 (正数为加，负数为减)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">¥</span>
                            <input type="number" name="amount" step="0.01" class="form-control border-start-0" placeholder="0.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary px-4">确认调整</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openBalanceModal(userId, username, balance) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUsername').innerText = username;
    document.getElementById('modalCurrentBalance').innerText = balance.toFixed(2);
    const modal = new bootstrap.Modal(document.getElementById('balanceModal'));
    modal.show();
}
</script>
<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'admin');
/**
 * 作者：任意
 * qq：2908286914
 */
?>