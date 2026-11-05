<?php

namespace App\Libraries\HotelRates;

/**
 * Hotel Incomplete Work — the permanent queue of everything the system found
 * missing or doubtful and could not decide by itself. Nothing here guesses:
 * a detector states what is missing, where, and what would fix it; a person
 * (or Claude, from the export) fills the gap; the next scan closes the item.
 *
 *   no_rate           a confirmed tour night is paid at the quoted rate because no plan covers it
 *   hotel_missing     a hotel used on tours is not in the Hotel Rates panel at all
 *   quote_gap         upcoming quoted (unconfirmed) nights of a hotel that no approved plan covers
 *   quote_no_rate     the timeline asked for a rate and the panel had none (event, re-checked by the scan)
 *   draft_pending     a plan waiting in the approval queue
 *   missing_supplier  a hotel on the master without any supplier account (6-9-11-…)
 *   sheet_no_supplier a transcribed rate sheet (writable/hotel_rates_entry/*.json) whose hotel has no supplier record
 *   unusual_margin    payable above the quoted amount, or a margin above Settings › margin_alert_pct
 *   unmapped_country  a quotation country with no market mapping (default market used)
 *   merge_check       a duplicate merge to confirm or undo (registered by hand)
 *   sheet_query       something a rate sheet left open (registered by hand)
 *   manual            anything else a person adds
 *
 * Detectors upsert by item_key. Items the same detector no longer finds are
 * closed by the system ("No longer found"); a closed item that comes back is
 * reopened; ignored items stay ignored. Hand-registered kinds are never
 * closed automatically.
 */
final class WorkQueueService
{
    public const KINDS = [
        'no_rate'           => ['label' => 'No contract rate (confirmed tour)', 'level' => 'red',   'auto' => true],
        'hotel_missing'     => ['label' => 'Hotel not in the panel',             'level' => 'red',   'auto' => true],
        'quote_gap'         => ['label' => 'Quoted nights without a plan',       'level' => 'amber', 'auto' => true],
        'quote_no_rate'     => ['label' => 'No rate while quoting',              'level' => 'amber', 'auto' => true],
        'draft_pending'     => ['label' => 'Draft waiting approval',             'level' => 'amber', 'auto' => true],
        'missing_supplier'  => ['label' => 'No supplier account',               'level' => 'red',   'auto' => true],
        'sheet_no_supplier' => ['label' => 'Rate sheet without a supplier account', 'level' => 'red', 'auto' => true],
        'unusual_margin'    => ['label' => 'Unusual margin',                     'level' => 'amber', 'auto' => true],
        'unmapped_country'  => ['label' => 'Country not mapped to a market',     'level' => 'amber', 'auto' => true],
        'merge_check'       => ['label' => 'Merge to confirm',                   'level' => 'amber', 'auto' => false],
        'sheet_query'       => ['label' => 'Rate sheet query',                   'level' => 'amber', 'auto' => false],
        'manual'            => ['label' => 'Other',                              'level' => 'blue',  'auto' => false],
    ];

    public const STATUSES = ['open', 'done', 'ignored'];

    /** data_json sub-keys a detector rescan must never wipe — see upsert(). */
    public const STICKY_DATA_KEYS = ['supplier_details'];

    /** kinds that can carry a staged supplier_details sub-record (see saveSupplierDetails()). */
    public const SUPPLIER_DETAIL_KINDS = ['sheet_no_supplier', 'missing_supplier'];

    private $db;
    private string $user;
    private HotelRateService $svc;
    private ?string $entryDir;
    private ?bool $codesReady = null;   // hr2_hotel.hotel_code exists (schema 8) — the hotel code HTL-### rides along on every item

    public function __construct($db, string $user = 'system', ?HotelRateService $svc = null, ?string $entryDir = null)
    {
        $this->db = $db;
        $this->user = $user ?: 'system';
        $this->svc = $svc ?? new HotelRateService($db, $this->user);
        $this->entryDir = $entryDir ?? (defined('WRITEPATH') ? WRITEPATH . 'hotel_rates_entry' : null);
    }

    // =====================================================================
    //  Scan
    // =====================================================================

    /** Run the scan only when the last one is older than $minutes (0 = always). */
    public function scanIfDue(int $minutes, array $opts = []): ?array
    {
        if ($minutes > 0) {
            $last = $this->svc->meta('work_scan_at');
            if ($last && strtotime($last) > time() - $minutes * 60) {
                return null;
            }
        }
        return $this->scan($opts);
    }

    /**
     * Run every detector (or the tour-scoped ones when $opts['tour_id'] is given),
     * upsert what they found and close what they no longer find.
     * @return array{new:int, seen:int, reopened:int, closed:int, open:int, kinds:array}
     */
    public function scan(array $opts = []): array
    {
        $tourId = isset($opts['tour_id']) ? (string) $opts['tour_id'] : null;
        $items = [];
        $kinds = [];
        $accs = null;
        if ($tourId !== null) {
            // one tour was re-costed: refresh the rate gaps of ITS hotels (the items are per
            // hotel and month, so every tour at those hotels is taken into account) and its own margins
            $accs = array_column($this->rows("SELECT DISTINCT hotel_acc FROM hr2_tour_hotel_cost WHERE tour_id = ? AND hotel_acc <> ''", [$tourId]), 'hotel_acc');
            $items = array_merge($this->detectNoRate($accs), $this->detectUnusualMargin($tourId));
            $kinds = ['no_rate', 'hotel_missing', 'unusual_margin'];
        } else {
            $items = array_merge(
                $this->detectNoRate(null),
                $this->detectUnusualMargin(null),
                $this->detectQuoteGaps(),
                $this->detectDrafts(),
                $this->detectMissingSuppliers(),
                $this->detectSheetsWithoutSupplier(),
                $this->detectUnmappedCountries(),
                $this->recheckQuoteNoRate()
            );
            $kinds = ['no_rate', 'hotel_missing', 'quote_gap', 'quote_no_rate', 'draft_pending', 'missing_supplier', 'sheet_no_supplier', 'unusual_margin', 'unmapped_country'];
        }
        $now = date('Y-m-d H:i:s');
        $seen = [];
        $stats = ['new' => 0, 'seen' => 0, 'reopened' => 0, 'closed' => 0];
        foreach ($items as $it) {
            $seen[$it['key']] = true;
            $r = $this->upsert($it, 'auto', $now);
            $stats[$r]++;
        }
        // close what this scan no longer finds (only kinds this scan is authoritative for)
        if ($kinds) {
            $sql = "SELECT id, item_key FROM hr2_work_item WHERE source = 'auto' AND status = 'open' AND kind IN ('" . implode("','", $kinds) . "')";
            $binds = [];
            if ($tourId !== null) {
                // only the items this partial scan is authoritative for: the rate gaps of the
                // tour's hotels and the margin items of the tour itself
                $sql .= " AND (tour_id = ?" . ($accs ? " OR (kind IN ('no_rate', 'hotel_missing') AND hotel_acc IN (" . implode(', ', array_fill(0, count($accs), '?')) . '))' : '') . ')';
                $binds = array_merge([$tourId], $accs ?: []);
            }
            foreach ($this->rows($sql, $binds) as $row) {
                if (!isset($seen[$row['item_key']])) {
                    $this->db->query("UPDATE hr2_work_item SET status = 'done', resolved_at = ?, resolved_by = 'system', updated_at = ?, note = CONCAT(COALESCE(note, ''), ?) WHERE id = ?", [$now, $now, ($this->noteSep($row['id'])) . 'No longer found on ' . substr($now, 0, 16) . ' — closed by the system.', (int) $row['id']]);
                    $stats['closed']++;
                }
            }
        }
        if ($tourId === null) {
            $this->svc->setMeta('work_scan_at', $now);
        }
        $stats['open'] = $this->openCount();
        $stats['kinds'] = $this->counts();
        return $stats;
    }

