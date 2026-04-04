-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 04, 2026 at 10:33 AM
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
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','to_cook','preparing','completed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 'UPI', 'yes', '123@ybl.com', '2026-04-04 05:45:30');

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
(4, 'Cappuccino', 4, 99.00, 'Cup', 0.00, 'Hot frothy cappuccino', '2026-04-04 07:18:38'),
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restaurant_tables`
--

INSERT INTO `restaurant_tables` (`id`, `table_number`, `seats`, `status`, `active`, `created_at`) VALUES
(1, 'Table 1', 4, 'free', 'yes', '2026-04-04 07:18:38'),
(2, 'Table 2', 2, 'free', 'yes', '2026-04-04 07:18:38'),
(3, 'Table 3', 6, 'free', 'yes', '2026-04-04 07:18:38'),
(4, 'Table 4', 4, 'free', 'yes', '2026-04-04 07:18:38'),
(5, 'Table 5', 8, 'free', 'yes', '2026-04-04 07:18:38'),
(6, 'Table 6', 2, 'free', 'yes', '2026-04-04 07:18:38');

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
(1, 'Kalola Darshil', 'kaloladarshil710@gmail.com', '$2y$10$LEsXNKxAUcIkB5kGvZXb6uGMg7oe7OwM0IYrbEmCLCJkc19GFg312', 'admin', '2026-04-04 06:13:01', 'active'),
(2, 'Kalola Darshil', 'kaloladarshil132@gmail.com', '$2y$10$W9Bku5KGDVaRAhyOUBP6wu6AzNtx0jXLlXfme4Br6Du55/4PcCJ3q', 'admin', '2026-04-04 06:41:22', 'active');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
