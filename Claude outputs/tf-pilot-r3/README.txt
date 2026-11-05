tf-pilot-r3 (11 Oct 2026) - development system only. See the docstring of apply.py for what it changes.
Tested on the cloud test copy: R4 suite 202/202, R5 bulk 71/71, Pilot 36/36, Operations/confidentiality 33/33, r3 26/26.
Route scan (tests/scan_bank.py): every GET route of the application requested as each department test login with the
development system's own menus (tests/dev_group_menus.json): no full supplier bank account number served to any of them.
Install:  python3 apply.py check <avenir root>   then   python3 apply.py apply <avenir root>
Undo:     python3 apply.py rollback <avenir root>