    /** Insert or refresh one detected item; returns new | seen | reopened. */
    private function upsert(array $it, string $source, string $now): string
    {
        $ex = $this->row('SELECT id, status, note, data_json FROM hr2_work_item WHERE item_key = ?', [$it['key']]);
        // A person may have saved supplier_details (or other sticky sub-fields — see STICKY_DATA_KEYS)
        // on this item between scans. Detectors rebuild `data` from scratch each time, so without this
        // the next scan would silently wipe that out; carry it forward unless the detector itself set it.
        if ($ex) {
            $exData = json_decode((string) ($ex['data_json'] ?? ''), true);
            if (is_array($exData)) {
                foreach (self::STICKY_DATA_KEYS as $sk) {
                    if (isset($exData[$sk]) && !isset($it['data'][$sk])) {
                        $it['data'][$sk] = $exData[$sk];
                    }
                }
            }
        }
        $data = isset($it['data']) ? json_encode($it['data'], JSON_UNESCAPED_UNICODE) : null;
        $level = $it['level'] ?? (self::KINDS[$it['kind']]['level'] ?? 'amber');
        if (!$ex) {
            $this->db->query(
                'INSERT INTO hr2_work_item (item_key, kind, level, title, detail, hotel_id, hotel_acc, hotel_name, tour_id, manual_id, plan_id, ref_date, ref_date_to, amount, data_json, source, status, note, seen_count, created_at, created_by, updated_at, last_seen_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'open\', ?, 1, ?, ?, ?, ?)',
                [$it['key'], $it['kind'], $level, mb_substr($it['title'], 0, 255), $it['detail'] ?? null, $it['hotel_id'] ?? null, $it['hotel_acc'] ?? null, isset($it['hotel_name']) ? mb_substr($it['hotel_name'], 0, 190) : null,
                    $it['tour_id'] ?? null, $it['manual_id'] ?? null, $it['plan_id'] ?? null, $it['ref_date'] ?? null, $it['ref_date_to'] ?? null, $it['amount'] ?? null, $data, $source, $it['note'] ?? null, $now, $this->user, $now, $now]
            );
            return 'new';
        }
        $reopen = $ex['status'] === 'done';
        $this->db->query(
            'UPDATE hr2_work_item SET kind = ?, level = ?, title = ?, detail = ?, hotel_id = ?, hotel_acc = ?, hotel_name = ?, tour_id = ?, manual_id = ?, plan_id = ?, ref_date = ?, ref_date_to = ?, amount = ?, data_json = ?, seen_count = seen_count + 1, updated_at = ?, last_seen_at = ?'
            . ($reopen ? ", status = 'open', resolved_at = NULL, resolved_by = NULL, note = CONCAT(COALESCE(note, ''), ?)" : '') . ' WHERE id = ?',
            array_merge(
                [$it['kind'], $level, mb_substr($it['title'], 0, 255), $it['detail'] ?? null, $it['hotel_id'] ?? null, $it['hotel_acc'] ?? null, isset($it['hotel_name']) ? mb_substr($it['hotel_name'], 0, 190) : null,
                    $it['tour_id'] ?? null, $it['manual_id'] ?? null, $it['plan_id'] ?? null, $it['ref_date'] ?? null, $it['ref_date_to'] ?? null, $it['amount'] ?? null, $data, $now, $now],
                $reopen ? [$this->noteSep((int) $ex['id']) . 'Found again on ' . substr($now, 0, 16) . ' — reopened.'] : [],
                [(int) $ex['id']]
            )
        );
        return $reopen ? 'reopened' : 'seen';
    }

    private function noteSep(int $id): string
    {
        $r = $this->row('SELECT note FROM hr2_work_item WHERE id = ?', [$id]);
        return ($r && (string) $r['note'] !== '') ? "\n" : '';
    }

    // =====================================================================
    //  Detectors — each returns a list of items (key, kind, title, detail, …)
    // =====================================================================

    /**
     * Confirmed-tour nights paid at the quoted rate because no plan covers them.
     * One item per hotel and month — not per tour — because the gap is the missing rate
     * sheet, not the tour; the affected tours are listed inside the item, and the Tour
     * Payment Dashboard flags each tour on its own.
     * @param string[]|null $accs limit to these supplier accounts (a tour's hotels), null = all
     */
    public function detectNoRate(?array $accs = null): array
    {
        $sql = "SELECT c.tour_id, c.hotel_acc, c.hotel_id, c.tour_date, c.room_type_name, c.meal_plan, c.quote_total, c.source,
                       cs.manual_id, tq.hotel_name, h.name AS panel_name
                FROM hr2_tour_hotel_cost c
                JOIN consignee cs ON cs.tour_id = c.tour_id AND cs.verify = 1 AND COALESCE(cs.tour_cancel, 0) <> 1
                LEFT JOIN tour_quotations tq ON tq.id = c.quotation_row_id
                LEFT JOIN hr2_hotel h ON h.id = c.hotel_id
                WHERE c.source IN ('quoted', 'none') AND c.tour_date >= CURDATE()";
        $binds = [];
        if ($accs !== null) {
            if (!$accs) {
                return [];
            }
            $sql .= ' AND c.hotel_acc IN (' . implode(', ', array_fill(0, count($accs), '?')) . ')';
            $binds = array_values($accs);
        }
        $groups = [];
        foreach ($this->rows($sql . ' ORDER BY c.hotel_acc, c.tour_date, c.id', $binds) as $r) {
            $k = $r['hotel_acc'] . '|' . substr((string) $r['tour_date'], 0, 7);
            $groups[$k] ??= ['rows' => [], 'first' => $r, 'month' => substr((string) $r['tour_date'], 0, 7)];
            $groups[$k]['rows'][] = $r;
        }
        // the approved quotation plans of each hotel, so the item can say what stops where
        $plans = [];
        foreach ($this->rows("SELECT p.hotel_id, p.name, p.valid_from, p.valid_to FROM hr2_contract p WHERE p.status = 'approved' AND p.applies_to <> 'payment' ORDER BY p.hotel_id, p.valid_from") as $pl) {
            $plans[(int) $pl['hotel_id']][] = $pl;
        }
        $out = [];
        foreach ($groups as $g) {
            $f = $g['first'];
            $nights = $g['rows'];
            $inPanel = !empty($f['hotel_id']);
            $name = trim((string) ($f['panel_name'] ?: $f['hotel_name'] ?: $f['hotel_acc']));
            $hotelPlans = $inPanel ? ($plans[(int) $f['hotel_id']] ?? []) : [];
            $from = $nights[0]['tour_date'];
            $to = $nights[count($nights) - 1]['tour_date'];
            $before = null;
            $after = null;
            foreach ($hotelPlans as $pl) {
                if ($pl['valid_to'] < $from && (!$before || $pl['valid_to'] > $before['valid_to'])) {
                    $before = $pl;
                }
                if ($pl['valid_from'] > $to && (!$after || $pl['valid_from'] < $after['valid_from'])) {
                    $after = $pl;
                }
            }
            $why = !$inPanel ? '' : (!$hotelPlans
                ? ' This hotel has no approved plan at all.'
                : ' Approved plans exist but none covers these nights'
                    . ($before ? ' — the last one before them ("' . $before['name'] . '") ended ' . date('j M Y', strtotime($before['valid_to'])) : '')
                    . ($after ? ($before ? ' and the next one ("' : ' — the next one ("') . $after['name'] . '") starts ' . date('j M Y', strtotime($after['valid_from'])) : '')
                    . '.');
            $quoted = array_sum(array_map(static fn ($n) => (float) $n['quote_total'], $nights));
            $none = count(array_filter($nights, static fn ($n) => $n['source'] === 'none'));
            $tours = [];
            foreach ($nights as $n) {
                $tours[$n['tour_id']] ??= ['tour_id' => $n['tour_id'], 'manual_id' => $n['manual_id'], 'nights' => 0];
                $tours[$n['tour_id']]['nights']++;
            }
            $month = date('F Y', strtotime($g['month'] . '-01'));
            $label = $inPanel ? 'No contract rate' : 'Hotel not in the panel';
            $tourWord = count($tours) === 1 ? 'tour' : 'tours';
            $title = "{$label} — {$name} · {$month}: " . count($nights) . ' night' . (count($nights) === 1 ? '' : 's') . ' on ' . count($tours) . " confirmed {$tourWord} (quoted " . number_format($quoted, 2) . ')';
            $tourList = implode(', ', array_map(static fn ($t) => $t['manual_id'] ?: $t['tour_id'], array_slice(array_values($tours), 0, 12))) . (count($tours) > 12 ? ' … +' . (count($tours) - 12) . ' more' : '');
            $detail = $inPanel
                ? "No approved plan prices these nights (" . $this->dateSpan($from, $to) . '): ' . $this->nightList($nights) . ".{$why} The quoted rate is used for the hotel payment and every tour is flagged \"check the rate before payment\". Load or approve the sheet covering {$month}; the tours re-cost themselves the moment the plan is approved. Tours: {$tourList}."
                : "{$name} ({$f['hotel_acc']}) is not a hotel in the Hotel Rates panel, so these nights (" . $this->dateSpan($from, $to) . ') are paid at the quoted rate: ' . $this->nightList($nights) . ". Create the hotel in the panel (or attach the supplier account to an existing hotel) and load its rate sheet. Tours: {$tourList}.";
            if ($none) {
                $detail .= " {$none} night(s) have no quoted rate either.";
            }
            $out[] = [
                'key' => 'no_rate:' . $f['hotel_acc'] . ':' . $g['month'],
                'kind' => $inPanel ? 'no_rate' : 'hotel_missing',
                'level' => 'red',
                'title' => $title,
                'detail' => $detail,
                'hotel_id' => $inPanel ? (int) $f['hotel_id'] : null,
                'hotel_acc' => $f['hotel_acc'],
                'hotel_name' => $name,
                'tour_id' => count($tours) === 1 ? $f['tour_id'] : null,
                'manual_id' => count($tours) === 1 ? $f['manual_id'] : null,
                'ref_date' => $from,
                'ref_date_to' => $to,
                'amount' => round($quoted, 2),
                'data' => [
                    'nights' => array_map(static fn ($n) => ['date' => $n['tour_date'], 'room_type' => $n['room_type_name'], 'meal_plan' => $n['meal_plan'], 'quoted' => (float) $n['quote_total'], 'source' => $n['source'], 'tour' => $n['manual_id']], array_slice($nights, 0, 40)),
                    'tours' => array_values($tours),
                    'month' => $g['month'],
                    'plan_before' => $before['name'] ?? null,
                    'plan_after' => $after['name'] ?? null,
                ],
            ];
        }
        return $out;
    }

