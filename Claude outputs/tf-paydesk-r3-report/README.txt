tf-paydesk-r3 — Tour Payment & Accounting Workflow: Supplier Payables, payment-date rate, and the four corrections (10 Oct 2026)
=================================================================================================================================
Built and tested on the cloud test copy only. NOT installed on live. Install only after Shantha's approval.
This kit contains everything of r1 and r2 (never installed) plus the four corrections of 10 Oct:
   1. the final Avenir paying account is selected or confirmed by the accountant and confirmed by the bank approver;
   2. Pay Individually Now — an urgent-payment route that creates a single-item batch with a recorded bypass reason;
   3. the Realised Exchange Gain / Loss ledger mapping, balanced per-tour postings at Paid, Paid blocked while unmapped;
   4. the official Payment Voucher No. — the voucher's own number, unique, immutable, labelled everywhere.

1. THE FLOW (as installed)
--------------------------
   Payment Request → Payment Approval → SUPPLIER PAYABLES (approved payments wait under their supplier)
   → Select and Create Batch (management)  — or —  Pay Individually Now (management, with a reason)
   → one Payment Voucher No. in the accountant's queue → the accountant initiates the bank payment (payment-date rate,
   the ACTUAL paying account) → the bank approver confirms the account and Paid (final rate locked; the exchange-rate
   difference posted per tour line) → remittance advice.

   Request payment        requester      as r2: USD at today's confirmed rate (kept as "requested at"); LKR never converted.
   Approve payment        approver       as r2: no voucher, no paying account; the APPROVAL FIGURES are written once and
                                         never overwritten (locked USD, rate shown at approval, provisional LKR, date, user).
                                         r3: a holder of the batch privilege may tick "Pay individually now" in the approval
                                         window — the approval is followed at once by a single-item batch (see below).
   Supplier Payables      payables       as r2: the approved payments by supplier; a Pay now button per item (batch privilege).
   Create batch           batch          as r2: the supplier's approved payments (any tours) → ONE voucher, in the accountant's
                                         queue. r3: the Avenir account chosen here is management's SUGGESTION, recorded on
                                         the batch; the voucher pays from it until the accountant confirms the actual one.
   Pay Individually Now   batch          r3: exactly one approved payment → a single-item batch at once, with the reason
                                         the accumulation stage is bypassed — Driver advance · Driver settlement · Urgent
                                         cash payment · Pay-at-gate item · Emergency supplier payment · Other authorised
                                         payment (a note is required for "Other"). Recorded on the batch (individual,
                                         bypass_reason, bypass_note), shown on the dashboard, the batch page and Supplier
                                         Payables, and written to the audit history as accumulation_bypassed. The payment
                                         still follows approval → voucher → initiation → Paid; nothing is skipped but the
                                         wait. The driver advance of the test tour takes this route (driver_advance).
   Bank payment page      accountant     as r2 (payment-date rate, LKR calculated, charges, total debited, difference).
                                         r3: the accountant SELECTS OR CONFIRMS the actual paying account — preselected
                                         with management's suggestion. A different account needs a reason; the account is
                                         revalidated (active on the master, tied to an active ledger account, LKR for an
                                         LKR payment — a USD account is refused); the change is written to the audit
                                         history (bank_account_changed: original and new account, user, date, time,
                                         reason, "revalidated"); the voucher's paying ledger follows while unposted.
                                         Confirming the suggested account is recorded too (account_confirmed).
   Final approval · Paid  bank approver  as r2 (the rate of the final payment date, locked). r3: the page shows the account
                                         the payment was initiated from, management's suggestion and every change; the
                                         approver must tick "I confirm the paying account" (Paid is refused otherwise); a
                                         change at this step needs a reason and is recorded the same way, and is refused
                                         once the ledger has been posted from the other account. On Paid the exchange-rate
                                         difference is POSTED (see 3). The lock records who confirmed the account.
   Remittance advice      requester      as r2, with the official Payment Voucher No.

