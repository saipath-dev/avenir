# Hotel Rates (new) — schema 7

The rate-plan layer of the Avenir management system (CodeIgniter 4.6, MySQL 8),
rebuilt from the 2026/27 rate sheets the hotels actually send (Thema
Collection, MOI/DBI, Aitken Spence, Fort Resorts, Blue Water, Radisson,
Amaya/Hayleys, Hilton Yala, Sheraton Kosgoda, Morven, Casamara, Centauria,
Elephant Reach, Tamarind Tree, Simpsons Forest, Grand Serendib, Deewara).
`RATE_SHEET_GAP_ANALYSIS.md` next to this file lists every structure found in
those sheets and where it lives now.

Panel: **Master Files → Hotel Rates (new)** (`master_files/hotel_rate_v2`).
Approvals & settings: **Master Files → Hotel Rate Approval**
(`master_files/hotel_rate_approval`).
What is still missing: **Master Files → Hotel Incomplete Work**
(`master_files/hotel_work`).

Existing files touched: one `require` line at the end of
`app/Config/Routes.php` (plus the `recalc_hotel_cost` route in the
`tour_payment_dashboard` group), the old Hotel Rate screen
(`HotelRateController.php` + `hotel_rate.php`) with the lock described below,
and — for the hotel cost handoff — small marked additions to
`TourPaymentDashboardController.php` / `tour_payment_dashboard.php`,
`TourAccountsController.php` / `tour_accounts_note.php`,
`TourAccountsCheckController.php` / `payment_approval_v2.php` and
`DebtorInvoiceNewController.php` (the confirmation hook). The Hotel Rate
report and `NewTourController::get_meal_prices()` keep working from the same
`hotel_rate` rows.

## What is in the box

| File | Purpose |
|---|---|
| `app/Database/Sql/hotel_rates_v2_schema.sql` | 30 InnoDB tables prefixed `hr2_` + the settings seed (re-runnable; upgrades schema 3–6 in place) |
| `app/Database/Sql/hotel_rates_v2_menu.sql` | Menu entries "Hotel Rates (new)", "Hotel Rate Approval" and "Hotel Incomplete Work" + System Administrator grants |
| `app/Database/Migrations/2026-09-11-000001_CreateHotelRatesV2.php` | Same schema for `php spark migrate` |
| `app/Libraries/HotelRates/Sheet.php` | The one data shape every part speaks (editor ⇄ validator ⇄ import ⇄ database) |
| `app/Libraries/HotelRates/Settings.php` | Every tunable rule (`hr2_meta`) and the country → market table |
| `app/Libraries/HotelRates/RateValidator.php` | Checks E01–E18 (block approval) and W01–W19 (must be confirmed) |
| `app/Libraries/HotelRates/HotelRateService.php` | Hotels, room inventory, plans (save / approve / supersede / copy), coverage, merge, audit |
| `app/Libraries/HotelRates/RateLookup.php` | The pricing engine: which plan prices a night and how (markets, booking window, tier, base + supplement, meal deltas, parent chain) |
| `app/Libraries/HotelRates/LegacyProjector.php` | Rebuilds the legacy `hotel_rate` rows from the approved plans |
| `app/Libraries/HotelRates/HotelCostService.php` | The hotel cost handoff: what a confirmed tour PAYS each hotel per night (`hr2_tour_hotel_cost`) for the Tour Payment Dashboard |
| `app/Libraries/HotelRates/WorkQueueService.php` | Hotel Incomplete Work: the detectors, the queue (`hr2_work_item`) and its actions |
| `app/Controllers/MasterFiles/HotelWorkController.php`, `app/Views/pages/master_files/hotel_work.php` | The Hotel Incomplete Work panel |
| `app/Libraries/HotelRates/LegacyLock.php` | Tells the old Hotel Rate screen which hotels it may still edit |
| `app/Libraries/HotelRates/RateImportService.php` | Excel (template workbook, long list, old wide upload) and AI sheets → preview → commit |
| `app/Libraries/HotelRates/AiSheetExtractor.php` | Reads a PDF / image with the existing `Config\OpenAI` settings into the Sheet shape |
| `app/Libraries/HotelRates/LegacyMigrator.php` | One-time copy of `supplier` + `hotel_rate` + `room_type` into `hr2_*` |
| `app/Libraries/HotelRates/Installer.php`, `Dates.php` | Schema install / upgrade; date parsing (01.05.2026, Excel serials, "1st May 2026") |
| `app/Commands/HotelRates/{Install,Migrate,Project,Check}.php` | `php spark hotelrates:install / migrate / project / check` |
| `app/Controllers/MasterFiles/HotelRateV2Controller.php` | JSON endpoints for the panel (and `lookup` / `quote` for the timeline) |
| `app/Views/pages/master_files/hotel_rate_v2.php` | The panel |
| `app/Config/Routes.hotel_rate_v2.php` | The two route groups, included from `Routes.php` |
| `app/Controllers/MasterFiles/HotelRateController.php`, `app/Views/pages/master_files/hotel_rate.php` | The OLD screen with the lock (small, marked additions) |

