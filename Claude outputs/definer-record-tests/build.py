import json, sys
J = json.load(open('views.json'))
V, T = J['views'], J['trigs']
DEV = dict(host='ShanthaW', port=3306, version='8.4.7', uuid='d52e1dce-aa9a-11f1-8e2d-e446b01a6b9e', db='avenir')
VMD5 = {'acc_selct': 'd95f47d351a2d9f27f3b8f43b43098c1', 'account_runing_q2': 'f030b78a240c3de25c9733aa78be9621', 'account_runing_q21': '1d4f3dd6c80c39b4da5e09c0399428ea'}
TMD5 = {'driver_allocation_after_insert': ('INSERT', 'd21612dbdd00ea7fcc42fb6377682aac'), 'driver_allocation_after_delete': ('DELETE', '96d9f654c04d93f8a7c806e4a1cc14cb'), 'driver_allocation_after_update': ('UPDATE', 'ee77d484e2415657d979eb335deb61c6')}

def identity(idn):
    return (f"IF(@@hostname = '{idn['host']}' AND @@port = {idn['port']} AND @@version = '{idn['version']}' "
            f"AND @@server_uuid = '{idn['uuid']}' AND DATABASE() = '{idn['db']}', 1, NULL)")

def views_ok(definer):
    conds = ' OR '.join(f"(TABLE_NAME = '{n}' AND MD5(VIEW_DEFINITION) = '{m}')" for n, m in VMD5.items())
    return (f"IF((SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND DEFINER = '{definer}' "
            f"AND SECURITY_TYPE = 'DEFINER' AND CHECK_OPTION = 'NONE' AND ({conds})) = 3, 1, NULL)")

def trigs_ok(definer):
    conds = ' OR '.join(f"(TRIGGER_NAME = '{n}' AND EVENT_MANIPULATION = '{e}' AND MD5(ACTION_STATEMENT) = '{m}')" for n, (e, m) in TMD5.items())
    return (f"IF((SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'driver_allocation' "
            f"AND ACTION_TIMING = 'AFTER' AND DEFINER = '{definer}' AND SQL_MODE = 'NO_ENGINE_SUBSTITUTION' AND ({conds})) = 3, 1, NULL)")

def only3():
    return "IF((SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'driver_allocation') = 3, 1, NULL)"

Q1 = """SELECT 'view' AS kind, TABLE_NAME AS name, DEFINER AS definer, SECURITY_TYPE AS security_or_sql_mode, MD5(VIEW_DEFINITION) AS fingerprint
  FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('acc_selct', 'account_runing_q2', 'account_runing_q21')
UNION ALL
SELECT 'trigger', TRIGGER_NAME, DEFINER, SQL_MODE, MD5(ACTION_STATEMENT)
  FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'driver_allocation'
ORDER BY 1, 2;"""
Q2 = """SELECT d.id AS allocation_id, d.tour_id, d.driver FROM driver_allocation d
  LEFT JOIN driver_allocation_audit a ON a.record_id = d.id AND a.operation = 'INSERT'
 WHERE a.audit_id IS NULL ORDER BY d.id;"""

def ddl(definer):
    out = []
    for n in ['acc_selct', 'account_runing_q2', 'account_runing_q21']:
        out.append(f"-- view {n}\n" + V[n].replace("DEFINER='dbmasteruser'@'%'", definer) + "\n")
    out.append("-- the triggers: driver_allocation is locked for writing, so no allocation can be saved between a DROP and its CREATE\nLOCK TABLES `driver_allocation` WRITE;\n")
    for n in ['driver_allocation_after_delete', 'driver_allocation_after_insert', 'driver_allocation_after_update']:
        out.append(f"-- trigger {n}\nDROP TRIGGER IF EXISTS `{n}`;\nDELIMITER $$\n" + T[n].replace("DEFINER='dbmasteruser'@'%'", definer) + "\nDELIMITER ;\n")
    out.append("UNLOCK TABLES;\n")
    return '\n'.join(out)

HEAD_GUARD = """USE `{db}`;
SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';   -- the sql_mode the three triggers are stored with
DROP TEMPORARY TABLE IF EXISTS `_definer_guard`;
CREATE TEMPORARY TABLE `_definer_guard` (`step` VARCHAR(160) NOT NULL, `passed` TINYINT NOT NULL);
"""

