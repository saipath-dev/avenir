-- tf-supmail-r2 (4 Nov 2026, Shantha): the time an activity is booked for.
-- A supplier asked to hold a jeep, a boat or an entrance needs the hour as well as the day. The column is
-- NULL until somebody enters it; nothing guesses a time, and a request whose wording asks for one is held
-- on the desk until it is entered. Additive and idempotent: one nullable column, nothing else touched.
ALTER TABLE `tf_service_job` ADD COLUMN `on_time` TIME NULL DEFAULT NULL AFTER `on_date`;
