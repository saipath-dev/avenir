<?php

namespace App\Libraries\TourFlow;

/**
 * The history of a tour: who changed what, and when.
 *
 * Ownership and editing are two different things. The handling agent is whose tour it
 * is; this class records who touched it. A screen takes a snapshot of the record before
 * an edit and one after, and the difference — field by field, night by night, name by
 * name — is written to tf_activity in plain words ("Night 3 · hotel: Cinnamon Lodge →
 * Heritance Kandalama"), with the person and the time. Nothing that did not change is
 * written; an edit that changed nothing leaves no line.
 *
 * The desks' own trail (tf_tour_event: requested, confirmed, voucher, problem, and so
 * on) stays exactly as it is. A tour's history reads both, newest first.
 */
final class Activity
{
    private $db;
    private string $user;
    private ?int $userId;
    private ?string $name;

    /** Beyond this many lines the change list is cut, with a count of the rest. */
    public const MAX_LINES = 80;

    public function __construct($db, string $user = '', ?int $userId = null, ?string $name = null)
    {
        $this->db     = $db;
        $this->user   = $user;
        $this->userId = $userId;
        $this->name   = $name;
    }

    private static function cut($v, int $len): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        return $v === '' ? null : mb_substr($v, 0, $len);
    }

    // =====================================================================
    //  Writing
    // =====================================================================

    /**
     * One line of history.
     *
     * @param array<int, array{what:string, from:mixed, to:mixed}>|null $changes
     */
    public function record(string $entity, int $entityId, ?int $intakeId, ?int $tourId, string $action, ?string $summary, ?array $changes = null): int
    {
        $this->db->query(
            'INSERT INTO tf_activity (entity, entity_id, intake_id, tour_id, action, summary, changes_json, by_user_id, by_user, by_name, `at`) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [
                mb_substr($entity, 0, 30), $entityId, $intakeId, $tourId, mb_substr($action, 0, 40), self::cut($summary, 500),
                $changes ? json_encode(array_values($changes), JSON_UNESCAPED_UNICODE) : null,
                $this->userId, self::cut($this->user, 100), self::cut($this->name ?? $this->user, 190), date('Y-m-d H:i:s'),
            ]
        );
        return (int) $this->db->insertID();
    }

    /**
     * A change to a master (an allowance, a per-kilometre rate, a bata, an excursion rate),
     * with what it was and what it is now — from any library, whether or not this install
     * has the log yet. Nothing changed, nothing written. Never throws: a master's save is
     * not held up by its own history.
     *
     * @param array<int, array{what:string, from:mixed, to:mixed}> $changes
     */
    public static function master($db, string $user, string $which, int $entityId, string $summary, array $changes, ?int $userId = null, ?string $name = null): void
    {
        try {
            $changes = array_values(array_filter($changes, static fn ($c) => self::norm($c['from'] ?? null) !== self::norm($c['to'] ?? null)));
            if (!$changes || !$db->query("SHOW TABLES LIKE 'tf_activity'")->getResultArray()) {
                return;
            }
            (new self($db, $user, $userId, $name))->record('master', $entityId, null, null, mb_substr($which, 0, 40), $summary, $changes);
        } catch (\Throwable $e) {
            // the line is a record, never a gate
        }
    }

    /** The log as CSV, the same filters as recent(): when, who, what, the summary, and each change on its own line. */
    public function export(array $f = [], int $limit = 1000): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['when', 'who', 'inquiry', 'entity', 'action', 'summary', 'what', 'from', 'to']);
        foreach ($this->recent($f, $limit) as $r) {
            $changes = $r['changes_total'] > count($r['changes']) ? $this->allChanges((int) $r['id']) : $r['changes'];
            if (!$changes) {
                fputcsv($out, [$r['at'], $r['by'], $r['inquiry_no'] ?? '', $r['entity'], $r['action'], $r['summary'], '', '', '']);
                continue;
            }
            foreach ($changes as $c) {
                fputcsv($out, [$r['at'], $r['by'], $r['inquiry_no'] ?? '', $r['entity'], $r['action'], $r['summary'], $c['what'] ?? '', $c['from'] ?? '', $c['to'] ?? '']);
            }
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    private function allChanges(int $id): array
    {
        $r = $this->db->query('SELECT changes_json FROM tf_activity WHERE id = ?', [$id])->getRowArray();
        return $r && $r['changes_json'] ? (json_decode((string) $r['changes_json'], true) ?: []) : [];
    }

    /**
     * Write the difference between two snapshots, if there is one. Returns the line's id,
     * or null when nothing changed. A note (why) is put in front of the summary.
     */
    public function logDiff(string $entity, int $entityId, ?int $intakeId, ?int $tourId, string $action, ?array $before, ?array $after, ?string $note = null): ?int
    {
        $changes = self::diff($before, $after);
        if (!$changes) {
            return null;
        }
        $summary = self::summarise($changes);
        if ($note !== null && trim($note) !== '') {
            $summary = trim($note) . ' — ' . $summary;
        }
        return $this->record($entity, $entityId, $intakeId, $tourId, $action, $summary, $changes);
    }

    // =====================================================================
    //  Snapshots
    // =====================================================================

    /**
     * The inquiry as a flat picture: its own fields, then every child row keyed by id.
     * Read straight from the tables — light, and independent of what the panel derives.
     *
     * @return array{fields: array<string, mixed>, children: array<string, array<int, array<string, mixed>>>}|null
     */
    public function snapshotIntake(int $id): ?array
    {
        $it = $this->db->query(
            'SELECT status, agent_acc, agent_name, agent_email, country_iso, market_code, tour_name, arrival_date, departure_date, nights, pax_total, pax_adults, pax_children,
                    rooms_note, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl, hotel_class, arrival_flight, departure_flight, note, markup_pct, handler_name, archived_at, archive_note,
                    CASE WHEN source_text IS NULL OR source_text = \'\' THEN 0 ELSE 1 END AS has_email
             FROM tf_intake WHERE id = ?',
            [$id]
        )->getRowArray();
        if (!$it) {
            return null;
        }
        // schema 20: the follow-up date, on installs that have it
        try {
            $fu = $this->db->query('SELECT follow_up_on, follow_up_note FROM tf_intake WHERE id = ?', [$id])->getRowArray();
            $it['follow_up_on']   = $fu['follow_up_on'] ?? null;
            $it['follow_up_note'] = $fu['follow_up_note'] ?? null;
        } catch (\Throwable $e) {
            // an older install: no follow-up to picture
        }
        $fields = [];
        foreach (self::intakeFieldLabels() as $col => $label) {
            $fields[$label] = $it[$col] ?? null;
        }
        $children = [];
        foreach (self::intakeGroups() as $key => $g) {
            $rows = [];
            try {
                foreach ($this->db->query($g['sql'], [$id])->getResultArray() as $r) {
                    $rows[self::rowKey($g, $r)] = $r;
                }
            } catch (\Throwable $e) {
                // a table this install does not have yet is simply not part of the picture
            }
            $children[$key] = $rows;
        }
        return ['fields' => $fields, 'children' => $children];
    }

    /**
     * What makes a row the same row between two snapshots. The planner rebuilds vehicles,
     * crew, legs and accommodation with fresh ids on every change of the party, so those are
     * known by what they are (the vehicle's place in the list, the crew role, the leg's day
     * and order) — otherwise every edit would read as everything removed and put back.
     */
    private static function rowKey(array $cfg, array $row): string
    {
        $cols = $cfg['key'] ?? ['id'];
        $parts = [];
        foreach ($cols as $c) {
            $parts[] = (string) ($row[$c] ?? '');
        }
        return implode('|', $parts);
    }

    /** A hotel card: the stay as the desk sees it. */
    public function snapshotHotelJob(int $jobId): ?array
    {
        $j = $this->db->query('SELECT hotel_name, area, check_in, check_out, nights, rooms, rooms_note, meal_plan, pax, special_note, crew_rooms, status, note, archived_at, archive_note FROM tf_hotel_job WHERE id = ?', [$jobId])->getRowArray();
        if (!$j) {
            return null;
        }
        $labels = ['hotel_name' => 'hotel', 'area' => 'area', 'check_in' => 'check-in', 'check_out' => 'check-out', 'nights' => 'nights', 'rooms' => 'rooms', 'rooms_note' => 'rooms note', 'meal_plan' => 'meal plan', 'pax' => 'party', 'special_note' => 'special note', 'crew_rooms' => 'crew rooms', 'status' => 'status', 'note' => 'desk note', 'archived_at' => 'archived', 'archive_note' => 'archive note'];
        $fields = [];
        foreach ($labels as $col => $label) {
            $fields[$label] = $j[$col];
        }
        return ['fields' => $fields, 'children' => []];
    }

    /** An activity or ticket line on the desk (schema 20). */
    public function snapshotServiceJob(int $jobId): ?array
    {
        try {
            $j = $this->db->query('SELECT title, kind, day_no, on_date, pax, status, confirmation_ref, confirmed_with, problem_code, problem_note, request_note, note, archived_at, archive_note FROM tf_service_job WHERE id = ?', [$jobId])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$j) {
            return null;
        }
        $labels = ['title' => 'what', 'kind' => 'kind', 'day_no' => 'day', 'on_date' => 'date', 'pax' => 'party', 'status' => 'status', 'confirmation_ref' => 'reference', 'confirmed_with' => 'confirmed with', 'problem_code' => 'problem', 'problem_note' => 'problem note', 'request_note' => 'note on the request', 'note' => 'desk note', 'archived_at' => 'archived', 'archive_note' => 'archive note'];
        $fields = [];
        foreach ($labels as $col => $label) {
            $fields[$label] = $j[$col];
        }
        return ['fields' => $fields, 'children' => []];
    }

    /** A transport card: the vehicle, its legs, its crew and its documents. */
    public function snapshotTransportJob(int $jobId): ?array
    {
        $j = $this->db->query('SELECT vehicle_type, qty, days, km_total, extra_km_total, vehicle_reg, status, note, archived_at, archive_note FROM tf_transport_job WHERE id = ?', [$jobId])->getRowArray();
        if (!$j) {
            return null;
        }
        $labels = ['vehicle_type' => 'vehicle', 'qty' => 'how many', 'days' => 'days', 'km_total' => 'km', 'extra_km_total' => 'local km', 'vehicle_reg' => 'registration', 'status' => 'status', 'note' => 'desk note', 'archived_at' => 'archived', 'archive_note' => 'archive note'];
        $fields = [];
        foreach ($labels as $col => $label) {
            $fields[$label] = $j[$col];
        }
        $children = ['legs' => [], 'crew' => [], 'docs' => []];
        foreach ($this->db->query('SELECT id, day_no, seq, kind, from_place, to_place, km, extra_km, night_hotel, note FROM tf_transport_leg WHERE job_id = ? ORDER BY day_no, seq, id', [$jobId])->getResultArray() as $r) {
            $children['legs'][(int) $r['id']] = $r;
        }
        // schema 24: the days each person works and a replacement are part of the record
        $dated = false;
        try {
            $dated = (bool) $this->db->query("SHOW COLUMNS FROM `tf_transport_crew` LIKE 'date_from'")->getResultArray();
        } catch (\Throwable $e) {
            $dated = false;
        }
        foreach ($this->db->query('SELECT id, role_name, days, bata, supplier_acc, person_name, person_bata, status, note' . ($dated ? ', date_from, date_to, replaced_note' : '') . ' FROM tf_transport_crew WHERE job_id = ? ORDER BY id', [$jobId])->getResultArray() as $r) {
            $children['crew'][(int) $r['id']] = $r;
        }
        foreach ($this->db->query('SELECT id, doc_name, phase, state, note FROM tf_transport_doc WHERE job_id = ? ORDER BY id', [$jobId])->getResultArray() as $r) {
            $children['docs'][(int) $r['id']] = $r;
        }
        return ['fields' => $fields, 'children' => $children];
    }

    /** tf_intake column => the word the history uses for it. */
    public static function intakeFieldLabels(): array
    {
        return [
            'status' => 'status', 'agent_acc' => 'agent code', 'agent_name' => 'agent', 'agent_email' => 'agent email', 'country_iso' => 'country', 'market_code' => 'market',
            'tour_name' => 'group / tour name', 'arrival_date' => 'arrival', 'departure_date' => 'departure', 'nights' => 'nights', 'pax_total' => 'passengers', 'pax_adults' => 'adults', 'pax_children' => 'children',
            'rooms_note' => 'rooms', 'rooms_sgl' => 'single rooms', 'rooms_dbl' => 'double rooms', 'rooms_twn' => 'twin rooms', 'rooms_tpl' => 'triple rooms', 'hotel_class' => 'hotel class',
            'arrival_flight' => 'arrival flight', 'departure_flight' => 'departure flight', 'note' => 'note', 'markup_pct' => 'markup %', 'handler_name' => 'handling agent',
            'archived_at' => 'archived', 'archive_note' => 'archive note', 'has_email' => 'email attached',
            'follow_up_on' => 'follow-up date', 'follow_up_note' => 'follow-up note',
        ];
    }

    /**
     * The child rows of an inquiry: how to read them, what to call one, which columns count.
     * `label` names a row ("Night 3", "Passenger Takao/Rio Ms"); `fields` says which columns
     * are compared and what to call each; `summary` picks the columns that describe a row
     * that was added or removed.
     */
    public static function intakeGroups(): array
    {
        return [
            'days' => [
                'sql'     => 'SELECT id, day_no, day_date, area, hotel_name_raw, meal_plan, rooms, room_type_name, rate_plan_name, narrative, special_note FROM tf_intake_day WHERE intake_id = ? ORDER BY day_no, id',
                'label'   => ['Night', 'day_no'],
                'fields'  => ['day_date' => 'date', 'area' => 'area', 'hotel_name_raw' => 'hotel', 'meal_plan' => 'meal plan', 'rooms' => 'rooms', 'room_type_name' => 'room type', 'rate_plan_name' => 'rate plan', 'narrative' => 'narrative', 'special_note' => 'special note'],
                'summary' => ['hotel_name_raw', 'area', 'meal_plan', 'room_type_name'],
            ],
            'passengers' => [
                'sql'     => 'SELECT id, full_name, room_type, note, group_id FROM tf_intake_pax WHERE intake_id = ? ORDER BY sort_order, id',
                'label'   => ['Passenger', 'full_name'],
                'fields'  => ['full_name' => 'name', 'room_type' => 'room type', 'note' => 'note', 'group_id' => 'group'],
                'summary' => ['full_name', 'room_type'],
            ],
            'requests' => [
                'sql'     => 'SELECT id, kind, title, status, answer FROM tf_intake_request WHERE intake_id = ? ORDER BY sort_order, id',
                'label'   => ['Request', 'title'],
                'fields'  => ['kind' => 'kind', 'title' => 'title', 'status' => 'status', 'answer' => 'answer'],
                'summary' => ['title', 'status'],
            ],
            'services' => [
                'sql'     => 'SELECT id, day_no, kind, title, qty, pay_mode, amount_usd, special_note, description FROM tf_intake_service WHERE intake_id = ? ORDER BY day_no, sort_order, id',
                'label'   => ['Service', 'title', 'day_no'],
                'fields'  => ['day_no' => 'day', 'kind' => 'kind', 'title' => 'title', 'qty' => 'quantity', 'pay_mode' => 'settled by', 'amount_usd' => 'amount USD', 'special_note' => 'special note', 'description' => 'description'],
                'summary' => ['title', 'day_no', 'pay_mode'],
            ],
            'vehicles' => [
                'sql'     => 'SELECT id, sort_order, label, type, class_code, qty, group_id, day_from, day_to, km_rate, rate_by, rate_note, chosen FROM tf_intake_vehicle WHERE intake_id = ? ORDER BY sort_order, id',
                'key'     => ['sort_order'],
                'label'   => ['Vehicle', 'type'],
                'fields'  => ['label' => 'label', 'type' => 'size', 'class_code' => 'class', 'qty' => 'how many', 'group_id' => 'group', 'day_from' => 'from night', 'day_to' => 'to night', 'km_rate' => 'rate per km', 'rate_by' => 'rate basis', 'rate_note' => 'rate note', 'chosen' => 'chosen'],
                'summary' => ['type', 'qty', 'km_rate'],
            ],
            'crew' => [
                'sql'     => 'SELECT id, role_code, role_name, days, bata, currency, supplier_acc, person_name, person_bata, chosen, note FROM tf_intake_crew WHERE intake_id = ? ORDER BY sort_order, id',
                'key'     => ['role_code'],
                'label'   => ['Crew', 'role_name'],
                // the transport card's crew rows share this group: status, the days worked and a
                // replacement (schema 24) are theirs; an inquiry's crew line simply has none
                'fields'  => ['days' => 'days', 'bata' => 'bata', 'currency' => 'currency', 'supplier_acc' => 'supplier', 'person_name' => 'person', 'person_bata' => 'agreed bata', 'chosen' => 'chosen', 'note' => 'note',
                              'status' => 'status', 'date_from' => 'first day', 'date_to' => 'last day', 'replaced_note' => 'replaced because'],
                'summary' => ['role_name', 'days', 'bata'],
            ],
            // legs the planner works out by itself are consequences, not edits: only a leg a person
            // typed or corrected (origin human — a distance typed over the history, a leg added by hand) is history
            'sectors' => [
                'sql'     => 'SELECT id, day_no, seq, vehicle_seq, kind, from_place, to_place, km, extra_km, note, waived_by FROM tf_intake_sector WHERE intake_id = ? AND origin = \'human\' ORDER BY vehicle_seq, day_no, seq, id',
                'key'     => ['vehicle_seq', 'day_no', 'seq'],
                'label'   => ['Leg', 'from_place', 'to_place', 'day_no'],
                'fields'  => ['day_no' => 'day', 'vehicle_seq' => 'vehicle', 'kind' => 'kind', 'from_place' => 'from', 'to_place' => 'to', 'km' => 'km', 'extra_km' => 'local km', 'note' => 'note', 'waived_by' => 'waived by'],
                'summary' => ['from_place', 'to_place', 'km'],
            ],
            'groups' => [
                'sql'     => 'SELECT id, code, name, pax, day_from, day_to, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl, note FROM tf_intake_group WHERE intake_id = ? ORDER BY sort_order, id',
                'label'   => ['Group', 'name'],
                'fields'  => ['name' => 'name', 'pax' => 'passengers', 'day_from' => 'first night', 'day_to' => 'last night', 'rooms_sgl' => 'single', 'rooms_dbl' => 'double', 'rooms_twn' => 'twin', 'rooms_tpl' => 'triple', 'note' => 'note'],
                'summary' => ['name', 'pax'],
            ],
            'excursions' => [
                'sql'     => 'SELECT id, day_no, name, group_id, qty, rate, amount, description, note FROM tf_intake_excursion WHERE intake_id = ? ORDER BY day_no, sort_order, id',
                'label'   => ['Excursion', 'name', 'day_no'],
                'fields'  => ['day_no' => 'day', 'group_id' => 'group', 'qty' => 'quantity', 'rate' => 'rate', 'amount' => 'amount', 'description' => 'description', 'note' => 'note'],
                'summary' => ['name', 'day_no', 'amount'],
            ],
            'accom' => [
                'sql'     => 'SELECT id, kind, required, nights, rate, currency, note FROM tf_intake_accom WHERE intake_id = ? AND day_no = 0 ORDER BY kind',
                'key'     => ['kind'],
                'label'   => ['Accommodation', 'kind'],
                'fields'  => ['required' => 'needed', 'nights' => 'nights', 'rate' => 'rate', 'currency' => 'currency', 'note' => 'note'],
                'summary' => ['kind', 'required', 'nights'],
            ],
            'vehicle_days' => [
                'sql'     => 'SELECT id, vehicle_seq, day_no, retained, note FROM tf_intake_vehicle_day WHERE intake_id = ? ORDER BY vehicle_seq, day_no',
                'key'     => ['vehicle_seq', 'day_no'],
                'label'   => ['Vehicle day', 'day_no'],
                'fields'  => ['retained' => 'retained', 'note' => 'note'],
                'summary' => ['vehicle_seq', 'day_no', 'retained'],
            ],
            'quotes' => [
                'sql'     => 'SELECT id, version, status, sell_usd, valid_until FROM tf_quote WHERE intake_id = ? ORDER BY version',
                'key'     => ['version'],
                'label'   => ['Quotation v', 'version'],
                'fields'  => ['status' => 'status', 'sell_usd' => 'price USD', 'valid_until' => 'valid until'],
                'summary' => ['status', 'sell_usd'],
            ],
        ];
    }

    // =====================================================================
    //  Comparing
    // =====================================================================

    /**
     * What moved between two snapshots, in plain words.
     *
     * @return array<int, array{what:string, from:mixed, to:mixed}>
     */
    public static function diff(?array $before, ?array $after): array
    {
        $out = [];
        if ($before === null && $after === null) {
            return $out;
        }
        $bf = $before['fields'] ?? [];
        $af = $after['fields'] ?? [];
        foreach (array_unique(array_merge(array_keys($bf), array_keys($af))) as $k) {
            $x = self::norm($bf[$k] ?? null);
            $y = self::norm($af[$k] ?? null);
            if ($x !== $y) {
                $out[] = ['what' => $k, 'from' => $x, 'to' => $y];
            }
        }
        $groups = self::intakeGroups();
        $bc = $before['children'] ?? [];
        $ac = $after['children'] ?? [];
        foreach (array_unique(array_merge(array_keys($bc), array_keys($ac))) as $g) {
            $cfg = $groups[$g] ?? self::genericGroup($g, $bc[$g] ?? [], $ac[$g] ?? []);
            $brows = $bc[$g] ?? [];
            $arows = $ac[$g] ?? [];
            foreach ($brows as $id => $row) {
                if (!isset($arows[$id])) {
                    $out[] = ['what' => self::rowLabel($cfg, $row) . ' removed', 'from' => self::rowSummary($cfg, $row), 'to' => null];
                }
            }
            foreach ($arows as $id => $row) {
                if (!isset($brows[$id])) {
                    $out[] = ['what' => self::rowLabel($cfg, $row) . ' added', 'from' => null, 'to' => self::rowSummary($cfg, $row)];
                    continue;
                }
                $old = $brows[$id];
                foreach ($cfg['fields'] as $col => $label) {
                    $x = self::norm($old[$col] ?? null);
                    $y = self::norm($row[$col] ?? null);
                    if ($x !== $y) {
                        $out[] = ['what' => self::rowLabel($cfg, $row) . ' · ' . $label, 'from' => $x, 'to' => $y];
                    }
                }
            }
        }
        return $out;
    }

    /** A group the config does not know (a card's legs, say): every column counts, the first text column names the row. */
    private static function genericGroup(string $g, array $b, array $a): array
    {
        $sample = $a ? reset($a) : ($b ? reset($b) : []);
        $fields = [];
        foreach (array_keys($sample ?: []) as $col) {
            if ($col !== 'id') {
                $fields[$col] = str_replace('_', ' ', $col);
            }
        }
        $name = ucfirst(rtrim($g, 's'));
        $nameCol = null;
        foreach (['doc_name', 'role_name', 'from_place', 'name', 'title'] as $c) {
            if (array_key_exists($c, $sample ?: [])) {
                $nameCol = $c;
                break;
            }
        }
        $label = $nameCol ? [$name, $nameCol] : [$name, 'id'];
        if ($g === 'legs') {
            $label = ['Leg', 'from_place', 'to_place', 'day_no'];
        }
        return ['label' => $label, 'fields' => $fields, 'summary' => array_slice(array_keys($fields), 0, 3)];
    }

    private static function rowLabel(array $cfg, array $row): string
    {
        $l = $cfg['label'];
        $word = array_shift($l);
        // a row is named by its own words, cut short: a service typed in as a whole sentence is still one line
        $name = static function ($v): string {
            $v = trim((string) ($v ?? ''));
            if ($v === '') {
                return '?';
            }
            return mb_strlen($v) > 48 ? mb_substr($v, 0, 45) . '…' : $v;
        };
        if (count($l) === 3) {   // a leg: "Leg Colombo → Kandy (day 2)"
            return $word . ' ' . $name($row[$l[0]] ?? '') . ' → ' . $name($row[$l[1]] ?? '') . ' (day ' . ($row[$l[2]] ?? '?') . ')';
        }
        if (count($l) === 2) {   // "Service Sigiriya rock (day 3)"
            return $word . ' ' . $name($row[$l[0]] ?? '') . ' (day ' . ($row[$l[1]] ?? '?') . ')';
        }
        $v = $row[$l[0]] ?? '';
        return $word === 'Quotation v' ? 'Quotation v' . $v : trim($word . ' ' . $name($v));
    }

    private static function rowSummary(array $cfg, array $row): string
    {
        $parts = [];
        foreach ($cfg['summary'] as $col) {
            $v = $row[$col] ?? null;
            if ($v !== null && trim((string) $v) !== '') {
                $v = trim((string) $v);
                $parts[] = mb_strlen($v) > 80 ? mb_substr($v, 0, 77) . '…' : $v;
            }
        }
        return implode(' · ', $parts);
    }

    /** Numbers compare as numbers, blanks as null, everything else as trimmed text. */
    private static function norm($v)
    {
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v ? 1 : 0;
        }
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        if (is_numeric($s)) {
            $f = (float) $s;
            return floor($f) == $f && abs($f) < 1e15 ? (int) $f : $f;
        }
        return $s;
    }

    /** The first few changes as one line, for the list. */
    public static function summarise(array $changes): string
    {
        $parts = [];
        foreach (array_slice($changes, 0, 3) as $c) {
            $from = $c['from'];
            $to   = $c['to'];
            if ($from === null && $to !== null) {
                $parts[] = $c['what'] . ': ' . self::short($to);
            } elseif ($to === null && $from !== null) {
                $parts[] = $c['what'] . ': ' . self::short($from) . ' → (cleared)';
            } else {
                $parts[] = $c['what'] . ': ' . self::short($from) . ' → ' . self::short($to);
            }
        }
        $more = count($changes) - 3;
        return implode('; ', $parts) . ($more > 0 ? " (+{$more} more)" : '');
    }

    private static function short($v): string
    {
        $s = (string) $v;
        return mb_strlen($s) > 60 ? mb_substr($s, 0, 57) . '…' : $s;
    }

    // =====================================================================
    //  Reading
    // =====================================================================

    /**
     * A tour's history, newest first: every line written here and every line of the desks'
     * trail, in one list. Each entry says who, when, what, and carries the changes.
     */
    public function timeline(int $intakeId, ?int $tourId = null, int $limit = 200): array
    {
        $out = [];
        foreach ($this->db->query('SELECT * FROM tf_activity WHERE intake_id = ? ORDER BY id DESC LIMIT ' . (int) $limit, [$intakeId])->getResultArray() as $r) {
            $changes = $r['changes_json'] ? (json_decode((string) $r['changes_json'], true) ?: []) : [];
            $out[] = [
                'kind' => 'activity', 'id' => (int) $r['id'], 'entity' => $r['entity'], 'entity_id' => (int) $r['entity_id'], 'lane' => self::laneOf((string) $r['entity']),
                'action' => $r['action'], 'summary' => $r['summary'], 'changes' => array_slice($changes, 0, self::MAX_LINES), 'changes_total' => count($changes),
                'by' => $r['by_name'] ?: $r['by_user'], 'by_user' => $r['by_user'], 'at' => $r['at'],
            ];
        }
        try {
            $ev = $tourId
                ? $this->db->query('SELECT * FROM tf_tour_event WHERE tour_id = ? OR (tour_id IS NULL AND intake_id = ?) ORDER BY id DESC LIMIT ' . (int) $limit, [$tourId, $intakeId])->getResultArray()
                : $this->db->query('SELECT * FROM tf_tour_event WHERE intake_id = ? ORDER BY id DESC LIMIT ' . (int) $limit, [$intakeId])->getResultArray();
            foreach ($ev as $e) {
                $out[] = [
                    'kind' => 'event', 'id' => (int) $e['id'], 'entity' => $e['lane'], 'entity_id' => (int) ($e['job_id'] ?? 0), 'lane' => $e['lane'],
                    'action' => $e['event'], 'summary' => trim(($e['to_status'] ? '→ ' . str_replace('_', ' ', (string) $e['to_status']) : '') . ($e['note'] ? ' — ' . $e['note'] : '')) ?: null,
                    'changes' => [], 'changes_total' => 0, 'by' => $e['by'], 'by_user' => $e['by'], 'at' => $e['at'],
                ];
            }
        } catch (\Throwable $e) {
            // no trail table on this install: the activity lines are the whole history
        }
        // an archive or restore is written to both trails (the card's own, and the history that
        // names the person): the desk line is dropped where the history line says the same thing
        $seen = [];
        foreach ($out as $r) {
            if ($r['kind'] === 'activity') {
                $seen[$r['lane'] . '|' . $r['entity_id'] . '|' . $r['action'] . '|' . $r['at']] = true;
            }
        }
        $out = array_values(array_filter($out, static fn ($r) => $r['kind'] !== 'event' || !isset($seen[$r['lane'] . '|' . $r['entity_id'] . '|' . $r['action'] . '|' . $r['at']])));
        usort($out, static fn ($a, $b) => [$b['at'], $b['id']] <=> [$a['at'], $a['id']]);
        return array_slice($out, 0, $limit);
    }

    private static function laneOf(string $entity): string
    {
        return ['inquiry' => 'quotation', 'quote' => 'quotation', 'hotel_job' => 'hotel', 'transport_job' => 'transport', 'tour' => 'tour', 'hotel' => 'hotel'][$entity] ?? 'tour';
    }

    /**
     * The log across every tour, for the Manage screen: newest first, filtered by who did it
     * (user id or sign-in name), by the handling agent of the inquiry, by entity or action.
     */
    public function recent(array $f = [], int $limit = 200): array
    {
        $where = [];
        $binds = [];
        if (!empty($f['by'])) {
            $where[] = '(a.by_user = ? OR a.by_name = ? OR a.by_user_id = ?)';
            array_push($binds, (string) $f['by'], (string) $f['by'], (int) $f['by']);
        }
        if (!empty($f['handler'])) {
            $where[] = '(i.handler_id = ? OR i.handler_name = ?)';
            array_push($binds, (int) $f['handler'], (string) $f['handler']);
        }
        if (!empty($f['entity'])) {
            $where[] = 'a.entity = ?';
            $binds[] = (string) $f['entity'];
        }
        if (!empty($f['action'])) {
            $where[] = 'a.action = ?';
            $binds[] = (string) $f['action'];
        }
        if (!empty($f['from'])) {
            $where[] = 'a.at >= ?';
            $binds[] = (string) $f['from'] . ' 00:00:00';
        }
        if (!empty($f['to'])) {
            $where[] = 'a.at <= ?';
            $binds[] = (string) $f['to'] . ' 23:59:59';
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(i.inquiry_no LIKE ? OR i.tour_name LIKE ? OR i.agent_name LIKE ? OR a.summary LIKE ?)';
            array_push($binds, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
        }
        $rows = $this->db->query(
            'SELECT a.*, i.inquiry_no, i.tour_name, i.agent_name, i.handler_name FROM tf_activity a LEFT JOIN tf_intake i ON i.id = a.intake_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY a.id DESC LIMIT ' . max(1, min(1000, $limit)),
            $binds
        )->getResultArray();
        foreach ($rows as &$r) {
            $changes = $r['changes_json'] ? (json_decode((string) $r['changes_json'], true) ?: []) : [];
            $r['changes'] = array_slice($changes, 0, self::MAX_LINES);
            $r['changes_total'] = count($changes);
            $r['by'] = $r['by_name'] ?: $r['by_user'];
            unset($r['changes_json']);
        }
        unset($r);
        return $rows;
    }
}
