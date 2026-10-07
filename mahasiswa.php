<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
require_once 'layout.php';
wajib_role(['ADMIN', 'OPERATOR_PRODI']);

$operator = $_SESSION['kode_role'] === 'OPERATOR_PRODI';
$prodi_sesi = (int)($_SESSION['id_program_studi'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) { selesai('error', 'ID mahasiswa tidak valid.', 'mahasiswa.php'); }
    $lama = ambil_baris('mahasiswa', 'id_mahasiswa', $id);
    $sql = "DELETE FROM mahasiswa WHERE id_mahasiswa = ?" . ($operator ? " AND id_program_studi = ?" : "");
    $st = mysqli_prepare($koneksi, $sql);
    if ($operator) { mysqli_stmt_bind_param($st, "ii", $id, $prodi_sesi); } else { mysqli_stmt_bind_param($st, "i", $id); }
    mysqli_stmt_execute($st);
    $ok = mysqli_stmt_affected_rows($st) > 0;
    if ($ok) { catat_audit('mahasiswa', $id, 'DELETE', $lama, null); }
    selesai($ok ? 'success' : 'error', $ok ? 'Data mahasiswa berhasil dihapus.' : 'Data mahasiswa tidak ditemukan atau di luar kewenangan Anda.', 'mahasiswa.php');
}

$q = trim($_GET['q'] ?? '');
$w = ['1=1']; $t = ''; $p = [];
if ($operator) { $w[] = 'm.id_program_studi = ?'; $t .= 'i'; $p[] = $prodi_sesi; }
if ($q !== '') { $w[] = '(m.npm LIKE ? OR m.nama_mahasiswa LIKE ?)'; $t .= 'ss'; $p[] = "%$q%"; $p[] = "%$q%"; }
$rows = db_all($koneksi, "SELECT m.id_mahasiswa, m.npm, m.nama_mahasiswa, m.jenis_kelamin, m.tanggal_masuk, m.status_mahasiswa, ps.kode_program_studi
    FROM mahasiswa m JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
    WHERE " . implode(' AND ', $w) . " ORDER BY m.npm", $t, $p);

page_start('Data Mahasiswa', 'Data Mahasiswa', 'Kelola data mahasiswa.', ['mahasiswa_form.php', '+ Tambah Mahasiswa']); ?>
    <form method="get" class="no-print" style="margin-bottom:14px;display:flex;gap:8px;max-width:520px">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari NPM atau nama mahasiswa" style="flex:1;padding:10px 12px;border:1px solid #d5dbe1;border-radius:10px">
        <button class="btn btn-primary" type="submit">Cari</button><a class="btn btn-outline" href="mahasiswa.php">Reset</a>
    </form>
    <div class="dashboard-panel"><div class="table-responsive">
        <table class="data-table">
            <thead><tr><th width="60">No.</th><th>NPM</th><th>Nama Mahasiswa</th><th>Prodi</th><th>JK</th><th>Tgl Masuk</th><th>Status</th><th width="150">Aksi</th></tr></thead>
            <tbody>
            <?php if ($rows): foreach ($rows as $i => $r): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($r['npm']) ?></td>
                    <td><strong><?= e($r['nama_mahasiswa']) ?></strong></td>
                    <td><?= e($r['kode_program_studi']) ?></td>
                    <td><?= e($r['jenis_kelamin']) ?></td>
                    <td><?= e($r['tanggal_masuk']) ?></td>
                    <td><span class="badge"><?= e($r['status_mahasiswa']) ?></span></td>
                    <td><div class="action-group">
                        <a class="btn btn-sm btn-outline" href="mahasiswa_form.php?id=<?= (int)$r['id_mahasiswa'] ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Hapus data mahasiswa ini?');">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id_mahasiswa'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="8" class="empty-state">Belum ada data mahasiswa.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div></div>
<?php page_end();
