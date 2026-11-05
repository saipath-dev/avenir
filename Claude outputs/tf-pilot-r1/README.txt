tf-pilot-r1 — the common starting page (Pilot), confidential access on the server, the read-only auditor (11 Oct 2026)
=================================================================================================================================
Built and tested on the cloud test copy; installs OVER tf-paydesk-r5 on the development system only. No database change.
10 files: 8 changed — app/Controllers/LoginController.php, app/Controllers/NewDashboardController.php, app/Config/Routes.php (one route), app/Controllers/Tour/TourPaymentDeskController.php (a refused desk action is recorded like a refused route; GET tour/tour_payment_dashboard/audit_log — the audit trail, read-only, for the administrator, the Accounts manager and the auditor),
app/Views/pages/dashboard2/tile_dashboard.php, app/Filters/UserPermissionFilter.php, app/Libraries/TourPayments/Perms.php,
app/Libraries/TourPayments/SupplierBank.php — and 2 new: app/Libraries/Pilot.php, app/Views/pages/dashboard2/pilot_panel.php.

1. PILOT — THE COMMON STARTING PAGE
-----------------------------------
   After login every department lands on dashboard2/tiles (the tile page with the Pilot Report tile) instead of the empty
   legacy /dashboard; the System Administrator keeps /dashboard (its figures are administrator-only), drivers their portal.
   The page is now open to every signed-in user — before, it needed the "Tour Search" menu of the Ground Handling category,
   which is why landing there used to mean an Operations privilege. What the page SHOWS is unchanged in principle: the area
   tiles are the user's permitted menu categories, and the new department panel at the top is built from the same
   privileges (App\Libraries\Pilot::forUser):
      • the department (the profile name) and the signed-in user;
      • TASKS WAITING, each a count of records the user may open and a link to the page — shown only when the privilege
        for that work is held: my requests awaiting approval / rejected (request privilege); requests awaiting approval,
        suppliers awaiting a batch, hotel rate approvals (management); vouchers and bulk transfers awaiting initiation,
        suppliers with payments waiting whose bank is unverified (accounts); initiated payments awaiting Paid (bank
        authoriser); inquiries awaiting a reply, quotations awaiting confirmation (tour reservations); tours within 14 days
        with unconfirmed hotel nights (hotel reservations); tours within 14 days without a driver, tours on the road today
        (operations); tours starting within 7 days (anyone with a tour view);
      • SHORTCUTS: the held entries of a curated list (dashboard, approval, payables, bank payment check, bookings, …);
      • YOUR REPORTS: the held menus of the report categories (and report-named menus), as links.
   Nothing on the panel grants anything; the page's data widgets (tour search, recent tours, driver allocation) keep their
   own menu requirements through the filter's aliases, exactly as before.

