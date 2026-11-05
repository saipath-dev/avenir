# tf-paydesk-r4 — revised setup proposal (accounts, mappings, privileges)

Development system `C:\wamp64\www\avenir`, database `avenir` — 11 Oct 2026. Everything below was read from the system; nothing has been created, mapped or granted. **Nothing will be changed until Avenir approves this page.**

## 1. Ledger accounts — placement to be confirmed by Avenir Accounts

| Account | Proposed location (Base → Title → Heading) | Status | Needs |
|---|---|---|---|
| **Realised Exchange Gain** | Other Income (8) → Foreign Exchange Gain-Loss (35) → Foreign Exchange Gain-Loss (111) — beside the existing 8-35-111-10780 *Foreign Exchange Gain-Loss* (423 postings in FY 2024/25, 13 in the current ledger) and 8-35-111-11414 *Exchange Gain Loss on bank balance* (7 year-end revaluation postings) | **not created** | **Accounts sign-off** — alternative: reuse 8-35-111-10780 instead of creating a new account (then realised gains from tour payments would share the legacy FX account) |
| **Realised Exchange Loss** | Expenses (4) → Expenses (8) → **Finance Charges (37)** — beside Bank Charges 4-8-37-10818. No "exchange loss" heading exists under Expenses today; if Accounts prefers its own heading, create heading "Exchange Loss" under Expenses → Expenses first, then the account under it | **not created** | **Accounts sign-off** on the heading |
| **Bank Charges 4-8-37-10818** | exists: Expenses (4) → Expenses (8) → Finance Charges (37); the only account under that heading | **active** (`deact = 0`, same leaf type as every voucher-posting account), used in FY 2024/25 (25 postings, Apr 2024 – Mar 2025), 0 postings in the current ledger | ready to map — no creation needed |

The Settings page accepts any active posting account, requires the three to be different accounts, and records who mapped them (audit `ledger_mapping_saved`). The gain and loss accounts stay empty until a USD voucher is marked Paid at a rate different from its approval rate; Bank Charges is posted only when a charge is entered at Paid.

## 2. Enabled users today (from `user_acc_tbl`, `access = 1`; driver logins excluded)

| Group | Enabled users | Disabled (not counted) |
|---|---|---|
| 1 System Administrator | **super admin** | — |
| 9 Accountant | **t1** | Nimesha, Prashanth |
| 13 Operational | cmx, rizma, Umesha, Vithya | Anupa, charith, Gayathri, isurini, Sandun |
| 15 Maintenance | Shanuka | — |
| 17 Auditor | audit firm, nethanjana, Nipuna | Niroshan |
| 21 Management | **none** | — |
| 25 Admin | **none** | — |
| 26 Quotation Desk | iqbaal, Malindo | — |
| 28 Transport & Crew Guide Allocation Desk | isuru, **Ravishan** | — |
| 5 END USER / 14 Agent Chat / 18–19 Ticketing | dale, test, test2 / alex / randima, TicketingAdmin, Wasantha, Dulmi, Ravihansi | — |

Two facts that change the plan: **the only enabled Accountant is `t1`** — if that is a test login, no real accountant has an enabled account yet, and Accounts must enable the right user (or confirm `t1`) before the grant. And **the approvals already in the records were made by "Ravishan" and "super admin"**; Ravishan's login sits in group 28 (Transport & Crew desk), which holds no payment-approval menu, while groups 21 Management and 25 Admin have no enabled user at all. So "management approval" today is in practice super admin plus Ravishan. Avenir must name the approver(s).

## 3. Permission matrix (initial controlled rollout) — one screen

| New menu (Settings → Group Privileges) | System Administrator (super admin) | Accountant (t1 — see note) | Auditor (audit firm, nethanjana, Nipuna) | Management approver — **group to be named** |
|---|---|---|---|---|
| 244 Tour payment approval (management) | ✓ already | — | — | ✓ |
| 245 Supplier Payables | ✓ already | ✓ | ✓ **read-only** (batch, initiate and Paid each need their own menu, so the page shows no action buttons) | ✓ |
| 246 Supplier payment batch — create (management) | ✓ already | — | — | ✓ |
| 247 Bank payment initiation (accountant) | ✓ already | ✓ | — | — |
| 248 Bank payment approver (Paid) | ✓ **only** — separate from the initiator | — | — | — |
| 249 Avenir Bank Accounts (master) | ✓ **only** — this menu also unlocks the ledger-mapping Save on Settings, so it stays with System Administrator for now | — (can still *view* the bank-account list through 247) | — | — |
| 250 Supplier Bank Details (enter / update) | ✓ already | ✓ | — | — |
| 251 Supplier bank verification | ✓ **only** — the person who enters bank details does not verify them | — | — | — |
| Groups that receive **nothing**: 25 Admin (no users), 21 Management (no users, unless chosen as the approver group), 13 Operational, 26 Quotation Desk, 28 Transport desk, 15 Maintenance, 5/14/18/19/24 | | | | |

Daily USD Rate (existing menu 217) stays with System Administrator and Admin as today; the Paid approver is the one who needs it, and that is System Administrator in this matrix.

Options for the management approver column (Avenir chooses):
- **(a) Recommended:** create a profile "Payment Approver" (Settings → Group Profiles), grant it 244 + 245 + 246, and move the named user(s) into it. If the approver is Ravishan, he also loses group 28's transport menus unless those are allowed to the new profile as well.
- **(b)** Use the existing empty group 21 "Management" (it holds only the *Driver advance* menu) and move the named user(s) into it — same caveat about their other menus.
- **(c)** Grant 244/245/246 to group 28 — rejected for a tight matrix, because isuru would receive approval and batch rights too.

## 4. Exact click sequence (only after approval, in this order; users sign out and in afterwards)

1. **Accounts (chart)** — Accounts → Manage Accounts → *Other Income* → *Foreign Exchange Gain-Loss* → *Foreign Exchange Gain-Loss* → **Create New Account** → name `Realised Exchange Gain` → Save. Then Manage Accounts → *Expenses* → *Expenses* → *Finance Charges* (or the new heading Accounts chose) → **Create New Account** → `Realised Exchange Loss` → Save. Nothing for Bank Charges.
2. **Mappings** — Tour Payment Dashboard → **Settings** → Ledger mapping: Gain = Realised Exchange Gain; Loss = Realised Exchange Loss; Bank charges = `Bank Charges 4-8-37-10818` → **Save mapping** (page shows 3 of 3 mapped; audit row `ledger_mapping_saved`).
3. **Privileges** — Settings → Group Privileges → choose profile → Filter → **Allow** on each menu per the matrix: Accountant → 245, 247, 250 (under *Pilot Report* and *Master Files*); Auditor → 245; the approver profile → 244, 245, 246. Nothing for Admin, Management (unless chosen), Operational, Quotation Desk, Transport desk.
4. **Verification (me, read-only)** — `tpd_meta` mapping keys, `account_posting` rows, `user_privileges` for menus 244–251 against this matrix, the Settings page, and one Supplier Payables view as the Auditor to confirm no action buttons.

## 5. Open points for Avenir before anything is clicked

1. Gain account: new account under 8-35-111, or reuse 8-35-111-10780?
2. Loss account heading: Finance Charges (37) or a new "Exchange Loss" heading under Expenses?
3. Is `t1` the real accountant login, or should Nimesha / Prashanth / another user be enabled first?
4. Who is the management approver, and option (a), (b) or (c)?
5. Does Accounts want the Avenir Bank Accounts master (249) now (it carries the mapping right), or leave it with System Administrator for the rollout?
