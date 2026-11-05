-- tf-bankrec-r1: DUMMY data for the reconciliation tests on a TEST BED only (never on the development or live database).
-- Three inactive TEST bank accounts, three TEST bank ledger accounts and a TEST suspense account, TEST ledger rows
-- (IDs 99980001-99980299). Remove nothing else; re-running replaces only these dummy rows.
INSERT INTO account_posting (P_Code, PName, B_Code, T_Code, H_Code, L_Code) SELECT 29901, 'TEST Bank LKR (dummy)', 1, 6, 8, '1-6-8-29901' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM account_posting WHERE L_Code = '1-6-8-29901');
INSERT INTO account_posting (P_Code, PName, B_Code, T_Code, H_Code, L_Code) SELECT 29902, 'TEST Bank USD (dummy)', 1, 6, 8, '1-6-8-29902' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM account_posting WHERE L_Code = '1-6-8-29902');
INSERT INTO account_posting (P_Code, PName, B_Code, T_Code, H_Code, L_Code) SELECT 29903, 'TEST Bank JPY (dummy)', 1, 6, 8, '1-6-8-29903' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM account_posting WHERE L_Code = '1-6-8-29903');
INSERT INTO account_posting (P_Code, PName, B_Code, T_Code, H_Code, L_Code) SELECT 29909, 'TEST Suspense (dummy)', 2, 9, 99, '2-9-99-29909' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM account_posting WHERE L_Code = '2-9-99-29909');
DELETE FROM account_runing WHERE ID BETWEEN 99980000 AND 99989999;
INSERT INTO account_runing (ID, L_Code, Dr, Cr, Discription, Date, Recpt_No, Chk_No, payee, index_no, Cashier, Dt, inv_total) VALUES
 (99980001, '1-6-8-29901', 1000000, 0, 'TEST OPENING (dummy)', '2026-08-01', 0, '', '', '', 'test', NOW(), ''),
 (99980002, '1-6-8-29901', 0, 150000, 'TEST CHEQUE 000123 (dummy)', '2026-08-30', 0, '000123', '', '', 'test', NOW(), ''),
 (99980003, '1-6-8-29901', 0, 50000, 'VOUCHER PAYMENT - TEST HOTEL A (dummy)', '2026-09-01', 90001, '', 'TEST HOTEL A', '9901', 'test', NOW(), ''),
 (99980004, '1-6-8-29901', 0, 20000, 'VOUCHER PAYMENT - TEST SUPPLIER B (dummy)', '2026-09-02', 90002, '', 'TEST SUPPLIER B', '9902', 'test', NOW(), ''),
 (99980005, '1-6-8-29901', 0, 30000, 'VOUCHER PAYMENT - TEST SUPPLIER B (dummy)', '2026-09-02', 90002, '', 'TEST SUPPLIER B', '9902', 'test', NOW(), ''),
 (99980006, '1-6-8-29901', 3000000, 0, 'JOURNAL ENTRY - USD TO LKR (dummy)', '2026-09-03', 0, '', '', '', 'test', NOW(), ''),
 (99980007, '1-6-8-29901', 0, 75000, 'VOUCHER PAYMENT - TEST GUIDE C (dummy)', '2026-09-03', 90003, '', 'TEST GUIDE C', '9903', 'test', NOW(), ''),
 (99980008, '1-6-8-29901', 0, 75000, 'VOUCHER PAYMENT - TEST GUIDE C (dummy)', '2026-09-03', 90003, '', 'TEST GUIDE C', '9903', 'test', NOW(), ''),
 (99980009, '1-6-8-29901', 0, 40000, 'VOUCHER PAYMENT - TEST RESTAURANT E (dummy)', '2026-09-04', 90004, '', 'TEST RESTAURANT E', '9904', 'test', NOW(), ''),
 (99980010, '1-6-8-29901', 0, 12345, 'VOUCHER PAYMENT - TEST JEEP D (dummy)', '2026-09-05', 90005, '', 'TEST JEEP D', '9905', 'test', NOW(), ''),
 (99980101, '1-6-8-29902', 15000000, 0, 'TEST OPENING USD 50,000 @ 300 (dummy)', '2026-08-01', 0, '', '', '', 'test', NOW(), ''),
 (99980102, '1-6-8-29902', 1500000, 0, 'TEST AGENT REMITTANCE (dummy)', '2026-09-02', 0, '', '', '', 'test', NOW(), ''),
 (99980103, '1-6-8-29902', 0, 3000000, 'JOURNAL ENTRY - USD TO LKR (dummy)', '2026-09-03', 0, '', '', '', 'test', NOW(), ''),
 (99980201, '1-6-8-29903', 2000000, 0, 'TEST OPENING JPY 1,000,000 @ 2.00 (dummy)', '2026-08-01', 0, '', '', '', 'test', NOW(), ''),
 (99980202, '1-6-8-29903', 404000, 0, 'TEST JP AGENT (dummy)', '2026-09-04', 0, '', '', '', 'test', NOW(), '');
INSERT INTO tpd_bank_account (entity, bank_name, account_name, account_no, currency, label, ledger_code, is_default, active, external_display, display_digits, note, created_by, created_at)
 SELECT 'Avenir Holidays (Pvt) Ltd', 'TEST BANK (dummy)', 'Avenir Holidays TEST', 'TEST-LKR-0001', 'LKR', 'TEST LKR account (dummy)', '1-6-8-29901', 0, 0, 'masked', 4, 'dummy account for the reconciliation tests', 'test bed', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tpd_bank_account WHERE label = 'TEST LKR account (dummy)');
INSERT INTO tpd_bank_account (entity, bank_name, account_name, account_no, currency, label, ledger_code, is_default, active, external_display, display_digits, note, created_by, created_at)
 SELECT 'Avenir Holidays (Pvt) Ltd', 'TEST BANK (dummy)', 'Avenir Holidays TEST', 'TEST-USD-0002', 'USD', 'TEST USD account (dummy)', '1-6-8-29902', 0, 0, 'masked', 4, 'dummy account for the reconciliation tests', 'test bed', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tpd_bank_account WHERE label = 'TEST USD account (dummy)');
INSERT INTO tpd_bank_account (entity, bank_name, account_name, account_no, currency, label, ledger_code, is_default, active, external_display, display_digits, note, created_by, created_at)
 SELECT 'Avenir Holidays (Pvt) Ltd', 'TEST BANK (dummy)', 'Avenir Holidays TEST', 'TEST-JPY-0003', 'JPY', 'TEST JPY account (dummy)', '1-6-8-29903', 0, 0, 'masked', 4, 'dummy account for the reconciliation tests', 'test bed', NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tpd_bank_account WHERE label = 'TEST JPY account (dummy)');
