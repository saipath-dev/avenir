# Daily Bank Reconciliation & Bank Balance — Step 1: inspection report (read-only)

Avenir Holidays "New Generation" · prepared for Shantha · 11 Oct 2026

Nothing was changed for this step: no code, no schema, no tables, no accounting logic, no deployment. The inspection was made on the cloud test copy of the live code (identical to `C:\wamp64\www\avenir` apart from the tf-paydesk kit under test) and on the test database, which is a copy of the production dump of **25 Sep 2024** plus test data. Where the production chart of accounts may differ from that snapshot I say so.

---

## 1. What accounting / banking functionality already exists

**Ledger.** One double-entry ledger, table `account_runing` (MyISAM, LKR only). Every posting is a pair of rows sharing one entry `ID` (counter `invoice_no.acc_id`, `InvoiceNumberModel::incrementAccId`). Chart of accounts: `account_basement` → `account_titel` → `account_heading` → `account_posting`, code `B-T-H-P`; bank accounts are `1-6-8-*` (heading "Bank Accounts", ID 194), debtors/agents `1-2-5-*`, suppliers `6-9-11-*` / `6-9-23-*`, income `5-7-7-*`, cash `1-2-3-*`, advances `6-11-107-*`. Financial year April–March; `YearEndProcessController` posts closing pairs; FY2024/25 was archived to `account_runing_2024` and a second database `avenir_2024`.

**Company bank accounts.** No legacy bank master: "a bank" is any `account_posting` row under 1-6-8. In the snapshot there are two: `1-6-8-10380` Commercial Bank and `1-6-8-10390` Sampath (the latter looks test-added). USD banks are recognised only by the word "USD" in the account name (`DebtorPaymentController:38-39`, `UsdWithdrawalController:28-29`); none exist in the snapshot. The code also hard-codes `1-6-8-9352` (cheque) and `1-6-8-9750` (non-supplier vouchers) which are **not in the snapshot's chart** — on live these may exist, or postings to them may be orphaned. The tf-paydesk kit (test copy) adds `tpd_bank_account`, a proper master (bank, branch, account no, **currency**, SWIFT, ledger code, default, active, external display) used by the new payment flow.

**Bank Statement (ledger listing).** `accounts/bank_statement` (`BankStatementController`, menu 109): per-bank Dr/Cr for a date range, opening balance `SUM(Dr)-SUM(Cr) WHERE Date<date1`, running balance in the view. It is a ledger print, not a statement from the bank.

**Existing bank reconciliation screen — yes, one exists.** `accounts/bank_reconcil/*` (`Accounts/BankReconsillationController`, model `BankReconcilModel`, view `bank_reconcillation.php`, 15 routes at Routes.php 743-759). It works on two flags on the ledger rows: `account_runing.bank_rec` (ticked as cleared) and `bankst_flag` (locked by "Reconciliation"). Flow: load the bank's rows with `bankst_flag=0` up to an end date → tick rows (`update`, `finalize_selected`) → type the statement balance into a free-text box (JS shows the difference; **the statement figure is not saved**) → "Reconciliation" locks ticked rows → uncleared rows can be parked in holding account `1-6-112-10782` (`post_unrealised_entries`) and reposted. Print views `summary`/`details`; `reconcillation_report.php` is unfinished (prints counts where amounts belong). In the snapshot: **0 rows ticked, 0 locked, no menu entry** — it appears unused. Side effect to know: it flags by entry `ID`, so the counter-leg of the entry is flagged too (a bank-to-bank transfer ticked on one bank is ticked on the other).

**Bank Payment Check** (`report/bank_payment_check`, menus 255/256): lists unfinalized vouchers (`finalized=0, Aflag=0`), `mark_finalized`/`bulk_finalize` set `voucher.finalized, finalized_user, finalized_date, bank_date`; `finalized_records` lists paid vouchers by bank date. It marks a voucher as paid at the bank; it does **not** touch the ledger — it is not reconciliation.

**Dashboard bank balances** (`DashboardController::get_bank_balances`): all-time `ΣDr−ΣCr` per 1-6-8 account, LKR, from the ledger.

