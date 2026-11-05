# Bank Reconciliation — read-only findings and decision table (12 Oct 2026)

**Scope**

- **Source of the findings:** at your choice, the read-only examination used **development only** (`localhost/avenir`, database `avenir`).
- **Development's data is a copy of live** that stops on **8 Sep 2026**. Every live point below is therefore **"not verified on live"**.
- **What I did on live:** before you chose, I opened live's sign-in page (no sign-in) and downloaded 9 public script and image files to compare them. I did nothing else on live.
- **What I did on development:** I ran SELECT queries in phpMyAdmin and read files. Nothing was changed.
- **Not done:** no mapping, first day or bank account was entered; no old tool was run; nothing was imported, posted, updated or deleted.

---

## 1. Read-only findings

### 1.1 Files, schema and bank-account master

| Item | Development | Live |
|---|---|---|
| Daily Bank Reconciliation | r3 installed; schema version 2; 13 `brc_…` tables, all empty | Not verified |
| Code it depends on | The reconciliation uses the Tour Payments foundation (the `TourPayments` Audit / BankAccounts / Perms libraries, the tables `tpd_audit`, `tpd_meta`, `tpd_bank_account`, and the "Avenir Bank Accounts" screen, menu 249). On development this came with tf-paydesk r4/r5. | **Probably absent** (see next row). A live package must include this foundation or it cannot run. |
| Public files | Help widget, tour-mail script, e-mail logo and PDF fonts present; `plugins.js` carries the sign-out fix | Help widget, tour-mail script, e-mail logo and fonts are **missing (404)**; `plugins.js` is the June 2024 file. **Live is well behind development.** |
| Bank-account master (`tpd_bank_account`) | **Empty** (0 rows) | Not verified (the table probably does not exist yet) |
| Engines | Reconciliation and payment tables: InnoDB (24 tables). Ledger `account_runing`, `daily_usd_rate`, `a_log`: MyISAM. | Not verified |

The live `plugins.js` differs from development's original in one line only: `base_url` is `/` on live and `/avenir/` on development. **A live package must keep live's own line.**

### 1.2 Ledger accounts and activity (development copy, to 8 Sep 2026)

| Ledger account | Rows | Last ordinary date | Book balance at 31 Aug (LKR) | Proposed (recommendation only) |
|---|---:|---|---:|---|
| Com Bank LKR `1-6-8-10380` | 1,844 | 7 Sep 2026 (192 rows in Sep) | −31,745,432.10 | Map |
| Com Bank USD `1-6-8-10898` | 23 | 30 Sep 2026 (entered in July) | −14,308,953.43 | Map |
| Pan Asia LKR `1-6-8-10998` | 32 | 25 Aug 2026 | 2,935,163.50 | Map |
| Pan Asia USD `1-6-8-10999`, YEN `1-6-8-11000` | 0 | — | 0.00 | Wait until used |
| Bank Control `1-6-8-9750` | 125 | 31 Aug 2026 | −6,833,544.62 | Do not map |
| Deposits in Transit `1-6-112-10782`, Advance deposit `1-6-112-11024` | 0 | — | — | Do not map |

**September cannot be confirmed as final from development**, because the copy ends on 8 Sep. Accounts must confirm it from live.

### 1.3 Unusual and future-dated rows (left unchanged)

| Entry | Account(s) | Dated | Amount | Entered | Note |
|---|---|---|---:|---|---|
| 24553 "Journal entry – USD to LKR" | 10380 Dr / 10898 Cr | **24 Oct 2026** | 3,020,000.00 | 10 Jun 2026, Nimesha | Both legs are bank accounts; probably a date error |
| 24568 "Journal entry – air tickets sale" | 10380 Dr | **13 Nov 2026** | 209,000.00 | 10 Jun 2026, Nimesha | |
| 5915 / 5916 "Voucher payment" | 10380 Cr; 9750 Dr/Cr | **1 Jan 2030** | 178,020.00 | no entry time, Niroshan | **Carries old-screen flags** (`bank_rec`, `bankst_flag`) |
| 25668, 25669 "Journal entry – fuel charges" | 10380 Dr | **16 Apr 2525** | 2 × 25,000.00 | 6 Jul 2026, Nimesha | Year typed wrongly |
| 11851 "Journal entry – STW payments" | 9750 Cr | **20 Mar 2029** | 4,788,575.00 | 18 Jul 2025, Jacob | |

