tf-paydesk-r2 — Tour Payment & Accounting Workflow with Supplier Payables and the payment-date rate (10 Oct 2026)
==================================================================================================================
Built and tested on the cloud test copy only. NOT installed on live. Install only after Shantha's approval.
This kit contains everything of tf-paydesk-r1 (never installed) plus the two changes Shantha asked for on 10 Oct:
   (1) the corrected flow — an approved payment no longer goes straight to Bank Payment Check; it waits under its
       supplier in SUPPLIER PAYABLES until management creates the supplier's batch, which issues the voucher;
   (2) the exchange-rate rule — the approval figures are kept as they are, and the final LKR is calculated at the
       rate of the ACTUAL BANK-PAYMENT DATE; the difference is recorded separately as an exchange gain or loss.

1. THE FLOW (as installed)
--------------------------
   Payment Request → Payment Approval → SUPPLIER PAYABLES (accumulation under the supplier) → Select and Create Batch
   → Voucher issued (accountant's queue) → Accountant initiates the bank payment → Bank approver confirms Paid
   → Remittance advice

   Request payment        requester     holder of Tour Payment Dashboard. A USD item is requested at today's confirmed
                                        rate (kept as "requested at"); LKR items are never converted. Invoice number,
                                        booking reference, due date and note can be entered with the request.
   Approve payment        approver      holder of "Tour payment approval (management)" or of the Payment approval page.
                                        NO voucher and NO paying account at this step any more. The approval runs the
                                        existing approval code (status 4, or 6 for a bulk supplier; the service-fee
                                        accrual as before) and writes the APPROVAL FIGURES once, never to be
                                        overwritten: the locked USD amount, the rate shown at approval (today's
                                        confirmed rate — else the rate locked at request), the provisional LKR
                                        (USD × that rate), the approval date and the approving user. The supplier's
                                        bank details must be verified (or confirmed by a verifier) before approval.
                                        The Payment approval page's own Accept does the same (it approves only; a
                                        banner on that page links to Supplier Payables).
   Supplier Payables      payables      tour/tour_payment_dashboard/payables — every approved payment waiting, grouped
                                        by supplier: tour, guest, dates, pax, service, invoice/ref, locked amount,
                                        approval rate, provisional LKR, requested/approved by and when, due date,
                                        the supplier's bank verification. Totals per supplier and overall. A second
                                        tab lists the batches and their state. Seen by every payment role.
   Create batch           batch         holder of "Supplier payment batch — create (management)". Selects the
                                        supplier's approved payments (one supplier, any tours), chooses the Avenir
                                        account from the master, confirms an unverified supplier account if a
                                        verifier, and creates the batch: the chain's own create_bulk_voucher raises
                                        ONE voucher (batch mode: the requests stay approved as they were, no second
                                        accrual, each line valued at its approval rate), tpd_batch records the batch
                                        (SB-00001 …) and each request is tied to its v_inv line. The voucher lands in
                                        Bank Payment Check. From the dashboard the "Create batch · issue voucher"
                                        button of an approved line opens the same window with the supplier's items.
   Bank payment page      accountant    tour/tour_payment_dashboard/batch/{id} (linked from Bank Payment Check, the
                                        dashboard and Supplier Payables). Shows the supplier and verified bank
                                        details, all included tours and invoices, the locked USD amount, the
                                        approval-date rate(s), the provisional LKR, the actual payment date, TODAY'S
                                        CONFIRMED USD RATE (the rate of the chosen payment date), the actual LKR
                                        (locked USD × that rate, per item, rounded to the cent), bank charges, the
                                        total debited and the exchange-rate difference. The accountant confirms the
                                        date, the rate, the Avenir account, the charges and the bank/batch reference;
                                        the USD amount, supplier, invoices and allocations cannot be changed, and the
                                        LKR cannot be typed. The unposted voucher (Aflag 0) and its v_inv lines are
                                        brought to this rate. Status: Bank Payment Initiated — Awaiting Bank Approval.
   Final approval · Paid  bank approver holder of "Bank payment approver (Paid)" ONLY, on the same page. The page
                                        loads the rate of the FINAL payment date (if it differs from the initiation
                                        date, that day's rate replaces the initiation's; the initiation record keeps
                                        its own figures). The approval rate stays visible beside it. Paid runs the
                                        existing authorisation (ledger posted at the final LKR, per tour line) and
                                        finalisation (bank date), then locks permanently: final payment date, final
                                        rate, final LKR, charges, total debit, reference — and each request's final
                                        figures and its share of the difference (tpd_request_ext.paid_rate / paid_lkr
                                        / fx_diff). Bank Payment Check's Mark Finalized refuses a batch voucher and
                                        points to the batch page, so the rate confirmation cannot be skipped.
   Remittance advice      requester     one advice per voucher (= per batch, per supplier), with the FINAL figures,
                                        listing every tour and invoice of the batch; reviewed before sending; sent
                                        once; the supplier becomes Notified only after the server accepted the mail.

   The rate of a day: the Daily USD Rate entry of that date (Today's rate, confirmed on the dashboard by a holder of
   Daily USD Rate / the Admin group). When no rate is confirmed for the payment date, the bank payment page says so;
   a holder of the Daily USD Rate privilege can enter it there (it is then recorded as that day's rate, with an
   audit entry); anyone else is asked to have it confirmed first. A rate never changes retroactively: a different
   figure posted against a confirmed day is refused.

   The exchange-rate difference: final LKR − provisional LKR, per request (+ loss, − gain), summed per batch. It is
   recorded on the request record, the initiation record, the Paid lock, the dashboard, the batch page, the voucher
   document and the audit record. LEDGER: this install has no exchange gain/loss account in the chart of accounts, so
   the difference is NOT posted to the ledger — the supplier's ledger is credited at the approval-time figures and
   debited at the final figures. When Accounts names such an account (Setup: POST tour/tour_payment_dashboard/setup
   with fx_ledger_code=<L_Code>), every Paid posts the difference automatically (Dr exchange loss / Cr supplier, or
   Dr supplier / Cr exchange gain; advances_pay 23) so the supplier's ledger nets to zero. See decision (c).

   Statuses on the dashboard: 1 Payment Not Requested · 2/3 Awaiting Management Approval · 4 Approved for Payment
   ("waiting under <supplier> in Supplier Payables") · 5 Payment Voucher Issued (batch SB-…) · 6 Bank Payment
   Initiated — Awaiting Bank Approval · 7 Fully Paid · 8 Supplier Notification Pending · 9 Supplier Notified;
   also Partly Paid, Due Soon, Overdue, On Hold, Requires Checking, Rejected, Reversed.

   Duplicate protection: approve twice, batch twice, initiate twice, Paid twice, send the advice twice — each refused
   (409 / 400) and nothing changes; one request in flight per tour + supplier + type; an approved payment is "open"
   until its batch voucher is paid, so no second request can be made for it meanwhile.

