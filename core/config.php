<?php
/**
 * 核心配置文件
 */
if (!defined('IN_SYSTEM')) {
    exit('Unauthorized Access');
}

// 移除严格的 PHP 版本检查，支持 PHP 7.4+
if (PHP_MAJOR_VERSION < 7) {
    die("Error: This system requires PHP 7.4 or higher.");
}

// 错误处理优化：显示具体错误而不是白屏
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_exception_handler(function ($e) {
    echo "<h3>系统运行错误</h3>";
    echo "<p>错误信息: " . $e->getMessage() . "</p>";
    echo "<p>所在文件: " . $e->getFile() . " 第 " . $e->getLine() . " 行</p>";
    exit;
});

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'auth_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_PATH', dirname(__DIR__));
define('CORE_PATH', __DIR__);
define('SYSTEM_VERSION', '0.2.0');
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
// 自动计算全路径 BASE_URL，确保在各种环境下跳转正常
if (!defined('BASE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_dir = str_replace('\\', '/', dirname($script_name));
    $base_dir = preg_replace('/\/(user|admin|api|core|includes)$/', '', $base_dir);
    $base_dir = rtrim($base_dir, '/');
    define('BASE_URL', $protocol . $host . ($base_dir ?: '') . '/');
}
function filter_input_data(&$data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            filter_input_data($data[$key]);
        }
    } else {
        $data = trim($data);
    }
}
filter_input_data($_GET);
filter_input_data($_POST);
filter_input_data($_REQUEST);
function validate_input($value, $type = 'string', $min = 0, $max = 255) {
    if ($value === null || $value === '') return false;
    $len = mb_strlen($value);
    if ($len < $min || $len > $max) return false;
    switch ($type) {
        case 'username':
            return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $value);
        case 'password':
            return mb_strlen($value) >= 6 && mb_strlen($value) <= 30;
        case 'card_key':
            return preg_match('/^[A-Z0-9-]{10,50}$/', $value);
        case 'qq':
            return preg_match('/^[1-9][0-9]{4,10}$/', $value);
        case 'domain':
            // 严格单域名验证，排除 * 和多域名分隔符
            if (strpos($value, '*') !== false || strpos($value, ',') !== false || strpos($value, ';') !== false) return false;
            return preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?$/i', $value);
        case 'number':
            return is_numeric($value);
        case 'email':
            return filter_var($value, FILTER_VALIDATE_EMAIL);
        default:
            return true;
    }
}
function get_setting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetchColumn();
    return $res !== false ? $res : $default;
}
function update_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
    return $stmt->execute([$value, $key]);
}
function redirect($url) {
    if (strpos($url, 'http') !== 0) {
        // 使用相对全路径，避免 host 识别问题
        $base_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $base_dir = preg_replace('/\/(user|admin|api|core|includes)$/', '', $base_dir);
        $url = rtrim($base_dir, '/') . '/' . ltrim($url, '/');
    }
    
    if (!headers_sent()) {
        header("Location: $url", true, 302);
    } else {
        echo "<script>window.location.href='{$url}';</script>";
    }
    exit();
}
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        $message = addslashes($msg['message']);
        echo "<script>alert('{$message}');</script>";
    }
}
/**
 * 作者：任意
 * qq：2908286914
 */
?>