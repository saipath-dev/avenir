# Bank Reconciliation r3 — development stage report (12 Oct 2026)

**Summary.** I installed r3 and the sign-out fix on **development only** and checked them there. **Live was not touched.**

- No real bank account, mapping, first day, statement or accounting entry was created, changed or deleted.
- The ledger is unchanged.
- The mappings, the first days and the two old tools are waiting for your approval.
- **I have stopped here.**

Times are Sri Lanka time.

---

## 1. Exactly what I changed (development, `C:\wamp64\www\avenir`, database `avenir`)

| Change | Detail |
|---|---|
| **Daily Bank Reconciliation r1 → r3** (kit `tf-bankrec-r3-dev`) | **13 files replaced, 1 added:**<br>• `Routes.php`<br>• `DailyBankRecController`<br>• `BankReconsillationController` (the old-screen guard)<br>• the BankRec libraries `Corrections`, `Matching`, `Rec`, `Schema`, `Statements`, and `LegacyGuard` (new)<br>• the old screen's view `bank_reconcillation.php` (it now shows the refusal reasons)<br>• the views `_common`, `account`, `fx`, `index`<br>These are the same files, byte for byte, that passed on the isolated instance. |
| **Database schema v1 → v2** | Guarded script: it runs only on database `avenir` at version 1.<br>• 2 new tables: `brc_claim`, `brc_mark_event`<br>• new columns and keys on the reconciliation tables (`brc_…`), which are all empty<br>• `brc_schema_version` 1 → 2<br>No other table was altered. |
| **Sign-out fix** (kit `tf-session-r1b`) | `LoginController`, `UserSessionFilter`, `plugins.js`, `layouts/scripts.php` |
| **Version mark (cache-busting)** | Pages now load `plugins.js?v=<the file's modification time>` (currently 1791793440), so a changed `plugins.js` loads at once, without Ctrl+F5.<br>**Limit:** the mark covers `plugins.js` only. Other shared scripts (e.g. `app.js`) have no mark yet. Adding it to them is a small change to `scripts.php`, for your approval. |

**Not changed:**

- No mapping, first day, bank account, login, role or privilege was created or changed. Until mappings are confirmed, the guard protects nothing.
- Test scripts were served for a while from the local-only helper folder. They have been moved back out, and the folder holds only its 3 original files.

## 2. Backups and rollback

