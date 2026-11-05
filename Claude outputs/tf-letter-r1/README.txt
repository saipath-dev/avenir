tf-letter-r1 — hotel booking request / hotel voucher letters, 10 Oct 2026

Asked (Shantha, 10 Oct): rates are confidential — no figures in the Rate line, "as per agreed contract" instead, and
"as per agreed tactical / early bird" when such an offer was applied; a more professional letter with the AVENIR logo;
company address No. 980/4A, Level 4, Wickramasinghe Pl, Sri Jayawardanapura 10100, Sri Lanka.

What changes:
 * Rate line: no figure in any letter (e-mail or print). It reads
     "As per agreed contract rates"           — the normal case
     "As per agreed tactical rate"            — a tactical offer was chosen for that hotel on the tour (Hotel Rates)
     "As per agreed early bird offer"         — an early bird offer was chosen
     "As per agreed special offer"            — any other special offer
   If the choice cannot be read, the letter falls back to "As per agreed contract rates" (never a figure).
 * Letterhead: AVENIR logo, company name, address, phone and e-mail; title HOTEL BOOKING REQUEST / HOTEL VOUCHER;
   "Our reference" and date; "To: Reservations Department, <hotel>"; a Reservation details table; a confirmation
   request box; signature; a footer with the confidentiality line ("…Please do not disclose rates to the guests.").
 * E-mail: sent as UTF-8 (names and dashes read right in every mail program); the logo travels inside the message
   (a localhost picture address cannot be opened by the hotel). The copy kept in the system is unchanged.
   This UTF-8 setting applies to quotation e-mails too (re-tested: a quotation still goes with its PDF).
 * The company address itself is a setting, not code: Tour Desk → Admin → Company → Address. It is changed there
   after the kit is in.

Files: new app/Libraries/TourFlow/HotelLetter.php, public/assets/images/email/avenir-logo-email.png (from Logo-new.png);
       changed app/Controllers/Tour/TourDeskController.php (documentData), app/Views/pages/tour/hotel_document.php,
       app/Libraries/TourFlow/MailServer.php (embedLocalImages), app/Controllers/Mail/SendController.php (UTF-8, logo).
Nothing in the database is changed by the kit. No hotel rate is read into a letter, published or changed.
Install / undo:  python3 apply.py check|apply|rollback C:/wamp64/www/avenir   (backups in before/<time>/)
Tested on the cloud mirror of the live code with a local test mail server: 13 of 13 for the letters (tactical and
contract wording, no figures, new address, logo inside the e-mail, nothing on localhost), 4 of 4 for a quotation.
