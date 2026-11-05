# Completion report: backup verification, definer repair, advance_delete secured (11 Oct 2026)

All of this work was done on the development system only. Nothing was installed on the live system. No real ledger entry, payment, supplier or tour was changed. Avenir's paying-account visibility and the older staff groups' access are unchanged, pending your separate decision. The restore-verify database is kept.

## A. Completed work

### 1. Step 1: backup verification finished

- **Why one count said 230.** phpMyAdmin repeats the column-header row inside its result table after every 100 rows. The list of 228 tables therefore came back as 230 rows, with headers at rows 101 and 202. My first copy run counted those two rows as tables, which is also where the two "undefined" errors came from. The database has **228 base tables** (143 InnoDB, 85 MyISAM), **13 views, 4 triggers and 57 foreign keys**. It has no stored routines and no events.
- **Name check.** Every object was compared by name between `avenir`, the backup `avenir_backup_20261011_post_r5` and the restored copy `avenir_restore_verify_20261011`. Nothing is missing and nothing is extra. The data checks reported earlier still stand: 373,092 rows, and checksums equal between the backup and the restored copy.
- **One correction to the backup.** The three `driver_allocation` triggers are stored with sql_mode `NO_ENGINE_SUBSTITUTION`, but my copies had an empty sql_mode. I recreated them in the backup with the original sql_mode and definer, so they now match `avenir` as it was before the repair. No data was touched. The backup record has an addendum about this.

### 2. An isolated test instance on the restore copy

- **Location.** `C:\wamp64\www\avenir_rv`, at `http://localhost/avenir_rv/`. It is a copy of the development code that connects **only** to `avenir_restore_verify_20261011`: every connection group is forced to it in `Database.php`.
- **Safeguards.** It has its own session and CSRF cookies, a red "ISOLATED TEST COPY" bar on every page, and no live keys (no SMS, WhatsApp, AI or e-mail).
- **Checked.** A sign-in on it wrote its session only to the restore database. The `avenir` sessions were untouched.
- **Notes.** `README-avenir_rv.txt` in that folder describes it and lists the dummy records.

### 3. Broken database definers repaired

**The cause.** Three views and three triggers were owned by `dbmasteruser@%`, a user that does not exist on the development server:
- views `acc_selct`, `account_runing_q2`, `account_runing_q21`;
- triggers `driver_allocation_after_insert`, `_after_update` and `_after_delete`.

**Tested on the isolated copy first:**
1. **Before the repair.** The three views failed with #1449. A real driver allocation save through the app answered "Failed to allocate driver", and the log shows the same #1449.
2. **The repair.** I recreated the six objects under `root@localhost`, the account the application itself connects as, which already owns the other 10 views and the voucher trigger. Only the definer changed: view SQL, security type, check option, trigger body, timing, event and sql_mode are all identical, compared by checksum. The triggers record `COALESCE(@current_app_user, USER())`, the connecting account, so the definer does not change what they write.
3. **After the repair, with dummy records.** Tour AH TEST-9901 and drivers TEST DRIVER A and B:
   - Allocating a driver works, and the INSERT audit row is written.
   - A duplicate allocation is still refused (409).
   - Removing a driver works, and the DELETE audit row is written.
   - An UPDATE writes its audit row.
   - **Crew replacement** on a dummy transport card works: driver A's line becomes "replaced" for 1–2 Dec, and driver B is engaged 3–5 Dec. Replacing the same line twice is still refused.
   - Transport & Guide Handling is still refused for both allocation and replacement.

**Applied to the development system:**
- The six objects were recreated the same way (root@localhost; SQL, sql_mode and timing unchanged).
- No `dbmasteruser` objects are left.
- The three views now read normally (1,174 / 11,355 / 11,355 rows).
- `driver_allocation` (629 rows) and its audit (1,786 rows) were not touched.
- **Rollback:** `definer-repair-rollback-20261011.sql` restores the original definitions byte for byte, checked by SHA-256 against what was read from the server. The post-R5 backup also holds them.

**Department permissions rechecked on the development system** after the repair, for all seven test logins:
- All seven land on the Pilot page.
- Only Operations allocates.
- Only the three requesting departments cancel requests.
- Only the Management Approver approves.
- Only Accounts initiates.
- Only the Bank Authoriser marks Paid.

This is the same as before the repair.

### 4. advance_delete secured (kit tf-ledger-guard-r1, installed on the development system)

`tour/tour_details_search/advance_delete` used to set `aflag = 1` on every leg of any ledger entry from a plain GET, for anyone holding Tour Search. It now works like this:

