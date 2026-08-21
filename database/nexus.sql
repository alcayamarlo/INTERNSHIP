-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2026 at 03:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `nexus` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nexus`;

DROP TABLE IF EXISTS `student_competencies`, `system_logs`, `students`, `resumes`, `reports`, `portfolios`, `password_reset_tokens`, `notifications`, `messages`, `migrations`, `job_batches`, `jobs`, `internship_requirements`, `internship_applications`, `internships`, `institutions`, `failed_jobs`, `employers`, `coordinators`, `competencies`, `certificates`, `cache_locks`, `cache`, `announcements`, `users`;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nexus`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_role` varchar(255) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-16327314363e8d7212f97437d8567042', 'i:1;', 1782825502),
('laravel-cache-16327314363e8d7212f97437d8567042:timer', 'i:1782825502;', 1782825502),
('laravel-cache-7f9f28f2841172ee5b6ea9301ca7590c', 'i:2;', 1782825442),
('laravel-cache-7f9f28f2841172ee5b6ea9301ca7590c:timer', 'i:1782825442;', 1782825442),
('laravel-cache-d429c1fac70c1417028d4a5e7fc61532', 'i:1;', 1782825416),
('laravel-cache-d429c1fac70c1417028d4a5e7fc61532:timer', 'i:1782825416;', 1782825416);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `verification_status` varchar(255) NOT NULL DEFAULT 'evidence_submitted',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `student_id`, `title`, `issuer`, `issue_date`, `file_path`, `created_at`, `updated_at`) VALUES
(1, 1, 'AWS Certified Cloud Practitioner', 'Amazon Web Services', '2026-03-30', 'portfolios/1/cert_aws.pdf', '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `competencies`
--

CREATE TABLE `competencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'skill',
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `competencies`
--