2. TABLES AND PRIVILEGES (Setup creates them; nothing existing is altered)
-------------------------------------------------------------------------
   NEW table  tpd_batch           the supplier batch: number, supplier, currency, USD total, provisional LKR total,
                                  items, tours, Avenir account, voucher, status, note, who/when
   NEW columns tpd_request_ext    batch_id, vinv_id, approval_rate, approval_lkr, approval_rate_date (written once),
                                  paid_rate, paid_lkr, paid_date, fx_diff (written once, at Paid)
               tpd_bank_initiation batch_id, payment_date, usd_amount, usd_rate, rate_source, approval_lkr, fx_diff, total_debit
               tpd_bank_approval  batch_id, approval_lkr, fx_diff, charges, total_debit, rate_source, fx_posted
   NEW privileges (menu_list rows, granted to System Administrator at setup):
        Supplier Payables                                tour/tour_payment_dashboard/payables   (a visible page)
        Supplier payment batch — create (management)     tour/tour_payment_dashboard/batch      (hidden privilege)
   plus the six of r1 (approve, initiate, approve-Paid, bank accounts, supplier bank, verify). Schema version 4.
   The r1 tables (tpd_request_ext, tpd_bank_initiation, tpd_bank_approval, tpd_remittance, tpd_bank_account,
   tpd_supplier_bank, tpd_expense, tpd_document, tpd_audit, tpd_meta) are unchanged apart from the columns above.

3. FILES
--------
New (17):   app/Libraries/TourPayments/{Schema,Perms,Audit,PayDesk,BankAccounts,SupplierBank,Documents,Batches}.php
            app/Controllers/Tour/TourPaymentDeskController.php        app/Controllers/MasterFiles/TourPaymentMastersController.php
            app/Views/pages/tour/{tour_payment_dashboard (rewritten), tpd_payables, tpd_batch, tpd_voucher, tpd_remittance, tpd_sheet}.php
            app/Views/pages/master_files/{tpd_bank_accounts, tpd_supplier_bank}.php
