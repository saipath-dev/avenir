-- Tour Photos — menu entry under Master Files, granted to the System Administrator (schema 27).
-- The photo library the client quotation draws on: tags (destinations, attractions,
-- activities and the words that point at them) and three to five good photos for each.
-- One menu URL = one privilege; the installer's desk seed grants Management too.
-- Additive; safe to re-run.
SET @admin_uid  := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @tour_cat   := (SELECT id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1);
SET @fallback   := (SELECT id FROM menu_category WHERE category = 'Tour' AND del = 0 ORDER BY id LIMIT 1);
SET @cat        := COALESCE(@tour_cat, @fallback);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @cat, 'Tour Photos', COALESCE(@admin_uid, 0), 0, 'master_files/tour_photos',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @cat), 0) + 1, 0
WHERE @cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/tour_photos' AND `del` = 0);

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT 1, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
WHERE ml.`url` = 'master_files/tour_photos' AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = 1 AND up.`menu_id` = ml.`id`);
