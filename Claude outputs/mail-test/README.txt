Avenir Holidays - mail connection test (Lolipop hosting mail)
=============================================================

What it does
  1. Sends ONE test e-mail from htl.res@avenirholidays.com to Shanth@lionroyal.co.jp through smtp.lolipop.jp
     (port 465, SSL). It asks you to type SEND before it sends.
  2. Opens the inbox of htl.res@avenirholidays.com READ-ONLY through imap.lolipop.jp (port 993, SSL) and counts the
     messages. Nothing is read, marked, moved or deleted.
  The server certificates are checked (against the CA list in writable\cacert.pem).

What it does NOT do
  - It does not save the password anywhere, show it, or write it to the transcript.
  - It does not start or change the Avenir system or its database; it only borrows PHPMailer from the vendor folder.

How to run it (on the WAMP server) - from a Command Prompt, so the window stays open
  1. Click Start, type cmd and press Enter. A black Command Prompt window opens.
  2. Type this line and press Enter:
       cd /d "C:\wamp64\www\avenir\Claude outputs\mail-test"
  3. Type dir and press Enter: MAIL-TEST.bat, mail_test.php, ImapClient.php and README.txt must be listed.
  4a. Inbox check only (sends NOTHING):   MAIL-TEST.bat --no-send
  4b. Later, the full test (one e-mail):  MAIL-TEST.bat
  5. Type the mailbox password when asked (nothing shows while typing) and press Enter.
  6. For 4b only: type SEND and press Enter to send the one test e-mail.
  7. Wait for "RESULT:". Do not close the window; take a photo of it. A transcript MAIL-TEST-<date>.txt is
     written beside this file (no password in it).
  8. For 4b: check that the e-mail arrived at Shanth@lionroyal.co.jp (also its spam folder).
  Options: --no-send (inbox check only)   --no-imap (send only)   --to=someone@example.com (another recipient)

If MAIL-TEST.bat or mail_test.php disappears again
  Stop. Do not restore or allow anything yet. Open Windows Security -> Virus & threat protection -> Protection
  history, take a photo of the newest entry and send it to Claude.

Afterwards
  - Please change the mailbox password (it was written in the chat), then give the new one to nobody but the person
    who will type it into the system's settings when the connection goes live.
  - Send Claude the transcript (or a photo of the window) if anything failed.
