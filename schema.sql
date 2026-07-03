-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 03, 2026 at 08:35 AM
-- Server version: 10.11.14-MariaDB-0+deb12u2
-- PHP Version: 8.4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `YOUR_DATABASE_NAME`
--

-- --------------------------------------------------------

--
-- Table structure for table `squid_access_log`
--

CREATE TABLE `squid_access_log` (
  `id` int(11) NOT NULL,
  `log_time` int(11) NOT NULL,
  `client_ip` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `method` varchar(10) NOT NULL,
  `url` text NOT NULL,
  `status_code` int(11) NOT NULL,
  `response_size` bigint(20) NOT NULL,
  `response_time` int(11) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `hitratio` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `squid_access_log`
--
ALTER TABLE `squid_access_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_access` (`log_time`,`client_ip`,`url`(255));

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `squid_access_log`
--
ALTER TABLE `squid_access_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
