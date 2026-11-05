AVENIR — TEST COPY FOR THE REAL E-MAIL WORKFLOW TEST (avenir_mailtest)
=====================================================================

What this folder is
  A separate copy of the Avenir program code, with the two reviewed release kits (tf-tour-booking-r1, tf-mail-r1),
  used ONCE to test the new hotel-mail process with the real bookings mailbox (htl.res@avenirholidays.com) and
  DUMMY data only. It is not the live system.

  Address      http://localhost/avenir_mailtest/      (opens only on this laptop: Apache "Require local")
  Database     avenir_mailtest  (its own; the live databases avenir, inquiries and avenir_2024 are never used —
               app/Config/Database.php forces every connection to avenir_mailtest)
  Sign-in      mailtestadmin, with the password Shantha chooses on the setup page (not recorded anywhere here)
  Dummy data   one tour TEST-MAIL-1008, one stay at "TEST HOTEL (dummy — not a real hotel)", 10–12 Dec 2026,
               whose booking e-mail is the test recipient's own address. No real hotel, agent or customer.
  Red bar      every page of this copy shows a red "TEST COPY" bar at the bottom.
  Cookies      its own (avenir_mailtest_session), so signing in here never signs anyone out of the live system.

What it does NOT have
  No live .env (no WhatsApp, SMS, AI or legacy e-mail keys of its own), no uploads, no live data, no scheduled task.
  Automatic sending is OFF: a letter goes only when the Send button is pressed and confirmed.

Mailbox password
  Typed by Shantha on this copy's settings screen, kept sealed with this copy's own key (.env, never shown).
  It goes away with the test database when the copy is removed.

Certificate checking
  ON, always. While Norton's e-mail scanning is on, the copy refuses to connect (Norton's own certificate is not
  Lolipop's). That refusal is correct and is itself one of the checks.

After the test (removal)
  1. http://localhost/avenir_mailtest/mailtest_setup.php → "Remove the test database" (type REMOVE avenir_mailtest).
  2. Delete this folder C:\wamp64\www\avenir_mailtest (or Claude moves it into a _to_delete folder for you).
