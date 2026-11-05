# tf-paydesk-r5 — Bulk supplier payment (one voucher per item): impact report

Prepared 11 Oct 2026 from the installed r4 code and the development database `avenir` (read-only). **Nothing has been built or changed.** This report says what r5 would change, what it would not, where the risk sits, and the two decisions needed before building.

## 1. In one paragraph

r4 pays a supplier through **one voucher per batch**: management selects a supplier's approved items, one Payment Voucher (PV) is issued with one `v_inv` line per item, the accountant initiates one bank transfer, the bank approver marks it Paid, and the exchange difference and bank charges are allocated to the tour lines. r5 keeps the batch, the single bank transfer, the rate/account/date confirmed once at batch level, and the per-tour allocation — but issues **one voucher with its own number per item**, links every voucher to the batch, produces one itemised remittance advice, and reconciles the one bank-statement debit against the batch and all its vouchers. The sum of the vouchers equals the transfer; the allocated charges add back exactly to the charge entered.

## 2. Two decisions needed first

**Decision A — table engine.** `voucher`, `v_inv`, `invoice_no`, `payment_request` and `account_runing` are **MyISAM** on the development database (85 of the 217 tables are). MyISAM has no transactions: `BEGIN … ROLLBACK` does nothing to them. "If any voucher fails, create none" can therefore be **guaranteed** only if the four voucher tables are converted to InnoDB (`ALTER TABLE … ENGINE=InnoDB`). The conversion is small (voucher 2.5 MB, v_inv 1.7 MB, payment_request 0.6 MB, invoice_no 1 row; no FULLTEXT or spatial indexes; MySQL 8.4 on dev), keeps every row and index, and is reversible by the same statement with `ENGINE=MyISAM`. It is still a change to four core accounting tables and needs Avenir's explicit approval and a backup before it runs. The legacy code reads and writes these tables through the same SQL and does not use any MyISAM-only feature (no `LOCK TABLES`, `INSERT DELAYED` or `FULLTEXT` in the application). Without the conversion, r5 can only *approximate* all-or-nothing (write in strict order, verify each row, undo this call's own rows on failure — what r4 already does for its one voucher); a crash mid-way could leave a partial batch that a repair step would then have to find and remove.
*Recommendation: convert the four voucher tables now; leave `account_runing` (the ledger) as it is and make the Paid postings resumable and verified per voucher (section 5), with the ledger conversion as a separate later decision.*

**Decision B — who writes the vouchers.** r4 calls the legacy `create_bulk_voucher` once per batch. For N per-item vouchers the options are (i) call it N times inside one transaction — simple, but it was written for one call per request and reads/rewrites the voucher counter non-atomically (`SELECT V_Num` then `UPDATE`), or (ii) a tf-paydesk voucher writer (`TourPayments\Vouchers::issue`) that inserts exactly the same `voucher` and `v_inv` rows the legacy call produces (same columns, values and conventions, verified column-by-column in the test suite against legacy-issued vouchers), inside the transaction and under the voucher-issue lock. *Recommendation: (ii), because it is the only way to issue N consecutive numbers atomically and to stop before anything is written when the counter is behind the table.*

## 3. What stays exactly as it is

Requests, approval, the approval rates (never overwritten), Supplier Payables grouping, the r4 single-voucher batch for suppliers that need it, Pay Individually Now, supplier-bank verification, the Daily USD Rate dependency, the Bank Charges / Realised Exchange Gain / Loss mappings, the voucher-number unique index and guard trigger, the voucher-issue named lock, every legacy page and report, and every historical record. The pre-existing accounting irregularities documented in the Step 1 report are not touched.

## 4. Database changes (schema version 7 — additive only)

| Object | Change | Why |
|---|---|---|
| `tpd_batch` | add `mode` ('single' = r4 behaviour, 'per_item'), `voucher_count`, `transfer_total` (the one bank transfer, in the batch currency), `transfer_lkr`, `bank_ref`, `payment_date`, `usd_rate`, `charges_total`, `charges_allocated` (must equal `charges_total`), `submit_token` (unique), `reconciled`, `reconciled_at`, `selected_by/at`, `confirmed_by/at`, `paid_by/at` | one bank transfer per batch; duplicate-submission guard; batch-level audit |
| `tpd_batch_voucher` (new) | `batch_id`, `vnum` (unique), `pr_id` (unique), `tour_id`, `sup_acc`, `service`, `invoice_no`, `currency`, `amount`, `approval_rate`, `approval_lkr`, `paid_rate`, `paid_lkr`, `fx_diff`, `charge_share`, `status` | the link voucher ↔ batch ↔ item; the per-voucher figures |
| `tpd_request_ext` | add `disputed` (0/1), `dispute_reason`, `disputed_by/at` | "disputed" items can be excluded with a reason; `on_hold`/`check` flags already exist |
| `tpd_bank_initiation`, `tpd_bank_approval` | keep one row per voucher (unique `vnum`); the batch-level figures live on `tpd_batch`; rows for per-item batches carry `batch_id` | the existing per-voucher pages and reports keep working |
| `tpd_remittance` | add `batch_id`; one advice per batch lists every voucher | itemised remittance |
| `tpd_audit` | new actions: `bulk_selected`, `bulk_confirmed`, `bulk_issued` (count, first–last PV), `bulk_issue_failed`, `batch_initiated`, `batch_paid`, `batch_reconciled`, `item_disputed` | full who/when record |
| Engine | Decision A: `voucher`, `v_inv`, `invoice_no`, `payment_request` → InnoDB | transactions |

