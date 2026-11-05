<?php

namespace App\Libraries\TourFlow;

/**
 * Edit Requests (schema 33): a colleague with Edit Request Authority says what needs to change on an
 * inquiry — the words as they stand, and what they should say now — and TourFlow finds the working
 * values, shows Current → New with everything that follows from it, and changes them only when the
 * colleague confirms.
 *
 * The original email is never touched: an Edit Request amends the working inquiry, through the same
 * service calls a person's own edits go through, so every rule those follow still holds — the dates
 * decide the nights, a hotel night takes only a published contract rate, a figure a person typed stays
 * theirs, a closed inquiry is not edited, an issued quotation keeps what it was frozen with.
 *
 * The preview is the change itself, made inside a database transaction that is then undone: what it
 * shows is what Confirm does, worked out by the same code. Confirm makes it again for real, but only
 * when the inquiry is still as it was when it was checked; otherwise it shows the change afresh.
 *
 * Authority is per person and off for everyone until Management turns it on under Tour Desk Admin →
 * Edit Requests. It is not given by being able to open an inquiry, and the System Administrator's
 * "everything" does not include it either: it is switched on by name, like for anyone else.
 *
 * On a confirmed tour the request is saved as Waiting for Amendment, with its reason, and changes
 * nothing. Management opens the tour with Amend on the Tour Board; then it is checked and confirmed like
 * any other. Once one is applied, reading the email again is switched off for the inquiry, and Confirm
 * Tour waits for a quotation version issued after the last one applied.
 */
final class EditRequests
{
    public const AUTHORITY = 'edit_request';
    public const TABLE = 'tf_edit_request';
    public const AUTH_TABLE = 'tf_user_authority';

    private $db;
    private IntakeService $take;
    private static array $has = [];

    public function __construct($db, IntakeService $take)
    {
        $this->db   = $db;
        $this->take = $take;
    }

    public static function installed($db): bool
    {
        $k = spl_object_id($db);
        if (!array_key_exists($k, self::$has)) {
            try {
                self::$has[$k] = (bool) $db->query("SHOW TABLES LIKE '" . self::TABLE . "'")->getResultArray()
                    && (bool) $db->query("SHOW TABLES LIKE '" . self::AUTH_TABLE . "'")->getResultArray();
            } catch (\Throwable $e) {
                self::$has[$k] = false;
            }
        }
        return self::$has[$k];
    }

    /** For the tests, which build and drop the tables. */
    public static function forget(): void
    {
        self::$has = [];
    }

    private function user(): string
    {
        return $this->take->user();
    }

    private function who(): string
    {
        return $this->take->actorName() ?: ($this->user() ?: 'a colleague');
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function activity(): Activity
    {
        return $this->take->activity();
    }

    // =====================================================================
    //  Authority: per person, switched on and off by Management
    // =====================================================================

    /** Does this account hold Edit Request Authority? Nobody does until Management says so. */
    public static function holds($db, ?int $userId): bool
    {
        if (!$userId || !self::installed($db)) {
            return false;
        }
        try {
            $r = $db->query('SELECT granted FROM ' . self::AUTH_TABLE . ' WHERE user_id = ? AND authority = ?', [$userId, self::AUTHORITY])->getRowArray();
        } catch (\Throwable $e) {
            return false;
        }
        return $r !== null && (int) $r['granted'] === 1;
    }

    /** Every Tour Desk account, with its switch — for Tour Desk Admin → Edit Requests. */
    public function authorityList(): array
    {
        $on = [];
        foreach ($this->db->query('SELECT * FROM ' . self::AUTH_TABLE . ' WHERE authority = ?', [self::AUTHORITY])->getResultArray() as $r) {
            $on[(int) $r['user_id']] = $r;
        }
        $out = [];
        foreach (Staff::handlers($this->db) as $h) {
            if (empty($h['id']) || ($h['username'] ?? null) === null) {
                continue;   // a name on file with no Tour Desk account cannot be given anything
            }
            $r = $on[(int) $h['id']] ?? null;
            $out[] = [
                'user_id' => (int) $h['id'], 'name' => $h['name'], 'username' => $h['username'], 'group' => $h['group'],
                'granted' => $r !== null && (int) $r['granted'] === 1,
                'set_by' => $r['set_by'] ?? null, 'set_at' => $r['set_at'] ?? null, 'note' => $r['note'] ?? null,
            ];
            unset($on[(int) $h['id']]);
        }
        // an account switched on that no longer holds a Tour Desk screen still shows, so it can be switched off
        foreach ($on as $uid => $r) {
            $who = Staff::byId($this->db, $uid);
            $out[] = ['user_id' => $uid, 'name' => $who['name'] ?? ($r['name'] ?: ('account ' . $uid)), 'username' => $who['username'] ?? $r['username'], 'group' => null,
                'granted' => (int) $r['granted'] === 1, 'set_by' => $r['set_by'], 'set_at' => $r['set_at'], 'note' => $r['note'], 'no_desk' => true];
        }
        return $out;
    }

    /** Switch one person's authority on or off. The screen that calls this is Management's (tour/manage). */
    public function setAuthority(int $userId, bool $on, ?string $note = null): array
    {
        $who = Staff::byId($this->db, $userId);
        if ($who === null) {
            throw new \InvalidArgumentException('That account does not exist.');
        }
        $was = self::holds($this->db, $userId);
        $now = $this->now();
        $this->db->query(
            'INSERT INTO ' . self::AUTH_TABLE . ' (user_id, authority, username, name, granted, set_by, set_by_id, set_at, note) VALUES (?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE username = VALUES(username), name = VALUES(name), granted = VALUES(granted), set_by = VALUES(set_by), set_by_id = VALUES(set_by_id), set_at = VALUES(set_at), note = VALUES(note)',
            [$userId, self::AUTHORITY, self::cut($who['username'], 100), self::cut($who['name'], 160), $on ? 1 : 0, self::cut($this->who(), 100), $this->take->actorId(), $now, self::cut($note, 255)]
        );
        if ($was !== $on) {
            try {
                $this->activity()->record('authority', $userId, null, null, 'edit_authority',
                    'Edit Request Authority turned ' . ($on ? 'ON' : 'OFF') . ' for ' . $who['name'] . ($note ? ' — ' . $note : ''),
                    [['what' => 'Edit Request Authority · ' . $who['name'], 'from' => $was ? 'on' : 'off', 'to' => $on ? 'on' : 'off']]);
            } catch (\Throwable $e) {
                // the history never stops the work
            }
        }
        return $this->authorityList();
    }

    private function mustHold(): void
    {
        if (!self::holds($this->db, $this->take->actorId())) {
            throw new NoAuthority('Edit Requests need Edit Request Authority, which Management turns on for each person under Tour Desk Admin → Edit Requests. You can see the requests on this inquiry, but not make or confirm one.');
        }
    }

    // =====================================================================
    //  Requests
    // =====================================================================

    /** Every Edit Request on an inquiry, newest first, as the screen shows them. */
    public function listFor(int $intakeId): array
    {
        $rows = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE intake_id = ? ORDER BY seq DESC', [$intakeId])->getResultArray();
        return array_map([$this, 'shape'], $rows);
    }

    private function shape(array $r): array
    {
        $r['id']       = (int) $r['id'];
        $r['seq']      = (int) $r['seq'];
        $r['answers']  = json_decode((string) ($r['answers_json'] ?? ''), true) ?: [];
        $r['proposal'] = json_decode((string) ($r['proposal_json'] ?? ''), true) ?: null;
        $r['result']   = json_decode((string) ($r['result_json'] ?? ''), true) ?: null;
        unset($r['answers_json'], $r['proposal_json'], $r['result_json']);
        return $r;
    }

    private function row(int $intakeId, int $reqId): array
    {
        $r = $this->db->query('SELECT * FROM ' . self::TABLE . ' WHERE id = ? AND intake_id = ?', [$reqId, $intakeId])->getRowArray();
        if (!$r) {
            throw new \InvalidArgumentException('That Edit Request is not on this inquiry.');
        }
        return $r;
    }

    /**
     * Where the inquiry stands for Edit Requests: 'open' (they are checked and applied), 'locked' (a
     * confirmed tour: saved as Waiting for Amendment), or 'closed' (not taken), with the reason.
     *
     * @return array{state:string, why:string}
     */
    public static function standing(array $it): array
    {
        if (!empty($it['archived_at'])) {
            return ['state' => 'closed', 'why' => 'This inquiry is archived. Management can restore it; until then no Edit Request is taken.'];
        }
        $st = (string) ($it['status'] ?? '');
        if ($st === 'confirmed') {
            return ['state' => 'locked', 'why' => 'This tour is confirmed and locked. An Edit Request is saved as Waiting for Amendment and changes nothing until Management opens the tour with Amend on the Tour Board.'];
        }
        if ($st === 'cancelled') {
            return ['state' => 'closed', 'why' => 'This tour was cancelled. Management can reopen it from the Tour Board; until then no Edit Request is taken.'];
        }
        if ($st === 'declined') {
            return ['state' => 'closed', 'why' => 'This inquiry was declined. Set it back to working first (Back to working); then Edit Requests can be made.'];
        }
        if ($st === 'converted') {
            return ['state' => 'closed', 'why' => 'This inquiry was converted in the old system and is closed here.'];
        }
        return ['state' => 'open', 'why' => ''];
    }

    /**
     * A new Edit Request. On an open inquiry it is checked at once; on a confirmed tour it is saved as
     * Waiting for Amendment with its reason; on a closed inquiry it is refused.
     */
    public function submit(int $intakeId, array $in): array
    {
        $this->mustHold();
        $it = $this->take->get($intakeId);
        if (!$it) {
            throw new \InvalidArgumentException('Inquiry not found.');
        }
        $pasted = trim(str_replace("\r\n", "\n", (string) ($in['pasted'] ?? '')));
        $change = trim(str_replace("\r\n", "\n", (string) ($in['change_to'] ?? '')));
        $reason = trim((string) ($in['reason'] ?? ''));
        if ($pasted === '') {
            throw new \InvalidArgumentException('Paste the words that need to change into the first box.');
        }
        if (mb_strlen($pasted) > 4000 || mb_strlen($change) > 4000) {
            throw new \InvalidArgumentException('Keep an Edit Request to one change at a time: each box takes up to 4,000 characters.');
        }
        $stand = self::standing($it);
        if ($stand['state'] === 'closed') {
            throw new \RuntimeException($stand['why']);
        }
        if ($stand['state'] === 'locked' && $reason === '') {
            throw new \InvalidArgumentException('This tour is confirmed and locked, so the request waits for Management. Say why it is needed (the customer\'s message, say) in the reason box — Management sees it on the Tour Board.');
        }
        $tourId = null;
        try {
            $t = $this->db->query('SELECT id FROM tf_tour WHERE intake_id = ?', [$intakeId])->getRowArray();
            $tourId = $t ? (int) $t['id'] : null;
        } catch (\Throwable $e) {
            // no tours table here
        }
        $now = $this->now();
        // the request's number on its inquiry; two made at the same moment cannot take the same one
        for ($try = 0; ; $try++) {
            $seq = (int) ($this->db->query('SELECT COALESCE(MAX(seq), 0) + 1 AS n FROM ' . self::TABLE . ' WHERE intake_id = ?', [$intakeId])->getRowArray()['n'] ?? 1);
            try {
                $this->db->query(
                    'INSERT INTO ' . self::TABLE . ' (intake_id, seq, status, pasted_text, change_to, reason, was_locked, tour_id, requested_by, requested_by_id, requested_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    [$intakeId, $seq, $stand['state'] === 'locked' ? 'waiting' : 'open', $pasted, $change, self::cut($reason, 1000), $stand['state'] === 'locked' ? 1 : 0, $tourId,
                     self::cut($this->who(), 100), $this->take->actorId(), $now, $now]
                );
                break;
            } catch (\Throwable $e) {
                if ($try >= 2) {
                    throw $e;
                }
            }
        }
        $reqId = (int) $this->db->insertID();
        try {
            $this->activity()->record('inquiry', $intakeId, $intakeId, $tourId, 'edit_request',
                'Edit Request #' . $seq . ' ' . ($stand['state'] === 'locked' ? 'saved, waiting for amendment' : 'made') . ': “' . self::cut(self::oneLine($pasted), 90) . '” → “' . self::cut(self::oneLine($change), 90) . '”' . ($reason !== '' ? ' — ' . self::cut($reason, 120) : ''),
                [['what' => 'Edit Request #' . $seq, 'from' => self::cut($pasted, 200), 'to' => self::cut($change, 200)]]);
        } catch (\Throwable $e) {
            // the history never stops the work
        }
        if ($stand['state'] === 'locked') {
            return ['request' => $this->shape($this->row($intakeId, $reqId)), 'message' => 'Saved as Waiting for Amendment. It changes nothing on the confirmed tour; Management sees it, with your reason, on the Tour Board.'];
        }
        return $this->check($intakeId, $reqId, []);
    }

