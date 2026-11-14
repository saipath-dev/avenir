<?php

namespace App\Libraries\TourFlow;

/**
 * tf-lunch-r1 (14 Nov 2026): the lunch arrangement of each tour day, and the extra facts of the Meal Master it rests on.
 *
 *   tf_intake_lunch   one row per inquiry day: Lunch on Route | No Lunch | Driver-Arranged Lunch (a meal allowance) | Hotel Lunch,
 *                     with the allowance's basis (per person / for the day), amount, currency and the words for the driver.
 *   tf_meal_route     + via (the stops the route passes), + is_default (the one default restaurant of a route)
 *   tf_meal           + dietary, child_from / child_to (the child age band), rate_status (approved | pending)
 *
 * Everything here is additive: no existing column, row or rule is changed. The restaurant line itself, its rate, the taxes,
 * the conversion and the costing stay with MealMaster / IntakeService, as before.
 */
class LunchArrangement
{
    public const KINDS = ['route' => 'Lunch on Route', 'driver' => 'Driver-Arranged Lunch', 'hotel' => 'Hotel Lunch', 'none' => 'No Lunch'];

    private static bool $done = false;
    private $db;
    private string $user;
    private ?int $userId;
    private ?string $name;

    public function __construct($db, string $user = '', ?int $userId = null, ?string $name = null)
    {
        $this->db = $db;
        $this->user = $user;
        $this->userId = $userId;
        $this->name = $name;
        self::ensure($db);
    }

