# Hotel rate sheets vs. Hotel Rates v2 — gap analysis

Source: 48 rate-sheet e-mails (52 unique PDFs + content-bearing bodies) covering
Thema Collection (17 hotels), MOI/DBI (9), Aitken Spence (8 hotels × 3 offers +
supplement summary), Fort Resorts (3), Blue Water (4 contracts), Radisson (3
hotels tactical + Radisson Collection Galle), Amaya/Hayleys (3), Hilton Yala,
Sheraton Kosgoda, Morven, Casamara (2 seasons), Centauria Lake, Elephant Reach,
Tamarind Tree, Simpsons Forest, Grand Serendib, Deewara Tangalle.

Legend for "v2 today": OK = held faithfully; PART = held with loss of detail;
NO = cannot be entered without inventing something.

## A. Contract identity and scope

| # | Construct seen in sheets | Examples | v2 today | v3 design |
|---|---|---|---|---|
| A1 | Several rate plans valid at once for the same hotel and dates (base contract + early bird + market SPO + tactical) | Aitken Spence Early Bird / CHN-JAP SPO1 / AME SPO1 all Winter 26/27; Radisson tactical vs contracted | NO (E06 blocks overlapping approved contracts of same market) | `hr3_contract` = one *rate plan*. Overlap allowed across plans; `plan_type` (contract / special_offer / tactical / early_bird / addendum / migrated) + `priority` decide which one the lookup uses first. |
| A2 | Booking window ("book before 31 Oct 2026", "on or before 30 Nov") separate from stay dates | Aitken Spence EB, Radisson tactical, Sheraton tactical | NO | `book_from` / `book_to` on the plan; lookup checks booking date. |
| A3 | Rate code / reference printed by the hotel (must appear on voucher) | Aitken Spence `HA/W26_27/AME/01`, Blue Water "market code", Hilton "Honeymoon" booking code | NO | `rate_code`, `voucher_note` on the plan. |
| A4 | Market list per plan; some plans exclude markets | Fort Sigiriya "Asia & ME (excluding China / Japan)", "Europe / Australia", "China | Russia", "Japan"; Blue Water special markets; Aitken Spence per market | PART (single free-text `market`) | `hr3_contract_market` rows (market code + include/exclude). Lookup takes the agent's market; "ALL" fallback. |
| A5 | Addendum that changes only part of a base contract ("all other supplements as per original contract") | Aitken Spence SPOs, Radisson Collection Galle, Radisson tactical | NO | `parent_contract_id`; lookup falls through to parent for anything the addendum does not define. |
| A6 | Contract metadata: contract date, issue date, contract type (DMC / LTA / wholesale), signed?, VAT reg no., payment done by, credit facility yes/no, "rates include tax" flag, exchange-rate rule | Thema, Fort, Hilton, Sheraton | PART | Fields on `hr3_contract`: `contract_date`, `contract_type`, `hotel_reference`, `credit_facility`, `signed_status`, plus free `terms` JSON for the rest. |
| A7 | Multi-hotel documents (one PDF, 3–8 hotels) | Aitken Spence, Radisson, Amaya, MOI | PART (one contract per hotel, manually) | Import splits per hotel; `hr3_import` keeps one payload, many contracts; `source_file` shared. |
| A8 | Superseding: "instead of the previously quoted rates" | Amaya | OK | keep `supersede`. |
| A9 | "Continue same rate until we issue new rates" (rolling extension) | Morven | NO | `valid_to` editable + `extended_note`; coverage check warns. |

## B. Seasons and dates

