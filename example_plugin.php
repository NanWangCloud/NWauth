<?php
/**
 * FastAuth 授权系统 - 插件/程序对接示例 (PHP 版)
 * 版本：v0.2.0
 */

class FastAuthConnector {
    private $api_url;
    private $license_key;
    private $cache_file;
    private $cache_ttl = 3600; // 本地缓存时间 (秒)

    public function __construct($api_url, $license_key) {
        $this->api_url = rtrim($api_url, '/') . '/api/api.php';
        $this->license_key = $license_key;
        $this->cache_file = __DIR__ . '/.auth_cache';
    }

    /**
     * 执行授权验证
     */
    public function checkAuth() {
        // 1. 尝试从本地缓存读取
        if ($this->checkCache()) {
            return true;
        }

        // 2. 发起远程验证请求
        $hwid = $this->getMachineId();
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $params = [
            'action' => 'verify',
            'license_key' => $this->license_key,
            'hwid' => $hwid,
            'domain' => $domain
        ];

        $response = $this->postRequest($params);
        $result = json_decode($response, true);

        if ($result && isset($result['status']) && $result['status'] === 'success') {
            // 验证通过，写入本地缓存
            $this->writeCache($result);
            return true;
        }

        // 验证失败，抛出异常或处理错误
        $msg = $result['msg'] ?? '远程服务器连接失败';
        die("<div style='padding:20px; background:#fff5f5; border:1px solid #feb2b2; color:#c53030; border-radius:8px; font-family:sans-serif;'>
                <b>[授权错误]</b>：{$msg} <br><br>
                请前往授权中心检查您的授权状态。
             </div>");
    }

    /**
     * 获取机器唯一标识 (示例)
     */
    private function getMachineId() {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return md5(getcurrentuser() . gethostname());
        } else {
            // Linux 下可结合网卡 MAC 或 CPU 信息
            return md5(php_uname('n') . php_uname('m'));
        }
    }

    private function postRequest($data) {
        $url = $this->api_url . '?action=' . $data['action'];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    private function checkCache() {
        if (file_exists($this->cache_file)) {
            $data = json_decode(file_get_contents($this->cache_file), true);
            if ($data && (time() - $data['time'] < $this->cache_ttl)) {
                return true;
            }
        }
        return false;
    }

    private function writeCache($result) {
        $cache_data = [
            'time' => time(),
            'license_key' => $this->license_key,
            'expires_at' => $result['expires_at'] ?? ''
        ];
        file_put_contents($this->cache_file, json_encode($cache_data));
    }
}

// --- 使用示例 ---

// 1. 配置信息 (请根据实际情况修改)
$auth_api = "http://您的授权站域名.com"; 
$my_license = "xxxx-xxxx-xxxx"; // 用户购买后填写的授权码

// 2. 初始化并验证
$auth = new FastAuthConnector($auth_api, $my_license);
$auth->checkAuth();

// 3. 验证通过后的业务代码
echo "<h1>恭喜，授权验证通过！</h1>";
echo "<p>这是受保护的受限内容。</p>";
