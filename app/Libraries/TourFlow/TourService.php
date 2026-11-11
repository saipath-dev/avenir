<?php

namespace App\Libraries\TourFlow;

/**
 * The tour after the agent says yes: Confirm, the two queues, and the Board.
 *
 * Confirm is one transaction. It writes the tour, one card per hotel stay for the
 * Hotel Desk, one card per vehicle for the Transport Desk, and the first line of the
 * trail — or none of it. It sends no email, raises no invoice, moves no money and
 * writes nothing to the legacy tour tables. From that moment the inquiry is read-only:
 * the quotation is the record of what was sold, and the cards carry their own copy of
 * what each desk needs, so a desk can move a check-in or re-order a day without the
 * quotation moving.
 *
 * Nothing is guessed. A night with no hotel named still becomes a card, flagged; a
 * hotel with no supplier record keeps the name the agent wrote; a meal plan not stated
 * stays blank and flagged, and the card cannot be requested until it is set.
 *
 * What a desk may see is decided by the screen it opens (the route filter), never by
 * hiding columns per group: the hotel card carries no transport and no totals, the
 * transport card carries kilometres and not rupees. The Board, which only Management
 * holds, is the one place money from every lane sits together.
 */
final class TourService
{
    public const HOTEL_STATUSES     = ['to_book', 'requested', 'confirmed', 'voucher_issued', 'problem', 'cancelled'];
    public const TRANSPORT_STATUSES = ['to_allocate', 'allocated', 'scheduled', 'pack_issued', 'running', 'returned', 'settled', 'cancelled'];
    /** The transport ladder in order; a card moves one rung at a time, forward with a check, back with a note. */
    public const TRANSPORT_LADDER   = ['to_allocate', 'allocated', 'scheduled', 'pack_issued', 'running', 'returned', 'settled'];
    /** Activities and tickets (schema 20): the same steps as a stay, plus "not needed" for a line with nothing to arrange. */
    public const SERVICE_STATUSES   = ['to_book', 'requested', 'confirmed', 'not_needed', 'problem', 'cancelled', 'pay_at_gate'];   // 'pay_at_gate' since schema 37 (tf-tickets-r1)
    /** The activity-line statuses that count as done: nothing more for the desk to arrange. */
    public const SERVICE_DONE       = ['confirmed', 'not_needed', 'pay_at_gate'];
    /** The service kinds that go to the Transport Desk as crew or vehicle, never as a reservation. */
    private const SERVICE_KINDS_SKIPPED = ['guide', 'transport'];
    /** A line the party arranges for themselves, or that is not happening, is nothing to reserve. */
    private const PAY_MODES_SKIPPED = ['own_arrangement', 'not_arranged', 'no_charge', 'in_rate'];   // nothing to book for a stop with nothing to pay

    private $db;
    private string $user;
    private ?IntakeService $intake;
    private ?bool $hasActualLegs = null;
    private ?bool $hasServiceJobs = null;
    private ?bool $hasCrewPerson = null;
    private ?bool $hasCrewRate = null;
    /** tf-crew-master-r1: the desk sets this for a holder of "Driver & Guide bank details"; the crew lines then carry the bank. */
    public bool $bankVisible = false;   // tf-masters-r3 (schema 36)
    private ?bool $hasServiceFee = null;   // tf-tickets-r1 (schema 37): tf_service_job.fee_id
    private ?TicketStock $stock = null;    // tf-tickets-r1: the entrance ticket stock
    private ?array $masterTypes = null;    // tf-masters-r3: the vehicle master's types by id
    private ?bool $hasHotelRoomType = null;   // tf-walkfix-r1 (schema 35)
    private ?bool $hasDeskJobs = null;
    private ?bool $hasTourArchive = null;
    private ?bool $hasCrewDates = null;

    public function __construct($db, string $user = '', ?IntakeService $intake = null)
    {
        $this->db     = $db;
        $this->user   = $user;
        $this->intake = $intake;
    }

    private function intake(): IntakeService
    {
        return $this->intake ??= new IntakeService($this->db, $this->user);
    }

    /** Whether a crew row carries the days worked and a replacement, and vouchers can be issued (schema 24). */
    private ?bool $hasServicePlace = null;   // tf-entrance-r1 (schema 39): tf_service_job.place_id

