-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2025 at 04:16 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kakee-carwash-pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `vehicle_brand` varchar(50) NOT NULL,
  `plate_no` varchar(20) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `payment_method` enum('cash','qr') NOT NULL DEFAULT 'cash',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vehicle_type` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `vehicle_brand`, `plate_no`, `service_id`, `package_id`, `payment_method`, `created_at`, `vehicle_type`, `created_by`) VALUES
(1, 'test', '01111263463', 'Honda', 'PJG 1986', NULL, NULL, 'cash', '2025-06-09 13:48:16', NULL, 2),
(4, 'test1231', '123123', '21312', '132', 1, NULL, 'cash', '2025-06-12 16:51:21', 'car_m', 3),
(5, 'Mohd', '01429821940', 'Perodua', 'MPW1201', 1, NULL, 'cash', '2025-06-17 12:33:40', 'car_m', 3),
(6, 'Azam', '01719029392', 'Honda', 'VMF2010', NULL, 3, 'qr', '2025-06-17 15:59:27', 'car_m', 3),
(7, 'Fatin', '01840128109', 'Perodua', 'Mdu1093', NULL, 4, 'qr', '2025-06-18 13:18:38', 'car_s', 2),
(8, 'Mira', '0132445657', 'Tesla', 'vqw7874', 1, NULL, 'qr', '2025-06-18 14:15:23', 'car_m', 2),
(9, 'Azlan', '01928391029', 'Toyota', 'WWF1020', NULL, 5, 'qr', '2025-06-18 15:39:28', 'car_l', 3),
(11, 'Roy', '01332123920', 'Proton', 'plu1291', 6, NULL, 'qr', '2025-06-18 16:33:10', 'car_xl', 3),
(12, 'Mat', '01921890123', 'Honda', 'brk5224', NULL, 4, 'cash', '2025-06-18 16:43:56', 'car_s', 2),
(13, 'Ah Seng', '01412345678', 'Toyota', 'aaa1990', NULL, 5, 'cash', '2025-06-18 17:31:31', 'car_l', 2),
(14, 'Leong', '01829320429', 'Toyota', 'sjv9302', 1, NULL, 'qr', '2025-06-19 06:24:56', 'motor_lt', 3),
(17, 'Vendran', '01894027492', 'Suzuki', 'vmn2090', 1, NULL, 'qr', '2025-06-19 06:41:40', 'motor_gt', 2),
(18, 'Aisyah', '01219409109', 'Perodua Kancil', 'dba5029', NULL, 3, 'cash', '2025-06-19 07:00:21', 'car_s', 3),
(19, 'Malik', '01720920940', 'Proton X50', 'jwa1292', 1, NULL, 'qr', '2025-06-19 13:54:50', 'car_l', 3);

-- --------------------------------------------------------

--
-- Table structure for table `customer_services`
--

CREATE TABLE `customer_services` (
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `via_package` int(11) DEFAULT NULL,
  `price` decimal(8,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `customer_services`
--

INSERT INTO `customer_services` (`customer_id`, `service_id`, `via_package`, `price`) VALUES
(4, 1, NULL, 20.00),
(5, 1, NULL, 20.00),
(6, 1, 3, 20.00),
(6, 9, 3, 20.00),
(7, 1, 4, 18.00),
(7, 7, 4, 10.00),
(7, 9, 4, 20.00),
(8, 1, NULL, 20.00),
(9, 1, 5, 23.00),
(9, 7, 5, 15.00),
(9, 8, 5, 20.00),
(9, 9, 5, 25.00),
(11, 6, NULL, 20.00),
(12, 1, 4, 18.00),
(12, 7, 4, 10.00),
(12, 9, 4, 20.00),
(13, 1, 5, 23.00),
(13, 7, 5, 15.00),
(13, 8, 5, 20.00),
(13, 9, 5, 25.00),
(14, 1, NULL, 10.00),
(17, 1, NULL, 12.00),
(18, 1, 3, 18.00),
(18, 9, 3, 20.00),
(19, 1, NULL, 23.00);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `price`, `description`, `status`) VALUES
(3, 'Pakej A', 35.00, 'Cuci Luar & Dalam + Nano Mist', 1),
(4, 'Pakej B', 45.00, 'Cuci Luar & Dalam + Nano Mist + Polish Dashboard', 1),
(5, 'Pakej C', 65.00, 'Cuci Luar & Dalam + Nano Mist + Water Wax + Polish Dashboard', 1);

-- --------------------------------------------------------

--
-- Table structure for table `package_services`
--

CREATE TABLE `package_services` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `package_services`
--

INSERT INTO `package_services` (`id`, `package_id`, `service_id`) VALUES
(6, 3, 1),
(7, 3, 9),
(8, 4, 1),
(9, 4, 9),
(10, 4, 7),
(11, 5, 1),
(12, 5, 9),
(13, 5, 7),
(14, 5, 8);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `status`) VALUES
(1, 'Cuci Luar & Dalam', 'Cuci bahagian luar dan dalam kereta.', 1),
(2, 'Polish & Wax', 'Servis mengilat dan lilin untuk kereta.', 1),
(5, 'Luar', NULL, 1),
(6, 'Cuci enjin', NULL, 1),
(7, 'Polish Dashboard', NULL, 1),
(8, 'Waterwax', NULL, 1),
(9, 'Nano Mist', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `service_prices`
--

CREATE TABLE `service_prices` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `vehicle_type` enum('car_s','car_m','car_l','car_xl','motor_lt','motor_gt') NOT NULL,
  `price` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `service_prices`
--

INSERT INTO `service_prices` (`id`, `service_id`, `vehicle_type`, `price`) VALUES
(1, 1, 'car_s', 18.00),
(2, 1, 'car_m', 20.00),
(3, 1, 'car_l', 23.00),
(4, 1, 'car_xl', 25.00),
(5, 1, 'motor_lt', 10.00),
(6, 1, 'motor_gt', 12.00),
(7, 2, 'car_s', 250.00),
(8, 2, 'car_m', 300.00),
(9, 2, 'car_l', 350.00),
(10, 2, 'car_xl', 400.00),
(28, 5, 'car_s', 12.00),
(29, 5, 'car_m', 12.00),
(30, 5, 'car_l', 15.00),
(31, 5, 'car_xl', 18.00),
(49, 6, 'car_s', 15.00),
(50, 6, 'car_m', 15.00),
(51, 6, 'car_l', 15.00),
(52, 6, 'car_xl', 20.00),
(53, 7, 'car_s', 10.00),
(54, 7, 'car_m', 10.00),
(55, 7, 'car_l', 15.00),
(56, 7, 'car_xl', 20.00),
(57, 8, 'car_s', 15.00),
(58, 8, 'car_m', 15.00),
(59, 8, 'car_l', 20.00),
(60, 8, 'car_xl', 25.00),
(61, 8, 'motor_lt', 5.00),
(62, 8, 'motor_gt', 7.00),
(63, 9, 'car_s', 20.00),
(64, 9, 'car_m', 20.00),
(65, 9, 'car_l', 25.00),
(66, 9, 'car_xl', 30.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', '001131', 'admin'),
(2, 'Ali', 'staff1', 'staff'),
(3, 'Fahim', 'staff2', 'staff');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `fk_created_by` (`created_by`);

--
-- Indexes for table `customer_services`
--
ALTER TABLE `customer_services`
  ADD PRIMARY KEY (`customer_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `package_services`
--
ALTER TABLE `package_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_prices`
--
ALTER TABLE `service_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`);

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
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `package_services`
--
ALTER TABLE `package_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `service_prices`
--
ALTER TABLE `service_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `customer_services`
--
ALTER TABLE `customer_services`
  ADD CONSTRAINT `customer_services_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_services`
--
ALTER TABLE `package_services`
  ADD CONSTRAINT `package_services_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_prices`
--
ALTER TABLE `service_prices`
  ADD CONSTRAINT `service_prices_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

SELECT * FROM customers
WHERE name LIKE '%ali%';