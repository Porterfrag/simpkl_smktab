-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 15, 2025 at 12:35 PM
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
-- Database: `db_pkl`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id_absensi` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Hadir','Izin','Sakit','Alpha') NOT NULL,
  `dicatat_oleh` enum('Siswa','Pembimbing','Admin','Sistem') NOT NULL,
  `id_pembimbing` int(11) DEFAULT NULL,
  `jam_absen` time DEFAULT NULL,
  `latitude` varchar(30) DEFAULT NULL,
  `longitude` varchar(30) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `bukti_foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id_absensi`, `id_siswa`, `tanggal`, `status`, `dicatat_oleh`, `id_pembimbing`, `jam_absen`, `latitude`, `longitude`, `keterangan`, `bukti_foto`) VALUES
(1, 6, '2025-11-11', 'Hadir', 'Siswa', NULL, '17:31:27', NULL, NULL, 'test', NULL),
(2, 6, '2025-11-12', 'Hadir', 'Siswa', NULL, '01:45:51', NULL, NULL, 'testing', 'absen_6_1762908351.jpeg'),
(14, 33, '2025-11-13', 'Hadir', 'Siswa', NULL, '21:05:49', '-3.338479', '114.696167', '', 'absen_33_1763039149.jpeg'),
(15, 6, '2025-11-13', 'Alpha', 'Pembimbing', 3, NULL, NULL, NULL, NULL, NULL),
(18, 6, '2025-11-10', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(19, 33, '2025-11-10', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(20, 35, '2025-11-10', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(21, 33, '2025-11-11', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(22, 35, '2025-11-11', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(23, 33, '2025-11-12', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(24, 35, '2025-11-12', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(25, 36, '2025-11-12', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(26, 36, '2025-11-10', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(27, 36, '2025-11-11', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(35, 36, '2025-11-13', 'Alpha', 'Admin', NULL, NULL, NULL, NULL, NULL, NULL),
(36, 35, '2025-11-13', 'Alpha', 'Admin', NULL, NULL, NULL, NULL, NULL, NULL),
(37, 36, '2025-11-14', 'Hadir', 'Siswa', NULL, '00:00:54', '-3.338490', '114.696166', '', 'absen_36_1763049654.jpeg'),
(38, 37, '2025-11-11', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(39, 37, '2025-11-12', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(46, 37, '2025-11-13', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(47, 38, '2025-11-14', 'Hadir', 'Siswa', NULL, '19:36:13', '-3.323674', '114.610614', '', 'absen_38_1763120173.jpeg'),
(48, 38, '2025-11-11', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(49, 38, '2025-11-12', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL),
(50, 38, '2025-11-13', 'Alpha', 'Sistem', NULL, NULL, NULL, NULL, 'Tidak absen (Otomatis)', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jurnal_harian`
--

CREATE TABLE `jurnal_harian` (
  `id_jurnal` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kegiatan` text NOT NULL,
  `foto_kegiatan` varchar(255) DEFAULT NULL,
  `status_validasi` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
  `catatan_pembimbing` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `jurnal_harian`
--

INSERT INTO `jurnal_harian` (`id_jurnal`, `id_siswa`, `tanggal`, `kegiatan`, `foto_kegiatan`, `status_validasi`, `catatan_pembimbing`) VALUES
(2, 6, '2025-11-11', 'testttt', 'foto_69135862290d68.70590211.jpg', 'Disetujui', ''),
(3, 6, '2025-11-11', 'testttt', 'foto_691358d22b95d1.42355249.jpg', 'Disetujui', ''),
(4, 6, '2025-11-11', 'test', 'foto_691364e6611326.93625685.jpg', 'Disetujui', ''),
(5, 6, '2025-11-12', 'testing', 'foto_6913d8db0bbfb6.43109685.jpg', 'Disetujui', ''),
(17, 36, '2025-11-13', 'test', NULL, 'Disetujui', ''),
(18, 36, '2025-11-14', 'tidur', NULL, 'Disetujui', 'ya'),
(19, 37, '2025-11-14', 'Membuat fitur copy pada lembar kfr dan juga lembar program terapi', 'foto_6916048d8360b9.33988979.png', 'Disetujui', ''),
(21, 38, '2025-11-14', 'test ngewe', NULL, 'Disetujui', 'bagus');

-- --------------------------------------------------------

--
-- Table structure for table `pembimbing`
--

CREATE TABLE `pembimbing` (
  `id_pembimbing` int(11) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pembimbing`
--

INSERT INTO `pembimbing` (`id_pembimbing`, `nip`, `nama_guru`, `no_telp`) VALUES
(2, '1234567890', 'Riza Aulia', '081528493029'),
(3, '1234567895', 'Handrianus Bahi Wibowo', '081528493028'),
(4, '1234567899', 'Joko Widodo', '081528493028'),
(5, '6894623149232612', 'Maretha', '0841616494'),
(7, '12345678988', 'Riza Aulia 2', '081528493028');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `key_setting` varchar(50) NOT NULL,
  `value_setting` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`key_setting`, `value_setting`, `description`) VALUES
