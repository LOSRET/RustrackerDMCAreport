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