## Install on the local WAMP copy first

```
cd C:\wamp64\www\avenir
php spark hotelrates:install --menu      # creates / upgrades the hr2_ tables, seeds settings + country → market, adds the two menu entries (safe to re-run)
php spark hotelrates:migrate --dry-run   # report only
php spark hotelrates:migrate             # one "migrated" plan per hotel from the old hotel_rate rows (~1 s)
php spark hotelrates:check               # nights on upcoming tours with no rate, expiring plans, closed offers, flagged rows
```

`php` is `C:\wamp64\bin\php\php8.x\php.exe` if it is not on the PATH. Without
spark: open Master Files → Hotel Rates (new) as System Administrator — the
page offers **Install now** and then **Dry run / Copy hotels & rates across**
(the same code the commands run), and Settings has **Re-run install** for
later upgrades.

The hr2_ tables are created in the database's own utf8mb4 collation (on the
production MySQL 8 that is `utf8mb4_0900_ai_ci`), read from `tour_quotations`
at install time, and converted on upgrade if they differ — otherwise MySQL 8
refuses every join between the new tables and the old ones ("Illegal mix of
collations").

Schema 3 (the previous drop) is upgraded in place — nothing is dropped. Only
the very first layout (schema 1, `hr2_rate_period`) is rebuilt, and the
command refuses to do that when it holds hand-entered contracts unless
`--force` is given.

Then log in, open Master Files → Hotel Rates (new). Log out and in again if
the menu entries do not show (privileges are read at login).

**Nightly job (recommended):** `php spark hotelrates:project` — tactical
offers with booking windows start and stop applying as the calendar moves, so
the rows the old screen reads are refreshed every night (Windows Task
Scheduler on the server).

## Who may do what — the system's own privilege procedure

Permission is the normal one: a menu entry per privilege, granted per group
under Settings › Group Privileges, enforced by `UserPermissionFilter` (a
request is allowed when its path is a sub-path of a granted menu URL).

| Menu entry (URL) | What the grant unlocks |
|---|---|
| **Hotel Rates (new)** `master_files/hotel_rate_v2` | open the panel, enter and edit drafts, import Excel / read PDFs *as drafts*, delete drafts, copy plans, room inventory, hotel details, coverage, Try a quote, health check, view Settings |
| **Hotel Rate Approval** `master_files/hotel_rate_approval` | approve, supersede, "Correct the approved plan", import with "Save & approve", merge duplicate hotels, change Settings and the country → market table, the approval queue page |
| **Hotel Incomplete Work** `master_files/hotel_work` | the queue of what is missing: view, Refresh, add an item, close / ignore / reopen one, attach a supplier code and load its sheet as a draft, export the list |

System Administrator (group 1) always has all three. The panel hides what the
user may not do (Approve buttons, "Save & approve", merge, Settings › Save)
and the controller re-checks the grant (`canApprove()`), so an approval can
never slip through the first URL. Every approval-group request is also
written to `a_log` by the filter like any other menu page.

A menu entry created through Settings › Menu List (`show_in_menu_bar = 0`)
appears in the sidebar under its category *and* as a tile inside that
category's modal on the tile dashboard; a tile in another category (Ground
Handling, say) is one more `menu_list` row with the same URL under that
category (see `app/Database/Seeds/quick_actions_tiles.sql` for the pattern).
`GET master_files/hotel_rate_v2/summary` returns the counts a tile or widget
may want (hotels priced today, drafts waiting, plans expiring). The Hotel Rates
panel's toolbar carries an **Incomplete work** button with the open count, so
the queue is one click away wherever rates are entered.

