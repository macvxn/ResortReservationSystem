mysqldump: Deprecated program name. It will be removed in a future release, use '/data/data/com.termux/files/usr/bin/mariadb-dump' instead
/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.1.2-MariaDB, for Android (aarch64)
--
-- Host: 127.0.0.1    Database: resort_reservation_db
-- ------------------------------------------------------
-- Server version	12.1.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_value` text DEFAULT NULL COMMENT 'JSON format',
  `new_value` text DEFAULT NULL COMMENT 'JSON format',
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `audit_logs` VALUES
(1,NULL,'email_verified','users',2,NULL,NULL,'127.0.0.1','2026-01-11 13:45:21'),
(2,3,'email_verified','users',3,NULL,NULL,'127.0.0.1','2026-01-11 13:55:55'),
(3,3,'profile_updated','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 14:13:06'),
(4,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 14:13:34'),
(5,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-11 16:22:39'),
(6,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-11 17:06:41'),
(7,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-11 17:07:37'),
(8,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-11 17:09:17'),
(9,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-11 17:09:19'),
(10,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-11 17:59:05'),
(11,1,'user_rejected','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 17:59:22'),
(12,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-11 17:59:27'),
(13,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-11 17:59:37'),
(14,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:01:05'),
(15,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:02:01'),
(16,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:02:02'),
(17,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-11 18:02:10'),
(18,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-11 18:02:15'),
(19,1,'user_rejected','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:29:13'),
(20,1,'user_rejected','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:29:15'),
(21,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-11 18:29:15'),
(22,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-11 18:29:24'),
(23,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:29:58'),
(24,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:30:01'),
(25,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-11 18:50:34'),
(26,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-11 18:50:40'),
(27,1,'user_rejected','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:51:03'),
(28,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-11 18:51:04'),
(29,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-11 18:51:18'),
(30,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 18:51:35'),
(31,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-11 19:11:06'),
(32,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-11 19:11:23'),
(33,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-12 13:00:20'),
(34,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-12 13:00:47'),
(35,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-12 13:00:52'),
(36,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-12 13:01:30'),
(37,NULL,'email_verified','users',5,NULL,NULL,'127.0.0.1','2026-01-12 13:02:32'),
(38,NULL,'logout','users',5,NULL,NULL,'127.0.0.1','2026-01-12 13:03:26'),
(39,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-12 19:46:11'),
(40,1,'user_rejected','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-12 19:47:24'),
(41,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-12 19:47:40'),
(42,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-12 19:47:46'),
(43,3,'profile_updated','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-12 19:48:09'),
(44,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-12 20:29:42'),
(45,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-12 20:31:00'),
(46,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-12 20:31:13'),
(47,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-12 20:31:21'),
(48,1,'user_rejected','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-12 20:35:18'),
(49,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-12 20:35:24'),
(50,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-12 20:35:31'),
(51,3,'profile_updated','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-12 20:36:34'),
(52,3,'id_uploaded','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-12 20:38:05'),
(53,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-12 20:38:10'),
(54,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-12 20:38:16'),
(55,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-12 20:38:41'),
(56,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-12 21:35:46'),
(57,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-12 21:49:56'),
(58,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-12 21:50:02'),
(59,1,'user_verified','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-12 21:50:11'),
(60,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-12 21:50:13'),
(61,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-12 21:50:22'),
(62,3,'CREATE_RESERVATION','reservations',1,NULL,NULL,'127.0.0.1','2026-01-12 22:33:24'),
(63,3,'CREATE_RESERVATION','reservations',2,NULL,NULL,'127.0.0.1','2026-01-12 22:42:24'),
(64,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-13 13:24:26'),
(65,3,'profile_updated','user_profiles',3,NULL,NULL,'127.0.0.1','2026-01-13 13:24:49'),
(66,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-13 13:25:28'),
(67,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 13:25:40'),
(68,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 13:25:55'),
(69,NULL,'login','users',5,NULL,NULL,'127.0.0.1','2026-01-13 19:15:32'),
(70,NULL,'profile_updated','user_profiles',17,NULL,NULL,'127.0.0.1','2026-01-13 19:17:06'),
(71,NULL,'id_uploaded','user_profiles',17,NULL,NULL,'127.0.0.1','2026-01-13 19:17:13'),
(72,NULL,'logout','users',5,NULL,NULL,'127.0.0.1','2026-01-13 19:17:18'),
(73,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:17:25'),
(74,1,'user_rejected','user_profiles',17,NULL,NULL,'127.0.0.1','2026-01-13 19:17:49'),
(75,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:17:50'),
(76,NULL,'login','users',5,NULL,NULL,'127.0.0.1','2026-01-13 19:18:02'),
(77,NULL,'id_uploaded','user_profiles',17,NULL,NULL,'127.0.0.1','2026-01-13 19:18:24'),
(78,NULL,'logout','users',5,NULL,NULL,'127.0.0.1','2026-01-13 19:18:29'),
(79,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:18:44'),
(80,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:18:53'),
(81,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:33:04'),
(82,1,'user_rejected','user_profiles',17,NULL,NULL,'127.0.0.1','2026-01-13 19:37:22'),
(83,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:37:24'),
(84,NULL,'login','users',5,NULL,NULL,'127.0.0.1','2026-01-13 19:37:32'),
(85,NULL,'id_uploaded','user_profiles',17,NULL,NULL,'127.0.0.1','2026-01-13 19:38:20'),
(86,NULL,'auto_verified','user_profiles',17,NULL,NULL,'127.0.0.1','2026-01-13 19:38:23'),
(87,NULL,'logout','users',5,NULL,NULL,'127.0.0.1','2026-01-13 19:40:04'),
(88,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:40:11'),
(89,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:48:29'),
(90,6,'email_verified','users',6,NULL,NULL,'127.0.0.1','2026-01-13 19:56:57'),
(91,6,'profile_updated','user_profiles',18,NULL,NULL,'127.0.0.1','2026-01-13 19:57:19'),
(92,6,'id_uploaded','user_profiles',18,NULL,NULL,'127.0.0.1','2026-01-13 19:57:34'),
(93,6,'manual_review_required','user_profiles',18,NULL,NULL,'127.0.0.1','2026-01-13 19:57:36'),
(94,6,'logout','users',6,NULL,NULL,'127.0.0.1','2026-01-13 19:57:43'),
(95,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:57:51'),
(96,1,'user_rejected','user_profiles',18,NULL,NULL,'127.0.0.1','2026-01-13 19:58:06'),
(97,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 19:58:08'),
(98,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-13 20:05:42'),
(99,3,'CREATE_RESERVATION','reservations',3,NULL,NULL,'127.0.0.1','2026-01-13 20:06:02'),
(100,3,'CREATE_RESERVATION','reservations',4,NULL,NULL,'127.0.0.1','2026-01-13 20:25:00'),
(101,3,'UPLOAD_PAYMENT','payment_proofs',1,NULL,NULL,'127.0.0.1','2026-01-13 20:25:56'),
(102,3,'CREATE_RESERVATION','reservations',5,NULL,NULL,'127.0.0.1','2026-01-13 20:32:26'),
(103,3,'CREATE_RESERVATION','reservations',6,NULL,NULL,'127.0.0.1','2026-01-13 20:36:02'),
(104,3,'CREATE_RESERVATION','reservations',7,NULL,NULL,'127.0.0.1','2026-01-13 20:49:27'),
(105,3,'UPLOAD_PAYMENT','payment_proofs',2,NULL,NULL,'127.0.0.1','2026-01-13 20:50:25'),
(106,3,'CREATE_RESERVATION','reservations',8,NULL,NULL,'127.0.0.1','2026-01-13 21:15:28'),
(107,3,'CREATE_RESERVATION','reservations',9,NULL,NULL,'127.0.0.1','2026-01-13 21:16:00'),
(108,3,'CREATE_RESERVATION','reservations',10,NULL,NULL,'127.0.0.1','2026-01-13 21:19:38'),
(109,3,'UPLOAD_PAYMENT','payment_proofs',3,NULL,NULL,'127.0.0.1','2026-01-13 21:19:59'),
(110,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-13 21:39:10'),
(111,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 21:39:19'),
(112,1,'REJECT_RESERVATION','reservations',10,NULL,NULL,'127.0.0.1','2026-01-13 21:41:26'),
(113,1,'REJECT_RESERVATION','reservations',9,NULL,NULL,'127.0.0.1','2026-01-13 21:41:33'),
(114,1,'REJECT_RESERVATION','reservations',8,NULL,NULL,'127.0.0.1','2026-01-13 21:41:38'),
(115,1,'APPROVE_RESERVATION','reservations',7,NULL,NULL,'127.0.0.1','2026-01-13 21:43:03'),
(116,1,'REJECT_RESERVATION','reservations',6,NULL,NULL,'127.0.0.1','2026-01-13 21:43:11'),
(117,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 21:43:31'),
(118,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-13 21:43:38'),
(119,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-13 21:45:13'),
(120,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 21:45:21'),
(121,1,'DISABLE_COTTAGE','cottages',1,NULL,NULL,'127.0.0.1','2026-01-13 22:06:44'),
(122,1,'ENABLE_COTTAGE','cottages',1,NULL,NULL,'127.0.0.1','2026-01-13 22:06:49'),
(123,1,'ADD_COTTAGE','cottages',7,NULL,NULL,'127.0.0.1','2026-01-13 22:08:24'),
(124,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 22:09:34'),
(125,3,'login','users',3,NULL,NULL,'127.0.0.1','2026-01-13 22:09:47'),
(126,3,'logout','users',3,NULL,NULL,'127.0.0.1','2026-01-13 22:10:31'),
(127,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 22:10:38'),
(128,1,'logout','users',1,NULL,NULL,'127.0.0.1','2026-01-13 22:17:37'),
(129,1,'login','users',1,NULL,NULL,'127.0.0.1','2026-01-13 22:17:51'),
(130,1,'EXPORT_AUDIT_LOGS','audit_logs',NULL,NULL,NULL,'127.0.0.1','2026-01-13 22:38:12'),
(131,1,'EXPORT_AUDIT_LOGS','audit_logs',NULL,NULL,NULL,'127.0.0.1','2026-01-13 22:38:17');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Temporary table structure for view `cottage_availability`
--

DROP TABLE IF EXISTS `cottage_availability`;
/*!50001 DROP VIEW IF EXISTS `cottage_availability`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `cottage_availability` AS SELECT
 1 AS `cottage_id`,
  1 AS `cottage_name`,
  1 AS `capacity`,
  1 AS `price_per_night`,
  1 AS `total_reservations`,
  1 AS `approved_reservations` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `cottage_images`
--

DROP TABLE IF EXISTS `cottage_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cottage_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `cottage_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `idx_cottage_id` (`cottage_id`),
  CONSTRAINT `1` FOREIGN KEY (`cottage_id`) REFERENCES `cottages` (`cottage_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cottage_images`
--

LOCK TABLES `cottage_images` WRITE;
/*!40000 ALTER TABLE `cottage_images` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cottage_images` VALUES
(1,1,'cottage1_1.jpg',1,'2026-01-12 21:35:09'),
(2,2,'cottage1_2.jpg',0,'2026-01-12 21:35:09'),
(3,4,'cottage2_1.jpg',1,'2026-01-12 21:35:09'),
(4,5,'cottage3_1.jpg',1,'2026-01-12 21:35:09'),
(7,7,'696651d810f39_1768313304.jpg',1,'2026-01-13 22:08:24');
/*!40000 ALTER TABLE `cottage_images` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cottages`
--

DROP TABLE IF EXISTS `cottages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cottages` (
  `cottage_id` int(11) NOT NULL AUTO_INCREMENT,
  `cottage_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cottage_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cottages`
--

LOCK TABLES `cottages` WRITE;
/*!40000 ALTER TABLE `cottages` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `cottages` VALUES
(1,'Sunset Villa','Beautiful beachfront cottage with stunning sunset views',6,3500.00,1,'2026-01-10 23:40:46','2026-01-13 22:06:49'),
(2,'Mountain View Cabin','Cozy cabin surrounded by pine trees',4,2500.00,1,'2026-01-10 23:40:46','2026-01-10 23:40:46'),
(3,'Lakeside Retreat','Peaceful cottage by the lake with fishing access',8,4000.00,1,'2026-01-10 23:40:46','2026-01-10 23:40:46'),
(4,'Beachfront Paradise','Modern cottage with direct beach access and private balcony',6,4500.00,1,'2026-01-12 21:35:09','2026-01-12 21:35:09'),
(5,'Family Retreat','Spacious cottage perfect for family gatherings',10,5500.00,1,'2026-01-12 21:35:09','2026-01-12 21:35:09'),
(6,'Romantic Getaway','Intimate cottage with jacuzzi and garden view',2,3800.00,1,'2026-01-12 21:35:09','2026-01-12 21:35:09'),
(7,'Bitch','For family',2,1000.00,1,'2026-01-13 22:08:24','2026-01-13 22:08:24');
/*!40000 ALTER TABLE `cottages` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_successful` tinyint(1) DEFAULT 0,
  `attempted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`attempt_id`),
  KEY `idx_email` (`email`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `login_attempts` VALUES
(1,'admin@gmail.com','127.0.0.1',0,'2026-01-11 13:34:30'),
(2,'admin@gmail.com','127.0.0.1',0,'2026-01-11 13:34:36'),
(3,'admin@gmail.com','127.0.0.1',0,'2026-01-11 13:34:41'),
(4,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:22:49'),
(5,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:22:53'),
(6,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:22:58'),
(7,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:23:03'),
(8,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:23:09'),
(9,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:23:13'),
(10,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:23:16'),
(11,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:23:18'),
(12,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:25:15'),
(13,'admin@gmail.com','127.0.0.1',0,'2026-01-11 16:45:43'),
(14,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-11 17:06:41'),
(15,'admin@gmail.com','127.0.0.1',0,'2026-01-11 17:07:55'),
(16,'admin@resort.com','127.0.0.1',0,'2026-01-11 17:08:36'),
(17,'admin@resort.com','127.0.0.1',0,'2026-01-11 17:09:04'),
(18,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-11 17:09:17'),
(19,'admin@gmail.com','127.0.0.1',0,'2026-01-11 17:09:52'),
(20,'admin@gmail.com','127.0.0.1',0,'2026-01-11 17:09:57'),
(21,'admin@gmail.com','127.0.0.1',1,'2026-01-11 17:59:04'),
(22,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-11 17:59:37'),
(23,'admin@gmail.com','127.0.0.1',1,'2026-01-11 18:02:15'),
(24,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-11 18:29:24'),
(25,'admin@gmail.com','127.0.0.1',1,'2026-01-11 18:50:40'),
(26,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-11 18:51:18'),
(27,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-12 13:00:20'),
(28,'admin@gmail.com','127.0.0.1',1,'2026-01-12 13:00:52'),
(29,'admin@gmail.com','127.0.0.1',1,'2026-01-12 19:46:11'),
(30,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-12 19:47:46'),
(31,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-12 20:29:42'),
(32,'admin@gmail.com','127.0.0.1',1,'2026-01-12 20:31:21'),
(33,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-12 20:35:31'),
(34,'admin@gmail.com','127.0.0.1',1,'2026-01-12 20:38:16'),
(35,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-12 21:35:46'),
(36,'admin@gmail.com','127.0.0.1',1,'2026-01-12 21:50:02'),
(37,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-12 21:50:22'),
(38,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-13 13:24:26'),
(39,'admin@gmail.com','127.0.0.1',1,'2026-01-13 13:25:40'),
(40,'laurentemarvin028@gmail.com','127.0.0.1',1,'2026-01-13 19:15:32'),
(41,'admin@gmail.com','127.0.0.1',1,'2026-01-13 19:17:25'),
(42,'laurentemarvin028@gmail.com','127.0.0.1',1,'2026-01-13 19:18:02'),
(43,'admin@gmail.com','127.0.0.1',1,'2026-01-13 19:18:44'),
(44,'admin@gmail.com','127.0.0.1',1,'2026-01-13 19:33:04'),
(45,'laurentemarvin028@gmail.com','127.0.0.1',1,'2026-01-13 19:37:32'),
(46,'admin@gmail.com','127.0.0.1',1,'2026-01-13 19:40:11'),
(47,'admin@gmail.com','127.0.0.1',1,'2026-01-13 19:57:51'),
(48,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-13 20:05:42'),
(49,'admin@gmail.com','127.0.0.1',1,'2026-01-13 21:39:19'),
(50,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-13 21:43:38'),
(51,'admin@gmail.com','127.0.0.1',1,'2026-01-13 21:45:21'),
(52,'marvinlaurente028@gmail.com','127.0.0.1',0,'2026-01-13 22:09:41'),
(53,'marvinlaurente028@gmail.com','127.0.0.1',1,'2026-01-13 22:09:47'),
(54,'admin@gmail.com','127.0.0.1',1,'2026-01-13 22:10:38'),
(55,'admin@gmail.com','127.0.0.1',0,'2026-01-13 22:17:47'),
(56,'admin@gmail.com','127.0.0.1',1,'2026-01-13 22:17:51');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `ocr_verification_logs`
--

DROP TABLE IF EXISTS `ocr_verification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ocr_verification_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `extracted_text` text DEFAULT NULL,
  `normalized_text` text DEFAULT NULL,
  `confidence_score` decimal(5,2) DEFAULT NULL COMMENT 'Accuracy percentage (0-100)',
  `processed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_profile_id` (`profile_id`),
  CONSTRAINT `1` FOREIGN KEY (`profile_id`) REFERENCES `user_profiles` (`profile_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ocr_verification_logs`
--

LOCK TABLES `ocr_verification_logs` WRITE;
/*!40000 ALTER TABLE `ocr_verification_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `ocr_verification_logs` VALUES
(1,3,'','',0.00,'2026-01-11 19:11:06'),
(2,3,'8:36 PM &S R (B\n\nX Adidas Customer Journey ... 0<g :\nPain Points Opportunities\nToo many\n\nStrong brand\nbrand g .\n, storytelling\n\noptions\nUncertain Virtual try-on\nabout sizing feature, size\nand fit guides\nPremium Member\npricing, discounts,\nshipping free shipping\ncosts threshold\nOccasional Quality\nquality assurance,\nissues easy returns\n\nReply to Claude...\n\n+ ¢ @','836 pm s r b x adidas customer journey 0g pain points opportunities too many strong brand brand g storytelling options uncertain virtual tryon about sizing feature size and fit guides premium member pricing discounts shipping free shipping costs threshold occasional quality quality assurance issues easy returns reply to claude',3.75,'2026-01-12 20:31:01'),
(3,3,'SO ~ REPUBLIKA NG PILIPINAS\n= \\ Republic of the Philippines rZ‘ﬁ\\:\\!-‘l“\n‘“. PAMBANSANG PAGKAKAKILANLAN @\\; PRI\ni i\\n < ,.f\' i “I“:(\\:Q;,iui Philippine [dentification Card \"\"mmm’\"‘r L_ \'u/-i\\ N\ns (] \"|“‘.‘ Tk AU \\“‘_“\' Wi, Beasa i ,“I‘\\“\'“‘ 0 ‘\\\\‘]‘\n5483-1286-7908-1746 LR\n% ) «\"‘-\' il - - Apelyido/Last Name ‘ \\\\\'{1\'\\‘{“!‘!\\?{,‘\\ i\\ \"\\H\\\\\\‘ |\n! 1 Mga Pangalan/Given Names\' ;,‘jf;\':\".‘,t‘x.:_tt‘vun..\\. i IJ\\/} i\n\">l o MARVIN %\n3 .?.‘ | Gitnang Apelyido/Middle Name\n% | HERNAL\nr - --;\'v,, o | Petsa ng Kapanganakan/Date of Birth\no OCTOBER 22, 2003\nTirahan/Address J ::—:%faj: ’ PHL\nPUROK STARAPPLE, MALINGIN, CIFZDF BAGO, NEGROS 7\nOCCIDENTAL ”%’ A&','so republika ng pilipinas republic of the philippines rzl pambansang pagkakakilanlan pri i in f i iqiui philippine dentification card mmmr l ui n s tk au wi beasa i i 0 5483128679081746 lr il apelyidolast name 1 i h 1 mga pangalangiven names jftxttvun i ij i l o marvin 3 gitnang apelyidomiddle name hernal r v o petsa ng kapanganakandate of birth o october 22 2003 tirahanaddress j faj phl purok starapple malingin cifzdf bago negros 7 occidental a',75.00,'2026-01-12 20:38:08'),
(7,18,'1208 AM @ >_ B T Sl S @Em)\nO E-CLEARANCE /7 3\nB g database\nQ @ system e\n» = api\n2 m config\n@m assets\n|\n% CSS\n@ BB images\n= js\n& adminlte\nm dist\n‘ m plugins\n@ includes\n@@ conn.php\n‘ @ mail_config.php\n@ vendor\n@ composer\n@ dompdf\n@ masterminds\nB phpmailer\n@ sabberworm\nm thecodingmachine\n@ autoload.php\n{} composer.json @\nI & composer.lock\ne @ index.php v a ESC','1208 am b t sl s em o eclearance 7 3 b g database q system e api 2 m config m assets css bb images js adminlte m dist m plugins includes connphp mailconfigphp vendor composer dompdf masterminds b phpmailer sabberworm m thecodingmachine autoloadphp composerjson i composerlock e indexphp v a esc',0.00,'2026-01-13 19:57:36');
/*!40000 ALTER TABLE `ocr_verification_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `payment_proofs`
--

DROP TABLE IF EXISTS `payment_proofs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_proofs` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `reservation_id` int(11) NOT NULL,
  `receipt_image_path` varchar(255) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `idx_reservation_id` (`reservation_id`),
  CONSTRAINT `1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_proofs`
--

LOCK TABLES `payment_proofs` WRITE;
/*!40000 ALTER TABLE `payment_proofs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `payment_proofs` VALUES
(2,7,'payment_69663f91b97296.23088688_1768308625.jpg','929383747483','2026-01-13 20:50:25'),
(3,10,'6966467f47100_1768310399.jpg','929383747483','2026-01-13 21:19:59');
/*!40000 ALTER TABLE `payment_proofs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Temporary table structure for view `pending_reservations`
--

DROP TABLE IF EXISTS `pending_reservations`;
/*!50001 DROP VIEW IF EXISTS `pending_reservations`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `pending_reservations` AS SELECT
 1 AS `reservation_id`,
  1 AS `email`,
  1 AS `full_name`,
  1 AS `cottage_name`,
  1 AS `check_in_date`,
  1 AS `check_out_date`,
  1 AS `total_nights`,
  1 AS `total_price`,
  1 AS `receipt_image_path`,
  1 AS `reference_number`,
  1 AS `created_at` */;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `pending_verifications`