| Backup | Where | Status |
|---|---|---|
| **Files** | `C:\wamp64\www\avenir-backup-20261012-before-r3\` (`app\` + `public\`, md5 list of 2,481 files) | Kept |
| **Database** | Full copy `avenir_backup_20261012_before_r3` (239 tables, checksums equal to development at copy time, sessions aside) | Kept |
| **Replaced files** | `tf-bankrec-r3-dev\before\20261012-083346\`, `tf-session-r1b\before\20261012-083352\` | Kept |

**Rollback was tested on development:**

- Both kits were rolled back, plus the schema script v2 → v1.
- The files were then md5-identical to the backup.
- The database was identical to the backup, apart from logs, sessions, audit rows and the automatic hotel scan.
- Then I installed both kits again.

**Rolling back takes 3 steps:**

1. `apply.py rollback` for each kit.
2. The rollback SQL in phpMyAdmin.
3. Check the result as described in each kit's README.

## 3. Test results

| Where | What | Result |
|---|---|---|
| **Development** | Checks that need no reconciliation data:<br>• who may see the pages and data (preparer, reviewer, view-only; four other departments refused)<br>• every action refused by role before anything is written<br>• a request without the security token refused<br>• the r3 routes are in place<br>• the old screen opens and carries the r3 messages<br>• the version mark<br>Run after the first install, and again after rollback and re-install. | **18/18, 18/18** |
| **Development** | Sign-out fix, TEST login:<br>• a background request without a session gets a quiet 401, with no redirect<br>• a page request without a session goes to the sign-in page<br>• the sign-in page keeps a valid session<br>• after sign-out, requests are refused quietly | **5/5** |
| Development | **Concurrency** (two users at the same moment) | **Not run.** It needs reconciliation records; development has none, and dummy data is not allowed there (D11). |
| Development | The red "session ended" notice | **Not run here.** The browser pane blocks the page's scripts on 127.0.0.1, and the localhost tab holds your own session. It passed on the isolated instance and in the cloud. |
| Isolated instance (same code) | r3 verification earlier today | 38/38 browser, 16/16 database, races 2/2 |
| Cloud copy (identical code) | • regression 202 / 71 / 36 / 33 / 26 / 10<br>• r2 suite 108/108, r2 screens 20/20<br>• r3 suite 132/132 (includes true parallel races)<br>• r3 screens 24/24<br>• sign-out fix 18/18<br>• version mark 4/4 | **All pass** |

*Offer:* I can run the concurrency tests on development with temporary TEST records and remove them afterwards. I will do this only if you approve.

## 4. Ledger and data integrity

At 14:14 I compared every table of development with the copy taken before the install.

- **Ledger `account_runing`: unchanged.** 11,364 rows; Dr 965,763,907.13; Cr 965,314,763.93; the reconciliation flags are unchanged. The table checksum is identical.
- **The old screen's statement table `account_runing_bank_statetment`: unchanged.**
- **The reconciliation tables (`brc_…`):** empty before and after.
- **230 tables are identical.** 2 tables are new (the schema).
- **9 tables differ, all for expected reasons:**

| Table | Why it differs |
|---|---|
| `tpd_meta` | Schema version 1 → 2 |
| `tpd_audit` | 3 install / rollback / re-install rows, plus refusals recorded for the TEST logins |
| `a_log`, `user_sessions`, `ci_sessions` | Page visits and sign-ins of the TEST logins, and session records |
| `hr2_meta`, `hr2_work_item` | The system's automatic hotel work-item scan |
| `tf_activity`, `tf_intake` | One edit of inquiry 21 by "super admin" at 13:56 — your own work, not mine |

## 5. Account mappings and first days — for your approval (nothing applied)

**Before any mapping, the bank accounts must be entered (D4).**

- The bank-account master on development is **empty**.
- Accounts must enter the real accounts (bank, branch, account number). I do not enter account numbers.
- Each account is then mapped to its ledger account and given a first day.

| Ledger account (as named in the system) | Activity on development | Recommendation |
|---|---|---|
| Com Bank LKR Account `1-6-8-10380` | 1,844 rows, 27 Jan – Sep 2026 (192 in Sep) | **Map. First day 1 Oct 2026** |
| Com Bank USD Account `1-6-8-10898` | 23 rows, Apr – Sep 2026 | **Map. 1 Oct 2026.** LKR equivalents need D6 |
| Pan Asia Bank LKR Account `1-6-8-10998` | 32 rows, 9 Apr – 25 Aug 2026 | **Map. 1 Oct 2026** |
| Pan Asia Bank USD `1-6-8-10999`, YEN `1-6-8-11000` | No rows | **Wait until they are used.** YEN also needs a rate source (D6) |
| Bank Control `1-6-8-9750`, Deposits in Transit `1-6-112-10782`, Advance deposit `1-6-112-11024` | — | **Do not map.** These are not bank accounts with statements; Deposits in Transit is the old screen's unrealised account. |

**First day.** 1 Oct 2026 works for an account only if all three of these hold:

- September is final in the ledger for that account;
- the bank's closing balance on 30 Sep is available;
- the opening difference is understood (E7).

Dates before the first day stay on the old screen. If these conditions are not met, use **1 Nov 2026**. 1 Sep 2026 is also possible, if you want September's difference worked through in the new workflow.

**Rows for Accounts to check.** I left them unchanged. Inside a protected period they would appear as warnings.

- `1-6-8-10380`:
    - rows dated 24 Oct and 13 Nov 2026 (after today);
    - one row dated 1 Jan 2030 that carries old-screen flags;
    - two rows dated 16 Apr 2525.
- `1-6-8-10898`: one row dated 24 Oct 2026.
- Bank Control: rows dated 20 Mar 2029 and 1 Jan 2030.

**Development is a copy and may differ from live.** These figures need a read-only recheck on live before you decide.

## 6. The two old tools that are not guarded (nothing changed)

|  | **A. Settings → Manual Update → "Bank Not Reconciliation Record Insert"** | **B. Accounts → "Bank Req Data Transfer"** (`accounts/bank_unrealised_value`) |
|---|---|---|
| **What it does** | Copies **286 fixed ledger rows** (chosen by row ID) from the 2024 database into the current ledger, under new IDs | For one chosen bank account: copies its unreconciled FY 2024/25 entries from the 2024 ledger table into the ledger, and **subtracts them from the "Brought Forward Balance 2025-03-31" rows** |
| **Risk** | • One click, with no confirmation and no security token<br>• **Each click adds the rows again**<br>• On development the 2024 database does not exist, so it would fail; on live it would run | • No security token<br>• **A second run copies and subtracts again** |
| **Who can use it (development)** | System Administrator (1 user) and ticketing_admin (3 users) | System Administrator (1 user) |
| **Why r3 does not stop it** | r3 guards only the old Bank Reconciliation screen, and only rows dated from the first day. This tool writes rows dated 2024/25. | The same: it writes 2024/25 rows and changes the 31 Mar 2025 brought-forward balance. That shifts the balance the daily reconciliation starts from. |
| **Recommendation** | **Retire this one action:**<br>• the server refuses it, with a message and an audit record;<br>• the button is removed;<br>• the rest of Manual Update stays as it is.<br>(Taking the Manual Update menu away from ticketing_admin would also remove its other functions.)<br>Before that, check on live, read-only, whether it was already run. | **Ask Accounts whether it has been run on live for each bank.**<br>• If done: retire it the same way.<br>• If still needed: run it once, under supervision, **before** that account's first day is confirmed, then retire it. |

**Protection for both tools (my recommendation):**

- Same guard (`LegacyGuard`) and same refusal style as the old screen.
- Refused for any account with a confirmed mapping, **whatever the date**.
- Refused on a second run.
- Security token required.
- Every attempt audited.

After your approval I would build this on development and test it before live.

## 7. D1–D7 and E1–E7 — all still open

| Point | Status after this work |
|---|---|
| D1 off-computer copy of the export | Open |
| D2 who prepares, who reviews | Open |
| D3 first day per account | Open — recommendation in §5 |
| D4 real bank accounts entered, mappings confirmed | Open — the master is empty on development |
| D5 statement format | Open |
| D6 daily-rate source / exchange-rate policy | Open — needed for USD and YEN |
| D7 old screen overlapping the new workflow | Open. Progress:<br>• the guard is **installed on development**, but inactive (no confirmed mapping)<br>Still to do:<br>• the old tools (§6)<br>• the live install<br>• withdrawing the old menu after a parallel month |
| E1–E7 | All open; this work does not affect any of them:<br>• E1 masking Avenir's paying account<br>• E2 older groups' view of supplier bank numbers<br>• E3 InnoDB<br>• E4 debtor-invoice amendment link<br>• E5 `tour/print`<br>• E6 `LEGACY_ALLOCATION_GROUPS`<br>• E7 the 449,143.20 imbalance and the provisional 34,495,229.47 difference |

## 8. Still blocking live

1. **Your decisions on D2, D3, D5 and D6**, and the mappings and first days (§5).
2. **The real bank accounts entered by Accounts (D4).**
3. **E7 understood** for each account's opening balance.
4. **The two old tools** decided, then guarded or retired, and tested (§6).
5. **D1:** the off-computer copy.
6. **Preparing live:**
    - a read-only check of live's files and schema;
    - fresh file and database backups;
    - a live kit built and rehearsed for live's current files (the development kits refuse live);
    - **your explicit approval.**
7. **After go-live:** one parallel month before the old menu is withdrawn (D7).

**Optional, not blocking:**

- concurrency tests on development with temporary TEST records;
- the version mark on the other shared scripts.

---

**Files** in `Claude outputs\`:

- **Kits:**
    - `tf-bankrec-r3-dev\` and `tf-session-r1b\`
    - each kit has `results\development\DEVELOPMENT-RESULTS.txt`
    - `tf-bankrec-r3-dev\` also has `results\cloud\`
- **Zips** (without the `before\` backups):
    - `tf-bankrec-r3-dev-20261012.zip` (md5 6b797a194999a8cb985f78b4ee0cfe69)
    - `tf-session-r1b-20261012.zip` (md5 239d8ec378e3a7e73ce26c54d14be636)
