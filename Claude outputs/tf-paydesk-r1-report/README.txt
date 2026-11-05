tf-paydesk-r1 — Tour Payment & Accounting Workflow (10 Oct 2026)
=================================================================
Built and tested on the cloud test copy only. NOT installed on live. Install only after Shantha's approval.

1. WHAT THIS IS
---------------
The Tour Payment Dashboard rebuilt in the approved design, extended into the complete payment workflow Shantha
specified on 10 Oct 2026 — without replacing the existing payment and accounting chain. Every step that existed
before still happens in the same tables and through the same code:

   payment_request  (request → management approval)      TourAccountsController / TourAccountsCheckController
   voucher + v_inv  (the payment voucher / instruction)   created by the existing approval code
   Bank Payment Check (authorisation = ledger posting,     VoucherPaymentController::update + mark_finalized
                      finalisation = paid, bank date)

What the chain never recorded is added beside it, in new tables prefixed tpd_ (tour payment desk):

   tpd_request_ext      the exchange rate locked to each request, its LKR, invoice no., booking ref, due date, note,
                        who approved and the voucher raised
   tpd_bank_initiation  the accountant's step: Avenir account, date/time, batch and bank references, amount,
                        charges, proof file, notes   (one per voucher — a duplicate is refused)
   tpd_bank_approval    the lock written when the bank approver clicks Paid: date, amount, currency, rate, LKR,
                        account, reference, who initiated, who approved, exact time   (one per voucher)
   tpd_remittance       the supplier's remittance advice per voucher: pending → prepared → sent (sent once)
   tpd_bank_account     the Avenir Bank Account Master
   tpd_supplier_bank    suppliers' bank-payment details, invoice/remittance e-mails and verification
   tpd_expense          other supplier expenses added by hand
   tpd_document         supplier invoices, booking references, bank proofs, the advices sent
   tpd_audit            append-only audit record (user, date, exact time, action, previous/new value, reason,
                        rate, bank account, reference, voucher/invoice)
   tpd_meta             schema version

2. THE WORKFLOW (as installed)
------------------------------
Statuses (colour on the dashboard):
   1 Payment Not Requested (grey)      2/3 Payment Requested — Awaiting Management Approval (blue)*
   4 Approved for Payment (indigo)     5 Payment Voucher Issued (violet)
   6 Bank Payment Initiated — Awaiting Bank Approval (teal)
   7 Fully Paid (green)                8 Supplier Notification Pending (olive)   9 Supplier Notified (green)
   also: Partly Paid (purple) · Due Soon (amber) · Overdue (red) · On Hold (yellow, the rate review) ·
         Requires Checking (yellow) · Rejected (red) · Reversed (red, a cancelled voucher)
   * In this system a request reaches management the moment it is made (there is one approval step), so stages
     2 and 3 are the same state; the dashboard labels it "Awaiting Management Approval".

Steps and who does them (privilege = a menu entry granted to the group under Settings → Group Privileges):
   Request payment       requester      holder of Tour Payment Dashboard. Hotels in USD at today's confirmed rate,
                                        which is locked to the request (tpd_request_ext.usd_rate) — the same rate the
                                        chain already writes to tour_quotations.hotel_dollar_rate. LKR items are never
                                        converted. A supplier invoice number, booking reference, due date and note can
                                        be entered with the request.
   Approve payment       approver       holder of "Tour payment approval (management)" or of the Payment approval
                                        page. Chooses the Avenir paying account (from the master); the supplier's bank
                                        details must be verified, or confirmed on the spot by a holder of "Supplier
                                        bank verification". The approval runs the existing approval code: status 4 (6
                                        for a bulk supplier), the voucher and v_inv line, the ledger postings — exactly
                                        as the Payment approval page does. Approving never marks anything paid.
   Issue voucher         approver       bulk-payment HOTEL suppliers only (status 6 without a voucher) — the existing
                                        create_bulk_voucher. Service fees get their voucher at approval, as today.
   Record bank initiation accountant    holder of Bank Payment Check (or "Bank payment initiation"). Avenir account,
                                        date/time, amount (must equal the voucher), currency, charges, batch and bank
                                        references, proof file, notes. If the voucher is not yet authorised, its paying
                                        account is set to the one chosen here, so the ledger posts to that account.
   Paid                  bank approver  holder of "Bank payment approver (Paid)" ONLY. Enters the actual payment date
                                        and the final bank reference. Runs the existing authorisation (Aflag 1, ledger
                                        posting) and finalisation (finalized 1, bank_date), then writes the lock and
                                        sets the supplier's advice pending. Once the desk is set up, Bank Payment
                                        Check's own "Mark Finalized" is restricted to this privilege too (server side);
                                        the accountant's page shows the initiations recorded and "Paid: bank approver
                                        only" in place of the button. Cancel (reversal) stays available to accountants.
   Prepare / send remittance  requester One advice per voucher, per supplier — never combined. Address from the
                                        supplier's bank record (else the supplier master's e-mail). The e-mail and the
                                        PDF are shown for review before "Send to supplier"; sent through the signed-in
                                        user's mailbox as every other e-mail of the system; a second send is refused.
                                        Status becomes Supplier Notified only after the server accepted the message.
   Add Expense           requester      other supplier expenses (supplier from the master); they enter the chain as a
                                        service fee request (type lunch) and are approved / vouchered / paid like any
                                        other; cancelling an expense keeps it in the history.
   Reject / cancel       approver / requester   with a reason, kept in the audit record; the line can be requested again.

