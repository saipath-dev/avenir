# Daily Bank Reconciliation r2: D9, D10, safeguards and posting status — test-stage report (12 Oct 2026)

**Scope of this stage**
- I built and tested the changes in my cloud workspace first. I then installed and verified them on the **isolated test instance** only (`http://localhost/avenir_rv/`, database `avenir_restore_verify_20261011`).
- **Not** applied to the development system (`avenir`). It is still on r1, and its reconciliation tables are empty. Live is untouched.
- Only dummy data and test logins were used. No real bank account was entered, no real statement was imported, and no accounting entry was posted.
- **I have stopped here.** The next step needs your approval.

---

## 1. What changed and where

### The changes (kit `tf-bankrec-r2`)

| Change | What it does now |
|---|---|
| **D9: timing items in matching** | **Listed and suggested.** Timing items now appear with the open items and in the suggestions ("accepting clears the classification"). <br>**What a match keeps.** Accepting a match clears the timing classification but keeps it on record: who classified it, when, the expected date, and a history (cleared → restored → cleared). <br>**Undo.** An undo restores the same classification, with the same classifier, time and expected date. <br>**Two users.** A match must name the timing classifications it clears. If another user classified the item a moment earlier, the match is refused with "Reload and confirm…". |
| **D10: opening outstanding items** | **Candidates.** Ledger rows booked before an account's first day are listed as candidates, with their effect on bank − book. <br>**Four eyes.** The preparer proposes one as an opening outstanding item. A different person (the reviewer) accepts it. <br>**What explains the opening difference.** Only accepted items explain it. A row that clears later *without* being accepted never explains anything; the day shows "not an accepted opening item → insufficient data". <br>**Counted once.** Each date shows each opening item as *outstanding* or *cleared on [date]*. It counts as an opening item until the day it clears, then inside its match. |
| **Partial clearing** | A match can clear part of an item. The rest stays outstanding, with an expected date. A later line completes it, and the partial match is kept as superseded. Undoing the completion makes the partial match active again. |
| **Two users at once** | **Guards:** <ul><li>one claim per matched or classified item, enforced by the database;</li><li>one change at a time per account;</li><li>version checks on statement lines and rates;</li><li>conditional updates for acceptance, decisions and reviews.</li></ul>**Result:** duplicate matches, duplicate acceptance and double review are refused (409). |
| **Changes after review** | **What a record keeps.** Each daily record keeps its snapshot, plus fingerprints of what it rests on: ledger rows, statement lines, rates, matches, explanations and opening items. <br>**A later change** flags the record **"Changed since review — re-review needed"** and names what changed. The reviewed snapshot is kept unchanged. <br>**Corrections afterwards.** Approving or posting a correction after the review is recorded as posting progress. It does not make the review out of date. |
| **Exchange-rate differences** | **Visible.** Differences stay visible in each match. <br>**Need an explanation.** The 1 % suggestion tolerance never explains a difference: each needs a written explanation. A USD or JPY daily record cannot be prepared until the revaluation is explained. <br>**From r1.** Differences recorded under r1 without an explanation now show as "to explain". |
| **Reconciliation status** | Clear states (section 2). A proposed or approved correction is never shown as "Reconciled"; the day reads "ledger not yet corrected". Pending postings are shown on: <ul><li>the day;</li><li>the overview;</li><li>the corrections list;</li><li>reviewed daily records.</li></ul> |

**Files:** `Routes.php` (3 routes added in the r1 group), the controller, `Corrections` / `Matching` / `Rec` / `Schema` / `Statements`, and the four views. `Access.php` is unchanged.

**Database (schema version 2):**
- two new tables: `brc_claim` and `brc_mark_event`;
- columns added to the reconciliation (`brc_`) tables only.

No other table is altered.

### Where it is

