<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
wajib_role(['ADMIN', 'DEKANAT', 'REKTORAT']);

$role = $_SESSION['kode_role'];

// Ruang lingkup akses berdasarkan role
$scope = ['1=1', '', []];
$label_scope = 'Seluruh Data Mahasiswa';
if ($role === 'REKTORAT') {
    $scope = ['f.id_universitas = ?', 'i', [(int)$_SESSION['id_universitas']]];
    $label_scope = 'Tingkat Universitas';
} elseif ($role === 'DEKANAT') {
    $scope = ['ps.id_fakultas = ?', 'i', [(int)$_SESSION['id_fakultas']]];
    $label_scope = 'Tingkat Fakultas';
}

// Filter dari GET
$f_fak = (int)($_GET['fakultas'] ?? 0);
$f_prodi = (int)($_GET['prodi'] ?? 0);
$f_status = $_GET['status'] ?? '';
$f_jk = $_GET['jk'] ?? '';
$f_cari = trim($_GET['cari'] ?? '');

$where = [$scope[0]]; $types = $scope[1]; $params = $scope[2];
if ($f_fak)   { $where[] = 'ps.id_fakultas = ?';        $types .= 'i'; $params[] = $f_fak; }
if ($f_prodi) { $where[] = 'm.id_program_studi = ?';    $types .= 'i'; $params[] = $f_prodi; }
if (in_array($f_status, ['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'], true)) {
    $where[] = 'm.status_mahasiswa = ?'; $types .= 's'; $params[] = $f_status;
} else { $f_status = ''; }
if (in_array($f_jk, ['L','P'], true)) { $where[] = 'm.jenis_kelamin = ?'; $types .= 's'; $params[] = $f_jk; } else { $f_jk = ''; }
if ($f_cari !== '') {
    $where[] = '(m.npm LIKE ? OR m.nama_mahasiswa LIKE ?)'; $types .= 'ss';
    $params[] = "%$f_cari%"; $params[] = "%$f_cari%";
}

$sql = "SELECT m.npm, m.nama_mahasiswa, m.jenis_kelamin, m.tanggal_masuk, m.status_mahasiswa,
               ps.kode_program_studi, ps.nama_program_studi, f.kode_fakultas, f.nama_fakultas
        FROM mahasiswa m
        JOIN program_studi ps ON ps.id_program_studi = m.id_program_studi
        JOIN fakultas f ON f.id_fakultas = ps.id_fakultas
        WHERE " . implode(' AND ', $where) . " ORDER BY m.npm ASC";
$st = mysqli_prepare($koneksi, $sql);
if ($types !== '') { mysqli_stmt_bind_param($st, $types, ...$params); }
mysqli_stmt_execute($st);
$rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
mysqli_stmt_close($st);

$total = count($rows);
$lk = count(array_filter($rows, fn($r) => $r['jenis_kelamin'] === 'L'));
$pr = $total - $lk;
$aktif = count(array_filter($rows, fn($r) => $r['status_mahasiswa'] === 'Aktif'));

