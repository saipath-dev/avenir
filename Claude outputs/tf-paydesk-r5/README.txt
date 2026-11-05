tf-paydesk-r5 — Bulk supplier payment (one transfer, one voucher per item), separation of duties, the request privilege,
legacy cancellation recognised (11 Oct 2026)
=================================================================================================================================
Built and tested on the cloud test copy only. NOT installed on the development system. Install only after Shantha's approval.
Installs OVER tf-paydesk-r4 (schema 6 → 7); everything of r4 stays as installed (README-r4.txt is the r4 documentation).
34 source files: the 29 r4 files re-shipped (12 of them changed) + 5 new (Bulk.php, Separation.php, Vouchers.php,
tpd_bulk.php, tpd_bulk_remittance.php). The installer refuses to run on a copy whose files are not the r4 versions.

0. WHAT IS NEW, IN ONE PARAGRAPH
--------------------------------
   Supplier Payables gets a BULK PAY action: management reviews a supplier's approved items, presses "Select all eligible",
   reads the exclusions (with reasons), confirms count and totals once, and the system issues a SEPARATE Payment Voucher for
   every item — each with its own official voucher number, its own tour, service date and invoice — under one bulk batch,
   all in one operation (if any voucher cannot be issued, nothing is issued; a repeated confirmation creates nothing twice).
   The accountant initiates ONE bank transfer for the whole batch (bank charges allocated to the cent across the vouchers);
   the bank approver marks the whole batch Paid; the supplier receives ONE itemised remittance advice. The server enforces
   separation of duties at every step (never your own request; never Paid for what you initiated — the administrator is not
   exempt), and seeing the dashboard no longer grants the right to make payment requests (a new privilege entry). The desk
   now also recognises a voucher cancelled on the legacy Voucher Cancellation screen (an r4 gap, § 5).

