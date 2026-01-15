<?php
// Simple web installer: create DB and import schema.sql
// WARNING: run this only on a local/dev machine (not production)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? '127.0.0.1';
    $db = $_POST['dbname'] ?? 'aau_presensi';
    $user = $_POST['user'] ?? 'root';
    $pass = $_POST['pass'] ?? '';

    $errors = [];
    try {
        // connect without dbname so we can create DB
        $dsn = "mysql:host={$host};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

        // import schema
        $sqlFile = __DIR__ . '/sql/schema.sql';
        if (!is_readable($sqlFile)) throw new Exception('schema.sql not found or not readable');
        $sql = file_get_contents($sqlFile);
        // split on semicolons; simple but effective for this small schema
        $stmts = array_filter(array_map('trim', explode(";", $sql)));

        // connect to the created DB
        $dsnDb = "mysql:host={$host};dbname={$db};charset=utf8mb4";
        $pdoDb = new PDO($dsnDb, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        foreach ($stmts as $s) {
            // skip comments
            if (strpos($s, '/*') !== false || strpos($s, '--') === 0) continue;
            try { $pdoDb->exec($s); } catch (Exception $e) {
                // collect but continue
                $errors[] = "Statement failed: " . substr($s, 0, 120) . ' — ' . $e->getMessage();
            }
        }

        $success = true;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Setup — AAU Presensi</title>
  <style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;max-width:760px;margin:40px auto;color:#222;padding:16px}label{display:block;margin-top:8px}input{padding:8px;width:100%;box-sizing:border-box}button{margin-top:12px;padding:8px 12px}</style>
</head>
<body>
  <h1>Setup Database — AAU Presensi</h1>
  <?php if(!empty($errors)): ?>
    <div style="background:#fff3cd;border:1px solid #ffeeba;padding:12px;border-radius:6px;margin-bottom:12px"><strong>Pemberitahuan:</strong><ul><?php foreach($errors as $err) echo '<li>'.htmlspecialchars($err).'</li>'; ?></ul></div>
  <?php endif; ?>

  <?php if(!empty($success)): ?>
    <div style="background:#d4edda;border:1px solid #c3e6cb;padding:12px;border-radius:6px;margin-bottom:12px">Database berhasil dibuat / diimpor.<br>
    Edit <code>inc/db.php</code> jika perlu, lalu buka <a href="index.php">index.php</a>.</div>
  <?php endif; ?>

  <form method="POST">
    <label>Host <input name="host" value="127.0.0.1"></label>
    <label>Database name <input name="dbname" value="aau_presensi"></label>
    <label>MySQL username <input name="user" value="root"></label>
    <label>MySQL password <input name="pass" value=""></label>
    <div style="font-size:0.9em;color:#666;margin-top:8px">Catatan: script ini hanya untuk mempermudah setup lokal. Jangan jalankan di server publik.</div>
    <button type="submit">Buat Database & Import Schema</button>
  </form>
</body>
</html>