<?php
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');

$id = $_GET['id'] ?? null;
$q = $_GET['q'] ?? null;

if($id){
    $rows = fetch_all($pdo, 'SELECT id, nidn, name, role FROM users WHERE id = ? LIMIT 1', [$id]);
} elseif($q) {
    $rows = fetch_all($pdo, 'SELECT id, nidn, name, role FROM users WHERE name LIKE ? OR nidn LIKE ? ORDER BY name', ["%$q%","%$q%"]);
} else {
    $rows = fetch_all($pdo, 'SELECT id, nidn, name, role FROM users ORDER BY name');
}

echo json_encode(['ok'=>true,'data'=>$rows]);
