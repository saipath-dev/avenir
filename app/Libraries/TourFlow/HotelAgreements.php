<?php

namespace App\Libraries\TourFlow;

/**
 * tf-agree-r1 (27 Nov 2026, Shantha): Manual hotel agreement — what a hotel agreed by telephone for one inquiry's nights,
 * before the written confirmation arrives.
 *
 * Recorded per inquiry and hotel nights (never on the hotel's general contract): the agreed room (description, occupancy,
 * rooms), rate, currency, meal plan and the dates of the nights; the adults, children and child ages it covers; the bed
 * arrangement; the child supplement ("Included / no extra charge", or an amount per child per night); the hotel contact,
 * the call date and notes. The written confirmation can be attached later and is never required.
 *
 * While active, the agreement prices those nights in the Hotels list and the quotation — "Manual agreement — confirmed by
 * phone" — in place of the published contract, so the contract-data stops for those nights (no published rate, family room
 * capacity / beds, the hotel's child charge) no longer apply. Exactly what was agreed is costed: nothing is assumed about
 * guests the agreement does not name. Every save keeps who, when and the previous values.
 */
final class HotelAgreements
{
    public const LABEL = 'Manual agreement — confirmed by phone';
    public const OCC   = ['sgl' => 'Single', 'dbl' => 'Double', 'twn' => 'Twin', 'tpl' => 'Triple', 'quad' => 'Quadruple', 'fam' => 'Family room', 'unit' => 'Whole unit / villa'];
    public const MEALS = ['RO', 'BB', 'HB', 'FB', 'AI'];
    private const MAX_DOC = 8 * 1024 * 1024;

    private static array $ready = [];

