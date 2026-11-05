tf-paydesk-r4 — Tour Payment & Accounting Workflow: Supplier Payables, payment-date rate, the four r3 corrections, and the
r4 items — bank-charge accounting, live voucher-number preflight, rollback after payments, final verification (11 Oct 2026)
=================================================================================================================================
Built and tested on the cloud test copy only. NOT installed on live. Install only after Shantha's approval.
This kit contains everything of r1, r2 and r3 (never installed) plus the four items of 11 Oct:
   1. BANK-CHARGE ACCOUNTING — a configurable Bank Charges ledger mapping (an existing active account chosen by Accounts),
      the charges allocated per tour line and posted ONCE at Paid, balanced, and the ledger RECONCILED to the bank debit;
      Paid blocked, before anything is written, while a needed mapping is missing;
   2. LIVE VOUCHER-NUMBER PREFLIGHT — a read-only check (SQL + runner) to run on the live database before installing;
      plus a guard in the desk: the chain's number counter is checked before every voucher is issued;
   3. ROLLBACK AFTER PAYMENTS EXIST — tested with the real installer on a live-equivalent copy, documented with the
      limitation and the recovery procedure (§ 7);
   4. FINAL VERIFICATION — the full suite rerun (tests 0–40) plus tests 41–47; 232 of 232 checks passed (§ 8).

1. THE FLOW (as installed)
--------------------------
   Payment Request → Payment Approval → SUPPLIER PAYABLES (approved payments wait under their supplier)
   → Select and Create Batch (management)  — or —  Pay Individually Now (management, with a reason)
   → one Payment Voucher No. in the accountant's queue → the accountant initiates the bank payment (payment-date rate,
   the ACTUAL paying account, the bank charges if known) → the bank approver confirms the account, the charges and Paid
   (final rate locked; the exchange-rate difference and the bank charges posted per tour line; the ledger reconciled to
   the bank debit) → remittance advice.
   Everything of r3 § 1 stands (approval figures written once; suggestion → selection/confirmation of the paying account
   with reasons and audit; Pay Individually Now with its reason; the official Payment Voucher No.).

2. BANK CHARGES (r4 item 1)
---------------------------
   Mapping — Settings page tour/tour_payment_dashboard/settings (System Administrator, or the Accounts manager = holder
   of the Avenir Bank Accounts privilege): a third field, "Bank Charges — ledger account (expense)", chosen from the chart
   of accounts by search ("use as bank charges"). Only existing, active accounts; the three mapped accounts (gain, loss,
   bank charges) must be different; nothing is invented — Accounts creates or approves the account first. Every save is
   an audit entry (ledger_mapping_saved / fx_mapping_saved, previous and new). POST equivalent: setup with
   bank_charge_ledger_code (only the keys posted are changed).
   Entering the charges — the accountant may enter them at bank initiation (they are carried to the final approval
   page); the bank approver confirms or corrects them at Paid. Negative charges are refused. 0.00 = the bank charged
   nothing.
   Allocation — on a multi-tour batch the charges are split across the tour lines PRO RATA TO EACH LINE'S FINAL LKR, to
   the cent, by the largest-remainder rule, so the shares always add up exactly to the charges (75.55 over 3,200 / 1,800
   → 48.35 / 27.20; 300.00 over two equal lines → 150.00 / 150.00; one line takes all). The share is previewed live on the
   batch page ("Charges share" column) and, at Paid, written once to each request's record (tpd_request_ext.charge_share)
   — it appears in each tour's payment record: the Tour Payment Dashboard line details ("bank charges … this tour's share
   …, debited for this tour …"), the Complete Tour Record column "Bank charges (this tour's share)", the voucher document
   (per-line Charges share) and the batch page.
   Posting — at Paid, ONE BALANCED PAIR PER TOUR LINE: Dr Bank Charges (the line's share) / Cr the Avenir paying account
   (the same share), carrying the tour (Invoice_Num / index_no), account_runing advances_pay 24, description "Bank
   charges Payment Voucher No. N / AH xxxx (share a of b)". Posted ONCE per voucher (tpd_bank_approval.charges_posted,
   charge_entries; tpd_request_ext.charge_entry_id); audit entry charges_posted. Charges of 0.00 post nothing.
   Reconciliation — after the postings the desk reads the voucher's ledger back (the chain's authorisation rows by
   Recpt_No = Payment Voucher No., the charge and exchange pairs by their entry ids) and checks: payment credited to the
   paying account = final LKR; charges credited to the paying account = charges; charges debited to Bank Charges =
   charges, recorded once; TOTAL credited to the paying account = TOTAL THE BANK DEBITED (final LKR + charges); every
   entry Dr = Cr; the exchange difference posted in full. The result is written on the lock (ledger_bank_credit,
   reconciled) and the audit (ledger_reconciled), shown on the batch page ("Ledger reconciliation to the bank debit" with
   every row and every check), on the voucher document and in the dashboard line details. tests/sample-postings.txt shows
   four real examples read back from the test copy.
   Blocking — the Paid request validates everything BEFORE writing anything: voucher state, bank reference, exchange
   mapping (USD), charges ≥ 0, BANK-CHARGE MAPPING when charges > 0, the paying account's ledger account, the account
   confirmation and reason, the payment-date rate. A missing mapping answers 409 with the configuration warning
   (config_warning) and the page shows the warning and disables Paid; no voucher, lock, ledger row or request record is
   written (verified by test 43 on USD and on LKR payments). A payment the bank charged nothing for never needs the
   mapping.
   One Paid at a time — the Paid request takes a database named lock per voucher (GET_LOCK); a repeated or simultaneous
   submission waits and then meets the "already Paid" refusal (409). The chain's tables are MyISAM (no transactions), so
   this lock plus the validate-then-write order is what prevents a partial or double posting (test 46: two servers, two
   sessions, the same instant → one 200, one 409, one set of postings).

