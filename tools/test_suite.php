<?php
// Local test suite for AAU app. Run from browser on the same host: http://localhost/aau/tools/test_suite.php
// Security: only allow requests from localhost
$allowed = ['127.0.0.1','::1','localhost'];
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if(!in_array($remote, ['127.0.0.1','::1'])){
    http_response_code(403);
    echo "Forbidden: test runner only available from localhost.\n";
    exit;
}

function curl($url, $opts = []){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if(isset($opts['post'])){ curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['post']); }
    if(isset($opts['cookiejar'])){ curl_setopt($ch, CURLOPT_COOKIEJAR, $opts['cookiejar']); curl_setopt($ch, CURLOPT_COOKIEFILE, $opts['cookiejar']); }
    if(isset($opts['headers'])){ curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']); }
    $res = curl_exec($ch);
    $err = curl_errno($ch) ? curl_error($ch) : null;
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['body'=>$res,'err'=>$err,'code'=>$code];
}

$base = (isset($_SERVER['HTTP_HOST']) ? (preg_match('#:\d+$#', $_SERVER['HTTP_HOST']) ? 'http://'.$_SERVER['HTTP_HOST'].'/aau' : 'http://localhost/aau') : 'http://localhost/aau');
$cookiejar = sys_get_temp_dir() . '/aau_test_cookie.txt';
@unlink($cookiejar);

$results = [];

// 1) ping
$r = curl($base . '/api/ping.php');
$ok = ($r['err'] === null && $r['code'] === 200 && strpos($r['body'], '"ok"') !== false);
$results[] = ['test'=>'ping','ok'=>$ok,'detail'=>$r];

// 2) get_users
$r = curl($base . '/api/get_users.php');
$ok = ($r['err'] === null && $r['code'] === 200 && strpos($r['body'], '"ok"') !== false);
$results[] = ['test'=>'get_users','ok'=>$ok,'detail'=>$r];

// parse to find a lecturer id (nidn 10001)
$lecturerId = null;
if($ok){
    $j = json_decode($r['body'], true);
    if($j && !empty($j['data'])){
        foreach($j['data'] as $u){ if($u['nidn'] === '10001'){ $lecturerId = $u['id']; break; } }
    }
}

// 3) login as admin (nidn=admin, password=admin)
$login = ['nidn'=>'admin','password'=>'admin','role'=>'admin'];
$r = curl($base . '/auth.php', ['post'=>$login, 'cookiejar'=>$cookiejar]);
// auth.php redirects to admin.php on success — check cookie file present and admin page content
$adminPage = curl($base . '/admin.php', ['cookiejar'=>$cookiejar]);
$ok = ($adminPage['err'] === null && $adminPage['code'] === 200 && strpos($adminPage['body'], 'AAU — Admin') !== false);
$results[] = ['test'=>'admin_login','ok'=>$ok,'detail'=>$adminPage];

// extract CSRF token from admin page JS: window.AAU_CSRF_TOKEN = "....";
$csrf = null;
if($ok){ if(preg_match('/window\.AAU_CSRF_TOKEN\s*=\s*(?:json_encode\()?\s*(?:\'?\"?)([a-f0-9]{48}|[^"]+)(?:\'?\"?)/i', $adminPage['body'], $m)){
    $csrf = $m[1];
} else if(preg_match('/window\.AAU_CSRF_TOKEN\s*=\s*([\"\'])(.+?)\1/', $adminPage['body'], $m2)){
    $csrf = $m2[2];
}}
$results[] = ['test'=>'csrf_extraction','ok'=>!empty($csrf),'csrf'=>$csrf];

