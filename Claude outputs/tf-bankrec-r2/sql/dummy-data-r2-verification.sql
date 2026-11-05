-- tf-bankrec-r2: DUMMY data for the r2 verification on a TEST BED only (never on the development or live database):
-- a fourth inactive TEST bank account "TEST LKR verification account (dummy)", its TEST ledger account 1-6-8-29904 and
-- seven TEST ledger rows 99980301-99980307 (two cheques booked before the first day, a receipt cleared in two parts,
-- three vouchers). Re-running replaces only these dummy rows. Removed by reset-demo-to-r1-snapshot.sql.
INSERT INTO account_posting (P_Code, PName, B_Code, T_Code, H_Code, L_Code) SELECT 29904, 'TEST Bank LKR verification (dummy)', 1, 6, 8, '1-6-8-29904' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM account_posting WHERE L_Code = '1-6-8-29904');
DELETE FROM account_runing WHERE ID BETWEEN 99980300 AND 99980399;
INSERT INTO account_runing (ID, L_Code, Dr, Cr, Discription, Date, Recpt_No, Chk_No, payee, index_no, Cashier, Dt, inv_total) VALUES
 (99980301, '1-6-8-29904', 500000, 0, 'TEST OPENING V (dummy)', '2026-08-01', 0, '', '', '', 'test', '2026-09-06 10:00:00', ''),
 (99980302, '1-6-8-29904', 0, 20000, 'TEST CHEQUE 000201 (dummy)', '2026-08-28', 0, '000201', '', '', 'test', '2026-09-06 10:00:00', ''),
 (99980303, '1-6-8-29904', 0, 30000, 'TEST CHEQUE 000202 (dummy)', '2026-08-29', 0, '000202', '', '', 'test', '2026-09-06 10:00:00', ''),
 (99980304, '1-6-8-29904', 90000, 0, 'RECEIPT - TEST AGENT XYZ (dummy)', '2026-09-02', 0, '', 'TEST AGENT XYZ', '', 'test', '2026-09-06 10:00:00', ''),
 (99980305, '1-6-8-29904', 0, 15000, 'VOUCHER PAYMENT - TEST VENDOR J (dummy)', '2026-09-02', 90011, '', 'TEST VENDOR J', '9910', 'test', '2026-09-06 10:00:00', ''),
 (99980306, '1-6-8-29904', 0, 8000, 'VOUCHER PAYMENT - TEST VENDOR K (dummy)', '2026-09-03', 90012, '', 'TEST VENDOR K', '9911', 'test', '2026-09-06 10:00:00', ''),
 (99980307, '1-6-8-29904', 0, 12000, 'VOUCHER PAYMENT - TEST VENDOR L (dummy)', '2026-09-04', 90013, '', 'TEST VENDOR L', '9912', 'test', '2026-09-06 10:00:00', '');
INSERT INTO tpd_bank_account (entity, bank_name, account_name, account_no, currency, label, ledger_code, is_default, active, external_display, display_digits, note, created_by, created_at)
 SELECT 'Avenir Holidays (Pvt) Ltd', 'TEST BANK (dummy)', 'Avenir Holidays TEST', 'TEST-LKR-0004', 'LKR', 'TEST LKR verification account (dummy)', '1-6-8-29904', 0, 0, 'masked', 4, 'dummy account for the reconciliation r2 verification', 'test bed', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tpd_bank_account WHERE label = 'TEST LKR verification account (dummy)');
SELECT (SELECT id FROM tpd_bank_account WHERE label = 'TEST LKR verification account (dummy)') AS verification_account_id, (SELECT COUNT(*) FROM account_runing WHERE ID BETWEEN 99980300 AND 99980399) AS dummy_rows;
