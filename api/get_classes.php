<?php
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');

$lecturer = $_GET['lecturer'] ?? null;
if($lecturer){
    $rows = fetch_all($pdo, 'SELECT c.id, c.code, c.name, c.schedule, u.id AS lecturer_id, u.name AS lecturer_name FROM classes c JOIN users u ON c.lecturer_id = u.id WHERE u.id = ? OR u.nidn = ? ORDER BY c.name', [$lecturer, $lecturer]);
} else {
    $rows = fetch_all($pdo, 'SELECT c.id, c.code, c.name, c.schedule, u.id AS lecturer_id, u.name AS lecturer_name FROM classes c JOIN users u ON c.lecturer_id = u.id ORDER BY c.name');
}

echo json_encode(['ok' => true, 'data' => $rows]);
