<?php

require 'includes/fungsi.php';

wajib_login();

$q = trim($_GET['q'] ?? '');
$rows = daftar_status($q);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    izin(['admin', 'petugas']);
    cek_token();

    try {
        $pdo->beginTransaction();

        sinkron_cek();

        $pdo->commit();

        pesan(
            'Tabel cek pembayaran diperbarui dari transaksi.'
        );
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        pesan(
            'Gagal memperbarui tabel cek. Periksa log PHP.'
        );

        error_log($ex->getMessage());
    }

    pergi('cek.php');
}

$judul = 'Cek Pembayaran';

require 'includes/header.php';
?>

<div class="panel">
    <form method="get" class="d-flex gap-2">
        <input
            name="q"
            class="form-control"
            value="<?= e($q) ?>"
            placeholder="Cari berdasarkan nama atau NISN"
            aria-label="Nama atau NISN"
        >

        <button class="btn btn-primary">
            Cek
        </button>

        <a
            href="cek.php"
            class="btn btn-outline-secondary"
        >
            Reset
        </a>
    </form>

    <p class="small mt-3 mb-0">
        Status untuk bulan
        <strong>
            <?= e(bulan_indonesia(date('Y-m-d'))) ?>
        </strong>
        dihitung dari bulan terakhir yang sudah dibayar.
        Siswa tanpa transaksi ditampilkan sebagai belum lunas.
    </p>

    <?php if ($user['level'] !== 'siswa'): ?>
        <form method="post" class="mt-3">
            <?= token() ?>

            <button class="btn btn-outline-secondary btn-sm">
                Perbarui tabel cek pembayaran
            </button>
        </form>
    <?php endif; ?>
</div>

<?php foreach (['Sudah Lunas', 'Belum Lunas'] as $status): ?>
    <?php
    $group = array_filter(
        $rows,
        fn($siswa) =>
            $siswa['status_pembayaran'] === $status
    );
    ?>

    <div class="panel">
        <h3 class="h5">
            <?= e($status) ?>
            (<?= count($group) ?> siswa)
        </h3>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Telepon</th>
                        <th>Lunas sampai</th>
                        <th>Bulan belum dibayar*</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($group as $siswa): ?>
                        <tr>
                            <td>
                                <?= e($siswa['nisn']) ?>
                            </td>

                            <td>
                                <?= e($siswa['nama']) ?>
                            </td>

                            <td>
                                <?= e($siswa['nama_kelas']) ?>
                            </td>

                            <td>
                                <?= e($siswa['no_telp']) ?>
                            </td>

                            <td>
                                <?php if ($siswa['terakhir']): ?>
                                    <?= e(
                                        bulan_indonesia(
                                            $siswa['terakhir']
                                        )
                                    ) ?>
                                <?php else: ?>
                                    Belum ada transaksi
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e($siswa['jumlah_bulan']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$group): ?>
                        <tr>
                            <td colspan="6">
                                Tidak ada data.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
<?php require 'includes/footer.php'; ?>