    /** Payable above quoted (any source), or a contract margin above the threshold — per tour + hotel. */
    public function detectUnusualMargin(?string $tourId): array
    {
        $pct = (float) ($this->svc->settings()->get('margin_alert_pct') ?? 40);
        $sql = "SELECT c.tour_id, c.hotel_acc, c.hotel_id, MIN(c.tour_date) AS d_from, MAX(c.tour_date) AS d_to, COUNT(*) AS nights,
                       SUM(c.quote_total) AS quoted, SUM(c.pay_total) AS payable,
                       SUM(CASE WHEN c.source = 'contracted' THEN c.quote_total ELSE 0 END) AS quoted_c,
                       SUM(CASE WHEN c.source = 'contracted' THEN c.pay_total ELSE 0 END) AS payable_c,
                       SUM(c.source = 'adhoc') AS adhoc_nights, MAX(cs.manual_id) AS manual_id, MAX(h.name) AS panel_name, MAX(tq.hotel_name) AS hotel_name, MAX(c.plan_name) AS plan_name
                FROM hr2_tour_hotel_cost c
                JOIN consignee cs ON cs.tour_id = c.tour_id AND cs.verify = 1 AND COALESCE(cs.tour_cancel, 0) <> 1
                LEFT JOIN tour_quotations tq ON tq.id = c.quotation_row_id
                LEFT JOIN hr2_hotel h ON h.id = c.hotel_id
                WHERE c.source IN ('contracted', 'adhoc') AND c.tour_date >= CURDATE()";
        $binds = [];
        if ($tourId !== null) {
            $sql .= ' AND c.tour_id = ?';
            $binds[] = $tourId;
        }
        $sql .= ' GROUP BY c.tour_id, c.hotel_acc, c.hotel_id';
        $out = [];
        foreach ($this->rows($sql, $binds) as $r) {
            $name = trim((string) ($r['panel_name'] ?: $r['hotel_name'] ?: $r['hotel_acc']));
            $quoted = (float) $r['quoted'];
            $payable = (float) $r['payable'];
            $span = $this->dateSpan($r['d_from'], $r['d_to']);
            $base = ['hotel_id' => $r['hotel_id'] ? (int) $r['hotel_id'] : null, 'hotel_acc' => $r['hotel_acc'], 'hotel_name' => $name, 'tour_id' => $r['tour_id'], 'manual_id' => $r['manual_id'], 'ref_date' => $r['d_from'], 'ref_date_to' => $r['d_to'], 'kind' => 'unusual_margin'];
            if ($payable > $quoted + 0.5) {
                $out[] = $base + [
                    'key' => 'margin:' . $r['tour_id'] . ':' . $r['hotel_acc'],
                    'level' => 'red',
                    'title' => "Payable above the quoted rate — {$name} · {$r['manual_id']} · {$span}: quoted " . number_format($quoted, 2) . ', payable ' . number_format($payable, 2) . ' (' . number_format($quoted - $payable, 2) . ')',
                    'detail' => 'The rate in the Hotel Rates panel (' . ($r['plan_name'] ?: 'contract plan') . ') is higher than the rate on the quotation for ' . $r['nights'] . ' night(s). Either the quotation was priced from an old or hand-typed rate, or the plan is wrong. Check the plan and the quotation before the hotel is paid; the payment request defaults to the payable amount.',
                    'amount' => round($quoted - $payable, 2),
                    'data' => ['quoted' => $quoted, 'payable' => $payable, 'nights' => (int) $r['nights'], 'adhoc_nights' => (int) $r['adhoc_nights'], 'plan' => $r['plan_name']],
                ];
            } elseif ((float) $r['quoted_c'] > 0 && $pct > 0) {
                $margin = (float) $r['quoted_c'] - (float) $r['payable_c'];
                $mp = $margin / (float) $r['quoted_c'] * 100;
                if ($mp > $pct) {
                    $out[] = $base + [
                        'key' => 'margin:' . $r['tour_id'] . ':' . $r['hotel_acc'],
                        'level' => 'amber',
                        'title' => "Unusually high margin — {$name} · {$r['manual_id']} · {$span}: quoted " . number_format((float) $r['quoted_c'], 2) . ', contract ' . number_format((float) $r['payable_c'], 2) . ' (' . number_format($mp, 0) . ' % margin)',
                        'detail' => 'The contract rate (' . ($r['plan_name'] ?: 'contract plan') . ') is far below the quoted rate on contract-priced nights (threshold ' . number_format($pct, 0) . ' % under Settings › Incomplete work). Fine if the quotation was priced high on purpose; otherwise check the plan (wrong meal plan, room type, per-person vs per-room) before the hotel is paid.',
                        'amount' => round($margin, 2),
                        'data' => ['quoted' => (float) $r['quoted_c'], 'payable' => (float) $r['payable_c'], 'margin_pct' => round($mp, 1), 'nights' => (int) $r['nights'], 'plan' => $r['plan_name']],
                    ];
                }
            }
        }
        return $out;
    }

    /** Upcoming quoted (not yet confirmed) nights that no approved plan covers, per hotel. */
    public function detectQuoteGaps(): array
    {
        $days = (int) ($this->svc->settings()->get('coverage_days') ?? 365);
        $sql = "SELECT tq.hotel_acc, MAX(tq.hotel_name) AS hotel_name, a.hotel_id, MAX(h.name) AS panel_name, COUNT(*) AS nights, COUNT(DISTINCT tq.quotation_id) AS tours,
                       MIN(tq.tour_date) AS d_from, MAX(tq.tour_date) AS d_to,
                       (SELECT MAX(p.valid_to) FROM hr2_contract p WHERE p.hotel_id = a.hotel_id AND p.status = 'approved' AND p.applies_to <> 'payment') AS plans_end
                FROM tour_quotations tq
                JOIN consignee cs ON cs.tour_id = tq.quotation_id AND COALESCE(cs.is_template, 0) = 0 AND COALESCE(cs.tour_cancel, 0) <> 1 AND COALESCE(cs.verify, 0) <> 1
                LEFT JOIN hr2_hotel_account a ON a.sup_acc_no = tq.hotel_acc
                LEFT JOIN hr2_hotel h ON h.id = a.hotel_id
                WHERE tq.hotel_acc <> '' AND tq.confirm <> 3 AND tq.tour_date >= CURDATE() AND tq.tour_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                  AND NOT EXISTS (SELECT 1 FROM hr2_contract p JOIN hr2_season s ON s.contract_id = p.id JOIN hr2_season_date sd ON sd.season_id = s.id
                                  WHERE p.hotel_id = a.hotel_id AND p.status = 'approved' AND p.applies_to <> 'payment' AND sd.date_from <= tq.tour_date AND sd.date_to >= tq.tour_date)
                GROUP BY tq.hotel_acc, a.hotel_id";
        $out = [];
        foreach ($this->rows($sql, [$days]) as $r) {
            $name = trim((string) ($r['panel_name'] ?: $r['hotel_name'] ?: $r['hotel_acc']));
            $inPanel = !empty($r['hotel_id']);
            $span = $this->dateSpan($r['d_from'], $r['d_to']);
            $out[] = [
                'key' => 'gap:' . $r['hotel_acc'],
                'kind' => $inPanel ? 'quote_gap' : 'hotel_missing',
                'level' => $inPanel ? 'amber' : 'red',
                'title' => ($inPanel ? 'Quoted nights without a plan' : 'Hotel not in the panel') . " — {$name}: {$r['nights']} night(s) on {$r['tours']} quotation(s), {$span}",
                'detail' => $inPanel
                    ? 'These quotations use the hotel on nights no approved plan covers' . ($r['plans_end'] ? ' (the approved plans end ' . $r['plans_end'] . ')' : ' (no approved plan at all)') . '. The timeline still shows whatever the old Hotel Rate rows hold, but the hotel will be flagged "no contract rate" once the tours are confirmed. Load or approve the sheet covering ' . $span . '.'
                    : "{$name} ({$r['hotel_acc']}) is used on {$r['tours']} upcoming quotation(s) but is not in the Hotel Rates panel. Create the hotel there (or attach the code to an existing hotel) and load its rate sheet.",
                'hotel_id' => $inPanel ? (int) $r['hotel_id'] : null,
                'hotel_acc' => $r['hotel_acc'],
                'hotel_name' => $name,
                'ref_date' => $r['d_from'],
                'ref_date_to' => $r['d_to'],
                'data' => ['nights' => (int) $r['nights'], 'tours' => (int) $r['tours'], 'plans_end' => $r['plans_end']],
            ];
        }
        return $out;
    }

