<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
require_once 'layout.php';
wajib_role(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { selesai('error', 'ID program studi tidak valid.', 'prodi.php'); }
    $n = db_all($koneksi, "SELECT (SELECT COUNT(*) FROM mahasiswa WHERE id_program_studi = ?) + (SELECT COUNT(*) FROM pengguna WHERE id_program_studi = ?) AS t", "ii", [$id, $id])[0]['t'];
    if ((int)$n > 0) { selesai('error', 'Program studi tidak dapat dihapus karena masih memiliki mahasiswa atau pengguna.', 'prodi.php'); }
    $lama = ambil_baris('program_studi', 'id_program_studi', $id);
    $st = mysqli_prepare($koneksi, "DELETE FROM program_studi WHERE id_program_studi = ?");
    mysqli_stmt_bind_param($st, "i", $id);
    mysqli_stmt_execute($st);
    $ok = mysqli_stmt_affected_rows($st) > 0;
    if ($ok) { catat_audit('program_studi', $id, 'DELETE', $lama, null); }
    selesai($ok ? 'success' : 'error', $ok ? 'Program studi berhasil dihapus.' : 'Gagal menghapus program studi.', 'prodi.php');
}

$rows = db_all($koneksi, "SELECT ps.id_program_studi, ps.kode_program_studi, ps.nama_program_studi, ps.jenjang, ps.status_aktif, f.kode_fakultas
    FROM program_studi ps JOIN fakultas f ON f.id_fakultas = ps.id_fakultas ORDER BY f.kode_fakultas, ps.kode_program_studi");

page_start('Program Studi', 'Program Studi', 'Kelola data program studi dalam sistem.', ['prodi_form.php', '+ Tambah Program Studi']); ?>
    <div class="dashboard-panel"><div class="table-responsive">
        <table class="data-table">
            <thead><tr><th width="60">No.</th><th>Fakultas</th><th>Kode</th><th>Nama Program Studi</th><th>Jenjang</th><th>Status</th><th width="150">Aksi</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($r['kode_fakultas']) ?></td>
                    <td><span class="badge"><?= e($r['kode_program_studi']) ?></span></td>
                    <td><strong><?= e($r['nama_program_studi']) ?></strong></td>
                    <td><?= e($r['jenjang']) ?></td>
                    <td><?= e($r['status_aktif']) ?></td>
                    <td><div class="action-group">
                        <a class="btn btn-sm btn-outline" href="prodi_form.php?id=<?= (int)$r['id_program_studi'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus program studi ini?');">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id_program_studi'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="7" class="empty-state">Belum ada data program studi.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div></div>
<?php page_end();
