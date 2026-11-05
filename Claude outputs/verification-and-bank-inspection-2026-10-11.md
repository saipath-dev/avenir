# Operations, confidentiality, verification and Step 2 bank inspection — 11 Oct 2026

All of this work was done on the development system only (`C:\wamp64\www\avenir`). Nothing was installed on the live system, and no hotel rate, accounting entry or reconciliation flag was changed. The dashboard layout is unchanged.

## 1. Post-R5 database backup: done and restore-verified

- **The backup.** `avenir_backup_20261011_post_r5`: a full copy of `avenir` in its R5 state (schema 7, pilot-r1/r2/r3 installed). It is labelled post-R5 and is not a pre-installation backup.
- **How it was made.**
  - A server-side copy through phpMyAdmin, in the development system's MySQL 8.4.7.
  - phpMyAdmin's own "Copy database" stops at the view `account_runing_q2`, because its definer `dbmasteruser@%` does not exist on this server. So I copied by SQL: every table's structure, its data, AUTO_INCREMENT counters, foreign keys, views (with their original definers) and triggers.
- **Timing.** The copy was made in two sittings, because your computer slept in between. It was checked complete against `avenir` at 17:15 on 11 Oct 2026.
- **Existing backups.** `avenir_backup_20261011`, the before-R4/R5 file backups and the kit backups are untouched.
- **Restore check.** The backup was restored into a separate, isolated database, `avenir_restore_verify_20261011`, and the three databases were compared:

| Checked | avenir | backup | restored copy |
|---|---:|---:|---:|
| Tables | 228 | 228 | 228 |
| Rows, counted table by table | 373,092 | 373,092 | 373,092 |
| Table checksums | — | all 228 equal the restored copy | all 228 equal the backup |
| Columns / indexes | 4,416 / 641 | identical | identical |
| Foreign keys / views / triggers | 57 / 13 / 4 | identical | identical |
| AUTO_INCREMENT counters | 205 | identical | identical |
| Ledger (account_runing): rows / ΣDr / ΣCr | 11,364 / 965,763,907.13 / 965,314,763.93 | same | same |
| Vouchers / highest PV / payment requests / audit rows | 7,975 / 8020 / 3,110 / 245 | same | same |

- **Backup against `avenir`.** 226 of the 228 tables have identical checksums. The two that differ, `ci_sessions` and `user_sessions`, have the same rows. Only the "last activity" times of signed-in sessions moved on after the copy.
- **Record.** The backup record is in `post-r5-database-backup-record-2026-10-11.md`.
- **For you to decide.** The isolated restore copy can be kept or removed (164.7 MB). I left it in place.

## 2. Operations faults: fixed and verified (tf-pilot-r2)

- **Driver Allocation.** Opened from its menu without a tour, the page now goes to the existing "Tours pending driver allocation" list. With a tour selected it opens without the error.
- **Crew replacement.** The menu entry now opens the Transport Desk at its existing crew replacement work, so no second workflow was added.

Tested on the development system:

| Login | Allocation page | Engage crew, vehicle, days worked, replace | Crew replacement entry |
|---|---|---|---|
| TestOperations | opens (200) | allowed past the privilege check (they returned "not found" only because I used dummy record numbers, so nothing was written) | opens the desk |
| Administrator | opens | allowed | opens the desk |
| Tour Res, Hotel Res, Transport, Fin. Approver, Bank Auth., Accounts | refused | refused (403) | refused |

Every refusal was recorded in the audit trail.

## 3. Hotel Reservations confidentiality: done (tf-pilot-r2 and tf-pilot-r3)

**What was done:**

- I requested every GET route in the application (1,434 requests) as each department login, using the menus each department actually holds on the development system.
- That scan found more routes that still showed full supplier bank numbers. Tf-pilot-r3 masks them all:
  - the Hotel Rate master's supplier lookup and hotel search;
  - the Driver form;
  - three hotel lookups used while quoting;
  - the payment-requested report;
  - Bank Payment Check, including the finalized records.
- The audit trail now keeps only the last four digits of an attempted bank change.
- After the fixes the scan was repeated. No department login received a full supplier number on any route, and no route was missed.

**Result for Hotel Reservations:**