3. THE VOUCHER NUMBER (r4 item 2)
---------------------------------
   As r3: voucher.Vnum is the official Payment Voucher No.; Setup adds the unique index and the update guard (DB-level
   refusal of a duplicate / an edit); labelled everywhere.
   PREFLIGHT (read-only, before installing): preflight/preflight_vnum.sql — SELECT/SHOW statements only — checks the
   voucher table (duplicates, NULL/zero/negative numbers, min/max), the COUNTER (invoice_no.V_Num must equal MAX(Vnum):
   a counter BEHIND the table would hand out an issued number), gaps (information only), orphan lines, ledger references,
   whether the index/trigger already exist, the database user's grants, the server version, binary logging and
   log_bin_trust_function_creators (a trigger on a server with binary logging and no SUPER privilege needs that setting
   = 1 — if the trigger cannot be created, Setup says so and the index alone stands; the application never rewrites a
   number anyway), and whether any tpd_ table exists. preflight/preflight_vnum.py runs the same file against the database
   named in the application's .env (the password is read from .env by the script and never printed). The findings are
   reported to Shantha before "apply"; nothing is renumbered or altered. The code of the live application was also scanned:
   every 'Vnum' write is an INSERT at voucher creation (24 places); no code UPDATEs the number — the update guard is
   transparent to the existing screens (tests 40, 47 C10–C11).
   THE GUARD IN THE DESK: before a batch issues a voucher the desk takes a named lock (one voucher issued at a time) and
   checks that invoice_no.V_Num + 1 is not an issued number. If the counter fell behind the table (a restored backup, a
   manual edit), the batch is refused with a clear message naming the counter and the last number — NOTHING is written —
   because the chain's own creation code does not check its insert: with the unique index refusing the duplicate voucher,
   the chain would still attach the lines to the EXISTING voucher of that number and flag the requests. After the call
   the desk verifies the voucher row it issued exists; if not, this call's own lines and flags are taken back (by id) and
   the failure recorded (voucher_issue_failed). Remedy when the preflight or the message shows a counter behind the table:
   Accounts sets invoice_no.V_Num = MAX(voucher.Vnum) (test 45).

