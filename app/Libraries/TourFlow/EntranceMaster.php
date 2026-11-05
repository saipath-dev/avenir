<?php

namespace App\Libraries\TourFlow;

/**
 * Place to Visit / Entrance Fee Master (schema 39, tf-entrance-r1) — entrances and safaris only.
 *
 *   tf_place          the paid entrance places and the safari parks: name, code, destination,
 *                     location, map link, description, image, category, who settles it, whether
 *                     a safari jeep is hired there, in use or not
 *   tf_place_rate     one charge of a place for a period: the entrance (adult and child, a head)
 *                     or the safari jeep (per vehicle), in USD or LKR — or marked Free
 *   tf_place_alias    the other names a place is written under; each points at one place
 *   tf_place_import   the one-time import: what became of each record of the old Place to Visit
 *                     list and of each entrance or jeep fee kept under Excursion Rates
 *
 * The rule (ServiceLookup applies it, the same for every screen):
 *
 *   an entrance or safari line that is a place of the master:
 *       a valid positive rate for the day        → charged (a safari park: the entrance a head
 *                                                   and the jeep per vehicle, each shown)
 *       marked Free                              → no charge
 *       no rate, an ended rate, or 0.00          → held
 *   an entrance or safari line not in the master → held — never free
 *   ordinary sightseeing (a city tour, a town, shopping, a market, a photo stop, free time)
 *                                                → outside this master: no entrance charge
 *   meals, guides, trains, treatments, shows and other services → outside this master
 *
 * The old Place to Visit list (Master Files → Entrance Fee, table entrance_fee) is read once, by
 * Install now, through the approved list Database/Sql/tour_flow_place_import.json, and never
 * written. After that the master stands on its own: it does not follow the old list, and the
 * old list never prices an entrance or a safari again. No figure is guessed: where the old
 * records disagree, or one figure covers the entrance and the jeep together, no rate is taken
 * and the figures are written on the place for a person to decide.
 */
final class EntranceMaster
{
    public const URL        = 'master_files/entrance_master';
    public const LEGACY_URL = 'master_files/entrance_fee';

    public const CURRENCIES = ['USD', 'LKR'];
    public const CHARGES    = ['entrance' => 'entrance, a head', 'jeep' => 'safari jeep, per vehicle'];
    public const PAY_MODES  = ['in_tour' => 'in the tour', 'prepaid' => 'prepaid', 'direct' => 'paid direct by the clients'];
    public const CATEGORIES = ['Temple', 'Heritage site', 'Museum', 'Garden', 'National park / safari', 'Other paid place'];

    /** The kinds of line that must be a place of the master: an entrance or a safari not in it is held. */
    public const SCOPE_KINDS = ['entrance', 'safari'];

    /** A USD figure above this is almost certainly rupees: the old list holds 2,000 for a temple in its $ column. */
    public const USD_SANITY = 500.0;

    /** The approved list the one-time import reads (under app/Database/Sql). */
    public const IMPORT_FILE = 'tour_flow_place_import.json';

    private $db;
    private string $user;
    private ?int $userId;
    private ?string $actor;

    private static array $installed = [];

    public function __construct($db, string $user = '', ?int $userId = null, ?string $actor = null)
    {
        $this->db     = $db;
        $this->user   = $user !== '' ? $user : 'system';
        $this->userId = $userId;
        $this->actor  = $actor;
    }

    /** True once Install now has made the master's tables (schema 39); until then every screen works as before. */
    public static function installed($db): bool
    {
        $k = is_object($db) ? spl_object_id($db) : 0;
        if (!array_key_exists($k, self::$installed)) {
            try {
                $ok = true;
                foreach (['tf_place', 'tf_place_rate', 'tf_place_alias', 'tf_place_import'] as $t) {
                    $ok = $ok && (bool) $db->query("SHOW TABLES LIKE '{$t}'")->getResultArray();
                }
                self::$installed[$k] = $ok;
            } catch (\Throwable $e) {
                self::$installed[$k] = false;
            }
        }
        return self::$installed[$k];
    }

    /** Forget whether the tables are there (the installer and the tests ask again after creating them). */
    public static function forget(): void
    {
        self::$installed = [];
    }

