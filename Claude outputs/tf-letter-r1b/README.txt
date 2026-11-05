tf-letter-r1b — 10 Oct 2026, on top of tf-letter-r1 (already live)

Asked (Shantha): the dark band at the bottom of the hotel booking request does not look nice — keep it white like the
letter, with thick (bold) letters so the wording reads clearly.

Change: app/Views/pages/tour/hotel_document.php, the e-mail footer only — white background, a purple line above it,
the confidentiality line in bold purple; the address is not repeated at the bottom (it is in the letterhead). Nothing else changes.
Install / undo:  python3 apply.py check|apply|rollback C:/wamp64/www/avenir   (backup in before/<time>/)
apply refuses if the view on the copy is not the r1 one it was tested on.
Tested on the cloud mirror with a local test mail server: 15 of 15 (r1's checks, the white bold footer, the address only once).
