# Live verification — Step 1B (bank reconciliation inspection) and tf-paydesk-r4 voucher-number preflight

Avenir Holidays · read-only · 11 Oct 2026 06:18–06:25 (server time) · prepared for Shantha

**How it was done.** The live application at `C:\wamp64\www\avenir` reads database `avenir` on the WAMP MySQL at `localhost:3306` (settings in `app/Config/Database.php`; the `.env` database lines are commented out). That server is not reachable from the sandbox, so every query below was run as SELECT / SHOW statements only, through phpMyAdmin 5.2.3 in the built-in browser after Shantha signed in herself. Nothing was written: no index, no trigger, no table, no row, no file on live. The 11 live application files were re-checked by checksum before this (all unchanged; no desk file exists on live).

**Server:** MySQL 8.4.7 on host ShanthaW · database `avenir` (230 tables, default charset utf8mb4) · default storage engine MyISAM · binary logging OFF · `log_bin_trust_function_creators` = 0 (irrelevant while binary logging is off) · `sql_mode` empty (non-strict) · phpMyAdmin session = root@localhost (ALL privileges incl. CREATE, INDEX, TRIGGER).

---

## A. Step 1B — the questions asked

### 1. Bank account names and ledger codes (heading 1-6-8 "Bank Accounts")

| Ledger code | Name on live | Currency (from the name) | Ledger rows (FY from 1 Apr 2026) | Book balance ΣDr−ΣCr (LKR) |
|---|---|---|---|---|
| 1-6-8-10380 | Com Bank LKR Account | LKR | 1,844 (27 Jan 2026 → *2525-04-16*) | **−42,713,062.85** (Dr 64.73M / Cr 107.45M) |
| 1-6-8-10898 | Com Bank USD Account | USD | 23 (1 Apr → 24 Oct 2026) | −17,002,498.43 (Dr 34.46M / Cr 51.46M) |
| 1-6-8-10998 | Pan Asia Bank LKR Account | LKR | 32 (9 Apr → 25 Aug 2026) | +2,935,163.50 |
| 1-6-8-10999 | Pan Asia Bank USD Account | USD | 0 | — |
| 1-6-8-11000 | Pan Asia Bank YEN Account | JPY (name says YEN) | 0 | — |
| 1-6-8-9750 | Bank Control | not a bank — the control account the voucher code uses for non-supplier vouchers (`sup<>1`) | 125 (8 Apr 2026 → *2030-01-01*) | −11,622,119.62 |

Under titel 1-6 there are also 1-6-112-10782 "Deposits in Transit" (the reconciliation holding account, 0 rows) and 1-6-112-11024 "Advance deposit". None is deactivated. The shadow USD ledger `account_runing_usd` holds **one** row (Com Bank USD, Dr 48,305.00 on 15 Jun 2026). Vouchers by paying account: 7,411 from Com Bank LKR (7,369 finalized), 15 from Pan Asia LKR, 2 from Com Bank USD, 371 from the migrated "opening balance" control, 3 from Petty Cash, **148 with no paying account at all** (131 Petty-Cash-type vouchers, 12 driver advances, 1 hotel; 4 carry the literal text "Select a Bank Account"), and 20 driver vouchers whose "bank" field holds the driver's own supplier code.

The test copy had only two bank accounts (Commercial Bank Account 1-6-8-10380, Sampath 1-6-8-10390) and no USD or YEN account; the live names differ ("Com Bank LKR Account"). **Interpretation (not a fact from the data):** the LKR and USD book balances are credit balances — payments are posted to the bank accounts (107M LKR since April) but receipts are not reaching them in the same measure; see B.5 and B.6 — this is central to the reconciliation design: the bank statement, not the ledger, must be the source of the cash figure.

### 2. LKR vs USD
Only by the account name (there is no currency field in the legacy chart): LKR = 1-6-8-10380, 1-6-8-10998; USD = 1-6-8-10898, 1-6-8-10999; YEN = 1-6-8-11000; 1-6-8-9750 is a control account, not a bank. The desk's `tpd_bank_account` master (test copy only) is where a currency is recorded per account.

