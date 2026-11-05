# Daily Bank Reconciliation: review guide and pending decisions (12 Oct 2026)

**What this document is for**
- It covers your review of Step 2 and a dummy-data demonstration only.
- Nothing here changes the live system. No accounting entry is posted, and no real bank statement is imported.
- This work is kept separate from the Agent Inbox, Staff Performance and System Behaviour project.

**Checked again on 12 Oct before writing:**
- The installed files on both copies still match the kit exactly.
- On development, the reconciliation tables are still empty, and the ledger is identical to before the install.
- The demonstration data on the isolated test instance is intact.

---

## 1. What is completed, where it is installed, and what is unfinished

### Completed

| Item | State |
|---|---|
| **Daily Bank Reconciliation module** (kit `tf-bankrec-r1`) | Built and tested. **What it covers:** <ul><li>accounts with a ledger mapping that a second person confirms;</li><li>LKR, USD and JPY, each with its LKR equivalent;</li><li>statement upload (CSV) or typed entry, which is checked, kept as a draft, accepted and never posted;</li><li>bank and book balances kept separate;</li><li>matching with suggestions;</li><li>timing, duplicate-looking and excluded items, and bank charges;</li><li>USD→LKR transfers with the day's rate;</li><li>proposed corrections for review, never posted;</li><li>opening items;</li><li>daily records, prepared by one person and reviewed by another;</li><li>a full audit trail.</li></ul> |
| **Scope correction** | The Step 2 note and the workbook label LKR 34,495,229.47 as **Unreconciled — insufficient data**. |
| **Independent export** of the verified post-R5 recovery backup | Made and restore-tested. It is at `C:\wamp64\tmp\avenir-exports-20261011\`. |
| **Backup roles** | Recorded: pre-R5 backups untouched; post-R5 = the recovery backup; restore copy = test bed, not a recovery source. |
| **dbmasteruser rollback script** | Not run. It is now a guarded emergency record with a recovery procedure. |

### Where it is installed

| Place | Address | Code | Database | Data in it |
|---|---|---|---|---|
| **Development system** | `http://localhost/avenir/accounts/daily_bank_rec` (Accounts → Daily Bank Reconciliation) | 12 kit files; Routes.php changed | `avenir`: 11 new tables (empty), 3 menu entries, 9 privilege rows | **None.** The bank-accounts master is empty and there is no dummy data. |
| **Isolated test instance** on your computer (red "ISOLATED TEST COPY" bar) | `http://localhost/avenir_rv/accounts/daily_bank_rec` | Same 12 files | `avenir_restore_verify_20261011` (test bed) | **Dummy demonstration data** (section 2) |
| **Cloud test copy** (my workspace) | — | Same code | `avenir_test` | Dummy data for the automated tests |
| **Live system** | — | **Not installed** | — | — |

### Unfinished, by design and waiting for your decisions

- **Real accounts.** No real Avenir bank account has been set up. I do not enter or assume account numbers.
- **Real data.** No real statement has been imported, and there is no fresh copy of the live data.
- **Backup off the computer.** The export has not yet been copied off this computer (decision D1).
- **Two improvements found in this review** (D9, D10), not yet made.
- **Clean-up** of the test copies and the withdrawn helper folder (D8).
- **Live installation** (D12).

---

## 2. Reviewing the dummy-data demonstration: exact pages and steps

The demonstration runs on the **isolated test instance**, not on the development database. The development database is kept free of dummy data (decision D11).

- **Signing in:** use the department **test logins** with the test password you set for them.
- **Separate session:** signing in at `/avenir_rv/` is separate from your development session.
- **Switching role:** open `http://localhost/avenir_rv/logout`, then sign in as the other test login.

| Test login | Role in the reconciliation |
|---|---|
| **TestAccounts** | Preparer: imports, matches, classifies, proposes, prepares the daily record |
| **TestFinApprover** | Reviewer: confirms mappings, approves or rejects corrections, reviews daily records |
| **TestBankAuth** | View only |

### A. Look at what is already there (read only), as any of the three logins

