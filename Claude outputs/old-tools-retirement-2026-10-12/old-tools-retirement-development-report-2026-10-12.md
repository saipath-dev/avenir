# Retirement of the two old tools — development results (12 Oct 2026)

**Summary**

- **Development:** `tf-legacy-retire-r1` is **installed** (5 files). The ledger is identical before and after, and nothing was posted or corrected.
- **No database, login, group or permission change on development.**
  - Your signed-in session was used only for two page views and eight refusal requests.
  - Neither old tool ran, no other button was pressed, and you were not signed out.
- **Isolated test instance:**
  - It was tested with a temporary TEST login. The login was then **removed**, and the user and permission tables were verified identical to the clean snapshot.
  - Its five files were **rolled back** to the originals.
- **Live:** not accessed; no package built. **I have stopped for your approval.**

Times are Sri Lanka time.

---

## 1. The five-file change (kit `Claude outputs\tf-legacy-retire-r1\`)

| File | Change | md5 original → new |
|---|---|---|
| `app/Libraries/BankRec/LegacyGuard.php` | Adds `RETIRED_TOOLS` and `refuseRetiredTool()`:<br>• security token checked first: missing or wrong → **403**<br>• valid token → **409** "retired"<br>• one audit row per attempt | 1d4cca93 → 93a621ca |
| `app/Controllers/Settings/ManualUpdateController.php` | `bank_recon_record_insert()` refuses before anything else. The original code stays below and is never reached. | d67ac130 → 45ddf8e8 |
| `app/Controllers/Accounts/BankUnrealisedValueController.php` | `process_unrealised_value_change()` refuses in the same way. | 280eb40f → 102e7d8a |
| `app/Views/pages/settings/manual_update.php` | That one button → grey "**Retired:** Bank Not Reconciliation Record Insert…" notice | 4f52f9b3 → 616ab158 |
| `app/Views/pages/accounts/bank_unrealised_value_change.php` | The Process button → grey "**Retired:** Bank Req Data Transfer…" notice | 3b808508 → c3ca2a73 |

What stays the same:

- **No route, menu or database change.** There is no run-once marker.
- **Both routes stay**, so a page that is still open gets a proper 403 answer.
- **All other Manual Update buttons, actions and code are unchanged.**

**The audit row** (table `tpd_audit`, action `legacy_tool_refused`) records:

- the tool;
- the user and the time;
- the IP address;
- the answer (403 or 409) and the reason (token or retired);
- the bank posted, for Bank Req Data Transfer;
- "(nothing was changed)".

