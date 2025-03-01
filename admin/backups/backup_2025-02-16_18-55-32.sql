-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: isp
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
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (38,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 06:19:37'),(39,4,'create_plan','Created new plan: Basic plan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 06:20:06'),(40,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 11:38:23'),(41,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 11:50:07'),(42,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 12:03:50'),(43,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 12:06:26'),(44,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 12:07:02'),(45,4,'remove_subscription','Removed plan subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 12:10:36'),(46,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 12:10:50'),(47,4,'add_payment','Added payment of ₱1000.00 for billing ID: 5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 13:26:28'),(48,4,'edit_payment','Updated payment #1 - Amount: ₱1000.00, Status: completed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 13:36:59'),(49,4,'edit_payment','Updated payment #1 - Amount: ₱1000.00, Status: completed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-10 13:37:26'),(50,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:15:22'),(51,4,'remove_subscription','Removed plan subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:15:52'),(52,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:16:04'),(53,4,'remove_subscription','Removed plan subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:21:06'),(54,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:21:16'),(55,4,'remove_subscription','Removed plan subscription for customer: rona buan','::1','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36','2025-02-11 04:32:51'),(56,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36','2025-02-11 04:33:04'),(57,4,'remove_subscription','Removed plan subscription for customer: rona buan','::1','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36','2025-02-11 04:36:51'),(58,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Mobile Safari/537.36','2025-02-11 04:37:15'),(59,4,'remove_subscription','Removed plan subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:43:20'),(60,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:43:30'),(61,4,'remove_subscription','Removed plan subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:50:03'),(62,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:50:12'),(63,4,'remove_subscription','Removed subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:53:37'),(64,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:53:44'),(65,4,'remove_subscription','Removed subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:53:54'),(66,4,'update_subscription','Updated subscription for customer: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 04:54:04'),(67,4,'settings_update','Updated company settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 05:16:54'),(68,4,'add_payment','Added payment of ₱1000.00 for billing ID: 6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 05:18:23'),(69,4,'add_payment','Added payment of ₱1000.00 for billing ID: 7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 05:23:03'),(70,4,'add_payment','Added payment of ₱1000.00 for billing ID: 8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 06:06:52'),(71,4,'add_payment','Added payment of ₱1000.00 for billing ID: 8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 06:43:46'),(72,4,'payment','Payment of ₱500.00 recorded for billing #15',NULL,NULL,'2025-02-11 09:12:59'),(76,4,'payment','Payment of ₱500.00 recorded for billing #16',NULL,NULL,'2025-02-11 09:22:57'),(77,4,'payment','Payment of ₱500.00 recorded for billing #17',NULL,NULL,'2025-02-11 09:31:50'),(78,4,'settings_update','Updated company settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 10:19:59'),(79,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 10:40:10'),(80,4,'create_asset','Created new asset: Vendo 1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 13:51:38'),(81,4,'settings_update','Updated company settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-11 15:05:08'),(82,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 01:40:28'),(83,4,'add_expense','Added expense of ₱1,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 04:57:34'),(84,4,'add_expense','Added expense of ₱1,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 04:59:28'),(85,4,'update_expense','Updated expense of ₱1,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 05:04:31'),(86,4,'approved_expense','Approved expense of ₱1,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 05:04:34'),(87,4,'add_expense','Added expense of ₱1,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 05:11:58'),(88,4,'add_expense','Added expense of ₱1,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 05:44:01'),(89,4,'approved_expense','Approved expense of ₱1,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 05:52:28'),(90,4,'add_expense','Added expense of ₱2,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 06:08:11'),(91,4,'add_expense','Added expense of ₱2,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 06:08:35'),(92,4,'rejected_expense','Rejected expense of ₱2,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 06:08:37'),(93,4,'edit_payment','Updated payment #22 - Amount: ₱1000.00, Status: completed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 07:28:12'),(94,4,'edit_payment','Updated payment #22 - Amount: ₱1000.00, Status: completed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 07:39:41'),(95,4,'edit_payment','Updated payment #22 - Amount: ₱1000.00, Status: completed','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-12 07:39:58'),(96,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 02:35:51'),(97,4,'add_expense','Added expense of ₱1,000.00','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 02:36:13'),(98,4,'create_collection','Created new collection #3 for asset #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 05:28:31'),(99,4,'create_collection','Created new collection #4 for asset #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 05:30:17'),(100,4,'create_collection','Created new collection #5 for asset #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 05:33:10'),(101,4,'create_collection','Created new collection #6 for asset #3','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 05:36:25'),(102,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 06:23:51'),(103,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 06:31:12'),(104,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 07:06:01'),(105,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0','2025-02-13 07:20:20'),(106,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0','2025-02-13 07:23:04'),(107,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 Edg/133.0.0.0','2025-02-13 07:49:31'),(108,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 08:05:48'),(109,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:01:14'),(110,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:01:20'),(111,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:14:09'),(112,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:14:13'),(113,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:14:20'),(114,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:14:32'),(115,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:20:20'),(116,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:20:24'),(117,4,'create_role','Created new role: Admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:38:45'),(118,4,'create_backup','Created database backup: backup_2025-02-13_18-40-44.sql','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:40:45'),(119,4,'create_role','Created new role: Customers','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:42:20'),(120,4,'create_role','Created new role: Staff','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 10:42:58'),(121,4,'update_settings','Updated system settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 11:21:07'),(122,4,'update_user','User: ronab','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 11:22:26'),(123,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 11:22:30'),(124,5,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 11:22:35'),(125,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 12:55:19'),(126,4,'update_user','User: ronab','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 13:01:41'),(128,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 13:03:02'),(130,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 13:03:27'),(131,4,'create_employee','Created new employee: fernan formentera (EMP2025-0001)','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-13 13:04:04'),(136,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 00:12:11'),(138,4,'update_user','User: fformentera','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 00:23:17'),(139,4,'update_user','User: fformentera','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 00:28:43'),(140,4,'update_role','Updated role: Admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:01:47'),(141,4,'update_role','Updated role: Staff','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:03:21'),(142,4,'update_user','User: fformentera','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:03:44'),(143,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:03:45'),(144,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:03:49'),(145,7,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:04:38'),(146,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:04:43'),(147,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:07:58'),(148,5,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:08:03'),(149,5,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:22:20'),(150,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:22:24'),(151,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:33:31'),(152,5,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 01:33:35'),(153,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 09:13:09'),(154,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 09:14:36'),(155,5,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 09:14:43'),(156,5,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 09:17:01'),(157,5,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 10:48:00'),(158,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 10:48:04'),(159,4,'create_backup','Created database backup: backup_2025-02-14_19-27-36.sql','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-14 11:27:37'),(160,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 04:10:59'),(161,4,'create_plan','Created new plan: Premium plan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 04:11:41'),(162,4,'update_plan','Updated plan: Premium plan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 04:11:57'),(163,4,'update','Customer updated: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 04:56:28'),(164,4,'update','Customer updated: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 04:56:48'),(165,4,'update','Customer updated: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 05:00:05'),(166,4,'update','Customer updated: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 05:00:14'),(167,4,'update_settings','Updated system settings','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 05:20:17'),(168,4,'update_plan','Updated plan: Basic plan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 05:21:31'),(169,4,'update_plan','Updated plan: Premium plan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 05:21:38'),(170,4,'update_plan','Updated plan: Premium plan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 05:21:48'),(171,4,'update','Customer updated: rona buan','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 06:39:01'),(172,4,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 06:55:15'),(173,7,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 06:55:23'),(174,7,'logout','User logged out','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 06:55:36'),(175,4,'login','User logged in successfully','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36','2025-02-16 06:55:40');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_collections`
--

DROP TABLE IF EXISTS `asset_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `collection_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','check','bank_transfer','gcash') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_collection_date` (`collection_date`),
  KEY `idx_asset_date` (`asset_id`,`collection_date`),
  CONSTRAINT `asset_collections_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `asset_collections_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `asset_collections_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_collections`
--

LOCK TABLES `asset_collections` WRITE;
/*!40000 ALTER TABLE `asset_collections` DISABLE KEYS */;
INSERT INTO `asset_collections` VALUES (6,3,'2025-02-13',5000.00,'cash','','0','2025-02-13 05:36:25','2025-02-13 05:36:25',4,NULL),(7,3,'2025-02-13',5000.00,'cash','','','2025-02-13 05:44:20','2025-02-13 05:44:20',4,NULL);
/*!40000 ALTER TABLE `asset_collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_expenses`
--

DROP TABLE IF EXISTS `asset_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','check','bank_transfer','gcash') NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','paid','overdue') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `asset_expenses_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `asset_expenses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `asset_expenses_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_expenses`
--

LOCK TABLES `asset_expenses` WRITE;
/*!40000 ALTER TABLE `asset_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text NOT NULL,
  `expected_amount` decimal(10,2) NOT NULL,
  `collection_frequency` enum('daily','weekly','monthly','quarterly','annually') NOT NULL DEFAULT 'monthly',
  `next_collection_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_status` (`status`),
  KEY `idx_next_collection` (`next_collection_date`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (3,'Vendo 1','','Malita',5000.00,'monthly','2025-03-13','','active','2025-02-11 13:51:38','2025-02-13 05:28:31',NULL,NULL);
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half-day') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `attendance_type` enum('regular','overtime','holiday') DEFAULT 'regular',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`employee_id`,`date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_logs`
--

DROP TABLE IF EXISTS `backup_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `size` bigint(20) DEFAULT NULL,
  `type` enum('full','partial') DEFAULT 'full',
  `status` enum('success','failed') DEFAULT 'success',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `backup_logs_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_logs`
--

LOCK TABLES `backup_logs` WRITE;
/*!40000 ALTER TABLE `backup_logs` DISABLE KEYS */;
INSERT INTO `backup_logs` VALUES (1,'backup_2025-02-13_18-40-44.sql',71812,'full','success',4,'2025-02-13 10:40:45'),(2,'backup_2025-02-14_19-27-36.sql',95514,'full','success',4,'2025-02-14 11:27:37');
/*!40000 ALTER TABLE `backup_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bandwidth_logs`
--

DROP TABLE IF EXISTS `bandwidth_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bandwidth_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `download_speed` decimal(10,2) DEFAULT NULL,
  `upload_speed` decimal(10,2) DEFAULT NULL,
  `latency` int(11) DEFAULT NULL,
  `packet_loss` decimal(5,2) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_timestamp` (`timestamp`),
  CONSTRAINT `bandwidth_logs_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bandwidth_logs`
--

LOCK TABLES `bandwidth_logs` WRITE;
/*!40000 ALTER TABLE `bandwidth_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `bandwidth_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billing`
--

DROP TABLE IF EXISTS `billing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `invoiceid` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('unpaid','paid','partial','overdue','cancelled','pending') DEFAULT 'unpaid',
  `due_date` date NOT NULL,
  `billtocustomer` varchar(255) DEFAULT NULL,
  `billingaddress` text DEFAULT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `companyname` varchar(255) DEFAULT NULL,
  `companyaddress` text DEFAULT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `late_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoiceid` (`invoiceid`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_created_at` (`created_at`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `billing_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`amount` >= 0),
  CONSTRAINT `CONSTRAINT_2` CHECK (`discount` >= 0),
  CONSTRAINT `CONSTRAINT_3` CHECK (`balance` >= 0),
  CONSTRAINT `CONSTRAINT_4` CHECK (`late_fee` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billing`
--

LOCK TABLES `billing` WRITE;
/*!40000 ALTER TABLE `billing` DISABLE KEYS */;
INSERT INTO `billing` VALUES (31,4,'INV-20250212-1321',1000.00,'unpaid','2025-03-12','rona buan','asdasdas 12123',0.00,'','',0.00,0.00,'2025-02-12 05:59:04','2025-02-12 07:58:19',NULL);
/*!40000 ALTER TABLE `billing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `billingitems`
--

DROP TABLE IF EXISTS `billingitems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `billingitems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `billingid` int(11) NOT NULL,
  `itemdescription` text DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `totalprice` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `billingid` (`billingid`),
  CONSTRAINT `billingitems_ibfk_1` FOREIGN KEY (`billingid`) REFERENCES `billing` (`id`) ON DELETE CASCADE,
  CONSTRAINT `CONSTRAINT_1` CHECK (`amount` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `billingitems`
--

LOCK TABLES `billingitems` WRITE;
/*!40000 ALTER TABLE `billingitems` DISABLE KEYS */;
INSERT INTO `billingitems` VALUES (30,31,'Basic plan - monthly internet service',1,1000.00,1000.00,0.00,'2025-02-12 05:59:04');
/*!40000 ALTER TABLE `billingitems` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_phone` varchar(50) DEFAULT NULL,
  `company_email` varchar(255) DEFAULT NULL,
  `company_website` varchar(255) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'PHP',
  `logo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;
/*!40000 ALTER TABLE `company` DISABLE KEYS */;
INSERT INTO `company` VALUES (40,'company_profile','updated','Company profile settings','FRGS IT Solutions','Maruya Ext. Poblacion, Malita Davao Occidental','09055571328','mongosera@gmail.com','https://regz.shop',0.00,'PHP',NULL,'2025-02-10 04:44:16','2025-02-11 15:05:08');
/*!40000 ALTER TABLE `company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `customer_code` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `coordinates` point DEFAULT NULL,
  `service_area_id` int(11) DEFAULT NULL,
  `installation_fee` decimal(10,2) DEFAULT 0.00,
  `installation_notes` text DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `credit_balance` decimal(10,2) DEFAULT 0.00,
  `outstanding_balance` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  KEY `idx_customer_code` (`customer_code`),
  KEY `idx_status` (`status`),
  KEY `idx_plan` (`plan_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `user_id` (`user_id`),
  KEY `service_area_id` (`service_area_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_ibfk_3` FOREIGN KEY (`service_area_id`) REFERENCES `service_areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (4,5,'ACC0001','rona buan','Address here','contact person1','11111111111','customer@admin.com',2,'2025-02-01','2025-03-14','active',NULL,NULL,0.00,'0',0.00,'2025-02-09 16:00:00','2025-02-16 06:39:01',0.00,1000.00);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deduction_history`
--

DROP TABLE IF EXISTS `deduction_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deduction_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `deduction_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `deduction_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `deduction_type_id` (`deduction_type_id`),
  CONSTRAINT `deduction_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `deduction_history_ibfk_2` FOREIGN KEY (`deduction_type_id`) REFERENCES `deduction_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deduction_history`
--

LOCK TABLES `deduction_history` WRITE;
/*!40000 ALTER TABLE `deduction_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `deduction_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deduction_transactions`
--

DROP TABLE IF EXISTS `deduction_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deduction_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deduction_id` int(11) NOT NULL,
  `payroll_item_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `deduction_id` (`deduction_id`),
  KEY `payroll_item_id` (`payroll_item_id`),
  CONSTRAINT `deduction_transactions_ibfk_1` FOREIGN KEY (`deduction_id`) REFERENCES `employee_deductions` (`id`),
  CONSTRAINT `deduction_transactions_ibfk_2` FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deduction_transactions`
--

LOCK TABLES `deduction_transactions` WRITE;
/*!40000 ALTER TABLE `deduction_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `deduction_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deduction_types`
--

DROP TABLE IF EXISTS `deduction_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deduction_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('government','loan','other') DEFAULT 'other',
  `amount_type` enum('fixed','percentage') DEFAULT 'fixed',
  `default_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deduction_types`
--

LOCK TABLES `deduction_types` WRITE;
/*!40000 ALTER TABLE `deduction_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `deduction_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_deductions`
--

DROP TABLE IF EXISTS `employee_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `deduction_type_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `deduction_type_id` (`deduction_type_id`),
  CONSTRAINT `employee_deductions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `employee_deductions_ibfk_2` FOREIGN KEY (`deduction_type_id`) REFERENCES `deduction_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_deductions`
--

LOCK TABLES `employee_deductions` WRITE;
/*!40000 ALTER TABLE `employee_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `employee_code` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `position` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `hire_date` date NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `allowance` decimal(10,2) DEFAULT 0.00,
  `daily_rate` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `sss_no` varchar(20) DEFAULT NULL,
  `philhealth_no` varchar(20) DEFAULT NULL,
  `pagibig_no` varchar(20) DEFAULT NULL,
  `tin_no` varchar(20) DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  UNIQUE KEY `email` (`email`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (2,7,'EMP2025-0001','fernan','formentera','admin2@admin.com','','Poblacion Malita Davao Occidental','Technician','IT','2024-02-13',18000.00,NULL,642.86,'active','','','','',NULL,NULL,'2025-02-13 13:04:04');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (86,'Utilities','Utility bills and services',1,'2025-02-12 05:11:22','2025-02-12 05:11:22'),(87,'Rent','Rental and lease payments',1,'2025-02-12 05:11:22','2025-02-12 05:11:22'),(88,'Salaries','Employee salaries and wages',1,'2025-02-12 05:11:22','2025-02-12 05:11:22'),(89,'Equipment','Equipment purchases and rentals',1,'2025-02-12 05:11:22','2025-02-12 05:11:22'),(90,'Maintenance','Maintenance and repairs',1,'2025-02-12 05:11:22','2025-02-12 05:11:22'),(91,'Marketing','Marketing and advertising',1,'2025-02-12 05:11:22','2025-02-12 05:11:22'),(92,'Office Supplies','Office materials and supplies',1,'2025-02-12 05:11:22','2025-02-12 05:11:22'),(93,'Others','Other miscellaneous expenses',1,'2025-02-12 05:11:22','2025-02-12 05:11:22'),(110,'Utilities','Utility bills and services',1,'2025-02-14 00:46:54','2025-02-14 00:46:54'),(111,'Rent','Rental and lease payments',1,'2025-02-14 00:46:54','2025-02-14 00:46:54'),(112,'Salaries','Employee salaries and wages',1,'2025-02-14 00:46:54','2025-02-14 00:46:54'),(113,'Equipment','Equipment purchases and rentals',1,'2025-02-14 00:46:54','2025-02-14 00:46:54'),(114,'Maintenance','Maintenance and repairs',1,'2025-02-14 00:46:54','2025-02-14 00:46:54'),(115,'Marketing','Marketing and advertising',1,'2025-02-14 00:46:54','2025-02-14 00:46:54'),(116,'Office Supplies','Office materials and supplies',1,'2025-02-14 00:46:54','2025-02-14 00:46:54'),(117,'Others','Other miscellaneous expenses',1,'2025-02-14 00:46:54','2025-02-14 00:46:54'),(118,'Utilities','Utility bills and services',1,'2025-02-14 00:53:52','2025-02-14 00:53:52'),(119,'Rent','Rental and lease payments',1,'2025-02-14 00:53:52','2025-02-14 00:53:52'),(120,'Salaries','Employee salaries and wages',1,'2025-02-14 00:53:52','2025-02-14 00:53:52'),(121,'Equipment','Equipment purchases and rentals',1,'2025-02-14 00:53:52','2025-02-14 00:53:52'),(122,'Maintenance','Maintenance and repairs',1,'2025-02-14 00:53:52','2025-02-14 00:53:52'),(123,'Marketing','Marketing and advertising',1,'2025-02-14 00:53:52','2025-02-14 00:53:52'),(124,'Office Supplies','Office materials and supplies',1,'2025-02-14 00:53:52','2025-02-14 00:53:52'),(125,'Others','Other miscellaneous expenses',1,'2025-02-14 00:53:52','2025-02-14 00:53:52'),(126,'Utilities','Utility bills and services',1,'2025-02-14 01:00:42','2025-02-14 01:00:42'),(127,'Rent','Rental and lease payments',1,'2025-02-14 01:00:42','2025-02-14 01:00:42'),(128,'Salaries','Employee salaries and wages',1,'2025-02-14 01:00:42','2025-02-14 01:00:42'),(129,'Equipment','Equipment purchases and rentals',1,'2025-02-14 01:00:42','2025-02-14 01:00:42'),(130,'Maintenance','Maintenance and repairs',1,'2025-02-14 01:00:42','2025-02-14 01:00:42'),(131,'Marketing','Marketing and advertising',1,'2025-02-14 01:00:42','2025-02-14 01:00:42'),(132,'Office Supplies','Office materials and supplies',1,'2025-02-14 01:00:42','2025-02-14 01:00:42'),(133,'Others','Other miscellaneous expenses',1,'2025-02-14 01:00:42','2025-02-14 01:00:42');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `receipt_image` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `approved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `expenses_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `sick_leave` decimal(5,2) DEFAULT 0.00,
  `vacation_leave` decimal(5,2) DEFAULT 0.00,
  `emergency_leave` decimal(5,2) DEFAULT 0.00,
  `maternity_leave` decimal(5,2) DEFAULT 0.00,
  `paternity_leave` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_balance` (`employee_id`,`year`),
  CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES (1,2,2025,5.00,5.00,5.00,0.00,0.00,'2025-02-13 13:59:05','2025-02-13 15:41:35');
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `paid` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (31,'Vacation Leave','Annual vacation leave',1,'2025-02-10 04:44:16'),(32,'Sick Leave','Medical leave',1,'2025-02-10 04:44:16'),(33,'Emergency Leave','Urgent personal matters',1,'2025-02-10 04:44:16'),(34,'Maternity Leave','Pregnancy related leave',1,'2025-02-10 04:44:16'),(35,'Paternity Leave','New father leave',1,'2025-02-10 04:44:16'),(86,'Vacation Leave','Annual vacation leave',1,'2025-02-14 00:46:54'),(87,'Sick Leave','Medical leave',1,'2025-02-14 00:46:54'),(88,'Emergency Leave','Urgent personal matters',1,'2025-02-14 00:46:54'),(89,'Maternity Leave','Pregnancy related leave',1,'2025-02-14 00:46:54'),(90,'Paternity Leave','New father leave',1,'2025-02-14 00:46:54'),(91,'Vacation Leave','Annual vacation leave',1,'2025-02-14 00:53:52'),(92,'Sick Leave','Medical leave',1,'2025-02-14 00:53:52'),(93,'Emergency Leave','Urgent personal matters',1,'2025-02-14 00:53:52'),(94,'Maternity Leave','Pregnancy related leave',1,'2025-02-14 00:53:52'),(95,'Paternity Leave','New father leave',1,'2025-02-14 00:53:52'),(96,'Vacation Leave','Annual vacation leave',1,'2025-02-14 01:00:42'),(97,'Sick Leave','Medical leave',1,'2025-02-14 01:00:42'),(98,'Emergency Leave','Urgent personal matters',1,'2025-02-14 01:00:42'),(99,'Maternity Leave','Pregnancy related leave',1,'2025-02-14 01:00:42'),(100,'Paternity Leave','New father leave',1,'2025-02-14 01:00:42');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaves`
--

DROP TABLE IF EXISTS `leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `leave_type_id` (`leave_type_id`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_leaves_employee` (`employee_id`),
  KEY `idx_leaves_dates` (`start_date`,`end_date`),
  KEY `idx_leaves_status` (`status`),
  CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `leaves_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`),
  CONSTRAINT `leaves_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leaves`
--

LOCK TABLES `leaves` WRITE;
/*!40000 ALTER TABLE `leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `leaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
INSERT INTO `payment_methods` VALUES (31,'Cash','Direct cash payment','active','2025-02-10 04:44:16'),(32,'Bank Transfer','Bank transfer payment','active','2025-02-10 04:44:16'),(33,'Credit Card','Credit card payment','active','2025-02-10 04:44:16'),(34,'GCash','GCash mobile payment','active','2025-02-10 04:44:16'),(35,'Maya','Maya digital payment','active','2025-02-10 04:44:16'),(46,'Cash','Direct cash payment','active','2025-02-12 04:51:46'),(47,'Bank Transfer','Bank transfer payment','active','2025-02-12 04:51:46'),(48,'Credit Card','Credit card payment','active','2025-02-12 04:51:46'),(49,'GCash','GCash mobile payment','active','2025-02-12 04:51:46'),(50,'Maya','Maya digital payment','active','2025-02-12 04:51:46'),(51,'Cash','Direct cash payment','active','2025-02-12 04:55:52'),(52,'Bank Transfer','Bank transfer payment','active','2025-02-12 04:55:52'),(53,'Credit Card','Credit card payment','active','2025-02-12 04:55:52'),(54,'GCash','GCash mobile payment','active','2025-02-12 04:55:52'),(55,'Maya','Maya digital payment','active','2025-02-12 04:55:52'),(56,'Cash','Direct cash payment','active','2025-02-12 04:58:42'),(57,'Bank Transfer','Bank transfer payment','active','2025-02-12 04:58:42'),(58,'Credit Card','Credit card payment','active','2025-02-12 04:58:42'),(59,'GCash','GCash mobile payment','active','2025-02-12 04:58:42'),(60,'Maya','Maya digital payment','active','2025-02-12 04:58:42'),(61,'Cash','Direct cash payment','active','2025-02-12 05:01:44'),(62,'Bank Transfer','Bank transfer payment','active','2025-02-12 05:01:44'),(63,'Credit Card','Credit card payment','active','2025-02-12 05:01:44'),(64,'GCash','GCash mobile payment','active','2025-02-12 05:01:44'),(65,'Maya','Maya digital payment','active','2025-02-12 05:01:44'),(66,'Cash','Direct cash payment','active','2025-02-12 05:04:06'),(67,'Bank Transfer','Bank transfer payment','active','2025-02-12 05:04:06'),(68,'Credit Card','Credit card payment','active','2025-02-12 05:04:06'),(69,'GCash','GCash mobile payment','active','2025-02-12 05:04:06'),(70,'Maya','Maya digital payment','active','2025-02-12 05:04:06'),(71,'Cash','Direct cash payment','active','2025-02-12 05:11:22'),(72,'Bank Transfer','Bank transfer payment','active','2025-02-12 05:11:22'),(73,'Credit Card','Credit card payment','active','2025-02-12 05:11:22'),(74,'GCash','GCash mobile payment','active','2025-02-12 05:11:22'),(75,'Maya','Maya digital payment','active','2025-02-12 05:11:22'),(76,'Cash','Direct cash payment','active','2025-02-12 05:39:07'),(77,'Bank Transfer','Bank transfer payment','active','2025-02-12 05:39:07'),(78,'Credit Card','Credit card payment','active','2025-02-12 05:39:07'),(79,'GCash','GCash mobile payment','active','2025-02-12 05:39:07'),(80,'Maya','Maya digital payment','active','2025-02-12 05:39:07'),(81,'Cash','Direct cash payment','active','2025-02-12 05:51:43'),(82,'Bank Transfer','Bank transfer payment','active','2025-02-12 05:51:43'),(83,'Credit Card','Credit card payment','active','2025-02-12 05:51:43'),(84,'GCash','GCash mobile payment','active','2025-02-12 05:51:43'),(85,'Maya','Maya digital payment','active','2025-02-12 05:51:43'),(86,'Cash','Direct cash payment','active','2025-02-14 00:46:54'),(87,'Bank Transfer','Bank transfer payment','active','2025-02-14 00:46:54'),(88,'Credit Card','Credit card payment','active','2025-02-14 00:46:54'),(89,'GCash','GCash mobile payment','active','2025-02-14 00:46:54'),(90,'Maya','Maya digital payment','active','2025-02-14 00:46:54'),(91,'Cash','Direct cash payment','active','2025-02-14 00:53:52'),(92,'Bank Transfer','Bank transfer payment','active','2025-02-14 00:53:52'),(93,'Credit Card','Credit card payment','active','2025-02-14 00:53:52'),(94,'GCash','GCash mobile payment','active','2025-02-14 00:53:52'),(95,'Maya','Maya digital payment','active','2025-02-14 00:53:52'),(96,'Cash','Direct cash payment','active','2025-02-14 01:00:42'),(97,'Bank Transfer','Bank transfer payment','active','2025-02-14 01:00:42'),(98,'Credit Card','Credit card payment','active','2025-02-14 01:00:42'),(99,'GCash','GCash mobile payment','active','2025-02-14 01:00:42'),(100,'Maya','Maya digital payment','active','2025-02-14 01:00:42');
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `billing_id` int(11) NOT NULL,
  `payment_method_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed','failed') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `payment_method_id` (`payment_method_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_billing` (`billing_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`billing_id`) REFERENCES `billing` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`),
  CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `basic_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','approved','paid','cancelled') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_items`
--

DROP TABLE IF EXISTS `payroll_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_period_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `deduction_details` text DEFAULT NULL,
  `allowance` decimal(10,2) DEFAULT 0.00,
  `overtime_hours` decimal(5,2) DEFAULT 0.00,
  `overtime_amount` decimal(10,2) DEFAULT 0.00,
  `late_hours` decimal(5,2) DEFAULT 0.00,
  `late_deduction` decimal(10,2) DEFAULT 0.00,
  `absences` int(11) DEFAULT 0,
  `absence_deduction` decimal(10,2) DEFAULT 0.00,
  `sss_contribution` decimal(10,2) DEFAULT 0.00,
  `philhealth_contribution` decimal(10,2) DEFAULT 0.00,
  `pagibig_contribution` decimal(10,2) DEFAULT 0.00,
  `tax_contribution` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `gross_salary` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','approved','paid') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `payroll_period_id` (`payroll_period_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `payroll_items_ibfk_1` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`),
  CONSTRAINT `payroll_items_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_items`
--

LOCK TABLES `payroll_items` WRITE;
/*!40000 ALTER TABLE `payroll_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `pay_date` date NOT NULL,
  `status` enum('draft','processing','approved','paid') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `payroll_periods_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payroll_periods_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_periods`
--

LOCK TABLES `payroll_periods` WRITE;
/*!40000 ALTER TABLE `payroll_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('menu','action') NOT NULL DEFAULT 'action',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'view_dashboard','Can view dashboard','menu','2025-02-14 01:00:42'),(2,'manage_dashboard','Can customize dashboard widgets','action','2025-02-14 01:00:42'),(3,'view_users','Can view user list','menu','2025-02-14 01:00:42'),(4,'add_user','Can add new users','action','2025-02-14 01:00:42'),(5,'edit_user','Can edit users','action','2025-02-14 01:00:42'),(6,'delete_user','Can delete users','action','2025-02-14 01:00:42'),(7,'change_user_status','Can activate/deactivate users','action','2025-02-14 01:00:42'),(8,'view_roles','Can view roles list','menu','2025-02-14 01:00:42'),(9,'add_role','Can add new roles','action','2025-02-14 01:00:42'),(10,'edit_role','Can edit roles','action','2025-02-14 01:00:42'),(11,'delete_role','Can delete roles','action','2025-02-14 01:00:42'),(12,'assign_permissions','Can assign permissions to roles','action','2025-02-14 01:00:42'),(13,'view_leaves','Can view leave list','menu','2025-02-14 01:00:42'),(14,'apply_leave','Can apply for leave','action','2025-02-14 01:00:42'),(15,'approve_leave','Can approve/reject leave requests','action','2025-02-14 01:00:42'),(16,'manage_leave_types','Can manage leave types','action','2025-02-14 01:00:42'),(17,'view_leave_reports','Can view leave reports','action','2025-02-14 01:00:42'),(18,'view_attendance','Can view attendance','menu','2025-02-14 01:00:42'),(19,'mark_attendance','Can mark attendance','action','2025-02-14 01:00:42'),(20,'edit_attendance','Can edit attendance records','action','2025-02-14 01:00:42'),(21,'generate_attendance_report','Can generate attendance reports','action','2025-02-14 01:00:42'),(22,'manage_attendance_settings','Can manage attendance settings','action','2025-02-14 01:00:42'),(23,'view_reports','Can view reports section','menu','2025-02-14 01:00:42'),(24,'generate_reports','Can generate various reports','action','2025-02-14 01:00:42'),(25,'export_reports','Can export reports to different formats','action','2025-02-14 01:00:42'),(26,'schedule_reports','Can schedule automated reports','action','2025-02-14 01:00:42'),(27,'view_settings','Can view settings','menu','2025-02-14 01:00:42'),(28,'manage_settings','Can modify system settings','action','2025-02-14 01:00:42'),(29,'manage_company_profile','Can update company information','action','2025-02-14 01:00:42'),(30,'manage_system_backup','Can perform system backups','action','2025-02-14 01:00:42'),(31,'view_customers','Can view customer list','menu','2025-02-14 01:00:42'),(32,'add_customer','Can add new customers','action','2025-02-14 01:00:42'),(33,'edit_customer','Can edit customers','action','2025-02-14 01:00:42'),(34,'delete_customer','Can delete customers','action','2025-02-14 01:00:42'),(35,'manage_customer_subscriptions','Can manage customer subscriptions','action','2025-02-14 01:00:42'),(36,'view_billing','Can view billing section','menu','2025-02-14 01:00:42'),(37,'create_invoice','Can create new invoices','action','2025-02-14 01:00:42'),(38,'process_payments','Can process customer payments','action','2025-02-14 01:00:42'),(39,'manage_payment_methods','Can manage payment methods','action','2025-02-14 01:00:42'),(40,'view_payment_history','Can view payment history','action','2025-02-14 01:00:42'),(41,'view_services','Can view services section','menu','2025-02-14 01:00:42'),(42,'manage_service_plans','Can manage service plans','action','2025-02-14 01:00:42'),(43,'manage_service_areas','Can manage service areas','action','2025-02-14 01:00:42'),(44,'view_service_status','Can view service status','action','2025-02-14 01:00:42'),(45,'view_support','Can view support section','menu','2025-02-14 01:00:42'),(46,'manage_tickets','Can manage support tickets','action','2025-02-14 01:00:42'),(47,'respond_tickets','Can respond to support tickets','action','2025-02-14 01:00:42'),(48,'close_tickets','Can close support tickets','action','2025-02-14 01:00:42'),(49,'view_inventory','Can view inventory section','menu','2025-02-14 01:00:42'),(50,'manage_products','Can manage products','action','2025-02-14 01:00:42'),(51,'manage_stock','Can manage stock levels','action','2025-02-14 01:00:42'),(52,'view_inventory_reports','Can view inventory reports','action','2025-02-14 01:00:42');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bandwidth` varchar(50) DEFAULT NULL,
  `setup_fee` decimal(10,2) DEFAULT 0.00,
  `contract_duration` int(11) DEFAULT 0,
  `download_speed` varchar(50) DEFAULT NULL,
  `upload_speed` varchar(50) DEFAULT NULL,
  `data_cap` bigint(20) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES (2,'Basic plan','monthly internet service upto 50mbps',1000.00,'50',0.00,0,NULL,NULL,0,'active','2025-02-10 06:20:06','2025-02-16 05:21:31'),(3,'Premium plan','Monthly internet service upto 100mbps',2000.00,'100',0.00,0,NULL,NULL,0,'active','2025-02-16 04:11:41','2025-02-16 05:21:48');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `positions`
--

DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowance` decimal(10,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `positions`
--

LOCK TABLES `positions` WRITE;
/*!40000 ALTER TABLE `positions` DISABLE KEYS */;
/*!40000 ALTER TABLE `positions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `reorder_level` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,'2025-02-14 01:01:47'),(1,2,'2025-02-14 01:01:47'),(1,3,'2025-02-14 01:01:47'),(1,4,'2025-02-14 01:01:47'),(1,5,'2025-02-14 01:01:47'),(1,6,'2025-02-14 01:01:47'),(1,7,'2025-02-14 01:01:47'),(1,8,'2025-02-14 01:01:47'),(1,9,'2025-02-14 01:01:47'),(1,10,'2025-02-14 01:01:47'),(1,11,'2025-02-14 01:01:47'),(1,12,'2025-02-14 01:01:47'),(1,13,'2025-02-14 01:01:47'),(1,14,'2025-02-14 01:01:47'),(1,15,'2025-02-14 01:01:47'),(1,16,'2025-02-14 01:01:47'),(1,17,'2025-02-14 01:01:47'),(1,18,'2025-02-14 01:01:47'),(1,19,'2025-02-14 01:01:47'),(1,20,'2025-02-14 01:01:47'),(1,21,'2025-02-14 01:01:47'),(1,22,'2025-02-14 01:01:47'),(1,23,'2025-02-14 01:01:47'),(1,24,'2025-02-14 01:01:47'),(1,25,'2025-02-14 01:01:47'),(1,26,'2025-02-14 01:01:47'),(1,27,'2025-02-14 01:01:47'),(1,28,'2025-02-14 01:01:47'),(1,29,'2025-02-14 01:01:47'),(1,30,'2025-02-14 01:01:47'),(1,31,'2025-02-14 01:01:47'),(1,32,'2025-02-14 01:01:47'),(1,33,'2025-02-14 01:01:47'),(1,34,'2025-02-14 01:01:47'),(1,35,'2025-02-14 01:01:47'),(1,36,'2025-02-14 01:01:47'),(1,37,'2025-02-14 01:01:47'),(1,38,'2025-02-14 01:01:47'),(1,39,'2025-02-14 01:01:47'),(1,40,'2025-02-14 01:01:47'),(1,41,'2025-02-14 01:01:47'),(1,42,'2025-02-14 01:01:47'),(1,43,'2025-02-14 01:01:47'),(1,44,'2025-02-14 01:01:47'),(1,45,'2025-02-14 01:01:47'),(1,46,'2025-02-14 01:01:47'),(1,47,'2025-02-14 01:01:47'),(1,48,'2025-02-14 01:01:47'),(1,49,'2025-02-14 01:01:47'),(1,50,'2025-02-14 01:01:47'),(1,51,'2025-02-14 01:01:47'),(1,52,'2025-02-14 01:01:47'),(3,14,'2025-02-14 01:03:21'),(3,18,'2025-02-14 01:03:21'),(3,31,'2025-02-14 01:03:21'),(3,32,'2025-02-14 01:03:21'),(3,36,'2025-02-14 01:03:21');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin','Full system access with all privileges','2025-02-13 10:38:45','2025-02-13 13:01:03'),(3,'Staff','General staff access for day-to-day operations','2025-02-13 10:42:58','2025-02-13 13:00:53'),(11,'collector','Access to payment collection and related features','2025-02-13 12:59:56','2025-02-13 12:59:56'),(12,'customer','Limited access for customers to view their own data','2025-02-13 12:59:56','2025-02-13 12:59:56'),(13,'support','Customer support staff','2025-02-14 00:53:52','2025-02-14 00:53:52'),(14,'billing','Billing and payments staff','2025-02-14 00:53:52','2025-02-14 00:53:52');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_history`
--

DROP TABLE IF EXISTS `salary_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `days_in_month` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_salary_history_employee` (`employee_id`),
  KEY `idx_salary_history_date` (`effective_date`),
  CONSTRAINT `salary_history_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `salary_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_history`
--

LOCK TABLES `salary_history` WRITE;
/*!40000 ALTER TABLE `salary_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_report_items`
--

DROP TABLE IF EXISTS `sales_report_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_report_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('completed','pending','cancelled') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_payment` (`payment_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `sales_report_items_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `sales_report_items_ibfk_2` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_report_items`
--

LOCK TABLES `sales_report_items` WRITE;
/*!40000 ALTER TABLE `sales_report_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales_report_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service_areas`
--

DROP TABLE IF EXISTS `service_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service_areas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `coverage_radius` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_areas`
--

LOCK TABLES `service_areas` WRITE;
/*!40000 ALTER TABLE `service_areas` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting` (`category`,`name`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'company','company_name','FRGS IT Solutions','text','Name of your company','2025-02-16 05:12:11','2025-02-16 05:20:17'),(2,'company','company_address','Maruya Ext. Poblacion Malita Davao Occidental','text','Company physical address','2025-02-16 05:12:11','2025-02-16 05:20:17'),(3,'company','company_phone','09055571328','text','Company contact number','2025-02-16 05:12:11','2025-02-16 05:20:17'),(4,'company','company_email','mongosera@gmail.com','text','Company email address','2025-02-16 05:12:11','2025-02-16 05:20:17'),(5,'company','company_website','regzpro.com','text','Company website URL','2025-02-16 05:12:11','2025-02-16 05:20:17'),(6,'company','logo_path','','text','Path to company logo file','2025-02-16 05:12:11','2025-02-16 05:20:17'),(7,'financial','tax_rate','0.00','number','Default tax rate (in percentage)','2025-02-16 05:12:11','2025-02-16 05:20:17'),(8,'financial','currency','PHP','text','Default currency for transactions','2025-02-16 05:12:11','2025-02-16 05:20:17'),(9,'financial','late_fee_percentage','0','number','Late payment fee percentage','2025-02-16 05:12:11','2025-02-16 05:20:17'),(10,'financial','grace_period_days','3','number','Grace period for payments (in days)','2025-02-16 05:12:11','2025-02-16 05:20:17'),(11,'system','enable_email_notifications','0','boolean','Enable/disable email notifications','2025-02-16 05:12:11','2025-02-16 05:20:17'),(12,'system','enable_sms_notifications','0','boolean','Enable/disable SMS notifications','2025-02-16 05:12:11','2025-02-16 05:20:17'),(13,'system','maintenance_mode','0','boolean','Enable/disable maintenance mode','2025-02-16 05:12:11','2025-02-16 05:20:17'),(14,'system','default_pagination','20','number','Default number of items per page','2025-02-16 05:12:11','2025-02-16 05:20:17');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `billing_cycle` enum('monthly','quarterly','annually') DEFAULT 'monthly',
  `status` enum('active','inactive','suspended','cancelled') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `plan_id` (`plan_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES (11,4,2,'2025-02-11','2025-03-11','monthly','active',NULL,1,'2025-02-11 04:37:15','2025-02-11 04:54:04');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` enum('admin','staff','collector','customer') NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `employee_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,'admin','$2y$12$FiWYQvla/lf5keENUo8NGO.dndndE1nyrROO0Y4xcHFhtytDZYBgW','admin@example.com','admin','active','2025-02-16 18:20:45','2025-02-10 04:44:16','2025-02-16 10:20:45',NULL),(5,'ronab','$2y$10$AgZ5eB7M.egFfU28UL72WeHVykbXZ8yadbxhBKRj8kymhy1P14bOu','customer@admin.com','customer','active','2025-02-14 17:17:01','2025-02-10 04:45:53','2025-02-14 10:47:28',NULL),(7,'fformentera','$2y$10$baen7bwwrXDIc1WEY6xdtesHITPmJcMAg18uZyyq3pcWvfdT4VTuu','admin2@admin.com','staff','active','2025-02-16 14:55:23','2025-02-13 13:04:04','2025-02-16 06:55:23',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-02-16 18:55:33