### 3. Have the legacy reconciliation flags ever been used on live?
* Current ledger `account_runing`: `bank_rec = 1` on **2 rows**, `bankst_flag = 1` on the same 2 rows — one entry (ID 5915, "VOUCHER PAYMENT", 178,020.00, dated **2030-01-01**, user Niroshan): effectively a single test. 0 rows parked in Deposits in Transit, 0 rows with `New_L_Code`, 0 rows with `Bank_Date`, `BankFlg` all 0, cheque register `chk_runing` empty.
* Archived ledger `account_runing_2024` (15,900 rows, 1 Apr 2024 → 11 Jul 2025): **5,604 rows flagged** `bank_rec = 1` and `bankst_flag = 1` — the legacy screen **was** used for FY2024/25.
* `account_runing_bank_statetment`: **10,808 rows** (5,485 entries, 31 Mar 2025 → 2037-03-07, ΣDr 2.26bn / ΣCr 2.26bn, 2 rows flagged) — a copy of a ledger, not referenced by any code (the test copy had it empty).
* The screen exists in the live menu: 112 "Bank Reconcillation" (`accounts/bank_reconcil`), 122 Details, 123 Summary, 184 "Bank Req Data Transfer" (`accounts/bank_unrealised_value`), granted to groups 1, 17 (Auditor), 25 (Admin). Activity log: `accounts/bank_reconcil` opened **95 times** (3 Apr → 12 Aug 2026) by Nimesha 30, super admin 38, Ravishan 23, Nipuna 4; `accounts/bank_statement` 286 times; `report/bank_payment_check` 2,344 times (to 7 Sep 2026); `accounts/debtor_payment` 169; `accounts/usd_withdrawal` 18.

### 4. Do the hard-coded account codes exist today?

| Code | Used by | On live | Ledger rows |
|---|---|---|---|
| 1-6-8-9750 | non-supplier vouchers (`sup<>1`) | **yes — "Bank Control"** | 125 |
| 1-6-112-10782 | reconciliation holding (unrealised) | yes — "Deposits in Transit" | 0 |
| 1-2-5-10779 | Debtor Control (debtor receipts) | yes — "Debtor Control Account" | 33 |
| 8-35-111-10780 | FX gain/loss on debtor receipts | yes — "Foreign Exchange Gain-Loss" | 13 |
| 1-2-3-10900 | petty cash (vouchers, petty cash report) | yes — "Petty Cash Account" | 44 |
| 1-2-108-10382 | driver over-payment (fund return) | yes — "Supplier Over Payments" | 0 |
| 4-8-106-10385 / 10386 | transport cost / hotel charges accrual | yes | 194 / 727 |
| 4-8-109-10653 | lunch / other fee accrual | yes — "LUNCH" | 709 |
| 5-7-7-10388 | Tour Income | yes | 1,040 |
| 1-2-5-10635 | LionTours (hard-coded in other-invoice receipts) | yes | 146 |
| 3-15-33-10749 | opening-balance control (migrated vouchers) | yes — "opening balance" | 0 |
| 6-11-107-10381 | advances (tour details) | yes — "Advance Payment" | 0 |
| **1-6-8-9352** | cheque vouchers (VoucherPaymentController) | **NO** | 0 |
| **1-2-3-1000** | cash vouchers, Cash Book | **NO** | 0 |
| **1-2-4-1000** | old petty-cash code (CashBookController::petty_cash) | **NO** | 0 |
| **6-11-107-9663** | customer advances (`accounts/advance_payment`) | **NO** | 0 |

The four missing codes have no ledger rows, so nothing is orphaned today; but a cheque voucher, a cash voucher or an advance receipt through those screens would post to a non-existent account (and vanish from the ledger views). Rows posted to a code that is not in the chart: 8 zero-amount rows with an empty code ("254-/260- Supplier Invoice", user Dulmi, May–Jun 2026) and **one row with code "0": Dr 15,868,192.50, "STW MAY 2026 - debtor payment", user Nimesha, 15 Jun 2026** — a 15.9M debit that reached no account (Accounts should look at this; it is a receipt whose bank leg is missing from the bank ledger).

### 5. Current counters (`invoice_no`) and the tables behind them