Exchange rate: Today's USD Exchange Rate is one central section (holders of Daily USD Rate or the Admin group confirm
it; who and when are shown). Rounding: USD × rate rounded half away from zero to the cent — the system's DECIMAL(12,2)
behaviour. A requested or paid item keeps its own rate; changing today's rate affects only what is not yet requested
(shown "indicative"). A payment in parts: the balance is requested as a new request once the earlier part is paid,
and takes the rate of its own day (test 2). The duplicate rule in TourAccountsController::paymentRequestFrom was
changed for this: a request still in flight blocks another; a paid, completed, cancelled or rejected one does not.

Duplicate protection: lines are the system's own records (quotation nights, activities, driver allocation, expenses),
keyed by their source; reloading never duplicates them. One request in flight per tour + supplier + type (the
chain's own key); approve twice, initiate twice, Paid twice, send the advice twice — each is refused with a 409 and
nothing changes. A service removed from the itinerary after a payment stays on the dashboard as a flagged line
("no longer on the itinerary"); a service whose price changed after the request is flagged ("requested $X, itinerary
now $Y"). Nothing in the payment history is altered automatically.

3. FILES
--------
New (15):   app/Libraries/TourPayments/{Schema,Perms,Audit,PayDesk,BankAccounts,SupplierBank,Documents}.php
            app/Controllers/Tour/TourPaymentDeskController.php        app/Controllers/MasterFiles/TourPaymentMastersController.php
            app/Views/pages/tour/{tour_payment_dashboard (rewritten), tpd_voucher, tpd_remittance, tpd_sheet}.php
            app/Views/pages/master_files/{tpd_bank_accounts, tpd_supplier_bank}.php
Changed (8, each change marked "tf-paydesk-r1" in the code):
   TourPaymentDashboardController.php  public dashboardData(); exact Tour ID match first (AH 221 no longer opens AH 2210); privileges to the page
   TourAccountsController.php          the duplicate-request rule (open vs closed); the desk's record of each request (rate, invoice, note)
   TourAccountsCheckController.php     after an approval made on the Payment approval page: the desk's record and audit entry
   BankPaymentCheckController.php      Mark Finalized / bulk finalize: bank-approver guard (only once the desk is set up) + the Paid lock
   bank_payment_check.php (view)       initiations shown; Mark Finalized only for bank approvers; Cancel unchanged
   Routes.php                          the new routes under tour/tour_payment_dashboard/ and master_files/{bank_accounts,supplier_bank}
   UserPermissionFilter.php            Tour Payment Dashboard holders may call tour_accounts' request endpoints (the dashboard's own buttons)
Not changed: VoucherPaymentController (ledger posting), the Payment approval page, the request / special-rate / rate-review
code of the dashboard (kept verbatim), every existing table.

4. INSTALL (after approval)
---------------------------
   python3 apply.py check  "C:\wamp64\www\avenir"      nothing is written; refuses if a live file changed since testing
   python3 apply.py apply  "C:\wamp64\www\avenir"      backs up the 8 changed files to before/<time>/, copies the 23 files
   then, signed in as System Administrator: open Tour Payment Dashboard → "Set up now"
        (= POST tour/tour_payment_dashboard/setup): creates the tpd_ tables (CREATE TABLE IF NOT EXISTS, see
        schema-reference.sql) and the six privilege entries, granted to the System Administrator group. Idempotent.
   then grant the privileges (Settings → Group Privileges; users log out and in once):
        Tour payment approval (management)       tour/tour_payment_dashboard/approve      → Management
        Bank payment initiation (accountant)     report/bank_payment_check/initiate       → Accounts (or hold Bank Payment Check)
        Bank payment approver (Paid)             report/bank_payment_check/approve        → the one person who confirms transfers
        Avenir Bank Accounts                     master_files/bank_accounts               → Accounts manager
        Supplier Bank Details                    master_files/supplier_bank               → Accounts
        Supplier bank verification               master_files/supplier_bank/verify        → the person who checks suppliers' accounts
   then Master Files → Avenir Bank Accounts: add the company accounts (each tied to its ledger account, one default)
        Master Files → Supplier Bank Details: enter / verify the suppliers' accounts and remittance e-mails
   ROLLBACK:  python3 apply.py rollback "C:\wamp64\www\avenir"   puts the 8 files back and moves the 15 new files aside.
        The tpd_ tables and the six menu rows stay (unused) so that no record is lost; drop them only by hand if wanted.
   IMPORTANT once installed: Bank Payment Check's Mark Finalized needs the "Bank payment approver (Paid)" privilege.
        Until it is granted, nobody but the System Administrator can mark a payment paid.

5. TESTS (cloud test copy, database avenir_test, tour AH 2218 "NEMOTO / REIKO MS", 11–16 Oct 2026, 1 pax, STW)
--------------------------------------------------------------------------------------------------------------
tests/flow_test.py runs Shantha's tests 1–21 through the real endpoints as four role accounts (tcpayreq requester,
tcpayappr approver, tcaccount accountant, tcbankappr bank approver): 97 checks, 97 passed (tests/results.json).
Test 22 (layouts) is the screenshots. The test data is in tests/setup_testdata.py (test copy only). The mail of test
12 went to a local test sink — nothing left the machine. See results.txt for every check by test number.

