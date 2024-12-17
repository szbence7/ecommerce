-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 17, 2024 at 05:19 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce`
--

-- Először töröljük a gyerek táblákat
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `product_translations`;
DROP TABLE IF EXISTS `category_translations`;
DROP TABLE IF EXISTS `translations`;

-- Aztán a szülő táblákat
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `exchange_rates`;
DROP TABLE IF EXISTS `languages`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Electronics', 'Electronic devices and gadgets', '2024-12-16 15:23:58'),
(2, 'Clothing', 'Fashion and apparel', '2024-12-16 15:23:58'),
(3, 'Books', 'Books and literature', '2024-12-16 15:23:58'),
(4, 'Home & Garden', 'Home decoration and garden tools', '2024-12-16 15:23:58');

-- --------------------------------------------------------

--
-- Table structure for table `category_translations`
--

CREATE TABLE `category_translations` (
  `category_id` int(11) NOT NULL,
  `language_code` varchar(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `category_translations`
--

INSERT INTO `category_translations` (`category_id`, `language_code`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'hu', 'Elektronika', 'Elektronikai eszközök és kütyük', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(1, 'en', 'Electronics', 'Electronic devices and gadgets', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(2, 'hu', 'Ruházat', 'Divat és öltözködés', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(2, 'en', 'Clothing', 'Fashion and apparel', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(3, 'hu', 'Könyvek', 'Könyvek és irodalom', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(3, 'en', 'Books', 'Books and literature', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(4, 'hu', 'Otthon és Kert', 'Lakberendezés és kerti eszközök', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(4, 'en', 'Home & Garden', 'Home decoration and garden tools', '2024-12-17 14:50:44', '2024-12-17 14:50:44');

-- --------------------------------------------------------

--
-- Table structure for table `exchange_rates`
--

CREATE TABLE `exchange_rates` (
  `currency` varchar(3) NOT NULL,
  `rate` decimal(10,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exchange_rates`
--

INSERT INTO `exchange_rates` (`currency`, `rate`) VALUES
('GBP', 0.8600),
('HUF', 410.0000),
('USD', 1.0800);

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(11) NOT NULL,
  `code` varchar(5) NOT NULL,
  `name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_default` tinyint(1) DEFAULT 0,
  `flag_icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`id`, `code`, `name`, `is_active`, `is_default`, `flag_icon`, `created_at`) VALUES
(1, 'hu', 'Magyar', 1, 1, NULL, '2024-12-17 14:50:44'),
(2, 'en', 'English', 1, 0, NULL, '2024-12-17 14:50:44');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
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
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `category_id`, `image`, `created_at`) VALUES
(1, 'iPhone 14 Pro', 'Latest Apple smartphone with advanced features', 999.99, 1, 'iphone14pro.jpg', '2024-12-16 15:23:58'),
(2, 'Samsung 4K TV', '55-inch Smart LED TV with HDR', 699.99, 1, 'samsung-tv.jpg', '2024-12-16 15:23:58'),
(3, 'MacBook Air M2', '13-inch laptop with Apple M2 chip', 1299.99, 1, 'macbook-air.jpg', '2024-12-16 15:23:58'),
(4, 'Classic Blue Jeans', 'Comfortable cotton denim jeans', 49.99, 2, 'blue-jeans.jpg', '2024-12-16 15:23:58'),
(5, 'White Sneakers', 'Casual athletic shoes', 79.99, 2, 'white-sneakers.jpg', '2024-12-16 15:23:58'),
(6, 'Cotton T-Shirt', 'Basic crew neck t-shirt', 19.99, 2, 'tshirt.jpg', '2024-12-16 15:23:58'),
(7, 'The Art of Programming', 'Comprehensive guide to programming', 59.99, 3, 'programming-book.jpg', '2024-12-16 15:23:58'),
(8, 'Cooking Basics', 'Learn to cook like a chef', 29.99, 3, 'cooking-book.jpg', '2024-12-16 15:23:58'),
(9, 'Science Fiction Collection', 'Best sci-fi stories of 2023', 39.99, 3, 'scifi-book.jpg', '2024-12-16 15:23:58'),
(10, 'Garden Tool Set', 'Complete set of essential garden tools', 89.99, 4, 'garden-tools.jpg', '2024-12-16 15:23:58'),
(11, 'Smart LED Bulb', 'WiFi-enabled color changing bulb', 29.99, 4, 'led-bulb.jpg', '2024-12-16 15:23:58'),
(12, 'Throw Pillows Set', 'Decorative pillows for your couch', 44.99, 4, 'pillows.jpg', '2024-12-16 15:23:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_translations`
--

