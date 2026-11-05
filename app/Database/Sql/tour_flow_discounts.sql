-- tf-discount-r1 (1 Nov 2026) — the invoice discount adjustment, Pilot only.
-- Keyed on debtor_invoice_log.id, which is the only safe unique invoice identifier: Invoice_Num repeats
-- (1,293 distinct numbers across 2,039 invoice rows) and account_runing has no primary key at all.
-- The adjustment stores every figure it shows, so no customer document or preview ever depends on a
-- report view. Additive and safe to re-run.

CREATE TABLE IF NOT EXISTS `tf_invoice_discount` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_log_id`      INT          NOT NULL,            -- debtor_invoice_log.id  (the key)
  `invoice_no`          VARCHAR(60)  NULL,                -- copied for display only, never a key
  `tour_id`             INT          NULL,                -- tf_tour.id
  `quote_id`            INT          NULL,                -- tf_quote.id, the issued version
  `customer_lcode`      VARCHAR(60)  NOT NULL,            -- the debtor account credited
  `income_lcode`        VARCHAR(60)  NOT NULL,            -- from debtor_invoice_log.payment_lcode
  `income_fallback`     TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 when payment_lcode was blank
  `ledger_status`       VARCHAR(10)  NOT NULL DEFAULT '', -- copied from the invoice's own rows
  `method`              ENUM('points','percent') NOT NULL,
  `input_value`         DECIMAL(10,4) NOT NULL,
  `cost_amount`         DECIMAL(14,2) NULL,
  `original_markup_pct` DECIMAL(8,2)  NULL,
  `issued_local`        DECIMAL(14,2) NOT NULL,
  `issued_usd`          DECIMAL(14,2) NULL,
  `usd_rate`            DECIMAL(14,4) NULL,
  `discount_local`      DECIMAL(14,2) NOT NULL,
  `discount_usd`        DECIMAL(14,2) NULL,
  `revised_local`       DECIMAL(14,2) NOT NULL,
  `revised_usd`         DECIMAL(14,2) NULL,
  `remaining_profit`    DECIMAL(14,2) NULL,
  `below_cost`          TINYINT(1)   NOT NULL DEFAULT 0,
  `external_pct`        DECIMAL(8,4) NULL,                -- only when it reproduces the amount exactly
  `reason`              TEXT         NULL,
  `state`               ENUM('requested','approved','cancelled','reversed') NOT NULL DEFAULT 'requested',
  `idem_key`            VARCHAR(64)  NOT NULL,
  `requested_by`        VARCHAR(100) NULL,
  `requested_by_id`     INT          NULL,
  `requested_at`        DATETIME     NULL,
  `approved_by`         VARCHAR(100) NULL,
  `approved_by_id`      INT          NULL,
  `approved_at`         DATETIME     NULL,
  `approve_reason`      VARCHAR(500) NULL,
  `cancelled_by`        VARCHAR(100) NULL,
  `cancelled_at`        DATETIME     NULL,
  `cancel_note`         VARCHAR(500) NULL,
  `reversed_by`         VARCHAR(100) NULL,
  `reversed_at`         DATETIME     NULL,
  `reverse_note`        VARCHAR(500) NULL,
  `post_id`             INT          NULL,                -- account_runing.ID of the posting
  `reverse_post_id`     INT          NULL,
  `created_at`          DATETIME     NULL,
  `updated_at`          DATETIME     NULL,
  -- one live adjustment per invoice: the generated column is NULL once cancelled or reversed, and
  -- MySQL does not enforce uniqueness over NULL, so history is unlimited while only one may be live.
  `live_flag` TINYINT GENERATED ALWAYS AS (CASE WHEN `state` IN ('requested','approved') THEN 1 ELSE NULL END) STORED,
  UNIQUE KEY `uq_live_per_invoice` (`invoice_log_id`, `live_flag`),
  UNIQUE KEY `uq_idem` (`idem_key`),
  KEY `k_invoice` (`invoice_log_id`),
  KEY `k_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tf_invoice_discount_log` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `discount_id` INT          NOT NULL,
  `action`      VARCHAR(40)  NOT NULL,
  `note`        VARCHAR(1000) NULL,
  `by_user`     VARCHAR(100) NULL,
  `by_user_id`  INT          NULL,
  `by_name`     VARCHAR(150) NULL,
  `at`          DATETIME     NULL,
  KEY `k_discount` (`discount_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- tf-discount-r1.2 (1 Nov 2026) — the posting claim.
-- account_runing is MyISAM: it takes no part in a transaction, has no row locking and no unique key, so
-- nothing in that table can prevent a second posting or undo a partial one. The gate is therefore held
-- HERE, in InnoDB: one row per (adjustment, operation), enforced by a unique key. Whoever inserts the
-- claim row owns the posting; a second attempt gets a duplicate-key error and writes nothing at all.
-- The claim is committed BEFORE the ledger is touched and carries the ledger ID it allocated, so an
-- interrupted posting leaves a record naming exactly which rows to check and repair.
CREATE TABLE IF NOT EXISTS `tf_ledger_post_claim` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `discount_id`  INT          NOT NULL,
  `op`           ENUM('discount','reversal') NOT NULL,
  `ledger_id`    INT          NULL,                -- account_runing.ID allocated for this posting
  `expected`     TEXT         NULL,                -- the two legs as they must appear, for verification
  `legs_found`   TINYINT      NOT NULL DEFAULT 0,
  `state`        ENUM('claimed','complete','incomplete') NOT NULL DEFAULT 'claimed',
  `problem`      VARCHAR(1000) NULL,
  `claimed_by`   VARCHAR(100) NULL,
  `claimed_at`   DATETIME     NULL,
  `settled_at`   DATETIME     NULL,
  UNIQUE KEY `uq_claim` (`discount_id`, `op`),
  KEY `k_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
