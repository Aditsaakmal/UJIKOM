<?php

require_once __DIR__ . '/../includes/fungsi.php';

$user = izin(['admin', 'petugas']);

$q = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';

if (!in_array(
    $status_filter,
    ['', 'Belum Lunas', 'Sudah Lunas'],
    true
)) {
    $status_filter = '';
}

$sql = '
    SELECT
        p.*,
        s.nama,
        s.nama_kelas
    FROM tb_pembayaran p
    JOIN tb_siswa s
        ON p.nisn = s.nisn
    WHERE (
        s.nama LIKE ?
        OR p.nisn LIKE ?
        OR p.id_pembayaran LIKE ?
    )
';

$params = array_fill(0, 3, '%' . $q . '%');

if ($status_filter !== '') {
    $sql .= ' AND p.status = ?';
    $params[] = $status_filter;
}

$sql .= '
    ORDER BY
        p.tgl_bayar DESC,
        p.id_pembayaran DESC
';

$rows = query($sql, $params)->fetchAll();

$total_tagihan = 0;
$total_diterima = 0;
$total_kekurangan = 0;

$judul = 'Detail Pembayaran';

require __DIR__ . '/../includes/header.php';
?>

<!-- CSS khusus halaman pembayaran -->
<link
    rel="stylesheet"
    href="<?= e(base_url()) ?>/assets/pembayaran.css?v=3"
>

<section class="panel panel-pembayaran">

    <div class="pembayaran-toolbar no-print">
        <a href="tambah.php" class="btn btn-primary btn-sm">
            Tambah pembayaran
        </a>

        <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            data-print
        >
            Cetak laporan
        </button>
    </div>

    <form
        method="get"
        class="pembayaran-filter no-print"
    >
        <input
            type="search"
            name="q"
            class="form-control"
            value="<?= e($q) ?>"
            placeholder="Cari nama, NISN, atau kode transaksi"
            aria-label="Cari pembayaran"
        >

        <select
            name="status"
            class="form-select"
            aria-label="Status pembayaran"
        >
            <option value="">Semua status</option>

            <option
                value="Belum Lunas"
                <?= $status_filter === 'Belum Lunas'
                    ? 'selected'
                    : '' ?>
            >
                Belum Lunas
            </option>

            <option
                value="Sudah Lunas"
                <?= $status_filter === 'Sudah Lunas'
                    ? 'selected'
                    : '' ?>
            >
                Sudah Lunas
            </option>
        </select>

        <div class="filter-tombol">
            <button type="submit" class="btn btn-primary">
                Cari
            </button>

            <a href="index.php" class="btn btn-outline-secondary">
                Reset
            </a>
        </div>
    </form>

    <h3 class="h5 print-only">
        Laporan pembayaran SPP
    </h3>

    <p class="small text-secondary no-print">
        <?= count($rows) ?> transaksi ditemukan.
    </p>

    <div class="pembayaran-scroll">
        <table class="table pembayaran-table">
            <colgroup>
                <col class="col-kode">
                <col class="col-siswa">
                <col class="col-tanggal">
                <col class="col-periode">
                <col class="col-uang">
                <col class="col-status">
                <col class="col-aksi no-print">
            </colgroup>

            <thead>
                <tr>
                    <th scope="col">Kode</th>
                    <th scope="col">Siswa</th>
                    <th scope="col">Tanggal bayar</th>
                    <th scope="col">Periode sampai</th>
                    <th scope="col">Rincian pembayaran</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="no-print">Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $tagihan = (int) $row['nominal_bayar'];

                    $diterima_bersih =
                        (int) $row['jumlah_bayar']
                        - (int) $row['kembalian'];

                    $kekurangan = max(
                        0,
                        $tagihan - $diterima_bersih
                    );

                    $total_tagihan += $tagihan;
                    $total_diterima += $diterima_bersih;
                    $total_kekurangan += $kekurangan;

                    $lunas = $row['status'] === 'Sudah Lunas';
                    ?>

                    <tr>
                        <td class="pembayaran-kode">
                            <?= e($row['id_pembayaran']) ?>
                        </td>

                        <td>
                            <div class="pembayaran-nama">
                                <?= e($row['nama']) ?>
                            </div>

                            <div class="pembayaran-info">
                                <?= e($row['nisn']) ?>
                            </div>

                            <div class="pembayaran-info">
                                <?= e($row['nama_kelas']) ?>
                            </div>
                        </td>

                        <td class="pembayaran-tanggal">
                            <?= e(
                                tanggal_indonesia(
                                    $row['tgl_bayar']
                                )
                            ) ?>
                        </td>

                        <td>
                            <div class="pembayaran-periode">
                                <?= e(
                                    bulan_indonesia(
                                        $row['tgl_terakhir_bayar']
                                    )
                                ) ?>
                            </div>

                            <div class="pembayaran-info">
                                <?= e($row['jumlah_bulan']) ?> bulan
                            </div>
                        </td>

                        <td>
                            <div class="pembayaran-uang">
                                <span class="label-uang">
                                    Tagihan
                                </span>

                                <span class="nilai-uang">
                                    <?= rupiah($tagihan) ?>
                                </span>

                                <span class="label-uang">
                                    Diterima
                                </span>

                                <span class="nilai-uang">
                                    <?= rupiah($diterima_bersih) ?>
                                </span>

                                <span class="label-uang">
                                    Kurang
                                </span>

                                <span class="nilai-uang <?= 
                                    $kekurangan > 0
                                        ? 'nilai-kurang'
                                        : ''
                                ?>">
                                    <?= rupiah($kekurangan) ?>
                                </span>
                            </div>
                        </td>

                        <td>
                            <span class="pembayaran-status <?= 
                                $lunas ? 'lunas' : 'belum-lunas'
                            ?>">
                                <?= e($row['status']) ?>
                            </span>
                        </td>

                        <td class="no-print">
                            <div class="pembayaran-aksi">
                                <a
                                    class="btn btn-outline-primary btn-sm"
                                    href="detail.php?id=<?= urlencode(
                                        $row['id_pembayaran']
                                    ) ?>"
                                >
                                    Detail
                                </a>

                                <?php if (!$lunas): ?>
                                    <a
                                        class="btn btn-success btn-sm"
                                        href="tambah.php?id=<?= urlencode(
                                            $row['id_pembayaran']
                                        ) ?>"
                                    >
                                        Lunasi
                                    </a>
                                <?php endif; ?>

                                <?php if ($user['level'] === 'admin'): ?>
                                    <form
                                        method="post"
                                        action="hapus.php"
                                        data-confirm="Hapus transaksi ini?"
                                    >
                                        <?= token() ?>

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= e(
                                                $row['id_pembayaran']
                                            ) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger btn-sm"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            Data pembayaran tidak ditemukan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pembayaran-ringkasan">
        <div>
            <span>Total tagihan</span>
            <strong><?= rupiah($total_tagihan) ?></strong>
        </div>

        <div>
            <span>Total diterima bersih</span>
            <strong><?= rupiah($total_diterima) ?></strong>
        </div>

        <div>
            <span>Total kekurangan</span>
            <strong><?= rupiah($total_kekurangan) ?></strong>
        </div>
    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>