# Step 2: Daily Bank Reconciliation workflow — built and tested (11 Oct 2026)

**Ready for your review.**

- The work was done on the development and test system only. Nothing was installed on the live system.
- No real accounting data was posted or changed, and no reconciliation flag in the ledger was touched.
- As you corrected, this step prepares and tests the process. It is **not** Avenir's actual reconciliation. That follows once the real data has been entered and you approve.

**Screenshots** are in `bankrec-screenshots/`. They show dummy data only. Files 01–15 are from the cloud test copy, 16 from the isolated test instance on your computer, and 17 from the development system.

**Test records** are in `bankrec-test-results/`.

---

## A. Scope correction (done)

- **Step 2 note** (`step2-opening-balances-and-inputs-2026-10-11.md`), rewritten:
  - LKR 34,495,229.47 is labelled **Unreconciled — insufficient data**.
  - Each observation now reads "to check once the real data has been entered". None is described as a loss, a missing receipt, a duplicate payment or a system error.
  - Historical statements are no longer requested.
- **Workbook** (`combank-lkr-sep2026-inspection.xlsx`), relabelled:
  - "sample comparison (provisional)";
  - the "Book issues" sheet is now "Observations (unverified)";
  - wording is "duplicate-looking, not confirmed".

## B. Safeguards

| Safeguard | Result |
|---|---|
| **Pre-R5 backups** | Untouched. `avenir_backup_20261011` last changed 06:56; the before-R4 and before-R5 folder copies last changed 01:13 and 06:30. |
| **Post-R5 backup** | `avenir_backup_20261011_post_r5` is recorded as the verified post-R5 recovery backup. |
| **Restore copy** | `avenir_restore_verify_20261011` is recorded as a test bed, **not a recovery source**. Kept, as instructed. |
| **Independent exported backup** | `C:\wamp64\tmp\avenir-exports-20261011\avenir_backup_20261011_post_r5.sql.gz`: 12.05 MB, SHA-256 `3d4cf936…a0dbb8`, with a manifest and restore instructions. Restore-tested on a separate MySQL 8.0 server: 228 tables, 373,092 rows, all 228 table checksums equal, 13 views, 57 foreign keys, 205 auto-increment values identical, 4 triggers. |
| **dbmasteruser rollback script** | **Not run.** It is now an emergency record (`definer-repair-rollback-20261011.sql`) with an approval check, development-server identification, pre-checks, post-checks and a recovery procedure for partial failure. A separate recovery file, `definer-repair-forward-20261011.sql`, goes with it. Its behaviour was tested only on a disposable scratch copy (`definer-record-tests/`). On the development server only the read-only checks were run: the server is identified correctly, the objects are in the repaired state, and 0 allocations lack an audit row. |
| **The two money matters** | Recorded separately in section F. Neither blocked the work. |

**About the independent export:**
- It protects against disk failure only once a copy is kept off this computer (decision G1).
- It was made with phpMyAdmin's own export. Two small differences, with no behavioural effect:
  - the triggers are recreated under the account that restores the file;
  - a SQL comment inside three trigger bodies is dropped.

