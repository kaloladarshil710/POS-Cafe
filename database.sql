-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 05, 2026 at 06:00 AM
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
-- Database: `pos_cafe`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `status`, `created_at`) VALUES
(1, 'Fast Food', 'active', '2026-04-04 07:49:55'),
(2, 'Beverages', 'active', '2026-04-04 07:49:55'),
(3, 'Desserts', 'active', '2026-04-04 07:49:55'),
(4, 'Snacks', 'active', '2026-04-04 07:49:55');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `table_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','to_cook','preparing','completed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_source` enum('staff','customer') DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `table_id`, `user_id`, `total_amount`, `status`, `created_at`, `order_source`) VALUES
(1, 'ORD11E694113601', 1, 3, 99.00, 'paid', '2026-04-04 09:36:01', 'staff'),
(2, 'ORDB6D9A5114059', 2, 3, 189.00, 'paid', '2026-04-04 09:40:59', 'staff'),
(3, 'ORDAC22CD114130', 2, 3, 189.00, 'paid', '2026-04-04 09:41:30', 'staff'),
(4, 'ORD8BB032120632', 1, 3, 99.00, 'paid', '2026-04-04 10:06:32', 'staff'),
(5, 'ORDD55925120653', 1, 3, 99.00, 'paid', '2026-04-04 10:06:53', 'staff'),
(6, 'ORD1BE55D120817', 2, 3, 249.00, 'paid', '2026-04-04 10:08:17', 'staff'),
(7, 'ORD03DBD8121040', 3, 3, 299.00, 'paid', '2026-04-04 10:10:40', 'staff'),
(8, 'ORD2BECA2121130', 3, 3, 249.00, 'paid', '2026-04-04 10:11:30', 'staff'),
(9, 'ORD4544C8121356', 3, 3, 249.00, 'paid', '2026-04-04 10:13:56', 'staff'),
(10, 'ORD397ECB122419', 2, 3, 149.00, 'paid', '2026-04-04 10:24:19', 'staff'),
(11, 'ORD03FB12123752', 2, 3, 249.00, 'paid', '2026-04-04 10:37:52', 'staff'),
(12, 'ORD10C527125753', 1, 3, 9.00, 'paid', '2026-04-04 10:57:53', 'staff'),
(13, 'ORD9451E8130409', 1, 3, 338.00, 'paid', '2026-04-04 11:04:09', 'staff'),
(14, 'ORD87E6D9131120', 1, 3, 149.00, 'paid', '2026-04-04 11:11:20', 'staff'),
(15, 'ORD270631131426', 1, 3, 249.00, 'paid', '2026-04-04 11:14:26', 'staff'),
(16, 'ORDF99D4D131543', 1, 3, 189.00, 'paid', '2026-04-04 11:15:43', 'staff'),
(17, 'ORD502D2B132613', 2, 3, 249.00, 'paid', '2026-04-04 11:26:13', 'staff'),
(18, 'ORD06073C135216', 1, 3, 149.00, 'paid', '2026-04-04 11:52:16', 'staff'),
(19, 'ORD2984E5135946', 2, 3, 149.00, 'paid', '2026-04-04 11:59:46', 'staff'),
(20, 'ORDC7339E141124', 2, 3, 149.00, 'paid', '2026-04-04 12:11:24', 'staff'),
(21, 'ORDB0CC53141211', 1, 3, 338.00, 'paid', '2026-04-04 12:12:11', 'staff'),
(22, 'ORD82704C141848', 3, 3, 149.00, 'paid', '2026-04-04 12:18:48', 'staff'),
(23, 'ORD7B509A141903', 3, 3, 149.00, 'paid', '2026-04-04 12:19:03', 'staff'),
(24, 'ORDC311BA141924', 2, 3, 149.00, 'paid', '2026-04-04 12:19:24', 'staff'),
(25, 'ORD096E00190448', 1, 4, 338.00, 'paid', '2026-04-04 17:04:48', 'staff'),
(26, 'ORD328BBC220611', 3, 4, 886.00, 'paid', '2026-04-04 20:06:11', 'staff'),
(27, 'ORD9AC39A223137', 2, 4, 338.00, 'paid', '2026-04-04 20:31:37', 'staff'),
(28, 'ORDD9EA2E043421', 2, 2, 417.00, 'paid', '2026-04-05 02:34:21', 'staff'),
(29, 'QRC6C82A043836', 1, NULL, 247.00, 'paid', '2026-04-05 02:38:36', 'staff'),
(30, 'QR1D067E044225', 1, NULL, 247.00, 'paid', '2026-04-05 02:42:25', 'customer'),
(31, 'QR2AFDF9044802', 1, NULL, 247.00, 'paid', '2026-04-05 02:48:02', 'customer'),
(32, 'QRC6035C045556', 1, NULL, 39.00, 'paid', '2026-04-05 02:55:56', 'customer'),
(33, 'ORD866296045624', 2, 2, 249.00, 'paid', '2026-04-05 02:56:24', 'staff'),
(34, 'QR764E0B050055', 1, NULL, 168.00, 'paid', '2026-04-05 03:00:55', 'customer'),
(35, 'QRA1A4FB051418', 1, NULL, 168.00, 'paid', '2026-04-05 03:14:18', 'customer'),
(36, 'ORD0C4BFE053336', 3, 2, 288.00, 'paid', '2026-04-05 03:33:36', 'staff'),
(37, 'QR74AC70053359', 1, NULL, 168.00, 'paid', '2026-04-05 03:33:59', 'customer'),
(38, 'QRTEST001', 1, NULL, 168.00, 'paid', '2026-04-05 03:37:42', 'customer'),
(39, 'QRTEST002', 1, NULL, 247.00, 'paid', '2026-04-05 03:37:42', 'customer'),
(40, 'QRTEST003', 2, NULL, 149.00, 'paid', '2026-04-05 03:37:42', 'customer'),
(41, 'QRTEST004', 3, NULL, 299.00, 'paid', '2026-04-05 03:37:42', 'customer'),
(42, 'QRTEST001', 1, NULL, 168.00, 'paid', '2026-04-05 03:39:29', 'customer'),
(43, 'QRTEST002', 1, NULL, 247.00, 'paid', '2026-04-05 03:39:29', 'customer'),
(44, 'QRTEST003', 2, NULL, 149.00, 'paid', '2026-04-05 03:39:29', 'customer'),
(45, 'QRTEST004', 3, NULL, 299.00, 'paid', '2026-04-05 03:39:29', 'customer'),
(46, 'QRLIVE001', 1, NULL, 168.00, 'paid', '2026-04-05 03:41:50', 'customer'),
(47, 'QRLIVE002', 1, NULL, 247.00, 'paid', '2026-04-05 03:41:50', 'customer'),
(48, 'QRLIVE003', 2, NULL, 149.00, 'paid', '2026-04-05 03:41:50', 'customer'),
(49, 'QRLIVE004', 2, NULL, 299.00, 'paid', '2026-04-05 03:41:50', 'customer'),
(50, 'QRLIVE005', 3, NULL, 129.00, 'paid', '2026-04-05 03:41:50', 'customer'),
(51, 'QRLIVE006', 3, NULL, 99.00, 'paid', '2026-04-05 03:41:50', 'customer'),
(52, 'QRLIVE007', 4, NULL, 249.00, 'paid', '2026-04-05 03:41:50', 'customer'),
(53, 'QRLIVE008', 5, NULL, 349.00, 'paid', '2026-04-05 03:41:50', 'customer'),
(54, 'ORD04E014055040', 2, 5, 168.00, 'paid', '2026-04-05 03:50:40', 'staff'),
(55, 'ORDA05161055138', 1, 5, 188.00, 'paid', '2026-04-05 03:51:38', 'staff'),
(56, 'ORD2EC3E4055218', 2, 5, 158.00, 'paid', '2026-04-05 03:52:18', 'staff'),
(57, 'ORD078054055232', 3, 5, 298.00, 'paid', '2026-04-05 03:52:32', 'staff'),
(58, 'QR9A8404055433', 1, NULL, 208.00, 'paid', '2026-04-05 03:54:33', 'customer'),
(59, 'QRB01EEC055523', 1, NULL, 278.00, 'paid', '2026-04-05 03:55:23', 'customer'),
(60, 'ORD85ECBD055936', 4, 5, 288.00, 'paid', '2026-04-05 03:59:36', 'staff');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `item_status` enum('pending','prepared') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`, `created_at`, `item_status`) VALUES
(1, 1, 4, 'Cappuccino', 1, 99.00, 99.00, '2026-04-04 09:36:01', 'pending'),
(2, 2, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 09:40:59', 'pending'),
(3, 3, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 09:41:30', 'pending'),
(4, 4, 4, 'Cappuccino', 1, 99.00, 99.00, '2026-04-04 10:06:32', 'prepared'),
(5, 5, 4, 'Cappuccino', 1, 99.00, 99.00, '2026-04-04 10:06:53', 'prepared'),
(6, 6, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-04 10:08:17', 'prepared'),
(7, 7, 1, 'Margherita Pizza', 1, 299.00, 299.00, '2026-04-04 10:10:40', 'prepared'),
(8, 8, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-04 10:11:30', 'pending'),
(9, 9, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-04 10:13:56', 'pending'),
(10, 10, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 10:24:19', 'pending'),
(11, 11, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-04 10:37:52', 'pending'),
(12, 12, 4, 'Cappuccino', 1, 9.00, 9.00, '2026-04-04 10:57:53', 'pending'),
(13, 13, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 11:04:09', 'prepared'),
(14, 13, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 11:04:09', 'pending'),
(15, 14, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 11:11:20', 'prepared'),
(16, 15, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-04 11:14:26', 'prepared'),
(17, 16, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 11:15:43', 'pending'),
(18, 17, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-04 11:26:13', 'pending'),
(19, 18, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 11:52:16', 'pending'),
(20, 19, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 11:59:46', 'pending'),
(21, 20, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 12:11:24', 'pending'),
(22, 21, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 12:12:11', 'pending'),
(23, 21, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 12:12:11', 'pending'),
(24, 22, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 12:18:48', 'pending'),
(25, 23, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 12:19:03', 'pending'),
(26, 24, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 12:19:24', 'pending'),
(27, 25, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 17:04:48', 'prepared'),
(28, 25, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 17:04:48', 'prepared'),
(29, 26, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 20:06:11', 'pending'),
(30, 26, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 20:06:11', 'pending'),
(31, 26, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-04 20:06:11', 'pending'),
(32, 26, 1, 'Margherita Pizza', 1, 299.00, 299.00, '2026-04-04 20:06:11', 'pending'),
(33, 27, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-04 20:31:37', 'pending'),
(34, 27, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 20:31:37', 'pending'),
(35, 28, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 02:34:21', 'pending'),
(36, 28, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 02:34:21', 'pending'),
(37, 28, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-05 02:34:21', 'pending'),
(38, 30, 15, 'Fresh Lime Soda', 2, 59.00, 118.00, '2026-04-05 02:42:25', 'pending'),
(39, 30, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 02:42:25', 'pending'),
(40, 31, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 02:48:02', 'pending'),
(41, 31, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 02:48:02', 'pending'),
(42, 31, 16, 'Gulab Jamun', 1, 79.00, 79.00, '2026-04-05 02:48:02', 'pending'),
(43, 32, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 02:55:56', 'pending'),
(44, 33, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-05 02:56:24', 'pending'),
(45, 34, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:00:55', 'pending'),
(46, 34, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:00:55', 'pending'),
(47, 35, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:14:18', 'pending'),
(48, 35, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:14:18', 'pending'),
(49, 36, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-05 03:33:36', 'pending'),
(50, 36, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:33:36', 'pending'),
(51, 37, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:33:59', 'pending'),
(52, 37, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:33:59', 'pending'),
(53, 38, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:39:59', 'pending'),
(54, 38, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:39:59', 'pending'),
(55, 39, 15, 'Fresh Lime Soda', 2, 59.00, 118.00, '2026-04-05 03:39:59', 'pending'),
(56, 39, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:39:59', 'pending'),
(57, 40, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-05 03:39:59', 'prepared'),
(58, 41, 1, 'Margherita Pizza', 1, 299.00, 299.00, '2026-04-05 03:39:59', 'prepared'),
(59, 38, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:41:50', 'pending'),
(60, 38, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:41:50', 'pending'),
(61, 39, 15, 'Fresh Lime Soda', 2, 59.00, 118.00, '2026-04-05 03:41:50', 'pending'),
(62, 39, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:41:50', 'pending'),
(63, 40, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-05 03:41:50', 'prepared'),
(64, 41, 1, 'Margherita Pizza', 1, 299.00, 299.00, '2026-04-05 03:41:50', 'prepared'),
(65, 42, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:41:50', 'pending'),
(66, 43, 18, 'Samosa', 3, 29.00, 87.00, '2026-04-05 03:41:50', 'pending'),
(67, 44, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-05 03:41:50', 'prepared'),
(68, 45, 19, 'Paneer Pizza', 1, 349.00, 349.00, '2026-04-05 03:41:50', 'prepared'),
(69, 54, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:50:40', 'pending'),
(70, 54, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:50:40', 'pending'),
(71, 55, 20, 'Chocolate Shake', 1, 149.00, 149.00, '2026-04-05 03:51:38', 'pending'),
(72, 55, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:51:38', 'pending'),
(73, 56, 16, 'Gulab Jamun', 2, 79.00, 158.00, '2026-04-05 03:52:18', 'pending'),
(74, 57, 3, 'Veg Burger', 1, 149.00, 149.00, '2026-04-05 03:52:32', 'pending'),
(75, 57, 20, 'Chocolate Shake', 1, 149.00, 149.00, '2026-04-05 03:52:32', 'pending'),
(76, 58, 20, 'Chocolate Shake', 1, 149.00, 149.00, '2026-04-05 03:54:33', 'pending'),
(77, 58, 15, 'Fresh Lime Soda', 1, 59.00, 59.00, '2026-04-05 03:54:33', 'prepared'),
(78, 59, 20, 'Chocolate Shake', 1, 149.00, 149.00, '2026-04-05 03:55:23', 'pending'),
(79, 59, 13, 'Mango Shake', 1, 129.00, 129.00, '2026-04-05 03:55:23', 'pending'),
(80, 60, 2, 'Pasta Alfredo', 1, 249.00, 249.00, '2026-04-05 03:59:36', 'pending'),
(81, 60, 14, 'Masala Tea', 1, 39.00, 39.00, '2026-04-05 03:59:36', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_method`, `amount`, `status`, `created_at`) VALUES
(1, 1, 'Cash', 99.00, 'paid', '2026-04-04 09:40:14'),
(2, 2, 'Cash', 189.00, 'paid', '2026-04-04 09:41:15'),
(3, 3, 'Cash', 189.00, 'paid', '2026-04-04 10:05:03'),
(4, 8, 'Cash', 249.00, 'paid', '2026-04-04 10:11:40'),
(5, 4, 'Cash', 99.00, 'paid', '2026-04-04 10:57:19'),
(6, 7, 'Cash', 299.00, 'paid', '2026-04-04 10:57:28'),
(7, 6, 'Cash', 249.00, 'paid', '2026-04-04 10:57:35'),
(8, 5, 'Cash', 99.00, 'paid', '2026-04-04 10:57:40'),
(9, 12, 'UPI', 9.00, 'paid', '2026-04-04 11:01:54'),
(10, 10, 'Cash', 149.00, 'paid', '2026-04-04 11:15:53'),
(11, 18, 'Cash', 149.00, 'paid', '2026-04-04 11:57:27'),
(12, 11, 'Cash', 249.00, 'paid', '2026-04-04 11:57:33'),
(13, 17, 'Cash', 249.00, 'paid', '2026-04-04 11:57:33'),
(14, 11, 'Cash', 249.00, 'paid', '2026-04-04 11:58:54'),
(15, 17, 'Cash', 249.00, 'paid', '2026-04-04 11:58:54'),
(16, 11, 'Cash', 249.00, 'paid', '2026-04-04 11:58:57'),
(17, 17, 'Cash', 249.00, 'paid', '2026-04-04 11:58:57'),
(18, 11, 'Cash', 249.00, 'paid', '2026-04-04 11:59:36'),
(19, 17, 'Cash', 249.00, 'paid', '2026-04-04 11:59:36'),
(20, 19, 'Cash', 149.00, 'paid', '2026-04-04 12:00:21'),
(21, 20, 'Cash', 149.00, 'paid', '2026-04-04 12:32:14'),
(22, 24, 'Cash', 149.00, 'paid', '2026-04-04 12:32:14'),
(23, 9, 'UPI', 249.00, 'paid', '2026-04-04 12:32:46'),
(24, 22, 'UPI', 149.00, 'paid', '2026-04-04 12:32:46'),
(25, 23, 'UPI', 149.00, 'paid', '2026-04-04 12:32:46'),
(26, 13, 'UPI', 338.00, 'paid', '2026-04-04 12:39:30'),
(27, 14, 'UPI', 149.00, 'paid', '2026-04-04 12:39:30'),
(28, 15, 'UPI', 249.00, 'paid', '2026-04-04 12:39:30'),
(29, 16, 'UPI', 189.00, 'paid', '2026-04-04 12:39:30'),
(30, 21, 'UPI', 338.00, 'paid', '2026-04-04 12:39:30'),
(31, 25, 'UPI', 338.00, 'paid', '2026-04-04 17:05:16'),
(32, 26, 'UPI', 886.00, 'paid', '2026-04-04 20:06:24'),
(33, 27, 'Digital', 338.00, 'paid', '2026-04-04 20:31:59'),
(34, 28, 'UPI', 417.00, 'paid', '2026-04-05 02:55:26'),
(35, 33, 'UPI', 249.00, 'paid', '2026-04-05 02:56:33'),
(36, 36, 'UPI', 288.00, 'paid', '2026-04-05 03:33:50'),
(37, 40, 'UPI', 149.00, 'paid', '2026-04-05 03:40:21'),
(38, 41, 'Cash', 299.00, 'paid', '2026-04-05 03:40:21'),
(39, 40, 'UPI', 149.00, 'paid', '2026-04-05 03:41:50'),
(40, 41, 'Cash', 299.00, 'paid', '2026-04-05 03:41:50'),
(41, 44, 'Digital', 249.00, 'paid', '2026-04-05 03:41:50'),
(42, 37, 'Cash', 168.00, 'paid', '2026-04-05 03:49:25'),
(43, 38, 'Cash', 168.00, 'paid', '2026-04-05 03:49:25'),
(44, 39, 'Cash', 247.00, 'paid', '2026-04-05 03:49:25'),
(45, 42, 'Cash', 168.00, 'paid', '2026-04-05 03:49:25'),
(46, 43, 'Cash', 247.00, 'paid', '2026-04-05 03:49:25'),
(47, 46, 'Cash', 168.00, 'paid', '2026-04-05 03:49:25'),
(48, 47, 'Cash', 247.00, 'paid', '2026-04-05 03:49:25'),
(49, 40, 'Cash', 149.00, 'paid', '2026-04-05 03:49:31'),
(50, 44, 'Cash', 149.00, 'paid', '2026-04-05 03:49:31'),
(51, 48, 'Cash', 149.00, 'paid', '2026-04-05 03:49:31'),
(52, 49, 'Cash', 299.00, 'paid', '2026-04-05 03:49:31'),
(53, 41, 'Cash', 299.00, 'paid', '2026-04-05 03:49:34'),
(54, 45, 'Cash', 299.00, 'paid', '2026-04-05 03:49:34'),
(55, 50, 'Cash', 129.00, 'paid', '2026-04-05 03:49:34'),
(56, 51, 'Cash', 99.00, 'paid', '2026-04-05 03:49:34'),
(57, 53, 'UPI', 349.00, 'paid', '2026-04-05 03:50:07'),
(58, 52, 'Cash', 249.00, 'paid', '2026-04-05 03:50:12'),
(59, 54, 'UPI', 168.00, 'paid', '2026-04-05 03:50:50'),
(60, 55, 'UPI', 188.00, 'paid', '2026-04-05 03:51:57'),
(61, 56, 'UPI', 158.00, 'paid', '2026-04-05 03:53:40'),
(62, 57, 'UPI', 298.00, 'paid', '2026-04-05 03:57:05'),
(63, 60, 'Digital', 288.00, 'paid', '2026-04-05 03:59:54');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `method_name` varchar(50) NOT NULL,
  `is_enabled` enum('yes','no') DEFAULT 'yes',
  `upi_id` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `method_name`, `is_enabled`, `upi_id`, `created_at`) VALUES
