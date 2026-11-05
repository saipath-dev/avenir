# E8 — Accounts module access control: design (development only)

**Status:** design for your approval. **Nothing has been changed:** no code, permission or data on development, and live is not touched. The retired-tool change stays installed on development, as it is.

**Priority:** high. E8 is a separate item from D1–D7 and E1–E7. It also covers E4: the Tour Dashboard reaching debtor-invoice actions.

**Basis:** the 203 addresses under `accounts/`, read from the code on 12 Oct 2026. Appendix A lists every one of them with its proposed rule; the same list is in the CSV.

---

## 1. Why this is needed — what the inventory shows

| Finding | Evidence (development) |
|---|---|
| **One menu opens the whole module.** The permission check allows every address that *starts with* an allowed menu address. So "Manage Accounts" (address `accounts`) opens all 203 addresses. Every other Accounts menu likewise opens everything below its own address. | It is held by groups 1, 17 (Auditor, 3 active users), 25 (Admin, no users) and 30 (Accounts). |
| **Page access and actions are the same permission.** Anyone who can open a screen can also change its data. | 73 addresses change data:<br>• 68 by POST<br>• 5 by a plain link (GET) |
| **Changes made by plain links.** | • `creditor_invoice/expenses_delete` and `new_creditor_invoice/expenses_delete`: delete a ledger entry and its voucher rows<br>• `debtor_invoice/cancellation_request`: request a tour amendment<br>• `debtor_invoice/cancellation`: cancel an invoice and mark the tour cancelled<br>• `utility_bill/delete`: delete a ledger entry |
| **Almost no security token.** | Only Daily Bank Reconciliation (27 actions) and the two retired tools check a token. **45 actions accept a request without one.** |
| **Almost no own checks in code.** | Only 40 of 203 addresses check the privilege again in their own code: Daily Bank Reconciliation and the retired tool. The other 163 rely on the menu alone. |
| **Almost no audit trail.** | No audit row for Accounts actions, except Daily Bank Reconciliation and the retired tools. |
| **Read-only is guessed from the group's name.** It refuses POSTs, and GETs whose address contains a change word as a separate part. | 4 of the 5 changing links slip through this rule. |
| **Back doors from other modules.** Hidden "aliases" in the permission filter open Accounts addresses to other menus. | • Tour Dashboard (110), Working Dashboard (117) and Payment Forecast (132) open **everything under `debtor_invoice_new/`**, including posting a debtor invoice and sending a receipt (E4). This reaches 12 groups.<br>• Invoice Cancellation (187) and Inquiry (79, 203) open the amendment request. |
| **Dead routes.** These addresses point at code that does not exist. | `new_creditor_invoice/complete_payment`, `voucher_payments/voucher_delete`, `debtor_invoice_new/tour_details`, `utility_bill_entertainment/delete` |

**Correction to my last answer.** `journal_entry/fetch` only *reads*: it is the account search box on the journal page. A code-reading mistake made it look like a write. There are **5** changing links, not 6. The corrected Manage Accounts list is delivered with this design.

**Classes of the 203 addresses:**

| Class | Count |
|---|---:|
| Pages | 82 |
| Reads | 44 (29 GET, 15 POST) |
| Writes | 73 (68 POST, 5 GET) |
| Dead | 4 |

---

## 2. Design principles

1. **Deny by default.** An Accounts address that is not in the registry is refused and audited.
2. **Seeing is not doing.** Page privileges let a user open screens and read. Every change needs a separate **action privilege**.
3. **Every change is a POST with the security token**, and is **audited**, whether it succeeds or is refused.
4. **Least privilege.** A user gets an action only if their role needs it. Destructive, corrective and approval actions are separated from day-to-day posting.
5. **Auditors read from an explicit allowlist**, not a name guess, and have **no** action privileges.
6. **No surprises.**
    - The new rules first run in **report mode**: they write down what they *would* refuse, and refuse nothing.
    - Enforcement starts only after you have reviewed those reports.
    - Every step can be rolled back.

---

## 3. Target design

### 3.1 One route registry — the single source of truth

A new configuration file, `app/Config/AccessPolicy.php`, lists every Accounts address once (Appendix A). Each entry has:

