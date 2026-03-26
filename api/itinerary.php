<?php
/**
 * 行程 API
 *
 * GET    /api/itinerary.php           → 取得所有行程（按天分組）
 * GET    /api/itinerary.php?day=1     → 取得第 1 天行程
 * PUT    /api/itinerary.php           → 更新排序 body: { day: 1, order: [3,1,2,5,4] } (id 陣列)
 * POST   /api/itinerary.php           → 新增一筆行程
 * DELETE /api/itinerary.php?id=5      → 刪除一筆行程
 */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') { jsonResponse(['ok' => true]); }

$db = getDB();

// ===== GET =====
if ($method === 'GET') {
    $day = isset($_GET['day']) ? (int)$_GET['day'] : 0;

    if ($day > 0) {
        $stmt = $db->prepare("SELECT * FROM itinerary WHERE day = ? ORDER BY sort_order ASC");
        $stmt->execute([$day]);
        jsonResponse(['day' => $day, 'stops' => $stmt->fetchAll()]);
    }

    // 全部天數
    $stmt = $db->query("SELECT * FROM itinerary ORDER BY day ASC, sort_order ASC");
    $rows = $stmt->fetchAll();

    $grouped = [];
    foreach ($rows as $row) {
        $d = $row['day'];
        if (!isset($grouped[$d])) $grouped[$d] = [];
        // Parse badges JSON
        $row['badges'] = $row['badges'] ? json_decode($row['badges'], true) : [];
        $grouped[$d][] = $row;
    }

    jsonResponse(['days' => $grouped]);
}

// ===== PUT — 重新排序 =====
if ($method === 'PUT') {
    $body = getJsonBody();
    $day = (int)($body['day'] ?? 0);
    $order = $body['order'] ?? []; // array of itinerary IDs in new order

    if (!$day || empty($order)) {
        jsonResponse(['error' => 'day and order[] required'], 400);
    }

    $stmt = $db->prepare("UPDATE itinerary SET sort_order = ? WHERE id = ? AND day = ?");
    foreach ($order as $i => $id) {
        $stmt->execute([$i * 10, (int)$id, $day]);
    }

    jsonResponse(['status' => 'ok', 'day' => $day, 'reordered' => count($order)]);
}

// ===== POST — 新增行程 =====
if ($method === 'POST') {
    $body = getJsonBody();

    $required = ['day', 'title'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            jsonResponse(['error' => "$field is required"], 400);
        }
    }

    // 取得該天最大 sort_order
    $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM itinerary WHERE day = ?");
    $stmt->execute([(int)$body['day']]);
    $nextOrder = $stmt->fetchColumn();

    $stmt = $db->prepare("
        INSERT INTO itinerary (day, sort_order, time, title, detail, type, ticket, lat, lng, map_query, kr_name, kr_addr, badges)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        (int)$body['day'],
        (int)($body['sort_order'] ?? $nextOrder),
        $body['time'] ?? '',
        $body['title'],
        $body['detail'] ?? '',
        $body['type'] ?? 'attraction',
        (int)($body['ticket'] ?? 0),
        $body['lat'] ?? null,
        $body['lng'] ?? null,
        $body['map_query'] ?? '',
        $body['kr_name'] ?? '',
        $body['kr_addr'] ?? '',
        isset($body['badges']) ? json_encode($body['badges'], JSON_UNESCAPED_UNICODE) : '',
    ]);

    $newId = $db->lastInsertId();
    jsonResponse(['status' => 'ok', 'id' => $newId], 201);
}

// ===== DELETE =====
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $stmt = $db->prepare("DELETE FROM itinerary WHERE id = ?");
    $stmt->execute([$id]);

    jsonResponse(['status' => 'ok', 'deleted' => $id]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
