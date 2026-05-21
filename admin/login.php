<?php
/**
 * 管理员登录
 */

require_once __DIR__ . '/../includes/functions.php';
requireConfig();

session_start();

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = '请输入用户名和密码';
    } else {
        $authed = false;

        try {
            $pdo = getDB();
            $tbl = DB_PREFIX . 'admins';
            $stmt = $pdo->prepare("SELECT password_hash FROM `$tbl` WHERE username = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $hash = $stmt->fetchColumn();

            if ($hash && password_verify($password, $hash)) {
                $authed = true;
            }
        } catch (PDOException $e) {
            // 表不存在 → 回退到旧常量
            if (defined('ADMIN_USER') && hash_equals(ADMIN_USER, $username)
                && defined('ADMIN_PASS_HASH') && password_verify($password, ADMIN_PASS_HASH)) {
                $authed = true;
            }
        }

        if ($authed) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $username;
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?><!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title data-i18n="login.title">管理员登录 — DMCA Panel</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="brand-bar"></div>

<nav class="topnav">
    <div class="topnav-inner">
        <a href="../index.php" class="topnav-brand" data-i18n="nav.brand">DMCA Panel</a>
        <div class="topnav-right">
            <button type="button" id="lang-switch" class="lang-switch" data-i18n="lang.switch">English</button>
            <span class="topnav-link" data-i18n="login.title">管理员登录</span>
        </div>
    </div>
</nav>

<div class="login-wrap">
<div class="login-card">
    <div class="card">

        <h2 class="login-title" data-i18n="login.title">管理员登录</h2>

        <?php if ($error): ?>
        <div class="alert alert-error mt-16"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="post" class="mt-24">
            <div class="form-group">
                <label class="form-label" for="username" data-i18n="login.username">用户名</label>
                <input type="text" id="username" name="username" class="form-input"
                       value="<?php echo h($_POST['username'] ?? ''); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="password" data-i18n="login.password">密码</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" data-i18n="login.button">登录</button>
        </form>

        <p class="text-sm text-center mt-16">
            <a href="../index.php" data-i18n="login.back">&larr; 返回举报页面</a>
        </p>

    </div>
</div>
</div>

<script src="../assets/i18n.js"></script>
</body>
</html>
