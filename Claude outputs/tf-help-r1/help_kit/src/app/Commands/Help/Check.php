<?php

namespace App\Commands\Help;

use App\Libraries\Help\HelpManual;
use App\Libraries\Help\HelpPdf;
use App\Libraries\Help\HelpVerifier;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * tf-help-r1 — php spark help:check [--pdf]
 * Checks every User Manual topic against the system as it is now (screens, buttons, messages,
 * routes, menu entries, pictures). Run it after every release: a red line means a screen changed
 * and the topic must be updated. --pdf also rebuilds the printable manual. Reads only.
 */
class Check extends BaseCommand
{
    protected $group = 'Help';
    protected $name = 'help:check';
    protected $description = 'Check the User Manual topics against the current system (and optionally rebuild the PDF).';
    protected $usage = 'help:check [--pdf]';

    public function run(array $params)
    {
        $m = HelpManual::instance();
        $r = (new HelpVerifier($m))->run();
        foreach ($r['topics'] as $t) {
            CLI::write(($t['ok'] ? CLI::color('  ok    ', 'green') : CLI::color('  CHECK ', 'red')) . $t['section'] . ' · ' . $t['title']);
            foreach ($t['problems'] as $p) {
                CLI::write('          - ' . $p, 'yellow');
            }
        }
        foreach ($r['guide_problems'] as $p) {
            CLI::write('  CHECK  ' . $p, 'red');
        }
        CLI::newLine();
        CLI::write($r['ok'] . ' of ' . $r['total'] . ' topics match the system (' . $r['checked_at'] . ').', $r['ok'] === $r['total'] && !$r['guide_problems'] ? 'green' : 'red');
        if (array_key_exists('pdf', $params) || CLI::getOption('pdf')) {
            $f = (new HelpPdf($m))->path();
            CLI::write('Printable manual: ' . $f . ' (' . round(filesize($f) / 1024) . ' KB)');
        }
        return $r['ok'] === $r['total'] && !$r['guide_problems'] ? EXIT_SUCCESS : EXIT_ERROR;
    }
}
