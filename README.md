# SISTEM PRESENSI DOSEN AAU — Local Setup

Instruksi singkat untuk menjalankan versi PHP+MySQL pada XAMPP.

1. Buat database di MySQL (gunakan phpMyAdmin atau CLI):

   - contoh di phpMyAdmin: Import `sql/schema.sql` atau jalankan perintah SQL:
     - `CREATE DATABASE aau_presensi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;`
     - `USE aau_presensi;`
     - jalankan isi `sql/schema.sql` (sudah berisi CREATE TABLE dan seed)

2. Sesuaikan konfigurasi koneksi di `inc/db.php` bila perlu (username/password MySQL).

3. Jalankan XAMPP (Apache + MySQL).

4. Import schema & seed: di phpMyAdmin pilih database `aau_presensi` lalu import file `sql/schema.sql` atau jalankan SQL-nya.

5. Buka browser:
   - `http://localhost/aau/index.php`

6. Kredensial demo (setelah import):
   - Admin: `nidn: admin`, password: `admin`
   - Dosen contoh: `nidn: 10001` dan `nidn: 10002` — untuk mengatur password, gunakan tool berikut (di terminal):
     - `php tools/make_hash.php yourpassword` → copy hasil hash lalu jalankan SQL:
       - `UPDATE users SET password_hash = '<hash>' WHERE nidn = '10001';`

Catatan: Anda dapat mengganti password seed pada `sql/schema.sql` sebelum import dengan hash yang Anda inginkan (gunakan `tools/make_hash.php`).

Catatan:
- `api/save_presensi.php` memerlukan login (session) dan hanya lecturer yang mengampu kelas tersebut atau admin yang diperbolehkan menyimpan presensi.
- Saat ini beberapa halaman masih memiliki fallback client-side yang menyimpan ke `localStorage` jika server tidak tersedia.

--------------------------------------------------

## Opsi setup cepat (web-based)
Jika Anda kesulitan menjalankan import lewat phpMyAdmin atau CLI, buka `http://localhost/aau/setup.php` dan ikuti formulir untuk membuat database dan mengimpor `sql/schema.sql`.

> Peringatan: `setup.php` hanya untuk lingkungan pengembangan lokal — jangan jalankan di server publik.

## Tes endpoint cepat (Windows PowerShell)
Gunakan skrip PowerShell untuk memeriksa endpoint publik:

- Jalankan: `powershell -ExecutionPolicy Bypass -File tools\test_endpoints.ps1`
- Skrip akan memanggil beberapa API publik (`ping`, `get_classes`, `get_users`, `get_recent_presensi`) dan melaporkan status.

## Link publik sementara (ngrok)
Untuk berbagi aplikasi sementara melalui internet:

1. Download dan jalankan ngrok dari https://ngrok.com
2. Jalankan `ngrok http 80` (atau `ngrok http 8080` jika Apache Anda pakai port lain)
3. Ngrok akan menampilkan URL publik (https://xxxxxx.ngrok.io) yang bisa Anda bagikan.

### Web-based test suite
Saya telah menambahkan skrip pemeriksaan fungsional yang dapat dijalankan langsung dari mesin lokal Anda:

- Buka di browser (hanya dapat diakses dari mesin lokal): `http://localhost/aau/tools/test_suite.php`
- Script ini akan melakukan login sebagai `admin` (seeded), membuat user dan kelas percobaan, menyimpan presensi, lalu membersihkan entitas uji.
- Hasilnya ditampilkan sebagai laporan sederhana di halaman. Jika ada kegagalan, salin outputnya dan kirim ke saya agar saya bantu analisis.

Catatan: script hanya bekerja untuk lingkungan pengembangan lokal dan dibatasi untuk akses dari `127.0.0.1` / `::1`.
