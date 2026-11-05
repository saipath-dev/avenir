# tf-paydesk-r5 — Decision B: the atomic voucher writer, demonstrated against the legacy writer

Cloud test copy only (8081 / `avenir_test`, post-suite state, 11 Oct 2026). **No voucher-writing logic on the development system has been replaced.** The prototype (`App\Libraries\TourPayments\Vouchers`) and the switch that lets the test copy's batch creation call it (`POST writer=proto`, honoured only when the test copy's `.env` sets `TPD_WRITER_PROTO=1`) exist in the cloud copy alone; both are in the evidence folder for reading.

## 1. What the prototype is

A writer that produces exactly the rows the chain's `create_bulk_voucher` produces in batch mode — one `voucher` row, one `v_inv` line per request, `payment_request.voucher_created = 1`, `invoice_no.V_Num` advanced — with the same column values and conventions (payee, supplier account, bank, `ref_po`/`C_Number` = tour, `advances_pay` 1 for driver advances / 2 otherwise, `doller_amt`, descriptions "Hotel Reservation …", "Transport Advance …", "Lunch or Other Fee …"), but:

- inside **one database transaction**, under the r4 voucher-issue lock;
- after checking **every number it is about to use** against the voucher table (refused before writing if the counter is behind);
- re-reading its own rows before commit (count of vouchers issued = count intended);
- in two modes: `single` (one voucher for the items — the r4 behaviour) and `per_item` (one voucher per item, consecutive numbers — the r5 behaviour);
- on any failure: ROLLBACK, a clear message, an audit row (`voucher_issue_failed`), and the items remain waiting.

Everything else in batch creation (eligibility, supplier-bank verification, the suggested paying account, `tpd_batch`, `tpd_request_ext`, audit) is the unchanged r4 code around it.

## 2. The demonstration — 38 of 38 checks (voucher_writer_compare.py, writer_compare.txt)

Starting state for every scenario (restored from the same snapshot each time): 12 approved, unvouchered restaurant lunches for GIMANHALA LUNCH VOUCHER on two tours (AH 2218, AH 2290), LKR 31,000.00; counter 615 = last voucher 615; the four tables on InnoDB.

| Scenario | Result |
|---|---|
| **S1 legacy writer** — one voucher for the 12 items through the r4 batch creation | voucher 616, Amount 31,000.00, 12 lines, 12 requests flagged, counter 616 |
| **S2 prototype, single mode** — same items, inputs, user | voucher 616; **voucher row identical in all 69 columns** (row id and timestamps excepted); **12 `v_inv` lines identical in all 28 columns**; payment_request flags, request links, batch row and counter identical; row counts of voucher, v_inv, payment_request, tpd_batch, tpd_request_ext, tpd_audit and account_runing equal to the legacy run (nothing extra written) |
| **S3 prototype, per-item mode** | **12 vouchers 616–627**, consecutive, under one batch; Σ = 31,000.00 = the single-voucher total; each voucher carries its own tour and amount; exactly one `v_inv` line per voucher; every request linked to its own number; counter advanced by exactly 12; column values identical to the single voucher apart from number, amount, tour and dollar figure; audit `bulk_issued` with the 12 numbers |
| **S4 failure injected after voucher 3 of 12 (InnoDB)** | refused "Nothing issued: …"; vouchers, lines, counter, request flags and batches **unchanged**; the 12 items still wait in Supplier Payables; audit `voucher_issue_failed`; retried without the fault → the **same numbers 616–627** (no gap) |
| **S5 counter behind the table** | refused **before writing**, naming the counter and the last issued number; the r4 legacy path refuses the same situation the same way |
| **S6 the same failure with the four tables on MyISAM** (the development database today) | the run is refused, but **vouchers 616, 617, 618, their lines, three request flags and the counter remained** — the partial batch the requirement forbids; this is Decision A |
| **S7 downstream on prototype vouchers** | bank initiation and Paid with LKR 155.00 charges succeed; charges allocated over the 12 lines to the cent; ledger check: credits to the paying account 31,000.00 + 155.00 = 31,155.00 = the bank debit, every entry balanced; 12 balanced bank-charge pairs named with the voucher number; Finalized Bank Check, Voucher Report, Complete Tour Record and the voucher document render with it (all 12 lines, both tours); legacy Voucher Cancellation behaves **identically** on a fresh legacy voucher and a fresh per-item prototype voucher (Aflag/flag 3/1, same ledger rows, same HTTP answer — the legacy screen answers HTTP 500 on an unauthorised voucher in both cases, a pre-existing quirk); cancelling one per-item voucher leaves the other 11 untouched; the legacy Bank Payment Check lists the 11 open per-item vouchers individually; each request linked to its own number; **historical vouchers (≤ 615) untouched by every scenario** (count and checksum) |
| **S8 legacy authorisation and screens** | Payment Authentication (`voucher_auth/update`) gives the **same result on both writers** (HTTP status, Aflag, authorising user and date, ledger rows posted — count, Dr, Cr); New Voucher Report, Voucher Audit, Payment Release and Voucher Payments render identically and show/omit the voucher the same way; authorising one per-item voucher posts that voucher's own amount only; audit trail: `batch_created` for both, `bulk_issued` only for per-item runs, `voucher_issue_failed` only for failed runs |

Also relevant: the full r4 suite (202/202) ran on the converted tables (Decision A evidence), so the legacy writer itself is unaffected by the engine change.

## 3. Your six confirmations

| | Confirmed by |
|---|---|
| **Numbering** | consecutive numbers from the same counter (S1–S3); refused before writing when the counter is behind (S5); no gap after a failed run (S4); the unique index and guard trigger still the backstop (Decision A rehearsal) |
| **Existing reports** | Voucher Report, New Voucher Report, Voucher Audit, Payment Release, Voucher Payments, Finalized Bank Check, Bank Payment Check, Complete Tour Record, the desk voucher document — all render with prototype vouchers, identically to legacy ones (S7, S8) |
| **Authorisation** | legacy Payment Authentication identical on both (S8); r4 initiation / Paid / charges / ledger check pass on a prototype voucher (S7) |
| **Cancellation** | legacy Voucher Cancellation identical on both; one per-item voucher can be cancelled alone (S7) |
| **Audit trails** | tpd audit rows per step; failures recorded with the reason; the legacy `Aut_user`/`aut_date`/`rej*` columns filled by the legacy screens exactly as before (S7, S8) |
| **Historical records** | every scenario left vouchers ≤ 615 unchanged (count + checksum), and the Decision A fingerprints cover every row of the nine tables |

## 4. What the prototype does not yet do (r5 build scope, after approval)

Batch-level initiation and Paid for N vouchers with one bank transfer and the resumable ledger postings; the `tpd_batch_voucher` link table (the prototype links through `tpd_request_ext.voucher_vnum`); the one-currency rule; the disputed flag; the itemised remittance advice; the Supplier Payables review screen with Select-all-eligible and the confirmation step; the one-time submit token; blocking the legacy per-voucher Mark Finalized for per-item batch vouchers; the same-user guard (a user never approves or marks Paid what they requested or initiated). All listed in the impact report §4–§9 and test plan §10.

## 5. Recommendation

Adopt the prototype's approach as the r5 voucher writer, used **only** by the Tour Payment Desk's batch creation (legacy screens keep calling the legacy code), together with Decision A for the four tables. Until Decision A is approved, the writer falls back to r4's verified-write-and-undo and says so — it never claims to be atomic on MyISAM.