    /** Plans waiting in the approval queue. */
    public function detectDrafts(): array
    {
        $sql = "SELECT c.id, c.name, c.plan_type, c.applies_to, c.valid_from, c.valid_to, c.created_at, c.entered_by, c.source_type, h.id AS hotel_id, h.name AS hotel_name, h.primary_sup_acc_no
                FROM hr2_contract c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.status = 'draft' ORDER BY h.name, c.valid_from";
        $out = [];
        foreach ($this->rows($sql) as $r) {
            $out[] = [
                'key' => 'draft:' . $r['id'],
                'kind' => 'draft_pending',
                'level' => 'amber',
                'title' => "Draft waiting approval — {$r['hotel_name']}: {$r['name']} (" . $this->dateSpan($r['valid_from'], $r['valid_to']) . ')',
                'detail' => 'Entered ' . substr((string) $r['created_at'], 0, 10) . ' by ' . ($r['entered_by'] ?: '—') . ($r['source_type'] ? ' (' . $r['source_type'] . ')' : '') . '. Until it is approved the quotation and the hotel payment fall back to the older plans or the quoted rate. Open Hotel Rates → Approval queue, check the sheet and approve.',
                'hotel_id' => (int) $r['hotel_id'],
                'hotel_acc' => $r['primary_sup_acc_no'],
                'hotel_name' => $r['hotel_name'],
                'plan_id' => (int) $r['id'],
                'ref_date' => $r['valid_from'],
                'ref_date_to' => $r['valid_to'],
                'data' => ['plan_type' => $r['plan_type'], 'applies_to' => $r['applies_to']],
            ];
        }
        return $out;
    }

    /** Hotels on the master without a supplier account. */
    public function detectMissingSuppliers(): array
    {
        $out = [];
        $codeCol = $this->codesReady() ? 'h.hotel_code' : 'NULL AS hotel_code';
        foreach ($this->rows("SELECT h.id, h.name, {$codeCol} FROM hr2_hotel h LEFT JOIN hr2_hotel_account a ON a.hotel_id = h.id WHERE a.hotel_id IS NULL AND h.status = 'active' ORDER BY h.name") as $r) {
            $code = (string) ($r['hotel_code'] ?? '');
            $out[] = [
                'key' => 'nosupplier:hotel:' . $r['id'],
                'kind' => 'missing_supplier',
                'level' => 'red',
                'title' => "No supplier account — {$r['name']}" . ($code !== '' ? " ({$code})" : ''),
                'detail' => 'The hotel is on the Hotel Master' . ($code !== '' ? " as {$code}" : '') . ' but has no supplier account (the ledger account 6-9-11-… that quotations and payments post to), so money cannot reach it. Create the account under Master Files → Supplier and attach it on the hotel\'s Hotel tab; the hotel keeps its code.',
                'hotel_id' => (int) $r['id'],
                'hotel_name' => $r['name'],
            ];
        }
        return $out;
    }

    /** Transcribed sheets (writable/hotel_rates_entry/*.json) whose hotel has no supplier record or an unknown code. */
    public function detectSheetsWithoutSupplier(): array
    {
        $out = [];
        if (!$this->entryDir || !is_dir($this->entryDir)) {
            return $out;
        }
        $files = glob(rtrim($this->entryDir, '/\\') . '/*.json') ?: [];
        sort($files);
        foreach ($files as $f) {
            if (basename($f) === 'work_items.json') {
                continue;
            }
            $doc = json_decode((string) file_get_contents($f), true);
            if (!is_array($doc) || empty($doc['sheet'])) {
                continue;
            }
            $key = (string) ($doc['key'] ?? basename($f, '.json'));
            $acc = trim((string) ($doc['hotel']['acc'] ?? ''));
            $name = trim((string) ($doc['hotel']['name'] ?? $key));
            $plan = (string) ($doc['sheet']['contract']['name'] ?? '');
            $from = $doc['sheet']['contract']['valid_from'] ?? null;
            $to = $doc['sheet']['contract']['valid_to'] ?? null;
            if ($acc !== '' && $this->svc->findHotelByAcc($acc)) {
                continue; // resolvable — load_plans takes it
            }
            $known = $acc !== '' ? $this->row('SELECT Name FROM supplier WHERE Sup_Acc_No = ?', [$acc]) : null;
            $out[] = [
                'key' => 'nosupplier:sheet:' . $key,
                'kind' => 'sheet_no_supplier',
                'level' => 'red',
                'title' => ($acc === '' ? 'Rate sheet transcribed, no supplier account' : ($known ? 'Rate sheet transcribed, supplier not on the Hotel Master' : 'Rate sheet transcribed, unknown supplier account')) . " — {$name}: {$plan}" . ($from ? ' (' . $this->dateSpan($from, $to) . ')' : ''),
                'detail' => $acc === ''
                    ? "The sheet for {$name} is transcribed (file {$key}.json) but no supplier account exists, so it cannot be loaded. Create the supplier under Master Files → Supplier, then press \"Attach supplier account\" on this item: the hotel is created with its hotel code and the plan loads as a draft for approval. Rates were NOT guessed."
                    : ($known ? "Supplier {$acc} ({$known['Name']}) exists but is not a hotel on the Hotel Master yet. Press \"Attach supplier account\" and choose {$acc}: the hotel is created from the supplier record, given its hotel code, and the plan loads as a draft." : "The sheet carries the supplier account {$acc}, which is not in the system. Check it (Master Files → Supplier) and press \"Attach supplier account\" to choose the right one."),
                'hotel_acc' => $acc !== '' ? $acc : null,
                'hotel_name' => $name,
                'ref_date' => $from ?: null,
                'ref_date_to' => $to ?: null,
                'data' => ['file' => basename($f), 'entry_key' => $key, 'plan' => $plan, 'acc_on_sheet' => $acc],
            ];
        }
        return $out;
    }

    /** Quotation countries with no market mapping (the default market is used silently). */
    public function detectUnmappedCountries(): array
    {
        $out = [];
        $default = (string) ($this->svc->settings()->get('default_market') ?? 'JP');
        $sql = "SELECT c.country, COUNT(*) AS n, MIN(c.start_date) AS d_from
                FROM consignee c LEFT JOIN hr2_market_country m ON m.country_iso = c.country
                WHERE COALESCE(c.is_template, 0) = 0 AND COALESCE(c.tour_cancel, 0) <> 1 AND c.start_date >= CURDATE() AND c.country IS NOT NULL AND c.country <> '' AND m.country_iso IS NULL
                GROUP BY c.country ORDER BY n DESC";
        foreach ($this->rows($sql) as $r) {
            $out[] = [
                'key' => 'country:' . $r['country'],
                'kind' => 'unmapped_country',
                'level' => 'amber',
                'title' => "Country {$r['country']} is not mapped to a market — {$r['n']} upcoming tour(s) priced with the default market {$default}",
                'detail' => 'Plans with market-specific rates pick the default market for these tours. Map the country under Hotel Rates → Settings → Country → market.',
                'ref_date' => $r['d_from'],
                'data' => ['country' => $r['country'], 'tours' => (int) $r['n']],
            ];
        }
        return $out;
    }

