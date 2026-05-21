<?php
/**
 * 管理后台 — Tracker API 设置
 */

require_once __DIR__ . '/../includes/functions.php';
requireConfig();

session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$csrf = csrf_token();
$message = '';
$msg_type = '';
$test_result = '';

// —— 处理保存 ——
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $message = '安全校验失败，请刷新页面后重试。';
        $msg_type = 'error';
    } elseif (isset($_POST['save'])) {
        $api   = trim($_POST['rustracker_api'] ?? '');
        $token = trim($_POST['rustracker_token'] ?? '');

        $config_path = __DIR__ . '/../config.php';
        $config = file_get_contents($config_path);

        // 替换 RUSTRACKER_API
        $config = preg_replace(
            "/define\('RUSTRACKER_API',\s*'[^']*'\);/",
            "define('RUSTRACKER_API', '" . addcslashes($api, "'\\") . "');",
            $config
        );
        // 替换 RUSTRACKER_TOKEN
        $config = preg_replace(
            "/define\('RUSTRACKER_TOKEN',\s*'[^']*'\);/",
            "define('RUSTRACKER_TOKEN', '" . addcslashes($token, "'\\") . "');",
            $config
        );

        $written = @file_put_contents($config_path, $config);
        if ($written !== false) {
            $message = '设置已保存。';
            $msg_type = 'success';
        } else {
            $message = '无法写入 config.php，请检查文件权限。';
            $msg_type = 'error';
        }

    } elseif (isset($_POST['test'])) {
        // 测试连接
        $api   = trim($_POST['rustracker_api'] ?? '');
        $token = trim($_POST['rustracker_token'] ?? '');

        if ($api === '') {
            $test_result = '请先输入 API 地址。';
        } elseif (!function_exists('curl_init')) {
            $test_result = '服务器未安装 curl 扩展，无法测试。';
        } else {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $api,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS     => json_encode(['info_hash' => str_repeat('0', 40)]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                $test_result = '连接失败：' . $curl_error;
            } elseif ($http_code >= 200 && $http_code < 500) {
                $test_result = '连接成功 — HTTP ' . $http_code . '。响应：' . substr($response, 0, 300);
            } else {
                $test_result = '服务器返回 HTTP ' . $http_code . '。响应：' . substr($response, 0, 300);
            }
        }
    }
}

// —— 当前值 ——
$current_api   = RUSTRACKER_API;
$current_token = RUSTRACKER_TOKEN;

// —— 表单中显示的值（提交后回显）——
$form_api   = $_POST['rustracker_api'] ?? $current_api;
$form_token = $_POST['rustracker_token'] ?? $current_token;
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tracker 设置 — DMCA Panel</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="brand-bar"></div>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <a href="index.php" class="sidebar-brand">DMCA Panel</a>
        <ul class="sidebar-nav">
            <li><a href="index.php">举报列表</a></li>
            <li><a href="settings.php" class="active">Tracker 设置</a></li>
            <li><a href="logout.php">退出登录</a></li>
        </ul>
    </aside>

    <main class="admin-main">

        <div class="admin-header">
            <h1>Tracker API 设置</h1>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $msg_type === 'success' ? 'success' : ($msg_type === 'error' ? 'error' : 'info'); ?>">
            <?php echo h($message); ?>
        </div>
        <?php endif; ?>

        <div class="card" style="max-width:640px;">

            <h2 class="mb-16">Rustracker 黑名单 API</h2>
            <p class="text-sm mb-24">审核通过举报后，系统将调用此接口推送 Info Hash 至 Rustracker 黑名单。</p>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">

                <div class="form-group">
                    <label class="form-label" for="rustracker_api">API 地址</label>
                    <input type="url" id="rustracker_api" name="rustracker_api" class="form-input"
                           value="<?php echo h($form_api); ?>"
                           placeholder="http://localhost:3000/api/blacklist">
                    <p class="form-hint">Rustracker 的 POST /api/blacklist 端点地址</p>
                </div>

                <div class="form-group">
                    <label class="form-label" for="rustracker_token">Bearer Token</label>
                    <input type="text" id="rustracker_token" name="rustracker_token" class="form-input"
                           value="<?php echo h($form_token); ?>"
                           placeholder="your-secret-token">
                    <p class="form-hint">对应 Rustracker 的 RUSTRACKER_ADMIN_TOKEN</p>
                </div>

                <div style="display:flex;gap:12px;">
                    <button type="submit" name="save" class="btn btn-primary">保存设置</button>
                    <button type="submit" name="test" class="btn btn-outline">测试连接</button>
                </div>
            </form>

            <?php if ($test_result): ?>
            <div class="mt-16">
                <pre class="config-code"><?php echo h($test_result); ?></pre>
            </div>
            <?php endif; ?>

        </div>

    </main>
</div>

</body>
</html>
