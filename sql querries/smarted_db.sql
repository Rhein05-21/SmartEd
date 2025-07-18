-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 01:50 AM
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
-- Database: `smarted_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `admin_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_otp` varchar(10) DEFAULT NULL,
  `reset_otp_expires` datetime DEFAULT NULL,
  `username` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `firstname`, `middlename`, `lastname`, `email`, `admin_id`, `password`, `created_at`, `updated_at`, `reset_otp`, `reset_otp_expires`, `username`) VALUES
(2, NULL, NULL, NULL, 'rheinbanasihantigle@gmail.com', 'ADMIN-75263', '$2y$10$xKU9LkOkWy1GU.Z4x6rox.3/X6euv024GoSeopbtVvAq2YjgBBw8W', '2025-06-27 12:24:17', '2025-06-30 09:06:18', '722629', '2025-06-30 11:16:18', '');

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`answer_id`, `question_id`, `answer_text`, `is_correct`) VALUES
(81, 41, 'a) Jose Protacio Rizal Mercado y Alonso Realonda', 1),
(82, 41, 'b) Jose Protacio Mercado Rizal y Alonso Realonda', 0),
(83, 41, 'c) Jose Protacio Rizal Mercado y Realonda Alonso', 0),
(84, 41, 'd) Jose Protacio Mercado Rizal Alonso y Realonda', 0),
(85, 42, 'a) 1851', 0),
(86, 42, 'b) 1861', 1),
(87, 42, 'c) 1871', 0),
(88, 42, 'd) 1881', 0),
(89, 43, 'a) El Filibusterismo', 0),
(90, 43, 'b) Noli Me Tángere', 1),
(91, 43, 'c) La Solidaridad', 0),
(92, 43, 'd) Mi Último Adiós', 0),
(93, 44, 'a) Dimasalang', 0),
(94, 44, 'b) Laong Laan', 1),
(95, 44, 'c) Plaridel', 0),
(96, 44, 'd) Taga-ilog', 0),
(97, 45, 'a) Paris and Heidelberg', 1),
(98, 45, 'b) Madrid and Barcelona', 0),
(99, 45, 'c) London and Berlin', 0),
(100, 45, 'd) Rome and Vienna', 0),
(101, 46, 'a) A la Juventud Filipina', 0),
(102, 46, 'b) Mi Primera Inspiración', 0),
(103, 46, 'c) Mi Último Adiós', 1),
(104, 46, 'd) Sa Aking Mga Kababata', 0),
(105, 47, 'a) Juan', 0),
(106, 47, 'b) Paciano', 1),
(107, 47, 'c) Saturnino', 0),
(108, 47, 'd) Jose', 0),
(109, 48, 'a) Katipunan', 0),
(110, 48, 'b) La Liga Filipina', 1),
(111, 48, 'c) Propaganda Movement', 0),
(112, 48, 'd) Illustrados', 0),
(113, 49, 'a) December 29, 1896', 0),
(114, 49, 'b) December 30, 1896', 1),
(115, 49, 'c) January 1, 1897', 0),
(116, 49, 'd) December 28, 1896', 0),
(117, 50, 'a) Fort Santiago', 0),
(118, 50, 'b) Dapitan', 1),
(119, 50, 'c) Hong Kong', 0),
(120, 50, 'd) Biñan', 0),
(121, 51, 'True', 1),
(122, 51, 'False', 0),
(123, 52, 'True', 1),
(124, 52, 'False', 0),
(125, 53, 'True', 0),
(126, 53, 'False', 1),
(127, 54, 'True', 1),
(128, 54, 'False', 0),
(129, 55, 'True', 0),
(130, 55, 'False', 1),
(131, 56, 'True', 1),
(132, 56, 'False', 0),
(133, 57, 'True', 0),
(134, 57, 'False', 1),
(135, 58, 'True', 1),
(136, 58, 'False', 0),
(137, 59, 'True', 0),
(138, 59, 'False', 1),
(139, 60, 'True', 1),
(140, 60, 'False', 0),
(141, 61, 'Paciano Rizal', 1),
(142, 61, 'Universidad Central de Madrid', 0),
(143, 61, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(144, 61, 'Crisostomo Ibarra', 0),
(145, 61, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(146, 61, 'Berlin, Germany', 0),
(147, 61, 'Teodora Alonso Realonda', 0),
(148, 61, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(149, 61, 'Rizal Monument', 0),
(150, 61, 'Rebellion, Sedition, and Formation of Illegal Associations', 0),
(151, 62, 'Paciano Rizal', 0),
(152, 62, 'Universidad Central de Madrid', 1),
(153, 62, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(154, 62, 'Crisostomo Ibarra', 0),
(155, 62, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(156, 62, 'Berlin, Germany', 0),
(157, 62, 'Teodora Alonso Realonda', 0),
(158, 62, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(159, 62, 'Rizal Monument', 0),
(160, 62, 'Rebellion, Sedition, and Formation of Illegal Associations', 0),
(161, 63, 'Paciano Rizal', 0),
(162, 63, 'Universidad Central de Madrid', 0),
(163, 63, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 1),
(164, 63, 'Crisostomo Ibarra', 0),
(165, 63, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(166, 63, 'Berlin, Germany', 0),
(167, 63, 'Teodora Alonso Realonda', 0),
(168, 63, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(169, 63, 'Rizal Monument', 0),
(170, 63, 'Rebellion, Sedition, and Formation of Illegal Associations', 0),
(171, 64, 'Paciano Rizal', 0),
(172, 64, 'Universidad Central de Madrid', 0),
(173, 64, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(174, 64, 'Crisostomo Ibarra', 1),
(175, 64, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(176, 64, 'Berlin, Germany', 0),
(177, 64, 'Teodora Alonso Realonda', 0),
(178, 64, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(179, 64, 'Rizal Monument', 0),
(180, 64, 'Rebellion, Sedition, and Formation of Illegal Associations', 0),
(181, 65, 'Paciano Rizal', 0),
(182, 65, 'Universidad Central de Madrid', 0),
(183, 65, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(184, 65, 'Crisostomo Ibarra', 0),
(185, 65, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 1),
(186, 65, 'Berlin, Germany', 0),
(187, 65, 'Teodora Alonso Realonda', 0),
(188, 65, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(189, 65, 'Rizal Monument', 0),
(190, 65, 'Rebellion, Sedition, and Formation of Illegal Associations', 0),
(191, 66, 'Paciano Rizal', 0),
(192, 66, 'Universidad Central de Madrid', 0),
(193, 66, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(194, 66, 'Crisostomo Ibarra', 0),
(195, 66, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(196, 66, 'Berlin, Germany', 1),
(197, 66, 'Teodora Alonso Realonda', 0),
(198, 66, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(199, 66, 'Rizal Monument', 0),
(200, 66, 'Rebellion, Sedition, and Formation of Illegal Associations', 0),
(201, 67, 'Paciano Rizal', 0),
(202, 67, 'Universidad Central de Madrid', 0),
(203, 67, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(204, 67, 'Crisostomo Ibarra', 0),
(205, 67, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(206, 67, 'Berlin, Germany', 0),
(207, 67, 'Teodora Alonso Realonda', 0),
(208, 67, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(209, 67, 'Rizal Monument', 0),
(210, 67, 'Rebellion, Sedition, and Formation of Illegal Associations', 1),
(211, 68, 'Paciano Rizal', 0),
(212, 68, 'Universidad Central de Madrid', 0),
(213, 68, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(214, 68, 'Crisostomo Ibarra', 0),
(215, 68, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(216, 68, 'Berlin, Germany', 0),
(217, 68, 'Teodora Alonso Realonda', 1),
(218, 68, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(219, 68, 'Rizal Monument', 0),
(220, 68, 'Rebellion, Sedition, and Formation of Illegal Associations', 0),
(221, 69, 'Paciano Rizal', 0),
(222, 69, 'Universidad Central de Madrid', 0),
(223, 69, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(224, 69, 'Crisostomo Ibarra', 0),
(225, 69, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(226, 69, 'Berlin, Germany', 0),
(227, 69, 'Teodora Alonso Realonda', 0),
(228, 69, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 1),
(229, 69, 'Rizal Monument', 0),
(230, 69, 'Rebellion, Sedition, and Formation of Illegal Associations', 0),
(231, 70, 'Paciano Rizal', 0),
(232, 70, 'Universidad Central de Madrid', 0),
(233, 70, 'Propaganda Movement (specifically through its organ, La Solidaridad)', 0),
(234, 70, 'Crisostomo Ibarra', 0),
(235, 70, '\"Sa Aking Mga Kababata\" (To My Fellow Youth)', 0),
(236, 70, 'Berlin, Germany', 0),
(237, 70, 'Teodora Alonso Realonda', 0),
(238, 70, 'A private school in Biñan (often simply referred to as the school of Maestro Justiniano Aquino Cruz)', 0),
(239, 70, 'Rizal Monument', 1),
(240, 70, 'Rebellion, Sedition, and Formation of Illegal Associations', 0);

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`id`, `name`) VALUES
(2, 'Dr. Jose Rizal'),
(3, 'Alber Einstein'),
(4, 'Gregorio Fernandez Zaide');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `isbn` varchar(255) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `pdf_path` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `isbn`, `author_id`, `category_id`, `available`, `pdf_path`, `cover_image`, `archived_at`, `archived`) VALUES
(3, 'Math in Digital Wolrd', NULL, 3, 1, 1, NULL, 'cover_6875a92ad737e.jpg', NULL, 0),
(4, 'El Filibusterismo', NULL, 2, 2, 1, NULL, 'cover_68762140894bc.jpg', NULL, 1),
(6, 'Jose Rizal: Life, Works, and Writings', NULL, 4, 2, 1, NULL, 'cover_68763ffc35a6a.jpg', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `book_requests`
--

CREATE TABLE `book_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','returned','declined') DEFAULT 'pending',
  `borrow_date` timestamp NULL DEFAULT current_timestamp(),
  `return_date` timestamp NULL DEFAULT NULL,
  `approve_date` timestamp NULL DEFAULT NULL,
  `decline_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `book_requests`
--

INSERT INTO `book_requests` (`id`, `student_id`, `book_id`, `status`, `borrow_date`, `return_date`, `approve_date`, `decline_date`) VALUES
(2, 6, 4, 'returned', '2025-07-15 09:42:09', '2025-07-15 09:43:02', '2025-07-15 09:42:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(2, 'Filipino'),
(1, 'Mathematics');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `description`, `created_at`) VALUES
(1, 'Life and Works of Rizal', 'As required by Republic Act No. 1425, a Philippine academic subject that strives to promote nationalism and a respect for the national hero\'s values and contributions to the country\'s growth, Jose Rizal\'s life and work are studied.  In addition to his important literary works, such as Noli Me Tángere and El filibusterismo, as well as his essays and correspondences, this thorough study includes his life, which includes his birth, childhood, education, travels, exile, trial, and execution.  The focus of the coursework is on comprehending Rizal\'s ideas and how they applied to Philippine culture in the 19th century and how they still have relevance now.', '2025-07-14 08:46:00'),
(2, 'test', 'Okay', '2025-07-17 09:35:37');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `course_id`, `student_id`, `created_at`, `is_approved`) VALUES
(5, 1, '2025-0001', '2025-07-17 23:00:01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `exam_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `total_questions` int(11) NOT NULL,
  `passing_score` int(11) NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `min_multiple_choice` int(11) NOT NULL DEFAULT 10,
  `min_true_false` int(11) NOT NULL DEFAULT 10,
  `min_matching` int(11) NOT NULL DEFAULT 10,
  `allowed_types` varchar(100) DEFAULT 'multiple_choice,true_false,matching'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `course_id`, `exam_name`, `description`, `duration`, `total_questions`, `passing_score`, `start_date`, `end_date`, `created_at`, `min_multiple_choice`, `min_true_false`, `min_matching`, `allowed_types`) VALUES