Changed (9, each change marked "tf-paydesk-r1" / "tf-paydesk-r2" in the code):
   TourAccountsCheckController.php     approval (single and bulk accept): with Supplier Payables in place NO voucher is
                                       raised and no bank account is needed — the accruals are posted as before;
                                       create_bulk_voucher: batch mode for already-approved requests (no re-approval,
                                       no second accrual, approval rates per line); without batch mode it approves only
   TourPaymentDashboardController.php  public dashboardData(); exact Tour ID match first; privileges to the page
   TourAccountsController.php          the duplicate-request rule (open vs closed); the desk's record of each request
   BankPaymentCheckController.php      Mark Finalized / bulk finalize: bank-approver guard + batch guard (a batch
                                       voucher is paid on its batch page); batch links passed to the page
   bank_payment_check.php (view)       initiations and batch links shown; no Mark Finalized for a batch voucher
   payment_approval_v2.php (view)      a banner linking to Supplier Payables when the flow is in place (nothing else)
   Routes.php                          the new routes (payables, payables_data, batch_items, batch_create, batch/{id}, batch_data/{id}, rate_for)
   UserPermissionFilter.php            Tour Payment Dashboard holders may call tour_accounts' request endpoints
Not changed: VoucherPaymentController (ledger posting), the request / special-rate / rate-review code of the
dashboard (kept verbatim), every existing table.

4. INSTALL (after approval)
---------------------------
   python3 apply.py check  "C:\wamp64\www\avenir"      nothing is written; refuses if a live file changed since testing
   python3 apply.py apply  "C:\wamp64\www\avenir"      backs up the 9 changed files to before/<time>/, copies the 26 files
   then, signed in as System Administrator: open Tour Payment Dashboard → "Set up now"
        (= POST tour/tour_payment_dashboard/setup): creates the tpd_ tables (CREATE TABLE IF NOT EXISTS), adds the
        new columns, the eight privilege entries (granted to System Administrator). Idempotent; see schema-reference.sql.
        FROM THIS MOMENT approvals wait in Supplier Payables (schema version 4). Until Setup is run the chain's
        own steps work as today (approval → voucher) and the desk's extras are off.
   then grant the privileges (Settings → Group Privileges; users log out and in once):
        Tour payment approval (management)              tour/tour_payment_dashboard/approve   → Management
        Supplier payment batch — create (management)    tour/tour_payment_dashboard/batch     → Management (who creates batches)
        Supplier Payables                               tour/tour_payment_dashboard/payables  → anyone who should see the queue
                                                        (the four payment roles see it anyway)
        Bank payment initiation (accountant)            report/bank_payment_check/initiate    → Accounts (or hold Bank Payment Check)
        Bank payment approver (Paid)                    report/bank_payment_check/approve     → the one person who confirms transfers
        Avenir Bank Accounts                            master_files/bank_accounts            → Accounts manager
        Supplier Bank Details                           master_files/supplier_bank            → Accounts
        Supplier bank verification                      master_files/supplier_bank/verify     → the person who checks suppliers' accounts
   then Master Files → Avenir Bank Accounts: add the company accounts (each tied to its ledger account, one default)
        Master Files → Supplier Bank Details: enter / verify the suppliers' accounts and remittance e-mails
   optional: name the exchange gain/loss ledger account (Accounts to create it in the chart of accounts first):
        POST tour/tour_payment_dashboard/setup  fx_ledger_code=<L_Code>   (System Administrator; GET …/setup?q=exchange searches)
   ROLLBACK:  python3 apply.py rollback "C:\wamp64\www\avenir"   puts the 9 files back and moves the 17 new files aside.
        The tpd_ tables, columns and menu rows stay (unused) so that no record is lost; drop them only by hand if wanted.
   IMPORTANT once installed: Mark Finalized needs the "Bank payment approver (Paid)" privilege, and a batch voucher
        is paid on its batch page. Until the privileges are granted, only the System Administrator can create batches
        or mark a payment paid.

5. TESTS (cloud test copy, database avenir_test; AH 2218 "NEMOTO / REIKO MS" 11–16 Oct 2026 and a second tour
   AH 2290 "TANAKA / YUKI MS" 20–22 Oct 2026 with a Cassamara night, for the multi-tour batch)
