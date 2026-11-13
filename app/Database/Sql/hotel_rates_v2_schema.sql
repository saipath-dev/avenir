-- ============================================================================
-- Hotel Rates (new) — schema version 7
-- Avenir Holidays management system (CodeIgniter 4, MySQL 8 / MariaDB 10.11)
--
-- Designed from the 2026/27 rate sheets received from Thema Collection, MOI/DBI,
-- Aitken Spence, Fort Resorts, Blue Water, Radisson, Amaya/Hayleys, Hilton Yala,
-- Sheraton Kosgoda, Morven, Casamara, Centauria, Elephant Reach, Tamarind Tree,
-- Simpsons Forest, Grand Serendib and Deewara. Every structure those sheets use
-- has a home here; nothing has to be squeezed into SGL/DBL/TPL x 4 meal plans.
--
-- Vocabulary
--   hotel          one real property (many supplier codes may map to it)
--   room type      per hotel; "base" rooms carry rates, "supplement" rooms are
--                  priced as base + supplement (Sheraton, Radisson, Amaya ...)
--   contract       one RATE PLAN: a contract, a tactical offer, an early-bird
--                  offer, a market special (SPO) or an addendum. Several plans
--                  can be valid for the same hotel and dates; markets, booking
--                  window, tier and priority decide which one a quotation uses.
--   season         a named period of a plan made of one or MORE date ranges
--                  ("01 Nov-20 Dec & 01-30 Apr" is one season with two ranges)
--   rate           season x room type x meal plan with SGL/DBL/TPL/QUAD/UNIT
--   meal delta     "BB reduction USD 5 on HB" style rules that derive plans
--   child rate     age band x with/without bed x meal plan -> amount or %
--   supplement     gala / peak / festival / extra adult / early-late check
--   guide, FOC, offers, cancellation, payment, restrictions, extras: their own rows
--
-- All tables are InnoDB and prefixed hr2_. The legacy tables (`hotel_rate`,
-- `room_type`, `supplier`) are NOT modified; `hotel_rate` is refreshed from
-- here by LegacyProjector so every old screen keeps working.
--
-- Safe to re-run: every statement uses IF NOT EXISTS / INSERT IGNORE, so the
-- same file installs a fresh copy and upgrades schema 3 in place (schema 4
-- adds the settings rows in hr2_meta and the hr2_market_country table). The
-- install command detects the schema-version-1 layout (hr2_rate_period) and
-- rebuilds it.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `hr2_meta` (
  `k` VARCHAR(40) NOT NULL,
  `v` VARCHAR(400) NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings (see App\Libraries\HotelRates\Settings::DEFINITIONS; edited from the panel).
INSERT IGNORE INTO `hr2_meta` (`k`, `v`) VALUES
  ('schema_version', '4'),
  ('default_market', 'JP'), ('default_currency', 'USD'),
  ('fit_max_rooms', '4'), ('group_min_rooms', '5'),
  ('gala_dates', '12-24,12-31'), ('gala_child_pct', '50'),
  ('tiny_rate', '10'), ('huge_rate', '3000'), ('change_warning_pct', '40'), ('expiry_warning_days', '90'), ('child_max_age', '18'),
  ('coverage_days', '365'), ('check_days', '120'),
  ('margin_alert_pct', '40'), ('work_scan_minutes', '10'),
  ('legacy_booking_basis', 'today'), ('legacy_screen', 'all');

CREATE TABLE IF NOT EXISTS `hr2_location` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`    VARCHAR(120) NOT NULL,
  `region`  VARCHAR(60)  NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_location_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_location_alias` (
  `alias`        VARCHAR(120) NOT NULL,
  `location_id`  INT UNSIGNED NOT NULL,
  PRIMARY KEY (`alias`),
  KEY `idx_hr2_location_alias_loc` (`location_id`),
  CONSTRAINT `fk_hr2_location_alias_loc` FOREIGN KEY (`location_id`) REFERENCES `hr2_location` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Source markets the hotels price for. A plan lists the markets it includes
-- (none = all markets) and may exclude some ("Asia excluding China / Japan").
CREATE TABLE IF NOT EXISTS `hr2_market` (
  `code`        VARCHAR(10) NOT NULL,
  `name`        VARCHAR(80) NOT NULL,
  `sort_order`  SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `hr2_market` (`code`, `name`, `sort_order`) VALUES
  ('ALL', 'All markets', 0), ('JP', 'Japan', 10), ('CN', 'China', 20), ('FE', 'Far East (Korea, Taiwan, HK, SE Asia)', 30),
  ('IN', 'India', 40), ('SA', 'South Asia (other)', 45), ('ME', 'Middle East & Gulf', 50), ('EU', 'Europe', 60), ('UK', 'United Kingdom', 61),
  ('NL', 'Netherlands', 62), ('ES', 'Spain', 63), ('EE', 'Eastern Europe', 64), ('RU', 'Russia & CIS', 65), ('AU', 'Australia & NZ', 70),
  ('US', 'USA & Canada', 80), ('DOM', 'Domestic (Sri Lanka)', 90), ('OTH', 'Other', 99);

-- Which market a quotation belongs to, from the country the quotation form
-- stores (consignee.country = country.iso, filled from the agent record).
-- Seeded from the `country` table by the installer; edited in Settings.
CREATE TABLE IF NOT EXISTS `hr2_market_country` (
  `country_iso`   CHAR(2) NOT NULL,
  `country_name`  VARCHAR(80) NOT NULL,
  `market_code`   VARCHAR(10) NOT NULL,
  `note`          VARCHAR(200) NULL,
  `updated_by`    VARCHAR(100) NULL,
  `updated_at`    DATETIME NULL,
  PRIMARY KEY (`country_iso`),
  KEY `idx_hr2_market_country_market` (`market_code`),
  CONSTRAINT `fk_hr2_market_country_market` FOREIGN KEY (`market_code`) REFERENCES `hr2_market` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_hotel` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`                VARCHAR(240) NOT NULL,
  `primary_sup_acc_no`  VARCHAR(50)  NOT NULL,
  `location_id`         INT UNSIGNED NULL,
  `chain`               VARCHAR(120) NULL,
  `star_category`       VARCHAR(20)  NULL,
  `address`             VARCHAR(255) NULL,
  `website`             VARCHAR(160) NULL,
  `reservations_email`  VARCHAR(255) NULL,
  `reservations_phone`  VARCHAR(80)  NULL,
  `sales_contact`       VARCHAR(160) NULL,
  `accounts_email`      VARCHAR(255) NULL,
  `phone`               VARCHAR(60)  NULL,
  `check_in_time`       VARCHAR(8)   NULL,
  `check_out_time`      VARCHAR(8)   NULL,
  `currency`            CHAR(3)      NOT NULL DEFAULT 'USD',
  `tax_treatment`       ENUM('inclusive','exclusive','unknown') NOT NULL DEFAULT 'unknown',
  `tax_note`            VARCHAR(200) NULL,
  `bank_details`        TEXT NULL,
  `status`              ENUM('active','inactive','merged') NOT NULL DEFAULT 'active',
  `merged_into_id`      INT UNSIGNED NULL,
  `notes`               TEXT NULL,
  `created_at`          DATETIME NULL,
  `created_by`          VARCHAR(100) NULL,
  `updated_at`          DATETIME NULL,
  `updated_by`          VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_hotel_primary_acc` (`primary_sup_acc_no`),
  KEY `idx_hr2_hotel_name` (`name`),
  KEY `idx_hr2_hotel_location` (`location_id`),
  KEY `idx_hr2_hotel_chain` (`chain`),
  KEY `idx_hr2_hotel_status` (`status`),
  CONSTRAINT `fk_hr2_hotel_location` FOREIGN KEY (`location_id`) REFERENCES `hr2_location` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_hotel_account` (
  `sup_acc_no`   VARCHAR(50)  NOT NULL,
  `hotel_id`     INT UNSIGNED NOT NULL,
  `legacy_name`  VARCHAR(240) NULL,
  `is_primary`   TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`sup_acc_no`),
  KEY `idx_hr2_hotel_account_hotel` (`hotel_id`),
  CONSTRAINT `fk_hr2_hotel_account_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hr2_hotel` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room inventory per hotel, as the fact sheets describe it.
-- priced_as = 'supplement' rooms have no rate rows of their own: their price is
-- base_room_type's rate + hr2_room_supplement (Sheraton "Higher Room Category
-- Supplements", Radisson "Room supplements", Amaya, Aitken Spence, Fort ...).
CREATE TABLE IF NOT EXISTS `hr2_room_type` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id`              INT UNSIGNED NOT NULL,
  `name`                  VARCHAR(160) NOT NULL,
  `code`                  VARCHAR(20)  NULL,
  `legacy_room_type_id`   INT NULL,
  `priced_as`             ENUM('base','supplement') NOT NULL DEFAULT 'base',
  `base_room_type_id`     INT UNSIGNED NULL,
  `room_count`            SMALLINT UNSIGNED NULL,
  `size_sqm`              DECIMAL(6,1) NULL,
  `bed_config`            VARCHAR(120) NULL,
  `view`                  VARCHAR(60)  NULL,
  `max_adults`            TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `max_children`          TINYINT UNSIGNED NOT NULL DEFAULT 2,
  `max_occupancy`         TINYINT UNSIGNED NULL,
  `extra_bed_allowed`     TINYINT(1) NOT NULL DEFAULT 1,
  `unit_pax_min`          TINYINT UNSIGNED NULL,
  `unit_pax_max`          TINYINT UNSIGNED NULL,
  `description`           VARCHAR(400) NULL,
  `sort_order`            SMALLINT NOT NULL DEFAULT 0,
  `status`                ENUM('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_room_type_hotel_name` (`hotel_id`, `name`),
  KEY `idx_hr2_room_type_legacy` (`legacy_room_type_id`),
  KEY `idx_hr2_room_type_base` (`base_room_type_id`),
  CONSTRAINT `fk_hr2_room_type_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hr2_hotel` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One RATE PLAN. Several plans may cover the same hotel and dates.
CREATE TABLE IF NOT EXISTS `hr2_contract` (
  `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id`                INT UNSIGNED NOT NULL,
  `name`                    VARCHAR(160) NOT NULL,
  `plan_type`               ENUM('contract','special_offer','tactical','early_bird','addendum','series','migrated') NOT NULL DEFAULT 'contract',
  `rate_code`               VARCHAR(60)  NULL,
  `tier`                    ENUM('all','fit','group','wedding','long_stay') NOT NULL DEFAULT 'all',
  `tier_min_rooms`          SMALLINT UNSIGNED NULL,
  `tier_max_rooms`          SMALLINT UNSIGNED NULL,
  `tier_min_pax`            SMALLINT UNSIGNED NULL,
  `tier_max_pax`            SMALLINT UNSIGNED NULL,
  `priority`                TINYINT NOT NULL DEFAULT 0,
  `applies_to`              ENUM('both','quotation','payment') NOT NULL DEFAULT 'both',   -- payment = ad hoc rate: pays the hotel, never prices the customer
  `parent_contract_id`      INT UNSIGNED NULL,
  `import_id`               INT UNSIGNED NULL,
  `valid_from`              DATE NOT NULL,
  `valid_to`                DATE NOT NULL,
  `book_from`               DATE NULL,
  `book_to`                 DATE NULL,
  `currency`                CHAR(3) NOT NULL DEFAULT 'USD',
  `billing_currency`        ENUM('same','LKR') NOT NULL DEFAULT 'same',
  `pricing_mode`            ENUM('per_room','per_person','per_unit') NOT NULL DEFAULT 'per_room',
  `default_meal_plan`       VARCHAR(2) NULL,
  `taxes_included`          TINYINT(1) NOT NULL DEFAULT 1,
  `tax_pct`                 DECIMAL(5,2) NULL,
  `tax_note`                VARCHAR(200) NULL,
  `status`                  ENUM('draft','approved','superseded','cancelled') NOT NULL DEFAULT 'draft',
  `source_type`             ENUM('manual','excel','ai','migration','email') NOT NULL DEFAULT 'manual',
  `source_file`             VARCHAR(255) NULL,
  `received_at`             DATE NULL,
  `contract_date`           DATE NULL,
  `contract_type`           VARCHAR(40)  NULL,
  `hotel_reference`         VARCHAR(80)  NULL,
  `credit_facility`         ENUM('unknown','yes','no') NOT NULL DEFAULT 'unknown',
  `min_stay`                TINYINT UNSIGNED NULL,
  `max_rooms_per_booking`   SMALLINT UNSIGNED NULL,
  `release_days`            SMALLINT UNSIGNED NULL,
  `tentative_hold_hours`    SMALLINT UNSIGNED NULL,
  `extra_bed_adult`         DECIMAL(10,2) NULL,
  `max_children_free`       TINYINT UNSIGNED NULL,
  `child_policy_text`       VARCHAR(800) NULL,
  `guide_policy_text`       VARCHAR(600) NULL,
  `foc_policy_text`         VARCHAR(400) NULL,
  `cancellation_text`       VARCHAR(1200) NULL,
  `payment_text`            VARCHAR(800) NULL,
  `terms_text`              TEXT NULL,
  `voucher_note`            VARCHAR(300) NULL,
  `notes`                   TEXT NULL,
  `entered_by`              VARCHAR(100) NULL,
  `checked_by`              VARCHAR(100) NULL,
  `approved_by`             VARCHAR(100) NULL,
  `approved_at`             DATETIME NULL,
  `superseded_by`           INT UNSIGNED NULL,
  `created_at`              DATETIME NULL,
  `updated_at`              DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_contract_hotel` (`hotel_id`, `status`, `valid_from`, `valid_to`),
  KEY `idx_hr2_contract_parent` (`parent_contract_id`),
  KEY `idx_hr2_contract_import` (`import_id`),
  CONSTRAINT `fk_hr2_contract_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hr2_hotel` (`id`),
  CONSTRAINT `chk_hr2_contract_dates` CHECK (`valid_from` <= `valid_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_contract_market` (
  `contract_id`  INT UNSIGNED NOT NULL,
  `market_code`  VARCHAR(10)  NOT NULL,
  `mode`         ENUM('include','exclude') NOT NULL DEFAULT 'include',
  PRIMARY KEY (`contract_id`, `market_code`),
  CONSTRAINT `fk_hr2_contract_market_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A season of a plan: name + kind + one or more date ranges (hr2_season_date).
CREATE TABLE IF NOT EXISTS `hr2_season` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`  INT UNSIGNED NOT NULL,
  `name`         VARCHAR(120) NULL,
  `kind`         ENUM('normal','peak','festive','low','other') NOT NULL DEFAULT 'normal',
  `min_stay`     TINYINT UNSIGNED NULL,
  `max_rooms`    SMALLINT UNSIGNED NULL,
  `sort_order`   SMALLINT NOT NULL DEFAULT 0,
  `notes`        VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_season_contract` (`contract_id`, `sort_order`),
  CONSTRAINT `fk_hr2_season_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_season_date` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_id`  INT UNSIGNED NOT NULL,
  `date_from`  DATE NOT NULL,
  `date_to`    DATE NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_season_date` (`season_id`, `date_from`, `date_to`),
  CONSTRAINT `fk_hr2_season_date_season` FOREIGN KEY (`season_id`) REFERENCES `hr2_season` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_hr2_season_date` CHECK (`date_from` <= `date_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- season x room type x meal plan. Numbers follow the plan's pricing_mode:
--   per_room   sgl/dbl/tpl/quad = price of the room at that occupancy
--   per_person sgl/dbl/tpl      = price per person at that occupancy
--   per_unit   unit             = price of the whole villa / bungalow / dorm bed
-- NULL = not offered / not given. 0 is never stored. A missing row = not offered.
CREATE TABLE IF NOT EXISTS `hr2_rate` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `season_id`       INT UNSIGNED NOT NULL,
  `room_type_id`    INT UNSIGNED NOT NULL,
  `meal_plan`       ENUM('RO','BB','HB','FB','AI') NOT NULL,
  `sgl`             DECIMAL(10,2) NULL,
  `dbl`             DECIMAL(10,2) NULL,
  `tpl`             DECIMAL(10,2) NULL,
  `quad`            DECIMAL(10,2) NULL,
  `unit`            DECIMAL(10,2) NULL,
  `child_with_bed`  DECIMAL(10,2) NULL,
  `child_no_bed`    DECIMAL(10,2) NULL,
  `quality_flags`   VARCHAR(200) NULL,
  `note`            VARCHAR(200) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_rate` (`season_id`, `room_type_id`, `meal_plan`),
  KEY `idx_hr2_rate_room` (`room_type_id`),
  CONSTRAINT `fk_hr2_rate_season` FOREIGN KEY (`season_id`) REFERENCES `hr2_season` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hr2_rate_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `hr2_room_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Deluxe to Club Suite USD 40 per room per night" — amount added to the base
-- room's rate. season_id NULL = whole plan. Negative amounts are allowed.
CREATE TABLE IF NOT EXISTS `hr2_room_supplement` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`   INT UNSIGNED NOT NULL,
  `room_type_id`  INT UNSIGNED NOT NULL,
  `season_id`     INT UNSIGNED NULL,
  `amount`        DECIMAL(10,2) NOT NULL,
  `per`           ENUM('room','person') NOT NULL DEFAULT 'room',
  `note`          VARCHAR(200) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_room_supplement` (`contract_id`, `room_type_id`),
  CONSTRAINT `fk_hr2_room_supplement_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "BB reduction USD 5 per person on HB", "AI supplement USD 45 on HB / 22.5 per
-- child", "Half board add-on USD 25". Signed per-person-per-night amounts that
-- derive to_plan from from_plan when the hotel prices only one basis.
CREATE TABLE IF NOT EXISTS `hr2_meal_delta` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`  INT UNSIGNED NOT NULL,
  `from_plan`    ENUM('RO','BB','HB','FB','AI') NOT NULL,
  `to_plan`      ENUM('RO','BB','HB','FB','AI') NOT NULL,
  `adult`        DECIMAL(10,2) NOT NULL,
  `child`        DECIMAL(10,2) NULL,
  `season_id`    INT UNSIGNED NULL,
  `note`         VARCHAR(200) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_meal_delta` (`contract_id`, `from_plan`, `to_plan`),
  CONSTRAINT `fk_hr2_meal_delta_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extra meal prices (breakfast / lunch / dinner) per person.
CREATE TABLE IF NOT EXISTS `hr2_meal_price` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`  INT UNSIGNED NOT NULL,
  `meal`         ENUM('breakfast','lunch','dinner','other') NOT NULL,
  `name`         VARCHAR(80) NULL,
  `adult`        DECIMAL(10,2) NOT NULL,
  `child`        DECIMAL(10,2) NULL,
  `note`         VARCHAR(200) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_meal_price` (`contract_id`),
  CONSTRAINT `fk_hr2_meal_price_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gala dinners, peak / festival / August / Perahera surcharges, extra adult,
-- early check-in, late check-out. Dates, season, meal plans and room type narrow
-- where it applies; amount_sgl/dbl/tpl hold occupancy-specific amounts
-- (Casamara "Perahera SGL 55 / DBL 65 / TPL 75"); pct_of_rate covers "50% of
-- the room rate" late check-outs; child_amount / child_pct cover the child line.
CREATE TABLE IF NOT EXISTS `hr2_supplement` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`          INT UNSIGNED NOT NULL,
  `type`                 ENUM('gala','peak','festival','extra_adult','early_checkin','late_checkout','other') NOT NULL DEFAULT 'other',
  `name`                 VARCHAR(160) NOT NULL,
  `date_from`            DATE NULL,
  `date_to`              DATE NULL,
  `season_id`            INT UNSIGNED NULL,
  `applies_meal_plans`   VARCHAR(30) NULL,
  `room_type_id`         INT UNSIGNED NULL,
  `per`                  ENUM('person','adult','child','room','night','booking') NOT NULL DEFAULT 'person',
  `amount`               DECIMAL(10,2) NULL,
  `amount_sgl`           DECIMAL(10,2) NULL,
  `amount_dbl`           DECIMAL(10,2) NULL,
  `amount_tpl`           DECIMAL(10,2) NULL,
  `child_amount`         DECIMAL(10,2) NULL,
  `child_pct`            DECIMAL(5,2) NULL,
  `pct_of_rate`          DECIMAL(5,2) NULL,
  `compulsory`           TINYINT(1) NOT NULL DEFAULT 1,
  `note`                 VARCHAR(400) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_supplement_contract` (`contract_id`, `date_from`, `date_to`),
  CONSTRAINT `fk_hr2_supplement_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Child pricing by age band. amount 0 = free of charge. pct + pct_basis for
-- "50% of the adult rate" / "50% of the meal supplement" style rules.
CREATE TABLE IF NOT EXISTS `hr2_child_rate` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`    INT UNSIGNED NOT NULL,
  `age_from`       DECIMAL(4,2) NOT NULL DEFAULT 0,
  `age_to`         DECIMAL(4,2) NOT NULL,
  `bed`            ENUM('any','with_bed','no_bed') NOT NULL DEFAULT 'any',
  `meal_plan`      VARCHAR(2) NULL,
  `amount`         DECIMAL(10,2) NULL,
  `pct`            DECIMAL(5,2) NULL,
  `pct_basis`      ENUM('adult_rate','meal_rate','meal_supplement','room_rate') NULL,
  `max_children`   TINYINT UNSIGNED NULL,
  `room_type_id`   INT UNSIGNED NULL,
  `note`           VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_child_rate` (`contract_id`, `age_from`),
  CONSTRAINT `fk_hr2_child_rate_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Guide / driver / tour-leader room rules.
CREATE TABLE IF NOT EXISTS `hr2_guide_policy` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`    INT UNSIGNED NOT NULL,
  `role`           ENUM('guide','driver','tour_leader','any') NOT NULL DEFAULT 'guide',
  `meal_plan`      VARCHAR(2) NULL,
  `amount`         DECIMAL(10,2) NULL,
  `currency`       CHAR(3) NULL,
  `pct_of`         ENUM('sgl','dbl','room') NULL,
  `pct`            DECIMAL(5,2) NULL,
  `per`            ENUM('person','room') NOT NULL DEFAULT 'room',
  `free_from_pax`  SMALLINT UNSIGNED NULL,
  `min_rooms`      SMALLINT UNSIGNED NULL,
  `accommodation`  ENUM('hotel_room','guide_quarters','nearby','unknown') NOT NULL DEFAULT 'unknown',
  `note`           VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_guide_policy` (`contract_id`),
  CONSTRAINT `fk_hr2_guide_policy_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- "Every 15th paying pax the 16th is FOC", "every 7th paid room the 8th at 50%".
CREATE TABLE IF NOT EXISTS `hr2_foc_rule` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`   INT UNSIGNED NOT NULL,
  `basis`         ENUM('pax','rooms') NOT NULL DEFAULT 'pax',
  `every_n`       SMALLINT UNSIGNED NOT NULL,
  `free_n`        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `discount_pct`  DECIMAL(5,2) NOT NULL DEFAULT 100,
  `as_what`       ENUM('any','half_double','single','guide_room') NOT NULL DEFAULT 'any',
  `note`          VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_foc_rule` (`contract_id`),
  CONSTRAINT `fk_hr2_foc_rule_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Early-booking, long-stay, honeymoon, group reductions and similar offers.
CREATE TABLE IF NOT EXISTS `hr2_offer` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`    INT UNSIGNED NOT NULL,
  `kind`           ENUM('early_booking','long_stay','honeymoon','group_reduction','free_night','other') NOT NULL DEFAULT 'other',
  `name`           VARCHAR(160) NOT NULL,
  `book_from`      DATE NULL,
  `book_to`        DATE NULL,
  `stay_from`      DATE NULL,
  `stay_to`        DATE NULL,
  `min_nights`     TINYINT UNSIGNED NULL,
  `min_pax`        SMALLINT UNSIGNED NULL,
  `min_rooms`      SMALLINT UNSIGNED NULL,
  `value_pct`      DECIMAL(5,2) NULL,
  `value_amount`   DECIMAL(10,2) NULL,
  `free_nights`    TINYINT UNSIGNED NULL,
  `per`            ENUM('person','room','booking','night') NOT NULL DEFAULT 'room',
  `combinable`     TINYINT(1) NOT NULL DEFAULT 0,
  `excludes_peak`  TINYINT(1) NOT NULL DEFAULT 0,
  `conditions`     VARCHAR(600) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_offer` (`contract_id`),
  CONSTRAINT `fk_hr2_offer_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cancellation matrix rows: (optional stay dates) x tier/size x days-before band -> charge.
CREATE TABLE IF NOT EXISTS `hr2_cancellation_rule` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`    INT UNSIGNED NOT NULL,
  `date_from`      DATE NULL,
  `date_to`        DATE NULL,
  `tier`           ENUM('all','fit','group') NOT NULL DEFAULT 'all',
  `min_rooms`      SMALLINT UNSIGNED NULL,
  `max_rooms`      SMALLINT UNSIGNED NULL,
  `min_pax`        SMALLINT UNSIGNED NULL,
  `max_pax`        SMALLINT UNSIGNED NULL,
  `days_from`      SMALLINT UNSIGNED NULL,
  `days_to`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `charge_pct`     DECIMAL(5,2) NULL,
  `charge_nights`  TINYINT UNSIGNED NULL,
  `note`           VARCHAR(300) NULL,
  `sort_order`     SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_cancellation_rule` (`contract_id`, `sort_order`),
  CONSTRAINT `fk_hr2_cancellation_rule_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_payment_rule` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`  INT UNSIGNED NOT NULL,
  `applies`      ENUM('all','credit','non_credit','fit','group','peak') NOT NULL DEFAULT 'all',
  `kind`         ENUM('prepay','advance','interest','surcharge','credit_period','other') NOT NULL DEFAULT 'prepay',
  `days_before`  SMALLINT NULL,
  `pct`          DECIMAL(5,2) NULL,
  `date_from`    DATE NULL,
  `date_to`      DATE NULL,
  `note`         VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_payment_rule` (`contract_id`),
  CONSTRAINT `fk_hr2_payment_rule_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Date-bound rules: minimum stay, closed to arrival / departure, meal plan
-- required (24 & 31 Dec HB/FB only), stop sales.
CREATE TABLE IF NOT EXISTS `hr2_restriction` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`  INT UNSIGNED NOT NULL,
  `kind`         ENUM('min_stay','closed_to_arrival','closed_to_departure','meal_plan_required','stop_sale','other') NOT NULL,
  `date_from`    DATE NOT NULL,
  `date_to`      DATE NOT NULL,
  `value`        VARCHAR(60) NULL,
  `note`         VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_restriction` (`contract_id`, `date_from`, `date_to`),
  CONSTRAINT `fk_hr2_restriction_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Excursions, transfers, MICE and other priced extras printed on the sheet.
CREATE TABLE IF NOT EXISTS `hr2_extra` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contract_id`  INT UNSIGNED NOT NULL,
  `category`     ENUM('excursion','transfer','mice','wellness','other') NOT NULL DEFAULT 'other',
  `name`         VARCHAR(160) NOT NULL,
  `per`          ENUM('person','room','booking','group','hour') NOT NULL DEFAULT 'person',
  `adult`        DECIMAL(10,2) NULL,
  `child`        DECIMAL(10,2) NULL,
  `currency`     CHAR(3) NULL,
  `note`         VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_extra` (`contract_id`),
  CONSTRAINT `fk_hr2_extra_contract` FOREIGN KEY (`contract_id`) REFERENCES `hr2_contract` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Which legacy hotel_rate rows were written by the projector (per hotel).
CREATE TABLE IF NOT EXISTS `hr2_legacy_map` (
  `hotel_rate_id`  INT NOT NULL,
  `hotel_id`       INT UNSIGNED NOT NULL,
  `room_type_id`   INT UNSIGNED NOT NULL,
  `date_from`      DATE NOT NULL,
  `date_to`        DATE NOT NULL,
  `contract_ids`   VARCHAR(200) NULL,
  PRIMARY KEY (`hotel_rate_id`),
  KEY `idx_hr2_legacy_map_hotel` (`hotel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- What a confirmed tour PAYS each hotel per night, from the panel: the ad hoc (payment-only)
-- rate when one exists for that hotel / night / room type / meal plan, else the contract rate,
-- else the quoted rate (flagged). The customer's quotation is never touched; the difference
-- between the quoted and the payable amount is the margin.
CREATE TABLE IF NOT EXISTS `hr2_tour_hotel_cost` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`            VARCHAR(50)  NOT NULL,
  `manual_id`          VARCHAR(50)  NULL,
  `quotation_row_id`   INT          NOT NULL,
  `hotel_acc`          VARCHAR(50)  NOT NULL,
  `hotel_id`           INT UNSIGNED NULL,
  `tour_date`          DATE         NOT NULL,
  `room_type_legacy`   INT          NULL,
  `room_type_name`     VARCHAR(160) NULL,
  `meal_plan`          VARCHAR(2)   NULL,
  `market`             VARCHAR(10)  NULL,
  `sgl_count`          SMALLINT     NOT NULL DEFAULT 0,
  `dbl_count`          SMALLINT     NOT NULL DEFAULT 0,
  `tpl_count`          SMALLINT     NOT NULL DEFAULT 0,
  `quote_sgl`          DECIMAL(10,2) NULL,
  `quote_dbl`          DECIMAL(10,2) NULL,
  `quote_tpl`          DECIMAL(10,2) NULL,
  `pay_sgl`            DECIMAL(10,2) NULL,
  `pay_dbl`            DECIMAL(10,2) NULL,
  `pay_tpl`            DECIMAL(10,2) NULL,
  `pay_supplements`    DECIMAL(10,2) NOT NULL DEFAULT 0,
  `quote_total`        DECIMAL(10,2) NOT NULL DEFAULT 0,
  `pay_total`          DECIMAL(10,2) NOT NULL DEFAULT 0,
  `source`             ENUM('adhoc','contracted','quoted','none') NOT NULL DEFAULT 'none',
  `plan_id`            INT UNSIGNED NULL,
  `plan_name`          VARCHAR(160) NULL,
  `season_name`        VARCHAR(120) NULL,
  `derived`            VARCHAR(60)  NULL,
  `flags_json`         TEXT         NULL,
  `check_note`         VARCHAR(300) NULL,
  `computed_at`        DATETIME     NOT NULL,
  `computed_by`        VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_tour_hotel_cost_row` (`quotation_row_id`),
  KEY `idx_hr2_tour_hotel_cost_tour` (`tour_id`, `hotel_acc`),
  KEY `idx_hr2_tour_hotel_cost_hotel` (`hotel_id`, `tour_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hotel Incomplete Work: everything the system found missing or doubtful and could not
-- decide by itself (no contract rate for a confirmed night, a hotel with no supplier record,
-- a draft waiting for approval, an unusual margin, a rate-sheet query …). Detectors upsert by
-- item_key; items a later scan no longer finds are closed by the system; people close the
-- rest with a note. Kinds are free text so new detectors need no schema change.
CREATE TABLE IF NOT EXISTS `hr2_work_item` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_key`     VARCHAR(190) NOT NULL,
  `kind`         VARCHAR(40)  NOT NULL,
  `level`        ENUM('red','amber','blue') NOT NULL DEFAULT 'amber',
  `title`        VARCHAR(255) NOT NULL,
  `detail`       TEXT NULL,
  `hotel_id`     INT UNSIGNED NULL,
  `hotel_acc`    VARCHAR(50)  NULL,
  `hotel_name`   VARCHAR(190) NULL,
  `tour_id`      VARCHAR(50)  NULL,
  `manual_id`    VARCHAR(50)  NULL,
  `plan_id`      INT UNSIGNED NULL,
  `ref_date`     DATE NULL,
  `ref_date_to`  DATE NULL,
  `amount`       DECIMAL(12,2) NULL,
  `data_json`    TEXT NULL,
  `source`       ENUM('auto','manual') NOT NULL DEFAULT 'auto',
  `status`       ENUM('open','done','ignored') NOT NULL DEFAULT 'open',
  `note`         TEXT NULL,
  `seen_count`   INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL,
  `created_by`   VARCHAR(100) NULL,
  `updated_at`   DATETIME NOT NULL,
  `last_seen_at` DATETIME NULL,
  `resolved_at`  DATETIME NULL,
  `resolved_by`  VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr2_work_key` (`item_key`),
  KEY `ix_hr2_work_status_kind` (`status`, `kind`),
  KEY `ix_hr2_work_hotel` (`hotel_id`),
  KEY `ix_hr2_work_tour` (`tour_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_import` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id`      INT UNSIGNED NULL,
  `contract_id`   INT UNSIGNED NULL,
  `contract_ids`  VARCHAR(400) NULL,
  `source`        ENUM('excel','ai','manual','migration','email') NOT NULL,
  `file_name`     VARCHAR(255) NULL,
  `status`        ENUM('preview','committed','rolled_back','failed') NOT NULL DEFAULT 'preview',
  `rows_total`    INT NOT NULL DEFAULT 0,
  `rows_ok`       INT NOT NULL DEFAULT 0,
  `rows_warning`  INT NOT NULL DEFAULT 0,
  `rows_error`    INT NOT NULL DEFAULT 0,
  `payload`       LONGTEXT NULL,
  `created_by`    VARCHAR(100) NULL,
  `created_at`    DATETIME NULL,
  `committed_at`  DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_import_hotel` (`hotel_id`),
  KEY `idx_hr2_import_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_audit` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity`      VARCHAR(30) NOT NULL,
  `entity_id`   INT UNSIGNED NOT NULL,
  `action`      ENUM('insert','update','delete','merge','unmerge','approve','supersede','project','migrate','rename','override','archive','restore') NOT NULL,
  `old_json`    MEDIUMTEXT NULL,
  `new_json`    MEDIUMTEXT NULL,
  `import_id`   INT UNSIGNED NULL,
  `user`        VARCHAR(100) NULL,
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_audit_entity` (`entity`, `entity_id`),
  KEY `idx_hr2_audit_import` (`import_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- schema 8 (6 Oct 2026): the hotel code HTL-001 … — one number per hotel, given by the system, never reused.
-- hr2_sequence advances atomically (LAST_INSERT_ID(last_no + 1)); the register's unique keys make a duplicate impossible.
CREATE TABLE IF NOT EXISTS `hr2_sequence` (
  `name`     VARCHAR(30) NOT NULL,
  `last_no`  INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr2_hotel_code` (
  `code`          VARCHAR(12) NOT NULL,
  `no`            INT UNSIGNED NOT NULL,
  `hotel_id`      INT UNSIGNED NULL,
  `sup_acc_no`    VARCHAR(50) NULL,
  `hotel_name_at` VARCHAR(240) NULL,
  `how`           VARCHAR(40) NOT NULL,
  `assigned_at`   DATETIME NOT NULL,
  `assigned_by`   VARCHAR(100) NULL,
  PRIMARY KEY (`code`),
  UNIQUE KEY `uq_hr2_hotel_code_no` (`no`),
  UNIQUE KEY `uq_hr2_hotel_code_hotel` (`hotel_id`),
  KEY `idx_hr2_hotel_code_acc` (`sup_acc_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- tf-hr-docs-r1 (12 Nov 2026): supporting documents of a hotel (contract_id NULL) or of one rate plan.
-- Files are stored outside the public folder under writable/hotel_rate_docs/<hotel_id>/; rows are never deleted
-- (removed_at / removed_by / removed_reason hide a document and keep the trail).
CREATE TABLE IF NOT EXISTS `hr2_document` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hotel_id`        INT UNSIGNED NOT NULL,
  `contract_id`     INT UNSIGNED NULL,
  `kind`            VARCHAR(20) NOT NULL DEFAULT 'rate_sheet',
  `original_name`   VARCHAR(255) NOT NULL,
  `stored_name`     VARCHAR(120) NOT NULL,
  `ext`             VARCHAR(10) NOT NULL,
  `mime`            VARCHAR(120) NULL,
  `size_bytes`      INT UNSIGNED NOT NULL DEFAULT 0,
  `sha256`          CHAR(64) NOT NULL,
  `note`            VARCHAR(300) NULL,
  `uploaded_by`     VARCHAR(100) NULL,
  `uploaded_at`     DATETIME NOT NULL,
  `removed_at`      DATETIME NULL,
  `removed_by`      VARCHAR(100) NULL,
  `removed_reason`  VARCHAR(300) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr2_document_hotel` (`hotel_id`, `removed_at`),
  KEY `idx_hr2_document_contract` (`contract_id`, `removed_at`),
  KEY `idx_hr2_document_sha` (`sha256`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