| Counter | Value | Table maximum | In step? |
|---|---|---|---|
| ledger entry `acc_id` | 28,989 | MAX(account_runing.ID) 28,989 | yes |
| voucher number `V_Num` | **8,020** | MAX(voucher.Vnum) **8,020** | **yes** |
| `voucher` (Voucher_No counter) | 10,007 | MAX(voucher.Voucher_No) NULL (never written, as known) | n/a |
| `debtor_receipt_no` | 501 | MAX(debtor_receipts.receipt_no) 99 | no — the receipt screen draws two numbers per receipt (one for the bank leg), as noted in Step 1 |
| debtor invoice `Invoice_2` | 1,828 | 2,039 invoices (2024-04-01 → 2026-09-08) | n/a (manual numbers also exist) |
| advances `No` | 639 | — | |
| journal `JE_inv` | 1,069 | MAX(JE_INV) 1,069 | yes |
| `Tour_No` / `inquiry_no` | 2,386 / 18 | | |

Volumes: vouchers **7,975** (first 25 Apr 2024, last **7 Sep 2026 18:57**; 7,897 finalized, 10 pending Aflag 0, 7,654 posted, 234 rejected, 77 cancelled); payment requests **3,110** (10 requested-awaiting-approval, 906 approved, 2,159 completed, 8 cancelled, 25 rejected; last request **8 Sep 2026**); debtor receipts 421 (17 Apr 2025 → 30 Sep 2026); ledger 11,364 rows / 5,634 entries (27 Jan 2026 → 2525-04-16); daily USD rates 7 (19 Aug → **7 Sep 2026**, 325.00 by Ravishan). Last application log entry and last session: **today, 11 Oct 2026 02:35**. (Payment activity stops on 7–8 Sep while logins continue — worth confirming whether day-to-day payments are being made on this copy or elsewhere.)

### 6. tpd_* tables and the new supplier-payment tables
**None exist on live.** The `tpd_bank_account` master and the ten other desk tables are test-copy only. No `tpd_` table, no `uq_tpd_voucher_vnum` index, no `tpd_voucher_vnum_guard` trigger; `voucher` has only its PRIMARY key on ID.

### 7. Differences from the test copy inspected (2024-09-25 production snapshot + test data)
* Server: MySQL **8.4.7** on live vs MariaDB 10.11 on the test copy (the `utf8mb4_0900_ai_ci` collation used by the live Payment Approval query exists here — the test-server artefact noted in the r4 README does not apply to live). `payment_request` is utf8mb4_0900_ai_ci; voucher/v_inv latin1; ledger utf8mb3.
* Chart of accounts: 1,165 accounts live vs 715; six 1-6-8 accounts incl. USD and YEN vs two; the control/holding accounts above exist on live but not in the snapshot.
* Ledger re-based: the current `account_runing` starts 27 Jan 2026 (FY2025/26) with FY2024/25 in `account_runing_2024`; opening balances for the banks were entered as **one-sided journals on 1 Apr 2026** (Com Bank LKR Dr 497,850.38; Com Bank USD Dr 161,135.32 — entries 25915 and 25918 have no credit leg). No "Brought Forward"/YEP rows.
* Groups: live has 23 groups (Auditor 17, ticketing 18/19/20, Tmp_account 22, Transport Manager 23, Agent Chat User 14; "Accounts" 11 is deleted); 71 users (59 enabled); enabled users per group: System Administrator 1, Accountant 1, Auditor 3, Driver 36, Operational 4, … — **group 25 "Admin" has no enabled user**, so "Payment approval" (granted to 1 and 25) is effectively the super admin's alone.
* Menus: 180 entries; the Tour Payment Dashboard (200) and Payment approval (216) sit in category 27 "Pilot Report" (not "Payments"); Daily USD Rate is menu 217 (test: 257). The kit's Setup finds categories by the dashboard's own URL, so this difference does not affect it.
* Data quality items not present in the snapshot: 8 unbalanced entries (three lunch-voucher pairs with unequal legs, e.g. Dr 5,000 / Cr 6,000; the two one-sided opening journals; three one-sided hotel accrual credits 27724/27725/28358 of Aug–Sep 2026); future-dated rows (2525-04-16 "FUEL CHARGES" journals, 2037-03-07, 2030-01-01, 2029-12-24) that distort any "balance up to date"; voucher type spelt both "Bank Transfer" (7,035) and "BT" (809).

---

## B. tf-paydesk-r4 voucher-number preflight (read-only)

