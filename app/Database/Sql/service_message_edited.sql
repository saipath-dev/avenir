-- tf-supmail-r2 (4 Nov 2026, Shantha): who wrote a supplier request themselves, and when.
-- A request a person has edited is theirs: a later automatic rewrite leaves its words alone, and the trail
-- shows that what went out was written by hand rather than produced from the template.
-- Additive and idempotent: two nullable columns, nothing else touched.
ALTER TABLE `tf_service_message` ADD COLUMN `edited_at` DATETIME NULL DEFAULT NULL AFTER `body`;
ALTER TABLE `tf_service_message` ADD COLUMN `edited_by` VARCHAR(190) NULL DEFAULT NULL AFTER `edited_at`;
