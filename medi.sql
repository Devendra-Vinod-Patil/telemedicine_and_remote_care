-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 08:30 PM
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
-- Database: `medi`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `room_id` varchar(50) DEFAULT NULL,
  `prescription` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `doctor_id`, `patient_id`, `appointment_date`, `appointment_time`, `status`, `created_at`, `room_id`, `prescription`) VALUES
(5, 23, 4, '2025-09-29', '19:27:00', 'confirmed', '2025-09-27 10:54:31', NULL, 'uploads/prescriptions/1766552989_ef7b358b-5aa2-48e4-a385-0791431315bc.png'),
(6, 23, 4, '2025-09-29', '22:31:00', 'completed', '2025-09-27 10:56:06', NULL, '{\"type\":\"digital\",\"patient_name\":\"shakti vala\",\"age\":78,\"gender\":\"male\",\"diagnosis\":\"suffering from cold\",\"medicines\":[{\"name\":\"Paracetamol\",\"dose\":\"2\",\"duration\":\"5\"},{\"name\":\"aspirin\",\"dose\":\"5\",\"duration\":\"8\"}],\"note_advice\":\"hydrated consume 5 liter d'),
(7, 23, 4, '2025-09-30', '18:47:00', 'completed', '2025-09-27 11:17:40', NULL, 'uploads/prescriptions/1766553047_ef7b358b-5aa2-48e4-a385-0791431315bc.png'),
(8, 1, 4, '2025-09-29', '09:38:00', 'pending', '2025-09-27 19:04:11', NULL, NULL),
(9, 1, 4, '2025-10-07', '12:36:00', 'pending', '2025-09-27 19:04:48', NULL, NULL),
(10, 23, 4, '2025-10-15', '18:44:00', 'completed', '2025-09-28 10:11:39', 'room_68d909db2d823', 'uploads/prescriptions/1766552721_DSC_0314[1].png'),
(11, 23, 4, '2025-10-15', '17:44:00', 'completed', '2025-09-28 10:14:48', 'room_68d90a98025e9', 'uploads/prescriptions/1766552455_hand-drawn-doctor-cartoon-illustration_23-2150680327.jpg'),
(12, 23, 4, '2026-04-21', '04:28:00', 'confirmed', '2026-04-20 17:52:32', 'room_69e667e00ccb2', '{\"type\":\"digital\",\"patient_name\":\"shakti vala\",\"age\":45,\"gender\":\"male\",\"diagnosis\":\"suffering from cold \\r\\nfever\",\"medicines\":[{\"name\":\"Paracetamol\",\"dose\":\"3\",\"duration\":\"10\"}],\"note_advice\":\"make hydrated\",\"created_at\":\"2026-04-20T20:02:50+02:00\"}'),
(13, 23, 4, '2026-04-21', '02:51:00', 'pending', '2026-04-20 18:21:41', 'room_69e66eb5c6efc', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `name`, `email`, `subject`, `message`, `submission_date`) VALUES
(1, 'devendra patil', 'patildevendra984@gmail.com', 'jsbdkshkj', 'fekjfhekjflejofe', '2025-09-27 17:45:55'),
(2, 'devendra patil', 'patildevendra984@gmail.com', 'jsbdkshkj', 'fekjfhekjflejofe', '2025-09-27 17:57:01');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `clinic` varchar(150) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `full_name`, `email`, `phone`, `password`, `specialization`, `experience`, `clinic`, `photo`, `created_at`) VALUES
(1, 'devendra', 'dev@gmail.com', '9696596', '$2y$10$9t7RKGqgoNMW8AkzuyiL7uWWAMO9ueFhWdfXesuKk/yeF4A0EPJ2m', 'ewffew', 5, 'wflrkekm', 'uploads/1758968269_default.png', '2025-09-27 10:17:49'),
(23, 'Devendra Patil', 'devendra@gmail.com', '9033051964', '$2y$10$Y0lVZ53xw6wMRhxo9IlZheDmI2w/AZ717wfxZ7uKyLQlNpPGYxgPa', 'cardiologist', 10, 'Patil Hospital', 'uploads/1758970384_Screenshot 2025-09-25 134622.png', '2025-09-27 10:53:04');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `full_name`, `email`, `phone`, `photo`, `password`, `created_at`) VALUES
(4, 'shakti vala', 'shakti@gmail.com', '8855665544', NULL, '$2y$10$RQbgGTgja249/yh2dS.meOoDBSxcdCoa0Kuq7WIDYoRvl1EbQm13u', '2025-09-25 18:24:36');

-- --------------------------------------------------------

--
-- Table structure for table `site_info`
--

CREATE TABLE `site_info` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_info`
--

INSERT INTO `site_info` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'contact_address', '123 Health St, Wellness City, 10001'),
(2, 'contact_phone', '(123) 456-7890'),
(3, 'contact_email', 'support@telemedcare.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `site_info`
--
ALTER TABLE `site_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `site_info`
--
ALTER TABLE `site_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