// 4) admin create user (if csrf and login ok)
$createdUserId = null;
if($csrf){
    $payload = ['nidn'=>'TST' . rand(1000,9999),'name'=>'Test User','password'=>'testpass123','role'=>'dosen','csrf_token'=>$csrf];
    $r = curl($base . '/api/admin_user_create.php', ['post'=>$payload,'cookiejar'=>$cookiejar]);
    $ok = ($r['err'] === null && $r['code'] === 200 && strpos($r['body'], '"ok"') !== false);
    $json = json_decode($r['body'], true);
    if($ok && !empty($json['user']['id'])) $createdUserId = $json['user']['id'];
    $results[] = ['test'=>'admin_create_user','ok'=>$ok,'detail'=>$json];
}

// 5) admin create class (assign to lecturer found earlier)
$createdClassId = null;
if($csrf && $lecturerId){
    $payload = ['code'=>'TST-' . rand(100,999),'name'=>'Test Class','lecturer_id'=>$lecturerId,'schedule'=>'Senin 08:00','csrf_token'=>$csrf];
    $r = curl($base . '/api/admin_class_create.php', ['post'=>$payload,'cookiejar'=>$cookiejar]);
    $json = json_decode($r['body'], true);
    $ok = ($r['err'] === null && $r['code'] === 200 && !empty($json['class']['id']));
    if($ok) $createdClassId = $json['class']['id'];
    $results[] = ['test'=>'admin_create_class','ok'=>$ok,'detail'=>$json];
}

// 6) attempt save_presensi as admin for that class
$presensiId = null;
if($createdClassId && $csrf){
    $payload = ['class_id'=>$createdClassId,'status'=>'Hadir','csrf_token'=>$csrf];
    $r = curl($base . '/api/save_presensi.php', ['post'=>$payload,'cookiejar'=>$cookiejar]);
    $json = json_decode($r['body'], true);
    $ok = ($r['err'] === null && $r['code'] === 200 && !empty($json['data']['id']));
    if($ok) $presensiId = $json['data']['id'];
    $results[] = ['test'=>'save_presensi','ok'=>$ok,'detail'=>$json];
}

// 7) get_recent_presensi check
$r = curl($base . '/api/get_recent_presensi.php?since=0');
$ok = ($r['err'] === null && $r['code'] === 200 && strpos($r['body'], '"ok"') !== false);
$results[] = ['test'=>'get_recent_presensi','ok'=>$ok,'detail'=>json_decode($r['body'], true)];

// 8) cleanup created class and user
$cleanup = [];
if($csrf && $createdClassId){ $r = curl($base . '/api/admin_class_delete.php', ['post'=>['id'=>$createdClassId,'csrf_token'=>$csrf],'cookiejar'=>$cookiejar]); $cleanup[] = ['deleted_class'=>$r['body']]; }
if($csrf && $createdUserId){ $r = curl($base . '/api/admin_user_delete.php', ['post'=>['id'=>$createdUserId,'csrf_token'=>$csrf],'cookiejar'=>$cookiejar]); $cleanup[] = ['deleted_user'=>$r['body']]; }

// Output results as simple HTML report
?><!doctype html>
<html><head><meta charset="utf-8"><title>AAU Test Suite</title><style>body{font-family:system-ui,Segoe UI,Arial;padding:20px}pre{background:#f7f7f9;border:1px solid #eee;padding:12px;border-radius:6px} .ok{color:green}.fail{color:red}</style></head><body>
<h1>AAU Test Suite Results</h1>
<ul>
<?php foreach($results as $r): ?>
  <li><strong><?=htmlspecialchars($r['test'])?></strong>: <?php if(!empty($r['ok'])) echo '<span class="ok">OK</span>'; else echo '<span class="fail">FAIL</span>'; ?>
    <pre><?php echo htmlspecialchars(json_encode($r['detail'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); ?></pre>
  </li>
<?php endforeach; ?>
</ul>
<h2>Cleanup</h2>
<pre><?php echo htmlspecialchars(json_encode($cleanup, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)); ?></pre>
<p>Note: This script runs only from localhost and performs create/delete actions to verify endpoints. If you want additional checks (login as lecturer, CSV export, export presensi per class), tell me and I will extend it.</p>
</body></html>
