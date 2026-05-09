<?php
/**
 * 票券收納 API
 *
 * GET    /api/vouchers.php                       → 全部 voucher（依 voucher_key 索引）
 * GET    /api/vouchers.php?voucher_key=luge      → 單筆
 * POST   /api/vouchers.php                       → 新增/更新
 *           body: { voucher_key, order_no?, link?, note? }
 * DELETE /api/vouchers.php?voucher_key=luge      → 清空整筆（含照片）
 *
 * 上傳/刪除照片走 photo-vouchers.php
 */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { jsonResponse(['ok' => true]); }

$db = getDB();

if ($method === 'GET') {
    $key = $_GET['voucher_key'] ?? '';
    if ($key !== '') {
        $stmt = $db->prepare("SELECT * FROM vouchers WHERE voucher_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        if ($row) $row['photos'] = $row['photos'] ? json_decode($row['photos'], true) : [];
        jsonResponse(['voucher' => $row ?: null]);
    }
    $stmt = $db->query("SELECT * FROM vouchers");
    $rows = $stmt->fetchAll();
    $byKey = [];
    foreach ($rows as $row) {
        $row['photos'] = $row['photos'] ? json_decode($row['photos'], true) : [];
        $byKey[$row['voucher_key']] = $row;
    }
    jsonResponse(['vouchers' => $byKey]);
}

if ($method === 'POST') {
    $body = getJsonBody();
    $key = trim($body['voucher_key'] ?? '');
    if ($key === '') jsonResponse(['error' => 'voucher_key required'], 400);

    $orderNo = trim($body['order_no'] ?? '');
    $link    = trim($body['link'] ?? '');
    $note    = trim($body['note'] ?? '');

    $stmt = $db->prepare("
        INSERT INTO vouchers (voucher_key, order_no, link, note)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          order_no = VALUES(order_no),
          link     = VALUES(link),
          note     = VALUES(note)
    ");
    $stmt->execute([$key, $orderNo, $link, $note]);

    jsonResponse(['status' => 'ok', 'voucher_key' => $key]);
}

if ($method === 'DELETE') {
    $key = $_GET['voucher_key'] ?? '';
    if ($key === '') jsonResponse(['error' => 'voucher_key required'], 400);

    // 順便刪實體照片
    $stmt = $db->prepare("SELECT photos FROM vouchers WHERE voucher_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if ($row && $row['photos']) {
        $photos = json_decode($row['photos'], true);
        $uploadsDir = __DIR__ . '/../uploads';
        foreach ($photos as $p) {
            $abs = $uploadsDir . '/' . basename(dirname($p)) . '/' . basename($p);
            if (file_exists($abs)) @unlink($abs);
        }
    }

    $stmt = $db->prepare("DELETE FROM vouchers WHERE voucher_key = ?");
    $stmt->execute([$key]);

    jsonResponse(['status' => 'ok', 'deleted' => $key]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