--------------------------------------------------------------------------------------------------------------
tests/flow_test.py runs Shantha's tests 1–21 and the new ones 23–30 through the real endpoints as four role
accounts (tcpayreq requester, tcpayappr approver + batch, tcaccount accountant, tcbankappr bank approver): 134
checks, 134 passed (tests/results.json, results.txt by test number). Test 22 (layouts) = the screenshots.
   23 approval → Supplier Payables → batch (management only) → voucher; requester/accountant refused
   24 the accountant's bank payment page; the LKR cannot be typed; Mark Finalized refused for a batch voucher
   25 approval and payment on the same day: final = provisional, difference 0.00 (COVENRO $55 @ 327.25)
   26 payment after approval, rate increasing: Kassapa part 1 approved @ 327.25 (22,907.50), paid @ 329.10
      (23,037.00), loss +129.50; the approval figures unchanged; the ledger carries 23,037.00
   27 rate decreasing: Kassapa part 2 approved @ 329.10 (23,037.00), paid @ 325.00 (22,750.00), gain −287.00
   28 initiation and final approval on different dates: initiated for yesterday @ 328.00 (22,960.00), Paid today
      @ 325.00 — the final figures follow the final date; the initiation record keeps 328.00 / 22,960.00
   29 one supplier, two tours in one batch (Cassamara $60 + $60, approved @ 325.00): one voucher 39,000.00
      provisional, paid @ 329.10 = 39,492.00; difference +492.00 allocated +246.00 to each tour; each v_inv line
      19,746.00; the ledger posted per tour; each tour's dashboard shows its own share; one advice, both invoices
   30 the approval figures of every payment byte-for-byte unchanged after four payments at three rates; the
      differences recorded separately (0.00 · +129.50 · −287.00 · +246.00 · +246.00); fx_posted = 0 (no account)
The mail of test 12 went to a local test sink — nothing left the machine. The test data (tests/setup_testdata.py)
is for the test copy only; the rates of the two earlier days (325.00, 328.00) were inserted as test data.

6. DECISIONS AND WARNINGS FOR SHANTHA
-------------------------------------
 a. The accumulation applies to EVERY approved payment — hotels, services, expenses and driver advances alike. A
    batch may hold a single payment, so an urgent one is batched on its own; nothing is forced to wait.
 b. Batch creation is a privilege of its own ("Supplier payment batch — create (management)") so it can be held by
    the same people as the approval or by fewer. The approval no longer names a paying account; the batch does.
 c. The exchange-rate difference is recorded on the desk; it is posted to the ledger only once an exchange
    gain/loss account is named (none exists in the chart of accounts). Bank charges are recorded on the desk only
    (the existing chain never posted them). Both are shown on the batch page, the voucher and the audit record.
 d. The "rate shown at approval" is Today's confirmed rate on the approval day; if none is confirmed that day the
    rate locked at request is used (and if there is none either, the approval is refused until a rate is confirmed).
 e. The voucher's LKR is provisional until the bank payment: the chain's bulk voucher is re-rated (voucher.Amount
    and each v_inv line) at initiation and at Paid while its ledger is not yet posted. A voucher authorised outside
    this flow before Paid keeps its posted figure; the final figures are still recorded and the difference flagged.
 f. A multi-tour batch voucher keeps the chain's convention ref_po = the first request's tour; each v_inv line carries
    its own tour, and the authorisation posts the ledger per line (per tour). The nights' paid flag is set at Paid.
 g. The legacy Payment approval page's "Accept" (create_bulk_voucher) now approves only; its bank-account prompt is
    kept (the account is ignored at this step). The old Bank Payment Check flow is unchanged for vouchers that were not
    raised by a batch (older payments): Mark Finalized still works for them, bank approver only.
 h. One request per supplier per tour (the chain's rule): two charges of one supplier on a tour are one payment, shown
    on both lines with the amount shared out (test 13). Separate payments to the same supplier on one tour follow one
    another (the balance rule, test 2).
 i. Voucher_No (the printed voucher number) is not written by the existing authorisation step — pre-existing, left as is.
 j. On the test copy the supplier master had no bank data, so TEST… numbers and @example.invalid addresses were entered.
 k. Known pre-existing console errors on the test server (header polling "Failed to fetch", DataTables on Bank Payment
    Check) are unrelated and unchanged.
 l. The test DB's "today" is Asia/Colombo (11 Oct 2026 at the time of the run): the screenshots show that date.
