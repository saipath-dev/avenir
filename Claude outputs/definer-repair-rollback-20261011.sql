-- =====================================================================================================================
--  EMERGENCY RECORD ONLY. DO NOT RUN.
--  definer-repair-rollback-20261011.sql  (revised 11 Oct 2026, evening: guards, checks and recovery procedure added)
-- =====================================================================================================================
--  What it would do: put the three views acc_selct, account_runing_q2, account_runing_q21 and the three triggers
--  driver_allocation_after_insert / _update / _delete in `avenir` back under the definer 'dbmasteruser'@'%', exactly as they
--  were before the repair of 11 Oct 2026. Their SQL does not change, only the account they run as.
--
--  Why it must not be run: 'dbmasteruser'@'%' does not exist on the development server. After running it, the three views
--  fail with error #1449, and every driver allocation save answers "Failed to allocate driver" while the MyISAM row is still
--  written without its audit row.
--
--  Status: kept only as a record of the original definitions. The same definitions are in the verified post-R5 recovery
--  backup (avenir_backup_20261011_post_r5) and its independent export (C:\wamp64\tmp\avenir-exports-20261011\).
--  It may be run only with Shantha's written approval, and only after the approval line in section 1 has been edited.
--
--  How it protects itself. Each check inserts one row into a temporary table whose `passed` column cannot be NULL. A failed
--  check inserts NULL, so the script stops at once with error 1048 "Column 'passed' cannot be null". Both phpMyAdmin and the
--  mysql client stop at the first error, so nothing after a failed check runs. The temporary table disappears when the
--  connection closes; nothing else is created.
--
--  Sections
--    1  Approval check       stops unless @rollback_approved has been set to a written-approval reference
--    2  Server check         stops unless this is the development server: ShanthaW, port 3306, MySQL 8.4.7,
--                            server_uuid d52e1dce-aa9a-11f1-8e2d-e446b01a6b9e, database avenir
--    3  Pre-checks           stops unless the six objects are exactly in the repaired state recorded on 11 Oct 2026
--    4  The change           the views first, then the triggers while driver_allocation is locked
--    5  Post-checks          stops (after the change) if any of the six objects is not as expected, and lists them
--    6  Recovery procedure   what to do if the run stops part-way (read it before any run)
--
--  Run it as one import in phpMyAdmin with the database avenir selected, or with the mysql client started with
--  --comments (without it the client strips the comment inside the trigger bodies and check 5b stops the run). Never split it.
-- =====================================================================================================================

USE `avenir`;
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';   -- the sql_mode the three triggers are stored with
DROP TEMPORARY TABLE IF EXISTS `_definer_guard`;
CREATE TEMPORARY TABLE `_definer_guard` (`step` VARCHAR(160) NOT NULL, `passed` TINYINT NOT NULL);

-- 1. Approval check. Leave this line as it is. Only with Shantha's written approval, replace NULL with the approval
--    reference, for example 'Shantha, e-mail of 2026-10-12'.
SET @rollback_approved = NULL;
INSERT INTO `_definer_guard` VALUES ('1 approval: @rollback_approved must hold a written-approval reference',
  IF(CHAR_LENGTH(COALESCE(@rollback_approved, '')) >= 10, 1, NULL));

-- 2. Server check.
INSERT INTO `_definer_guard` VALUES ('2 server: ShanthaW / 3306 / MySQL 8.4.7 / d52e1dce-aa9a-11f1-8e2d-e446b01a6b9e / database avenir',
  IF(@@hostname = 'ShanthaW' AND @@port = 3306 AND @@version = '8.4.7' AND @@server_uuid = 'd52e1dce-aa9a-11f1-8e2d-e446b01a6b9e' AND DATABASE() = 'avenir', 1, NULL));

-- 3. Pre-checks: the six objects are in the repaired state (root@localhost, same SQL as recorded on 11 Oct 2026).
INSERT INTO `_definer_guard` VALUES ('3a pre: the three views are under root@localhost with the recorded definitions',
  IF((SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND DEFINER = 'root@localhost' AND SECURITY_TYPE = 'DEFINER' AND CHECK_OPTION = 'NONE' AND ((TABLE_NAME = 'acc_selct' AND MD5(VIEW_DEFINITION) = 'd95f47d351a2d9f27f3b8f43b43098c1') OR (TABLE_NAME = 'account_runing_q2' AND MD5(VIEW_DEFINITION) = 'f030b78a240c3de25c9733aa78be9621') OR (TABLE_NAME = 'account_runing_q21' AND MD5(VIEW_DEFINITION) = '1d4f3dd6c80c39b4da5e09c0399428ea'))) = 3, 1, NULL));
