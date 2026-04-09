-- Add new fields to products table
-- Run this if migration doesn't work

ALTER TABLE `products` 
ADD COLUMN `photo` VARCHAR(255) NULL AFTER `name`,
ADD COLUMN `cost` DECIMAL(10,2) NULL AFTER `photo`,
ADD COLUMN `price` DECIMAL(10,2) NULL AFTER `cost`,
ADD COLUMN `gtin13` VARCHAR(13) NULL AFTER `price`,
ADD COLUMN `specialities` TEXT NULL AFTER `gtin13`;