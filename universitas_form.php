<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
wajib_role(['ADMIN']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$edit = $id > 0;
$fields = ['kode_universitas','nama_universitas','slogan','alamat','kota','provinsi','kode_pos','telepon','website','email'];
$d = array_fill_keys($fields, '');
$error = '';

if ($edit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $st = mysqli_prepare($koneksi, "SELECT " . implode(',', $fields) . " FROM universitas WHERE id_universitas = ?");
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    if (!$row) { $_SESSION['error'] = 'Data universitas tidak ditemukan.'; header("Location: universitas.php"); exit; }
    $d = array_map(fn($v) => $v ?? '', $row);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($fields as $f) { $d[$f] = trim($_POST[$f] ?? ''); }
    $d['kode_universitas'] = strtoupper($d['kode_universitas']);

    if ($d['kode_universitas'] === '' || $d['nama_universitas'] === '') {
        $error = 'Kode dan nama universitas wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $d['kode_universitas'])) {
        $error = 'Kode hanya boleh huruf kapital, angka, tanda minus, dan underscore.';
    } elseif ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif ($d['website'] !== '' && !filter_var($d['website'], FILTER_VALIDATE_URL)) {
        $error = 'Format website tidak valid (contoh: https://umb.ac.id).';
    } else {
        $st = mysqli_prepare($koneksi, "SELECT id_universitas FROM universitas WHERE kode_universitas = ? AND id_universitas <> ? LIMIT 1");
        mysqli_stmt_bind_param($st, "si", $d['kode_universitas'], $id);
        mysqli_stmt_execute($st);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($st))) { $error = 'Kode universitas sudah digunakan.'; }
        mysqli_stmt_close($st);
    }

    if ($error === '') {
        $lama = $edit ? ambil_baris('universitas', 'id_universitas', $id) : null;
        $v = array_values($d);
        if ($edit) {
            $sql = "UPDATE universitas SET kode_universitas=?, nama_universitas=?, slogan=NULLIF(?,''), alamat=NULLIF(?,''), kota=NULLIF(?,''), provinsi=NULLIF(?,''), kode_pos=NULLIF(?,''), telepon=NULLIF(?,''), website=NULLIF(?,''), email=NULLIF(?,'') WHERE id_universitas=?";
            $st = mysqli_prepare($koneksi, $sql);
            $v[] = $id;
            mysqli_stmt_bind_param($st, "ssssssssssi", ...$v);
        } else {
            $sql = "INSERT INTO universitas (kode_universitas, nama_universitas, slogan, alamat, kota, provinsi, kode_pos, telepon, website, email) VALUES (?, ?, NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NULLIF(?,''), NULLIF(?,''))";
            $st = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($st, "ssssssssss", ...$v);
        }
        if (mysqli_stmt_execute($st)) {
            $rid = $edit ? $id : mysqli_insert_id($koneksi); catat_audit('universitas', $rid, $edit ? 'UPDATE' : 'INSERT', $lama, ambil_baris('universitas', 'id_universitas', $rid)); 
            $_SESSION['success'] = $edit ? 'Universitas berhasil diperbarui.' : 'Universitas berhasil ditambahkan.';
            mysqli_stmt_close($st);
            header("Location: universitas.php");
            exit;
        }
        $error = 'Gagal menyimpan: ' . mysqli_stmt_error($st);
        mysqli_stmt_close($st);
    }
}

function h($k) { global $d; return htmlspecialchars($d[$k]); }
$judul = $edit ? 'Edit Universitas' : 'Tambah Universitas';
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
    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">MASTER DATA</span>
            <h1><?= $judul ?></h1>
            <p><?= $edit ? 'Perbarui data universitas.' : 'Tambahkan data universitas ke dalam sistem.' ?></p>
        </div>
        <a href="universitas.php" class="btn btn-outline">&larr; Kembali</a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="dashboard-panel form-panel">
        <form method="post">
            <?= csrf_field() ?>
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
            <h3>Informasi Universitas</h3>
            <small class="form-help">Field bertanda * wajib diisi.</small>

            <div class="form-grid">
                <div class="form-group">
                    <label for="kode_universitas">Kode Universitas *</label>
                    <input type="text" id="kode_universitas" name="kode_universitas" maxlength="20" required value="<?= h('kode_universitas') ?>" placeholder="Contoh: UMB">
                    <small class="form-help">Maksimal 20 karakter.</small>
                </div>
                <div class="form-group">
                    <label for="nama_universitas">Nama Universitas *</label>
                    <input type="text" id="nama_universitas" name="nama_universitas" maxlength="200" required value="<?= h('nama_universitas') ?>" placeholder="Nama lengkap universitas">
                </div>
            </div>
            <div class="form-group">
                <label for="slogan">Slogan</label>
                <input type="text" id="slogan" name="slogan" maxlength="255" value="<?= h('slogan') ?>">
            </div>
            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3"><?= h('alamat') ?></textarea>
            </div>
            <div class="form-grid">
                <div class="form-group"><label for="kota">Kota</label><input type="text" id="kota" name="kota" maxlength="100" value="<?= h('kota') ?>"></div>
                <div class="form-group"><label for="provinsi">Provinsi</label><input type="text" id="provinsi" name="provinsi" maxlength="100" value="<?= h('provinsi') ?>"></div>
                <div class="form-group"><label for="kode_pos">Kode Pos</label><input type="text" id="kode_pos" name="kode_pos" maxlength="10" value="<?= h('kode_pos') ?>"></div>
                <div class="form-group"><label for="telepon">Telepon</label><input type="text" id="telepon" name="telepon" maxlength="50" value="<?= h('telepon') ?>"></div>
                <div class="form-group"><label for="website">Website</label><input type="text" id="website" name="website" maxlength="255" value="<?= h('website') ?>" placeholder="https://..."></div>
                <div class="form-group"><label for="email">Email</label><input type="text" id="email" name="email" maxlength="150" value="<?= h('email') ?>"></div>
            </div>

            <div class="form-actions">
                <a href="universitas.php" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary"><?= $edit ? 'Simpan Perubahan' : 'Simpan Universitas' ?></button>
            </div>
        </form>
    </div>
</main>
</body>
</html>
