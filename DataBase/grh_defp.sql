-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2025 at 07:43 PM
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
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `name_ar` varchar(50) NOT NULL COMMENT 'اسم القسم (عربي)',
  `name_en` varchar(50) NOT NULL COMMENT 'Department Name (English)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `name_ar`, `name_en`, `created_at`) VALUES
(1, 'الإدارة', 'Management', '2025-03-28 17:26:30'),
(2, 'الموارد البشرية', 'Human Resources', '2025-03-28 17:26:30'),
(3, 'المبيعات', 'Sales', '2025-03-28 17:26:30'),
(4, 'التسويق', 'Marketing', '2025-03-28 17:26:30'),
(5, 'التقنية', 'IT', '2025-03-28 17:26:30');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `full_name_ar` varchar(100) NOT NULL COMMENT 'الاسم الكامل (عربي)',
  `full_name_en` varchar(100) NOT NULL COMMENT 'Full Name (English)',
  `birth_date` date NOT NULL,
  `birth_place` varchar(100) NOT NULL,
  `gender` enum('male','female') NOT NULL COMMENT 'النوع',
  `bloodtype` varchar(20) NOT NULL,
  `national_id` varchar(20) NOT NULL COMMENT 'الرقم الوطني',
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL COMMENT 'العنوان',
  `position` varchar(50) NOT NULL COMMENT 'المنصب',
  `department` varchar(50) NOT NULL COMMENT 'القسم',
  `hire_date` date NOT NULL COMMENT 'تاريخ التعيين',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'الحالة',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `full_name_ar`, `full_name_en`, `birth_date`, `birth_place`, `gender`, `bloodtype`, `national_id`, `email`, `phone`, `address`, `position`, `department`, `hire_date`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'علي أحمد', 'Ali Ahmed', '1990-05-15', 'الرياض', 'male', 'O+', '1011111111', 'ali@company.com', '0551111111', 'الرياض - حي النخيل', 'مدير مشاريع', 'الإدارة', '2020-01-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(2, 'فاطمة خالد', 'Fatima Khalid', '1992-08-22', 'جدة', 'female', 'A+', '1022222222', 'fatima@company.com', '0552222222', 'جدة - حي الصفا', 'محلل موارد بشرية', 'الموارد البشرية', '2021-03-15', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(3, 'محمد ناصر', 'Mohammed Nasser', '1988-12-10', 'الدمام', 'male', 'B+', '1033333333', 'mohammed@company.com', '0553333333', 'الدمام - حي البحر', 'منسق مبيعات', 'المبيعات', '2019-11-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(4, 'لينا عمر', 'Lina Omar', '1995-04-05', 'الرياض', 'female', 'AB+', '1044444444', 'lina@company.com', '0554444444', 'الرياض - حي العليا', 'مسوق رقمي', 'التسويق', '2022-06-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(5, 'خالد سعود', 'Khaled Saud', '1993-07-19', 'الطائف', 'male', 'O-', '1055555555', 'khaled@company.com', '0555555555', 'الطائف - حي الورد', 'مبرمج', 'التقنية', '2023-01-10', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(6, 'نورة راشد', 'Noura Rashid', '1991-09-30', 'الخبر', 'female', 'A-', '1066666666', 'noura@company.com', '0556666666', 'الخبر - حي الجوهرة', 'مدير فريق', 'الإدارة', '2020-07-15', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(7, 'عمر حسن', 'Omar Hassan', '1985-03-25', 'الدمام', 'male', 'B-', '1077777777', 'omar@company.com', '0557777777', 'الدمام - حي النور', 'محاسب', 'الموارد البشرية', '2018-05-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(8, 'سارة عبدالله', 'Sara Abdullah', '1994-11-12', 'جدة', 'female', 'AB-', '1088888888', 'sara@company.com', '0558888888', 'جدة - حي الروضة', 'مندوبة مبيعات', 'المبيعات', '2021-09-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(9, 'فيصل نادر', 'Faisal Nadir', '1996-02-28', 'الرياض', 'male', 'O+', '1099999999', 'faisal@company.com', '0559999999', 'الرياض - حي السلي', 'مصمم جرافيك', 'التسويق', '2022-12-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(10, 'هديل محمد', 'Hadeel Mohammed', '1997-06-14', 'الجبيل', 'female', 'A+', '1100000000', 'hadeel@company.com', '0550000000', 'الجبيل - حي البلد', 'مختبر برمجيات', 'التقنية', '2023-03-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(11, 'أحمد سعيد', 'Ahmed Saeed', '1990-07-01', 'الرياض', 'male', 'O+', '2011111111', 'ahmed@company.com', '0561111111', 'الرياض - حي العريجاء', 'موظف استقبال', 'الإدارة', '2023-04-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(12, 'ريم عبدالعزيز', 'Reem Abdulaziz', '1992-09-15', 'جدة', 'female', 'A+', '2022222222', 'reem@company.com', '0562222222', 'جدة - حي المحمدية', 'مساعد إداري', 'الموارد البشرية', '2022-08-15', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(13, 'تركي فيصل', 'Turki Faisal', '1989-11-20', 'الدمام', 'male', 'B+', '2033333333', 'turki@company.com', '0563333333', 'الدمام - حي الثقبة', 'ممثل مبيعات', 'المبيعات', '2021-12-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(14, 'نوف خالد', 'Nouf Khalid', '1993-04-12', 'الرياض', 'female', 'AB+', '2044444444', 'nouf@company.com', '0564444444', 'الرياض - حي الندى', 'منسقة تسويق', 'التسويق', '2023-02-01', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36'),
(15, 'بدر ناصر', 'Badr Nasser', '1994-08-25', 'الطائف', 'male', 'O-', '2055555555', 'badr@company.com', '0565555555', 'الطائف - حي الشهداء', 'دعم فني', 'التقنية', '2023-05-15', 1, '2025-03-28 17:27:36', '2025-03-28 17:27:36');

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

--
-- Dumping data for table `employee_previous_positions`
--

INSERT INTO `employee_previous_positions` (`id`, `employee_id`, `position`, `department_id`, `start_date`, `end_date`) VALUES
(1, 1, 'مساعد مدير مشاريع', 1, '2018-01-01', '2019-12-31'),
(2, 2, 'مساعد موارد بشرية', 2, '2019-06-01', '2021-02-28'),
(3, 3, 'مندوب مبيعات مبتدئ', 3, '2017-03-01', '2019-10-31'),
(4, 4, 'مساعد تسويق', 4, '2020-01-01', '2022-05-31'),
(5, 5, 'مبرمج مبتدئ', 5, '2021-07-01', '2022-12-31'),
(6, 6, 'منسق مشاريع', 1, '2018-09-01', '2020-06-30'),
(7, 7, 'محاسب مساعد', 2, '2016-01-01', '2018-04-30'),
(8, 8, 'متدرب مبيعات', 3, '2020-03-01', '2021-08-31'),
(9, 9, 'مصمم مبتدئ', 4, '2021-01-01', '2022-11-30'),
(10, 10, 'متدبر تقنية', 5, '2022-06-01', '2023-02-28');

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
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `national_id` (`national_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `employee_previous_positions`
--
ALTER TABLE `employee_previous_positions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_previous_positions`
--
ALTER TABLE `employee_previous_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD CONSTRAINT `employee_documents_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_previous_positions`
--
ALTER TABLE `employee_previous_positions`
  ADD CONSTRAINT `employee_previous_positions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
