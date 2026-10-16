-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 16, 2026 at 03:06 AM
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
-- Database: `cec_shm`
--

-- --------------------------------------------------------

--
-- Table structure for table `3l_parameters`
--

CREATE TABLE `3l_parameters` (
  `sensor_id` int(11) NOT NULL,
  `fck` int(11) DEFAULT NULL,
  `sfc` double DEFAULT NULL,
  `pour_date` varchar(40) NOT NULL DEFAULT '',
  `rh` int(11) DEFAULT NULL,
  `ac` double DEFAULT NULL,
  `ar` double DEFAULT NULL,
  `u_value` int(11) DEFAULT NULL,
  `load_value` double DEFAULT NULL,
  `serial_no` varchar(50) NOT NULL DEFAULT '',
  `cs` double DEFAULT NULL,
  `ct` double DEFAULT NULL,
  `ctea` varchar(40) NOT NULL DEFAULT '14.1',
  `cteb` varchar(40) NOT NULL DEFAULT '17.3',
  `ctec` varchar(40) NOT NULL DEFAULT '16.4',
  `l1_value` varchar(40) NOT NULL DEFAULT '0.006',
  `l2_value` varchar(40) NOT NULL DEFAULT '0.03',
  `cc` varchar(40) NOT NULL DEFAULT '1000',
  `t_wave` double DEFAULT NULL,
  `temperature` double DEFAULT NULL,
  `temp_offset` double DEFAULT NULL,
  `base_floor` int(11) DEFAULT NULL,
  `mid_floor` int(11) DEFAULT NULL,
  `roof_floor` int(11) DEFAULT NULL,
  `creep_t0` int(11) NOT NULL DEFAULT 28,
  `ts_days` varchar(40) NOT NULL DEFAULT '0',
  `h0_value` varchar(40) NOT NULL DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `3l_readings`
--

CREATE TABLE `3l_readings` (
  `sensor_id` int(11) NOT NULL,
  `row_no` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL DEFAULT '',
  `reading_date` int(11) NOT NULL DEFAULT 0,
  `bottom_strain` double DEFAULT NULL,
  `bottom_temp` double DEFAULT NULL,
  `top_strain` double DEFAULT NULL,
  `top_temp` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `3l_stress_history`
--

CREATE TABLE `3l_stress_history` (
  `stress_history_id` int(11) NOT NULL,
  `sensor_id` int(11) NOT NULL,
  `row_no` int(11) NOT NULL,
  `history_date` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `atg_parameters`
--

CREATE TABLE `atg_parameters` (
  `sensor_id` int(11) NOT NULL,
  `concrete_grade` int(11) DEFAULT NULL,
  `sfc` double DEFAULT NULL,
  `pour_date` varchar(40) NOT NULL DEFAULT '',
  `rh` int(11) DEFAULT NULL,
  `ac` double DEFAULT NULL,
  `ar` double DEFAULT NULL,
  `u_value` int(11) DEFAULT NULL,
  `load_value` double DEFAULT NULL,
  `serial_no` varchar(50) NOT NULL DEFAULT '',
  `ks` double DEFAULT NULL,
  `ktt` double DEFAULT NULL,
  `kts` double DEFAULT NULL,
  `kst` varchar(40) NOT NULL DEFAULT '',
  `temp_fbg_1` double DEFAULT NULL,
  `temp_fbg_2` double DEFAULT NULL,
  `base_floor` int(11) DEFAULT NULL,
  `mid_floor` int(11) DEFAULT NULL,
  `roof_floor` int(11) DEFAULT NULL,
  `creep_t0` int(11) NOT NULL DEFAULT 28,
  `ts_days` varchar(40) NOT NULL DEFAULT '0',
  `h0_value` varchar(40) NOT NULL DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `atg_readings`
--

CREATE TABLE `atg_readings` (
  `sensor_id` int(11) NOT NULL,
  `row_no` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL DEFAULT '',
  `reading_date` int(11) NOT NULL DEFAULT 0,
  `bottom_strain` double DEFAULT NULL,
  `bottom_temp` double DEFAULT NULL,
  `top_strain` double DEFAULT NULL,
  `top_temp` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `atg_stress_history`
--

CREATE TABLE `atg_stress_history` (
  `stress_history_id` int(11) NOT NULL,
  `sensor_id` int(11) NOT NULL,
  `row_no` int(11) NOT NULL,
  `history_date` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE `blocks` (
  `block_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `block_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_projects`
--

CREATE TABLE `client_projects` (
  `user_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `project_id` int(11) NOT NULL,
  `project_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensors`
--

CREATE TABLE `sensors` (
  `sensor_id` int(11) NOT NULL,
  `block_id` int(11) NOT NULL,
  `sensor_number` varchar(20) NOT NULL,
  `formula` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `session_version` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `role`, `session_version`) VALUES
(1, 'cambrian', '$2y$10$wn/mLcZzNgb7U0kIp8eAeuMUAxUFSfeMrnEhbt/753J1YnsWA5qxy', 'admin', 3),
(2, 'user', '$2y$10$UAtqNzy13p/h2I/sE.epDO.wJAAAAwLIEk.s8rdr6ZidoznLrku/.', 'client', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `3l_parameters`
--
ALTER TABLE `3l_parameters`
  ADD PRIMARY KEY (`sensor_id`);

--
-- Indexes for table `3l_readings`
--
ALTER TABLE `3l_readings`
  ADD PRIMARY KEY (`sensor_id`,`row_no`);

--
-- Indexes for table `3l_stress_history`
--
ALTER TABLE `3l_stress_history`
  ADD PRIMARY KEY (`stress_history_id`),
  ADD UNIQUE KEY `sensor_row` (`sensor_id`,`row_no`);

--
-- Indexes for table `atg_parameters`
--
ALTER TABLE `atg_parameters`
  ADD PRIMARY KEY (`sensor_id`);

--
-- Indexes for table `atg_readings`
--
ALTER TABLE `atg_readings`
  ADD PRIMARY KEY (`sensor_id`,`row_no`);

--
-- Indexes for table `atg_stress_history`
--
ALTER TABLE `atg_stress_history`
  ADD PRIMARY KEY (`stress_history_id`),
  ADD UNIQUE KEY `sensor_row` (`sensor_id`,`row_no`);

--
-- Indexes for table `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`block_id`),
  ADD UNIQUE KEY `unique_block_name` (`project_id`,`block_name`);

--
-- Indexes for table `client_projects`
--
ALTER TABLE `client_projects`
  ADD PRIMARY KEY (`user_id`,`project_id`),
  ADD KEY `fk_client_projects_project` (`project_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`project_id`),
  ADD UNIQUE KEY `unique_project_name` (`project_name`);

--
-- Indexes for table `sensors`
--
ALTER TABLE `sensors`
  ADD PRIMARY KEY (`sensor_id`),
  ADD UNIQUE KEY `unique_sensor_number` (`block_id`,`sensor_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `3l_stress_history`
--
ALTER TABLE `3l_stress_history`
  MODIFY `stress_history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `atg_stress_history`
--
ALTER TABLE `atg_stress_history`
  MODIFY `stress_history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `blocks`
--
ALTER TABLE `blocks`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `project_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sensors`
--
ALTER TABLE `sensors`
  MODIFY `sensor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `3l_parameters`
--
ALTER TABLE `3l_parameters`
  ADD CONSTRAINT `fk_3l_parameters_sensor` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`) ON DELETE CASCADE;

--
-- Constraints for table `3l_readings`
--
ALTER TABLE `3l_readings`
  ADD CONSTRAINT `fk_3l_readings_sensor` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`) ON DELETE CASCADE;

--
-- Constraints for table `3l_stress_history`
--
ALTER TABLE `3l_stress_history`
  ADD CONSTRAINT `fk_3l_history_sensor` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`) ON DELETE CASCADE;

--
-- Constraints for table `atg_parameters`
--
ALTER TABLE `atg_parameters`
  ADD CONSTRAINT `fk_atg_parameters_sensor` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`) ON DELETE CASCADE;

--
-- Constraints for table `atg_readings`
--
ALTER TABLE `atg_readings`
  ADD CONSTRAINT `fk_atg_readings_sensor` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`) ON DELETE CASCADE;

--
-- Constraints for table `atg_stress_history`
--
ALTER TABLE `atg_stress_history`
  ADD CONSTRAINT `fk_atg_history_sensor` FOREIGN KEY (`sensor_id`) REFERENCES `sensors` (`sensor_id`) ON DELETE CASCADE;

--
-- Constraints for table `blocks`
--
ALTER TABLE `blocks`
  ADD CONSTRAINT `fk_blocks_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE;

--
-- Constraints for table `client_projects`
--
ALTER TABLE `client_projects`
  ADD CONSTRAINT `fk_client_projects_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_client_projects_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `sensors`
--
ALTER TABLE `sensors`
  ADD CONSTRAINT `fk_sensors_block` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