2. THE EXCHANGE GAIN / LOSS LEDGER MAPPING (correction 3)
---------------------------------------------------------
   Settings page: tour/tour_payment_dashboard/settings (System Administrator, or the Accounts manager = holder of the
   Avenir Bank Accounts privilege). Two fields — Realised Exchange Gain, Realised Exchange Loss — each a ledger account
   chosen from the chart of accounts by searching it; only existing, active accounts can be mapped, the two must differ,
   and NOTHING IS INVENTED: Accounts creates or approves the accounts first. Every save is an audit entry
   (fx_mapping_saved, previous and new). The POST equivalent: tour/tour_payment_dashboard/setup with fx_gain_ledger_code
   and fx_loss_ledger_code.
   At final Paid of a foreign-currency batch, for EVERY tour line: final LKR − provisional LKR
      > 0 (the rate rose)  → Dr Realised Exchange Loss / Cr the supplier's ledger account
      < 0 (the rate fell)  → Dr the supplier's ledger account / Cr Realised Exchange Gain
   one balanced pair per line, carrying its own tour (Invoice_Num / index_no), the rate and the USD amount
   (account_runing, advances_pay 23, description "Exchange loss/gain Payment Voucher No. N / AH xxxx (USD … @ a → b)").
   The entry ids are kept on the Paid lock (fx_entries) and on each request (fx_entry_id); the audit entry fx_posted lists
   every line. The approval-rate and final-rate figures stay side by side (tpd_request_ext approval_* and paid_*).
   If the mapping is missing or invalid, the final Paid of a foreign-currency payment is BLOCKED (409 with the
   configuration warning, the Paid button disabled on the page, the warning shown to the bank approver) — no incomplete
   accounting entry is ever written. LKR-only payments are not affected. A difference of 0.00 posts nothing.
   Bank charges stay recorded on the desk (the chain never posted them) — see decision (c).