**Kit hygiene.** The ZIP is `tf-legacy-retire-r1-20261012.zip` (18 files, md5 501ef6e05e901ad6192498e069ce6656, without the `before\` backups). I scanned it, and it contains **no password or credential value**. The temporary-login scripts are kept **outside** the kit, in `TEST-ONLY-NEVER-LIVE-tf-legacy-retire-r1-fixture\`.

## 2. Test results

| Where | What | Result |
|---|---|---|
| **Cloud test copy** | **Positive control first.** With the ORIGINAL code, both tools did write to the test copy's ledger. This proves the checks can detect a write. The copy was then restored from a snapshot. | done |
| | **Other Manual Update actions.** The 9 other actions were run with the original code and then with the new code, each time on the same restored database. They gave the same answers, and every table ended the same. | **9 / 9** |
| | **Refusals** (a non-administrator TEST login and an administrator TEST login):<br>• no token 403, wrong token 403, valid token 409<br>• unprivileged user stopped by the permission check<br>• 12 audit rows, one per attempt<br>• ledger, entry counter and 2024 ledger unchanged<br>• every other table unchanged<br>• rollback gives the 5 originals | **25 / 25** |
| | Regression with the change in: r3 suite; r3 screens | **132 / 132; 24 / 24** |
| **Isolated test instance** | Browser checks with the temporary TEST login: pages and notices, the A and B refusals, the unprivileged user | **7 / 7** |
| | Audit rows 478–485 (one per attempt); ledger and all other tables unchanged; rollback byte-identical | all as expected |
| **Development** | Your session: pages, A and B refusals (403 / 403 / 409 each) | **5 / 5** |
| | TestAccounts (own sign-in, separate browser address): A stopped by the permission check; B refused 403 without token and 409 with token (see finding 1) | as expected |
| | Rollback byte-identical; reinstall verified; final confirmation (A 409, B 409) | as expected |

**Other Manual Update actions on the isolated instance and development.** I did not press them there, because they change data. On both, the page still shows the 8 other buttons and the 9 action addresses, and their code is byte-identical. Their behaviour was proven unchanged on the cloud copy.

## 3. Ledger integrity — development

Figures were taken at 16:12:14 before, at 16:13:08 after the tests, and at 16:13:59 at the end. **All three are identical:**

| Measure | Value |
|---|---|
| `account_runing` rows | 11,364 |
| Total debits | 965,763,907.13 |
| Total credits | 965,314,763.93 |
| Reconciliation flags (`bank_rec` / `bankst_flag`) | 2 / 2 |
| Checksum `account_runing` | 633232719 |
| Checksum `invoice_no` (entry counter) | 2816037445 |
| Checksum `account_runing_2024` | 2922173702 |
| Checksum `account_runing_bank_statetment` | 843700953 |

- **241 tables compared.** Only these changed:
  - `tpd_audit`: the rows listed below;
  - `a_log`, `ci_sessions`, `user_sessions`: page visits and sign-ins.
- **User, group, privilege and menu tables: unchanged.**

**Audit rows on development** (one per attempt, as expected):

| Rows | User | Tool | Outcome |
|---|---|---|---|
| 412, 413, 414 | super admin (your session) | Record Insert | token 403, token 403, retired 409 |
| 415, 416, 417 | super admin | Bank Req Data Transfer (bank 1-6-8-10380) | token 403, token 403, retired 409 |
| 418 | TestAccounts | Record Insert | access_denied (permission check) |
| 419, 420 | TestAccounts | Bank Req Data Transfer | token 403, retired 409 |
| 421, 422 | super admin | both (final confirmation after reinstall) | retired 409, 409 |

**Isolated instance:** checked at 16:08:54, 16:09:53 and 16:11:57, all identical:

- 11,390 rows; debits 989,263,607.13; credits 968,857,808.93; flags 2 / 2;
- checksums 141237580 / 2816037445 / 2922173702 / 843700953.

Its ledger includes the dummy demonstration rows.

## 4. Rollback evidence

| Where | Rollback | Result |
|---|---|---|
| Cloud | `apply.py rollback` | 5 files = originals (md5); pages show the original buttons; kit re-applied |
| Isolated instance (16:11) | `apply.py rollback` | `all_five_original: true`; each file **byte-identical** (`cmp`) to its backup copy **and** to development's original; the pages show the original buttons again. **The isolated instance stays rolled back.** |
| Development (16:13:37) | `apply.py rollback` | `all_five_original: true`; each file **byte-identical** to its backup copy **and** to the isolated instance's original. |
| Development (16:13:43) | **Reinstalled**, because the retirement is approved for development | Verified by md5. **Final status: installed.** |

## 5. The temporary test login (isolated test instance only) — record

| Step | Time | What |
|---|---|---|
| Created | before 16:00 | `TestLegacyTools` (user 124), its own TEST group 38, employee row 90, and 2 privilege rows: Manual Update (107) and Bank Req Data Transfer (184) only. Not an administrator. |
| Corrected | 16:08 | The first version had copied TestAccounts' password hash. I replaced it with a **new one-time password**: its hash matches no other login, and the password is stored nowhere. |
| Removed | 16:11:57 | Login, group, employee row and both privilege rows deleted. Nothing left. |
| Verified | 16:11:57 | `user_acc_tbl`, `group_profile`, `user_privileges`, `employee` are row-by-row and checksum **identical to the clean pre-test snapshot** (`avenir_rv_backup_20261012_before_r3`). Audit and log rows from the test remain as the record. |

There was no equivalent login or permission change on development or live.

## 6. Two findings from the testing (nothing changed for them)

1. **"Manage Accounts" opens every `accounts/…` address.**
   - **What it is:** menu 49, address `accounts`. The permission check treats every address that starts with an allowed menu address as allowed. So this one menu opens every `accounts/…` page and action, including Bank Req Data Transfer and the old Bank Reconciliation screen's actions.
   - **Who holds it on development:**
     - System Administrator;
     - Auditor (group 17, 4 users; read-only on development only because of a development rule);
     - Admin (group 25, no users);
     - Accounts (group 30).
   - **Effect:** before this change, an Accounts user could run Bank Req Data Transfer directly. It is now refused for everyone.
   - **Recommendation:** review this rule as a separate item (proposed **E8**). It is probably the same on live (not verified).
2. **A correction to my earlier evidence.**
   - **What I got wrong:** I said Bank Req Data Transfer's page was "never opened between 27 March and 8 September". The activity log cannot support that. A visit made through "Manage Accounts" is logged only as `accounts`, and System Administrator requests are not logged by the current development code. I withdraw that point.
   - **What still stands:** "no bank needs a run of Bank Req Data Transfer" still rests on the ledger evidence: no trace of its writes in the current ledger or in the 2025 copies. It remains **strongly supported by development evidence but not verified on live**.

## 7. D4 — entering the real bank accounts (clarified; nothing granted)

**How it works today (development)**

- The "Avenir Bank Accounts" screen (`master_files/bank_accounts`, menu 249) has its **own privilege**, `bank_master`, checked in the code. It is granted **only to System Administrator** (1 user).
- Every save is audited (`bank_account_saved`, with the previous and new values).
- There is **no second-person check** on the master itself.
- Accounts cannot enter accounts, and **must not be given administrator access**.

**Recommended controlled process**

1. **Accounts** gathers and verifies the bank documents for each account (bank letter or statement header): bank, branch, account name, account number, currency, and the ledger account it belongs to.
2. **An authorised administrator** (System Administrator) enters the master details from those documents. **Accounts then checks** what was entered against the documents and signs off. The save is audited.
3. **The Accounts preparer** proposes the ledger mapping in Daily Bank Reconciliation. The first day is proposed only after D3 is decided.
4. **The independent Management Approver** confirms the mapping. The code refuses confirmation by the login that proposed it.
5. **The administrator who entered the master does not confirm the mapping.**

**Should a separate least-privilege "Maintain Bank Account Master" permission be created later? Yes.**

- The privilege already exists technically (menu 249, checked by the code).
- The later step is to give it to a dedicated group holding only that menu, assigned to one named person who is **neither** the Accounts preparer **nor** the Management Approver.
- At the same time, add a **verification step** for master changes (a second person confirms an entered or changed account before it can be mapped), like the existing supplier-bank verification.
- That needs a small code change, built and tested on the cloud copy and the isolated instance first. **Not now:** after D2 names the people.

## 8. Still stopped

- No real bank account entry, mapping confirmation or first day.
- No old-tool run and no ledger correction.
- No real statement upload. A statement-format test, when approved, will use a **sanitised** CSV on the **isolated test instance** only.
- No live examination, package or installation.

The Accounts Action Sheet is a separate document.
