# tf-help-r2 — the in-system User Manual, one manual per section

A **User Manual** button on every screen. It opens the manual for that screen — each section of the system has
its own: so far **Hotel Rates** and **Hotel Master** (new in r2). Staff ask in plain words ("Where do I go to
approve hotel rates?", "How do I add a new hotel?") and get short numbered steps with the exact screen and
button names, a direct link to the screen, and — when they lack the permission — who can do it instead. Each
manual has its own printable PDF (view, download, print); the full manual has every section. Help never changes
data: every help route is GET and read-only.

## What r2 changes (over r1, live since 10 Oct 2026)

* One manual per screen: Hotel Rates screens open the **Hotel Rates manual**, Supplier and the hotel's
  **Hotel details / Room types / History** tabs open the **Hotel Master manual** (12 topics, 11-step guide,
  8 pictures). Shared screens show **Manuals for this screen** to switch.
* Own PDF per manual: `help/manual/hotel_rates`, `help/manual/hotel_master`; `help/manual` = all sections.
* Questions about parts of the system not covered yet (drivers, vehicles, vouchers, payments…) get
  *No checked answer* instead of a near miss. Answers from another manual are marked as such.
* "Room types of a hotel" moved from Hotel Rates to Hotel Master.
* `install.py undo-update` goes back to r1; `rollback` still takes the help out completely.

## What is in the kit

| Path | What |
|---|---|
| `src/app/Help/sections/*.json` | The instructions, one file per section (`hotel_rates.json`, `hotel_master.json`, `general.json`) — the one source |
| `src/app/Help/images/hotel_rates/`, `hotel_master/` | 17 + 8 marked-up screenshots of the live screens (9–10 Oct 2026) |
| `src/app/Libraries/Help/` | `HelpManual` (topics, question matching), `HelpAccess` (permissions, read-only), `HelpAnswer`, `HelpPdf` (TCPDF), `HelpVerifier` |
| `src/app/Controllers/HelpController.php` | `help`, `help/page`, `help/ask`, `help/topic/{id}`, `help/img/…`, `help/manual[/{section}]`, `help/check` |
| `src/app/Filters/HelpWidgetFilter.php` | Adds the button's script/stylesheet before `</body>` on signed-in HTML pages |
| `src/app/Commands/Help/Check.php` | `php spark help:check [--pdf]` |
| `src/app/Config/Routes.help.php`, `src/app/Views/help/` | Routes and the two small pages |
| `src/public/assets/help/` | `help-widget.js`, `help-widget.css` (plain JS, no libraries) |
| `install.py` | check / apply (also updates) / undo-update / rollback (backs up first) |
| `tests/` | `help_demo_r2.py` (37 checks), `help_demo.py` (r1, 24), `help_sweep.py` (every menu page) — they run against a test copy through its own `tc.py` helper (sign-ins of that test copy; not shipped) |

Two existing files get small, marked additions (`tf-help-r1`):
* `app/Config/Routes.php` — one line `require __DIR__ . '/Routes.help.php';`
* `app/Config/Filters.php` — the `HelpWidgetFilter` alias and after-filter, and `help`, `help/*` on
  `UserPermissionFilter`'s exception list (help is for everyone; it never writes `a_log`).

Nothing else is changed. No database table is created or written. The help writes only its own files
under `writable/help/` (the PDF cache, `check.json`, and a question log without names).

## Install (test copy first; live only with Shantha's approval)

```
python3 install.py check       C:/wamp64/www/<copy>   # report only
python3 install.py apply       C:/wamp64/www/<copy>   # first install: backups in before/<time>/;
                                                     # over r1: an update, the replaced files kept in updates/<time>/
php spark help:check --pdf                           # in that copy: every topic against the code, all PDFs rebuilt
python3 install.py undo-update C:/wamp64/www/<copy>   # back to the version before the update (r1)
python3 install.py rollback    C:/wamp64/www/<copy>   # the help out completely: the two files restored, help files moved aside
```

Without PHP on the command line: sign in as System Administrator and open `help/check`.

## Tested r2 (cloud mirror of the live code — every file checked against live by md5 — test database, 10 Oct 2026)

* `help:check`: 32 of 32 topics match the code; the three PDFs build (Hotel Rates 2.9 MB, Hotel Master 1.0 MB,
  full 3.8 MB).
* `help_demo_r2.py`: 37 of 37 — the manual chosen per screen and per tab, switching, answers marked from the
  other manual, Supplier, Tour Inquiry and the dashboard without a manual of their own, no guessing, every PDF
  inline and as a download, permissions (Hotel Desk told archiving needs Tour Desk Admin and who has it), and
  the database unchanged by all of it.
* `help_demo.py` (r1): 24 of 24 still. Sweep: all 67 HTML menu pages carry the button; JSON and AJAX untouched.
* Question battery: 73 of 73 expected answers; 17 questions about other parts of the system — none answered
  from an unrelated topic.

## Tested r1 (cloud test copy, real Avenir code, test database, 10 Oct 2026)

* `help:check`: 21 of 21 topics match the code.
* 24 browser checks (`help_demo.py`): the button on Hotel Rates, Tour Inquiry and the dashboard; the
  question "Where do I go to approve hotel rates?" as System Administrator (steps + direct link to the
  Approval queue, which opens without approving anything), Hotel Desk (told Hotel Rate Approval is
  needed and who has it; link locked) and Quotation Desk; a two-part question; a question with no
  checked answer (says so, no guessing); PDF view and download; the database unchanged by asking.
* Sweep (`help_sweep.py`): all 67 HTML menu pages carry the button; JSON and AJAX answers are left alone.
