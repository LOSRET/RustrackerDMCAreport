<?php
/**
 * API: 审核举报 & 推送 Rustracker 黑名单（需认证）
 * POST /api/review.php
 * Body: {"id": 1, "action": "approve|reject", "admin_note": ""}
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
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '仅支持 POST']);
    exit;
}

// Bearer Token 认证
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/^Bearer\s+(.+)$/i', $auth_header, $m)) {
    $token = $m[1];
}
if ($token !== RUSTRACKER_TOKEN || RUSTRACKER_TOKEN === '') {
    http_response_code(401);
    echo json_encode(['error' => '未授权']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['id']) || empty($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => '请提供 id 和 action']);
    exit;
}

$id = (int)$input['id'];
$action = $input['action'];
$note = trim($input['admin_note'] ?? '');

if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'action 必须是 approve 或 reject']);
    exit;
}

$new_status = $action === 'approve' ? 'approved' : 'rejected';

try {
    $pdo = getDB();
    $tbl = DB_PREFIX . 'dmca_reports';

    $stmt = $pdo->prepare("SELECT * FROM `$tbl` WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $report = $stmt->fetch();

    if (!$report) {
        http_response_code(404);
        echo json_encode(['error' => '举报不存在']);
        exit;
    }

    if ($report['status'] !== 'pending') {
        http_response_code(409);
        echo json_encode(['error' => '该举报已处理']);
        exit;
    }

    // 审核通过 → 推送 Rustracker
    $rustracker = null;
    if ($new_status === 'approved' && !empty($report['info_hash'])) {
        $rustracker = rustracker_push(RUSTRACKER_API, RUSTRACKER_TOKEN, $report['info_hash']);
    }

    $stmt = $pdo->prepare("UPDATE `$tbl` SET status = :s, admin_note = :n WHERE id = :id");
    $stmt->execute([':s' => $new_status, ':n' => $note, ':id' => $id]);

    echo json_encode([
        'success'    => true,
        'id'         => $id,
        'status'     => $new_status,
        'rustracker' => $rustracker,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库错误']);
}
