<?php
/**
 * 管理后台 — DMCA 举报审核
 */

require_once __DIR__ . '/../includes/functions.php';
requireConfig();

session_start();

// —— 认证检查 ——
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// —— CSRF ——
$csrf = csrf_token();
$table = DB_PREFIX . 'dmca_reports';

// —— 处理审核操作 ——
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $message = '安全校验失败，请刷新页面后重试。';
        $msg_type = 'error';
        goto render;
    }

    $id = (int)$_POST['id'];
    $action = $_POST['action'];

    if ($action === 'approve' || $action === 'reject') {
        $note = trim($_POST['admin_note'] ?? '');
        $new_status = $action === 'approve' ? 'approved' : 'rejected';

        // 审核通过时，推送到 Rustracker
        $rustracker_result = '';
        if ($new_status === 'approved' && RUSTRACKER_TOKEN !== '') {
            if (!function_exists('curl_init')) {
                $rustracker_result = 'Rustracker 推送不可用：服务器未安装 curl 扩展。';
            } else {
                $pdo = getDB();
                $stmt = $pdo->prepare("SELECT info_hash FROM `$table` WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $row = $stmt->fetch();

                if ($row && !empty($row['info_hash'])) {
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL            => RUSTRACKER_API,
                        CURLOPT_POST           => true,
                        CURLOPT_HTTPHEADER     => [
                            'Authorization: Bearer ' . RUSTRACKER_TOKEN,
                            'Content-Type: application/json',
                        ],
                        CURLOPT_POSTFIELDS     => json_encode(['info_hash' => $row['info_hash']]),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT        => 10,
                        CURLOPT_CONNECTTIMEOUT => 5,
                    ]);
                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curl_error = curl_error($ch);
                    curl_close($ch);

                    if ($curl_error) {
                        $rustracker_result = 'Rustracker 推送失败：' . $curl_error;
                    } elseif ($http_code >= 200 && $http_code < 300) {
                        $rustracker_result = '已推送至 Rustracker 黑名单';
                    } else {
                        $rustracker_result = 'Rustracker 返回 HTTP ' . $http_code . '：' . substr($response, 0, 200);
                    }
                }
            }
        }

        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE `$table` SET status = :s, admin_note = :n WHERE id = :id");
        $stmt->execute([':s' => $new_status, ':n' => $note, ':id' => $id]);

        $labels = ['approved' => '已通过', 'rejected' => '已驳回'];
        $message = "举报 #{$id} 已标记为「{$labels[$new_status]}」。";
        if ($rustracker_result) $message .= ' ' . $rustracker_result;
        $msg_type = $new_status === 'approved' ? 'success' : 'info';

    } elseif ($action === 'reopen') {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE `$table` SET status = 'pending', admin_note = '' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $message = "举报 #{$id} 已恢复为待审核。";
        $msg_type = 'info';
    }
}

render:

// —— 筛选 & 分页 ——
$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'approved', 'rejected'])) $filter = 'all';

$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where = '';
$params = [];
if ($filter !== 'all') {
    $where = "WHERE status = :status";
    $params[':status'] = $filter;
}

