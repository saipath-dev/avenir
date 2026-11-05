tf-bankrec-r2 — Daily Bank Reconciliation r2, 12 October 2026. APPROVED FOR THE ISOLATED TEST INSTANCE ONLY
(http://localhost/avenir_rv/, database avenir_restore_verify_20261011). Not for the development system or live.

What r2 changes (over tf-bankrec-r1)
  D9   Timing items are listed with the open items and offered in suggestions. Accepting a match clears the timing
       classification (kept: who classified it, when, the expected date; history in brc_mark_event); an undo restores it.
       A match must name the timing classifications it clears, so one made by another user a moment earlier is refused.
  D10  Ledger rows booked before the first day can be proposed as opening outstanding items and accepted by a reviewer.
       They then explain their part of the opening difference and stay outstanding until a match clears them. A row
       that clears later without being an accepted opening item never explains the opening difference by itself.
  +    Partial clearing (a match for part of an item; the rest stays outstanding; a later match completes it).
  +    Two users at once: a claim per matched/classified item (database-enforced), one change at a time per account,
       version checks on statement lines and rates, conditional updates for acceptance, decisions and reviews.
  +    Daily records keep their snapshot and the fingerprints of what they rest on; a later change to ledger rows,
       statement lines, rates, matches, explanations or opening items flags the reviewed record "re-review needed".
  +    Exchange-rate differences need a written explanation (the 1 % suggestion tolerance never explains anything);
       the revaluation must be explained when the daily record is prepared.
  +    Corrections show where they stand: proposed / approved — not posted / posted and verified against the ledger
       entry. "Ledger not yet corrected" is shown on the day, the overview and reviewed records. Nothing is posted.

Install (isolated test instance)
  1. python3 apply.py check  <avenir_rv root>      (C:\wamp64\www\avenir_rv; refuses any other base URL)
  2. python3 apply.py apply  <avenir_rv root>      (backs up the r1 files to before/<time>/)
  3. phpMyAdmin, database avenir_restore_verify_20261011 selected: run sql/upgrade-v2-test-bed.sql (stops itself on any
     other database or schema version).
  4. Optional, for the verification account: sql/dummy-data-r2-verification.sql.

Roll back
  Files:     python3 apply.py rollback <avenir_rv root>   (the r1 files come back from before/<time>/)
  Database:  EITHER sql/rollback-schema-v2-to-v1.sql (removes the r2 columns/tables; the r2 work stays as r1 rows)
             OR     sql/reset-demo-to-r1-snapshot.sql (puts the demonstration data back exactly as it was before r2,
                    from sql/demo-snapshot-r1-20261012/; also removes the verification account and its dummy rows).
  Rehearsed: upgrade, rollback and reset on MySQL 8.0.46 with the snapshot; every table back byte-for-byte.

Files      app/Config/Routes.php (+3 routes in the r1 group), the controller, Corrections/Matching/Rec/Schema/Statements,
           the four views. Access.php unchanged. md5 list: SOURCE-MD5.txt.
Tests      tests/test_bankrec_r2.py (cloud, 108 checks incl. true parallel races), tests/ui_bankrec_r2.py (cloud screens),
           tests/rv_test_r2.js (the isolated-instance run, also rehearsed in the cloud), tests/rehearse_rv_r2.py.
           rv_test_r2.js fixed 12 Oct: it reads the security token from '.../daily_bank_rec' (no trailing slash; WAMP
           redirects the slash form to /public/...). Not a module change; SOURCE-MD5.txt is unchanged.
Results    results/cloud/ (final run 12 Oct: r2 suite 108/108, screens 20/20, regressions 202/71/36/33/26/10, rehearsal 63/63)
           results/isolated-instance/ (12 Oct, avenir_rv: upgrade 21 claims = 21 items, 57/57 checks, 7/7 two-session
           tests, ledger check values unchanged; results_rv_r2.json)
Installed  on the isolated instance only, 12 Oct 2026 (files backed up in before/20261012-002808/; schema v2). Development
           (avenir) is still r1 / schema v1 with empty reconciliation tables. Live: not installed.
Never      writes to the ledger (account_runing) or its reconciliation flags; never posts an entry.