    /**
     * Read the request against the inquiry as it is now: the questions still open, or the change with
     * everything that follows from it. Nothing is changed; the result is kept with the request.
     */
    public function check(int $intakeId, int $reqId, array $answers): array
    {
        $this->mustHold();
        $r = $this->row($intakeId, $reqId);
        if (!in_array($r['status'], ['open', 'waiting'], true)) {
            throw new \RuntimeException('Edit Request #' . $r['seq'] . ' is ' . $r['status'] . '; there is nothing to check.');
        }
        $it = $this->take->get($intakeId);
        $stand = self::standing($it);
        if ($stand['state'] === 'locked') {
            return ['request' => $this->shape($r), 'message' => 'The tour is still confirmed and locked. The request waits until Management opens the tour with Amend on the Tour Board.'];
        }
        if ($stand['state'] === 'closed') {
            throw new \RuntimeException($stand['why']);
        }
        $all = array_merge(json_decode((string) ($r['answers_json'] ?? ''), true) ?: [], array_filter($answers, static fn ($v) => $v !== null && $v !== ''));
        $read = $this->read($it, $r, $all);
        $proposal = ['outcome' => 'question', 'questions' => $read['questions'], 'refusals' => $read['refusals'], 'notes' => $read['notes'], 'located' => $read['located'], 'intents' => $read['intents']];
        if ($read['questions']) {
            $proposal['outcome'] = 'question';
        } elseif (!$read['intents']) {
            $proposal['outcome'] = 'refused';
        } else {
            $sim = $this->simulate($intakeId, (int) $r['id'], (int) $r['seq'], $read['intents']);
            $proposal = array_merge($proposal, $sim, ['outcome' => 'proposal']);
        }
        $now = $this->now();
        $this->db->query('UPDATE ' . self::TABLE . ' SET status = ?, answers_json = ?, proposal_json = ?, fingerprint = ?, checked_by = ?, checked_at = ?, updated_at = ? WHERE id = ?', [
            'open', json_encode($all, JSON_UNESCAPED_UNICODE), json_encode($proposal, JSON_UNESCAPED_UNICODE), $proposal['fingerprint'] ?? null,
            self::cut($this->who(), 100), $now, $now, (int) $r['id'],
        ]);
        return ['request' => $this->shape($this->row($intakeId, (int) $r['id'])), 'message' => self::outcomeMessage($proposal)];
    }