$pdo = getDB();
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` $where");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$data_stmt = $pdo->prepare("SELECT * FROM `$table` $where ORDER BY created_at DESC LIMIT :lim OFFSET :off");
foreach ($params as $k => $v) $data_stmt->bindValue($k, $v);
$data_stmt->bindValue(':lim', $per_page, PDO::PARAM_INT);
$data_stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$data_stmt->execute();
$reports = $data_stmt->fetchAll();

// —— 统计 ——
$stats_stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM `$table` GROUP BY status");
$stats = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($stats_stmt as $row) $stats[$row['status']] = (int)$row['cnt'];

// —— 判断是否 AJAX 请求 ——
$is_ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

if ($is_ajax):
    // 只输出需要更新的片段
?>
<?php if ($message): ?>
<div class="alert alert-<?php echo $msg_type === 'success' ? 'success' : ($msg_type === 'error' ? 'error' : 'info'); ?>"><?php echo h($message); ?></div>
<?php endif; ?>

<div class="tabs">
    <a href="?status=all" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">全部 <?php echo array_sum($stats); ?></a>
    <a href="?status=pending" class="tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">待审核 <?php echo $stats['pending']; ?></a>
    <a href="?status=approved" class="tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">已通过 <?php echo $stats['approved']; ?></a>
    <a href="?status=rejected" class="tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">已驳回 <?php echo $stats['rejected']; ?></a>
</div>

<?php if (empty($reports)): ?>
<div class="empty-state" id="empty-search">
    <div class="empty-state-icon">&#128196;</div>
    <h2>暂无数据</h2>
    <p>没有符合条件的举报记录</p>
</div>
<?php else: ?>
<div class="table-wrap">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>举报人</th>
            <th class="col-company">公司</th>
            <th>原始作品</th>
            <th class="col-hash">Info Hash</th>
            <th>状态</th>
            <th>提交时间</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reports as $r):
            $search_text = implode(' ', [$r['reporter_name'], $r['reporter_email'], $r['company_name'], $r['info_hash'], $r['original_work']]);
        ?>
        <tr class="row-clickable" data-search="<?php echo h($search_text); ?>"
            data-id="<?php echo (int)$r['id']; ?>"
            data-reporter_name="<?php echo h($r['reporter_name']); ?>"
            data-reporter_email="<?php echo h($r['reporter_email']); ?>"
            data-company_name="<?php echo h($r['company_name']); ?>"
            data-original_work="<?php echo h($r['original_work']); ?>"
            data-infringing_url="<?php echo h($r['infringing_url']); ?>"
            data-info_hash="<?php echo h($r['info_hash']); ?>"
            data-description="<?php echo h($r['description']); ?>"
            data-status="<?php echo h($r['status']); ?>"
            data-status_label="<?php
                $labels = ['pending' => '待审核', 'approved' => '已通过', 'rejected' => '已驳回'];
                echo $labels[$r['status']];
            ?>"
            data-created_at="<?php echo date('Y-m-d H:i', strtotime($r['created_at'])); ?>"
            data-admin_note="<?php echo h($r['admin_note']); ?>"
        >
            <td>#<?php echo (int)$r['id']; ?></td>
            <td>
                <div class="cell-reporter-name"><?php echo h($r['reporter_name']); ?></div>
                <div class="cell-reporter-email"><?php echo h($r['reporter_email']); ?></div>
            </td>
            <td class="col-company"><?php echo h($r['company_name']); ?></td>
            <td><span class="truncate" title="<?php echo h($r['original_work']); ?>"><?php echo h($r['original_work']); ?></span></td>
            <td class="col-hash">
                <?php if (!empty($r['info_hash'])): ?>
                <code class="code-inline"><?php echo h(substr($r['info_hash'], 0, 8)); ?>&hellip;</code>
                <button class="copy-btn" onclick="DMCA.copyHash('<?php echo h($r['info_hash']); ?>', this); event.stopPropagation();">复制</button>
                <?php else: ?>
                <span class="text-muted">&mdash;</span>
                <?php endif; ?>
            </td>
            <td><span class="badge badge-<?php echo h($r['status']); ?>"><?php echo $labels[$r['status']]; ?></span></td>
            <td class="cell-date"><?php echo date('Y-m-d H:i', strtotime($r['created_at'])); ?></td>
            <td class="actions" onclick="event.stopPropagation();">
                <?php if ($r['status'] === 'pending'): ?>
                <form method="post" class="form-inline action-form">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                    <button type="submit" class="btn btn-success btn-sm">通过</button>
                </form>
                <button type="button" class="btn btn-danger btn-sm" onclick="DMCA.toggleReject(<?php echo (int)$r['id']; ?>)">驳回</button>
                <?php else: ?>
                <form method="post" class="form-inline action-form" onsubmit="return confirm('确认将此举报恢复为待审核状态？');">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <input type="hidden" name="action" value="reopen">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                    <button type="submit" class="btn btn-outline btn-sm">重新打开</button>
                </form>
                <?php if (!empty($r['admin_note'])): ?>
                <div class="cell-action-note" title="<?php echo h($r['admin_note']); ?>"><?php echo h(mb_strlen($r['admin_note']) > 20 ? mb_substr($r['admin_note'], 0, 20) . '…' : $r['admin_note']); ?></div>
                <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php if ($r['status'] === 'pending'): ?>
        <tr class="reject-row" id="reject-row-<?php echo (int)$r['id']; ?>" onclick="event.stopPropagation();">
            <td colspan="8">
                <div class="reject-form" id="reject-form-<?php echo (int)$r['id']; ?>">
                    <form method="post" class="action-form">
                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                        <div class="form-group">
                            <label class="form-label" for="reject_note_<?php echo (int)$r['id']; ?>">驳回理由</label>
                            <textarea id="reject_note_<?php echo (int)$r['id']; ?>" name="admin_note" placeholder="可选填写驳回理由..."></textarea>
                        </div>
                        <div class="reject-form-actions">
                            <button type="button" class="btn btn-outline btn-sm" onclick="DMCA.cancelReject(<?php echo (int)$r['id']; ?>)">取消</button>
                            <button type="submit" class="btn btn-danger btn-sm">确认驳回</button>
                        </div>
                    </form>
                </div>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if ($total_pages > 1): ?>
<div class="pagination">
    <?php if ($page > 1): ?>
        <a href="?status=<?php echo h($filter); ?>&page=<?php echo $page - 1; ?>">&larr; 上一页</a>
    <?php else: ?>
        <span class="disabled">&larr; 上一页</span>
    <?php endif; ?>
    <span><?php echo $page; ?> / <?php echo $total_pages; ?></span>
    <?php if ($page < $total_pages): ?>
        <a href="?status=<?php echo h($filter); ?>&page=<?php echo $page + 1; ?>">下一页 &rarr;</a>
    <?php else: ?>
        <span class="disabled">下一页 &rarr;</span>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php
exit;
endif; // end AJAX-only output
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DMCA 举报管理</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="brand-bar"></div>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <a href="index.php" class="sidebar-brand">DMCA Panel</a>
        <ul class="sidebar-nav">
            <li><a href="index.php" class="active">举报列表</a></li>
            <li><a href="logout.php">退出登录</a></li>
        </ul>
    </aside>

    <main class="admin-main">

        <div class="admin-header">
            <h1>DMCA 举报管理</h1>
            <a href="../index.php" class="btn btn-outline btn-sm">查看公开页面 &rarr;</a>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $msg_type === 'success' ? 'success' : ($msg_type === 'error' ? 'error' : 'info'); ?>"><?php echo h($message); ?></div>
        <?php endif; ?>

        <!-- 搜索 -->
        <div class="search-bar">
            <input type="text" class="search-input" id="search" placeholder="搜索 举报人 / 邮箱 / 公司 / Info Hash ..." autocomplete="off">
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?status=all" class="tab <?php echo $filter === 'all' ? 'active' : ''; ?>">全部 <?php echo array_sum($stats); ?></a>
            <a href="?status=pending" class="tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">待审核 <?php echo $stats['pending']; ?></a>
            <a href="?status=approved" class="tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">已通过 <?php echo $stats['approved']; ?></a>
            <a href="?status=rejected" class="tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">已驳回 <?php echo $stats['rejected']; ?></a>
        </div>

        <?php if (empty($reports)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128196;</div>
            <h2>暂无数据</h2>
            <p>没有符合条件的举报记录</p>
        </div>
        <?php else: ?>

        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>举报人</th>
                    <th class="col-company">公司</th>
                    <th>原始作品</th>
                    <th class="col-hash">Info Hash</th>
                    <th>状态</th>
                    <th>提交时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r):
                    $search_text = implode(' ', [$r['reporter_name'], $r['reporter_email'], $r['company_name'], $r['info_hash'], $r['original_work']]);
                ?>
                <tr class="row-clickable" data-search="<?php echo h($search_text); ?>"
                    data-id="<?php echo (int)$r['id']; ?>"
                    data-reporter_name="<?php echo h($r['reporter_name']); ?>"
                    data-reporter_email="<?php echo h($r['reporter_email']); ?>"
                    data-company_name="<?php echo h($r['company_name']); ?>"
                    data-original_work="<?php echo h($r['original_work']); ?>"
                    data-infringing_url="<?php echo h($r['infringing_url']); ?>"
                    data-info_hash="<?php echo h($r['info_hash']); ?>"
                    data-description="<?php echo h($r['description']); ?>"
                    data-status="<?php echo h($r['status']); ?>"
                    data-status_label="<?php
                        $labels = ['pending' => '待审核', 'approved' => '已通过', 'rejected' => '已驳回'];
                        echo $labels[$r['status']];
                    ?>"
                    data-created_at="<?php echo date('Y-m-d H:i', strtotime($r['created_at'])); ?>"
                    data-admin_note="<?php echo h($r['admin_note']); ?>"
                >
                    <td>#<?php echo (int)$r['id']; ?></td>
                    <td>
                        <div class="cell-reporter-name"><?php echo h($r['reporter_name']); ?></div>
                        <div class="cell-reporter-email"><?php echo h($r['reporter_email']); ?></div>
                    </td>
                    <td class="col-company"><?php echo h($r['company_name']); ?></td>
                    <td><span class="truncate" title="<?php echo h($r['original_work']); ?>"><?php echo h($r['original_work']); ?></span></td>
                    <td class="col-hash">
                        <?php if (!empty($r['info_hash'])): ?>
                        <code class="code-inline"><?php echo h(substr($r['info_hash'], 0, 8)); ?>&hellip;</code>
                        <button class="copy-btn" onclick="DMCA.copyHash('<?php echo h($r['info_hash']); ?>', this); event.stopPropagation();">复制</button>
                        <?php else: ?>
                        <span class="text-muted">&mdash;</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?php echo h($r['status']); ?>"><?php echo $labels[$r['status']]; ?></span></td>
                    <td class="cell-date"><?php echo date('Y-m-d H:i', strtotime($r['created_at'])); ?></td>
                    <td class="actions" onclick="event.stopPropagation();">
                        <?php if ($r['status'] === 'pending'): ?>
                        <form method="post" class="form-inline action-form">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                            <button type="submit" class="btn btn-success btn-sm">通过</button>
                        </form>
                        <button type="button" class="btn btn-danger btn-sm" onclick="DMCA.toggleReject(<?php echo (int)$r['id']; ?>)">驳回</button>
                        <?php else: ?>
                        <form method="post" class="form-inline action-form" onsubmit="return confirm('确认将此举报恢复为待审核状态？');">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <input type="hidden" name="action" value="reopen">
                            <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                            <button type="submit" class="btn btn-outline btn-sm">重新打开</button>
                        </form>
                        <?php if (!empty($r['admin_note'])): ?>
                        <div class="cell-action-note" title="<?php echo h($r['admin_note']); ?>"><?php echo h(mb_strlen($r['admin_note']) > 20 ? mb_substr($r['admin_note'], 0, 20) . '…' : $r['admin_note']); ?></div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($r['status'] === 'pending'): ?>
                <tr class="reject-row" id="reject-row-<?php echo (int)$r['id']; ?>" onclick="event.stopPropagation();">
                    <td colspan="8">
                        <div class="reject-form" id="reject-form-<?php echo (int)$r['id']; ?>">
                            <form method="post" class="action-form">
                                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                                <div class="form-group">
                                    <label class="form-label" for="reject_note_<?php echo (int)$r['id']; ?>">驳回理由</label>
                                    <textarea id="reject_note_<?php echo (int)$r['id']; ?>" name="admin_note" placeholder="可选填写驳回理由..."></textarea>
                                </div>
                                <div class="reject-form-actions">
                                    <button type="button" class="btn btn-outline btn-sm" onclick="DMCA.cancelReject(<?php echo (int)$r['id']; ?>)">取消</button>
                                    <button type="submit" class="btn btn-danger btn-sm">确认驳回</button>
                                </div>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?status=<?php echo h($filter); ?>&page=<?php echo $page - 1; ?>">&larr; 上一页</a>
            <?php else: ?>
                <span class="disabled">&larr; 上一页</span>
            <?php endif; ?>
            <span><?php echo $page; ?> / <?php echo $total_pages; ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="?status=<?php echo h($filter); ?>&page=<?php echo $page + 1; ?>">下一页 &rarr;</a>
            <?php else: ?>
                <span class="disabled">下一页 &rarr;</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<!-- Detail Modal -->
<div class="modal-overlay" id="detail-modal">
    <div class="modal">
        <button class="modal-close" onclick="DMCA.closeModal()">&times;</button>
        <div class="modal-title" id="modal-title"></div>
        <div id="modal-content"></div>
    </div>
</div>

<script src="../assets/app.js"></script>
</body>
</html>