- Supplier contacts stay visible and editable.
- Bank numbers are shown as the last four digits, for example ••••6350.
- The bank fields are locked, and the server ignores any bank change it sends.

**Supplier master access:**

1. While the protection was unverified, the Supplier master was refused to Hotel Reservations.
2. Once it was verified, I gave its two menus (Supplier and Supplier Report) back.
3. I checked again afterwards: the list, the edit form and the lookup all open, with no full number anywhere on them.

**Finance access is unchanged.** Accounts, the Bank Authoriser, the verifier and the administrator still see full numbers.

**One correction to my own r2 work.** R2 had also masked and locked bank details for the older staff groups: Operational (9 users), Maintenance, the legacy Auditor and Sales. That was an access change you had not approved. R3 gives those groups back exactly what they had before.

**Testing:**

- Cloud test copy: R4 202/202, R5 bulk 71/71, Pilot 36/36, Operations/confidentiality 33/33, r3 26/26.
- Kit: rehearsed with check, apply and rollback, then installed on the development system. All 10 files were checked against the kit after install.

## 4. Recheck of the seven logins

All seven checks below passed:

- **Landing page.** All seven test logins land on the Pilot page.
- **Shortcuts.** Each login sees only the shortcuts for its own work. Operations sees Driver Allocation and Crew replacement; Transport sees Driver advance but not allocation.
- **Request.** Only Tour Reservations, Hotel Reservations and Transport & Guide Handling can make or cancel payment requests.
- **Approve and create batches.** Only the Management Approver.
- **Initiate a bank payment.** Only Accounts.
- **Mark Paid.** Only the Bank Authoriser.
- **Audit records.**
  - Every refusal of a cross-role action was recorded: 80 new entries, matching the expected count for each login exactly.
  - Each entry records who, what, when and the IP address.
  - No recorded value contains a full account number.

## 5. Unresolved issues

1. **A GET route can flag ledger rows.** `tour/tour_details_search/advance_delete?id=` sets `aflag = 1` on any ledger row. Any profile holding Tour Search can reach it, and none of the current screens uses it. This existed before my work. I recommend restricting it to Accounts, but this needs your approval.
2. **The `driver_allocation` triggers fail on the development server.** All three triggers (after insert, update and delete) run as `dbmasteruser@%`, a user that does not exist there. Any write to `driver_allocation` on the development system should therefore fail with error #1449. The same definer cause affects some views (below). On the live server this user probably exists. Recreating these objects with an existing definer on the development system needs your approval.
3. **The ledger is not balanced.** In `account_runing`, ΣDr − ΣCr = LKR 449,143.20 across 11,364 rows, so some entries have no matching opposite leg. I found this while checking the backup. It is not changed; it belongs with the Step 2 book issues.
4. **Avenir's own paying account number** is shown in full on the batch and voucher pages to the Management Approver and the Auditor. The supplier numbers on those pages are masked. Decide whether Avenir's number should be masked too.
5. **Older staff groups** (group id 28 or lower) keep full supplier bank numbers as before. This should be reviewed when staff move to the department profiles.
6. **Tour Dashboard link to invoices.** The Tour Dashboard route alias opens the debtor-invoice amendment screen to every holder of the Tour Dashboard. This existed before my work and needs a decision.
7. **Still open from earlier rounds:**
   - The legacy payment request says "saved" even when nothing was saved.
   - The collation mismatch between the new `tpd_` tables and the legacy tables.
   - The Bank Payment Check console error.
   - The legacy voucher-cancel HTTP 500.
   - `tour/print` is open to every signed-in user.
   - `LEGACY_ALLOCATION_GROUPS` should be retired later.
   - Views owned by `dbmasteruser@%` (`acc_selct`, `account_runing_q2`, `account_runing_q21`) cannot be read on the development server.
8. **Restore copy.** Keep or remove `avenir_restore_verify_20261011`; it is your decision.

## 6. Step 2: Daily Bank Reconciliation inspection (read-only)

**Sources:**

- The Commercial Bank LKR statement for the account ending 0620, 1–30 Sep 2026: 24 pages, 521 transactions, read directly from the PDF.
- Your 6 and 9 Oct screenshots.
- The development system's book.

**The statement is internally consistent.**