INSERT INTO `_definer_guard` VALUES ('3b pre: the three triggers are under root@localhost, NO_ENGINE_SUBSTITUTION, recorded bodies',
  IF((SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'driver_allocation' AND ACTION_TIMING = 'AFTER' AND DEFINER = 'root@localhost' AND SQL_MODE = 'NO_ENGINE_SUBSTITUTION' AND ((TRIGGER_NAME = 'driver_allocation_after_insert' AND EVENT_MANIPULATION = 'INSERT' AND MD5(ACTION_STATEMENT) = 'd21612dbdd00ea7fcc42fb6377682aac') OR (TRIGGER_NAME = 'driver_allocation_after_delete' AND EVENT_MANIPULATION = 'DELETE' AND MD5(ACTION_STATEMENT) = '96d9f654c04d93f8a7c806e4a1cc14cb') OR (TRIGGER_NAME = 'driver_allocation_after_update' AND EVENT_MANIPULATION = 'UPDATE' AND MD5(ACTION_STATEMENT) = 'ee77d484e2415657d979eb335deb61c6'))) = 3, 1, NULL));
INSERT INTO `_definer_guard` VALUES ('3c pre: driver_allocation has exactly these three triggers', IF((SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'driver_allocation') = 3, 1, NULL));
SET @allocations_before = (SELECT COUNT(*) FROM `driver_allocation`), @audit_before = (SELECT COUNT(*) FROM `driver_allocation_audit`);
SELECT * FROM `_definer_guard`;

-- 4. The change (original definitions, byte for byte as read from the server on 11 Oct 2026).
-- view acc_selct
CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER='dbmasteruser'@'%' SQL SECURITY DEFINER VIEW `acc_selct` AS select `avenir`.`account_basement`.`B_Code` AS `B_Code`,`avenir`.`account_basement`.`BName` AS `BName`,`avenir`.`account_basement`.`balflag` AS `balflag`,`avenir`.`account_titel`.`T_Code` AS `T_Code`,`avenir`.`account_titel`.`TName` AS `TName`,`avenir`.`account_heading`.`HName` AS `HName`,`avenir`.`account_heading`.`H_Code` AS `H_Code`,`avenir`.`account_posting`.`P_Code` AS `P_Code`,`avenir`.`account_posting`.`PName` AS `PName`,`avenir`.`account_posting`.`L_Code` AS `L_Code` from (`avenir`.`account_posting` join (`avenir`.`account_heading` join (`avenir`.`account_titel` join `avenir`.`account_basement` on((`avenir`.`account_titel`.`B_Code` = `avenir`.`account_basement`.`B_Code`))) on(((`avenir`.`account_heading`.`T_Code` = `avenir`.`account_titel`.`T_Code`) and (`avenir`.`account_heading`.`B_Code` = `avenir`.`account_basement`.`B_Code`)))) on(((`avenir`.`account_posting`.`B_Code` = `avenir`.`account_basement`.`B_Code`) and (`avenir`.`account_posting`.`T_Code` = `avenir`.`account_titel`.`T_Code`) and (`avenir`.`account_posting`.`H_Code` = `avenir`.`account_heading`.`H_Code`))));

-- view account_runing_q2
CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER='dbmasteruser'@'%' SQL SECURITY DEFINER VIEW `account_runing_q2` AS select `avenir`.`account_runing_qq`.`index_no` AS `TourId`,`avenir`.`account_runing_qq`.`BName` AS `BName`,`avenir`.`account_runing_qq`.`TName` AS `TName`,`avenir`.`account_runing_qq`.`HName` AS `HName`,`avenir`.`account_runing_qq`.`PName` AS `PName`,`avenir`.`account_runing_qq`.`L_Code` AS `L_Code`,`avenir`.`account_runing_qq`.`Date` AS `Date`,`avenir`.`account_runing_qq`.`Dr` AS `Dr`,`avenir`.`account_runing_qq`.`Cr` AS `Cr`,`avenir`.`account_runing_qq`.`ID` AS `ID`,`avenir`.`account_runing_qq`.`balflag` AS `balflag`,`avenir`.`account_runing_qq`.`Recpt_No` AS `Recpt_No`,(case when (`avenir`.`account_runing_qq`.`balflag` = 1) then (`avenir`.`account_runing_qq`.`Dr` - `avenir`.`account_runing_qq`.`Cr`) else (`avenir`.`account_runing_qq`.`Cr` - `avenir`.`account_runing_qq`.`Dr`) end) AS `bal`,`avenir`.`account_runing_qq`.`Discription` AS `Discription` from `avenir`.`account_runing_qq`;