--

DROP TABLE IF EXISTS `pending_verifications`;
/*!50001 DROP VIEW IF EXISTS `pending_verifications`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8mb4;
/*!50001 CREATE VIEW `pending_verifications` AS SELECT
 1 AS `profile_id`,
  1 AS `email`,
  1 AS `full_name`,
  1 AS `phone_number`,
  1 AS `id_image_path`,
  1 AS `created_at`,
  1 AS `confidence_score` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `cottage_id` int(11) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_nights` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending_admin_review','approved','rejected') DEFAULT 'pending_admin_review',
  `admin_remarks` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`reservation_id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_cottage_id` (`cottage_id`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`check_in_date`,`check_out_date`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`cottage_id`) REFERENCES `cottages` (`cottage_id`) ON DELETE CASCADE,
  CONSTRAINT `3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations`
--

LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `reservations` VALUES
(6,3,4,'2026-01-14','2026-01-16',2,9000.00,'rejected','Nn',1,'2026-01-13 21:43:11','2026-01-13 20:36:02','2026-01-13 21:43:11'),
(7,3,2,'2026-01-13','2026-01-22',9,22500.00,'approved',NULL,1,'2026-01-13 21:43:03','2026-01-13 20:49:27','2026-01-13 21:43:03'),
(8,3,1,'2026-01-14','2026-01-20',6,21000.00,'rejected','Jnnn',1,'2026-01-13 21:41:38','2026-01-13 21:15:28','2026-01-13 21:41:38'),
(9,3,1,'2026-01-21','2026-01-26',5,17500.00,'rejected','Jjj',1,'2026-01-13 21:41:33','2026-01-13 21:16:00','2026-01-13 21:41:33'),
(10,3,1,'2026-01-13','2026-01-16',3,10500.00,'rejected','Jejej',1,'2026-01-13 21:41:26','2026-01-13 21:19:38','2026-01-13 21:41:26');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `user_profiles`
--

DROP TABLE IF EXISTS `user_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_profiles` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `id_image_path` varchar(255) DEFAULT NULL,
  `verification_status` enum('unverified','pending_verification','verified','rejected') DEFAULT 'unverified',
  `admin_remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `verified_by` (`verified_by`),
  KEY `idx_verification_status` (`verification_status`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_profiles`
--

LOCK TABLES `user_profiles` WRITE;
/*!40000 ALTER TABLE `user_profiles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `user_profiles` VALUES
(3,3,'Marvin Laurente','09952721095','Bago city','5483-1286-7908-1746','6964eb2d2da5d_1768221485.jpg','verified',NULL,1,'2026-01-12 21:50:11','2026-01-11 13:55:55','2026-01-13 13:24:49'),
(18,6,'Marvin Laurente','09952721095','NA','5483-1286-7908-1746','6966332e81478_1768305454.jpg','rejected','Kwjwkkw',1,'2026-01-13 19:58:06','2026-01-13 19:56:57','2026-01-13 19:58:06');
/*!40000 ALTER TABLE `user_profiles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_email_verified` tinyint(1) DEFAULT 0,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(1,'admin@gmail.com','$2y$12$/rGNsXd9K.pP/qATbjG69uRlB6vw7voJCsHLvmxpTrC.nwFRf.LWK','admin',1,NULL,NULL,'2026-01-10 23:40:46','2026-01-11 17:58:48'),
(3,'marvinlaurente028@gmail.com','$2y$12$l.T4RrQ2q57ttdeMQDBT9.DQk.7dVgBf2Ef6bUmhO/eLCdnxXd/kO','user',1,NULL,NULL,'2026-01-11 13:55:17','2026-01-11 13:55:55'),
(6,'laurentemarvin028@gmail.com','$2y$12$j30aTokVSPr.176Wo/Bvw./e1SiXkgDSjoROX9S5EZbLfMjSdcF4u','user',1,NULL,NULL,'2026-01-13 19:56:23','2026-01-13 19:56:57');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Final view structure for view `cottage_availability`
--

/*!50001 DROP VIEW IF EXISTS `cottage_availability`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `cottage_availability` AS select `c`.`cottage_id` AS `cottage_id`,`c`.`cottage_name` AS `cottage_name`,`c`.`capacity` AS `capacity`,`c`.`price_per_night` AS `price_per_night`,count(`r`.`reservation_id`) AS `total_reservations`,sum(case when `r`.`status` = 'approved' then 1 else 0 end) AS `approved_reservations` from (`cottages` `c` left join `reservations` `r` on(`c`.`cottage_id` = `r`.`cottage_id`)) where `c`.`is_active` = 1 group by `c`.`cottage_id`,`c`.`cottage_name`,`c`.`capacity`,`c`.`price_per_night` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `pending_reservations`
--

/*!50001 DROP VIEW IF EXISTS `pending_reservations`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `pending_reservations` AS select `r`.`reservation_id` AS `reservation_id`,`u`.`email` AS `email`,`up`.`full_name` AS `full_name`,`c`.`cottage_name` AS `cottage_name`,`r`.`check_in_date` AS `check_in_date`,`r`.`check_out_date` AS `check_out_date`,`r`.`total_nights` AS `total_nights`,`r`.`total_price` AS `total_price`,`pp`.`receipt_image_path` AS `receipt_image_path`,`pp`.`reference_number` AS `reference_number`,`r`.`created_at` AS `created_at` from ((((`reservations` `r` join `users` `u` on(`r`.`user_id` = `u`.`user_id`)) join `user_profiles` `up` on(`u`.`user_id` = `up`.`user_id`)) join `cottages` `c` on(`r`.`cottage_id` = `c`.`cottage_id`)) left join `payment_proofs` `pp` on(`r`.`reservation_id` = `pp`.`reservation_id`)) where `r`.`status` = 'pending_admin_review' order by `r`.`created_at` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `pending_verifications`
--

/*!50001 DROP VIEW IF EXISTS `pending_verifications`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `pending_verifications` AS select `up`.`profile_id` AS `profile_id`,`u`.`email` AS `email`,`up`.`full_name` AS `full_name`,`up`.`phone_number` AS `phone_number`,`up`.`id_image_path` AS `id_image_path`,`up`.`created_at` AS `created_at`,`ocr`.`confidence_score` AS `confidence_score` from ((`user_profiles` `up` join `users` `u` on(`up`.`user_id` = `u`.`user_id`)) left join `ocr_verification_logs` `ocr` on(`up`.`profile_id` = `ocr`.`profile_id`)) where `up`.`verification_status` = 'pending_verification' order by `up`.`created_at` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-01-13 22:40:04
