<?php
/**
 * Migration：建立 vouchers 表
 * 一次性執行，跑完可刪除
 *
 * 使用：瀏覽器訪問 /api/migrate-vouchers.php
 */
require_once __DIR__ . '/config.php';

$db = getDB();

$db->exec("
    CREATE TABLE IF NOT EXISTS vouchers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        voucher_key VARCHAR(64) NOT NULL UNIQUE,
        order_no VARCHAR(255),
        link TEXT,
        note TEXT,
        photos JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_voucher_key (voucher_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

jsonResponse(['status' => 'ok', 'message' => 'vouchers table created']);
