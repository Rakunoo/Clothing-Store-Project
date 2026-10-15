-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2026 at 07:06 AM
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
-- Database: `clothing_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `total_amount`, `status`) VALUES
(1, 1, '2026-09-15 14:55:26', 599.99, 'pending'),
(2, 2, '2026-09-17 03:44:25', 599.99, 'Pending'),
(3, 4, '2026-09-19 12:23:04', 259.99, 'Pending'),
(4, 4, '2026-09-19 12:23:14', 199.99, 'Pending'),
(5, 7, '2026-09-19 14:45:54', 199.99, 'Cancelled'),
(6, 6, '2026-09-19 18:26:32', 259.99, 'Cancelled'),
(7, 6, '2026-09-21 01:49:54', 259.99, 'Cancelled'),
(8, 6, '2026-09-21 01:51:11', 259.99, 'Cancelled'),
(9, 6, '2026-09-21 02:07:08', 719.97, 'Cancelled'),
(10, 6, '2026-09-21 04:21:54', 399.99, 'Cancelled'),
(11, 6, '2026-09-22 11:14:52', 0.99, 'Cancelled'),
(12, 6, '2026-09-22 12:42:57', 0.99, 'Cancelled'),
(13, 6, '2026-09-23 14:34:47', 0.99, 'Cancelled'),
(14, 6, '2026-09-25 06:58:08', 0.99, 'Cancelled'),
(15, 6, '2026-10-12 22:43:42', 399.99, 'Cancelled'),
(16, 6, '2026-10-12 22:44:08', 399.99, 'Cancelled'),
(17, 6, '2026-10-15 13:05:30', 199.99, 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `size` varchar(5) NOT NULL DEFAULT 'M'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_purchase`, `size`) VALUES
(1, 1, 1, 1, 599.99, 'M'),
(2, 2, 1, 1, 599.99, 'M'),
(3, 3, 1, 1, 259.99, 'M'),
(4, 4, 2, 1, 199.99, 'M'),
(5, 5, 2, 1, 199.99, 'M'),
(6, 6, 1, 1, 259.99, 'M'),
(7, 7, 1, 1, 259.99, 'M'),
(8, 8, 1, 1, 259.99, 'M'),
(9, 9, 2, 1, 199.99, 'M'),
(10, 9, 1, 2, 259.99, 'M'),
(11, 10, 4, 1, 399.99, 'M'),
(12, 11, 5, 1, 0.99, 'M'),
(13, 12, 5, 1, 0.99, 'M'),
(14, 13, 5, 1, 0.99, 'M'),
(15, 14, 5, 1, 0.99, 'M'),
(16, 15, 4, 1, 399.99, 'S'),
(17, 16, 4, 1, 399.99, 'XS'),
(18, 17, 2, 1, 199.99, 'L');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock_quantity`, `category`, `image_url`) VALUES
(1, 'Long Sleeves', 'Long Sleeves', 259.99, 0, 'MEN', NULL),
(2, 'Sando', 'Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sando Gang Sa', 199.99, 44, 'MEN', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRbnYdOvMR1s9XTY1bF86hFsYGJDzC9zHIz3pP_AQ42-TXOYbIMQPmrq4CZ&s=10'),
(4, 'Cropped Top', 'Cropped Top', 399.99, 4, 'WOMEN', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_size_stock`
--

CREATE TABLE `product_size_stock` (
  `product_id` int(11) NOT NULL,
  `size` varchar(5) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0
) ;

--
-- Dumping data for table `product_size_stock`
--

INSERT INTO `product_size_stock` (`product_id`, `size`, `quantity`) VALUES
(1, '2XL', 0),
(1, 'L', 0),
(1, 'M', 0),
(1, 'S', 0),
(1, 'XL', 0),
(1, 'XS', 0),
(2, '2XL', 23),
(2, 'L', 4),
(2, 'M', 3),
(2, 'S', 2),
(2, 'XL', 7),
(2, 'XS', 5),
(4, '2XL', 0),
(4, 'L', 0),
(4, 'M', 0),
(4, 'S', 0),
(4, 'XL', 0),
(4, 'XS', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` varchar(200) NOT NULL,
  `role` varchar(20) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `otp_attempts` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `address`, `role`, `phone_number`, `otp`, `otp_expiry`, `otp_attempts`) VALUES
(6, 'Darren (Gwapo)', 'admin123@gmail.com', '$2y$10$.RAeDv.T9heAHz0nFasws.b5OrbyCtu0FXmYCHxm8T2PtZQIecykS', 'Dagupan City', 'admin', '09912399286', '569376', '2026-10-12 23:24:43', 0),
(7, 'Eilis Yea', 'eilis123@gmail.com', '$2y$10$pPLsPQn9sZqmnqAQNSksUOT9kP2tN/fU6d3xGOEP9tR.WJOQnSiRy', 'Tapuac', 'customer', '09123456789', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `user_id` int(11) NOT NULL,
  `last_seen` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity`
--

INSERT INTO `user_activity` (`user_id`, `last_seen`) VALUES
(6, '2026-10-15 13:05:47');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_size_stock`
--
ALTER TABLE `product_size_stock`
  ADD PRIMARY KEY (`product_id`,`size`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `product_size_stock`
--
ALTER TABLE `product_size_stock`
  ADD CONSTRAINT `fk_product_size_stock_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