| Field | Meaning |
|---|---|
| method + address | e.g. `POST accounts/journal_entry/save`. Placeholders such as `(:num)` are allowed. |
| class | page / read / write |
| required privilege | **Page or read:** the existing menu of that screen (unchanged).<br>**Write:** an action privilege (3.2). |
| other modules allowed | Explicit replacement for today's hidden aliases. For example, Tour Management may read `creditor_invoice/get_details`. Only reads and the amendment request are carried over. |
| read-only allowed | yes / no (the Auditor allowlist, 3.7) |
| token / audit | Always on for writes |

The registry covers 199 addresses, plus 5 "tombstones" for the old links converted to POST. The 4 dead routes are removed. **An automated test fails if a new `accounts/` route is added without a registry entry** (8.T1). The rule cannot silently decay again.

### 3.2 Action privileges — 22 new, hidden

They are stored like every Avenir privilege, as menu rows granted to groups (Settings → Group Privileges):

- hidden from the sidebar (`show_in_menu_bar = 1`);
- with an address of the form `act:…`, which can never match a real page, so the old prefix rule can never hand them out by accident.

Each day-to-day action is paired with today's screen. Destructive, corrective and approval actions are separate and elevated. The Daily Bank Reconciliation keeps its own three privileges (view, prepare, review) unchanged. The retired tool is denied to everyone.

| Action privilege | What it allows | Addresses | Paired with today's menu | Default holders (proposal, for decision) |
|---|---|---:|---|---|
| `act:chart.maintain` | Chart of accounts — maintain | 9 | — (new, elevated) | 1 System Administrator |
| `act:records.delete` | Delete a posted record (reason required) | 4 | — (new, elevated) | 1 System Administrator, 29 Finance & Approvals — Management Approver |
| `act:journal.correct` | Journal — reverse / change date (reason required) | 2 | — (new, elevated) | 1 System Administrator, 29 Finance & Approvals — Management Approver |
| `act:journal.post` | Journal — post | 1 | Journal Entry (81) | 1 System Administrator, 30 Accounts |
| `act:vouchers.create` | Vouchers — create | 1 | Voucher Payments (82) | 1 System Administrator, 30 Accounts |
| `act:vouchers.finalise` | Vouchers — finalise (Mark Finalized) / authorise | 1 | Bank Payment Check Report (189) | 1 System Administrator |
| `act:vouchers.audit_mark` | Voucher audit — record marks | 2 | Voucher Audit (124) | 1 System Administrator, 29 Finance & Approvals — Management Approver, 30 Accounts |
| `act:creditor_invoice.post` | Payment amendment — post | 1 | Payment Amendment (84) | 1 System Administrator, 30 Accounts |
| `act:creditor_payment.post` | Creditor payment — post | 1 | Creditor Payment (56) | 1 System Administrator, 30 Accounts |
| `act:other_invoice.post` | Service provider invoice — post | 1 | Service Provider Invoice (172) | 1 System Administrator, 9 Accountant, 30 Accounts |
| `act:service_provider_payment.post` | Service provider payment — post | 1 | Service Provider Payment (194) | 1 System Administrator, 9 Accountant, 30 Accounts |
| `act:advance_payment.post` | Advance payment — post | 1 | Advance Payments (90) | 1 System Administrator, 30 Accounts |
| `act:utility_bill.post` | Utility bill — post / pay | 3 | Utility Bill Payment (113) | 1 System Administrator, 9 Accountant, 30 Accounts |
| `act:entertainment.post` | Entertainment expense — post | 3 | Utility Bill Entertainment (181) | 1 System Administrator, 30 Accounts |
| `act:debtor_payment.post` | Debtor receipt — post | 1 | Debtor Payment (78) | 1 System Administrator, 18 ticketing_admin, 20 TicketingAccountant, 30 Accounts |
| `act:debtor_invoice.post` | Debtor invoice — post / send receipt | 2 | Debtor Invoice (86) | 1 System Administrator, 30 Accounts |
| `act:debit_note.post` | Debit note — post | 1 | Debit Note (115) | 1 System Administrator, 30 Accounts |
| `act:credit_note.post` | Credit note — post | 1 | Credit Note (139) | 1 System Administrator, 30 Accounts |
| `act:usd_withdrawal.post` | USD withdrawal — post | 1 | USD Withdrawal (192) | 1 System Administrator, 30 Accounts |
| `act:amendment.request` | Tour amendment — request | 1 | Invoice Cancellation (187) | 1 System Administrator, 13 Operational, 15 Maintenance |
| `act:amendment.decide` | Tour amendment — approve / finalise | 1 | Tour Amendment Approval (127) | 1 System Administrator, 29 Finance & Approvals — Management Approver |
| `act:old_bankrec.change` | Old bank reconciliation — change (until withdrawn, D7) | 6 | Bank Reconcillation (112) | 1 System Administrator, 30 Accounts |

