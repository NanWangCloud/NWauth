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

// 处理邮箱测试 (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'test_email') {
    header('Content-Type: application/json');
    $to = trim($_GET['email'] ?? '');
    if (!validate_input($to, 'email')) {
        echo json_encode(['status' => false, 'msg' => '测试邮箱格式不正确']);
        exit;
    }
    if (send_email($to, 'SMTP 配置测试', '这是一封来自 ' . get_setting('site_name') . ' 的测试邮件。如果您收到此邮件，说明您的 SMTP 配置正确。')) {
        echo json_encode(['status' => true, 'msg' => '测试邮件已发送，请检查收件箱']);
    } else {
        echo json_encode(['status' => false, 'msg' => '邮件发送失败，请检查 SMTP 配置或服务器支持情况']);
    }
    exit;
}

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF 验证失败';
    } else {
        $site_name = trim($_POST['site_name'] ?? '');
        $site_url = trim($_POST['site_url'] ?? '');
        $allow_register = isset($_POST['allow_register']) ? '1' : '0';
        $force_hwid = isset($_POST['force_hwid']) ? '1' : '0';
        $reg_gift = (float)($_POST['reg_gift'] ?? 0);
        $api_key = trim($_POST['api_key'] ?? '');
        $admin_path = trim($_POST['admin_path'] ?? 'admin');
        $change_detail_fee = (float)($_POST['change_detail_fee'] ?? 0);
        $reg_email_suffix = trim($_POST['reg_email_suffix'] ?? '');

        // SEO 与品牌设置
        $site_description = trim($_POST['site_description'] ?? '');
        $site_keywords = trim($_POST['site_keywords'] ?? '');
        $site_icp = trim($_POST['site_icp'] ?? '');
        $site_copyright_no = trim($_POST['site_copyright_no'] ?? '');

        // 文件上传处理
        $upload_dir = BASE_PATH . '/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
            $logo_name = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $upload_dir . $logo_name)) {
                update_setting('site_logo', 'uploads/' . $logo_name);
            }
        }

        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION);
            $favicon_name = 'favicon_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $upload_dir . $favicon_name)) {
                update_setting('site_favicon', 'uploads/' . $favicon_name);
            }
        }

        if ($site_name === '' || $site_url === '') {
        $smtp_host = trim($_POST['smtp_host'] ?? '');
        $smtp_port = trim($_POST['smtp_port'] ?? '465');
        $smtp_user = trim($_POST['smtp_user'] ?? '');
        $smtp_pass = trim($_POST['smtp_pass'] ?? '');
        $smtp_from = trim($_POST['smtp_from'] ?? '');
        $smtp_from_name = trim($_POST['smtp_from_name'] ?? '');
        $smtp_secure = trim($_POST['smtp_secure'] ?? 'ssl');

        // 验证码配置
        $captcha_type = $_POST['captcha_type'] ?? '1';
        $captcha_length = (int)($_POST['captcha_length'] ?? 4);

        // 业务逻辑
        $license_refund_ratio = (float)($_POST['license_refund_ratio'] ?? 0.8);
        $mod_license_email_verify = isset($_POST['mod_license_email_verify']) ? '1' : '0';

        // 全局通知开关
        $global_notify_login = isset($_POST['global_notify_login']) ? '1' : '0';
        $global_notify_buy = isset($_POST['global_notify_buy']) ? '1' : '0';
        $global_notify_mod = isset($_POST['global_notify_mod']) ? '1' : '0';
        $global_notify_bind = isset($_POST['global_notify_bind']) ? '1' : '0';
        $global_notify_agent_up = isset($_POST['global_notify_agent_up']) ? '1' : '0';
        $global_notify_agent_gen = isset($_POST['global_notify_agent_gen']) ? '1' : '0';

        // 模板
        $email_tpl_login = $_POST['email_tpl_login'] ?? '';
        $email_tpl_buy = $_POST['email_tpl_buy'] ?? '';
        $email_tpl_mod = $_POST['email_tpl_mod'] ?? '';
        $email_tpl_bind = $_POST['email_tpl_bind'] ?? '';
        $email_tpl_agent_up = $_POST['email_tpl_agent_up'] ?? '';
        $email_tpl_agent_gen = $_POST['email_tpl_agent_gen'] ?? '';

        if ($site_name === '' || $site_url === '') {
            $error = '站点名称和 URL 不能为空';
        } elseif ($admin_path === '') {
            $error = '后台路径不能为空';
        } else {
            update_setting('site_name', $site_name);
            update_setting('site_url', $site_url);
            update_setting('allow_register', $allow_register);
            update_setting('force_hwid', $force_hwid);
            update_setting('reg_gift', $reg_gift);
            update_setting('api_key', $api_key);
            update_setting('admin_path', $admin_path);
            update_setting('change_detail_fee', $change_detail_fee);
            update_setting('reg_email_verify', $reg_email_verify);
            update_setting('reg_email_suffix', $reg_email_suffix);

            update_setting('site_description', $site_description);
            update_setting('site_keywords', $site_keywords);
            update_setting('site_icp', $site_icp);
            update_setting('site_copyright_no', $site_copyright_no);

            update_setting('smtp_host', $smtp_host);
            update_setting('smtp_port', $smtp_port);
            update_setting('smtp_user', $smtp_user);
            update_setting('smtp_pass', $smtp_pass);
            update_setting('smtp_from', $smtp_from);
            update_setting('smtp_from_name', $smtp_from_name);
            update_setting('smtp_secure', $smtp_secure);

            update_setting('captcha_type', $captcha_type);
            update_setting('captcha_length', $captcha_length);

            update_setting('license_refund_ratio', $license_refund_ratio);
            update_setting('mod_license_email_verify', $mod_license_email_verify);

            update_setting('global_notify_login', $global_notify_login);
            update_setting('global_notify_buy', $global_notify_buy);
            update_setting('global_notify_mod', $global_notify_mod);
            update_setting('global_notify_bind', $global_notify_bind);
            update_setting('global_notify_agent_up', $global_notify_agent_up);
            update_setting('global_notify_agent_gen', $global_notify_agent_gen);

            update_setting('email_tpl_login', $email_tpl_login);
            update_setting('email_tpl_buy', $email_tpl_buy);
            update_setting('email_tpl_mod', $email_tpl_mod);
            update_setting('email_tpl_bind', $email_tpl_bind);
            update_setting('email_tpl_agent_up', $email_tpl_agent_up);
            update_setting('email_tpl_agent_gen', $email_tpl_agent_gen);

            logAdminAction('更新系统设置 v0.2.0');
            $success = '系统设置已更新！';
        }
    }
}

