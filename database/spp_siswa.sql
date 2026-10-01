-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 01 Okt 2026 pada 06.12
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spp_siswa`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `cek_pembayaran`
--

CREATE TABLE `cek_pembayaran` (
  `nisn` varchar(10) NOT NULL,
  `tgl_terakhir_bayar` date NOT NULL,
  `tgl_sekarang` date NOT NULL,
  `status_pembayaran` enum('Belum Lunas','Sudah Lunas') NOT NULL,
  `jumlah_bulan` varchar(5) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `no_telp` varchar(13) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cek_pembayaran`
--

INSERT INTO `cek_pembayaran` (`nisn`, `tgl_terakhir_bayar`, `tgl_sekarang`, `status_pembayaran`, `jumlah_bulan`, `nama`, `no_telp`) VALUES
('0012345679', '2026-10-01', '2026-10-01', 'Sudah Lunas', '0', 'Acim', '081234567891'),
('0012345678', '2026-10-01', '2026-10-01', 'Sudah Lunas', '0', 'Aditsa', '081234567890'),
('0012345680', '2027-07-01', '2026-10-01', 'Sudah Lunas', '0', 'Lovensia', '081234567892'),
('0012345681', '2026-10-01', '2026-10-01', 'Sudah Lunas', '0', 'Melia', '081234567893'),
('0012345683', '2027-02-01', '2026-10-01', 'Sudah Lunas', '0', 'Restu Aji', '081234567895'),
('0012345682', '2026-10-01', '2026-10-01', 'Sudah Lunas', '0', 'Saddam', '081234567894');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_kelas`
--

CREATE TABLE `tb_kelas` (
  `id_kelas` varchar(11) NOT NULL,
  `nama_kelas` varchar(10) NOT NULL,
  `komp_keahlian` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_kelas`
--