('grading_start_date', '2025-12-01', 'Tanggal mulai pembimbing dapat memasukkan nilai akhir PKL.'),
('pkl_end_date', '2025-12-31', 'Tanggal resmi berakhirnya kegiatan PKL.'),
('pkl_start_date', '2025-11-12', 'Tanggal resmi dimulainya kegiatan PKL.');

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id_pengumuman` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  `tanggal_post` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`id_pengumuman`, `judul`, `isi`, `id_admin`, `tanggal_post`) VALUES
(3, 'Monitoring', 'test', 1, '2025-11-12 13:08:55');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian`
--

CREATE TABLE `penilaian` (
  `id_penilaian` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_pembimbing` int(11) DEFAULT NULL,
  `aspek_disiplin` int(11) DEFAULT 0,
  `aspek_kompetensi` int(11) DEFAULT 0,
  `aspek_kerjasama` int(11) DEFAULT 0,
  `aspek_inisiatif` int(11) DEFAULT 0,
  `catatan_penilaian` text DEFAULT NULL,
  `tanggal_penilaian` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `penilaian`
--

INSERT INTO `penilaian` (`id_penilaian`, `id_siswa`, `id_pembimbing`, `aspek_disiplin`, `aspek_kompetensi`, `aspek_kerjasama`, `aspek_inisiatif`, `catatan_penilaian`, `tanggal_penilaian`) VALUES
(1, 6, 3, 90, 90, 90, 90, 'test', '2025-11-11 15:39:17');

-- --------------------------------------------------------

--
-- Table structure for table `perusahaan`
--

CREATE TABLE `perusahaan` (
  `id_perusahaan` int(11) NOT NULL,
  `nama_perusahaan` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `kontak_person` varchar(100) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `hari_kerja` varchar(50) NOT NULL DEFAULT '1,2,3,4,5'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `perusahaan`
--

INSERT INTO `perusahaan` (`id_perusahaan`, `nama_perusahaan`, `alamat`, `kontak_person`, `no_telp`, `hari_kerja`) VALUES
(1, 'PT ASTA CANON', 'Jl. Veteran KM. 14,000', 'Ridho', '081528493028', '1,2,3,4,5'),
(2, 'PT. INFO KOMPUTER', 'Jl. Sudimampir', 'Yuniarti Meidina', '081528493028', '1,2,3,4,5'),
(3, 'PT. AXA HEXA DECA', 'Jl. Keringat Bau', 'Mejiro Doto', '081528493028', '1,2,3,4,5'),
(4, 'SMKN 1 Sungai Tebok', 'Abumbun Jaya', 'Yanes', '08156121541156', '1,2,3,4,5'),
(5, 'PT. EXO', 'Jl. Veteran', 'Yuliana', '08123456', '1,2,3,4,5');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `kelas` varchar(10) DEFAULT NULL,
  `id_perusahaan` int(11) DEFAULT NULL,
  `id_pembimbing` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nis`, `nama_lengkap`, `jurusan`, `kelas`, `id_perusahaan`, `id_pembimbing`) VALUES
(6, '19216818', 'Aulia Azizah', 'RPL', 'XII RPL 2', 2, 3),
(33, '19216811', 'Ani Suryani', 'RPL', 'XII RPL 3', 1, 3),
(35, '192168182', 'Pebrianus Pangeleloe', 'RPL', 'XII RPL 3', 1, 3),
(36, '192168103', 'Budi', 'RPL', 'XII RPL 1', 2, 3),
(37, '19216814', 'Yogi Pradana', 'RPL', 'XII RPL 3', 1, 3),
(38, '123', 'Nur Azizah Amini 2', 'RPL', 'XII RPL 3', 2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','siswa','pembimbing') NOT NULL,
  `id_ref` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `id_ref`) VALUES