-- view account_runing_q21
CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER='dbmasteruser'@'%' SQL SECURITY DEFINER VIEW `account_runing_q21` AS select `avenir`.`account_runing_qq`.`BName` AS `BName`,`avenir`.`account_runing_qq`.`TName` AS `TName`,`avenir`.`account_runing_qq`.`HName` AS `HName`,`avenir`.`account_runing_qq`.`PName` AS `PName`,`avenir`.`account_runing_qq`.`Date` AS `Date`,`avenir`.`account_runing_qq`.`Dr` AS `Dr`,`avenir`.`account_runing_qq`.`Cr` AS `Cr`,(case when (`avenir`.`account_runing_qq`.`balflag` = 1) then (`avenir`.`account_runing_qq`.`Dr` - `avenir`.`account_runing_qq`.`Cr`) else (`avenir`.`account_runing_qq`.`Cr` - `avenir`.`account_runing_qq`.`Dr`) end) AS `bal` from `avenir`.`account_runing_qq`;

-- the triggers: driver_allocation is locked for writing, so no allocation can be saved between a DROP and its CREATE
LOCK TABLES `driver_allocation` WRITE;

-- trigger driver_allocation_after_delete
DROP TRIGGER IF EXISTS `driver_allocation_after_delete`;
DELIMITER $$
CREATE DEFINER='dbmasteruser'@'%' TRIGGER `driver_allocation_after_delete` AFTER DELETE ON `driver_allocation` FOR EACH ROW BEGIN
  INSERT INTO driver_allocation_audit (
    operation, record_id, tour_id, manual_id, driver, driver_name, changed_by
  )
  VALUES (
    'DELETE', OLD.id, OLD.tour_id, OLD.manual_id, OLD.driver, OLD.driver_name, 
    COALESCE(@current_app_user, USER()) -- Use application user if set, else MySQL user
  );
END$$
DELIMITER ;

-- trigger driver_allocation_after_insert
DROP TRIGGER IF EXISTS `driver_allocation_after_insert`;
DELIMITER $$
CREATE DEFINER='dbmasteruser'@'%' TRIGGER `driver_allocation_after_insert` AFTER INSERT ON `driver_allocation` FOR EACH ROW BEGIN
  INSERT INTO driver_allocation_audit (
    operation, record_id, tour_id, manual_id, driver, driver_name, changed_by
  )
  VALUES (
    'INSERT', NEW.id, NEW.tour_id, NEW.manual_id, NEW.driver, NEW.driver_name, 
    COALESCE(@current_app_user, USER()) -- Use application user if set, else MySQL user
  );
END$$
DELIMITER ;

-- trigger driver_allocation_after_update
DROP TRIGGER IF EXISTS `driver_allocation_after_update`;
DELIMITER $$
CREATE DEFINER='dbmasteruser'@'%' TRIGGER `driver_allocation_after_update` AFTER UPDATE ON `driver_allocation` FOR EACH ROW BEGIN
  INSERT INTO driver_allocation_audit (
    operation, record_id, tour_id, manual_id, driver, driver_name, changed_by
  )
  VALUES (
    'UPDATE', NEW.id, NEW.tour_id, NEW.manual_id, NEW.driver, NEW.driver_name, 
    COALESCE(@current_app_user, USER()) -- Use application user if set, else MySQL user
  );
END$$
DELIMITER ;

UNLOCK TABLES;