4. TABLES, COLUMNS, PRIVILEGES (Setup creates them; schema version 6)
---------------------------------------------------------------------
   NEW in r4 (columns): tpd_request_ext charge_share, charge_entry_id · tpd_bank_approval charges_posted, charge_entries,
                        ledger_bank_credit, reconciled
   NEW in r4 (settings): tpd_meta bank_charge_ledger_code
   As r3: tpd_batch individual/bypass/suggested account; fx_entries, account_confirmed_by, fx_entry_id; the unique index
   uq_tpd_voucher_vnum and trigger tpd_voucher_vnum_guard on voucher; tpd_meta fx_gain_ledger_code, fx_loss_ledger_code.
   As r2/r1: the eleven tpd_ tables, the approval_* / paid_* columns, eight privileges. No existing row is altered.
   schema-reference.sql shows the tables as Setup leaves them.

5. FILES
--------
New (18):   app/Libraries/TourPayments/{Schema,Perms,Audit,PayDesk,BankAccounts,SupplierBank,Documents,Batches}.php
            app/Controllers/Tour/TourPaymentDeskController.php        app/Controllers/MasterFiles/TourPaymentMastersController.php
            app/Views/pages/tour/{tour_payment_dashboard (rewritten), tpd_payables, tpd_batch, tpd_settings, tpd_voucher, tpd_remittance, tpd_sheet}.php
            app/Views/pages/master_files/{tpd_bank_accounts, tpd_supplier_bank}.php
Changed (11, each change marked "tf-paydesk-r1" / "r2" / "r3" / "r4" in the code) — as r3 § 5:
   TourAccountsCheckController.php, TourPaymentDashboardController.php, TourAccountsController.php,
   BankPaymentCheckController.php, bank_payment_check.php, payment_approval_v2.php, TourRecord.php, tour_record.php
   (r4: the bank-charge share column), Routes.php, UserPermissionFilter.php, tour_payment_dashboard.php.
r4 touched (within the 29): Schema.php, Batches.php (chargeMapping, allocate, chargeLines, postCharges, reconcile,
   recordReconciliation), PayDesk.php (charge_share / tour_total_debit on each transaction), Audit.php (labels),
   TourPaymentDeskController.php (paid(): named lock, validations before writes, postings, reconciliation; initiate():
   charges validated, mapping warning; setup(): the three mappings; batch_create(): the voucher-number guard),
   tpd_settings.php, tpd_batch.php, tpd_voucher.php, tour_record.php, tour_payment_dashboard.php.
Not changed: VoucherPaymentController (ledger posting), the request / special-rate / rate-review code of the dashboard
(kept verbatim), every existing table's rows. apply.py refuses to run if a live file changed since the kit was tested
(TESTED_ON checksums of the 11 files), and gives every apply its own backup folder.

