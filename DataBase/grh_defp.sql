-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2025 at 01:44 AM
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
  `changed_field` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `activity_date` datetime DEFAULT current_timestamp(),
  `changed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `employee_id`, `activity_type`, `details`, `changed_field`, `old_value`, `new_value`, `activity_date`, `changed_by`) VALUES
(9, NULL, 'delete', 'تم حذف الموظف: الموظف الأول', NULL, NULL, NULL, '2025-05-28 08:58:14', 1),
(12, 1, 'promotion', 'تمت الترقية من عون إدارة رئيسي إلى كاتب', 'position', 'عون إدارة رئيسي', 'كاتب', '2025-05-28 10:01:34', 1);

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
  `father_lastname` varchar(100) DEFAULT NULL,
  `mother_firstname` varchar(100) DEFAULT NULL,
  `mother_lastname` varchar(100) DEFAULT NULL,
  `birth_date` date NOT NULL,
  `birth_place` varchar(100) NOT NULL,
  `gender` enum('male','female') NOT NULL COMMENT 'النوع',
  `bloodtype` varchar(20) NOT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL COMMENT 'الحالة العائلية',
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
  `is_new_employee` tinyint(1) DEFAULT 0 COMMENT '1 if new employee, 0 otherwise',
  `vacances_remain_days` int(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `firstname_ar`, `lastname_ar`, `firstname_en`, `lastname_en`, `father_lastname`, `mother_firstname`, `mother_lastname`, `birth_date`, `birth_place`, `gender`, `bloodtype`, `marital_status`, `national_id`, `ssn`, `email`, `phone`, `address`, `position`, `department_id`, `first_hire_date`, `hire_date`, `is_active`, `is_new_employee`, `vacances_remain_days`, `created_at`, `updated_at`) VALUES
(1, 'محمد', 'بلحاج', 'Mohamed', 'Belhadj', 'عبد القادر', 'ربيعة', 'بن عيسى', '1985-07-15', 'قالمة', 'male', 'A+', 'married', '8507150098765', 123456789, 'mohamed.belhadj@gmail.com', '0550123456', 'حي الأمير عبد القادر، قالمة', 'كاتب', 1, '2015-03-10', '2026-01-01', 1, 0, 28, '2025-04-19 00:19:53', '2025-05-28 09:01:34'),
(2, 'أحمد', 'بن علي', 'Ahmed', 'Ben Ali', 'عمر', 'خديجة', 'بن محمد', '1990-11-22', 'عنابة', 'male', 'O+', 'single', '9011220012345', 234567890, 'ahmed.benali@example.com', '0551122334', 'شارع الجمهورية، عنابة', 'متصرف رئيسي', 2, '2016-05-15', '2020-06-01', 1, 0, 22, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(3, 'فاطمة', 'زهرة', 'Fatima', 'Zahra', 'عبد الرحمن', 'أمينة', 'بن عبد الله', '1988-03-08', 'قسنطينة', 'female', 'B+', 'divorced', '8803080023456', 345678901, 'fatima.zahra@example.com', '0552233445', 'حي بوعقال، قسنطينة', 'عون إدارة رئيسي', 3, '2017-02-20', '2021-03-15', 1, 0, 18, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(4, 'عبد القادر', 'موسى', 'Abdelkader', 'Moussa', 'يوسف', 'حليمة', 'بن أحمد', '1982-09-12', 'وهران', 'male', 'AB+', 'widowed', '8209120034567', 456789012, 'abdelkader.m@example.com', '0553344556', 'حي المحطة، وهران', 'محاسب إداري رئيسي', 4, '2010-08-01', '2018-09-01', 1, 0, 15, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(5, 'ليلى', 'بوعزة', 'Lila', 'Bouazza', 'مصطفى', 'سارة', 'بن عمار', '1993-05-25', 'بجاية', 'female', 'A-', 'single', '9305250045678', 567890123, 'lila.bouazza@example.com', '0554455667', 'شارع الاستقلال، بجاية', 'مترجم رئيسي', 5, '2018-11-10', '2022-01-15', 1, 1, 0, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(6, 'يوسف', 'شاوش', 'Youssef', 'Chaouch', 'عبد الحميد', 'نور', 'حسين', '1987-12-03', 'سطيف', 'male', 'O-', 'married', '8712030056789', 678901234, 'youssef.chaouch@example.com', '0555566778', 'حي 8 ماي 1945، سطيف', 'مهندس دولة', 5, '2014-07-22', '2019-04-10', 1, 0, 20, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(7, 'سميرة', 'قاسمي', 'Samira', 'Kassemi', 'خالد', 'نادية', 'بن طاهر', '1991-08-17', 'تلمسان', 'female', 'B-', 'married', '9108170067890', 789012345, 'samira.k@example.com', '0556677889', 'شارع الأمير عبد القادر، تلمسان', 'رئيس الوثائقيين أمناء المحفوظات', 1, '2016-09-05', '2020-11-20', 1, 0, 16, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(8, 'خالد', 'بن عمر', 'Khaled', 'Ben Omar', 'عمر', 'أمينة', 'بن عودة', '1984-01-30', 'الشلف', 'male', 'A+', 'divorced', '8401300078901', 890123456, 'khaled.benomar@example.com', '0557788990', 'حي أول نوفمبر، الشلف', 'رئيس المحللين', 2, '2012-04-15', '2017-08-01', 1, 0, 12, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(9, 'نور', 'حسين', 'Nour', 'Hussein', 'إبراهيم', 'حليمة', 'بن زايد', '1995-06-14', 'باتنة', 'female', 'O+', 'single', '9506140089012', 901234567, 'nour.hussein@example.com', '0558899001', 'شارع العقيد عميروش، باتنة', 'عون مكتب', 3, '2019-03-01', '2022-06-15', 1, 1, 0, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(10, 'عمر', 'بن عيسى', 'Omar', 'Ben Aissa', 'عبد الرحمن', 'سارة', 'بن عمار', '1989-04-05', 'الجزائر', 'male', 'AB-', 'married', '8904050090123', 123450987, 'omar.benaissa@example.com', '0559900112', 'حي الحرية، الجزائر', 'عون تقني', 5, '2015-10-10', '2021-02-01', 1, 0, 19, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(11, 'حليمة', 'بن زايد', 'Halima', 'Ben Zayed', 'طارق', 'ليلى', 'بوعزة', '1992-07-19', 'البليدة', 'female', 'B+', 'widowed', '9207190012345', 234561098, 'halima.bz@example.com', '0550011223', 'شارع الشهداء، البليدة', 'كاتب مديرية رئيسي', 1, '2017-12-05', '2020-07-01', 1, 0, 23, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(12, 'طارق', 'رمضان', 'Tarek', 'Ramadan', 'عبد القادر', 'فاطمة', 'زهرة', '1986-10-28', 'تيبازة', 'male', 'A-', 'married', '8610280023456', 345672109, 'tarek.ramadan@example.com', '0551122334', 'حي النصر، تيبازة', 'محلل اقتصادي', 2, '2013-06-18', '2018-09-01', 1, 0, 17, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(13, 'سارة', 'بن عمار', 'Sarah', 'Ben Ammar', 'مصطفى', 'نور', 'حسين', '1994-02-11', 'جيجل', 'female', 'O+', 'single', '9402110034567', 456783210, 'sarah.benammar@example.com', '0552233445', 'شارع 5 جويلية، جيجل', 'مهندس تطبيقي', 5, '2019-08-22', '2022-01-10', 1, 1, 0, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(14, 'مصطفى', 'بوشارب', 'Mustapha', 'Bouchareb', 'عبد الرحمن', 'أمينة', 'بن عودة', '1983-11-08', 'سكيكدة', 'male', 'AB+', 'married', '8311080045678', 567894321, 'mustapha.b@example.com', '0553344556', 'حي 20 أوت 1955، سكيكدة', 'رئيس المهندسين', 5, '2011-05-15', '2016-11-01', 1, 0, 14, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(15, 'نادية', 'بن طاهر', 'Nadia', 'Ben Taher', 'خالد', 'سميرة', 'قاسمي', '1990-09-23', 'عنابة', 'female', 'B-', 'divorced', '9009230056789', 678905432, 'nadia.bentaher@example.com', '0554455667', 'شارع العربي بن مهيدي، عنابة', 'مترجم', 4, '2016-04-10', '2019-07-15', 1, 0, 21, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(16, 'عبد الرحمن', 'بن يوسف', 'Abderrahmane', 'Ben Youssef', 'يوسف', 'حليمة', 'بن زايد', '1988-06-07', 'قسنطينة', 'male', 'A+', 'married', '8806070067890', 789016543, 'abderrahmane.by@example.com', '0555566778', 'حي الأمير عبد القادر، قسنطينة', 'عون إدارة', 3, '2014-03-01', '2018-06-01', 1, 0, 13, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(17, 'أمينة', 'بن عودة', 'Amina', 'Ben Ouda', 'طارق', 'نادية', 'بن طاهر', '1993-12-14', 'وهران', 'female', 'O-', 'single', '9312140078901', 890127654, 'amina.benouda@example.com', '0556677889', 'شارع أحمد زبانة، وهران', 'مساعد محاسب إداري', 4, '2018-09-15', '2021-12-01', 1, 1, 0, '2025-04-19 00:19:53', '2025-04-19 00:19:53'),
(18, 'إبراهيم', 'بن أحمد', 'Ibrahim', 'Ben Ahmed', 'عبد القادر', 'سارة', 'بن عمار', '1987-04-02', 'بجاية', 'male', 'B+', 'married', '8704020089012', 901238765, 'ibrahim.benahmed@example.com', '0557788990', 'شارع الشهداء، بجاية', 'عون حفظ البيانات', 1, '2015-01-20', '2019-04-15', 1, 0, 16, '2025-04-19 00:19:53', '2025-04-19 00:19:53');

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

--
-- Dumping data for table `employee_high_level_history`
--

INSERT INTO `employee_high_level_history` (`history_id`, `employee_id`, `position_id`, `start_date`, `end_date`) VALUES
(13, 1, 1, '2020-01-15', '2022-03-31'),
(14, 1, 3, '2022-04-01', NULL),
(15, 2, 5, '2019-05-10', '2021-12-15'),
(16, 3, 2, '2021-02-20', '2023-06-30');

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
(3, 2, 'عون إدارة', 2, '2016-05-15', '2018-05-15'),
(4, 2, 'متصرف', 2, '2018-05-16', '2020-05-31'),
(5, 3, 'عون مكتب', 3, '2017-02-20', '2019-02-20'),
(6, 3, 'عون إدارة', 3, '2019-02-21', '2021-03-14'),
(7, 4, 'مساعد محاسب إداري', 4, '2010-08-01', '2013-08-01'),
(8, 4, 'محاسب إداري', 4, '2013-08-02', '2016-08-01'),
(9, 4, 'محاسب إداري رئيسي', 4, '2016-08-02', '2018-08-31'),
(10, 5, 'مترجم', 5, '2018-11-10', '2020-11-10'),
(11, 5, 'مترجم رئيسي', 5, '2020-11-11', '2022-01-14'),
(12, 6, 'مهندس تطبيقي', 5, '2014-07-22', '2017-07-22'),
(13, 6, 'مهندس رئيسي', 5, '2017-07-23', '2019-04-09'),
(14, 7, 'وثائقي أمين محفوظات', 1, '2016-09-05', '2018-09-05'),
(15, 7, 'وثائقي أمين محفوظات رئيسي', 1, '2018-09-06', '2020-11-19'),
(16, 8, 'محلل اقتصادي', 2, '2012-04-15', '2015-04-15'),
(17, 8, 'محلل رئيسي', 2, '2015-04-16', '2017-07-31'),
(18, 9, 'عون مكتب', 3, '2019-03-01', '2021-03-01'),
(19, 10, 'عون تقني', 5, '2015-10-10', '2018-10-10'),
(20, 10, 'معاون تقني', 5, '2018-10-11', '2021-01-31'),
(61, 1, 'عون إدارة', 0, '2015-03-10', '2018-03-10'),
(62, 1, 'متصرف رئيسي', 0, '2018-03-11', '2022-12-31'),
(63, 1, 'متصرف', 0, '2023-01-01', '2026-01-01'),
(64, 1, 'عون إدارة رئيسي', 0, '2025-01-01', '2026-01-01');

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
-- Table structure for table `laws`
--

CREATE TABLE `laws` (
  `law_id` int(11) NOT NULL,
  `law_text` text NOT NULL,
  `law_category` varchar(50) NOT NULL COMMENT 'deduction, annual_leave, sick_leave, or multiple',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laws`