    /**
     * Open "no rate while quoting" items are re-checked: the hotel may have been changed on
     * the quotation, or a plan may cover the night now. Returns the ones still open (so the
     * scan keeps them) — closing happens through the normal "no longer found" path.
     */
    public function recheckQuoteNoRate(): array
    {
        $keep = [];
        foreach ($this->rows("SELECT * FROM hr2_work_item WHERE kind = 'quote_no_rate' AND status = 'open'") as $it) {
            $d = json_decode((string) $it['data_json'], true) ?: [];
            $acc = (string) ($d['hotel_acc'] ?? $it['hotel_acc']);
            $date = (string) ($d['date'] ?? $it['ref_date']);
            // The New Tour form (tour/tour) has no date at all — re-run the same dateless
            // legacy lookup it uses and close only if that lookup is usable now.
            if (!empty($d['legacy_form'])) {
                if ($acc !== '' && $this->legacyFormRateUsable($acc, (string) ($d['room'] ?? ''), (string) ($d['meal'] ?? ''))) {
                    continue; // a legacy row now prices it — closed by the scan
                }
                if ($acc === '') {
                    continue; // malformed, nothing to re-check — drop it rather than keep forever
                }
                $keep[] = ['key' => $it['item_key'], 'kind' => 'quote_no_rate', 'level' => $it['level'], 'title' => $it['title'], 'detail' => $it['detail'], 'hotel_id' => $it['hotel_id'], 'hotel_acc' => $it['hotel_acc'], 'hotel_name' => $it['hotel_name'], 'tour_id' => $it['tour_id'], 'manual_id' => $it['manual_id'], 'plan_id' => $it['plan_id'], 'ref_date' => $it['ref_date'], 'ref_date_to' => $it['ref_date_to'], 'amount' => $it['amount'], 'data' => $d];
                continue;
            }
            $tour = (string) ($d['tour_id'] ?? $it['tour_id']);
            if ($acc === '' || $date === '') {
                continue;
            }
            if ($tour !== '') {
                $still = $this->row('SELECT COUNT(*) AS n FROM tour_quotations WHERE quotation_id = ? AND tour_date = ? AND hotel_acc = ? AND confirm <> 3', [$tour, $date, $acc]);
                if ((int) ($still['n'] ?? 0) === 0) {
                    continue; // the quotation moved on — closed by the scan
                }
            }
            $opts = [];
            if (!empty($d['country'])) {
                $opts['country'] = $d['country'];
            }
            try {
                $r = $this->svc->lookup($acc, $d['room'] ?? '', (string) ($d['meal'] ?? ''), $date, $opts);
            } catch (\Throwable $e) {
                $r = null;
            }
            if ($r) {
                continue; // a rate exists now — closed by the scan
            }
            $keep[] = ['key' => $it['item_key'], 'kind' => 'quote_no_rate', 'level' => $it['level'], 'title' => $it['title'], 'detail' => $it['detail'], 'hotel_id' => $it['hotel_id'], 'hotel_acc' => $it['hotel_acc'], 'hotel_name' => $it['hotel_name'], 'tour_id' => $it['tour_id'], 'manual_id' => $it['manual_id'], 'plan_id' => $it['plan_id'], 'ref_date' => $it['ref_date'], 'ref_date_to' => $it['ref_date_to'], 'amount' => $it['amount'], 'data' => $d];
        }
        return $keep;
    }

    /**
     * Called by the quotation timeline when it asked for a rate: registers the gap (never a
     * guess) so it shows in the panel until a plan covers the night or the hotel changes.
     * $found = 'none' (nothing at all) | 'legacy' (only an old Hotel Rate row, no approved plan).
     */
    public function registerQuoteGap(string $tourId, string $hotelAcc, string $date, $room, string $meal, string $found, array $extra = []): ?int
    {
        if ($tourId === '' || $hotelAcc === '' || $date === '') {
            return null;
        }
        $hotel = $this->svc->findHotelByAcc($hotelAcc);
        $sup = $this->row('SELECT Name FROM supplier WHERE Sup_Acc_No = ?', [$hotelAcc]);
        $name = trim((string) ($hotel['name'] ?? $sup['Name'] ?? $hotelAcc));
        $cs = $this->row('SELECT manual_id, country FROM consignee WHERE tour_id = ?', [$tourId]);
        $roomName = null;
        if (is_numeric((string) $room)) {
            $rt = $this->row('SELECT rtype FROM room_type WHERE id = ?', [(int) $room]);
            $roomName = $rt['rtype'] ?? null;
        }
        $roomLabel = $roomName ?: (string) $room;
        $mealU = strtoupper($meal);
        $title = ($found === 'legacy' ? 'Rate came from the old Hotel Rate screen, no approved plan' : 'No rate found while quoting') . " — {$name} · " . ($cs['manual_id'] ?? $tourId) . " · {$date} · {$roomLabel} {$mealU}";
        $detail = $found === 'legacy'
            ? "The timeline priced {$date} from an old Hotel Rate row because no approved plan in the Hotel Rates panel covers {$name} / {$roomLabel} / {$mealU} on that night. Load or approve the sheet; until then the payment side will flag the night \"check rate before payment\"."
            : "Neither the Hotel Rates panel nor the old Hotel Rate rows price {$name} / {$roomLabel} / {$mealU} on {$date}; the rate was typed by hand (or left empty). Load or approve the sheet covering that night. Nothing was guessed.";
        $item = [
            'key' => 'quote:' . $tourId . ':' . $hotelAcc . ':' . $date . ':' . $room . ':' . strtolower($meal),
            'kind' => 'quote_no_rate',
            'level' => $found === 'legacy' ? 'amber' : 'red',
            'title' => $title,
            'detail' => $detail,
            'hotel_id' => $hotel ? (int) $hotel['id'] : null,
            'hotel_acc' => $hotelAcc,
            'hotel_name' => $name,
            'tour_id' => $tourId,
            'manual_id' => $cs['manual_id'] ?? null,
            'ref_date' => $date,
            'data' => ['tour_id' => $tourId, 'hotel_acc' => $hotelAcc, 'date' => $date, 'room' => (string) $room, 'room_name' => $roomName, 'meal' => strtolower($meal), 'country' => $cs['country'] ?? null, 'found' => $found] + $extra,
        ];
        $this->upsert($item, 'auto', date('Y-m-d H:i:s'));
        return (int) ($this->row('SELECT id FROM hr2_work_item WHERE item_key = ?', [$item['key']])['id'] ?? 0);
    }

