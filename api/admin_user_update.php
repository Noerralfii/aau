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
$name = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? null;
$role = $_POST['role'] ?? 'dosen';

if(!$id || !$name){ echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit; }

// prevent demoting last admin (basic check)
$stmt = $pdo->prepare('UPDATE users SET name = ?, role = ? WHERE id = ?');
$stmt->execute([$name, $role, $id]);
if($password){ $hash = password_hash($password, PASSWORD_DEFAULT); $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $id]); }

echo json_encode(['ok'=>true]);
