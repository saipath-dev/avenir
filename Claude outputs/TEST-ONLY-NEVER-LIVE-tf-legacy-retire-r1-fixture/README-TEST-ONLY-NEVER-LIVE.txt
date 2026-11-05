TEST ONLY — NEVER LIVE — NEVER DEVELOPMENT

A temporary test fixture for tf-legacy-retire-r1, for the ISOLATED TEST BED only (database avenir_restore_verify_20261011,
http://localhost/avenir_rv/). It is NOT part of the deployable kit (tf-legacy-retire-r1 = 5 files + apply.py).

  create-temporary-test-login.sql   adds TestLegacyTools in its own TEST group holding only the two privileges the test needs
                                    (Manual Update 107, Bank Req Data Transfer 184). Not an administrator.
                                    The password: a new one-time password made for the test run; only its hash is pasted
                                    into the script at run time (placeholder below). No password or hash is stored here.
  remove-temporary-test-login.sql   removes that login, its group, its employee row and its two privilege rows, and shows
                                    the counts afterwards.

Both scripts stop (error 1048) unless the selected database is avenir_restore_verify_20261011.
Audit, activity-log and session rows the test login produced stay as the record of the test.
