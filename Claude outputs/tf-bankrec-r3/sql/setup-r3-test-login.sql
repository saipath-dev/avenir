-- tf-bankrec-r3: ONE TEST login for the dual-role test, on the ISOLATED TEST BED (avenir_restore_verify_20261011) only.
-- TestRecDual, in a new TEST group "TEST Reconciliation preparer + reviewer (test)" holding only the three Daily Bank
-- Reconciliation privileges (view, prepare, review) — to prove that a user who holds both may still not approve or review
-- their own work. Not an administrator. No existing login or group is changed. Password: the test-login password.
-- Removed by sql/reset-to-before-r3.sql. This is not a role decision for real staff (role assignments stay open).

-- Guard: stops (error 1048) unless this is the isolated test-bed database and the TEST login does not exist yet.
DROP TEMPORARY TABLE IF EXISTS brc_r3_guard;
CREATE TEMPORARY TABLE brc_r3_guard (ok TINYINT NOT NULL);
INSERT INTO brc_r3_guard (ok) VALUES (IF(DATABASE() = 'avenir_restore_verify_20261011', 1, NULL));
INSERT INTO brc_r3_guard (ok) VALUES (IF((SELECT COUNT(*) FROM user_acc_tbl WHERE user = 'TestRecDual') = 0, 1, NULL));
INSERT INTO brc_r3_guard (ok) VALUES (IF((SELECT COUNT(*) FROM group_profile WHERE type = 'TEST Reconciliation preparer + reviewer (test)') = 0, 1, NULL));

INSERT INTO group_profile (type, user, del) VALUES ('TEST Reconciliation preparer + reviewer (test)', 33740, 0);
SET @g = LAST_INSERT_ID();
INSERT INTO employee (emp_num, empno, Acc_No, Name) VALUES ('200160', '200160', '', 'TEST Reconciliation dual-role tester (test)');
INSERT INTO user_acc_tbl (emp_id, user, pass, acc_no, group_id, access, login_type, user_role) VALUES (200160, 'TestRecDual', '$2y$10$.MvNvZitwYoCs1uJxvESR./pshrFgKyMsgzpAlYFvQt48g4G5xL9e', NULL, @g, 1, NULL, 0);
INSERT INTO user_privileges (group_id, user_id, menu_id) SELECT @g, 33740, id FROM menu_list WHERE url IN ('accounts/daily_bank_rec', 'accounts/daily_bank_rec/prepare', 'accounts/daily_bank_rec/review') AND del = 0;
INSERT INTO tpd_audit (at, user, action, ref_type, ref_id, reason) SELECT DATE_FORMAT(NOW(3), '%Y-%m-%d %H:%i:%s.%f'), 'claude-technical (setup script run in phpMyAdmin; isolated test instance)', 'brc_setup', 'brc_setup', 'r3 test login', 'tf-bankrec-r3: TEST login TestRecDual (view + prepare + review) for the dual-role test' FROM DUAL;
DROP TEMPORARY TABLE IF EXISTS brc_r3_guard;
SELECT u.id, u.user, u.group_id, g.type, (SELECT GROUP_CONCAT(m.url ORDER BY m.id) FROM user_privileges p JOIN menu_list m ON m.id = p.menu_id WHERE p.group_id = u.group_id) AS privileges FROM user_acc_tbl u JOIN group_profile g ON g.id = u.group_id WHERE u.user = 'TestRecDual';
