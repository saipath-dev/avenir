# Avenir Holidays — department structure and test head accounts

Development system (`C:\wamp64\www\avenir`), 11 Oct 2026. Set up through the application's own screens (Settings → Group Profiles / Group Privileges, Staff Registration, User Accounts). Nothing was deleted; existing logins, profiles and their grants are untouched.

In Avenir a "department" is carried by the **group profile** (the permission set a login belongs to). The six departments you listed map to seven profiles, because Finance & Approvals keeps its two permissions separate. The separation-of-duties checks (r5) compare *usernames*, never profiles, so two people in the same department are still separated, and one person can never approve, batch, initiate or finally authorise their own request.

## Departments → profiles → test logins

| # | Department | Profile (id) | Test staff record (emp no) | Test username |
|---|---|---|---|---|
| 1 | Tour Reservations | **Tour Reservations** (31) | TEST HEAD — Tour Reservations (200146) | `TestTourRes` |
| 2 | Hotel Reservations | **Hotel Reservations** (32) | TEST HEAD — Hotel Reservations (200147) | `TestHotelRes` |
| 3 | Transport & Guide Handling | **Transport & Guide Handling** (33) | TEST HEAD — Transport & Guide Handling (200148) | `TestTransport` |
| 4 | Finance & Approvals | **Finance & Approvals — Management Approver** (29) | TEST HEAD — Finance & Approvals (Management Approver) (200149) | `TestFinApprover` |
| 4 | Finance & Approvals | **Finance & Approvals — Bank Authoriser** (35) | TEST — Finance & Approvals (Bank Authoriser) (200150) | `TestBankAuth` |
| 5 | Accounts | **Accounts** (30) | TEST HEAD — Accounts (200151) | `TestAccounts` |
| 6 | Pilot / Operations Allocation | **Pilot / Operations Allocation** (36, new) | TEST HEAD — Pilot / Operations Allocation (200152) | `TestOperations` |

All seven logins use the password you gave in the chat; it is not written anywhere. Each login is **active** (Access = 1). Every head should change the password at first sign-in, and when the real staff are appointed each person gets their own login under the department's profile — a shared departmental login would make the audit record useless, as you said.

Also still available, unassigned: **Auditor (read-only)** (34) and **System Administrator** (1).

## What each profile can open

**1 · Tour Reservations (31) — 32 menus.** Tour Inquiry · New Quotation · Tour Quotation · Tour Costing · Pricing · Quotation Change Log · Tour Calendar · Tour Documentations · Tour Timeline Preview · Booking · Booking Dashboard · **Tour Management (confirmations)** · **Confirmed Tour Report** · Arrival Notification · Tour Board · Tour Dashboard · Working Dashboard · Tour Search · the rate masters it quotes from (Hotel Rates (new), Hotel Rate, Hotel Rate Report, Excursion Rates, Transport Rates, Mileage & Allowances, Place to Visit / Entrance Fee Master, Meal Master, Free Visits, Paid Activities & Services, Tour Photos) · **Tour Payment Dashboard (restaurant / other service payment requests)** · Open Chat. *No approval, batch, initiation or Paid.*

**2 · Hotel Reservations (32) — 20 menus.** Hotel Bookings · Hotel Reservation · Hotel Confirmation · Hotel Management · Hotel Rate Confirmation · Hotel Rates (new) · Hotel Rate · Room Type · Hotel Rate Report · Hotel Incomplete Work · Reservation Card · Itinerary List · Supplier · Supplier Report · Tour Board · Tour Dashboard · Working Dashboard · Tour Search · **Tour Payment Dashboard (hotel payment requests)** · Open Chat.

**3 · Transport & Guide Handling (33) — 31 menus.** Transport Operations · Transport Rates · Mileage & Allowances · Mileage override · Crew Roles & Quotation Costing · Drivers & Guides · Driver advance · Driver Advance · Driver Advance Request · Driver Expense · Driver Logsheet · Driver Settlement · Driver Settlement Request · Driver Log Report · Transport · Transport Portal · Vehicle · Driver · Driver Report · Consignee · Extra Kilometers · Routing Registry · Itinerary List · **Tour Documentations** · Ticket Stock · Tour Board · Tour Dashboard · Working Dashboard · Tour Search · **Tour Payment Dashboard (driver-advance and transport payment requests)** · Open Chat. *Moved to department 6:* Driver Allocation, Driver Allocated Tours, Crew replacement.

