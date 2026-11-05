-- tf-hotelgrade-r1 (3 Nov 2026): the hotel's own grade — Standard, Superior, Deluxe, Luxury — beside
-- the star rating it already carried. Additive and idempotent: no row is touched, nothing is dropped.
SET @add := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'hr2_hotel' AND COLUMN_NAME = 'grade');
SET @sql := IF(@add = 0,
    'ALTER TABLE `hr2_hotel` ADD COLUMN `grade` VARCHAR(20) NULL DEFAULT NULL AFTER `star_category`',
    'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
