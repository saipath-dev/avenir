-- ============================================================================
-- Ground Handling action tiles — data-driven action cards inside the
-- Ground Handling category modal on the tile dashboard
-- (app/Views/pages/dashboard2/tile_dashboard.php)
--
-- Adds the 7 "action tiles" (formerly the hardcoded top action row) as
-- menu_list rows UNDER the existing Ground Handling category (cat_id = 25),
-- and grants them to the Operations group (13) and System Administrator (1).
--
-- They render as icon cards INSIDE the Ground Handling modal (the modal loop
-- filters `show_in_menu_bar != 1`, so these rows MUST have show_in_menu_bar=0).
-- The top action row and the top-row Pilot Report tile were removed from the
-- view; these 7 now live only in the GH modal, per group grant.
--
-- Click behavior is view-owned via data-action (a menu name -> behavior map):
--   Tour Search / Hotel Management / Transport  -> data-action="modal"    (open tourSearchModal)
--   New Inquiry / New Booking / Transport Portal / Ongoing Tour Transport -> data-action="redirect" (window.location)
-- The `url` on the 3 modal tiles is only a permission key (dashboard2/tiles,
-- a route these groups already hold); it is NOT used for navigation.
--
-- IDs assigned by this seed on the live DB (for reference):
--   menu_list rows:  201 Tour Search, 202 New Inquiry, 203 New Booking,
--                    204 Hotel Management, 205 Transport, 206 Transport Portal,
--                    207 Ongoing Tour Transport
--   (originally created under a temporary "Quick Actions" category id 28,
--    then moved to cat_id 25; category 28 was soft-deleted.)
--
-- Idempotent: safe to re-run. Guards prevent duplicate menu rows / grants.
-- `user` column = admin who created the row (33740 = super admin).
-- ============================================================================

SET @admin_uid := 33740;   -- creator stamp (super admin)
SET @gh_cat    := 25;      -- Ground Handling category id
SET @ops_group := 13;      -- Operations group_id
SET @sa_group  := 1;       -- System Administrator group_id

-- ---------------------------------------------------------------------------
-- 1) The 6 action tiles as menu_list rows under Ground Handling (cat_id 25).
--    show_in_menu_bar = 0 so they appear inside the GH modal as icon cards.
--    order_id offset (+100) keeps them after GH's real sub-items if desired;
--    adjust to taste. Re-run-safe: insert each only if absent under cat 25.
-- ---------------------------------------------------------------------------
INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @gh_cat, 'Tour Search', @admin_uid, 0, 'dashboard2/tiles', 101, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `cat_id`=@gh_cat AND `name`='Tour Search' AND `del`=0);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @gh_cat, 'New Inquiry', @admin_uid, 0, 'inquiries', 102, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `cat_id`=@gh_cat AND `name`='New Inquiry' AND `del`=0);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @gh_cat, 'New Booking', @admin_uid, 0, 'dashboard2/tour_search/new_quotation', 103, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `cat_id`=@gh_cat AND `name`='New Booking' AND `del`=0);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @gh_cat, 'Hotel Management', @admin_uid, 0, 'dashboard2/tiles', 104, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `cat_id`=@gh_cat AND `name`='Hotel Management' AND `del`=0);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @gh_cat, 'Transport', @admin_uid, 0, 'dashboard2/tiles', 105, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `cat_id`=@gh_cat AND `name`='Transport' AND `del`=0);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @gh_cat, 'Transport Portal', @admin_uid, 0, 'dashboard2/transport/transport_portal', 106, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `cat_id`=@gh_cat AND `name`='Transport Portal' AND `del`=0);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @gh_cat, 'Ongoing Tour Transport', @admin_uid, 0, 'dashboard2/transport/transport_ongoing', 107, 0
WHERE NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `cat_id`=@gh_cat AND `name`='Ongoing Tour Transport' AND `del`=0);

-- ---------------------------------------------------------------------------
-- 2) Grant the 6 tiles to Operations (13) and System Administrator (1).
--    Add more group_ids here to expose the action tiles to other groups.
--    Re-run-safe: skip any (group,menu) grant that already exists.
-- ---------------------------------------------------------------------------
INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT grp.gid, @admin_uid, ml.`id`
FROM `menu_list` ml
CROSS JOIN (SELECT @ops_group AS gid UNION ALL SELECT @sa_group) grp
WHERE ml.`cat_id` = @gh_cat
  AND ml.`del` = 0
  AND ml.`name` IN ('Tour Search','New Inquiry','New Booking','Hotel Management','Transport','Transport Portal','Ongoing Tour Transport')
  AND NOT EXISTS (
      SELECT 1 FROM `user_privileges` up
      WHERE up.`group_id` = grp.gid AND up.`menu_id` = ml.`id`
  );

-- ---------------------------------------------------------------------------
-- Verify: action tiles visible in the GH modal for a given group
--   (menu_id 144 = the original "Ground Handling" card also shows)
-- ---------------------------------------------------------------------------
-- SELECT ml.id, ml.name, ml.url, ml.show_in_menu_bar
-- FROM user_privileges up
-- JOIN menu_list ml ON ml.id = up.menu_id
-- WHERE up.group_id = 13 AND ml.cat_id = 25 AND ml.del = 0 AND ml.show_in_menu_bar = 0
-- ORDER BY ml.order_id;
