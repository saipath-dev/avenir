tf-ledger-guard-r1 (11 Oct 2026) - development system only. See the docstring of apply.py.
Tested: cloud test copy (tests/ledger_guard_test.py, 10/10) and the isolated test instance avenir_rv on the
restore-verify database with dummy ledger entries only (results in the completion report). Real ledger rows untouched.
Install:  python3 apply.py check <avenir root>   then   python3 apply.py apply <avenir root>
Undo:     python3 apply.py rollback <avenir root>
