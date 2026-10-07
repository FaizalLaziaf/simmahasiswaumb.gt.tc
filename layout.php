<?php
/* layout.php - helper tampilan & query untuk halaman CRUD master */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function db_all(mysqli $k, string $sql, string $types = '', array $p = []): array {
    $st = mysqli_prepare($k, $sql);
    if ($types !== '') { mysqli_stmt_bind_param($st, $types, ...$p); }
    mysqli_stmt_execute($st);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($st), MYSQLI_ASSOC);
    mysqli_stmt_close($st);
    return $rows;
}

function options(array $rows, string $val, callable $label, $selected, string $kosong = ''): string {
    $o = $kosong !== '' ? '<option value="">' . e($kosong) . '</option>' : '';
    foreach ($rows as $r) {
        $s = (string)$selected === (string)$r[$val] ? ' selected' : '';
        $o .= '<option value="' . e($r[$val]) . '"' . $s . '>' . e($label($r)) . '</option>';
    }
    return $o;
}

function page_start(string $title, string $h1, string $sub, ?array $tombol = null, ?string $kembali = null, string $eyebrow = 'MASTER DATA'): void { ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - SIM Mahasiswa</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<?php include 'topbar.php'; include 'sideleftbar.php'; ?>
<main class="main-content">
    <div class="page-heading page-heading-flex">
        <div>
            <span class="eyebrow"><?= e($eyebrow) ?></span>
            <h1><?= e($h1) ?></h1>
            <p><?= e($sub) ?></p>
        </div>
        <?php if ($tombol): ?><a href="<?= e($tombol[0]) ?>" class="btn btn-primary"><?= e($tombol[1]) ?></a><?php endif; ?>
        <?php if ($kembali): ?><a href="<?= e($kembali) ?>" class="btn btn-outline">&larr; Kembali</a><?php endif; ?>
    </div>
    <?php foreach (['success' => 'alert-success', 'error' => 'alert-danger'] as $k => $c): if (isset($_SESSION[$k])): ?>
        <div class="alert <?= $c ?>"><?= e($_SESSION[$k]) ?></div>
    <?php unset($_SESSION[$k]); endif; endforeach;
}

function page_end(): void { echo "</main>\n</body>\n</html>"; }

/* Redirect dengan pesan flash */
function selesai(string $tipe, string $pesan, string $ke): void {
    $_SESSION[$tipe] = $pesan;
    header("Location: $ke");
    exit;
}
