tf-mailfix-r1 — sending a quotation (and hotel letters) by e-mail, 10 Oct 2026

What was wrong (INQ 1019, quotation v1, Send now):
 1. The mailbox on the account (traveljp@avenirholidays.com) was set to the server mail.avenirholidays.com.
    That name no longer exists: avenirholidays.com's mail is at Lolipop (MX mx01.lolipop.jp). The send failed.
 2. Windows on this laptop reports that failure in Japanese, in Shift-JIS ("このようなホストは不明です。" = no such host).
    That text is not UTF-8, so the screen's answer could not be built and showed
    "Failed to parse JSON string. Error: Malformed UTF-8 characters" instead of the reason.

The fix:
 * Company mailboxes (@avenirholidays.com) send through the company mail server, app/Config/CompanyMail.php:
   smtp.lolipop.jp, port 465, SSL, login = the full address (what the hotel-mail test of 8 Oct sent through).
   Other mailboxes keep the host on their user account. Passwords stay on the user accounts; none is in this kit.
 * Mail errors are turned into UTF-8 and start with the reason in plain words (server not found, login refused,
   server not reachable), so the screen shows them.

Files: changed SendController.php, QuoteMail.php, HotelMail.php; new CompanyMail.php, MailServer.php.
Install / undo:  python3 apply.py check|apply|rollback C:/wamp64/www/avenir   (backups in before/<time>/)
Tested on the cloud mirror of the live code: the Shift-JIS answer reproduced the live error before the fix and shows the
reason after it; a real send goes to smtp.lolipop.jp; the hotel bookings, inquiry and hotel rates screens still open.
