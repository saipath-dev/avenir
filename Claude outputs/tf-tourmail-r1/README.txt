tf-tourmail-r1 — the tour's hotel and restaurant e-mails, 10 Oct 2026

Asked (Shantha, 10 Oct): send the reservation mails to the hotels and restaurants automatically when Confirm is pressed;
show the list first and send only on OK; see the sent mails in the system; if a mail is not sent the system must say so.
Also: the letters said "1 DBL" where the agent asked for a Twin room — change to the type the agent asked for and keep
a note; "Driver room required (2 night(s))" was on every hotel — each hotel gets its own nights.

What it does:
 * Confirm Tour (Tour Inquiry): after the restaurants' list, a list of every hotel stay — nights, hotel, the room line
   the letter will carry, crew rooms per hotel, the e-mail address (Hotel Master → supplier record; type one where
   there is none) and a Send tick. Nothing is sent before OK; Cancel sends nothing. Then the password and the final
   warning, which says how many e-mails go. On Confirm Tour the tour is confirmed and each hotel's booking request is
   e-mailed from the person's own mailbox (restaurants' vouchers as before). The result lists every hotel and restaurant:
   sent (to which address) or NOT sent (and why).
 * Room type: where the agent's request (the inquiry's room note, e.g. "1 Twin Room") says twin and the rooms were entered
   as DBL (or the other way round), the letters carry the type the agent asked for — the stay's room line and the
   passengers' (TWN) — and each stay's trail says "room type corrected from … to … — the agent asked for …". The
   quotation and its costing are not touched.
 * Crew rooms: each hotel's letter says its own nights ("Driver room required (2 nights)"). Where the driver's nights
   were set by hand (e.g. 2 of 8 nights) the list asks at which hotels, and shows "2 of 2 entered".
 * Hotel Desk → a tour → "Email all hotels": the same list for a tour already confirmed; stays requested already are shown
   and never sent again. "Mails": the tour's e-mails as they went, each with its copy.
 * Sent Mails (Hotel Bookings → Sent Mails): every hotel request/voucher and restaurant voucher e-mail of every tour —
   who, when, to which address, sent / NOT sent and why, the copy. Filters: All, Not sent, Sent; search.
 * The red "N mail(s) not sent" alert in the top bar on every screen (for a sign-in holding Hotel Bookings): each mail
   that did not go, with why, until it is sent from its stay / line or recorded as sent another way.
 * Fixed on the way: a second e-mail in the same request (two restaurants at Confirm, or several hotels) stopped with
   "Cannot declare class PHPMailer\...\Exception"; and a mail server that does not answer is given up after 45 seconds
   (was 5 minutes), so Confirm comes back with what went and what did not.

Files: new  app/Libraries/TourFlow/TourMailer.php, app/Controllers/Tour/TourMailController.php, app/Config/Routes.tourmail.php,
            app/Views/pages/tour/sent_mails.php, app/Views/layouts/_mail_alert.php, public/assets/tourmail/tour-mails.js
       changed app/Config/Routes.php (one line), app/Views/layouts/header.php (one line), Controllers/Tour/TourIntakeController.php
            (confirm sends the hotels' requests), Views/pages/tour/intake.php (the list at Confirm), hotel_bookings.php (Email all
            hotels, Mails, Sent Mails), hotel_document.php (passengers' room type follows the stay's), Controllers/Mail/SendController.php
Nothing in the database is changed by the kit. No rate, Hotel Master or restaurant master is written.
Install / undo:  python3 apply.py check|apply|rollback C:/wamp64/www/avenir   (backups in before/<time>/)
Tested on the cloud mirror with a local test mail server (no mail left the test machine): 35 of 35 for Confirm with the
list (7 hotels: 5 sent, 2 without address NOT sent and on the alert; TWN corrected and noted; the driver's 2 nights at the
hotel chosen; nothing sent twice; Email all hotels; recorded as sent another way clears the alert), 10 of 10 for the alert's
privilege, the restaurant path and the pages, 15 of 15 for the single letter, 4 of 4 for a quotation.