**Other accounting screens:** Cash Book (`1-2-3-1000`; petty cash `1-2-4-1000`/`1-2-3-10900`), Petty Cash report, Accounts Filtering (general ledger by account), Trial Balance, P&L, Balance Sheet, Liability report, Debtor Outstanding and Age Analysis (and creditor age analysis), Journal Entry (free Dr/Cr, no Dr=Cr check on the server, reversal), USD Withdrawal (`accounts/usd_withdrawal`), voucher reports, voucher cancel (reversal rows), Voucher Audit.

## 2. Relevant tables / models and their purpose

| Table | Purpose |
|---|---|
| `account_runing` (+ `_2024`, `_usd`, `_del`, `_copy*`, `_bank_statetment`) | The ledger. `account_runing_usd` = single-sided USD shadow rows for USD banks (0 rows in snapshot). `account_runing_bank_statetment` has the ledger schema, 0 rows, no code uses it. |
| `account_posting` (+ basement/titel/heading; views `acc_selct`, `account_runing_qq`, `account_runing_q2`, `bank_statement`) | Chart of accounts and ledger-with-chart views. `bank_statement` view = `account_runing_qq WHERE TName='Other Assets'` (all T=6 accounts, not only banks). |
| `voucher`, `v_inv` | Supplier payment vouchers and their lines. `Vnum` = Payment Voucher No.; `Aflag` 0 pending / 1 posted / 2 rejected / 3 cancelled-after-posting; `finalized`, `bank_date`; `Bank`, `Bank_acc` (paying ledger code); `doller_amt`. |
| `payment_request` | Supplier payment requests; status 0 hold / 1 requested / 2 completed / 3 cancelled / 4 approved / 5 rejected / 6 approved bulk; `voucher_created`. |
| `invoice_no` | Single-row counters: `acc_id` (ledger entry), `V_Num` (voucher), `debtor_receipt_no`, `Invoice_2` (debtor invoice), `No` (advances), `JE_inv`… |
| `debtor_invoice_log`, `other_invoice_log` | Tour invoices to agents (AH-nn-mm-yy; USD `dollar_amount`, `doller_rate`, LKR `credit_balance`) and JPY "other" invoices. |
| `debtor_receipts`, `other_invoice_payment` | Customer/agent receipts (receipt no, customer, date, LKR `bank_amount`, USD `dollar`, `dollarrate`, invoice `slip_no`, tour) and JPY receipts. |
| `customers` (agents, `Sup_Acc_No` = `1-2-5-*`), `supplier` (suppliers incl. supplier bank fields) | Masters. |
| `daily_usd_rate` | Daily USD rate (unique per date; `created_user`, `created_at`); set by `master_files/daily_usd_rate` (menu 257), the login prompt for group 25, and the payment desk's "Today's rate". |
| `tour_quotations.hotel_dollar_rate`, `total_hotel_doller`, `pay_doller_count` | Rate locked to a hotel night at request time. |
| `chk_runing`, view `chk_filter` | Cheque register tables — present, empty, unused by code. |
| `banks`, `branches` | Bank/branch directory for supplier bank details only (empty in snapshot). |
| `a_log`, `user_sessions` | Legacy activity log (menu-level) and login registry. |
| `user_acc_tbl`, `group_profile`, `menu_category`, `menu_list`, `user_privileges`, view `user_priv_q` | Users, groups, privileges (granted per group by menu URL). |
| tf-paydesk (test copy only, not on live yet): `tpd_bank_account`, `tpd_supplier_bank`, `tpd_request_ext`, `tpd_batch`, `tpd_bank_initiation`, `tpd_bank_approval`, `tpd_remittance`, `tpd_document`, `tpd_expense`, `tpd_audit`, `tpd_meta` | Avenir bank master, verified supplier banks, request figures (locked/approval/final rates, FX and charge shares), batches, initiation, Paid lock (incl. `ledger_bank_credit`, `reconciled`), remittance, documents, audit, settings. |

## 3. How customer money received is recorded today

