-- TEST ONLY — NEVER LIVE — NEVER DEVELOPMENT. Isolated test bed avenir_restore_verify_20261011 only.
-- Temporary test login TestLegacyTools (tf-legacy-retire-r1 refusal tests). Remove it afterwards: remove-temporary-test-login.sql
-- Before running: replace the placeholder with the bcrypt hash of a NEW one-time password made for this run (never reuse
-- another login's password; never store the password or the hash anywhere).
SET @pass_hash = '<<BCRYPT HASH OF A NEW ONE-TIME PASSWORD — PASTED AT RUN TIME, NEVER STORED>>';
DROP TEMPORARY TABLE IF EXISTS lr_guard;
CREATE TEMPORARY TABLE lr_guard (ok TINYINT NOT NULL);
INSERT INTO lr_guard (ok) VALUES (IF(DATABASE() = 'avenir_restore_verify_20261011', 1, NULL));
INSERT INTO lr_guard (ok) VALUES (IF(@pass_hash LIKE '$2y$%' AND CHAR_LENGTH(@pass_hash) = 60, 1, NULL));
INSERT INTO lr_guard (ok) VALUES (IF((SELECT COUNT(*) FROM user_acc_tbl WHERE pass = @pass_hash) = 0, 1, NULL));
INSERT INTO lr_guard (ok) VALUES (IF((SELECT COUNT(*) FROM user_acc_tbl WHERE user = 'TestLegacyTools') = 0, 1, NULL));
INSERT INTO group_profile (type, user, del) VALUES ('TEST Legacy tools holder (isolated test instance)', 1, 0);
SET @g = LAST_INSERT_ID();
INSERT INTO employee (emp_num, empno, Acc_No, Name) VALUES ('200170', '200170', '', 'TEST Legacy tools tester (isolated test instance)');
INSERT INTO user_acc_tbl (emp_id, user, pass, acc_no, group_id, access, login_type, user_role) VALUES (200170, 'TestLegacyTools', @pass_hash, NULL, @g, 1, NULL, 0);
INSERT INTO user_privileges (group_id, user_id, menu_id) SELECT @g, 1, id FROM menu_list WHERE id IN (107, 184) AND del = 0;
DROP TEMPORARY TABLE IF EXISTS lr_guard;
SET @pass_hash = NULL;
SELECT u.id, u.user, u.group_id, g.type, (SELECT GROUP_CONCAT(p.menu_id ORDER BY p.menu_id) FROM user_privileges p WHERE p.group_id = u.group_id) AS privileges
  FROM user_acc_tbl u JOIN group_profile g ON g.id = u.group_id WHERE u.user = 'TestLegacyTools';
