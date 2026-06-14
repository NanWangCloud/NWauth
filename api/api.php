<?php
/**
 * 作者：任意
 * qq：2908286914
 */
define('IN_SYSTEM', true);
// api.php
header('Content-Type: application/json');
require_once '../core/auth.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'verify':
        $license_key = $_REQUEST['license_key'] ?? '';
        $software_id = 1; // 锁定单软件 ID
        $hwid = $_REQUEST['hwid'] ?? '';
        $domain = $_REQUEST['domain'] ?? $_SERVER['HTTP_HOST'] ?? '';

        if (!$license_key) {
            echo json_encode(['status' => 'error', 'msg' => '缺少授权密钥'], JSON_UNESCAPED_UNICODE);
            break;
        }

        $result = verifyLicense($license_key, $software_id, $hwid, $domain);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        break;

    case 'check_update':
        $software_id = 1; // 锁定单软件 ID
        $current_version = $_GET['version'] ?? '';

        global $pdo;
        $stmt = $pdo->prepare("SELECT version, update_url, update_log FROM software WHERE id = ?");
        $stmt->execute([$software_id]);
        $software = $stmt->fetch();

        if ($software && version_compare($software['version'], $current_version, '>')) {
            echo json_encode([
                'status' => 'update_available',
                'new_version' => $software['version'],
                'url' => $software['update_url'],
                'log' => $software['update_log']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'latest', 'msg' => '当前已是最新版本'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'msg' => '未知的 API 动作'], JSON_UNESCAPED_UNICODE);
        break;
}

/**
 * 作者：任意
 * qq：2908286914
 */
?>
