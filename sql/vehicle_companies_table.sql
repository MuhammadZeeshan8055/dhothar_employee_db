CREATE TABLE IF NOT EXISTS `vehicle_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_vehicle_company_slug` (`slug`),
  UNIQUE KEY `uniq_vehicle_company_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `vehicle_companies` (`slug`, `name`, `is_active`, `created_at`, `updated_at`)
SELECT 'uny_mobility_srl', 'UNY MOBILITY SRL', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `vehicle_companies` WHERE `slug` = 'uny_mobility_srl');

INSERT INTO `vehicle_companies` (`slug`, `name`, `is_active`, `created_at`, `updated_at`)
SELECT 'kiris_rent_srl', 'KIRIS RENT SRL', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `vehicle_companies` WHERE `slug` = 'kiris_rent_srl');

INSERT INTO `vehicle_companies` (`slug`, `name`, `is_active`, `created_at`, `updated_at`)
SELECT 'rbj_brothers_srl', 'RBJ BROTHERS SRL', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `vehicle_companies` WHERE `slug` = 'rbj_brothers_srl');

INSERT INTO `vehicle_companies` (`slug`, `name`, `is_active`, `created_at`, `updated_at`)
SELECT 'pasio', 'PASIO', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `vehicle_companies` WHERE `slug` = 'pasio');

INSERT INTO `vehicle_companies` (`slug`, `name`, `is_active`, `created_at`, `updated_at`)
SELECT 'phoenix_car_rental', 'Phoenix car rental', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `vehicle_companies` WHERE `slug` = 'phoenix_car_rental');

INSERT INTO `vehicle_companies` (`slug`, `name`, `is_active`, `created_at`, `updated_at`)
SELECT 'own_car', 'Own Car', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `vehicle_companies` WHERE `slug` = 'own_car');
