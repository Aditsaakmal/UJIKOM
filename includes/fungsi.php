<?php

// Memulai session sebelum menampilkan HTML.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/koneksi.php';

// Menjalankan SQL menggunakan prepared statement.
function query($sql, $params = [])
{
    global $pdo;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt;
}

// Mengamankan teks sebelum ditampilkan sebagai HTML.
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Memformat angka menjadi rupiah.
function rupiah($value)
{
    return 'Rp ' . number_format(
        (float) $value,
        0,
        ',',
        '.'
    );
}

// Mengembalikan daftar nama bulan Indonesia.
function daftar_bulan()
{
    return [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];
}

// Mengubah tanggal atau periode menjadi "Oktober 2026".
function bulan_indonesia($tanggal)
{
    if (!$tanggal) {
        return '-';
    }

    $periode = substr((string) $tanggal, 0, 7);

    if (!preg_match('/^(\d{4})-(\d{2})$/', $periode, $hasil)) {
        return '-';
    }

    $nomor = (int) $hasil[2];
    $bulan = daftar_bulan();

    if (!isset($bulan[$nomor])) {
        return '-';
    }

    return $bulan[$nomor] . ' ' . $hasil[1];
}

// Mengubah tanggal menjadi "1 Oktober 2026".
function tanggal_indonesia($tanggal)
{
    if (!$tanggal || !tanggal_valid($tanggal)) {
        return '-';
    }

    return (int) substr($tanggal, 8, 2)
        . ' '
        . bulan_indonesia($tanggal);
}

// Mendapatkan alamat dasar aplikasi.
function base_url()
{
    $script = str_replace(
        '\\',
        '/',
        $_SERVER['SCRIPT_NAME']
    );

    $folder = dirname($script);

    if (in_array(
        basename($folder),
        ['master', 'pembayaran'],
        true
    )) {
        $folder = dirname($folder);
    }

    return rtrim($folder, '/.');
}

// Mengalihkan halaman dan menghentikan proses.
function pergi($path)
{
    header('Location: ' . base_url() . '/' . $path);
    exit;
}

// Memastikan pengguna sudah login dan akunnya masih tersedia.
function wajib_login()
{
    if (empty($_SESSION['user_id'])) {
        pergi('login.php');
    }

    $user = query(
        'SELECT id_petugas, nama_petugas, level
         FROM tb_petugas
         WHERE id_petugas = ?',
        [$_SESSION['user_id']]
    )->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);
        pergi('login.php');
    }

    return $user;
}

// Memeriksa hak akses berdasarkan level pengguna.
function izin($levels)
{
    $user = wajib_login();

    if (!in_array($user['level'], $levels, true)) {
        http_response_code(403);

        exit(
            'Akses ditolak. Menu ini tidak tersedia untuk level akun Anda.'
        );
    }

    return $user;
}

// Membuat token keamanan untuk formulir.
function token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf" value="'
        . e($_SESSION['csrf'])
        . '">';
}

// Memeriksa token formulir sebelum melakukan perubahan data.
function cek_token()
{
    $session_token = $_SESSION['csrf'] ?? '';
    $form_token = $_POST['csrf'] ?? '';

    if (
        $session_token === ''
        || !hash_equals($session_token, $form_token)
    ) {
        http_response_code(403);

        exit(
            'Token formulir tidak valid. Muat ulang halaman dan coba lagi.'
        );
    }
}

// Menyimpan pesan yang ditampilkan sekali setelah redirect.
function pesan($text)
{
    $_SESSION['pesan'] = $text;
}

// Mengubah error database menjadi pesan yang mudah dipahami.
function db_error($ex)
{
    error_log($ex->getMessage());

    $code = (int) ($ex->errorInfo[1] ?? 0);

    if ($code === 1062) {
        return 'Kode atau identitas tersebut sudah digunakan.';
    }

    if ($code === 1451) {
        return 'Data masih dipakai oleh tabel lain sehingga tidak dapat dihapus atau diubah.';
    }

    if ($code === 1452) {
        return 'Data referensi tidak tersedia. Periksa kelas, siswa, atau ID SPP.';
    }

    return 'Proses database gagal. Periksa log PHP dan konfigurasi database.';
}

