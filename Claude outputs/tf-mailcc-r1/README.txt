tf-mailcc-r1 — 10 Oct 2026. Asked (Shantha): every e-mail sent from the system copied (CC) to iqbaal@avenirholidays.com
and dsm@avenirholidays.com.

 * Tour Desk Admin → Settings → "The company's lines" has a new box: "Copy every e-mail to (CC)". Every e-mail the system
   sends (hotel booking requests and vouchers, restaurant vouchers, quotations, invoices — everything that goes through
   the system's mail sender) is copied to these addresses. An address already on the mail is not added twice. Empty =
   nobody. A wrong address is refused and nothing is saved. The change is on the Activity log like the other lines.
 * The hotel list before sending says who every e-mail is also copied to.
Files changed: Controllers/Mail/SendController.php, Libraries/TourFlow/MailServer.php, Controllers/Tour/TourManageController.php,
               Views/pages/tour/manage.php, Libraries/TourFlow/TourMailer.php, public/assets/tourmail/tour-mails.js
Install / undo: python3 apply.py check|apply|rollback C:/wamp64/www/avenir
Tested on the cloud mirror with a local test mail server (test addresses only): 7 of 7 — wrong address refused; saved;
a quotation and a hotel letter both copied to the two; not copied twice; emptied = nobody copied.
