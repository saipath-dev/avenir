tf-mailfix-r2 — sending a quotation (and hotel letters) by e-mail, 10 Oct 2026 (r1 + My Profile → Mailbox)

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

r2 — My Profile → Mailbox (after r1 found Lolipop, Lolipop refused the saved password for traveljp@avenirholidays.com):
 * A Mailbox button on My Profile. The signed-in person types their mailbox address and the mailbox's own password.
   The login is checked with the mail server (connect, log in, quit — nothing is sent) and saved on their own user
   account only if the server accepts it. "Check saved login" checks the password already saved. The password is never
   shown, returned or logged. At most 6 checks per 10 minutes per sign-in (a mail server locks a mailbox after many
   failures). This is also the only way to set the System Administrator account's mailbox.

Files: r1 changed SendController.php, QuoteMail.php, HotelMail.php; new CompanyMail.php, MailServer.php.
       r2 changed MailServer.php (login check), Views/pages/my_profile.php (Mailbox button); new MailboxController.php,
       Config/Routes.mailbox.php, Views/pages/_mailbox_modal.php; one line added to Config/Routes.php.
Install / undo:  python3 apply.py check|apply|rollback C:/wamp64/www/avenir   (backups in before/<time>/; each rollback
                 undoes the newest apply of that copy — run it again to undo the one before)
Tested on the cloud mirror of the live code (r2: 22 of 22 with a local test mail server — old password refused,
wrong password not saved, right password saved, the quotation then delivered with its PDF, nothing sent by checking,
no password in any page or answer, the check limit, signed-out refused). r1: the Shift-JIS answer reproduced the live error before the fix and shows the
reason after it; a real send goes to smtp.lolipop.jp; the hotel bookings, inquiry and hotel rates screens still open.
