<?php

require_once __DIR__ . '/../includes/fungsi.php';

izin(['admin', 'petugas']);

$error = '';
$id_edit = trim($_GET['id'] ?? '');
$transaksi_edit = null;

// Mengambil transaksi belum lunas yang akan diselesaikan.
if ($id_edit !== '') {
    $transaksi_edit = query(
        'SELECT *
         FROM tb_pembayaran
         WHERE id_pembayaran = ?',
        [$id_edit]
    )->fetch();

    if (!$transaksi_edit) {
        http_response_code(404);
        exit('Transaksi tidak ditemukan.');
    }

    if ($transaksi_edit['status'] !== 'Belum Lunas') {
        exit('Transaksi ini sudah lunas.');
    }
}

// Memproses pembayaran.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_token();

    try {
        $pdo->beginTransaction();

        $status = $_POST['status'] ?? '';

        // Pilihan harus sesuai ENUM pada tabel.
        if (!in_array(
            $status,
            ['Belum Lunas', 'Sudah Lunas'],
            true
        )) {
            throw new InvalidArgumentException(
                'Status pembayaran tidak valid.'
            );
        }

        $nisn = $transaksi_edit
            ? $transaksi_edit['nisn']
            : trim($_POST['nisn'] ?? '');

        // Mengunci siswa selama penyimpanan.
        $siswa = query(
            'SELECT *
             FROM tb_siswa
             WHERE nisn = ?
             FOR UPDATE',
            [$nisn]
        )->fetch();

        if (!$siswa) {
            throw new InvalidArgumentException(
                'Pilih siswa yang terdaftar.'
            );
        }

        $tanggal_bayar = $_POST['tgl_bayar'] ?? '';

        if (
            !tanggal_valid($tanggal_bayar)
            || $tanggal_bayar > date('Y-m-d')
        ) {
            throw new InvalidArgumentException(
                'Tanggal bayar tidak valid atau melebihi hari ini.'
            );
        }

        $bayar = uang($_POST['jumlah_bayar'] ?? '');

        if ($transaksi_edit) {
            // Membaca ulang transaksi setelah siswa dikunci.
            $transaksi = query(
                'SELECT *
                 FROM tb_pembayaran
                 WHERE id_pembayaran = ?
                 FOR UPDATE',
                [$id_edit]
            )->fetch();

            if (
                !$transaksi
                || $transaksi['status'] !== 'Belum Lunas'
            ) {
                throw new InvalidArgumentException(
                    'Transaksi sudah diselesaikan atau tidak tersedia.'
                );
            }

            // Periode dan tagihan menggunakan transaksi lama.
            $jumlah_bulan = (int) $transaksi['jumlah_bulan'];
            $total = uang($transaksi['nominal_bayar']);
            $akhir = $transaksi['tgl_terakhir_bayar'];
            $batas = $transaksi['batas_pembayaran'];
            $id_spp = $transaksi['id_spp'];

            if ($bayar < uang($transaksi['jumlah_bayar'])) {
                throw new InvalidArgumentException(
                    'Total uang diterima tidak boleh lebih kecil dari nilai sebelumnya.'
                );
            }
        } else {
            // Hindari transaksi baru sebelum tagihan lama diselesaikan.
            $belum_lunas = query(
                "SELECT id_pembayaran
                 FROM tb_pembayaran
                 WHERE nisn = ?
                   AND status = 'Belum Lunas'
                 LIMIT 1",
                [$nisn]
            )->fetch();

            if ($belum_lunas) {
                throw new InvalidArgumentException(
                    'Siswa masih mempunyai transaksi belum lunas. '
                    . 'Buka Detail Pembayaran lalu pilih Lunasi.'
                );
            }

            $spp = query(
                'SELECT nominal
                 FROM tb_spp
                 WHERE id_spp = ?',
                [$siswa['id_spp']]
            )->fetch();

            if (!$spp) {
                throw new InvalidArgumentException(
                    'Tarif SPP siswa belum tersedia.'
                );
            }

            $jumlah_bulan = filter_var(
                $_POST['jumlah_bulan'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (
                $jumlah_bulan === false
                || $jumlah_bulan < 1
                || $jumlah_bulan > 12
            ) {
                throw new InvalidArgumentException(
                    'Jumlah bulan harus 1 sampai 12.'
                );
            }

            $nominal = uang($spp['nominal']);

            if ($nominal <= 0) {
                throw new InvalidArgumentException(
                    'Tarif SPP harus lebih dari nol.'
                );
            }

            $total = $nominal * $jumlah_bulan;
            $id_spp = $siswa['id_spp'];

            $terakhir = query(
                "SELECT MAX(tgl_terakhir_bayar)
                 FROM tb_pembayaran
                 WHERE nisn = ?
                   AND status = 'Sudah Lunas'",
                [$nisn]
            )->fetchColumn();

            if ($terakhir) {
                $periode_terakhir = substr(
                    $terakhir,
                    0,
                    7
                ) . '-01';

                $mulai = date(
                    'Y-m-01',
                    strtotime($periode_terakhir . ' +1 month')
                );
            } else {
                $input = $_POST['bulan_mulai'] ?? '';

                if (
                    !preg_match('/^\d{4}-\d{2}$/', $input)
                    || !tanggal_valid($input . '-01')
                ) {
                    throw new InvalidArgumentException(
                        'Bulan mulai tidak valid.'
                    );
                }

                $mulai = $input . '-01';
            }

            $akhir = date(
                'Y-m-01',
                strtotime(
                    $mulai
                    . ' +'
                    . ($jumlah_bulan - 1)
                    . ' month'
                )
            );

            $batas = substr($mulai, 0, 7) . '-10';
        }

        // Memastikan pilihan status sesuai jumlah pembayaran.
        if ($status === 'Sudah Lunas' && $bayar < $total) {
            throw new InvalidArgumentException(
                'Status Sudah Lunas memerlukan uang minimal '
                . rupiah($total)
                . '.'
            );
        }

        if ($status === 'Belum Lunas' && $bayar >= $total) {
            throw new InvalidArgumentException(
                'Uang sudah memenuhi tagihan. Pilih status Sudah Lunas.'
            );
        }

        $kembalian = $status === 'Sudah Lunas'
            ? $bayar - $total
            : 0;

        if ($transaksi_edit) {
            $id = $id_edit;

            query(
                'UPDATE tb_pembayaran
                 SET status = ?,
                     tgl_bayar = ?,
                     jumlah_bayar = ?,
                     kembalian = ?
                 WHERE id_pembayaran = ?',
                [
                    $status,
                    $tanggal_bayar,
                    (string) $bayar,
                    (string) $kembalian,
                    $id
                ]
            );
        } else {
            $id = kode_bayar();

            query(
                'INSERT INTO tb_pembayaran (
                    id_pembayaran,
                    status,
                    nisn,
                    tgl_bayar,
                    tgl_terakhir_bayar,
                    batas_pembayaran,
                    jumlah_bulan,
                    id_spp,
                    nominal_bayar,
                    jumlah_bayar,
                    kembalian
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $id,
                    $status,
                    $nisn,
                    $tanggal_bayar,
                    $akhir,
                    $batas,
                    (string) $jumlah_bulan,
                    $id_spp,
                    (string) $total,
                    (string) $bayar,
                    (string) $kembalian
                ]
            );
        }

        // Status cek diperbarui berdasarkan transaksi lunas.
        sinkron_cek();

        $pdo->commit();

        pesan('Pembayaran berhasil disimpan.');

        pergi(
            'pembayaran/detail.php?id=' . urlencode($id)
        );
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex instanceof PDOException
            ? db_error($ex)
            : $ex->getMessage();
    }
}