INSERT INTO `tb_kelas` (`id_kelas`, `nama_kelas`, `komp_keahlian`) VALUES
('KLS001', 'X-RPL-1', 'Rekayasa Perangkat Lunak'),
('KLS002', 'X-RPL-2', 'Rekayasa Perangkat Lunak'),
('KLS003', 'XI-RPL-1', 'Rekayasa Perangkat Lunak'),
('KLS004', 'XI-TKJ-1', 'Teknik Komputer dan Jaringan'),
('KLS005', 'XII-TKJ', 'Teknik Komputer dan Jaringan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pembayaran`
--

CREATE TABLE `tb_pembayaran` (
  `id_pembayaran` varchar(11) NOT NULL,
  `status` enum('Belum Lunas','Sudah Lunas') NOT NULL,
  `nisn` varchar(10) NOT NULL,
  `tgl_bayar` date NOT NULL,
  `tgl_terakhir_bayar` date NOT NULL,
  `batas_pembayaran` date NOT NULL,
  `jumlah_bulan` varchar(10) NOT NULL,
  `id_spp` varchar(40) NOT NULL,
  `nominal_bayar` varchar(100) NOT NULL,
  `jumlah_bayar` varchar(40) NOT NULL,
  `kembalian` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_pembayaran`
--

INSERT INTO `tb_pembayaran` (`id_pembayaran`, `status`, `nisn`, `tgl_bayar`, `tgl_terakhir_bayar`, `batas_pembayaran`, `jumlah_bulan`, `id_spp`, `nominal_bayar`, `jumlah_bayar`, `kembalian`) VALUES
('BYR001', 'Sudah Lunas', '0012345678', '2026-10-01', '2026-09-01', '2026-10-10', '1', 'SPP001', '150000', '150000', '0'),
('BYR002', 'Sudah Lunas', '0012345679', '2026-10-01', '2026-09-01', '2026-10-10', '1', 'SPP002', '175000', '200000', '25000'),
('BYR258383E6', 'Sudah Lunas', '0012345681', '2026-10-01', '2026-10-01', '2026-10-10', '1', 'SPP004', '225000', '250000', '25000'),
('BYR41C974F8', 'Sudah Lunas', '0012345680', '2026-10-01', '2027-07-01', '2026-10-10', '10', 'SPP003', '2000000', '2000000', '0'),
('BYR65B5D206', 'Sudah Lunas', '0012345678', '2026-10-01', '2026-10-01', '2026-10-10', '1', 'SPP001', '150000', '200000', '50000'),
('BYRA3D5C853', 'Sudah Lunas', '0012345679', '2026-10-01', '2026-10-01', '2026-10-10', '1', 'SPP002', '175000', '200000', '25000'),
('BYRB1FF6B7E', 'Sudah Lunas', '0012345683', '2026-10-01', '2027-02-01', '2026-10-10', '5', 'SPP005', '1250000', '1250000', '0'),
('BYRDAD9B3D8', 'Sudah Lunas', '0012345682', '2026-10-01', '2026-10-01', '2026-10-10', '1', 'SPP005', '250000', '250000', '0');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_petugas`
--

CREATE TABLE `tb_petugas` (
  `id_petugas` varchar(11) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(32) NOT NULL,
  `nama_petugas` varchar(35) NOT NULL,
  `level` enum('admin','petugas','siswa') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_petugas`
--

INSERT INTO `tb_petugas` (`id_petugas`, `username`, `password`, `nama_petugas`, `level`) VALUES
('PTG001', 'admin', '0192023a7bbd73250516f069df18b500', 'Administrator', 'admin'),
('PTG002', 'petugas1', 'e10adc3949ba59abbe56e057f20f883e', 'Budi', 'petugas'),
('PTG003', 'petugas2', 'e10adc3949ba59abbe56e057f20f883e', 'Siti', 'petugas'),
('PTG004', 'petugas3', 'e10adc3949ba59abbe56e057f20f883e', 'Dedi', 'petugas'),
('PTG005', 'petugas4', 'e10adc3949ba59abbe56e057f20f883e', 'Rina', 'petugas');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_siswa`
--

CREATE TABLE `tb_siswa` (
  `nisn` varchar(10) NOT NULL,
  `nis` varchar(8) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `id_kelas` varchar(11) NOT NULL,
  `nama_kelas` varchar(10) NOT NULL,
  `alamat` text NOT NULL,
  `no_telp` varchar(13) NOT NULL,
  `id_spp` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_siswa`
--

INSERT INTO `tb_siswa` (`nisn`, `nis`, `nama`, `id_kelas`, `nama_kelas`, `alamat`, `no_telp`, `id_spp`) VALUES
('0012345678', '10000001', 'Aditsa', 'KLS001', 'X-RPL-1', 'Depok', '081234567890', 'SPP001'),
('0012345679', '10000002', 'Acim', 'KLS002', 'X-RPL-2', 'Tangerang', '081234567891', 'SPP002'),
('0012345680', '10000003', 'Lovensia', 'KLS003', 'XI-RPL-1', 'Jakarta', '081234567892', 'SPP003'),
('0012345681', '10000004', 'Melia', 'KLS004', 'XI-TKJ-1', 'Bogor', '081234567893', 'SPP004'),
('0012345682', '10000005', 'Saddam', 'KLS005', 'XII-TKJ', 'Bekasi', '081234567894', 'SPP005'),
('0012345683', '10000006', 'Restu Aji', 'KLS005', 'XII-TKJ', 'Balaraja', '081234567895', 'SPP005');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_spp`
--

CREATE TABLE `tb_spp` (
  `id_spp` varchar(11) NOT NULL,
  `tahun` int(11) NOT NULL,
  `nominal` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tb_spp`
--

INSERT INTO `tb_spp` (`id_spp`, `tahun`, `nominal`) VALUES
('SPP001', 2026, '150000'),
('SPP002', 2026, '175000'),
('SPP003', 2026, '200000'),
('SPP004', 2026, '225000'),
('SPP005', 2026, '250000');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `cek_pembayaran`
--
ALTER TABLE `cek_pembayaran`
  ADD KEY `idx_cek_nisn` (`nisn`),
  ADD KEY `idx_cek_nama` (`nama`),
  ADD KEY `idx_cek_no_telp` (`no_telp`);

--
-- Indeks untuk tabel `tb_kelas`
--
ALTER TABLE `tb_kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD KEY `idx_nama_kelas` (`nama_kelas`);

--
-- Indeks untuk tabel `tb_pembayaran`
--
ALTER TABLE `tb_pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `idx_pembayaran_nisn` (`nisn`),
  ADD KEY `idx_pembayaran_id_spp` (`id_spp`);

--
-- Indeks untuk tabel `tb_petugas`
--
ALTER TABLE `tb_petugas`
  ADD PRIMARY KEY (`id_petugas`);

--
-- Indeks untuk tabel `tb_siswa`
--
ALTER TABLE `tb_siswa`
  ADD PRIMARY KEY (`nisn`),
  ADD KEY `idx_nis` (`nis`),
  ADD KEY `idx_nama` (`nama`),
  ADD KEY `idx_id_kelas` (`id_kelas`),
  ADD KEY `idx_nama_kelas` (`nama_kelas`),
  ADD KEY `idx_no_telp` (`no_telp`),
  ADD KEY `idx_id_spp` (`id_spp`);

--
-- Indeks untuk tabel `tb_spp`
--
ALTER TABLE `tb_spp`
  ADD PRIMARY KEY (`id_spp`);

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `cek_pembayaran`
--
ALTER TABLE `cek_pembayaran`
  ADD CONSTRAINT `fk_cek_nama` FOREIGN KEY (`nama`) REFERENCES `tb_siswa` (`nama`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cek_nisn` FOREIGN KEY (`nisn`) REFERENCES `tb_siswa` (`nisn`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cek_no_telp` FOREIGN KEY (`no_telp`) REFERENCES `tb_siswa` (`no_telp`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_pembayaran`
--
ALTER TABLE `tb_pembayaran`
  ADD CONSTRAINT `fk_pembayaran_id_spp` FOREIGN KEY (`id_spp`) REFERENCES `tb_siswa` (`id_spp`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembayaran_nisn` FOREIGN KEY (`nisn`) REFERENCES `tb_siswa` (`nisn`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tb_siswa`
--
ALTER TABLE `tb_siswa`
  ADD CONSTRAINT `fk_siswa_id_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `tb_kelas` (`id_kelas`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_siswa_nama_kelas` FOREIGN KEY (`nama_kelas`) REFERENCES `tb_kelas` (`nama_kelas`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
