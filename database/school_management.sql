-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 23, 2026 at 06:35 AM
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
-- Database: `school_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_drop_requests`
--

CREATE TABLE `add_drop_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `request_type` enum('add','drop') NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `registrar_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `add_drop_requests`
--

INSERT INTO `add_drop_requests` (`id`, `student_id`, `subject_id`, `request_type`, `reason`, `status`, `reviewed_by`, `reviewed_at`, `registrar_note`, `created_at`) VALUES
(1, 30, 1, 'drop', 'time complex', 'approved', 56, '2026-02-18 10:20:56', 'are you sure', '2026-02-18 02:20:16');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('all','students','teachers','registrar','admin','hr') DEFAULT 'all',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `posted_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `target_audience`, `priority`, `posted_by`, `created_at`, `deleted_at`) VALUES
(1, 'Trial Testing', 'hello everyone?', 'all', 'low', 1, '2026-02-13 03:14:27', '2026-02-23 09:46:44'),
(2, 'Testing', 'REgistrar', 'students', 'medium', 56, '2026-02-18 01:53:16', '2026-02-23 09:46:41'),
(3, 'Pre Trial', 'trial attacthment', 'all', 'medium', 1, '2026-02-21 07:14:45', '2026-02-23 09:46:35'),
(4, 'trial attachment', 'testing trial', 'all', 'medium', 1, '2026-02-23 01:48:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `announcement_attachments`
--

CREATE TABLE `announcement_attachments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` enum('image','video','file') NOT NULL DEFAULT 'file',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement_attachments`
--

INSERT INTO `announcement_attachments` (`id`, `announcement_id`, `file_path`, `original_name`, `file_type`, `created_at`) VALUES
(1, 3, 'uploads/announcements/ann_69995b65eb5b92.74658164.png', 'Screenshot 2026-02-19 125634.png', 'image', '2026-02-21 07:14:45'),
(3, 4, 'uploads/announcements/ann_699bb37dedf9b5.86926886.png', 'Screenshot 2026-02-19 125634.png', 'image', '2026-02-23 01:55:09'),
(4, 4, 'uploads/announcements/ann_699bb4b4cdb423.67768303.mp4', 'WIN_20251027_18_03_27_Pro.mp4', 'video', '2026-02-23 02:00:20');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `id` int(11) NOT NULL,
  `building_name` varchar(255) NOT NULL,
  `building_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`id`, `building_name`, `building_code`, `description`, `location`, `created_at`) VALUES
(1, 'Main Building', 'MB', 'Primary academic building', 'Campus Center', '2026-02-13 01:56:18'),
(2, 'Science Building', 'SB', 'Laboratories and science classrooms', 'East Wing', '2026-02-13 01:56:18'),
(3, 'Administration Building', 'AB', 'Administrative offices', 'West Wing', '2026-02-13 01:56:18');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `duration_years` int(11) DEFAULT 4,
  `total_units` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `course_code`, `description`, `department_id`, `duration_years`, `total_units`, `status`, `created_at`) VALUES
(1, 'Bachelor of Science in Computer Science', 'BSCS', 'A program that focuses on computer programming, algorithms, and software development.', NULL, 4, 148, 'active', '2026-02-18 02:34:40'),
(2, 'Bachelor of Science in Information Technology', 'BSIT', 'A program that focuses on practical application of technology in business and industry.', NULL, 4, 144, 'active', '2026-02-18 02:34:40'),
(3, 'Bachelor of Science in Mathematics', 'BSMATH', 'A program covering pure and applied mathematics.', NULL, 4, 140, 'active', '2026-02-18 02:34:40'),
(4, 'Bachelor of Science Criminology', 'BSC', 'for crim student', NULL, 4, 192, 'active', '2026-02-18 02:39:09'),
(5, 'Bachelor of Science in Hospitality and Tourism Management.', 'BSHTM', '', NULL, 4, 174, 'active', '2026-02-18 06:20:25');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `department_code` varchar(50) NOT NULL,
  `head_of_department` varchar(255) DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `department_name`, `department_code`, `head_of_department`, `office_location`, `contact_email`, `contact_phone`, `created_at`) VALUES
(1, 'Computer Science', 'CS', 'Dr. John Smith', 'Main Building, Room 205', 'cs@school.edu', NULL, '2026-02-13 01:56:18'),
(2, 'Mathematics', 'MATH', 'Dr. Jane Doe', 'Main Building, Room 210', 'math@school.edu', NULL, '2026-02-13 01:56:18'),
(3, 'English', 'ENG', 'Prof. Robert Johnson', 'Main Building, Room 215', 'english@school.edu', NULL, '2026-02-13 01:56:18'),
(4, 'Registrar', 'RD', 'registrar', 'main building', 'registrar@gmail.com', '0909091234', '2026-02-13 03:13:23');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment_details`
--


--
-- Dumping data for table `enrollment_details`
--