    private function need(): void
    {
        if (!self::installed($this->db)) {
            throw new \RuntimeException('The Place to Visit / Entrance Fee Master is not installed on this site yet. Open Tour Inquiry as the administrator and press Install now.');
        }
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function log(int $placeId, string $summary, array $changes = []): void
    {
        Activity::master($this->db, $this->user, 'entrance_master', $placeId, $summary, $changes, $this->userId, $this->actor);
    }

    private static function hasColumn($db, string $table, string $column): bool
    {
        try {
            return (bool) $db->query("SHOW COLUMNS FROM `{$table}` LIKE ?", [$column])->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function hasTable($db, string $table): bool
    {
        try {
            return (bool) $db->query("SHOW TABLES LIKE '{$table}'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function cut($v, int $n): ?string
    {
        $t = trim((string) ($v ?? ''));
        return $t === '' ? null : mb_substr($t, 0, $n);
    }

    private static function money($v): ?float
    {
        if ($v === null) {
            return null;
        }
        $t = str_replace([',', ' '], '', trim((string) $v));
        if ($t === '') {
            return null;
        }
        if (!is_numeric($t)) {
            throw new \InvalidArgumentException('"' . trim((string) $v) . '" is not an amount.');
        }
        return round((float) $t, 2);
    }

    private static function date($v): ?string
    {
        $t = trim((string) ($v ?? ''));
        if ($t === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $t);
        if (!$d || $d->format('Y-m-d') !== $t) {
            throw new \InvalidArgumentException('"' . $t . '" is not a date (YYYY-MM-DD).');
        }
        return $t;
    }

    public static function fmtDate(?string $d): string
    {
        if ($d === null || $d === '') {
            return '';
        }
        $t = strtotime($d);
        return $t ? date('j M Y', $t) : $d;
    }

    // =====================================================================
    //  Reading the master
    // =====================================================================

    private static function rateRow(array $r): array
    {
        return [
            'id'         => (int) $r['id'],
            'place_id'   => (int) $r['place_id'],
            'charge'     => (string) ($r['charge'] ?? 'entrance') === 'jeep' ? 'jeep' : 'entrance',
            'valid_from' => $r['valid_from'] ?: null,
            'valid_to'   => $r['valid_to'] ?: null,
            'free_entry' => (int) ($r['free_entry'] ?? 0),
            'adult_rate' => (float) $r['adult_rate'],
            'child_rate' => $r['child_rate'] === null || $r['child_rate'] === '' ? null : (float) $r['child_rate'],
            'currency'   => strtoupper((string) $r['currency']),
            'note'       => $r['note'],
            'source'     => (string) $r['source'],
            'source_ref' => $r['source_ref'] ?? null,
            'active'     => (int) $r['active'],
            'updated_at' => $r['updated_at'] ?? null,
            'updated_by' => $r['updated_by'] ?? null,
        ];
    }

    /** The rates of every place, by place, oldest period first. */
    public function ratesByPlace(bool $withOff = false): array
    {
        $out = [];
        if (!self::installed($this->db)) {
            return $out;
        }
        foreach ($this->db->query('SELECT * FROM tf_place_rate' . ($withOff ? '' : ' WHERE active = 1') . ' ORDER BY place_id, (valid_from IS NULL) DESC, valid_from, id')->getResultArray() as $r) {
            $out[(int) $r['place_id']][] = self::rateRow($r);
        }
        return $out;
    }

    /** The one rate of a charge that covers a date (an open end covers everything that way), or null. */
    public static function pick(array $rates, string $date, string $charge = 'entrance'): ?array
    {
        $hit = null;
        foreach ($rates as $r) {
            if ((int) ($r['active'] ?? 1) !== 1 || ($r['charge'] ?? 'entrance') !== $charge) {
                continue;
            }
            if ($r['valid_from'] !== null && $r['valid_from'] > $date) {
                continue;
            }
            if ($r['valid_to'] !== null && $r['valid_to'] < $date) {
                continue;
            }
            if ($hit === null || (string) ($r['valid_from'] ?? '') >= (string) ($hit['valid_from'] ?? '')) {
                $hit = $r;
            }
        }
        return $hit;
    }

    /** Whether a rate is a charge (a positive figure, not marked free), free (marked), or no valid rate (0.00 without the mark). */
    public static function rateKind(?array $r): string
    {
        if ($r === null) {
            return 'none';
        }
        if ((int) $r['free_entry'] === 1) {
            return 'free';
        }
        if ($r['adult_rate'] > 0 || ($r['child_rate'] ?? 0) > 0) {
            return 'charged';
        }
        return 'zero';
    }

    /** Every active other name, by place. */
    public function aliasesByPlace(): array
    {
        $out = [];
        if (!self::installed($this->db)) {
            return $out;
        }
        foreach ($this->db->query('SELECT id, place_id, alias, alias_key, source FROM tf_place_alias WHERE active = 1 ORDER BY alias')->getResultArray() as $a) {
            $out[(int) $a['place_id']][] = ['id' => (int) $a['id'], 'alias' => (string) $a['alias'], 'alias_key' => (string) $a['alias_key'], 'source' => (string) $a['source']];
        }
        return $out;
    }

    /**
     * Every place with its rates, other names, the old records it was imported from, and what it
     * is on $on (today by default): charged, free, no rate (held), to check (held) or not in use.
     *
     * @return array<int, array<string, mixed>>
     */
    public function places(?string $on = null): array
    {
        $this->need();
        $on      = $on ?: date('Y-m-d');
        $rows    = $this->db->query('SELECT * FROM tf_place ORDER BY name, id')->getResultArray();
        $rates   = $this->ratesByPlace(true);
        $aliases = $this->aliasesByPlace();
        $sources = [];
        foreach ($this->db->query("SELECT source, source_id, outcome, place_id, old_name, old_code, old_figure FROM tf_place_import WHERE place_id IS NOT NULL ORDER BY id")->getResultArray() as $t) {
            $sources[(int) $t['place_id']][] = ['source' => (string) $t['source'], 'ref' => (string) $t['source_id'], 'outcome' => (string) $t['outcome'],
                'name' => (string) $t['old_name'], 'code' => (string) $t['old_code'], 'figure' => (string) $t['old_figure']];
        }
        $used = [];
        try {
            foreach ($this->db->query("SELECT s.place_id, COUNT(*) n FROM tf_intake_service s JOIN tf_intake i ON i.id = s.intake_id
                                       WHERE s.place_id IS NOT NULL AND i.status NOT IN ('confirmed','cancelled','declined','converted') AND i.archived_at IS NULL GROUP BY s.place_id")->getResultArray() as $u) {
                $used[(int) $u['place_id']] = (int) $u['n'];
            }
        } catch (\Throwable $e) {
            $used = [];
        }
        $tickets = [];
        try {
            if (self::hasColumn($this->db, 'tf_ticket_attraction', 'place_id')) {
                foreach ($this->db->query('SELECT place_id, control FROM tf_ticket_attraction WHERE active = 1 AND place_id IS NOT NULL')->getResultArray() as $t) {
                    $tickets[(int) $t['place_id']] = (string) $t['control'];
                }
            }
        } catch (\Throwable $e) {
            $tickets = [];
        }
        // tf-safari-r1: every booking that ever used a place (any inquiry, any status) — such a place is archived, never deleted
        $usedAll = [];
        try {
            foreach ($this->db->query('SELECT place_id, COUNT(*) n FROM tf_intake_service WHERE place_id IS NOT NULL GROUP BY place_id')->getResultArray() as $u) {
                $usedAll[(int) $u['place_id']] = (int) $u['n'];
            }
        } catch (\Throwable $e) {
            $usedAll = [];
        }
        $parties = $this->partiesByPlace();
        $out = [];
        foreach ($rows as $r) {
            $id   = (int) $r['id'];
            $all  = $rates[$id] ?? [];
            $live = array_values(array_filter($all, static fn ($x) => $x['active'] === 1));
            $p = [
                'id'          => $id,
                'code'        => trim((string) ($r['code'] ?? '')),
                'name'        => trim((string) $r['name']),
                'destination' => trim((string) ($r['destination'] ?? '')),
                'location'    => trim((string) ($r['location'] ?? '')),
                'map_link'    => trim((string) ($r['map_link'] ?? '')),
                'description' => $r['description'],
                'image'       => $r['image'],
                'category'    => trim((string) ($r['category'] ?? '')),
                'pay_mode'    => (string) $r['pay_mode'],
                'jeep'        => (int) $r['jeep'],
                'active'      => (int) $r['active'],
                'merged_into' => $r['merged_into'] === null ? null : (int) $r['merged_into'],
                'review_note' => $r['review_note'],
                'source'      => (string) $r['source'],
                'rates'       => $live,
                'rates_off'   => array_values(array_filter($all, static fn ($x) => $x['active'] !== 1)),
                'current'     => self::pick($live, $on, 'entrance'),
                'current_jeep'=> self::pick($live, $on, 'jeep'),
                'aliases'     => $aliases[$id] ?? [],
                'sources'     => $sources[$id] ?? [],
                'used'        => $used[$id] ?? 0,
                'used_all'    => $usedAll[$id] ?? 0,
                'ticket'      => $tickets[$id] ?? null,
                // tf-safari-r1
                'jeep_seats'     => isset($r['jeep_seats']) && $r['jeep_seats'] !== null ? (int) $r['jeep_seats'] : null,
                'child_age_from' => isset($r['child_age_from']) && $r['child_age_from'] !== null ? (float) $r['child_age_from'] : null,
                'child_age_to'   => isset($r['child_age_to']) && $r['child_age_to'] !== null ? (float) $r['child_age_to'] : null,
                'age_note'       => $r['age_note'] ?? null,
                'pay_together'   => (int) ($r['pay_together'] ?? 0),
                'archived_at'    => $r['archived_at'] ?? null,
                'archived_by'    => $r['archived_by'] ?? null,
                'archive_note'   => $r['archive_note'] ?? null,
                'parties'        => $parties[$id] ?? (object) [],
            ];
            $p['age_rule'] = self::ageRuleText($p['child_age_from'], $p['child_age_to']);
            [$p['status'], $p['status_text']] = self::statusOf($p, $on);
            if (!empty($p['archived_at'])) {
                $p['status'] = 'archived';
                $p['status_text'] = 'Archived ' . self::fmtDate(substr((string) $p['archived_at'], 0, 10)) . ($p['archived_by'] ? ' by ' . $p['archived_by'] : '') . ($p['archive_note'] ? ' — ' . $p['archive_note'] : '') . '. Kept for the bookings that used it; not offered.';
            }
            $out[] = $p;
        }
        return $out;
    }

    /** One place as places() gives it, or null. */
    public function place(int $id, ?string $on = null): ?array
    {
        foreach ($this->places($on) as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }
        return null;
    }

    /**
     * One charge of a place on a date: [kind, words] — kind is charged, free, zero or none.
     */
    public static function chargeOn(array $p, string $charge, string $on): array
    {
        $cur  = $charge === 'jeep' ? $p['current_jeep'] : $p['current'];
        $kind = self::rateKind($cur);
        $what = $charge === 'jeep' ? 'jeep' : 'entrance';
        if ($kind === 'free') {
            return ['free', ucfirst($what) . ': Free' . (($cur['valid_from'] || $cur['valid_to']) ? ' (' . self::period($cur) . ')' : '')];
        }
        if ($kind === 'charged') {
            return ['charged', ucfirst($what) . ': ' . self::rateText($cur)];
        }
        if ($kind === 'zero') {
            return ['zero', ucfirst($what) . ': 0.00 for ' . self::fmtDate($on) . ' without the Free mark — enter the rate, or mark it Free'];
        }
        $next = null;
        $last = null;
        foreach ($p['rates'] as $r) {
            if (($r['charge'] ?? 'entrance') !== $charge) {
                continue;
            }
            if ($r['valid_from'] !== null && $r['valid_from'] > $on && ($next === null || $r['valid_from'] < $next)) {
                $next = $r['valid_from'];
            }
            if ($r['valid_to'] !== null && $r['valid_to'] < $on && ($last === null || $r['valid_to'] > $last)) {
                $last = $r['valid_to'];
            }
        }
        return ['none', ucfirst($what) . ': no rate' . ($last || $next ? ' for ' . self::fmtDate($on) : ' yet') . ($last ? ' — the last one ended ' . self::fmtDate($last) : '') . ($next ? ' — the next starts ' . self::fmtDate($next) : '')];
    }

    /**
     * What a place is on a date, with the words for it:
     *   priced   its entrance (and, at a safari park, its jeep) has a valid positive rate — charged
     *   free     marked Free (the entrance, and the jeep where there is one) — nothing to pay
     *   no_rate  a charge has no valid rate for the day (not set, ended, starting later, 0.00) — held
     *   check    no rate yet, and the imported figures need a person (they disagree, one covers the
     *            entrance and the jeep together, or one looks like rupees) — held
     *   off      not in use, or merged into another place
     *
     * @return array{0:string,1:string}
     */
    public static function statusOf(array $p, string $on): array
    {
        if ($p['merged_into'] !== null) {
            return ['off', 'Merged into another place — read as that place.'];
        }
        if ((int) $p['active'] !== 1) {
            return ['off', 'Not in use — an entrance or safari line naming it is held.'];
        }
        $parts = [self::chargeOn($p, 'entrance', $on)];
        if ((int) $p['jeep'] === 1) {
            $parts[] = self::chargeOn($p, 'jeep', $on);
        }
        $kinds = array_map(static fn ($x) => $x[0], $parts);
        $text  = implode(' · ', array_map(static fn ($x) => $x[1], $parts)) . '.';
        if (!array_diff($kinds, ['free'])) {
            return ['free', $text];
        }
        if (!array_diff($kinds, ['free', 'charged'])) {
            return ['priced', $text];
        }
        if (!$p['rates'] && trim((string) $p['review_note']) !== '') {
            return ['check', $text . ' Imported figures to decide (written on the place).'];
        }
        return ['no_rate', $text];
    }

    public static function period(array $r): string
    {
        if (!$r['valid_from'] && !$r['valid_to']) {
            return 'open';
        }
        return ($r['valid_from'] ? 'from ' . self::fmtDate($r['valid_from']) : 'until') . ($r['valid_to'] ? ($r['valid_from'] ? ' to ' : ' ') . self::fmtDate($r['valid_to']) : '');
    }

    /** A rate in words: "USD 40.00 a head, child 20.00 (from 1 Jan 2027)", "USD 50.00 per vehicle". */
    public static function rateText(array $r): string
    {
        $jeep = ($r['charge'] ?? 'entrance') === 'jeep';
        if ((int) ($r['free_entry'] ?? 0) === 1) {
            $t = 'Free';
        } else {
            $t = $r['currency'] . ' ' . number_format($r['adult_rate'], 2) . ($jeep ? ' per vehicle' : ' a head');
            if (!$jeep && $r['child_rate'] !== null) {
                $t .= ', child ' . ($r['child_rate'] > 0 ? number_format($r['child_rate'], 2) : 'free');
            }
        }
        if ($r['valid_from'] || $r['valid_to']) {
            $t .= ' (' . self::period($r) . ')';
        }
        return $t;
    }

    /** Counts by status, for the tiles. */
    public static function summary(array $places): array
    {
        $s = ['places' => 0, 'priced' => 0, 'free' => 0, 'no_rate' => 0, 'check' => 0, 'off' => 0, 'aliases' => 0, 'parks' => 0];
        foreach ($places as $p) {
            if ($p['merged_into'] !== null) {
                continue;
            }
            $s['places']++;
            $s[$p['status']] = ($s[$p['status']] ?? 0) + 1;
            $s['aliases'] += count($p['aliases']);
            if ($p['jeep'] === 1 && $p['active'] === 1) {
                $s['parks']++;
            }
        }
        return $s;
    }

    // =====================================================================
    //  Places
    // =====================================================================

    private function placeRow(int $id): array
    {
        $r = $this->db->query('SELECT * FROM tf_place WHERE id = ?', [$id])->getRowArray();
        if (!$r) {
            throw new \InvalidArgumentException('That place is not in the master.');
        }
        return $r;
    }

    private static function label(array $p): string
    {
        return trim((string) $p['name']) . (trim((string) ($p['code'] ?? '')) !== '' ? ' (' . trim((string) $p['code']) . ')' : '');
    }

    /** The place already in the master under a name (its own name, or an other name), other than $except. */
    private function placeByName(string $name, ?int $except = null): ?array
    {
        $nk = ServiceLookup::nameKey($name);
        $wk = ServiceLookup::words($name);
        if ($nk === '') {
            return null;
        }
        foreach ($this->db->query('SELECT * FROM tf_place WHERE merged_into IS NULL' . ($except ? ' AND id <> ' . (int) $except : ''))->getResultArray() as $p) {
            if (ServiceLookup::nameKey((string) $p['name']) === $nk) {
                return $p;
            }
        }
        $a = $this->db->query('SELECT p.* FROM tf_place_alias a JOIN tf_place p ON p.id = a.place_id WHERE a.alias_key = ? AND a.active = 1 AND p.merged_into IS NULL' . ($except ? ' AND p.id <> ' . (int) $except : '') . ' LIMIT 1', [$wk])->getRowArray();
        return $a ?: null;
    }

    /**
     * Add or change a place. A name already in the master (as a place or an other name) is
     * refused — the master never holds the same place twice; so is a code another place uses.
     */
    public function savePlace(array $v): array
    {
        $this->need();
        $id   = isset($v['id']) && (int) $v['id'] > 0 ? (int) $v['id'] : null;
        $was  = $id ? $this->placeRow($id) : null;
        $name = self::cut($v['name'] ?? ($was['name'] ?? null), 255);
        if ($name === null) {
            throw new \InvalidArgumentException('A place needs a name.');
        }
        if (($dup = $this->placeByName($name, $id)) !== null) {
            throw new \InvalidArgumentException('"' . $name . '" is in the master already: ' . self::label($dup) . '. The master never holds the same place twice — change that one, or give it this name as an other name.');
        }
        $code = self::cut($v['code'] ?? ($was['code'] ?? null), 60);
        if ($code !== null && ($o = $this->db->query('SELECT name, code FROM tf_place WHERE LOWER(TRIM(code)) = LOWER(?) AND merged_into IS NULL' . ($id ? ' AND id <> ' . $id : '') . ' LIMIT 1', [$code])->getRowArray())) {
            throw new \InvalidArgumentException('The code ' . $code . ' is ' . self::label($o) . '\'s already.');
        }
        $pay = (string) ($v['pay_mode'] ?? ($was['pay_mode'] ?? 'in_tour'));
        if (!isset(self::PAY_MODES[$pay])) {
            throw new \InvalidArgumentException('Choose who settles it: in the tour, prepaid, or paid direct by the clients.');
        }
        $cat = self::cut(array_key_exists('category', $v) ? $v['category'] : ($was['category'] ?? null), 40);
        if ($cat !== null && !in_array($cat, self::CATEGORIES, true) && (!$was || $cat !== trim((string) $was['category']))) {
            throw new \InvalidArgumentException('Choose the category: ' . implode(', ', self::CATEGORIES) . '.');
        }
        $map = self::cut($v['map_link'] ?? ($was['map_link'] ?? null), 500);
        if ($map !== null && !preg_match('#^https?://#i', $map)) {
            throw new \InvalidArgumentException('The map link is a web address (https://…).');
        }
        $vals = [
            'code'        => $code,
            'name'        => $name,
            'destination' => self::cut(array_key_exists('destination', $v) ? ProgrammeMasters::destination((string) $v['destination']) : ($was['destination'] ?? null), 120),
            'location'    => self::cut($v['location'] ?? ($was['location'] ?? null), 255),
            'map_link'    => $map,
            'description' => array_key_exists('description', $v) ? (trim((string) $v['description']) === '' ? null : mb_substr(trim((string) $v['description']), 0, 4000)) : ($was['description'] ?? null),
            'category'    => $cat,
            'pay_mode'    => $pay,
            'jeep'        => array_key_exists('jeep', $v) ? (int) (!empty($v['jeep']) && (string) $v['jeep'] !== '0') : (int) ($was['jeep'] ?? 0),
            'active'      => array_key_exists('active', $v) ? (int) (!empty($v['active']) && (string) $v['active'] !== '0') : (int) ($was['active'] ?? 1),
        ];
        // tf-safari-r1: how many people a jeep carries, and the children's age rules for the entrance
        if (self::hasColumn($this->db, 'tf_place', 'jeep_seats')) {
            $seats = array_key_exists('jeep_seats', $v) ? trim((string) $v['jeep_seats']) : (string) ($was['jeep_seats'] ?? '');
            if ($seats !== '' && (!ctype_digit($seats) || (int) $seats < 1 || (int) $seats > 60)) {
                throw new \InvalidArgumentException('The jeep capacity is the number of passengers one jeep carries (1 to 60), or blank if not known.');
            }
            $age = static function ($x, string $what): ?float {
                $x = trim((string) ($x ?? ''));
                if ($x === '') {
                    return null;
                }
                if (!is_numeric($x) || (float) $x < 0 || (float) $x > 17.99) {
                    throw new \InvalidArgumentException('The ' . $what . ' is an age from 0 to 17.99 years, or blank.');
                }
                return round((float) $x, 2);
            };
            $from = $age(array_key_exists('child_age_from', $v) ? $v['child_age_from'] : ($was['child_age_from'] ?? null), 'age from which the child rate is paid');
            $to   = $age(array_key_exists('child_age_to', $v) ? $v['child_age_to'] : ($was['child_age_to'] ?? null), 'age up to which the child rate is paid');
            if ($from !== null && $to !== null && $to < $from) {
                throw new \InvalidArgumentException('The child rate ends at an age below the one it starts at.');
            }
            $vals['jeep_seats']     = $seats === '' ? null : (int) $seats;
            $vals['child_age_from'] = $from;
            $vals['child_age_to']   = $to;
            $vals['age_note']       = self::cut(array_key_exists('age_note', $v) ? $v['age_note'] : ($was['age_note'] ?? null), 255);
            if (self::hasColumn($this->db, 'tf_place', 'pay_together')) {
                // tf-safari-r2: the jeep operator is paid the jeep and the entrance together
                $vals['pay_together'] = array_key_exists('pay_together', $v) ? (int) (!empty($v['pay_together']) && (string) $v['pay_together'] !== '0') : (int) ($was['pay_together'] ?? 0);
            }
            self::$rulesCache = [];
        }
        if ($was) {
            $sets = [];
            $binds = [];
            $changes = [];
            foreach ($vals as $k => $val) {
                $sets[] = "`{$k}` = ?";
                $binds[] = $val;
                if ((string) ($was[$k] ?? '') !== (string) ($val ?? '')) {
                    $changes[] = ['what' => $k, 'from' => $was[$k] ?? null, 'to' => $val];
                }
            }
            $binds[] = $this->now();
            $binds[] = $this->user;
            $binds[] = $id;
            $this->db->query('UPDATE tf_place SET ' . implode(', ', $sets) . ', updated_at = ?, updated_by = ? WHERE id = ?', $binds);
            if ($changes) {
                $this->log($id, 'Place to Visit master: ' . self::label($vals) . ' changed — ' . implode(', ', array_map(static fn ($c) => $c['what'], $changes)), $changes);
            }
        } else {
            $cols = array_keys($vals);
            $this->db->query('INSERT INTO tf_place (`' . implode('`, `', $cols) . '`, source, created_at, created_by, updated_at, updated_by)
                              VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ',\'manual\',?,?,?,?)',
                array_merge(array_values($vals), [$this->now(), $this->user, $this->now(), $this->user]));
            $id = (int) $this->db->insertID();
            $this->log($id, 'Place to Visit master: ' . self::label($vals) . ' added (no rate yet — held until one is entered or it is marked Free).', [['what' => 'place', 'from' => null, 'to' => self::label($vals)]]);
        }
        return $this->place($id) ?? [];
    }

    // =====================================================================
    //  Rates
    // =====================================================================

    private static function overlaps(?string $aFrom, ?string $aTo, ?string $bFrom, ?string $bTo): bool
    {
        $aFrom ??= '0000-01-01';
        $bFrom ??= '0000-01-01';
        $aTo   ??= '9999-12-31';
        $bTo   ??= '9999-12-31';
        return $aFrom <= $bTo && $bFrom <= $aTo;
    }

    private function rateById(int $id): array
    {
        return $this->db->query('SELECT * FROM tf_place_rate WHERE id = ?', [$id])->getRowArray() ?: [];
    }

    /**
     * Add or change one rate of a place: its entrance (a head; a child rate if children pay
     * differently) or its safari jeep (per vehicle). Free only when marked; otherwise the rate
     * must be above 0 (0.00 is never taken as free). Periods of one charge never overlap: a
     * clash is refused and named — or, with close_previous, the period running into the new one
     * ends the day before it starts (the usual "new rate from 1 January").
     */
    public function saveRate(array $v): array
    {
        $this->need();
        $placeId = (int) ($v['place_id'] ?? 0);
        $p   = $this->placeRow($placeId);
        $id  = isset($v['id']) && (int) $v['id'] > 0 ? (int) $v['id'] : null;
        $was = null;
        if ($id !== null) {
            $was = $this->db->query('SELECT * FROM tf_place_rate WHERE id = ? AND place_id = ?', [$id, $placeId])->getRowArray();
            if (!$was || (int) $was['active'] !== 1) {
                throw new \InvalidArgumentException('That rate is not on this place (or is switched off).');
            }
        }
        $charge = $was ? (string) $was['charge'] : (string) ($v['charge'] ?? 'entrance');   // a rate keeps its charge
        if (!isset(self::CHARGES[$charge])) {
            throw new \InvalidArgumentException('Choose the charge: the entrance (a head) or the safari jeep (per vehicle).');
        }
        if ($charge === 'jeep' && (int) $p['jeep'] !== 1) {
            throw new \InvalidArgumentException(self::label($p) . ' is not marked as a safari park with a jeep. Tick "a safari jeep is hired here" on the place first.');
        }
        $free = !empty($v['free_entry']) && (string) $v['free_entry'] !== '0' ? 1 : 0;
        $cur  = strtoupper(trim((string) ($v['currency'] ?? 'USD')));
        if (!in_array($cur, self::CURRENCIES, true)) {
            throw new \InvalidArgumentException('The currency is USD or LKR.');
        }
        if ($free) {
            $adult = 0.0;
            $child = null;
        } else {
            $adult = self::money($v['adult_rate'] ?? null);
            if ($adult === null || $adult <= 0) {
                throw new \InvalidArgumentException($charge === 'jeep'
                    ? 'Give the jeep rate per vehicle (above 0). A jeep with nothing to pay is marked Free, never entered as 0.'
                    : 'Give the adult rate (above 0). A place with no entrance fee is marked Free, never entered as 0.');
            }
            $child = $charge === 'entrance' ? self::money($v['child_rate'] ?? null) : null;
            if ($child !== null && $child < 0) {
                throw new \InvalidArgumentException('The child rate cannot be below 0.');
            }
            if ($cur === 'USD' && ($adult > self::USD_SANITY || ($child ?? 0) > self::USD_SANITY) && empty($v['confirm_high'])) {
                throw new \InvalidArgumentException('USD ' . number_format(max($adult, $child ?? 0), 2) . ' looks like a rupee figure. Choose LKR, or tick "the figure is right in USD".');
            }
        }
        $from = self::date($v['valid_from'] ?? null);
        $to   = self::date($v['valid_to'] ?? null);
        if ($from !== null && $to !== null && $to < $from) {
            throw new \InvalidArgumentException('The rate ends before it starts.');
        }
        $note = self::cut($v['note'] ?? null, 255);

        $closed = [];
        $others = $this->db->query('SELECT * FROM tf_place_rate WHERE place_id = ? AND charge = ? AND active = 1' . ($id ? ' AND id <> ?' : ''), $id ? [$placeId, $charge, $id] : [$placeId, $charge])->getResultArray();
        foreach ($others as $o) {
            $oFrom = $o['valid_from'] ?: null;
            $oTo   = $o['valid_to'] ?: null;
            if (!self::overlaps($from, $to, $oFrom, $oTo)) {
                continue;
            }
            $canClose = !empty($v['close_previous']) && $from !== null && ($oFrom === null || $oFrom < $from) && ($oTo === null || $oTo >= $from);
            if (!$canClose) {
                throw new \InvalidArgumentException('That period overlaps the ' . ($charge === 'jeep' ? 'jeep' : 'entrance') . ' rate ' . self::rateText(self::rateRow($o)) . '. Change the dates, or tick "end the current rate the day before" for a new rate from a date.');
            }
            $end = date('Y-m-d', strtotime($from . ' -1 day'));
            $this->db->query('UPDATE tf_place_rate SET valid_to = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$end, $this->now(), $this->user, (int) $o['id']]);
            $closed[] = ['what' => 'rate ' . (int) $o['id'] . ' ends', 'from' => $oTo, 'to' => $end];
        }
        if ($id === null) {
            $this->db->query('INSERT INTO tf_place_rate (place_id, charge, valid_from, valid_to, free_entry, adult_rate, child_rate, currency, note, source, active, created_at, created_by, updated_at, updated_by)
                              VALUES (?,?,?,?,?,?,?,?,?,\'manual\',1,?,?,?,?)', [$placeId, $charge, $from, $to, $free, $adult, $child, $cur, $note, $this->now(), $this->user, $this->now(), $this->user]);
            $id = (int) $this->db->insertID();
            $txt = self::rateText(self::rateRow($this->rateById($id)));
            $this->log($placeId, 'Place to Visit master: ' . self::label($p) . ' — ' . ($charge === 'jeep' ? 'jeep' : 'entrance') . ' rate added: ' . $txt, array_merge([['what' => $charge . ' rate ' . $id, 'from' => null, 'to' => $txt]], $closed));
        } else {
            $this->db->query('UPDATE tf_place_rate SET valid_from = ?, valid_to = ?, free_entry = ?, adult_rate = ?, child_rate = ?, currency = ?, note = ?, updated_at = ?, updated_by = ? WHERE id = ?',
                [$from, $to, $free, $adult, $child, $cur, $note, $this->now(), $this->user, $id]);
            $before = self::rateText(self::rateRow($was));
            $after  = self::rateText(self::rateRow($this->rateById($id)));
            $this->log($placeId, 'Place to Visit master: ' . self::label($p) . ' — ' . ($charge === 'jeep' ? 'jeep' : 'entrance') . ' rate changed: ' . $before . ' → ' . $after, array_merge([['what' => $charge . ' rate ' . $id, 'from' => $before, 'to' => $after]], $closed));
        }
        // the figures the import left to decide are decided once every charge of the place has a rate: the note is
        // taken off the place (it stays on the Activity log)
        if (trim((string) ($p['review_note'] ?? '')) !== '') {
            $has = fn (string $c): bool => (bool) $this->db->query('SELECT id FROM tf_place_rate WHERE place_id = ? AND charge = ? AND active = 1 LIMIT 1', [$placeId, $c])->getRowArray();
            if ($has('entrance') && ((int) $p['jeep'] !== 1 || $has('jeep'))) {
                $this->db->query('UPDATE tf_place SET review_note = NULL WHERE id = ?', [$placeId]);
                $this->log($placeId, 'Place to Visit master: ' . self::label($p) . ' — the figures to decide are settled (rates entered).', [['what' => 'to decide', 'from' => $p['review_note'], 'to' => null]]);
            }
        }
        return ['rate' => self::rateRow($this->rateById($id)), 'closed' => count($closed)];
    }

    /** Switch a rate off: kept, never deleted; the lines that used it are worked out again on their next read. */
    public function switchOffRate(int $rateId): array
    {
        $this->need();
        $r = $this->db->query('SELECT * FROM tf_place_rate WHERE id = ?', [$rateId])->getRowArray();
        if (!$r || (int) $r['active'] !== 1) {
            throw new \InvalidArgumentException('That rate is not in use.');
        }
        $p = $this->placeRow((int) $r['place_id']);
        $this->db->query('UPDATE tf_place_rate SET active = 0, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $rateId]);
        $txt = self::rateText(self::rateRow($r));
        $this->log((int) $r['place_id'], 'Place to Visit master: ' . self::label($p) . ' — ' . ((string) $r['charge'] === 'jeep' ? 'jeep' : 'entrance') . ' rate switched off (kept): ' . $txt, [['what' => 'rate ' . $rateId, 'from' => $txt, 'to' => 'switched off']]);
        return self::rateRow($this->rateById($rateId));
    }

    // =====================================================================
    //  Other names
    // =====================================================================

    /** Give a place another name it is written under. Points at that place only; refused if it is another place's name or other name. */
    public function addAlias(int $placeId, string $alias, string $source = 'manual'): array
    {
        $this->need();
        $p     = $this->placeRow($placeId);
        $alias = trim(preg_replace('/\s+/', ' ', $alias) ?? '');
        $key   = ServiceLookup::words($alias);
        if ($key === '') {
            throw new \InvalidArgumentException('Type the other name.');
        }
        if (mb_strlen($alias) > 190) {
            throw new \InvalidArgumentException('An other name is at most 190 characters.');
        }
        if ((int) $p['active'] !== 1 || $p['merged_into'] !== null) {
            throw new \InvalidArgumentException(self::label($p) . ' is not in use. Put it back in use before giving it other names.');
        }
        if (ServiceLookup::nameKey($alias) === ServiceLookup::nameKey((string) $p['name'])) {
            throw new \InvalidArgumentException('"' . $alias . '" is the place\'s own name; it is matched already.');
        }
        $nk = ServiceLookup::nameKey($alias);
        foreach ($this->db->query('SELECT * FROM tf_place WHERE id <> ? AND merged_into IS NULL', [$placeId])->getResultArray() as $o) {
            if (ServiceLookup::nameKey((string) $o['name']) === $nk) {
                throw new \InvalidArgumentException('"' . $alias . '" is the name of another place: ' . self::label($o) . '. An other name points at one place only.');
            }
        }
        $taken = $this->db->query('SELECT a.place_id, p.name, p.code FROM tf_place_alias a JOIN tf_place p ON p.id = a.place_id WHERE a.alias_key = ? AND a.active = 1 LIMIT 1', [$key])->getRowArray();
        if ($taken) {
            if ((int) $taken['place_id'] === $placeId) {
                throw new \InvalidArgumentException('"' . $alias . '" is already an other name of this place.');
            }
            throw new \InvalidArgumentException('"' . $alias . '" already points at ' . self::label($taken) . '. An other name points at one place only.');
        }
        $this->db->query('INSERT INTO tf_place_alias (place_id, alias, alias_key, source, active, created_at, created_by, updated_at, updated_by) VALUES (?,?,?,?,1,?,?,?,?)',
            [$placeId, $alias, mb_substr($key, 0, 190), $source === 'import' ? 'import' : 'manual', $this->now(), $this->user, $this->now(), $this->user]);
        $id = (int) $this->db->insertID();
        $this->log($placeId, 'Place to Visit master: ' . self::label($p) . ' — other name added: "' . $alias . '"', [['what' => 'other name', 'from' => null, 'to' => $alias]]);
        return ['id' => $id, 'place_id' => $placeId, 'alias' => $alias, 'alias_key' => $key];
    }

    /** Take an other name off: kept, switched off. */
    public function removeAlias(int $aliasId): array
    {
        $this->need();
        $a = $this->db->query('SELECT * FROM tf_place_alias WHERE id = ?', [$aliasId])->getRowArray();
        if (!$a || (int) $a['active'] !== 1) {
            throw new \InvalidArgumentException('That other name is not in use.');
        }
        $p = $this->placeRow((int) $a['place_id']);
        $this->db->query('UPDATE tf_place_alias SET active = 0, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $aliasId]);
        $this->log((int) $a['place_id'], 'Place to Visit master: ' . self::label($p) . ' — other name taken off: "' . $a['alias'] . '"', [['what' => 'other name', 'from' => $a['alias'], 'to' => null]]);
        return ['id' => $aliasId, 'place_id' => (int) $a['place_id'], 'alias' => (string) $a['alias']];
    }

    // =====================================================================
    //  Two places that are one
    // =====================================================================

    /**
     * Merge a duplicate place into the one kept (Dalada Maligawa into the Kandy temple entrance,
     * say), once a person has confirmed they are one place. The duplicate is kept, out of use,
     * pointing at the one kept; its name and other names become the kept one's other names; its
     * rates are switched off (kept). Where the two disagree on a rate, neither is chosen: the
     * imported rates of the kept place are switched off too and the figures written down.
     */
    public function mergePlaces(int $fromId, int $intoId): array
    {
        $this->need();
        if ($fromId === $intoId) {
            throw new \InvalidArgumentException('Choose two different places.');
        }
        $from = $this->placeRow($fromId);
        $into = $this->placeRow($intoId);
        if ($from['merged_into'] !== null || $into['merged_into'] !== null || (int) $into['active'] !== 1) {
            throw new \InvalidArgumentException('Both places must be in use and not merged already.');
        }
        $changes = [];
        foreach ($this->db->query('SELECT * FROM tf_place_alias WHERE place_id = ? AND active = 1', [$fromId])->getResultArray() as $a) {
            $this->db->query('UPDATE tf_place_alias SET active = 0, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, (int) $a['id']]);
            try {
                $this->addAlias($intoId, (string) $a['alias'], (string) $a['source']);
            } catch (\InvalidArgumentException $e) {
                // its own name already, or taken: nothing forced
            }
        }
        $notes = [];
        foreach (array_keys(self::CHARGES) as $charge) {
            $fromRates = $this->db->query('SELECT * FROM tf_place_rate WHERE place_id = ? AND charge = ? AND active = 1', [$fromId, $charge])->getResultArray();
            $intoRates = $this->db->query('SELECT * FROM tf_place_rate WHERE place_id = ? AND charge = ? AND active = 1', [$intoId, $charge])->getResultArray();
            $sig = static fn (array $rs): array => array_values(array_unique(array_map(static fn ($r) => self::rateText(self::rateRow($r)), $rs)));
            if ($fromRates && $sig($fromRates) !== $sig($intoRates)) {
                foreach ($intoRates as $r) {
                    if ((string) $r['source'] !== 'manual') {
                        $this->db->query('UPDATE tf_place_rate SET active = 0, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, (int) $r['id']]);
                    }
                }
                $notes[] = ($charge === 'jeep' ? 'Jeep' : 'Entrance') . ': ' . implode(' / ', $sig($intoRates) ?: ['none']) . ' (' . $into['name'] . ') vs ' . implode(' / ', $sig($fromRates)) . ' (' . $from['name'] . ')';
            }
            foreach ($fromRates as $r) {
                $this->db->query('UPDATE tf_place_rate SET active = 0, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, (int) $r['id']]);
                $changes[] = ['what' => 'rate of ' . $from['name'] . ' switched off (kept)', 'from' => self::rateText(self::rateRow($r)), 'to' => null];
            }
        }
        if ($notes) {
            $note = 'Conflicting rates after merging ' . $from['name'] . ': ' . implode('; ', $notes) . '. None was chosen: decide the rate and enter it.';
            $this->db->query('UPDATE tf_place SET review_note = ? WHERE id = ?', [mb_substr($note, 0, 1000), $intoId]);
            $changes[] = ['what' => 'conflicting rates', 'from' => null, 'to' => $note];
        }
        $this->db->query('UPDATE tf_place SET active = 0, merged_into = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$intoId, $this->now(), $this->user, $fromId]);
        if ((int) $from['jeep'] === 1 && (int) $into['jeep'] !== 1) {
            $this->db->query('UPDATE tf_place SET jeep = 1 WHERE id = ?', [$intoId]);
            $changes[] = ['what' => 'jeep', 'from' => '0', 'to' => '1'];
        }
        try {
            $this->addAlias($intoId, (string) $from['name'], 'manual');
        } catch (\InvalidArgumentException $e) {
            // same name as the one kept: matched already
        }
        $changes[] = ['what' => 'merged', 'from' => self::label($from), 'to' => self::label($into)];
        $this->log($intoId, 'Place to Visit master: ' . self::label($from) . ' merged into ' . self::label($into) . ' (kept, out of use; its names now point here).', $changes);
        return ['into' => $this->place($intoId)];
    }

    // =====================================================================
    //  The one-time import
    // =====================================================================

    /**
     * Every record of the old Place to Visit list as it stands — read by the one-time import
     * only, never written.
     */
    public static function oldRecords($db): array
    {
        $lkr  = self::hasColumn($db, 'entrance_fee', 'entranceFeeLkr');
        $meta = self::hasTable($db, 'tf_fee_meta');
        $rows = $db->query('SELECT e.id, e.name, e.code, e.location, e.description, e.img, e.entranceFee' . ($lkr ? ', e.entranceFeeLkr' : ', NULL AS entranceFeeLkr')
            . ', e.direct_payment, e.is_prepaid' . ($meta ? ', m.destination AS dest_set' : ', NULL AS dest_set')
            . ' FROM entrance_fee e' . ($meta ? ' LEFT JOIN tf_fee_meta m ON m.fee_id = e.id' : '') . ' ORDER BY e.id')->getResultArray();
        foreach ($rows as &$r) {
            $r['id']       = (int) $r['id'];
            $r['usd']      = $r['entranceFee'] === null || $r['entranceFee'] === '' ? 0.0 : (float) $r['entranceFee'];
            $r['lkr']      = $r['entranceFeeLkr'] === null || $r['entranceFeeLkr'] === '' ? 0.0 : (float) $r['entranceFeeLkr'];
            $r['pay_mode'] = (int) ($r['is_prepaid'] ?? 0) === 1 ? 'prepaid' : ((int) ($r['direct_payment'] ?? 0) === 1 ? 'direct' : 'in_tour');
        }
        unset($r);
        return $rows;
    }

    /** The approved import list (Database/Sql/tour_flow_place_import.json), or null when there is none. */
    public static function importList(?string $sqlDir = null): ?array
    {
        $file = rtrim($sqlDir ?? (defined('APPPATH') ? APPPATH . 'Database/Sql' : ''), '/\\') . '/' . self::IMPORT_FILE;
        if (!is_file($file)) {
            return null;
        }
        $j = json_decode((string) file_get_contents($file), true);
        return is_array($j) ? $j : null;
    }

    /** True once the one-time import has run on this site. */
    public static function imported($db): bool
    {
        try {
            return (bool) $db->query("SELECT v FROM tf_meta WHERE k = 'place_import_done'")->getRowArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function figureText(float $usd, float $lkr): string
    {
        if ($usd <= 0 && $lkr <= 0) {
            return '0.00';
        }
        return trim(($usd > 0 ? 'USD ' . number_format($usd, 2) : '') . ($usd > 0 && $lkr > 0 ? ' / ' : '') . ($lkr > 0 ? 'LKR ' . number_format($lkr, 2) : ''));
    }

    private static function logImport($db, string $source, string $ref, string $outcome, ?int $placeId, string $name, string $code, string $figure, string $reason, string $by, string $now): void
    {
        // a record already accounted for is never re-marked "not on the list"
        $db->query('INSERT ' . ($outcome === 'not_listed' ? 'IGNORE ' : '') . 'INTO tf_place_import (source, source_id, outcome, place_id, old_name, old_code, old_figure, reason, imported_by, imported_at) VALUES (?,?,?,?,?,?,?,?,?,?)'
                    . ($outcome === 'not_listed' ? '' : ' ON DUPLICATE KEY UPDATE outcome = VALUES(outcome), place_id = VALUES(place_id), reason = VALUES(reason)'),
            [$source, mb_substr($ref, 0, 100), $outcome, $placeId, mb_substr($name, 0, 255), mb_substr($code, 0, 100), mb_substr($figure, 0, 60), mb_substr($reason, 0, 500), $by, $now]);
    }

    /**
     * Schema 39: the one-time import of the approved list into the master. Run once (by Install
     * now); after that the master stands on its own and this does nothing.
     *
     *   places      each built from the old records the list names (the first is the place;
     *               the others, confirmed duplicates, become its other names) and from the
     *               Excursion Rates entrance and jeep fees it names (switched off there, kept)
     *   rates       a charge takes a rate only when every figure found for it agrees; figures
     *               that disagree, one covering the entrance and the jeep together, or one
     *               that looks like rupees give no rate and are written on the place (held)
     *   sightseeing general sightseeing names — outside the master, no entrance charge
     *   review      places whose entrance fee is not confirmed — not imported
     *   services    meals, guides, trains, treatments, shows and other activities — outside
     *               the master; the old list goes on pricing them until their own masters
     *
     * A record is found by its id and name; if the id carries another name, by its name alone
     * (one record only). Nothing is guessed, and the old list is not changed.
     *
     * @param array<string, mixed>|null $list the approved list (null: read the file)
     * @return array<int, string> notes for the installer
     */
    public static function import($db, ?array $list = null, string $by = 'installer', ?string $sqlDir = null): array
    {
        self::forget();
        if (!self::installed($db) || self::imported($db)) {
            return [];
        }
        $list ??= self::importList($sqlDir);
        $now = date('Y-m-d H:i:s');
        $done = static function () use ($db, $now): void {
            $db->query("INSERT INTO tf_meta (k, v) VALUES ('place_import_done', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [$now]);
        };
        if ($list === null) {
            $done();
            return ['Place to Visit master: no import list was found (' . self::IMPORT_FILE . '); the master starts empty — add the places on Master Files → Place to Visit / Entrance Fee Master.'];
        }
        try {
            $old = self::oldRecords($db);
        } catch (\Throwable $e) {
            $old = [];
        }
        $byId = [];
        $byName = [];
        foreach ($old as $o) {
            $byId[$o['id']] = $o;
            $byName[mb_strtolower(trim((string) $o['name']))][] = $o;
        }
        $used = [];
        $missing = [];
        $find = static function ($entry) use ($byId, $byName, &$used, &$missing): ?array {
            $id   = (int) ($entry[0] ?? 0);
            $name = trim((string) ($entry[1] ?? ''));
            $o    = $byId[$id] ?? null;
            if ($o === null || mb_strtolower(trim((string) $o['name'])) !== mb_strtolower($name)) {
                $same = $byName[mb_strtolower($name)] ?? [];
                $o = count($same) === 1 ? $same[0] : null;
            }
            if ($o === null || isset($used[$o['id']])) {
                $missing[] = $name . ($o !== null ? ' (listed twice)' : '');
                return null;
            }
            $used[$o['id']] = true;
            return $o;
        };
        $exc = [];
        try {
            foreach ($db->query('SELECT * FROM tf_excursion_rate')->getResultArray() as $x) {
                $exc[mb_strtolower(trim((string) $x['code']))] = $x;
            }
        } catch (\Throwable $e) {
            $exc = [];
        }
        $n = ['places' => 0, 'rated' => 0, 'held' => 0, 'merged' => 0, 'split' => 0, 'sightseeing' => 0, 'review' => 0, 'service' => 0, 'not_listed' => 0, 'excursion' => 0, 'aliases' => 0];

        foreach ((array) ($list['places'] ?? []) as $pl) {
            $srcs = [];
            foreach ((array) ($pl['old'] ?? []) as $entry) {
                if (($o = $find($entry)) !== null) {
                    $srcs[] = $o;
                }
            }
            $xs = [];
            foreach ((array) ($pl['excursion'] ?? []) as $code) {
                $x = $exc[mb_strtolower(trim((string) $code))] ?? null;
                if ($x !== null && (int) $x['active'] === 1) {
                    $xs[] = $x;
                } else {
                    $missing[] = 'Excursion Rates ' . $code . ($x !== null ? ' (switched off already)' : '');
                }
            }
            if (!$srcs && !$xs) {
                continue;
            }
            $first = $srcs[0] ?? null;
            $name  = trim((string) ($pl['name'] ?? ($first['name'] ?? $xs[0]['name'] ?? '')));
            $code  = self::cut($pl['code'] ?? ($first['code'] ?? null), 60);
            $mixed = array_map('intval', (array) ($pl['mixed'] ?? []));
            $jeepOld = array_map('intval', (array) ($pl['jeep_old'] ?? []));
            $cat   = in_array((string) ($pl['category'] ?? ''), self::CATEGORIES, true) ? (string) $pl['category'] : null;
            $jeep  = !empty($pl['jeep']) ? 1 : 0;
            $loc   = self::cut($pl['location'] ?? ($first['location'] ?? null), 255);
            $db->query("INSERT INTO tf_place (code, name, destination, location, map_link, description, image, category, pay_mode, jeep, active, review_note, source, created_at, created_by, updated_at, updated_by)
                        VALUES (?,?,?,?,NULL,?,?,?,?,?,1,NULL,'import',?,?,?,?)",
                [$code, mb_substr($name, 0, 255), self::cut($first['dest_set'] ?? null, 120), $loc,
                 trim((string) ($first['description'] ?? '')) === '' ? null : trim((string) $first['description']), self::cut($first['img'] ?? null, 255),
                 $cat, $first['pay_mode'] ?? 'in_tour', $jeep, $now, $by, $now, $by]);
            $pid = (int) $db->insertID();
            $n['places']++;

            // the other names: every other record's name, and the Excursion Rates names
            $names = [];
            foreach ($srcs as $i => $o) {
                if ($i > 0 || ServiceLookup::nameKey((string) $o['name']) !== ServiceLookup::nameKey($name)) {
                    $names[] = trim((string) $o['name']);
                }
            }
            foreach ($xs as $x) {
                $names[] = trim((string) $x['name']);
            }
            foreach (array_unique($names) as $alias) {
                $key = ServiceLookup::words($alias);
                if ($key === '' || ServiceLookup::nameKey($alias) === ServiceLookup::nameKey($name) || $db->query('SELECT id FROM tf_place_alias WHERE alias_key = ? AND active = 1', [$key])->getRowArray()) {
                    continue;
                }
                $db->query("INSERT INTO tf_place_alias (place_id, alias, alias_key, source, active, created_at, created_by, updated_at, updated_by) VALUES (?,?,?,'import',1,?,?,?,?)",
                    [$pid, mb_substr($alias, 0, 190), mb_substr($key, 0, 190), $now, $by, $now, $by]);
                $n['aliases']++;
            }

            // the figures, charge by charge
            $figs  = ['entrance' => [], 'jeep' => []];
            $block = ['entrance' => [], 'jeep' => []];
            foreach ($srcs as $o) {
                $txt = trim((string) $o['name']) . ' ' . self::figureText($o['usd'], $o['lkr']) . ' (old list)';
                if (in_array($o['id'], $mixed, true)) {
                    if ($o['usd'] > 0 || $o['lkr'] > 0) {
                        $why = trim((string) $o['name']) . ' holds ' . self::figureText($o['usd'], $o['lkr']) . ' a head for the safari — it does not say how much is the entrance and how much the jeep';
                        $block['entrance'][] = $why;
                        if ($jeep) {
                            $block['jeep'][] = $why;
                        }
                    }
                    continue;
                }
                $charge = in_array($o['id'], $jeepOld, true) ? 'jeep' : 'entrance';
                if ($o['usd'] <= 0 && $o['lkr'] <= 0) {
                    continue;   // 0.00: no figure
                }
                if ($charge === 'jeep') {
                    $block['jeep'][] = trim((string) $o['name']) . ' holds ' . self::figureText($o['usd'], $o['lkr']) . ' a head in the old list, not per vehicle';
                } elseif ($o['usd'] > self::USD_SANITY) {
                    $block['entrance'][] = $txt . ' — looks like rupees in the $ column';
                } elseif ($o['usd'] > 0 && $o['lkr'] > 0) {
                    $block['entrance'][] = $txt . ' — two figures';
                } else {
                    $figs['entrance'][] = ['cur' => $o['usd'] > 0 ? 'USD' : 'LKR', 'amt' => $o['usd'] > 0 ? $o['usd'] : $o['lkr'], 'from' => null, 'to' => null, 'txt' => $txt, 'ref' => (string) $o['id']];
                }
            }
            foreach ($xs as $x) {
                $unit   = (string) $x['unit'];
                $charge = $unit === 'per_vehicle' ? 'jeep' : 'entrance';
                $cur    = strtoupper((string) ($x['currency'] ?: 'USD'));
                $txt    = trim((string) $x['name']) . ' ' . $cur . ' ' . number_format((float) $x['rate'], 2) . ($unit === 'per_vehicle' ? ' per vehicle' : ($unit === 'per_group' ? ' per group' : ' a head')) . ' (Excursion Rates ' . $x['code'] . ')';
                if ($charge === 'jeep' && !$jeep) {
                    $block['entrance'][] = $txt . ' — charged per vehicle, but the place has no jeep';
                } elseif ($unit === 'per_group' || !in_array($cur, self::CURRENCIES, true)) {
                    $block[$charge][] = $txt . ' — not a head / per vehicle figure in USD or LKR';
                } elseif ((float) $x['rate'] > 0) {
                    $figs[$charge][] = ['cur' => $cur, 'amt' => round((float) $x['rate'], 2), 'from' => $x['valid_from'] ?: null, 'to' => $x['valid_to'] ?: null, 'txt' => $txt, 'ref' => (string) $x['code']];
                }
            }
            $notes = [];
            $rated = ['entrance' => false, 'jeep' => false];
            foreach (['entrance', 'jeep'] as $charge) {
                if ($charge === 'jeep' && !$jeep) {
                    continue;
                }
                $f = $figs[$charge];
                $distinct = array_unique(array_map(static fn ($x) => $x['cur'] . ' ' . number_format($x['amt'], 2), $f));
                if (!$block[$charge] && count($distinct) === 1) {
                    $open = array_filter($f, static fn ($x) => $x['from'] === null && $x['to'] === null);
                    $per  = array_unique(array_map(static fn ($x) => ($x['from'] ?? '') . '|' . ($x['to'] ?? ''), $f));
                    $from = $open || count($per) !== 1 ? null : $f[0]['from'];
                    $to   = $open || count($per) !== 1 ? null : $f[0]['to'];
                    $db->query("INSERT INTO tf_place_rate (place_id, charge, valid_from, valid_to, free_entry, adult_rate, child_rate, currency, note, source, source_ref, active, created_at, created_by, updated_at, updated_by)
                                VALUES (?,?,?,?,0,?,NULL,?,?,'import',?,1,?,?,?,?)",
                        [$pid, $charge, $from, $to, $f[0]['amt'], $f[0]['cur'], mb_substr('Imported: ' . implode('; ', array_map(static fn ($x) => $x['txt'], $f)), 0, 255),
                         mb_substr(implode(',', array_map(static fn ($x) => $x['ref'], $f)), 0, 100), $now, $by, $now, $by]);
                    $rated[$charge] = true;
                    continue;
                }
                $all = array_merge(array_map(static fn ($x) => $x['txt'], $f), $block[$charge]);
                if ($all) {
                    $notes[] = ($charge === 'jeep' ? 'Jeep' : 'Entrance') . ': ' . implode('; ', $all) . ' — none chosen';
                }
            }
            if ($notes) {
                $db->query('UPDATE tf_place SET review_note = ? WHERE id = ?', [mb_substr(implode('. ', $notes) . '. Decide the rate and enter it.', 0, 1000), $pid]);
            }
            if ($rated['entrance'] && (!$jeep || $rated['jeep'])) {
                $n['rated']++;
            } else {
                $n['held']++;
            }

            // the import log: each old record and Excursion Rates fee, and where it went
            foreach ($srcs as $i => $o) {
                $outcome = in_array($o['id'], $mixed, true) ? 'split' : ($i === 0 ? 'imported' : 'merged');
                $reason  = $outcome === 'split' ? 'Entrance and jeep together in one figure: imported as the place, its entrance and its jeep kept apart; the figure is not split.'
                    : ($outcome === 'merged' ? (in_array($o['id'], $jeepOld, true) ? 'The jeep of ' . $name . ': its name is an other name of it.' : 'The same place as ' . $name . ': its name is an other name of it.') : 'Imported as ' . $name . '.');
                self::logImport($db, 'old_list', (string) $o['id'], $outcome, $pid, (string) $o['name'], trim((string) ($o['code'] ?? '')), self::figureText($o['usd'], $o['lkr']), $reason, $by, $now);
                if ($outcome !== 'imported') {
                    $n[$outcome]++;
                }
            }
            foreach ($xs as $x) {
                self::logImport($db, 'excursion', (string) $x['code'], 'imported', $pid, (string) $x['name'], (string) $x['code'],
                    strtoupper((string) ($x['currency'] ?: 'USD')) . ' ' . number_format((float) $x['rate'], 2) . ' ' . str_replace('_', ' ', (string) $x['unit']),
                    'Its ' . ((string) $x['unit'] === 'per_vehicle' ? 'jeep' : 'entrance') . ' figure is read into ' . $name . '; switched off under Excursion Rates (kept), so the master is the one place it is priced.', $by, $now);
                $db->query('UPDATE tf_excursion_rate SET active = 0, updated_by = ?, updated_at = ? WHERE code = ?', [$by, $now, $x['code']]);
                $n['excursion']++;
            }
        }

        foreach (['sightseeing' => 'General sightseeing: outside the master — no entrance charge from it.',
                  'review'      => 'Not imported: its entrance fee is not confirmed. Add it to the master once it is (with its rate, or as Free).',
                  'services'    => 'A meal, a service or an activity: outside the master — the old list goes on pricing it until its own master.'] as $group => $reason) {
            foreach ((array) ($list[$group] ?? []) as $entry) {
                if (($o = $find($entry)) === null) {
                    continue;
                }
                $outcome = $group === 'services' ? 'service' : $group;
                $why = trim((string) ($entry[2] ?? ''));
                self::logImport($db, 'old_list', (string) $o['id'], $outcome, null, (string) $o['name'], trim((string) ($o['code'] ?? '')), self::figureText($o['usd'], $o['lkr']), $why !== '' ? $reason . ' ' . $why : $reason, $by, $now);
                $n[$outcome]++;
            }
        }
        foreach ($old as $o) {
            if (!isset($used[$o['id']])) {
                self::logImport($db, 'old_list', (string) $o['id'], 'not_listed', null, (string) $o['name'], trim((string) ($o['code'] ?? '')), self::figureText($o['usd'], $o['lkr']),
                    'Not on the approved import list: not imported; the old list prices it as before (never as an entrance or a safari).', $by, $now);
                $n['not_listed']++;
            }
        }

        // the other names of Admin → Settings that point at a record now in the master (one time)
        try {
            $r = $db->query("SELECT v FROM tf_meta WHERE k = 'service_aliases'")->getRowArray();
        } catch (\Throwable $e) {
            $r = null;
        }
        foreach (preg_split('/\r?\n/', (string) ($r['v'] ?? '')) ?: [] as $line) {
            if (!str_contains($line, '=>')) {
                continue;
            }
            [$phrase, $code] = array_map('trim', explode('=>', $line, 2));
            $key = ServiceLookup::words($phrase);
            if ($key === '' || $code === '' || $db->query('SELECT id FROM tf_place_alias WHERE alias_key = ? AND active = 1', [$key])->getRowArray()) {
                continue;
            }
            $hits = $db->query("SELECT DISTINCT place_id FROM tf_place_import WHERE source = 'old_list' AND place_id IS NOT NULL AND LOWER(TRIM(old_code)) = LOWER(TRIM(?))", [$code])->getResultArray();
            if (count($hits) !== 1) {
                continue;
            }
            $db->query("INSERT INTO tf_place_alias (place_id, alias, alias_key, source, active, created_at, created_by, updated_at, updated_by) VALUES (?,?,?,'import',1,?,?,?,?)",
                [(int) $hits[0]['place_id'], mb_substr($phrase, 0, 190), mb_substr($key, 0, 190), $now, $by, $now, $by]);
            $n['aliases']++;
        }

        // Ticket Stock: an attraction already under control by an old record follows the place it became
        try {
            if (self::hasColumn($db, 'tf_ticket_attraction', 'place_id')) {
                $db->query("UPDATE tf_ticket_attraction a JOIN tf_place_import t ON t.source = 'old_list' AND t.source_id = CAST(a.fee_id AS CHAR) AND t.place_id IS NOT NULL
                            SET a.place_id = t.place_id WHERE a.place_id IS NULL AND a.fee_id IS NOT NULL");
            }
        } catch (\Throwable $e) {
            // nothing under control, or the stock is not installed
        }

        $done();
        return ['Place to Visit / Entrance Fee Master imported once from the approved list (the old list is unchanged and is not read for entrances or safaris again): '
            . $n['places'] . ' place(s) — ' . $n['rated'] . ' with their rates, ' . $n['held'] . ' held for a rate (no figure, or figures that disagree: none chosen); '
            . $n['merged'] . ' duplicate record(s) merged in as other names; ' . $n['split'] . ' safari record(s) with the entrance and jeep together kept apart; '
            . $n['excursion'] . ' Excursion Rates fee(s) read in and switched off there (kept). Not imported: ' . $n['sightseeing'] . ' general sightseeing, ' . $n['review'] . ' held for review, '
            . $n['service'] . ' meals / services / activities' . ($n['not_listed'] ? ', ' . $n['not_listed'] . ' not on the list' : '') . '. '
            . ($missing ? 'Named on the list but not found here: ' . implode('; ', array_slice($missing, 0, 20)) . '. ' : '') . 'The full report is on the master.'];
    }

    /** The import as it happened: every old record and Excursion Rates fee, with the counts that show nothing was lost. */
    public function importReport(): array
    {
        $this->need();
        $rows = $this->db->query('SELECT t.*, p.name AS place_name, p.code AS place_code, p.merged_into FROM tf_place_import t LEFT JOIN tf_place p ON p.id = t.place_id ORDER BY t.source, t.outcome, t.old_name')->getResultArray();
        $c = ['old' => 0, 'excursion' => 0, 'imported' => 0, 'merged' => 0, 'split' => 0, 'sightseeing' => 0, 'review' => 0, 'service' => 0, 'not_listed' => 0];
        $out = [];
        foreach ($rows as $t) {
            if ($t['source'] === 'old_list') {
                $c['old']++;
                $c[$t['outcome']] = ($c[$t['outcome']] ?? 0) + 1;
            } else {
                $c['excursion']++;
            }
            $out[] = ['source' => (string) $t['source'], 'ref' => (string) $t['source_id'], 'name' => (string) $t['old_name'], 'code' => (string) $t['old_code'], 'figure' => (string) $t['old_figure'],
                'outcome' => (string) $t['outcome'], 'reason' => (string) $t['reason'], 'place_id' => $t['place_id'] === null ? null : (int) $t['place_id'],
                'place' => $t['place_name'] ?? null, 'place_code' => $t['place_code'] ?? null, 'at' => $t['imported_at'] ?? null];
        }
        try {
            $c['old_now'] = (int) ($this->db->query('SELECT COUNT(*) n FROM entrance_fee')->getRowArray()['n'] ?? 0);
            // records added to the old list after the import: not read — the master does not follow the old list
            $c['old_since'] = (int) ($this->db->query("SELECT COUNT(*) n FROM entrance_fee e WHERE NOT EXISTS (SELECT 1 FROM tf_place_import t WHERE t.source = 'old_list' AND t.source_id = CAST(e.id AS CHAR))")->getRowArray()['n'] ?? 0);
        } catch (\Throwable $e) {
            $c['old_now'] = null;
            $c['old_since'] = null;
        }
        $c['in_master'] = $c['imported'] + $c['merged'] + $c['split'];
        $c['done'] = $this->db->query("SELECT v FROM tf_meta WHERE k = 'place_import_done'")->getRowArray()['v'] ?? null;
        return ['counts' => $c, 'rows' => $out];
    }

    // =====================================================================
    //  Checks
    // =====================================================================

    /** The place in the master under a name (its own or an other name), or null. The Free Visits master refuses those. */
    public function placeNamed(string $name): ?array
    {
        if (!self::installed($this->db)) {
            return null;
        }
        $p = $this->placeByName($name);
        return $p && (int) $p['active'] === 1 ? ['id' => (int) $p['id'], 'name' => (string) $p['name'], 'code' => trim((string) ($p['code'] ?? ''))] : null;
    }

    /** Free visits whose name is a place of the master: the master decides, so they are listed to put right. */
    public function conflicts(): array
    {
        $this->need();
        $out = [];
        try {
            $free = $this->db->query('SELECT id, name FROM tf_free_visit WHERE active = 1')->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($free as $f) {
            if (($p = $this->placeNamed((string) $f['name'])) !== null) {
                $out[] = ['free_id' => (int) $f['id'], 'free_name' => (string) $f['name'], 'place_id' => $p['id'], 'place' => $p['name'], 'code' => $p['code']];
            }
        }
        return $out;
    }

    /** What the master makes of a line on a date, read exactly as a quotation line is read (at a safari park $qty is the jeeps). For the "Try a line" box and the tests. */
    public function check(string $title, ?string $date = null, ?string $kind = null, ?int $adults = null, ?int $children = null, $qty = null): array
    {
        $look = new ServiceLookup($this->db);
        $pax  = ($adults ?? 0) + ($children ?? 0);
        $pax  = $pax > 0 ? $pax : null;
        $line = ['title' => $title, 'kind' => $kind ?: null, 'pay_mode' => 'unknown', 'origin' => 'ai', 'qty' => $qty === null || $qty === '' ? null : (float) $qty, 'qty_override' => $qty === null || $qty === '' ? 0 : 1];
        $out  = $look->apply($line, $pax, $date ?: date('Y-m-d'), ['adults' => $adults, 'children' => $children]);
        $c    = ServiceLookup::classify($out);
        return [
            'title' => $title, 'kind' => $out['kind'], 'date' => $date ?: date('Y-m-d'),
            'state' => $c['state'], 'costed' => $c['costed'], 'blocks' => $c['blocks'], 'why' => $c['why'],
            'match_state' => $out['match_state'], 'note' => $out['match_note'],
            'place_id' => $out['place_id'] ?? null, 'fee_id' => $out['fee_id'] ?? null, 'fee_code' => $out['fee_code'] ?? null,
            'unit_usd' => $out['unit_usd'] ?? null, 'child_usd' => $out['child_usd'] ?? null, 'jeep_usd' => $out['jeep_usd'] ?? null, 'qty' => $out['qty'] ?? null,
            'amount_usd' => $c['costed'] ? ($out['amount_usd'] ?? null) : null,
            'rate_currency' => $out['rate_currency'] ?? null, 'rate_adult' => $out['rate_adult'] ?? null, 'rate_child' => $out['rate_child'] ?? null,
            'suggest_code' => $out['suggest_code'] ?? null,
        ];
    }

    /**
     * Everything ServiceLookup needs to read a line against the master in one go: the places by
     * id (with where a merged one points), by code, by name; the other names; the rates; the
     * place each imported old record became; the old records the old list no longer prices
     * (places, general sightseeing, places held for review); and the ones the approved list left
     * with it as meals, services and activities.
     */
    public static function index($db): array
    {
        $ix = ['places' => [], 'by_code' => [], 'by_name' => [], 'by_fee' => [], 'aliases' => [], 'off_names' => [], 'rates' => [], 'old_out' => [], 'services' => []];
        if (!self::installed($db)) {
            return $ix;
        }
        foreach ($db->query('SELECT * FROM tf_place')->getResultArray() as $p) {
            $p['id'] = (int) $p['id'];
            $ix['places'][$p['id']] = $p;
        }
        $ambiguous = [];
        foreach ($db->query("SELECT source_id, outcome, place_id, old_code FROM tf_place_import WHERE source = 'old_list'")->getResultArray() as $t) {
            $fid = (int) $t['source_id'];
            $oc  = mb_strtolower(trim((string) $t['old_code']));
            if ($t['place_id'] !== null) {
                $ix['by_fee'][$fid] = (int) $t['place_id'];
                $ix['old_out'][$fid] = (string) $t['outcome'];
                if ($oc !== '') {
                    $ix['by_code'][$oc] ??= (int) $t['place_id'];
                }
            } else {
                if (in_array((string) $t['outcome'], ['sightseeing', 'review'], true)) {
                    $ix['old_out'][$fid] = (string) $t['outcome'];
                } elseif ((string) $t['outcome'] === 'service') {
                    $ix['services'][$fid] = true;   // a meal, a service or an activity the approved list left with the old list
                }
                if ($oc !== '') {
                    $ambiguous[$oc] = true;   // a code the old list also gives a meal, a service or another record: never read as a place's code
                }
            }
        }
        foreach ($ix['places'] as $id => $p) {
            if ((int) $p['active'] !== 1 && $p['merged_into'] === null && ($nk = ServiceLookup::nameKey((string) $p['name'])) !== '') {
                $ix['off_names'][$nk] ??= $id;   // a place taken out of use: a line naming it is held as such, never read as another
            }
            if ((int) $p['active'] !== 1 || $p['merged_into'] !== null) {
                continue;
            }
            $code = mb_strtolower(trim((string) ($p['code'] ?? '')));
            if ($code !== '') {
                $ix['by_code'][$code] ??= $id;
            }
            $nk = ServiceLookup::nameKey((string) $p['name']);
            if ($nk !== '') {
                $ix['by_name'][$nk] ??= $id;
            }
        }
        foreach (array_keys($ambiguous) as $oc) {
            unset($ix['by_code'][$oc]);
        }
        foreach ($db->query('SELECT place_id, alias_key FROM tf_place_alias WHERE active = 1')->getResultArray() as $a) {
            if ((string) $a['alias_key'] !== '') {
                $ix['aliases'][(string) $a['alias_key']] = (int) $a['place_id'];
            }
        }
        uksort($ix['aliases'], static fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($db->query('SELECT * FROM tf_place_rate WHERE active = 1 ORDER BY place_id, valid_from')->getResultArray() as $r) {
            $ix['rates'][(int) $r['place_id']][] = self::rateRow($r);
        }
        return $ix;
    }

    // =====================================================================
    //  tf-safari-r1: jeep capacity, child age rules, the jeep operator and the ticket supplier, delete / archive
    // =====================================================================

    public const ROLES = ['jeep_operator' => 'Jeep operator', 'ticket_supplier' => 'Entrance authority / ticket supplier'];
    public const PARTY_FIELDS = ['business_name' => 190, 'contact_person' => 120, 'phone' => 60, 'whatsapp' => 60, 'email' => 190, 'address' => 255,
        'booking_instructions' => 4000, 'payment_terms' => 255, 'account_holder' => 190, 'bank' => 120, 'branch' => 120, 'bank_code' => 20, 'branch_code' => 20, 'swift_code' => 20];

    private static array $rulesCache = [];

    /** A place's jeep capacity and child age rules (null when not set), read once per request. */
    public static function rules($db, ?int $placeId): array
    {
        $none = ['seats' => null, 'age_from' => null, 'age_to' => null, 'age_note' => null];
        if (!$placeId) {
            return $none;
        }
        $k = (is_object($db) ? spl_object_id($db) : 0) . ':' . $placeId;
        if (!array_key_exists($k, self::$rulesCache)) {
            $r = null;
            try {
                if (self::hasColumn($db, 'tf_place', 'jeep_seats')) {
                    $r = $db->query('SELECT jeep_seats, child_age_from, child_age_to, age_note FROM tf_place WHERE id = ?', [$placeId])->getRowArray();
                }
            } catch (\Throwable $e) {
                $r = null;
            }
            self::$rulesCache[$k] = $r ? ['seats' => $r['jeep_seats'] !== null ? (int) $r['jeep_seats'] : null,
                'age_from' => $r['child_age_from'] !== null ? (float) $r['child_age_from'] : null,
                'age_to' => $r['child_age_to'] !== null ? (float) $r['child_age_to'] : null, 'age_note' => $r['age_note']] : $none;
        }
        return self::$rulesCache[$k];
    }

    /** The jeeps a party needs: every passenger, adults and children, takes a seat. Null when either is unknown. */
    public static function jeepsNeeded(?int $pax, ?int $seats): ?int
    {
        return ($pax !== null && $pax > 0 && $seats !== null && $seats > 0) ? (int) ceil($pax / $seats) : null;
    }

    public static function ageRuleText(?float $from, ?float $to): string
    {
        $n = static fn (float $x): string => rtrim(rtrim(number_format($x, 2), '0'), '.');
        if ($from === null && $to === null) {
            return 'No age rule set: every child of the party pays the child rate.';
        }
        $parts = [];
        if ($from !== null && $from > 0) {
            $parts[] = 'under ' . $n($from) . ' years: free';
        }
        $parts[] = 'child rate ' . ($from !== null && $from > 0 ? 'from ' . $n($from) . ' ' : '') . ($to !== null ? 'up to ' . $n($to) . ' years' : '(no upper age)');
        if ($to !== null) {
            $parts[] = 'older: adult rate';
        }
        return ucfirst(implode('; ', $parts)) . '.';
    }

    /**
     * What the children pay for an entrance, child by child: under the free age nothing, within the child ages the child rate,
     * above them the adult rate; a child whose age is not given pays the child rate (and is said so). total is null when a child
     * should pay the child rate and there is none.
     *
     * @param array<int, float|null> $ages
     */
    public static function childSplit(float $adultUnit, ?float $childUnit, array $ages, int $kids, ?float $from, ?float $to): array
    {
        $n = static fn (float $x): string => rtrim(rtrim(number_format($x, 2), '0'), '.');
        $out = ['total' => 0.0, 'free' => 0, 'child' => 0, 'adult' => 0, 'no_age' => 0, 'parts' => []];
        for ($i = 0; $i < $kids; $i++) {
            $age = $ages[$i] ?? null;
            $who = 'child ' . ($i + 1) . ($age !== null ? ' (' . $n((float) $age) . ' yrs)' : ' (age not given)');
            if ($age !== null && $from !== null && $age < $from) {
                $out['free']++;
                $out['parts'][] = $who . ': free (under ' . $n($from) . ')';
                continue;
            }
            if ($age !== null && $to !== null && $age > $to) {
                $out['adult']++;
                $out['total'] += $adultUnit;
                $out['parts'][] = $who . ': adult rate ' . number_format($adultUnit, 2) . ' (over ' . $n($to) . ')';
                continue;
            }
            if ($childUnit === null) {
                $out['total'] = null;
                $out['parts'][] = $who . ': no child rate recorded';
                return $out;
            }
            $out['child']++;
            if ($age === null) {
                $out['no_age']++;
            }
            $out['total'] += $childUnit;
            $out['parts'][] = $who . ': child rate ' . number_format($childUnit, 2);
        }
        $out['total'] = round((float) $out['total'], 2);
        return $out;
    }

    private static function mask(?string $acc): ?string
    {
        $d = preg_replace('/\s+/', '', (string) $acc);
        return $d === '' ? null : '•••• ' . substr($d, -4);
    }

    /** The jeep operator and the ticket supplier of every place; account numbers masked; the linked supplier record's details beside. */
    public function partiesByPlace(): array
    {
        $out = [];
        if (!self::hasTable($this->db, 'tf_place_party')) {
            return $out;
        }
        $rows = $this->db->query('SELECT p.*, s.Sup_Acc_No AS s_acc, s.Name AS s_name, s.Address AS s_address, s.TP AS s_tp, s.Mobile AS s_mobile, s.email AS s_email,
                s.rep_offce AS s_rep, s.rep_mobile AS s_rep_mobile, s.acc_holder AS s_holder, s.sup_bank_acc AS s_bank_acc, s.bank_name AS s_bank, s.branch_name AS s_branch,
                s.bank_id AS s_bank_id, s.branch_id AS s_branch_id, s.cr_dyz AS s_cr_days
            FROM tf_place_party p LEFT JOIN supplier s ON s.ID = p.supplier_id ORDER BY p.place_id, p.role')->getResultArray();
        foreach ($rows as $r) {
            $own = [];
            foreach (array_keys(self::PARTY_FIELDS) as $f) {
                $own[$f] = $r[$f] !== null && trim((string) $r[$f]) !== '' ? (string) $r[$f] : null;
            }
            $own['account_masked'] = self::mask($r['account_number']);
            $sup = $r['supplier_id'] !== null && $r['s_name'] !== null ? [
                'id' => (int) $r['supplier_id'], 'acc_no' => $r['s_acc'], 'name' => $r['s_name'], 'address' => $r['s_address'], 'phone' => trim(implode(' / ', array_filter([$r['s_tp'], $r['s_mobile']]))) ?: null,
                'email' => $r['s_email'], 'contact' => $r['s_rep'], 'contact_mobile' => $r['s_rep_mobile'], 'account_holder' => $r['s_holder'], 'account_masked' => self::mask($r['s_bank_acc']),
                'bank' => $r['s_bank'], 'branch' => $r['s_branch'], 'bank_code' => $r['s_bank_id'], 'branch_code' => $r['s_branch_id'],
                'payment_terms' => ($r['s_cr_days'] !== null && (int) $r['s_cr_days'] > 0) ? 'Credit ' . (int) $r['s_cr_days'] . ' days (supplier record)' : null,
            ] : null;
            $out[(int) $r['place_id']][(string) $r['role']] = ['role' => (string) $r['role'], 'supplier_id' => $r['supplier_id'] !== null ? (int) $r['supplier_id'] : null,
                'own' => $own, 'supplier' => $sup, 'updated_at' => $r['updated_at'], 'updated_by' => $r['updated_by']];
        }
        return $out;
    }

    /** Suppliers to link, by name or account code (never their passwords or bank numbers). */
    public function suppliers(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        return $this->db->query('SELECT ID AS id, Sup_Acc_No AS acc_no, Name AS name FROM supplier WHERE Name LIKE ? OR Sup_Acc_No LIKE ? ORDER BY Name LIMIT 25', ['%' . $q . '%', '%' . $q . '%'])->getResultArray();
    }

    /** Save a place's jeep operator or ticket supplier. A blank account number keeps the one saved; clear_account removes it. */
    public function saveParty(array $v): array
    {
        $this->need();
        if (!self::hasTable($this->db, 'tf_place_party')) {
            throw new \RuntimeException('Install the update first (Tour Inquiry → Install now).');
        }
        $placeId = (int) ($v['place_id'] ?? 0);
        $p = $this->placeRow($placeId);
        $role = (string) ($v['role'] ?? '');
        if (!isset(self::ROLES[$role])) {
            throw new \InvalidArgumentException('Choose: the jeep operator or the entrance authority / ticket supplier.');
        }
        if ($role === 'jeep_operator' && (int) $p['jeep'] !== 1) {
            throw new \InvalidArgumentException(self::label($p) . ' is not a safari park with a jeep.');
        }
        $sid = isset($v['supplier_id']) && (string) $v['supplier_id'] !== '' ? (int) $v['supplier_id'] : null;
        if ($sid !== null && !$this->db->query('SELECT ID FROM supplier WHERE ID = ?', [$sid])->getRowArray()) {
            throw new \InvalidArgumentException('That supplier is not in the Supplier master.');
        }
        $was = $this->db->query('SELECT * FROM tf_place_party WHERE place_id = ? AND role = ?', [$placeId, $role])->getRowArray();
        $vals = ['supplier_id' => $sid];
        foreach (self::PARTY_FIELDS as $f => $len) {
            $vals[$f] = array_key_exists($f, $v) ? self::cut($v[$f], $len) : ($was[$f] ?? null);
        }
        if ($vals['email'] !== null && !filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('The e-mail address does not look right.');
        }
        $acc = trim((string) ($v['account_number'] ?? ''));
        if (!empty($v['clear_account'])) {
            $vals['account_number'] = null;
        } elseif ($acc !== '') {
            if (!preg_match('/^[0-9A-Za-z \-]{4,60}$/', $acc)) {
                throw new \InvalidArgumentException('The account number is letters, figures, spaces or dashes (4 to 60).');
            }
            $vals['account_number'] = $acc;
        } else {
            $vals['account_number'] = $was['account_number'] ?? null;
        }
        $changes = [];
        foreach ($vals as $k => $val) {
            if ((string) ($was[$k] ?? '') !== (string) ($val ?? '')) {
                $changes[] = $k === 'account_number' ? ['what' => 'account number', 'from' => self::mask($was[$k] ?? null), 'to' => self::mask($val)] : ['what' => $k, 'from' => $was[$k] ?? null, 'to' => $val];
            }
        }
        $cols = array_keys($vals);
        if ($was) {
            $this->db->query('UPDATE tf_place_party SET ' . implode(', ', array_map(static fn ($c) => "`{$c}` = ?", $cols)) . ', updated_at = ?, updated_by = ? WHERE id = ?',
                array_merge(array_values($vals), [$this->now(), $this->user, (int) $was['id']]));
        } else {
            $this->db->query('INSERT INTO tf_place_party (place_id, role, `' . implode('`, `', $cols) . '`, updated_at, updated_by) VALUES (?, ?, ' . implode(',', array_fill(0, count($cols), '?')) . ', ?, ?)',
                array_merge([$placeId, $role], array_values($vals), [$this->now(), $this->user]));
        }
        if ($changes) {
            $this->log($placeId, 'Place to Visit master: ' . self::label($p) . ' — ' . self::ROLES[$role] . ' details ' . ($was ? 'changed' : 'added'), $changes);
        }
        return $this->partiesByPlace()[$placeId][$role] ?? [];
    }

    /** What Delete would do: remove for good (never used) or archive (used in bookings, Ticket Stock, the import or a merge). */
    public function deleteCheck(int $id): array
    {
        $this->need();
        $p = $this->placeRow($id);
        $n = static function ($db, string $sql, array $b): int {
            try {
                return (int) ($db->query($sql, $b)->getRowArray()['n'] ?? 0);
            } catch (\Throwable $e) {
                return 0;
            }
        };
        $why = [];
        if (($k = $n($this->db, 'SELECT COUNT(*) n FROM tf_intake_service WHERE place_id = ?', [$id])) > 0) {
            $why[] = $k . ' booking line(s) on inquiries or tours';
        }
        if (self::hasColumn($this->db, 'tf_ticket_attraction', 'place_id') && $n($this->db, 'SELECT COUNT(*) n FROM tf_ticket_attraction WHERE place_id = ?', [$id]) > 0) {
            $why[] = 'Ticket Stock';
        }
        if (($k = $n($this->db, 'SELECT COUNT(*) n FROM tf_place WHERE merged_into = ?', [$id])) > 0) {
            $why[] = $k . ' place(s) merged into it';
        }
        if ($n($this->db, 'SELECT COUNT(*) n FROM tf_place_import WHERE place_id = ?', [$id]) > 0) {
            $why[] = 'the record of the import from the old list';
        }
        return ['place' => self::label($p), 'archive' => $why !== [], 'why' => $why, 'archived' => !empty($p['archived_at'])];
    }

    /** Delete a place: archived (kept, not offered) when anything used it; otherwise removed with its rates, names and details. */
    public function deletePlace(int $id, ?string $note): array
    {
        $chk = $this->deleteCheck($id);
        $p = $this->placeRow($id);
        $note = self::cut($note, 255);
        if ($chk['archive']) {
            if (!self::hasColumn($this->db, 'tf_place', 'archived_at')) {
                throw new \RuntimeException('Install the update first (Tour Inquiry → Install now).');
            }
            $this->db->query('UPDATE tf_place SET active = 0, archived_at = ?, archived_by = ?, archive_note = ?, updated_at = ?, updated_by = ? WHERE id = ?',
                [$this->now(), $this->user, $note, $this->now(), $this->user, $id]);
            $this->log($id, 'Place to Visit master: ' . self::label($p) . ' archived (used by ' . implode(', ', $chk['why']) . ' — kept for that history, no longer offered)' . ($note ? ': ' . $note : ''),
                [['what' => 'archived', 'from' => null, 'to' => $this->now()]]);
            return ['done' => 'archived'] + $chk;
        }
        $this->db->transStart();
        $this->db->query('DELETE FROM tf_place_rate WHERE place_id = ?', [$id]);
        $this->db->query('DELETE FROM tf_place_alias WHERE place_id = ?', [$id]);
        if (self::hasTable($this->db, 'tf_place_party')) {
            $this->db->query('DELETE FROM tf_place_party WHERE place_id = ?', [$id]);
        }
        $this->db->query('DELETE FROM tf_place WHERE id = ?', [$id]);
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('The place could not be deleted. Nothing was changed.');
        }
        $this->log($id, 'Place to Visit master: ' . self::label($p) . ' deleted (never used)' . ($note ? ': ' . $note : ''), [['what' => 'place', 'from' => self::label($p), 'to' => null]]);
        return ['done' => 'deleted'] + $chk;
    }

    /** Bring an archived place back into use. */
    public function restorePlace(int $id): array
    {
        $this->need();
        $p = $this->placeRow($id);
        if (empty($p['archived_at'])) {
            throw new \InvalidArgumentException(self::label($p) . ' is not archived.');
        }
        $this->db->query('UPDATE tf_place SET active = 1, archived_at = NULL, archived_by = NULL, archive_note = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $id]);
        $this->log($id, 'Place to Visit master: ' . self::label($p) . ' restored from the archive', [['what' => 'archived', 'from' => $p['archived_at'], 'to' => null]]);
        return $this->place($id) ?? [];
    }

    // =====================================================================
    //  tf-safari-r2: the safari payment slip — one per payee, with its reference, the tour / file number and the breakdown
    // =====================================================================

    /**
     * The payment slip(s) for one safari line of an inquiry or tour: worked out from the master as it is now (the rates on the
     * safari date, the children's age rules, the jeeps on the line or the jeeps the party needs). Where the jeep and the
     * entrance are paid together, one slip to the jeep operator carries both; otherwise one slip each. Each slip keeps a
     * reference number of its own; when its figures change the reference stays and the revision goes up.
     */
    public function slips(int $intakeId, int $serviceId): array
    {
        $this->need();
        if (!self::hasTable($this->db, 'tf_safari_slip')) {
            throw new \RuntimeException('Install the update first (Tour Inquiry → Install now).');
        }
        $it = $this->db->query('SELECT * FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray();
        $s  = $this->db->query('SELECT * FROM tf_intake_service WHERE id = ? AND intake_id = ?', [$serviceId, $intakeId])->getRowArray();
        if (!$it || !$s) {
            throw new \InvalidArgumentException('That safari line is not on this inquiry.');
        }
        if (empty($s['place_id'])) {
            throw new \InvalidArgumentException('This line is not priced from the Place to Visit master, so there is no safari to pay.');
        }
        $p = $this->placeRow((int) $s['place_id']);
        if ((int) $p['jeep'] !== 1) {
            throw new \InvalidArgumentException(self::label($p) . ' is not a safari park.');
        }
        $day = $this->db->query('SELECT day_date FROM tf_intake_day WHERE intake_id = ? AND day_no = ? AND day_date IS NOT NULL LIMIT 1', [$intakeId, (int) $s['day_no']])->getRowArray();
        $date = $day['day_date'] ?? TourDates::dateOf($it['arrival_date'] ?? null, (int) $s['day_no']);
        if (!$date) {
            throw new \InvalidArgumentException('The safari has no date yet: set the arrival date first.');
        }
        $rates = $this->ratesByPlace()[(int) $p['id']] ?? [];
        $ent = self::pick($rates, $date, 'entrance');
        $jr  = self::pick($rates, $date, 'jeep');
        $rules = self::rules($this->db, (int) $p['id']);
        $adults = (int) ($it['pax_adults'] ?? 0);
        $kids = ChildFares::children($it);
        $pax = (int) ($it['pax_total'] ?? 0) ?: $adults + count($kids);
        if ($adults + count($kids) !== $pax) {
            throw new \InvalidArgumentException('The party does not add up (adults ' . $adults . ' + children ' . count($kids) . ' ≠ ' . $pax . '): correct it on the inquiry first.');
        }
        $qty = ServiceLookup::effectiveQty($s);
        $jeeps = $qty !== null ? (int) $qty : self::jeepsNeeded($pax, $rules['seats']);
        $problems = [];
        if (!$ent) {
            $problems[] = 'no entrance rate on ' . self::fmtDate($date);
        }
        if (!$jr) {
            $problems[] = 'no jeep rate on ' . self::fmtDate($date);
        }
        if ($jeeps === null) {
            $problems[] = 'the number of jeeps (give it on the line, or the jeep capacity in the master)';
        }
        if ($problems) {
            throw new \InvalidArgumentException('The payment slip cannot be worked out yet: ' . implode('; ', $problems) . '.');
        }

        // ---- the breakdown, in the rate's own currency
        $n = static fn (float $x): string => rtrim(rtrim(number_format($x, 2), '0'), '.');
        $entLines = [];
        $entFree = (int) $ent['free_entry'] === 1;
        if ($adults > 0) {
            $entLines[] = ['what' => 'Entrance fee — adults', 'qty' => $adults, 'rate' => $entFree ? 0.0 : $ent['adult_rate'], 'amount' => $entFree ? 0.0 : round($adults * $ent['adult_rate'], 2), 'cur' => $ent['currency']];
        }
        $groups = ['free' => [], 'child' => [], 'adult' => []];
        foreach ($kids as $k) {
            $a = $k['age'];
            if ($a !== null && $rules['age_from'] !== null && $a < $rules['age_from']) {
                $groups['free'][] = $a;
            } elseif ($a !== null && $rules['age_to'] !== null && $a > $rules['age_to']) {
                $groups['adult'][] = $a;
            } else {
                $groups['child'][] = $a;
            }
        }
        $ages = static fn (array $l): string => implode(', ', array_map(static fn ($a) => $a === null ? 'age not given' : $n((float) $a) . ' yrs', $l));
        if ($groups['child']) {
            if (!$entFree && $ent['child_rate'] === null) {
                throw new \InvalidArgumentException('The master holds no child entrance rate for ' . self::label($p) . ': enter it first.');
            }
            $cr = $entFree ? 0.0 : (float) $ent['child_rate'];
            $entLines[] = ['what' => 'Entrance fee — children (' . $ages($groups['child']) . ')', 'qty' => count($groups['child']), 'rate' => $cr, 'amount' => round(count($groups['child']) * $cr, 2), 'cur' => $ent['currency']];
        }
        if ($groups['adult']) {
            $entLines[] = ['what' => 'Entrance fee — children over ' . $n((float) $rules['age_to']) . ' at the adult rate (' . $ages($groups['adult']) . ')', 'qty' => count($groups['adult']), 'rate' => $entFree ? 0.0 : $ent['adult_rate'], 'amount' => $entFree ? 0.0 : round(count($groups['adult']) * $ent['adult_rate'], 2), 'cur' => $ent['currency']];
        }
        if ($groups['free']) {
            $entLines[] = ['what' => 'Entrance fee — children under ' . $n((float) $rules['age_from']) . ', no charge (' . $ages($groups['free']) . ')', 'qty' => count($groups['free']), 'rate' => 0.0, 'amount' => 0.0, 'cur' => $ent['currency']];
        }
        $jeepFree = (int) $jr['free_entry'] === 1;
        $jeepLines = [['what' => 'Jeep fee' . ($qty === null && $rules['seats'] ? ' (' . $pax . ' passengers, ' . (int) $rules['seats'] . ' a jeep)' : ''), 'qty' => $jeeps, 'rate' => $jeepFree ? 0.0 : $jr['adult_rate'], 'amount' => $jeepFree ? 0.0 : round($jeeps * $jr['adult_rate'], 2), 'cur' => $jr['currency']]];

        // ---- the payees
        $parties = $this->partiesByPlace()[(int) $p['id']] ?? [];
        $together = (int) ($p['pay_together'] ?? 0) === 1;
        $payees = [];
        if ($together) {
            $payees[] = ['role' => 'jeep_operator', 'lines' => array_merge($jeepLines, $entLines)];
        } else {
            $payees[] = ['role' => 'jeep_operator', 'lines' => $jeepLines];
            $payees[] = ['role' => 'ticket_supplier', 'lines' => $entLines];
        }

        // ---- the tour / file number and the guest
        $tourNo = null;
        if (!empty($it['tour_id'])) {
            try {
                $c = $this->db->query('SELECT manual_id, tour_id FROM consignee WHERE ID = ?', [(int) $it['tour_id']])->getRowArray();
                $tourNo = $c ? trim((string) ($c['manual_id'] ?: $c['tour_id'])) : null;
            } catch (\Throwable $e) {
                $tourNo = null;
            }
        }
        $fileNo = (string) $it['inquiry_no'];
        $pax1 = $this->db->query('SELECT * FROM tf_intake_pax WHERE intake_id = ? ORDER BY sort_order, id LIMIT 1', [$intakeId])->getRowArray();
        $guest = $pax1 ? trim((string) ($pax1['name'] ?? $pax1['full_name'] ?? '')) : '';
        $guest = $guest !== '' ? $guest : trim((string) ($it['tour_name'] ?? ''));
        $refBase = 'AVH-SAF-' . preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($p['code'] ?: $p['id']))) . '-' . preg_replace('/[^A-Z0-9]/', '', strtoupper($tourNo ?: $fileNo));

        $out = [];
        foreach ($payees as $i => $py) {
            $pt = $parties[$py['role']] ?? null;
            $total = round(array_sum(array_map(static fn ($l) => $l['amount'], $py['lines'])), 2);
            $curs = array_values(array_unique(array_map(static fn ($l) => $l['cur'], $py['lines'])));
            $payee = [
                'role' => $py['role'], 'role_label' => self::ROLES[$py['role']],
                'name' => $pt ? ($pt['own']['business_name'] ?: ($pt['supplier']['name'] ?? null)) : null,
                'supplier_acc' => $pt['supplier']['acc_no'] ?? null,
                'contact' => $pt ? ($pt['own']['contact_person'] ?: ($pt['supplier']['contact'] ?? null)) : null,
                'phone' => $pt ? ($pt['own']['phone'] ?: ($pt['supplier']['phone'] ?? null)) : null,
                'whatsapp' => $pt['own']['whatsapp'] ?? null,
                'email' => $pt ? ($pt['own']['email'] ?: ($pt['supplier']['email'] ?? null)) : null,
                'account_holder' => $pt ? ($pt['own']['account_holder'] ?: ($pt['supplier']['account_holder'] ?? null)) : null,
                'account_masked' => $pt ? ($pt['own']['account_masked'] ?: ($pt['supplier']['account_masked'] ?? null)) : null,
                'bank' => $pt ? ($pt['own']['bank'] ?: ($pt['supplier']['bank'] ?? null)) : null,
                'branch' => $pt ? ($pt['own']['branch'] ?: ($pt['supplier']['branch'] ?? null)) : null,
                'payment_terms' => $pt ? ($pt['own']['payment_terms'] ?: ($pt['supplier']['payment_terms'] ?? null)) : null,
            ];
            if ($payee['name'] === null) {
                throw new \InvalidArgumentException('The ' . strtolower(self::ROLES[$py['role']]) . ' of ' . self::label($p) . ' is not set in the Place to Visit master.');
            }
            // the entrance part's own ledger account when it is paid with the jeep (the entrance supplier record)
            if ($together && isset($parties['ticket_supplier']['supplier']['acc_no'])) {
                foreach ($py['lines'] as &$l) {
                    if (str_starts_with($l['what'], 'Entrance')) {
                        $l['ledger'] = $parties['ticket_supplier']['supplier']['acc_no'];
                    } elseif (isset($pt['supplier']['acc_no'])) {
                        $l['ledger'] = $pt['supplier']['acc_no'];
                    }
                }
                unset($l);
            }
            $figures = json_encode([$py['lines'], $total, $payee['name'], $date, $adults, count($kids), $jeeps], JSON_UNESCAPED_UNICODE);
            $was = $this->db->query('SELECT * FROM tf_safari_slip WHERE service_id = ? AND payee_role = ?', [$serviceId, $py['role']])->getRowArray();
            $now = $this->now();
            if (!$was) {
                $seq = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_safari_slip WHERE ref_no LIKE ?', [$refBase . '-%'])->getRowArray()['n'] ?? 0) + 1;
                $ref = $refBase . '-' . str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
                $this->db->query('INSERT INTO tf_safari_slip (ref_no, intake_id, service_id, place_id, payee_role, supplier_id, payee_name, tour_no, file_no, safari_date, total, currency, figures_json, revision, created_at, created_by, updated_at, updated_by)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1,?,?,?,?)',
                    [$ref, $intakeId, $serviceId, (int) $p['id'], $py['role'], $pt['supplier_id'] ?? null, $payee['name'], $tourNo, $fileNo, $date, $total, implode('/', $curs), $figures, $now, $this->user, $now, $this->user]);
                $rev = 1;
                $this->log((int) $p['id'], 'Safari payment slip ' . $ref . ' made for ' . $fileNo . ($tourNo ? ' / ' . $tourNo : '') . ' — ' . $payee['name'] . ', ' . implode('/', $curs) . ' ' . number_format($total, 2));
            } else {
                $ref = (string) $was['ref_no'];
                $rev = (int) $was['revision'];
                if ((string) $was['figures_json'] !== $figures || (string) ($was['tour_no'] ?? '') !== (string) ($tourNo ?? '')) {
                    $rev++;
                    $this->db->query('UPDATE tf_safari_slip SET supplier_id = ?, payee_name = ?, tour_no = ?, file_no = ?, safari_date = ?, total = ?, currency = ?, figures_json = ?, revision = ?, updated_at = ?, updated_by = ? WHERE id = ?',
                        [$pt['supplier_id'] ?? null, $payee['name'], $tourNo, $fileNo, $date, $total, implode('/', $curs), $figures, $rev, $now, $this->user, (int) $was['id']]);
                    $this->log((int) $p['id'], 'Safari payment slip ' . $ref . ' revised (revision ' . $rev . ') — ' . implode('/', $curs) . ' ' . number_format((float) $was['total'], 2) . ' → ' . number_format($total, 2));
                }
            }
            $out[] = ['ref' => $ref, 'revision' => $rev, 'payee' => $payee, 'lines' => $py['lines'], 'total' => $total, 'currency' => implode('/', $curs), 'together' => $together];
        }
        return [
            'place' => ['name' => $p['name'], 'code' => $p['code'], 'seats' => $rules['seats'], 'age_rule' => self::ageRuleText($rules['age_from'], $rules['age_to'])],
            'intake' => ['file_no' => $fileNo, 'tour_no' => $tourNo, 'guest' => $guest, 'agent' => $it['agent_name'] ?? null, 'adults' => $adults, 'children' => count($kids), 'pax' => $pax],
            'safari_date' => $date, 'jeeps' => $jeeps, 'together' => $together, 'slips' => $out,
        ];
    }
}
