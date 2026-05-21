<?php
/**
 * DMCA Panel 配置文件
 *
 * 复制此文件为 config.php 并填入你的信息。
 * 或者访问 /install.php 通过安装向导自动生成。
 */

// ** 数据库设置 **
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'dmca_panel');
define('DB_USER', 'root');
define('DB_PASS', '');

// ** 数据表前缀（多实例时使用，一般为空）**
define('DB_PREFIX', '');

// ** Rustracker 黑名单 API **
define('RUSTRACKER_API', 'http://localhost:3000/api/blacklist');
define('RUSTRACKER_TOKEN', '');

// ** 管理员账号（安装时自动生成）**
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', '');

// ** 认证密钥（安装时自动生成）**
define('AUTH_KEY', '');
define('CSRF_SECRET', '');
