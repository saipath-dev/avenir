-- tf-paydesk-r4 — the desk's 11 tables as Setup creates/upgrades them (schema version 6), dumped from the test copy; for reference only (Setup creates them with CREATE TABLE IF NOT EXISTS + ADD COLUMN).
/*M!999999\- enable the sandbox mode */ 
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_meta` (
  `k` varchar(60) NOT NULL,
  `v` text DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_bank_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(160) NOT NULL DEFAULT 'Avenir Holidays (Pvt) Ltd',
  `bank_name` varchar(160) NOT NULL,
  `branch` varchar(160) DEFAULT NULL,
  `account_name` varchar(160) NOT NULL,
  `account_no` varchar(60) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'LKR',
  `swift` varchar(20) DEFAULT NULL,
  `account_type` varchar(40) DEFAULT NULL,
  `label` varchar(120) NOT NULL,
  `ledger_code` varchar(50) DEFAULT NULL COMMENT 'account_posting.L_Code the ledger posts to (the bank ledger account)',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `external_display` varchar(10) NOT NULL DEFAULT 'masked' COMMENT 'full | masked | hidden on supplier documents',
  `display_digits` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `note` varchar(300) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tpd_bank_active` (`active`,`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_supplier_bank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sup_acc` varchar(50) NOT NULL,
  `beneficiary_name` varchar(200) DEFAULT NULL,
  `bank_name` varchar(160) DEFAULT NULL,
  `branch` varchar(160) DEFAULT NULL,
  `account_no` varchar(60) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'LKR',
  `swift` varchar(20) DEFAULT NULL,
  `invoice_email` varchar(200) DEFAULT NULL,
  `remittance_email` varchar(200) DEFAULT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'unverified' COMMENT 'unverified | verified | changed | inactive',
  `verified_by` varchar(100) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_hash` char(40) DEFAULT NULL COMMENT 'sha1 of the details as verified — a later change is detected against it',
  `note` varchar(300) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tpd_supplier_bank` (`sup_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_request_ext` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pr_id` int(10) unsigned NOT NULL COMMENT 'payment_request.id',
  `tour_id` varchar(50) NOT NULL,
  `hotel_acc` varchar(50) NOT NULL,
  `type` varchar(40) DEFAULT NULL,
  `source_type` varchar(20) DEFAULT NULL COMMENT 'hotel | activity | driver_advance | expense',
  `source_id` varchar(60) DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'LKR',
  `amount` decimal(14,2) DEFAULT NULL COMMENT 'the request in its own currency',
  `usd_rate` decimal(12,4) DEFAULT NULL COMMENT 'USD → LKR locked when the request was made',
  `lkr_amount` decimal(14,2) DEFAULT NULL,
  `rate_date` date DEFAULT NULL,
  `rate_user` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `booking_ref` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `voucher_vnum` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `parent_pr_id` int(10) unsigned DEFAULT NULL COMMENT 'the earlier request this one pays the balance of',
  `batch_id` int(10) unsigned DEFAULT NULL COMMENT 'tpd_batch.id once taken into a supplier payment batch',
  `vinv_id` int(11) DEFAULT NULL COMMENT 'the v_inv line of this request on the batch voucher',
  `approval_rate` decimal(12,4) DEFAULT NULL COMMENT 'USD rate shown at approval (provisional)',
  `approval_lkr` decimal(14,2) DEFAULT NULL COMMENT 'provisional LKR = USD × approval rate',
  `approval_rate_date` date DEFAULT NULL,
  `paid_rate` decimal(12,4) DEFAULT NULL COMMENT 'USD rate of the actual bank-payment date (final, locked at Paid)',
  `paid_lkr` decimal(14,2) DEFAULT NULL COMMENT 'final LKR = USD × payment-date rate',
  `paid_date` date DEFAULT NULL,
  `fx_diff` decimal(14,2) DEFAULT NULL COMMENT 'final LKR − provisional LKR (+ loss / − gain), this request''s share',
  `fx_entry_id` int(11) DEFAULT NULL COMMENT 'tf-paydesk-r3: the account_runing entry (pair) that posted this line''s exchange-rate difference',
  `charge_share` decimal(12,2) DEFAULT NULL COMMENT 'tf-paydesk-r4: this request''s share of the bank charges of the payment (pro rata to its final LKR)',
  `charge_entry_id` int(11) DEFAULT NULL COMMENT 'tf-paydesk-r4: the account_runing entry (pair) that posted this line''s share of the bank charges',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tpd_request_ext` (`pr_id`),
  KEY `idx_tpd_request_ext_tour` (`tour_id`,`hotel_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_bank_initiation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vnum` int(11) NOT NULL COMMENT 'voucher.Vnum',
  `voucher_id` int(11) DEFAULT NULL,
  `tour_id` varchar(50) DEFAULT NULL,
  `sup_acc` varchar(50) DEFAULT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `ledger_code` varchar(50) DEFAULT NULL,
  `initiated_at` datetime NOT NULL,
  `batch_ref` varchar(100) DEFAULT NULL,
  `bank_ref` varchar(100) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'LKR',
  `charges` decimal(12,2) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `initiated_by` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  `batch_id` int(10) unsigned DEFAULT NULL,
  `payment_date` date DEFAULT NULL COMMENT 'the actual bank-payment date the accountant entered',
  `usd_amount` decimal(14,2) DEFAULT NULL,
  `usd_rate` decimal(12,4) DEFAULT NULL COMMENT 'the rate of the payment date, confirmed by the accountant',
  `rate_source` varchar(12) DEFAULT NULL COMMENT 'daily (Today''s rate) | entered',
  `approval_lkr` decimal(14,2) DEFAULT NULL COMMENT 'the provisional LKR at approval, for comparison',
  `fx_diff` decimal(14,2) DEFAULT NULL,
  `total_debit` decimal(14,2) DEFAULT NULL COMMENT 'LKR amount + bank charges',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tpd_bank_initiation` (`vnum`),
  KEY `idx_tpd_bank_initiation_batch` (`batch_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_bank_approval` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vnum` int(11) NOT NULL,
  `voucher_id` int(11) DEFAULT NULL,
  `tour_id` varchar(50) DEFAULT NULL,
  `sup_acc` varchar(50) DEFAULT NULL,
  `paid_date` date NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'LKR',
  `usd_amount` decimal(14,2) DEFAULT NULL,
  `usd_rate` decimal(12,4) DEFAULT NULL,
  `lkr_amount` decimal(14,2) DEFAULT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `ledger_code` varchar(50) DEFAULT NULL,
  `bank_ref` varchar(100) DEFAULT NULL,
  `initiated_by` varchar(100) DEFAULT NULL,
  `approved_by` varchar(100) NOT NULL,
  `approved_at` datetime(3) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `batch_id` int(10) unsigned DEFAULT NULL,
  `approval_lkr` decimal(14,2) DEFAULT NULL COMMENT 'the provisional LKR at approval — kept beside the final figures',
  `fx_diff` decimal(14,2) DEFAULT NULL COMMENT 'final LKR − provisional LKR: + exchange loss, − exchange gain',
  `charges` decimal(12,2) DEFAULT NULL,
  `total_debit` decimal(14,2) DEFAULT NULL,
  `rate_source` varchar(12) DEFAULT NULL,
  `fx_posted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 when the difference was posted to the exchange gain/loss ledger account',
  `fx_entries` varchar(255) DEFAULT NULL COMMENT 'the account_runing entry ids of the exchange-difference postings (one pair per tour line)',
  `account_confirmed_by` varchar(100) DEFAULT NULL COMMENT 'the bank approver who confirmed the paying account at Paid',
  `charges_posted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'tf-paydesk-r4: 1 when the bank charges were posted to the mapped Bank Charges account (once)',
  `charge_entries` varchar(255) DEFAULT NULL COMMENT 'the account_runing entry ids of the bank-charge postings (one pair per tour line)',
  `ledger_bank_credit` decimal(14,2) DEFAULT NULL COMMENT 'sum of the credits to the paying account''s ledger for this voucher at Paid (payment + charges)',
  `reconciled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 when ledger_bank_credit = total_debit and every entry balances',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tpd_bank_approval` (`vnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_remittance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vnum` int(11) NOT NULL,
  `tour_id` varchar(50) DEFAULT NULL,
  `sup_acc` varchar(50) NOT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'pending' COMMENT 'pending | prepared | sent | failed',
  `to_email` varchar(200) DEFAULT NULL,
  `cc` varchar(400) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `prepared_by` varchar(100) DEFAULT NULL,
  `prepared_at` datetime DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `error` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `message_id` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tpd_remittance` (`vnum`),
  KEY `idx_tpd_remittance_sup` (`sup_acc`),
  KEY `idx_tpd_remittance_tour` (`tour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_document` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `kind` varchar(15) NOT NULL COMMENT 'invoice | booking | bank_proof | remittance | other',
  `ref_type` varchar(20) DEFAULT NULL COMMENT 'line | request | voucher',
  `ref_id` varchar(80) DEFAULT NULL,
  `vnum` int(11) DEFAULT NULL,
  `number` varchar(100) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tpd_document_tour` (`tour_id`),
  KEY `idx_tpd_document_ref` (`ref_type`,`ref_id`),
  KEY `idx_tpd_document_vnum` (`vnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_expense` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tour_id` varchar(50) NOT NULL,
  `manual_id` varchar(50) DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'Other',
  `supplier_acc` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `description` varchar(255) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'LKR',
  `amount` decimal(14,2) NOT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `note` varchar(500) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'open' COMMENT 'open | cancelled',
  `created_by` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  `cancelled_by` varchar(100) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tpd_expense_tour` (`tour_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_batch` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_no` varchar(20) DEFAULT NULL COMMENT 'SB-00001 …',
  `sup_acc` varchar(50) NOT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `currency` varchar(5) NOT NULL DEFAULT 'LKR' COMMENT 'USD | LKR | MIXED',
  `usd_total` decimal(14,2) DEFAULT NULL COMMENT 'the locked USD amounts, summed',
  `approval_lkr_total` decimal(14,2) NOT NULL COMMENT 'provisional LKR at the approval-date rates (+ the LKR items)',
  `item_count` int(11) NOT NULL DEFAULT 0,
  `tour_count` int(11) NOT NULL DEFAULT 0,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `ledger_code` varchar(50) DEFAULT NULL,
  `vnum` int(11) DEFAULT NULL COMMENT 'the voucher issued for the batch',
  `status` varchar(12) NOT NULL DEFAULT 'issued' COMMENT 'issued | initiated | paid | notified | cancelled',
  `note` varchar(500) DEFAULT NULL,
  `created_by` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `individual` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = paid individually (the accumulation stage bypassed)',
  `bypass_reason` varchar(40) DEFAULT NULL COMMENT 'driver_advance | driver_settlement | urgent_cash | pay_at_gate | emergency | other',
  `bypass_note` varchar(500) DEFAULT NULL,
  `suggested_bank_account_id` int(10) unsigned DEFAULT NULL COMMENT 'the Avenir account management suggested when creating the batch',
  `suggested_ledger_code` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tpd_batch_no` (`batch_no`),
  KEY `idx_tpd_batch_sup` (`sup_acc`,`status`),
  KEY `idx_tpd_batch_vnum` (`vnum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE IF NOT EXISTS `tpd_audit` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `at` datetime(3) NOT NULL,
  `user` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tour_id` varchar(50) DEFAULT NULL,
  `manual_id` varchar(50) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `ref_type` varchar(20) DEFAULT NULL,
  `ref_id` varchar(80) DEFAULT NULL,
  `vnum` int(11) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `prev_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `usd_rate` decimal(12,4) DEFAULT NULL,
  `bank_account` varchar(160) DEFAULT NULL,
  `bank_ref` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tpd_audit_tour` (`tour_id`,`at`),
  KEY `idx_tpd_audit_vnum` (`vnum`),
  KEY `idx_tpd_audit_user` (`user`,`at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
-- tf-paydesk-r3: the official Payment Voucher No. is voucher.Vnum — Setup adds (when no duplicates exist) a unique index and an update guard on the EXISTING voucher table; no row is touched. Reversible: DROP INDEX / DROP TRIGGER.
-- ALTER TABLE `voucher` ADD UNIQUE INDEX `uq_tpd_voucher_vnum` (`Vnum`);
-- CREATE TRIGGER `tpd_voucher_vnum_guard` BEFORE UPDATE ON `voucher` FOR EACH ROW BEGIN IF NEW.Vnum <> OLD.Vnum THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Payment Voucher No. (Vnum) cannot be changed once issued'; END IF; END;
-- tf-paydesk-r4 columns added to the r3 tables: tpd_request_ext.charge_share, charge_entry_id; tpd_bank_approval.charges_posted, charge_entries, ledger_bank_credit, reconciled. tpd_meta keys: schema_version, installed_at, fx_gain_ledger_code, fx_loss_ledger_code, bank_charge_ledger_code.