1. **Overview.** Open `http://localhost/avenir_rv/accounts/daily_bank_rec`. You will see three accounts, TEST LKR, TEST USD and TEST JPY. Each shows a confirmed mapping, first day 01/09/2026, statements to 05/09/2026, and its status.
2. **Summary.** Click **Open** on **TEST LKR account (dummy)**. The **Summary** tab opens at *As at 05/09/2026*. You will see:
   - the Bank card, the Book card, the Items count, and *Reconciliation of bank and book*;
   - status **Reconciled — reviewed**.
3. **Statements tab.** Click **View** on statement 1 to see:
   - the 11 lines with category and state;
   - the line marked *edited*, whose original is kept;
   - the checks.
4. **Matching tab.** Click **Show**. You will see:
   - open statement lines;
   - open ledger rows;
   - **Matched** (6);
   - **Classified** (one timing item and one duplicate-looking item).
5. **Bank charges tab.** It lists four charges (CEFT ×2, fund-transfer fee, GPRS rental), each covered by a correction.
6. **Corrections & opening tab.** It shows two approved corrections: bank charges 4,550.00 and the cash deposit 235,000.00. Neither is posted.
7. **Daily records tab.** Record #1, prepared by TestAccounts and reviewed by TestFinApprover.
8. **Audit trail tab.** Every action, with user, time, values before and after, and reason.
9. **USD account.** Go back to the overview and open **TEST USD account (dummy)**. As at 05/09/2026 it shows USD and LKR side by side and the revaluation.
10. **Rates & transfers.** Click **Rates & transfers** (top right), set **From 01/09/2026** and **To 05/09/2026**, and click **Show**. You will see the daily rates and the USD → LKR transfer.

### B. Hands-on: import, match and explain a new dummy statement (6–8 Sep)

The file to use is `Claude outputs\bankrec-demo\DUMMY-statement-TEST-LKR-06-to-08-Sep-2026.csv`. It holds four dummy lines that continue from the 5 Sep closing balance. I rehearsed every step below on the cloud copy and got exactly the results shown.

**Sign in as TestAccounts.**

1. **Import.** On TEST LKR, open the **Statements** tab and choose **Upload CSV**.
   - Choose the file and type **Opening balance** `3,893,106.00 CR`.
   - Click **Check**. You should see no check errors; categories: payment 2, charge 1, receipt 1; closing 3,893,081.00.
   - Nothing is kept yet. Click **Keep as draft**, then **View** the new statement and click **Accept**.
2. **Suggestions.** Open the **Matching** tab and set **From 01/09/2026** and **To 08/09/2026**. Click **Show**, then **Suggest matches**.
   - **Nothing is suggested yet.** The restaurant payment PV 90004 was classified earlier as a timing item, and classified items are left out (limitation, D9).
3. **Clear the timing item.** In the **Classified** table, click **Clear** on *ledger 99980009.1 (timing)* and give the reason "paid on 6 Sep".
   - Click **Suggest matches** again. The 6 Sep payment *AH 9904 – TEST RESTAURANT E – 90004* is now suggested with **high** confidence.
   - Click **Accept**.
4. **Summary.** Open the **Summary** tab, set **As at 08/09/2026** and click **Refresh**. Expected:

   | | LKR |
   |---|---:|
   | Bank closing | 3,893,081.00 |
   | Book closing | 3,547,655.00 |
   | Difference (bank − book) | 345,426.00 |
   | Statement items not in the ledger | 270,425.00 |
   | Ledger items not on the statement | 75,000.00 (the duplicate-looking row) |
   | Rounding inside a match | 1.00 |
   | **Unexplained remainder** | **0.00** |

   The status is **Unreconciled — items to explain (3)**:
   - the 25.00 charge;
   - the 100,000.00 receipt;
   - the 60,000.00 payment.
