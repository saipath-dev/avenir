-- tf-taxiq-r2 (3 Nov 2026): a van taking a small tour at the car rate — the driver's own agreement and the
-- approval that must come before the allocation. One row per request, approved by somebody other than the
-- person who raised it, exactly as a tour discount is. Additive: it creates its own table and touches nothing.
CREATE TABLE IF NOT EXISTS `tf_rot_car_rate` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `job_id`        INT UNSIGNED NULL DEFAULT NULL,
  `tour_id`       INT UNSIGNED NULL DEFAULT NULL,
  `tour_no`       VARCHAR(40)  NULL DEFAULT NULL,
  `person_id`     INT UNSIGNED NOT NULL,
  `person_name`   VARCHAR(190) NULL DEFAULT NULL,
  `guests`        INT          NULL DEFAULT NULL,
  `van_rate_km`   DECIMAL(10,2) NULL DEFAULT NULL,
  `car_rate_km`   DECIMAL(10,2) NULL DEFAULT NULL,
  `agreed_rate_km` DECIMAL(10,2) NOT NULL,
  `driver_said`   VARCHAR(400) NULL DEFAULT NULL,
  `state`         ENUM('requested','approved','declined','cancelled') NOT NULL DEFAULT 'requested',
  `requested_by`  VARCHAR(100) NULL DEFAULT NULL,
  `requested_by_id` INT UNSIGNED NULL DEFAULT NULL,
  `requested_at`  DATETIME NOT NULL,
  `decided_by`    VARCHAR(100) NULL DEFAULT NULL,
  `decided_by_id` INT UNSIGNED NULL DEFAULT NULL,
  `decided_at`    DATETIME NULL DEFAULT NULL,
  `decision_note` VARCHAR(400) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `k_job` (`job_id`),
  KEY `k_person` (`person_id`),
  KEY `k_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
