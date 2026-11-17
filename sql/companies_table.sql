CREATE TABLE IF NOT EXISTS `companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_company_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `companies` (`name`, `is_active`, `created_at`, `updated_at`)
SELECT 'RENT WITH ME S.R.L', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'RENT WITH ME S.R.L');

INSERT INTO `companies` (`name`, `is_active`, `created_at`, `updated_at`)
SELECT 'DHOTHAR HR SERVICES S.R.L', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'DHOTHAR HR SERVICES S.R.L');

INSERT INTO `companies` (`name`, `is_active`, `created_at`, `updated_at`)
SELECT 'DHOTHAR INTERNATINOAL IMPEX S.R.L', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'DHOTHAR INTERNATINOAL IMPEX S.R.L');

INSERT INTO `companies` (`name`, `is_active`, `created_at`, `updated_at`)
SELECT 'DIG LOGISTICS S.R.L', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'DIG LOGISTICS S.R.L');

INSERT INTO `companies` (`name`, `is_active`, `created_at`, `updated_at`)
SELECT 'PASIO', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `companies` WHERE `name` = 'PASIO');
