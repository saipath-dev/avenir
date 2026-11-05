tf-bankrec-r3 — Daily Bank Reconciliation r3, 12 October 2026. FOR THE ISOLATED TEST INSTANCE ONLY
(http://localhost/avenir_rv/, database avenir_restore_verify_20261011). Not for the development system or live:
development stays on tf-bankrec-r1, live is untouched. Installing anywhere else needs a separate approval.

What r3 changes (over tf-bankrec-r2)
  1 D7 guard   The OLD Bank Reconciliation screen (Accounts → Bank Reconciliation, accounts/bank_reconcil) refuses every
               change for a bank ledger account that has a CONFIRMED mapping in the Daily Bank Reconciliation, for rows
               dated on or after that mapping's first day: ticking/unticking a row (update), "Finalize selected",
               "reconcile up to", moving rows to or from the unrealised account (post / repost / repost single).
               Earlier dates stay available exactly as before. A refusal changes nothing, is answered 409 with the reason
               (shown on the screen), and is audited (brc_legacy_refused). The guard never sets or clears a flag.
               The new workflow lists flags the old screen set in the protected period (bank_rec, bankst_flag, rows moved
               to the unrealised account 1-6-112-10782) as a warning on the day and on the overview, and leaves them.
               Which accounts are protected and from which date depends only on the mappings — no mapping or first day
               is assumed or confirmed by r3 (D1–D7 stay open).
  2 Pending    A correction that covers several days (bank charges of a week, say) is counted on a day only for the part
    amounts    attributable to that day's items so far; the correction's full total is named separately ("X up to this
               day; correction total Y"); each correction is counted once. Status and "ledger not yet corrected" unchanged.
  3 Verify     A posted correction whose ledger entry changes afterwards reads "Posted entry changed in the ledger —
    again      verify again" (it counts as not corrected); the preparer can verify it again — accepted only if the entry
               still matches the approved lines exactly; refused (409) otherwise. Audited.
  +            The old screen's error messages appear at all (they used swal(), which throws with the SweetAlert2 11 the
               page loads); corrections and opening items withdrawn by their proposer read "withdrawn by …".

Install (isolated test instance)
  1. python3 apply.py check  <avenir_rv root>      (C:\wamp64\www\avenir_rv; refuses any other base URL)
  2. python3 apply.py apply  <avenir_rv root>      (backs up the 8 r2 files to before/<time>/; adds LegacyGuard.php)
  No database step: r3 makes no schema change (version stays 2).
  For the verification: sql/setup-r3-test-login.sql adds the TEST login TestRecDual (TEST group with the view, prepare and
  review privileges of Daily Bank Reconciliation only; test-bed database only).

Roll back
  Files:     python3 apply.py rollback <avenir_rv root>   (the r2 files come back; LegacyGuard.php is taken out)
  Data:      sql/reset-to-before-r3.sql (phpMyAdmin, test-bed database selected) puts the reconciliation TEST data back
             exactly as it was before r3 (snapshot sql/before-r3-snapshot/), including the dummy ledger rows, and removes
             the TEST login TestRecDual. The audit trail is kept.
  Complete:  the whole test-bed database was also copied before r3 (database avenir_rv_backup_20261012_before_r3) and
             the instance's app\ and public\ folders (C:\wamp64\www\avenir_rv-backup-20261012-before-r3\).

Files      see SOURCE-MD5.txt (8 changed + 1 added). tools/: the scripts that build Routes.php and the two old-screen files
           from the r2 files (patch_*), the snapshot/reset generator (r3_db.py) and the phpMyAdmin helpers (pma_r3.js).
Tests      tests/test_bankrec_r3.py (cloud: the r2 suite + r3, races of two different users), tests/ui_bankrec_r3.py
           (cloud screens), tests/rv_test_r3.js (the isolated-instance run, rehearsed in the cloud by
           tests/rehearse_rv_r3.py), tests/rehearse_apply_r3.sh (apply/rollback rehearsal).
Results    results/cloud/: regression 202/71/36/33/26/10, r2 suite 108/108 and screens 20/20 on the r3 code, r3 suite 132/132,
           r3 screens 24/24, rehearsal of the instance run 34/34, apply/rollback rehearsal.
           results/isolated-instance/ (12 Oct, avenir_rv): 54/54 checks (RESULTS.txt), races of two different users,
           before/after ledger checks, the tested rollback; then r3 installed again (before/20261012-061644).
Never      writes to the ledger (account_runing) or its reconciliation flags; never posts an entry. The dummy posting
           entry of the verification (99980399) was written by SQL into the test-bed database only.
