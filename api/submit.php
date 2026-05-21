<?php
/**
 * API: 提交 DMCA 举报
 * POST /api/submit.php
 */

require_once __DIR__ . '/../includes/functions.php';

if (!file_exists(__DIR__ . '/../config.php')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => '系统未安装']);
    exit;
}
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '仅支持 POST']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => '无效的 JSON']);
    exit;
}

$name    = trim($input['reporter_name'] ?? '');
$email   = trim($input['reporter_email'] ?? '');
$company = trim($input['company_name'] ?? '');
$work    = trim($input['original_work'] ?? '');
$url     = trim($input['infringing_url'] ?? '');
$hash    = trim($input['info_hash'] ?? '');
$desc    = trim($input['description'] ?? '');
$address = trim($input['address'] ?? '');
$role    = trim($input['role'] ?? '');
$inf_loc = trim($input['infringing_location'] ?? '');
$phone   = trim($input['phone'] ?? '');
$sig_ok  = !empty($input['signature_consent']) ? 1 : 0;
$sig_name = trim($input['signature_name'] ?? '');

$errors = [];
if ($name === '')     $errors[] = 'reporter_name 不能为空';
if ($email === '')    $errors[] = 'reporter_email 不能为空';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'reporter_email 格式不正确';
if ($work === '')     $errors[] = 'original_work 不能为空';
if ($address === '')  $errors[] = 'address 不能为空';
if (!in_array($role, ['owner', 'representative'])) $errors[] = 'role 必须为 owner 或 representative';
if ($hash !== '' && !preg_match('/^[a-fA-F0-9]{40}$/', $hash)) $errors[] = 'info_hash 格式不正确';
if (!$sig_ok)         $errors[] = 'signature_consent 必选';
if ($sig_name === '') $errors[] = 'signature_name 不能为空';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['error' => '验证失败', 'details' => $errors]);
    exit;
}

try {
    $pdo = getDB();
    $tbl = DB_PREFIX . 'dmca_reports';
    @$pdo->exec("ALTER TABLE `$tbl`
        ADD COLUMN IF NOT EXISTS address VARCHAR(255) NOT NULL DEFAULT '',
        ADD COLUMN IF NOT EXISTS phone VARCHAR(50) NULL,
        ADD COLUMN IF NOT EXISTS role ENUM('owner','representative') NOT NULL DEFAULT 'owner',
        ADD COLUMN IF NOT EXISTS infringing_location TEXT NULL,
        ADD COLUMN IF NOT EXISTS signature_consent TINYINT(1) NOT NULL DEFAULT 0,
        ADD COLUMN IF NOT EXISTS signature_name VARCHAR(100) NOT NULL DEFAULT ''");
    $stmt = $pdo->prepare(
        "INSERT INTO `$tbl` (reporter_name, reporter_email, company_name, original_work, infringing_url, infringing_location, info_hash, description, address, phone, role, signature_consent, signature_name)
         VALUES (:n, :e, :c, :w, :u, :il, :h, :d, :addr, :ph, :role, :sc, :sn)"
    );
    $stmt->execute([
        ':n' => $name, ':e' => $email, ':c' => $company,
        ':w' => $work, ':u' => $url,   ':il' => $inf_loc,
        ':h' => $hash, ':d' => $desc,  ':addr' => $address,
        ':ph' => $phone, ':role' => $role,
        ':sc' => $sig_ok, ':sn' => $sig_name,
    ]);

    http_response_code(201);
    echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库错误']);
}
