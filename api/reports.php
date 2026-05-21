<?php
/**
 * API: 获取举报列表（需认证）
 * GET /api/reports.php?status=pending&page=1&per_page=20
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

$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'pending', 'approved', 'rejected'])) $status = 'all';

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
$offset = ($page - 1) * $per_page;

try {
    $pdo = getDB();
    $tbl = DB_PREFIX . 'dmca_reports';

    $where = '';
    $params = [];
    if ($status !== 'all') {
        $where = "WHERE status = :status";
        $params[':status'] = $status;
    }

    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM `$tbl` $where");
    $count_stmt->execute($params);
    $total = (int)$count_stmt->fetchColumn();

    $data_stmt = $pdo->prepare(
        "SELECT * FROM `$tbl` $where ORDER BY created_at DESC LIMIT :lim OFFSET :off"
    );
    foreach ($params as $k => $v) $data_stmt->bindValue($k, $v);
    $data_stmt->bindValue(':lim', $per_page, PDO::PARAM_INT);
    $data_stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $data_stmt->execute();

    echo json_encode([
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $per_page,
        'total_pages' => (int)ceil($total / $per_page),
        'data'        => $data_stmt->fetchAll(),
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '数据库错误']);
}
