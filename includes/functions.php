<?php
/**
 * DMCA Panel — 共享函数库
 */

// ——— 数据库单例 ———
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ——— 配置检测 ———
function requireConfig(): void {
    if (!file_exists(__DIR__ . '/../config.php')) {
        header('Location: ../install.php');
        exit;
    }
    require_once __DIR__ . '/../config.php';
}

// ——— HTML 转义 ———
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ——— CSRF ———
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool {
    return isset($_SESSION['csrf_token'])
        && $token !== null
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ——— 频率限制 ———
function rate_limit_check(string $key, int $max = 5, int $window = 3600): bool {
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start' => time()];
    }
    $rl = &$_SESSION[$key];
    if (time() - $rl['start'] > $window) {
        $rl = ['count' => 0, 'start' => time()];
    }
    return $rl['count'] < $max;
}

function rate_limit_increment(string $key): void {
    $_SESSION[$key]['count']++;
}

// ——— Rustracker Blacklist API ———
function rustracker_push(string $api_url, string $token, string $info_hash): array {
    $result = [
        'success'   => false,
        'added'     => null,
        'http_code' => 0,
        'error'     => null,
        'response'  => '',
    ];

    if ($api_url === '' || $token === '') {
        $result['error'] = 'Rustracker API 地址或 Token 未配置';
        return $result;
    }

    if (!function_exists('curl_init')) {
        $result['error'] = 'curl 扩展未安装';
        return $result;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $api_url,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode(['info_hash' => $info_hash]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $body = curl_exec($ch);
    $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $result['response'] = $body;

    if ($curl_error) {
        $result['error'] = '连接失败：' . $curl_error;
        return $result;
    }

    // 按状态码分类处理
    switch ($result['http_code']) {
        case 200:
            $data = json_decode($body, true);
            if ($data && ($data['ok'] ?? false) === true) {
                $result['success'] = true;
                $result['added']   = $data['added'] ?? false;
                if ($result['added']) {
                    $result['error'] = null; // 新增成功
                } else {
                    $result['error'] = null; // 已存在，也算成功
                }
            } else {
                $result['error'] = 'Rustracker 返回异常：' . (is_array($data) ? ($data['error'] ?? '未知错误') : substr($body, 0, 200));
            }
            break;
        case 400:
            $result['error'] = 'info_hash 格式错误（需为 40 位十六进制）';
            break;
        case 401:
            $result['error'] = 'Rustracker Token 缺失或错误（Unauthorized）';
            break;
        case 503:
            $result['error'] = 'Rustracker 未配置 admin token 或 blacklist 文件';
            break;
        case 500:
            $result['error'] = 'Rustracker 服务端写入 blacklist 文件失败';
            break;
        default:
            $result['error'] = 'Rustracker 返回 HTTP ' . $result['http_code'] . '：' . substr($body, 0, 200);
    }

    return $result;
}