    /** The table and the added columns, made once if they are not there. Nothing existing is altered. */
    public static function ensure($db): void
    {
        if (self::$done) {
            return;
        }
        self::$done = true;
        try {
            $db->query('CREATE TABLE IF NOT EXISTS tf_intake_lunch (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                intake_id INT UNSIGNED NOT NULL,
                day_no INT NOT NULL,
                arrangement VARCHAR(10) NOT NULL,
                allow_basis VARCHAR(8) NULL,
                allow_amount DECIMAL(12,2) NULL,
                allow_currency VARCHAR(3) NULL,
                driver_note VARCHAR(500) NULL,
                updated_at DATETIME NOT NULL,
                updated_by VARCHAR(120) NULL,
                UNIQUE KEY uq_intake_day (intake_id, day_no)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $add = static function (string $t, string $c, string $ddl) use ($db): void {
                if (!$db->query("SHOW TABLES LIKE '" . $t . "'")->getResultArray()) {
                    return;
                }
                if (!$db->query('SHOW COLUMNS FROM `' . $t . "` LIKE '" . $c . "'")->getResultArray()) {
                    $db->query('ALTER TABLE `' . $t . '` ADD COLUMN ' . $ddl);
                }
            };
            $add('tf_meal_route', 'via', 'via VARCHAR(160) NULL');
            $add('tf_meal_route', 'is_default', 'is_default TINYINT(1) NOT NULL DEFAULT 0');
            $add('tf_meal', 'dietary', 'dietary VARCHAR(255) NULL');
            $add('tf_meal', 'child_from', 'child_from TINYINT UNSIGNED NULL');
            $add('tf_meal', 'child_to', 'child_to TINYINT UNSIGNED NULL');
            $add('tf_meal', 'rate_status', "rate_status VARCHAR(10) NOT NULL DEFAULT 'approved'");
        } catch (\Throwable $e) {
            self::$done = false;
        }
    }

    /** The arrangements saved on an inquiry, by day number. */
    public function forIntake(int $intakeId): array
    {
        $out = [];
        foreach ($this->db->query('SELECT * FROM tf_intake_lunch WHERE intake_id = ? ORDER BY day_no', [$intakeId])->getResultArray() as $r) {
            $out[(int) $r['day_no']] = [
                'day_no' => (int) $r['day_no'], 'arrangement' => $r['arrangement'], 'allow_basis' => $r['allow_basis'],
                'allow_amount' => $r['allow_amount'] === null ? null : (float) $r['allow_amount'], 'allow_currency' => $r['allow_currency'],
                'driver_note' => $r['driver_note'], 'updated_at' => $r['updated_at'], 'updated_by' => $r['updated_by'],
            ];
        }
        return $out;
    }

    /** Save a day's arrangement (and, for a driver-arranged lunch, the allowance). Recorded in the inquiry's history. */
    public function set(int $intakeId, int $dayNo, array $b): array
    {
        $k = strtolower(trim((string) ($b['arrangement'] ?? '')));
        if (!isset(self::KINDS[$k])) {
            throw new \InvalidArgumentException('Choose Lunch on Route, Driver-Arranged Lunch, Hotel Lunch or No Lunch.');
        }
        if ($dayNo < 1) {
            throw new \InvalidArgumentException('That is not a day of this tour.');
        }
        $basis = null;
        $amt = null;
        $cur = null;
        $note = null;
        if ($k === 'driver') {
            $basis = (string) ($b['allow_basis'] ?? 'pp') === 'day' ? 'day' : 'pp';
            $raw = trim((string) ($b['allow_amount'] ?? ''));
            if ($raw !== '') {
                if (!is_numeric($raw) || (float) $raw < 0) {
                    throw new \InvalidArgumentException('The allowance is an amount of money, not negative.');
                }
                $amt = round((float) $raw, 2);
            }
            $cur = strtoupper(trim((string) ($b['allow_currency'] ?? 'LKR')));
            $cur = in_array($cur, ['LKR', 'USD'], true) ? $cur : 'LKR';
            $note = trim((string) ($b['driver_note'] ?? ''));
            $note = $note === '' ? null : mb_substr($note, 0, 500);
        }
        $before = $this->forIntake($intakeId)[$dayNo] ?? null;
        $this->db->query('INSERT INTO tf_intake_lunch (intake_id, day_no, arrangement, allow_basis, allow_amount, allow_currency, driver_note, updated_at, updated_by) VALUES (?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE arrangement = VALUES(arrangement), allow_basis = VALUES(allow_basis), allow_amount = VALUES(allow_amount), allow_currency = VALUES(allow_currency), driver_note = VALUES(driver_note), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)',
            [$intakeId, $dayNo, $k, $basis, $amt, $cur, $note, date('Y-m-d H:i:s'), $this->name ?: $this->user]);
        $say = static function (?array $r): string {
            if (!$r) {
                return 'not set';
            }
            $w = self::KINDS[$r['arrangement']] ?? $r['arrangement'];
            if ($r['arrangement'] === 'driver') {
                $w .= $r['allow_amount'] === null ? ' (allowance not set)' : ' (allowance ' . $r['allow_currency'] . ' ' . number_format((float) $r['allow_amount'], 2) . ($r['allow_basis'] === 'day' ? ' for the day' : ' per person') . ')';
            }
            return $w;
        };
        $after = $this->forIntake($intakeId)[$dayNo];
        if ($say($before) !== $say($after)) {
            try {
                (new Activity($this->db, $this->user, $this->userId, $this->name))->record('inquiry', $intakeId, $intakeId, null, 'lunch_arrangement',
                    'Lunch arrangement, day ' . $dayNo . ': ' . $say($before) . ' → ' . $say($after),
                    [['field' => 'Lunch arrangement, day ' . $dayNo, 'from' => $say($before), 'to' => $say($after)]]);
            } catch (\Throwable $e) {
            }
        }
        return $after;
    }

    /** Every lunch route of the Meal Master with its restaurants, the default first. */
    public function routeDefaults(): array
    {
        try {
            return array_map(static fn ($r) => [
                'from' => $r['from_place'], 'to' => $r['to_place'], 'via' => $r['via'], 'default' => (int) $r['is_default'] === 1,
                'meal_id' => (int) $r['meal_id'], 'restaurant' => $r['restaurant'], 'location' => $r['location'], 'active' => (int) $r['active'] === 1,
                'currency' => $r['currency'], 'adult_rate' => $r['adult_rate'] === null ? null : (float) $r['adult_rate'], 'child_rate' => $r['child_rate'] === null ? null : (float) $r['child_rate'],
                'valid_from' => $r['valid_from'], 'valid_to' => $r['valid_to'], 'rate_status' => $r['rate_status'] ?? 'approved',
                'adult_lkr' => $r['adult_lkr'] === null ? null : (float) $r['adult_lkr'], 'child_lkr' => $r['child_lkr'] === null ? null : (float) $r['child_lkr'],
            ], $this->db->query("SELECT r.from_place, r.to_place, r.via, r.is_default, r.meal_id, m.restaurant, m.location, m.active, m.currency, m.adult_rate, m.child_rate, m.valid_from, m.valid_to, m.rate_status, m.adult_lkr, m.child_lkr
                FROM tf_meal_route r JOIN tf_meal m ON m.id = r.meal_id WHERE m.meal_type = 'lunch'
                ORDER BY r.from_place, r.to_place, r.is_default DESC, m.restaurant")->getResultArray());
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** The added facts of a Meal Master record, saved beside MealMaster::save (which keeps its own rules). */
    public static function saveExtra($db, int $mealId, array $in): void
    {
        self::ensure($db);
        $age = static function ($v): ?int {
            $v = trim((string) $v);
            return $v === '' || !ctype_digit($v) ? null : min(17, (int) $v);
        };
        $diet = trim((string) ($in['dietary'] ?? ''));
        $st = (string) ($in['rate_status'] ?? 'approved') === 'pending' ? 'pending' : 'approved';
        $db->query('UPDATE tf_meal SET dietary = ?, child_from = ?, child_to = ?, rate_status = ? WHERE id = ?',
            [$diet === '' ? null : mb_substr($diet, 0, 255), $age($in['child_from'] ?? ''), $age($in['child_to'] ?? ''), $st, $mealId]);
        $type = (string) ($db->query('SELECT meal_type FROM tf_meal WHERE id = ?', [$mealId])->getRowArray()['meal_type'] ?? 'lunch');
        foreach ($db->query('SELECT from_place, to_place FROM tf_meal_route WHERE meal_id = ? AND is_default = 1', [$mealId])->getResultArray() as $r) {
            // one default restaurant per route and meal type: the others on the same route step down
            $db->query('UPDATE tf_meal_route SET is_default = 0 WHERE meal_id <> ? AND from_place = ? AND to_place = ? AND meal_id IN (SELECT id FROM tf_meal WHERE meal_type = ?)',
                [$mealId, $r['from_place'], $r['to_place'], $type]);
        }
    }
}