| # | Construct | Examples | v2 | v3 |
|---|---|---|---|---|
| B1 | Non-contiguous date sets with one rate | Casamara "01 Nov–20 Dec & 01–30 Apr"; Grand Serendib; Radisson Collection Galle "24–29 Dec & 2–7 Jan" | NO (one from/to per period) | `hr3_season` has many `hr3_season_date` ranges. |
| B2 | Season names by month ("August", "Peak", "Festive") | Elephant Reach, Fort, Grand Serendib | PART | season `name` + `kind` (normal / peak / festive / low). |
| B3 | Minimum stay per season / festive min stay | Hilton 22 Dec–1 Jan 2 nights; Ayurvie 3–7 nights | PART (min_stay on period) | `min_stay` on season **and** on plan; plus `hr3_restriction` (date range, kind = min_stay / closed_to_arrival / closed_to_departure / meal_plan_required). |
| B4 | Meal-plan restriction on dates ("24 & 31 Dec only HB/FB/AI") | Centauria, Fort, Blue Water | NO | `hr3_restriction kind=meal_plan_required, value="HB,FB,AI"`. |
| B5 | No check-out allowed on a date | Sheraton "no bookings checking out 31 Dec" | NO | `hr3_restriction kind=closed_to_departure`. |
| B6 | Max rooms per period under an offer (allotment cap) | Radisson tactical "Maximum rooms 30/20/10" | NO | `max_rooms` on season. |
| B7 | Validity spanning summer+winter, calendar year, or 3 seasons in one letter | Centauria (3 seasons), Tamarind (Jan–Dec), Fort (May–Mar) | OK | unchanged. |

## C. Rate grid shapes

| # | Construct | Examples | v2 | v3 |
|---|---|---|---|---|
| C1 | Per-room SGL/DBL/TPL × RO/BB/HB/FB/AI | Radisson, Morven, Casamara | OK | `hr3_rate` rows: season × room type × meal plan × occupancy. |
| C2 | Quad / 4th person / FAMILY column | Elephant Reach FAMILY; Tamarind "4th pax 15 BB / 25 HB / 30 FB"; Thema "4th person supplement" | NO | occupancy code `QUAD` (or family) + per-plan `extra_adult` supplement by meal plan. |
| C3 | Per-person pricing (per person sharing) | Ayurvie, Wild Glamping ("per person") | PART (`rate_basis`) | `pricing_mode` on plan: per_room / per_person / per_unit. |
| C4 | Unit pricing (whole villa/bungalow by pax count, dormitory) | Thema bungalows "up to 6 pax", Oruwa villa, Hikka House dorm | NO | `pricing_mode=per_unit`, `hr3_rate.occupancy = 'UNIT'`, `unit_pax_min/max`. |
| C5 | Base room + supplement for higher categories (incl. negative) | Sheraton, Radisson, Amaya, Aitken Spence, Fort, Blue Water, Simpsons | PART (supplement type room_supplement, but no link to which room) | `hr3_room_type.priced_as = 'base'|'supplement'`, `hr3_room_supplement` (room type, season optional, amount, per room/person). Lookup = base rate + supplement. |
| C6 | Meal-plan deltas relative to a base basis (BB reduction, FB/AI supplement, RO reduction) with adult and child amounts | Blue Water, Amaya, Fort, Aitken Spence, Sheraton AI on BB/HB, Radisson AI | NO | `hr3_meal_delta` (from plan, to plan, adult amount, child amount, per person per night). Lookup derives missing meal plan rows. |
| C7 | Child column inside the grid ("Child supplement sharing bed" per season) | Fort (4th column) | PART (child_rate on rate row) | `hr3_rate` keeps `occupancy` rows; child handled by `hr3_child_rate` (age band, with/without bed, meal plan, amount or % of adult). |
| C8 | Rows missing = not offered; "-" or "X" placeholders | MOI ("-"), Radisson ("X"), Aitken Spence (HB missing in some seasons) | OK (no row) | unchanged; import treats `-`, `X`, `N/A`, blank as "not offered". |
| C9 | Rate given without a stated meal plan (implied by hotel) | Rainforest Ecolodge (AI implied) | NO | plan-level `default_meal_plan`; import applies it. |
| C10 | Percent-based child rate ("50% of adult rate") | MOI, Aitken Spence "50% of meal supplement" | NO | `hr3_child_rate.pct_of_adult`, `basis` (room_rate / meal_rate / supplement). |
| C11 | Tier grids: FIT vs Group (by rooms per booking), wedding, long-stay | Blue Water (FIT <5 rooms, Group ≥5), Thema groups >15 pax reduction, Elephant Reach 10+ rooms −5 | NO | `hr3_contract.tier` (`fit`, `group`, `wedding`, `long_stay`, `all`) + `tier_min_rooms`, `tier_min_pax`; each tier is its own plan under one `contract_group`; or group reduction as `hr3_offer kind=group_reduction`. |

