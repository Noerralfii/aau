<?php
session_start();
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/csrf.php';
header('Content-Type: application/json; charset=utf-8');

if(empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin'){
    echo json_encode(['ok'=>false,'error'=>'Admin authentication required']); exit;
}
$token = $_POST['csrf_token'] ?? '';
if(!csrf_validate($token)){
    echo json_encode(['ok'=>false,'error'=>'Invalid CSRF token']); exit;
}

$id = (int)($_POST['id'] ?? 0);
$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');
$lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
$schedule = trim($_POST['schedule'] ?? '');

if(!$id || !$code || !$name || !$lecturer_id){ echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit; }

$stmt = $pdo->prepare('UPDATE classes SET code = ?, name = ?, lecturer_id = ?, schedule = ? WHERE id = ?');
$stmt->execute([$code, $name, $lecturer_id, $schedule, $id]);

echo json_encode(['ok'=>true]);
