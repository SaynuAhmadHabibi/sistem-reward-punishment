-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2026 at 03:03 AM
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
-- Database: `sistem_reward_punishment`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 3, 'create_penilaian', 'Menambah penilaian untuk karyawan ID: 11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-02 00:51:27'),
(2, 3, 'calculate_topsis', 'Menghitung TOPSIS untuk penilaian ID: 12, Nilai Preferensi: 0.36002550013068', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-02 01:08:43'),
(3, 3, 'create_penilaian', 'Menambah penilaian untuk karyawan ID: 9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-02 01:26:38'),
(4, 3, 'calculate_topsis', 'Menghitung TOPSIS untuk penilaian ID: 13, Nilai Preferensi: 0.35729080783144', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-02 01:28:10');

-- --------------------------------------------------------

--
-- Table structure for table `backup_log`
--

CREATE TABLE `backup_log` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `size` bigint(20) DEFAULT NULL,
  `backup_type` enum('full','partial') DEFAULT 'full',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departemen`
--

CREATE TABLE `departemen` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departemen`
--

INSERT INTO `departemen` (`id`, `kode`, `nama`, `deskripsi`, `created_at`) VALUES
(1, 'IT', 'Information Technology', 'Departemen Teknologi Informasi', '2026-02-01 05:49:29'),
(2, 'HRD', 'Human Resource Development', 'Departemen Sumber Daya Manusia', '2026-02-01 05:49:29'),
(3, 'FIN', 'Finance', 'Departemen Keuangan', '2026-02-01 05:49:29'),
(4, 'MKT', 'Marketing', 'Departemen Pemasaran', '2026-02-01 05:49:29'),
(5, 'OPS', 'Operations', 'Departemen Operasional', '2026-02-01 05:49:29'),
(6, 'PRD', 'Production', 'Departemen Produksi', '2026-02-01 05:49:29'),
(7, 'LOG', 'Logistics', 'Departemen Logistik', '2026-02-01 05:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `jabatan`
--

CREATE TABLE `jabatan` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `level` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jabatan`
--

INSERT INTO `jabatan` (`id`, `kode`, `nama`, `level`, `created_at`) VALUES
(1, 'DIR', 'Direktur', 1, '2026-02-01 05:49:30'),
(2, 'MGR', 'Manager', 2, '2026-02-01 05:49:30'),
(3, 'SPV', 'Supervisor', 3, '2026-02-01 05:49:30'),
(4, 'STF', 'Staff', 4, '2026-02-01 05:49:30'),
(5, 'OPR', 'Operator', 5, '2026-02-01 05:49:30'),
(6, 'INT', 'Intern', 6, '2026-02-01 05:49:30');

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id` int(11) NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `departemen_id` int(11) DEFAULT NULL,
  `jabatan_id` int(11) DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `status` enum('aktif','non-aktif') DEFAULT 'aktif',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `karyawan`
--

INSERT INTO `karyawan` (`id`, `nik`, `nama`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `email`, `telepon`, `departemen_id`, `jabatan_id`, `tanggal_masuk`, `status`, `foto`, `created_at`, `updated_at`) VALUES
(1, 'EMP001', 'Budi Santoso', 'L', 'Jakarta', '1985-05-15', 'Jl. Merdeka No. 123, Jakarta', 'budi@example.com', '081234567890', 1, 2, '2020-01-15', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(2, 'EMP002', 'Siti Rahayu', 'P', 'Bandung', '1990-08-20', 'Jl. Sudirman No. 45, Bandung', 'siti@example.com', '081298765432', 2, 3, '2021-03-10', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(3, 'EMP003', 'Ahmad Wijaya', 'L', 'Surabaya', '1988-12-05', 'Jl. Thamrin No. 67, Surabaya', 'ahmad@example.com', '082112345678', 3, 4, '2019-11-25', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(4, 'EMP004', 'Dewi Lestari', 'P', 'Yogyakarta', '1992-03-30', 'Jl. Gajah Mada No. 89, Yogyakarta', 'dewi@example.com', '081345678901', 4, 4, '2022-06-15', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(5, 'EMP005', 'Joko Prasetyo', 'L', 'Semarang', '1987-07-12', 'Jl. Diponegoro No. 101, Semarang', 'joko@example.com', '081556677889', 5, 5, '2021-09-01', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(6, 'EMP006', 'Maya Sari', 'P', 'Medan', '1995-11-08', 'Jl. Imam Bonjol No. 25, Medan', 'maya@example.com', '081667788990', 1, 4, '2023-02-20', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(7, 'EMP007', 'Rudi Hartono', 'L', 'Palembang', '1983-09-14', 'Jl. Sudirman No. 78, Palembang', 'rudi@example.com', '081778899001', 6, 3, '2018-07-10', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(8, 'EMP008', 'Nina Kusuma', 'P', 'Makassar', '1991-06-22', 'Jl. Pettarani No. 45, Makassar', 'nina@example.com', '081889900112', 7, 5, '2020-09-05', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(9, 'EMP009', 'Dedi Rahman', 'L', 'Balikpapan', '1989-04-18', 'Jl. MT Haryono No. 90, Balikpapan', 'dedi@example.com', '081990011223', 3, 4, '2021-12-01', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(10, 'EMP010', 'Rina Amelia', 'P', 'Pekanbaru', '1993-12-03', 'Jl. Sudirman No. 112, Pekanbaru', 'rina@example.com', '082001122334', 2, 4, '2022-08-15', 'aktif', NULL, '2026-02-01 05:49:30', '2026-02-01 05:49:30'),
(11, 'EMP0011', 'saynu', 'L', 'Basecamp Rinjani', '1998-10-12', 'jalan sejahtera raya no 65\\r\\nrt03/004', 'saynuahmad12@gmail.com', '081218285870', 2, 6, '2025-12-01', 'aktif', '', '2026-02-02 00:44:50', '2026-02-02 00:44:50');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian`
--

CREATE TABLE `penilaian` (
  `id` int(11) NOT NULL,
  `karyawan_id` int(11) NOT NULL,
  `penilai_id` int(11) NOT NULL,
  `tanggal_penilaian` date NOT NULL,
  `kinerja` decimal(5,2) NOT NULL CHECK (`kinerja` >= 0 and `kinerja` <= 100),
  `kedisiplinan` decimal(5,2) NOT NULL CHECK (`kedisiplinan` >= 0 and `kedisiplinan` <= 100),
  `kerjasama` decimal(5,2) NOT NULL CHECK (`kerjasama` >= 0 and `kerjasama` <= 100),
  `absensi` decimal(5,2) NOT NULL CHECK (`absensi` >= 0 and `absensi` <= 30),
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `topsis_status` varchar(20) DEFAULT 'pending' COMMENT 'Status perhitungan TOPSIS: pending, calculating, calculated',
  `topsis_d_plus` decimal(10,6) DEFAULT NULL COMMENT 'Jarak ke solusi ideal positif',
  `topsis_d_minus` decimal(10,6) DEFAULT NULL COMMENT 'Jarak ke solusi ideal negatif',
  `topsis_preference` decimal(10,6) DEFAULT NULL COMMENT 'Nilai preferensi TOPSIS (0-1)',
  `topsis_category` varchar(20) DEFAULT NULL COMMENT 'Kategori hasil: reward, normal, punishment',
  `topsis_level` varchar(50) DEFAULT NULL COMMENT 'Level detail: sangat_baik, baik, cukup, ringan, sedang, berat',
  `calculated_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu perhitungan TOPSIS selesai'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penilaian`
--

INSERT INTO `penilaian` (`id`, `karyawan_id`, `penilai_id`, `tanggal_penilaian`, `kinerja`, `kedisiplinan`, `kerjasama`, `absensi`, `catatan`, `created_at`, `topsis_status`, `topsis_d_plus`, `topsis_d_minus`, `topsis_preference`, `topsis_category`, `topsis_level`, `calculated_at`) VALUES
(1, 1, 1, '2026-02-01', 85.00, 90.00, 88.00, 2.00, 'Kinerja sangat baik, disiplin tinggi', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 1, '2026-02-01', 78.00, 85.00, 80.00, 3.00, 'Kerjasama tim baik, perlu peningkatan kinerja', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, 1, '2026-02-01', 92.00, 88.00, 90.00, 1.00, 'Performansi luar biasa', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 4, 1, '2026-02-01', 65.00, 70.00, 75.00, 5.00, 'Perlu perbaikan dalam absensi', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 5, 1, '2026-02-01', 45.00, 50.00, 55.00, 8.00, 'Memerlukan perhatian khusus', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 6, 1, '2026-02-01', 88.00, 92.00, 85.00, 1.00, 'Staff IT berpotensi tinggi', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 7, 1, '2026-02-01', 76.00, 80.00, 82.00, 4.00, 'Supervisor production yang handal', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(8, 8, 1, '2026-02-01', 82.00, 85.00, 88.00, 2.00, 'Operator logistics efisien', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(9, 9, 1, '2026-02-01', 70.00, 75.00, 78.00, 6.00, 'Perlu monitoring lebih intensif', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(10, 10, 1, '2026-02-01', 90.00, 88.00, 92.00, 1.00, 'HRD staff excellent', '2026-02-01 05:49:30', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 11, 3, '2026-02-02', 80.00, 90.00, 88.00, 27.00, NULL, '2026-02-02 00:46:00', 'pending', NULL, NULL, NULL, NULL, NULL, NULL),
(12, 11, 3, '2026-03-04', 89.00, 98.00, 85.00, 27.00, NULL, '2026-02-02 00:51:27', 'calculated', 0.129384, 0.072786, 0.360026, 'normal', 'normal', '2026-02-02 01:08:43'),
(13, 9, 3, '2026-03-16', 88.00, 77.00, 67.00, 25.00, NULL, '2026-02-02 01:26:38', 'calculated', 0.104343, 0.058005, 0.357291, '0', 'normal', '2026-02-02 01:28:10');

-- --------------------------------------------------------

--
-- Table structure for table `punishment`
--

CREATE TABLE `punishment` (
  `id` int(11) NOT NULL,
  `karyawan_id` int(11) NOT NULL,
  `penilaian_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `topsis_score` decimal(10,6) NOT NULL,
  `level` enum('ringan','sedang','berat') NOT NULL,
  `alasan` text NOT NULL,
  `sanksi` text DEFAULT NULL,
  `diberikan_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `punishment`
--

INSERT INTO `punishment` (`id`, `karyawan_id`, `penilaian_id`, `tanggal`, `topsis_score`, `level`, `alasan`, `sanksi`, `diberikan_oleh`, `created_at`) VALUES
(1, 4, 4, '2026-02-01', 0.654322, 'sedang', 'Absensi buruk dan kinerja rendah', 'Peringatan tertulis + pembinaan', 1, '2026-02-01 05:49:31'),
(2, 5, 5, '2026-02-01', 0.543211, 'berat', 'Kinerja sangat rendah dan absensi tinggi', 'Penundaan kenaikan gaji 6 bulan', 1, '2026-02-01 05:49:31'),
(3, 9, 9, '2026-02-01', 0.554434, 'ringan', 'Absensi cukup tinggi', 'Pembinaan dan monitoring', 1, '2026-02-01 05:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `reward`
--

CREATE TABLE `reward` (
  `id` int(11) NOT NULL,
  `karyawan_id` int(11) NOT NULL,
  `penilaian_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `topsis_score` decimal(10,6) NOT NULL,
  `level` enum('sangat_baik','baik','cukup') NOT NULL,
  `jenis_reward` varchar(100) DEFAULT NULL,
  `nilai_reward` decimal(15,2) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `diberikan_oleh` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward`
--

INSERT INTO `reward` (`id`, `karyawan_id`, `penilaian_id`, `tanggal`, `topsis_score`, `level`, `jenis_reward`, `nilai_reward`, `keterangan`, `diberikan_oleh`, `created_at`) VALUES
(1, 1, 1, '2026-02-01', 0.876544, 'sangat_baik', 'Bonus Kinerja', 5000000.00, 'Bonus untuk kinerja excellent', 1, '2026-02-01 05:49:31'),
(2, 3, 3, '2026-02-01', 0.901235, 'sangat_baik', 'Promosi Jabatan', 0.00, 'Promosi ke level supervisor', 1, '2026-02-01 05:49:31'),
(3, 6, 6, '2026-02-01', 0.887767, 'baik', 'Sertifikat Penghargaan', 0.00, 'Sertifikat karyawan teladan', 1, '2026-02-01 05:49:31'),
(4, 10, 10, '2026-02-01', 0.898990, 'sangat_baik', 'Tunjangan Khusus', 2000000.00, 'Tunjangan untuk HRD staff excellent', 1, '2026-02-01 05:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `session_logs`
--

CREATE TABLE `session_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topsis_calculations`
--

CREATE TABLE `topsis_calculations` (
  `id` int(11) NOT NULL,
  `penilaian_id` int(11) NOT NULL,
  `original_kinerja` decimal(10,2) DEFAULT NULL,
  `original_kedisiplinan` decimal(10,2) DEFAULT NULL,
  `original_kerjasama` decimal(10,2) DEFAULT NULL,
  `original_absensi` decimal(10,2) DEFAULT NULL,
  `normalized_kinerja` decimal(10,6) DEFAULT NULL,
  `normalized_kedisiplinan` decimal(10,6) DEFAULT NULL,
  `normalized_kerjasama` decimal(10,6) DEFAULT NULL,
  `normalized_absensi` decimal(10,6) DEFAULT NULL,
  `weighted_kinerja` decimal(10,6) DEFAULT NULL,
  `weighted_kedisiplinan` decimal(10,6) DEFAULT NULL,
  `weighted_kerjasama` decimal(10,6) DEFAULT NULL,
  `weighted_absensi` decimal(10,6) DEFAULT NULL,
  `ideal_positive_kinerja` decimal(10,6) DEFAULT NULL,
  `ideal_positive_kedisiplinan` decimal(10,6) DEFAULT NULL,
  `ideal_positive_kerjasama` decimal(10,6) DEFAULT NULL,
  `ideal_positive_absensi` decimal(10,6) DEFAULT NULL,
  `ideal_negative_kinerja` decimal(10,6) DEFAULT NULL,
  `ideal_negative_kedisiplinan` decimal(10,6) DEFAULT NULL,
  `ideal_negative_kerjasama` decimal(10,6) DEFAULT NULL,
  `ideal_negative_absensi` decimal(10,6) DEFAULT NULL,
  `d_plus` decimal(10,6) DEFAULT NULL,
  `d_minus` decimal(10,6) DEFAULT NULL,
  `preference` decimal(10,6) DEFAULT NULL,
  `ranking` int(11) DEFAULT NULL,
  `category` varchar(20) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topsis_results`
--

CREATE TABLE `topsis_results` (
  `id` int(11) NOT NULL,
  `penilaian_id` int(11) NOT NULL,
  `d_plus` decimal(10,6) NOT NULL,
  `d_minus` decimal(10,6) NOT NULL,
  `preference` decimal(10,6) NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topsis_results`
--

INSERT INTO `topsis_results` (`id`, `penilaian_id`, `d_plus`, `d_minus`, `preference`, `calculated_at`) VALUES
(1, 1, 0.123456, 0.876544, 0.876544, '2026-02-01 05:49:31'),
(2, 2, 0.234567, 0.765433, 0.765433, '2026-02-01 05:49:31'),
(3, 3, 0.098765, 0.901235, 0.901235, '2026-02-01 05:49:31'),
(4, 4, 0.345678, 0.654322, 0.654322, '2026-02-01 05:49:31'),
(5, 5, 0.456789, 0.543211, 0.543211, '2026-02-01 05:49:31'),
(6, 6, 0.112233, 0.887767, 0.887767, '2026-02-01 05:49:31'),
(7, 7, 0.223344, 0.776656, 0.776656, '2026-02-01 05:49:31'),
(8, 8, 0.334455, 0.665545, 0.665545, '2026-02-01 05:49:31'),
(9, 9, 0.445566, 0.554434, 0.554434, '2026-02-01 05:49:31'),
(10, 10, 0.101010, 0.898990, 0.898990, '2026-02-01 05:49:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('hrd_admin','direktur','admin','manager','user') DEFAULT 'user',
  `permissions` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama`, `email`, `role`, `permissions`, `foto`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'hrd_admin', 'aHJkX2FkbWluMTIz', 'HRD Administrator', 'hrd@company.com', 'hrd_admin', '{\"read\":true,\"write\":true,\"delete\":true,\"manage_users\":true,\"manage_departments\":true,\"manage_positions\":true,\"manage_employees\":true,\"manage_evaluations\":true,\"manage_rewards\":true,\"manage_punishments\":true,\"generate_reports\":true,\"backup_data\":true}', NULL, '2026-02-01 14:33:51', '2026-02-01 05:49:29', '2026-02-01 07:33:51'),
(2, 'direktur', 'ZGlyZWt0dXIxMjM=', 'Direktur Utama', 'direktur@company.com', 'direktur', '{\"read\":true,\"write\":false,\"delete\":false,\"manage_users\":false,\"manage_departments\":false,\"manage_positions\":false,\"manage_employees\":false,\"manage_evaluations\":false,\"manage_rewards\":false,\"manage_punishments\":false,\"generate_reports\":true,\"backup_data\":false}', NULL, '2026-02-01 14:35:02', '2026-02-01 05:49:29', '2026-02-01 07:35:02'),
(3, 'admin', 'YWRtaW4xMjM=', 'Administrator', 'admin@company.com', 'admin', '{\"read\":true,\"write\":true,\"delete\":false,\"manage_users\":true,\"manage_departments\":true,\"manage_positions\":true,\"manage_employees\":true,\"manage_evaluations\":true,\"manage_rewards\":true,\"manage_punishments\":true,\"generate_reports\":true,\"backup_data\":true}', NULL, '2026-02-02 07:42:19', '2026-02-01 05:49:29', '2026-02-02 00:42:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `backup_log`
--
ALTER TABLE `backup_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `departemen`
--
ALTER TABLE `departemen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD KEY `idx_karyawan_nik` (`nik`),
  ADD KEY `idx_karyawan_nama` (`nama`),
  ADD KEY `idx_karyawan_departemen` (`departemen_id`),
  ADD KEY `idx_karyawan_jabatan` (`jabatan_id`),
  ADD KEY `idx_karyawan_status` (`status`);

--
-- Indexes for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_penilaian_tanggal` (`tanggal_penilaian`),
  ADD KEY `idx_penilaian_karyawan` (`karyawan_id`),
  ADD KEY `idx_penilaian_penilai` (`penilai_id`),
  ADD KEY `idx_topsis_status` (`topsis_status`),
  ADD KEY `idx_topsis_preference` (`topsis_preference`),
  ADD KEY `idx_topsis_category` (`topsis_category`);

--
-- Indexes for table `punishment`
--
ALTER TABLE `punishment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penilaian_id` (`penilaian_id`),
  ADD KEY `diberikan_oleh` (`diberikan_oleh`),
  ADD KEY `idx_punishment_tanggal` (`tanggal`),
  ADD KEY `idx_punishment_karyawan` (`karyawan_id`),
  ADD KEY `idx_punishment_level` (`level`);

--
-- Indexes for table `reward`
--
ALTER TABLE `reward`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penilaian_id` (`penilaian_id`),
  ADD KEY `diberikan_oleh` (`diberikan_oleh`),
  ADD KEY `idx_reward_tanggal` (`tanggal`),
  ADD KEY `idx_reward_karyawan` (`karyawan_id`),
  ADD KEY `idx_reward_level` (`level`);

--
-- Indexes for table `session_logs`
--
ALTER TABLE `session_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_logs_user` (`user_id`),
  ADD KEY `idx_session_logs_action` (`action`),
  ADD KEY `idx_session_logs_created` (`created_at`);

--
-- Indexes for table `topsis_calculations`
--
ALTER TABLE `topsis_calculations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_penilaian_id` (`penilaian_id`),
  ADD KEY `idx_preference` (`preference`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `topsis_results`
--
ALTER TABLE `topsis_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `penilaian_id` (`penilaian_id`),
  ADD KEY `idx_topsis_preference` (`preference`),
  ADD KEY `idx_topsis_penilaian` (`penilaian_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `backup_log`
--
ALTER TABLE `backup_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departemen`
--
ALTER TABLE `departemen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `punishment`
--
ALTER TABLE `punishment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reward`
--
ALTER TABLE `reward`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topsis_calculations`
--
ALTER TABLE `topsis_calculations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topsis_results`
--
ALTER TABLE `topsis_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `backup_log`
--
ALTER TABLE `backup_log`
  ADD CONSTRAINT `backup_log_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD CONSTRAINT `karyawan_ibfk_1` FOREIGN KEY (`departemen_id`) REFERENCES `departemen` (`id`),
  ADD CONSTRAINT `karyawan_ibfk_2` FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan` (`id`);

--
-- Constraints for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD CONSTRAINT `penilaian_ibfk_1` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penilaian_ibfk_2` FOREIGN KEY (`penilai_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `punishment`
--
ALTER TABLE `punishment`
  ADD CONSTRAINT `punishment_ibfk_1` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`),
  ADD CONSTRAINT `punishment_ibfk_2` FOREIGN KEY (`penilaian_id`) REFERENCES `penilaian` (`id`),
  ADD CONSTRAINT `punishment_ibfk_3` FOREIGN KEY (`diberikan_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `reward`
--
ALTER TABLE `reward`
  ADD CONSTRAINT `reward_ibfk_1` FOREIGN KEY (`karyawan_id`) REFERENCES `karyawan` (`id`),
  ADD CONSTRAINT `reward_ibfk_2` FOREIGN KEY (`penilaian_id`) REFERENCES `penilaian` (`id`),
  ADD CONSTRAINT `reward_ibfk_3` FOREIGN KEY (`diberikan_oleh`) REFERENCES `users` (`id`);

--
-- Constraints for table `session_logs`
--
ALTER TABLE `session_logs`
  ADD CONSTRAINT `session_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `topsis_calculations`
--
ALTER TABLE `topsis_calculations`
  ADD CONSTRAINT `topsis_calculations_ibfk_1` FOREIGN KEY (`penilaian_id`) REFERENCES `penilaian` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topsis_results`
--
ALTER TABLE `topsis_results`
  ADD CONSTRAINT `topsis_results_ibfk_1` FOREIGN KEY (`penilaian_id`) REFERENCES `penilaian` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
