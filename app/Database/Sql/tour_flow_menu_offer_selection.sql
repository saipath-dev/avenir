-- Tactical / Early Bird rate selection — the privilege behind "Choose the payment rate" on the Tour Payment
-- Dashboard (tf-offers-r1). One menu URL = one privilege: a group holding master_files/offer_selection may choose,
-- for a tour's stay at a hotel, which tactical rate or early bird offer the hotel payment uses (or keep the
-- published rate) when more than one fits. Everybody preparing payments sees the rates and the choice made.
-- Granted here to the System Administrator only; grant it to other groups under Settings → Group Privileges.
-- A privilege, not a screen: show_in_menu_bar = 1 keeps it off every menu and off the tile dashboard (as the
-- Crew replacement entry is), but it is listed under Group Privileges like any entry. Additive; safe to re-run.
SET @admin_uid  := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @mf_cat     := (SELECT id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1);
SET @fallback   := (SELECT id FROM menu_category WHERE category = 'Tour' AND del = 0 ORDER BY id LIMIT 1);
SET @cat        := COALESCE(@mf_cat, @fallback);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @cat, 'Tactical / Early Bird rate selection', COALESCE(@admin_uid, 0), 0, 'master_files/offer_selection',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @cat), 0) + 1, 1
WHERE @cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/offer_selection' AND `del` = 0);

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT 1, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
WHERE ml.`url` = 'master_files/offer_selection' AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = 1 AND up.`menu_id` = ml.`id`);
