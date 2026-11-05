tf-bankrec-r1 — Daily Bank Reconciliation (Step 2), 11 October 2026. Development / test system only.

Install:   python3 apply.py check <avenir root>   then   python3 apply.py apply <avenir root>
           then the System Administrator presses Set up on Accounts → Daily Bank Reconciliation.
Rollback:  python3 apply.py rollback <avenir root>   (Routes.php put back; the 11 added files moved aside; tables kept)
Files:     app/Config/Routes.php (changed: one route group inside `accounts`)
           app/Controllers/Accounts/DailyBankRecController.php, app/Libraries/BankRec/*.php (6),
           app/Views/pages/accounts/bankrec/*.php (4)   (added)
Database:  tables brc_account_map, brc_statement, brc_line, brc_fx_rate, brc_match, brc_match_item, brc_mark, brc_transfer,
           brc_opening, brc_correction, brc_day; 3 privilege entries; tpd_meta brc_schema_version = 1. Nothing else changes.
Tests:     tests/ (dummy data only) and the results files.
