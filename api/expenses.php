<?php
/**
 * 花費記帳 API
 *
 * GET    /api/expenses.php        → 取得所有花費
 * POST   /api/expenses.php        → 新增花費 body: { desc_text, amount, category? }
 * DELETE /api/expenses.php?id=5   → 刪除一筆
 * DELETE /api/expenses.php?all=1  → 清除全部
 */
require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { jsonResponse(['ok' => true]); }

$db = getDB();

if ($method === 'GET') {
    $rows = $db->query("SELECT * FROM expenses ORDER BY created_at DESC")->fetchAll();
    $total = array_sum(array_column($rows, 'amount'));
    jsonResponse(['expenses' => $rows, 'total_krw' => $total]);
}

if ($method === 'POST') {
    $body = getJsonBody();
    if (empty($body['desc_text']) || empty($body['amount'])) {
        jsonResponse(['error' => 'desc_text and amount required'], 400);
    }
    $stmt = $db->prepare("INSERT INTO expenses (desc_text, amount, category) VALUES (?, ?, ?)");
    $stmt->execute([
        $body['desc_text'],
        (int)$body['amount'],
        $body['category'] ?? '',
    ]);
    jsonResponse(['status' => 'ok', 'id' => $db->lastInsertId()], 201);
}

if ($method === 'DELETE') {
    if (isset($_GET['all'])) {
        $db->exec("DELETE FROM expenses");
        jsonResponse(['status' => 'ok', 'message' => 'all cleared']);
    }
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id required'], 400);
    $db->prepare("DELETE FROM expenses WHERE id = ?")->execute([$id]);
    jsonResponse(['status' => 'ok', 'deleted' => $id]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
