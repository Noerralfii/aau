<?php
session_start();
require __DIR__ . '/inc/db.php';
if(empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'dosen'){
  header('Location: index.php'); exit;
}
$uid = $_SESSION['user_id'];
$me = fetch_one($pdo,'SELECT id, nidn, name FROM users WHERE id = ?', [$uid]);
$classes = fetch_all($pdo,'SELECT id, code, name, schedule FROM classes WHERE lecturer_id = ? ORDER BY name', [$uid]);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#0b3d91" />
  <title>Dashboard — SISTEM PRESENSI DOSEN AAU</title>
  <link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-dashboard">
  <a class="skip-link" href="#main">Lewati ke konten</a>
  <header class="site-header" role="banner">
    <div class="brand">
      <div class="logo" aria-hidden="true"> <!-- svg -->
        <svg width="44" height="44" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="30" fill="#0b3d91" /><path d="M20 34 L32 12 L44 34 L32 28 L20 34 Z" fill="#fff" /></svg>
      </div>
      <div class="title">SISTEM PRESENSI DOSEN AAU</div>
    </div>
    <nav class="header-actions" role="navigation" aria-label="Main navigation">
      <div id="userInfo" style="color:#fff;font-weight:700">Hai, <?=htmlspecialchars($me['name'])?></div>
      <button class="icon-btn menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="site-menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="2" fill="#ffffff" rx="1"/><rect x="3" y="11" width="18" height="2" fill="#ffffff" rx="1"/><rect x="3" y="16" width="18" height="2" fill="#ffffff" rx="1"/></svg>
      </button>
      <div id="site-menu" class="site-menu" hidden>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php" class="btn-logout">Logout</a>
      </div>
    </nav>
  </header>

  <main id="main" class="container">
    <section class="dashboard-intro">
      <h2>Selamat Datang, <?=htmlspecialchars($me['name'])?></h2>
      <p class="muted">Silakan pilih kelas untuk melihat jadwal dan melakukan presensi</p>
    </section>

    <section class="class-grid">
      <?php foreach($classes as $c): ?>
        <a href="class.php?class=<?=$c['id']?>" class="class-card" data-class="<?=$c['code']?>" data-href="class.php?class=<?=$c['id']?>" aria-label="<?=htmlspecialchars($c['name'])?> — <?=htmlspecialchars($c['schedule'])?>">
          <div class="class-meta">
            <h3 class="class-name"><?=htmlspecialchars($c['code'])?></h3>
            <p class="course"><?=htmlspecialchars($c['name'])?></p>
          </div>
          <div class="class-schedule"><?=htmlspecialchars($c['schedule'])?></div>
        </a>
      <?php endforeach; ?>
    </section>

  </main>

  <footer class="site-footer">
    <small>© AAU — Sistem Presensi Dosen</small>
  </footer>

  <script src="assets/js/app.js"></script>
</body>
</html>