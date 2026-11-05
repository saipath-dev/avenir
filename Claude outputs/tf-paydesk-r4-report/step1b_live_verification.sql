-- Step 1B — LIVE VERIFICATION, READ-ONLY (every statement is a SELECT or SHOW). Bank reconciliation module, inspection follow-up.
-- Run against the live database (phpMyAdmin → SQL tab, or the mysql client). Nothing is written.

-- 0. where we are
SELECT DATABASE() AS db, @@hostname AS server_host, @@version AS server_version, NOW() AS server_time;

-- 1. the company's bank accounts in the chart (heading Bank Accounts = 1-6-8) with names; LKR vs USD can only be told from the name in the legacy system
SELECT L_Code, PName, COALESCE(deact, 0) AS deactivated,
       CASE WHEN PName LIKE '%USD%' OR PName LIKE '%DOLLAR%' THEN 'USD (by name)' WHEN PName LIKE '%LKR%' OR PName LIKE '%RUPEE%' THEN 'LKR (by name)' ELSE 'not stated in the name' END AS currency_by_name
FROM account_posting WHERE L_Code LIKE '1-6-8-%' ORDER BY L_Code;
-- 1b. every account under titel 1-6 (Other Assets) — the legacy reconciliation and journal screens list all of these as "banks"
SELECT L_Code, PName, COALESCE(deact, 0) AS deactivated FROM account_posting WHERE L_Code LIKE '1-6-%' ORDER BY L_Code;
-- 1c. ledger activity per bank account: rows, first/last date, balance (ΣDr−ΣCr)
SELECT a.L_Code, p.PName, COUNT(*) AS rows_, MIN(a.Date) AS first_date, MAX(a.Date) AS last_date, SUM(a.Dr) AS total_dr, SUM(a.Cr) AS total_cr, SUM(a.Dr) - SUM(a.Cr) AS ledger_balance
FROM account_runing a LEFT JOIN account_posting p ON p.L_Code = a.L_Code WHERE a.L_Code LIKE '1-6-8-%' GROUP BY a.L_Code, p.PName ORDER BY a.L_Code;
-- 1d. the USD shadow ledger: rows per account
SELECT L_Code, COUNT(*) AS rows_, SUM(Dr) AS usd_dr, SUM(Cr) AS usd_cr, MIN(Date) AS first_date, MAX(Date) AS last_date FROM account_runing_usd GROUP BY L_Code;
-- 1e. the desk's bank master, if it exists on live (expected: table missing)
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'tpd\_%';

-- 2. have the legacy reconciliation flags ever been used on live?
SELECT bank_rec, COUNT(*) AS rows_ FROM account_runing GROUP BY bank_rec;
SELECT bankst_flag, COUNT(*) AS rows_ FROM account_runing GROUP BY bankst_flag;
SELECT COUNT(*) AS parked_in_holding_account, MIN(Date) AS first_date, MAX(Date) AS last_date FROM account_runing WHERE L_Code = '1-6-112-10782';
SELECT COUNT(*) AS rows_with_new_l_code FROM account_runing WHERE New_L_Code IS NOT NULL AND New_L_Code <> '';
SELECT COUNT(*) AS rows_bank_date, COUNT(DISTINCT Bank_Date) AS distinct_bank_dates FROM account_runing WHERE Bank_Date IS NOT NULL AND Bank_Date <> '0000-00-00';
SELECT BankFlg, COUNT(*) FROM account_runing GROUP BY BankFlg;
SELECT COUNT(*) AS rows_2024_table FROM account_runing_2024;
SELECT COUNT(*) AS rows_bank_statement_table FROM account_runing_bank_statetment;
SELECT COUNT(*) AS cheque_register_rows FROM chk_runing;
-- 2b. is the reconciliation screen in the menu / granted to anyone?
SELECT m.id, m.cat_id, m.name, m.url, m.del, m.show_in_menu_bar, (SELECT GROUP_CONCAT(DISTINCT group_id) FROM user_privileges u WHERE u.menu_id = m.id) AS groups_granted
FROM menu_list m WHERE m.url LIKE '%reconcil%' OR m.url LIKE '%bank_statement%' OR m.url LIKE '%bank_unrealised%' OR m.url LIKE '%usd_withdrawal%' OR m.url LIKE '%debtor_payment%' OR m.url LIKE '%bank_payment_check%' ORDER BY m.id;
-- 2c. has anyone opened those screens? (the legacy activity log)
SELECT Url, COUNT(*) AS hits, MIN(Date) AS first_, MAX(Date) AS last_ FROM a_log WHERE Url LIKE '%reconcil%' OR Url LIKE '%bank_statement%' OR Url LIKE '%bank_unrealised%' OR Url LIKE '%usd_withdrawal%' OR Url LIKE '%debtor_payment%' OR Url LIKE '%bank_payment_check%' GROUP BY Url ORDER BY hits DESC;

