# Hotel Rates (new) — go-live sheet for Shantha

What to create in the system, in the order that works, and what each thing does.

## 1. Install / upgrade the tables (once, on the WAMP copy first)

```
cd C:\wamp64\www\avenir
php spark hotelrates:install --menu
php spark hotelrates:migrate --dry-run
php spark hotelrates:migrate
php spark hotelrates:check
```

`install` is safe to re-run: it upgrades the earlier schema-3 tables in place,
converts them to the database's collation, adds the settings rows, creates and
seeds the country → market table from the system's own country list, and
(with `--menu`) creates the two menu entries below granted to System
Administrator.

**Without spark** (what we did on 12 September): log in as System
Administrator, open `master_files/hotel_rate_v2`, press **Install now**, then
**Dry run** and **Copy hotels & rates across**. Settings › **Re-run install**
repeats the install step whenever files are updated.

## 2. Menu items (Settings › Menu List) — or let `--menu` create them

| Field | Entry 1 | Entry 2 | Entry 3 |
|---|---|---|---|
| Menu Category | Master Files | Master Files | Master Files |
| Item Name | Hotel Rates (new) — rename to "Hotel Rates" when the old screen retires | Hotel Rate Approval | Hotel Incomplete Work |
| Page URL | `master_files/hotel_rate_v2` | `master_files/hotel_rate_approval` | `master_files/hotel_work` |

The URL is the privilege. Everything the panels call lives under those three
paths, so no route aliases are needed for them. An entry created
this way appears in the sidebar under Master Files **and** as a tile inside
the Master Files modal on the tile dashboard (both read the same rows). If
you would rather have Hotel Rate Approval as a privilege only, set its
`show_in_menu_bar` to 1 in the database — that hides it from both.

The old entry `master_files/hotel_rate` stays. The old screen keeps working
for viewing; once `hotelrates:migrate` has run it is locked for editing (see 5).

## 3. Group privileges (Settings › Group Privileges)

| Group | Grant | They can |
|---|---|---|
| Staff who type in rate sheets | Hotel Rates (new) | open the panel, enter and edit drafts, import Excel / read PDFs as drafts, room inventory, hotel details, coverage, Try a quote, health check, view Settings |
| Whoever signs rates off (you, a manager) | Hotel Rates (new) **and** Hotel Rate Approval | everything above, plus approve, supersede, correct approved plans, import with "Save & approve", merge duplicate hotels, change Settings and the country → market table, the approval queue |
| Rates staff **and** the accounts staff who pay hotels | Hotel Incomplete Work | see what is missing (no contract rate, drafts, sheets without a supplier, unusual margins), close items with a note, attach a supplier code and load its sheet, export the list |
| System Administrator (group 1) | nothing to do | always has all three |

Users must log out and in after a grant (privileges are read at login).
Approvals are enforced twice: the permission filter blocks the approval URLs
for groups without the grant, and the controller re-checks the grant even if
someone sends the "approve" flag through the panel URL.

## 4. Dashboard buttons (tile dashboard)

The two menu entries above already give you tiles inside the **Master Files**
modal. For a button somewhere else — the Ground Handling modal next to
"Hotel Management", for example — add one more `menu_list` row with the same
URL under that category and grant it; the tile links to its URL and takes an
icon from the default cycle, nothing else is needed. (It will also be listed
under that category in the sidebar, exactly like the existing Tour Search /
New Booking tiles.)

```sql
-- example: "Hotel Rates" and "Approve Hotel Rates" tiles inside the Ground Handling
-- modal (cat_id 25), granted to System Administrator (add group ids to the UNION)
SET @admin_uid := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @cat := 25;   -- Ground Handling

INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar)
SELECT @cat, 'Hotel Rates', @admin_uid, 0, 'master_files/hotel_rate_v2', 201, 0
WHERE NOT EXISTS (SELECT 1 FROM menu_list WHERE cat_id = @cat AND name = 'Hotel Rates' AND show_in_menu_bar = 0 AND del = 0);

INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar)
SELECT @cat, 'Approve Hotel Rates', @admin_uid, 0, 'master_files/hotel_rate_approval', 202, 0
WHERE NOT EXISTS (SELECT 1 FROM menu_list WHERE cat_id = @cat AND name = 'Approve Hotel Rates' AND show_in_menu_bar = 0 AND del = 0);

INSERT INTO user_privileges (group_id, user_id, menu_id)
SELECT grp.gid, @admin_uid, ml.id
FROM menu_list ml CROSS JOIN (SELECT 1 AS gid) grp
WHERE ml.cat_id = @cat AND ml.del = 0 AND ml.show_in_menu_bar = 0 AND ml.name IN ('Hotel Rates', 'Approve Hotel Rates')
  AND NOT EXISTS (SELECT 1 FROM user_privileges up WHERE up.group_id = grp.gid AND up.menu_id = ml.id);
```

