# Accounts Action Sheet — before the Daily Bank Reconciliation can start (12 Oct 2026)

**For Accounts. Please confirm or explain each item and return the sheet to Shantha.**

**Before you start**

- The figures come from the **development copy of the ledger**, which stops on **8 Sep 2026**. Please check each one against **live**.
- Nothing has been corrected. **Any correction is decided by Accounts and approved by Shantha separately.** Claude does not post or correct ledger entries.
- **Do not give bank account numbers to Claude.** Account numbers go only on the bank documents, to the person who enters them (item F).

---

## A. September 2026 and the 30 September closing balance — per bank account

| Bank account (ledger) | Is September 2026 final in the ledger? (yes / no — by whom, date) | Bank closing balance on 30 Sep 2026 (from the bank statement) | Statement for 1–30 Sep available? |
|---|---|---|---|
| Com Bank LKR Account `1-6-8-10380` | | | |
| Com Bank USD Account `1-6-8-10898` (USD amount and the LKR value used) | | | |
| Pan Asia Bank LKR Account `1-6-8-10998` (no entries after 25 Aug in the copy) | | | |
| Pan Asia Bank USD `1-6-8-10999` / YEN `1-6-8-11000`: still unused? (yes / no) | | — | — |

## B. The eight one-sided entries — together 449,143.20 (debits minus credits)

A one-sided entry is one whose debits and credits do not match.

| Entry | Date | What the ledger shows | Imbalance | Please explain / proposed action |
|---|---|---|---:|---|
| 25915 | 1 Apr 2026 | Com Bank LKR **Dr 497,850.38** only. "Journal entry – Com bank opening" (entered 8 Jul 2026, super admin) | +497,850.38 | |
| 25918 | 1 Apr 2026 | Com Bank USD **Dr 161,135.32** only. "Journal entry – Com bank USD opening" (entered 8 Jul 2026, super admin) | +161,135.32 | |
| 27725 | 21 Aug 2026 | Sigiriya Village Hotel **Cr 134,257.50** only. "Hotel Reservation 1491" (entered 13 Aug 2026) | −134,257.50 | |
| 27724 | 23 Aug 2026 | Morven Colombo **Cr 44,752.50** only. "Hotel Reservation 1491" (entered 13 Aug 2026) | −44,752.50 | |
| 28358 | 2 Sep 2026 | Araliya Green Hills **Cr 35,832.50** only. "Hotel Reservation 1959" (entered 25 Aug 2026) | −35,832.50 | |
| 25804 | 7 Jul 2026 | Gimanhala lunch voucher Dr 10,000.00; Com Bank LKR Cr 5,000.00 | +5,000.00 | |
| 24654 | 10 Jun 2026 | Gimanhala lunch voucher Dr 5,000.00; Com Bank LKR Cr 6,000.00 | −1,000.00 | |
| 24734 | 26 May 2026 | LUNCH Dr 6,000.00; Oak Ray Anchorage lunch voucher Cr 5,000.00 | +1,000.00 | |
| | | **Total** | **+449,143.20** | |

## C. The two bank opening journals (part of B)

| Entry | Account | Amount | Questions |
|---|---|---:|---|
| 25915 | Com Bank LKR | 497,850.38 | 1. Does it equal the bank balance on 31 Mar 2026?<br>2. What should its other side be?<br>3. Was it entered once only? |
| 25918 | Com Bank USD | 161,135.32 | 1. Does it equal the bank balance on 31 Mar 2026?<br>2. What should its other side be?<br>3. Was it entered once only? |

## D. The receipt posted to account code "0" — 15,868,192.50

| Entry | Date | What the ledger shows | Questions |
|---|---|---|---|
| 25058 | 15 Jun 2026 | **Dr 15,868,192.50 to code "0"** (no such account); Cr Debtor Control Account 15,868,192.50. "STW MAY 2026 – debtor payment", Nimesha, entered 19 Jun 2026. | 1. Which bank account received this money?<br>2. On what date?<br>3. Is it on that bank's statement? |

Until this is answered, that bank's book balance is understated by this amount. It may explain part of the 34,495,229.47 provisional difference on Com Bank LKR.

## E. Entries dated in the future or with an impossible year

| Entry | Dated | Account(s) | Amount | Entered | Correct date? / action |
|---|---|---|---:|---|---|
| 24553 "Journal entry – USD to LKR" | **24 Oct 2026** | Com Bank LKR Dr / Com Bank USD Cr | 3,020,000.00 | 10 Jun 2026, Nimesha | |
| 24568 "Journal entry – air tickets sale" | **13 Nov 2026** | Com Bank LKR Dr | 209,000.00 | 10 Jun 2026, Nimesha | |
| 11851 "Journal entry – STW payments" | **20 Mar 2029** | Bank Control Cr | 4,788,575.00 | 18 Jul 2025, Jacob | |
| 5915 / 5916 "Voucher payment" | **1 Jan 2030** | Com Bank LKR Cr; Bank Control Dr/Cr | 178,020.00 | no entry time, Niroshan. **Ticked as reconciled on the old screen.** | |
| 25668, 25669 "Journal entry – fuel charges" | **16 Apr 2525** | Com Bank LKR Dr | 2 × 25,000.00 | 6 Jul 2026, Nimesha | |

If the first day is 1 Oct or 1 Nov 2026, entries 24553 and 24568 fall inside the reconciled period, so their dates matter first.

## F. The real bank-account documents

**Please gather and verify, for each bank account in A:**

- a bank letter or statement header showing the bank, branch, account name, account number, currency and account type;
- the statement covering the first reconciliation day;
- one CSV export from web banking, to check the format.

**How the documents are used**

- They go to the **authorised administrator** who enters them on "Avenir Bank Accounts". **Accounts then checks the entry against the documents.**
- For the format check, Claude will use a **sanitised copy** of the CSV on the isolated test instance only. The column layout is kept; account numbers, names, references and amounts are replaced.

## G. Daily USD rate — responsibility and source

| Question | Answer |
|---|---|
| Who confirms the daily USD rate each working day (name / role)? | |
| Source: the system's daily USD rate, the bank's rate on the statement, or the Central Bank rate? | |
| Deadline each day (before the reconciliation is prepared)? | |
| JPY: needed only once the YEN account is used. Which source then? | |

The development copy holds only 7 daily USD rates (19 Aug – 7 Sep 2026). A rate must exist for every reconciled day of a USD account.

---

**Accounts sign-off:** name ______________________  date ____________  checked against live: yes / no