-- 3. do the hard-coded account codes exist today?
SELECT c.code, c.used_for, p.PName, COALESCE(p.deact, 0) AS deactivated, (SELECT COUNT(*) FROM account_runing a WHERE a.L_Code = c.code) AS ledger_rows
FROM (SELECT '1-6-8-9352' AS code, 'cheque payments (VoucherPaymentController)' AS used_for UNION ALL SELECT '1-6-8-9750', 'non-supplier vouchers (sup<>1)' UNION ALL SELECT '1-6-112-10782', 'reconciliation holding account (unrealised)'
      UNION ALL SELECT '1-2-5-10779', 'Debtor Control (debtor receipts)' UNION ALL SELECT '8-35-111-10780', 'Income Foreign Exchange Gain-Loss (debtor receipts)' UNION ALL SELECT '1-2-3-1000', 'Cash (cash vouchers, cash book)' UNION ALL SELECT '1-2-3-10900', 'Petty cash (vouchers, petty cash report)'
      UNION ALL SELECT '1-2-4-1000', 'petty cash (old code, CashBookController::petty_cash)' UNION ALL SELECT '6-11-107-9663', 'customer advances (accounts/advance_payment)' UNION ALL SELECT '6-11-107-10381', 'advances (tour details payment_details)' UNION ALL SELECT '1-2-108-10382', 'driver over-payment (fund return)'
      UNION ALL SELECT '4-8-106-10385', 'Transport cost (driver settlement)' UNION ALL SELECT '4-8-106-10386', 'Hotel charges accrual (payment request)' UNION ALL SELECT '4-8-109-10653', 'Lunch / other fee accrual (approval)' UNION ALL SELECT '5-7-7-10388', 'Tour income (invoices, credit notes)' UNION ALL SELECT '1-2-5-10635', 'LionTours (hard-coded in other-invoice receipts)'
      UNION ALL SELECT '3-15-33-10749', 'Opening Balance Control (migrated vouchers)') c LEFT JOIN account_posting p ON p.L_Code = c.code ORDER BY c.code;
-- 3b. ledger rows posted to a code that is NOT in the chart (they vanish from the ledger views)
SELECT a.L_Code, COUNT(*) AS rows_, SUM(a.Dr) AS dr, SUM(a.Cr) AS cr, MIN(a.Date) AS first_date, MAX(a.Date) AS last_date FROM account_runing a LEFT JOIN account_posting p ON p.L_Code = a.L_Code WHERE p.L_Code IS NULL GROUP BY a.L_Code ORDER BY rows_ DESC LIMIT 40;

-- 4. the counters
SELECT acc_id AS ledger_entry_counter, V_Num AS voucher_number_counter, voucher AS voucher_no_counter, debtor_receipt_no, Invoice_2 AS debtor_invoice_counter, No AS advance_counter, JE_inv AS journal_counter, Tour_No, inquiry_no FROM invoice_no;
SELECT (SELECT MAX(ID) FROM account_runing) AS max_ledger_id, (SELECT MAX(Vnum) FROM voucher) AS max_vnum, (SELECT MAX(Voucher_No) FROM voucher) AS max_voucher_no, (SELECT MAX(receipt_no) FROM debtor_receipts) AS max_receipt_no, (SELECT MAX(JE_INV) FROM account_runing) AS max_je;
SELECT COUNT(*) AS vouchers, COUNT(DISTINCT Vnum) AS distinct_vnum, SUM(Vnum IS NULL OR Vnum <= 0) AS bad_vnum, MIN(C_Date) AS first_voucher, MAX(C_Date) AS last_voucher, SUM(finalized = 1) AS finalized, SUM(Aflag = 0) AS pending, SUM(Aflag = 1) AS posted, SUM(Aflag = 2) AS rejected, SUM(Aflag = 3) AS cancelled FROM voucher;
SELECT COUNT(*) AS payment_requests, SUM(payment_request = 1) AS requested, SUM(payment_request IN (4, 6)) AS approved, SUM(payment_request IN (4, 6) AND voucher_created = 0) AS approved_without_voucher, SUM(payment_request = 2) AS completed, SUM(payment_request = 3) AS cancelled, SUM(payment_request = 5) AS rejected, MAX(payment_request_date) AS last_request FROM payment_request;
SELECT COUNT(*) AS debtor_invoices, MIN(date) AS first_, MAX(date) AS last_ FROM debtor_invoice_log;
SELECT COUNT(*) AS debtor_receipts, MIN(payment_date) AS first_, MAX(payment_date) AS last_ FROM debtor_receipts;
SELECT COUNT(*) AS ledger_rows, COUNT(DISTINCT ID) AS ledger_entries, MIN(Date) AS first_date, MAX(Date) AS last_date, SUM(Dr) AS total_dr, SUM(Cr) AS total_cr FROM account_runing;
SELECT COUNT(*) AS unbalanced_entries FROM (SELECT ID FROM account_runing GROUP BY ID HAVING ABS(SUM(Dr) - SUM(Cr)) > 0.005) t;
SELECT COUNT(*) AS daily_rates, MIN(rate_date) AS first_, MAX(rate_date) AS last_ FROM daily_usd_rate;

-- 5. differences from the 2024-09-25 snapshot the inspection used (sizes and dates)
SELECT TABLE_NAME, TABLE_ROWS, ENGINE, UPDATE_TIME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('account_runing', 'account_runing_2024', 'account_runing_usd', 'account_posting', 'voucher', 'v_inv', 'payment_request', 'debtor_invoice_log', 'debtor_receipts', 'other_invoice_log', 'consignee', 'tour_quotations', 'daily_usd_rate', 'a_log', 'menu_list', 'user_privileges', 'group_profile', 'user_acc_tbl') ORDER BY TABLE_NAME;
SELECT COUNT(*) AS accounts_in_chart, SUM(COALESCE(deact, 0) = 1) AS deactivated FROM account_posting;
SELECT id, type, del FROM group_profile ORDER BY id;
SELECT COUNT(*) AS users, SUM(access = 1) AS enabled FROM user_acc_tbl;
SELECT COUNT(*) AS menu_entries FROM menu_list WHERE del = 0;
SELECT COUNT(*) AS brought_forward_rows FROM account_runing WHERE Discription LIKE '%Brought Forward%' OR Invoice_Num LIKE 'YEP-%';
