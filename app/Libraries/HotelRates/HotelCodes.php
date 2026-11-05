<?php

namespace App\Libraries\HotelRates;

/**
 * The hotel code — HTL-001, HTL-002, … — the stable operational reference of a hotel (Shantha, 6 Oct 2026).
 *
 * One number per hotel, given by the system, never typed by staff, never reused, never changed by a rename, kept by a
 * hotel that is archived, inactive or merged. The internal id (hr2_hotel.id) and the ledger account (6-9-11-…) go on
 * carrying the relationships; the code is what people read, search and say on the phone.
 *
 * Where the number comes from. The old Supplier master already numbered its hotel suppliers H001 … H331 from one
 * counter (invoice_no.hotel_code). That numbering is kept — HTL-001 is the hotel that was H001 — so the office's old
 * and new screens name the same hotel by the same number; the legacy column supplier.hotel_code is not touched. Hotels
 * with no legacy number, and every new hotel, take the next number from ONE shared sequence (hr2_sequence 'HTL'), so the
 * two spellings can never drift apart.
 *
 * Why it cannot duplicate. The sequence is advanced with a single atomic UPDATE (LAST_INSERT_ID(last_no + 1)), the
 * register hr2_hotel_code has UNIQUE keys on the number, the code and the hotel, and hr2_hotel.hotel_code is UNIQUE —
 * two people creating two hotels in the same second get two different numbers, or the second insert is refused and
 * retried with the next number.
 */
final class HotelCodes
{
    public const PREFIX   = 'HTL';
    public const SEQUENCE = 'HTL';
    public const WIDTH    = 3;          // HTL-001 … HTL-999, then HTL-1000: the width is a minimum, never a ceiling

    private $db;
    private string $user;

    public function __construct($db, string $user = 'system')
    {
        $this->db   = $db;
        $this->user = $user !== '' ? $user : 'system';
    }

    /** HTL-007 for 7. */
    public static function format(int $no): string
    {
        return self::PREFIX . '-' . str_pad((string) $no, self::WIDTH, '0', STR_PAD_LEFT);
    }