--

INSERT INTO `laws` (`law_id`, `law_text`, `law_category`, `created_at`, `is_active`) VALUES
(1, '-بمقتضى المرسوم التنفيذي رقم 08-04 المؤرخ في 09/01/2008، المتضمن القانون الأساسي الخاص بالموظفين المنتمين للأسلاك المشتركة في المؤسسات والإدارات العمومية، المعدل والمتمم', 'deduction', '2025-04-26 22:09:08', 1),
(2, '- وبمقتضى المرسوم التنفيذي رقم 09-241 المؤرخ في 22/07/2009، المتضمن القانون الأساسي الخاص بالموظفين المنتمين للأسلاك التقنية الخاصة بالإدارة المكلفة بالسكن والعمران،', 'annual_leave', '2025-04-26 22:09:08', 1),
(3, '-وبمقتضى المرسوم التنفيذي رقم 09-93 المؤرخ في 22/02/2009، المتضمن القانون الأساسي الخاص بالموظفين المنتمين للأسلاك الخاصة بالتكوين والتعليم المهنيين،', 'sick_leave', '2025-04-26 22:09:08', 1),
(4, '-بمقتضى المرسوم الرئاسي رقم 07-304 المؤرخ في 29/09/2007 الذي يحدد الشبكة الاستدلالية لمرتبات الموظفين ونظام دفع رواتبهم، المعدل والمتمم،', 'multiple', '2025-04-26 22:09:08', 1);

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
('مستخدم الأول', 'admin', '$2y$10$gtgJ7bYtqG2lQ2vJNoTy8ONM.UgiZtS53xdjcZ0CP3c97/nxOBR/S', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_activity_log_user` (`changed_by`);

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
-- Indexes for table `laws`
--
ALTER TABLE `laws`
  ADD PRIMARY KEY (`law_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee_high_level_history`
--
ALTER TABLE `employee_high_level_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `employee_previous_positions`
--
ALTER TABLE `employee_previous_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `high_level_positions`
--
ALTER TABLE `high_level_positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `laws`
--
ALTER TABLE `laws`
  MODIFY `law_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_activity_log_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

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