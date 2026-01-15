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

$lecturer = $_POST['lecturer'] ?? null;
if(!$lecturer){ echo json_encode(['ok'=>false,'error'=>'lecturer required']); exit; }

// resolve id
$lect = fetch_one($pdo, 'SELECT id FROM users WHERE id = ? OR nidn = ? LIMIT 1', [$lecturer, $lecturer]);
if(!$lect) { echo json_encode(['ok'=>false,'error'=>'Dosen tidak ditemukan']); exit; }

$stmt = $pdo->prepare('DELETE FROM presensi WHERE lecturer_id = ?');
$stmt->execute([$lect['id']]);

echo json_encode(['ok'=>true,'deleted'=> $stmt->rowCount()]);
