-- Staff Assignment (tf-stage10, 30 Oct 2026, schema 57). One menu URL = one privilege, a RIGHT and not a menu line
-- (show_in_menu_bar = 1). Granted to the System Administrator ONLY; the controller refuses every other group on every call
-- regardless of grants (Shantha, 30 Oct 2026: Staff Assignment is Super Admin only). Additive; safe to re-run.
SET @admin_uid  := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @master_cat := (SELECT id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @master_cat, 'Staff Assignment', COALESCE(@admin_uid, 0), 0, 'master_files/staff',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @master_cat), 0) + 1, 1
WHERE @master_cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/staff' AND `del` = 0);

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT g.id, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
JOIN `group_profile` g ON g.del = 0 AND g.type = 'System Administrator'
WHERE ml.`url` = 'master_files/staff' AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = g.id AND up.`menu_id` = ml.`id`);
