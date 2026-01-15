<?php
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');
$since = (int)($_GET['since'] ?? 0);
// fetch presensi with id > since
$rows = fetch_all($pdo, 'SELECT p.id, p.date, p.time, p.status, c.code AS kelas, u.id AS lecturer_id, u.name AS lecturer FROM presensi p JOIN classes c ON p.class_id = c.id JOIN users u ON p.lecturer_id = u.id WHERE p.id > ? ORDER BY p.id ASC LIMIT 100', [$since]);

echo json_encode(['ok'=>true,'data'=>$rows]);
