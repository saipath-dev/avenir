<?php

namespace App\Libraries\TourFlow;

/**
 * tf-stage3 (29 Oct 2026) — the Master panel's one search across every master, and its "Needs attention" list.
 * Read-only: every hit links to the EXISTING page that holds the record (by its own address and record id). A result is
 * offered only when the sign-in holds that page, so the search never reveals a master a person cannot open.
 */
final class MasterSearch
{
    /** Which existing page shows each kind of record, and the address that opens the record there. */
    private const PAGE = [
        'hotel'    => ['url' => 'master_files/hotel_master',    'open' => 'master_files/hotel_master?hotel=%d',   'label' => 'Hotel'],
        'rates'    => ['url' => 'master_files/hotel_rate_v2',   'open' => 'master_files/hotel_rate_v2?hotel=%s',  'label' => 'Hotel rates'],
        'supplier' => ['url' => 'master_files/supplier/all',    'open' => 'master_files/supplier/all?q=%s',       'label' => 'Supplier'],
        'customer' => ['url' => 'master_files/customer',        'open' => 'master_files/customer/agents?acc=%s',           'label' => 'Customer / agent'],
        'crew'     => ['url' => 'master_files/crew_people',     'open' => 'master_files/crew_people?person=%d',   'label' => 'Driver / guide'],
        'place'    => ['url' => 'master_files/entrance_master', 'open' => 'master_files/entrance_master?place=%d','label' => 'Place / safari'],
        'meal'     => ['url' => 'master_files/meal_master',     'open' => 'master_files/meal_master?meal=%d',     'label' => 'Meal'],
        'activity' => ['url' => 'master_files/paid_services',   'open' => 'master_files/paid_services?q=%s',      'label' => 'Paid activity'],
        'vehicle'  => ['url' => 'master_files/vehicle',         'open' => 'master_files/vehicle?q=%s',            'label' => 'Vehicle'],
        'vsize'    => ['url' => 'master_files/transport_rates', 'open' => 'master_files/transport_rates?class=%s', 'label' => 'Vehicle size & rate'],   // tf-stage7
        'account'  => ['url' => 'master_files/supplier_accounts','open' => 'master_files/supplier_accounts?q=%s', 'label' => 'Bank account (registry)'],
    ];

    public static function pages(): array
    {
        return array_column(self::PAGE, 'url');
    }

    /**
     * @param array<string,bool> $held  the page URLs the sign-in holds
     */
    /** tf-stage7: which result kinds belong to which door (the door pages search within themselves). */
    public const DOORS = [
        'customers'  => ['customer'],
        'hotels'     => ['hotel', 'rates'],
        'transport'  => ['crew', 'vehicle', 'vsize'],
        'activities' => ['place', 'meal', 'activity', 'supplier'],
        'company'    => ['account'],
    ];
    public const DOOR_TITLES = ['customers' => 'Customers & Agents', 'hotels' => 'Hotels', 'transport' => 'Transport & Crew', 'activities' => 'Activities & Suppliers', 'company' => 'Company Settings'];