// Mengambil siswa beserta tarif dan pembayaran terakhir.
$daftar_siswa = query(
    "SELECT
        s.nisn,
        s.nama,
        s.nama_kelas,
        sp.nominal,
        p.terakhir
     FROM tb_siswa s
     LEFT JOIN tb_spp sp
        ON s.id_spp = sp.id_spp
     LEFT JOIN (
        SELECT
            nisn,
            MAX(tgl_terakhir_bayar) AS terakhir
        FROM tb_pembayaran
        WHERE status = 'Sudah Lunas'
        GROUP BY nisn
     ) p ON p.nisn = s.nisn
     ORDER BY s.nama"
)->fetchAll();

$nisn_pilihan = $transaksi_edit
    ? $transaksi_edit['nisn']
    : ($_POST['nisn'] ?? '');

$jumlah_form = $transaksi_edit
    ? (int) $transaksi_edit['jumlah_bulan']
    : ($_POST['jumlah_bulan'] ?? 1);

$periode_form = $_POST['bulan_mulai'] ?? date('Y-m');

if ($transaksi_edit) {
    $periode_akhir = substr(
        $transaksi_edit['tgl_terakhir_bayar'],
        0,
        7
    ) . '-01';

    $periode_form = date(
        'Y-m',
        strtotime(
            $periode_akhir
            . ' -'
            . ((int) $transaksi_edit['jumlah_bulan'] - 1)
            . ' month'
        )
    );
}

