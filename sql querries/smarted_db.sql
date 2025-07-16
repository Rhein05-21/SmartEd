-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 16, 2025 at 09:08 AM
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
  `question_id` int(11) DEFAULT NULL,
  `answer_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`answer_id`, `question_id`, `answer_text`, `is_correct`, `created_at`) VALUES
(0, 0, 'True', 1, '2025-07-16 07:02:08'),
(0, 0, 'False', 0, '2025-07-16 07:02:08'),
(0, 0, 'True', 1, '2025-07-16 07:02:14'),
(0, 0, 'False', 0, '2025-07-16 07:02:14'),
(0, 0, 'True', 0, '2025-07-16 07:02:33'),
(0, 0, 'False', 1, '2025-07-16 07:02:33'),
(0, 0, 'True', 1, '2025-07-16 07:02:39'),
(0, 0, 'False', 0, '2025-07-16 07:02:39'),
(0, 0, 'True', 0, '2025-07-16 07:02:56'),
(0, 0, 'False', 1, '2025-07-16 07:02:56'),
(0, 0, 'True', 1, '2025-07-16 07:02:58'),
(0, 0, 'False', 0, '2025-07-16 07:02:58'),
(0, 0, 'True', 0, '2025-07-16 07:03:13'),
(0, 0, 'False', 1, '2025-07-16 07:03:13'),
(0, 0, 'True', 1, '2025-07-16 07:03:15'),
(0, 0, 'False', 0, '2025-07-16 07:03:15'),
(0, 0, 'True', 0, '2025-07-16 07:03:24'),
(0, 0, 'False', 1, '2025-07-16 07:03:24'),
(0, 0, 'True', 1, '2025-07-16 07:03:32'),
(0, 0, 'False', 0, '2025-07-16 07:03:32');

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
(0, 'Life and Works of Rizal', 'As required by Republic Act No. 1425, a Philippine academic subject that strives to promote nationalism and a respect for the national hero\'s values and contributions to the country\'s growth, Jose Rizal\'s life and work are studied.  In addition to his important literary works, such as Noli Me Tángere and El filibusterismo, as well as his essays and correspondences, this thorough study includes his life, which includes his birth, childhood, education, travels, exile, trial, and execution.  The focus of the coursework is on comprehending Rizal\'s ideas and how they applied to Philippine culture in the 19th century and how they still have relevance now.', '2025-07-14 08:46:00');

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
(0, 0, '2025-0001', '2025-07-16 04:22:35', 1);

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
(17, 0, 'Prelim Exam', 'GoodLuck', 60, 10, 50, '2025-07-16 15:05:00', '2025-07-16 20:00:00', '2025-07-16 01:01:46', 0, 10, 0, 'true_false');

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
(0, 13, 2025, '2025-07-16 06:19:07', NULL, NULL, 'in_progress', '2025-07-16 06:19:07');

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
(0, 6, NULL, 'Your enrollment in the course \'Life and Works of Rizal\' has been approved!', 0, '2025-07-16 04:26:13'),
(0, 2025, 13, 'New exam available: Prelim Exam', 0, '2025-07-16 05:25:10'),
(0, 2025, 14, 'New exam available: Prelim Exam', 0, '2025-07-16 06:39:02'),
(0, 2025, 15, 'New exam available: Prelim Exam', 0, '2025-07-16 06:55:01'),
(0, 2025, 16, 'New exam available: Prelim Exam', 0, '2025-07-16 07:00:50'),
(0, 2025, 17, 'New exam available: Prelim Exam', 0, '2025-07-16 07:01:46');

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
  `exam_id` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('multiple_choice','true_false') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`question_id`, `exam_id`, `question_text`, `question_type`, `created_at`) VALUES
(0, 17, 'Jose Rizal was born in Calamba, Laguna.', 'true_false', '2025-07-16 07:02:08'),
(0, 17, 'Rizal\'s novel, Noli Me Tángere, was written in Spanish.', 'true_false', '2025-07-16 07:02:14'),
(0, 17, ' Rizal used the pen name \"Plaridel\" in his writings for La Solidaridad.', 'true_false', '2025-07-16 07:02:33'),
(0, 17, 'Jose Rizal\'s older brother, Paciano, was a key figure in the Philippine Revolution.', 'true_false', '2025-07-16 07:02:39'),
(0, 17, 'The \"Mi Último Adiós\" was written by Rizal after his execution.', 'true_false', '2025-07-16 07:02:56'),
(0, 17, 'Jose Rizal was a polyglot, capable of speaking more than 20 different languages.', 'true_false', '2025-07-16 07:02:57'),
(0, 17, 'The Katipunan, a revolutionary society, was founded by Jose Rizal.', 'true_false', '2025-07-16 07:03:13'),
(0, 17, ' Rizal was exiled to Dapitan for four years.', 'true_false', '2025-07-16 07:03:15'),
(0, 17, 'The El Filibusterismo is the first of Rizal\'s two major novels.', 'true_false', '2025-07-16 07:03:24'),
(0, 17, 'Jose Rizal was executed on December 30, 1896, in Bagumbayan (now Luneta).', 'true_false', '2025-07-16 07:03:32');

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
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