**A note on how the export was made:**
- My first attempt was a small helper page in `C:\wamp64\www\_claude_tools\` that would have run mysqldump from the web server.
- It was withdrawn before it was ever used and now cannot run: the address answers "not found" and the folder refuses access. It is safe to delete that folder.
- The export was then made with phpMyAdmin's standard export instead.

## C. How the workflow works

**Where it is:** Accounts → **Daily Bank Reconciliation** (`accounts/daily_bank_rec`). It has three pages:
- the accounts overview;
- one account's workbench;
- Rates & transfers.

### The daily process

1. **Set up the account.** The bank account is entered in the existing Avenir Bank Accounts master: bank, account, currency and ledger account. In Reconciliation, a preparer then *proposes* its ledger mapping and its first day, and a different user with the review privilege *confirms* it. If the master later changes, the mapping drops back to "Changed in master — confirm again" until it is confirmed again.
2. **Rates.** For USD and JPY accounts, the preparer enters the day's rate in LKR per unit, with its source. If no USD rate is entered, the system's existing daily USD rate is used. Any change to a rate needs a reason and is kept with its old and new value.
3. **Statements.**
   - **Input:** upload the bank's CSV export, or type the lines in.
   - **Check:** recognises the columns and dates; checks every printed balance against opening plus lines, opening against the previous statement's closing, and gaps and overlaps; flags duplicate-looking lines (also against earlier imports) and missing rates.
   - **Keep as draft:** a draft line can be corrected only with a reason, and the imported original is kept.
   - **Accept:** the lines are then locked.
   - **Never posted:** nothing is ever posted to the ledger. A wrong accepted statement can only be voided, by a reviewer, and is kept for the record.
4. **Match.** Statement lines are matched with ledger rows. Suggestions use the amount (LKR, or within 1 % for USD and JPY), the PV number, cheque and tour numbers and the dates. They also cover one bank line paying several ledger rows of one PV, and cheques written before the first day. Nothing is matched until the preparer accepts. A difference inside a match must be explained as exchange rate, rounding (up to 1.00) or other.
5. **Classify what does not match:**
   - **timing**, with the expected clearing date;
   - **duplicate-looking**, flagged only, never deleted;
   - **excluded**, with a reason;
   - **bank charge**, recognised from the wording, such as CEFT, fund-transfer or GPRS charges.
6. **Transfers.** Money leaving one Avenir account and arriving in another is paired, for example USD → LKR. The record shows:
   - the amount in each currency;
   - the rate actually obtained;
   - the day's rate;
   - the LKR difference.

   Each side is still matched to the ledger in its own account.
7. **Corrections.** These are *proposals* for a reviewer: a balanced journal (ledger account, debit and credit) with a reason.
   - For the selected bank charges, a correction is built from the payment workflow's existing bank-charges mapping.
   - A reviewer who did not propose it approves or rejects it.
   - The system **never posts** a correction. After approval, Accounts may post it by hand in the accounting module and then record the ledger entry ID against it.
8. **Opening items.** These explain bank minus book on the first day. They are proposed, then accepted by a reviewer.
9. **Daily record.** The preparer records the day as it stands. A different user reviews it or returns it with a reason.
10. **Audit.** Every action is written to the existing audit trail with:
    - the user;
    - the date and time to the millisecond;
    - the record;
    - the values before and after;
    - the reason.

    Refusals are recorded too.

### Bank and book are kept separate

| | Bank (accepted statements) | Book (ledger, read only) |
|---|---|---|
| Opening | statement opening on the first day, in the account currency, and its LKR value at that day's rate | ledger balance at the end of the day before |
| Closing | closing at the chosen day, in the account currency, and its LKR value at that day's rate | ledger balance at the end of the chosen day |

**The difference (bank − book, in LKR) is explained, item by item, by:**
- the opening difference still unexplained;
- accepted opening items;
- statement items not in the ledger;
- ledger items not on the statement;
- differences inside matches;
- for USD and JPY, the revaluation of the balance at the day's rate.

Any **unexplained remainder** must be 0.00. If it is not, a statement or the ledger is incomplete or has changed.

### Status of a day

| Status | When |
|---|---|
| **Not set up — ledger mapping not confirmed** | No confirmed mapping. |
| **Unreconciled — insufficient data** | Any of the following: a missing or broken statement, a gap, a missing rate, an opening difference not yet explained, or a ledger row changed after it was matched. |
| **Unreconciled — items to explain** | Some line or ledger row is neither matched nor classified, nor covered by a correction. |
| **Reconciled — all items explained (awaiting review)** | Everything is explained and the remainder is 0.00. |
| **Reconciled — reviewed** | As above, and a different user has reviewed that day's record. |

### Who can do what

The proposed department grants are decision G2.

| Profile | View | Prepare: import, match, propose | Review: confirm, approve, sign off |
|---|:-:|:-:|:-:|
| Accounts | ✓ | ✓ | — |
| Finance & Approvals — Management Approver | ✓ | — | ✓ |
| Finance & Approvals — Bank Authoriser | ✓ | — | — |
| Auditor (read-only); no users yet | ✓ | — | — |
| Tour Reservations, Hotel Reservations, Transport & Guide Handling, Pilot / Operations, older staff groups | — | — | — |
| System Administrator | ✓ | ✓ | ✓ |

- **Four eyes.** No one, the administrator included, can confirm, approve or review something they proposed or prepared.
- **Account numbers.** Avenir account numbers are shown masked here, last four digits only. Their visibility elsewhere is unchanged, as you asked.

## D. Test results

| Where | What | Result |
|---|---|---|
| Cloud test copy | Full workflow with dummy LKR, USD and JPY accounts (`cloud_workflow_85_checks.json`) | **85 / 85** |
| Cloud test copy | Screens in a real browser, three roles and phone width (`cloud_browser_14_checks.json`), screenshots 01–15 | **14 / 14** |
| Cloud test copy | Existing suites after the change: R4, R5 bulk, Pilot, Operations/confidentiality, r3, ledger guard | **202/202 · 71/71 · 36/36 · 33/33 · 26/26 · 10/10** |
| Isolated test instance on your computer (`avenir_rv`, MySQL 8.4.7) | The same process with the department test logins (`isolated_instance_56_checks.txt`), screenshot 16 | **56 / 56** |
| Development system | Install, Set up, grants; read-only and refusal probes with all seven test logins (`development_install_probes.txt`), screenshot 17 | **all as designed** |
| Every run | Real ledger rows before and after: count, sums and a checksum of every row, including aflag, bank_rec, bankst_flag and BankFlg | **identical** |

**What the dummy data proved (LKR account, 1–5 Sep):**
- **Opening.** Bank opened at 1,000,000.00 and the book at 850,000.00. The 150,000.00 difference showed "Unreconciled — insufficient data" until the cheque written on 30 Aug was matched to the bank line of 2 Sep. Remaining opening difference: 0.00.
- **5 Sep.** Bank 3,893,106.00 against book 3,547,655.00, a difference of 345,451.00. It is explained by:
  - statement items not in the ledger: 230,450.00 (4 charges −4,550.00 and a cash deposit +235,000.00, each with a correction);
  - ledger items not on the statement: 115,000.00 (a timing item of 40,000.00 and a duplicate-looking 75,000.00);
  - rounding inside a match: 1.00.
- **Unexplained:** 0.00.

**USD:** a difference of 85,470.00, explained by:
- exchange-rate differences inside matches: −7,500.00;
- a USD 15 charge: −4,530.00;
- revaluation at the day's rate: 97,500.00.

The USD → LKR transfer showed a rate obtained of 300.00 against a day's rate of 301.00, a difference of −10,000.00 LKR.

**JPY:** a difference of 32,000.00, all revaluation.

**Refusals tested:**
- other departments: page, data and changes all refused;
- the view-only profile cannot change anything;
- a change without the CSRF token is refused;
- the preparer cannot confirm, approve or review;
- the administrator cannot approve their own proposal;
- an accepted statement cannot be edited, and cannot be voided while it has matches;
- an unbalanced proposed entry is refused;
- a duplicate import is flagged and its overlapping period refused.

## E. Files changed and rollback

**Kit:** `tf-bankrec-r1` (`Claude outputs\tf-bankrec-r1\`, zip `tf-bankrec-r1.zip`).

| | File |
|---|---|
| changed | `app/Config/Routes.php`: one route group added inside `accounts` (was 23467c1c…, now 921c2ea6…) |
| added | `app/Controllers/Accounts/DailyBankRecController.php` |
| added | `app/Libraries/BankRec/` Schema, Access, Rec, Statements, Matching, Corrections |
| added | `app/Views/pages/accounts/bankrec/` _common, index, account, fx |

**Database on development:**
- 11 new tables `brc_*`, empty;
- 3 new privilege entries;
- 9 privilege rows: 3 for the System Administrator, 6 department grants;
- `tpd_meta` `brc_schema_version` = 1.

No existing table or row was altered. The 966 existing privilege rows are unchanged.

**Rollback:**
- `python3 apply.py rollback <avenir root>` puts Routes.php back and moves the 11 added files aside into `writable/bankrec_r1_rolled_back_<time>/`. Nothing is deleted.
- Backup of this install: `before\20261011-222403`.
- The tables are left in place, so no history is lost.
- To withdraw the menus as well, untick the three "Daily Bank Reconciliation" privileges under Settings → Group Privileges.
- A rehearsal restored Routes.php exactly.
- On the isolated instance the kit was applied twice. Each apply has its own backup and is undone in turn.

## F. Separate matters, recorded for later investigation

Neither blocked development.

| Matter | Amount (LKR) | Status |
|---|---:|---|
| Overall ledger imbalance in the development copy (ΣDr − ΣCr, all accounts) | 449,143.20 | Separate; for later investigation |
| Provisional bank-to-book opening difference, Com Bank LKR, 1 Sep 2026 (sample statement against the development copy) | 34,495,229.47 | Unreconciled — insufficient data |

## G. Decisions for your approval

1. **Off-computer copy of the export.** Choose where it goes: an external drive or other storage you choose, or I send it into this chat. It contains the full database, including supplier bank details.
2. **Who does what.**
   - Accounts prepares, the Management Approver reviews, and the Bank Authoriser and Auditor view only.
   - Should the Bank Authoriser also review?
   - These are new accounting privileges on the department profiles, which today hold only test logins. No existing staff login received anything.
3. **First day of reconciliation per account.** Provisionally 1 Sep 2026. Each account's first day is set when its mapping is proposed.
4. **Setting up the real accounts.** The Avenir Bank Accounts master on development is empty, and I have not entered or assumed any account number. You, or someone you name, enters them, and their mappings are proposed and confirmed.
5. **Statement formats.**
   - CSV is supported: from Excel, use Save As → CSV.
   - Reading Excel files directly would need a new library installed on the development system, so it was not done.
   - The Commercial Bank PDF reader used earlier runs only in my workspace.
   - I recommend the bank's CSV export.
6. **Source of the daily USD and JPY rate.** For example, the bank's buying rate shown on the transfer, or the Central Bank rate. The source is recorded with every rate.
7. **The old Bank Reconciliation screen** (`accounts/bank_reconcil`).
   - It writes `bank_rec` / `bankst_flag` on the ledger rows, and its "post unrealised entries" function updates ledger rows.
   - The new workflow never touches those flags.
   - Should the old screen be withdrawn later, or kept in parallel?
8. **Removal later**, when you are ready:
   - the restore-verify database and its test instance `avenir_rv`, which hold dummy data only;
   - the withdrawn helper folder `_claude_tools`.

## H. Unresolved and limitations

- **Real data.** Real statements and a fresh copy of the live data have not been entered. That is the next stage, after your approval.
- **Signed out of the development application.** The browser pane was signed out of the development application at the end of the probes. Please sign in again when you next use it.
- **Set-up attribution.** The Set up button on development was pressed by me in your signed-in System Administrator session. The audit shows it as "super admin" at 22:24.
- **Unchanged items.** Avenir's paying-account visibility, the older staff groups' access and the InnoDB conversion are unchanged and still await your decision.
- **Next task.** The Tour Payment Dashboard redesign is queued as the next task. It will not start until you have reviewed this report.
