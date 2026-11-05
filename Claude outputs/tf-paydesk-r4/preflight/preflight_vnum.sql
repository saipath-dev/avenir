-- tf-paydesk-r4 — LIVE PREFLIGHT for the official Payment Voucher No. (voucher.Vnum). READ-ONLY: every statement is a SELECT.
-- Run against the live database (phpMyAdmin → SQL tab, or the mysql client). Nothing is renumbered or altered.

-- A. the table and the number column
SELECT TABLE_SCHEMA, TABLE_NAME, ENGINE, TABLE_ROWS, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('voucher', 'v_inv', 'invoice_no');
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'voucher' AND COLUMN_NAME IN ('ID', 'Vnum', 'Voucher_No', 'Aflag', 'finalized');

-- B. counts: total, distinct numbers, NULL / zero / negative, min, max
SELECT COUNT(*) AS vouchers, COUNT(DISTINCT Vnum) AS distinct_vnum, COUNT(*) - COUNT(DISTINCT Vnum) AS duplicated_rows,
       SUM(Vnum IS NULL) AS null_vnum, SUM(Vnum = 0) AS zero_vnum, SUM(Vnum < 0) AS negative_vnum,
       MIN(Vnum) AS min_vnum, MAX(Vnum) AS max_vnum FROM voucher;

-- C. every duplicated number with its rows (expected: no rows)
SELECT Vnum, COUNT(*) AS rows_with_this_number, GROUP_CONCAT(ID ORDER BY ID) AS voucher_ids, GROUP_CONCAT(Aflag ORDER BY ID) AS aflags, GROUP_CONCAT(finalized ORDER BY ID) AS finalized, GROUP_CONCAT(DATE(C_Date) ORDER BY ID) AS created
FROM voucher GROUP BY Vnum HAVING COUNT(*) > 1 ORDER BY Vnum;

-- D. NULL / zero / negative numbers (expected: no rows)
SELECT ID, Vnum, Acc_No, Amount, Aflag, finalized, C_Date FROM voucher WHERE Vnum IS NULL OR Vnum <= 0 ORDER BY ID;

-- E. the number counter must be in step with the table (counter = max Vnum; a counter BEHIND the table would reuse an issued number)
SELECT (SELECT V_Num FROM invoice_no LIMIT 1) AS counter_v_num, (SELECT MAX(Vnum) FROM voucher) AS max_vnum,
       (SELECT V_Num FROM invoice_no LIMIT 1) - (SELECT MAX(Vnum) FROM voucher) AS counter_minus_max,
       (SELECT COUNT(*) FROM invoice_no) AS counter_rows;

-- F. gaps in the numbering (information only — gaps are harmless; a number is never reused)
SELECT COUNT(*) AS numbers_in_use, MAX(Vnum) - MIN(Vnum) + 1 AS span, (MAX(Vnum) - MIN(Vnum) + 1) - COUNT(DISTINCT Vnum) AS unused_numbers_in_span FROM voucher;

-- G. voucher lines (v_inv.V_tkn) that point at no voucher, and vouchers with no lines (information only)
SELECT COUNT(*) AS orphan_lines FROM v_inv vi LEFT JOIN voucher v ON v.Vnum = vi.V_tkn WHERE v.ID IS NULL;
SELECT COUNT(*) AS vouchers_without_lines FROM voucher v LEFT JOIN v_inv vi ON vi.V_tkn = v.Vnum WHERE vi.ID IS NULL;

-- H. ledger rows that reference a voucher number that does not exist (information only)
SELECT COUNT(DISTINCT Recpt_No) AS ledger_voucher_refs_without_voucher FROM account_runing a LEFT JOIN voucher v ON v.Vnum = a.Recpt_No WHERE a.Recpt_No > 0 AND a.Discription LIKE 'VOUCHER PAYMENT%' AND v.ID IS NULL;

-- I. what Setup would add — is any of it already there?
SELECT INDEX_NAME, NON_UNIQUE, COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'voucher' ORDER BY INDEX_NAME, SEQ_IN_INDEX;
SELECT TRIGGER_NAME, EVENT_MANIPULATION, ACTION_TIMING FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'voucher';

-- J. can the database user create the index and the trigger? (the trigger needs TRIGGER on the table; on a server with binary logging and no SUPER it also needs log_bin_trust_function_creators = 1)
SHOW GRANTS;
SELECT @@version AS server_version, @@log_bin AS binary_logging, @@log_bin_trust_function_creators AS trust_function_creators, @@sql_mode AS sql_mode;

-- K. the desk's own tables, if the kit was ever installed before (expected on a fresh live: none)
SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'tpd\_%';