(1, 'Cash', 'yes', NULL, '2026-04-04 05:45:30'),
(2, 'Digital', 'yes', NULL, '2026-04-04 05:45:30'),
(3, 'UPI', 'yes', '7567521990@sbi', '2026-04-04 05:45:30');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT 'Plate',
  `tax` decimal(5,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `price`, `unit`, `tax`, `description`, `image`, `created_at`) VALUES
(1, 'Margherita Pizza', 1, 299.00, 'Plate', 5.00, 'Classic tomato and mozzarella pizza', 'pizaa-combo.webp', '2026-04-04 07:18:38'),
(2, 'Pasta Alfredo', 2, 249.00, 'Plate', 5.00, 'Creamy white sauce pasta', NULL, '2026-04-04 07:18:38'),
(3, 'Veg Burger', 3, 149.00, 'Piece', 0.00, 'Fresh veggie burger with lettuce', 'burger.jpg', '2026-04-04 07:18:38'),
(4, 'Cappuccino', 4, 9.00, 'Cup', 0.00, 'Hot frothy cappuccino', 'hot-coffee.jpg', '2026-04-04 07:18:38'),
(5, 'Cold Coffee', 4, 129.00, 'Glass', 0.00, 'Chilled blended coffee', 'cold-coffee.jpg', '2026-04-04 07:18:38'),
(7, 'Chicken Burger', 3, 189.00, 'Piece', 5.00, 'Grilled chicken burger', 'burger.jpg', '2026-04-04 07:18:38'),
(9, 'Vada Pav', 1, 49.00, 'Piece', 0.00, 'Mumbai style spicy vada in soft pav', 'vadapav.jpg', '2026-04-04 21:39:24'),
(10, 'Club Sandwich', 1, 149.00, 'Plate', 5.00, 'Toasted triple-decker sandwich with veggies', 'clubsandwich.jpg', '2026-04-04 21:39:24'),
(11, 'Aloo Paratha', 1, 99.00, 'Plate', 0.00, 'Crispy stuffed flatbread with pickle & curd', 'aloo-paratha.jpg', '2026-04-04 21:39:24'),
(12, 'Frankie Roll', 1, 119.00, 'Piece', 0.00, 'Spicy veggie roll wrapped in soft roti', 'frankieroll.jpg', '2026-04-04 21:39:24'),
(13, 'Mango Shake', 2, 129.00, 'Glass', 0.00, 'Thick and creamy fresh mango milkshake', 'mangoshake.jpg', '2026-04-04 21:39:24'),
(14, 'Masala Tea', 2, 39.00, 'Cup', 0.00, 'Aromatic Indian spiced ginger tea', 'masala-tea.jpg', '2026-04-04 21:39:24'),
(15, 'Fresh Lime Soda', 2, 59.00, 'Glass', 0.00, 'Chilled lime soda, sweet or salted', 'freshlime.webp', '2026-04-04 21:39:24'),
(16, 'Gulab Jamun', 3, 79.00, 'Plate', 0.00, 'Soft milk dumplings in rose sugar syrup', 'gulab-jamun.jpg', '2026-04-04 21:39:24'),
(17, 'Brownie', 3, 99.00, 'Piece', 0.00, 'Warm chocolate brownie with vanilla drizzle', 'brownie.jpg', '2026-04-04 21:39:24'),
(18, 'Samosa', 4, 29.00, 'Piece', 0.00, 'Crispy pastry filled with spiced potatoes', 'samosa.jpg', '2026-04-04 21:39:24'),
(19, 'Paneer Pizza', 1, 349.00, 'Plate', 5.00, 'Loaded paneer pizza', 'pizaa-combo.webp', '2026-04-05 03:41:50'),
(20, 'Chocolate Shake', 2, 149.00, 'Glass', 0.00, 'Rich chocolate shake', 'mangoshake.jpg', '2026-04-05 03:41:50'),
(21, 'French Fries', 4, 99.00, 'Plate', 0.00, 'Crispy salted fries', 'samosa.jpg', '2026-04-05 03:41:50'),
(22, 'Cheese Sandwich', 1, 129.00, 'Plate', 5.00, 'Grilled cheese sandwich', 'clubsandwich.jpg', '2026-04-05 03:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_tables`
--

CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL,
  `table_number` varchar(20) NOT NULL,
  `seats` int(11) NOT NULL,
  `status` enum('free','occupied') DEFAULT 'free',
  `active` enum('yes','no') DEFAULT 'yes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `occupied_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `table_number`, `seats`, `status`, `active`, `created_at`, `occupied_at`) VALUES
(1, 'Table 1', 4, 'free', 'yes', '2026-04-04 07:18:38', NULL),
(2, 'Table 2', 2, 'free', 'yes', '2026-04-04 07:18:38', NULL),
(3, 'Table 3', 6, 'free', 'yes', '2026-04-04 07:18:38', NULL),
(4, 'Table 4', 4, 'free', 'yes', '2026-04-04 07:18:38', NULL),
(5, 'Table 5', 8, 'free', 'yes', '2026-04-04 07:18:38', NULL),
(6, 'Table 6', 2, 'free', 'yes', '2026-04-04 07:18:38', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `status`) VALUES
(2, 'Kalola Darshil', 'kaloladarshil132@gmail.com', '$2y$10$W9Bku5KGDVaRAhyOUBP6wu6AzNtx0jXLlXfme4Br6Du55/4PcCJ3q', 'admin', '2026-04-04 06:41:22', 'active'),
(3, 'jj', 'jj@gmail.com', '$2y$10$ZrrvpQzHZFZgWRuN7NElWeuKpXJCVeEow3a8Y6W6qqFAw26.xK5oq', 'staff', '2026-04-04 09:34:33', 'active'),
(4, 'jay patel', 'jaylimbasiya987@gmail.com', '$2y$10$4wNJXnf86B2R6PiQ.u9nAegUW.Zf9d0JaHERrhZ7IZAhKvIZoHW1q', 'admin', '2026-04-04 15:54:23', 'active'),
(5, 'Kalola Darshil', 'kaloladarshil7@gmail.com', '$2y$10$pFNBjtCoan1NA1y5NLDHT.cA1HQdqqBVIwwlThuco1toBdnCS5izK', 'staff', '2026-04-05 03:11:36', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
