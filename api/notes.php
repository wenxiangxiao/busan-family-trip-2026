<?php
/**
 * 行程心得 / 照片 API
 *
 * GET    /api/notes.php                     → 取得所有心得（依 stop_id 分組）
 * GET    /api/notes.php?stop_id=5           → 取得單一行程心得
 * POST   /api/notes.php                     → 新增/更新心得
 *                                              body: { stop_id: 5, note_text: "..." }
 * DELETE /api/notes.php?stop_id=5           → 清空心得（含照片紀錄）
 */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') { jsonResponse(['ok' => true]); }

$db = getDB();

// ===== GET =====
if ($method === 'GET') {
    $stopId = isset($_GET['stop_id']) ? (int)$_GET['stop_id'] : 0;

    if ($stopId > 0) {
        $stmt = $db->prepare("SELECT * FROM stop_notes WHERE stop_id = ?");
        $stmt->execute([$stopId]);
        $row = $stmt->fetch();
        if ($row) {
            $row['photos'] = $row['photos'] ? json_decode($row['photos'], true) : [];
        }
        jsonResponse(['note' => $row ?: null]);
    }

    // 全部
    $stmt = $db->query("SELECT * FROM stop_notes");
    $rows = $stmt->fetchAll();
    $byStopId = [];
    foreach ($rows as $row) {
        $row['photos'] = $row['photos'] ? json_decode($row['photos'], true) : [];
        $byStopId[$row['stop_id']] = $row;
    }
    jsonResponse(['notes' => $byStopId]);
}

// ===== POST — 新增/更新心得 =====
if ($method === 'POST') {
    $body = getJsonBody();
    $stopId = (int)($body['stop_id'] ?? 0);
    if (!$stopId) jsonResponse(['error' => 'stop_id required'], 400);

    $noteText = trim($body['note_text'] ?? '');

    $stmt = $db->prepare("
        INSERT INTO stop_notes (stop_id, note_text)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE note_text = VALUES(note_text)
    ");
    $stmt->execute([$stopId, $noteText]);

    jsonResponse(['status' => 'ok', 'stop_id' => $stopId]);
}

// ===== DELETE — 清空整筆心得 =====
if ($method === 'DELETE') {
    $stopId = (int)($_GET['stop_id'] ?? 0);
    if (!$stopId) jsonResponse(['error' => 'stop_id required'], 400);

    // 先取出 photos 路徑，順便刪實體檔
    $stmt = $db->prepare("SELECT photos FROM stop_notes WHERE stop_id = ?");
    $stmt->execute([$stopId]);
    $row = $stmt->fetch();
    if ($row && $row['photos']) {
        $photos = json_decode($row['photos'], true);
        $uploadsDir = __DIR__ . '/../uploads';
        foreach ($photos as $p) {
            $path = $uploadsDir . '/' . basename($p);
            if (file_exists($path)) @unlink($path);
        }
    }

    $stmt = $db->prepare("DELETE FROM stop_notes WHERE stop_id = ?");
    $stmt->execute([$stopId]);

    jsonResponse(['status' => 'ok', 'deleted' => $stopId]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
