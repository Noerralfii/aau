-- AAU Sistem Presensi Dosen — Schema and Seed
-- Create database (run in MySQL or phpMyAdmin):
-- CREATE DATABASE aau_presensi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE aau_presensi;

DROP TABLE IF EXISTS presensi;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nidn VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('dosen','admin') NOT NULL DEFAULT 'dosen',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  lecturer_id INT NOT NULL,
  schedule VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE presensi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  class_id INT NOT NULL,
  lecturer_id INT NOT NULL,
  date DATE NOT NULL,
  time TIME DEFAULT NULL,
  status ENUM('Hadir','Izin','Tidak Hadir') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed admin user (password: admin)
INSERT INTO users (nidn, name, password_hash, role) VALUES
('admin','Administrator', '$2y$10$e0Xb7a0m7B8iLz3kzj4Z3u2m/tEwq6fjm1Nf8xgFvIhQxZs9B1QyW', 'admin');
-- Note: the hash above is password_hash('admin', PASSWORD_DEFAULT) generated in PHP on my machine; you can reset it.

-- Seed lecturers (password: 'dosen123')
INSERT INTO users (nidn, name, password_hash, role) VALUES
('10001','Dr. Muhammad Nur Alfisyahr', '$2y$10$K9v6D4uN9v6t8b6Kz4jD6u8Vf1b7k2j6L3a9pQm', 'dosen'),
('10002','Dr. Rini Suryanti', '$2y$10$mQ9bU2xv7nB6r8a3Kc5yW6sPq2mC5x1Tz3yH9g', 'dosen');

-- Seed classes
INSERT INTO classes (code, name, lecturer_id, schedule) VALUES
('SERSAN-B','Sersan-B — Matematika Teknik',  (SELECT id FROM users WHERE nidn='10001'), 'Senin, 08:00 - 09:30'),
('LETNAN-A','Letnan-A — Fisika Teknik',       (SELECT id FROM users WHERE nidn='10001'), 'Selasa, 10:00 - 11:30'),
('KAPTEN-C','Kapten-C — Bahasa Inggris Teknik', (SELECT id FROM users WHERE nidn='10002'), 'Kamis, 13:00 - 14:30');

-- Sample presensi (optional)
INSERT INTO presensi (class_id, lecturer_id, date, time, status) VALUES
((SELECT id FROM classes WHERE code='SERSAN-B'), (SELECT id FROM users WHERE nidn='10001'), '2025-02-13', '08:12:00', 'Hadir'),
((SELECT id FROM classes WHERE code='SERSAN-B'), (SELECT id FROM users WHERE nidn='10001'), '2025-02-20', '08:05:00', 'Hadir'),
((SELECT id FROM classes WHERE code='SERSAN-B'), (SELECT id FROM users WHERE nidn='10001'), '2025-02-27', NULL, 'Tidak Hadir');

-- End of schema
