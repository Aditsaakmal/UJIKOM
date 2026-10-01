<?php

require '../includes/fungsi.php';

$user = izin(['admin', 'petugas']);

$row = query(
    'SELECT
        p.*,
        s.nama,
        s.nama_kelas
     FROM tb_pembayaran p
     JOIN tb_siswa s
        ON p.nisn = s.nisn
     WHERE p.id_pembayaran = ?',
    [$_GET['id'] ?? '']
)->fetch();

if (!$row) {
    http_response_code(404);
    exit('Transaksi tidak ditemukan.');
}

// Menentukan bulan awal berdasarkan bulan akhir dan jumlah bulan.
$periode_akhir = substr(
    $row['tgl_terakhir_bayar'],
    0,
    7
) . '-01';

$jumlah_bulan = max(
    1,
    (int) $row['jumlah_bulan']
);

$periode_awal = date(
    'Y-m-01',
    strtotime(
        $periode_akhir
        . ' -'
        . ($jumlah_bulan - 1)
        . ' month'
    )
);

$periode_teks = bulan_indonesia($periode_awal);

if ($jumlah_bulan > 1) {
    $periode_teks .= ' sampai '
        . bulan_indonesia($periode_akhir);
}

$judul = 'Bukti Pembayaran';

require '../includes/header.php';

$informasi = [
    'NISN' => $row['nisn'],
    'Nama siswa' => $row['nama'],
    'Kelas' => $row['nama_kelas'],
    'Tanggal bayar' => tanggal_indonesia(
        $row['tgl_bayar']
    ),
    'Periode dibayar' => $periode_teks,
    'Lunas sampai bulan' => bulan_indonesia(
        $row['tgl_terakhir_bayar']
    ),
    'Batas pembayaran' => tanggal_indonesia(
        $row['batas_pembayaran']
    ),
    'Jumlah bulan' => $row['jumlah_bulan'] . ' bulan',
    'Kode SPP' => $row['id_spp'],
    'Status transaksi' => $row['status']
];

$uang = [
    'Total tagihan' => $row['nominal_bayar'],
    'Uang diterima' => $row['jumlah_bayar'],
    'Kembalian' => $row['kembalian']
];
?>

<div class="panel">
    <h3 class="h4">
        Bukti pembayaran SPP
    </h3>

    <p>
        Kode transaksi:
        <strong>
            <?= e($row['id_pembayaran']) ?>
        </strong>
    </p>

    <table class="table">
        <tbody>
            <?php foreach ($informasi as $label => $value): ?>
                <tr>
                    <th style="width: 40%;">
                        <?= e($label) ?>
                    </th>

                    <td>
                        <?= e($value) ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php foreach ($uang as $label => $value): ?>
                <tr>
                    <th>
                        <?= e($label) ?>
                    </th>

                    <td>
                        <?= rupiah($value) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="d-flex gap-2 no-print">
        <button
            class="btn btn-primary"
            data-print
        >
            Cetak bukti
        </button>

        <a
            href="index.php"
            class="btn btn-outline-secondary"
        >
            Kembali
        </a>

        <?php if ($user['level'] === 'admin'): ?>
            <form
                method="post"
                action="hapus.php"
                data-confirm="Hapus transaksi terakhir ini? Status pembayaran akan dihitung ulang."
            >
                <?= token() ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= e($row['id_pembayaran']) ?>"
                >

                <button class="btn btn-danger">
                    Hapus transaksi
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require '../includes/footer.php'; ?>