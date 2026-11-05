tf-bankrec-r3 for DEVELOPMENT (http://localhost/avenir/, database avenir), 12 October 2026. Approved by Shantha for the
development system only. Live is refused by apply.py and needs a separate approval.

Takes development from tf-bankrec-r1 straight to r3 (the r2 code and schema step + the r3 changes, as verified on the
isolated test instance). Files: 13 changed + LegacyGuard.php added (SOURCE-MD5.txt; the same files, byte for byte, as the
isolated instance runs — r3 kit + the r2 files r3 did not change).

Install
  1. python3 apply.py check  <C:\wamp64\www\avenir>   (refuses any other base URL than http://localhost/avenir/)
  2. python3 apply.py apply  <C:\wamp64\www\avenir>   (backs up the 13 r1 files to before/<time>/)
  3. phpMyAdmin, database avenir: sql/upgrade-schema-v1-to-v2-development.sql (guarded: database avenir, schema 1)
Roll back
  1. python3 apply.py rollback <C:\wamp64\www\avenir>   (the r1 files back; LegacyGuard.php taken out)
  2. phpMyAdmin, database avenir: sql/rollback-schema-v2-to-v1-development.sql
  Complete backups taken before the install: C:\wamp64\www\avenir-backup-20261012-before-r3\ (app\ + public\, md5 list)
  and the database copy avenir_backup_20261012_before_r3.
No mapping, first day, bank account or role is created by this kit: the guard protects nothing until mappings are confirmed.
Never writes to the ledger (account_runing) or its reconciliation flags; never posts an entry.