INSERT INTO `competencies` (`id`, `name`, `category`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'PHP Development', 'technical', NULL, 1, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(2, 'Laravel Framework', 'technical', NULL, 1, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(3, 'Database Design', 'technical', NULL, 1, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(4, 'Problem Solving', 'soft', NULL, 1, '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `coordinators`
--

CREATE TABLE `coordinators` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `institution_id` bigint(20) UNSIGNED DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `office_address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coordinators`
--

INSERT INTO `coordinators` (`id`, `user_id`, `institution_id`, `department`, `profile_picture`, `office_address`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Internship Office', NULL, NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `employers`
--

CREATE TABLE `employers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employers`
--

INSERT INTO `employers` (`id`, `user_id`, `company_name`, `industry`, `description`, `website`, `logo`, `address`, `contact_person`, `created_at`, `updated_at`) VALUES
(1, 3, 'TechNova Solutions', 'Information Technology', 'Software development and digital transformation company.', 'https://technova.example.com', NULL, '456 Business Park, Makati', 'John Reyes', '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `institutions`
--

CREATE TABLE `institutions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `institutions`
--

INSERT INTO `institutions` (`id`, `name`, `address`, `contact_email`, `contact_phone`, `description`, `created_at`, `updated_at`) VALUES
(1, 'St. Cecilia''s College-Cebu, Inc.', NULL, NULL, NULL, NULL, '2026-06-30 04:23:25', '2026-06-30 04:23:25');

-- --------------------------------------------------------

--
-- Table structure for table `internships`
--

CREATE TABLE `internships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `responsibilities` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `allowance` decimal(10,2) DEFAULT NULL,
  `work_setup` varchar(255) NOT NULL DEFAULT 'onsite',
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internships`
--

INSERT INTO `internships` (`id`, `employer_id`, `title`, `description`, `responsibilities`, `requirements`, `duration`, `allowance`, `work_setup`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Web Developer Intern', 'Join our development team to build modern web applications.', 'Develop features using Laravel\nCollaborate with senior developers\nWrite clean, tested code', 'BS IT or related field, knowledge of PHP and Laravel.', '3 months', 8000.00, 'hybrid', 'Makati City', 'open', '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(2, 1, 'UI/UX Design Intern', 'Support our design team in creating user-friendly interfaces.', NULL, NULL, '2 months', 6000.00, 'remote', 'Remote', 'open', '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `internship_applications`
--

CREATE TABLE `internship_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `internship_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `cover_letter` text DEFAULT NULL,
  `match_percentage` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `interview_at` timestamp NULL DEFAULT NULL,
  `employer_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internship_applications`
--

INSERT INTO `internship_applications` (`id`, `internship_id`, `student_id`, `status`, `cover_letter`, `match_percentage`, `applied_at`, `interview_at`, `employer_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'reviewed', 'I am excited to apply for this internship opportunity.', 85, '2026-06-27 04:23:26', NULL, NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `internship_requirements`
--

CREATE TABLE `internship_requirements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `internship_id` bigint(20) UNSIGNED NOT NULL,
  `competency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `skill_id` bigint(20) UNSIGNED DEFAULT NULL,
  `requirement_name` varchar(255) NOT NULL,
  `required_level` varchar(255) NOT NULL DEFAULT 'intermediate',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `internship_requirements`
--

INSERT INTO `internship_requirements` (`id`, `internship_id`, `competency_id`, `skill_id`, `requirement_name`, `required_level`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, 'PHP Development', 'intermediate', '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(2, 1, NULL, NULL, 'Laravel Framework', 'beginner', '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sender_id` bigint(20) UNSIGNED NOT NULL,
  `receiver_id` bigint(20) UNSIGNED NOT NULL,
  `body` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(11, '0001_01_01_000000_create_users_table', 1),
(12, '0001_01_01_000001_create_cache_table', 1),
(13, '0001_01_01_000002_create_jobs_table', 1),
(14, '2026_06_30_000001_create_institutions_table', 1),
(15, '2026_06_30_000002_create_role_profiles_table', 1),
(16, '2026_06_30_000003_create_competencies_tables', 1),
(17, '2026_06_30_000004_create_portfolio_tables', 1),
(18, '2026_06_30_000005_create_internship_tables', 1),
(19, '2026_06_30_000006_create_communication_tables', 1),
(20, '2026_06_30_000007_add_profile_to_coordinators', 1),
(21, '2026_06_30_124845_normalize_admin_user_role', 2),
(22, '2026_08_22_000001_add_evidence_and_persistent_profile_fields', 3);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolios`
--

CREATE TABLE `portfolios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'project',
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `portfolios`
--

INSERT INTO `portfolios` (`id`, `student_id`, `title`, `type`, `description`, `file_path`, `created_at`, `updated_at`) VALUES
(1, 1, 'E-Commerce Platform Project', 'project', 'Full-stack e-commerce platform built with Laravel and Vue.js. Features include product catalog, shopping cart, payment integration, and admin dashboard.', 'portfolios/1/project_ecommerce.pdf', '2026-04-30 04:23:26', '2026-06-30 04:23:26'),
(2, 1, 'AWS Certified Cloud Practitioner', 'certificate', 'Official AWS certification demonstrating cloud computing knowledge.', 'portfolios/1/cert_aws.pdf', '2026-03-30 04:23:26', '2026-06-30 04:23:26'),
(3, 1, 'GitHub Portfolio', 'project', 'Collection of personal projects showcasing coding skills and best practices.', 'portfolios/1/portfolio_github.pdf', '2026-05-30 04:23:26', '2026-06-30 04:23:26'),
(4, 1, 'Capstone Project Documentation', 'project', 'Final year capstone project: Skill-Bridge Internship Platform. Complete documentation including architecture, database design, and implementation details.', 'portfolios/1/capstone_documentation.pdf', '2026-03-02 04:23:26', '2026-06-30 04:23:26'),
(5, 1, 'Academic Transcript', 'transcript', 'Official academic transcript from Metro State University.', 'portfolios/1/transcript.pdf', '2026-05-30 04:23:26', '2026-06-30 04:23:26'),
(6, 1, 'Recommendation Letter - Professor John Doe', 'award', 'Letter of recommendation from Professor John Doe, highlighting technical excellence and leadership.', 'portfolios/1/recommendation_letter.pdf', '2026-06-16 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `generated_by` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `file_path` varchar(255) DEFAULT NULL,
  `format` varchar(255) NOT NULL DEFAULT 'pdf',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resumes`
--

CREATE TABLE `resumes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `file_path` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `resumes`
--

INSERT INTO `resumes` (`id`, `student_id`, `content`, `file_path`, `generated_at`, `created_at`, `updated_at`) VALUES
(1, 1, '{\"personal\":{\"name\":\"Anna Dela Cruz\",\"email\":\"student@skillbridge.test\",\"phone\":\"+63 900 000 0004\",\"address\":\"789 Student St, Quezon City\",\"program\":\"BS Information Technology\",\"year_level\":\"4th Year\",\"institution\":\"Metro State University\"},\"objectives\":\"Seeking a software development internship to apply full-stack skills.\",\"competencies\":[{\"name\":\"PHP Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Built web applications using PHP.\"},{\"name\":\"Laravel Framework\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Developed MVC applications with Laravel.\"},{\"name\":\"Communication\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":null},{\"name\":\"JavaScript\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Frontend development with vanilla JavaScript and frameworks.\"},{\"name\":\"MySQL Database Design\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Database design and SQL optimization.\"},{\"name\":\"AWS Cloud Services\",\"category\":\"Certification\",\"level\":\"Intermediate\",\"description\":\"AWS Certified Cloud Practitioner (Passed)\"},{\"name\":\"Team Leadership\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":\"Led a team of 5 developers on capstone project.\"},{\"name\":\"Project Management\",\"category\":\"Soft Skill\",\"level\":\"Intermediate\",\"description\":\"Managed multiple projects using Agile methodologies.\"},{\"name\":\"Git Version Control\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Git and GitHub for collaborative development.\"},{\"name\":\"REST API Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Building and consuming REST APIs.\"}],\"certificates\":[{\"title\":\"AWS Certified Cloud Practitioner\",\"issuer\":\"Amazon Web Services\",\"issue_date\":\"Mar 2026\"}],\"portfolios\":[{\"title\":\"E-Commerce Platform Project\",\"type\":\"Project\",\"description\":\"Full-stack e-commerce platform built with Laravel and Vue.js. Features include product catalog, shopping cart, payment integration, and admin dashboard.\"},{\"title\":\"AWS Certified Cloud Practitioner\",\"type\":\"Certificate\",\"description\":\"Official AWS certification demonstrating cloud computing knowledge.\"},{\"title\":\"GitHub Portfolio\",\"type\":\"Project\",\"description\":\"Collection of personal projects showcasing coding skills and best practices.\"},{\"title\":\"Capstone Project Documentation\",\"type\":\"Project\",\"description\":\"Final year capstone project: Skill-Bridge Internship Platform. Complete documentation including architecture, database design, and implementation details.\"},{\"title\":\"Academic Transcript\",\"type\":\"Transcript\",\"description\":\"Official academic transcript from Metro State University.\"},{\"title\":\"Recommendation Letter - Professor John Doe\",\"type\":\"Award\",\"description\":\"Letter of recommendation from Professor John Doe, highlighting technical excellence and leadership.\"}]}', 'resumes/student_1_1782825475.pdf', '2026-06-30 05:17:55', '2026-06-30 05:17:55', '2026-06-30 05:17:55');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('epprcERFawOSNA9Llr7g5t7WlqlELzoNDT5rpxQE', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoicGhUQ3BUenVlOUNHT2xXdmhiVEd6Z1E5cXAzY2VDRDJESjMxTmhudiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zdHVkZW50L3Byb2ZpbGUiO3M6NToicm91dGUiO3M6MjA6InN0dWRlbnQucHJvZmlsZS5lZGl0Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6NDt9', 1782825554),
('oQY5P16oGfZlXRGq5V7W6bcErlodyEkbXy1BXEDz', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/3.9.16 Chrome/144.0.7559.236 Electron/40.10.3 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoialQ5aW0zZ0ExOTh3c0oyR1FpdkVrRnMycXJTRm5EUThKTkNoNGhoYSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1782823812);

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'skill',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `name`, `category`, `description`, `created_at`, `updated_at`) VALUES
(1, 'PHP', 'technical', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(2, 'Laravel', 'technical', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(3, 'JavaScript', 'technical', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(4, 'MySQL', 'technical', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(5, 'Communication', 'soft', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(6, 'Teamwork', 'soft', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `institution_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id_number` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `year_level` varchar(255) DEFAULT NULL,
  `career_objectives` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `zip_code` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `expected_graduation` date DEFAULT NULL,
  `preferred_internship_field` varchar(255) DEFAULT NULL,
  `preferred_work_setup` varchar(255) DEFAULT NULL,
  `preferred_location` varchar(255) DEFAULT NULL,
  `profile_completion` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `institution_id`, `student_id_number`, `program`, `year_level`, `career_objectives`, `profile_picture`, `address`, `date_of_birth`, `profile_completion`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 'MSU-2024-001', 'BS Information Technology', '4th Year', 'Seeking a software development internship to apply full-stack skills.', NULL, '789 Student St, Quezon City', NULL, 82, '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `student_competencies`
--

CREATE TABLE `student_competencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `competency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'skill',
  `description` text DEFAULT NULL,
  `proficiency_level` varchar(255) NOT NULL DEFAULT 'beginner',
  `obtained_at` date DEFAULT NULL,
  `assessment_name` varchar(255) DEFAULT NULL,
  `issuing_organization` varchar(255) DEFAULT NULL,
  `evidence_path` varchar(255) DEFAULT NULL,
  `evidence_name` varchar(255) DEFAULT NULL,
  `verification_status` varchar(255) NOT NULL DEFAULT 'evidence_submitted',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_competencies`
--

INSERT INTO `student_competencies` (`id`, `student_id`, `competency_id`, `name`, `category`, `description`, `proficiency_level`, `obtained_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'PHP Development', 'technical', 'Built web applications using PHP.', 'advanced', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(2, 1, NULL, 'Laravel Framework', 'technical', 'Developed MVC applications with Laravel.', 'intermediate', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(3, 1, NULL, 'Communication', 'soft', NULL, 'advanced', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(4, 1, NULL, 'JavaScript', 'technical', 'Frontend development with vanilla JavaScript and frameworks.', 'intermediate', '2025-12-30', '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(5, 1, NULL, 'MySQL Database Design', 'technical', 'Database design and SQL optimization.', 'advanced', '2025-10-30', '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(6, 1, NULL, 'AWS Cloud Services', 'certification', 'AWS Certified Cloud Practitioner (Passed)', 'intermediate', '2026-03-30', '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(7, 1, NULL, 'Team Leadership', 'soft', 'Led a team of 5 developers on capstone project.', 'advanced', '2026-03-02', '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(8, 1, NULL, 'Project Management', 'soft', 'Managed multiple projects using Agile methodologies.', 'intermediate', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(9, 1, NULL, 'Git Version Control', 'technical', 'Git and GitHub for collaborative development.', 'advanced', NULL, '2026-06-30 04:23:26', '2026-06-30 04:23:26'),
(10, 1, NULL, 'REST API Development', 'technical', 'Building and consuming REST APIs.', 'advanced', '2026-01-30', '2026-06-30 04:23:26', '2026-06-30 04:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `ip_address`, `user_agent`, `details`, `created_at`, `updated_at`) VALUES
(1, 1, 'login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '[]', '2026-06-30 04:46:20', '2026-06-30 04:46:20'),
(2, 1, 'admin_update_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '{\"user_id\":3}', '2026-06-30 05:13:20', '2026-06-30 05:13:20'),
(3, 1, 'admin_update_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '{\"user_id\":3}', '2026-06-30 05:13:26', '2026-06-30 05:13:26'),
(4, 1, 'admin_update_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '{\"user_id\":2}', '2026-06-30 05:13:34', '2026-06-30 05:13:34'),
(5, 1, 'admin_update_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '{\"user_id\":2}', '2026-06-30 05:13:48', '2026-06-30 05:13:48'),
(6, 1, 'admin_update_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '{\"user_id\":4}', '2026-06-30 05:13:58', '2026-06-30 05:13:58'),
(7, 1, 'admin_update_user', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '{\"user_id\":1}', '2026-06-30 05:14:08', '2026-06-30 05:14:08'),
(8, 1, 'logout', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '[]', '2026-06-30 05:15:43', '2026-06-30 05:15:43'),
(9, 2, 'login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '[]', '2026-06-30 05:15:57', '2026-06-30 05:15:57'),
(10, 2, 'logout', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '[]', '2026-06-30 05:16:09', '2026-06-30 05:16:09'),
(11, NULL, 'login_failed', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '{\"email\":\"employer@skillbridge.test\"}', '2026-06-30 05:16:23', '2026-06-30 05:16:23'),
(12, 3, 'login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '[]', '2026-06-30 05:16:51', '2026-06-30 05:16:51'),
(13, 3, 'logout', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '[]', '2026-06-30 05:17:10', '2026-06-30 05:17:10'),
(14, 4, 'login', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '[]', '2026-06-30 05:17:23', '2026-06-30 05:17:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'student',
  `phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `role`, `phone`, `avatar`, `is_active`, `email_verified_at`, `last_login_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@skillbridge.test', 'admin', '+63 900 000 0001', NULL, 1, NULL, '2026-06-30 04:46:20', '$2y$10$z9dVIRMg5DqXTtAsjj/bceyoQD9rgxC0Wh5jMaODMGVmVW6Z78Vjy', NULL, '2026-06-30 04:23:26', '2026-06-30 05:14:08'),
(2, 'Maria Santos', 'coordinator@skillbridge.test', 'coordinator', '+63 900 000 0002', NULL, 1, NULL, '2026-06-30 05:15:57', '$2y$10$z9dVIRMg5DqXTtAsjj/bceyoQD9rgxC0Wh5jMaODMGVmVW6Z78Vjy', NULL, '2026-06-30 04:23:26', '2026-06-30 05:15:57'),
(3, 'John Reyes', 'employer@skillbridge.test', 'employer', '+63 900 000 0003', NULL, 1, NULL, '2026-06-30 05:16:51', '$2y$10$z9dVIRMg5DqXTtAsjj/bceyoQD9rgxC0Wh5jMaODMGVmVW6Z78Vjy', NULL, '2026-06-30 04:23:26', '2026-06-30 05:16:51'),
(4, 'Anna Dela Cruz', 'student@skillbridge.test', 'student', '+63 900 000 0004', NULL, 1, NULL, '2026-06-30 05:17:23', '$2y$10$z9dVIRMg5DqXTtAsjj/bceyoQD9rgxC0Wh5jMaODMGVmVW6Z78Vjy', NULL, '2026-06-30 05:17:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `announcements_user_id_foreign` (`user_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `certificates_student_id_foreign` (`student_id`);

--
-- Indexes for table `competencies`
--
ALTER TABLE `competencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coordinators`
--
ALTER TABLE `coordinators`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coordinators_user_id_foreign` (`user_id`),
  ADD KEY `coordinators_institution_id_foreign` (`institution_id`);

--
-- Indexes for table `employers`
--
ALTER TABLE `employers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employers_user_id_foreign` (`user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `institutions`
--
ALTER TABLE `institutions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `internships`
--
ALTER TABLE `internships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `internships_employer_id_foreign` (`employer_id`);

--
-- Indexes for table `internship_applications`
--
ALTER TABLE `internship_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `internship_applications_internship_id_student_id_unique` (`internship_id`,`student_id`),
  ADD KEY `internship_applications_student_id_foreign` (`student_id`);

--
-- Indexes for table `internship_requirements`
--
ALTER TABLE `internship_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `internship_requirements_internship_id_foreign` (`internship_id`),
  ADD KEY `internship_requirements_competency_id_foreign` (`competency_id`),
  ADD KEY `internship_requirements_skill_id_foreign` (`skill_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `messages_sender_id_foreign` (`sender_id`),
  ADD KEY `messages_receiver_id_foreign` (`receiver_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_foreign` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `portfolios`
--
ALTER TABLE `portfolios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `portfolios_student_id_foreign` (`student_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reports_generated_by_foreign` (`generated_by`);

--
-- Indexes for table `resumes`
--
ALTER TABLE `resumes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resumes_student_id_foreign` (`student_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `skills_name_unique` (`name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `students_user_id_foreign` (`user_id`),
  ADD KEY `students_institution_id_foreign` (`institution_id`);

--
-- Indexes for table `student_competencies`
--
ALTER TABLE `student_competencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_competencies_student_id_foreign` (`student_id`),
  ADD KEY `student_competencies_competency_id_foreign` (`competency_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `system_logs_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `competencies`
--
ALTER TABLE `competencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `coordinators`
--
ALTER TABLE `coordinators`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employers`
--
ALTER TABLE `employers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `institutions`
--
ALTER TABLE `institutions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `internships`
--
ALTER TABLE `internships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `internship_applications`
--
ALTER TABLE `internship_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `internship_requirements`
--
ALTER TABLE `internship_requirements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portfolios`
--
ALTER TABLE `portfolios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resumes`
--
ALTER TABLE `resumes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_competencies`
--
ALTER TABLE `student_competencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coordinators`
--
ALTER TABLE `coordinators`
  ADD CONSTRAINT `coordinators_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `coordinators_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employers`
--
ALTER TABLE `employers`
  ADD CONSTRAINT `employers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internships`
--
ALTER TABLE `internships`
  ADD CONSTRAINT `internships_employer_id_foreign` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internship_applications`
--
ALTER TABLE `internship_applications`
  ADD CONSTRAINT `internship_applications_internship_id_foreign` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internship_applications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `internship_requirements`
--
ALTER TABLE `internship_requirements`
  ADD CONSTRAINT `internship_requirements_competency_id_foreign` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `internship_requirements_internship_id_foreign` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `internship_requirements_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `portfolios`
--
ALTER TABLE `portfolios`
  ADD CONSTRAINT `portfolios_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `resumes`
--
ALTER TABLE `resumes`
  ADD CONSTRAINT `resumes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_competencies`
--
ALTER TABLE `student_competencies`
  ADD CONSTRAINT `student_competencies_competency_id_foreign` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_competencies_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