CREATE TABLE `product_translations` (
  `product_id` int(11) NOT NULL,
  `language_code` varchar(5) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_translations`
--

INSERT INTO `product_translations` (`product_id`, `language_code`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'hu', 'iPhone 14 Pro', 'A legújabb Apple okostelefon fejlett funkciókkal', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(1, 'en', 'iPhone 14 Pro', 'Latest Apple smartphone with advanced features', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(2, 'hu', 'Samsung 4K TV', '55 inches Smart LED TV HDR technológiával', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(2, 'en', 'Samsung 4K TV', '55-inch Smart LED TV with HDR', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(3, 'hu', 'MacBook Air M2', '13 inches laptop Apple M2 processzorral', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(3, 'en', 'MacBook Air M2', '13-inch laptop with Apple M2 chip', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(4, 'hu', 'Klasszikus Kék Farmer', 'Kényelmes pamut farmer', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(4, 'en', 'Classic Blue Jeans', 'Comfortable cotton denim jeans', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(5, 'hu', 'Fehér Sportcipő', 'Alkalmi sportcipő', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(5, 'en', 'White Sneakers', 'Casual athletic shoes', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(6, 'hu', 'Pamut Póló', 'Kerek nyakú alapdarab póló', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(6, 'en', 'Cotton T-Shirt', 'Basic crew neck t-shirt', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(7, 'hu', 'A Programozás Művészete', 'Átfogó programozási útmutató', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(7, 'en', 'The Art of Programming', 'Comprehensive guide to programming', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(8, 'hu', 'Főzési Alapok', 'Tanulj meg főzni, mint egy séf', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(8, 'en', 'Cooking Basics', 'Learn to cook like a chef', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(9, 'hu', 'Sci-fi Gyűjtemény', '2023 legjobb sci-fi történetei', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(9, 'en', 'Science Fiction Collection', 'Best sci-fi stories of 2023', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(10, 'hu', 'Kerti Szerszámkészlet', 'Alapvető kerti szerszámok teljes készlete', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(10, 'en', 'Garden Tool Set', 'Complete set of essential garden tools', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(11, 'hu', 'Okos LED Izzó', 'WiFi-képes, színváltós izzó', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(11, 'en', 'Smart LED Bulb', 'WiFi-enabled color changing bulb', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(12, 'hu', 'Díszpárna Szett', 'Dekoratív párnák a kanapéra', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(12, 'en', 'Throw Pillows Set', 'Decorative pillows for your couch', '2024-12-17 14:50:44', '2024-12-17 14:50:44');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('currency', 'HUF');

-- --------------------------------------------------------

--
-- Table structure for table `translations`
--

CREATE TABLE `translations` (
  `id` int(11) NOT NULL,
  `language_code` varchar(5) NOT NULL,
  `translation_key` varchar(255) NOT NULL,
  `translation_value` text NOT NULL,
  `context` varchar(50) DEFAULT 'shop',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `translations`
--

INSERT INTO `translations` (`id`, `language_code`, `translation_key`, `translation_value`, `context`, `created_at`, `updated_at`) VALUES
(1, 'hu', 'nav.home', 'Főoldal', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(2, 'en', 'nav.home', 'Home', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(3, 'hu', 'nav.products', 'Termékek', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(4, 'en', 'nav.products', 'Products', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(5, 'hu', 'nav.categories', 'Kategóriák', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(6, 'en', 'nav.categories', 'Categories', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(7, 'hu', 'nav.cart', 'Kosár', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(8, 'en', 'nav.cart', 'Cart', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(9, 'hu', 'product.price', 'Ár: {price}', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(10, 'en', 'product.price', 'Price: {price}', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(11, 'hu', 'product.outofstock', 'Nincs készleten', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(12, 'en', 'product.outofstock', 'Out of stock', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(13, 'hu', 'product.addtocart', 'Kosárba', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(14, 'en', 'product.addtocart', 'Add to Cart', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(15, 'hu', 'cart.empty', 'A kosár üres', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(16, 'en', 'cart.empty', 'Cart is empty', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(17, 'hu', 'cart.checkout', 'Fizetés', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(18, 'en', 'cart.checkout', 'Checkout', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(19, 'hu', 'admin.dashboard', 'Vezérlőpult', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(20, 'en', 'admin.dashboard', 'Dashboard', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(21, 'hu', 'admin.products', 'Termékek', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(22, 'en', 'admin.products', 'Products', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(23, 'hu', 'admin.categories', 'Kategóriák', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(24, 'en', 'admin.categories', 'Categories', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(25, 'hu', 'admin.orders', 'Rendelések', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(26, 'en', 'admin.orders', 'Orders', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(27, 'hu', 'admin.settings', 'Beállítások', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(28, 'en', 'admin.settings', 'Settings', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(29, 'hu', 'admin.settings.general', 'Általános beállítások', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(30, 'en', 'admin.settings.general', 'General Settings', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(31, 'hu', 'admin.settings.currency', 'Pénznem beállítások', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(32, 'en', 'admin.settings.currency', 'Currency Settings', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(33, 'hu', 'admin.settings.style', 'Megjelenés beállítások', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(34, 'en', 'admin.settings.style', 'Style Settings', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(35, 'hu', 'admin.settings.language', 'Nyelvi beállítások', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(36, 'en', 'admin.settings.language', 'Language Settings', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(37, 'hu', 'admin.panel', 'Admin Panel', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(38, 'en', 'admin.panel', 'Admin Panel', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(39, 'hu', 'admin.return_to_shop', 'Vissza a boltba', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(40, 'en', 'admin.return_to_shop', 'Return to Shop', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(41, 'hu', 'admin.logout', 'Kijelentkezés', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(42, 'en', 'admin.logout', 'Logout', 'admin', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(43, 'hu', 'cart.added_success', 'Termék sikeresen hozzáadva a kosárhoz!', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(44, 'en', 'cart.added_success', 'Product successfully added to cart!', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(45, 'hu', 'cart.added_error', 'Hiba történt a termék kosárba helyezésekor!', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(46, 'en', 'cart.added_error', 'Error adding product to cart!', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(47, 'hu', 'categories.title', 'Kategóriák', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(48, 'en', 'categories.title', 'Categories', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(49, 'hu', 'nav.search', 'Termékek keresése...', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(50, 'en', 'nav.search', 'Search products...', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(51, 'hu', 'nav.logout', 'Kijelentkezés', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(52, 'en', 'nav.logout', 'Logout', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(53, 'hu', 'nav.cart_count', 'Kosár ({count})', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(54, 'en', 'nav.cart_count', 'Cart ({count})', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(55, 'hu', 'filter.title', 'Ár szűrő', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(56, 'en', 'filter.title', 'Price Filter', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(57, 'hu', 'filter.apply', 'Szűrő alkalmazása', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(58, 'en', 'filter.apply', 'Apply Filter', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(59, 'hu', 'cart.drawer.title', 'Kosár', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(60, 'en', 'cart.drawer.title', 'Your Cart', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(61, 'hu', 'cart.drawer.empty', 'A kosár üres', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(62, 'en', 'cart.drawer.empty', 'Your cart is empty', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(63, 'hu', 'cart.drawer.total', 'Összesen:', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(64, 'en', 'cart.drawer.total', 'Total:', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(65, 'hu', 'cart.drawer.checkout', 'Fizetés', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44'),
(66, 'en', 'cart.drawer.checkout', 'Checkout', 'shop', '2024-12-17 14:50:44', '2024-12-17 14:50:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_role` int(11) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `created_at`, `user_role`) VALUES
(2, 'admin', 'bencusix@me.com', '$2y$10$WcpsiU/lwugiSAAHrkp0LOLQRlAje2OJAa3lie2w1rZb/WOG9g2WG', '2024-12-16 19:32:43', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `category_translations`
--
ALTER TABLE `category_translations`
  ADD PRIMARY KEY (`category_id`,`language_code`),
  ADD KEY `language_code` (`language_code`);

--
-- Indexes for table `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`currency`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_translations`
--
ALTER TABLE `product_translations`
  ADD PRIMARY KEY (`product_id`,`language_code`),
  ADD KEY `language_code` (`language_code`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `translations`
--
ALTER TABLE `translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_translation` (`language_code`,`translation_key`,`context`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `translations`
--
ALTER TABLE `translations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `category_translations`
--
ALTER TABLE `category_translations`
  ADD CONSTRAINT `category_translations_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `category_translations_ibfk_2` FOREIGN KEY (`language_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_translations`
--
ALTER TABLE `product_translations`
  ADD CONSTRAINT `product_translations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_translations_ibfk_2` FOREIGN KEY (`language_code`) REFERENCES `languages` (`code`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
