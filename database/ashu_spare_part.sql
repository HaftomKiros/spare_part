-- =====================================================
-- Ashu Spare Part Management System - Database Schema
-- Import this file in phpMyAdmin
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `ashu_spare_part` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `ashu_spare_part`;

-- ----------------------------
-- 1. Company Settings
-- ----------------------------
CREATE TABLE `company_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `company_address` varchar(255) DEFAULT NULL,
  `company_phone` varchar(255) DEFAULT NULL,
  `company_email` varchar(255) DEFAULT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'ETB',
  `currency_symbol` varchar(5) NOT NULL DEFAULT 'Br',
  `tax_number` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 2. Roles
-- ----------------------------
CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 3. Users
-- ----------------------------
CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 4. Vehicle Types
-- ----------------------------
CREATE TABLE `vehicle_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `wheel_count` int NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_types_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 5. Vehicle Models
-- ----------------------------
CREATE TABLE `vehicle_models` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_type_id` bigint UNSIGNED NOT NULL,
  `brand` varchar(255) NOT NULL DEFAULT 'Bajaj',
  `model_name` varchar(255) NOT NULL,
  `model_code` varchar(255) DEFAULT NULL,
  `year` year DEFAULT NULL,
  `engine_cc` varchar(255) DEFAULT NULL,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `buying_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_models_vehicle_type_id_foreign` (`vehicle_type_id`),
  CONSTRAINT `vehicle_models_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 6. Units
-- ----------------------------
CREATE TABLE `units` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 7. Part Categories
-- ----------------------------
CREATE TABLE `part_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `part_categories_slug_unique` (`slug`),
  KEY `part_categories_parent_id_foreign` (`parent_id`),
  CONSTRAINT `part_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `part_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 8. Spare Parts
-- ----------------------------
CREATE TABLE `spare_parts` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `part_category_id` bigint UNSIGNED NOT NULL,
  `unit_id` bigint UNSIGNED NOT NULL,
  `part_number` varchar(255) NOT NULL,
  `oem_number` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `buying_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reorder_level` int NOT NULL DEFAULT 5,
  `current_stock` int NOT NULL DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spare_parts_part_number_unique` (`part_number`),
  KEY `spare_parts_part_category_id_foreign` (`part_category_id`),
  KEY `spare_parts_unit_id_foreign` (`unit_id`),
  CONSTRAINT `spare_parts_part_category_id_foreign` FOREIGN KEY (`part_category_id`) REFERENCES `part_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spare_parts_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 9. Spare Part ↔ Vehicle Model (Compatibility)
-- ----------------------------
CREATE TABLE `spare_part_vehicle_model` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `spare_part_id` bigint UNSIGNED NOT NULL,
  `vehicle_model_id` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spare_part_vehicle_model_spare_part_id_foreign` (`spare_part_id`),
  KEY `spare_part_vehicle_model_vehicle_model_id_foreign` (`vehicle_model_id`),
  CONSTRAINT `spare_part_vehicle_model_spare_part_id_foreign` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spare_part_vehicle_model_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 10. Suppliers