3. THE OFFICIAL PAYMENT VOUCHER No. (correction 4)
--------------------------------------------------
   Rule confirmed: the existing voucher.Vnum IS the official Payment Voucher No. — one per voucher, taken from the
   chain's counter when the batch issues the voucher, never rewritten by any code, and the number Bank Payment Check and
   the ledger postings already use. (voucher.Voucher_No was never written by the existing authorisation step; it is
   left alone.) On this install the 613 existing vouchers all carry distinct numbers.
   Setup adds what the table never had — a UNIQUE index on voucher.Vnum (a duplicated number is refused by the database)
   and a BEFORE UPDATE trigger (a change to an issued number is refused: "Payment Voucher No. (Vnum) cannot be changed
   once issued"). Both are additive and reversible (DROP INDEX / DROP TRIGGER); no row is touched. If the database ever
   held duplicates, Setup would not add the index and would say so; if the database user lacked the TRIGGER privilege,
   Setup would say so and the index alone would stand.
   Labelled "Payment Voucher No." on: the Tour Payment Dashboard (lines, details, history), Supplier Payables (batches),
   the supplier batch page, the payment voucher page and PDF, the accountant's and the bank approver's page, the Paid
   lock, the remittance advice (page, PDF, e-mail subject and body), Bank Payment Check's batch link, the Complete Tour
   Record (new section "7 · Supplier payments"), the export sheet and the audit history.
   A consolidated supplier batch has ONE Payment Voucher No.; every supplier invoice number of the batch is listed
   separately on the voucher and the advice. The desk never re-issues: batching an already-vouchered payment is refused.

4. TABLES, COLUMNS, PRIVILEGES (Setup creates them; schema version 5)
---------------------------------------------------------------------
   NEW in r3 (columns):  tpd_batch individual, bypass_reason, bypass_note, suggested_bank_account_id, suggested_ledger_code
                         tpd_bank_approval fx_entries, account_confirmed_by · tpd_request_ext fx_entry_id
   NEW in r3 (on the existing voucher table): unique index uq_tpd_voucher_vnum, trigger tpd_voucher_vnum_guard (see 3)
   NEW in r3 (settings): tpd_meta fx_gain_ledger_code, fx_loss_ledger_code
   As r2: tpd_batch; tpd_request_ext approval_* / paid_* / fx_diff / batch_id / vinv_id; tpd_bank_initiation and
   tpd_bank_approval payment-date columns; privileges Supplier Payables and Supplier payment batch — create.
   As r1: the ten tpd_ tables and six privileges. No existing row is altered by Setup.

5. FILES
--------
New (18):   app/Libraries/TourPayments/{Schema,Perms,Audit,PayDesk,BankAccounts,SupplierBank,Documents,Batches}.php
            app/Controllers/Tour/TourPaymentDeskController.php        app/Controllers/MasterFiles/TourPaymentMastersController.php
            app/Views/pages/tour/{tour_payment_dashboard (rewritten), tpd_payables, tpd_batch, tpd_settings, tpd_voucher, tpd_remittance, tpd_sheet}.php
            app/Views/pages/master_files/{tpd_bank_accounts, tpd_supplier_bank}.php
Changed (11, each change marked "tf-paydesk-r1" / "r2" / "r3" in the code):
   TourAccountsCheckController.php     approval raises no voucher (accruals as before); create_bulk_voucher batch mode
   TourPaymentDashboardController.php  public dashboardData(); exact Tour ID match first; privileges to the page
   TourAccountsController.php          the duplicate-request rule; the desk's record of each request
   BankPaymentCheckController.php      Mark Finalized guards (bank approver; a batch voucher is paid on its batch page); batch links
   bank_payment_check.php (view)       initiations and the "Payment Voucher No. · batch" links; no Mark Finalized for a batch voucher
   payment_approval_v2.php (view)      a banner linking to Supplier Payables (nothing else)
   TourRecord.php (library)            r3: the tour's supplier payments added to the Complete Tour Record's data
   tour_record.php (view)              r3: section "7 · Supplier payments" (Amendments and History become 8 and 9)
   Routes.php                          the new routes (… settings)
   UserPermissionFilter.php            Tour Payment Dashboard holders may call tour_accounts' request endpoints
Not changed: VoucherPaymentController (ledger posting), the request / special-rate / rate-review code of the
dashboard (kept verbatim), every existing table's rows.

6. INSTALL (after approval)
---------------------------
   python3 apply.py check  "C:\wamp64\www\avenir"      nothing is written; refuses if a live file changed since testing
   python3 apply.py apply  "C:\wamp64\www\avenir"      backs up the 11 changed files to before/<time>/, copies the 29 files
   then, signed in as System Administrator: open Tour Payment Dashboard → "Set up now" (= POST tour/tour_payment_dashboard/setup):
        creates the tpd_ tables, the new columns, the eight privilege entries, the unique index and update guard on
        voucher.Vnum. Idempotent; see schema-reference.sql. From this moment approvals wait in Supplier Payables.
   then grant the privileges (Settings → Group Privileges; users log out and in once):
        Tour payment approval (management)              tour/tour_payment_dashboard/approve   → Management
        Supplier payment batch — create (management)    tour/tour_payment_dashboard/batch     → Management (batches and Pay Individually Now)
        Supplier Payables                               tour/tour_payment_dashboard/payables  → anyone who should see the queue
        Bank payment initiation (accountant)            report/bank_payment_check/initiate    → Accounts (or hold Bank Payment Check)
        Bank payment approver (Paid)                    report/bank_payment_check/approve     → the one person who confirms transfers
        Avenir Bank Accounts                            master_files/bank_accounts            → Accounts manager (also maps the gain/loss accounts)
        Supplier Bank Details                           master_files/supplier_bank            → Accounts
        Supplier bank verification                      master_files/supplier_bank/verify     → the person who checks suppliers' accounts
   then Master Files → Avenir Bank Accounts: add the company accounts (each tied to its ledger account, one default)
        Master Files → Supplier Bank Details: enter / verify the suppliers' accounts and remittance e-mails
   then ACCOUNTS creates (or approves) the Realised Exchange Gain and Realised Exchange Loss accounts in the chart of
        accounts, and the System Administrator or the Accounts manager maps them: Tour Payment Dashboard → Settings.
        Until then every foreign-currency Paid is blocked with the configuration warning (LKR payments are not).
   ROLLBACK:  python3 apply.py rollback "C:\wamp64\www\avenir"   puts the 11 files back and moves the 18 new files aside.
        The tpd_ tables, columns, settings and menu rows stay (unused) so that no record is lost. The unique index and
        the trigger on voucher.Vnum also stay (they only refuse duplicates and edits of the number); to remove them by
        hand: DROP INDEX uq_tpd_voucher_vnum ON voucher; DROP TRIGGER tpd_voucher_vnum_guard;
   IMPORTANT once installed: Mark Finalized needs the "Bank payment approver (Paid)" privilege, and a batch voucher is
        paid on its batch page, with the paying account confirmed. Until the privileges are granted, only the System
        Administrator can create batches, pay individually or mark a payment paid.

7. TESTS (cloud test copy, database avenir_test; AH 2218 "NEMOTO / REIKO MS" and AH 2290 "TANAKA / YUKI MS")
-------------------------------------------------------------------------------------------------------------
tests/flow_test.py runs tests 1–30 of r1/r2 and the ten retests (31–40) through the real endpoints as four role
accounts (tcpayreq requester, tcpayappr approver + batch, tcaccount accountant + Accounts manager, tcbankappr bank
approver): 165 checks, 165 passed (tests/results.json, results.txt by test number). Of the earlier 134 checks, 133 are
unchanged and pass; one was rewritten — the old "no ledger posting yet" check of test 30 is now the posting check.
   31 management suggests Com Bank; the accountant selects Sampath: without a reason refused (428); with a reason the
      change is recorded (original → new, user, time, reason, revalidated) and the voucher pays from Sampath; a USD
      account is refused for an LKR payment
   32 the bank approver sees the final account beside the suggestion and the change history; Paid without confirming
      the account refused (428); a change at Paid (Sampath → Com Bank) needs a reason and is recorded; the ledger
      posted from the confirmed account; the lock names who confirmed
   33 the driver advance (LKR 20,000) uses Pay Individually Now: a single-item batch at once, "Driver advance" recorded
      on the batch and in the audit (accumulation_bypassed), the advance voucher in the accountant's queue
   34 an urgent cash payment (Gimanhala) creates a single-item batch; no reason → refused; "Other" without a note →
      refused; two items → refused; the accountant cannot use it (403)
   35 exchange GAIN −287.00 (Kassapa part 2) posted Dr supplier / Cr 4-8-109-31901 (TEST Realised Exchange Gain)
   36 exchange LOSS +129.50 (Kassapa part 1) posted Dr 4-8-109-31902 (TEST Realised Exchange Loss) / Cr supplier
   37 with the mapping removed, the final foreign-currency Paid is blocked (409, configuration warning on the page);
      a requester cannot map; a non-existent code and the same code for both are refused; the Accounts manager maps
      the two TEST accounts (audit fx_mapping_saved) and the Paid proceeds
   38 the two-tour Cassamara batch: +246.00 posted for AH 2218 and +246.00 for AH 2290, one balanced pair each,
      Dr = Cr over all postings, each request tied to its entry
   39 the same Payment Voucher No. on the dashboard (both tours), Supplier Payables, the batch page, the voucher
      page/PDF, the bank approval record, the remittance advice, the supplier e-mail, the Complete Tour Record and the
      audit history; one number for the consolidated batch, both invoices listed; all 613+ vouchers distinct
   40 inserting a second voucher with the same number fails at the database (unique index); changing an issued
      number fails (update guard); re-batching a vouchered payment refused; other voucher columns still update
The mail of test 12 went to a local test sink — nothing left the machine. Test data (tests/setup_testdata.py, test
copy only): the four role accounts, the second tour, the rates of the two earlier days, two TEST ledger accounts
(4-8-109-31901/31902) for the mapping, and a TourFlow tour linked to AH 2218 for the Complete Tour Record.

8. DECISIONS AND WARNINGS FOR SHANTHA
-------------------------------------
 a. Who may Pay Individually Now: holders of "Supplier payment batch — create (management)" — the same privilege as
    batch creation. Give it to fewer people if the urgent route should be narrower.
 b. The paying-account revalidation checks: active on the Avenir Bank Account Master, tied to an existing active ledger
    account, and LKR (every voucher of the chain is paid in LKR). A USD company account cannot pay a voucher.
 c. Bank charges are recorded on the desk (initiation, lock, documents) but not posted to the ledger — the existing chain
    never posted them and no account was named. Say the word and the same mapping page can carry a bank-charges account.
 d. The TEST gain/loss accounts exist on the test copy only. On live, Accounts must create or approve the two accounts
    in the chart of accounts before anyone can map them; until then foreign-currency Paid is blocked by design.
 e. The unique index and trigger are the first change this kit makes to an existing table (voucher). They add only a
    refusal of duplicated / edited numbers; they touch no row and are reversible. The chain's own counter
    (invoice_no.V_Num) reads then writes, so two users issuing at the same instant could have drawn the same number
    before — now the second insert is refused instead of duplicating.
 f. voucher.Voucher_No (the number the authorisation step meant to assign) was never written by the existing code; it
    is left as it is. The official number is Vnum.
 g. The Complete Tour Record finds a tour's payments through tf_tour.legacy_tour_id, else a consignee whose Tour ID
    equals the record's tour number. Records of tours not linked to the payment desk show a note instead of the table.
 h. The accumulation applies to every approved payment; the Pay Individually Now route is the only bypass and always
    records its reason. As r2: a batch may hold a single payment.
 i. Known pre-existing console errors on the test server (header polling "Failed to fetch", DataTables on Bank Payment
    Check) are unrelated and unchanged. The test DB's "today" is Asia/Colombo (11 Oct 2026 at the time of the run).