    public static function search($db, string $q, array $held, int $limit = 8, ?string $door = null): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $like = '%' . $q . '%';
        $qs = static fn (string $s): string => rawurlencode($s);
        $out = [];
        // tf-stage7: a number of seats or passengers ("7 seats", "12 pax", "seats 10") finds what carries that many
        $seats = preg_match('/(?:^|\s)(\d{1,2})\s*(?:seats?|seater|pax|passengers?|people)?(?:\s|$)/i', $q, $mm) && (int) $mm[1] > 0 ? (int) $mm[1] : null;
        $only = $door !== null && isset(self::DOORS[$door]) ? self::DOORS[$door] : null;
        $add = static function (string $kind, string $title, string $sub, $openArg, ?string $openUrl = null) use (&$out, $held, $qs, $only) {
            $pg = self::PAGE[$kind];
            if (!isset($held[$pg['url']]) || ($only !== null && !in_array($kind, $only, true))) {
                return;
            }
            $open = $openUrl ?? sprintf($pg['open'], is_int($openArg) ? $openArg : $qs((string) $openArg));
            $out[] = ['kind' => $kind, 'kind_label' => $pg['label'], 'title' => $title, 'sub' => $sub, 'url' => $open];
        };
        $try = static function (callable $f): void {
            try {
                $f();
            } catch (\Throwable $e) {
                // a master not installed on this site: nothing from it
            }
        };
        $try(function () use ($db, $like, $limit, $add) {
            foreach ($db->query("SELECT id, name, hotel_code, primary_sup_acc_no, status FROM hr2_hotel WHERE merged_into_id IS NULL AND (name LIKE ? OR hotel_code LIKE ? OR primary_sup_acc_no LIKE ?) ORDER BY name LIMIT {$limit}", [$like, $like, $like])->getResultArray() as $r) {
                $add('hotel', $r['name'], trim(($r['hotel_code'] ? $r['hotel_code'] . ' · ' : '') . ($r['primary_sup_acc_no'] ?: '') . ($r['status'] !== 'active' ? ' · ' . $r['status'] : '')), (int) $r['id']);
                if ($r['primary_sup_acc_no']) {
                    $add('rates', $r['name'] . ' — rates', 'contracts, room types, child bands', (string) $r['primary_sup_acc_no']);
                }
            }
        });
        $try(function () use ($db, $like, $limit, $add) {
            foreach ($db->query("SELECT ID, Sup_Acc_No, Name, sup_cat, is_avalible FROM supplier WHERE Name LIKE ? OR Sup_Acc_No LIKE ? ORDER BY Name LIMIT {$limit}", [$like, $like])->getResultArray() as $r) {
                $cat = ['1' => 'hotel', '2' => 'driver / transport', '3' => 'utility', '4' => 'activity / restaurant', '5' => 'other', '6' => 'other'][(string) $r['sup_cat']] ?? 'supplier';
                $add('supplier', $r['Name'], $r['Sup_Acc_No'] . ' · ' . $cat . ((int) $r['is_avalible'] !== 1 ? ' · not available' : ''), (string) $r['Sup_Acc_No']);
                $add('account', $r['Name'] . ' — bank accounts', 'registered accounts and verification', (string) $r['Sup_Acc_No']);
            }
        });
        $try(function () use ($db, $like, $limit, $add) {
            foreach ($db->query("SELECT ID, Sup_Acc_No, Name, country, agent FROM customers WHERE Name LIKE ? OR Sup_Acc_No LIKE ? OR long_name LIKE ? ORDER BY Name LIMIT {$limit}", [$like, $like, $like])->getResultArray() as $r) {
                $add('customer', $r['Name'], trim($r['Sup_Acc_No'] . ($r['country'] ? ' · ' . $r['country'] : '') . ((int) $r['agent'] === 1 ? ' · agent' : '')), (string) $r['Sup_Acc_No']);
            }
        });
        $try(function () use ($db, $q, $limit, $add) {
            // tf-stage7: every word has to be found somewhere on the person ("japanese guide", "english driver", a name and a language)
            $words = array_values(array_filter(preg_split('/\s+/', trim($q))));
            $cond = implode(' AND ', array_fill(0, count($words), "CONCAT_WS(' ', name, nickname, kind, category, languages, role_codes, supplier_acc, vehicle_reg) LIKE ?"));
            foreach ($db->query("SELECT id, name, nickname, kind, languages, supplier_acc, status FROM tf_crew_person WHERE archived_at IS NULL AND ({$cond}) ORDER BY name LIMIT {$limit}", array_map(static fn ($w) => '%' . $w . '%', $words))->getResultArray() as $r) {
                $add('crew', $r['name'], trim(($r['kind'] ?: '') . ($r['languages'] ? ' · ' . $r['languages'] : '') . ($r['supplier_acc'] ? ' · ' . $r['supplier_acc'] : '') . ($r['status'] !== 'active' ? ' · ' . $r['status'] : '')), (int) $r['id']);
            }
        });
        $try(function () use ($db, $like, $limit, $add) {
            foreach ($db->query("SELECT p.id, p.code, p.name, p.destination, p.jeep, p.active, p.archived_at FROM tf_place p WHERE p.merged_into IS NULL AND (p.name LIKE ? OR p.code LIKE ? OR p.destination LIKE ? OR p.id IN (SELECT place_id FROM tf_place_alias WHERE active = 1 AND alias LIKE ?) OR p.id IN (SELECT place_id FROM tf_place_party WHERE business_name LIKE ?)) ORDER BY p.name LIMIT {$limit}", [$like, $like, $like, $like, $like])->getResultArray() as $r) {
                $add('place', $r['name'], trim(($r['code'] ? $r['code'] . ' · ' : '') . ((int) $r['jeep'] === 1 ? 'safari park' : 'entrance') . ($r['destination'] ? ' · ' . $r['destination'] : '') . ($r['archived_at'] ? ' · archived' : ((int) $r['active'] !== 1 ? ' · not in use' : ''))), (int) $r['id']);
            }
        });
        $try(function () use ($db, $like, $limit, $add) {
            foreach ($db->query("SELECT id, code, meal_type, restaurant, location, active FROM tf_meal WHERE code LIKE ? OR restaurant LIKE ? OR location LIKE ? OR cuisine LIKE ? ORDER BY restaurant LIMIT {$limit}", [$like, $like, $like, $like])->getResultArray() as $r) {
                $add('meal', ucfirst((string) $r['meal_type']) . ' at ' . $r['restaurant'], $r['code'] . ' · ' . $r['location'] . ((int) $r['active'] !== 1 ? ' · switched off' : ''), (int) $r['id']);
            }
        });
        $try(function () use ($db, $like, $limit, $add) {
            foreach ($db->query("SELECT code, name, location, unit, rate, currency, active FROM tf_excursion_rate WHERE (code LIKE ? OR name LIKE ? OR location LIKE ?) ORDER BY active DESC, name LIMIT {$limit}", [$like, $like, $like])->getResultArray() as $r) {
                $add('activity', $r['name'], $r['code'] . ' · ' . ($r['location'] ?: '—') . ' · ' . $r['currency'] . ' ' . number_format((float) $r['rate'], 2) . ' ' . str_replace('_', ' ', (string) $r['unit']) . ((int) $r['active'] !== 1 ? ' · switched off' : ''), (string) $r['code']);
            }
        });
        $try(function () use ($db, $like, $limit, $add, $seats) {
            $sql = "SELECT id, type, v_num, owner, passenger_count FROM vehicle WHERE type LIKE ? OR v_num LIKE ? OR owner LIKE ?" . ($seats !== null ? " OR passenger_count >= ?" : '') . " ORDER BY passenger_count, v_num LIMIT {$limit}";
            foreach ($db->query($sql, $seats !== null ? [$like, $like, $like, $seats] : [$like, $like, $like])->getResultArray() as $r) {
                $add('vehicle', trim($r['v_num'] . ' · ' . $r['type']), trim(($r['owner'] ? $r['owner'] . ' · ' : '') . ($r['passenger_count'] ? $r['passenger_count'] . ' passengers' : '')), (string) $r['v_num']);
            }
        });
        // tf-stage7: the vehicle sizes the company prices (Transport Rates), by name or by the seats / passengers they carry
        $try(function () use ($db, $like, $limit, $add, $seats) {
            $sql = "SELECT c.code, c.name, c.max_pax, c.seats, (SELECT r.rate_km FROM tf_transport_rate r WHERE r.class_code = c.code AND r.valid_from <= CURDATE() AND (r.valid_to IS NULL OR r.valid_to >= CURDATE()) ORDER BY r.valid_from DESC LIMIT 1) AS rate_km
                    FROM tf_vehicle_class c WHERE c.active = 1 AND (c.name LIKE ? OR c.code LIKE ?" . ($seats !== null ? " OR c.max_pax >= ? OR c.seats >= ?" : '') . ") ORDER BY c.max_pax LIMIT {$limit}";
            foreach ($db->query($sql, $seats !== null ? [$like, $like, $seats, $seats] : [$like, $like])->getResultArray() as $r) {
                $add('vsize', $r['name'], trim('carries ' . (int) $r['max_pax'] . ' passengers' . ($r['seats'] ? ' (' . (int) $r['seats'] . ' seats)' : '') . ($r['rate_km'] !== null ? ' · LKR ' . number_format((float) $r['rate_km'], 2) . ' per km' : ' · no rate today')), (string) $r['code']);
            }
        });
        return $out;
    }

    /** The "Needs attention" list: real conditions a person should act on, each linking to the page that fixes it. */
    public static function attention($db, array $held, string $usdUrl = 'home', ?string $door = null): array
    {
        $out = [];
        $today = date('Y-m-d');
        $doorOnly = $door !== null && isset(self::DOOR_TITLES[$door]) ? self::DOOR_TITLES[$door] : null;
        $add = static function (string $level, string $what, string $why, string $url, string $door) use (&$out, $held, $doorOnly) {
            $u = strtok($url, '?');
            // tf-stage6: a page reached under another page's privilege (master_files/customer/agents under master_files/customer) counts as held
            if ($u !== 'home' && !isset($held[$u]) && !isset($held[str_replace('\\', '/', dirname($u))])) {
                return;
            }
            if ($doorOnly !== null && $door !== $doorOnly) {   // tf-stage7: a door's page lists its own items
                return;
            }
            $out[] = ['level' => $level, 'what' => $what, 'why' => $why, 'url' => $url, 'door' => $door];
        };
        $try = static function (callable $f): void {
            try {
                $f();
            } catch (\Throwable $e) {
            }
        };
        // the USD rate
        $try(function () use ($db, $today, $add, $usdUrl) {
            $t = $db->query('SELECT rate FROM daily_usd_rate WHERE rate_date = ?', [$today])->getRowArray();
            if (!$t) {
                $last = $db->query('SELECT rate, rate_date FROM daily_usd_rate ORDER BY rate_date DESC LIMIT 1')->getRowArray();
                $age = $last ? (int) floor((strtotime($today) - strtotime($last['rate_date'])) / 86400) : null;
                $add('red', "Today's USD rate is not entered", $last ? 'The last rate is ' . number_format((float) $last['rate'], 2) . ' from ' . date('j M Y', strtotime($last['rate_date'])) . ' (' . $age . ' days ago); hotel payment requests wait' : 'No rate has ever been entered', $usdUrl, 'Company Settings');
            } else {
                // tf-stage4d: a TEST figure stands in for the real rate until a real correction replaces it
                $lastLog = $db->query('SELECT reason FROM tf_usd_rate_log WHERE rate_date = ? ORDER BY id DESC LIMIT 1', [$today])->getRowArray();
                if ($lastLog && stripos((string) ($lastLog['reason'] ?? ''), 'TEST') !== false) {
                    $add('red', "Today's USD rate " . number_format((float) $t['rate'], 2) . ' is a TEST figure', 'Enter the real rate of the day as a correction (with the reason); costings using the TEST figure will then show it for review', $usdUrl, 'Company Settings');
                }
            }
        });
        // hotel contracts ending within 60 days, and hotels used on open inquiries with no approved contract covering the stay
        $try(function () use ($db, $today, $add) {
            $soon = date('Y-m-d', strtotime('+60 days'));
            $rows = $db->query("SELECT c.id, c.valid_to, h.name, h.id AS hid FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'approved' AND c.valid_to BETWEEN ? AND ? AND NOT EXISTS (SELECT 1 FROM hr2_contract c2 WHERE c2.hotel_id = c.hotel_id AND c2.status = 'approved' AND c2.valid_from > c.valid_to) ORDER BY c.valid_to LIMIT 6", [$today, $soon])->getResultArray();
            foreach ($rows as $r) {
                $add('amber', $r['name'] . ': contract ends ' . date('j M Y', strtotime($r['valid_to'])), 'No approved contract follows it; stays after that date will be held', 'master_files/hotel_rate_v2?hotel=' . rawurlencode((string) ($db->query('SELECT primary_sup_acc_no FROM hr2_hotel WHERE id = ?', [(int) $r['hid']])->getRowArray()['primary_sup_acc_no'] ?? '')), 'Hotels');
            }
            $n = (int) ($db->query("SELECT COUNT(*) n FROM hr2_contract WHERE status = 'draft'")->getRowArray()['n'] ?? 0);
            if ($n > 0) {
                $add('amber', $n . ' hotel rate plan' . ($n === 1 ? '' : 's') . ' waiting for approval', 'Drafts are not used for costing until approved', 'master_files/hotel_rate_approval', 'Hotels');
            }
        });
        // places and safari parks with no rate for today
        $try(function () use ($db, $today, $add) {
            $rows = $db->query("SELECT p.id, p.name, p.jeep FROM tf_place p WHERE p.active = 1 AND p.merged_into IS NULL AND p.archived_at IS NULL AND NOT EXISTS (SELECT 1 FROM tf_place_rate r WHERE r.place_id = p.id AND r.active = 1 AND r.charge = 'entrance' AND (r.valid_from IS NULL OR r.valid_from <= ?) AND (r.valid_to IS NULL OR r.valid_to >= ?)) ORDER BY p.jeep DESC, p.name", [$today, $today])->getResultArray();
            $parks = array_values(array_filter($rows, static fn ($r) => (int) $r['jeep'] === 1));
            $others = count($rows) - count($parks);
            foreach (array_slice($parks, 0, 4) as $r) {
                $add('amber', $r['name'] . ': no entrance or jeep rate', 'A safari there is held until the rates are entered', 'master_files/entrance_master?place=' . (int) $r['id'], 'Activities & Suppliers');
            }
            if ($others > 0) {
                $add('amber', $others . ' place' . ($others === 1 ? '' : 's') . ' with no entrance rate for today', 'An entrance there is held, never guessed', 'master_files/entrance_master', 'Activities & Suppliers');
            }
        });
        // bank accounts: incomplete or unverified, counted
        $try(function () use ($db, $add) {
            $r = $db->query("SELECT COUNT(*) n, SUM(status <> 'verified') unverified, SUM(holder_name IS NULL OR holder_name = '' OR bank_name IS NULL OR bank_name = '' OR account_no IS NULL OR account_no = '') incomplete FROM tpd_supplier_account WHERE active = 1")->getRowArray();
            if ($r && (int) $r['incomplete'] > 0) {
                $add('amber', (int) $r['incomplete'] . ' registered bank account' . ((int) $r['incomplete'] === 1 ? '' : 's') . ' incomplete', 'Holder, bank or account number missing; a transfer cannot be initiated to them', 'master_files/supplier_accounts?f=incomplete', 'Company Settings');
            }
            if ($r && (int) $r['unverified'] > 0) {
                $add('amber', (int) $r['unverified'] . ' of ' . (int) $r['n'] . ' bank accounts not verified', 'Payment release waits for an independent verification', 'master_files/supplier_accounts?f=unverified', 'Company Settings');
            }
        });
        // meals and paid activities ending within 30 days
        $try(function () use ($db, $today, $add) {
            $soon = date('Y-m-d', strtotime('+30 days'));
            $n = (int) ($db->query("SELECT COUNT(*) n FROM tf_meal WHERE active = 1 AND valid_to BETWEEN ? AND ?", [$today, $soon])->getRowArray()['n'] ?? 0);
            if ($n > 0) {
                $add('amber', $n . ' meal rate' . ($n === 1 ? '' : 's') . ' ending within 30 days', 'Lunches after the end date are held', 'master_files/meal_master', 'Activities & Suppliers');
            }
            $n = (int) ($db->query("SELECT COUNT(*) n FROM tf_excursion_rate WHERE active = 1 AND valid_to BETWEEN ? AND ?", [$today, $soon])->getRowArray()['n'] ?? 0);
            if ($n > 0) {
                $add('amber', $n . ' paid activit' . ($n === 1 ? 'y' : 'ies') . ' ending within 30 days', 'Lines after the end date are held', 'master_files/paid_services', 'Activities & Suppliers');
            }
        });
        // drivers and guides with a licence expiring within 30 days
        $try(function () use ($db, $today, $add) {
            $soon = date('Y-m-d', strtotime('+30 days'));
            $rows = $db->query("SELECT id, name, licence_expiry, guide_licence_expiry FROM tf_crew_person WHERE archived_at IS NULL AND status = 'active' AND ((licence_expiry BETWEEN ? AND ?) OR (guide_licence_expiry BETWEEN ? AND ?)) ORDER BY COALESCE(licence_expiry, guide_licence_expiry) LIMIT 4", [$today, $soon, $today, $soon])->getResultArray();
            foreach ($rows as $r) {
                $d = $r['licence_expiry'] && $r['licence_expiry'] >= $today && $r['licence_expiry'] <= $soon ? $r['licence_expiry'] : $r['guide_licence_expiry'];
                $add('amber', $r['name'] . ': licence expires ' . date('j M Y', strtotime($d)), 'Renew before the next allocation', 'master_files/crew_people?person=' . (int) $r['id'], 'Transport & Crew');
            }
        });
        // customers: none marked as agents yet
        $try(function () use ($db, $add) {
            $r = $db->query("SELECT COUNT(*) n, SUM(agent = 1) agents FROM customers")->getRowArray();
            if ($r && (int) $r['n'] > 0 && (int) $r['agents'] === 0) {
                $add('amber', 'No customer is marked as an agent yet', 'The inquiry cannot tell an agent from a direct customer', 'master_files/customer/agents', 'Customers & Agents');
            } else {
                // tf-stage6: agents without a quotation currency, and open inquiries whose agent is not on the ledger
                $nc = (int) ($db->query("SELECT COUNT(*) n FROM customers WHERE agent = 1 AND (quote_currency IS NULL OR quote_currency = '')")->getRowArray()['n'] ?? 0);
                if ($nc) {
                    $add('amber', $nc . ' agent' . ($nc === 1 ? '' : 's') . ' without a quotation currency', 'Quotations use the company setting until it is set on the agent', 'master_files/customer/agents?f=agents', 'Customers & Agents');
                }
                $um = (int) ($db->query("SELECT COUNT(*) n FROM tf_intake WHERE archived_at IS NULL AND status IN ('new','working','quoted') AND (agent_acc IS NULL OR agent_acc = '') AND agent_name IS NOT NULL AND agent_name <> ''")->getRowArray()['n'] ?? 0);
                if ($um) {
                    $add('amber', $um . ' open inquir' . ($um === 1 ? 'y names' : 'ies name') . ' an agent not matched to a customer', 'Pick the agent from the ledger on the inquiry, or add the customer first', 'tour/intake', 'Customers & Agents');
                }
            }
        });
        // tf-stage8: active blocks and the existing work they touch
        $try(function () use ($db, $add) {
            foreach (Blocks::attention($db) as $b) {
                $add($b['level'], $b['what'], $b['why'], 'master_files/blocks', 'Blocks & Investigations');
            }
        });
        usort($out, static fn ($a, $b) => ($a['level'] === 'red' ? 0 : 1) <=> ($b['level'] === 'red' ? 0 : 1));
        return $out;
    }
}