(1, 'admin', '$2y$10$p4GviANjL6IOWoaaMVkWy.lHE9d7H1iYU/5BAr5ufenHEChEduMYW', 'admin', NULL),
(5, '1234567890', '$2y$10$NEnCzbBXk12CoG2u2IQV1OuCztAwC/zPIUNX.CKOGJIZuijm1ovYW', 'pembimbing', 2),
(6, '19216818', '$2y$10$BK6dbyvuo9CjE6hpbr/kauOh9vCIGREgGCDViPAAhsX0353Vry18C', 'siswa', 6),
(8, '1234567895', '$2y$10$Qk9MOQVqsqYCXIeKRmjf0.zJvvyHz2c.aZ6UBiVTBXUxQEsn7uMfW', 'pembimbing', 3),
(9, '1234567899', '$2y$10$VDVSYk47q/hXP8bJimbpBOTfNsOVzL3PLYyfLlytxDOLP4SVRejqy', 'pembimbing', 4),
(11, '6894623149232612', '$2y$10$PPbduQfUyRnx/BabN8h3.e.Jv9DFbCyKzJAmoVxCYUALoH40zFvyW', 'pembimbing', 5),
(36, '19216811', '$2y$10$isQeUXbUKHJkj52tSFAgpeWPqVXfX6Oh3tS.TEV5tpELb1f0xYSlC', 'siswa', 33),
(37, '12345678988', '$2y$10$oYr1/7LvLfZZ8rzYvR4q0.TtkrWK3tDwon9qyqYW.Hy.Uw4eOaQ/.', 'pembimbing', 7),
(38, '192168182', '$2y$10$u10fRY0NkrMEWHxGuiz.fO61wwmFkw9nuBwoWXRsm3Fj7/k4oPzaS', 'siswa', 35),
(39, '192168103', '$2y$10$N26.NnfRKG7L6F4zMHS0COwAs/GNEV5FYyIUb6q0qRNTsrCHtXj6u', 'siswa', 36),
(40, '19216814', '$2y$10$s0FZ9li/0Z5wDGxgyI9ry.hlEz2y8P68xUIYvKhKHnDmrXT9ZvAzS', 'siswa', 37),
(41, '123', '$2y$10$rWzLN0AocSxCUZ1zt.XexuQSbpFQ5Oc3fnb7YuKjpMGT2ThYXPJzG', 'siswa', 38);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id_absensi`),
  ADD UNIQUE KEY `id_siswa` (`id_siswa`,`tanggal`),
  ADD KEY `id_pembimbing` (`id_pembimbing`);

--
-- Indexes for table `jurnal_harian`
--
ALTER TABLE `jurnal_harian`
  ADD PRIMARY KEY (`id_jurnal`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `pembimbing`
--
ALTER TABLE `pembimbing`
  ADD PRIMARY KEY (`id_pembimbing`),
  ADD UNIQUE KEY `nip` (`nip`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`key_setting`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id_pengumuman`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indexes for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id_penilaian`),
  ADD UNIQUE KEY `id_siswa` (`id_siswa`),
  ADD KEY `id_pembimbing` (`id_pembimbing`);

--
-- Indexes for table `perusahaan`
--
ALTER TABLE `perusahaan`
  ADD PRIMARY KEY (`id_perusahaan`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nis` (`nis`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id_absensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `jurnal_harian`
--
ALTER TABLE `jurnal_harian`
  MODIFY `id_jurnal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `pembimbing`
--
ALTER TABLE `pembimbing`
  MODIFY `id_pembimbing` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id_pengumuman` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id_penilaian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `perusahaan`
--
ALTER TABLE `perusahaan`
  MODIFY `id_perusahaan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`id_pembimbing`) REFERENCES `pembimbing` (`id_pembimbing`) ON DELETE SET NULL;

--
-- Constraints for table `jurnal_harian`
--
ALTER TABLE `jurnal_harian`
  ADD CONSTRAINT `jurnal_harian_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);

--
-- Constraints for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD CONSTRAINT `pengumuman_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD CONSTRAINT `penilaian_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `penilaian_ibfk_2` FOREIGN KEY (`id_pembimbing`) REFERENCES `pembimbing` (`id_pembimbing`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
