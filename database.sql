-- ----------------------------
-- FastAuth Database Export
-- ----------------------------

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for users
-- ----------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `email_notify_settings` text DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `is_agent` tinyint(1) DEFAULT '0',
  `agent_level` int(11) DEFAULT '0',
  `inviter_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for admins
-- ----------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `email_notify_settings` text DEFAULT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of admins (Password: 123456)
-- ----------------------------
INSERT IGNORE INTO `admins` (`id`, `username`, `password`, `role`) VALUES (1, 'admin', '$2y$10$8.N.B.H.O.G2E5p/U5vS6Ew8N0wWCOWfO6pYlP0Tq/X.8B9Y1W2X3Z', 'superadmin');

-- ----------------------------
-- Table structure for software
-- ----------------------------
CREATE TABLE IF NOT EXISTS `software` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `version` varchar(20) DEFAULT NULL,
  `update_url` varchar(255) DEFAULT NULL,
  `update_log` text,
  `price_per_month` decimal(10,2) DEFAULT '0.00',
  `price_month` decimal(10,2) DEFAULT NULL,
  `price_quarter` decimal(10,2) DEFAULT NULL,
  `price_half_year` decimal(10,2) DEFAULT NULL,
  `price_year` decimal(10,2) DEFAULT NULL,
  `price_3year` decimal(10,2) DEFAULT NULL,
  `price_permanent` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of software
-- ----------------------------
INSERT IGNORE INTO `software` VALUES (1, '极速授权标准版', '1.2.0', 'http://example.com/download', '修复了若干已知 Bug', 99.00, '2026-04-18 18:00:00');
INSERT IGNORE INTO `software` VALUES (2, '极速授权专业版', '2.0.1', 'http://example.com/download', '新增 API 接口限流功能', 199.00, '2026-04-18 18:00:00');

-- ----------------------------
-- Table structure for licenses
-- ----------------------------
CREATE TABLE IF NOT EXISTS `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `software_id` int(11) DEFAULT NULL,
  `license_key` varchar(100) NOT NULL,
  `hwid` varchar(255) DEFAULT NULL,
  `qq` varchar(20) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_key` (`license_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for coupons
-- ----------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('amount', 'percent') DEFAULT 'amount',
  `value` decimal(10,2) NOT NULL,
  `min_amount` decimal(10,2) DEFAULT '0.00',
  `is_used` tinyint(1) DEFAULT '0',
  `used_by` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for cards
-- ----------------------------
CREATE TABLE IF NOT EXISTS `cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_key` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `software_id` int(11) DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `used_by` int(11) DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_key` (`card_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for articles
-- ----------------------------
CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text,
  `author_id` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'announcement',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for feedback
-- ----------------------------
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text,
  `reply_content` text,
  `status` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for purchase_logs
-- ----------------------------
CREATE TABLE IF NOT EXISTS `purchase_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `software_id` int(11) NOT NULL,
  `months` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for finance_logs
-- ----------------------------
CREATE TABLE IF NOT EXISTS `finance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('recharge','consume','refund','commission') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for admin_logs
-- ----------------------------
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `target` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for agent_levels
-- ----------------------------
CREATE TABLE IF NOT EXISTS `agent_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `discount` decimal(3,2) DEFAULT '1.00',
  `min_recharge` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of agent_levels
-- ----------------------------
INSERT IGNORE INTO `agent_levels` VALUES (1, '普通代理', 0.90, 0.00);
INSERT IGNORE INTO `agent_levels` VALUES (2, '高级代理', 0.80, 500.00);
INSERT IGNORE INTO `agent_levels` VALUES (3, '核心代理', 0.70, 2000.00);

-- ----------------------------
-- Table structure for settings
-- ----------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `key` varchar(50) NOT NULL,
  `value` text,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of settings
-- ----------------------------
INSERT IGNORE INTO `settings` VALUES ('site_name', '极速授权管理系统');
INSERT IGNORE INTO `settings` VALUES ('site_url', 'http://localhost');
INSERT IGNORE INTO `settings` VALUES ('allow_register', '1');
INSERT IGNORE INTO `settings` VALUES ('force_hwid', '1');
INSERT IGNORE INTO `settings` VALUES ('reg_gift', '0.00');
INSERT IGNORE INTO `settings` VALUES ('api_key', 'sk_test_1234567890');
INSERT IGNORE INTO `settings` VALUES ('admin_path', 'admin');
INSERT IGNORE INTO `settings` VALUES ('change_detail_fee', '10.00');
INSERT IGNORE INTO `settings` VALUES ('reg_email_verify', '0');
INSERT IGNORE INTO `settings` VALUES ('reg_email_suffix', '');
INSERT IGNORE INTO `settings` VALUES ('smtp_host', '');
INSERT IGNORE INTO `settings` VALUES ('smtp_port', '465');
INSERT IGNORE INTO `settings` VALUES ('smtp_user', '');
INSERT IGNORE INTO `settings` VALUES ('smtp_pass', '');
INSERT IGNORE INTO `settings` VALUES ('smtp_from', '');
INSERT IGNORE INTO `settings` VALUES ('smtp_from_name', '极速授权系统');
INSERT IGNORE INTO `settings` VALUES ('smtp_secure', 'ssl');
INSERT IGNORE INTO `settings` VALUES ('email_tpl_login', '您的账号 {username} 于 {time} 在 IP {ip} 登录成功。');
INSERT IGNORE INTO `settings` VALUES ('email_tpl_buy', '您已成功购买 {software_name} 授权，时长：{duration}，授权码：{key}。');
INSERT IGNORE INTO `settings` VALUES ('email_tpl_mod', '您的授权 {key} 资料已修改成功。');
INSERT IGNORE INTO `settings` VALUES ('email_tpl_bind', '您的授权 {key} 已成功绑定机器码 {hwid}。');
INSERT IGNORE INTO `settings` VALUES ('email_tpl_agent_up', '恭喜！您已成功升级为 {level_name}。');
INSERT IGNORE INTO `settings` VALUES ('email_tpl_agent_gen', '您作为代理生成的授权码：{key} 已成功发放。');
INSERT IGNORE INTO `settings` VALUES ('global_notify_login', '0');
INSERT IGNORE INTO `settings` VALUES ('global_notify_buy', '1');
INSERT IGNORE INTO `settings` VALUES ('global_notify_mod', '1');
INSERT IGNORE INTO `settings` VALUES ('global_notify_bind', '1');
INSERT IGNORE INTO `settings` VALUES ('global_notify_agent_up', '1');
INSERT IGNORE INTO `settings` VALUES ('global_notify_agent_gen', '1');
INSERT IGNORE INTO `settings` VALUES ('captcha_type', '1');
INSERT IGNORE INTO `settings` VALUES ('captcha_length', '4');
INSERT IGNORE INTO `settings` VALUES ('license_refund_ratio', '0.8');
INSERT IGNORE INTO `settings` VALUES ('mod_license_email_verify', '0');

-- ----------------------------
-- Table structure for software_updates
-- ----------------------------
CREATE TABLE IF NOT EXISTS `software_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `software_id` int(11) NOT NULL,
  `version` varchar(20) NOT NULL,
  `update_url` varchar(255) DEFAULT NULL,
  `update_log` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