| Check | Result on live |
|---|---|
| vouchers / distinct Vnum | 7,975 / 7,975 — **no duplicate** |
| NULL / zero / negative Vnum | 0 / 0 / 0 — **none** |
| min / max Vnum | 1 / 8,020 |
| counter `invoice_no.V_Num` vs MAX(Vnum) | 8,020 vs 8,020 → **counter_minus_max = 0, in step** (1 counter row) |
| gaps | 45 unused numbers inside the span 1–8,020 (harmless; numbers are never reused) |
| orphan voucher lines (`v_inv.V_tkn` with no voucher) | **5** — tokens 5165 (22 Dec 2025), 5182 (23 Dec 2025), 6761, 6762, 6763 (8 Jun 2026, tour 2098, 3,000 / 35,750 / 51,000) |
| ledger "VOUCHER PAYMENT" rows referring to a voucher that no longer exists | **2** — voucher numbers 6761 and 6762 (postings exist, the voucher rows do not: deleted after posting) |
| vouchers without lines | 0 |
| existing index on voucher | PRIMARY (ID) only — no unique index on Vnum yet |
| existing triggers on voucher | none |
| grants of the signed-in user | root@localhost — ALL privileges (CREATE, INDEX, TRIGGER included); the application connects with the credentials in Database.php (not read) |
| server / binary logging / trust_function_creators / sql_mode | MySQL 8.4.7 / OFF / 0 / '' |
| tpd_ tables present | none — a fresh install |

**Compatibility conclusion:** the proposed unique index on `voucher.Vnum` and the BEFORE UPDATE guard are compatible with the existing 7,975 rows (all distinct, none invalid) and with voucher creation (the counter is exactly in step, so the next number 8,021 is free; every live code path that writes `Vnum` is an INSERT at creation; nothing updates the number). Binary logging is off, so the trigger needs no special setting. **Nothing was created** — the index and trigger would be added only by Setup after your approval. The orphan lines and the two missing vouchers 6761/6762 are pre-existing data-integrity items for Accounts; they do not conflict with the index.

---

## C. Warnings, conflicts and blockers for the installation decision (nothing done about any of them)

1. **Not a blocker, but a decision:** the desk's Supplier Payables would list, after Setup, the approved-but-unvouchered requests that have no voucher for their supplier and tour — on live there are **5** (Flower Drum – Warehouse, lunch, AH 2083 / 2098 / 1952 / 1892 / 1929, approved by Ravishan 11–17 Aug 2026, LKR 30,000 in total); the other 153 "approved, voucher_created = 0" requests do have a later voucher for the same supplier and tour and are paired to it. Accounts should decide those 5 (batch them, or cancel) once installed. There are also 10 requests awaiting approval (incl. 7 from 2025–Mar 2026 on ST 842 / ST 1240) and 10 vouchers pending authorisation (Aflag 0, 21 Aug – 7 Sep 2026) that the old Bank Payment Check still owns — they are untouched by the kit.
2. **Paying accounts:** the desk pays LKR vouchers only from LKR accounts recorded on the Avenir Bank Account Master; the live chart has Com Bank LKR, Pan Asia LKR (and USD/YEN accounts that cannot pay a voucher). The 148 vouchers with an empty paying account are legacy records; the kit never touches them.
3. **Three ledger accounts to be created/approved by Accounts before mapping:** Realised Exchange Gain, Realised Exchange Loss, Bank Charges. Live has "Foreign Exchange Gain-Loss" 8-35-111-10780 (used by debtor receipts) — Accounts may map gain and loss to two existing or new accounts of their choice; the kit does not invent any.
4. **Data-quality findings for Accounts (read-only, pre-existing):** the 15,868,192.50 debit posted to account "0" on 15 Jun 2026; 8 unbalanced entries; future-dated rows; the two one-sided opening journals; 5 orphan voucher lines and 2 vouchers deleted after posting; "Select a Bank Account" stored as a bank code on 4 vouchers. None of these blocks the kit; all of them matter for the bank-reconciliation design.
5. **Where the live data actually is:** this database shows logins today but no payment, voucher or daily-rate activity after 7–8 Sep 2026. Please confirm that this WAMP copy is the system Accounts uses for payments (and not a mirror of a hosted system), because the installation and the reconciliation module must go where the payments are made.
6. **No blockers** were found for the preflight itself: no duplicates, counter in step, user has the privileges, no tpd_ tables.

Stopped here. No installation, no Step 2.
