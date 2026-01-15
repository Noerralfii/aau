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

$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');
$lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
$schedule = trim($_POST['schedule'] ?? '');

if(!$code || !$name || !$lecturer_id){ echo json_encode(['ok'=>false,'error'=>'Missing fields']); exit; }

$exists = fetch_one($pdo, 'SELECT id FROM classes WHERE code = ? LIMIT 1', [$code]);
if($exists){ echo json_encode(['ok'=>false,'error'=>'Class code exists']); exit; }

$stmt = $pdo->prepare('INSERT INTO classes (code, name, lecturer_id, schedule) VALUES (?, ?, ?, ?)');
$stmt->execute([$code, $name, $lecturer_id, $schedule]);
$id = $pdo->lastInsertId();

echo json_encode(['ok'=>true,'class'=>['id'=>$id,'code'=>$code,'name'=>$name,'lecturer_id'=>$lecturer_id,'schedule'=>$schedule]]);
