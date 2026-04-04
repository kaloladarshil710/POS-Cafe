-- POS Cafe Full Database Schema (Core PHP + Bootstrap POS)
-- Run: DROP DATABASE pos_cafe; CREATE DATABASE pos_cafe; then import this file

DROP DATABASE IF EXISTS pos_cafe;
CREATE DATABASE pos_cafe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_cafe;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Users (Admin/Staff)
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Categories
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL UNIQUE,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Products (with images)
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT 'default-product.jpg',
  `unit` varchar(50) DEFAULT 'Plate',
  `tax` decimal(5,2) DEFAULT 0.00,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Restaurant Tables
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` varchar(20) NOT NULL UNIQUE,
  `qr_token` varchar(32) DEFAULT NULL UNIQUE,
  `seats` int(11) NOT NULL,
  `status` enum('free','occupied') DEFAULT 'free',
  `active` enum('yes','no') DEFAULT 'yes',
  `occupied_since` timestamp NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- Customer Sessions (QR ordering)
CREATE TABLE `customer_sessions` (
  `id` varchar(64) NOT NULL PRIMARY KEY,
  `table_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Orders
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL UNIQUE,
  `session_id` varchar(64),
  `table_id` int(11) NOT NULL,
  `staff_id` int(11) NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','preparing','ready','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`session_id`) REFERENCES `customer_sessions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Order Items
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` text,
  `status` enum('pending','prepared') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Payments
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `method_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed') DEFAULT 'pending',
  `transaction_id` varchar(100),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`method_id`) REFERENCES `payment_methods`(`id`)
) ENGINE=InnoDB;

-- Payment Methods
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `upi_id` varchar(120),
  `qr_image` varchar(255),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- SAMPLE DATA
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin User', 'admin@poscafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Staff User', 'staff@poscafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

INSERT INTO `categories` (`name`) VALUES ('Starters'), ('Main Course'), ('Beverages'), ('Desserts');

INSERT INTO `products` (`name`, `category_id`, `price`, `image`, `description`) VALUES
('Garlic Bread', 1, 149.00, 'garlic-bread.jpg', 'Crispy garlic bread'),
('Veg Pizza', 2, 299.00, 'pizza.jpg', 'Fresh veg pizza'),
('Cold Coffee', 3, 129.00, 'cold-coffee.jpg', 'Iced blended coffee'),
('Ice Cream', 4, 99.00, 'ice-cream.jpg', 'Vanilla scoop');

INSERT INTO `restaurant_tables` (`table_number`, `seats`, `qr_token`) VALUES
('Table 1', 4, 'qr_table1_abc123'),
('Table 2', 6, 'qr_table2_def456'),
('Table 3', 4, 'qr_table3_ghi789'),
('Table 4', 8, 'qr_table4_jkl012');

INSERT INTO `payment_methods` (`name`, `upi_id`, `qr_image`) VALUES
('Cash', NULL, NULL),
('UPI', 'poscafe@paytm', 'upi-qr.jpg'),
('Card', NULL, NULL);

COMMIT;