    private static function outcomeMessage(array $p): string
    {
        if ($p['outcome'] === 'proposal') {
            $changes = array_filter($p['intents'], static fn ($i) => empty($i['same']));
            return $changes ? 'Check the change below, then Confirm — nothing has been changed yet.' : 'The inquiry already says this. Confirming records the amendment; nothing on the inquiry changes.';
        }
        if ($p['outcome'] === 'question') {
            return 'Answer the question' . (count($p['questions']) === 1 ? '' : 's') . ' below first — nothing is changed until then.';
        }
        return 'Nothing here can be changed by this Edit Request — see why below.';
    }

    /** The matcher, with the masters it needs. */
    private function read(array $it, array $r, array $answers): array
    {
        $it['extract_raw'] = json_decode((string) ($it['extract_json'] ?? ''), true) ?: [];
        $read = EditMatcher::read($it, (string) $r['pasted_text'], (string) $r['change_to'], $answers, $this->lookups());
        return $this->hotelTowns($it, $read);
    }

    /**
     * A new hotel in another town than the night's area says: the driving follows the area, so the
     * person is told, not second-guessed — the area is theirs to change, in the same request.
     */
    private function hotelTowns(array $it, array $read): array
    {
        $areas = [];
        foreach ((array) $it['days'] as $d) {
            $areas[(int) $d['id']] = (string) ($d['area'] ?? '');
        }
        foreach ($read['intents'] as $i) {
            if (($i['kind'] ?? '') === 'day' && ($i['attr'] ?? '') === 'area') {
                $areas[(int) $i['day_id']] = (string) $i['to_raw'];
            }
        }
        foreach ($read['intents'] as $k => $i) {
            if (($i['kind'] ?? '') !== 'day' || ($i['attr'] ?? '') !== 'hotel_name_raw' || empty($i['hotel_id'])) {
                continue;
            }
            $h = ($this->lookups()['hotelById'])((int) $i['hotel_id']);
            $town = $h ? trim(MileageLookup::townOf((string) ($h['location'] ?? ''))) : '';
            $area = trim($areas[(int) $i['day_id']] ?? '');
            if ($town === '' || $area === '' || mb_stripos($area, $town) !== false) {
                continue;
            }
            $read['intents'][$k]['note'] = trim($i['note'] . ' Hotel Rates has ' . $h['name'] . ' in ' . $town . ', but the night\'s area says “' . $area . '”, and the driving is planned to the area. If the party now stays in ' . $town . ', put the area in the Change-to box as well (“' . $h['name'] . ' – ' . $town . '”).');
        }
        return $read;
    }

    private ?array $lookups = null;

    private function lookups(): array
    {
        if ($this->lookups !== null) {
            return $this->lookups;
        }
        $db = $this->db;
        $take = $this->take;
        $hotels = [];
        try {
            $hotels = $db->query("SELECT h.id, h.name, l.name AS location FROM hr2_hotel h LEFT JOIN hr2_location l ON l.id = h.location_id WHERE h.status NOT IN ('merged', 'inactive')")->getResultArray();
        } catch (\Throwable $e) {
            $hotels = [];
        }
        $byKey = [];
        foreach ($hotels as $h) {
            $byKey[\App\Libraries\HotelRates\HotelRateService::nameKey((string) $h['name'])][] = $h;
        }
        $free = [];
        try {
            $free = $db->query('SELECT id, name FROM tf_free_visit WHERE active = 1')->getResultArray();
        } catch (\Throwable $e) {
            $free = [];
        }
        $paid = [];
        try {
            $hasMeta = (bool) $db->query("SHOW TABLES LIKE 'tf_fee_meta'")->getResultArray();
            $paid = $db->query($hasMeta ? 'SELECT e.id, e.name FROM entrance_fee e LEFT JOIN tf_fee_meta m ON m.fee_id = e.id WHERE COALESCE(m.active, 1) = 1' : 'SELECT id, name FROM entrance_fee')->getResultArray();
        } catch (\Throwable $e) {
            $paid = [];
        }
        $classes = [];
        try {
            $classes = $db->query('SELECT code, name, match_words, active FROM tf_vehicle_class')->getResultArray();
        } catch (\Throwable $e) {
            $classes = [];
        }
        $this->lookups = [
            // a hotel is exactly one record of Hotel Rates by its name, or it is asked about
            'hotel' => static function (string $text) use ($byKey, $hotels, $take): array {
                $k = \App\Libraries\HotelRates\HotelRateService::nameKey($text);
                $exact = $byKey[$k] ?? [];
                if (count($exact) === 1) {
                    return ['exact' => $exact[0], 'near' => []];
                }
                $near = [];
                foreach ($exact as $h) {
                    $near[(int) $h['id']] = $h;
                }
                $names = array_flip(array_map('mb_strtolower', $take->nearHotelNames($text, 5)));
                foreach ($hotels as $h) {
                    $hk = \App\Libraries\HotelRates\HotelRateService::nameKey((string) $h['name']);
                    if (isset($names[mb_strtolower((string) $h['name'])]) || (strlen($k) >= 6 && strlen($hk) >= 6 && (str_contains($hk, $k) || str_contains($k, $hk)))) {
                        $near[(int) $h['id']] = $h;
                    }
                }
                return ['exact' => null, 'ambiguous' => count($exact) > 1, 'near' => array_slice(array_values($near), 0, 8)];
            },
            'hotelById' => static function (int $id) use ($hotels, $byKey): ?array {
                foreach ($hotels as $h) {
                    if ((int) $h['id'] === $id) {
                        // two hotels of one name in Hotel Rates cannot be told apart when the night is priced by name
                        if (count($byKey[\App\Libraries\HotelRates\HotelRateService::nameKey((string) $h['name'])] ?? []) > 1) {
                            return $h + ['duplicate' => true];
                        }
                        return $h;
                    }
                }
                return null;
            },
            // a visit is exactly one entry of Free Visits or of Paid Activities & Services, by name
            'visit' => static function (string $text) use ($free, $take): ?array {
                $k = ServiceLookup::nameKey($text);
                if ($k === '') {
                    return null;
                }
                // schema 39: the Entrance Fee Master is the authority — an item of it (by name or other name) is the paid one
                if ($take->services()->master()) {
                    $m = $take->services()->match($text);
                    if (($m['state'] ?? '') === 'matched' && (!empty($m['place_id']) || !empty($m['fee_id']))) {
                        // a place added on the master alone has no old record: it is carried by its place
                        return ['kind' => 'paid', 'id' => (int) ($m['fee_id'] ?? 0), 'place_id' => !empty($m['place_id']) ? (int) $m['place_id'] : null, 'name' => (string) $m['name']];
                    }
                }
                foreach ($free as $f) {
                    if (ServiceLookup::nameKey((string) $f['name']) === $k) {
                        return ['kind' => 'free', 'id' => (int) $f['id'], 'name' => (string) $f['name']];
                    }
                }
                $m = $take->services()->match($text);
                if (($m['state'] ?? '') === 'matched' && !empty($m['fee_id'])) {
                    return ['kind' => 'paid', 'id' => (int) $m['fee_id'], 'name' => (string) $m['name']];
                }
                return null;
            },
            'masters' => array_merge(
                array_map(static fn ($f) => ['kind' => 'free', 'id' => (int) $f['id'], 'name' => (string) $f['name']], $free),
                array_map(static fn ($f) => ['kind' => 'paid', 'id' => (int) $f['id'], 'name' => (string) $f['name']], $paid)
            ),
            'crewRoles' => $take->crewRoles(true),
            'vehicleClasses' => $classes,
        ];
        return $this->lookups;
    }

    // =====================================================================
    //  The change, worked out and undone
    // =====================================================================

    /**
     * Make the change inside a transaction, read the inquiry and its costing before and after, and
     * undo it. What comes back is exactly what Confirm will do.
     */
    private function simulate(int $intakeId, int $reqId, int $seq, array $intents): array
    {
        $this->db->transBegin();
        try {
            $before = $this->state($intakeId);
            $fp = self::fingerprint($before, $intents);
            $errors = $this->apply($intakeId, $reqId, $seq, $intents, true);
            $after = $this->state($intakeId);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return ['fingerprint' => null, 'errors' => [$e->getMessage()], 'connected' => [], 'follow_ups' => [], 'cleared' => [], 'cost' => null];
        }
        $this->db->transRollback();
        return ['fingerprint' => $fp, 'errors' => $errors] + self::diff($before, $after, $intents);
    }