-- ----------------------------
CREATE TABLE `suppliers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_supplier_code_unique` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 11. Customers
-- ----------------------------
CREATE TABLE `customers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `customer_type` enum('individual','business') NOT NULL DEFAULT 'individual',
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_customer_code_unique` (`customer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 12. Vehicle Stock
-- ----------------------------
CREATE TABLE `vehicle_stocks` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_model_id` bigint UNSIGNED NOT NULL,
  `current_stock` int NOT NULL DEFAULT 0,
  `reorder_level` int NOT NULL DEFAULT 2,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_stocks_vehicle_model_id_foreign` (`vehicle_model_id`),
  CONSTRAINT `vehicle_stocks_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 13. Purchases
-- ----------------------------
CREATE TABLE `purchases` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_number` varchar(255) NOT NULL,
  `supplier_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `purchase_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `status` enum('draft','ordered','received','cancelled') NOT NULL DEFAULT 'ordered',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchases_purchase_number_unique` (`purchase_number`),
  KEY `purchases_supplier_id_foreign` (`supplier_id`),
  KEY `purchases_user_id_foreign` (`user_id`),
  CONSTRAINT `purchases_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 14. Purchase Items
-- ----------------------------
CREATE TABLE `purchase_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_id` bigint UNSIGNED NOT NULL,
  `item_type` enum('vehicle','spare_part') NOT NULL,
  `vehicle_model_id` bigint UNSIGNED DEFAULT NULL,
  `spare_part_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_items_purchase_id_foreign` (`purchase_id`),
  KEY `purchase_items_vehicle_model_id_foreign` (`vehicle_model_id`),
  KEY `purchase_items_spare_part_id_foreign` (`spare_part_id`),
  CONSTRAINT `purchase_items_purchase_id_foreign` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchase_items_spare_part_id_foreign` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 15. Sales
-- ----------------------------
CREATE TABLE `sales` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(255) NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `sale_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','bank_transfer','cheque','credit') NOT NULL DEFAULT 'cash',
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'paid',
  `status` enum('draft','completed','cancelled') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sales_invoice_number_unique` (`invoice_number`),
  KEY `sales_customer_id_foreign` (`customer_id`),
  KEY `sales_user_id_foreign` (`user_id`),
  CONSTRAINT `sales_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 16. Sale Items
-- ----------------------------
CREATE TABLE `sale_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_id` bigint UNSIGNED NOT NULL,
  `item_type` enum('vehicle','spare_part') NOT NULL,
  `vehicle_model_id` bigint UNSIGNED DEFAULT NULL,
  `spare_part_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_items_sale_id_foreign` (`sale_id`),
  KEY `sale_items_vehicle_model_id_foreign` (`vehicle_model_id`),
  KEY `sale_items_spare_part_id_foreign` (`spare_part_id`),
  CONSTRAINT `sale_items_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_items_spare_part_id_foreign` FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 17. Sale Returns
-- ----------------------------
CREATE TABLE `sale_returns` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_number` varchar(255) NOT NULL,
  `sale_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `return_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `return_type` enum('refund','exchange','credit') NOT NULL DEFAULT 'refund',
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sale_returns_return_number_unique` (`return_number`),
  KEY `sale_returns_sale_id_foreign` (`sale_id`),
  KEY `sale_returns_customer_id_foreign` (`customer_id`),
  KEY `sale_returns_user_id_foreign` (`user_id`),
  CONSTRAINT `sale_returns_sale_id_foreign` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  CONSTRAINT `sale_returns_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sale_returns_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 18. Sale Return Items
-- ----------------------------
CREATE TABLE `sale_return_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sale_return_id` bigint UNSIGNED NOT NULL,
  `item_type` enum('vehicle','spare_part') NOT NULL,
  `vehicle_model_id` bigint UNSIGNED DEFAULT NULL,
  `spare_part_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_return_items_sale_return_id_foreign` (`sale_return_id`),
  CONSTRAINT `sale_return_items_sale_return_id_foreign` FOREIGN KEY (`sale_return_id`) REFERENCES `sale_returns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 19. Stock Movements
-- ----------------------------
CREATE TABLE `stock_movements` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_type` enum('vehicle','spare_part') NOT NULL,
  `vehicle_model_id` bigint UNSIGNED DEFAULT NULL,
  `spare_part_id` bigint UNSIGNED DEFAULT NULL,
  `movement_type` enum('purchase','sale','return_in','return_out','adjustment_in','adjustment_out','opening') NOT NULL,
  `quantity` int NOT NULL,
  `quantity_before` int NOT NULL,
  `quantity_after` int NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 20. Stock Adjustments
-- ----------------------------
CREATE TABLE `stock_adjustments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(255) NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `adjustment_date` date NOT NULL,
  `adjustment_type` enum('increase','decrease','recount') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('draft','approved','rejected') NOT NULL DEFAULT 'approved',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_adjustments_adjustment_number_unique` (`adjustment_number`),
  KEY `stock_adjustments_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_adjustments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- 21. Stock Adjustment Items
-- ----------------------------
CREATE TABLE `stock_adjustment_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `stock_adjustment_id` bigint UNSIGNED NOT NULL,
  `item_type` enum('vehicle','spare_part') NOT NULL,
  `vehicle_model_id` bigint UNSIGNED DEFAULT NULL,
  `spare_part_id` bigint UNSIGNED DEFAULT NULL,
  `quantity_before` int NOT NULL,
  `quantity_adjusted` int NOT NULL,
  `quantity_after` int NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_adjustment_items_stock_adjustment_id_foreign` (`stock_adjustment_id`),
  CONSTRAINT `stock_adjustment_items_stock_adjustment_id_foreign` FOREIGN KEY (`stock_adjustment_id`) REFERENCES `stock_adjustments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA
-- =====================================================

-- Company
INSERT INTO `company_settings` (`company_name`, `company_address`, `company_phone`, `company_email`, `currency`, `currency_symbol`, `created_at`, `updated_at`)
VALUES ('Ashu Spare Part', 'Addis Ababa, Ethiopia', '+251-911-000000', 'info@ashusparepart.et', 'ETB', 'Br', NOW(), NOW());

-- Roles
INSERT INTO `roles` (`name`, `display_name`, `description`, `permissions`, `created_at`, `updated_at`) VALUES
('admin',     'Administrator', 'Full system access',           '["all"]',                        NOW(), NOW()),
('manager',   'Manager',       'Manage inventory and reports', '["catalog","inventory","reports","sales","purchases"]', NOW(), NOW()),
('cashier',   'Cashier',       'Process sales only',           '["sales.create","sales.view"]',  NOW(), NOW()),
('storekeeper','Storekeeper',  'Manage stock and inventory',   '["inventory","catalog.view"]',   NOW(), NOW());

-- Admin user (password: admin123)
INSERT INTO `users` (`role_id`, `name`, `email`, `phone`, `password`, `status`, `created_at`, `updated_at`)
VALUES (1, 'System Admin', 'admin@ashusparepart.et', '+251-911-000000',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NOW(), NOW());

-- Units
INSERT INTO `units` (`name`, `abbreviation`, `description`, `created_at`, `updated_at`) VALUES
('Piece',   'Pcs',  'Individual unit',       NOW(), NOW()),
('Set',     'Set',  'Set of related parts',  NOW(), NOW()),
('Box',     'Box',  'Box of parts',          NOW(), NOW()),
('Litre',   'L',    'Liquid measurement',    NOW(), NOW()),
('Metre',   'M',    'Length measurement',    NOW(), NOW()),
('Kilogram','Kg',   'Weight measurement',    NOW(), NOW()),
('Pair',    'Pr',   'Pair of items',         NOW(), NOW());

-- Vehicle Types
INSERT INTO `vehicle_types` (`name`, `slug`, `wheel_count`, `description`, `status`, `created_at`, `updated_at`) VALUES
('Two Wheeler',   'two-wheeler',   2, 'Motorcycles and scooters',     'active', NOW(), NOW()),
('Three Wheeler', 'three-wheeler', 3, 'Auto-rickshaws and tricycles', 'active', NOW(), NOW());

-- Vehicle Models
INSERT INTO `vehicle_models` (`vehicle_type_id`, `brand`, `model_name`, `model_code`, `engine_cc`, `selling_price`, `buying_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Bajaj', 'Boxer',       'BX100',  '100cc',  35000.00, 30000.00, 'active', NOW(), NOW()),
(1, 'Bajaj', 'Pulsar 135',  'P135',   '135cc',  55000.00, 48000.00, 'active', NOW(), NOW()),
(1, 'Bajaj', 'Pulsar 150',  'P150',   '150cc',  62000.00, 54000.00, 'active', NOW(), NOW()),
(1, 'Bajaj', 'Discover 125','D125',   '125cc',  45000.00, 39000.00, 'active', NOW(), NOW()),
(2, 'Bajaj', 'RE',          'RE200',  '200cc',  95000.00, 82000.00, 'active', NOW(), NOW()),
(2, 'Bajaj', 'Maxima',      'MAX200', '200cc', 105000.00, 90000.00, 'active', NOW(), NOW()),
(2, 'Bajaj', 'Maxima Cargo','MAXC',   '200cc', 110000.00, 95000.00, 'active', NOW(), NOW());

-- Vehicle Stocks
INSERT INTO `vehicle_stocks` (`vehicle_model_id`, `current_stock`, `reorder_level`, `created_at`, `updated_at`) VALUES
(1, 5, 2, NOW(), NOW()),
(2, 3, 2, NOW(), NOW()),
(3, 4, 2, NOW(), NOW()),
(4, 6, 2, NOW(), NOW()),
(5, 8, 3, NOW(), NOW()),
(6, 5, 3, NOW(), NOW()),
(7, 4, 3, NOW(), NOW());

-- Part Categories
INSERT INTO `part_categories` (`parent_id`, `name`, `slug`, `icon`, `status`, `created_at`, `updated_at`) VALUES
(NULL, 'Engine Parts',      'engine-parts',      'fa-cogs',        'active', NOW(), NOW()),
(NULL, 'Electrical Parts',  'electrical-parts',  'fa-bolt',        'active', NOW(), NOW()),
(NULL, 'Body Parts',        'body-parts',        'fa-car',         'active', NOW(), NOW()),
(NULL, 'Brake System',      'brake-system',      'fa-stop-circle', 'active', NOW(), NOW()),
(NULL, 'Transmission',      'transmission',      'fa-gears',       'active', NOW(), NOW()),
(NULL, 'Suspension',        'suspension',        'fa-arrows-v',    'active', NOW(), NOW()),
(NULL, 'Fuel System',       'fuel-system',       'fa-tint',        'active', NOW(), NOW()),
(NULL, 'Cooling System',    'cooling-system',    'fa-thermometer', 'active', NOW(), NOW()),
(NULL, 'Lubricants & Oils', 'lubricants-oils',   'fa-oil-can',     'active', NOW(), NOW()),
(NULL, 'Accessories',       'accessories',       'fa-star',        'active', NOW(), NOW());

-- Sample Spare Parts
INSERT INTO `spare_parts` (`part_category_id`, `unit_id`, `part_number`, `name`, `buying_price`, `selling_price`, `reorder_level`, `current_stock`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'SP-0001', 'Piston Ring Set (Boxer)',       250.00,  400.00, 10, 25, 'active', NOW(), NOW()),
(1, 1, 'SP-0002', 'Engine Oil Filter (Universal)', 80.00,   130.00, 20, 45, 'active', NOW(), NOW()),
(1, 2, 'SP-0003', 'Gasket Set (Boxer)',            320.00,  520.00,  8, 12, 'active', NOW(), NOW()),
(2, 1, 'SP-0004', 'Spark Plug (NGK)',               45.00,   80.00, 30, 60, 'active', NOW(), NOW()),
(2, 1, 'SP-0005', 'Battery 12V',                   650.00, 1000.00,  5, 10, 'active', NOW(), NOW()),
(3, 1, 'SP-0006', 'Front Mudguard (Boxer)',        180.00,  300.00,  5, 8,  'active', NOW(), NOW()),
(4, 7, 'SP-0007', 'Brake Shoe (Rear)',              95.00,  160.00, 15, 30, 'active', NOW(), NOW()),
(4, 7, 'SP-0008', 'Brake Pad (Front Disc)',        150.00,  250.00, 10, 20, 'active', NOW(), NOW()),
(9, 4, 'SP-0009', 'Engine Oil 4T (1L)',            120.00,  200.00, 50, 100,'active', NOW(), NOW()),
(5, 1, 'SP-0010', 'Chain Sprocket Set',            280.00,  480.00,  8, 15, 'active', NOW(), NOW());

-- Sample Suppliers
INSERT INTO `suppliers` (`supplier_code`, `name`, `company`, `phone`, `email`, `city`, `status`, `created_at`, `updated_at`) VALUES
('SUP-001', 'Ashu Spare Part Supplier',       'Bajaj Auto Limited',       '+91-20-12345678', 'supply@bajaj.com',       'Addis Ababa', 'active', NOW(), NOW()),
('SUP-002', 'Auto Parts Wholesale', 'Auto Parts Wholesale PLC', '+251-111-222333', 'info@autoparts.et',      'Addis Ababa', 'active', NOW(), NOW()),
('SUP-003', 'Mekele Spare Parts',   'Mekele Spare Parts Shop',  '+251-344-555666', 'mekele@spareparts.et',   'Mekele',      'active', NOW(), NOW());

-- Sample Customers
INSERT INTO `customers` (`customer_code`, `name`, `phone`, `city`, `customer_type`, `status`, `created_at`, `updated_at`) VALUES
('CUST-001', 'Walk-in Customer',    '+000-000-0000', 'Addis Ababa', 'individual', 'active', NOW(), NOW()),
('CUST-002', 'Kebede Tadesse',      '+251-911-111111', 'Addis Ababa', 'individual', 'active', NOW(), NOW()),
('CUST-003', 'Almaz Transport PLC', '+251-111-333444', 'Addis Ababa', 'business',   'active', NOW(), NOW());