- **If the first day is 1 Oct or 1 Nov,** entries 24553 and 24568 fall inside the protected period of Com Bank LKR (and 24553 of Com Bank USD). They would wait there as book items that never appear on a statement.
- **Accounts should decide** whether to correct their dates (by their own journal entries) before any first day is set.

### 1.4 E7 — the evidence

| Part | What the data shows |
|---|---|
| **449,143.20 imbalance** | Exactly **8 one-sided entries** (an entry whose debits and credits do not match):<br>• **25915:** Com Bank LKR opening journal, 1 Apr 2026, **+497,850.38**<br>• **25918:** Com Bank USD opening journal, 1 Apr 2026, **+161,135.32**<br>• three single-leg hotel-reservation credits: −134,257.50, −44,752.50, −35,832.50<br>• three small lunch / voucher lines: +5,000, −1,000, +1,000<br>Total 449,143.20. |
| **34,495,229.47 provisional difference** (Com Bank LKR, bank vs book at 1 Sep) | Candidates for **part** of it, not confirmed:<br>• a **receipt of 15,868,192.50** posted to account code **"0"** (15 Jun 2026, "STW May 2026 – debtor payment"), so its bank leg is missing;<br>• the one-sided opening journal above;<br>• bank payments recorded under Bank Control;<br>• bank charges not booked;<br>• the post-dated rows in §1.3.<br>The copy also stops on 8 Sep. |

**Both opening journals touch the bank accounts' opening balances**, so E7 bears directly on the first day.

### 1.5 The two old tools

| | **A. "Bank Not Reconciliation Record Insert"** (Settings → Manual Update) | **B. "Bank Req Data Transfer"** (Accounts, menu 184) |
|---|---|---|
| **Has it been run?** | **Yes, once: 21 Oct 2025, between 10:50 and 11:22.**<br>• It created **286 consecutive entry numbers (15947–16232)**: one per listed ID (285 IDs, one listed twice).<br>• Together they copy all **596 lines** into the ledger then current (FY 2025/26): the 594 lines of the listed FY 2024/25 entries, with the twice-listed entry copied twice.<br>• They are in the database copies of ~4 Nov and ~5 Dec 2025 (all 286), and 283 are in a copy taken during the run. | **No trace for any bank.**<br>• None of its candidate entries is in the current ledger or in the four 2025 copies.<br>• Its page was **never opened** between 27 Mar and 8 Sep 2026; the activity log starts on 27 Mar 2026. |
| **What it covered** | The FY 2024/25 bank items left unreconciled:<br>• Com Bank LKR 45 of 45<br>• Com Bank USD 10 of 11<br>• Pan Asia LKR 9 of 9<br>• Pan Asia USD 2 of 2<br>• YEN 1 of 1<br>• Bank Control 208 of 209 | **The same items Tool A already carried forward.** |
| **Since then** | The current ledger (FY 2026/27) has **no line dated before 1 Apr 2025**.<br>Manual Update was opened twice on 14 Jun 2026 (super admin), and no such lines appeared. **So it was not run again** (up to 8 Sep 2026). | The current ledger has **no "Brought Forward Balance 2025-03-31" rows**. A run now would add 2024/25 lines with nothing to offset them. |
| **Conclusion** | **It has served its purpose.**<br>A second run would copy the 596 lines again under new numbers, which would change the balance of every listed account (e.g. Com Bank LKR −3,000,332.05 net). | **No bank needs a supervised run**: not Com Bank LKR, Com Bank USD, Pan Asia LKR, USD or YEN, nor Bank Control.<br>Running it for any bank would duplicate items already carried forward. |
| **Not verifiable** | Live after 8 Sep 2026.<br>Whether live still has the 2024 database Tool A reads from (development does not, so it would fail there). | Dec 2025 – Mar 2026: the FY 2025/26 ledger is not in the development copy.<br>Live after 8 Sep 2026. |
| **Who can run it (development)** | System Administrator (1 user) and ticketing_admin (3 users) | System Administrator (1 user) |

---

## 2. Proposed retirement and protection of the two tools (development only — for approval; nothing done)

