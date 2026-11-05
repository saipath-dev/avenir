-- Enter the daily USD rate — the Home-page box (tf-stage2, 29 Oct 2026, schema 52).
-- One menu URL = one privilege, a RIGHT and not a menu line (show_in_menu_bar = 1): it grants nothing else, no settings, no Finance
-- or Administrator page. Granted here to the groups the Development Plan names: Quotation Desk, Transport & Crew Guide Allocation Desk,
-- Transport & Guide Handling, Accounts, Accountant, Finance & Approvals (both), System Administrator. Admin changes the grants
-- under Settings → Group Privileges. Additive; safe to re-run.
SET @admin_uid  := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @master_cat := (SELECT id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @master_cat, 'Enter the daily USD rate', COALESCE(@admin_uid, 0), 0, 'home/usd_rate',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @master_cat), 0) + 1, 1
WHERE @master_cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'home/usd_rate' AND `del` = 0);

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT g.id, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
JOIN `group_profile` g ON g.del = 0 AND g.type IN ('System Administrator', 'Quotation Desk', 'Transport & Crew Guide Allocation Desk', 'Transport & Guide Handling', 'Accounts', 'Accountant', 'Finance & Approvals — Management Approver', 'Finance & Approvals — Bank Authoriser')
WHERE ml.`url` = 'home/usd_rate' AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = g.id AND up.`menu_id` = ml.`id`);
