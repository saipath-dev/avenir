-- ============================================================================
-- Tour Flow — schema version 1  (inquiry intake)
-- Avenir Holidays management system (CodeIgniter 4, MySQL 8 / MariaDB 10.11)
--
-- Phase 1 of "one tour, one record": capture what an agent actually sent, read
-- it, let a person correct it, and keep the original alongside for ever.
--
-- These tables are additive. Nothing here touches consignee, tour_quotations,
-- tour_itinerary, hotel_rate or any hr2_ table. An intake becomes a tour only
-- when somebody converts it, and until then nothing appears on any existing
-- screen.
--
-- Vocabulary
--   intake      one inquiry as it arrived — the email, what was read from it,
--               and what a person corrected afterwards
--   field       every extracted value carries where it came from: a verbatim
--               quote from the email, or nothing at all. A value with no quote
--               behind it is never stored as if the agent had said it.
--   day         one requested night, as the agent described it
--   request     something the agent asked for, kept word for word
--
-- Safe to re-run: every statement uses IF NOT EXISTS / INSERT IGNORE.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `tf_meta` (
  `k` VARCHAR(40) NOT NULL,
  `v` TEXT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- extract_provider decides which service reads an email: 'anthropic' (Claude) or
-- 'openai'. Changing it here changes the service; no code change is involved. The
-- rate-sheet reader under Master Files → Hotel Rates is separate and unaffected.
INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  ('schema_version', '1'),
  ('inquiry_prefix', 'INQ'),
  ('inquiry_next', '1001'),
  ('extract_provider', 'anthropic'),
  ('extract_model', 'claude-sonnet-5'),
  ('quote_min_chars', '8'),
  -- Published contract rates only in a customer quotation. Tactical and special-offer
  -- plans stay on file for paying the hotel after confirmation and are never quoted.
  ('quote_plan_types', 'contract,migrated,addendum,series');

-- One inquiry. `source_text_original` (schema 14) is the email exactly as it was
-- pasted and is never rewritten; `source_text` is the copy the reader works from,
-- which staff may correct before it is read; `extract_json` is exactly what the
-- model returned; `fields_json` is the working copy after a person has corrected
-- anything.
CREATE TABLE IF NOT EXISTS `tf_intake` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inquiry_no`         VARCHAR(20)  NOT NULL,
  `status`             ENUM('new','working','quoted','confirmed','declined','cancelled','converted') NOT NULL DEFAULT 'new',

  `agent_acc`          VARCHAR(50)  NULL,
  `agent_name`         VARCHAR(190) NULL,
  `agent_email`        VARCHAR(190) NULL,
  `country_iso`        CHAR(2)      NULL,
  `market_code`        VARCHAR(10)  NULL,

  `tour_name`          VARCHAR(190) NULL,
  `arrival_date`       DATE NULL,
  `departure_date`     DATE NULL,
  `nights`             SMALLINT UNSIGNED NULL,
  `pax_total`          SMALLINT UNSIGNED NULL,
  `pax_adults`         SMALLINT UNSIGNED NULL,
  `pax_children`       SMALLINT UNSIGNED NULL,
  `rooms_note`         VARCHAR(255) NULL,
  `hotel_class`        VARCHAR(60)  NULL,
  `arrival_flight`     VARCHAR(160) NULL,
  `departure_flight`   VARCHAR(160) NULL,
  -- schema 32: the departure day is the day after the last night, never a hotel night. Its own
  -- wording for the client (a person's), what the email said of it, and a note for the desks.
  `departure_text`       VARCHAR(2000) NULL,
  `departure_narrative`  TEXT NULL,
  `departure_note`       VARCHAR(500) NULL,
  -- schema 32: the email's own dates, kept beside the inquiry's; when they differ a person
  -- confirms which stand, and the confirmation is kept with the pair it was given for
  `email_arrival_date`   DATE NULL,
  `email_departure_date` DATE NULL,
  `dates_confirmed_key`  VARCHAR(40) NULL,
  `dates_confirmed_by`   VARCHAR(100) NULL,
  `dates_confirmed_at`   DATETIME NULL,

  `source_subject`     VARCHAR(400) NULL,
  `source_from`        VARCHAR(255) NULL,
  `source_received_at` DATETIME NULL,
  `source_text`        MEDIUMTEXT NULL,

  `extract_json`       MEDIUMTEXT NULL,
  `fields_json`        MEDIUMTEXT NULL,
  `extract_model`      VARCHAR(60) NULL,
  `extract_ms`         INT UNSIGNED NULL,
  `extracted_at`       DATETIME NULL,
  `extract_error`      VARCHAR(400) NULL,

  `tour_id`            VARCHAR(50) NULL,
  `converted_at`       DATETIME NULL,
  `converted_by`       VARCHAR(100) NULL,
  `confirmed_at`       DATETIME NULL,
  `confirmed_by`       VARCHAR(100) NULL,
  -- schema 20: when to chase the agent about a quotation, and a word on what for
  `follow_up_on`       DATE NULL,
  `follow_up_note`     VARCHAR(255) NULL,
  -- schema 20: once a person has added, changed or removed a crew line the crew is theirs,
  -- and the planner does not put a guide back that the office took off. Auto crew resets it.
  `crew_by`            ENUM('auto','human') NOT NULL DEFAULT 'auto',
  `note`               TEXT NULL,

  `created_at`         DATETIME NOT NULL,
  `created_by`         VARCHAR(100) NULL,
  `updated_at`         DATETIME NOT NULL,
  `updated_by`         VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_intake_no` (`inquiry_no`),
  KEY `ix_tf_intake_status` (`status`, `arrival_date`),
  KEY `ix_tf_intake_agent` (`agent_acc`),
  KEY `ix_tf_intake_tour` (`tour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One requested night. hotel_name_raw is what the agent wrote; hotel_id is what
-- it matched in the Hotel Rates panel, and rate_state is what that panel says
-- about those exact nights for this market. Published rates only — an ad hoc
-- payment-only plan is never consulted here and never shown to an agent.
CREATE TABLE IF NOT EXISTS `tf_intake_day` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`       INT UNSIGNED NOT NULL,
  `day_no`          SMALLINT UNSIGNED NOT NULL,
  `day_date`        DATE NULL,
  `area`            VARCHAR(160) NULL,
  `hotel_name_raw`  VARCHAR(190) NULL,
  `hotel_id`        INT UNSIGNED NULL,
  `hotel_acc`       VARCHAR(50)  NULL,
  `match_note`      VARCHAR(190) NULL,
  `meal_plan`       VARCHAR(4)   NULL,
  `rooms`           SMALLINT UNSIGNED NULL,
  `nights`          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `rate_state`      ENUM('rate','no_plan','no_supplier','not_in_panel','nothing_named','not_checked') NOT NULL DEFAULT 'not_checked',
  `rate_note`       VARCHAR(255) NULL,
  `checked_at`      DATETIME NULL,
  `narrative`       TEXT NULL,
  `special_note`    VARCHAR(500) NULL,
  -- schema 28: a person's own wording for the day on the client quotation; blank means the standard wording
  `client_text`     VARCHAR(2000) NULL,
  `origin`          ENUM('ai','human') NOT NULL DEFAULT 'ai',
  `source_quote`    VARCHAR(1000) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_day_intake` (`intake_id`, `day_no`),
  CONSTRAINT `fk_tf_day_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Names as the agent gave them. Nothing else is asked for: no passport, no date
-- of birth, no nationality — the agents do not send it and the system does not
-- pretend to hold it.
CREATE TABLE IF NOT EXISTS `tf_intake_pax` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`   INT UNSIGNED NOT NULL,
  `full_name`   VARCHAR(160) NOT NULL,
  `room_type`   VARCHAR(20) NULL,
  `note`        VARCHAR(160) NULL,
  `origin`      ENUM('ai','human') NOT NULL DEFAULT 'ai',
  `sort_order`  SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_pax_intake` (`intake_id`, `sort_order`),
  CONSTRAINT `fk_tf_pax_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- What the agent asked for or asked about, kept word for word so nothing is
-- lost in a summary. A question stays open until somebody answers it here.
CREATE TABLE IF NOT EXISTS `tf_intake_request` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`    INT UNSIGNED NOT NULL,
  `kind`         ENUM('request','question') NOT NULL DEFAULT 'request',
  `title`        VARCHAR(255) NOT NULL,
  `quote_text`   VARCHAR(1000) NULL,
  `status`       ENUM('open','answered','done','dropped') NOT NULL DEFAULT 'open',
  `answer`       VARCHAR(1000) NULL,
  `answered_by`  VARCHAR(100) NULL,
  `answered_at`  DATETIME NULL,
  `origin`       ENUM('ai','human') NOT NULL DEFAULT 'ai',
  `sort_order`   SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_req_intake` (`intake_id`, `sort_order`),
  CONSTRAINT `fk_tf_req_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 2 -------------------------------------------------------------------
-- What happens on a day besides sleeping somewhere: sightseeing and entrance
-- visits, a safari, a train leg, an ayurveda treatment, a lunch, a tuk tuk.
--
-- These are priced from the existing entrance_fee master, which holds USD, and
-- turned into rupees with the daily_usd_rate for the day — the same pair of
-- numbers tour_activity already keeps as fee and lkr_fee. Nothing here writes to
-- either of those legacy tables; the link is kept soft, by code, so this module
-- can never damage the master list or the live tours that depend on it.
--
-- day_no 0 means the service belongs to the whole tour rather than one night
-- (a guide for the full trip, an airport expense).
CREATE TABLE IF NOT EXISTS `tf_intake_service` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`    INT UNSIGNED NOT NULL,
  `day_no`       SMALLINT NOT NULL DEFAULT 0,
  `kind`         ENUM('visit','entrance','safari','train','ayurveda','lunch','dinner','guide','transport','other') NOT NULL DEFAULT 'other',
  `title`        VARCHAR(255) NOT NULL,
  -- what the email called it when the line was first read. Kept so that renaming a
  -- line by hand does not make the next read of the same email create it all over again.
  `source_title` VARCHAR(255) NULL,
  `fee_id`       INT NULL,
  `fee_code`     VARCHAR(100) NULL,
  `match_state`  ENUM('matched','not_in_list','nothing_named','not_checked','no_charge') NOT NULL DEFAULT 'not_checked',
  `match_note`   VARCHAR(255) NULL,
  -- schema 25: the closest entry in the priced list, by code, offered for a person to accept
  `suggest_code` VARCHAR(100) NULL,
  `pax`          SMALLINT NULL,
  `qty`          DECIMAL(10,2) NULL,
  `qty_override`  TINYINT(1) NOT NULL DEFAULT 0,
  `qty_reason`    VARCHAR(255) NULL,
  `qty_set_by`    VARCHAR(100) NULL,
  `qty_set_at`    DATETIME NULL,
  `unit_usd`     DECIMAL(10,2) NULL,
  `amount_usd`   DECIMAL(12,2) NULL,
  `usd_rate`     DECIMAL(10,2) NULL,
  `rate_date`    DATE NULL,
  `amount_lkr`   DECIMAL(12,2) NULL,
  `pay_mode`     ENUM('in_tour','prepaid','direct','own_arrangement','not_arranged','unknown','no_charge','in_rate','optional') NOT NULL DEFAULT 'unknown',
  `special_note` VARCHAR(500) NULL,
  `source_quote` VARCHAR(1000) NULL,
  `origin`       ENUM('ai','human') NOT NULL DEFAULT 'ai',
  `sort_order`   SMALLINT NOT NULL DEFAULT 0,
  `checked_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_svc_intake` (`intake_id`, `day_no`, `sort_order`),
  KEY `ix_tf_svc_code` (`fee_code`),
  CONSTRAINT `fk_tf_svc_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 4 -------------------------------------------------------------------
-- Who drives and who guides, and what they are carried in.
--
-- Kept as two separate things on purpose: a vehicle is hired by the day and the
-- kilometre from the `vehicle` master, while a driver or a guide is a person paid
-- a day rate for a language. Putting them in one row would make both wrong.
--
-- Bata is the daily allowance paid to the person — the same word, and the same
-- kind of figure, as `vehicle`.`bata` already holds for a vehicle's own driver.
--
-- The bata below starts empty. Nobody but the business knows what a Japanese
-- speaking driver is paid, and a guessed figure in a quotation is worse than a
-- blank one, so the panel shows the role and says the bata is still needed.
-- schema 23: a role is a category (Driver, Chauffeur guide, National guide …) in a
-- language, and the bata belongs to that pair. The quotation screen picks the type and
-- the language from this list; the bata is maintained here (Master Files → Crew Bata,
-- Admin) and only here.
CREATE TABLE IF NOT EXISTS `tf_crew_role` (
  `code`       VARCHAR(20) NOT NULL,
  `name`       VARCHAR(80) NOT NULL,
  `kind`       ENUM('driver','guide') NOT NULL,
  `category`   VARCHAR(60) NULL,
  `language`   VARCHAR(40) NULL,
  `bata`       DECIMAL(10,2) NULL,
  `currency`   CHAR(3) NOT NULL DEFAULT 'LKR',
  `note`       VARCHAR(255) NULL,
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (the category is filled in by the installer from the kind — "Driver" / "Guide" — so
-- this seed runs unchanged on an install whose table predates the column)
INSERT IGNORE INTO `tf_crew_role` (`code`, `name`, `kind`, `language`, `bata`, `sort_order`) VALUES
  ('ESD',      'English speaking driver',  'driver', 'English',  NULL, 10),
  ('JSD',      'Japanese speaking driver', 'driver', 'Japanese', NULL, 20),
  ('GUIDE_EN', 'English guide',            'guide',  'English',  NULL, 30),
  ('GUIDE_JP', 'Japanese guide',           'guide',  'Japanese', NULL, 40),
  ('GUIDE_XX', 'Guide, another language',  'guide',  NULL,       NULL, 50);

-- The vehicle or vehicles this inquiry needs. Usually one row; a party larger than
-- the biggest vehicle held gets more than one, which is why qty exists.
CREATE TABLE IF NOT EXISTS `tf_intake_vehicle` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`  INT UNSIGNED NOT NULL,
  `vehicle_id` INT NULL,
  `class_code` VARCHAR(20) NULL,
  `type`       VARCHAR(120) NOT NULL,
  `seats`      SMALLINT UNSIGNED NULL,
  `qty`        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `km_rate`    DECIMAL(10,2) NULL,
  `bata`       DECIMAL(10,2) NULL,
  `currency`   CHAR(3) NOT NULL DEFAULT 'LKR',
  `chosen`     ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  `why`        VARCHAR(255) NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_veh_intake` (`intake_id`, `sort_order`),
  CONSTRAINT `fk_tf_veh_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who travels with them. One row per role taken on.
CREATE TABLE IF NOT EXISTS `tf_intake_crew` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`  INT UNSIGNED NOT NULL,
  `role_code`  VARCHAR(20) NOT NULL,
  `role_name`  VARCHAR(80) NOT NULL,
  `kind`       ENUM('driver','guide') NOT NULL,
  `language`   VARCHAR(40) NULL,
  `days`       SMALLINT UNSIGNED NULL,
  -- schema 32: the days are worked out from the programme (every day the party is served,
  -- arrival and departure included when there is a service) unless a person typed them
  `days_by`    ENUM('auto','human') NULL,
  `bata`       DECIMAL(10,2) NULL,
  `currency`   CHAR(3) NOT NULL DEFAULT 'LKR',
  `amount`     DECIMAL(12,2) NULL,
  `chosen`     ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  -- Who, once somebody is actually engaged. Crew are freelancers, carried in the
  -- supplier ledger under 6-9-11 exactly as driver_allocation already allocates them,
  -- so naming one here is what lets the payment side pay them later. Empty while
  -- quoting: you quote a Japanese guide long before you know which one is free.
  `supplier_acc` VARCHAR(50) NULL,
  `person_name`  VARCHAR(190) NULL,
  `person_bata`  DECIMAL(10,2) NULL,
  `why`        VARCHAR(255) NULL,
  `note`       VARCHAR(255) NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_crew` (`intake_id`, `role_code`),
  KEY `ix_tf_crew_sup` (`supplier_acc`),
  CONSTRAINT `fk_tf_crew_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The ladder the company actually uses to decide what a party travels in.
--
-- Note that max_pax is NOT the seat count. A party of twenty goes in a 33 seater,
-- thirteen in a twenty seater: the spare seats are legroom, luggage and the guide.
-- Seating a group to capacity is what a bus company does, not what a tour operator
-- does, so the ladder is held as the operator's own numbers and is editable here
-- rather than being inferred from the seats in the vehicle master.
CREATE TABLE IF NOT EXISTS `tf_vehicle_class` (
  `code`        VARCHAR(20) NOT NULL,
  `name`        VARCHAR(80) NOT NULL,
  `max_pax`     SMALLINT UNSIGNED NOT NULL,
  `seats`       SMALLINT UNSIGNED NULL,
  `match_words` VARCHAR(160) NULL,
  `active`      TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`  SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`),
  KEY `ix_tf_vclass_pax` (`max_pax`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_vehicle_class` (`code`, `name`, `max_pax`, `seats`, `match_words`, `sort_order`) VALUES
  ('SEDAN',     'Sedan car',        2,  4,  'sedan,car',                  10),
  ('VAN',       'Van',              3,  7,  'van',                        20),
  ('HIROOF',    'High roof van',    7,  10, 'high roof,hiroof,hi roof',   30),
  ('MINICOACH', 'Mini coach',       13, 20, 'mini coach,minicoach,coaster,20', 40),
  ('COACH33',   'Coach, 33 seater', 20, 33, 'coach,33',                   50),
  ('BUS45',     'Bus, 45 seater',   30, 45, 'bus,45',                     60),
  ('BUS55',     'Bus, 55 seater',   40, 55, '55',                         70);

-- Schema 7 -------------------------------------------------------------------
-- Where the driver and the guide sleep, and who pays for it.
--
-- Never assumed. Many hotels give a complimentary driver or guide room and many
-- do not, and the difference is worth real money over a ten night tour, so this
-- starts at "not required" for both and is only ever costed when somebody says
-- it is needed. A blank here means nobody has decided yet — it does not mean free.
--
-- Driver and guide are separate rows because the answer genuinely differs: often
-- the hotel houses the guide and not the driver, or the driver sleeps in quarters
-- while the guide takes a room.
--
-- day_no is 0 for "the whole tour", which is how it is used today. The column
-- exists so that night by night can be added later without moving the data: a
-- tour that needs a room on three nights and not the other seven becomes rows
-- with day_no 1..n, and nothing above has to change shape.
CREATE TABLE IF NOT EXISTS `tf_intake_accom` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`    INT UNSIGNED NOT NULL,
  `kind`         ENUM('driver','guide') NOT NULL,
  `day_no`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `required`     TINYINT(1) NOT NULL DEFAULT 0,
  `nights`       SMALLINT UNSIGNED NULL,
  `nights_by`    ENUM('counted','override') NOT NULL DEFAULT 'counted',
  `rate`         DECIMAL(10,2) NULL,
  `currency`     CHAR(3) NOT NULL DEFAULT 'USD',
  `rate_by`      ENUM('hotel_policy','company_rate','manual','none') NOT NULL DEFAULT 'none',
  `amount`       DECIMAL(12,2) NULL,
  `complimentary_nights` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `unknown_nights`       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `detail_json`  MEDIUMTEXT NULL,
  `why`          VARCHAR(400) NULL,
  `note`         VARCHAR(255) NULL,
  `updated_at`   DATETIME NULL,
  `updated_by`   VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_accom` (`intake_id`, `kind`, `day_no`),
  CONSTRAINT `fk_tf_accom_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 8 -------------------------------------------------------------------
-- The driving. One row per leg per day, because a vehicle is paid by the kilometre
-- and a tour that is not broken into legs cannot be costed at all.
--
-- The kilometres are not invented and not measured off a map. The company has driven
-- these roads 17,565 times across 1,729 tours and written the distance down each time,
-- so a leg takes the MEDIAN of what was actually driven between those two towns.
--
-- Two decisions in here were settled by looking at that record rather than by reasoning:
--
--   * estimate_km, not km. tour_sub_locations carries both — km is what the operator
--     typed, estimate_km is the measured road distance — and they disagree on 9,139 of
--     18,056 legs. estimate_km is the tight one: Colombo to the airport is 32.4-34.0 km
--     across 1,441 journeys, where km wanders from 30 to 220. The rest of the system
--     costs on estimate_km + extra_km too, so this matches the quotation editor.
--
--   * extra_km is a real number, not noise. The office adds a local-running allowance to
--     each leg, and it lands on the destination town: 40 into Kandy and Colombo, 25 into
--     Negombo, 15 into Sigiriya, 45 into Anuradhapura — the extra_kilometers master, leg
--     by leg. So it is read from the same history and kept in its own column, never
--     folded into the distance, because the two answer different questions.
--
-- km_by and extra_by say where each figure came from; samples says how many past
-- journeys stand behind it, so a leg backed by two trips is not mistaken for one backed
-- by four hundred. samples_dropped counts journeys thrown out as impossible — the record
-- holds a Yala-Galle leg logged at 7,220 km, and one of those in an average is a ruined
-- quotation.
CREATE TABLE IF NOT EXISTS `tf_intake_sector` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`  INT UNSIGNED NOT NULL,
  `day_no`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `seq`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `from_place` VARCHAR(190) NULL,
  `to_place`   VARCHAR(190) NULL,
  `km`         DECIMAL(10,2) NULL,
  `km_by`      ENUM('history','town_history','city_allowance','manual','google','actual','none','same_place') NOT NULL DEFAULT 'none',
  `extra_km`   DECIMAL(10,2) NULL,
  `extra_by`   ENUM('history','town_history','city_allowance','manual','none') NOT NULL DEFAULT 'none',
  `samples`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `samples_dropped` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `samples_post` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `km_low`     DECIMAL(10,2) NULL,
  `km_high`    DECIMAL(10,2) NULL,
  -- schema 17: the two figures a driven leg carries; `km` above is the one quoted
  `google_km`  DECIMAL(10,2) NULL,
  `google_by`  ENUM('google','not_found','no_key','error','none') NOT NULL DEFAULT 'none',
  `google_why` VARCHAR(255) NULL,
  `actual_km`  DECIMAL(10,2) NULL,
  `actual_by`  ENUM('history','town_history','manual','none') NOT NULL DEFAULT 'none',
  `actual_why` VARCHAR(400) NULL,
  `kind`       ENUM('transfer','local','arrival','departure','positioning','idle','garage') NOT NULL DEFAULT 'transfer',
  `origin`     ENUM('auto','human') NOT NULL DEFAULT 'auto',
  `why`        VARCHAR(400) NULL,
  `note`       VARCHAR(255) NULL,
  -- schema 18: a leg somebody took off stays off (the row is the marker); who and when
  `removed_at` DATETIME NULL,
  `removed_by` VARCHAR(100) NULL,
  -- schema 19: which leg of the plan this row is, so that changing a leg's kilometres
  -- leaves it the plan's leg. Empty on a leg a person added themselves.
  `plan_key`   VARCHAR(400) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_sector_intake` (`intake_id`, `day_no`, `seq`),
  CONSTRAINT `fk_tf_sector_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  -- Where a tour starts and ends when the agent has not said otherwise. Katunayake is
  -- the town the airport sits in, and it is how the leg history names it on 2,907 legs
  -- — under half a dozen spellings of the terminal itself, but one town every time.
  ('gateway', 'Katunayake'),
  ('gateway_out', 'Katunayake'),
  -- Which figure the quotation is costed on for a driven leg: Google's road distance
  -- ('google') or the company's own driven record ('actual'). Schema 17.
  ('mileage_quote_source', 'google'),
  -- Where the vehicle sets off from to collect the party. Not a guess: 1,194 of 1,729
  -- tours on file begin with a run TO the airport, and 2,145 of those runs start in
  -- Colombo. The return leg is NOT added, because the record does not show one — only
  -- 138 tours end with a leg leaving the airport.
  ('vehicle_base', 'Colombo'),
  ('position_on_arrival', '1'),
  -- A leg with fewer past journeys than this behind it is still offered, but flagged.
  ('km_min_samples', '3'),
  -- Longer than this and somebody has mistyped: the island is about 440 km end to end.
  -- Such journeys are left out of the median and counted, never silently averaged in.
  ('km_sane_max', '450');

-- Schema 9 -------------------------------------------------------------------
-- What a kilometre costs, by vehicle size.
--
-- The vehicle master is a register of the vehicles the company actually holds —
-- registration, owner, licence, insurance — and its rate column is per vehicle, with
-- no memory of what it used to be. Four of the seven sizes the inquiry panel chooses
-- between have no vehicle in it at all, so they have no rate anywhere.
--
-- This table holds the price and nothing else: one current rate per SIZE, typed by a
-- person and by nobody else — nothing here updates on its own. A rate is never
-- overwritten. Setting a new one closes the old period (valid_to) and opens a new one,
-- so the trail of what was charged when is always there to read.
--
-- A quotation does not look this table up when it is printed. The inquiry copies the
-- rate onto itself the moment the vehicle is settled (see tf_intake_vehicle.rate_id
-- and master_rate below), so a change here never moves a figure already sent out.
CREATE TABLE IF NOT EXISTS `tf_transport_rate` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_code`     VARCHAR(20) NOT NULL,
  `rate_km`        DECIMAL(10,2) NOT NULL,
  `currency`       CHAR(3) NOT NULL DEFAULT 'LKR',
  `valid_from`     DATE NOT NULL,
  `valid_to`       DATE NULL,
  `reason`         VARCHAR(200) NULL,
  `set_by`         VARCHAR(100) NULL,
  `set_at`         DATETIME NULL,
  -- a typo put right in place, kept apart from a price change so the history never
  -- shows a rate that was never actually charged
  `corrected_from` DECIMAL(10,2) NULL,
  `corrected_by`   VARCHAR(100) NULL,
  `corrected_at`   DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_trate_class` (`class_code`, `valid_from`),
  CONSTRAINT `fk_tf_trate_class` FOREIGN KEY (`class_code`) REFERENCES `tf_vehicle_class` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Google's answers, kept. One row per pair of places as asked (lower-cased in the keys),
-- so a pair is asked for once and every later plan reads it here. A place Google could
-- not find is kept too (status not_found, the reason in `note`); a refused key or a line
-- fault is never kept. Emptied by nobody; a wrong answer is asked again from the pane.
CREATE TABLE IF NOT EXISTS `tf_google_distance` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_key`   VARCHAR(190) NOT NULL,
  `to_key`     VARCHAR(190) NOT NULL,
  `from_text`  VARCHAR(190) NOT NULL,
  `to_text`    VARCHAR(190) NOT NULL,
  `km`         DECIMAL(10,2) NULL,
  `minutes`    INT UNSIGNED NULL,
  `status`     ENUM('ok','not_found') NOT NULL DEFAULT 'ok',
  `from_addr`  VARCHAR(255) NULL,
  `to_addr`    VARCHAR(255) NULL,
  `note`       VARCHAR(255) NULL,
  `fetched_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_gdist_pair` (`from_key`, `to_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Master Files → Mileage Allowances.
--
-- Kilometres that are charged without being driven between two places: a day the
-- party stays at the hotel with no sightseeing, and the driver's run out from the
-- garage at the start of a tour and back at the end. The city-tour allowances —
-- Colombo, Kandy — sit on the same screen but stay in the extra_kilometers master
-- the office already keeps, so there is one list of cities and not two.
--
-- Every figure here is typed by a person and changed only by a person. The seeds are
-- the company's own current practice: 80 km for a stay day is what management set,
-- and 30 km garage mileage is the flat figure the driver log report has always used.
CREATE TABLE IF NOT EXISTS `tf_mileage_allowance` (
  `code`       VARCHAR(30) NOT NULL,
  `name`       VARCHAR(80) NOT NULL,
  `km`         DECIMAL(10,2) NOT NULL DEFAULT 0,
  `applies`    ENUM('stay_day','tour_start','tour_end','both_ends') NOT NULL,
  `note`       VARCHAR(255) NULL,
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by` VARCHAR(100) NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_mileage_allowance` (`code`, `name`, `km`, `applies`, `note`) VALUES
  ('STAY_DAY', 'Stay day at the hotel, no sightseeing', 80, 'stay_day',
   'Charged for each day with no transfer and no visits. The vehicle and driver are still on the tour.'),
  ('GARAGE',   'Garage mileage', 30, 'both_ends',
   'The driver''s run from the garage to the first pickup, and back from the last drop. Applied once at each end of the tour.');

-- Schema 10 ------------------------------------------------------------------
-- Three desks, one tour.
--
-- Up to here an inquiry belongs to the people who quote it. The moment the agent
-- says yes, Confirm writes ONE tour record and fans it out: one card per hotel stay
-- for the Hotel Desk, one card per vehicle for the Transport Desk. Each desk works
-- its own cards and sees nothing else; Management reads every lane on the Tour
-- Board. The inquiry itself becomes read-only — the quotation is the record of what
-- was sold, and operations never edit it. The cards carry their own copy of what
-- they need (the stays, the legs, the crew), so the Hotel Desk can move a check-in
-- or the Transport Desk can re-order a day without touching the quotation.
--
-- Nothing here is guessed. A night with no hotel named still becomes a card, marked
-- needs_hotel; a hotel with no supplier record keeps the name the agent wrote; a
-- meal plan not stated stays blank and flagged.
--
-- Access is the system's own: three groups in group_profile, three menu entries in
-- menu_list, grants in user_privileges — created once by the installer and edited
-- afterwards only in Settings → Group Privileges. Nothing about that lives here.

-- The tour: one row per confirmed inquiry, for the whole of its life. tour_no is the
-- inquiry number carried through (management's decision), so a card, a voucher and
-- the original email all quote the same number. legacy_tour_id stays empty until a
-- bridge to the old tour tables is built; nothing depends on it.
CREATE TABLE IF NOT EXISTS `tf_tour` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_no`          VARCHAR(20)  NOT NULL,
  `intake_id`        INT UNSIGNED NOT NULL,
  `stage`            ENUM('confirmed','operating','completed','closed','cancelled') NOT NULL DEFAULT 'confirmed',
  `amending`         TINYINT(1)   NOT NULL DEFAULT 0,
  `agent_acc`        VARCHAR(50)  NULL,
  `agent_name`       VARCHAR(190) NULL,
  `tour_name`        VARCHAR(190) NULL,
  `market_code`      VARCHAR(10)  NULL,
  `country_iso`      CHAR(2)      NULL,
  `arrival_date`     DATE NULL,
  `departure_date`   DATE NULL,
  `nights`           SMALLINT UNSIGNED NULL,
  `pax_total`        SMALLINT UNSIGNED NULL,
  `pax_adults`       SMALLINT UNSIGNED NULL,
  `pax_children`     SMALLINT UNSIGNED NULL,
  `rooms_note`       VARCHAR(255) NULL,
  `hotel_class`      VARCHAR(60)  NULL,
  `arrival_flight`   VARCHAR(160) NULL,
  `departure_flight` VARCHAR(160) NULL,
  `confirmed_at`     DATETIME NOT NULL,
  `confirmed_by`     VARCHAR(100) NULL,
  `cancelled_at`     DATETIME NULL,
  `cancelled_by`     VARCHAR(100) NULL,
  `cancel_why`       VARCHAR(400) NULL,
  `legacy_tour_id`   VARCHAR(50)  NULL,
  `note`             TEXT NULL,
  -- schema 20: the quotation version the agent accepted, and the desk's "hotels & activities confirmed" mark
  `quote_id`         INT UNSIGNED NULL,
  `quote_version`    SMALLINT UNSIGNED NULL,
  `reservations_confirmed_at` DATETIME NULL,
  `reservations_confirmed_by` VARCHAR(100) NULL,
  -- schema 21: the Transport Desk's "work complete" mark (every vehicle, driver and guide allocated)
  `transport_complete_at` DATETIME NULL,
  `transport_complete_by` VARCHAR(100) NULL,
  -- schema 22: deleted by Admin — cancelled, archived with its inquiry, out of every list and count, restorable
  `archived_at`      DATETIME NULL,
  `archived_by`      VARCHAR(100) NULL,
  `archive_note`     VARCHAR(400) NULL,
  `created_at`       DATETIME NOT NULL,
  `updated_at`       DATETIME NOT NULL,
  `updated_by`       VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_tour_no` (`tour_no`),
  UNIQUE KEY `uq_tf_tour_intake` (`intake_id`),
  KEY `ix_tf_tour_stage` (`stage`, `arrival_date`),
  CONSTRAINT `fk_tf_tour_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One hotel stay: consecutive nights at the same hotel on one tour. The Hotel Desk's
-- card. day_from/day_to say which nights of the inquiry it came from; the flags say
-- what the Quotation Desk still owes it before it can be requested.
CREATE TABLE IF NOT EXISTS `tf_hotel_job` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`          INT UNSIGNED NOT NULL,
  `seq`              SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `day_from`         SMALLINT UNSIGNED NULL,
  `day_to`           SMALLINT UNSIGNED NULL,
  `hotel_acc`        VARCHAR(50)  NULL,
  `hotel_id`         INT UNSIGNED NULL,
  `hotel_name`       VARCHAR(190) NULL,
  `area`             VARCHAR(160) NULL,
  `check_in`         DATE NULL,
  `check_out`        DATE NULL,
  `nights`           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `rooms`            SMALLINT UNSIGNED NULL,
  `rooms_note`       VARCHAR(255) NULL,
  `meal_plan`        VARCHAR(4)   NULL,
  `pax`              SMALLINT UNSIGNED NULL,
  `special_note`     VARCHAR(1000) NULL,
  `crew_rooms`       VARCHAR(255) NULL,
  `needs_hotel`      TINYINT(1) NOT NULL DEFAULT 0,
  `needs_supplier`   TINYINT(1) NOT NULL DEFAULT 0,
  `needs_meal`       TINYINT(1) NOT NULL DEFAULT 0,
  `status`           ENUM('to_book','requested','confirmed','voucher_issued','problem','cancelled') NOT NULL DEFAULT 'to_book',
  `requested_at`     DATETIME NULL,
  `requested_by`     VARCHAR(100) NULL,
  `request_note`     VARCHAR(400) NULL,
  `confirmation_ref` VARCHAR(100) NULL,
  `confirmed_with`   VARCHAR(160) NULL,
  `confirmed_at`     DATETIME NULL,
  `confirmed_by`     VARCHAR(100) NULL,
  `voucher_no`       VARCHAR(40)  NULL,
  `voucher_at`       DATETIME NULL,
  `voucher_by`       VARCHAR(100) NULL,
  `problem_code`     VARCHAR(30)  NULL,
  `problem_note`     VARCHAR(400) NULL,
  `problem_at`       DATETIME NULL,
  `problem_by`       VARCHAR(100) NULL,
  `amended`          TINYINT(1) NOT NULL DEFAULT 0,
  `note`             VARCHAR(500) NULL,
  `updated_at`       DATETIME NULL,
  `updated_by`       VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_hjob_tour` (`tour_id`, `seq`),
  KEY `ix_tf_hjob_status` (`status`, `check_in`),
  CONSTRAINT `fk_tf_hjob_tour` FOREIGN KEY (`tour_id`) REFERENCES `tf_tour` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One vehicle on one tour: the Transport Desk's card. The kilometres and the rate are
-- copied from the quotation as it was confirmed; the rate is money and is shown on the
-- Tour Board only. The vehicle is the one allocated from the vehicle master; who drives
-- it and who guides are rows in tf_transport_crew.
CREATE TABLE IF NOT EXISTS `tf_transport_job` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`           INT UNSIGNED NOT NULL,
  `seq`               SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `intake_vehicle_id` INT UNSIGNED NULL,
  `class_code`        VARCHAR(20)  NULL,
  `vehicle_type`      VARCHAR(120) NOT NULL,
  `seats`             SMALLINT UNSIGNED NULL,
  `qty`               SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `days`              SMALLINT UNSIGNED NULL,
  `km_quoted`         DECIMAL(10,2) NULL,
  `extra_km_quoted`   DECIMAL(10,2) NULL,
  `km_total`          DECIMAL(10,2) NULL,
  `extra_km_total`    DECIMAL(10,2) NULL,
  `actual_km_total` DECIMAL(10,2) NULL,
  `km_rate`           DECIMAL(10,2) NULL,
  `rate_id`           INT UNSIGNED NULL,
  `currency`          CHAR(3) NOT NULL DEFAULT 'LKR',
  `vehicle_id`        INT NULL,
  `vehicle_reg`       VARCHAR(60)  NULL,
  `vehicle_note`      VARCHAR(255) NULL,
  `status`            ENUM('to_allocate','allocated','scheduled','pack_issued','running','returned','settled','cancelled') NOT NULL DEFAULT 'to_allocate',
  `allocated_at`      DATETIME NULL,
  `allocated_by`      VARCHAR(100) NULL,
  `scheduled_at`      DATETIME NULL,
  `scheduled_by`      VARCHAR(100) NULL,
  `pack_at`           DATETIME NULL,
  `pack_by`           VARCHAR(100) NULL,
  `running_at`        DATETIME NULL,
  `returned_at`       DATETIME NULL,
  `returned_by`       VARCHAR(100) NULL,
  `settled_at`        DATETIME NULL,
  `settled_by`        VARCHAR(100) NULL,
  `amended`           TINYINT(1) NOT NULL DEFAULT 0,
  `note`              VARCHAR(500) NULL,
  `updated_at`        DATETIME NULL,
  `updated_by`        VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_tjob_tour` (`tour_id`, `seq`),
  KEY `ix_tf_tjob_status` (`status`),
  CONSTRAINT `fk_tf_tjob_tour` FOREIGN KEY (`tour_id`) REFERENCES `tf_tour` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The schedule: the legs as costed, copied so operations can change them without
-- the quotation moving. origin says whether a leg is as costed, changed since, or
-- added by the desk. night_hotel is the hotel the party sleeps at after that day,
-- by name and town only — the driver needs to know where to go, not what it costs.
CREATE TABLE IF NOT EXISTS `tf_transport_leg` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`      INT UNSIGNED NOT NULL,
  `day_no`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `day_date`    DATE NULL,
  `seq`         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `from_place`  VARCHAR(190) NULL,
  `to_place`    VARCHAR(190) NULL,
  `km`          DECIMAL(10,2) NULL,
  `extra_km`    DECIMAL(10,2) NULL,
  -- schema 18: Google's figure beside the costed one, and the kilometres actually driven,
  -- entered after the tour — what every later quotation's driven record learns from
  `google_km`   DECIMAL(10,2) NULL,
  `quoted_by`   VARCHAR(16) NULL,
  `actual_km`   DECIMAL(10,2) NULL,
  `actual_by`   VARCHAR(100) NULL,
  `actual_at`   DATETIME NULL,
  `actual_note` VARCHAR(255) NULL,
  `kind`        ENUM('transfer','local','arrival','departure','positioning','idle','garage') NOT NULL DEFAULT 'transfer',
  `night_hotel` VARCHAR(190) NULL,
  `night_area`  VARCHAR(160) NULL,
  `origin`      ENUM('costed','changed','added') NOT NULL DEFAULT 'costed',
  `note`        VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_tleg_job` (`job_id`, `day_no`, `seq`),
  CONSTRAINT `fk_tf_tleg_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who travels with the party, and their allowances. Copied from the quotation's crew
-- lines; the desk names the person (a freelancer in the supplier ledger under 6-9-11,
-- as driver_allocation has always done) and confirms the bata.
--
-- schema 24: one row per PERSON, with the days they work (`date_from`, `date_to`). A
-- driver or guide replaced mid-tour keeps their row (status 'replaced', ended on their
-- last day, `replaced_by` the new row) and the replacement gets a row of their own on
-- the same card from their first day (`replaces` the old one). Both stay on the tour;
-- each is paid for the days actually worked (tf_crew_voucher). The vehicle is the
-- card's, never a crew row's, so a change of driver never touches it.
CREATE TABLE IF NOT EXISTS `tf_transport_crew` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`       INT UNSIGNED NOT NULL,
  `role_code`    VARCHAR(20)  NOT NULL,
  `role_name`    VARCHAR(80)  NOT NULL,
  `kind`         ENUM('driver','guide') NOT NULL,
  `language`     VARCHAR(40)  NULL,
  `days`         SMALLINT UNSIGNED NULL,
  `bata`         DECIMAL(10,2) NULL,
  `currency`     CHAR(3) NOT NULL DEFAULT 'LKR',
  `amount`       DECIMAL(12,2) NULL,
  `supplier_acc` VARCHAR(50)  NULL,
  `person_name`  VARCHAR(190) NULL,
  `person_bata`  DECIMAL(10,2) NULL,
  `status`       ENUM('to_engage','engaged','declined','replaced') NOT NULL DEFAULT 'to_engage',
  `note`         VARCHAR(255) NULL,
  `date_from`     DATE NULL,
  `date_to`       DATE NULL,
  `replaces`      INT UNSIGNED NULL,
  `replaced_by`   INT UNSIGNED NULL,
  `replaced_at`   DATETIME NULL,
  `replaced_note` VARCHAR(400) NULL,
  `updated_at`   DATETIME NULL,
  `updated_by`   VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_tcrew_job` (`job_id`),
  CONSTRAINT `fk_tf_tcrew_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- schema 24: a payment voucher per person for the days actually worked — the Transport
-- Desk's instruction to the payment side. Numbered from its own series (tf_meta
-- crew_voucher_prefix / crew_voucher_next), days × the person's own agreed bata (the
-- master's when they have none), and kept for good: a wrong one is cancelled with a
-- note and a fresh one issued, never edited or removed. Goes with the card.
CREATE TABLE IF NOT EXISTS `tf_crew_voucher` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`      INT UNSIGNED NOT NULL,
  `job_id`       INT UNSIGNED NOT NULL,
  `crew_id`      INT UNSIGNED NOT NULL,
  `voucher_no`   VARCHAR(30)  NOT NULL,
  `kind`         ENUM('driver','guide') NOT NULL,
  `role_code`    VARCHAR(20)  NOT NULL,
  `role_name`    VARCHAR(80)  NOT NULL,
  `supplier_acc` VARCHAR(50)  NULL,
  `person_name`  VARCHAR(190) NOT NULL,
  `date_from`    DATE NOT NULL,
  `date_to`      DATE NOT NULL,
  `days`         SMALLINT UNSIGNED NOT NULL,
  `rate`         DECIMAL(10,2) NOT NULL,
  `rate_source`  ENUM('person','master') NOT NULL DEFAULT 'master',
  `currency`     CHAR(3) NOT NULL DEFAULT 'LKR',
  `amount`       DECIMAL(12,2) NOT NULL,
  `vehicle_reg`  VARCHAR(60)  NULL,
  `state`        ENUM('issued','cancelled') NOT NULL DEFAULT 'issued',
  `note`         VARCHAR(400) NULL,
  `issued_at`    DATETIME NOT NULL,
  `issued_by`    VARCHAR(100) NULL,
  `cancelled_at` DATETIME NULL,
  `cancelled_by` VARCHAR(100) NULL,
  `cancel_note`  VARCHAR(400) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_cvoucher_no` (`voucher_no`),
  KEY `ix_tf_cvoucher_job` (`job_id`),
  KEY `ix_tf_cvoucher_tour` (`tour_id`),
  KEY `ix_tf_cvoucher_crew` (`crew_id`),
  CONSTRAINT `fk_tf_cvoucher_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  ('crew_voucher_prefix', 'CPV'),
  ('crew_voucher_next',   '1001');

-- schema 25: sightseeing with nothing to pay, and the priced list's other names. Both are
-- settings (Admin → Settings), never fixed in code: the words that mark a stop as no charge
-- when the priced list has no positive amount for it, and the phrases an agent writes for
-- an entry the priced list spells differently (one per line, "phrase => CODE").
INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  ('service_no_charge_words', 's/s, ss, sightseeing, sight seeing, city tour, market, shopping, photo stop, drive through, free time, leisure, at leisure'),
  ('service_aliases', 'temple of the tooth => KDY ENT\ntemple of tooth => KDY ENT\ntooth relic => KDY ENT\nkandy temple => KDY ENT');

-- schema 26: the driver's advance before the tour, and the settlement after it — on the
-- transport card, against what the card says is payable (the vehicle hire as costed, from
-- the kilometres and the rate copied onto the card, plus the driver's bata for the days).
-- An advance is a percentage of that payable, worked out by the card and adjustable by a
-- holder of the Driver advance privilege (tour/transport_ops/driver_advance), who also
-- approves it and records the payment: who paid, on what day, how, under what reference.
-- Numbered from its own series; never edited once paid — cancelled with a note before
-- that, corrected by an adjustment on the settlement after. The settlement is the card's
-- last step (Settled): total payable, less the advances paid, plus or minus the
-- adjustments a holder of the privilege lists, equals the balance — frozen with its own
-- number the moment the card is settled, and printable as the Settlement in the driver's
-- pack list. Every move is a line on the card's trail and on the history.
CREATE TABLE IF NOT EXISTS `tf_driver_advance` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`       INT UNSIGNED NOT NULL,
  `job_id`        INT UNSIGNED NOT NULL,
  `crew_id`       INT UNSIGNED NULL,
  `advance_no`    VARCHAR(30)  NOT NULL,
  `person_name`   VARCHAR(190) NULL,
  `supplier_acc`  VARCHAR(50)  NULL,
  `currency`      CHAR(3) NOT NULL DEFAULT 'LKR',
  `base_hire`     DECIMAL(12,2) NULL,
  `base_bata`     DECIMAL(12,2) NULL,
  `base_total`    DECIMAL(12,2) NOT NULL,
  `base_note`     VARCHAR(255) NULL,
  `pct`           DECIMAL(6,2) NULL,
  `amount_calc`   DECIMAL(12,2) NOT NULL,
  `amount`        DECIMAL(12,2) NOT NULL,
  `adjust_note`   VARCHAR(255) NULL,
  `adjusted_by`   VARCHAR(100) NULL,
  `adjusted_at`   DATETIME NULL,
  `status`        ENUM('requested','approved','paid','cancelled') NOT NULL DEFAULT 'requested',
  `requested_at`  DATETIME NOT NULL,
  `requested_by`  VARCHAR(100) NULL,
  `request_note`  VARCHAR(255) NULL,
  `approved_at`   DATETIME NULL,
  `approved_by`   VARCHAR(100) NULL,
  `approve_note`  VARCHAR(255) NULL,
  `paid_at`       DATETIME NULL,
  `paid_by`       VARCHAR(100) NULL,
  `paid_on`       DATE NULL,
  `method`        VARCHAR(30)  NULL,
  `reference`     VARCHAR(100) NULL,
  `remarks`       VARCHAR(255) NULL,
  `cancelled_at`  DATETIME NULL,
  `cancelled_by`  VARCHAR(100) NULL,
  `cancel_note`   VARCHAR(255) NULL,
  `updated_at`    DATETIME NULL,
  `updated_by`    VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_dadv_no` (`advance_no`),
  KEY `ix_tf_dadv_job` (`job_id`),
  KEY `ix_tf_dadv_tour` (`tour_id`),
  CONSTRAINT `fk_tf_dadv_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_driver_adjustment` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`     INT UNSIGNED NOT NULL,
  `label`      VARCHAR(120) NOT NULL,
  `amount`     DECIMAL(12,2) NOT NULL,
  `note`       VARCHAR(255) NULL,
  `added_at`   DATETIME NOT NULL,
  `added_by`   VARCHAR(100) NULL,
  `removed_at` DATETIME NULL,
  `removed_by` VARCHAR(100) NULL,
  `remove_note` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_dadj_job` (`job_id`),
  CONSTRAINT `fk_tf_dadj_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_driver_settlement` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`        INT UNSIGNED NOT NULL,
  `job_id`         INT UNSIGNED NOT NULL,
  `settlement_no`  VARCHAR(30) NOT NULL,
  `currency`       CHAR(3) NOT NULL DEFAULT 'LKR',
  `hire`           DECIMAL(12,2) NULL,
  `hire_basis`     VARCHAR(20) NULL,
  `hire_km`        DECIMAL(10,1) NULL,
  `bata`           DECIMAL(12,2) NULL,
  `payable`        DECIMAL(12,2) NOT NULL,
  `advances`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `adjustments`    DECIMAL(12,2) NOT NULL DEFAULT 0,
  `balance`        DECIMAL(12,2) NOT NULL,
  `detail_json`    MEDIUMTEXT NULL,
  `note`           VARCHAR(400) NULL,
  `settled_at`     DATETIME NOT NULL,
  `settled_by`     VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_dset_no` (`settlement_no`),
  UNIQUE KEY `uq_tf_dset_job` (`job_id`),
  CONSTRAINT `fk_tf_dset_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  ('driver_advance_prefix',    'DAV'),
  ('driver_advance_next',      '1001'),
  ('driver_settlement_prefix', 'DST'),
  ('driver_settlement_next',   '1001'),
  ('driver_advance_pcts',      '20, 30, 40, 50');

-- schema 27: the tour photo library — one place for the pictures the client quotation shows,
-- three to five good photos per destination or attraction, kept by Admin under Master Files →
-- Tour Photos. A tag is a destination, attraction or activity (Sigiriya, Kandy, Tea Country,
-- Safari, Beaches …) with the words a day's programme uses for it; a photo carries one or more
-- tags, a caption, and may be marked preferred so the best pictures come first. When the client
-- quotation is prepared each day's programme is read for those words and a photo is picked for
-- it — preferred first, the least used next, never the same picture twice on one itinerary —
-- and the pick is kept on the inquiry (tf_intake_day_photo) so staff can change it by hand.
-- A presentation layer over the tour data: nothing here is a cost, a rate or a price.
CREATE TABLE IF NOT EXISTS `tf_photo_tag` (
  `code`       VARCHAR(40)  NOT NULL,
  `name`       VARCHAR(120) NOT NULL,
  `kind`       ENUM('destination','attraction','activity','general') NOT NULL DEFAULT 'destination',
  `keywords`   VARCHAR(1000) NULL,
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  `updated_at` DATETIME NULL,
  `updated_by` VARCHAR(100) NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_photo_tag` (`code`, `name`, `kind`, `keywords`, `sort_order`) VALUES
  ('SIGIRIYA',     'Sigiriya',            'attraction',  'sigiriya, lion rock, sigiriya rock, pidurangala, habarana, kandalama, giritale', 10),
  ('DAMBULLA',     'Dambulla',            'attraction',  'dambulla, cave temple, golden temple, dambulla temple', 20),
  ('POLONNARUWA',  'Polonnaruwa',         'attraction',  'polonnaruwa, gal vihara, polonnaruwa ss', 30),
  ('ANURADHAPURA', 'Anuradhapura',        'attraction',  'anuradhapura, mihintale, sri maha bodhi, ruwanwelisaya', 40),
  ('KANDY',        'Kandy',               'destination', 'kandy, temple of the tooth, tooth relic, dalada maligawa, peradeniya, kandyan dance, kandy s/s', 50),
  ('PINNAWALA',    'Pinnawala',           'attraction',  'pinnawala, elephant orphanage, elephant foundation', 60),
  ('TEA',          'Tea Country',         'destination', 'tea country, nuwara eliya, ella, haputale, hatton, tea plantation, tea factory, tea estate, nine arch, horton plains, little england', 70),
  ('TRAIN',        'Hill country train',  'activity',    'train, train ride, ella train, kandy to ella, scenic train', 80),
  ('SAFARI',       'Safari',              'activity',    'safari, yala, udawalawe, minneriya, kaudulla, wilpattu, jeep, national park, elephant gathering, leopard', 90),
  ('GALLE',        'Galle',               'destination', 'galle, galle fort, dutch fort, galle city tour, unawatuna', 100),
  ('BEACH',        'Beaches',             'destination', 'beach, bentota, mirissa, hikkaduwa, negombo beach, tangalle, passikudah, trincomalee, nilaveli, kalpitiya, weligama, arugam bay, ahungalla, kosgoda, wadduwa', 110),
  ('WHALES',       'Whale watching',      'activity',    'whale watching, whales, dolphins', 120),
  ('COLOMBO',      'Colombo',             'destination', 'colombo, pettah, galle face, colombo city tour, colombo ss, independence square', 130),
  ('ARRIVAL',      'Arrival & departure', 'general',     'airport, arrival, departure, katunayake, negombo, transfer to the airport', 140);

CREATE TABLE IF NOT EXISTS `tf_photo` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file`          VARCHAR(190) NOT NULL,
  `thumb`         VARCHAR(190) NULL,
  `original_name` VARCHAR(190) NULL,
  `mime`          VARCHAR(60)  NULL,
  `width`         SMALLINT UNSIGNED NULL,
  `height`        SMALLINT UNSIGNED NULL,
  `bytes`         INT UNSIGNED NULL,
  `caption`       VARCHAR(190) NULL,
  `credit`        VARCHAR(120) NULL,
  `preferred`     TINYINT(1) NOT NULL DEFAULT 0,
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`    SMALLINT NOT NULL DEFAULT 0,
  `uses`          INT UNSIGNED NOT NULL DEFAULT 0,
  `last_used_at`  DATETIME NULL,
  `uploaded_at`   DATETIME NOT NULL,
  `uploaded_by`   VARCHAR(100) NULL,
  `replaced_at`   DATETIME NULL,
  `replaced_by`   VARCHAR(100) NULL,
  `removed_at`    DATETIME NULL,
  `removed_by`    VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_photo_active` (`active`, `preferred`, `uses`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_photo_tag_map` (
  `photo_id` INT UNSIGNED NOT NULL,
  `tag_code` VARCHAR(40)  NOT NULL,
  PRIMARY KEY (`photo_id`, `tag_code`),
  KEY `ix_tf_ptm_tag` (`tag_code`),
  CONSTRAINT `fk_tf_ptm_photo` FOREIGN KEY (`photo_id`) REFERENCES `tf_photo` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_intake_day_photo` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id` INT UNSIGNED NOT NULL,
  `day_no`    SMALLINT NOT NULL,
  `photo_id`  INT UNSIGNED NULL,
  `tag_code`  VARCHAR(40) NULL,
  `chosen_by` ENUM('auto','human') NOT NULL DEFAULT 'auto',
  `chosen_at` DATETIME NULL,
  `by`        VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_dayphoto` (`intake_id`, `day_no`),
  CONSTRAINT `fk_tf_dayphoto_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  ('photo_folder',      'uploads/tour_photos'),
  ('photo_max_width',   '1600'),
  ('photo_thumb_width', '480');

-- schema 28: the itinerary written to Avenir's professional standard — the sentences the
-- client quotation is built from, a paragraph a day (ItineraryWriter). Each is a setting,
-- edited under Admin → Settings; " | " separates alternatives the writer takes in turn so the
-- wording varies; the places, visits, hotels and flights are filled in from the inquiry, in
-- the desk's order, and nothing is invented (an unknown flight is left out, an unknown
-- airline printed as the agent wrote it). The airlines list writes a flight code out in full.
INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  ('itin_airport',           'Bandaranaike International Airport'),
  ('itin_representative',    'our local representative'),
  ('itin_airlines',          'UL = SriLankan Airlines\nEK = Emirates\nQR = Qatar Airways\nEY = Etihad Airways\nSQ = Singapore Airlines\nMH = Malaysia Airlines\nCX = Cathay Pacific\nAI = Air India\n6E = IndiGo\nTK = Turkish Airlines\nBA = British Airways\nLH = Lufthansa\nAF = Air France\nKL = KLM\nSU = Aeroflot\nTG = Thai Airways\nFZ = flydubai\nG9 = Air Arabia\nWY = Oman Air\nGF = Gulf Air\nSV = Saudia\nKU = Kuwait Airways\nJL = Japan Airlines\nNH = ANA\nKE = Korean Air\nOZ = Asiana Airlines\nCZ = China Southern\nCA = Air China\nMU = China Eastern\nUK = Vistara\n8D = FitsAir\nVJ = VietJet Air\nAK = AirAsia\nD7 = AirAsia X\nOD = Batik Air\nJ2 = Azerbaijan Airlines\nPG = Bangkok Airways'),
  ('itin_morning',           'After breakfast'),
  ('itin_morning_no_bf',     'In the morning'),
  ('itin_flight_in',         ' on {airline} flight {number}'),
  ('itin_flight_in_raw',     ' on {flight}'),
  ('itin_flight_out',        ' aboard {airline} {number}'),
  ('itin_flight_out_raw',    ' aboard {flight}'),
  ('itin_arrival',           'Upon arrival at {airport}{flight}, you will be met and assisted by {representative}. Thereafter, transfer to your hotel{in_area}.'),
  ('itin_arrival_visits',    'En route, {visits}.'),
  ('itin_move',              '{morning}, leave for {area}. | {morning}, proceed to {area}.'),
  ('itin_move_before',       '{morning}, {visits}. Thereafter, leave for {area}. | {morning}, {visits}, then proceed to {area}.'),
  ('itin_enroute',           'En route, {visits}. Thereafter, continue to {area}.'),
  ('itin_on_arrival',        'Upon arrival, {visits} before proceeding to the hotel. | Upon arrival in {area}, {visits}. Thereafter, proceed to the hotel.'),
  ('itin_checkin',           'Check in to {hotel} and spend the remainder of the day at leisure. | On arrival, check in to {hotel}; the rest of the day is at leisure.'),
  ('itin_same_area',         '{morning}, transfer to {hotel} and check in. The remainder of the day is at leisure.'),
  ('itin_same_area_visits',  '{morning}, {visits}. Thereafter, transfer to {hotel} and check in.'),
  ('itin_stay',              '{morning}, {visits}. Upon completion of the sightseeing programme, return to the hotel. | {morning}, {visits}. Thereafter, return to the hotel and spend the remainder of the day at leisure.'),
  ('itin_stay_free',         'Day at leisure{in_area}. | Spend the day at leisure{in_area}.'),
  ('itin_departure',         'Transfer to {airport} for your departure flight{to}{flight}.'),
  ('itin_departure_visits',  '{morning}, {visits}. Thereafter, transfer to {airport} for your departure flight{to}{flight}.');

-- schema 29: the two programme masters. Free Visits — the normal sightseeing and en-route
-- stops of each destination, with no cost anywhere on them: a free visit put on a day is on
-- the itinerary and never on the costing. The paid side — Paid Activities & Services — is
-- the priced lists the company already keeps, read and kept by destination on one screen:
-- entrances and tickets (entrance_fee, USD a head) and activities and excursions
-- (tf_excursion_rate, per person / group / vehicle, transport included or not); a paid item
-- put on a day pulls its rate and goes straight to the costing. The costing itself is read
-- in three sections — hotels (a subtotal a hotel), paid activities and services, transport
-- (vehicle, mileage, driver and guide) — that add up to the tour cost.
CREATE TABLE IF NOT EXISTS `tf_free_visit` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `destination` VARCHAR(120) NOT NULL,
  `name`        VARCHAR(190) NOT NULL,
  `kind`        ENUM('sightseeing','stop','walk','photo','other') NOT NULL DEFAULT 'sightseeing',
  `description` VARCHAR(1000) NULL,
  `active`      TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`  SMALLINT NOT NULL DEFAULT 0,
  `source`      ENUM('manual','backfill') NOT NULL DEFAULT 'manual',
  `origin`      VARCHAR(255) NULL,
  `created_at`  DATETIME NULL,
  `created_by`  VARCHAR(100) NULL,
  `updated_at`  DATETIME NULL,
  `updated_by`  VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_free_visit_dest` (`destination`, `active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- schema 29: TourFlow's own word on an entry of the priced list (entrance_fee), kept beside it
-- so the list itself — and the accounts posting each entry carries — is never altered: the
-- destination the entry sits under in Paid Activities & Services (empty or no row: not placed
-- yet — since schema 30 a destination is only ever given by a person, never read from the
-- address) and whether TourFlow still offers and matches it (0: switched off; the lines
-- already priced from it keep it and its figure).
-- The entries themselves, their names, codes and rates are kept under Master Files →
-- Entrance Fee as always.
CREATE TABLE IF NOT EXISTS `tf_fee_meta` (
  `fee_id`      INT NOT NULL,
  `destination` VARCHAR(120) NULL,
  `active`      TINYINT(1) NOT NULL DEFAULT 1,
  `updated_at`  DATETIME NULL,
  `updated_by`  VARCHAR(100) NULL,
  PRIMARY KEY (`fee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- schema 31: the rooms each party size is costed on in the pax matrix — Management's own
-- basis, changed on the Pricing screen (and logged), never a band in the code. One row a
-- size; the rooms must sleep exactly that many (a single sleeps 1, a double or twin 2, a
-- triple 3). Seeded once, as agreed: doubles, and a triple for an odd number above one.
-- A size is priced night by night at the published single / double / triple rates copied
-- beside each night; a hotel with no rate for a room the basis needs leaves that size
-- unpriced, flagged, never guessed.
CREATE TABLE IF NOT EXISTS `tf_pax_rooms` (
  `pax`        SMALLINT UNSIGNED NOT NULL,
  `rooms_sgl`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rooms_dbl`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rooms_twn`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rooms_tpl`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` DATETIME NULL,
  `updated_by` VARCHAR(100) NULL,
  PRIMARY KEY (`pax`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_pax_rooms` (`pax`, `rooms_sgl`, `rooms_dbl`, `rooms_twn`, `rooms_tpl`) VALUES
  (1, 1, 0, 0, 0),
  (2, 0, 1, 0, 0),
  (3, 0, 0, 0, 1),
  (4, 0, 2, 0, 0),
  (5, 0, 1, 0, 1),
  (6, 0, 3, 0, 0),
  (7, 0, 2, 0, 1),
  (8, 0, 4, 0, 0),
  (9, 0, 3, 0, 1),
  (10, 0, 5, 0, 0);

-- The driver's documents: what is handed over before the tour and what comes back
-- after it. The list is a master, edited by a person, never fixed in code. The seeds
-- are what the office hands over today; management adjusts the list on the panel.
CREATE TABLE IF NOT EXISTS `tf_doc_type` (
  `code`       VARCHAR(30) NOT NULL,
  `name`       VARCHAR(80) NOT NULL,
  `phase`      ENUM('before','after') NOT NULL DEFAULT 'before',
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  `updated_by` VARCHAR(100) NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_doc_type` (`code`, `name`, `phase`, `sort_order`) VALUES
  ('ITIN',   'Itinerary copy',  'before', 10),
  ('HOTELS', 'Hotel list',      'before', 20),
  ('ALLOW',  'Allowance sheet', 'before', 30),
  ('LOG',    'Log sheet',       'after',  40),
  ('SETTLE', 'Settlement',      'after',  50);

CREATE TABLE IF NOT EXISTS `tf_transport_doc` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`   INT UNSIGNED NOT NULL,
  `doc_code` VARCHAR(30) NOT NULL,
  `doc_name` VARCHAR(80) NOT NULL,
  `phase`    ENUM('before','after') NOT NULL DEFAULT 'before',
  `state`    ENUM('pending','issued','returned','accepted','waived') NOT NULL DEFAULT 'pending',
  `at`       DATETIME NULL,
  `by`       VARCHAR(100) NULL,
  `note`     VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_tdoc` (`job_id`, `doc_code`),
  CONSTRAINT `fk_tf_tdoc_job` FOREIGN KEY (`job_id`) REFERENCES `tf_transport_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Why a hotel card is a problem: a short list, editable, so the Board can count
-- problems by kind rather than read free text. 'Other' always takes a note.
CREATE TABLE IF NOT EXISTS `tf_reason` (
  `code`       VARCHAR(30) NOT NULL,
  `kind`       ENUM('hotel_problem','service_problem') NOT NULL DEFAULT 'hotel_problem',
  `name`       VARCHAR(120) NOT NULL,
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_reason` (`code`, `kind`, `name`, `sort_order`) VALUES
  ('FULL',    'hotel_problem', 'Hotel full or stop-sale on these dates', 10),
  ('RATE',    'hotel_problem', 'Hotel quotes a different rate from the contract', 20),
  ('ROOM',    'hotel_problem', 'Room type or meal plan not available', 30),
  ('NOREPLY', 'hotel_problem', 'No reply from the hotel', 40),
  ('OTHER',   'hotel_problem', 'Other — see the note', 90);

-- The trail. Every change to a tour or a card: which lane, which card, what moved
-- from what to what, who, when, why. The Board reads this; so does a card's history.
-- intake_id is carried as well as tour_id so an un-confirm (which removes the tour)
-- still leaves its mark against the inquiry.
CREATE TABLE IF NOT EXISTS `tf_tour_event` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`   INT UNSIGNED NOT NULL,
  `tour_id`     INT UNSIGNED NULL,
  `lane`        ENUM('tour','quotation','hotel','transport','service') NOT NULL DEFAULT 'tour',
  `job_id`      INT UNSIGNED NULL,
  `event`       VARCHAR(40)  NOT NULL,
  `from_status` VARCHAR(30)  NULL,
  `to_status`   VARCHAR(30)  NULL,
  `note`        VARCHAR(500) NULL,
  `by`          VARCHAR(100) NULL,
  `at`          DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_event_tour` (`tour_id`, `id`),
  KEY `ix_tf_event_intake` (`intake_id`, `id`),
  CONSTRAINT `fk_tf_event_tour` FOREIGN KEY (`tour_id`) REFERENCES `tf_tour` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  -- hotel voucher numbers: prefix and the next number, taken atomically like inquiry numbers
  ('voucher_prefix', 'HV'),
  ('voucher_next', '1001'),
  -- a transport card whose tour arrives within this many days and has no pack issued is counted on the badge
  ('pack_warn_days', '7');

-- Schema 11 ------------------------------------------------------------------
-- The quotation engine proper.
--
--   * Passenger groups. A party that splits — three go home on day seven, seven stay
--     ten nights — is two groups, each with its own dates, its own room mix and its own
--     names. A party that does not split is one group, the whole party, and nothing
--     changes for it. Every vehicle, every hotel night and every excursion is costed
--     for the group it serves.
--   * Several vehicles and drivers on the same day: a vehicle line names the group it
--     carries and the days it is engaged for; its legs are its own (vehicle_seq on the
--     sector). The eighty-kilometre stay-day figure is charged only on a day the vehicle
--     is actually retained — a vehicle released for the day charges nothing.
--   * Excursions with their own rate master, kept apart from entrance fees. One that
--     includes transport waives the local running on that day so mileage is never
--     charged twice.
--   * Visit descriptions: copied from the Place to Visit master onto the quote's own
--     line, where they may be edited; the master is never written to from here.
--   * Room type per night, chosen from the Hotel Rates panel, with the published rate
--     copied beside it so the costing is the panel's figure, never a typed one.
--   * The quotation itself as a numbered version with its costing frozen inside it.
--
-- Additive, as ever: nothing outside tf_ is touched.

CREATE TABLE IF NOT EXISTS `tf_intake_group` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`  INT UNSIGNED NOT NULL,
  `code`       VARCHAR(10) NOT NULL DEFAULT 'A',
  `name`       VARCHAR(80) NOT NULL,
  `pax`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `day_from`   SMALLINT UNSIGNED NULL,
  `day_to`     SMALLINT UNSIGNED NULL,
  `rooms_sgl`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rooms_dbl`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rooms_twn`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `rooms_tpl`  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `note`       VARCHAR(255) NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_group_intake` (`intake_id`, `sort_order`),
  CONSTRAINT `fk_tf_group_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Whether a vehicle line is kept on for a given day. Keyed by the line's position rather
-- than its id, because a re-plan rebuilds the lines and must not lose what a person
-- decided about a stay day. Absent row = retained, the safe default: nothing is ever
-- silently dropped from a quotation, only released on purpose.
CREATE TABLE IF NOT EXISTS `tf_intake_vehicle_day` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`   INT UNSIGNED NOT NULL,
  `vehicle_seq` SMALLINT NOT NULL DEFAULT 0,
  `day_no`      SMALLINT UNSIGNED NOT NULL,
  `retained`    TINYINT(1) NOT NULL DEFAULT 1,
  `note`        VARCHAR(255) NULL,
  `updated_by`  VARCHAR(100) NULL,
  `updated_at`  DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_vday` (`intake_id`, `vehicle_seq`, `day_no`),
  CONSTRAINT `fk_tf_vday_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Master Files → Excursion Rates. Its own list, apart from entrance fees: a jeep safari,
-- a whale-watching boat, a train ride, a village tour. Typed by a person, changed by a
-- person. A quotation copies the rate onto its own line the moment the excursion is
-- added, so a later change here never moves a figure already quoted.
CREATE TABLE IF NOT EXISTS `tf_excursion_rate` (
  `code`               VARCHAR(30) NOT NULL,
  `name`               VARCHAR(160) NOT NULL,
  `unit`               ENUM('per_person','per_group','per_vehicle') NOT NULL DEFAULT 'per_person',
  `rate`               DECIMAL(12,2) NOT NULL,
  `currency`           CHAR(3) NOT NULL DEFAULT 'USD',
  `includes_transport` TINYINT(1) NOT NULL DEFAULT 0,
  `valid_from`         DATE NULL,
  `valid_to`           DATE NULL,
  `note`               VARCHAR(255) NULL,
  `active`             TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by`         VARCHAR(100) NULL,
  `updated_at`         DATETIME NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_intake_excursion` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`          INT UNSIGNED NOT NULL,
  `day_no`             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `group_id`           INT UNSIGNED NULL,
  `code`               VARCHAR(30) NOT NULL,
  `name`               VARCHAR(160) NOT NULL,
  `unit`               ENUM('per_person','per_group','per_vehicle') NOT NULL DEFAULT 'per_person',
  `qty`                DECIMAL(10,2) NOT NULL DEFAULT 1,
  `rate`               DECIMAL(12,2) NOT NULL,
  `currency`           CHAR(3) NOT NULL DEFAULT 'USD',
  `amount`             DECIMAL(12,2) NULL,
  `includes_transport` TINYINT(1) NOT NULL DEFAULT 0,
  `description`        TEXT NULL,
  `note`               VARCHAR(255) NULL,
  `sort_order`         SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_exc_intake` (`intake_id`, `day_no`, `sort_order`),
  CONSTRAINT `fk_tf_exc_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The quotation as issued: a numbered version with the whole costing frozen inside it.
-- The client's price is cost plus a markup that only Management sets and only
-- Management sees; the internal costing shows cost, and the profit lines are drawn
-- only for a group that holds the Pricing screen.
CREATE TABLE IF NOT EXISTS `tf_quote` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`      INT UNSIGNED NOT NULL,
  `version`        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `status`         ENUM('draft','issued','superseded') NOT NULL DEFAULT 'draft',
  `currency`       CHAR(3) NOT NULL DEFAULT 'USD',
  `usd_rate`       DECIMAL(10,4) NULL,
  `rate_date`      DATE NULL,
  `pax`            SMALLINT UNSIGNED NULL,
  `cost_lkr`       DECIMAL(14,2) NULL,
  `cost_usd`       DECIMAL(14,2) NULL,
  `markup_pct`     DECIMAL(6,2) NULL,
  `sell_usd`       DECIMAL(14,2) NULL,
  `per_person_usd` DECIMAL(12,2) NULL,
  `cost_json`      MEDIUMTEXT NULL,
  `valid_until`    DATE NULL,
  `note`           TEXT NULL,
  `issued_at`      DATETIME NULL,
  `issued_by`      VARCHAR(100) NULL,
  -- schema 34: the maximum number of travellers this version is quoted for — the client quotation and
  -- its PDF show the price-per-person rows up to it and no further. Set when the version is made (the
  -- party size), changed on the client quotation screen until the version has been emailed.
  `max_pax`        SMALLINT UNSIGNED NULL,
  `created_at`     DATETIME NOT NULL,
  `created_by`     VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_quote_v` (`intake_id`, `version`),
  CONSTRAINT `fk_tf_quote_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  -- the client's price: cost plus this percentage. Unset on purpose — Management sets it
  -- on the Pricing screen; until then no client quotation is issued.
  ('markup_default_pct', ''),
  ('quote_currency', 'USD'),
  ('quote_validity_days', '14'),
  ('quote_terms', 'Rates are quoted per person in US dollars on the room basis shown, valid until the date given, and subject to availability at the time of confirmation. Entrance fees, meals and services not listed are excluded.');

-- Schema 12 ------------------------------------------------------------------
-- Who handles a tour, who changed what, and archiving instead of deleting.
--
--   * Ownership is one thing, editing another. Every inquiry carries a handling agent —
--     the staff member whose tour it is, the person who created it unless Management
--     reassigns it — and the tour carries the same. Anyone may edit; the handler stays.
--   * Every edit is written to tf_activity: which record, what moved from what to what,
--     who and when. Reassignments, archives and restores are written there too. The
--     desks' own trail (tf_tour_event) is unchanged; a tour's history reads both.
--   * Nothing is deleted by a button. An inquiry or a card is archived — hidden from
--     every list and count, restorable, its data intact — and only an archived inquiry
--     with nothing attached to it can be removed for good, by Management, on purpose.
--
-- The columns are added by the installer (ALTER TABLE, checked first); the table below
-- is new. Additive, as ever: nothing outside tf_ is touched.

CREATE TABLE IF NOT EXISTS `tf_activity` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity`       VARCHAR(30)  NOT NULL,
  `entity_id`    INT UNSIGNED NOT NULL,
  `intake_id`    INT UNSIGNED NULL,
  `tour_id`      INT UNSIGNED NULL,
  `action`       VARCHAR(40)  NOT NULL,
  `summary`      VARCHAR(500) NULL,
  `changes_json` MEDIUMTEXT   NULL,
  `by_user_id`   INT UNSIGNED NULL,
  `by_user`      VARCHAR(100) NULL,
  `by_name`      VARCHAR(190) NULL,
  `at`           DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_act_intake` (`intake_id`, `id`),
  KEY `ix_tf_act_tour` (`tour_id`, `id`),
  KEY `ix_tf_act_entity` (`entity`, `entity_id`, `id`),
  KEY `ix_tf_act_user` (`by_user_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 13 ------------------------------------------------------------------
-- Which customer is a booking agent. The customer ledger (customers) holds agents and
-- ticketing customers alike; its `agent` column (already there, empty for everyone)
-- becomes the one mark: 1 = agent, 0 = not. The installer writes 0 into every empty
-- value and makes 0 the default (Installer::agentFlag) — no table here. Marking is done
-- by hand under Master Files → Consignees (Mark as agent / Unmark), each change written
-- to tf_activity (entity `customer`). Every quotation screen then offers the marked
-- agents first: the Tour Inquiry panel, New Quotation and Tour Quotation. Until the
-- first customer is marked they keep offering the whole ledger, so nothing goes blank.

-- Schema 14 ------------------------------------------------------------------
-- The email as pasted, and the copy that is read. `source_text` is what the reader works
-- from; before it is read, staff review it and may correct it — a missing year on "17JAN",
-- say — and `source_text_original` keeps the paste exactly as it arrived, never changed.
-- `source_edited_at` / `source_edited_by` say when and by whom the working copy last
-- moved; every edit is a line in tf_activity (email_edited) with the lines that changed.
-- Columns added by the installer (ALTER TABLE, checked first); no new table. Dates are
-- checked before reading (TextChecks): a day and month with no year, no date at all, a
-- departure before the arrival, a date already gone — flagged, never guessed.

-- Schema 15 ------------------------------------------------------------------
-- Where a night's meal plan came from. Agents often write the meal basis once, in the
-- terms at the foot of the email ("accommodation H/B"); the reader returns it as the
-- cited field `meal_basis`, or the plain-text reading finds it (EmailExtractor::
-- mealStatedForAll), and it is put on every night that names no meal plan of its own.
-- `tf_intake_day.meal_source` says which: 'day' (written on the night's line), 'tour'
-- (from the tour-wide basis; `meal_quote` keeps the words), 'human' (set by a person),
-- NULL (recorded before this schema). A night with a source of its own is never
-- overwritten by the basis, and a night without any meal plan stays without one — the
-- screen then shows the published figure per meal plan for the chosen room type and the
-- person picks. Columns added by the installer (ALTER TABLE, checked first); no new table.

-- Schema 16 ------------------------------------------------------------------
-- The rate on a night is kept once it is copied. A figure copied from Hotel Rates onto a
-- night (`rate_sgl` / `rate_dbl` / `rate_tpl` / `rate_unit`, with the plan) is what the
-- agent is quoted on, so a plan later corrected or replaced in the panel must not move it
-- unseen. `tf_intake_day.rate_frozen_at` is when the figure was copied; `rate_key` is what
-- it was priced for (hotel | date | room type | meal plan | market); every later check
-- only compares the panel's answer with the figure kept and, when the money differs (or
-- the panel has no published rate any more), stores the panel's answer in
-- `rate_drift_json` for the screen to show beside the night and the checklist to flag.
-- The figure is read afresh, without asking, only when the night itself changed (another
-- hotel, date, room type or meal plan is another price) or when it had no figure yet; and
-- on purpose when somebody presses Use the new rate (IntakeService::repriceDay), which is
-- a line in tf_activity (rate_repriced). A re-read of the email carries the kept figure
-- onto the night it re-creates when that night is for the same hotel. Columns added by
-- the installer (ALTER TABLE, checked first); no new table.

-- Schema 17 ------------------------------------------------------------------
-- Two figures on every driven leg, one quoted. Google's road distance (`google_km`, asked
-- through GoogleDistance with the key in .env, kept in tf_google_distance) and the
-- company's own driven record (`actual_km`, the median of tour_sub_locations.km — the
-- actual mileage the office put on the leg on past tours, not estimate_km, which was
-- Google's figure on the day) sit side by side; `km` / `km_by` say which one the quotation
-- is costed on. Google by preference (tf_meta mileage_quote_source = 'google', changed on
-- Master Files → Mileage Allowances): it has a figure for a sector nobody has driven yet,
-- and it tends to sit above the driven record, which is the buffer wanted in the price.
-- The driven record stands in where Google has nothing (no key, a place it cannot find,
-- a line fault) and the leg says which; a distance typed by hand stands over both. The
-- legs planned before this carry their old figure across as the driven record and are
-- asked about when next planned, or from the Ask Google button (IntakeService::googleLegs,
-- a line in tf_activity, mileage_google). Columns added by the installer; one new table.

-- Schema 18 ------------------------------------------------------------------
-- The kilometres actually driven, entered after the tour, and learned from. The
-- transport card's legs carry Google's figure beside the costed one (`tf_transport_leg.
-- google_km`, `quoted_by` = which figure the quotation used) and take the actual driven
-- kilometres per leg once the tour has run (`actual_km`, who and when, a note) — the
-- Google figure is never overwritten by it. Those actuals are the driven record's first
-- source: MileageLookup reads them beside tour_sub_locations.km, and once a sector has
-- enough of them (tf_meta km_min_samples, three by default) the figure is their median
-- and the older record only a note; `tf_intake_sector.samples_post` says how many of the
-- journeys behind a figure were entered after a tour. On the inquiry the actual column
-- can also be typed by hand (`actual_by` = 'manual'), always beside Google's figure and
-- never in place of it; a typed actual that the quotation takes (Google missing, or the
-- setting) is `km_by` = 'actual'. A leg taken off the mileage (the Delete button) is kept
-- as a struck-through row (`removed_at`, `removed_by`) so the next re-plan does not put it
-- back and Restore can; it counts for nothing. Columns added by the installer; no new table.
--
-- Schema 19: `plan_key` says which leg of the plan a row is. Changing a leg's kilometres
-- makes the row the person's (`origin` = 'human') but leaves it the plan's leg, so the plan
-- neither lays it down a second time nor gives up on the rest of that day — before this, a
-- garage figure typed on day one quietly cost the day its airport run and its transfer. A
-- leg a person adds themselves has none: the plan never made it, and plans around it.

-- Schema 20 ------------------------------------------------------------------
-- The three desks, one tour, to the brief.
--
-- Confirm needs an issued quotation: the agent accepted a version, and the tour
-- records which (`tf_tour.quote_id`, `quote_version`). The tour takes its own number
-- when it is confirmed — prefix, year and a running count, all from tf_meta
-- (tour_prefix, tour_year, tour_next, tour_pad), so "AVH-2026-00125" is a setting and
-- not code; tours confirmed before this keep the number they have. The inquiry keeps
-- its own INQ number throughout.
--
-- Activities and tickets go on the Hotel Reservations & Activities desk the moment
-- the tour is confirmed, one row per thing to arrange (`tf_service_job`): every
-- service line and excursion the quotation carried except what the party arranges
-- for themselves or is not happening, each with the same steps a hotel stay has —
-- to book, requested, confirmed with a reference — plus "not needed" for a line the
-- desk finds nothing to arrange for. When every stay and every item is done the desk
-- marks the tour HOTELS & ACTIVITIES CONFIRMED (`tf_tour.reservations_confirmed_at`,
-- `_by`), which the inquiry, the Transport Desk and the Board all see.
--
-- A quoted inquiry can carry a follow-up date (`tf_intake.follow_up_on`, `_note`):
-- the Inquiries list has a "Follow-ups due" filter and count. Nothing is sent.
CREATE TABLE IF NOT EXISTS `tf_service_job` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`          INT UNSIGNED NOT NULL,
  `seq`              SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  -- where the line came from: a service line or an excursion on the quotation, or the desk itself
  `source`           ENUM('service','excursion','desk') NOT NULL DEFAULT 'service',
  `source_id`        INT UNSIGNED NULL,
  `kind`             VARCHAR(20)  NOT NULL DEFAULT 'other',
  `title`            VARCHAR(255) NOT NULL,
  `day_no`           SMALLINT UNSIGNED NULL,
  `on_date`          DATE NULL,
  `pax`              SMALLINT UNSIGNED NULL,
  `qty`              DECIMAL(10,2) NULL,
  `pay_mode`         VARCHAR(20)  NULL,
  -- schema 37: the Entrance Fee master's row the line stands for (copied from the quotation line), so the
  -- ticket stock knows which attraction's tickets it takes
  `fee_id`           INT NULL,
  `fee_code`         VARCHAR(100) NULL,
  `special_note`     VARCHAR(500) NULL,
  -- schema 37: 'pay_at_gate' — the entrance is paid at the counter on the day; its own status, never "nothing to arrange"
  `status`           ENUM('to_book','requested','confirmed','not_needed','problem','cancelled','pay_at_gate') NOT NULL DEFAULT 'to_book',
  `requested_at`     DATETIME NULL,
  `requested_by`     VARCHAR(100) NULL,
  `request_note`     VARCHAR(400) NULL,
  `confirmation_ref` VARCHAR(100) NULL,
  `confirmed_with`   VARCHAR(160) NULL,
  `confirmed_at`     DATETIME NULL,
  `confirmed_by`     VARCHAR(100) NULL,
  `problem_code`     VARCHAR(30)  NULL,
  `problem_note`     VARCHAR(400) NULL,
  `problem_at`       DATETIME NULL,
  `problem_by`       VARCHAR(100) NULL,
  `amended`          TINYINT(1) NOT NULL DEFAULT 0,
  `note`             VARCHAR(500) NULL,
  `created_at`       DATETIME NULL,
  `created_by`       VARCHAR(100) NULL,
  `updated_at`       DATETIME NULL,
  `updated_by`       VARCHAR(100) NULL,
  `archived_at`      DATETIME NULL,
  `archived_by`      VARCHAR(100) NULL,
  `archive_note`     VARCHAR(400) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_sjob_tour` (`tour_id`, `seq`),
  KEY `ix_tf_sjob_status` (`status`, `on_date`),
  CONSTRAINT `fk_tf_sjob_tour` FOREIGN KEY (`tour_id`) REFERENCES `tf_tour` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  -- tour numbers: PREFIX-YEAR-COUNT, the count restarting each year, padded to tour_pad digits
  ('tour_prefix', 'AVH'),
  ('tour_year', ''),
  ('tour_next', '1'),
  ('tour_pad', '5');

-- Schema 21 ------------------------------------------------------------------
-- One tour, one job on each desk.
--
-- Confirm puts ONE job on Hotel Bookings and ONE on Transport: the tour itself. The
-- stays, the activity lines and the vehicle cards are what is inside that job, never
-- jobs of their own. Each desk's list is therefore one row per confirmed tour, opened
-- to see the hotels (or the vehicles) it needs; the red count on the bar is the number
-- of TOURS still needing that desk's work, so five hotels on one tour count once and a
-- tour leaves the count only when the desk marks its work complete. Hotel work
-- complete is the mark already on the tour (`reservations_confirmed_at`: every stay
-- with its voucher issued, every activity line confirmed or not needed). Transport
-- work complete is the new mark (`transport_complete_at`, `_by`): every vehicle set,
-- every driver and guide engaged. A card moving back takes its desk's mark off again.
-- Nothing is deleted when a desk finishes: the tour stays under Completed with every
-- line of its history.
--
-- The Hotel Desk sends the hotel a booking request, and later the voucher, from the
-- stay itself — through the signed-in user's own mailbox, as the Hotel Reservation
-- screen has always done — and the copy sent is kept untouched (`tf_hotel_message`),
-- with the hotel's replies recorded beside it. The company's own lines on those
-- documents (name, address, telephone, email) are settings, not code.
CREATE TABLE IF NOT EXISTS `tf_hotel_message` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`     INT UNSIGNED NOT NULL,
  `tour_id`    INT UNSIGNED NOT NULL,
  -- what it is: the booking request sent, the voucher sent, or a reply the hotel gave
  `kind`       ENUM('request','voucher','reply') NOT NULL DEFAULT 'request',
  -- how it went or came: by email from here, or by other means recorded by hand
  `channel`    ENUM('email','other') NOT NULL DEFAULT 'email',
  `to_addr`    VARCHAR(400) NULL,
  `cc_addr`    VARCHAR(400) NULL,
  `subject`    VARCHAR(255) NULL,
  `body`       MEDIUMTEXT NULL,
  `state`      ENUM('sent','failed','recorded') NOT NULL DEFAULT 'sent',
  `error`      VARCHAR(400) NULL,
  `by`         VARCHAR(100) NULL,
  `by_name`    VARCHAR(190) NULL,
  `at`         DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_hmsg_job` (`job_id`, `id`),
  KEY `ix_tf_hmsg_tour` (`tour_id`, `id`),
  CONSTRAINT `fk_tf_hmsg_job` FOREIGN KEY (`job_id`) REFERENCES `tf_hotel_job` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- what a hotel can answer, in the words the desk uses
INSERT IGNORE INTO `tf_reason` (`code`, `kind`, `name`, `sort_order`) VALUES
  ('REJECTED',    'hotel_problem', 'Rejected / unavailable — the hotel cannot take the booking', 5),
  ('ALTERNATIVE', 'hotel_problem', 'Alternative required — another hotel has to be found', 6);

INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  -- the company's lines on the booking request and the voucher (Manage → Settings)
  ('company_name',    'Avenir Holidays (Pvt) Ltd'),
  ('company_address', 'No. 126/1, Park Road, Colombo 05, Sri Lanka'),
  ('company_phone',   '(+94) 114 215 977'),
  ('company_email',   'traveljp@avenirholidays.com, land@avenirholidays.com');

-- Schema 22 ------------------------------------------------------------------
-- Admin delete, without orphans and with a trail.
--
-- Only the Admin privilege (tour/manage) deletes a tour, and it is a soft delete:
-- the tour is cancelled (every card cancelled, both desks' marks off), archived
-- (`tf_tour.archived_at`, `_by`, `archive_note`) together with its inquiry, and so out of
-- Inquiries, Hotel Bookings, Transport, the Board and every red count — restorable from
-- Admin → Deleted / archived with everything it carried. A deleted tour can then be
-- removed for good, with its number typed to confirm: the tour row goes and takes its
-- cards, legs, crew, documents, letters and activity lines with it (foreign keys
-- cascade), its trail lines go with it, and one line on the Activity log records what
-- was removed and by whom. The inquiry is removed the same way only when it has nothing
-- else attached (no issued quotation, no legacy link). Columns added by the installer.

-- Schema 23 ------------------------------------------------------------------
-- Driver allocation and guide allocation, with the bata kept in the master.
--
-- The quotation screen holds a driver section and a guide section. Each picks a type
-- (the role's `category`, added to `tf_crew_role` by the installer) and a language from
-- this master, allocates a freelancer from the supplier ledger, and has its own room
-- line. The bata per day is the master's figure for that category and language, times
-- the days: shown on the quotation, never typed there. The master is maintained under
-- Master Files → Crew Bata, its own privilege (menu URL master_files/crew_bata), granted
-- to the System Administrator by tour_flow_menu_crew_bata.sql. A freelancer's own agreed
-- bata on the ledger is carried for the payment side and shown for information; it no
-- longer replaces the category bata in the quotation.

-- Schema 24 ------------------------------------------------------------------
-- A driver or guide replaced mid-tour, and a payment voucher per person.
--
-- On the Transport Desk, a group holding the "Crew replacement" privilege (menu URL
-- tour/transport_ops/crew_change, granted to the System Administrator and Management)
-- can replace an engaged driver or guide from a given day. The outgoing person's row
-- stays on the card, marked replaced and ended on their last day; the replacement gets
-- a row of their own from their first day, on the same tour and the same vehicle card.
-- Every crew row carries its start and end dates (`tf_transport_crew.date_from`,
-- `date_to`; defaulted at Confirm from the quoted days, corrected by the desk). The
-- vehicle is the card's and is changed by its own action with its own trail line; a
-- change of crew never touches it. Each person then gets a payment voucher for the
-- days actually worked (tf_crew_voucher: CPV numbers, days × their own agreed bata or
-- the master's), printable, kept for good, cancelled with a note and re-issued when the
-- days change. Columns added by the installer; the status list widened in place.

-- Schema 25 ------------------------------------------------------------------
-- Sightseeing that costs nothing, and the priced list's other names.
--
-- A day service is one of three things: priced from the entrance fee master (a positive
-- amount under the name or code, or under a phrase the aliases setting maps to it), no
-- charge (a sightseeing stop with nothing to pay — said by a person, or recognised from
-- the no-charge words setting when the list has no positive amount for it), or unsure.
-- An unsure line is never forced to a price: the screen and the checklist offer No charge,
-- the closest entry in the list (`tf_intake_service.suggest_code`), or the master. Only a
-- positive amount in the master outranks the no-charge words, so "Colombo SS" at 3 dollars
-- is priced and "Kandy S/S" is not. The status lists are widened in place; the column and
-- the two settings are added by the installer.

-- Schema 26 ------------------------------------------------------------------
-- The driver's advance and settlement on the transport card.
--
-- Before the tour a driver is often given an advance — twenty, thirty, forty, fifty per
-- cent, or a figure of management's own — against what the card says is payable: the
-- vehicle hire as costed (kilometres × the rate copied onto the card at Confirm) plus the
-- driver's bata for the days. The card works the amount out from the percentage; a holder
-- of the Driver advance privilege (tour/transport_ops/driver_advance — Management and
-- the System Administrator by default) adjusts it if need be, approves it, and records the
-- payment (who paid, the day, the method, the reference, remarks). Requested → approved →
-- paid; cancelled with a note before it is paid, never after (a wrong payment is put
-- right by an adjustment on the settlement). At the end, Settled — the card's last step —
-- freezes the settlement: the payable (the hire on the kilometres actually driven when
-- they are entered, else as scheduled, else as costed; the bata from the payment vouchers
-- issued, else the lines), less the advances paid, plus or minus the adjustments listed,
-- equals the balance; numbered, kept, printable as the Settlement of the driver's pack.
-- A card with an advance still unpaid cannot be settled. Every move is on the trail and
-- the history with its old and new figures.

-- Schema 27 ------------------------------------------------------------------
-- The tour photo library, a presentation layer over the tour data.
--
-- Admin keeps three to five good photos per destination, attraction or activity under
-- Master Files → Tour Photos (upload, preview, replace, remove, restore; a caption; one or
-- more tags; preferred). A tag carries the words a day's programme uses for it, edited
-- there too, so new destinations and new words are added any time without a change to
-- the code. When the client quotation is prepared, each day's area, programme, visits
-- and excursions are read for those words; the best tag's photos are offered preferred
-- first and least used next, the same picture is never used twice on one itinerary, and
-- the pick is kept on the inquiry so the quotation is stable and staff may change any
-- day's photo by hand (or say "no photo"). A day whose programme changes is picked again;
-- a person's choice stays. The files live under public/uploads/tour_photos (the folder is
-- a setting), resized on upload with a thumbnail for the screens. Nothing here is a price.

-- Schema 28 ------------------------------------------------------------------
-- The itinerary written as a person writes one. The client quotation carries a paragraph
-- for each day, built by ItineraryWriter from the night, its visits and excursions in the
-- desk's order, the hotel and the flights: "Upon arrival at Bandaranaike International
-- Airport on UL 504, you will be met and assisted by our local representative, then transfer
-- to your hotel in Negombo." / "After breakfast, leave for Kandy. En route, visit Dambulla
-- Cave Temple. On arrival in Kandy, visit the Temple of the Tooth. Check in at Hotel
-- Casamara." / "After breakfast, transfer to Bandaranaike International Airport for your
-- departure flight on UL 503." The sentences are the itin_* settings above; a person's own
-- wording for a day (`tf_intake_day.client_text`, added by the installer) stands as written.
-- The wording is frozen into each quotation version with its costing, so what was sent stays.

-- Schema 29 ------------------------------------------------------------------
-- Two programme masters, and the costing in three sections. Free Visits (`tf_free_visit`,
-- Master Files → Free Visits) holds each destination's normal sightseeing and en-route
-- stops and carries no cost; a free visit added to a day is a no-charge line on the
-- programme (`tf_intake_service.free_visit_id`, added by the installer) and never reaches
-- the costing. Paid Activities & Services (Master Files → Paid Activities & Services) is one
-- screen by destination over the priced lists already in use — entrance_fee (entrances,
-- tickets and the activities priced a head; read as it is, its entries still added and
-- changed under Master Files → Entrance Fee, which opens their accounts posting; TourFlow's
-- own destination and on/off for an entry sit beside it in `tf_fee_meta`) and
-- tf_excursion_rate (activities and excursions priced per person, group or vehicle, kept on
-- this screen; `location` added) — and a paid item put on a day pulls its rate into the
-- costing as before. The internal
-- costing reads in three sections: hotels with a subtotal a hotel, paid activities and
-- services, and transport (vehicle and mileage, driver and guide and their rooms), added up
-- to the tour cost (QuoteService::costing, `groups` and `hotel_subtotals`). The free visits
-- already on open inquiries as no-charge lines are copied into the master once, by
-- destination, so nothing typed so far is lost.

-- Schema 30 ------------------------------------------------------------------
-- A destination is a person's word, never worked out. An entry of the priced list sits under
-- a destination in Paid Activities & Services only once somebody puts it there (tf_fee_meta);
-- its address is shown beside it and never read for a town. The no-charge stops copied into
-- Free Visits from past inquiries carry no destination until a person gives them one:
-- `tf_free_visit.origin` (added by the installer) says which inquiry and night each came from.
-- Nothing is deleted from either master any more: taking an item off switches it off and
-- keeps it. Before any schema upgrade the installer writes a copy of every tf_ table (and the
-- TourFlow menu entries and their privileges) to writable/tf_backups/, and refuses to upgrade
-- if it cannot.

-- Schema 31 ------------------------------------------------------------------
-- The pax matrix: above the three-section internal costing, what the tour costs and what the
-- client pays per person for each party size from one to ten, each size worked out on its
-- own from the masters — never one total divided. Rooms: the basis above (`tf_pax_rooms`)
-- at the published rates on each night. Paid activities and services: per person × the
-- party, per group once, per vehicle × the vehicles the size needs. Transport: the vehicle
-- the Transport Rates ladder gives for the size (its "carries up to" figure, which
-- Management can now change on that screen, logged), on the tour's charged kilometres, at
-- the rate this inquiry already carries for that size or the master's rate in force. The
-- inquiry's own party size is priced on its own rooms and vehicle and must equal the detailed
-- costing, or the quotation waits. The agent's quotation gains a simple table — party size,
-- the rooms it is based on, the price per person — and never a cost, a markup or a profit.

-- Schema 32 ------------------------------------------------------------------
-- One rule for a tour's shape, for every inquiry, read from an email or typed by hand: the
-- arrival and departure dates decide it. Nights = departure − arrival; days = nights + 1; one
-- hotel night per night, from the arrival date to the night before departure. The departure day
-- is made by the system — the transfer from the last night's place to the airport, and anything
-- else on that day — and is never a hotel night. A line of the itinerary dated on or after the
-- departure is kept as it is but stops the quotation until a person says what it is: the
-- departure day (its services and wording move there) or an extra night (the departure moves).
-- The email's own dates sit beside the inquiry's (`email_arrival_date`, `email_departure_date`);
-- when a person's dates differ, Go for Quotation waits until somebody confirms which stand.
-- Driver and guide days follow the programme — every day with a transfer, a visit or the vehicle
-- on duty, arrival and departure included — unless a person types them (`tf_intake_crew.days_by`).
-- Opening a declined, cancelled or archived inquiry changes nothing on it.

-- Schema 33 ------------------------------------------------------------------
-- Edit Requests. Below the original email on every inquiry, a colleague pastes the words that
-- need to change and types what they should say now; TourFlow finds the working values those
-- words stand for, shows Current → New with everything that follows from it (the nights, the
-- rates, the legs, the crew's days, the costing), and changes them only when the colleague
-- confirms — through the same service calls a person's own edits use, so every rule stays.
-- The original email is never changed. Nothing is guessed: what cannot be told apart is asked,
-- and a figure (a rate, a price, kilometres) is never taken from the words.
-- Each request is kept whole (`tf_edit_request`): the words, the answers given, what was shown,
-- what was applied, who asked, who confirmed and when; every one applied is also a line in
-- tf_activity with every value that moved. Once one is applied, reading the email again is
-- switched off for that inquiry, and Confirm Tour waits for a quotation version issued after
-- it. On a confirmed tour a request is saved as Waiting for Amendment with its reason and
-- changes nothing until Management opens the tour with Amend on the Tour Board.
-- Edit Request Authority is per person (`tf_user_authority`), off for everyone — the System
-- Administrator included — until Management turns it on under Tour Desk Admin → Edit Requests.
-- Two new tables; nothing else changes.

CREATE TABLE IF NOT EXISTS `tf_edit_request` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`       INT UNSIGNED NOT NULL,
  -- the request's number on its inquiry: #1, #2...
  `seq`             SMALLINT UNSIGNED NOT NULL,
  -- open (being checked), waiting (a confirmed tour: for Management's Amend), applied, cancelled (withdrawn)
  `status`          ENUM('open','waiting','applied','cancelled') NOT NULL DEFAULT 'open',
  -- the two boxes, word for word
  `pasted_text`     TEXT NOT NULL,
  `change_to`       TEXT NOT NULL,
  `reason`          VARCHAR(1000) NULL,
  `was_locked`      TINYINT(1) NOT NULL DEFAULT 0,
  `tour_id`         INT UNSIGNED NULL,
  `requested_by`    VARCHAR(100) NULL,
  `requested_by_id` INT UNSIGNED NULL,
  `requested_at`    DATETIME NOT NULL,
  -- the answers to the questions asked, and what the last check showed (Current → New and what follows)
  `answers_json`    MEDIUMTEXT NULL,
  `proposal_json`   MEDIUMTEXT NULL,
  -- the inquiry as it was when checked: Confirm applies only while it is still so
  `fingerprint`     CHAR(40) NULL,
  `checked_by`      VARCHAR(100) NULL,
  `checked_at`      DATETIME NULL,
  -- what was applied, with every value that moved
  `result_json`     MEDIUMTEXT NULL,
  `confirmed_by`    VARCHAR(100) NULL,
  `confirmed_by_id` INT UNSIGNED NULL,
  `confirmed_at`    DATETIME NULL,
  -- the newest quotation version on the inquiry when it was applied: the version confirmed as the sale
  -- has to be one made (and issued) after it
  `quote_mark`      INT UNSIGNED NULL,
  `cancelled_by`    VARCHAR(100) NULL,
  `cancelled_at`    DATETIME NULL,
  `cancel_note`     VARCHAR(255) NULL,
  -- the history line written when it was applied
  `activity_id`     INT UNSIGNED NULL,
  `updated_at`      DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_er_seq` (`intake_id`, `seq`),
  KEY `ix_tf_er_status` (`status`, `intake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A person's own authorities, switched on and off by Management one account at a time; nobody
-- holds one until it is switched on. `edit_request` is the first.
CREATE TABLE IF NOT EXISTS `tf_user_authority` (
  `user_id`    INT UNSIGNED NOT NULL,
  `authority`  VARCHAR(40)  NOT NULL,
  `username`   VARCHAR(100) NULL,
  `name`       VARCHAR(160) NULL,
  `granted`    TINYINT(1)   NOT NULL DEFAULT 0,
  `set_by`     VARCHAR(100) NULL,
  `set_by_id`  INT UNSIGNED NULL,
  `set_at`     DATETIME     NULL,
  `note`       VARCHAR(255) NULL,
  PRIMARY KEY (`user_id`, `authority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------------------------
-- Schema 34: the quotation emailed to the agent from the client quotation screen, with the PDF
-- attached, through the signed-in person's own mailbox (as hotel letters go). Every send, and
-- every failure, is kept here with what went: to, cc, subject, the message, the PDF's name and the
-- range of travellers it carried. The subject and the message start from a ready-made email
-- (`tf_email_template`, below), chosen in the send window and filled in per inquiry.
-- ---------------------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tf_quote_email` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`   INT UNSIGNED NOT NULL,
  `quote_id`    INT UNSIGNED NOT NULL,
  `version`     SMALLINT UNSIGNED NOT NULL,
  `to_email`    VARCHAR(190) NOT NULL,
  `cc_email`    VARCHAR(500) NULL,
  `subject`     VARCHAR(255) NOT NULL,
  `message`     TEXT NULL,
  `pdf_name`    VARCHAR(120) NULL,
  `max_pax`     SMALLINT UNSIGNED NULL,
  `status`      ENUM('sent','failed') NOT NULL DEFAULT 'sent',
  `error`       VARCHAR(500) NULL,
  `sent_by`     VARCHAR(100) NULL,
  `sent_by_id`  INT UNSIGNED NULL,
  `sent_name`   VARCHAR(190) NULL,
  `sent_from`   VARCHAR(190) NULL,
  `sent_at`     DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_qe_quote` (`quote_id`, `sent_at`),
  KEY `ix_tf_qe_intake` (`intake_id`, `sent_at`),
  CONSTRAINT `fk_tf_qe_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tf_qe_quote` FOREIGN KEY (`quote_id`) REFERENCES `tf_quote` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The ready-made emails (schema 34): a template is a name, a category (which send window offers it),
-- a subject and a message with words in braces filled in from the inquiry or the tour. Managed under
-- Tour Desk Admin → Settings by Management; archived, never deleted, so a send that used one keeps its
-- trail. Seeded with the standard set by the installer when the table is empty.
CREATE TABLE IF NOT EXISTS `tf_email_template` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(120) NOT NULL,
  `category`    VARCHAR(40)  NOT NULL,
  `subject`     VARCHAR(255) NOT NULL,
  `message`     TEXT NOT NULL,
  `is_default`  TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `archived_at` DATETIME NULL,
  `archived_by` VARCHAR(100) NULL,
  `created_at`  DATETIME NOT NULL,
  `created_by`  VARCHAR(100) NULL,
  `updated_at`  DATETIME NULL,
  `updated_by`  VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_et_cat` (`category`, `archived_at`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 35 ------------------------------------------------------------------
-- Two small things from the first end-to-end walk. A journey leg that starts and ends in
-- the airport area — Katunayake, the planner's gateway town: the airport hotel on the
-- first or last night — and has no figure from past tours or Google is quoted at 0 km and
-- says so (`tf_intake_sector.km_by` = 'same_place'): the airport and garage running the
-- plan already carries covers it, so it no longer blocks the quotation. Katunayake only;
-- a hotel in any other town has its legs priced as before, a figure found for the airport
-- leg is quoted as before, and a figure typed over it still stands. Management's note
-- on the markup (`tf_intake.markup_note`) is kept with the markup, on the Pricing panel
-- only, and no longer written into the inquiry's general note that the Hotel and
-- Transport desks see. The hotel's normal room type (the first with a published rate in
-- the panel's own order) fills in from the master when a hotel is chosen, marked as the
-- master's choice (`tf_intake_day.room_type_auto`) until a person chooses another for the
-- booking; the wording this booking uses for it, when it differs from the master's name,
-- travels with the night (`tf_intake_day.room_type_text`) and the stay
-- (`tf_hotel_job.room_type_text`, changed by the Hotel Desk for that stay alone). The
-- Hotel Master is never changed from here. An enum value and four columns added by the
-- installer; no new table.

-- Schema 36 ------------------------------------------------------------------
-- The Drivers & Guides master (tf-masters-r3): one record per person the company engages
-- — a driver, a guide, or both — with the categories they serve (the Crew Bata master's
-- codes: JSD, ESD, GUIDE_JP …), their languages, how to reach them, licences, their own
-- agreed bata where one is agreed, and the ledger account (6-9-11) accounts keeps for them
-- (never invented here: blank until accounts gives one). Transport Operations picks the
-- person from this master; the crew line carries `person_id`. Archived, never deleted.
CREATE TABLE IF NOT EXISTS `tf_crew_person` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(190) NOT NULL,
  `kind`           ENUM('driver','guide','both') NOT NULL DEFAULT 'driver',
  `role_codes`     VARCHAR(190) NULL,
  `languages`      VARCHAR(190) NULL,
  `phone`          VARCHAR(60)  NULL,
  `mobile`         VARCHAR(60)  NULL,
  `email`          VARCHAR(190) NULL,
  `nic`            VARCHAR(40)  NULL,
  `licence_no`     VARCHAR(60)  NULL,
  `licence_expiry` DATE NULL,
  `guide_licence_no`     VARCHAR(60) NULL,
  `guide_licence_expiry` DATE NULL,
  `supplier_acc`   VARCHAR(50)  NULL,
  `bata`           DECIMAL(10,2) NULL,
  `bata_currency`  CHAR(3) NULL,
  `vehicle_reg`    VARCHAR(40)  NULL,
  `note`           VARCHAR(500) NULL,
  `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `archived_at`    DATETIME NULL,
  `archived_by`    VARCHAR(100) NULL,
  `created_at`     DATETIME NOT NULL,
  `created_by`     VARCHAR(100) NULL,
  `updated_at`     DATETIME NULL,
  `updated_by`     VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cp_name` (`name`),
  KEY `ix_tf_cp_kind` (`kind`, `status`, `archived_at`),
  KEY `ix_tf_cp_acc` (`supplier_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 37 ------------------------------------------------------------------
-- Entrance Ticket Stock Control (tf-tickets-r1): the attractions whose tickets the company
-- buys in advance and holds, each numbered ticket with one status, and the batches received.
-- An attraction is put under control by hand (nothing is assumed from the fee master);
-- "pay at gate" is the other way an entrance is handled. One number is never on two tours:
-- unique per attraction, and allocation locks the rows it takes. Nothing is deleted — a
-- ticket is void with a reason, an attraction goes off the list and keeps its tickets.
CREATE TABLE IF NOT EXISTS `tf_ticket_attraction` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  -- schema 39: an attraction is a place of the Place to Visit / Entrance Fee Master (place_id) —
  -- fee_id is the old Place to Visit record it was put under control by before that
  `fee_id`      INT NULL,
  `place_id`    INT UNSIGNED NULL,
  `fee_code`    VARCHAR(100) NULL,
  `name`        VARCHAR(255) NOT NULL,
  `control`     ENUM('stock','pay_at_gate') NOT NULL DEFAULT 'stock',
  `low_mark`    SMALLINT UNSIGNED NULL,
  `unit_cost`   DECIMAL(10,2) NULL,
  `currency`    CHAR(3) NULL,
  `note`        VARCHAR(500) NULL,
  `active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NULL,
  `created_by`  VARCHAR(100) NULL,
  `updated_at`  DATETIME NULL,
  `updated_by`  VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_tatt_fee` (`fee_id`),
  UNIQUE KEY `ux_tf_tatt_place` (`place_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_ticket_batch` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attraction_id` INT UNSIGNED NOT NULL,
  `received_on`   DATE NOT NULL,
  -- schema 38 (tf-tickets-r2): when the batch was bought / paid for, and what was paid for the whole batch
  `paid_on`       DATE NULL,
  `prefix`        VARCHAR(20) NULL,
  `from_no`       VARCHAR(60) NOT NULL,
  `to_no`         VARCHAR(60) NOT NULL,
  `count`         SMALLINT UNSIGNED NOT NULL,
  `amount_paid`   DECIMAL(12,2) NULL,
  `unit_cost`     DECIMAL(10,2) NULL,
  `currency`      CHAR(3) NULL,
  `ref`           VARCHAR(100) NULL,
  `note`          VARCHAR(400) NULL,
  `created_at`    DATETIME NULL,
  `created_by`    VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_tbatch_att` (`attraction_id`, `received_on`),
  CONSTRAINT `fk_tf_tbatch_att` FOREIGN KEY (`attraction_id`) REFERENCES `tf_ticket_attraction` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_ticket` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attraction_id`  INT UNSIGNED NOT NULL,
  `batch_id`       INT UNSIGNED NULL,
  `ticket_no`      VARCHAR(60) NOT NULL,
  `sort_no`        BIGINT UNSIGNED NULL,
  `status`         ENUM('available','allocated','used','void') NOT NULL DEFAULT 'available',
  `tour_id`        INT UNSIGNED NULL,
  `service_job_id` INT UNSIGNED NULL,
  `allocated_at`   DATETIME NULL,
  `allocated_by`   VARCHAR(100) NULL,
  `used_at`        DATETIME NULL,
  `used_by`        VARCHAR(100) NULL,
  `void_at`        DATETIME NULL,
  `void_by`        VARCHAR(100) NULL,
  `void_note`      VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_ticket_no` (`attraction_id`, `ticket_no`),
  KEY `ix_tf_ticket_pick` (`attraction_id`, `status`, `sort_no`),
  KEY `ix_tf_ticket_tour` (`tour_id`),
  KEY `ix_tf_ticket_job` (`service_job_id`),
  CONSTRAINT `fk_tf_ticket_att` FOREIGN KEY (`attraction_id`) REFERENCES `tf_ticket_attraction` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 39 (tf-entrance-r1) -------------------------------------------------
-- Place to Visit / Entrance Fee Master: entrances and safaris only — the paid entrance
-- places (temples, heritage sites, museums, gardens and other paid places) and the safari
-- parks, with the park entrance a head and the safari jeep per vehicle kept apart. It is the
-- one authority on whether an entrance or a safari is charged and at what: a valid positive
-- rate for the day is charged, a rate marked Free is free, no rate (none, ended, 0.00) is
-- held, and an entrance or safari line not in the master is held — never free. Ordinary
-- sightseeing, meals, guides, trains and other services are outside it. Its places were
-- imported once from an approved list of the old Place to Visit records (tf_place_import says
-- what became of each); after that it stands on its own. Nothing is guessed or deleted.
CREATE TABLE IF NOT EXISTS `tf_place` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`         VARCHAR(60) NULL,
  `name`         VARCHAR(255) NOT NULL,
  `destination`  VARCHAR(120) NULL,
  -- the place as the old list located it (the Google location text), and a map link when one is given
  `location`     VARCHAR(255) NULL,
  `map_link`     VARCHAR(500) NULL,
  `description`  TEXT NULL,
  `image`        VARCHAR(255) NULL,
  `category`     VARCHAR(40) NULL,
  `pay_mode`     ENUM('in_tour','prepaid','direct') NOT NULL DEFAULT 'in_tour',
  -- a safari park: a safari jeep is hired there, charged per vehicle beside the entrance
  `jeep`         TINYINT(1) NOT NULL DEFAULT 0,
  `active`       TINYINT(1) NOT NULL DEFAULT 1,
  -- two places found to be one: this one is kept, out of use, and points at the place it was merged into
  `merged_into`  INT UNSIGNED NULL,
  -- figures the import could not take (they disagree, cover the entrance and jeep together, or look like rupees)
  `review_note`  VARCHAR(1000) NULL,
  `source`       ENUM('import','manual') NOT NULL DEFAULT 'manual',
  `created_at`   DATETIME NULL,
  `created_by`   VARCHAR(100) NULL,
  `updated_at`   DATETIME NULL,
  `updated_by`   VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_place_name` (`name`(80)),
  KEY `ix_tf_place_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One charge of a place for a period: the entrance (adult and child, a head) or the safari
-- jeep (per vehicle). Periods of one charge never overlap; a rate is switched off and kept,
-- never deleted. Free only when marked free_entry; 0.00 is never free.
CREATE TABLE IF NOT EXISTS `tf_place_rate` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id`    INT UNSIGNED NOT NULL,
  `charge`      ENUM('entrance','jeep') NOT NULL DEFAULT 'entrance',
  `valid_from`  DATE NULL,
  `valid_to`    DATE NULL,
  `free_entry`  TINYINT(1) NOT NULL DEFAULT 0,
  `adult_rate`  DECIMAL(12,2) NOT NULL DEFAULT 0,
  -- the entrance only — blank: children pay the adult rate; 0: children free
  `child_rate`  DECIMAL(12,2) NULL,
  `currency`    CHAR(3) NOT NULL DEFAULT 'USD',
  `note`        VARCHAR(255) NULL,
  `source`      ENUM('manual','import') NOT NULL DEFAULT 'manual',
  `source_ref`  VARCHAR(100) NULL,
  `active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NULL,
  `created_by`  VARCHAR(100) NULL,
  `updated_at`  DATETIME NULL,
  `updated_by`  VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_place_rate` (`place_id`, `charge`, `active`, `valid_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- An other name points at one place and nothing else: it never makes a place, and never
-- a charge of its own. alias_key is the name as words, the form a line's title is read in.
CREATE TABLE IF NOT EXISTS `tf_place_alias` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id`    INT UNSIGNED NOT NULL,
  `alias`       VARCHAR(190) NOT NULL,
  `alias_key`   VARCHAR(190) NOT NULL,
  `source`      ENUM('manual','import') NOT NULL DEFAULT 'manual',
  `active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  DATETIME NULL,
  `created_by`  VARCHAR(100) NULL,
  `updated_at`  DATETIME NULL,
  `updated_by`  VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_place_alias_place` (`place_id`),
  KEY `ix_tf_place_alias_key` (`alias_key`, `active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The one-time import, record by record: each old Place to Visit record (imported as a place,
-- merged into one as a duplicate, a safari record split into entrance and jeep, general
-- sightseeing, held for review, a meal / service / activity, or not on the list) and each
-- entrance or jeep fee read in from Excursion Rates. Written once; the old records are untouched.
CREATE TABLE IF NOT EXISTS `tf_place_import` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source`      ENUM('old_list','excursion') NOT NULL,
  `source_id`   VARCHAR(100) NOT NULL,
  `outcome`     ENUM('imported','merged','split','sightseeing','review','service','not_listed') NOT NULL,
  `place_id`    INT UNSIGNED NULL,
  `old_name`    VARCHAR(255) NULL,
  `old_code`    VARCHAR(100) NULL,
  `old_figure`  VARCHAR(60) NULL,
  `reason`      VARCHAR(500) NULL,
  `imported_by` VARCHAR(100) NULL,
  `imported_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_place_import` (`source`, `source_id`),
  KEY `ix_tf_place_import_place` (`place_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Schema 40 (tf-meals-r1): the Meal Master — lunches and dinners outside the hotel for
-- passengers on Half Board packages. One record is one restaurant's meal at one location for
-- one period: the supplier (from the Supplier master, never typed in), the location, the address
-- and the contact, the cuisine and what the meal is, the adult and child rate in its currency,
-- the taxes (included in the rates, or a percentage added on top), Valid From / Valid To, in use
-- or not, remarks. A meal on a booking is the inquiry's own lunch / dinner line, linked here and
-- priced from here; nothing of the route, the passengers or the day is kept twice.
-- =====================================================================
CREATE TABLE IF NOT EXISTS `tf_meal` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`          VARCHAR(40) NOT NULL,
  `meal_type`     ENUM('lunch','dinner') NOT NULL,
  -- the restaurant or meal supplier: a record of the Supplier master (its id and account number), and its name as the meal is sold
  `supplier_id`   INT NULL,
  `supplier_acc`  VARCHAR(50) NULL,
  `restaurant`    VARCHAR(190) NOT NULL,
  `location`      VARCHAR(120) NOT NULL,
  `address`       VARCHAR(300) NULL,
  `contact_name`  VARCHAR(120) NULL,
  `contact_phone` VARCHAR(60) NULL,
  `contact_email` VARCHAR(190) NULL,
  `cuisine`       VARCHAR(80) NULL,
  `description`   VARCHAR(1000) NULL,
  -- the quotation rate, in USD (the quotation is costed in USD only)
  `adult_rate`    DECIMAL(12,2) NOT NULL DEFAULT 0,
  -- blank: a child pays the adult rate; 0: children eat free
  `child_rate`    DECIMAL(12,2) NULL,
  `currency`      CHAR(3) NOT NULL DEFAULT 'USD',
  -- the actual amount the restaurant charges, in LKR: what the booking voucher states after confirmation (no conversion)
  `adult_lkr`     DECIMAL(12,2) NULL,
  `child_lkr`     DECIMAL(12,2) NULL,
  -- the taxes: a percentage added on top of the rates, or the rates already include them
  `tax_pct`       DECIMAL(6,2) NOT NULL DEFAULT 0,
  `tax_included`  TINYINT(1) NOT NULL DEFAULT 0,
  `tax_note`      VARCHAR(190) NULL,
  `valid_from`    DATE NOT NULL,
  `valid_to`      DATE NULL,
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  `remarks`       VARCHAR(1000) NULL,
  `created_at`    DATETIME NULL,
  `created_by`    VARCHAR(100) NULL,
  `updated_at`    DATETIME NULL,
  `updated_by`    VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_meal_code` (`code`),
  KEY `ix_tf_meal_type` (`meal_type`, `active`, `valid_from`),
  KEY `ix_tf_meal_location` (`location`),
  KEY `ix_tf_meal_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The legs of the route a meal serves, in the direction travelled (Colombo → Kandy is not
-- Kandy → Colombo): a lunch on the way is offered on the day the itinerary drives that leg.
CREATE TABLE IF NOT EXISTS `tf_meal_route` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `meal_id`     INT UNSIGNED NOT NULL,
  `from_place`  VARCHAR(120) NOT NULL,
  `to_place`    VARCHAR(120) NOT NULL,
  `sort_order`  SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_tf_meal_route_meal` (`meal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- schema 40: the Meal Master's settings (Master Files → Meal Master), never fixed in code — the meal an HB
-- hotel gives besides breakfast unless a night says otherwise, the usual meal times, and which Supplier
-- master categories a meal supplier may be chosen from (4 Restaurant & Other Suppliers, 1 Hotel).
INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  ('meal_hb_default',    'dinner'),
  ('meal_lunch_time',    '12:30'),
  ('meal_dinner_time',   '19:30'),
  ('meal_supplier_cats', '4,1');

-- schema 40: the lunch place is picked by the system every day of a tour once the route is set — a
-- restaurant on the day's route leg first, else in the day's town, the cheapest of those (a person's pick
-- is never replaced; 0 switches this off) — the itinerary's words for a Meal Master meal, and the numbers
-- of the restaurant booking vouchers (RBV 1001 …).
INSERT IGNORE INTO `tf_meta` (`k`, `v`) VALUES
  ('meal_auto_lunch',     '1'),
  ('itin_meal_lunch',     'Lunch at {place}.'),
  ('itin_meal_dinner',    'Dinner at {place}.'),
  ('meal_voucher_prefix', 'RBV'),
  ('meal_voucher_next',   '1001');

-- schema 40: a restaurant booking voucher per meal of a confirmed tour — sent to the restaurant by email when the tour is
-- confirmed (from the confirming person's mailbox, with the ready-made email of category restaurant_voucher), to book the
-- meal: the day and time, the adults and children, and the amount in LKR from the Meal Master's actual rates (no
-- conversion). Numbered from its own series (tf_meta meal_voucher_prefix / meal_voucher_next); the email sent is kept on
-- it. Kept for good: a wrong one is cancelled with a note and a fresh one issued, never edited or removed.
CREATE TABLE IF NOT EXISTS `tf_meal_voucher` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`      INT UNSIGNED NOT NULL,
  `job_id`       INT UNSIGNED NOT NULL,
  `service_id`   INT UNSIGNED NULL,
  `meal_id`      INT UNSIGNED NOT NULL,
  `voucher_no`   VARCHAR(30)  NOT NULL,
  `supplier_id`  INT NULL,
  `supplier_acc` VARCHAR(50)  NULL,
  `restaurant`   VARCHAR(190) NOT NULL,
  `location`     VARCHAR(120) NULL,
  `meal_type`    ENUM('lunch','dinner') NOT NULL,
  `on_date`      DATE NULL,
  `meal_time`    VARCHAR(5)   NULL,
  `adults`       SMALLINT NULL,
  `children`     SMALLINT NULL,
  -- the Meal Master's actual rates in LKR as they stood when the voucher was issued, the taxes and the amount
  `adult_lkr`    DECIMAL(12,2) NOT NULL,
  `child_lkr`    DECIMAL(12,2) NULL,
  `tax_pct`      DECIMAL(6,2) NULL,
  `tax_included` TINYINT(1) NOT NULL DEFAULT 0,
  `tax_lkr`      DECIMAL(14,2) NULL,
  `amount_lkr`   DECIMAL(14,2) NOT NULL,
  `special_note` VARCHAR(500) NULL,
  -- where it went: the restaurant's booking email and contact, and the email as sent (or why it was not)
  `to_email`     VARCHAR(190) NULL,
  `contact`      VARCHAR(190) NULL,
  `mail_status`  ENUM('not_sent','sent','failed','other') NOT NULL DEFAULT 'not_sent',
  `mail_error`   VARCHAR(500) NULL,
  `mail_cc`      VARCHAR(500) NULL,
  `mail_subject` VARCHAR(255) NULL,
  `mail_body`    MEDIUMTEXT NULL,
  `sent_at`      DATETIME NULL,
  `sent_by`      VARCHAR(100) NULL,
  `state`        ENUM('issued','cancelled') NOT NULL DEFAULT 'issued',
  `note`         VARCHAR(400) NULL,
  `issued_at`    DATETIME NOT NULL,
  `issued_by`    VARCHAR(100) NULL,
  `cancelled_at` DATETIME NULL,
  `cancelled_by` VARCHAR(100) NULL,
  `cancel_note`  VARCHAR(400) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_mvoucher_no` (`voucher_no`),
  KEY `ix_tf_mvoucher_job` (`job_id`),
  KEY `ix_tf_mvoucher_tour` (`tour_id`),
  KEY `ix_tf_mvoucher_service` (`service_id`),
  KEY `ix_tf_mvoucher_meal` (`meal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 44 (tf-crew-master-r1): the Driver & Guide Master — the Drivers & Guides master
-- (tf_crew_person) grown into one record per person with a category (JSD, JSCG, JSNTG),
-- the personal details, licences with copies, the vehicle normally supplied (with its
-- documents and photographs), the LKR-per-km rate with its history, and the bank details
-- (behind their own privilege). The tables below are new; the columns on tf_crew_person
-- and tf_transport_crew are added by the installer (upgradeColumns). Nothing is deleted.

-- The vehicle a driver (JSD / JSCG) normally supplies: one current vehicle per person, the
-- earlier ones kept with is_current = 0.
CREATE TABLE IF NOT EXISTS `tf_crew_vehicle` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id`        INT UNSIGNED NOT NULL,
  `reg_no`           VARCHAR(40)  NULL,
  `make`             VARCHAR(80)  NULL,
  `model`            VARCHAR(80)  NULL,
  `year`             SMALLINT UNSIGNED NULL,
  `vehicle_type`     ENUM('car','van','minibus','bus') NULL,
  `roof_type`        ENUM('high','flat') NULL,
  `rate_class`       VARCHAR(20) NULL,
  `ownership`        ENUM('own','rented','other_supplier') NULL,
  `seats`            SMALLINT UNSIGNED NULL,
  `max_pax_luggage`  SMALLINT UNSIGNED NULL,
  `insurance_expiry` DATE NULL,
  `insurance_file`   VARCHAR(255) NULL,
  `licence_expiry`   DATE NULL,
  `licence_file`     VARCHAR(255) NULL,
  `note`             VARCHAR(400) NULL,
  `is_current`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`       DATETIME NOT NULL,
  `created_by`       VARCHAR(100) NULL,
  `updated_at`       DATETIME NULL,
  `updated_by`       VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cv_person` (`person_id`, `is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Several photographs per vehicle: exterior, interior, other.
CREATE TABLE IF NOT EXISTS `tf_crew_vehicle_photo` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `file`       VARCHAR(255) NOT NULL,
  `view`       ENUM('exterior','interior','other') NOT NULL DEFAULT 'exterior',
  `caption`    VARCHAR(120) NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `added_at`   DATETIME NOT NULL,
  `added_by`   VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cvp_vehicle` (`vehicle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The SPECIAL rate per kilometre agreed with a driver / vehicle arrangement, LKR only, each
-- with the date it takes effect — it overrides, for that person only, the GENERAL rate of
-- their vehicle's class on the Transport Rates master (tf_transport_rate, by rate_class
-- above). A new rate is a new row: the old one stays, and a tour that took its rate keeps
-- the figure copied onto its crew line.
CREATE TABLE IF NOT EXISTS `tf_crew_rate` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id`      INT UNSIGNED NOT NULL,
  `rate_km`        DECIMAL(10,2) NOT NULL,
  `currency`       CHAR(3) NOT NULL DEFAULT 'LKR',
  `effective_from` DATE NOT NULL,
  `note`           VARCHAR(255) NULL,
  `set_at`         DATETIME NOT NULL,
  `set_by`         VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cr_person` (`person_id`, `effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The agreed bata, with its history: for a JSD / JSCG the agreed bata per day (beside the
-- rate per kilometre); for a JSNTG the agreed guide rate per day (`bata`) and the bata /
-- overnight allowance per night (`overnight`). A new figure from a date is a new row; the
-- earlier ones stay, and a tour copies the figures in force the day the person is engaged.
-- tf_crew_person.bata / overnight_bata mirror the row in force today.
CREATE TABLE IF NOT EXISTS `tf_crew_bata` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id`      INT UNSIGNED NOT NULL,
  `bata`           DECIMAL(10,2) NULL,
  `overnight`      DECIMAL(10,2) NULL,
  `currency`       CHAR(3) NOT NULL DEFAULT 'LKR',
  `effective_from` DATE NOT NULL,
  `note`           VARCHAR(255) NULL,
  `set_at`         DATETIME NOT NULL,
  `set_by`         VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_cb_person` (`person_id`, `effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The STANDARD figures paid to the crew, each with the date it took effect (Shantha, 5 Oct 2026):
--   bata        the standard bata per day for a JSD / JSCG            LKR 4,000
--   guide_fee   the standard guide fee per day for a JSNTG            LKR 7,500
--   overnight   the overnight allowance per night for a JSNTG         no figure until it is confirmed
-- A person's own agreed figure (tf_crew_bata) overrides the standard for that person only; with none,
-- the standard in force the day they are engaged is copied onto the tour. A new standard from a date
-- is a new row — the earlier rows stay, and no tour already allocated moves.
CREATE TABLE IF NOT EXISTS `tf_crew_standard` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item`           VARCHAR(20) NOT NULL,
  `amount`         DECIMAL(10,2) NULL,
  `currency`       CHAR(3) NOT NULL DEFAULT 'LKR',
  `effective_from` DATE NOT NULL,
  `note`           VARCHAR(255) NULL,
  `set_at`         DATETIME NOT NULL,
  `set_by`         VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_cs_item_from` (`item`, `effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The standards as instructed on 5 Oct 2026, seeded once (an item already carrying a row is left alone;
-- a later figure is added from the master with its own date). The overnight allowance is deliberately
-- not seeded: no figure is invented, and nothing is added to a settlement until it is confirmed.
INSERT INTO `tf_crew_standard` (`item`, `amount`, `currency`, `effective_from`, `note`, `set_at`, `set_by`)
  SELECT 'bata', 4000.00, 'LKR', '2026-10-05', 'Standard bata per day (instruction of 5 Oct 2026)', NOW(), 'installer'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tf_crew_standard` WHERE `item` = 'bata');
INSERT INTO `tf_crew_standard` (`item`, `amount`, `currency`, `effective_from`, `note`, `set_at`, `set_by`)
  SELECT 'guide_fee', 7500.00, 'LKR', '2026-10-05', 'Standard guide fee per day (instruction of 5 Oct 2026)', NOW(), 'installer'
  FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tf_crew_standard` WHERE `item` = 'guide_fee');

-- The one quotation role the master needs beside JSD and GUIDE_JP: the chauffeur guide (drives
-- and holds the guide licence — a driver line on a quotation, no separate guide). Its costing
-- bata matches the JSD's on the Crew Bata master the day the role is created (Shantha, 5 Oct
-- 2026: "for JSCG costing use 5,000, matching JSD" — JSD holds 5,000 on live); from then on the
-- Crew Bata master sets it like any role. The Japanese guide line stays GUIDE_JP — that line is
-- the JSNTG category on the Driver & Guide Master — so no quotation changes and the default
-- Japanese crew (JSD + GUIDE_JP, by sort order) is as before. A JSCG row already there is left alone.
INSERT IGNORE INTO `tf_crew_role` (`code`, `name`, `kind`, `language`, `bata`, `currency`, `sort_order`)
  SELECT 'JSCG', 'Japanese speaking chauffeur guide', 'driver', 'Japanese',
         (SELECT `bata` FROM `tf_crew_role` WHERE `code` = 'JSD' LIMIT 1),
         COALESCE((SELECT `currency` FROM `tf_crew_role` WHERE `code` = 'JSD' LIMIT 1), 'LKR'), 25
  FROM DUAL;

-- ============================================================================
-- Schema 45 (tf-comms-r1, 27 Oct 2026, Shantha): Communications & History — one continuous record per inquiry /
-- tour: every e-mail (in and out), WhatsApp or other message, phone note, meeting note and handover note, with its
-- attachments, the staff member who recorded it, what it refers to (quotation version, hotel card, passengers …),
-- and the follow-ups it leaves open. System-sent mails (quotations, hotel letters) and the inquiry's own e-mail are
-- captured automatically; everything else is recorded by hand. Nothing here ever changes the itinerary by itself.
-- ============================================================================
CREATE TABLE IF NOT EXISTS `tf_comm` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`     INT UNSIGNED NOT NULL,
  `tour_id`       INT UNSIGNED NULL,
  `direction`     ENUM('in','out','internal') NOT NULL DEFAULT 'in',
  `channel`       ENUM('email','whatsapp','sms','phone','meeting','note','handover','system') NOT NULL DEFAULT 'email',
  `tag`           ENUM('','agreement','approval','change','question','confirmation') NOT NULL DEFAULT '',
  `occurred_at`   DATETIME NOT NULL,
  `from_addr`     VARCHAR(400) NULL,
  `to_addr`       VARCHAR(400) NULL,
  `cc_addr`       VARCHAR(500) NULL,
  `bcc_addr`      VARCHAR(500) NULL,
  `subject`       VARCHAR(400) NULL,
  `body`          MEDIUMTEXT NULL,
  `source`        ENUM('system','manual','import') NOT NULL DEFAULT 'manual',
  `ref_table`     VARCHAR(40) NULL,
  `ref_id`        INT UNSIGNED NULL,
  `links_json`    TEXT NULL,
  `send_state`    ENUM('','sent','failed','not_sent') NOT NULL DEFAULT '',
  `send_error`    VARCHAR(500) NULL,
  `sent_from`     VARCHAR(255) NULL,
  `in_reply_to`   INT UNSIGNED NULL,
  `thread_key`    VARCHAR(120) NULL,
  `message_id`    VARCHAR(255) NULL,
  `version`       INT UNSIGNED NOT NULL DEFAULT 1,
  `prev_json`     MEDIUMTEXT NULL,
  `recorded_by`   VARCHAR(100) NULL,
  `recorded_by_id` INT NULL,
  `recorded_name` VARCHAR(190) NULL,
  `recorded_at`   DATETIME NOT NULL,
  `edited_at`     DATETIME NULL,
  `edited_by`     VARCHAR(100) NULL,
  `removed_at`    DATETIME NULL,
  `removed_by`    VARCHAR(100) NULL,
  `remove_note`   VARCHAR(500) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tf_comm_intake` (`intake_id`, `occurred_at`),
  KEY `idx_tf_comm_tour` (`tour_id`, `occurred_at`),
  KEY `idx_tf_comm_ref` (`ref_table`, `ref_id`),
  KEY `idx_tf_comm_msgid` (`message_id`),
  CONSTRAINT `fk_tf_comm_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_comm_attachment` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `comm_id`       INT UNSIGNED NOT NULL,
  `intake_id`     INT UNSIGNED NOT NULL,
  `file`          VARCHAR(190) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime`          VARCHAR(120) NULL,
  `bytes`         INT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_at`   DATETIME NOT NULL,
  `uploaded_by`   VARCHAR(100) NULL,
  `removed_at`    DATETIME NULL,
  `removed_by`    VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tf_comm_att_comm` (`comm_id`),
  KEY `idx_tf_comm_att_intake` (`intake_id`),
  CONSTRAINT `fk_tf_comm_att_comm` FOREIGN KEY (`comm_id`) REFERENCES `tf_comm` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 47 (tf-comms-r3): e-mails collected from the TourFlow mailbox — every message once (UID + Message-ID), filed on a
-- record only by a certain reference (In-Reply-To / References, the [AVH:…] tag, the AVH-REF conversation key), else queued.
CREATE TABLE IF NOT EXISTS `tf_mail_in` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mailbox`         VARCHAR(190) NOT NULL,
  `uid`             INT UNSIGNED NOT NULL,
  `uidvalidity`     VARCHAR(40) NOT NULL DEFAULT '',
  `message_id`      VARCHAR(255) NULL,
  `in_reply_to`     VARCHAR(998) NULL,
  `references_hdr`  TEXT NULL,
  `from_addr`       VARCHAR(400) NULL,
  `to_addr`         VARCHAR(400) NULL,
  `cc_addr`         VARCHAR(500) NULL,
  `reply_to`        VARCHAR(400) NULL,
  `subject`         VARCHAR(400) NULL,
  `body_text`       MEDIUMTEXT NULL,
  `body_html`       MEDIUMTEXT NULL,
  `received_at`     DATETIME NOT NULL,
  `size_bytes`      INT UNSIGNED NOT NULL DEFAULT 0,
  `status`          ENUM('new','matched','unmatched','discarded','skipped') NOT NULL DEFAULT 'new',
  `intake_id`       INT UNSIGNED NULL,
  `comm_id`         INT UNSIGNED NULL,
  `draft_intake_id` INT UNSIGNED NULL,
  `match_reason`    VARCHAR(255) NULL,
  `matched_by`      VARCHAR(100) NULL,
  `matched_at`      DATETIME NULL,
  `note`            VARCHAR(500) NULL,
  `imported_at`     DATETIME NOT NULL,
  `imported_by`     VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_mail_in_uid` (`mailbox`, `uidvalidity`, `uid`),
  KEY `idx_tf_mail_in_msgid` (`message_id`),
  KEY `idx_tf_mail_in_status` (`status`, `received_at`),
  KEY `idx_tf_mail_in_intake` (`intake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_mail_in_attachment` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `mail_id`       INT UNSIGNED NOT NULL,
  `file`          VARCHAR(120) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime`          VARCHAR(120) NULL,
  `bytes`         INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_tf_mail_in_att` (`mail_id`),
  CONSTRAINT `fk_tf_mail_in_att` FOREIGN KEY (`mail_id`) REFERENCES `tf_mail_in` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_comm_followup` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`     INT UNSIGNED NOT NULL,
  `tour_id`       INT UNSIGNED NULL,
  `comm_id`       INT UNSIGNED NULL,
  `title`         VARCHAR(255) NOT NULL,
  `detail`        VARCHAR(1000) NULL,
  `owner_id`      INT NULL,
  `owner_name`    VARCHAR(190) NULL,
  `due_on`        DATE NULL,
  `status`        ENUM('open','done','dropped') NOT NULL DEFAULT 'open',
  `done_at`       DATETIME NULL,
  `done_by`       VARCHAR(100) NULL,
  `done_note`     VARCHAR(500) NULL,
  `created_at`    DATETIME NOT NULL,
  `created_by`    VARCHAR(100) NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tf_comm_fu_intake` (`intake_id`, `status`, `due_on`),
  KEY `idx_tf_comm_fu_owner` (`owner_id`, `status`),
  CONSTRAINT `fk_tf_comm_fu_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 49 (tf-child-r1): the Child Fare Review — one row per item a person decided (applicable / not applicable), with the
-- fingerprint of what it was decided on (a change of ages, party, itinerary, hotel or rate makes it "review again")
CREATE TABLE IF NOT EXISTS `tf_child_review` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`     INT UNSIGNED NOT NULL,
  `item_key`      VARCHAR(60) NOT NULL,
  `kind`          VARCHAR(20) NOT NULL,
  `title`         VARCHAR(255) NULL,
  `day_from`      SMALLINT UNSIGNED NULL,
  `day_to`        SMALLINT UNSIGNED NULL,
  `decision`      ENUM('applicable','not_applicable') NOT NULL,
  `reason_code`   VARCHAR(20) NULL,
  `reason`        VARCHAR(255) NULL,
  `child_total`   DECIMAL(10,2) NULL,
  `per_night`     DECIMAL(10,2) NULL,
  `manual`        TINYINT(1) NOT NULL DEFAULT 0,
  `suggestion`    VARCHAR(20) NULL,
  `fingerprint`   CHAR(40) NOT NULL,
  `calc_json`     TEXT NULL,
  `decided_by`    VARCHAR(100) NULL,
  `decided_by_id` INT NULL,
  `decided_name`  VARCHAR(190) NULL,
  `decided_at`    DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_child_review` (`intake_id`, `item_key`),
  CONSTRAINT `fk_tf_child_review_intake` FOREIGN KEY (`intake_id`) REFERENCES `tf_intake` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 50 (tf-safari-r1): who runs the jeeps and who sells the entrance tickets at a place — one row each (role), linked to the
-- Supplier master where there is a record; blank details are read from that supplier record. The account number is never sent
-- back to the screen in full (the last four figures only).
CREATE TABLE IF NOT EXISTS `tf_place_party` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `place_id`             INT UNSIGNED NOT NULL,
  `role`                 ENUM('jeep_operator','ticket_supplier') NOT NULL,
  `supplier_id`          INT NULL,
  `business_name`        VARCHAR(190) NULL,
  `contact_person`       VARCHAR(120) NULL,
  `phone`                VARCHAR(60) NULL,
  `whatsapp`             VARCHAR(60) NULL,
  `email`                VARCHAR(190) NULL,
  `address`              VARCHAR(255) NULL,
  `booking_instructions` TEXT NULL,
  `payment_terms`        VARCHAR(255) NULL,
  `account_holder`       VARCHAR(190) NULL,
  `account_number`       VARCHAR(60) NULL,
  `bank`                 VARCHAR(120) NULL,
  `branch`               VARCHAR(120) NULL,
  `bank_code`            VARCHAR(20) NULL,
  `branch_code`          VARCHAR(20) NULL,
  `swift_code`           VARCHAR(20) NULL,
  `updated_at`           DATETIME NULL,
  `updated_by`           VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_place_party` (`place_id`, `role`),
  CONSTRAINT `fk_tf_place_party_place` FOREIGN KEY (`place_id`) REFERENCES `tf_place` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 51 (tf-safari-r2): the safari payment slip — one per payee of a safari line (the jeep operator, and the ticket supplier
-- unless they are paid together), with its own reference number; when its figures change the reference stays and the revision goes up
CREATE TABLE IF NOT EXISTS `tf_safari_slip` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_no`       VARCHAR(60) NOT NULL,
  `intake_id`    INT UNSIGNED NOT NULL,
  `service_id`   INT UNSIGNED NOT NULL,
  `place_id`     INT UNSIGNED NOT NULL,
  `payee_role`   ENUM('jeep_operator','ticket_supplier') NOT NULL,
  `supplier_id`  INT NULL,
  `payee_name`   VARCHAR(190) NULL,
  `tour_no`      VARCHAR(40) NULL,
  `file_no`      VARCHAR(40) NULL,
  `safari_date`  DATE NULL,
  `total`        DECIMAL(12,2) NOT NULL DEFAULT 0,
  `currency`     VARCHAR(10) NULL,
  `figures_json` TEXT NULL,
  `revision`     INT NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL,
  `created_by`   VARCHAR(100) NULL,
  `updated_at`   DATETIME NULL,
  `updated_by`   VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tf_safari_slip_ref` (`ref_no`),
  UNIQUE KEY `uq_tf_safari_slip_line` (`service_id`, `payee_role`),
  KEY `ix_tf_safari_slip_intake` (`intake_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 52 (tf-stage2): every entry and every correction of the daily USD rate — the old and the new figure, who, when, why,
-- and where it was entered (the Home box or the Daily USD Rate page). The rate itself stays in daily_usd_rate.
CREATE TABLE IF NOT EXISTS `tf_usd_rate_log` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rate_date` DATE NOT NULL,
  `old_rate`  DECIMAL(10,2) NULL,
  `new_rate`  DECIMAL(10,2) NOT NULL,
  `kind`      ENUM('entry','correction') NOT NULL,
  `reason`    VARCHAR(255) NULL,
  `user`      VARCHAR(100) NOT NULL,
  `user_name` VARCHAR(190) NULL,
  `source`    VARCHAR(40) NOT NULL,
  `prev_user` VARCHAR(100) NULL,
  `prev_at`   DATETIME NULL,
  `at`        DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_usd_rate_log_date` (`rate_date`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 53 (tf-stage4): every recalculation or review of an inquiry's saved figures — what moved (old and new, item by
-- item), the cost and price before and after, the USD rate before and after, who, when, why; and the baseline each open
-- inquiry started from. Issued quotation versions keep their own frozen costing and are never touched.
CREATE TABLE IF NOT EXISTS `tf_recalc_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `intake_id`   INT UNSIGNED NOT NULL,
  `action`      ENUM('baseline','recalculated','kept') NOT NULL,
  `items_json`  MEDIUMTEXT NULL,
  `cost_before` DECIMAL(12,2) NULL,
  `cost_after`  DECIMAL(12,2) NULL,
  `sell_before` DECIMAL(12,2) NULL,
  `sell_after`  DECIMAL(12,2) NULL,
  `usd_before`  DECIMAL(12,4) NULL,
  `usd_after`   DECIMAL(12,4) NULL,
  `note`        VARCHAR(255) NULL,
  `user`        VARCHAR(100) NOT NULL,
  `user_name`   VARCHAR(190) NULL,
  `at`          DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_recalc_log_intake` (`intake_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 56 (tf-stage8): Blocks & Investigations. A block on one exact record (a supplier / driver / guide, a hotel, a customer
-- or agent, or one bank account identified by its number and bank), its kind, reason, reference and restricted notes, who
-- applied and lifted it and when; and its log (applied, refused on a route, note, lifted). Nothing else is altered.
CREATE TABLE IF NOT EXISTS `tf_block` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_type`    ENUM('supplier','hotel','customer','account') NOT NULL,
  `target_key`     VARCHAR(50) NOT NULL,
  `target_label`   VARCHAR(190) NOT NULL,
  `sup_acc`        VARCHAR(50) NULL,
  `acct_no_norm`   VARCHAR(64) NULL,
  `bank_norm`      VARCHAR(120) NULL,
  `kind`           ENUM('work','payments','both') NOT NULL,
  `reason`         VARCHAR(500) NOT NULL,
  `reference`      VARCHAR(120) NULL,
  `notes`          TEXT NULL,
  `status`         ENUM('active','lifted') NOT NULL DEFAULT 'active',
  `set_by`         VARCHAR(100) NOT NULL,
  `set_by_name`    VARCHAR(190) NULL,
  `set_at`         DATETIME NOT NULL,
  `lifted_by`      VARCHAR(100) NULL,
  `lifted_by_name` VARCHAR(190) NULL,
  `lifted_at`      DATETIME NULL,
  `lift_reason`    VARCHAR(500) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_block_target` (`status`, `target_type`, `target_key`),
  KEY `ix_tf_block_sup` (`status`, `sup_acc`),
  KEY `ix_tf_block_acct` (`status`, `acct_no_norm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_block_log` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `block_id`  INT UNSIGNED NOT NULL,
  `action`    ENUM('applied','refused','note','lifted') NOT NULL,
  `user`      VARCHAR(100) NOT NULL,
  `user_name` VARCHAR(190) NULL,
  `at`        DATETIME NOT NULL,
  `detail`    VARCHAR(1000) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_block_log_block` (`block_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schema 57 (tf-stage10): Staff Assignment. An account the Super Admin brings onto the panel (its department label grants nothing),
-- its individual Open / Blocked rows keyed by menu_list (absent = Blocked), and the append-only log of every change and refusal.
CREATE TABLE IF NOT EXISTS `tf_staff` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT NOT NULL,
  `on_panel`         TINYINT(1) NOT NULL DEFAULT 1,
  `department`       VARCHAR(80) NULL,
  `responsibilities` VARCHAR(500) NULL,
  `status`           ENUM('active','suspended','deactivated') NOT NULL DEFAULT 'active',
  `status_reason`    VARCHAR(500) NULL,
  `status_by`        VARCHAR(100) NULL,
  `status_at`        DATETIME NULL,
  `created_by`       VARCHAR(100) NOT NULL,
  `created_at`       DATETIME NOT NULL,
  `updated_by`       VARCHAR(100) NULL,
  `updated_at`       DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_staff_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_staff_permission` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT UNSIGNED NOT NULL,
  `menu_id`  INT NOT NULL,
  `state`    ENUM('open','blocked') NOT NULL DEFAULT 'blocked',
  `reason`   VARCHAR(500) NOT NULL,
  `set_by`   VARCHAR(100) NOT NULL,
  `set_at`   DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_tf_staff_perm` (`staff_id`, `menu_id`),
  KEY `ix_tf_staff_perm_open` (`staff_id`, `state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tf_staff_log` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id`   INT UNSIGNED NULL,
  `user_id`    INT NULL,
  `action`     VARCHAR(30) NOT NULL COMMENT 'added_to_panel | removed_from_panel | label | opened | blocked | refused | status',
  `menu_id`    INT NULL,
  `from_state` VARCHAR(40) NULL,
  `to_state`   VARCHAR(40) NULL,
  `reason`     VARCHAR(500) NOT NULL,
  `detail`     VARCHAR(1000) NULL,
  `by_user`    VARCHAR(100) NOT NULL,
  `by_name`    VARCHAR(190) NULL,
  `at`         DATETIME NOT NULL,
  `ip`         VARCHAR(45) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_tf_staff_log_staff` (`staff_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
