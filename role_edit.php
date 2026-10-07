<?php
require_once 'auth_check.php';
require_once 'koneksi.php';

wajib_role(['ADMIN']);

$id_role = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_role) {
    $_SESSION['error'] = 'ID role tidak valid.';
    header("Location: role.php");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_role, kode_role, nama_role, keterangan
     FROM roles
     WHERE id_role = ?
     LIMIT 1"
);
mysqli_stmt_bind_param($stmt, "i", $id_role);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$role = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$role) {
    $_SESSION['error'] = 'Data role tidak ditemukan.';
    header("Location: role.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nama_role = trim($_POST['nama_role'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($nama_role === '') {
        $error = 'Nama role wajib diisi.';
    }

    if ($error === '') {
        $lama = ambil_baris('roles', 'id_role', $id_role);
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE roles
             SET nama_role = ?, keterangan = NULLIF(?, '')
             WHERE id_role = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $nama_role,
            $keterangan,
            $id_role
        );

        if (mysqli_stmt_execute($stmt)) {
            catat_audit('roles', $id_role, 'UPDATE', $lama, ambil_baris('roles', 'id_role', $id_role));
            $_SESSION['success'] = 'Role berhasil diperbarui.';
            mysqli_stmt_close($stmt);
            header("Location: role.php");
            exit;
        }

        $error = 'Gagal memperbarui role: ' . mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
    }

    $role['nama_role'] = $nama_role;
    $role['keterangan'] = $keterangan;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Role - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">

<?php include 'topbar.php'; ?>
<?php include 'sideleftbar.php'; ?>

<main class="main-content">
    <div class="page-heading">
        <span class="eyebrow">MASTER DATA</span>
        <h1>Edit Role</h1>
        <p>Perbarui informasi role.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="dashboard-panel form-panel">
        <form method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="kode_role">Kode Role</label>
                <input
                    type="text"
                    id="kode_role"
                    value="<?= htmlspecialchars($role['kode_role']) ?>"
                    readonly
                >
                <small class="form-help">
                    Kode role menjadi identitas sistem dan tidak dapat diubah dari halaman ini.
                </small>
            </div>

            <div class="form-group">
                <label for="nama_role">Nama Role</label>
                <input
                    type="text"
                    id="nama_role"
                    name="nama_role"
                    maxlength="100"
                    required
                    value="<?= htmlspecialchars($role['nama_role']) ?>"
                >
            </div>

            <div class="form-group">
                <label for="keterangan">Keterangan</label>
                <textarea
                    id="keterangan"
                    name="keterangan"
                    rows="4"
                    maxlength="255"
                ><?= htmlspecialchars($role['keterangan'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <a href="role.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
