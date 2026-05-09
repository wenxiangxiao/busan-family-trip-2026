<?php
/**
 * 行程照片上傳 API
 *
 * POST   /api/photos.php                    → 上傳一張照片
 *                                              FormData: stop_id, photo (檔案)
 *                                              回傳: { status:'ok', path:'2026-05/abc.jpg' }
 * DELETE /api/photos.php?stop_id=5&path=... → 刪一張照片
 */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { jsonResponse(['ok' => true]); }

$db = getDB();
$uploadsDir = __DIR__ . '/../uploads';

if (!is_dir($uploadsDir)) {
    @mkdir($uploadsDir, 0755, true);
}

// ===== POST — 上傳照片 =====
if ($method === 'POST') {
    $stopId = (int)($_POST['stop_id'] ?? 0);
    if (!$stopId) jsonResponse(['error' => 'stop_id required'], 400);

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'photo upload failed'], 400);
    }

    $file = $_FILES['photo'];
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse(['error' => 'photo too large (max 5MB)'], 400);
    }

    // 偵測 MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowedMime[$mime])) {
        jsonResponse(['error' => 'unsupported image type: ' . $mime], 400);
    }
    $ext = $allowedMime[$mime];

    // 月份子資料夾
    $monthDir = date('Y-m');
    $targetDir = $uploadsDir . '/' . $monthDir;
    if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);

    $filename = $stopId . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $relPath = $monthDir . '/' . $filename;
    $absPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $absPath)) {
        jsonResponse(['error' => 'move_uploaded_file failed'], 500);
    }

    // 把路徑寫進 stop_notes.photos
    $stmt = $db->prepare("SELECT photos FROM stop_notes WHERE stop_id = ?");
    $stmt->execute([$stopId]);
    $row = $stmt->fetch();

    $photos = ($row && $row['photos']) ? json_decode($row['photos'], true) : [];
    $photos[] = $relPath;
    $photosJson = json_encode($photos, JSON_UNESCAPED_UNICODE);

    if ($row) {
        $stmt = $db->prepare("UPDATE stop_notes SET photos = ? WHERE stop_id = ?");
        $stmt->execute([$photosJson, $stopId]);
    } else {
        $stmt = $db->prepare("INSERT INTO stop_notes (stop_id, photos) VALUES (?, ?)");
        $stmt->execute([$stopId, $photosJson]);
    }

    jsonResponse(['status' => 'ok', 'path' => $relPath, 'photos' => $photos]);
}

// ===== DELETE — 刪一張 =====
if ($method === 'DELETE') {
    $stopId = (int)($_GET['stop_id'] ?? 0);
    $path = $_GET['path'] ?? '';
    if (!$stopId || !$path) jsonResponse(['error' => 'stop_id and path required'], 400);

    // 防止路徑穿越
    if (strpos($path, '..') !== false || strpos($path, '/') === 0) {
        jsonResponse(['error' => 'invalid path'], 400);
    }

    $stmt = $db->prepare("SELECT photos FROM stop_notes WHERE stop_id = ?");
    $stmt->execute([$stopId]);
    $row = $stmt->fetch();
    if (!$row) jsonResponse(['error' => 'stop note not found'], 404);

    $photos = json_decode($row['photos'], true) ?: [];
    $photos = array_values(array_filter($photos, fn($p) => $p !== $path));

    $stmt = $db->prepare("UPDATE stop_notes SET photos = ? WHERE stop_id = ?");
    $stmt->execute([json_encode($photos, JSON_UNESCAPED_UNICODE), $stopId]);

    // 刪實體檔（路徑長度 > 0、basename 安全）
    $absPath = $uploadsDir . '/' . $path;
    if (file_exists($absPath)) @unlink($absPath);

    jsonResponse(['status' => 'ok', 'photos' => $photos]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
