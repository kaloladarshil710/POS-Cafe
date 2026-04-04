-- ============================================================
-- POS Cafe - Complete Database Setup
-- ============================================================

CREATE DATABASE IF NOT EXISTS pos_cafe;
USE pos_cafe;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') DEFAULT 'admin',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) DEFAULT 'Plate',
    tax DECIMAL(5,2) DEFAULT 0.00,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Restaurant Tables
CREATE TABLE IF NOT EXISTS restaurant_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(20) NOT NULL,
    seats INT NOT NULL,
    status ENUM('free','occupied') DEFAULT 'free',
    active ENUM('yes','no') DEFAULT 'yes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment Methods Table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) NOT NULL,
    is_enabled ENUM('yes','no') DEFAULT 'yes',
    upi_id VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL,
    table_id INT NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','to_cook','preparing','completed','paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(120) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    item_status ENUM('pending','prepared') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid') DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Default Data
-- ============================================================

-- Default Payment Methods
INSERT IGNORE INTO payment_methods (id, method_name, is_enabled, upi_id) VALUES
(1, 'Cash', 'yes', NULL),
(2, 'Digital', 'yes', NULL),
(3, 'UPI', 'yes', '123@ybl.com');

-- Default Admin User (password: admin123)
INSERT IGNORE INTO users (id, name, email, password, role, status) VALUES
(1, 'Admin User', 'admin@poscafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Sample Tables
INSERT IGNORE INTO restaurant_tables (table_number, seats, status, active) VALUES
('Table 1', 4, 'free', 'yes'),
('Table 2', 2, 'free', 'yes'),
('Table 3', 6, 'free', 'yes'),
('Table 4', 4, 'free', 'yes'),
('Table 5', 8, 'free', 'yes'),
('Table 6', 2, 'free', 'yes');

-- Sample Products
INSERT IGNORE INTO products (name, category, price, unit, tax, description) VALUES
('Margherita Pizza', 'Pizza', 299.00, 'Plate', 5.00, 'Classic tomato and mozzarella pizza'),
('Pasta Alfredo', 'Pasta', 249.00, 'Plate', 5.00, 'Creamy white sauce pasta'),
('Veg Burger', 'Burger', 149.00, 'Piece', 0.00, 'Fresh veggie burger with lettuce'),
('Cappuccino', 'Coffee', 99.00, 'Cup', 0.00, 'Hot frothy cappuccino'),
('Cold Coffee', 'Coffee', 129.00, 'Glass', 0.00, 'Chilled blended coffee'),
('Mineral Water', 'Beverages', 30.00, 'Bottle', 0.00, '1L mineral water bottle'),
('Chicken Burger', 'Burger', 189.00, 'Piece', 5.00, 'Grilled chicken burger'),
('Paneer Tikka', 'Starters', 220.00, 'Plate', 5.00, 'Tandoori paneer tikka with chutney');