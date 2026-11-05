-- tf-discount-r1 (1 Nov 2026; renamed 2 Nov 2026). One menu URL = one privilege, a RIGHT not a menu line.
-- The page is called Tour Discounts and sits immediately after Pricing in the Tour category.
-- Granted to the System Administrator ONLY so the panel can be exercised on the isolated copy;
-- no other group receives it, and the controller checks the privilege on every call regardless.
SET @admin_uid := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @cat := (SELECT id FROM menu_category WHERE category = 'Tour' AND del = 0 ORDER BY id LIMIT 1);

-- Placement WITHOUT moving anything else: the entry takes Pricing's own order_id, so no other menu
-- entry's order_id is touched. MenuLoader orders by order_id then menu_id, and this entry's id is the
-- higher of the two, so it renders immediately after Pricing.
SET @after := (SELECT order_id FROM menu_list WHERE cat_id = @cat AND url = 'tour/pricing' AND del = 0 ORDER BY id LIMIT 1);
SET @slot  := COALESCE(@after, (SELECT MAX(order_id) FROM menu_list m2 WHERE m2.cat_id = @cat));

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @cat, 'Tour Discounts', COALESCE(@admin_uid, 0), 0, 'tour/pricing/discount', @slot, 1
WHERE @cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'tour/pricing/discount' AND `del` = 0);

-- an installation made before this correction carries the old label or the old slot
UPDATE `menu_list` SET `name` = 'Tour Discounts', `order_id` = @slot
WHERE `url` = 'tour/pricing/discount' AND `del` = 0 AND @slot IS NOT NULL;

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT g.id, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
JOIN `group_profile` g ON g.del = 0 AND g.type = 'System Administrator'
WHERE ml.`url` = 'tour/pricing/discount' AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = g.id AND up.`menu_id` = ml.`id`);
