<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
require_once 'layout.php';
wajib_role(['ADMIN', 'OPERATOR_PRODI']);

$operator = $_SESSION['kode_role'] === 'OPERATOR_PRODI';
$prodi_sesi = (int)($_SESSION['id_program_studi'] ?? 0);
$STATUS = ['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$edit = $id > 0;
$F = ['id_program_studi','npm','nama_mahasiswa','jenis_kelamin','tempat_lahir','tanggal_lahir','tanggal_masuk','alamat','status_mahasiswa'];
$d = array_fill_keys($F, ''); $d['status_mahasiswa'] = 'Aktif'; $d['tanggal_masuk'] = date('Y-m-d');
$error = '';

if ($edit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $r = db_all($koneksi, "SELECT " . implode(',', $F) . " FROM mahasiswa WHERE id_mahasiswa = ?" . ($operator ? " AND id_program_studi = ?" : ""),
        $operator ? "ii" : "i", $operator ? [$id, $prodi_sesi] : [$id]);
    if (!$r) { selesai('error', 'Data mahasiswa tidak ditemukan atau di luar kewenangan Anda.', 'mahasiswa.php'); }
    $d = array_map(fn($v) => $v ?? '', $r[0]);
}

function tgl_valid(string $s): bool { $x = DateTime::createFromFormat('Y-m-d', $s); return $x && $x->format('Y-m-d') === $s; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($F as $f) { $d[$f] = trim($_POST[$f] ?? ''); }
    if ($operator) { $d['id_program_studi'] = $prodi_sesi; }
    $d['id_program_studi'] = (int)$d['id_program_studi'];

    if (!$d['id_program_studi'] || $d['npm'] === '' || $d['nama_mahasiswa'] === '' || $d['tanggal_masuk'] === '') {
        $error = 'Program studi, NPM, nama, dan tanggal masuk wajib diisi.';
    } elseif (!in_array($d['jenis_kelamin'], ['L','P'], true) || !in_array($d['status_mahasiswa'], $STATUS, true)) {
        $error = 'Jenis kelamin atau status tidak valid.';
    } elseif (!tgl_valid($d['tanggal_masuk']) || ($d['tanggal_lahir'] !== '' && !tgl_valid($d['tanggal_lahir']))) {
        $error = 'Format tanggal tidak valid.';
    } elseif (db_all($koneksi, "SELECT 1 FROM mahasiswa WHERE npm = ? AND id_mahasiswa <> ?", "si", [$d['npm'], $id])) {
        $error = 'NPM sudah terdaftar.';
    } elseif ($edit && $operator && !db_all($koneksi, "SELECT 1 FROM mahasiswa WHERE id_mahasiswa = ? AND id_program_studi = ?", "ii", [$id, $prodi_sesi])) {
        $error = 'Data di luar kewenangan Anda.';
    }

    if ($error === '') {
        $lama = $edit ? ambil_baris('mahasiswa', 'id_mahasiswa', $id) : null;
        $v = array_values($d); // urutan sesuai $F
        if ($edit) {
            $st = mysqli_prepare($koneksi, "UPDATE mahasiswa SET id_program_studi=?, npm=?, nama_mahasiswa=?, jenis_kelamin=?, tempat_lahir=NULLIF(?,''), tanggal_lahir=NULLIF(?,''), tanggal_masuk=?, alamat=NULLIF(?,''), status_mahasiswa=? WHERE id_mahasiswa=?");
            $v[] = $id; mysqli_stmt_bind_param($st, "issssssssi", ...$v);
        } else {
            $st = mysqli_prepare($koneksi, "INSERT INTO mahasiswa (id_program_studi, npm, nama_mahasiswa, jenis_kelamin, tempat_lahir, tanggal_lahir, tanggal_masuk, alamat, status_mahasiswa) VALUES (?,?,?,?,NULLIF(?,''),NULLIF(?,''),?,NULLIF(?,''),?)");
            mysqli_stmt_bind_param($st, "issssssss", ...$v);
        }
        if (mysqli_stmt_execute($st)) { $rid = $edit ? $id : mysqli_insert_id($koneksi); catat_audit('mahasiswa', $rid, $edit ? 'UPDATE' : 'INSERT', $lama, ambil_baris('mahasiswa', 'id_mahasiswa', $rid)); selesai('success', $edit ? 'Data mahasiswa berhasil diperbarui.' : 'Data mahasiswa berhasil ditambahkan.', 'mahasiswa.php'); }
        $error = 'Gagal menyimpan: ' . mysqli_stmt_error($st);
    }
}

$prodi = db_all($koneksi, "SELECT id_program_studi, kode_program_studi, nama_program_studi FROM program_studi" . ($operator ? " WHERE id_program_studi = ?" : "") . " ORDER BY kode_program_studi", $operator ? "i" : "", $operator ? [$prodi_sesi] : []);
$judul = $edit ? 'Edit Mahasiswa' : 'Tambah Mahasiswa';
page_start($judul, $judul, 'Isi data mahasiswa.', null, 'mahasiswa.php');
if ($error) { echo '<div class="alert alert-danger">' . e($error) . '</div>'; } ?>
    <div class="dashboard-panel form-panel">
        <form method="post">
            <?= csrf_field() ?><?php if ($edit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group"><label>Program Studi *</label>
                    <select name="id_program_studi" required><?= options($prodi, 'id_program_studi', fn($r) => $r['kode_program_studi'] . ' - ' . $r['nama_program_studi'], $d['id_program_studi'], '-- Pilih Program Studi --') ?></select></div>
                <div class="form-group"><label>NPM *</label><input type="text" name="npm" maxlength="30" required value="<?= e($d['npm']) ?>"></div>
                <div class="form-group"><label>Nama Mahasiswa *</label><input type="text" name="nama_mahasiswa" maxlength="200" required value="<?= e($d['nama_mahasiswa']) ?>"></div>
                <div class="form-group"><label>Jenis Kelamin *</label>
                    <select name="jenis_kelamin" required><option value="">-- Pilih --</option><option value="L" <?= $d['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option><option value="P" <?= $d['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option></select></div>
                <div class="form-group"><label>Tempat Lahir</label><input type="text" name="tempat_lahir" maxlength="100" value="<?= e($d['tempat_lahir']) ?>"></div>
                <div class="form-group"><label>Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= e($d['tanggal_lahir']) ?>"></div>
                <div class="form-group"><label>Tanggal Masuk *</label><input type="date" name="tanggal_masuk" required value="<?= e($d['tanggal_masuk']) ?>"></div>
                <div class="form-group"><label>Status *</label>
                    <select name="status_mahasiswa"><?php foreach ($STATUS as $s): ?><option <?= $d['status_mahasiswa'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-group"><label>Alamat</label><textarea name="alamat" rows="3"><?= e($d['alamat']) ?></textarea></div>
            <div class="form-actions"><a href="mahasiswa.php" class="btn btn-outline">Batal</a><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
<?php page_end();
