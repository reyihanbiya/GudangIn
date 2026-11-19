-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2026 at 02:42 PM
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
-- Database: `gudangin`
--

-- --------------------------------------------------------

--
-- Table structure for table `coa`
--

CREATE TABLE `coa` (
  `kode_akun` varchar(10) NOT NULL,
  `nama_akun` varchar(100) NOT NULL,
  `jenis_akun` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_pembelian`
--

CREATE TABLE `detail_pembelian` (
  `id_detail` int(11) NOT NULL,
  `id_pembelian` varchar(10) NOT NULL,
  `id_persediaan` varchar(10) NOT NULL,
  `harga_beli` decimal(15,2) NOT NULL,
  `jumlah_barang` int(11) NOT NULL,
  `subtotal` decimal(15,2) GENERATED ALWAYS AS (`harga_beli` * `jumlah_barang`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `id_detail` int(11) NOT NULL,
  `id_penjualan` varchar(10) NOT NULL,
  `id_persediaan` varchar(10) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `jumlah_barang` int(11) NOT NULL,
  `subtotal` decimal(15,2) GENERATED ALWAYS AS (`harga_satuan` * `jumlah_barang`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jurnal_umum`
--

CREATE TABLE `jurnal_umum` (
  `id_jurnal` int(11) NOT NULL,
  `kode_akun` varchar(10) NOT NULL,
  `id_pembelian` varchar(10) DEFAULT NULL,
  `id_penjualan` varchar(10) DEFAULT NULL,
  `tanggal_jurnal` date NOT NULL,
  `no_akun` varchar(10) DEFAULT NULL,
  `nama_akun` varchar(100) DEFAULT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `kredit` decimal(15,2) NOT NULL DEFAULT 0.00
) ;

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id_pegawai` varchar(10) NOT NULL,
  `nama_pegawai` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `jabatan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id_pelanggan` varchar(10) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `id_pembelian` varchar(10) NOT NULL,
  `id_supplier` varchar(10) NOT NULL,
  `id_pegawai` varchar(10) NOT NULL,
  `tanggal` date NOT NULL,
  `status_pengiriman` varchar(30) DEFAULT 'Diproses'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penjualan`
--

CREATE TABLE `penjualan` (
  `id_penjualan` varchar(10) NOT NULL,
  `id_pelanggan` varchar(10) NOT NULL,
  `id_pegawai` varchar(10) NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `persediaan`
--

CREATE TABLE `persediaan` (
  `id_persediaan` varchar(10) NOT NULL,
  `id_pegawai` varchar(10) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori_barang` varchar(50) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `harga_jual` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `id_supplier` varchar(10) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_kartu_stok`
-- (See below for the actual view)
--
CREATE TABLE `view_kartu_stok` (
`id_persediaan` varchar(10)
,`nama_barang` varchar(100)
,`tanggal` date
,`keterangan` varchar(9)
,`id_transaksi` varchar(10)
,`masuk` int(11)
,`keluar` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_laporanpembelian`
-- (See below for the actual view)
--
CREATE TABLE `view_laporanpembelian` (
`id_pembelian` varchar(10)
,`tanggal` date
,`nama_supplier` varchar(100)
,`nama_pegawai` varchar(100)
,`status_pengiriman` varchar(30)
,`total_pembelian` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_laporanpenjualan`
-- (See below for the actual view)
--
CREATE TABLE `view_laporanpenjualan` (
`id_penjualan` varchar(10)
,`tanggal` date
,`nama_pelanggan` varchar(100)
,`nama_pegawai` varchar(100)
,`total_penjualan` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Structure for view `view_kartu_stok`
--
DROP TABLE IF EXISTS `view_kartu_stok`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_kartu_stok`  AS SELECT `ps`.`id_persediaan` AS `id_persediaan`, `ps`.`nama_barang` AS `nama_barang`, `t`.`tanggal` AS `tanggal`, `t`.`keterangan` AS `keterangan`, `t`.`id_transaksi` AS `id_transaksi`, `t`.`masuk` AS `masuk`, `t`.`keluar` AS `keluar` FROM (`persediaan` `ps` join (select `dp`.`id_persediaan` AS `id_persediaan`,`p`.`tanggal` AS `tanggal`,'Pembelian' AS `keterangan`,`p`.`id_pembelian` AS `id_transaksi`,`dp`.`jumlah_barang` AS `masuk`,0 AS `keluar` from (`detail_pembelian` `dp` join `pembelian` `p` on(`p`.`id_pembelian` = `dp`.`id_pembelian`)) union all select `dj`.`id_persediaan` AS `id_persediaan`,`j`.`tanggal` AS `tanggal`,'Penjualan' AS `Penjualan`,`j`.`id_penjualan` AS `id_penjualan`,0 AS `0`,`dj`.`jumlah_barang` AS `jumlah_barang` from (`detail_penjualan` `dj` join `penjualan` `j` on(`j`.`id_penjualan` = `dj`.`id_penjualan`))) `t` on(`t`.`id_persediaan` = `ps`.`id_persediaan`)) ORDER BY `ps`.`id_persediaan` ASC, `t`.`tanggal` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `view_laporanpembelian`
--
DROP TABLE IF EXISTS `view_laporanpembelian`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_laporanpembelian`  AS SELECT `p`.`id_pembelian` AS `id_pembelian`, `p`.`tanggal` AS `tanggal`, `s`.`nama_supplier` AS `nama_supplier`, `pg`.`nama_pegawai` AS `nama_pegawai`, `p`.`status_pengiriman` AS `status_pengiriman`, coalesce(sum(`d`.`subtotal`),0) AS `total_pembelian` FROM (((`pembelian` `p` join `supplier` `s` on(`s`.`id_supplier` = `p`.`id_supplier`)) join `pegawai` `pg` on(`pg`.`id_pegawai` = `p`.`id_pegawai`)) left join `detail_pembelian` `d` on(`d`.`id_pembelian` = `p`.`id_pembelian`)) GROUP BY `p`.`id_pembelian`, `p`.`tanggal`, `s`.`nama_supplier`, `pg`.`nama_pegawai`, `p`.`status_pengiriman` ;

-- --------------------------------------------------------

--
-- Structure for view `view_laporanpenjualan`
--
DROP TABLE IF EXISTS `view_laporanpenjualan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_laporanpenjualan`  AS SELECT `j`.`id_penjualan` AS `id_penjualan`, `j`.`tanggal` AS `tanggal`, `pl`.`nama_pelanggan` AS `nama_pelanggan`, `pg`.`nama_pegawai` AS `nama_pegawai`, coalesce(sum(`d`.`subtotal`),0) AS `total_penjualan` FROM (((`penjualan` `j` join `pelanggan` `pl` on(`pl`.`id_pelanggan` = `j`.`id_pelanggan`)) join `pegawai` `pg` on(`pg`.`id_pegawai` = `j`.`id_pegawai`)) left join `detail_penjualan` `d` on(`d`.`id_penjualan` = `j`.`id_penjualan`)) GROUP BY `j`.`id_penjualan`, `j`.`tanggal`, `pl`.`nama_pelanggan`, `pg`.`nama_pegawai` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `coa`
--
ALTER TABLE `coa`
  ADD PRIMARY KEY (`kode_akun`);

--
-- Indexes for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD PRIMARY KEY (`id_detail`,`id_pembelian`),
  ADD KEY `fk_dpembelian_pembelian` (`id_pembelian`),
  ADD KEY `fk_dpembelian_persediaan` (`id_persediaan`);

--
-- Indexes for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`id_detail`,`id_penjualan`),
  ADD KEY `fk_dpenjualan_penjualan` (`id_penjualan`),
  ADD KEY `fk_dpenjualan_persediaan` (`id_persediaan`);

--
-- Indexes for table `jurnal_umum`
--
ALTER TABLE `jurnal_umum`
  ADD PRIMARY KEY (`id_jurnal`),
  ADD KEY `fk_jurnal_coa` (`kode_akun`),
  ADD KEY `fk_jurnal_pembelian` (`id_pembelian`),
  ADD KEY `fk_jurnal_penjualan` (`id_penjualan`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id_pegawai`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id_pelanggan`);

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id_pembelian`),
  ADD KEY `fk_pembelian_supplier` (`id_supplier`),
  ADD KEY `fk_pembelian_pegawai` (`id_pegawai`);

--
-- Indexes for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id_penjualan`),
  ADD KEY `fk_penjualan_pelanggan` (`id_pelanggan`),
  ADD KEY `fk_penjualan_pegawai` (`id_pegawai`);

--
-- Indexes for table `persediaan`
--
ALTER TABLE `persediaan`
  ADD PRIMARY KEY (`id_persediaan`),
  ADD KEY `fk_persediaan_pegawai` (`id_pegawai`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`id_supplier`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jurnal_umum`
--
ALTER TABLE `jurnal_umum`
  MODIFY `id_jurnal` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pembelian`
--
ALTER TABLE `detail_pembelian`
  ADD CONSTRAINT `fk_dpembelian_pembelian` FOREIGN KEY (`id_pembelian`) REFERENCES `pembelian` (`id_pembelian`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dpembelian_persediaan` FOREIGN KEY (`id_persediaan`) REFERENCES `persediaan` (`id_persediaan`) ON UPDATE CASCADE;

--
-- Constraints for table `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `fk_dpenjualan_penjualan` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dpenjualan_persediaan` FOREIGN KEY (`id_persediaan`) REFERENCES `persediaan` (`id_persediaan`) ON UPDATE CASCADE;

--
-- Constraints for table `jurnal_umum`
--
ALTER TABLE `jurnal_umum`
  ADD CONSTRAINT `fk_jurnal_coa` FOREIGN KEY (`kode_akun`) REFERENCES `coa` (`kode_akun`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_pembelian` FOREIGN KEY (`id_pembelian`) REFERENCES `pembelian` (`id_pembelian`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jurnal_penjualan` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `fk_pembelian_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id_pegawai`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembelian_supplier` FOREIGN KEY (`id_supplier`) REFERENCES `supplier` (`id_supplier`) ON UPDATE CASCADE;

--
-- Constraints for table `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `fk_penjualan_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id_pegawai`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_penjualan_pelanggan` FOREIGN KEY (`id_pelanggan`) REFERENCES `pelanggan` (`id_pelanggan`) ON UPDATE CASCADE;

--
-- Constraints for table `persediaan`
--
ALTER TABLE `persediaan`
  ADD CONSTRAINT `fk_persediaan_pegawai` FOREIGN KEY (`id_pegawai`) REFERENCES `pegawai` (`id_pegawai`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
