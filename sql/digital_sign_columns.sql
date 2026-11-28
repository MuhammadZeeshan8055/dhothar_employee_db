-- Digital Sign columns
-- Run once in phpMyAdmin / MySQL
-- Adds signature fields to both earnings tables

ALTER TABLE `delivery_earnings`
  ADD COLUMN `signature_image` varchar(255) DEFAULT NULL AFTER `status`,
  ADD COLUMN `signed_at` datetime DEFAULT NULL AFTER `signature_image`,
  ADD COLUMN `signed_by` int(11) DEFAULT NULL AFTER `signed_at`;

ALTER TABLE `ride_sharing_earnings`
  ADD COLUMN `signature_image` varchar(255) DEFAULT NULL AFTER `status`,
  ADD COLUMN `signed_at` datetime DEFAULT NULL AFTER `signature_image`,
  ADD COLUMN `signed_by` int(11) DEFAULT NULL AFTER `signed_at`;
