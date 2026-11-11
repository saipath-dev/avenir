<?php

namespace App\Libraries\TourFlow;

/**
 * tf-stage4 (29 Oct 2026) — saved costings are protected.
 *
 * An inquiry's figures (the rate on each night, each entrance / meal / activity line, each vehicle's rate per kilometre,
 * and the USD rate the costing converts rupees at) are what the agent was, or is about to be, quoted. Opening or
 * refreshing the inquiry never moves them. When a master rate has changed since, the change is shown beside the
 * saved figure — old and new — and the quotation gate (Go for Quotation) waits until somebody authorised has either
 * taken the new figures (Recalculate) or kept the saved ones (Keep saved figures). Both are written to tf_recalc_log
 * with the figures before and after, who did it and when, and to the inquiry's history.
 *
 * Who may: holders of the Recalculate right (menu URL tour/intake/recalculate — granted to the System Administrator,
 * Management and the Quotation Desk). A holder who is not the System Administrator and does not hold Pricing
 * (Management) may act only on an inquiry they handle (tf_intake.handler_id).
 *
 * Issued quotation versions (tf_quote.cost_json), confirmed tours and payments carry their own frozen figures and are
 * never touched from here.
 */
final class CostingGuard
{
    public const URL = 'tour/intake/recalculate';

    private static ?bool $installed = null;

    public static function installed($db): bool
    {
        if (self::$installed === null) {
            try {
                self::$installed = (bool) $db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'usd_rate_kept'")->getResultArray()
                    && (bool) $db->query("SHOW TABLES LIKE 'tf_recalc_log'")->getResultArray();
            } catch (\Throwable $e) {
                self::$installed = false;
            }
        }
        return self::$installed;
    }

    // ------------------------------------------------------------------ service lines: what a line was priced for

    /**
     * What an entrance / meal / activity line was priced for. The same key = the same line, so a different answer from
     * the master is a changed rate (shown, not applied). Another party, date, quantity or child decision is another
     * price, and the line is read afresh.
     */
    public static function serviceKey(array $row, ?int $pax, ?array $party): string
    {
        $ages = $party['child_ages'] ?? [];
        if (is_array($ages)) {
            sort($ages);
        }
        return sha1(json_encode([
            'pax' => $pax, 'adults' => $party['adults'] ?? null, 'children' => $party['children'] ?? null, 'ages' => $ages,
            'decision' => (string) ($row['child_decision'] ?? ''), 'date' => $row['day_date'] ?? null,
            'qty' => $row['qty'] ?? null, 'override' => (int) ($row['qty_override'] ?? 0),
            'meal' => $row['meal_id'] ?? null, 'free' => $row['free_visit_id'] ?? null, 'title' => trim((string) ($row['title'] ?? '')),
        ]));
    }

    /** A service line carries a figure the agent is quoted on (priced from the list, or settled as no charge). */
    public static function serviceHasFigure(array $row): bool
    {
        $state = (string) ($row['match_state'] ?? '');
        if ($state === 'no_charge') {
            return !empty($row['checked_at']);
        }
        return $state === 'matched' && (($row['unit_usd'] ?? null) !== null || ($row['amount_usd'] ?? null) !== null);
    }

    // ------------------------------------------------------------------ the watch: every rate that moved since

    /** A money figure in words. */
    public static function money($v, ?string $cur = null): string
    {
        if ($v === null || $v === '') {
            return '—';
        }
        return ($cur ? $cur . ' ' : '') . number_format((float) $v, 2);
    }

    private static function nightFigure(array $f, ?string $cur): string
    {
        if (($f['unit'] ?? null) !== null && $f['unit'] !== '') {
            return self::money($f['unit'], $cur) . ' per unit';
        }
        $parts = [];
        foreach (['sgl' => 'SGL', 'dbl' => 'DBL', 'tpl' => 'TPL'] as $k => $lbl) {
            if (($f[$k] ?? null) !== null && $f[$k] !== '') {
                $parts[] = $lbl . ' ' . number_format((float) $f[$k], 2);
            }
        }
        return $parts ? ($cur ? $cur . ' ' : '') . implode(' / ', $parts) : 'no rate';
    }

