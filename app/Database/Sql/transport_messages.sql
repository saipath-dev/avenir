-- tf-transmail-r1 (3 Nov 2026): every booking request to a transport supplier, driver or guide — prepared,
-- sent, failed, communicated by hand, or a reply recorded. One row per communication, exactly as the hotels'
-- letters are kept. Additive and idempotent: it creates its own table and touches nothing that exists.
CREATE TABLE IF NOT EXISTS `tf_transport_message` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`    INT UNSIGNED NOT NULL,
  `job_id`     INT UNSIGNED NULL DEFAULT NULL,
  `crew_id`    INT UNSIGNED NULL DEFAULT NULL,
  `party`      ENUM('supplier','driver','guide') NOT NULL,
  `party_name` VARCHAR(190) NULL DEFAULT NULL,
  `kind`       ENUM('request','amendment','reply') NOT NULL DEFAULT 'request',
  `channel`    ENUM('email','whatsapp','print','other') NOT NULL DEFAULT 'email',
  `state`      ENUM('prepared','sent','failed','recorded') NOT NULL DEFAULT 'prepared',
  `to_addr`    VARCHAR(255) NULL DEFAULT NULL,
  `cc_addr`    VARCHAR(255) NULL DEFAULT NULL,
  `to_phone`   VARCHAR(60)  NULL DEFAULT NULL,
  `subject`    VARCHAR(255) NULL DEFAULT NULL,
  `body`       MEDIUMTEXT   NULL DEFAULT NULL,
  `error`      VARCHAR(500) NULL DEFAULT NULL,
  `by_name`    VARCHAR(190) NULL DEFAULT NULL,
  `at`         DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  KEY `k_tour` (`tour_id`),
  KEY `k_job` (`job_id`),
  KEY `k_crew` (`crew_id`),
  KEY `k_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
