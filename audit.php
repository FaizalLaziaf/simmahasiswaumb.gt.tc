<?php
/**
 * audit.php
 * Helper pencatatan Audit Log untuk operasi INSERT / UPDATE / DELETE.
 * (LOGIN dan LOGOUT dicatat langsung di auth.php dan logout.php)
 */

/* Ambil satu baris tabel (tanpa kolom password_hash) untuk disimpan sebagai snapshot */
function ambil_baris(string $tabel, string $kolom_id, $id): ?array
{
    global $koneksi;
    $boleh = ['roles', 'universitas', 'fakultas', 'program_studi', 'mahasiswa', 'pengguna'];
    if (!in_array($tabel, $boleh, true)) { return null; }

    $st = mysqli_prepare($koneksi, "SELECT * FROM `$tabel` WHERE `$kolom_id` = ? LIMIT 1");
    if (!$st) { return null; }
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st)) ?: null;
    mysqli_stmt_close($st);

    if ($row) { unset($row['password_hash']); }
    return $row;
}

/* Catat satu aktivitas ke tabel audit_log. Kegagalan audit tidak boleh mengganggu proses utama. */
function catat_audit(string $tabel, $record_id, string $aksi, ?array $lama = null, ?array $baru = null): void
{
    global $koneksi;

    $id_pengguna = isset($_SESSION['id_pengguna']) ? (int)$_SESSION['id_pengguna'] : null;
    $rid   = (string)$record_id;
    $json  = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $d_lama = $lama !== null ? json_encode($lama, $json) : null;
    $d_baru = $baru !== null ? json_encode($baru, $json) : null;
    $ip    = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

    try {
        $st = mysqli_prepare(
            $koneksi,
            "INSERT INTO audit_log (id_pengguna, tabel_nama, record_id, aksi, data_lama, data_baru, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$st) { return; }
        mysqli_stmt_bind_param($st, "isssssss", $id_pengguna, $tabel, $rid, $aksi, $d_lama, $d_baru, $ip, $agent);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    } catch (Throwable $e) {
        // PHP 8.1+ melempar exception; kegagalan audit tidak boleh menggagalkan proses utama.
        error_log('catat_audit gagal: ' . $e->getMessage());
    }
}