No legacy column is altered or removed. Rollback of r5 files leaves the new tables and columns in place (data preserved), exactly as r1–r4 do.

## 5. Accounting structure

1. **Voucher per item.** For each selected item one `voucher` row (Amount = the item, `ref_po`/`C_Number` = the tour, `Acc_No` = the supplier, `Bank_acc` = the suggested paying account, `v_typ` Bank Transfer, `advances_pay` 1 for driver advances, 2 otherwise) and exactly one `v_inv` line (tour, description, invoice reference, amount, rate), as the legacy code lays them out. Numbers are consecutive from the counter (e.g. PV 8021–8080 for 60 lunches); the batch number (`SB-00031`) is written on every voucher and every document.
2. **One bank transfer.** The accountant initiates the batch once: payment date, paying account, rate of that date (for USD), bank reference, total charges. The system writes one `tpd_bank_initiation` row per voucher (amount = the voucher) and the batch totals on `tpd_batch`. Σ voucher amounts = `transfer_total`, checked before saving.
3. **Paid once at batch level.** The bank approver marks the batch Paid; the system runs the r4 per-voucher Paid for every voucher under the batch lock: lock, final LKR at the payment-date rate, FX difference posted (Dr/Cr Realised Exchange Gain/Loss against the paying account, one pair per voucher), bank-charge share posted (Dr Bank Charges / Cr paying account, one pair per voucher), legacy `finalized` flag and bank date on every voucher, hotel-night paid flags, payment-request status. Postings are **idempotent and resumable**: every entry carries the voucher number and batch id; if the process stops after voucher 37 of 60, re-running Paid posts only the missing 23 and the batch is not marked Paid until the reconciliation passes. (With `account_runing` still MyISAM this is the strongest guarantee available; with it converted, the whole Paid becomes one transaction.)
4. **Charges allocation.** `charges_total` is allocated across the vouchers pro rata to their final LKR by the largest-remainder method already used in r4, so the shares add back to the total to the cent; each share is posted once and stored on `tpd_batch_voucher.charge_share` and `tpd_request_ext.charge_share`. Zero charges → nothing posted. A missing Bank Charges mapping blocks Paid before anything is written (as in r4).
5. **Currencies.** A batch is one supplier and **one currency**; Select-all-eligible works within the currency shown, and a supplier with USD and LKR items gets two batches.
6. **Complete Tour Record.** Each voucher already references its tour, so the tour record shows its own voucher number, amount, rate, FX difference, charge share and batch number — one payment record per item, as required.

## 6. Voucher numbering and locking

