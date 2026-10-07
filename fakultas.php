<?php
require_once 'auth_check.php';
require_once 'koneksi.php';
wajib_role(['ADMIN']);

$result = mysqli_query($koneksi, "SELECT f.id_fakultas, f.kode_fakultas, f.nama_fakultas, u.kode_universitas
    FROM fakultas f JOIN universitas u ON u.id_universitas = f.id_universitas
    ORDER BY u.kode_universitas, f.kode_fakultas");
if (!$result) { die("Gagal mengambil data: " . htmlspecialchars(mysqli_error($koneksi))); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fakultas - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<?php include 'topbar.php'; ?>
<?php include 'sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow">MASTER DATA</span>
            <h1>Fakultas</h1>
            <p>Kelola data fakultas dalam sistem.</p>
        </div>
        <a href="fakultas_form.php" class="btn btn-primary">+ Tambah Fakultas</a>
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
                    <tr><th width="70">No.</th><th width="130">Universitas</th><th width="130">Kode</th><th>Nama Fakultas</th><th width="150">Aksi</th></tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) > 0): $no = 1; ?>
                    <?php while ($r = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($r['kode_universitas']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($r['kode_fakultas']) ?></span></td>
                        <td><strong><?= htmlspecialchars($r['nama_fakultas']) ?></strong></td>
                        <td>
                            <div class="action-group">
                                <a class="btn btn-sm btn-outline" href="fakultas_form.php?id=<?= (int)$r['id_fakultas'] ?>">Edit</a>
                                <form action="fakultas_hapus.php" method="post" onsubmit="return confirm('Hapus fakultas ini?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$r['id_fakultas'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="empty-state">Belum ada data fakultas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
