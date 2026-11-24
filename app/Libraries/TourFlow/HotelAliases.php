<?php

namespace App\Libraries\TourFlow;

use App\Libraries\HotelRates\HotelRateService;

/**
 * tf-hotel-names-r1 (24 Nov 2026, Shantha): the other names one hotel is known by — "Goodwood Plaza" is the hotel the Hotel
 * Master calls "KYNROCK Airport Hotel". Kept on the Hotel Master (Other names); an agent's email that uses any of them lands on
 * that hotel. One name points at one hotel only. The table is created on first use; nothing else in the database changes.
 */
final class HotelAliases
{
    public static function ensure($db): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $db->query("CREATE TABLE IF NOT EXISTS `tf_hotel_alias` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `hotel_id` INT NOT NULL,
            `alias` VARCHAR(190) NOT NULL,
            `alias_key` VARCHAR(190) NOT NULL,
            `created_by` VARCHAR(80) NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `alias_key` (`alias_key`),
            KEY `hotel` (`hotel_id`)
        )");
        $done = true;
    }

    /** @return string[] the other names of one hotel, as typed */
    public static function of($db, int $hotelId): array
    {
        try {
            self::ensure($db);
            return array_column($db->query('SELECT alias FROM tf_hotel_alias WHERE hotel_id = ? ORDER BY alias', [$hotelId])->getResultArray(), 'alias');
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<int,string[]> hotel id => its other names */
    public static function byHotel($db): array
    {
        try {
            self::ensure($db);
            $out = [];
            foreach ($db->query('SELECT hotel_id, alias FROM tf_hotel_alias ORDER BY alias')->getResultArray() as $r) {
                $out[(int) $r['hotel_id']][] = (string) $r['alias'];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Keep one more name for a hotel (a rename keeps the old name this way). Quietly does nothing when the name is taken or is the hotel's own. */
    public static function keep($db, int $hotelId, string $name, string $user): bool
    {
        try {
            $names = self::of($db, $hotelId);
            $names[] = $name;
            $r = self::set($db, $hotelId, implode("\n", $names), $user);
            return in_array(trim(preg_replace('/\s+/', ' ', $name) ?? ''), $r['added'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string,int> name key => hotel id, for matching */
    public static function keys($db): array
    {
        try {
            self::ensure($db);
            $out = [];
            foreach ($db->query('SELECT hotel_id, alias_key FROM tf_hotel_alias')->getResultArray() as $r) {
                $out[(string) $r['alias_key']] = (int) $r['hotel_id'];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Replace a hotel's other names with the given list (one per line, or separated by commas / semicolons).
     * @return array{added:string[], removed:string[], names:string[]}
     */
    public static function set($db, int $hotelId, string $text, string $user): array
    {
        self::ensure($db);
        $hotel = $db->query('SELECT id, name FROM hr2_hotel WHERE id = ?', [$hotelId])->getRowArray();
        if (!$hotel) {
            throw new \RuntimeException('Hotel not found.');
        }
        $want = [];
        foreach (preg_split('/[\r\n,;]+/', $text) ?: [] as $n) {
            $n = trim(preg_replace('/\s+/', ' ', $n) ?? '');
            if ($n === '') {
                continue;
            }
            if (mb_strlen($n) > 190) {
                throw new \RuntimeException('An other name is too long (190 characters at most).');
            }
            $k = HotelRateService::nameKey($n);
            if ($k === '') {
                continue;
            }
            if ($k === HotelRateService::nameKey((string) $hotel['name'])) {
                continue;   // its own name is matched already
            }
            $want[$k] = $n;
        }
        // one name, one hotel: not another hotel's name, not another hotel's other name
        foreach ($db->query("SELECT id, name FROM hr2_hotel WHERE status <> 'merged' AND id <> ?", [$hotelId])->getResultArray() as $o) {
            $ok = HotelRateService::nameKey((string) $o['name']);
            if (isset($want[$ok])) {
                throw new \RuntimeException('"' . $want[$ok] . '" is the name of another hotel in the Hotel Master (' . $o['name'] . '). If they are one hotel, merge them instead.');
            }
        }
        foreach ($db->query('SELECT a.alias, a.alias_key, h.name FROM tf_hotel_alias a JOIN hr2_hotel h ON h.id = a.hotel_id WHERE a.hotel_id <> ?', [$hotelId])->getResultArray() as $o) {
            if (isset($want[(string) $o['alias_key']])) {
                throw new \RuntimeException('"' . $want[(string) $o['alias_key']] . '" is already an other name of ' . $o['name'] . '. One name points at one hotel only.');
            }
        }
        $have = [];
        foreach ($db->query('SELECT id, alias, alias_key FROM tf_hotel_alias WHERE hotel_id = ?', [$hotelId])->getResultArray() as $r) {
            $have[(string) $r['alias_key']] = $r;
        }
        $added = [];
        $removed = [];
        foreach ($have as $k => $r) {
            if (!isset($want[$k])) {
                $db->query('DELETE FROM tf_hotel_alias WHERE id = ?', [(int) $r['id']]);
                $removed[] = (string) $r['alias'];
            }
        }
        foreach ($want as $k => $n) {
            if (!isset($have[$k])) {
                $db->query('INSERT INTO tf_hotel_alias (hotel_id, alias, alias_key, created_by, created_at) VALUES (?,?,?,?,?)', [$hotelId, $n, mb_substr($k, 0, 190), mb_substr($user, 0, 80), date('Y-m-d H:i:s')]);
                $added[] = $n;
            }
        }
        return ['added' => $added, 'removed' => $removed, 'names' => array_values($want)];
    }
}
