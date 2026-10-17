-- Ride Sharing tables (run once in phpMyAdmin / MySQL)
-- Does not alter delivery_earnings or employee_rate_settings

CREATE TABLE IF NOT EXISTS `ride_sharing_rate_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `week_year` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `week_start` date DEFAULT NULL,
  `week_end` date DEFAULT NULL,
  `commission_rate` decimal(10,2) NOT NULL DEFAULT 10.00 COMMENT 'Percent of Total Net',
  `vehicle_rent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_employee_week` (`employee_id`, `week_year`, `week_number`),
  KEY `idx_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ride_sharing_earnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `earning_date` date DEFAULT NULL,
  `week_year` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `week_start` date DEFAULT NULL,
  `week_end` date DEFAULT NULL,
  `bolt_net` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bolt_payout` decimal(12,2) NOT NULL DEFAULT 0.00,
  `uber_net` decimal(12,2) NOT NULL DEFAULT 0.00,
  `uber_payout` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_net` decimal(12,2) NOT NULL DEFAULT 0.00,
  `commission` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vehicle_rent` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_payout` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `week_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `prev_carry` decimal(12,2) NOT NULL DEFAULT 0.00,
  `adjustment_note` text,
  `total_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=unpaid, 1=paid/locked',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_employee_week` (`employee_id`, `week_year`, `week_number`),
  KEY `idx_employee` (`employee_id`),
  KEY `idx_week` (`week_year`, `week_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
