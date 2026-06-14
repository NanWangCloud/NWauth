# 极速授权管理系统 (FastAuth) 使用文档 v0.2.0

一套基于原生 PHP + MySQL 开发的专业级应用授权、分销与用户管理系统。采用浅蓝渐变现代 UI，提供全自动化授权管理解决方案。

---

## 1. 快速安装

### 步骤 A：环境要求
- **PHP**: 7.4.x - 8.2.x
- **MySQL**: 5.7+
- **扩展**: `openssl`, `pdo_mysql`, `gd` (用于验证码)

### 步骤 B：准备数据库
1. 在 MySQL 中创建一个新数据库。
2. 将根目录下的 `database.sql` 导入该数据库。

### 步骤 C：修改配置
编辑 `core/config.php` 文件，填入您的数据库信息及站点 URL。

### 步骤 D：访问系统
- **默认管理员**: `admin` / `123456` (登录后请立即修改)
- **密码重置**: 如忘记密码，可临时访问 `admin/emergency_reset.php` 将 admin 密码重置为 123456（使用后请立即删除该文件）。

---

## 2. 开发接口 (API)

### 授权校验
`POST /api/api.php?action=verify`
- **参数**: 
  - `license_key`: 授权密钥
  - `hwid`: 机器码 (可选，留空则绑定首次调用的设备)
  - `domain`: 授权域名 (可选，默认取当前 Host)
- **返回**: 成功返回 `{"status":"success", ...}`，失败返回 `error` 及错误原因。

### 版本更新
`GET /api/api.php?action=check_update&version=1.0.0`

---

## 3. 对接示例
请参考根目录下的 `example_plugin.php`，展示了如何在 PHP 项目中实现远程授权验证、本地缓存及异常处理。

---

## 4. 版权信息
- **作者**: 任意
- **QQ**: 2908286914
