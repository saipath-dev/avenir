# Avenir New Generation — the regression battery

Rebuilt on 6 October 2026 after the earlier harness (93 PHP suites, 38 screen suites, a mock host) was lost with a
workspace reset. This battery runs against the **real Avenir application** on a **test copy** — never a mock, never live.

## What it refuses

Both halves stop before doing anything unless the database they are pointed at is a test copy whose **name ends in
`_test`** (`tests/newgen/_support/NewGenTestCase.php`, `tests/newgen/screens/tc.py`). The accounts it signs in with
(`tcadmin`, `tcquote`, `tctransport`, `tchotel`, `tcmgmt`) exist only on the test copy. No key, password of a real
account, or customer data is in these files (checked by `A11_SecurityRulesTest::testTheBatteryCarriesNoSecret`).

## Running it

    bash tests/newgen/run_battery.sh            # PHP side, screen suites, survey — one log and one summary under tests/newgen/_work/
    bash tests/newgen/run_battery.sh php        # vendor/bin/phpunit -c tests/newgen/phpunit.xml
    bash tests/newgen/run_battery.sh screens    # python3 tests/newgen/screens/<suite>.py, one after another

Environment (defaults suit the workspace copy):

| variable | meaning | default |
|---|---|---|
| `NEWGEN_HOST` | where the test copy answers | `http://127.0.0.1:8080` (the app at `/avenir/`) |
| `NEWGEN_DB_SOCKET` | the test database's socket (or leave empty and use host/port) | — |
| `NEWGEN_DB_HOST` / `NEWGEN_DB_PORT` / `NEWGEN_DB_USER` | TCP access to the test database | `127.0.0.1` / `3307` / `root` |
| `NEWGEN_DB_NAME` | the test database — must end in `_test` | `avenir_test` |
| `NEWGEN_WORK` | where logs and screenshots go | `tests/newgen/_work` |

Needs: PHP 8.1+ with mysqli (the app's own `vendor/bin/phpunit`), Python 3 with Playwright and its Chromium
(`pip install playwright && python -m playwright install chromium`), the `mysql` client.

## The PHP side (`php/`, PHPUnit, 64 tests / ~600 assertions)

| suite | what it holds the system to |
|---|---|
| A01 Installer & schema | schema 44, desks version 9, every TourFlow table and the schema-44 columns; the copies folder; the write-probe (the tf_backups fix) passes, refuses, cleans up; a copy is written whole with its checksum |
| A02 Desks & the access rule | VERSION frozen at 9; `ensure()` changes nothing; the three desk groups exist once; the desks hold exactly what was granted and nothing from the "ungranted" list; one menu URL = one privilege |
| A03 Routes & menus | every new-generation route points at a real controller method; every guarded route sits under a menu URL; the 25 Tour Desk menu URLs are single and live; the privilege view agrees with the tables; the menu headings as installed |
| A04 Source integrity | every loadable PHP file parses; no debugging leftovers in the new-generation files; the kits on record match what is installed; the release views compile |
| A05 Inquiry & quotation | list and counts agree with the table; an inquiry reads whole; the next number follows; costing adds up and the client view hides cost; pricing settings; the pax matrix; dates and text checks; an inquiry started by hand (then archived) |
| A06 Tours & desks | a tour reads with lanes and strip; no confirm without an issued quotation; desk queues and counts; the bar's badges come from the same numbers; cancelled tours readable and off the desks; the record keeps its history |
| A07 Transport & Guide Services | the three categories and the master's counts; the standards (4,000 / 7,500 / overnight unset, from 5 Oct 2026); what a person is paid (standard then agreed, nothing guessed); bank details only with the privilege; the seven class rates; mileage allowances and the lookup |
| A08 Masters | places & entrance fees, meal master, free visits / activities / destinations, ticket stock, the offer-selection privilege |
| A09 Hotel Rates & work queue | module version; hotels and contracts; a rate by date or not at all; the scan is idempotent and its counts agree; Ignore is per item and survives the scan, Done reopens; readiness |
| A10 Kits & rollback | every kit's parts and checksums; apply → roll back → apply on a scratch tree; a kit refuses a stranger's file |
| A11 Security rules | re-authentication refuses wrong/empty; Edit Request Authority never implied; the test accounts; no secret in the battery; sessions in the database, logins throttled |
| A12 Engine guard rails | an empty inquiry is refused with every reason named (room mix, hotel per night, kilometres, pricing); no quotation, no tour; the room mix is typed, never derived |

## The screen side (`screens/`, Playwright over the real pages, ~190 checks)

| suite | what it covers |
|---|---|
| `ui_1c` | the 1c reading layer and bar: names, Masters menu in four groups, lead and help folds, the section rail (jump, fold, remembered, re-render), print, screen names, Hotel Incomplete Work grouping, the desks' bars and the route filter |
| `ui_hotel_work_flow` | the work on the grouped list: add, search, close with a note, Done view, reopen, Refresh, export |
| `ui_desks` | each desk account: header, sidebar entries = grants, bar, no Masters, every URL answers as the grants say, an action POST refused; signed out |
| `ui_masters` | places & entrance fees (rows, search, add form), meal master, free visits / paid activities / excursion rates, tour photos, the TGS strip on its four screens, transport rates, Drivers & Guides tabs and counts, a record opens |
| `ui_tour_operations` | New quote → Start by hand → a record → its costing blockers → listed; the quotation and e-mail pages; Confirmed Tours, Hotel Bookings, Transport Operations, Tour Board, a tour record, Ticket Stock, Admin (backups list), Pricing, Payment Dashboard |
| `ui_hotel_rates` | the panel's list, search, detail, rate-on-a-date, the approval queue, readiness, summary, holds |
| `ui_security` | sign-in refusals and throttling, cookie flags, action-only privileges refused on the real endpoints, bank details only for the System Administrator, no cross-desk data, sign-out |
| `sweep_http` | every request the screens make (no 4xx/5xx), every JSON endpoint valid, the privilege guard for four accounts, signed out |
| `survey` | one full-page screenshot per Tour Operations screen with status and JS errors (one known legacy item: the old dashboard's own script error) |

## Keeping it

The battery lives in the application tree under `tests/newgen/` (nothing in `app/` reads it) and a copy is kept outside
any workspace: `C:\wamp64\www\avenir\Claude outputs\regression_tests\` on Shantha's machine, plus the chat's files.
`MANIFEST.md5` lists every file's checksum. `REBUILD_TEST_COPY.md` says how a test copy is built from the live files
and the backups in about an hour, so a lost workspace costs an hour, not a day.
