# tf-paydesk-r5 — Decision A: MyISAM → InnoDB for the four voucher tables

Backup, migration, compatibility and rollback plan, with the rehearsal evidence from the cloud test copy (11 Oct 2026). **Nothing has been converted on the development system.** This plan is for approval; the conversion itself waits for it.

## 1. What would change, and what would not

| | Today (development database) | After the conversion |
|---|---|---|
| `voucher` (7,975 rows, 2.5 MB) | MyISAM | InnoDB |
| `v_inv` (8,178 rows, 1.7 MB) | MyISAM | InnoDB |
| `invoice_no` (1 row — all document counters) | MyISAM | InnoDB |
| `payment_request` (3,110 rows, 0.6 MB) | MyISAM | InnoDB |
| `account_runing` (the ledger, 11,364 rows) and the other 80 MyISAM tables | MyISAM | **unchanged** (see §6) |
| Columns, indexes, primary keys, auto-increment values, the voucher-number unique index and the guard trigger | | **identical** (verified row-for-row and structure-for-structure in the rehearsal) |
| Every row of every table | | **identical** (fingerprinted before and after) |
| Application code | | **unchanged** — the four tables are read and written through the same SQL; no MyISAM-only feature is used anywhere in the application (no `LOCK TABLES`, `INSERT DELAYED`, FULLTEXT or spatial index) |

Why these four: they are the tables one voucher issue writes (`voucher` row, `v_inv` lines, `invoice_no.V_Num`, `payment_request.voucher_created`). With them on InnoDB a batch of N vouchers is one transaction: either all N exist or none.

## 2. Rehearsal evidence (cloud copy of the post-suite test database, MariaDB 10.11; the development system is MySQL 8.4.7 — same statements, same behaviour)

| Step | Result |
|---|---|
| Fingerprints before (count + MD5 of every row, every column) — voucher, v_inv, invoice_no, payment_request, account_runing, tpd_batch, tpd_bank_approval, tpd_request_ext, tpd_audit | recorded |
| `ALTER TABLE … ENGINE=InnoDB` ×4 | 0.01–0.02 s each on the test copy; expected well under 5 s each on the development tables |
| Fingerprints after | **identical, all nine tables** |
| Structure after (columns, indexes, trigger, auto-increment) | **identical apart from the engine clause** |
| Voucher-number guard on InnoDB: renumbering an issued voucher | refused: "Payment Voucher No. (Vnum) cannot be changed once issued" |
| Voucher-number guard on InnoDB: inserting a duplicate number | refused: `Duplicate entry … uq_tpd_voucher_vnum` |
| **Transaction proof** — a 3-voucher run whose third voucher reuses an issued number, then ROLLBACK, on the InnoDB copy | **nothing remained**: voucher / v_inv / counter 616 / 622 / 617 before and after |
| The same run on a MyISAM copy (as the development database is today) | **vouchers 618 and 619, their two lines and the advanced counter (619) remained** — the partial batch the requirement forbids |
| Full tf-paydesk-r4 test suite (tests 0–46) run with the four tables on InnoDB | **202 of 202 checks passed** — requests, approvals, Supplier Payables, batches, voucher issue, bank initiation, Paid, FX and bank-charge postings, reconciliation, documents, remittance, repeated/concurrent Paid, counter drift |
| `ALTER TABLE … ENGINE=MyISAM` ×4 (the rollback) | 0.01–0.02 s each |
| Fingerprints after the rollback | **identical to the start** |
| Structure after the rollback | identical except two auto-increment counters consumed by the proof's own inserts (InnoDB does not reuse an internal row id taken by a rolled-back insert; the **voucher number** comes from `invoice_no`, which is rolled back, so voucher numbering stays gap-free) |

Scripts and logs: `engine_rehearsal.sh`, `engine_rehearsal.log`, `fp_before.txt` / `fp_innodb.txt` / `fp_back.txt`, `struct_*.txt`, `results_innodb_r4suite.json`, `flow_run_innodb.log` (in the r5 evidence folder).

## 3. Pre-checks on the development system (read-only, before anything runs)

1. `SELECT VERSION()`; `SHOW ENGINES` shows InnoDB = DEFAULT; `innodb_file_per_table = ON` (MySQL 8 default).
2. The four tables: engine, row counts, sizes; no FULLTEXT/spatial indexes (already confirmed: none); no foreign keys pointing at them (none exist in this schema).
3. Free disk space ≥ 10 × the four tables' size (≈ 50 MB) plus the backup.
4. No one signed in to the application (`user_sessions`), WAMP services otherwise idle; the Apache PHP opcache does not matter (no code changes).
5. `invoice_no.V_Num` = `MAX(voucher.Vnum)` (the r4 preflight) — the conversion does not fix a counter that is behind.
6. Backup from §4 completed and verified.

## 4. Backup (before), restore (if needed)

