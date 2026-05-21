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
if ($token === '' || $token !== setting_get('rustracker_token')) {
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

    // 审核通过 → GET 查询 + POST 添加（可关闭自动推送）
    $auto_push = setting_get('auto_blacklist', '1') === '1';
    $api = setting_get('rustracker_api');
    $tkn = setting_get('rustracker_token');
    $rustracker = null;
    $rustracker_fatal = false;
    if ($new_status === 'approved' && $auto_push && !empty($report['info_hash'])) {
        $hash = $report['info_hash'];

        // Step 1: GET 查询
        $check = rustracker_check($api, $tkn, $hash);
        if ($check['success'] && $check['blacklisted']) {
            $rustracker = ['action' => 'skipped', 'reason' => 'already_blacklisted', 'check' => $check];
        } elseif ($check['success'] && !$check['blacklisted']) {
            // Step 2: POST 添加
            $push = rustracker_push($api, $tkn, $hash);
            $rustracker = ['action' => 'pushed', 'push' => $push, 'check' => $check];
            if (!$push['success']) $rustracker_fatal = true;
        } else {
            $rustracker = ['action' => 'check_failed', 'check' => $check];
            $rustracker_fatal = true;
        }
    }

    // Rustracker 致命错误时不更新状态
    if ($rustracker_fatal) {
        http_response_code(502);
        echo json_encode([
            'success'    => false,
            'error'      => 'Rustracker 操作失败，状态未变更',
            'id'         => $id,
            'rustracker' => $rustracker,
        ]);
        exit;
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