### 3.3 The enforcement filter

A new filter, `AccessPolicyFilter`, runs after the sign-in check. **Only for addresses the registry manages** (in phase 1: `accounts/`), it replaces the old "starts with" rule. For each request:

1. **Not in the registry** → 403 "not available", audited (`access_policy_denied`, reason *unregistered*).
2. **Wrong method** (for example, a GET to a write) → 405, audited.
3. **The user's group is read-only** and the address is not on the allowlist → 403, audited.
4. **The user holds none of the required privileges** → 403, audited. The System Administrator keeps every privilege, as today, but steps 2, 5 and 6 still apply.
5. **Write without a valid security token** → 403 "Security token missing or expired — reload the page. Nothing was changed.", audited.
6. **Write allowed** → it runs. Afterwards one audit row records the outcome (6.T6), and the response carries the next token.

**Modes, per module, in the same config file:**

| Mode | Behaviour |
|---|---|
| `legacy` | Today's behaviour. |
| `report` | Today's behaviour, plus one `access_policy_shadow` audit row for every request the new rules *would* refuse. |
| `enforce` | The new rules. |

Switching modes is a one-line change, and the first rollback lever (7.1).

### 3.4 Security token for every change

**Making it available to every page**

- `layouts/head.php` gets two `<meta>` tags with the token name and value.
- `layouts/scripts.php` gets a small jQuery prefilter. It adds the token as the `X-CSRF-TOKEN` header to every same-site POST, and picks up the new token from each response.
- The token rotates after every successful change and is read from the page; the cookie is HttpOnly, so scripts cannot read it.
- Pages that already send the token keep working: Daily Bank Reconciliation, and the retired-tool pages, which send it in the body.

**What the inventory found**

- **No plain HTML forms post to Accounts.** All 77 page calls to Accounts POST addresses are jQuery requests, so the prefilter covers them without editing each page.

### 3.5 Changing links become POST

For each of the 5 links:

1. Its route becomes POST.
2. The controller reads its values from the POST body; a value in the address is ignored.
3. Each active page that calls it switches its call to POST; the prefilter adds the token.
4. The old GET address stays for one stabilisation period as a **tombstone**. It answers 405 "This action now needs a POST from the page; reload the page", changes nothing, and is audited.

| Address | Active pages that call it |
|---|---|
| `creditor_invoice/expenses_delete` | `accounts/creditor_invoice.php` |
| `new_creditor_invoice/expenses_delete` | `accounts/new_creditor_invoice.php` |
| `debtor_invoice/cancellation_request` | `accounts/debtor_arrival_report.php`, `accounts/debtor_invoice_report.php`, `accounts/debtor_receipt_report.php`, `dashboard2/tile_dashboard.php`, `dashboard2/tile_dashboard2.php`, `dashboard2/tour_search/timeline.php`, `inquiry/inquiry_timeline.php`, `inquiry/inquiry_timeline_full.php`, `tour/quotation/quotation_timeline.php` |
| `debtor_invoice/cancellation` | `accounts/debtor_invoice_cancellation_req.php`, `accounts/debtor_invoice_cancellation_req_final.php` |
| `utility_bill/delete` | `accounts/utility_bill_report.php`, `accounts/entertainment_expenses_report.php` |

Notes:

- Two backup copies of these pages are never rendered by any route, so they are left alone.
- **The working dashboard (`tile_dashboard.php`, `tile_dashboard2.php`) is touched.** The change is one call per file, from GET to POST, with no visual change. Under your standing rule on the dashboard, this needs your explicit approval (decision X9).

### 3.6 Audit trail

**Where:** `tpd_audit`, the same table the payment and reconciliation work uses.

**One row per change attempt, recording:**

