<?php

namespace App\Commands\TourFlow;

use App\Libraries\TourFlow\CommsInbox;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark tourflow:inbox    — collect new e-mails from the TourFlow mailbox and file them (tf-comms-r3).
 *
 * For a scheduled task on UAT / production (every few minutes), with no one signed in:
 *   Windows Task Scheduler:  "C:\wamp64\bin\php\php8.1.33\php.exe" C:\wamp64\www\avenir\spark tourflow:inbox
 *   cron (Linux):            * /5 * * * *  cd /path/to/avenir && php spark tourflow:inbox >> writable/logs/inbox-cron.log 2>&1
 * Only where the command line is truly unavailable, the web entry (off by default, Tour Desk Admin → Settings → Incoming mailbox):
 *   POST https://<host>/api/tourflow/mail_poll   with header  X-TourFlow-Token: <token shown once when made>
 */
class Inbox extends BaseCommand
{
    protected $group = 'TourFlow';
    protected $name = 'tourflow:inbox';
    protected $description = 'Collect new e-mails from the TourFlow mailbox and file them on their inquiry / tour records.';
    protected $usage = 'tourflow:inbox';

    public function run(array $params)
    {
        date_default_timezone_set('Asia/Colombo');
        $db = \Config\Database::connect();
        if (!CommsInbox::ready($db)) {
            CLI::error('Communications & History (schema 47) is not installed.');
            return;
        }
        $r = (new CommsInbox($db, 'scheduled task'))->check('scheduled');
        CLI::write(date('Y-m-d H:i:s') . ' ' . ($r['ok'] ? 'OK' : 'FAILED') . ' — ' . $r['message'], $r['ok'] ? 'green' : 'red');
        foreach ((array) ($r['notes'] ?? []) as $n) {
            CLI::write('  ' . $n, 'yellow');
        }
    }
}
