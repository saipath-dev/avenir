-- tf-supmail-r1 (4 Nov 2026): every reservation request to an ACTIVITY supplier or to a RESTAURANT on a
-- confirmed tour — prepared, sent, failed, communicated by hand, or a reply recorded. One row per
-- communication, exactly as the hotels' letters and the transport requests are kept.
--
-- `req_key` holds 'request' on a request row and NULL on every other kind. With the unique key below that
-- gives ONE request per line per party at the database itself, so a double click, a retry or two desks
-- pressing at once cannot raise the same request twice; MySQL ignores NULLs in a unique index, so a line
-- may still carry as many replies and amendments as the desk records.
--
-- Additive and idempotent: it creates its own table and touches nothing that exists.
CREATE TABLE IF NOT EXISTS `tf_service_message` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id`    INT UNSIGNED NOT NULL,
  `job_id`     INT UNSIGNED NULL DEFAULT NULL,
  `party`      ENUM('activity','restaurant') NOT NULL DEFAULT 'activity',
  `party_name` VARCHAR(190) NULL DEFAULT NULL,
  `kind`       ENUM('request','amendment','reply') NOT NULL DEFAULT 'request',
  `req_key`    VARCHAR(16)  NULL DEFAULT NULL,
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
  KEY `k_state` (`state`),
  UNIQUE KEY `u_one_request` (`job_id`, `party`, `req_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