## Settings — nothing is hard-coded

Every rule the checks and the engine use is a row in `hr2_meta`, edited from
the panel (Settings button; saving needs the approval privilege):

| Group | Settings |
|---|---|
| Markets & currency | default market (used when a quotation's country is unmapped), default contract currency |
| Tiers | FIT up to *n* rooms / Group from *n* rooms — used when a plan says FIT or Group but prints no limit |
| Gala nights | the MM-DD nights that need a gala supplement (default 12-24, 12-31), usual child % |
| Checks | tiny / huge rate thresholds, % change vs the previous plan, expiry warning days, child age warning |
| Horizons | coverage chart days ahead, health-check days ahead |
| Old Hotel Rate screen | booking basis for the rebuilt rows; lock mode (see below) |
| Country → market | one market per country ISO code — the quotation form stores the agent's country (`consignee.country = country.iso`) |

The country table is seeded from the system's `country` table with a sensible
default (Japan → JP, Hong Kong → CN, Gulf → ME, Maldives → SA, UK → UK,
Poland → EE, Sri Lanka → DOM, everything else → OTH) and edited per row; a
note explains special cases such as a Singapore desk selling Japanese guests.

## How the data is organised

* **Hotel** — one real property; several supplier codes (`hr2_hotel_account`)
  can map to it (merge duplicates from the panel). Inventory lives in
  `hr2_room_type`: count, m², beds, max adults/children, and whether the room
  is priced with its own rates (`base`) or as **base room + supplement**
  (Sheraton "Higher room category supplements", Radisson, Amaya, Aitken
  Spence, Fort …).
* **Rate plan** (`hr2_contract`) — a contract, a tactical / early-bird /
  special offer, or an addendum. Several plans may cover the same hotel and
  dates. Each plan carries: stay validity, **booking window**, **markets**
  (include / exclude), **tier** (FIT / group with optional limits), priority,
  rate code, pricing mode (per room / per person / per unit), tax treatment,
  contract metadata, and the policy texts as written.
* **Season** (`hr2_season` + `hr2_season_date`) — a named period with **one or
  more date ranges** ("01 Nov–20 Dec & 01–30 Apr"), optional min stay and a
  room cap for tactical offers.
* **Rate** — season × room type × meal plan with SGL / DBL / TPL / QUAD /
  UNIT and optional child-with-bed / child-no-bed. A missing row means "not
  offered"; 0 is never stored.
* **Room supplements** and **meal deltas** — "Suite +260 per room", "BB
  reduction −5 per person on HB", "AI +100 on BB". The engine derives every
  combination the sheet does not print.
* **Supplements** — gala dinners by basis (BB guests vs HB/FB/AI guests),
  peak / August / Perahera surcharges (per room, per person, or by occupancy
  SGL/DBL/TPL), extra adult / 4th person, early check-in and late check-out
  (fixed or % of the rate). Compulsory ones are added to quotations.
* **Child rules** — age band × with/without bed × meal plan → amount (0 =
  free) or % of adult rate / meal rate / meal supplement.
* **Guide & driver rules, FOC rules, offers, restrictions** (min stay,
  meal plan required on 24/31 Dec, no check-out), **cancellation matrix**
  (stay period × FIT/group or room/pax limits × days-before band → % or
  nights), **payment rules**, **extras** (excursions, transfers, MICE).
* **Addendum** — a plan with a parent: anything it does not define (gala
  dinners, child rules, room supplements, meal deltas…) is taken from the
  parent ("all other supplements as per the original contract").

## Which plan prices a night

`RateLookup::price(hotel, date, room, meal, {market | country, booking_date, rooms, pax, tier})`

1. approved plans whose validity and a season date range cover the night;
2. booking window contains the booking date;
3. markets include the quotation's market (or the plan has no market list) and
   do not exclude it. The market is `market` if given, else the `country`
   (ISO code) through Settings › Country → market, else the default market;
4. tier fits the number of rooms / pax (limits printed on the plan, else the
   FIT / Group settings);
5. order: priority, then offer > addendum > series > contract > migrated, then
   explicit market, explicit tier, newest approval, narrowest season.

Every plan says what it is **for** (`applies_to`): *both* (the default),
*quotation only*, or *payment only* — an **ad hoc rate** the hotel agreed for
a stay, which must never reach the customer. The engine takes a `purpose`:
`quotation` (the default — payment-only plans are invisible, so the
quotation, the old-screen rows and coverage never see them) or `payment`
(payment-only plans win first, then the contract plans as above).

Inside the plan: the printed row, else base room + room supplement, else
another meal plan's row + meal delta(s) (two steps allowed: HB→BB→RO), else
the next candidate. The answer carries the plan, season, derivation,
supplements for that night, restrictions, child rules, guide / FOC rules.

`GET master_files/hotel_rate_v2/lookup?hotel_acc=&room=&meal=&date=&country=|market=&booking_date=&rooms=`
answers what the timeline needs (`SGL/DBL/TPL/QUAD/UNIT` + `market` + `data`).
`GET …/quote?hotel_id=&room=&meal=&check_in=&nights=&adults=&country=|market=`
prices a whole stay night by night (the panel's "Try a quote").

**The quotation timeline now prices from these plans.**
`NewTourController::get_meal_prices()` (`tour/quotation/get_meal_prices`, called
by the timeline when the hotel, room type or meal plan changes) asks
`HotelRateService::lookup()` first — market from the agent's country, ad hoc
payment rates never included — and answers the rates plus `source: panel`, the
plan and season names, the market and the night's `flags`. The timeline prints
them under the meal plan: a green tick with the plan and season, or an amber
"old rate row — no plan", or a red "no rate found", and one line per compulsory
item (gala dinner, peak, festival, minimum stay). When no plan covers the night
it falls back to the old `hotel_rate` rows exactly as before, and the gap is
registered in Hotel Incomplete Work; when nothing prices the night at all the
old "No Hotel Rate Found" dialog now opens **Hotel Rates** on that hotel and
date. Rates already saved on a quotation are never touched.

**A quotation uses the published contract rate only.** Every quotation lookup
(the timeline's `get_meal_prices`, the `lookup` endpoint, the Tour Desk's
inquiry nights and its accommodation planner) passes `plan_types` to the
engine, so only *contract*, *migrated*, *addendum* and *series* plans compete
for the night; special offers, early-bird and tactical plans never price a
quotation, even when they carry a higher priority than the contract for the
same dates (before this the engine chose the offer as the winner and the
quotation then saw "no rate" although a contract existed). The set is
`quote_plan_types` in `tf_meta` (comma-separated, that default when unset) —
`RateLookup::quotationPlanTypes($db)` reads it. The panel's own card, "Try a
quote" and `rates_on` still let every approved plan compete, and
`lookup?…&all_plans=1` does the same on request. When a night has no
quotable plan the inquiry note says why: draft plans awaiting approval, an
offer or tactical plan that is not quoted, a season gap, another market.

## Rate on a date / a stay — the card every hotel opens with

Pick a hotel and the first thing on the screen is what it costs **tonight**:
every room type × meal plan (SGL / DBL / TPL, QUAD or the unit price), the plan
and season each cell comes from (derived cells say so — base room + supplement,
meal delta), meals and child prices. Change the date, or give a last night /
number of nights, and the card prices **every night of the stay**: nights with
identical rates are folded into segments, a *Stay total* table adds up the room
rates per room type × meal plan, and the market and number of rooms (FIT /
group) can be changed on the card.

Above the rates sits **Watch out** — read from the plans themselves, so it works
for periods nobody knows by heart: every gala dinner (24 / 31 Dec or whatever
the hotel prints), peak, festival (Perahera) and other compulsory supplements
with their amounts and the guests they apply to, festive / peak seasons,
minimum stays and meal-plan rules, nights with no approved rate, and the gala
dates from Settings when a plan records no gala for them. Compulsory items come
first, each with the exact nights of the stay it touches.

The same warnings travel with the quotation lookup:
`GET master_files/hotel_rate_v2/lookup?…` answers `flags` next to the rates
(`kind` gala | peak | festival | supplement | season | restriction | no_rate |
gala_unrecorded | adhoc, `level` red | amber | blue | green), so the timeline
can show them (the quotation lookup itself never carries the ad hoc flag —
only the card and the payment side do).
`GET …/rates_on?hotel_id=&date=&to=&market=|country=&rooms=` is the card's
endpoint (`to` turns it into a stay). `?hotel=<code>&date=&to=` on the panel
URL opens a hotel on those dates directly.

## Ad hoc rates — visible, never quoted, paid

**New plan → Ad hoc rate (payment only)** makes a special-offer plan with
`applies_to = payment` and priority 10: give it the nights, the room type /
meal plan and the price agreed with the hotel, approve it. The rate-on-a-date
card then shows the contract rate in the cell as before with a green
**pay 120 / 120 / 160** strip beside it, an *Ad hoc* line (hotel paid vs
quotation, margin on a double) and an *ad hoc rate* flag under Watch out. The
quotation lookup, the timeline, `hotel_rate` and coverage ignore it
completely: if the normal rate is 200 and the ad hoc rate is 120, the customer
is still quoted 200; the hotel is paid 120; the 80 is our margin. Galas and
other compulsory supplements missing from the ad hoc plan are taken from the
contract plan when the hotel is paid.

## Hotel cost handoff → Tour Payment Dashboard

`HotelCostService` prices every hotel night of a **confirmed** tour
(`consignee.verify = 1` — the invoice generated) with purpose `payment` and
stores one row per `tour_quotations` night in `hr2_tour_hotel_cost`: quoted
SGL / DBL / TPL and payable SGL / DBL / TPL, room counts, compulsory
supplements for the night (per-person items × adults, per-room items × rooms),
the source — **adhoc** (payment-only plan) → **contracted** (contract plan)
→ **quoted** (no plan covers the night: the quotation figure is used and the
row says *"No ad hoc or special rate found and no contract rate for this night
— the quoted rate is used. Check the rate before payment."*) → **none**
(nothing at all) — the plan / season used, the night's flags and a check
note. The quotation rows are never written.

When it runs: automatically when the invoice is generated
(`DebtorInvoiceNewController` confirms the tour, then computes; a failure is
logged and never blocks invoicing); when a confirmed tour is opened on the
dashboard and nothing is stored yet, or a plan of one of its hotels was
approved / superseded since (`ensureTour()`); when a plan is approved
(`projectHotel()` re-costs the confirmed tours with nights at that hotel from
today on); on demand — the dashboard's **Recalculate** button
(`POST tour/tour_payment_dashboard/recalc_hotel_cost {tour_id}`); and for
everything at once — panel → Settings → **Cost confirmed tours** (setup step
`cost_backfill`; `{all: true}` includes past tours).

Where it shows: the **Tour Payment Dashboard** hotel rows carry *Hotel cost
$ … [Ad hoc | Contracted rate | Quoted rate — no contract rate found | Mixed —
check]*, the margin against the quoted $ (red when negative), the plans used
and the check note; the Payment Request modal defaults the amount to the
payable cost, shows the quoted figure and margin, and lets the request go up
to the higher of the quoted amount and the cost. The **Payment Forecast
(Tour Accounts)** hotel rows show the same line and default the request to the
cost. The **Payment Approval** page's *System Hotel Rate* column shows the
payable total, its source badge, every night (ad hoc / contract / quoted ⚠
with supplements), quoted vs margin, the check notes, and *requested ≠
payable* when the request differs from the cost. A tour that is not confirmed
has no rows and the screens look exactly as before.

## Hotel Incomplete Work — the permanent list of what is missing

**Master Files → Hotel Incomplete Work** (`master_files/hotel_work`) is where
everything the system could not settle by itself lands. Nothing is ever guessed:
each item says what is missing, for which hotel and nights, which tours it
affects and what would close it. Detectors:

| Kind | Raised when |
|---|---|
| No contract rate (confirmed tour) | a confirmed tour night is paid at the quoted rate because no plan covers it — one item per hotel **and month**, listing the tours and saying which approved plan ends before and which starts after the gap |
| Hotel not in the panel | a hotel used on tours has no hotel record in the panel at all |
| Quoted nights without a plan | upcoming quotations (not yet confirmed) use a hotel on nights no approved plan covers |
| No rate while quoting | the quotation timeline asked for a rate and the panel had none (the old row was used, or nothing at all); or the plain New Tour form (`tour/tour` — no date, so it only ever reads the old `hotel_rate` table) found no usable row for that hotel/room/meal |
| Draft waiting approval | a plan sits in the approval queue |
| Missing supplier record | a panel hotel with no supplier code |
| Rate sheet without a supplier | a transcribed sheet in `writable/hotel_rates_entry/` whose hotel has no supplier record — **Attach supplier code** on the item creates the hotel from the supplier record, writes the code into the sheet file and loads the plan as a draft |
| Unusual margin | payable above the quoted amount (red), or a contract margin above Settings › *Flag a hotel margin above …* (amber, default 40 %) |
| Country not mapped to a market | a quotation country with no market mapping (the default market was used) |
| Merge to confirm / Rate sheet query / Other | added by hand, or loaded from `writable/hotel_rates_entry/work_items.json` |

Items are keyed, so a scan refreshes what it finds, **closes what it no longer
finds** ("No longer found — closed by the system") and reopens anything that
comes back. People close the rest with a note, or *Ignore* one with a reason;
hand-added items are never closed automatically. Scans run when the panel is
opened (at most every Settings › *Re-scan … minutes*, default 10), after a tour
is costed, after a plan is approved or deleted, from **Refresh**, from the
nightly `php spark hotelrates:project`, and from the setup step `work_scan`.
**Export for Claude** copies the open items as plain text — hand it over with the
missing sheets or codes and the gaps get filled from the same list.

### Supplier details — staging what is known before the code exists

A **Rate sheet without a supplier** or **Missing supplier record** item is
often blocked on nothing more than someone creating the supplier record — and
the address, chain, contact person, phone or email needed to do that is
usually already sitting in the rate-sheet email. **Supplier details** on the
item lets that be typed in and kept right there, *before* the supplier code
exists, so it is not re-hunted later and is not lost:

- It never creates, guesses or invents a supplier code — that is still only
  done via **Master Files → Supplier**, ledger accounts `6-9-11-<PR code>`,
  exactly as before. This is purely a notepad attached to the item.
- The item's row shows what was saved (chain · address · contact · phone ·
  email, plus any free-text note) and, in amber, **Still need: …** for
  whichever of *address* / *phone or email* is still blank — so a glance at
  the panel says exactly what is left to chase, per hotel.
- Saved details survive the item being re-detected by the next scan (a
  sticky sub-field of `data_json`, carried forward — see
  `WorkQueueService::STICKY_DATA_KEYS`), and once **Attach supplier code**
  is used, the modal opens with a "Known so far — …" reminder built from
  whatever was staged, so nothing has to be re-typed.
- Leaving a field blank when saving clears it; nothing is required, so a
  partial detail (just a phone number, say) is fine to save as-is.

Endpoint: `POST item/(:num)/supplier_details {chain, address, contact_person,
phone, email, notes}` → `WorkQueueService::saveSupplierDetails()`. Restricted
to the `sheet_no_supplier` / `missing_supplier` kinds
(`WorkQueueService::SUPPLIER_DETAIL_KINDS`).

## Duplicate hotels: merge and undo

The **Duplicates** button groups hotels whose names match after normalising.
Two hotels in different towns are usually two properties of one brand (Wild
Glamping Gal Oya / Knuckles, Anantaya Chilaw / Passikudah): such groups carry a
*different locations* warning and merging them asks you to type MERGE. A
merge writes what it moved (codes, plans, imports, room types moved or folded)
to the audit row; the kept hotel then lists the hotel it absorbed with an
**Undo merge** button (approval privilege) that puts everything back — code,
plans, room types (a folded room type comes back as a copy), supplier
visibility — and rebuilds the old-screen rows of both hotels. For merges made
before the log carried details, the plans are inferred (a migrated plan from
the order the migration created them, a hand-entered plan from its own insert
record) and the preview says so.

The projector writes the old-screen rows for **every** supplier code of a
hotel, so a duplicate code that was merged in keeps pricing in the quotation
timeline.

## The old Hotel Rate screen

Approving, superseding, correcting a plan or merging hotels rebuilds that
hotel's rows in `hotel_rate` through `LegacyProjector`: every date interval ×
room type → the 12 legacy numbers as the engine answers them for the default
market and a booking made today. Per-person plans are multiplied by occupancy;
whole-unit prices cannot be expressed in the old table and are reported as
skipped. Rows written this way are tracked in `hr2_legacy_map`.

Because those rows are rebuilt, the old screen is **locked** once
`hotelrates:migrate` has copied the hotels across (Settings › Old screen:
*every hotel* — the default, *only hotels that have an approved plan here*,
or *open*). A locked hotel shows a banner with a link into the new panel, its
inputs are disabled, and the old controller refuses `create / update /
delete / ai_confirm` for it and skips it in Excel uploads — even if someone
calls the endpoints directly. Until the migration has run there is nothing
to redirect to, so the old screen keeps working exactly as before.

## Checks (RateValidator)

Errors block approval: hotel unmatched (E01), validity / currency / tier
limits (E02), season dates (E03), season outside validity (E04), duplicate
rows or overlapping seasons inside the plan (E05), room / meal plan (E07),
empty rows or no double rate (E08), zero / negative rate (E09), supplement
(E10), booking window (E11), markets (E12), meal delta (E13), child rule
(E14), cancellation rule (E15), room supplement (E16), parent plan (E17),
unit pricing (E18).

Warnings must be confirmed: gaps (W01), expiry (W02), meal order (W03),
occupancy order — the "35 for 135" typo (W04), tiny / huge (W05), no gala on
the configured gala nights (W06), child bands (W07), room type will be created
(W08), > *n* % change against the previous plan (W09), overlap with an
approved plan of the same kind and markets (W10), cancellation bands out of
order (W11), restriction outside validity (W12), booking window closed (W13),
supplement-priced room with its own rows (W14), taxes excluded without a %
(W15), room cap on a normal contract (W16), long minimum stay (W17), empty
guide rule (W18), per-person / per-unit sanity (W19). The thresholds behind
W02, W05, W06, W07 and W09 come from Settings.

## Test harness

The classes only use `query()`, `insertID()`, `transStart/Complete/Status()`
on the connection, so they were tested outside CodeIgniter against a copy of
the production tables under MySQL strict mode: install / upgrade (3 → 7 in
place) / migration (21 checks), 26 plans transcribed from the real rate sheets
with 113 lookup, projection and validator checks, 16 import checks, 41
settings / country-market / tier / threshold / lock checks, 5 + 8 checks of
the old screen's lock, 30 checks of the rate-on-a-date card, stays and flags,
19 checks of merge / undo merge, 14 checks of ad hoc (payment-only) plans,
17 checks of the hotel cost handoff (ad hoc → contract → quoted flagged,
supplements, staleness, the quotation untouched), 47 checks of Hotel Incomplete
Work (every detector, the grouping per hotel and month, auto-close and reopen,
the quoting gap event, the dateless New Tour form gap and its own recheck,
attach-supplier-and-load, the seed file, the export), and 70 headless-browser
checks of both panels as an approver and as a user with the panel privilege
only. Ask for the harness if you want to run it here.
