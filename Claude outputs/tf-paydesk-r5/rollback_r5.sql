-- tf-paydesk-r5 — DATABASE rollback (schema 7 → 6), to run only after `apply.py rollback` put the r4 files back.
-- Setup made copies of every table it touched before upgrading: tpd_bak_v6_<date>__<table>. Nothing below deletes a
-- payment record: it removes the r5 columns, the r5 indexes, the request-privilege menu entry and its grants, and sets
-- the schema version back. A bulk batch created under r5 keeps its vouchers (ordinary vouchers) and its child batches
-- (seen by r4 as single batches); its parent row (mode bulk_parent, no voucher) is left in place and ignored by r4.
-- Run statement by statement in phpMyAdmin on database `avenir`; each is safe to re-run.

-- 1. the request privilege: its grants, then the menu entry (r4 grants the request right through the dashboard menu again)
DELETE FROM user_privileges WHERE menu_id IN (SELECT id FROM menu_list WHERE url = 'tour/tour_payment_dashboard/request');
DELETE FROM menu_list WHERE url = 'tour/tour_payment_dashboard/request';

-- 2. indexes added by r5
ALTER TABLE tpd_batch DROP INDEX uq_tpd_batch_submit_token;
ALTER TABLE tpd_batch DROP INDEX idx_tpd_batch_parent;
ALTER TABLE tpd_request_ext DROP INDEX idx_tpd_request_ext_bulk;

-- 3. columns added by r5 (tpd_batch)
ALTER TABLE tpd_batch
  DROP COLUMN parent_id, DROP COLUMN mode, DROP COLUMN voucher_count, DROP COLUMN transfer_total, DROP COLUMN transfer_lkr,
  DROP COLUMN bank_ref, DROP COLUMN payment_date, DROP COLUMN usd_rate, DROP COLUMN charges_total, DROP COLUMN charges_allocated,
  DROP COLUMN total_debit, DROP COLUMN ledger_bank_credit, DROP COLUMN reconciled, DROP COLUMN reconciled_at, DROP COLUMN submit_token,
  DROP COLUMN writer, DROP COLUMN engine_atomic, DROP COLUMN selected_by, DROP COLUMN selected_at, DROP COLUMN confirmed_by,
  DROP COLUMN confirmed_at, DROP COLUMN initiated_by, DROP COLUMN initiated_at, DROP COLUMN paid_by, DROP COLUMN paid_at;

-- 4. columns added by r5 (tpd_request_ext, tpd_remittance)
ALTER TABLE tpd_request_ext DROP COLUMN disputed, DROP COLUMN dispute_reason, DROP COLUMN disputed_by, DROP COLUMN disputed_at, DROP COLUMN bulk_id;
DELETE FROM tpd_remittance WHERE vnum IS NULL;            -- the itemised advices of bulk batches (r4 has no such record)
ALTER TABLE tpd_remittance DROP COLUMN batch_id;
ALTER TABLE tpd_remittance MODIFY vnum INT NOT NULL;

-- 5. the schema version
UPDATE tpd_meta SET v = '6' WHERE k = 'schema_version';

-- 6. (optional) compare with the snapshot taken by Setup, e.g.
--    SELECT COUNT(*) FROM tpd_bak_v6_<date>__user_privileges;  SELECT COUNT(*) FROM user_privileges;
--    and drop the snapshot tables once satisfied: DROP TABLE tpd_bak_v6_<date>__tpd_batch; … (six tables)