- action: `accounts_action`, `access_policy_denied` or `access_policy_shadow`;
- the address and method;
- the user and group;
- the time and IP address;
- the answer (HTTP status, and the application's own status where it returns one);
- the privilege used;
- the record identifiers posted (for example invoice number, voucher, tour, entry ID);
- the reason, for deletes and corrections (X8).

**Never recorded:** amounts, bank account numbers, or free text other than the reason. Refusals are recorded the same way. Page views stay in the existing activity log.

### 3.7 Auditor read-only allowlist

- **Who:** the read-only groups are listed explicitly in the config: 17 Auditor and 34 Auditor (read-only). The old name guess is no longer used for Accounts.
- **What they can open:** **84 addresses** (56 pages and 28 reads), flagged "yes" in Appendix A.
    - all reports, ledgers, statements and prints;
    - the chart of accounts view;
    - the bank-reconciliation details and summary;
    - Daily Bank Reconciliation view (they already hold it).
- **What they cannot open:** entry screens and their helper reads (26 pages, 16 reads). They do not need these to audit, and showing them invites attempted changes.
- **Actions:** none. "Voucher audit — record marks" is the one exception you may want to grant to named auditors (X4).

### 3.8 Least privilege — who can do what, today and proposed

**Defaults** (each one subject to your decisions in section 4):

- **Day-to-day posting** goes to the groups that hold that screen today. Auditor groups and the empty Admin group are excluded.
- **Elevated actions** (deletes, journal corrections, chart maintenance, amendment approval, voucher finalisation) go to the Management Approver and/or the System Administrator.
- **Other modules keep only reads**, plus the amendment request.

**Counts on development:**

| Group | Active users (dev) | Pages / reads reachable today | Changing actions reachable today | Proposed changing actions | Proposed action privileges (default, for decision) |
|---|---:|---:|---:|---:|---|
| 1 System Administrator | 1 | 126 | 45 | 45 | advance_payment.post, amendment.decide, amendment.request, chart.maintain, credit_note.post, creditor_invoice.post, creditor_payment.post, debit_note.post, debtor_invoice.post, debtor_payment.post, entertainment.post, journal.correct, journal.post, old_bankrec.change, other_invoice.post, records.delete, service_provider_payment.post, usd_withdrawal.post, utility_bill.post, vouchers.audit_mark, vouchers.create, vouchers.finalise |
| 9 Accountant | 1 | 8 | 6 | 5 | other_invoice.post, service_provider_payment.post, utility_bill.post |
| 12 Sales | 0 | 6 | 2 | 0 | — |
| 13 Operational | 4 | 7 | 3 | 1 | amendment.request |
| 15 Maintenance | 1 | 6 | 3 | 1 | amendment.request |
| 17 Auditor | 3 | 126 | 45 | 0 | — |
| 18 ticketing_admin | 3 | 2 | 1 | 1 | debtor_payment.post |
| 20 TicketingAccountant | 0 | 2 | 1 | 1 | debtor_payment.post |
| 21 Management | 0 | 5 | 2 | 0 | — |
| 23 Transport Manager | 0 | 2 | 0 | 0 | — |
| 25 Admin | 0 | 126 | 45 | 0 | — |
| 29 Finance & Approvals — Management Approver | 1 | 30 | 4 | 9 | amendment.decide, journal.correct, records.delete, vouchers.audit_mark |
| 30 Accounts | 1 | 126 | 45 | 27 | advance_payment.post, credit_note.post, creditor_invoice.post, creditor_payment.post, debit_note.post, debtor_invoice.post, debtor_payment.post, entertainment.post, journal.post, old_bankrec.change, other_invoice.post, service_provider_payment.post, usd_withdrawal.post, utility_bill.post, vouchers.audit_mark, vouchers.create |
| 31 Tour Reservations | 1 | 6 | 2 | 0 | — |
| 32 Hotel Reservations | 1 | 5 | 2 | 0 | — |
| 33 Transport & Guide Handling | 1 | 6 | 2 | 0 | — |
| 34 Auditor (read-only) | 0 | 54 | 4 | 0 | — |
| 35 Finance & Approvals — Bank Authoriser | 1 | 16 | 0 | 0 | — |
| 36 Pilot / Operations Allocation | 1 | 5 | 2 | 0 | — |

**What changes**

- **Auditor groups 17 and 34:** from 45 / 4 reachable changing actions to **0**.
- **Empty Admin group 25:** from 45 to **0**.
- **Sales, Management, Tour, Hotel and Transport Reservations, Pilot** (2 each, through the Tour Dashboard aliases — E4): to **0**.
- **Accounts:** keeps the 27 day-to-day actions and loses the 18 elevated ones.
- **Management Approver:** gains the elevated ones. These are mostly actions they cannot reach today (4 → 9).
- **System Administrator:** keeps everything, now with token, method check and audit.

---

## 4. Decisions needed from you before building

| # | Decision | My recommendation |
|---|---|---|
| X1 | Approve the 22 action privileges and the registry (Appendix A) as the rule set | Approve; I will adjust any row you mark |
| X2 | Elevated actions: deletes (4), journal reverse / change date (2), tour amendment approval (1) | Management Approver + System Administrator, **reason required**; not the Accounts preparer |
| X3 | Chart of accounts maintenance (9 actions) | System Administrator only for now. Later, a named Finance Controller, as with the bank master (D4) |
| X4 | Voucher audit marks (2): the auditor's own work, or Accounts / Approver? | If auditors must record marks, grant only this one action to named auditors. Otherwise Approver + Accounts |
| X5 | Voucher finalisation (`voucher_auth/update`, used by Bank Payment Check "Mark Finalized") | System Administrator only, consistent with "Paid = System Administrator only". Add Bank Authoriser (35) only if you want them to finalise legacy vouchers |
| X6 | Tour amendment requests from Operational (13) and Maintenance (15) pages | Keep, as an explicit privilege. Add Accounts (30) if they also raise requests |
| X7 | E4: remove `debtor_invoice_new/*` from Tour Dashboard, Working Dashboard and Payment Forecast | Remove the actions. Keep only the print and "sent mail" reads those pages show |
| X8 | Reason required for deletes and corrections (a one-line prompt; stored in the audit row) | Yes |
| X9 | One-line GET→POST change in the working dashboard files (`tile_dashboard*.php`) | Approve (no visual change). Otherwise amendment requests from the dashboard stop working when enforced |
| X10 | Legacy groups: Accountant (9, 1 user) keeps utility / service-provider posting; ticketing_admin (18, 3 users) keeps debtor receipts | Keep for now; review with E2 / E6 |
| X11 | Admin group 25 (no users) | Remove its Accounts privileges |
| X12 | Old Bank Reconciliation changes (6 actions) | Accounts only, until the old menu is withdrawn under D7 |

---

## 5. Migration plan — development only

The order is: cloud copy → isolated test instance → development. Each phase has an exit check and stops for your approval.

| Phase | What | Exit check |
|---|---|---|
| **P0** | Your decisions X1–X12; the registry finalised | Signed-off registry |
| **P1 — cloud copy** | Build:<br>• the registry and filter (with the three modes)<br>• the token meta tags and prefilter<br>• the after-audit<br>• the 5 GET→POST conversions with tombstones<br>• dead-route removal<br>• the privilege migration SQL and rollback SQL<br>Run the full test plan (section 6) | All tests pass; rollback rehearsed at all three levels |
| **P2 — isolated instance** | Install the kit. Run the migration SQL on the test bed. Tests use temporary TEST logins under the fixture rules (TEST ONLY — NEVER LIVE; one-time passwords; removed afterwards, verified against the snapshot) | Tests pass; rollback; fixture removed |
| **P3 — development, report mode** | Install the files with Accounts in **report** mode. Behaviour is unchanged; tokens are sent; shadow rows are recorded. Use development normally for an agreed period (for example 5 working days). I then report every `access_policy_shadow` row | You review: no legitimate work would have been refused, or the registry is corrected |
| **P4 — development, privileges** | Back up the privilege tables: snapshot of `menu_list`, `user_privileges` and `group_profile`, plus a full database copy. Insert the 22 hidden `act:` privileges and the approved grants (every row tagged `E8`). Verify the per-group matrix against section 3.8 | Matrix matches; nothing else changed |
| **P5 — development, enforce** | Switch Accounts to **enforce**. Run development checks: refusals and page reads with TEST logins and (with your consent) your session. Check ledger integrity | All checks pass; ledger identical |
| **P6 — stabilisation** | Monitor the audit trail. After an agreed period, with a separate approval, remove the tombstones and the old code path | — |

**Live** needs its own inventory, kit and approval later. Live is behind development, and its users and permissions differ.

---

## 6. Test plan

| # | Test | Where |
|---|---|---|
| T1 | **Registry completeness:** every `accounts/` route in `Routes.php` appears exactly once in the registry, and no registry entry is stale (a build check) | cloud, isolated, development |
| T2 | **Route matrix:** 199 addresses × 7 personas (no session, unprivileged, Auditor, Accounts, Approver, Bank Authoriser, System Administrator) × request variants (GET; POST without, with wrong, and with valid token). The expected answer is computed from the registry | **Cloud:** all of it, including successful changes on test data.<br>**Isolated:** all of it.<br>**Development:** refusals and reads only |
| T3 | **Functional regression:** every one of the 45 changing actions is performed from its own page by the right role, and the database effect is compared with the legacy code on the same restored snapshot (the method used for the Manual Update buttons) | cloud |
| T4 | **Security token:** missing, wrong or expired → 403; several changes in one page (rotation); two tabs; long-open pages | cloud, isolated |
| T5 | **GET→POST:** old links answer 405 and are audited; every calling page works by POST (browser clicks on all 15 active pages) | cloud, isolated |
| T6 | **Audit:** exactly one row per change attempt, with the right fields and no amounts or account numbers | all three |
| T7 | **Read-only:** auditors open all 84 allowlisted addresses, are refused everything else, and make 0 changes | cloud, isolated |
| T8 | **Existing suites:**<br>• regression battery (202 / 71 / 36 / 33 / 26 / 10)<br>• r2 108 and r3 132 suites, plus their screens<br>• sign-out fix<br>• retired-tools tests | cloud |
| T9 | **Ledger and data integrity:** rows, totals, flags and checksums before and after; all tables compared (only audit, log, session and the tagged privilege rows may change) | isolated, development |
| T10 | **Rollback rehearsal**, all three levels (section 7) | cloud, isolated; levels 1–2 on development |
| T11 | **Overhead:** time per request with the filter on (target under 5 ms) | cloud |

---

## 7. Full rollback

| Level | How | Restores |
|---|---|---|
| **7.1 Mode switch** (seconds) | `enforce` → `report` or `legacy` in `AccessPolicy.php` (one line) | Today's permission behaviour. The GET→POST pages keep working, because the POST routes stay. |
| **7.2 Files** | `apply.py rollback` | Every changed file back **byte-for-byte**: filter, config, routes, layouts, controllers, pages. Checked against `ORIGINAL-MD5`, as for the retired-tool kit. |
| **7.3 Data** | `rollback-e8-privileges.sql` deletes only the rows tagged `E8`: the 22 `act:` menu rows and their grants | `menu_list`, `user_privileges` and `group_profile` identical to the P4 snapshot (checked row by row and by checksum). A full database copy taken in P4 is the last resort. |

- **Order:** 7.1 first. 7.2 if code must go. 7.3 can follow at any time; unused `act:` rows are harmless.
- **Kept:** audit rows stay as the record.
- **Not needed:** no ledger or business data is migrated, so there is no data rollback beyond privileges.

---

## 8. What will change (expected, for the kit)

**New files**

- `app/Config/AccessPolicy.php` (registry and modes)
- `app/Filters/AccessPolicyFilter.php`
- the migration and rollback SQL
- the tests

**Changed files**

| File | Change |
|---|---|
| `app/Config/Filters.php` | Register the filter |
| `app/Filters/UserPermissionFilter.php` | Delegate managed modules; aliases into `accounts/` replaced by registry entries |
| `app/Config/Routes.php` | 5 GET→POST, 5 tombstones, 4 dead removed |
| `layouts/head.php`, `layouts/scripts.php` | Token meta tags and prefilter |
| 4 controllers | Read from POST |
| 2 more controllers (journal, entertainment expenses) | Reason for deletes and corrections (X8) |
| 15 active pages | Calls switched to POST, plus reason prompts. Includes the two working-dashboard files (X9) |

**Not changed**

- no ledger table or business data;
- no existing menu or grant (only new `act:` rows are added in P4);
- no other module's behaviour (they stay on the old rule; see section 9).

---

## 9. Out of scope and risks

**Out of scope**

- **Other modules.** Other modules have the same "starts with" rule: `tour/`, `dashboard2/`, `master_files/`, `report/`, `settings/`. The same registry and filter can take them on one by one later, each with its own inventory. I recommend that as the next step after Accounts.
- **Live** (see section 5).

**Risks and mitigations**

| Risk | Mitigation |
|---|---|
| A legitimate action is refused after enforcement | Report mode first (P3); the mode switch (7.1) |
| Pages left open across the deployment send no token | They get a clear "reload the page" message. The prefilter is loaded from server-rendered layouts, so no browser cache issue |
| Hidden callers of a converted link | The tombstones record them for review |
| Work beyond these 203 addresses | The registry test (T1) keeps new Accounts routes from bypassing the rules |
