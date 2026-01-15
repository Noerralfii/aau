<?php
session_start();
require __DIR__ . '/inc/db.php';

$classParam = $_GET['class'] ?? null; // id
if(!$classParam){ header('Location: dashboard.php'); exit; }

// fetch class
$class = fetch_one($pdo, 'SELECT c.id, c.code, c.name, c.schedule, u.id as lecturer_id, u.name as lecturer_name FROM classes c JOIN users u ON c.lecturer_id = u.id WHERE c.id = ? LIMIT 1', [$classParam]);
if(!$class){ echo 'Kelas tidak ditemukan'; exit; }

// check current user
$uid = $_SESSION['user_id'] ?? null;
$meName = $_SESSION['user_name'] ?? 'Tamu';

?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="theme-color" content="#0b3d91" />
  <title>Kelas: <?=htmlspecialchars($class['code'])?> — PRESENSI</title>
  <link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-class" data-class-id="<?=$class['id']?>">
  <a class="skip-link" href="#main">Lewati ke konten</a>
  <header class="site-header" role="banner">
    <div class="brand">
      <div class="logo" aria-hidden="true"> <!-- svg -->
        <svg width="44" height="44" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="30" fill="#0b3d91" /><path d="M20 34 L32 12 L44 34 L32 28 L20 34 Z" fill="#fff" /></svg>
      </div>
      <div class="title">SISTEM PRESENSI DOSEN AAU</div>
    </div>
    <nav class="header-actions" role="navigation" aria-label="Main navigation">
      <a class="back-link" href="dashboard.php">← Kembali</a>
      <button class="icon-btn menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="site-menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="3" y="6" width="18" height="2" fill="#ffffff" rx="1"/><rect x="3" y="11" width="18" height="2" fill="#ffffff" rx="1"/><rect x="3" y="16" width="18" height="2" fill="#ffffff" rx="1"/></svg>
      </button>
      <div id="site-menu" class="site-menu" hidden>
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
      </div>
    </nav>
  </header>

  <main id="main" class="container">
    <section class="class-detail">
      <h2>Kelas: <?=htmlspecialchars($class['code'])?></h2>
      <div class="info-grid">
        <div>
          <strong>Mata Kuliah</strong>
          <div><?=htmlspecialchars($class['name'])?></div>
        </div>
        <div>
          <strong>Jadwal Mengajar</strong>
          <div><?=htmlspecialchars($class['schedule'])?></div>
        </div>
      </div>

      <div class="presensi-area">
        <h3>Presensi Hari Ini</h3>
        <div id="presensiStatus" class="presensi-box muted" role="status" aria-live="polite">Memeriksa jadwal…</div>
        <div class="presensi-actions">
          <fieldset class="presensi-form" id="presensiForm">
            <legend class="sr-only">Form Presensi</legend>
            <label><input type="radio" name="status" value="Hadir"> Hadir</label>
            <label><input type="radio" name="status" value="Izin"> Izin</label>
            <label><input type="radio" name="status" value="Tidak Hadir"> Tidak Hadir</label>
            <button id="saveBtn" class="btn" disabled>SIMPAN PRESENSI</button>
          </fieldset>
          <div class="note muted">Presensi hanya dapat dilakukan pada <strong>08:00 – 09:30</strong></div>

          <div class="time-tools" style="margin-top:8px;display:flex;gap:8px;align-items:center">
            <label style="display:flex;gap:8px;align-items:center"><input id="simulateToggle" type="checkbox"> Mode Simulasi</label>
            <input id="simulateTime" type="time" value="08:15" disabled aria-label="Waktu simulasi">
            <small class="muted">(Gunakan untuk menguji jendela presensi)</small>
          </div>
        </div>
      </div>

      <section class="rekap">
        <h3>Rekap Presensi</h3>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px">
          <div class="muted">Unduh atau kelola rekap presensi</div>
          <div style="display:flex;gap:8px">
            <button id="exportCsv" class="btn">Export CSV</button>
            <button id="clearRekap" class="btn" title="Hapus rekap (demo)">Hapus</button>
          </div>
        </div>
        <div class="table-wrap">
          <table class="rekap-table">
            <thead>
              <tr><th>Tanggal</th><th>Waktu Presensi</th><th>Status</th></tr>
            </thead>
            <tbody id="rekapBody"></tbody>
          </table>
        </div>
        <div class="summary">Total Kehadiran: <span id="totalCount">0</span> kali</div>
      </section>
    </section>
  </main>

  <footer class="site-footer">
    <small>© AAU — Sistem Presensi Dosen</small>
  </footer>

  <script src="assets/js/app.js"></script>
  <script>
    // preload class id and CSRF token for client-side scripts
    window.AAU_CLASS_ID = <?= (int)$class['id'] ?>;
    <?php require __DIR__ . '/inc/csrf.php'; ?>
    window.AAU_CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;
  </script>
</body>
</html>