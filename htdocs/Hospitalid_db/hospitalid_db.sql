-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2025 at 04:52 PM
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
-- Database: `hospitalid_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `patient_visit`
--

CREATE TABLE `patient_visit` (
  `patient_visit_id` int(11) NOT NULL,
  `valid_id` int(11) DEFAULT NULL,
  `visit_id` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor`
--

CREATE TABLE `visitor` (
  `visitor_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `valid_id` varchar(50) NOT NULL,
  `number_of_visitors` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`visitor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visit_log`
--

CREATE TABLE `visit_log` (
  `visit_log_id` varchar(40) NOT NULL,
  `valid_id` int(11) NOT NULL,
  `check_in_time` varchar(40) DEFAULT NULL,
  `check_out_time` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `patient_visit`
--
ALTER TABLE `patient_visit`
  ADD PRIMARY KEY (`patient_visit_id`),
  ADD KEY `Visitor_Patient_visit` (`valid_id`),
  ADD KEY `Visit_log_Patient_visit` (`visit_id`);

--
-- Indexes for table `visitor`
--
ALTER TABLE `visitor`
  ADD PRIMARY KEY (`visitor_id`);

--
-- Indexes for table `visit_log`
--
ALTER TABLE `visit_log`
  ADD PRIMARY KEY (`visit_log_id`),
  ADD KEY `Visitor_Visit_log` (`valid_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `patient_visit`
--
ALTER TABLE `patient_visit`
  ADD CONSTRAINT `Visit_log_Patient_visit` FOREIGN KEY (`visit_id`) REFERENCES `visit_log` (`visit_log_id`),
  ADD CONSTRAINT `Visitor_Patient_visit` FOREIGN KEY (`valid_id`) REFERENCES `visitor` (`visitor_id`);

--
-- Constraints for table `visit_log`
--
ALTER TABLE `visit_log`
  ADD CONSTRAINT `Visitor_Visit_log` FOREIGN KEY (`valid_id`) REFERENCES `visitor` (`visitor_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
