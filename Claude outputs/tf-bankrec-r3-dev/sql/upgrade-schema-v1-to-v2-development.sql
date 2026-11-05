-- tf-bankrec-r3 (development), schema step from tf-bankrec-r2: schema version 1 → 2 of the Daily Bank Reconciliation on the DEVELOPMENT database (avenir) only.
-- Generated from app/Libraries/BankRec/Schema.php (the same steps as the "Set up" button). Adds two tables and columns to the brc_ tables;
-- no other table is altered. Run in phpMyAdmin with the development database `avenir` selected. Never on live.

-- Guard: stops (error 1048) unless this is the development database avenir at schema version 1.
DROP TEMPORARY TABLE IF EXISTS brc_r2_guard;
CREATE TEMPORARY TABLE brc_r2_guard (ok TINYINT NOT NULL);
INSERT INTO brc_r2_guard (ok) VALUES (IF(DATABASE() = 'avenir', 1, NULL));
INSERT INTO brc_r2_guard (ok) VALUES (IF((SELECT v FROM tpd_meta WHERE k = 'brc_schema_version') = '1', 1, NULL));
INSERT INTO brc_r2_guard (ok) VALUES (IF((SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brc_account_map') = 1, 1, NULL));

CREATE TABLE IF NOT EXISTS `brc_claim` ( `item_key` VARCHAR(80) NOT NULL COMMENT 'L<statement line id> | B<bank account id>:<ledger row ID.n>', `bank_account_id` INT UNSIGNED NOT NULL, `kind` VARCHAR(10) NOT NULL COMMENT 'match | mark', `ref_id` INT UNSIGNED NOT NULL, `claimed_by` VARCHAR(100) NOT NULL, `claimed_at` DATETIME NOT NULL, PRIMARY KEY (`item_key`), KEY `idx_brc_claim_ref` (`kind`, `ref_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `brc_mark_event` ( `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `mark_id` INT UNSIGNED NOT NULL, `event` VARCHAR(20) NOT NULL COMMENT 'cleared_by_match | restored_by_undo', `match_id` INT UNSIGNED NULL, `by_user` VARCHAR(100) NOT NULL, `at` DATETIME NOT NULL, `note` VARCHAR(300) NULL, PRIMARY KEY (`id`), KEY `idx_brc_me_mark` (`mark_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `brc_line` ADD COLUMN `version` INT NOT NULL DEFAULT 0 COMMENT 'raised by every change; a change based on an older version is refused';
ALTER TABLE `brc_fx_rate` ADD COLUMN `version` INT NOT NULL DEFAULT 0;
ALTER TABLE `brc_match` ADD COLUMN `kind` VARCHAR(10) NOT NULL DEFAULT 'full' COMMENT 'full | partial (the rest clears later)', ADD COLUMN `remainder_lkr` DECIMAL(19,2) NULL COMMENT 'partial: the part still outstanding (bank − book, LKR)', ADD COLUMN `expected_date` DATE NULL COMMENT 'partial: when the rest is expected', ADD COLUMN `supersedes_id` INT UNSIGNED NULL COMMENT 'the partial match this one completes or extends', ADD COLUMN `superseded_by` INT UNSIGNED NULL, ADD COLUMN `diff_note` VARCHAR(300) NULL COMMENT 'the explanation of a difference (required for exchange-rate and other differences)', ADD COLUMN `diff_noted_by` VARCHAR(100) NULL, ADD COLUMN `diff_noted_at` DATETIME NULL;
ALTER TABLE `brc_mark` ADD COLUMN `cleared_by_match` INT UNSIGNED NULL COMMENT 'a timing item cleared by this match (status matched); an undo restores it';
ALTER TABLE `brc_opening` ADD COLUMN `source` VARCHAR(10) NOT NULL DEFAULT 'manual' COMMENT 'manual | ledger (a ledger row booked before the first day)', ADD COLUMN `book_key` VARCHAR(40) NULL, ADD COLUMN `item_date` DATE NULL, ADD COLUMN `book_fp` CHAR(16) NULL COMMENT 'fingerprint of the ledger row when it was proposed', ADD COLUMN `active_key` VARCHAR(80) NULL COMMENT 'set while proposed or accepted: one opening item per ledger row';
ALTER TABLE `brc_correction` ADD COLUMN `posting_state` VARCHAR(12) NULL COMMENT 'verified: the posted ledger entry was checked against the approved lines', ADD COLUMN `posted_rows_json` TEXT NULL, ADD COLUMN `posted_fp` CHAR(40) NULL, ADD COLUMN `posted_entry_date` DATE NULL;
ALTER TABLE `brc_transfer` ADD COLUMN `active_from` INT UNSIGNED NULL COMMENT 'from_line_id while active (unique)', ADD COLUMN `active_to` INT UNSIGNED NULL COMMENT 'to_line_id while active (unique)';
ALTER TABLE `brc_day` ADD COLUMN `inputs_json` TEXT NULL COMMENT 'fingerprints of everything the record was computed from', ADD COLUMN `inputs_hash` CHAR(40) NULL, ADD COLUMN `fx_note` VARCHAR(300) NULL COMMENT 'the preparer''s explanation of the exchange-rate revaluation', ADD COLUMN `pending_json` TEXT NULL COMMENT 'corrections not yet posted when the record was prepared';
UPDATE brc_transfer SET active_from = from_line_id, active_to = to_line_id WHERE status = 'active' AND active_from IS NULL;
ALTER TABLE `brc_opening` ADD UNIQUE KEY `uq_brc_open_active` (`active_key`);
ALTER TABLE `brc_transfer` ADD UNIQUE KEY `uq_brc_tr_from` (`active_from`), ADD UNIQUE KEY `uq_brc_tr_to` (`active_to`);
ALTER TABLE `brc_mark` ADD KEY `idx_brc_mark_match` (`cleared_by_match`);
ALTER TABLE `brc_correction` ADD KEY `idx_brc_corr_posted` (`posted_ref`);
-- backfill (as Schema::backfillV2): a claim for every matched or classified item; existing explanations of differences
INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at) SELECT CONCAT('L', i.line_id), m.bank_account_id, 'match', m.id, m.created_by, m.created_at FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active' WHERE i.side = 'bank';
INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at) SELECT CONCAT('B', m.bank_account_id, ':', i.book_key), m.bank_account_id, 'match', m.id, m.created_by, m.created_at FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active' WHERE i.side = 'book';
INSERT IGNORE INTO brc_claim (item_key, bank_account_id, kind, ref_id, claimed_by, claimed_at) SELECT IF(k.side = 'bank', CONCAT('L', k.line_id), CONCAT('B', k.bank_account_id, ':', k.book_key)), k.bank_account_id, 'mark', k.id, k.created_by, k.created_at FROM brc_mark k WHERE k.status = 'active';
UPDATE brc_match SET diff_note = note, diff_noted_by = created_by, diff_noted_at = created_at WHERE diff_note IS NULL AND difference_lkr <> 0 AND diff_reason IN ('exchange rate', 'other') AND note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) >= 5;
UPDATE tpd_meta SET v = '2' WHERE k = 'brc_schema_version';
INSERT INTO tpd_audit (at, user, action, ref_type, ref_id, new_value, reason) SELECT DATE_FORMAT(NOW(3), '%Y-%m-%d %H:%i:%s.%f'), 'claude-technical (schema script run in phpMyAdmin; development)', 'brc_setup', 'brc_setup', 'v2',
  CONCAT('{"from":1,"to":2,"claims":', (SELECT COUNT(*) FROM brc_claim), ',"fx_differences_to_explain":', (SELECT COUNT(*) FROM brc_match WHERE status = 'active' AND difference_lkr <> 0 AND diff_reason = 'exchange rate' AND diff_note IS NULL), '}'), 'tf-bankrec-r3 install on development: schema version 2' FROM DUAL;
-- check: the claims recorded must equal the matched + classified items
SELECT (SELECT COUNT(*) FROM brc_claim) AS claims, (SELECT COUNT(*) FROM brc_match_item i JOIN brc_match m ON m.id = i.match_id AND m.status = 'active') + (SELECT COUNT(*) FROM brc_mark WHERE status = 'active') AS items_matched_or_classified, (SELECT v FROM tpd_meta WHERE k = 'brc_schema_version') AS schema_version;
DROP TEMPORARY TABLE IF EXISTS brc_r2_guard;