if (
    !preg_match('/^\d{4}-\d{2}$/', $periode_form)
    || !tanggal_valid($periode_form . '-01')
) {
    $periode_form = date('Y-m');
}

$bulan_pilihan = (int) substr($periode_form, 5, 2);
$tahun_pilihan = (int) substr($periode_form, 0, 4);

$tahun_awal = min((int) date('Y') - 5, $tahun_pilihan);
$tahun_akhir = max((int) date('Y') + 10, $tahun_pilihan);

$status_form = $_POST['status']
    ?? ($transaksi_edit ? 'Sudah Lunas' : 'Belum Lunas');

$uang_form = $_POST['jumlah_bayar']
    ?? ($transaksi_edit['jumlah_bayar'] ?? '');

$judul = $transaksi_edit
    ? 'Pelunasan Pembayaran'
    : 'Pembayaran SPP';

require __DIR__ . '/../includes/header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<section class="panel">
    <h3 class="h5 mb-3">
        <?= e($judul) ?>
    </h3>

    <?php if ($transaksi_edit): ?>
        <div class="alert alert-info">
            Transaksi:
            <strong>
                <?= e($transaksi_edit['id_pembayaran']) ?>
            </strong>

            <br>

            Uang sebelumnya:
            <strong>
                <?= rupiah($transaksi_edit['jumlah_bayar']) ?>
            </strong>

            <br>

            Masukkan <strong>total uang yang sudah diterima</strong>,
            termasuk pembayaran sebelumnya.
            Periode dan tagihan mengikuti transaksi awal.
        </div>
    <?php endif; ?>

    <form method="post" class="row g-3">
        <?= token() ?>

        <div class="col-md-6">
            <label for="nisn" class="form-label">
                Siswa
            </label>

            <select
                name="nisn"
                id="nisn"
                class="form-select"
                <?= $transaksi_edit ? 'disabled' : '' ?>
                required
            >
                <option value="">Pilih siswa</option>

                <?php foreach ($daftar_siswa as $siswa): ?>
                    <?php
                    $bulan_berikutnya = '';

                    if ($siswa['terakhir']) {
                        $bulan_berikutnya = date(
                            'Y-m',
                            strtotime(
                                substr($siswa['terakhir'], 0, 7)
                                . '-01 +1 month'
                            )
                        );
                    }

                    $tarif_tampilan = $siswa['nominal'] ?? 0;

                    if (
                        $transaksi_edit
                        && $siswa['nisn'] === $nisn_pilihan
                    ) {
                        $bulan_berikutnya = $periode_form;

                        $tarif_tampilan =
                            (int) $transaksi_edit['nominal_bayar']
                            / max(
                                1,
                                (int) $transaksi_edit['jumlah_bulan']
                            );
                    }
                    ?>

                    <option
                        value="<?= e($siswa['nisn']) ?>"
                        data-nominal="<?= e($tarif_tampilan) ?>"
                        data-mulai="<?= e($bulan_berikutnya) ?>"
                        <?= $nisn_pilihan === $siswa['nisn']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e(
                            $siswa['nisn']
                            . ' - '
                            . $siswa['nama']
                            . ' / '
                            . $siswa['nama_kelas']
                        ) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="tgl_bayar" class="form-label">
                Tanggal bayar
            </label>

            <input
                type="date"
                name="tgl_bayar"
                id="tgl_bayar"
                class="form-control"
                max="<?= date('Y-m-d') ?>"
                value="<?= e(
                    $_POST['tgl_bayar'] ?? date('Y-m-d')
                ) ?>"
                required
            >
        </div>

        <div class="col-md-6">
            <label for="bulan_pilihan" class="form-label">
                Bulan mulai dibayar
            </label>

            <select
                id="bulan_pilihan"
                class="form-select"
                required
            >
                <?php foreach (daftar_bulan() as $nomor => $nama): ?>
                    <option
                        value="<?= sprintf('%02d', $nomor) ?>"
                        <?= $bulan_pilihan === $nomor
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e($nama) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="tahun_pilihan" class="form-label">
                Tahun pembayaran
            </label>

            <select
                id="tahun_pilihan"
                class="form-select"
                required
            >
                <?php
                for (
                    $tahun = $tahun_awal;
                    $tahun <= $tahun_akhir;
                    $tahun++
                ):
                ?>
                    <option
                        value="<?= $tahun ?>"
                        <?= $tahun_pilihan === $tahun
                            ? 'selected'
                            : '' ?>
                    >
                        <?= $tahun ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <input
            type="hidden"
            name="bulan_mulai"
            id="bulan_mulai"
            value="<?= e($periode_form) ?>"
        >

        <div class="col-12">
            <div
                id="info_periode"
                class="alert alert-secondary py-2 mb-0"
            >
                Pilih periode pembayaran.
            </div>
        </div>

        <div class="col-md-6">
            <label for="jumlah_bulan" class="form-label">
                Jumlah bulan
            </label>

            <input
                type="number"
                name="jumlah_bulan"
                id="jumlah_bulan"
                class="form-control"
                min="1"
                max="12"
                step="1"
                value="<?= e($jumlah_form) ?>"
                <?= $transaksi_edit ? 'readonly' : '' ?>
                required
            >
        </div>

        <div class="col-md-6">
            <label for="jumlah_bayar" class="form-label">
                Total uang diterima (Rp)
            </label>

            <input
                type="number"
                name="jumlah_bayar"
                id="jumlah_bayar"
                class="form-control"
                min="0"
                max="9999999999"
                step="1"
                value="<?= e($uang_form) ?>"
                required
            >
        </div>

        <div class="col-md-6">
            <label for="status" class="form-label">
                Status pembayaran
            </label>

            <select
                name="status"
                id="status"
                class="form-select"
                required
            >
                <option
                    value="Belum Lunas"
                    <?= $status_form === 'Belum Lunas'
                        ? 'selected'
                        : '' ?>
                >
                    Belum Lunas
                </option>

                <option
                    value="Sudah Lunas"
                    <?= $status_form === 'Sudah Lunas'
                        ? 'selected'
                        : '' ?>
                >
                    Sudah Lunas
                </option>
            </select>

            <small class="text-secondary">
                Status harus sesuai jumlah uang yang diterima.
            </small>
        </div>

        <div class="col-12">
            <p>
                Tarif per bulan:
                <strong id="tarif">Rp 0</strong>
            </p>

            <p>
                Periode pembayaran:
                <strong id="periode_dibayar">-</strong>
            </p>

            <p>
                Total tagihan:
                <span class="amount" id="total">Rp 0</span>
            </p>

            <p>
                Kembalian:
                <strong id="kembalian">Rp 0</strong>
            </p>

            <button type="submit" class="btn btn-primary">
                Simpan pembayaran
            </button>

            <a href="index.php" class="btn btn-outline-secondary">
                Batal
            </a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>