For a specific icon, add a line to `$actionTileMeta` in
`app/Views/pages/dashboard2/tile_dashboard.php` keyed `'25|Hotel Rates'`
(action `redirect`, target `master_files/hotel_rate_v2`).

For a widget that shows numbers, `GET master_files/hotel_rate_v2/summary`
returns `{hotels, hotels_priced_today, approved_plans, drafts, expiring,
expiry_days, last_approval}` for the logged-in user.

## 5. Settings (panel → Settings button; saving needs the approval grant)

Nothing is hard-coded. Review these once after install:

* **Default market** (JP) and **default currency** (USD).
* **FIT up to n rooms / Group from n rooms** (4 / 5) — used only when a sheet prints no limit.
* **Gala nights** (12-24, 12-31) and the usual child % (50).
* **Check thresholds**: tiny 10, huge 3000, 40 % change, 90 days expiry, child age 18.
* **Horizons**: coverage 365 days, health check 120 days.
* **Old screen**: *locked for every hotel* (default) — it takes effect the moment `hotelrates:migrate` has copied the hotels across; before that the old screen works as before because there is nothing to redirect to. *Only hotels that have an approved plan here* and *open* are the alternatives.
* **Country → market**: one row per country in the system's country list. The quotation form stores the agent's country (ISO code, filled from the agent record), so this table is how a quotation will find its market. Check the agents you actually work with: Japan → JP, India → IN, the Gulf → ME, Singapore → FE (change it to JP if a Singapore desk sells Japanese guests, and say so in the note).

## 5a. Hotel cost handoff (schema 6) — what to do once

* **Upgrade the tables**: panel → Settings → **Re-run install** (or `php spark hotelrates:install`). Schema 6 adds `hr2_contract.applies_to` and the `hr2_tour_hotel_cost` table; existing plans keep working. Done on the WAMP copy on 12 September.
* **Cost the confirmed tours once**: panel → Settings → **Cost confirmed tours** (setup step `cost_backfill`). Every confirmed tour with hotel nights from today on is priced ad hoc → contract → quoted-flagged and shown on the Tour Payment Dashboard, the Payment Forecast and the Payment Approval page. From then on it happens by itself when an invoice is generated, when a plan is approved, and when a confirmed tour is opened on the dashboard. Done on the WAMP copy on 12 September (138 tours).
* **Read the red badges**: *Quoted rate — no contract rate found* means no approved plan covers that night — usually a season not yet loaded (September/October 2026 nights show it wherever only the winter 2026/27 sheets exist) or a draft still waiting in the approval queue. Approve the plan or load the sheet and the tour re-costs itself; the request still defaults to the quoted figure meanwhile, so nothing is blocked.
* **Ad hoc rates**: New plan → *Ad hoc rate (payment only)* — for the hotel, the nights, the room type / meal plan and the price agreed. Approving it never changes a quotation; the confirmed tours at that hotel are re-costed and the dashboard shows *Ad hoc rate* with the margin.

## 5b. Hotel Incomplete Work (schema 7) — the permanent panel

**Master Files → Hotel Incomplete Work** (`master_files/hotel_work`) lists
everything the system found missing and did not guess. It is created by the same
Re-run install step and the menu entry is granted to System Administrator; give
it to the rates and the accounts staff under Settings › Group Privileges (one
more row, exactly like the other two Hotel Rates entries).

