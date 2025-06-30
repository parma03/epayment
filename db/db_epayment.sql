-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 29, 2025 at 07:12 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_epayment`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_barang`
--

CREATE TABLE `tb_barang` (
  `id_barang` bigint(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `deskripsi_barang` text NOT NULL,
  `stok_barang` int(11) NOT NULL,
  `harga_barang` decimal(10,2) NOT NULL,
  `photo_barang` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_barang`
--

INSERT INTO `tb_barang` (`id_barang`, `nama_barang`, `deskripsi_barang`, `stok_barang`, `harga_barang`, `photo_barang`, `created_at`, `updated_at`) VALUES
(1, 'tes barang 1', 'tes 1111', 3, 1500000.00, '685fc1f7e2771.jpg', '2025-06-28 17:20:39', '2025-06-28 18:47:15'),
(2, 'tes barang 2', 'tew', 200, 1000000.00, '685fc2e1615cf.jpg', '2025-06-28 17:24:33', '2025-06-28 18:43:48');

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengiriman`
--

CREATE TABLE `tb_pengiriman` (
  `id_pengiriman` bigint(11) NOT NULL,
  `id_transaksi` bigint(11) NOT NULL,
  `id_driver` bigint(11) DEFAULT NULL,
  `id_gudang` bigint(11) DEFAULT NULL,
  `status` enum('disiapkan','dikirim','terkirim','selesai') NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_pengiriman`
--

INSERT INTO `tb_pengiriman` (`id_pengiriman`, `id_transaksi`, `id_driver`, `id_gudang`, `status`, `created_at`, `updated_at`) VALUES
(2, 8, NULL, NULL, 'selesai', '2025-06-28 21:00:31', '2025-06-29 02:25:04');

-- --------------------------------------------------------

--
-- Table structure for table `tb_transaksi`
--

CREATE TABLE `tb_transaksi` (
  `id_transaksi` bigint(11) NOT NULL,
  `id_barang` bigint(11) NOT NULL,
  `id_user` bigint(11) NOT NULL,
  `nama_pemesan` varchar(255) NOT NULL,
  `nohp_pemesan` varchar(20) NOT NULL,
  `alamat_pemesan` text NOT NULL,
  `jumlah_beli` int(11) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `status_pembayaran` enum('pending','paid','failed','cancelled') NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `snap_token` text NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_transaksi`
--

INSERT INTO `tb_transaksi` (`id_transaksi`, `id_barang`, `id_user`, `nama_pemesan`, `nohp_pemesan`, `alamat_pemesan`, `jumlah_beli`, `total_harga`, `status_pembayaran`, `order_id`, `snap_token`, `created_at`, `updated_at`) VALUES
(8, 1, 6, 'tes pelanggan 1', '08215225551', 'tes', 2, 3000000.00, 'paid', 'ORDER-1751119603-2680', 'e38895da-342e-4ec1-98a4-377ca9b66c25', '2025-06-28 21:07:10', NULL),
(9, 1, 6, 'tes pelanggan 1', '08215225551', 'tes', 2, 3000000.00, 'paid', 'ORDER-1751119603-2680', 'e38895da-342e-4ec1-98a4-377ca9b66c25', '2025-06-28 21:07:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` bigint(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrator','Driver','Gudang','Pelayan','Pelanggan') NOT NULL,
  `photo_profile` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `update_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `email`, `password`, `role`, `photo_profile`, `created_at`, `update_at`) VALUES
(1, 'admin@gmail.com', '123', 'Administrator', NULL, '2025-06-25 20:11:53', NULL),
(2, 'tes123@gmail.com1', '123', 'Administrator', '685facdb4e230.png', '2025-06-28 15:50:35', '2025-06-28 15:51:25'),
(3, 'tespelayan@gmail.com', '123', 'Pelayan', '685fadddf27cb.png', '2025-06-28 15:54:53', '2025-06-28 15:59:25'),
(4, 'tesdriver@gmail.com', '123', 'Driver', '685faedb49634.png', '2025-06-28 15:59:07', NULL),
(5, 'tesgudang@gmail.com', '123', 'Gudang', '685fafb398a3b.png', '2025-06-28 16:02:43', NULL),
(6, 'tespelanggan@gmail.com', '123', 'Pelanggan', '685fafe6b70d3.png', '2025-06-28 16:03:34', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_barang`
--
ALTER TABLE `tb_barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `tb_pengiriman`
--
ALTER TABLE `tb_pengiriman`
  ADD PRIMARY KEY (`id_pengiriman`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_driver` (`id_driver`),
  ADD KEY `id_gudang` (`id_gudang`);

--
-- Indexes for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_barang` (`id_barang`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_barang`
--
ALTER TABLE `tb_barang`
  MODIFY `id_barang` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_pengiriman`
--
ALTER TABLE `tb_pengiriman`
  MODIFY `id_pengiriman` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  MODIFY `id_transaksi` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_pengiriman`
--
ALTER TABLE `tb_pengiriman`
  ADD CONSTRAINT `tb_pengiriman_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `tb_transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_pengiriman_ibfk_2` FOREIGN KEY (`id_driver`) REFERENCES `tb_user` (`id_user`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `tb_pengiriman_ibfk_3` FOREIGN KEY (`id_gudang`) REFERENCES `tb_user` (`id_user`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `tb_transaksi`
--
ALTER TABLE `tb_transaksi`
  ADD CONSTRAINT `tb_transaksi_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `tb_barang` (`id_barang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_transaksi_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
