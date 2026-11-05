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
adds the settings rows, creates and seeds the country → market table from the
system's own country list, and (with `--menu`) creates the two menu entries
below granted to System Administrator. If spark is awkward, paste
`app/Database/Sql/hotel_rates_v2_schema.sql` and `hotel_rates_v2_menu.sql`
into phpMyAdmin instead.

## 2. Menu items (Settings › Menu List) — or let `--menu` create them

| Field | Entry 1 | Entry 2 |
|---|---|---|
| Menu Category | Master Files | Master Files |
| Item Name | Hotel Rates (new) — rename to "Hotel Rates" when the old screen retires | Hotel Rate Approval |
| Page URL | `master_files/hotel_rate_v2` | `master_files/hotel_rate_approval` |

The URL is the privilege. Everything the panel calls lives under those two
paths, so no route aliases are needed for the panel itself. An entry created
this way appears in the sidebar under Master Files **and** as a tile inside
the Master Files modal on the tile dashboard (both read the same rows). If
you would rather have Hotel Rate Approval as a privilege only, set its
`show_in_menu_bar` to 1 in the database — that hides it from both.

The old entry `master_files/hotel_rate` stays. The old screen keeps working
for viewing, and is locked for editing on hotels the new panel manages (see 5).

## 3. Group privileges (Settings › Group Privileges)

| Group | Grant | They can |
|---|---|---|
| Staff who type in rate sheets | Hotel Rates (new) | open the panel, enter and edit drafts, import Excel / read PDFs as drafts, room inventory, hotel details, coverage, Try a quote, health check, view Settings |
| Whoever signs rates off (you, a manager) | Hotel Rates (new) **and** Hotel Rate Approval | everything above, plus approve, supersede, correct approved plans, import with "Save & approve", merge duplicate hotels, change Settings and the country → market table, the approval queue |
| System Administrator (group 1) | nothing to do | always has both |

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
* **Old screen**: *locked for hotels that have an approved plan here* (default). Switch to *locked for every hotel* once the migration is accepted, so no new rates are typed into the old screen by mistake.
* **Country → market**: one row per country in the system's country list. The quotation form stores the agent's country (ISO code, filled from the agent record), so this table is how a quotation will find its market. Check the agents you actually work with: Japan → JP, India → IN, the Gulf → ME, Singapore → FE (change it to JP if a Singapore desk sells Japanese guests, and say so in the note).

## 6. Nightly job

`php spark hotelrates:project` (Windows Task Scheduler on the server) — offers
with booking windows switch on and off as the calendar moves, and the rows the
old screen and the timeline read are rebuilt.

## 7. Next step, planned separately: the quotation side

`NewTourController::get_meal_prices()` keeps reading `hotel_rate` today. When
we plan the timeline switch, it will call
`HotelRateService::lookup($hotel_code, $room_type, $meal, $date, ['country' => <consignee.country>, 'rooms' => n])`
and get the rate, the market used, the compulsory supplements and the
restrictions for that night. If the timeline calls the JSON endpoint
`master_files/hotel_rate_v2/lookup` directly instead, add that path to the
`tour/booking_confirm` list in `UserPermissionFilter::MENU_ROUTE_ALIASES`
(where `tour/tour/get_meal_prices` already is).

## 8. Housekeeping when everything is accepted

* Decide on the "Read a PDF" button (needs the OpenAI key in `Config\OpenAI`; the button only shows when a key is present).
* Delete `public\_schema_dump2_k7q2.php` and `writable\schema_dump*` on the PC (left over from the code review).
