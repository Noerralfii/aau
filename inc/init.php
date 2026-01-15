<?php
// Session and security defaults
// Set strict mode and cookie params
if (session_status() === PHP_SESSION_NONE) {
    // use secure cookie when running under HTTPS
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    // Set cookie params before session_start
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Simple rate limiter helper (per-session)
if(!function_exists('rate_limit_check')){
function rate_limit_check($key, $limit = 10, $window = 60){
    if(!isset($_SESSION['_rl'])) $_SESSION['_rl'] = [];
    $now = time();
    $arr = &$_SESSION['_rl'];
    if(!isset($arr[$key])) $arr[$key] = [];
    // drop old
    $arr[$key] = array_filter($arr[$key], function($t) use ($now, $window){ return ($now - $t) < $window; });
    if(count($arr[$key]) >= $limit) return false;
    $arr[$key][] = $now;
    return true;
}
}

if(!function_exists('json_output')){
function json_output($data, $code = 200){
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
}
