# tf-paydesk-r4 — installation report (development system)

`C:\wamp64\www\avenir`, database `avenir` on WAMP (Apache 2.4.65, PHP 8.1.33, MySQL 8.4.7). 11 Oct 2026. A separate local development system, not connected to any external server.

## 1. Backup (before anything)

- **Files:** `C:\wamp64\www\avenir-backup-20261011-before-r4\` — app/ (963 files), public/ (1,481), vendor/ (3,379), .env, composer.*, spark, preload.php, writable/ without cache/session/debugbar. Verified: file counts equal; the 11 files the kit changes byte-identical (md5) to the live copies.
- **Database:** `avenir_backup_20261011` — a server-side copy of `avenir` (217 tables, 13 views, 3 triggers, 372,229 rows, 164 MB). Verified table by table: row counts equal for all 217; `CHECKSUM TABLE` identical for 215; the two that differ are the session tables (contents change while a user is signed in). No financial table differs.
- Restore: files by copying the backup folders back (or `apply.py rollback` for the kit's 29 files); database by phpMyAdmin "Copy database to" or table by table from the backup.

## 2. Installation

- Kit `tf-paydesk-r4.zip` (md5 11039a3e776d335c78e0cd4bb1a060bf, 130 files), unpacked to `Claude outputs\tf-paydesk-r4\`.
- `apply.py apply`: 11 files changed (Routes, BankPaymentCheckController, TourAccountsCheckController, TourAccountsController, TourPaymentDashboardController, UserPermissionFilter, TourRecord, bank_payment_check.php, payment_approval_v2.php, tour_payment_dashboard.php, tour_record.php), 18 files added (TourPaymentMastersController, TourPaymentDeskController, the TourPayments library: Audit, BankAccounts, Batches, Documents, PayDesk, Perms, Schema, SupplierBank; views tpd_bank_accounts, tpd_supplier_bank, tpd_batch, tpd_payables, tpd_remittance, tpd_settings, tpd_sheet, tpd_voucher). Originals kept in `before\20261011-014804`. `apply.py check`: 29 same. Opcache validates timestamps, so no restart was needed.
- **Setup** (Tour Payment Dashboard → Set up now, clicked by Shantha): schema version 6 of 6; 11 tables created (tpd_meta, tpd_bank_account, tpd_supplier_bank, tpd_request_ext, tpd_bank_initiation, tpd_bank_approval, tpd_remittance, tpd_document, tpd_expense, tpd_batch, tpd_audit); 8 menus created (244 Tour payment approval, 245 Supplier Payables, 246 Supplier payment batch — create, 247 Bank payment initiation, 248 Bank payment approver (Paid), 249 Avenir Bank Accounts, 250 Supplier Bank Details, 251 Supplier bank verification) and granted to System Administrator; voucher-number unique index `uq_tpd_voucher_vnum` and update guard trigger `tpd_voucher_vnum_guard` in place.

## 3. Schema changes to the existing database

Additive only: the 11 `tpd_*` tables (all InnoDB), the unique index on `voucher.Vnum`, the trigger on `voucher` (refuses a change of an issued number), 8 rows in `menu_list`, 8 rows in `user_privileges` (group 1), 2 rows in `tpd_meta`. No existing column was altered or removed; no existing row was changed.

## 4. Rollback test on this system (before any payment under the kit)

- `apply.py rollback` (backup 20261011-013052): the 11 changed files restored byte-for-byte (md5 = originals); the 18 added files moved aside to `writable\paydesk_rolled_back_20261011-014729`; the legacy Tour Payment Dashboard and Bank Payment Check pages rendered under the old files.
- Database fingerprints (count | MD5 of all rows) identical before and after the rollback: voucher 7975 | a27dda…, v_inv 8178 | fd9112…, payment_request 3110 | 58ed0b…, account_runing 11364 | 918404…, invoice_no 1 | 75b25c…, tpd_meta 2 | 738fd0…, menu_list 192 | ed2d64…, user_privileges 710 | 53bb3b…, account_posting 1165 | 2f4772….
- Re-apply (backup 20261011-014804): verified, 29 same; Settings page rendered (schema 6/6). The rollback with payments present was tested in the cloud (test 47, 30 checks): records stay, the previous application handles them, re-apply loses nothing.

## 5. Test totals

- Cloud test copy, same kit: **232 of 232** checks (tests 0–46: 202; test 47 rollback: 30). Rerun with the four voucher tables on InnoDB (r5 rehearsal): 202 of 202.
- On this system (read-only or UI checks): preflight — no duplicate voucher number, counter 8020 in step with the table, 5 orphan `v_inv` lines and 2 postings of deleted vouchers 6761/6762 (pre-existing); Step 1B live verification; Supplier Payables listing (3 suppliers, 7 approved payments: Flower Drum ×5, Mr Asanga, L R Thushara; 0 batches); Settings; every payment page renders with no configuration warning after the mappings.

## 6. Configuration completed on this system (approved 11 Oct 2026)

- Accounts created: **Realised Exchange Gain 8-35-111-11808** (Other Income → Foreign Exchange Gain-Loss; the legacy 8-35-111-10780 untouched) and **Realised Exchange Loss 4-8-37-11809** (Expenses → Expenses → Finance Charges).
- Ledger mapping saved (audit note "Approved by Shantha 11 Oct 2026 …"): gain 8-35-111-11808, loss 4-8-37-11809, bank charges **4-8-37-10818** (existing, active, used in FY 2024/25). Three distinct codes; both mapping checks *complete*.
- Role profiles created, menus granted, **no users assigned**: 29 Management / Payment Approver, 30 Accountant (New Generation), 31 Quotation Desk (New Generation), 32 Hotel Reservations Desk, 33 Transport Desk (New Generation), 34 Auditor (read-only) — see `avenir-new-team-roles.md`. Existing logins and groups untouched. Paid (248), bank verification (251) and the bank-account master (249) stay with System Administrator.
- Avenir bank-account master entries (real account numbers) are left for Accounts to enter.

## 7. Warnings and limitations

- Separation of duties is by profile; a user holding both the dashboard (request) and approval or initiation could act on their own item — the same-user guard is proposed for r5.
- The legacy Voucher Cancellation screen answers HTTP 500 on an unauthorised voucher although it does cancel it (pre-existing; identical for kit and legacy vouchers).
- "Reconciled" on the batch page means the ledger postings equal the Paid figures (payments + charges = the bank debit entered). It is not a bank-statement match; that is Bank Reconciliation Step 2, not started. Wording in the r5 screens will say "ledger balanced".
- The legacy writer reads then rewrites the voucher counter; the unique index and the r4 lock protect against duplicates; r5 proposes the atomic writer.
- The four voucher tables and the ledger are MyISAM on this system (Decision A, separate plan).

## 8. Pre-existing accounting irregularities — documented, not modified

Listed in `bank-reconciliation-step1-inspection.md` and `live-verification-2026-10-11.md`: the 15,868,192.50 debit to account "0"; 8 unbalanced entries; future-dated rows (2525, 2037, 2030, 2029 — including one on 8-35-111-10780 dated 2029-03-20); one-sided opening journals; 148 vouchers without a paying account; 5 stranded Flower Drum requests; 5 orphan `v_inv` lines; postings for deleted vouchers 6761/6762; `bank_rec`/`bankst_flag` used only in 2024/25 (5,604 rows) and on 2 current rows. None was touched by the installation, the rollback, the re-apply or the configuration.

## 9. Not done / waiting

Bank Reconciliation Step 2 (on hold). Dashboard redesign (preview approved in principle; the working dashboard unchanged). r5 (cloud test copy only). Staff accounts (when the new team's details arrive).
