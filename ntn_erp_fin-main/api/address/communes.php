<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/erp/config/auth.php';
header('Content-Type: application/json');
requireLogin();

$district_code = trim($_GET['district_code'] ?? '');
if (empty($district_code)) { echo json_encode([]); exit; }

$pdo  = getDBConnection();
$stmt = $pdo->prepare("SELECT code, name, full_name FROM communes WHERE district_code = ? ORDER BY name");
$stmt->execute([$district_code]);
echo json_encode($stmt->fetchAll());