<?php
// helper functions for validation and consistent JSON responses
require_once __DIR__ . '/init.php';

function json_ok($data = []){
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => true], $data));
    exit;
}

function json_err($msg = 'error', $code = 400){
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function validate_password($pw){
    if(strlen($pw) < 8) return 'Password minimal 8 karakter';
    if(!preg_match('/[0-9]/', $pw)) return 'Password harus memuat angka';
    if(!preg_match('/[a-zA-Z]/', $pw)) return 'Password harus memuat huruf';
    return true;
}

function sanitize_str($s, $max = 255){
    $s = trim((string)$s);
    $s = strip_tags($s);
    if($max) $s = mb_substr($s, 0, $max);
    return $s;
}

function check_enum($value, $allowed){
    return in_array($value, $allowed, true);
}