1. BULK PAY — THE FLOW
----------------------
   Supplier Payables → a supplier's row → [Bulk pay]  (management: holders of the batch-creation privilege, menu 246)

   a) REVIEW (the server's eligibility check, GET bulk_eligibility?sup_acc=):
      one currency group per supplier (USD and LKR are shown as two groups — one bulk payment is one currency);
      every approved item of the supplier is listed as ELIGIBLE or EXCLUDED with the reason:
         not approved · already vouchered · already in a batch · tour cancelled · on hold (hotel rate-approval hold) ·
         disputed · supplier bank not verified · supplier bank changed since verification · USD item without an approval rate
      [Select all eligible (n)] / [Deselect all] / tick per item; the selected count and total update live;
      a DISPUTED item (POST dispute {pr_id, disputed 1|0, reason} — management or accountant, reason required, audited
      item_disputed / dispute_cleared) stays out of every bulk payment until the dispute is cleared.
      The supplier's bank details are shown; an unverified or changed account blocks the payment unless a holder of the
      verification privilege confirms it here (recorded under that user's name, as in r2).
   b) REVIEW & CONFIRM: the confirmation step lists the number of payments, the tours, "N separate Payment Vouchers will be
      issued", "ONE bank transfer of <currency> <total>" from the suggested Avenir account (the accountant confirms the actual
      account at initiation), the provisional LKR for USD items, and whether the voucher tables are transactional on this
      install. A one-time confirmation reference (submit_token) is generated when the step opens.
   c) CONFIRM — issue the vouchers (POST bulk_create {sup_acc, pr_ids[], currency, bank_account_id, submit_token, note}):
      the server RE-VALIDATES the selection against the eligibility check (the client's list is never trusted): any
      ineligible id → the whole request is refused and nothing is created; a mixed-currency selection is refused;
      the counter check of r4 runs first (counter behind the voucher table → refused before anything is written);
      then N vouchers are issued with CONSECUTIVE official numbers (invoice_no.V_Num, one voucher row + its v_inv lines +
      the request flags per item), the bulk PARENT batch (SB-xxxxx, mode bulk_parent, no voucher of its own) and one
      CHILD batch per voucher (mode bulk_child, the r2 batch record that carries the voucher) are written, and
      tpd_request_ext.bulk_id links each item. Audit: bulk_issued (the numbers, who confirmed, the token) and one
      voucher_issued per voucher.
      FAILURE: on InnoDB voucher tables it is one transaction — nothing is written. On MyISAM (the development system today,
      Decision A deferred) the desk takes back what the failed run wrote (its own voucher rows, lines, flags and the counter,
      by id) and verifies the take-back; the failure is recorded (voucher_issue_failed) and the items stay eligible. Tested
      on both engines (§ 7).
      DUPLICATE: the same submit_token again returns the existing batch ("already processed") and creates nothing; a new
      confirmation for the same items is refused (they are already batched).
   d) The accountant (Bank Payment Check privilege) initiates the ONE transfer on the bulk batch page
      (POST bulk_initiate {bulk_id, bank_account_id, payment_date, bank_ref, charges, note}): the actual paying account, the
      payment date (USD: the rate of that date), the bank reference, the bank charges of the transfer. The charges are
      allocated across the vouchers PRO RATA to each voucher's LKR, to the cent (largest-remainder rule; Σ shares = charges);
      every voucher goes through the r4 initiation (one tpd_bank_initiation row each, the same date / account / reference,
      batch_ref = the bulk batch number). A child voucher cannot be initiated on its own (409, names the bulk batch); the
      legacy Bank Payment Check lists the vouchers individually and links each to the bulk page. The run is RESUMABLE: a
      second run initiates only what is missing and keeps the transfer details recorded by the first; a complete run again
      changes nothing ("nothing to initiate … the recorded transfer details were kept").
   e) The bank approver (Bank payment approver privilege) marks the whole batch Paid
      (POST bulk_paid {bulk_id, paid_date, bank_ref, bank_account_id, confirm_account, note}): every voucher goes through the
      r4 Paid under the batch lock — the Paid lock, the final rate (USD), the exchange-difference pair, the bank-charge pair
      (Dr Bank Charges / Cr paying account, once per voucher), the legacy finalize, the per-voucher ledger check. Then the
      batch-level LEDGER CHECK: every voucher Paid · every voucher's own ledger check passed · the charge shares add back to
      the charges entered · Σ credits to the paying account over the batch = the one bank debit (transfer + charges). The
      parent is marked reconciled only when all four hold (audit bulk_reconciled / bulk_reconcile_failed). Resumable; a
      child cannot be marked Paid on its own (409) and the legacy Mark Finalized is blocked for it (403).
      "Ledger balanced" is NOT a bank-statement match — that is Bank Reconciliation Step 2 (the message and the page say so).
   f) ONE itemised remittance advice for the transfer (POST bulk_remittance_prepare / bulk_remittance_send {bulk_id, to, cc};
      GET bulk_remittance/{id}[?pdf=1]): every voucher listed with tour, guest, service, invoice, amount; the one transfer
      total, the bank reference, the paying bank; sent once (the per-voucher records are marked sent and point at the batch
      advice; a second send is refused). The per-voucher advice of a bulk child is refused (409, names the batch).

   Pages: tour/tour_payment_dashboard/bulk/{id} (the bulk batch: vouchers, the transfer, the ledger check, the advice, the
   audit), bulk_data/{id} (JSON), bulk_remittance/{id}. Supplier Payables → Batches lists the bulk parent with its roll-up
   (count, voucher numbers first–last, paid / initiated / issued / cancelled) and its children grouped under it.

   Stages of a bulk batch: issued → initiated (initiation incomplete = re-run) → paid (ledger balanced) / paid — ledger not
   yet balanced (re-run Paid) / paid incomplete; "paid, but N voucher(s) cancelled" when a child was cancelled on the legacy
   screen after issue — the batch never shows as complete or balanced then (the recorded transfer no longer matches).

