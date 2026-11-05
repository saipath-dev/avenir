tf-legacy-retire-r1 — retires two old one-off tools, 12 Oct 2026. Approved by Shantha for the DEVELOPMENT system only.
Tested on the cloud test copy, then on the isolated test instance (avenir_rv), then on development (refusal tests only).
apply.py refuses every other target, live included.

What it does (5 files, no database or schema change, no route or menu change, no run-once marker)
  "Bank Not Reconciliation Record Insert" (Settings → Manual Update) and "Bank Req Data Transfer" (Accounts, menu 184) are
  retired. Their routes stay, so a page that is still open gets a proper answer. Any request to either tool:
    1. security token missing or wrong → 403 "Security token missing or expired — reload the page. Nothing was changed."
    2. valid token → 409 with the retirement message (A: completed on 21 October 2025, entries 15947–16232; B: items already
       carried forward on 21 October 2025, no 31 March 2025 brought-forward balance to adjust). Nothing else runs.
    3. every attempt → one tpd_audit row: action legacy_tool_refused, ref_type legacy_tool, ref_id the tool, the user, the time,
       the IP address, the answer (403/409), why (token/retired), the posted bank (B), reason "… (nothing was changed)".
  A user without the privilege is stopped earlier by the permission filter (403, audited as access_denied), as before.
  On the pages, only the two buttons become grey "Retired" notices. All other Manual Update buttons, routes and code are
  unchanged. The original code of both tools stays in the controllers, after the refusal, and is never reached.

Files (SOURCE-MD5.txt = new, ORIGINAL-MD5.txt = the originals on development and the isolated instance)
  app/Libraries/BankRec/LegacyGuard.php                       + RETIRED_TOOLS, refuseRetiredTool()
  app/Controllers/Settings/ManualUpdateController.php         bank_recon_record_insert(): refuses first
  app/Controllers/Accounts/BankUnrealisedValueController.php  process_unrealised_value_change(): refuses first
  app/Views/pages/settings/manual_update.php                  the one button → "Retired" notice
  app/Views/pages/accounts/bank_unrealised_value_change.php   the Process button → "Retired" notice

Install / roll back
  python3 apply.py check    <root>    (C:\wamp64\www\avenir or C:\wamp64\www\avenir_rv)
  python3 apply.py apply    <root>    backs up the 5 files to before\<time>\files\ (checked against ORIGINAL-MD5), copies, verifies
  python3 apply.py rollback <root>    puts the 5 files back and verifies each against ORIGINAL-MD5 ("all_five_original": true)
  PHP's opcache picks up changed files within about 2 seconds.

Tests   tests/test_legacy_retire.py (cloud copy: positive control, other Manual Update actions before/after, refusals, audit,
        ledger, rollback); tests/retire_check.js (browser: isolated instance with TEST logins; development with the session
        already open). No password or credential value is stored in this kit.
        The temporary TEST login used on the isolated test bed is created and removed by scripts kept OUTSIDE this kit, in
        ..\TEST-ONLY-NEVER-LIVE-tf-legacy-retire-r1-fixture\ (TEST ONLY — NEVER LIVE).
Results results/cloud/, results/isolated-instance/, results/development/