**4a · Finance & Approvals — Management Approver (29) — 31 menus.** Payment approval · Tour payment approval (management) · Supplier Payables · **Supplier payment batch — create** · Finalized Bank Check Payment · Daily USD Rate · Hotel Rate Approval · Tactical / Early Bird rate selection · Tour Board · Tour Desk Admin · Pricing · Tour Inquiry · Tour Dashboard · Working Dashboard · Tour Search · Confirmed Tour Report · Payment Forecast Report · Supplier Report · the legacy voucher-control reports (Payment Release, Voucher Cancellation, Voucher Report, New Voucher Report, Voucher Audit, Cancelled Voucher Report, Trial Balance, Balance Sheet, Profit and Loss, Ledger Accounts, Bank Statement) · Tour Payment Dashboard · Open Chat. *No initiation, no Paid.*

**4b · Finance & Approvals — Bank Authoriser (35) — 6 menus.** **Bank payment approver (Paid)** · Bank Payment Check Report · Finalized Bank Check Payment · Supplier Payables · Daily USD Rate · Tour Search. *Nothing else — not an approver, not an accountant, not an administrator.*

**5 · Accounts (30) — 65 menus.** Bank Payment Check Report · **Bank payment initiation (accountant)** · Finalized Bank Check Payment · Supplier Payables · Supplier Bank Details · Driver & Guide bank details · Daily USD Rate · the accounts entry screens (Manage Accounts, Creditor / Debtor Payment, Journal Entry, Voucher Payments, Payment Amendment, Debtor Invoice, Advance Payments, **Bank Reconciliation (legacy)**, Utility Bill Payment, Debit / Credit Note, Service Provider Invoice / Payment, Utility Bill Entertainment, USD Withdrawal) · all 37 accounts reports (incl. Bank Reconciliation Details / Summary, Bank Statement) · Supplier · Supplier Report · Tour Search · Tour Payment Dashboard · Open Chat. *No approval, no batch creation, no Paid, no bank verification, no Avenir Bank Accounts master.*

**6 · Pilot / Operations Allocation (36, new) — 22 menus.** Transport Operations · **Driver Allocation** · **Crew replacement** · Driver Allocated Tours · Pending Tours · Ongoing Tours · Ground Handling · Tour Details View · Itinerary List · Transport · Transport Portal · Drivers & Guides · Vehicle · Driver · Driver Report · Driver Logsheet · Driver Log Report · Tour Board · Tour Dashboard · Working Dashboard · Tour Search · Open Chat. *No payment menus: driver advances are requested by Transport & Guide Handling.*

## Separation of payment duties, by profile

| Step | Who |
|---|---|
| Make a payment request (hotel, transport, restaurant / other, driver advance) | Tour Reservations · Hotel Reservations · Transport & Guide Handling |
| Approve / reject; create the supplier batch or bulk payment; suggest the paying account | Finance & Approvals — Management Approver |
| Enter supplier bank details; initiate the bank payment (date, rate, reference, charges) | Accounts |
| Verify supplier bank details; Avenir bank-account master; ledger mappings | System Administrator |
| Final bank authorisation — Paid | Finance & Approvals — Bank Authoriser |

Until r5 is installed, the Tour Payment Dashboard menu is both the read access and the request right, so Management Approver and Accounts could technically request (fully audited). r5 adds the separate "Tour payment requests (make / cancel)" privilege and the server-side same-user guard; at r5 Setup that privilege is granted to profiles 31, 32 and 33 only.

## Side effects to know about

- The seven test staff records were created through Staff Registration, which (as for every employee) opened a staff ledger account for each in the employee ledger group **4-8-9-…** (the next numbers the system assigned), named after the test record. They carry no transactions. Deactivate or delete them with the test records when the real staff are appointed.
- Menu grants are recorded by the application; nothing was written to the database directly.

## When the real staff arrive

1. Staff Registration → the person (real employee number).
2. Settings → User Accounts → their own username, the department's profile, their own password.
3. Finance & Approvals: one login with Management Approver, a different person with Bank Authoriser.
4. Remove the seven TEST logins and staff records (and their 4-8-9 accounts).