    /** Mirrors TourController::get_meal_prices()'s own lookup exactly — that screen has no date, so this can't. */
    private function legacyFormRateUsable(string $hotelAcc, string $room, string $meal): bool
    {
        $row = $this->row('SELECT * FROM hotel_rate WHERE hotel = ? AND room_type = ? LIMIT 1', [$hotelAcc, $room]);
        if (!$row) {
            return false;
        }
        $cols = ['fb' => ['sfb', 'dfb', 'tfb'], 'hb' => ['shb', 'dhb', 'thb'], 'bb' => ['sbb', 'dbb', 'tbb']][strtolower($meal)] ?? null;
        if (!$cols) {
            return false;
        }
        foreach ($cols as $c) {
            if ((float) ($row[$c] ?? 0) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * The New Tour form (tour/tour, TourController::get_meal_prices) has no date at all, so it
     * can only ever read the legacy hotel_rate table — the Hotel Rates panel cannot be checked
     * from there. This records when that lookup finds nothing usable, so the gap is visible here
     * instead of a one-time popup nobody else sees. Never blocks the form; the price the browser
     * gets back is completely unchanged either way. $reason = 'none' (no row at all) | 'zero' (a
     * row exists but this meal plan's SGL/DBL/TPL are all 0 — same "nothing usable" case).
     */
    public function registerLegacyFormGap(string $hotelAcc, $room, string $meal, string $reason): ?int
    {
        if ($hotelAcc === '') {
            return null;
        }
        $hotel = $this->svc->findHotelByAcc($hotelAcc);
        $sup = $this->row('SELECT Name FROM supplier WHERE Sup_Acc_No = ?', [$hotelAcc]);
        $name = trim((string) ($hotel['name'] ?? $sup['Name'] ?? $hotelAcc));
        $roomName = null;
        if (is_numeric((string) $room)) {
            $rt = $this->row('SELECT rtype FROM room_type WHERE id = ?', [(int) $room]);
            $roomName = $rt['rtype'] ?? null;
        }
        $roomLabel = $roomName ?: (string) $room;
        $mealU = strtoupper($meal);
        $title = "No rate on the New Tour form — {$name} · {$roomLabel} {$mealU}";
        $detail = $reason === 'zero'
            ? "The old Hotel Rate row for {$name} / {$roomLabel} has no {$mealU} price set (SGL, DBL and TPL are all 0), so the New Tour form left the price fields at 0. This screen has no date, so the Hotel Rates panel could not be checked either. Load or complete the rate sheet covering this hotel and room. Nothing was guessed."
            : "Neither an old Hotel Rate row nor the Hotel Rates panel has a rate for {$name} / {$roomLabel} / {$mealU}. This screen (New Tour) has no date, so only the old table could be checked. Load or approve the rate sheet covering this hotel and room. Nothing was guessed.";
        $item = [
            'key' => 'legacyform:' . $hotelAcc . ':' . $room . ':' . strtolower($meal),
            'kind' => 'quote_no_rate',
            'level' => $reason === 'zero' ? 'amber' : 'red',
            'title' => $title,
            'detail' => $detail,
            'hotel_id' => $hotel ? (int) $hotel['id'] : null,
            'hotel_acc' => $hotelAcc,
            'hotel_name' => $name,
            'data' => ['legacy_form' => true, 'hotel_acc' => $hotelAcc, 'room' => (string) $room, 'room_name' => $roomName, 'meal' => strtolower($meal), 'reason' => $reason],
        ];
        $this->upsert($item, 'auto', date('Y-m-d H:i:s'));
        return (int) ($this->row('SELECT id FROM hr2_work_item WHERE item_key = ?', [$item['key']])['id'] ?? 0);
    }

    // =====================================================================
    //  People: add, resolve, list, export
    // =====================================================================

    /** Add (or refresh) an item by hand — from the panel, or from a seed file. Never closed automatically. */
    public function register(array $item): int
    {
        $kind = (string) ($item['kind'] ?? 'manual');
        if (!isset(self::KINDS[$kind])) {
            $kind = 'manual';
        }
        $title = trim((string) ($item['title'] ?? ''));
        if ($title === '') {
            throw new \RuntimeException('The item needs a title.');
        }
        $acc = isset($item['hotel_acc']) ? trim((string) $item['hotel_acc']) : '';
        $hotel = $acc !== '' ? $this->svc->findHotelByAcc($acc) : null;
        if (!$hotel && !empty($item['hotel_id'])) {
            $hotel = $this->svc->getHotel((int) $item['hotel_id']);
        }
        $tour = isset($item['tour_id']) ? trim((string) $item['tour_id']) : '';
        $manual = isset($item['manual_id']) ? trim((string) $item['manual_id']) : '';
        if ($tour === '' && $manual !== '') {
            $c = $this->row('SELECT tour_id FROM consignee WHERE manual_id = ? ORDER BY ID DESC LIMIT 1', [$manual]);
            $tour = (string) ($c['tour_id'] ?? '');
        } elseif ($tour !== '' && $manual === '') {
            $c = $this->row('SELECT manual_id FROM consignee WHERE tour_id = ? LIMIT 1', [$tour]);
            $manual = (string) ($c['manual_id'] ?? '');
        }
        $key = trim((string) ($item['key'] ?? ''));
        if ($key === '') {
            $key = 'manual:' . substr(sha1($kind . '|' . $title . '|' . $acc . '|' . $tour . '|' . ($item['ref_date'] ?? '')), 0, 24);
        }
        $level = in_array($item['level'] ?? '', ['red', 'amber', 'blue'], true) ? $item['level'] : (self::KINDS[$kind]['level'] ?? 'amber');
        $it = [
            'key' => mb_substr($key, 0, 190),
            'kind' => $kind,
            'level' => $level,
            'title' => $title,
            'detail' => isset($item['detail']) ? trim((string) $item['detail']) : null,
            'hotel_id' => $hotel ? (int) $hotel['id'] : null,
            'hotel_acc' => $acc !== '' ? $acc : ($hotel['primary_sup_acc_no'] ?? null),
            'hotel_name' => $hotel['name'] ?? (isset($item['hotel_name']) ? trim((string) $item['hotel_name']) : null),
            'tour_id' => $tour !== '' ? $tour : null,
            'manual_id' => $manual !== '' ? $manual : null,
            'plan_id' => !empty($item['plan_id']) ? (int) $item['plan_id'] : null,
            'ref_date' => !empty($item['ref_date']) ? Dates::parse($item['ref_date']) : null,
            'ref_date_to' => !empty($item['ref_date_to']) ? Dates::parse($item['ref_date_to']) : null,
            'amount' => isset($item['amount']) && $item['amount'] !== '' ? (float) $item['amount'] : null,
            'data' => $item['data'] ?? null,
            'note' => isset($item['note']) ? trim((string) $item['note']) : null,
        ];
        $this->upsert($it, 'manual', date('Y-m-d H:i:s'));
        return (int) ($this->row('SELECT id FROM hr2_work_item WHERE item_key = ?', [$it['key']])['id'] ?? 0);
    }

    /** Load a seed file of items (the shape register() takes), e.g. writable/hotel_rates_entry/work_items.json. */
    public function loadSeedFile(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException("Seed file not found: {$path}");
        }
        $doc = json_decode((string) file_get_contents($path), true);
        $items = is_array($doc) ? ($doc['items'] ?? $doc) : [];
        $out = ['loaded' => 0, 'failed' => []];
        foreach ($items as $i => $item) {
            try {
                $this->register(is_array($item) ? $item : []);
                $out['loaded']++;
            } catch (\Throwable $e) {
                $out['failed'][] = "#{$i}: " . $e->getMessage();
            }
        }
        return $out;
    }

    /** Mark an item done / ignored / open again, with a note. */
    public function resolve(int $id, string $status, ?string $note = null): array
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \RuntimeException("Unknown status {$status}.");
        }
        $it = $this->row('SELECT * FROM hr2_work_item WHERE id = ?', [$id]);
        if (!$it) {
            throw new \RuntimeException("Work item {$id} not found.");
        }
        $now = date('Y-m-d H:i:s');
        $line = ($note !== null && trim($note) !== '') ? trim($note) : null;
        $stamp = ($status === 'open' ? 'Reopened' : ($status === 'done' ? 'Done' : 'Ignored')) . ' by ' . $this->user . ' on ' . substr($now, 0, 16) . ($line ? ': ' . $line : '');
        $this->db->query(
            'UPDATE hr2_work_item SET status = ?, resolved_at = ?, resolved_by = ?, updated_at = ?, note = CONCAT(COALESCE(note, \'\'), ?) WHERE id = ?',
            [$status, $status === 'open' ? null : $now, $status === 'open' ? null : $this->user, $now, $this->noteSep($id) . $stamp, $id]
        );
        $this->svc->audit('work_item', $id, 'update', ['status' => $it['status']], ['status' => $status, 'note' => $line]);
        return $this->get($id);
    }

    /**
     * The same decision for the rest of the hotel's open items of the same kind (6 Oct 2026, asked for on Misty Villa: a
     * hotel Avenir does not use has several transcribed sheets, and ignoring them one by one with the same reason is
     * busywork). Siblings are matched on the hotel record when the item has one, else on the hotel's name as the sheets
     * spell it. Each sibling gets the same note; nothing is deleted and every one can be reopened. Returns how many.
     */
    public function resolveHotelSiblings(int $id, string $status, ?string $note = null): int
    {
        $it = $this->row('SELECT * FROM hr2_work_item WHERE id = ?', [$id]);
        if (!$it) {
            throw new \RuntimeException("Work item {$id} not found.");
        }
        if (!empty($it['hotel_id'])) {
            $sibs = $this->rows("SELECT id FROM hr2_work_item WHERE id <> ? AND status = 'open' AND kind = ? AND hotel_id = ?", [$id, $it['kind'], (int) $it['hotel_id']]);
        } elseif (trim((string) $it['hotel_name']) !== '') {
            $sibs = $this->rows("SELECT id FROM hr2_work_item WHERE id <> ? AND status = 'open' AND kind = ? AND hotel_id IS NULL AND LOWER(TRIM(hotel_name)) = ?", [$id, $it['kind'], mb_strtolower(trim((string) $it['hotel_name']))]);
        } else {
            return 0;
        }
        foreach ($sibs as $s) {
            $this->resolve((int) $s['id'], $status, ($note !== null && trim($note) !== '' ? trim($note) . ' ' : '') . "(with item #{$id}, the whole hotel)");
        }
        return count($sibs);
    }

    /** Close an open item by its key (e.g. when the draft it points at is approved or deleted). */
    public function closeByKey(string $key, string $note): bool
    {
        $it = $this->row("SELECT id FROM hr2_work_item WHERE item_key = ? AND status = 'open'", [$key]);
        if (!$it) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $this->db->query("UPDATE hr2_work_item SET status = 'done', resolved_at = ?, resolved_by = 'system', updated_at = ?, note = CONCAT(COALESCE(note, ''), ?) WHERE id = ?", [$now, $now, $this->noteSep((int) $it['id']) . $note . ' (' . substr($now, 0, 16) . ')', (int) $it['id']]);
        return true;
    }

    public function get(int $id): ?array
    {
        $it = $this->row('SELECT w.*, ' . $this->codeSelect() . ' FROM hr2_work_item w LEFT JOIN hr2_hotel h ON h.id = w.hotel_id WHERE w.id = ?', [$id]);
        return $it ? $this->decorate($it) : null;
    }

    /** `h.hotel_code` once schema 8 is in, NULL before it — so the same queries run on an older installation. */
    private function codeSelect(): string
    {
        return $this->codesReady() ? 'h.hotel_code' : 'NULL AS hotel_code';
    }

    private function codesReady(): bool
    {
        if ($this->codesReady === null) {
            try {
                $this->codesReady = (bool) $this->db->query("SHOW COLUMNS FROM hr2_hotel LIKE 'hotel_code'")->getRowArray();
            } catch (\Throwable $e) {
                $this->codesReady = false;
            }
        }
        return $this->codesReady;
    }

    /**
     * Hotel suppliers for the "Attach supplier account" dialog: by name, by hotel code (HTL-017 / H017 / 17) or by
     * account number; each with the hotel it already is on the master, when it is. Nothing is created here.
     *
     * @return array<int, array{acc:string, name:string, address:?string, legacy_code:?string, hotel_id:?int, hotel_name:?string, hotel_code:?string}>
     */
    public function searchSuppliers(string $q, int $limit = 15): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }
        $codeCol = $this->codesReady() ? 'h.hotel_code' : 'NULL';
        $sql = "SELECT s.Sup_Acc_No AS acc, s.Name AS name, s.Address AS address, s.hotel_code AS legacy_code,
                       h.id AS hotel_id, h.name AS hotel_name, {$codeCol} AS hotel_code
                FROM supplier s
                LEFT JOIN hr2_hotel_account a ON a.sup_acc_no = s.Sup_Acc_No
                LEFT JOIN hr2_hotel h ON h.id = a.hotel_id
                WHERE s.sup_cat = 1 AND (s.Name LIKE ? OR s.Sup_Acc_No LIKE ?";
        $like = '%' . $q . '%';
        $binds = [$like, $like];
        $no = HotelCodes::parse($q);
        if ($no !== null) {
            $sql .= ' OR s.hotel_code = ?';
            $binds[] = 'H' . str_pad((string) $no, 3, '0', STR_PAD_LEFT);
            if ($this->codesReady()) {
                $sql .= ' OR h.hotel_code = ?';
                $binds[] = HotelCodes::format($no);
            }
        }
        $sql .= ') ORDER BY s.Name LIMIT ' . max(1, min(50, $limit));
        $out = [];
        foreach ($this->rows($sql, $binds) as $r) {
            $out[] = [
                'acc'         => trim((string) $r['acc']),
                'name'        => trim((string) $r['name']),
                'address'     => trim((string) ($r['address'] ?? '')) ?: null,
                'legacy_code' => trim((string) ($r['legacy_code'] ?? '')) ?: null,
                'hotel_id'    => $r['hotel_id'] !== null ? (int) $r['hotel_id'] : null,
                'hotel_name'  => $r['hotel_name'] ?? null,
                'hotel_code'  => $r['hotel_code'] ?? null,
            ];
        }
        return $out;
    }

