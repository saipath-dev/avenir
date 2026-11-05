<?php

namespace App\Libraries\TourFlow;

/**
 * Entrance Ticket Stock Control — tf-tickets-r1 (schema 37, point 8 of the approved list).
 *
 * The attractions whose entrance tickets the company buys in advance and holds (Sigiriya, Polonnaruwa …) are put
 * under control here, one record per attraction of the Entrance Fee master. Stock comes in as numbered tickets —
 * a batch received by range (prefix, first and last number) or as a list of numbers — and every ticket is one
 * row with one status: available → allocated (to one tour, one activity line, by the party size) → used; or void
 * (lost, damaged, returned to the authority) while still available. One number is never on two tours: the
 * allocation takes the lowest available numbers inside a transaction with the rows locked, and the number is
 * unique per attraction by constraint. Nothing is deleted; a release puts a ticket back to available and the
 * trail says so.
 *
 * An attraction can instead be marked "pay at gate" — the entrance is paid at the counter on the day — so an
 * activity line for it takes the Pay at gate status (its own status, never "nothing to arrange"), with the
 * amount the quotation carried shown beside it. The program never assumes either mode: an attraction nobody has
 * put under control is handled on the Activities desk exactly as before (request / confirm with the supplier).
 *
 * One menu URL = one privilege: the Ticket Stock screen (tour/ticket_stock) receives stock, voids numbers and
 * reads the counts; allocating to a tour, releasing and marking used happen on the Activities desk, under the
 * Hotel Bookings privilege that desk has always had.
 *
 * tf-tickets-r2 (schema 38): each batch is also a purchase — the date it was bought / paid for and the amount paid
 * for the whole batch, with its currency, and the quantity bought, which must agree with the numbers received. A
 * top-up is simply the next batch: earlier batches, their payments and every ticket's history stay as they were.
 * The attraction carries the totals: tickets received, available, allocated, used, void, and the amount paid.
 */
class TicketStock
{
    public const CONTROLS = ['stock', 'pay_at_gate'];
    public const TICKET_STATUSES = ['available', 'allocated', 'used', 'void'];
    public const MAX_BATCH = 1000;

    private $db;
    private string $user;
    private ?bool $hasTables = null;
    private ?bool $hasPay = null;

