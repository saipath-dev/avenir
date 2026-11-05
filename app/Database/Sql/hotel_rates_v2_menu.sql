-- ============================================================================
-- Hotel Rates (new) — menu entries under Master Files + grants to System Administrator
--
-- Two entries, because the system's permission procedure is "one menu URL =
-- one privilege" (UserPermissionFilter allows a request whose path is a
-- sub-path of a granted menu URL):
--
--   Hotel Rates (new)     master_files/hotel_rate_v2        view, enter drafts, import as drafts, quotes, checks
--   Hotel Rate Approval   master_files/hotel_rate_approval  approve / supersede / correct approved plans,
--                                                            import with approval, merge hotels, Settings
--   Hotel Incomplete Work master_files/hotel_work           the work queue: what the system found missing
--                                                            (no contract rate, drafts, missing suppliers, margins …)
--
-- Idempotent: safe to re-run. Grants only the System Administrator group (1);
-- give other groups access through Settings > Group Privileges (or add group
-- ids to the CROSS JOIN below).
--
-- show_in_menu_bar = 0 is what the Menu List screen writes: the entry appears
-- in the sidebar under its category AND as a tile inside that category's
-- modal on the tile dashboard (both loops keep rows with show_in_menu_bar != 1).
-- A row with show_in_menu_bar = 1 is a privilege only (hidden from both). A
-- tile in another category (e.g. Ground Handling, cat_id 25) is one more row
-- with the same url under that category (see app/Database/Seeds/quick_actions_tiles.sql).
--
-- Run with:  php spark hotelrates:install --menu     (or paste into phpMyAdmin)
-- ============================================================================

SET @admin_uid := (SELECT MIN(id) FROM user_acc_tbl WHERE group_id = 1);
SET @mf_cat    := (SELECT id FROM menu_category WHERE category = 'Master Files' AND del = 0 ORDER BY id LIMIT 1);
SET @sa_group  := 1;

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @mf_cat, 'Hotel Rates (new)', COALESCE(@admin_uid, 0), 0, 'master_files/hotel_rate_v2',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @mf_cat), 0) + 1, 0
WHERE @mf_cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/hotel_rate_v2' AND `del` = 0);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @mf_cat, 'Hotel Rate Approval', COALESCE(@admin_uid, 0), 0, 'master_files/hotel_rate_approval',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @mf_cat), 0) + 1, 0
WHERE @mf_cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/hotel_rate_approval' AND `del` = 0);

INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @mf_cat, 'Hotel Incomplete Work', COALESCE(@admin_uid, 0), 0, 'master_files/hotel_work',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @mf_cat), 0) + 1, 0
WHERE @mf_cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/hotel_work' AND `del` = 0);

-- hotel-dup-r1 (27 Oct 2026): Hotel Master — add, edit or remove hotels (Management actions need the Tour Desk Manage privilege too)
INSERT INTO `menu_list` (`cat_id`, `name`, `user`, `del`, `url`, `order_id`, `show_in_menu_bar`)
SELECT @mf_cat, 'Hotel Master', COALESCE(@admin_uid, 0), 0, 'master_files/hotel_master',
       COALESCE((SELECT MAX(order_id) FROM menu_list ml2 WHERE ml2.cat_id = @mf_cat), 0) + 1, 0
WHERE @mf_cat IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menu_list` WHERE `url` = 'master_files/hotel_master' AND `del` = 0);

INSERT INTO `user_privileges` (`group_id`, `user_id`, `menu_id`)
SELECT grp.gid, COALESCE(@admin_uid, 0), ml.`id`
FROM `menu_list` ml
CROSS JOIN (SELECT @sa_group AS gid) grp
WHERE ml.`url` IN ('master_files/hotel_rate_v2', 'master_files/hotel_rate_approval', 'master_files/hotel_work', 'master_files/hotel_master') AND ml.`del` = 0
  AND NOT EXISTS (SELECT 1 FROM `user_privileges` up WHERE up.`group_id` = grp.gid AND up.`menu_id` = ml.`id`);
