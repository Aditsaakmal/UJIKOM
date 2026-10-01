// Menampilkan konfirmasi sebelum menghapus data.
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
        if (!confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// Daftar bulan untuk tampilan periode pembayaran.
const namaBulan = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
];

// Mengubah nilai YYYY-MM menjadi nama bulan dan tahun.
function formatBulan(periode) {
    const bagian = periode.split('-');

    if (bagian.length !== 2) {
        return '-';
    }

    const bulan = Number(bagian[1]);

    if (bulan < 1 || bulan > 12) {
        return '-';
    }

    return namaBulan[bulan - 1] + ' ' + bagian[0];
}

// Menggabungkan dropdown bulan dan tahun menjadi nilai YYYY-MM.
function simpanPeriodePilihan() {
    const bulan = document.getElementById('bulan_pilihan');
    const tahun = document.getElementById('tahun_pilihan');
    const periode = document.getElementById('bulan_mulai');

    if (!bulan || !tahun || !periode) {
        return;
    }

    periode.value = tahun.value + '-' + bulan.value;
}

// Menampilkan periode tertentu pada dropdown.
function tampilkanPeriodePilihan(nilai) {
    const bulan = document.getElementById('bulan_pilihan');
    const tahun = document.getElementById('tahun_pilihan');

    if (!bulan || !tahun) {
        return;
    }

    const bagian = nilai.split('-');

    if (bagian.length !== 2) {
        return;
    }

    // Tambahkan tahun jika periode otomatis berada di luar pilihan awal.
    const tahunTersedia = Array.from(tahun.options).some(
        option => option.value === bagian[0]
    );

    if (!tahunTersedia) {
        tahun.add(new Option(bagian[0], bagian[0]));
    }

    tahun.value = bagian[0];
    bulan.value = bagian[1];

    simpanPeriodePilihan();
}

// Mengatur periode berdasarkan siswa yang dipilih.
function aturPeriodeSiswa() {
    const siswa = document.getElementById('nisn');
    const bulan = document.getElementById('bulan_pilihan');
    const tahun = document.getElementById('tahun_pilihan');
    const info = document.getElementById('info_periode');

    if (!siswa || !bulan || !tahun) {
        return;
    }

    const pilihan = siswa.options[siswa.selectedIndex];
    const periodeBerikutnya = pilihan.dataset.mulai || '';
    const otomatis = periodeBerikutnya !== '';

    if (otomatis) {
        tampilkanPeriodePilihan(periodeBerikutnya);
    } else {
        simpanPeriodePilihan();
    }

    // Siswa yang sudah membayar otomatis melanjutkan bulan terakhir.
    bulan.disabled = otomatis;
    tahun.disabled = otomatis;

    if (info) {
        info.textContent = otomatis
            ? 'Pembayaran berikutnya dimulai pada '
                + formatBulan(periodeBerikutnya)
                + '. Periode ditetapkan otomatis.'
            : 'Pembayaran pertama: pilih bulan dan tahun awal pembayaran.';
    }

    hitungPembayaran();
}

// Menghitung pratinjau total, kembalian, dan periode yang dibayar.
function hitungPembayaran() {
    const siswa = document.getElementById('nisn');

    if (!siswa) {
        return;
    }

    const pilihan = siswa.options[siswa.selectedIndex];

    const nominal = Number(
        pilihan.dataset.nominal || 0
    );

    const jumlahBulan = Number(
        document.getElementById('jumlah_bulan').value || 0
    );

    const uangDiterima = Number(
        document.getElementById('jumlah_bayar').value || 0
    );

    const total = nominal * jumlahBulan;
    const kembalian = Math.max(0, uangDiterima - total);

    const formatRupiah = nilai =>
        'Rp ' + nilai.toLocaleString('id-ID');

    document.getElementById('tarif').textContent =
        formatRupiah(nominal);

    document.getElementById('total').textContent =
        formatRupiah(total);

    document.getElementById('kembalian').textContent =
        formatRupiah(kembalian);

    const periode = document.getElementById('bulan_mulai');
    const tampilan = document.getElementById('periode_dibayar');

    if (!periode || !tampilan) {
        return;
    }

    if (
        !siswa.value
        || !Number.isInteger(jumlahBulan)
        || jumlahBulan < 1
        || jumlahBulan > 12
    ) {
        tampilan.textContent = '-';
        return;
    }

    const bagian = periode.value.split('-');
    const tahunAwal = Number(bagian[0]);
    const bulanAwal = Number(bagian[1]);

    if (
        bagian.length !== 2
        || bulanAwal < 1
        || bulanAwal > 12
    ) {
        tampilan.textContent = '-';
        return;
    }

    // Hitung periode akhir tanpa bergantung pada zona waktu browser.
    const indeksAkhir = bulanAwal - 1 + jumlahBulan - 1;
    const tahunAkhir = tahunAwal + Math.floor(indeksAkhir / 12);
    const bulanAkhir = (indeksAkhir % 12) + 1;

    const periodeAkhir = tahunAkhir
        + '-'
        + String(bulanAkhir).padStart(2, '0');

    tampilan.textContent = jumlahBulan === 1
        ? formatBulan(periode.value)
        : formatBulan(periode.value)
            + ' sampai '
            + formatBulan(periodeAkhir);
}

// Memasang event hanya jika halaman memiliki form pembayaran.
const inputSiswa = document.getElementById('nisn');
const inputBulan = document.getElementById('bulan_pilihan');
const inputTahun = document.getElementById('tahun_pilihan');

if (inputSiswa && inputBulan && inputTahun) {
    inputSiswa.addEventListener('change', aturPeriodeSiswa);

    [inputBulan, inputTahun].forEach(input => {
        input.addEventListener('change', () => {
            simpanPeriodePilihan();
            hitungPembayaran();
        });
    });

    ['jumlah_bulan', 'jumlah_bayar'].forEach(id => {
        const input = document.getElementById(id);

        if (input) {
            input.addEventListener('input', hitungPembayaran);
        }
    });

    // Pastikan nilai tersembunyi sesuai dropdown saat form dikirim.
    inputSiswa.form.addEventListener('submit', () => {
        simpanPeriodePilihan();
    });

    aturPeriodeSiswa();
}

// Membuka dialog cetak browser.
document.querySelectorAll('[data-print]').forEach(button => {
    button.addEventListener('click', () => {
        window.print();
    });
});