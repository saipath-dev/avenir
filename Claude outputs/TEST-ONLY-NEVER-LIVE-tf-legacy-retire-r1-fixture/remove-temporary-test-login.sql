-- TEST ONLY — NEVER LIVE — NEVER DEVELOPMENT. Isolated test bed avenir_restore_verify_20261011 only.
-- Removes the temporary test login TestLegacyTools, its group, its employee row and its privilege rows.
DROP TEMPORARY TABLE IF EXISTS lr_guard;
CREATE TEMPORARY TABLE lr_guard (ok TINYINT NOT NULL);
INSERT INTO lr_guard (ok) VALUES (IF(DATABASE() = 'avenir_restore_verify_20261011', 1, NULL));
SET @g = (SELECT group_id FROM user_acc_tbl WHERE user = 'TestLegacyTools');
INSERT INTO lr_guard (ok) VALUES (IF((SELECT type FROM group_profile WHERE id = @g) = 'TEST Legacy tools holder (isolated test instance)', 1, NULL));
DELETE FROM user_privileges WHERE group_id = @g;
DELETE FROM user_acc_tbl WHERE user = 'TestLegacyTools' AND group_id = @g;
DELETE FROM employee WHERE emp_num = '200170' AND Name = 'TEST Legacy tools tester (isolated test instance)';
DELETE FROM group_profile WHERE id = @g AND type = 'TEST Legacy tools holder (isolated test instance)';
DROP TEMPORARY TABLE IF EXISTS lr_guard;
SELECT (SELECT COUNT(*) FROM user_acc_tbl WHERE user = 'TestLegacyTools') AS login_left,
       (SELECT COUNT(*) FROM group_profile WHERE type = 'TEST Legacy tools holder (isolated test instance)') AS group_left,
       (SELECT COUNT(*) FROM user_privileges WHERE group_id = @g) AS privileges_left,
       (SELECT COUNT(*) FROM employee WHERE emp_num = '200170') AS employee_left;
