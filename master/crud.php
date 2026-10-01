<?php

require_once __DIR__ . '/../includes/fungsi.php';

$user = izin(['admin']);

require __DIR__ . '/konfigurasi.php';

// Memastikan halaman master tersedia.
if (!isset($jenis, $master[$jenis])) {
    http_response_code(404);
    exit('Halaman tidak ditemukan.');
}

$cfg = $master[$jenis];
$table = $cfg['table'];
$pk = $cfg['pk'];
$judul = $cfg['title'];

$error = '';
$edit = trim($_GET['edit'] ?? '');
$data = [];

// Data pilihan kelas.
$kelas = query(
    'SELECT id_kelas, nama_kelas
     FROM tb_kelas
     ORDER BY nama_kelas'
)->fetchAll();

// Data pilihan tarif SPP.
$spp = query(
    'SELECT id_spp, tahun, nominal
     FROM tb_spp
     ORDER BY tahun, id_spp'
)->fetchAll();

// Mengambil data untuk formulir edit.
if ($edit !== '') {
    $data = query(
        "SELECT * FROM $table WHERE $pk = ?",
        [$edit]
    )->fetch();

    if (!$data) {
        http_response_code(404);
        exit('Data tidak ditemukan.');
    }
}

// Memproses tambah, ubah, dan hapus.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_token();

    try {
        $pdo->beginTransaction();

        $aksi = $_POST['aksi'] ?? '';

        if ($aksi === 'hapus') {
            $id = trim($_POST['id'] ?? '');

            if ($id === '') {
                throw new InvalidArgumentException(
                    'Identitas data tidak valid.'
                );
            }

            // Validasi penghapusan petugas.
            if ($jenis === 'petugas') {
                $target = query(
                    'SELECT level
                     FROM tb_petugas
                     WHERE id_petugas = ?
                     FOR UPDATE',
                    [$id]
                )->fetch();

                if ($id === $user['id_petugas']) {
                    throw new InvalidArgumentException(
                        'Akun yang sedang login tidak boleh dihapus.'
                    );
                }

                $jumlah_admin = query(
                    "SELECT COUNT(*)
                     FROM tb_petugas
                     WHERE level = 'admin'"
                )->fetchColumn();

                if (
                    $target
                    && $target['level'] === 'admin'
                    && $jumlah_admin <= 1
                ) {
                    throw new InvalidArgumentException(
                        'Minimal satu admin harus tersedia.'
                    );
                }
            }

            // Mencegah penghapusan tarif yang digunakan siswa.
            if ($jenis === 'spp') {
                $digunakan = query(
                    'SELECT COUNT(*)
                     FROM tb_siswa
                     WHERE id_spp = ?',
                    [$id]
                )->fetchColumn();

                if ($digunakan) {
                    throw new InvalidArgumentException(
                        'Tarif masih digunakan oleh siswa.'
                    );
                }
            }

            query(
                "DELETE FROM $table WHERE $pk = ?",
                [$id]
            );
        } elseif ($aksi === 'simpan') {
            $original = trim($_POST['original'] ?? '');
            $old = false;

            if ($original !== '') {
                $old = query(
                    "SELECT *
                     FROM $table
                     WHERE $pk = ?
                     FOR UPDATE",
                    [$original]
                )->fetch();

                if (!$old) {
                    throw new InvalidArgumentException(
                        'Data yang akan diubah tidak ditemukan.'
                    );
                }
            }

            $values = [];

            // Validasi field berdasarkan konfigurasi.
            foreach ($cfg['fields'] as $key => $field) {
                $val = $key === 'password'
                    ? ($_POST[$key] ?? '')
                    : trim($_POST[$key] ?? '');

                if (
                    $key === 'password'
                    && $old
                    && $val === ''
                ) {
                    continue;
                }

                if ($val === '') {
                    throw new InvalidArgumentException(
                        $field[0] . ' wajib diisi.'
                    );
                }

                if (
                    $field[2]
                    && mb_strlen($val) > $field[2]
                ) {
                    throw new InvalidArgumentException(
                        $field[0] . ' terlalu panjang.'
                    );
                }

                $values[$key] = $val;
            }

            if ($old && $values[$pk] !== $original) {
                throw new InvalidArgumentException(
                    'Kode utama tidak boleh diganti.'
                );
            }

            // Validasi data SPP.
            if ($jenis === 'spp') {
                if (
                    !preg_match('/^\d{4}$/', $values['tahun'])
                    || (int) $values['tahun'] < 1900
                ) {
                    throw new InvalidArgumentException(
                        'Tahun harus 4 digit dan minimal 1900.'
                    );
                }

                if (uang($values['nominal']) <= 0) {
                    throw new InvalidArgumentException(
                        'Nominal SPP harus lebih dari nol.'
                    );
                }
            }

            // Validasi data siswa.
            if ($jenis === 'siswa') {
                if (!preg_match('/^\d{10}$/', $values['nisn'])) {
                    throw new InvalidArgumentException(
                        'NISN harus tepat 10 digit.'
                    );
                }

                if (!preg_match('/^\d{1,8}$/', $values['nis'])) {
                    throw new InvalidArgumentException(
                        'NIS harus 1-8 digit.'
                    );
                }

                if (!preg_match('/^\d{8,13}$/', $values['no_telp'])) {
                    throw new InvalidArgumentException(
                        'Nomor telepon harus 8-13 digit.'
                    );
                }

                $data_kelas = query(
                    'SELECT nama_kelas
                     FROM tb_kelas
                     WHERE id_kelas = ?',
                    [$values['id_kelas']]
                )->fetch();

                $data_spp = query(
                    'SELECT id_spp
                     FROM tb_spp
                     WHERE id_spp = ?',
                    [$values['id_spp']]
                )->fetch();

                if (!$data_kelas || !$data_spp) {
                    throw new InvalidArgumentException(
                        'Kelas atau tarif SPP tidak ditemukan.'
                    );
                }

                $values['nama_kelas'] = $data_kelas['nama_kelas'];

                if (
                    $old
                    && $old['id_spp'] !== $values['id_spp']
                ) {
                    $jumlah_transaksi = query(
                        'SELECT COUNT(*)
                         FROM tb_pembayaran
                         WHERE nisn = ?',
                        [$original]
                    )->fetchColumn();

                    if ($jumlah_transaksi) {
                        throw new InvalidArgumentException(
                            'Tarif siswa yang sudah mempunyai transaksi tidak boleh diganti pada versi sederhana ini.'
                        );
                    }
                }
            }

            // Validasi data petugas.
            if ($jenis === 'petugas') {
                if (!in_array(
                    $values['level'],
                    ['admin', 'petugas', 'siswa'],
                    true
                )) {
                    throw new InvalidArgumentException(
                        'Level tidak valid.'
                    );
                }

                $username_dipakai = query(
                    'SELECT COUNT(*)
                     FROM tb_petugas
                     WHERE username = ?
                       AND id_petugas <> ?',
                    [$values['username'], $original]
                )->fetchColumn();

                if ($username_dipakai) {
                    throw new InvalidArgumentException(
                        'Username sudah dipakai.'
                    );
                }

                if (
                    $old
                    && $original === $user['id_petugas']
                    && $values['level'] !== 'admin'
                ) {
                    throw new InvalidArgumentException(
                        'Level akun admin yang sedang login tidak boleh diganti.'
                    );
                }

                if (isset($values['password'])) {
                    if (strlen($values['password']) < 6) {
                        throw new InvalidArgumentException(
                            'Password minimal 6 karakter.'
                        );
                    }

                    // Kompatibel dengan struktur password VARCHAR(32).
                    $values['password'] = md5(
                        $values['password']
                    );
                }
            }

            $cols = array_keys($values);

            if ($old) {
                $sets = implode(
                    ', ',
                    array_map(
                        fn($column) => "$column = ?",
                        $cols
                    )
                );

                query(
                    "UPDATE $table SET $sets WHERE $pk = ?",
                    array_merge(
                        array_values($values),
                        [$original]
                    )
                );
            } else {
                $columns = implode(', ', $cols);

                $marks = implode(
                    ', ',
                    array_fill(0, count($cols), '?')
                );

                query(
                    "INSERT INTO $table ($columns)
                     VALUES ($marks)",
                    array_values($values)
                );
            }
        } else {
            throw new InvalidArgumentException(
                'Aksi formulir tidak valid.'
            );
        }

        if ($jenis === 'siswa') {
            sinkron_cek();
        }

        $pdo->commit();

        pesan(
            $aksi === 'hapus'
                ? 'Data berhasil dihapus.'
                : 'Data berhasil disimpan.'
        );

        pergi('master/' . $jenis . '.php');
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex instanceof PDOException
            ? db_error($ex)
            : $ex->getMessage();

        if (($_POST['aksi'] ?? '') !== 'hapus') {
            $data = $_POST;
            $edit = $_POST['original'] ?? '';
        }
    }
}

