<?php
session_start();
require __DIR__ . '/../inc/db.php';
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../inc/init.php';
require __DIR__ . '/../inc/db.php';
require __DIR__ . '/../inc/csrf.php';
require __DIR__ . '/../inc/helpers.php';

if(empty($_SESSION['user_id'])){
    json_err('Not authenticated', 401);
}
$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'dosen';

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if(!csrf_validate($token)){
    json_err('Invalid CSRF token', 403);
}

// POST params: class_id OR class_code, status, optional simulate_time (HH:MM)
$class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : null;
$class_code = isset($_POST['class_code']) ? sanitize_str($_POST['class_code']) : null;
$status = isset($_POST['status']) ? sanitize_str($_POST['status']) : null;
$simulate_time = isset($_POST['simulate_time']) ? sanitize_str($_POST['simulate_time'], 5) : null;

if(!$status || (!$class_id && !$class_code)){
    json_err('Missing parameters');
}

// validate status
$allowed = ['Hadir','Izin','Tidak Hadir'];
if(!in_array($status, $allowed, true)) json_err('Invalid status');

// resolve class id
if(!$class_id){
    $row = fetch_one($pdo, 'SELECT id, lecturer_id FROM classes WHERE code = ? LIMIT 1', [$class_code]);
    if(!$row){ json_err('Class not found', 404); }
    $class_id = $row['id'];
    $class_lecturer = (int)$row['lecturer_id'];
} else {
    $row = fetch_one($pdo, 'SELECT id, lecturer_id FROM classes WHERE id = ? LIMIT 1', [$class_id]);
    if(!$row){ json_err('Class not found', 404); }
    $class_lecturer = (int)$row['lecturer_id'];
}

// Authorization: only the lecturer for that class or admin can save
if($user_id !== $class_lecturer && $user_role !== 'admin'){
    json_err('Not authorized to save for this class', 403);
}

// optional rate-limit per-session on presensi submissions (prevent accidental spam)
if(!rate_limit_check('presensi_submit', 6, 60)){
    json_err('Too many submissions, slow down', 429);
}

// time handling
$date = date('Y-m-d');
$time = date('H:i:s');
if($simulate_time){
    // simulate_time is HH:MM
    $parts = explode(':', $simulate_time);
    if(count($parts) >= 2){
        $h = intval($parts[0]); $m = intval($parts[1]);
        if($h>=0 && $h<24 && $m>=0 && $m<60) $time = sprintf('%02d:%02d:00', $h, $m);
    }
}

// insert
$stmt = $pdo->prepare('INSERT INTO presensi (class_id, lecturer_id, date, time, status) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$class_id, $user_id, $date, $time, $status]);

json_ok(['message'=>'Presensi tersimpan','data'=>['id'=>$pdo->lastInsertId(),'date'=>$date,'time'=>$time,'status'=>$status]]);