    /** schema 39: the activity line carries the place of the Place to Visit / Entrance Fee Master it stands for (place_id). */
    private function hasServicePlace(): bool
    {
        if ($this->hasServicePlace === null) {
            try {
                $this->hasServicePlace = (bool) $this->db->query("SHOW COLUMNS FROM `tf_service_job` LIKE 'place_id'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasServicePlace = false;
            }
        }
        return $this->hasServicePlace;
    }

    /** schema 37: the activity line carries the Entrance Fee row it stands for (fee_id / fee_code). */
    private function hasServiceFee(): bool
    {
        if ($this->hasServiceFee === null) {
            try {
                $this->hasServiceFee = (bool) $this->db->query("SHOW COLUMNS FROM `tf_service_job` LIKE 'fee_id'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasServiceFee = false;
            }
        }
        return $this->hasServiceFee;
    }

    /** The entrance ticket stock (schema 37); its methods say when it is not installed. */
    public function stock(): TicketStock
    {
        if ($this->stock === null) {
            $this->stock = new TicketStock($this->db, $this->user);
        }
        return $this->stock;
    }

    /** schema 44: the crew line carries the person's category and the rate per km copied the day they were engaged. */
    private function hasCrewRateColumns(): bool
    {
        if ($this->hasCrewRate === null) {
            try {
                $this->hasCrewRate = (bool) $this->db->query("SHOW COLUMNS FROM `tf_transport_crew` LIKE 'person_km_rate'")->getResultArray()
                    && (bool) $this->db->query("SHOW COLUMNS FROM `tf_transport_crew` LIKE 'person_overnight'")->getResultArray()
                    && (bool) $this->db->query("SHOW COLUMNS FROM `tf_transport_crew` LIKE 'person_overnight_source'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasCrewRate = false;
            }
        }
        return $this->hasCrewRate;
    }

    /** schema 36: the crew line carries the person from the Drivers & Guides master and their phone. */
    private function hasCrewPerson(): bool
    {
        if ($this->hasCrewPerson === null) {
            try {
                $this->hasCrewPerson = (bool) $this->db->query("SHOW COLUMNS FROM `tf_transport_crew` LIKE 'person_id'")->getResultArray()
                    && (bool) $this->db->query("SHOW TABLES LIKE 'tf_crew_person'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasCrewPerson = false;
            }
        }
        return $this->hasCrewPerson;
    }

    /**
     * A person from the Drivers & Guides master, as a crew line takes them: id, name, phone, ledger account and
     * their own agreed bata. Null when the id is not on the master or the person is archived / inactive.
     */
    private function crewPerson(?int $personId): ?array
    {
        if ($personId === null || $personId <= 0 || !$this->hasCrewPerson()) {
            return null;
        }
        $people = new CrewPeople($this->db, $this->user);
        $p = $people->get($personId, false);
        if (!$p) {
            throw new \RuntimeException('That person is not on the Driver & Guide Master.');
        }
        if (!empty($p['archived_at']) || (string) $p['status'] !== 'active') {
            // tf-crew-master-r1: an inactive or archived person is never allocated
            throw new \RuntimeException($p['name'] . ' is ' . (!empty($p['archived_at']) ? 'archived' : 'inactive') . ' on the Driver & Guide Master; restore or activate the record first.');
        }
        // the rate per km in force for the driver: their special rate, else the general rate of their vehicle's class
        $eff = $p['rate_effective'] ?? ['rate_km' => null, 'source' => null, 'from' => null, 'class' => null];
        // the bata (guide fee) in force: their own agreed figure, else the standard of their category — and the overnight
        // allowance, kept apart (agreed, else the standard once confirmed, else none)
        $bt = $p['bata_effective'] ?? ['bata' => null, 'source' => null, 'from' => null, 'overnight' => null, 'overnight_source' => null, 'overnight_from' => null];
        return ['id' => (int) $p['id'], 'name' => (string) $p['name'], 'nickname' => $p['nickname'] ?? null, 'kind' => (string) $p['kind'], 'category' => $p['category'] ?? null,
            'category_confirmed' => !empty($p['category_confirmed']), 'phone' => trim((string) ($p['mobile'] ?: $p['phone'] ?: '')) ?: null,
            'acc' => trim((string) ($p['supplier_acc'] ?? '')) ?: null,
            'bata' => $bt['bata'], 'bata_source' => $bt['source'], 'bata_from' => $bt['from'],
            'overnight' => $bt['overnight'], 'overnight_source' => $bt['overnight_source'], 'overnight_from' => $bt['overnight_from'],
            'km_rate' => $eff['rate_km'], 'rate_from' => $eff['from'], 'rate_source' => $eff['source'], 'rate_class' => $eff['class'],
            'warnings' => array_values(array_filter($p['warnings'] ?? [], static fn ($w) => $w['level'] !== 'info'))];
    }

    /**
     * What the line keeps of the bata the day the person is engaged: the date the figure took effect and where it came
     * from — 'agreed' (the person's own figure), 'standard' (the standard in force that day), 'typed' (a figure typed at
     * allocation, differing from the master's) — and the overnight allowance with its date and source, kept apart.
     * Column order: person_bata_from, person_overnight, person_bata_source, person_overnight_from, person_overnight_source.
     */
    /** Where the bata a line is paid on came from, in words (for the events and the voucher). */
    public static function bataSourceText(array $c): string
    {
        $own = ($c['person_bata'] ?? null) !== null && $c['person_bata'] !== '';
        if (!$own) {
            return 'the Crew Bata master\'s figure for the role';
        }
        return match ($c['person_bata_source'] ?? null) {
            'agreed'   => 'the person\'s own agreed ' . ((string) $c['kind'] === 'guide' ? 'guide fee' : 'bata') . (!empty($c['person_bata_from']) ? ' in force from ' . $c['person_bata_from'] : ''),
            'standard' => 'the standard ' . ((string) $c['kind'] === 'guide' ? 'guide fee' : 'bata') . (!empty($c['person_bata_from']) ? ' in force from ' . $c['person_bata_from'] : '') . ', copied when engaged',
            'typed'    => 'the figure typed at allocation',
            default    => 'the figure on the line',
        };
    }

    private static function bataSnapshot(?array $person, ?float $bata): array
    {
        if ($person === null) {
            return [null, null, null, null, null];
        }
        $same = $bata !== null && $person['bata'] !== null && (float) $person['bata'] === $bata;
        return [
            $same ? ($person['bata_from'] ?? null) : null,
            $person['overnight'] ?? null,
            $bata === null ? null : ($same ? ($person['bata_source'] ?? null) : 'typed'),
            $person['overnight_from'] ?? null,
            $person['overnight_source'] ?? null,
        ];
    }

    /**
     * tf-crew-master-r1: the crew line's category — JSD, JSCG, JSNTG (a GUIDE_JP line counts as JSNTG) — and the people
     * it may be given, STRICTLY that category (Shantha, 5 Oct 2026); a line outside the three lists those serving its code.
     */
    public function crewPeopleFor(?string $code, ?string $kind = null): array
    {
        if (!$this->hasCrewPerson()) {
            return [];
        }
        $people = (new CrewPeople($this->db, $this->user))->forLine($code, $kind);
        return array_map(fn ($p) => $this->pickerPerson($p, true), $people);
    }

    /**
     * A person as the allocation picker shows them: photo, nickname, legal name, category, the vehicle normally supplied
     * with its roof type, the licences and documents with their expiry, the rate in force — and the warnings.
     */
    private function pickerPerson(array $p, ?bool $serves): array
    {
        $v = $p['vehicle'] ?? null;
        $eff = $p['rate_effective'] ?? ['rate_km' => null, 'source' => null, 'from' => null, 'class' => null, 'class_name' => null, 'general' => null, 'special' => null];
        $bt = $p['bata_effective'] ?? ['bata' => null, 'source' => null, 'from' => null, 'agreed' => null, 'standard' => null, 'overnight' => null, 'overnight_source' => null, 'overnight_from' => null, 'item_name' => null];
        return [
            'id' => $p['id'], 'name' => $p['name'], 'nickname' => $p['nickname'] ?? null, 'kind' => $p['kind'], 'category' => $p['category'] ?? null, 'category_confirmed' => !empty($p['category_confirmed']),
            'codes' => $p['codes'], 'languages' => $p['languages'], 'phone' => $p['mobile'] ?: $p['phone'], 'whatsapp' => $p['whatsapp'] ?? null, 'acc' => $p['supplier_acc'], 'serves' => $serves ?? ($p['serves'] ?? null),
            // the bata (guide fee) in force today — the person's own agreed figure, else the standard — with both shown
            'bata' => $bt['bata'], 'bata_source' => $bt['source'], 'bata_from' => $bt['from'], 'bata_item' => $bt['item_name'],
            'agreed_bata' => $bt['agreed']['bata'] ?? null, 'agreed_bata_from' => $bt['agreed']['effective_from'] ?? null, 'standard_bata' => $bt['standard']['bata'] ?? null, 'standard_bata_from' => $bt['standard']['effective_from'] ?? null,
            'overnight_bata' => $bt['overnight'], 'overnight_source' => $bt['overnight_source'], 'overnight_from' => $bt['overnight_from'],
            'status' => $p['status'] ?? 'active', 'photo' => $p['photo_file_url'] ?? null, 'engagements' => $p['engagements'] ?? 0,
            'licence_no' => $p['licence_no'] ?? null, 'licence_expiry' => $p['licence_expiry'] ?? null, 'licence_expired' => $p['licence_expired'] || $p['guide_licence_expired'],
            'driving_licence_expired' => (bool) $p['licence_expired'], 'guide_licence_no' => $p['guide_licence_no'] ?? null, 'guide_licence_expiry' => $p['guide_licence_expiry'] ?? null, 'guide_licence_expired' => (bool) $p['guide_licence_expired'],
            'vehicle_reg' => $v['reg_no'] ?? ($p['vehicle_reg'] ?? null),
            'vehicle' => $v ? [
                'id' => $v['id'], 'reg_no' => $v['reg_no'], 'label' => $v['label'], 'vehicle_type' => $v['vehicle_type'], 'type_name' => $v['type_name'], 'roof_type' => $v['roof_type'], 'roof_name' => $v['roof_name'],
                'seats' => $v['seats'], 'max_pax_luggage' => $v['max_pax_luggage'], 'ownership_name' => $v['ownership_name'],
                'insurance_expiry' => $v['insurance_expiry'], 'insurance_expired' => $v['insurance_expired'], 'licence_expiry' => $v['licence_expiry'], 'licence_expired' => $v['licence_expired'],
                'photo' => !empty($v['photos']) ? $v['photos'][0]['url'] : null, 'photos' => count($v['photos'] ?? []),
            ] : null,
            'km_rate' => $eff['rate_km'], 'rate_from' => $eff['from'], 'rate_source' => $eff['source'], 'rate_class' => $eff['class'], 'rate_class_name' => $eff['class_name'],
            'general_rate' => $eff['general']['rate_km'] ?? null, 'general_rate_from' => $eff['general']['valid_from'] ?? null, 'special_rate' => $eff['special']['rate_km'] ?? null, 'special_rate_from' => $eff['special']['effective_from'] ?? null,
            'warnings' => array_values(array_filter($p['warnings'] ?? [], static fn ($w) => $w['level'] !== 'info')),
        ];
    }

    /** What a payment screen shows for a crew line's person: nickname, legal name, category; the bank for the privilege. */
    public function crewPayee(?int $personId, ?string $acc, bool $withBank): ?array
    {
        if (!$this->hasCrewPerson()) {
            return null;
        }
        try {
            return (new CrewPeople($this->db, $this->user))->payee($personId, $acc, $withBank);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The people on the Drivers & Guides master a crew line may be given, in the desk's order: those serving its category first. */
    public function crewPeople(?string $kind = null, ?string $code = null): array
    {
        if (!$this->hasCrewPerson()) {
            return [];
        }
        if ($code !== null && $code !== '' && CrewPeople::lineCategory($code) !== null) {
            return $this->crewPeopleFor($code, $kind);
        }
        $people = (new CrewPeople($this->db, $this->user))->all($kind, true, $code);
        return array_map(fn ($p) => $this->pickerPerson($p, null), $people);
    }

    private function hasCrewDates(): bool
    {
        if ($this->hasCrewDates === null) {
            try {
                $this->hasCrewDates = (bool) $this->db->query("SHOW COLUMNS FROM `tf_transport_crew` LIKE 'date_from'")->getResultArray()
                    && (bool) $this->db->query("SHOW TABLES LIKE 'tf_crew_voucher'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasCrewDates = false;
            }
        }
        return $this->hasCrewDates;
    }

    /** Whether the transport card's legs can hold Google's figure and the actual driven kilometres (schema 18). */
    private function hasActualLegs(): bool
    {
        if ($this->hasActualLegs === null) {
            try {
                $this->hasActualLegs = (bool) $this->db->query("SHOW COLUMNS FROM `tf_transport_leg` LIKE 'actual_km'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasActualLegs = false;
            }
        }
        return $this->hasActualLegs;
    }

    /** Whether the tour carries activities and tickets, the sold version and the reservations mark (schema 20). */
    private function hasServiceJobs(): bool
    {
        if ($this->hasServiceJobs === null) {
            try {
                $this->hasServiceJobs = (bool) $this->db->query("SHOW TABLES LIKE 'tf_service_job'")->getResultArray()
                    && (bool) $this->db->query("SHOW COLUMNS FROM `tf_tour` LIKE 'quote_id'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasServiceJobs = false;
            }
        }
        return $this->hasServiceJobs;
    }

    /** schema 35: the stay carries the room type as this booking describes it. */
    private function hasHotelRoomType(): bool
    {
        if ($this->hasHotelRoomType === null) {
            try {
                $this->hasHotelRoomType = (bool) $this->db->query("SHOW COLUMNS FROM `tf_hotel_job` LIKE 'room_type_text'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasHotelRoomType = false;
            }
        }
        return $this->hasHotelRoomType;
    }

    /**
     * tf-walkfix-r1: the room type as this booking describes it, changed by the Hotel Desk for this stay alone —
     * the master is not touched, the quotation is not touched, and the rooms line on the letters follows it.
     */
    public function hotelRoomType(int $jobId, ?string $text): array
    {
        if (!$this->hasHotelRoomType()) {
            throw new \RuntimeException('The room type on a stay needs the schema 35 upgrade: open Tour Inquiry as the administrator and press Install now.');
        }
        $text = self::cut($text, 120);
        $text = $text !== null && trim($text) !== '' ? trim($text) : null;
        if ($text === null) {
            throw new \RuntimeException('Type the room type as it should read on this booking.');
        }
        $j = $this->hotelJob($jobId);
        if ($j['archived']) {
            throw new \RuntimeException('This card is archived. Management can restore it from the ⋯ Manage menu.');
        }
        if ((string) $j['tour_stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        $was = trim((string) ($j['room_type_text'] ?? ''));
        // the rooms line keeps its mix and takes the new wording after the dash: "1 DBL — Superior" → "1 DBL — Superior Lake View"
        $note = (string) ($j['rooms_note'] ?? '');
        if ($note !== '' && str_contains($note, ' — ')) {
            $note = preg_replace('/ — [^;]*$/u', ' — ' . $text, $note) ?? $note;
        } elseif ($note !== '') {
            $note .= ' — ' . $text;
        } else {
            $note = $text;
        }
        $this->db->transStart();
        $this->db->query('UPDATE tf_hotel_job SET room_type_text = ?, rooms_note = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$text, self::cut($note, 255), $this->now(), $this->user, $jobId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'hotel', $jobId, 'room_type', (string) $j['status'], (string) $j['status'], 'Room type on this booking: ' . ($was !== '' ? $was . ' → ' : '') . $text . ' (the master is unchanged)');
        $this->db->transComplete();
        return $this->hotelJob($jobId);
    }

    /** Whether the tour carries the Transport Desk's "work complete" mark and the hotel messages (schema 21). */
    private function hasDeskJobs(): bool
    {
        if ($this->hasDeskJobs === null) {
            try {
                $this->hasDeskJobs = (bool) $this->db->query("SHOW COLUMNS FROM `tf_tour` LIKE 'transport_complete_at'")->getResultArray()
                    && (bool) $this->db->query("SHOW TABLES LIKE 'tf_hotel_message'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasDeskJobs = false;
            }
        }
        return $this->hasDeskJobs;
    }

    /** Whether a tour can be deleted by Admin — archived, restorable (schema 22). */
    private function hasTourArchive(): bool
    {
        if ($this->hasTourArchive === null) {
            try {
                $this->hasTourArchive = (bool) $this->db->query("SHOW COLUMNS FROM `tf_tour` LIKE 'archived_at'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasTourArchive = false;
            }
        }
        return $this->hasTourArchive;
    }

    /** The SQL that keeps a deleted (archived) tour out of a list, when the column exists. */
    private function liveTourSql(string $alias = 't'): string
    {
        return $this->hasTourArchive() ? " AND {$alias}.archived_at IS NULL" : '';
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function meta(string $k, ?string $default = null): ?string
    {
        $r = $this->db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray();
        return $r['v'] ?? $default;
    }

    private static function cut($v, int $len): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        return mb_substr($v, 0, $len);
    }

    // =====================================================================
    //  The trail
    // =====================================================================

    public function event(int $intakeId, ?int $tourId, string $lane, ?int $jobId, string $event, ?string $from, ?string $to, ?string $note = null): void
    {
        $this->db->query(
            'INSERT INTO tf_tour_event (intake_id, tour_id, lane, job_id, event, from_status, to_status, note, `by`, `at`) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$intakeId, $tourId, $lane, $jobId, $event, $from, $to, self::cut($note, 500), $this->user, $this->now()]
        );
    }

    // =====================================================================
    //  Confirm
    // =====================================================================

    public function tourForIntake(int $intakeId): ?array
    {
        return $this->db->query('SELECT * FROM tf_tour WHERE intake_id = ?', [$intakeId])->getRowArray();
    }

    /**
     * What Confirm would refuse, before it is pressed. Only the essentials stop it;
     * every other gap travels with the tour as a flag on the card it belongs to.
     */
    public function confirmBlockers(array $it): array
    {
        $out = [];
        if (trim((string) ($it['agent_name'] ?? '')) === '' && trim((string) ($it['agent_acc'] ?? '')) === '') {
            $out[] = 'the agent';
        }
        if (empty($it['arrival_date'])) {
            $out[] = 'the arrival date';
        }
        if (empty($it['departure_date'])) {
            $out[] = 'the departure date';
        }
        if ((int) ($it['pax_total'] ?? 0) <= 0) {
            $out[] = 'the party size';
        }
        if (empty($it['days'])) {
            $out[] = 'at least one night';
        }
        // schema 32: no hotel night on the departure day, and a line for every night the dates give
        if (!empty($it['departure_rows'])) {
            $out[] = 'the night(s) that fall on the departure day settled (Make it the departure day, or It\'s an extra night)';
        }
        if (!empty($it['missing_nights'])) {
            $out[] = 'a line for every night the dates give (nights ' . TourDates::runs($it['missing_nights']) . ' have none)';
        }
        // the agent accepts a quotation that was sent: Confirm records which version
        if ($this->hasServiceJobs() && $this->issuedQuote((int) ($it['id'] ?? 0)) === null) {
            $out[] = 'an issued quotation (Go for Quotation, then Issue the version the agent accepted)';
        }
        // schema 33: the version confirmed stands for the working inquiry as it is now — one issued
        // after the last Edit Request applied, whether or not that request moved the price
        $stale = EditRequests::confirmBlocker($this->db, (int) ($it['id'] ?? 0));
        if ($stale !== null) {
            $out[] = $stale;
        }
        return $out;
    }

    /** The version the agent accepted: the one issued, newest if more than one somehow is. */
    public function issuedQuote(int $intakeId): ?array
    {
        if ($intakeId <= 0) {
            return null;
        }
        try {
            return $this->db->query("SELECT id, version, sell_usd, per_person_usd, issued_at, issued_by FROM tf_quote WHERE intake_id = ? AND status = 'issued' ORDER BY version DESC LIMIT 1", [$intakeId])->getRowArray() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The tour's own number, from settings: PREFIX-YEAR-COUNT, the count restarting each
     * year and padded to tour_pad digits — "AVH-2026-00125". Taken once, when the tour is
     * confirmed; a tour keeps it for good. The counter is stepped atomically and a number
     * already on a tour (a counter put back by hand) is stepped past, never reused.
     */
    public function nextTourNo(): string
    {
        $prefix = trim((string) ($this->meta('tour_prefix') ?? 'AVH'));
        $pad    = max(1, min(8, (int) ($this->meta('tour_pad') ?? 5)));
        $year   = date('Y');
        if ((string) ($this->meta('tour_year') ?? '') !== $year) {
            $this->db->query("INSERT INTO tf_meta (k, v) VALUES ('tour_year', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [$year]);
            $this->db->query("INSERT INTO tf_meta (k, v) VALUES ('tour_next', '1') ON DUPLICATE KEY UPDATE v = '1'");
        }
        for ($guard = 0; $guard < 1000; $guard++) {
            $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'tour_next'");
            $n = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray()['n'] ?? 0);
            if ($n <= 0) {
                $this->db->query("INSERT INTO tf_meta (k, v) VALUES ('tour_next', '1') ON DUPLICATE KEY UPDATE v = v");
                continue;
            }
            $no = ($prefix !== '' ? $prefix . '-' : '') . $year . '-' . str_pad((string) ($n - 1), $pad, '0', STR_PAD_LEFT);
            if (!$this->db->query('SELECT id FROM tf_tour WHERE tour_no = ?', [$no])->getRowArray()) {
                return $no;
            }
        }
        throw new \RuntimeException('No free tour number could be taken; check the tour numbering on the Manage screen.');
    }

    /**
     * The agent said yes. One transaction: the tour, the hotel cards, the transport
     * cards, the trail — or nothing. On an inquiry unlocked for amendment, cards still
     * at their first step are rebuilt and cards already in progress are kept and marked.
     */

    /**
     * tf-transmail-r1: the transport booking requests are prepared from the record as soon as there is somebody
     * to ask — at Confirm, and again each time the Transport desk sets a vehicle or engages a driver or a guide.
     * Preparing is not sending and not confirming: it writes the letter and names a missing address, nothing more.
     */
    private function prepareTransportRequests(int $tourId): void
    {
        try {
            (new TransportMailer($this->db, $this->user, $this->user))->prepare($tourId);
        } catch (\Throwable $e) {
            // the desk's own work never waits on this
        }
    }

    /**
     * tf-supmail-r1 (4 Nov 2026): the same for the ACTIVITY suppliers and the RESTAURANTS — a request is
     * prepared for every line that has none, and goes out where there is an address and automatic sending is
     * on. One per line, guarded at the database, so calling this again after a change is safe. Preparing and
     * sending leave every line exactly as it was: only the desk's own record of the answer confirms a booking.
     */
    private function prepareSupplierRequests(int $tourId): void
    {
        try {
            (new SupplierMailer($this->db, $this->user, $this->user))->prepare($tourId);
        } catch (\Throwable $e) {
            // the desk's own work never waits on this
        }
    }

    public function confirm(int $intakeId): array
    {
        $it = $this->intake()->get($intakeId);
        if (!$it) {
            throw new \RuntimeException('Inquiry not found.');
        }
        if (in_array((string) $it['status'], ['declined', 'cancelled'], true)) {
            throw new \RuntimeException('A ' . $it['status'] . ' inquiry cannot be confirmed. Reopen it first.');
        }
        $existing = $this->tourForIntake($intakeId);
        if ($existing && (string) $existing['stage'] === 'cancelled') {
            throw new \RuntimeException('This tour was cancelled. Management can reopen it from the Tour Board.');
        }
        if ((string) $it['status'] === 'confirmed' && $existing && !(int) $existing['amending']) {
            throw new \RuntimeException('Already confirmed on ' . $existing['confirmed_at'] . '.');
        }
        $blockers = $this->confirmBlockers($it);
        if ($blockers) {
            throw new \RuntimeException('Confirm needs ' . implode(', ', $blockers) . '. Nothing else is required — every other gap travels with the tour as a flag.');
        }

        $now = $this->now();
        $this->db->transStart();

        $header = [
            'agent_acc' => $it['agent_acc'], 'agent_name' => $it['agent_name'], 'tour_name' => $it['tour_name'],
            'market_code' => $it['market_code'], 'country_iso' => $it['country_iso'],
            'arrival_date' => $it['arrival_date'], 'departure_date' => $it['departure_date'], 'nights' => $it['nights'],
            'pax_total' => $it['pax_total'], 'pax_adults' => $it['pax_adults'], 'pax_children' => $it['pax_children'],
            'rooms_note' => $it['rooms_note'], 'hotel_class' => $it['hotel_class'],
            'arrival_flight' => $it['arrival_flight'], 'departure_flight' => $it['departure_flight'],
            // ownership travels with the tour: the inquiry's handling agent is the tour's
            'handler_id' => $it['handler_id'] ?? null, 'handler_name' => $it['handler_name'] ?? null,
        ];

        // the version the agent accepted travels with the tour (schema 20)
        $sold = $this->hasServiceJobs() ? $this->issuedQuote($intakeId) : null;
        if ($sold) {
            $header['quote_id']      = (int) $sold['id'];
            $header['quote_version'] = (int) $sold['version'];
        }

        if ($existing) {
            $tourId = (int) $existing['id'];
            $sets = implode(', ', array_map(static fn ($k) => "`{$k}` = ?", array_keys($header)));
            $this->db->query("UPDATE tf_tour SET {$sets}, amending = 0, updated_at = ?, updated_by = ? WHERE id = ?", array_merge(array_values($header), [$now, $this->user, $tourId]));
            $rebuilt = $this->rebuildCards($tourId, $it);
            $this->event($intakeId, $tourId, 'quotation', null, 'reconfirmed', 'working', 'confirmed', $rebuilt . ($sold ? ' Sold as version ' . $sold['version'] . '.' : ''));
        } else {
            // the tour's own number from settings (schema 20); before that, the inquiry number carried through
            $tourNo = $this->hasServiceJobs() ? $this->nextTourNo() : (string) $it['inquiry_no'];
            $cols = array_merge(['tour_no', 'intake_id', 'stage', 'confirmed_at', 'confirmed_by', 'created_at', 'updated_at', 'updated_by'], array_keys($header));
            $vals = array_merge([$tourNo, $intakeId, 'confirmed', $now, $this->user, $now, $now, $this->user], array_values($header));
            $this->db->query('INSERT INTO tf_tour (' . implode(', ', array_map(static fn ($c) => "`{$c}`", $cols)) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', $vals);
            $tourId = (int) $this->db->insertID();
            $h = $this->buildHotelCards($tourId, $it);
            $t = $this->buildTransportCards($tourId, $it);
            $a = $this->hasServiceJobs() ? $this->buildServiceCards($tourId, $it) : 0;
            $this->event($intakeId, $tourId, 'quotation', null, 'confirmed', (string) $it['status'], 'confirmed',
                "Tour {$tourNo}: one job to Hotel Bookings ({$h} hotel stay(s), {$a} activity/ticket line(s)) and one to Transport ({$t} vehicle(s))." . ($sold ? ' Sold as version ' . $sold['version'] . '.' : ''));
        }

        $this->db->query('UPDATE tf_intake SET status = ?, confirmed_at = ?, confirmed_by = ?, updated_at = ?, updated_by = ? WHERE id = ?', ['confirmed', $now, $this->user, $now, $this->user, $intakeId]);
        $this->db->transComplete();
        $this->prepareTransportRequests($tourId);
        $this->prepareSupplierRequests($tourId);
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('Confirm could not be saved; nothing was changed.');
        }
        return $this->get($tourId);
    }

    /**
     * Consecutive nights at the same hotel are one stay. The key is the supplier code
     * when the hotel matched, the panel's hotel id when only that did, and otherwise
     * the name exactly as the agent wrote it. A night with nothing named is a stay of
     * its own, flagged, so the desk sees the hole where the hotel should be.
     */
    public static function stays(array $it): array
    {
        $days = $it['days'] ?? [];
        usort($days, static fn ($a, $b) => [(int) $a['day_no'], (int) $a['id']] <=> [(int) $b['day_no'], (int) $b['id']]);
        $arrival = $it['arrival_date'] ?? null;
        $stays = [];
        $cur   = null;
        foreach ($days as $d) {
            $name = trim((string) ($d['hotel_name_raw'] ?? ''));
            $acc  = trim((string) ($d['hotel_acc'] ?? ''));
            $hid  = (int) ($d['hotel_id'] ?? 0);
            $named = $name !== '' || $acc !== '' || $hid > 0;
            $key = !$named ? 'none:' . $d['day_no'] : ($acc !== '' ? 'acc:' . $acc : ($hid > 0 ? 'hid:' . $hid : 'name:' . mb_strtolower($name)));
            $date = $d['day_date'] ?? null;
            if (!$date && $arrival && (int) $d['day_no'] >= 1) {
                $date = date('Y-m-d', strtotime($arrival . ' +' . ((int) $d['day_no'] - 1) . ' days'));
            }
            // schema 32: one line is one night — the dates decide the nights, and a line an email once wrote
            // as "2 nights" beside a line of its own for the second night is not counted twice
            $nights = 1;
            // the room mix the night is costed on (typed, or the groups present), in words
            $mix = is_array($d['room_mix'] ?? null) ? $d['room_mix'] : null;
            $mixWords = [];
            foreach ([['sgl', 'SGL'], ['dbl', 'DBL'], ['twn', 'TWN'], ['tpl', 'TPL']] as [$k, $l]) {
                if ($mix && (int) ($mix[$k] ?? 0) > 0) {
                    $mixWords[] = (int) $mix[$k] . ' ' . $l;
                }
            }
            // the room type as this booking describes it (tf-walkfix-r1), else the master's name
            $rtWord  = trim((string) ($d['room_type_text'] ?? '')) !== '' ? trim((string) $d['room_type_text']) : (string) ($d['room_type_name'] ?? '');
            $mixNote = $mixWords ? implode(' · ', $mixWords) . ($rtWord !== '' ? ' — ' . $rtWord : '') : null;
            $mixRooms = $mix && (int) ($mix['rooms'] ?? 0) > 0 ? (int) $mix['rooms'] : null;
            $mixPax = $mix && $mix['pax'] !== null && (int) $mix['pax'] > 0 ? (int) $mix['pax'] : null;
            if ($cur && $cur['key'] === $key && $named) {
                $cur['day_to']  = (int) $d['day_no'];
                $cur['nights'] += $nights;
                $cur['rooms']   = $cur['rooms'] ?? $mixRooms ?? ($d['rooms'] !== null ? (int) $d['rooms'] : null);
                $cur['meal']    = $cur['meal'] ?? self::cut($d['meal_plan'] ?? null, 4);
                if ($mixNote !== null) {
                    // the party may change part-way through the stay (a group leaves): kept night by night
                    $cur['mix_by_night'][(int) $d['day_no']] = $mixNote;
                }
                if (!empty($d['special_note'])) {
                    $cur['notes'][] = 'Night ' . $d['day_no'] . ': ' . $d['special_note'];
                }
                if ($cur['area'] === null && !empty($d['area'])) {
                    $cur['area'] = self::cut($d['area'], 160);
                }
                continue;
            }
            if ($cur) {
                $stays[] = $cur;
            }
            $cur = [
                'key' => $key, 'day_from' => (int) $d['day_no'], 'day_to' => (int) $d['day_no'],
                'hotel_acc' => $acc !== '' ? $acc : null, 'hotel_id' => $hid > 0 ? $hid : null,
                'hotel_name' => $name !== '' ? self::cut($name, 190) : null, 'area' => self::cut($d['area'] ?? null, 160),
                'room_type' => $rtWord !== '' ? self::cut($rtWord, 120) : null,
                'check_in' => $date, 'nights' => $nights,
                'rooms' => $mixRooms ?? ($d['rooms'] !== null && $d['rooms'] !== '' ? (int) $d['rooms'] : null),
                'mix_by_night' => $mixNote !== null ? [(int) $d['day_no'] => $mixNote] : [], 'pax' => $mixPax,
                'meal' => self::cut($d['meal_plan'] ?? null, 4),
                'notes' => !empty($d['special_note']) ? ['Night ' . $d['day_no'] . ': ' . $d['special_note']] : [],
                'needs_hotel' => !$named,
                'needs_supplier' => $named && $acc === '',
            ];
        }
        if ($cur) {
            $stays[] = $cur;
        }
        foreach ($stays as &$s) {
            $s['check_out']  = $s['check_in'] ? date('Y-m-d', strtotime($s['check_in'] . ' +' . $s['nights'] . ' days')) : null;
            $s['needs_meal'] = $s['meal'] === null;
            // one note for the Hotel Desk: the mix, and where it changes night by night
            $byNight = $s['mix_by_night'] ?? [];
            $s['rooms_note'] = count(array_unique($byNight)) > 1
                ? implode('; ', array_map(static fn ($n, $w) => 'night ' . $n . ': ' . $w, array_keys($byNight), $byNight))
                : ($byNight ? reset($byNight) : null);
            unset($s['key'], $s['mix_by_night']);
        }
        unset($s);
        return $stays;
    }

    private function crewRoomsNote(array $it): ?string
    {
        $bits = [];
        foreach (['driver' => 'Driver', 'guide' => 'Guide'] as $kind => $label) {
            $a = $it['accom'][$kind] ?? null;
            if ($a && (int) ($a['required'] ?? 0) === 1) {
                $bits[] = $label . ' room required' . (!empty($a['nights']) ? " ({$a['nights']} night(s))" : '');
            }
        }
        return $bits ? self::cut(implode('; ', $bits), 255) : null;
    }

    private function buildHotelCards(int $tourId, array $it): int
    {
        $crewRooms = $this->crewRoomsNote($it);
        $n = 0;
        foreach (self::stays($it) as $i => $s) {
            $this->db->query(
                'INSERT INTO tf_hotel_job (tour_id, seq, day_from, day_to, hotel_acc, hotel_id, hotel_name, area, check_in, check_out, nights, rooms, rooms_note, meal_plan, pax, special_note, crew_rooms, needs_hotel, needs_supplier, needs_meal, status, updated_at, updated_by' . ($this->hasHotelRoomType() ? ', room_type_text' : '') . ')
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?' . ($this->hasHotelRoomType() ? ',?' : '') . ')',
                array_merge([
                    $tourId, $i, $s['day_from'], $s['day_to'], $s['hotel_acc'], $s['hotel_id'], $s['hotel_name'], $s['area'], $s['check_in'], $s['check_out'], $s['nights'],
                    $s['rooms'], self::cut($s['rooms_note'] ?? ($it['rooms_note'] ?? null), 255), $s['meal'], $s['pax'] ?? ($it['pax_total'] !== null ? (int) $it['pax_total'] : null),
                    self::cut($s['notes'] ? implode("\n", $s['notes']) : null, 1000), $crewRooms,
                    $s['needs_hotel'] ? 1 : 0, $s['needs_supplier'] ? 1 : 0, $s['needs_meal'] ? 1 : 0, 'to_book', $this->now(), $this->user,
                ], $this->hasHotelRoomType() ? [$s['room_type'] ?? null] : [])
            );
            $n++;
        }
        return $n;
    }

    /**
     * Activities and tickets, one line each, exactly as the quotation carried them: every
     * service line and every excursion, except what the party arranges for themselves, what
     * is not happening, and the guide and transport lines (those are the Transport Desk's
     * crew and vehicle). Nothing is added that the quotation did not say; a line the desk
     * finds nothing to arrange for is marked "not needed" there, not left out here.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function serviceLines(array $it): array
    {
        $arrival = $it['arrival_date'] ?? null;
        $date = static function ($dayNo) use ($arrival): ?string {
            $d = (int) $dayNo;
            return $arrival && $d >= 1 ? date('Y-m-d', strtotime($arrival . ' +' . ($d - 1) . ' days')) : null;
        };
        $out = [];
        foreach ($it['services'] ?? [] as $sv) {
            $kind = (string) ($sv['kind'] ?? 'other');
            if (in_array($kind, self::SERVICE_KINDS_SKIPPED, true) || in_array((string) ($sv['pay_mode'] ?? ''), self::PAY_MODES_SKIPPED, true) || ServiceLookup::isFree($sv)) {
                continue;
            }
            $out[] = [
                'source' => 'service', 'source_id' => (int) $sv['id'], 'kind' => $kind,
                'title' => self::cut($sv['title'] ?? '', 255) ?? '(untitled)', 'day_no' => (int) ($sv['day_no'] ?? 0) ?: null,
                'on_date' => $date($sv['day_no'] ?? 0), 'pax' => self::intOrNull($sv['pax'] ?? null) ?? self::intOrNull($it['pax_total'] ?? null),
                'qty' => $sv['qty'] !== null && $sv['qty'] !== '' ? (float) $sv['qty'] : null, 'pay_mode' => self::cut($sv['pay_mode'] ?? null, 20),
                'special_note' => self::cut($sv['special_note'] ?? null, 500),
                // schema 37: which Entrance Fee row the line stands for, so the ticket stock can serve it
                'fee_id' => !empty($sv['fee_id']) ? (int) $sv['fee_id'] : null, 'fee_code' => self::cut($sv['fee_code'] ?? null, 100),
                // schema 39: the place of the Place to Visit / Entrance Fee Master it is — Ticket Stock serves it by its place
                'place_id' => !empty($sv['place_id']) ? (int) $sv['place_id'] : null,
                // tf-supmail-r3: the time agreed while quoting travels with the line, so the supplier's request
                // can go without the desk entering it again. A meal carries its own time in the same field.
                'on_time' => self::timeOrNull($sv['on_time'] ?? ($sv['meal_time'] ?? null)),
            ];
        }
        foreach ($it['excursions'] ?? [] as $ex) {
            $out[] = [
                'source' => 'excursion', 'source_id' => (int) $ex['id'], 'kind' => 'excursion',
                'title' => self::cut($ex['name'] ?? '', 255) ?? '(untitled)', 'day_no' => (int) ($ex['day_no'] ?? 0) ?: null,
                'on_date' => $date($ex['day_no'] ?? 0), 'pax' => self::intOrNull($it['pax_total'] ?? null),
                'qty' => $ex['qty'] !== null && $ex['qty'] !== '' ? (float) $ex['qty'] : null, 'pay_mode' => 'in_tour',
                'special_note' => self::cut(trim(((int) ($ex['includes_transport'] ?? 0) ? 'Transport included in the excursion. ' : '') . (string) ($ex['note'] ?? '')), 500),
                'fee_id' => null, 'fee_code' => null, 'place_id' => null, 'on_time' => null,
            ];
        }
        usort($out, static fn ($a, $b) => [(int) ($a['day_no'] ?? 0), $a['source'], (int) $a['source_id']] <=> [(int) ($b['day_no'] ?? 0), $b['source'], (int) $b['source_id']]);
        return $out;
    }

    private static function intOrNull($v): ?int
    {
        return $v === null || $v === '' ? null : (int) $v;
    }

    /** tf-supmail-r2: whether this install carries the activity time column yet */
    private function hasServiceTime(): bool
    {
        static $has = null;
        if ($has === null) {
            try {
                $has = (bool) $this->db->query("SHOW COLUMNS FROM `tf_service_job` LIKE 'on_time'")->getResultArray();
            } catch (\Throwable $e) {
                $has = false;
            }
        }
        return $has;
    }

    /**
     * tf-supmail-r2 (Shantha, 4 Nov 2026): set or clear the time an activity line is booked for.
     * A blank clears it; anything that is not a real time of day is refused rather than stored as a guess.
     */
    public function setServiceTime(int $jobId, ?string $time, ?string $why = null): array
    {
        $j = $this->serviceJob($jobId);
        if (!$this->hasServiceTime()) {
            throw new \RuntimeException('This install does not carry the activity time yet: open Tour Inquiry as the administrator and press Install now.');
        }
        $raw = trim((string) ($time ?? ''));
        $t   = self::timeOrNull($raw);
        if ($raw !== '' && $t === null) {
            throw new \InvalidArgumentException('Give the time as hours and minutes on the 24-hour clock, for example 09:30 or 14:00.');
        }
        $was = $j['on_time'] ?? null;
        if ((string) $was === (string) $t) {
            return $this->serviceJob($jobId);
        }
        $this->db->transStart();
        $this->db->query('UPDATE tf_service_job SET on_time = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$t, $this->now(), $this->user, $jobId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'service', $jobId, 'time',
            $was !== null ? substr((string) $was, 0, 5) : null, $t !== null ? substr($t, 0, 5) : null,
            trim(($t === null ? 'The time was taken off.' : 'Booked for ' . substr($t, 0, 5) . '.') . ($why ? ' ' . mb_substr($why, 0, 300) : '')));
        $this->db->transComplete();
        // the request waiting on this line follows the record it was written from
        $this->prepareSupplierRequests((int) $j['tour_id']);
        return $this->serviceJob($jobId);
    }

    /** tf-supmail-r2: a time as the masters keep it, or null — never a guess */
    public static function timeOrNull($v): ?string
    {
        $v = trim((string) ($v ?? ''));
        if ($v === '') {
            return null;
        }
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $v, $m)) {
            return null;
        }
        return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) ($m[3] ?? 0));
    }

    private function insertServiceJob(int $tourId, int $seq, array $l): void
    {
        $fee   = $this->hasServiceFee();
        $place = $this->hasServicePlace();
        $time  = $this->hasServiceTime();
        $this->db->query(
            'INSERT INTO tf_service_job (tour_id, seq, source, source_id, kind, title, day_no, on_date, pax, qty, pay_mode, special_note, status, created_at, created_by, updated_at, updated_by' . ($fee ? ', fee_id, fee_code' : '') . ($place ? ', place_id' : '') . ($time ? ', on_time' : '') . ')
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?' . ($fee ? ',?,?' : '') . ($place ? ',?' : '') . ($time ? ',?' : '') . ')',
            array_merge([$tourId, $seq, $l['source'], $l['source_id'], self::cut($l['kind'], 20), $l['title'], $l['day_no'], $l['on_date'], $l['pax'], $l['qty'], $l['pay_mode'], $l['special_note'],
             'to_book', $this->now(), $this->user, $this->now(), $this->user], $fee ? [$l['fee_id'] ?? null, $l['fee_code'] ?? null] : [], $place ? [$l['place_id'] ?? null] : [],
             $time ? [self::timeOrNull($l['on_time'] ?? null)] : [])
        );
    }

    private function buildServiceCards(int $tourId, array $it): int
    {
        $n = 0;
        foreach (self::serviceLines($it) as $i => $l) {
            $this->insertServiceJob($tourId, $i, $l);
            $n++;
        }
        return $n;
    }

    private function buildTransportCards(int $tourId, array $it): int
    {
        $vehicles = $it['vehicles'] ?? [];
        if (!$vehicles) {
            return 0;
        }
        $mil   = $it['mileage'] ?? [];
        $days  = [];
        foreach ($it['days'] ?? [] as $d) {
            $days[(int) $d['day_no']] = $d;
        }
        $dayCount = max(count($days), (int) ($it['nights'] ?? 0) + 1);
        $docTypes = $this->db->query('SELECT code, name, phase FROM tf_doc_type WHERE active = 1 ORDER BY sort_order, code')->getResultArray();
        $groups = [];
        foreach ($it['groups'] ?? [] as $g) {
            $groups[(int) $g['id']] = $g;
        }
        $byVeh = [];
        foreach ($mil['by_vehicle'] ?? [] as $bv) {
            $byVeh[(int) $bv['vehicle_seq']] = $bv;
        }
        $n = 0;
        foreach ($vehicles as $i => $v) {
            // one card per vehicle line, with that line's own kilometres (charged, never the
            // waived ones), its group and its nights — so two vans on one tour are two jobs
            $seqV = (int) ($v['sort_order'] ?? $i);
            $bv   = $byVeh[$seqV] ?? null;
            $grp  = ($v['group_id'] ?? null) !== null ? ($groups[(int) $v['group_id']] ?? null) : null;
            $from = (int) ($v['day_from'] ?? 0) ?: (int) (($grp['day_from'] ?? null) ?: 1);
            $to   = (int) ($v['day_to'] ?? 0) ?: (int) (($grp['day_to'] ?? null) ?: ($it['nights'] ?? 0));
            $vdays = $to >= $from && $to > 0 ? ($to - $from + 2) : $dayCount;   // nights engaged plus the departure day
            $noteBits = [];
            if (!empty($v['label'])) {
                $noteBits[] = $v['label'];
            }
            if ($grp) {
                $noteBits[] = 'carries ' . $grp['name'] . ' (' . (int) $grp['pax'] . ' pax)';
            }
            if (($v['day_from'] ?? null) !== null || ($v['day_to'] ?? null) !== null || $grp) {
                $noteBits[] = 'nights ' . $from . ' to ' . $to;
            }
            foreach ($it['sectors'] ?? [] as $s) {
                if ((int) ($s['vehicle_seq'] ?? 0) === $seqV && !empty($s['waived_by'])) {
                    $noteBits[] = 'day ' . (int) $s['day_no'] . ': ' . $s['waived_by'] . ' includes transport (not charged)';
                }
            }
            foreach ($it['vehicle_days'] ?? [] as $vd) {
                if ((int) $vd['vehicle_seq'] === $seqV && (int) $vd['retained'] === 0) {
                    $noteBits[] = 'day ' . (int) $vd['day_no'] . ': released' . (!empty($vd['note']) ? ' — ' . $vd['note'] : '');
                }
            }
            $this->db->query(
                'INSERT INTO tf_transport_job (tour_id, seq, intake_vehicle_id, class_code, vehicle_type, seats, qty, days, km_quoted, extra_km_quoted, km_total, extra_km_total, km_rate, rate_id, currency, vehicle_note, status, updated_at, updated_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $tourId, $i, (int) $v['id'], $v['class_code'] ?? null, self::cut($v['type'] ?? 'Vehicle', 120) ?? 'Vehicle', $v['seats'] ?? null, max(1, (int) ($v['qty'] ?? 1)), $vdays,
                    $bv['km'] ?? ($mil['km'] ?? null), $bv['extra_km'] ?? ($mil['extra_km'] ?? null), $bv['km'] ?? ($mil['km'] ?? null), $bv['extra_km'] ?? ($mil['extra_km'] ?? null),
                    $v['km_rate'] ?? null, $v['rate_id'] ?? null, $v['currency'] ?? 'LKR', self::cut($noteBits ? implode(' · ', array_unique($noteBits)) : null, 255), 'to_allocate', $this->now(), $this->user,
                ]
            );
            $jobId = (int) $this->db->insertID();
            $this->copyLegs($jobId, $it, $days, $seqV);
            $this->copyCrew($jobId, $it, $i === 0);
            foreach ($docTypes as $t) {
                $this->db->query('INSERT INTO tf_transport_doc (job_id, doc_code, doc_name, phase, state) VALUES (?,?,?,?,?)', [$jobId, $t['code'], $t['name'], $t['phase'], 'pending']);
            }
            $n++;
        }
        return $n;
    }

    private function copyLegs(int $jobId, array $it, array $days, int $seqV = 0): void
    {
        $arrival = $it['arrival_date'] ?? null;
        foreach ($it['sectors'] ?? [] as $s) {
            // only this vehicle's legs; a leg waived because an excursion brought its own
            // transport is not driving this vehicle does, so it is not on the card
            if ((int) ($s['vehicle_seq'] ?? 0) !== $seqV || !empty($s['waived_by']) || !empty($s['removed_at'])) {
                continue;
            }
            $dayNo = (int) $s['day_no'];
            $day   = $days[$dayNo] ?? null;
            $date  = $day['day_date'] ?? null;
            if (!$date && $arrival && $dayNo >= 1) {
                $date = date('Y-m-d', strtotime($arrival . ' +' . ($dayNo - 1) . ' days'));
            }
            $cols = 'job_id, day_no, day_date, seq, from_place, to_place, km, extra_km, kind, night_hotel, night_area, origin, note';
            $bind = [
                $jobId, $dayNo, $date, (int) ($s['seq'] ?? 0), self::cut($s['from_place'] ?? null, 190), self::cut($s['to_place'] ?? null, 190),
                $s['km'], $s['extra_km'], $s['kind'] ?? 'transfer',
                $day ? self::cut($day['hotel_name_raw'] ?? null, 190) : null, $day ? self::cut($day['area'] ?? null, 160) : null,
                'costed', self::cut($s['note'] ?? null, 255),
            ];
            if ($this->hasActualLegs()) {
                // Google's figure travels with the leg, and which figure the quotation used;
                // the actual driven kilometres are the desk's to enter after the tour
                $cols .= ', google_km, quoted_by';
                array_push($bind, $s['google_km'] ?? null, self::cut($s['km_by'] ?? null, 16));
            }
            $this->db->query('INSERT INTO tf_transport_leg (' . $cols . ') VALUES (' . implode(',', array_fill(0, count($bind), '?')) . ')', $bind);
        }
    }

    /**
     * The crew travel with the party: every role on the first vehicle, a driver on each
     * further one. Each row starts with the days the quotation priced — from the arrival
     * date, for the quoted number of days (schema 24) — for the desk to confirm or correct;
     * a payment voucher is only ever issued for the dates on the row.
     */
    private function copyCrew(int $jobId, array $it, bool $first): void
    {
        $dated   = $this->hasCrewDates();
        $arrival = self::cut($it['arrival_date'] ?? null, 10);
        foreach ($it['crew'] ?? [] as $c) {
            if (!$first && (string) $c['kind'] !== 'driver') {
                continue;
            }
            $cols = 'job_id, role_code, role_name, kind, language, days, bata, currency, amount, supplier_acc, person_name, person_bata, status, updated_at, updated_by';
            $bind = [
                $jobId, $c['role_code'], $c['role_name'], $c['kind'], $c['language'] ?? null, $c['days'] ?? null, $c['bata'] ?? null, $c['currency'] ?? 'LKR', $c['amount'] ?? null,
                $first ? ($c['supplier_acc'] ?? null) : null, $first ? ($c['person_name'] ?? null) : null, $first ? ($c['person_bata'] ?? null) : null,
                ($first && !empty($c['supplier_acc'])) ? 'engaged' : 'to_engage', $this->now(), $this->user,
            ];
            if ($dated) {
                $days = (int) ($c['days'] ?? 0);
                $cols .= ', date_from, date_to';
                array_push($bind, $arrival, ($arrival && $days >= 1) ? date('Y-m-d', strtotime($arrival . ' +' . ($days - 1) . ' days')) : null);
            }
            $this->db->query('INSERT INTO tf_transport_crew (' . $cols . ') VALUES (' . implode(',', array_fill(0, count($bind), '?')) . ')', $bind);
        }
    }

    /** After an amendment: cards still at their first step are rebuilt, the rest are kept and marked. */
    private function rebuildCards(int $tourId, array $it): string
    {
        // whatever was confirmed before the amendment, neither desk's work is complete now
        $this->dropReservationsMark($tourId);
        $this->dropTransportMark($tourId);
        $keptH = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_hotel_job WHERE tour_id = ? AND status NOT IN ('to_book','cancelled')", [$tourId])->getRowArray()['n'] ?? 0);
        $keptT = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_transport_job WHERE tour_id = ? AND status NOT IN ('to_allocate','cancelled')", [$tourId])->getRowArray()['n'] ?? 0);
        $this->db->query("DELETE FROM tf_hotel_job WHERE tour_id = ? AND status = 'to_book'", [$tourId]);
        $this->db->query("DELETE FROM tf_transport_job WHERE tour_id = ? AND status = 'to_allocate'", [$tourId]);
        $this->db->query("UPDATE tf_hotel_job SET amended = 1, updated_at = ?, updated_by = ? WHERE tour_id = ? AND status NOT IN ('cancelled')", [$this->now(), $this->user, $tourId]);
        $this->db->query("UPDATE tf_transport_job SET amended = 1, updated_at = ?, updated_by = ? WHERE tour_id = ? AND status NOT IN ('cancelled')", [$this->now(), $this->user, $tourId]);

        // rebuild only the stays that no kept card already covers (same first night)
        $covered = array_map('intval', array_column($this->db->query('SELECT day_from FROM tf_hotel_job WHERE tour_id = ?', [$tourId])->getResultArray(), 'day_from'));
        $crewRooms = $this->crewRoomsNote($it);
        $seq = (int) ($this->db->query('SELECT COALESCE(MAX(seq), -1) m FROM tf_hotel_job WHERE tour_id = ?', [$tourId])->getRowArray()['m'] ?? -1) + 1;
        $newH = 0;
        foreach (self::stays($it) as $s) {
            if (in_array($s['day_from'], $covered, true)) {
                continue;
            }
            $this->db->query(
                'INSERT INTO tf_hotel_job (tour_id, seq, day_from, day_to, hotel_acc, hotel_id, hotel_name, area, check_in, check_out, nights, rooms, rooms_note, meal_plan, pax, special_note, crew_rooms, needs_hotel, needs_supplier, needs_meal, status, updated_at, updated_by' . ($this->hasHotelRoomType() ? ', room_type_text' : '') . ')
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?' . ($this->hasHotelRoomType() ? ',?' : '') . ')',
                array_merge([
                    $tourId, $seq++, $s['day_from'], $s['day_to'], $s['hotel_acc'], $s['hotel_id'], $s['hotel_name'], $s['area'], $s['check_in'], $s['check_out'], $s['nights'],
                    $s['rooms'], self::cut($s['rooms_note'] ?? ($it['rooms_note'] ?? null), 255), $s['meal'], $s['pax'] ?? ($it['pax_total'] !== null ? (int) $it['pax_total'] : null),
                    self::cut($s['notes'] ? implode("\n", $s['notes']) : null, 1000), $crewRooms,
                    $s['needs_hotel'] ? 1 : 0, $s['needs_supplier'] ? 1 : 0, $s['needs_meal'] ? 1 : 0, 'to_book', $this->now(), $this->user,
                ], $this->hasHotelRoomType() ? [$s['room_type'] ?? null] : [])
            );
            $newH++;
        }
        $newT = 0;
        if (!$this->db->query('SELECT id FROM tf_transport_job WHERE tour_id = ? LIMIT 1', [$tourId])->getRowArray()) {
            $newT = $this->buildTransportCards($tourId, $it);
        }
        // activities and tickets: lines still to book are rebuilt from the quotation; lines
        // the desk has worked on are kept and marked, and a line the quotation no longer has
        // is marked too rather than removed — the desk may hold a booking for it
        $newA = 0;
        $keptA = 0;
        if ($this->hasServiceJobs()) {
            $keptA = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_service_job WHERE tour_id = ? AND status NOT IN ('to_book','cancelled') AND archived_at IS NULL", [$tourId])->getRowArray()['n'] ?? 0);
            $this->db->query("DELETE FROM tf_service_job WHERE tour_id = ? AND status = 'to_book' AND archived_at IS NULL", [$tourId]);
            $this->db->query("UPDATE tf_service_job SET amended = 1, updated_at = ?, updated_by = ? WHERE tour_id = ? AND status <> 'cancelled' AND archived_at IS NULL", [$this->now(), $this->user, $tourId]);
            $have = [];
            foreach ($this->db->query('SELECT source, source_id FROM tf_service_job WHERE tour_id = ?', [$tourId])->getResultArray() as $r) {
                $have[$r['source'] . ':' . (int) $r['source_id']] = true;
            }
            $seqA = (int) ($this->db->query('SELECT COALESCE(MAX(seq), -1) m FROM tf_service_job WHERE tour_id = ?', [$tourId])->getRowArray()['m'] ?? -1) + 1;
            foreach (self::serviceLines($it) as $l) {
                if (isset($have[$l['source'] . ':' . (int) $l['source_id']])) {
                    continue;
                }
                $this->insertServiceJob($tourId, $seqA++, $l);
                $newA++;
            }
        }
        return "{$newH} hotel stay(s), {$newA} activity/ticket line(s) and {$newT} vehicle(s) rebuilt; {$keptH} hotel card(s), {$keptA} activity line(s) and {$keptT} transport card(s) already in progress kept and marked amended.";
    }

    // =====================================================================
    //  Management: un-confirm, unlock for amendment, cancel, reopen
    // =====================================================================

    private function tour(int $tourId): array
    {
        $t = $this->db->query('SELECT * FROM tf_tour WHERE id = ?', [$tourId])->getRowArray();
        if (!$t) {
            throw new \RuntimeException('Tour not found.');
        }
        return $t;
    }

    private function untouched(int $tourId): bool
    {
        $h = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_hotel_job WHERE tour_id = ? AND status <> 'to_book'", [$tourId])->getRowArray()['n'] ?? 0);
        $t = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_transport_job WHERE tour_id = ? AND status <> 'to_allocate'", [$tourId])->getRowArray()['n'] ?? 0);
        $a = $this->hasServiceJobs() ? (int) ($this->db->query("SELECT COUNT(*) n FROM tf_service_job WHERE tour_id = ? AND status <> 'to_book'", [$tourId])->getRowArray()['n'] ?? 0) : 0;
        return $h === 0 && $t === 0 && $a === 0;
    }

    /** Only while no card has moved: the cards are removed and the inquiry opens again. */
    public function unconfirm(int $tourId, ?string $why = null): array
    {
        $t = $this->tour($tourId);
        if (!$this->untouched($tourId)) {
            throw new \RuntimeException('A card on this tour has already moved, so it cannot be un-confirmed. Use Amend to correct it, or Cancel tour.');
        }
        $this->db->transStart();
        $this->event((int) $t['intake_id'], $tourId, 'quotation', null, 'unconfirmed', 'confirmed', 'working', $why);
        $this->db->query('DELETE FROM tf_tour WHERE id = ?', [$tourId]);
        $this->db->query("UPDATE tf_intake SET status = 'working', confirmed_at = NULL, confirmed_by = NULL, updated_at = ?, updated_by = ? WHERE id = ?", [$this->now(), $this->user, (int) $t['intake_id']]);
        $this->db->transComplete();
        return $this->intake()->get((int) $t['intake_id']);
    }

    /** Open the inquiry for correction; Confirm on it afterwards rebuilds what has not moved. */
    public function unlock(int $tourId, ?string $why = null): array
    {
        $t = $this->tour($tourId);
        if ((string) $t['stage'] === 'cancelled') {
            throw new \RuntimeException('A cancelled tour is not amended; reopen it first.');
        }
        $this->db->transStart();
        $this->db->query('UPDATE tf_tour SET amending = 1, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $tourId]);
        $this->db->query("UPDATE tf_intake SET status = 'working', updated_at = ?, updated_by = ? WHERE id = ?", [$this->now(), $this->user, (int) $t['intake_id']]);
        $this->event((int) $t['intake_id'], $tourId, 'quotation', null, 'unlocked', 'confirmed', 'working', $why);
        $this->db->transComplete();
        return $this->get($tourId);
    }

    public function cancel(int $tourId, ?string $why = null): array
    {
        $t = $this->tour($tourId);
        if ((string) $t['stage'] === 'cancelled') {
            return $this->get($tourId);
        }
        $why = self::cut($why, 400);
        if ($why === null) {
            throw new \RuntimeException('Say why the tour is cancelled; the reason stays on the trail.');
        }
        $now = $this->now();
        $this->db->transStart();
        foreach ($this->db->query("SELECT id, status FROM tf_hotel_job WHERE tour_id = ? AND status <> 'cancelled'", [$tourId])->getResultArray() as $j) {
            $this->event((int) $t['intake_id'], $tourId, 'hotel', (int) $j['id'], 'cancelled', (string) $j['status'], 'cancelled', $why);
        }
        foreach ($this->db->query("SELECT id, status FROM tf_transport_job WHERE tour_id = ? AND status <> 'cancelled'", [$tourId])->getResultArray() as $j) {
            $this->event((int) $t['intake_id'], $tourId, 'transport', (int) $j['id'], 'cancelled', (string) $j['status'], 'cancelled', $why);
        }
        $this->db->query("UPDATE tf_hotel_job SET status = 'cancelled', updated_at = ?, updated_by = ? WHERE tour_id = ? AND status <> 'cancelled'", [$now, $this->user, $tourId]);
        $this->db->query("UPDATE tf_transport_job SET status = 'cancelled', updated_at = ?, updated_by = ? WHERE tour_id = ? AND status <> 'cancelled'", [$now, $this->user, $tourId]);
        if ($this->hasServiceJobs()) {
            foreach ($this->db->query("SELECT id, status FROM tf_service_job WHERE tour_id = ? AND status <> 'cancelled'", [$tourId])->getResultArray() as $j) {
                $this->event((int) $t['intake_id'], $tourId, 'service', (int) $j['id'], 'cancelled', (string) $j['status'], 'cancelled', $why);
            }
            $this->db->query("UPDATE tf_service_job SET status = 'cancelled', updated_at = ?, updated_by = ? WHERE tour_id = ? AND status <> 'cancelled'", [$now, $this->user, $tourId]);
        }
        $this->db->query("UPDATE tf_tour SET stage = 'cancelled', amending = 0, cancelled_at = ?, cancelled_by = ?, cancel_why = ?, updated_at = ?, updated_by = ? WHERE id = ?", [$now, $this->user, $why, $now, $this->user, $tourId]);
        // the desks' "work complete" marks come off with the cards (the trail keeps who marked them and when)
        $this->dropReservationsMark($tourId);
        $this->dropTransportMark($tourId);
        $this->db->query("UPDATE tf_intake SET status = 'cancelled', updated_at = ?, updated_by = ? WHERE id = ?", [$now, $this->user, (int) $t['intake_id']]);
        $this->event((int) $t['intake_id'], $tourId, 'tour', null, 'cancelled', (string) $t['stage'], 'cancelled', $why);
        $this->db->transComplete();
        return $this->get($tourId);
    }

    /** A cancelled tour back on the desks: the cards come back at their first step. */
    public function reopen(int $tourId, ?string $why = null): array
    {
        $t = $this->tour($tourId);
        if ((string) $t['stage'] !== 'cancelled') {
            return $this->get($tourId);
        }
        $now = $this->now();
        $this->db->transStart();
        $this->db->query("UPDATE tf_hotel_job SET status = 'to_book', updated_at = ?, updated_by = ? WHERE tour_id = ? AND status = 'cancelled'", [$now, $this->user, $tourId]);
        $this->db->query("UPDATE tf_transport_job SET status = 'to_allocate', updated_at = ?, updated_by = ? WHERE tour_id = ? AND status = 'cancelled'", [$now, $this->user, $tourId]);
        if ($this->hasServiceJobs()) {
            $this->db->query("UPDATE tf_service_job SET status = 'to_book', updated_at = ?, updated_by = ? WHERE tour_id = ? AND status = 'cancelled'", [$now, $this->user, $tourId]);
        }
        $this->db->query("UPDATE tf_tour SET stage = 'confirmed', cancelled_at = NULL, cancelled_by = NULL, cancel_why = NULL, reservations_confirmed_at = NULL, reservations_confirmed_by = NULL, updated_at = ?, updated_by = ? WHERE id = ?", [$now, $this->user, $tourId]);
        $this->dropTransportMark($tourId);
        $this->db->query("UPDATE tf_intake SET status = 'confirmed', updated_at = ?, updated_by = ? WHERE id = ?", [$now, $this->user, (int) $t['intake_id']]);
        $this->event((int) $t['intake_id'], $tourId, 'tour', null, 'reopened', 'cancelled', 'confirmed', $why);
        $this->db->transComplete();
        return $this->get($tourId);
    }

    // =====================================================================
    //  Admin: delete (archive) a tour, restore it, remove it for good (schema 22)
    // =====================================================================

    /**
     * Deleted by Admin, the safe way: the tour is cancelled (every card cancelled, both
     * desks' marks off, the trail says why), then archived together with its inquiry, so it
     * is out of Inquiries, Hotel Bookings, Transport, the Board and every red count — and
     * kept whole, restorable from Admin → Deleted / archived. Nothing is removed.
     */
    public function deleteTour(int $tourId, ?string $why = null): array
    {
        if (!$this->hasTourArchive()) {
            throw new \RuntimeException('Not installed on this site yet.');
        }
        $t = $this->tour($tourId);
        $why = self::cut($why, 400);
        if ($why === null) {
            throw new \RuntimeException('Say why the tour is deleted; the reason stays on the trail.');
        }
        if (!empty($t['archived_at'])) {
            return $this->get($tourId);
        }
        $now = $this->now();
        $this->db->transStart();
        if ((string) $t['stage'] !== 'cancelled') {
            $this->cancel($tourId, $why);
        }
        $this->db->query('UPDATE tf_tour SET archived_at = ?, archived_by = ?, archive_note = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, $why, $now, $this->user, $tourId]);
        $this->event((int) $t['intake_id'], $tourId, 'tour', null, 'deleted', (string) $t['stage'], 'deleted', $why);
        // the inquiry goes with it, out of the Inquiries lists and counts (its archive rule is met: the tour is cancelled)
        try {
            $this->intake()->archive((int) $t['intake_id'], 'Tour ' . $t['tour_no'] . ' deleted — ' . $why);
        } catch (\Throwable $e) {
            // an inquiry that cannot be archived (a legacy link, say) stays where it is; the tour is deleted either way
        }
        $this->intake()->activity()->record('tour', $tourId, (int) $t['intake_id'], $tourId, 'deleted', 'Tour ' . $t['tour_no'] . ' deleted (archived, restorable) — ' . $why, [['what' => 'stage', 'from' => $t['stage'], 'to' => 'cancelled · deleted']]);
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('The tour could not be deleted; nothing was changed.');
        }
        return $this->get($tourId);
    }

    /** Back from Deleted / archived, with its inquiry; it stays cancelled until reopened from the Board or Admin. */
    public function restoreTour(int $tourId): array
    {
        $t = $this->tour($tourId);
        if (empty($t['archived_at'])) {
            return $this->get($tourId);
        }
        $now = $this->now();
        $this->db->transStart();
        $this->db->query('UPDATE tf_tour SET archived_at = NULL, archived_by = NULL, archive_note = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, $tourId]);
        try {
            $this->intake()->restore((int) $t['intake_id']);
        } catch (\Throwable $e) {
            // the inquiry may already be back; the tour is restored either way
        }
        $this->event((int) $t['intake_id'], $tourId, 'tour', null, 'restored', 'deleted', (string) $t['stage'], null);
        $this->intake()->activity()->record('tour', $tourId, (int) $t['intake_id'], $tourId, 'restored', 'Tour ' . $t['tour_no'] . ' restored from Deleted / archived (still cancelled — reopen it to put it back on the desks)');
        $this->db->transComplete();
        return $this->get($tourId);
    }

    /**
     * What removing a tour for good would take with it, so the screen can say so before
     * anyone types the number: the cards, their letters, the trail lines, the inquiry's
     * own standing (whether it could go too, and what stops it).
     */
    public function removalOf(int $tourId): array
    {
        $t = $this->tour($tourId);
        $n = fn (string $sql) => (int) ($this->db->query($sql, [$tourId])->getRowArray()['n'] ?? 0);
        $out = [
            'tour_id' => $tourId, 'tour_no' => $t['tour_no'], 'intake_id' => (int) $t['intake_id'], 'stage' => $t['stage'],
            'deleted' => !empty($t['archived_at']),
            'hotel_cards' => $n('SELECT COUNT(*) n FROM tf_hotel_job WHERE tour_id = ?'),
            'transport_cards' => $n('SELECT COUNT(*) n FROM tf_transport_job WHERE tour_id = ?'),
            'activity_lines' => $this->hasServiceJobs() ? $n('SELECT COUNT(*) n FROM tf_service_job WHERE tour_id = ?') : 0,
            'letters' => $this->hasDeskJobs() ? $n('SELECT COUNT(*) n FROM tf_hotel_message WHERE tour_id = ?') : 0,
            'vouchers' => $this->hasCrewDates() ? $n('SELECT COUNT(*) n FROM tf_crew_voucher WHERE tour_id = ?') : 0,
            'trail_lines' => $n('SELECT COUNT(*) n FROM tf_tour_event WHERE tour_id = ?'),
            'blocks' => [],
            'inquiry' => null,
        ];
        if (!$out['deleted']) {
            $out['blocks'][] = 'it is not deleted — delete it first (it is then restorable until removed)';
        }
        try {
            $dep = $this->intake()->dependencies((int) $t['intake_id']);
            // the tour itself is what is being removed, so it does not stand in the inquiry's way here
            $out['inquiry'] = ['inquiry_no' => $dep['inquiry_no'], 'archived' => $dep['archived'], 'blocks' => array_values(array_filter($dep['blocks_delete'], static fn ($b) => !str_starts_with($b, 'it has a tour')))];
        } catch (\Throwable $e) {
            $out['inquiry'] = null;
        }
        return $out;
    }

    /**
     * Gone for good — only a deleted tour, with its number typed to confirm. The tour row
     * goes and the database takes its cards, legs, crew, documents, letters and activity
     * lines with it (the foreign keys cascade); its trail lines go too, so nothing is left
     * pointing at a tour that is not there. One line on the Activity log says what went
     * and who removed it; that log is kept. With $withInquiry the inquiry follows, by its
     * own rule (archived, no issued quotation, no legacy link), or stays and the answer says why.
     */
    public function removeTourPermanently(int $tourId, string $confirmNo, bool $withInquiry = false): array
    {
        $r = $this->removalOf($tourId);
        if ($r['blocks']) {
            throw new \RuntimeException('This tour cannot be removed: ' . implode('; ', $r['blocks']) . '.');
        }
        if (trim($confirmNo) === '' || strcasecmp(trim($confirmNo), (string) $r['tour_no']) !== 0) {
            throw new \RuntimeException('Type the tour number (' . $r['tour_no'] . ') to confirm.');
        }
        $this->db->transStart();
        $this->db->query('DELETE FROM tf_tour_event WHERE tour_id = ?', [$tourId]);
        $this->db->query('DELETE FROM tf_tour WHERE id = ?', [$tourId]);
        $this->intake()->activity()->record('tour', $tourId, $r['intake_id'], null, 'removed',
            'Removed for good: tour ' . $r['tour_no'] . ' (' . $r['hotel_cards'] . ' hotel card(s), ' . $r['transport_cards'] . ' transport card(s), ' . $r['activity_lines'] . ' activity line(s), ' . $r['letters'] . ' letter(s), ' . $r['vouchers'] . ' payment voucher(s), ' . $r['trail_lines'] . ' trail line(s))');
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('The tour could not be removed; nothing was changed.');
        }
        $r['removed'] = true;
        $r['inquiry_removed'] = false;
        $r['inquiry_note'] = null;
        if ($withInquiry && $r['inquiry']) {
            try {
                $this->intake()->deletePermanently($r['intake_id'], (string) $r['inquiry']['inquiry_no']);
                $r['inquiry_removed'] = true;
            } catch (\Throwable $e) {
                $r['inquiry_note'] = $e->getMessage();
            }
        }
        return $r;
    }

    // =====================================================================
    //  Reading
    // =====================================================================

    public function get(int $tourId): ?array
    {
        $t = $this->db->query('SELECT * FROM tf_tour WHERE id = ?', [$tourId])->getRowArray();
        if (!$t) {
            return null;
        }
        $t['hotel_jobs']     = $this->db->query('SELECT * FROM tf_hotel_job WHERE tour_id = ? ORDER BY seq, id', [$tourId])->getResultArray();
        $t['transport_jobs'] = $this->db->query('SELECT * FROM tf_transport_job WHERE tour_id = ? ORDER BY seq, id', [$tourId])->getResultArray();
        foreach ($t['transport_jobs'] as &$j) {
            $this->hydrateJob($j);
        }
        unset($j);
        $t['service_jobs']   = $this->hasServiceJobs() ? $this->db->query('SELECT * FROM tf_service_job WHERE tour_id = ? ORDER BY seq, id', [$tourId])->getResultArray() : [];
        // tf-tickets-r1: the ticket numbers each line holds, for the Board, the record and the programme
        $byLine = $this->stock()->ticketsForTour($tourId);
        foreach ($t['service_jobs'] as &$sj) {
            $nos = $byLine[(int) $sj['id']] ?? [];
            $sj['tickets']      = $nos;
            $sj['ticket_text']  = $nos ? TicketStock::rangeText(array_column($nos, 'no')) : '';
            $sj['tickets_used'] = $nos ? count(array_filter($nos, static fn ($x) => $x['status'] === 'used')) : 0;
        }
        unset($sj);
        $t['events']  = $this->db->query('SELECT * FROM tf_tour_event WHERE tour_id = ? OR (tour_id IS NULL AND intake_id = ?) ORDER BY id DESC LIMIT 200', [$tourId, (int) $t['intake_id']])->getResultArray();
        $t['lanes']   = $this->lanes($t);
        $t['money']   = $this->money($t);
        $t['strip']   = $this->strip($t);
        $t['dates_line'] = TourDates::line($t['arrival_date'] ?? null, $t['departure_date'] ?? null);
        // schema 33: what is waiting for an amendment, with the reason each was asked for
        $t['edit_waiting'] = EditRequests::waiting($this->db, [(int) $t['intake_id']]);
        return $t;
    }

    /** The departure day's note for the desks (schema 32: a late check-out, lunch on the way), or null. */
    private function departureNote(int $intakeId): ?string
    {
        try {
            if (!$this->db->query("SHOW COLUMNS FROM tf_intake LIKE 'departure_note'")->getResultArray()) {
                return null;
            }
            $n = trim((string) ($this->db->query('SELECT departure_note FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray()['departure_note'] ?? ''));
            return $n !== '' ? $n : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * The card's kilometres three ways: Google's, the costed figure, and what was actually
     * driven — each over the legs that have it, and costed against actual over the legs
     * both know, which is the only fair comparison.
     *
     * @param array<int, array<string, mixed>> $legs
     */
    public static function actualsSummary(array $legs): array
    {
        $o = ['driven_legs' => 0, 'google_km' => 0.0, 'google_legs' => 0, 'quoted_km' => 0.0, 'quoted_legs' => 0, 'actual_km' => 0.0, 'actual_legs' => 0,
              'both_quoted_km' => 0.0, 'both_actual_km' => 0.0, 'both_legs' => 0, 'missing' => 0, 'has_columns' => false];
        foreach ($legs as $l) {
            if (!array_key_exists('actual_km', $l)) {
                return $o;
            }
            $o['has_columns'] = true;
            $driven = !in_array((string) $l['kind'], ['garage', 'idle'], true) && !((string) $l['kind'] === 'local' && strcasecmp(trim((string) $l['from_place']), trim((string) $l['to_place'])) === 0);
            if (!$driven) {
                continue;
            }
            $o['driven_legs']++;
            $g = $l['google_km'] !== null ? (float) $l['google_km'] : null;
            $q = $l['km'] !== null ? (float) $l['km'] : null;
            $a = $l['actual_km'] !== null ? (float) $l['actual_km'] : null;
            if ($g !== null) { $o['google_km'] += $g; $o['google_legs']++; }
            if ($q !== null) { $o['quoted_km'] += $q; $o['quoted_legs']++; }
            if ($a !== null) { $o['actual_km'] += $a; $o['actual_legs']++; } else { $o['missing']++; }
            if ($q !== null && $a !== null) { $o['both_quoted_km'] += $q; $o['both_actual_km'] += $a; $o['both_legs']++; }
        }
        foreach (['google_km', 'quoted_km', 'actual_km', 'both_quoted_km', 'both_actual_km'] as $k) {
            $o[$k] = round($o[$k], 1);
        }
        $o['difference_km'] = $o['both_legs'] > 0 ? round($o['both_quoted_km'] - $o['both_actual_km'], 1) : null;
        return $o;
    }

    private function hydrateJob(array &$j): void
    {
        $j['legs'] = $this->db->query('SELECT * FROM tf_transport_leg WHERE job_id = ? ORDER BY day_no, seq, id', [(int) $j['id']])->getResultArray();
        $j['crew'] = $this->db->query('SELECT * FROM tf_transport_crew WHERE job_id = ? ORDER BY id', [(int) $j['id']])->getResultArray();
        $j['docs'] = $this->db->query('SELECT * FROM tf_transport_doc WHERE job_id = ? ORDER BY phase, id', [(int) $j['id']])->getResultArray();
        if (!in_array((string) ($j['status'] ?? ''), ['pack_issued', 'running', 'returned', 'settled', 'cancelled'], true) && empty($j['archived_at'])) {
            // a card still before its pack takes the master's list: a card made before the list existed takes it whole, and
            // (tf-route-map-r1) a document added to the master list since joins every open card, so a new sheet such as the
            // route map is on the pack of the tours already on the desk and not only on tomorrow's
            $this->backfillDocs((int) $j['id']);
            $j['docs'] = $this->db->query('SELECT * FROM tf_transport_doc WHERE job_id = ? ORDER BY phase, id', [(int) $j['id']])->getResultArray();
        }
        $j['pack'] = $this->packSummary($j);
        $j['vouchers'] = [];
        $j['crew_dated'] = $this->hasCrewDates();
        if (!$j['crew_dated']) {
            return;
        }
        // schema 24: the days each person works, who replaced whom, and each person's payment voucher
        $j['vouchers'] = $this->crewVouchers((int) $j['id']);
        $live = [];
        foreach ($j['vouchers'] as $v) {
            if ((string) $v['state'] === 'issued') {
                $live[(int) $v['crew_id']] = $v;
            }
        }
        $names = [];
        foreach ($j['crew'] as $c) {
            $names[(int) $c['id']] = $c['person_name'] ?: null;
        }
        foreach ($j['crew'] as &$c) {
            // tf-crew-master-r1: who is paid — nickname, legal name, category (the bank only for the privilege, added by the desk)
            $c['payee'] = (!empty($c['person_id']) || !empty($c['supplier_acc'])) && in_array((string) $c['status'], ['engaged', 'replaced'], true)
                ? $this->crewPayee(!empty($c['person_id']) ? (int) $c['person_id'] : null, $c['supplier_acc'] ?? null, $this->bankVisible) : null;
            $c['days_worked']      = self::daysBetween($c['date_from'] ?? null, $c['date_to'] ?? null);
            $c['replaces_name']    = !empty($c['replaces']) ? ($names[(int) $c['replaces']] ?? null) : null;
            $c['replaced_by_name'] = !empty($c['replaced_by']) ? ($names[(int) $c['replaced_by']] ?? null) : null;
            $v = $live[(int) $c['id']] ?? null;
            $c['voucher'] = $v ? [
                'id' => (int) $v['id'], 'voucher_no' => $v['voucher_no'], 'days' => (int) $v['days'], 'rate' => (float) $v['rate'], 'rate_source' => $v['rate_source'],
                'amount' => (float) $v['amount'], 'currency' => $v['currency'], 'issued_at' => $v['issued_at'], 'issued_by' => $v['issued_by'],
                // the days on the row moved since the voucher was issued: it no longer says what was worked
                'stale' => (string) $v['date_from'] !== (string) $c['date_from'] || (string) $v['date_to'] !== (string) $c['date_to'],
            ] : null;
        }
        unset($c);
    }

    /** Days worked, both ends counted: 3 to 5 March is three days. Null while a date is missing; nought when the end is before the start. */
    public static function daysBetween(?string $from, ?string $to): ?int
    {
        if (!$from || !$to) {
            return null;
        }
        $a = strtotime($from);
        $b = strtotime($to);
        if ($a === false || $b === false) {
            return null;
        }
        return max(0, (int) round(($b - $a) / 86400) + 1);
    }

    /** Where each lane stands, in counts a screen can draw without money. */
    public function lanes(array $t): array
    {
        $h = ['total' => 0, 'to_book' => 0, 'requested' => 0, 'confirmed' => 0, 'voucher_issued' => 0, 'problem' => 0, 'cancelled' => 0, 'flags' => 0, 'archived' => 0];
        foreach ($t['hotel_jobs'] ?? [] as $j) {
            // an archived card is off the desk: it counts for nothing here
            if (!empty($j['archived_at'])) {
                $h['archived']++;
                continue;
            }
            $h['total']++;
            $h[$j['status']] = ($h[$j['status']] ?? 0) + 1;
            if ((int) $j['needs_hotel'] || (int) $j['needs_meal'] || (int) $j['needs_supplier']) {
                $h['flags']++;
            }
        }
        $h['booked'] = $h['confirmed'] + $h['voucher_issued'];
        $tr = ['total' => 0, 'status' => null, 'rung' => 0, 'of' => count(self::TRANSPORT_LADDER) - 1, 'amended' => 0, 'archived' => 0];
        foreach ($t['transport_jobs'] ?? [] as $j) {
            if (!empty($j['archived_at'])) {
                $tr['archived']++;
                continue;
            }
            $tr['total']++;
            $rung = array_search((string) $j['status'], self::TRANSPORT_LADDER, true);
            $rung = $rung === false ? 0 : (int) $rung;
            // the lane shows its slowest vehicle
            if ($tr['status'] === null || $rung < $tr['rung']) {
                $tr['status'] = (string) $j['status'];
                $tr['rung']   = $rung;
            }
            if ((int) $j['amended']) {
                $tr['amended']++;
            }
        }
        $q = ['status' => null, 'invoice' => 'later'];
        $it = $this->db->query('SELECT status FROM tf_intake WHERE id = ?', [(int) $t['intake_id']])->getRowArray();
        $q['status'] = $it['status'] ?? null;
        // activities and tickets: done is confirmed (with the supplier, or tickets from stock in hand), found not needed, or paid at the gate
        $a = ['total' => 0, 'to_book' => 0, 'requested' => 0, 'confirmed' => 0, 'not_needed' => 0, 'pay_at_gate' => 0, 'problem' => 0, 'cancelled' => 0, 'archived' => 0];
        foreach ($t['service_jobs'] ?? [] as $j) {
            if (!empty($j['archived_at'])) {
                $a['archived']++;
                continue;
            }
            $a['total']++;
            $a[$j['status']] = ($a[$j['status']] ?? 0) + 1;
        }
        $a['done'] = $a['confirmed'] + $a['not_needed'] + $a['pay_at_gate'];
        return ['hotel' => $h, 'transport' => $tr, 'quotation' => $q, 'service' => $a];
    }

    /**
     * The one status strip every screen shows for a tour — the Inquiries screen, both desks
     * and the Board — so a person can see where the whole tour stands without asking anyone:
     * the quotation and the version sold, hotels done of total, activities done of total, the
     * desk's "hotels & activities confirmed" mark, and vehicle, driver and guide each on its
     * own. Counts and states only; never another desk's money.
     */
    public function strip(array $t): array
    {
        $lanes = $t['lanes'] ?? $this->lanes($t);
        $jobs  = array_values(array_filter($t['transport_jobs'] ?? [], static fn ($j) => empty($j['archived_at'])));
        $ids   = array_map(static fn ($j) => (int) $j['id'], $jobs);
        $crew  = [];
        if ($ids) {
            $need = array_filter($jobs, static fn ($j) => !array_key_exists('crew', $j));
            if ($need) {
                foreach ($this->db->query('SELECT job_id, kind, status, person_name FROM tf_transport_crew WHERE job_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids)->getResultArray() as $c) {
                    $crew[(int) $c['job_id']][] = $c;
                }
            }
            foreach ($jobs as $j) {
                if (array_key_exists('crew', $j)) {
                    $crew[(int) $j['id']] = $j['crew'];
                }
            }
        }
        // tf-record-r1: beside the counts, who and what — the vehicle's registration (or type), the driver's and the
        // guide's names once engaged — so the Board and the inquiry can say "Driver allocated · Ruwan" instead of a count
        $vehicle = ['done' => 0, 'of' => count($jobs), 'names' => []];
        $driver  = ['done' => 0, 'of' => count($jobs), 'names' => []];
        $guide   = ['done' => 0, 'of' => 0, 'names' => []];
        foreach ($jobs as $j) {
            if (trim((string) ($j['vehicle_reg'] ?? '')) !== '' || !empty($j['vehicle_id'])) {
                $vehicle['done']++;
                // the registration; a master vehicle without one is set under its type, and the chip says so
                $reg = trim((string) ($j['vehicle_reg'] ?? ''));
                $vehicle['names'][] = $reg !== '' ? ($this->regIsType($j) ? $reg . ' (no registration on the master)' : $reg) : trim((string) ($j['vehicle_type'] ?? 'vehicle'));
            }
            $hasDriver = false;
            foreach ($crew[(int) $j['id']] ?? [] as $c) {
                if ((string) $c['status'] === 'replaced') {
                    // schema 24: a person replaced mid-tour is history on the card, not a slot to fill
                    continue;
                }
                $who = trim((string) ($c['person_name'] ?? ''));
                if ((string) $c['kind'] === 'driver') {
                    if ((string) $c['status'] === 'engaged') {
                        $hasDriver = true;
                        if ($who !== '') {
                            $driver['names'][] = $who;
                        }
                    }
                } elseif ((string) $c['kind'] === 'guide') {
                    $guide['of']++;
                    if ((string) $c['status'] === 'engaged') {
                        $guide['done']++;
                        if ($who !== '') {
                            $guide['names'][] = $who;
                        }
                    }
                }
            }
            if ($hasDriver) {
                $driver['done']++;
            }
        }
        foreach (['vehicle', 'driver', 'guide'] as $k) {
            $$k = array_merge($$k, ['names' => array_values(array_unique($$k['names']))]);
        }
        $state = static fn (array $x, string $none, string $done, string $todo): string => $x['of'] === 0 ? $none : ($x['done'] >= $x['of'] ? $done : $todo);
        $vehicle['state'] = $state($vehicle, 'no_vehicle_line', 'allocated', 'to_allocate');
        $driver['state']  = $state($driver, 'no_vehicle_line', 'allocated', 'to_allocate');
        $guide['state']   = $state($guide, 'not_required', 'allocated', 'to_allocate');
        $h = $lanes['hotel'];
        $a = $lanes['service'] ?? ['total' => 0, 'done' => 0, 'problem' => 0, 'cancelled' => 0];
        $marked = !empty($t['reservations_confirmed_at']);
        // a stay is done for the desk once its voucher is issued (management's rule); confirmed
        // without a voucher is on the way, and is shown as such
        $hDone   = (int) $h['voucher_issued'];
        $hReady  = $hDone + (int) $h['cancelled'] >= (int) $h['total'];
        $aReady  = (int) $a['done'] + (int) ($a['cancelled'] ?? 0) >= (int) $a['total'];
        $stage   = (string) ($t['stage'] ?? '');
        $over    = in_array($stage, ['completed', 'closed'], true);
        $tMarked = !empty($t['transport_complete_at']);
        $tReady  = count($jobs) > 0 && $vehicle['done'] >= $vehicle['of'] && $driver['done'] >= $driver['of'] && $guide['done'] >= $guide['of'];
        return [
            'tour_no'  => $t['tour_no'] ?? null, 'stage' => $stage ?: null, 'amending' => (int) ($t['amending'] ?? 0),
            'quotation' => ['status' => $lanes['quotation']['status'] ?? null, 'version' => isset($t['quote_version']) && $t['quote_version'] !== null ? (int) $t['quote_version'] : null],
            'hotels'   => ['done' => $hDone, 'confirmed' => (int) $h['confirmed'], 'booked' => (int) $h['booked'], 'of' => (int) $h['total'], 'problem' => (int) $h['problem'], 'flags' => (int) ($h['flags'] ?? 0), 'state' => $h['total'] === 0 ? 'none' : ($hReady ? 'done' : ($h['problem'] ? 'problem' : 'open'))],
            'activities' => ['done' => (int) $a['done'], 'of' => (int) $a['total'], 'problem' => (int) $a['problem'], 'state' => $a['total'] === 0 ? 'none' : ($aReady ? 'done' : ($a['problem'] ? 'problem' : 'open'))],
            'reservations_confirmed' => $marked, 'reservations_confirmed_at' => $t['reservations_confirmed_at'] ?? null, 'reservations_confirmed_by' => $t['reservations_confirmed_by'] ?? null,
            'reservations_ready' => !$marked && ($h['total'] > 0 || $a['total'] > 0) && $hReady && $aReady,
            'vehicle'  => $vehicle, 'driver' => $driver, 'guide' => $guide,
            'transport' => ['status' => $lanes['transport']['status'] ?? null, 'of' => (int) ($lanes['transport']['total'] ?? 0)],
            'transport_complete' => $tMarked, 'transport_complete_at' => $t['transport_complete_at'] ?? null, 'transport_complete_by' => $t['transport_complete_by'] ?? null,
            'transport_ready' => !$tMarked && $tReady,
            // each desk's one job on this tour: nothing for it, still pending, or complete (marked,
            // or the tour is over). The red counts on the bar count the tours pending here.
            'hotel_work'     => $h['total'] === 0 && $a['total'] === 0 ? 'none' : ($marked || $over ? 'complete' : 'pending'),
            'transport_work' => count($jobs) === 0 ? 'none' : ($tMarked || $over ? 'complete' : 'pending'),
        ];
    }

    /**
     * The tour as every desk needs to see it once it is confirmed — the programme day by
     * day with the hotel, meal plan and rooms each night, what happens that day (visits,
     * tickets, excursions) and how each stands on its desk; the passengers; the flights;
     * the agent's requests and their answers; the vehicle and the crew engaged. Read from
     * the one record (the confirmed inquiry and the tour's cards), never copied, so a
     * correction on one desk is what the next desk reads. Not a rupee or a dollar on it:
     * money stays with the quotation and the Board.
     */
    public function details(int $tourId): ?array
    {
        $t = $this->db->query('SELECT * FROM tf_tour WHERE id = ?', [$tourId])->getRowArray();
        if (!$t) {
            return null;
        }
        $iid = (int) $t['intake_id'];
        $it  = $this->db->query('SELECT inquiry_no, status, agent_name, agent_acc, agent_email, market_code, country_iso, tour_name, hotel_class, rooms_note, note, arrival_flight, departure_flight, confirmed_at, confirmed_by FROM tf_intake WHERE id = ?', [$iid])->getRowArray() ?: [];
        $out = [
            'tour_id' => $tourId, 'tour_no' => $t['tour_no'], 'stage' => $t['stage'], 'amending' => (int) $t['amending'],
            'inquiry_id' => $iid, 'inquiry_no' => $it['inquiry_no'] ?? null, 'quote_version' => isset($t['quote_version']) ? self::intOrNull($t['quote_version']) : null,
            'agent_name' => $t['agent_name'], 'agent_acc' => $t['agent_acc'], 'agent_email' => $it['agent_email'] ?? null, 'market_code' => $t['market_code'], 'country_iso' => $t['country_iso'],
            'tour_name' => $t['tour_name'], 'arrival_date' => $t['arrival_date'], 'departure_date' => $t['departure_date'], 'nights' => $t['nights'],
            'dates_line' => TourDates::line($t['arrival_date'], $t['departure_date']),
            'pax_total' => $t['pax_total'], 'pax_adults' => $t['pax_adults'], 'pax_children' => $t['pax_children'], 'rooms_note' => $t['rooms_note'], 'hotel_class' => $t['hotel_class'],
            'arrival_flight' => $t['arrival_flight'] ?: ($it['arrival_flight'] ?? null), 'departure_flight' => $t['departure_flight'] ?: ($it['departure_flight'] ?? null),
            'note' => $it['note'] ?? null, 'confirmed_at' => $t['confirmed_at'], 'confirmed_by' => $t['confirmed_by'],
            'handler_name' => $t['handler_name'] ?? null,
            'reservations_confirmed_at' => $t['reservations_confirmed_at'] ?? null, 'reservations_confirmed_by' => $t['reservations_confirmed_by'] ?? null,
        ];

        // the stays as the Hotel desk holds them, keyed by the first night they cover
        $stays = [];
        foreach ($this->db->query('SELECT id, day_from, day_to, hotel_name, area, check_in, check_out, nights, rooms, rooms_note, meal_plan, status, confirmation_ref, voucher_no, problem_note, needs_hotel, needs_meal FROM tf_hotel_job WHERE tour_id = ? AND archived_at IS NULL ORDER BY seq, id', [$tourId])->getResultArray() as $h) {
            for ($d = (int) $h['day_from']; $d <= max((int) $h['day_from'], (int) $h['day_to']); $d++) {
                $stays[$d] = $h;
            }
        }
        // activities and tickets as the desk holds them, by the quotation line they came from
        $lines = [];
        $deskLines = [];
        if ($this->hasServiceJobs()) {
            foreach ($this->db->query('SELECT id, source, source_id, kind, title, day_no, on_date, pax, pay_mode, status, confirmation_ref, problem_note, request_note FROM tf_service_job WHERE tour_id = ? AND archived_at IS NULL ORDER BY seq, id', [$tourId])->getResultArray() as $l) {
                if ((string) $l['source'] === 'desk') {
                    $deskLines[(int) ($l['day_no'] ?? 0)][] = $l;
                } else {
                    $lines[$l['source'] . ':' . (int) $l['source_id']] = $l;
                }
            }
        }
        $services = [];
        // a free visit from the master (schema 29) is told where it falls by its destination, as on the quotation
        $freeCols = (bool) $this->db->query("SHOW COLUMNS FROM tf_intake_service LIKE 'free_visit_id'")->getResultArray() && (bool) $this->db->query("SHOW TABLES LIKE 'tf_free_visit'")->getResultArray();
        $svcSql = $freeCols
            ? "SELECT s.id, s.day_no, s.kind, s.title, s.fee_id, s.pay_mode, s.match_state, s.special_note, s.free_visit_id, f.destination AS free_destination, f.kind AS free_kind FROM tf_intake_service s LEFT JOIN tf_free_visit f ON f.id = s.free_visit_id WHERE s.intake_id = ? AND s.kind NOT IN ('guide','transport') ORDER BY s.day_no, s.sort_order, s.id"
            : "SELECT id, day_no, kind, title, fee_id, pay_mode, match_state, special_note FROM tf_intake_service WHERE intake_id = ? AND kind NOT IN ('guide','transport') ORDER BY day_no, sort_order, id";
        foreach ($this->db->query($svcSql, [$iid])->getResultArray() as $sv) {
            $sv['desk'] = $lines['service:' . (int) $sv['id']] ?? null;
            $services[(int) $sv['day_no']][] = $sv;
        }
        $excursions = [];
        foreach ($this->db->query('SELECT id, day_no, name, qty, includes_transport, description, note FROM tf_intake_excursion WHERE intake_id = ? ORDER BY day_no, sort_order, id', [$iid])->getResultArray() as $ex) {
            $ex['desk'] = $lines['excursion:' . (int) $ex['id']] ?? null;
            $excursions[(int) $ex['day_no']][] = $ex;
        }
        // the programme, day by day — the night's hotel, meal plan and rooms as the quotation
        // says, the stay's standing as the Hotel desk says, and what happens that day
        $days = [];
        $hasOwnText = (bool) $this->db->query("SHOW COLUMNS FROM tf_intake_day LIKE 'client_text'")->getResultArray();
        $dayRows = $this->db->query('SELECT day_no, day_date, area, hotel_name_raw, meal_plan, rooms, nights, narrative, special_note' . ($hasOwnText ? ', client_text' : '') . ' FROM tf_intake_day WHERE intake_id = ? ORDER BY day_no, id', [$iid])->getResultArray();
        // the itinerary as the client quotation says it (schema 28): the same words on the driver's copy
        $wording = ['days' => [], 'departure' => null];
        try {
            $wording = (new ItineraryWriter($this->db, new ServiceLookup($this->db)))->write([
                'arrival_flight' => $out['arrival_flight'], 'departure_flight' => $out['departure_flight'], 'nights' => (int) ($t['nights'] ?? 0),
                'days' => array_map(static fn ($d) => $d + ['services' => $services[(int) $d['day_no']] ?? [], 'excursions' => $excursions[(int) $d['day_no']] ?? []], $dayRows),
                'services' => array_merge(...array_values($services ?: [[]])), 'excursions' => array_merge(...array_values($excursions ?: [[]])),
            ]);
        } catch (\Throwable $e) {
            // the programme stands without the wording
        }
        foreach ($dayRows as $d) {
            $n = (int) $d['day_no'];
            $date = $d['day_date'] ?: ($t['arrival_date'] && $n >= 1 ? date('Y-m-d', strtotime($t['arrival_date'] . ' +' . ($n - 1) . ' days')) : null);
            $stay = $stays[$n] ?? null;
            $days[] = [
                'day_no' => $n, 'date' => $date, 'area' => $d['area'],
                'hotel' => $stay['hotel_name'] ?? $d['hotel_name_raw'], 'meal_plan' => $stay['meal_plan'] ?? $d['meal_plan'], 'rooms' => $stay['rooms'] ?? $d['rooms'], 'rooms_note' => $stay['rooms_note'] ?? null,
                'stay' => $stay ? ['id' => (int) $stay['id'], 'status' => $stay['status'], 'confirmation_ref' => $stay['confirmation_ref'], 'voucher_no' => $stay['voucher_no'], 'problem_note' => $stay['problem_note'], 'check_in' => $stay['check_in'], 'check_out' => $stay['check_out'], 'first_night' => (int) $stay['day_from'] === $n, 'needs_hotel' => (int) $stay['needs_hotel'], 'needs_meal' => (int) $stay['needs_meal']] : null,
                'narrative' => $d['narrative'], 'special_note' => $d['special_note'],
                'wording' => $wording['days'][$n]['text'] ?? null,
                'services' => array_map(static fn ($sv) => ['id' => (int) $sv['id'], 'kind' => $sv['kind'], 'title' => $sv['title'], 'pay_mode' => ($sv['match_state'] ?? '') === 'no_charge' ? 'no_charge' : $sv['pay_mode'], 'note' => $sv['special_note'],
                    'desk' => $sv['desk'] ? ['id' => (int) $sv['desk']['id'], 'status' => $sv['desk']['status'], 'ref' => $sv['desk']['confirmation_ref'], 'note' => $sv['desk']['problem_note'] ?: $sv['desk']['request_note']] : null], $services[$n] ?? []),
                'excursions' => array_map(static fn ($ex) => ['id' => (int) $ex['id'], 'name' => $ex['name'], 'qty' => $ex['qty'], 'includes_transport' => (int) $ex['includes_transport'], 'description' => $ex['description'], 'note' => $ex['note'],
                    'desk' => $ex['desk'] ? ['id' => (int) $ex['desk']['id'], 'status' => $ex['desk']['status'], 'ref' => $ex['desk']['confirmation_ref'], 'note' => $ex['desk']['problem_note'] ?: $ex['desk']['request_note']] : null], $excursions[$n] ?? []),
                'desk_lines' => array_map(static fn ($l) => ['id' => (int) $l['id'], 'kind' => $l['kind'], 'title' => $l['title'], 'status' => $l['status'], 'ref' => $l['confirmation_ref']], $deskLines[$n] ?? []),
            ];
        }
        $out['days'] = $days;
        // the departure sentence for the programme's last row, unless a day row is already the departure day
        $lastNo = $days ? (int) end($days)['day_no'] : 0;
        $out['departure_wording'] = isset($wording['departure']) && $lastNo <= (int) ($t['nights'] ?? $lastNo) ? $wording['departure']['text'] : null;
        // schema 32: the departure day as the dates give it, and what is on it (a late check-out, lunch on
        // the way) with how each stands on its desk — unless a day row is already the departure day
        $stayN = TourDates::nights($t['arrival_date'], $t['departure_date']);
        $depNo = $stayN !== null ? $stayN + 1 : $lastNo + 1;
        $rowOn = in_array($depNo, array_map(static fn ($x) => (int) $x['day_no'], $days), true);
        $out['departure_day_no']     = $depNo;
        $out['departure_services']   = $rowOn ? [] : array_map(static fn ($sv) => ['id' => (int) $sv['id'], 'kind' => $sv['kind'], 'title' => $sv['title'], 'pay_mode' => ($sv['match_state'] ?? '') === 'no_charge' ? 'no_charge' : $sv['pay_mode'], 'note' => $sv['special_note'],
            'desk' => $sv['desk'] ? ['id' => (int) $sv['desk']['id'], 'status' => $sv['desk']['status'], 'ref' => $sv['desk']['confirmation_ref'], 'note' => $sv['desk']['problem_note'] ?: $sv['desk']['request_note']] : null], $services[$depNo] ?? []);
        $out['departure_excursions'] = $rowOn ? [] : array_map(static fn ($ex) => ['id' => (int) $ex['id'], 'name' => $ex['name'], 'qty' => $ex['qty'], 'includes_transport' => (int) $ex['includes_transport'], 'description' => $ex['description'], 'note' => $ex['note'],
            'desk' => $ex['desk'] ? ['id' => (int) $ex['desk']['id'], 'status' => $ex['desk']['status'], 'ref' => $ex['desk']['confirmation_ref'], 'note' => $ex['desk']['problem_note'] ?: $ex['desk']['request_note']] : null], $excursions[$depNo] ?? []);
        $out['departure_desk_lines'] = $rowOn ? [] : array_map(static fn ($l) => ['id' => (int) $l['id'], 'kind' => $l['kind'], 'title' => $l['title'], 'status' => $l['status'], 'ref' => $l['confirmation_ref']], $deskLines[$depNo] ?? []);
        $out['departure_note']       = $this->departureNote($iid);
        // lines with no day of their own (day 0): on the tour as a whole
        $out['tour_services']   = array_map(static fn ($sv) => ['title' => $sv['title'], 'kind' => $sv['kind'], 'pay_mode' => ($sv['match_state'] ?? '') === 'no_charge' ? 'no_charge' : $sv['pay_mode'], 'desk' => $sv['desk'] ? ['status' => $sv['desk']['status'], 'ref' => $sv['desk']['confirmation_ref']] : null], $services[0] ?? []);
        $out['tour_desk_lines'] = array_map(static fn ($l) => ['title' => $l['title'], 'kind' => $l['kind'], 'status' => $l['status'], 'ref' => $l['confirmation_ref']], $deskLines[0] ?? []);

        $out['passengers'] = $this->db->query('SELECT id, full_name, room_type, note FROM tf_intake_pax WHERE intake_id = ? ORDER BY sort_order, id', [$iid])->getResultArray();
        $out['requests']   = $this->db->query("SELECT id, kind, title, status, answer, answered_by, answered_at FROM tf_intake_request WHERE intake_id = ? AND status <> 'dropped' ORDER BY FIELD(status, 'open', 'answered', 'done'), sort_order, id", [$iid])->getResultArray();
        // vehicles and crew as the Transport desk holds them: who is engaged, never what they cost
        $out['vehicles'] = [];
        foreach ($this->db->query('SELECT id, vehicle_type, qty, days, vehicle_reg, vehicle_note, status FROM tf_transport_job WHERE tour_id = ? AND archived_at IS NULL ORDER BY seq, id', [$tourId])->getResultArray() as $v) {
            $v['crew'] = $this->db->query('SELECT id, role_name, kind, language, days, person_name, status' . ($this->hasCrewDates() ? ', date_from, date_to' : '') . ' FROM tf_transport_crew WHERE job_id = ? ORDER BY id', [(int) $v['id']])->getResultArray();
            $out['vehicles'][] = $v;
        }
        $out['strip'] = $this->stripFor($tourId);
        return $out;
    }

    /** The strip for a tour by id, for a screen that has only the id (a desk card, a queue row). */
    public function stripFor(int $tourId): ?array
    {
        $t = $this->db->query('SELECT * FROM tf_tour WHERE id = ?', [$tourId])->getRowArray();
        if (!$t) {
            return null;
        }
        $t['hotel_jobs']     = $this->db->query('SELECT id, status, needs_hotel, needs_meal, needs_supplier, archived_at FROM tf_hotel_job WHERE tour_id = ?', [$tourId])->getResultArray();
        $t['transport_jobs'] = $this->db->query('SELECT id, status, amended, vehicle_id, vehicle_reg, vehicle_type, archived_at FROM tf_transport_job WHERE tour_id = ?', [$tourId])->getResultArray();
        $t['service_jobs']   = $this->hasServiceJobs() ? $this->db->query('SELECT id, status, archived_at FROM tf_service_job WHERE tour_id = ?', [$tourId])->getResultArray() : [];
        return $this->strip($t);
    }

    /** Money is for the Board. The transport cost is what the quotation carried; hotels wait for the cost engine. */
    private function money(array $t): array
    {
        $transport = null;
        foreach ($t['transport_jobs'] ?? [] as $j) {
            if (!empty($j['archived_at'])) {
                continue;
            }
            if ($j['km_rate'] !== null && $j['km_quoted'] !== null) {
                $transport = ($transport ?? 0) + ((float) $j['km_quoted'] + (float) ($j['extra_km_quoted'] ?? 0)) * (float) $j['km_rate'] * max(1, (int) $j['qty']);
            }
        }
        return ['transport_lkr' => $transport === null ? null : round($transport, 2), 'hotels' => null, 'note' => 'Hotel cost follows the cost engine (next phase).'];
    }

    /**
     * The filters every desk list shares — the handling agent, the arrival window and the
     * free text — as SQL on the tour row (alias t). The text also matches the handler's name,
     * so typing a colleague's name finds their tours from any screen.
     *
     * @return array{0:string, 1:array}  the clause (starting " AND ...") and its binds
     */
    private function tourFilters(array $f, array $textCols): array
    {
        $sql   = '';
        $binds = [];
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $cols = array_merge($textCols, ['t.handler_name']);
            $sql .= ' AND (' . implode(' OR ', array_map(static fn ($c) => "{$c} LIKE ?", $cols)) . ')';
            foreach ($cols as $c) {
                $binds[] = "%{$q}%";
            }
        }
        [$hw, $hb] = IntakeService::handlerWhere($f['handler'] ?? null, 't.', $this->db);
        if ($hw !== null) {
            $sql .= ' AND ' . $hw;
            array_push($binds, ...$hb);
        }
        if (!empty($f['from'])) {
            $sql .= ' AND t.arrival_date >= ?';
            $binds[] = (string) $f['from'];
        }
        if (!empty($f['to'])) {
            $sql .= ' AND t.arrival_date <= ?';
            $binds[] = (string) $f['to'];
        }
        return [$sql, $binds];
    }

    /** The Board: every tour, nearest arrival first. */
    public function board(array $f = []): array
    {
        $which = (string) ($f['which'] ?? 'live');
        $where = "WHERE t.stage IN ('confirmed','operating')";
        if ($which === 'completed') {
            $where = "WHERE t.stage IN ('completed','closed')";
        } elseif ($which === 'cancelled') {
            $where = "WHERE t.stage = 'cancelled'";
        } elseif ($which === 'all' || $which === 'deleted') {
            $where = 'WHERE 1 = 1';
        }
        // a tour deleted by Admin is out of every list but its own (which = deleted)
        $where .= $which === 'deleted' ? ($this->hasTourArchive() ? ' AND t.archived_at IS NOT NULL' : ' AND 1 = 0') : $this->liveTourSql();
        [$fs, $binds] = $this->tourFilters($f, ['t.tour_no', 't.agent_name', 't.tour_name']);
        $where .= $fs;
        $rows = $this->db->query("SELECT t.* FROM tf_tour t {$where} ORDER BY t.arrival_date IS NULL, t.arrival_date, t.id LIMIT 300", $binds)->getResultArray();
        $today = date('Y-m-d');
        $soon  = date('Y-m-d', strtotime('+7 days'));
        foreach ($rows as &$t) {
            $t['hotel_jobs']     = $this->db->query('SELECT id, status, needs_hotel, needs_meal, needs_supplier, amended, check_in, archived_at FROM tf_hotel_job WHERE tour_id = ? ORDER BY seq', [(int) $t['id']])->getResultArray();
            $t['transport_jobs'] = $this->db->query('SELECT id, status, amended, qty, km_quoted, extra_km_quoted, km_rate, vehicle_id, vehicle_reg, vehicle_type, archived_at FROM tf_transport_job WHERE tour_id = ? ORDER BY seq', [(int) $t['id']])->getResultArray();
            $t['service_jobs']   = $this->hasServiceJobs() ? $this->db->query('SELECT id, status, archived_at FROM tf_service_job WHERE tour_id = ?', [(int) $t['id']])->getResultArray() : [];
            $t['lanes'] = $this->lanes($t);
            $t['money'] = $this->money($t);
            $t['strip'] = $this->strip($t);
            $t['stage'] = $this->settleStage($t, $today);
            $late = [];
            if ($t['arrival_date'] && $t['arrival_date'] <= $soon && in_array($t['stage'], ['confirmed', 'operating'], true)) {
                if ($t['lanes']['hotel']['total'] > $t['lanes']['hotel']['booked'] + $t['lanes']['hotel']['cancelled']) {
                    $late[] = 'hotels not all confirmed';
                }
                if ($t['lanes']['transport']['total'] > 0 && $t['lanes']['transport']['rung'] < 3) {
                    $late[] = 'driver\'s pack not issued';
                }
                $sv = $t['lanes']['service'] ?? null;
                if ($sv && $sv['total'] > $sv['done'] + $sv['cancelled']) {
                    $late[] = 'activities or tickets not all confirmed';
                }
            }
            $t['late'] = $late;
            $t['problems'] = $t['lanes']['hotel']['problem'] + (int) ($t['lanes']['service']['problem'] ?? 0);
            $t['flags']    = $t['lanes']['hotel']['flags'];
            $t['days_to_arrival'] = $t['arrival_date'] ? (int) floor((strtotime($t['arrival_date']) - strtotime($today)) / 86400) : null;
            unset($t['hotel_jobs'], $t['transport_jobs'], $t['service_jobs']);
        }
        unset($t);
        // schema 33: Edit Requests saved on a locked tour, waiting for Management to open it with Amend
        $waiting = [];
        foreach (EditRequests::waiting($this->db, array_map(static fn ($t) => (int) $t['intake_id'], $rows)) as $w) {
            $waiting[(int) $w['intake_id']][] = $w;
        }
        foreach ($rows as &$t) {
            $t['edit_waiting'] = $waiting[(int) $t['intake_id']] ?? [];
        }
        unset($t);
        if ($which === 'problems') {
            $rows = array_values(array_filter($rows, static fn ($t) => $t['problems'] > 0 || $t['late']));
        } elseif ($which === 'arriving') {
            $rows = array_values(array_filter($rows, static fn ($t) => $t['days_to_arrival'] !== null && $t['days_to_arrival'] >= 0 && $t['days_to_arrival'] <= 7));
        }
        return $rows;
    }

    /**
     * confirmed → operating the moment any card moves; → completed once the party has
     * left and both lanes are done. Written back so the Board's counts are cheap to read.
     */
    private function settleStage(array $t, string $today): string
    {
        $stage = (string) $t['stage'];
        if (in_array($stage, ['cancelled', 'closed', 'completed'], true)) {
            return $stage;
        }
        $moved = $t['lanes']['hotel']['total'] > $t['lanes']['hotel']['to_book'] || $t['lanes']['transport']['rung'] > 0;
        $new   = $stage;
        if ($stage === 'confirmed' && $moved) {
            $new = 'operating';
        }
        $hotelsDone = $t['lanes']['hotel']['total'] === $t['lanes']['hotel']['voucher_issued'] + $t['lanes']['hotel']['cancelled'];
        $transDone  = $t['lanes']['transport']['total'] === 0 || in_array($t['lanes']['transport']['status'], ['returned', 'settled'], true);
        $sv = $t['lanes']['service'] ?? null;
        $actsDone = $sv === null || $sv['total'] === $sv['done'] + $sv['cancelled'];
        if ($t['departure_date'] && $t['departure_date'] < $today && $hotelsDone && $transDone && $actsDone) {
            $new = 'completed';
        }
        if ($new !== $stage) {
            $this->db->query('UPDATE tf_tour SET stage = ?, updated_at = ? WHERE id = ?', [$new, $this->now(), (int) $t['id']]);
            $this->event((int) $t['intake_id'], (int) $t['id'], 'tour', null, 'stage', $stage, $new, null);
        }
        return $new;
    }

    /** The read-only strip on a confirmed inquiry: counts, never money from another desk. */
    public function progressFor(int $intakeId): ?array
    {
        $t = $this->tourForIntake($intakeId);
        if (!$t) {
            return null;
        }
        $full = $this->get((int) $t['id']);
        return [
            'tour_id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'stage' => $full['stage'], 'amending' => (int) $t['amending'],
            'confirmed_at' => $t['confirmed_at'], 'confirmed_by' => $t['confirmed_by'],
            'quote_id' => isset($t['quote_id']) ? self::intOrNull($t['quote_id']) : null, 'quote_version' => isset($t['quote_version']) ? self::intOrNull($t['quote_version']) : null,
            'hotel' => $full['lanes']['hotel'], 'transport' => $full['lanes']['transport'], 'service' => $full['lanes']['service'] ?? null,
            'strip' => $full['strip'],
            'handler_id' => $t['handler_id'] ?? null, 'handler_name' => $t['handler_name'] ?? null,
            'hotel_cards' => array_values(array_map(static fn ($j) => ['hotel_name' => $j['hotel_name'], 'area' => $j['area'], 'check_in' => $j['check_in'], 'nights' => $j['nights'], 'status' => $j['status'], 'problem_note' => $j['problem_note'], 'needs_hotel' => (int) $j['needs_hotel'], 'needs_meal' => (int) $j['needs_meal']],
                array_filter($full['hotel_jobs'], static fn ($j) => empty($j['archived_at'])))),
            'service_cards' => array_values(array_map(static fn ($j) => ['title' => $j['title'], 'kind' => $j['kind'], 'day_no' => $j['day_no'], 'on_date' => $j['on_date'], 'status' => $j['status'], 'problem_note' => $j['problem_note']],
                array_filter($full['service_jobs'] ?? [], static fn ($j) => empty($j['archived_at'])))),
        ];
    }

    // =====================================================================
    //  The desks' lists: one row per confirmed tour (schema 21)
    // =====================================================================

    /**
     * A desk's list, one row per confirmed tour — the tour's one job on that desk — with
     * where the whole tour stands and whether this desk's work on it is still pending,
     * complete, or nothing at all. `which` is pending (the default), completed, cancelled
     * or all; the other filters are the ones every list shares. Nearest arrival first.
     *
     * @param string $desk  'hotel' | 'transport'
     */
    public function deskTours(string $desk, array $f = []): array
    {
        $which = (string) ($f['which'] ?? 'pending');
        $where = $which === 'cancelled' ? "WHERE t.stage = 'cancelled'" : ($which === 'all' ? 'WHERE 1 = 1' : "WHERE t.stage <> 'cancelled'");
        $where .= $this->liveTourSql();   // a tour deleted by Admin is on no desk
        [$fs, $binds] = $this->tourFilters($f, ['t.tour_no', 't.agent_name', 't.tour_name']);
        $where .= $fs;
        $rows = $this->db->query(
            "SELECT t.*, i.inquiry_no FROM tf_tour t LEFT JOIN tf_intake i ON i.id = t.intake_id {$where}
             ORDER BY t.arrival_date IS NULL, t.arrival_date, t.id LIMIT 300",
            $binds
        )->getResultArray();
        $key = $desk === 'transport' ? 'transport_work' : 'hotel_work';
        $out = [];
        foreach ($rows as $t) {
            $t['hotel_jobs']     = $this->db->query('SELECT id, status, needs_hotel, needs_meal, needs_supplier, amended, check_in, hotel_name, archived_at FROM tf_hotel_job WHERE tour_id = ? ORDER BY seq', [(int) $t['id']])->getResultArray();
            $t['transport_jobs'] = $this->db->query('SELECT id, status, amended, qty, vehicle_type, vehicle_id, vehicle_reg, archived_at FROM tf_transport_job WHERE tour_id = ? ORDER BY seq', [(int) $t['id']])->getResultArray();
            $t['service_jobs']   = $this->hasServiceJobs() ? $this->db->query('SELECT id, status, archived_at FROM tf_service_job WHERE tour_id = ?', [(int) $t['id']])->getResultArray() : [];
            $t['lanes'] = $this->lanes($t);
            $strip = $this->strip($t);
            $work  = $strip[$key];
            if ($which === 'pending' && $work !== 'pending') {
                continue;
            }
            if ($which === 'completed' && $work !== 'complete') {
                continue;
            }
            $amended = 0;
            foreach (array_merge($t['hotel_jobs'], $t['transport_jobs']) as $j) {
                $amended += (int) ($j['amended'] ?? 0);
            }
            $out[] = [
                'id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'inquiry_no' => $t['inquiry_no'] ?? null, 'intake_id' => (int) $t['intake_id'],
                'stage' => $t['stage'], 'amending' => (int) $t['amending'],
                'agent_name' => $t['agent_name'], 'agent_acc' => $t['agent_acc'], 'tour_name' => $t['tour_name'], 'market_code' => $t['market_code'],
                'arrival_date' => $t['arrival_date'], 'departure_date' => $t['departure_date'], 'nights' => $t['nights'] !== null ? (int) $t['nights'] : null,
                'dates_line' => TourDates::line($t['arrival_date'], $t['departure_date']),
                'pax_total' => $t['pax_total'] !== null ? (int) $t['pax_total'] : null, 'hotel_class' => $t['hotel_class'],
                'handler_id' => $t['handler_id'] ?? null, 'handler_name' => $t['handler_name'] ?? null,
                'confirmed_at' => $t['confirmed_at'], 'confirmed_by' => $t['confirmed_by'] ?? null, 'quote_version' => isset($t['quote_version']) && $t['quote_version'] !== null ? (int) $t['quote_version'] : null,
                // a cancelled tour carries the reason, who and when to every desk
                'cancelled_at' => $t['cancelled_at'] ?? null, 'cancelled_by' => $t['cancelled_by'] ?? null, 'cancel_why' => $t['cancel_why'] ?? null,
                // tf-workflow-r1: the final confirmation to the agent travels with the line, so a desk
                // shows "Confirmed" rather than "reservations pending" once the handler has sent it
                'final_confirmed_at' => $t['final_confirmed_at'] ?? null, 'final_confirmed_by' => $t['final_confirmed_by'] ?? null,
                'work' => $work,
                'work_at' => $desk === 'transport' ? ($t['transport_complete_at'] ?? null) : ($t['reservations_confirmed_at'] ?? null),
                'work_by' => $desk === 'transport' ? ($t['transport_complete_by'] ?? null) : ($t['reservations_confirmed_by'] ?? null),
                'problems' => (int) $t['lanes']['hotel']['problem'] + (int) ($t['lanes']['service']['problem'] ?? 0),
                'amended' => $amended,
                'vehicles' => (int) $t['lanes']['transport']['total'],
                'strip' => $strip,
            ];
        }
        return $out;
    }

    /** How many tours sit under each chip of a desk's list, from the same rows the list shows. */
    public function deskCounts(string $desk): array
    {
        $all = $this->deskTours($desk, ['which' => 'all']);
        $c = ['pending' => 0, 'completed' => 0, 'cancelled' => 0, 'all' => count($all), 'none' => 0];
        foreach ($all as $t) {
            if ($t['stage'] === 'cancelled') {
                $c['cancelled']++;
            } elseif ($t['work'] === 'pending') {
                $c['pending']++;
            } elseif ($t['work'] === 'complete') {
                $c['completed']++;
            } else {
                $c['none']++;
            }
        }
        return $c;
    }

    // =====================================================================
    //  Hotel Desk
    // =====================================================================

    public function hotelQueue(array $f = []): array
    {
        $status = (string) ($f['status'] ?? 'open');
        // a tour opened by id shows its cards whatever its stage: a cancelled tour keeps its history
        $where  = !empty($f['tour_id']) ? 'WHERE 1 = 1' : "WHERE t.stage <> 'cancelled'" . $this->liveTourSql();
        $binds  = [];
        if ($status === 'archived') {
            $where = 'WHERE j.archived_at IS NOT NULL';
        } else {
            $where .= ' AND j.archived_at IS NULL';
            if ($status === 'open') {
                $where .= " AND j.status IN ('to_book','requested','confirmed','problem')";
            } elseif ($status !== 'all') {
                $where .= ' AND j.status = ?';
                $binds[] = $status;
            }
        }
        [$fs, $fb] = $this->tourFilters($f, ['t.tour_no', 't.agent_name', 't.tour_name', 'j.hotel_name']);
        $where .= $fs;
        array_push($binds, ...$fb);
        if (!empty($f['tour_id'])) {
            $where .= ' AND j.tour_id = ?';
            $binds[] = (int) $f['tour_id'];
        }
        return $this->db->query(
            "SELECT j.*, t.tour_no, t.agent_name, t.tour_name, t.arrival_date, t.departure_date, t.pax_total AS tour_pax, t.stage AS tour_stage, t.hotel_class, t.handler_id, t.handler_name, t.intake_id
             FROM tf_hotel_job j JOIN tf_tour t ON t.id = j.tour_id {$where}
             ORDER BY t.arrival_date IS NULL, t.arrival_date, t.id, j.seq LIMIT 500",
            $binds
        )->getResultArray();
    }

    public function hotelCounts(): array
    {
        $rows = $this->db->query("SELECT j.status, COUNT(*) n FROM tf_hotel_job j JOIN tf_tour t ON t.id = j.tour_id WHERE t.stage <> 'cancelled' AND j.archived_at IS NULL GROUP BY j.status")->getResultArray();
        $out = array_fill_keys(self::HOTEL_STATUSES, 0);
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        $out['archived'] = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_hotel_job WHERE archived_at IS NOT NULL')->getRowArray()['n'] ?? 0);
        return $out;
    }

    public function hotelJob(int $jobId): array
    {
        $j = $this->db->query(
            'SELECT j.*, t.tour_no, t.agent_name, t.tour_name, t.arrival_date, t.departure_date, t.pax_total AS tour_pax, t.stage AS tour_stage, t.intake_id, t.hotel_class, t.rooms_note AS tour_rooms_note, t.handler_id, t.handler_name
             FROM tf_hotel_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.id = ?',
            [$jobId]
        )->getRowArray();
        if (!$j) {
            throw new \RuntimeException('Hotel card not found.');
        }
        $j['archived'] = !empty($j['archived_at']);
        $j['events'] = $this->db->query("SELECT * FROM tf_tour_event WHERE lane = 'hotel' AND job_id = ? ORDER BY id DESC LIMIT 50", [$jobId])->getResultArray();
        try {
            $j['activity'] = $this->db->query("SELECT id, action, summary, changes_json, by_user, by_name, `at` FROM tf_activity WHERE entity = 'hotel_job' AND entity_id = ? ORDER BY id DESC LIMIT 50", [$jobId])->getResultArray();
            foreach ($j['activity'] as &$a) {
                $a['changes'] = $a['changes_json'] ? (json_decode((string) $a['changes_json'], true) ?: []) : [];
                $a['by'] = $a['by_name'] ?: $a['by_user'];
                unset($a['changes_json']);
            }
            unset($a);
        } catch (\Throwable $e) {
            $j['activity'] = [];
        }
        $j['messages'] = $this->hotelMessages($jobId);
        return $j;
    }

    // ---- what went to the hotel and what it answered (schema 21) ----

    /** The letters sent and the replies recorded on a stay, newest first; the copy kept is the copy sent. */
    public function hotelMessages(int $jobId, int $limit = 50): array
    {
        if (!$this->hasDeskJobs()) {
            return [];
        }
        try {
            return $this->db->query('SELECT id, kind, channel, to_addr, cc_addr, subject, body, state, error, `by`, by_name, `at` FROM tf_hotel_message WHERE job_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit), [$jobId])->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Keep an untouched copy of what went to the hotel — the request, the voucher — or of
     * what it answered, with who and when. A failed send is kept too, with the error, so
     * nobody wonders later whether it went.
     */
    public function recordHotelMessage(int $jobId, string $kind, string $channel, ?string $to, ?string $cc, ?string $subject, ?string $body, string $state = 'sent', ?string $error = null, ?string $byName = null): array
    {
        if (!$this->hasDeskJobs()) {
            throw new \RuntimeException('Not installed on this site yet.');
        }
        $j = $this->hotelJob($jobId);
        if (!in_array($kind, ['request', 'voucher', 'reply'], true) || !in_array($channel, ['email', 'other'], true) || !in_array($state, ['sent', 'failed', 'recorded'], true)) {
            throw new \RuntimeException('Unknown message kind.');
        }
        $this->db->query(
            'INSERT INTO tf_hotel_message (job_id, tour_id, kind, channel, to_addr, cc_addr, subject, body, state, error, `by`, by_name, `at`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$jobId, (int) $j['tour_id'], $kind, $channel, self::cut($to, 400), self::cut($cc, 400), self::cut($subject, 255), $body, $state, self::cut($error, 400), $this->user, self::cut($byName, 190), $this->now()]
        );
        $id = (int) $this->db->insertID();
        try {   // tf-comms-r1: hotel letters and replies join the Communications & History record
            (new Comms($this->db, $this->user))->captureHotelMessage($id);
        } catch (\Throwable $e) {
            log_message('error', 'Communications capture (hotel message) failed: ' . $e->getMessage());
        }
        return $this->db->query('SELECT * FROM tf_hotel_message WHERE id = ?', [$id])->getRowArray();
    }

    /** The hotel's reply, in its words, kept beside the letters — the card itself does not move. */
    public function hotelReply(int $jobId, ?string $text, ?string $byName = null): array
    {
        $text = self::cut($text, 4000);
        if ($text === null) {
            throw new \RuntimeException('Type what the hotel said.');
        }
        $j = $this->hotelJob($jobId);
        if ($j['archived']) {
            throw new \RuntimeException('This card is archived. Management can restore it from the ⋯ Manage menu.');
        }
        $this->db->transStart();
        $this->recordHotelMessage($jobId, 'reply', 'other', null, null, null, $text, 'recorded', null, $byName);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'hotel', $jobId, 'reply', (string) $j['status'], (string) $j['status'], mb_substr($text, 0, 400));
        $this->db->transComplete();
        return $this->hotelJob($jobId);
    }

    /**
     * What the booking request, and later the voucher, says — read from the one record:
     * the stay as the Hotel Desk holds it, the tour, the hotel and how to reach it (its
     * supplier record, never typed here), the party, the company's own lines (settings)
     * and the person sending. No rate: a group holding Hotel Rates may add the published
     * one from the card. `kind` is request or voucher.
     */
    public function hotelDocument(int $jobId, string $kind = 'request', array $sender = []): array
    {
        $j = $this->hotelJob($jobId);
        $kind = $kind === 'voucher' ? 'voucher' : 'request';
        if ($kind === 'voucher' && !in_array((string) $j['status'], ['confirmed', 'voucher_issued'], true)) {
            throw new \RuntimeException('A voucher is prepared once the hotel has confirmed the stay.');
        }
        $it = $this->db->query('SELECT id, inquiry_no, agent_email, market_code, country_iso, pax_adults, pax_children FROM tf_intake WHERE id = ?', [(int) $j['intake_id']])->getRowArray() ?: [];
        // the hotel's own contact lines: from its supplier record, by the code the stay carries
        // (or the panel hotel's primary supplier); nothing is invented when there is none
        $acc = trim((string) ($j['hotel_acc'] ?? ''));
        if ($acc === '' && !empty($j['hotel_id'])) {
            try {
                $acc = trim((string) ($this->db->query('SELECT primary_sup_acc_no FROM hr2_hotel WHERE id = ?', [(int) $j['hotel_id']])->getRowArray()['primary_sup_acc_no'] ?? ''));
            } catch (\Throwable $e) {
                $acc = '';
            }
        }
        $hotel = ['name' => $j['hotel_name'], 'acc' => $acc !== '' ? $acc : null, 'address' => null, 'phone' => null, 'mobile' => null, 'email' => null, 'contact' => null];
        if ($acc !== '') {
            try {
                $sup = $this->db->query('SELECT Name, Address, TP, Mobile, email, rep_offce FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
                if ($sup) {
                    // a supplier record's "0", "-" or "N/A" is an empty field, not a phone number
                    $clean = static fn ($v) => trim((string) ($v ?? '')) !== '' && !in_array(strtoupper(trim((string) $v)), ['0', '-', 'N/A', 'NA', 'NIL', 'NONE', 'NULL'], true) ? trim((string) $v) : null;
                    $hotel['address'] = $clean($sup['Address'] ?? null);
                    $hotel['phone']   = $clean($sup['TP'] ?? null);
                    $hotel['mobile']  = $clean($sup['Mobile'] ?? null);
                    $hotel['email']   = $clean($sup['email'] ?? null);
                    $hotel['contact'] = $clean($sup['rep_offce'] ?? null);
                    $hotel['name']    = $hotel['name'] ?: $clean($sup['Name'] ?? null);
                }
            } catch (\Throwable $e) {
                // a supplier table this install lacks: the document goes without those lines
            }
        }
        // tf-walkfix-r1 (point 5): the Hotel Master's own Reservations e-mail and phone (Hotel Rates panel → the
        // hotel's details) are what the letters go to — the supplier record's lines stand in only where the master
        // has none. Read, never written, from here.
        $hotel['reservation_email'] = null;
        $hotel['reservation_phone'] = null;
        if (!empty($j['hotel_id'])) {
            try {
                $hm = $this->db->query('SELECT reservations_email, reservations_phone FROM hr2_hotel WHERE id = ?', [(int) $j['hotel_id']])->getRowArray();
                $cleanM = static fn ($v) => trim((string) ($v ?? '')) !== '' && !in_array(strtoupper(trim((string) $v)), ['0', '-', 'N/A', 'NA', 'NIL', 'NONE', 'NULL'], true) ? trim((string) $v) : null;
                if ($hm) {
                    $hotel['reservation_email'] = $cleanM($hm['reservations_email'] ?? null);
                    $hotel['reservation_phone'] = $cleanM($hm['reservations_phone'] ?? null);
                }
            } catch (\Throwable $e) {
                // an install without the Hotel Rates panel: the supplier record alone
            }
        }
        if ($hotel['reservation_email'] !== null) {
            $hotel['email'] = $hotel['reservation_email'];
        }
        if ($hotel['reservation_phone'] !== null && $hotel['phone'] === null) {
            $hotel['phone'] = $hotel['reservation_phone'];
        }
        $pax = [];
        try {
            $pax = $this->db->query('SELECT full_name, room_type, note FROM tf_intake_pax WHERE intake_id = ? ORDER BY sort_order, id', [(int) $j['intake_id']])->getResultArray();
        } catch (\Throwable $e) {
            $pax = [];
        }
        $company = [];
        foreach (['company_name', 'company_address', 'company_phone', 'company_email'] as $k) {
            $company[substr($k, 8)] = $this->meta($k);
        }
        $ref = ($j['tour_no'] ?? '') . (isset($it['inquiry_no']) && $it['inquiry_no'] !== $j['tour_no'] ? ' / ' . $it['inquiry_no'] : '');
        return [
            'kind'      => $kind,
            'reference' => $ref,
            'company'   => $company,
            'sender'    => $sender + ['name' => null, 'phone' => null, 'email' => null, 'can_send' => false],
            'hotel'     => $hotel,
            'tour'      => [
                'id' => (int) $j['tour_id'], 'tour_no' => $j['tour_no'], 'inquiry_no' => $it['inquiry_no'] ?? null, 'tour_name' => $j['tour_name'],
                'agent_name' => $j['agent_name'], 'market_code' => $it['market_code'] ?? null, 'country_iso' => $it['country_iso'] ?? null,
                'arrival_date' => $j['arrival_date'], 'departure_date' => $j['departure_date'], 'pax_total' => $j['tour_pax'],
                'pax_adults' => $it['pax_adults'] ?? null, 'pax_children' => $it['pax_children'] ?? null,
                'handler_name' => $j['handler_name'], 'hotel_class' => $j['hotel_class'],
            ],
            'stay'      => [
                'id' => (int) $j['id'], 'hotel_id' => !empty($j['hotel_id']) ? (int) $j['hotel_id'] : null, 'check_in' => $j['check_in'], 'check_out' => $j['check_out'], 'nights' => (int) $j['nights'],
                'day_from' => $j['day_from'], 'day_to' => $j['day_to'], 'rooms' => $j['rooms'], 'rooms_note' => $j['rooms_note'],
                'meal_plan' => $j['meal_plan'], 'pax' => $j['pax'], 'special_note' => $j['special_note'], 'crew_rooms' => $j['crew_rooms'], 'note' => $j['note'],
                'status' => $j['status'], 'confirmation_ref' => $j['confirmation_ref'], 'confirmed_with' => $j['confirmed_with'], 'confirmed_at' => $j['confirmed_at'],
                'voucher_no' => $j['voucher_no'], 'voucher_at' => $j['voucher_at'], 'requested_at' => $j['requested_at'],
                'needs_hotel' => (int) $j['needs_hotel'], 'needs_meal' => (int) $j['needs_meal'], 'needs_supplier' => (int) $j['needs_supplier'],
            ],
            'passengers' => $pax,
            'subject'   => trim(($j['tour_no'] ?? '') . ' — ' . ($j['tour_name'] ?: ($j['agent_name'] ?? '')) . ' — ' . ($hotel['name'] ?: 'hotel') . ' — ' . ($kind === 'voucher' ? 'Hotel voucher ' . ($j['voucher_no'] ?? '') : 'Booking request')),
        ];
    }

    // ---- the Manage privilege's work on a hotel card: edit, archive, restore ----

    /** The columns Management may correct on a card without reopening the quotation. */
    public const HOTEL_EDIT_FIELDS = ['hotel_name', 'area', 'check_in', 'check_out', 'rooms', 'rooms_note', 'meal_plan', 'pax', 'special_note', 'crew_rooms'];

    /**
     * Correct a hotel card in place. The quotation is not touched — this is for the card
     * the Hotel Desk works from: a name spelt wrong, a check-in moved by a day, a meal plan
     * the agent settled by phone. Every change is written to the history.
     */
    public function editHotelJob(int $jobId, array $d, ?string $note = null): array
    {
        $j = $this->hotelJob($jobId);
        if ($j['archived']) {
            throw new \RuntimeException('This card is archived. Restore it first.');
        }
        if ((string) $j['tour_stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        $sets = [];
        $bind = [];
        foreach (self::HOTEL_EDIT_FIELDS as $f) {
            if (!array_key_exists($f, $d)) {
                continue;
            }
            $v = $d[$f];
            switch ($f) {
                case 'check_in':
                case 'check_out':
                    $v = $v === null || trim((string) $v) === '' ? null : date('Y-m-d', strtotime((string) $v));
                    break;
                case 'rooms':
                case 'pax':
                    $v = $v === null || trim((string) $v) === '' ? null : max(0, (int) $v);
                    break;
                case 'meal_plan':
                    $m = strtoupper(str_replace(['/', ' '], '', (string) $v));
                    $v = in_array($m, ['RO', 'BB', 'HB', 'FB', 'AI'], true) ? $m : null;
                    break;
                default:
                    $v = self::cut($v, $f === 'special_note' ? 1000 : ($f === 'hotel_name' ? 190 : 255));
            }
            $sets[] = "`{$f}` = ?";
            $bind[] = $v;
        }
        if (!$sets) {
            throw new \RuntimeException('Nothing to change.');
        }
        $in  = array_key_exists('check_in', $d) ? $d['check_in'] : $j['check_in'];
        $out = array_key_exists('check_out', $d) ? $d['check_out'] : $j['check_out'];
        if ($in && $out) {
            $nights = (int) round((strtotime((string) $out) - strtotime((string) $in)) / 86400);
            if ($nights < 1) {
                throw new \RuntimeException('The check-out has to be after the check-in.');
            }
            $sets[] = '`nights` = ?';
            $bind[] = $nights;
        }
        // the flags follow the facts: a name filled in clears "hotel not named", a meal plan set clears "meal plan not stated"
        $name = array_key_exists('hotel_name', $d) ? trim((string) $d['hotel_name']) : (string) $j['hotel_name'];
        $sets[] = '`needs_hotel` = ?';
        $bind[] = $name === '' ? 1 : 0;
        if (array_key_exists('meal_plan', $d)) {
            $m = strtoupper(str_replace(['/', ' '], '', (string) $d['meal_plan']));
            $sets[] = '`needs_meal` = ?';
            $bind[] = in_array($m, ['RO', 'BB', 'HB', 'FB', 'AI'], true) ? 0 : 1;
        }
        $act = $this->intake()->activity();
        $before = $act->snapshotHotelJob($jobId);
        $sets[] = '`updated_at` = ?';
        $bind[] = $this->now();
        $sets[] = '`updated_by` = ?';
        $bind[] = $this->user;
        $bind[] = $jobId;
        $this->db->query('UPDATE tf_hotel_job SET ' . implode(', ', $sets) . ' WHERE id = ?', $bind);
        $act->logDiff('hotel_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'edited', $before, $act->snapshotHotelJob($jobId), $note);
        return $this->hotelJob($jobId);
    }

    /**
     * Off the desk, restorable. A card the hotel has already been asked about — requested,
     * confirmed, voucher issued — is not archived: bring it back to "to book" or cancel the
     * tour first, so nothing the hotel holds is quietly forgotten.
     */
    public function archiveHotelJob(int $jobId, ?string $note = null): array
    {
        $j = $this->hotelJob($jobId);
        if ($j['archived']) {
            return $j;
        }
        if (in_array((string) $j['status'], ['requested', 'confirmed', 'voucher_issued'], true)) {
            throw new \RuntimeException('This stay is ' . str_replace('_', ' ', (string) $j['status']) . ' with the hotel. Put it back to "to book" (or cancel the tour) before archiving it, so nothing the hotel is holding is forgotten.');
        }
        $now = $this->now();
        $this->db->transStart();
        $this->db->query('UPDATE tf_hotel_job SET archived_at = ?, archived_by = ?, archive_note = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, self::cut($note, 400), $now, $this->user, $jobId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'hotel', $jobId, 'archived', (string) $j['status'], null, $note);
        $this->intake()->activity()->record('hotel_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'archived', trim('Hotel card archived: ' . ($j['hotel_name'] ?: 'no hotel named') . ' ' . ($j['check_in'] ?? '') . ($note ? ' — ' . trim($note) : '')));
        $this->db->transComplete();
        return $this->hotelJob($jobId);
    }

    public function restoreHotelJob(int $jobId): array
    {
        $j = $this->hotelJob($jobId);
        if (!$j['archived']) {
            return $j;
        }
        $now = $this->now();
        $this->db->transStart();
        $this->db->query('UPDATE tf_hotel_job SET archived_at = NULL, archived_by = NULL, archive_note = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, $jobId]);
        $this->dropReservationsMark((int) $j['tour_id']);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'hotel', $jobId, 'restored', null, (string) $j['status'], null);
        $this->intake()->activity()->record('hotel_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'restored', 'Hotel card restored: ' . ($j['hotel_name'] ?: 'no hotel named'));
        $this->db->transComplete();
        return $this->hotelJob($jobId);
    }

    public function archiveTransportJob(int $jobId, ?string $note = null): array
    {
        $j = $this->transportJob($jobId);
        if (!empty($j['archived_at'])) {
            return $j;
        }
        if (!in_array((string) $j['status'], ['to_allocate', 'cancelled', 'settled'], true)) {
            throw new \RuntimeException('This vehicle is ' . str_replace('_', ' ', (string) $j['status']) . '. Only a card still to allocate, or already settled or cancelled, is archived.');
        }
        $now = $this->now();
        $this->db->transStart();
        $this->db->query('UPDATE tf_transport_job SET archived_at = ?, archived_by = ?, archive_note = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, self::cut($note, 400), $now, $this->user, $jobId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'archived', (string) $j['status'], null, $note);
        $this->intake()->activity()->record('transport_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'archived', trim('Transport card archived: ' . $j['vehicle_type'] . ($note ? ' — ' . trim($note) : '')));
        $this->db->transComplete();
        return $this->transportJob($jobId);
    }

    public function restoreTransportJob(int $jobId): array
    {
        $j = $this->transportJob($jobId);
        if (empty($j['archived_at'])) {
            return $j;
        }
        $now = $this->now();
        $this->db->transStart();
        $this->db->query('UPDATE tf_transport_job SET archived_at = NULL, archived_by = NULL, archive_note = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, $jobId]);
        $this->dropTransportMark((int) $j['tour_id']);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'restored', null, (string) $j['status'], null);
        $this->intake()->activity()->record('transport_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'restored', 'Transport card restored: ' . $j['vehicle_type']);
        $this->db->transComplete();
        return $this->transportJob($jobId);
    }

    public function reasons(string $kind = 'hotel_problem'): array
    {
        return $this->db->query('SELECT code, name FROM tf_reason WHERE kind = ? AND active = 1 ORDER BY sort_order, code', [$kind])->getResultArray();
    }

    private function hotelMove(int $jobId, array $from, string $to, string $event, array $sets, ?string $note): array
    {
        $j = $this->hotelJob($jobId);
        if (in_array($to, ['requested', 'confirmed'], true)) {   // tf-stage8: no new booking with a blocked hotel
            $why = Blocks::workRefusal($this->db, ['hotel' => (string) (int) ($j['hotel_id'] ?? 0)], 'hotel booking (' . ($j['tour_no'] ?? $j['tour_id']) . ')', $this->user) ?? Blocks::workRefusalForSupplierAcc($this->db, $j['hotel_acc'] ?? null, 'hotel booking (' . ($j['tour_no'] ?? $j['tour_id']) . ')', $this->user);
            if ($why) {
                throw new \RuntimeException($why);
            }
        }
        if ((string) $j['tour_stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        if ($j['archived']) {
            throw new \RuntimeException('This card is archived. Management can restore it from the ⋯ Manage menu.');
        }
        if (!in_array((string) $j['status'], $from, true)) {
            throw new \RuntimeException('This card is ' . str_replace('_', ' ', (string) $j['status']) . '; it cannot be moved to ' . str_replace('_', ' ', $to) . ' from there.');
        }
        $sets['status']     = $to;
        $sets['updated_at'] = $this->now();
        $sets['updated_by'] = $this->user;
        $this->db->transStart();
        $this->db->query('UPDATE tf_hotel_job SET ' . implode(', ', array_map(static fn ($k) => "`{$k}` = ?", array_keys($sets))) . ' WHERE id = ?', array_merge(array_values($sets), [$jobId]));
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'hotel', $jobId, $event, (string) $j['status'], $to, $note);
        if (!in_array($to, ['confirmed', 'voucher_issued'], true)) {
            $this->dropReservationsMark((int) $j['tour_id']);
        }
        $this->db->transComplete();
        return $this->hotelJob($jobId);
    }

    /** The request goes to the hotel by the desk's own email; this records that it went. */
    public function hotelRequest(int $jobId, ?string $note = null): array
    {
        $j = $this->hotelJob($jobId);
        if ((int) $j['needs_hotel']) {
            throw new \RuntimeException('No hotel is named for these nights. The Quotation Desk has to name one before a request can go.');
        }
        if ((int) $j['needs_meal']) {
            throw new \RuntimeException('The meal plan is not stated. Ask the Quotation Desk to set it on the inquiry before requesting the stay.');
        }
        return $this->hotelMove($jobId, ['to_book', 'problem'], 'requested', 'requested', ['requested_at' => $this->now(), 'requested_by' => $this->user, 'request_note' => self::cut($note, 400), 'problem_code' => null, 'problem_note' => null], $note);
    }

    /**
     * The request went to the hotel from here (by email, or by other means the desk
     * records): the copy is kept, and a stay still to book moves to requested. A stay
     * already requested keeps its place — this was a reminder — and the trail says so.
     */
    public function hotelRequestSent(int $jobId, string $channel, ?string $to, ?string $cc, ?string $subject, ?string $body, ?string $byName = null, ?string $note = null): array
    {
        $j = $this->hotelJob($jobId);
        if ((int) $j['needs_hotel']) {
            throw new \RuntimeException('No hotel is named for these nights. The Quotation Desk has to name one before a request can go.');
        }
        if ((int) $j['needs_meal']) {
            throw new \RuntimeException('The meal plan is not stated. Ask the Quotation Desk to set it on the inquiry before requesting the stay.');
        }
        if ($j['archived']) {
            throw new \RuntimeException('This card is archived. Management can restore it from the ⋯ Manage menu.');
        }
        $this->db->transStart();
        $this->recordHotelMessage($jobId, 'request', $channel, $to, $cc, $subject, $body, $channel === 'email' ? 'sent' : 'recorded', null, $byName);
        $how = $channel === 'email' ? 'sent by email to ' . ($to ?: '?') : 'sent ' . ($note ?: 'by other means');
        if (in_array((string) $j['status'], ['to_book', 'problem'], true)) {
            $this->hotelMove($jobId, ['to_book', 'problem'], 'requested', 'requested', ['requested_at' => $this->now(), 'requested_by' => $this->user, 'request_note' => self::cut($how, 400), 'problem_code' => null, 'problem_note' => null], $how);
        } else {
            $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'hotel', $jobId, 'request_resent', (string) $j['status'], (string) $j['status'], $how);
        }
        $this->db->transComplete();
        return $this->hotelJob($jobId);
    }

    /** The voucher went to the hotel: the copy is kept and the trail says so; the card does not move. */
    public function hotelVoucherSent(int $jobId, string $channel, ?string $to, ?string $cc, ?string $subject, ?string $body, ?string $byName = null): array
    {
        $j = $this->hotelJob($jobId);
        if ((string) $j['status'] !== 'voucher_issued') {
            throw new \RuntimeException('Issue the voucher first; then it can be sent.');
        }
        $this->db->transStart();
        $this->recordHotelMessage($jobId, 'voucher', $channel, $to, $cc, $subject, $body, $channel === 'email' ? 'sent' : 'recorded', null, $byName);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'hotel', $jobId, 'voucher_sent', (string) $j['status'], (string) $j['status'], ($j['voucher_no'] ?? 'voucher') . ($channel === 'email' ? ' sent by email to ' . ($to ?: '?') : ' sent by other means'));
        $this->db->transComplete();
        return $this->hotelJob($jobId);
    }

    public function hotelConfirm(int $jobId, string $ref, ?string $with = null, ?string $note = null): array
    {
        $ref = self::cut($ref, 100);
        if ($ref === null) {
            throw new \RuntimeException('Enter the hotel\'s confirmation reference.');
        }
        return $this->hotelMove($jobId, ['to_book', 'requested', 'problem'], 'confirmed', 'confirmed', ['confirmation_ref' => $ref, 'confirmed_with' => self::cut($with, 160), 'confirmed_at' => $this->now(), 'confirmed_by' => $this->user, 'problem_code' => null, 'problem_note' => null], trim('ref ' . $ref . ($note ? ' — ' . $note : '')));
    }

    public function nextVoucherNo(): string
    {
        $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'voucher_next'");
        $n = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray()['n'] ?? 0);
        if ($n <= 0) {
            $this->db->query("INSERT INTO tf_meta (k, v) VALUES ('voucher_next', '1001') ON DUPLICATE KEY UPDATE v = v");
            $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'voucher_next'");
            $n = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray()['n'] ?? 1001);
        }
        return trim(($this->meta('voucher_prefix') ?? 'HV') . ' ' . ($n - 1));
    }

    public function hotelVoucher(int $jobId, ?string $note = null): array
    {
        $j = $this->hotelJob($jobId);
        if ((string) $j['status'] !== 'confirmed') {
            throw new \RuntimeException('A voucher is issued only once the hotel has confirmed.');
        }
        $no = $this->nextVoucherNo();
        return $this->hotelMove($jobId, ['confirmed'], 'voucher_issued', 'voucher', ['voucher_no' => $no, 'voucher_at' => $this->now(), 'voucher_by' => $this->user], trim($no . ($note ? ' — ' . $note : '')));
    }

    public function hotelProblem(int $jobId, string $code, ?string $note = null): array
    {
        $reason = $this->db->query('SELECT code, name FROM tf_reason WHERE code = ? AND kind = ? AND active = 1', [$code, 'hotel_problem'])->getRowArray();
        if (!$reason) {
            throw new \RuntimeException('Choose a reason from the list.');
        }
        $note = self::cut($note, 400);
        if ($code === 'OTHER' && $note === null) {
            throw new \RuntimeException('"Other" needs a note saying what the problem is.');
        }
        return $this->hotelMove($jobId, ['to_book', 'requested', 'confirmed', 'voucher_issued'], 'problem', 'problem', ['problem_code' => $code, 'problem_note' => $note, 'problem_at' => $this->now(), 'problem_by' => $this->user], trim($reason['name'] . ($note ? ' — ' . $note : '')));
    }

    /** The desk found a way after all: the card goes back to the start of the ladder. */
    public function hotelBack(int $jobId, ?string $note = null): array
    {
        $note = self::cut($note, 400);
        if ($note === null) {
            throw new \RuntimeException('Say what changed; the note stays on the trail.');
        }
        $j = $this->hotelJob($jobId);
        $sets = ['problem_code' => null, 'problem_note' => null, 'confirmation_ref' => null, 'confirmed_at' => null, 'confirmed_by' => null, 'confirmed_with' => null, 'requested_at' => null, 'requested_by' => null];
        if (!empty($j['voucher_no'])) {
            // the number stays on the trail; a fresh voucher takes a fresh number
            $sets += ['voucher_no' => null, 'voucher_at' => null, 'voucher_by' => null];
            $note = 'voucher ' . $j['voucher_no'] . ' withdrawn — ' . $note;
        }
        return $this->hotelMove($jobId, ['problem', 'requested', 'confirmed', 'voucher_issued'], 'to_book', 'back', $sets, $note);
    }

    /** The desk's own note on a card — never the stay itself, which is the quotation's. */
    public function hotelNote(int $jobId, ?string $note, ?string $crewRooms = null): array
    {
        $j = $this->hotelJob($jobId);
        if ($j['archived']) {
            throw new \RuntimeException('This card is archived. Management can restore it from the ⋯ Manage menu.');
        }
        $act = $this->intake()->activity();
        $before = $act->snapshotHotelJob($jobId);
        $this->db->query('UPDATE tf_hotel_job SET note = ?, crew_rooms = COALESCE(?, crew_rooms), amended = 0, updated_at = ?, updated_by = ? WHERE id = ?', [self::cut($note, 500), self::cut($crewRooms, 255), $this->now(), $this->user, $jobId]);
        $act->logDiff('hotel_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'note', $before, $act->snapshotHotelJob($jobId));
        return $this->hotelJob($jobId);
    }

    // =====================================================================
    //  Activities & tickets (the Hotel Reservations & Activities desk, schema 20)
    // =====================================================================

    public function serviceQueue(array $f = []): array
    {
        if (!$this->hasServiceJobs()) {
            return [];
        }
        $status = (string) ($f['status'] ?? 'open');
        $where  = !empty($f['tour_id']) ? 'WHERE 1 = 1' : "WHERE t.stage <> 'cancelled'" . $this->liveTourSql();
        $binds  = [];
        if ($status === 'archived') {
            $where = 'WHERE j.archived_at IS NOT NULL';
        } else {
            $where .= ' AND j.archived_at IS NULL';
            if ($status === 'open') {
                $where .= " AND j.status IN ('to_book','requested','problem')";
            } elseif ($status !== 'all') {
                $where .= ' AND j.status = ?';
                $binds[] = $status;
            }
        }
        [$fs, $fb] = $this->tourFilters($f, ['t.tour_no', 't.agent_name', 't.tour_name', 'j.title']);
        $where .= $fs;
        array_push($binds, ...$fb);
        if (!empty($f['tour_id'])) {
            $where .= ' AND j.tour_id = ?';
            $binds[] = (int) $f['tour_id'];
        }
        return $this->db->query(
            "SELECT j.*, t.tour_no, t.agent_name, t.tour_name, t.arrival_date, t.departure_date, t.pax_total AS tour_pax, t.stage AS tour_stage, t.handler_id, t.handler_name, t.intake_id, t.reservations_confirmed_at
             FROM tf_service_job j JOIN tf_tour t ON t.id = j.tour_id {$where}
             ORDER BY t.arrival_date IS NULL, t.arrival_date, t.id, j.on_date IS NULL, j.on_date, j.seq LIMIT 500",
            $binds
        )->getResultArray();
    }

    public function serviceCounts(): array
    {
        $out = array_fill_keys(self::SERVICE_STATUSES, 0);
        $out['archived'] = 0;
        if (!$this->hasServiceJobs()) {
            return $out;
        }
        foreach ($this->db->query("SELECT j.status, COUNT(*) n FROM tf_service_job j JOIN tf_tour t ON t.id = j.tour_id WHERE t.stage <> 'cancelled' AND j.archived_at IS NULL GROUP BY j.status")->getResultArray() as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        $out['archived'] = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_service_job WHERE archived_at IS NOT NULL')->getRowArray()['n'] ?? 0);
        return $out;
    }

    public function serviceJob(int $jobId): array
    {
        if (!$this->hasServiceJobs()) {
            throw new \RuntimeException('Activities and tickets are not installed on this site yet; run Install from the Inquiries screen.');
        }
        $j = $this->db->query(
            'SELECT j.*, t.tour_no, t.agent_name, t.tour_name, t.arrival_date, t.departure_date, t.pax_total AS tour_pax, t.stage AS tour_stage, t.intake_id, t.handler_id, t.handler_name, t.reservations_confirmed_at
             FROM tf_service_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.id = ?',
            [$jobId]
        )->getRowArray();
        if (!$j) {
            throw new \RuntimeException('Activity or ticket line not found.');
        }
        $j['archived'] = !empty($j['archived_at']);
        $j['events']   = $this->db->query("SELECT * FROM tf_tour_event WHERE lane = 'service' AND job_id = ? ORDER BY id DESC LIMIT 50", [$jobId])->getResultArray();
        // tf-tickets-r1: the entrance ticket stock as it bears on this line — the attraction its fee row is under (if any),
        // the tickets it holds, the amount the quotation carried (for pay at gate), and the attractions under control to pick from
        $j['tickets']      = [];
        $j['ticket_text']  = '';
        $j['attraction']   = null;
        $j['attractions']  = [];
        $j['gate_amount']  = null;
        $j['gate_hint']    = false;
        $j['stock_on']     = $this->stock()->available();
        if ($j['stock_on']) {
            $j['tickets']     = $this->stock()->ticketsFor($jobId);
            $j['ticket_text'] = TicketStock::rangeText($j['tickets']);
            // schema 39: by the place of the Place to Visit / Entrance Fee Master the line is (its old record before the master)
            $j['attraction']  = $this->stock()->attractionFor(isset($j['place_id']) && $j['place_id'] !== null ? (int) $j['place_id'] : null, isset($j['fee_id']) ? (int) $j['fee_id'] : null);
            $j['attractions'] = array_map(static fn ($a) => ['id' => $a['id'], 'name' => $a['name'], 'control' => $a['control'], 'n_available' => $a['n_available']], $this->stock()->attractions(true));
        }
        if ((string) $j['source'] === 'service' && !empty($j['source_id'])) {
            try {
                $sv = $this->db->query('SELECT unit_usd, amount_usd, pax FROM tf_intake_service WHERE id = ?', [(int) $j['source_id']])->getRowArray();
                if ($sv && $sv['unit_usd'] !== null && (float) $sv['unit_usd'] > 0) {
                    $pax = (int) ($j['pax'] ?? 0) ?: (int) ($sv['pax'] ?? 0);
                    $j['gate_amount'] = 'USD ' . number_format((float) $sv['unit_usd'], 2) . ($pax > 0 ? ' × ' . $pax . ' = USD ' . number_format((float) $sv['unit_usd'] * $pax, 2) : '') . ' on the quotation line';
                }
            } catch (\Throwable $e) {
                // the figure is a help, never a gate
            }
        }
        $placeHint = null;
        if ((string) $j['source'] === 'service' && !empty($j['source_id']) && EntranceMaster::installed($this->db)) {
            // schema 39: the place of the Place to Visit / Entrance Fee Master the line was priced from says who settles it
            try {
                $pl = $this->db->query('SELECT p.pay_mode FROM tf_intake_service s JOIN tf_place p ON p.id = s.place_id WHERE s.id = ?', [(int) $j['source_id']])->getRowArray();
                $placeHint = $pl ? (string) $pl['pay_mode'] === 'direct' : null;
            } catch (\Throwable $e) {
                $placeHint = null;
            }
        }
        if ($placeHint !== null) {
            $j['gate_hint'] = $placeHint;
        } elseif (!empty($j['fee_id'])) {
            try {
                $fee = $this->db->query('SELECT direct_payment FROM entrance_fee WHERE id = ?', [(int) $j['fee_id']])->getRowArray();
                $j['gate_hint'] = (int) ($fee['direct_payment'] ?? 0) === 1;
            } catch (\Throwable $e) {
                // no entrance master
            }
        }
        // schema 40: a lunch or dinner from the Meal Master — the restaurant, where, how to reach them, when, for whom, what it costs
        $j['meal'] = null;
        if ((string) $j['source'] === 'service' && !empty($j['source_id'])) {
            try {
                $j['meal'] = (new MealMaster($this->db, $this->user))->forLine((int) $j['source_id']);
            } catch (\Throwable $e) {
                $j['meal'] = null;   // a help to the desk, never a gate
            }
        }
        // schema 40: booking the restaurant — the booking vouchers of the line, and what a voucher would say now (the amount in LKR
        // from the Meal Master's actual rates, where it goes), or why none can be issued
        $j['meal_vouchers'] = [];
        $j['meal_booking']  = null;
        if ($j['meal'] !== null) {
            try {
                $mm = new MealMaster($this->db, $this->user);
                if ($mm->vouchersOn()) {
                    $j['meal_vouchers'] = $mm->vouchers($jobId);
                    $b = $mm->booking($jobId);
                    $j['meal_booking'] = [
                        'amount_lkr' => $b['lkr']['total'] ?? null, 'tax_lkr' => $b['lkr']['tax'] ?? null, 'to' => $b['to'], 'contact' => $b['contact'], 'why' => $b['why'],
                        'live_id' => $b['live']['id'] ?? null, 'stale' => $b['stale'], 'lkr_text' => $b['meal']['lkr_text'] ?? null, 'needs_supplier' => $b['meal']['needs_supplier'] ?? false,
                    ];
                }
            } catch (\Throwable $e) {
                $j['meal_booking'] = null;   // a help to the desk, never a gate
            }
        }
        return $j;
    }

    private function serviceMove(int $jobId, array $from, string $to, string $event, array $sets, ?string $note): array
    {
        $j = $this->serviceJob($jobId);
        if (in_array($to, ['requested', 'confirmed'], true)) {   // tf-stage8: no new booking with a blocked operator, restaurant or supplier
            foreach (Blocks::serviceJobSupplierAccs($this->db, $j) as $sacc) {
                if ($why = Blocks::workRefusalForSupplierAcc($this->db, $sacc, 'supplier booking (' . ($j['tour_no'] ?? $j['tour_id']) . ')', $this->user)) {
                    throw new \RuntimeException($why);
                }
            }
        }
        if ((string) $j['tour_stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        if ($j['archived']) {
            throw new \RuntimeException('This line is archived. Management can restore it from the ⋯ Manage menu.');
        }
        if (!in_array((string) $j['status'], $from, true)) {
            throw new \RuntimeException('This line is ' . str_replace('_', ' ', (string) $j['status']) . '; it cannot be moved to ' . str_replace('_', ' ', $to) . ' from there.');
        }
        $sets['status']     = $to;
        $sets['updated_at'] = $this->now();
        $sets['updated_by'] = $this->user;
        $this->db->transStart();
        $this->db->query('UPDATE tf_service_job SET ' . implode(', ', array_map(static fn ($k) => "`{$k}` = ?", array_keys($sets))) . ' WHERE id = ?', array_merge(array_values($sets), [$jobId]));
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'service', $jobId, $event, (string) $j['status'], $to, $note);
        // a line moving off "done" takes the tour's mark with it: the mark says everything is confirmed
        if (!in_array($to, self::SERVICE_DONE, true)) {
            // tf-confirmed-r5: the same as a hotel card — the mark comes off and the handling agent hears about it
            $this->db->query('UPDATE tf_tour SET reservations_confirmed_at = NULL, reservations_confirmed_by = NULL WHERE id = ? AND reservations_confirmed_at IS NOT NULL', [(int) $j['tour_id']]);
            if ((int) $this->db->affectedRows() > 0) {
                $this->markDropped((int) $j['tour_id'], 'reservations_unconfirmed', 'Hotels and activities',
                    'The activity "' . $j['title'] . '" was moved back to ' . str_replace('_', ' ', $to) . ' after the work had been marked complete, so the mark came off.');
            }
        }
        $this->db->transComplete();
        return $this->serviceJob($jobId);
    }

    /** The request goes to the supplier by the desk's own email or phone; this records that it went. */
    public function serviceRequest(int $jobId, ?string $note = null): array
    {
        return $this->serviceMove($jobId, ['to_book', 'problem'], 'requested', 'requested', ['requested_at' => $this->now(), 'requested_by' => $this->user, 'request_note' => self::cut($note, 400), 'problem_code' => null, 'problem_note' => null], $note);
    }

    public function serviceConfirm(int $jobId, string $ref, ?string $with = null, ?string $note = null): array
    {
        $ref = self::cut($ref, 100);
        if ($ref === null) {
            throw new \RuntimeException('Enter the supplier\'s confirmation or ticket reference.');
        }
        return $this->serviceMove($jobId, ['to_book', 'requested', 'problem'], 'confirmed', 'confirmed', ['confirmation_ref' => $ref, 'confirmed_with' => self::cut($with, 160), 'confirmed_at' => $this->now(), 'confirmed_by' => $this->user, 'problem_code' => null, 'problem_note' => null], trim('ref ' . $ref . ($note ? ' — ' . $note : '')));
    }

    /** Nothing to arrange for this line (paid at the gate, no booking taken, already in hand): said, with why. */
    public function serviceNotNeeded(int $jobId, ?string $note = null): array
    {
        $note = self::cut($note, 400);
        if ($note === null) {
            throw new \RuntimeException('Say why nothing needs arranging; the note stays on the trail.');
        }
        return $this->serviceMove($jobId, ['to_book', 'requested', 'problem'], 'not_needed', 'not_needed', ['request_note' => $note, 'problem_code' => null, 'problem_note' => null], $note);
    }

    public function serviceProblem(int $jobId, string $code, ?string $note = null): array
    {
        $reason = $this->db->query('SELECT code, name FROM tf_reason WHERE code = ? AND kind = ? AND active = 1', [$code, 'service_problem'])->getRowArray();
        if (!$reason) {
            throw new \RuntimeException('Choose a reason from the list.');
        }
        $note = self::cut($note, 400);
        if ($code === 'S_OTHER' && $note === null) {
            throw new \RuntimeException('"Other" needs a note saying what the problem is.');
        }
        return $this->serviceMove($jobId, ['to_book', 'requested', 'confirmed', 'not_needed', 'pay_at_gate'], 'problem', 'problem', ['problem_code' => $code, 'problem_note' => $note, 'problem_at' => $this->now(), 'problem_by' => $this->user], trim($reason['name'] . ($note ? ' — ' . $note : '')));
    }

    public function serviceBack(int $jobId, ?string $note = null): array
    {
        $note = self::cut($note, 400);
        if ($note === null) {
            throw new \RuntimeException('Say what changed; the note stays on the trail.');
        }
        // tf-tickets-r1: tickets allocated from stock go back to stock with the line (used ones stay used, on the trail)
        $back = $this->stock()->release($jobId, $note);
        $card = $this->serviceMove($jobId, ['problem', 'requested', 'confirmed', 'not_needed', 'pay_at_gate'], 'to_book', 'back', ['problem_code' => null, 'problem_note' => null, 'confirmation_ref' => null, 'confirmed_at' => null, 'confirmed_by' => null, 'confirmed_with' => null, 'requested_at' => null, 'requested_by' => null], $note . ($back ? ' — ' . $back . ' ticket(s) back to stock' : ''));
        if ($back) {
            $this->event((int) $card['intake_id'], (int) $card['tour_id'], 'service', $jobId, 'tickets_released', null, null, $back . ' ticket(s) back to stock.');
        }
        return $card;
    }

    // ---- tf-tickets-r1 (schema 37): entrance tickets from stock, and the pay-at-gate status ----

    /**
     * The entrance is paid at the counter on the day: the line's own status, counted as done. Says so with the
     * amount the quotation carried, never "nothing to arrange".
     */
    public function servicePayAtGate(int $jobId, ?string $note = null): array
    {
        $j = $this->serviceJob($jobId);
        $words = 'Paid at the gate on the day' . ($j['gate_amount'] !== null ? ' — ' . $j['gate_amount'] : '') . ($note ? ' — ' . trim($note) : '');
        return $this->serviceMove($jobId, ['to_book', 'requested', 'problem'], 'pay_at_gate', 'pay_at_gate', ['request_note' => self::cut($words, 400), 'problem_code' => null, 'problem_note' => null], $words);
    }

    /**
     * Tickets from stock for the line: as many as the party (or the count given), the lowest available numbers of
     * the attraction — the one the line's Entrance Fee row is under, or the one chosen when the line names none.
     * The line is then confirmed, its reference the numbers themselves; more can be added to a line that already
     * holds tickets (a bigger party). Never from a line confirmed with a supplier — go back first.
     */
    public function serviceTickets(int $jobId, ?int $attractionId = null, ?int $count = null, ?string $note = null): array
    {
        $j = $this->serviceJob($jobId);
        if ((string) $j['tour_stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        if ($j['archived']) {
            throw new \RuntimeException('This line is archived. Management can restore it from the ⋯ Manage menu.');
        }
        $held = $j['tickets'] ?? [];
        if ((string) $j['status'] === 'confirmed' && !$held) {
            throw new \RuntimeException('This line is confirmed with the supplier (ref ' . $j['confirmation_ref'] . '); go back to "to book" before taking tickets from stock.');
        }
        if (!in_array((string) $j['status'], ['to_book', 'requested', 'problem', 'confirmed'], true)) {
            throw new \RuntimeException('This line is ' . str_replace('_', ' ', (string) $j['status']) . '; tickets are allocated from "to book".');
        }
        $attractionId = $attractionId ?: (int) ($j['attraction']['id'] ?? 0);
        if ($held) {
            $attractionId = (int) $held[0]['attraction_id'];
        }
        if ($attractionId <= 0) {
            throw new \RuntimeException('Choose which attraction\'s tickets this line takes — it is not matched to one under control.');
        }
        $count = $count ?: (int) ($j['pax'] ?? 0);
        if ($count <= 0) {
            throw new \RuntimeException('How many tickets? The line has no party size; type the count.');
        }
        $tickets = $this->stock()->allocate($jobId, (int) $j['tour_id'], $attractionId, $count);
        $ref     = 'Tickets ' . TicketStock::rangeText($tickets);
        $sets    = ['confirmation_ref' => self::cut($ref, 100), 'confirmed_with' => 'Ticket stock', 'confirmed_at' => $this->now(), 'confirmed_by' => $this->user, 'problem_code' => null, 'problem_note' => null];
        if ((string) $j['status'] === 'confirmed') {
            // more tickets on a line already holding some: the reference grows, the status stays
            $sets['updated_at'] = $this->now();
            $sets['updated_by'] = $this->user;
            $this->db->query('UPDATE tf_service_job SET ' . implode(', ', array_map(static fn ($k) => "`{$k}` = ?", array_keys($sets))) . ' WHERE id = ?', array_merge(array_values($sets), [$jobId]));
            $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'service', $jobId, 'tickets', 'confirmed', 'confirmed', $count . ' more ticket(s) from stock: now ' . $ref . ($note ? ' — ' . trim($note) : ''));
            return $this->serviceJob($jobId);
        }
        return $this->serviceMove($jobId, ['to_book', 'requested', 'problem'], 'confirmed', 'tickets', $sets, $count . ' ticket(s) from stock — ' . $ref . ($note ? ' — ' . trim($note) : ''));
    }

    /** The line's tickets were used at the gate (the tour has run). The status stays confirmed; the tickets read used. */
    public function serviceTicketsUsed(int $jobId): array
    {
        $j = $this->serviceJob($jobId);
        $n = $this->stock()->markUsed($jobId);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'service', $jobId, 'tickets_used', null, null, $n . ' ticket(s) used at the gate.');
        return $this->serviceJob($jobId);
    }

    public function serviceNote(int $jobId, ?string $note): array
    {
        $j = $this->serviceJob($jobId);
        if ($j['archived']) {
            throw new \RuntimeException('This line is archived. Management can restore it from the ⋯ Manage menu.');
        }
        $this->db->query('UPDATE tf_service_job SET note = ?, amended = 0, updated_at = ?, updated_by = ? WHERE id = ?', [self::cut($note, 500), $this->now(), $this->user, $jobId]);
        return $this->serviceJob($jobId);
    }

    /**
     * A line the desk adds itself — something to arrange that the quotation did not carry
     * as a line of its own (a train the agent mentioned in passing, a ticket the guide asked
     * for). It says so (source 'desk') and is never carried back into the quotation.
     */
    public function addServiceJob(int $tourId, array $d): array
    {
        if (!$this->hasServiceJobs()) {
            throw new \RuntimeException('Activities and tickets are not installed on this site yet.');
        }
        $t = $this->tour($tourId);
        if ((string) $t['stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        $title = self::cut($d['title'] ?? null, 255);
        if ($title === null) {
            throw new \RuntimeException('Say what is to be arranged.');
        }
        $kind = in_array((string) ($d['kind'] ?? ''), ['visit', 'entrance', 'safari', 'train', 'ayurveda', 'lunch', 'dinner', 'excursion', 'other'], true) ? (string) $d['kind'] : 'other';
        $dayNo = self::intOrNull($d['day_no'] ?? null);
        $on = self::cut($d['on_date'] ?? null, 10);
        if ($on === null && $dayNo !== null && $dayNo >= 1 && $t['arrival_date']) {
            $on = date('Y-m-d', strtotime($t['arrival_date'] . ' +' . ($dayNo - 1) . ' days'));
        }
        $seq = (int) ($this->db->query('SELECT COALESCE(MAX(seq), -1) m FROM tf_service_job WHERE tour_id = ?', [$tourId])->getRowArray()['m'] ?? -1) + 1;
        $this->db->transStart();
        // tf-supmail-r1: the desk may name the place it is booking, so the line's reservation request can reach
        // that place's own booking contact. A place that is not on the master is simply not set.
        $placeId = self::intOrNull($d['place_id'] ?? null);
        if ($placeId !== null && !$this->db->query('SELECT id FROM tf_place WHERE id = ? AND merged_into IS NULL', [$placeId])->getRowArray()) {
            $placeId = null;
        }
        $this->insertServiceJob($tourId, $seq, ['source' => 'desk', 'source_id' => null, 'kind' => $kind, 'title' => $title, 'day_no' => $dayNo, 'on_date' => $on,
            'pax' => self::intOrNull($d['pax'] ?? null) ?? self::intOrNull($t['pax_total']), 'qty' => null, 'pay_mode' => null, 'place_id' => $placeId,
            'on_time' => $d['on_time'] ?? null, 'special_note' => self::cut($d['note'] ?? null, 500)]);
        $id = (int) $this->db->insertID();
        $this->event((int) $t['intake_id'], $tourId, 'service', $id, 'added', null, 'to_book', 'Added by the desk: ' . $title);
        $this->db->query('UPDATE tf_tour SET reservations_confirmed_at = NULL, reservations_confirmed_by = NULL WHERE id = ? AND reservations_confirmed_at IS NOT NULL', [$tourId]);
        if ((int) $this->db->affectedRows() > 0) {
            $this->markDropped($tourId, 'reservations_unconfirmed', 'Hotels and activities',
                'The activity "' . $title . '" was added after the work had been marked complete, so the mark came off.');
        }
        $this->db->transComplete();
        // tf-supmail-r1: a line added after the tour was confirmed gets its own request, like every other
        $this->prepareSupplierRequests($tourId);
        return $this->serviceJob($id);
    }

    public function archiveServiceJob(int $jobId, ?string $note = null): array
    {
        $j = $this->serviceJob($jobId);
        if ($j['archived']) {
            return $j;
        }
        if (in_array((string) $j['status'], ['requested', 'confirmed'], true)) {
            throw new \RuntimeException('This line is ' . (string) $j['status'] . ' with the supplier. Put it back to "to book" first, so nothing the supplier is holding is forgotten.');
        }
        $now = $this->now();
        $this->db->transStart();
        $this->db->query('UPDATE tf_service_job SET archived_at = ?, archived_by = ?, archive_note = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, self::cut($note, 400), $now, $this->user, $jobId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'service', $jobId, 'archived', (string) $j['status'], null, $note);
        $this->intake()->activity()->record('service_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'archived', trim('Activity line archived: ' . $j['title'] . ($note ? ' — ' . trim($note) : '')));
        $this->db->query('UPDATE tf_tour SET reservations_confirmed_at = NULL, reservations_confirmed_by = NULL WHERE id = ? AND reservations_confirmed_at IS NOT NULL', [(int) $j['tour_id']]);
        if ((int) $this->db->affectedRows() > 0) {
            $this->markDropped((int) $j['tour_id'], 'reservations_unconfirmed', 'Hotels and activities',
                'The activity "' . $j['title'] . '" was taken off after the work had been marked complete, so the mark came off.');
        }
        $this->db->transComplete();
        return $this->serviceJob($jobId);
    }

    public function restoreServiceJob(int $jobId): array
    {
        $j = $this->serviceJob($jobId);
        if (!$j['archived']) {
            return $j;
        }
        $now = $this->now();
        $this->db->transStart();
        $this->db->query('UPDATE tf_service_job SET archived_at = NULL, archived_by = NULL, archive_note = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, $jobId]);
        $this->db->query('UPDATE tf_tour SET reservations_confirmed_at = NULL, reservations_confirmed_by = NULL WHERE id = ? AND reservations_confirmed_at IS NOT NULL', [(int) $j['tour_id']]);
        if ((int) $this->db->affectedRows() > 0) {
            $this->markDropped((int) $j['tour_id'], 'reservations_unconfirmed', 'Hotels and activities',
                'The activity "' . $j['title'] . '" was put back after the work had been marked complete, so the mark came off.');
        }
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'service', $jobId, 'restored', null, (string) $j['status'], null);
        $this->intake()->activity()->record('service_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'restored', 'Activity line restored: ' . $j['title']);
        $this->db->transComplete();
        return $this->serviceJob($jobId);
    }

    /**
     * HOTELS & ACTIVITIES CONFIRMED: the desk's word that everything is in hand, put on the
     * tour where the inquiry, the Transport Desk and the Board all see it. Only when every
     * stay is confirmed (or vouchered) and every activity line is confirmed or not needed;
     * a stay or a line that later moves off "done" takes the mark off again.
     */
    public function markReservationsConfirmed(int $tourId): array
    {
        if (!$this->hasServiceJobs()) {
            throw new \RuntimeException('Not installed on this site yet.');
        }
        $t = $this->get($tourId);
        if (!$t) {
            throw new \RuntimeException('Tour not found.');
        }
        if ((string) $t['stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        $s = $t['strip'];
        if (!$s['reservations_ready'] && !$s['reservations_confirmed']) {
            $left = [];
            if ($s['hotels']['of'] > $s['hotels']['done']) {
                $left[] = ($s['hotels']['of'] - $s['hotels']['done']) . ' hotel stay(s)';
            }
            if ($s['activities']['of'] > $s['activities']['done']) {
                $left[] = ($s['activities']['of'] - $s['activities']['done']) . ' activity or ticket line(s)';
            }
            throw new \RuntimeException('Not everything is confirmed yet: ' . (implode(' and ', $left) ?: 'nothing on this tour to confirm') . '.');
        }
        if (!$s['reservations_confirmed']) {
            $now = $this->now();
            $this->db->query('UPDATE tf_tour SET reservations_confirmed_at = ?, reservations_confirmed_by = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, $now, $this->user, $tourId]);
            $this->event((int) $t['intake_id'], $tourId, 'tour', null, 'reservations_confirmed', null, 'confirmed', 'Hotels & activities confirmed: ' . $s['hotels']['done'] . ' stay(s), ' . $s['activities']['done'] . ' activity/ticket line(s).');
        }
        return $this->get($tourId);
    }


    /**
     * tf-confirmed-r2: a desk takes its completion mark off a tour the agent has already been told is confirmed.
     * The reservation agent who owns the tour is told, as a follow-up on the tour's own Communications & History,
     * so it sits in front of a person instead of waiting to be noticed on a list. Best effort: it never stops the
     * desk from recording what it had to record.
     */
    private function warnHandlerOfReopen(array $t, string $what, ?string $why): void
    {
        if (empty($t['final_confirmed_at'])) {
            return;   // nothing was sent to the agent yet: reopening is ordinary desk work
        }
        try {
            $title = 'Changed after the final confirmation — ' . $what . ' reopened on ' . ($t['tour_no'] ?? ('tour ' . $t['id']));
            $detail = $what . ' was reopened by ' . $this->user . ' on ' . $this->now()
                . ($why ? ' — ' . $why : '')
                . '. The agent was told on ' . substr((string) $t['final_confirmed_at'], 0, 16)
                . ' that the tour was confirmed. Finish the work again, then send the updated confirmation from Confirmed Tours.';
            (new Comms($this->db, $this->user, null, $this->user))->addFollowup((int) $t['intake_id'], [
                'title' => $title, 'detail' => $detail,
                'owner_id' => $t['handler_id'] ?? null, 'owner_name' => $t['handler_name'] ?? $this->user,
                'due_on' => date('Y-m-d'),
            ]);
        } catch (\Throwable $e) {
            // the desk's own record stands; the warning is still on the tour's trail and on Confirmed Tours
        }
    }

    public function unmarkReservationsConfirmed(int $tourId, ?string $why = null): array
    {
        $t = $this->tour($tourId);
        $why = self::cut($why, 400);
        if ($why === null) {
            throw new \RuntimeException('Say why the mark is taken off; the reason stays on the trail.');
        }
        $this->db->query('UPDATE tf_tour SET reservations_confirmed_at = NULL, reservations_confirmed_by = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $tourId]);
        $this->event((int) $t['intake_id'], $tourId, 'tour', null, 'reservations_unconfirmed', 'confirmed', null, $why);
        $this->warnHandlerOfReopen($t, 'Hotels and activities', $why);
        return $this->get($tourId);
    }

    /** A stay moving off "done" takes the tour's mark with it, the same as an activity line. */


    // =====================================================================
    //  The Transport Desk's "work complete" mark (schema 21) — the tour's one job there
    // =====================================================================

    /**
     * The Transport Desk has done its work on the tour: every vehicle set, every driver and
     * guide engaged. Marked by a person, never taken by itself; the tour then leaves the red
     * Transport count and sits under Completed with its cards and their history.
     */
    public function markTransportComplete(int $tourId): array
    {
        if (!$this->hasDeskJobs()) {
            throw new \RuntimeException('Not installed on this site yet.');
        }
        $t = $this->get($tourId);
        if (!$t) {
            throw new \RuntimeException('Tour not found.');
        }
        if ((string) $t['stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        $s = $t['strip'];
        if (!$s['transport_ready'] && !$s['transport_complete']) {
            $left = [];
            if ($s['vehicle']['of'] === 0) {
                $left[] = 'no vehicle card on this tour';
            }
            if ($s['vehicle']['done'] < $s['vehicle']['of']) {
                $left[] = ($s['vehicle']['of'] - $s['vehicle']['done']) . ' vehicle(s) to set';
            }
            if ($s['driver']['done'] < $s['driver']['of']) {
                $left[] = ($s['driver']['of'] - $s['driver']['done']) . ' driver(s) to engage';
            }
            if ($s['guide']['done'] < $s['guide']['of']) {
                $left[] = ($s['guide']['of'] - $s['guide']['done']) . ' guide(s) to engage';
            }
            throw new \RuntimeException('Not everything is allocated yet: ' . (implode(', ', $left) ?: 'nothing on this tour to allocate') . '.');
        }
        if (!$s['transport_complete']) {
            $now = $this->now();
            $this->db->query('UPDATE tf_tour SET transport_complete_at = ?, transport_complete_by = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, $now, $this->user, $tourId]);
            $this->event((int) $t['intake_id'], $tourId, 'tour', null, 'transport_complete', null, 'complete', 'Transport work complete: ' . $s['vehicle']['done'] . ' vehicle(s) set, ' . $s['driver']['done'] . ' driver(s) and ' . $s['guide']['done'] . ' guide(s) engaged.');
        }
        return $this->get($tourId);
    }

    public function unmarkTransportComplete(int $tourId, ?string $why = null): array
    {
        $t = $this->tour($tourId);
        $why = self::cut($why, 400);
        if ($why === null) {
            throw new \RuntimeException('Say why the mark is taken off; the reason stays on the trail.');
        }
        $this->db->query('UPDATE tf_tour SET transport_complete_at = NULL, transport_complete_by = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$this->now(), $this->user, $tourId]);
        $this->event((int) $t['intake_id'], $tourId, 'tour', null, 'transport_incomplete', 'complete', null, $why);
        $this->warnHandlerOfReopen($t, 'Transport, driver and guide', $why);
        return $this->get($tourId);
    }

    /** A vehicle card going back, a crew line no longer engaged, an amendment: the mark comes off. */
    /**
     * tf-taxiq-r7 (Shantha, 4 Nov 2026): whether the agent has already been told this tour is confirmed.
     * A change made after that moment is not an ordinary desk edit — somebody outside Avenir is holding a
     * confirmation that is no longer true — so the desk has to say why.
     */
    private function finallyConfirmed(int $tourId): bool
    {
        try {
            $t = $this->db->query('SELECT final_confirmed_at FROM tf_tour WHERE id = ?', [$tourId])->getRowArray();
            return $t !== null && !empty($t['final_confirmed_at']);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * tf-taxiq-r7: the reason for a change made after the client was told the tour was confirmed.
     * Refused before anything is written, so a blank reason changes nothing at all. Returns the reason
     * to keep on the trail, or null when the tour has not been confirmed to the client yet.
     */
    private function changeReasonAfterFinal(int $tourId, ?string $reason, string $what): ?string
    {
        if (!$this->finallyConfirmed($tourId)) {
            return null;
        }
        $r = trim((string) ($reason ?? ''));
        if ($r === '') {
            throw new \InvalidArgumentException(
                'This tour has already been confirmed to the agent, so a change to the ' . $what
                . ' needs a reason. It is kept on the tour\'s record and shown to the handling agent, who has to send an'
                . ' updated confirmation. Nothing has been changed.'
            );
        }
        return mb_substr($r, 0, 400);
    }

    private function dropTransportMark(int $tourId, ?string $why = null): void
    {
        if (!$this->hasDeskJobs()) {
            return;
        }
        $this->db->query('UPDATE tf_tour SET transport_complete_at = NULL, transport_complete_by = NULL WHERE id = ? AND transport_complete_at IS NOT NULL', [$tourId]);
        // tf-confirmed-r4: the desk's work changed and the mark came off by itself. On a tour the client has already
        // been told is confirmed that is exactly the case the handling agent has to hear about — a silent drop would
        // leave the line reading "Confirmed" while transport is open again.
        if ((int) $this->db->affectedRows() > 0) {
            $this->markDropped($tourId, 'transport_incomplete', 'Transport, driver and guide',
                'The vehicle, driver or guide changed after the work had been marked complete, so the mark came off.'
                // tf-taxiq-r7: after a final confirmation the desk's own reason is kept with it, word for word
                . ($why !== null && trim($why) !== '' ? ' Reason given: ' . trim($why) : ''));
        }
    }

    private function dropReservationsMark(int $tourId): void
    {
        if (!$this->hasServiceJobs()) {
            return;
        }
        $this->db->query('UPDATE tf_tour SET reservations_confirmed_at = NULL, reservations_confirmed_by = NULL WHERE id = ? AND reservations_confirmed_at IS NOT NULL', [$tourId]);
        if ((int) $this->db->affectedRows() > 0) {
            $this->markDropped($tourId, 'reservations_unconfirmed', 'Hotels and activities',
                'A hotel stay or an activity changed after the work had been marked complete, so the mark came off.');
        }
    }

    /** the trail line and the handling agent's warning for a completion mark that came off by itself */
    private function markDropped(int $tourId, string $event, string $what, string $why): void
    {
        try {
            $t = $this->db->query('SELECT id, tour_no, intake_id, handler_id, handler_name, final_confirmed_at FROM tf_tour WHERE id = ?', [$tourId])->getRowArray();
            if (!$t) {
                return;
            }
            $this->event((int) $t['intake_id'], $tourId, 'tour', null, $event, 'complete', null, $why);
            $this->warnHandlerOfReopen($t, $what, $why);
        } catch (\Throwable $e) {
            // the desk's own change stands; the warning is best effort
        }
    }

    // =====================================================================
    //  Transport Desk
    // =====================================================================

    public function transportQueue(array $f = []): array
    {
        $status = (string) ($f['status'] ?? 'open');
        $where  = !empty($f['tour_id']) ? 'WHERE 1 = 1' : "WHERE t.stage <> 'cancelled'" . $this->liveTourSql();
        $binds  = [];
        if ($status === 'archived') {
            $where = 'WHERE j.archived_at IS NOT NULL';
        } else {
            $where .= ' AND j.archived_at IS NULL';
            if ($status === 'open') {
                $where .= " AND j.status NOT IN ('settled','cancelled')";
            } elseif ($status !== 'all') {
                $where .= ' AND j.status = ?';
                $binds[] = $status;
            }
        }
        [$fs, $fb] = $this->tourFilters($f, ['t.tour_no', 't.agent_name', 't.tour_name', 'j.vehicle_reg']);
        $where .= $fs;
        array_push($binds, ...$fb);
        if (!empty($f['tour_id'])) {
            $where .= ' AND j.tour_id = ?';
            $binds[] = (int) $f['tour_id'];
        }
        $rows = $this->db->query(
            "SELECT j.id, j.tour_id, j.seq, j.class_code, j.vehicle_type, j.seats, j.qty, j.days, j.km_total, j.extra_km_total, j.vehicle_reg, j.status, j.amended, j.updated_at, j.archived_at, j.archived_by, j.archive_note,
                    t.tour_no, t.agent_name, t.tour_name, t.arrival_date, t.departure_date, t.pax_total AS tour_pax, t.stage AS tour_stage, t.handler_id, t.handler_name, t.intake_id
             FROM tf_transport_job j JOIN tf_tour t ON t.id = j.tour_id {$where}
             ORDER BY t.arrival_date IS NULL, t.arrival_date, t.id, j.seq LIMIT 500",
            $binds
        )->getResultArray();
        foreach ($rows as &$r) {
            // the person on the job now: a person replaced mid-tour (schema 24) is history on the card
            $d = $this->db->query("SELECT person_name, status FROM tf_transport_crew WHERE job_id = ? AND kind = 'driver' AND status <> 'replaced' ORDER BY id DESC LIMIT 1", [(int) $r['id']])->getRowArray();
            $r['driver_name']   = $d['person_name'] ?? null;
            $r['driver_status'] = $d['status'] ?? null;
            $g = $this->db->query("SELECT person_name, status FROM tf_transport_crew WHERE job_id = ? AND kind = 'guide' AND status <> 'replaced' ORDER BY id DESC LIMIT 1", [(int) $r['id']])->getRowArray();
            $r['guide_of']      = $g ? 1 : 0;
            $r['guide_name']    = $g['person_name'] ?? null;
            $r['guide_status']  = $g['status'] ?? null;
            $r['crew_replaced'] = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_transport_crew WHERE job_id = ? AND status = 'replaced'", [(int) $r['id']])->getRowArray()['n'] ?? 0);
            $r['docs_before']   = $this->db->query("SELECT COUNT(*) n FROM tf_transport_doc WHERE job_id = ? AND phase = 'before' AND state IN ('issued','waived')", [(int) $r['id']])->getRowArray()['n'] ?? 0;
            $r['docs_before_of'] = $this->db->query("SELECT COUNT(*) n FROM tf_transport_doc WHERE job_id = ? AND phase = 'before'", [(int) $r['id']])->getRowArray()['n'] ?? 0;
        }
        unset($r);
        return $rows;
    }

    public function transportCounts(): array
    {
        $rows = $this->db->query("SELECT j.status, COUNT(*) n FROM tf_transport_job j JOIN tf_tour t ON t.id = j.tour_id WHERE t.stage <> 'cancelled' AND j.archived_at IS NULL GROUP BY j.status")->getResultArray();
        $out = array_fill_keys(self::TRANSPORT_STATUSES, 0);
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        $out['archived'] = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_transport_job WHERE archived_at IS NOT NULL')->getRowArray()['n'] ?? 0);
        return $out;
    }

    public function transportJob(int $jobId): array
    {
        $j = $this->db->query(
            'SELECT j.*, t.tour_no, t.agent_name, t.tour_name, t.arrival_date, t.departure_date, t.pax_total AS tour_pax, t.stage AS tour_stage, t.intake_id, t.arrival_flight, t.departure_flight, t.handler_id, t.handler_name
             FROM tf_transport_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.id = ?',
            [$jobId]
        )->getRowArray();
        if (!$j) {
            throw new \RuntimeException('Transport card not found.');
        }
        // kilometres, never rupees, on the card itself: the rate per kilometre is the Board's. What the
        // card says is payable to the driver (schema 26) is worked out from it below, as figures,
        // and the rate itself is not handed out.
        $rate = $j['km_rate'] !== null && $j['km_rate'] !== '' ? (float) $j['km_rate'] : null;
        $cur  = (string) ($j['currency'] ?: 'LKR');
        unset($j['km_rate'], $j['rate_id'], $j['currency']);
        $j['archived'] = !empty($j['archived_at']);
        $this->hydrateJob($j);
        $j['actuals'] = self::actualsSummary($j['legs']);
        $j['events'] = $this->db->query("SELECT * FROM tf_tour_event WHERE lane = 'transport' AND job_id = ? ORDER BY id DESC LIMIT 50", [$jobId])->getResultArray();
        // tf-crew-master-r1: the driver engaged brought their own LKR rate per km (copied onto the line the day they were
        // engaged): the hire payable to them is worked out on it; with none, on the class rate as before
        // 'special' — the driver's own agreed rate; 'general' — the Transport Rates master's rate for the vehicle's class the day
        // they were engaged; 'class' — the rate the quotation copied onto the card (a line engaged before the master, or with no class)
        $j['rate_source'] = 'class';
        foreach ($j['crew'] as $c) {
            if ((string) $c['kind'] === 'driver' && (string) $c['status'] === 'engaged' && isset($c['person_km_rate']) && $c['person_km_rate'] !== null && $c['person_km_rate'] !== '') {
                $rate = (float) $c['person_km_rate'];
                $cur  = 'LKR';
                $j['rate_source'] = in_array($c['person_rate_source'] ?? null, ['special', 'general'], true) ? $c['person_rate_source'] : 'driver';
                break;
            }
        }
        // Shantha, 5 Oct 2026: a driver replaced mid-tour — the days and the bata are split by each person's actual
        // dates (each line has its own dates and voucher); the vehicle hire is split by the kilometres actually driven
        // on each driver's days, each at the rate copied onto their own line, when every leg of those days has its
        // actual kilometres entered — otherwise the hire is not worked out and the card is flagged for a manual settlement
        $j['hire_split'] = $this->hireSplit($j, $rate, $cur);
        $this->hydrateDriverMoney($j, $rate, $cur);
        $j['money']['rate_source'] = $j['rate_source'];
        // tf-masters-r3: the master has a registration the card lacks (set under its type before the master had one)
        $j['master_reg'] = $this->vehicleMasterReg($j);
        $j['reg_is_type'] = $this->regIsType($j);
        return $j;
    }

    /** Every desk edit to a transport card refuses an archived one; the card comes back through Manage. */
    private function assertTransportLive(int $jobId): void
    {
        $r = $this->db->query('SELECT archived_at FROM tf_transport_job WHERE id = ?', [$jobId])->getRowArray();
        if ($r && !empty($r['archived_at'])) {
            throw new \RuntimeException('This card is archived. Management can restore it from the ⋯ Manage menu.');
        }
    }

    /** The vehicle master as the desk chooses from it. */
    public function vehicles(): array
    {
        try {
            if (!$this->db->query("SHOW TABLES LIKE 'vehicle'")->getResultArray()) {
                return [];
            }
            $rows = $this->db->query('SELECT id, type, passenger_count, v_num, owner, action FROM vehicle ORDER BY type, v_num')->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        return array_map(static fn ($r) => [
            'id' => (int) $r['id'], 'type' => trim((string) $r['type']) ?: 'Vehicle', 'seats' => (int) ($r['passenger_count'] ?? 0),
            'reg' => trim((string) ($r['v_num'] ?? '')), 'owner' => trim((string) ($r['owner'] ?? '')), 'flagged' => (int) ($r['action'] ?? 0) === 1,
        ], $rows);
    }

    /**
     * tf-masters-r3 (point 10): the registration number lives on the vehicle master (vehicle.v_num, "Vehicle Number" on
     * Master Files → Vehicle, the screen that has always kept it). TourFlow reads it — never writes it — and a card
     * allocated under the type alone takes the number when the desk presses "take the registration from the master".
     */
    /** The vehicle master's types by id, trimmed — read once, to tell a type standing in for a registration from a registration. */
    private function masterTypes(): array
    {
        if ($this->masterTypes === null) {
            $this->masterTypes = [];
            try {
                foreach ($this->db->query('SELECT id, type FROM vehicle')->getResultArray() as $v) {
                    $this->masterTypes[(int) $v['id']] = trim((string) $v['type']);
                }
            } catch (\Throwable $e) {
                // no vehicle master on this install
            }
        }
        return $this->masterTypes;
    }

    /** Whether a card's "registration" is really the vehicle's type — the master had no number when it was allocated. */
    public function regIsType(array $job): bool
    {
        $reg = trim((string) ($job['vehicle_reg'] ?? ''));
        if ($reg === '') {
            return false;
        }
        if (strcasecmp($reg, trim((string) ($job['vehicle_type'] ?? ''))) === 0) {
            return true;
        }
        $types = $this->masterTypes();
        return !empty($job['vehicle_id']) && isset($types[(int) $job['vehicle_id']]) && strcasecmp($reg, $types[(int) $job['vehicle_id']]) === 0;
    }

    /** The registration the master now has for a card's vehicle, when the card carries its type instead (the master had none at allocation). */
    public function vehicleMasterReg(array $job): ?string
    {
        if (empty($job['vehicle_id'])) {
            return null;
        }
        try {
            $v = $this->db->query('SELECT v_num FROM vehicle WHERE id = ?', [(int) $job['vehicle_id']])->getRowArray();
            $reg = trim((string) ($v['v_num'] ?? ''));
            return $reg !== '' && $reg !== trim((string) ($job['vehicle_reg'] ?? '')) ? $reg : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function jobIntake(int $jobId): array
    {
        $j = $this->db->query('SELECT j.id, j.status, j.tour_id, j.archived_at, t.intake_id, t.stage FROM tf_transport_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.id = ?', [$jobId])->getRowArray();
        if (!$j) {
            throw new \RuntimeException('Transport card not found.');
        }
        if ((string) $j['stage'] === 'cancelled') {
            throw new \RuntimeException('This tour is cancelled.');
        }
        if (!empty($j['archived_at'])) {
            throw new \RuntimeException('This card is archived. Management can restore it from the ⋯ Manage menu.');
        }
        return $j;
    }

    public function allocateVehicle(int $jobId, ?int $vehicleId, ?string $reg = null, ?string $note = null, ?string $changeReason = null): array
    {
        $j = $this->jobIntake($jobId);
        $regOut = self::cut($reg, 60);
        if ($vehicleId) {
            $v = null;
            foreach ($this->vehicles() as $row) {
                if ($row['id'] === $vehicleId) {
                    $v = $row;
                }
            }
            if (!$v) {
                throw new \RuntimeException('That vehicle is not in the vehicle master.');
            }
            if ($why = Blocks::workRefusalForSupplierAcc($this->db, $v['owner'] ?? null, 'vehicle allocation (' . ($j['tour_no'] ?? '') . ')', $this->user)) {   // tf-stage8: the vehicle's owner
                throw new \RuntimeException($why);
            }
            $regOut = $v['reg'] !== '' ? $v['reg'] : $v['type'];
        } elseif ($regOut === null) {
            throw new \RuntimeException('Choose a vehicle from the master, or type its registration.');
        }
        // tf-taxiq-r7 (Shantha, 4 Nov 2026): the vehicle was the one change that never took the completion mark
        // off — the tour went on reading "Transport work complete" with a different vehicle on it. It is a change
        // like any other: worked out before the first write, so a change needing a reason it has not been given is
        // refused with nothing altered.
        $cur = $this->db->query('SELECT vehicle_id, vehicle_reg FROM tf_transport_job WHERE id = ?', [$jobId])->getRowArray() ?: [];
        $vehicleChanged = (int) ($cur['vehicle_id'] ?? 0) !== (int) ($vehicleId ?: 0)
            || trim((string) ($cur['vehicle_reg'] ?? '')) !== trim((string) $regOut);
        $whyChanged = $vehicleChanged
            ? $this->changeReasonAfterFinal((int) $j['tour_id'], $changeReason, 'vehicle')
            : null;
        $this->db->transStart();
        $this->db->query('UPDATE tf_transport_job SET vehicle_id = ?, vehicle_reg = ?, vehicle_note = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$vehicleId ?: null, $regOut, self::cut($note, 255), $this->now(), $this->user, $jobId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'vehicle', null, null, 'Vehicle ' . $regOut . ($note ? ' — ' . $note : ''));
        if ($vehicleChanged) {
            $this->dropTransportMark((int) $j['tour_id'], $whyChanged);
        }
        $this->db->transComplete();
        $this->autoAllocate($jobId);
        $this->prepareTransportRequests((int) $j['tour_id']);
        return $this->transportJob($jobId);
    }

    public function engageCrew(int $crewId, ?string $supplierAcc, ?string $personName, $personBata = null, string $status = 'engaged', ?string $note = null, ?int $personId = null, bool $acknowledge = false, ?string $changeReason = null): array
    {
        $c = $this->db->query('SELECT * FROM tf_transport_crew WHERE id = ?', [$crewId])->getRowArray();
        if (!$c) {
            throw new \RuntimeException('Crew line not found.');
        }
        $j = $this->jobIntake((int) $c['job_id']);
        if (!in_array($status, ['to_engage', 'engaged', 'declined'], true)) {
            throw new \RuntimeException('Unknown crew status.');
        }
        if ((string) $c['status'] === 'replaced') {
            throw new \RuntimeException(($c['person_name'] ?: 'This person') . ' was replaced on this tour; their line is history. Engage or change the person on the current line.');
        }
        // tf-masters-r3: the person from the Drivers & Guides master brings their name, phone, ledger account and
        // own agreed bata with them; a name typed by hand still works, flagged on the card as not on the master
        $person = $this->crewPerson($personId);
        $phone  = null;
        $lineCat = CrewPeople::lineCategory((string) $c['role_code']);
        if ($person !== null) {
            if ($person['kind'] !== 'both' && $person['kind'] !== (string) $c['kind']) {
                throw new \RuntimeException($person['name'] . ' is a ' . $person['kind'] . ' on the master; this line is a ' . $c['kind'] . '.');
            }
            // tf-crew-master-r1: STRICT — a JSCG line takes a JSCG, a JSD line a JSD, a JSNTG line a JSNTG; nobody else
            if ($lineCat !== null && ($person['category'] ?? null) !== $lineCat) {
                throw new \RuntimeException($person['name'] . ' is ' . ($person['category'] ? 'a ' . $person['category'] : 'not categorised') . ' on the Driver & Guide Master; this line needs a ' . $lineCat . ' (' . CrewPeople::CATEGORIES[$lineCat]['name'] . ').');
            }
            // the clear warning before an allocation: an expired licence or document, a category still to confirm —
            // shown to the person engaging, who goes on only by saying so
            if ($status === 'engaged' && !$acknowledge && $person['warnings']) {
                throw new CrewWarning($person['name'] . ': ' . implode(' ', array_map(static fn ($w) => $w['text'], $person['warnings'])), $person['warnings']);
            }
            $name  = $person['name'];
            $acc   = $person['acc'] ?? self::cut($supplierAcc, 50);
            $phone = $person['phone'];
            if (($personBata === null || $personBata === '') && $person['bata'] !== null) {
                $personBata = $person['bata'];
            }
        } elseif ($lineCat !== null && $status === 'engaged' && $this->hasCrewPerson() && (new CrewPeople($this->db, $this->user))->masterReady()) {
            // a JSD / JSCG / JSNTG line is filled from the master, never by a typed name
            throw new \RuntimeException('This ' . $lineCat . ' line is filled from the Driver & Guide Master — pick the person from the list (add them to the master first if they are not on it).');
        } else {
            $name = self::cut($personName, 190);
            $acc  = self::cut($supplierAcc, 50);
            if ($acc !== null && $name === null) {
                $row = $this->db->query('SELECT Name FROM supplier WHERE Sup_Acc_No = ?', [$acc])->getRowArray();
                $name = $row ? self::cut($row['Name'], 190) : null;
            }
        }
        if ($status === 'engaged' && $name === null) {
            throw new \RuntimeException('Name the person engaged — from the Drivers & Guides master, or by hand.');
        }
        if ($status === 'engaged' && ($why = Blocks::workRefusalForSupplierAcc($this->db, $acc, 'driver / guide allocation (' . ($j['tour_no'] ?? '') . ')', $this->user))) {   // tf-stage8
            throw new \RuntimeException($why);
        }
        $bata = ($personBata === null || $personBata === '') ? null : round((float) $personBata, 2);
        // tf-taxiq-r5: the mark came off only when a line LEFT "engaged", so swapping one engaged driver for
        // another left the tour reading "Transport work complete" with a different person on it. A change of
        // person is as much a change as a release.
        // tf-taxiq-r7: worked out BEFORE the first write, so that a change needing a reason it has not been
        // given is refused with nothing altered at all.
        $wasPerson = $this->hasCrewPerson() && $c['person_id'] !== null ? (int) $c['person_id'] : null;
        $nowPerson = $this->hasCrewPerson() && $person !== null ? (int) ($person['id'] ?? 0) : null;
        $personChanged = $wasPerson !== null || $nowPerson !== null
            ? $wasPerson !== $nowPerson
            : trim((string) ($c['person_name'] ?? '')) !== trim((string) ($name ?? ''));
        $crewChanged = $status !== (string) $c['status'] || $personChanged;
        $whyChanged = $crewChanged
            ? $this->changeReasonAfterFinal((int) $j['tour_id'], $changeReason, (string) $c['kind'] === 'guide' ? 'guide' : 'driver')
            : null;
        $this->db->transStart();
        $this->db->query('UPDATE tf_transport_crew SET supplier_acc = ?, person_name = ?, person_bata = ?, status = ?, note = ?, updated_at = ?, updated_by = ?' . ($this->hasCrewPerson() ? ', person_id = ?, person_phone = ?' : '') . ' WHERE id = ?',
            array_merge([$acc, $name, $bata, $status, self::cut($note, 255), $this->now(), $this->user], $this->hasCrewPerson() ? [$person['id'] ?? null, $phone] : [], [$crewId]));
        // tf-crew-master-r1: the person's category and their rate per kilometre in force today are copied onto the
        // line — a rate changed on the master later never moves this tour
        if ($this->hasCrewRateColumns()) {
            $drv = $person !== null && (string) $c['kind'] === 'driver';
            $this->db->query('UPDATE tf_transport_crew SET person_category = ?, person_km_rate = ?, person_rate_from = ?, person_rate_source = ?, person_rate_class = ?, person_bata_from = ?, person_overnight = ?, person_bata_source = ?, person_overnight_from = ?, person_overnight_source = ? WHERE id = ?',
                array_merge([$person['category'] ?? null, $drv ? $person['km_rate'] : null, $drv ? $person['rate_from'] : null, $drv ? $person['rate_source'] : null, $drv ? $person['rate_class'] : null],
                    self::bataSnapshot($person, $bata), [$crewId]));
        }
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $c['job_id'], 'crew', (string) $c['status'], $status, trim($c['role_name'] . ': ' . ($name ?? 'nobody') . ($person ? ' (from the master)' : '') . ($note ? ' — ' . $note : '')));
        if ($status !== 'engaged' || $personChanged) {
            // a driver or guide no longer engaged, or a different person on the line: the desk's work is open again
            $this->dropTransportMark((int) $j['tour_id'], $whyChanged);
        }
        $this->db->transComplete();
        $this->autoAllocate((int) $c['job_id']);
        $this->prepareTransportRequests((int) $j['tour_id']);
        return $this->transportJob((int) $c['job_id']);
    }

    // =====================================================================
    //  Schema 24: the days each person works, a replacement mid-tour, a payment
    //  voucher per person for the days actually worked
    // =====================================================================

    private function assertCrewDated(): void
    {
        if (!$this->hasCrewDates()) {
            throw new \RuntimeException('The transport crew tables are not at the current version. Open Tour Inquiry and press Install first.');
        }
    }

    private static function dateOrNull($v): ?string
    {
        $v = trim((string) ($v ?? ''));
        if ($v === '') {
            return null;
        }
        $ts = strtotime($v);
        if ($ts === false || !preg_match('/^\d{4}-\d{2}-\d{2}$/', date('Y-m-d', $ts))) {
            throw new \InvalidArgumentException('That is not a date: ' . $v);
        }
        return date('Y-m-d', $ts);
    }

    /** The crew row with its job, refused when the tour is cancelled or the card archived. */
    private function crewRow(int $crewId): array
    {
        $c = $this->db->query('SELECT * FROM tf_transport_crew WHERE id = ?', [$crewId])->getRowArray();
        if (!$c) {
            throw new \RuntimeException('Crew line not found.');
        }
        $c['_job'] = $this->jobIntake((int) $c['job_id']);
        return $c;
    }

    /**
     * The days a person works on the tour — corrected by the desk from what the quotation
     * priced. A voucher already issued for other days is not touched: the card says so and
     * the desk cancels it and issues a fresh one.
     */
    public function crewDates(int $crewId, $from, $to, ?string $note = null): array
    {
        $this->assertCrewDated();
        $c = $this->crewRow($crewId);
        $f = self::dateOrNull($from);
        $t = self::dateOrNull($to);
        if ($f === null || $t === null) {
            throw new \InvalidArgumentException('Both dates are needed: the first and the last day worked.');
        }
        if ($t < $f) {
            throw new \InvalidArgumentException('The last day cannot come before the first.');
        }
        $this->db->transStart();
        $this->db->query('UPDATE tf_transport_crew SET date_from = ?, date_to = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$f, $t, $this->now(), $this->user, $crewId]);
        $days = self::daysBetween($f, $t);
        $this->event((int) $c['_job']['intake_id'], (int) $c['_job']['tour_id'], 'transport', (int) $c['job_id'], 'crew_dates', null, null,
            trim($c['role_name'] . ': ' . ($c['person_name'] ?: 'nobody yet') . ' works ' . $f . ' to ' . $t . ' (' . $days . ' day' . ($days === 1 ? '' : 's') . ')' . ($note ? ' — ' . self::cut($note, 255) : '')));
        $this->db->transComplete();
        return $this->transportJob((int) $c['job_id']);
    }

    /**
     * Replace an engaged driver or guide from a given day, mid-tour. The person going keeps
     * their row — status 'replaced', ended on their last day, pointing at who took over —
     * and the replacement gets a row of their own on the same card, same role, from that
     * day to the end the old row had. Both stay on the tour with their dates, each is paid
     * for their own days, and the trail says who, when and why. The vehicle is the card's
     * and is not touched: a change of vehicle is its own action with its own line.
     *
     * $d: date (the replacement's first day), last_day (the outgoing person's last day;
     *     the day before by default), supplier_acc / person_name, person_bata, note (why).
     */
    public function replaceCrew(int $crewId, array $d): array
    {
        $this->assertCrewDated();
        $c = $this->crewRow($crewId);
        $j = $c['_job'];
        if ((string) $c['status'] === 'replaced') {
            $by = !empty($c['replaced_by']) ? ($this->db->query('SELECT person_name FROM tf_transport_crew WHERE id = ?', [(int) $c['replaced_by']])->getRowArray()['person_name'] ?? null) : null;
            throw new \RuntimeException(($c['person_name'] ?: 'This person') . ' was already replaced' . ($by ? ' by ' . $by : '') . '. Replace the person on the current line instead.');
        }
        if ((string) $c['status'] !== 'engaged') {
            throw new \RuntimeException('Only an engaged driver or guide is replaced mid-tour. Nobody is engaged on this line yet — engage somebody, or change the name on the line.');
        }
        $date = self::dateOrNull($d['date'] ?? null);
        if ($date === null) {
            throw new \InvalidArgumentException('Say from which day the replacement takes over.');
        }
        $why = self::cut($d['note'] ?? null, 400);
        if ($why === null) {
            throw new \InvalidArgumentException('Say why the ' . $c['kind'] . ' is being replaced; it stays on the trail.');
        }
        $person = $this->crewPerson(isset($d['person_id']) && $d['person_id'] !== '' ? (int) $d['person_id'] : null);
        $phone  = null;
        $lineCat = CrewPeople::lineCategory((string) $c['role_code']);
        if ($person !== null) {
            if ($person['kind'] !== 'both' && $person['kind'] !== (string) $c['kind']) {
                throw new \InvalidArgumentException($person['name'] . ' is a ' . $person['kind'] . ' on the master; this line is a ' . $c['kind'] . '.');
            }
            // tf-crew-master-r1: the replacement is of the line's category too, and their warnings are seen first
            if ($lineCat !== null && ($person['category'] ?? null) !== $lineCat) {
                throw new \RuntimeException($person['name'] . ' is ' . ($person['category'] ? 'a ' . $person['category'] : 'not categorised') . ' on the Driver & Guide Master; this line needs a ' . $lineCat . '.');
            }
            if (empty($d['acknowledge']) && $person['warnings']) {
                throw new CrewWarning($person['name'] . ': ' . implode(' ', array_map(static fn ($w) => $w['text'], $person['warnings'])), $person['warnings']);
            }
            if ($this->hasCrewPerson() && (int) ($c['person_id'] ?? 0) === $person['id']) {
                throw new \InvalidArgumentException($person['name'] . ' is the person on the line already. A different person replaces them; to change their days, correct the dates.');
            }
            $acc   = $person['acc'] ?? self::cut($d['supplier_acc'] ?? null, 50);
            $name  = $person['name'];
            $phone = $person['phone'];
            if (($d['person_bata'] ?? '') === '' && $person['bata'] !== null) {
                $d['person_bata'] = $person['bata'];
            }
        } else {
            if ($lineCat !== null && $this->hasCrewPerson() && (new CrewPeople($this->db, $this->user))->masterReady()) {
                throw new \RuntimeException('This ' . $lineCat . ' line is filled from the Driver & Guide Master — pick the replacement from the list.');
            }
            $acc  = self::cut($d['supplier_acc'] ?? null, 50);
            $name = self::cut($d['person_name'] ?? null, 190);
            if ($acc !== null && $name === null) {
                $row  = $this->db->query('SELECT Name FROM supplier WHERE Sup_Acc_No = ?', [$acc])->getRowArray();
                $name = $row ? self::cut($row['Name'], 190) : null;
            }
        }
        if ($name === null) {
            throw new \InvalidArgumentException('Name the replacement — from the Driver & Guide Master, or by hand.');
        }
        if ($acc !== null && $acc === (string) $c['supplier_acc']) {
            throw new \InvalidArgumentException($name . ' is the person on the line already. A different person replaces them; to change their days, correct the dates.');
        }
        $bata = ($d['person_bata'] ?? '') === '' || $d['person_bata'] === null ? null : round((float) $d['person_bata'], 2);
        if ($bata !== null && $bata < 0) {
            throw new \InvalidArgumentException('A bata cannot be negative.');
        }
        if ($bata === null && $acc !== null) {
            // their own agreed bata, as the ledger holds it, unless the desk typed another
            try {
                $row  = $this->db->query('SELECT bata FROM supplier WHERE Sup_Acc_No = ?', [$acc])->getRowArray();
                $bata = ($row && $row['bata'] !== null && $row['bata'] !== '') ? round((float) $row['bata'], 2) : null;
            } catch (\Throwable $e) {
                $bata = null;   // an older ledger with no bata column: the master's figure is what is paid
            }
        }
        $tour = $this->db->query('SELECT arrival_date, departure_date FROM tf_tour WHERE id = ?', [(int) $j['tour_id']])->getRowArray() ?: [];

        // the outgoing person's days: from their start (the arrival if never set) to the day
        // before the replacement, unless the desk says another last day
        $oldFrom = $c['date_from'] ?: ($tour['arrival_date'] ?? null);
        $lastDay = self::dateOrNull($d['last_day'] ?? null) ?: date('Y-m-d', strtotime($date . ' -1 day'));
        if ($oldFrom !== null && $lastDay < date('Y-m-d', strtotime($oldFrom . ' -1 day'))) {
            $lastDay = date('Y-m-d', strtotime($oldFrom . ' -1 day'));   // never worked a day
        }
        // the replacement's days: from that day to the end the line had (the quoted end, or
        // the tour's departure when the line never had one)
        $newTo = $c['date_to'] ?: ($tour['departure_date'] ?? null);
        if ($newTo !== null && $newTo < $date) {
            $newTo = $date;
        }
        $newDays = self::daysBetween($date, $newTo);
        $rate    = ($c['bata'] === null || $c['bata'] === '') ? null : (float) $c['bata'];

        // tf-taxiq-r7: a replacement mid-tour is a change like any other — it never took the completion mark off.
        // The replacement note already says why, so it stands as the reason; checked before the first write.
        $whyChanged = $this->changeReasonAfterFinal((int) $j['tour_id'], $d['change_reason'] ?? $why,
            (string) $c['kind'] === 'guide' ? 'guide (replaced mid-tour)' : 'driver (replaced mid-tour)');

        $this->db->transStart();
        $this->db->query(
            'INSERT INTO tf_transport_crew (job_id, role_code, role_name, kind, language, days, bata, currency, amount, supplier_acc, person_name, person_bata, status, note, date_from, date_to, replaces, updated_at, updated_by' . ($this->hasCrewPerson() ? ', person_id, person_phone' : '') . ')
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?' . ($this->hasCrewPerson() ? ',?,?' : '') . ')',
            array_merge([
                (int) $c['job_id'], $c['role_code'], $c['role_name'], $c['kind'], $c['language'], $newDays, $c['bata'], $c['currency'] ?: 'LKR',
                ($newDays !== null && $rate !== null) ? round($newDays * $rate, 2) : null,
                $acc, $name, $bata, 'engaged', self::cut($d['line_note'] ?? null, 255), $date, $newTo, $crewId, $this->now(), $this->user,
            ], $this->hasCrewPerson() ? [$person['id'] ?? null, $phone] : [])
        );
        $newId = (int) $this->db->insertID();
        if ($this->hasCrewRateColumns() && $person !== null) {
            $drv = (string) $c['kind'] === 'driver';
            $this->db->query('UPDATE tf_transport_crew SET person_category = ?, person_km_rate = ?, person_rate_from = ?, person_rate_source = ?, person_rate_class = ?, person_bata_from = ?, person_overnight = ?, person_bata_source = ?, person_overnight_from = ?, person_overnight_source = ? WHERE id = ?',
                array_merge([$person['category'], $drv ? $person['km_rate'] : null, $drv ? $person['rate_from'] : null, $drv ? $person['rate_source'] : null, $drv ? $person['rate_class'] : null],
                    self::bataSnapshot($person, $bata), [$newId]));
        }
        $this->db->query(
            "UPDATE tf_transport_crew SET status = 'replaced', date_from = ?, date_to = ?, replaced_by = ?, replaced_at = ?, replaced_note = ?, updated_at = ?, updated_by = ? WHERE id = ?",
            [$oldFrom, $lastDay, $newId, $this->now(), $why, $this->now(), $this->user, $crewId]
        );
        $oldDays = self::daysBetween($oldFrom, $lastDay);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $c['job_id'], 'crew_replaced', 'engaged', 'replaced',
            $c['role_name'] . ': ' . ($c['person_name'] ?: 'the person engaged') . ' replaced by ' . $name . ' from ' . $date
            . ' (' . ($c['person_name'] ?: 'outgoing') . ($oldFrom ? ' ' . $oldFrom . ' to ' . $lastDay . ', ' . $oldDays . ' day' . ($oldDays === 1 ? '' : 's') : '')
            . '; ' . $name . ' ' . $date . ' to ' . ($newTo ?: '?') . ($newDays !== null ? ', ' . $newDays . ' day' . ($newDays === 1 ? '' : 's') : '') . ') — ' . $why);
        $this->dropTransportMark((int) $j['tour_id'], $whyChanged);
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('The replacement could not be recorded; nothing was changed.');
        }
        return $this->transportJob((int) $c['job_id']);
    }

    /** Every payment voucher on a card, issued and cancelled, newest first. */
    public function crewVouchers(int $jobId): array
    {
        if (!$this->hasCrewDates()) {
            return [];
        }
        return $this->db->query('SELECT * FROM tf_crew_voucher WHERE job_id = ? ORDER BY id DESC', [$jobId])->getResultArray();
    }

    private function nextCrewVoucherNo(): string
    {
        $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'crew_voucher_next'");
        $n = (int) $this->db->insertID();
        if ($n <= 0) {
            $this->db->query("INSERT INTO tf_meta (k, v) VALUES ('crew_voucher_next', '1001') ON DUPLICATE KEY UPDATE v = v");
            $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'crew_voucher_next'");
            $n = (int) $this->db->insertID();
        }
        return trim(($this->meta('crew_voucher_prefix') ?? 'CPV') . ' ' . ($n - 1));
    }

    /**
     * A payment voucher for one person, for the days actually worked: days × their own
     * agreed bata on the ledger, or the master's for the role when they have none. One
     * live voucher per person; a wrong one is cancelled with a note and a fresh one issued.
     * Nothing is guessed: no dates, no person or no bata means no voucher, and the message
     * says which.
     */
    public function issueCrewVoucher(int $crewId, ?string $note = null): array
    {
        $this->assertCrewDated();
        $c = $this->crewRow($crewId);
        $j = $c['_job'];
        if (!in_array((string) $c['status'], ['engaged', 'replaced'], true)) {
            throw new \RuntimeException('A voucher is issued to a person engaged on the tour, or replaced during it. Nobody is engaged on this line.');
        }
        if (self::cut($c['person_name'], 190) === null) {
            throw new \RuntimeException('The line names nobody. Engage the person first.');
        }
        if (empty($c['date_from']) || empty($c['date_to'])) {
            throw new \RuntimeException('Set the first and last day ' . $c['person_name'] . ' worked before issuing the voucher.');
        }
        $days = self::daysBetween($c['date_from'], $c['date_to']);
        if ($days === null || $days < 1) {
            throw new \RuntimeException($c['person_name'] . ' worked no day between ' . $c['date_from'] . ' and ' . $c['date_to'] . '; there is nothing to pay.');
        }
        $own    = ($c['person_bata'] === null || $c['person_bata'] === '') ? null : (float) $c['person_bata'];
        $master = ($c['bata'] === null || $c['bata'] === '') ? null : (float) $c['bata'];
        $rate   = $own ?? $master;
        if ($rate === null) {
            throw new \RuntimeException('No bata for ' . $c['person_name'] . ': type their own agreed bata on the line, or Admin sets the master\'s for ' . $c['role_name'] . ' under Master Files → Crew Bata.');
        }
        $live = $this->db->query("SELECT voucher_no FROM tf_crew_voucher WHERE crew_id = ? AND state = 'issued' LIMIT 1", [$crewId])->getRowArray();
        if ($live) {
            throw new \RuntimeException('Voucher ' . $live['voucher_no'] . ' is already issued to ' . $c['person_name'] . '. Cancel it first if the days or the bata changed, then issue a fresh one.');
        }
        $job = $this->db->query('SELECT vehicle_reg FROM tf_transport_job WHERE id = ?', [(int) $c['job_id']])->getRowArray() ?: [];
        $this->db->transStart();
        $no = $this->nextCrewVoucherNo();
        $amount = round($days * $rate, 2);
        $this->db->query(
            'INSERT INTO tf_crew_voucher (tour_id, job_id, crew_id, voucher_no, kind, role_code, role_name, supplier_acc, person_name, date_from, date_to, days, rate, rate_source, currency, amount, vehicle_reg, state, note, issued_at, issued_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                (int) $j['tour_id'], (int) $c['job_id'], $crewId, $no, $c['kind'], $c['role_code'], $c['role_name'], $c['supplier_acc'], $c['person_name'],
                $c['date_from'], $c['date_to'], $days, $rate, $own !== null ? 'person' : 'master', $c['currency'] ?: 'LKR', $amount,
                (string) $c['kind'] === 'driver' ? ($job['vehicle_reg'] ?? null) : null, 'issued', self::cut($note, 400), $this->now(), $this->user,
            ]
        );
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $c['job_id'], 'crew_voucher', null, null,
            $no . ': ' . $c['person_name'] . ' (' . $c['role_name'] . ') ' . $c['date_from'] . ' to ' . $c['date_to'] . ', ' . $days . ' day' . ($days === 1 ? '' : 's') . ' × ' . number_format($rate, 2) . ' = ' . ($c['currency'] ?: 'LKR') . ' ' . number_format($amount, 2)
            . ' (' . self::bataSourceText($c) . ')' . ($note ? ' — ' . self::cut($note, 255) : ''));
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('The voucher could not be issued; nothing was changed.');
        }
        return $this->transportJob((int) $c['job_id']);
    }

    /** A voucher is never edited or removed: it is cancelled with a note, and a fresh one issued. */
    public function cancelCrewVoucher(int $voucherId, ?string $why): array
    {
        $this->assertCrewDated();
        $v = $this->db->query('SELECT * FROM tf_crew_voucher WHERE id = ?', [$voucherId])->getRowArray();
        if (!$v) {
            throw new \RuntimeException('Voucher not found.');
        }
        $j = $this->jobIntake((int) $v['job_id']);
        if ((string) $v['state'] === 'cancelled') {
            throw new \RuntimeException('Voucher ' . $v['voucher_no'] . ' is cancelled already.');
        }
        $why = self::cut($why, 400);
        if ($why === null) {
            throw new \InvalidArgumentException('Say why voucher ' . $v['voucher_no'] . ' is cancelled; it stays on the trail.');
        }
        $this->db->transStart();
        $this->db->query("UPDATE tf_crew_voucher SET state = 'cancelled', cancelled_at = ?, cancelled_by = ?, cancel_note = ? WHERE id = ?", [$this->now(), $this->user, $why, $voucherId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $v['job_id'], 'crew_voucher_cancelled', 'issued', 'cancelled',
            $v['voucher_no'] . ' (' . $v['person_name'] . ', ' . $v['currency'] . ' ' . number_format((float) $v['amount'], 2) . ') cancelled — ' . $why);
        $this->db->transComplete();
        return $this->transportJob((int) $v['job_id']);
    }

    /** One voucher with everything its printed form needs: the tour, the card, the company's lines, who replaced whom. */
    public function crewVoucher(int $voucherId): array
    {
        $this->assertCrewDated();
        $v = $this->db->query(
            'SELECT v.*, t.tour_no, t.tour_name, t.agent_name, t.arrival_date, t.departure_date, t.pax_total, t.handler_name, t.intake_id, j.vehicle_type, j.seq AS vehicle_seq, j.vehicle_reg AS card_vehicle_reg
             FROM tf_crew_voucher v JOIN tf_tour t ON t.id = v.tour_id JOIN tf_transport_job j ON j.id = v.job_id WHERE v.id = ?',
            [$voucherId]
        )->getRowArray();
        if (!$v) {
            throw new \RuntimeException('Voucher not found.');
        }
        $c = $this->db->query('SELECT * FROM tf_transport_crew WHERE id = ?', [(int) $v['crew_id']])->getRowArray() ?: [];
        $rel = null;
        if (!empty($c['replaces'])) {
            $o = $this->db->query('SELECT person_name, date_from, date_to FROM tf_transport_crew WHERE id = ?', [(int) $c['replaces']])->getRowArray();
            $rel = $o ? 'Replaced ' . ($o['person_name'] ?: 'the previous ' . $v['kind']) . ($o['date_to'] ? ', whose last day was ' . $o['date_to'] : '') . '.' : null;
        } elseif (!empty($c['replaced_by'])) {
            $n = $this->db->query('SELECT person_name, date_from FROM tf_transport_crew WHERE id = ?', [(int) $c['replaced_by']])->getRowArray();
            $rel = $n ? 'Replaced by ' . ($n['person_name'] ?: 'another ' . $v['kind']) . ($n['date_from'] ? ' from ' . $n['date_from'] : '') . ($c['replaced_note'] ? ' — ' . $c['replaced_note'] : '') . '.' : null;
        }
        $sup = null;
        if (!empty($v['supplier_acc'])) {
            try {
                $sup = $this->db->query('SELECT Name, Address, TP, Mobile FROM supplier WHERE Sup_Acc_No = ?', [$v['supplier_acc']])->getRowArray() ?: null;
            } catch (\Throwable $e) {
                $sup = null;
            }
        }
        return [
            'voucher'  => $v,
            'crew'     => $c,
            'relation' => $rel,
            'supplier' => $sup,
            // tf-crew-master-r1: who is paid — nickname, legal name, category; the bank for the privilege
            'payee'    => $this->crewPayee(!empty($c['person_id']) ? (int) $c['person_id'] : null, $v['supplier_acc'] ?? null, $this->bankVisible),
            'company'  => [
                'name' => $this->meta('company_name') ?? 'Avenir Holidays (Pvt) Ltd', 'address' => $this->meta('company_address'),
                'phone' => $this->meta('company_phone'), 'email' => $this->meta('company_email'),
            ],
        ];
    }

    /** A vehicle and an engaged driver is what "allocated" means; the rung is taken the moment both are there. */
    private function autoAllocate(int $jobId): void
    {
        $j = $this->db->query('SELECT status, vehicle_reg FROM tf_transport_job WHERE id = ?', [$jobId])->getRowArray();
        if (!$j || (string) $j['status'] !== 'to_allocate' || trim((string) $j['vehicle_reg']) === '') {
            return;
        }
        $driver = $this->db->query("SELECT id FROM tf_transport_crew WHERE job_id = ? AND kind = 'driver' AND status = 'engaged' LIMIT 1", [$jobId])->getRowArray();
        if ($driver) {
            $this->transportStatus($jobId, 'allocated', null);
        }
    }

    /** One rung at a time. Forward needs the rung's condition met; back needs a note. */
    public function transportStatus(int $jobId, string $to, ?string $note = null, bool $hireByHand = false): array
    {
        $j = $this->jobIntake($jobId);
        $from = (string) $j['status'];
        if ($to === 'settled' && $from !== 'settled' && $this->hasDriverAdvance()) {
            // a driver replaced mid-tour with the kilometres of each driver's days not all entered: flagged for a manual
            // settlement — the step goes through only when the hire is settled by hand (as adjustment lines) and said so
            $card = $this->transportJob($jobId);
            if (!empty($card['money']['manual_hire'])) {
                if (!$hireByHand) {
                    throw new \RuntimeException($card['money']['why_no_hire'] . ' Enter the kilometres, or settle the hire by hand: add it as adjustment lines and tick "the vehicle hire is settled by hand" with a note.');
                }
                if (self::cut($note, 400) === null) {
                    throw new \RuntimeException('Say how the vehicle hire was settled by hand; the note stays on the settlement.');
                }
            }
        }
        if (!in_array($to, self::TRANSPORT_LADDER, true)) {
            throw new \RuntimeException('Unknown status.');
        }
        $fromRung = (int) array_search($from, self::TRANSPORT_LADDER, true);
        $toRung   = (int) array_search($to, self::TRANSPORT_LADDER, true);
        if ($toRung === $fromRung) {
            return $this->transportJob($jobId);
        }
        if ($toRung > $fromRung + 1) {
            throw new \RuntimeException('One step at a time: the next step from ' . str_replace('_', ' ', $from) . ' is ' . str_replace('_', ' ', self::TRANSPORT_LADDER[$fromRung + 1]) . '.');
        }
        if ($toRung < $fromRung) {
            if ($toRung < $fromRung - 1) {
                throw new \RuntimeException('Step back one rung at a time.');
            }
            if (self::cut($note, 400) === null) {
                throw new \RuntimeException('Say why the card goes back; the note stays on the trail.');
            }
        } else {
            $this->requireRung($jobId, $to);
        }
        $stamp = ['allocated' => 'allocated', 'scheduled' => 'scheduled', 'pack_issued' => 'pack', 'running' => 'running', 'returned' => 'returned', 'settled' => 'settled'][$to] ?? null;
        $sets  = ['status' => $to, 'updated_at' => $this->now(), 'updated_by' => $this->user];
        if ($stamp !== null && $toRung > $fromRung) {
            $sets["{$stamp}_at"] = $this->now();
            if ($stamp !== 'running') {
                $sets["{$stamp}_by"] = $this->user;
            }
        }
        $this->db->transStart();
        $this->db->query('UPDATE tf_transport_job SET ' . implode(', ', array_map(static fn ($k) => "`{$k}` = ?", array_keys($sets))) . ' WHERE id = ?', array_merge(array_values($sets), [$jobId]));
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'status', $from, $to, $note);
        if ($to === 'to_allocate') {
            // back to the start: the desk's work on this tour is not complete any more
            $this->dropTransportMark((int) $j['tour_id']);
        }
        $this->db->transComplete();
        if ($to === 'scheduled' && $toRung > $fromRung) {
            // the documents may all be out already: then the pack issues itself here and now
            $this->autoPack($jobId);
        }
        if ($to === 'settled' && $toRung > $fromRung) {
            // the driver's settlement is frozen with the step (schema 26)
            $this->freezeSettlement($jobId, $note);
        }
        return $this->transportJob($jobId);
    }

    private function requireRung(int $jobId, string $to): void
    {
        switch ($to) {
            case 'allocated':
                $j = $this->db->query('SELECT vehicle_reg FROM tf_transport_job WHERE id = ?', [$jobId])->getRowArray();
                if (trim((string) ($j['vehicle_reg'] ?? '')) === '') {
                    throw new \RuntimeException('Allocate a vehicle first.');
                }
                if (!$this->db->query("SELECT id FROM tf_transport_crew WHERE job_id = ? AND kind = 'driver' AND status = 'engaged' LIMIT 1", [$jobId])->getRowArray()) {
                    throw new \RuntimeException('Engage a driver first.');
                }
                break;
            case 'scheduled':
                if (!$this->db->query('SELECT id FROM tf_transport_leg WHERE job_id = ? LIMIT 1', [$jobId])->getRowArray()) {
                    throw new \RuntimeException('There is no schedule to check: add at least one leg.');
                }
                break;
            case 'pack_issued':
                $n = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_transport_doc WHERE job_id = ? AND phase = 'before' AND state = 'pending'", [$jobId])->getRowArray()['n'] ?? 0);
                if ($n > 0) {
                    throw new \RuntimeException("{$n} document(s) in the driver's pack are still pending. Mark each issued, or waived with a note.");
                }
                break;
            case 'returned':
                $n = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_transport_doc WHERE job_id = ? AND phase = 'after' AND state IN ('pending','issued')", [$jobId])->getRowArray()['n'] ?? 0);
                if ($n > 0) {
                    throw new \RuntimeException("{$n} document(s) have not come back yet. Mark each returned, or waived with a note.");
                }
                break;
            case 'settled':
                if ($this->hasDriverAdvance()) {
                    $open = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_driver_advance WHERE job_id = ? AND status IN ('requested','approved')", [$jobId])->getRowArray()['n'] ?? 0);
                    if ($open > 0) {
                        throw new \RuntimeException("{$open} driver advance(s) are still requested or approved but not paid. Record the payment, or cancel them with a note, before the card is settled.");
                    }
                }
                break;
        }
    }

    public function saveLeg(int $jobId, ?int $legId, array $d): array
    {
        $j = $this->jobIntake($jobId);
        $kinds = ['transfer', 'local', 'arrival', 'departure', 'positioning', 'idle', 'garage'];
        $kind  = in_array((string) ($d['kind'] ?? ''), $kinds, true) ? (string) $d['kind'] : 'transfer';
        $km    = ($d['km'] ?? '') === '' || $d['km'] === null ? null : round((float) $d['km'], 2);
        $extra = ($d['extra_km'] ?? '') === '' || !isset($d['extra_km']) ? null : round((float) $d['extra_km'], 2);
        $vals  = [
            (int) ($d['day_no'] ?? 0), self::cut($d['day_date'] ?? null, 10), (int) ($d['seq'] ?? 0), self::cut($d['from_place'] ?? null, 190), self::cut($d['to_place'] ?? null, 190),
            $km, $extra, $kind, self::cut($d['night_hotel'] ?? null, 190), self::cut($d['night_area'] ?? null, 160), self::cut($d['note'] ?? null, 255),
        ];
        $this->db->transStart();
        if ($legId) {
            $old = $this->db->query('SELECT * FROM tf_transport_leg WHERE id = ? AND job_id = ?', [$legId, $jobId])->getRowArray();
            if (!$old) {
                throw new \RuntimeException('Leg not found.');
            }
            $origin = (string) $old['origin'] === 'added' ? 'added' : 'changed';
            $this->db->query('UPDATE tf_transport_leg SET day_no = ?, day_date = ?, seq = ?, from_place = ?, to_place = ?, km = ?, extra_km = ?, kind = ?, night_hotel = ?, night_area = ?, note = ?, origin = ? WHERE id = ?', array_merge($vals, [$origin, $legId]));
            $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'leg', null, null, 'Day ' . (int) ($d['day_no'] ?? 0) . ' changed: ' . ($d['from_place'] ?? '') . ' → ' . ($d['to_place'] ?? ''));
        } else {
            $this->db->query('INSERT INTO tf_transport_leg (day_no, day_date, seq, from_place, to_place, km, extra_km, kind, night_hotel, night_area, note, origin, job_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, ['added', $jobId]));
            $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'leg', null, null, 'Day ' . (int) ($d['day_no'] ?? 0) . ' added: ' . ($d['from_place'] ?? '') . ' → ' . ($d['to_place'] ?? ''));
        }
        $this->retotal($jobId);
        $this->db->transComplete();
        return $this->transportJob($jobId);
    }

    public function deleteLeg(int $jobId, int $legId): array
    {
        $j = $this->jobIntake($jobId);
        $old = $this->db->query('SELECT * FROM tf_transport_leg WHERE id = ? AND job_id = ?', [$legId, $jobId])->getRowArray();
        if ($old) {
            $this->db->transStart();
            $this->db->query('DELETE FROM tf_transport_leg WHERE id = ?', [$legId]);
            $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'leg', null, null, 'Day ' . $old['day_no'] . ' removed: ' . $old['from_place'] . ' → ' . $old['to_place']);
            $this->retotal($jobId);
            $this->db->transComplete();
        }
        return $this->transportJob($jobId);
    }

    /** The schedule's own totals; the costed figures beside them never move. */
    private function retotal(int $jobId): void
    {
        $r = $this->db->query('SELECT SUM(km) km, SUM(extra_km) extra FROM tf_transport_leg WHERE job_id = ?', [$jobId])->getRowArray();
        $this->db->query('UPDATE tf_transport_job SET km_total = ?, extra_km_total = ?, amended = 0, updated_at = ?, updated_by = ? WHERE id = ?', [$r['km'] ?? null, $r['extra'] ?? null, $this->now(), $this->user, $jobId]);
        $this->retotalActual($jobId);
    }

    /** The kilometres actually driven so far, added up over the legs that have them. */
    private function retotalActual(int $jobId): void
    {
        if (!$this->hasActualLegs()) {
            return;
        }
        try {
            $r = $this->db->query('SELECT SUM(actual_km) a, COUNT(actual_km) n FROM tf_transport_leg WHERE job_id = ?', [$jobId])->getRowArray();
            $this->db->query('UPDATE tf_transport_job SET actual_km_total = ? WHERE id = ?', [((int) ($r['n'] ?? 0)) > 0 ? $r['a'] : null, $jobId]);
        } catch (\Throwable $e) {
            // an older job table: the legs still carry their figures
        }
    }

    /**
     * The kilometres actually driven on one leg, entered after the tour from the driver's
     * log sheet. Into the leg's own actual column — Google's figure and the costed one are
     * never overwritten — and from then on part of the driven record every later
     * quotation reads (MileageLookup). Blank clears it. The costed kilometres on the
     * quotation do not change.
     */
    public function saveLegActual(int $jobId, int $legId, $km, ?string $note = null): array
    {
        $j = $this->jobIntake($jobId);
        if (!$this->hasActualLegs()) {
            throw new \RuntimeException('The transport legs are not at the current version. Open Tour Inquiry and press Install first.');
        }
        $leg = $this->db->query('SELECT * FROM tf_transport_leg WHERE id = ? AND job_id = ?', [$legId, $jobId])->getRowArray();
        if (!$leg) {
            throw new \RuntimeException('Leg not found.');
        }
        if (in_array((string) $leg['kind'], ['garage', 'idle'], true) || ((string) $leg['kind'] === 'local' && strcasecmp(trim((string) $leg['from_place']), trim((string) $leg['to_place'])) === 0)) {
            throw new \InvalidArgumentException('That leg is an allowance, not a journey between two places — the actual kilometres go on the driven legs.');
        }
        $typed = $km !== null && $km !== '';
        if ($typed && (!is_numeric($km) || (float) $km < 0)) {
            throw new \InvalidArgumentException('The actual mileage has to be a number of kilometres, nought or more.');
        }
        $val = $typed ? round((float) $km, 2) : null;
        $this->db->transStart();
        $this->db->query(
            'UPDATE tf_transport_leg SET actual_km = ?, actual_by = ?, actual_at = ?, actual_note = ? WHERE id = ?',
            [$val, $typed ? self::cut($this->user ?: 'a colleague', 100) : null, $typed ? $this->now() : null, $typed ? self::cut($note, 255) : null, $legId]
        );
        $quoted = $leg['km'] === null ? 'no costed figure' : 'costed at ' . rtrim(rtrim(number_format((float) $leg['km'], 1, '.', ''), '0'), '.') . ' km';
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'actual_km', null, null,
            'Day ' . (int) $leg['day_no'] . ' ' . $leg['from_place'] . ' → ' . $leg['to_place'] . ': '
            . ($typed ? 'actually driven ' . rtrim(rtrim(number_format($val, 1, '.', ''), '0'), '.') . ' km (' . $quoted . ')' : 'the actual kilometres were cleared')
            . ($typed && self::cut($note, 255) !== null ? ' — ' . self::cut($note, 255) : ''));
        $this->retotalActual($jobId);
        $this->db->transComplete();
        return $this->transportJob($jobId);
    }

    public function docState(int $docId, string $state, ?string $note = null): array
    {
        $d = $this->db->query('SELECT * FROM tf_transport_doc WHERE id = ?', [$docId])->getRowArray();
        if (!$d) {
            throw new \RuntimeException('Document not found.');
        }
        if (!in_array($state, ['pending', 'issued', 'returned', 'accepted', 'waived'], true)) {
            throw new \RuntimeException('Unknown document state.');
        }
        if ($state === 'waived' && self::cut($note, 255) === null) {
            throw new \RuntimeException('Waiving a document needs a note saying why.');
        }
        $j = $this->jobIntake((int) $d['job_id']);
        $this->db->transStart();
        $this->db->query('UPDATE tf_transport_doc SET state = ?, `at` = ?, `by` = ?, note = ? WHERE id = ?', [$state, $this->now(), $this->user, self::cut($note, 255), $docId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $d['job_id'], 'document', (string) $d['state'], $state, $d['doc_name'] . ($note ? ' — ' . $note : ''));
        $this->db->transComplete();
        if ((string) $d['phase'] === 'before' && in_array($state, ['issued', 'waived'], true)) {
            $this->autoPack((int) $d['job_id']);
        }
        return $this->transportJob((int) $d['job_id']);
    }

    // =====================================================================
    //  The driver's pack: what is handed over, generated from the record where it can be
    // =====================================================================

    /**
     * Every "before" document issued or waived while the card stands at Scheduled: the pack
     * issues itself — "Pack issued", stamped with who handed over the last document and when,
     * and a line on the trail. Nothing to do while a document is still pending, while the
     * card is not yet at Scheduled (it takes the step the moment the schedule is checked), or
     * once the pack is out.
     */
    private function autoPack(int $jobId): void
    {
        $j = $this->db->query('SELECT status FROM tf_transport_job WHERE id = ?', [$jobId])->getRowArray();
        if (!$j || (string) $j['status'] !== 'scheduled') {
            return;
        }
        $of      = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_transport_doc WHERE job_id = ? AND phase = 'before'", [$jobId])->getRowArray()['n'] ?? 0);
        $pending = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_transport_doc WHERE job_id = ? AND phase = 'before' AND state = 'pending'", [$jobId])->getRowArray()['n'] ?? 0);
        if ($of === 0 || $pending > 0) {
            return;
        }
        $this->transportStatus($jobId, 'pack_issued', 'Every document in the driver\'s pack is issued or waived — the pack issued itself.');
    }

    /** A card with no list at all (made before the list existed) takes the master's active list, as a new card does. */
    private function backfillDocs(int $jobId): void
    {
        foreach ($this->db->query('SELECT code, name, phase FROM tf_doc_type WHERE active = 1 ORDER BY sort_order, code')->getResultArray() as $t) {
            $this->db->query('INSERT IGNORE INTO tf_transport_doc (job_id, doc_code, doc_name, phase, state) VALUES (?,?,?,?,?)', [$jobId, $t['code'], $t['name'], $t['phase'], 'pending']);
        }
    }

    /**
     * Which documents the record can produce by itself, so nothing is typed twice: the
     * itinerary copy (the programme sheet), the hotel list, the allowance sheet and the log
     * sheet. By the master's seeded code first, then by the name a person gave the entry, so
     * a list entry renamed or re-added still gets its generator; anything else is prepared
     * by hand and only marked here.
     */
    public static function packGenerator(array $doc): ?string
    {
        $byCode = ['ITIN' => 'itinerary', 'HOTELS' => 'hotels', 'ALLOW' => 'allowance', 'LOG' => 'log', 'SETTLE' => 'settlement', 'ROUTE' => 'route'];   // tf-route-map-r1: the driver's route map
        $code   = strtoupper(trim((string) ($doc['doc_code'] ?? '')));
        if (isset($byCode[$code])) {
            return $byCode[$code];
        }
        $name = ' ' . strtolower(trim((string) ($doc['doc_name'] ?? ''))) . ' ';
        foreach (['itinerar' => 'itinerary', 'programme' => 'itinerary', 'program ' => 'itinerary', 'hotel' => 'hotels', 'allowance' => 'allowance', 'bata' => 'allowance', 'log' => 'log', 'settle' => 'settlement', 'route' => 'route', 'map' => 'route'] as $word => $kind) {
            if (str_contains($name, $word)) {
                return $kind;
            }
        }
        return null;
    }

    /**
     * The pack as the card shows it, right under the steps: each document before and after
     * with what the record can generate for it, the count handed over, whether the pack is
     * ready to issue, and who issued it when.
     */
    private function packSummary(array $j): array
    {
        $before = [];
        $after  = [];
        foreach ($j['docs'] ?? [] as $d) {
            $d['generate'] = self::packGenerator($d);
            if ((string) $d['phase'] === 'after') {
                $after[] = $d;
            } else {
                $before[] = $d;
            }
        }
        $done      = count(array_filter($before, static fn ($d) => in_array((string) $d['state'], ['issued', 'waived'], true)));
        $back      = count(array_filter($after, static fn ($d) => in_array((string) $d['state'], ['returned', 'accepted', 'waived'], true)));
        $issued    = !empty($j['pack_at']) && in_array((string) ($j['status'] ?? ''), ['pack_issued', 'running', 'returned', 'settled'], true);
        return [
            'before'      => $before,
            'after'       => $after,
            'before_done' => $done,
            'before_of'   => count($before),
            'after_done'  => $back,
            'after_of'    => count($after),
            'ready'       => count($before) > 0 && $done === count($before),
            'issued'      => $issued,
            'issued_at'   => $issued ? $j['pack_at'] : null,
            'issued_by'   => $issued ? $j['pack_by'] : null,
            // a document put back to pending after the pack went out: said, so the card is not quietly wrong
            'reopened'    => $issued ? count(array_filter($before, static fn ($d) => (string) $d['state'] === 'pending')) : 0,
        ];
    }

    /**
     * One document of the pack, generated from the record for a page to print or a PDF:
     * the data every kind needs and the part its own kind adds. Nothing on any of them is
     * a price the desk does not already see: bata on the allowance sheet is the desk's own.
     *
     * @return array<string, mixed>
     */
    public function packDocument(int $jobId, string $code): array
    {
        $j    = $this->transportJob($jobId);
        $doc  = null;
        foreach ($j['docs'] as $d) {
            if (strcasecmp((string) $d['doc_code'], $code) === 0) {
                $doc = $d;
                break;
            }
        }
        $kind = self::packGenerator($doc ?? ['doc_code' => $code, 'doc_name' => $code]);
        if ($kind === null) {
            throw new \RuntimeException('"' . ($doc['doc_name'] ?? $code) . '" is prepared by hand — there is nothing to generate for it; mark it issued once it is handed over.');
        }
        $details = $this->details((int) $j['tour_id']) ?? [];
        $company = [];
        foreach (['company_name', 'company_address', 'company_phone', 'company_email'] as $k) {
            $company[substr($k, 8)] = $this->meta($k);
        }
        $out = [
            'kind'    => $kind,
            'code'    => $doc['doc_code'] ?? strtoupper($code),
            'title'   => $doc['doc_name'] ?? ['itinerary' => 'Itinerary copy', 'hotels' => 'Hotel list', 'allowance' => 'Allowance sheet', 'log' => 'Log sheet', 'settlement' => 'Settlement', 'route' => 'Route map'][$kind],
            'doc'     => $doc,
            'job'     => $j,
            'details' => $details,
            'company' => $company,
            'driver'  => $this->personOn($j, 'driver'),
            'guide'   => $this->personOn($j, 'guide'),
        ];
        if ($kind === 'hotels') {
            $out['stays'] = $this->staysForDriver((int) $j['tour_id']);
        } elseif ($kind === 'allowance') {
            $out['allowance'] = $this->allowanceSheet($j);
        } elseif ($kind === 'route') {
            // tf-route-map-r1: the driver's route map — the card's saved legs on a map, with the planned mileage day by day; read only
            $out['route'] = (new RouteMap($this->db))->build($j, $details, $company, $out['driver']);
        }
        return $out;
    }

    /** The person on the job now for a kind (driver or guide): engaged, not replaced; null when nobody. */
    private function personOn(array $j, string $kind): ?array
    {
        $on = null;
        foreach ($j['crew'] ?? [] as $c) {
            if ((string) $c['kind'] === $kind && (string) $c['status'] === 'engaged' && trim((string) ($c['person_name'] ?? '')) !== '') {
                $on = $c;
            }
        }
        return $on;
    }

    /**
     * The hotel list for the driver: every stay as the Hotel desk holds it, in order, with
     * the hotel's own address and telephone from its supplier record (nothing invented when
     * there is none), the meal plan, the rooms, the crew rooms, and where the booking stands.
     */
    private function staysForDriver(int $tourId): array
    {
        $out = [];
        foreach ($this->db->query('SELECT * FROM tf_hotel_job WHERE tour_id = ? AND archived_at IS NULL ORDER BY seq, id', [$tourId])->getResultArray() as $h) {
            $contact = $this->hotelContact($h);
            $out[] = [
                'day_from' => (int) $h['day_from'], 'day_to' => (int) $h['day_to'], 'nights' => (int) $h['nights'],
                'check_in' => $h['check_in'], 'check_out' => $h['check_out'],
                'hotel' => $h['hotel_name'] ?: ($contact['name'] ?? null), 'area' => $h['area'],
                'address' => $contact['address'], 'phone' => $contact['phone'], 'mobile' => $contact['mobile'], 'contact' => $contact['contact'],
                'meal_plan' => $h['meal_plan'], 'rooms' => $h['rooms'], 'rooms_note' => $h['rooms_note'], 'crew_rooms' => $h['crew_rooms'],
                'status' => $h['status'], 'confirmation_ref' => $h['confirmation_ref'], 'confirmed_with' => $h['confirmed_with'], 'voucher_no' => $h['voucher_no'],
                'special_note' => $h['special_note'], 'problem_note' => $h['problem_note'],
                'needs_hotel' => (int) $h['needs_hotel'],
            ];
        }
        return $out;
    }

    /** A hotel's own contact lines from its supplier record, by the code the stay carries or the panel hotel's primary supplier. */
    private function hotelContact(array $h): array
    {
        $acc = trim((string) ($h['hotel_acc'] ?? ''));
        if ($acc === '' && !empty($h['hotel_id'])) {
            try {
                $acc = trim((string) ($this->db->query('SELECT primary_sup_acc_no FROM hr2_hotel WHERE id = ?', [(int) $h['hotel_id']])->getRowArray()['primary_sup_acc_no'] ?? ''));
            } catch (\Throwable $e) {
                $acc = '';
            }
        }
        $out = ['name' => null, 'acc' => $acc !== '' ? $acc : null, 'address' => null, 'phone' => null, 'mobile' => null, 'email' => null, 'contact' => null];
        if ($acc !== '') {
            try {
                $sup = $this->db->query('SELECT Name, Address, TP, Mobile, email, rep_offce FROM supplier WHERE Sup_Acc_No = ? LIMIT 1', [$acc])->getRowArray();
                if ($sup) {
                    // a supplier record's "0", "-" or "N/A" is an empty field, not a phone number
                    $clean = static fn ($v) => trim((string) ($v ?? '')) !== '' && !in_array(strtoupper(trim((string) $v)), ['0', '-', 'N/A', 'NA', 'NIL', 'NONE', 'NULL'], true) ? trim((string) $v) : null;
                    $out['name']    = $clean($sup['Name'] ?? null);
                    $out['address'] = $clean($sup['Address'] ?? null);
                    $out['phone']   = $clean($sup['TP'] ?? null);
                    $out['mobile']  = $clean($sup['Mobile'] ?? null);
                    $out['email']   = $clean($sup['email'] ?? null);
                    $out['contact'] = $clean($sup['rep_offce'] ?? null);
                }
            } catch (\Throwable $e) {
                // a supplier table this install lacks: the list goes without those lines
            }
        }
        if (($out['address'] === null || $out['phone'] === null) && !empty($h['hotel_id'])) {
            // the panel hotel's own lines, where the supplier record has none
            try {
                $ph = $this->db->query('SELECT address, phone, reservations_phone, check_in_time, check_out_time FROM hr2_hotel WHERE id = ?', [(int) $h['hotel_id']])->getRowArray();
                if ($ph) {
                    $out['address'] = $out['address'] ?? (trim((string) $ph['address']) !== '' ? trim((string) $ph['address']) : null);
                    $out['phone']   = $out['phone'] ?? (trim((string) ($ph['phone'] ?: $ph['reservations_phone'])) !== '' ? trim((string) ($ph['phone'] ?: $ph['reservations_phone'])) : null);
                }
            } catch (\Throwable $e) {
                // no panel on this install
            }
        }
        return $out;
    }

    /**
     * The allowance sheet: each person on the card with the days they work and the bata a
     * day (their own agreed figure, else the master's), the total for those days, and the
     * payment voucher if one is issued; the crew rooms the quotation allowed for, as nights;
     * the kilometres of the schedule and the allowance legs. Figures the desk already sees
     * on the card — nothing from the quotation's costing.
     */
    private function allowanceSheet(array $j): array
    {
        $people = [];
        foreach ($j['crew'] ?? [] as $c) {
            if ((string) $c['status'] === 'declined' || trim((string) ($c['person_name'] ?? '')) === '') {
                continue;
            }
            $rate   = $c['person_bata'] !== null && $c['person_bata'] !== '' ? (float) $c['person_bata'] : ($c['bata'] !== null && $c['bata'] !== '' ? (float) $c['bata'] : null);
            $days   = $c['days_worked'] ?? null;
            $days   = $days !== null ? (int) $days : ($c['days'] !== null ? (int) $c['days'] : null);
            $v      = $c['voucher'] ?? null;   // a payment voucher issued: the sheet says what the voucher says, as the card's payable does
            $people[] = [
                'kind' => $c['kind'], 'role' => $c['role_name'], 'language' => $c['language'], 'name' => $c['person_name'], 'acc' => $c['supplier_acc'],
                'status' => $c['status'], 'date_from' => $c['date_from'] ?? null, 'date_to' => $c['date_to'] ?? null,
                'days' => $v ? (int) $v['days'] : $days, 'days_source' => $v ? 'voucher' : ((($c['days_worked'] ?? null) !== null) ? 'worked' : 'quoted'),
                'rate' => $v ? (float) $v['rate'] : $rate, 'rate_source' => $v ? ($v['rate_source'] === 'person' ? 'own' : 'master') : ($c['person_bata'] !== null && $c['person_bata'] !== '' ? 'own' : ($rate !== null ? 'master' : null)),
                'currency' => $v ? ($v['currency'] ?? 'LKR') : ($c['currency'] ?? 'LKR'),
                'total' => $v ? (float) $v['amount'] : ($rate !== null && $days !== null ? round($rate * $days, 2) : null),
                'rate_basis' => $rate !== null ? self::bataSourceText($c) : null,
                'voucher' => $v,
            ];
        }
        $rooms = [];
        try {
            foreach ($this->db->query("SELECT kind, required, nights, complimentary_nights, note FROM tf_intake_accom WHERE intake_id = ? AND day_no = 0", [(int) $j['intake_id']])->getResultArray() as $a) {
                $rooms[] = ['kind' => $a['kind'], 'required' => (int) $a['required'], 'nights' => $a['nights'] !== null ? (int) $a['nights'] : null, 'complimentary' => (int) $a['complimentary_nights'], 'note' => $a['note']];
            }
        } catch (\Throwable $e) {
            $rooms = [];
        }
        $allowanceLegs = [];
        foreach ($j['legs'] ?? [] as $l) {
            $driven = !in_array((string) $l['kind'], ['garage', 'idle'], true) && !((string) $l['kind'] === 'local' && strcasecmp(trim((string) $l['from_place']), trim((string) $l['to_place'])) === 0);
            if (!$driven && $l['km'] !== null) {
                $allowanceLegs[] = ['day_no' => (int) $l['day_no'], 'day_date' => $l['day_date'], 'kind' => $l['kind'], 'from' => $l['from_place'], 'to' => $l['to_place'], 'km' => (float) $l['km']];
            }
        }
        return [
            'people' => $people,
            'rooms'  => $rooms,
            'km'     => ['total' => $j['km_total'] !== null ? (float) $j['km_total'] : null, 'extra' => $j['extra_km_total'] !== null ? (float) $j['extra_km_total'] : null, 'days' => $j['days'] !== null ? (int) $j['days'] : null],
            'allowance_legs' => $allowanceLegs,
        ];
    }

    public function transportNote(int $jobId, ?string $note): array
    {
        $j = $this->jobIntake($jobId);
        $act = $this->intake()->activity();
        $before = $act->snapshotTransportJob($jobId);
        $this->db->query('UPDATE tf_transport_job SET note = ?, amended = 0, updated_at = ?, updated_by = ? WHERE id = ?', [self::cut($note, 500), $this->now(), $this->user, $jobId]);
        $act->logDiff('transport_job', $jobId, (int) $j['intake_id'], (int) $j['tour_id'], 'note', $before, $act->snapshotTransportJob($jobId));
        return $this->transportJob($jobId);
    }

    // =====================================================================
    //  The driver's advance and settlement (schema 26)
    // =====================================================================

    private ?bool $hasDriverAdvance = null;

    /** Whether this install has the advance tables (schema 26). */
    public function hasDriverAdvance(): bool
    {
        if ($this->hasDriverAdvance === null) {
            try {
                $this->hasDriverAdvance = (bool) $this->db->query("SHOW TABLES LIKE 'tf_driver_advance'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasDriverAdvance = false;
            }
        }
        return $this->hasDriverAdvance;
    }

    public const ADVANCE_METHODS = ['cash', 'bank', 'cheque', 'other'];

    /** The percentages the card offers, from the setting (Admin → Settings), never fixed in code. */
    public function advancePercentages(): array
    {
        $out = [];
        foreach (preg_split('/[,\s]+/', (string) ($this->meta('driver_advance_pcts') ?? '20, 30, 40, 50')) ?: [] as $p) {
            if ($p !== '' && is_numeric($p) && (float) $p > 0 && (float) $p <= 100) {
                $out[] = (float) $p;
            }
        }
        return $out ?: [20.0, 30.0, 40.0, 50.0];
    }

    /**
     * What the card says is payable to the driver, from what the card already holds:
     *
     *   hire  — the vehicle hire: the kilometres (charged plus the local running) × the rate per
     *           kilometre copied onto the card at Confirm × the units. As costed (the quotation's
     *           figure), as scheduled (the legs on the card), and on the kilometres actually
     *           driven once every driven leg has them — the settlement takes the actual when it
     *           is there, else the schedule, else the costed figure; the advance takes the costed.
     *   bata  — the driver's days × bata a day (own agreed, else the master's); from the payment
     *           voucher issued for the days actually worked once there is one.
     *
     * No rate is handed out — the figures are. A card with no rate per kilometre says so, and
     * the payable is the bata alone.
     */
    private function hydrateDriverMoney(array &$j, ?float $rate, string $cur): void
    {
        $qty   = max(1, (int) ($j['qty'] ?? 1));
        $km    = static fn ($a, $b) => ($a === null || $a === '') ? null : round((float) $a + (float) ($b ?? 0), 1);
        $kmCosted    = $km($j['km_quoted'] ?? null, $j['extra_km_quoted'] ?? null);
        $kmScheduled = $km($j['km_total'] ?? null, $j['extra_km_total'] ?? null);
        $actualsFull = !empty($j['actuals']['has_columns']) && (int) ($j['actuals']['driven_legs'] ?? 0) > 0 && (int) ($j['actuals']['missing'] ?? 1) === 0;
        $kmActual    = $actualsFull ? $km($j['actual_km_total'] ?? null, $j['extra_km_total'] ?? null) : null;
        $hireOf = static fn (?float $k) => $rate !== null && $k !== null ? round($k * $rate * $qty, 2) : null;
        $bata  = [];
        $bataT = 0.0;
        foreach ($j['crew'] ?? [] as $c) {
            if ((string) $c['kind'] !== 'driver' || !in_array((string) $c['status'], ['engaged', 'replaced'], true) || trim((string) ($c['person_name'] ?? '')) === '') {
                continue;
            }
            $v = $c['voucher'] ?? null;
            $r = $c['person_bata'] !== null && $c['person_bata'] !== '' ? (float) $c['person_bata'] : ($c['bata'] !== null && $c['bata'] !== '' ? (float) $c['bata'] : null);
            $d = ($c['days_worked'] ?? null) !== null ? (int) $c['days_worked'] : ($c['days'] !== null ? (int) $c['days'] : null);
            $line = [
                'crew_id' => (int) $c['id'], 'name' => $c['person_name'], 'acc' => $c['supplier_acc'], 'status' => $c['status'],
                'days' => $v ? (int) $v['days'] : $d, 'rate' => $v ? (float) $v['rate'] : $r,
                'amount' => $v ? (float) $v['amount'] : ($r !== null && $d !== null ? round($r * $d, 2) : null),
                'source' => $v ? 'voucher ' . $v['voucher_no'] : ($r === null ? 'no bata' : ($d === null ? 'days not set' : (($c['days_worked'] ?? null) !== null ? 'days on the card' : 'days as quoted'))),
                'rate_basis' => $r !== null ? self::bataSourceText($c) : null,
            ];
            if ($line['amount'] !== null) {
                $bataT += $line['amount'];
            }
            $bata[] = $line;
        }
        $hireCosted = $hireOf($kmCosted);
        $hireSched  = $hireOf($kmScheduled);
        $hireActual = $hireOf($kmActual);
        $split  = $j['hire_split'] ?? null;
        $manual = false;
        $whyNoHire = $rate === null ? 'No rate per kilometre on this card, so the vehicle hire cannot be worked out; the payable is the bata alone.' : ($kmCosted === null ? 'No costed kilometres on this card.' : null);
        if ($split !== null) {
            if ($split['known']) {
                // the hire split by each driver's actual kilometres at their own copied rate: that is the hire, on actual
                $hireActual = $split['hire'];
                $kmActual   = $split['km'];
            } else {
                // flagged for a manual settlement: no hire is worked out on any basis until the kilometres are in
                $hireCosted = $hireSched = $hireActual = null;
                $manual     = true;
                $whyNoHire  = $split['why'];
            }
        }
        $basis = $hireActual !== null ? ($split !== null ? 'split' : 'actual') : ($hireSched !== null ? 'scheduled' : ($hireCosted !== null ? 'costed' : ($manual ? 'manual' : 'costed')));
        $hireNow = $hireActual ?? $hireSched ?? $hireCosted;
        $j['money'] = [
            'currency'   => $cur,
            'has_rate'   => $rate !== null,
            'qty'        => $qty,
            'hire'       => ['costed' => $hireCosted, 'scheduled' => $hireSched, 'actual' => $hireActual, 'km_costed' => $kmCosted, 'km_scheduled' => $kmScheduled, 'km_actual' => $kmActual, 'now' => $hireNow, 'basis' => $basis],
            'split'      => $split,
            'manual_hire' => $manual,
            'bata'       => ['lines' => $bata, 'total' => round($bataT, 2)],
            // the advance base: the costed hire plus the bata — on a card whose driver was replaced mid-tour, the split hire
            // (there is no one costed figure for two drivers on two rates)
            'payable_before' => round((($split !== null && $split['known'] ? $split['hire'] : $hireCosted) ?? 0) + $bataT, 2),
            'payable_now'    => round(($hireNow ?? 0) + $bataT, 2),
            'why_no_hire'    => $whyNoHire,
        ];
        $j['advances']    = [];
        $j['adjustments'] = [];
        $j['settlement']  = null;
        $j['advance_pcts'] = $this->advancePercentages();
        if (!$this->hasDriverAdvance()) {
            return;
        }
        $j['advances'] = $this->db->query('SELECT * FROM tf_driver_advance WHERE job_id = ? ORDER BY id', [(int) $j['id']])->getResultArray();
        $j['adjustments'] = $this->db->query('SELECT * FROM tf_driver_adjustment WHERE job_id = ? AND removed_at IS NULL ORDER BY id', [(int) $j['id']])->getResultArray();
        $frozen = $this->db->query('SELECT * FROM tf_driver_settlement WHERE job_id = ?', [(int) $j['id']])->getRowArray();
        $j['settlement'] = $frozen ? $this->settlementFromRow($frozen) : $this->settlementLive($j);
    }

    /**
     * Shantha, 5 Oct 2026 — a driver replaced mid-tour: the vehicle hire split by the kilometres ACTUALLY driven on
     * each driver's days (the legs dated within their first and last day), each segment at the rate copied onto that
     * driver's own line (their special rate, else the general rate of their class, else the card's class rate — as
     * the line was engaged). Known only when every driven leg of every segment has its actual kilometres entered and
     * every driven leg falls within somebody's days; otherwise the card is flagged for a manual settlement and no hire
     * is worked out on any basis. Null when nobody was replaced (the ordinary rule applies).
     */
    private function hireSplit(array $j, ?float $classRate, string $cur): ?array
    {
        $drivers = array_values(array_filter($j['crew'] ?? [], static fn ($c) => (string) $c['kind'] === 'driver' && in_array((string) $c['status'], ['engaged', 'replaced'], true) && trim((string) ($c['person_name'] ?? '')) !== ''));
        if (count($drivers) < 2 || !array_filter($drivers, static fn ($c) => (string) $c['status'] === 'replaced')) {
            return null;
        }
        $qty  = max(1, (int) ($j['qty'] ?? 1));
        $tour = $this->db->query('SELECT arrival_date, departure_date FROM tf_tour WHERE id = ?', [(int) $j['tour_id']])->getRowArray() ?: [];
        usort($drivers, static fn ($a, $b) => [(string) ($a['date_from'] ?? ''), (int) $a['id']] <=> [(string) ($b['date_from'] ?? ''), (int) $b['id']]);
        $legs = array_values(array_filter($j['legs'] ?? [], static function ($l) {
            return !in_array((string) $l['kind'], ['garage', 'idle'], true) && !((string) $l['kind'] === 'local' && strcasecmp(trim((string) $l['from_place']), trim((string) $l['to_place'])) === 0);
        }));
        $hasActuals = $legs && array_key_exists('actual_km', $legs[0]);
        $segments = [];
        $assigned = [];
        $problems = [];
        $hire = 0.0;
        $kmT  = 0.0;
        foreach ($drivers as $c) {
            $from = $c['date_from'] ?: ($tour['arrival_date'] ?? null);
            $to   = $c['date_to'] ?: ((string) $c['status'] === 'engaged' ? ($tour['departure_date'] ?? null) : null);
            $r    = $c['person_km_rate'] !== null && $c['person_km_rate'] !== '' ? (float) $c['person_km_rate'] : $classRate;
            $src  = $c['person_km_rate'] !== null && $c['person_km_rate'] !== '' ? (in_array($c['person_rate_source'] ?? null, ['special', 'general'], true) ? $c['person_rate_source'] : 'driver') : ($classRate !== null ? 'class' : null);
            $seg  = ['crew_id' => (int) $c['id'], 'name' => $c['person_name'], 'status' => $c['status'], 'date_from' => $from, 'date_to' => $to, 'rate' => $r, 'rate_source' => $src, 'legs' => 0, 'missing' => 0, 'km' => null, 'hire' => null, 'why' => null];
            if ($from === null || $to === null) {
                $seg['why'] = 'the first and last day are not set';
            } elseif ($r === null) {
                $seg['why'] = 'no rate per kilometre on the line or the card';
            } else {
                $km = 0.0;
                foreach ($legs as $i => $l) {
                    $d = (string) ($l['day_date'] ?? '');
                    if ($d === '' || $d < $from || $d > $to) {
                        continue;
                    }
                    $assigned[$i] = true;
                    $seg['legs']++;
                    if (!$hasActuals || $l['actual_km'] === null || $l['actual_km'] === '') {
                        $seg['missing']++;
                        continue;
                    }
                    $km += (float) $l['actual_km'] + (float) ($l['extra_km'] ?? 0);
                }
                if ($seg['missing'] > 0) {
                    $seg['why'] = $seg['missing'] . ' leg' . ($seg['missing'] === 1 ? '' : 's') . ' of ' . $seg['legs'] . ' on their days without the actual kilometres';
                } else {
                    $seg['km']   = round($km, 1);
                    $seg['hire'] = round($km * $r * $qty, 2);
                    $hire += $seg['hire'];
                    $kmT  += $seg['km'];
                }
            }
            if ($seg['why'] !== null) {
                $problems[] = $c['person_name'] . ': ' . $seg['why'];
            }
            $segments[] = $seg;
        }
        $outside = count(array_filter(array_keys($legs), static fn ($i) => !isset($assigned[$i])));
        if ($outside > 0 && !$problems) {
            $problems[] = $outside . ' driven leg' . ($outside === 1 ? '' : 's') . ' dated outside every driver\'s days (or undated)';
        }
        $replacedOn = null;
        foreach ($drivers as $c) {
            if ((string) $c['status'] === 'engaged' && !empty($c['replaces'])) {
                $replacedOn = $c['date_from'] ?: $replacedOn;
            }
        }
        $known = !$problems;
        return [
            'known' => $known, 'replaced_on' => $replacedOn, 'segments' => $segments, 'qty' => $qty, 'currency' => $cur,
            'hire' => $known ? round($hire, 2) : null, 'km' => $known ? round($kmT, 1) : null,
            'why' => $known ? null : 'The driver was replaced mid-tour' . ($replacedOn ? ' on ' . $replacedOn : '') . ', so the vehicle hire is split by the kilometres actually driven on each driver\'s days — not worked out yet: ' . implode('; ', $problems) . '. Flagged for a manual settlement.',
        ];
    }

    /** The settlement as it stands now: the payable, less the advances paid, plus or minus the adjustments. */
    private function settlementLive(array $j): array
    {
        $m = $j['money'];
        $paid = 0.0;
        $adv  = [];
        foreach ($j['advances'] as $a) {
            if ((string) $a['status'] === 'paid') {
                $paid += (float) $a['amount'];
                $adv[] = ['advance_no' => $a['advance_no'], 'amount' => (float) $a['amount'], 'paid_on' => $a['paid_on'], 'person_name' => $a['person_name']];
            }
        }
        $adjT = 0.0;
        $adjs = [];
        foreach ($j['adjustments'] as $x) {
            $adjT += (float) $x['amount'];
            $adjs[] = ['id' => (int) $x['id'], 'label' => $x['label'], 'amount' => (float) $x['amount'], 'note' => $x['note'], 'added_by' => $x['added_by'], 'added_at' => $x['added_at']];
        }
        $payable = (float) $m['payable_now'];
        return [
            'frozen'      => false,
            'settlement_no' => null,
            'currency'    => $m['currency'],
            'hire'        => $m['hire']['now'],
            'hire_basis'  => $m['hire']['basis'],
            'hire_km'     => in_array($m['hire']['basis'], ['actual', 'split'], true) ? $m['hire']['km_actual'] : ($m['hire']['basis'] === 'scheduled' ? $m['hire']['km_scheduled'] : ($m['hire']['basis'] === 'manual' ? null : $m['hire']['km_costed'])),
            'hire_split'  => $m['split'] ?? null,
            'manual_hire' => !empty($m['manual_hire']),
            'bata'        => $m['bata']['total'],
            'bata_lines'  => $m['bata']['lines'],
            'payable'     => round($payable, 2),
            'advances'    => round($paid, 2),
            'advance_lines' => $adv,
            'adjustments' => round($adjT, 2),
            'adjustment_lines' => $adjs,
            'balance'     => round($payable - $paid + $adjT, 2),
            'unpaid_advances' => count(array_filter($j['advances'], static fn ($a) => in_array((string) $a['status'], ['requested', 'approved'], true))),
            'settled_at'  => null,
            'settled_by'  => null,
            'note'        => null,
        ];
    }

    /** A settlement frozen when the card was settled, exactly as it was then. */
    private function settlementFromRow(array $r): array
    {
        $d = json_decode((string) ($r['detail_json'] ?? ''), true) ?: [];
        return [
            'frozen'      => true,
            'settlement_no' => $r['settlement_no'],
            'currency'    => $r['currency'],
            'hire'        => $r['hire'] !== null ? (float) $r['hire'] : null,
            'hire_basis'  => $r['hire_basis'],
            'hire_km'     => $r['hire_km'] !== null ? (float) $r['hire_km'] : null,
            'hire_split'  => $d['hire_split'] ?? null,
            'manual_hire' => $r['hire_basis'] === 'manual',
            'bata'        => $r['bata'] !== null ? (float) $r['bata'] : null,
            'bata_lines'  => $d['bata_lines'] ?? [],
            'payable'     => (float) $r['payable'],
            'advances'    => (float) $r['advances'],
            'advance_lines' => $d['advance_lines'] ?? [],
            'adjustments' => (float) $r['adjustments'],
            'adjustment_lines' => $d['adjustment_lines'] ?? [],
            'balance'     => (float) $r['balance'],
            'unpaid_advances' => 0,
            'settled_at'  => $r['settled_at'],
            'settled_by'  => $r['settled_by'],
            'note'        => $r['note'],
        ];
    }

    private function nextNumber(string $key, string $prefixKey, string $default): string
    {
        $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = ?", [$key]);
        $n = (int) $this->db->insertID();
        if ($n <= 0) {
            $this->db->query("INSERT INTO tf_meta (k, v) VALUES (?, '1001') ON DUPLICATE KEY UPDATE v = v", [$key]);
            $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = ?", [$key]);
            $n = (int) $this->db->insertID();
        }
        return trim(($this->meta($prefixKey) ?? $default) . ' ' . ($n - 1));
    }

    private function assertDriverAdvance(): void
    {
        if (!$this->hasDriverAdvance()) {
            throw new \RuntimeException('The driver advance tables are not on this install yet. Open Tour Inquiry and press Install first.');
        }
    }

    private function advanceRow(int $id): array
    {
        $a = $this->db->query('SELECT * FROM tf_driver_advance WHERE id = ?', [$id])->getRowArray();
        if (!$a) {
            throw new \RuntimeException('Advance not found.');
        }
        return $a;
    }

    private static function figure($v): ?float
    {
        if ($v === null || $v === '' || !is_numeric($v)) {
            return null;
        }
        return round((float) $v, 2);
    }

    /**
     * Request an advance against the payable: a percentage of it (the card works the amount
     * out), or a figure typed by a holder of the privilege (the controller checks; here
     * a typed figure is recorded as adjusted, with why). The base is frozen on the row, so
     * a later change to the card does not move an advance already asked for.
     *
     * @param array{crew_id?:mixed, pct?:mixed, amount?:mixed, note?:mixed, adjust_note?:mixed} $d
     */
    public function requestAdvance(int $jobId, array $d, bool $mayAdjust = false): array
    {
        $this->assertDriverAdvance();
        $this->assertTransportLive($jobId);
        $j = $this->transportJob($jobId);
        if (in_array((string) $j['status'], ['settled', 'cancelled'], true)) {
            throw new \RuntimeException('This card is ' . $j['status'] . '; no advance can be requested on it.');
        }
        $m = $j['money'];
        $base = (float) $m['payable_before'];
        if ($base <= 0) {
            throw new \RuntimeException('Nothing is payable on this card yet — engage the driver (bata) or check the card has costed kilometres and a rate.');
        }
        $crew = null;
        $crewId = self::intOrNull($d['crew_id'] ?? null);
        foreach ($j['crew'] as $c) {
            if ((string) $c['kind'] === 'driver' && (string) $c['status'] === 'engaged' && trim((string) ($c['person_name'] ?? '')) !== '' && ($crewId === null || (int) $c['id'] === $crewId)) {
                $crew = $c;
            }
        }
        if ($crew === null) {
            throw new \RuntimeException('Engage the driver first: the advance is paid to a person on the card.');
        }
        $pct    = self::figure($d['pct'] ?? null);
        $typed  = self::figure($d['amount'] ?? null);
        if ($pct !== null && ($pct <= 0 || $pct > 100)) {
            throw new \InvalidArgumentException('The percentage has to be between 1 and 100.');
        }
        if ($pct === null && $typed === null) {
            throw new \InvalidArgumentException('Choose a percentage, or type the amount.');
        }
        $calc = $pct !== null ? round($base * $pct / 100, 2) : $typed;
        $amount = $calc;
        $adjNote = null;
        if ($typed !== null && $pct !== null && abs($typed - $calc) >= 0.01) {
            if (!$mayAdjust) {
                throw new \RuntimeException('Adjusting the amount away from the percentage needs the Driver advance privilege — request the percentage, and Management adjusts.');
            }
            $adjNote = self::cut($d['adjust_note'] ?? null, 255);
            if ($adjNote === null) {
                throw new \InvalidArgumentException('Say why the amount differs from the percentage; the note stays on the advance.');
            }
            $amount = $typed;
        } elseif ($typed !== null && $pct === null && !$mayAdjust) {
            throw new \RuntimeException('Typing an amount instead of a percentage needs the Driver advance privilege — choose a percentage, and Management adjusts.');
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('The advance has to be more than nought.');
        }
        if ($amount > $base) {
            throw new \RuntimeException('The advance (' . number_format($amount, 2) . ') is more than the whole payable (' . number_format($base, 2) . ').');
        }
        $paidSoFar = 0.0;
        foreach ($j['advances'] as $a) {
            if (in_array((string) $a['status'], ['requested', 'approved', 'paid'], true)) {
                $paidSoFar += (float) $a['amount'];
            }
        }
        if ($paidSoFar + $amount > $base) {
            throw new \RuntimeException('With the advances already on the card (' . number_format($paidSoFar, 2) . ') this would go over the payable (' . number_format($base, 2) . ').');
        }
        $no = $this->nextNumber('driver_advance_next', 'driver_advance_prefix', 'DAV');
        $this->db->transStart();
        $this->db->query(
            'INSERT INTO tf_driver_advance (tour_id, job_id, crew_id, advance_no, person_name, supplier_acc, currency, base_hire, base_bata, base_total, base_note, pct, amount_calc, amount, adjust_note, adjusted_by, adjusted_at, status, requested_at, requested_by, request_note, updated_at, updated_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                (int) $j['tour_id'], $jobId, (int) $crew['id'], $no, self::cut($crew['person_name'], 190), self::cut($crew['supplier_acc'] ?? null, 50), $m['currency'],
                $m['hire']['costed'], $m['bata']['total'], $base,
                self::cut(($m['hire']['costed'] !== null ? 'hire as costed on ' . self::num1($m['hire']['km_costed']) . ' km' : 'no vehicle hire (' . ($m['why_no_hire'] ?? 'no rate') . ')') . ' + bata ' . number_format((float) $m['bata']['total'], 2), 255),
                $pct, $calc, $amount, $adjNote, $adjNote !== null ? $this->user : null, $adjNote !== null ? $this->now() : null,
                'requested', $this->now(), $this->user, self::cut($d['note'] ?? null, 255), $this->now(), $this->user,
            ]
        );
        $id = (int) $this->db->insertID();
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'advance', null, 'requested', $no . ': ' . ($pct !== null ? rtrim(rtrim(number_format($pct, 2), '0'), '.') . '% of ' . number_format($base, 2) . ' = ' : '') . number_format($amount, 2) . ' ' . $m['currency'] . ' for ' . $crew['person_name'] . ($adjNote ? ' (adjusted from ' . number_format($calc, 2) . ' — ' . $adjNote . ')' : ''));
        $this->db->transComplete();
        $this->logAdvance($j, $id, 'advance requested', [['what' => 'advance', 'from' => null, 'to' => $no . ' ' . number_format($amount, 2)]]);
        return $this->transportJob($jobId);
    }

    private static function num1($v): string
    {
        return $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.');
    }

    /** One line of history for an advance, with the figures that moved. Never a gate. */
    private function logAdvance(array $j, int $advanceId, string $summary, array $changes): void
    {
        try {
            $this->intake()->activity()->record('transport_job', (int) $j['id'], (int) $j['intake_id'], (int) $j['tour_id'], 'advance', $summary, $changes);
        } catch (\Throwable $e) {
            // the line is a record, never a gate
        }
    }

    /** Adjust the amount of a requested or approved advance (the privilege, checked by the controller), with why. */
    public function adjustAdvance(int $advanceId, $amount, ?string $why): array
    {
        $this->assertDriverAdvance();
        $a = $this->advanceRow($advanceId);
        if (!in_array((string) $a['status'], ['requested', 'approved'], true)) {
            throw new \RuntimeException('An advance already ' . $a['status'] . ' is not adjusted; a wrong payment is put right by an adjustment on the settlement.');
        }
        $new = self::figure($amount);
        if ($new === null || $new <= 0) {
            throw new \InvalidArgumentException('The amount has to be a figure above nought.');
        }
        if (self::cut($why, 255) === null) {
            throw new \InvalidArgumentException('Say why the amount is adjusted; the note stays on the advance.');
        }
        if ($new > (float) $a['base_total']) {
            throw new \RuntimeException('The advance (' . number_format($new, 2) . ') is more than the whole payable (' . number_format((float) $a['base_total'], 2) . ').');
        }
        $j = $this->jobIntake((int) $a['job_id']);
        $this->db->transStart();
        $this->db->query('UPDATE tf_driver_advance SET amount = ?, adjust_note = ?, adjusted_by = ?, adjusted_at = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$new, self::cut($why, 255), $this->user, $this->now(), $this->now(), $this->user, $advanceId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $a['job_id'], 'advance', (string) $a['status'], (string) $a['status'], $a['advance_no'] . ' adjusted: ' . number_format((float) $a['amount'], 2) . ' → ' . number_format($new, 2) . ' — ' . self::cut($why, 255));
        $this->db->transComplete();
        $this->logAdvance(['id' => (int) $a['job_id'], 'intake_id' => (int) $j['intake_id'], 'tour_id' => (int) $j['tour_id']], $advanceId, $a['advance_no'] . ' adjusted — ' . self::cut($why, 255), [['what' => $a['advance_no'] . ' amount', 'from' => number_format((float) $a['amount'], 2), 'to' => number_format($new, 2)]]);
        return $this->transportJob((int) $a['job_id']);
    }

    /** Approve a requested advance (the privilege): who and when, with a note if any. */
    public function approveAdvance(int $advanceId, ?string $note = null): array
    {
        $this->assertDriverAdvance();
        $a = $this->advanceRow($advanceId);
        if ((string) $a['status'] !== 'requested') {
            throw new \RuntimeException('Only a requested advance is approved; this one is ' . $a['status'] . '.');
        }
        $j = $this->jobIntake((int) $a['job_id']);
        $this->db->transStart();
        $this->db->query("UPDATE tf_driver_advance SET status = 'approved', approved_at = ?, approved_by = ?, approve_note = ?, updated_at = ?, updated_by = ? WHERE id = ?", [$this->now(), $this->user, self::cut($note, 255), $this->now(), $this->user, $advanceId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $a['job_id'], 'advance', 'requested', 'approved', $a['advance_no'] . ': ' . number_format((float) $a['amount'], 2) . ' ' . $a['currency'] . ' approved' . ($note ? ' — ' . $note : ''));
        $this->db->transComplete();
        $this->logAdvance(['id' => (int) $a['job_id'], 'intake_id' => (int) $j['intake_id'], 'tour_id' => (int) $j['tour_id']], $advanceId, $a['advance_no'] . ' approved', [['what' => $a['advance_no'] . ' status', 'from' => 'requested', 'to' => 'approved']]);
        return $this->transportJob((int) $a['job_id']);
    }

    /**
     * Record the payment of an approved advance (the privilege): who paid, on what day, how,
     * the reference and remarks. Paid is final — never edited, never cancelled; a wrong
     * payment is put right by an adjustment on the settlement.
     *
     * @param array{paid_on?:mixed, method?:mixed, reference?:mixed, remarks?:mixed} $d
     */
    public function payAdvance(int $advanceId, array $d): array
    {
        $this->assertDriverAdvance();
        $a = $this->advanceRow($advanceId);
        if ((string) $a['status'] !== 'approved') {
            throw new \RuntimeException((string) $a['status'] === 'requested' ? 'Approve the advance first; then record its payment.' : 'This advance is ' . $a['status'] . '.');
        }
        $on = self::cut($d['paid_on'] ?? null, 10);
        if ($on === null || strtotime($on) === false) {
            throw new \InvalidArgumentException('The payment date is needed.');
        }
        $method = strtolower(trim((string) ($d['method'] ?? '')));
        if (!in_array($method, self::ADVANCE_METHODS, true)) {
            throw new \InvalidArgumentException('Say how it was paid: cash, bank, cheque or other.');
        }
        $ref = self::cut($d['reference'] ?? null, 100);
        if ($method !== 'cash' && $ref === null) {
            throw new \InvalidArgumentException('A bank, cheque or other payment needs its reference.');
        }
        $j = $this->jobIntake((int) $a['job_id']);
        $this->db->transStart();
        $this->db->query("UPDATE tf_driver_advance SET status = 'paid', paid_at = ?, paid_by = ?, paid_on = ?, method = ?, reference = ?, remarks = ?, updated_at = ?, updated_by = ? WHERE id = ?", [$this->now(), $this->user, date('Y-m-d', strtotime($on)), $method, $ref, self::cut($d['remarks'] ?? null, 255), $this->now(), $this->user, $advanceId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $a['job_id'], 'advance', 'approved', 'paid', $a['advance_no'] . ': ' . number_format((float) $a['amount'], 2) . ' ' . $a['currency'] . ' paid ' . date('j M Y', strtotime($on)) . ' by ' . $method . ($ref ? ' ref ' . $ref : '') . (self::cut($d['remarks'] ?? null, 255) ? ' — ' . self::cut($d['remarks'], 255) : ''));
        $this->db->transComplete();
        $this->logAdvance(['id' => (int) $a['job_id'], 'intake_id' => (int) $j['intake_id'], 'tour_id' => (int) $j['tour_id']], $advanceId, $a['advance_no'] . ' paid', [['what' => $a['advance_no'] . ' status', 'from' => 'approved', 'to' => 'paid'], ['what' => $a['advance_no'] . ' paid', 'from' => null, 'to' => date('Y-m-d', strtotime($on)) . ' · ' . $method . ($ref ? ' · ' . $ref : '')]]);
        return $this->transportJob((int) $a['job_id']);
    }

    /** Cancel an advance not yet paid, with why. Paid is final. */
    public function cancelAdvance(int $advanceId, ?string $why): array
    {
        $this->assertDriverAdvance();
        $a = $this->advanceRow($advanceId);
        if ((string) $a['status'] === 'paid') {
            throw new \RuntimeException('A paid advance is not cancelled; put it right with an adjustment on the settlement.');
        }
        if ((string) $a['status'] === 'cancelled') {
            return $this->transportJob((int) $a['job_id']);
        }
        if (self::cut($why, 255) === null) {
            throw new \InvalidArgumentException('Say why the advance is cancelled; the note stays on it.');
        }
        $j = $this->jobIntake((int) $a['job_id']);
        $this->db->transStart();
        $this->db->query("UPDATE tf_driver_advance SET status = 'cancelled', cancelled_at = ?, cancelled_by = ?, cancel_note = ?, updated_at = ?, updated_by = ? WHERE id = ?", [$this->now(), $this->user, self::cut($why, 255), $this->now(), $this->user, $advanceId]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $a['job_id'], 'advance', (string) $a['status'], 'cancelled', $a['advance_no'] . ' cancelled — ' . self::cut($why, 255));
        $this->db->transComplete();
        $this->logAdvance(['id' => (int) $a['job_id'], 'intake_id' => (int) $j['intake_id'], 'tour_id' => (int) $j['tour_id']], $advanceId, $a['advance_no'] . ' cancelled — ' . self::cut($why, 255), [['what' => $a['advance_no'] . ' status', 'from' => (string) $a['status'], 'to' => 'cancelled']]);
        return $this->transportJob((int) $a['job_id']);
    }

    /** An adjustment on the settlement (the privilege): a label, a signed amount (+ owed to the driver, − deducted), a note. Not on a settled card. */
    public function addAdjustment(int $jobId, array $d): array
    {
        $this->assertDriverAdvance();
        $this->assertTransportLive($jobId);
        $j = $this->jobIntake($jobId);
        if ((string) $j['status'] === 'settled') {
            throw new \RuntimeException('The card is settled; the settlement is frozen.');
        }
        $label = self::cut($d['label'] ?? null, 120);
        $amt   = self::figure($d['amount'] ?? null);
        if ($label === null || $amt === null || $amt == 0.0) {
            throw new \InvalidArgumentException('An adjustment needs a label and an amount (plus for money owed to the driver, minus for a deduction).');
        }
        $this->db->transStart();
        $this->db->query('INSERT INTO tf_driver_adjustment (job_id, label, amount, note, added_at, added_by) VALUES (?,?,?,?,?,?)', [$jobId, $label, $amt, self::cut($d['note'] ?? null, 255), $this->now(), $this->user]);
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'adjustment', null, null, $label . ': ' . ($amt > 0 ? '+' : '') . number_format($amt, 2) . (self::cut($d['note'] ?? null, 255) ? ' — ' . self::cut($d['note'], 255) : ''));
        $this->db->transComplete();
        $this->logAdvance(['id' => $jobId, 'intake_id' => (int) $j['intake_id'], 'tour_id' => (int) $j['tour_id']], 0, 'settlement adjustment: ' . $label, [['what' => 'adjustment ' . $label, 'from' => null, 'to' => ($amt > 0 ? '+' : '') . number_format($amt, 2)]]);
        return $this->transportJob($jobId);
    }

    /** Take an adjustment off again, with why; it stays on the row, marked removed. Not on a settled card. */
    public function removeAdjustment(int $adjId, ?string $why): array
    {
        $this->assertDriverAdvance();
        $x = $this->db->query('SELECT * FROM tf_driver_adjustment WHERE id = ?', [$adjId])->getRowArray();
        if (!$x) {
            throw new \RuntimeException('Adjustment not found.');
        }
        $j = $this->jobIntake((int) $x['job_id']);
        if ((string) $j['status'] === 'settled') {
            throw new \RuntimeException('The card is settled; the settlement is frozen.');
        }
        if (self::cut($why, 255) === null) {
            throw new \InvalidArgumentException('Say why the adjustment comes off.');
        }
        if ($x['removed_at'] === null) {
            $this->db->transStart();
            $this->db->query('UPDATE tf_driver_adjustment SET removed_at = ?, removed_by = ?, remove_note = ? WHERE id = ?', [$this->now(), $this->user, self::cut($why, 255), $adjId]);
            $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', (int) $x['job_id'], 'adjustment', null, null, $x['label'] . ' (' . ((float) $x['amount'] > 0 ? '+' : '') . number_format((float) $x['amount'], 2) . ') taken off — ' . self::cut($why, 255));
            $this->db->transComplete();
            $this->logAdvance(['id' => (int) $x['job_id'], 'intake_id' => (int) $j['intake_id'], 'tour_id' => (int) $j['tour_id']], 0, 'settlement adjustment removed: ' . $x['label'], [['what' => 'adjustment ' . $x['label'], 'from' => ((float) $x['amount'] > 0 ? '+' : '') . number_format((float) $x['amount'], 2), 'to' => null]]);
        }
        return $this->transportJob((int) $x['job_id']);
    }

    /** The settlement frozen as the card is settled: the figures as they stand, numbered, kept. */
    private function freezeSettlement(int $jobId, ?string $note): void
    {
        if (!$this->hasDriverAdvance()) {
            return;
        }
        if ($this->db->query('SELECT id FROM tf_driver_settlement WHERE job_id = ?', [$jobId])->getRowArray()) {
            return;
        }
        $j = $this->transportJob($jobId);
        $s = $j['settlement'];
        if (!$s || !empty($s['frozen'])) {
            return;
        }
        $no = $this->nextNumber('driver_settlement_next', 'driver_settlement_prefix', 'DST');
        $this->db->query(
            'INSERT INTO tf_driver_settlement (tour_id, job_id, settlement_no, currency, hire, hire_basis, hire_km, bata, payable, advances, adjustments, balance, detail_json, note, settled_at, settled_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                (int) $j['tour_id'], $jobId, $no, $s['currency'], $s['hire'], $s['hire_basis'], $s['hire_km'], $s['bata'], $s['payable'], $s['advances'], $s['adjustments'], $s['balance'],
                json_encode(['bata_lines' => $s['bata_lines'], 'advance_lines' => $s['advance_lines'], 'adjustment_lines' => $s['adjustment_lines'], 'hire_split' => $s['hire_split'] ?? null, 'hire_by_hand' => !empty($s['manual_hire'])], JSON_UNESCAPED_UNICODE),
                self::cut($note, 400), $this->now(), $this->user,
            ]
        );
        $this->event((int) $j['intake_id'], (int) $j['tour_id'], 'transport', $jobId, 'settlement', null, 'settled', $no . ': payable ' . number_format((float) $s['payable'], 2) . ' − advances ' . number_format((float) $s['advances'], 2) . ' ' . ((float) $s['adjustments'] >= 0 ? '+' : '−') . ' adjustments ' . number_format(abs((float) $s['adjustments']), 2) . ' = balance ' . number_format((float) $s['balance'], 2) . ' ' . $s['currency']);
        $this->logAdvance(['id' => $jobId, 'intake_id' => (int) $j['intake_id'], 'tour_id' => (int) $j['tour_id']], 0, $no . ' settled: balance ' . number_format((float) $s['balance'], 2), [['what' => 'settlement', 'from' => null, 'to' => $no . ' balance ' . number_format((float) $s['balance'], 2)]]);
    }

    // =====================================================================
    //  Masters the desks edit: the driver's pack list and the problem reasons
    // =====================================================================

    public function docTypes(bool $activeOnly = false): array
    {
        return $this->db->query('SELECT * FROM tf_doc_type' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort_order, code')->getResultArray();
    }

    public function saveDocType(string $code, string $name, string $phase, bool $active): array
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', $code) ?? '');
        $name = self::cut($name, 80);
        if ($code === '' || $name === null) {
            throw new \RuntimeException('A document needs a short code and a name.');
        }
        $phase = $phase === 'after' ? 'after' : 'before';
        $this->db->query(
            'INSERT INTO tf_doc_type (code, name, phase, active, sort_order, updated_by, updated_at) VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), phase = VALUES(phase), active = VALUES(active), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
            [$code, $name, $phase, $active ? 1 : 0, 100, $this->user, $this->now()]
        );
        return $this->docTypes();
    }

    // =====================================================================
    //  The bar's badges — one count each, from the same queries the screens use
    // =====================================================================

    public function counts(): array
    {
        $out = ['tour/intake' => 0, 'tour/hotel_bookings' => 0, 'tour/transport_ops' => 0, 'tour/board' => 0];
        // tf-walkfix-r1: the badge counts what the Inquiries list shows under Open — an archived inquiry is not with the desk
        $c = $this->db->query("SELECT status, COUNT(*) n FROM tf_intake WHERE status IN ('new','working','quoted') AND archived_at IS NULL GROUP BY status")->getResultArray();
        foreach ($c as $r) {
            $out['tour/intake'] += (int) $r['n'];
        }
        // the red counts are work alerts: the number of TOURS still needing that desk's work —
        // five hotels on one tour count once, and the tour leaves the count only when the desk
        // marks its work complete (or the tour is over). Never a card, never an inquiry.
        // the Board's read first: it settles each live tour's stage (operating, completed) by date
        $out['tour/board'] = count(array_filter($this->board(['which' => 'live']), static fn ($t) => $t['problems'] > 0 || $t['late']));
        $hotel = 0;
        $transport = 0;
        foreach ($this->deskTours('hotel', ['which' => 'all']) as $t) {
            if ($t['stage'] === 'cancelled') {
                continue;
            }
            $hotel     += $t['strip']['hotel_work'] === 'pending' ? 1 : 0;
            $transport += $t['strip']['transport_work'] === 'pending' ? 1 : 0;
        }
        $out['tour/hotel_bookings'] = $hotel;
        $out['tour/transport_ops']  = $transport;
        // tf-tickets-r1: attractions under stock control that are low or out of tickets — work for whoever buys them
        $out['tour/ticket_stock'] = $this->stock()->alerts();
        return $out;
    }
}
