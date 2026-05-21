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
    } elseif (!hash_equals(ADMIN_USER, $username)) {
        $error = '用户名或密码错误';
    } elseif (!password_verify($password, ADMIN_PASS_HASH)) {
        $error = '用户名或密码错误';
    } else {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        header('Location: index.php');
        exit;
    }
}
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>管理员登录 — DMCA Panel</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="login-wrap">
<div class="login-card">
    <div class="brand-bar"></div>
    <div class="card">

        <h2 class="login-title">管理员登录</h2>

        <?php if ($error): ?>
        <div class="alert alert-error mt-16"><?php echo h($error); ?></div>
        <?php endif; ?>

        <form method="post" class="mt-24">
            <div class="form-group">
                <label class="form-label" for="username">用户名</label>
                <input type="text" id="username" name="username" class="form-input"
                       value="<?php echo h($_POST['username'] ?? ''); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">密码</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">登录</button>
        </form>

        <p class="text-sm text-center mt-16">
            <a href="../index.php">&larr; 返回举报页面</a>
        </p>

    </div>
</div>
</div>

</body>
</html>
