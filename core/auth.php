<?php
/**
 * 作者：任意
 * qq：2908286914
 */

// 保护核心文件，禁止直接访问
if (!defined('IN_SYSTEM')) {
    exit('Access Denied');
}

require_once 'config.php';
require_once 'template.php';

session_start();

/**
 * CSRF Token 生成与验证
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 验证图形验证码
 */
function verifyCaptcha($code) {
    if (empty($code) || empty($_SESSION['captcha'])) return false;
    $res = strtolower($code) === $_SESSION['captcha'];
    unset($_SESSION['captcha']); // 验证一次即失效
    return $res;
}

/**
 * 发送邮件通知 (核心函数)
 */
function send_email($to, $subject, $content) {
    $smtp_host = get_setting('smtp_host');
    if (empty($smtp_host)) return false; // 未配置 SMTP 不发送

    $from = get_setting('smtp_from');
    $from_name = get_setting('smtp_from_name', '极速授权系统');
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $from_name <$from>" . "\r\n";

    // 注意：这里使用 PHP mail()，实际生产环境建议用 PHPMailer 配合 SMTP
    // 鉴于目前环境限制，我们提供逻辑框架
    try {
        return @mail($to, $subject, $content, $headers);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 触发通知逻辑
 */
function trigger_notification($user_id, $type, $params = [], $is_admin = false) {
    global $pdo;
    
    // 检查全局开关
    if (get_setting("global_notify_$type", '0') !== '1') return;

    // 获取用户信息和个人通知设置
    $table = $is_admin ? 'admins' : 'users';
    $stmt = $pdo->prepare("SELECT email, email_notify_settings FROM $table WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['email'])) return;

    $notify_settings = json_decode($user['email_notify_settings'] ?? '{}', true);
    // 如果用户明确关闭了该通知，则不发送 (默认开启)
    if (isset($notify_settings[$type]) && $notify_settings[$type] === false) return;

    // 获取并填充模板
    $tpl = get_setting("email_tpl_$type", '');
    if (empty($tpl)) return;

    $subject = "系统通知 - " . get_setting('site_name');
    $content = $tpl;
    foreach ($params as $key => $val) {
        $content = str_replace("{{$key}}", $val, $content);
    }

    send_email($user['email'], $subject, $content);
}

/**
 * 生成并发送邮箱验证码
 */
function send_email_code($email, $type = 'reg') {
    $code = rand(100000, 999999);
    $_SESSION[$type . '_email_code'] = (string)$code;
    $_SESSION[$type . '_email_target'] = $email;
    $_SESSION[$type . '_email_time'] = time();

    $site_name = get_setting('site_name');
    $subject = "【{$site_name}】您的验证码是 {$code}";
    $content = "您正在进行验证操作，您的验证码为：<b>{$code}</b>，请在 10 分钟内按提示输入。如非本人操作，请忽略此邮件。";

    return send_email($email, $subject, $content);
}


/**
 * 检查是否已登录
 */
/**
 * 尝试登录 (用户)
 */
function login($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['status' => false, 'msg' => '用户不存在'];
    }

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = false;
        $_SESSION['is_agent'] = $user['is_agent'];
        
        // 触发登录通知
        trigger_notification($user['id'], 'login', [
            'username' => $user['username'],
            'time' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        return ['status' => true];
    }
    return ['status' => false, 'msg' => '密码错误'];
}

/**
 * 尝试登录 (管理员)
 */
function adminLogin($username, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return ['status' => false, 'msg' => '管理员不存在'];
    }

    if (password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['is_admin'] = true;
        
        $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$admin['id']]);
        return ['status' => true];
    }
    return ['status' => false, 'msg' => '密码错误'];
}

/**
 * 注册新用户
 */
