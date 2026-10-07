<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
wajib_role(['ADMIN']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$edit = $id > 0;
$d = ['id_universitas' => '', 'kode_fakultas' => '', 'nama_fakultas' => ''];
$error = '';

if ($edit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $st = mysqli_prepare($koneksi, "SELECT id_universitas, kode_fakultas, nama_fakultas FROM fakultas WHERE id_fakultas = ?");
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    if (!$row) { $_SESSION['error'] = 'Data fakultas tidak ditemukan.'; header("Location: fakultas.php"); exit; }
    $d = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $d['id_universitas'] = (int)($_POST['id_universitas'] ?? 0);
    $d['kode_fakultas']  = strtoupper(trim($_POST['kode_fakultas'] ?? ''));
    $d['nama_fakultas']  = trim($_POST['nama_fakultas'] ?? '');

    if (!$d['id_universitas'] || $d['kode_fakultas'] === '' || $d['nama_fakultas'] === '') {
        $error = 'Universitas, kode fakultas, dan nama fakultas wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $d['kode_fakultas'])) {
        $error = 'Kode hanya boleh huruf kapital, angka, tanda minus, dan underscore.';
    } else {
        $st = mysqli_prepare($koneksi, "SELECT id_fakultas FROM fakultas WHERE id_universitas = ? AND kode_fakultas = ? AND id_fakultas <> ? LIMIT 1");
        mysqli_stmt_bind_param($st, "isi", $d['id_universitas'], $d['kode_fakultas'], $id);
        mysqli_stmt_execute($st);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($st))) { $error = 'Kode fakultas sudah digunakan pada universitas ini.'; }
        mysqli_stmt_close($st);
    }

    if ($error === '') {
        $lama = $edit ? ambil_baris('fakultas', 'id_fakultas', $id) : null;
        if ($edit) {
            $st = mysqli_prepare($koneksi, "UPDATE fakultas SET id_universitas=?, kode_fakultas=?, nama_fakultas=? WHERE id_fakultas=?");
            mysqli_stmt_bind_param($st, "issi", $d['id_universitas'], $d['kode_fakultas'], $d['nama_fakultas'], $id);
        } else {
            $st = mysqli_prepare($koneksi, "INSERT INTO fakultas (id_universitas, kode_fakultas, nama_fakultas) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($st, "iss", $d['id_universitas'], $d['kode_fakultas'], $d['nama_fakultas']);
        }
        if (mysqli_stmt_execute($st)) {
            $rid = $edit ? $id : mysqli_insert_id($koneksi); catat_audit('fakultas', $rid, $edit ? 'UPDATE' : 'INSERT', $lama, ambil_baris('fakultas', 'id_fakultas', $rid)); 
            $_SESSION['success'] = $edit ? 'Fakultas berhasil diperbarui.' : 'Fakultas berhasil ditambahkan.';
            mysqli_stmt_close($st);
            header("Location: fakultas.php");
            exit;
        }
        $error = 'Gagal menyimpan: ' . mysqli_stmt_error($st);
        mysqli_stmt_close($st);
    }
}

$univ = mysqli_query($koneksi, "SELECT id_universitas, kode_universitas, nama_universitas FROM universitas ORDER BY nama_universitas");
$judul = $edit ? 'Edit Fakultas' : 'Tambah Fakultas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $judul ?> - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<?php include 'topbar.php'; ?>
<?php include 'sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading">
        <span class="eyebrow">MASTER DATA</span>
        <h1><?= $judul ?></h1>
        <p><?= $edit ? 'Perbarui data fakultas.' : 'Tambahkan fakultas ke dalam sistem.' ?></p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="dashboard-panel form-panel">
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
            <h3>Form Fakultas</h3>
            <small class="form-help">Perubahan kode harus tetap unik dalam universitas.</small>

            <div class="form-group">
                <label for="id_universitas">Universitas *</label>
                <select id="id_universitas" name="id_universitas" required>
                    <option value="">-- Pilih Universitas --</option>
                    <?php while ($u = mysqli_fetch_assoc($univ)): ?>
                        <option value="<?= (int)$u['id_universitas'] ?>" <?= (int)$d['id_universitas'] === (int)$u['id_universitas'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['kode_universitas'] . ' - ' . $u['nama_universitas']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="kode_fakultas">Kode Fakultas *</label>
                <input type="text" id="kode_fakultas" name="kode_fakultas" maxlength="20" required value="<?= htmlspecialchars($d['kode_fakultas']) ?>" placeholder="Contoh: FT, FEB, FIKES">
            </div>
            <div class="form-group">
                <label for="nama_fakultas">Nama Fakultas *</label>
                <input type="text" id="nama_fakultas" name="nama_fakultas" maxlength="200" required value="<?= htmlspecialchars($d['nama_fakultas']) ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="fakultas.php" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</main>
</body>
</html>
