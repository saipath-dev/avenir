# Tour payments — completion report (11 Oct 2026)

Scope of this report: the outstanding R4 verification, the R5 testing you approved (same-user protection, Bank Authoriser, the atomic voucher writer under test, InnoDB deferred), the bulk supplier payment, and the dashboard preview. Nothing below was installed on the development system except the R4 configuration you already approved (accounts, mappings, unassigned profiles). The development system's accounting tables, voucher-writing logic, working dashboard and staff accounts are unchanged.

## 1. R4 verification on the development system — complete

- Chart of accounts: Realised Exchange Gain **8-35-111-11808** (under Other Income → Foreign Exchange Gain-Loss, legacy 10780 untouched) and Realised Exchange Loss **4-8-37-11809** (under Finance Charges); Bank Charges **4-8-37-10818** (existing, active).
- Tour Payment Dashboard → Settings: the three mappings saved, audit note "Approved by Shantha 11 Oct 2026"; the setup check reports both mappings complete; no payment page shows a configuration warning.
- Group profiles created, **no users assigned**: 29 Management / Payment Approver, 30 Accountant (New Generation), 31 Quotation Desk (New Generation), 32 Hotel Reservations Desk, 33 Transport Desk (New Generation), 34 Auditor (read-only), **35 Bank Authoriser (final Paid)** — the last holds only Bank payment approver, Bank Payment Check / Finalized records, Supplier Payables, Daily USD rate and the dashboard; it is separate from System Administrator, Management Approver and Accountant, as you asked.
- Still open on the development system: a database-level read of tpd_meta and the mapping audit row (phpMyAdmin session expired — needs you signed in; the application-level checks all pass).

## 2. R5 testing on the cloud test copy — complete

| What | Result |
|---|---|
| Whole R4 suite (tests 0–46, 202 checks) with the R5 code, schema 7 | **202 / 202 on MyISAM** and **202 / 202 on InnoDB** |
| R5 tests 48–62 (bulk payment, separation, request privilege, cancellation) — 71 checks | **71 / 71 on InnoDB**, **71 / 71 on MyISAM** (undo path), **70 / 70 on the packaged kit** (the failure-injection test needs the test hook, skipped there) |
| Atomic voucher writer vs the chain's writer (Decision B demonstration, 38 checks) | 38 / 38 identical rows, lines, postings, legacy pages |
| Engine migration rehearsal (Decision A) | delivered earlier: fingerprints identical, 202 / 202 on InnoDB, transaction proof, rollback identical |

What tests 48–62 cover, in your words: voucher numbering (consecutive official numbers, counter checked before any write), financial accuracy (Σ vouchers = the one transfer; charges allocated to the cent and posted once per voucher; the batch ledger check = the bank debit), existing reports (Finalized records, Voucher Report, Voucher Audit, tour record, Bank Payment Check all render with the new vouchers), cancellations (a voucher cancelled on the legacy screen leaves the batch incomplete and is never paid), audit history (every step and every refusal recorded) and recovery after failure (a failure mid-run creates nothing — one transaction on InnoDB, the desk's verified undo on MyISAM; a repeated confirmation creates nothing twice).

### 2a. Same-user protection — built and tested (server side)
A user cannot approve, batch, bulk-batch, initiate or finally authorise a payment they requested; a user cannot mark Paid what they initiated. Enforced on every route, not by hidden buttons; the System Administrator is not exempt; every refusal is an audit entry. Dashboard visibility and request authority are now separate: Setup creates the privilege entry **"Tour payment requests (make / cancel)"**; opening the dashboard grants read-only access; the legacy request routes are covered too.

### 2b. Bulk supplier payment — built and tested
Supplier Payables → **Bulk pay**: review table with eligibility chips, **Select all eligible (n)**, the server's exclusion list with reasons (cancelled tour, on hold, disputed, already vouchered/batched, bank not verified/changed, USD item without a rate), a confirmation step listing counts, totals, "N separate Payment Vouchers, one bank transfer" and a one-time reference; then one bulk batch with **a separate voucher per item** (own number, own tour, own invoice). The accountant initiates **one** transfer for the batch; the bank approver marks the whole batch Paid; **one itemised remittance advice**. The batches tab shows the bulk parent with its roll-up and the vouchers grouped under it. Screenshots: kit `shots/01…15`.

### 2c. A gap found in R4 and closed in R5
R4 recognised only the legacy "Rejected" flag (Aflag 2) as a dead voucher. A voucher cancelled on the legacy **Voucher Cancellation** screen (Aflag 3) could still be initiated or marked Paid by the desk. R5 treats both as cancelled everywhere (never initiated, paid, re-used or offered for bulk payment; shown as cancelled). On the development system the gap stays open until R5 is installed; it needs a cancelled voucher and a user who then presses Paid on it — the legacy Bank Payment Check never lists a cancelled voucher — so the practical risk is low, but it is real.

### 2d. Deliverable
**tf-paydesk-r5.zip** (34 source files: the 29 R4 files re-shipped, 12 changed, plus 5 new; installer with "check / apply / rollback"; README; tests; evidence; screenshots). It installs over R4, no core accounting table is altered, no voucher table is converted. One point to be clear about: the bulk payment issues its vouchers through the new writer (one voucher per item cannot be produced by the chain's bulk call, which collapses items into one voucher); the R2 single batch and Pay Individually Now keep the chain's writer. Installing R5 therefore means approving the new writer **for bulk vouchers only**.

## 3. Dashboard preview — ready for your final visual approval
The design-only preview (dummy data, desktop and phone) is published at https://claude.ai/artifact/PBbwiTysUojpgqBDag2PeZ and in `tpd-dashboard-preview.zip` (16 screenshots). It follows your choices: banner kept; side-by-side detail on large screens, drawer below on smaller ones; purple for ordinary action-required, red only for overdue or blocked; "ledger balanced · bank-statement match pending" wording. The working dashboard is untouched.

## 4. Decisions needed from you

1. **Dashboard** — approve the preview's visual design (or list changes). Only after approval would the working dashboard be replaced, as a separate kit.
2. **R5 installation on the development system** — approve `tf-paydesk-r5` (install over R4, run Setup once for schema 7). If approved, also decide which profiles receive the new request privilege: Hotel Reservations Desk (32) and Transport Desk (33) are clear; who requests restaurant and other payments — Quotation Desk (31) or Accountant (30)?
3. **Decision A (MyISAM → InnoDB for voucher, v_inv, invoice_no, payment_request)** — the completed migration, compatibility, transaction-integrity and rollback results are in `tf-paydesk-r5-decision-a-engine-migration-plan.md` and the evidence zip; the R5 suite now also passes on both engines. Approve, or keep deferred (R5 works on MyISAM with the verified undo path).
4. **Decision B (the new voucher writer for the R2 single batch too)** — not needed for R5; approve only if you want one writer everywhere.
5. **R4 cancellation gap** — accept it as closed by R5, or ask for a small R4 hot-fix on the development system before R5.
6. **Staff accounts** — no change; the seven profiles stay unassigned until you give the new staff details.

After your decisions on 1–2 we return to Step 2, Daily Bank Reconciliation, at its present inspection stage.

## 5. Pre-existing quirks noticed (not changed)
- The legacy Bank Payment Check logs a browser console error ("_DT_CellIndex") from its petty-cash table on every load, also without R5; the page works.
- The legacy voucher cancel answers HTTP 500 after cancelling an unposted voucher; the cancellation itself is complete.