The whole issue runs under `GET_LOCK('tpd_voucher_issue')` (r4's lock, now held for the N numbers), inside one transaction. Before writing: counter read, `MAX(Vnum)` read, the next N numbers checked against the `voucher` table; if the counter is behind the table the batch is refused with nothing written (r4 message). The unique index `uq_tpd_voucher_vnum` and the guard trigger remain the database backstop. After commit: every voucher row re-read and counted; mismatch = failure (cannot happen after a committed transaction, but the check and the audit row stay).

## 7. Reconciliation

- **Batch ↔ vouchers:** Σ `tpd_batch_voucher.amount` = `tpd_batch.transfer_total`; Σ `paid_lkr` + `charges_total` = `total_debit`; Σ `charge_share` = `charges_total`. Stored as `tpd_batch.reconciled` with the figures and the time.
- **Ledger ↔ bank:** the r4 check extended to the batch: all credits to the paying account's ledger for the batch's vouchers (payments + charge shares) = the bank debit; every entry Dr = Cr; FX and charges posted for every voucher.
- **Bank statement ↔ batch:** the batch's `bank_ref`, `payment_date`, paying account and `total_debit` are the match key for the one statement line. Bank Reconciliation Step 2 (not started) will consume this; the legacy `bank_rec`/`bankst_flag` columns are not touched by r5.

## 8. User-interface changes

1. **Supplier Payables** — opening a supplier shows a review table: tour, guest, service, invoice/ref, amount, approval rate, provisional LKR, due, and an **eligibility chip** per item (Eligible / Excluded: cancelled · on hold · requires checking · disputed · already vouchered · already paid · bank not verified · other currency). Buttons: **Select all eligible (n)**, per-row checkboxes to deselect, a summary bar (selected count, total in the batch currency, provisional LKR), **Review & confirm payment**. The excluded items are listed with their reason under the table. Mark as disputed (with reason) from the row menu.
2. **Confirmation step** — supplier, paying account (suggested), currency, item count, total, the list of items, "N vouchers will be issued (PV next–next+N−1) under one batch", warnings (unverified bank, missing mappings). One button, disabled while submitting; the confirmation carries a one-time token.
3. **Batch page** — header (batch number, supplier, N vouchers, transfer total, status), the voucher list (PV, tour, guest, service, amount, final LKR, FX, charge share, status), bank initiation form once for the batch, Paid once for the batch, the reconciliation card, the itemised remittance advice (PDF/e-mail), and the audit trail (selected / confirmed / initiated / paid, by whom, when).
4. **Bank Payment Check (legacy page)** — per-item batch vouchers appear grouped under their batch with "Paid from the batch page"; per-voucher Mark Finalized is blocked for them (no partial Paid, no double payment). Finalized records continue to list every voucher individually.
5. **Documents** — one voucher PDF per item (tour, service, invoice, supplier, batch number); one remittance advice per batch listing every voucher and item with the totals; the Complete Tour Record shows the per-item voucher.
6. **Dashboard** — the "Bank action" card and the Batched / Bank Check stages count batches; the detail panel shows "PV 8021 · batch SB-00031 (1 of 60)".

## 9. Your controls, and how each is met

| Control | Mechanism |
|---|---|
| Select only approved, unpaid, eligible items | eligibility computed server-side from `payment_request` status 4/6 + `voucher_created = 0` + no `batch_id` + not on hold / check / disputed / cancelled + bank verified + same currency; the client's selection is re-validated at confirmation |
| Exclude cancelled, held, disputed, vouchered, paid | the same rule; each exclusion carries its reason and is shown |
| Separate currencies | one currency per batch, enforced server-side |
| Lock numbering during creation | named lock + transaction + unique index + trigger |
| Prevent duplicate submission / double payment | one-time `submit_token` (unique) per confirmation; items already in a batch are refused; Paid once per batch under `GET_LOCK('tpd_batch_paid_<id>')`; legacy per-voucher finalize blocked for batch vouchers |
| All-or-nothing | one transaction over `voucher`, `v_inv`, `invoice_no`, `payment_request`, `tpd_*` (requires Decision A); on any error ROLLBACK, counter unchanged, audit `bulk_issue_failed` |
| Reconcile one statement transaction to the batch and its vouchers | section 7 |
| Full audit of who selected, confirmed, paid, when | `tpd_batch` columns + `tpd_audit` rows per step |
| Rollback preserves completed history | files-only rollback; tables and rows stay; r4 batches and legacy vouchers unaffected |

## 10. Tests to add (cloud copy first, then the development system)

48 Eligibility: every exclusion reason, mixed currencies split, already-vouchered refused. 49 Select all eligible / deselect / counts and totals. 50 Issue 60 vouchers: consecutive numbers, one `v_inv` per voucher, Σ = transfer, legacy layout identical to a legacy-issued voucher column by column. 51 Failure injection at voucher k (duplicate number, refused insert, killed connection): nothing written, counter unchanged, items still waiting. 52 Counter behind the table: refused before writing. 53 Duplicate submission (same token twice, two users, double-click): one batch. 54 Batch initiation: one transfer, N initiation rows, totals equal. 55 Batch Paid: N Paid rows, FX and charge pairs per voucher, charges add back to the cent (equal, unequal, 1-cent cases, zero charges, missing mapping blocks). 56 Resumable Paid: stop after k vouchers, re-run posts only the rest, batch not Paid until reconciled. 57 Reconciliation: batch ↔ vouchers ↔ ledger ↔ bank debit. 58 Documents: N voucher PDFs, one itemised remittance, tour records. 59 Legacy page: finalize blocked for batch vouchers; r4 single-voucher batches unaffected. 60 Rollback before and after per-item batches exist (files restored, data intact, r4 handles its own batches, re-apply loses nothing). 61 Engine conversion forward and back with fingerprints. Then the full r4 suite (0–47) again.

## 11. Risks and limits to state plainly

- Without Decision A, "nothing posts if anything fails" is best-effort, not guaranteed.
- `account_runing` stays MyISAM in this plan: Paid postings are resumable and verified, not atomic.
- The legacy voucher counter is read-then-written; other legacy pages that issue vouchers do not take the tf-paydesk lock. The unique index and trigger already stop duplicates; a legacy page could still hit the "number already issued" error while a large batch is being issued (a few hundred milliseconds).
- 60-item batches are small for the database (< 1 s); the remittance PDF for very large batches may need pagination.
- The live server's MySQL/MariaDB version and engines must be checked before this goes beyond the development system.

## 12. Sequence if approved

1. Avenir answers Decision A and B. 2. Backup (files + database copy, as for r4). 3. Engine conversion on the development database with before/after fingerprints. 4. Build r5 on the cloud test copy, run tests 0–61. 5. Install on the development system, run the on-machine checks, test rollback and re-apply. 6. Report and stop for approval.
