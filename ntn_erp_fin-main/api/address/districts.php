<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
header('Content-Type: application/json');
requireLogin();

$province_code = trim($_GET['province_code'] ?? '');
if (empty($province_code)) { echo json_encode([]); exit; }

$pdo  = getDBConnection();
$stmt = $pdo->prepare("SELECT code, name, full_name FROM districts WHERE province_code = ? ORDER BY name");
$stmt->execute([$province_code]);
echo json_encode($stmt->fetchAll());