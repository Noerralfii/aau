<?php
require __DIR__ . '/inc/init.php';
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';

$nidn = $_POST['nidn'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'dosen';

if(!$nidn || !$password){
    $_SESSION['error'] = 'Masukkan NIDN dan password';
    header('Location: index.php'); exit;
}

// simple rate limit per-session for login attempts
if(isset($_SESSION['login_locked_until']) && time() < $_SESSION['login_locked_until']){
    $_SESSION['error'] = 'Akun sementara dikunci karena terlalu banyak percobaan. Coba lagi nanti.';
    header('Location: index.php'); exit;
}

// increment attempts
if(!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;

// fetch user by nidn
$user = fetch_one($pdo, 'SELECT id, nidn, name, password_hash, role FROM users WHERE nidn = ? LIMIT 1', [$nidn]);
if(!$user){
    $_SESSION['login_attempts']++;
    if($_SESSION['login_attempts'] >= 5){ $_SESSION['login_locked_until'] = time() + 300; }
    $_SESSION['error'] = 'Akun tidak ditemukan';
    header('Location: index.php'); exit;
}

if(!password_verify($password, $user['password_hash'])){
    $_SESSION['login_attempts']++;
    if($_SESSION['login_attempts'] >= 5){ $_SESSION['login_locked_until'] = time() + 300; }
    $_SESSION['error'] = 'Password salah';
    header('Location: index.php'); exit;
}

// check role
if($role === 'admin' && $user['role'] !== 'admin'){
    $_SESSION['error'] = 'Akun bukan admin';
    header('Location: index.php'); exit;
}

// login success: reset attempts and regenerate session id
unset($_SESSION['login_attempts']); unset($_SESSION['login_locked_until']);
session_regenerate_id(true);

// login success
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

if($user['role'] === 'admin'){
    header('Location: admin.php'); exit;
} else {
    header('Location: dashboard.php'); exit;
}
?>