// Memastikan tanggal benar dan menggunakan format YYYY-MM-DD.
function tanggal_valid($value)
{
    $tanggal = DateTime::createFromFormat(
        '!Y-m-d',
        (string) $value
    );

    return $tanggal
        && $tanggal->format('Y-m-d') === $value;
}

// Memvalidasi uang sebagai angka rupiah bulat.
function uang($value)
{
    if (!preg_match('/^\d{1,10}$/', (string) $value)) {
        throw new InvalidArgumentException(
            'Uang harus berupa angka rupiah bulat, maksimal 10 digit.'
        );
    }

    return (int) $value;
}

// Mengambil siswa beserta periode pembayaran terakhir dan statusnya.
function daftar_status($search = '')
{
    $sql = "
        SELECT
            s.*,
            sp.tahun,
            sp.nominal,
            p.terakhir
        FROM tb_siswa s
        LEFT JOIN tb_spp sp
            ON sp.id_spp = s.id_spp
        LEFT JOIN (
            SELECT
                nisn,
                MAX(tgl_terakhir_bayar) AS terakhir
            FROM tb_pembayaran
            WHERE status = 'Sudah Lunas'
            GROUP BY nisn
        ) p ON p.nisn = s.nisn
    ";

    $params = [];

    if ($search !== '') {
        $sql .= ' WHERE s.nisn LIKE ? OR s.nama LIKE ?';

        $params = [
            '%' . $search . '%',
            '%' . $search . '%'
        ];
    }

    $rows = query(
        $sql . ' ORDER BY s.nama',
        $params
    )->fetchAll();

    $bulan_ini = date('Y-m-01');

    foreach ($rows as &$row) {
        $periode_terakhir = $row['terakhir']
            ? substr($row['terakhir'], 0, 7) . '-01'
            : null;

        $row['status_pembayaran'] = (
            $periode_terakhir
            && $periode_terakhir >= $bulan_ini
        ) ? 'Sudah Lunas' : 'Belum Lunas';

        if ($periode_terakhir) {
            $awal = date(
                'Y-m-01',
                strtotime($periode_terakhir . ' +1 month')
            );
        } else {
            $tahun = $row['tahun'] ?: date('Y');
            $awal = $tahun . '-01-01';
        }

        $tanggal_awal = new DateTime($awal);
        $tanggal_sekarang = new DateTime($bulan_ini);

        if ($tanggal_awal > $tanggal_sekarang) {
            $row['jumlah_bulan'] = 0;
        } else {
            $selisih_tahun = (
                (int) $tanggal_sekarang->format('Y')
                - (int) $tanggal_awal->format('Y')
            );

            $selisih_bulan = (
                (int) $tanggal_sekarang->format('n')
                - (int) $tanggal_awal->format('n')
            );

            $row['jumlah_bulan'] = (
                $selisih_tahun * 12
                + $selisih_bulan
                + 1
            );
        }
    }

    unset($row);

    return $rows;
}

// Memperbarui tabel cek berdasarkan transaksi pembayaran.
function sinkron_cek()
{
    $rows = daftar_status();

    foreach ($rows as $siswa) {
        query(
            'DELETE FROM cek_pembayaran WHERE nisn = ?',
            [$siswa['nisn']]
        );

        query(
            'INSERT INTO cek_pembayaran (
                nisn,
                tgl_terakhir_bayar,
                tgl_sekarang,
                status_pembayaran,
                jumlah_bulan,
                nama,
                no_telp
            ) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $siswa['nisn'],
                $siswa['terakhir'] ?: date('Y-m-01'),
                date('Y-m-d'),
                $siswa['status_pembayaran'],
                (string) $siswa['jumlah_bulan'],
                $siswa['nama'],
                $siswa['no_telp']
            ]
        );
    }
}

// Membuat kode transaksi sebanyak 11 karakter.
function kode_bayar()
{
    return 'BYR' . strtoupper(bin2hex(random_bytes(4)));
}