// 获取当前设置
$keys = [
    'site_name', 'site_url', 'allow_register', 'force_hwid', 'reg_gift', 
    'api_key', 'admin_path', 'change_detail_fee', 'reg_email_verify', 'reg_email_suffix',
    'site_description', 'site_keywords', 'site_logo', 'site_favicon', 'site_icp', 'site_copyright_no',
    'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from', 'smtp_from_name', 'smtp_secure',
    'captcha_type', 'captcha_length', 'license_refund_ratio', 'mod_license_email_verify',
    'global_notify_login', 'global_notify_buy', 'global_notify_mod', 'global_notify_bind', 'global_notify_agent_up', 'global_notify_agent_gen',
    'email_tpl_login', 'email_tpl_buy', 'email_tpl_mod', 'email_tpl_bind', 'email_tpl_agent_up', 'email_tpl_agent_gen'
];
$current_settings = [];
foreach ($keys as $key) {
    $current_settings[$key] = get_setting($key);
}

$page_title = '系统设置';
ob_start();
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs nav-justified border-0 bg-light" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active py-3 fw-bold" id="basic-tab" data-bs-toggle="tab" href="#basic" role="tab"><i class="fas fa-cog me-2"></i>基础设置</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 fw-bold" id="branding-tab" data-bs-toggle="tab" href="#branding" role="tab"><i class="fas fa-paint-brush me-2"></i>品牌与 SEO</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 fw-bold" id="email-tab" data-bs-toggle="tab" href="#email" role="tab"><i class="fas fa-envelope me-2"></i>邮件配置</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 fw-bold" id="security-tab" data-bs-toggle="tab" href="#security" role="tab"><i class="fas fa-shield-alt me-2"></i>安全与业务</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3 fw-bold" id="api-tab" data-bs-toggle="tab" href="#api" role="tab"><i class="fas fa-code me-2"></i>API 配置</a>
                    </li>
                </ul>

                <form method="POST" class="p-4" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="tab-content" id="settingsTabsContent">
                        <!-- 基础设置 -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel">
                            <?php if ($success): ?><div class="alert alert-success small"><?php echo $success; ?></div><?php endif; ?>
                            <?php if ($error): ?><div class="alert alert-danger small"><?php echo $error; ?></div><?php endif; ?>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">站点名称</label>
                                    <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">站点 URL (需包含 http/https)</label>
                                    <input type="url" name="site_url" class="form-control" value="<?php echo htmlspecialchars($current_settings['site_url']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">后台路径</label>
                                    <input type="text" name="admin_path" class="form-control" value="<?php echo htmlspecialchars($current_settings['admin_path']); ?>" required>
                                    <div class="form-text smaller">修改后需手动更改文件夹名称！</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">修改授权手续费 (元)</label>
                                    <input type="number" name="change_detail_fee" class="form-control" value="<?php echo $current_settings['change_detail_fee']; ?>" step="0.01">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">注册赠送余额 (元)</label>
                                    <input type="number" name="reg_gift" class="form-control" value="<?php echo $current_settings['reg_gift']; ?>" step="0.01">
                                </div>
                                <div class="col-md-6 d-flex align-items-center mt-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="allow_register" <?php echo $current_settings['allow_register'] === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label small fw-bold">允许新用户注册</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 品牌与 SEO -->
                        <div class="tab-pane fade" id="branding" role="tabpanel">
                            <h6 class="fw-bold text-primary mb-3">品牌展示 (留空不显示)</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">网站 Logo</label>
                                    <input type="file" name="site_logo" class="form-control mb-2" accept="image/*">
                                    <?php if (!empty($current_settings['site_logo'])): ?>
                                        <div class="mt-2"><img src="<?php echo BASE_URL . $current_settings['site_logo']; ?>" style="max-height: 50px;" class="img-thumbnail"></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Favicon (ICO)</label>
                                    <input type="file" name="site_favicon" class="form-control mb-2" accept=".ico,image/x-icon,image/png">
                                    <?php if (!empty($current_settings['site_favicon'])): ?>
                                        <div class="mt-2"><img src="<?php echo BASE_URL . $current_settings['site_favicon']; ?>" style="max-height: 32px;" class="img-thumbnail"></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">ICP 备案号</label>
                                    <input type="text" name="site_icp" class="form-control" value="<?php echo htmlspecialchars($current_settings['site_icp'] ?? ''); ?>" placeholder="如：京ICP备12345678号">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">软件著作权号</label>
                                    <input type="text" name="site_copyright_no" class="form-control" value="<?php echo htmlspecialchars($current_settings['site_copyright_no'] ?? ''); ?>" placeholder="如：2024SR123456">
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mb-3">SEO 优化 (提升搜索排名)</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold">网站描述 (Description)</label>
                                    <textarea name="site_description" class="form-control" rows="3" placeholder="简要描述您的网站，建议 100 字以内"><?php echo htmlspecialchars($current_settings['site_description'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">关键词 (Keywords, 逗号分隔)</label>
                                    <input type="text" name="site_keywords" class="form-control" value="<?php echo htmlspecialchars($current_settings['site_keywords'] ?? ''); ?>" placeholder="授权系统, 自动发卡, 软件分销">
                                </div>
                            </div>
                        </div>

                        <!-- 邮件配置 -->
                        <div class="tab-pane fade" id="email" role="tabpanel">
                            <h6 class="fw-bold text-primary mb-3">SMTP 发送服务器</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label class="form-label small fw-bold">SMTP 服务器</label>
                                    <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($current_settings['smtp_host']); ?>" placeholder="smtp.qq.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">端口</label>
                                    <input type="text" name="smtp_port" class="form-control" value="<?php echo htmlspecialchars($current_settings['smtp_port']); ?>" placeholder="465">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">SMTP 账号</label>
                                    <input type="text" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($current_settings['smtp_user']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">SMTP 密码/授权码</label>
                                    <input type="password" name="smtp_pass" class="form-control" value="<?php echo htmlspecialchars($current_settings['smtp_pass']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">发件人名称</label>
                                    <input type="text" name="smtp_from_name" class="form-control" value="<?php echo htmlspecialchars($current_settings['smtp_from_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">发件人邮箱</label>
                                    <input type="text" name="smtp_from" class="form-control" value="<?php echo htmlspecialchars($current_settings['smtp_from']); ?>">
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="input-group">
                                        <input type="email" id="test_email_to" class="form-control" placeholder="输入接收邮箱进行测试">
                                        <button type="button" class="btn btn-outline-primary" onclick="testEmail()">发送测试邮件</button>
                                    </div>
                                </div>
                            </div>

                            <h6 class="fw-bold text-primary mb-3">通知模板设置 (支持 HTML)</h6>
                            <div class="row g-3">
                                <?php 
                                $notifies = [
                                    'login' => '用户登录', 'buy' => '购买授权', 'mod' => '修改授权',
                                    'bind' => '绑定授权', 'agent_up' => '代理升级', 'agent_gen' => '下级生成'
                                ];
                                foreach ($notifies as $key => $name):
                                ?>
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="global_notify_<?php echo $key; ?>" <?php echo $current_settings['global_notify_'.$key] === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label small fw-bold"><?php echo $name; ?>通知</label>
                                    </div>
                                    <textarea name="email_tpl_<?php echo $key; ?>" class="form-control font-monospace smaller" rows="3"><?php echo htmlspecialchars($current_settings['email_tpl_'.$key]); ?></textarea>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- 安全与业务 -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-primary mb-3">图形验证码</h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">类型</label>
                                        <select name="captcha_type" class="form-select">
                                            <option value="1" <?php echo $current_settings['captcha_type'] == '1' ? 'selected' : ''; ?>>纯数字</option>
                                            <option value="2" <?php echo $current_settings['captcha_type'] == '2' ? 'selected' : ''; ?>>纯字母</option>
                                            <option value="3" <?php echo $current_settings['captcha_type'] == '3' ? 'selected' : ''; ?>>数字字母混合</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">长度</label>
                                        <input type="number" name="captcha_length" class="form-control" value="<?php echo $current_settings['captcha_length']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-primary mb-3">注册与授权业务</h6>
                                    <div class="mb-3">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="reg_email_verify" <?php echo $current_settings['reg_email_verify'] === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label small fw-bold">强制注册邮箱验证码校验</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="mod_license_email_verify" <?php echo $current_settings['mod_license_email_verify'] === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label small fw-bold">修改授权信息需邮箱验证</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="force_hwid" <?php echo $current_settings['force_hwid'] === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label small fw-bold">强制机器码校验</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">退费比例 (0-1)</label>
                                        <input type="number" name="license_refund_ratio" class="form-control" value="<?php echo $current_settings['license_refund_ratio']; ?>" step="0.01">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">限制注册邮箱后缀 (逗号分隔)</label>
                                        <input type="text" name="reg_email_suffix" class="form-control" value="<?php echo htmlspecialchars($current_settings['reg_email_suffix']); ?>" placeholder="@qq.com,@gmail.com">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- API 配置 -->
                        <div class="tab-pane fade" id="api" role="tabpanel">
                            <h6 class="fw-bold text-primary mb-3">核心通讯接口</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">API 通讯密钥 (用于签名校验)</label>
                                <div class="input-group">
                                    <input type="text" id="api_key" name="api_key" class="form-control font-monospace" value="<?php echo htmlspecialchars($current_settings['api_key']); ?>" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateApiKey()">重新生成</button>
                                </div>
                            </div>
                            <div class="alert alert-warning small">
                                <i class="fas fa-exclamation-triangle me-2"></i>警告：修改 API 密钥会导致所有已对接的客户端无法通过验证，请谨慎操作！
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill">
                            <i class="fas fa-save me-2"></i> 保存所有设置
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function generateApiKey() {
    if (confirm('确定要重新生成 API 密钥吗？这将导致现有客户端对接失效！')) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let key = '';
        for (let i = 0; i < 32; i++) key += chars.charAt(Math.floor(Math.random() * chars.length));
        document.getElementById('api_key').value = key;
    }
}

function testEmail() {
    const to = document.getElementById('test_email_to').value;
    if (!to) { alert('请输入接收邮箱'); return; }
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerText = '正在发送...';
    
    fetch('admin_settings.php?action=test_email&email=' + encodeURIComponent(to))
        .then(res => res.json())
        .then(data => {
            alert(data.msg);
            btn.disabled = false;
            btn.innerText = '发送测试邮件';
        })
        .catch(() => {
            alert('请求失败');
            btn.disabled = false;
            btn.innerText = '发送测试邮件';
        });
}
</script>

<?php
$content = ob_get_clean();
render_layout($content, $page_title, 'admin');
?>