INSERT INTO `enrollment_details` (`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `nickname`, `dob`, `sex`, `civil_status`, `nationality`, `religion`, `place_of_birth`, `mobile_number`, `landline_number`, `home_address`, `enrollment_type`, `semester`, `school_year`, `prev_school`, `prev_school_addr`, `prev_school_year`, `father_name`, `father_occupation`, `father_contact`, `father_income`, `mother_name`, `mother_occupation`, `mother_contact`, `mother_income`, `guardian_name`, `guardian_relation`, `guardian_contact`, `guardian_address`, `emergency_contact_name`, `emergency_contact_relation`, `emergency_contact_phone`, `created_at`, `updated_at`) VALUES
(1, 64, NULL, NULL, NULL, NULL, NULL, '2000-01-05', 'Male', 'Single', 'Filipino', NULL, 'Naga Orlanes Clinic Naga City, Cebu', '09562700171', NULL, 'South Poblacion Atbang sa Dx Hardware, Naga City, Cebu', 'Returnee', '1st Semester', '2025-2026', 'Sagay National High School-Main Senior High', NULL, NULL, 'Romeo S. Villena', NULL, NULL, NULL, 'Concepcion N. Villena', NULL, NULL, NULL, 'Jhumelah V. Micabalo', NULL, NULL, NULL, 'Jhumelah', 'Siblings', '09333317030', '2026-02-23 03:39:24', '2026-02-23 04:51:01'),
(2, 65, 'Jiggy', 'N.', 'Getuaban', '', 'Jigs', '0000-00-00', 'Male', 'Single', 'Filipino', 'Roman Catholic', 'Naga Orlanes Clinic Naga City, Cebu', '09562700171', '', 'Minglanilla, Talisay City, Cebu', 'New Student', '1st Semester', '2025-2026', 'Sagay National High School-Main Senior High', 'Sagay City, Negros Occ.', '2020-2021', 'Romeo S. Villena', 'Deceased', '', '', 'Concepcion N. Villena', 'Deceased', '', '', 'Jhumelah V. Micabalo', 'Siblings', '09333317030', 'Brgy.Kinasang-an, Cebu City, Cebu', 'Jhumelah', 'Siblings', '09333317030', '2026-02-23 04:51:01', '2026-02-23 04:51:01');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `user_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `subject`, `message`, `status`, `response`, `user_reply`, `created_at`) VALUES
(1, 2, 'Suggestion', 'testing', 'resolved', 'check trial', NULL, '2026-02-19 02:24:09'),
(2, 61, 'Suggestion', 'Practice', 'resolved', 'testing trial check', 'ok done', '2026-02-19 02:43:01'),
(3, 61, 'report', 'testing trial', 'resolved', 'thank you for testing', NULL, '2026-02-23 02:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_attachments`
--

CREATE TABLE `feedback_attachments` (
  `id` int(11) NOT NULL,
  `feedback_id` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_type` enum('image','video','file') NOT NULL DEFAULT 'file',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_attachments`
--

INSERT INTO `feedback_attachments` (`id`, `feedback_id`, `file_path`, `original_name`, `file_type`, `created_at`) VALUES
(1, 3, 'uploads/feedback/fb_699bb7520ab562.35229468.jpg', '9.jpg', 'image', '2026-02-23 02:11:30');

-- --------------------------------------------------------

--
-- Table structure for table `floor_plan_routes`
--

CREATE TABLE `floor_plan_routes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_room` varchar(255) NOT NULL,
  `end_room` varchar(255) NOT NULL,
  `waypoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`waypoints`)),
  `visible_to_students` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `floor_plan_routes`
--

INSERT INTO `floor_plan_routes` (`id`, `name`, `description`, `start_room`, `end_room`, `waypoints`, `visible_to_students`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'Chapel to AVP office', 'chapel to avp', 'Chapel', 'AVP Office', '[{\"x\":562.2550685572911,\"y\":558.7019128789684},{\"x\":262.3960697598811,\"y\":433.4671148269287},{\"x\":254.89959478994584,\"y\":134.39894037429644},{\"x\":116.21480784614373,\"y\":128.7914121033096}]', 1, 1, '2026-02-16 04:34:28', '2026-02-16 05:38:47'),
(3, 'HR to Clinic', 'clinic', 'HR', 'Clinic', '[{\"x\":181.80896383307714,\"y\":465.2431083625209},{\"x\":264.2701885023649,\"y\":455.89722791087615},{\"x\":256.77371353242967,\"y\":134.39894037429644},{\"x\":655.9610056814817,\"y\":136.2681164646254}]', 1, 1, '2026-02-16 05:05:11', '2026-02-16 05:38:45'),
(4, 'chapel to louging room', '', 'Chapel', 'Lounging Room', '[{\"x\":558.5068310723234,\"y\":561.4018307292921},{\"x\":571.6256622697101,\"y\":438.0362087675813},{\"x\":571.6256622697101,\"y\":318.40893898652837},{\"x\":734.6739928658018,\"y\":314.67058680587047},{\"x\":738.4222303507694,\"y\":400.65268696100225}]', 1, 1, '2026-02-16 06:20:36', '2026-02-16 06:20:36'),
(6, 'Banko to BED', '', 'Banko Maximo', 'BED Principal', '[{\"x\":69.36183928404841,\"y\":454.02805182054715},{\"x\":262.3960697598811,\"y\":448.4205235495603},{\"x\":260.52195101739727,\"y\":136.2681164646254},{\"x\":69.36183928404841,\"y\":134.39894037429644}]', 1, 1, '2026-02-19 01:27:22', '2026-02-19 01:27:22');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `midterm_grade` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `remarks` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `subject_id`, `midterm_grade`, `final_grade`, `semester`, `school_year`, `remarks`, `created_at`) VALUES
(1, 60, 5, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 04:37:54'),
(2, 61, 5, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 04:37:55'),
(3, 30, 5, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 04:37:56'),
(4, 60, 6, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 05:33:08'),
(5, 61, 6, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 05:33:08'),
(6, 30, 6, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 05:33:08'),
(7, 60, 3, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 05:52:23'),
(8, 61, 3, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 05:52:23'),
(9, 30, 3, 2.00, 2.00, '1st Semester', '2026-2027', 'Passed', '2026-02-21 05:52:23');

-- --------------------------------------------------------

--
-- Table structure for table `grade_submissions`
--

CREATE TABLE `grade_submissions` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `teacher_note` text DEFAULT NULL,
  `registrar_note` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_data` longblob DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `grade_submissions`
--

INSERT INTO `grade_submissions` (`id`, `teacher_id`, `subject_id`, `section_id`, `semester`, `school_year`, `status`, `teacher_note`, `registrar_note`, `file_path`, `reviewed_by`, `reviewed_at`, `submitted_at`, `file_data`, `file_name`, `deleted_at`) VALUES
(1, 2, 5, 1, '1st Semester', '2026-2027', 'approved', '', '', '/school-management-output/uploads/grade_sheets/GradeSheet_2_5_1_1771643739.xlsx', 56, '2026-02-21 11:15:57', '2026-02-21 03:15:39', NULL, NULL, NULL),
(2, 2, 5, 1, '1st Semester', '2026-2027', 'approved', '', '', '/school-management-output/uploads/grade_sheets/GradeSheet_2_5_1_1771644107.xlsx', 56, '2026-02-21 11:22:36', '2026-02-21 03:21:47', NULL, NULL, NULL),
(3, 2, 5, 1, '1st Semester', '2026-2027', 'rejected', '', '', '/school-management-output/uploads/grade_sheets/GradeSheet_2_5_1_1771645216.xlsx', 56, '2026-02-21 12:02:04', '2026-02-21 03:40:16', NULL, NULL, NULL),
(4, 2, 5, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:/xampp/htdocs/school-management-output/uploads/grade_sheets/GradeSheet_2_5_1_1771647553.xlsx', 56, '2026-02-21 12:19:30', '2026-02-21 04:19:13', NULL, NULL, NULL),
(5, 2, 6, 1, '1st Semester', '2026-2027', 'approved', '', '', '/school-management-output/uploads/grade_sheets/GradeSheet_2_6_1_1771647691.xlsx', 56, '2026-02-21 12:21:51', '2026-02-21 04:21:31', NULL, NULL, NULL),
(6, 2, 3, 1, '1st Semester', '2026-2027', 'rejected', '', '', '/school-management-output/uploads/grade_sheets/GradeSheet_2_3_1_1771647782.xlsx', 56, '2026-02-21 12:23:23', '2026-02-21 04:23:02', NULL, NULL, NULL),
(7, 2, 3, 1, '1st Semester', '2026-2027', 'approved', '', '', '/school-management-output/uploads/grade_sheets/GradeSheet_2_3_1_1771647884.xlsx', 56, '2026-02-21 12:25:04', '2026-02-21 04:24:44', NULL, NULL, NULL),
(8, 2, 5, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:\\xampp\\htdocs\\school-management-output\\uploads\\grade_sheets\\GradeSheet_2_5_1_1771648252.xlsx', 56, '2026-02-21 12:31:11', '2026-02-21 04:30:52', NULL, '', NULL),
(9, 2, 5, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:\\xampp\\htdocs\\school-management-output\\uploads\\grade_sheets\\GradeSheet_2_5_1_1771648412.xlsx', 56, '2026-02-21 12:34:01', '2026-02-21 04:33:32', NULL, '', NULL),
(10, 2, 6, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:\\xampp\\htdocs\\school-management-output\\uploads\\grade_sheets\\GradeSheet_2_6_1_1771649111.xlsx', 56, '2026-02-21 12:46:35', '2026-02-21 04:45:11', NULL, '', NULL),
(11, 2, 6, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:\\xampp\\htdocs\\school-management-output\\uploads\\grade_sheets\\GradeSheet_2_6_1_1771650348.xlsx', 56, '2026-02-21 13:06:13', '2026-02-21 05:05:48', 0x504b030414000600080000002100a404cfe97101000098050000130008025b436f6e74656e745f54797065735d2e786d6c20a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000c494cf6ec2300cc6ef93f60e55ae531be0304d138503db8e1bd2d8036489a1116912c581c1dbcf0d7f344d1d08516997466de2effbd98d3d1c6f6a93ad21a076b664fda2c732b0d2296d1725fb98bde40f2cc328ac12c65928d916908d47b737c3d9d60366146db164558cfe91739415d4020be7c1d2cedc855a447a0d0bee855c8a05f041af77cfa5b3116ccc63a3c146c327988b9589d9f3863eef48021864d96477b0f12a99f0de68292291f2b555bf5cf2bd434191e90c56dae31d6130deead0ecfc6db08f7ba3d204ad209b8a105f454d187c63f8970bcb4fe796c56991164a379f6b09cac9554d1528d007100a2b80589b22ad452db43d709ff04f8791a7a5df3148935f12be9063f04f1c91ee1df0f4bcbe1449e64ce218b706b0ebdf9f44cf395722807a8f813ab473809fda6738a4307252d155edb80847dd53fed43fd3e03cd224097039c0615434d1b927210851c37158b435ddd191a6d0d5194333e714a8166f9ee6eae81b0000ffff0300504b030414000600080000002100b5553023f40000004c0200000b0008025f72656c732f2e72656c7320a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000ac924d4fc3300c86ef48fc87c8f7d5dd9010424b774148bb21547e8049dc0fb58da3241bddbf271c10541a8303477fbd7efccadbdd3c8deac821f6e234ac8b12143b23b677ad8697fa7175072a2672964671ace1c41176d5f5d5f699474a792876bd8f2aabb8a8a14bc9df2346d3f144b110cf2e571a0913a51c86163d99815ac64d59de62f8ae01d54253edad86b0b737a0ea93cf9b7fd796a6e90d3f88394cecd29915c8736267d9ae7cc86c21f5f91a5553683969b0629e723a22795f646cc0f3449bbf13fd7c2d4e9cc852223412f832cf47c725a0f57f5ab434f1cb9d79c43709c3abc8f0c9828b1fa8de010000ffff0300504b030414000600080000002100fe69ea570a010000cc0300001a000801786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c7320a2040128a0000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000bc934f4bc43010c5ef82df21ccdda6adba886cba0745d8abae1f20a4d3a66c9b94ccf8a7dfde50b17561a997e271de90f77e3c32dbdd67d78a770cd478a7204b5210e88c2f1b572b783d3c5ddd8120d6aed4ad77a86040825d7179b17dc656737c44b6e9494417470a2c737f2f25198b9da6c4f7e8e2a6f2a1d31cc750cb5e9ba3ae51e669ba91e1b70714279e625f2a08fbf21ac461e863f2dfdebeaa1a838fdebc75e8f84c84e4c885d150871a59c1387e8b591241419e67c8d764f8f0e1481691678e4922396ef22598ec9f61169bd9ac0963746b1eac6edcdccc242d3572bb2604591db07ce1102f806690137909e66655181eda7870d387a571fe89972737587c010000ffff0300504b030414000600080000002100ae79cc62f70100000b0400000f000000786c2f776f726b626f6f6b2e786d6ca4534d8fda3010bd57ea7fb07c5ff25116958864d5d22fa4aa425d76b97031ce402cfc91dace02ffbe636769d372d9aa97d81ec76fe6bd3733bb3b29499ec03a617449b3514a09686e6aa1f7257d587dba794b89f34cd74c1a0d253d83a377d5eb57b3a3b187ad31078200da95b4f1be2d92c4f106147323d382c69b9db18a793cda7de25a0bac760d805732c9d37492282634ed110afb120cb3db090e1f0cef1468df835890cc63f9ae11adbba029fe1238c5eca16b6fb8512d426c8514fe1c412951bc58ecb5b16c2b91f629bbbd20e3f60a5a096e8d333b3f42a8a42ff28a6f962659d653ae663b21e1b1979db0b6fdc654c8222991ccf98fb5f0509774824773843f02b66bdf7742e26d361ee7294daa5f562c2d41e7a0c75a35c2ad9f3d0a3f219f77d283d5ccc3dc688ff23d0bffbf5245ec7963d018f21d7e74c202f64350ac9ae197f1826ddd92f986745696745e6c1e1c32df2ce7e3e9e662a5db0cf465d7e6fd83c28c07be0912ee8beaf77f93af66a17b1f051cdd6f05c3919cd642d7e658529c85f3607f8ce1b5a87d53d27c9ca678dfc7be80d837bea4d3db0c638c7bf1042bb6458b62258344b1fb31615c898eae7fb6ac06721fe602672d2c8be02e25b610b8b18b3ac20c9fdcfbae0e067e156ef8261fbcc963ea4b3ece240fed814b049fe4d3ec4dfce332c7d54f000000ffff0300504b0304140006000800000021004d8f947caf0200006e0600000d000000786c2f7374796c65732e786d6cb4556d6f9b3010fe3e69ff01f93b35d0908508a896a64895ba6a523a695f1d308955bf20db7464d3fefbce4012aa4e7be9b42ff1f9383ff7dc736727bdea04f79ea8364cc90c851701f2a82c55c5e42e439f1e0a7f813c6389ac08579266e8400dbacadfbe498d3d70bad9536a3d809026437b6b9b25c6a6dc5341cc856aa8842fb5d28258d8ea1d368da6a432ee90e0380a823916844934202c45f9272082e8c7b6f14b251a62d99671660f3d16f244b9bcdd49a5c99603d52e9c91d2ebc2b98e8e197ad78b2482955a1955db0b00c5aaae59495f724d708249794602d8d72185310ea2a1f03cad95b4c62b552b2dc80fe88ef4f251aa2fb2709f9c7388ca53f3d57b221c3c11c2795a2aaeb467416ca835741e49041d22ae09675bcd9cb32682f1c3e0eecff5fd19e30403b55c14763cc6c5c021c6f98955e40880234f41704bb52c60e38df6c3a181f412666380e9e37e13bdd3e41046f1e400ee13e6e956e90a66f1acc7d195a79cd616886ab6dbbbd5aa067eb7ca5a68599e568cec9424dc9532809c0c28a7a49c6fdcbc7eae9f6177b5275b51087b5b650826df897034a190d11cf0868dc39fa20dd8135827d6dfc37a5d3d26234dc30ff7add8525df43767ecedffc0ec2b01ee13819ec9732ad4739395a17b4788c38c1ec96e5bc62d933f910630abee2c76e07a6dddb5ecdb70ca029a57b4262db70fa78f193adb1f68c55a919ca23eb227657b880c9ded216ae672d0cede19186458bd56b30c7dbb59bd4bd63745e42f82d5c29f5dd2d84fe2d5da8f67d7abf5ba488228b8fe3e791ffee175e8df32687f385b1a0e6f881e8b1d4bdc9c7d199a6ceedc48f7171803ed29f7249a07efe330f08bcb20f46773b2f017f3cbd82fe2305acf67ab9bb88827dce357be47010ec3e37bd485f1d232413993c75e1d3b34f5429360fb8b225c297d27f0f98f22ff010000ffff0300504b03041400060008000000210036e85a35140300002809000018000000786c2f776f726b7368656574732f7368656574322e786d6c9c565d6fda30147d9fb4ff60e5bd848494d208a8281fa5d2264d6bb73d1bc701ab499cd9e6a3ffbed74e02b14b45d517fc7172ceb5cfb57d19de1df20ceda8908c17232fe8743d440bc21356ac47de9fe7c5d5c04352e122c1192fe8c87ba5d2bb1b7fff36dc73f12237942a040a851c791ba5cad8f725d9d01ccb0e2f690148ca458e150cc5da97a5a03831a43cf3c36eb7efe798155ea5108bcf68f0346584ce38d9e6b4509588a01956b07eb961a56cd472f219b91c8b976d7945785e82c48a654cbd1a510fe5247e5c175ce05506fb3e0411268db619bc93cf19115cf2547540ceaf16fa7ecfb7fead0f4ae361c26007da7624683af22641bc8c3c7f3c34fefc65742f5b7da4f0ea896694289a409a3ca478f983a66a4ab30cb89187743e569cbf68e6237cd38510d23074084c14dbd1eaeb47f85afe3741a10b11fd63c876bf09bf3019fc25504253bccdd46fbe5f52b6de2858c73538a28d8993d71995043202813be1b556253c0309f84539d3470b1cc507d3ee59a236d08b3a831b50205ba978feaf9eaca91529ac49d0d6a4707091d4ab49d036a4f022093c31cbd34ed62bb94c82c51b12b44da4fec548fd9a74738a74d908b8852612b4cdf2ba1f45f22be74d526758e1f150f03d82db05299025d677358841a8ca6047a7e07c12217b9a36d1c76de4493850bb717fe8efe094901abb6f633736366d63031b9bb5b15b1b9bb7b1d0115db4c1a06b331f2c30b0c1a5058647d007778e16c141fb9245c03b5a14f41c8f2c30724cb2c06bc7250b74ac9fb7c1d0f177119a6c9d766932f9707676d916ea9d5cb38c81cbf42563807732c659e4bd053aa760da064327d1338be91a63311dd945efac316767976da18f8cd18fe9572e15f08ec684ce51bdb7402789530b74cedaac0d06ae316db0e758ba88ce1a7376160ad569f1ef8ca9ca49f5f294784d7f62b166854419d42b5d1ee0e11355fd307da86466169ea1155750099ad106fe2c50787fba1dc843cab96a06505c98aecc34990bc1055499f6b0a96dbaa0066812c5f3084dc2781ea2492f9e8354b1cd57543c292d3091cff4a02b99a9838eaa7ffc8f337e030000ffff0300504b030414000600080000002100300f886bed060000de1d000013000000786c2f7468656d652f7468656d65312e786d6cec594b6f1b3710be17e87f20f69e58b225c7362207962c256de2c4b095143952bbd42e63ee724152b6752b9263810245d3a29702bdf550b40d9000bda4bfc66d8a3605f2173a2457aba545f995047d45077b1fdf0ce7cd19eed56b872943fb4448cab35650bf5c0b10c9421ed12c6e0577fbbd4b2b01920a6711663c23ad604c64706dfdfdf7aee23595909420a0cfe41a6e058952f9dac2820ce1319697794e327837e422c50a6e45bc10097c007c53b6b058ab2d2fa4986601ca700a6cef0c873424a8af5906eb13e65d06b79992fa41c8c4ae664d1c0a838df6ea1a21c7b2c304dac7ac15c03a113fe89343152086a58217ada0667ec1c2fad505bc5610313587b642d733bf82ae2088f616cd9a221e948bd67b8dd52b9b257f03606a16d7ed763bdd7ac9cf00701882a656962acf466fa5de9ef0ac80ece52cef4ead596bb8f80affa5199957dbed7673b590c53235207bd998c1afd4961b1b8b0ede802cbe39836fb4373a9d65076f4016bf3c83ef5d595d6eb878034a18cdf666d0daa1bd5ec1bd840c39bbe185af007ca556c0a728888632baf412439ea979b196e2075cf400a0810c2b9a2135cec9108710c51d9c0e04c57a01bc4670e58d7d14ca99477a2d24434173d50a3ecc3164c494dfabe7dfbf7afe14bd7afee4e8e1b3a3873f1d3d7a74f4f047cbcb21bc81b3b84af8f2dbcffefcfa63f4c7d36f5e3efec28f9755fcaf3f7cf2cbcf9ffb81904153895e7cf9e4b7674f5e7cf5e9efdf3df6c037041e54e17d9a12896e9303b4c353d0cd18c6959c0cc4f928fa09a60e054e80b7877557250ef0f618331fae4d5ce3dd13503c7cc0eba3078eacbb891829ea59f966923ac02dce599b0baf016eeab52a16ee8fb2d8bfb81855713b18effbd6eee0cc716d779443d59c04a563fb4e421c31b719ce148e494614d2eff81e211eedee53ead8758b86824b3e54e83e456d4cbd26e9d381134853a21b3405bf8c7d3a83ab1ddb6cdd436dce7c5a6f927d170909819947f83e618e19afe391c2a98f651fa7ac6af05b58253e2177c722ace2ba5281a763c238ea46444a1fcd1d01fa569c7e1343bdf2ba7d8b8d53172914ddf3f1bc8539af2237f95e27c169ee9599664915fb81dc8310c5689b2b1f7c8bbb19a2efc10f389bebee7b9438ee3ebd10dca5b123d23440f49b91f0f8f23ae16e3e8ed910135365a0a43b953aa5d949659b51a8dbefcaf6641fdb804dcc973c378e15eb79b87f6189dec4a36c9b4056cc6e51ef2af4bb0a1dfce72bf4bc5c7ef375795a8aa14a4f7b6dd379a7731bef21656c578d19b9254def2d61038a7af0d00c0566322c07b13c81cba2cd7770b1c0860609ae3ea22ad94d700e7d7bdd8c91b12c58c712e55cc2bc681e9b81961ce36d46540aadbb99369b7a0eb1954362b5c523fb78a93a6f966cccf4199b9976b2d0926670d6c596aebcde62752bd55cb3b9aad58d68a6283aaa952a830f67558387a535a1b341d00f81959761ecd7b2c3bc831989b4dded2c3e718b5efa2db9a8d0da2a92e0885817398f2baeab1bdf4d4268125d1ed79dcf9ad540395d0813169371f5c2469e30981a59a7ddb16c625935b758860e5ac16a73b119a010e7ad6008932e5ca639384dea5e10b3188e8b42256cd49e9a8b26daa61aaffaa3aa0e8717369166a2ca49e35c48b58965627d685e15ae629999cb8dfc8bcd860eb637a3800dd40b48b1b40221f2b7490176745d4b864312aaaab32b4fccb185011495908f1411bb497480066c247630b81f6caaf589a884030b93d0fa064ed7b4b5cd2bb7b61675ad7aa66570f6396679828b6aa94f67261967e126df4a19cc9d95d68807ba796537ca9d5f159df16f4a956a18ffcf54d1db019c202c45da03211cee0a8c74beb6022e54c2a10ae5090d7b02cebd4ced806881135a780dc6872366f35f907dfddfe69ce561d21a0641b5436324286c272a11846c435932d1770ab37ab1f55896ac606422aa22aeccadd803b24f585fd7c0655d83039440a89b6a529401833b1e7fee7d91418358f728ffd4c6c526f3797777bdb9db0ec9d29fb19568548a7e652b58f5b7332737185311ceb201cbe972b662cd68bcd89cbbf3e856addacfe4700e84f41fd8ffa80899fd5ea137d43edf81da8ae0f383151e41545fd2550d224817487b3580bec73eb4c1a459d9158ae6f42d7641e5ba90a5176954cf69ecb28972977372f1e4bee67cc62e2cecd8ba1a471e5383678fa7a86e8f267388718cf9d055fd16c5070fc0d19b70ea3f62f6eb94cce1cee441be2d4c740d78342e2e99b41bae8d3a3dc3d82665870c118d0e27f3c7b141a3f8d853363680362312045a49b8e41b1a5c421d9805a9dd2d4be2c5d3894b0ab33294ec92d81ca8f918c0f7b142643dda9995753367b5d657134bb1ec754c7606e159e6339977ce3aabc9eca078a2a32e60327578b2c90a4b81f166030fbe700a0cc3a9fd5e059b8e2d2a2664d7ff020000ffff0300504b03041400060008000000210086bb77c4b6040000fb11000018000000786c2f776f726b7368656574732f7368656574312e786d6c9c58cd92e23610bea72aefe0f221355bb58bb18119f0025b2cfea32ac9a6c8ece6ec3132b8c6b6882d60e6b60f9127cc93a4256363f518b3f105d9dfa76ee9eb9684dad34f2f49ac1c499647349da97aafaf2a240de8264ab733f5eba3f361ac2a39f3d38d1fd394ccd45792ab9fe63fff343dd1ec39df11c214f090e63375c7d8ded4b43cd891c4cf7b744f5260429a253e83d76cabe5fb8cf81b6194c49ad1efdf6b891fa56ae1c1cc7ec4070dc32820160d0e094959e12423b1cf60fef92edae7a5b724f81177899f3d1ff61f029aecc1c5531447ec55385595243057db9466fe530cba5ff4a11f94bec5cb1bf749146434a721eb813bad98e85bcd136da281a7f9741381021e762523e14c5de8a6a70f556d3e1501fa1691535e7b5618ddff4a42b624710c9d87aac213f044e933efb9daccd43ef8cc494c021e0ac587e6488ade2b7d0049fc5b0cc39f610cad1aa4fe5c0ee888a4fd91291b12fa8798ade9c923d176c760858c20083c16e6e6d52279004980a17bc6887b0d680c2ee0574922be9a2088fe8b684fd186ed66eaa8377e0007c1216734f9abc0f4b36561639c6da03ddbe8c39b46204f0c04edd9c818df3482100a231eca62268671d308262f8ca02da777dbe8fe6cf4f07f34c1be1323415b8ed4bf363dad08bcc8a9e5337f3ecde84981fd0419c8f73edf9dba098e8a04f6780a9a7308c9e3660b6e27ac21b939acabe3bc3fd58eb05482738fcf650f9e756eb2c48085011b030e065c0c7835400349952e581d9d74713b59978e74953d2a5d18b0306063c0c1808b01af0648baf83eed922f6e27eb3290aeb247a50b0316066c0c38187031e0d5004917ecb14ebab89dac6b8074953d2a5d18b0306063c0c1808b01af0648ba602375d2c5ed645d43a4abec51e9c28085011b030e065c0c783540d2052755275d60579d1823a4a8ceddcbdcb2ce3dc89c55e7c63267d7b989cc39754e47c7972b91e80cf024f2b291a408f16b51979d3a16a7293e75c05b15371d2df1a544a2756249240ab92d9128e64e3113744eb88da8577734b84c5e8ac8a46344262222f8bc026f9788a0b42f2512e5ddaa9306cabb2d59e28834cec46d44bdbaa36b11d1e132ddedef97dfe58e737cd6717f55500cb48696328be269c92cf26c4bac8ee3022c9f0e5e2acdb027f9ba1a9aaeffe03a185e16063a2b3eb7b2cb56d66a656d8935d02e74809d4fc3f9cab1d7eb2febbbc5377bbd70ed3b676c3a7affdd7bf5dfefffa8efa65ad818c6665b776cbab76cbd6b739636a6b8fd77ba05c2df794bacdbd8251ff4aaadd5cada126ba043cd69b57581e599587ef9fafbe3cab92ba2f85efd65cb3ecee0de5b26019d0bde35a77220bb5e63a0ae6b0b641bbb6cb5b55a595b628d37dbba6d5c176c1b0319b38fb538a2838457b08d4a8b3816556751a12424db8afa3457027ae055e40806acd05a51cceb438c1b26dca11bf0810977d0067c68c21dae011f997007e2f5f0653af3e9dedf92dffc6c1ba5b91243cdcd0b5cb8136445052c9ea11a17285ca09e288362b67cdbc1170e02f550bf071b20a494952f3048c43f27908d9d6534833ab9fe5a96e7fc2bc083b2d0f9c70043598c4d7bac2c26a63d01ac6fc2390d2d68d187d00ee070192862e9a687e489647f32ee7e913f92175ea90b5d684cadfa6c33ff0f0000ffff0300504b030414000600080000002100ff9ef85b05020000dd04000014000000786c2f736861726564537472696e67732e786d6c94544d6fdb300cbd0fd87f2074ea0e893f52349de1b8cbd2a4cdd00443ec6ed85193d9589d2c65925cb4437ffce4184131b919b68b61f2914f7c7cb2d38bc75ac0036ac3959c9068181240c954c9e576426e8bc5e09c80b1549654288913f284865c646fdfa4c65870bdd24c4865ed2e0902c32aaca919aa1d4a87dc295d53eb42bd0dcc4e232d4d85686b11c4617816d4944b024c35d24ec8f8944023f9cf06675d6214932c353c4b6d76b5995ece21bf9ecf8b34b0591ab4e90eca9beff7c86c022b6adbb32d6706b80417c04a95a8257c555a9470b29a16d71085d1bb1e85eb77d213f8982f8b4104533869df7a756e2e6dd09551a752280dea0e72c6ddaeb03d71293bb98e0a0a649554426d9f009e01be21d570830f281288dcd2f6710be458a3b1a8bbf421ea0dc82aa5c4be2b81388ccf06ee31de33df4a6e4d0223bf65ad863d16db94282d2c2f8f216b5aa38f75a2fdec8b201f59f1d2e9a9e14ad3b247b6e0928ad7a18df34eff30bdd166b3413c1a8461189d8fe2b10f7f521595120d144a386d5c2abfe2bfccf29b0f5e79f9e08f1be8bc386d071cf54cd8b07b8eb01ec2172edc78f41f679ba97ad7b8251e2e97df7638308c7cc47416f73dfc3be1d47dfc748b89dff7991a83652fbda05cbc929ebbcf59f81435437bb0267c1f451fb66dd590a9daafd4edb27e1dc7f34edbf1826eda17dac0fd9eb2df000000ffff0300504b03041400060008000000210021556ba28a0100002403000010000801646f6350726f70732f6170702e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c92414fe3301085ef48fb1f2cdfa943592154394608d8e5c08a4a297036f6a4b170edc8338ddafdf5388d28097b5889dbccbca7a7cf339657bb8d671d24743194fc6c567006c144ebc2bae44fab5fa7979c21e960b58f014abe07e457eac7895ca6d8422207c87244c0923744ed4208340d6c34ceb21cb252c7b4d194dbb416b1ae9d81db68b61b0824e64571216047102cd8d3f618c887c44547df0db5d1f47cf8bcdab71958c9ebb6f5ce68caaf547f9c4911634dec6e67c04b311665a6abc06c93a3bd2aa418b7b232dac34d0e56b5f608527c0ee43de87e694bed122ad9d1a203433131747ff3dae69cbd6a841ea7e49d4e4e07ca58bd6d680eb56f91927a89e90d1b004229b261181ecab1775cbb9f6a7e30e4626aec0306902c4c11578e3ce063bdd489fe477c601878079cdf495b60554f39863ce256b4b5f9c4ecc1e1c430e1f942f0e0c21b3eb5ab78ab093e363c1dcaaad1096c3ecaf102c781bccfcb4dbe0fb969745883fdf0fc2bf4ffe179f8f4eaec62569c17f9d4a399149fdf5bbd030000ffff0300504b0304140006000800000021005f595248a4000000e000000010000000786c2f63616c63436861696e2e786d6c5cce410ac2301005d0bde01dc2ec6dda2a22d2b40ba15e400f10d2b12924939209a2b7378254713330efc3e737ddc33b71c7c853200555518240326198685470bdf49b03084e9a06ed02a182273274ed7ad518edccc9ea89446e205660539a8f52b2b1e835176146cac92d44af537ee328798ea807b688c93b5997e55efa5c006d63445470aeb620a63c02847b5fb9f8eee35fa9ffa4af7e442edbda17000000ffff0300504b030414000600080000002100d7fa94b73c0100005902000011000801646f6350726f70732f636f72652e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c925f4bc33014c5df05bf43c97b9bb41b6386b603277b723070a2f81692bb2dd8fc218976fbf6a6edac1bfae463ee39f777cfbda45c1c55937c82f3d2e80ae5194109686e84d4fb0a3d6f57e91c253e302d58633454e8041e2deadb9b925bca8d838d33165c90e09348d29e725ba143089662ecf90114f35974e828ee8c532cc4a7db63cbf83bdb032e0899610581091618ee80a91d89e88c147c44da0fd7f400c13134a040078ff32cc73fde004ef93f1b7ae5c2a96438d9b8d339ee255bf0411cdd472f4763dbb6593be963c4fc397e5d3f3ef5aba65277b7e280ea5270ca1db0605cbd594e6725be2874c76b980feb78e79d04717fea3c7725fe5d8f9c3ef6000391c4207488fdadbc4c960fdb15aa0b52cc5252a405d9922925133a9dbf7563affabb6043419d87ff9bf80da8fbdcd79fa1fe020000ffff0300504b01022d0014000600080000002100a404cfe971010000980500001300000000000000000000000000000000005b436f6e74656e745f54797065735d2e786d6c504b01022d0014000600080000002100b5553023f40000004c0200000b00000000000000000000000000aa0300005f72656c732f2e72656c73504b01022d0014000600080000002100fe69ea570a010000cc0300001a00000000000000000000000000cf060000786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c73504b01022d0014000600080000002100ae79cc62f70100000b0400000f0000000000000000000000000019090000786c2f776f726b626f6f6b2e786d6c504b01022d00140006000800000021004d8f947caf0200006e0600000d000000000000000000000000003d0b0000786c2f7374796c65732e786d6c504b01022d001400060008000000210036e85a3514030000280900001800000000000000000000000000170e0000786c2f776f726b7368656574732f7368656574322e786d6c504b01022d0014000600080000002100300f886bed060000de1d0000130000000000000000000000000061110000786c2f7468656d652f7468656d65312e786d6c504b01022d001400060008000000210086bb77c4b6040000fb11000018000000000000000000000000007f180000786c2f776f726b7368656574732f7368656574312e786d6c504b01022d0014000600080000002100ff9ef85b05020000dd04000014000000000000000000000000006b1d0000786c2f736861726564537472696e67732e786d6c504b01022d001400060008000000210021556ba28a010000240300001000000000000000000000000000a21f0000646f6350726f70732f6170702e786d6c504b01022d00140006000800000021005f595248a4000000e0000000100000000000000000000000000062220000786c2f63616c63436861696e2e786d6c504b01022d0014000600080000002100d7fa94b73c01000059020000110000000000000000000000000034230000646f6350726f70732f636f72652e786d6c504b0506000000000c000c0004030000a72500000000, 'Grades_MATH 101_BSIT-1 A_2026-2027.xlsx', NULL),
(12, 2, 6, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:\\xampp\\htdocs\\school-management-output\\uploads\\grade_sheets\\GradeSheet_2_6_1_1771651173.xlsx', 56, '2026-02-21 13:19:56', '2026-02-21 05:19:33', 0x504b030414000600080000002100a404cfe97101000098050000130008025b436f6e74656e745f54797065735d2e786d6c20a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000c494cf6ec2300cc6ef93f60e55ae531be0304d138503db8e1bd2d8036489a1116912c581c1dbcf0d7f344d1d08516997466de2effbd98d3d1c6f6a93ad21a076b664fda2c732b0d2296d1725fb98bde40f2cc328ac12c65928d916908d47b737c3d9d60366146db164558cfe91739415d4020be7c1d2cedc855a447a0d0bee855c8a05f041af77cfa5b3116ccc63a3c146c327988b9589d9f3863eef48021864d96477b0f12a99f0de68292291f2b555bf5cf2bd434191e90c56dae31d6130deead0ecfc6db08f7ba3d204ad209b8a105f454d187c63f8970bcb4fe796c56991164a379f6b09cac9554d1528d007100a2b80589b22ad452db43d709ff04f8791a7a5df3148935f12be9063f04f1c91ee1df0f4bcbe1449e64ce218b706b0ebdf9f44cf395722807a8f813ab473809fda6738a4307252d155edb80847dd53fed43fd3e03cd224097039c0615434d1b927210851c37158b435ddd191a6d0d5194333e714a8166f9ee6eae81b0000ffff0300504b030414000600080000002100b5553023f40000004c0200000b0008025f72656c732f2e72656c7320a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000ac924d4fc3300c86ef48fc87c8f7d5dd9010424b774148bb21547e8049dc0fb58da3241bddbf271c10541a8303477fbd7efccadbdd3c8deac821f6e234ac8b12143b23b677ad8697fa7175072a2672964671ace1c41176d5f5d5f699474a792876bd8f2aabb8a8a14bc9df2346d3f144b110cf2e571a0913a51c86163d99815ac64d59de62f8ae01d54253edad86b0b737a0ea93cf9b7fd796a6e90d3f88394cecd29915c8736267d9ae7cc86c21f5f91a5553683969b0629e723a22795f646cc0f3449bbf13fd7c2d4e9cc852223412f832cf47c725a0f57f5ab434f1cb9d79c43709c3abc8f0c9828b1fa8de010000ffff0300504b030414000600080000002100fe69ea570a010000cc0300001a000801786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c7320a2040128a0000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000bc934f4bc43010c5ef82df21ccdda6adba886cba0745d8abae1f20a4d3a66c9b94ccf8a7dfde50b17561a997e271de90f77e3c32dbdd67d78a770cd478a7204b5210e88c2f1b572b783d3c5ddd8120d6aed4ad77a86040825d7179b17dc656737c44b6e9494417470a2c737f2f25198b9da6c4f7e8e2a6f2a1d31cc750cb5e9ba3ae51e669ba91e1b70714279e625f2a08fbf21ac461e863f2dfdebeaa1a838fdebc75e8f84c84e4c885d150871a59c1387e8b591241419e67c8d764f8f0e1481691678e4922396ef22598ec9f61169bd9ac0963746b1eac6edcdccc242d3572bb2604591db07ce1102f806690137909e66655181eda7870d387a571fe89972737587c010000ffff0300504b030414000600080000002100ae79cc62f70100000b0400000f000000786c2f776f726b626f6f6b2e786d6ca4534d8fda3010bd57ea7fb07c5ff25116958864d5d22fa4aa425d76b97031ce402cfc91dace02ffbe636769d372d9aa97d81ec76fe6bd3733bb3b29499ec03a617449b3514a09686e6aa1f7257d587dba794b89f34cd74c1a0d253d83a377d5eb57b3a3b187ad31078200da95b4f1be2d92c4f106147323d382c69b9db18a793cda7de25a0bac760d805732c9d37492282634ed110afb120cb3db090e1f0cef1468df835890cc63f9ae11adbba029fe1238c5eca16b6fb8512d426c8514fe1c412951bc58ecb5b16c2b91f629bbbd20e3f60a5a096e8d333b3f42a8a42ff28a6f962659d653ae663b21e1b1979db0b6fdc654c8222991ccf98fb5f0509774824773843f02b66bdf7742e26d361ee7294daa5f562c2d41e7a0c75a35c2ad9f3d0a3f219f77d283d5ccc3dc688ff23d0bffbf5245ec7963d018f21d7e74c202f64350ac9ae197f1826ddd92f986745696745e6c1e1c32df2ce7e3e9e662a5db0cf465d7e6fd83c28c07be0912ee8beaf77f93af66a17b1f051cdd6f05c3919cd642d7e658529c85f3607f8ce1b5a87d53d27c9ca678dfc7be80d837bea4d3db0c638c7bf1042bb6458b62258344b1fb31615c898eae7fb6ac06721fe602672d2c8be02e25b610b8b18b3ac20c9fdcfbae0e067e156ef8261fbcc963ea4b3ece240fed814b049fe4d3ec4dfce332c7d54f000000ffff0300504b0304140006000800000021004d8f947caf0200006e0600000d000000786c2f7374796c65732e786d6cb4556d6f9b3010fe3e69ff01f93b35d0908508a896a64895ba6a523a695f1d308955bf20db7464d3fefbce4012aa4e7be9b42ff1f9383ff7dc736727bdea04f79ea8364cc90c851701f2a82c55c5e42e439f1e0a7f813c6389ac08579266e8400dbacadfbe498d3d70bad9536a3d809026437b6b9b25c6a6dc5341cc856aa8842fb5d28258d8ea1d368da6a432ee90e0380a823916844934202c45f9272082e8c7b6f14b251a62d99671660f3d16f244b9bcdd49a5c99603d52e9c91d2ebc2b98e8e197ad78b2482955a1955db0b00c5aaae59495f724d708249794602d8d72185310ea2a1f03cad95b4c62b552b2dc80fe88ef4f251aa2fb2709f9c7388ca53f3d57b221c3c11c2795a2aaeb467416ca835741e49041d22ae09675bcd9cb32682f1c3e0eecff5fd19e30403b55c14763cc6c5c021c6f98955e40880234f41704bb52c60e38df6c3a181f412666380e9e37e13bdd3e41046f1e400ee13e6e956e90a66f1acc7d195a79cd616886ab6dbbbd5aa067eb7ca5a68599e568cec9424dc9532809c0c28a7a49c6fdcbc7eae9f6177b5275b51087b5b650826df897034a190d11cf0868dc39fa20dd8135827d6dfc37a5d3d26234dc30ff7add8525df43767ecedffc0ec2b01ee13819ec9732ad4739395a17b4788c38c1ec96e5bc62d933f910630abee2c76e07a6dddb5ecdb70ca029a57b4262db70fa78f193adb1f68c55a919ca23eb227657b880c9ded216ae672d0cede19186458bd56b30c7dbb59bd4bd63745e42f82d5c29f5dd2d84fe2d5da8f67d7abf5ba488228b8fe3e791ffee175e8df32687f385b1a0e6f881e8b1d4bdc9c7d199a6ceedc48f7171803ed29f7249a07efe330f08bcb20f46773b2f017f3cbd82fe2305acf67ab9bb88827dce357be47010ec3e37bd485f1d232413993c75e1d3b34f5429360fb8b225c297d27f0f98f22ff010000ffff0300504b03041400060008000000210036e85a35140300002809000018000000786c2f776f726b7368656574732f7368656574322e786d6c9c565d6fda30147d9fb4ff60e5bd848494d208a8281fa5d2264d6bb73d1bc701ab499cd9e6a3ffbed74e02b14b45d517fc7172ceb5cfb57d19de1df20ceda8908c17232fe8743d440bc21356ac47de9fe7c5d5c04352e122c1192fe8c87ba5d2bb1b7fff36dc73f12237942a040a851c791ba5cad8f725d9d01ccb0e2f690148ca458e150cc5da97a5a03831a43cf3c36eb7efe798155ea5108bcf68f0346584ce38d9e6b4509588a01956b07eb961a56cd472f219b91c8b976d7945785e82c48a654cbd1a510fe5247e5c175ce05506fb3e0411268db619bc93cf19115cf2547540ceaf16fa7ecfb7fead0f4ae361c26007da7624683af22641bc8c3c7f3c34fefc65742f5b7da4f0ea896694289a409a3ca478f983a66a4ab30cb89187743e569cbf68e6237cd38510d23074084c14dbd1eaeb47f85afe3741a10b11fd63c876bf09bf3019fc25504253bccdd46fbe5f52b6de2858c73538a28d8993d71995043202813be1b556253c0309f84539d3470b1cc507d3ee59a236d08b3a831b50205ba978feaf9eaca91529ac49d0d6a4707091d4ab49d036a4f022093c31cbd34ed62bb94c82c51b12b44da4fec548fd9a74738a74d908b8852612b4cdf2ba1f45f22be74d526758e1f150f03d82db05299025d677358841a8ca6047a7e07c12217b9a36d1c76de4493850bb717fe8efe094901abb6f633736366d63031b9bb5b15b1b9bb7b1d0115db4c1a06b331f2c30b0c1a5058647d007778e16c141fb9245c03b5a14f41c8f2c30724cb2c06bc7250b74ac9fb7c1d0f177119a6c9d766932f9707676d916ea9d5cb38c81cbf42563807732c659e4bd053aa760da064327d1338be91a63311dd945efac316767976da18f8cd18fe9572e15f08ec684ce51bdb7402789530b74cedaac0d06ae316db0e758ba88ce1a7376160ad569f1ef8ca9ca49f5f294784d7f62b166854419d42b5d1ee0e11355fd307da86466169ea1155750099ad106fe2c50787fba1dc843cab96a06505c98aecc34990bc1055499f6b0a96dbaa0066812c5f3084dc2781ea2492f9e8354b1cd57543c292d3091cff4a02b99a9838eaa7ffc8f337e030000ffff0300504b030414000600080000002100300f886bed060000de1d000013000000786c2f7468656d652f7468656d65312e786d6cec594b6f1b3710be17e87f20f69e58b225c7362207962c256de2c4b095143952bbd42e63ee724152b6752b9263810245d3a29702bdf550b40d9000bda4bfc66d8a3605f2173a2457aba545f995047d45077b1fdf0ce7cd19eed56b872943fb4448cab35650bf5c0b10c9421ed12c6e0577fbbd4b2b01920a6711663c23ad604c64706dfdfdf7aee23595909420a0cfe41a6e058952f9dac2820ce1319697794e327837e422c50a6e45bc10097c007c53b6b058ab2d2fa4986601ca700a6cef0c873424a8af5906eb13e65d06b79992fa41c8c4ae664d1c0a838df6ea1a21c7b2c304dac7ac15c03a113fe89343152086a58217ada0667ec1c2fad505bc5610313587b642d733bf82ae2088f616cd9a221e948bd67b8dd52b9b257f03606a16d7ed763bdd7ac9cf00701882a656962acf466fa5de9ef0ac80ece52cef4ead596bb8f80affa5199957dbed7673b590c53235207bd998c1afd4961b1b8b0ede802cbe39836fb4373a9d65076f4016bf3c83ef5d595d6eb878034a18cdf666d0daa1bd5ec1bd840c39bbe185af007ca556c0a728888632baf412439ea979b196e2075cf400a0810c2b9a2135cec9108710c51d9c0e04c57a01bc4670e58d7d14ca99477a2d24434173d50a3ecc3164c494dfabe7dfbf7afe14bd7afee4e8e1b3a3873f1d3d7a74f4f047cbcb21bc81b3b84af8f2dbcffefcfa63f4c7d36f5e3efec28f9755fcaf3f7cf2cbcf9ffb81904153895e7cf9e4b7674f5e7cf5e9efdf3df6c037041e54e17d9a12896e9303b4c353d0cd18c6959c0cc4f928fa09a60e054e80b7877557250ef0f618331fae4d5ce3dd13503c7cc0eba3078eacbb891829ea59f966923ac02dce599b0baf016eeab52a16ee8fb2d8bfb81855713b18effbd6eee0cc716d779443d59c04a563fb4e421c31b719ce148e494614d2eff81e211eedee53ead8758b86824b3e54e83e456d4cbd26e9d381134853a21b3405bf8c7d3a83ab1ddb6cdd436dce7c5a6f927d170909819947f83e618e19afe391c2a98f651fa7ac6af05b58253e2177c722ace2ba5281a763c238ea46444a1fcd1d01fa569c7e1343bdf2ba7d8b8d53172914ddf3f1bc8539af2237f95e27c169ee9599664915fb81dc8310c5689b2b1f7c8bbb19a2efc10f389bebee7b9438ee3ebd10dca5b123d23440f49b91f0f8f23ae16e3e8ed910135365a0a43b953aa5d949659b51a8dbefcaf6641fdb804dcc973c378e15eb79b87f6189dec4a36c9b4056cc6e51ef2af4bb0a1dfce72bf4bc5c7ef375795a8aa14a4f7b6dd379a7731bef21656c578d19b9254def2d61038a7af0d00c0566322c07b13c81cba2cd7770b1c0860609ae3ea22ad94d700e7d7bdd8c91b12c58c712e55cc2bc681e9b81961ce36d46540aadbb99369b7a0eb1954362b5c523fb78a93a6f966cccf4199b9976b2d0926670d6c596aebcde62752bd55cb3b9aad58d68a6283aaa952a830f67558387a535a1b341d00f81959761ecd7b2c3bc831989b4dded2c3e718b5efa2db9a8d0da2a92e0885817398f2baeab1bdf4d4268125d1ed79dcf9ad540395d0813169371f5c2469e30981a59a7ddb16c625935b758860e5ac16a73b119a010e7ad6008932e5ca639384dea5e10b3188e8b42256cd49e9a8b26daa61aaffaa3aa0e8717369166a2ca49e35c48b58965627d685e15ae629999cb8dfc8bcd860eb637a3800dd40b48b1b40221f2b7490176745d4b864312aaaab32b4fccb185011495908f1411bb497480066c247630b81f6caaf589a884030b93d0fa064ed7b4b5cd2bb7b61675ad7aa66570f6396679828b6aa94f67261967e126df4a19cc9d95d68807ba796537ca9d5f159df16f4a956a18ffcf54d1db019c202c45da03211cee0a8c74beb6022e54c2a10ae5090d7b02cebd4ced806881135a780dc6872366f35f907dfddfe69ce561d21a0641b5436324286c272a11846c435932d1770ab37ab1f55896ac606422aa22aeccadd803b24f585fd7c0655d83039440a89b6a529401833b1e7fee7d91418358f728ffd4c6c526f3797777bdb9db0ec9d29fb19568548a7e652b58f5b7332737185311ceb201cbe972b662cd68bcd89cbbf3e856addacfe4700e84f41fd8ffa80899fd5ea137d43edf81da8ae0f383151e41545fd2550d224817487b3580bec73eb4c1a459d9158ae6f42d7641e5ba90a5176954cf69ecb28972977372f1e4bee67cc62e2cecd8ba1a471e5383678fa7a86e8f267388718cf9d055fd16c5070fc0d19b70ea3f62f6eb94cce1cee441be2d4c740d78342e2e99b41bae8d3a3dc3d82665870c118d0e27f3c7b141a3f8d853363680362312045a49b8e41b1a5c421d9805a9dd2d4be2c5d3894b0ab33294ec92d81ca8f918c0f7b142643dda9995753367b5d657134bb1ec754c7606e159e6339977ce3aabc9eca078a2a32e60327578b2c90a4b81f166030fbe700a0cc3a9fd5e059b8e2d2a2664d7ff020000ffff0300504b03041400060008000000210086bb77c4b6040000fb11000018000000786c2f776f726b7368656574732f7368656574312e786d6c9c58cd92e23610bea72aefe0f221355bb58bb18119f0025b2cfea32ac9a6c8ece6ec3132b8c6b6882d60e6b60f9127cc93a4256363f518b3f105d9dfa76ee9eb9684dad34f2f49ac1c499647349da97aafaf2a240de8264ab733f5eba3f361ac2a39f3d38d1fd394ccd45792ab9fe63fff343dd1ec39df11c214f090e63375c7d8ded4b43cd891c4cf7b744f5260429a253e83d76cabe5fb8cf81b6194c49ad1efdf6b891fa56ae1c1cc7ec4070dc32820160d0e094959e12423b1cf60fef92edae7a5b724f81177899f3d1ff61f029aecc1c5531447ec55385595243057db9466fe530cba5ff4a11f94bec5cb1bf749146434a721eb813bad98e85bcd136da281a7f9741381021e762523e14c5de8a6a70f556d3e1501fa1691535e7b5618ddff4a42b624710c9d87aac213f044e933efb9daccd43ef8cc494c021e0ac587e6488ade2b7d0049fc5b0cc39f610cad1aa4fe5c0ee888a4fd91291b12fa8798ade9c923d176c760858c20083c16e6e6d52279004980a17bc6887b0d680c2ee0574922be9a2088fe8b684fd186ed66eaa8377e0007c1216734f9abc0f4b36561639c6da03ddbe8c39b46204f0c04edd9c818df3482100a231eca62268671d308262f8ca02da777dbe8fe6cf4f07f34c1be1323415b8ed4bf363dad08bcc8a9e5337f3ecde84981fd0419c8f73edf9dba098e8a04f6780a9a7308c9e3660b6e27ac21b939acabe3bc3fd58eb05482738fcf650f9e756eb2c48085011b030e065c0c7835400349952e581d9d74713b59978e74953d2a5d18b0306063c0c1808b01af0648baf83eed922f6e27eb3290aeb247a50b0316066c0c38187031e0d5004917ecb14ebab89dac6b8074953d2a5d18b0306063c0c1808b01af0648ba602375d2c5ed645d43a4abec51e9c28085011b030e065c0c783540d2052755275d60579d1823a4a8ceddcbdcb2ce3dc89c55e7c63267d7b989cc39754e47c7972b91e80cf024f2b291a408f16b51979d3a16a7293e75c05b15371d2df1a544a2756249240ab92d9128e64e3113744eb88da8577734b84c5e8ac8a46344262222f8bc026f9788a0b42f2512e5ddaa9306cabb2d59e28834cec46d44bdbaa36b11d1e132ddedef97dfe58e737cd6717f55500cb48696328be269c92cf26c4bac8ee3022c9f0e5e2acdb027f9ba1a9aaeffe03a185e16063a2b3eb7b2cb56d66a656d8935d02e74809d4fc3f9cab1d7eb2febbbc5377bbd70ed3b676c3a7affdd7bf5dfefffa8efa65ad818c6665b776cbab76cbd6b739636a6b8fd77ba05c2df794bacdbd8251ff4aaadd5cada126ba043cd69b57581e599587ef9fafbe3cab92ba2f85efd65cb3ecee0de5b26019d0bde35a77220bb5e63a0ae6b0b641bbb6cb5b55a595b628d37dbba6d5c176c1b0319b38fb538a2838457b08d4a8b3816556751a12424db8afa3457027ae055e40806acd05a51cceb438c1b26dca11bf0810977d0067c68c21dae011f997007e2f5f0653af3e9dedf92dffc6c1ba5b91243cdcd0b5cb8136445052c9ea11a17285ca09e288362b67cdbc1170e02f550bf071b20a494952f3048c43f27908d9d6534833ab9fe5a96e7fc2bc083b2d0f9c70043598c4d7bac2c26a63d01ac6fc2390d2d68d187d00ee070192862e9a687e489647f32ee7e913f92175ea90b5d684cadfa6c33ff0f0000ffff0300504b030414000600080000002100ff9ef85b05020000dd04000014000000786c2f736861726564537472696e67732e786d6c94544d6fdb300cbd0fd87f2074ea0e893f52349de1b8cbd2a4cdd00443ec6ed85193d9589d2c65925cb4437ffce4184131b919b68b61f2914f7c7cb2d38bc75ac0036ac3959c9068181240c954c9e576426e8bc5e09c80b1549654288913f284865c646fdfa4c65870bdd24c4865ed2e0902c32aaca919aa1d4a87dc295d53eb42bd0dcc4e232d4d85686b11c4617816d4944b024c35d24ec8f8944023f9cf06675d6214932c353c4b6d76b5995ece21bf9ecf8b34b0591ab4e90eca9beff7c86c022b6adbb32d6706b80417c04a95a8257c555a9470b29a16d71085d1bb1e85eb77d213f8982f8b4104533869df7a756e2e6dd09551a752280dea0e72c6ddaeb03d71293bb98e0a0a649554426d9f009e01be21d570830f281288dcd2f6710be458a3b1a8bbf421ea0dc82aa5c4be2b81388ccf06ee31de33df4a6e4d0223bf65ad863d16db94282d2c2f8f216b5aa38f75a2fdec8b201f59f1d2e9a9e14ad3b247b6e0928ad7a18df34eff30bdd166b3413c1a8461189d8fe2b10f7f521595120d144a386d5c2abfe2bfccf29b0f5e79f9e08f1be8bc386d071cf54cd8b07b8eb01ec2172edc78f41f679ba97ad7b8251e2e97df7638308c7cc47416f73dfc3be1d47dfc748b89dff7991a83652fbda05cbc929ebbcf59f81435437bb0267c1f451fb66dd590a9daafd4edb27e1dc7f34edbf1826eda17dac0fd9eb2df000000ffff0300504b03041400060008000000210021556ba28a0100002403000010000801646f6350726f70732f6170702e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c92414fe3301085ef48fb1f2cdfa943592154394608d8e5c08a4a297036f6a4b170edc8338ddafdf5388d28097b5889dbccbca7a7cf339657bb8d671d24743194fc6c567006c144ebc2bae44fab5fa7979c21e960b58f014abe07e457eac7895ca6d8422207c87244c0923744ed4208340d6c34ceb21cb252c7b4d194dbb416b1ae9d81db68b61b0824e64571216047102cd8d3f618c887c44547df0db5d1f47cf8bcdab71958c9ebb6f5ce68caaf547f9c4911634dec6e67c04b311665a6abc06c93a3bd2aa418b7b232dac34d0e56b5f608527c0ee43de87e694bed122ad9d1a203433131747ff3dae69cbd6a841ea7e49d4e4e07ca58bd6d680eb56f91927a89e90d1b004229b261181ecab1775cbb9f6a7e30e4626aec0306902c4c11578e3ce063bdd489fe477c601878079cdf495b60554f39863ce256b4b5f9c4ecc1e1c430e1f942f0e0c21b3eb5ab78ab093e363c1dcaaad1096c3ecaf102c781bccfcb4dbe0fb969745883fdf0fc2bf4ffe179f8f4eaec62569c17f9d4a399149fdf5bbd030000ffff0300504b0304140006000800000021005f595248a4000000e000000010000000786c2f63616c63436861696e2e786d6c5cce410ac2301005d0bde01dc2ec6dda2a22d2b40ba15e400f10d2b12924939209a2b7378254713330efc3e737ddc33b71c7c853200555518240326198685470bdf49b03084e9a06ed02a182273274ed7ad518edccc9ea89446e205660539a8f52b2b1e835176146cac92d44af537ee328798ea807b688c93b5997e55efa5c006d63445470aeb620a63c02847b5fb9f8eee35fa9ffa4af7e442edbda17000000ffff0300504b030414000600080000002100d7fa94b73c0100005902000011000801646f6350726f70732f636f72652e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c925f4bc33014c5df05bf43c97b9bb41b6386b603277b723070a2f81692bb2dd8fc218976fbf6a6edac1bfae463ee39f777cfbda45c1c55937c82f3d2e80ae5194109686e84d4fb0a3d6f57e91c253e302d58633454e8041e2deadb9b925bca8d838d33165c90e09348d29e725ba143089662ecf90114f35974e828ee8c532cc4a7db63cbf83bdb032e0899610581091618ee80a91d89e88c147c44da0fd7f400c13134a040078ff32cc73fde004ef93f1b7ae5c2a96438d9b8d339ee255bf0411cdd472f4763dbb6593be963c4fc397e5d3f3ef5aba65277b7e280ea5270ca1db0605cbd594e6725be2874c76b980feb78e79d04717fea3c7725fe5d8f9c3ef6000391c4207488fdadbc4c960fdb15aa0b52cc5252a405d9922925133a9dbf7563affabb6043419d87ff9bf80da8fbdcd79fa1fe020000ffff0300504b01022d0014000600080000002100a404cfe971010000980500001300000000000000000000000000000000005b436f6e74656e745f54797065735d2e786d6c504b01022d0014000600080000002100b5553023f40000004c0200000b00000000000000000000000000aa0300005f72656c732f2e72656c73504b01022d0014000600080000002100fe69ea570a010000cc0300001a00000000000000000000000000cf060000786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c73504b01022d0014000600080000002100ae79cc62f70100000b0400000f0000000000000000000000000019090000786c2f776f726b626f6f6b2e786d6c504b01022d00140006000800000021004d8f947caf0200006e0600000d000000000000000000000000003d0b0000786c2f7374796c65732e786d6c504b01022d001400060008000000210036e85a3514030000280900001800000000000000000000000000170e0000786c2f776f726b7368656574732f7368656574322e786d6c504b01022d0014000600080000002100300f886bed060000de1d0000130000000000000000000000000061110000786c2f7468656d652f7468656d65312e786d6c504b01022d001400060008000000210086bb77c4b6040000fb11000018000000000000000000000000007f180000786c2f776f726b7368656574732f7368656574312e786d6c504b01022d0014000600080000002100ff9ef85b05020000dd04000014000000000000000000000000006b1d0000786c2f736861726564537472696e67732e786d6c504b01022d001400060008000000210021556ba28a010000240300001000000000000000000000000000a21f0000646f6350726f70732f6170702e786d6c504b01022d00140006000800000021005f595248a4000000e0000000100000000000000000000000000062220000786c2f63616c63436861696e2e786d6c504b01022d0014000600080000002100d7fa94b73c01000059020000110000000000000000000000000034230000646f6350726f70732f636f72652e786d6c504b0506000000000c000c0004030000a72500000000, 'Grades_MATH 101_BSIT-1 A_2026-2027.xlsx', NULL);
INSERT INTO `grade_submissions` (`id`, `teacher_id`, `subject_id`, `section_id`, `semester`, `school_year`, `status`, `teacher_note`, `registrar_note`, `file_path`, `reviewed_by`, `reviewed_at`, `submitted_at`, `file_data`, `file_name`, `deleted_at`) VALUES
(13, 2, 6, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:\\xampp\\htdocs\\school-management-output\\uploads\\grade_sheets\\GradeSheet_2_6_1_1771651565.xlsx', 56, '2026-02-21 13:26:28', '2026-02-21 05:26:05', 0x504b030414000600080000002100a404cfe97101000098050000130008025b436f6e74656e745f54797065735d2e786d6c20a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000c494cf6ec2300cc6ef93f60e55ae531be0304d138503db8e1bd2d8036489a1116912c581c1dbcf0d7f344d1d08516997466de2effbd98d3d1c6f6a93ad21a076b664fda2c732b0d2296d1725fb98bde40f2cc328ac12c65928d916908d47b737c3d9d60366146db164558cfe91739415d4020be7c1d2cedc855a447a0d0bee855c8a05f041af77cfa5b3116ccc63a3c146c327988b9589d9f3863eef48021864d96477b0f12a99f0de68292291f2b555bf5cf2bd434191e90c56dae31d6130deead0ecfc6db08f7ba3d204ad209b8a105f454d187c63f8970bcb4fe796c56991164a379f6b09cac9554d1528d007100a2b80589b22ad452db43d709ff04f8791a7a5df3148935f12be9063f04f1c91ee1df0f4bcbe1449e64ce218b706b0ebdf9f44cf395722807a8f813ab473809fda6738a4307252d155edb80847dd53fed43fd3e03cd224097039c0615434d1b927210851c37158b435ddd191a6d0d5194333e714a8166f9ee6eae81b0000ffff0300504b030414000600080000002100b5553023f40000004c0200000b0008025f72656c732f2e72656c7320a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000ac924d4fc3300c86ef48fc87c8f7d5dd9010424b774148bb21547e8049dc0fb58da3241bddbf271c10541a8303477fbd7efccadbdd3c8deac821f6e234ac8b12143b23b677ad8697fa7175072a2672964671ace1c41176d5f5d5f699474a792876bd8f2aabb8a8a14bc9df2346d3f144b110cf2e571a0913a51c86163d99815ac64d59de62f8ae01d54253edad86b0b737a0ea93cf9b7fd796a6e90d3f88394cecd29915c8736267d9ae7cc86c21f5f91a5553683969b0629e723a22795f646cc0f3449bbf13fd7c2d4e9cc852223412f832cf47c725a0f57f5ab434f1cb9d79c43709c3abc8f0c9828b1fa8de010000ffff0300504b030414000600080000002100fe69ea570a010000cc0300001a000801786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c7320a2040128a0000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000bc934f4bc43010c5ef82df21ccdda6adba886cba0745d8abae1f20a4d3a66c9b94ccf8a7dfde50b17561a997e271de90f77e3c32dbdd67d78a770cd478a7204b5210e88c2f1b572b783d3c5ddd8120d6aed4ad77a86040825d7179b17dc656737c44b6e9494417470a2c737f2f25198b9da6c4f7e8e2a6f2a1d31cc750cb5e9ba3ae51e669ba91e1b70714279e625f2a08fbf21ac461e863f2dfdebeaa1a838fdebc75e8f84c84e4c885d150871a59c1387e8b591241419e67c8d764f8f0e1481691678e4922396ef22598ec9f61169bd9ac0963746b1eac6edcdccc242d3572bb2604591db07ce1102f806690137909e66655181eda7870d387a571fe89972737587c010000ffff0300504b030414000600080000002100ae79cc62f70100000b0400000f000000786c2f776f726b626f6f6b2e786d6ca4534d8fda3010bd57ea7fb07c5ff25116958864d5d22fa4aa425d76b97031ce402cfc91dace02ffbe636769d372d9aa97d81ec76fe6bd3733bb3b29499ec03a617449b3514a09686e6aa1f7257d587dba794b89f34cd74c1a0d253d83a377d5eb57b3a3b187ad31078200da95b4f1be2d92c4f106147323d382c69b9db18a793cda7de25a0bac760d805732c9d37492282634ed110afb120cb3db090e1f0cef1468df835890cc63f9ae11adbba029fe1238c5eca16b6fb8512d426c8514fe1c412951bc58ecb5b16c2b91f629bbbd20e3f60a5a096e8d333b3f42a8a42ff28a6f962659d653ae663b21e1b1979db0b6fdc654c8222991ccf98fb5f0509774824773843f02b66bdf7742e26d361ee7294daa5f562c2d41e7a0c75a35c2ad9f3d0a3f219f77d283d5ccc3dc688ff23d0bffbf5245ec7963d018f21d7e74c202f64350ac9ae197f1826ddd92f986745696745e6c1e1c32df2ce7e3e9e662a5db0cf465d7e6fd83c28c07be0912ee8beaf77f93af66a17b1f051cdd6f05c3919cd642d7e658529c85f3607f8ce1b5a87d53d27c9ca678dfc7be80d837bea4d3db0c638c7bf1042bb6458b62258344b1fb31615c898eae7fb6ac06721fe602672d2c8be02e25b610b8b18b3ac20c9fdcfbae0e067e156ef8261fbcc963ea4b3ece240fed814b049fe4d3ec4dfce332c7d54f000000ffff0300504b0304140006000800000021004d8f947caf0200006e0600000d000000786c2f7374796c65732e786d6cb4556d6f9b3010fe3e69ff01f93b35d0908508a896a64895ba6a523a695f1d308955bf20db7464d3fefbce4012aa4e7be9b42ff1f9383ff7dc736727bdea04f79ea8364cc90c851701f2a82c55c5e42e439f1e0a7f813c6389ac08579266e8400dbacadfbe498d3d70bad9536a3d809026437b6b9b25c6a6dc5341cc856aa8842fb5d28258d8ea1d368da6a432ee90e0380a823916844934202c45f9272082e8c7b6f14b251a62d99671660f3d16f244b9bcdd49a5c99603d52e9c91d2ebc2b98e8e197ad78b2482955a1955db0b00c5aaae59495f724d708249794602d8d72185310ea2a1f03cad95b4c62b552b2dc80fe88ef4f251aa2fb2709f9c7388ca53f3d57b221c3c11c2795a2aaeb467416ca835741e49041d22ae09675bcd9cb32682f1c3e0eecff5fd19e30403b55c14763cc6c5c021c6f98955e40880234f41704bb52c60e38df6c3a181f412666380e9e37e13bdd3e41046f1e400ee13e6e956e90a66f1acc7d195a79cd616886ab6dbbbd5aa067eb7ca5a68599e568cec9424dc9532809c0c28a7a49c6fdcbc7eae9f6177b5275b51087b5b650826df897034a190d11cf0868dc39fa20dd8135827d6dfc37a5d3d26234dc30ff7add8525df43767ecedffc0ec2b01ee13819ec9732ad4739395a17b4788c38c1ec96e5bc62d933f910630abee2c76e07a6dddb5ecdb70ca029a57b4262db70fa78f193adb1f68c55a919ca23eb227657b880c9ded216ae672d0cede19186458bd56b30c7dbb59bd4bd63745e42f82d5c29f5dd2d84fe2d5da8f67d7abf5ba488228b8fe3e791ffee175e8df32687f385b1a0e6f881e8b1d4bdc9c7d199a6ceedc48f7171803ed29f7249a07efe330f08bcb20f46773b2f017f3cbd82fe2305acf67ab9bb88827dce357be47010ec3e37bd485f1d232413993c75e1d3b34f5429360fb8b225c297d27f0f98f22ff010000ffff0300504b03041400060008000000210036e85a35140300002809000018000000786c2f776f726b7368656574732f7368656574322e786d6c9c565d6fda30147d9fb4ff60e5bd848494d208a8281fa5d2264d6bb73d1bc701ab499cd9e6a3ffbed74e02b14b45d517fc7172ceb5cfb57d19de1df20ceda8908c17232fe8743d440bc21356ac47de9fe7c5d5c04352e122c1192fe8c87ba5d2bb1b7fff36dc73f12237942a040a851c791ba5cad8f725d9d01ccb0e2f690148ca458e150cc5da97a5a03831a43cf3c36eb7efe798155ea5108bcf68f0346584ce38d9e6b4509588a01956b07eb961a56cd472f219b91c8b976d7945785e82c48a654cbd1a510fe5247e5c175ce05506fb3e0411268db619bc93cf19115cf2547540ceaf16fa7ecfb7fead0f4ae361c26007da7624683af22641bc8c3c7f3c34fefc65742f5b7da4f0ea896694289a409a3ca478f983a66a4ab30cb89187743e569cbf68e6237cd38510d23074084c14dbd1eaeb47f85afe3741a10b11fd63c876bf09bf3019fc25504253bccdd46fbe5f52b6de2858c73538a28d8993d71995043202813be1b556253c0309f84539d3470b1cc507d3ee59a236d08b3a831b50205ba978feaf9eaca91529ac49d0d6a4707091d4ab49d036a4f022093c31cbd34ed62bb94c82c51b12b44da4fec548fd9a74738a74d908b8852612b4cdf2ba1f45f22be74d526758e1f150f03d82db05299025d677358841a8ca6047a7e07c12217b9a36d1c76de4493850bb717fe8efe094901abb6f633736366d63031b9bb5b15b1b9bb7b1d0115db4c1a06b331f2c30b0c1a5058647d007778e16c141fb9245c03b5a14f41c8f2c30724cb2c06bc7250b74ac9fb7c1d0f177119a6c9d766932f9707676d916ea9d5cb38c81cbf42563807732c659e4bd053aa760da064327d1338be91a63311dd945efac316767976da18f8cd18fe9572e15f08ec684ce51bdb7402789530b74cedaac0d06ae316db0e758ba88ce1a7376160ad569f1ef8ca9ca49f5f294784d7f62b166854419d42b5d1ee0e11355fd307da86466169ea1155750099ad106fe2c50787fba1dc843cab96a06505c98aecc34990bc1055499f6b0a96dbaa0066812c5f3084dc2781ea2492f9e8354b1cd57543c292d3091cff4a02b99a9838eaa7ffc8f337e030000ffff0300504b030414000600080000002100300f886bed060000de1d000013000000786c2f7468656d652f7468656d65312e786d6cec594b6f1b3710be17e87f20f69e58b225c7362207962c256de2c4b095143952bbd42e63ee724152b6752b9263810245d3a29702bdf550b40d9000bda4bfc66d8a3605f2173a2457aba545f995047d45077b1fdf0ce7cd19eed56b872943fb4448cab35650bf5c0b10c9421ed12c6e0577fbbd4b2b01920a6711663c23ad604c64706dfdfdf7aee23595909420a0cfe41a6e058952f9dac2820ce1319697794e327837e422c50a6e45bc10097c007c53b6b058ab2d2fa4986601ca700a6cef0c873424a8af5906eb13e65d06b79992fa41c8c4ae664d1c0a838df6ea1a21c7b2c304dac7ac15c03a113fe89343152086a58217ada0667ec1c2fad505bc5610313587b642d733bf82ae2088f616cd9a221e948bd67b8dd52b9b257f03606a16d7ed763bdd7ac9cf00701882a656962acf466fa5de9ef0ac80ece52cef4ead596bb8f80affa5199957dbed7673b590c53235207bd998c1afd4961b1b8b0ede802cbe39836fb4373a9d65076f4016bf3c83ef5d595d6eb878034a18cdf666d0daa1bd5ec1bd840c39bbe185af007ca556c0a728888632baf412439ea979b196e2075cf400a0810c2b9a2135cec9108710c51d9c0e04c57a01bc4670e58d7d14ca99477a2d24434173d50a3ecc3164c494dfabe7dfbf7afe14bd7afee4e8e1b3a3873f1d3d7a74f4f047cbcb21bc81b3b84af8f2dbcffefcfa63f4c7d36f5e3efec28f9755fcaf3f7cf2cbcf9ffb81904153895e7cf9e4b7674f5e7cf5e9efdf3df6c037041e54e17d9a12896e9303b4c353d0cd18c6959c0cc4f928fa09a60e054e80b7877557250ef0f618331fae4d5ce3dd13503c7cc0eba3078eacbb891829ea59f966923ac02dce599b0baf016eeab52a16ee8fb2d8bfb81855713b18effbd6eee0cc716d779443d59c04a563fb4e421c31b719ce148e494614d2eff81e211eedee53ead8758b86824b3e54e83e456d4cbd26e9d381134853a21b3405bf8c7d3a83ab1ddb6cdd436dce7c5a6f927d170909819947f83e618e19afe391c2a98f651fa7ac6af05b58253e2177c722ace2ba5281a763c238ea46444a1fcd1d01fa569c7e1343bdf2ba7d8b8d53172914ddf3f1bc8539af2237f95e27c169ee9599664915fb81dc8310c5689b2b1f7c8bbb19a2efc10f389bebee7b9438ee3ebd10dca5b123d23440f49b91f0f8f23ae16e3e8ed910135365a0a43b953aa5d949659b51a8dbefcaf6641fdb804dcc973c378e15eb79b87f6189dec4a36c9b4056cc6e51ef2af4bb0a1dfce72bf4bc5c7ef375795a8aa14a4f7b6dd379a7731bef21656c578d19b9254def2d61038a7af0d00c0566322c07b13c81cba2cd7770b1c0860609ae3ea22ad94d700e7d7bdd8c91b12c58c712e55cc2bc681e9b81961ce36d46540aadbb99369b7a0eb1954362b5c523fb78a93a6f966cccf4199b9976b2d0926670d6c596aebcde62752bd55cb3b9aad58d68a6283aaa952a830f67558387a535a1b341d00f81959761ecd7b2c3bc831989b4dded2c3e718b5efa2db9a8d0da2a92e0885817398f2baeab1bdf4d4268125d1ed79dcf9ad540395d0813169371f5c2469e30981a59a7ddb16c625935b758860e5ac16a73b119a010e7ad6008932e5ca639384dea5e10b3188e8b42256cd49e9a8b26daa61aaffaa3aa0e8717369166a2ca49e35c48b58965627d685e15ae629999cb8dfc8bcd860eb637a3800dd40b48b1b40221f2b7490176745d4b864312aaaab32b4fccb185011495908f1411bb497480066c247630b81f6caaf589a884030b93d0fa064ed7b4b5cd2bb7b61675ad7aa66570f6396679828b6aa94f67261967e126df4a19cc9d95d68807ba796537ca9d5f159df16f4a956a18ffcf54d1db019c202c45da03211cee0a8c74beb6022e54c2a10ae5090d7b02cebd4ced806881135a780dc6872366f35f907dfddfe69ce561d21a0641b5436324286c272a11846c435932d1770ab37ab1f55896ac606422aa22aeccadd803b24f585fd7c0655d83039440a89b6a529401833b1e7fee7d91418358f728ffd4c6c526f3797777bdb9db0ec9d29fb19568548a7e652b58f5b7332737185311ceb201cbe972b662cd68bcd89cbbf3e856addacfe4700e84f41fd8ffa80899fd5ea137d43edf81da8ae0f383151e41545fd2550d224817487b3580bec73eb4c1a459d9158ae6f42d7641e5ba90a5176954cf69ecb28972977372f1e4bee67cc62e2cecd8ba1a471e5383678fa7a86e8f267388718cf9d055fd16c5070fc0d19b70ea3f62f6eb94cce1cee441be2d4c740d78342e2e99b41bae8d3a3dc3d82665870c118d0e27f3c7b141a3f8d853363680362312045a49b8e41b1a5c421d9805a9dd2d4be2c5d3894b0ab33294ec92d81ca8f918c0f7b142643dda9995753367b5d657134bb1ec754c7606e159e6339977ce3aabc9eca078a2a32e60327578b2c90a4b81f166030fbe700a0cc3a9fd5e059b8e2d2a2664d7ff020000ffff0300504b03041400060008000000210086bb77c4b6040000fb11000018000000786c2f776f726b7368656574732f7368656574312e786d6c9c58cd92e23610bea72aefe0f221355bb58bb18119f0025b2cfea32ac9a6c8ece6ec3132b8c6b6882d60e6b60f9127cc93a4256363f518b3f105d9dfa76ee9eb9684dad34f2f49ac1c499647349da97aafaf2a240de8264ab733f5eba3f361ac2a39f3d38d1fd394ccd45792ab9fe63fff343dd1ec39df11c214f090e63375c7d8ded4b43cd891c4cf7b744f5260429a253e83d76cabe5fb8cf81b6194c49ad1efdf6b891fa56ae1c1cc7ec4070dc32820160d0e094959e12423b1cf60fef92edae7a5b724f81177899f3d1ff61f029aecc1c5531447ec55385595243057db9466fe530cba5ff4a11f94bec5cb1bf749146434a721eb813bad98e85bcd136da281a7f9741381021e762523e14c5de8a6a70f556d3e1501fa1691535e7b5618ddff4a42b624710c9d87aac213f044e933efb9daccd43ef8cc494c021e0ac587e6488ade2b7d0049fc5b0cc39f610cad1aa4fe5c0ee888a4fd91291b12fa8798ade9c923d176c760858c20083c16e6e6d52279004980a17bc6887b0d680c2ee0574922be9a2088fe8b684fd186ed66eaa8377e0007c1216734f9abc0f4b36561639c6da03ddbe8c39b46204f0c04edd9c818df3482100a231eca62268671d308262f8ca02da777dbe8fe6cf4f07f34c1be1323415b8ed4bf363dad08bcc8a9e5337f3ecde84981fd0419c8f73edf9dba098e8a04f6780a9a7308c9e3660b6e27ac21b939acabe3bc3fd58eb05482738fcf650f9e756eb2c48085011b030e065c0c7835400349952e581d9d74713b59978e74953d2a5d18b0306063c0c1808b01af0648baf83eed922f6e27eb3290aeb247a50b0316066c0c38187031e0d5004917ecb14ebab89dac6b8074953d2a5d18b0306063c0c1808b01af0648ba602375d2c5ed645d43a4abec51e9c28085011b030e065c0c783540d2052755275d60579d1823a4a8ceddcbdcb2ce3dc89c55e7c63267d7b989cc39754e47c7972b91e80cf024f2b291a408f16b51979d3a16a7293e75c05b15371d2df1a544a2756249240ab92d9128e64e3113744eb88da8577734b84c5e8ac8a46344262222f8bc026f9788a0b42f2512e5ddaa9306cabb2d59e28834cec46d44bdbaa36b11d1e132ddedef97dfe58e737cd6717f55500cb48696328be269c92cf26c4bac8ee3022c9f0e5e2acdb027f9ba1a9aaeffe03a185e16063a2b3eb7b2cb56d66a656d8935d02e74809d4fc3f9cab1d7eb2febbbc5377bbd70ed3b676c3a7affdd7bf5dfefffa8efa65ad818c6665b776cbab76cbd6b739636a6b8fd77ba05c2df794bacdbd8251ff4aaadd5cada126ba043cd69b57581e599587ef9fafbe3cab92ba2f85efd65cb3ecee0de5b26019d0bde35a77220bb5e63a0ae6b0b641bbb6cb5b55a595b628d37dbba6d5c176c1b0319b38fb538a2838457b08d4a8b3816556751a12424db8afa3457027ae055e40806acd05a51cceb438c1b26dca11bf0810977d0067c68c21dae011f997007e2f5f0653af3e9dedf92dffc6c1ba5b91243cdcd0b5cb8136445052c9ea11a17285ca09e288362b67cdbc1170e02f550bf071b20a494952f3048c43f27908d9d6534833ab9fe5a96e7fc2bc083b2d0f9c70043598c4d7bac2c26a63d01ac6fc2390d2d68d187d00ee070192862e9a687e489647f32ee7e913f92175ea90b5d684cadfa6c33ff0f0000ffff0300504b030414000600080000002100ff9ef85b05020000dd04000014000000786c2f736861726564537472696e67732e786d6c94544d6fdb300cbd0fd87f2074ea0e893f52349de1b8cbd2a4cdd00443ec6ed85193d9589d2c65925cb4437ffce4184131b919b68b61f2914f7c7cb2d38bc75ac0036ac3959c9068181240c954c9e576426e8bc5e09c80b1549654288913f284865c646fdfa4c65870bdd24c4865ed2e0902c32aaca919aa1d4a87dc295d53eb42bd0dcc4e232d4d85686b11c4617816d4944b024c35d24ec8f8944023f9cf06675d6214932c353c4b6d76b5995ece21bf9ecf8b34b0591ab4e90eca9beff7c86c022b6adbb32d6706b80417c04a95a8257c555a9470b29a16d71085d1bb1e85eb77d213f8982f8b4104533869df7a756e2e6dd09551a752280dea0e72c6ddaeb03d71293bb98e0a0a649554426d9f009e01be21d570830f281288dcd2f6710be458a3b1a8bbf421ea0dc82aa5c4be2b81388ccf06ee31de33df4a6e4d0223bf65ad863d16db94282d2c2f8f216b5aa38f75a2fdec8b201f59f1d2e9a9e14ad3b247b6e0928ad7a18df34eff30bdd166b3413c1a8461189d8fe2b10f7f521595120d144a386d5c2abfe2bfccf29b0f5e79f9e08f1be8bc386d071cf54cd8b07b8eb01ec2172edc78f41f679ba97ad7b8251e2e97df7638308c7cc47416f73dfc3be1d47dfc748b89dff7991a83652fbda05cbc929ebbcf59f81435437bb0267c1f451fb66dd590a9daafd4edb27e1dc7f34edbf1826eda17dac0fd9eb2df000000ffff0300504b03041400060008000000210021556ba28a0100002403000010000801646f6350726f70732f6170702e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c92414fe3301085ef48fb1f2cdfa943592154394608d8e5c08a4a297036f6a4b170edc8338ddafdf5388d28097b5889dbccbca7a7cf339657bb8d671d24743194fc6c567006c144ebc2bae44fab5fa7979c21e960b58f014abe07e457eac7895ca6d8422207c87244c0923744ed4208340d6c34ceb21cb252c7b4d194dbb416b1ae9d81db68b61b0824e64571216047102cd8d3f618c887c44547df0db5d1f47cf8bcdab71958c9ebb6f5ce68caaf547f9c4911634dec6e67c04b311665a6abc06c93a3bd2aa418b7b232dac34d0e56b5f608527c0ee43de87e694bed122ad9d1a203433131747ff3dae69cbd6a841ea7e49d4e4e07ca58bd6d680eb56f91927a89e90d1b004229b261181ecab1775cbb9f6a7e30e4626aec0306902c4c11578e3ce063bdd489fe477c601878079cdf495b60554f39863ce256b4b5f9c4ecc1e1c430e1f942f0e0c21b3eb5ab78ab093e363c1dcaaad1096c3ecaf102c781bccfcb4dbe0fb969745883fdf0fc2bf4ffe179f8f4eaec62569c17f9d4a399149fdf5bbd030000ffff0300504b0304140006000800000021005f595248a4000000e000000010000000786c2f63616c63436861696e2e786d6c5cce410ac2301005d0bde01dc2ec6dda2a22d2b40ba15e400f10d2b12924939209a2b7378254713330efc3e737ddc33b71c7c853200555518240326198685470bdf49b03084e9a06ed02a182273274ed7ad518edccc9ea89446e205660539a8f52b2b1e835176146cac92d44af537ee328798ea807b688c93b5997e55efa5c006d63445470aeb620a63c02847b5fb9f8eee35fa9ffa4af7e442edbda17000000ffff0300504b030414000600080000002100d7fa94b73c0100005902000011000801646f6350726f70732f636f72652e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c925f4bc33014c5df05bf43c97b9bb41b6386b603277b723070a2f81692bb2dd8fc218976fbf6a6edac1bfae463ee39f777cfbda45c1c55937c82f3d2e80ae5194109686e84d4fb0a3d6f57e91c253e302d58633454e8041e2deadb9b925bca8d838d33165c90e09348d29e725ba143089662ecf90114f35974e828ee8c532cc4a7db63cbf83bdb032e0899610581091618ee80a91d89e88c147c44da0fd7f400c13134a040078ff32cc73fde004ef93f1b7ae5c2a96438d9b8d339ee255bf0411cdd472f4763dbb6593be963c4fc397e5d3f3ef5aba65277b7e280ea5270ca1db0605cbd594e6725be2874c76b980feb78e79d04717fea3c7725fe5d8f9c3ef6000391c4207488fdadbc4c960fdb15aa0b52cc5252a405d9922925133a9dbf7563affabb6043419d87ff9bf80da8fbdcd79fa1fe020000ffff0300504b01022d0014000600080000002100a404cfe971010000980500001300000000000000000000000000000000005b436f6e74656e745f54797065735d2e786d6c504b01022d0014000600080000002100b5553023f40000004c0200000b00000000000000000000000000aa0300005f72656c732f2e72656c73504b01022d0014000600080000002100fe69ea570a010000cc0300001a00000000000000000000000000cf060000786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c73504b01022d0014000600080000002100ae79cc62f70100000b0400000f0000000000000000000000000019090000786c2f776f726b626f6f6b2e786d6c504b01022d00140006000800000021004d8f947caf0200006e0600000d000000000000000000000000003d0b0000786c2f7374796c65732e786d6c504b01022d001400060008000000210036e85a3514030000280900001800000000000000000000000000170e0000786c2f776f726b7368656574732f7368656574322e786d6c504b01022d0014000600080000002100300f886bed060000de1d0000130000000000000000000000000061110000786c2f7468656d652f7468656d65312e786d6c504b01022d001400060008000000210086bb77c4b6040000fb11000018000000000000000000000000007f180000786c2f776f726b7368656574732f7368656574312e786d6c504b01022d0014000600080000002100ff9ef85b05020000dd04000014000000000000000000000000006b1d0000786c2f736861726564537472696e67732e786d6c504b01022d001400060008000000210021556ba28a010000240300001000000000000000000000000000a21f0000646f6350726f70732f6170702e786d6c504b01022d00140006000800000021005f595248a4000000e0000000100000000000000000000000000062220000786c2f63616c63436861696e2e786d6c504b01022d0014000600080000002100d7fa94b73c01000059020000110000000000000000000000000034230000646f6350726f70732f636f72652e786d6c504b0506000000000c000c0004030000a72500000000, 'Grades_MATH 101_BSIT-1 A_2026-2027.xlsx', NULL),
(14, 2, 6, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:\\xampp\\htdocs\\school-management-output\\uploads\\grade_sheets\\GradeSheet_2_6_1_1771651973.xlsx', 56, '2026-02-21 13:33:08', '2026-02-21 05:32:53', 0x504b030414000600080000002100a404cfe97101000098050000130008025b436f6e74656e745f54797065735d2e786d6c20a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000c494cf6ec2300cc6ef93f60e55ae531be0304d138503db8e1bd2d8036489a1116912c581c1dbcf0d7f344d1d08516997466de2effbd98d3d1c6f6a93ad21a076b664fda2c732b0d2296d1725fb98bde40f2cc328ac12c65928d916908d47b737c3d9d60366146db164558cfe91739415d4020be7c1d2cedc855a447a0d0bee855c8a05f041af77cfa5b3116ccc63a3c146c327988b9589d9f3863eef48021864d96477b0f12a99f0de68292291f2b555bf5cf2bd434191e90c56dae31d6130deead0ecfc6db08f7ba3d204ad209b8a105f454d187c63f8970bcb4fe796c56991164a379f6b09cac9554d1528d007100a2b80589b22ad452db43d709ff04f8791a7a5df3148935f12be9063f04f1c91ee1df0f4bcbe1449e64ce218b706b0ebdf9f44cf395722807a8f813ab473809fda6738a4307252d155edb80847dd53fed43fd3e03cd224097039c0615434d1b927210851c37158b435ddd191a6d0d5194333e714a8166f9ee6eae81b0000ffff0300504b030414000600080000002100b5553023f40000004c0200000b0008025f72656c732f2e72656c7320a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000ac924d4fc3300c86ef48fc87c8f7d5dd9010424b774148bb21547e8049dc0fb58da3241bddbf271c10541a8303477fbd7efccadbdd3c8deac821f6e234ac8b12143b23b677ad8697fa7175072a2672964671ace1c41176d5f5d5f699474a792876bd8f2aabb8a8a14bc9df2346d3f144b110cf2e571a0913a51c86163d99815ac64d59de62f8ae01d54253edad86b0b737a0ea93cf9b7fd796a6e90d3f88394cecd29915c8736267d9ae7cc86c21f5f91a5553683969b0629e723a22795f646cc0f3449bbf13fd7c2d4e9cc852223412f832cf47c725a0f57f5ab434f1cb9d79c43709c3abc8f0c9828b1fa8de010000ffff0300504b030414000600080000002100fe69ea570a010000cc0300001a000801786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c7320a2040128a0000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000bc934f4bc43010c5ef82df21ccdda6adba886cba0745d8abae1f20a4d3a66c9b94ccf8a7dfde50b17561a997e271de90f77e3c32dbdd67d78a770cd478a7204b5210e88c2f1b572b783d3c5ddd8120d6aed4ad77a86040825d7179b17dc656737c44b6e9494417470a2c737f2f25198b9da6c4f7e8e2a6f2a1d31cc750cb5e9ba3ae51e669ba91e1b70714279e625f2a08fbf21ac461e863f2dfdebeaa1a838fdebc75e8f84c84e4c885d150871a59c1387e8b591241419e67c8d764f8f0e1481691678e4922396ef22598ec9f61169bd9ac0963746b1eac6edcdccc242d3572bb2604591db07ce1102f806690137909e66655181eda7870d387a571fe89972737587c010000ffff0300504b030414000600080000002100ae79cc62f70100000b0400000f000000786c2f776f726b626f6f6b2e786d6ca4534d8fda3010bd57ea7fb07c5ff25116958864d5d22fa4aa425d76b97031ce402cfc91dace02ffbe636769d372d9aa97d81ec76fe6bd3733bb3b29499ec03a617449b3514a09686e6aa1f7257d587dba794b89f34cd74c1a0d253d83a377d5eb57b3a3b187ad31078200da95b4f1be2d92c4f106147323d382c69b9db18a793cda7de25a0bac760d805732c9d37492282634ed110afb120cb3db090e1f0cef1468df835890cc63f9ae11adbba029fe1238c5eca16b6fb8512d426c8514fe1c412951bc58ecb5b16c2b91f629bbbd20e3f60a5a096e8d333b3f42a8a42ff28a6f962659d653ae663b21e1b1979db0b6fdc654c8222991ccf98fb5f0509774824773843f02b66bdf7742e26d361ee7294daa5f562c2d41e7a0c75a35c2ad9f3d0a3f219f77d283d5ccc3dc688ff23d0bffbf5245ec7963d018f21d7e74c202f64350ac9ae197f1826ddd92f986745696745e6c1e1c32df2ce7e3e9e662a5db0cf465d7e6fd83c28c07be0912ee8beaf77f93af66a17b1f051cdd6f05c3919cd642d7e658529c85f3607f8ce1b5a87d53d27c9ca678dfc7be80d837bea4d3db0c638c7bf1042bb6458b62258344b1fb31615c898eae7fb6ac06721fe602672d2c8be02e25b610b8b18b3ac20c9fdcfbae0e067e156ef8261fbcc963ea4b3ece240fed814b049fe4d3ec4dfce332c7d54f000000ffff0300504b0304140006000800000021004d8f947caf0200006e0600000d000000786c2f7374796c65732e786d6cb4556d6f9b3010fe3e69ff01f93b35d0908508a896a64895ba6a523a695f1d308955bf20db7464d3fefbce4012aa4e7be9b42ff1f9383ff7dc736727bdea04f79ea8364cc90c851701f2a82c55c5e42e439f1e0a7f813c6389ac08579266e8400dbacadfbe498d3d70bad9536a3d809026437b6b9b25c6a6dc5341cc856aa8842fb5d28258d8ea1d368da6a432ee90e0380a823916844934202c45f9272082e8c7b6f14b251a62d99671660f3d16f244b9bcdd49a5c99603d52e9c91d2ebc2b98e8e197ad78b2482955a1955db0b00c5aaae59495f724d708249794602d8d72185310ea2a1f03cad95b4c62b552b2dc80fe88ef4f251aa2fb2709f9c7388ca53f3d57b221c3c11c2795a2aaeb467416ca835741e49041d22ae09675bcd9cb32682f1c3e0eecff5fd19e30403b55c14763cc6c5c021c6f98955e40880234f41704bb52c60e38df6c3a181f412666380e9e37e13bdd3e41046f1e400ee13e6e956e90a66f1acc7d195a79cd616886ab6dbbbd5aa067eb7ca5a68599e568cec9424dc9532809c0c28a7a49c6fdcbc7eae9f6177b5275b51087b5b650826df897034a190d11cf0868dc39fa20dd8135827d6dfc37a5d3d26234dc30ff7add8525df43767ecedffc0ec2b01ee13819ec9732ad4739395a17b4788c38c1ec96e5bc62d933f910630abee2c76e07a6dddb5ecdb70ca029a57b4262db70fa78f193adb1f68c55a919ca23eb227657b880c9ded216ae672d0cede19186458bd56b30c7dbb59bd4bd63745e42f82d5c29f5dd2d84fe2d5da8f67d7abf5ba488228b8fe3e791ffee175e8df32687f385b1a0e6f881e8b1d4bdc9c7d199a6ceedc48f7171803ed29f7249a07efe330f08bcb20f46773b2f017f3cbd82fe2305acf67ab9bb88827dce357be47010ec3e37bd485f1d232413993c75e1d3b34f5429360fb8b225c297d27f0f98f22ff010000ffff0300504b03041400060008000000210036e85a35140300002809000018000000786c2f776f726b7368656574732f7368656574322e786d6c9c565d6fda30147d9fb4ff60e5bd848494d208a8281fa5d2264d6bb73d1bc701ab499cd9e6a3ffbed74e02b14b45d517fc7172ceb5cfb57d19de1df20ceda8908c17232fe8743d440bc21356ac47de9fe7c5d5c04352e122c1192fe8c87ba5d2bb1b7fff36dc73f12237942a040a851c791ba5cad8f725d9d01ccb0e2f690148ca458e150cc5da97a5a03831a43cf3c36eb7efe798155ea5108bcf68f0346584ce38d9e6b4509588a01956b07eb961a56cd472f219b91c8b976d7945785e82c48a654cbd1a510fe5247e5c175ce05506fb3e0411268db619bc93cf19115cf2547540ceaf16fa7ecfb7fead0f4ae361c26007da7624683af22641bc8c3c7f3c34fefc65742f5b7da4f0ea896694289a409a3ca478f983a66a4ab30cb89187743e569cbf68e6237cd38510d23074084c14dbd1eaeb47f85afe3741a10b11fd63c876bf09bf3019fc25504253bccdd46fbe5f52b6de2858c73538a28d8993d71995043202813be1b556253c0309f84539d3470b1cc507d3ee59a236d08b3a831b50205ba978feaf9eaca91529ac49d0d6a4707091d4ab49d036a4f022093c31cbd34ed62bb94c82c51b12b44da4fec548fd9a74738a74d908b8852612b4cdf2ba1f45f22be74d526758e1f150f03d82db05299025d677358841a8ca6047a7e07c12217b9a36d1c76de4493850bb717fe8efe094901abb6f633736366d63031b9bb5b15b1b9bb7b1d0115db4c1a06b331f2c30b0c1a5058647d007778e16c141fb9245c03b5a14f41c8f2c30724cb2c06bc7250b74ac9fb7c1d0f177119a6c9d766932f9707676d916ea9d5cb38c81cbf42563807732c659e4bd053aa760da064327d1338be91a63311dd945efac316767976da18f8cd18fe9572e15f08ec684ce51bdb7402789530b74cedaac0d06ae316db0e758ba88ce1a7376160ad569f1ef8ca9ca49f5f294784d7f62b166854419d42b5d1ee0e11355fd307da86466169ea1155750099ad106fe2c50787fba1dc843cab96a06505c98aecc34990bc1055499f6b0a96dbaa0066812c5f3084dc2781ea2492f9e8354b1cd57543c292d3091cff4a02b99a9838eaa7ffc8f337e030000ffff0300504b030414000600080000002100300f886bed060000de1d000013000000786c2f7468656d652f7468656d65312e786d6cec594b6f1b3710be17e87f20f69e58b225c7362207962c256de2c4b095143952bbd42e63ee724152b6752b9263810245d3a29702bdf550b40d9000bda4bfc66d8a3605f2173a2457aba545f995047d45077b1fdf0ce7cd19eed56b872943fb4448cab35650bf5c0b10c9421ed12c6e0577fbbd4b2b01920a6711663c23ad604c64706dfdfdf7aee23595909420a0cfe41a6e058952f9dac2820ce1319697794e327837e422c50a6e45bc10097c007c53b6b058ab2d2fa4986601ca700a6cef0c873424a8af5906eb13e65d06b79992fa41c8c4ae664d1c0a838df6ea1a21c7b2c304dac7ac15c03a113fe89343152086a58217ada0667ec1c2fad505bc5610313587b642d733bf82ae2088f616cd9a221e948bd67b8dd52b9b257f03606a16d7ed763bdd7ac9cf00701882a656962acf466fa5de9ef0ac80ece52cef4ead596bb8f80affa5199957dbed7673b590c53235207bd998c1afd4961b1b8b0ede802cbe39836fb4373a9d65076f4016bf3c83ef5d595d6eb878034a18cdf666d0daa1bd5ec1bd840c39bbe185af007ca556c0a728888632baf412439ea979b196e2075cf400a0810c2b9a2135cec9108710c51d9c0e04c57a01bc4670e58d7d14ca99477a2d24434173d50a3ecc3164c494dfabe7dfbf7afe14bd7afee4e8e1b3a3873f1d3d7a74f4f047cbcb21bc81b3b84af8f2dbcffefcfa63f4c7d36f5e3efec28f9755fcaf3f7cf2cbcf9ffb81904153895e7cf9e4b7674f5e7cf5e9efdf3df6c037041e54e17d9a12896e9303b4c353d0cd18c6959c0cc4f928fa09a60e054e80b7877557250ef0f618331fae4d5ce3dd13503c7cc0eba3078eacbb891829ea59f966923ac02dce599b0baf016eeab52a16ee8fb2d8bfb81855713b18effbd6eee0cc716d779443d59c04a563fb4e421c31b719ce148e494614d2eff81e211eedee53ead8758b86824b3e54e83e456d4cbd26e9d381134853a21b3405bf8c7d3a83ab1ddb6cdd436dce7c5a6f927d170909819947f83e618e19afe391c2a98f651fa7ac6af05b58253e2177c722ace2ba5281a763c238ea46444a1fcd1d01fa569c7e1343bdf2ba7d8b8d53172914ddf3f1bc8539af2237f95e27c169ee9599664915fb81dc8310c5689b2b1f7c8bbb19a2efc10f389bebee7b9438ee3ebd10dca5b123d23440f49b91f0f8f23ae16e3e8ed910135365a0a43b953aa5d949659b51a8dbefcaf6641fdb804dcc973c378e15eb79b87f6189dec4a36c9b4056cc6e51ef2af4bb0a1dfce72bf4bc5c7ef375795a8aa14a4f7b6dd379a7731bef21656c578d19b9254def2d61038a7af0d00c0566322c07b13c81cba2cd7770b1c0860609ae3ea22ad94d700e7d7bdd8c91b12c58c712e55cc2bc681e9b81961ce36d46540aadbb99369b7a0eb1954362b5c523fb78a93a6f966cccf4199b9976b2d0926670d6c596aebcde62752bd55cb3b9aad58d68a6283aaa952a830f67558387a535a1b341d00f81959761ecd7b2c3bc831989b4dded2c3e718b5efa2db9a8d0da2a92e0885817398f2baeab1bdf4d4268125d1ed79dcf9ad540395d0813169371f5c2469e30981a59a7ddb16c625935b758860e5ac16a73b119a010e7ad6008932e5ca639384dea5e10b3188e8b42256cd49e9a8b26daa61aaffaa3aa0e8717369166a2ca49e35c48b58965627d685e15ae629999cb8dfc8bcd860eb637a3800dd40b48b1b40221f2b7490176745d4b864312aaaab32b4fccb185011495908f1411bb497480066c247630b81f6caaf589a884030b93d0fa064ed7b4b5cd2bb7b61675ad7aa66570f6396679828b6aa94f67261967e126df4a19cc9d95d68807ba796537ca9d5f159df16f4a956a18ffcf54d1db019c202c45da03211cee0a8c74beb6022e54c2a10ae5090d7b02cebd4ced806881135a780dc6872366f35f907dfddfe69ce561d21a0641b5436324286c272a11846c435932d1770ab37ab1f55896ac606422aa22aeccadd803b24f585fd7c0655d83039440a89b6a529401833b1e7fee7d91418358f728ffd4c6c526f3797777bdb9db0ec9d29fb19568548a7e652b58f5b7332737185311ceb201cbe972b662cd68bcd89cbbf3e856addacfe4700e84f41fd8ffa80899fd5ea137d43edf81da8ae0f383151e41545fd2550d224817487b3580bec73eb4c1a459d9158ae6f42d7641e5ba90a5176954cf69ecb28972977372f1e4bee67cc62e2cecd8ba1a471e5383678fa7a86e8f267388718cf9d055fd16c5070fc0d19b70ea3f62f6eb94cce1cee441be2d4c740d78342e2e99b41bae8d3a3dc3d82665870c118d0e27f3c7b141a3f8d853363680362312045a49b8e41b1a5c421d9805a9dd2d4be2c5d3894b0ab33294ec92d81ca8f918c0f7b142643dda9995753367b5d657134bb1ec754c7606e159e6339977ce3aabc9eca078a2a32e60327578b2c90a4b81f166030fbe700a0cc3a9fd5e059b8e2d2a2664d7ff020000ffff0300504b03041400060008000000210086bb77c4b6040000fb11000018000000786c2f776f726b7368656574732f7368656574312e786d6c9c58cd92e23610bea72aefe0f221355bb58bb18119f0025b2cfea32ac9a6c8ece6ec3132b8c6b6882d60e6b60f9127cc93a4256363f518b3f105d9dfa76ee9eb9684dad34f2f49ac1c499647349da97aafaf2a240de8264ab733f5eba3f361ac2a39f3d38d1fd394ccd45792ab9fe63fff343dd1ec39df11c214f090e63375c7d8ded4b43cd891c4cf7b744f5260429a253e83d76cabe5fb8cf81b6194c49ad1efdf6b891fa56ae1c1cc7ec4070dc32820160d0e094959e12423b1cf60fef92edae7a5b724f81177899f3d1ff61f029aecc1c5531447ec55385595243057db9466fe530cba5ff4a11f94bec5cb1bf749146434a721eb813bad98e85bcd136da281a7f9741381021e762523e14c5de8a6a70f556d3e1501fa1691535e7b5618ddff4a42b624710c9d87aac213f044e933efb9daccd43ef8cc494c021e0ac587e6488ade2b7d0049fc5b0cc39f610cad1aa4fe5c0ee888a4fd91291b12fa8798ade9c923d176c760858c20083c16e6e6d52279004980a17bc6887b0d680c2ee0574922be9a2088fe8b684fd186ed66eaa8377e0007c1216734f9abc0f4b36561639c6da03ddbe8c39b46204f0c04edd9c818df3482100a231eca62268671d308262f8ca02da777dbe8fe6cf4f07f34c1be1323415b8ed4bf363dad08bcc8a9e5337f3ecde84981fd0419c8f73edf9dba098e8a04f6780a9a7308c9e3660b6e27ac21b939acabe3bc3fd58eb05482738fcf650f9e756eb2c48085011b030e065c0c7835400349952e581d9d74713b59978e74953d2a5d18b0306063c0c1808b01af0648baf83eed922f6e27eb3290aeb247a50b0316066c0c38187031e0d5004917ecb14ebab89dac6b8074953d2a5d18b0306063c0c1808b01af0648ba602375d2c5ed645d43a4abec51e9c28085011b030e065c0c783540d2052755275d60579d1823a4a8ceddcbdcb2ce3dc89c55e7c63267d7b989cc39754e47c7972b91e80cf024f2b291a408f16b51979d3a16a7293e75c05b15371d2df1a544a2756249240ab92d9128e64e3113744eb88da8577734b84c5e8ac8a46344262222f8bc026f9788a0b42f2512e5ddaa9306cabb2d59e28834cec46d44bdbaa36b11d1e132ddedef97dfe58e737cd6717f55500cb48696328be269c92cf26c4bac8ee3022c9f0e5e2acdb027f9ba1a9aaeffe03a185e16063a2b3eb7b2cb56d66a656d8935d02e74809d4fc3f9cab1d7eb2febbbc5377bbd70ed3b676c3a7affdd7bf5dfefffa8efa65ad818c6665b776cbab76cbd6b739636a6b8fd77ba05c2df794bacdbd8251ff4aaadd5cada126ba043cd69b57581e599587ef9fafbe3cab92ba2f85efd65cb3ecee0de5b26019d0bde35a77220bb5e63a0ae6b0b641bbb6cb5b55a595b628d37dbba6d5c176c1b0319b38fb538a2838457b08d4a8b3816556751a12424db8afa3457027ae055e40806acd05a51cceb438c1b26dca11bf0810977d0067c68c21dae011f997007e2f5f0653af3e9dedf92dffc6c1ba5b91243cdcd0b5cb8136445052c9ea11a17285ca09e288362b67cdbc1170e02f550bf071b20a494952f3048c43f27908d9d6534833ab9fe5a96e7fc2bc083b2d0f9c70043598c4d7bac2c26a63d01ac6fc2390d2d68d187d00ee070192862e9a687e489647f32ee7e913f92175ea90b5d684cadfa6c33ff0f0000ffff0300504b030414000600080000002100ff9ef85b05020000dd04000014000000786c2f736861726564537472696e67732e786d6c94544d6fdb300cbd0fd87f2074ea0e893f52349de1b8cbd2a4cdd00443ec6ed85193d9589d2c65925cb4437ffce4184131b919b68b61f2914f7c7cb2d38bc75ac0036ac3959c9068181240c954c9e576426e8bc5e09c80b1549654288913f284865c646fdfa4c65870bdd24c4865ed2e0902c32aaca919aa1d4a87dc295d53eb42bd0dcc4e232d4d85686b11c4617816d4944b024c35d24ec8f8944023f9cf06675d6214932c353c4b6d76b5995ece21bf9ecf8b34b0591ab4e90eca9beff7c86c022b6adbb32d6706b80417c04a95a8257c555a9470b29a16d71085d1bb1e85eb77d213f8982f8b4104533869df7a756e2e6dd09551a752280dea0e72c6ddaeb03d71293bb98e0a0a649554426d9f009e01be21d570830f281288dcd2f6710be458a3b1a8bbf421ea0dc82aa5c4be2b81388ccf06ee31de33df4a6e4d0223bf65ad863d16db94282d2c2f8f216b5aa38f75a2fdec8b201f59f1d2e9a9e14ad3b247b6e0928ad7a18df34eff30bdd166b3413c1a8461189d8fe2b10f7f521595120d144a386d5c2abfe2bfccf29b0f5e79f9e08f1be8bc386d071cf54cd8b07b8eb01ec2172edc78f41f679ba97ad7b8251e2e97df7638308c7cc47416f73dfc3be1d47dfc748b89dff7991a83652fbda05cbc929ebbcf59f81435437bb0267c1f451fb66dd590a9daafd4edb27e1dc7f34edbf1826eda17dac0fd9eb2df000000ffff0300504b03041400060008000000210021556ba28a0100002403000010000801646f6350726f70732f6170702e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c92414fe3301085ef48fb1f2cdfa943592154394608d8e5c08a4a297036f6a4b170edc8338ddafdf5388d28097b5889dbccbca7a7cf339657bb8d671d24743194fc6c567006c144ebc2bae44fab5fa7979c21e960b58f014abe07e457eac7895ca6d8422207c87244c0923744ed4208340d6c34ceb21cb252c7b4d194dbb416b1ae9d81db68b61b0824e64571216047102cd8d3f618c887c44547df0db5d1f47cf8bcdab71958c9ebb6f5ce68caaf547f9c4911634dec6e67c04b311665a6abc06c93a3bd2aa418b7b232dac34d0e56b5f608527c0ee43de87e694bed122ad9d1a203433131747ff3dae69cbd6a841ea7e49d4e4e07ca58bd6d680eb56f91927a89e90d1b004229b261181ecab1775cbb9f6a7e30e4626aec0306902c4c11578e3ce063bdd489fe477c601878079cdf495b60554f39863ce256b4b5f9c4ecc1e1c430e1f942f0e0c21b3eb5ab78ab093e363c1dcaaad1096c3ecaf102c781bccfcb4dbe0fb969745883fdf0fc2bf4ffe179f8f4eaec62569c17f9d4a399149fdf5bbd030000ffff0300504b0304140006000800000021005f595248a4000000e000000010000000786c2f63616c63436861696e2e786d6c5cce410ac2301005d0bde01dc2ec6dda2a22d2b40ba15e400f10d2b12924939209a2b7378254713330efc3e737ddc33b71c7c853200555518240326198685470bdf49b03084e9a06ed02a182273274ed7ad518edccc9ea89446e205660539a8f52b2b1e835176146cac92d44af537ee328798ea807b688c93b5997e55efa5c006d63445470aeb620a63c02847b5fb9f8eee35fa9ffa4af7e442edbda17000000ffff0300504b030414000600080000002100d7fa94b73c0100005902000011000801646f6350726f70732f636f72652e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c925f4bc33014c5df05bf43c97b9bb41b6386b603277b723070a2f81692bb2dd8fc218976fbf6a6edac1bfae463ee39f777cfbda45c1c55937c82f3d2e80ae5194109686e84d4fb0a3d6f57e91c253e302d58633454e8041e2deadb9b925bca8d838d33165c90e09348d29e725ba143089662ecf90114f35974e828ee8c532cc4a7db63cbf83bdb032e0899610581091618ee80a91d89e88c147c44da0fd7f400c13134a040078ff32cc73fde004ef93f1b7ae5c2a96438d9b8d339ee255bf0411cdd472f4763dbb6593be963c4fc397e5d3f3ef5aba65277b7e280ea5270ca1db0605cbd594e6725be2874c76b980feb78e79d04717fea3c7725fe5d8f9c3ef6000391c4207488fdadbc4c960fdb15aa0b52cc5252a405d9922925133a9dbf7563affabb6043419d87ff9bf80da8fbdcd79fa1fe020000ffff0300504b01022d0014000600080000002100a404cfe971010000980500001300000000000000000000000000000000005b436f6e74656e745f54797065735d2e786d6c504b01022d0014000600080000002100b5553023f40000004c0200000b00000000000000000000000000aa0300005f72656c732f2e72656c73504b01022d0014000600080000002100fe69ea570a010000cc0300001a00000000000000000000000000cf060000786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c73504b01022d0014000600080000002100ae79cc62f70100000b0400000f0000000000000000000000000019090000786c2f776f726b626f6f6b2e786d6c504b01022d00140006000800000021004d8f947caf0200006e0600000d000000000000000000000000003d0b0000786c2f7374796c65732e786d6c504b01022d001400060008000000210036e85a3514030000280900001800000000000000000000000000170e0000786c2f776f726b7368656574732f7368656574322e786d6c504b01022d0014000600080000002100300f886bed060000de1d0000130000000000000000000000000061110000786c2f7468656d652f7468656d65312e786d6c504b01022d001400060008000000210086bb77c4b6040000fb11000018000000000000000000000000007f180000786c2f776f726b7368656574732f7368656574312e786d6c504b01022d0014000600080000002100ff9ef85b05020000dd04000014000000000000000000000000006b1d0000786c2f736861726564537472696e67732e786d6c504b01022d001400060008000000210021556ba28a010000240300001000000000000000000000000000a21f0000646f6350726f70732f6170702e786d6c504b01022d00140006000800000021005f595248a4000000e0000000100000000000000000000000000062220000786c2f63616c63436861696e2e786d6c504b01022d0014000600080000002100d7fa94b73c01000059020000110000000000000000000000000034230000646f6350726f70732f636f72652e786d6c504b0506000000000c000c0004030000a72500000000, 'Grades_MATH 101_BSIT-1 A_2026-2027.xlsx', NULL);
INSERT INTO `grade_submissions` (`id`, `teacher_id`, `subject_id`, `section_id`, `semester`, `school_year`, `status`, `teacher_note`, `registrar_note`, `file_path`, `reviewed_by`, `reviewed_at`, `submitted_at`, `file_data`, `file_name`, `deleted_at`) VALUES
(15, 2, 3, 1, '1st Semester', '2026-2027', 'approved', '', '', 'C:\\xampp\\htdocs\\school-management-output\\uploads\\grade_sheets\\GradeSheet_2_3_1_1771653123.xlsx', 56, '2026-02-21 13:52:23', '2026-02-21 05:52:03', 0x504b030414000600080000002100a404cfe97101000098050000130008025b436f6e74656e745f54797065735d2e786d6c20a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000c494cf6ec2300cc6ef93f60e55ae531be0304d138503db8e1bd2d8036489a1116912c581c1dbcf0d7f344d1d08516997466de2effbd98d3d1c6f6a93ad21a076b664fda2c732b0d2296d1725fb98bde40f2cc328ac12c65928d916908d47b737c3d9d60366146db164558cfe91739415d4020be7c1d2cedc855a447a0d0bee855c8a05f041af77cfa5b3116ccc63a3c146c327988b9589d9f3863eef48021864d96477b0f12a99f0de68292291f2b555bf5cf2bd434191e90c56dae31d6130deead0ecfc6db08f7ba3d204ad209b8a105f454d187c63f8970bcb4fe796c56991164a379f6b09cac9554d1528d007100a2b80589b22ad452db43d709ff04f8791a7a5df3148935f12be9063f04f1c91ee1df0f4bcbe1449e64ce218b706b0ebdf9f44cf395722807a8f813ab473809fda6738a4307252d155edb80847dd53fed43fd3e03cd224097039c0615434d1b927210851c37158b435ddd191a6d0d5194333e714a8166f9ee6eae81b0000ffff0300504b030414000600080000002100b5553023f40000004c0200000b0008025f72656c732f2e72656c7320a2040228a000020000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000ac924d4fc3300c86ef48fc87c8f7d5dd9010424b774148bb21547e8049dc0fb58da3241bddbf271c10541a8303477fbd7efccadbdd3c8deac821f6e234ac8b12143b23b677ad8697fa7175072a2672964671ace1c41176d5f5d5f699474a792876bd8f2aabb8a8a14bc9df2346d3f144b110cf2e571a0913a51c86163d99815ac64d59de62f8ae01d54253edad86b0b737a0ea93cf9b7fd796a6e90d3f88394cecd29915c8736267d9ae7cc86c21f5f91a5553683969b0629e723a22795f646cc0f3449bbf13fd7c2d4e9cc852223412f832cf47c725a0f57f5ab434f1cb9d79c43709c3abc8f0c9828b1fa8de010000ffff0300504b030414000600080000002100fe69ea570a010000cc0300001a000801786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c7320a2040128a0000100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000bc934f4bc43010c5ef82df21ccdda6adba886cba0745d8abae1f20a4d3a66c9b94ccf8a7dfde50b17561a997e271de90f77e3c32dbdd67d78a770cd478a7204b5210e88c2f1b572b783d3c5ddd8120d6aed4ad77a86040825d7179b17dc656737c44b6e9494417470a2c737f2f25198b9da6c4f7e8e2a6f2a1d31cc750cb5e9ba3ae51e669ba91e1b70714279e625f2a08fbf21ac461e863f2dfdebeaa1a838fdebc75e8f84c84e4c885d150871a59c1387e8b591241419e67c8d764f8f0e1481691678e4922396ef22598ec9f61169bd9ac0963746b1eac6edcdccc242d3572bb2604591db07ce1102f806690137909e66655181eda7870d387a571fe89972737587c010000ffff0300504b030414000600080000002100ae79cc62f70100000b0400000f000000786c2f776f726b626f6f6b2e786d6ca4534d8fda3010bd57ea7fb07c5ff25116958864d5d22fa4aa425d76b97031ce402cfc91dace02ffbe636769d372d9aa97d81ec76fe6bd3733bb3b29499ec03a617449b3514a09686e6aa1f7257d587dba794b89f34cd74c1a0d253d83a377d5eb57b3a3b187ad31078200da95b4f1be2d92c4f106147323d382c69b9db18a793cda7de25a0bac760d805732c9d37492282634ed110afb120cb3db090e1f0cef1468df835890cc63f9ae11adbba029fe1238c5eca16b6fb8512d426c8514fe1c412951bc58ecb5b16c2b91f629bbbd20e3f60a5a096e8d333b3f42a8a42ff28a6f962659d653ae663b21e1b1979db0b6fdc654c8222991ccf98fb5f0509774824773843f02b66bdf7742e26d361ee7294daa5f562c2d41e7a0c75a35c2ad9f3d0a3f219f77d283d5ccc3dc688ff23d0bffbf5245ec7963d018f21d7e74c202f64350ac9ae197f1826ddd92f986745696745e6c1e1c32df2ce7e3e9e662a5db0cf465d7e6fd83c28c07be0912ee8beaf77f93af66a17b1f051cdd6f05c3919cd642d7e658529c85f3607f8ce1b5a87d53d27c9ca678dfc7be80d837bea4d3db0c638c7bf1042bb6458b62258344b1fb31615c898eae7fb6ac06721fe602672d2c8be02e25b610b8b18b3ac20c9fdcfbae0e067e156ef8261fbcc963ea4b3ece240fed814b049fe4d3ec4dfce332c7d54f000000ffff0300504b0304140006000800000021004d8f947caf0200006e0600000d000000786c2f7374796c65732e786d6cb4556d6f9b3010fe3e69ff01f93b35d0908508a896a64895ba6a523a695f1d308955bf20db7464d3fefbce4012aa4e7be9b42ff1f9383ff7dc736727bdea04f79ea8364cc90c851701f2a82c55c5e42e439f1e0a7f813c6389ac08579266e8400dbacadfbe498d3d70bad9536a3d809026437b6b9b25c6a6dc5341cc856aa8842fb5d28258d8ea1d368da6a432ee90e0380a823916844934202c45f9272082e8c7b6f14b251a62d99671660f3d16f244b9bcdd49a5c99603d52e9c91d2ebc2b98e8e197ad78b2482955a1955db0b00c5aaae59495f724d708249794602d8d72185310ea2a1f03cad95b4c62b552b2dc80fe88ef4f251aa2fb2709f9c7388ca53f3d57b221c3c11c2795a2aaeb467416ca835741e49041d22ae09675bcd9cb32682f1c3e0eecff5fd19e30403b55c14763cc6c5c021c6f98955e40880234f41704bb52c60e38df6c3a181f412666380e9e37e13bdd3e41046f1e400ee13e6e956e90a66f1acc7d195a79cd616886ab6dbbbd5aa067eb7ca5a68599e568cec9424dc9532809c0c28a7a49c6fdcbc7eae9f6177b5275b51087b5b650826df897034a190d11cf0868dc39fa20dd8135827d6dfc37a5d3d26234dc30ff7add8525df43767ecedffc0ec2b01ee13819ec9732ad4739395a17b4788c38c1ec96e5bc62d933f910630abee2c76e07a6dddb5ecdb70ca029a57b4262db70fa78f193adb1f68c55a919ca23eb227657b880c9ded216ae672d0cede19186458bd56b30c7dbb59bd4bd63745e42f82d5c29f5dd2d84fe2d5da8f67d7abf5ba488228b8fe3e791ffee175e8df32687f385b1a0e6f881e8b1d4bdc9c7d199a6ceedc48f7171803ed29f7249a07efe330f08bcb20f46773b2f017f3cbd82fe2305acf67ab9bb88827dce357be47010ec3e37bd485f1d232413993c75e1d3b34f5429360fb8b225c297d27f0f98f22ff010000ffff0300504b03041400060008000000210090dfda570c0300001709000018000000786c2f776f726b7368656574732f7368656574322e786d6c9c56cb6ee23014dd8f34ff6065dfbc48298d808af2285d8c349a7666d6c671c06a12676c03eddfcfb5432076a9a8bac18fc339d73ed7f6cdf0eeb52cd08e0ac97835f2223ff410ad08cf58b51e79bf9f1757030f4985ab0c17bca223ef8d4aef6efcfddb70cfc58bdc50aa10285472e46d94aad3209064434b2c7d5ed30a909c8b122b188a75206b417166486511c461d80f4acc2aaf5148c56734789e3342679c6c4b5aa94644d0022b58bfdcb05ab66a25f98c5c89c5cbb6be22bcac4162c50aa6de8ca8874a923eae2b2ef0aa807dbf460926adb619bc932f19115cf25cf92017340b7dbfe7dbe03600a5f13063b0036d3b12341f7993285d265e301e1a7ffe30ba979d3e5278f5440b4a14cd204d1ed2f6af387fd17f7c84a91014a5f98356c444b11d9dd2a218798f0964f09f89015d08101c2374fb6db48549d84f81329ae36da17ef1fd92b2f54641d86b3040fb90666f332a09240002fbf1b55625bc0009f84525d327090cc4afcd5259a636d04bfcc10d2890ad54bcfc7b983c501b527c2041bb6ff0787091d43b90a06d49f1451278629607ed81145d26c1e20d09da3652ff62a4fe8174738a74d908b8742612b4edf2c28f22058df326a933acf07828f81ec1658214c81aebab19a520d464d0d729389f44c89ea64d8007799570a076e3fe30d8c1292107ecbe8bddd8d8b48b0d6c6cd6c56e6d6cdec5624774d105a3d0663e586064834b0b8c8f6000ee1c2d8283f6258b8077b428ea391e5960e2986481d78e4b16e8583fef82b1e3ef2236d93aedd264f2e1ececb22bd43bb966190397e94bc600ef648cb3c87b0b744ec1b40bc64ea26716d335c6623ab28bde59639a59f7c474853e32463fa65fb954c03b1a133b81ef2dd049e2d4029db336eb82916b4c17ec39962e92b3c69c9d85ba745afc3b639a72d2bc3c355ed31f58ac5925514173531ee0e1134dfd087de82b5eeba2a19fa115575009dad106be0d28bc3fa10f79c83957ed008a0bd38598667321b8802ad31db6b54dd7cf084d92749ea0499cce6334e9a57390aab6e58a8a27a50526f299beea4a66eaa0a31a1c3f69c6ff010000ffff0300504b030414000600080000002100300f886bed060000de1d000013000000786c2f7468656d652f7468656d65312e786d6cec594b6f1b3710be17e87f20f69e58b225c7362207962c256de2c4b095143952bbd42e63ee724152b6752b9263810245d3a29702bdf550b40d9000bda4bfc66d8a3605f2173a2457aba545f995047d45077b1fdf0ce7cd19eed56b872943fb4448cab35650bf5c0b10c9421ed12c6e0577fbbd4b2b01920a6711663c23ad604c64706dfdfdf7aee23595909420a0cfe41a6e058952f9dac2820ce1319697794e327837e422c50a6e45bc10097c007c53b6b058ab2d2fa4986601ca700a6cef0c873424a8af5906eb13e65d06b79992fa41c8c4ae664d1c0a838df6ea1a21c7b2c304dac7ac15c03a113fe89343152086a58217ada0667ec1c2fad505bc5610313587b642d733bf82ae2088f616cd9a221e948bd67b8dd52b9b257f03606a16d7ed763bdd7ac9cf00701882a656962acf466fa5de9ef0ac80ece52cef4ead596bb8f80affa5199957dbed7673b590c53235207bd998c1afd4961b1b8b0ede802cbe39836fb4373a9d65076f4016bf3c83ef5d595d6eb878034a18cdf666d0daa1bd5ec1bd840c39bbe185af007ca556c0a728888632baf412439ea979b196e2075cf400a0810c2b9a2135cec9108710c51d9c0e04c57a01bc4670e58d7d14ca99477a2d24434173d50a3ecc3164c494dfabe7dfbf7afe14bd7afee4e8e1b3a3873f1d3d7a74f4f047cbcb21bc81b3b84af8f2dbcffefcfa63f4c7d36f5e3efec28f9755fcaf3f7cf2cbcf9ffb81904153895e7cf9e4b7674f5e7cf5e9efdf3df6c037041e54e17d9a12896e9303b4c353d0cd18c6959c0cc4f928fa09a60e054e80b7877557250ef0f618331fae4d5ce3dd13503c7cc0eba3078eacbb891829ea59f966923ac02dce599b0baf016eeab52a16ee8fb2d8bfb81855713b18effbd6eee0cc716d779443d59c04a563fb4e421c31b719ce148e494614d2eff81e211eedee53ead8758b86824b3e54e83e456d4cbd26e9d381134853a21b3405bf8c7d3a83ab1ddb6cdd436dce7c5a6f927d170909819947f83e618e19afe391c2a98f651fa7ac6af05b58253e2177c722ace2ba5281a763c238ea46444a1fcd1d01fa569c7e1343bdf2ba7d8b8d53172914ddf3f1bc8539af2237f95e27c169ee9599664915fb81dc8310c5689b2b1f7c8bbb19a2efc10f389bebee7b9438ee3ebd10dca5b123d23440f49b91f0f8f23ae16e3e8ed910135365a0a43b953aa5d949659b51a8dbefcaf6641fdb804dcc973c378e15eb79b87f6189dec4a36c9b4056cc6e51ef2af4bb0a1dfce72bf4bc5c7ef375795a8aa14a4f7b6dd379a7731bef21656c578d19b9254def2d61038a7af0d00c0566322c07b13c81cba2cd7770b1c0860609ae3ea22ad94d700e7d7bdd8c91b12c58c712e55cc2bc681e9b81961ce36d46540aadbb99369b7a0eb1954362b5c523fb78a93a6f966cccf4199b9976b2d0926670d6c596aebcde62752bd55cb3b9aad58d68a6283aaa952a830f67558387a535a1b341d00f81959761ecd7b2c3bc831989b4dded2c3e718b5efa2db9a8d0da2a92e0885817398f2baeab1bdf4d4268125d1ed79dcf9ad540395d0813169371f5c2469e30981a59a7ddb16c625935b758860e5ac16a73b119a010e7ad6008932e5ca639384dea5e10b3188e8b42256cd49e9a8b26daa61aaffaa3aa0e8717369166a2ca49e35c48b58965627d685e15ae629999cb8dfc8bcd860eb637a3800dd40b48b1b40221f2b7490176745d4b864312aaaab32b4fccb185011495908f1411bb497480066c247630b81f6caaf589a884030b93d0fa064ed7b4b5cd2bb7b61675ad7aa66570f6396679828b6aa94f67261967e126df4a19cc9d95d68807ba796537ca9d5f159df16f4a956a18ffcf54d1db019c202c45da03211cee0a8c74beb6022e54c2a10ae5090d7b02cebd4ced806881135a780dc6872366f35f907dfddfe69ce561d21a0641b5436324286c272a11846c435932d1770ab37ab1f55896ac606422aa22aeccadd803b24f585fd7c0655d83039440a89b6a529401833b1e7fee7d91418358f728ffd4c6c526f3797777bdb9db0ec9d29fb19568548a7e652b58f5b7332737185311ceb201cbe972b662cd68bcd89cbbf3e856addacfe4700e84f41fd8ffa80899fd5ea137d43edf81da8ae0f383151e41545fd2550d224817487b3580bec73eb4c1a459d9158ae6f42d7641e5ba90a5176954cf69ecb28972977372f1e4bee67cc62e2cecd8ba1a471e5383678fa7a86e8f267388718cf9d055fd16c5070fc0d19b70ea3f62f6eb94cce1cee441be2d4c740d78342e2e99b41bae8d3a3dc3d82665870c118d0e27f3c7b141a3f8d853363680362312045a49b8e41b1a5c421d9805a9dd2d4be2c5d3894b0ab33294ec92d81ca8f918c0f7b142643dda9995753367b5d657134bb1ec754c7606e159e6339977ce3aabc9eca078a2a32e60327578b2c90a4b81f166030fbe700a0cc3a9fd5e059b8e2d2a2664d7ff020000ffff0300504b0304140006000800000021001b6feaf486040000af11000018000000786c2f776f726b7368656574732f7368656574312e786d6c9c58cb92a33614dda72aff40b19aa9ca9887ed6e9bb299f298572f52493993c99ac6c2a61a902361bb7b371f912fcc97e40a0c46b7319eb069c1399c2b9dab87757bf1f9354b9513613ca1f9523546baaa903ca2db24df2dd53fbf7a9f66aac28b30df8629cdc9527d235cfd6cfffcd3e24cd90bdf1352281021e74b755f14074bd378b42759c847f440726062cab2b08057b6d3f88191705b8ab2543375fd41cbc22457ab0816fb9118348e938838343a66242faa208ca46101e3e7fbe4c0eb6859f423e1b290bd1c0f9f229a1d20c4739226c55b195455b2c87adae59485cf29f87e35266154c72e5fde85cf9288514ee36204e1b46aa0ef3dcfb5b90691ecc536010722ed0a23f1525d1956604c54cd5e9409fa9690336f3d2b22dfcf94be08e269bb547508c1494a22e15c09a139913549d3a5ea431885ff5d4615cf10526b62b69febf85e3947bf33654be2f098161b7a0e48b2db17b020a6e05958b7b66f0ee111e41cba1e99531135a2298480bf4a9688c503390b5fcbf69c6c8bfd529d8e668f10203af282667f55987151561af3a281f6a231267745e38b08da8bc89cdd15414ecad1415b8bccbb22187c2982b61ede7dd1c345f4f87f3cc1362b7b82b6ee49bf353cad4a7c39a74e5884f682d1b302db0766801f42b1190d0b025513381253d03d87307942b612ba520d93cb615d9d6c7da19d60a944972fbed45f8859179235061c0cb818f030e0632068011a586a7cc1ea18e44be8645f06f2557fd1f8c280830117031e067c0c042d40f2050b78902fa1937d99c857fd45e30b030e065c0c7818f03110b400c997387f86ac43a1937d8d91affa8bc617061c0cb818f030e063206801922fd848837c099dec6b827cd55f34be30e060c0c58087011f03410b907cc14935c817e89a13638a1cb5b907995bb7b9479973dadc4ce6dc36379739afcd19e8f8f225129d0181445e37929421710b1ab2a267e5698a4f1d88d6e4cd404b7c2d91689d38128952ee4a24cab9578d049d137e271ab4038daf839732321f98917999117c5e41b46b46d0b4af2512cdbbd3264d34efaea4c419e91c89df8906ed40b73262c0dd79d8cfafb8cb9d6c7cd689784d524cb486d6328bf2e9c82c8aec4aac81f302ac180e5e2add7020c5ba999aa1bfe00608af0b039d155f7ad9752febf4b2aec49a68177ac0da8bd87ef2dccde6b7cd87d53777b3f2dd0fdeccf20cfde32feabfdfff513f2eb4b8338ddd5a7f66f9f7b4c1ad314b1bd3187aab10c29e5cf7b1eb5eadd3cbba126ba243cdebd5fac0766c9de096484ed4d06b8aa8af7a12d5c7ae7bb54e2feb4aacf96edbf6f52beac08e7bbda8383bad5489aacac6aac4c808db95052657227a1465e014223668ab8815051ec64d0b2ec11df8d8824b64073eb1e012d6814f2db8c48882f63a1c7b710877e4d790ed929c2b2989cb0a157ed45955c2ea23f1034f0fa26e15a5d0332da01aaddff6f01f0902058d3e82f51d535ad42fd04922ca7fb27519a30c0addf66b5d5f8baafd515919a2783795d5cc7267ca6a6eb973c0740b0e5a6827702a4c1448353c8fe1790ccfd0597ecc9e09fba3105dacf857f22acaedd21bea576bfed562ff070000ffff0300504b0304140006000800000021002fd69714fc010000d104000014000000786c2f736861726564537472696e67732e786d6c94544d6fd34010bd23f11f463e9543e28f544db11c97903a21082214a7481c177b6a6fd98fe05d5714f5c7771c2baa5837082e96f7bd79b3f366c64eae7e4901f7d818aed5cc0bc78107a80a5d7255cdbc9bdd7274e981b14c954c688533ef018d7795be7e95186381b4caccbcdada7decfba6a8513233d67b54c4dcea46324bc7a6f2cdbe41569a1ad14ae1474170e14bc69507856e959d79d3730f5ac57fb6b8e88149e4a589e16962d3d5767e9d41fe21cb76896fd3c4efe09ecadbef7758d8183255096e6a586829294fc12cd981b36cb30a83f0cd40461ae263789faf77a310e670d6bd0de2a896c620853172267403fa16f282537f10b882b5ea2d7657edb0a89516ba7a007804f886ac814f788f2286901a753877448e128dc5a6878fa7418145adb538a8628882e862448fe921f38de2d6c43071251b3d1e64b16d89cac2fafa14b361125dae37eda2cf865ce6332fc98f8455c3ca41b225574cbc4c6d69579a1f6650da62318a26a32008c2cb493475e98fba664aa1819d16e48d2bed46fcd7b05cf171560eeeffb175348bf3aec0c96008dbe28e236cc6f0950b2a8ffd636db4b5fb969a785c2e5776bc30085dc6f4231ecef0ef09e7f4c1b30a6357f7851983e5005e322e5e8033fa84859b4216688fa309de86e1bbaa8b1a175aba914dd7acdfa7f9bcf7763aa0aff639ad4fbfa4f4090000ffff0300504b03041400060008000000210021556ba28a0100002403000010000801646f6350726f70732f6170702e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c92414fe3301085ef48fb1f2cdfa943592154394608d8e5c08a4a297036f6a4b170edc8338ddafdf5388d28097b5889dbccbca7a7cf339657bb8d671d24743194fc6c567006c144ebc2bae44fab5fa7979c21e960b58f014abe07e457eac7895ca6d8422207c87244c0923744ed4208340d6c34ceb21cb252c7b4d194dbb416b1ae9d81db68b61b0824e64571216047102cd8d3f618c887c44547df0db5d1f47cf8bcdab71958c9ebb6f5ce68caaf547f9c4911634dec6e67c04b311665a6abc06c93a3bd2aa418b7b232dac34d0e56b5f608527c0ee43de87e694bed122ad9d1a203433131747ff3dae69cbd6a841ea7e49d4e4e07ca58bd6d680eb56f91927a89e90d1b004229b261181ecab1775cbb9f6a7e30e4626aec0306902c4c11578e3ce063bdd489fe477c601878079cdf495b60554f39863ce256b4b5f9c4ecc1e1c430e1f942f0e0c21b3eb5ab78ab093e363c1dcaaad1096c3ecaf102c781bccfcb4dbe0fb969745883fdf0fc2bf4ffe179f8f4eaec62569c17f9d4a399149fdf5bbd030000ffff0300504b0304140006000800000021000cbf26be9b000000bc00000010000000786c2f63616c63436861696e2e786d6c3c8ec10ac2301044ef82ffb0ecdda6ed41449af620d41fd00f08e96a02c9a66483e8df1b417a1998373033c3f48e015e94c527d6d8352d02b14d8be7a7c6fb6d3e9c10a4185e4c484c1a3f24388dfbdd604db017673c436d60d1e84a59cf4a8975148d346925aec923e5684ab5f9a964cd641671442506d5b7ed51c55a80e360216bbc763d82af2710c24fd59fcf1baf446dbbe3170000ffff0300504b030414000600080000002100930c6de23d0100005902000011000801646f6350726f70732f636f72652e786d6c20a2040128a00001000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009c92cb4ec3301045f748fc43e47de2247d08ac249528ea8a4a952802b1b3ec696a113f641bd2fe3d4ed28656b062e9b977cedc19b9581c64137d817542ab1265498a22504c73a1ea12bd6c57f11d8a9ca78ad3462b28d1111c5a54b737053384690b1bab0d582fc04581a41c61a6447bef0dc1d8b13d48ea92e05041dc692ba90f4f5b6343d907ad01e7693ac7123ce5d453dc01633312d109c9d988349fb6e9019c61684082f20e6749867fbc1eac747f36f4ca85530a7f3461a753dc4b36678338ba0f4e8cc6b66d9376d2c708f933fcb67e7aee578d85ea6ec5005505678459a05edb6ab39cce0b7c51e88ed750e7d7e1ce3b01fce1d879ee0bfcbb1e387dec01063c0a41c810fbacbc4e968fdb15aaf2349fc7691ee7d9369d915946a693f76eec557f176c28c8d3f07f13cf80aacf7dfd19aa6f000000ffff0300504b01022d0014000600080000002100a404cfe971010000980500001300000000000000000000000000000000005b436f6e74656e745f54797065735d2e786d6c504b01022d0014000600080000002100b5553023f40000004c0200000b00000000000000000000000000aa0300005f72656c732f2e72656c73504b01022d0014000600080000002100fe69ea570a010000cc0300001a00000000000000000000000000cf060000786c2f5f72656c732f776f726b626f6f6b2e786d6c2e72656c73504b01022d0014000600080000002100ae79cc62f70100000b0400000f0000000000000000000000000019090000786c2f776f726b626f6f6b2e786d6c504b01022d00140006000800000021004d8f947caf0200006e0600000d000000000000000000000000003d0b0000786c2f7374796c65732e786d6c504b01022d001400060008000000210090dfda570c030000170900001800000000000000000000000000170e0000786c2f776f726b7368656574732f7368656574322e786d6c504b01022d0014000600080000002100300f886bed060000de1d0000130000000000000000000000000059110000786c2f7468656d652f7468656d65312e786d6c504b01022d00140006000800000021001b6feaf486040000af110000180000000000000000000000000077180000786c2f776f726b7368656574732f7368656574312e786d6c504b01022d00140006000800000021002fd69714fc010000d10400001400000000000000000000000000331d0000786c2f736861726564537472696e67732e786d6c504b01022d001400060008000000210021556ba28a010000240300001000000000000000000000000000611f0000646f6350726f70732f6170702e786d6c504b01022d00140006000800000021000cbf26be9b000000bc000000100000000000000000000000000021220000786c2f63616c63436861696e2e786d6c504b01022d0014000600080000002100930c6de23d010000590200001100000000000000000000000000ea220000646f6350726f70732f636f72652e786d6c504b0506000000000c000c00040300005e2500000000, 'Grades_ENG101_BSIT-1 A_2026-2027 (1).xlsx', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `hr_employees`
--

CREATE TABLE `hr_employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `employment_type` enum('full_time','part_time','contractual','probationary') NOT NULL DEFAULT 'full_time',
  `hire_date` date DEFAULT NULL,
  `salary_grade` varchar(50) DEFAULT NULL,
  `monthly_salary` decimal(10,2) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `sss_number` varchar(50) DEFAULT NULL,
  `philhealth_number` varchar(50) DEFAULT NULL,
  `pagibig_number` varchar(50) DEFAULT NULL,
  `tin_number` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `emergency_contact_relation` varchar(100) DEFAULT NULL,
  `status` enum('active','on_leave','resigned','terminated') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_balances`
--

CREATE TABLE `hr_leave_balances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `allocated_days` int(11) NOT NULL DEFAULT 0,
  `used_days` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_leave_balances`
--

INSERT INTO `hr_leave_balances` (`id`, `user_id`, `leave_type_id`, `year`, `allocated_days`, `used_days`, `created_at`) VALUES
(1, 2, 3, '2026', 0, 2, '2026-02-23 03:06:58');

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_requests`
--

CREATE TABLE `hr_leave_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_days` int(11) NOT NULL DEFAULT 1,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_leave_requests`
--

INSERT INTO `hr_leave_requests` (`id`, `user_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `reviewed_by`, `review_note`, `reviewed_at`, `created_at`) VALUES
(1, 2, 3, '2026-02-23', '2026-02-24', 2, 'i have emergency', 'approved', 63, '', '2026-02-23 11:06:58', '2026-02-23 03:06:24');

-- --------------------------------------------------------

--
-- Table structure for table `hr_leave_types`
--

CREATE TABLE `hr_leave_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `max_days_per_year` int(11) NOT NULL DEFAULT 5,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hr_leave_types`
--

INSERT INTO `hr_leave_types` (`id`, `name`, `max_days_per_year`, `description`, `is_active`) VALUES
(1, 'Sick Leave', 15, 'Leave due to illness or medical appointments', 1),
(2, 'Vacation Leave', 15, 'Annual vacation or personal leave', 1),
(3, 'Emergency Leave', 3, 'Unexpected personal or family emergency', 1),
(4, 'Maternity Leave', 105, 'Maternity leave for female employees', 1),
(5, 'Paternity Leave', 7, 'Paternity leave for male employees', 1),
(6, 'Bereavement Leave', 3, 'Leave due to death of immediate family member', 1),
(7, 'Sick Leave', 15, 'Leave due to illness or medical appointments', 1),
(8, 'Vacation Leave', 15, 'Annual vacation or personal leave', 1),
(9, 'Emergency Leave', 3, 'Unexpected personal or family emergency', 1),
(10, 'Maternity Leave', 105, 'Maternity leave for female employees', 1),
(11, 'Paternity Leave', 7, 'Paternity leave for male employees', 1),
(12, 'Bereavement Leave', 3, 'Leave due to death of immediate family member', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 56, 'New Registration', 'New student registration: Student2 (2024-0003) | Section: Computer Science 1-A (CS1A)', 0, '2026-02-18 00:52:13'),
(2, 58, 'Application Approved', 'Your enrollment application has been approved! You can now proceed with subject enrollment.', 0, '2026-02-18 00:52:36'),
(3, 57, 'Application Approved', 'Your enrollment application has been approved! You can now proceed with subject enrollment.', 0, '2026-02-18 01:01:43'),
(4, 58, 'Study Load Assigned', 'Your study load has been finalized. You can now view your enrolled subjects.', 0, '2026-02-18 01:24:53'),
(5, 57, 'Study Load Assigned', 'Your study load has been finalized. You can now view your enrolled subjects.', 0, '2026-02-18 01:25:03'),
(6, 30, 'Subject Request approved', 'Your Drop request has been approved. Note: are you sure', 0, '2026-02-18 02:20:56'),
(7, 56, 'New Registration', 'New student registration: Student3 (2024-0004)', 0, '2026-02-18 02:44:39'),
(8, 30, 'Study Load Assigned', 'Your study load has been finalized. You can now view your enrolled subjects.', 0, '2026-02-18 05:20:33'),
(9, 56, 'New Registration', 'New student registration: Johannes Tolentino (SCC-23-00018327) | Section: BSIT-1 A (BSIT)', 0, '2026-02-18 05:25:28'),
(10, 60, 'Application Approved', 'Your enrollment application has been approved! You can now proceed with subject enrollment.', 0, '2026-02-18 05:26:34'),
(11, 56, 'New Registration', 'New student registration: Rcjie N. Villena (2024-00033)', 0, '2026-02-18 06:08:36'),
(12, 61, 'Application Approved', 'Your enrollment application has been approved! You can now proceed with subject enrollment.', 0, '2026-02-18 06:08:55'),
(13, 56, 'New Registration', 'New student registration: John Carlo D. Ababan (2024-01110)', 0, '2026-02-18 06:31:00'),
(14, 62, 'Application Approved', 'Your enrollment application has been approved! You can now proceed with subject enrollment.', 0, '2026-02-18 06:31:25'),
(15, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for CS202 Object-Oriented Programming — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 03:15:39'),
(16, 2, '? Grade Sheet Approved', 'Your grade submission for CS202 Object-Oriented Programming — Section BSIT-1 A has been approved.', 0, '2026-02-21 03:15:57'),
(17, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for CS202 Object-Oriented Programming — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 03:21:47'),
(18, 2, '? Grade Sheet Approved', 'Your grade submission for CS202 Object-Oriented Programming — Section BSIT-1 A has been approved.', 0, '2026-02-21 03:22:36'),
(19, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for CS202 Object-Oriented Programming — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 03:40:16'),
(20, 2, '? Grade Sheet Rejected', 'Your grade submission for CS202 Object-Oriented Programming — Section BSIT-1 A has been rejected.', 0, '2026-02-21 04:02:04'),
(21, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for CS202 Object-Oriented Programming — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 04:19:13'),
(22, 2, '? Grade Sheet Approved', 'Your grade submission for CS202 Object-Oriented Programming — Section BSIT-1 A has been approved.', 0, '2026-02-21 04:19:30'),
(23, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for MATH 101 Mathematics in the Modern World — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 04:21:31'),
(24, 2, '? Grade Sheet Approved', 'Your grade submission for MATH 101 Mathematics in the Modern World — Section BSIT-1 A has been approved.', 0, '2026-02-21 04:21:51'),
(25, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for ENG101 English Communication — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 04:23:02'),
(26, 2, '? Grade Sheet Rejected', 'Your grade submission for ENG101 English Communication — Section BSIT-1 A has been rejected.', 0, '2026-02-21 04:23:23'),
(27, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for ENG101 English Communication — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 04:24:44'),
(28, 2, '? Grade Sheet Approved', 'Your grade submission for ENG101 English Communication — Section BSIT-1 A has been approved.', 0, '2026-02-21 04:25:04'),
(29, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for CS202 Object-Oriented Programming — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 04:30:52'),
(30, 2, '? Grade Sheet Approved', 'Your grade submission for CS202 Object-Oriented Programming — Section BSIT-1 A has been approved.', 0, '2026-02-21 04:31:12'),
(31, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for CS202 Object-Oriented Programming — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 04:33:32'),
(32, 2, '? Grade Sheet Approved', 'Your grade submission for CS202 Object-Oriented Programming — Section BSIT-1 A has been approved.', 0, '2026-02-21 04:34:01'),
(33, 60, '? Grade Updated', 'Your grade has been updated by your teacher.', 0, '2026-02-21 04:37:54'),
(34, 61, '? Grade Updated', 'Your grade has been updated by your teacher.', 0, '2026-02-21 04:37:55'),
(35, 30, '? Grade Updated', 'Your grade has been updated by your teacher.', 0, '2026-02-21 04:37:56'),
(36, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for MATH 101 Mathematics in the Modern World — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 04:45:11'),
(37, 2, '? Grade Sheet Approved', 'Your grade submission for MATH 101 Mathematics in the Modern World — BSIT-1 A has been approved.', 0, '2026-02-21 04:46:35'),
(38, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for MATH 101 Mathematics in the Modern World — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 05:05:48'),
(39, 2, '? Grade Sheet Approved', 'Your grade submission for MATH 101 Mathematics in the Modern World — BSIT-1 A has been approved.', 0, '2026-02-21 05:06:13'),
(40, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for MATH 101 Mathematics in the Modern World — Section BSIT-1 A (with Excel file).', 0, '2026-02-21 05:19:33'),
(41, 2, '? Grade Sheet Approved', 'Your grade submission for MATH 101 Mathematics in the Modern World — BSIT-1 A has been approved.', 0, '2026-02-21 05:19:56'),
(42, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for MATH 101 Mathematics in the Modern World — Section BSIT-1 A.', 0, '2026-02-21 05:26:05'),
(43, 2, '? Grade Sheet Approved', 'Your grade submission for MATH 101 Mathematics in the Modern World — BSIT-1 A has been approved.', 0, '2026-02-21 05:26:28'),
(44, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for MATH 101 Mathematics in the Modern World — Section BSIT-1 A.', 0, '2026-02-21 05:32:53'),
(45, 60, '? Grade Posted', 'Your grade for MATH 101 Mathematics in the Modern World (1st Semester 2026-2027) has been recorded.', 0, '2026-02-21 05:33:08'),
(46, 61, '? Grade Posted', 'Your grade for MATH 101 Mathematics in the Modern World (1st Semester 2026-2027) has been recorded.', 0, '2026-02-21 05:33:08'),
(47, 30, '? Grade Posted', 'Your grade for MATH 101 Mathematics in the Modern World (1st Semester 2026-2027) has been recorded.', 0, '2026-02-21 05:33:08'),
(48, 2, '? Grade Sheet Approved', 'Your grade submission for MATH 101 Mathematics in the Modern World — BSIT-1 A has been approved. (3 grade(s) recorded.)', 0, '2026-02-21 05:33:08'),
(49, 56, '? Grade Sheet Submitted', 'Teacher submitted grades for ENG101 English Communication — Section BSIT-1 A.', 0, '2026-02-21 05:52:03'),
(50, 60, '? Grade Posted', 'Your grade for ENG101 English Communication (1st Semester 2026-2027) has been recorded.', 0, '2026-02-21 05:52:23'),
(51, 61, '? Grade Posted', 'Your grade for ENG101 English Communication (1st Semester 2026-2027) has been recorded.', 0, '2026-02-21 05:52:23'),
(52, 30, '? Grade Posted', 'Your grade for ENG101 English Communication (1st Semester 2026-2027) has been recorded.', 0, '2026-02-21 05:52:23'),
(53, 2, '? Grade Sheet Approved', 'Your grade submission for ENG101 English Communication — BSIT-1 A has been approved. (3 grade(s) recorded.)', 0, '2026-02-21 05:52:23'),
(54, 59, 'Application Approved', 'Your enrollment application has been approved! You can now proceed with subject enrollment.', 0, '2026-02-21 05:55:17'),
(55, 30, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:45'),
(56, 57, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:45'),
(57, 58, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:45'),
(58, 59, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:45'),
(59, 60, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:45'),
(60, 61, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:45'),
(61, 62, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:45'),
(62, 2, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:46'),
(63, 56, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:46'),
(64, 1, 'New Announcement: Pre Trial', 'trial attacthment', 0, '2026-02-21 07:14:46'),
(65, 30, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(66, 57, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(67, 58, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(68, 59, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(69, 60, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(70, 61, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(71, 62, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(72, 2, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(73, 56, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(74, 1, 'New Announcement: trial attachment', 'testing trial', 0, '2026-02-23 01:48:03'),
(75, 1, 'New Leave Request ?', 'Teacher submitted a leave request (2026-02-23 to 2026-02-24)', 0, '2026-02-23 03:06:24'),
(76, 2, 'Leave Request Approved ?', 'Your Emergency Leave (2026-02-23 to 2026-02-24) has been approved.', 0, '2026-02-23 03:06:58'),
(77, 56, 'New Enrollment Application', 'New enrollment application from Rcjie N. Villena (SCC-21-00014266) — Bachelor of Science in Information Technology 3rd Year', 0, '2026-02-23 03:39:24'),
(78, 1, 'New Enrollment Application', 'New enrollment application from Rcjie N. Villena (SCC-21-00014266) — Bachelor of Science in Information Technology 3rd Year', 0, '2026-02-23 03:39:24'),
(79, 64, 'Application Approved', 'Your enrollment application has been approved! You can now proceed with subject enrollment.', 0, '2026-02-23 03:40:33'),
(80, 56, 'New Enrollment Application', 'New enrollment application from Jiggy N. Getuaban (SCC-21-00014267) — Bachelor of Science in Information Technology 1st Year', 0, '2026-02-23 04:51:01'),
(81, 1, 'New Enrollment Application', 'New enrollment application from Jiggy N. Getuaban (SCC-21-00014267) — Bachelor of Science in Information Technology 1st Year', 0, '2026-02-23 04:51:01');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `building_id` int(11) NOT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `room_type` varchar(100) DEFAULT NULL,
  `accessibility_features` text DEFAULT NULL,
  `coordinates_x` decimal(10,6) DEFAULT NULL,
  `coordinates_y` decimal(10,6) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `building_id`, `floor`, `capacity`, `room_type`, `accessibility_features`, `coordinates_x`, `coordinates_y`) VALUES
(1, '101', 1, 'Ground Floor', 40, 'Lecture Room', 'Wheelchair accessible', NULL, NULL),
(2, '201', 1, '2nd Floor', 35, 'Computer Lab', 'Elevator access', NULL, NULL),
(3, '301', 2, '3rd Floor', 30, 'Science Lab', 'Wheelchair accessible, Elevator access', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `study_load_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(100) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `floor` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) NOT NULL,
  `section_name` varchar(100) NOT NULL,
  `section_code` varchar(50) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `max_students` int(11) DEFAULT 40,
  `room` varchar(100) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `adviser_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `section_name`, `section_code`, `course`, `year_level`, `semester`, `school_year`, `max_students`, `room`, `building`, `adviser_id`, `status`, `created_at`) VALUES
(1, 'BSIT-1 A', 'BSIT', 'Bachelor of Science in Information Technology', '1st Year', '1st Semester', '2026-2027', 40, '0', 'main building', 2, 'active', '2026-02-18 02:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `section_schedules`
--

CREATE TABLE `section_schedules` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `section_subject_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(100) DEFAULT NULL,
  `building` varchar(100) DEFAULT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `section_schedules`
--

INSERT INTO `section_schedules` (`id`, `section_id`, `section_subject_id`, `day_of_week`, `start_time`, `end_time`, `room`, `building`, `floor`, `created_at`) VALUES
(1, 1, 3, 'Wednesday', '10:00:00', '12:00:00', 'Room 101', 'main building', '1st Floor', '2026-02-18 05:08:40'),
(2, 1, 3, 'Wednesday', '13:00:00', '15:00:00', 'Room 101', 'main building', '1st Floor', '2026-02-18 05:09:30');

-- --------------------------------------------------------

--
-- Table structure for table `section_subjects`
--

CREATE TABLE `section_subjects` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `section_subjects`
--

INSERT INTO `section_subjects` (`id`, `section_id`, `subject_id`, `teacher_id`, `created_at`) VALUES
(1, 1, 3, 2, '2026-02-18 05:07:19'),
(2, 1, 6, 2, '2026-02-18 05:07:38'),
(3, 1, 5, 2, '2026-02-18 05:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `study_loads`
--

CREATE TABLE `study_loads` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `status` enum('draft','finalized') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `study_loads`
--

INSERT INTO `study_loads` (`id`, `student_id`, `subject_id`, `teacher_id`, `section_id`, `section`, `semester`, `school_year`, `status`, `created_at`) VALUES
(8, 30, 3, 2, 1, NULL, NULL, NULL, 'finalized', '2026-02-18 05:19:59'),
(9, 30, 6, 2, 1, NULL, NULL, NULL, 'finalized', '2026-02-18 05:19:59'),
(10, 30, 5, 2, 1, NULL, NULL, NULL, 'finalized', '2026-02-18 05:19:59'),
(11, 60, 3, 2, 1, NULL, NULL, NULL, 'draft', '2026-02-18 05:27:03'),
(12, 60, 6, 2, 1, NULL, NULL, NULL, 'draft', '2026-02-18 05:27:03'),
(13, 60, 5, 2, 1, NULL, NULL, NULL, 'draft', '2026-02-18 05:27:03'),
(14, 61, 3, 2, 1, NULL, NULL, NULL, 'draft', '2026-02-19 04:07:29'),
(15, 61, 6, 2, 1, NULL, NULL, NULL, 'draft', '2026-02-19 04:07:29'),
(16, 61, 5, 2, 1, NULL, NULL, NULL, 'draft', '2026-02-19 04:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `units` int(11) NOT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `prerequisites` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `description`, `units`, `course`, `year_level`, `prerequisites`, `status`, `created_at`) VALUES
(1, 'CS101', 'Introduction to Computer Science', NULL, 3, 'Computer Science', '1st Year', NULL, 'active', '2026-02-13 01:56:18'),
(3, 'ENG101', 'English Communication', 'Focuses on effective communication in professional/academic contexts (writing, speaking, presentations).', 3, NULL, '1st Year', 'ENG101', 'active', '2026-02-13 01:56:18'),
(4, 'CS201', 'Data Structures and Algorithms', NULL, 3, 'Computer Science', '2nd Year', NULL, 'active', '2026-02-13 01:56:18'),
(5, 'CS202', 'Object-Oriented Programming', '', 3, 'Bachelor of Science in Information Technology', '1st Year', 'CS201', 'active', '2026-02-13 01:56:18'),
(6, 'MATH 101', 'Mathematics in the Modern World', 'Explores mathematical concepts for everyday life, logic, patterns, statistics, and quantitative reasoning', 3, NULL, '1st Year', 'MATH 101', 'active', '2026-02-18 05:02:36');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'registration_open', '0', 'Whether student registration is currently open', '2026-02-16 06:39:35'),
(2, 'current_semester', 'First Semester', 'Current active semester', '2026-02-13 01:56:18'),
(3, 'current_school_year', '2024-2025', 'Current school year', '2026-02-13 01:56:18'),
(4, 'school_name', 'Saint Cecilia College Cebu Inc.', 'Official school name', '2026-02-16 06:39:35');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_specialties`
--

CREATE TABLE `teacher_specialties` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `is_primary` tinyint(1) DEFAULT 0,
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `teacher_specialties`
--

INSERT INTO `teacher_specialties` (`id`, `teacher_id`, `subject_id`, `proficiency_level`, `is_primary`, `assigned_date`) VALUES
(1, 2, 5, 'expert', 0, '2026-02-13 03:54:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher','registrar','admin','hr') NOT NULL,
  `status` enum('pending','approved','rejected','active','inactive') DEFAULT 'pending',
  `avatar_url` varchar(500) DEFAULT NULL,
  `theme_preference` varchar(50) DEFAULT NULL,
  `deactivated_until` datetime DEFAULT NULL,
  `deactivation_reason` text DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `office_hours` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `session_token` varchar(64) DEFAULT NULL,
  `session_started_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE `enrollment_details` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `civil_status` varchar(30) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `place_of_birth` varchar(255) DEFAULT NULL,
  `mobile_number` varchar(50) DEFAULT NULL,
  `landline_number` varchar(50) DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `enrollment_type` varchar(50) DEFAULT 'New Student',
  `semester` varchar(30) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `prev_school` varchar(255) DEFAULT NULL,
  `prev_school_addr` varchar(255) DEFAULT NULL,
  `prev_school_year` varchar(20) DEFAULT NULL,
  `father_name` varchar(255) DEFAULT NULL,
  `father_occupation` varchar(255) DEFAULT NULL,
  `father_contact` varchar(50) DEFAULT NULL,
  `father_income` varchar(100) DEFAULT NULL,
  `mother_name` varchar(255) DEFAULT NULL,
  `mother_occupation` varchar(255) DEFAULT NULL,
  `mother_contact` varchar(50) DEFAULT NULL,
  `mother_income` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_relation` varchar(100) DEFAULT NULL,
  `guardian_contact` varchar(50) DEFAULT NULL,
  `guardian_address` text DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_relation` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `avatar_url`, `theme_preference`, `deactivated_until`, `deactivation_reason`, `student_id`, `course`, `year_level`, `section_id`, `department`, `office_location`, `office_hours`, `profile_image`, `created_at`, `updated_at`, `archived_at`) VALUES
(1, 'System Administrator', 'admin@school.edu', '$2y$10$7V2VovTslTG268Sl7HjFeOojYPfVJPCnkAovRJgUYGhdV0LN2OTJu', 'admin', 'active', NULL, 'rose', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-13 01:56:18', '2026-02-21 06:47:14', NULL),
(2, 'Teacher', 'teacher@gmail.com', '$2y$10$cMyN6NTMrDdApGmXwlRb2ecPtn8rxcsQ0KaoZnLuEmlkBF9L7LmcS', 'teacher', 'active', NULL, 'rose', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-13 01:58:24', '2026-02-21 07:04:56', NULL),
(30, 'student', 'Student@gmail.com', '$2y$10$UmzP9JilkDHvnp5DgNQ5x.BfS658pvNzF4f9IvvB5h/t2FxEarrzS', 'student', 'approved', NULL, NULL, NULL, NULL, '2024-00001', 'Computer Science', '1st Year', 1, '', '', '', NULL, '2026-02-13 02:22:52', '2026-02-18 05:19:59', NULL),
(56, 'Registrar', 'registrar@gmail.com', '$2y$10$cx9Pw8qkS2sD8/qq0WL7tOkQo0M.KP5Cb9Rztczfgvd/EBozDPNUm', 'registrar', 'active', NULL, 'rose', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-13 03:11:59', '2026-02-21 07:06:26', NULL),
(57, 'Student1', 'student1@gmail.com', '$2y$10$eBb.ACPUCggqBHWFE/U5n.RzIYZ/XEZ5u/0CPsJYBHfzNte3qOYsq', 'student', 'approved', NULL, NULL, NULL, NULL, '2024-0002', 'Information Technology', '2nd Year', NULL, NULL, NULL, NULL, NULL, '2026-02-16 06:40:11', '2026-02-18 05:13:29', NULL),
(58, 'Student2', 'student2@gmail.com', '$2y$10$5FS0ly1uJKuwckubVs/a4.Wee9Bpx8FGS7fVH14sSEPYStEhL1NnG', 'student', 'approved', NULL, NULL, NULL, NULL, '2024-0003', 'Computer Science', '1st Year', NULL, NULL, NULL, NULL, NULL, '2026-02-18 00:52:13', '2026-02-18 05:13:32', NULL),
(59, 'Student3', 'student3@gmail.com', '$2y$10$352mXHpmMedsyjKHJj9ir.CwmxCJos5gGPT3ACZY/w.AaS634w4x6', 'student', 'active', NULL, NULL, NULL, NULL, '2024-0004', 'Bachelor of Science in Information Technology', '1st Year', NULL, NULL, NULL, NULL, NULL, '2026-02-18 02:44:39', '2026-02-21 06:52:13', NULL),
(60, 'Johannes Tolentino', 'mcetolentino0911@gmail.com', '$2y$10$OZ5PN/Pmo/HwGUVZ8FybBeHWJiw5HaSK188.fdUsgbIt9stV145Gm', 'student', 'approved', NULL, NULL, NULL, NULL, 'SCC-23-00018327', 'Bachelor of Science in Information Technology', '1st Year', 1, NULL, NULL, NULL, NULL, '2026-02-18 05:25:28', '2026-02-18 05:26:34', NULL),
(61, 'Rcjie N. Villena', 'rcjiez@gmail.com', '$2y$10$24AtRcqVu5mFNqGVAtNm5OKZLqlvbhvvFU488NCQnCEr/b5FV9Wiu', 'student', 'approved', NULL, 'rose', NULL, NULL, '2024-00033', 'Bachelor of Science in Computer Science', '1st Year', 1, NULL, NULL, NULL, NULL, '2026-02-18 06:08:36', '2026-02-21 06:38:54', NULL),
(62, 'John Carlo D. Ababan', 'ababan@gmail.com', '$2y$10$4xrmIcxugfrkTrpQIPdHNOeUnYosuO7Pfjw4WEvztXJyWkdMZwoI.', 'student', 'approved', NULL, NULL, NULL, NULL, '2024-01110', 'Bachelor of Science in Hospitality and Tourism Management.', '1st Year', NULL, NULL, NULL, NULL, NULL, '2026-02-18 06:31:00', '2026-02-18 06:31:25', NULL),
(63, 'HR Officer', 'hr@school.edu', '$2y$10$X.YLd6TPJcRPcC.5LduCPO.KPI/YgqYtcO71fIxpDM.j/4hx5ifUu', 'hr', 'active', NULL, 'rose', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-23 02:40:43', '2026-02-23 02:42:35', NULL),
(64, 'Rcjie N. Villena', 'rcjie@gmail.com', '$2y$10$Qn8jdXe.960GBxEBHuYhseAgpaFnO32Cb6Qe3e/3Dg5yiMcb3I5ia', 'student', 'approved', NULL, NULL, NULL, NULL, 'SCC-21-00014266', 'Bachelor of Science in Information Technology', '3rd Year', NULL, NULL, NULL, NULL, NULL, '2026-02-23 03:39:24', '2026-02-23 03:40:33', NULL),
(65, 'Jiggy N. Getuaban', 'jiggy@gmail.com', '$2y$10$mFLN7zrZKzMWaKYwnE2R0eCJAN.KdLJB51a3mTV4IyEuuMMdERyEi', 'student', 'pending', 'uploads/avatars/enroll_1771822260_2813.jpg', NULL, NULL, NULL, 'SCC-21-00014267', 'Bachelor of Science in Information Technology', '1st Year', 1, NULL, NULL, NULL, NULL, '2026-02-23 04:51:01', '2026-02-23 04:51:01', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_drop_requests`
--
ALTER TABLE `add_drop_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`);

--
-- Indexes for table `announcement_attachments`
--
ALTER TABLE `announcement_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `building_code` (`building_code`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code_unique` (`course_code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_code` (`department_code`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `enrollment_details`
--
ALTER TABLE `enrollment_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback_attachments`
--
ALTER TABLE `feedback_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `feedback_id` (`feedback_id`);

--
-- Indexes for table `floor_plan_routes`
--
ALTER TABLE `floor_plan_routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visible_routes` (`visible_to_students`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `grade_submissions`
--
ALTER TABLE `grade_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `hr_leave_balances`
--
ALTER TABLE `hr_leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_type_year` (`user_id`,`leave_type_id`,`year`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `hr_leave_types`
--
ALTER TABLE `hr_leave_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `building_id` (`building_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `study_load_id` (`study_load_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_code` (`section_code`),
  ADD KEY `adviser_id` (`adviser_id`);

--
-- Indexes for table `section_schedules`
--
ALTER TABLE `section_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `section_subject_id` (`section_subject_id`);

--
-- Indexes for table `section_subjects`
--
ALTER TABLE `section_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_section_subject` (`section_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `study_loads`
--
ALTER TABLE `study_loads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `sl_section_fk` (`section_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `teacher_specialties`
--
ALTER TABLE `teacher_specialties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_subject` (`teacher_id`,`subject_id`),
  ADD KEY `idx_teacher_id` (`teacher_id`),
  ADD KEY `idx_subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `users_section_fk` (`section_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_drop_requests`
--
ALTER TABLE `add_drop_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcement_attachments`
--
ALTER TABLE `announcement_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment_details`
--
ALTER TABLE `enrollment_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback_attachments`
--
ALTER TABLE `feedback_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `floor_plan_routes`
--
ALTER TABLE `floor_plan_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `grade_submissions`
--
ALTER TABLE `grade_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `hr_employees`
--
ALTER TABLE `hr_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_leave_balances`
--
ALTER TABLE `hr_leave_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `hr_leave_types`
--
ALTER TABLE `hr_leave_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `section_schedules`
--
ALTER TABLE `section_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `section_subjects`
--
ALTER TABLE `section_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `study_loads`
--
ALTER TABLE `study_loads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `teacher_specialties`
--
ALTER TABLE `teacher_specialties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `add_drop_requests`
--
ALTER TABLE `add_drop_requests`
  ADD CONSTRAINT `adr_reviewer_fk` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `adr_student_fk` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `adr_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcement_attachments`
--
ALTER TABLE `announcement_attachments`
  ADD CONSTRAINT `announcement_attachments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_dept_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollment_details`
--
ALTER TABLE `enrollment_details`
  ADD CONSTRAINT `enrollment_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_attachments`
--
ALTER TABLE `feedback_attachments`
  ADD CONSTRAINT `feedback_attachments_ibfk_1` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `floor_plan_routes`
--
ALTER TABLE `floor_plan_routes`
  ADD CONSTRAINT `floor_plan_routes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hr_employees`
--
ALTER TABLE `hr_employees`
  ADD CONSTRAINT `hr_employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hr_leave_balances`
--
ALTER TABLE `hr_leave_balances`
  ADD CONSTRAINT `hr_leave_balances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hr_leave_balances_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types` (`id`);

--
-- Constraints for table `hr_leave_requests`
--
ALTER TABLE `hr_leave_requests`
  ADD CONSTRAINT `hr_leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hr_leave_requests_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `hr_leave_types` (`id`),
  ADD CONSTRAINT `hr_leave_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`study_load_id`) REFERENCES `study_loads` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`adviser_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `section_schedules`
--
ALTER TABLE `section_schedules`
  ADD CONSTRAINT `sch_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sch_ibfk_2` FOREIGN KEY (`section_subject_id`) REFERENCES `section_subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `section_subjects`
--
ALTER TABLE `section_subjects`
  ADD CONSTRAINT `ss_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ss_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ss_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `study_loads`
--
ALTER TABLE `study_loads`
  ADD CONSTRAINT `sl_section_fk` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `study_loads_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `study_loads_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `study_loads_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_specialties`
--
ALTER TABLE `teacher_specialties`
  ADD CONSTRAINT `teacher_specialties_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_specialties_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_section_fk` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
