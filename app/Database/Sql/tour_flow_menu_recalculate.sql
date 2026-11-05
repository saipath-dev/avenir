-- Recalculate saved costings (tf-stage4, 29 Oct 2026, schema 53).
-- One menu URL = one privilege, a RIGHT and not a menu line (show_in_menu_bar = 1): it lets a holder press Recalculate or
-- Keep saved figures on an inquiry — nothing else. Granted to the System Administrator, Management and the Quotation Desk.
-- A Quotation Desk holder acts only on the inquiries they handle (the code checks the handler); Management and the System
-- Administrator on any. Admin changes the grants under Settings → Group Privileges. Additive; safe to re-run.
SET @admin_uid  := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @tour_cat   := (SELECT cat_id FROM menu_list WHERE url = 'tour/intake' AND del = 0 ORDER BY id LIMIT 1);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @tour_cat, 'Recalculate saved costings', COALESCE(@admin_uid, 0), 0, 'tour/intake/recalculate',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @tour_cat), 0) + 1, 1
WHERE @tour_cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'tour/intake/recalculate' AND `del` = 0);

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT g.id, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
JOIN `group_profile` g ON g.del = 0 AND g.type IN ('System Administrator', 'Management', 'Quotation Desk')
WHERE ml.`url` = 'tour/intake/recalculate' AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = g.id AND up.`menu_id` = ml.`id`);
