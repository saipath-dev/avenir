# tf-help-r1 — the in-system User Manual (Hotel Rates first)

A **User Manual** button on every screen. Staff ask in plain words ("Where do I go to approve hotel
rates?") and get short numbered steps with the exact screen and button names, a direct link to the
screen, and — when they lack the permission — who can do it instead. The printable PDF is made from
the same instructions. Help never changes data: every help route is GET and read-only.

## What is in the kit

| Path | What |
|---|---|
| `src/app/Help/sections/*.json` | The instructions (one file per section; `hotel_rates.json`, `general.json`) — the one source |
| `src/app/Help/images/hotel_rates/` | 17 marked-up screenshots of the live screens (9–10 Oct 2026) |
| `src/app/Libraries/Help/` | `HelpManual` (topics, question matching), `HelpAccess` (permissions, read-only), `HelpAnswer`, `HelpPdf` (TCPDF), `HelpVerifier` |
| `src/app/Controllers/HelpController.php` | `help`, `help/page`, `help/ask`, `help/topic/{id}`, `help/img/…`, `help/manual`, `help/check` |
| `src/app/Filters/HelpWidgetFilter.php` | Adds the button's script/stylesheet before `</body>` on signed-in HTML pages |
| `src/app/Commands/Help/Check.php` | `php spark help:check [--pdf]` |
| `src/app/Config/Routes.help.php`, `src/app/Views/help/` | Routes and the two small pages |
| `src/public/assets/help/` | `help-widget.js`, `help-widget.css` (plain JS, no libraries) |
| `install.py` | check / apply / rollback (backs up the two patched files first) |

Two existing files get small, marked additions (`tf-help-r1`):
* `app/Config/Routes.php` — one line `require __DIR__ . '/Routes.help.php';`
* `app/Config/Filters.php` — the `HelpWidgetFilter` alias and after-filter, and `help`, `help/*` on
  `UserPermissionFilter`'s exception list (help is for everyone; it never writes `a_log`).

Nothing else is changed. No database table is created or written. The help writes only its own files
under `writable/help/` (the PDF cache, `check.json`, and a question log without names).

## Install (test copy first; live only with Shantha's approval)

```
python3 install.py check    C:/wamp64/www/<copy>      # report only
python3 install.py apply    C:/wamp64/www/<copy>      # backups go to before/<time>/
php spark help:check --pdf                           # in that copy: every topic against the code
python3 install.py rollback C:/wamp64/www/<copy>      # if needed: the two files restored, new files moved aside
```

Without PHP on the command line: sign in as System Administrator and open `help/check`.

## Tested (cloud test copy, real Avenir code, test database, 10 Oct 2026)

* `help:check`: 21 of 21 topics match the code.
* 24 browser checks (`help_demo.py`): the button on Hotel Rates, Tour Inquiry and the dashboard; the
  question "Where do I go to approve hotel rates?" as System Administrator (steps + direct link to the
  Approval queue, which opens without approving anything), Hotel Desk (told Hotel Rate Approval is
  needed and who has it; link locked) and Quotation Desk; a two-part question; a question with no
  checked answer (says so, no guessing); PDF view and download; the database unchanged by asking.
* Sweep (`help_sweep.py`): all 67 HTML menu pages carry the button; JSON and AJAX answers are left alone.
