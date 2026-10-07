<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
require_once 'layout.php';
wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { selesai('error', 'ID pengguna tidak valid.', 'pengguna.php'); }
    if ($id === (int)$_SESSION['id_pengguna']) { selesai('error', 'Anda tidak dapat menghapus akun yang sedang digunakan.', 'pengguna.php'); }
    $lama = ambil_baris('pengguna', 'id_pengguna', $id);
    $st = mysqli_prepare($koneksi, "DELETE FROM pengguna WHERE id_pengguna = ?");
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $ok = mysqli_stmt_affected_rows($st) > 0;
    if ($ok) { catat_audit('pengguna', $id, 'DELETE', $lama, null); }
    selesai($ok ? 'success' : 'error', $ok ? 'Pengguna berhasil dihapus.' : 'Gagal menghapus pengguna.', 'pengguna.php');
}

$rows = db_all($koneksi, "SELECT p.id_pengguna, p.username, p.nama_lengkap, p.email, p.status_aktif, p.last_login, r.nama_role
    FROM pengguna p JOIN roles r ON r.id_role = p.id_role ORDER BY p.id_pengguna");

page_start('Pengguna', 'Pengguna', 'Kelola akun pengguna aplikasi.', ['pengguna_form.php', '+ Tambah Pengguna']); ?>
    <div class="dashboard-panel"><div class="table-responsive">
        <table class="data-table">
            <thead><tr><th width="60">No.</th><th>Username</th><th>Nama Lengkap</th><th>Email</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th width="150">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><span class="badge"><?= e($r['username']) ?></span></td>
                    <td><strong><?= e($r['nama_lengkap']) ?></strong></td>
                    <td><?= e($r['email'] ?? '-') ?></td>
                    <td><?= e($r['nama_role']) ?></td>
                    <td><?= e($r['status_aktif']) ?></td>
                    <td><?= e($r['last_login'] ?? '-') ?></td>
                    <td><div class="action-group">
                        <a class="btn btn-sm btn-outline" href="pengguna_form.php?id=<?= (int)$r['id_pengguna'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus pengguna ini?');">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id_pengguna'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
<?php page_end();
