-- tf-workflow-r1 (3 Nov 2026) — the two confirmations, kept apart.
--
-- Shantha's rule: pressing CONFIRMED on the Quotation panel records the CLIENT'S ACCEPTANCE and
-- releases the tour to the operational teams. It is not the confirmation sent to the agent. The
-- FINAL confirmation is pressed by the Confirmed Tour Handler, on the Confirmed Tour panel, only
-- once every required section is green, and it is what the agent is told about.
--
-- Additive only: two columns on tf_tour. No existing row is touched, no column is dropped, and
-- tf_tour.stage keeps exactly the values it has today, so every screen and report that reads the
-- stage carries on unchanged. A tour confirmed before this release has both columns NULL, which
-- reads as "client accepted, final confirmation not yet sent" — which is what it is.
--
-- Safe to run twice: each column is added only if it is not already there (MySQL 8 has no
-- ADD COLUMN IF NOT EXISTS, so the check is explicit).

SET @add_at := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tf_tour' AND COLUMN_NAME = 'final_confirmed_at');
SET @sql_at := IF(@add_at = 0,
    'ALTER TABLE `tf_tour` ADD COLUMN `final_confirmed_at` DATETIME NULL DEFAULT NULL AFTER `confirmed_by`',
    'DO 0');
PREPARE s FROM @sql_at; EXECUTE s; DEALLOCATE PREPARE s;

SET @add_by := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tf_tour' AND COLUMN_NAME = 'final_confirmed_by');
SET @sql_by := IF(@add_by = 0,
    'ALTER TABLE `tf_tour` ADD COLUMN `final_confirmed_by` VARCHAR(100) NULL DEFAULT NULL AFTER `final_confirmed_at`',
    'DO 0');
PREPARE s FROM @sql_by; EXECUTE s; DEALLOCATE PREPARE s;
