# Backup record: post-R5 database backup, development system (11 Oct 2026)

**This is a POST-R5 backup.** It holds the development database after tf-paydesk-r5 (schema 7) and tf-pilot-r1, r2 and r3 were installed. It is not a pre-installation backup. The pre-installation backups are the earlier ones listed at the end of this record; they were not touched.

| | |
|---|---|
| Server | Development system (WAMP, `C:\wamp64\www\avenir`), MySQL 8.4.7, local phpMyAdmin 5.2.3, signed in by Shantha |
| Source database | `avenir` |
| Backup | **`avenir_backup_20261011_post_r5`** (utf8mb4 / utf8mb4_unicode_ci, 164.7 MB) |
| Restore check | `avenir_restore_verify_20261011`, an isolated copy restored from the backup (164.7 MB) |
| Taken | 11 Oct 2026, in two sittings because the computer slept in between: 16:23–16:27, then 17:0x. Completeness was confirmed against `avenir` at 17:15 (server time). |
| Made by | Claude, through phpMyAdmin's SQL interface. Nothing in `avenir` was written. |

## Method

1. Every base table (228) was created with `CREATE TABLE … LIKE` and filled with `INSERT … SELECT`. Foreign-key checks were off while loading.
2. AUTO_INCREMENT counters were set to the source's values (205 tables).
3. The 57 foreign keys were added from the source's definitions.
4. The 13 views were recreated with their original definers and SQL security, pointing at the backup's own tables.
5. The 4 triggers were recreated with their original definers and bodies, byte for byte.

phpMyAdmin's "Copy database" could not be used: it stops at the view `account_runing_q2`, whose definer `dbmasteruser@%` does not exist on this server.

## Verification

| Check | avenir | backup | restored copy |
|---|---:|---:|---:|
| Tables | 228 | 228 | 228 |
| Rows (exact count, every table) | 373,092 | 373,092 | 373,092 |
| CHECKSUM TABLE | 226 equal the backup* | 228 equal the restored copy | 228 equal the backup |
| Columns, signature | 4,416 | identical | identical |
| Indexes, signature | 641 | identical | identical |
| Foreign keys, signature | 57 | identical | identical |
| Views (definitions and definers) | 13 | identical | identical |
| Triggers (bodies and definers) | 4 | identical | identical |
| AUTO_INCREMENT counters | 205 | identical | identical |
| Ledger `account_runing`: rows / ΣDr / ΣCr | 11,364 / 965,763,907.13 / 965,314,763.93 | same | same |
| `voucher`: rows / highest PV | 7,975 / 8020 | same | same |
| `payment_request` / `tpd_audit` rows | 3,110 / 245 | same | same |
| `tpd_meta` schema_version | 7 | 7 | 7 |

\* `ci_sessions` and `user_sessions` have the same rows in `avenir` and the backup. Only the "last activity" time of signed-in sessions moved on after the copy (latest 17:15:39 in `avenir`, 17:06:39 in the backup), so they are not data changes.

## Notes

- To restore, copy `avenir_backup_20261011_post_r5` back the same way. The restore check above proves that path.
- The isolated restore copy `avenir_restore_verify_20261011` can be kept or removed; that is your decision.
- Three views (`acc_selct`, `account_runing_q2`, `account_runing_q21`) and the three `driver_allocation` triggers carry the definer `dbmasteruser@%`, which is missing on the development server. They were copied exactly as they are.
- Existing backups were left untouched:
  - the database `avenir_backup_20261011`;
  - the app folder copy `C:\wamp64\www\avenir-backup-20261011-before-r5\`;
  - the installer backups under each kit's `before\` folder;
  - the before-R4 backups.

## Addendum (11 Oct 2026, later)

**Why one count said 230.** phpMyAdmin repeats the column-header row inside its result table after every 100 rows. A 228-row list therefore came back as 230 rows, with headers at positions 101 and 202. My first copy run counted those two header rows as tables. That is also where the two "undefined" table errors came from. The database has 228 base tables: 143 InnoDB and 85 MyISAM. It also has 13 views, 4 triggers, 57 foreign keys, no stored routines and no events. A name-by-name check found every one of them in both the backup and the restored copy, with nothing missing and nothing extra.

**Trigger sql_mode corrected in the backup.** The three `driver_allocation` triggers in `avenir` were stored with sql_mode `NO_ENGINE_SUBSTITUTION`. My copies had picked up the session's empty sql_mode instead. They were recreated in the backup with `NO_ENGINE_SUBSTITUTION` and the original definer `dbmasteruser@%`. The backup's triggers now match the pre-repair state of `avenir` in definer, body, timing and sql_mode. No data was touched.

**The restored copy is now a test bed.** `avenir_restore_verify_20261011` was used afterwards to test the definer repair and the advance_delete hardening, with dummy records only. It therefore no longer equals the backup:
- it has the repaired definers;
- it has a dummy tour, drivers, transport card, crew lines and ledger entries;
- it has the test audit rows.

The backup itself is unchanged, apart from the trigger sql_mode correction described above.

## Addendum 2: roles of the backups and an independent export (11 Oct 2026, evening)

### What each copy is for

| Copy | Role |
|---|---|
| `avenir_backup_20261011` (database) | Pre-R5 backups: untouched and kept as they are. Last change to `avenir_backup_20261011` was 06:56 on 11 Oct. |
| `C:\wamp64\www\avenir-backup-20261011-before-r4\` (6,166 files) and `…-before-r5\` (982 files) | Pre-R5 backups: untouched and kept as they are. Newest files: 01:13 and 06:30 on 11 Oct. |
| `avenir_backup_20261011_post_r5` (database) | **The verified post-R5 recovery backup.** Used only for recovery. |
| `avenir_restore_verify_20261011` (database) | **Not a recovery source.** It is now a test bed for the isolated test instance, with dummy records and changed definers. Kept for now; its removal is for your approval. |
| `C:\wamp64\tmp\avenir-exports-20261011\avenir_backup_20261011_post_r5.sql.gz` | Independent export of the post-R5 recovery backup (below). |

### The independent export

- **Source.** `avenir_backup_20261011_post_r5`, exported with phpMyAdmin's own Export (SQL, structure and data) and compressed.
- **Size.** 12,054,295 bytes (178,106,063 uncompressed). SHA-256 `3d4cf936…a0dbb8`.
- **Location.** `C:\wamp64\tmp\avenir-exports-20261011\`, outside the web folder, with its manifest (row count and checksum of every table) and `README-export.txt`, which explains how to restore it.

**Restore check.** The export was restored on a separate MySQL 8.0.46 server, not on this computer's databases. Compared with the backup:

| Check | Result |
|---|---|
| Base tables | 228 |
| Rows | 373,092, every table equal |
| CHECKSUM TABLE | equal for all 228 tables |
| Views (definitions and definers) | 13, identical |
| Foreign keys | 57 |
| AUTO_INCREMENT values | 205, identical |
| Ledger | 11,364 rows, ΣDr 965,763,907.13, ΣCr 965,314,763.93 |
| Vouchers | 7,975, highest PV 8020 |
| Triggers | 4, same executable code |

phpMyAdmin's export makes two small changes to the triggers. Neither changes behaviour, and the original text stays in the guarded emergency record:
- they are recreated under the account that restores the file;
- a SQL comment inside three trigger bodies is dropped.

**Not yet independent.** The export protects against computer or disk failure only once a copy is kept off this computer. Where it is kept is your decision.
