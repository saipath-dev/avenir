-- ============================================================================
-- Transport Rates — menu entry and the System Administrator grant.
--
-- One menu URL = one privilege, the rule the rest of the system uses. This adds a
-- single row for master_files/transport_rates under Master Files and grants it to
-- group 1 so the screen can be opened at all; every other group is granted it by
-- hand in Settings → Group Privileges, exactly like the other Master Files entries.
--
-- Additive and safe to re-run: both statements do nothing if the row is already
-- there, and nothing existing is updated or deleted.
-- ============================================================================

SET @admin_uid := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @cat       := (SELECT id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1);
SET @sa_group  := 1;

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @cat, 'Transport Rates', COALESCE(@admin_uid, 0), 0, 'master_files/transport_rates',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @cat), 0) + 1, 0
WHERE @cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/transport_rates' AND `del` = 0);

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT grp.gid, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
CROSS JOIN (SELECT @sa_group AS gid) grp
WHERE ml.`url` = 'master_files/transport_rates' AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = grp.gid AND up.`menu_id` = ml.`id`);
