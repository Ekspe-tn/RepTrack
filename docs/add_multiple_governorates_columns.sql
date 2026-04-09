-- Add columns to users table for multiple governorates support
-- Run this SQL manually in your database

ALTER TABLE `users` 
ADD COLUMN `governorate_ids` TEXT NULL COMMENT 'JSON array of governorate IDs for multi-governorate support' 
AFTER `governorate_id`;

ALTER TABLE `users` 
ADD COLUMN `excluded_city_ids` TEXT NULL COMMENT 'JSON array of excluded city IDs' 
AFTER `governorate_ids`;