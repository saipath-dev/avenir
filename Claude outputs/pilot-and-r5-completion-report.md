# Completion report — Pilot landing page, confidential access, R5 installed, test logins (11 Oct 2026)

Development system only (`C:\wamp64\www\avenir`). Nothing on the live system. The working Tour Payment Dashboard layout is unchanged; the redesign stays pending your approval. The departments and the seven test logins are as you left them.

## 1. Starting page — done

Every department now lands on the Pilot page (`dashboard2/tiles`, the tile page with the Pilot Report tile) after login; the System Administrator keeps the administration dashboard; drivers keep their portal. The page is open to every signed-in user — before, it needed the "Tour Search" menu of the Ground Handling category, which is why landing there would have meant an Operations privilege. What it shows is decided only by the user's own privileges: a new department panel at the top (department, tasks waiting with counts and links, shortcuts, your reports) and, below it, the area tiles as before. Nothing on the panel grants anything; the page's data widgets keep their own menu requirements.

## 2. Confidential reports, figures and bank details — done

Avenir's permission filter already runs on every route (direct links, downloads, exports and data endpoints included). What was added:

- Every refusal is now **recorded** — `tpd_audit`, action `access_denied`: who, which route or action, method, address, reason. A read-only audit-trail endpoint (`tour/tour_payment_dashboard/audit_log`) serves it to the administrator, the Accounts manager and auditor profiles.
- **Auditor (read-only)** (profile 34, and any profile named read-only/auditor): may open what it is granted, never change anything — POST/PUT/DELETE and acting GET routes are refused and recorded.
- **Supplier bank account numbers** are masked to the last four digits for every role that neither pays nor verifies (requesters, management, auditors); Accounts, the Bank Authoriser, the Supplier Bank Details master, the verifier and the administrator see the full number. The Avenir paying accounts were already offered masked; the remittance advice already printed "ending ####".
- A gap found and closed: the **Bank Authoriser profile could not actually press Paid** — it lacks the Tour Payment Dashboard menu, and the route filter refused the batch page's Paid action (the R4/R5 tests ran with a bank approver who also held the dashboard). Payment roles without the dashboard menu now reach the desk pages their own privilege is about; every action is still checked by the controller and the separation of duties.
- The administrator is unchanged (bypasses the filter as before).

## 3. R5 installed on the development system — done

- Backups: the whole `app/` folder copied to `C:\wamp64\www\avenir-backup-20261011-before-r5\` (981 files, verified), the 12 changed files backed up by the installer (`tf-paydesk-r5\before\20261011-063012`), the full before-R4 file and database backups still there. **Not done:** a fresh full database copy — phpMyAdmin needs your sign-in (the Avenir "Backup Database" feature looks for `F:\wamp64` and cannot run on this machine). Setup's new pre-upgrade table snapshot did not run this time because the first kit build was deployed before I added it; it will protect the next upgrade. R5's database change is additive (columns, indexes, one privilege entry) and `rollback_r5.sql` in the kit reverses it.
- Installed and verified: 34 files, schema 7, all 11 tables and 9 privilege entries present, mappings intact; Supplier Payables shows Bulk pay; Bank Payment Check renders.
- Performance fix while verifying: Supplier Payables took **48 s** on the development data (a voucher lookup per approved request, inherited from R2/R4); the scan was rewritten to a few queries — **1.7 s**, identical result (3 suppliers, 7 items). The kit on disk is the final build.
- InnoDB conversion: deferred, untouched.

## 4. Payment permissions — done

"Tour payment requests (make / cancel)" (menu 252, created by Setup) granted to Tour Reservations (31), Hotel Reservations (32) and Transport & Guide Handling (33) only. Management Approver (29) approves and creates batches, Accounts (30) initiates, Bank Authoriser (35) marks Paid — none of them can request; the same-user protections run on every route.

## 5. Each login tested on the development system

Through the application, from a separate browser origin so your administrator session stayed intact. Probes: landing page, panel, 17 pages/data endpoints, 7 actions (with harmless ids — nothing was written), bank-detail endpoint, the desk's own permission flags.

| Login | Lands on Pilot | Panel shows | Opens only its own areas | Refused (recorded) | Bank number |
|---|---|---|---|---|---|
| TestTourRes (31) | yes | own requests · inquiries · upcoming quotations · tours on the road | Pilot Report, Master Files, Transport & Guide Services, Tour, Chat | accounts, settings, bank balances, Bank Payment Check, approval, payables data, allocation — 13 routes + 7 actions | masked |
| TestHotelRes (32) | yes | own requests · 6 tours within 14 days with unconfirmed hotel nights | + Ground Handling | 26 routes + 14 actions | masked |
| TestTransport (33) | yes | own requests · 6 tours within 14 days without a driver | + Ground Handling, Transport & Guide Services | 13 + 7 | masked |
| TestFinApprover (29) | yes | **10 payment requests awaiting approval** · suppliers awaiting batch · rate approvals | + Accounts Reports (13 reports listed) | initiate, Paid, request, settings — 9 + 4 | not served (403) |
| TestBankAuth (35) | yes | payments initiated awaiting Paid | Pilot Report, Master Files, Tour | everything but Bank Payment Check, payables, finalized — 21 + 1 | — |
| TestAccounts (30) | yes | vouchers / bulk transfers awaiting initiation · **42 suppliers with payments waiting and an unverified bank** · 40 reports | + Accounts, Accounts Reports | approval, Paid, batch, settings — 10 + 5 | full |
| TestOperations (36) | yes | tours without a driver · on the road today | Ground Handling, Transport & Guide Services, Master Files, Tour, Chat | every payment and accounts route — 23 | not served |

153 refusals recorded in the audit trail during the run, each with user, route/action, method, address and reason. Cloud test copy, same build: R4 suite 202/202, R5 bulk 71/71, Pilot 36/36.

## 6. Outstanding issues

1. **Database copy before/after R5** — needs your phpMyAdmin sign-in; I will then make `avenir_backup_20261011_r5` and verify the schema at table level.
2. **Legacy Driver Allocation page** (`dashboard2/transport/driver_allow/tour_transport`) answers an error on the development system for every user, administrator included ("Undefined array key tour_id" in its view); the **Crew replacement** menu (233) points at a route that does not exist. Pre-existing; the Pilot shortcuts avoid both. Say if you want them fixed.
3. **Legacy Supplier master** (menu 29) shows supplier bank fields in full on its own page; Hotel Reservations (32) and Accounts hold it. The desk now masks for 32; decide whether 32 keeps menu 29 (it needs it to register new hotels).
4. Routes excepted from the permission filter before my work and left as they are: `tour/print/*` (tour documents, any signed-in user) and the daily USD rate quick-save (the controller checks the rate privilege).
5. The `tpd_` tables use the server's default collation while the legacy tables use another; SQL joins between them on text columns fail (handled where it occurred). Worth aligning in a later Setup.
6. Known and unchanged: the Bank Payment Check console error, the legacy voucher-cancel HTTP 500, the seven test staff ledger accounts (unused, kept).

## 7. Deliverables

`C:\wamp64\www\avenir\Claude outputs\`: `tf-paydesk-r5.zip` and folder (as installed, with `rollback_r5.sql`), `tf-pilot-r1.zip` and folder (as installed; README, tests, evidence, screenshots), this report.

## Next: Step 2, Daily Bank Reconciliation

Continuing at the inspection stage. To proceed I need you signed in to phpMyAdmin (for the ledger-side reads — the same access gives the R5 database copy above) and the Com Bank statements to compare against; nothing will be labelled reconciled on the strength of a Paid mark.
