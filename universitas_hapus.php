<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: universitas.php"); exit; }
csrf_verify();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { $_SESSION['error'] = 'ID universitas tidak valid.'; header("Location: universitas.php"); exit; }

// Tidak boleh dihapus bila masih dipakai fakultas atau pengguna
$st = mysqli_prepare($koneksi, "SELECT (SELECT COUNT(*) FROM fakultas WHERE id_universitas = ?) + (SELECT COUNT(*) FROM pengguna WHERE id_universitas = ?) AS total");
mysqli_stmt_bind_param($st, "ii", $id, $id);
mysqli_stmt_execute($st);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if ((int)$row['total'] > 0) {
    $_SESSION['error'] = 'Universitas tidak dapat dihapus karena masih memiliki fakultas atau pengguna.';
} else {
    $lama = ambil_baris('universitas', 'id_universitas', $id);
    $st = mysqli_prepare($koneksi, "DELETE FROM universitas WHERE id_universitas = ?");
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $ok = mysqli_stmt_affected_rows($st) > 0;
    if ($ok) { catat_audit('universitas', $id, 'DELETE', $lama, null); }
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Universitas berhasil dihapus.' : 'Gagal menghapus universitas.';
    mysqli_stmt_close($st);
}
header("Location: universitas.php");
exit;
