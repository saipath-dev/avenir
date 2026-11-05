# Avenir Holidays — new-team role profiles (ready, unassigned)

Development system, 11 Oct 2026. Seven roles. Six new profiles were created with their menus granted and **no user assigned**; System Administrator is the existing group 1 (every menu). Existing staff logins and their groups are untouched; nothing was deleted. When the new employees are appointed, each account is created in Settings → User Accounts and given one of these profiles.

## Separation of payment duties (who can do what)

| Step | Profile | Menu |
|---|---|---|
| Make a payment request (hotel, activity, driver advance, expense) | Hotel Reservations Desk · Transport Desk | Tour Payment Dashboard (200) |
| Approve / reject a request; review hotel rate | Management / Payment Approver | Tour payment approval (244) · Payment approval (216) |
| Create the supplier batch, issue the voucher, suggest the paying account | Management / Payment Approver | Supplier payment batch — create (246) |
| Enter supplier bank details | Accountant | Supplier Bank Details (250) |
| Verify supplier bank details | System Administrator only | Supplier bank verification (251) |
| Initiate the bank payment (payment date, rate, bank reference, charges) | Accountant | Bank payment initiation (247) |
| Final bank authorisation — mark Paid | System Administrator only | Bank payment approver (248) |
| Confirm today's USD rate | Management · Accountant · System Administrator | Daily USD Rate (217) |
| Avenir bank-account master and the ledger mappings | System Administrator only | Avenir Bank Accounts (249) |
| See Supplier Payables and batches (read-only) | Auditor · Accountant · Management | Supplier Payables (245) |

No profile holds both approval (244/246) and initiation (247); none but System Administrator holds Paid (248). Two things for your decision are at the end.

## The profiles

### 29 Management / Payment Approver — 31 menus

- **Payments:** Tour Payment Dashboard · Payment approval · Tour payment approval (management) · Supplier Payables · Supplier payment batch — create (management) · Finalized Bank Check Payment · Daily USD Rate
- **Oversight:** Tour Board · Tour Desk Admin · Pricing · Hotel Rate Approval · Tactical / Early Bird rate selection · Tour Dashboard · Working Dashboard · Confirmed Tour Report · Payment Forecast Report · Supplier Report · Tour Inquiry · Tour Search
- **Legacy voucher control:** Payment Release (legacy authorisation) · Voucher Cancellation · Voucher Report · New Voucher Report · Voucher Audit · Cancelled Voucher Report · Trial Balance · Balance Sheet · Profit and Loss · Ledger Accounts · Bank Statement
- Open Chat

### 30 Accountant (New Generation) — 65 menus

- **Payments:** Bank Payment Check Report · Finalized Bank Check Payment · Tour Payment Dashboard · Supplier Payables · Bank payment initiation (accountant) · Supplier Bank Details · Driver & Guide bank details · Daily USD Rate
- **Accounts:** Manage Accounts · Creditor Payment · Debtor Payment · Journal Entry · Voucher Payments · Payment Amendment · Debtor Invoice · Advance Payments · Bank Reconciliation (legacy) · Utility Bill Payment · Debit Note · Credit Note · Service Provider Invoice · Utility Bill Entertainment · USD Withdrawal · Service Provider Payment · Supplier · Supplier Report
- **Accounts reports:** all 37 (Balance Sheet … Account Reservation Card, Pending Voucher Report included)
- Tour Search · Open Chat
- *Not held:* approval, batch creation, Paid, bank verification, Avenir Bank Accounts master, Voucher Cancellation, Payment Release, Year End, Backup.

### 31 Quotation Desk (New Generation) — 29 menus

- **New Generation:** Tour Inquiry · Pricing · Tour Board · Hotel Rates (new) · Excursion Rates · Transport Rates · Mileage & Allowances · Place to Visit / Entrance Fee Master · Meal Master · Free Visits · Paid Activities & Services · Tour Photos
- **Legacy quoting:** Tour Quotation · Tour Costing · New Quotation · Tour Search · Tour Dashboard · Working Dashboard · Quotation Change Log · Tour Calendar · Tour Documentations · Tour Timeline Preview · Booking · Booking Dashboard · Arrival Notification · Place to Visit · Hotel Rate · Hotel Rate Report
- Open Chat · *no payment menus*

### 32 Hotel Reservations Desk — 20 menus

- **New Generation:** Hotel Bookings · Hotel Rates (new) · Hotel Incomplete Work · Tour Board · Tour Payment Dashboard (to request hotel payments)
- **Legacy hotels:** Hotel Reservation · Hotel Confirmation · Hotel Management · Hotel Rate Confirmation · Hotel Rate · Room Type · Hotel Rate Report · Supplier · Supplier Report · Reservation Card · Itinerary List · Tour Search · Tour Dashboard · Working Dashboard
- Open Chat

### 33 Transport Desk (New Generation) — 33 menus

- **New Generation:** Transport Operations · Transport Rates · Mileage & Allowances · Mileage override · Crew Roles & Quotation Costing · Crew replacement · Drivers & Guides · Driver advance · Ticket Stock · Tour Board · Tour Payment Dashboard (to request driver advances)
- **Legacy transport:** Driver Allocation · Driver Advance · Driver Advance Request · Driver Expense · Driver Logsheet · Driver Settlement · Driver Settlement Request · Driver Allocated Tours · Transport · Transport Portal · Driver Log Report · Vehicle · Driver · Driver Report · Consignee · Extra Kilometers · Routing Registry · Itinerary List · Tour Search · Tour Dashboard · Working Dashboard
- Open Chat

### 34 Auditor (read-only) — 45 menus

- **Payments (read-only):** Supplier Payables (no action buttons without 246/247/248) · Finalized Bank Check Payment
- **Reports only:** the 35 account reports that do not edit (Pending Voucher Report, Edit Journal Entry, Voucher Cancellation, Payment Release, Tour Amendment Approval / Finalize deliberately excluded) · Supplier Report · Hotel Rate Report · Driver Report · Confirmed Tour Report · Payment Forecast Report · Usage Note Report · Stock Summary Report
- *No chat, no masters, no accounts entry screens.*

### 1 System Administrator — existing group, every menu (173 granted today)

## Two decisions for you

1. **Management and Accountant hold the Tour Payment Dashboard (200)** because approvals and initiations are done on that page, and 200 is also the request privilege. So a manager could technically request and approve the same item, and an accountant could request and initiate one (both fully audited). r5 can close this with a **same-user guard** (a user can never approve, batch, initiate or mark Paid what they themselves requested or initiated) — I recommend adding it; until then the separation is by profile and by audit.
2. **Final bank authorisation (Paid) stays with System Administrator.** If you want a dedicated **Bank Authoriser** profile (248 + Bank Payment Check + Finalized, nothing else) so that System Administrator stays a purely administrative role, say so and I'll add it ready and unassigned.

Everything else (the exact menus per desk) can be adjusted when the new staff details arrive.