| Part | Design |
|---|---|
| **Files** (5; no database change) | • `LegacyGuard.php` gets a "retired tool" refusal and a token check.<br>• `ManualUpdateController.php`: `bank_recon_record_insert` refuses before anything else.<br>• `manual_update.php`: that one button and its click handler are replaced by a grey "retired" note. **The other Manual Update buttons are unchanged.**<br>• `BankUnrealisedValueController.php`: `process` refuses.<br>• `bank_unrealised_value_change.php`: the Process button is replaced by the same note.<br>Routes and menus are not changed, so an old open page gets the refusal, not an error. |
| **Security token** | Each page carries the application's security token, and both actions check it first.<br>• Missing or wrong token: **403** "Security token missing or expired — reload the page. Nothing was changed."<br>• The token check is **audited separately**, so forged or cross-site attempts are visible. |
| **Second-run prevention** (needed only if you later want a tool kept rather than retired) | • **A run-once marker** in `tpd_meta` (`legacy_run:<tool>[:<bank>]`).<br>• **A data check:** the tool refuses if the entries it would write are already in the ledger. Tool A's evidence is the 21 Oct 2025 copies; Tool B's is its entry numbers.<br>• It also refuses for any account with a confirmed mapping, **whatever the date**. |
| **Refusal messages** | **A:** "This one-off was completed on 21 October 2025 (entries 15947–16232) and has been retired. Running it again would copy the same 2024/25 entries into the ledger a second time. Nothing was changed."<br>**B:** "Bank Req Data Transfer has been retired. The 2024/25 unreconciled bank items were already carried forward on 21 October 2025, and the current ledger has no 31 March 2025 brought-forward balance to adjust. Nothing was changed."<br>Both are answered **409** and shown on the page. |
| **Audit trail** | Every attempt writes one `tpd_audit` row:<br>• action `legacy_tool_refused`, with the tool, the user and their id, the time, the IP, the bank (B) and the reason (retired / token / mapped / already run);<br>• `legacy_tool_run` would record an allowed run, with the lines written. |
| **Rollback** | `apply.py rollback` restores the 5 files from `before\<time>\`.<br>There is no schema or data change to undo. Audit rows stay, as intended. |
| **Tests** | 1. Cloud copy: refusal with and without token; audit row; ledger checksum unchanged; other Manual Update buttons still work.<br>2. Isolated test instance, with a TEST login given the two menus there.<br>3. Development: data-free refusals only, ledger checksum identical. |

---

## 3. First day per account — status, no decision

**Neither 1 Oct nor 1 Nov can be called safe for any account yet.** Three things are missing: live is not verified, Accounts has not confirmed September, and the real bank accounts have not been entered.

| Account | For 1 Oct we need | What could make it unsafe (development evidence) |
|---|---|---|
| **Com Bank LKR** | • September final in the ledger<br>• bank balance at 30 Sep<br>• E7 for this account understood (the 497,850.38 one-sided opening journal; the 15.87 m receipt on code "0")<br>• Accounts' view on the 24 Oct and 13 Nov rows | • A large unexplained opening difference (34.5 m at 1 Sep)<br>• September only partly in the copy |
| **Com Bank USD** | • USD statements<br>• rate policy (D6)<br>• E7 for this account (the 161,135.32 one-sided opening journal)<br>• Accounts' view on the 30 Sep and 24 Oct rows | • Rows entered months before their date<br>• Few rows; the statement is not yet seen |
| **Pan Asia LKR** | • Bank balance at 30 Sep<br>• live rows after 25 Aug | • None found on development (no rows since 25 Aug, no odd dates). This is **the simplest candidate**, but the facts must still come from live and Accounts. |

Once these facts are in, I will state for each account whether 1 Oct or 1 Nov is safe, and why.

---

## 4. Decision table

| # | Decision required | My recommendation | Evidence | Blocks live? |
|---|---|---|---|:-:|
| **D1** | Where the off-computer copy of the database export is kept | An external drive that you keep. | All backups are on the same computer: the database copies on the same MySQL, the file backups on the same disk. | **Yes** |
| **D2** | Who prepares and who reviews (see the profiles below) | **Preparer:** "Accounts" (group 30: view + prepare, menus 253/254).<br>**Independent reviewer:** "Finance & Approvals — Management Approver" (group 29: view + review, menus 253/255).<br>**View only:** "Finance & Approvals — Bank Authoriser" (35), "Auditor (read-only)" (34).<br>**Not for daily use:** System Administrator logins.<br>**Not a reviewer:** the Bank Authoriser, because they mark payments Paid. | • On development, groups 29 and 30 hold only TEST logins. | **Yes** |
| **D3** | First day per account | No decision yet (§3). | §1.2–1.4 | **Yes** |
| **D4** | Real bank accounts entered by Accounts; each mapping proposed by the preparer and confirmed by the reviewer | Enter the accounts on the "Avenir Bank Accounts" screen (System Administrator only), with the details taken from the bank documents. I will not enter account numbers. | The bank-account master is empty; the mapping four-eyes check is built in. | **Yes** |
| **D5** | Statement format | **(a) CSV export from web banking**, with one real sample per bank checked first (needs your approval: real data on development). | **Supported:**<br>• **(a)** CSV: separator, header row and debit/credit or signed-amount columns are found automatically; 12 date formats; Windows-1252 text<br>• **(b)** lines typed in, for short statements<br>**Not supported:**<br>• **(c)** Excel files (would need a library on the server)<br>• **(d)** PDF (would have to be typed in) | **Yes** |
| **D6** | Exchange-rate source and policy | **USD (a)**, confirmed every day.<br>**JPY (b)**, entered with its source, once the YEN account is used.<br>Accounts also decides whether month-end revaluation is posted in the ledger (the workflow never posts). | **Options:**<br>• **(a)** the system's daily USD rate (`daily_usd_rate`), used automatically when no rate is entered for a day<br>• **(b)** the bank's own rate from the statement<br>• **(c)** the Central Bank rate<br>Any rate entered shows its source.<br>**Evidence:** on development only **7 daily USD rates** exist (19 Aug – 7 Sep 2026), so (a) works only if the rate really is confirmed every day. | **Yes** (USD accounts) |
| **D7** | Use of the old screen and the two old tools | • Approve the retirement design in §2.<br>• The old screen is not used for protected periods.<br>• The old menu is withdrawn after one parallel month. | • The guard is on development.<br>• Tool A was run on 21 Oct 2025; Tool B has no trace.<br>• On development the 4-user "Auditor" group (17) counts as read-only (tf-pilot-r1). Live probably lacks that rule, so they may be able to tick rows on the old screen there. | **Yes** |
| **E1** | Mask Avenir's paying account number for roles that do not pay | Mask it to the last 4 digits | It is shown in full on batch and voucher pages to the Approver and Auditor (development). | No (payments release) |
| **E2** | Older groups (id ≤ 28) still see full supplier bank numbers | Review when staff move to the department profiles | Unchanged on development | No |
| **E3** | Convert the MyISAM ledger tables to InnoDB | A separate, backed-up step later | The reconciliation tables are InnoDB, and the reconciliation never writes the ledger. | No (for the reconciliation) |
| **E4** | Debtor-invoice amendment link open to every Tour Dashboard holder | Restrict it to Accounts | Unchanged | No |
| **E5** | `tour/print` open to every signed-in user | Restrict it to the roles that print tours | Unchanged | No |
| **E6** | Retire `LEGACY_ALLOCATION_GROUPS` | Later, with E2 | Unchanged | No |
| **E7** | Explain the 449,143.20 imbalance and the 34,495,229.47 provisional difference | Accounts reviews:<br>• the 8 one-sided entries<br>• the code-"0" receipt<br>• the post-dated rows (§1.3–1.4)<br>Then the opening items are agreed for each account's first day. | §1.4 | **Yes** (first day of Com Bank LKR / USD) |

**D2: how the same person is kept from preparing and reviewing the same reconciliation**

1. **The code checks it.** A reviewer is refused on any daily record, mapping, opening item or correction that the same login prepared or proposed.
2. **One login belongs to one group**, and the preparer and reviewer groups do not overlap.
3. **One person, one login.** No shared or second logins.
4. **System Administrator logins hold every privilege** and are not used for daily reconciliation.

You name the people. Neither group has a real staff member yet.

### Further live blockers found in this step

1. **Live has not been examined.** That needs your sign-in on system.avenirholidays.com, or a fresh copy of the live database.
2. **Live lacks the Tour Payments foundation** the reconciliation depends on (§1.1).
3. **Live is behind development.** A live package must be built from live's own files, and I cannot read those PHP files from here.
4. **Pending items from before:**
    - D1–D7 and E7;
    - fresh live backups;
    - your explicit approval.

**Nothing on development or live was changed in this step.**