| | LKR |
|---|---:|
| Opening balance, 1 Sep | 2,749,797.37 Cr |
| Receipts (15 items) | 36,448,025.00 |
| Payments (506 items) | 34,525,365.29 |
| Closing balance, 30 Sep | 4,672,457.08 Cr |

All 521 running balances recompute without a break, and the totals agree with the bank's printed totals.

**Payments:**

- Tour payments: 249 lines, LKR 30.21 m.
- Other payments: 36 lines, LKR 4.29 m (tickets, salaries, rent, utilities, director's current account).
- Bank charges: LKR 23,200 in total:
  - 208 CEFT fees at LKR 25;
  - 11 fund-transfer fees at LKR 1,000;
  - 2 GPRS rentals.

**Receipts:**

- 11 transfers from account 5000028818, LKR 36,140,000. These are probably USD 10,000 each: the 1 Sep fee lines read "USD 10,000", which gives implied rates of 327.25 to 330.50.
- One cash deposit of LKR 235,000.
- "Other bank payment returned", LKR 63,000 + 25. Three payments of LKR 63,000 went out on 21 Sep; one of them came back.

**Overdraft.** On 1 Sep payments went out before that day's two transfers arrived. The balance stood at −2,152,856.88 during the day and closed at +4,118,043.12. No overdraft charge appears in the statement.

**Limit of the book.** The development copy of the book ends on 7 Sep, so I could only compare 1–7 Sep.

**Payments 1–7 Sep (165 bank lines) agree with the book to the rupee:**

- 83 lines are matched to vouchers, by PV number, tour numbers and amount. Some single transfers paid several vouchers at once.
- The book credits LKR 1,218,305 too much: voucher 7919 is in the ledger twice.
- Voucher 8010 (LKR 139,750) was paid at the bank but was still pending in the copy.
- Bank charges of LKR 6,725 are not in the book.
- Eight payments have no voucher in the book, LKR 794,410 in total. They include Sigiriya tickets 298,375, an IT consultation fee 100,000 and "to be reimbursed" 200,000.
- Lucky Jeep for AH 2082/2094/2140: the bank paid 52,000 against book vouchers of 84,000.

**Receipts 1–7 Sep.** The bank received five transfers, LKR 16,367,500. The book has no September receipts on this account.

**Book problems, documented and not changed:**

- Vouchers credited twice: LKR 1,362,355 (7919, 7675, 7696, 7705, 7730).
- Non-supplier vouchers paid from Com Bank are booked to Bank Control 1-6-8-9750, which stood at −6.83 m.
- USD→LKR transfers are not booked.
- Bank charges are not booked.
- The Com Bank LKR book balance is −31.7 m against +2.7 m at the bank on 1 Sep, a difference of 34.5 m.
- The USD account is held in LKR only and is negative.

**Gaps:**

- No book data after 7 Sep: the development copy is older than the live system.
- No USD account statement (5000028818).
- No Pan Asia statements.
- October is only available as screenshots: a balance of 4,355,683.17 and lines from 6 and 9 Oct.

**Proposed next step, for your review. Nothing is built until you approve:**

1. Send the September statement for the USD account (5000028818) and the Pan Asia statements. For October, send the daily CSV/XLSX exports from web banking.
2. Provide a fresh copy of the live database, or approve read-only access to the live ledger, so 8–30 Sep and October can be compared.
3. Agree the starting point. I suggest 1 Sep 2026, using the bank's opening balance. The historical book differences would be kept on an opening reconciliation schedule and not posted.
4. Then design the reconciliation on the test copy:
   - a statement import (the Commercial Bank PDF reader used here is already proven on September);
   - matching by PV number, tour numbers and amount, including several vouchers in one transfer, with rounding flagged;
   - an unmatched-items queue;
   - a daily record per account in its own currency.

   It would make no ledger postings. A line is shown as reconciled only when it matches a statement line.
5. Separately decide how the book problems above should be corrected. Correction entries would be prepared for your approval, never posted automatically.

Details are in `combank-lkr-sep2026-inspection.xlsx`: a summary, all 523 statement lines with categories and matches, the 1–7 Sep unmatched items, the transfers with implied rates and the charges, and the book issues.