6. INSTALL (after approval)
---------------------------
   0. PREFLIGHT (read-only): run preflight/preflight_vnum.sql on the live database (phpMyAdmin → SQL, or
      python3 preflight/preflight_vnum.py "C:\wamp64\www\avenir\.env") and send the output to Shantha / Claude.
      Go on only if: no duplicated / NULL / zero Vnum; counter_minus_max = 0 (else Accounts corrects the counter first);
      no tpd_ table present (a fresh install). Trigger privilege / log_bin_trust_function_creators as reported.
   1. BACKUP: the database (mysqldump) and the folder C:\wamp64\www\avenir (or at least app/).
   2. python3 apply.py check  "C:\wamp64\www\avenir"      nothing is written; refuses if a live file changed since testing
      python3 apply.py apply  "C:\wamp64\www\avenir"      backs up the 11 changed files to before/<time>/, copies the 29 files
      then restart Apache (WAMP → Restart All Services) so that no cached copy of a changed file is served (opcache).
   3. Signed in as System Administrator: Tour Payment Dashboard → "Set up now" (= POST tour/tour_payment_dashboard/setup):
      creates the tpd_ tables, the new columns, the eight privilege entries, the unique index and update guard on
      voucher.Vnum. Idempotent. From this moment approvals wait in Supplier Payables.
   4. Grant the privileges (Settings → Group Privileges; users log out and in once) — the eight of r3 § 6:
        Tour payment approval (management) · Supplier payment batch — create (management) · Supplier Payables ·
        Bank payment initiation (accountant) · Bank payment approver (Paid) · Avenir Bank Accounts · Supplier Bank Details ·
        Supplier bank verification.
   5. Master Files → Avenir Bank Accounts (the company accounts, each tied to its ledger account, one default, the
      currency right — only LKR accounts can pay a voucher); Master Files → Supplier Bank Details (accounts, verification,
      remittance e-mails).
   6. ACCOUNTS creates or approves THREE accounts in the chart of accounts — Realised Exchange Gain, Realised Exchange
      Loss, Bank Charges — and the System Administrator or the Accounts manager maps them: Tour Payment Dashboard →
      Settings. Until then: a foreign-currency Paid is blocked (gain/loss unmapped); a Paid with bank charges is blocked
      (Bank Charges unmapped). LKR payments without charges are not affected.
   IMPORTANT once installed: Mark Finalized needs the "Bank payment approver (Paid)" privilege, and a batch voucher is
   paid on its batch page, with the paying account and the charges confirmed. Until the privileges are granted, only the
   System Administrator can create batches, pay individually or mark a payment paid.

7. ROLLBACK (r4 item 3) — before any payment, and after payments exist
----------------------------------------------------------------------
   python3 apply.py rollback "C:\wamp64\www\avenir"   then restart Apache.
   What it does: puts the 11 original files back byte-for-byte (from the backup of the newest apply) and moves the 18
   new files aside to writable/paydesk_rolled_back_<time>/ (kept, not deleted). It does NOT touch the database: every
   voucher, voucher line, request, ledger row, batch, initiation, Paid lock, approval figure, account-change entry, FX
   entry, charge entry and audit row stays exactly as it was (test 47 C2 compares every table byte-for-byte). The tpd_
   tables, columns, settings and menu rows stay (unused); the unique index and the trigger on voucher.Vnum stay (they
   only refuse a duplicated / edited number; the previous application never does either) — to remove them by hand:
   DROP INDEX uq_tpd_voucher_vnum ON voucher; DROP TRIGGER tpd_voucher_vnum_guard;
   Rollback BEFORE any payment (test 47 A): the files are back; the only remnants are the empty desk tables, the menu rows
   and the index/trigger. Nothing else to do.
   Rollback AFTER payments exist (test 47 B–D) — what the PREVIOUS application does with the records:
     · Vouchers paid through the desk are ordinary vouchers to the old application: Aflag 1 (posted by its own
       authorisation step), finalized = 1 with the bank date — Bank Payment Check's "Finalized records" lists them with
       their bank dates; the voucher report, Liability report, Accounts filtering, Trial balance, Bank statement, Complete
       Tour Record and the dashboard show them (the dashboard says "Fully Paid"). The exchange-difference and bank-charge
       rows are ordinary balanced ledger rows the old reports read.
     · Requests approved under the desk, not yet batched (no voucher yet): the old application shows them as approved
       ("Pending Payment") but its Bank Payment Check cannot see them — THIS IS THE LIMITATION. They are not lost: they
       wait in the database and reappear in Supplier Payables the moment the kit is applied again (test 47 D4).
     · Batches initiated but not yet Paid (voucher Aflag 0, Bank set): the old Bank Payment Check lists the voucher; it can
       be authorised and Mark Finalized the old way (the amount is the one re-rated at initiation); the exchange
       difference and the bank charges would NOT be posted by the old application — Accounts posts them by journal if
       the kit is not coming back.
     · New payments during the rollback window go the old way: approval creates the voucher at once with the next number
       (the index accepts it while the counter is in step), authorisation and Mark Finalized work, the old voucher cancel
       works (test 47 C9–C11). When the kit is applied again, such vouchers show on the desk as legacy vouchers, not as
       batches (D5).
   RECOVERY PROCEDURE for the stranded approved-but-unbatched items (only if the kit is NOT coming back):
     1. list them (read-only):
          SELECT pr.id, pr.tour_id, c.manual_id, pr.hotel_acc, pr.hotel_name, pr.type, pr.requestAmount, pr.accept_user, pr.accept_date
          FROM payment_request pr LEFT JOIN consignee c ON c.tour_id = pr.tour_id
          LEFT JOIN voucher v ON v.Acc_No = pr.hotel_acc AND v.ref_po = pr.tour_id AND v.C_Date >= pr.accept_date
          WHERE pr.payment_request IN (4,6) AND pr.voucher_created = 0 AND v.ID IS NULL;
     2. for Hotel and Transport Advance items: Accounts sets payment_request = 1 on those ids (they reappear on Payment
        Approval) and approves them the old way — the voucher is created at once. The hotel accrual was posted at request
        time by both flows, so it is not duplicated.
     3. for lunch / service / expense items: do NOT re-approve (the old approval would post the service accrual a second
        time). Raise the voucher on Accounts → Voucher Payments against the supplier for the approved amount, or decide the
        item by journal — an Accounts decision, recorded there.
     4. Settings → Group Privileges: the eight desk privileges may be removed from the groups (the menu rows are hidden
        anyway once the routes are gone).
   Nothing in this procedure deletes financial history.