-- 5. Post-checks.
INSERT INTO `_definer_guard` VALUES ('5a post: the three views are under dbmasteruser@% with the recorded definitions', IF((SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND DEFINER = 'dbmasteruser@%' AND SECURITY_TYPE = 'DEFINER' AND CHECK_OPTION = 'NONE' AND ((TABLE_NAME = 'acc_selct' AND MD5(VIEW_DEFINITION) = 'd95f47d351a2d9f27f3b8f43b43098c1') OR (TABLE_NAME = 'account_runing_q2' AND MD5(VIEW_DEFINITION) = 'f030b78a240c3de25c9733aa78be9621') OR (TABLE_NAME = 'account_runing_q21' AND MD5(VIEW_DEFINITION) = '1d4f3dd6c80c39b4da5e09c0399428ea'))) = 3, 1, NULL));
INSERT INTO `_definer_guard` VALUES ('5b post: the three triggers are under dbmasteruser@% with the recorded bodies and sql_mode', IF((SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'driver_allocation' AND ACTION_TIMING = 'AFTER' AND DEFINER = 'dbmasteruser@%' AND SQL_MODE = 'NO_ENGINE_SUBSTITUTION' AND ((TRIGGER_NAME = 'driver_allocation_after_insert' AND EVENT_MANIPULATION = 'INSERT' AND MD5(ACTION_STATEMENT) = 'd21612dbdd00ea7fcc42fb6377682aac') OR (TRIGGER_NAME = 'driver_allocation_after_delete' AND EVENT_MANIPULATION = 'DELETE' AND MD5(ACTION_STATEMENT) = '96d9f654c04d93f8a7c806e4a1cc14cb') OR (TRIGGER_NAME = 'driver_allocation_after_update' AND EVENT_MANIPULATION = 'UPDATE' AND MD5(ACTION_STATEMENT) = 'ee77d484e2415657d979eb335deb61c6'))) = 3, 1, NULL));
INSERT INTO `_definer_guard` VALUES ('5c post: no allocation or audit row was written during the change',
  IF((SELECT COUNT(*) FROM `driver_allocation`) = @allocations_before AND (SELECT COUNT(*) FROM `driver_allocation_audit`) = @audit_before, 1, NULL));
SELECT * FROM `_definer_guard`;
SELECT 'view' AS kind, TABLE_NAME AS name, DEFINER AS definer, SECURITY_TYPE AS security_or_sql_mode, MD5(VIEW_DEFINITION) AS fingerprint
  FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('acc_selct', 'account_runing_q2', 'account_runing_q21')
UNION ALL
SELECT 'trigger', TRIGGER_NAME, DEFINER, SQL_MODE, MD5(ACTION_STATEMENT)
  FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'driver_allocation'
ORDER BY 1, 2;

-- 6. Recovery procedure if the run stops part-way (read before any run)
--
--  a) Stopped at check 1, 2 or 3 (error 1048 naming the check): nothing was changed. Confirm with query Q1 below: all six
--     objects still show root@localhost. Do not edit checks 2 or 3 to make them pass.
--  b) Stopped in section 4 or 5 (any other error, or error 1048 at check 5): some objects may be under dbmasteruser@% and
--     others under root@localhost, and if it stopped between a DROP TRIGGER and its CREATE, that trigger is missing.
--     Then, at once:
--       1. Run definer-repair-forward-20261011.sql. It puts all six back under root@localhost with the same SQL, and it
--          has its own server check and post-checks.
--       2. Run Q1: expect root@localhost on all six objects and three triggers on driver_allocation.
--       3. Run Q2: it lists allocations that have no INSERT audit row (made while a trigger was missing or broken).
--          Do not create audit rows by hand; record the gap in the audit notes and report it.
--       4. If in doubt, test one allocation on the isolated test instance (http://localhost/avenir_rv/), not on development.
--  c) Completed, but the change must be undone: the same steps as b).
--  d) The forward repair also fails: restore nothing over `avenir`. The definitions are in avenir_backup_20261011_post_r5 and
--     in the independent export; ask for help before any further change.
--
--  Q1 (read-only): the six objects now
--  SELECT 'view' AS kind, TABLE_NAME AS name, DEFINER AS definer, SECURITY_TYPE AS security_or_sql_mode, MD5(VIEW_DEFINITION) AS fingerprint
--    FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('acc_selct', 'account_runing_q2', 'account_runing_q21')
--  UNION ALL
--  SELECT 'trigger', TRIGGER_NAME, DEFINER, SQL_MODE, MD5(ACTION_STATEMENT)
--    FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'driver_allocation'
--  ORDER BY 1, 2;
--
--  Q2 (read-only): allocations without their INSERT audit row
--  SELECT d.id AS allocation_id, d.tour_id, d.driver FROM driver_allocation d
--    LEFT JOIN driver_allocation_audit a ON a.record_id = d.id AND a.operation = 'INSERT'
--   WHERE a.audit_id IS NULL ORDER BY d.id;
