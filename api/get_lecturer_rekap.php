<?php
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');

$lecturer = $_GET['lecturer'] ?? null;
if(!$lecturer){ echo json_encode(['ok'=>false,'error'=>'lecturer required']); exit; }

// allow nidn or id
$lect = fetch_one($pdo, 'SELECT id, nidn, name FROM users WHERE id = ? OR nidn = ? LIMIT 1', [$lecturer, $lecturer]);
if(!$lect){ echo json_encode(['ok'=>false,'error'=>'Dosen tidak ditemukan']); exit; }

$rows = fetch_all($pdo, 'SELECT p.id, c.code AS kelas, p.date, p.time, p.status FROM presensi p JOIN classes c ON p.class_id = c.id WHERE p.lecturer_id = ? ORDER BY p.date DESC, p.time DESC', [$lect['id']]);
$hadirCount = 0; foreach($rows as $r) if($r['status'] === 'Hadir') $hadirCount++;
$salary = $hadirCount * 200000; 

echo json_encode(['ok'=>true,'lecturer'=>$lect,'rows'=>$rows,'hadir'=>$hadirCount,'salary'=>$salary]);
