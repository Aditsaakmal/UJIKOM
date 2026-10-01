<?php

require '../includes/fungsi.php';

// Penghapusan transaksi hanya dapat dilakukan admin.
izin(['admin']);

// Penghapusan wajib melalui formulir POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    exit(
        'Gunakan tombol Hapus pada tabel atau detail pembayaran.'
    );
}

cek_token();

try {
    $pdo->beginTransaction();

    $id = trim($_POST['id'] ?? '');

    if ($id === '') {
        throw new InvalidArgumentException(
            'Kode transaksi tidak valid.'
        );
    }

    // Mengambil transaksi yang akan dihapus.
    $transaksi = query(
        'SELECT *
         FROM tb_pembayaran
         WHERE id_pembayaran = ?',
        [$id]
    )->fetch();

    if (!$transaksi) {
        throw new InvalidArgumentException(
            'Transaksi tidak ditemukan.'
        );
    }

    // Mengunci data siswa selama proses penghapusan.
    query(
        'SELECT nisn
         FROM tb_siswa
         WHERE nisn = ?
         FOR UPDATE',
        [$transaksi['nisn']]
    );

    // Memastikan transaksi yang dihapus adalah periode terakhir.
    $terakhir = query(
        'SELECT id_pembayaran
         FROM tb_pembayaran
         WHERE nisn = ?
         ORDER BY
            tgl_terakhir_bayar DESC,
            id_pembayaran DESC
         LIMIT 1',
        [$transaksi['nisn']]
    )->fetchColumn();

    if ($terakhir !== $id) {
        throw new InvalidArgumentException(
            'Hapus transaksi periode terakhir siswa ini terlebih dahulu.'
        );
    }

    // Menghapus transaksi berdasarkan primary key.
    query(
        'DELETE FROM tb_pembayaran
         WHERE id_pembayaran = ?',
        [$id]
    );

    // Menghitung ulang status pembayaran siswa.
    sinkron_cek();

    $pdo->commit();

    pesan(
        'Transaksi berhasil dihapus dan status pembayaran dihitung ulang.'
    );
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $pesan_error = $ex instanceof PDOException
        ? db_error($ex)
        : $ex->getMessage();

    pesan($pesan_error);
}

pergi('pembayaran/index.php');