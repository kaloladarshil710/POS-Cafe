-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2026 at 10:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','to_cook','preparing','completed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `table_id`, `user_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 'ORD11E694113601', 1, 3, 99.00, 'paid', '2026-04-04 09:36:01'),
(2, 'ORDB6D9A5114059', 2, 3, 189.00, 'paid', '2026-04-04 09:40:59'),
(3, 'ORDAC22CD114130', 2, 3, 189.00, 'paid', '2026-04-04 09:41:30'),
(4, 'ORD8BB032120632', 1, 3, 99.00, 'paid', '2026-04-04 10:06:32'),
(5, 'ORDD55925120653', 1, 3, 99.00, 'paid', '2026-04-04 10:06:53'),
(6, 'ORD1BE55D120817', 2, 3, 249.00, 'paid', '2026-04-04 10:08:17'),
(7, 'ORD03DBD8121040', 3, 3, 299.00, 'paid', '2026-04-04 10:10:40'),
(8, 'ORD2BECA2121130', 3, 3, 249.00, 'paid', '2026-04-04 10:11:30'),
(9, 'ORD4544C8121356', 3, 3, 249.00, 'paid', '2026-04-04 10:13:56'),
(10, 'ORD397ECB122419', 2, 3, 149.00, 'paid', '2026-04-04 10:24:19'),
(11, 'ORD03FB12123752', 2, 3, 249.00, 'paid', '2026-04-04 10:37:52'),
(12, 'ORD10C527125753', 1, 3, 9.00, 'paid', '2026-04-04 10:57:53'),
(13, 'ORD9451E8130409', 1, 3, 338.00, 'paid', '2026-04-04 11:04:09'),
(14, 'ORD87E6D9131120', 1, 3, 149.00, 'paid', '2026-04-04 11:11:20'),
(15, 'ORD270631131426', 1, 3, 249.00, 'paid', '2026-04-04 11:14:26'),
(16, 'ORDF99D4D131543', 1, 3, 189.00, 'paid', '2026-04-04 11:15:43'),
(17, 'ORD502D2B132613', 2, 3, 249.00, 'paid', '2026-04-04 11:26:13'),
(18, 'ORD06073C135216', 1, 3, 149.00, 'paid', '2026-04-04 11:52:16'),
(19, 'ORD2984E5135946', 2, 3, 149.00, 'paid', '2026-04-04 11:59:46'),
(20, 'ORDC7339E141124', 2, 3, 149.00, 'paid', '2026-04-04 12:11:24'),
(21, 'ORDB0CC53141211', 1, 3, 338.00, 'paid', '2026-04-04 12:12:11'),
(22, 'ORD82704C141848', 3, 3, 149.00, 'paid', '2026-04-04 12:18:48'),
(23, 'ORD7B509A141903', 3, 3, 149.00, 'paid', '2026-04-04 12:19:03'),
(24, 'ORDC311BA141924', 2, 3, 149.00, 'paid', '2026-04-04 12:19:24'),
(25, 'ORD096E00190448', 1, 4, 338.00, 'paid', '2026-04-04 17:04:48'),
(26, 'ORD328BBC220611', 3, 4, 886.00, 'paid', '2026-04-04 20:06:11'),
(27, 'ORD9AC39A223137', 2, 4, 338.00, 'paid', '2026-04-04 20:31:37');

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
(34, 27, 7, 'Chicken Burger', 1, 189.00, 189.00, '2026-04-04 20:31:37', 'pending');

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
(33, 27, 'Digital', 338.00, 'paid', '2026-04-04 20:31:59');

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
(3, 'UPI', 'yes', '7567521999@sbi', '2026-04-04 05:45:30');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `price`, `unit`, `tax`, `description`, `created_at`) VALUES
(1, 'Margherita Pizza', 1, 299.00, 'Plate', 5.00, 'Classic tomato and mozzarella pizza', '2026-04-04 07:18:38'),
(2, 'Pasta Alfredo', 2, 249.00, 'Plate', 5.00, 'Creamy white sauce pasta', '2026-04-04 07:18:38'),
(3, 'Veg Burger', 3, 149.00, 'Piece', 0.00, 'Fresh veggie burger with lettuce', '2026-04-04 07:18:38'),
(4, 'Cappuccino', 4, 9.00, 'Cup', 0.00, 'Hot frothy cappuccino', '2026-04-04 07:18:38'),
(5, 'Cold Coffee', 4, 129.00, 'Glass', 0.00, 'Chilled blended coffee', '2026-04-04 07:18:38'),
(7, 'Chicken Burger', 3, 189.00, 'Piece', 5.00, 'Grilled chicken burger', '2026-04-04 07:18:38');

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
(4, 'jay patel', 'jaylimbasiya987@gmail.com', '$2y$10$4wNJXnf86B2R6PiQ.u9nAegUW.Zf9d0JaHERrhZ7IZAhKvIZoHW1q', 'admin', '2026-04-04 15:54:23', 'active');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `restaurant_tables`
--
ALTER TABLE `restaurant_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