    /**
     * Apply the change for real — only while the inquiry is as it was when it was checked.
     */
    public function confirm(int $intakeId, int $reqId, ?string $fingerprint): array
    {
        $this->mustHold();
        $r = $this->row($intakeId, $reqId);
        if (!in_array($r['status'], ['open', 'waiting'], true)) {
            throw new \RuntimeException('Edit Request #' . $r['seq'] . ' is ' . $r['status'] . '.');
        }
        $it = $this->take->get($intakeId);
        $stand = self::standing($it);
        if ($stand['state'] !== 'open') {
            throw new \RuntimeException($stand['state'] === 'locked' ? 'The tour is confirmed and locked: Management opens it with Amend on the Tour Board before an Edit Request can change it.' : $stand['why']);
        }
        $answers = json_decode((string) ($r['answers_json'] ?? ''), true) ?: [];
        $read = $this->read($it, $r, $answers);
        if ($read['questions'] || !$read['intents']) {
            $out = $this->check($intakeId, $reqId, []);
            $out['stale'] = true;
            $out['message'] = 'The request has to be checked again before it can be confirmed: ' . $out['message'];
            return $out;
        }
        $intents = $read['intents'];
        $this->db->transBegin();
        try {
            // one Confirm at a time: a second one pressed at the same moment finds it applied
            $lock = $this->db->query('SELECT status FROM ' . self::TABLE . ' WHERE id = ? FOR UPDATE', [(int) $r['id']])->getRowArray();
            if (!$lock || !in_array($lock['status'], ['open', 'waiting'], true)) {
                $this->db->transRollback();
                throw new \RuntimeException('Edit Request #' . $r['seq'] . ' is ' . ($lock['status'] ?? 'gone') . ' — somebody else has just dealt with it.');
            }
            $before = $this->state($intakeId);
            if ($fingerprint === null || $fingerprint === '' || self::fingerprint($before, $intents) !== $fingerprint) {
                $this->db->transRollback();
                $out = $this->check($intakeId, $reqId, []);
                $out['stale'] = true;
                $out['message'] = 'The inquiry changed since this was checked (someone edited it, or a rate moved). Here is the change as it stands now — check it again before confirming.';
                return $out;
            }
            $snapBefore = $this->activity()->snapshotIntake($intakeId);
            $errors = $this->apply($intakeId, $reqId, (int) $r['seq'], $intents, false);
            if ($errors) {
                throw new \RuntimeException(implode(' ', $errors));
            }
            $after = $this->state($intakeId);
            $diff = self::diff($before, $after, $intents);
            $now = $this->now();
            $result = ['intents' => $intents, 'notes' => $read['notes'], 'refusals' => $read['refusals']] + $diff;
            $this->db->query('UPDATE ' . self::TABLE . ' SET status = ?, result_json = ?, confirmed_by = ?, confirmed_by_id = ?, confirmed_at = ?, quote_mark = ?, updated_at = ? WHERE id = ?', [
                'applied', json_encode($result, JSON_UNESCAPED_UNICODE), self::cut($this->who(), 100), $this->take->actorId(), $now, $this->quoteMark($intakeId), $now, (int) $r['id'],
            ]);
            // the history: the Edit Request itself, the values it changed, and everything that followed
            $snapAfter = $this->activity()->snapshotIntake($intakeId);
            $changes = [];
            foreach ($intents as $i) {
                $changes[] = ['what' => 'Edit Request #' . $r['seq'] . ' · ' . $i['scope'] . ' · ' . $i['label'], 'from' => self::cut((string) $i['from'], 300), 'to' => self::cut((string) $i['to'], 300) . (!empty($i['same']) ? ' (already so — recorded)' : '')];
            }
            foreach ($snapBefore && $snapAfter ? Activity::diff($snapBefore, $snapAfter) : [] as $c) {
                $changes[] = $c;
            }
            foreach ($diff['connected'] as $c) {
                if (($c['group'] ?? '') === 'Costing' || ($c['group'] ?? '') === 'Transport and mileage') {
                    $changes[] = ['what' => $c['group'] . ' · ' . $c['what'], 'from' => $c['from'], 'to' => $c['to']];
                }
            }
            $tourId = $r['tour_id'] !== null ? (int) $r['tour_id'] : null;
            $actId = $this->activity()->record('inquiry', $intakeId, $intakeId, $tourId, 'edit_request',
                'Edit Request #' . $r['seq'] . ' applied, confirmed by ' . $this->who() . ': ' . self::summaryLine($intents), $changes);
            $this->db->query('UPDATE ' . self::TABLE . ' SET activity_id = ? WHERE id = ?', [$actId, (int) $r['id']]);
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new \RuntimeException('The Edit Request could not be applied, and nothing was changed: ' . $e->getMessage());
        }
        return ['request' => $this->shape($this->row($intakeId, $reqId)), 'message' => 'Edit Request #' . $r['seq'] . ' applied. It is in the history with everything that followed from it.', 'applied' => true];
    }

    public function cancel(int $intakeId, int $reqId, ?string $note = null): array
    {
        $this->mustHold();
        $r = $this->row($intakeId, $reqId);
        if (!in_array($r['status'], ['open', 'waiting'], true)) {
            throw new \RuntimeException('Edit Request #' . $r['seq'] . ' is ' . $r['status'] . ' and cannot be withdrawn.');
        }
        $now = $this->now();
        $this->db->query('UPDATE ' . self::TABLE . ' SET status = ?, cancelled_by = ?, cancelled_at = ?, cancel_note = ?, updated_at = ? WHERE id = ?', ['cancelled', self::cut($this->who(), 100), $now, self::cut($note, 255), $now, $reqId]);
        try {
            $this->activity()->record('inquiry', $intakeId, $intakeId, $r['tour_id'] !== null ? (int) $r['tour_id'] : null, 'edit_request', 'Edit Request #' . $r['seq'] . ' withdrawn by ' . $this->who() . ($note ? ' — ' . $note : '') . '. Nothing was changed.', null);
        } catch (\Throwable $e) {
            // the history never stops the work
        }
        return ['request' => $this->shape($this->row($intakeId, $reqId)), 'message' => 'Withdrawn. Nothing was changed.'];
    }

    /** The pick a paid item gives a line: its old record, and (schema 39) its place in the Place to Visit / Entrance Fee Master. */
    private static function paidPick(array $m): array
    {
        $pick = [];
        if ((int) ($m['id'] ?? 0) > 0) {
            $pick['fee_id'] = (int) $m['id'];
        }
        if ((int) ($m['place_id'] ?? 0) > 0) {
            $pick['place_id'] = (int) $m['place_id'];
        }
        return $pick;
    }

    private static function summaryLine(array $intents): string
    {
        $parts = [];
        foreach ($intents as $i) {
            $parts[] = $i['label'] . ' ' . self::cut((string) $i['from'], 40) . ' → ' . self::cut((string) $i['to'], 40) . (!empty($i['same']) ? ' (already so)' : '');
        }
        return self::cut(implode('; ', $parts), 380);
    }

