-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Sep 2024 pada 14.49
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ayutoys`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang`
--

CREATE TABLE `barang` (
  `barang_id` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `stok` int(11) DEFAULT 0,
  `satuan` varchar(10) DEFAULT 'PCS',
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(100) DEFAULT NULL,
  `aktif` enum('aktif','tidak') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `barang`
--

INSERT INTO `barang` (`barang_id`, `nama_barang`, `kategori`, `stok`, `satuan`, `dibuat`, `photo`, `aktif`) VALUES
(1, 'Ipad Apel Quran', 'Mainan Elektronik', 7, 'PCS', '2024-08-02 00:35:00', '../aset/images/fotobarang/Ipad_Apel_Quran2024-08-21_18-37-11.jpeg', 'aktif'),
(2, 'Happy Doll', 'Mainan Boneka', 5, 'PCS', '2024-08-02 00:37:00', '../aset/images/fotobarang/Happy_Doll2024-08-21_18-37-39.jpeg', 'aktif'),
(3, 'Dream Bubble', 'Mainan Outdoor', 10, 'PCS', '2024-08-02 00:38:00', '../aset/images/fotobarang/Dream_Bubble2024-08-21_18-38-16.jpeg', 'aktif'),
(4, 'Thomas Train', 'Mainan Kendaraan', 23, 'PCS', '2024-08-02 00:40:00', '../aset/images/fotobarang/Thomas_Train2024-08-21_18-41-42.jpeg', 'aktif'),
(5, 'Barbie Boom', 'Mainan Outdoor', 6, 'PCS', '2024-08-02 00:42:00', '../aset/images/fotobarang/Barbie_Boom2024-08-21_18-42-59.jpeg', 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang_keluar`
--

CREATE TABLE `barang_keluar` (
  `barang_keluar_id` int(11) NOT NULL,
  `plg` varchar(50) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `kode_prd` varchar(50) NOT NULL,
  `kode_bk` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_jual` decimal(10,2) NOT NULL,
  `tanggal_keluar` date NOT NULL,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `barang_keluar`
--

INSERT INTO `barang_keluar` (`barang_keluar_id`, `plg`, `barang_id`, `kode_prd`, `kode_bk`, `user_id`, `jumlah`, `harga_jual`, `tanggal_keluar`, `dibuat`) VALUES
(1, 'Mega', 1, 'PRD1496021082024', 'BK1139622082024', 1, 1, 20000.00, '2024-08-07', '2024-08-07 07:07:00'),
(2, 'Ezza', 4, 'PRD1579821082024', 'BK1893722082024', 1, 1, 19000.00, '2024-08-12', '2024-08-12 12:11:00');

--
-- Trigger `barang_keluar`
--
DELIMITER $$
CREATE TRIGGER `after_barang_keluar_insert` AFTER INSERT ON `barang_keluar` FOR EACH ROW BEGIN
    UPDATE stok
    SET stok_masuk = stok_masuk - NEW.jumlah
    WHERE kode_prd = NEW.kode_prd;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `barang_masuk_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kode_bm` varchar(50) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `stok` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_beli` decimal(10,2) NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `barang_masuk`
--

INSERT INTO `barang_masuk` (`barang_masuk_id`, `user_id`, `kode_bm`, `barang_id`, `supplier_id`, `stok`, `jumlah`, `harga_beli`, `tanggal_masuk`, `dibuat`) VALUES
(1, 1, 'BM1709021082024', 1, 1, 0, 8, 16000.00, '2024-08-03', '2024-08-03 08:46:00'),
(2, 1, 'BM1709021082024', 2, 1, 0, 5, 15700.00, '2024-08-03', '2024-08-03 08:46:00'),
(3, 1, 'BM1709021082024', 3, 1, 0, 10, 16000.00, '2024-08-03', '2024-08-03 08:46:00'),
(4, 1, 'BM1709021082024', 4, 1, 0, 24, 15500.00, '2024-08-03', '2024-08-03 08:46:00'),
(5, 1, 'BM1709021082024', 5, 1, 0, 6, 7250.00, '2024-08-03', '2024-08-03 08:46:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bk_sementara`
--

CREATE TABLE `bk_sementara` (
  `bk_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `kode_prd` varchar(50) NOT NULL,
  `stok` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_jual` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bk_t`
--

CREATE TABLE `bk_t` (
  `bkt_id` int(11) NOT NULL,
  `kode_bk` varchar(50) NOT NULL,
  `bayar` decimal(10,2) NOT NULL,
  `kembalian` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bk_t`
--

INSERT INTO `bk_t` (`bkt_id`, `kode_bk`, `bayar`, `kembalian`, `total`) VALUES
(1, 'BK1139622082024', 20000.00, 0.00, 20000.00),
(2, 'BK1893722082024', 50000.00, 31000.00, 19000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `bm_sementara`
--

CREATE TABLE `bm_sementara` (
  `bm_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `stok` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_beli` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bm_t`
--

CREATE TABLE `bm_t` (
  `bmt_id` int(11) NOT NULL,
  `kode_bm` varchar(50) NOT NULL,
  `total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bm_t`
--

INSERT INTO `bm_t` (`bmt_id`, `kode_bm`, `total`) VALUES
(1, 'BM1709021082024', 782000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok`
--

CREATE TABLE `stok` (
  `id_stok` int(11) NOT NULL,
  `barang_masuk_id` int(11) DEFAULT NULL,
  `kode_bm` varchar(50) NOT NULL,
  `kode_prd` varchar(50) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `harga_beli` decimal(10,2) DEFAULT NULL,
  `harga_jual` decimal(10,2) DEFAULT NULL,
  `stok` int(11) NOT NULL,
  `stok_masuk` int(11) DEFAULT NULL,
  `stok_akhir` int(11) DEFAULT NULL,
  `lokasi_penyimpanan` varchar(50) NOT NULL,
  `tanggal_masuk` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stok`
--

INSERT INTO `stok` (`id_stok`, `barang_masuk_id`, `kode_bm`, `kode_prd`, `barang_id`, `supplier_id`, `user_id`, `harga_beli`, `harga_jual`, `stok`, `stok_masuk`, `stok_akhir`, `lokasi_penyimpanan`, `tanggal_masuk`) VALUES
(1, 1, 'BM1709021082024', 'PRD1496021082024', 1, 1, 1, 16000.00, 20000.00, 0, 7, 16, 'Rak 4', '2024-08-03'),
(2, 2, 'BM1709021082024', 'PRD1746421082024', 2, 1, 1, 15700.00, 19000.00, 0, 5, 10, 'Rak 2', '2024-08-03'),
(3, 3, 'BM1709021082024', 'PRD1515621082024', 3, 1, 1, 16000.00, 20000.00, 0, 10, 20, 'Rak 5', '2024-08-03'),
(4, 4, 'BM1709021082024', 'PRD1579821082024', 4, 1, 1, 15500.00, 19000.00, 0, 23, 48, 'Rak 6', '2024-08-03'),
(5, 5, 'BM1709021082024', 'PRD1202321082024', 5, 1, 1, 7250.00, 10000.00, 0, 6, 12, 'Rak 10', '2024-08-03');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stokopname`
--

CREATE TABLE `stokopname` (
  `id_stokopname` int(11) NOT NULL,
  `kode_prd` varchar(50) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `stok_sistem` int(11) NOT NULL,
  `stok_fisik` int(11) NOT NULL,
  `selisih` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stokopname`
--

INSERT INTO `stokopname` (`id_stokopname`, `kode_prd`, `barang_id`, `stok_sistem`, `stok_fisik`, `selisih`, `catatan`, `tanggal`) VALUES
(1, 'PRD1202321082024', 5, 6, 6, 0, 'Jumblah fisik barang masi ada 6', '2024-08-04 01:00:00'),
(2, 'PRD1515621082024', 3, 10, 10, 0, 'Jumblah fisik barang masih ada 10', '2024-08-04 01:01:00'),
(3, 'PRD1746421082024', 2, 5, 5, 0, 'Jumblah fisik barang masih ada 5', '2024-08-04 01:04:00'),
(4, 'PRD1496021082024', 1, 8, 8, 0, 'Jumblah fisik barang masih ada 8', '2024-08-04 01:07:00'),
(5, 'PRD1579821082024', 4, 24, 24, 0, 'Jumblah fisik barang masih ada 24', '2024-08-04 01:12:00');

--
-- Trigger `stokopname`
--
DELIMITER $$
CREATE TRIGGER `update_barang_stok` AFTER INSERT ON `stokopname` FOR EACH ROW BEGIN
    UPDATE barang
    SET stok = stok - NEW.stok_sistem + New.stok_fisik
    WHERE barang_id = NEW.barang_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `supplier`
--

CREATE TABLE `supplier` (
  `supplier_id` int(11) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `kontak` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `aktif` enum('aktif','tidak') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `supplier`
--

INSERT INTO `supplier` (`supplier_id`, `nama_supplier`, `kontak`, `alamat`, `dibuat`, `aktif`) VALUES
(1, 'Pandawa Toys', '087826472321', 'Jl.Raya Otista No.92 Jamblang', '2024-08-21 16:34:13', 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_telepon` varchar(15) DEFAULT NULL,
  `role` enum('admin','staff','owner') NOT NULL,
  `dibuat` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(100) DEFAULT NULL,
  `aktif` enum('aktif','tidak') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `nama_lengkap`, `no_telepon`, `role`, `dibuat`, `photo`, `aktif`) VALUES
(1, 'adminayu', '$2y$10$8fMq52nuQFKA4tvy8ATd8Ow38/saM0XHvDhWkfpzNBlpsVCGmv0qO', 'adminayu@gmail.com', 'Ayu Toys', '0123456789', 'admin', '2024-08-08 16:16:39', NULL, 'aktif'),
(2, 'pemilikayu', '$2y$10$kKtwwpS3uPvwJQ4JmpIjqOY90J6L8UUO8cfjJ312JiC6IKF563gIG', 'abas.sunandar@gmail.com', 'Sunandar', '082121134031', 'owner', '2024-08-20 05:13:31', '../aset/images/fotoprofil/sunandar.jpg', 'aktif');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`barang_id`);

--
-- Indeks untuk tabel `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD PRIMARY KEY (`barang_keluar_id`),
  ADD KEY `barang_id` (`barang_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kode_bk` (`kode_bk`);

--
-- Indeks untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`barang_masuk_id`),
  ADD KEY `barang_id` (`barang_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kode_bm` (`kode_bm`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indeks untuk tabel `bk_sementara`
--
ALTER TABLE `bk_sementara`
  ADD PRIMARY KEY (`bk_id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indeks untuk tabel `bk_t`
--
ALTER TABLE `bk_t`
  ADD PRIMARY KEY (`bkt_id`),
  ADD UNIQUE KEY `kode_bk` (`kode_bk`);

--
-- Indeks untuk tabel `bm_sementara`
--
ALTER TABLE `bm_sementara`
  ADD PRIMARY KEY (`bm_id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indeks untuk tabel `bm_t`
--
ALTER TABLE `bm_t`
  ADD PRIMARY KEY (`bmt_id`),
  ADD UNIQUE KEY `kode_bm` (`kode_bm`);

--
-- Indeks untuk tabel `stok`
--
ALTER TABLE `stok`
  ADD PRIMARY KEY (`id_stok`),
  ADD UNIQUE KEY `kode_prd` (`kode_prd`),
  ADD KEY `barang_id` (`barang_id`),
  ADD KEY `barang_masuk_id` (`barang_masuk_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `stokopname`
--
ALTER TABLE `stokopname`
  ADD PRIMARY KEY (`id_stokopname`),
  ADD KEY `fk_barang` (`barang_id`);

--
-- Indeks untuk tabel `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `barang`
--
ALTER TABLE `barang`
  MODIFY `barang_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `barang_keluar`
--
ALTER TABLE `barang_keluar`
  MODIFY `barang_keluar_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `barang_masuk_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `bk_sementara`
--
ALTER TABLE `bk_sementara`
  MODIFY `bk_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `bk_t`
--
ALTER TABLE `bk_t`
  MODIFY `bkt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `bm_sementara`
--
ALTER TABLE `bm_sementara`
  MODIFY `bm_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `bm_t`
--
ALTER TABLE `bm_t`
  MODIFY `bmt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `stok`
--
ALTER TABLE `stok`
  MODIFY `id_stok` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `stokopname`
--
ALTER TABLE `stokopname`
  MODIFY `id_stokopname` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `barang_keluar`
--
ALTER TABLE `barang_keluar`
  ADD CONSTRAINT `barang_keluar_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `stok` (`barang_id`),
  ADD CONSTRAINT `barang_keluar_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `barang_keluar_ibfk_3` FOREIGN KEY (`kode_bk`) REFERENCES `bk_t` (`kode_bk`);

--
-- Ketidakleluasaan untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`barang_id`),
  ADD CONSTRAINT `barang_masuk_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `barang_masuk_ibfk_3` FOREIGN KEY (`kode_bm`) REFERENCES `bm_t` (`kode_bm`),
  ADD CONSTRAINT `barang_masuk_ibfk_4` FOREIGN KEY (`supplier_id`) REFERENCES `supplier` (`supplier_id`);

--
-- Ketidakleluasaan untuk tabel `bk_sementara`
--
ALTER TABLE `bk_sementara`
  ADD CONSTRAINT `bk_sementara_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `stok` (`barang_id`);

--
-- Ketidakleluasaan untuk tabel `bm_sementara`
--
ALTER TABLE `bm_sementara`
  ADD CONSTRAINT `bm_sementara_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`barang_id`);

--
-- Ketidakleluasaan untuk tabel `stok`
--
ALTER TABLE `stok`
  ADD CONSTRAINT `stok_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`barang_id`),
  ADD CONSTRAINT `stok_ibfk_2` FOREIGN KEY (`barang_masuk_id`) REFERENCES `barang_masuk` (`barang_masuk_id`),
  ADD CONSTRAINT `stok_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `stokopname`
--
ALTER TABLE `stokopname`
  ADD CONSTRAINT `fk_barang` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`barang_id`),
  ADD CONSTRAINT `stokopname_ibfk_1` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`barang_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
