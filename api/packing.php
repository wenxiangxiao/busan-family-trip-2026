<?php
/**
 * 行李清單 API
 *
 * GET    /api/packing.php                      → 取得所有勾選狀態
 * PUT    /api/packing.php   body: { item_key, checked: 1|0 } → 更新勾選
 * DELETE /api/packing.php?reset=1              → 重置全部
 */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { jsonResponse(['ok' => true]); }

$db = getDB();

if ($method === 'GET') {
    $rows = $db->query("SELECT item_key, checked FROM packing")->fetchAll();
    $map = [];
    foreach ($rows as $r) { $map[$r['item_key']] = (bool)$r['checked']; }
    jsonResponse(['packing' => $map]);
}

if ($method === 'PUT') {
    $body = getJsonBody();
    if (!isset($body['item_key'])) jsonResponse(['error' => 'item_key required'], 400);

    $stmt = $db->prepare("
        INSERT INTO packing (item_key, checked) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE checked = VALUES(checked)
    ");
    $stmt->execute([$body['item_key'], (int)($body['checked'] ?? 0)]);
    jsonResponse(['status' => 'ok']);
}

if ($method === 'DELETE') {
    if (isset($_GET['reset'])) {
        $db->exec("DELETE FROM packing");
        jsonResponse(['status' => 'ok', 'message' => 'reset']);
    }
    jsonResponse(['error' => 'use ?reset=1'], 400);
}

jsonResponse(['error' => 'Method not allowed'], 405);
