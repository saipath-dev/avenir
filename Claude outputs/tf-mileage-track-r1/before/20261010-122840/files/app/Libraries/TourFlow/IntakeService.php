<?php

namespace App\Libraries\TourFlow;

use App\Libraries\HotelRates\HotelRateService;

/**
 * Everything the inquiry panel does to the database.
 *
 * An intake is an inquiry as it arrived. It owns the pasted email, what was read
 * from it, what a person corrected, the requested nights, the names and the
 * requests. It does not own a price and it does not create a tour: a tour is
 * made later, when somebody converts the inquiry, and until then nothing here
 * shows up on any existing screen.
 *
 * Hotels the agent names are matched against the Hotel Rates panel and checked
 * for the nights asked for, at the market the agent's country maps to. Published
 * rates only — an ad hoc, payment-only plan is never consulted for an inquiry and
 * is never shown to an agent.
 */
final class IntakeService
{
    private ?bool $hotelCodeCol = null;   // hr2_hotel.hotel_code present (Hotel Rates schema 8)
    private $db;
    private string $user;
    private ?HotelRateService $rates;
    private ?ServiceLookup $services = null;
    private ?PhotoLibrary $photos = null;
    private ?ItineraryWriter $writer = null;
    private ?ProgrammeMasters $masters = null;
    private ?array $quotePlans = null;
    /** The customer ledger, read once per request (customers()). */
    private ?array $customerRows = null;
    private ?VehiclePlanner $planner = null;
    private ?AccomPlanner $accom = null;
    private ?MileageLookup $mileage = null;
    private ?GoogleDistance $google = null;
    private ?TransportRates $transport = null;
    private ?MileageAllowances $allowances = null;
    /** The signed-in person as an account, for ownership and the history; the username alone still works everywhere. */
    private ?int $actorId = null;
    private ?string $actorName = null;
    private ?Activity $activity = null;

    public function __construct($db, string $user = '', ?HotelRateService $rates = null)
    {
        $this->db    = $db;
        $this->user  = $user;
        $this->rates = $rates;
    }

    /** Who is working: the account id and the name to show. A new inquiry is theirs; every line of history names them. */
    public function withActor(?int $userId, ?string $displayName): self
    {
        $this->actorId   = $userId ?: null;
        $this->actorName = $displayName !== null && trim($displayName) !== '' ? trim($displayName) : null;
        $this->activity  = null;
        $this->masters   = null;
        return $this;
    }

    public function actorId(): ?int
    {
        return $this->actorId;
    }

    public function actorName(): string
    {
        return $this->actorName ?? $this->user;
    }

    public function user(): string
    {
        return $this->user;
    }

    /** The history writer, carrying the same person. */
    public function activity(): Activity
    {
        return $this->activity ??= new Activity($this->db, $this->user, $this->actorId, $this->actorName);
    }

    private function rates(): HotelRateService
    {
        if ($this->rates === null) {
            $this->rates = new HotelRateService($this->db, $this->user);
        }
        return $this->rates;
    }

    public function services(): ServiceLookup
    {
        return $this->services ??= new ServiceLookup($this->db);
    }

    /** The itinerary as Avenir writes it (schema 28): a paragraph a day from the nights, visits, excursions and flights. */
    public function writer(): ItineraryWriter
    {
        return $this->writer ??= new ItineraryWriter($this->db, $this->services());
    }

    /** The two programme masters (schema 29): Free Visits (never costed) and Paid Activities & Services (priced). */
    public function masters(): ProgrammeMasters
    {
        return $this->masters ??= new ProgrammeMasters($this->db, $this->user, $this->actorId, $this->actorName());
    }

    /** The tour photo library (schema 27): the pictures the client quotation carries, a presentation layer only. */
    public function photos(): PhotoLibrary
    {
        return $this->photos ??= new PhotoLibrary($this->db, $this->actorName());
    }

    /**
     * A person's choice of photo for one day of the itinerary: a photo id, 'none' for no
     * picture, or 'auto' to let the library choose again. Written to the history like any edit.
     */
    public function setDayPhoto(int $id, int $dayNo, $choice): array
    {
        $this->assertOpen($id);   // the pictures follow the inquiry: read-only once it is confirmed, cancelled or archived
        $it = $this->get($id);
        if (!$it) {
            throw new \RuntimeException('Inquiry not found.');
        }
        $before = $it['photos']['days'][$dayNo] ?? null;
        $this->photos()->setDayPhoto($id, $dayNo, $choice);
        $it = $this->get($id);
        $after = $it['photos']['days'][$dayNo] ?? null;
        $word = static fn ($p) => $p === null ? 'automatic' : ($p['photo_id'] === null ? 'no photo' : 'photo ' . $p['photo_id'] . ($p['caption'] ? ' (' . $p['caption'] . ')' : '') . ($p['chosen_by'] === 'human' ? ', chosen' : ', automatic'));
        $this->activity()->record('inquiry', $id, $id, null, 'photo', 'Day ' . $dayNo . ' photo: ' . $word($after), [['what' => 'day ' . $dayNo . ' photo', 'from' => $word($before), 'to' => $word($after)]]);
        return $it;
    }

    // =====================================================================
    //  Reference numbers
    // =====================================================================

    /**
     * Atomic, unlike the read-then-add counters elsewhere in the system: two
     * people pressing Save at the same moment cannot be handed the same number.
     */
    public function nextInquiryNo(): string
    {
        $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'inquiry_next'");
        $row = $this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray();
        $n   = (int) ($row['n'] ?? 0);
        if ($n <= 0) { // the meta row was missing; put it back and try once more
            $this->db->query("INSERT INTO tf_meta (k, v) VALUES ('inquiry_next', '1001') ON DUPLICATE KEY UPDATE v = v");
            $this->db->query("UPDATE tf_meta SET v = LAST_INSERT_ID(CAST(v AS UNSIGNED) + 1) WHERE k = 'inquiry_next'");
            $n = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS n')->getRowArray()['n'] ?? 1001);
        }
        $prefix = (string) ($this->meta('inquiry_prefix') ?? 'INQ');
        return trim($prefix . ' ' . ($n - 1));
    }

    public function meta(string $k): ?string
    {
        $r = $this->db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray();
        return $r['v'] ?? null;
    }

    // =====================================================================
    //  Creating and reading
    // =====================================================================

    /**
     * Start an inquiry. Nothing is read yet.
     *
     * The email is optional. An inquiry that arrives by phone, or on a day when the
     * reading service is down, is started empty and typed in — it is the same record,
     * with the same fields, and it can be given its email later. Reading is an
     * assistant here, never a turnstile you have to get through to do the work.
     */
    public function create(array $source): int
    {
        $now  = date('Y-m-d H:i:s');
        $text = trim((string) ($source['source_text'] ?? ''));

        // Whose inquiry it is: the person who starts it, until Management reassigns it.
        // The account is matched by the sign-in name when the screen did not say.
        $handlerId   = $this->actorId;
        $handlerName = $this->actorName;
        if ($handlerId === null && $this->user !== '') {
            $who = Staff::byUsername($this->db, $this->user);
            $handlerId   = $who['id'] ?? null;
            $handlerName = $handlerName ?? ($who['name'] ?? null);
        }
        $handlerName = $handlerName ?? ($this->user !== '' ? $this->user : null);

        // The email as it arrived is kept untouched beside the copy that is read. The review
        // step may have corrected the working copy already (a missing year, say): then the
        // paste is the original and the corrected text is what the reader gets.
        $original = trim((string) ($source['source_text_original'] ?? ''));
        if ($original === '') {
            $original = $text;
        }
        $edited = $text !== '' && $original !== $text;

        $keepOriginal = $this->keepsOriginal();
        $this->db->query(
            'INSERT INTO tf_intake (inquiry_no, status, handler_id, handler_name, handler_set_by, handler_set_at, source_subject, source_from, source_received_at, source_text, '
            . ($keepOriginal ? 'source_text_original, source_edited_at, source_edited_by, ' : '') . 'created_at, created_by, updated_at, updated_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,' . ($keepOriginal ? '?,?,?,' : '') . '?,?,?,?)',
            array_merge([
                $this->nextInquiryNo(),
                // an inquiry somebody is already typing into is being worked on, not new
                $text === '' ? 'working' : 'new',
                $handlerId, self::cut($handlerName, 190), $handlerName === null ? null : $this->user, $handlerName === null ? null : $now,
                self::cut($source['source_subject'] ?? null, 400),
                self::cut($source['source_from'] ?? null, 255),
                self::dateTimeOrNull($source['source_received_at'] ?? null),
                $text === '' ? null : $text,
            ], $keepOriginal ? [$text === '' ? null : $original, $edited ? $now : null, $edited ? $this->user : null] : [],
            [$now, $this->user, $now, $this->user])
        );
        $id = (int) $this->db->insertID();
        try {
            $this->activity()->record('inquiry', $id, $id, null, 'created', 'Inquiry started' . ($text === '' ? ' by hand' : ' from an email') . ($handlerName ? '; handling agent ' . $handlerName : ''));
            if ($edited) {
                $this->logEmailEdit($id, $original, $text, 'Corrected before reading');
            }
        } catch (\Throwable $e) {
            // the history never stops the work
        }
        return $id;
    }

    /** Schema 14 keeps the original email beside the working copy; an install not upgraded yet has no such column. */
    private ?bool $keepsOriginal = null;
    private ?bool $hasMealSource = null;
    private ?bool $hasRateFreeze = null;
    private ?bool $hasRoomTypeAuto = null;   // tf-walkfix-r1 (schema 35)
    private ?bool $hasTwoFigures = null;
    private ?bool $hasSamePlace = null;   // tf-walkfix-r1 (schema 35)
    private ?array $gatewayTowns = null;  // tf-walkfix-r1: the airport area, from the planner's gateway setting
    private ?bool $hasActuals = null;
    private ?bool $hasRemoved = null;
    private ?bool $hasPlanKey = null;
    private ?bool $hasCrewBy = null;
    private ?bool $hasFollowUp = null;
    private ?bool $hasDatesRule = null;
    private ?bool $hasCrewDaysBy = null;

    private function keepsOriginal(): bool
    {
        if ($this->keepsOriginal === null) {
            try {
                $this->keepsOriginal = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'source_text_original'")->getResultArray();
            } catch (\Throwable $e) {
                $this->keepsOriginal = false;
            }
        }
        return $this->keepsOriginal;
    }

    /**
     * Correct the working copy of the email — the text the reader works from — and keep the
     * original exactly as it was pasted. Every change is a line in the history with the lines
     * that moved. Reading it again afterwards is a separate decision (read()).
     */
    public function editEmail(int $id, string $text, ?string $note = null): array
    {
        $this->assertOpen($id);
        $text = trim(str_replace("\r\n", "\n", $text));
        if ($text === '') {
            throw new \InvalidArgumentException('The email text cannot be emptied. To take the email off this inquiry, ask Management.');
        }
        if (!$this->keepsOriginal()) {
            throw new \RuntimeException('The Tour Desk needs its schema 14 upgrade before an email can be corrected (Tour Inquiry → Install now).');
        }
        $r = $this->db->query('SELECT source_text, source_text_original FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$r || trim((string) $r['source_text']) === '') {
            throw new \InvalidArgumentException('This inquiry has no email to correct — paste one first.');
        }
        $before = str_replace("\r\n", "\n", (string) $r['source_text']);
        if (trim($before) === $text) {
            return $this->get($id);
        }
        $now = date('Y-m-d H:i:s');
        // the original is set once, from what was there before the first correction
        $this->db->query(
            'UPDATE tf_intake SET source_text = ?, source_text_original = COALESCE(source_text_original, ?), source_edited_at = ?, source_edited_by = ?, updated_at = ?, updated_by = ? WHERE id = ?',
            [$text, (string) $r['source_text'], $now, $this->user, $now, $this->user, $id]
        );
        $this->logEmailEdit($id, $before, $text, $note);
        return $this->get($id);
    }

    /** One history line per correction of the email text: the lines that changed, side by side. */
    private function logEmailEdit(int $id, string $before, string $after, ?string $note = null): void
    {
        try {
            $changes = self::lineDiff($before, $after);
            $n = count($changes);
            $this->activity()->record('inquiry', $id, $id, null, 'email_edited', trim(($note ? trim($note) . ' — ' : '') . 'Email text corrected: ' . $n . ' line(s) changed. The original is kept.'), $changes);
        } catch (\Throwable $e) {
            // the history never stops the work
        }
    }

    /**
     * The lines that differ between two texts, paired up in order — enough to see what a
     * correction was, without a full diff. Capped, so a re-paste of a whole email is one
     * readable entry rather than a hundred lines.
     *
     * @return array<int, array{what:string, from:?string, to:?string}>
     */
    public static function lineDiff(string $before, string $after, int $max = 12): array
    {
        $a = array_map('rtrim', preg_split('/\r\n|\r|\n/', $before) ?: []);
        $b = array_map('rtrim', preg_split('/\r\n|\r|\n/', $after) ?: []);
        $removed = [];
        $added   = [];
        $countB  = array_count_values($b);
        $countA  = array_count_values($a);
        foreach ($a as $i => $line) {
            if (($countB[$line] ?? 0) > 0) {
                $countB[$line]--;
            } else {
                $removed[] = ['no' => $i + 1, 'line' => $line];
            }
        }
        foreach ($b as $i => $line) {
            if (($countA[$line] ?? 0) > 0) {
                $countA[$line]--;
            } else {
                $added[] = ['no' => $i + 1, 'line' => $line];
            }
        }
        $out = [];
        $n = max(count($removed), count($added));
        for ($i = 0; $i < $n && count($out) < $max; $i++) {
            $from = $removed[$i]['line'] ?? null;
            $to   = $added[$i]['line'] ?? null;
            if (trim((string) $from) === '' && trim((string) $to) === '') {
                continue;
            }
            $out[] = ['what' => 'Line ' . ($added[$i]['no'] ?? $removed[$i]['no']), 'from' => $from === null ? null : mb_substr($from, 0, 200), 'to' => $to === null ? null : mb_substr($to, 0, 200)];
        }
        if ($n > $max) {
            $out[] = ['what' => 'and ' . ($n - $max) . ' more line(s)', 'from' => null, 'to' => null];
        }
        return $out;
    }

    /**
     * Start an inquiry from a filled-in form: the record, the values and the blank nights
     * in one go, so an inquiry number is used only when somebody actually saves one.
     *
     * The values go through saveField, the same path the review screen uses, so they are
     * marked as the person's and a later read of an email will not overwrite them.
     *
     * @param array<string, mixed> $fields  keyed by EmailExtractor::FIELDS
     */
    public function createManual(array $fields, int $makeNights = 0, string $agentAcc = ''): array
    {
        $id = $this->create([]);
        foreach ($fields as $key => $value) {
            if (!in_array($key, EmailExtractor::FIELDS, true)) {
                continue;
            }
            if ($value === null || trim((string) $value) === '') {
                continue;
            }
            $this->saveField($id, $key, $value);
        }
        // One blank night per night stated. Counting nights off an arrival date is
        // arithmetic, not a guess, and every one of them is still empty for a person
        // to fill in — no hotel, no meal plan, nothing invented.
        // An agent chosen from the ledger keeps its booking code, whatever the typed name
        // was afterwards — the code is the master's, not something derived from spelling.
        if (trim($agentAcc) !== '') {
            $this->db->query('UPDATE tf_intake SET agent_acc = ? WHERE id = ?', [trim($agentAcc), $id]);
        }
        $this->resolveAgent($id);
        if (trim($agentAcc) !== '') {
            $this->db->query('UPDATE tf_intake SET agent_acc = ? WHERE id = ?', [trim($agentAcc), $id]);
        }
        $this->syncNights($id);

        for ($i = 0; $i < max(0, min(60, $makeNights)); $i++) {
            $this->addDay($id);
        }
        return $this->get($id);
    }

    /**
     * Give an inquiry that was started by hand the agent's email, on the same record.
     * Nothing already typed is touched; reading it afterwards is a separate decision.
     */
    public function attachEmail(int $id, array $source): array
    {
        $this->assertOpen($id);
        $text = trim((string) ($source['source_text'] ?? ''));
        if ($text === '') {
            throw new \InvalidArgumentException('Paste the email before attaching it.');
        }
        $original = trim((string) ($source['source_text_original'] ?? ''));
        if ($original === '') {
            $original = $text;
        }
        $edited = $original !== $text;
        $now    = date('Y-m-d H:i:s');
        $keep   = $this->keepsOriginal();
        $this->db->query(
            'UPDATE tf_intake SET source_text = ?, ' . ($keep ? 'source_text_original = ?, source_edited_at = ?, source_edited_by = ?, ' : '') . 'source_subject = COALESCE(?, source_subject), source_from = COALESCE(?, source_from),
                source_received_at = COALESCE(?, source_received_at), updated_at = ?, updated_by = ? WHERE id = ?',
            array_merge([$text], $keep ? [$original, $edited ? $now : null, $edited ? $this->user : null] : [], [
                self::cut($source['source_subject'] ?? null, 400),
                self::cut($source['source_from'] ?? null, 255),
                self::dateTimeOrNull($source['source_received_at'] ?? null),
                $now, $this->user, $id,
            ])
        );
        if ($edited) {
            $this->logEmailEdit($id, $original, $text, 'Corrected before reading');
        }
        return $this->get($id);
    }

    public function get(int $id): ?array
    {
        $it = $this->db->query('SELECT * FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            return null;
        }
        $it['fields']     = json_decode((string) $it['fields_json'], true) ?: [];
        $it['days']       = $this->db->query('SELECT * FROM tf_intake_day WHERE intake_id = ? ORDER BY day_no, id', [$id])->getResultArray();
        $it['passengers'] = $this->db->query('SELECT * FROM tf_intake_pax WHERE intake_id = ? ORDER BY sort_order, id', [$id])->getResultArray();
        $it['requests']   = $this->db->query('SELECT * FROM tf_intake_request WHERE intake_id = ? ORDER BY sort_order, id', [$id])->getResultArray();
        $it['services']   = $this->db->query('SELECT * FROM tf_intake_service WHERE intake_id = ? ORDER BY day_no, sort_order, id', [$id])->getResultArray();
        // Entrance & Activities follows the list, the party and the settings on its own (see syncServices) —
        // on an open inquiry only: opening a closed one (confirmed, declined, cancelled, archived) changes nothing on it
        $it['recalc']     = $this->syncServices($id, $it['services'], self::isClosed($it), self::intOrNull($it['pax_total'] ?? null), $it['days'], $it['arrival_date'] ?? null,
                                                ['adults' => self::intOrNull($it['pax_adults'] ?? null), 'children' => self::intOrNull($it['pax_children'] ?? null)]);
        $this->markFreeVisits($it['services']);
        $it['vehicles']   = $this->db->query('SELECT * FROM tf_intake_vehicle WHERE intake_id = ? ORDER BY sort_order, id', [$id])->getResultArray();
        // what the master says today, beside what this inquiry copied — so a rate that
        // has moved since the vehicle was settled is visible, and only ever re-priced on purpose
        foreach ($it['vehicles'] as &$v) {
            $now = $this->transport()->current((string) $v['class_code']);
            $v['master_now']    = $now['rate_km'] ?? null;
            $v['master_now_id'] = $now['id'] ?? null;
        }
        unset($v);
        $it['crew']       = $this->db->query('SELECT * FROM tf_intake_crew WHERE intake_id = ? ORDER BY sort_order, id', [$id])->getResultArray();
        $it['sectors']    = $this->db->query('SELECT * FROM tf_intake_sector WHERE intake_id = ? ORDER BY vehicle_seq, day_no, seq, id', [$id])->getResultArray();
        $it['groups']     = $this->db->query('SELECT * FROM tf_intake_group WHERE intake_id = ? ORDER BY sort_order, id', [$id])->getResultArray();
        $it['excursions'] = $this->db->query('SELECT * FROM tf_intake_excursion WHERE intake_id = ? ORDER BY day_no, sort_order, id', [$id])->getResultArray();
        $it['vehicle_days'] = $this->db->query('SELECT vehicle_seq, day_no, retained, note FROM tf_intake_vehicle_day WHERE intake_id = ?', [$id])->getResultArray();
        // the quotation versions, newest first, without the frozen costing (that is read on its own page)
        $it['quotes'] = $this->db->query('SELECT id, version, status, currency, usd_rate, rate_date, pax, sell_usd, per_person_usd, valid_until, note, issued_at, issued_by, created_at, created_by FROM tf_quote WHERE intake_id = ? ORDER BY version DESC', [$id])->getResultArray();
        $it['mileage']    = self::mileageSummary($it['sectors'], $it['vehicles']);
        // whether Google can be asked at all, and which figure the quotation is costed on
        $it['mileage']['source']     = $this->quoteSource();
        $it['mileage']['google_key'] = $this->hasTwoFigures() && $this->google()->hasKey();
        $it['rooms']      = self::roomMix($it, null);
        $it['accom']      = [];
        foreach ($this->db->query("SELECT * FROM tf_intake_accom WHERE intake_id = ? AND day_no = 0", [$id])->getResultArray() as $a) {
            $a['lines'] = json_decode((string) ($a['detail_json'] ?? ''), true) ?: [];
            unset($a['detail_json']);
            $it['accom'][$a['kind']] = $a;
        }

        // Hang each day's services off the day itself, so one row of the itinerary table
        // has everything it needs to draw without the screen re-grouping the list.
        $byDay = [];
        foreach ($it['services'] as $s) {
            $byDay[(int) $s['day_no']][] = $s;
        }
        $legsByDay = [];
        foreach ($it['sectors'] as $s) {
            $legsByDay[(int) $s['day_no']][] = $s;
        }
        $excByDay = [];
        foreach ($it['excursions'] as $x) {
            $excByDay[(int) $x['day_no']][] = $x;
        }
        foreach ($it['days'] as &$d) {
            $d['services']   = $byDay[(int) $d['day_no']] ?? [];
            $d['sectors']    = $legsByDay[(int) $d['day_no']] ?? [];
            $d['excursions'] = $excByDay[(int) $d['day_no']] ?? [];
            $d['room_mix']   = self::roomMix($it, (int) $d['day_no']);
            $d['room_types'] = json_decode((string) ($d['room_types_json'] ?? ''), true) ?: [];
            $d['rate_supp']  = json_decode((string) ($d['rate_supp_json'] ?? ''), true) ?: [];
            // the panel's answer where it has moved away from the figure kept on the night
            $d['rate_drift'] = json_decode((string) ($d['rate_drift_json'] ?? ''), true) ?: null;
        }
        unset($d);
        // schema 32: the dates decide the tour's shape. The nights are the lines inside the stay; a line
        // dated on or after the departure is kept, apart, as "falls on the departure day" until a person
        // says what it is; the departure day itself is made from the dates, with whatever is on that day.
        $it['tour_dates']     = TourDates::of($it);
        $split                = TourDates::split($it['days'], $it['tour_dates']['nights'], $it['tour_dates']['departure']);
        $it['days']           = $split['nights'];
        $it['departure_rows'] = $split['departure_rows'];
        $it['missing_nights'] = $split['missing'];
        $it['departure_day']  = $this->departureDay($it, $byDay, $excByDay, $legsByDay);
        $it['dates_check']    = $this->datesCheck($it);
        // the days the party is served, from what is on the programme — the crew's days follow it
        $it['programme_days']      = $this->programmeDays($it);
        $it['programme_day_count'] = $it['programme_days'] !== [] ? count($it['programme_days']) : null;
        $it['programme_basis']     = self::programmeBasis($it['programme_days'], $it['tour_dates']['departure_day_no'] ?? null);
        $it['crew']           = $this->crewWithDays($it);
        $it['tour_services'] = $byDay[0] ?? [];
        unset($it['fields_json']);
        // the email: whether the working copy differs from the paste, and what the date check says of it
        $it['source_edited'] = !empty($it['source_edited_at']) || (isset($it['source_text_original']) && $it['source_text_original'] !== null && (string) $it['source_text_original'] !== (string) $it['source_text']);
        $it['email_checks']  = trim((string) ($it['source_text'] ?? '')) !== '' ? TextChecks::scan((string) $it['source_text'], $it['source_received_at'] ?? null)['flags'] : [];
        // the customer master's word on the agent: marked, on the ledger but not marked, or not on it
        $it['agent_marked'] = $this->agentMark($it['agent_acc'] ?? null);
        $it['entrances'] = $this->entrances($it);
        // schema 40: the meals of a Half Board tour, day by day — what the hotel gives, what is arranged outside from the Meal Master
        if ($this->hasMealCols()) {
            try {
                $it['meals'] = $this->meals()->plan($it);
            } catch (\Throwable $e) {
                $it['meals'] = ['available' => true, 'days' => [], 'problems' => [], 'error' => $e->getMessage()];
            }
        }
        // the pictures the client quotation shows for each day (schema 27) — a presentation layer:
        // absent until the library is installed, and never a figure
        try {
            if ($this->photos()->available()) {
                $it['photos'] = $this->photos()->forIntake($it, self::isClosed($it));
            }
        } catch (\Throwable $e) {
            $it['photos'] = ['days' => [], 'tags' => [], 'cover' => [], 'error' => $e->getMessage()];
        }
        // the itinerary written out, day by day, the way the quotation says it (schema 28)
        try {
            $it['itinerary'] = $this->writer()->write($it);
        } catch (\Throwable $e) {
            $it['itinerary'] = ['days' => [], 'departure' => null, 'error' => $e->getMessage()];
        }
        // schema 33: the Edit Requests on it, in short — waiting ones, the last applied (reading the email
        // again is off after it), and whether Confirm Tour waits for a quotation version issued after it
        $it['edit_requests'] = EditRequests::summary($this->db, $it);
        $it['gaps']     = $this->gaps($it);
        $it['progress'] = in_array((string) $it['status'], ['confirmed', 'cancelled'], true) || $it['confirmed_at'] !== null ? $this->tourProgress($id) : null;
        $it['archived'] = !empty($it['archived_at']);
        // read-only when confirmed or cancelled (the record of what was sold) and when archived
        $it['locked']   = in_array((string) $it['status'], ['confirmed', 'cancelled'], true) || $it['archived'];
        // ownership beside editing: whose tour it is, and who last touched it
        $it['handler']   = ['id' => $it['handler_id'] !== null ? (int) $it['handler_id'] : null, 'name' => $it['handler_name'], 'set_by' => $it['handler_set_by'], 'set_at' => $it['handler_set_at']];
        $it['last_edit'] = ['by' => $it['updated_by'], 'at' => $it['updated_at']];
        return $it;
    }

    /**
     * The lines put on a day from the Free Visits master (schema 29) carry where the visit is and
     * what sort of visit it is, read from the master: the itinerary says a free visit "en route"
     * or "on arrival" by its destination, and the screen marks the line as a free visit.
     */
    private function markFreeVisits(array &$services): void
    {
        $ids = [];
        foreach ($services as $s) {
            if (!empty($s['free_visit_id'])) {
                $ids[(int) $s['free_visit_id']] = true;
            }
        }
        if ($ids === []) {
            return;
        }
        try {
            $rows = $this->db->query('SELECT id, destination, kind, name FROM tf_free_visit WHERE id IN (' . implode(',', array_keys($ids)) . ')')->getResultArray();
        } catch (\Throwable $e) {
            return;
        }
        $by = [];
        foreach ($rows as $r) {
            $by[(int) $r['id']] = $r;
        }
        foreach ($services as &$s) {
            $f = !empty($s['free_visit_id']) ? ($by[(int) $s['free_visit_id']] ?? null) : null;
            if ($f !== null) {
                $s['free_destination'] = (string) $f['destination'];
                $s['free_kind']        = (string) $f['kind'];
            }
        }
        unset($s);
    }

    /**
     * The list, with every filter the screen offers combined: status, handling agent,
     * customer / group / number text, and the arrival window. Archived inquiries are out of
     * every list but their own.
     *
     * @param array{status?:string,q?:string,handler?:string|int,from?:string,to?:string,limit?:int} $f
     */
    public function list(array $f = []): array
    {
        $where  = [];
        $binds  = [];
        $status = (string) ($f['status'] ?? 'open');
        if ($status === 'archived') {
            $where[] = 'archived_at IS NOT NULL';
        } else {
            $where[] = 'archived_at IS NULL';
            if ($status === 'open') {
                $where[] = "status IN ('new','working','quoted')";
            } elseif ($status === 'follow_up') {
                // chase the agent: a follow-up date that has come, on an inquiry still open
                $where[] = "status IN ('new','working','quoted') AND follow_up_on IS NOT NULL AND follow_up_on <= CURDATE()";
            } elseif ($status !== 'all' && $status !== '') {
                $where[] = 'status = ?';
                $binds[] = $status;
            }
        }
        $q = trim((string) ($f['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(inquiry_no LIKE ? OR agent_name LIKE ? OR tour_name LIKE ? OR agent_email LIKE ? OR agent_acc LIKE ?)';
            array_push($binds, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
        }
        [$hw, $hb] = self::handlerWhere($f['handler'] ?? null, '', $this->db);
        if ($hw !== null) {
            $where[] = $hw;
            array_push($binds, ...$hb);
        }
        if (!empty($f['from'])) {
            $where[] = 'arrival_date >= ?';
            $binds[] = (string) $f['from'];
        }
        if (!empty($f['to'])) {
            $where[] = 'arrival_date <= ?';
            $binds[] = (string) $f['to'];
        }
        // has_email rather than the email itself: the list needs to know which way an
        // inquiry came in, and pulling a whole MEDIUMTEXT per row to find out is waste.
        // the version issued to the agent, if any: the list can offer Confirm Tour on the row
        $sql = "SELECT id, inquiry_no, status, agent_name, agent_acc, tour_name, arrival_date, departure_date, nights, pax_total, rooms_note, market_code, tour_id, extracted_at, extract_error,
                    created_at, created_by, updated_at, updated_by, handler_id, handler_name, handler_set_at, handler_set_by, archived_at, archived_by, archive_note,
                    (SELECT MAX(q.version) FROM tf_quote q WHERE q.intake_id = tf_intake.id AND q.status = 'issued') AS issued_version,
                    CASE WHEN source_text IS NULL OR source_text = '' THEN 0 ELSE 1 END AS has_email" . ($this->hasFollowUp() ? ', follow_up_on, follow_up_note' : '') . "
                FROM tf_intake" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ($status === 'follow_up' ? ' ORDER BY follow_up_on, id DESC' : ' ORDER BY id DESC') . ' LIMIT ' . max(1, min(500, (int) ($f['limit'] ?? 100)));
        $rows = $this->db->query($sql, $binds)->getResultArray();
        foreach ($rows as &$r) {
            $r['open_days'] = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_intake_day WHERE intake_id = ? AND rate_state IN ('no_plan','no_supplier','not_in_panel')", [$r['id']])->getRowArray()['n'] ?? 0);
            $r['open_questions'] = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_intake_request WHERE intake_id = ? AND kind = 'question' AND status = 'open'", [$r['id']])->getRowArray()['n'] ?? 0);
        }
        return $rows;
    }

    /**
     * The WHERE for a handling-agent filter, shared by every list. A number is an account
     * id and also matches a row that carries only that account's name; text matches the
     * name. Blank means no filter.
     *
     * @return array{0:?string, 1:array}
     */
    public static function handlerWhere($handler, string $prefix = '', $db = null): array
    {
        $h = trim((string) ($handler ?? ''));
        if ($h === '') {
            return [null, []];
        }
        if ($h === 'none') {
            return ["({$prefix}handler_id IS NULL AND ({$prefix}handler_name IS NULL OR {$prefix}handler_name = ''))", []];
        }
        if (ctype_digit($h)) {
            $who = $db ? Staff::byId($db, (int) $h) : null;
            if ($who) {
                return ["({$prefix}handler_id = ? OR ({$prefix}handler_id IS NULL AND {$prefix}handler_name = ?))", [(int) $h, $who['name']]];
            }
            return ["{$prefix}handler_id = ?", [(int) $h]];
        }
        return ["{$prefix}handler_name = ?", [$h]];
    }

    public function counts(): array
    {
        $rows = $this->db->query('SELECT status, COUNT(*) n FROM tf_intake WHERE archived_at IS NULL GROUP BY status')->getResultArray();
        $out  = ['new' => 0, 'working' => 0, 'quoted' => 0, 'confirmed' => 0, 'declined' => 0, 'cancelled' => 0, 'converted' => 0, 'archived' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int) $r['n'];
        }
        $out['open'] = $out['new'] + $out['working'] + $out['quoted'];
        $out['archived'] = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_intake WHERE archived_at IS NOT NULL')->getRowArray()['n'] ?? 0);
        $out['follow_up'] = $this->hasFollowUp()
            ? (int) ($this->db->query("SELECT COUNT(*) n FROM tf_intake WHERE archived_at IS NULL AND status IN ('new','working','quoted') AND follow_up_on IS NOT NULL AND follow_up_on <= CURDATE()")->getRowArray()['n'] ?? 0)
            : 0;
        return $out;
    }

    /** Whether an inquiry can carry a follow-up date (schema 20). */
    private function hasFollowUp(): bool
    {
        if ($this->hasFollowUp === null) {
            try {
                $this->hasFollowUp = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'follow_up_on'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasFollowUp = false;
            }
        }
        return $this->hasFollowUp;
    }

    /**
     * When to chase the agent about a quotation, and a word on what for. Nothing is sent:
     * the date puts the inquiry on the "Follow-ups due" list when it comes. Blank clears it.
     */
    public function saveFollowUp(int $id, $on, ?string $note = null): array
    {
        $this->assertOpen($id);
        if (!$this->hasFollowUp()) {
            throw new \RuntimeException('Follow-up dates are not installed on this site yet; run Install from the Inquiries screen.');
        }
        $date = self::dateOrNull($on);
        if ($on !== null && trim((string) $on) !== '' && $date === null) {
            throw new \InvalidArgumentException('That is not a date.');
        }
        $this->db->query('UPDATE tf_intake SET follow_up_on = ?, follow_up_note = ?, updated_at = ?, updated_by = ? WHERE id = ?',
            [$date, $date !== null ? self::cut($note, 255) : null, date('Y-m-d H:i:s'), $this->user, $id]);
        $this->touch($id);
        return $this->get($id);
    }

    // =====================================================================
    //  Ownership, archiving — the Manage privilege's own work
    // =====================================================================

    /**
     * Give the tour to somebody else. The handler stays on the inquiry and on its tour
     * until this is called again; the change is written to the history with who did it.
     */
    public function setHandler(int $id, ?int $userId, ?string $name, ?string $note = null): array
    {
        $it = $this->db->query('SELECT id, handler_id, handler_name FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            throw new \RuntimeException('Inquiry not found.');
        }
        $who = $userId ? Staff::byId($this->db, $userId) : null;
        if ($userId && !$who) {
            throw new \RuntimeException('That user account was not found.');
        }
        $newName = $who['name'] ?? self::cut($name, 190);
        if ($newName === null) {
            throw new \RuntimeException('Choose who handles this tour.');
        }
        $now = date('Y-m-d H:i:s');
        $this->db->transStart();
        $this->db->query('UPDATE tf_intake SET handler_id = ?, handler_name = ?, handler_set_by = ?, handler_set_at = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$who['id'] ?? null, $newName, $this->user, $now, $now, $this->user, $id]);
        $tour = null;
        if ($this->db->query("SHOW TABLES LIKE 'tf_tour'")->getResultArray()) {
            $tour = $this->db->query('SELECT id FROM tf_tour WHERE intake_id = ?', [$id])->getRowArray();
            if ($tour) {
                $this->db->query('UPDATE tf_tour SET handler_id = ?, handler_name = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$who['id'] ?? null, $newName, $now, $this->user, (int) $tour['id']]);
            }
        }
        $from = $it['handler_name'] ?: '(nobody)';
        $this->activity()->record('inquiry', $id, $id, $tour ? (int) $tour['id'] : null, 'reassigned', trim("Handling agent {$from} → {$newName}" . ($note ? ' — ' . trim($note) : '')), [['what' => 'handling agent', 'from' => $it['handler_name'], 'to' => $newName]]);
        $this->db->transComplete();
        return $this->get($id);
    }

    /**
     * What hangs off an inquiry, before anyone archives or removes it: its tour and the
     * cards on the desks, its quotations, and its own rows. Counts, so the screen can say
     * exactly why something is refused.
     */
    public function dependencies(int $id): array
    {
        $it = $this->db->query('SELECT id, inquiry_no, status, tour_id, archived_at FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            throw new \RuntimeException('Inquiry not found.');
        }
        $n = fn (string $sql, array $b = []) => (int) ($this->db->query($sql, array_merge([$id], $b))->getRowArray()['n'] ?? 0);
        $out = [
            'inquiry_no' => $it['inquiry_no'], 'status' => $it['status'], 'archived' => !empty($it['archived_at']),
            'tour' => null, 'hotel_cards' => 0, 'hotel_cards_moved' => 0, 'transport_cards' => 0, 'transport_cards_moved' => 0,
            'quotes' => $n('SELECT COUNT(*) n FROM tf_quote WHERE intake_id = ?'),
            'quotes_issued' => $n("SELECT COUNT(*) n FROM tf_quote WHERE intake_id = ? AND status IN ('issued','superseded')"),
            'nights' => $n('SELECT COUNT(*) n FROM tf_intake_day WHERE intake_id = ?'),
            'passengers' => $n('SELECT COUNT(*) n FROM tf_intake_pax WHERE intake_id = ?'),
            'services' => $n('SELECT COUNT(*) n FROM tf_intake_service WHERE intake_id = ?'),
            'history' => $n('SELECT COUNT(*) n FROM tf_activity WHERE intake_id = ?'),
            'legacy_tour_id' => $it['tour_id'],
            'blocks_archive' => [], 'blocks_delete' => [],
        ];
        if ($this->db->query("SHOW TABLES LIKE 'tf_tour'")->getResultArray()) {
            $t = $this->db->query('SELECT id, tour_no, stage FROM tf_tour WHERE intake_id = ?', [$id])->getRowArray();
            if ($t) {
                $out['tour'] = ['id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'stage' => $t['stage']];
                $out['hotel_cards'] = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_hotel_job WHERE tour_id = ?', [(int) $t['id']])->getRowArray()['n'] ?? 0);
                $out['hotel_cards_moved'] = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_hotel_job WHERE tour_id = ? AND status NOT IN ('to_book','cancelled')", [(int) $t['id']])->getRowArray()['n'] ?? 0);
                $out['transport_cards'] = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_transport_job WHERE tour_id = ?', [(int) $t['id']])->getRowArray()['n'] ?? 0);
                $out['transport_cards_moved'] = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_transport_job WHERE tour_id = ? AND status NOT IN ('to_allocate','cancelled')", [(int) $t['id']])->getRowArray()['n'] ?? 0);
            }
        }
        if ($out['tour'] && $out['tour']['stage'] !== 'cancelled') {
            $out['blocks_archive'][] = 'its tour ' . $out['tour']['tour_no'] . ' is ' . $out['tour']['stage'] . ' on the desks — cancel the tour from the Tour Board first';
        }
        if (in_array((string) $it['status'], ['confirmed'], true) && !$out['tour']) {
            $out['blocks_archive'][] = 'it is confirmed';
        }
        if ($out['tour']) {
            $out['blocks_delete'][] = 'it has a tour (' . $out['tour']['tour_no'] . ', ' . $out['tour']['stage'] . ') with ' . $out['hotel_cards'] . ' hotel card(s) and ' . $out['transport_cards'] . ' transport card(s)';
        }
        if ($out['quotes_issued'] > 0) {
            $out['blocks_delete'][] = $out['quotes_issued'] . ' quotation version(s) were issued to the agent';
        }
        if (!empty($it['tour_id'])) {
            $out['blocks_delete'][] = 'it is linked to legacy tour ' . $it['tour_id'];
        }
        if (empty($it['archived_at'])) {
            $out['blocks_delete'][] = 'it is not archived — archive it first';
        }
        return $out;
    }

    /** Out of every list and count, data intact, restorable. Refused while its tour is live on the desks. */
    public function archive(int $id, ?string $note = null): array
    {
        $dep = $this->dependencies($id);
        if ($dep['archived']) {
            return $this->get($id);
        }
        if ($dep['blocks_archive']) {
            throw new \RuntimeException('This inquiry cannot be archived: ' . implode('; ', $dep['blocks_archive']) . '.');
        }
        $now = date('Y-m-d H:i:s');
        $this->db->query('UPDATE tf_intake SET archived_at = ?, archived_by = ?, archive_note = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, self::cut($note, 400), $now, $this->user, $id]);
        $this->activity()->record('inquiry', $id, $id, $dep['tour']['id'] ?? null, 'archived', trim('Archived' . ($note ? ' — ' . trim($note) : '')));
        return $this->get($id);
    }

    public function restore(int $id): array
    {
        $it = $this->db->query('SELECT id, archived_at FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            throw new \RuntimeException('Inquiry not found.');
        }
        if (empty($it['archived_at'])) {
            return $this->get($id);
        }
        $now = date('Y-m-d H:i:s');
        $this->db->query('UPDATE tf_intake SET archived_at = NULL, archived_by = NULL, archive_note = NULL, updated_at = ?, updated_by = ? WHERE id = ?', [$now, $this->user, $id]);
        $this->activity()->record('inquiry', $id, $id, null, 'restored', 'Restored from the archive');
        return $this->get($id);
    }

    /**
     * Gone for good — only an archived inquiry with nothing attached: no tour, no issued
     * quotation, no legacy link. Its own rows go with it; its history stays, ending with
     * this line, so the number can still be accounted for.
     */
    public function deletePermanently(int $id, string $confirmNo): array
    {
        $dep = $this->dependencies($id);
        if ($dep['blocks_delete']) {
            throw new \RuntimeException('This inquiry cannot be removed: ' . implode('; ', $dep['blocks_delete']) . '.');
        }
        if (trim($confirmNo) === '' || strcasecmp(trim($confirmNo), (string) $dep['inquiry_no']) !== 0) {
            throw new \RuntimeException('Type the inquiry number (' . $dep['inquiry_no'] . ') to confirm.');
        }
        $this->db->transStart();
        // its own rows, and its trail lines (tf_tour_event carries the inquiry id without a key), so nothing is left pointing at it
        foreach (['tf_tour_event', 'tf_quote', 'tf_intake_excursion', 'tf_intake_vehicle_day', 'tf_intake_group', 'tf_intake_sector', 'tf_intake_accom', 'tf_intake_crew', 'tf_intake_vehicle', 'tf_intake_service', 'tf_intake_request', 'tf_intake_pax', 'tf_intake_day'] as $t) {
            if ($this->db->query("SHOW TABLES LIKE '{$t}'")->getResultArray()) {
                $this->db->query("DELETE FROM {$t} WHERE intake_id = ?", [$id]);
            }
        }
        $this->db->query('DELETE FROM tf_intake WHERE id = ?', [$id]);
        $this->activity()->record('inquiry', $id, $id, null, 'deleted', 'Removed for good: ' . $dep['inquiry_no'] . ' (' . $dep['nights'] . ' night(s), ' . $dep['passengers'] . ' name(s), ' . $dep['quotes'] . ' draft quotation(s))');
        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            throw new \RuntimeException('The inquiry could not be removed; nothing was changed.');
        }
        return $dep;
    }

    // =====================================================================
    //  Storing what was read
    // =====================================================================

    /**
     * Save an extraction over an intake. Anything a person has already corrected
     * by hand is kept: a re-read never overwrites a human answer.
     */
    public function applyExtraction(int $id, array $ex): array
    {
        $this->assertOpen($id);
        // schema 33: once an Edit Request is applied, a new reading could rebuild what it changed
        $this->assertReadable($id);
        $it = $this->db->query('SELECT * FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            throw new \RuntimeException("Inquiry {$id} not found.");
        }
        $existing = json_decode((string) $it['fields_json'], true) ?: [];
        $fields   = [];
        foreach (EmailExtractor::FIELDS as $key) {
            $human = ($existing[$key]['origin'] ?? '') === 'human';
            $fields[$key] = $human ? $existing[$key] : ($ex['fields'][$key] ?? ['value' => null, 'state' => 'missing', 'quote' => null, 'basis' => null, 'origin' => 'ai']);
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transStart();

        $this->db->query(
            'UPDATE tf_intake SET agent_name = ?, agent_email = ?, tour_name = ?, arrival_date = ?, departure_date = ?, nights = ?, pax_total = ?, pax_adults = ?, pax_children = ?,
                rooms_note = ?, hotel_class = ?, arrival_flight = ?, departure_flight = ?, extract_json = ?, fields_json = ?, extract_model = ?, extract_ms = ?, extracted_at = ?, extract_error = NULL,
                status = CASE WHEN status = \'new\' THEN \'working\' ELSE status END, updated_at = ?, updated_by = ? WHERE id = ?',
            [
                self::v($fields, 'agent_name'), self::v($fields, 'agent_email'), self::v($fields, 'tour_name'),
                self::dateOrNull(self::v($fields, 'arrival_date')), self::dateOrNull(self::v($fields, 'departure_date')),
                self::intOrNull(self::v($fields, 'nights')), self::intOrNull(self::v($fields, 'pax_total')),
                self::intOrNull(self::v($fields, 'pax_adults')), self::intOrNull(self::v($fields, 'pax_children')),
                self::cut(self::v($fields, 'rooms_note'), 255), self::cut(self::v($fields, 'hotel_class'), 60),
                self::cut(self::v($fields, 'arrival_flight'), 160), self::cut(self::v($fields, 'departure_flight'), 160),
                json_encode($ex['raw'] ?? $ex, JSON_UNESCAPED_UNICODE), json_encode($fields, JSON_UNESCAPED_UNICODE),
                self::cut($ex['model'] ?? null, 60), (int) ($ex['ms'] ?? 0), $now, $now, $this->user, $id,
            ]
        );

        // schema 32: the email's own dates, kept beside the inquiry's whatever a person typed, so a
        // difference between the two is seen and confirmed rather than silently overwritten
        if ($this->hasDatesRule()) {
            $this->db->query('UPDATE tf_intake SET email_arrival_date = ?, email_departure_date = ? WHERE id = ?', [
                self::dateOrNull($ex['fields']['arrival_date']['value'] ?? null),
                self::dateOrNull($ex['fields']['departure_date']['value'] ?? null),
                $id,
            ]);
        }
        // schema 32: the dates decide the nights. An entry of the email on or after the departure date
        // is the departure day written as a night: its words and its services go to the departure day.
        $stayN = TourDates::nights(self::dateOrNull(self::v($fields, 'arrival_date')), self::dateOrNull(self::v($fields, 'departure_date')));
        $stayDep = self::dateOrNull(self::v($fields, 'departure_date'));
        $depWords = [];
        $depNotes = [];
        $nightCount = 0;
        foreach ((array) ($ex['days'] ?? []) as $d) {
            if (!TourDates::beyond(['day_no' => $d['day_no'] ?? 0, 'day_date' => $d['day_date'] ?? null], $stayN, $stayDep)) {
                $nightCount++;
            }
        }
        $depNo = $stayN !== null ? $stayN + 1 : $nightCount + 1;
        $dep = is_array($ex['departure_day'] ?? null) ? $ex['departure_day'] : null;
        if ($dep) {
            if (trim((string) ($dep['narrative'] ?? '')) !== '') {
                $depWords[] = trim((string) $dep['narrative']);
            }
            if (trim((string) ($dep['special_note'] ?? '')) !== '') {
                $depNotes[] = trim((string) $dep['special_note']);
            }
        }

        // What the email produced is replaced wholesale, so a re-read never leaves half
        // of the last one behind. What a PERSON produced is untouchable: an inquiry that
        // was typed in by hand and later had its email pasted on must come out with both,
        // not with the typing destroyed. A night the person owns also wins over the
        // email's version of that same night, so nothing appears twice.
        $ownedDays = [];
        foreach ($this->db->query("SELECT day_no FROM tf_intake_day WHERE intake_id = ? AND origin = 'human'", [$id])->getResultArray() as $r) {
            $ownedDays[(int) $r['day_no']] = true;
        }
        // The figure kept on a night is what the agent was quoted on; a re-read of the email
        // must not lose it. Each night the email re-creates for the same hotel gets the old
        // night's figure (and its room type) back, and the check that follows compares it
        // with the panel as usual instead of reading it afresh.
        $keptByNo = [];
        if ($this->hasRateFreeze()) {
            foreach ($this->db->query("SELECT * FROM tf_intake_day WHERE intake_id = ? AND origin = 'ai' AND rate_frozen_at IS NOT NULL", [$id])->getResultArray() as $r) {
                $keptByNo[(int) $r['day_no']] = $r;
            }
        }
        $this->db->query("DELETE FROM tf_intake_day WHERE intake_id = ? AND origin = 'ai'", [$id]);
        $mealSource = $this->hasMealSource();
        foreach ((array) ($ex['days'] ?? []) as $d) {
            if (isset($ownedDays[(int) $d['day_no']])) {
                continue;
            }
            if (TourDates::beyond(['day_no' => $d['day_no'] ?? 0, 'day_date' => $d['day_date'] ?? null], $stayN, $stayDep)) {
                // the departure day, not a night: its words are the departure day's
                if (trim((string) ($d['narrative'] ?? '')) !== '') {
                    $depWords[] = trim((string) $d['narrative']);
                }
                if (trim((string) ($d['special_note'] ?? '')) !== '') {
                    $depNotes[] = trim((string) $d['special_note']);
                }
                continue;
            }
            // where the night's meal plan came from: its own line, or the tour-wide basis
            // the agent wrote once ("accommodation H/B") — kept so the screen can say which
            $src = $d['meal_source'] ?? (($d['meal_plan'] ?? null) !== null ? 'day' : null);
            $this->db->query(
                'INSERT INTO tf_intake_day (intake_id, day_no, day_date, area, hotel_name_raw, meal_plan, rooms, nights, narrative, special_note, source_quote, rate_state' . ($mealSource ? ', meal_source, meal_quote' : '') . ')
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?' . ($mealSource ? ',?,?' : '') . ')',
                array_merge([
                    $id, (int) $d['day_no'], $d['day_date'] ?? null, self::cut($d['area'] ?? null, 160),
                    self::cut($d['hotel_name_raw'] ?? null, 190), $d['meal_plan'] ?? null,
                    self::intOrNull($d['rooms'] ?? null), max(1, (int) ($d['nights'] ?? 1)),
                    $d['narrative'] ?? null, self::cut($d['special_note'] ?? null, 500),
                    self::cut($d['source_quote'] ?? null, 1000),
                    empty($d['hotel_name_raw']) ? 'nothing_named' : 'not_checked',
                ], $mealSource ? [$src, self::cut($d['meal_quote'] ?? null, 190)] : [])
            );
            $old = $keptByNo[(int) $d['day_no']] ?? null;
            if ($old && self::sameHotelName((string) $old['hotel_name_raw'], (string) ($d['hotel_name_raw'] ?? ''))) {
                $this->db->query(
                    'UPDATE tf_intake_day SET hotel_id = ?, hotel_acc = ?, room_type_id = ?, room_type_name = ?, rate_state = ?, rate_note = ?, rate_plan_id = ?, rate_plan_name = ?,
                        rate_sgl = ?, rate_dbl = ?, rate_tpl = ?, rate_unit = ?, unit_pax_max = ?, rate_currency = ?, rate_supp_json = ?, rate_frozen_at = ?, rate_key = ?
                     WHERE id = ?',
                    [$old['hotel_id'], $old['hotel_acc'], $old['room_type_id'], $old['room_type_name'], $old['rate_state'], $old['rate_note'], $old['rate_plan_id'], $old['rate_plan_name'],
                     $old['rate_sgl'], $old['rate_dbl'], $old['rate_tpl'], $old['rate_unit'], $old['unit_pax_max'], $old['rate_currency'], $old['rate_supp_json'], $old['rate_frozen_at'], $old['rate_key'],
                     (int) $this->db->insertID()]
                );
            }
        }

        // Services a person added or corrected by hand are theirs and survive a re-read;
        // the ones that came out of the email are replaced, because they describe this
        // email and nothing else. A person's line is recognised by its day and its name.
        // Matched on what the EMAIL called the line, not on what the person renamed it to;
        // otherwise correcting a spelling makes the next read add the old version back.
        $mine = $this->db->query("SELECT day_no, COALESCE(source_title, title) AS t FROM tf_intake_service WHERE intake_id = ? AND origin = 'human'", [$id])->getResultArray();
        $held = [];
        foreach ($mine as $m) {
            $held[(int) $m['day_no'] . '|' . mb_strtolower(trim((string) $m['t']))] = true;
        }
        $this->db->query("DELETE FROM tf_intake_service WHERE intake_id = ? AND origin = 'ai'", [$id]);
        // each day's services on its own day; the departure day's (written as a night, or given on its
        // own by the reader) on the departure day — the day after the last night the dates give
        $svcDays = [];
        foreach ((array) ($ex['days'] ?? []) as $d) {
            $no = TourDates::beyond(['day_no' => $d['day_no'] ?? 0, 'day_date' => $d['day_date'] ?? null], $stayN, $stayDep) ? $depNo : (int) $d['day_no'];
            $svcDays[] = [$no, (array) ($d['services'] ?? [])];
        }
        if ($dep && !empty($dep['services'])) {
            $svcDays[] = [$depNo, (array) $dep['services']];
        }
        foreach ($svcDays as [$svcDay, $svcList]) {
            foreach ($svcList as $i => $s) {
                $title = self::cut($s['title'] ?? null, 255);
                if ($title === null) {
                    continue;
                }
                if (isset($held[(int) $svcDay . '|' . mb_strtolower(trim($title))])) {
                    continue;
                }
                $this->db->query(
                    'INSERT INTO tf_intake_service (intake_id, day_no, kind, title, source_title, qty, pay_mode, special_note, source_quote, origin, sort_order)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $id, (int) $svcDay, $s['kind'] ?? 'other', $title, $title,
                        self::intOrNull($s['qty'] ?? null), $s['pay_mode'] ?? 'unknown', null,
                        self::cut($s['source_quote'] ?? null, 1000), 'ai', (int) ($s['sort_order'] ?? $i),
                    ]
                );
            }
        }

        $ownedNames = [];
        foreach ($this->db->query("SELECT full_name FROM tf_intake_pax WHERE intake_id = ? AND origin = 'human'", [$id])->getResultArray() as $r) {
            $ownedNames[mb_strtolower(trim((string) $r['full_name']))] = true;
        }
        $this->db->query("DELETE FROM tf_intake_pax WHERE intake_id = ? AND origin = 'ai'", [$id]);
        foreach ((array) ($ex['passengers'] ?? []) as $i => $p) {
            if (isset($ownedNames[mb_strtolower(trim((string) $p['full_name']))])) {
                continue;
            }
            $this->db->query(
                "INSERT INTO tf_intake_pax (intake_id, full_name, room_type, note, origin, sort_order) VALUES (?,?,?,?,'ai',?)",
                [$id, self::cut($p['full_name'], 160), self::cut($p['room_type'] ?? null, 20), self::cut($p['note'] ?? null, 160), (int) ($p['sort_order'] ?? $i)]
            );
        }

        // Requests a person has already answered survive a re-read.
        $answered = $this->db->query("SELECT title, status, answer, answered_by, answered_at FROM tf_intake_request WHERE intake_id = ? AND status <> 'open'", [$id])->getResultArray();
        $byTitle  = [];
        foreach ($answered as $a) {
            $byTitle[mb_strtolower($a['title'])] = $a;
        }
        $ownedReq = [];
        foreach ($this->db->query("SELECT title FROM tf_intake_request WHERE intake_id = ? AND origin = 'human'", [$id])->getResultArray() as $r) {
            $ownedReq[mb_strtolower(trim((string) $r['title']))] = true;
        }
        $this->db->query("DELETE FROM tf_intake_request WHERE intake_id = ? AND origin = 'ai'", [$id]);
        foreach ((array) ($ex['requests'] ?? []) as $i => $r) {
            if (isset($ownedReq[mb_strtolower(trim((string) $r['title']))])) {
                continue;
            }
            $prev = $byTitle[mb_strtolower((string) $r['title'])] ?? null;
            $this->db->query(
                "INSERT INTO tf_intake_request (intake_id, kind, title, quote_text, status, answer, answered_by, answered_at, origin, sort_order) VALUES (?,?,?,?,?,?,?,?,'ai',?)",
                [
                    $id, $r['kind'] ?? 'request', self::cut($r['title'], 255), self::cut($r['quote_text'] ?? null, 1000),
                    $prev['status'] ?? 'open', $prev['answer'] ?? null, $prev['answered_by'] ?? null, $prev['answered_at'] ?? null, (int) ($r['sort_order'] ?? $i),
                ]
            );
        }

        // the departure day's words from the email, and a note for the desks where there is none yet
        if ($this->hasDatesRule()) {
            $this->db->query('UPDATE tf_intake SET departure_narrative = ? WHERE id = ?', [$depWords ? implode("\n\n", array_unique($depWords)) : null, $id]);
            if ($depNotes) {
                $this->db->query("UPDATE tf_intake SET departure_note = ? WHERE id = ? AND (departure_note IS NULL OR departure_note = '')", [self::cut(implode(' ', array_unique($depNotes)), 500), $id]);
            }
        }

        $this->db->transComplete();
        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Saving what was read failed; nothing was changed.');
        }

        $this->resolveAgent($id);
        $this->syncNights($id);
        // the nights take their dates from the arrival — a night the reader could not date
        // (no year in the email) is dated by arithmetic once a person has set the arrival
        $this->dateNights($id, self::dateOrNull($it['arrival_date'] ?? null));
        $this->planCrew($id);
        $this->planVehicle($id);
        $this->checkHotels($id);
        $this->checkServices($id);
        $this->planSectors($id);
        $this->planAccom($id);
        return $this->get($id);
    }

    /**
     * Date the nights from the arrival date: night N is the arrival plus N-1 days. That is
     * arithmetic, never a guess, and it is the one thing that makes a hotel rate look-up
     * possible at all. A night with no date gets one; a night whose date was in step with
     * the previous arrival moves with the new one; a night carrying the same day and month
     * but another year is the year slip this exists to catch, and moves too. A night somebody
     * dated on purpose, out of step with the arrival, is left exactly as they set it.
     * Returns how many nights were dated. Does nothing while the arrival is unknown.
     */
    public function dateNights(int $id, ?string $oldArrival = null): int
    {
        $it = $this->db->query('SELECT arrival_date FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        $arrival = self::dateOrNull($it['arrival_date'] ?? null);
        if ($arrival === null) {
            return 0;
        }
        $n = 0;
        foreach ($this->db->query('SELECT id, day_no, day_date FROM tf_intake_day WHERE intake_id = ? ORDER BY day_no, id', [$id])->getResultArray() as $d) {
            $offset = max(0, (int) $d['day_no'] - 1);
            $want   = date('Y-m-d', strtotime($arrival . ' +' . $offset . ' day'));
            $have   = self::dateOrNull($d['day_date']);
            if ($have === $want) {
                continue;
            }
            $inStep = $have === null
                || ($oldArrival !== null && $have === date('Y-m-d', strtotime($oldArrival . ' +' . $offset . ' day')))
                || substr($have, 5) === substr($want, 5);   // same day and month, wrong year
            if ($inStep) {
                $this->db->query('UPDATE tf_intake_day SET day_date = ? WHERE id = ?', [$want, (int) $d['id']]);
                $n++;
            }
        }
        return $n;
    }

    public function recordExtractionFailure(int $id, string $message): void
    {
        $this->db->query('UPDATE tf_intake SET extract_error = ?, updated_at = ?, updated_by = ? WHERE id = ?', [self::cut($message, 400), date('Y-m-d H:i:s'), $this->user, $id]);
    }

    /**
     * A person corrects a field. The correction is marked as theirs so the panel
     * can show what came from the email and what came from a colleague, and so a
     * later re-read never silently overwrites it.
     */
    public function saveField(int $id, string $key, $value, ?string $note = null): array
    {
        $this->assertOpen($id);
        if (!in_array($key, EmailExtractor::FIELDS, true)) {
            throw new \RuntimeException("Unknown field '{$key}'.");
        }
        $it = $this->db->query('SELECT fields_json, arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            throw new \RuntimeException("Inquiry {$id} not found.");
        }
        $fields = json_decode((string) $it['fields_json'], true) ?: [];
        $oldArrival = self::dateOrNull($it['arrival_date'] ?? null);
        // the nights before this change; while a date is part-way through a change, the nights last counted
        $oldNights  = TourDates::nights($it['arrival_date'] ?? null, $it['departure_date'] ?? null)
            ?? (isset($fields['nights']['last']) && $fields['nights']['last'] !== null ? (int) $fields['nights']['last'] : null);
        $clean  = is_string($value) ? trim($value) : $value;
        // The night count is not a field anybody fills in; it follows from the dates.
        // Whatever is offered here is kept as a claim and syncNights decides the value.
        if ($key === 'nights') {
            $fields['nights']['claimed'] = ($clean === '' || $clean === null) ? null : (int) $clean;
            $this->db->query('UPDATE tf_intake SET fields_json = ?, updated_at = ?, updated_by = ? WHERE id = ?',
                [json_encode($fields, JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s'), $this->user, $id]);
            $this->syncNights($id);
            return $this->get($id);
        }

        $fields[$key] = [
            'value'  => ($clean === '' || $clean === null) ? null : $clean,
            'state'  => ($clean === '' || $clean === null) ? 'missing' : 'stated',
            'quote'  => null,
            'basis'  => ($clean === '' || $clean === null) ? null : ($note ?: 'Entered by ' . ($this->user ?: 'a colleague')),
            'origin' => ($clean === '' || $clean === null) ? 'ai' : 'human',
        ];

        $column = in_array($key, ['agent_name', 'agent_email', 'tour_name', 'rooms_note', 'hotel_class', 'arrival_flight', 'departure_flight'], true) ? $key
            : (in_array($key, ['arrival_date', 'departure_date'], true) ? $key
            : (in_array($key, ['nights', 'pax_total', 'pax_adults', 'pax_children'], true) ? $key : null));

        $now = date('Y-m-d H:i:s');
        if ($column !== null) {
            $stored = in_array($key, ['arrival_date', 'departure_date'], true) ? self::dateOrNull($fields[$key]['value'])
                : (in_array($key, ['nights', 'pax_total', 'pax_adults', 'pax_children'], true) ? self::intOrNull($fields[$key]['value']) : $fields[$key]['value']);
            $this->db->query("UPDATE tf_intake SET `{$column}` = ?, fields_json = ?, updated_at = ?, updated_by = ? WHERE id = ?", [$stored, json_encode($fields, JSON_UNESCAPED_UNICODE), $now, $this->user, $id]);
        } else {
            $this->db->query('UPDATE tf_intake SET fields_json = ?, updated_at = ?, updated_by = ? WHERE id = ?', [json_encode($fields, JSON_UNESCAPED_UNICODE), $now, $this->user, $id]);
        }
        if ($key === 'agent_name' || $key === 'agent_email' || $key === 'agent_country') {
            $this->resolveAgent($id);
        }
        // A date change moves the night count, and the night count is never typed.
        if ($key === 'arrival_date' || $key === 'departure_date' || $key === 'nights') {
            $this->syncNights($id);
            $this->costCrew($id);
        }
        // A new arrival date moves the nights with it, and every hotel rate is looked up
        // against the night's date — so the nights are dated again and the hotels re-checked.
        if ($key === 'arrival_date' && $this->dateNights($id, $oldArrival) > 0) {
            $this->checkHotels($id);
            $this->checkServices($id);
        }
        // schema 32: the dates decide which lines are nights and which day is the departure day. What was
        // on the old departure day moves with it, and the legs are planned again for the new shape.
        if ($key === 'arrival_date' || $key === 'departure_date') {
            $now2 = $this->db->query('SELECT arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray() ?: [];
            $this->followDepartureDay($id, $oldNights, TourDates::nights($now2['arrival_date'] ?? null, $now2['departure_date'] ?? null));
            $this->planSectors($id);
            $this->replanAccom($id);   // the crew's rooms follow the nights of the stay
        }
        // The party size decides the vehicle, so entering it is enough to choose one — and it
        // is the ceiling every per-person service is costed at, so those are priced again.
        if ($key === 'pax_total') {
            $this->planVehicle($id);
            $this->checkServices($id);
        }
        if ($key === 'agent_country') {
            $this->planCrew($id);
        }
        // A meal basis typed here (the agent said it on the phone, say) reaches every night
        // that has no meal plan of its own or took its meal plan from the earlier basis. A
        // night whose meal plan was written on its line, or set by a person, is not touched.
        if ($key === 'meal_basis') {
            $this->applyMealBasis($id, $fields['meal_basis']['value'], $fields['meal_basis']['basis']);
        }
        // schema 40: the lunch places the system picked follow the party (adults and children) and the nights' meal plans
        if (in_array($key, ['pax_total', 'pax_adults', 'pax_children', 'meal_basis'], true)) {
            $this->autoLunches($id);
        }
        return $this->get($id);
    }

    /**
     * Put a tour-wide meal basis on the nights that have none of their own. Nights whose
     * meal plan came from their own line ('day') or from a person ('human') keep it; nights
     * that carry the previous basis ('tour') follow the new one, and are cleared when the
     * basis is removed. Each night re-reads its published rate afterwards.
     */
    public function applyMealBasis(int $id, ?string $meal, ?string $quote): int
    {
        if (!$this->hasMealSource()) {
            return 0;
        }
        $meal = $meal === null ? null : strtoupper(str_replace(['/', ' ', '.'], '', trim($meal)));
        if ($meal !== null && !in_array($meal, ['RO', 'BB', 'HB', 'FB', 'AI'], true)) {
            throw new \RuntimeException('The meal basis must be RO, BB, HB, FB or AI.');
        }
        $n = 0;
        foreach ($this->db->query('SELECT id, meal_plan, meal_source FROM tf_intake_day WHERE intake_id = ? ORDER BY day_no', [$id])->getResultArray() as $d) {
            $fromBasis = (string) $d['meal_source'] === 'tour';
            // a meal plan from the night's own line, from a person, or of unknown origin
            // (recorded before schema 15) is the night's own and stays
            if ($d['meal_plan'] !== null && !$fromBasis) {
                continue;
            }
            if ($meal === null) {
                if (!$fromBasis) {
                    continue;
                }
                $this->db->query('UPDATE tf_intake_day SET meal_plan = NULL, meal_source = NULL, meal_quote = NULL WHERE id = ?', [$d['id']]);
            } else {
                $this->db->query('UPDATE tf_intake_day SET meal_plan = ?, meal_source = ?, meal_quote = ? WHERE id = ?', [$meal, 'tour', self::cut($quote, 190), $d['id']]);
            }
            $n++;
            $this->checkHotels($id, (int) $d['id']);
        }
        return $n;
    }

    /** Schema 15 records where a night's meal plan came from; older tables do not. */
    /** Whether the day rows carry a person's own wording for the quotation (schema 28). */
    private ?bool $clientTextCol = null;
    private function hasClientText(): bool
    {
        if ($this->clientTextCol === null) {
            try {
                $this->clientTextCol = (bool) $this->db->query("SHOW COLUMNS FROM tf_intake_day LIKE 'client_text'")->getResultArray();
            } catch (\Throwable $e) {
                $this->clientTextCol = false;
            }
        }
        return $this->clientTextCol;
    }

    private function hasMealSource(): bool
    {
        if ($this->hasMealSource === null) {
            try {
                $this->hasMealSource = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_day` LIKE 'meal_source'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasMealSource = false;
            }
        }
        return $this->hasMealSource;
    }

    /**
     * Add a night by hand, at the end of whatever is there.
     *
     * The date is worked out from the arrival date and the night number when the arrival
     * date is known — that is arithmetic, not a guess — and left empty otherwise, for a
     * person to set. Everything else starts empty rather than being filled in hopefully.
     */
    public function addDay(int $id, array $d = []): array
    {
        $this->assertOpen($id);
        $it = $this->db->query('SELECT arrival_date FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            throw new \RuntimeException('That inquiry does not exist.');
        }
        $next = (int) ($this->db->query('SELECT COALESCE(MAX(day_no), 0) + 1 AS n FROM tf_intake_day WHERE intake_id = ?', [$id])->getRowArray()['n'] ?? 1);
        // schema 32: the dates decide the nights. A night past them would sit on the departure day, so
        // adding one moves the departure a day later — only when the person has said so (extend).
        $dates  = $this->db->query('SELECT arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray() ?: [];
        $stayN  = TourDates::nights($dates['arrival_date'] ?? null, $dates['departure_date'] ?? null);
        $extend = false;
        if ($stayN !== null && $next > $stayN) {
            if (empty($d['extend'])) {
                throw new \InvalidArgumentException('The dates give ' . $stayN . ' night(s), so a night ' . $next . ' would fall on the departure day (' . date('j M Y', strtotime((string) $dates['departure_date'])) . '). Adding it moves the departure to ' . date('j M Y', strtotime(TourDates::dateOf($dates['arrival_date'], $next + 1))) . ' — confirm that on the screen.');
            }
            $extend = true;
        }
        $date = self::dateOrNull($d['day_date'] ?? null);
        if ($date === null && !empty($it['arrival_date'])) {
            $date = date('Y-m-d', strtotime($it['arrival_date'] . ' +' . ($next - 1) . ' day'));
        }

        $meal = strtoupper(str_replace(['/', ' '], '', (string) ($d['meal_plan'] ?? '')));
        $meal = in_array($meal, ['RO', 'BB', 'HB', 'FB', 'AI'], true) ? $meal : null;
        $withSource = $this->hasMealSource();
        $this->db->query(
            'INSERT INTO tf_intake_day (intake_id, day_no, day_date, area, hotel_name_raw, meal_plan, rooms, nights, rate_state, origin' . ($withSource ? ', meal_source' : '') . ")
             VALUES (?,?,?,?,?,?,?,1,'nothing_named','human'" . ($withSource ? ',?' : '') . ')',
            array_merge([
                $id, $next, $date, self::cut($d['area'] ?? null, 160), self::cut($d['hotel_name_raw'] ?? null, 190),
                $meal, self::intOrNull($d['rooms'] ?? null),
            ], $withSource ? [$meal === null ? null : 'human'] : [])
        );
        $dayId = (int) $this->db->insertID();
        if ($extend) {
            // the stay is a night longer: the departure moves to the day after the new night
            $this->saveField($id, 'departure_date', TourDates::dateOf($dates['arrival_date'], $next + 1), 'Moved a day later: night ' . $next . ' added by ' . ($this->actorName() ?: 'a colleague'));
        }
        if (!empty($d['hotel_name_raw'])) {
            $this->checkHotels($id, $dayId);
        }
        $this->planSectors($id);
        $this->touch($id);
        return $this->get($id);
    }

    public function deleteDay(int $id, int $dayId): array
    {
        $this->assertOpen($id);
        $row = $this->db->query('SELECT * FROM tf_intake_day WHERE id = ? AND intake_id = ?', [$dayId, $id])->getRowArray();
        // schema 32: a line on the departure day is not a night — taking it off keeps what is on it,
        // on the departure day, exactly as Make it the departure day does
        if ($row && $this->hasDatesRule()) {
            $dates = $this->db->query('SELECT arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray() ?: [];
            if (TourDates::beyond($row, TourDates::nights($dates['arrival_date'] ?? null, $dates['departure_date'] ?? null), $dates['departure_date'] ?? null)) {
                return $this->makeDepartureDay($id, $dayId);
            }
        }
        if ($row) {
            // the day's own services and legs go with it, rather than being left pointing at
            // a night that is no longer there
            $this->db->query('DELETE FROM tf_intake_service WHERE intake_id = ? AND day_no = ?', [$id, (int) $row['day_no']]);
            $this->db->query('DELETE FROM tf_intake_sector WHERE intake_id = ? AND day_no = ?', [$id, (int) $row['day_no']]);
            $this->db->query('DELETE FROM tf_intake_day WHERE id = ? AND intake_id = ?', [$dayId, $id]);
        }
        $this->planSectors($id);
        $this->touch($id);
        return $this->get($id);
    }

    // ---- the departure day and the dates (schema 32) ------------------------------------

    /**
     * What was on the old departure day moves with it when the dates change the nights: its services
     * and activities follow to the new departure day. Only when the old departure day had no line of
     * its own — a line written there is a person's to settle, and keeps what is on it.
     */
    private function followDepartureDay(int $id, ?int $oldNights, ?int $newNights): void
    {
        if ($oldNights === null || $newNights === null || $oldNights === $newNights) {
            return;
        }
        $oldDep = $oldNights + 1;
        $newDep = $newNights + 1;
        if ($this->db->query('SELECT id FROM tf_intake_day WHERE intake_id = ? AND day_no = ? LIMIT 1', [$id, $oldDep])->getRowArray()) {
            return;
        }
        $this->db->query('UPDATE tf_intake_service SET day_no = ? WHERE intake_id = ? AND day_no = ?', [$newDep, $id, $oldDep]);
        $this->db->query('UPDATE tf_intake_excursion SET day_no = ? WHERE intake_id = ? AND day_no = ?', [$newDep, $id, $oldDep]);
    }

    /**
     * A line that falls on the departure day is the departure day: its services, activities and the
     * legs a person typed go to the departure day, its own wording for the client and the agent's
     * words and note become the departure day's (where it has none yet), and the line itself goes.
     * Nothing on it is lost, and the history keeps the line as it was.
     */
    public function makeDepartureDay(int $id, int $dayId): array
    {
        $this->assertOpen($id);
        $row = $this->db->query('SELECT * FROM tf_intake_day WHERE id = ? AND intake_id = ?', [$dayId, $id])->getRowArray();
        if (!$row) {
            throw new \InvalidArgumentException('That line is not part of this inquiry.');
        }
        $it = $this->db->query('SELECT * FROM tf_intake WHERE id = ?', [$id])->getRowArray() ?: [];
        $n  = TourDates::nights($it['arrival_date'] ?? null, $it['departure_date'] ?? null);
        if ($n === null) {
            throw new \InvalidArgumentException('Set both the arrival and the departure date first: they say which day is the departure day.');
        }
        if (!TourDates::beyond($row, $n, $it['departure_date'] ?? null)) {
            throw new \InvalidArgumentException('Night ' . (int) $row['day_no'] . ' is inside the stay the dates give (' . $n . ' night' . ($n === 1 ? '' : 's') . '), so it is a night. If the tour is shorter, change the departure date first.');
        }
        $depNo = $n + 1;
        $from  = (int) $row['day_no'];
        if ($from !== $depNo) {
            $this->db->query('UPDATE tf_intake_service SET day_no = ? WHERE intake_id = ? AND day_no = ?', [$depNo, $id, $from]);
            $this->db->query('UPDATE tf_intake_excursion SET day_no = ? WHERE intake_id = ? AND day_no = ?', [$depNo, $id, $from]);
            $this->db->query("UPDATE tf_intake_sector SET day_no = ? WHERE intake_id = ? AND day_no = ? AND origin = 'human'", [$depNo, $id, $from]);
        }
        if ($this->hasDatesRule()) {
            $sets = [];
            $bind = [];
            $own = trim((string) ($row['client_text'] ?? ''));
            if ($own !== '' && trim((string) ($it['departure_text'] ?? '')) === '') {
                $sets[] = 'departure_text = ?';
                $bind[] = self::cut($own, 2000);
            }
            $said = trim((string) ($row['narrative'] ?? ''));
            if ($said !== '' && trim((string) ($it['departure_narrative'] ?? '')) === '') {
                $sets[] = 'departure_narrative = ?';
                $bind[] = $said;
            }
            $note = trim((string) ($row['special_note'] ?? ''));
            if ($note !== '') {
                $had = trim((string) ($it['departure_note'] ?? ''));
                $sets[] = 'departure_note = ?';
                $bind[] = self::cut($had !== '' && !str_contains($had, $note) ? $had . ' ' . $note : ($had !== '' ? $had : $note), 500);
            }
            if ($sets) {
                $bind[] = $id;
                $this->db->query('UPDATE tf_intake SET ' . implode(', ', $sets) . ' WHERE id = ?', $bind);
            }
        }
        // the line itself: the planner's legs on it are planned again below, the person's moved above
        $this->db->query("DELETE FROM tf_intake_sector WHERE intake_id = ? AND day_no = ? AND origin = 'auto'", [$id, $from]);
        $this->db->query('DELETE FROM tf_intake_day WHERE id = ? AND intake_id = ?', [$dayId, $id]);
        $this->planSectors($id);
        $this->replanAccom($id);   // no crew room on the departure day either
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * The party really stays that night: the departure moves to the day after the line, so it is a
     * night of the stay. The change is the departure date's, recorded like any date typed by a person.
     */
    public function extraNight(int $id, int $dayId): array
    {
        $this->assertOpen($id);
        $row = $this->db->query('SELECT * FROM tf_intake_day WHERE id = ? AND intake_id = ?', [$dayId, $id])->getRowArray();
        if (!$row) {
            throw new \InvalidArgumentException('That line is not part of this inquiry.');
        }
        $it = $this->db->query('SELECT arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray() ?: [];
        $n  = TourDates::nights($it['arrival_date'] ?? null, $it['departure_date'] ?? null);
        if ($n === null) {
            throw new \InvalidArgumentException('Set both the arrival and the departure date first.');
        }
        if (!TourDates::beyond($row, $n, $it['departure_date'] ?? null)) {
            throw new \InvalidArgumentException('Night ' . (int) $row['day_no'] . ' is already a night of the stay.');
        }
        $newDep = TourDates::dateOf($it['arrival_date'], (int) $row['day_no'] + 1);
        return $this->saveField($id, 'departure_date', $newDep, 'Moved to ' . date('j M Y', strtotime((string) $newDep)) . ': night ' . (int) $row['day_no'] . ' kept as an extra night by ' . ($this->actorName() ?: 'a colleague'));
    }

    /** The departure day's own wording for the client, and a note for the desks (late check-out, lunch...). */
    public function saveDeparture(int $id, array $d): array
    {
        $this->assertOpen($id);
        if (!$this->hasDatesRule()) {
            throw new \RuntimeException('The departure day\'s wording needs the schema 32 upgrade: open Tour Inquiry once as the administrator.');
        }
        $sets = [];
        $bind = [];
        if (array_key_exists('text', $d)) {
            $sets[] = 'departure_text = ?';
            $bind[] = self::cut($d['text'], 2000);
        }
        if (array_key_exists('note', $d)) {
            $sets[] = 'departure_note = ?';
            $bind[] = self::cut($d['note'], 500);
        }
        if ($sets) {
            $bind[] = $id;
            $this->db->query('UPDATE tf_intake SET ' . implode(', ', $sets) . ' WHERE id = ?', $bind);
        }
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * The departure day's programme as the email put it, amended by an Edit Request (schema 33). The
     * email itself is not touched: this is the working copy of its words for that day. The planning
     * reads the day's visits from their own lines, not from these words, so nothing else moves with it.
     */
    public function saveDepartureNarrative(int $id, string $words): array
    {
        $this->assertOpen($id);
        if (!$this->hasDatesRule()) {
            throw new \RuntimeException('The departure day\'s wording needs the schema 32 upgrade: open Tour Inquiry once as the administrator.');
        }
        $words = trim($words);
        $this->db->query('UPDATE tf_intake SET departure_narrative = ? WHERE id = ?', [$words !== '' ? $words : null, $id]);
        $this->touch($id);
        return $this->get($id);
    }

    /** The inquiry's dates stand, though the email says otherwise: kept with the pair confirmed. */
    public function confirmDates(int $id): array
    {
        $this->assertOpen($id);
        $it = $this->get($id);
        $dc = $it['dates_check'] ?? null;
        if (!$dc || !$dc['differs']) {
            throw new \InvalidArgumentException('The dates agree with the email; there is nothing to confirm.');
        }
        $key = TourDates::confirmKey($it['arrival_date'] ?? null, $it['departure_date'] ?? null, $dc['email_arrival'], $dc['email_departure']);
        $this->db->query('UPDATE tf_intake SET dates_confirmed_key = ?, dates_confirmed_by = ?, dates_confirmed_at = ? WHERE id = ?', [$key, self::cut($this->actorName() ?: ($this->user ?: 'a colleague'), 100), date('Y-m-d H:i:s'), $id]);
        $this->touch($id);
        return $this->get($id);
    }

    /** The email's dates, taken: the arrival first, so the nights move with it and every hotel is checked again. */
    public function useEmailDates(int $id): array
    {
        $this->assertOpen($id);
        $it = $this->get($id);
        $dc = $it['dates_check'] ?? null;
        if (!$dc) {
            throw new \InvalidArgumentException('There are no dates from the email on file.');
        }
        // schema 33: a date an applied Edit Request settled is the later word and stays; only the
        // date still in question is taken from the email
        $keepArr = !empty($dc['arrival']['edit_request']);
        $keepDep = !empty($dc['departure']['edit_request']);
        if (!$keepArr && $dc['email_arrival'] !== null && $dc['email_arrival'] !== ($it['arrival_date'] ?? null)) {
            $this->saveField($id, 'arrival_date', $dc['email_arrival'], 'Taken from the email');
        }
        if (!$keepDep && $dc['email_departure'] !== null && $dc['email_departure'] !== ($it['departure_date'] ?? null)) {
            $this->saveField($id, 'departure_date', $dc['email_departure'], 'Taken from the email');
        }
        return $this->get($id);
    }

    /** A blank line for every night the dates give that has none, dated, for a person to fill in. */
    public function addMissingNights(int $id): array
    {
        $this->assertOpen($id);
        $it = $this->get($id);
        $missing = $it['missing_nights'] ?? [];
        if ($missing === []) {
            return $it;
        }
        foreach ($missing as $n) {
            $this->db->query(
                "INSERT INTO tf_intake_day (intake_id, day_no, day_date, nights, rate_state, origin) VALUES (?,?,?,1,'nothing_named','human')",
                [$id, (int) $n, TourDates::dateOf($it['arrival_date'], (int) $n)]
            );
        }
        $this->planSectors($id);
        $this->replanAccom($id);
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * The crew's rooms worked out again after the nights changed (schema 32) — only where a room is
     * required: a line nobody has asked for is left exactly as it is, words and all.
     */
    private function replanAccom(int $id): void
    {
        try {
            if ($this->db->query('SELECT id FROM tf_intake_accom WHERE intake_id = ? AND day_no = 0 AND required = 1 LIMIT 1', [$id])->getRowArray()) {
                $this->planAccom($id);
            }
        } catch (\Throwable $e) {
            // no accommodation table yet: nothing to follow
        }
    }

    /** Add or change a passenger name. Names only — no passport, no date of birth. */
    public function savePassenger(int $id, ?int $paxId, array $p): array
    {
        $this->assertOpen($id);
        $name = self::cut($p['full_name'] ?? null, 160);
        if ($name === null || trim($name) === '') {
            throw new \InvalidArgumentException('A passenger needs a name.');
        }
        $room = self::cut($p['room_type'] ?? null, 20);
        $note = self::cut($p['note'] ?? null, 160);
        $grp  = array_key_exists('group_id', $p) ? self::intOrNull($p['group_id']) : null;
        if ($grp !== null && !$this->db->query('SELECT id FROM tf_intake_group WHERE id = ? AND intake_id = ?', [$grp, $id])->getRowArray()) {
            throw new \InvalidArgumentException('That group is not on this inquiry.');
        }

        if ($paxId) {
            $own = $this->db->query('SELECT id FROM tf_intake_pax WHERE id = ? AND intake_id = ?', [$paxId, $id])->getRowArray();
            if (!$own) {
                throw new \InvalidArgumentException('That name is not on this inquiry.');
            }
            $this->db->query("UPDATE tf_intake_pax SET full_name = ?, room_type = ?, note = ?, origin = 'human' WHERE id = ?", [$name, $room, $note, $paxId]);
            if (array_key_exists('group_id', $p)) {
                $this->db->query('UPDATE tf_intake_pax SET group_id = ? WHERE id = ?', [$grp, $paxId]);
            }
        } else {
            $next = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM tf_intake_pax WHERE intake_id = ?', [$id])->getRowArray()['n'] ?? 0);
            $this->db->query("INSERT INTO tf_intake_pax (intake_id, full_name, room_type, note, group_id, origin, sort_order) VALUES (?,?,?,?,?,'human',?)", [$id, $name, $room, $note, $grp, $next]);
        }
        $this->touch($id);
        return $this->get($id);
    }

    public function deletePassenger(int $id, int $paxId): array
    {
        $this->assertOpen($id);
        $this->db->query('DELETE FROM tf_intake_pax WHERE id = ? AND intake_id = ?', [$paxId, $id]);
        $this->touch($id);
        return $this->get($id);
    }

    /** Add or change a request or question by hand. */
    public function saveRequest(int $id, ?int $reqId, array $r): array
    {
        $this->assertOpen($id);
        $title = self::cut($r['title'] ?? null, 255);
        if ($title === null || trim($title) === '') {
            throw new \InvalidArgumentException('A request needs a short title.');
        }
        $kind  = ($r['kind'] ?? '') === 'question' ? 'question' : 'request';
        $quote = self::cut($r['quote_text'] ?? null, 1000);

        if ($reqId) {
            $own = $this->db->query('SELECT id FROM tf_intake_request WHERE id = ? AND intake_id = ?', [$reqId, $id])->getRowArray();
            if (!$own) {
                throw new \InvalidArgumentException('That request is not on this inquiry.');
            }
            $this->db->query("UPDATE tf_intake_request SET kind = ?, title = ?, quote_text = ?, origin = 'human' WHERE id = ?", [$kind, $title, $quote, $reqId]);
        } else {
            $next = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM tf_intake_request WHERE intake_id = ?', [$id])->getRowArray()['n'] ?? 0);
            $this->db->query("INSERT INTO tf_intake_request (intake_id, kind, title, quote_text, status, origin, sort_order) VALUES (?,?,?,?,'open','human',?)", [$id, $kind, $title, $quote, $next]);
        }
        $this->touch($id);
        return $this->get($id);
    }

    public function deleteRequest(int $id, int $reqId): array
    {
        $this->assertOpen($id);
        $this->db->query('DELETE FROM tf_intake_request WHERE id = ? AND intake_id = ?', [$reqId, $id]);
        $this->touch($id);
        return $this->get($id);
    }

    /** Correct one requested night, then re-check it against the rates panel. */
    public function saveDay(int $id, int $dayId, array $d): array
    {
        $this->assertOpen($id);
        $row = $this->db->query('SELECT * FROM tf_intake_day WHERE id = ? AND intake_id = ?', [$dayId, $id])->getRowArray();
        if (!$row) {
            throw new \RuntimeException('That night is not part of this inquiry.');
        }
        // schema 32: a line on the departure day is not a night, so it takes no hotel until a person
        // says it is an extra night — no "Departure - BIA" hotel is ever needed for the departure
        if (array_key_exists('hotel_name_raw', $d) && trim((string) $d['hotel_name_raw']) !== '') {
            $dates = $this->db->query('SELECT arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray() ?: [];
            $probe = $row;
            if (array_key_exists('day_date', $d)) {
                $probe['day_date'] = self::dateOrNull($d['day_date']);
            }
            if (TourDates::beyond($probe, TourDates::nights($dates['arrival_date'] ?? null, $dates['departure_date'] ?? null), $dates['departure_date'] ?? null)) {
                throw new \InvalidArgumentException('Night ' . (int) $row['day_no'] . ' falls on the departure day, and the departure day has no hotel: the transfer to the airport is added by itself. Press Make it the departure day — or, if the party really stays that night, It\'s an extra night, and then choose the hotel.');
            }
        }
        $sets = [];
        $bind = [];
        foreach (['area' => 160, 'hotel_name_raw' => 190, 'narrative' => 4000, 'special_note' => 500, 'client_text' => 2000] as $f => $len) {
            if (array_key_exists($f, $d) && ($f !== 'client_text' || $this->hasClientText())) {
                $sets[] = "`{$f}` = ?";
                $bind[] = self::cut($d[$f], $len);
            }
        }
        if (array_key_exists('meal_plan', $d)) {
            $m = strtoupper(str_replace(['/', ' '], '', (string) $d['meal_plan']));
            $m = in_array($m, ['RO', 'BB', 'HB', 'FB', 'AI'], true) ? $m : null;
            if ($m !== (string) $row['meal_plan'] || $m === null) {
                $sets[] = '`meal_plan` = ?';
                $bind[] = $m;
                // a person's choice is the night's own from now on; the basis no longer speaks for it
                if ($this->hasMealSource()) {
                    $sets[] = '`meal_source` = ?';
                    $bind[] = $m === null ? null : 'human';
                    $sets[] = '`meal_quote` = NULL';
                }
            }
        }
        if (array_key_exists('rooms', $d)) {
            $sets[] = '`rooms` = ?';
            $bind[] = self::intOrNull($d['rooms']);
        }
        $hotelChanged = array_key_exists('hotel_name_raw', $d) && trim((string) $d['hotel_name_raw']) !== trim((string) $row['hotel_name_raw']);
        if ($hotelChanged) {
            // a different hotel means a different set of room types: the old choice does not carry over (the dialog's
            // list was the old hotel's); the new hotel's normal type fills in when the night is checked
            $sets[] = '`room_type_id` = NULL';
            if ($this->hasRoomTypeAuto()) {
                $sets[] = '`room_type_auto` = 0';
            }
        } elseif (array_key_exists('room_type_id', $d)) {
            $sets[] = '`room_type_id` = ?';
            $bind[] = self::intOrNull($d['room_type_id']);
            if ($this->hasRoomTypeAuto()) {
                $sets[] = '`room_type_auto` = 0';   // chosen by a person for this booking — the master's choice no longer (cleared: the master's normal type returns)
            }
        }
        if (array_key_exists('room_type_text', $d) && $this->hasRoomTypeAuto()) {
            // tf-walkfix-r1: the wording this booking uses for the room type, when it differs from the master's name
            $txt = self::cut($d['room_type_text'], 120);
            $sets[] = '`room_type_text` = ?';
            $bind[] = $txt !== null && trim($txt) !== '' ? trim($txt) : null;
        }
        if (array_key_exists('day_date', $d)) {
            $sets[] = '`day_date` = ?';
            $bind[] = self::dateOrNull($d['day_date']);
        }
        // A night somebody has corrected is theirs from now on, so reading the email
        // again leaves it alone instead of replacing it with the version they fixed.
        if ($sets) {
            $sets[] = "`origin` = 'human'";
        }
        if ($sets) {
            $bind[] = $dayId;
            $this->db->query('UPDATE tf_intake_day SET ' . implode(', ', $sets) . ' WHERE id = ?', $bind);
        }
        $this->touch($id);
        $this->checkHotels($id, $dayId);
        // Moving a night to a different area changes what is driven, so the legs are
        // re-read — except on any day somebody has already set the driving for by hand.
        if (array_key_exists('area', $d) || array_key_exists('hotel_name_raw', $d)) {
            $this->planSectors($id);
        } elseif (array_key_exists('meal_plan', $d)) {
            $this->autoLunches($id);   // schema 40: a night on FB gives lunch — the system's own lunch place goes
        }
        return $this->get($id);
    }

    public function answerRequest(int $id, int $reqId, string $status, ?string $answer = null): array
    {
        if (!in_array($status, ['open', 'answered', 'done', 'dropped'], true)) {
            throw new \RuntimeException('Unknown status.');
        }
        $this->assertOpen($id);
        $now = date('Y-m-d H:i:s');
        $this->db->query(
            'UPDATE tf_intake_request SET status = ?, answer = ?, answered_by = ?, answered_at = ? WHERE id = ? AND intake_id = ?',
            [$status, self::cut($answer, 1000), $status === 'open' ? null : $this->user, $status === 'open' ? null : $now, $reqId, $id]
        );
        $this->touch($id);
        return $this->get($id);
    }

    public function setStatus(int $id, string $status, ?string $note = null): array
    {
        if (!in_array($status, ['new', 'working', 'quoted', 'declined'], true)) {
            throw new \RuntimeException('An inquiry can be set to new, working, quoted or declined here. Confirming it is its own button, and puts the tour on the desks.');
        }
        $this->assertOpen($id);
        $this->db->query('UPDATE tf_intake SET status = ?, note = COALESCE(?, note), updated_at = ?, updated_by = ? WHERE id = ?', [$status, $note, date('Y-m-d H:i:s'), $this->user, $id]);
        return $this->get($id);
    }

    /**
     * A confirmed inquiry is the record of what was sold: read-only. Every hand edit
     * comes through here first. Management opens it again from the Tour Board (Amend),
     * and Confirm afterwards rebuilds the cards that have not moved.
     */
    public function assertOpen(int $id): void
    {
        $r = $this->db->query('SELECT status, archived_at FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if ($r && !empty($r['archived_at'])) {
            throw new \RuntimeException('This inquiry is archived and read-only. Management can restore it from the ⋯ Manage menu.');
        }
        if ($r && in_array((string) $r['status'], ['confirmed', 'cancelled'], true)) {
            throw new \RuntimeException($r['status'] === 'confirmed'
                ? 'This inquiry is confirmed and read-only. Management can open it for amendment from the Tour Board.'
                : 'This inquiry\'s tour was cancelled. Management can reopen it from the Tour Board.');
        }
    }

    /**
     * Reading the email again is switched off on an inquiry once an Edit Request has been applied to
     * it (schema 33): a new reading rebuilds what came from the email and could undo the amendment.
     * The original email is never changed either way.
     */
    public function assertReadable(int $id): void
    {
        $why = EditRequests::readBlocked($this->db, $id);
        if ($why !== null) {
            throw new \RuntimeException($why);
        }
    }

    /** The strip on a confirmed inquiry: where its hotel and transport cards stand. Counts only. */
    private function tourProgress(int $id): ?array
    {
        try {
            if (!$this->db->query("SHOW TABLES LIKE 'tf_tour'")->getResultArray()) {
                return null;
            }
            return (new TourService($this->db, $this->user, $this))->progressFor($id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function touch(int $id): void
    {
        $this->db->query('UPDATE tf_intake SET updated_at = ?, updated_by = ? WHERE id = ?', [date('Y-m-d H:i:s'), $this->user, $id]);
    }

    // =====================================================================
    //  The agent, and the market that decides which rates apply
    // =====================================================================

    /** Match the agent against the supplier ledger, and the country against the market table. */
    // =====================================================================
    //  Vehicle and crew — two separate things, on purpose
    // =====================================================================

    public function planner(): VehiclePlanner
    {
        return $this->planner ??= new VehiclePlanner($this->db, $this->transport());
    }

    /** Master Files → Transport Rates: the only source of a rate per kilometre. */
    public function transport(): TransportRates
    {
        return $this->transport ??= new TransportRates($this->db, $this->user);
    }

    /** Master Files → Mileage Allowances: the stay day, the garage run, the city tours. */
    public function allowances(): MileageAllowances
    {
        return $this->allowances ??= new MileageAllowances($this->db, $this->user);
    }

    /**
     * The driver and guide roles on offer, with whatever day rate the business has set.
     * A role is a category (Driver, Chauffeur guide, National guide …) in a language, and
     * the bata belongs to the pair. Active ones only, unless the master screen asks for all.
     */
    public function crewRoles(bool $all = false): array
    {
        try {
            $rows = $this->db->query('SELECT * FROM tf_crew_role' . ($all ? '' : ' WHERE active = 1') . ' ORDER BY kind, sort_order, name')->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        return array_map(static fn ($r) => [
            'code' => (string) $r['code'], 'name' => (string) $r['name'], 'kind' => (string) $r['kind'],
            'category' => trim((string) ($r['category'] ?? '')) ?: ((string) $r['kind'] === 'guide' ? 'Guide' : 'Driver'),
            'language' => trim((string) ($r['language'] ?? '')) ?: null,
            'bata' => ($r['bata'] === null || $r['bata'] === '') ? null : (float) $r['bata'],
            'currency' => (string) $r['currency'], 'note' => $r['note'],
            'active' => (int) ($r['active'] ?? 1), 'sort_order' => (int) ($r['sort_order'] ?? 0),
        ], $rows);
    }

    /**
     * Add or change one role in the crew master — Master Files → Crew Bata, Admin's screen.
     * The kind (driver or guide) is fixed once the code exists: the quotation screen keeps
     * a driver section and a guide section, and a line cannot move between them by a
     * master edit. A changed bata goes through saveCrewBata, so it reaches every inquiry
     * still being worked on and is written to the history the same way.
     */
    public function saveCrewRole(array $r): array
    {
        $code = strtoupper(trim((string) ($r['code'] ?? '')));
        $code = (string) preg_replace('/[^A-Z0-9_\-]/', '', $code);
        if ($code === '' || strlen($code) > 20) {
            throw new \InvalidArgumentException('A role needs a short code — letters, numbers, dashes and underscores, up to 20.');
        }
        $kind = strtolower(trim((string) ($r['kind'] ?? '')));
        $was  = $this->db->query('SELECT * FROM tf_crew_role WHERE code = ?', [$code])->getRowArray();
        if ($was) {
            if ($kind !== '' && $kind !== (string) $was['kind']) {
                throw new \InvalidArgumentException('A ' . $was['kind'] . ' role stays a ' . $was['kind'] . ' role. Add a new role for the other kind instead.');
            }
            $kind = (string) $was['kind'];
        } elseif (!in_array($kind, ['driver', 'guide'], true)) {
            throw new \InvalidArgumentException('A role is a driver or a guide.');
        }
        $category = self::cut(trim((string) ($r['category'] ?? '')), 60);
        if ($category === null || $category === '') {
            throw new \InvalidArgumentException('A role needs a category — Driver, Chauffeur guide, National guide, and so on.');
        }
        $language = self::cut(trim((string) ($r['language'] ?? '')), 40);
        $language = ($language === null || $language === '') ? null : $language;
        $name     = self::cut(trim((string) ($r['name'] ?? '')), 80);
        if ($name === null || $name === '') {
            $name = ($language !== null ? $language . ' speaking ' : '') . strtolower($category);
            $name = ucfirst($name);
        }
        $bata = (!array_key_exists('bata', $r) || $r['bata'] === null || $r['bata'] === '') ? null : round((float) $r['bata'], 2);
        if ($bata !== null && $bata < 0) {
            throw new \InvalidArgumentException('A bata cannot be negative.');
        }
        $cur    = strtoupper(substr(trim((string) ($r['currency'] ?? ($was['currency'] ?? 'LKR'))), 0, 3)) ?: 'LKR';
        $note   = self::cut($r['note'] ?? null, 255);
        $active = array_key_exists('active', $r) ? (!empty($r['active']) ? 1 : 0) : (int) ($was['active'] ?? 1);
        $sort   = array_key_exists('sort_order', $r) && is_numeric($r['sort_order']) ? (int) $r['sort_order'] : (int) ($was['sort_order'] ?? 0);
        $hasCat = (bool) $this->db->query("SHOW COLUMNS FROM `tf_crew_role` LIKE 'category'")->getResultArray();

        if ($was) {
            $this->db->query(
                'UPDATE tf_crew_role SET name = ?, ' . ($hasCat ? 'category = ?, ' : '') . 'language = ?, note = ?, active = ?, sort_order = ? WHERE code = ?',
                array_merge([$name], $hasCat ? [$category] : [], [$language, $note, $active, $sort, $code])
            );
            $now_ = ['name' => $name, 'category' => $category, 'language' => $language, 'note' => $note, 'active' => $active];
            $changes = [];
            foreach ($now_ as $k => $v) {
                if ((string) ($was[$k] ?? '') !== (string) ($v ?? '')) {
                    $changes[] = ['what' => $code . ' ' . $k, 'from' => $was[$k] ?? null, 'to' => $v];
                }
            }
            if ($changes !== []) {
                Activity::master($this->db, $this->user, 'crew_role', 0, 'Crew Bata: ' . $code . ' (' . $name . ') changed', $changes, $this->actorId, $this->actorName);
                // an open inquiry shows the role as the master now names it; a quoted figure is not touched here
                $this->db->query(
                    "UPDATE tf_intake_crew c JOIN tf_intake i ON i.id = c.intake_id SET c.role_name = ?, c.language = ? WHERE c.role_code = ? AND i.status IN ('new','working','quoted')",
                    [$name, $language, $code]
                );
            }
            $bataWas = ($was['bata'] === null || $was['bata'] === '') ? null : (float) $was['bata'];
            if ($bata !== $bataWas || $cur !== (string) $was['currency']) {
                $this->saveCrewBata($code, $bata, $cur);
            }
        } else {
            $this->db->query(
                'INSERT INTO tf_crew_role (code, name, kind, ' . ($hasCat ? 'category, ' : '') . 'language, bata, currency, note, active, sort_order) VALUES (?,?,?,' . ($hasCat ? '?,' : '') . '?,?,?,?,?,?)',
                array_merge([$code, $name, $kind], $hasCat ? [$category] : [], [$language, $bata, $cur, $note, $active, $sort])
            );
            Activity::master($this->db, $this->user, 'crew_role', 0, 'Crew Bata: ' . $code . ' (' . $name . ') added' . ($bata !== null ? ' at ' . $cur . ' ' . number_format($bata, 2) : ', no bata yet'),
                [['what' => $code . ' kind', 'from' => null, 'to' => $kind], ['what' => $code . ' category', 'from' => null, 'to' => $category], ['what' => $code . ' language', 'from' => null, 'to' => $language],
                 ['what' => $code . ' bata', 'from' => null, 'to' => $bata], ['what' => $code . ' currency', 'from' => null, 'to' => $cur]], $this->actorId, $this->actorName);
        }
        foreach ($this->crewRoles(true) as $row) {
            if ($row['code'] === $code) {
                return $row;
            }
        }
        return ['code' => $code];
    }

    /**
     * Take a role off the master. One that is on any inquiry — open or quoted — is kept on
     * file and switched off instead, so every line that names it still says what it was.
     *
     * @return array{deleted:bool,deactivated:bool,used:int}
     */
    public function deleteCrewRole(string $code): array
    {
        $code = strtoupper(trim($code));
        $was  = $this->db->query('SELECT * FROM tf_crew_role WHERE code = ?', [$code])->getRowArray();
        if (!$was) {
            throw new \InvalidArgumentException('That is not a crew role this system holds.');
        }
        $used = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_intake_crew WHERE role_code = ?', [$code])->getRowArray()['n'] ?? 0);
        if ($used > 0) {
            $this->db->query('UPDATE tf_crew_role SET active = 0 WHERE code = ?', [$code]);
            Activity::master($this->db, $this->user, 'crew_role', 0, 'Crew Bata: ' . $code . ' (' . $was['name'] . ') switched off — on ' . $used . ' quotation line(s)',
                [['what' => $code . ' active', 'from' => (int) $was['active'], 'to' => 0]], $this->actorId, $this->actorName);
            return ['deleted' => false, 'deactivated' => true, 'used' => $used];
        }
        $this->db->query('DELETE FROM tf_crew_role WHERE code = ?', [$code]);
        Activity::master($this->db, $this->user, 'crew_role', 0, 'Crew Bata: ' . $code . ' (' . $was['name'] . ') removed — never quoted',
            [['what' => $code, 'from' => $was['name'] . ' · ' . ($was['bata'] ?? 'no bata'), 'to' => null]], $this->actorId, $this->actorName);
        return ['deleted' => true, 'deactivated' => false, 'used' => 0];
    }

    /**
     * Set a role's bata — the daily allowance the person is paid.
     *
     * Master data, so it is deliberately separate from any one inquiry: set it once and
     * every quotation from then on picks it up. Inquiries still being worked on are
     * brought in line straight away, rather than holding a figure copied at the moment
     * the role was first put on; a converted one is left alone, because its numbers have
     * already been quoted to somebody.
     */
    public function saveCrewBata(string $code, $bata, ?string $currency = null): array
    {
        $r = ($bata === null || $bata === '') ? null : round((float) $bata, 2);
        if ($r !== null && $r < 0) {
            throw new \InvalidArgumentException('A bata cannot be negative.');
        }
        if (!in_array($code, array_column($this->crewRoles(true), 'code'), true)) {
            throw new \InvalidArgumentException('That is not a crew role this system holds.');
        }
        $was = $this->db->query('SELECT name, bata, currency FROM tf_crew_role WHERE code = ?', [$code])->getRowArray() ?: [];
        $this->db->query(
            'UPDATE tf_crew_role SET bata = ?, currency = COALESCE(?, currency) WHERE code = ?',
            [$r, $currency ?: null, $code]
        );
        Activity::master($this->db, $this->user, 'crew_bata', 0, 'Bata rates: ' . $code . ' (' . ($was['name'] ?? $code) . ') ' . ($was['bata'] ?? 'not set') . ' → ' . ($r ?? 'not set') . ' ' . ($currency ?: ($was['currency'] ?? '')),
            [['what' => $code . ' bata', 'from' => $was['bata'] ?? null, 'to' => $r], ['what' => $code . ' currency', 'from' => $was['currency'] ?? null, 'to' => $currency ?: ($was['currency'] ?? null)]], $this->actorId, $this->actorName);
        foreach ($this->db->query("SELECT DISTINCT c.intake_id AS id FROM tf_intake_crew c
                                   JOIN tf_intake i ON i.id = c.intake_id
                                   WHERE c.role_code = ? AND i.status IN ('new','working','quoted')", [$code])->getResultArray() as $row) {
            $this->db->query('UPDATE tf_intake_crew SET bata = ?, currency = ? WHERE intake_id = ? AND role_code = ?', [$r, $currency ?: ($was['currency'] ?? 'LKR'), (int) $row['id'], $code]);
            $this->costCrew((int) $row['id']);
        }
        return $this->crewRoles();
    }

    /**
     * Work out the vehicle from the passenger count and put it on the inquiry.
     *
     * Leaves a person's own choice alone: once somebody has overridden the vehicle it is
     * theirs, and a later change to the pax count flags the mismatch rather than silently
     * swapping the vehicle underneath them.
     */
    public function planVehicle(int $id, bool $force = false): void
    {
        $it = $this->db->query('SELECT pax_total, status FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it || in_array((string) $it['status'], ['converted', 'declined', 'confirmed', 'cancelled'], true)) {
            // a quotation that has gone out keeps every figure it went out with
            return;
        }
        $manual = $this->db->query("SELECT COUNT(*) n FROM tf_intake_vehicle WHERE intake_id = ? AND chosen = 'manual'", [$id])->getRowArray()['n'] ?? 0;
        if ((int) $manual > 0 && !$force) {
            return;
        }
        // The ladder already leaves spare seats for luggage and the guide, so a guide is
        // not added on top of the party — that is what the gap between max_pax and seats is.
        $plan = $this->planner()->suggest(self::intOrNull($it['pax_total']));

        $kept = $this->rateSnapshots($id);
        $this->db->query('DELETE FROM tf_intake_vehicle WHERE intake_id = ?', [$id]);
        foreach ($plan['vehicles'] as $i => $v) {
            $this->insertVehicleLine($id, $v, 'auto', self::cut($v['why'] ?? $plan['why'], 255), $i, $kept);
        }
        $this->touch($id);
    }

    /**
     * The rate each size on this inquiry already carries, so a re-plan does not quietly
     * re-price it. A figure copied onto an inquiry stays until somebody re-prices it on
     * purpose or types a special rate — that is what makes an old quotation stay old.
     *
     * @return array<string, array<string, mixed>>
     */
    private function rateSnapshots(int $id): array
    {
        $kept = [];
        foreach ($this->db->query("SELECT class_code, km_rate, rate_id, master_rate, rate_by, rate_note, rate_set_by, rate_set_at, currency
                                   FROM tf_intake_vehicle WHERE intake_id = ? AND rate_by IN ('master','manual')", [$id])->getResultArray() as $r) {
            $kept[(string) $r['class_code']] = $r;
        }
        return $kept;
    }

    /**
     * One vehicle line, with its rate settled: the figure already on this inquiry for
     * that size if there is one, otherwise today's master rate, copied — never a link
     * that a later change to the master could move.
     *
     * @param array<string, mixed> $v    a planner line
     * @param array<string, array<string, mixed>> $kept
     */
    private function insertVehicleLine(int $id, array $v, string $chosen, ?string $why, int $sort, array $kept): void
    {
        $code = (string) $v['class_code'];
        $now  = date('Y-m-d H:i:s');
        $groupId = self::intOrNull($v['group_id'] ?? null);
        $dayFrom = self::intOrNull($v['day_from'] ?? null);
        $dayTo   = self::intOrNull($v['day_to'] ?? null);
        $label   = self::cut($v['label'] ?? null, 40) ?? ('Vehicle ' . ($sort + 1));
        if (isset($kept[$code])) {
            $k = $kept[$code];
            $rate = [$k['km_rate'], $k['rate_id'], $k['master_rate'], $k['rate_by'], $k['rate_note'], $k['rate_set_by'], $k['rate_set_at'], $k['currency'] ?: 'LKR'];
        } elseif (($v['rate_id'] ?? null) !== null) {
            $rate = [$v['km_rate'], $v['rate_id'], $v['master_rate'], 'master', null, $this->user, $now, $v['currency'] ?? 'LKR'];
        } else {
            $rate = [null, null, null, 'none', null, null, null, 'LKR'];
        }
        $this->db->query(
            "INSERT INTO tf_intake_vehicle
                (intake_id, group_id, day_from, day_to, label, vehicle_id, class_code, type, seats, qty, km_rate, rate_id, master_rate, rate_by, rate_note, rate_set_by, rate_set_at, bata, currency, chosen, why, sort_order)
             VALUES (?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?,?,?, ?,?,?,?,?)",
            array_merge(
                [$id, $groupId, $dayFrom, $dayTo, $label, $v['id'] ?? null, $code, self::cut($v['type'], 120), $v['seats'] ?? null, max(1, (int) ($v['qty'] ?? 1))],
                [$rate[0], $rate[1], $rate[2], $rate[3], $rate[4], $rate[5], $rate[6]],
                [$v['bata'] ?? null, $rate[7], $chosen, $why, $sort]
            )
        );
    }

    /** Override the vehicle by hand. From then on it is the person's, not the planner's. */
    public function saveVehicle(int $id, array $rows): array
    {
        $this->assertOpen($id);
        $planner = $this->planner();
        $classes = [];
        foreach ($planner->classes() as $c) {
            $classes[$c['code']] = $c;
        }

        $lines = [];
        foreach ($rows as $r) {
            $code = trim((string) ($r['class_code'] ?? ''));
            if (!isset($classes[$code])) {
                continue;   // only a size the company actually uses
            }
            $qty  = max(1, min(9, (int) ($r['qty'] ?? 1)));
            $line = $planner->line($classes[$code], $qty, 'Chosen by ' . ($this->user ?: 'a colleague') . '.');
            // which group it carries and which days it is engaged for; blank means the whole party, the whole tour
            $line['group_id'] = self::intOrNull($r['group_id'] ?? null);
            $line['day_from'] = self::intOrNull($r['day_from'] ?? null);
            $line['day_to']   = self::intOrNull($r['day_to'] ?? null);
            $line['label']    = self::cut($r['label'] ?? null, 40);
            $lines[] = $line;
        }
        if ($lines === []) {
            throw new \InvalidArgumentException('Choose a vehicle size to use.');
        }

        // nothing is removed until there is something valid to put in its place
        $kept = $this->rateSnapshots($id);
        $this->db->query('DELETE FROM tf_intake_vehicle WHERE intake_id = ?', [$id]);
        foreach ($lines as $i => $l) {
            $this->insertVehicleLine($id, $l, 'manual', self::cut($l['why'], 255), $i, $kept);
        }
        $this->planSectors($id);   // each vehicle drives its own legs over its own days
        $this->touch($id);
        return $this->get($id);
    }

    /** Put the vehicle back under the planner's control. */
    public function autoVehicle(int $id): array
    {
        $this->assertOpen($id);
        $this->planVehicle($id, true);
        $this->planSectors($id);
        return $this->get($id);
    }

    /**
     * A special rate for this one job, or back to the master's.
     *
     * The master figure is never lost under a special rate: it stays in master_rate,
     * printed beside the special one with who set it and why, so the discount is a
     * visible decision and not a quiet edit. A special rate belongs to this inquiry
     * alone; the master is untouched.
     */
    public function saveVehicleRate(int $id, int $lineId, $rate, ?string $note = null): array
    {
        $this->assertOpen($id);
        $line = $this->db->query('SELECT * FROM tf_intake_vehicle WHERE id = ? AND intake_id = ?', [$lineId, $id])->getRowArray();
        if (!$line) {
            throw new \InvalidArgumentException('That vehicle line is not on this inquiry.');
        }
        $now = date('Y-m-d H:i:s');
        if ($rate === null || $rate === '') {
            // back to the master: today's rate for the size, copied afresh
            $cur = $this->transport()->current((string) $line['class_code']);
            $this->db->query(
                "UPDATE tf_intake_vehicle SET km_rate = ?, rate_id = ?, master_rate = ?, rate_by = ?, rate_note = NULL,
                        rate_set_by = ?, rate_set_at = ?, currency = ? WHERE id = ?",
                [$cur['rate_km'] ?? null, $cur['id'] ?? null, $cur['rate_km'] ?? null, $cur ? 'master' : 'none',
                 $this->user, $now, $cur['currency'] ?? 'LKR', $lineId]
            );
        } else {
            if (!is_numeric($rate) || (float) $rate < 0) {
                throw new \InvalidArgumentException('A rate per kilometre cannot be negative.');
            }
            // the master figure is refreshed so the comparison beside the special rate is current
            $cur = $this->transport()->current((string) $line['class_code']);
            $this->db->query(
                "UPDATE tf_intake_vehicle SET km_rate = ?, master_rate = COALESCE(?, master_rate), rate_id = COALESCE(?, rate_id),
                        rate_by = 'manual', rate_note = ?, rate_set_by = ?, rate_set_at = ? WHERE id = ?",
                [round((float) $rate, 2), $cur['rate_km'] ?? null, $cur['id'] ?? null, self::cut($note, 200), $this->user, $now, $lineId]
            );
        }
        $this->touch($id);
        return $this->get($id);
    }

    /** Copy today's master rate onto every line that is still on a master rate. Explicit — never automatic. */
    public function repriceVehicle(int $id): array
    {
        $this->assertOpen($id);
        foreach ($this->db->query("SELECT id FROM tf_intake_vehicle WHERE intake_id = ? AND rate_by IN ('master','none')", [$id])->getResultArray() as $r) {
            $this->saveVehicleRate($id, (int) $r['id'], null);
        }
        return $this->get($id);
    }

    /**
     * Suggest the crew from what is already known: the market decides the language, and
     * a tour with nights needs somebody driving it. Every line is marked 'auto' and can
     * be removed or swapped; a line a person has touched is never replaced.
     */
    public function planCrew(int $id, bool $force = false): void
    {
        $it = $this->db->query('SELECT market_code, country_iso, nights' . ($this->hasCrewBy() ? ', crew_by' : '') . ' FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            return;
        }
        // A crew a person has decided is theirs: a guide the office took off is not put back
        // by a recheck or a change of agent (schema 20). Auto crew (force) starts afresh.
        if (!$force && $this->hasCrewBy() && (string) ($it['crew_by'] ?? 'auto') === 'human') {
            $this->costCrew($id);
            return;
        }
        $manual = (int) ($this->db->query("SELECT COUNT(*) n FROM tf_intake_crew WHERE intake_id = ? AND chosen = 'manual'", [$id])->getRowArray()['n'] ?? 0);
        if ($manual > 0 && !$force) {
            $this->costCrew($id);
            return;
        }
        if ($force && $this->hasCrewBy()) {
            $this->db->query("UPDATE tf_intake SET crew_by = 'auto' WHERE id = ?", [$id]);
        }

        $japanese = strtoupper((string) ($it['market_code'] ?? '')) === 'JP' || strtoupper((string) ($it['country_iso'] ?? '')) === 'JP';
        $lang     = $japanese ? 'Japanese' : 'English';
        $why      = $japanese
            ? 'The agent is a Japanese market account, so Japanese speaking crew is offered.'
            : 'No Japanese market on this agent, so English speaking crew is offered.';

        // one driver and one guide in that language: the first active role of each kind the
        // master lists for it (Master Files → Crew Bata decides the order)
        $wanted = [];
        foreach (['driver', 'guide'] as $kind) {
            foreach ($this->crewRoles() as $r) {
                if ($r['kind'] === $kind && strcasecmp((string) $r['language'], $lang) === 0) {
                    $wanted[] = $r;
                    break;
                }
            }
        }

        $this->db->query("DELETE FROM tf_intake_crew WHERE intake_id = ? AND chosen = 'auto'", [$id]);
        $i = 0;
        foreach ($wanted as $r) {
            $this->db->query(
                "INSERT IGNORE INTO tf_intake_crew (intake_id, role_code, role_name, kind, language, days, bata, currency, chosen, why, sort_order)
                 VALUES (?,?,?,?,?,?,?,?,'auto',?,?)",
                [$id, $r['code'], $r['name'], $r['kind'], $r['language'], $this->hasCrewDaysBy() ? null : self::intOrNull($it['nights']), $r['bata'], $r['currency'], $why, $i++]
            );
        }
        $this->costCrew($id);
        $this->touch($id);
    }

    // =====================================================================
    //  Where the driver and the guide sleep
    // =====================================================================

    public function accom(): AccomPlanner
    {
        return $this->accom ??= new AccomPlanner($this->db, $this->rates(), $this->services(), fn (string $t) => $this->quotable($t));
    }

    /**
     * Recompute both accommodation lines.
     *
     * A line that is not required is costed at nothing and says so; a line that IS required
     * is worked out night by night from the itinerary. Nothing here ever turns "unknown"
     * into "free" — an unpriced night is counted and reported, never quietly dropped.
     */
    public function planAccom(int $id): void
    {
        $it = $this->db->query('SELECT pax_total, market_code, arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            return;
        }
        $days = $this->db->query('SELECT * FROM tf_intake_day WHERE intake_id = ? ORDER BY day_no, id', [$id])->getResultArray();
        // schema 32: the crew sleep the nights of the stay; a line on the departure day is no night
        $stayN = TourDates::nights($it['arrival_date'] ?? null, $it['departure_date'] ?? null);
        $days  = array_values(array_filter($days, static fn ($d) => !TourDates::beyond($d, $stayN, $it['departure_date'] ?? null)));
        $pax  = self::intOrNull($it['pax_total']);
        $now  = date('Y-m-d H:i:s');

        foreach (AccomPlanner::KINDS as $kind) {
            $row = $this->db->query('SELECT * FROM tf_intake_accom WHERE intake_id = ? AND kind = ? AND day_no = 0', [$id, $kind])->getRowArray();
            if (!$row) {
                // Created "not required", which is the default the business asked for and
                // the only honest starting point: nobody has decided yet.
                $this->db->query(
                    "INSERT INTO tf_intake_accom (intake_id, kind, day_no, required, why, updated_at, updated_by)
                     VALUES (?,?,0,0,?,?,?)",
                    [$id, $kind, 'Not required unless somebody says otherwise. Nothing is assumed to be free.', $now, $this->user]
                );
                continue;
            }
            if ((int) $row['required'] !== 1) {
                $this->db->query(
                    "UPDATE tf_intake_accom SET nights = NULL, rate = NULL, rate_by = 'none', amount = NULL,
                        complimentary_nights = 0, unknown_nights = 0, detail_json = NULL,
                        why = 'Not required, so nothing is costed for it.', updated_at = ? WHERE id = ?",
                    [$now, (int) $row['id']]
                );
                continue;
            }

            $plan = $this->accom()->plan($days, $kind, $pax, $it['market_code'] ?: null);

            // a night count somebody has overridden is theirs; otherwise it is counted
            $nights = ((string) $row['nights_by'] === 'override' && $row['nights'] !== null)
                ? (int) $row['nights'] : $plan['nights'];
            $rate   = ((string) $row['rate_by'] === 'manual' && $row['rate'] !== null)
                ? (float) $row['rate'] : $plan['rate'];
            $rateBy = ((string) $row['rate_by'] === 'manual' && $row['rate'] !== null) ? 'manual' : $plan['rate_by'];
            $amount = ($rate !== null && $nights > 0) ? round($rate * $nights, 2) : null;

            $why = $plan['why'];
            if ((string) $row['nights_by'] === 'override') {
                $why .= ' Night count set by hand to ' . $nights . '.';
            }
            if ($rateBy === 'manual') {
                $why .= ' Nightly figure set by hand.';
            }

            $this->db->query(
                'UPDATE tf_intake_accom SET nights = ?, rate = ?, currency = ?, rate_by = ?, amount = ?,
                    complimentary_nights = ?, unknown_nights = ?, detail_json = ?, why = ?, updated_at = ? WHERE id = ?',
                [
                    $nights, $rate, $plan['currency'] ?: 'USD', $rateBy, $amount,
                    $plan['complimentary'], $plan['unknown'], json_encode($plan['lines'], JSON_UNESCAPED_UNICODE),
                    self::cut($why, 400), $now, (int) $row['id'],
                ]
            );
        }
    }

    /**
     * Say whether a driver or guide room is needed, and correct the count or the figure.
     * Required is never set for somebody: it is a decision, and it starts at no.
     */
    public function saveAccom(int $id, string $kind, array $d): array
    {
        $this->assertOpen($id);
        if (!in_array($kind, AccomPlanner::KINDS, true)) {
            throw new \InvalidArgumentException('Accommodation is held for a driver or a guide.');
        }
        $this->planAccom($id);   // makes sure the row exists
        $row = $this->db->query('SELECT * FROM tf_intake_accom WHERE intake_id = ? AND kind = ? AND day_no = 0', [$id, $kind])->getRowArray();

        $sets = [];
        $bind = [];
        if (array_key_exists('required', $d)) {
            $sets[] = '`required` = ?';
            $bind[] = !empty($d['required']) ? 1 : 0;
        }
        if (array_key_exists('nights', $d)) {
            $n = self::intOrNull($d['nights']);
            $sets[] = '`nights` = ?';
            $bind[] = $n;
            $sets[] = '`nights_by` = ?';
            $bind[] = $n === null ? 'counted' : 'override';
        }
        if (array_key_exists('rate', $d)) {
            $r = ($d['rate'] === null || $d['rate'] === '') ? null : round((float) $d['rate'], 2);
            if ($r !== null && $r < 0) {
                throw new \InvalidArgumentException('A nightly figure cannot be negative.');
            }
            $sets[] = '`rate` = ?';
            $bind[] = $r;
            $sets[] = '`rate_by` = ?';
            $bind[] = $r === null ? 'none' : 'manual';
        }
        if (array_key_exists('note', $d)) {
            $sets[] = '`note` = ?';
            $bind[] = self::cut($d['note'], 255);
        }
        if ($sets) {
            $bind[] = date('Y-m-d H:i:s');
            $bind[] = $this->user;
            $bind[] = (int) $row['id'];
            $this->db->query('UPDATE tf_intake_accom SET ' . implode(', ', $sets) . ', updated_at = ?, updated_by = ? WHERE id = ?', $bind);
        }
        $this->planAccom($id);
        $this->touch($id);
        return $this->get($id);
    }

    // =====================================================================
    //  The driving — how far, sector by sector, day by day
    // =====================================================================

    public function mileage(): MileageLookup
    {
        return $this->mileage ??= new MileageLookup(
            $this->db,
            (float) ($this->meta('km_sane_max') ?? 450),
            (int) ($this->meta('km_min_samples') ?? 3)
        );
    }

    /** Google's road distances, asked once per pair of places and kept. */
    public function google(): GoogleDistance
    {
        return $this->google ??= new GoogleDistance($this->db);
    }

    /** Tests answer for Google here; nothing else needs to. */
    public function withGoogle(GoogleDistance $google): self
    {
        $this->google = $google;
        return $this;
    }

    /**
     * Which figure the quotation is costed on: Google's road distance ('google', the
     * default) or the company's own driven record ('actual'). The setting tf_meta
     * mileage_quote_source, changed on Master Files → Mileage Allowances.
     */
    public function quoteSource(): string
    {
        return strtolower(trim((string) ($this->meta('mileage_quote_source') ?? ''))) === 'actual' ? 'actual' : 'google';
    }

    /** Whether the sector table carries the two figures side by side (schema 17). */
    private function hasTwoFigures(): bool
    {
        if ($this->hasTwoFigures === null) {
            try {
                $this->hasTwoFigures = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_sector` LIKE 'google_km'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasTwoFigures = false;
            }
        }
        return $this->hasTwoFigures;
    }

    /** Whether the sector table can say how many journeys were entered after a tour (schema 18). */
    private function hasActuals(): bool
    {
        if ($this->hasActuals === null) {
            try {
                $this->hasActuals = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_sector` LIKE 'samples_post'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasActuals = false;
            }
        }
        return $this->hasActuals;
    }

    /** A leg the vehicle actually drives between two places — the only kind Google is asked about. */
    public static function isDrivenLeg(string $kind, ?string $from, ?string $to): bool
    {
        if (in_array($kind, ['garage', 'idle'], true)) {
            return false;
        }
        if ($kind === 'local') {
            return $from !== null && $to !== null && !self::samePlace($from, $to);
        }
        return true;
    }

    /**
     * Break the itinerary into legs and put a distance on each one.
     *
     * The shape of a tour is not invented here, it is copied from what the company
     * actually does: 1,194 of 1,729 tours on file begin with the vehicle running from
     * Colombo out to the airport to collect the party, then the transfer inland. The
     * return positioning run is deliberately NOT added, because the record does not show
     * one — only 138 tours end with a leg leaving the airport, so quoting it every time
     * would be inventing work nobody does.
     *
     * A day with no transfer on it still drives. That is what the extra_kilometers master
     * is for, and it gets its own line rather than being buried in a transfer.
     *
     * A leg somebody has typed or corrected is never regenerated: the day it belongs to is
     * left alone entirely, the same rule the vehicle and the crew already follow.
     */
    public function planSectors(int $id, bool $force = false): void
    {
        $this->laySectors($id, $force);
        // schema 40: the route is set — the lunch place of each day follows it (MealMaster::autoLunch)
        $this->autoLunches($id);
    }

    private function laySectors(int $id, bool $force): void
    {
        $it = $this->db->query('SELECT arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            return;
        }
        $days = $this->db->query(
            'SELECT d.day_no, d.day_date, d.area, d.hotel_name_raw, d.hotel_id, h.name AS hotel_name, l.name AS hotel_area
             FROM tf_intake_day d
             LEFT JOIN hr2_hotel h ON h.id = d.hotel_id
             LEFT JOIN hr2_location l ON l.id = h.location_id
             WHERE d.intake_id = ? ORDER BY d.day_no, d.id',
            [$id]
        )->getResultArray();
        // schema 32: only the nights of the stay make legs. A line on or after the departure date is
        // not a night: the departure transfer runs from the last night's place on the day after it.
        $stayNights = TourDates::nights($it['arrival_date'] ?? null, $it['departure_date'] ?? null);
        $days = array_values(array_filter($days, static fn ($d) => !TourDates::beyond($d, $stayNights, $it['departure_date'] ?? null)));
        // A day with a visit, a safari or a lunch out on it is a day out; a day with nothing
        // on it is a day at the hotel. That is a fact the itinerary already holds, not a guess.
        // Who pays for the visit does not come into it — a visit the agent arranges themselves
        // is still somewhere the vehicle drives them. Only a visit that is NOT happening
        // ('not_arranged') leaves the day quiet.
        $busy   = [];
        $visits = [];   // day => the visits in the agent's words, in order: where the vehicle goes that day
        // schema 40: a lunch or dinner place of the Meal Master that nobody wrote into the itinerary (the system's
        // pick, or one a person chose) is where the party eats on the way, not a trip: it never moves the vehicle or
        // the kilometres. A line taken onto the master from the agent's words counts by those words, as before.
        $mealOff = $this->hasMealCols() ? " AND NOT (meal_id IS NOT NULL AND COALESCE(source_title, '') = '')" : '';
        foreach ($this->db->query("SELECT day_no, COALESCE(source_title, title) AS title FROM tf_intake_service WHERE intake_id = ? AND day_no > 0
                                   AND COALESCE(pay_mode, '') <> 'not_arranged'" . $mealOff . ' ORDER BY day_no, sort_order, id', [$id])->getResultArray() as $r) {
            $busy[(int) $r['day_no']] = true;
            $visits[(int) $r['day_no']][] = (string) $r['title'];
        }
        foreach ($this->db->query('SELECT DISTINCT day_no FROM tf_intake_excursion WHERE intake_id = ? AND day_no > 0', [$id])->getResultArray() as $r) {
            $busy[(int) $r['day_no']] = true;
        }
        // Days a vehicle is released for: no stay-day mileage is charged for it. Absent = retained.
        $released = [];
        foreach ($this->db->query('SELECT vehicle_seq, day_no FROM tf_intake_vehicle_day WHERE intake_id = ? AND retained = 0', [$id])->getResultArray() as $r) {
            $released[(int) $r['vehicle_seq'] . ':' . (int) $r['day_no']] = true;
        }

        // Legs a person has made their own. Each one stands: the plan neither removes it nor
        // lays it down again. A leg somebody only changed the kilometres on is still the
        // plan's leg — it says which one in plan_key — so the rest of that day goes on being
        // planned around it. Without plan_key (an older install) there is no way to tell an
        // edited leg from one added by hand, so the plan keeps off the whole day, as it used to.
        $mine    = [];   // plan_key => the id of the person's leg standing in its place
        $own     = [];   // every leg of theirs that claims to be a leg of the plan
        $touched = [];   // vehicle:day the plan keeps off entirely
        if (!$force) {
            $keyed = $this->hasPlanKey();
            foreach ($this->db->query('SELECT id, vehicle_seq, day_no, from_place, to_place, kind' . ($keyed ? ', plan_key' : '')
                . " FROM tf_intake_sector WHERE intake_id = ? AND origin = 'human'"
                . ($this->hasRemoved() ? ' AND removed_at IS NULL' : ''), [$id])->getResultArray() as $r) {
                $pk = $keyed ? trim((string) ($r['plan_key'] ?? '')) : '';
                if ($pk !== '') {
                    $mine[$pk] = (int) $r['id'];
                    $own[]     = ['id' => (int) $r['id'], 'vs' => (int) $r['vehicle_seq'], 'day' => (int) $r['day_no'], 'kind' => (string) $r['kind']];
                } elseif (!$keyed) {
                    $touched[(int) $r['vehicle_seq'] . ':' . (int) $r['day_no']] = true;
                }
            }
        }
        // A leg somebody removed stays removed: the row is kept, struck through, as the
        // marker that says so, and the plan does not put that leg back. Auto (the ⋯ Auto
        // button) starts afresh and brings everything back.
        $removed = [];
        if ($this->hasRemoved()) {
            if ($force) {
                $this->db->query('DELETE FROM tf_intake_sector WHERE intake_id = ? AND removed_at IS NOT NULL', [$id]);
            } else {
                foreach ($this->db->query('SELECT id, vehicle_seq, day_no, from_place, to_place, kind' . ($this->hasPlanKey() ? ', plan_key' : '')
                    . ' FROM tf_intake_sector WHERE intake_id = ? AND removed_at IS NOT NULL', [$id])->getResultArray() as $r) {
                    // the leg of the plan it stands for, which is not its ends any more if
                    // somebody had changed them before taking it off
                    $pk = trim((string) ($r['plan_key'] ?? ''));
                    $removed[$pk !== '' ? $pk : self::legKey((int) $r['vehicle_seq'], (int) $r['day_no'], $r['from_place'], $r['to_place'], (string) $r['kind'])] = (int) $r['id'];
                }
            }
        }
        $this->db->query(
            'DELETE FROM tf_intake_sector WHERE intake_id = ?' . ($force ? '' : " AND origin = 'auto'") . ($this->hasRemoved() ? ' AND removed_at IS NULL' : ''),
            [$id]
        );
        $stillRemoved = [];
        if ($days === []) {
            $this->touch($id);
            return;
        }

        $km        = $this->mileage();
        $gateIn    = trim((string) ($this->meta('gateway') ?? 'Katunayake'));
        $gateOut   = trim((string) ($this->meta('gateway_out') ?? $gateIn));
        $base      = trim((string) ($this->meta('vehicle_base') ?? ''));
        $position  = trim((string) ($this->meta('position_on_arrival') ?? '1')) === '1';
        [$garageOut, $garageBack] = $this->allowances()->garageKm();

        $firstDayNo = (int) $days[0]['day_no'];
        $lastDayNo  = (int) $days[count($days) - 1]['day_no'];
        $byNo = [];
        foreach ($days as $d) {
            $byNo[(int) $d['day_no']] = $d;
        }
        $groups = [];
        foreach ($this->db->query('SELECT id, day_from, day_to FROM tf_intake_group WHERE intake_id = ?', [$id])->getResultArray() as $g) {
            $groups[(int) $g['id']] = $g;
        }

        // One run per vehicle line, each over the days it is engaged for. No line yet
        // means one vehicle for the whole party for the whole tour, exactly as before.
        $lines = $this->db->query('SELECT id, sort_order, group_id, day_from, day_to FROM tf_intake_vehicle WHERE intake_id = ? ORDER BY sort_order, id', [$id])->getResultArray();
        if ($lines === []) {
            $lines = [['id' => null, 'sort_order' => 0, 'group_id' => null, 'day_from' => null, 'day_to' => null]];
        }

        foreach ($lines as $line) {
            $seqV  = (int) $line['sort_order'];
            $grp   = $line['group_id'] !== null ? ($groups[(int) $line['group_id']] ?? null) : null;
            $from  = self::intOrNull($line['day_from']) ?? ($grp ? self::intOrNull($grp['day_from']) : null) ?? $firstDayNo;
            $to    = self::intOrNull($line['day_to'])   ?? ($grp ? self::intOrNull($grp['day_to'])   : null) ?? $lastDayNo;
            $from  = max($firstDayNo, min($from, $lastDayNo));
            $to    = max($from, min($to, $lastDayNo));
            // does the party this vehicle carries leave the country when the vehicle's days end?
            $partyLeaves = $to === $lastDayNo || ($grp && self::intOrNull($grp['day_to']) === $to);

            $legs  = [];
            $prev  = null;
            $prevHotel = null;
            $prevAlias = null;
            $first = true;
            foreach ($days as $d) {
                $dayNo = (int) $d['day_no'];
                if ($dayNo < $from || $dayNo > $to) {
                    continue;
                }
                [$here, $note] = $this->placeFor($d, $km);
                // The record's own name for the night's hotel ("Heritance Kandalama, Dambulla"):
                // the journeys driven to that door, for when the town's name alone finds none.
                $alias = $d['hotel_id'] ? ($km->historyName($d['hotel_name'] ?? null) ?? $km->historyName($d['hotel_name_raw'] ?? null)) : null;
                // The same hotel as last night means the party has not moved, whatever the
                // day's line says: "Anuradhapura" on a night at Heritance Kandalama is where
                // they go for the day, not where they sleep.
                $hotelId = self::intOrNull($d['hotel_id'] ?? null);
                if (!$first && $hotelId !== null && $hotelId === $prevHotel && $prev !== null) {
                    $here = $prev;
                    $note = '';
                }

                if ($first) {
                    $first = false;
                    if ($garageOut > 0) {
                        $legs[] = [$dayNo, 'Garage', $base !== '' ? $base : $gateIn, 'garage', ''];
                    }
                    if ($dayNo === $firstDayNo) {
                        if ($position && $base !== '' && $gateIn !== '') {
                            $legs[] = [$dayNo, $base, $gateIn, 'positioning',
                                'The vehicle runs out to the airport to collect the party. This is how the company works: 1,194 of 1,729 tours on file start with this leg.', null, null];
                        }
                        if ($gateIn !== '' && $here !== null) {
                            $legs[] = [$dayNo, $gateIn, $here, 'arrival', $note, null, $alias];
                        } elseif ($here === null) {
                            $legs[] = [$dayNo, $gateIn, null, 'arrival', 'No area or hotel on day 1 yet, so where they are driven to is not known.'];
                        }
                    } elseif ($base !== '' && $here !== null) {
                        // joins the tour part-way: the run out from base to wherever the party is that day
                        $legs[] = [$dayNo, $base, $here, 'positioning', 'The vehicle joins the tour on day ' . $dayNo . ': the run out from ' . $base . ' to the party.', null, $alias];
                    }
                    $prev = $here;
                    $prevHotel = $hotelId;
                    $prevAlias = $alias;
                    continue;
                }

                if ($here !== null && $prev !== null && !self::samePlace($here, $prev)) {
                    $legs[] = [$dayNo, $prev, $here, 'transfer', $note, $prevAlias, $alias];
                } elseif ($here !== null) {
                    // Staying put. A day out to other towns is driven leg by leg — out to each
                    // place the day names and back to the hotel — each one looked up in the
                    // history like any transfer. A day out within the town gets the town's
                    // city-tour allowance; a day at the hotel with nothing on it gets the
                    // stay-day figure — but only while the vehicle is retained. A vehicle
                    // released for the day charges nothing.
                    $stops = isset($busy[$dayNo]) ? $this->dayOutStops($km, $d, $here, $alias, $visits[$dayNo] ?? []) : [];
                    if ($stops !== []) {
                        $names = array_column($stops, 'town');
                        $slips = array_filter($stops, static fn ($x) => $x['slip']);
                        $tour  = 'Day out from ' . MileageLookup::townOf($here) . ': ' . implode(', ', $names) . ' and back.'
                            . ($slips ? ' Read "' . implode('", "', array_column($slips, 'given')) . '" as ' . implode(', ', array_column($slips, 'town')) . ' (one letter off the record\'s spelling).' : '');
                        $at = $here;
                        $atAlias = $alias;
                        foreach ($stops as $stop) {
                            $legs[] = [$dayNo, $at, $stop['town'], 'local', $tour, $atAlias, null];
                            $at = $stop['town'];
                            $atAlias = null;
                        }
                        $legs[] = [$dayNo, $at, $here, 'local', $tour, null, $alias];
                    } elseif (isset($busy[$dayNo])) {
                        $legs[] = [$dayNo, $here, $here, 'local', $this->dayOutNote($km, $d, $here, $visits[$dayNo] ?? [], $note)];
                    } elseif (!isset($released[$seqV . ':' . $dayNo])) {
                        $legs[] = [$dayNo, $here, $here, 'idle', $note];
                    }
                }
                $prev = $here ?? $prev;
                $prevHotel = $hotelId;
                $prevAlias = $alias;
            }

            $endDay = $to + 1;
            if ($partyLeaves) {
                if ($prev !== null && $gateOut !== '') {
                    // schema 32: the departure day is no hotel night, but it is a working day. What is on it
                    // before the flight is driven like any day out — on to each other town its visits name,
                    // or the town's city-tour allowance for a visit in the town itself — and the transfer to
                    // the airport runs from where the party is by then. Nothing is added the day does not name.
                    $at      = $prev;
                    $atAlias = $prevAlias;
                    if (isset($busy[$endDay])) {
                        $depVisits = array_values(array_unique($visits[$endDay] ?? []));
                        $stops = $this->dayOutStops($km, ['area' => null], $prev, $prevAlias, $depVisits);
                        if ($stops !== []) {
                            $tour = 'Departure day: ' . implode(', ', array_column($stops, 'town')) . ' on the way to the airport'
                                . ($depVisits ? ' (' . implode(', ', $depVisits) . ')' : '') . '.';
                            foreach ($stops as $stop) {
                                $legs[] = [$endDay, $at, $stop['town'], 'local', $tour, $atAlias, null];
                                $at = $stop['town'];
                                $atAlias = null;
                            }
                        } else {
                            $legs[] = [$endDay, $prev, $prev, 'local', 'Departure day: ' . ($depVisits ? implode(', ', $depVisits) : 'the excursion') . ' in ' . MileageLookup::townOf($prev) . ' before the transfer to the airport.'];
                        }
                    }
                    $legs[] = [$endDay, $at, $gateOut, 'departure', '', $atAlias, null];
                }
                if ($garageBack > 0) {
                    $legs[] = [$endDay, $gateOut !== '' ? $gateOut : ($prev ?? ''), 'Garage', 'garage', ''];
                }
            } else {
                // the vehicle's days end but the party stays on: it runs back to base
                if ($prev !== null && $base !== '') {
                    $legs[] = [$endDay, $prev, $base, 'positioning', 'The vehicle is released after day ' . $to . ' and runs back to ' . $base . '.'];
                }
                if ($garageBack > 0) {
                    $legs[] = [$endDay, $base !== '' ? $base : ($prev ?? ''), 'Garage', 'garage', ''];
                }
            }

            // Which generated legs a leg of the person's already stands for. By its plan_key,
            // exactly, first. A leg edited before it could say which leg of the plan it was —
            // the site came across at schema 19 with the key taken from its ends, and its
            // ends had been changed — matches nothing that way; it stands for the one leg
            // of its kind the plan makes on its day, when there is exactly one, and learns
            // the key so this is settled. One that still matches nothing keeps the plan off
            // its day, as before, because laying the plan's leg down beside it would count
            // the journey twice. A leg a person added themselves has no key and comes into
            // none of this: the plan works around it.
            $stand = [];   // index into $legs => [the person's leg id, the key to give it or null]
            $used  = [];
            foreach ($legs as $i => $leg) {
                $rk = self::legKey($seqV, (int) $leg[0], $leg[1], $leg[2], $leg[3]);
                if (isset($mine[$rk])) {
                    $stand[$i] = [$mine[$rk], null];
                    $used[$mine[$rk]] = true;
                }
            }
            $loose = [];
            foreach ($own as $o) {
                if ($o['vs'] === $seqV && !isset($used[$o['id']])) {
                    $loose[$o['day'] . ':' . $o['kind']][] = $o['id'];
                }
            }
            foreach ($loose as $dk => $ids) {
                [$dn, $kd] = explode(':', $dk, 2);
                $cands = [];
                foreach ($legs as $i => $leg) {
                    if (!isset($stand[$i]) && (int) $leg[0] === (int) $dn && (string) $leg[3] === $kd) {
                        $cands[] = $i;
                    }
                }
                if (count($ids) === 1 && count($cands) === 1) {
                    $i = $cands[0];
                    $stand[$i] = [$ids[0], self::legKey($seqV, (int) $legs[$i][0], $legs[$i][1], $legs[$i][2], $legs[$i][3])];
                    $used[$ids[0]] = true;
                } else {
                    $touched[$seqV . ':' . (int) $dn] = true;
                }
            }

            // Google is asked about every driven leg of the run in one go (the pairs it
            // already knows cost nothing), so the plan waits once, not once per leg.
            if ($this->hasTwoFigures()) {
                $pairs = [];
                foreach ($legs as $i => $leg) {
                    [$dayNo, $legFrom, $legTo, $kind] = $leg;
                    if (!isset($touched[$seqV . ':' . $dayNo]) && !isset($stand[$i])
                        && $legFrom !== null && $legTo !== null && self::isDrivenLeg($kind, $legFrom, $legTo)) {
                        $pairs[] = [$leg[5] ?? $legFrom, $leg[6] ?? $legTo];
                    }
                }
                if ($pairs !== []) {
                    $this->google()->warm($pairs);
                }
            }

            $seq = [];
            foreach ($legs as $i => $leg) {
                [$dayNo, $legFrom, $legTo, $kind, $note] = $leg;
                if (isset($touched[$seqV . ':' . $dayNo])) {
                    continue;
                }
                $seq[$dayNo] = ($seq[$dayNo] ?? -1) + 1;
                $rk = self::legKey($seqV, $dayNo, $legFrom, $legTo, $kind);
                if (isset($stand[$i])) {
                    // somebody's own figures for this leg: their row stands where the plan
                    // would have put it, and the rest of the day is planned around it
                    [$sid, $learn] = $stand[$i];
                    if ($learn !== null) {
                        $this->db->query('UPDATE tf_intake_sector SET seq = ?, plan_key = ? WHERE id = ?', [$seq[$dayNo], self::cut($learn, 400), $sid]);
                    } else {
                        $this->db->query('UPDATE tf_intake_sector SET seq = ? WHERE id = ?', [$seq[$dayNo], $sid]);
                    }
                    continue;
                }
                if (isset($removed[$rk])) {
                    // somebody took this leg off: the marker stays, the leg is not put back
                    $stillRemoved[$removed[$rk]] = true;
                    $this->db->query('UPDATE tf_intake_sector SET seq = ? WHERE id = ?', [$seq[$dayNo], $removed[$rk]]);
                    continue;
                }
                $row = $this->sectorFigures($km, $legFrom, $legTo, $kind, $leg[5] ?? null, $leg[6] ?? null);
                $this->insertSector($id, $dayNo, $seq[$dayNo], $seqV, $legFrom, $legTo, $kind, 'auto', $row, $note);
            }
        }
        // a marker for a leg the plan no longer makes (the itinerary moved on) has nothing left to hold off
        foreach ($removed as $rk => $rid) {
            if (!isset($stillRemoved[$rid])) {
                [$vs, $dn] = explode(':', $rk);
                if (!isset($touched[$vs . ':' . $dn])) {
                    $this->db->query('DELETE FROM tf_intake_sector WHERE id = ?', [$rid]);
                }
            }
        }
        $this->applyExcursionWaivers($id);
        $this->costCrew($id);   // schema 32: the crew's days follow the programme just planned
        $this->touch($id);
    }

    /** What makes a planned leg the same leg next time: its vehicle, day, ends and kind. */
    private static function legKey(int $seqV, int $dayNo, ?string $from, ?string $to, string $kind): string
    {
        return $seqV . ':' . $dayNo . ':' . mb_strtolower(trim((string) $from)) . '→' . mb_strtolower(trim((string) $to)) . ':' . $kind;
    }

    /** Whether a leg can be taken off and stay off (schema 18). */
    private function hasRemoved(): bool
    {
        if ($this->hasRemoved === null) {
            try {
                $this->hasRemoved = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_sector` LIKE 'removed_at'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasRemoved = false;
            }
        }
        return $this->hasRemoved;
    }

    /** Whether a leg can say which leg of the plan it is (schema 19). */
    private function hasPlanKey(): bool
    {
        if ($this->hasPlanKey === null) {
            try {
                $this->hasPlanKey = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_sector` LIKE 'plan_key'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasPlanKey = false;
            }
        }
        return $this->hasPlanKey;
    }

    /**
     * The figures for one leg.
     *
     * A driven leg carries two: what Google's map makes the road distance, and what the
     * company's own record says was driven between the same places on past tours. The
     * quotation is costed on one of them — Google's by preference (see quoteSource) —
     * and the other is kept beside it, never thrown away, so the true kilometres and the
     * buffer between the two are always in view.
     *
     * A "local" leg is not a journey between two places, so it is never looked up in the
     * history and Google is not asked — it takes the town's own allowance from the
     * extra_kilometers master, and where the master has no entry for that town it takes
     * nothing at all rather than a number somebody would have to unpick later.
     *
     * @return array<string, mixed>
     */
    private function sectorFigures(MileageLookup $km, ?string $from, ?string $to, string $kind, ?string $fromAlias = null, ?string $toAlias = null): array
    {
        $blank = ['google_km' => null, 'google_by' => 'none', 'google_why' => null, 'actual_km' => null, 'actual_by' => 'none', 'actual_why' => null, 'samples_post' => 0];
        if ($to === null || $from === null) {
            return ['km' => null, 'km_by' => 'none', 'extra_km' => null, 'extra_by' => 'none',
                    'samples' => 0, 'dropped' => 0, 'low' => null, 'high' => null,
                    'why' => 'Both ends of the leg have to be named before a distance can be looked up.'] + $blank;
        }
        $none = ['extra_km' => null, 'extra_by' => 'none', 'samples' => 0, 'dropped' => 0, 'low' => null, 'high' => null] + $blank;
        if ($kind === 'local' && !self::samePlace($from, $to)) {
            // a day out to another town: a journey, looked up like any other
            $kind = 'transfer';
        }
        if ($kind === 'local') {
            $city = $this->allowances()->cityKm($to);
            $town = MileageLookup::townOf($to);
            return [
                'km' => $city, 'km_by' => $city === null ? 'none' : 'city_allowance',
                'why' => $city === null
                    ? 'A day out in ' . $town . ', but Mileage Allowances has no city-tour figure for it. Add one there, or type the kilometres in.'
                    : 'A day out in ' . $town . ': the city-tour allowance from Mileage Allowances, ' . self::num($city) . ' km.',
            ] + $none;
        }
        if ($kind === 'idle') {
            $stay = $this->allowances()->stayDayKm();
            return [
                'km' => $stay, 'km_by' => 'city_allowance',
                'why' => 'A day at the hotel with nothing booked. Charged at the stay-day figure from Mileage Allowances, ' . self::num($stay) . ' km.',
            ] + $none;
        }
        if ($kind === 'garage') {
            [$out, $back] = $this->allowances()->garageKm();
            $isOut = strcasecmp((string) $from, 'Garage') === 0;
            return [
                'km' => $isOut ? $out : $back, 'km_by' => 'city_allowance',
                'why' => ($isOut ? 'The driver\'s run out from the garage to the first pickup.' : 'The driver\'s run back to the garage from the last drop.')
                    . ' The garage mileage figure from Mileage Allowances.',
            ] + $none;
        }
        $hit = $km->between($from, $to);
        // Nothing under the town's name: the journeys to the hotel's own door, as the record
        // spells it ("Heritance Kandalama, Dambulla"), before giving up. Said in the why.
        if ($hit['samples'] === 0 && ($fromAlias !== null || $toAlias !== null)) {
            foreach ([[$fromAlias ?? $from, $toAlias ?? $to], [$fromAlias ?? $from, $to], [$from, $toAlias ?? $to]] as [$f2, $t2]) {
                if (strcasecmp($f2, $from) === 0 && strcasecmp($t2, $to) === 0) {
                    continue;
                }
                $try = $km->between($f2, $t2);
                if ($try['samples'] > 0) {
                    $hit = $try;
                    $hit['why'] = 'Looked up as ' . $f2 . ' → ' . $t2 . ', the record\'s own name for the hotel. ' . $hit['why'];
                    break;
                }
            }
        }
        $row = [
            'km' => $hit['km'], 'km_by' => $hit['km_by'], 'extra_km' => $hit['extra_km'], 'extra_by' => $hit['extra_by'],
            'samples' => $hit['samples'], 'samples_post' => (int) ($hit['samples_post'] ?? 0), 'dropped' => $hit['dropped'], 'low' => $hit['low'], 'high' => $hit['high'],
            'why' => $hit['why'],
            'actual_km' => $hit['km'], 'actual_by' => $hit['km_by'], 'actual_why' => $hit['why'],
        ] + $blank;
        if (!$this->hasTwoFigures()) {
            return $this->sameTownLeg($row, $from, $to, $kind);   // an older table: the driven record alone, exactly as before
        }
        // Google is asked for the hotel's own door where the record names one — a
        // distance to "Heritance Kandalama, Dambulla" beats one to the middle of Kandalama.
        $g = $this->google()->distance($fromAlias ?? $from, $toAlias ?? $to);
        $row['google_km']  = $g['km'];
        $row['google_by']  = $g['by'];
        $row['google_why'] = $g['why'] !== '' ? $g['why'] : null;
        [$row['km'], $row['km_by'], $row['why']] = $this->quotedFigure($row, $g);
        return $this->sameTownLeg($row, $from, $to, $kind);
    }

    /**
     * tf-walkfix-r1: the airport area. Katunayake (the planner's gateway town, tf_meta 'gateway') is where the
     * airport is: a leg that starts and ends there — the airport hotel on the first night, or the last night beside
     * the airport before the flight — is no journey the vehicle is paid for over and above the airport and garage
     * running the plan already carries, so it is quoted at 0 km, says so, and stops nothing. This is the one
     * exception: a hotel in Negombo or any other town gets its leg looked up and priced exactly as before, and a
     * same-town leg elsewhere is still a leg with no distance until somebody prices it. A figure found on file for
     * the airport leg is quoted as before; a figure typed over it still stands. Until the installer has added the
     * value (schema 35) the leg stays as it was.
     */
    private function sameTownLeg(array $row, string $from, string $to, string $kind): array
    {
        if ($row['km'] !== null || !in_array($kind, ['transfer', 'arrival', 'departure', 'positioning'], true) || !self::samePlace($from, $to) || !$this->isGatewayTown($to) || !$this->hasSamePlace()) {
            return $row;
        }
        $row['km']    = 0.0;
        $row['km_by'] = 'same_place';
        $row['why']   = MileageLookup::townOf($to) . ' is the airport area: the airport and garage running the plan already carries covers this, so 0 km is quoted and nothing more is needed. A hotel in any other town has its leg priced as usual.';
        return $row;
    }

    /** Whether a place is in the airport area — the planner's gateway town (tf_meta 'gateway' / 'gateway_out'), Katunayake. */
    private function isGatewayTown(string $place): bool
    {
        if ($this->gatewayTowns === null) {
            $in  = trim((string) ($this->meta('gateway') ?? 'Katunayake'));
            $out = trim((string) ($this->meta('gateway_out') ?? $in));
            $this->gatewayTowns = array_values(array_unique(array_filter([MileageLookup::townOf($in), MileageLookup::townOf($out)], static fn ($t) => $t !== '')));
        }
        $town = MileageLookup::townOf($place);
        foreach ($this->gatewayTowns as $g) {
            if (strcasecmp($g, $town) === 0) {
                return true;
            }
        }
        return false;
    }

    /** Whether the sector table can mark a leg quoted at 0 km because it starts and ends in the airport area (schema 35). */
    private function hasSamePlace(): bool
    {
        if ($this->hasSamePlace === null) {
            try {
                $col = $this->db->query("SHOW COLUMNS FROM `tf_intake_sector` LIKE 'km_by'")->getRowArray();
                $this->hasSamePlace = $col !== null && str_contains((string) ($col['Type'] ?? ''), "'same_place'");
            } catch (\Throwable $e) {
                $this->hasSamePlace = false;
            }
        }
        return $this->hasSamePlace;
    }

    /**
     * Which of the two figures the quotation is costed on, and the sentence that says so.
     *
     * Google by preference: it has an answer for a sector nobody has driven yet, and the
     * figure tends to sit above the driven record. The driven record when Google has none —
     * no key on this install, a place it could not find, or a line it could not reach —
     * and the leg says which. Neither: no figure at all, never a nought.
     *
     * @return array{0:?float, 1:string, 2:string}  km, km_by, why
     */
    private function quotedFigure(array $row, array $g): array
    {
        $src   = $this->quoteSource();
        $gOk   = $g['by'] === 'google' && $g['km'] !== null;
        $aOk   = $row['actual_km'] !== null;
        $typed = ($row['actual_by'] ?? '') === 'manual';
        $gTxt  = $gOk ? self::num((float) $g['km']) . ' km' : '';
        // the actual, said the way it actually is: a figure somebody typed is not "past tours"
        $aSay  = !$aOk
            ? 'No past tour on file for this leg.'
            : ($typed
                ? 'The actual is ' . self::num((float) $row['actual_km']) . ' km, typed by hand.'
                : 'Past tours drove ' . self::num((float) $row['actual_km']) . ' km over ' . (int) $row['samples'] . ' journey' . ((int) $row['samples'] === 1 ? '' : 's') . '.');
        $aWhy  = (string) $row['actual_why'];
        // the driven figure's name as the quoted one: the record's, or a typed actual
        $aBy   = $typed ? 'actual' : (string) $row['actual_by'];
        // why Google has nothing, in a clause: "Google could not place “X”" — never for a missing key, the checklist carries that once
        $gGap = (!$gOk && $g['by'] !== 'no_key' && trim((string) $g['why']) !== '') ? rtrim(trim((string) $g['why']), '.') : '';

        if ($src === 'google' && $gOk) {
            return [(float) $g['km'], 'google', 'Quoted at Google\'s ' . $gTxt . '. ' . $aSay];
        }
        if ($aOk) {
            if ($src === 'google') {
                // Google has nothing: the driven figure stands in, and says why it is doing so
                return [(float) $row['actual_km'], $aBy, $aWhy . ($gGap !== '' ? ' Quoted from ' . ($typed ? 'the typed actual' : 'past tours') . ': ' . $gGap . '.' : '')];
            }
            return [(float) $row['actual_km'], $aBy, $aWhy . ($gOk ? ' Google makes it ' . $gTxt . '; the driven figure is quoted by the setting on Mileage Allowances.' : '')];
        }
        if ($gOk) {
            return [(float) $g['km'], 'google', 'No past tour on file for this leg, so Google\'s ' . $gTxt . ' is quoted.'];
        }
        return [null, 'none', $aWhy . ($gGap !== '' ? ' ' . $gGap . '.' : '')];
    }

    /** One row of tf_intake_sector, written the same way whether the plan or a person made it. */
    private function insertSector(int $id, int $dayNo, int $seq, int $seqV, ?string $from, ?string $to, string $kind, string $origin, array $row, ?string $note): void
    {
        $cols = 'intake_id, day_no, seq, vehicle_seq, from_place, to_place, km, km_by, extra_km, extra_by, samples, samples_dropped, km_low, km_high, kind, origin, why, note';
        $bind = [
            $id, $dayNo, $seq, $seqV, self::cut($from, 190), self::cut($to, 190),
            $row['km'], $row['km_by'], $row['extra_km'], $row['extra_by'],
            $row['samples'], $row['dropped'], $row['low'], $row['high'], $kind, $origin,
            self::cut($row['why'], 400), self::cut($note, 255),
        ];
        if ($this->hasTwoFigures()) {
            $cols .= ', google_km, google_by, google_why, actual_km, actual_by, actual_why';
            array_push($bind, $row['google_km'] ?? null, $row['google_by'] ?? 'none', self::cut($row['google_why'] ?? null, 255),
                $row['actual_km'] ?? null, $row['actual_by'] ?? 'none', self::cut($row['actual_why'] ?? null, 400));
        }
        if ($this->hasActuals()) {
            $cols .= ', samples_post';
            $bind[] = (int) ($row['samples_post'] ?? 0);
        }
        // Which leg of the plan this is, so that editing its kilometres later does not cost
        // the day its other journeys. A leg a person adds themselves gets none: see saveSector.
        if ($origin === 'auto' && $this->hasPlanKey()) {
            $cols .= ', plan_key';
            $bind[] = self::cut(self::legKey($seqV, $dayNo, $from, $to, $kind), 400);
        }
        $this->db->query('INSERT INTO tf_intake_sector (' . $cols . ') VALUES (' . implode(',', array_fill(0, count($bind), '?')) . ')', $bind);
    }

    /**
     * Where a day out from the hotel goes: the towns the day's line and its visits name,
     * other than the town the party sleeps in, in the order the agent wrote them. Only
     * towns the driving history knows — a name it has never driven to is not a stop, and
     * the day says so instead (see dayOutNote). Nothing is added that the day does not name.
     *
     * @return array<int, array{town:string, slip:bool, given:string}>
     */
    private function dayOutStops(MileageLookup $km, array $day, string $here, ?string $alias, array $visits): array
    {
        $home = [mb_strtolower(MileageLookup::townOf($here)), mb_strtolower($here)];
        if ($alias !== null) {
            $home[] = mb_strtolower(MileageLookup::townOf($alias));
        }
        $stops = [];
        $add = static function (?array $t, string $given) use (&$stops, $home): void {
            if ($t === null || in_array(mb_strtolower($t['town']), $home, true)) {
                return;
            }
            foreach ($stops as $s) {
                if (strcasecmp($s['town'], $t['town']) === 0) {
                    return;
                }
            }
            $stops[] = ['town' => $t['town'], 'slip' => (bool) $t['slip'], 'given' => $given];
        };
        // the day's own line first ("Sigiriya/Polonnaruwa", "Anuradapura"), then its visits
        $area = trim((string) ($day['area'] ?? ''));
        if ($area !== '' && !self::samePlace($area, $here)) {
            foreach (MileageLookup::stopsIn($area) as $part) {
                $add($km->town($part) ?? $km->townIn($part), $part);
            }
        }
        foreach ($visits as $v) {
            $add($km->townIn($v), $v);
        }
        return $stops;
    }

    /** A day out that names no town the record knows: the local allowance, and a word on why. */
    private function dayOutNote(MileageLookup $km, array $day, string $here, array $visits, string $note): string
    {
        $area = trim((string) ($day['area'] ?? ''));
        $named = [];
        if ($area !== '' && !self::samePlace($area, $here)) {
            foreach (MileageLookup::stopsIn($area) as $part) {
                if ($km->town($part) === null && $km->townIn($part) === null) {
                    $named[] = $part;
                }
            }
        }
        if ($named === []) {
            return $note;
        }
        return trim($note . ' The day names "' . implode('", "', array_unique($named)) . '", which the driving history does not know, so it is charged as a day in ' . MileageLookup::townOf($here) . '. Add the legs by hand if the vehicle goes there.');
    }

    /**
     * Add a leg, or correct one. Either way it becomes the person's: from then on a
     * re-plan leaves that whole day alone.
     */
    public function saveSector(int $id, ?int $sectorId, array $s): array
    {
        $this->assertOpen($id);
        $from = self::cut($s['from_place'] ?? null, 190);
        $to   = self::cut($s['to_place'] ?? null, 190);
        $kind = in_array($s['kind'] ?? '', ['transfer', 'local', 'arrival', 'departure', 'positioning', 'idle', 'garage'], true) ? $s['kind'] : 'transfer';
        if ($from === null || $to === null) {
            throw new \InvalidArgumentException('A leg needs somewhere to start and somewhere to finish.');
        }
        $typed = array_key_exists('km', $s) && $s['km'] !== null && $s['km'] !== '';
        $km    = $typed ? round((float) $s['km'], 2) : null;
        if ($typed && $km < 0) {
            throw new \InvalidArgumentException('A distance cannot be negative.');
        }
        $extraTyped = array_key_exists('extra_km', $s) && $s['extra_km'] !== null && $s['extra_km'] !== '';
        $extra      = $extraTyped ? round((float) $s['extra_km'], 2) : null;
        if ($extraTyped && $extra < 0) {
            throw new \InvalidArgumentException('A local running allowance cannot be negative.');
        }

        // Nothing typed? Then the record and Google answer, exactly as they would for an auto leg.
        $f = $this->sectorFigures($this->mileage(), $from, $to, $kind);
        if ($typed) {
            // What the two figures would have been is kept in the reason, not thrown away: a
            // hand-typed 60 against 400 journeys that all say 89 is worth somebody seeing.
            $was = '';
            if (($f['google_by'] ?? 'none') === 'google' && $f['google_km'] !== null) {
                $was .= ' Google makes it ' . self::num((float) $f['google_km']) . ' km.';
            }
            if ($f['actual_km'] !== null && $f['samples'] > 0) {
                $was .= ' Past tours drove ' . self::num((float) $f['actual_km']) . ' km over ' . $f['samples'] . ' journey(s).';
            }
            $f['km']    = $km;
            $f['km_by'] = 'manual';
            $f['why']   = 'Distance set by hand by ' . ($this->user ?: 'a colleague') . '.' . $was;
        }
        if ($extraTyped) {
            $f['extra_km'] = $extra;
            $f['extra_by'] = 'manual';
        }

        $dayNo = max(0, (int) ($s['day_no'] ?? 0));
        if ($sectorId) {
            $set  = 'day_no = ?, from_place = ?, to_place = ?, km = ?, km_by = ?, extra_km = ?, extra_by = ?, samples = ?, samples_dropped = ?, km_low = ?, km_high = ?, kind = ?, why = ?, note = ?, origin = \'human\'';
            $bind = [
                $dayNo, $from, $to, $f['km'], $f['km_by'], $f['extra_km'], $f['extra_by'],
                $f['samples'], $f['dropped'], $f['low'], $f['high'], $kind,
                self::cut($f['why'], 400), self::cut($s['note'] ?? null, 255),
            ];
            if ($this->hasTwoFigures()) {
                $set .= ', google_km = ?, google_by = ?, google_why = ?, actual_km = ?, actual_by = ?, actual_why = ?';
                array_push($bind, $f['google_km'], $f['google_by'], self::cut($f['google_why'], 255), $f['actual_km'], $f['actual_by'], self::cut($f['actual_why'], 400));
            }
            if ($this->hasActuals()) {
                $set .= ', samples_post = ?';
                $bind[] = (int) ($f['samples_post'] ?? 0);
            }
            $this->db->query('UPDATE tf_intake_sector SET ' . $set . ' WHERE id = ? AND intake_id = ?', array_merge($bind, [$sectorId, $id]));
        } else {
            $seqV = max(0, (int) ($s['vehicle_seq'] ?? 0));
            $next = (int) ($this->db->query('SELECT COALESCE(MAX(seq), -1) + 1 AS n FROM tf_intake_sector WHERE intake_id = ? AND day_no = ? AND vehicle_seq = ?', [$id, $dayNo, $seqV])->getRowArray()['n'] ?? 0);
            $this->insertSector($id, $dayNo, $next, $seqV, $from, $to, $kind, 'human', $f, self::cut($s['note'] ?? null, 255));
        }
        $this->applyExcursionWaivers($id);
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * The actual mileage of a leg, typed by hand — into the actual column, beside Google's
     * figure and never in place of it. The leg becomes the person's (a re-plan leaves its
     * day alone), and the quoted figure follows the setting: Google's stays quoted where it
     * is preferred and present; where Google has nothing, or the setting prefers the driven
     * figure, the typed actual is quoted and says so. Blank clears it, and the record's
     * own figure comes back.
     */
    public function saveActual(int $id, int $sectorId, $km, ?string $note = null): array
    {
        $this->assertOpen($id);
        if (!$this->hasTwoFigures()) {
            throw new \RuntimeException('The sector table is not at the current version. Open Tour Inquiry and press Install first.');
        }
        $s = $this->db->query('SELECT * FROM tf_intake_sector WHERE id = ? AND intake_id = ?', [$sectorId, $id])->getRowArray();
        if (!$s) {
            throw new \InvalidArgumentException('That leg is not on this inquiry.');
        }
        if (!self::isDrivenLeg((string) $s['kind'], $s['from_place'], $s['to_place'])) {
            throw new \InvalidArgumentException('That leg is an allowance, not a journey between two places; its figure is set on Mileage Allowances.');
        }
        $typed = $km !== null && $km !== '';
        if ($typed && (!is_numeric($km) || (float) $km < 0)) {
            throw new \InvalidArgumentException('The actual mileage has to be a number of kilometres, nought or more.');
        }
        $who  = $this->user ?: 'a colleague';
        $note = self::cut($note, 200);
        if ($typed) {
            $actual = ['km' => round((float) $km, 2), 'by' => 'manual', 'samples' => 0, 'samples_post' => 0, 'dropped' => 0, 'low' => null, 'high' => null,
                       'why' => 'Actual mileage typed by ' . $who . ' on ' . date('j M Y') . ($note !== null ? ' — ' . $note : '') . '.'];
        } else {
            // cleared: the record answers again, exactly as on a fresh plan
            $hit = $this->mileage()->between($s['from_place'], $s['to_place']);
            $actual = ['km' => $hit['km'], 'by' => (string) $hit['km_by'], 'samples' => (int) $hit['samples'], 'samples_post' => (int) ($hit['samples_post'] ?? 0),
                       'dropped' => (int) $hit['dropped'], 'low' => $hit['low'], 'high' => $hit['high'], 'why' => (string) $hit['why']];
        }
        $set  = 'actual_km = ?, actual_by = ?, actual_why = ?, samples = ?, samples_dropped = ?, km_low = ?, km_high = ?, origin = \'human\'';
        $bind = [$actual['km'], $actual['by'], self::cut($actual['why'], 400), $actual['samples'], $actual['dropped'], $actual['low'], $actual['high']];
        if ($this->hasActuals()) {
            $set .= ', samples_post = ?';
            $bind[] = $actual['samples_post'];
        }
        if ($s['km_by'] !== 'manual') {
            // the quoted figure follows the setting; a quoted override typed by hand stands
            $g = ['by' => (string) $s['google_by'], 'km' => $s['google_km'] === null ? null : (float) $s['google_km'], 'why' => (string) ($s['google_why'] ?? '')];
            [$qKm, $qBy, $qWhy] = $this->quotedFigure(['actual_km' => $actual['km'], 'actual_by' => $actual['by'], 'actual_why' => $actual['why'], 'samples' => $actual['samples']], $g);
            $set .= ', km = ?, km_by = ?, why = ?';
            array_push($bind, $qKm, $qBy, self::cut($qWhy, 400));
        }
        $this->db->query('UPDATE tf_intake_sector SET ' . $set . ' WHERE id = ?', array_merge($bind, [(int) $s['id']]));
        $this->applyExcursionWaivers($id);
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * The extra running a leg is allowed — the kilometres the office gives the driver for
     * site visits and city tours on top of the journey itself. Typed by hand into its own
     * column; the driven figures (Google's and the actual) are never touched by it, and the
     * charged total follows at once. Blank puts the automatic figure back: the median extra
     * the drivers logged on that journey, or the destination town's allowance from Master
     * Files → Extra Kilometers.
     */
    public function saveExtra(int $id, int $sectorId, $km, ?string $note = null): array
    {
        $this->assertOpen($id);
        $s = $this->db->query('SELECT * FROM tf_intake_sector WHERE id = ? AND intake_id = ?', [$sectorId, $id])->getRowArray();
        if (!$s) {
            throw new \InvalidArgumentException('That leg is not on this inquiry.');
        }
        if (!empty($s['removed_at'])) {
            throw new \InvalidArgumentException('That leg has been taken off the mileage. Restore it first.');
        }
        $typed = $km !== null && $km !== '';
        if ($typed && (!is_numeric($km) || (float) $km < 0)) {
            throw new \InvalidArgumentException('The extra running has to be a number of kilometres, nought or more.');
        }
        if ($typed) {
            $extra   = round((float) $km, 2);
            $extraBy = 'manual';
        } else {
            // cleared: whatever the leg would have been given on a fresh plan
            [$extra, $extraBy] = $this->extraForLeg($s);
        }
        $this->db->query(
            'UPDATE tf_intake_sector SET extra_km = ?, extra_by = ?, note = ?, origin = \'human\' WHERE id = ?',
            [$extra, $extraBy, self::cut($note !== null && trim($note) !== '' ? $note : ($s['note'] ?? null), 255), (int) $s['id']]
        );
        $this->applyExcursionWaivers($id);
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * What a leg's extra running would be without anybody typing it: the allowance for a
     * day spent in one town, the median extra the drivers logged on that journey, or the
     * destination town's figure from the Extra Kilometers master. Null when nothing covers it.
     *
     * @return array{0:?float, 1:string}  km, extra_by
     */
    private function extraForLeg(array $s): array
    {
        $kind = (string) $s['kind'];
        $from = $s['from_place'];
        $to   = $s['to_place'];
        if (in_array($kind, ['garage', 'idle'], true)) {
            return [null, 'none'];
        }
        if ($kind === 'local' && $from !== null && $to !== null && self::samePlace($from, $to)) {
            // a day in one town: the town's own figure is the leg, not an extra on top
            return [null, 'none'];
        }
        $hit = $this->mileage()->between($from, $to);
        if ($hit['extra_km'] !== null) {
            return [(float) $hit['extra_km'], (string) $hit['extra_by']];
        }
        $city = $this->allowances()->cityKm($to);
        return $city !== null ? [(float) $city, 'city_allowance'] : [null, 'none'];
    }

    /**
     * Ask Google about the legs of an inquiry — every driven leg, or one — and put its
     * answer beside each. A leg quoted from the driven record because Google had nothing
     * takes Google's figure now if the setting prefers it; a distance somebody typed by
     * hand is never replaced, only shown Google's figure beside it. `refresh` forgets what
     * was kept for those pairs and asks Google again.
     */
    public function googleLegs(int $id, ?int $sectorId = null, bool $refresh = false): array
    {
        $this->assertOpen($id);
        if (!$this->hasTwoFigures()) {
            throw new \RuntimeException('The sector table is not at the current version. Open Tour Inquiry and press Install first.');
        }
        $g = $this->google();
        if (!$g->hasKey()) {
            throw new \RuntimeException((string) $g->keyProblem());
        }
        $rows = $this->db->query(
            'SELECT * FROM tf_intake_sector WHERE intake_id = ?' . ($sectorId ? ' AND id = ?' : '') . ' ORDER BY vehicle_seq, day_no, seq, id',
            $sectorId ? [$id, $sectorId] : [$id]
        )->getResultArray();
        $legs = array_values(array_filter($rows, static fn ($s) => empty($s['removed_at']) && self::isDrivenLeg((string) $s['kind'], $s['from_place'], $s['to_place'])));
        if ($legs === []) {
            throw new \InvalidArgumentException($sectorId ? 'That leg is an allowance, not a journey between two places, so there is nothing to ask Google.' : 'There is no driven leg on this inquiry to ask Google about.');
        }
        if ($refresh) {
            foreach ($legs as $s) {
                $g->forget($s['from_place'], $s['to_place']);
            }
        }
        $g->warm(array_map(static fn ($s) => [$s['from_place'], $s['to_place']], $legs), $refresh);

        $asked = $answered = $quoted = $missing = 0;
        foreach ($legs as $s) {
            $asked++;
            $a = $g->distance($s['from_place'], $s['to_place']);
            if ($a['by'] === 'google') {
                $answered++;
            } else {
                $missing++;
            }
            $row = [
                'km' => $s['km'] === null ? null : (float) $s['km'], 'km_by' => (string) $s['km_by'], 'why' => (string) $s['why'],
                'samples' => (int) $s['samples'],
                'actual_km' => $s['actual_km'] === null ? null : (float) $s['actual_km'], 'actual_by' => (string) $s['actual_by'], 'actual_why' => (string) ($s['actual_why'] ?? ''),
            ];
            $set  = 'google_km = ?, google_by = ?, google_why = ?';
            $bind = [$a['km'], $a['by'], self::cut($a['why'] !== '' ? $a['why'] : null, 255)];
            if ($s['km_by'] !== 'manual') {
                // not typed by hand: the quoted figure follows the setting, as it would on a fresh plan
                [$qKm, $qBy, $qWhy] = $this->quotedFigure($row, $a);
                if ($qBy === 'google') {
                    $quoted++;
                }
                $set .= ', km = ?, km_by = ?, why = ?';
                array_push($bind, $qKm, $qBy, self::cut($qWhy, 400));
            } elseif ($a['by'] === 'google' && $a['km'] !== null && !str_contains((string) $s['why'], 'Google makes it')) {
                $set .= ', why = ?';
                $bind[] = self::cut(rtrim((string) $s['why']) . ' Google makes it ' . self::num((float) $a['km']) . ' km.', 400);
            }
            $this->db->query('UPDATE tf_intake_sector SET ' . $set . ' WHERE id = ?', array_merge($bind, [(int) $s['id']]));
        }
        $this->touch($id);
        try {
            $this->activity()->record(
                'inquiry', $id, $id, null, 'mileage_google',
                'Asked Google about ' . $asked . ' leg' . ($asked === 1 ? '' : 's') . ($refresh ? ' again' : '') . ': ' . $answered . ' answered'
                    . ($missing ? ', ' . $missing . ' without a figure' : '') . '; ' . $quoted . ' now quoted at Google\'s distance.'
            );
        } catch (\Throwable $e) {
            // the history never stops the work
        }
        return $this->get($id);
    }

    /**
     * Take a leg off the mileage. A leg the plan made is struck through and stays off —
     * the row is kept as the marker, so the next re-plan does not put it back and the
     * pane can offer Restore. A leg somebody added by hand is simply removed. Either way
     * the totals and the costing follow at once.
     */
    public function deleteSector(int $id, int $sectorId): array
    {
        $this->assertOpen($id);
        // A leg the plan made is struck through, not thrown away — the marker is what keeps
        // the plan from laying it down again. That holds for a leg somebody edited the
        // kilometres on too: it is still the plan's leg. Only a leg a person added
        // themselves is simply removed; the plan was never going to make that one.
        $s = $this->db->query('SELECT origin, removed_at' . ($this->hasPlanKey() ? ', plan_key' : '') . ' FROM tf_intake_sector WHERE id = ? AND intake_id = ?', [$sectorId, $id])->getRowArray();
        $ofPlan = $s && ((string) $s['origin'] === 'auto' || trim((string) ($s['plan_key'] ?? '')) !== '');
        if ($ofPlan && $this->hasRemoved()) {
            $this->db->query('UPDATE tf_intake_sector SET removed_at = ?, removed_by = ?, waived_by = NULL WHERE id = ?', [date('Y-m-d H:i:s'), self::cut($this->user ?: 'a colleague', 100), $sectorId]);
        } else {
            $this->db->query('DELETE FROM tf_intake_sector WHERE id = ? AND intake_id = ?', [$sectorId, $id]);
        }
        $this->applyExcursionWaivers($id);
        $this->touch($id);
        return $this->get($id);
    }

    /** Put a struck-off leg back on the mileage. */
    public function restoreSector(int $id, int $sectorId): array
    {
        $this->assertOpen($id);
        if ($this->hasRemoved()) {
            $this->db->query('UPDATE tf_intake_sector SET removed_at = NULL, removed_by = NULL WHERE id = ? AND intake_id = ?', [$sectorId, $id]);
        }
        $this->applyExcursionWaivers($id);
        $this->touch($id);
        return $this->get($id);
    }

    /** Hand the whole itinerary's driving back to the history, losing every hand-typed leg. */
    public function autoSectors(int $id): array
    {
        $this->assertOpen($id);
        $this->planSectors($id, true);
        return $this->get($id);
    }

    /**
     * What the driving comes to, and what it costs.
     *
     * Base and allowance are added up apart and then together, because they answer
     * different questions and the rest of the system keeps them apart too. The charged
     * total is base + allowance, which is what the quotation editor has always totalled.
     *
     * @param array<int, array<string, mixed>> $sectors
     * @param array<int, array<string, mixed>> $vehicles
     * @return array<string, mixed>
     */
    public static function mileageSummary(array $sectors, array $vehicles): array
    {
        // one bucket per vehicle line, keyed by its position; legs name the line they belong to
        $lines = [];
        foreach ($vehicles as $v) {
            $lines[(int) ($v['sort_order'] ?? 0)] = $v;
        }
        if ($lines === []) {
            $lines[0] = null;
        }
        // The two figures are added up beside the quoted one: Google's road distance and the
        // driven record, each over the legs that have it, and the pair over the legs that have
        // BOTH — that last pair is the only fair comparison, and its difference is the buffer.
        $zero = ['legs' => 0, 'km' => 0.0, 'extra_km' => 0.0, 'waived_km' => 0.0, 'charged_km' => 0.0, 'unpriced' => 0, 'thin' => 0,
                 'google_km' => 0.0, 'google_legs' => 0, 'actual_km' => 0.0, 'actual_legs' => 0, 'both_google_km' => 0.0, 'both_actual_km' => 0.0, 'both_legs' => 0,
                 'quoted_google' => 0, 'quoted_actual' => 0, 'quoted_manual' => 0, 'google_missing' => 0, 'google_unasked' => 0, 'no_key' => 0,
                 'actual_typed' => 0, 'actual_learned' => 0, 'removed' => 0, 'extra_typed' => 0, 'extra_none' => 0];
        $by = [];
        foreach ($lines as $seqV => $v) {
            $by[$seqV] = ['vehicle_seq' => $seqV, 'label' => $v['label'] ?? null, 'type' => $v['type'] ?? null, 'qty' => max(1, (int) ($v['qty'] ?? 1)),
                'group_id' => $v['group_id'] ?? null, 'day_from' => $v['day_from'] ?? null, 'day_to' => $v['day_to'] ?? null,
                'km_rate' => ($v !== null && $v['km_rate'] !== null && $v['km_rate'] !== '') ? (float) $v['km_rate'] : null, 'currency' => (string) ($v['currency'] ?? 'LKR'), 'amount' => null] + $zero;
        }
        foreach ($sectors as $s) {
            $seqV = (int) ($s['vehicle_seq'] ?? 0);
            if (!isset($by[$seqV])) {
                $by[$seqV] = ['vehicle_seq' => $seqV, 'label' => null, 'type' => null, 'qty' => 1, 'group_id' => null, 'day_from' => null, 'day_to' => null,
                    'km_rate' => null, 'currency' => 'LKR', 'amount' => null] + $zero;
            }
            $b = &$by[$seqV];
            if (!empty($s['removed_at'])) {
                // taken off by a person: shown struck through, costed at nothing, counted here only
                $b['removed']++;
                unset($b);
                continue;
            }
            $b['legs']++;
            $legKm = ($s['km'] === null ? 0.0 : (float) $s['km']) + ($s['extra_km'] === null ? 0.0 : (float) $s['extra_km']);
            if ($s['km'] === null) {
                $b['unpriced']++;
            }
            // a leg waived because an excursion that day already includes transport is shown, never charged
            if (!empty($s['waived_by'])) {
                $b['waived_km'] += $legKm;
            } else {
                if ($s['km'] !== null) {
                    $b['km'] += (float) $s['km'];
                }
                if ($s['extra_km'] !== null) {
                    $b['extra_km'] += (float) $s['extra_km'];
                }
                $gk = array_key_exists('google_km', $s) && $s['google_km'] !== null ? (float) $s['google_km'] : null;
                $ak = array_key_exists('actual_km', $s) && $s['actual_km'] !== null ? (float) $s['actual_km'] : null;
                if ($gk !== null) {
                    $b['google_km'] += $gk;
                    $b['google_legs']++;
                }
                if ($ak !== null) {
                    $b['actual_km'] += $ak;
                    $b['actual_legs']++;
                }
                if ($gk !== null && $ak !== null) {
                    $b['both_google_km'] += $gk;
                    $b['both_actual_km'] += $ak;
                    $b['both_legs']++;
                }
            }
            // only a quoted figure that rests on one or two past journeys is thin; Google's is not a sample
            if (in_array($s['km_by'], ['history', 'town_history'], true) && (int) $s['samples'] > 0 && (int) $s['samples'] < 3) {
                $b['thin']++;
            }
            if ($s['km_by'] === 'google') {
                $b['quoted_google']++;
            } elseif ($s['km_by'] === 'manual') {
                $b['quoted_manual']++;
            } elseif (in_array($s['km_by'], ['history', 'town_history', 'actual'], true)) {
                $b['quoted_actual']++;
            }
            if (($s['actual_by'] ?? '') === 'manual') {
                $b['actual_typed']++;
            }
            if (($s['extra_by'] ?? '') === 'manual') {
                $b['extra_typed']++;
            }
            // a leg that drives somewhere and is given nothing for the site visit or city tour
            if (self::isDrivenLeg((string) $s['kind'], $s['from_place'] ?? null, $s['to_place'] ?? null) && $s['extra_km'] === null && ($s['km_by'] ?? '') !== 'same_place') {
                $b['extra_none']++;
            }
            if ((int) ($s['samples_post'] ?? 0) > 0) {
                $b['actual_learned']++;
            }
            if (self::isDrivenLeg((string) $s['kind'], $s['from_place'] ?? null, $s['to_place'] ?? null) && ($s['google_by'] ?? 'none') !== 'google' && ($s['km_by'] ?? '') !== 'same_place') {
                if (($s['google_by'] ?? 'none') === 'none') {
                    $b['google_unasked']++;   // planned before Google was part of this, and not asked since
                } else {
                    $b['google_missing']++;
                    if ($s['google_by'] === 'no_key') {
                        $b['no_key']++;
                    }
                }
            }
            unset($b);
        }
        $tot = $zero + ['amount' => 0.0, 'km_rate' => 0.0];
        $haveRate = $lines !== [] && current($lines) !== null;
        foreach ($by as &$b) {
            $b['charged_km'] = round($b['km'] + $b['extra_km'], 1);
            foreach (['km', 'extra_km', 'waived_km', 'google_km', 'actual_km', 'both_google_km', 'both_actual_km'] as $k) {
                $b[$k] = round($b[$k], 1);
            }
            // The vehicle is paid by the kilometre. A rate missing from the master is said out loud, never treated as nought.
            if ($b['km_rate'] !== null) {
                $b['amount'] = round($b['charged_km'] * $b['km_rate'] * $b['qty'], 2);
                $tot['amount'] += $b['amount'];
                $tot['km_rate'] += $b['km_rate'] * $b['qty'];
            } else {
                $haveRate = false;
            }
            foreach (array_keys($zero) as $k) {
                $tot[$k] += $b[$k];
            }
        }
        unset($b);
        return [
            'legs'       => $tot['legs'],
            'km'         => round($tot['km'], 1),
            'extra_km'   => round($tot['extra_km'], 1),
            'waived_km'  => round($tot['waived_km'], 1),
            'charged_km' => round($tot['charged_km'], 1),
            'unpriced'   => $tot['unpriced'],
            'thin'       => $tot['thin'],
            'km_rate'    => $haveRate ? round($tot['km_rate'], 2) : null,
            'amount'     => ($haveRate && $tot['amount'] > 0) ? round($tot['amount'], 2) : null,
            'currency'   => $vehicles !== [] ? (string) ($vehicles[0]['currency'] ?? 'LKR') : 'LKR',
            // the two figures side by side, over the charged legs
            'google_km'      => round($tot['google_km'], 1),
            'google_legs'    => $tot['google_legs'],
            'actual_km'      => round($tot['actual_km'], 1),
            'actual_legs'    => $tot['actual_legs'],
            'both_legs'      => $tot['both_legs'],
            'both_google_km' => round($tot['both_google_km'], 1),
            'both_actual_km' => round($tot['both_actual_km'], 1),
            'buffer_km'      => $tot['both_legs'] > 0 ? round($tot['both_google_km'] - $tot['both_actual_km'], 1) : null,
            'quoted_google'  => $tot['quoted_google'],
            'quoted_actual'  => $tot['quoted_actual'],
            'quoted_manual'  => $tot['quoted_manual'],
            'google_missing' => $tot['google_missing'],
            'google_unasked' => $tot['google_unasked'],
            'no_key'         => $tot['no_key'],
            'actual_typed'   => $tot['actual_typed'],
            'actual_learned' => $tot['actual_learned'],
            'removed'        => $tot['removed'],
            'extra_typed'    => $tot['extra_typed'],
            'extra_none'     => $tot['extra_none'],
            'by_vehicle' => array_values($by),
        ];
    }

    /**
     * Which of a night's several names to drive to.
     *
     * A night can go by three: the area as the agent wrote it, the area the rates panel
     * says the matched hotel is actually in, and the hotel's own name. The agent's words
     * are kept word for word everywhere else in this panel, and that is right — but
     * "Galle or near Galle" and "Ella / Nuwaraeliya" are not places, and no amount of
     * looking will find a road to them.
     *
     * So the history is asked which of the three it recognises, in that order, and the
     * first one it has actually driven to wins. Nothing is rewritten and nothing is
     * guessed at: every candidate is a fact the night already carries, and when the
     * answer is not the agent's own wording the leg says so on its face.
     *
     * @return array{0: ?string, 1: string}
     */
    private function placeFor(array $day, MileageLookup $km): array
    {
        $area  = trim((string) ($day['area'] ?? ''));
        $panel = trim((string) ($day['hotel_area'] ?? ''));
        $hotel = trim((string) ($day['hotel_name_raw'] ?? ''));

        $tried = [];
        foreach ([[$area, 'area'], [$panel, 'panel'], [$hotel, 'hotel']] as [$name, $source]) {
            if ($name === '') {
                continue;
            }
            $tried[] = [$name, $source];
            if ($km->knows($name) <= 0) {
                continue;
            }
            // Some location records are a whole postal address — "Good Wood Airport Hotel,
            // Canada Friendship Road, Katunayake" is on file as an area. The distance comes
            // out right either way because the town is what gets matched, but a leg reading
            // "Negombo → Good Wood Airport Hotel, Canada Friendship Road" helps nobody. So
            // where the name itself has never been driven to and only its town has, the
            // town is what the leg shows.
            $town = MileageLookup::townOf($name);
            if ($town !== '' && strcasecmp($town, $name) !== 0 && $km->knows($name, true) <= 0 && $km->knows($town) > 0) {
                $name = $town;
            }
            if ($source === 'area') {
                return [mb_substr($name, 0, 190), ''];
            }
            $why = $source === 'panel'
                ? 'Nobody has driven to "' . $tried[0][0] . '", so ' . $name . ' — the area the rates panel puts ' . ($hotel !== '' ? $hotel : 'this hotel') . ' in — was used instead.'
                : 'Nobody has driven to "' . $tried[0][0] . '", so the hotel\'s own name was used instead.';
            return [mb_substr($name, 0, 190), $why];
        }
        // Nothing the history knows. The night still gets a leg — with the agent's own
        // words on it and no distance — rather than being left out of the itinerary.
        return $tried === [] ? [null, ''] : [mb_substr($tried[0][0], 0, 190), ''];
    }

    /** Two nights in Kandy are two nights in Kandy, whichever hotel each one names. */
    private static function samePlace(string $a, string $b): bool
    {
        return strcasecmp(trim($a), trim($b)) === 0
            || strcasecmp(MileageLookup::townOf($a), MileageLookup::townOf($b)) === 0;
    }

    private static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    }

    /**
     * The freelance drivers and guides on the supplier ledger.
     *
     * Crew are freelancers, not staff, so they live in `supplier` under the 6-9-11 heading
     * — the same accounts driver_allocation already allocates and the payment side already
     * pays. Each carries their own agreed bata, the language they work in, and the licence
     * dates that say what they may do.
     *
     * @return array<int, array{acc:string,name:string,bata:?float,language:?string,is_driver:bool,is_guide:bool}>
     */
    public function freelancers(?string $kind = null): array
    {
        try {
            $rows = $this->db->query(
                "SELECT Sup_Acc_No AS acc, Name AS name, bata, language, dled, gled, v_num
                 FROM supplier
                 WHERE Sup_Acc_No LIKE '6-9-11-%' AND Name IS NOT NULL AND Name <> ''
                 ORDER BY Name"
            )->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $isDriver = trim((string) ($r['dled'] ?? '')) !== '' && ($r['dled'] !== '0000-00-00');
            $isGuide  = trim((string) ($r['gled'] ?? '')) !== '' && ($r['gled'] !== '0000-00-00');
            if (!$isDriver && !$isGuide) {
                // no licence recorded either way: a driver if they have a vehicle against them,
                // otherwise left out rather than offered as something they may not be
                $isDriver = trim((string) ($r['v_num'] ?? '')) !== '';
                if (!$isDriver) {
                    continue;
                }
            }
            if ($kind === 'driver' && !$isDriver) {
                continue;
            }
            if ($kind === 'guide' && !$isGuide) {
                continue;
            }
            $out[] = [
                'acc'       => (string) $r['acc'],
                'name'      => trim((string) $r['name']),
                'bata'      => ($r['bata'] === null || $r['bata'] === '') ? null : (float) $r['bata'],
                'language'  => trim((string) ($r['language'] ?? '')) ?: null,
                'is_driver' => $isDriver,
                'is_guide'  => $isGuide,
            ];
        }
        return $out;
    }

    /** Add, change or drop one crew line by hand. */
    /** Whether the inquiry remembers that a person decided its crew (schema 20). */
    private function hasCrewBy(): bool
    {
        if ($this->hasCrewBy === null) {
            try {
                $this->hasCrewBy = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'crew_by'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasCrewBy = false;
            }
        }
        return $this->hasCrewBy;
    }

    private function crewIsTheirs(int $id): void
    {
        if ($this->hasCrewBy()) {
            $this->db->query("UPDATE tf_intake SET crew_by = 'human' WHERE id = ?", [$id]);
        }
    }

    public function saveCrew(int $id, string $code, array $d): array
    {
        $this->assertOpen($id);
        if (!empty($d['delete'])) {
            $this->db->query('DELETE FROM tf_intake_crew WHERE intake_id = ? AND role_code = ?', [$id, $code]);
            // taken off on purpose: the planner does not put it back
            $this->crewIsTheirs($id);
            $this->costCrew($id);
            $this->touch($id);
            return $this->get($id);
        }
        $role = null;
        foreach ($this->crewRoles() as $r) {
            if ($r['code'] === $code) {
                $role = $r;
            }
        }
        if ($role === null) {
            throw new \InvalidArgumentException('That is not a crew role this system holds.');
        }
        $days = array_key_exists('days', $d) ? self::intOrNull($d['days']) : null;
        // schema 32: a number typed is the person's and stays; left empty, the days follow the programme
        $daysBy = $days !== null ? 'human' : 'auto';
        $note = self::cut($d['note'] ?? null, 255);

        // Who is engaged, if anybody is yet. Only somebody actually on the supplier ledger:
        // a freelancer nobody can pay is not an allocation, it is a note to self.
        $acc = trim((string) ($d['supplier_acc'] ?? ''));
        $who = null;
        if ($acc !== '') {
            foreach ($this->freelancers() as $f) {
                if ($f['acc'] === $acc) {
                    $who = $f;
                    break;
                }
            }
            if ($who === null) {
                throw new \InvalidArgumentException('That person is not on the supplier ledger. Add them under Master Files → Supplier first.');
            }
        }

        $this->db->query(
            "INSERT INTO tf_intake_crew (intake_id, role_code, role_name, kind, language, days, bata, currency, chosen, supplier_acc, person_name, person_bata, why, note, sort_order)
             VALUES (?,?,?,?,?,?,?,?,'manual',?,?,?,?,?,0)
             ON DUPLICATE KEY UPDATE days = VALUES(days), note = VALUES(note), chosen = 'manual', why = VALUES(why), bata = VALUES(bata),
                supplier_acc = VALUES(supplier_acc), person_name = VALUES(person_name), person_bata = VALUES(person_bata)",
            [$id, $role['code'], $role['name'], $role['kind'], $role['language'], $days, $role['bata'], $role['currency'],
             $who['acc'] ?? null, self::cut($who['name'] ?? null, 190), $who['bata'] ?? null,
             $who !== null ? ($who['name'] . ' engaged by ' . ($this->user ?: 'a colleague') . '.') : ('Chosen by ' . ($this->user ?: 'a colleague') . '.'), $note]
        );
        if ($this->hasCrewDaysBy()) {
            $this->db->query('UPDATE tf_intake_crew SET days_by = ? WHERE intake_id = ? AND role_code = ?', [$daysBy, $id, $role['code']]);
        }
        $this->crewIsTheirs($id);
        $this->costCrew($id);
        $this->planVehicle($id);   // a guide added or dropped changes the seats needed
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * Change a line's type or language: the line moves to another role of the same kind
     * and keeps its days, its note and whoever is engaged. A driver line stays in the
     * driver section and a guide line in the guide section; the master's bata for the new
     * role is what gets costed from here on.
     */
    public function changeCrew(int $id, string $from, string $to, array $d = []): array
    {
        $this->assertOpen($id);
        $old = $this->db->query('SELECT * FROM tf_intake_crew WHERE intake_id = ? AND role_code = ?', [$id, $from])->getRowArray();
        if (!$old) {
            throw new \InvalidArgumentException('That line is not on this inquiry any more.');
        }
        $d = ['days' => array_key_exists('days', $d) ? $d['days'] : ((string) ($old['days_by'] ?? '') === 'human' || !$this->hasCrewDaysBy() ? $old['days'] : null), 'note' => array_key_exists('note', $d) ? $d['note'] : $old['note'],
              'supplier_acc' => array_key_exists('supplier_acc', $d) ? $d['supplier_acc'] : $old['supplier_acc']];
        if ($to === $from) {
            return $this->saveCrew($id, $to, $d);
        }
        $role = null;
        foreach ($this->crewRoles() as $r) {
            if ($r['code'] === $to) {
                $role = $r;
            }
        }
        if ($role === null) {
            throw new \InvalidArgumentException('That is not a crew role this system holds.');
        }
        if ($role['kind'] !== (string) $old['kind']) {
            throw new \InvalidArgumentException('A ' . $old['kind'] . ' line stays a ' . $old['kind'] . '. Remove it and add a ' . $role['kind'] . ' under ' . ucfirst($role['kind']) . ' allocation instead.');
        }
        if ($this->db->query('SELECT id FROM tf_intake_crew WHERE intake_id = ? AND role_code = ?', [$id, $to])->getRowArray()) {
            throw new \InvalidArgumentException($role['name'] . ' is already on this inquiry — change that line, or remove this one.');
        }
        // whoever is engaged comes across if they are still on the ledger; checked BEFORE
        // the old line goes, so a refusal leaves the inquiry exactly as it was
        $acc = trim((string) ($d['supplier_acc'] ?? ''));
        $carried = true;
        if ($acc !== '' && !in_array($acc, array_column($this->freelancers(), 'acc'), true)) {
            $carried = false;
            $d['supplier_acc'] = '';
        }
        $this->db->query('DELETE FROM tf_intake_crew WHERE intake_id = ? AND role_code = ?', [$id, $from]);
        $this->saveCrew($id, $to, $d);
        $why ='Changed from ' . $old['role_name'] . ' to ' . $role['name'] . ' by ' . ($this->user ?: 'a colleague') . '.'
            . ($acc !== '' ? ($carried ? ' ' . trim((string) $old['person_name']) . ' stays engaged.' : ' ' . trim((string) $old['person_name']) . ' was not carried over: no longer on the supplier ledger.') : '');
        $this->db->query('UPDATE tf_intake_crew SET why = ? WHERE intake_id = ? AND role_code = ?', [self::cut($why, 255), $id, $to]);
        return $this->get($id);
    }

    /** Put the crew back under the planner's control. */
    public function autoCrew(int $id): array
    {
        $this->assertOpen($id);
        $this->db->query('DELETE FROM tf_intake_crew WHERE intake_id = ?', [$id]);
        $this->planCrew($id, true);
        $this->planVehicle($id, true);
        return $this->get($id);
    }

    /**
     * Days × bata, where both are known. A role whose bata the business has not set yet
     * carries no amount at all rather than a nought that would quietly under-quote.
     *
     * The bata is the master's for the role — the category and language chosen — and
     * nothing else (schema 23): a freelancer's own agreed figure on the ledger is carried
     * for the payment side and shown beside it, but the quotation is costed at the
     * master's rate, the way a hotel is quoted at its published rate.
     */
    private function costCrew(int $id): void
    {
        $nights = self::intOrNull($this->db->query('SELECT nights FROM tf_intake WHERE id = ?', [$id])->getRowArray()['nights'] ?? null);
        // schema 32: a line's days are the programme's — every day the party is served, arrival and
        // departure included when something happens on them — unless a person typed them
        $byRule = $this->hasCrewDaysBy();
        $prog   = $byRule ? $this->programmeDaysFor($id) : [];
        foreach ($this->db->query('SELECT id, days, bata' . ($byRule ? ', days_by' : '') . ' FROM tf_intake_crew WHERE intake_id = ?', [$id])->getResultArray() as $c) {
            if ($byRule && (string) ($c['days_by'] ?? '') !== 'human') {
                $days = $prog !== [] ? count($prog) : null;
                $rate = ($c['bata'] === null || $c['bata'] === '') ? null : (float) $c['bata'];
                $amt  = ($days !== null && $rate !== null) ? round($days * $rate, 2) : null;
                $this->db->query("UPDATE tf_intake_crew SET days = ?, days_by = 'auto', amount = ? WHERE id = ?", [$days, $amt, (int) $c['id']]);
                continue;
            }
            $days = self::intOrNull($c['days']) ?? $nights;
            $rate = ($c['bata'] === null || $c['bata'] === '') ? null : (float) $c['bata'];
            $amt  = ($days !== null && $rate !== null) ? round($days * $rate, 2) : null;
            $this->db->query('UPDATE tf_intake_crew SET days = ?, amount = ? WHERE id = ?', [$days, $amt, (int) $c['id']]);
        }
    }

    // =====================================================================
    //  Masters the panel picks from
    // =====================================================================

    /**
     * The agents a quotation picks from: the customers marked as agents under Master
     * Files → Consignees (customers.agent = 1). Read only — an agent record is created
     * and marked there, never from here.
     *
     * The ledger holds ticketing customers beside the agents, so the marked ones are the
     * list. Until the first customer has been marked the whole ledger is offered, so a
     * screen never turns up empty; agentsMarked() says which of the two it is.
     *
     * @return array<int, array{acc:string,name:string,long:?string,email:?string,country:?string,margin:?int,is_agent:bool}>
     */
    public function agents(): array
    {
        $all    = $this->customers();
        $marked = array_values(array_filter($all, static fn (array $a) => $a['is_agent']));
        return $marked !== [] ? $marked : $all;
    }

    /**
     * Everyone on the customer ledger, agent or not, each with the mark. The full list is
     * for the one case the marked list does not cover — a customer that is not marked yet
     * has to be picked on purpose, and is then flagged on the inquiry.
     *
     * @return array<int, array{acc:string,name:string,long:?string,email:?string,country:?string,margin:?int,is_agent:bool}>
     */
    public function customers(): array
    {
        if ($this->customerRows !== null) {
            return $this->customerRows;
        }
        try {
            $rows = $this->db->query(
                'SELECT Sup_Acc_No AS acc, Name AS name, long_name AS long_name, email, country, margin, agent
                 FROM customers WHERE Name IS NOT NULL AND Name <> "" ORDER BY Name'
            )->getResultArray();
        } catch (\Throwable $e) {
            return $this->customerRows = [];
        }
        $out = [];
        foreach ($rows as $r) {
            $acc = trim((string) $r['acc']);
            if ($acc === '') {
                continue;
            }
            $out[] = [
                'acc'     => $acc,
                'name'    => trim((string) $r['name']),
                'long'    => trim((string) ($r['long_name'] ?? '')) ?: null,
                'email'   => trim((string) ($r['email'] ?? '')) ?: null,
                'country' => trim((string) ($r['country'] ?? '')) ?: null,
                'margin'  => ($r['margin'] === null || $r['margin'] === '') ? null : (int) $r['margin'],
                'is_agent' => (int) ($r['agent'] ?? 0) === 1,
            ];
        }
        return $this->customerRows = $out;
    }

    /** Drop the ledger read for this request (after a mark has changed, or in a test). */
    public function forgetCustomers(): void
    {
        $this->customerRows = null;
    }

    /** How many customers are marked as agents — 0 until the marking has begun. */
    public function agentsMarked(): int
    {
        return count(array_filter($this->customers(), static fn (array $a) => $a['is_agent']));
    }

    /**
     * The mark on one ledger record: true = marked as an agent, false = on the ledger but
     * not marked, null = not on the ledger (or no code to look up).
     */
    public function agentMark(?string $acc): ?bool
    {
        $acc = trim((string) $acc);
        if ($acc === '') {
            return null;
        }
        foreach ($this->customers() as $a) {
            if ($a['acc'] === $acc) {
                return $a['is_agent'];
            }
        }
        return null;
    }

    /**
     * Take an agent from the ledger onto this inquiry: the code, the name, the email and
     * the country in one step, so nobody retypes what the company already holds.
     * Each value goes through saveField, so all of it is marked as the person's choice.
     */
    public function applyAgent(int $id, string $acc): array
    {
        $this->assertOpen($id);
        $acc = trim($acc);
        $row = null;
        // the whole ledger, not only the marked agents: a customer not marked yet can be
        // chosen on purpose, and the inquiry then says so in its gaps
        foreach ($this->customers() as $a) {
            if ($a['acc'] === $acc) {
                $row = $a;
                break;
            }
        }
        if ($row === null) {
            throw new \InvalidArgumentException('That agent is not in the customer ledger.');
        }
        $this->saveField($id, 'agent_name', $row['long'] ?: $row['name'], 'Chosen from the agent list (' . $row['acc'] . ').' . ($row['is_agent'] ? '' : ' Not marked as an agent in the customer master.'));
        if ($row['email'] !== null) {
            $this->saveField($id, 'agent_email', $row['email'], 'From the agent record ' . $row['acc'] . '.');
        }
        if ($row['country'] !== null) {
            $this->saveField($id, 'agent_country', $row['country'], 'From the agent record ' . $row['acc'] . '.');
        }
        // the ledger code is the booking code; it is the master's, not a typed value
        $this->db->query('UPDATE tf_intake SET agent_acc = ? WHERE id = ?', [$row['acc'], $id]);
        $this->resolveAgent($id);
        // resolveAgent re-derives the account from name/email; the explicit choice wins
        $this->db->query('UPDATE tf_intake SET agent_acc = ? WHERE id = ?', [$row['acc'], $id]);
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * Areas that actually hold hotels, so the list a person picks from is never full of
     * places with nothing in them.
     *
     * @return array<int, array{id:int,name:string,region:?string,hotels:int}>
     */
    public function areas(): array
    {
        try {
            $rows = $this->db->query(
                'SELECT l.id, l.name, l.region, COUNT(h.id) AS hotels
                 FROM hr2_location l JOIN hr2_hotel h ON h.location_id = l.id AND h.status = "active"
                 GROUP BY l.id, l.name, l.region HAVING hotels > 0 ORDER BY l.name'
            )->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        return array_map(static fn ($r) => [
            'id' => (int) $r['id'], 'name' => (string) $r['name'],
            'region' => trim((string) ($r['region'] ?? '')) ?: null, 'hotels' => (int) $r['hotels'],
        ], $rows);
    }

    /**
     * The hotels in one area. Only active ones, and only what the panel already holds —
     * a hotel is created under Master Files → Supplier, never from an inquiry.
     *
     * @return array<int, array{id:int,name:string,code:?string,star:?string,has_supplier:bool}>
     */
    public function hotelsIn(int $locationId): array
    {
        try {
            // the hotel code (HTL-###, 6 Oct 2026) rides along once the Hotel Master carries it; older installations answer without it
            $this->hotelCodeCol ??= (bool) $this->db->query("SHOW COLUMNS FROM hr2_hotel LIKE 'hotel_code'")->getRowArray();
            // rates_pending: no approved quotation rate plan at all — the hotel may be named (identification, preparation) but
            // it prices nothing until its rates are entered and approved (Shantha, 6 Oct 2026)
            $rows = $this->db->query(
                'SELECT h.id, h.name, h.star_category, h.primary_sup_acc_no, ' . ($this->hotelCodeCol ? 'h.hotel_code' : 'NULL AS hotel_code') . ',
                        (SELECT COUNT(*) FROM hr2_contract c WHERE c.hotel_id = h.id AND c.status = "approved" AND c.applies_to <> "payment") AS quote_plans
                 FROM hr2_hotel h
                 WHERE h.location_id = ? AND h.status = "active" ORDER BY h.name',
                [$locationId]
            )->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        return array_map(static fn ($r) => [
            'id' => (int) $r['id'], 'name' => (string) $r['name'],
            'code' => trim((string) ($r['hotel_code'] ?? '')) ?: null,
            'star' => trim((string) ($r['star_category'] ?? '')) ?: null,
            'has_supplier' => trim((string) ($r['primary_sup_acc_no'] ?? '')) !== '',
            'rates_pending' => ((int) ($r['quote_plans'] ?? 0)) === 0,
        ], $rows);
    }

    // =====================================================================
    //  What may be quoted
    // =====================================================================

    /**
     * May a plan of this kind appear in a customer quotation?
     *
     * The rule is the company's: published contract rates only. A tactical or special-offer
     * rate is genuine and stays on file for paying the hotel after confirmation, but it is
     * never what an agent is quoted — contract 200 with a tactical 130 for the same night
     * is quoted at 200.
     *
     * hr2_contract.applies_to was meant to carry this, but every contract on this install
     * sits at 'both', so it separates nothing. plan_type is what actually distinguishes
     * them, and the list of quotable kinds is a setting rather than a decision buried here.
     */
    public function quotable(string $planType): bool
    {
        $t = strtolower(trim($planType));
        return $t !== '' && in_array($t, $this->quotablePlanTypes(), true);
    }

    /** The published kinds, from the one setting the pricing engine reads too (tf_meta.quote_plan_types). */
    public function quotablePlanTypes(): array
    {
        return $this->quotePlans ??= \App\Libraries\HotelRates\RateLookup::quotationPlanTypes($this->db);
    }

    // =====================================================================
    //  Nights
    // =====================================================================

    /**
     * Nights are the gap between the two dates. Nothing else.
     *
     * Not what an agent wrote, not what a colleague typed: 16 September to 28 September
     * is twelve nights however many the email claims, and a quotation built on the wrong
     * number is wrong in every line. The claim is kept and shown, but it is never the value.
     */
    public static function nightsBetween(?string $arrival, ?string $departure): ?int
    {
        $a = self::dateOrNull($arrival);
        $d = self::dateOrNull($departure);
        if ($a === null || $d === null) {
            return null;
        }
        $n = (int) round((strtotime($d) - strtotime($a)) / 86400);
        return $n >= 0 ? $n : null;
    }

    /**
     * Recompute the night count from the dates and write it everywhere it is held.
     * Called after anything that can change a date, a read included.
     */
    public function syncNights(int $id): void
    {
        $r = $this->db->query('SELECT arrival_date, departure_date, nights, fields_json FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$r) {
            return;
        }
        $fields  = json_decode((string) $r['fields_json'], true) ?: [];
        $claimed = $fields['nights']['value'] ?? null;
        $claimed = ($claimed === null || $claimed === '') ? null : (int) $claimed;
        // whatever was last claimed by an email or a person is remembered, so the panel can
        // show the disagreement rather than silently discarding what the agent wrote
        $claimed = $fields['nights']['claimed'] ?? $claimed;
        $n       = self::nightsBetween($r['arrival_date'], $r['departure_date']);
        // schema 32: the nights the dates last gave, kept while a date is part-way through a change (the
        // arrival moved past the old departure, say), so the departure day's visits can follow it after
        $last    = $n ?? (isset($fields['nights']['last']) && $fields['nights']['last'] !== null ? (int) $fields['nights']['last'] : null);

        $fields['nights'] = $n === null
            ? [
                'value' => null, 'state' => 'missing', 'quote' => null,
                'basis' => 'Both the arrival and the departure date are needed before the nights can be counted.',
                'origin' => 'derived', 'claimed' => $claimed, 'last' => $last,
            ]
            : [
                'value' => $n, 'state' => 'derived', 'quote' => null,
                'basis' => 'Counted from ' . date('j M Y', strtotime((string) $r['arrival_date'])) . ' to ' . date('j M Y', strtotime((string) $r['departure_date'])) . '.',
                'origin' => 'derived', 'claimed' => $claimed, 'last' => $last,
            ];

        $this->db->query('UPDATE tf_intake SET nights = ?, fields_json = ? WHERE id = ?', [$n, json_encode($fields, JSON_UNESCAPED_UNICODE), $id]);
    }

    public function resolveAgent(int $id): void
    {
        $it = $this->db->query('SELECT id, agent_name, agent_email, fields_json FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            return;
        }
        $fields  = json_decode((string) $it['fields_json'], true) ?: [];
        $country = (string) ($fields['agent_country']['value'] ?? '');
        $acc     = null;
        $iso     = null;
        $market  = null;

        // Matching an agent is a convenience, not a requirement. If the customer
        // ledger cannot be read for any reason the inquiry is still perfectly usable
        // with the agent named but unmatched, so a failure here must never lose the
        // reading of an email.
        try {
            $email = trim((string) $it['agent_email']);
            if ($email !== '') {
                $domain = substr(strrchr($email, '@') ?: '', 1);
                if ($domain !== '') {
                    // a marked agent first: an agent company and a ticketing customer can share a domain
                    try {
                        $r = $this->db->query('SELECT Sup_Acc_No AS acc FROM customers WHERE email LIKE ? ORDER BY agent DESC, ID LIMIT 1', ['%@' . $domain])->getRowArray();
                    } catch (\Throwable $e) {
                        // a ledger without the agent column: the first match, as before
                        $r = $this->db->query('SELECT Sup_Acc_No AS acc FROM customers WHERE email LIKE ? LIMIT 1', ['%@' . $domain])->getRowArray();
                    }
                    $acc = $r['acc'] ?? null;
                }
            }
            if ($acc === null && trim((string) $it['agent_name']) !== '') {
                $key = HotelRateService::nameKey((string) $it['agent_name']);
                if ($key !== '') {
                    foreach ($this->db->query('SELECT Sup_Acc_No AS acc, Name FROM customers WHERE Name IS NOT NULL AND Name <> ""')->getResultArray() as $c) {
                        if (HotelRateService::nameKey((string) $c['Name']) === $key) {
                            $acc = $c['acc'];
                            break;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $acc = null;
        }

        try {
            if ($country !== '') {
                $r = $this->db->query('SELECT country_iso FROM hr2_market_country WHERE LOWER(country_name) = LOWER(?) LIMIT 1', [$country])->getRowArray();
                $iso = $r['country_iso'] ?? (preg_match('/^[A-Za-z]{2}$/', $country) ? strtoupper($country) : null);
            }
            if ($iso !== null) {
                $r = $this->db->query('SELECT market_code FROM hr2_market_country WHERE country_iso = ?', [$iso])->getRowArray();
                $market = $r['market_code'] ?? null;
            }
        } catch (\Throwable $e) {
            $iso = $market = null;
        }

        $this->db->query('UPDATE tf_intake SET agent_acc = ?, country_iso = ?, market_code = ? WHERE id = ?', [$acc, $iso, $market, $id]);
    }

    // =====================================================================
    //  Hotels — matched against the panel, checked for the nights asked for
    // =====================================================================

    /**
     * For each requested night with a hotel named: find it in the panel and ask
     * whether a published rate covers that night for this market.
     *
     * Five honest answers, no guesses:
     *   rate           an approved plan covers it
     *   no_plan        the hotel is in the panel, nothing covers those nights
     *   no_supplier    a rate sheet is transcribed but no supplier record exists yet
     *   not_in_panel   the name matches no hotel we hold
     *   nothing_named  the agent did not name a hotel for that night
     */
    public function checkHotels(int $id, ?int $onlyDayId = null, array $opts = []): void
    {
        $it = $this->db->query('SELECT id, market_code FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            return;
        }
        $market  = (string) ($it['market_code'] ?? '') ?: null;
        $sql     = 'SELECT * FROM tf_intake_day WHERE intake_id = ?' . ($onlyDayId ? ' AND id = ?' : '');
        $days    = $this->db->query($sql, $onlyDayId ? [$id, $onlyDayId] : [$id])->getResultArray();
        $now     = date('Y-m-d H:i:s');
        $freeze  = $this->hasRateFreeze();
        // a deliberate re-price (the Use the new rate button): the panel's answer replaces the figure kept
        $takeNew = !empty($opts['take_new']);

        foreach ($days as $d) {
            $raw = trim((string) $d['hotel_name_raw']);
            if ($raw === '') {
                $this->db->query('UPDATE tf_intake_day SET hotel_id = NULL, hotel_acc = NULL, match_note = NULL, rate_state = ?, rate_note = NULL, checked_at = ? WHERE id = ?', ['nothing_named', $now, $d['id']]);
                continue;
            }
            $hotel = $this->matchHotel($raw);
            if ($hotel === null) {
                $sheet = $this->sheetWaitingFor($raw);
                // a near miss is offered, never taken: "kandalmaa" is probably Kandalama, but the person chooses
                $near = $this->nearHotelNames($raw);
                $hint = $near ? ' Did you mean ' . implode(' or ', array_map(static fn ($n) => '"' . $n . '"', $near)) . '? Press Choose hotel to pick it.' : '';
                // no hotel, so no rate: a figure copied on an earlier check must not stay beside the night
                $this->db->query(
                    'UPDATE tf_intake_day SET hotel_id = NULL, hotel_acc = NULL, match_note = ?, rate_state = ?, rate_note = ?, checked_at = ?,
                        rate_plan_id = NULL, rate_plan_name = NULL, rate_sgl = NULL, rate_dbl = NULL, rate_tpl = NULL, rate_unit = NULL, unit_pax_max = NULL, rate_currency = NULL, rate_supp_json = NULL, room_types_json = NULL'
                    . ($freeze ? ', rate_frozen_at = NULL, rate_key = NULL, rate_drift_json = NULL' : '') . '
                     WHERE id = ?',
                    [
                        $sheet ? 'rate sheet on file' : 'no hotel of this name',
                        $sheet ? 'no_supplier' : 'not_in_panel',
                        self::cut($sheet ? 'Rates are transcribed but no supplier record exists yet — already open in Hotel Incomplete Work.' . $hint : 'No hotel of this name in the panel.' . ($hint ?: ' Create it under Master Files → Supplier, then it can be priced.'), 255),
                        $now, $d['id'],
                    ]
                );
                continue;
            }

            $state = 'no_plan';
            $note  = null;
            $date  = $d['day_date'];
            if ($date === null) {
                $note  = 'No date for this night yet, so the rate cannot be checked.';
                $state = 'not_checked';
            } else {
                try {
                    // A quotation may only ever be built on a published contract rate.
                    // Tactical and special-offer plans are real and stay on file for the
                    // payment side after confirmation, but they are invisible here: if the
                    // contract says 200 and a tactical says 130, the agent is quoted 200.
                    // The engine is asked for the published kinds only, so an offer that
                    // would otherwise win the night is walked past and the contract under it
                    // prices the night — rather than the night being reported as unpriced.
                    $opts2 = ($market ? ['market' => $market] : []) + ['plan_types' => $this->quotablePlanTypes()];
                    $on    = $this->rates()->ratesOn((int) $hotel['id'], $date, $opts2);

                    $all      = (array) ($on['plans'] ?? []);
                    $eligible = array_values(array_filter($all, fn ($p) => $this->quotable((string) ($p['plan_type'] ?? ''))));

                    if (!empty($on['available']) && $eligible) {
                        $state = 'rate';
                        $plan  = $eligible[0];
                        $note  = trim(($plan['name'] ?? 'Contract rate') . (!empty($plan['season']) ? ' · ' . $plan['season'] : ''));
                        if ($market) {
                            $note .= ' · market ' . $market;
                        }
                        $snap = $this->roomRateSnapshot($on, $eligible, $d);
                    } else {
                        $note = self::whyNoRate($on, $date, $market);
                    }
                } catch (\Throwable $e) {
                    $state = 'not_checked';
                    $note  = 'Could not check the rate: ' . $e->getMessage();
                }
            }

            $snap = $snap ?? ['room_type_id' => $d['room_type_id'] ?? null, 'room_type_name' => null, 'plan_id' => null, 'plan_name' => null, 'sgl' => null, 'dbl' => null, 'tpl' => null, 'unit' => null, 'unit_pax_max' => null, 'currency' => null, 'supp' => null, 'room_types' => null, 'room_type_auto' => (int) ($d['room_type_auto'] ?? 0)];

            // ---- the figure kept on the night, against the panel's answer now ----------
            // A figure copied onto a night is what the agent is quoted on. From then on the
            // panel is only compared with it: a plan corrected or replaced in Hotel Rates
            // shows up beside the night as a change to consider, and moves the figure only
            // when somebody presses Use the new rate. The figure is read afresh, without
            // asking, only when the night itself changed — another hotel, date, room type or
            // meal plan is another price, not a changed one — or when it had no figure yet.
            $key  = $freeze && $date !== null ? self::rateKey((int) $hotel['id'], (string) $date, self::intOrNull($snap['room_type_id'] ?? null), $d['meal_plan'] ?? null, $market) : null;
            $kept = $freeze && self::hasFigure($d) && (string) ($d['rate_key'] ?? '') !== '';
            if ($kept && (string) $d['rate_key'] === $key && !$takeNew) {
                $drift = self::rateDrift($d, $snap, $state, $note, $now);
                $this->db->query(
                    'UPDATE tf_intake_day SET hotel_id = ?, hotel_acc = ?, match_note = ?, checked_at = ?, room_types_json = ?, rate_drift_json = ? WHERE id = ?',
                    [(int) $hotel['id'], $hotel['primary_sup_acc_no'], 'matched: ' . $hotel['name'], $now, $snap['room_types'], $drift, $d['id']]
                );
                unset($snap);
                continue;
            }

            $this->db->query(
                'UPDATE tf_intake_day SET hotel_id = ?, hotel_acc = ?, match_note = ?, rate_state = ?, rate_note = ?, checked_at = ?,
                    room_type_id = ?, room_type_name = ?, rate_plan_id = ?, rate_plan_name = ?, rate_sgl = ?, rate_dbl = ?, rate_tpl = ?, rate_unit = ?, unit_pax_max = ?, rate_currency = ?, rate_supp_json = ?, room_types_json = ?'
                . ($freeze ? ', rate_frozen_at = ?, rate_key = ?, rate_drift_json = NULL' : '')
                . ($this->hasRoomTypeAuto() ? ', room_type_auto = ?' : '') . '
                 WHERE id = ?',
                array_merge(
                    [(int) $hotel['id'], $hotel['primary_sup_acc_no'], 'matched: ' . $hotel['name'], $state, self::cut($note, 255), $now,
                     $snap['room_type_id'], $snap['room_type_name'], $snap['plan_id'], $snap['plan_name'], $snap['sgl'], $snap['dbl'], $snap['tpl'], $snap['unit'], $snap['unit_pax_max'], $snap['currency'], $snap['supp'], $snap['room_types']],
                    $freeze ? [self::snapHasFigure($snap) ? $now : null, self::snapHasFigure($snap) ? $key : null] : [],
                    $this->hasRoomTypeAuto() ? [(int) ($snap['room_type_auto'] ?? 0)] : [],
                    [$d['id']]
                )
            );
            // a figure that was on the night and is now another: say so in the history
            if ($kept && self::figuresDiffer($d, $snap, $state)) {
                $this->logReprice($id, $d, $snap, $state, $takeNew ? 'Use the new rate' : 'the night changed (hotel, date, room type or meal plan)');
            }
            unset($snap);
        }
    }

    /** Re-price one night on purpose: the panel's current published rate replaces the figure kept. */
    public function repriceDay(int $id, int $dayId): array
    {
        $this->assertOpen($id);
        $row = $this->db->query('SELECT id FROM tf_intake_day WHERE id = ? AND intake_id = ?', [$dayId, $id])->getRowArray();
        if (!$row) {
            throw new \RuntimeException('That night is not part of this inquiry.');
        }
        $this->checkHotels($id, $dayId, ['take_new' => true]);
        $this->touch($id);
        return $this->get($id);
    }

    /** What a night was priced for. Another hotel, date, room type, meal plan or market is another price. */
    public static function rateKey(int $hotelId, string $date, ?int $roomTypeId, ?string $meal, ?string $market): string
    {
        return $hotelId . '|' . $date . '|' . ($roomTypeId ?? '') . '|' . strtoupper((string) $meal) . '|' . strtoupper((string) $market);
    }

    private static function hasFigure(array $d): bool
    {
        foreach (['rate_sgl', 'rate_dbl', 'rate_tpl', 'rate_unit'] as $c) {
            if (isset($d[$c]) && $d[$c] !== null && $d[$c] !== '') {
                return true;
            }
        }
        return false;
    }

    private static function snapHasFigure(array $snap): bool
    {
        foreach (['sgl', 'dbl', 'tpl', 'unit'] as $c) {
            if (isset($snap[$c]) && $snap[$c] !== null) {
                return true;
            }
        }
        return false;
    }

    /** Does the panel's answer now differ in money from the figure kept on the night? */
    private static function figuresDiffer(array $d, array $snap, string $state): bool
    {
        if ($state !== 'rate' || !self::snapHasFigure($snap)) {
            return true;
        }
        foreach (['sgl' => 'rate_sgl', 'dbl' => 'rate_dbl', 'tpl' => 'rate_tpl', 'unit' => 'rate_unit'] as $k => $c) {
            $a = isset($d[$c]) && $d[$c] !== null && $d[$c] !== '' ? round((float) $d[$c], 2) : null;
            $b = isset($snap[$k]) && $snap[$k] !== null ? round((float) $snap[$k], 2) : null;
            if ($a !== $b) {
                return true;
            }
        }
        $suppA = json_decode((string) ($d['rate_supp_json'] ?? ''), true) ?: [];
        $suppB = json_decode((string) ($snap['supp'] ?? ''), true) ?: [];
        return json_encode($suppA) !== json_encode($suppB);
    }

    /**
     * The panel's answer when it differs from the figure kept: stored beside the night for
     * the screen to show, with the reason when the panel has no published rate any more.
     */
    private static function rateDrift(array $d, array $snap, string $state, ?string $note, string $now): ?string
    {
        if (!self::figuresDiffer($d, $snap, $state)) {
            return null;
        }
        $none = $state !== 'rate' || !self::snapHasFigure($snap);
        return json_encode([
            'seen_at'   => $now,
            'none'      => $none,
            'why'       => $none ? self::cut($note, 255) : null,
            'plan_id'   => $none ? null : $snap['plan_id'],
            'plan_name' => $none ? null : $snap['plan_name'],
            'sgl'       => $none ? null : $snap['sgl'],
            'dbl'       => $none ? null : $snap['dbl'],
            'tpl'       => $none ? null : $snap['tpl'],
            'unit'      => $none ? null : $snap['unit'],
            'currency'  => $none ? null : $snap['currency'],
            'supp'      => $none ? [] : (json_decode((string) ($snap['supp'] ?? ''), true) ?: []),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function logReprice(int $id, array $d, array $snap, string $state, string $why): void
    {
        try {
            $fig = static function (?float $sgl, ?float $dbl, ?float $tpl, ?float $unit): string {
                if ($unit !== null) {
                    return number_format($unit, 2) . ' per unit';
                }
                if ($sgl === null && $dbl === null && $tpl === null) {
                    return 'no rate';
                }
                $f = static fn (?float $v) => $v === null ? '—' : number_format($v, 2);
                return $f($sgl) . ' / ' . $f($dbl) . ' / ' . $f($tpl);
            };
            $n = static fn ($v) => $v === null || $v === '' ? null : (float) $v;
            $from = $fig($n($d['rate_sgl'] ?? null), $n($d['rate_dbl'] ?? null), $n($d['rate_tpl'] ?? null), $n($d['rate_unit'] ?? null));
            $to   = $state === 'rate' ? $fig($n($snap['sgl'] ?? null), $n($snap['dbl'] ?? null), $n($snap['tpl'] ?? null), $n($snap['unit'] ?? null)) : 'no rate';
            $this->activity()->record(
                'inquiry', $id, $id, null, 'rate_repriced',
                'Night ' . (int) $d['day_no'] . ' (' . trim((string) $d['hotel_name_raw']) . '): the rate on the night changed from ' . $from . ' to ' . $to . ' — ' . $why . '.',
                [['what' => 'Rate (sgl / dbl / tpl)', 'from' => $from, 'to' => $to], ['what' => 'Plan', 'from' => (string) ($d['rate_plan_name'] ?? ''), 'to' => (string) ($snap['plan_name'] ?? '')]]
            );
        } catch (\Throwable $e) {
            // the history never stops the work
        }
    }

    /** Schema 16 keeps the figure copied onto a night until somebody replaces it; older tables re-read it every time. */
    /** schema 35: the night says whether its room type was the master's choice, and carries this booking's wording for it. */
    private function hasRoomTypeAuto(): bool
    {
        if ($this->hasRoomTypeAuto === null) {
            try {
                $this->hasRoomTypeAuto = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_day` LIKE 'room_type_auto'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasRoomTypeAuto = false;
            }
        }
        return $this->hasRoomTypeAuto;
    }

    private function hasRateFreeze(): bool
    {
        if ($this->hasRateFreeze === null) {
            try {
                $this->hasRateFreeze = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_day` LIKE 'rate_frozen_at'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasRateFreeze = false;
            }
        }
        return $this->hasRateFreeze;
    }

    /**
     * The published rate for one night, copied beside it: the room type chosen for the
     * night (or the only one the hotel has), the meal plan asked for, and the single /
     * double / triple room rates the panel holds for that combination — plus every
     * compulsory supplement (a gala dinner, a peak surcharge) the panel flags for the date.
     * Where the hotel has several room types and none is chosen, the list is stored so the
     * screen can offer it, and no rate is copied: nothing is picked on somebody's behalf.
     *
     * @return array<string, mixed>
     */
    private function roomRateSnapshot(array $on, array $eligible, array $d): array
    {
        $out = ['room_type_id' => null, 'room_type_name' => null, 'plan_id' => null, 'plan_name' => null, 'sgl' => null, 'dbl' => null, 'tpl' => null, 'unit' => null, 'unit_pax_max' => null, 'currency' => $on['currency'] ?? null, 'supp' => null, 'room_types' => null, 'room_type_auto' => 0];
        $okPlans = [];
        foreach ($eligible as $p) {
            $okPlans[(int) $p['id']] = $p['name'];
        }
        $grid = (array) ($on['grid'] ?? []);
        $types = [];
        foreach ($grid as $row) {
            $priced = false;
            // the published figure per meal plan, so a night whose meal plan is still to be
            // chosen can show what each choice costs instead of a blank
            $meals = [];
            foreach ((array) ($row['rates'] ?? []) as $meal => $c) {
                if ($c && isset($okPlans[(int) $c['plan_id']])) {
                    $priced = true;
                    $meals[strtoupper((string) $meal)] = [
                        'sgl'  => isset($c['sgl']) && $c['sgl'] !== null ? round((float) $c['sgl'], 2) : null,
                        'dbl'  => isset($c['dbl']) && $c['dbl'] !== null ? round((float) $c['dbl'], 2) : null,
                        'tpl'  => isset($c['tpl']) && $c['tpl'] !== null ? round((float) $c['tpl'], 2) : null,
                        'unit' => isset($c['unit']) && $c['unit'] !== null ? round((float) $c['unit'], 2) : null,
                    ];
                }
            }
            $types[] = ['id' => (int) $row['room_type_id'], 'name' => (string) $row['name'], 'priced' => $priced,
                'per_unit' => ($row['priced_as'] ?? '') === 'per_unit' || (int) ($row['unit_pax_max'] ?? 0) > 0, 'unit_pax_max' => self::intOrNull($row['unit_pax_max'] ?? null),
                'meals' => $meals];
        }
        $out['room_types'] = $types ? json_encode($types) : null;
        $pricedTypes = array_values(array_filter($types, static fn ($t) => $t['priced']));
        $chosen = self::intOrNull($d['room_type_id'] ?? null);
        $out['room_type_auto'] = $chosen !== null ? (int) ($d['room_type_auto'] ?? 0) : 0;
        if ($chosen === null && count($pricedTypes) >= 1) {
            // tf-walkfix-r1: the hotel's normal room type — the first with a published rate in the panel's own
            // order (its only one, where it has one) — fills in by itself, marked as the master's choice; the
            // night says so and it can be changed for this booking on the Night dialog without touching the master
            $chosen = $pricedTypes[0]['id'];
            $out['room_type_auto'] = 1;
        }
        if ($chosen === null) {
            return $out;
        }
        $meal = strtoupper(trim((string) ($d['meal_plan'] ?? '')));
        foreach ($grid as $row) {
            if ((int) $row['room_type_id'] !== $chosen) {
                continue;
            }
            $out['room_type_id']   = $chosen;
            $out['room_type_name'] = (string) $row['name'];
            $c = $meal !== '' ? ($row['rates'][$meal] ?? null) : null;
            if ($c && isset($okPlans[(int) $c['plan_id']])) {
                $out['plan_id']   = (int) $c['plan_id'];
                $out['plan_name'] = $okPlans[(int) $c['plan_id']];
                $out['sgl'] = $c['sgl'] !== null ? round((float) $c['sgl'], 2) : null;
                $out['dbl'] = $c['dbl'] !== null ? round((float) $c['dbl'], 2) : null;
                $out['tpl'] = $c['tpl'] !== null ? round((float) $c['tpl'], 2) : null;
                // a tent or a villa priced as one unit whatever the occupancy: the unit rate, and how many it takes
                $out['unit'] = isset($c['unit']) && $c['unit'] !== null ? round((float) $c['unit'], 2) : null;
                $out['unit_pax_max'] = self::intOrNull($row['unit_pax_max'] ?? null);
            }
        }
        $supp = [];
        foreach ((array) ($on['flags'] ?? []) as $f) {
            if (!empty($f['compulsory']) && isset($f['amount']) && $f['amount'] !== null) {
                $supp[] = ['name' => (string) ($f['name'] ?? 'Supplement'), 'amount' => round((float) $f['amount'], 2), 'per' => (string) ($f['per'] ?? 'person'), 'kind' => (string) ($f['kind'] ?? 'supplement')];
            }
        }
        $out['supp'] = $supp ? json_encode($supp) : null;
        return $out;
    }

    /** Match a name the agent typed against the panel, using the panel's own normalisation. */
    /**
     * Price every service on an inquiry against the company's own list.
     *
     * Runs after a read and whenever a person changes a line. A service that is not in
     * the list keeps the agent's words and is marked so — it is never dropped, and never
     * quietly assumed to be some other entry that happens to look similar.
     */
    public function checkServices(int $id, ?int $onlyServiceId = null): void
    {
        $it = $this->db->query('SELECT pax_total, pax_adults, pax_children FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        $pax = self::intOrNull($it['pax_total'] ?? null);
        $party = ['adults' => self::intOrNull($it['pax_adults'] ?? null), 'children' => self::intOrNull($it['pax_children'] ?? null)];

        $sql  = 'SELECT s.*, d.day_date FROM tf_intake_service s
                 LEFT JOIN tf_intake_day d ON d.intake_id = s.intake_id AND d.day_no = s.day_no
                 WHERE s.intake_id = ?' . ($onlyServiceId ? ' AND s.id = ?' : '');
        $bind = $onlyServiceId ? [$id, $onlyServiceId] : [$id];
        $arrival = $this->db->query('SELECT arrival_date FROM tf_intake WHERE id = ?', [$id])->getRowArray()['arrival_date'] ?? null;

        foreach ($this->db->query($sql, $bind)->getResultArray() as $row) {
            if (($row['day_date'] ?? null) === null && (int) $row['day_no'] > 0) {
                $row['day_date'] = TourDates::dateOf($arrival, (int) $row['day_no']);   // the departure day: by arithmetic
            }
            $out = $this->applyLine($row, $pax, $party);
            if ($out !== null) {
                $this->writeLine($row, $out);
            }
        }
    }

    /** One line priced against the list as it is now; null when the list will not read (that must not cost us the line). */
    private function applyLine(array $row, ?int $pax, ?array $party = null): ?array
    {
        try {
            return $this->services()->apply($row, $pax, $row['day_date'] ?? null, $party);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private ?bool $hasMasterCols = null;

    /** Whether a service line carries the Place to Visit / Entrance Fee Master's place and rates beside it (schema 39). */
    private function hasMasterCols(): bool
    {
        if ($this->hasMasterCols === null) {
            try {
                $this->hasMasterCols = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_service` LIKE 'jeep_usd'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasMasterCols = false;
            }
        }
        return $this->hasMasterCols;
    }

    private ?bool $hasMealCols = null;

    /** Whether a service line can be linked to the Meal Master (schema 40) — the master's tables and the line's columns are there. */
    private function hasMealCols(): bool
    {
        return $this->hasMealCols ??= MealMaster::installed($this->db);
    }

    private ?MealMaster $meals = null;

    /** The Meal Master (schema 40): lunches and dinners outside the hotel for passengers on Half Board. */
    public function meals(): MealMaster
    {
        return $this->meals ??= new MealMaster($this->db, $this->user, $this->actorId, $this->actorName());
    }

    /** What the last saveMeal did, in words, for the controller's message. */
    public ?string $mealNote = null;

    /**
     * Put a meal of the Meal Master on a day, change one, or take an old lunch / dinner line onto the master
     * (schema 40). The rules are the master's (MealMaster::assign); the line is then priced like every other
     * line, the day's driving re-planned (a lunch out makes a quiet day a day out, as before) and the inquiry touched.
     */
    public function saveMeal(int $id, ?int $serviceId, array $b): array
    {
        $this->assertOpen($id);
        $this->mealNote = null;
        if (!$this->hasMealCols()) {
            throw new \RuntimeException('The Meal Master is not installed on this site yet. Open Tour Inquiry as the administrator and press Install now.');
        }
        $it = $this->get($id);
        if (!$it) {
            throw new \InvalidArgumentException('Inquiry not found.');
        }
        $r = $this->meals()->assign($it, $serviceId, $b);
        $this->checkServices($id, $r['service_id']);
        $this->planSectors($id);
        $this->touch($id);
        $line = $this->db->query('SELECT match_state, match_note, meal_id, meal_email, meal_contact, meal_phone FROM tf_intake_service WHERE id = ?', [$r['service_id']])->getRowArray() ?: [];
        $this->mealNote = $r['note'] . (($line['match_state'] ?? '') === 'rate_missing' ? ' Held: ' . ($line['match_note'] ?? '') : '');
        // the booking email and contact typed here go on the Meal Master too — only when a Meal Master holder asked (the controller checks)
        if (!empty($b['save_master']) && !empty($line['meal_id'])) {
            $said = $this->meals()->saveContact((int) $line['meal_id'], ['email' => $line['meal_email'], 'contact' => $line['meal_contact'], 'phone' => $line['meal_phone']]);
            if ($said !== null) {
                $this->mealNote .= ' ' . $said;
            }
        }
        return $this->get($id);
    }

    private bool $autoLunching = false;

    /** The last automatic lunch place that could not be worked out, and why (null: none). */
    public ?string $lunchError = null;

    /**
     * The lunch place of every day, picked by the system once the route is set (schema 40, MealMaster::autoLunch: the
     * route first, then the cheapest; a person's pick never replaced). Runs after every re-plan of the route, on an open
     * inquiry only, and only when the Meal Master has a lunch in use or the system picked one here before. The lines it
     * writes are priced like any other; it never touches the kilometres (see laySectors).
     */
    private function autoLunches(int $id, bool $always = false): array
    {
        $none = ['changed' => [], 'said' => []];
        if ($this->autoLunching || !$this->hasMealCols()) {
            return $none;
        }
        try {
            $this->meals()->refresh();   // the setting as it is now
            if (!$always && !$this->meals()->autoOn()) {
                return $none;
            }
            $head = $this->db->query('SELECT status, archived_at FROM tf_intake WHERE id = ?', [$id])->getRowArray();
            if (!$head || self::isClosed($head)) {
                return $none;
            }
            $any = $this->db->query("SELECT id FROM tf_meal WHERE meal_type = 'lunch' AND active = 1 LIMIT 1")->getRowArray()
                || $this->db->query('SELECT id FROM tf_intake_service WHERE intake_id = ? AND meal_auto = 1 LIMIT 1', [$id])->getRowArray();
            if (!$any) {
                return $none;
            }
            $this->autoLunching = true;
            $it = $this->get($id);
            if (!$it || !empty($it['locked'])) {
                return $none;
            }
            // a lunch the system picked on a day that is not a day of the tour any more goes with the day
            $days = array_map(static fn ($r) => (int) $r['day_no'], $it['meals']['days'] ?? []);
            $stray = [];
            foreach ($it['services'] as $sv) {
                if (!empty($sv['meal_auto']) && !in_array((int) $sv['day_no'], $days, true)) {
                    $stray[] = (int) $sv['id'];
                }
            }
            $r = $this->meals()->autoLunch($it);
            foreach ($stray as $sid) {
                $this->db->query('DELETE FROM tf_intake_service WHERE id = ? AND meal_auto = 1', [$sid]);
                $r['said'][] = 'An automatic lunch place on a day that is no longer on the tour was taken off.';
            }
            foreach (array_unique($r['changed']) as $sid) {
                if ($this->db->query('SELECT id FROM tf_intake_service WHERE id = ?', [$sid])->getRowArray()) {
                    $this->checkServices($id, $sid);
                }
            }
            if ($r['said']) {
                try {
                    $this->activity()->record('intake', $id, $id, null, 'meals', 'Lunch places picked from the route', array_map(static fn ($t) => ['what' => 'Lunch place', 'from' => null, 'to' => $t], $r['said']));
                } catch (\Throwable $e) {
                    // the line is a record, never a gate
                }
            }
            return $r;
        } catch (\Throwable $e) {
            // never in the way of the change that set the route: kept, and written to the log
            $this->lunchError = $e->getMessage();
            if (function_exists('log_message')) {
                log_message('error', 'TourFlow automatic lunch place, inquiry ' . $id . ': ' . $e->getMessage());
            }
            return $none;
        } finally {
            $this->autoLunching = false;
        }
    }

    /** What the last fillLunches did, in words. */
    public array $lunchNotes = [];

    /** "Pick lunch places" on the Meals card: the automatic lunch place run now, even with the setting off (a person's picks and removals stand). */
    public function fillLunches(int $id): array
    {
        $this->assertOpen($id);
        if (!$this->hasMealCols()) {
            throw new \RuntimeException('The Meal Master is not installed on this site yet. Open Tour Inquiry as the administrator and press Install now.');
        }
        $r = $this->autoLunches($id, true);
        $this->lunchNotes = $r['said'];
        if ($r['changed']) {
            $this->touch($id);
        }
        return $this->get($id);
    }

    /**
     * "+ Add a new restaurant" at a day's lunch place: the restaurant goes into the Meal Master (its rules all apply, its
     * code given when blank) and onto the day for the party — in place of the line given, if one is. For a holder of the
     * Meal Master only (the controller checks).
     */
    public function quickMeal(int $id, array $b): array
    {
        $this->assertOpen($id);
        if (!$this->hasMealCols()) {
            throw new \RuntimeException('The Meal Master is not installed on this site yet. Open Tour Inquiry as the administrator and press Install now.');
        }
        $it = $this->get($id);
        if (!$it) {
            throw new \InvalidArgumentException('Inquiry not found.');
        }
        $dayNo = (int) ($b['day_no'] ?? 0);
        $row = null;
        foreach ($it['meals']['days'] ?? [] as $r) {
            if ($r['day_no'] === $dayNo) {
                $row = $r;
                break;
            }
        }
        if ($row === null) {
            throw new \InvalidArgumentException('Day ' . $dayNo . ' is not a night of this tour or its departure day.');
        }
        $split = MealMaster::partySplit($it, $row['party']);
        $sid = !empty($b['service_id']) ? (int) $b['service_id'] : null;
        // the restaurant and the day's line together, or neither: a meal the day cannot take (the hotel gives it, more
        // people than the party…) leaves nothing behind in the master, so a second try does not add it twice
        $this->db->transBegin();
        try {
            $m = $this->meals()->quickAdd($b, $row);
            $line = [
                'day_no' => $dayNo, 'meal_type' => $m['meal_type'], 'meal_id' => $m['id'],
                'qty_adult' => $b['qty_adult'] ?? $split['adults'], 'qty_child' => $b['qty_child'] ?? $split['children'],
                'meal_time' => $b['meal_time'] ?? null, 'special_note' => $b['special_note'] ?? null,
                'confirm_outside' => $b['confirm_outside'] ?? null, 'outside_reason' => $b['outside_reason'] ?? null,
            ];
            $out = $this->saveMeal($id, $sid, $line);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->meals()->refresh();
            throw $e;
        }
        $this->db->transCommit();
        $this->mealNote = 'Added to the Meal Master as ' . $m['code'] . ($m['needs_supplier'] ? ' (not linked to a supplier account yet — it is paid once it is)' : '') . '. ' . (string) $this->mealNote;
        return $out;
    }

    /** On a Half Board night, which meal the hotel gives besides breakfast (lunch / dinner, or null for the setting's default). */
    public function setHbMeal(int $id, int $dayId, ?string $meal): array
    {
        $this->assertOpen($id);
        if (!$this->hasMealCols()) {
            throw new \RuntimeException('The Meal Master is not installed on this site yet. Open Tour Inquiry as the administrator and press Install now.');
        }
        $r = $this->meals()->setHbMeal($id, $dayId, $meal);
        $this->autoLunches($id);   // an HB night giving lunch: the system's own lunch place that day goes (and comes back with dinner)
        try {
            $this->activity()->record('intake', $id, $id, null, 'meals', 'Night ' . $r['day_no'] . ($r['hotel'] ? ' (' . $r['hotel'] . ')' : '') . ': the HB meal the hotel gives is now ' . $r['to'],
                [['what' => 'Night ' . $r['day_no'] . ' — HB meal', 'from' => $r['from'], 'to' => $r['to']]]);
        } catch (\Throwable $e) {
            // the line is a record, never a gate
        }
        $this->touch($id);
        $this->mealNote = 'Night ' . $r['day_no'] . ': the hotel gives breakfast and ' . $r['to'] . '.';
        return $this->get($id);
    }

    /** The columns of a service line the read-time sync compares: the money and the match, and (schema 39) the master's rate. */
    private function serviceFigures(): array
    {
        $f = self::SERVICE_FIGURES;
        if (!$this->hasSuggestCode()) {
            $f = array_values(array_diff($f, ['suggest_code']));
        }
        if ($this->hasMasterCols()) {
            $f = array_merge($f, ['place_id', 'rate_id', 'child_usd', 'rate_currency', 'rate_adult', 'rate_child', 'jeep_rate_id', 'jeep_usd']);
        }
        if ($this->hasMealCols()) {
            // schema 40: a Meal Master line's tax and total in the meal's own currency
            $f = array_merge($f, ['tax_pct', 'tax_cur', 'amount_cur']);
        }
        return $f;
    }

    /** The priced columns of one line written back. */
    private function writeLine(array $row, array $out): void
    {
        $this->db->query(
            'UPDATE tf_intake_service SET kind = ?, fee_id = ?, fee_code = ?, match_state = ?, match_note = ?,
                unit_usd = ?, amount_usd = ?, usd_rate = ?, rate_date = ?, amount_lkr = ?, pay_mode = ?, checked_at = ?' . ($this->hasSuggestCode() ? ', suggest_code = ?' : '') . '
             WHERE id = ?',
            array_merge([
                $out['kind'], $out['fee_id'], self::cut($out['fee_code'] ?? null, 100), $out['match_state'],
                self::cut($out['match_note'] ?? null, 255), $out['unit_usd'], $out['amount_usd'],
                $out['usd_rate'], $out['rate_date'], $out['amount_lkr'], $out['pay_mode'],
                $out['checked_at'],
            ], $this->hasSuggestCode() ? [self::cut($out['suggest_code'] ?? null, 100)] : [], [(int) $row['id']])
        );
        if ($this->hasMasterCols()) {
            // schema 39: the place of the master the line is, and the rates it was priced from (the entrance; at a safari park the jeep)
            $this->db->query('UPDATE tf_intake_service SET place_id = ?, rate_id = ?, child_usd = ?, rate_currency = ?, rate_adult = ?, rate_child = ?, jeep_rate_id = ?, jeep_usd = ? WHERE id = ?', [
                !empty($out['place_id']) ? (int) $out['place_id'] : null, $out['rate_id'] ?? null, $out['child_usd'] ?? null, self::cut($out['rate_currency'] ?? null, 3),
                $out['rate_adult'] ?? null, $out['rate_child'] ?? null, $out['jeep_rate_id'] ?? null, $out['jeep_usd'] ?? null, (int) $row['id'],
            ]);
            // the place's own words for the client, copied onto the line the first time it is matched (as the old list's were)
            if (!empty($out['place_id']) && trim((string) ($row['description'] ?? '')) === '') {
                $pl = $this->services()->placeEntry((int) $out['place_id']);
                if ($pl && trim((string) ($pl['description'] ?? '')) !== '') {
                    $this->db->query('UPDATE tf_intake_service SET description = ? WHERE id = ?', [trim((string) $pl['description']), (int) $row['id']]);
                    $row['description'] = trim((string) $pl['description']);
                }
            }
        }
        if ($this->hasMealCols()) {
            // schema 40: a Meal Master line's tax and total in the meal's own currency (null on every other line)
            $this->db->query('UPDATE tf_intake_service SET tax_pct = ?, tax_cur = ?, amount_cur = ? WHERE id = ?', [$out['tax_pct'] ?? null, $out['tax_cur'] ?? null, $out['amount_cur'] ?? null, (int) $row['id']]);
        }
        // The visit description: the master's text copied onto this line the first time
        // the visit is matched, and from then on the line's own — a person may rewrite it
        // for this quotation, and nothing written here ever goes back to the master.
        if (!empty($out['fee_id']) && trim((string) ($row['description'] ?? '')) === '') {
            $tpl = $this->db->query('SELECT description FROM entrance_fee WHERE id = ?', [(int) $out['fee_id']])->getRowArray();
            if ($tpl && trim((string) $tpl['description']) !== '') {
                $this->db->query('UPDATE tf_intake_service SET description = ? WHERE id = ?', [trim((string) $tpl['description']), (int) $row['id']]);
            }
        }
    }

    /** The columns of a service line that carry money or the match — what the read-time sync compares. */
    private const SERVICE_FIGURES = ['match_state', 'fee_id', 'fee_code', 'unit_usd', 'amount_usd', 'amount_lkr', 'usd_rate', 'pay_mode', 'suggest_code'];

    /**
     * Entrance & Activities follows what feeds it, on its own. Every read prices each line
     * again from the priced list, the party and the settings as they are now, and writes
     * a line back only where a figure moved — a rate changed in the master, an entry added
     * or taken off, a word added to the no-charge list, a rate for the day. So the section's
     * total is always current, and the quotation, which takes that total as it is, follows
     * without anyone pressing anything. What moved is said on the line, returned for the
     * screen, and written to the log. A person's own decisions (the pay mode they set, the
     * entry they picked, the quantity, the words) are inputs here, never overwritten.
     *
     * Not on a confirmed, cancelled or archived inquiry: that is the record of what was sold.
     *
     * @return array<int, string> what changed, in words, one per line that moved
     */
    private function syncServices(int $id, array &$services, bool $locked, ?int $pax, array $days, ?string $arrival = null, ?array $party = null): array
    {
        if ($locked || $services === [] || !$this->services()->available()) {
            return [];
        }
        $this->services()->refresh();   // the master and the settings as they are now, not as first read
        $dateOf = [];
        foreach ($days as $d) {
            $dateOf[(int) $d['day_no']] = $d['day_date'] ?? null;
        }
        // a day with no night line of its own (the departure day) is dated from the arrival, by arithmetic
        foreach ($services as $row) {
            $n = (int) $row['day_no'];
            if ($n > 0 && ($dateOf[$n] ?? null) === null) {
                $dateOf[$n] = TourDates::dateOf($arrival, $n);
            }
        }
        $said = [];
        $log  = [];
        foreach ($services as $i => $row) {
            $row['day_date'] = $dateOf[(int) $row['day_no']] ?? null;
            $out = $this->applyLine($row, $pax, $party);
            if ($out === null) {
                continue;
            }
            $moved = [];
            foreach ($this->serviceFigures() as $k) {
                if (self::figure($row[$k] ?? null) !== self::figure($out[$k] ?? null)) {
                    $moved[$k] = [$row[$k] ?? null, $out[$k] ?? null];
                }
            }
            // schema 40: a Meal Master line held for another reason (switched off → its supplier unavailable) says the reason it has now
            if (!empty($row['meal_id']) && (string) ($row['match_note'] ?? '') !== (string) self::cut($out['match_note'] ?? null, 255)) {
                $moved['match_note'] = [$row['match_note'] ?? null, self::cut($out['match_note'] ?? null, 255)];
            }
            if ($moved === []) {
                continue;
            }
            $this->writeLine($row, $out);
            foreach ($this->serviceFigures() as $k) {
                $services[$i][$k] = $out[$k] ?? null;
            }
            $services[$i]['match_note'] = self::cut($out['match_note'] ?? null, 255);
            $services[$i]['kind']       = $out['kind'];
            $services[$i]['checked_at'] = $out['checked_at'];
            $where = ((int) $row['day_no'] ? 'Day ' . (int) $row['day_no'] : 'Tour') . ' · ' . $row['title'];
            $what  = self::movedText($moved, $out);
            $said[] = $where . ': ' . $what;
            foreach ($moved as $k => [$from, $to]) {
                $log[] = ['what' => $where . ' — ' . $k, 'from' => $from, 'to' => $to];
            }
        }
        if ($said !== []) {
            try {
                $this->activity()->record('intake', $id, $id, null, 'recalculated', 'Entrance & Activities worked out again on its own: ' . implode('; ', $said), $log);
            } catch (\Throwable $e) {
                // the line is a record, never a gate
            }
        }
        return $said;
    }

    /** A money or match figure as a comparable string: 7, 7.0 and "7.00" are the same figure; null and '' are the same absence. */
    private static function figure($v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
        }
        return (string) $v;
    }

    /** What moved on a line, in words: the rate, the amount, the entry, the state. */
    private static function movedText(array $moved, array $out): string
    {
        $money = static fn ($v) => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
        $parts = [];
        if (isset($moved['match_state'])) {
            [$from, $to] = $moved['match_state'];
            $word = ['matched' => 'priced from the list', 'no_charge' => 'no charge', 'not_in_list' => 'not in the priced list', 'nothing_named' => 'nothing named', 'not_checked' => 'not checked',
                     'not_in_master' => 'an entrance or safari not in the Place to Visit / Entrance Fee Master, held', 'rate_missing' => 'in the Place to Visit / Entrance Fee Master with no rate for the day, held'];
            $parts[] = 'now ' . ($word[$to] ?? $to) . ($from ? ' (was ' . ($word[$from] ?? $from) . ')' : '');
        }
        if (isset($moved['fee_code']) && ($moved['fee_code'][1] ?? null) !== null) {
            $parts[] = 'entry ' . $moved['fee_code'][1];
        }
        if (isset($moved['unit_usd'])) {
            $parts[] = 'rate ' . $money($moved['unit_usd'][0]) . ' → ' . $money($moved['unit_usd'][1]);
        }
        if (isset($moved['amount_usd'])) {
            $parts[] = 'amount USD ' . $money($moved['amount_usd'][0]) . ' → ' . $money($moved['amount_usd'][1]);
        } elseif (isset($moved['amount_lkr']) && ($moved['amount_lkr'][1] ?? null) !== null) {
            $parts[] = 'rupees at the rate of the day';
        }
        if (isset($moved['pay_mode'])) {
            $parts[] = 'who pays: ' . str_replace('_', ' ', (string) $moved['pay_mode'][1]);
        }
        if ($parts === [] && isset($moved['suggest_code'])) {
            $parts[] = 'closest entry now ' . ($moved['suggest_code'][1] ?? '—');
        }
        if ($parts === [] && isset($moved['match_note'])) {
            $parts[] = 'now: ' . ($moved['match_note'][1] ?? '—');
        }
        return implode(', ', $parts) ?: 'rechecked';
    }

    /**
     * Entrance & Activities: the one place the entrance and activity cost is worked out.
     * Every line of the inquiry with what it is, what it costs and why, and one total —
     * which the quotation takes as it is. The counts say what stops the quotation. Also
     * the places a day's narrative names that have no line on that day, for a person to
     * look at (the priced list knows the name; nothing is added on its own).
     *
     * @return array<string, mixed>
     */
    public function entrances(array $it): array
    {
        $pax   = self::intOrNull($it['pax_total'] ?? null);
        $look  = $this->services();
        $dates = [];
        $areas = [];
        foreach ($it['days'] ?? [] as $d) {
            $dates[(int) $d['day_no']] = $d['day_date'] ?? null;
            $areas[(int) $d['day_no']] = $d['area'] ?? null;
        }
        $lines = [];
        $total = 0.0;
        $count = ['priced' => 0, 'no_charge' => 0, 'declined' => 0, 'direct' => 0, 'unsure' => 0, 'no_amount' => 0, 'rupees' => 0, 'rate_missing' => 0, 'not_in_master' => 0];
        $free  = [];
        foreach ($it['services'] ?? [] as $s) {
            if (!empty($s['free_visit_id'])) {
                // a free visit from the master (schema 29) is on the programme and the itinerary only:
                // it is not a line of this section, which is the paid side; it is counted beside it
                $free[] = ['id' => (int) $s['id'], 'day_no' => (int) $s['day_no'], 'title' => $s['title']];
                continue;
            }
            $c = ServiceLookup::classify($s);
            $count[$c['state']] = ($count[$c['state']] ?? 0) + 1;
            $qty   = $s['qty'] !== null && $s['qty'] !== '' ? (float) $s['qty'] : null;
            // schema 39: a safari park line — the entrance for the party, the number on the line is the jeeps
            $park  = !empty($s['place_id']) && ($s['jeep_usd'] ?? null) !== null && $s['jeep_usd'] !== '';
            // how many it is for: the quantity typed, else the party (as the pricing counts it)
            $heads = $park ? ($pax !== null && $pax > 0 ? (float) $pax : null) : ($qty ?? ($pax !== null && $pax > 0 ? (float) $pax : null));
            $unit  = $s['unit_usd'] !== null && $s['unit_usd'] !== '' ? (float) $s['unit_usd'] : null;
            $entry = !empty($s['place_id']) ? $look->placeEntry((int) $s['place_id']) : (!empty($s['fee_id']) ? $look->entry((int) $s['fee_id']) : null);
            $amt   = $s['amount_usd'] !== null && $s['amount_usd'] !== '' ? (float) $s['amount_usd'] : null;
            if ($c['costed'] && $amt !== null) {
                $total += $amt;
            }
            // what a line that is not costed would cost if it were taken on (the list's figure × how many)
            $would = !$c['costed'] && $unit !== null && $unit > 0 && $heads !== null ? round($unit * $heads, 2) : null;
            $lines[] = [
                'id' => (int) $s['id'], 'day_no' => (int) $s['day_no'], 'day_date' => $dates[(int) $s['day_no']] ?? null, 'area' => $areas[(int) $s['day_no']] ?? null,
                'title' => $s['title'], 'kind' => $s['kind'], 'origin' => $s['origin'] ?? 'ai', 'pay_mode' => $s['pay_mode'],
                'state' => $c['state'], 'costed' => $c['costed'], 'blocks' => $c['blocks'], 'why' => $c['why'],
                'count' => $heads, 'per_person' => !empty($s['place_id']) || ServiceLookup::perPerson($s['kind'] ?? null),
                'unit_usd' => $unit,
                // schema 39: the place of the master and its rates beside the line — the child figure, its own currency, and at a safari park the jeep
                'place_id' => !empty($s['place_id']) ? (int) $s['place_id'] : null,
                'child_usd' => isset($s['child_usd']) && $s['child_usd'] !== null && $s['child_usd'] !== '' ? (float) $s['child_usd'] : null,
                'rate_currency' => $s['rate_currency'] ?? null, 'rate_adult' => isset($s['rate_adult']) && $s['rate_adult'] !== null ? (float) $s['rate_adult'] : null,
                'rate_child' => isset($s['rate_child']) && $s['rate_child'] !== null ? (float) $s['rate_child'] : null, 'match_state' => $s['match_state'] ?? null,
                'park' => $park || ($entry !== null && !empty($s['place_id']) && (int) ($entry['jeep'] ?? 0) === 1),
                'jeep_usd' => $park ? (float) $s['jeep_usd'] : null, 'jeeps' => $park || (!empty($s['place_id']) && (int) ($entry['jeep'] ?? 0) === 1) ? $qty : null,
                'amount_usd' => $c['costed'] ? $amt : null, 'would_cost_usd' => $would,
                'fee_code' => $s['fee_code'], 'fee_name' => $entry['name'] ?? null, 'suggest_code' => $s['suggest_code'] ?? null,
                'note' => $s['match_note'], 'special_note' => $s['special_note'] ?? null,
                // schema 40: a lunch or dinner from the Meal Master — its adults and children, and its total in the meal's own currency
                'meal_id' => !empty($s['meal_id']) ? (int) $s['meal_id'] : null,
                'qty_adult' => isset($s['qty_adult']) && $s['qty_adult'] !== null ? (int) $s['qty_adult'] : null, 'qty_child' => isset($s['qty_child']) && $s['qty_child'] !== null ? (int) $s['qty_child'] : null,
                'meal_time' => $s['meal_time'] ?? null, 'amount_cur' => isset($s['amount_cur']) && $s['amount_cur'] !== null ? (float) $s['amount_cur'] : null,
            ];
        }
        // the same name twice on one day is one thing costed twice (lines from before the merge rule, or typed twice)
        $seen = [];
        foreach ($lines as $l) {
            $seen[$l['day_no'] . '|' . mb_strtolower(trim((string) $l['title']))][] = $l['id'];
        }
        $duplicates = [];
        foreach ($seen as $key => $ids) {
            if (count($ids) > 1) {
                $first = current(array_filter($lines, static fn ($l) => $l['id'] === $ids[0]));
                $duplicates[] = ['day_no' => $first['day_no'], 'title' => $first['title'], 'ids' => $ids];
            }
        }
        $locked = in_array((string) ($it['status'] ?? ''), ['confirmed', 'cancelled'], true) || !empty($it['archived_at']);
        $stops  = $count['unsure'] + $count['no_amount'] + $count['rupees'] + $count['rate_missing'] + $count['not_in_master'] + count($duplicates);
        return [
            'lines'      => $lines,
            'total_usd'  => round($total, 2),
            'pax'        => $pax,
            'count'      => count($lines),
            'by_state'   => $count,
            'duplicates' => $duplicates,
            'stops'      => $stops,
            'ready'      => $stops === 0,
            // a confirmed tour is the record of what was sold: nothing more is asked of it
            'mentions'   => $locked ? [] : $this->mentions($it),
            'recalc'     => $it['recalc'] ?? [],
            'free_visits' => $free,
            'master'     => $look->master(),
        ];
    }

    /**
     * Places a day's narrative names that the priced list knows (by an entry's own name of
     * two words or more, or an alias from the settings) with no line for them on that day.
     * Flagged for a person, never added: the words may be a drive past, not a visit.
     *
     * @return array<int, array{day_no:int, code:string, name:string, phrase:string}>
     */
    public function mentions(array $it): array
    {
        $look = $this->services();
        if (!$look->available()) {
            return [];
        }
        $known = [];   // phrase (as words) => [code, name]
        foreach ($look->all() as $m) {
            $w = ServiceLookup::words($m['name']);
            if ($w !== '' && str_contains($w, ' ') && trim((string) $m['code']) !== '') {
                $known[$w] = [(string) $m['code'], (string) $m['name']];
            }
        }
        if ($look->master()) {
            // schema 39: the master's other names, each pointing at its item
            foreach ($look->aliasEntries() as $phrase => $row) {
                if (trim((string) ($row['code'] ?? '')) !== '') {
                    $known[$phrase] = [(string) $row['code'], (string) $row['name']];
                }
            }
        } else {
            foreach ($look->aliases() as $phrase => $code) {
                $hit = null;
                foreach ($look->all() as $m) {
                    if (strcasecmp(trim((string) $m['code']), trim($code)) === 0) {
                        $hit = $m;
                        break;
                    }
                }
                if ($hit) {
                    $known[$phrase] = [(string) $hit['code'], (string) $hit['name']];
                }
            }
        }
        if ($known === []) {
            return [];
        }
        $out = [];
        foreach ($it['days'] ?? [] as $d) {
            $text = ' ' . ServiceLookup::words((string) ($d['narrative'] ?? '')) . ' ';
            if (trim($text) === '') {
                continue;
            }
            $dayNo = (int) $d['day_no'];
            $onDay = array_filter($it['services'] ?? [], static fn ($s) => (int) $s['day_no'] === $dayNo);
            $seen  = [];
            foreach ($known as $phrase => [$code, $name]) {
                if (isset($seen[$code]) || !str_contains($text, ' ' . $phrase . ' ')) {
                    continue;
                }
                $lined = false;
                foreach ($onDay as $s) {
                    if (strcasecmp(trim((string) ($s['fee_code'] ?? '')), $code) === 0 || str_contains(' ' . ServiceLookup::words((string) $s['title']) . ' ', ' ' . $phrase . ' ')) {
                        $lined = true;
                        break;
                    }
                }
                if (!$lined) {
                    $seen[$code] = true;
                    $out[] = ['day_no' => $dayNo, 'code' => $code, 'name' => $name, 'phrase' => $phrase];
                }
            }
        }
        return $out;
    }

    /** What the last saveService did beyond saving: a line merged into one already on the day, a quantity capped. Read by the controller for its message. */
    public ?string $serviceNote = null;

    private ?bool $hasSuggestCode = null;

    /** Whether a service line can carry the closest priced-list entry (schema 25). */
    private function hasSuggestCode(): bool
    {
        if ($this->hasSuggestCode === null) {
            try {
                $this->hasSuggestCode = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_service` LIKE 'suggest_code'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasSuggestCode = false;
            }
        }
        return $this->hasSuggestCode;
    }

    /**
     * Add or change one service. Anything touched here becomes the person's, not the email's.
     *
     * Adding is idempotent: the same service (same name, same day) pressed twice, or three
     * times, is one line updated, never a second line — a double click on Save, a slow
     * reply, a re-pick from the list all land on the line that is there. And a per-person
     * service (an entrance, a visit, a train seat, a meal) is never for more people than
     * the inquiry has: the quantity is capped at the party, and the amount follows.
     */
    public function saveService(int $id, ?int $serviceId, array $s): array
    {
        $this->assertOpen($id);
        $this->serviceNote = null;
        $title = self::cut($s['title'] ?? null, 255);
        if ($title === null || trim($title) === '') {
            throw new \InvalidArgumentException('A service needs a name.');
        }
        $kind   = in_array($s['kind'] ?? '', ServiceLookup::KINDS, true) ? $s['kind'] : ServiceLookup::kindFor($title);
        $mode   = in_array($s['pay_mode'] ?? '', ServiceLookup::PAY_MODES, true) ? $s['pay_mode'] : 'unknown';
        $dayNo  = max(0, (int) ($s['day_no'] ?? 0));
        $qty    = self::intOrNull($s['qty'] ?? null);
        $note   = self::cut($s['special_note'] ?? null, 500);
        $notes  = [];

        // the party is the ceiling for anything charged per person
        $pax = self::intOrNull($this->db->query('SELECT pax_total FROM tf_intake WHERE id = ?', [$id])->getRowArray()['pax_total'] ?? null);
        if ($qty !== null && $qty < 0) {
            $qty = null;
        }
        if ($qty !== null && $pax !== null && $pax > 0 && $qty > $pax && ServiceLookup::perPerson($kind)) {
            $notes[] = 'The quantity was capped at the party of ' . $pax . ' — a per-person ' . $kind . ' is never for more people than the inquiry has.';
            $qty = $pax;
        }

        if (!$serviceId) {
            // the same service on the same day is one line, whatever pressed it again
            $dup = $this->db->query('SELECT id FROM tf_intake_service WHERE intake_id = ? AND day_no = ? AND LOWER(TRIM(title)) = LOWER(TRIM(?)) ORDER BY id LIMIT 1', [$id, $dayNo, $title])->getRowArray();
            if ($dup) {
                $serviceId = (int) $dup['id'];
                array_unshift($notes, '"' . trim($title) . '" is already on ' . ($dayNo ? 'night ' . $dayNo : 'the tour') . ' — that line was updated, not added again.');
            }
        }

        // a priced-list entry accepted for this line (the closest one offered, or one picked): the
        // line keeps the agent's words and is priced from that entry; a wrong code is refused
        $feeCode = array_key_exists('fee_code', $s) ? self::cut($s['fee_code'], 100) : null;
        // an entry picked by its id (Paid Activities & Services, schema 29): the one entry meant,
        // even where the list holds two entries under one code or one with no code at all
        $feePick = isset($s['fee_id']) && $s['fee_id'] !== '' && (int) $s['fee_id'] > 0 ? (int) $s['fee_id'] : null;
        // schema 39: a place of the Place to Visit / Entrance Fee Master picked by its id
        $placePick = isset($s['place_id']) && $s['place_id'] !== '' && (int) $s['place_id'] > 0 && $this->services()->master() ? (int) $s['place_id'] : null;
        if ($placePick === null && $feePick !== null && $this->services()->master()) {
            // an old record picked by its id that is now a place: the place is what is picked
            $placePick = $this->services()->placeForFee($feePick);
        }
        if ($placePick !== null) {
            $pl = $this->services()->placeEntry($placePick);
            if (!$pl || (int) $pl['active'] !== 1) {
                throw new \InvalidArgumentException(($pl ? '"' . trim((string) $pl['name']) . '" is' : 'That place is') . ' switched off (not in use) in the Place to Visit / Entrance Fee Master.');
            }
            $placePick = (int) $pl['id'];
            $feePick   = null;
            // the place decides (in use or not, its code) — never the old record it came from
            $feeCode   = self::cut(trim((string) ($pl['code'] ?? '')) !== '' ? trim((string) $pl['code']) : null, 100);
        }
        if ($placePick !== null) {
            // taken above
        } elseif ($feePick !== null) {
            $entry = $this->services()->entry($feePick);
            if (!$entry) {
                throw new \InvalidArgumentException('That entry is not in the priced list.');
            }
            if ((int) ($entry['active'] ?? 1) !== 1) {
                throw new \InvalidArgumentException('"' . trim((string) $entry['name']) . '" is ' . ($this->services()->master() ? 'not in use in the Entrance Fee Master.' : 'switched off under Paid Activities & Services.'));
            }
            $feeCode = self::cut(trim((string) $entry['code']) !== '' ? trim((string) $entry['code']) : null, 100);
        } elseif ($feeCode !== null && $this->services()->match($title, $feeCode)['fee_code'] === null) {
            throw new \InvalidArgumentException('"' . $feeCode . '" is not a code in the priced list.');
        }
        $picked = $feePick !== null || $feeCode !== null || $placePick !== null;

        if ($serviceId) {
            $own = $this->db->query('SELECT * FROM tf_intake_service WHERE id = ? AND intake_id = ?', [$serviceId, $id])->getRowArray();
            if (!$own) {
                throw new \InvalidArgumentException('That service is not on this inquiry.');
            }
            // a line given a new name is matched afresh by that name (a pick from the list is a new
            // name too); an entry accepted by code replaces the one before; otherwise the pick stays
            $renamed = mb_strtolower(trim((string) $own['title'])) !== mb_strtolower(trim($title));
            // schema 40: a meal of the Meal Master is changed on the Meals card (its restaurant, adults, children, time) — here
            // only its note, its words for the client and who pays, so its figures never drift from what it was priced on
            if (!empty($own['meal_id']) && ($renamed || $kind !== (string) $own['kind'] || $dayNo !== (int) $own['day_no'] || $picked
                || ($qty !== null && $qty !== (int) ($own['qty_adult'] ?? 0) + (int) ($own['qty_child'] ?? 0)))) {
                throw new \InvalidArgumentException('"' . trim((string) $own['title']) . '" is a meal from the Meal Master — change its day, restaurant, adults, children or time on the Meals card.');
            }
            $this->db->query(
                "UPDATE tf_intake_service SET day_no = ?, kind = ?, title = ?, qty = ?, pay_mode = ?, special_note = ?, origin = 'human'" . ($picked ? ', fee_code = ?, fee_id = ?' : ($renamed ? ', fee_code = NULL, fee_id = NULL' : '')) . " WHERE id = ?",
                array_merge([$dayNo, $kind, $title, $qty, $mode, $note], $picked ? [$feeCode, $feePick] : [], [$serviceId])
            );
            if ($this->hasMasterCols() && ($picked || $renamed)) {
                $this->db->query('UPDATE tf_intake_service SET place_id = ? WHERE id = ?', [$placePick, $serviceId]);
            }
            // a free visit from the master stays one while it keeps its name and nothing is paid for
            // it; renamed, priced from the list, or said to be paid, it is an ordinary line again and
            // is matched and costed as one (schema 29)
            if (!empty($own['free_visit_id']) && ($renamed || $picked || !in_array($mode, ['no_charge', 'unknown'], true))) {
                $this->db->query('UPDATE tf_intake_service SET free_visit_id = NULL WHERE id = ?', [$serviceId]);
                $notes[] = 'No longer the free visit from the master: priced and costed as an ordinary line.';
            } elseif (!empty($own['free_visit_id']) && $mode === 'unknown') {
                $this->db->query("UPDATE tf_intake_service SET pay_mode = 'no_charge' WHERE id = ?", [$serviceId]);
            }
            if (array_key_exists('description', $s)) {
                // the quote's own words for this visit; an empty box means "take the master's text again"
                $this->db->query('UPDATE tf_intake_service SET description = ? WHERE id = ?', [self::cut($s['description'], 4000), $serviceId]);
            }
        } else {
            $next = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM tf_intake_service WHERE intake_id = ? AND day_no = ?', [$id, $dayNo])->getRowArray()['n'] ?? 0);
            $this->db->query(
                "INSERT INTO tf_intake_service (intake_id, day_no, kind, title, qty, pay_mode, special_note, origin, sort_order, fee_code, fee_id) VALUES (?,?,?,?,?,?,?,'human',?,?,?)",
                [$id, $dayNo, $kind, $title, $qty, $mode, $note, $next, $feeCode, $feePick]
            );
            $serviceId = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS id')->getRowArray()['id'] ?? 0);
            if ($placePick !== null && $this->hasMasterCols()) {
                $this->db->query('UPDATE tf_intake_service SET place_id = ? WHERE id = ?', [$placePick, $serviceId]);
            }
        }

        $this->checkServices($id, $serviceId);
        $this->planSectors($id);   // a first visit on a quiet day makes it a day out
        $this->touch($id);
        $line = $this->db->query('SELECT match_state, match_note, unit_usd, pay_mode FROM tf_intake_service WHERE id = ?', [$serviceId])->getRowArray() ?: [];
        if (($line['match_state'] ?? '') === 'not_in_master' && !in_array((string) ($line['pay_mode'] ?? ''), ['own_arrangement', 'not_arranged', 'direct'], true)) {
            $notes[] = 'Held: an entrance or a safari that is not in the Place to Visit / Entrance Fee Master — pick the place, or add it there; it is never taken as free.';
        } elseif (($line['match_state'] ?? '') === 'rate_missing') {
            $notes[] = 'Held: in the Place to Visit / Entrance Fee Master with no rate for the day — the master needs the rate (or Free).';
        } elseif (($line['pay_mode'] ?? '') === 'no_charge' || ($line['match_state'] ?? '') === 'no_charge') {
            $notes[] = 'No charge: shown on the programme, nothing costed.';
        } elseif ($picked && ($line['match_state'] ?? '') === 'matched') {
            $notes[] = 'Priced from the list' . ($line['unit_usd'] !== null ? ' at USD ' . rtrim(rtrim(number_format((float) $line['unit_usd'], 2), '0'), '.') . ' a head' : '') . '.';
        }
        $this->serviceNote = $notes ? implode(' ', $notes) : null;
        return $this->get($id);
    }

    /**
     * Put a free visit from the Free Visits master on a day (schema 29). The line is on the
     * programme and in the itinerary, carries the master's name and description, and is never
     * costed: no priced list is read for it, whatever its words are. The same visit on the same
     * day is one line; a line already there under that name that is free is joined to the
     * master, and one that is costed is left as it is and said.
     */
    public function addFreeVisit(int $id, int $dayNo, int $freeVisitId): array
    {
        $this->assertOpen($id);
        $this->serviceNote = null;
        $fv = $this->masters()->freeVisit($freeVisitId);
        if (!$fv) {
            throw new \InvalidArgumentException('That free visit is not in the master.');
        }
        if ((int) $fv['active'] !== 1) {
            throw new \InvalidArgumentException('"' . $fv['name'] . '" is switched off in the Free Visits master.');
        }
        $dayNo = max(0, $dayNo);
        if ($dayNo > 0 && !$this->db->query('SELECT id FROM tf_intake_day WHERE intake_id = ? AND day_no = ?', [$id, $dayNo])->getRowArray()) {
            throw new \InvalidArgumentException('There is no night ' . $dayNo . ' on this inquiry.');
        }
        $title = (string) $fv['name'];
        $desc  = trim((string) ($fv['description'] ?? '')) !== '' ? trim((string) $fv['description']) : null;
        $was   = $this->db->query('SELECT * FROM tf_intake_service WHERE intake_id = ? AND day_no = ? AND LOWER(TRIM(title)) = LOWER(TRIM(?)) ORDER BY id LIMIT 1', [$id, $dayNo, $title])->getRowArray();
        $where = $dayNo ? 'night ' . $dayNo : 'the tour';
        if ($was) {
            $sid = (int) $was['id'];
            if ((int) ($was['free_visit_id'] ?? 0) === $freeVisitId) {
                $this->serviceNote = '"' . $title . '" is already on ' . $where . '.';
                return $this->get($id);
            }
            if (ServiceLookup::classify($was)['costed']) {
                throw new \InvalidArgumentException('"' . $title . '" is already on ' . $where . ' as a priced line. Change that line if it is really free.');
            }
            $this->db->query("UPDATE tf_intake_service SET free_visit_id = ?, pay_mode = 'no_charge', origin = 'human'" . ($desc !== null && trim((string) ($was['description'] ?? '')) === '' ? ', description = ?' : '') . ' WHERE id = ?',
                array_merge([$freeVisitId], $desc !== null && trim((string) ($was['description'] ?? '')) === '' ? [$desc] : [], [$sid]));
            $this->serviceNote = '"' . $title . '" was already on ' . $where . ' — that line is now the free visit from the master.';
        } else {
            $next = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM tf_intake_service WHERE intake_id = ? AND day_no = ?', [$id, $dayNo])->getRowArray()['n'] ?? 0);
            $this->db->query(
                "INSERT INTO tf_intake_service (intake_id, day_no, kind, title, qty, pay_mode, special_note, origin, sort_order, free_visit_id, description) VALUES (?,?,'visit',?,NULL,'no_charge',NULL,'human',?,?,?)",
                [$id, $dayNo, $title, $next, $freeVisitId, $desc]
            );
            $sid = (int) ($this->db->query('SELECT LAST_INSERT_ID() AS id')->getRowArray()['id'] ?? 0);
            $this->serviceNote = 'Free visit: on the programme and the itinerary, nothing costed.';
        }
        $this->checkServices($id, $sid);
        $this->planSectors($id);   // a first visit on a quiet day makes it a day out
        $this->touch($id);   // the history is written by the screen's edit bracket, like any other line
        return $this->get($id);
    }

    public function deleteService(int $id, int $serviceId): array
    {
        $this->assertOpen($id);
        $gone = $this->db->query('SELECT kind, day_no FROM tf_intake_service WHERE id = ? AND intake_id = ?', [$serviceId, $id])->getRowArray();
        $this->db->query('DELETE FROM tf_intake_service WHERE id = ? AND intake_id = ?', [$serviceId, $id]);
        // schema 40: the last lunch of a day taken off by a person stays off — the automatic lunch place does not put it back
        if ($gone && in_array((string) $gone['kind'], ['lunch', 'dinner'], true) && $this->hasMealCols()
            && !$this->db->query('SELECT id FROM tf_intake_service WHERE intake_id = ? AND day_no = ? AND kind = ? LIMIT 1', [$id, (int) $gone['day_no'], (string) $gone['kind']])->getRowArray()) {
            $this->meals()->setSkip($id, (string) $gone['kind'], (int) $gone['day_no'], true);
        }
        $this->planSectors($id);   // a day with nothing left on it is a stay day again
        $this->touch($id);
        return $this->get($id);
    }


    // =====================================================================
    //  Passenger groups, the room mix, and the days a vehicle is kept on
    // =====================================================================

    /**
     * The room mix a night is costed on: singles, doubles, twins and triples.
     *
     * A party that splits is costed group by group, so the mix for a night is the sum of
     * the groups actually there that night; a party that does not split carries one mix
     * on the inquiry itself. Nothing is derived from the passenger count — "10 passengers"
     * could be five doubles or ten singles, and the difference is the whole hotel bill —
     * so a mix nobody has typed is reported as missing, never guessed.
     *
     * @return array{sgl:int,dbl:int,twn:int,tpl:int,rooms:int,sleeps:int,pax:?int,source:string,groups:array<int,array<string,mixed>>}
     */
    public static function roomMix(array $it, ?int $dayNo): array
    {
        $out    = ['sgl' => 0, 'dbl' => 0, 'twn' => 0, 'tpl' => 0, 'rooms' => 0, 'sleeps' => 0, 'pax' => null, 'source' => 'none', 'groups' => []];
        $groups = (array) ($it['groups'] ?? []);
        if ($groups !== []) {
            $out['source'] = 'groups';
            $pax = 0;
            foreach ($groups as $g) {
                if ($dayNo !== null && !self::groupOn($g, $dayNo)) {
                    continue;
                }
                foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
                    $out[$k] += (int) ($g['rooms_' . $k] ?? 0);
                }
                $pax += (int) ($g['pax'] ?? 0);
                $out['groups'][] = [
                    'id' => (int) $g['id'], 'code' => (string) $g['code'], 'name' => (string) $g['name'], 'pax' => (int) ($g['pax'] ?? 0),
                    'sgl' => (int) ($g['rooms_sgl'] ?? 0), 'dbl' => (int) ($g['rooms_dbl'] ?? 0), 'twn' => (int) ($g['rooms_twn'] ?? 0), 'tpl' => (int) ($g['rooms_tpl'] ?? 0),
                ];
            }
            $out['pax'] = $pax;
        } else {
            foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
                $out[$k] = (int) ($it['rooms_' . $k] ?? 0);
            }
            $out['pax'] = self::intOrNull($it['pax_total'] ?? null);
            if ($out['sgl'] + $out['dbl'] + $out['twn'] + $out['tpl'] > 0) {
                $out['source'] = 'intake';
            }
        }
        $out['rooms']  = $out['sgl'] + $out['dbl'] + $out['twn'] + $out['tpl'];
        $out['sleeps'] = $out['sgl'] + 2 * $out['dbl'] + 2 * $out['twn'] + 3 * $out['tpl'];
        if ($out['source'] === 'groups' && $out['rooms'] === 0) {
            $out['source'] = $out['groups'] === [] ? 'nobody' : 'groups_empty';
        }
        return $out;
    }

    /** Is a group on tour on a given night? Blank ends mean the whole tour. */
    public static function groupOn(array $g, int $dayNo): bool
    {
        $from = self::intOrNull($g['day_from'] ?? null);
        $to   = self::intOrNull($g['day_to'] ?? null);
        return ($from === null || $dayNo >= $from) && ($to === null || $dayNo <= $to);
    }

    /** The room mix for a party that does not split: typed once on the inquiry. */
    public function saveRooms(int $id, array $r): array
    {
        $this->assertOpen($id);
        $bind = [];
        foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
            $n = (int) ($r['rooms_' . $k] ?? $r[$k] ?? 0);
            if ($n < 0 || $n > 999) {
                throw new \InvalidArgumentException('A room count has to be between 0 and 999.');
            }
            $bind[] = $n;
        }
        $bind[] = $id;
        $this->db->query('UPDATE tf_intake SET rooms_sgl = ?, rooms_dbl = ?, rooms_twn = ?, rooms_tpl = ? WHERE id = ?', $bind);
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * A passenger group: part of the party with its own nights, its own room mix and its
     * own names. Vehicles, hotel nights and excursions are costed for the group they
     * serve. A group left blank at both ends is on the whole tour. The first group made
     * from a party that never split before, when nothing is typed for it, takes the
     * party's own count and mix with it so nothing already entered is lost.
     */
    public function saveGroup(int $id, ?int $groupId, array $g): array
    {
        $this->assertOpen($id);
        $it = $this->db->query('SELECT nights, pax_total, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl FROM tf_intake WHERE id = ?', [$id])->getRowArray();
        if (!$it) {
            throw new \RuntimeException('No such inquiry.');
        }
        $nights = self::intOrNull($it['nights']);
        $name   = self::cut($g['name'] ?? null, 80);
        $name   = ($name !== null && trim($name) !== '') ? trim($name) : null;
        $pax    = max(0, min(999, (int) ($g['pax'] ?? 0)));
        $from   = self::intOrNull($g['day_from'] ?? null);
        $to     = self::intOrNull($g['day_to'] ?? null);
        if ($from !== null && $from < 1) {
            throw new \InvalidArgumentException('The first night is night 1.');
        }
        if ($to !== null && $nights !== null && $nights > 0 && $to > $nights) {
            throw new \InvalidArgumentException("The tour has {$nights} night(s); a group cannot stay past night {$nights}.");
        }
        if ($from !== null && $to !== null && $to < $from) {
            throw new \InvalidArgumentException('The group\'s last night cannot come before its first.');
        }
        $rooms = [];
        foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
            $n = (int) ($g['rooms_' . $k] ?? 0);
            if ($n < 0 || $n > 999) {
                throw new \InvalidArgumentException('A room count has to be between 0 and 999.');
            }
            $rooms[$k] = $n;
        }
        $note = self::cut($g['note'] ?? null, 255);
        $code = strtoupper(trim((string) self::cut($g['code'] ?? null, 10)));

        if ($groupId) {
            $own = $this->db->query('SELECT * FROM tf_intake_group WHERE id = ? AND intake_id = ?', [$groupId, $id])->getRowArray();
            if (!$own) {
                throw new \InvalidArgumentException('That group is not on this inquiry.');
            }
            $this->db->query(
                'UPDATE tf_intake_group SET code = ?, name = ?, pax = ?, day_from = ?, day_to = ?, rooms_sgl = ?, rooms_dbl = ?, rooms_twn = ?, rooms_tpl = ?, note = ? WHERE id = ?',
                [$code !== '' ? $code : $own['code'], $name ?? $own['name'], $pax, $from, $to, $rooms['sgl'], $rooms['dbl'], $rooms['twn'], $rooms['tpl'], $note, $groupId]
            );
        } else {
            $count = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_intake_group WHERE intake_id = ?', [$id])->getRowArray()['n'] ?? 0);
            if ($code === '') {
                $code = chr(65 + min(25, $count));
            }
            $name = $name ?? ('Group ' . $code);
            if ($count === 0 && $pax === 0 && array_sum($rooms) === 0) {
                $pax = (int) ($it['pax_total'] ?? 0);
                foreach (['sgl', 'dbl', 'twn', 'tpl'] as $k) {
                    $rooms[$k] = (int) ($it['rooms_' . $k] ?? 0);
                }
            }
            $this->db->query(
                'INSERT INTO tf_intake_group (intake_id, code, name, pax, day_from, day_to, rooms_sgl, rooms_dbl, rooms_twn, rooms_tpl, note, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [$id, $code, $name, $pax, $from, $to, $rooms['sgl'], $rooms['dbl'], $rooms['twn'], $rooms['tpl'], $note, $count]
            );
        }
        $this->planSectors($id);   // a vehicle serving the group drives the group's nights
        $this->touch($id);
        return $this->get($id);
    }

    /** Remove a group. Its names, vehicles and excursions stay, back with the whole party. */
    public function deleteGroup(int $id, int $groupId): array
    {
        $this->assertOpen($id);
        $this->db->query('UPDATE tf_intake_pax SET group_id = NULL WHERE intake_id = ? AND group_id = ?', [$id, $groupId]);
        $this->db->query('UPDATE tf_intake_vehicle SET group_id = NULL WHERE intake_id = ? AND group_id = ?', [$id, $groupId]);
        $this->db->query('UPDATE tf_intake_excursion SET group_id = NULL WHERE intake_id = ? AND group_id = ?', [$id, $groupId]);
        $this->db->query('DELETE FROM tf_intake_group WHERE id = ? AND intake_id = ?', [$groupId, $id]);
        $this->planSectors($id);
        $this->touch($id);
        return $this->get($id);
    }

    /** Put a named passenger in a group, or take them out of one (null). */
    public function assignPassenger(int $id, int $paxId, ?int $groupId): array
    {
        $this->assertOpen($id);
        if ($groupId !== null && !$this->db->query('SELECT id FROM tf_intake_group WHERE id = ? AND intake_id = ?', [$groupId, $id])->getRowArray()) {
            throw new \InvalidArgumentException('That group is not on this inquiry.');
        }
        if (!$this->db->query('SELECT id FROM tf_intake_pax WHERE id = ? AND intake_id = ?', [$paxId, $id])->getRowArray()) {
            throw new \InvalidArgumentException('That name is not on this inquiry.');
        }
        $this->db->query('UPDATE tf_intake_pax SET group_id = ? WHERE id = ?', [$groupId, $paxId]);
        $this->touch($id);
        return $this->get($id);
    }

    /**
     * Keep a vehicle on for a stay day, or release it. Only a retained vehicle charges the
     * stay-day (eighty-kilometre) figure; a released one charges nothing that day and the
     * leg simply is not there. Keyed by the line's position, so a re-plan that rebuilds
     * the lines keeps the decision. Retained is the default: nothing is ever dropped from
     * a quotation quietly, only released on purpose.
     */
    public function setVehicleDay(int $id, int $vehicleSeq, int $dayNo, bool $retained, ?string $note = null): array
    {
        $this->assertOpen($id);
        if ($dayNo < 1 || $vehicleSeq < 0) {
            throw new \InvalidArgumentException('Say which vehicle and which day.');
        }
        $note = self::cut($note, 255);
        $now  = date('Y-m-d H:i:s');
        $this->db->query(
            'INSERT INTO tf_intake_vehicle_day (intake_id, vehicle_seq, day_no, retained, note, updated_by, updated_at) VALUES (?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE retained = VALUES(retained), note = VALUES(note), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
            [$id, $vehicleSeq, $dayNo, $retained ? 1 : 0, $note, $this->user, $now]
        );
        $this->planSectors($id);   // the idle leg goes, or comes back
        $this->touch($id);
        return $this->get($id);
    }

    // =====================================================================
    //  Excursions: their own rate master, and the lines a quotation carries
    // =====================================================================

    public const EXC_UNITS = ['per_person', 'per_group', 'per_vehicle'];

    /** @return array<int, array<string, mixed>> */
    public function excursionRates(bool $activeOnly = false): array
    {
        return $this->db->query('SELECT * FROM tf_excursion_rate' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY active DESC, name')->getResultArray();
    }

    /** Add or change one excursion in the master. Typed by a person, changed by a person; nothing here is automatic. */
    public function saveExcursionRate(array $r): array
    {
        $code = strtoupper(trim((string) ($r['code'] ?? '')));
        $code = (string) preg_replace('/[^A-Z0-9_\-]/', '', $code);
        if ($code === '' || strlen($code) > 30) {
            throw new \InvalidArgumentException('An excursion needs a short code — letters, numbers and dashes, up to 30.');
        }
        $name = self::cut($r['name'] ?? null, 160);
        if ($name === null || trim($name) === '') {
            throw new \InvalidArgumentException('An excursion needs a name.');
        }
        $unit = in_array($r['unit'] ?? '', self::EXC_UNITS, true) ? $r['unit'] : 'per_person';
        if (!isset($r['rate']) || !is_numeric($r['rate']) || (float) $r['rate'] < 0) {
            throw new \InvalidArgumentException('The rate has to be a number, nought or more.');
        }
        $rate = round((float) $r['rate'], 2);
        $cur  = strtoupper(substr(trim((string) ($r['currency'] ?? 'USD')), 0, 3)) ?: 'USD';
        $inc  = !empty($r['includes_transport']) ? 1 : 0;
        $vf   = self::dateOrNull($r['valid_from'] ?? null);
        $vt   = self::dateOrNull($r['valid_to'] ?? null);
        if ($vf !== null && $vt !== null && $vt < $vf) {
            throw new \InvalidArgumentException('Valid-to cannot come before valid-from.');
        }
        $note   = self::cut($r['note'] ?? null, 255);
        $active = array_key_exists('active', $r) ? (!empty($r['active']) ? 1 : 0) : 1;
        $now    = date('Y-m-d H:i:s');
        $was    = $this->db->query('SELECT * FROM tf_excursion_rate WHERE code = ?', [$code])->getRowArray();
        if ($was) {
            $this->db->query(
                'UPDATE tf_excursion_rate SET name = ?, unit = ?, rate = ?, currency = ?, includes_transport = ?, valid_from = ?, valid_to = ?, note = ?, active = ?, updated_by = ?, updated_at = ? WHERE code = ?',
                [trim($name), $unit, $rate, $cur, $inc, $vf, $vt, $note, $active, $this->user, $now, $code]
            );
        } else {
            $this->db->query(
                'INSERT INTO tf_excursion_rate (code, name, unit, rate, currency, includes_transport, valid_from, valid_to, note, active, updated_by, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [$code, trim($name), $unit, $rate, $cur, $inc, $vf, $vt, $note, $active, $this->user, $now]
            );
        }
        $now_ = ['name' => trim($name), 'unit' => $unit, 'rate' => $rate, 'currency' => $cur, 'includes_transport' => $inc, 'valid_from' => $vf, 'valid_to' => $vt, 'note' => $note, 'active' => $active];
        Activity::master($this->db, $this->user, 'excursion_rate', 0, 'Excursion Rates: ' . $code . ' ' . ($was ? (float) $was['rate'] . ' → ' . (float) $rate . ' ' . $cur : 'added at ' . (float) $rate . ' ' . $cur),
            array_map(static fn ($k) => ['what' => $code . ' ' . str_replace('_', ' ', $k), 'from' => $was[$k] ?? null, 'to' => $now_[$k]], array_keys($now_)), $this->actorId, $this->actorName);
        return $this->db->query('SELECT * FROM tf_excursion_rate WHERE code = ?', [$code])->getRowArray();
    }

    /**
     * Remove an excursion from the master. One a quotation has already used is kept on
     * file and switched off instead, so the line it was copied onto still says where its
     * figure came from.
     *
     * @return array{deleted:bool,deactivated:bool,used:int}
     */
    public function deleteExcursionRate(string $code): array
    {
        $code = strtoupper(trim($code));
        $used = (int) ($this->db->query('SELECT COUNT(*) n FROM tf_intake_excursion WHERE code = ?', [$code])->getRowArray()['n'] ?? 0);
        if ($used > 0) {
            $this->db->query('UPDATE tf_excursion_rate SET active = 0, updated_by = ?, updated_at = ? WHERE code = ?', [$this->user, date('Y-m-d H:i:s'), $code]);
            return ['deleted' => false, 'deactivated' => true, 'used' => $used];
        }
        $this->db->query('DELETE FROM tf_excursion_rate WHERE code = ?', [$code]);
        return ['deleted' => true, 'deactivated' => false, 'used' => 0];
    }

    /**
     * Put an excursion on a day of the quotation, or change one. The rate is copied from
     * the master the moment the line is made — a later change to the master never moves a
     * figure already quoted — and the quantity starts from what the unit implies: the
     * people going, one per group, or one per vehicle serving them. Day, group, quantity,
     * description and note are the line's own to edit; nothing here writes to the master.
     */
    public function saveExcursion(int $id, ?int $lineId, array $e): array
    {
        $this->assertOpen($id);
        $line = null;
        if ($lineId) {
            $line = $this->db->query('SELECT * FROM tf_intake_excursion WHERE id = ? AND intake_id = ?', [$lineId, $id])->getRowArray();
            if (!$line) {
                throw new \InvalidArgumentException('That excursion is not on this inquiry.');
            }
        }
        $dayNo = array_key_exists('day_no', $e) ? (int) $e['day_no'] : (int) ($line['day_no'] ?? 0);
        if ($dayNo < 1) {
            throw new \InvalidArgumentException('An excursion sits on a day of the tour — choose which.');
        }
        $groupId = array_key_exists('group_id', $e) ? self::intOrNull($e['group_id']) : self::intOrNull($line['group_id'] ?? null);
        if ($groupId !== null && !$this->db->query('SELECT id FROM tf_intake_group WHERE id = ? AND intake_id = ?', [$groupId, $id])->getRowArray()) {
            throw new \InvalidArgumentException('That group is not on this inquiry.');
        }

        $code = strtoupper(trim((string) ($e['code'] ?? ($line['code'] ?? ''))));
        if ($line === null || $code !== (string) $line['code']) {
            // a new line, or a different excursion on it: the master's figures are copied afresh
            $master = $code !== '' ? $this->db->query('SELECT * FROM tf_excursion_rate WHERE code = ?', [$code])->getRowArray() : null;
            if (!$master) {
                throw new \InvalidArgumentException('Choose an excursion from the Excursion Rates master. If it is not there yet, add it under Master Files → Excursion Rates first — nothing is typed in here.');
            }
            if ((int) $master['active'] !== 1) {
                throw new \InvalidArgumentException('That excursion is switched off in the master.');
            }
            $name = (string) $master['name'];
            $unit = (string) $master['unit'];
            $rate = (float) $master['rate'];
            $cur  = (string) $master['currency'];
            $inc  = (int) $master['includes_transport'];
        } else {
            $name = (string) $line['name'];
            $unit = (string) $line['unit'];
            $rate = (float) $line['rate'];
            $cur  = (string) $line['currency'];
            $inc  = (int) $line['includes_transport'];
        }

        // the same excursion added again to the same day and group is that line, not a second
        // one — a double click on Add, or a re-pick from the list, updates what is there
        $this->serviceNote = null;
        if ($line === null) {
            $dup = $this->db->query('SELECT * FROM tf_intake_excursion WHERE intake_id = ? AND day_no = ? AND code = ? AND ' . ($groupId === null ? 'group_id IS NULL' : 'group_id = ?') . ' ORDER BY id LIMIT 1', $groupId === null ? [$id, $dayNo, $code] : [$id, $dayNo, $code, $groupId])->getRowArray();
            if ($dup) {
                $line   = $dup;
                $lineId = (int) $dup['id'];
                $this->serviceNote = $name . ' is already on day ' . $dayNo . ' — that line was updated, not added again.';
            }
        }

        $typedQty = array_key_exists('qty', $e) && $e['qty'] !== null && $e['qty'] !== '';
        if ($typedQty) {
            if (!is_numeric($e['qty']) || (float) $e['qty'] < 0) {
                throw new \InvalidArgumentException('The quantity has to be a number, nought or more.');
            }
            $qty = round((float) $e['qty'], 2);
        } elseif ($line !== null && $code === (string) $line['code'] && !array_key_exists('group_id', $e)) {
            $qty = (float) $line['qty'];
        } else {
            $qty = $this->excursionQty($id, $unit, $groupId);
        }
        // charged per person: never for more people than are going
        if ($unit === 'per_person') {
            $ceiling = $this->excursionQty($id, 'per_person', $groupId);
            if ($ceiling > 0 && $qty > $ceiling) {
                $this->serviceNote = trim(($this->serviceNote ?? '') . ' The quantity was capped at the ' . (int) $ceiling . ' going — a per-person excursion is never for more people than the inquiry has.');
                $qty = $ceiling;
            }
        }
        $amount = round($qty * $rate, 2);
        $desc   = array_key_exists('description', $e) ? self::cut($e['description'], 4000) : ($line['description'] ?? null);
        $note   = array_key_exists('note', $e) ? self::cut($e['note'], 255) : ($line['note'] ?? null);

        if ($line !== null) {
            $this->db->query(
                'UPDATE tf_intake_excursion SET day_no = ?, group_id = ?, code = ?, name = ?, unit = ?, qty = ?, rate = ?, currency = ?, amount = ?, includes_transport = ?, description = ?, note = ? WHERE id = ?',
                [$dayNo, $groupId, $code, $name, $unit, $qty, $rate, $cur, $amount, $inc, $desc, $note, $lineId]
            );
        } else {
            $next = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM tf_intake_excursion WHERE intake_id = ? AND day_no = ?', [$id, $dayNo])->getRowArray()['n'] ?? 0);
            $this->db->query(
                'INSERT INTO tf_intake_excursion (intake_id, day_no, group_id, code, name, unit, qty, rate, currency, amount, includes_transport, description, note, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [$id, $dayNo, $groupId, $code, $name, $unit, $qty, $rate, $cur, $amount, $inc, $desc, $note, $next]
            );
        }
        $this->planSectors($id);   // an excursion makes its day a day out; one with transport waives the local running
        $this->touch($id);
        return $this->get($id);
    }

    public function deleteExcursion(int $id, int $lineId): array
    {
        $this->assertOpen($id);
        $this->db->query('DELETE FROM tf_intake_excursion WHERE id = ? AND intake_id = ?', [$lineId, $id]);
        $this->planSectors($id);
        $this->touch($id);
        return $this->get($id);
    }

    /** What the unit implies for the quantity: the people going, one per group, or one per vehicle serving them. */
    private function excursionQty(int $id, string $unit, ?int $groupId): float
    {
        if ($unit === 'per_group') {
            return 1.0;
        }
        if ($unit === 'per_vehicle') {
            $n = 0;
            foreach ($this->db->query('SELECT group_id, qty FROM tf_intake_vehicle WHERE intake_id = ?', [$id])->getResultArray() as $l) {
                $lg = self::intOrNull($l['group_id']);
                if ($groupId === null || $lg === null || $lg === $groupId) {
                    $n += max(1, (int) $l['qty']);
                }
            }
            return (float) max(1, $n);
        }
        if ($groupId !== null) {
            $g = $this->db->query('SELECT pax FROM tf_intake_group WHERE id = ?', [$groupId])->getRowArray();
            return (float) max(0, (int) ($g['pax'] ?? 0));
        }
        $pax = self::intOrNull($this->db->query('SELECT pax_total FROM tf_intake WHERE id = ?', [$id])->getRowArray()['pax_total'] ?? null);
        return (float) ($pax ?? 0);   // nought is a gap the panel reports, not a guess
    }

    /**
     * Mileage is never charged twice. An excursion whose rate includes its own transport —
     * a jeep safari, a boat, a train — is the vehicle for that day, so the local running
     * (or the stay-day figure) of every vehicle line serving that party on that day is
     * waived: still shown on the leg, marked with the excursion's name, never added up.
     * A transfer on the same day is untouched: the party still had to be driven there.
     * A line carrying the whole party is not waived for an excursion only one group takes.
     */
    private function applyExcursionWaivers(int $id): void
    {
        $this->db->query('UPDATE tf_intake_sector SET waived_by = NULL WHERE intake_id = ? AND waived_by IS NOT NULL', [$id]);
        $exc = $this->db->query('SELECT day_no, group_id, name FROM tf_intake_excursion WHERE intake_id = ? AND includes_transport = 1 AND day_no > 0 ORDER BY day_no, sort_order, id', [$id])->getResultArray();
        if ($exc === []) {
            return;
        }
        $lines = $this->db->query('SELECT sort_order, group_id FROM tf_intake_vehicle WHERE intake_id = ?', [$id])->getResultArray();
        if ($lines === []) {
            $lines = [['sort_order' => 0, 'group_id' => null]];
        }
        foreach ($exc as $x) {
            $xg = self::intOrNull($x['group_id']);
            foreach ($lines as $l) {
                $lg = self::intOrNull($l['group_id']);
                if ($xg !== null && $xg !== $lg) {
                    continue;
                }
                $this->db->query(
                    "UPDATE tf_intake_sector SET waived_by = ? WHERE intake_id = ? AND vehicle_seq = ? AND day_no = ? AND kind IN ('local','idle') AND waived_by IS NULL" . ($this->hasRemoved() ? ' AND removed_at IS NULL' : ''),
                    [self::cut($x['name'], 190), $id, (int) $l['sort_order'], (int) $x['day_no']]
                );
            }
        }
    }


    /**
     * The panel hotel a typed name means. The panel's own name first; then the names the
     * hotel's supplier records carry (the name it had before a rename, the name the old
     * screens still use), so an email that says "Kynrock Airport hotel" still lands on the
     * hotel now called Kynrock Katunayake; then a one-letter slip on either.
     */
    public function matchHotel(string $raw): ?array
    {
        $key = HotelRateService::nameKey($raw);
        if ($key === '') {
            return null;
        }
        $rows = $this->db->query("SELECT id, name, primary_sup_acc_no FROM hr2_hotel WHERE status <> 'merged'")->getResultArray();
        $near = null;
        foreach ($rows as $h) {
            $hk = HotelRateService::nameKey((string) $h['name']);
            if ($hk === $key) {
                return $h;
            }
            if ($near === null && strlen($hk) >= 6 && abs(strlen($hk) - strlen($key)) <= 2 && levenshtein($hk, $key) <= 1) {
                $near = $h;
            }
        }
        // the other names the hotel goes by: its supplier records' names (legacy_name is what
        // the supplier master calls it, kept when the panel's name is corrected)
        try {
            $byId = [];
            foreach ($rows as $h) {
                $byId[(int) $h['id']] = $h;
            }
            $aliases = $this->db->query('SELECT hotel_id, sup_acc_no, legacy_name FROM hr2_hotel_account')->getResultArray();
            // the supplier master's current names, read on their own (the two tables differ in
            // character set on the live install, so no join)
            $supplierNames = [];
            try {
                foreach ($this->db->query('SELECT Sup_Acc_No, Name FROM supplier WHERE sup_cat = 1')->getResultArray() as $sr) {
                    $supplierNames[trim((string) $sr['Sup_Acc_No'])] = (string) $sr['Name'];
                }
            } catch (\Throwable $e) {
                // no supplier table here: the legacy names alone
            }
            foreach ($aliases as $a) {
                $h = $byId[(int) $a['hotel_id']] ?? null;
                if ($h === null) {
                    continue;
                }
                foreach ([$a['legacy_name'], $supplierNames[trim((string) $a['sup_acc_no'])] ?? null] as $alias) {
                    $ak = HotelRateService::nameKey((string) $alias);
                    if ($ak === '') {
                        continue;
                    }
                    if ($ak === $key) {
                        return $h;
                    }
                    if ($near === null && strlen($ak) >= 6 && abs(strlen($ak) - strlen($key)) <= 2 && levenshtein($ak, $key) <= 1) {
                        $near = $h;
                    }
                }
            }
        } catch (\Throwable $e) {
            // no supplier join on this install: the panel names alone
        }
        return $near;
    }

    /**
     * Why a night has no published rate, in the words that say what to do next: a draft
     * waiting for approval (the commonest — a sheet typed in but not yet approved is not
     * published), an offer or tactical plan that covers the date but is never quoted, a plan
     * that covers the date by validity but has no season dated for it, a plan for another
     * market, and otherwise the nearest plans either side of the date.
     */
    public static function whyNoRate(array $on, string $date, ?string $market): string
    {
        $when   = date('j M Y', strtotime($date));
        $drafts = array_values((array) ($on['drafts'] ?? []));
        $others = array_values((array) ($on['other_market_plans'] ?? []));
        $parts  = [];
        if ($drafts) {
            $names = array_map(static fn ($p) => (string) ($p['name'] ?? 'draft') . ' (' . str_replace('_', ' ', (string) ($p['plan_type'] ?? '')) . ')', array_slice($drafts, 0, 2));
            $parts[] = count($drafts) . ' draft plan(s) cover ' . $when . ' but are not approved yet: ' . implode('; ', $names) . (count($drafts) > 2 ? ' and more' : '') . '. Approve them under Hotel Rates → Approval queue, then press Check hotels again.';
        }
        $notQuoted   = [];
        $noSeason    = [];
        $otherMarket = [];
        foreach ($others as $p) {
            $why = (string) ($p['why'] ?? '');
            if (str_contains($why, 'not quoted')) {
                $notQuoted[] = (string) ($p['name'] ?? '') . ' (' . str_replace('_', ' ', (string) ($p['plan_type'] ?? '')) . ')';
            } elseif (str_contains($why, 'no season')) {
                $noSeason[] = (string) ($p['name'] ?? '');
            } elseif (str_contains($why, 'markets')) {
                $otherMarket[] = (string) ($p['name'] ?? '') . ' (' . $why . ')';
            }
        }
        if ($notQuoted) {
            $parts[] = 'Only ' . implode('; ', array_slice($notQuoted, 0, 2)) . ' covers ' . $when . '. A quotation uses the published contract rate, never an offer or tactical rate: enter the contract for these dates, or, if that sheet is the contract, set its plan type to Contract in Hotel Rates.';
        }
        if ($noSeason) {
            $parts[] = implode('; ', array_slice($noSeason, 0, 2)) . ' is approved for a period that includes ' . $when . ' but has no season dated for it — add the season in Hotel Rates.';
        }
        if ($otherMarket) {
            $parts[] = implode('; ', array_slice($otherMarket, 0, 2)) . ' covers ' . $when . ' but not for market ' . ($market ?: 'the default') . '.';
        }
        if (!$parts) {
            $note = 'No approved plan covers ' . $when . '.';
            $prev = self::nearestDate($on['nearest']['previous'] ?? null);
            $next = self::nearestDate($on['nearest']['next'] ?? null);
            if ($prev !== null) {
                $note .= ' Previous plan ends ' . $prev . '.';
            }
            if ($next !== null) {
                $note .= ' Next starts ' . $next . '.';
            }
            return $note;
        }
        return implode(' ', $parts);
    }

    /**
     * Hotels whose name is close to what the agent typed — a slip of two or three letters, or
     * the panel's name inside the agent's words — offered as a suggestion, never chosen.
     *
     * @return string[] up to $max panel names
     */
    public function nearHotelNames(string $raw, int $max = 2): array
    {
        $key = HotelRateService::nameKey($raw);
        if (strlen($key) < 5) {
            return [];
        }
        $scored = [];
        try {
            $rows = $this->db->query("SELECT name FROM hr2_hotel WHERE status = 'active'")->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
        foreach ($rows as $h) {
            $hk = HotelRateService::nameKey((string) $h['name']);
            if ($hk === '' || $hk === $key) {
                continue;
            }
            $d = levenshtein($hk, $key);
            if ($d <= 3 && $d <= (int) floor(strlen($key) / 4)) {
                $scored[] = [$d, (string) $h['name']];
            } elseif (strlen($hk) >= 8 && (str_contains($key, $hk) || str_contains($hk, $key))) {
                $scored[] = [4, (string) $h['name']];
            }
        }
        usort($scored, static fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
        return array_slice(array_column($scored, 1), 0, $max);
    }

    /** Is there a transcribed sheet waiting on a supplier record for this hotel? */
    private function sheetWaitingFor(string $raw): bool
    {
        $key = HotelRateService::nameKey($raw);
        if ($key === '') {
            return false;
        }
        try {
            $rows = $this->db->query("SELECT hotel_name FROM hr2_work_item WHERE kind = 'sheet_no_supplier' AND status = 'open' AND hotel_name IS NOT NULL")->getResultArray();
        } catch (\Throwable $e) {
            return false;
        }
        foreach ($rows as $r) {
            if (HotelRateService::nameKey((string) $r['hotel_name']) === $key) {
                return true;
            }
        }
        return false;
    }

    // =====================================================================
    //  What is still needed
    // =====================================================================

    /** Derived, never stored, so it is always true of the inquiry as it stands now. */
    // =====================================================================
    //  The dates rule (schema 32)
    // =====================================================================

    /** Closed: confirmed, declined, cancelled, converted or archived. Opening one changes nothing on it. */
    public static function isClosed(array $it): bool
    {
        return in_array((string) ($it['status'] ?? ''), ['confirmed', 'cancelled', 'declined', 'converted'], true) || !empty($it['archived_at']);
    }

    private function hasDatesRule(): bool
    {
        if ($this->hasDatesRule === null) {
            try {
                $this->hasDatesRule = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake` LIKE 'email_arrival_date'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasDatesRule = false;
            }
        }
        return $this->hasDatesRule;
    }

    private function hasCrewDaysBy(): bool
    {
        if ($this->hasCrewDaysBy === null) {
            try {
                $this->hasCrewDaysBy = (bool) $this->db->query("SHOW COLUMNS FROM `tf_intake_crew` LIKE 'days_by'")->getResultArray();
            } catch (\Throwable $e) {
                $this->hasCrewDaysBy = false;
            }
        }
        return $this->hasCrewDaysBy;
    }

    /**
     * The departure day, made from the dates: the day after the last night, the transfer from the last
     * night's place to the airport and the departure flight, and whatever a person or the email put on
     * that day — a late check-out, lunch, a visit on the way. Never a hotel. Null until both dates are set.
     */
    private function departureDay(array $it, array $byDay, array $excByDay, array $legsByDay): ?array
    {
        $td = $it['tour_dates'] ?? TourDates::of($it);
        if ($td['nights'] === null) {
            return null;
        }
        $no   = (int) $td['departure_day_no'];
        $last = null;
        foreach (($it['days'] ?? []) as $d) {
            if ((int) $d['day_no'] <= (int) $td['nights']) {
                $last = $d;
            }
        }
        $from = $last ? trim((string) ($last['hotel_name_raw'] ?: $last['area'] ?? '')) : '';
        return [
            'day_no'     => $no,
            'date'       => $td['departure'],
            'from'       => $from !== '' ? $from : null,
            'from_area'  => $last['area'] ?? null,
            'last_night' => $last ? (int) $last['day_no'] : null,
            'flight'     => $it['departure_flight'] ?? null,
            'services'   => $byDay[$no] ?? [],
            'excursions' => $excByDay[$no] ?? [],
            'legs'       => $legsByDay[$no] ?? [],
            'text'       => $it['departure_text'] ?? null,
            'narrative'  => $it['departure_narrative'] ?? null,
            'note'       => $it['departure_note'] ?? null,
            'editable'   => $this->hasDatesRule(),
        ];
    }

    /**
     * The email's own dates beside the inquiry's. When a person's dates differ from what the email
     * says, somebody confirms which stand; the confirmation is kept with the pair it was given for,
     * so a later change to either lapses it. Null before schema 32 or with no email dates on file.
     */
    public function datesCheck(array $it): ?array
    {
        if (!$this->hasDatesRule()) {
            return null;
        }
        $ea = TourDates::iso($it['email_arrival_date'] ?? null);
        $ed = TourDates::iso($it['email_departure_date'] ?? null);
        if ($ea === null && $ed === null) {
            return null;
        }
        $a = TourDates::iso($it['arrival_date'] ?? null);
        $d = TourDates::iso($it['departure_date'] ?? null);
        $arrDiff = $ea !== null && $ea !== $a;
        $depDiff = $ed !== null && $ed !== $d;
        $differs = $arrDiff || $depDiff;
        $key = TourDates::confirmKey($a, $d, $ea, $ed);
        $byKey = $differs && (string) ($it['dates_confirmed_key'] ?? '') === $key;
        // schema 33: a date an applied Edit Request set (or confirmed as it stands) is settled against
        // the email for as long as the inquiry still holds that date. The other date is not: when only
        // one of the two was settled, the question stays open for the other one.
        $cover = [];
        if ($differs && !empty($it['id'])) {
            foreach (EditRequests::dateCoverage($this->db, (int) $it['id']) as $field => $c) {
                if (TourDates::iso($c['value']) === ($field === 'arrival_date' ? $a : $d)) {
                    $cover[$field] = $c;
                }
            }
        }
        $arrCov = $arrDiff ? ($cover['arrival_date'] ?? null) : null;
        $depCov = $depDiff ? ($cover['departure_date'] ?? null) : null;
        $byRequest = $differs && (!$arrDiff || $arrCov !== null) && (!$depDiff || $depCov !== null);
        $confirmed = $byKey || $byRequest;
        $open = [];
        if ($differs && !$confirmed) {
            if ($arrDiff && $arrCov === null) {
                $open[] = 'arrival';
            }
            if ($depDiff && $depCov === null) {
                $open[] = 'departure';
            }
        }
        $settled = [];
        foreach (['arrival' => [$arrCov, $a], 'departure' => [$depCov, $d]] as $which => [$c, $v]) {
            if ($c !== null) {
                $settled[] = 'the ' . $which . ' (' . TourDates::day($v) . ') was confirmed by Edit Request #' . $c['seq'] . ($c['by'] ? ' (' . $c['by'] . ($c['at'] ? ', ' . date('j M Y', strtotime((string) $c['at'])) : '') . ')' : '');
            }
        }
        $req = $byRequest && !$byKey ? ($depCov ?? $arrCov) : null;
        $f = $it['fields'] ?? [];
        return [
            'email_arrival'   => $ea,
            'email_departure' => $ed,
            'email_line'      => TourDates::line($ea, $ed),
            'line'            => TourDates::line($a, $d),
            'differs'         => $differs,
            'confirmed'       => $confirmed,
            'confirmed_by'    => $byKey ? ($it['dates_confirmed_by'] ?? null) : ($req ? 'Edit Request #' . $req['seq'] . ($req['by'] ? ' (' . $req['by'] . ')' : '') : null),
            'confirmed_at'    => $byKey ? ($it['dates_confirmed_at'] ?? null) : ($req['at'] ?? null),
            // each date on its own: which still differs from the email, and which an Edit Request settled
            'arrival'         => ['email' => $ea, 'inquiry' => $a, 'differs' => $arrDiff, 'edit_request' => $arrCov],
            'departure'       => ['email' => $ed, 'inquiry' => $d, 'differs' => $depDiff, 'edit_request' => $depCov],
            'open'            => $open,
            'open_line'       => implode('; ', array_map(static fn ($w) => 'the ' . $w . ': the email says ' . TourDates::day($w === 'arrival' ? $ea : $ed) . ', the inquiry has ' . (($w === 'arrival' ? $a : $d) !== null ? TourDates::day($w === 'arrival' ? $a : $d) : 'no date'), $open)),
            'settled_line'    => $settled ? ucfirst(implode('; ', $settled)) . '.' : '',
            'by_hand'         => ($f['arrival_date']['origin'] ?? '') === 'human' || ($f['departure_date']['origin'] ?? '') === 'human',
            'basis'           => trim(implode(' ', array_filter([
                ($f['arrival_date']['origin'] ?? '') === 'human' ? (string) ($f['arrival_date']['basis'] ?? '') : '',
                ($f['departure_date']['origin'] ?? '') === 'human' && ($f['departure_date']['basis'] ?? '') !== ($f['arrival_date']['basis'] ?? '') ? (string) ($f['departure_date']['basis'] ?? '') : '',
            ]))),
        ];
    }

    /** The dates rule's lines on the inquiry's checklist. */
    private function datesGaps(array $it): array
    {
        $g  = [];
        $td = $it['tour_dates'] ?? TourDates::of($it);
        foreach (($it['departure_rows'] ?? []) as $r) {
            $n = (int) $r['day_no'];
            $when = !empty($r['day_date']) ? ' (' . date('j M Y', strtotime((string) $r['day_date'])) . ')' : '';
            $what = trim((string) ($r['hotel_name_raw'] ?? '')) !== '' ? ', ' . trim((string) $r['hotel_name_raw']) : (trim((string) ($r['area'] ?? '')) !== '' ? ', ' . trim((string) $r['area']) : '');
            $g[] = ['code' => 'departure_row_' . $r['id'], 'level' => 'red', 'label' => "Night {$n}{$when}{$what} falls on the departure day: the dates give {$td['nights']} night(s). Press Make it the departure day, or It's an extra night if the party really stays that night."];
        }
        if (($it['missing_nights'] ?? []) !== []) {
            $m = $it['missing_nights'];
            $g[] = ['code' => 'nights_missing', 'level' => 'red', 'label' => 'The dates give ' . $td['nights'] . ' night(s), but night' . (count($m) === 1 ? ' ' : 's ') . TourDates::runs($m) . (count($m) === 1 ? ' has' : ' have') . ' no line. Press Add the missing nights and name the hotels.'];
        }
        $dc = $it['dates_check'] ?? null;
        if ($dc && $dc['differs']) {
            if ($dc['confirmed']) {
                $g[] = ['code' => 'dates_kept', 'level' => 'blue', 'label' => 'The email says ' . $dc['email_line'] . '. The inquiry\'s dates were confirmed by ' . ($dc['confirmed_by'] ?: 'a colleague') . ($dc['confirmed_at'] ? ' on ' . date('j M Y', strtotime((string) $dc['confirmed_at'])) : '') . '.'];
            } elseif (($dc['settled_line'] ?? '') !== '') {
                // schema 33: one date settled by an Edit Request, the other still to confirm
                $g[] = ['code' => 'dates_email', 'level' => 'red', 'label' => 'The email says ' . $dc['email_line'] . ', but this inquiry has ' . $dc['line'] . '. ' . $dc['settled_line'] . ' Still to confirm — ' . $dc['open_line'] . ': Keep these dates, or Use the email\'s ' . (count($dc['open']) === 1 ? $dc['open'][0] : 'dates') . '. The quotation waits until then.'];
            } else {
                $g[] = ['code' => 'dates_email', 'level' => 'red', 'label' => 'The email says ' . $dc['email_line'] . ', but this inquiry has ' . $dc['line'] . ($dc['by_hand'] ? ' (changed by hand)' : '') . '. Confirm which is right: Keep these dates, or Use the email\'s dates. The quotation waits until then.'];
            }
        }
        return $g;
    }

    /**
     * The days the party is served in the programme, worked out from what is on it: every day with a
     * leg (a transfer, a day out, the vehicle on duty on a free day), a visit or an activity — the
     * arrival and the departure days included when something happens on them. A day the vehicle is
     * released with nothing on it does not count. Garage runs alone do not make a day.
     *
     * @return array<int, int> the day numbers, in order
     */
    public function programmeDays(array $it): array
    {
        $td  = $it['tour_dates'] ?? TourDates::of($it);
        $max = $td['nights'] !== null ? (int) $td['nights'] + 1 : null;
        $on  = [];
        foreach (($it['sectors'] ?? []) as $s) {
            if (!empty($s['removed_at']) || (string) ($s['kind'] ?? '') === 'garage') {
                continue;
            }
            $on[(int) $s['day_no']] = true;
        }
        foreach (($it['services'] ?? []) as $s) {
            if ((int) $s['day_no'] <= 0 || (string) ($s['pay_mode'] ?? '') === 'not_arranged') {
                continue;
            }
            $on[(int) $s['day_no']] = true;
        }
        foreach (($it['excursions'] ?? []) as $x) {
            if ((int) $x['day_no'] > 0) {
                $on[(int) $x['day_no']] = true;
            }
        }
        $days = array_values(array_filter(array_keys($on), static fn ($n) => $n >= 1 && ($max === null || $n <= $max)));
        sort($days);
        return $days;
    }

    /** The programme's days for an inquiry, read from the tables (for the crew figure kept on each line). */
    private function programmeDaysFor(int $id): array
    {
        $it = $this->db->query('SELECT arrival_date, departure_date FROM tf_intake WHERE id = ?', [$id])->getRowArray() ?: [];
        $it['sectors']    = $this->db->query('SELECT day_no, kind' . ($this->hasRemoved() ? ', removed_at' : '') . ' FROM tf_intake_sector WHERE intake_id = ?', [$id])->getResultArray();
        $it['services']   = $this->db->query('SELECT day_no, pay_mode FROM tf_intake_service WHERE intake_id = ?', [$id])->getResultArray();
        $it['excursions'] = $this->db->query('SELECT day_no FROM tf_intake_excursion WHERE intake_id = ?', [$id])->getResultArray();
        return $this->programmeDays($it);
    }

    /** "Days 1 to 8 — every day with a transfer, a visit or the vehicle on duty, arrival and departure included." */
    private static function programmeBasis(array $days, ?int $depNo): string
    {
        if ($days === []) {
            return 'Nothing on the programme yet to count days from: no leg, no visit, no activity.';
        }
        $words = 'Day' . (count($days) === 1 ? ' ' : 's ') . TourDates::runs($days);
        $ends  = [];
        if ($days[0] === 1) {
            $ends[] = 'arrival';
        }
        if ($depNo !== null && end($days) === $depNo) {
            $ends[] = 'departure';
        }
        return $words . ' — every day with a transfer, a visit or the vehicle on duty' . ($ends ? ', ' . implode(' and ', $ends) . ' included' : '') . '.';
    }

    /**
     * The crew lines with their days as the costing uses them: the programme's figure, unless a person
     * typed the days (days_by = human). A closed inquiry keeps the days it was sold with.
     */
    private function crewWithDays(array $it): array
    {
        $crew = $it['crew'] ?? [];
        if ($crew === []) {
            return $crew;
        }
        $closed = self::isClosed($it);
        $byRule = $this->hasCrewDaysBy();
        $prog   = $it['programme_days'] ?? $this->programmeDays($it);
        $auto   = $prog !== [] ? count($prog) : null;
        $depNo  = $it['tour_dates']['departure_day_no'] ?? null;
        foreach ($crew as &$c) {
            $c['days_stored'] = $c['days'];
            $c['days_auto']   = $auto;
            $human = (string) ($c['days_by'] ?? '') === 'human';
            if (!$byRule || $closed || $human) {
                // a closed inquiry's line is read exactly as it was: a line never marked (from before the
                // rule) keeps the old reading — its days, or the nights when it has none
                $c['days_by']    = $byRule ? ($human ? 'human' : ($c['days_by'] ?? null)) : null;
                $c['days_basis'] = $human ? 'Set by hand.' : ($closed ? 'As the inquiry was closed with.' : '');
                continue;
            }
            $c['days_by']    = 'auto';
            $c['days']       = $auto;
            $c['days_basis'] = self::programmeBasis($prog, $depNo);
            $bata = ($c['bata'] === null || $c['bata'] === '') ? null : (float) $c['bata'];
            $c['amount'] = ($auto !== null && $bata !== null) ? round($auto * $bata, 2) : null;
        }
        unset($c);
        return $crew;
    }

    public function gaps(array $it): array
    {
        $g      = [];
        $fields = $it['fields'] ?? [];

        $mixSet = !in_array(($it['rooms'] ?? self::roomMix($it, null))['source'], ['none', 'nobody', 'groups_empty'], true);
        foreach ([
            'arrival_date' => 'Arrival date',
            'pax_total'    => 'Number of passengers',
            'rooms_note'   => 'Room requirement',
        ] as $key => $label) {
            $state = $fields[$key]['state'] ?? 'missing';
            if ($key === 'rooms_note' && $mixSet) {
                continue;   // the room mix is typed below; the agent's wording of it is no longer a hole
            }
            if ($state === 'missing' || ($fields[$key]['value'] ?? null) === null) {
                $g[] = ['code' => $key, 'level' => 'red', 'label' => $label . ' is not stated in the email.'];
            }
        }
        // The dates decide the night count. When the email claims a different number that
        // disagreement is worth a person's eye — usually it means a date is wrong, and a
        // quotation built on the wrong span is wrong in every line.
        $claimed = $fields['nights']['claimed'] ?? null;
        $counted = $fields['nights']['value'] ?? null;
        if ($claimed !== null && $counted !== null && (int) $claimed !== (int) $counted) {
            $g[] = ['code' => 'nights_disagree', 'level' => 'red', 'label' => "The email says {$claimed} night(s), but the dates given come to {$counted}. The dates are used — check which is wrong."];
        }
        if ($claimed !== null && $counted === null) {
            $g[] = ['code' => 'nights_nodates', 'level' => 'amber', 'label' => "The email mentions {$claimed} night(s), but without both dates the nights cannot be counted, so none is recorded."];
        }
        // schema 32: the dates decide the shape. A line on or after the departure date is not a night;
        // a night the dates give with no line has nowhere to sleep; and when a person's dates differ
        // from the email's, somebody confirms which stand before the quotation goes.
        foreach ($this->datesGaps($it) as $dg) {
            $g[] = $dg;
        }
        // schema 33: an Edit Request applied after the quotation version on record was issued — the
        // version to be confirmed has to be issued after it, whether or not the cost moved
        $stale = $it['edit_requests']['stale_issue'] ?? null;
        if ($stale) {
            $g[] = ['code' => 'edit_request_reissue', 'level' => 'amber', 'label' => 'Edit Request #' . $stale['seq'] . ' was applied on ' . date('j M Y H:i', strtotime((string) $stale['applied_at'])) . ', after quotation version ' . $stale['version'] . ' was made. Go for Quotation and Issue a new version: Confirm Tour waits for a version made and issued after the amendment, whether or not the price moved.'];
        }
        foreach ((array) ($it['edit_requests']['waiting'] ?? []) as $w) {
            $g[] = ['code' => 'edit_request_waiting_' . $w['seq'], 'level' => 'amber', 'label' => ($it['edit_requests']['standing'] ?? '') === 'locked'
                ? 'Edit Request #' . $w['seq'] . ' is waiting for amendment' . ($w['reason'] ? ' (' . $w['reason'] . ')' : '') . ': it changes nothing until Management opens the tour with Amend on the Tour Board.'
                : 'Edit Request #' . $w['seq'] . ' was saved while the tour was locked' . ($w['reason'] ? ' (' . $w['reason'] . ')' : '') . '. The tour is open for amendment now: check it under Edit Request, below the email, and confirm it or withdraw it.'];
        }
        // What the email's own text says about its dates (TextChecks): a year missing while
        // the arrival is still unknown is the reason it is unknown, and says what to do.
        $arrivalSet = self::dateOrNull($it['arrival_date'] ?? null) !== null;
        foreach ((array) ($it['email_checks'] ?? []) as $c) {
            if (in_array($c['code'], ['year_missing', 'no_dates', 'date_past'], true) && $arrivalSet) {
                continue;   // a date has been settled since (typed in, or the text corrected and read again)
            }
            $g[] = ['code' => 'email_' . $c['code'], 'level' => $c['level'], 'label' => 'In the email: ' . $c['label']];
        }
        $dep = self::dateOrNull($it['departure_date'] ?? null);
        if ($arrivalSet && $dep !== null && $dep < (string) $it['arrival_date']) {
            $g[] = ['code' => 'span', 'level' => 'red', 'label' => 'Departure ' . date('j M Y', strtotime($dep)) . ' is before arrival ' . date('j M Y', strtotime((string) $it['arrival_date'])) . ' — correct the dates; the nights cannot be counted.'];
        }

        // A tour that arrives before the email asking for it was sent has the wrong year on
        // it — "17JAN" in a September email is next January. Every rate lookup runs against
        // the date, so a year out means every night reads "no published rate".
        $arrival  = self::dateOrNull($it['arrival_date'] ?? null);
        $received = ($rcv = self::dateTimeOrNull($it['source_received_at'] ?? null)) ? substr($rcv, 0, 10) : null;
        if ($arrival !== null) {
            if ($received !== null && $arrival < $received) {
                $g[] = ['code' => 'arrival_past', 'level' => 'red', 'label' => 'Arrival ' . date('j M Y', strtotime($arrival)) . ' is before the email was sent (' . date('j M Y', strtotime($received)) . '), so the year is probably ' . (date('Y', strtotime($arrival)) + 1) . '. Correct the arrival and departure dates — every hotel rate is looked up against them.'];
            } elseif ($received === null && $arrival < date('Y-m-d')) {
                $g[] = ['code' => 'arrival_past', 'level' => 'amber', 'label' => 'Arrival ' . date('j M Y', strtotime($arrival)) . ' is in the past. Check the year — every hotel rate is looked up against the dates.'];
            }
        }

        // The agent on the inquiry is on the ledger but nobody has marked it as an agent. Once
        // the marking has begun that is worth a look: either it is an agent and the master
        // should say so, or a ticketing customer was picked by mistake.
        if (($it['agent_marked'] ?? null) === false && $this->agentsMarked() > 0) {
            $g[] = ['code' => 'agent_unmarked', 'level' => 'amber', 'label' => trim((string) ($it['agent_name'] ?? '')) . ' (' . (string) $it['agent_acc'] . ') is on the customer list but is not marked as an agent. Mark it under Master Files → Consignees, or choose another agent.'];
        }

        foreach ($fields as $key => $f) {
            if (($f['state'] ?? '') === 'unverified') {
                $g[] = ['code' => $key, 'level' => 'amber', 'label' => str_replace('_', ' ', $key) . ' could not be traced to the email — check it before using it.'];
            }
            if (($f['state'] ?? '') === 'derived') {
                $g[] = ['code' => $key, 'level' => 'blue', 'label' => str_replace('_', ' ', $key) . ' was worked out, not stated: ' . ($f['basis'] ?? 'confirm it.')];
            }
        }

        $noMeal = $noHotel = $noDate = 0;
        $noMealNights = [];
        $fromBasis = [];   // meal plan => nights that took it from the email's tour-wide line
        $basisQuote = null;
        foreach (($it['days'] ?? []) as $d) {
            if (($d['meal_plan'] ?? null) === null) {
                $noMeal++;
                $noMealNights[] = (int) $d['day_no'];
            } elseif (($d['meal_source'] ?? null) === 'tour') {
                $fromBasis[(string) $d['meal_plan']][] = (int) $d['day_no'];
                $basisQuote = $basisQuote ?? ($d['meal_quote'] ?? null);
            }
            // Common on a night typed in before the dates are settled: the hotel is known
            // but there is no date to price it against. Saying so beats a grey pill.
            if (($d['day_date'] ?? null) === null && trim((string) ($d['hotel_name_raw'] ?? '')) !== '') {
                $noDate++;
            }
            if (($d['rate_state'] ?? '') === 'nothing_named') {
                $noHotel++;
            }
            if (in_array($d['rate_state'] ?? '', ['not_in_panel', 'no_supplier'], true)) {
                $g[] = ['code' => 'hotel_' . $d['id'], 'level' => 'red', 'label' => trim((string) $d['hotel_name_raw']) . ' cannot be priced yet — ' . ($d['rate_state'] === 'no_supplier' ? 'no supplier record.' : 'not in the panel.')];
            } elseif (($d['rate_state'] ?? '') === 'no_plan') {
                $g[] = ['code' => 'hotel_' . $d['id'], 'level' => 'red', 'label' => 'Published rate missing: ' . trim((string) $d['hotel_name_raw']) . ', night ' . (int) $d['day_no'] . ' — ' . ($d['rate_note'] ?? 'no plan covers that night.')];
            } elseif (($d['rate_state'] ?? '') === 'rate') {
                // the plan covers the night; now the room type and the meal plan have to meet a figure
                $types  = is_array($d['room_types'] ?? null) ? $d['room_types'] : (json_decode((string) ($d['room_types_json'] ?? ''), true) ?: []);
                $priced = array_values(array_filter($types, static fn ($t) => !empty($t['priced'])));
                $hotel  = trim((string) $d['hotel_name_raw']);
                $night  = 'night ' . (int) $d['day_no'];
                if (($d['room_types_json'] ?? null) === null) {
                    // checked before the room types and rates were copied beside a night: read it again
                    $g[] = ['code' => 'reread_' . $d['id'], 'level' => 'amber', 'label' => $hotel . ', ' . $night . ': the room types and published rate have not been read onto this night yet — press Check hotels again.'];
                } elseif (($d['room_type_id'] ?? null) === null) {
                    if (count($priced) > 1) {
                        $g[] = ['code' => 'roomtype_' . $d['id'], 'level' => 'red', 'label' => $hotel . ', ' . $night . ': choose the room type — the panel has ' . count($priced) . ' with a published rate.'];
                    } else {
                        $g[] = ['code' => 'roomtype_' . $d['id'], 'level' => 'red', 'label' => 'Published rate missing: ' . $hotel . ', ' . $night . ' — no room type of that hotel carries a published rate for the date.'];
                    }
                } elseif (($d['meal_plan'] ?? null) !== null && ($d['rate_dbl'] ?? null) === null && ($d['rate_sgl'] ?? null) === null && ($d['rate_tpl'] ?? null) === null && ($d['rate_unit'] ?? null) === null) {
                    $g[] = ['code' => 'rate_' . $d['id'], 'level' => 'red', 'label' => 'Published rate missing: ' . $hotel . ', ' . $night . ' — no ' . $d['meal_plan'] . ' rate for ' . ($d['room_type_name'] ?: 'that room type') . ' on ' . ($d['day_date'] ? date('j M Y', strtotime((string) $d['day_date'])) : 'that date') . '. Fix it in Hotel Rates; nothing is typed in here.'];
                }
                // The figure on the night is kept; the panel has since moved. Said here, decided by a person.
                $drift = is_array($d['rate_drift'] ?? null) ? $d['rate_drift'] : (json_decode((string) ($d['rate_drift_json'] ?? ''), true) ?: null);
                if ($drift) {
                    $since = !empty($d['rate_frozen_at']) ? ' (copied ' . date('j M Y', strtotime((string) $d['rate_frozen_at'])) . ')' : '';
                    $g[] = ['code' => 'drift_' . $d['id'], 'level' => 'amber', 'label' => !empty($drift['none'])
                        ? 'Rate changed in Hotel Rates: ' . $hotel . ', ' . $night . ' — the panel no longer has a published rate for this night' . ($drift['why'] ? ' (' . $drift['why'] . ')' : '') . '. The figure on the night' . $since . ' is kept as quoted; press Use the new rate only if the quotation is to change.'
                        : 'Rate changed in Hotel Rates: ' . $hotel . ', ' . $night . ' — the night keeps ' . self::figureText($d, $it) . $since . '; the panel now gives ' . self::figureText(['rate_sgl' => $drift['sgl'], 'rate_dbl' => $drift['dbl'], 'rate_tpl' => $drift['tpl'], 'rate_unit' => $drift['unit'], 'rate_currency' => $drift['currency'], 'day_no' => $d['day_no'], 'room_mix' => $d['room_mix'] ?? null], $it) . ($drift['plan_name'] ? ' (' . $drift['plan_name'] . ')' : '') . '. Press Use the new rate on the night to take it; otherwise the quoted figure stands.'];
                }
            }
        }

        // The room mix and the groups: what the hotel bill is multiplied by. Never derived
        // from the passenger count — ten people could be five doubles or ten singles.
        $mix = $it['rooms'] ?? self::roomMix($it, null);
        if (in_array($mix['source'], ['none', 'groups_empty'], true) && (($it['days'] ?? []) !== [])) {
            $g[] = ['code' => 'rooms_mix', 'level' => 'red', 'label' => $mix['source'] === 'groups_empty'
                ? 'No group has a room mix yet (singles / doubles / twins / triples), so no night can be costed.'
                : 'The room mix is not set (singles / doubles / twins / triples), so no night can be costed.'];
        }
        $groupsHere = (array) ($it['groups'] ?? []);
        if ($groupsHere !== []) {
            $sum = 0;
            $named = [];
            foreach (($it['passengers'] ?? []) as $p) {
                if (($p['group_id'] ?? null) !== null) {
                    $named[(int) $p['group_id']] = ($named[(int) $p['group_id']] ?? 0) + 1;
                }
            }
            foreach ($groupsHere as $grp) {
                $sum += (int) $grp['pax'];
                $one = self::roomMix(['groups' => [$grp]], null);
                if ((int) $grp['pax'] > 0 && $one['rooms'] > 0 && $one['sleeps'] !== (int) $grp['pax']) {
                    $g[] = ['code' => 'group_sleeps_' . $grp['id'], 'level' => 'amber', 'label' => $grp['name'] . ': the room mix sleeps ' . $one['sleeps'] . ' but the group is ' . (int) $grp['pax'] . '. Check it before quoting.'];
                }
                if ((int) $grp['pax'] > 0 && $one['rooms'] === 0) {
                    $g[] = ['code' => 'group_rooms_' . $grp['id'], 'level' => 'red', 'label' => $grp['name'] . ' has no room mix yet.'];
                }
                if (isset($named[(int) $grp['id']]) && $named[(int) $grp['id']] > (int) $grp['pax']) {
                    $g[] = ['code' => 'group_names_' . $grp['id'], 'level' => 'amber', 'label' => $grp['name'] . ' is ' . (int) $grp['pax'] . ' passenger(s) but ' . $named[(int) $grp['id']] . ' names are assigned to it.'];
                }
            }
            $paxAll = self::intOrNull($it['pax_total'] ?? null);
            if ($paxAll !== null && $sum !== $paxAll) {
                $g[] = ['code' => 'groups_pax', 'level' => 'red', 'label' => 'The groups add up to ' . $sum . ' passenger(s), but the inquiry is for ' . $paxAll . '. Every passenger has to be in a group.'];
            }
        } elseif ($mix['source'] === 'intake' && $mix['pax'] !== null && $mix['sleeps'] !== (int) $mix['pax']) {
            $g[] = ['code' => 'rooms_sleeps', 'level' => 'amber', 'label' => 'The room mix sleeps ' . $mix['sleeps'] . ' but the party is ' . (int) $mix['pax'] . '. Check it before quoting.'];
        }

        // Excursions: a copied rate with a quantity of nought costs nothing, which is the one thing it must not do quietly.
        foreach (($it['excursions'] ?? []) as $x) {
            if ((float) $x['qty'] <= 0) {
                $g[] = ['code' => 'exc_qty_' . $x['id'], 'level' => 'red', 'label' => $x['name'] . ' on day ' . (int) $x['day_no'] . ' has a quantity of nought — say how many go.'];
            }
        }
        if ($noMeal > 0) {
            $g[] = ['code' => 'meals', 'level' => 'amber', 'label' => 'Meal plan not stated for night' . ($noMeal === 1 ? ' ' : 's ') . self::nightList($noMealNights) . ' — pick RO, BB, HB or FB on the night to see its published rate; nothing is assumed.'];
        }
        // A meal plan the agent wrote once for the whole tour is on those nights by the
        // agent's word, not the night's own line: said here so nobody takes it for more.
        foreach ($fromBasis as $meal => $nights) {
            $g[] = ['code' => 'meal_basis', 'level' => 'blue', 'label' => 'Meal plan ' . $meal . ' on night' . (count($nights) === 1 ? ' ' : 's ') . self::nightList($nights) . ' comes from the email\'s own terms' . ($basisQuote ? ' (“' . $basisQuote . '”)' : '') . ', not from those nights\' lines.'];
        }
        if ($noHotel > 0) {
            $g[] = ['code' => 'hotels', 'level' => 'amber', 'label' => "No hotel named for {$noHotel} night(s)."];
        }
        if ($noDate > 0) {
            $g[] = ['code' => 'daydates', 'level' => 'amber', 'label' => "{$noDate} night(s) have a hotel but no date, so the rate cannot be checked yet."];
        }

        // Day services. A line the agent has declined is not a gap — it was decided.
        $offList = $noAmount = $masterNoRate = $notInMaster = 0;
        $noRate  = false;
        foreach (($it['services'] ?? []) as $s) {
            if (ServiceLookup::isFree($s)) {
                continue;   // declined, or a stop with nothing to pay: decided, not a gap
            }
            if (($s['match_state'] ?? '') === 'not_in_list') {
                $offList++;
            } elseif (($s['match_state'] ?? '') === 'not_in_master' && ($s['pay_mode'] ?? '') !== 'direct') {
                $notInMaster++;
            } elseif (($s['match_state'] ?? '') === 'rate_missing' && ($s['pay_mode'] ?? '') !== 'direct') {
                $masterNoRate++;
            } elseif (($s['amount_usd'] ?? null) === null) {
                $noAmount++;
            }
            if (($s['amount_usd'] ?? null) !== null && ($s['amount_lkr'] ?? null) === null) {
                $noRate = true;
            }
        }
        if ($offList > 0) {
            $g[] = ['code' => 'svc_off_list', 'level' => 'red', 'label' => "{$offList} day service(s) are not in the priced list — for each, mark No charge if nothing is paid, pick the priced entry, or add it under Master Files."];
        }
        if ($notInMaster > 0) {
            $g[] = ['code' => 'svc_not_in_master', 'level' => 'red', 'label' => "{$notInMaster} entrance(s) or safari(s) are not in the Place to Visit / Entrance Fee Master — held: pick the place for each, or add it to the master (with its rate, or as Free); never taken as free."];
        }
        if ($masterNoRate > 0) {
            $g[] = ['code' => 'svc_rate_missing', 'level' => 'red', 'label' => "{$masterNoRate} entrance(s) or safari(s) are in the Place to Visit / Entrance Fee Master with no rate for the day — the master needs the rate, or the place marked Free; nothing is guessed."];
        }
        if ($noAmount > 0) {
            $g[] = ['code' => 'svc_no_amount', 'level' => 'amber', 'label' => "{$noAmount} day service(s) are in the list but carry no amount — mark No charge if nothing is paid, or set the amount in the master."];
        }
        if ($noRate) {
            $g[] = ['code' => 'svc_no_rate', 'level' => 'amber', 'label' => 'No USD rate has been entered, so the service costs cannot be shown in rupees.'];
        }

        // Vehicle and crew, kept apart because they are costed apart.
        $pax = $fields['pax_total']['value'] ?? null;
        if (($it['vehicles'] ?? []) === [] && $pax !== null) {
            $g[] = ['code' => 'no_vehicle', 'level' => 'red', 'label' => 'No vehicle has been settled for ' . $pax . ' passenger(s) — the party may be larger than anything held.'];
        }
        foreach (($it['crew'] ?? []) as $c) {
            if ($c['bata'] === null || $c['bata'] === '') {
                $g[] = ['code' => 'crew_rate_' . $c['role_code'], 'level' => 'red', 'label' => $c['role_name'] . ' has no bata set, so it cannot be costed. Admin sets it once under Master Files → Crew Bata and every quotation picks it up.'];
            }
        }
        if (($it['crew'] ?? []) === []) {
            $g[] = ['code' => 'no_crew', 'level' => 'amber', 'label' => 'No driver or guide has been put on this inquiry yet.'];
        }

        foreach (($it['crew'] ?? []) as $c) {
            $std = ($c['bata'] === null || $c['bata'] === '') ? null : (float) $c['bata'];
            $own = ($c['person_bata'] === null || $c['person_bata'] === '') ? null : (float) $c['person_bata'];
            if ($std !== null && $own !== null && abs($std - $own) > 0.005) {
                $g[] = [
                    'code' => 'bata_differs_' . $c['role_code'],
                    'level' => 'blue',
                    'label' => trim((string) $c['person_name']) . ' is on ' . number_format($own) . ' a day on the ledger, not the '
                        . number_format($std) . ' the master holds for a ' . strtolower((string) $c['role_name']) . '. The master\'s figure is what is quoted; theirs is what the payment side pays.',
                ];
            }
        }

        // Accommodation: only ever a gap once somebody has SAID it is needed. An untouched
        // line is not a gap, it is a decision nobody has made yet — and the panel says that
        // rather than treating silence as "free".
        foreach (($it['accom'] ?? []) as $kind => $a) {
            if ((int) ($a['required'] ?? 0) !== 1) {
                continue;
            }
            if ((int) ($a['unknown_nights'] ?? 0) > 0) {
                $g[] = [
                    'code'  => 'accom_unknown_' . $kind,
                    'level' => 'red',
                    'label' => ucfirst($kind) . ' accommodation is required, but ' . (int) $a['unknown_nights']
                        . ' night(s) have nothing on file about what a room costs. They are NOT counted as free — price them or the quotation is short.',
                ];
            }
            if ($a['rate'] === null && (int) ($a['nights'] ?? 0) === 0) {
                $g[] = [
                    'code'  => 'accom_norate_' . $kind,
                    'level' => 'amber',
                    'label' => ucfirst($kind) . ' accommodation is required but no nightly figure could be found. Set one by hand.',
                ];
            }
        }

        // A rate the master has moved on from since this inquiry copied it. Said, not
        // applied: an open inquiry re-prices when somebody presses the button, a quotation
        // that has gone out never does.
        foreach (($it['vehicles'] ?? []) as $v) {
            if (($v['rate_by'] ?? '') === 'master' && ($v['master_now_id'] ?? null) !== null && (int) $v['master_now_id'] !== (int) ($v['rate_id'] ?? 0)) {
                $g[] = ['code' => 'rate_moved_' . $v['id'], 'level' => 'blue',
                        'label' => 'The Transport Rates master now says ' . number_format((float) $v['master_now'], 2) . ' per km for a ' . $v['type']
                            . '; this inquiry was priced at ' . number_format((float) $v['km_rate'], 2) . ' and keeps it unless you re-price.'];
            }
            if (($v['rate_by'] ?? '') === 'manual') {
                $g[] = ['code' => 'rate_special_' . $v['id'], 'level' => 'blue',
                        'label' => 'A special rate of ' . number_format((float) $v['km_rate'], 2) . ' per km is on the ' . $v['type']
                            . ($v['master_rate'] !== null ? ' (master rate ' . number_format((float) $v['master_rate'], 2) . ')' : '') . ', set by ' . ($v['rate_set_by'] ?: 'somebody') . '.'];
            }
        }

        // The driving. A leg with no distance on it is the dangerous one: it costs nothing,
        // so a quotation built over it is short by exactly the journey nobody priced.
        $mil = $it['mileage'] ?? null;
        if ($mil !== null && (int) $mil['legs'] > 0) {
            if ((int) $mil['unpriced'] > 0) {
                $g[] = ['code' => 'km_unpriced', 'level' => 'red',
                        'label' => (int) $mil['unpriced'] . ' leg(s) have no distance — the company has never driven them, so they are costing nothing. Type the kilometres in.'];
            }
            if ((int) $mil['thin'] > 0) {
                $g[] = ['code' => 'km_thin', 'level' => 'amber',
                        'label' => (int) $mil['thin'] . ' leg(s) rest on only one or two past journeys. Worth an eye before quoting.'];
            }
            // Google's figure is what the quotation prefers; where it is missing the driven
            // record stands in, and the checklist says so — once for a missing key, per leg otherwise.
            if ($this->hasTwoFigures()) {
                $hasKey  = $this->google()->hasKey();
                $noKey   = (int) ($mil['no_key'] ?? 0) + (!$hasKey ? (int) ($mil['google_unasked'] ?? 0) : 0);
                if (!$hasKey && $noKey > 0) {
                    $g[] = ['code' => 'km_google_key', 'level' => 'amber',
                            'label' => 'No Google Maps key is set (GOOGLE_MAPS_API_KEY in .env), so ' . $noKey . ' leg(s) are quoted from past tours instead of Google\'s road distance. Add the key, then press Ask Google on the mileage.'];
                } elseif ($hasKey && (int) ($mil['google_missing'] ?? 0) > 0) {
                    $g[] = ['code' => 'km_google_missing', 'level' => 'amber',
                            'label' => 'Google has no figure for ' . (int) $mil['google_missing'] . ' leg(s) — see the Google column on the mileage; ' . ($this->quoteSource() === 'google' ? 'those are quoted from past tours where there is a record, or need the kilometres typed in.' : 'the driven record is quoted by the setting on Mileage Allowances.')];
                }
                if ($hasKey && (int) ($mil['google_unasked'] ?? 0) > 0) {
                    $g[] = ['code' => 'km_google_unasked', 'level' => 'amber',
                            'label' => 'Google has not been asked about ' . (int) $mil['google_unasked'] . ' leg(s) yet (they were worked out before Google was part of the mileage). Press Ask Google on the mileage to put its figure beside each one.'];
                }
            }
            // the office gives the driver extra for site visits and city tours; a leg with none
            // is usually a town the Extra Kilometers master has no figure for
            if ((int) ($mil['extra_none'] ?? 0) > 0) {
                $towns = [];
                foreach (($it['sectors'] ?? []) as $s) {
                    if (empty($s['removed_at']) && self::isDrivenLeg((string) $s['kind'], $s['from_place'] ?? null, $s['to_place'] ?? null) && $s['extra_km'] === null) {
                        $t = MileageLookup::townOf((string) $s['to_place']);
                        if ($t !== '' && !in_array($t, $towns, true)) {
                            $towns[] = $t;
                        }
                    }
                }
                $g[] = ['code' => 'km_extra_none', 'level' => 'amber',
                        'label' => (int) $mil['extra_none'] . ' leg(s) carry no extra running for site visits or a city tour'
                            . ($towns !== [] ? ' — nothing on file for ' . implode(', ', array_slice($towns, 0, 4)) . (count($towns) > 4 ? ' and others' : '') : '')
                            . '. Type it in the Extra column, or give the town a figure under Master Files → Extra Kilometers.'];
            }
            foreach (($mil['by_vehicle'] ?? []) as $bv) {
                if ($bv['km_rate'] === null && ($it['vehicles'] ?? []) !== [] && (int) $bv['legs'] > 0) {
                    $g[] = ['code' => 'km_norate_' . $bv['vehicle_seq'], 'level' => 'red',
                            'label' => ($bv['label'] ?: 'The vehicle') . ' (' . ($bv['type'] ?: 'size not set') . ') has no rate per kilometre yet, so ' . self::num((float) $bv['charged_km']) . ' km cannot be costed. Set it under Master Files → Transport Rates.'];
                }
            }
        } elseif (($it['days'] ?? []) !== []) {
            $g[] = ['code' => 'km_none', 'level' => 'amber', 'label' => 'The itinerary has not been broken into legs yet, so there is no mileage to cost.'];
        }

        foreach (($it['requests'] ?? []) as $r) {
            if (($r['kind'] ?? '') === 'question' && ($r['status'] ?? '') === 'open') {
                $g[] = ['code' => 'q_' . $r['id'], 'level' => 'amber', 'label' => 'Unanswered: ' . $r['title']];
            }
        }
        return $g;
    }

    // =====================================================================

    /**
     * The value only when it is safe for the rest of the system to read.
     *
     * An 'unverified' value — one the model produced with a quote that is not in the
     * email — stays in fields_json so the panel can show it and a person can confirm
     * it, but it is never written to the columns other screens read. Nothing that
     * could not be traced back to the agent's own words leaks out of this panel.
     */
    private static function v(array $fields, string $key)
    {
        $f = $fields[$key] ?? null;
        if (!is_array($f)) {
            return null;
        }
        $state = $f['state'] ?? 'missing';
        if ($state === 'unverified' && ($f['origin'] ?? '') !== 'human') {
            return null;
        }
        $value = $f['value'] ?? null;
        return is_scalar($value) ? $value : null;
    }

    /** The panel returns "nearest plan" as either a date or a small record about it. */
    private static function nearestDate($n): ?string
    {
        if (is_string($n) && trim($n) !== '') {
            return trim($n);
        }
        if (is_array($n)) {
            foreach (['date', 'valid_to', 'valid_from', 'date_to', 'date_from'] as $k) {
                if (!empty($n[$k]) && is_string($n[$k])) {
                    return $n[$k];
                }
            }
        }
        return null;
    }

    /**
     * The rate on a night as one figure per room type asked for — "USD 320 (DBL)", or
     * "USD 320 ×2 (DBL), 283 (SGL)" — never the whole sgl / dbl / tpl row. With no room
     * mix set there is nothing to pick, and it says so.
     */
    public static function figureText(array $d, array $it): string
    {
        $cur  = (string) ($d['rate_currency'] ?? 'USD') ?: 'USD';
        $n    = static fn ($v) => $v === null || $v === '' ? null : (float) $v;
        $unit = $n($d['rate_unit'] ?? null);
        $fmt  = static fn (float $v) => rtrim(rtrim(number_format($v, 2, '.', ','), '0'), '.');
        if ($unit !== null) {
            return $cur . ' ' . $fmt($unit) . ' per unit';
        }
        $mix = is_array($d['room_mix'] ?? null) ? $d['room_mix'] : self::roomMix($it, isset($d['day_no']) ? (int) $d['day_no'] : null);
        $parts = [];
        foreach ([['sgl', 'SGL', 'rate_sgl'], ['dbl', 'DBL', 'rate_dbl'], ['twn', 'TWN', 'rate_dbl'], ['tpl', 'TPL', 'rate_tpl']] as [$k, $label, $col]) {
            $count = (int) ($mix[$k] ?? 0);
            if ($count <= 0) {
                continue;
            }
            $v = $n($d[$col] ?? null);
            $parts[] = ($v === null ? 'no ' . $label . ' rate' : $fmt($v)) . ($count > 1 ? ' ×' . $count : '') . ' (' . $label . ')';
        }
        if (!$parts) {
            return 'a rate the rooms are not set for (set SGL / DBL / TWN / TPL to see it)';
        }
        return $cur . ' ' . implode(', ', $parts);
    }

    /** The same hotel as the agent typed it, give or take spacing and capitals. */
    private static function sameHotelName(string $a, string $b): bool
    {
        $n = static fn (string $s) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $s)));
        return $n($a) !== '' && $n($a) === $n($b);
    }

    /** "3, 4 and 7" — nights named the way a person would say them. */
    private static function nightList(array $nights): string
    {
        $nights = array_values(array_unique(array_map('intval', $nights)));
        sort($nights);
        if (count($nights) <= 1) {
            return (string) ($nights[0] ?? '');
        }
        $last = array_pop($nights);
        return implode(', ', $nights) . ' and ' . $last;
    }

    private static function cut($v, int $len): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        return $s === '' ? null : mb_substr($s, 0, $len);
    }

    private static function intOrNull($v): ?int
    {
        return ($v === null || $v === '' || !is_numeric($v)) ? null : (int) $v;
    }

    private static function dateOrNull($v): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
    }

    private static function dateTimeOrNull($v): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        if ($s === '') {
            return null;
        }
        $t = strtotime($s);
        return $t ? date('Y-m-d H:i:s', $t) : null;
    }
}