| Place | Code | Database | State |
|---|---|---|---|
| **Isolated test instance** (`avenir_rv`) | **r2.** 12 files, each md5-checked against the kit. The r1 files are backed up in `Claude outputs\tf-bankrec-r2\before\20261012-002808\`. | `avenir_restore_verify_20261011` at **schema version 2**, with a dummy verification account. | **Verified (section 4).** Left in its post-demonstration state for your review. |
| **Development** (`avenir`) | **r1, unchanged.** All 12 files match the r1 kit; the r2 routes are absent. | `avenir` at **schema version 1**. Reconciliation tables, bank-accounts master and dummy-range ledger rows are all **empty**. No audit entry since 03:56 today. | **Unchanged** |
| **Cloud test copy** | r2 | MariaDB 10.11 copy, plus a MySQL 8.0 rehearsal copy | Tested (section 3) |
| **Live** | — | — | **Not touched** |

---

## 2. Reconciliation status as shown on screen

| What you see | What it means |
|---|---|
| **All items explained — ledger not yet corrected — N correction(s) awaiting approval (amount)** | Differences are explained, but corrections are only **proposed** |
| **… — N approved but not posted (amount)** | Corrections are **approved**, but the ledger has **not** been corrected |
| **Posted — linked to a verified ledger entry** | Accounts posted the entry by hand; its ledger rows were checked against the approved lines and linked |
| **Posted entry changed in the ledger — verify again** | The linked entry was changed after verification, so the item is no longer explained |
| **Reconciled — all items explained** / **Reconciled — reviewed** | Shown **only** when nothing is pending |

**Reviewed days.** A reviewed day with pending postings reads **"Reviewed — ledger not yet corrected — …"**. Its daily record keeps the pending list as it stood when prepared, next to the position now.

**Nothing was posted in this work.**
- **Cloud:** the "posted and verified" state was tested with a 2-row dummy posting inserted by the test, standing in for Accounts.
- **Isolated instance:** no ledger entry was created, so that state was not exercised there (section 6).

---

## 3. Cloud results (my workspace)

| Suite | Result |
|---|---|
| **r2 API suite on the real application code.** <br>**Covers:** <ul><li>D9 and D10;</li><li>partial clearing, undo and reopening;</li><li>opening items over 5 days;</li><li>changes after review;</li><li>exchange-rate explanations;</li><li>posting states, including posted and verified;</li><li>four eyes, including an administrator who cannot approve their own proposal;</li><li>refusals for 8 other profiles.</li></ul>**True parallel races** (the two users' requests were in the server at the same time): <ul><li>duplicate matches: 10 requests from 2 preparers → 1 match, 9 refused;</li><li>duplicate acceptance;</li><li>match vs classify;</li><li>two reviewers;</li><li>conflicting line edits;</li><li>two drafts of one period;</li><li>conflicting rate edits.</li></ul> | **108 / 108** |
| r2 screens in a real browser (preparer, reviewer, phone width; no script errors) | **20 / 20** |
| Existing suites: R4 · R5 bulk · Pilot · Operations/confidentiality · r3 · ledger guard | **202 · 71 · 36 · 33 · 26 · 10 — all pass** |
| **Rehearsal of the isolated-instance run.** Used the `avenir_rv` demonstration data as saved on 12 Oct, the kit's upgrade SQL, and the same browser script in two sessions. Run again after the script fix in section 6. | **63 / 63** (both runs) |
| Upgrade → rollback → reset on MySQL 8.0 with the saved demonstration copy | Every table back **byte for byte** |
| Real ledger rows before and after every run (count, sums, and a checksum of every row including the reconciliation flags) | **Identical** |
| Audit | Every change and refusal is audited under the test login that made it |

---

## 4. Isolated test instance results (your computer: WAMP, MySQL 8.4.7), 12 Oct 06:52–07:13 by the computer's clock

### Steps

1. **Resettable copy kept first.** The demonstration data had been saved before r2 (`sql/demo-snapshot-r1-20261012/`, 13 tables). Before the upgrade I re-checked that the instance still held exactly that data: same row counts in all 13 tables, and no activity since the copy was taken. `sql/reset-demo-to-r1-snapshot.sql` puts it back.
2. **Upgrade.** The kit's guarded upgrade SQL brought the schema from version 1 to 2. Its built-in check: **21 claims = 21 matched or classified items**.
3. **Dummy data.** Loaded the verification account (inactive "TEST LKR verification account (dummy)", ledger 1-6-8-29904, 7 dummy ledger rows 99980301–07).
4. **Test run.** Ran the browser script with the department **test logins** (TestAccounts, TestFinApprover, TestBankAuth, TestHotelRes, TestTourRes, TestTransport, TestOperations).
   - **Two sessions:** one tab on `localhost` and one on `127.0.0.1`. The site keeps a separate sign-in for each address, so these were two independent sessions.
   - **Races:** each was sent from both tabs at the same moment.

### Results: **57 / 57 checks, 7 / 7 two-session tests, 4 / 4 database checks**

| Area | What was checked on the instance | Result |
|---|---|---|
| **Existing demonstration under r2** (6) | **D10 rule on old data.** LKR 5 Sep now reads "Unreconciled — insufficient data": the 30 Aug cheque cleared on 2 Sep but is not an accepted opening item. <br>**Re-review flag.** The record reviewed under r1 is flagged "re-review needed", with its snapshot kept. <br>**USD.** The two r1 exchange-rate differences, recorded without an explanation, now show as "to explain". <br>**JPY.** Still explained. <br>**Corrections.** Shown as "Approved — not posted". <br>**Overview.** Shows the re-review flag. | **All pass** |
| **6–8 Sep dummy-statement demonstration** (16) | **D10.** The 30 Aug cheque is listed among the pre-start rows, proposed by TestAccounts and accepted by TestFinApprover. Day 1 then reconciles: opening difference explained, 150,000.00 outstanding; on 5 Sep it shows cleared on 2 Sep. <br>**Import.** Checked: no errors, closing 3,893,081.00. Kept as a draft and accepted. <br>**D9.** PV 90004 was suggested, at high confidence, for the *timing* item 99980009, without clearing it first. Accepting the match cleared the classification and kept its history. <br>**8 Sep figures.** Bank 3,893,081.00, book 3,547,655.00. The difference of 345,426.00 is explained to 0.00, with three items to explain. <br>**Explanations.** The charge and the receipt were proposed as corrections, and the payment was classified as timing. The day then read "All items explained — ledger not yet corrected — 2 awaiting approval (100,025.00); 2 approved but not posted (239,550.00)". <br>**Review.** Prepared by TestAccounts, who could not review it (403). TestFinApprover approved the corrections (not posted) and reviewed the day: **"Reviewed — ledger not yet corrected — 4 approved but not posted (339,575.00)"**. <br>**5 Sep.** Prepared again and re-reviewed; now current. <br>**USD.** Both differences explained. Preparing without a revaluation explanation was refused (400); preparing with one, then reviewing, gives "Reviewed — ledger not yet corrected". | **All pass** |
| **Opening items across several days** (verification account, 1–5 Sep) | **Two cheques (000201 and 000202).** Both booked before the first day, proposed and accepted. Proposing the same row again was refused (409), as was a row dated after the first day (400). The preparer could not accept (403). <br>**Day by day, bank − book is explained to 0.00 each day:** <br>1 Sep: 49,975.00 · 2 Sep: −25,025.00 · 3 Sep: 4,975.00 · 4 Sep: −10,025.00 · 5 Sep: 29,975.00 <br>**Cheque status.** Cheque 000202 is outstanding throughout. Cheque 000201 is outstanding on 1–2 Sep and cleared from 3 Sep, counted once. | **All pass** |
| **Timing items clearing later, partial clearing, undo, reopening** | **Partial clearing.** It clears the agent receipt's timing classification, so it was refused until confirmed (409), then recorded: 50,000 of 90,000, rest −40,000.00 expected 5 Sep. <br>**D9 confirmation.** PV 90011 was suggested at high confidence ("accepting clears the classification"). It was refused without confirming the classification (409), then matched. <br>**Undo and reopen.** The undo restored the same classification (same classifier, time and expected date 4 Sep). It was matched again; the history reads cleared → restored → cleared. <br>**Completing the partial match.** The 5 Sep line completed it, and the reviewed 3 Sep record stayed current with the same figures. Undoing the completion made the partial match active again, and it was completed again. | **All pass** |
| **Changes after review** | **Before review.** A record changed after preparation could not be reviewed (409). <br>**Ledger row.** A matched dummy ledger row (99980307) was changed in the database after review. The day turned "insufficient", and the record was flagged **"Changed since review — re-review needed: ledger rows"**, with the reviewed snapshot unchanged. Put back exactly, the review was current again. <br>**Exchange rate.** A rate change after review flagged the reviewed USD record (statement LKR values, exchange rates). Put back, it was current again. <br>**Pending postings.** A reviewed record keeps the pending postings as prepared and shows them now. <br>**Corrections approved after review.** These did not invalidate it ("corrections progressed since"). <br>**Recording a posting.** An entry that does not match the approved lines was refused (409). | **All pass** |
| **Permission separation and same-person guards** | **Four eyes.** The proposer cannot accept an opening item (403). The reviewer cannot withdraw another's proposal (403) or match (403). The preparer cannot review (403). <br>**View-only.** Cannot propose or explain (403), but can read (200). <br>**Other departments.** TestHotelRes, TestTourRes, TestTransport and TestOperations have no access (403) to data, candidates or explanations. | **All pass** |

**Two sessions at once (same moment, from two tabs):**

| Test | Outcome | Confirmed in the database |
|---|---|---|
| Duplicate matches (5 + 5 requests on one line) | **1 match, 9 refused** (409 "already matched (match #13)"); the first requests of both sessions overlapped in time | One active match per line and per ledger row; claims 34 = items 34 |
| Duplicate acceptance of one draft statement | **1 accepted, 1 refused** (409 "it was accepted by TestAccounts at 07:05:06") | One "accepted" audit row |
| One session matches a ledger row while the other classifies it as timing | **Match accepted; classification refused** (409 "already matched") | — |
| Two reviews of the same daily record | **1 reviewed, 1 refused** (409 "not waiting for review") | One "reviewed" audit row |
| Conflicting edits of one statement line (both on version 0) | **First kept; second refused** (409 "changed by someone else after you opened it") | — |
| Two drafts of the same period accepted at once | **1 accepted; 1 refused** (overlap); the refused draft voided | — |
| Conflicting rate edits (both on version 0) | **First kept; second refused** (409); the reviewed USD record flagged, then current again when the rate was put back | — |

**Audit on the instance (this run):**

| Login | Audit rows |
|---|---:|
| TestAccounts | 64 |
| TestFinApprover | 17 |
| TestBankAuth | 2 |
| TestHotelRes, TestTourRes, TestTransport, TestOperations | 4 each |
| `claude-technical (upgrade script run in phpMyAdmin; isolated test instance)` | 1 (the schema upgrade) |

**Timing history kept** (`brc_mark_event`): 7 events, each with user and time.

---

## 5. Evidence that the existing ledger rows are unchanged

The ledger check value covers four things:
- the row count;
- the sum of Dr;
- the sum of Cr;
- a checksum over every row's ID, account, amounts, description, date and the four reconciliation flags (`aflag`, `bank_rec`, `bankst_flag`, `BankFlg`) and receipt/cheque numbers.

| Database | Rows covered | Before | After | Same? |
|---|---|---|---|---|
| Your database export (reference) | Real rows | `11364 · 965,763,907.13 · 965,314,763.93 · 24383521306171` | — | — |
| **Development** `avenir` | All rows (there are no dummy rows) | `11364 · 965,763,907.13 · 965,314,763.93 · 24383521306171` | the same | **Yes** (= export) |
| **Isolated instance** | Real rows | `11364 · 965,763,907.13 · 965,314,763.93 · 24383521306171` | the same | **Yes** (= export) |
| **Isolated instance** | Everything except the new verification rows (real + 15 r1 dummy + 4 earlier dummy rows) | `11383 · 988,673,607.13 · 968,772,808.93 · 24425814230570` | the same | **Yes** |
| **Isolated instance** | The 7 verification dummy rows | Not measured at load; the same SQL loaded in the cloud copy gives `7 · 590,000.00 · 85,000.00 · 14102896273` | `7 · 590,000.00 · 85,000.00 · 14102896273`, after the deliberate edit of 99980307 and its exact restoration | **Yes** (equal to the rows as loaded) |

**The module never wrote to the ledger.**
- **Ledger rows:** the only ledger change in the whole run was the deliberate dummy-row edit and restore, made by SQL as part of the test.
- **Reconciliation flags:** no flag on any ledger row changed.

---

## 6. Known limitations and unresolved issues

1. **"Posted — linked to a verified ledger entry" was shown in the cloud only.**
   - **Why:** posting was not authorised, so no ledger entry was created on the instance.
   - **What the instance did test:** a mismatching entry is refused.
   - **Cloud screenshot:** included below.
2. **The same-person guard for someone who holds both roles was tested in the cloud only.**
   - **Cloud:** an administrator cannot approve their own proposal.
   - **Instance:** the test logins have separated privileges, so the guard there was shown through role separation (proposer cannot accept; preparer cannot review).
3. **The two sessions on the instance used the same test login.**
   - **Instance:** two independent sessions of the same test login (TestAccounts, or TestFinApprover for reviews), in two tabs.
   - **Cloud:** the races used two different preparer logins.
   - The protections (claims, per-account lock, versions) do not depend on who the user is.
4. **"At the same time" means overlapping.** The requests overlapped in time; the server takes one change per account at a time, so the second waits and is then refused cleanly.
5. **Test-script issue on the instance (fixed; not a module defect).**
   - **What happened:** the first attempt at the 6–8 Sep demonstration requested `…/daily_bank_rec/` with a trailing slash. WAMP redirects that address, so the script had no security token, and the 5 changes it attempted were refused ("security token missing").
   - **Effect:** nothing was saved.
   - **Fix:** I corrected the script (kit updated). The cloud rehearsal passed again, 63/63, and the demonstration then passed on the instance. The audit keeps those refusals.
6. **A bank-charges correction that groups several days shows its full amount on each day it covers.**
   - **Example:** 4,550.00 shows on 1 Sep, although only 25.00 of it falls by that date.
   - **What is right:** the status (not posted).
   - **What is not:** the amount shown is the whole correction, not the part up to that date. It can be refined later.
7. **The old Bank Reconciliation screen is neither blocked nor detected.** Its flags (`bank_rec`, `bankst_flag`) are not part of the daily-record fingerprint, so its use would not flag a reviewed record. See D7 for the rule I recommend.
8. **The system daily USD rate is incomplete.**
   - **The gap:** your export has it on only 7 dates between 19 Aug and 7 Sep, and it records no source.
   - **Why it matters:** the reconciliation never borrows another day's rate, so a USD day with no rate shows "insufficient data".
   - See D6.
9. **What ran under your phpMyAdmin session (attribution).** These steps on the instance were run as SQL in phpMyAdmin under your signed-in session (MySQL `root`); there is no separate technical database login:
   - the schema upgrade;
   - loading the dummy data;
   - the deliberate edit and restore of dummy row 99980307;
   - writing the screenshots and results to `C:\wamp64\tmp\brc_r2_shots_20261012\`.

   The upgrade is recorded in the audit as `claude-technical (upgrade script run in phpMyAdmin; isolated test instance)`. The other SQL steps do not appear in the application's audit trail. They were done by me, not by you. Every change made through the application used the identifiable test logins.
10. **The instance is left in its post-demonstration state** so you can look at it.
    - **Back to before r2:** `sql/reset-demo-to-r1-snapshot.sql`.
    - **Remove r2 entirely:** `apply.py rollback` plus `sql/rollback-schema-v2-to-v1.sql`.
11. **Unchanged from r1:**
    - **Statement formats:** CSV and typed only.
    - **Daily records:** prepared on request.
    - **Phones:** wide tables scroll sideways.
    - **Not yet tested:** real bank formats, real volumes, and browsers other than Chromium-based ones.

---

## 7. Screenshots

Taken on the **isolated test instance** (red bar at the top), except the last one. Files are in `screenshots-r2\`.

**D10: an earlier row that cleared later does not explain the opening difference (before acceptance)**
![](screenshots-r2/00_before_d10_lkr_5sep_insufficient.jpg)

**D10: day 1 with the accepted opening item outstanding; opening difference explained**
![](screenshots-r2/01_d10_lkr_1sep_opening_item_outstanding.jpg)

**Pending postings on a reviewed day: "Reviewed — ledger not yet corrected — 4 approved but not posted"**
![](screenshots-r2/02_pending_postings_lkr_8sep_reviewed.jpg)

**D9: the timing item cleared by its match; who classified it and when are kept**
![](screenshots-r2/03_d9_lkr_timing_cleared_by_match_history.jpg)

**Corrections "Approved — not posted (ledger not corrected)", and the accepted opening item**
![](screenshots-r2/04_corrections_and_opening_items_states.jpg)

**Daily records keep what was not posted when prepared, and show it now**
![](screenshots-r2/05_daily_records_pending_postings_kept.jpg)

**Exchange-rate differences visible, each explained; revaluation shown and explained; reviewed with a pending posting**
![](screenshots-r2/06_fx_usd_5sep_differences_explained_reviewed.jpg)

**D9: the timing item in the suggestions ("accepting clears the classification"); the partial match with its outstanding rest**
![](screenshots-r2/07_d9_timing_item_in_suggestions_and_partial_match.jpg)

**D9: history after undo and reopening (cleared → restored → cleared)**
![](screenshots-r2/08_d9_history_cleared_restored_cleared.jpg)

**A matched ledger row changed after review: "Changed since review — re-review needed"**
![](screenshots-r2/09_changed_since_review_banner_ledger_row.jpg)

**Daily records: "re-review needed — changed: ledger rows"**
![](screenshots-r2/10_daily_records_rereview_needed.jpg)

**The reviewed snapshot is kept, beside the position now**
![](screenshots-r2/11_reviewed_snapshot_kept_rereview_needed.jpg)

**D10 across days: 2 Sep, both opening items outstanding**
![](screenshots-r2/12_d10_verification_2sep_both_opening_items_outstanding.jpg)

**D10 across days: 5 Sep, one cleared on 3 Sep, one still outstanding**
![](screenshots-r2/13_d10_verification_5sep_one_cleared_one_outstanding.jpg)

**A rate change after review flags the reviewed USD record**
![](screenshots-r2/14_rate_changed_after_review_rereview_needed.jpg)

**Overview for the reviewer**
![](screenshots-r2/15_overview_reviewer_ledger_not_corrected_flags.jpg)

**Cloud only: "Posted — linked to a verified ledger entry" (dummy posting inserted by the test)**
![](screenshots-r2/cloud_22_posted_linked_to_verified_ledger_entry.jpg)

---

## 8. Installation and rollback packages

Kit **`tf-bankrec-r2`** is in `C:\wamp64\www\avenir\Claude outputs\tf-bankrec-r2\` (updated today) and attached here.

**Approved for the isolated instance only.**

| Step | How |
|---|---|
| Check | `python apply.py check <avenir_rv root>`. Refuses any base URL other than `/avenir_rv/`, and any file that is not the r1 version. |
| Install files | `python apply.py apply <avenir_rv root>`. Backs up the r1 files to `before\<time>\`, writes 12 files, reads them back by md5. |
| Database | In phpMyAdmin, with `avenir_restore_verify_20261011` selected, run `sql/upgrade-v2-test-bed.sql`. It stops by itself on any other database or schema version. |
| Verification data (optional) | `sql/dummy-data-r2-verification.sql` |
| Roll back files | `python apply.py rollback <avenir_rv root>` |
| Roll back database | **Either** `sql/rollback-schema-v2-to-v1.sql` (removes the r2 columns and tables) **or** `sql/reset-demo-to-r1-snapshot.sql` (restores the demonstration exactly as it was before r2, from `sql/demo-snapshot-r1-20261012/`, and removes the verification account and its dummy rows). Both rehearsed on MySQL 8.0. |
| Results | `results\`: the cloud logs and the isolated-instance results (`results_rv_r2.json`) |

A development or live package would be built separately, after your approval.

---

## 9. Remaining decisions D1–D7, with my recommendations

| # | Decision | My recommendation | Blocks real-data trial? | Blocks live? |
|---|---|---|:-:|:-:|
| **D1** | Off-computer copy of the database export (it contains supplier bank details and customer data) | Copy it to an **external drive that you keep**. I do not upload it anywhere. | No | **Yes** |
| **D2** | Who prepares and who reviews | **As built:** <ul><li>Accounts prepares;</li><li>the Management Approver reviews daily records and decides corrections and opening items;</li><li>the Bank Authoriser and the Auditor view only.</li></ul>The system already refuses review or approval by the person who prepared or proposed. The Bank Authoriser marks payments Paid, so should not sign off their reconciliation. | Yes | **Yes** |
| **D3** | First day of reconciliation, per account | **1 October 2026** for each account, if September's ledger is final when we start. Otherwise the first day of the month after go-live. <br>D10 now handles cheques and receipts booked before that day: they are proposed and accepted as opening outstanding items. So the first day only needs the bank statement's opening balance and a complete ledger. | Yes | **Yes** |
| **D4** | Entering the real Avenir bank accounts | **You, or a named Accounts user**, enter each account in the master, and a different person confirms each ledger mapping. I do not enter or assume account numbers. | **Yes** | **Yes** |
| **D5** | Statement format | **CSV export from web banking.** Later, with your approval, one real CSV per bank (Commercial Bank, Pan Asia) for a format check on development. | Yes (one sample per bank) | **Yes** |
| **D6** | **Source of the daily rate** | See **D6 in detail** below. | Yes | **Yes** |
| **D7** | **The old Bank Reconciliation screen** (`accounts/bank_reconcil`; it writes `bank_rec` / `bankst_flag` on ledger rows) | **A rule now, a guard before live.** See **D7 in detail** below. | No (the rule is enough for a trial) | **Yes** (the guard) |

### D6 in detail: the daily-rate source

**How the system picks a rate today:**
1. The rate entered on **Rates & transfers** for that date and currency.
2. Otherwise, for USD only, the system's daily USD rate (`daily_usd_rate`) for that same date.
3. Never a rate from another date. Without a rate, the day shows "insufficient data".

**My recommendation: one published source, entered every business day.**

- **USD:**
  - **Which rate:** the published **USD telegraphic-transfer (TT) buying rate of the bank that holds the USD account**, for that business date. Use the first rate the bank publishes that morning.
  - **Who enters it:** Accounts, on Rates & transfers, in LKR per 1 USD, with the source written as "‹bank› TT buying ‹date›".
  - **Why the buying rate:** it is the rate at which the bank turns Avenir's dollars into rupees. The USD → LKR transfers are done at it, so transfer and revaluation differences stay small and explainable.
- **JPY:**
  - **Which rate:** the same bank's published **JPY TT buying rate**, entered **per 1 JPY** (if the bank quotes per 100 JPY, divide by 100), with its source.
  - **Why it must be entered:** there is no system JPY rate.
- **Weekends and holidays with entries:** enter the last published rate explicitly for that date, with the note "carried from ‹date›". The system will not carry a rate forward by itself.
- **The system daily USD rate:**
  - **Keep it as a fallback only if** you confirm it is taken from the same bank rate.
  - **Otherwise** I recommend switching the fallback off (a small change, needing your approval), so that every USD day uses a rate with a recorded source.
  - **Why:** in your export it exists on only 7 dates between 19 Aug and 7 Sep, with no source.
- **Month-end revaluation for the accounts:** this follows Accounts' own policy (for example, a rate your auditors prefer). It is posted by Accounts, not by this module.

### D7 in detail: keeping the old and new screens off the same periods

**The rule.** Once an account's ledger mapping is confirmed in the new workflow, its first day is the boundary for that bank ledger account:
- From the first day onward, it is reconciled **only in the new workflow**.
- The old screen is not used for that ledger account for any date on or after the first day.
- Dates before the first day stay with the old screen.

**Guard, recommended before live (needs your approval; not built):**
1. The old screen refuses to mark rows of a mapped ledger account dated on or after its first day, and points to the new screen.
2. The new screen warns if any row of a mapped account, dated on or after the first day, carries old-screen flags.
3. After one parallel month, the old menu is withdrawn for mapped accounts.

**Today's position.** In your export, none of the bank ledger rows dated June–November 2026 carry old-screen flags; only a few future-dated rows (dated 2030) do. Those are among the existing irregularities, and I left them untouched.

**Other decisions:**

| Decision | Status |
|---|---|
| **D8** clean-up | Not authorised; nothing was removed |
| **D9 / D10** | Implemented and verified on the test copies only; your decision is needed before they go to development |
| **D11** | Kept: development has no dummy data |
| **D12** live | Not now |

---

## 10. Other open decisions (unchanged, still outstanding)

| # | Decision |
|---|---|
| E1 | Mask Avenir's own paying account number to the last 4 digits for roles that do not pay |
| E2 | Older staff groups' view of full supplier bank numbers |
| E3 | InnoDB conversion of the MyISAM tables |
| E4 | Restrict the debtor-invoice amendment link to Accounts |
| E5 | Restrict `tour/print` to the roles that print tours |
| E6 | Retire `LEGACY_ALLOCATION_GROUPS` |
| E7 | The ledger imbalance of 449,143.20 and the provisional 34,495,229.47 difference |

---

## 11. Boundaries kept

| Boundary | What was done |
|---|---|
| Development system | Not changed: code r1, schema 1, no dummy data, ledger identical |
| Live | Not touched |
| Real accounts and statements | No real bank account entered; no real statement imported |
| Accounting entries | None posted |
| Test copies and backups | The isolated test instance, the restore-verify database, the recovery backups, the test copies and the withdrawn helper folder are all kept; nothing was deleted |
| Database export | Not uploaded anywhere |
| Separate projects | The Tour Payment Dashboard redesign and the Agent Inbox, Staff Performance and System Behaviour project were not touched |