    public static function ensure($db): bool
    {
        $k = is_object($db) ? spl_object_id($db) : 0;
        if (isset(self::$ready[$k])) {
            return self::$ready[$k];
        }
        try {
            $db->query("CREATE TABLE IF NOT EXISTS `tf_intake_hotel_agreement` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `intake_id` INT UNSIGNED NOT NULL,
                `option_id` INT UNSIGNED NULL,
                `hotel_name` VARCHAR(190) NOT NULL,
                `day_ids` VARCHAR(255) NOT NULL,
                `date_from` DATE NULL,
                `date_to` DATE NULL,
                `room_label` VARCHAR(160) NOT NULL,
                `occ` VARCHAR(10) NOT NULL,
                `rooms` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                `rate` DECIMAL(12,2) NOT NULL,
                `currency` CHAR(3) NOT NULL DEFAULT 'USD',
                `meal_plan` VARCHAR(4) NOT NULL,
                `adults` SMALLINT UNSIGNED NOT NULL,
                `children` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                `child_ages` VARCHAR(80) NULL,
                `bed_arrangement` VARCHAR(255) NULL,
                `child_supp_mode` VARCHAR(10) NULL,
                `child_supp_amount` DECIMAL(12,2) NULL,
                `child_supp_children` SMALLINT UNSIGNED NULL,
                `contact_name` VARCHAR(160) NOT NULL,
                `call_date` DATE NOT NULL,
                `notes` TEXT NULL,
                `written_ref` VARCHAR(255) NULL,
                `status` VARCHAR(10) NOT NULL DEFAULT 'active',
                `created_by` VARCHAR(100) NULL, `created_at` DATETIME NULL,
                `updated_by` VARCHAR(100) NULL, `updated_at` DATETIME NULL,
                PRIMARY KEY (`id`), KEY `k_intake` (`intake_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->query("CREATE TABLE IF NOT EXISTS `tf_intake_hotel_agreement_log` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `agreement_id` INT UNSIGNED NOT NULL,
                `action` VARCHAR(20) NOT NULL,
                `old_json` TEXT NULL,
                `new_json` TEXT NULL,
                `by_user` VARCHAR(100) NULL,
                `at` DATETIME NOT NULL,
                PRIMARY KEY (`id`), KEY `k_ag` (`agreement_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->query("CREATE TABLE IF NOT EXISTS `tf_intake_hotel_agreement_doc` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `agreement_id` INT UNSIGNED NOT NULL,
                `file_name` VARCHAR(190) NOT NULL,
                `mime` VARCHAR(100) NOT NULL,
                `size` INT UNSIGNED NOT NULL,
                `data` LONGBLOB NOT NULL,
                `uploaded_by` VARCHAR(100) NULL,
                `uploaded_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`), KEY `k_ag` (`agreement_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            self::$ready[$k] = true;
        } catch (\Throwable $e) {
            log_message('error', 'HotelAgreements::ensure: ' . $e->getMessage());
            self::$ready[$k] = false;
        }
        return self::$ready[$k];
    }

    private static function row(array $r): array
    {
        $r['id'] = (int) $r['id'];
        $r['option_id'] = $r['option_id'] !== null ? (int) $r['option_id'] : null;
        $r['day_ids'] = array_values(array_filter(array_map('intval', explode(',', (string) $r['day_ids']))));
        foreach (['rooms', 'adults', 'children'] as $i) {
            $r[$i] = (int) $r[$i];
        }
        $r['child_supp_children'] = $r['child_supp_children'] !== null ? (int) $r['child_supp_children'] : null;
        foreach (['rate', 'child_supp_amount'] as $m) {
            $r[$m] = $r[$m] !== null ? (float) $r[$m] : null;
        }
        $r['label'] = self::LABEL;
        return $r;
    }

    /** The active agreements of an inquiry, each with its documents (names only) and its history. */
    public static function forIntake($db, int $intakeId): array
    {
        if (!self::ensure($db)) {
            return [];
        }
        $out = [];
        foreach ($db->query("SELECT * FROM tf_intake_hotel_agreement WHERE intake_id = ? AND status = 'active' ORDER BY id", [$intakeId])->getResultArray() as $r) {
            $a = self::row($r);
            $a['docs'] = $db->query('SELECT id, file_name, size, uploaded_by, uploaded_at FROM tf_intake_hotel_agreement_doc WHERE agreement_id = ? ORDER BY id', [$a['id']])->getResultArray();
            $a['history'] = array_map(static fn ($h) => ['action' => $h['action'], 'by' => $h['by_user'], 'at' => $h['at'], 'old' => json_decode((string) $h['old_json'], true), 'new' => json_decode((string) $h['new_json'], true)],
                $db->query('SELECT action, old_json, new_json, by_user, at FROM tf_intake_hotel_agreement_log WHERE agreement_id = ? ORDER BY id DESC LIMIT 20', [$a['id']])->getResultArray());
            $out[] = $a;
        }
        return $out;
    }

    /**
     * What one night costs under an agreement, in the agreement's currency: the rooms at the agreed rate, and the child
     * supplement (included = a line at no charge; an amount = per child per night × the children it is charged for).
     */
    public static function night(array $a): array
    {
        $occ = self::OCC[$a['occ']] ?? $a['occ'];
        $who = $a['adults'] . ' adult' . ($a['adults'] === 1 ? '' : 's') . ($a['children'] ? ' + ' . $a['children'] . ' child' . ($a['children'] === 1 ? '' : 'ren') . ($a['child_ages'] ? ' (' . $a['child_ages'] . ')' : '') : '');
        $lines = [['kind' => 'agree', 'label' => self::LABEL . ': ' . $a['rooms'] . ' × ' . $a['room_label'] . ' — ' . $occ . ', ' . $who . ($a['bed_arrangement'] ? ', ' . $a['bed_arrangement'] : ''),
            'qty' => $a['rooms'], 'rate' => (float) $a['rate'], 'amount' => round($a['rooms'] * (float) $a['rate'], 2)]];
        if ($a['children'] > 0 && $a['child_supp_mode'] === 'included') {
            $lines[] = ['kind' => 'child', 'label' => 'Child supplement: included / no extra charge (agreed by phone)', 'qty' => $a['children'], 'rate' => 0.0, 'amount' => 0.0];
        } elseif ($a['children'] > 0 && $a['child_supp_mode'] === 'charge') {
            $n = (int) ($a['child_supp_children'] ?? $a['children']);
            $lines[] = ['kind' => 'child', 'label' => 'Child supplement agreed by phone: ' . $n . ' × ' . number_format((float) $a['child_supp_amount'], 2) . ' a night', 'qty' => $n, 'rate' => (float) $a['child_supp_amount'], 'amount' => round($n * (float) $a['child_supp_amount'], 2)];
        }
        return ['lines' => $lines, 'amount' => round(array_sum(array_column($lines, 'amount')), 2), 'currency' => $a['currency'], 'meal_plan' => $a['meal_plan'], 'room' => $a['room_label']];
    }

    /** The agreement that prices a night in a costing: for an option, one for that option or for all; else for the chosen option or for all. */
    public static function forNight(array $agreements, int $dayId, ?int $optionId): ?array
    {
        $pick = null;
        foreach ($agreements as $a) {
            if (!in_array($dayId, $a['day_ids'], true)) {
                continue;
            }
            if ($a['option_id'] === null || $a['option_id'] === $optionId) {
                if ($pick === null || ($pick['option_id'] === null && $a['option_id'] !== null)) {
                    $pick = $a;   // one made for this option wins over one for every option
                }
            }
        }
        return $pick;
    }

    // =====================================================================
    //  saving
    // =====================================================================

    /** Validate and save (create or edit) an agreement; the exact missing details are said, nothing is assumed. */
    public static function save($db, int $intakeId, array $v, string $by): array
    {
        if (!self::ensure($db)) {
            throw new \RuntimeException('The manual agreement could not be stored (database).');
        }
        $id  = (int) ($v['id'] ?? 0);
        $old = $id ? $db->query("SELECT * FROM tf_intake_hotel_agreement WHERE id = ? AND intake_id = ? AND status = 'active'", [$id, $intakeId])->getRowArray() : null;
        if ($id && !$old) {
            throw new \InvalidArgumentException('That agreement is not on this inquiry.');
        }
        $miss = [];
        $str  = static fn ($k, $len) => mb_substr(trim((string) ($v[$k] ?? '')), 0, $len);
        $num  = static function ($k) use ($v) {
            $x = is_string($v[$k] ?? null) ? str_replace([',', ' '], '', trim($v[$k])) : ($v[$k] ?? null);
            return ($x === null || $x === '' || !is_numeric($x)) ? null : (float) $x;
        };
        // the nights: of this inquiry, all at the one hotel
        $dayIds = array_values(array_unique(array_filter(array_map('intval', (array) ($v['day_ids'] ?? [])))));
        $days = [];
        if ($dayIds) {
            $in = implode(',', array_fill(0, count($dayIds), '?'));
            $days = $db->query("SELECT id, day_no, day_date, hotel_name_raw FROM tf_intake_day WHERE intake_id = ? AND id IN ({$in}) ORDER BY day_no", array_merge([$intakeId], $dayIds))->getResultArray();
        }
        if (!$days || count($days) !== count($dayIds)) {
            $miss[] = 'the nights it covers';
        }
        $hotels = array_unique(array_map(static fn ($d) => mb_strtolower(trim((string) $d['hotel_name_raw'])), $days));
        if (count($hotels) > 1) {
            throw new \InvalidArgumentException('An agreement covers nights at one hotel; the nights chosen are at ' . count($hotels) . ' hotels.');
        }
        $hotel = $days ? trim((string) $days[0]['hotel_name_raw']) : '';
        if ($days && $hotel === '') {
            $miss[] = 'a hotel on the nights';
        }
        $room = $str('room_label', 160);
        $occ  = (string) ($v['occ'] ?? '');
        $rooms = (int) ($v['rooms'] ?? 0);
        $rate = $num('rate');
        $cur  = strtoupper($str('currency', 3));
        $meal = strtoupper($str('meal_plan', 4));
        $adults = (int) ($v['adults'] ?? 0);
        $children = (int) ($v['children'] ?? 0);
        $ages = $str('child_ages', 80);
        $bed  = $str('bed_arrangement', 255);
        $mode = (string) ($v['child_supp_mode'] ?? '');
        $suppAmt = $num('child_supp_amount');
        $suppN = ($v['child_supp_children'] ?? '') === '' ? null : (int) $v['child_supp_children'];
        $contact = $str('contact_name', 160);
        $call = $str('call_date', 10);
        if ($room === '') { $miss[] = 'the room agreed'; }
        if (!isset(self::OCC[$occ])) { $miss[] = 'the occupancy'; }
        if ($rooms < 1 || $rooms > 20) { $miss[] = 'the number of rooms (1–20)'; }
        if ($rate === null || $rate <= 0) { $miss[] = 'the agreed rate'; }
        if (!in_array($cur, ['USD', 'LKR'], true)) { $miss[] = 'the currency (USD or LKR)'; }
        if (!in_array($meal, self::MEALS, true)) { $miss[] = 'the meal plan'; }
        if ($adults < 1) { $miss[] = 'the adults covered'; }
        if ($children > 0) {
            $agesList = array_values(array_filter(array_map('trim', preg_split('/[,;\/ ]+/', $ages)), static fn ($x) => $x !== ''));
            if (count($agesList) !== $children || array_filter($agesList, static fn ($x) => !is_numeric($x) || (float) $x < 0 || (float) $x > 17)) {
                $miss[] = 'the age of each child covered (' . $children . ' age' . ($children === 1 ? '' : 's') . ', e.g. 11, 9, 7)';
            } else {
                $ages = implode(', ', $agesList);
            }
            if (!in_array($mode, ['included', 'charge'], true)) {
                $miss[] = 'the child supplement (Included / no extra charge, or the amount)';
            } elseif ($mode === 'charge' && ($suppAmt === null || $suppAmt <= 0)) {
                $miss[] = 'the child supplement amount per child per night';
            } elseif ($mode === 'charge' && $suppN !== null && ($suppN < 1 || $suppN > $children)) {
                $miss[] = 'how many children the supplement is charged for (1–' . $children . ')';
            }
        } else {
            $ages = '';
            $mode = '';
        }
        if ($bed === '') { $miss[] = 'the bed arrangement agreed'; }
        if ($contact === '') { $miss[] = "the hotel contact's name"; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $call) || !strtotime($call) || $call > date('Y-m-d')) { $miss[] = 'the call date (not in the future)'; }
        if ($miss) {
            throw new \InvalidArgumentException('Not saved — the agreement still needs: ' . implode('; ', $miss) . '.');
        }
        $optId = ($v['option_id'] ?? '') === '' || $v['option_id'] === null ? null : (int) $v['option_id'];
        if ($optId !== null && !$db->query('SELECT id FROM tf_intake_roomopt WHERE id = ? AND intake_id = ?', [$optId, $intakeId])->getRowArray()) {
            throw new \InvalidArgumentException('Unknown room option.');
        }
        $n = [
            'option_id' => $optId, 'hotel_name' => $hotel, 'day_ids' => implode(',', array_map(static fn ($d) => (int) $d['id'], $days)),
            'date_from' => $days[0]['day_date'] ?? null, 'date_to' => $days[count($days) - 1]['day_date'] ?? null,
            'room_label' => $room, 'occ' => $occ, 'rooms' => $rooms, 'rate' => round($rate, 2), 'currency' => $cur, 'meal_plan' => $meal,
            'adults' => $adults, 'children' => $children, 'child_ages' => $ages !== '' ? $ages : null, 'bed_arrangement' => $bed,
            'child_supp_mode' => $mode !== '' ? $mode : null, 'child_supp_amount' => $mode === 'charge' ? round((float) $suppAmt, 2) : null,
            'child_supp_children' => $mode === 'charge' ? ($suppN ?? $children) : null,
            'contact_name' => $contact, 'call_date' => $call, 'notes' => trim((string) ($v['notes'] ?? '')) !== '' ? mb_substr(trim((string) $v['notes']), 0, 4000) : null,
            'written_ref' => trim((string) ($v['written_ref'] ?? '')) !== '' ? mb_substr(trim((string) $v['written_ref']), 0, 255) : null,
        ];
        $now = date('Y-m-d H:i:s');
        if ($old) {
            $changes = [];
            foreach ($n as $k => $val) {
                if ((string) $val !== (string) ($old[$k] ?? '') && !(is_numeric($val) && is_numeric($old[$k] ?? null) && (float) $val === (float) $old[$k])) {
                    $changes[$k] = true;
                }
            }
            if (!$changes) {
                return ['id' => $id, 'changed' => false];
            }
            $sets = implode(', ', array_map(static fn ($k) => "`{$k}` = ?", array_keys($n)));
            $db->query("UPDATE tf_intake_hotel_agreement SET {$sets}, updated_by = ?, updated_at = ? WHERE id = ?", array_merge(array_values($n), [$by, $now, $id]));
            $oldVals = array_intersect_key($old, $changes);
            $newVals = array_intersect_key($n, $changes);
            self::log($db, $id, 'edit', $oldVals, $newVals, $by);
            return ['id' => $id, 'changed' => true];
        }
        $cols = array_keys($n);
        $db->query('INSERT INTO tf_intake_hotel_agreement (intake_id, ' . implode(', ', $cols) . ', status, created_by, created_at, updated_by, updated_at) VALUES (?' . str_repeat(', ?', count($cols)) . ", 'active', ?, ?, ?, ?)",
            array_merge([$intakeId], array_values($n), [$by, $now, $by, $now]));
        $id = (int) $db->insertID();
        self::log($db, $id, 'create', null, $n, $by);
        return ['id' => $id, 'changed' => true];
    }

    /** Withdraw an agreement (kept, with its history); the nights go back to the published contract. */
    public static function withdraw($db, int $intakeId, int $id, string $reason, string $by): void
    {
        if (!self::ensure($db)) {
            throw new \RuntimeException('The manual agreement could not be changed (database).');
        }
        $old = $db->query("SELECT * FROM tf_intake_hotel_agreement WHERE id = ? AND intake_id = ? AND status = 'active'", [$id, $intakeId])->getRowArray();
        if (!$old) {
            throw new \InvalidArgumentException('That agreement is not on this inquiry.');
        }
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('Say why the agreement is withdrawn.');
        }
        $db->query("UPDATE tf_intake_hotel_agreement SET status = 'void', updated_by = ?, updated_at = ? WHERE id = ?", [$by, date('Y-m-d H:i:s'), $id]);
        self::log($db, $id, 'withdraw', ['status' => 'active'], ['status' => 'void', 'reason' => mb_substr(trim($reason), 0, 300)], $by);
    }

    /** Attach the written confirmation (a PDF or image up to 8 MB), kept in the database with the agreement. */
    public static function attach($db, int $intakeId, int $id, $file, string $by): void
    {
        if (!self::ensure($db)) {
            throw new \RuntimeException('The written confirmation could not be stored (database).');
        }
        if (!$db->query("SELECT id FROM tf_intake_hotel_agreement WHERE id = ? AND intake_id = ? AND status = 'active'", [$id, $intakeId])->getRowArray()) {
            throw new \InvalidArgumentException('That agreement is not on this inquiry.');
        }
        if (!$file || !$file->isValid()) {
            throw new \InvalidArgumentException('Choose the file of the written confirmation.');
        }
        $mime = (string) $file->getMimeType();
        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'message/rfc822', 'application/vnd.ms-outlook'], true)) {
            throw new \InvalidArgumentException('The written confirmation must be a PDF, an image (JPG, PNG) or an e-mail file.');
        }
        if ($file->getSize() > self::MAX_DOC) {
            throw new \InvalidArgumentException('The file is larger than 8 MB.');
        }
        $data = file_get_contents($file->getTempName());
        $db->query('INSERT INTO tf_intake_hotel_agreement_doc (agreement_id, file_name, mime, size, data, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$id, mb_substr($file->getClientName(), 0, 190), $mime, (int) $file->getSize(), $data, $by, date('Y-m-d H:i:s')]);
        self::log($db, $id, 'attach', null, ['file' => $file->getClientName()], $by);
    }

    public static function doc($db, int $intakeId, int $docId): ?array
    {
        if (!self::ensure($db)) {
            return null;
        }
        return $db->query('SELECT d.file_name, d.mime, d.data FROM tf_intake_hotel_agreement_doc d JOIN tf_intake_hotel_agreement a ON a.id = d.agreement_id WHERE d.id = ? AND a.intake_id = ?', [$docId, $intakeId])->getRowArray() ?: null;
    }

    private static function log($db, int $id, string $action, ?array $old, ?array $new, string $by): void
    {
        $db->query('INSERT INTO tf_intake_hotel_agreement_log (agreement_id, action, old_json, new_json, by_user, at) VALUES (?, ?, ?, ?, ?, ?)',
            [$id, $action, $old !== null ? json_encode($old, JSON_UNESCAPED_UNICODE) : null, $new !== null ? json_encode($new, JSON_UNESCAPED_UNICODE) : null, $by, date('Y-m-d H:i:s')]);
    }
}
