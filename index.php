<?php

require 'includes/fungsi.php';

wajib_login();

$rows = daftar_status();

$lunas = count(
    array_filter(
        $rows,
        fn($siswa) =>
            $siswa['status_pembayaran'] === 'Sudah Lunas'
    )
);

$transaksi = query(
    'SELECT
        COUNT(*) AS n,
        COALESCE(
            SUM(CAST(nominal_bayar AS UNSIGNED)),
            0
        ) AS total
     FROM tb_pembayaran'
)->fetch();

$judul = 'Dashboard';

require 'includes/header.php';

$statistik = [
    'Total siswa' => count($rows),
    'Sudah lunas bulan ini' => $lunas,
    'Belum lunas bulan ini' => count($rows) - $lunas,
    'Jumlah transaksi' => $transaksi['n']
];
?>

<div class="row g-3 mb-4">
    <?php foreach ($statistik as $label => $value): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="panel h-100">
                <div class="text-secondary">
                    <?= e($label) ?>
                </div>

                <div class="stat">
                    <?= e($value) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="panel">
    <h3 class="h5">
        Selamat datang di aplikasi pembayaran SPP
    </h3>

    <p>
        Gunakan menu untuk mengelola data dan mencatat
        pembayaran siswa.
    </p>

    <p>
        Status bulan ini:
        <strong>
            <?= e(bulan_indonesia(date('Y-m-d'))) ?>
        </strong>.
        Siswa dinyatakan lunas apabila pembayaran mencakup
        bulan ini atau bulan berikutnya.
    </p>

    <?php if ($user['level'] !== 'siswa'): ?>
        <p>
            Total nominal transaksi tercatat:
            <strong>
                <?= rupiah($transaksi['total']) ?>
            </strong>
            (tidak termasuk kembalian).
        </p>

        <a
            class="btn btn-primary"
            href="pembayaran/tambah.php"
        >
            Catat pembayaran
        </a>
    <?php endif; ?>

    <a
        class="btn btn-outline-secondary"
        href="cek.php"
    >
        Cek pembayaran
    </a>
</div>

<?php require 'includes/footer.php'; ?>