6. DECISIONS AND WARNINGS FOR SHANTHA
-------------------------------------
 a. One request per supplier per tour (the chain's rule): two charges of one supplier on a tour (two meals, two
    safaris) are requested, approved and paid as ONE payment; the dashboard shows the request on both lines and
    shares the amount out, and the voucher / advice list both invoice numbers. Separate payments to the same supplier
    on one tour are possible only one after the other (the balance rule).
 b. Stages 2 and 3 of the status list are one state here (see above).
 c. "Issue voucher" applies to bulk-payment HOTEL suppliers; service-fee vouchers are issued at approval (existing code).
 d. Voucher_No (the printed voucher number) is not written by the existing authorisation step (its model does not
    allow the column) — a pre-existing gap, left as is. The dashboard identifies vouchers by Vnum, as Bank Payment
    Check does.
 e. A Paid lock is written only from this dashboard's Paid or Bank Payment Check's Mark Finalized; payments finalised
    before the install have no lock and no advice — they show as Fully Paid without a notification state.
 f. The supplier master's own bank columns are untouched; the verified details live in tpd_supplier_bank. On the test
    copy the master had no bank data at all, so test details were entered there (TEST… numbers, @example.invalid).
 g. The approver may also hold the Bank Payment Check page; holding it grants the accountant's initiation.
    Separation that is enforced: nobody without "Bank payment approver (Paid)" can mark a payment paid.
 h. Guide payments: a guide's bata is shown when recorded in driver expenses under the guide's account; there is no
    separate guide-payment table in the system. Driver settlement still comes from the log sheet (existing).
 i. Colours: the approved reference used navy; the dashboard uses the system's purple for its header and buttons, the
    reference's green for the exchange-rate panel and the status colours of the brief. One CSS variable changes it.
 j. The reference site (violetdog256.chatgpt.site) needs a sign-in, so the five screenshots were the design source.
 k. Known pre-existing console errors on the test server (header polling "Failed to fetch", DataTables on Bank Payment
    Check) are unrelated and unchanged.
