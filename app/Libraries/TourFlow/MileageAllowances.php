<?php

namespace App\Libraries\TourFlow;

/**
 * Kilometres charged without being driven between two places — Master Files →
 * Mileage Allowances.
 *
 * Three kinds, all typed by a person and changed only by a person:
 *
 *   the stay day   — a day the party stays at the hotel with no sightseeing. The vehicle
 *                    and the driver are still on the tour, so the day is charged at a
 *                    flat figure: 80 km, as management set it.
 *   the garage run — the driver's trip from the garage out to the first pickup, and back
 *                    from the last drop. 30 km at each end, the flat figure the driver
 *                    log report has always applied.
 *   the city tour  — a fixed allowance for a day spent in and around one town: Colombo,
 *                    Kandy, Sigiriya. These are the office's existing extra_kilometers
 *                    master, read and written here so there is one list of cities and
 *                    not two; the old Extra Kilometers screen still shows the same rows.
 *
 * None of these is a price. The Transport Rates master says what a kilometre costs; this
 * says how many kilometres a day of that kind is. The mileage engine adds them to the
 * itinerary as legs of their own, each editable, so the total is never a mystery.
 */
final class MileageAllowances
{
    private $db;
    private string $user;

    public function __construct($db, string $user = '')
    {
        $this->db   = $db;
        $this->user = $user;
    }

    // =====================================================================
    //  The fixed allowances
    // =====================================================================

    /** @return array<int, array{code:string, name:string, km:float, applies:string, note:?string, active:bool, updated_by:?string, updated_at:?string}> */
    public function all(): array
    {
        // Read fresh every time: the table is a handful of rows, and a figure changed on
        // the master screen must be what the very next plan uses.
        $out = [];
        try {
            $rows = $this->db->query('SELECT * FROM tf_mileage_allowance ORDER BY FIELD(applies, "stay_day", "both_ends", "tour_start", "tour_end"), code')->getResultArray();
        } catch (\Throwable $e) {
            return $out;
        }
        foreach ($rows as $r) {
            $out[] = [
                'code' => (string) $r['code'], 'name' => (string) $r['name'], 'km' => (float) $r['km'],
                'applies' => (string) $r['applies'], 'note' => $r['note'] ?: null, 'active' => (int) $r['active'] === 1,
                'updated_by' => $r['updated_by'] ?: null, 'updated_at' => $r['updated_at'] ?: null,
            ];
        }
        return $out;
    }

    public function get(string $code): ?array
    {
        foreach ($this->all() as $a) {
            if ($a['code'] === strtoupper(trim($code))) {
                return $a;
            }
        }
        return null;
    }

    /** The kilometres a stay day is charged at. Nought when the rule is switched off. */
    public function stayDayKm(): float
    {
        $a = $this->get('STAY_DAY');
        return ($a !== null && $a['active']) ? $a['km'] : 0.0;
    }

    /**
     * The garage run, as a pair: kilometres at the start of the tour and at the end.
     *
     * @return array{0: float, 1: float}
     */
    public function garageKm(): array
    {
        $a = $this->get('GARAGE');
        if ($a === null || !$a['active'] || $a['km'] <= 0) {
            return [0.0, 0.0];
        }
        return match ($a['applies']) {
            'tour_start' => [$a['km'], 0.0],
            'tour_end'   => [0.0, $a['km']],
            default      => [$a['km'], $a['km']],
        };
    }

    /** Change a figure. The row keeps its meaning; only the kilometres, the note and on/off move. */
    public function save(string $code, $km, ?string $note = null, ?bool $active = null): array
    {
        $code = strtoupper(trim($code));
        if ($this->get($code) === null) {
            throw new \InvalidArgumentException('That allowance is not one this system holds.');
        }
        $v = self::num($km);
        if ($v === null || $v < 0) {
            throw new \InvalidArgumentException('Enter the kilometres — nought is allowed, a negative is not.');
        }
        $was = $this->get($code);
        $this->db->query(
            'UPDATE tf_mileage_allowance SET km = ?, note = COALESCE(?, note), active = COALESCE(?, active), updated_by = ?, updated_at = ? WHERE code = ?',
            [$v, self::cut($note, 255), $active === null ? null : ($active ? 1 : 0), $this->user, date('Y-m-d H:i:s'), $code]
        );
        Activity::master($this->db, $this->user, 'mileage_allowance', 0, 'Mileage Allowances: ' . $code . ' ' . (float) ($was['km'] ?? 0) . ' → ' . $v . ' km' . ($note ? ' — ' . trim((string) $note) : ''), [
            ['what' => $code . ' km', 'from' => $was['km'] ?? null, 'to' => $v],
            ['what' => $code . ' note', 'from' => $was['note'] ?? null, 'to' => $note ?? ($was['note'] ?? null)],
            ['what' => $code . ' active', 'from' => $was['active'] ?? null, 'to' => $active === null ? ($was['active'] ?? null) : ($active ? 1 : 0)],
        ]);
        return $this->all();
    }