    public function __construct($db, string $user)
    {
        $this->db   = $db;
        $this->user = $user;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private static function cut($v, int $len): ?string
    {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : mb_substr($v, 0, $len);
    }

    /** Schema 37 is on this install: the three tables exist. Read once. */
    public function available(): bool
    {
        if ($this->hasTables === null) {
            try {
                $this->hasTables = (bool) $this->db->query("SHOW TABLES LIKE 'tf_ticket'")->getResultArray()
                    && (bool) $this->db->query("SHOW TABLES LIKE 'tf_ticket_attraction'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasTables = false;
            }
        }
        return $this->hasTables;
    }

    /** Schema 38 is on this install: a batch carries its purchase date and the amount paid. Read once. */
    public function hasPurchase(): bool
    {
        if ($this->hasPay === null) {
            try {
                $this->hasPay = $this->available() && (bool) $this->db->query("SHOW COLUMNS FROM tf_ticket_batch LIKE 'amount_paid'")->getResultArray()
                    && (bool) $this->db->query("SHOW COLUMNS FROM tf_ticket_batch LIKE 'paid_on'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasPay = false;
            }
        }
        return $this->hasPay;
    }

    private function need(): void
    {
        if (!$this->available()) {
            throw new \RuntimeException('Ticket stock control is not installed on this site yet; run Install now from the Inquiries screen.');
        }
    }

    private function log(string $entity, int $id, ?int $tourId, string $action, string $summary): void
    {
        try {
            if (!$this->db->query("SHOW TABLES LIKE 'tf_activity'")->getResultArray()) {
                return;
            }
            (new Activity($this->db, $this->user))->record($entity, $id, null, $tourId, $action, $summary);
        } catch (\Throwable $e) {
            // the log never stops the work
        }
    }

    // =====================================================================
    //  The attractions under control
    // =====================================================================

    /** schema 39: the attractions are the places of the Place to Visit / Entrance Fee Master (tf_ticket_attraction.place_id). */
    private function byPlace(): bool
    {
        try {
            return EntranceMaster::installed($this->db) && (bool) $this->db->query("SHOW COLUMNS FROM `tf_ticket_attraction` LIKE 'place_id'")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * The attractions that can be put under control, for the picker: id, name, code, fee, direct_payment, and whether
     * already under control. Schema 39: the places of the Place to Visit / Entrance Fee Master (keyed "p<id>"), with
     * the master's entrance rate today — one master, not a second list; before it, the old Entrance Fee list.
     */
    public function feeMaster(): array
    {
        $this->need();
        $out = [];
        if ($this->byPlace()) {
            $under = [];
            foreach ($this->db->query('SELECT place_id, id FROM tf_ticket_attraction WHERE place_id IS NOT NULL')->getResultArray() as $r) {
                $under[(int) $r['place_id']] = (int) $r['id'];
            }
            foreach ((new EntranceMaster($this->db))->places() as $p) {
                if ($p['merged_into'] !== null || ($p['active'] !== 1 && !isset($under[$p['id']]))) {
                    continue;
                }
                $cur = $p['current'];
                $out[] = ['fee_id' => 'p' . $p['id'], 'place_id' => $p['id'], 'name' => $p['name'], 'code' => $p['code'], 'location' => $p['destination'] ?: $p['location'],
                    'fee_usd' => EntranceMaster::rateKind($cur) === 'charged' && $cur['currency'] === 'USD' ? $cur['adult_rate'] : null, 'rate_text' => $p['status_text'], 'status' => $p['status'],
                    'direct_payment' => $p['pay_mode'] === 'direct', 'attraction_id' => $under[$p['id']] ?? null];
            }
            return $out;
        }
        $under = [];
        foreach ($this->db->query('SELECT fee_id, id FROM tf_ticket_attraction')->getResultArray() as $r) {
            $under[(int) $r['fee_id']] = (int) $r['id'];
        }
        try {
            foreach ($this->db->query('SELECT id, name, code, location, entranceFee, direct_payment FROM entrance_fee ORDER BY name')->getResultArray() as $r) {
                $out[] = ['fee_id' => (int) $r['id'], 'name' => trim((string) $r['name']), 'code' => trim((string) ($r['code'] ?? '')), 'location' => trim((string) ($r['location'] ?? '')),
                    'fee_usd' => $r['entranceFee'] === null ? null : (float) $r['entranceFee'], 'direct_payment' => (int) ($r['direct_payment'] ?? 0) === 1,
                    'attraction_id' => $under[(int) $r['id']] ?? null];
            }
        } catch (\Throwable $e) {
            // no entrance fee master on this install
        }
        return $out;
    }

    /** Every attraction under control with its counts: available, allocated, used, void; low when available is under the mark. */
    public function attractions(bool $activeOnly = false): array
    {
        $this->need();
        $rows = $this->db->query(
            "SELECT a.*,
                    SUM(t.status = 'available') AS n_available, SUM(t.status = 'allocated') AS n_allocated, SUM(t.status = 'used') AS n_used, SUM(t.status = 'void') AS n_void,
                    MIN(CASE WHEN t.status = 'available' THEN t.sort_no END) AS next_no
             FROM tf_ticket_attraction a LEFT JOIN tf_ticket t ON t.attraction_id = a.id" . ($activeOnly ? ' WHERE a.active = 1' : '') . '
             GROUP BY a.id ORDER BY a.active DESC, a.name'
        )->getResultArray();
        $paid = $this->purchases(array_map(static fn ($r) => (int) $r['id'], $rows));
        $out = [];
        foreach ($rows as $r) {
            $out[] = $this->shapeAttraction($r, $paid[(int) $r['id']] ?? null);
        }
        return $out;
    }

    /**
     * tf-tickets-r2: the purchases of each attraction — how many batches, and the amount paid per currency (a batch
     * received before schema 38, or without an amount, is counted as such: never guessed from the cost each).
     *
     * @param int[] $ids
     * @return array<int, array{batches:int, paid:array<string,float>, unpaid:int}>
     */
    private function purchases(array $ids): array
    {
        $out = [];
        if (!$ids) {
            return $out;
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pay = $this->hasPurchase();
        $rows = $this->db->query(
            'SELECT attraction_id, ' . ($pay ? "IFNULL(currency, '') AS cur, COUNT(*) AS n, SUM(amount_paid IS NULL) AS n_unpaid, SUM(amount_paid) AS paid" : "'' AS cur, COUNT(*) AS n, COUNT(*) AS n_unpaid, NULL AS paid")
            . " FROM tf_ticket_batch WHERE attraction_id IN ({$in}) GROUP BY attraction_id" . ($pay ? ', cur' : ''),
            $ids
        )->getResultArray();
        foreach ($rows as $r) {
            $id = (int) $r['attraction_id'];
            $out[$id] ??= ['batches' => 0, 'paid' => [], 'unpaid' => 0];
            $out[$id]['batches'] += (int) $r['n'];
            $out[$id]['unpaid']  += (int) $r['n_unpaid'];
            if ($r['paid'] !== null) {
                $cur = $r['cur'] !== '' ? $r['cur'] : '—';
                $out[$id]['paid'][$cur] = round(($out[$id]['paid'][$cur] ?? 0) + (float) $r['paid'], 2);
            }
        }
        return $out;
    }

    private function shapeAttraction(array $r, ?array $paid = null): array
    {
        $r['id']          = (int) $r['id'];
        $r['fee_id']      = $r['fee_id'] === null ? null : (int) $r['fee_id'];
        $r['place_id']    = isset($r['place_id']) && $r['place_id'] !== null ? (int) $r['place_id'] : null;
        $r['active']      = (int) $r['active'] === 1;
        $r['low_mark']    = $r['low_mark'] === null ? null : (int) $r['low_mark'];
        $r['n_available'] = (int) ($r['n_available'] ?? 0);
        $r['n_allocated'] = (int) ($r['n_allocated'] ?? 0);
        $r['n_used']      = (int) ($r['n_used'] ?? 0);
        $r['n_void']      = (int) ($r['n_void'] ?? 0);
        // tf-tickets-r2: every ticket ever received, whatever has happened to it since, and the purchases behind them
        $r['n_received']  = $r['n_available'] + $r['n_allocated'] + $r['n_used'] + $r['n_void'];
        $r['n_batches']   = (int) ($paid['batches'] ?? 0);
        $r['paid']        = $paid['paid'] ?? [];
        $r['n_unpaid']    = (int) ($paid['unpaid'] ?? 0);
        $r['low']         = $r['control'] === 'stock' && $r['active'] && $r['low_mark'] !== null && $r['n_available'] <= $r['low_mark'];
        $r['empty']       = $r['control'] === 'stock' && $r['active'] && $r['n_available'] === 0;
        return $r;
    }

    public function attraction(int $id): array
    {
        $this->need();
        $r = $this->db->query(
            "SELECT a.*, SUM(t.status = 'available') AS n_available, SUM(t.status = 'allocated') AS n_allocated, SUM(t.status = 'used') AS n_used, SUM(t.status = 'void') AS n_void,
                    MIN(CASE WHEN t.status = 'available' THEN t.sort_no END) AS next_no
             FROM tf_ticket_attraction a LEFT JOIN tf_ticket t ON t.attraction_id = a.id WHERE a.id = ? GROUP BY a.id",
            [$id]
        )->getRowArray();
        if (!$r) {
            throw new \RuntimeException('That attraction is not under ticket control.');
        }
        return $this->shapeAttraction($r, $this->purchases([$id])[$id] ?? null);
    }

    /** The attraction a fee of the master is under, or null when nobody has put it under control. */
    public function attractionForFee(?int $feeId): ?array
    {
        return $this->attractionFor(null, $feeId);
    }

    /**
     * The attraction an activity line is under, or null: schema 39, by the place of the Place to Visit / Entrance Fee
     * Master it is; a line from before (its old record only) by that record, or the place the record was imported into.
     */
    public function attractionFor(?int $placeId, ?int $feeId): ?array
    {
        if ((!$placeId && !$feeId) || !$this->available()) {
            return null;
        }
        $byPlace = $this->byPlace();
        if ($byPlace && !$placeId && $feeId) {
            try {
                $t = $this->db->query("SELECT place_id FROM tf_place_import WHERE source = 'old_list' AND source_id = ? AND place_id IS NOT NULL", [(string) $feeId])->getRowArray();
                $placeId = $t ? (int) $t['place_id'] : null;
            } catch (\Throwable $e) {
                $placeId = null;
            }
        }
        $r = null;
        if ($byPlace && $placeId) {
            // a merged place is read as the place it was merged into
            $guard = 0;
            while ($placeId && $guard++ < 5 && ($m = $this->db->query('SELECT merged_into FROM tf_place WHERE id = ?', [$placeId])->getRowArray()) && $m['merged_into'] !== null) {
                $placeId = (int) $m['merged_into'];
            }
            $r = $this->db->query('SELECT id FROM tf_ticket_attraction WHERE place_id = ? LIMIT 1', [$placeId])->getRowArray();
        }
        if (!$r && $feeId) {
            $r = $this->db->query('SELECT id FROM tf_ticket_attraction WHERE fee_id = ? LIMIT 1', [$feeId])->getRowArray();
        }
        return $r ? $this->attraction((int) $r['id']) : null;
    }

    /**
     * Put an attraction under control: from stock (numbered tickets held in advance) or pay at gate. Schema 39: a
     * place in use in the Place to Visit / Entrance Fee Master ($placeId); before it, a row of the Entrance Fee list
     * ($feeId). The name and code are copied from the master as it stands; nothing is invented.
     */
    public function addAttraction(int $feeId, string $control, $lowMark = null, ?string $note = null, $unitCost = null, ?string $currency = null, ?int $placeId = null): array
    {
        $this->need();
        if (!in_array($control, self::CONTROLS, true)) {
            throw new \InvalidArgumentException('Choose how the entrance is handled: tickets from stock, or pay at gate.');
        }
        if ($this->byPlace()) {
            $pl = $placeId ? $this->db->query('SELECT id, name, code, active, merged_into FROM tf_place WHERE id = ?', [$placeId])->getRowArray() : null;
            if (!$pl) {
                throw new \InvalidArgumentException('Choose an attraction from the Place to Visit / Entrance Fee Master.');
            }
            if ((int) $pl['active'] !== 1 || $pl['merged_into'] !== null) {
                throw new \InvalidArgumentException(trim((string) $pl['name']) . ' is not in use in the Place to Visit / Entrance Fee Master.');
            }
            if ($this->db->query('SELECT id FROM tf_ticket_attraction WHERE place_id = ?', [(int) $pl['id']])->getRowArray()) {
                throw new \InvalidArgumentException(trim((string) $pl['name']) . ' is under control already.');
            }
            $this->db->query(
                'INSERT INTO tf_ticket_attraction (fee_id, place_id, fee_code, name, control, low_mark, unit_cost, currency, note, active, created_at, created_by, updated_at, updated_by) VALUES (NULL,?,?,?,?,?,?,?,?,1,?,?,?,?)',
                [(int) $pl['id'], self::cut($pl['code'], 100), self::cut($pl['name'], 255) ?? 'attraction', $control, self::intOrNull($lowMark), self::moneyOrNull($unitCost), self::cut($currency, 3), self::cut($note, 500), $this->now(), $this->user, $this->now(), $this->user]
            );
            $id = (int) $this->db->insertID();
            $this->log('ticket_attraction', $id, null, 'added', trim((string) $pl['name']) . ' put under ticket control: ' . ($control === 'stock' ? 'tickets from stock' : 'pay at gate') . '.');
            return $this->attraction($id);
        }
        $fee = $this->db->query('SELECT id, name, code, entranceFee FROM entrance_fee WHERE id = ?', [$feeId])->getRowArray();
        if (!$fee) {
            throw new \InvalidArgumentException('Choose an attraction from the Entrance Fee master.');
        }
        if ($this->db->query('SELECT id FROM tf_ticket_attraction WHERE fee_id = ?', [$feeId])->getRowArray()) {
            throw new \InvalidArgumentException(trim((string) $fee['name']) . ' is under control already.');
        }
        $this->db->query(
            'INSERT INTO tf_ticket_attraction (fee_id, fee_code, name, control, low_mark, unit_cost, currency, note, active, created_at, created_by, updated_at, updated_by) VALUES (?,?,?,?,?,?,?,?,1,?,?,?,?)',
            [$feeId, self::cut($fee['code'], 100), self::cut($fee['name'], 255) ?? 'attraction', $control, self::intOrNull($lowMark), self::moneyOrNull($unitCost), self::cut($currency, 3), self::cut($note, 500), $this->now(), $this->user, $this->now(), $this->user]
        );
        $id = (int) $this->db->insertID();
        $this->log('ticket_attraction', $id, null, 'added', trim((string) $fee['name']) . ' put under ticket control: ' . ($control === 'stock' ? 'tickets from stock' : 'pay at gate') . '.');
        return $this->attraction($id);
    }

    public function updateAttraction(int $id, array $d): array
    {
        $a = $this->attraction($id);
        $sets = [];
        $binds = [];
        $notes = [];
        if (array_key_exists('control', $d)) {
            $c = (string) $d['control'];
            if (!in_array($c, self::CONTROLS, true)) {
                throw new \InvalidArgumentException('Choose how the entrance is handled: tickets from stock, or pay at gate.');
            }
            if ($c !== $a['control']) {
                if ($c === 'pay_at_gate' && ($a['n_available'] > 0 || $a['n_allocated'] > 0)) {
                    throw new \InvalidArgumentException($a['name'] . ' still holds ' . ($a['n_available'] + $a['n_allocated']) . ' ticket(s) available or allocated; it cannot be marked pay at gate while tickets are in hand.');
                }
                $sets[] = 'control = ?';
                $binds[] = $c;
                $notes[] = 'handled as ' . ($c === 'stock' ? 'tickets from stock' : 'pay at gate');
            }
        }
        if (array_key_exists('low_mark', $d)) {
            $sets[] = 'low_mark = ?';
            $binds[] = self::intOrNull($d['low_mark']);
            $notes[] = 'low mark ' . (self::intOrNull($d['low_mark']) ?? 'none');
        }
        if (array_key_exists('unit_cost', $d)) {
            $sets[] = 'unit_cost = ?';
            $binds[] = self::moneyOrNull($d['unit_cost']);
        }
        if (array_key_exists('currency', $d)) {
            $sets[] = 'currency = ?';
            $binds[] = self::cut($d['currency'], 3);
        }
        if (array_key_exists('note', $d)) {
            $sets[] = 'note = ?';
            $binds[] = self::cut($d['note'], 500);
        }
        if (array_key_exists('active', $d)) {
            $on = (int) (bool) $d['active'];
            if ($on !== (int) $a['active']) {
                if ($on === 0 && $a['n_allocated'] > 0) {
                    throw new \InvalidArgumentException($a['name'] . ' has ' . $a['n_allocated'] . ' ticket(s) allocated to tours; mark them used or release them before taking it off the list.');
                }
                $sets[] = 'active = ?';
                $binds[] = $on;
                $notes[] = $on ? 'back on the list' : 'off the list (kept)';
            }
        }
        if ($sets) {
            $sets[] = 'updated_at = ?';
            $binds[] = $this->now();
            $sets[] = 'updated_by = ?';
            $binds[] = $this->user;
            $binds[] = $id;
            $this->db->query('UPDATE tf_ticket_attraction SET ' . implode(', ', $sets) . ' WHERE id = ?', $binds);
            if ($notes) {
                $this->log('ticket_attraction', $id, null, 'changed', $a['name'] . ': ' . implode('; ', $notes) . '.');
            }
        }
        return $this->attraction($id);
    }

    // =====================================================================
    //  Stock in: batches of numbered tickets
    // =====================================================================

    /**
     * Receive tickets: a range (prefix, first and last number — zero padding kept as typed) or a list of numbers.
     * Every number must be new for the attraction; the batch is refused whole when any is already on file, so
     * no ticket is ever counted twice.
     */
    public function receive(int $attractionId, array $d): array
    {
        $a = $this->attraction($attractionId);
        if ($a['control'] !== 'stock') {
            throw new \InvalidArgumentException($a['name'] . ' is pay at gate; there is no stock to receive. Change how it is handled first.');
        }
        if (!$a['active']) {
            throw new \InvalidArgumentException($a['name'] . ' is off the list; put it back before receiving stock.');
        }
        $numbers = self::numbersFrom($d);
        if (!$numbers) {
            throw new \InvalidArgumentException('Give the ticket numbers: a first and last number (with the printed prefix, if any), or a list of numbers.');
        }
        if (count($numbers) > self::MAX_BATCH) {
            throw new \InvalidArgumentException('That is ' . count($numbers) . ' tickets; receive at most ' . self::MAX_BATCH . ' in one batch.');
        }
        $in   = implode(',', array_fill(0, count($numbers), '?'));
        $dups = $this->db->query("SELECT ticket_no FROM tf_ticket WHERE attraction_id = ? AND ticket_no IN ({$in})", array_merge([$attractionId], array_column($numbers, 'no')))->getResultArray();
        if ($dups) {
            $list = array_column($dups, 'ticket_no');
            throw new \InvalidArgumentException(count($list) . ' of these numbers are on file already for ' . $a['name'] . ' (' . implode(', ', array_slice($list, 0, 5)) . (count($list) > 5 ? ' …' : '') . '). Nothing was received; check the numbers.');
        }
        $receivedOn = self::dateOrNull($d['received_on'] ?? null) ?? date('Y-m-d');
        $currency   = self::cut(strtoupper(trim((string) ($d['currency'] ?? ''))), 3);
        // tf-tickets-r2 (schema 38): the batch is a purchase — the date bought / paid, the amount paid, the quantity bought
        $paidOn = null;
        $amount = null;
        if ($this->hasPurchase()) {
            $qty = trim((string) ($d['quantity'] ?? ''));
            if ($qty === '' || !ctype_digit($qty) || (int) $qty < 1) {
                throw new \InvalidArgumentException('Give the quantity purchased — the number of tickets bought in this batch. Nothing was received.');
            }
            if ((int) $qty !== count($numbers)) {
                throw new \InvalidArgumentException('The quantity purchased is ' . (int) $qty . ' but the numbers give ' . count($numbers) . ' ticket(s) (' . $numbers[0]['no'] . ' to ' . $numbers[count($numbers) - 1]['no'] . '). Check the numbers or the quantity; nothing was received.');
            }
            $paidOn = self::dateOrNull($d['paid_on'] ?? null);
            if ($paidOn === null) {
                throw new \InvalidArgumentException('Give the purchase / payment date of this batch. Nothing was received.');
            }
            if ($paidOn > date('Y-m-d')) {
                throw new \InvalidArgumentException('The purchase / payment date ' . $paidOn . ' is after today. Nothing was received.');
            }
            $rawAmount = trim((string) ($d['amount_paid'] ?? ''));
            $amount = self::moneyOrNull($rawAmount);
            if ($amount === null || $amount < 0) {
                throw new \InvalidArgumentException('Give the amount paid for this batch (0 for tickets that cost nothing). Nothing was received.');
            }
            if ($amount > 0 && $currency === null) {
                throw new \InvalidArgumentException('Give the currency of the amount paid. Nothing was received.');
            }
        }
        $cols  = ['attraction_id', 'received_on', 'prefix', 'from_no', 'to_no', 'count', 'unit_cost', 'currency', 'ref', 'note', 'created_at', 'created_by'];
        $binds = [$attractionId, $receivedOn, self::cut($d['prefix'] ?? null, 20), $numbers[0]['no'], $numbers[count($numbers) - 1]['no'], count($numbers),
                  self::moneyOrNull($d['unit_cost'] ?? null), $currency, self::cut($d['ref'] ?? null, 100), self::cut($d['note'] ?? null, 400), $this->now(), $this->user];
        if ($this->hasPurchase()) {
            array_push($cols, 'paid_on', 'amount_paid');
            array_push($binds, $paidOn, $amount);
        }
        $this->db->transStart();
        $this->db->query(
            'INSERT INTO tf_ticket_batch (' . implode(', ', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')',
            $binds
        );
        $batchId = (int) $this->db->insertID();
        foreach (array_chunk($numbers, 200) as $chunk) {
            $vals  = [];
            $binds = [];
            foreach ($chunk as $n) {
                $vals[] = '(?,?,?,?,?)';
                array_push($binds, $attractionId, $batchId, $n['no'], $n['sort'], 'available');
            }
            $this->db->query('INSERT INTO tf_ticket (attraction_id, batch_id, ticket_no, sort_no, status) VALUES ' . implode(',', $vals), $binds);
        }
        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('The batch could not be saved; nothing was received.');
        }
        $this->log('ticket_batch', $batchId, null, 'received', $a['name'] . ': ' . count($numbers) . ' ticket(s) received, ' . $numbers[0]['no'] . ' to ' . $numbers[count($numbers) - 1]['no']
            . ($paidOn !== null ? '; bought ' . $paidOn . ' for ' . ($currency ? $currency . ' ' : '') . number_format((float) $amount, 2) : '')
            . ($d['ref'] ?? null ? ' (' . trim((string) $d['ref']) . ')' : '') . '.');
        return ['batch_id' => $batchId, 'count' => count($numbers), 'first' => $numbers[0]['no'], 'last' => $numbers[count($numbers) - 1]['no'], 'attraction' => $this->attraction($attractionId)];
    }

    /** The numbers a receipt names: {prefix, from_no, to_no} as a range, or {numbers} as text (one per line or comma-separated). */
    public static function numbersFrom(array $d): array
    {
        $out = [];
        $list = trim((string) ($d['numbers'] ?? ''));
        if ($list !== '') {
            $seen = [];
            foreach (preg_split('/[\s,;]+/', $list) ?: [] as $raw) {
                $no = strtoupper(trim($raw));
                if ($no === '' || isset($seen[$no])) {
                    continue;
                }
                if (mb_strlen($no) > 60) {
                    throw new \InvalidArgumentException('A ticket number is at most 60 characters: ' . mb_substr($no, 0, 20) . '…');
                }
                $seen[$no] = true;
                $out[] = ['no' => $no, 'sort' => self::sortNo($no)];
            }
            usort($out, static fn ($x, $y) => [$x['sort'] ?? PHP_INT_MAX, $x['no']] <=> [$y['sort'] ?? PHP_INT_MAX, $y['no']]);
            return $out;
        }
        $from = trim((string) ($d['from_no'] ?? ''));
        $to   = trim((string) ($d['to_no'] ?? ''));
        if ($from === '' && $to === '') {
            return [];
        }
        if (!ctype_digit($from) || ($to !== '' && !ctype_digit($to))) {
            throw new \InvalidArgumentException('The first and last numbers are digits only; put any letters in the prefix box (printed before the number).');
        }
        $to = $to === '' ? $from : $to;
        if ((int) $to < (int) $from) {
            throw new \InvalidArgumentException('The last number is before the first.');
        }
        if ((int) $to - (int) $from + 1 > self::MAX_BATCH) {
            throw new \InvalidArgumentException('That range is ' . ((int) $to - (int) $from + 1) . ' tickets; receive at most ' . self::MAX_BATCH . ' in one batch.');
        }
        $prefix = strtoupper(trim((string) ($d['prefix'] ?? '')));
        $width  = strlen($from);
        for ($i = (int) $from; $i <= (int) $to; $i++) {
            $no = $prefix . str_pad((string) $i, $width, '0', STR_PAD_LEFT);
            $out[] = ['no' => $no, 'sort' => $i];
        }
        return $out;
    }

    private static function sortNo(string $no): ?int
    {
        return preg_match('/(\d+)\s*$/', $no, $m) ? (int) substr($m[1], -15) : null;
    }

    // =====================================================================
    //  Allocation to a tour's activity line
    // =====================================================================

    /** The tickets on an activity line, in number order. */
    public function ticketsFor(int $serviceJobId): array
    {
        if (!$this->available()) {
            return [];
        }
        return $this->db->query(
            'SELECT t.id, t.ticket_no, t.status, t.allocated_at, t.allocated_by, t.used_at, t.used_by, a.name AS attraction, a.id AS attraction_id
             FROM tf_ticket t JOIN tf_ticket_attraction a ON a.id = t.attraction_id WHERE t.service_job_id = ? ORDER BY t.sort_no, t.ticket_no',
            [$serviceJobId]
        )->getResultArray();
    }

    /** The tickets of every line of a tour, keyed by line id — for the Board, the record and the programme. */
    public function ticketsForTour(int $tourId): array
    {
        if (!$this->available()) {
            return [];
        }
        $out = [];
        foreach ($this->db->query('SELECT t.service_job_id, t.ticket_no, t.status, t.sort_no FROM tf_ticket t WHERE t.tour_id = ? ORDER BY t.service_job_id, t.sort_no, t.ticket_no', [$tourId])->getResultArray() as $r) {
            $out[(int) $r['service_job_id']][] = ['no' => $r['ticket_no'], 'status' => $r['status']];
        }
        return $out;
    }

    /** "10001–10003, 10007": consecutive numbers folded into a range. */
    public static function rangeText(array $numbers): string
    {
        $nos = [];
        foreach ($numbers as $n) {
            $nos[] = is_array($n) ? ($n['no'] ?? $n['ticket_no'] ?? '') : (string) $n;
        }
        $nos = array_values(array_filter($nos, static fn ($x) => $x !== ''));
        if (!$nos) {
            return '';
        }
        $parts = [];
        $start = $prev = $nos[0];
        $startN = $prevN = self::sortNo($nos[0]);
        $len = 1;
        $flush = static function () use (&$parts, &$start, &$prev, &$len) {
            $parts[] = $len >= 3 ? $start . '–' . $prev : ($len === 2 ? $start . ', ' . $prev : $start);
        };
        for ($i = 1, $c = count($nos); $i < $c; $i++) {
            $n = self::sortNo($nos[$i]);
            $samePrefix = $prevN !== null && $n !== null && preg_replace('/\d+\s*$/', '', $nos[$i]) === preg_replace('/\d+\s*$/', '', $prev) && strlen($nos[$i]) === strlen($prev);
            if ($samePrefix && $n === $prevN + 1) {
                $prev  = $nos[$i];
                $prevN = $n;
                $len++;
                continue;
            }
            $flush();
            $start = $prev = $nos[$i];
            $startN = $prevN = $n;
            $len = 1;
        }
        $flush();
        return implode(', ', $parts);
    }

    /**
     * Allocate tickets to an activity line: the lowest available numbers of the attraction, as many as the party
     * (or the count asked for), locked while they are taken so two desks can never be given the same number.
     * Returns the tickets now on the line. The caller moves the line's status and writes its own event.
     */
    public function allocate(int $serviceJobId, int $tourId, int $attractionId, int $count): array
    {
        $a = $this->attraction($attractionId);
        if ($a['control'] !== 'stock') {
            throw new \InvalidArgumentException($a['name'] . ' is pay at gate: there is no stock to allocate. Mark the line "pay at gate" instead.');
        }
        if (!$a['active']) {
            throw new \InvalidArgumentException($a['name'] . ' is off the Ticket Stock list; put it back before allocating.');
        }
        if ($count <= 0) {
            throw new \InvalidArgumentException('How many tickets? One per passenger, at least one.');
        }
        if ($count > 200) {
            throw new \InvalidArgumentException('At most 200 tickets on one line.');
        }
        $already = $this->ticketsFor($serviceJobId);
        foreach ($already as $t) {
            if ((int) $t['attraction_id'] !== $attractionId) {
                throw new \InvalidArgumentException('This line already holds tickets of ' . $t['attraction'] . '; release them before allocating another attraction\'s.');
            }
        }
        $this->db->transStart();
        $rows = $this->db->query(
            "SELECT id, ticket_no FROM tf_ticket WHERE attraction_id = ? AND status = 'available' ORDER BY sort_no IS NULL, sort_no, ticket_no LIMIT " . (int) $count . ' FOR UPDATE',
            [$attractionId]
        )->getResultArray();
        if (count($rows) < $count) {
            $this->db->transComplete();
            throw new \RuntimeException('Only ' . count($rows) . ' ' . $a['name'] . ' ticket(s) available; ' . $count . ' needed. Receive stock on Ticket Stock first — nothing was allocated.');
        }
        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        $in  = implode(',', $ids);
        $this->db->query("UPDATE tf_ticket SET status = 'allocated', tour_id = ?, service_job_id = ?, allocated_at = ?, allocated_by = ? WHERE id IN ({$in}) AND status = 'available'", [$tourId, $serviceJobId, $this->now(), $this->user]);
        $n = $this->db->affectedRows();
        $this->db->transComplete();
        if ($this->db->transStatus() === false || $n !== count($ids)) {
            throw new \RuntimeException('The tickets could not be allocated (another desk took them at the same moment). Try again.');
        }
        $this->log('ticket_attraction', $attractionId, $tourId, 'allocated', $a['name'] . ': ' . $n . ' ticket(s) ' . self::rangeText(array_column($rows, 'ticket_no')) . ' allocated to tour ' . $tourId . ', line ' . $serviceJobId . '.');
        return $this->ticketsFor($serviceJobId);
    }

    /** Put a line's allocated tickets back to stock (used ones stay used). Returns how many went back. */
    public function release(int $serviceJobId, ?string $why = null): int
    {
        if (!$this->available()) {
            return 0;
        }
        $rows = $this->db->query("SELECT t.id, t.ticket_no, t.tour_id, t.attraction_id, a.name FROM tf_ticket t JOIN tf_ticket_attraction a ON a.id = t.attraction_id WHERE t.service_job_id = ? AND t.status = 'allocated'", [$serviceJobId])->getResultArray();
        if (!$rows) {
            return 0;
        }
        $in = implode(',', array_map(static fn ($r) => (int) $r['id'], $rows));
        $this->db->query("UPDATE tf_ticket SET status = 'available', tour_id = NULL, service_job_id = NULL, allocated_at = NULL, allocated_by = NULL WHERE id IN ({$in}) AND status = 'allocated'");
        $this->log('ticket_attraction', (int) $rows[0]['attraction_id'], (int) $rows[0]['tour_id'], 'released', $rows[0]['name'] . ': ' . count($rows) . ' ticket(s) ' . self::rangeText(array_column($rows, 'ticket_no')) . ' back to stock from line ' . $serviceJobId . ($why ? ' — ' . trim($why) : '') . '.');
        return count($rows);
    }

    /** The line's allocated tickets were used at the gate. Returns how many. */
    public function markUsed(int $serviceJobId): int
    {
        $this->need();
        $rows = $this->db->query("SELECT t.id, t.ticket_no, t.tour_id, t.attraction_id, a.name FROM tf_ticket t JOIN tf_ticket_attraction a ON a.id = t.attraction_id WHERE t.service_job_id = ? AND t.status = 'allocated'", [$serviceJobId])->getResultArray();
        if (!$rows) {
            throw new \RuntimeException('No allocated tickets on this line to mark used.');
        }
        $in = implode(',', array_map(static fn ($r) => (int) $r['id'], $rows));
        $this->db->query("UPDATE tf_ticket SET status = 'used', used_at = ?, used_by = ? WHERE id IN ({$in}) AND status = 'allocated'", [$this->now(), $this->user]);
        $this->log('ticket_attraction', (int) $rows[0]['attraction_id'], (int) $rows[0]['tour_id'], 'used', $rows[0]['name'] . ': ' . count($rows) . ' ticket(s) ' . self::rangeText(array_column($rows, 'ticket_no')) . ' used (line ' . $serviceJobId . ').');
        return count($rows);
    }

    /** Every allocated ticket of a tour used — after the tour, from the Ticket Stock screen. Returns how many. */
    public function markTourUsed(int $tourId): int
    {
        $this->need();
        $rows = $this->db->query("SELECT id, ticket_no, attraction_id FROM tf_ticket WHERE tour_id = ? AND status = 'allocated'", [$tourId])->getResultArray();
        if (!$rows) {
            return 0;
        }
        $in = implode(',', array_map(static fn ($r) => (int) $r['id'], $rows));
        $this->db->query("UPDATE tf_ticket SET status = 'used', used_at = ?, used_by = ? WHERE id IN ({$in}) AND status = 'allocated'", [$this->now(), $this->user]);
        $this->log('ticket_attraction', (int) $rows[0]['attraction_id'], $tourId, 'used', count($rows) . ' ticket(s) of tour ' . $tourId . ' used: ' . self::rangeText(array_column($rows, 'ticket_no')) . '.');
        return count($rows);
    }

    /** A ticket still in stock is void — lost, damaged, returned to the authority. Kept, with why; never deleted. */
    public function void(int $ticketId, ?string $note): array
    {
        $this->need();
        $t = $this->db->query('SELECT t.*, a.name FROM tf_ticket t JOIN tf_ticket_attraction a ON a.id = t.attraction_id WHERE t.id = ?', [$ticketId])->getRowArray();
        if (!$t) {
            throw new \RuntimeException('No such ticket.');
        }
        if ($t['status'] !== 'available') {
            throw new \InvalidArgumentException('Ticket ' . $t['ticket_no'] . ' is ' . $t['status'] . '; only a ticket still in stock can be voided' . ($t['status'] === 'allocated' ? ' — release it from its tour first' : '') . '.');
        }
        $note = self::cut($note, 255);
        if ($note === null) {
            throw new \InvalidArgumentException('Say why the ticket is void (lost, damaged, returned …); it stays on the trail.');
        }
        $this->db->query("UPDATE tf_ticket SET status = 'void', void_at = ?, void_by = ?, void_note = ? WHERE id = ? AND status = 'available'", [$this->now(), $this->user, $note, $ticketId]);
        $this->log('ticket_attraction', (int) $t['attraction_id'], null, 'void', $t['name'] . ': ticket ' . $t['ticket_no'] . ' void — ' . $note . '.');
        return $this->attraction((int) $t['attraction_id']);
    }

    /** A void ticket found again: back to stock. */
    public function unvoid(int $ticketId, ?string $note): array
    {
        $this->need();
        $t = $this->db->query('SELECT t.*, a.name FROM tf_ticket t JOIN tf_ticket_attraction a ON a.id = t.attraction_id WHERE t.id = ?', [$ticketId])->getRowArray();
        if (!$t || $t['status'] !== 'void') {
            throw new \InvalidArgumentException('Only a void ticket can be put back to stock.');
        }
        $this->db->query("UPDATE tf_ticket SET status = 'available', void_at = NULL, void_by = NULL, void_note = NULL WHERE id = ? AND status = 'void'", [$ticketId]);
        $this->log('ticket_attraction', (int) $t['attraction_id'], null, 'unvoid', $t['name'] . ': ticket ' . $t['ticket_no'] . ' back to stock' . ($note ? ' — ' . trim($note) : '') . '.');
        return $this->attraction((int) $t['attraction_id']);
    }

    // =====================================================================
    //  Reading the stock
    // =====================================================================

    /** The tickets of an attraction, filtered by status and text, newest batch first within a status; at most 500. */
    public function tickets(int $attractionId, ?string $status = null, ?string $q = null, int $limit = 500): array
    {
        $this->need();
        $where = ['t.attraction_id = ?'];
        $binds = [$attractionId];
        if ($status !== null && $status !== '' && in_array($status, self::TICKET_STATUSES, true)) {
            $where[] = 't.status = ?';
            $binds[] = $status;
        }
        if ($q !== null && trim($q) !== '') {
            $where[] = '(t.ticket_no LIKE ? OR tr.tour_no LIKE ?)';
            $binds[] = '%' . trim($q) . '%';
            $binds[] = '%' . trim($q) . '%';
        }
        return $this->db->query(
            'SELECT t.id, t.ticket_no, t.status, t.batch_id, t.tour_id, t.service_job_id, t.allocated_at, t.allocated_by, t.used_at, t.used_by, t.void_at, t.void_by, t.void_note,
                    tr.tour_no, tr.tour_name, tr.arrival_date, j.title AS line_title, j.on_date AS line_date, j.pax AS line_pax
             FROM tf_ticket t LEFT JOIN tf_tour tr ON tr.id = t.tour_id LEFT JOIN tf_service_job j ON j.id = t.service_job_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY t.sort_no IS NULL, t.sort_no, t.ticket_no LIMIT ' . max(1, min(2000, $limit)),
            $binds
        )->getResultArray();
    }

    public function batches(int $attractionId): array
    {
        $this->need();
        return $this->db->query(
            "SELECT b.*, SUM(t.status = 'available') AS n_available, SUM(t.status = 'allocated') AS n_allocated, SUM(t.status = 'used') AS n_used, SUM(t.status = 'void') AS n_void
             FROM tf_ticket_batch b LEFT JOIN tf_ticket t ON t.batch_id = b.id WHERE b.attraction_id = ? GROUP BY b.id ORDER BY b.received_on DESC, b.id DESC",
            [$attractionId]
        )->getResultArray();
    }

    /** The tours holding allocated tickets (to mark used after the tour), with their numbers per attraction. */
    public function toursHolding(): array
    {
        $this->need();
        $rows = $this->db->query(
            "SELECT t.tour_id, tr.tour_no, tr.tour_name, tr.arrival_date, tr.departure_date, tr.stage, a.name AS attraction, t.ticket_no, t.sort_no
             FROM tf_ticket t JOIN tf_ticket_attraction a ON a.id = t.attraction_id LEFT JOIN tf_tour tr ON tr.id = t.tour_id
             WHERE t.status = 'allocated' ORDER BY tr.arrival_date, t.tour_id, a.name, t.sort_no, t.ticket_no"
        )->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $k = (int) $r['tour_id'];
            $out[$k] ??= ['tour_id' => $k, 'tour_no' => $r['tour_no'], 'tour_name' => $r['tour_name'], 'arrival_date' => $r['arrival_date'], 'departure_date' => $r['departure_date'], 'stage' => $r['stage'], 'over' => $r['departure_date'] !== null && $r['departure_date'] < date('Y-m-d'), 'by_attraction' => [], 'count' => 0];
            $out[$k]['by_attraction'][$r['attraction']][] = $r['ticket_no'];
            $out[$k]['count']++;
        }
        foreach ($out as &$t) {
            $t['lines'] = [];
            foreach ($t['by_attraction'] as $name => $nos) {
                $t['lines'][] = ['attraction' => $name, 'count' => count($nos), 'numbers' => self::rangeText($nos)];
            }
            unset($t['by_attraction']);
        }
        return array_values($out);
    }

    /** For the Tour Desk bar: the attractions under stock control that are low or empty — work for whoever buys tickets. */
    public function alerts(): int
    {
        if (!$this->available()) {
            return 0;
        }
        try {
            return count(array_filter($this->attractions(true), static fn ($a) => $a['low'] || $a['empty']));
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function intOrNull($v): ?int
    {
        $v = trim((string) ($v ?? ''));
        return $v === '' ? null : max(0, (int) $v);
    }

    private static function moneyOrNull($v): ?float
    {
        $v = trim((string) ($v ?? ''));
        return $v === '' || !is_numeric($v) ? null : round((float) $v, 2);
    }

    private static function dateOrNull($v): ?string
    {
        $v = trim((string) ($v ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }
}
