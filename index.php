<?php
/**
 * DMCA Copyright Infringement Report — public submission page
 * Frontend i18n via assets/i18n.js (zh/en)
 */

require_once __DIR__ . '/includes/functions.php';
requireConfig();

session_start();

$csrf = csrf_token();
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'csrf';
    } elseif (!rate_limit_check('dmca_submit', 5, 3600)) {
        $errors[] = 'rate';
    } else {
        $hp = $_POST['website'] ?? '';
        if ($hp !== '') {
            $success = 'submit';
            goto show_form;
        }

        $name    = trim($_POST['reporter_name'] ?? '');
        $email   = trim($_POST['reporter_email'] ?? '');
        $company = trim($_POST['company_name'] ?? '');
        $work    = trim($_POST['original_work'] ?? '');
        $url     = trim($_POST['infringing_url'] ?? '');
        $hash    = trim($_POST['info_hash'] ?? '');
        $desc    = trim($_POST['description'] ?? '');

        if ($name === '')  $errors[] = 'name';
        if ($email === '') $errors[] = 'email';
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email_fmt';
        if ($work === '')  $errors[] = 'work';
        if ($hash !== '' && !preg_match('/^[a-fA-F0-9]{40}$/', $hash)) $errors[] = 'hash';

        if (empty($_POST['affirm_goodfaith']))  $errors[] = 'affirm_goodfaith';
        if (empty($_POST['affirm_accuracy']))   $errors[] = 'affirm_accuracy';
        if (empty($_POST['affirm_authority']))  $errors[] = 'affirm_authority';

        if (empty($errors)) {
            try {
                $pdo = getDB();
                $tbl = DB_PREFIX . 'dmca_reports';
                $stmt = $pdo->prepare(
                    "INSERT INTO `$tbl` (reporter_name, reporter_email, company_name, original_work, infringing_url, info_hash, description)
                     VALUES (:n, :e, :c, :w, :u, :h, :d)"
                );
                $stmt->execute([
                    ':n' => $name, ':e' => $email, ':c' => $company,
                    ':w' => $work, ':u' => $url,   ':h' => $hash, ':d' => $desc,
                ]);

                rate_limit_increment('dmca_submit');
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrf = $_SESSION['csrf_token'];
                $success = 'submit';
            } catch (PDOException $e) {
                $errors[] = 'server';
            }
        }
    }
}

show_form:

// Error key → i18n key mapping for JS translation
$err_map = [
    'csrf' => 'error.csrf', 'rate' => 'error.rate',
    'name' => 'error.name', 'email' => 'error.email', 'email_fmt' => 'error.email_fmt',
    'work' => 'error.work', 'hash' => 'error.hash',
    'affirm_goodfaith' => 'error.affirm_goodfaith',
    'affirm_accuracy' => 'error.affirm_accuracy',
    'affirm_authority' => 'error.affirm_authority',
    'server' => 'error.server',
];
?><!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title data-i18n="page.title">DMCA 版权侵权举报</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="brand-bar"></div>

<nav class="topnav">
    <div class="topnav-inner">
        <a href="index.php" class="topnav-brand" data-i18n="nav.brand">DMCA Panel</a>
        <div class="topnav-right">
            <button type="button" id="lang-switch" class="lang-switch" data-i18n="lang.switch">中文</button>
            <a href="admin/login.php" class="topnav-link" data-i18n="nav.login">管理员登录</a>
        </div>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1 data-i18n="page.heading">DMCA 版权侵权举报</h1>
        <p data-i18n="page.subtitle">请填写以下信息，我们将在 48 小时内审核处理</p>
    </div>
</div>

<div class="container">
    <div class="card">

        <?php if ($success): ?>
        <div class="alert alert-success"><span data-i18n="success.<?php echo h($success); ?>">举报已提交</span></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e):
                $key = $err_map[$e] ?? "error.{$e}";
            ?>
            <div><span data-i18n="<?php echo h($key); ?>"><?php echo h($e); ?></span></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
            <div class="hp-field">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" autocomplete="off" tabindex="-1">
            </div>

            <div class="form-group">
                <label class="form-label" for="reporter_name" data-i18n="form.label_name">举报人姓名</label>
                <input type="text" id="reporter_name" name="reporter_name" class="form-input" required
                       value="<?php echo h($_POST['reporter_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="reporter_email" data-i18n="form.label_email">举报人邮箱</label>
                <input type="email" id="reporter_email" name="reporter_email" class="form-input" required
                       value="<?php echo h($_POST['reporter_email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="company_name" data-i18n="form.label_company">权利人 / 公司名称</label>
                <input type="text" id="company_name" name="company_name" class="form-input"
                       value="<?php echo h($_POST['company_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="original_work" data-i18n="form.label_work">原始作品描述</label>
                <textarea id="original_work" name="original_work" class="form-textarea" required
                          ><?php echo h($_POST['original_work'] ?? ''); ?></textarea>
                <p class="form-hint" data-i18n="form.hint_work">请说明您拥有版权的原始作品名称、类型及相关证明信息</p>
            </div>
            <div class="form-group">
                <label class="form-label" for="infringing_url" data-i18n="form.label_url">侵权链接</label>
                <input type="text" id="infringing_url" name="infringing_url" class="form-input"
                       value="<?php echo h($_POST['infringing_url'] ?? ''); ?>" placeholder="https://...">
            </div>
            <div class="form-group">
                <label class="form-label" for="info_hash" data-i18n="form.label_hash">Info Hash（40 位十六进制）</label>
                <input type="text" id="info_hash" name="info_hash" class="form-input"
                       value="<?php echo h($_POST['info_hash'] ?? ''); ?>"
                       placeholder="d4c9b8e7f1a23b56c78d90e1f23a45b67c8d90e1"
                       maxlength="40" pattern="[a-fA-F0-9]{40}">
            </div>
            <div class="form-group">
                <label class="form-label" for="description" data-i18n="form.label_desc">补充说明</label>
                <textarea id="description" name="description" class="form-textarea"><?php echo h($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="dmca-affirm">
                <label class="form-checkbox">
                    <input type="checkbox" name="affirm_goodfaith" value="1" required>
                    <span data-i18n="affirm.goodfaith">我善意地相信，上述侵权材料的使用未经版权所有者、其代理人或法律的授权。</span>
                </label>
                <label class="form-checkbox">
                    <input type="checkbox" name="affirm_accuracy" value="1" required>
                    <span data-i18n="affirm.accuracy">本通知中的信息准确无误。本人愿承担作伪证的法律责任，并声明本人是已声明被侵犯的专有权所有者授权的代表。</span>
                </label>
                <label class="form-checkbox">
                    <input type="checkbox" name="affirm_authority" value="1" required>
                    <span data-i18n="affirm.authority">我了解，故意作出虚假陈述可能需承担法律责任（包括损害赔偿和诉讼费用）。</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block form-submit-row" data-i18n="form.submit">提交举报</button>
        </form>

    </div>
</div>

<script src="assets/i18n.js"></script>
</body>
</html>