// Opsi dropdown (dibatasi sesuai scope)
$opt = function ($sql_opt) use ($koneksi, $scope) {
    $st = mysqli_prepare($koneksi, str_replace('{SCOPE}', $scope[0], $sql_opt));
    if ($scope[1] !== '') { mysqli_stmt_bind_param($st, $scope[1], ...$scope[2]); }
    mysqli_stmt_execute($st);
    return mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
};
$list_fak = $opt("SELECT DISTINCT f.id_fakultas AS id, f.kode_fakultas, f.nama_fakultas FROM fakultas f JOIN program_studi ps ON ps.id_fakultas = f.id_fakultas WHERE {SCOPE} ORDER BY f.kode_fakultas");
$list_prodi = $opt("SELECT ps.id_program_studi AS id, ps.id_fakultas, ps.kode_program_studi, ps.nama_program_studi FROM program_studi ps JOIN fakultas f ON f.id_fakultas = ps.id_fakultas WHERE {SCOPE} ORDER BY ps.kode_program_studi");
$h = fn($v) => htmlspecialchars((string)$v);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Mahasiswa - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<?php include 'topbar.php'; ?>
<?php include 'sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading">
        <span class="eyebrow">LAPORAN AKADEMIK</span>
        <h1>Rekap Laporan Mahasiswa</h1>
        <p>Rekap data mahasiswa berdasarkan ruang lingkup akses pengguna.</p>
    </div>

    <div class="dashboard-panel form-panel no-print">
        <h3>Filter Laporan</h3>
        <small class="form-help">Scope akses: <span class="badge"><?= $h($label_scope) ?></span></small>
        <form method="get" style="margin-top:14px">
            <div class="form-grid filter-grid">
                <div class="form-group">
                    <label for="fakultas">Fakultas</label>
                    <select id="fakultas" name="fakultas">
                        <option value="0">-- Semua Fakultas --</option>
                        <?php foreach ($list_fak as $o): ?>
                            <option value="<?= (int)$o['id'] ?>" <?= $f_fak === (int)$o['id'] ? 'selected' : '' ?>><?= $h($o['kode_fakultas'] . ' - ' . $o['nama_fakultas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="prodi">Program Studi</label>
                    <select id="prodi" name="prodi">
                        <option value="0">-- Semua Program Studi --</option>
                        <?php foreach ($list_prodi as $o): ?>
                            <option value="<?= (int)$o['id'] ?>" data-fak="<?= (int)$o['id_fakultas'] ?>" <?= $f_prodi === (int)$o['id'] ? 'selected' : '' ?>><?= $h($o['kode_program_studi'] . ' - ' . $o['nama_program_studi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Status Mahasiswa</label>
                    <select id="status" name="status">
                        <option value="">-- Semua Status --</option>
                        <?php foreach (['Aktif','Cuti','Lulus','Mengundurkan Diri','Drop Out','Tidak Aktif'] as $s): ?>
                            <option <?= $f_status === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="jk">Jenis Kelamin</label>
                    <select id="jk" name="jk">
                        <option value="">-- Semua --</option>
                        <option value="L" <?= $f_jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                        <option value="P" <?= $f_jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="max-width:420px">
                <label for="cari">Cari NPM / Nama</label>
                <input type="text" id="cari" name="cari" value="<?= $h($f_cari) ?>" placeholder="Ketik NPM atau nama mahasiswa">
            </div>
            <div class="action-group">
                <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
                <a href="laporan.php" class="btn btn-outline">Reset</a>
                <button type="button" class="btn btn-outline" onclick="window.print()">Cetak</button>
            </div>
        </form>
    </div>

    <div class="stat-row">
        <div class="stat-box"><span>Total Mahasiswa</span><strong><?= $total ?></strong></div>
        <div class="stat-box"><span>Laki-laki</span><strong><?= $lk ?></strong></div>
        <div class="stat-box"><span>Perempuan</span><strong><?= $pr ?></strong></div>
        <div class="stat-box"><span>Mahasiswa Aktif</span><strong><?= $aktif ?></strong></div>
    </div>

    <div class="dashboard-panel">
        <div class="panel-head">
            <h3>Daftar Mahasiswa</h3>
            <small class="form-help"><?= $h($label_scope) ?> &middot; <?= date('d-m-Y H:i') ?></small>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th width="50">No</th><th>NPM</th><th>Nama Mahasiswa</th><th>Program Studi</th><th>Fakultas</th><th width="40">JK</th><th>Tanggal Masuk</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if ($total > 0): foreach ($rows as $i => $r): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= $h($r['npm']) ?></td>
                        <td><?= $h($r['nama_mahasiswa']) ?></td>
                        <td><?= $h($r['kode_program_studi'] . ' - ' . $r['nama_program_studi']) ?></td>
                        <td><?= $h($r['kode_fakultas'] . ' - ' . $r['nama_fakultas']) ?></td>
                        <td><?= $h($r['jenis_kelamin']) ?></td>
                        <td><?= $h($r['tanggal_masuk']) ?></td>
                        <td><span class="badge"><?= $h($r['status_mahasiswa']) ?></span></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="8" class="empty-state">Tidak ada data mahasiswa yang sesuai filter.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <small class="form-help">Menampilkan <?= $total ?> data mahasiswa.</small>
    </div>
</main>
<script>
// Batasi pilihan program studi sesuai fakultas yang dipilih
const fak = document.getElementById('fakultas'), prodi = document.getElementById('prodi');
function filterProdi() {
    Array.from(prodi.options).forEach(o => {
        const ok = !o.dataset.fak || fak.value === '0' || o.dataset.fak === fak.value;
        o.hidden = !ok; o.disabled = !ok;
    });
    if (prodi.selectedOptions[0] && prodi.selectedOptions[0].disabled) prodi.value = '0';
}
fak.addEventListener('change', filterProdi); filterProdi();
</script>
</body>
</html>
