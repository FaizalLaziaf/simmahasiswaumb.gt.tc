<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
require_once 'layout.php';
wajib_role(['ADMIN', 'OPERATOR_PRODI']);

$admin = $_SESSION['kode_role'] === 'ADMIN';
$per_halaman = 20;

// Filter
$f_aksi = $_GET['aksi'] ?? '';
$f_tabel = $_GET['tabel'] ?? '';
$f_dari = $_GET['dari'] ?? '';
$f_sampai = $_GET['sampai'] ?? '';
$f_cari = trim($_GET['cari'] ?? '');
$AKSI = ['LOGIN','LOGOUT','INSERT','UPDATE','DELETE','VIEW'];
$TABEL = ['pengguna','roles','universitas','fakultas','program_studi','mahasiswa'];
if (!in_array($f_aksi, $AKSI, true)) { $f_aksi = ''; }
if (!in_array($f_tabel, $TABEL, true)) { $f_tabel = ''; }
$tgl_ok = fn($s) => ($x = DateTime::createFromFormat('Y-m-d', $s)) && $x->format('Y-m-d') === $s;
if (!$tgl_ok($f_dari)) { $f_dari = ''; }
if (!$tgl_ok($f_sampai)) { $f_sampai = ''; }

$w = ['1=1']; $t = ''; $p = [];
if (!$admin) { $w[] = 'a.id_pengguna = ?'; $t .= 'i'; $p[] = (int)$_SESSION['id_pengguna']; } // operator hanya melihat aktivitasnya sendiri
if ($f_aksi !== '')  { $w[] = 'a.aksi = ?';       $t .= 's'; $p[] = $f_aksi; }
if ($f_tabel !== '') { $w[] = 'a.tabel_nama = ?'; $t .= 's'; $p[] = $f_tabel; }
if ($f_dari !== '')   { $w[] = 'a.waktu >= ?'; $t .= 's'; $p[] = $f_dari . ' 00:00:00'; }
if ($f_sampai !== '') { $w[] = 'a.waktu <= ?'; $t .= 's'; $p[] = $f_sampai . ' 23:59:59'; }
if ($f_cari !== '')   { $w[] = '(u.username LIKE ? OR u.nama_lengkap LIKE ? OR a.record_id LIKE ?)'; $t .= 'sss'; array_push($p, "%$f_cari%", "%$f_cari%", "%$f_cari%"); }
$where = implode(' AND ', $w);

$total = (int)db_all($koneksi, "SELECT COUNT(*) AS n FROM audit_log a LEFT JOIN pengguna u ON u.id_pengguna = a.id_pengguna WHERE $where", $t, $p)[0]['n'];
$halaman_total = max(1, (int)ceil($total / $per_halaman));
$halaman = min(max(1, (int)($_GET['hal'] ?? 1)), $halaman_total);
$offset = ($halaman - 1) * $per_halaman;