2. THE REQUIRED ACCOUNTING STRUCTURE, AS BUILT
----------------------------------------------
   • one Payment Voucher per item (voucher row, v_inv lines, the request's voucher_created flag) — exactly the chain's
     per-voucher records, so every existing report, the supplier ledger, Bank Payment Check, Voucher Report and the Finalized
     records see ordinary vouchers; the legacy Voucher Cancellation works on one voucher without touching the others;
   • the child batch (tpd_batch, mode bulk_child, parent_id) carries each voucher exactly as an r2 batch does
     (initiation row, Paid lock, charge share, FX pair, reconciliation);
   • the parent batch (mode bulk_parent) carries the ONE transfer: payment_date, usd_rate, bank_ref, transfer_lkr,
     charges_total, charges_allocated, total_debit (= transfer + charges), ledger_bank_credit, reconciled / reconciled_at,
     selected_by / confirmed_by / initiated_by / paid_by (+ times), submit_token (unique), writer ('atomic'), engine_atomic;
   • Σ children = the transfer, always: the transfer is never a figure of its own, it is the sum of the vouchers;
   • bank charges: one balanced pair per voucher (its share), posted once, Σ = the charges entered;
   • nothing posted for the parent — it is a roll-up, not a ledger document.
   Approval figures are never overwritten (r3 rule); the final LKR of a USD voucher is fixed at the payment-date rate (r2).

3. SEPARATION OF DUTIES (server side; Separation.php)
----------------------------------------------------
   Checked on the server at approve, batch_create, bulk_create, initiate / bulk_initiate and paid / bulk_paid, by comparing
   the session user with payment_request.payment_request_user (who requested) and tpd_bank_initiation.initiated_by (who
   initiated). Refused with 403 + "separation": true and a plain message; every refusal is audited (separation_blocked).
   The System Administrator is NOT exempt. Hidden buttons are not relied on — the checks run on every route.
   Consequence: a one-person installation cannot pay anything through the desk any more — at least three different users
   are needed (requester · approver/management · accountant · bank approver may be three people, the accountant and bank
   approver must differ from the initiator).

   THE BANK AUTHORISER ROLE: no code. The group profile "Bank Authoriser (final Paid)" (id 35 on the development system)
   already exists, unassigned, with Bank payment approver (248), Bank Payment Check / Finalized records (189, 191), Supplier
   Payables (245), Daily USD rate (217) and the Tour Payment Dashboard (74). Nothing else — not System Administrator, not
   Management Approver, not Accountant. Assign it only when the new staff member exists.

4. THE REQUEST PRIVILEGE (dashboard visibility ≠ request authority)
-------------------------------------------------------------------
   Setup creates the privilege entry "Tour payment requests (make / cancel)" = tour/tour_payment_dashboard/request (menu
   258 on the test copy; the number on the development system is whatever Setup allocates — read it in Group Privileges).
   It alone allows: adding an expense, the request meta, making or cancelling a payment request, the driver-advance request,
   and the legacy routes tour/tour_accounts/payment_request, driver_advance_payment_request, cancel_payment_request (covered
   by the route aliases of the permission filter). The dashboard privilege (74) now gives READ-ONLY access to the desk and
   its pages; the other payment roles are unchanged.
   GRANT IT right after Setup, or nobody but the System Administrator can request: Hotel Reservations Desk (profile 32),
   Transport Desk (33) and the profile that requests restaurant / other payments (Shantha decides — Quotation Desk 31 or
   the Accountant 30). Management / Payment Approver (29) deliberately does NOT get it (an approver who could request would
   defeat the separation).

5. LEGACY CANCELLATION RECOGNISED (an r4 gap, closed here)
-----------------------------------------------------------
   The legacy Voucher Cancellation screen (report/voucher_report/update) sets voucher.Aflag = 3 (and reverses the ledger
   rows of a posted voucher); the legacy authorisation reject sets Aflag = 2. r4 treated only Aflag 2 as dead, so a voucher
   cancelled on that screen could still be initiated or marked Paid by the desk. r5: Aflag 2 and 3 are both "cancelled" for
   the desk (PayDesk::isCancelled) — never initiated, paid, re-used for a request, or offered to a bulk payment; shown as
   cancelled on the batch listing, the voucher page and the tour history ("Voucher cancelled (Voucher Cancellation
   screen)" / "Voucher rejected / reversed"). Test 62 covers it. On the development system (r4 as installed) the gap is
   open until r5 is installed — it needs a cancelled voucher AND a desk user who then presses Paid on it; the legacy Bank
   Payment Check itself never lists a cancelled voucher.

6. INSTALL / SET UP / ROLL BACK
-------------------------------
   python3 apply.py check    C:\wamp64\www\avenir     — every file must be "same" or an r4 version; "changed_since_tested"
                                                        means a file is not the r4 version: stop and ask
   python3 apply.py apply    C:\wamp64\www\avenir     — backs up to before/<time>/ and copies the 34 files
   System Administrator → Tour Payment Dashboard → "Set up now" (or POST tour/tour_payment_dashboard/setup)
        schema 7: the tpd_batch columns (parent_id, mode, voucher_count, transfer_total, transfer_lkr, bank_ref,
        payment_date, usd_rate, charges_total, charges_allocated, total_debit, ledger_bank_credit, reconciled,
        reconciled_at, submit_token, writer, engine_atomic, selected/confirmed/initiated/paid by + at), tpd_request_ext
        (disputed, dispute_reason, disputed_by, disputed_at, bulk_id), tpd_remittance.batch_id and vnum made nullable;
        indexes uq_tpd_batch_submit_token, idx_tpd_batch_parent, idx_tpd_request_ext_bulk; the request privilege entry
        (granted to System Administrator only). No existing row is touched; no core accounting table is altered.
   Settings → Group Privileges: grant "Tour payment requests (make / cancel)" to the requesting profiles (§ 4); grant
        "Bank payment approver" (248) to the Bank Authoriser profile only.
   python3 apply.py rollback C:\wamp64\www\avenir     — the r4 files back, the 5 new files moved aside. The database is not
        touched by it: the schema-7 columns are harmless to r4; a bulk batch created meanwhile keeps its vouchers (ordinary
        vouchers — r4 shows its children as single batches) — do not roll back with an initiated-but-unpaid bulk batch open.
   DATABASE SIDE — before upgrading, Setup copies every table it will alter or write to (tpd_batch, tpd_request_ext,
        tpd_remittance, tpd_meta, menu_list, user_privileges) to tpd_bak_v6_<date>__<table> inside the database (same
        rows). rollback_r5.sql in this kit lists the statements that take schema 7 back to 6 (columns, indexes, the
        request-privilege entry and its grants, the version), to run in phpMyAdmin after apply.py rollback.
   Nothing in this kit converts voucher / v_inv / invoice_no / payment_request (Decision A, deferred) or replaces the
   chain's single-voucher writer for the r2 batch: the bulk payment issues its vouchers through the new writer
   (Vouchers.php) because the chain's bulk call writes one voucher for many items, which is what "do not collapse everything
   into a single voucher" excludes. The r2 batch and Pay Individually Now keep the chain's call.

7. TESTS AND RESULTS (cloud test copy; evidence/)
-------------------------------------------------
   The whole r4 suite (tests 0–46, 202 checks) on schema 7 with the r5 code:  202 / 202 on MyISAM, 202 / 202 on InnoDB
        (results_r5_myisam_suite.json, results_r5_innodb_suite.json, flow_run_*.log).
   r5 tests 48–62 (bulk_test.py, 71 checks):  71 / 71 on InnoDB (results_bulk_innodb.json), 71 / 71 on MyISAM
        (results_bulk_myisam.json — the non-atomic undo path), 70 / 70 on the kit build without the test hook
        (results_bulk_kitbuild.json; test 51, failure injection, needs the hook of the test copy and is skipped there).
     48 eligibility: reasons, two currency groups, a disputed item, a supplier whose bank changed after verification
     49 selection re-validated on the server (disputed id, mixed currencies, bad token, no privilege)
     50 12 items of two tours → 12 vouchers, consecutive numbers, one parent + 12 children, Σ children = transfer, audit
     51 failure after voucher 1 of 2 → nothing created, counter unchanged, audit voucher_issue_failed, items still eligible
     52 counter behind the voucher table → refused before writing
     53 duplicate submission (same token → existing batch; new confirmation → refused)
     54 the one transfer initiated: 12 initiation rows, same date/account/reference, charges 217.00 allocated to the cent,
        parent records the transfer; initiating again changes nothing
     55 Paid for the whole transfer: 12 Paid locks, 12 charge pairs (Σ 217.00) posted once, ledger check 31,217.00 = the
        bank debit, parent reconciled, "not a bank-statement match", Paid again posts nothing, each tour shows its own
        vouchers (6 + 6)
     56 USD batch at the payment-date rate; a child cancelled on the legacy screen → Paid reports INCOMPLETE (207), the
        batch is not reconciled, the advice is refused
     57 one itemised advice: all 12 vouchers, total, reference; the per-voucher advice of a child refused; sent once
     58 legacy screens with bulk vouchers (Finalized records, Voucher Report, Voucher Audit, tour record, voucher page,
        child batch page, bulk page, Payables); the batches listing shows the parent with its roll-up
     59 separation of duties: own request blocked at approve / batch / bulk / initiate / Paid (administrator included);
        the initiator cannot mark Paid (single and bulk); another user can; audit separation_blocked
     60 management (dashboard, no request privilege) reads the desk but cannot add an expense, write request meta or post
        the legacy payment request; the requester can; the accountant cannot
     61 Setup created the request privilege entry
     62 a voucher cancelled on the legacy screen (Aflag 3): the desk refuses initiate and Paid, the batch shows Cancelled,
        the history records it, the item is not offered again
   Earlier, Decision B demonstration (voucher_writer_compare.py, 38 / 38, results_writer.json): the new writer and the
   chain's writer produce identical voucher rows, lines, ledger postings and legacy-page output for the same batch.

8. PRE-EXISTING QUIRKS SEEN WHILE TESTING (not changed)
-------------------------------------------------------
   • report/bank_payment_check logs a browser console error "_DT_CellIndex" from the legacy petty-cash DataTable on every
     load (also without any r5 voucher); the page works.
   • report/voucher_report/update (the legacy cancel) answers HTTP 500 after cancelling an unposted voucher ("Not enough
     entries to swap"); the cancellation itself is complete (Aflag 3, flag 1).
