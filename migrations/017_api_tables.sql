-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: iacc
-- ------------------------------------------------------
-- Server version	5.7.44

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
-- Table structure for table `api_subscriptions`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `api_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `plan` enum('trial','starter','professional','enterprise') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trial',
  `status` enum('active','expired','cancelled','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `orders_limit` int(11) NOT NULL DEFAULT '50',
  `keys_limit` int(11) NOT NULL DEFAULT '1' COMMENT 'Max API keys allowed',
  `channels` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'website' COMMENT 'Comma-separated: website,email,line,facebook,manual',
  `ai_providers` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ollama' COMMENT 'Comma-separated: ollama,openai,claude,gemini',
  `trial_start` date DEFAULT NULL,
  `trial_end` date DEFAULT NULL,
  `started_at` datetime DEFAULT NULL COMMENT 'When paid plan started',
  `expires_at` datetime DEFAULT NULL COMMENT 'When current period expires',
  `enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Super Admin can disable',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_company` (`company_id`),
  KEY `idx_status` (`status`),
  KEY `idx_plan` (`plan`),
  CONSTRAINT `fk_sub_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_keys`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `key_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Default' COMMENT 'Friendly name',
  `api_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Public key sent in X-API-Key header',
  `api_secret` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Secret sent in X-API-Secret header',
  `previous_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_secret` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grace_expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_key` (`api_key`),
  KEY `idx_company` (`company_id`),
  KEY `idx_subscription` (`subscription_id`),
  CONSTRAINT `fk_key_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_key_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `api_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_invoices`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `api_invoices` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan` enum('trial','starter','professional','enterprise') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `orders_limit` int(11) NOT NULL DEFAULT '0',
  `orders_used` int(11) NOT NULL DEFAULT '0',
  `overage_orders` int(11) NOT NULL DEFAULT '0',
  `base_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `overage_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THB',
  `status` enum('issued','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issued',
  `issued_at` datetime DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_company_period` (`company_id`,`period_start`,`period_end`),
  UNIQUE KEY `uniq_invoice_number` (`invoice_number`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_status` (`status`),
  KEY `idx_period_end` (`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_usage_logs`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `api_usage_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `api_key_id` int(11) DEFAULT NULL,
  `endpoint` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. POST /api/v1/bookings',
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'website' COMMENT 'website, email, line, facebook, manual',
  `status_code` int(3) NOT NULL DEFAULT '200',
  `request_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Supports IPv6',
  `request_body` text COLLATE utf8mb4_unicode_ci COMMENT 'Truncated request payload',
  `response_body` text COLLATE utf8mb4_unicode_ci COMMENT 'Truncated response',
  `processing_ms` int(11) DEFAULT NULL COMMENT 'Processing time in milliseconds',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_company_date` (`company_id`,`created_at`),
  KEY `idx_api_key` (`api_key_id`),
  KEY `idx_channel` (`channel`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_webhooks`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `api_webhooks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Used for HMAC-SHA256 signature',
  `events` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'booking.created,booking.completed,booking.failed,booking.cancelled' COMMENT 'CSV of event types',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `failure_count` int(11) NOT NULL DEFAULT '0' COMMENT 'Consecutive failures — auto-disable at 10',
  `last_triggered` datetime DEFAULT NULL,
  `last_status` int(3) DEFAULT NULL COMMENT 'HTTP status of last delivery',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_webhook_company` (`company_id`),
  KEY `idx_webhook_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_webhook_deliveries`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `api_webhook_deliveries` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `event` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_code` int(3) DEFAULT NULL,
  `response_body` text COLLATE utf8mb4_unicode_ci,
  `duration_ms` int(11) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `error` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_webhook` (`webhook_id`),
  KEY `idx_delivery_date` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=173 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `channel_orders`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `channel_orders` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) NOT NULL COMMENT 'The company that owns this API subscription',
  `api_key_id` int(11) DEFAULT NULL,
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'website',
  `status` enum('pending','processing','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `guest_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guest_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `check_in` date DEFAULT NULL,
  `check_out` date DEFAULT NULL,
  `room_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guests` int(11) DEFAULT '1',
  `total_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'THB',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `raw_data` json DEFAULT NULL COMMENT 'Original payload from source',
  `idempotency_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linked_company_id` int(11) DEFAULT NULL COMMENT 'Customer company created/matched',
  `linked_pr_id` int(11) DEFAULT NULL COMMENT 'PR created',
  `linked_po_id` int(11) DEFAULT NULL COMMENT 'PO created',
  `ai_parsed` tinyint(1) NOT NULL DEFAULT '0',
  `ai_provider` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ollama, openai, claude, gemini',
  `ai_confidence` decimal(5,2) DEFAULT NULL COMMENT 'AI confidence score 0-100',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_idempotency` (`company_id`,`idempotency_key`),
  KEY `idx_company_status` (`company_id`,`status`),
  KEY `idx_channel` (`channel`),
  KEY `idx_checkin` (`check_in`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_booking_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-27 18:28:32
