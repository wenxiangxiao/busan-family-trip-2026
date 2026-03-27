<?php
/**
 * 釜山旅遊 — 資料庫設定
 * 部署時修改以下常數
 */

// 資料庫連線
define('DB_HOST', 'mysql');
define('DB_NAME', 'busan_trip');
define('DB_USER', 'busan_user');
define('DB_PASS', 'busan2026');

// CORS（前端網址，部署時改成正式網域）
define('ALLOWED_ORIGIN', '*');

// 時區
date_default_timezone_set('Asia/Taipei');

/**
 * 取得 PDO 連線
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/**
 * JSON 回應
 */
function jsonResponse(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 讀取 JSON body
 */
function getJsonBody(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