    /**
     * Each working value through the service call a person's own edit uses. On a dry run the request
     * is also marked applied inside the transaction, so what follows from it (the dates confirmed, the
     * email no longer read again) shows in the preview as it will be.
     *
     * @return string[] what could not be done
     */
    private function apply(int $id, int $reqId, int $seq, array $intents, bool $dry): array
    {
        $errors = [];
        $by = 'Edit Request #' . $seq . ' by ' . $this->who();
        $order = ['field:arrival_date' => 0, 'field:departure_date' => 1];
        usort($intents, static function ($a, $b) use ($order) {
            $rank = static fn ($i) => $order[$i['key']] ?? ['field' => 2, 'rooms' => 3, 'vehicle' => 4, 'crew' => 5, 'day' => 6, 'departure_words' => 7, 'service_remove' => 8, 'service_rename' => 9, 'service_add' => 10, 'request' => 11, 'request_drop' => 11, 'request_new' => 11, 'passenger' => 12][$i['kind']] ?? 20;
            return $rank($a) <=> $rank($b);
        });
        foreach ($intents as $i) {
            if (!empty($i['same'])) {
                continue;   // already so: recorded, nothing to change
            }
            try {
                switch ($i['kind']) {
                    case 'field':
                        $this->take->saveField($id, $i['field'], $i['to_raw'], $by);
                        break;
                    case 'rooms':
                        $this->take->saveRooms($id, $i['to_raw']);
                        break;
                    case 'vehicle':
                        $l = $i['line'];
                        $this->take->saveVehicle($id, [['class_code' => $i['to_code'], 'qty' => (int) ($l['qty'] ?? 1), 'group_id' => $l['group_id'] ?? null, 'day_from' => $l['day_from'] ?? null, 'day_to' => $l['day_to'] ?? null, 'label' => $l['label'] ?? null]]);
                        break;
                    case 'crew':
                        $this->take->changeCrew($id, $i['from_code'], $i['to_code']);
                        break;
                    case 'day':
                        $this->take->saveDay($id, (int) $i['day_id'], [$i['attr'] => $i['to_raw']]);
                        if ($i['attr'] === 'hotel_name_raw' && !empty($i['hotel_id'])) {
                            $got = $this->db->query('SELECT hotel_id FROM tf_intake_day WHERE id = ?', [(int) $i['day_id']])->getRowArray();
                            if ((int) ($got['hotel_id'] ?? 0) !== (int) $i['hotel_id']) {
                                $errors[] = 'Night ' . $i['day_no'] . ' did not take ' . $i['to'] . ' from Hotel Rates (another hotel there has the same name). Choose it on the night instead.';
                            }
                        }
                        break;
                    case 'departure_words':
                        $this->take->saveDepartureNarrative($id, (string) $i['to_raw']);
                        break;
                    case 'service_remove':
                        $this->take->deleteService($id, (int) $i['service_id']);
                        break;
                    case 'service_rename':
                        $s = $this->db->query('SELECT * FROM tf_intake_service WHERE id = ? AND intake_id = ?', [(int) $i['service_id'], $id])->getRowArray();
                        if (!$s) {
                            $errors[] = 'The line “' . $i['from'] . '” is no longer on the inquiry.';
                            break;
                        }
                        $m = $i['master'] ?? null;
                        $this->take->saveService($id, (int) $s['id'], ['title' => $i['to_raw'], 'day_no' => (int) $s['day_no'], 'kind' => $s['kind'], 'qty' => $s['qty'], 'pay_mode' => $m && $m['kind'] === 'free' ? 'no_charge' : $s['pay_mode'], 'special_note' => $s['special_note']] + ($m && $m['kind'] === 'paid' ? self::paidPick($m) : []));
                        if ($m && $m['kind'] === 'free') {
                            $this->take->addFreeVisit($id, (int) $s['day_no'], (int) $m['id']);
                        }
                        break;
                    case 'service_add':
                        $m = $i['master'];
                        if ($m['kind'] === 'free') {
                            $this->take->addFreeVisit($id, (int) $i['day_no'], (int) $m['id']);
                        } else {
                            $this->take->saveService($id, null, ['title' => (string) $m['name'], 'day_no' => (int) $i['day_no']] + self::paidPick($m));
                        }
                        break;
                    case 'request':
                        $rq = $this->db->query('SELECT kind FROM tf_intake_request WHERE id = ? AND intake_id = ?', [(int) $i['request_id'], $id])->getRowArray();
                        $this->take->saveRequest($id, (int) $i['request_id'], ['title' => $i['to_raw'], 'kind' => $rq['kind'] ?? 'request', 'quote_text' => $i['quote'] ?? null]);
                        break;
                    case 'request_drop':
                        $this->take->answerRequest($id, (int) $i['request_id'], 'dropped', 'Dropped by ' . $by . '.');
                        break;
                    case 'request_new':
                        $this->take->saveRequest($id, null, ['title' => $i['to_raw'], 'quote_text' => $i['quote'] ?? null]);
                        break;
                    case 'passenger':
                        $p = $this->db->query('SELECT room_type, note FROM tf_intake_pax WHERE id = ? AND intake_id = ?', [(int) $i['pax_id'], $id])->getRowArray() ?: [];
                        $this->take->savePassenger($id, (int) $i['pax_id'], ['full_name' => $i['to_raw'], 'room_type' => $p['room_type'] ?? null, 'note' => $p['note'] ?? null]);
                        break;
                }
            } catch (\Throwable $e) {
                $errors[] = $i['scope'] . ' · ' . $i['label'] . ': ' . $e->getMessage();
            }
        }
        if ($dry) {
            // inside the transaction only: the request counts as applied for what the inquiry shows after
            $this->db->query('UPDATE ' . self::TABLE . ' SET status = ?, result_json = ?, confirmed_by = ?, confirmed_at = ?, quote_mark = ? WHERE id = ?', ['applied', json_encode(['intents' => $intents], JSON_UNESCAPED_UNICODE), self::cut($this->who(), 100), $this->now(), $this->quoteMark($id), $reqId]);
        } else {
            $this->db->query('UPDATE ' . self::TABLE . ' SET result_json = ? WHERE id = ?', [json_encode(['intents' => $intents], JSON_UNESCAPED_UNICODE), $reqId]);
        }
        return $errors;
    }

