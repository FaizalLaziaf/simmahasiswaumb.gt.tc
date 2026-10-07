<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
wajib_role(['ADMIN']);

$result = mysqli_query($koneksi, "SELECT id_universitas, kode_universitas, nama_universitas, slogan, kota, provinsi FROM universitas ORDER BY id_universitas ASC");
if (!$result) { die("Gagal mengambil data: " . htmlspecialchars(mysqli_error($koneksi))); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universitas - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<?php include 'topbar.php'; ?>
<?php include 'sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">MASTER DATA</span>
            <h1>Universitas</h1>
            <p>Kelola data universitas dalam sistem.</p>
        </div>
        <a href="universitas_form.php" class="btn btn-primary">+ Tambah Universitas</a>
    </div>

    <?php foreach (['success' => 'alert-success', 'error' => 'alert-danger'] as $k => $cls): ?>
        <?php if (isset($_SESSION[$k])): ?>
            <div class="alert <?= $cls ?>"><?= htmlspecialchars($_SESSION[$k]) ?></div>
            <?php unset($_SESSION[$k]); ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="dashboard-panel">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="70">No.</th><th width="120">Kode</th><th>Nama Universitas</th>
                        <th>Slogan</th><th>Kota</th><th>Provinsi</th><th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) > 0): $no = 1; ?>
                    <?php while ($r = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><span class="badge"><?= htmlspecialchars($r['kode_universitas']) ?></span></td>
                        <td><strong><?= htmlspecialchars($r['nama_universitas']) ?></strong></td>
                        <td><?= htmlspecialchars($r['slogan'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['kota'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['provinsi'] ?? '-') ?></td>
                        <td>
                            <div class="action-group">
                                <a class="btn btn-sm btn-outline" href="universitas_form.php?id=<?= (int)$r['id_universitas'] ?>">Edit</a>
                                <form action="universitas_hapus.php" method="post" onsubmit="return confirm('Hapus universitas ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$r['id_universitas'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="empty-state">Belum ada data universitas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
