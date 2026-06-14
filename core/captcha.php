<?php
session_start();

function generateCaptcha() {
    $type = (int)get_captcha_setting('captcha_type', 1);
    $length = (int)get_captcha_setting('captcha_length', 4);
    
    $chars = '';
    if ($type === 1) {
        $chars = '0123456789';
    } elseif ($type === 2) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    } else {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    $_SESSION['captcha'] = strtolower($code);
    
    // 创建图像
    $width = $length * 20;
    $height = 40;
    $image = imagecreatetruecolor($width, $height);
    
    $bg = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $bg);
    
    // 添加干扰线
    for ($i = 0; $i < 5; $i++) {
        $color = imagecolorallocate($image, rand(100, 200), rand(100, 200), rand(100, 200));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $color);
    }
    
    // 添加验证码文字
    for ($i = 0; $i < $length; $i++) {
        $color = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));
        imagestring($image, 5, 5 + ($i * 18), 12, $code[$i], $color);
    }
    
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
}

// 模拟 get_setting 因为这里没包含 config.php
function get_captcha_setting($key, $default = '') {
    // 这里简单处理，实际上在调用前会 include config.php
    global $pdo;
    if (!$pdo) {
        define('IN_SYSTEM', true);
        require_once 'config.php';
    }
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $res = $stmt->fetchColumn();
    return $res !== false ? $res : $default;
}

generateCaptcha();