## D. Supplements, extras and children

| # | Construct | Examples | v2 | v3 |
|---|---|---|---|---|
| D1 | Gala/festive per person by basis (BB vs HB/FB/AI amounts) with child amount or % | Everywhere | PART (one amount, child %) | `hr3_supplement` with `applies_meal_plans`, `adult_amount`, `child_amount`, `child_pct`, `compulsory`, dates. One row per basis group. |
| D2 | Peak / festival / Perahera / August supplements per room or per person, sometimes by occupancy (SGL/DBL/TPL amounts) | Casamara Perahera SGL 55/DBL 65/TPL 75; Elephant Reach peak per room; Fort August per room; Grand Serendib Esala Perahera per person | PART | `hr3_supplement.per = room|person|adult|child|night|booking` + optional `occupancy` column amounts (`amount_sgl/dbl/tpl`). |
| D3 | Extra meals (breakfast/lunch/dinner) adult & child | all | NO (not modelled) | `hr3_meal_price` (meal, adult, child). |
| D4 | Extra bed price; child with bed vs without bed | all; STW asked explicitly for both | PART (`extra_bed` on rate row) | `hr3_child_rate` rows: age_from/to, `bed` (with/without/any), meal plan or ALL, amount or pct; plus `extra_bed_adult` per plan. |
| D5 | Child age bands vary (0–4.99 / 5–11.99; 0–5.99 / 6–11.99; 0–2.99 / 3–11.99; 7–12) and max children | all | PART (child_free_under, child_max_age) | age bands on `hr3_child_rate`; `max_children_free` on plan. |
| D6 | Extra adult / 3rd person supplement by meal plan; extra person only from certain category | Sheraton adult 40 BB / 70 HB; Radisson Collection "only from Premium upwards" | NO | `hr3_supplement type=extra_adult` with `applies_meal_plans`, `room_type_id` filter. |
| D7 | Early check-in / late check-out rules (fixed fee, 50% of rate, previous night) | Simpsons, Fort, Centauria (USD 15), Hilton | PART (supplement types) | `hr3_supplement type=early_checkin|late_checkout` amount or `pct_of_rate`, note. |
| D8 | Excursion / ancillary price lists from the hotel | Simpsons, Thema, Morven airport transfers, MICE | NO | `hr3_extra` (name, per, adult, child, note) — informational, not used by lookup. |

## E. Guide, driver, FOC, offers

| # | Construct | Examples | v2 | v3 |
|---|---|---|---|---|
| E1 | Guide room rate per meal plan (per person or per room), LKR amounts, "50% of single rate", FOC above N pax, guest-room rate if guide room unavailable | Thema, Casamara, Elephant Reach, Amaya, Centauria (LKR 9,500), Radisson (50% SGL, min 6 rooms), Blue Water | PART (free text) | `hr3_guide_policy` rows: role (guide / driver / tour_leader), meal plan, amount, currency, pct_of (sgl), free_above_pax, min_rooms, note. |
| E2 | FOC rules: every 15th/16th pax; every 10th/7th room; 8th room at 50% | Radisson, Morven, Tamarind, Fort, Blue Water | PART (`foc_policy` text) | `hr3_foc_rule` (basis pax/rooms, every_n, free_n, discount_pct). |
| E3 | Offers: early booking %, long-stay free nights, honeymoon inclusions, group reduction, non-combinable, excluding peak | Thema, Hilton, Elephant Reach, Aitken Spence | NO | `hr3_offer` (kind, value, conditions JSON, date/booking windows, combinable, excludes seasons). Informational + used by validator for W-codes only. |

## F. Money, tax, payment, cancellation

