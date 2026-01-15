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

$nidn = trim($_POST['nidn'] ?? '');
$name = trim($_POST['name'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'dosen';

if(!$nidn || !$name || !$password){
    echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit;
}

// check unique nidn
$existing = fetch_one($pdo, 'SELECT id FROM users WHERE nidn = ? LIMIT 1', [$nidn]);
if($existing){ echo json_encode(['ok'=>false,'error'=>'NIDN already exists']); exit; }

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (nidn, name, password_hash, role) VALUES (?, ?, ?, ?)');
$stmt->execute([$nidn,$name,$hash,$role]);
$id = $pdo->lastInsertId();

echo json_encode(['ok'=>true,'user'=>['id'=>$id,'nidn'=>$nidn,'name'=>$name,'role'=>$role]]);
