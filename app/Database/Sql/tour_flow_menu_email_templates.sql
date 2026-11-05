-- Email Templates — menu entry under Master Files, granted to the System Administrator only (tf-mailtpl-r1, 4 Nov 2026).
-- The saved wording of every email the system sends: quotation and itinerary, tour confirmation, hotel request and
-- voucher, restaurant request and voucher, driver/guide/transport request, activity and other supplier request.
-- One menu URL = one privilege; nobody else is granted it here — Admin grants it under Group Privileges if instructed.
-- Additive; safe to re-run.
SET @admin_uid  := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @master_cat := (SELECT id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1);
SET @tour_cat   := (SELECT id FROM menu_category WHERE category = 'Tour' AND del = 0 ORDER BY id LIMIT 1);
SET @cat        := COALESCE(@master_cat, @tour_cat);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @cat, 'Email Templates', COALESCE(@admin_uid, 0), 0, 'master_files/email_templates',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @cat), 0) + 1, 0
WHERE @cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/email_templates' AND `del` = 0);

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT 1, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
WHERE ml.`url` = 'master_files/email_templates' AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = 1 AND up.`menu_id` = ml.`id`);