8. TESTS (cloud test copy, database avenir_test; AH 2218 "NEMOTO / REIKO MS" and AH 2290 "TANAKA / YUKI MS")
-------------------------------------------------------------------------------------------------------------
tests/flow_test.py runs tests 0–30 (r1/r2), the ten retests 31–40 (r3) and the r4 tests 41–46 through the real
endpoints as four role accounts; tests/rollback_test.py runs test 47 against a LIVE-EQUIVALENT copy (the live files as
tested, no kit, its own database avenir_rb in which the kit was never installed) with the real installer.
   232 checks, 232 passed (tests/results.txt by test number; results.json / results_rollback.json raw):
   166 in tests 0–40 (the 165 of r3, all unchanged and passing, plus one new balance check in test 30), 36 in tests
   41–46, 30 in test 47.
   41 ZERO charges: Kassapa part 1 (USD) and the Gimanhala lunch (LKR) — nothing posted to Bank Charges, charges 0.00
      on the lock, reconciled (credits to the paying account = the bank debit)
   42 NONZERO charges: COVENRO 150.00 (one pair Dr 4-8-109-31903 / Cr 1-6-8-10380, carrying the tour; the request
      record carries 150.00; reconciled 17,998.75 + 150.00 = 18,148.75; audit charges_posted + ledger_reconciled);
      Kassapa part 2 200.00 against the Sampath account; the driver advance 75.00 taken from the initiation
   43 MISSING mapping: with the Bank Charges account unmapped a Paid carrying charges is refused (409, config_warning)
      on a USD payment and on an LKR payment, and NOTHING is written (voucher, lock, ledger and request records compared
      before/after); charges 0.00 never need the mapping; negative charges refused; the loss account cannot double as
      the charges account; a non-existent code refused; the Accounts manager maps 4-8-109-31903 (audit
      ledger_mapping_saved); the page shows the warning and the per-tour share
   44 MULTI-TOUR allocation: Cassamara 300.00 over two equal lines → 150.00 / 150.00, two pairs, each tagged to its tour,
      each tour's dashboard record shows its share and "debited for this tour" 19,896.00, the voucher document shows the
      shares; Gimanhala 75.55 over 3,200 / 1,800 → 48.35 / 27.20 (previewed before Paid, posted at Paid, reconciled
      5,075.55, both tours' records and the Complete Tour Record show their shares); the allocation rule unit-tested on
      the awkward cases (100.01, three equal lines, 0.01, no basis, one line, zero) — every sum exact
   45 DUPLICATE voucher attempts: the counter in step with the table; with the counter one behind, creating a batch is
      refused before anything is written (no voucher, no batch, no line, the request still waiting, the ledger untouched);
      with the counter corrected the next number is issued cleanly; (test 40 still: a raw duplicate insert and an edit of
      the number are refused by the database)
   46 REPEATED / CONCURRENT Paid: two submissions at the same instant from two servers and two sessions → exactly one
      200 and one 409, one lock, the payment posted once, the charges posted once, reconciled; a third and a fourth
      submission refused; the old Mark Finalized refused too
   47 ROLLBACK: A apply → check → rollback (files back byte-for-byte, added files moved aside) → apply again; B Setup on a
      never-installed database, masters, mapping, COVENRO paid with charges, a driver advance paid individually, Kassapa
      approved and waiting; C rollback → database byte-for-byte unchanged; index/trigger remain; the old application
      opens every page (dashboard, Bank Payment Check, Finalized records, voucher report, Liability, Trial balance,
      Complete Tour Record, Bank statement, Accounts filtering), lists both paid vouchers with bank dates, shows the
      statuses, reads the payment / charge rows; a new request approved the old way gets the next number, is authorised,
      Mark Finalized and cancelled the old way; the stranded item demonstrated and listed by the recovery query;
      D apply again + Setup → desk tables byte-for-byte as before (audit +1 "setup"), the legacy rows kept, the paid batch
      with its reconciliation, the stranded item back in Supplier Payables, the legacy voucher shown as a legacy voucher.
      (Payment Approval was not opened under the old files in C: the live page's raw query uses the MySQL 8 collation
      utf8mb4_0900_ai_ci, which the MariaDB test server lacks — a test-server artefact, unchanged by the kit.)
The mail of test 12 went to a local test sink — nothing left the machine. Test data (tests/setup_testdata.py, test
copy only): the four role accounts, the second tour, the rates of the two earlier days, three TEST ledger accounts
(4-8-109-31901/31902/31903 — gain, loss, bank charges) for the mapping, and a TourFlow tour linked to AH 2218.
tests/sample-postings.txt: four real reconciliations read back from the ledger (two-tour USD with loss and charges;
two-tour LKR with unequal shares; USD with gain from the second account; same-day USD with charges).

9. DECISIONS AND WARNINGS FOR SHANTHA
-------------------------------------
 a–i of r3 stand (who may Pay Individually Now; the account revalidation; the TEST accounts exist on the test copy only;
    the index/trigger are the first change to an existing table, reversible; Voucher_No left alone; the Complete Tour
    Record link; the accumulation applies to every approved payment; the known console errors). Decision (c) of r3 is
    superseded: bank charges ARE posted now, to the mapped Bank Charges account.
 j. Charges posted per tour line (Dr Bank Charges / Cr paying account, one pair per tour) rather than one pair per
    voucher — so that each tour's record and the supplier/tour drill-downs carry the tour's share, and the bank ledger
    stays in the same per-line pattern the chain's authorisation step already uses. The total on the bank ledger is the
    same either way.
 k. The bank approver has the last word on the charges (what the bank actually took); the accountant's figure is a
    carry-over. A charge known only after Paid cannot be added to the lock (locked forever): Accounts posts it by journal
    (Dr Bank Charges / Cr the bank) — the lock's total_debit then differs from the bank by that amount, visibly.
 l. The reconciliation compares the ledger with the lock, not with a bank statement — the statement side is the Daily Bank
    Reconciliation module (separate, inspection stage).
 m. On a live server with binary logging and no SUPER privilege for the application's database user, the trigger needs
    log_bin_trust_function_creators = 1 (RDS parameter group) — the preflight reports it; without the trigger the index
    alone stands and the application still never rewrites a number.
 n. After apply or rollback, restart Apache: PHP's opcache may otherwise serve a cached copy of a changed file for a
    short while (seen on the test server as a 404 right after a rollback).
 o. The rollback limitation (§ 7) is inherent to going back to a flow that creates the voucher at approval: an approval
    made under the desk has, by design, no voucher yet. Re-applying the kit is always the cleaner recovery.