    // =====================================================================
    //  City tour allowances — the extra_kilometers master
    // =====================================================================

    public function hasCityMaster(): bool
    {
        try {
            return (bool) $this->db->query("SHOW TABLES LIKE 'extra_kilometers'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<int, array{id:int, city:string, km:float}> */
    public function cities(): array
    {
        if (!$this->hasCityMaster()) {
            return [];
        }
        $out = [];
        foreach ($this->db->query('SELECT id, city, kilometers FROM extra_kilometers ORDER BY city')->getResultArray() as $r) {
            $out[] = ['id' => (int) $r['id'], 'city' => (string) $r['city'], 'km' => (float) $r['kilometers']];
        }
        return $out;
    }

    /** The allowance for a place, by its town. Null when the town is not in the list. */
    public function cityKm(?string $place): ?float
    {
        $town = MileageLookup::townOf(trim(str_ireplace(', Sri Lanka', '', (string) $place)));
        if ($town === '') {
            return null;
        }
        foreach ($this->cities() as $c) {
            if (strcasecmp($c['city'], $town) === 0) {
                return $c['km'];
            }
        }
        return null;
    }

    /** Add a city, or change one. The same two columns the Extra Kilometers screen writes. */
    public function saveCity(?int $id, string $city, $km): array
    {
        if (!$this->hasCityMaster()) {
            throw new \RuntimeException('The extra_kilometers master is not on this install.');
        }
        $name = trim(str_ireplace(', Sri Lanka', '', $city));
        if ($name === '') {
            throw new \InvalidArgumentException('Name the city or town.');
        }
        $v = self::num($km);
        if ($v === null || $v < 0) {
            throw new \InvalidArgumentException('Enter the kilometres allowed for a day in ' . $name . '.');
        }
        $dup = $this->db->query(
            'SELECT id FROM extra_kilometers WHERE LOWER(city) = LOWER(?)' . ($id ? ' AND id <> ?' : ''),
            $id ? [$name, $id] : [$name]
        )->getRowArray();
        if ($dup) {
            throw new \InvalidArgumentException($name . ' is already in the list — change that row instead of adding a second.');
        }
        if ($id) {
            $was = $this->db->query('SELECT city, kilometers FROM extra_kilometers WHERE id = ?', [$id])->getRowArray();
            $this->db->query('UPDATE extra_kilometers SET city = ?, kilometers = ? WHERE id = ?', [$name, $v, $id]);
            Activity::master($this->db, $this->user, 'city_allowance', $id, 'Extra Kilometers: ' . $name . ' ' . (float) ($was['kilometers'] ?? 0) . ' → ' . $v . ' km', [
                ['what' => 'city', 'from' => $was['city'] ?? null, 'to' => $name], ['what' => $name . ' km', 'from' => $was['kilometers'] ?? null, 'to' => $v],
            ]);
        } else {
            $this->db->query('INSERT INTO extra_kilometers (city, kilometers) VALUES (?, ?)', [$name, $v]);
            Activity::master($this->db, $this->user, 'city_allowance', (int) $this->db->insertID(), 'Extra Kilometers: ' . $name . ' added at ' . $v . ' km', [['what' => $name . ' km', 'from' => null, 'to' => $v]]);
        }
        return $this->cities();
    }

    public function deleteCity(int $id): array
    {
        if ($this->hasCityMaster()) {
            $was = $this->db->query('SELECT city, kilometers FROM extra_kilometers WHERE id = ?', [$id])->getRowArray();
            $this->db->query('DELETE FROM extra_kilometers WHERE id = ?', [$id]);
            if ($was) {
                Activity::master($this->db, $this->user, 'city_allowance', $id, 'Extra Kilometers: ' . $was['city'] . ' removed (was ' . (float) $was['kilometers'] . ' km)', [['what' => $was['city'] . ' km', 'from' => $was['kilometers'], 'to' => null]]);
            }
        }
        return $this->cities();
    }

    // =====================================================================

    private static function num($v): ?float
    {
        return ($v === null || $v === '' || !is_numeric($v)) ? null : round((float) $v, 2);
    }

    private static function cut($v, int $len): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        return $s === '' ? null : mb_substr($s, 0, $len);
    }
}
