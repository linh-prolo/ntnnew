<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/functions.php';
header('Content-Type: application/json');
requireLogin();
requireRole('director', 'accountant', 'manager', 'warehouse');
ensurePostCsrf();

$pdo = getDBConnection();
$id = (int)($_POST['id'] ?? 0);
$name = trim((string)($_POST['name'] ?? ''));
$description = trim((string)($_POST['description'] ?? '')) ?: null;
$isActive = (int)($_POST['is_active'] ?? 1) ? 1 : 0;

if ($name === '') {
    echo json_encode(['ok' => false, 'msg' => 'Thiếu tên nhóm']);
    exit;
}

if ($id > 0) {
    $ok = $pdo->prepare('UPDATE wa_categories SET name = ?, description = ?, is_active = ? WHERE id = ?')->execute([$name, $description, $isActive, $id]);
} else {
    $ok = $pdo->prepare('INSERT INTO wa_categories (name, description, is_active) VALUES (?, ?, ?)')->execute([$name, $description, $isActive]);
    $id = (int)$pdo->lastInsertId();
}

echo json_encode(['ok' => (bool)$ok, 'id' => $id]);
