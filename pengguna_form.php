<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
require_once 'layout.php';
wajib_role(['ADMIN']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$edit = $id > 0;
$diri = $edit && $id === (int)$_SESSION['id_pengguna'];
$F = ['id_role','id_universitas','id_fakultas','id_program_studi','username','nama_lengkap','email','no_hp','status_aktif'];
$d = array_fill_keys($F, ''); $d['status_aktif'] = 'Aktif';
$error = '';

if ($edit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $r = db_all($koneksi, "SELECT " . implode(',', $F) . " FROM pengguna WHERE id_pengguna = ?", "i", [$id]);
    if (!$r) { selesai('error', 'Pengguna tidak ditemukan.', 'pengguna.php'); }
    $d = array_map(fn($v) => $v ?? '', $r[0]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($F as $f) { $d[$f] = trim($_POST[$f] ?? ''); }
    foreach (['id_role','id_universitas','id_fakultas','id_program_studi'] as $f) { $d[$f] = (int)$d[$f]; }
    $pass = $_POST['password'] ?? '';
    $lama = $edit ? db_all($koneksi, "SELECT id_role FROM pengguna WHERE id_pengguna = ?", "i", [$id])[0]['id_role'] ?? 0 : 0;

    if (!$d['id_role'] || $d['username'] === '' || $d['nama_lengkap'] === '') {
        $error = 'Role, username, dan nama lengkap wajib diisi.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', $d['username'])) {
        $error = 'Username 3-100 karakter: huruf, angka, titik, minus, dan underscore.';
    } elseif (!in_array($d['status_aktif'], ['Aktif','Tidak Aktif'], true)) {
        $error = 'Status tidak valid.';
    } elseif ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (!$edit && strlen($pass) < 8) {
        $error = 'Password wajib diisi minimal 8 karakter.';
    } elseif ($edit && $pass !== '' && strlen($pass) < 8) {
        $error = 'Password baru minimal 8 karakter.';
    } elseif ($diri && ($d['status_aktif'] !== 'Aktif' || $d['id_role'] !== (int)$lama)) {
        $error = 'Anda tidak dapat menonaktifkan atau mengubah role akun yang sedang digunakan.';
    } elseif (db_all($koneksi, "SELECT 1 FROM pengguna WHERE username = ? AND id_pengguna <> ?", "si", [$d['username'], $id])) {
        $error = 'Username sudah digunakan.';
    }

    if ($error === '') {
        $lama = $edit ? ambil_baris('pengguna', 'id_pengguna', $id) : null;
        $base = [$d['id_role'], $d['id_universitas'], $d['id_fakultas'], $d['id_program_studi'], $d['username'], $d['nama_lengkap'], $d['email'], $d['no_hp'], $d['status_aktif']];
        if ($edit) {
            $sql = "UPDATE pengguna SET id_role=?, id_universitas=NULLIF(?,0), id_fakultas=NULLIF(?,0), id_program_studi=NULLIF(?,0), username=?, nama_lengkap=?, email=NULLIF(?,''), no_hp=NULLIF(?,''), status_aktif=?";
            $tp = "iiiisssss"; $v = $base;
            if ($pass !== '') { $sql .= ", password_hash=?"; $tp .= "s"; $v[] = password_hash($pass, PASSWORD_DEFAULT); }
            $sql .= " WHERE id_pengguna=?"; $tp .= "i"; $v[] = $id;
        } else {
            $sql = "INSERT INTO pengguna (id_role, id_universitas, id_fakultas, id_program_studi, username, nama_lengkap, email, no_hp, status_aktif, password_hash) VALUES (?, NULLIF(?,0), NULLIF(?,0), NULLIF(?,0), ?, ?, NULLIF(?,''), NULLIF(?,''), ?, ?)";
            $tp = "iiiissssss"; $v = array_merge($base, [password_hash($pass, PASSWORD_DEFAULT)]);
        }
        $st = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($st, $tp, ...$v);
        if (mysqli_stmt_execute($st)) { $rid = $edit ? $id : mysqli_insert_id($koneksi); catat_audit('pengguna', $rid, $edit ? 'UPDATE' : 'INSERT', $lama, ambil_baris('pengguna', 'id_pengguna', $rid)); selesai('success', $edit ? 'Pengguna berhasil diperbarui.' : 'Pengguna berhasil ditambahkan.', 'pengguna.php'); }
        $error = 'Gagal menyimpan: ' . mysqli_stmt_error($st);
    }
}

$roles = db_all($koneksi, "SELECT id_role, nama_role FROM roles ORDER BY id_role");
$univ  = db_all($koneksi, "SELECT id_universitas, nama_universitas FROM universitas ORDER BY nama_universitas");
$fak   = db_all($koneksi, "SELECT id_fakultas, kode_fakultas, nama_fakultas FROM fakultas ORDER BY kode_fakultas");
$prodi = db_all($koneksi, "SELECT id_program_studi, kode_program_studi, nama_program_studi FROM program_studi ORDER BY kode_program_studi");
$judul = $edit ? 'Edit Pengguna' : 'Tambah Pengguna';
page_start($judul, $judul, 'Isi data akun pengguna.', null, 'pengguna.php');
if ($error) { echo '<div class="alert alert-danger">' . e($error) . '</div>'; } ?>
    <div class="dashboard-panel form-panel">
        <form method="post" autocomplete="off">
            <?= csrf_field() ?><?php if ($edit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="form-group"><label>Username *</label><input type="text" name="username" maxlength="100" required value="<?= e($d['username']) ?>"></div>
                <div class="form-group"><label>Password <?= $edit ? '(kosongkan jika tidak diubah)' : '*' ?></label><input type="password" name="password" minlength="8" autocomplete="new-password" <?= $edit ? '' : 'required' ?>></div>
                <div class="form-group"><label>Nama Lengkap *</label><input type="text" name="nama_lengkap" maxlength="200" required value="<?= e($d['nama_lengkap']) ?>"></div>
                <div class="form-group"><label>Role *</label>
                    <select name="id_role" required><?= options($roles, 'id_role', fn($r) => $r['nama_role'], $d['id_role'], '-- Pilih Role --') ?></select></div>
                <div class="form-group"><label>Email</label><input type="text" name="email" maxlength="150" value="<?= e($d['email']) ?>"></div>
                <div class="form-group"><label>No. HP</label><input type="text" name="no_hp" maxlength="30" value="<?= e($d['no_hp']) ?>"></div>
                <div class="form-group"><label>Universitas</label>
                    <select name="id_universitas"><?= options($univ, 'id_universitas', fn($r) => $r['nama_universitas'], $d['id_universitas'], '-- Tidak ada --') ?></select></div>
                <div class="form-group"><label>Fakultas</label>
                    <select name="id_fakultas"><?= options($fak, 'id_fakultas', fn($r) => $r['kode_fakultas'] . ' - ' . $r['nama_fakultas'], $d['id_fakultas'], '-- Tidak ada --') ?></select></div>
                <div class="form-group"><label>Program Studi</label>
                    <select name="id_program_studi"><?= options($prodi, 'id_program_studi', fn($r) => $r['kode_program_studi'] . ' - ' . $r['nama_program_studi'], $d['id_program_studi'], '-- Tidak ada --') ?></select></div>
                <div class="form-group"><label>Status *</label>
                    <select name="status_aktif"><?php foreach (['Aktif','Tidak Aktif'] as $s): ?><option <?= $d['status_aktif'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
            </div>
            <small class="form-help">Role Dekanat memerlukan Fakultas, Rektorat memerlukan Universitas, dan Operator Prodi memerlukan Program Studi agar cakupan datanya benar.</small>
            <div class="form-actions"><a href="pengguna.php" class="btn btn-outline">Batal</a><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
<?php page_end();