* **It fills itself.** Confirmed nights with no contract rate (grouped per hotel and month, with the tours listed), hotels missing from the panel, quoted nights no plan covers, drafts waiting for approval, rate sheets without a supplier record, unusual margins, unmapped countries. A scan runs when a panel is opened (at most every 10 minutes — Settings › Incomplete work), after a tour is costed, after a plan is approved, on **Refresh**, and in the nightly `php spark hotelrates:project`.
* **It empties itself.** When the sheet is loaded and approved, the item closes with "No longer found — closed by the system" and the affected tours re-cost themselves. Nothing has to be ticked off by hand.
* **What staff do here**: *Attach supplier code* on a transcribed sheet (creates the hotel from the supplier record and loads the plan as a draft), *Supplier details* on the same kind of item to save whatever is already known about that hotel — address, chain, contact, phone, email, a note — before its supplier code exists, so it is not re-typed or re-hunted later, *Done* with a note, *Ignore* with a reason, *Add item* for anything else, and **Export for Claude** — the open items as plain text to hand over with the missing information.
* **Supplier details, never a code.** *Supplier details* only stages descriptive text a person already has; it never creates or guesses the supplier code itself — that is still exclusively **Master Files → Supplier**, ledger accounts `6-9-11-<PR code>`. The item shows what is saved and flags in amber whatever is still missing (address, or a phone/email), and the details survive being re-detected by later scans. As of 12 September the 20 open "Rate sheet without a supplier" items already carry what `supplier_records_needed.md` had on file for their 15 hotels — 9 are now fully complete (chain, address and phone all known) and the other 11 are flagged for exactly the one or two fields their rate sheet never gave (almost always a street address).
* **Seeded on 12 September** with the 30 open questions from the rate sheets already transcribed (Blue Water's "35" triple, the Perahera dates, Grand Serendib's peak period, the three merges to confirm, the duplicate supplier records …): panel → **Load seed items** (System Administrator) re-reads `writable\hotel_rates_entry\work_items.json` at any time.

## 5c. The quotation timeline now prices from the panel

`tour/quotation/get_meal_prices` asks the Hotel Rates panel first (market from
the agent's country) and falls back to the old `hotel_rate` rows exactly as
before when no approved plan covers the night. Under the meal plan the timeline
shows which plan and season priced the night, or an amber "old rate row — no
plan" / red "no rate found", plus every compulsory item for that night (gala
dinner, peak, festival, minimum stay) — so nobody has to know the festive dates
by heart. Rates already saved on a quotation are never touched, and every gap is
listed in Hotel Incomplete Work.

## 6. Nightly job

`php spark hotelrates:project` (Windows Task Scheduler on the server) — offers
with booking windows switch on and off as the calendar moves, and the rows the
old screen and the timeline read are rebuilt.

## 7. Done: the quotation side

`NewTourController::get_meal_prices()` now calls
`HotelRateService::lookup($hotel_code, $room_type, $meal, $date, ['country' => <consignee.country>, 'rooms' => n])`
and returns the rate, the market used and the night's flags; the old
`hotel_rate` query stays as the fallback (and raises a work item). The URL did
not change, so no privilege change was needed. `tour/tour/get_meal_prices` (the
older, simpler "New Tour" screen, `tour.php` — one hotel/room/meal for the
whole tour, no per-night date) still reads `hotel_rate` only and always will:
switching it to the panel would mean guessing which single date represents a
multi-night stay, which nothing in this system does. What it does have now:
when that lookup finds no usable row (none at all, or a row with every price
at 0 for the chosen meal plan — the same "nothing usable" case as no row),
`TourController::get_meal_prices()` raises a `quote_no_rate` work item too, so
the gap lands in Hotel Incomplete Work instead of only a one-time popup. The
price returned to the browser is byte-for-byte unchanged either way — this is
visibility only. The item closes itself once a usable `hotel_rate` row exists
for that hotel/room/meal, the same self-closing pattern as everywhere else.

## 8. Housekeeping when everything is accepted

* Decide on the "Read a PDF" button (needs the OpenAI key in `Config\OpenAI`; the button only shows when a key is present).
* Delete `public\_schema_dump2_k7q2.php` and `writable\schema_dump*` on the PC (left over from the code review).