def rollback(idn, approval_line="SET @rollback_approved = NULL;"):
    return f"""-- =====================================================================================================================
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
--  backup (avenir_backup_20261011_post_r5) and its independent export (C:\\wamp64\\tmp\\avenir-exports-20261011\\).
--  It may be run only with Shantha's written approval, and only after the approval line in section 1 has been edited.
--
--  How it protects itself. Each check inserts one row into a temporary table whose `passed` column cannot be NULL. A failed
--  check inserts NULL, so the script stops at once with error 1048 "Column 'passed' cannot be null". Both phpMyAdmin and the
--  mysql client stop at the first error, so nothing after a failed check runs. The temporary table disappears when the
--  connection closes; nothing else is created.
--
--  Sections
--    1  Approval check       stops unless @rollback_approved has been set to a written-approval reference
--    2  Server check         stops unless this is the development server: {idn['host']}, port {idn['port']}, MySQL {idn['version']},
--                            server_uuid {idn['uuid']}, database {idn['db']}
--    3  Pre-checks           stops unless the six objects are exactly in the repaired state recorded on 11 Oct 2026
--    4  The change           the views first, then the triggers while driver_allocation is locked
--    5  Post-checks          stops (after the change) if any of the six objects is not as expected, and lists them
--    6  Recovery procedure   what to do if the run stops part-way (read it before any run)
--
--  Run it as one import in phpMyAdmin with the database {idn['db']} selected, or with the mysql client started with
--  --comments (without it the client strips the comment inside the trigger bodies and check 5b stops the run). Never split it.
-- =====================================================================================================================

{HEAD_GUARD.format(db=idn['db'])}
-- 1. Approval check. Leave this line as it is. Only with Shantha's written approval, replace NULL with the approval
--    reference, for example 'Shantha, e-mail of 2026-10-12'.
{approval_line}
INSERT INTO `_definer_guard` VALUES ('1 approval: @rollback_approved must hold a written-approval reference',
  IF(CHAR_LENGTH(COALESCE(@rollback_approved, '')) >= 10, 1, NULL));

-- 2. Server check.
INSERT INTO `_definer_guard` VALUES ('2 server: {idn['host']} / {idn['port']} / MySQL {idn['version']} / {idn['uuid']} / database {idn['db']}',
  {identity(idn)});

-- 3. Pre-checks: the six objects are in the repaired state (root@localhost, same SQL as recorded on 11 Oct 2026).
INSERT INTO `_definer_guard` VALUES ('3a pre: the three views are under root@localhost with the recorded definitions',
  {views_ok('root@localhost')});
INSERT INTO `_definer_guard` VALUES ('3b pre: the three triggers are under root@localhost, NO_ENGINE_SUBSTITUTION, recorded bodies',
  {trigs_ok('root@localhost')});
INSERT INTO `_definer_guard` VALUES ('3c pre: driver_allocation has exactly these three triggers', {only3()});
SET @allocations_before = (SELECT COUNT(*) FROM `driver_allocation`), @audit_before = (SELECT COUNT(*) FROM `driver_allocation_audit`);
SELECT * FROM `_definer_guard`;

-- 4. The change (original definitions, byte for byte as read from the server on 11 Oct 2026).
{ddl("DEFINER='dbmasteruser'@'%'")}
-- 5. Post-checks.
INSERT INTO `_definer_guard` VALUES ('5a post: the three views are under dbmasteruser@% with the recorded definitions', {views_ok('dbmasteruser@%')});
INSERT INTO `_definer_guard` VALUES ('5b post: the three triggers are under dbmasteruser@% with the recorded bodies and sql_mode', {trigs_ok('dbmasteruser@%')});
INSERT INTO `_definer_guard` VALUES ('5c post: no allocation or audit row was written during the change',
  IF((SELECT COUNT(*) FROM `driver_allocation`) = @allocations_before AND (SELECT COUNT(*) FROM `driver_allocation_audit`) = @audit_before, 1, NULL));
SELECT * FROM `_definer_guard`;
{Q1}

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
--  {Q1.replace(chr(10), chr(10) + '--  ')}
--
--  Q2 (read-only): allocations without their INSERT audit row
--  {Q2.replace(chr(10), chr(10) + '--  ')}
"""

def forward(idn):
    return f"""-- =====================================================================================================================
--  definer-repair-forward-20261011.sql: RECOVERY FILE for the development system
-- =====================================================================================================================
--  Re-applies the definer repair of 11 Oct 2026: the three views and three driver_allocation triggers in `avenir` under
--  root@localhost (the account the application connects as), with exactly the recorded SQL, trigger bodies and sql_mode.
--  This is the state the development system is in now. Use it only to recover from a partial or unwanted run of the
--  rollback record (definer-repair-rollback-20261011.sql, section 6). Running it on the repaired system changes nothing
--  that matters: it recreates the same objects under the same account.
--
--  It stops with error 1048 unless this is the development server ({idn['host']}, port {idn['port']}, MySQL {idn['version']},
--  server_uuid {idn['uuid']}, database {idn['db']}). Run it as one import in phpMyAdmin with the database {idn['db']} selected,
--  or with the mysql client started with --comments (it keeps the comment inside the trigger bodies).
-- =====================================================================================================================

{HEAD_GUARD.format(db=idn['db'])}
INSERT INTO `_definer_guard` VALUES ('server: {idn['host']} / {idn['port']} / MySQL {idn['version']} / {idn['uuid']} / database {idn['db']}',
  {identity(idn)});
SET @allocations_before = (SELECT COUNT(*) FROM `driver_allocation`), @audit_before = (SELECT COUNT(*) FROM `driver_allocation_audit`);

{ddl("DEFINER='root'@'localhost'")}
INSERT INTO `_definer_guard` VALUES ('post: the three views are under root@localhost with the recorded definitions', {views_ok('root@localhost')});
INSERT INTO `_definer_guard` VALUES ('post: the three triggers are under root@localhost with the recorded bodies and sql_mode', {trigs_ok('root@localhost')});
INSERT INTO `_definer_guard` VALUES ('post: driver_allocation has exactly these three triggers', {only3()});
INSERT INTO `_definer_guard` VALUES ('post: no allocation or audit row was written during the change',
  IF((SELECT COUNT(*) FROM `driver_allocation`) = @allocations_before AND (SELECT COUNT(*) FROM `driver_allocation_audit`) = @audit_before, 1, NULL));
SELECT * FROM `_definer_guard`;
{Q1}
-- then run Q2 (allocations without their INSERT audit row):
{Q2}
"""

if __name__ == '__main__':
    mode = sys.argv[1]; out = sys.argv[2]
    idn = dict(DEV)
    if len(sys.argv) > 3:
        idn.update(json.loads(sys.argv[3]))
    approval = sys.argv[4] if len(sys.argv) > 4 else "SET @rollback_approved = NULL;"
    s = rollback(idn, approval) if mode == 'rollback' else forward(idn)
    open(out, 'w', newline='\n').write(s)
    print(out, len(s))
