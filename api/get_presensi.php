<?php
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');

$class = $_GET['class'] ?? null;
$lecturer = $_GET['lecturer'] ?? null;

if(!$class && !$lecturer){
    echo json_encode(['ok'=>false,'error'=>'class or lecturer required']);
    exit;
}

if($class){
    $rows = fetch_all($pdo, 'SELECT p.id, c.code as kelas, p.date, p.time, p.status, u.name as lecturer FROM presensi p JOIN classes c ON p.class_id = c.id JOIN users u ON p.lecturer_id = u.id WHERE c.id = ? ORDER BY p.date DESC, p.time DESC', [$class]);
} else {
    $rows = fetch_all($pdo, 'SELECT p.id, c.code as kelas, p.date, p.time, p.status FROM presensi p JOIN classes c ON p.class_id = c.id WHERE p.lecturer_id = ? ORDER BY p.date DESC, p.time DESC', [$lecturer]);
}

echo json_encode(['ok'=>true, 'data'=>$rows]);