    /** @param array{status?:string,kind?:string,q?:string,hotel_id?:int,tour_id?:string,limit?:int} $f */
    public function list(array $f = []): array
    {
        $where = [];
        $binds = [];
        $status = $f['status'] ?? 'open';
        if ($status !== 'all') {
            $where[] = 'w.status = ?';
            $binds[] = $status;
        }
        if (!empty($f['kind'])) {
            $where[] = 'w.kind = ?';
            $binds[] = $f['kind'];
        }
        if (!empty($f['hotel_id'])) {
            $where[] = 'w.hotel_id = ?';
            $binds[] = (int) $f['hotel_id'];
        }
        if (!empty($f['tour_id'])) {
            $where[] = 'w.tour_id = ?';
            $binds[] = (string) $f['tour_id'];
        }
        if (!empty($f['q'])) {
            $q = '%' . trim((string) $f['q']) . '%';
            $cond = '(w.title LIKE ? OR w.detail LIKE ? OR w.hotel_name LIKE ? OR w.hotel_acc LIKE ? OR w.manual_id LIKE ? OR w.note LIKE ?';
            array_push($binds, $q, $q, $q, $q, $q, $q);
            $no = HotelCodes::parse((string) $f['q']);
            if ($no !== null && $this->codesReady()) {           // "HTL-017", "H017" or "17" finds the hotel's items
                $cond .= ' OR h.hotel_code = ?';
                $binds[] = HotelCodes::format($no);
            }
            $where[] = $cond . ')';
        }
        $sql = 'SELECT w.*, ' . $this->codeSelect() . ' FROM hr2_work_item w LEFT JOIN hr2_hotel h ON h.id = w.hotel_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . " ORDER BY FIELD(w.status, 'open', 'ignored', 'done'), FIELD(w.level, 'red', 'amber', 'blue'), COALESCE(w.ref_date, '9999-12-31'), w.hotel_name, w.id LIMIT " . (int) ($f['limit'] ?? 500);
        return array_map([$this, 'decorate'], $this->rows($sql, $binds));
    }

    /** Open items per kind. */
    public function counts(): array
    {
        $out = [];
        foreach ($this->rows("SELECT kind, level, COUNT(*) AS n FROM hr2_work_item WHERE status = 'open' GROUP BY kind, level") as $r) {
            $out[$r['kind']] ??= ['n' => 0, 'red' => 0, 'amber' => 0, 'blue' => 0, 'label' => self::KINDS[$r['kind']]['label'] ?? $r['kind']];
            $out[$r['kind']]['n'] += (int) $r['n'];
            $out[$r['kind']][$r['level']] += (int) $r['n'];
        }
        return $out;
    }

    public function openCount(): int
    {
        return (int) ($this->row("SELECT COUNT(*) AS n FROM hr2_work_item WHERE status = 'open'")['n'] ?? 0);
    }

    /** Plain-text export of the open items — what to hand to Claude (or a colleague) to fill the gaps. */
    public function exportText(array $f = []): string
    {
        $items = $this->list($f + ['status' => 'open']);
        $lines = ['Hotel Incomplete Work — open items on ' . date('Y-m-d H:i') . ' (' . count($items) . ')', ''];
        $byKind = [];
        foreach ($items as $it) {
            $byKind[$it['kind']][] = $it;
        }
        foreach ($byKind as $kind => $list) {
            $lines[] = '## ' . (self::KINDS[$kind]['label'] ?? $kind) . ' (' . count($list) . ')';
            foreach ($list as $it) {
                $lines[] = '- [#' . $it['id'] . ' ' . $it['level'] . '] ' . $it['title'];
                if ($it['detail']) {
                    $lines[] = '    ' . $it['detail'];
                }
                $bits = array_filter([$it['hotel_acc'] ? 'supplier ' . $it['hotel_acc'] : null, $it['manual_id'] ? 'tour ' . $it['manual_id'] : null, $it['tour_id'] ? 'tour_id ' . $it['tour_id'] : null, $it['plan_id'] ? 'plan #' . $it['plan_id'] : null, $it['ref_date'] ? 'dates ' . $this->dateSpan($it['ref_date'], $it['ref_date_to']) : null]);
                if ($bits) {
                    $lines[] = '    (' . implode(' · ', $bits) . ')';
                }
                if (!empty($it['data']['nights'])) {
                    $lines[] = '    nights: ' . implode('; ', array_map(static fn ($n) => is_array($n) ? trim(($n['date'] ?? '') . ' ' . ($n['room_type'] ?? '') . ' ' . strtoupper((string) ($n['meal_plan'] ?? '')) . (isset($n['quoted']) ? ' quoted ' . $n['quoted'] : '')) : (string) $n, (array) $it['data']['nights']));
                }
                if ($it['note']) {
                    $lines[] = '    note: ' . str_replace("\n", ' / ', $it['note']);
                }
            }
            $lines[] = '';
        }
        return implode("\n", $lines);
    }