| # | Construct | Examples | v2 | v3 |
|---|---|---|---|---|
| F1 | Tax treatment: inclusive; +18% VAT; 34.34%; breakdown SC 10% / VAT 18% / SSCL 2.5%; "will be converted to LKR at THASL rate" | Hilton, Grand Serendib, Simpsons, Fort | PART (enum) | `tax_treatment` + `tax_pct` + `tax_note`; `billing_currency` (USD / LKR at day rate). |
| F2 | Cancellation matrices: season × FIT/GIT (pax or rooms tiers) × days-prior → % (or nights) | Thema (2 seasons × 2 tiers × 3 bands), Radisson, Fort, Amaya, Morven (rooms tiers → days), Grand Serendib | PART (free text) | `hr3_cancellation_rule` (season dates optional, tier, min/max rooms or pax, days_from, days_to, charge_pct, charge_nights, note). Free text kept too. |
| F3 | Payment terms: prepay N days; groups 50% advance 30 days; credit vs non-credit; 2% interest; card 3.25%; SSCL 2.5% on LKR; peak 50% advance | Thema, Blue Water, Hilton, MOI, Morven | PART (text) | `hr3_payment_rule` (applies_to fit/group/credit/non_credit, days_before, pct, note) + text. |
| F4 | Release period / tentative holds (48 h), rooming list 30 days | Blue Water, Centauria, Fort | NO | `release_days`, `tentative_hold_hours` on plan. |
| F5 | Group ≥N rooms "contact hotel" (rates not valid) | Hilton ≥5, Sheraton ≥10 | NO | `max_rooms_per_booking` on plan; validator warns at quote time (future). |

## G. Hotel master data

| # | Construct | Examples | v2 | v3 |
|---|---|---|---|---|
| G1 | Room inventory: count, size, beds, max adults/children, view | Thema, Hilton, Morven fact sheet, Grand Serendib, MOI "(8 rooms)" | PART | `hr3_room_type`: `room_count`, `size_sqm`, `bed_config`, `max_adults`, `max_children`, `max_occupancy`, `view`, `description`. |
| G2 | Hotel contacts (reservations e-mail/phone), bank details, check-in/out times, chain / management company | all | NO | `hr3_hotel`: `chain`, `reservation_email`, `reservation_phone`, `check_in`, `check_out`, `bank_details`, `address`, `star`. |
| G3 | Multiple hotels per e-mail sender / chain contact | Aitken Spence, Thema, Hayleys, DBI | NO | `chain` on hotel; import can assign per hotel. |

## H. Quality problems seen in the real sheets (validator must catch)

* Typo in a grid: Blue Water Summer 27 "HB 90 105 **35**" (TPL below DBL) → occupancy-order warning.
* Overlapping seasons in the same table: Blue Water "1 Jul–30 Aug" and "1 Jul–31 Oct" → overlap error inside plan.
* Stale years copied from last season: Grand Serendib peak "21 Dec 2025–10 Jan 2026" on a 2026/27 sheet → date-outside-validity error.
* Contradictory validity lines: Tamarind "01 Jan 2027 – 31 Dec 2027" and "01 Dec 2026 – 30 May 2027" → operator must choose; import shows both.
* Cancellation bands out of order (Tamarind 21–14 days 75%, 14–7 days 50%) → warning, not error (the hotel wrote it).
* Missing meal plan rows for some seasons (Aitken Spence SPO) → allowed, coverage grid shows holes.
* Season missing entirely in an offer (Turyaa 22 Dec–31 Mar) → allowed; lookup falls back to parent/base plan.

## I. What is *not* changing

* `hr2_*` naming stays for tables that keep their meaning (hotel, account, location, audit, import, legacy map). New tables are `hr3_*` only where the shape changed; in practice the whole set is recreated as `hr3_*` to avoid a half-migrated database — the install command migrates `hr2_*` data forward if present.
* Legacy write-through into `hotel_rate` continues: projection resolves one SGL/DBL/TPL per room type × meal plan × season date range, expanding non-contiguous sets and meal deltas so the timeline keeps working.
* Validator codes E01–E10 / W01–W09 keep their meanings; new codes are added (E11 booking window, E12 market, E13 bad delta, W10 tier, W11 cancellation order, W12 restriction, W13 child band gap).