- **Files:** none change. (The `avenir-backup-20261011-before-r4` folder already exists.)
- **Database:** a fresh server-side copy of the four tables *and* a full copy of `avenir`, both verified: `CREATE TABLE avenir_backup_<stamp>.voucher LIKE avenir.voucher; INSERT … SELECT` for the four tables, then `CHECKSUM TABLE` and row counts equal; plus the phpMyAdmin "Copy database" of the whole `avenir` as done for r4 (164 MB, ~2 minutes).
- **Restore (worst case):** `RENAME TABLE avenir.voucher TO avenir.voucher_failed, avenir_backup_<stamp>.voucher TO avenir.voucher` (and the other three) — seconds, no data typed by hand. The r4 rollback test's fingerprint method verifies the restore.

## 5. Migration procedure (the approved day, with no one signed in)

```
-- 1. fingerprints before (count + MD5 of all rows, ordered by primary key) for the 9 tables of §2  — saved to the report
-- 2. conversion, one table at a time, each verified before the next
ALTER TABLE voucher         ENGINE=InnoDB;   -- expected < 5 s
ALTER TABLE v_inv           ENGINE=InnoDB;
ALTER TABLE invoice_no      ENGINE=InnoDB;
ALTER TABLE payment_request ENGINE=InnoDB;
-- 3. verification: fingerprints after = before; SHOW CREATE TABLE differs only in ENGINE; trigger present;
--    the guard refuses a renumbering and a duplicate (on a throw-away copy row, inside a transaction that is rolled back);
--    the application pages render (dashboard, Supplier Payables, Bank Payment Check, Voucher Report, Complete Tour Record)
-- 4. the r4 on-machine checks (Supplier Payables listing, Settings, a read of each legacy page) and the preflight (counter in step)
```
Each `ALTER` locks only its own table for the fraction of a second it takes; nothing else is touched. The whole procedure, with verification, is under 15 minutes; the conversion itself under one minute.

## 6. Compatibility

- **Application:** verified by the full r4 suite on InnoDB (202/202) and by rendering the legacy pages. The legacy code's read-then-write counter (`SELECT V_Num` … `UPDATE`) behaves the same; the r4 named lock and the unique index remain the duplicate protection for every writer.
- **Mixed engines:** a statement that touches an InnoDB table and a MyISAM table (e.g. a voucher issue that also posts to `account_runing`) still runs; only the InnoDB part is transactional. r5 is designed around this (§7).
- **Reports and year-end copies:** `account_runing_2024`, `voucher_2024`-style copy tables are not touched; `CREATE TABLE … LIKE` of a converted table produces an InnoDB copy, which is harmless.
- **Row counts:** `information_schema.TABLES.TABLE_ROWS` is an estimate on InnoDB; nothing in the application reads it (confirmed by search).
- **Backups:** phpMyAdmin export/import and "Copy database" handle InnoDB tables identically.
- **Live server (later):** its MySQL/MariaDB version and settings must be checked with the same pre-checks before this is repeated there; nothing in this plan assumes the development machine.

## 7. The ledger stays on MyISAM — remaining risk, stated plainly

`account_runing` keeps today's engine in this plan. Consequences for r5:
- Voucher issue (the "create none if any fails" requirement) is fully atomic — it writes only the four converted tables.
- **Paid** writes the ledger: the payment posting, the exchange-difference pair and the bank-charge pair per voucher. These writes cannot be rolled back if the process stops half-way (a killed connection, a server restart). r5 therefore makes them **idempotent and resumable**: every entry carries the voucher number and the batch id; Paid re-run posts only what is missing; the batch is marked Paid only when the reconciliation passes (Σ credits to the paying account = the bank debit, every entry balanced, FX and charges present for every voucher). A half-posted batch is visible as "Paid — postings incomplete, re-run" and can never be reported as reconciled.
- Legacy pages that post to the ledger (authorisation, cancellation, journal entries) behave exactly as today.
- Converting `account_runing` as well would make Paid one transaction too. It is the central table of every accounting report, so it is a separate decision with the same rehearsal method; the pre-existing irregularities in it (the "0" account debit, unbalanced and future-dated entries) are unaffected either way and stay documented separately.

## 8. Rollback of the conversion

`ALTER TABLE … ENGINE=MyISAM` for the four tables (rehearsed: data and structure identical afterwards). Two points to know: it must run when no voucher is being issued (the r4 named lock makes that easy to check); and any InnoDB-only behaviour r5 relies on (the transaction) would be lost — r5's code detects the engine at start-up and refuses bulk issue with a clear message instead of pretending to be atomic.

## 9. Decision requested

Approve the conversion of `voucher`, `v_inv`, `invoice_no`, `payment_request` on the development system, following §3–§5, with the ledger left as it is (§7) — or defer it, in which case r5's bulk issue keeps the r4-style verified-write-and-undo behaviour and §7's wording applies to voucher issue as well.
