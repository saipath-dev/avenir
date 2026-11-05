-- tf-supmail-r3 (4 Nov 2026, Shantha): the time a service is booked for, on the INQUIRY.
-- The confirmed line already carries a time (tf_service_job.on_time). This puts it one step earlier, so a
-- time agreed while the tour is still being quoted travels with the line when the tour is confirmed, instead
-- of having to be entered again on the desk. Same shape as the meal's own time on this table (VARCHAR(5),
-- "HH:MM"). NULL until somebody enters it; nothing is ever guessed.
-- Additive and idempotent: one nullable column, nothing else touched.
ALTER TABLE `tf_intake_service` ADD COLUMN `on_time` VARCHAR(5) NULL DEFAULT NULL AFTER `meal_time`;
