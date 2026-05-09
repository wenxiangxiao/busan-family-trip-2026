<?php
/**
 * Migration：建立 stop_notes 表
 * 一次性執行，跑完可刪除
 *
 * 使用：瀏覽器訪問 /api/migrate-notes.php
 */
require_once __DIR__ . '/config.php';

$db = getDB();

$db->exec("
    CREATE TABLE IF NOT EXISTS stop_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stop_id INT NOT NULL UNIQUE,
        note_text TEXT,
        photos JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_stop_id (stop_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

jsonResponse(['status' => 'ok', 'message' => 'stop_notes table created']);