2. CONFIDENTIAL ACCESS, ENFORCED ON THE SERVER
----------------------------------------------
   Avenir already runs the permission filter GLOBALLY (Config/Filters: UserPermissionFilter on every route except login,
   /dashboard, the driver portal, help and a few utilities), so direct links, downloads, exports and data endpoints are
   covered by the same rule as pages: the path must be under a menu url the user's group holds, or under one of the
   filter's aliases. This kit changes four things in that filter:
      a) every refusal is RECORDED: tpd_audit action access_denied (user, path, method, ip, group, why) — a_log with
         Panel = DENIED where the payment desk is not installed; the answer is 403 (JSON for AJAX, the 403 page otherwise);
      b) READ-ONLY groups: profile 34 "Auditor (read-only)" and any profile whose name contains "read-only" or "auditor"
         may open what they are granted but never change anything — every POST / PUT / DELETE is refused, and so is any
         GET route whose path acts (add / remove / delete / update / create / save / cancel / approve / reject / finalize /
         mark / send / post / insert / confirm / verify / setup / initiate / paid / release / allocate / assign / upload /
         import / generate / reset / edit) — recorded as "read-only profile";
      c) payment roles WITHOUT the Tour Payment Dashboard menu — the Bank Authoriser (35), the Auditor (34), an Accounts
         profile without 200 — reach the desk pages their own privilege is about (Supplier Payables data, batch and bulk
         pages, the voucher page; initiate / Paid / batch endpoints for the matching privilege). Before this kit the Bank
         Authoriser profile could open the Bank Payment Check page but its Paid action on the batch page was refused by
         the route filter (the r4/r5 tests ran with a bank approver who also held the dashboard). Every action is still
         checked by the controller against the role (Perms) and the separation of duties (Separation);
      d) the landing page (dashboard2/tiles) is open to every signed-in user (§ 1).
   BANK DETAILS: a supplier's full account number is returned only to the roles that pay or verify — Accounts (bank
   initiation), the Bank Authoriser, the Supplier Bank Details master (the New Generation privilege; the legacy Supplier
   master menu does NOT count), the verifier, the Avenir bank-account master — and the administrator (Perms::bankDetails). Everyone else (requesters, management, auditors) gets the bank, the branch and the
   last four digits (•••• 2999) on the dashboard, Supplier Payables, the batch and bulk pages and the voucher page; the
   Avenir paying accounts were already offered masked (account_no_masked). The remittance advice already printed
   "ending ####".
   FINANCIAL FIGURES: the administrator dashboard's figures (bank balances, revenue, liabilities) are served by
   dashboard/get_* routes that the filter allows only to groups holding the "Workshop" menu (url dashboard) — the
   administrator; the legacy /dashboard page itself renders nothing for other groups. The accounts entry screens and the
   37 accounts reports are under accounts/… and report/… urls — a group reaches one only with its menu.
   SEEN ON THE DEVELOPMENT SYSTEM WHILE TESTING (pre-existing, not changed): the legacy Driver Allocation page
   (dashboard2/transport/driver_allow/tour_transport) answers 500 — "Undefined array key tour_id" in its view — for the
   administrator too; the "Crew replacement" menu (tour/transport_ops/crew_change) has no route (404). The Pilot shortcuts
   avoid both and send the allocation task to Transport Operations. The tpd_ tables carry the server's default collation
   (utf8mb4_0900_ai_ci) while the legacy tables are utf8mb4_unicode_ci: a SQL join between them on a text column fails
   (the panel converts explicitly; the payment desk compares such keys in PHP or by literal).
   NOT CHANGED (pre-existing, listed for your decision): tour/print and tour/print/* (tour document printing) are excepted
   from the permission filter for every signed-in user; master_files/daily_usd_rate/quick_save and /today are excepted
   (the controller itself checks the rate privilege); the legacy voucher print (accounts/voucher_print) is restricted to
   the administrator and the Admin group by the filter's orphan list.

3. INSTALL / VERIFY / ROLL BACK
-------------------------------
   python3 apply.py check  C:\wamp64\www\avenir  → 6 changed, 2 added, no "changed_since_tested"
   python3 apply.py apply  C:\wamp64\www\avenir  → backups in before/<time>/; nothing to set up
   Users sign out and in once. python3 apply.py rollback puts the r5 files back; no database step.

4. TESTS (cloud test copy; tests/pilot_test.py, evidence/results_pilot.json)
-----------------------------------------------------------------------------
   70 the starting page: every non-administrator login is sent to dashboard2/tiles, the administrator to /dashboard; the
      page opens for a requester, management, accounts, bank approver, auditor and a quotation desk login that holds no
      Ground Handling menu; the requester's page lists only its own areas and no operations / settings shortcut, no reports
   71 the panel's tasks per privilege, each count equal to the table's; roles see no line of another role
   72 bank details: accounts sees the full number, the requester the last four digits, the desk's and Supplier Payables'
      supplier accounts masked for management, the paying accounts offered masked
   73 confidential pages / data / exports refused (403) outside the role — balance sheet, trial balance, bank balances,
      user accounts, bank payment check, supplier payables data, a voucher PDF, the finalized-payments export — and every
      refusal recorded (access_denied); the permitted pages of each role still open
   74 the auditor reads Supplier Payables and the finalized payments but cannot dispute, cancel a voucher or grant a
      privilege (403, recorded "read-only profile"); nothing changed
   75 the administrator unchanged (dashboard, Pilot, Balance Sheet, User Accounts, Bank Payment Check, Supplier Payables,
      full bank details, the full report list)
   76 the request privilege split still holds (requester writes, management refused)
   77 the audit trail (audit_log) is readable by the administrator, the Accounts manager and the auditor, refused to
      the others; a refused desk ACTION (a requester trying to approve) is recorded as well as a refused route
   Plus the whole r4 suite (202 checks) and the r5 bulk tests (71) re-run on the same build — see evidence/.