* **Debtor receipt** (`accounts/debtor_payment`, `DebtorPaymentController::complete_payment`, menu 78): the user picks the agent, the LKR bank (1-6-8 names containing "LKR"), optionally a USD bank, the receipt date, a reference, and the invoices paid; per invoice a USD amount and a rate are typed (LKR = USD × rate). Writes: one `debtor_receipts` row per invoice (receipt no from `invoice_no.debtor_receipt_no`); per invoice a ledger pair Dr `1-2-5-10779` Debtor Control / Cr agent (`Invoice_Num` = invoice, `dollar_cr`, `dollarrate_cr`, `Recpt_No` = the typed ref — an int column; **dated the invoice's date, not the receipt date**); an FX gain/loss pair against `8-35-111-10780` for the LKR remainder; then ONE bank leg for the whole receipt: Dr LKR bank / Cr `1-2-5-10779`, dated the receipt date, `Invoice_Num 'DP-n'`, no USD fields; if a USD bank was chosen, one single-sided Dr row in `account_runing_usd` on the USD bank (USD = `dollar_cr`, rate of the last invoice line only, no balancing credit). The bank row and the invoice rows share no key — only the typed reference text.
* **Advance receipts** before invoicing: `accounts/advance_payment/add` (Dr bank / Cr `6-11-107-9663`, LKR only) and `tour/tour_details/payment_details` (Dr bank / Cr `6-11-107-10381`) — two different advance accounts; no routed code applies an advance to a later invoice.
* **JPY "other invoice" receipts**: `accounts/service_provider_payment/complete_payment` (Dr bank / Cr `1-2-5-10635` LionTours hard-coded; `other_invoice_payment`).
* **USD → LKR conversion**: `accounts/usd_withdrawal/process` — Dr LKR bank / Cr USD bank in LKR (= USD × rate, `dollar`/`dollarrate` set) plus a USD Cr row in `account_runing_usd`.
* Receipt report: `accounts/debtor_payment/report` (from `debtor_receipts`; date filters commented out; LKR only).
* There is **no customer/agent refund flow** (only driver fund returns) and no receipt allocation table — settlement is derived from `account_runing` sums per `Invoice_Num` (views `deptors_qq`, `deptors_qq_dollar`: LKR settled when ΣDr−ΣCr = 0, USD settled when Σdollar − Σdollar_cr = 0).

## 4. How supplier payments reach Bank Payment Check (the flow we built)

Payment Request (`tour/tour_accounts/payment_request`; `payment_request` status 1; `tpd_request_ext` locks the rate; hotel accrual Dr `4-8-106-10386` / Cr supplier posted at request) → Payment Approval (desk `approve()` → `update_payment_request_status` status 4/6; `tpd_request_ext.approval_rate/approval_lkr` written once; lunch accrual posted) → **Supplier Payables** (approved items wait: `payment_request IN (4,6) AND voucher_created=0 AND batch_id IS NULL`) → **Batch** (`batch_create`: one supplier, named lock, counter check, `create_bulk_voucher` batch mode → `voucher` row with `Vnum` = official Payment Voucher No., `v_inv` one line per request, `tpd_batch` SB-nnnnn, `voucher_created=1`) → **Bank initiation** (accountant: paying account confirmed, payment-date rate, `tpd_bank_initiation`, voucher re-rated while `Aflag=0`) → **Paid** (bank approver on the batch page: account confirmed, final rate, `VoucherPaymentController::update` posts Dr supplier / Cr paying bank per line with `Recpt_No` = Vnum, `aflag=3`; voucher `Aflag=1`, `finalized=1`, `bank_date`; `tpd_bank_approval` lock; FX pairs `advances_pay 23`; bank-charge pairs `advances_pay 24`; `tpd_request_ext.paid_*`; reconciliation of ledger credits = bank debit; remittance). Bank Payment Check lists the batch voucher with a link to its batch page and refuses Mark Finalized for it; legacy (non-batch) vouchers still go Aflag 0 → `accounts/voucher_auth/update` → Mark Finalized.

What this means for reconciliation: every supplier payment made through the flow leaves, on the paying bank's ledger account, credit rows carrying `Recpt_No` = Payment Voucher No. (payment) plus charge rows tagged `advances_pay 24`, with the bank's own reference (`tpd_bank_approval.bank_ref`), the actual payment date (`paid_date` = `voucher.bank_date`) and the total the bank debited (`total_debit`). Those are exactly the keys a statement line can be matched on.

Other writers of bank-account ledger rows outside the flow (must be considered by matching, not disturbed): debtor receipts (Dr bank, `Invoice_Num 'DP-n'`), advance receipts, USD withdrawal, creditor payment (`accounts/creditor_payment`, Dr supplier / Cr bank, no voucher), driver fund returns (Dr bank), journal entries, year-end/brought-forward rows, legacy vouchers authorised with cash/petty-cash/cheque types.

## 5. How USD and LKR are stored and handled

* The ledger is **LKR only** (`Dr`/`Cr`). Foreign currency is a memo per row: `dollar`/`dollarrate` (invoice/debit side) and `dollar_cr`/`dollarrate_cr` (receipt/credit side). It is not reliable as USD: many rows carry the LKR amount with rate 1 (e.g. entry 81).
* USD bank balances are not derivable from `account_runing`; the only USD-denominated record is the single-sided shadow table `account_runing_usd`, written by two screens (debtor receipt, USD withdrawal) and never reconciled.
* Rates: `daily_usd_rate` (one per day; `get_usd_rate($date)` helper needs the exact day — no fallback; TourFlow falls back to the latest ≤ date); hotel rate locked on `tour_quotations.hotel_dollar_rate`; the dashboard falls back to a hard-coded 125 when missing (`TourPaymentDashboardController:389,1321`). Debtor receipts and invoices use a rate typed by hand (invoice default 275), not `daily_usd_rate`.
* The tf-paydesk flow keeps three rates per supplier payment (locked at request, shown at approval, payment-date final), records the realised difference per tour line, and posts it to mapped gain/loss accounts; `tpd_bank_account.currency` says whether an Avenir account is LKR or USD (the flow pays LKR vouchers only from LKR accounts).

## 6. Audit logging that already exists

* `a_log` — one row per allowed request (user, matched menu URL and name, date, time, IP) written by `UserPermissionFilter`; **not** written for group 1 (administrators), alias-matched routes or filter-exempt routes. Viewable at `reports/employee_activity` (group 1) and a dashboard widget.
* `user_sessions` — login/session registry (token, IP, user agent, revoked).
* Ledger rows: `Cashier` + `Dt` (who/when posted); `date_changed`/`date_changed_user` only on journal date edits (old value not kept); `repostlogin`/`repostdate` never written; `account_runing_del` never written. Hard deletes with no trace exist (hotel-request reject deletes the accrual; driver expense delete; utility bill delete; reservation cancel).
* Vouchers: `login`/`C_Date` (created), `Aut_user`/`aut_date` (authorised), `rej_user`/`remark`, `is_confirm`/`confirm_user`/`confirm_date` (voucher audit), `finalized_user`/`finalized_date`/`bank_date`. Cancel-after-posting inserts reversal rows but does not record who cancelled.
* `payment_request`: `payment_request_user/date`, `accept_user/date`, `complete_user/date`, `payment_cancel_user/date` — overwritten, no history.
* Domain logs: `hr2_audit` (hotel rates, old/new JSON), `tf_activity` / `tf_tour_event` (TourFlow), `quotation_changes_audit_log`, `driver_allocation_audit`, `changes_log`, `inv_alert_log` (mostly empty in the snapshot).
* tf-paydesk (test copy): `tpd_audit` — append-only, millisecond time, user, tour, action, ref, voucher no, previous/new JSON, reason, rate, bank account, bank ref, IP; 30+ action kinds; shown on the dashboard, batch page and voucher document.
* `writable/logs/log-*.log` — ~300 `log_message('critical', getLastQuery())` calls dump SQL (server only, no user).

## 7. Existing components that can safely be reused

* **The ledger as the book side**: bank ledger rows per account from `account_runing` (and the `bank_statement` view), with the opening-balance query already used by Bank Statement/reconciliation (`SUM(Dr)-SUM(Cr) WHERE L_Code=? AND Date<?`).
* **The two legacy flags** `bank_rec` / `bankst_flag` as the "cleared / locked" markers — reading them is safe; the new module can set them in the same sense so the old screen and reports stay consistent (see risk on ID-wide flagging).
* **`tpd_bank_account`** as the bank master with currency (once the kit is live) — the natural list of "accounts to reconcile", mapped to ledger codes. Until then, the 1-6-8 list from `account_posting`.
* **`tpd_bank_approval` / `tpd_bank_initiation`** (bank reference, paid date, total debit, charges) and `voucher.Vnum` / `Recpt_No` on ledger rows for matching supplier payments.
* **`debtor_receipts`** (+ the 'DP-n' bank rows) for matching receipts; `daily_usd_rate` for conversion at the statement date; `Perms` / `menu_list` / `user_privileges` for roles; `tpd_audit` (`Audit::log`) for the trail; `Schema::ensure` style additive setup; the existing Bank Payment Check and Supplier Payables pages unchanged.
* `UsdWithdrawalController` postings as the existing form of an LKR↔USD transfer.

## 8. New components a Daily Bank Reconciliation would need (not built)

1. **Statement store** — `brs_statement` (one per account per period/import: bank account id, currency, statement date range, opening/closing balance as printed by the bank, file, imported by/at, status) and `brs_statement_line` (date, value date, description, reference, debit, credit, running balance, currency amount, hash for de-duplication, match status).
2. **Import** — CSV/XLSX/OFX parsers per bank format (Commercial, Sampath…; to be sampled from real exports), with a preview and duplicate detection (never importing the same line twice).
3. **Matching engine** — rules in order: exact bank reference ↔ `tpd_bank_approval.bank_ref` / `debtor_receipts` ref; amount + date window ↔ bank ledger rows (`Recpt_No`, `Invoice_Num 'DP-n'`, charge rows); many-to-one (one debit = payment + charges; one credit = several receipts); suggested matches with a confidence score, confirmed by a user. Table `brs_match` (statement line ↔ ledger entry/leg, type, confidence, matched by/at, unmatched reason).
4. **Unmatched queue** — statement lines with no book entry (bank charges, interest, unexplained receipts) and book entries with no statement line (uncleared); actions: "create book entry" via the existing posting paths (journal / receipt / charges), never a silent posting.
5. **Daily reconciliation record** — `brs_day` per account per day: book balance (ledger), statement balance, cleared/uncleared totals, difference, status (open / reconciled / locked), reconciled by/at; locking marks `bank_rec=1` / `bankst_flag=1` on the matched ledger rows in the legacy sense.
6. **Currency layer** — balances kept **per account in the account's own currency** (statement currency), converted for display at `daily_usd_rate` of the day; a true USD bank sub-ledger (double-sided) is required to show "USD actually held" from the books — today only the statement can give it.
7. **Management dashboard** — per currency: statement balance, book balance, uncleared items, unmatched receipts/payments, differences, ageing of unmatched items; drill-down to lines.
8. **Privileges** — new menu URLs (`accounts/bank_reconciliation`, `…/import`, `…/match`, `…/lock`, `…/dashboard`), granted per group as today; segregation suggested: importer/matcher vs. approver who locks a day.
9. **Audit** — every import, match, unmatch, lock, unlock in `tpd_audit` (or a sibling `brs_audit`) with previous/new values.

## 9. Conflicts and risks

* **The legacy reconciliation screen flags by entry `ID`**, which marks both legs of an entry; a new module must flag the **bank leg only** (by `ID` + `L_Code`) and must not reuse the old `update`/`reconciliation` endpoints as they stand. If both screens are used on the same account the flags will disagree.
* **No statement history today** — the typed statement balance is not saved; `account_runing_bank_statetment` and `chk_runing` are empty leftovers. Nothing to migrate, but also nothing to validate against.
* **Hard-coded and missing accounts**: `1-6-8-9352`, `1-6-8-9750`, `1-6-112-10782`, `1-2-5-10779`, `8-35-111-10780` are referenced by code but absent from the 2024 snapshot's chart; rows posted to a code not in `account_posting` vanish from every view built on `account_runing_qq` (INNER JOIN). The real production chart must be read before any matching rule is designed (read-only check once we have access).
* **USD banks cannot be reconciled from the books**: `account_runing` holds LKR; `account_runing_usd` is single-sided and partial. For USD accounts the statement will have to be the primary balance, with the book side rebuilt from the LKR rows' `dollar*` memo fields (unreliable) or a new USD sub-ledger — a design decision for Step 2.
* **Receipt dating**: debtor-receipt invoice rows are dated the invoice date while the bank row is dated the receipt date; matching must use the bank leg only.
* **Bank-to-bank entries** (USD withdrawal) have both legs on bank accounts — matching must handle one entry appearing on two statements.
* **Concurrency / counters**: `incrementAccId` is read-then-write with no lock (as is the voucher counter — now guarded by the kit's named lock); an import that posts many entries must serialise around it.
* **MyISAM, no transactions**: imports and lock operations must be idempotent and ordered (validate everything, then write), as done in the kit.
* **Permissions are URL-prefix based**: holding menu 49 `accounts` opens every `accounts/*` route — a new module under `accounts/…` would be open to every Accountant automatically; a dedicated category/URL prefix (e.g. `bank/…`) with its own menu rows avoids that.
* **Must-not-touch list**: `voucher.Vnum`, `Aflag`, `finalized`, `bank_date`; `payment_request` statuses; `tpd_*` rows; `VoucherPaymentController::update`; Bank Payment Check; Supplier Payables; the FX/charge postings. The new module reads these and only ever adds its own rows and the two cleared flags.
* Live installation of tf-paydesk-r4 is still pending your approval; the reconciliation design should assume it is there (bank master with currency, `tpd_bank_approval`), but must also work for legacy vouchers and receipts that predate it.

## 10. Recommended architecture (for discussion — not built)

A separate, additive module `App\Libraries\BankRecon` + `App\Controllers\Accounts\BankReconciliationController` (own URL prefix and menu category "Bank"), with its own tables prefixed `brs_` created by an additive `Schema::ensure`-style setup, no change to any existing table except setting `account_runing.bank_rec`/`bankst_flag` on the matched bank leg when a day is locked. Reads: `account_runing` (bank legs by `L_Code`), `tpd_bank_account` (accounts + currency; fallback 1-6-8 list), `tpd_bank_approval`/`voucher` (supplier payments), `debtor_receipts` (receipts), `daily_usd_rate`. Writes: `brs_statement`, `brs_statement_line`, `brs_match`, `brs_day`, `tpd_audit`. Never posts to the ledger by itself; "create the missing entry" links to the existing posting screens (journal entry, debtor receipt, the desk's charges) and then re-matches. Installed with the same apply/rollback kit pattern (file list + TESTED_ON checksums + additive schema + privileges), tested on the cloud copy first.

**Can it support Bank Statement Import → Automatic/Suggested Matching → Unmatched Transactions → USD/LKR Conversion → Daily Reconciliation → Management Bank Dashboard?** Yes, with the components in section 8: the statement store and importer (1–2), the rule-based matcher with confirmation (3), the unmatched queue (4), the per-account-currency balance layer with `daily_usd_rate` conversion (6), the daily record and lock (5) and the dashboard (7). The **real bank statement is the source of truth for cash held**: statement closing balance per account in its own currency → "total LKR actually held in LKR accounts" and "total USD actually held in USD accounts" come straight from the statements; the book side gives the uncleared/unmatched items and the differences. The one structural gap is the USD book side (no double-sided USD ledger) — the dashboard can show USD held from statements from day one; USD book balances need the Step 2 decision above.

## What I could not check (read-only, pending access)

The live production database (chart of accounts as it is today, whether the legacy reconciliation screen or flags have been used on live, the real bank account list and names, USD accounts, counters). These are read-only SELECTs; they can be run the same way as the voucher-number preflight once the link to your computer/database is available.

Stopped here. Waiting for your approval before Step 2.
