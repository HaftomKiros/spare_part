-- MySQL dump 10.13  Distrib 8.4.10, for Linux (x86_64)
--
-- Host: localhost    Database: ashu_spare_part
-- ------------------------------------------------------
-- Server version	8.4.10-0ubuntu0.26.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `company_settings`
--

DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ETB',
  `currency_symbol` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Br',
  `tax_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company_settings`
--

LOCK TABLES `company_settings` WRITE;
/*!40000 ALTER TABLE `company_settings` DISABLE KEYS */;
INSERT INTO `company_settings` VALUES (1,'Ashu Spare Part','Addis Ababa, Ethiopia','+251-911-000000','info@ashusparepart.et','logos/gCXCLgO70xESaghnlvL9kJ3gKUy0jV1qdSVVEREa.png','ETB','Br',NULL,NULL,'2026-08-23 23:21:11','2026-08-24 05:14:58');
/*!40000 ALTER TABLE `company_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_type` enum('individual','business') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_customer_code_unique` (`customer_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'CUST-001','Walk-in Customer','+000-000-0000',NULL,NULL,'Addis Ababa','individual',0.00,'active',NULL,'2026-08-23 23:21:12','2026-08-23 23:21:12'),(2,'CUST-002','Kebede Tadesse','+251-911-111111',NULL,NULL,'Addis Ababa','individual',0.00,'active',NULL,'2026-08-23 23:21:12','2026-08-23 23:21:12'),(3,'CUST-003','Almaz Transport PLC','+251-111-333444',NULL,NULL,'Addis Ababa','business',0.00,'active',NULL,'2026-08-23 23:21:12','2026-08-23 23:21:12');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `part_categories`
--

DROP TABLE IF EXISTS `part_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `part_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `part_categories_slug_unique` (`slug`),
  KEY `part_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `part_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `part_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `part_categories`
--

LOCK TABLES `part_categories` WRITE;
/*!40000 ALTER TABLE `part_categories` DISABLE KEYS */;
INSERT INTO `part_categories` VALUES (1,NULL,'Engine Parts','engine-parts-1',NULL,'fa-cogs','active','2026-08-23 23:21:12','2026-08-24 21:16:26'),(2,NULL,'Electrical Parts','electrical-parts',NULL,'fa-bolt','active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(3,NULL,'Body Parts','body-parts',NULL,'fa-car','active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(4,NULL,'Brake System','brake-system',NULL,'fa-stop-circle','active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(5,NULL,'Transmission','transmission',NULL,'fa-gears','active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(6,NULL,'Suspension','suspension',NULL,'fa-arrows-v','active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(7,NULL,'Fuel System','fuel-system',NULL,'fa-tint','active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(8,NULL,'Cooling System','cooling-system',NULL,'fa-thermometer','active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(9,NULL,'Lubricants & Oils','lubricants-oils',NULL,'fa-oil-can','active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(10,NULL,'Accessories','accessories',NULL,'fa-star','active','2026-08-23 23:21:12','2026-08-23 23:21:12');
/*!40000 ALTER TABLE `part_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint unsigned NOT NULL,
  `item_type` enum('vehicle','spare_part') COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_model_id` bigint unsigned DEFAULT NULL,
  `spare_part_id` bigint unsigned DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_vehicle_model_id_foreign` (`vehicle_model_id`),
  KEY `purchase_items_spare_part_id_foreign` (`spare_part_id`),
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_spare_part_id_foreign` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_items_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES (3,3,'spare_part',NULL,9,1,120.00,0.00,120.00,'2026-08-24 21:44:32','2026-08-24 21:44:32'),(4,4,'spare_part',NULL,11,1,300.00,0.00,300.00,'2026-08-25 01:52:49','2026-08-25 01:52:49');
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('unpaid','partial','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `status` enum('draft','ordered','received','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ordered',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_purchase_number_unique` (`purchase_number`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_user_id_foreign` (`user_id`),
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES (3,'PO-2026-0001',2,1,'2026-08-25',NULL,120.00,0.00,0.00,120.00,0.00,120.00,'unpaid','received',NULL,'2026-08-24 21:44:32','2026-08-24 21:44:32',3),(4,'PO-2026-0002',3,1,'2026-08-25',NULL,300.00,0.00,0.00,300.00,300.00,0.00,'paid','received',NULL,'2026-08-25 01:52:49','2026-08-25 01:52:49',1);
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Administrator','Full system access','[\"all\"]','2026-08-23 23:21:11','2026-08-23 23:21:11'),(2,'manager','Manager','Manage inventory and reports','[\"catalog\", \"inventory\", \"reports\", \"sales\", \"purchases\"]','2026-08-23 23:21:11','2026-08-23 23:21:11'),(3,'cashier','Cashier','Process sales only','[\"sales.create\", \"sales.view\"]','2026-08-23 23:21:11','2026-08-23 23:21:11'),(4,'storekeeper','Storekeeper','Manage stock and inventory','[\"inventory\", \"catalog.view\"]','2026-08-23 23:21:11','2026-08-23 23:21:11');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `item_type` enum('vehicle','spare_part') COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_model_id` bigint unsigned DEFAULT NULL,
  `spare_part_id` bigint unsigned DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_vehicle_model_id_foreign` (`vehicle_model_id`),
  KEY `sale_items_spare_part_id_foreign` (`spare_part_id`),
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_spare_part_id_foreign` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_items_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES (1,1,'vehicle',1,NULL,1,35000.00,0.00,35000.00,'2026-08-24 17:35:33','2026-08-24 17:35:33'),(2,1,'spare_part',NULL,1,1,400.00,0.00,400.00,'2026-08-24 17:35:33','2026-08-24 17:35:33'),(3,2,'vehicle',6,NULL,1,105000.00,0.00,105000.00,'2026-08-24 21:50:16','2026-08-24 21:50:16'),(4,3,'vehicle',6,NULL,1,105000.00,0.00,105000.00,'2026-08-24 22:09:11','2026-08-24 22:09:11'),(5,4,'spare_part',NULL,1,1,400.00,0.00,400.00,'2026-08-25 00:33:50','2026-08-25 00:33:50'),(6,5,'vehicle',5,NULL,11,95000.00,0.00,1045000.00,'2026-08-25 02:05:18','2026-08-25 02:05:18'),(7,6,'spare_part',NULL,4,1,80.00,0.00,80.00,'2026-08-25 02:09:50','2026-08-25 02:09:50');
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_return_items`
--

DROP TABLE IF EXISTS `sale_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_return_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_return_id` bigint unsigned NOT NULL,
  `item_type` enum('vehicle','spare_part') COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_model_id` bigint unsigned DEFAULT NULL,
  `spare_part_id` bigint unsigned DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_items_sale_return_id_foreign` (`sale_return_id`),
  CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_return_items`
--

LOCK TABLES `sale_return_items` WRITE;
/*!40000 ALTER TABLE `sale_return_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_returns`
--

DROP TABLE IF EXISTS `sale_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_returns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sale_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `return_type` enum('refund','exchange','credit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'refund',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_returns_return_number_unique` (`return_number`),
  KEY `sale_returns_sale_id_foreign` (`sale_id`),
  KEY `sale_returns_customer_id_foreign` (`customer_id`),
  KEY `sale_returns_user_id_foreign` (`user_id`),
  CONSTRAINT `sale_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `sale_returns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_returns`
--

LOCK TABLES `sale_returns` WRITE;
/*!40000 ALTER TABLE `sale_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sale_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `sale_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('cash','bank_transfer','cheque','credit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `payment_status` enum('unpaid','partial','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `status` enum('draft','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_invoice_number_unique` (`invoice_number`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_user_id_foreign` (`user_id`),
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,'INV-2026-0001',3,1,'2026-08-24',35400.00,0.00,0.00,35400.00,35400.00,0.00,'cash','paid','completed','ghjghj','2026-08-24 17:35:33','2026-08-24 17:35:33',NULL),(2,'INV-2026-0002',NULL,1,'2026-08-25',105000.00,0.00,0.00,105000.00,105000.00,0.00,'cash','paid','completed',NULL,'2026-08-24 21:50:16','2026-08-24 21:50:16',3),(3,'INV-2026-0003',NULL,1,'2026-08-25',105000.00,0.00,0.00,105000.00,105000.00,0.00,'cash','paid','completed','gggg','2026-08-24 22:09:11','2026-08-24 22:09:11',1),(4,'INV-2026-0004',NULL,1,'2026-08-25',400.00,0.00,0.00,400.00,0.00,400.00,'cash','unpaid','completed',NULL,'2026-08-25 00:33:50','2026-08-25 00:33:50',2),(5,'INV-2026-0005',NULL,1,'2026-08-25',1045000.00,0.00,0.00,1045000.00,0.00,1045000.00,'cash','unpaid','completed',NULL,'2026-08-25 02:05:18','2026-08-25 02:05:18',3),(6,'INV-2026-0006',NULL,1,'2026-08-25',80.00,0.00,0.00,80.00,0.00,80.00,'cash','unpaid','completed',NULL,'2026-08-25 02:09:50','2026-08-25 02:09:50',2);
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spare_part_vehicle_model`
--

DROP TABLE IF EXISTS `spare_part_vehicle_model`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spare_part_vehicle_model` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `spare_part_id` bigint unsigned NOT NULL,
  `vehicle_model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spare_part_vehicle_model_spare_part_id_foreign` (`spare_part_id`),
  KEY `spare_part_vehicle_model_vehicle_model_id_foreign` (`vehicle_model_id`),
  CONSTRAINT `spare_part_vehicle_model_spare_part_id_foreign` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spare_part_vehicle_model_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spare_part_vehicle_model`
--

LOCK TABLES `spare_part_vehicle_model` WRITE;
/*!40000 ALTER TABLE `spare_part_vehicle_model` DISABLE KEYS */;
INSERT INTO `spare_part_vehicle_model` VALUES (1,11,1),(2,11,4),(3,11,6),(4,11,7),(5,11,2),(6,11,3),(7,11,5);
/*!40000 ALTER TABLE `spare_part_vehicle_model` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spare_parts`
--

DROP TABLE IF EXISTS `spare_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spare_parts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `part_category_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `part_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `oem_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `buying_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reorder_level` int NOT NULL DEFAULT '5',
  `current_stock` int NOT NULL DEFAULT '0',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spare_parts_part_number_unique` (`part_number`),
  KEY `spare_parts_part_category_id_foreign` (`part_category_id`),
  KEY `spare_parts_unit_id_foreign` (`unit_id`),
  CONSTRAINT `spare_parts_part_category_id_foreign` FOREIGN KEY (`part_category_id`) REFERENCES `part_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spare_parts_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spare_parts`
--

LOCK TABLES `spare_parts` WRITE;
/*!40000 ALTER TABLE `spare_parts` DISABLE KEYS */;
INSERT INTO `spare_parts` VALUES (1,1,1,'SP-0001',NULL,'Piston Ring Set (Boxer)',NULL,250.00,400.00,10,23,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-25 00:33:50'),(2,1,1,'SP-0002',NULL,'Engine Oil Filter (Universal)',NULL,80.00,130.00,20,45,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(3,1,2,'SP-0003',NULL,'Gasket Set (Boxer)',NULL,320.00,520.00,8,12,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(4,2,1,'SP-0004',NULL,'Spark Plug (NGK)',NULL,45.00,80.00,30,60,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-25 02:09:50'),(5,2,1,'SP-0005',NULL,'Battery 12V',NULL,650.00,1000.00,5,10,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(6,3,1,'SP-0006',NULL,'Front Mudguard (Boxer)',NULL,180.00,300.00,5,12,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-24 22:10:22'),(7,4,7,'SP-0007',NULL,'Brake Shoe (Rear)',NULL,95.00,160.00,15,30,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(8,4,7,'SP-0008',NULL,'Brake Pad (Front Disc)',NULL,150.00,250.00,10,20,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(9,9,4,'SP-0009',NULL,'Engine Oil 4T (1L)',NULL,120.00,200.00,50,101,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-24 21:44:32'),(10,5,1,'SP-0010',NULL,'Chain Sprocket Set',NULL,280.00,480.00,8,15,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(11,3,6,'SP-0011','7878','haftom','kjkj',250.00,600.00,5,1,'sjsk',NULL,'active','2026-08-25 01:50:05','2026-08-25 01:52:49');
/*!40000 ALTER TABLE `spare_parts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustment_items`
--

DROP TABLE IF EXISTS `stock_adjustment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` bigint unsigned NOT NULL,
  `item_type` enum('vehicle','spare_part') COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_model_id` bigint unsigned DEFAULT NULL,
  `spare_part_id` bigint unsigned DEFAULT NULL,
  `quantity_before` int NOT NULL,
  `quantity_adjusted` int NOT NULL,
  `quantity_after` int NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustment_items`
--

LOCK TABLES `stock_adjustment_items` WRITE;
/*!40000 ALTER TABLE `stock_adjustment_items` DISABLE KEYS */;
INSERT INTO `stock_adjustment_items` VALUES (2,2,'vehicle',3,NULL,4,2,6,NULL,'2026-08-24 23:09:54','2026-08-24 23:09:54');
/*!40000 ALTER TABLE `stock_adjustment_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `adjustment_date` date NOT NULL,
  `adjustment_type` enum('increase','decrease','recount') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_adjustments_adjustment_number_unique` (`adjustment_number`),
  KEY `stock_adjustments_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_adjustments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
INSERT INTO `stock_adjustments` VALUES (2,'ADJ-2026-0001',1,'2026-08-25','increase','fdfdfdf','approved','2026-08-24 23:09:54','2026-08-24 23:09:54',2);
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `item_type` enum('vehicle','spare_part') COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_model_id` bigint unsigned DEFAULT NULL,
  `spare_part_id` bigint unsigned DEFAULT NULL,
  `movement_type` enum('purchase','sale','return_in','return_out','adjustment_in','adjustment_out','opening') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `quantity_before` int NOT NULL,
  `quantity_after` int NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `warehouse_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,'spare_part',NULL,6,'adjustment_in',1,8,9,25.00,NULL,NULL,1,'sdsd','2026-08-24 17:34:01','2026-08-24 17:34:01',NULL),(2,'vehicle',1,NULL,'sale',1,5,4,35000.00,'App\\Models\\Sale',1,1,'Sale #INV-2026-0001','2026-08-24 17:35:33','2026-08-24 17:35:33',NULL),(3,'spare_part',NULL,1,'sale',1,25,24,400.00,'App\\Models\\Sale',1,1,'Sale #INV-2026-0001','2026-08-24 17:35:33','2026-08-24 17:35:33',NULL),(4,'spare_part',NULL,6,'adjustment_in',1,0,1,0.00,NULL,NULL,1,NULL,'2026-08-24 21:42:58','2026-08-24 21:42:58',NULL),(5,'spare_part',NULL,9,'purchase',1,0,1,120.00,'App\\Models\\Purchase',3,1,'Purchase #PO-2026-0001','2026-08-24 21:44:32','2026-08-24 21:44:32',NULL),(6,'vehicle',6,NULL,'sale',1,0,0,105000.00,'App\\Models\\Sale',2,1,'Sale #INV-2026-0002','2026-08-24 21:50:16','2026-08-24 21:50:16',NULL),(7,'spare_part',NULL,6,'adjustment_in',1,1,2,0.00,NULL,NULL,1,NULL,'2026-08-24 22:05:22','2026-08-24 22:05:22',NULL),(8,'vehicle',6,NULL,'sale',1,0,0,105000.00,'App\\Models\\Sale',3,1,'Sale #INV-2026-0003','2026-08-24 22:09:11','2026-08-24 22:09:11',NULL),(9,'spare_part',NULL,6,'adjustment_in',1,2,3,0.00,NULL,NULL,1,NULL,'2026-08-24 22:10:22','2026-08-24 22:10:22',NULL),(10,'spare_part',NULL,4,'adjustment_in',1,0,1,0.00,NULL,NULL,1,NULL,'2026-08-24 22:22:48','2026-08-24 22:22:48',NULL),(11,'vehicle',5,NULL,'adjustment_in',1,0,1,0.00,NULL,NULL,1,NULL,'2026-08-24 22:53:41','2026-08-24 22:53:41',3),(12,'vehicle',3,NULL,'adjustment_in',2,0,2,0.00,'App\\Models\\StockAdjustment',2,1,'fdfdfdf','2026-08-24 23:09:54','2026-08-24 23:09:54',2),(13,'spare_part',NULL,4,'adjustment_out',1,1,0,0.00,NULL,NULL,1,'Stock transfer — jhj','2026-08-24 23:30:11','2026-08-24 23:30:11',1),(14,'spare_part',NULL,4,'adjustment_in',1,0,1,0.00,NULL,NULL,1,'Stock transfer — jhj','2026-08-24 23:30:11','2026-08-24 23:30:11',2),(15,'spare_part',NULL,1,'sale',1,0,0,400.00,'App\\Models\\Sale',4,1,'Sale #INV-2026-0004','2026-08-25 00:33:50','2026-08-25 00:33:50',2),(16,'spare_part',NULL,11,'purchase',1,0,1,300.00,'App\\Models\\Purchase',4,1,'Purchase #PO-2026-0002','2026-08-25 01:52:49','2026-08-25 01:52:49',1),(17,'vehicle',5,NULL,'sale',11,1,0,95000.00,'App\\Models\\Sale',5,1,'Sale #INV-2026-0005','2026-08-25 02:05:18','2026-08-25 02:05:18',3),(18,'spare_part',NULL,4,'sale',1,1,0,80.00,'App\\Models\\Sale',6,1,'Sale #INV-2026-0006','2026-08-25 02:09:50','2026-08-25 02:09:50',2);
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_supplier_code_unique` (`supplier_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'SUP-001','Ashu Spare Part Supplier','Bajaj Auto Limited','+91-20-12345678','supply@bajaj.com',NULL,'Addis Ababa',NULL,0.00,'active',NULL,'2026-08-23 23:21:12','2026-08-23 23:21:12'),(2,'SUP-002','Auto Parts Wholesale','Auto Parts Wholesale PLC','+251-111-222333','info@autoparts.et',NULL,'Addis Ababa',NULL,120.00,'active',NULL,'2026-08-23 23:21:12','2026-08-24 21:44:32'),(3,'SUP-003','Mekele Spare Parts','Mekele Spare Parts Shop','+251-344-555666','mekele@spareparts.et',NULL,'Mekele',NULL,0.00,'active',NULL,'2026-08-23 23:21:12','2026-08-23 23:21:12');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `units`
--

DROP TABLE IF EXISTS `units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abbreviation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `units`
--

LOCK TABLES `units` WRITE;
/*!40000 ALTER TABLE `units` DISABLE KEYS */;
INSERT INTO `units` VALUES (1,'Piece','Pcs','Individual unit','2026-08-23 23:21:12','2026-08-23 23:21:12'),(2,'Set','Set','Set of related parts','2026-08-23 23:21:12','2026-08-23 23:21:12'),(3,'Box','Box','Box of parts','2026-08-23 23:21:12','2026-08-23 23:21:12'),(4,'Litre','L','Liquid measurement','2026-08-23 23:21:12','2026-08-23 23:21:12'),(5,'Metre','M','Length measurement','2026-08-23 23:21:12','2026-08-23 23:21:12'),(6,'Kilogram','Kg','Weight measurement','2026-08-23 23:21:12','2026-08-23 23:21:12'),(7,'Pair','Pr','Pair of items','2026-08-23 23:21:12','2026-08-23 23:21:12');
/*!40000 ALTER TABLE `units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'System Admin','admin@ashusparepart.et','+251-911-000000','$2y$12$uVePDWvDwHulgiOcSYOLce7l8jsh4oMeayVR1dGeyzBiNbEUxbsyy',NULL,'active','RRIqQp2Qkq1wRbhyu2ekwuT4gYiqoBz3pyiJkY7gQ7bn2VizBxoTD2COCkj9','2026-08-23 23:21:12','2026-08-23 23:22:00');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_models`
--

DROP TABLE IF EXISTS `vehicle_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_models` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_type_id` bigint unsigned NOT NULL,
  `brand` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bajaj',
  `model_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` year DEFAULT NULL,
  `engine_cc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `selling_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `buying_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_models_vehicle_type_id_foreign` (`vehicle_type_id`),
  CONSTRAINT `vehicle_models_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_models`
--

LOCK TABLES `vehicle_models` WRITE;
/*!40000 ALTER TABLE `vehicle_models` DISABLE KEYS */;
INSERT INTO `vehicle_models` VALUES (1,1,'Bajaj','Boxer','BX100',NULL,'100cc',35000.00,30000.00,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(2,1,'Bajaj','Pulsar 135','P135',NULL,'135cc',55000.00,48000.00,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(3,1,'Bajaj','Pulsar 150','P150',NULL,'150cc',62000.00,54000.00,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(4,1,'Bajaj','Discover 125','D125',NULL,'125cc',45000.00,39000.00,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(5,2,'Bajaj','RE','RE200',NULL,'200cc',95000.00,82000.00,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(6,2,'Bajaj','Maxima','MAX200',NULL,'200cc',105000.00,90000.00,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(7,2,'Bajaj','Maxima Cargo','MAXC',NULL,'200cc',110000.00,95000.00,NULL,NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12');
/*!40000 ALTER TABLE `vehicle_models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_stocks`
--

DROP TABLE IF EXISTS `vehicle_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_model_id` bigint unsigned NOT NULL,
  `current_stock` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_stocks_vehicle_model_id_foreign` (`vehicle_model_id`),
  CONSTRAINT `vehicle_stocks_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_stocks`
--

LOCK TABLES `vehicle_stocks` WRITE;
/*!40000 ALTER TABLE `vehicle_stocks` DISABLE KEYS */;
INSERT INTO `vehicle_stocks` VALUES (1,1,4,2,'2026-08-23 23:21:12','2026-08-24 17:35:33'),(2,2,3,2,'2026-08-23 23:21:12','2026-08-23 23:21:12'),(3,3,6,2,'2026-08-23 23:21:12','2026-08-24 23:09:54'),(4,4,6,2,'2026-08-23 23:21:12','2026-08-23 23:21:12'),(5,5,0,3,'2026-08-23 23:21:12','2026-08-25 02:05:18'),(6,6,3,3,'2026-08-23 23:21:12','2026-08-24 22:09:11'),(7,7,4,3,'2026-08-23 23:21:12','2026-08-23 23:21:12');
/*!40000 ALTER TABLE `vehicle_stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicle_types`
--

DROP TABLE IF EXISTS `vehicle_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wheel_count` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_types_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicle_types`
--

LOCK TABLES `vehicle_types` WRITE;
/*!40000 ALTER TABLE `vehicle_types` DISABLE KEYS */;
INSERT INTO `vehicle_types` VALUES (1,'Two Wheeler','two-wheeler',2,'Motorcycles and scooters',NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12'),(2,'Three Wheeler','three-wheeler',3,'Auto-rickshaws and tricycles',NULL,'active','2026-08-23 23:21:12','2026-08-23 23:21:12');
/*!40000 ALTER TABLE `vehicle_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_spare_part_stock`
--

DROP TABLE IF EXISTS `warehouse_spare_part_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_spare_part_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint unsigned NOT NULL,
  `spare_part_id` bigint unsigned NOT NULL,
  `current_stock` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '5',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wh_part_unique` (`warehouse_id`,`spare_part_id`),
  KEY `wh_part_sp_fk` (`spare_part_id`),
  CONSTRAINT `wh_part_sp_fk` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wh_part_wh_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_spare_part_stock`
--

LOCK TABLES `warehouse_spare_part_stock` WRITE;
/*!40000 ALTER TABLE `warehouse_spare_part_stock` DISABLE KEYS */;
INSERT INTO `warehouse_spare_part_stock` VALUES (1,1,6,3,5,'2026-08-24 21:42:58','2026-08-24 22:10:22'),(2,3,9,1,5,'2026-08-24 21:44:32','2026-08-24 21:44:32'),(3,1,4,0,5,'2026-08-24 22:22:48','2026-08-24 22:22:48'),(4,2,4,0,5,'2026-08-24 23:30:11','2026-08-25 02:09:50'),(5,2,1,0,5,'2026-08-25 00:33:50','2026-08-25 00:33:50'),(6,1,11,1,5,'2026-08-25 01:52:49','2026-08-25 01:52:49');
/*!40000 ALTER TABLE `warehouse_spare_part_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouse_vehicle_stock`
--

DROP TABLE IF EXISTS `warehouse_vehicle_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouse_vehicle_stock` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `warehouse_id` bigint unsigned NOT NULL,
  `vehicle_model_id` bigint unsigned NOT NULL,
  `current_stock` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '2',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wh_vehicle_unique` (`warehouse_id`,`vehicle_model_id`),
  KEY `wh_veh_vm_fk` (`vehicle_model_id`),
  CONSTRAINT `wh_veh_vm_fk` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wh_veh_wh_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouse_vehicle_stock`
--

LOCK TABLES `warehouse_vehicle_stock` WRITE;
/*!40000 ALTER TABLE `warehouse_vehicle_stock` DISABLE KEYS */;
INSERT INTO `warehouse_vehicle_stock` VALUES (1,3,6,0,2,'2026-08-24 21:50:16','2026-08-24 21:50:16'),(2,1,6,0,2,'2026-08-24 22:09:11','2026-08-24 22:09:11'),(3,3,5,0,2,'2026-08-24 22:53:41','2026-08-25 02:05:18'),(4,2,3,2,2,'2026-08-24 23:09:54','2026-08-24 23:09:54');
/*!40000 ALTER TABLE `warehouse_vehicle_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (1,'STK-001','Main Store - Addis Ababa','Addis Ababa','Addis Ababa, Ethiopia','+251-111-000001','System Admin','active',NULL,1,'2026-08-24 21:39:22','2026-08-24 21:39:22'),(2,'STK-002','Mekelle Branch','Mekelle','Mekelle, Tigray','+251-344-000001','Branch Manager','active',NULL,0,'2026-08-24 21:39:22','2026-08-24 21:39:22'),(3,'STK-003','Kenya','Kenya',NULL,NULL,NULL,'active',NULL,0,'2026-08-24 21:43:50','2026-08-24 21:43:50');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'ashu_spare_part'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-25 11:02:29
