<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
require_once 'layout.php';
wajib_role(['ADMIN']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$edit = $id > 0;
$d = ['id_fakultas' => '', 'kode_program_studi' => '', 'nama_program_studi' => '', 'jenjang' => 'S1', 'status_aktif' => 'Aktif'];
$error = '';

if ($edit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $r = db_all($koneksi, "SELECT id_fakultas, kode_program_studi, nama_program_studi, jenjang, status_aktif FROM program_studi WHERE id_program_studi = ?", "i", [$id]);
    if (!$r) { selesai('error', 'Program studi tidak ditemukan.', 'prodi.php'); }
    $d = $r[0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $d['id_fakultas'] = (int)($_POST['id_fakultas'] ?? 0);
    $d['kode_program_studi'] = strtoupper(trim($_POST['kode_program_studi'] ?? ''));
    $d['nama_program_studi'] = trim($_POST['nama_program_studi'] ?? '');
    $d['jenjang'] = $_POST['jenjang'] ?? '';
    $d['status_aktif'] = $_POST['status_aktif'] ?? '';

    if (!$d['id_fakultas'] || $d['kode_program_studi'] === '' || $d['nama_program_studi'] === '') {
        $error = 'Fakultas, kode, dan nama program studi wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $d['kode_program_studi'])) {
        $error = 'Kode hanya boleh huruf kapital, angka, tanda minus, dan underscore.';
    } elseif (!in_array($d['jenjang'], ['D3','D4','S1','S2','S3'], true) || !in_array($d['status_aktif'], ['Aktif','Tidak Aktif'], true)) {
        $error = 'Jenjang atau status tidak valid.';
    } elseif (db_all($koneksi, "SELECT 1 FROM program_studi WHERE id_fakultas = ? AND kode_program_studi = ? AND id_program_studi <> ?", "isi", [$d['id_fakultas'], $d['kode_program_studi'], $id])) {
        $error = 'Kode program studi sudah digunakan pada fakultas ini.';
    }

    if ($error === '') {
        $lama = $edit ? ambil_baris('program_studi', 'id_program_studi', $id) : null;
        $v = [$d['id_fakultas'], $d['kode_program_studi'], $d['nama_program_studi'], $d['jenjang'], $d['status_aktif']];
        if ($edit) {
            $st = mysqli_prepare($koneksi, "UPDATE program_studi SET id_fakultas=?, kode_program_studi=?, nama_program_studi=?, jenjang=?, status_aktif=? WHERE id_program_studi=?");
            $v[] = $id; mysqli_stmt_bind_param($st, "issssi", ...$v);
        } else {
            $st = mysqli_prepare($koneksi, "INSERT INTO program_studi (id_fakultas, kode_program_studi, nama_program_studi, jenjang, status_aktif) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($st, "issss", ...$v);
        }
        if (mysqli_stmt_execute($st)) { $rid = $edit ? $id : mysqli_insert_id($koneksi); catat_audit('program_studi', $rid, $edit ? 'UPDATE' : 'INSERT', $lama, ambil_baris('program_studi', 'id_program_studi', $rid)); selesai('success', $edit ? 'Program studi berhasil diperbarui.' : 'Program studi berhasil ditambahkan.', 'prodi.php'); }
        $error = 'Gagal menyimpan: ' . mysqli_stmt_error($st);
    }
}

$fak = db_all($koneksi, "SELECT id_fakultas, kode_fakultas, nama_fakultas FROM fakultas ORDER BY kode_fakultas");
page_start($edit ? 'Edit Program Studi' : 'Tambah Program Studi', $edit ? 'Edit Program Studi' : 'Tambah Program Studi', 'Isi data program studi.', null, 'prodi.php');
if ($error) { echo '<div class="alert alert-danger">' . e($error) . '</div>'; } ?>
    <div class="dashboard-panel form-panel">
        <form method="post">
            <?= csrf_field() ?><?php if ($edit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
            <div class="form-group"><label>Fakultas *</label>
                <select name="id_fakultas" required><?= options($fak, 'id_fakultas', fn($r) => $r['kode_fakultas'] . ' - ' . $r['nama_fakultas'], $d['id_fakultas'], '-- Pilih Fakultas --') ?></select></div>
            <div class="form-grid">
                <div class="form-group"><label>Kode Program Studi *</label><input type="text" name="kode_program_studi" maxlength="20" required value="<?= e($d['kode_program_studi']) ?>" placeholder="Contoh: TI"></div>
                <div class="form-group"><label>Nama Program Studi *</label><input type="text" name="nama_program_studi" maxlength="200" required value="<?= e($d['nama_program_studi']) ?>"></div>
                <div class="form-group"><label>Jenjang *</label>
                    <select name="jenjang"><?php foreach (['D3','D4','S1','S2','S3'] as $j): ?><option <?= $d['jenjang'] === $j ? 'selected' : '' ?>><?= $j ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Status *</label>
                    <select name="status_aktif"><?php foreach (['Aktif','Tidak Aktif'] as $s): ?><option <?= $d['status_aktif'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="form-actions"><a href="prodi.php" class="btn btn-outline">Batal</a><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
<?php page_end();