5. **Explain the three items:**
   - **The charge:** on the **Bank charges** tab, tick it and choose *Propose a correction for the selected charges*.
   - **The receipt:** on **Corrections & opening**, choose *Propose a correction*, kind *Receipt on the statement, not in the ledger*. Use the dummy ledger accounts: debit 1‑6‑8‑29901 and credit 2‑9‑99‑29909 (TEST Suspense), amount 100,000, and give the statement line number. Alternatively, on Matching, tick it and choose *Classify one item → timing*.
   - **The payment of 60,000:** on Matching, tick it and choose *Classify one item* (for example *timing*, with a note), or propose a correction.

   The status becomes **Reconciled — all items explained (awaiting review)**.
6. **Daily record.** On the Summary tab, choose **Prepare the daily record for this date**.

**Sign in as TestFinApprover.**

7. **Review.** The overview shows **Waiting for review**.
   - On **Corrections & opening**, choose **Approve** or **Reject**.
   - On **Daily records**, choose **Reviewed** or **Return** (with a reason).

**Things you can also try:**
- **Four eyes:** TestAccounts cannot approve, and TestFinApprover cannot import.
- **Same person:** the person who proposed something cannot approve it.
- **Double import:** importing the same file twice is flagged and refused at acceptance.
- **Undo:** **Undo** on any match keeps the record, and the day's status falls back.

Everything in the isolated instance is dummy. Nothing reaches the development database or the live system.

---

## 3. How it works

### Statement import

- **Two ways in:**
  - **Upload CSV.** Columns are recognised from their headings (date, value date, description, reference, debit, credit or one amount column, balance). You can change the column choice, then click **Check** again. Dates are read day-before-month unless you pick a format. "CR"/"DR" balances and amounts in brackets are understood.
  - **Type lines.** For a short statement or a screenshot.
- **Checks before anything is kept:**
  - every printed balance must equal opening + lines;
  - the closing balance must agree;
  - the opening must equal the previous accepted statement's closing;
  - gaps and overlaps with other statements are found;
  - duplicate-looking lines are flagged, within the file and against earlier imports;
  - a USD or JPY day without a rate is flagged.
- **Draft, then accepted:**
  - A **draft** can be corrected line by line, only with a reason. The imported original is kept and audited.
  - **Accept** locks the lines.
  - An accepted statement can only be **voided** by a reviewer, and only when nothing is matched on it. It is kept for the record.
- **Never posted:** nothing is ever written to the ledger.
- **Categories:** each line is categorised automatically (receipt, payment, bank charge, transfer, interest). The preparer can change the category, with a reason.

### Matching