    /** 7 for "HTL-007", "htl 7", "H007" (the legacy spelling) or "7"; null for anything else. */
    public static function parse(?string $text): ?int
    {
        $t = strtoupper(trim((string) $text));
        if ($t === '') {
            return null;
        }
        if (preg_match('/^(?:HTL[\s-]*|H)?0*(\d{1,6})$/', $t, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    public function ready(): bool
    {
        try {
            return (bool) $this->db->query("SHOW TABLES LIKE 'hr2_hotel_code'")->getRowArray()
                && (bool) $this->db->query("SHOW COLUMNS FROM hr2_hotel LIKE 'hotel_code'")->getRowArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** The next number, atomically: two callers can never get the same one. */
    public function nextNumber(): int
    {
        $this->db->query('INSERT INTO hr2_sequence (name, last_no) VALUES (?, LAST_INSERT_ID(1)) ON DUPLICATE KEY UPDATE last_no = LAST_INSERT_ID(last_no + 1)', [self::SEQUENCE]);
        $no = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray()['n'] ?? 0);
        if ($no <= 0) {
            throw new \RuntimeException('The hotel code sequence could not be advanced.');
        }
        return $no;
    }

    /** The highest number given so far (0 when none). */
    public function lastNumber(): int
    {
        return (int) ($this->db->query('SELECT last_no FROM hr2_sequence WHERE name = ?', [self::SEQUENCE])->getRowArray()['last_no'] ?? 0);
    }

    /** Make sure the sequence stands at least at $no (after a backfill that used legacy numbers). */
    private function raiseTo(int $no): void
    {
        $this->db->query('INSERT INTO hr2_sequence (name, last_no) VALUES (?, ?) ON DUPLICATE KEY UPDATE last_no = GREATEST(last_no, VALUES(last_no))', [self::SEQUENCE, $no]);
    }

    /** The code of a hotel, or null when it has none yet. */
    public function codeOf(int $hotelId): ?string
    {
        $r = $this->db->query('SELECT hotel_code FROM hr2_hotel WHERE id = ?', [$hotelId])->getRowArray();
        return ($r['hotel_code'] ?? null) ?: null;
    }

    /** The hotel a code names (merged hotels included — the code stays theirs), or null. */
    public function hotelByCode(string $code): ?array
    {
        $no = self::parse($code);
        if ($no === null) {
            return null;
        }
        return $this->db->query('SELECT h.* FROM hr2_hotel_code c JOIN hr2_hotel h ON h.id = c.hotel_id WHERE c.no = ?', [$no])->getRowArray() ?: null;
    }

    /**
     * Give a hotel its code if it has none: the number reserved for its ledger account (a legacy H-number, or a number
     * drawn when the supplier was created on the old screen), else the next number. Returns the code either way.
     */
    public function assign(int $hotelId, string $how = 'auto'): string
    {
        $h = $this->db->query('SELECT id, name, hotel_code, primary_sup_acc_no FROM hr2_hotel WHERE id = ?', [$hotelId])->getRowArray();
        if (!$h) {
            throw new \RuntimeException("Hotel {$hotelId} not found.");
        }
        if (!empty($h['hotel_code'])) {
            return (string) $h['hotel_code'];
        }
        $existing = $this->db->query('SELECT code FROM hr2_hotel_code WHERE hotel_id = ?', [$hotelId])->getRowArray();
        if ($existing) {                                           // the register knows it already: mirror the code onto the hotel
            $this->db->query('UPDATE hr2_hotel SET hotel_code = ? WHERE id = ?', [$existing['code'], $hotelId]);
            return (string) $existing['code'];
        }
        $accs = array_map(static fn ($r) => trim((string) $r['sup_acc_no']), $this->db->query('SELECT sup_acc_no FROM hr2_hotel_account WHERE hotel_id = ? ORDER BY is_primary DESC', [$hotelId])->getResultArray());
        if ($h['primary_sup_acc_no']) {
            array_unshift($accs, trim((string) $h['primary_sup_acc_no']));
        }
        $accs = array_values(array_unique(array_filter($accs)));
        // 1. a number already reserved for one of its accounts (the supplier was created on the old screen, or a legacy H-number)
        $no = null;
        foreach ($accs as $acc) {
            $r = $this->db->query('SELECT no FROM hr2_hotel_code WHERE sup_acc_no = ? AND hotel_id IS NULL', [$acc])->getRowArray();
            if ($r) {
                $no = (int) $r['no'];
                $this->db->query('UPDATE hr2_hotel_code SET hotel_id = ?, hotel_name_at = ?, how = ?, assigned_at = ?, assigned_by = ? WHERE no = ?', [$hotelId, $h['name'], $how, date('Y-m-d H:i:s'), $this->user, $no]);
                break;
            }
            $legacy = $this->legacyNumberFor($acc);
            if ($legacy !== null && !$this->numberTaken($legacy)) {
                $no = $legacy;
                $this->insertRegister($no, $hotelId, $acc, (string) $h['name'], $how . '-legacy');
                $this->raiseTo($no);
                break;
            }
        }
        // 2. else the next number — retried if two callers collide on the unique keys
        if ($no === null) {
            for ($try = 0; $try < 5; $try++) {
                $candidate = $this->nextNumber();
                try {
                    $this->insertRegister($candidate, $hotelId, $accs[0] ?? null, (string) $h['name'], $how);
                    $no = $candidate;
                    break;
                } catch (\Throwable $e) {
                    if (!self::isDuplicate($e)) {
                        throw $e;
                    }
                }
            }
            if ($no === null) {
                throw new \RuntimeException('A hotel code could not be given (the register refused five numbers in a row).');
            }
        }
        $code = self::format($no);
        $this->db->query('UPDATE hr2_hotel SET hotel_code = ? WHERE id = ?', [$code, $hotelId]);
        return $code;
    }

    /**
     * Reserve a number for a supplier account before its hotel record exists (the old Supplier master creating a hotel
     * supplier): the number the legacy screen prints as H### is the same one the hotel gets as HTL-### later.
     */
    public function reserveForAccount(string $supAccNo, string $name, string $how = 'legacy-screen'): int
    {
        $supAccNo = trim($supAccNo);
        $r = $this->db->query('SELECT no, hotel_id FROM hr2_hotel_code WHERE sup_acc_no = ? ORDER BY hotel_id IS NULL DESC LIMIT 1', [$supAccNo])->getRowArray();
        if ($r && $r['hotel_id'] === null) {
            return (int) $r['no'];                                 // reserved earlier for this account, hotel not born yet
        }
        if ($r) {                                                  // the account already belongs to a hotel: a second supplier on it is a data fault, not a new number
            $h = $this->db->query('SELECT name FROM hr2_hotel WHERE id = ?', [(int) $r['hotel_id']])->getRowArray();
            throw new \RuntimeException("The account {$supAccNo} already belongs to the hotel " . ($h['name'] ?? '#' . $r['hotel_id']) . ' (' . self::format((int) $r['no']) . '). The account counter may be behind — nothing was created.');
        }
        for ($try = 0; $try < 5; $try++) {
            $no = $this->nextNumber();
            try {
                $this->insertRegister($no, null, $supAccNo, $name, $how);
                return $no;
            } catch (\Throwable $e) {
                if (!self::isDuplicate($e)) {
                    throw $e;
                }
            }
        }
        throw new \RuntimeException('A hotel number could not be reserved.');
    }

    /**
     * Every hotel on the master without a code gets one: first the hotels whose ledger account carries a legacy
     * H-number (HTL-### = that number, in id order), then the rest in id order from the next free number. Nothing is
     * overwritten; a code already there stays. Returns what was done, for the installer's notes.
     *
     * @return array{given:int, legacy:int, fresh:int, already:int, sequence:int, conflicts:string[]}
     */
    public function backfill(): array
    {
        $out = ['given' => 0, 'legacy' => 0, 'fresh' => 0, 'already' => 0, 'sequence' => 0, 'conflicts' => []];
        $hotels = $this->db->query('SELECT id, name, hotel_code, primary_sup_acc_no FROM hr2_hotel ORDER BY id')->getResultArray();
        // pass 1: legacy numbers
        $pending = [];
        foreach ($hotels as $h) {
            if (!empty($h['hotel_code'])) {
                $out['already']++;
                continue;
            }
            $legacy = $this->legacyNumberFor((string) $h['primary_sup_acc_no']);
            if ($legacy !== null && !$this->numberTaken($legacy)) {
                $this->insertRegister($legacy, (int) $h['id'], (string) $h['primary_sup_acc_no'], (string) $h['name'], 'backfill-legacy');
                $this->db->query('UPDATE hr2_hotel SET hotel_code = ? WHERE id = ?', [self::format($legacy), (int) $h['id']]);
                $this->raiseTo($legacy);
                $out['given']++; $out['legacy']++;
            } else {
                if ($legacy !== null) {
                    $out['conflicts'][] = "{$h['name']} ({$h['primary_sup_acc_no']}): legacy number {$legacy} is already taken — given a fresh number instead";
                }
                $pending[] = $h;
            }
        }
        // the legacy counter stands at the highest legacy number even when some hotel never came onto the master
        $maxLegacy = (int) ($this->db->query("SELECT MAX(CAST(SUBSTRING(hotel_code, 2) AS UNSIGNED)) AS n FROM supplier WHERE sup_cat = 1 AND hotel_code REGEXP '^H[0-9]+$'")->getRowArray()['n'] ?? 0);
        if ($maxLegacy > 0) {
            $this->raiseTo($maxLegacy);
        }
        // pass 2: the rest, fresh numbers in id order
        foreach ($pending as $h) {
            $this->assign((int) $h['id'], 'backfill');
            $out['given']++; $out['fresh']++;
        }
        $out['sequence'] = $this->lastNumber();
        // keep the old screen's counter in step, so the next H-number it prints continues after the HTL numbers
        try {
            $this->db->query('UPDATE invoice_no SET hotel_code = GREATEST(COALESCE(hotel_code, 0), ?)', [$out['sequence']]);
        } catch (\Throwable $e) {
            // the legacy counter table is optional on a copy
        }
        return $out;
    }

    /** The legacy H-number of a ledger account (supplier.hotel_code 'H017' → 17), or null. */
    public function legacyNumberFor(string $supAccNo): ?int
    {
        $supAccNo = trim($supAccNo);
        if ($supAccNo === '') {
            return null;
        }
        try {
            $r = $this->db->query('SELECT hotel_code FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$supAccNo])->getRowArray();
        } catch (\Throwable $e) {
            return null;
        }
        $t = strtoupper(trim((string) ($r['hotel_code'] ?? '')));
        return preg_match('/^H0*(\d{1,6})$/', $t, $m) ? (int) $m[1] : null;
    }

    private function numberTaken(int $no): bool
    {
        return (bool) $this->db->query('SELECT 1 FROM hr2_hotel_code WHERE no = ?', [$no])->getRowArray();
    }

    private function insertRegister(int $no, ?int $hotelId, ?string $acc, string $name, string $how): void
    {
        $this->db->query(
            'INSERT INTO hr2_hotel_code (code, no, hotel_id, sup_acc_no, hotel_name_at, how, assigned_at, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [self::format($no), $no, $hotelId, $acc ?: null, mb_substr($name, 0, 240), $how, date('Y-m-d H:i:s'), $this->user]
        );
    }

    private static function isDuplicate(\Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062');
    }
}