// Pencarian data.
$search = trim($_GET['q'] ?? '');

$display = array_filter(
    array_keys($cfg['fields']),
    fn($column) => $column !== 'password'
);

$sql = "SELECT * FROM $table";
$params = [];

if ($search !== '') {
    $conditions = array_map(
        fn($column) => "$column LIKE ?",
        $display
    );

    $sql .= ' WHERE ' . implode(' OR ', $conditions);

    $params = array_fill(
        0,
        count($display),
        '%' . $search . '%'
    );
}

$rows = query(
    $sql . " ORDER BY $pk",
    $params
)->fetchAll();

// Menu sidebar admin.
$nav = [
    'index.php' => 'Dashboard',
    'master/kelas.php' => 'Data Kelas',
    'master/siswa.php' => 'Data Siswa',
    'master/spp.php' => 'Data SPP',
    'master/petugas.php' => 'Data Petugas',
    'cek.php' => 'Cek Pembayaran',
    'pembayaran/tambah.php' => 'Pembayaran',
    'pembayaran/index.php' => 'Detail Pembayaran'
];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($judul) ?> | SPP Sekolah</title>

    <link
        rel="stylesheet"
        href="<?= e(base_url()) ?>/assets/vendor/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(base_url()) ?>/assets/style.css"
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
                        <?= $aktif ? 'aria-current="page"' : '' ?>
                    >
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

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

            <!-- Pesan berhasil -->
            <?php if (isset($_SESSION['pesan'])): ?>
                <div class="alert alert-success" role="status">
                    <?= e($_SESSION['pesan']) ?>
                </div>

                <?php unset($_SESSION['pesan']); ?>
            <?php endif; ?>

            <!-- Pesan kesalahan -->
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- Form tambah dan ubah -->
            <section class="panel" aria-labelledby="judul-form">
                <h3 id="judul-form" class="h5 mb-3">
                    <?= $edit !== '' ? 'Ubah' : 'Tambah' ?>
                    <?= e(strtolower($judul)) ?>
                </h3>

                <form method="post" class="row g-3">
                    <?= token() ?>

                    <input
                        type="hidden"
                        name="aksi"
                        value="simpan"
                    >

                    <input
                        type="hidden"
                        name="original"
                        value="<?= e($edit) ?>"
                    >

                    <?php foreach ($cfg['fields'] as $key => $field): ?>
                        <?php
                        $value = $key === 'password'
                            ? ''
                            : ($data[$key] ?? '');
                        ?>

                        <div class="col-md-6">
                            <label
                                for="<?= e($key) ?>"
                                class="form-label"
                            >
                                <?= e($field[0]) ?>
                            </label>

                            <?php if (in_array(
                                $field[1],
                                ['kelas', 'spp', 'level'],
                                true
                            )): ?>
                                <?php
                                $options = [];

                                if ($field[1] === 'kelas') {
                                    foreach ($kelas as $item) {
                                        $options[$item['id_kelas']] =
                                            $item['nama_kelas'];
                                    }
                                }

                                if ($field[1] === 'spp') {
                                    foreach ($spp as $item) {
                                        $options[$item['id_spp']] =
                                            $item['id_spp']
                                            . ' / '
                                            . $item['tahun']
                                            . ' / '
                                            . rupiah($item['nominal']);
                                    }
                                }

                                if ($field[1] === 'level') {
                                    $options = [
                                        'admin' => 'Admin',
                                        'petugas' => 'Petugas',
                                        'siswa' => 'Siswa (lihat dashboard dan cek pembayaran)'
                                    ];
                                }
                                ?>

                                <select
                                    name="<?= e($key) ?>"
                                    id="<?= e($key) ?>"
                                    class="form-select"
                                    required
                                >
                                    <option value="">
                                        Pilih <?= e($field[0]) ?>
                                    </option>

                                    <?php foreach ($options as $id => $label): ?>
                                        <option
                                            value="<?= e($id) ?>"
                                            <?= (string) $value === (string) $id
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php elseif ($field[1] === 'textarea'): ?>
                                <textarea
                                    name="<?= e($key) ?>"
                                    id="<?= e($key) ?>"
                                    class="form-control"
                                    maxlength="<?= e($field[2]) ?>"
                                    rows="3"
                                    required
                                ><?= e($value) ?></textarea>

                            <?php elseif ($field[1] === 'number'): ?>
                                <input
                                    type="number"
                                    name="<?= e($key) ?>"
                                    id="<?= e($key) ?>"
                                    value="<?= e($value) ?>"
                                    class="form-control"
                                    min="1"
                                    step="1"
                                    required
                                >

                            <?php else: ?>
                                <input
                                    type="<?= e($field[1]) ?>"
                                    name="<?= e($key) ?>"
                                    id="<?= e($key) ?>"
                                    value="<?= e($value) ?>"
                                    class="form-control"
                                    maxlength="<?= e($field[2]) ?>"
                                    <?= $edit !== '' && $key === $pk
                                        ? 'readonly'
                                        : '' ?>
                                    <?= $key === 'password' && $edit !== ''
                                        ? ''
                                        : 'required' ?>
                                >
                            <?php endif; ?>

                            <?php if ($key === 'password' && $edit !== ''): ?>
                                <small class="text-secondary">
                                    Kosongkan jika password tidak diganti.
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div class="col-12">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Simpan
                        </button>

                        <a
                            class="btn btn-outline-secondary"
                            href="<?= e($jenis) ?>.php"
                        >
                            Batal
                        </a>
                    </div>
                </form>
            </section>

            <!-- Daftar data -->
            <section class="panel" aria-labelledby="judul-daftar">
                <h3 id="judul-daftar" class="h5 mb-3">
                    Daftar <?= e(strtolower($judul)) ?>
                </h3>

                <form
                    method="get"
                    class="d-flex gap-2 mb-3"
                    role="search"
                >
                    <label for="pencarian" class="visually-hidden">
                        Cari data
                    </label>

                    <input
                        type="search"
                        id="pencarian"
                        name="q"
                        class="form-control"
                        value="<?= e($search) ?>"
                        placeholder="Cari data"
                    >

                    <button
                        type="submit"
                        class="btn btn-outline-primary"
                    >
                        Cari
                    </button>

                    <a
                        class="btn btn-outline-secondary"
                        href="<?= e($jenis) ?>.php"
                    >
                        Reset
                    </a>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <caption class="visually-hidden">
                            <?= e($judul) ?>
                        </caption>

                        <thead>
                            <tr>
                                <?php foreach ($display as $key): ?>
                                    <th scope="col">
                                        <?= e($cfg['fields'][$key][0]) ?>
                                    </th>
                                <?php endforeach; ?>

                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($display as $key): ?>
                                        <td>
                                            <?= $key === 'nominal'
                                                ? rupiah($row[$key])
                                                : e($row[$key]) ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td>
                                        <div class="actions">
                                            <a
                                                class="btn btn-warning btn-sm"
                                                href="?edit=<?= urlencode($row[$pk]) ?>"
                                            >
                                                Ubah
                                            </a>

                                            <form
                                                method="post"
                                                data-confirm="Hapus data ini?"
                                            >
                                                <?= token() ?>

                                                <input
                                                    type="hidden"
                                                    name="aksi"
                                                    value="hapus"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= e($row[$pk]) ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$rows): ?>
                                <tr>
                                    <td
                                        colspan="<?= count($display) + 1 ?>"
                                        class="text-center"
                                    >
                                        Data tidak ditemukan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>

    <script src="<?= e(base_url()) ?>/assets/app.js?v=2"></script>
</body>

</html>