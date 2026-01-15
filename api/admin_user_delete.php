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
if(!$id){ echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

// prevent deleting self
if($id == $_SESSION['user_id']){ echo json_encode(['ok'=>false,'error'=>'Cannot delete your own account']); exit; }

$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);

echo json_encode(['ok'=>true,'deleted'=>$stmt->rowCount()]);