    /** The newest quotation version on the inquiry, made or issued (0: none yet). */
    private function quoteMark(int $intakeId): int
    {
        try {
            return (int) ($this->db->query('SELECT COALESCE(MAX(id), 0) AS m FROM tf_quote WHERE intake_id = ?', [$intakeId])->getRowArray()['m'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // =====================================================================
    //  The inquiry, read for the comparison
    // =====================================================================

    private function state(int $id): array
    {
        $it = $this->take->get($id);
        $cost = null;
        try {
            $cost = (new QuoteService($this->db, $this->user(), $this->take))->costing($id);
        } catch (\Throwable $e) {
            $cost = null;
        }
        $nights = [];
        $words = ['rate' => 'published rate missing', 'no_plan' => 'published rate missing', 'no_supplier' => 'no supplier record', 'not_in_panel' => 'not in the panel', 'nothing_named' => 'no hotel named'];
        foreach ((array) $it['days'] as $d) {
            $hasFigure = ($d['rate_state'] ?? '') === 'rate' && ($d['rate_unit'] !== null || $d['rate_sgl'] !== null || $d['rate_dbl'] !== null || $d['rate_tpl'] !== null);
            $nights[(int) $d['day_no']] = [
                'date' => $d['day_date'], 'area' => $d['area'], 'hotel' => $d['hotel_name_raw'], 'meal' => $d['meal_plan'], 'room' => $d['room_type_name'],
                'rate' => ($d['rate_state'] ?? '') === 'rate' && empty($d['room_type_id']) ? 'the room type still to choose' : ($words[(string) $d['rate_state']] ?? (string) $d['rate_state']),
                'figure' => $hasFigure ? IntakeService::figureText($d, $it) : null, 'words' => $d['narrative'],
            ];
        }
        $services = [];
        foreach ((array) $it['services'] as $s) {
            $services[(int) $s['id']] = ['day' => (int) $s['day_no'], 'title' => $s['title'], 'state' => $s['match_state'] ?? null, 'usd' => $s['amount_usd'] ?? null, 'mode' => $s['pay_mode'] ?? null];
        }
        $legs = [];
        $km = 0.0;
        foreach ((array) $it['sectors'] as $s) {
            if (!empty($s['removed_at'])) {
                continue;
            }
            $k = (int) $s['vehicle_seq'] . ':' . (int) $s['day_no'] . ':' . $s['kind'] . ':' . mb_strtolower((string) $s['from_place']) . '>' . mb_strtolower((string) $s['to_place']);
            $legs[$k] = ['day' => (int) $s['day_no'], 'kind' => $s['kind'], 'from' => $s['from_place'], 'to' => $s['to_place'], 'km' => $s['km'] === null ? null : (float) $s['km'], 'extra' => $s['extra_km'] === null ? null : (float) $s['extra_km']];
            $km += (float) ($s['km'] ?? 0) + (float) ($s['extra_km'] ?? 0);
        }
        $crew = [];
        foreach ((array) $it['crew'] as $c) {
            $crew[(string) $c['role_code']] = ['name' => $c['role_name'], 'days' => $c['days'] === null ? null : (int) $c['days'], 'by' => $c['days_by'] ?? null, 'per_day' => $c['bata'] ?? null, 'amount' => $c['amount'] ?? null, 'currency' => $c['currency'] ?? null];
        }
        $veh = [];
        foreach ((array) $it['vehicles'] as $v) {
            $veh[(int) $v['sort_order']] = ['class' => $v['type'] ?: $v['class_code'], 'qty' => (int) $v['qty'], 'rate' => $v['km_rate']];
        }
        $blockers = [];
        foreach ((array) ($cost['blockers'] ?? []) as $b) {
            $blockers[(string) $b['code']] = (string) $b['label'];
        }
        $gaps = [];
        foreach ((array) ($it['gaps'] ?? []) as $g) {
            if (str_starts_with((string) $g['code'], 'edit_request')) {
                continue;   // what the Edit Requests themselves say is not the inquiry's state
            }
            $gaps[(string) $g['code']] = ['level' => $g['level'], 'label' => $g['label']];
        }
        $dc = $it['dates_check'] ?? null;
        return [
            'dates' => ['line' => $it['tour_dates']['line'] ?? null, 'nights' => $it['tour_dates']['nights'] ?? null, 'dep_no' => $it['tour_dates']['departure_day_no'] ?? null],
            'fields' => [
                'pax_total' => $it['pax_total'], 'pax_adults' => $it['pax_adults'], 'pax_children' => $it['pax_children'],
                'arrival_flight' => $it['arrival_flight'], 'departure_flight' => $it['departure_flight'], 'meal_basis' => $it['fields']['meal_basis']['value'] ?? null,
                'hotel_class' => $it['hotel_class'], 'tour_name' => $it['tour_name'], 'rooms_note' => $it['rooms_note'],
            ],
            'rooms' => ['sgl' => (int) $it['rooms_sgl'], 'dbl' => (int) $it['rooms_dbl'], 'twn' => (int) $it['rooms_twn'], 'tpl' => (int) $it['rooms_tpl']],
            'nights' => $nights,
            'departure_rows' => array_map(static fn ($r) => (int) $r['day_no'], (array) ($it['departure_rows'] ?? [])),
            'missing' => array_values((array) ($it['missing_nights'] ?? [])),
            'departure' => ['words' => $it['departure_narrative'] ?? null, 'text' => $it['departure_text'] ?? null],
            'services' => $services,
            'requests' => array_map(static fn ($r) => ['title' => $r['title'], 'status' => $r['status']], array_column((array) $it['requests'], null, 'id')),
            'passengers' => array_map(static fn ($p) => $p['full_name'], array_column((array) $it['passengers'], null, 'id')),
            'crew' => $crew,
            'vehicles' => $veh,
            'legs' => $legs,
            'km' => round($km, 1),
            'blockers' => $blockers,
            'gaps' => $gaps,
            'dates_confirmed' => $dc ? (bool) $dc['confirmed'] : null,
            'cost_usd' => $cost['cost_usd'] ?? null,
            'sell_usd' => $cost['markup']['sell_usd'] ?? null,
            'per_person_usd' => $cost['markup']['per_person_usd'] ?? null,
        ];
    }

    private static function fingerprint(array $before, array $intents): string
    {
        $key = array_map(static fn ($i) => [$i['key'], $i['to_raw'] ?? null, $i['kind']], $intents);
        return sha1(json_encode([$before, $key], JSON_UNESCAPED_UNICODE));
    }

    /**
     * What follows from the change: every value that moved besides the ones asked for, what a person
     * still has to settle after it, and what it settles.
     */
    private static function diff(array $a, array $b, array $intents): array
    {
        $direct = [];
        foreach ($intents as $i) {
            $direct[$i['key']] = true;
        }
        $out = [];
        $add = static function (string $group, string $what, $from, $to) use (&$out): void {
            $f = is_array($from) ? json_encode($from) : (string) ($from ?? '');
            $t = is_array($to) ? json_encode($to) : (string) ($to ?? '');
            if ($f === $t) {
                return;
            }
            $out[] = ['group' => $group, 'what' => $what, 'from' => $f === '' ? '—' : $f, 'to' => $t === '' ? '—' : $t];
        };
        $add('Dates and nights', 'The tour', $a['dates']['line'], $b['dates']['line']);
        foreach ($a['fields'] as $k => $v) {
            if (!isset($direct['field:' . $k])) {
                $add('The inquiry', EditMatcher::FIELD_LABELS[$k] ?? $k, $v, $b['fields'][$k] ?? null);
            }
        }
        if (!isset($direct['rooms'])) {
            $add('The party', 'Rooms', self::mix($a['rooms']), self::mix($b['rooms']));
        }
        foreach (array_unique(array_merge(array_keys($a['nights']), array_keys($b['nights']))) as $n) {
            $x = $a['nights'][$n] ?? null;
            $y = $b['nights'][$n] ?? null;
            $g = 'Night ' . $n;
            if ($x === null) {
                $add('Nights', $g, 'no line', 'a line for ' . ($y['date'] ?? 'this night'));
                continue;
            }
            if ($y === null) {
                $add('Nights', $g, ($x['hotel'] ?: 'a line') . ($x['date'] ? ' · ' . $x['date'] : ''), 'no line');
                continue;
            }
            $add('Nights', $g . ' · date', $x['date'], $y['date']);
            if (!isset($direct['night:' . $n . ':hotel'])) {
                $add('Nights', $g . ' · hotel', $x['hotel'], $y['hotel']);
            }
            if (!isset($direct['night:' . $n . ':area'])) {
                $add('Nights', $g . ' · area', $x['area'], $y['area']);
            }
            if (!isset($direct['night:' . $n . ':meal'])) {
                $add('Nights', $g . ' · meal plan', $x['meal'], $y['meal']);
            }
            $add('Nights', $g . ' · room type', $x['room'], $y['room']);
            $add('Nights', $g . ' · published rate', $x['figure'] ?: $x['rate'], $y['figure'] ?: $y['rate']);
        }
        $add('Dates and nights', 'Lines on the departure day', implode(', ', $a['departure_rows']), implode(', ', $b['departure_rows']));
        $add('Dates and nights', 'Nights with no line', implode(', ', $a['missing']), implode(', ', $b['missing']));
        if (!isset($direct['dep:words'])) {
            $add('The departure day', 'Wording', $a['departure']['words'], $b['departure']['words']);
        }
        foreach (array_unique(array_merge(array_keys($a['services']), array_keys($b['services']))) as $sid) {
            $x = $a['services'][$sid] ?? null;
            $y = $b['services'][$sid] ?? null;
            if (isset($direct['svc:' . $sid])) {
                continue;
            }
            if ($x === null) {
                // a new line: shown unless it is the visit this request adds
                $isAdd = false;
                foreach ($intents as $i) {
                    if ($i['kind'] === 'service_add' && EditMatcher::normText((string) $i['to_raw']) === EditMatcher::normText((string) $y['title'])) {
                        $isAdd = true;
                    }
                }
                if (!$isAdd) {
                    $add('Visits and services', 'Day ' . $y['day'] . ' · ' . $y['title'], 'not there', 'added');
                }
                continue;
            }
            if ($y === null) {
                $add('Visits and services', 'Day ' . $x['day'] . ' · ' . $x['title'], 'on the programme', 'taken off');
                continue;
            }
            $add('Visits and services', 'Day ' . $y['day'] . ' · ' . $y['title'] . ' · price', $x['usd'] !== null ? 'USD ' . $x['usd'] : ($x['state'] ?? '—'), $y['usd'] !== null ? 'USD ' . $y['usd'] : ($y['state'] ?? '—'));
            $add('Visits and services', 'Day ' . $y['day'] . ' · ' . $y['title'] . ' · day', $x['day'], $y['day']);
        }
        // a role changed by the request: the old line's days and bata beside the new one's
        $bata = static fn (?array $c) => $c === null ? '—' : ($c['days'] === null ? 'no days' : $c['days'] . ' day' . ($c['days'] === 1 ? '' : 's'))
            . ($c !== null && $c['per_day'] !== null ? ' × ' . $c['currency'] . ' ' . number_format((float) $c['per_day']) : '')
            . ($c !== null && $c['amount'] !== null ? ' = ' . $c['currency'] . ' ' . number_format((float) $c['amount']) : ($c !== null ? ' · no bata in the Crew Bata master' : ''));
        $paired = [];
        foreach ($intents as $i) {
            if (($i['kind'] ?? '') === 'crew' && empty($i['same'])) {
                $x = $a['crew'][$i['from_code']] ?? null;
                $y = $b['crew'][$i['to_code']] ?? null;
                $add('Driver and guide', 'Bata · ' . ($x['name'] ?? $i['from']) . ' → ' . ($y['name'] ?? $i['to']), $bata($x), $bata($y));
                $paired[$i['from_code']] = $paired[$i['to_code']] = true;
            }
        }
        foreach ($a['crew'] as $code => $x) {
            $y = $b['crew'][$code] ?? null;
            if ($y === null || isset($paired[$code])) {
                continue;
            }
            $add('Driver and guide', $x['name'] . ' · days', $x['days'], $y['days']);
            $add('Driver and guide', $x['name'] . ' · bata', $x['amount'] !== null ? $x['currency'] . ' ' . number_format((float) $x['amount']) : '—', $y['amount'] !== null ? $y['currency'] . ' ' . number_format((float) $y['amount']) : '—');
        }
        foreach ($b['crew'] as $code => $y) {
            if (!isset($a['crew'][$code]) && !isset($paired[$code])) {
                $add('Driver and guide', $y['name'] . ' · days and bata', '—', $bata($y));
                if ($y['by'] === 'human') {
                    $add('Driver and guide', $y['name'] . ' · days set by hand', '', 'kept');
                }
            }
        }
        foreach ($a['crew'] as $code => $x) {
            if (!isset($b['crew'][$code]) && !isset($paired[$code])) {
                $add('Driver and guide', $x['name'], $bata($x), 'no longer on the inquiry');
            }
        }
        if (!isset($direct['vehicle'])) {
            foreach ($a['vehicles'] as $k => $x) {
                $y = $b['vehicles'][$k] ?? null;
                $add('Transport and mileage', 'Vehicle ' . ($k + 1), $x['qty'] . ' × ' . $x['class'], $y ? $y['qty'] . ' × ' . $y['class'] : '—');
            }
        }
        foreach ($a['vehicles'] as $k => $x) {
            $y = $b['vehicles'][$k] ?? null;
            if ($y) {
                $add('Transport and mileage', 'Vehicle ' . ($k + 1) . ' · rate per km', $x['rate'], $y['rate']);
            }
        }
        // the legs, day by day: moved, added, taken off
        $legLine = static fn ($l) => 'Day ' . $l['day'] . ' · ' . $l['from'] . ' → ' . $l['to'] . ' (' . $l['kind'] . ($l['km'] !== null ? ', ' . rtrim(rtrim(number_format($l['km'] + (float) $l['extra'], 1), '0'), '.') . ' km' : ', no distance') . ')';
        foreach (array_diff_key($a['legs'], $b['legs']) as $l) {
            $add('Transport and mileage', 'Leg', $legLine($l), 'no longer planned');
        }
        foreach (array_diff_key($b['legs'], $a['legs']) as $l) {
            $add('Transport and mileage', 'Leg', 'not planned', $legLine($l));
        }
        foreach (array_intersect_key($a['legs'], $b['legs']) as $k => $l) {
            if ($l['km'] !== $b['legs'][$k]['km'] || $l['extra'] !== $b['legs'][$k]['extra']) {
                $add('Transport and mileage', 'Leg ' . $legLine($l), $legLine($l), $legLine($b['legs'][$k]));
            }
        }
        $add('Transport and mileage', 'Kilometres for the tour', $a['km'] . ' km', $b['km'] . ' km');
        $add('Costing', 'Cost of the tour (USD)', $a['cost_usd'] !== null ? number_format((float) $a['cost_usd'], 2) : '—', $b['cost_usd'] !== null ? number_format((float) $b['cost_usd'], 2) : '—');
        $add('Costing', 'Price (USD)', $a['sell_usd'] !== null ? number_format((float) $a['sell_usd'], 2) : '—', $b['sell_usd'] !== null ? number_format((float) $b['sell_usd'], 2) : '—');
        $add('Costing', 'Price per person (USD)', $a['per_person_usd'] !== null ? number_format((float) $a['per_person_usd'], 2) : '—', $b['per_person_usd'] !== null ? number_format((float) $b['per_person_usd'], 2) : '—');
        foreach ($a['requests'] as $rid => $x) {
            $y = $b['requests'][$rid] ?? null;
            if ($y && !isset($direct['req:' . $rid])) {
                $add('Customer requests', $x['title'], $x['status'], $y['status']);
            }
        }
        // what a person still has to settle after it, and what it settles. A line of the checklist that
        // was there before is not new because its wording moved (a night checked again says it again) —
        // except the dates question, whose wording is what says which date is still to confirm.
        $follow = [];
        $seen = [];
        $key = static fn (string $l) => mb_strtolower(trim(explode(' — ', $l, 2)[0]));
        foreach ($b['gaps'] as $code => $g) {
            $new = !isset($a['gaps'][$code]) || ($code === 'dates_email' && $a['gaps'][$code]['label'] !== $g['label']);
            if ($new && in_array($g['level'], ['red', 'amber'], true) && !isset($seen[$key($g['label'])])) {
                $follow[] = $g['label'];
                $seen[$key($g['label'])] = true;
            }
        }
        foreach ($b['blockers'] as $code => $label) {
            // the quotation's own stop for the same thing is said once
            if (!isset($a['blockers'][$code]) && !isset($seen[$key($label)])) {
                $follow[] = $label;
                $seen[$key($label)] = true;
            }
        }
        $cleared = [];
        foreach ($a['blockers'] as $code => $label) {
            if (!isset($b['blockers'][$code])) {
                $cleared[] = $label;
            }
        }
        return [
            'connected'  => $out,
            'follow_ups' => array_values(array_unique($follow)),
            'cleared'    => array_values(array_unique($cleared)),
            'cost'       => ['before' => $a['cost_usd'], 'after' => $b['cost_usd'], 'blockers_before' => count($a['blockers']), 'blockers_after' => count($b['blockers'])],
        ];
    }

    private static function mix(array $m): string
    {
        $w = [];
        foreach (['sgl' => 'SGL', 'dbl' => 'DBL', 'twn' => 'TWN', 'tpl' => 'TPL'] as $k => $l) {
            if ((int) ($m[$k] ?? 0) > 0) {
                $w[] = (int) $m[$k] . ' ' . $l;
            }
        }
        return $w ? implode(' · ', $w) : 'not set';
    }

    // =====================================================================
    //  What other parts of TourFlow ask
    // =====================================================================

    /**
     * The Edit Requests of one inquiry, in short, for the inquiry itself: how many are waiting, the
     * last one applied (reading the email again is switched off after it), and whether Confirm Tour
     * waits for a newer quotation version because of one.
     */
    public static function summary($db, array $it): ?array
    {
        if (!self::installed($db) || empty($it['id'])) {
            return null;
        }
        try {
            $rows = $db->query('SELECT id, seq, status, reason, requested_by, requested_at, confirmed_by, confirmed_at, quote_mark FROM ' . self::TABLE . ' WHERE intake_id = ? ORDER BY seq', [(int) $it['id']])->getResultArray();
        } catch (\Throwable $e) {
            return null;
        }
        $last = null;
        $count = ['open' => 0, 'waiting' => 0, 'applied' => 0, 'cancelled' => 0];
        $waiting = [];
        foreach ($rows as $r) {
            $count[$r['status']] = ($count[$r['status']] ?? 0) + 1;
            // the last one applied: the one applied over the newest quotation version, then the latest
            $rank = static fn ($x) => [(int) ($x['quote_mark'] ?? -1), (string) $x['confirmed_at'], (int) $x['seq']];
            if ($r['status'] === 'applied' && ($last === null || $rank($r) >= $rank($last))) {
                $last = $r;
            }
            if ($r['status'] === 'waiting') {
                $waiting[] = ['id' => (int) $r['id'], 'seq' => (int) $r['seq'], 'reason' => $r['reason'], 'by' => $r['requested_by'], 'at' => $r['requested_at']];
            }
        }
        $stale = self::staleIssue($it, $last);
        $stand = self::standing($it);
        return [
            'counts' => $count,
            'standing' => $stand['state'],
            'standing_why' => $stand['why'],
            'waiting' => $waiting,
            'last_applied' => $last ? ['seq' => (int) $last['seq'], 'by' => $last['confirmed_by'], 'at' => $last['confirmed_at']] : null,
            'read_blocked' => $last !== null,
            'stale_issue' => $stale,
        ];
    }

    /**
     * The issued version made before the last Edit Request applied, or null. A version stands for the
     * inquiry as it was when it was made (its costing is frozen then), so the one confirmed as the sale
     * has to be made after the amendment — issuing an older draft afterwards is not enough. Told by the
     * versions' own order, never by the clock alone: two things done in the same second are not ordered.
     */
    private static function staleIssue(array $it, ?array $last): ?array
    {
        if ($last === null) {
            return null;
        }
        $issued = null;
        foreach ((array) ($it['quotes'] ?? []) as $q) {
            if ((string) $q['status'] === 'issued' && ($issued === null || (int) $q['version'] > (int) $issued['version'])) {
                $issued = $q;
            }
        }
        if ($issued === null) {
            return null;   // no version issued at all: Confirm Tour already waits for one
        }
        $mark = $last['quote_mark'] ?? null;
        $older = $mark !== null && isset($issued['id'])
            ? (int) $issued['id'] <= (int) $mark
            : (string) $issued['issued_at'] <= (string) $last['confirmed_at'];
        if (!$older) {
            return null;
        }
        return ['version' => (int) $issued['version'], 'issued_at' => $issued['issued_at'], 'seq' => (int) $last['seq'], 'applied_at' => $last['confirmed_at']];
    }

    /** For Confirm Tour: the stale issue, read straight from the tables. */
    public static function confirmBlocker($db, int $intakeId): ?string
    {
        if (!self::installed($db)) {
            return null;
        }
        try {
            $last = $db->query('SELECT seq, confirmed_at, quote_mark FROM ' . self::TABLE . " WHERE intake_id = ? AND status = 'applied' ORDER BY COALESCE(quote_mark, -1) DESC, confirmed_at DESC, seq DESC LIMIT 1", [$intakeId])->getRowArray();
            if (!$last) {
                return null;
            }
            $quotes = $db->query("SELECT id, version, status, issued_at FROM tf_quote WHERE intake_id = ? AND status = 'issued'", [$intakeId])->getResultArray();
        } catch (\Throwable $e) {
            return null;
        }
        $s = self::staleIssue(['quotes' => $quotes], $last);
        if ($s === null) {
            return null;
        }
        return 'a quotation version made and issued after Edit Request #' . $s['seq'] . ' (applied ' . date('j M Y H:i', strtotime((string) $s['applied_at'])) . ') — version ' . $s['version'] . ' was made before it. Go for Quotation, then Issue the new version';
    }

    /** Read again is switched off once an Edit Request is applied: the reason, or null. */
    public static function readBlocked($db, int $intakeId): ?string
    {
        if (!self::installed($db)) {
            return null;
        }
        try {
            $r = $db->query('SELECT seq, confirmed_by, confirmed_at FROM ' . self::TABLE . " WHERE intake_id = ? AND status = 'applied' ORDER BY confirmed_at DESC LIMIT 1", [$intakeId])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        if (!$r) {
            return null;
        }
        return 'Reading the email again is switched off on this inquiry: Edit Request #' . $r['seq'] . ' was applied' . ($r['confirmed_by'] ? ' by ' . $r['confirmed_by'] : '') . ($r['confirmed_at'] ? ' on ' . date('j M Y', strtotime((string) $r['confirmed_at'])) : '') . ', and a new reading would rebuild what came from the email and could undo it. The original email stays exactly as it was pasted; make further changes with an Edit Request.';
    }

    /**
     * The dates an applied Edit Request settled against the email: field => [value, seq, by, at].
     * A date counts as settled while the inquiry still holds the value the request confirmed.
     */
    public static function dateCoverage($db, int $intakeId): array
    {
        if (!self::installed($db)) {
            return [];
        }
        $out = [];
        try {
            $rows = $db->query('SELECT seq, confirmed_by, confirmed_at, result_json FROM ' . self::TABLE . " WHERE intake_id = ? AND status = 'applied' ORDER BY confirmed_at, seq", [$intakeId])->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as $r) {
            $res = json_decode((string) $r['result_json'], true) ?: [];
            foreach ((array) ($res['intents'] ?? []) as $i) {
                if (($i['kind'] ?? '') === 'field' && in_array($i['field'] ?? '', ['arrival_date', 'departure_date'], true) && !empty($i['to_raw'])) {
                    $out[$i['field']] = ['value' => (string) $i['to_raw'], 'seq' => (int) $r['seq'], 'by' => $r['confirmed_by'], 'at' => $r['confirmed_at']];
                }
            }
        }
        return $out;
    }

    /** Requests saved on locked tours, waiting for Management to open them: for the Tour Board and Tour Desk Admin. */
    public static function waiting($db, ?array $intakeIds = null): array
    {
        if (!self::installed($db)) {
            return [];
        }
        $where = "r.status = 'waiting'";
        $binds = [];
        if ($intakeIds !== null) {
            if ($intakeIds === []) {
                return [];
            }
            $where .= ' AND r.intake_id IN (' . implode(',', array_fill(0, count($intakeIds), '?')) . ')';
            $binds = array_map('intval', $intakeIds);
        }
        try {
            $rows = $db->query('SELECT r.id, r.intake_id, r.seq, r.pasted_text, r.change_to, r.reason, r.requested_by, r.requested_at, i.inquiry_no, i.tour_name, i.status AS inquiry_status
                                FROM ' . self::TABLE . ' r JOIN tf_intake i ON i.id = r.intake_id WHERE ' . $where . ' ORDER BY r.requested_at', $binds)->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        return $rows;
    }

    private static function cut(?string $s, int $n): ?string
    {
        if ($s === null) {
            return null;
        }
        return mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '…' : $s;
    }

    private static function oneLine(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s));
    }
}