(23, 1, 'Prelim Exam', 'Good Luck', 720, 30, 50, '2025-07-18 07:00:00', '2025-07-18 19:00:00', '2025-07-17 17:04:30', 10, 10, 10, 'multiple_choice,true_false,Matching Type');

--
-- Triggers `exams`
--
DELIMITER $$
CREATE TRIGGER `after_exam_create` AFTER INSERT ON `exams` FOR EACH ROW BEGIN
    INSERT INTO exam_notifications (student_id, exam_id, message, is_read, created_at)
    SELECT e.student_id, NEW.exam_id,
           CONCAT('New exam available: ', NEW.exam_name),
           0,
           NOW()
    FROM enrollments e
    WHERE e.course_id = NEW.course_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `exam_attempts`
--

CREATE TABLE `exam_attempts` (
  `attempt_id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `status` enum('viewed','in_progress','finished') DEFAULT 'viewed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_attempts`
--

INSERT INTO `exam_attempts` (`attempt_id`, `exam_id`, `student_id`, `start_time`, `end_time`, `score`, `status`, `created_at`) VALUES
(0, 23, 2025, '2025-07-17 23:21:54', '2025-07-17 23:24:42', 100, 'finished', '2025-07-17 23:21:54');

-- --------------------------------------------------------

--
-- Table structure for table `exam_notifications`
--

CREATE TABLE `exam_notifications` (
  `notification_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_notifications`
--

INSERT INTO `exam_notifications` (`notification_id`, `student_id`, `exam_id`, `message`, `is_read`, `created_at`) VALUES
(0, 6, NULL, 'Your enrollment in the course \'Life and Works of Rizal\' has been approved!', 0, '2025-07-17 22:40:51'),
(0, 6, NULL, 'Your enrollment in the course \'Life and Works of Rizal\' has been approved!', 0, '2025-07-17 22:40:53'),
(0, 6, NULL, 'Your enrollment in the course \'Life and Works of Rizal\' has been approved!', 0, '2025-07-17 22:43:46'),
(0, 6, NULL, 'Your enrollment in the course \'Life and Works of Rizal\' has been approved!', 0, '2025-07-17 22:58:30'),
(0, 6, NULL, 'Your enrollment in the course \'test\' has been approved!', 0, '2025-07-17 22:59:07'),
(0, 2025, 23, 'New exam available: Prelim Exam', 0, '2025-07-17 23:04:30'),
(0, 6, NULL, 'Your enrollment in the course \'Life and Works of Rizal\' has been approved!', 0, '2025-07-17 23:21:16');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedbacks`
--

CREATE TABLE `feedbacks` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `comment_type` varchar(50) DEFAULT NULL,
  `comment_about` varchar(255) DEFAULT NULL,
  `comment_text` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `matching_pairs`
--

CREATE TABLE `matching_pairs` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `item_text` varchar(255) NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `return_date` date NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `exam_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `student_id`, `book_id`, `message`, `return_date`, `is_read`, `created_at`, `exam_id`) VALUES
(3, 6, 4, 'Your request for the book \'El Filibusterismo\' has been approved. Please return by Jul 15, 2025', '2025-07-15', 1, '2025-07-15 09:42:30', NULL),
(4, 6, 4, 'Reminder: You need to return \'El Filibusterismo\' in -1 days (due on Jul 15, 2025)', '2025-07-15', 1, '2025-07-15 09:42:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `exam_id`, `question_text`, `question_type`) VALUES
(41, 23, 'What was the full name of Jose Rizal?', 'multiple_choice'),
(42, 23, 'In what year was Jose Rizal born?\r\n', 'multiple_choice'),
(43, 23, 'Which of Rizal\'s novels exposed the abuses of the Spanish friars and colonial government in the Philippines?', 'multiple_choice'),
(44, 23, 'What was Rizal\'s pen name when he wrote for La Solidaridad?\r\n', 'multiple_choice'),
(45, 23, 'Where did Rizal pursue his ophthalmology studies after leaving the Philippines for the first time?\r\n', 'multiple_choice'),
(46, 23, 'Which poem did Rizal write on the eve of his execution?\r\n', 'multiple_choice'),
(47, 23, 'Who was Rizal\'s older brother who often supported him financially and morally?\r\n', 'multiple_choice'),
(48, 23, 'What was the secret society founded by Rizal in the Philippines upon his return in 1892, which aimed for reforms through peaceful means?\r\n', 'multiple_choice'),
(49, 23, 'On what date was Jose Rizal executed by firing squad?\r\n', 'multiple_choice'),
(50, 23, 'In which place was Rizal exiled for four years before his final arrest and trial?\r\n', 'multiple_choice'),
(51, 23, 'True or False: Jose Rizal was born in Calamba, Laguna.', 'true_false'),
(52, 23, ' True or False: Rizal\'s novel, Noli Me Tángere, was written in Spanish.', 'true_false'),
(53, 23, 'True or False: Rizal used the pen name \"Plaridel\" in his writings for La Solidaridad.', 'true_false'),
(54, 23, 'True or False: Jose Rizal\'s older brother, Paciano, was a key figure in the Philippine Revolution.', 'true_false'),
(55, 23, 'True or False: The \"Mi Último Adiós\" was written by Rizal after his execution.', 'true_false'),
(56, 23, 'True or False: Jose Rizal was a polyglot, capable of speaking more than 20 different languages.', 'true_false'),
(57, 23, 'True or False: The Katipunan, a revolutionary society, was founded by Jose Rizal.', 'true_false'),
(58, 23, 'True or False: Rizal was exiled to Dapitan for four years.', 'true_false'),
(59, 23, 'True or False: The El Filibusterismo is the first of Rizal\'s two major novels.', 'true_false'),
(60, 23, 'True or False: Jose Rizal was executed on December 30, 1896, in Bagumbayan (now Luneta).', 'true_false'),
(61, 23, 'Who was Jose Rizal\'s beloved older brother who always supported him?', 'Matching Type'),
(62, 23, 'In what famous university in Spain did Rizal continue his medical studies and also take up philosophy and letters?', 'Matching Type'),
(63, 23, 'What was the name of the organization formed by Filipino expatriates in Spain, where Rizal was an active member and contributed articles?', 'Matching Type'),
(64, 23, ' Who was the character in Noli Me Tángere believed to be Rizal\'s representation of himself and his ideals?', 'Matching Type'),
(65, 23, 'What was the title of Rizal\'s first literary work, a poem he wrote at the age of eight?', 'Matching Type'),
(66, 23, ' In which European city did Rizal finish writing Noli Me Tángere?\r\n', 'Matching Type'),
(67, 23, 'What crime was Jose Rizal accused of that led to his trial and execution?', 'Matching Type'),
(68, 23, 'Who was Rizal\'s mother, whom he wished to cure of her deteriorating eyesight?', 'Matching Type'),
(69, 23, 'What was the name of the school in Biñan, Laguna, where Rizal had his early education under Maestro Justiniano Aquino Cruz?', 'Matching Type'),
(70, 23, 'What monument stands today at the site of Rizal\'s execution in Luneta (Bagumbayan)?', 'Matching Type');

-- --------------------------------------------------------

--
-- Table structure for table `reopened_exams`
--

CREATE TABLE `reopened_exams` (
  `reopen_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `reopen_start_date` datetime NOT NULL DEFAULT current_timestamp(),
  `reopen_end_date` datetime NOT NULL DEFAULT (current_timestamp() + interval 1 hour),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `firstname` varchar(50) DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(11) DEFAULT NULL,
  `school` varchar(100) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `reset_otp` varchar(10) DEFAULT NULL,
  `reset_otp_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `email`, `password`, `created_at`, `firstname`, `lastname`, `birthdate`, `age`, `reset_token`, `reset_token_expiry`, `address`, `phone`, `school`, `verified`, `verification_token`, `is_approved`, `reset_otp`, `reset_otp_expires`) VALUES
(6, '2025-0001', 'rtigle21@gmail.com', '$2y$10$PcTfFoV10a5Q5lmwywM9/OxxTz1Kmduome7KZOGCT.8EuTilhWEZW', '2025-07-12 01:20:01', 'Rhein', 'Tigle', '2005-05-21', 20, NULL, NULL, '5492 Purok 4 Maahas, Los Baños, Laguna', '09770163408', 'Laguna University', 1, NULL, 0, NULL, NULL),
(7, '2025-0002', 'catotax786@pacfut.com', '$2y$10$lNAjCkA28tLp1wYVK9QxFefuVY.0Q2TFF.GW5RiqehQLDMyCjM0J2', '2025-07-16 02:29:24', 'Tyler', 'Posey', '2006-05-31', 19, NULL, NULL, 'Sto. Domingo Bay, Laguna', '09169508001', 'Laguna University', 1, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `answer_id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`answer_id`, `attempt_id`, `question_id`, `answer_text`, `is_correct`, `created_at`) VALUES
(0, 1, 1, '1', 1, '2025-07-16 12:39:31'),
(0, 0, 61, '141', 1, '2025-07-17 23:24:42'),
(0, 0, 62, '152', 1, '2025-07-17 23:24:42'),
(0, 0, 63, '163', 1, '2025-07-17 23:24:42'),
(0, 0, 64, '174', 1, '2025-07-17 23:24:42'),
(0, 0, 65, '185', 1, '2025-07-17 23:24:42'),
(0, 0, 66, '196', 1, '2025-07-17 23:24:42'),
(0, 0, 67, '210', 1, '2025-07-17 23:24:42'),
(0, 0, 68, '217', 1, '2025-07-17 23:24:42'),
(0, 0, 69, '228', 1, '2025-07-17 23:24:42'),
(0, 0, 70, '239', 1, '2025-07-17 23:24:42');

-- --------------------------------------------------------

--
-- Table structure for table `user_status`
--

CREATE TABLE `user_status` (
  `user_id` int(11) NOT NULL,
  `user_type` enum('admin','student') NOT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `last_activity` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `admin_id` (`admin_id`);

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn_unique` (`isbn`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `book_requests`
--
ALTER TABLE `book_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`student_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `feedbacks`
--
ALTER TABLE `feedbacks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id_unique` (`student_id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- Indexes for table `user_status`
--
ALTER TABLE `user_status`
  ADD PRIMARY KEY (`user_id`,`user_type`),
  ADD KEY `idx_online_status` (`is_online`,`last_activity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=241;

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `book_requests`
--
ALTER TABLE `book_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedbacks`
--
ALTER TABLE `feedbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`),
  ADD CONSTRAINT `books_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `book_requests`
--
ALTER TABLE `book_requests`
  ADD CONSTRAINT `book_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `book_requests_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
