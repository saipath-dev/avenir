# Step 2, Daily Bank Reconciliation: sample comparison of opening balances (provisional)

**Revised 11 Oct 2026. Scope corrected.**

- Step 2 is to prepare and test the Daily Bank Reconciliation process on the development/test system.
- It is **not** Avenir's actual bank reconciliation. That will be done later, once the workflow is complete and the real data has been entered.
- This note compares one sample bank statement with the development copy of the book. The copy stops on 8 Sep 2026.
- Every difference below is provisional and is labelled **Unreconciled — insufficient data**. None of them is a loss, a confirmed missing receipt, a duplicate payment or a system error.
- Nothing was posted, no reconciliation flag was changed and nothing on the live system was touched.

## 1. Commercial Bank LKR current account (ending 0620), 1 Sep 2026: bank and book kept separate

| 1 Sep 2026 | LKR | Source |
|---|---:|---|
| **Bank opening balance** | **2,749,797.37 Cr** | sample statement, 1–30 Sep 2026, page 1 |
| **Book opening balance** | **−31,745,432.10** (ΣDr − ΣCr) | development copy, ledger 1-6-8-10380, rows dated before 1 Sep (1,647 rows) |
| **Difference (bank − book)** | **34,495,229.47** | **Unreconciled — insufficient data** |

Neither figure was adjusted or posted.

### Observations, to check once the real data has been entered

These are not findings and are not confirmed. Each may have an ordinary explanation, such as an incomplete copy, entries made in another account or later, or a different accounting practice.

1. **Receipts.** Book receipts in the copy are lower in July (4.2 m) and August (3.0 m) than in April–June (12–22 m a month).
2. **Dates.** Five entries in the copy carry dates after 1 Sep, although they were entered before it. Together they come to +3,100,980. Two of them are dated 2525 and one 2030.
3. **Duplicate-looking entries.** Some vouchers appear twice in the ledger copy, together 144,050 before 1 Sep. They are not confirmed as duplicates.
4. **Bank Control.** Some payments from this bank account are recorded under 1-6-8-9750 "Bank Control". This may be the intended practice.
5. **Bank charges.** Charges on the statement are not found in the copy.
6. **Opening journal.** The financial-year opening journal of 497,850.38 has not yet been agreed to a bank balance.

## 2. Other bank accounts in the book copy (1 Sep 2026), for set-up only

| Ledger account | Book opening (LKR) | Bank statement |
|---|---:|---|
| 1-6-8-10898 Com Bank USD Account | −14,308,953.43 | not yet entered |
| 1-6-8-10998 Pan Asia Bank LKR Account | 2,935,163.50 | not yet entered |
| 1-6-8-10999 Pan Asia Bank USD Account | 0.00 | not yet entered |
| 1-6-8-11000 Pan Asia Bank YEN Account | 0.00 | not yet entered |

The reconciliation workflow sets up each bank account with its ledger mapping, and an authorised user has to confirm each mapping. No mapping has been assumed.

## 3. Two separate matters, recorded for later investigation

Neither of these blocks the development of the reconciliation process.

| Matter | Amount (LKR) | Status |
|---|---:|---|
| Overall ledger imbalance in the development copy (ΣDr − ΣCr, all accounts) | 449,143.20 | separate; for later investigation |
| Bank-to-book opening difference, Com Bank LKR, 1 Sep 2026 (sample) | 34,495,229.47 | Unreconciled — insufficient data |

## 4. Historical statements

Historical statements are not requested now. The actual reconciliation, with real statements and a fresh copy of the live data, will follow once the workflow is complete and you have approved it.