function register($username, $password, $email) {
    global $pdo;
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, agent_level) VALUES (?, ?, ?, ?)");
    try {
        return $stmt->execute([$username, $hashedPassword, $email, 0]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * 登出
 */
function logout() {
    $is_admin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);
    $admin_path = get_setting('admin_path', 'admin');
    
    // 彻底清除会话
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    session_destroy();
    
    if ($is_admin) {
        redirect($admin_path . '/login.php');
    } else {
        redirect('login.php');
    }
}

/**
 * 检查权限
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function isAgentLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_agent']) && $_SESSION['is_agent'] == 1;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isAdminLoggedIn();
}

function hasPermission($permission_name) {
    if (isAdminLoggedIn()) return true;
    return false;
}

function hasActiveLicense() {
    global $pdo;
    if (!isset($_SESSION['user_id']) || isAdminLoggedIn()) return false;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM licenses WHERE user_id = ? AND status = 1 AND expires_at > NOW()");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn() > 0;
}

function requireLogin() {
    if (isAdminLoggedIn()) return; // 管理员已登录，允许访问（或在业务逻辑中处理）
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        redirect(get_setting('admin_path', 'admin') . '/login.php');
    }
}

function requirePermission($permission_name) {
    if (isAdminLoggedIn()) return;
    requireLogin();
}

/**
 * 安全加密密钥生成器 (AES-256 + MD5 + 格式化)
 */
function generateSecureKey($prefix = '') {
    $secret = get_setting('api_key', 'fastauth_secret_key');
    $data = bin2hex(random_bytes(16)) . microtime(true) . uniqid();
    
    // AES-256-CBC 加密
    $method = "AES-256-CBC";
    $key = hash('sha256', $secret, true);
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    
    // 取 MD5
    $hash = md5($encrypted . bin2hex($iv));
    
    // 格式化: 每四字符后面写个- (取前 12 位以匹配 xxxx-xxxx-xxxx 格式)
    $short_hash = substr($hash, 0, 12);
    $formatted = implode('-', str_split($short_hash, 4));
    
    return $prefix ? $prefix . '-' . $formatted : $formatted;
}

function generateKey($prefix = 'LIC') {
    return generateSecureKey($prefix);
}

/**
 * 记录管理员日志
 */
function logAdminAction($action, $target = null) {
    global $pdo;
    if (!isAdminLoggedIn()) return;
    $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target, ip) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['admin_id'], $action, $target, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
}

/**
 * 记录财务日志
 */
function logFinance($user_id, $type, $amount, $remark = null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $balance = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("INSERT INTO finance_logs (user_id, type, amount, balance_after, remark) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $type, $amount, $balance, $remark]);
}

/**
 * 验证授权接口 (API 调用)
 */
function verifyLicense($license_key, $software_id, $hwid, $domain = '') {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM licenses WHERE license_key = ? AND software_id = ? AND status = 1");
    $stmt->execute([$license_key, $software_id]);
    $license = $stmt->fetch();

    if (!$license) return ['status' => 'error', 'msg' => '授权不存在或已失效'];

    if (new DateTime($license['expires_at']) < new DateTime()) {
        return ['status' => 'error', 'msg' => '授权已过期'];
    }

    // 域名校验 (严格匹配)
    if (!empty($license['domain']) && !empty($domain)) {
        if (strtolower($license['domain']) !== strtolower($domain)) {
            return ['status' => 'error', 'msg' => '授权域名不匹配，当前域名：' . $domain];
        }
    }

    // HWID 校验
    if ($license['hwid'] && $hwid && $license['hwid'] !== $hwid) {
        return ['status' => 'error', 'msg' => '机器码不匹配，授权已被锁定'];
    }

    // 如果没有绑定机器码，且本次请求提供了机器码，则进行绑定
    if (empty($license['hwid']) && !empty($hwid)) {
        $stmt = $pdo->prepare("UPDATE licenses SET hwid = ? WHERE id = ?");
        $stmt->execute([$hwid, $license['id']]);
        $license['hwid'] = $hwid;
        
        // 触发绑定通知
        trigger_notification($license['user_id'], 'bind', ['key' => $license_key, 'hwid' => $hwid]);
    }

    // 获取软件版本和更新信息
    $stmt = $pdo->prepare("SELECT name, version FROM software WHERE id = ?");
    $stmt->execute([$software_id]);
    $software = $stmt->fetch();

    return [
        'status' => 'success', 
        'msg' => '授权成功', 
        'expires_at' => $license['expires_at'],
        'software_name' => $software['name'] ?? '',
        'version' => $software['version'] ?? '',
        'update_url' => get_setting('update_package_url', ''),
        'update_log' => $software['update_log'] ?? ''
    ];
}

/**
 * 作者：任意
 * qq：2908286914
 */
?>
