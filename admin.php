<?php
session_start();
require __DIR__ . '/inc/db.php';
if(empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin'){
  header('Location: index.php'); exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#0b3d91" />
  <title>Admin — SISTEM PRESENSI DOSEN AAU</title>
  <link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-admin">
  <a class="skip-link" href="#main">Lewati ke konten</a>
  <header class="site-header" role="banner">
    <div class="brand">
      <div class="logo" aria-hidden="true"> <!-- svg -->
        <svg width="44" height="44" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="30" fill="#0b3d91" /><path d="M20 34 L32 12 L44 34 L32 28 L20 34 Z" fill="#fff" /></svg>
      </div>
      <div class="title">SISTEM PRESENSI DOSEN AAU — Admin</div>
    </div>
    <nav class="header-actions" role="navigation" aria-label="Main navigation">
      <button class="icon-btn menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="site-menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="2" fill="#ffffff" rx="1"/><rect x="3" y="11" width="18" height="2" fill="#ffffff" rx="1"/><rect x="3" y="16" width="18" height="2" fill="#ffffff" rx="1"/></svg>
      </button>
      <div id="site-menu" class="site-menu" hidden>
        <a href="index.php">Home</a>
        <a href="admin.php">Admin</a>
        <a href="logout.php" class="btn-logout">Logout</a>
      </div>
    </nav>
  </header>

  <?php require __DIR__ . '/inc/csrf.php'; $csrf = csrf_token(); ?>
  <main id="main" class="container">
    <section class="admin-intro">
      <h2>Daftar Dosen</h2>
      <p class="muted">Klik nama dosen untuk melihat mata kuliah dan rekap presensi. Gunakan tombol 'Tambah' untuk menambahkan data baru.</p>
    </section>

    <section class="lecturer-list">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <div style="display:flex;gap:8px;align-items:center">
          <input id="searchLecturer" type="search" placeholder="Cari dosen..." style="padding:8px;border-radius:8px;border:1px solid var(--accent-gray);width:320px">
          <button id="refreshList" class="btn">Refresh</button>
        </div>
        <div style="display:flex;gap:8px">
          <button id="btnAddLecturer" class="btn btn-primary">Tambah Dosen</button>
          <button id="btnAddClass" class="btn">Tambah Kelas</button>
        </div>
      </div>

      <div id="lecturersGrid" style="display:grid;grid-template-columns:1fr;gap:10px"></div>

      <hr style="margin:18px 0">

      <section>
        <h3>Kelas</h3>
        <div id="classesGrid" style="display:grid;grid-template-columns:1fr;gap:10px;margin-top:8px"></div>
      </section>
    </section>

    <!-- Modals -->
    <div id="modalOverlay" class="modal" aria-hidden="true">
      <div class="modal-overlay" data-close="true"></div>
      <div class="modal-panel" role="document">
        <button class="modal-close" aria-label="Tutup">×</button>
        <div id="modalContent"></div>
      </div>
    </div>

  </main>

  <footer class="site-footer">
    <small>© AAU — Sistem Presensi Dosen</small>
  </footer>

  <script>
    window.AAU_CSRF_TOKEN = <?= json_encode($csrf) ?>;
  </script>
  <script src="assets/js/app.js"></script>
</body>
</html>