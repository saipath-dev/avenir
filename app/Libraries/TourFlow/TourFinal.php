<?php

namespace App\Libraries\TourFlow;

/**
 * tf-final-r1 (19 Nov 2026, Shantha) — the finalization of a tour that has arrived.
 *
 * A confirmed tour moves to "Arrived – Finalization" by itself on its arrival date. It becomes "Completed" only when a
 * member of staff records that the tour has finished and that every payment and outstanding balance is settled. That
 * record is kept here, one line per decision, so nothing is overwritten: who, when, and what was declared. The tour
 * itself (tf_tour), its cards and its history are not touched by this class.
 */
class TourFinal
{
    private static bool $ensured = false;

    public static function ensure($db): void
    {
        if (self::$ensured) {
            return;
        }
        self::$ensured = true;
        $db->query("CREATE TABLE IF NOT EXISTS `tf_tour_final` (`id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `tour_id` INT UNSIGNED NOT NULL,
            `action` VARCHAR(20) NOT NULL, `tour_finished` TINYINT(1) NOT NULL DEFAULT 0, `payments_settled` TINYINT(1) NOT NULL DEFAULT 0,
            `note` VARCHAR(500) NULL, `by_name` VARCHAR(100) NULL, `at` DATETIME NOT NULL, PRIMARY KEY (`id`), KEY `k_tour` (`tour_id`, `id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** tour id → its finalization (the latest line, when that line is 'finalized'). */
    public static function map($db): array
    {
        self::ensure($db);
        $out = [];
        foreach ($db->query('SELECT f.* FROM tf_tour_final f JOIN (SELECT tour_id, MAX(id) id FROM tf_tour_final GROUP BY tour_id) m ON m.id = f.id')->getResultArray() as $r) {
            if ($r['action'] === 'finalized') {
                $out[(int) $r['tour_id']] = ['at' => $r['at'], 'by' => $r['by_name'], 'note' => $r['note']];
            }
        }
        return $out;
    }

    /** live | arrived | completed | cancelled — the one rule every screen reads. */
    public static function group(array $t, array $finalMap, string $today): string
    {
        $stage = (string) ($t['stage'] ?? '');
        if ($stage === 'cancelled') {
            return 'cancelled';
        }
        if (isset($finalMap[(int) $t['id']])) {
            return 'completed';
        }
        $arr = substr((string) ($t['arrival_date'] ?? ''), 0, 10);
        if (($arr !== '' && $arr <= $today) || in_array($stage, ['completed', 'closed'], true)) {
            return 'arrived';
        }
        return 'live';
    }

    public static function finalize($db, int $tourId, string $by, string $note = ''): void
    {
        self::ensure($db);
        $db->query('INSERT INTO tf_tour_final (tour_id, action, tour_finished, payments_settled, note, by_name, at) VALUES (?, ?, 1, 1, ?, ?, ?)',
            [$tourId, 'finalized', $note !== '' ? mb_substr($note, 0, 500) : null, mb_substr($by, 0, 100), date('Y-m-d H:i:s')]);
    }
}
