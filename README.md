# SIM Mahasiswa UMB

Sistem Informasi Manajemen Mahasiswa — PHP Native + MySQLi (MySQL/MariaDB).

## Cara menjalankan (XAMPP / Laragon)
1. Salin folder ini ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buat database `sim_mahasiswa` (collation `utf8mb4_unicode_ci`) di phpMyAdmin.
3. Import `database/sim_mahasiswa.sql` ke database tersebut.
4. Koneksi default ada di `koneksi.php` (host `localhost`, user `root`, password kosong).
   Untuk hosting, salin `config.production.example.php` menjadi `config.production.php`.
5. Buka `http://localhost/simmahasiswaumb/login.php`.

## Role
`ADMIN`, `OPERATOR_PRODI`, `DEKANAT`, `REKTORAT`, `MAHASISWA`

## Struktur database
`roles`, `universitas`, `fakultas`, `program_studi`, `mahasiswa`, `pengguna`, `audit_log`
