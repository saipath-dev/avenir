# Tour Payment Dashboard — design-only preview (11 Oct 2026)

**Status: preview only. Nothing on the working dashboard has been changed. All figures, tours, passengers, suppliers and users on the page are representative dummy values.**

Open `tour-payments-preview.html` in any browser (it is self-contained; the font loads from Google Fonts, everything else is inline). The "Viewing as" switch in the top bar only exists in the preview: it shows how the same page looks to Operational staff, Management, Accounts and the bank approver. Screenshots are in `screenshots/`.

## What the page is made of, and whom each part helps

1. **Control-centre header** — "Tour Payments", a one-line purpose, the tour search (ID, passenger or voucher number), the date range, "last refreshed" with a Refresh button, and "+ New payment request" for requesters. Dashboard, Back and User Manual stay in the top bar.
   *Operations* opens a tour in one step. *Accounts* sees how fresh the figures are.

2. **Attention banner** — one sentence that says how many payments need attention today and why (due within 48 hours, overdue, waiting for the rate). "Show them" filters the queue.
   *Management* gets the day's priorities without reading the table.

3. **Eight summary cards** (click to filter): Action required · Awaiting approval · Supplier payables · Bank action · Waiting for rate · Overdue or blocked · Paid this month · Due in range (LKR and USD at approval rates, marked provisional until Paid). Colour stripe = meaning: purple work, amber waiting, teal done, red only for overdue/blocked.
   *Management* reads exposure and bottlenecks; *Accounts* reads what is waiting at the bank; *Operations* sees whether their requests moved.

4. **Payment workflow** — six numbered stages, Requested → Approved → Supplier Payables → Batched → Bank Check → Paid, each with its count, a one-line meaning and the desk that owns it. Clicking a stage focuses the queue on it. The chip on the right shows whether today's USD rate is confirmed, because Paid on USD vouchers depends on it.
   Everyone sees where a payment sits and who holds it.

5. **Tabs** — Action required · Upcoming tours · Supplier payables · Payment batches · Completed payments, each with a count.

6. **Work queue (Action required)** — one row per payment item that someone must act on: tour ID and passenger, departure with passengers and a countdown, the payment item and its type, amount with currency (provisional LKR for USD items), due date (amber "Today", red only when overdue), a **labelled status chip**, the responsible desk and person, and **one next-action button**. The button is active only for the desk whose turn it is; everyone else sees "Waiting for Accounts / Management / bank approver", so role-based access is visible on the page, not only enforced behind it. Queue filters: search, period, priority (overdue & blocked, due within 48 hours, waiting for rate, my desk only).
   *Accounts* works top-down through its own desk's items; *Management* sees approvals and batches to create; *Operations* sees unrequested payments.

7. **Upcoming tours** — one row per tour: dates and countdown, pax, Hotels and Activities as "2 of 4 paid" progress bars with a hover tooltip (paid / in process / waiting), Driver advance as a labelled chip, amount due, stage, responsible desk, Open tour.
   This replaces the small coloured squares. *Operations* checks a tour's readiness at a glance.

8. **Supplier payables** — approved items grouped by supplier with bank-verification status, the tours included, and the proposed bulk-payment controls: **Select all eligible (n)**, selected count and total, excluded items with the reason (on hold, bank not verified), and "Review & confirm payment". This is the r5 behaviour you described (one batch, one bank transfer, one voucher per item); the preview only shows the control layout.

9. **Payment batches** — each batch with voucher, supplier, amount, stage chip, paying bank account and bank reference, and the next action for the right desk.

10. **Completed payments** — read-only table: Paid date, voucher, supplier, tours, amount, rate, bank charges, bank, "reconciled" chip. Historical records appear exactly as posted.

11. **Tour detail panel** — inline on wide screens (1600 px and up), a slide-over drawer on laptops, tablets and phones: payment timeline (who, when, amounts, rates), supplier breakdown with "2 of 4 paid", approval history, voucher and batch references, paying bank account, supplier bank verification, approval rate and today's rate status, bank charges (batch charge, this tour's share, mapped ledger, reconciliation), audit trail, and the **next permitted action** for the current user with the reason when it is someone else's turn.

12. **Status legend** — every status the current dashboard uses keeps its name (Fully Paid, Pending Payment, Advance Paid, Payment on Process, Waiting Rate Confirmation, Not Paid, Payment Not Requested, and the workflow statuses Requested → Awaiting Management Approval → Approved for Payment → Payment Voucher Issued → Bank Payment Initiated → Fully Paid → Supplier Notification Pending / Supplier Notified, plus Partly Paid, On Hold, Requires Checking, Rejected, Reversed, Cancelled). Red is used only for Overdue, Blocked, Rejected and Reversed.

## Responsive behaviour

Desktop 1920: queue and detail side by side. Desktop 1440 / laptop 1280: full-width queue, detail opens as a drawer. Tablet 834: cards stack to two rows, workflow in three columns, each queue row becomes a four-column card. Phone 390: two-column cards, workflow in two columns, each queue row becomes a two-column card with its next-action button; nothing scrolls sideways except the read-only completed-payments table.

## What does not change

No payment calculation, accounting rule, permission, database value, route or workflow step. Every existing capability (request, approve, batch, initiate, Paid, remittance, documents, expenses, classic table view, print) keeps its place; the preview only shows the layout of the main page.

## Open for your decision

- Keep the attention banner, or fold it into the first summary card?
- Inline detail panel only on wide screens (as shown) or always a drawer?
- Red stripe for "Action required" (as in your reference) or purple (as shown, keeping red for overdue/blocked only)?
