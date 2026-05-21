<?php
/**
 * DMCA Panel 安装向导
 */

define('INSTALLING', true);

if (file_exists(__DIR__ . '/config.php')) {
    header('HTTP/1.1 403 Forbidden');
    die('已安装。如需重新安装，请删除 config.php 后再次访问此页面。');
}

session_start();

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > 3) $step = 1;

$errors = [];
$db_error = '';

// ============================================================
// Step 1: 数据库配置
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    $_SESSION['db_host']   = trim($_POST['db_host'] ?? '');
    $_SESSION['db_port']   = trim($_POST['db_port'] ?? '3306');
    $_SESSION['db_name']   = trim($_POST['db_name'] ?? '');
    $_SESSION['db_user']   = trim($_POST['db_user'] ?? '');
    $_SESSION['db_pass']   = $_POST['db_pass'] ?? '';
    $_SESSION['db_prefix'] = trim($_POST['db_prefix'] ?? '');

    if ($_SESSION['db_host'] === '') $errors[] = '请输入数据库主机';
    if ($_SESSION['db_name'] === '') $errors[] = '请输入数据库名';
    if ($_SESSION['db_user'] === '') $errors[] = '请输入数据库用户名';

    if (empty($errors)) {
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4',
                $_SESSION['db_host'], (int)$_SESSION['db_port']);
            $pdo = new PDO($dsn, $_SESSION['db_user'], $_SESSION['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            $dbName = $_SESSION['db_name'];
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbName`");

            $schema = file_get_contents(__DIR__ . '/schema.sql');
            if ($_SESSION['db_prefix'] !== '') {
                $schema = str_replace('dmca_reports', $_SESSION['db_prefix'] . 'dmca_reports', $schema);
            }
            $pdo->exec($schema);

            // 检查是否已有数据
            $tbl = ($_SESSION['db_prefix'] ?? '') . 'dmca_reports';
            try {
                $cnt = $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
                if ($cnt > 0) {
                    $_SESSION['existing_data'] = (int)$cnt;
                }
            } catch (PDOException $e) {
                // 表不存在时才抛异常，忽略
            }

            header('Location: install.php?step=2');
            exit;
        } catch (PDOException $e) {
            $db_error = '数据库连接失败：' . $e->getMessage();
        }
    }
}

// ============================================================
// Step 2: 管理员 + Rustracker
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $admin_user  = trim($_POST['admin_user'] ?? '');
    $admin_pass  = $_POST['admin_pass'] ?? '';
    $admin_pass2 = $_POST['admin_pass2'] ?? '';
    $rustracker_api   = trim($_POST['rustracker_api'] ?? '');
    $rustracker_token = trim($_POST['rustracker_token'] ?? '');

    if ($admin_user === '') $errors[] = '请输入管理员用户名';
    if (strlen($admin_pass) < 6) $errors[] = '密码至少 6 位';
    if ($admin_pass !== $admin_pass2) $errors[] = '两次密码输入不一致';

    if (empty($errors)) {
        $auth_key   = bin2hex(random_bytes(32));
        $csrf_secret = bin2hex(random_bytes(32));
        $pass_hash  = password_hash($admin_pass, PASSWORD_BCRYPT);

        $config = "<?php\n" .
            "/** DMCA Panel 配置文件 — 由安装向导自动生成 */\n\n" .
            "define('DB_HOST', '" . addcslashes($_SESSION['db_host'], "'\\") . "');\n" .
            "define('DB_PORT', " . (int)$_SESSION['db_port'] . ");\n" .
            "define('DB_NAME', '" . addcslashes($_SESSION['db_name'], "'\\") . "');\n" .
            "define('DB_USER', '" . addcslashes($_SESSION['db_user'], "'\\") . "');\n" .
            "define('DB_PASS', '" . addcslashes($_SESSION['db_pass'], "'\\") . "');\n" .
            "define('DB_PREFIX', '" . addcslashes($_SESSION['db_prefix'] ?? '', "'\\") . "');\n\n" .
            "define('RUSTRACKER_API', '" . addcslashes($rustracker_api, "'\\") . "');\n" .
            "define('RUSTRACKER_TOKEN', '" . addcslashes($rustracker_token, "'\\") . "');\n\n" .
            "define('ADMIN_USER', '" . addcslashes($admin_user, "'\\") . "');\n" .
            "define('ADMIN_PASS_HASH', '" . addcslashes($pass_hash, "'\\") . "');\n\n" .
            "define('AUTH_KEY', '" . $auth_key . "');\n" .
            "define('CSRF_SECRET', '" . $csrf_secret . "');\n";

        $written = @file_put_contents(__DIR__ . '/config.php', $config);
        if ($written !== false) {
            session_destroy();
            header('Location: install.php?step=3');
            exit;
        } else {
            $_SESSION['config_code'] = $config;
            $errors[] = '无法自动写入 config.php，请手动创建文件并粘贴以下内容。';
        }
    }
}

// ============================================================
// 渲染
// ============================================================
function h($s) { echo htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DMCA Panel 安装</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="install-wrap">
<div class="install-card">

<?php if ($step === 3): ?>
    <!-- ============ 安装完成 ============ -->
    <div class="brand-bar"></div>
    <div class="card">
        <h1 class="install-title">安装完成</h1>
        <p class="text-sm text-center mt-8">DMCA Panel 已就绪</p>

        <div class="alert alert-success mt-24">
            config.php 已自动生成。为确保安全，建议删除 install.php。
        </div>

        <div class="mt-24 text-center">
            <a href="admin/login.php" class="btn btn-primary">进入管理后台</a>
        </div>
        <p class="text-sm text-center mt-12">
            <a href="index.php">查看举报提交页面 &rarr;</a>
        </p>
    </div>

<?php else: ?>
    <!-- ============ Step 1 / 2 ============ -->
    <div class="brand-bar"></div>
    <div class="card">

        <h1 class="install-title">DMCA Panel 安装</h1>

        <div class="steps mt-24">
            <div class="step <?php echo $step === 1 ? 'active' : ($step > 1 ? 'done' : ''); ?>">
                <?php echo $step > 1 ? '&#10003;' : ''; ?> 1. 数据库
            </div>
            <div class="step <?php echo $step === 2 ? 'active' : ''; ?>">2. 管理员</div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?><div><?php h($e); ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($db_error): ?>
        <div class="alert alert-error"><?php h($db_error); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['existing_data'])): ?>
        <div class="alert alert-info">检测到数据库中已有 <strong><?php echo (int)$_SESSION['existing_data']; ?></strong> 条举报记录。安装将保留现有数据，仅重新生成配置文件。</div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <!-- Step 1 -->
        <form method="post" action="install.php?step=1">
            <div class="form-group">
                <label class="form-label" for="db_host">数据库主机</label>
                <input type="text" id="db_host" name="db_host" class="form-input"
                       value="<?php h($_SESSION['db_host'] ?? 'localhost'); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="db_port">端口</label>
                <input type="number" id="db_port" name="db_port" class="form-input"
                       value="<?php h($_SESSION['db_port'] ?? '3306'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="db_name">数据库名</label>
                <input type="text" id="db_name" name="db_name" class="form-input"
                       value="<?php h($_SESSION['db_name'] ?? ''); ?>" required>
                <p class="form-hint">如数据库不存在，将自动创建</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="db_user">数据库用户名</label>
                <input type="text" id="db_user" name="db_user" class="form-input"
                       value="<?php h($_SESSION['db_user'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="db_pass">数据库密码</label>
                <input type="password" id="db_pass" name="db_pass" class="form-input"
                       value="<?php h($_SESSION['db_pass'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="db_prefix">表前缀（可选）</label>
                <input type="text" id="db_prefix" name="db_prefix" class="form-input"
                       value="<?php h($_SESSION['db_prefix'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-8">下一步</button>
        </form>

        <?php else: ?>
        <!-- Step 2 -->
        <form method="post" action="install.php?step=2">
            <div class="form-group">
                <label class="form-label" for="admin_user">管理员用户名</label>
                <input type="text" id="admin_user" name="admin_user" class="form-input" required autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label" for="admin_pass">管理员密码</label>
                <input type="password" id="admin_pass" name="admin_pass" class="form-input" required minlength="6">
                <p class="form-hint">至少 6 位</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="admin_pass2">确认密码</label>
                <input type="password" id="admin_pass2" name="admin_pass2" class="form-input" required>
            </div>

            <h2 class="rustracker-section-title mt-24 mb-16">Rustracker 黑名单集成（可选）</h2>

            <div class="form-group">
                <label class="form-label" for="rustracker_api">Blacklist API 地址</label>
                <input type="url" id="rustracker_api" name="rustracker_api" class="form-input"
                       value="<?php h($_POST['rustracker_api'] ?? ''); ?>" placeholder="http://localhost:3000/api/blacklist">
            </div>
            <div class="form-group">
                <label class="form-label" for="rustracker_token">Rustracker Token</label>
                <input type="text" id="rustracker_token" name="rustracker_token" class="form-input"
                       value="<?php h($_POST['rustracker_token'] ?? ''); ?>" placeholder="your-secret-token">
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-8">安装</button>

            <?php if (isset($_SESSION['config_code'])): ?>
            <div class="config-fallback">
                <h2>无法写入配置文件</h2>
                <p>请手动创建 <code>config.php</code> 文件，将以下内容复制进去：</p>
                <pre class="config-code"><?php h($_SESSION['config_code']); ?></pre>
            </div>
            <?php endif; ?>
        </form>
        <?php endif; ?>

    </div>
<?php endif; ?>

</div><!-- /install-card -->
</div><!-- /install-wrap -->

</body>
</html>