- **GET never changes a row.** The old address answers 405 and the attempt is recorded.
- **Prepare step, read-only.** `…/advance_delete/prepare?id=` returns:
  - the entry's rows;
  - whether the entry may be flagged, and why not;
  - the confirmation phrase `FLAG <id>`;
  - CodeIgniter's CSRF token.
- **POST to `…/advance_delete`.** The change itself. It requires:
  - an authorised Accounts user: the administrator, or a profile holding the "accounts" menu;
  - a valid CSRF token, sent in a form field or in the X-CSRF-TOKEN header;
  - the typed confirmation `FLAG <id>`;
  - a reason of at least 5 characters;
  - a tour-advance entry: a leg on 6-11-107-10381, the account the advance list reads, with every leg still unflagged. Voucher entries and other entries are refused.
- **Audit:**
  - A success is recorded as `ledger_entry_flagged`, with the user, time to the millisecond, the entry, the reason, IP, every row before and after, and the number of rows changed.
  - A refusal is recorded as `access_denied` or `ledger_flag_refused`.
- No screen in use called the old address; only an unused "- Copy" view did.

## B. Test results

| Test | Where | Result |
|---|---|---|
| Regression: R4 suite / R5 bulk / Pilot / Operations-confidentiality / r3 | cloud test copy, with tf-ledger-guard-r1 | 202/202 · 71/71 · 36/36 · 33/33 · 26/26 |
| advance_delete tests, dummy entries | cloud test copy | 10/10 |
| Definer failure reproduced (views #1449, allocation "Failed") | isolated copy | confirmed |
| Allocation save, duplicate refusal, remove, update, with dummy tour and drivers | isolated copy, after the repair | all pass; INSERT / DELETE / UPDATE audit rows written |
| Crew replacement and second-replacement refusal, dummy card | isolated copy | pass |
| Transport & Guide Handling refused allocation / replacement | isolated copy | pass (403, recorded) |
| Definer repair: only the definer changed | isolated copy and development | identical SQL / body / timing / sql_mode |
| Seven logins: landing and permissions after the repair | development | unchanged |
| advance_delete matrix (6 other departments: GET 405, prepare 403, POST 403; Accounts: no token 403, wrong token 403, no or wrong confirmation 400, short reason 400, non-advance 409, success 200, repeat 409) | isolated copy, dummy entries 99990001/99990002 | all as designed; 26 refusals + 1 success recorded with before/after |
| Real ledger untouched during tests (11,364 rows, checksum and aflag total equal before and after, and equal to `avenir`) | isolated copy and development | pass |
| Installed on development: read-only probes (GET 405; prepare shows the rows and "not eligible" for a voucher entry; POST without token 403; wrong confirmation 400; Tour Reservations 403) | development | pass; ledger checksum unchanged; **no successful change made on development** |

Kit `tf-ledger-guard-r1`:
- `apply.py check`: 2 files, no drift.
- `apply`: verified.
- Rollback was rehearsed and restores both files identically.
- The installer's backup is in `Claude outputs\tf-ledger-guard-r1\before\20261011-122201`.

## C. Unresolved issues and observations

1. **An allocation that "failed" before the repair was in fact saved.** On the isolated copy, the save that answered "Failed to allocate driver" had already inserted its row. `driver_allocation` is MyISAM and cannot roll back, so the row stayed but no audit row was written. On the development system nothing was lost: all 629 allocations have their audit row, and the data stops at 8 Sep. Worth knowing for the InnoDB decision.
2. **No tour advances in the development ledger.** The development ledger has no rows on the advance account 6-11-107-10381, so the guarded feature has nothing to act on in the development data. The live data may differ.
3. **The trigger records the database account.** The audit column `changed_by` records the database account (`root@localhost` here), not the Avenir user, unless the app sets `@current_app_user`; it never does. This existed before my work.
4. **Ledger rows with impossible or future dates** were found while inspecting. See the Step 2 note; they are not changed.
5. **Still open:**
   - the Avenir paying-account visibility and the older staff groups' access, which await your decision;
   - the ledger imbalance of LKR 449,143.20;
   - the earlier list: the legacy "saved" message, collation, the console error, the voucher-cancel 500, `tour/print`, and `LEGACY_ALLOCATION_GROUPS`.
6. **Kept for now, removal for your approval later:**
   - the restore database `avenir_restore_verify_20261011`, with dummy records only;
   - the test folder `C:\wamp64\www\avenir_rv`.

## D. Files (Claude outputs)

- `tf-ledger-guard-r1.zip` and its folder (installed, with tests and evidence).
- `definer-repair-rollback-20261011.sql`.
- `post-r5-database-backup-record-2026-10-11.md`, with the addendum.
- This report.
- `README-avenir_rv.txt`, inside `C:\wamp64\www\avenir_rv`.