    /**
     * Everything that has moved away from the saved figures of one inquiry (as get() read it): the nights Hotel Rates
     * changed, the service lines the master changed, the vehicles whose master rate moved, and the USD rate.
     * Pure reading; nothing is written.
     */
    public static function watch(array $it, ?array $usdNow): array
    {
        $items = [];
        foreach (($it['days'] ?? []) as $d) {
            $dr = is_array($d['rate_drift'] ?? null) ? $d['rate_drift'] : null;
            if (!$dr) {
                continue;
            }
            $cur = $d['rate_currency'] ?? null;
            $items[] = [
                'kind' => 'hotel', 'ref' => (int) $d['id'],
                'what' => 'Night ' . (int) $d['day_no'] . ' · ' . trim((string) ($d['hotel_name_raw'] ?? '')) . (!empty($d['room_type_name']) ? ' · ' . $d['room_type_name'] : '') . (!empty($d['meal_plan']) ? ' ' . $d['meal_plan'] : ''),
                'old'  => self::nightFigure(['sgl' => $d['rate_sgl'] ?? null, 'dbl' => $d['rate_dbl'] ?? null, 'tpl' => $d['rate_tpl'] ?? null, 'unit' => $d['rate_unit'] ?? null], $cur),
                'new'  => !empty($dr['none']) ? 'no published rate' . (!empty($dr['why']) ? ' — ' . $dr['why'] : '') : self::nightFigure($dr, $dr['currency'] ?? $cur) . (!empty($dr['plan_name']) ? ' (' . $dr['plan_name'] . ')' : ''),
                'since' => $dr['seen_at'] ?? null, 'source' => 'Hotel Rates',
            ];
        }
        foreach (($it['services'] ?? []) as $s) {
            $dr = is_array($s['rate_drift'] ?? null) ? $s['rate_drift'] : null;
            if (!$dr) {
                continue;
            }
            $m = $dr['moved'] ?? [];
            $old = isset($m['amount_usd']) ? self::money($m['amount_usd'][0], 'USD') : (isset($m['unit_usd']) ? self::money($m['unit_usd'][0], 'USD') . ' each' : (($s['match_state'] ?? '') === 'no_charge' ? 'no charge' : self::money($s['amount_usd'] ?? null, 'USD')));
            $new = isset($m['amount_usd']) ? self::money($m['amount_usd'][1], 'USD') : (isset($m['unit_usd']) ? self::money($m['unit_usd'][1], 'USD') . ' each' : ($dr['text'] ?? 'changed'));
            if (isset($m['match_state']) && !isset($m['amount_usd']) && !isset($m['unit_usd'])) {
                $new = $dr['text'] ?? ('now ' . $m['match_state'][1]);
            }
            $items[] = [
                'kind' => !empty($s['meal_id']) ? 'meal' : 'service', 'ref' => (int) $s['id'],
                'what' => ((int) $s['day_no'] ? 'Day ' . (int) $s['day_no'] : 'Tour') . ' · ' . $s['title'],
                'old' => $old, 'new' => $new, 'since' => $dr['seen_at'] ?? null,
                'source' => !empty($s['meal_id']) ? 'Meal Master' : 'Place to Visit / Paid Activities',
            ];
        }
        foreach (($it['vehicles'] ?? []) as $v) {
            if (!in_array((string) ($v['rate_by'] ?? ''), ['master', 'none'], true)) {
                continue;   // a special rate set for this inquiry is a decision, not a drift
            }
            // tf-stage4d: the figure decides, not the row id — a corrected master row (same id, new figure) is a change too
            if (($v['master_now'] ?? null) === null || round((float) $v['master_now'], 2) === round((float) ($v['km_rate'] ?? 0), 2)) {
                continue;   // no master rate to compare, or the same figure (a new master row at the same figure moves nothing)
            }
            $items[] = [
                'kind' => 'vehicle', 'ref' => (int) $v['id'],
                'what' => 'Vehicle · ' . trim((string) (($v['type'] ?? '') ?: ($v['class_code'] ?? ''))) . ' (rate per km)',
                'old' => self::money($v['km_rate'] ?? null, (string) (($v['currency'] ?? '') ?: 'LKR')), 'new' => self::money($v['master_now'] ?? null, (string) (($v['master_currency'] ?? $v['currency'] ?? '') ?: 'LKR')), 'since' => null, 'source' => 'Transport Rates',
            ];
        }
        $kept = isset($it['usd_rate_kept']) && $it['usd_rate_kept'] !== null && $it['usd_rate_kept'] !== '' ? (float) $it['usd_rate_kept'] : null;
        $now  = $usdNow['rate'] ?? null;
        if ($kept !== null && $now !== null && round($kept, 4) !== round((float) $now, 4)) {
            $items[] = [
                'kind' => 'usd', 'ref' => 0, 'what' => 'USD rate (rupees for one dollar) used to convert transport, crew and rupee costs',
                'old' => number_format($kept, 2) . (!empty($it['usd_rate_kept_date']) ? ' (' . date('j M Y', strtotime((string) $it['usd_rate_kept_date'])) . ')' : ''),
                'new' => number_format((float) $now, 2) . (!empty($usdNow['rate_date']) ? ' (' . date('j M Y', strtotime((string) $usdNow['rate_date'])) . ')' : ''),
                'since' => null, 'source' => 'Daily USD rate',
            ];
        }
        $sig = $items === [] ? null : sha1(json_encode(array_map(static fn ($i) => [$i['kind'], $i['ref'], $i['old'], $i['new']], $items)));
        $reviewed = $sig !== null && (string) ($it['costing_reviewed_sig'] ?? '') === $sig;
        return [
            'items'       => $items,
            'count'       => count($items),
            'sig'         => $sig,
            'reviewed'    => $reviewed,
            'reviewed_at' => $reviewed ? ($it['costing_reviewed_at'] ?? null) : null,
            'reviewed_by' => $reviewed ? ($it['costing_reviewed_by'] ?? null) : null,
            'blocked'     => $items !== [] && !$reviewed,
            'usd_kept'    => $kept, 'usd_kept_date' => $it['usd_rate_kept_date'] ?? null, 'usd_kept_at' => $it['usd_rate_kept_at'] ?? null,
            'usd_now'     => $now, 'usd_now_date' => $usdNow['rate_date'] ?? null,
        ];
    }

