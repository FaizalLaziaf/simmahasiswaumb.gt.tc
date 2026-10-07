<?php
/**
 * koneksi.php
 * Koneksi database MySQL/MariaDB
 * Sistem Informasi Manajemen Mahasiswa
 * PHP Native + MySQLi
 *
 * Otomatis memakai config.production.php jika file itu ada (di hosting),
 * jika tidak ada memakai konfigurasi lokal XAMPP/Laragon.
 */

// Konfigurasi default: lokal XAMPP/Laragon
$host     = "localhost";
$username = "root";
$password = "";
$database = "sim_mahasiswa";
$port     = 3306;

// Di hosting (InfinityFree), file ini menimpa nilai di atas
if (file_exists(__DIR__ . '/config.production.php')) {
    require __DIR__ . '/config.production.php';
}

// Membuat koneksi
$koneksi = mysqli_connect(
    $host,
    $username,
    $password,
    $database,
    $port
);

// Memeriksa koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Mengatur charset agar mendukung UTF-8
mysqli_set_charset($koneksi, "utf8mb4");
?>
