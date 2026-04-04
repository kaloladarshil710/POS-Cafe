-- ============================================================
-- POS Cafe - Complete Clean Database (Fixed)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Database: `pos_cafe`
CREATE DATABASE IF NOT EXISTS `pos_cafe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pos_cafe`;

-- --------------------------------------------------------
-- Table: categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`id`, `category_name`, `status`) VALUES
(1, 'Fast Food', 'active'),
(2, 'Beverages', 'active'),
(3, 'Desserts', 'active'),
(4, 'Snacks', 'active');

-- --------------------------------------------------------
-- Table: products (FIXED: added `category` varchar column for backward compatibility)
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT 'Plate',
  `tax` decimal(5,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`id`, `name`, `category_id`, `category`, `price`, `unit`, `tax`, `description`) VALUES
(1, 'Margherita Pizza', 1, 'Fast Food', 299.00, 'Plate', 5.00, 'Classic tomato and mozzarella pizza'),
(2, 'Pasta Alfredo', 1, 'Fast Food', 249.00, 'Plate', 5.00, 'Creamy white sauce pasta'),
(3, 'Veg Burger', 1, 'Fast Food', 149.00, 'Piece', 0.00, 'Fresh veggie burger with lettuce'),
(4, 'Cappuccino', 2, 'Beverages', 99.00, 'Cup', 0.00, 'Hot frothy cappuccino'),
(5, 'Cold Coffee', 2, 'Beverages', 129.00, 'Glass', 0.00, 'Chilled blended coffee'),
(6, 'Chocolate Brownie', 3, 'Desserts', 89.00, 'Piece', 0.00, 'Warm fudgy brownie'),
(7, 'Chicken Burger', 1, 'Fast Food', 189.00, 'Piece', 5.00, 'Grilled chicken burger'),
(8, 'Masala Fries', 4, 'Snacks', 79.00, 'Plate', 0.00, 'Crispy fries with masala seasoning'),
(9, 'Mineral Water', 2, 'Beverages', 30.00, 'Bottle', 0.00, 'Chilled 500ml water bottle');

-- --------------------------------------------------------
-- Table: restaurant_tables
-- --------------------------------------------------------
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` varchar(20) NOT NULL,
  `seats` int(11) NOT NULL,
  `status` enum('free','occupied') DEFAULT 'free',
  `active` enum('yes','no') DEFAULT 'yes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `restaurant_tables` (`id`, `table_number`, `seats`, `status`, `active`) VALUES
(1, 'Table 1', 4, 'free', 'yes'),
(2, 'Table 2', 2, 'free', 'yes'),
(3, 'Table 3', 6, 'free', 'yes'),
(4, 'Table 4', 4, 'free', 'yes'),
(5, 'Table 5', 8, 'free', 'yes'),
(6, 'Table 6', 2, 'free', 'yes');

-- --------------------------------------------------------
-- Table: payment_methods
-- --------------------------------------------------------
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method_name` varchar(50) NOT NULL,
  `is_enabled` enum('yes','no') DEFAULT 'yes',
  `upi_id` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `payment_methods` (`id`, `method_name`, `is_enabled`, `upi_id`) VALUES
(1, 'Cash', 'yes', NULL),
(2, 'Digital', 'yes', NULL),
(3, 'UPI', 'yes', '123@ybl.com');

-- --------------------------------------------------------
-- Table: orders
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `table_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','to_cook','preparing','completed','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_table` (`table_id`),
  KEY `idx_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: order_items
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `item_status` enum('pending','prepared') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payments_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default admin user: email=admin@poscafe.com, password=admin123
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'Admin', 'admin@poscafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

COMMIT;