    // ------------------------------------------------------------------ who may

    /**
     * Whether this person may recalculate (or keep) this inquiry's figures. $holdsRight: the Recalculate menu right;
     * $any: the System Administrator or a Pricing (Management) holder — any inquiry; otherwise only the handler's own.
     */
    public static function may(bool $holdsRight, bool $any, ?int $userId, array $it): array
    {
        if (!$holdsRight) {
            return ['ok' => false, 'why' => 'Recalculating saved figures is a separate right (Recalculate saved costings), held by the Quotation Desk, Management and the System Administrator.'];
        }
        if ($any) {
            return ['ok' => true, 'why' => null];
        }
        $h = $it['handler_id'] ?? ($it['handler']['id'] ?? null);
        if ($userId !== null && $h !== null && (int) $h === $userId) {
            return ['ok' => true, 'why' => null];
        }
        return ['ok' => false, 'why' => 'This inquiry is handled by ' . (($it['handler_name'] ?? $it['handler']['name'] ?? null) ?: 'nobody yet') . '. A Quotation Desk member recalculates only the inquiries they handle; Management or the System Administrator can recalculate any.'];
    }

    // ------------------------------------------------------------------ the actions

    /**
     * Recalculate ('take'): every moved figure takes the master's current one — hotels, service lines, vehicles, the
     * USD rate. Keep ('keep'): the saved figures stay and the changes shown are marked reviewed (the gate opens until a
     * rate moves again). Either way the figures before and after, who and when go to tf_recalc_log and the history.
     */
    public static function run(IntakeService $take, QuoteService $quotes, int $id, string $mode, ?string $note, string $user, ?string $userName): array
    {
        $db = $take->db();
        $take->assertOpen($id);
        $before = $take->get($id);
        if (!$before) {
            throw new \RuntimeException('No such inquiry.');
        }
        $watch = $before['costing_watch'] ?? ['items' => [], 'sig' => null];
        if ($watch['items'] === []) {
            return ['ok' => true, 'message' => 'Nothing has moved: every saved figure still matches the masters.', 'log_id' => null, 'data' => $before];
        }
        $totalsBefore = self::totals($quotes, $id);
        $now = date('Y-m-d H:i:s');
        if ($mode === 'keep') {
            $db->query('UPDATE tf_intake SET costing_reviewed_sig = ?, costing_reviewed_at = ?, costing_reviewed_by = ? WHERE id = ?', [$watch['sig'], $now, $userName ?: $user, $id]);
            $after = $take->get($id);
            $totalsAfter = $totalsBefore;
            $summary = 'Saved figures kept after review: ' . count($watch['items']) . ' rate change(s) seen and not applied' . ($note ? ' — ' . $note : '');
        } else {
            // hotels: every night Hotel Rates moved away from takes the panel's figure (each one a line in the history)
            $take->checkHotels($id, null, ['take_new' => true]);
            // entrance / meal / activity lines: the master's figure replaces the saved one
            $take->checkServices($id, null, ['take_new' => true]);
            // vehicles on the master rate: today's master row
            foreach ($db->query("SELECT id FROM tf_intake_vehicle WHERE intake_id = ? AND rate_by IN ('master','none')", [$id])->getResultArray() as $r) {
                $take->saveVehicleRate($id, (int) $r['id'], null);
            }
            // the USD rate: the one the costing would use today
            if ($watch['usd_now'] !== null) {
                $db->query('UPDATE tf_intake SET usd_rate_kept = ?, usd_rate_kept_date = ?, usd_rate_kept_at = ? WHERE id = ?', [$watch['usd_now'], $watch['usd_now_date'], $now, $id]);
            }
            $db->query('UPDATE tf_intake SET costing_reviewed_sig = NULL, costing_reviewed_at = NULL, costing_reviewed_by = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $user, $id]);
            $after = $take->get($id);
            $totalsAfter = self::totals($quotes, $id);
            $summary = 'Recalculated on purpose: ' . count($watch['items']) . ' rate change(s) applied' . ($note ? ' — ' . $note : '');
        }
        $logId = self::log($db, $id, $mode === 'keep' ? 'kept' : 'recalculated', $watch['items'], $totalsBefore, $totalsAfter, $note, $user, $userName);
        try {
            $changes = [];
            foreach ($watch['items'] as $i) {
                $changes[] = ['what' => $i['what'], 'from' => $i['old'], 'to' => $i['new']];
            }
            if ($totalsBefore['cost_usd'] !== null || $totalsAfter['cost_usd'] !== null) {
                $changes[] = ['what' => 'Cost USD', 'from' => $totalsBefore['cost_usd'], 'to' => $totalsAfter['cost_usd']];
                $changes[] = ['what' => 'Price USD', 'from' => $totalsBefore['sell_usd'], 'to' => $totalsAfter['sell_usd']];
            }
            $take->activity()->record('intake', $id, $id, null, $mode === 'keep' ? 'rates_kept' : 'recalculated', $summary, $changes);
        } catch (\Throwable $e) {
            // the history line is a record, never a gate
        }
        return ['ok' => true, 'message' => $mode === 'keep' ? 'Saved figures kept. The changes are marked reviewed; Go for Quotation can proceed on the saved figures.' : 'Recalculated. The new figures are on the inquiry; the old and new figures are in the log.', 'log_id' => $logId, 'data' => $after, 'before' => $totalsBefore, 'after' => $totalsAfter];
    }

    /** One deliberate re-price of a night or a vehicle (the existing buttons) goes to the same log. */
    public static function logManual($db, int $id, string $what, ?string $old, ?string $new, string $user, ?string $userName): void
    {
        try {
            self::log($db, $id, 'recalculated', [['kind' => 'manual', 'ref' => 0, 'what' => $what, 'old' => $old, 'new' => $new, 'since' => null, 'source' => 'manual']], ['cost_usd' => null, 'sell_usd' => null, 'usd_rate' => null], ['cost_usd' => null, 'sell_usd' => null, 'usd_rate' => null], null, $user, $userName);
        } catch (\Throwable $e) {
        }
    }

    private static function totals(QuoteService $quotes, int $id): array
    {
        try {
            $c = $quotes->costing($id);
            return ['cost_usd' => $c['cost_usd'] ?? null, 'sell_usd' => $c['markup']['sell_usd'] ?? null, 'usd_rate' => $c['usd_rate'] ?? null, 'blockers' => count($c['blockers'] ?? [])];
        } catch (\Throwable $e) {
            return ['cost_usd' => null, 'sell_usd' => null, 'usd_rate' => null, 'blockers' => null];
        }
    }

    private static function log($db, int $id, string $action, array $items, array $before, array $after, ?string $note, string $user, ?string $userName): int
    {
        $db->query('INSERT INTO tf_recalc_log (intake_id, action, items_json, cost_before, cost_after, sell_before, sell_after, usd_before, usd_after, note, user, user_name, at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $id, $action, json_encode($items, JSON_UNESCAPED_UNICODE), $before['cost_usd'], $after['cost_usd'], $before['sell_usd'], $after['sell_usd'], $before['usd_rate'], $after['usd_rate'],
            $note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 255) : null, $user, $userName, date('Y-m-d H:i:s'),
        ]);
        return (int) ($db->query('SELECT LAST_INSERT_ID() AS id')->getRowArray()['id'] ?? 0);
    }

    /** The log of one inquiry, newest first. */
    public static function history($db, int $id, int $limit = 50): array
    {
        if (!self::installed($db)) {
            return [];
        }
        $rows = $db->query('SELECT * FROM tf_recalc_log WHERE intake_id = ? ORDER BY id DESC LIMIT ' . (int) $limit, [$id])->getResultArray();
        foreach ($rows as &$r) {
            $r['items'] = json_decode((string) $r['items_json'], true) ?: [];
            unset($r['items_json']);
        }
        return $rows;
    }

    // ------------------------------------------------------------------ the baseline at install (schema 53)

    /**
     * The open inquiries keep the figures they hold today: nothing is recalculated. Each gets the USD rate its costing
     * uses today written down as the kept rate, and one 'baseline' line in the log with a snapshot of its saved figures,
     * so that the figures before any later recalculation are on record.
     */
    public static function baseline($db): string
    {
        if (!self::installed($db)) {
            return 'Saved-costing baseline not written: the columns are missing.';
        }
        $n = 0;
        $open = $db->query("SELECT * FROM tf_intake WHERE archived_at IS NULL AND status NOT IN ('confirmed','cancelled') AND usd_rate_kept IS NULL")->getResultArray();
        foreach ($open as $it) {
            $id = (int) $it['id'];
            $usd = ['rate' => null, 'rate_date' => null];
            try {
                $row = !empty($it['arrival_date'])
                    ? $db->query('SELECT rate, rate_date FROM daily_usd_rate WHERE rate_date <= ? ORDER BY rate_date DESC LIMIT 1', [$it['arrival_date']])->getRowArray()
                    : null;
                $row ??= $db->query('SELECT rate, rate_date FROM daily_usd_rate ORDER BY rate_date DESC LIMIT 1')->getRowArray();
                if ($row && (float) $row['rate'] > 0) {
                    $usd = ['rate' => (float) $row['rate'], 'rate_date' => (string) $row['rate_date']];
                }
            } catch (\Throwable $e) {
            }
            $snap = [
                'nights'   => $db->query('SELECT day_no, hotel_name_raw, room_type_name, meal_plan, rate_plan_name, rate_currency, rate_sgl, rate_dbl, rate_tpl, rate_unit FROM tf_intake_day WHERE intake_id = ? ORDER BY day_no', [$id])->getResultArray(),
                'services' => $db->query('SELECT day_no, title, match_state, qty, unit_usd, amount_usd FROM tf_intake_service WHERE intake_id = ? ORDER BY day_no, sort_order, id', [$id])->getResultArray(),
                'vehicles' => $db->query('SELECT class_code, km_rate, rate_by FROM tf_intake_vehicle WHERE intake_id = ?', [$id])->getResultArray(),
                'usd'      => $usd,
            ];
            if ($usd['rate'] !== null) {
                $db->query('UPDATE tf_intake SET usd_rate_kept = ?, usd_rate_kept_date = ?, usd_rate_kept_at = ? WHERE id = ?', [$usd['rate'], $usd['rate_date'], date('Y-m-d H:i:s'), $id]);
            }
            $db->query('INSERT INTO tf_recalc_log (intake_id, action, items_json, cost_before, cost_after, sell_before, sell_after, usd_before, usd_after, note, user, user_name, at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
                $id, 'baseline', json_encode($snap, JSON_UNESCAPED_UNICODE), null, null, null, null, $usd['rate'], $usd['rate'], 'Schema 53: the saved figures as they stood when saved costings became protected; nothing recalculated.', 'installer', 'Installer', date('Y-m-d H:i:s'),
            ]);
            $n++;
        }
        return 'Saved costings protected (schema 53): ' . $n . ' open inquir' . ($n === 1 ? 'y keeps' : 'ies keep') . ' the figures held today as the baseline; nothing was recalculated.';
    }
}
