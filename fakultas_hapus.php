<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header("Location: fakultas.php"); exit; }
csrf_verify();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { $_SESSION['error'] = 'ID fakultas tidak valid.'; header("Location: fakultas.php"); exit; }

$st = mysqli_prepare($koneksi, "SELECT (SELECT COUNT(*) FROM program_studi WHERE id_fakultas = ?) + (SELECT COUNT(*) FROM pengguna WHERE id_fakultas = ?) AS total");
mysqli_stmt_bind_param($st, "ii", $id, $id);
mysqli_stmt_execute($st);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if ((int)$row['total'] > 0) {
    $_SESSION['error'] = 'Fakultas tidak dapat dihapus karena masih memiliki program studi atau pengguna.';
} else {
    $lama = ambil_baris('fakultas', 'id_fakultas', $id);
    $st = mysqli_prepare($koneksi, "DELETE FROM fakultas WHERE id_fakultas = ?");
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $ok = mysqli_stmt_affected_rows($st) > 0;
    if ($ok) { catat_audit('fakultas', $id, 'DELETE', $lama, null); }
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Fakultas berhasil dihapus.' : 'Gagal menghapus fakultas.';
    mysqli_stmt_close($st);
}
header("Location: fakultas.php");
exit;
