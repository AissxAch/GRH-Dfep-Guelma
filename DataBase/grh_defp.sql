-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2025 at 03:16 AM
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
-- Database: `grh_defp`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `activity_type` enum('hire','promotion','modification','delete') DEFAULT NULL,
  `details` text DEFAULT NULL,
  `activity_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL COMMENT 'اسم القسم (عربي)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `name`, `created_at`) VALUES
(1, 'الإدارة', '2025-03-28 17:26:30'),
(2, 'الموارد البشرية', '2025-03-28 17:26:30'),
(3, 'المبيعات', '2025-03-28 17:26:30'),
(4, 'التسويق', '2025-03-28 17:26:30'),
(5, 'التقنية', '2025-03-28 17:26:30');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `firstname_ar` varchar(100) NOT NULL,
  `lastname_ar` varchar(100) NOT NULL,
  `firstname_en` varchar(100) NOT NULL,
  `lastname_en` varchar(100) NOT NULL,
  `birth_date` date NOT NULL,
  `birth_place` varchar(100) NOT NULL,
  `gender` enum('male','female') NOT NULL COMMENT 'النوع',
  `bloodtype` varchar(20) NOT NULL,
  `national_id` varchar(20) NOT NULL COMMENT 'الرقم الوطني',
  `ssn` int(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL COMMENT 'العنوان',
  `position` varchar(50) NOT NULL COMMENT 'المنصب',
  `department_id` int(10) DEFAULT NULL,
  `first_hire_date` date NOT NULL,
  `hire_date` date NOT NULL COMMENT 'تاريخ التعيين',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'الحالة',
  `vacances_remain_days` int(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `upload_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_high_level_history`
--

CREATE TABLE `employee_high_level_history` (
  `history_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_previous_positions`
--

CREATE TABLE `employee_previous_positions` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `position` varchar(100) NOT NULL,
  `department_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `high_level_positions`
--

CREATE TABLE `high_level_positions` (
  `position_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'اسم المنصب العالي',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `high_level_positions`
--

INSERT INTO `high_level_positions` (`position_id`, `name`, `created_at`) VALUES
(1, 'مكلف بالدراسات وبمشروع في الإدارة المركزية', '2025-04-18 22:54:09'),
(2, 'ملحق بالديوان في الإدارة المركزية', '2025-04-18 22:54:09'),
(3, 'مساعد بالديوان', '2025-04-18 22:54:09'),
(4, 'مكلف بالاستقبال والتوجيه', '2025-04-18 22:54:09'),
(5, 'مكلف ببرامج الترجمة - الترجمة الفورية', '2025-04-18 22:54:09'),
(6, 'مسؤول قواعد المعطيات', '2025-04-18 22:54:09'),
(7, 'مسؤول الشبكة', '2025-04-18 22:54:09'),
(8, 'مسؤول النظاميات المعلوماتية', '2025-04-18 22:54:09'),
(9, 'مكلف بالبرامج الإحصائية', '2025-04-18 22:54:09'),
(10, 'مكلف بالبرامج الوثائقية', '2025-04-18 22:54:09'),
(11, 'رئيس مخبر', '2025-04-18 22:54:09'),
(12, 'رئيس مصلحة الصيانة', '2025-04-18 22:54:09');

-- --------------------------------------------------------

--
-- Table structure for table `positions`
--

CREATE TABLE `positions` (
  `position_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `positions`
--

INSERT INTO `positions` (`position_id`, `name`, `created_at`) VALUES
(1, 'متصرف', '2025-04-15 00:17:24'),
(2, 'متصرف رئيسي', '2025-04-15 00:17:24'),
(3, 'متصرف مستشار', '2025-04-15 00:17:24'),
(4, 'ملحق الإدارة', '2025-04-15 00:17:24'),
(5, 'ملحق رئيسي لإدارة', '2025-04-15 00:17:24'),
(6, 'عون مكتب', '2025-04-15 00:17:24'),
(7, 'عون إدارة', '2025-04-15 00:17:24'),
(8, 'عون إدارة رئيسي', '2025-04-15 00:17:24'),
(9, 'مساعد محاسب إداري', '2025-04-15 00:17:24'),
(10, 'محاسب إداري', '2025-04-15 00:17:24'),
(11, 'محاسب إداري رئيسي', '2025-04-15 00:17:24'),
(12, 'عون حفظ البيانات', '2025-04-15 00:17:24'),
(13, 'كاتب', '2025-04-15 00:17:24'),
(14, 'كاتب مديرية', '2025-04-15 00:17:24'),
(15, 'كاتب مديرية رئيسي', '2025-04-15 00:17:24'),
(16, 'مترجم', '2025-04-15 00:17:24'),
(17, 'مترجم رئيسي', '2025-04-15 00:17:24'),
(18, 'رئيس المترجمين', '2025-04-15 00:17:24'),
(19, 'مهندس تطبيقي', '2025-04-15 00:17:24'),
(20, 'مهندس دولة', '2025-04-15 00:17:24'),
(21, 'مهندس رئيسي', '2025-04-15 00:17:24'),
(22, 'رئيس المهندسين', '2025-04-15 00:17:24'),
(24, 'تقني سام', '2025-04-15 00:17:24'),
(25, 'معاون تقني', '2025-04-15 00:17:24'),
(26, 'عون تقني', '2025-04-15 00:17:24'),
(27, 'وثائقي أمين محفوظات', '2025-04-15 00:17:24'),
(28, 'وثائقي أمين محفوظات رئيسي', '2025-04-15 00:17:24'),
(29, 'رئيس الوثائقيين أمناء المحفوظات', '2025-04-15 00:17:24'),
(30, 'مساعد وثائقي أمين محفوظات', '2025-04-15 00:17:24'),
(31, 'عون تقني في الوثائق والمحفوظات', '2025-04-15 00:17:24'),
(32, 'عون مخبر', '2025-04-15 00:17:24'),
(33, 'محلل اقتصادي', '2025-04-15 00:17:24'),
(34, 'محلل رئيسي', '2025-04-15 00:17:24'),
(35, 'رئيس المحللين', '2025-04-15 00:17:24'),
(36, 'تقني', '2025-04-15 00:55:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `FullName` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`FullName`, `username`, `password`, `id`) VALUES
('عاشوري عيسى', 'aissx', 'admin', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_high_level_history`
--
ALTER TABLE `employee_high_level_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `employee_previous_positions`
--
ALTER TABLE `employee_previous_positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `high_level_positions`
--
ALTER TABLE `high_level_positions`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`position_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_high_level_history`
--
ALTER TABLE `employee_high_level_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employee_previous_positions`
--
ALTER TABLE `employee_previous_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `high_level_positions`
--
ALTER TABLE `high_level_positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `positions`
--
ALTER TABLE `positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_high_level_history`
--
ALTER TABLE `employee_high_level_history`
  ADD CONSTRAINT `employee_high_level_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`),
  ADD CONSTRAINT `employee_high_level_history_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `high_level_positions` (`position_id`);

--
-- Constraints for table `employee_previous_positions`
--
ALTER TABLE `employee_previous_positions`
  ADD CONSTRAINT `employee_previous_positions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