    /**
     * Save (or update) what is already known about a hotel that needs a supplier record — its
     * address, chain, a contact person, phone, email, a free-text note — before the record itself
     * exists. This never creates or guesses a supplier account; it only keeps what a person (or a rate
     * sheet) has already said, so nobody re-types it once the code is created under Master Files →
     * Supplier and "Attach supplier account" finishes the job. Blank fields clear what was there.
     * Untouched by scans (see STICKY_DATA_KEYS in upsert()), so it survives until the item closes.
     */
    public function saveSupplierDetails(int $itemId, array $details): array
    {
        $it = $this->row('SELECT id, kind, data_json FROM hr2_work_item WHERE id = ?', [$itemId]);
        if (!$it) {
            throw new \RuntimeException("Work item {$itemId} not found.");
        }
        if (!in_array($it['kind'], self::SUPPLIER_DETAIL_KINDS, true)) {
            throw new \RuntimeException('Supplier details can only be saved on a "missing supplier" item.');
        }
        $data = json_decode((string) $it['data_json'], true) ?: [];
        $sd = $data['supplier_details'] ?? [];
        foreach (['chain', 'address', 'contact_person', 'phone', 'email', 'notes'] as $f) {
            if (!array_key_exists($f, $details)) {
                continue;
            }
            $v = trim((string) $details[$f]);
            if ($v === '') {
                unset($sd[$f]);
            } else {
                $sd[$f] = mb_substr($v, 0, $f === 'notes' || $f === 'address' ? 2000 : 255);
            }
        }
        $now = date('Y-m-d H:i:s');
        $sd['updated_at'] = $now;
        $sd['updated_by'] = $this->user;
        $data['supplier_details'] = $sd;
        $this->db->query('UPDATE hr2_work_item SET data_json = ?, updated_at = ? WHERE id = ?', [json_encode($data, JSON_UNESCAPED_UNICODE), $now, $itemId]);
        return (array) $this->get($itemId);
    }

    /**
     * For a "rate sheet without a supplier account" item: write the supplier account into the sheet file,
     * create the panel hotel from the supplier record when needed, load the plan as a draft
     * and close the item.
     */
    public function attachSupplierAndLoad(int $itemId, string $acc): array
    {
        $it = $this->row('SELECT * FROM hr2_work_item WHERE id = ?', [$itemId]);
        if (!$it || $it['kind'] !== 'sheet_no_supplier') {
            throw new \RuntimeException('This action is only for "rate sheet without a supplier" items.');
        }
        $acc = trim($acc);
        if ($acc === '') {
            throw new \RuntimeException('Choose the supplier account (6-9-11-…).');
        }
        $d = json_decode((string) $it['data_json'], true) ?: [];
        $file = rtrim((string) $this->entryDir, '/\\') . '/' . basename((string) ($d['file'] ?? ''));
        if (!$this->entryDir || !is_file($file)) {
            throw new \RuntimeException('The sheet file is no longer in writable/hotel_rates_entry.');
        }
        $hotel = $this->svc->findHotelByAcc($acc);
        $created = false;
        if (!$hotel) {
            $sup = $this->row('SELECT Name, Address, email, TP FROM supplier WHERE Sup_Acc_No = ?', [$acc]);
            if (!$sup) {
                throw new \RuntimeException("Supplier account {$acc} is not in the system (Master Files → Supplier). Nothing was changed.");
            }
            // the location only when the address names a known one (a street address no longer becomes a location of its own)
            $hid = $this->svc->createHotel(trim((string) $sup['Name']), $acc, $this->svc->matchLocation((string) ($sup['Address'] ?? '')), [
                'address' => trim((string) ($sup['Address'] ?? '')), 'reservations_email' => trim((string) ($sup['email'] ?? '')), 'reservations_phone' => trim((string) ($sup['TP'] ?? '')),
            ]);
            $hotel = $this->svc->getHotel($hid);
            $created = true;
        }
        $doc = json_decode((string) file_get_contents($file), true);
        if (!is_array($doc) || empty($doc['sheet'])) {
            throw new \RuntimeException('The sheet file could not be read.');
        }
        $doc['hotel']['acc'] = $acc;
        $doc['hotel']['attached_by'] = $this->user;
        $doc['hotel']['attached_at'] = date('Y-m-d H:i:s');
        file_put_contents($file, json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $sheet = $doc['sheet'];
        $sheet['hotel'] = ['id' => (int) $hotel['id'], 'name' => $hotel['name'], 'sup_acc_no' => $acc];
        $name = (string) ($sheet['contract']['name'] ?? '');
        $existing = $this->row("SELECT id, status FROM hr2_contract WHERE hotel_id = ? AND name = ? AND status IN ('draft','approved') ORDER BY id DESC LIMIT 1", [(int) $hotel['id'], $name]);
        $res = null;
        if ($existing) {
            $planId = (int) $existing['id'];
        } else {
            $res = $this->svc->saveContract($sheet, ['acknowledge_warnings' => true]);
            if (empty($res['saved'])) {
                $c = RateValidator::count($res['issues'] ?? []);
                throw new \RuntimeException("The plan could not be saved: {$c['errors']} error(s) in the sheet. Open Hotel Rates → {$hotel['name']} and enter it by hand.");
            }
            $planId = (int) $res['contract_id'];
        }
        $code = (string) ($hotel['hotel_code'] ?? '');
        $who = $hotel['name'] . ($code !== '' ? " ({$code})" : '');
        $note = ($created ? "Hotel {$who} created from supplier account {$acc}" . ($code !== '' ? " and given the hotel code {$code}" : '') . '. ' : "Supplier account {$acc} attached to {$who}. ") . ($existing ? "Plan #{$planId} was already there ({$existing['status']})." : "Plan \"{$name}\" loaded as draft #{$planId} — approve it in the Hotel Rates queue.");
        $this->resolve($itemId, 'done', $note);
        return ['hotel_id' => (int) $hotel['id'], 'hotel_name' => $hotel['name'], 'hotel_code' => $code ?: null, 'created' => $created, 'plan_id' => $planId, 'existing' => (bool) $existing, 'note' => $note, 'issues' => $res['issues'] ?? []];
    }

    // =====================================================================
    //  Helpers
    // =====================================================================

    private function decorate(array $it): array
    {
        $it['data'] = $it['data_json'] ? (json_decode($it['data_json'], true) ?: null) : null;
        unset($it['data_json']);
        $it['kind_label'] = self::KINDS[$it['kind']]['label'] ?? $it['kind'];
        $it['auto'] = self::KINDS[$it['kind']]['auto'] ?? false;
        $it['age_days'] = $it['created_at'] ? (int) floor((time() - strtotime($it['created_at'])) / 86400) : 0;
        $it['dates'] = $it['ref_date'] ? $this->dateSpan($it['ref_date'], $it['ref_date_to']) : null;
        if (in_array($it['kind'], self::SUPPLIER_DETAIL_KINDS, true)) {
            $sd = $it['data']['supplier_details'] ?? [];
            $missing = [];
            if (empty($sd['address'])) {
                $missing[] = 'address';
            }
            if (empty($sd['phone']) && empty($sd['email'])) {
                $missing[] = 'phone or email';
            }
            $it['supplier_missing'] = $missing;
        }
        return $it;
    }

    private function dateSpan(?string $from, ?string $to): string
    {
        if (!$from) {
            return '';
        }
        if (!$to || $to === $from) {
            return date('j M Y', strtotime($from));
        }
        $f = strtotime($from);
        $t = strtotime($to);
        if (date('Y-m', $f) === date('Y-m', $t)) {
            return date('j', $f) . '–' . date('j M Y', $t);
        }
        if (date('Y', $f) === date('Y', $t)) {
            return date('j M', $f) . ' – ' . date('j M Y', $t);
        }
        return date('j M Y', $f) . ' – ' . date('j M Y', $t);
    }

    private function nightList(array $nights): string
    {
        $parts = [];
        foreach (array_slice($nights, 0, 6) as $n) {
            $parts[] = date('j M', strtotime($n['tour_date'])) . ' ' . trim(($n['room_type_name'] ?: 'room') . ' ' . strtoupper((string) $n['meal_plan']));
        }
        if (count($nights) > 6) {
            $parts[] = '… +' . (count($nights) - 6) . ' more';
        }
        return implode(', ', $parts);
    }

    private function row(string $sql, array $binds = []): ?array
    {
        $r = $this->db->query($sql, $binds);
        return ($r === false || $r === true) ? null : ($r->getRowArray() ?: null);
    }

    private function rows(string $sql, array $binds = []): array
    {
        $r = $this->db->query($sql, $binds);
        return ($r === false || $r === true) ? [] : $r->getResultArray();
    }
}
