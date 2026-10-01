<?php

require_once __DIR__ . '/fungsi.php';

$user = wajib_login();

$judul = $judul ?? 'Aplikasi SPP';

// Menu dasar untuk semua pengguna.
$nav = [
    'index.php' => 'Dashboard',
    'cek.php' => 'Cek Pembayaran'
];

// Menu khusus admin.
if ($user['level'] === 'admin') {
    $nav = [
        'index.php' => 'Dashboard',
        'master/kelas.php' => 'Data Kelas',
        'master/siswa.php' => 'Data Siswa',
        'master/spp.php' => 'Data SPP',
        'master/petugas.php' => 'Data Petugas',
        'cek.php' => 'Cek Pembayaran'
    ];
}

// Menu pembayaran untuk admin dan petugas.
if (in_array($user['level'], ['admin', 'petugas'])) {
    $nav['pembayaran/tambah.php'] = 'Pembayaran';
    $nav['pembayaran/index.php'] = 'Detail Pembayaran';
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= e($judul) ?> | SPP Sekolah</title>

    <link
        href="<?= e(base_url()) ?>/assets/vendor/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="<?= e(base_url()) ?>/assets/style.css"
        rel="stylesheet"
    >
</head>

<body>
    <div class="app-shell">

        <!-- Sidebar -->
        <aside class="sidebar no-print">
            <h1>SPP Sekolah</h1>

            <p class="small">
                Administrasi pembayaran
            </p>

            <nav aria-label="Menu utama">
                <?php foreach ($nav as $url => $label): ?>
                    <?php
                    $aktif = str_ends_with(
                        $_SERVER['SCRIPT_NAME'],
                        '/' . $url
                    );
                    ?>

                    <a
                        href="<?= e(base_url() . '/' . $url) ?>"
                        class="<?= $aktif ? 'active' : '' ?>"
                    >
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Tombol logout -->
            <form
                method="post"
                action="<?= e(base_url()) ?>/logout.php"
                class="mt-4"
            >
                <?= token() ?>

                <button
                    type="submit"
                    class="btn btn-outline-light btn-sm"
                >
                    Keluar
                </button>
            </form>
        </aside>

        <!-- Konten utama -->
        <main class="main">

            <!-- Header halaman -->
            <header class="page-top">
                <h2><?= e($judul) ?></h2>

                <span>
                    <?= e($user['nama_petugas']) ?>

                    <small>
                        (<?= e($user['level']) ?>)
                    </small>
                </span>
            </header>

            <!-- Pesan setelah proses simpan atau hapus -->
            <?php if (isset($_SESSION['pesan'])): ?>
                <div class="alert alert-success">
                    <?= e($_SESSION['pesan']) ?>
                </div>

                <?php unset($_SESSION['pesan']); ?>
            <?php endif; ?>