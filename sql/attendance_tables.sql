-- Attendance tables (run once in phpMyAdmin / MySQL)
-- Saturday + Sunday are holidays in PHP code (not stored as rows)

CREATE TABLE IF NOT EXISTS `attendance_shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `hours` decimal(4,2) NOT NULL DEFAULT 8.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `title` varchar(150) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_holiday_date` (`holiday_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `attendance_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `att_date` date NOT NULL,
  `status` varchar(20) NOT NULL COMMENT 'present, absent, leave, holiday',
  `shift_id` int(11) DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_employee_date` (`employee_id`, `att_date`),
  KEY `idx_att_date` (`att_date`),
  KEY `idx_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default shifts (8 hours each)
INSERT INTO `attendance_shifts` (`name`, `start_time`, `end_time`, `hours`, `is_active`, `created_at`, `updated_at`)
SELECT '9-5', '09:00:00', '17:00:00', 8.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `attendance_shifts` WHERE `name` = '9-5');

INSERT INTO `attendance_shifts` (`name`, `start_time`, `end_time`, `hours`, `is_active`, `created_at`, `updated_at`)
SELECT '8-4', '08:00:00', '16:00:00', 8.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `attendance_shifts` WHERE `name` = '8-4');

INSERT INTO `attendance_shifts` (`name`, `start_time`, `end_time`, `hours`, `is_active`, `created_at`, `updated_at`)
SELECT '10-6', '10:00:00', '18:00:00', 8.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `attendance_shifts` WHERE `name` = '10-6');