$rows = db_all($koneksi, "SELECT a.id_audit, a.tabel_nama, a.record_id, a.aksi, a.data_lama, a.data_baru, a.ip_address, a.waktu, u.username, u.nama_lengkap
    FROM audit_log a LEFT JOIN pengguna u ON u.id_pengguna = a.id_pengguna
    WHERE $where ORDER BY a.waktu DESC, a.id_audit DESC LIMIT $per_halaman OFFSET $offset", $t, $p);

function qs(array $tambah = []): string {
    $q = array_filter(array_merge($_GET, $tambah), fn($v) => $v !== '' && $v !== null);
    return http_build_query($q);
}
function json_cantik(?string $j): string {
    if ($j === null || $j === '') { return '-'; }
    $x = json_decode($j, true);
    return $x === null ? $j : json_encode($x, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
function warna_aksi(string $a): string {
    return ['INSERT' => '#166534', 'UPDATE' => '#92400e', 'DELETE' => '#b91c1c', 'LOGIN' => '#1d4ed8', 'LOGOUT' => '#475569'][$a] ?? '#475569';
}

page_start('Audit Log', 'Audit Log', $admin ? 'Riwayat aktivitas seluruh pengguna pada sistem.' : 'Riwayat aktivitas akun Anda pada sistem.', null, null, 'SISTEM'); ?>
    <div class="dashboard-panel form-panel">
        <h3>Filter</h3>
        <form method="get" style="margin-top:12px">
            <div class="form-grid filter-grid">
                <div class="form-group"><label>Aksi</label>
                    <select name="aksi"><option value="">-- Semua --</option><?php foreach ($AKSI as $a): ?><option <?= $f_aksi === $a ? 'selected' : '' ?>><?= $a ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Tabel</label>
                    <select name="tabel"><option value="">-- Semua --</option><?php foreach ($TABEL as $x): ?><option <?= $f_tabel === $x ? 'selected' : '' ?>><?= $x ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Dari Tanggal</label><input type="date" name="dari" value="<?= e($f_dari) ?>"></div>
                <div class="form-group"><label>Sampai Tanggal</label><input type="date" name="sampai" value="<?= e($f_sampai) ?>"></div>
            </div>
            <div class="form-group" style="max-width:420px"><label>Cari pengguna / ID record</label><input type="text" name="cari" value="<?= e($f_cari) ?>" placeholder="Username, nama, atau ID record"></div>
            <div class="action-group"><button type="submit" class="btn btn-primary">Terapkan</button><a href="audit_log.php" class="btn btn-outline">Reset</a></div>
        </form>
    </div>

    <div class="dashboard-panel" style="margin-top:18px">
        <div class="panel-head"><h3>Riwayat Aktivitas</h3><small class="form-help">Total <?= $total ?> catatan &middot; halaman <?= $halaman ?> dari <?= $halaman_total ?></small></div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Tabel</th><th>ID Record</th><th>IP</th><th>Detail</th></tr></thead>
                <tbody>
                <?php if ($rows): foreach ($rows as $r): ?>
                    <tr>
                        <td style="white-space:nowrap"><?= e($r['waktu']) ?></td>
                        <td><strong><?= e($r['username'] ?? '(dihapus)') ?></strong><br><small><?= e($r['nama_lengkap'] ?? '') ?></small></td>
                        <td><span class="badge" style="color:<?= warna_aksi($r['aksi']) ?>"><?= e($r['aksi']) ?></span></td>
                        <td><?= e($r['tabel_nama']) ?></td>
                        <td><?= e($r['record_id'] ?? '-') ?></td>
                        <td><?= e($r['ip_address'] ?? '-') ?></td>
                        <td>
                            <?php if ($r['data_lama'] !== null || $r['data_baru'] !== null): ?>
                                <details>
                                    <summary style="cursor:pointer">Lihat</summary>
                                    <?php if ($r['data_lama'] !== null): ?><small>Sebelum</small><pre style="white-space:pre-wrap;font-size:12px;margin:4px 0 8px"><?= e(json_cantik($r['data_lama'])) ?></pre><?php endif; ?>
                                    <?php if ($r['data_baru'] !== null): ?><small>Sesudah</small><pre style="white-space:pre-wrap;font-size:12px;margin:4px 0"><?= e(json_cantik($r['data_baru'])) ?></pre><?php endif; ?>
                                </details>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="empty-state">Belum ada catatan aktivitas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($halaman_total > 1): ?>
        <div class="action-group" style="padding:14px 20px">
            <?php if ($halaman > 1): ?><a class="btn btn-sm btn-outline" href="?<?= e(qs(['hal' => $halaman - 1])) ?>">&larr; Sebelumnya</a><?php endif; ?>
            <?php if ($halaman < $halaman_total): ?><a class="btn btn-sm btn-outline" href="?<?= e(qs(['hal' => $halaman + 1])) ?>">Berikutnya &rarr;</a><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<?php page_end();
