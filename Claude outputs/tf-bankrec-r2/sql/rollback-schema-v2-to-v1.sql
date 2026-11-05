-- tf-bankrec-r2: schema version 2 → 1 on the ISOLATED TEST BED (avenir_restore_verify_20261011) only — the rollback of the r2 database change.
-- Removes the two version-2 tables and the version-2 columns, with everything recorded in them (claims, timing history, partial-match links,
-- difference explanations, posting verification, record fingerprints). Use together with: python apply.py rollback <avenir_rv root>.
-- To return to the demonstration data exactly as it was before r2, use reset-demo-to-r1-snapshot.sql instead.

-- Guard: stops (error 1048) unless this is the isolated test-bed database at schema version 2.
DROP TEMPORARY TABLE IF EXISTS brc_r2_guard;
CREATE TEMPORARY TABLE brc_r2_guard (ok TINYINT NOT NULL);
INSERT INTO brc_r2_guard (ok) VALUES (IF(DATABASE() = 'avenir_restore_verify_20261011', 1, NULL));
INSERT INTO brc_r2_guard (ok) VALUES (IF((SELECT v FROM tpd_meta WHERE k = 'brc_schema_version') = '2', 1, NULL));
INSERT INTO brc_r2_guard (ok) VALUES (IF((SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brc_account_map') = 1, 1, NULL));

DROP TABLE IF EXISTS brc_claim;
DROP TABLE IF EXISTS brc_mark_event;
ALTER TABLE `brc_opening` DROP INDEX `uq_brc_open_active`;
ALTER TABLE `brc_transfer` DROP INDEX `uq_brc_tr_from`, DROP INDEX `uq_brc_tr_to`;
ALTER TABLE `brc_mark` DROP INDEX `idx_brc_mark_match`;
ALTER TABLE `brc_correction` DROP INDEX `idx_brc_corr_posted`;
ALTER TABLE `brc_line` DROP COLUMN `version`;
ALTER TABLE `brc_fx_rate` DROP COLUMN `version`;
ALTER TABLE `brc_match` DROP COLUMN `kind`, DROP COLUMN `remainder_lkr`, DROP COLUMN `expected_date`, DROP COLUMN `supersedes_id`, DROP COLUMN `superseded_by`, DROP COLUMN `diff_note`, DROP COLUMN `diff_noted_by`, DROP COLUMN `diff_noted_at`;
ALTER TABLE `brc_mark` DROP COLUMN `cleared_by_match`;
ALTER TABLE `brc_opening` DROP COLUMN `source`, DROP COLUMN `book_key`, DROP COLUMN `item_date`, DROP COLUMN `book_fp`, DROP COLUMN `active_key`;
ALTER TABLE `brc_correction` DROP COLUMN `posting_state`, DROP COLUMN `posted_rows_json`, DROP COLUMN `posted_fp`, DROP COLUMN `posted_entry_date`;
ALTER TABLE `brc_transfer` DROP COLUMN `active_from`, DROP COLUMN `active_to`;
ALTER TABLE `brc_day` DROP COLUMN `inputs_json`, DROP COLUMN `inputs_hash`, DROP COLUMN `fx_note`, DROP COLUMN `pending_json`;
UPDATE brc_match SET status = 'undone', undone_by = 'schema rollback', undone_at = NOW(), undo_reason = 'superseded partial match (r2) removed by the schema rollback' WHERE status = 'superseded';
UPDATE brc_mark SET status = 'cleared' WHERE status = 'matched';
UPDATE tpd_meta SET v = '1' WHERE k = 'brc_schema_version';
INSERT INTO tpd_audit (at, user, action, ref_type, ref_id, reason) SELECT DATE_FORMAT(NOW(3), '%Y-%m-%d %H:%i:%s.%f'), 'claude-technical (upgrade script run in phpMyAdmin; isolated test instance)', 'brc_setup', 'brc_setup', 'v1 (rollback)', 'tf-bankrec-r2 schema rolled back on the isolated test instance' FROM DUAL;
DROP TEMPORARY TABLE IF EXISTS brc_r2_guard;