- **Suggestions** (nothing is matched until accepted):
  - **One line to one ledger row:** same amount (for USD and JPY, within 1 % of the LKR value), dated up to 10 days before or 3 after.
  - **One line to several rows** of the same PV number when they add up, for example one bank transfer paying a voucher with several lines.
  - **Cheques written before the first day**, found through the cheque number.
  - **Scoring:** by PV number (including the bank's shortened "‑ 79…"), cheque number, tour number and date. Confidence high, medium or low; *Accept all high-confidence* is offered.
- **Manual match:** tick any lines and rows; the totals and the difference are shown. A difference needs a reason:
  - exchange rate (USD and JPY only, up to 5 %);
  - rounding (up to 1.00);
  - other (with a note).
- **Undo:** keeps the match as "undone" with who, when and why.
- **Changed ledger row:** if a matched ledger row changes in the ledger afterwards, the day shows *insufficient data* until it is re-matched.

### Items that do not match

Each day lists them separately:

| List | What it means | How it is explained |
|---|---|---|
| Statement lines not in the ledger | On the bank, not in the book | Matched later, classified, or covered by a proposed or approved correction |
| Ledger rows not on the statement | In the book, not (yet) at the bank | Classified (for example timing) or covered by a correction |
| **Timing** | Expected to clear later (expected date recorded) | Clears when matched (today: clear the classification first, see D9) |
| **Duplicate-looking** | Looks like another item (flag only; nothing is deleted) | Investigated; a reversal can be proposed as a correction |
| **Excluded** | Not part of the reconciliation, with a reason | — |
| **Bank charges** | Recognised from the wording; listed with type and totals | A correction proposed from the bank-charges ledger mapping |
| Matched to an item after this day | Its partner falls after the as-at date | Treated as timing for that day |

The day is **Reconciled — all items explained** only when every one of these items has an explanation **and** the unexplained remainder is 0.00.

### Opening and closing balances

They are kept separate, never netted:

| | Bank side (accepted statements) | Book side (ledger, read only) |
|---|---|---|
| Opening | The statement's opening balance on the **first day**. For USD and JPY, also its LKR value at that day's rate. | The ledger balance at the end of the day **before** the first day |
| Closing | The statement balance at the end of the chosen day (the printed balance where there is one). For USD and JPY, also its LKR value at that day's rate. | The ledger balance at the end of the chosen day |

**The reconciliation in LKR:** *bank closing − book closing* is broken down into:
- the opening difference still unexplained;
- accepted opening items;
- statement items not in the ledger;
- ledger items not on the statement;
- differences inside matches;
- for USD and JPY, the revaluation of the balance at the day's rate;
- and the **unexplained remainder**, which must be 0.00.

**The opening difference** on the first day stays **Unreconciled — insufficient data** until it is explained. It can be explained by:
- **opening items:** proposed by the preparer, accepted by the reviewer;
- **earlier ledger items** that clear on the statement later, such as the 30 Aug cheque matched to the 2 Sep bank line.

**Worked dummy example (LKR):**

| | 1 Sep (first day) | 5 Sep |
|---|---:|---:|
| Bank opening / closing | 1,000,000.00 / 949,975.00 | — / 3,893,106.00 |
| Book opening / closing | 850,000.00 / 800,000.00 | — / 3,547,655.00 |
| Difference | 149,975.00 | 345,451.00 |
| Opening difference not yet explained | 150,000.00 (the cheque clears on 2 Sep; see D10) | 0.00 |
| Status | Unreconciled — insufficient data | Reconciled — reviewed |

### USD → LKR transfers

1. **Pairing.** The system pairs money leaving one Avenir account with money arriving in another within 2 days, at a rate within 5 % of the day's rate. On **Rates & transfers**, nothing is recorded until you click **Record**.
2. **What the transfer record shows:**
   - both amounts in their own currencies;
   - the **rate obtained** (LKR received ÷ USD sent);
   - the **day's rate** and its source;
   - the difference in LKR.

   **Dummy example:** USD 10,000 out on 3 Sep, LKR 3,000,000 in. Rate obtained 300.00; the day's rate 301.00; difference −10,000.00 LKR.
3. **Each side is still matched to the ledger in its own account:**
   - **LKR account:** the bank credit 3,000,000 is matched to the ledger debit 3,000,000 ("USD TO LKR" journal).
   - **USD account:** the bank debit of USD 10,000 is worth LKR 3,010,000 at the day's rate. It is matched to the ledger credit 3,000,000 with an **exchange-rate difference of −10,000.00**.
4. **Daily rate.** The rate is entered per day and currency, with its source. If no USD rate is entered, the system's existing daily USD rate is used. A change needs a reason and keeps the old and new values. Each statement line keeps its original amount, its rate and its LKR value.
5. **USD account at 5 Sep:**
   - bank USD 44,985.00, worth 13,585,470.00 LKR at 302.00;
   - book 13,500,000.00 LKR;
   - difference 85,470.00, explained as exchange-rate differences −7,500.00, a charge −4,530.00 and revaluation 97,500.00.

---

## 4. Pending decisions

"Testing" means further testing with dummy data. "Real-data trial" means entering real accounts and statements on the development system. "Live" means installation on the live system.

| # | Decision | Options | My recommendation | Blocks dummy testing? | Blocks real-data trial? | Blocks live? |
|---|---|---|---|:-:|:-:|:-:|
| **D1** | Off-computer copy of the database export (it contains supplier bank details and customer data) | (a) you copy it to an external drive you keep; (b) I send it into this chat; (c) your own cloud storage | **(a)**: an external drive, kept safe. Also (b) if you want a second copy. | No | No | **Yes**: have it before any live work |
| **D2** | Who prepares and who reviews | (a) as built: Accounts prepares; Management Approver reviews; Bank Authoriser and Auditor view; (b) Bank Authoriser also reviews; (c) a named senior accountant reviews | **(a)**: the Bank Authoriser marks payments Paid, so they should not also sign off the reconciliation of those payments | No | Yes | **Yes** |
| **D3** | First day of reconciliation, per account | (a) 1 Sep 2026 (provisional); (b) 1 Oct 2026; (c) the first day of the month after go-live | **The first day for which you will have both the bank statement and a complete ledger**, probably (b) or (c). (a) needs all September statements and a fresh live ledger. | No | Yes | **Yes** |
| **D4** | Entering the real Avenir bank accounts (master) | (a) you; (b) a named Accounts user, with a different person confirming each mapping | (a) or (b), with a second person confirming. I will not enter or assume account numbers. | No | **Yes** | **Yes** |
| **D5** | Statement format | (a) CSV export from web banking (supported); (b) reading Excel files directly (needs a library installed on development: your approval); (c) PDF (not supported in the app) | **(a)**. Later, provide one real CSV export per bank (Commercial Bank, Pan Asia) so I can confirm the columns are recognised. This is a format check on development, with your approval. | No | Yes (one sample per bank) | **Yes** |
| **D6** | Source of the daily USD and JPY rate | (a) the system's daily USD rate already confirmed each day; (b) the bank's board or buying rate; (c) the Central Bank rate | **USD (a)**, consistent with the payment workflow; **JPY (b)** until a JPY daily rate exists. The rate actually obtained on each transfer is shown beside it anyway. | No | Yes | **Yes** |
| **D7** | The old Bank Reconciliation screen (`accounts/bank_reconcil`; it writes bank_rec / bankst_flag on ledger rows) | (a) leave it unchanged and in use; (b) keep it but stop using it for periods reconciled in the new workflow; (c) withdraw its menu after a parallel month | **(b) now, then (c)** after one month of parallel use | No | No | No (a usage rule is needed) |
| **D8** | Clean-up | (a) keep the restore-verify database and `avenir_rv` until the demonstration is reviewed, then remove; (b) remove the withdrawn helper folder `C:\wamp64\www\_claude_tools` now | **(a) and (b)**. Removing needs your permission each time. | No (but `avenir_rv` is needed for the demonstration) | No | No |
| **D9** | **New, from this review:** a timing item must have its classification cleared before it can be matched, and suggestions skip it | (a) leave as is (workaround: *Clear*, then match); (b) small fix: matching a timing item clears it automatically, and suggestions include timing items | **(b)**, tested on the cloud copy and the isolated instance first | No | No | Recommended before live |
| **D10** | **New, from this review:** on the very first days, an item booked before the first day that clears later (the 30 Aug cheque) leaves the opening difference "insufficient data" until the day it clears | (a) leave as is (explain such items as accepted opening items); (b) small fix: count such items as outstanding (timing) at the start, so day 1 can reconcile | **(b)**, with the same testing | No | No | Recommended before live |
| **D11** | Dummy data on the development database for demonstrations | (a) keep development clean and demonstrate on the isolated instance; (b) load the dummy accounts into development too | **(a)** | No | No | No |
| **D12** | Live installation | Only after D1–D7 and D9–D10 are settled, a fresh pre-install backup of live, and your written approval | **Not now.** You have authorised review and demonstration only. | — | — | **This is the live step** |

### Other open decisions from earlier steps

These are outside the reconciliation, listed so nothing is lost. None blocks reconciliation testing.

| # | Decision | My recommendation |
|---|---|---|
| E1 | Avenir's own paying account number is shown in full to the Management Approver and the Auditor on batch and voucher pages | Mask it to the last 4 digits for roles that do not pay |
| E2 | Older staff groups (id ≤ 28) still see full supplier bank numbers | Review when staff move to the department profiles; no change until then |
| E3 | InnoDB conversion of the MyISAM tables (ledger, driver_allocation, audit) | Plan it as a separate, backed-up step; it makes failed saves roll back cleanly |
| E4 | The Tour Dashboard link opens the debtor-invoice amendment screen to every Tour Dashboard holder | Restrict it to Accounts |
| E5 | `tour/print` is open to every signed-in user | Restrict it to the roles that print tours |
| E6 | Retire `LEGACY_ALLOCATION_GROUPS` once staff are on the department profiles | Later, with E2 |
| E7 | The overall ledger imbalance of 449,143.20 and the provisional 34,495,229.47 difference | Investigate after real data is available, separately from the workflow |

---

## 5. Verified test results and known limitations

### Cloud tests (my workspace: cloud test copy, MariaDB 10.11)

| Test | Result |
|---|---|
| Full workflow with dummy LKR, USD and JPY accounts. Covers: mapping (four eyes), rates, CSV and typed statements, the balance-break check, line correction with the original kept, double-import detection, suggestions, manual and many-to-one matches, differences, timing / duplicate-looking items, charges, corrections (never posted), transfer, revaluation, daily record prepared → returned → reviewed, undo, void rules, audit, refusals for 8 other profiles | **85 / 85** |
| The same screens in a real browser: preparer, reviewer, view-only, refused department, phone width | **14 / 14** (screenshots 01–15) |
| Existing suites after the change: R4 · R5 bulk · Pilot · Operations/confidentiality · r3 · ledger guard | **202 · 71 · 36 · 33 · 26 · 10, all passing** |
| Rehearsal of the demonstration statement in section 2B (12 Oct) | All figures as stated |
| Real ledger rows before and after every run (count, sums and a checksum of every row, including the reconciliation flags) | **Identical** |

### Checks on your computer

| Where | Test | Result |
|---|---|---|
| Isolated test instance (`avenir_rv`, MySQL 8.4.7, WAMP) | The full process with the department test logins and dummy data | **56 / 56**. The real ledger rows in that database are unchanged. Every action and refusal is audited under the test login that made it. |
| Development system (`avenir`) | Install: 12 files verified against the kit; Set up; 9 privilege rows; the 966 existing privilege rows unchanged | Done (11 Oct, 22:24) |
| Development system | Probes with all seven test logins, read-only and refusals only: Accounts and the Management Approver see the page and data; the Bank Authoriser views only; Tour Reservations, Hotel Reservations, Transport and Operations are refused; a change without the security token is refused | **All as designed** |
| Development system | Ledger before and after; reconciliation tables | Ledger **identical** (11,364 rows; re-checked 12 Oct). Tables **empty**. |
| Both copies (12 Oct) | Installed files against the kit | **Identical**, no drift |

**Not yet tested:**
- real bank export formats (Commercial Bank, Pan Asia), real volumes and a real month;
- the live system;
- several people working on the same account at once;
- browsers other than Chromium-based ones.

### Known limitations

1. **Statement formats.** Only CSV and typed lines. Excel needs an approved library (D5); PDF is not read in the app.
2. **Timing items** must be cleared before matching (D9).
3. **Opening days.** An item booked before the first day that clears later shows "insufficient data" until it clears (D10).
4. **USD and JPY ledgers are kept in LKR only.** USD and JPY lines are therefore matched on their LKR value at the day's rate (within 1 %), and the gap is shown as an exchange-rate difference. The separate USD ledger table (`account_runing_usd`, one row) is not used.
5. **Approved corrections are not posted.** Accounts posts them by hand, records the ledger entry ID, then matches the posted row to the statement lines in the normal way.
6. **Suggestions are rules-based.** Look-alike payments (same amount and PV) are deliberately not auto-matched. The preparer decides.
7. **Daily records are prepared on request.** There is no automatic daily run.
8. **On a phone,** wide tables scroll sideways. They are readable, but the dashboard redesign's card layout is not applied here.
9. **Set up on development** was pressed by me in your administrator session, so the audit shows "super admin". Your development session was signed out at the end of the probes.
