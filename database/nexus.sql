-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: nexus
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `nexus`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `nexus` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `nexus`;

--
-- Table structure for table `academic_settings`
--

DROP TABLE IF EXISTS `academic_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_settings` (
  `id` tinyint(3) unsigned NOT NULL,
  `student_id_pattern` varchar(255) NOT NULL DEFAULT '^d{4}-d{5}$',
  `academic_year_start_month` tinyint(3) unsigned NOT NULL DEFAULT 8,
  `summer_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `current_term_override` varchar(10) DEFAULT NULL,
  `first_semester_start_month` tinyint(3) unsigned NOT NULL DEFAULT 8,
  `first_semester_start_day` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `first_semester_end_month` tinyint(3) unsigned NOT NULL DEFAULT 12,
  `first_semester_end_day` tinyint(3) unsigned NOT NULL DEFAULT 31,
  `second_semester_start_month` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `second_semester_start_day` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `second_semester_end_month` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `second_semester_end_day` tinyint(3) unsigned NOT NULL DEFAULT 31,
  `summer_start_month` tinyint(3) unsigned NOT NULL DEFAULT 6,
  `summer_start_day` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `summer_end_month` tinyint(3) unsigned NOT NULL DEFAULT 7,
  `summer_end_day` tinyint(3) unsigned NOT NULL DEFAULT 31,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `require_fourth_year_standing` tinyint(1) NOT NULL DEFAULT 1,
  `require_major_subjects_passed` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_settings`
--

LOCK TABLES `academic_settings` WRITE;
/*!40000 ALTER TABLE `academic_settings` DISABLE KEYS */;
INSERT INTO `academic_settings` VALUES (1,'^SCC-\\d{2}-\\d{8}$',8,1,NULL,8,1,12,31,1,1,5,31,6,1,7,31,'2026-10-11 11:19:45','2026-10-11 11:19:45',1,1);
/*!40000 ALTER TABLE `academic_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_role` varchar(255) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_user_id_foreign` (`user_id`),
  CONSTRAINT `announcements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,1,'birthday sex ni marlo','mag hermits cove ngita chix',NULL,'2026-10-12 12:03:08','2026-10-12 12:03:08','2026-10-12 12:03:08');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_status_histories`
--

DROP TABLE IF EXISTS `application_status_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `application_status_histories` (
  `id` bigint(20) unsigned NOT NULL,
  `application_id` bigint(20) unsigned NOT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `interview_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_status_histories_application_id_foreign` (`application_id`),
  KEY `application_status_histories_actor_id_foreign` (`actor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_status_histories`
--

LOCK TABLES `application_status_histories` WRITE;
/*!40000 ALTER TABLE `application_status_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `application_status_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `issuer` varchar(255) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'certificate',
  `uploaded_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `verification_status` varchar(255) NOT NULL DEFAULT 'evidence_submitted',
  `review_notes` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certificates_student_id_foreign` (`student_id`),
  KEY `certificates_reviewed_by_foreign` (`reviewed_by`),
  KEY `certificates_uploaded_by_user_id_foreign` (`uploaded_by_user_id`),
  KEY `certificates_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  CONSTRAINT `certificates_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `coordinators` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificates_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificates`
--

LOCK TABLES `certificates` WRITE;
/*!40000 ALTER TABLE `certificates` DISABLE KEYS */;
INSERT INTO `certificates` VALUES (1,1,'AWS Certified Cloud Practitioner','Amazon Web Services','2026-03-30','certificates/1/1_cert_1791741806_EViFdKwE.jpg','certificate',4,'2029-03-30','verified',NULL,'2026-10-12 05:30:54',NULL,2,'2026-06-30 04:23:26','2026-10-12 05:30:54');
/*!40000 ALTER TABLE `certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competencies`
--

DROP TABLE IF EXISTS `competencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'skill',
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competencies`
--

LOCK TABLES `competencies` WRITE;
/*!40000 ALTER TABLE `competencies` DISABLE KEYS */;
INSERT INTO `competencies` VALUES (1,'PHP Development','technical',NULL,1,'2026-06-30 04:23:26','2026-06-30 04:23:26'),(2,'Laravel Framework','technical',NULL,1,'2026-06-30 04:23:26','2026-06-30 04:23:26'),(3,'Database Design','technical',NULL,1,'2026-06-30 04:23:26','2026-06-30 04:23:26'),(4,'Problem Solving','soft',NULL,1,'2026-06-30 04:23:26','2026-06-30 04:23:26');
/*!40000 ALTER TABLE `competencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coordinators`
--

DROP TABLE IF EXISTS `coordinators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coordinators` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `institution_id` bigint(20) unsigned DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `office_address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `coordinators_user_id_foreign` (`user_id`),
  KEY `coordinators_institution_id_foreign` (`institution_id`),
  CONSTRAINT `coordinators_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `coordinators_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coordinators`
--

LOCK TABLES `coordinators` WRITE;
/*!40000 ALTER TABLE `coordinators` DISABLE KEYS */;
INSERT INTO `coordinators` VALUES (1,2,1,'Internship Office',NULL,NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26'),(3,8,NULL,NULL,NULL,NULL,'2026-10-12 05:13:44','2026-10-12 05:13:44');
/*!40000 ALTER TABLE `coordinators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deployment_requirements`
--

DROP TABLE IF EXISTS `deployment_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deployment_requirements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(80) NOT NULL,
  `name` varchar(255) NOT NULL,
  `upload_source` varchar(16) NOT NULL DEFAULT 'student',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deployment_requirements_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deployment_requirements`
--

LOCK TABLES `deployment_requirements` WRITE;
/*!40000 ALTER TABLE `deployment_requirements` DISABLE KEYS */;
INSERT INTO `deployment_requirements` VALUES (1,'resume','Resume','system',1,1,'2026-10-12 02:33:57','2026-10-12 02:33:57'),(2,'certificate-of-registration','Certificate of registration (COR)','student',2,1,'2026-10-12 02:33:57','2026-10-12 02:33:57'),(3,'parent-guardian-consent','Parent or guardian consent / waiver','student',3,1,'2026-10-12 02:33:57','2026-10-12 02:33:57'),(4,'medical-certificate','Medical certificate','student',4,1,'2026-10-12 02:33:57','2026-10-12 02:33:57'),(5,'endorsement-letter','Endorsement letter','coordinator',5,1,'2026-10-12 02:33:57','2026-10-12 02:33:57'),(6,'signed-moa','Signed MOA / training agreement','coordinator',6,1,'2026-10-12 02:33:57','2026-10-12 02:33:57');
/*!40000 ALTER TABLE `deployment_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employers`
--

DROP TABLE IF EXISTS `employers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `industry` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employers_user_id_foreign` (`user_id`),
  CONSTRAINT `employers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employers`
--

LOCK TABLES `employers` WRITE;
/*!40000 ALTER TABLE `employers` DISABLE KEYS */;
INSERT INTO `employers` VALUES (1,3,'TechNova Solutions','Information Technology','Software development and digital transformation company.','https://technova.example.com',NULL,'456 Business Park, Makati','John Reyes','2026-06-30 04:23:26','2026-06-30 04:23:26',NULL),(2,6,'Lula Batz DDS',NULL,NULL,NULL,NULL,NULL,'Lula Batz DDS','2026-10-12 05:14:15','2026-10-12 05:14:15',NULL);
/*!40000 ALTER TABLE `employers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutions`
--

DROP TABLE IF EXISTS `institutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `institutions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institutions`
--

LOCK TABLES `institutions` WRITE;
/*!40000 ALTER TABLE `institutions` DISABLE KEYS */;
INSERT INTO `institutions` VALUES (1,'St. Cecilia\'s College-Cebu, Inc.',NULL,NULL,NULL,NULL,'2026-06-30 04:23:25','2026-06-30 04:23:25');
/*!40000 ALTER TABLE `institutions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internship_applications`
--

DROP TABLE IF EXISTS `internship_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internship_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `internship_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `resume_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `cover_letter` text DEFAULT NULL,
  `match_percentage` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `interview_at` timestamp NULL DEFAULT NULL,
  `employer_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `internship_applications_internship_id_student_id_unique` (`internship_id`,`student_id`),
  KEY `internship_applications_student_id_foreign` (`student_id`),
  KEY `internship_applications_resume_id_foreign` (`resume_id`),
  CONSTRAINT `internship_applications_internship_id_foreign` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `internship_applications_resume_id_foreign` FOREIGN KEY (`resume_id`) REFERENCES `resumes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internship_applications_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internship_applications`
--

LOCK TABLES `internship_applications` WRITE;
/*!40000 ALTER TABLE `internship_applications` DISABLE KEYS */;
INSERT INTO `internship_applications` VALUES (1,1,1,NULL,'reviewed','I am excited to apply for this internship opportunity.',85,'2026-06-27 04:23:26',NULL,NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26',NULL);
/*!40000 ALTER TABLE `internship_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internship_requirements`
--

DROP TABLE IF EXISTS `internship_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internship_requirements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `internship_id` bigint(20) unsigned NOT NULL,
  `competency_id` bigint(20) unsigned DEFAULT NULL,
  `skill_id` bigint(20) unsigned DEFAULT NULL,
  `requirement_name` varchar(255) NOT NULL,
  `required_level` varchar(255) NOT NULL DEFAULT 'intermediate',
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `internship_requirements_internship_id_foreign` (`internship_id`),
  KEY `internship_requirements_competency_id_foreign` (`competency_id`),
  KEY `internship_requirements_skill_id_foreign` (`skill_id`),
  CONSTRAINT `internship_requirements_competency_id_foreign` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `internship_requirements_internship_id_foreign` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `internship_requirements_skill_id_foreign` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internship_requirements`
--

LOCK TABLES `internship_requirements` WRITE;
/*!40000 ALTER TABLE `internship_requirements` DISABLE KEYS */;
INSERT INTO `internship_requirements` VALUES (1,1,NULL,NULL,'PHP Development','intermediate',1,'2026-06-30 04:23:26','2026-06-30 04:23:26'),(2,1,NULL,NULL,'Laravel Framework','beginner',1,'2026-06-30 04:23:26','2026-06-30 04:23:26');
/*!40000 ALTER TABLE `internship_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `internships`
--

DROP TABLE IF EXISTS `internships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `internships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `employer_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `responsibilities` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `duration` varchar(255) DEFAULT NULL,
  `allowance` decimal(10,2) DEFAULT NULL,
  `work_setup` varchar(255) NOT NULL DEFAULT 'onsite',
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'open',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `internships_employer_id_foreign` (`employer_id`),
  CONSTRAINT `internships_employer_id_foreign` FOREIGN KEY (`employer_id`) REFERENCES `employers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `internships`
--

LOCK TABLES `internships` WRITE;
/*!40000 ALTER TABLE `internships` DISABLE KEYS */;
INSERT INTO `internships` VALUES (1,1,'Web Developer Intern','Join our development team to build modern web applications.','Develop features using Laravel\nCollaborate with senior developers\nWrite clean, tested code','BS IT or related field, knowledge of PHP and Laravel.','3 months',8000.00,'hybrid','Makati City','open',NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26',NULL),(2,1,'UI/UX Design Intern','Support our design team in creating user-friendly interfaces.',NULL,NULL,'2 months',6000.00,'remote','Remote','open',NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26',NULL);
/*!40000 ALTER TABLE `internships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,'default','{\"uuid\":\"120dd673-0d84-46b4-b55e-6d936999a965\",\"displayName\":\"App\\\\Jobs\\\\BuildResume\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":120,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\BuildResume\",\"command\":\"O:20:\\\"App\\\\Jobs\\\\BuildResume\\\":1:{s:9:\\\"studentId\\\";i:2;}\",\"batchId\":null},\"createdAt\":1790535511,\"delay\":null}',0,NULL,1790535511,1790535511),(2,'default','{\"uuid\":\"a35c3081-747f-4c12-b837-425662d86060\",\"displayName\":\"App\\\\Jobs\\\\BuildResume\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":3,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":120,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\BuildResume\",\"command\":\"O:20:\\\"App\\\\Jobs\\\\BuildResume\\\":1:{s:9:\\\"studentId\\\";i:2;}\",\"batchId\":null},\"createdAt\":1790535794,\"delay\":null}',0,NULL,1790535794,1790535794);
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `majors`
--

DROP TABLE IF EXISTS `majors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `majors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `majors_program_id_name_unique` (`program_id`,`name`),
  CONSTRAINT `majors_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `majors`
--

LOCK TABLES `majors` WRITE;
/*!40000 ALTER TABLE `majors` DISABLE KEYS */;
INSERT INTO `majors` VALUES (1,4,'Marketing Management','2026-10-11 11:19:45','2026-10-11 11:19:45'),(2,4,'Human Resource Management','2026-10-11 11:19:45','2026-10-11 11:19:45'),(3,4,'Financial Management','2026-10-11 11:19:45','2026-10-11 11:19:45'),(4,4,'Operations Management','2026-10-11 11:19:45','2026-10-11 11:19:45');
/*!40000 ALTER TABLE `majors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` bigint(20) unsigned NOT NULL,
  `receiver_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_sender_id_foreign` (`sender_id`),
  KEY `messages_receiver_id_foreign` (`receiver_id`),
  CONSTRAINT `messages_receiver_id_foreign` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,4,9,'pre',NULL,'2026-10-12 11:28:10','2026-10-12 11:28:10'),(2,4,8,'eut',NULL,'2026-10-12 11:28:28','2026-10-12 11:28:28'),(3,2,4,'undang na skoyla','2026-10-12 11:46:27','2026-10-12 11:40:04','2026-10-12 11:46:27'),(4,3,4,'bogo',NULL,'2026-10-12 11:49:20','2026-10-12 11:49:20'),(5,1,10,'kick out ka pre',NULL,'2026-10-12 12:11:09','2026-10-12 12:11:09');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (11,'0001_01_01_000000_create_users_table',1),(12,'0001_01_01_000001_create_cache_table',1),(13,'0001_01_01_000002_create_jobs_table',1),(14,'2026_06_30_000001_create_institutions_table',1),(15,'2026_06_30_000002_create_role_profiles_table',1),(16,'2026_06_30_000003_create_competencies_tables',1),(17,'2026_06_30_000004_create_portfolio_tables',1),(18,'2026_06_30_000005_create_internship_tables',1),(19,'2026_06_30_000006_create_communication_tables',1),(20,'2026_06_30_000007_add_profile_to_coordinators',1),(21,'2026_06_30_124845_normalize_admin_user_role',2),(22,'2026_08_22_000001_add_evidence_and_persistent_profile_fields',3),(23,'2026_09_10_000001_create_application_status_histories_table',4),(24,'2026_09_10_000002_add_review_metadata_to_evidence_tables',4),(25,'2026_09_11_000001_create_recommendation_feedback_table',4),(26,'2026_09_11_000002_add_soft_deletes_to_critical_tables',5),(27,'2026_09_11_000003_add_is_required_to_internship_requirements',5),(28,'2026_09_27_182116_add_email_notifications_to_users_table',6),(29,'2026_10_09_000001_add_email_verification_codes_to_users_table',6),(30,'2026_10_09_000002_create_skill_categories_and_normalized_names',7),(31,'2026_10_11_000001_add_coordinator_verification_audit',8),(32,'2026_10_12_000001_add_categories_and_access_metadata_to_portfolio_files',8),(33,'2026_10_12_000002_create_academic_catalogs_and_student_academic_fields',9),(34,'2026_10_12_000003_update_default_student_id_pattern',10),(35,'2026_10_12_000004_change_student_gwa_precision',11),(36,'2026_10_12_000005_clear_blank_gwa_for_legacy_student',12),(37,'2026_10_12_000006_add_profile_verification_and_eligibility',13),(38,'2026_10_12_000007_create_deployment_requirements',14),(39,'2026_10_12_211118_add_archived_at_to_notifications_table',15),(40,'2026_10_13_000001_add_archived_at_to_resumes_table',15),(41,'2026_10_13_000002_add_resume_and_expiry_to_internship_applications',15);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_id_foreign` (`user_id`),
  CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,6,'application_update','Application updated','Message','{\"application_id\":42}',NULL,NULL,'2026-09-27 10:12:25','2026-09-27 10:12:25'),(2,4,'verification','Competency Evidence Verified','Your competency evidence \"Communication\" has been verified by the coordinator.','{\"verification_type\":\"competency\",\"item_name\":\"Communication\",\"status\":\"verified\",\"review_notes\":null}','2026-10-12 04:55:07',NULL,'2026-10-10 11:12:48','2026-10-12 04:55:07'),(3,4,'verification','Competency Evidence Rejected','Your competency evidence \"JavaScript\" was rejected by the coordinator. ','{\"verification_type\":\"competency\",\"item_name\":\"JavaScript\",\"status\":\"rejected\",\"review_notes\":null}','2026-10-12 04:55:07',NULL,'2026-10-10 11:13:00','2026-10-12 04:55:07'),(4,4,'verification','Portfolio Evidence Rejected','Your submission \"Certificate of registration (COR)\" was rejected by the coordinator. Note: Evidence unclear','{\"verification_type\":\"portfolio\",\"item_name\":\"Certificate of registration (COR)\",\"status\":\"rejected\",\"review_notes\":\"Evidence unclear\"}','2026-10-12 04:55:07',NULL,'2026-10-12 04:54:21','2026-10-12 04:55:07'),(5,4,'verification','Portfolio Document Approved','Your submission \"Certificate of registration (COR)\" has been reviewed by the coordinator.','{\"verification_type\":\"portfolio\",\"item_name\":\"Certificate of registration (COR)\",\"status\":\"approved\",\"review_notes\":null}','2026-10-12 09:30:51',NULL,'2026-10-12 04:56:44','2026-10-12 09:30:51'),(6,2,'profile_change_request','Student profile change request','Gleci S Carmen requested a change to Year level.','{\"student_id\":1,\"change_request_id\":1}','2026-10-12 11:41:21',NULL,'2026-10-12 05:17:00','2026-10-12 11:41:21'),(7,4,'profile_change_request','Profile change request approved','Your requested profile change has been approved.','{\"student_id\":1,\"change_request_id\":1,\"status\":\"approved\"}','2026-10-12 09:30:51',NULL,'2026-10-12 05:19:01','2026-10-12 09:30:51'),(8,4,'verification','Certificate Evidence Verified','Your submission \"AWS Certified Cloud Practitioner\" has been reviewed by the coordinator.','{\"verification_type\":\"certificates\",\"item_name\":\"AWS Certified Cloud Practitioner\",\"status\":\"verified\",\"review_notes\":null}','2026-10-12 09:30:51',NULL,'2026-10-12 05:30:54','2026-10-12 09:30:51'),(9,4,'verification','Competency Evidence Verified','Your submission \"Team Leadership\" has been reviewed by the coordinator.','{\"verification_type\":\"competencies\",\"item_name\":\"Team Leadership\",\"status\":\"verified\",\"review_notes\":null}','2026-10-12 12:09:35',NULL,'2026-10-12 11:31:24','2026-10-12 12:09:35'),(10,4,'verification','Competency Evidence Verified','Your submission \"Git Version Control\" has been reviewed by the coordinator.','{\"verification_type\":\"competencies\",\"item_name\":\"Git Version Control\",\"status\":\"verified\",\"review_notes\":null}','2026-10-12 12:09:35',NULL,'2026-10-12 11:31:39','2026-10-12 12:09:35'),(11,4,'verification','Portfolio Document Approved','Your submission \"Medical certificate\" has been reviewed by the coordinator.','{\"verification_type\":\"portfolio\",\"item_name\":\"Medical certificate\",\"status\":\"approved\",\"review_notes\":null}','2026-10-12 12:09:35',NULL,'2026-10-12 11:31:46','2026-10-12 12:09:35'),(12,4,'verification','Portfolio Document Approved','Your submission \"Parent or guardian consent / waiver\" has been reviewed by the coordinator.','{\"verification_type\":\"portfolio\",\"item_name\":\"Parent or guardian consent \\/ waiver\",\"status\":\"approved\",\"review_notes\":null}','2026-10-12 12:09:35',NULL,'2026-10-12 11:39:24','2026-10-12 12:09:35'),(13,1,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:08','2026-10-12 12:03:08'),(14,2,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}','2026-10-12 12:11:33',NULL,'2026-10-12 12:03:15','2026-10-12 12:11:33'),(15,3,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:18','2026-10-12 12:03:18'),(16,4,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}','2026-10-12 12:09:35',NULL,'2026-10-12 12:03:19','2026-10-12 12:09:35'),(17,6,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:21','2026-10-12 12:03:21'),(18,8,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:22','2026-10-12 12:03:22'),(19,9,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:23','2026-10-12 12:03:23'),(20,10,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:25','2026-10-12 12:03:25'),(21,11,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:26','2026-10-12 12:03:26'),(22,12,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:28','2026-10-12 12:03:28'),(23,13,'announcement','birthday sex ni marlo','mag hermits cove ngita chix','{\"announcement_id\":1}',NULL,NULL,'2026-10-12 12:03:29','2026-10-12 12:03:29');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portfolios`
--

DROP TABLE IF EXISTS `portfolios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'project',
  `category` varchar(255) NOT NULL DEFAULT 'project',
  `deployment_requirement_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `verification_status` varchar(255) NOT NULL DEFAULT 'evidence_submitted',
  `review_notes` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portfolios_student_id_foreign` (`student_id`),
  KEY `portfolios_reviewed_by_foreign` (`reviewed_by`),
  KEY `portfolios_uploaded_by_user_id_foreign` (`uploaded_by_user_id`),
  KEY `portfolios_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `portfolios_deployment_requirement_id_foreign` (`deployment_requirement_id`),
  CONSTRAINT `portfolios_deployment_requirement_id_foreign` FOREIGN KEY (`deployment_requirement_id`) REFERENCES `deployment_requirements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `portfolios_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `coordinators` (`id`) ON DELETE SET NULL,
  CONSTRAINT `portfolios_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `portfolios_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `portfolios_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portfolios`
--

LOCK TABLES `portfolios` WRITE;
/*!40000 ALTER TABLE `portfolios` DISABLE KEYS */;
INSERT INTO `portfolios` VALUES (7,1,'Certificate of registration (COR)','transcript','deployment_document',2,'Deployment requirement document.','portfolios/1/deployment/student_1_1791809744_ZBiJGsTi.jpg',4,'approved',NULL,'2026-10-12 04:56:44',2,NULL,'2026-10-12 04:51:56','2026-10-12 04:56:44'),(8,1,'Parent or guardian consent / waiver','transcript','deployment_document',3,'Deployment requirement document.','portfolios/1/deployment/student_1_1791832136_7uR5oqCv.jpg',4,'approved',NULL,'2026-10-12 11:39:24',2,NULL,'2026-10-12 11:08:56','2026-10-12 11:39:24'),(9,1,'Medical certificate','transcript','deployment_document',4,'Deployment requirement document.','portfolios/1/deployment/student_1_1791832169_Tgepkcw6.jpg',4,'approved',NULL,'2026-10-12 11:31:46',2,NULL,'2026-10-12 11:09:13','2026-10-12 11:31:46');
/*!40000 ALTER TABLE `portfolios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  `required_internship_hours` smallint(5) unsigned NOT NULL,
  `required_hours_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `coordinator_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programs_name_unique` (`name`),
  UNIQUE KEY `programs_code_unique` (`code`),
  KEY `programs_coordinator_id_foreign` (`coordinator_id`),
  CONSTRAINT `programs_coordinator_id_foreign` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinators` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'BS Information Technology','BSIT',486,1,1,'2026-10-11 11:19:45','2026-10-12 05:03:42'),(2,'BS Hospitality Management','BSHM',600,1,1,'2026-10-11 11:19:45','2026-10-12 05:05:47'),(3,'BS Tourism Management','BSTM',600,1,1,'2026-10-11 11:19:45','2026-10-12 05:06:03'),(4,'BS Business Administration','BSBA',800,1,1,'2026-10-11 11:19:45','2026-10-12 05:05:39'),(5,'BS Management Accounting','BSMA',486,1,1,'2026-10-11 11:19:45','2026-10-12 05:05:57');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recommendation_feedback`
--

DROP TABLE IF EXISTS `recommendation_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recommendation_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `internship_id` bigint(20) unsigned NOT NULL,
  `feedback` varchar(255) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recommendation_feedback_student_id_internship_id_unique` (`student_id`,`internship_id`),
  KEY `recommendation_feedback_student_id_foreign` (`student_id`),
  KEY `recommendation_feedback_internship_id_foreign` (`internship_id`),
  CONSTRAINT `recommendation_feedback_internship_id_foreign` FOREIGN KEY (`internship_id`) REFERENCES `internships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recommendation_feedback_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recommendation_feedback`
--

LOCK TABLES `recommendation_feedback` WRITE;
/*!40000 ALTER TABLE `recommendation_feedback` DISABLE KEYS */;
INSERT INTO `recommendation_feedback` VALUES (1,1,1,'helpful','This recommendation aligned with my PHP development experience and internship goals.','2026-06-30 05:20:00','2026-06-30 05:20:00'),(2,1,2,'helpful','happy birthday marlo','2026-10-12 11:23:50','2026-10-12 11:23:50');
/*!40000 ALTER TABLE `recommendation_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `generated_by` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `file_path` varchar(255) DEFAULT NULL,
  `format` varchar(255) NOT NULL DEFAULT 'pdf',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reports_generated_by_foreign` (`generated_by`),
  CONSTRAINT `reports_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resumes`
--

DROP TABLE IF EXISTS `resumes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resumes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`content`)),
  `file_path` varchar(255) DEFAULT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'resume',
  `uploaded_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `verification_status` varchar(255) NOT NULL DEFAULT 'available',
  `review_notes` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resumes_student_id_foreign` (`student_id`),
  KEY `resumes_uploaded_by_user_id_foreign` (`uploaded_by_user_id`),
  KEY `resumes_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  CONSTRAINT `resumes_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `resumes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resumes_uploaded_by_user_id_foreign` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resumes`
--

LOCK TABLES `resumes` WRITE;
/*!40000 ALTER TABLE `resumes` DISABLE KEYS */;
INSERT INTO `resumes` VALUES (2,1,'{\"personal\":{\"name\":\"Anna Dela Cruz\",\"email\":\"student@skillbridge.test\",\"phone\":\"+63 900 000 0004\",\"address\":\"789 Student St, Quezon City\",\"program\":\"BS Information Technology\",\"year_level\":\"4th Year\",\"institution\":\"St. Cecilia\'s College-Cebu, Inc.\"},\"objectives\":\"Seeking a software development internship to apply full-stack skills.\",\"competencies\":[{\"name\":\"PHP Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Built web applications using PHP.\"},{\"name\":\"Laravel Framework\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Developed MVC applications with Laravel.\"},{\"name\":\"Communication\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":null},{\"name\":\"JavaScript\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Frontend development with vanilla JavaScript and frameworks.\"},{\"name\":\"MySQL Database Design\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Database design and SQL optimization.\"},{\"name\":\"AWS Cloud Services\",\"category\":\"Certification\",\"level\":\"Intermediate\",\"description\":\"AWS Certified Cloud Practitioner (Passed)\"},{\"name\":\"Team Leadership\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":\"Led a team of 5 developers on capstone project.\"},{\"name\":\"Project Management\",\"category\":\"Soft Skill\",\"level\":\"Intermediate\",\"description\":\"Managed multiple projects using Agile methodologies.\"},{\"name\":\"Git Version Control\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Git and GitHub for collaborative development.\"},{\"name\":\"REST API Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Building and consuming REST APIs.\"}],\"certificates\":[{\"title\":\"AWS Certified Cloud Practitioner\",\"issuer\":\"Amazon Web Services\",\"issue_date\":\"Mar 2026\"}],\"portfolios\":[{\"title\":\"E-Commerce Platform Project\",\"type\":\"Project\",\"description\":\"Full-stack e-commerce platform built with Laravel and Vue.js. Features include product catalog, shopping cart, payment integration, and admin dashboard.\"},{\"title\":\"AWS Certified Cloud Practitioner\",\"type\":\"Certificate\",\"description\":\"Official AWS certification demonstrating cloud computing knowledge.\"},{\"title\":\"GitHub Portfolio\",\"type\":\"Project\",\"description\":\"Collection of personal projects showcasing coding skills and best practices.\"},{\"title\":\"Capstone Project Documentation\",\"type\":\"Project\",\"description\":\"Final year capstone project: Skill-Bridge Internship Platform. Complete documentation including architecture, database design, and implementation details.\"},{\"title\":\"Academic Transcript\",\"type\":\"Transcript\",\"description\":\"Official academic transcript from Metro State University.\"},{\"title\":\"Recommendation Letter - Professor John Doe\",\"type\":\"Award\",\"description\":\"Letter of recommendation from Professor John Doe, highlighting technical excellence and leadership.\"}]}','resumes/student_1_1790535850.pdf','resume',NULL,'2026-09-27 11:04:11',NULL,'available',NULL,NULL,NULL,'2026-09-27 11:04:11','2026-09-27 11:04:11'),(3,1,'{\"personal\":{\"name\":\"Anna Dela Cruz\",\"email\":\"student@skillbridge.test\",\"phone\":\"+63 900 000 0004\",\"address\":\"789 Student St, Quezon City\",\"program\":\"BS Information Technology\",\"year_level\":\"4th Year\",\"institution\":\"St. Cecilia\'s College-Cebu, Inc.\"},\"objectives\":\"Seeking a software development internship to apply full-stack skills.\",\"competencies\":[{\"name\":\"PHP Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Built web applications using PHP.\"},{\"name\":\"Laravel Framework\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Developed MVC applications with Laravel.\"},{\"name\":\"Communication\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":null},{\"name\":\"JavaScript\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Frontend development with vanilla JavaScript and frameworks.\"},{\"name\":\"MySQL Database Design\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Database design and SQL optimization.\"},{\"name\":\"AWS Cloud Services\",\"category\":\"Certification\",\"level\":\"Intermediate\",\"description\":\"AWS Certified Cloud Practitioner (Passed)\"},{\"name\":\"Team Leadership\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":\"Led a team of 5 developers on capstone project.\"},{\"name\":\"Project Management\",\"category\":\"Soft Skill\",\"level\":\"Intermediate\",\"description\":\"Managed multiple projects using Agile methodologies.\"},{\"name\":\"Git Version Control\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Git and GitHub for collaborative development.\"},{\"name\":\"REST API Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Building and consuming REST APIs.\"}],\"certificates\":[{\"title\":\"AWS Certified Cloud Practitioner\",\"issuer\":\"Amazon Web Services\",\"issue_date\":\"Mar 2026\"}],\"portfolios\":[{\"title\":\"E-Commerce Platform Project\",\"type\":\"Project\",\"description\":\"Full-stack e-commerce platform built with Laravel and Vue.js. Features include product catalog, shopping cart, payment integration, and admin dashboard.\"},{\"title\":\"AWS Certified Cloud Practitioner\",\"type\":\"Certificate\",\"description\":\"Official AWS certification demonstrating cloud computing knowledge.\"},{\"title\":\"GitHub Portfolio\",\"type\":\"Project\",\"description\":\"Collection of personal projects showcasing coding skills and best practices.\"},{\"title\":\"Capstone Project Documentation\",\"type\":\"Project\",\"description\":\"Final year capstone project: Skill-Bridge Internship Platform. Complete documentation including architecture, database design, and implementation details.\"},{\"title\":\"Academic Transcript\",\"type\":\"Transcript\",\"description\":\"Official academic transcript from Metro State University.\"},{\"title\":\"Recommendation Letter - Professor John Doe\",\"type\":\"Award\",\"description\":\"Letter of recommendation from Professor John Doe, highlighting technical excellence and leadership.\"}]}','resumes/student_1_1790535865.pdf','resume',NULL,'2026-09-27 11:04:26',NULL,'available',NULL,NULL,NULL,'2026-09-27 11:04:26','2026-09-27 11:04:26'),(4,1,'{\"personal\":{\"name\":\"Gleci S Carmen\",\"email\":\"student@skillbridge.test\",\"phone\":\"+639000000004\",\"address\":\"789 Student St, Quezon City\",\"program\":\"BS Information Technology (BSIT)\",\"year_level\":\"4th Year\",\"institution\":\"St. Cecilia\'s College-Cebu, Inc.\"},\"objectives\":\"Seeking a software development internship to apply full-stack skills.\",\"competencies\":[{\"name\":\"PHP Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Built web applications using PHP.\"},{\"name\":\"Laravel Framework\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Developed MVC applications with Laravel.\"},{\"name\":\"Communication\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":null},{\"name\":\"JavaScript\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Frontend development with vanilla JavaScript and frameworks.\"},{\"name\":\"MySQL Database Design\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Database design and SQL optimization.\"},{\"name\":\"AWS Cloud Services\",\"category\":\"Certification\",\"level\":\"Intermediate\",\"description\":\"AWS Certified Cloud Practitioner (Passed)\"},{\"name\":\"Team Leadership\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":\"Led a team of 5 developers on capstone project.\"},{\"name\":\"Project Management\",\"category\":\"Soft Skill\",\"level\":\"Intermediate\",\"description\":\"Managed multiple projects using Agile methodologies.\"},{\"name\":\"Git Version Control\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Git and GitHub for collaborative development.\"},{\"name\":\"REST API Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Building and consuming REST APIs.\"}],\"certificates\":[{\"title\":\"AWS Certified Cloud Practitioner\",\"issuer\":\"Amazon Web Services\",\"issue_date\":\"Mar 2026\"}],\"portfolios\":[]}','resumes/student_1_1791809182.pdf','resume',4,'2026-10-12 04:46:22',NULL,'available',NULL,NULL,NULL,'2026-10-12 04:46:22','2026-10-12 04:46:22'),(5,1,'{\"personal\":{\"name\":\"Ong Bak Jr.\",\"email\":\"student@skillbridge.test\",\"phone\":\"+639000000004\",\"address\":\"789 Student St, Quezon City\",\"program\":\"BS Information Technology (BSIT)\",\"year_level\":\"3rd Year\",\"institution\":\"St. Cecilia\'s College-Cebu, Inc.\"},\"objectives\":\"Seeking a software development internship to apply full-stack skills.\",\"competencies\":[{\"name\":\"PHP Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Built web applications using PHP.\"},{\"name\":\"Laravel Framework\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Developed MVC applications with Laravel.\"},{\"name\":\"Communication\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":null},{\"name\":\"JavaScript\",\"category\":\"Technical Skill\",\"level\":\"Intermediate\",\"description\":\"Frontend development with vanilla JavaScript and frameworks.\"},{\"name\":\"MySQL Database Design\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Database design and SQL optimization.\"},{\"name\":\"AWS Cloud Services\",\"category\":\"Certification\",\"level\":\"Intermediate\",\"description\":\"AWS Certified Cloud Practitioner (Passed)\"},{\"name\":\"Team Leadership\",\"category\":\"Soft Skill\",\"level\":\"Advanced\",\"description\":\"Led a team of 5 developers on capstone project.\"},{\"name\":\"Project Management\",\"category\":\"Soft Skill\",\"level\":\"Intermediate\",\"description\":\"Managed multiple projects using Agile methodologies.\"},{\"name\":\"Git Version Control\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Git and GitHub for collaborative development.\"},{\"name\":\"REST API Development\",\"category\":\"Technical Skill\",\"level\":\"Advanced\",\"description\":\"Building and consuming REST APIs.\"}],\"certificates\":[{\"title\":\"AWS Certified Cloud Practitioner\",\"issuer\":\"Amazon Web Services\",\"issue_date\":\"Mar 2026\"}],\"portfolios\":[{\"title\":\"Certificate of registration (COR)\",\"type\":\"Transcript\",\"description\":\"Deployment requirement document.\"},{\"title\":\"Parent or guardian consent \\/ waiver\",\"type\":\"Transcript\",\"description\":\"Deployment requirement document.\"},{\"title\":\"Medical certificate\",\"type\":\"Transcript\",\"description\":\"Deployment requirement document.\"}]}','resumes/student_1_1791834300.pdf','resume',4,'2026-10-12 11:45:01',NULL,'available',NULL,NULL,NULL,'2026-10-12 11:45:01','2026-10-12 11:45:01');
/*!40000 ALTER TABLE `resumes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `skill_categories`
--

DROP TABLE IF EXISTS `skill_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_normalized` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `skill_categories_name_normalized_unique` (`name_normalized`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skill_categories`
--

LOCK TABLES `skill_categories` WRITE;
/*!40000 ALTER TABLE `skill_categories` DISABLE KEYS */;
INSERT INTO `skill_categories` VALUES (1,'technical','technical','2026-10-10 09:42:38','2026-10-10 09:42:38'),(2,'soft','soft','2026-10-10 09:42:38','2026-10-10 09:42:38');
/*!40000 ALTER TABLE `skill_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skills`
--

DROP TABLE IF EXISTS `skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'skill',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name_normalized` varchar(255) NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `skills_name_unique` (`name`),
  UNIQUE KEY `skills_name_normalized_unique` (`name_normalized`),
  KEY `skills_category_id_foreign` (`category_id`),
  CONSTRAINT `skills_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `skill_categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skills`
--

LOCK TABLES `skills` WRITE;
/*!40000 ALTER TABLE `skills` DISABLE KEYS */;
INSERT INTO `skills` VALUES (1,'PHP','technical',NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26','php',1),(2,'Laravel','technical',NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26','laravel',1),(3,'JavaScript','technical',NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26','javascript',1),(4,'MySQL','technical',NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26','mysql',1),(5,'Communication','soft',NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26','communication',2),(6,'Teamwork','soft',NULL,'2026-06-30 04:23:26','2026-06-30 04:23:26','teamwork',2);
/*!40000 ALTER TABLE `skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_change_requests`
--

DROP TABLE IF EXISTS `student_change_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_change_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `field` varchar(32) NOT NULL,
  `current_value` text DEFAULT NULL,
  `requested_value` text NOT NULL,
  `status` varchar(16) NOT NULL DEFAULT 'pending',
  `coordinator_note` text DEFAULT NULL,
  `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_change_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `student_change_requests_student_id_status_index` (`student_id`,`status`),
  CONSTRAINT `student_change_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_change_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_change_requests`
--

LOCK TABLES `student_change_requests` WRITE;
/*!40000 ALTER TABLE `student_change_requests` DISABLE KEYS */;
INSERT INTO `student_change_requests` VALUES (1,1,'year_level','4th Year','3rd Year','approved',NULL,2,'2026-10-12 05:19:01','2026-10-12 05:17:00','2026-10-12 05:19:01');
/*!40000 ALTER TABLE `student_change_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_competencies`
--

DROP TABLE IF EXISTS `student_competencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_competencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `competency_id` bigint(20) unsigned DEFAULT NULL,
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
  `review_notes` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_competencies_student_id_foreign` (`student_id`),
  KEY `student_competencies_competency_id_foreign` (`competency_id`),
  KEY `student_competencies_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `student_competencies_competency_id_foreign` FOREIGN KEY (`competency_id`) REFERENCES `competencies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_competencies_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `coordinators` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_competencies_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_competencies`
--

LOCK TABLES `student_competencies` WRITE;
/*!40000 ALTER TABLE `student_competencies` DISABLE KEYS */;
INSERT INTO `student_competencies` VALUES (1,1,NULL,'PHP Development','technical','Built web applications using PHP.','advanced',NULL,'PHP Skills Assessment','Metro State University',NULL,'php_assessment.pdf','verified','Competency verified after reviewing the submitted assessment record.','2026-06-30 04:55:00',NULL,'2026-06-30 04:23:26','2026-06-30 04:55:00'),(2,1,NULL,'Laravel Framework','technical','Developed MVC applications with Laravel.','intermediate',NULL,'Laravel Workshop Certificate','SkillBridge Academy',NULL,'laravel_workshop.pdf','verified','Workshop attendance and project output were accepted.','2026-06-30 04:56:00',NULL,'2026-06-30 04:23:26','2026-06-30 04:56:00'),(3,1,NULL,'Communication','soft',NULL,'advanced',NULL,'Communication Seminar','Metro State University',NULL,'communication_seminar.pdf','verified',NULL,'2026-10-10 11:12:48',NULL,'2026-06-30 04:23:26','2026-10-10 11:12:48'),(4,1,NULL,'JavaScript','technical','Frontend development with vanilla JavaScript and frameworks.','intermediate','2025-12-30',NULL,NULL,NULL,NULL,'rejected',NULL,'2026-10-10 11:13:00',NULL,'2026-06-30 04:23:26','2026-10-10 11:13:00'),(5,1,NULL,'MySQL Database Design','technical','Database design and SQL optimization.','advanced','2025-10-30','Database Design Assessment','Metro State University',NULL,'mysql_assessment.pdf','verified','Assessment was verified and matched the student profile.','2026-06-30 04:58:00',NULL,'2026-06-30 04:23:26','2026-06-30 04:58:00'),(6,1,NULL,'AWS Cloud Services','certification','AWS Certified Cloud Practitioner (Passed)','intermediate','2026-03-30','AWS Certified Cloud Practitioner','Amazon Web Services',NULL,'aws_certificate.pdf','verified','Credential verified through the official AWS certification record.','2026-06-30 04:59:00',NULL,'2026-06-30 04:23:26','2026-06-30 04:59:00'),(7,1,NULL,'Team Leadership','soft','Led a team of 5 developers on capstone project.','advanced','2026-03-02',NULL,NULL,NULL,NULL,'verified',NULL,'2026-10-12 11:31:24',NULL,'2026-06-30 04:23:26','2026-10-12 11:31:24'),(8,1,NULL,'Project Management','soft','Managed multiple projects using Agile methodologies.','intermediate',NULL,'Agile Project Management Proof','Metro State University',NULL,'agile_project.pdf','rejected','Submission lacked corroborating project documentation and dates.','2026-06-30 05:00:00',NULL,'2026-06-30 04:23:26','2026-06-30 05:00:00'),(9,1,NULL,'Git Version Control','technical','Git and GitHub for collaborative development.','advanced',NULL,NULL,NULL,NULL,NULL,'verified',NULL,'2026-10-12 11:31:24',NULL,'2026-06-30 04:23:26','2026-10-12 11:31:24'),(10,1,NULL,'REST API Development','technical','Building and consuming REST APIs.','advanced','2026-01-30','REST API Portfolio Review','SkillBridge Academy',NULL,'rest_api.pdf','verified','API project artifacts were reviewed and approved.','2026-06-30 05:02:00',NULL,'2026-06-30 04:23:26','2026-06-30 05:02:00');
/*!40000 ALTER TABLE `student_competencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `institution_id` bigint(20) unsigned DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `major_id` bigint(20) unsigned DEFAULT NULL,
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
  `profile_completion` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `school_year` varchar(9) DEFAULT NULL,
  `semester` varchar(10) DEFAULT NULL,
  `enrollment_status` varchar(20) DEFAULT NULL,
  `gwa` decimal(3,2) DEFAULT NULL,
  `profile_verified_at` timestamp NULL DEFAULT NULL,
  `profile_verified_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `rendered_internship_hours` smallint(5) unsigned NOT NULL DEFAULT 0,
  `major_subjects_passed` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_student_id_number_unique` (`student_id_number`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_institution_id_foreign` (`institution_id`),
  KEY `students_program_id_foreign` (`program_id`),
  KEY `students_major_id_foreign` (`major_id`),
  KEY `students_profile_verified_by_user_id_foreign` (`profile_verified_by_user_id`),
  CONSTRAINT `students_institution_id_foreign` FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_major_id_foreign` FOREIGN KEY (`major_id`) REFERENCES `majors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_profile_verified_by_user_id_foreign` FOREIGN KEY (`profile_verified_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,4,1,1,NULL,'SCC-19-00012321','BS Information Technology (BSIT)','3rd Year','Seeking a software development internship to apply full-stack skills.','profiles/students/1_1791832864_jT6FutQl.jpg','789 Student St, Quezon City','2004-12-12','Ong','Bak',NULL,'Jr.','Other','Minglanilla','Cebu','6046','College of Information Technology','2030-05-01','Cloud Computing / IT Infrastructure','Onsite','Cebu City, Cebu',91,'2026-06-30 04:23:26','2026-10-12 11:39:24',NULL,'D','2026-2027','first','Regular',1.90,'2026-10-12 05:07:12',2,450,0),(3,9,1,2,NULL,'SCC-123123123','BS Hospitality Management (BSHM)','1st Year','To gain hands-on experience in hotel and restaurant operations, develop strong customer service and communication skills, and apply what I’ve learned in class to real industry settings. I aim to build a career in hotel management or food and beverage services, starting with an internship that helps me grow professionally and prepares me for leadership roles in the hospitality industry.','profiles/students/3_1791658853_51Wktr1K.png','San Vicente, Tunghaan','2005-05-01','Anne','Sabanico','Z',NULL,'Female','Minglanilla','Cebu','6046','College of Hospitality Management','2030-05-15','Hotel Operations (Front Office / Food and Beverage Service)','Onsite','Cebu City or Mactan (Lapu-Lapu City), Cebu',73,'2026-10-10 10:48:01','2026-10-12 05:05:47',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),(4,10,1,1,NULL,NULL,'BS Information Technology (BSIT)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,17,'2026-10-12 07:58:08','2026-10-12 11:42:42',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-12 11:42:42',2,0,0),(5,11,1,1,NULL,NULL,'BS Information Technology (BSIT)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,17,'2026-10-12 10:03:41','2026-10-12 10:03:41',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),(6,12,1,1,NULL,NULL,'BS Information Technology (BSIT)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,22,'2026-10-12 10:57:27','2026-10-12 10:57:28',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),(7,13,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,17,'2026-10-12 12:01:56','2026-10-12 12:01:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),(8,14,1,4,NULL,NULL,'BS Business Administration (BSBA)',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,22,'2026-10-12 12:21:33','2026-10-12 12:21:33',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `system_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','[]','2026-06-30 04:46:20','2026-06-30 04:46:20'),(2,1,'admin_update_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','{\"user_id\":3}','2026-06-30 05:13:20','2026-06-30 05:13:20'),(3,1,'admin_update_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','{\"user_id\":3}','2026-06-30 05:13:26','2026-06-30 05:13:26'),(4,1,'admin_update_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','{\"user_id\":2}','2026-06-30 05:13:34','2026-06-30 05:13:34'),(5,1,'admin_update_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','{\"user_id\":2}','2026-06-30 05:13:48','2026-06-30 05:13:48'),(6,1,'admin_update_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','{\"user_id\":4}','2026-06-30 05:13:58','2026-06-30 05:13:58'),(7,1,'admin_update_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','{\"user_id\":1}','2026-06-30 05:14:08','2026-06-30 05:14:08'),(8,1,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','[]','2026-06-30 05:15:43','2026-06-30 05:15:43'),(9,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','[]','2026-06-30 05:15:57','2026-06-30 05:15:57'),(10,2,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','[]','2026-06-30 05:16:09','2026-06-30 05:16:09'),(11,NULL,'login_failed','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','{\"email\":\"employer@skillbridge.test\"}','2026-06-30 05:16:23','2026-06-30 05:16:23'),(12,3,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','[]','2026-06-30 05:16:51','2026-06-30 05:16:51'),(13,3,'logout','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','[]','2026-06-30 05:17:10','2026-06-30 05:17:10'),(14,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','[]','2026-06-30 05:17:23','2026-06-30 05:17:23'),(15,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.135.0 Chrome/148.0.7778.280 Electron/42.8.1 Safari/537.36','[]','2026-09-10 11:22:39','2026-09-10 11:22:39'),(16,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','[]','2026-09-10 11:24:38','2026-09-10 11:24:38'),(17,NULL,'register','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.135.0 Chrome/148.0.7778.280 Electron/42.8.1 Safari/537.36','{\"role\":\"student\"}','2026-09-10 11:34:16','2026-09-10 11:34:16'),(18,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','[]','2026-09-10 11:42:55','2026-09-10 11:42:55'),(19,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.135.0 Chrome/148.0.7778.280 Electron/42.8.1 Safari/537.36','[]','2026-09-10 12:01:13','2026-09-10 12:01:13'),(20,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.135.0 Chrome/148.0.7778.280 Electron/42.8.1 Safari/537.36','[]','2026-09-10 12:04:21','2026-09-10 12:04:21'),(21,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','[]','2026-09-10 12:25:27','2026-09-10 12:25:27'),(22,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','[]','2026-09-10 22:52:51','2026-09-10 22:52:51'),(23,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','[]','2026-09-10 23:03:01','2026-09-10 23:03:01'),(24,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','[]','2026-09-10 23:09:48','2026-09-10 23:09:48'),(25,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','[]','2026-09-10 23:10:49','2026-09-10 23:10:49'),(26,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36 Edg/152.0.0.0','[]','2026-09-10 23:11:42','2026-09-10 23:11:42'),(27,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.137.0 Chrome/148.0.7778.280 Electron/42.10.0 Safari/537.36','[]','2026-09-11 00:38:39','2026-09-11 00:38:39'),(28,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','[]','2026-09-27 10:58:16','2026-09-27 10:58:16'),(29,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','[]','2026-09-27 11:03:58','2026-09-27 11:03:58'),(30,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-10 10:38:50','2026-10-10 10:38:50'),(31,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-10 10:39:17','2026-10-10 10:39:17'),(32,9,'register','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-10 10:48:01','2026-10-10 10:48:01'),(33,9,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-10 10:57:59','2026-10-10 10:57:59'),(34,9,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-10 11:00:25','2026-10-10 11:00:25'),(35,9,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-10 11:01:17','2026-10-10 11:01:17'),(36,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-10 11:12:35','2026-10-10 11:12:35'),(37,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 07:19:51','2026-10-11 07:19:51'),(38,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 07:52:47','2026-10-11 07:52:47'),(39,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 07:55:09','2026-10-11 07:55:09'),(40,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 07:56:03','2026-10-11 07:56:03'),(41,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 07:56:21','2026-10-11 07:56:21'),(42,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 08:18:31','2026-10-11 08:18:31'),(43,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 08:18:53','2026-10-11 08:18:53'),(44,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 08:26:02','2026-10-11 08:26:02'),(45,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 08:26:13','2026-10-11 08:26:13'),(46,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 10:02:41','2026-10-11 10:02:41'),(47,4,'certificate_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"certificate_id\":1}','2026-10-11 10:03:26','2026-10-11 10:03:26'),(48,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 10:15:23','2026-10-11 10:15:23'),(49,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-11 10:16:12','2026-10-11 10:16:12'),(50,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','{\"role\":\"student\"}','2026-10-11 10:34:11','2026-10-11 10:34:11'),(51,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','{\"role\":\"student\"}','2026-10-11 10:34:13','2026-10-11 10:34:13'),(52,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-11 10:37:29','2026-10-11 10:37:29'),(53,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 11:23:03','2026-10-11 11:23:03'),(54,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 11:41:19','2026-10-11 11:41:19'),(55,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 12:59:22','2026-10-11 12:59:22'),(56,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 13:15:55','2026-10-11 13:15:55'),(57,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-11 13:22:30','2026-10-11 13:22:30'),(58,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 13:55:05','2026-10-11 13:55:05'),(59,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 13:55:10','2026-10-11 13:55:10'),(60,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 13:55:16','2026-10-11 13:55:16'),(61,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 13:55:18','2026-10-11 13:55:18'),(62,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 13:55:32','2026-10-11 13:55:32'),(63,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 13:56:07','2026-10-11 13:56:07'),(64,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 13:56:10','2026-10-11 13:56:10'),(65,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 13:56:12','2026-10-11 13:56:12'),(66,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 14:09:08','2026-10-11 14:09:08'),(67,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 14:09:18','2026-10-11 14:09:18'),(68,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-11 14:09:30','2026-10-11 14:09:30'),(69,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-11 23:45:47','2026-10-11 23:45:47'),(70,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 04:25:42','2026-10-12 04:25:42'),(71,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 04:44:55','2026-10-12 04:44:55'),(72,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 04:47:13','2026-10-12 04:47:13'),(73,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 04:50:25','2026-10-12 04:50:25'),(74,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 04:52:25','2026-10-12 04:52:25'),(75,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 04:54:55','2026-10-12 04:54:55'),(76,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 04:56:14','2026-10-12 04:56:14'),(77,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 05:02:52','2026-10-12 05:02:52'),(78,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 05:04:09','2026-10-12 05:04:09'),(79,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 05:05:27','2026-10-12 05:05:27'),(80,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 05:06:36','2026-10-12 05:06:36'),(81,2,'student_profile_verified','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','{\"student_id\":1}','2026-10-12 05:07:12','2026-10-12 05:07:12'),(82,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 05:10:26','2026-10-12 05:10:26'),(83,NULL,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 05:11:59','2026-10-12 05:11:59'),(84,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 05:12:50','2026-10-12 05:12:50'),(85,1,'admin_update_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"user_id\":8}','2026-10-12 05:13:44','2026-10-12 05:13:44'),(86,1,'admin_update_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"user_id\":6}','2026-10-12 05:14:15','2026-10-12 05:14:15'),(87,8,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 05:14:47','2026-10-12 05:14:47'),(88,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 05:15:56','2026-10-12 05:15:56'),(89,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 05:17:43','2026-10-12 05:17:43'),(90,2,'student_profile_change_request_approved','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"student_id\":1,\"change_request_id\":1,\"field\":\"year_level\"}','2026-10-12 05:19:01','2026-10-12 05:19:01'),(91,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 05:20:10','2026-10-12 05:20:10'),(92,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 05:30:01','2026-10-12 05:30:01'),(93,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 06:53:39','2026-10-12 06:53:39'),(94,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 07:13:52','2026-10-12 07:13:52'),(95,10,'register','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 07:58:08','2026-10-12 07:58:08'),(96,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 08:18:00','2026-10-12 08:18:00'),(97,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 08:27:09','2026-10-12 08:27:09'),(98,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 08:44:22','2026-10-12 08:44:22'),(99,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 09:23:07','2026-10-12 09:23:07'),(100,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 09:30:40','2026-10-12 09:30:40'),(101,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 09:41:15','2026-10-12 09:41:15'),(102,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','[]','2026-10-12 09:57:28','2026-10-12 09:57:28'),(103,11,'register','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.141.0 Chrome/150.0.7871.250 Electron/43.7.7 Safari/537.36','{\"role\":\"student\"}','2026-10-12 10:03:41','2026-10-12 10:03:41'),(104,12,'register','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 10:57:28','2026-10-12 10:57:28'),(105,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:03:15','2026-10-12 11:03:15'),(106,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 11:05:52','2026-10-12 11:05:52'),(107,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 11:06:01','2026-10-12 11:06:01'),(108,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 11:06:24','2026-10-12 11:06:24'),(109,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 11:06:36','2026-10-12 11:06:36'),(110,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 11:06:39','2026-10-12 11:06:39'),(111,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 11:15:27','2026-10-12 11:15:27'),(112,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 11:21:10','2026-10-12 11:21:10'),(113,4,'profile_update','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 11:21:16','2026-10-12 11:21:16'),(114,4,'message_send','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"receiver_id\":9}','2026-10-12 11:28:10','2026-10-12 11:28:10'),(115,4,'message_send','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"receiver_id\":8}','2026-10-12 11:28:28','2026-10-12 11:28:28'),(116,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:29:50','2026-10-12 11:29:50'),(117,2,'message_send','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"receiver_id\":4}','2026-10-12 11:40:04','2026-10-12 11:40:04'),(118,2,'student_profile_verified','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"student_id\":4}','2026-10-12 11:42:42','2026-10-12 11:42:42'),(119,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:43:14','2026-10-12 11:43:14'),(120,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:44:18','2026-10-12 11:44:18'),(121,3,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:46:57','2026-10-12 11:46:57'),(122,3,'message_send','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"receiver_id\":4}','2026-10-12 11:49:20','2026-10-12 11:49:20'),(123,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:53:22','2026-10-12 11:53:22'),(124,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:56:30','2026-10-12 11:56:30'),(125,3,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:58:10','2026-10-12 11:58:10'),(126,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 11:59:12','2026-10-12 11:59:12'),(127,1,'admin_create_user','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"user_id\":13}','2026-10-12 12:01:56','2026-10-12 12:01:56'),(128,1,'announcement_create','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"announcement_id\":1}','2026-10-12 12:03:08','2026-10-12 12:03:08'),(129,4,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 12:08:51','2026-10-12 12:08:51'),(130,1,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 12:10:43','2026-10-12 12:10:43'),(131,1,'message_send','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"receiver_id\":10}','2026-10-12 12:11:09','2026-10-12 12:11:09'),(132,2,'login','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','[]','2026-10-12 12:11:25','2026-10-12 12:11:25'),(133,14,'register','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','{\"role\":\"student\"}','2026-10-12 12:21:33','2026-10-12 12:21:33');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'student',
  `phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
  `sms_notifications` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `verification_code_expires_at` timestamp NULL DEFAULT NULL,
  `verification_attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Administrator','admin@skillbridge.test','admin','+63 900 000 0001',NULL,1,1,0,'2026-10-10 17:49:56','2026-06-30 04:46:20','$2y$12$dOoBnAw/42s2yTTODhgfLu0FrtJg2gZvk8GT3HexqpSGpqeQVU8J2',NULL,'2026-06-30 04:23:26','2026-09-10 23:03:01',NULL,NULL,NULL,0),(2,'Maria Santos','coordinator@skillbridge.test','coordinator','+63 900 000 0002',NULL,1,1,0,'2026-10-10 17:49:56','2026-06-30 05:15:57','$2y$12$k9YcmJ4BaixovJWJPNDgkObXLkbX9VMkKCs/x3DejfvYfQPcNer.q',NULL,'2026-06-30 04:23:26','2026-10-10 11:12:35',NULL,NULL,NULL,0),(3,'John Reyes','employer@skillbridge.test','employer','+63 900 000 0003',NULL,1,1,0,'2026-10-10 17:49:56','2026-06-30 05:16:51','$2y$12$OsNny8VWX4kIyMiqed9aa.mVTx/wbfcZc2HZVOtdD8HWzvJMujVk.',NULL,'2026-06-30 04:23:26','2026-10-12 11:46:57',NULL,NULL,NULL,0),(4,'Ong Bak Jr.','student@skillbridge.test','student','+639000000004',NULL,1,1,0,'2026-10-10 17:49:56','2026-06-30 05:17:23','$2y$12$E0ZOZooNld2iuhGDenQX5eZvpEJKQXf2hte6ReuvirTIgCosxpnpu',NULL,'2026-06-30 05:17:23','2026-10-12 11:15:27',NULL,NULL,NULL,0),(6,'Lula Batz DDS','employer2@example.com','employer',NULL,NULL,1,1,0,'2026-09-27 10:12:25',NULL,'$2y$12$ZHM9eQq0c/ZwXMwdWU9WM.jrW89zNNlmZgbQ4YrnoF4low3LNWnhW','8PYTAPeTzE','2026-09-27 10:12:25','2026-10-12 05:14:15',NULL,NULL,NULL,0),(8,'Brenna Turner','coordinator2@example.com','coordinator',NULL,NULL,1,1,0,'2026-09-27 10:13:12',NULL,'$2y$12$INpcVkl7goETCeL5rUKR5.BPHvQB9CWvAAZoK.8R0FZAW7fcjI82m','YEOAnB3bWaIza98sFlboaM1dn6UIr2grRN2IvhC1IofaKYHdMZHJV8klakEx','2026-09-27 10:13:12','2026-10-12 05:13:44',NULL,NULL,NULL,0),(9,'Anne Z Sabanico','annesabanico@gmail.com','student','09123123123',NULL,1,1,0,'2026-10-10 10:48:42',NULL,'$2y$12$CW1mb6zTrSXqjpkBe2jxQeM8npYOOpLyiOAwORoCE8aQX97tCu5xa',NULL,'2026-10-10 10:48:01','2026-10-10 11:00:25',NULL,NULL,NULL,0),(10,'Marlo Alcaya','alcayamarlo12@gmail.com','student',NULL,NULL,1,1,0,'2026-10-12 07:58:49',NULL,'$2y$12$6jArQVuoc2JF9FGiCfJKP.cLZZ8KURA2rsYvmo7H.p4BsPiSMis7.',NULL,'2026-10-12 07:58:08','2026-10-12 07:58:49',NULL,NULL,NULL,0),(11,'John Lloyd G. Racaza','johnlloydracaza88@gmail.com','student',NULL,NULL,1,1,0,'2026-10-12 10:04:09',NULL,'$2y$12$8FRoXSPx81nlQDmIT4OqQO0LDoslyONN1ibrSLebDbCUqI4R.SShW',NULL,'2026-10-12 10:03:41','2026-10-12 10:04:09',NULL,NULL,NULL,0),(12,'Johannes Tolentino','mcetolentino0911@gmail.com','student','09957342019',NULL,1,1,0,NULL,NULL,'$2y$12$izmQA/WvK.d25Ww6rLOyie3RYQKtabdzyXIndwJs.hJtLcvsJieJq',NULL,'2026-10-12 10:57:27','2026-10-12 10:57:28',NULL,'$2y$12$9nFBP9F2Z/QXZdRUu6jnGO97x2WbfuJ/IbhoHzBNRxrNvu3/rvQyW','2026-10-12 11:07:28',0),(13,'katrina lim','penayflex@gmail.com','student','09957342018',NULL,1,1,0,NULL,NULL,'$2y$12$c8Muep.ZguQq7OXTGJe7BOdEFEg24kgV/ruaro7o8oQrkabEeqtua',NULL,'2026-10-12 12:01:56','2026-10-12 12:01:56',NULL,NULL,NULL,0),(14,'Marga bahog belat','ok@gmail.com','student','09081618046',NULL,1,1,0,NULL,NULL,'$2y$12$Nj4OBhbysq15M.6K9atkjevFZLyWgIlnL2FDiy3t.hC89q8n5Iev.',NULL,'2026-10-12 12:21:33','2026-10-12 12:21:33',NULL,'$2y$12$hUR3Wt6uX1K7LKmwDK551O/Fuw2f7lGu7mdEA..p7ywdl0V/6wzCG','2026-10-12 12:31:33',0);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification_activities`
--

DROP TABLE IF EXISTS `verification_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `verification_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `item_type` varchar(32) NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `item` varchar(255) NOT NULL,
  `action` varchar(32) NOT NULL,
  `note` text DEFAULT NULL,
  `coordinator_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `verification_activities_coordinator_id_foreign` (`coordinator_id`),
  KEY `verification_activities_student_id_created_at_index` (`student_id`,`created_at`),
  KEY `verification_activities_item_type_item_id_index` (`item_type`,`item_id`),
  CONSTRAINT `verification_activities_coordinator_id_foreign` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinators` (`id`) ON DELETE SET NULL,
  CONSTRAINT `verification_activities_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification_activities`
--

LOCK TABLES `verification_activities` WRITE;
/*!40000 ALTER TABLE `verification_activities` DISABLE KEYS */;
INSERT INTO `verification_activities` VALUES (1,1,'portfolio',7,'Certificate of registration (COR)','rejected','Evidence unclear',1,'2026-10-12 04:54:21','2026-10-12 04:54:21'),(2,1,'portfolio',7,'Certificate of registration (COR)','approved',NULL,1,'2026-10-12 04:56:44','2026-10-12 04:56:44'),(3,1,'certificates',1,'AWS Certified Cloud Practitioner','verified',NULL,1,'2026-10-12 05:30:54','2026-10-12 05:30:54'),(4,1,'competencies',7,'Team Leadership','verified',NULL,1,'2026-10-12 11:31:24','2026-10-12 11:31:24'),(5,1,'competencies',9,'Git Version Control','verified',NULL,1,'2026-10-12 11:31:24','2026-10-12 11:31:24'),(6,1,'portfolio',9,'Medical certificate','approved',NULL,1,'2026-10-12 11:31:46','2026-10-12 11:31:46'),(7,1,'portfolio',8,'Parent or guardian consent / waiver','approved',NULL,1,'2026-10-12 11:39:24','2026-10-12 11:39:24');
/*!40000 ALTER TABLE `verification_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'nexus'
--

--
-- Dumping routines for database 'nexus'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-13 17:30:32
