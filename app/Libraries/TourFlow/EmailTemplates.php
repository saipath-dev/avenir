<?php

namespace App\Libraries\TourFlow;

/**
 * The ready-made emails (schema 34).
 *
 * A template is a name, a category (which send window offers it), a subject and a message.
 * Words in braces — {agent}, {tour_name}, {dates}, {hotel} … — are filled in from the inquiry
 * or the tour the window is on; a word the window cannot fill is left blank, never invented.
 * Every send window lists the templates of its own category and the general ones; choosing one
 * fills the subject and the message, which staff then edit as they like. Management keeps the
 * list under Tour Desk Admin → Settings: a template is archived, never deleted, so a send that
 * used one keeps its trail. The installer seeds the standard set once, when the table is empty.
 */
final class EmailTemplates
{
    public const CATEGORIES = [
        'quotation'        => 'Quotation',
        'invoice'          => 'Invoice',
        'hotel_request'    => 'Hotel booking request',
        'hotel_voucher'    => 'Hotel voucher',
        // schema 40 (tf-meals-r1): the booking voucher that goes to a restaurant of the Meal Master when a tour is confirmed
        'restaurant_voucher' => 'Restaurant booking voucher',
        // tf-mailtpl-r1 (4 Nov 2026): the request that goes BEFORE the voucher, and the two supplier
        // groups that had no wording of their own — transport (driver, guide, vehicle) and activities
        'restaurant_request' => 'Restaurant reservation request',
        'transport_request'  => 'Driver, guide & transport request',
        'activity_request'   => 'Activity & other supplier request',
        // tf-mailtpl-r2 (4 Nov 2026, Shantha): the workflow has TWO stages and they must not be confused.
        // Acceptance starts the reservations; the final confirmation goes only after every required desk has
        // finished and the handling agent has pressed Final confirm on Confirmed Tours.
        'confirmation'       => 'Tour acceptance — reservations starting',
        'final_confirmation' => 'Final confirmation — all services secured',
        'payment_reminder' => 'Payment reminder',
        'follow_up'        => 'Follow-up',
        'general'          => 'General',
    ];

    /** The words a template may carry, and what each is. */
    public const WORDS = [
        'agent'       => "the agent's name (the person or company the email goes to)",
        'agent_name'  => "the agent's name",
        'inquiry_no'  => 'the inquiry number',
        'ref'         => 'the quotation reference (inquiry number and version)',
        'version'     => 'the quotation version number',
        'tour_no'     => 'the tour number (once confirmed)',
        'tour_name'   => 'the tour name',
        'dates'       => 'the arrival and departure dates',
        'arrival'     => 'the arrival date',
        'departure'   => 'the departure date',
        'nights'      => 'the number of nights',
        'pax'         => 'the party size',
        'max_pax'     => 'the maximum number of travellers the quotation is for',
        'price'       => 'the quotation price (USD)',
        'per_person'  => 'the price per person (USD)',
        'valid_until' => 'the date the quotation is valid until',
        'hotel'       => 'the hotel (hotel letters)',
        'check_in'    => 'the check-in date (hotel letters)',
        'check_out'   => 'the check-out date (hotel letters)',
        'rooms'       => 'the rooms (hotel letters)',
        'meal_plan'   => 'the meal plan (hotel letters)',
        'restaurant'  => 'the restaurant (restaurant booking vouchers)',
        'meal'        => 'Lunch or Dinner (restaurant booking vouchers)',
        'meal_date'   => 'the day of the meal (restaurant booking vouchers)',
        'meal_time'   => 'the time of the meal (restaurant booking vouchers)',
        'adults'      => 'the adults the meal is for (restaurant booking vouchers)',
        'children'    => 'the children the meal is for (restaurant booking vouchers)',
        'voucher_no'  => 'the voucher number (restaurant booking vouchers)',
        'amount'      => 'the amount in LKR (restaurant booking vouchers)',
        'hotels'      => 'the hotels on the tour, one to a line',
        'services'    => 'the activities and services on the tour, one to a line',
        'driver'      => 'the driver engaged (transport requests)',
        'guide'       => 'the guide engaged (transport requests)',
        'vehicle'     => 'the vehicle and its registration (transport requests)',
        'role'        => 'what is asked for: driver, guide or vehicle (transport requests)',
        'service_days'=> 'the days the driver or guide serves (transport requests)',
        'supplier'    => 'the supplier the request goes to',
        'activity'    => 'the activity or service asked for (activity requests)',
        'activity_date' => 'the day of the activity (activity requests)',
        'activity_time' => 'the time of the activity (activity requests)',
        'location'    => 'where the service is (activity requests)',
        'me'          => 'the sender, the signed-in person',
        'company'     => 'the company name',
        'today'       => "today's date",
    ];

    /** The standard set, written once by the installer when the table is empty. */
    public const SEED = [
        ['Quotation – Standard', 'quotation', 1, 'Quotation {ref} — {tour_name}, {dates}',
            "Dear {agent},\n\nThank you for your enquiry. Please find attached our quotation {ref} for {tour_name}, {dates}, for up to {max_pax} travellers ({pax} as enquired). The price per person for each party size is on the quotation, valid until {valid_until}.\n\nWe would be glad to adjust the programme or the hotels to suit your clients; just let us know.\n\nKind regards,\n{me}\n{company}"],
        ['Quotation – Follow-up', 'quotation', 0, 'Following up: quotation {ref} — {tour_name}',
            "Dear {agent},\n\nWe hope this finds you well. We are following up on our quotation {ref} for {tour_name}, {dates}, sent earlier. The offer is valid until {valid_until}; should your clients wish to go ahead, we will be glad to hold the hotels and confirm the arrangements.\n\nIf anything in the programme or the price needs a change, please tell us and we will revise it.\n\nKind regards,\n{me}\n{company}"],
        ['Invoice – Standard', 'invoice', 1, 'Invoice — {tour_name}, {dates}',
            "Dear {agent},\n\nPlease find attached our invoice for {tour_name}, {dates}. Payment is due as per the terms on the invoice; kindly quote the invoice number with your remittance.\n\nThank you for your business.\n\nKind regards,\n{me}\n{company}"],
        ['Payment Reminder', 'payment_reminder', 1, 'Payment reminder — {tour_name}, {dates}',
            "Dear {agent},\n\nA gentle reminder that the payment for {tour_name}, {dates}, is now due. If it has already been made, please disregard this note and accept our thanks; otherwise we would be grateful for your remittance at your earliest convenience so the arrangements remain in place.\n\nKind regards,\n{me}\n{company}"],
        // the two hotel letters: the opening only — the booking details, the closing and the signature follow on the letter itself
        ['Hotel Booking Request', 'hotel_request', 1, 'Booking request — {tour_no} — {hotel}, {check_in} to {check_out}',
            "Dear Reservations Team,\n\nGreetings from {company}. Kindly reserve the accommodation below for {tour_no} at {hotel} — {check_in} to {check_out}, {nights} night(s), {rooms}, {pax} pax, {meal_plan} — and send us your confirmation number at your earliest convenience."],
        ['Hotel Voucher', 'hotel_voucher', 1, 'Hotel voucher — {tour_no} — {hotel}, {check_in} to {check_out}',
            "Dear Reservations Team,\n\nPlease accept this voucher for the accommodation below, confirmed by your reservations department under the reference shown — {tour_no}, {check_in} to {check_out}. Kindly settle the account with our office as agreed."],
        // the restaurant booking voucher (schema 40): the opening only — the reservation and the contact follow on the voucher
        ['Restaurant Booking Voucher', 'restaurant_voucher', 1, 'Booking voucher {voucher_no} — {tour_no} — {meal} on {meal_date}',
            "Dear {restaurant},\n\nGreetings from {company}. Please find below our booking voucher {voucher_no} for {meal} on {meal_date} at {meal_time} — {adults} adult(s) and {children} child(ren), tour {tour_no}. Kindly confirm the reservation by return email.\n\nThe amount of LKR {amount} is paid by our office; the guests are not to be charged for the meal."],
        // tf-mailtpl-r1: the restaurant reservation request, before the voucher
        ['Restaurant Reservation Request', 'restaurant_request', 1, 'Reservation request — {tour_no} — {meal} on {meal_date}',
            "Dear {restaurant},\n\nGreetings from {company}. Kindly reserve a table for {meal} on {meal_date} at {meal_time} for {adults} adult(s) and {children} child(ren), travelling with us under tour {tour_no} ({tour_name}, {dates}).\n\nPlease confirm the reservation by return email. Our booking voucher with the agreed amount follows once you confirm.\n\nKind regards,\n{me}\n{company}"],
        // tf-mailtpl-r1: the driver, guide and vehicle request
        ['Transport Booking Request', 'transport_request', 1, 'Booking request — {tour_no} — {role}, {dates}',
            "Dear {supplier},\n\nGreetings from {company}. We request your {role} for the confirmed tour below.\n\nTour: {tour_no} — {tour_name}\nAgent: {agent}\nDates: {dates}\nPassengers: {pax}\nVehicle: {vehicle}\nService days: {service_days}\n\nPlease confirm your availability by reply. This is a request, not a confirmation: the booking is held as pending on our side until your confirmation is recorded by our Transport desk.\n\nKind regards,\n{me}\n{company}"],
        // tf-mailtpl-r1: entrance, excursion, safari and every other supplier of a service on the day
        ['Activity Booking Request', 'activity_request', 1, 'Booking request — {tour_no} — {activity} on {activity_date}',
            "Dear {supplier},\n\nGreetings from {company}. Kindly reserve {activity} on {activity_date} at {activity_time}, {location}, for {pax} passenger(s) travelling with us under tour {tour_no} ({tour_name}, {dates}).\n\nPlease confirm the reservation by return email, with your reference. The account is settled with our office as agreed; the guests are not to be charged.\n\nKind regards,\n{me}\n{company}"],
        // stage one: the agent has accepted and we are now making the reservations. Nothing is claimed as secured.
        ['Tour Acceptance — reservations starting', 'confirmation', 1, 'Accepted: {tour_no} — {tour_name}, {dates}',
            "Dear {agent},\n\nThank you for your acceptance of {tour_name}, {dates}, for {pax} passenger(s). The tour is now on our books under the number {tour_no}.\n\nOur reservations teams have begun securing the arrangements, including:\n{hotels}\n\nThese are being requested now and are not yet secured. We will send you our final confirmation as soon as every service is held, and the documents follow nearer the date.\n\nThank you for choosing {company}.\n\nKind regards,\n{me}"],
        // stage two: every required desk has finished and the handling agent has pressed Final confirm
        ['Final Confirmation — services secured', 'final_confirmation', 1, 'Confirmed: {tour_no} — {tour_name}, {dates}',
            "Dear {agent},\n\nWe are pleased to confirm that all services for {tour_name}, {dates}, tour number {tour_no}, for {pax} passenger(s), are now secured.\n\nHotels:\n{hotels}\n\nEvery service shown on the attached itinerary is confirmed with our suppliers. The final documents follow nearer the date.\n\nThank you for choosing {company}.\n\nKind regards,\n{me}"],
        ['Follow-up', 'follow_up', 1, 'Following up — {tour_name}, {dates}',
            "Dear {agent},\n\nWe hope this finds you well. We are following up on {tour_name}, {dates}, under our reference {ref}.\n\nPlease let us know whether anything further is needed from our side; we will be glad to help.\n\nKind regards,\n{me}\n{company}"],
        ['General', 'general', 1, '{tour_name} — {company}',
            "Dear {agent},\n\n\n\nKind regards,\n{me}\n{company}"],
    ];

    private $db;
    private ?bool $available = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function available(): bool
    {
        if ($this->available === null) {
            try {
                $this->available = (bool) $this->db->query("SHOW TABLES LIKE 'tf_email_template'")->getResultArray();
            } catch (\Throwable $e) {
                $this->available = false;
            }
        }
        return $this->available;
    }

    /** The standard set, once: nothing is written when the table already holds a template. */
    public function seed(string $user = 'installer'): int
    {
        if (!$this->available()) {
            return 0;
        }
        $n = (int) ($this->db->query('SELECT COUNT(*) AS n FROM tf_email_template')->getRowArray()['n'] ?? 0);
        if ($n > 0) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $i = 0;
        foreach (self::SEED as [$name, $cat, $def, $subject, $message]) {
            $this->db->query('INSERT INTO tf_email_template (name, category, subject, message, is_default, sort_order, created_at, created_by) VALUES (?,?,?,?,?,?,?,?)', [$name, $cat, $subject, $message, $def, ++$i * 10, $now, $user]);
        }
        return $i;
    }

    /**
     * A category added after the table was first seeded (schema 40: restaurant_voucher) gets its standard template once,
     * when the table holds none of that category, live or archived. Nothing else is written or changed.
     */
    public function seedMissing(string $user = 'installer'): int
    {
        if (!$this->available()) {
            return 0;
        }
        $have = array_flip(array_column($this->db->query('SELECT DISTINCT category FROM tf_email_template')->getResultArray(), 'category'));
        $now = date('Y-m-d H:i:s');
        $n = 0;
        foreach (self::SEED as $i => [$name, $cat, $def, $subject, $message]) {
            // tf-mailtpl-r1: any category added after the first seeding — restaurant_voucher, and the
            // restaurant request, transport and activity groups — gets its standard wording once.
            if (isset($have[$cat])) {
                continue;
            }
            $this->db->query('INSERT INTO tf_email_template (name, category, subject, message, is_default, sort_order, created_at, created_by) VALUES (?,?,?,?,?,?,?,?)', [$name, $cat, $subject, $message, $def, ($i + 1) * 10, $now, $user]);
            $have[$cat] = true;
            $n++;
        }
        return $n;
    }

    /** @return array<int, array<string, mixed>> every template, the live ones first, then the archived */
    public function all(): array
    {
        if (!$this->available()) {
            return [];
        }
        return $this->db->query('SELECT * FROM tf_email_template ORDER BY archived_at IS NOT NULL, FIELD(category, ' . implode(',', array_fill(0, count(self::CATEGORIES), '?')) . '), sort_order, name', array_keys(self::CATEGORIES))->getResultArray();
    }

    public function get(int $id): ?array
    {
        if (!$this->available()) {
            return null;
        }
        return $this->db->query('SELECT * FROM tf_email_template WHERE id = ?', [$id])->getRowArray() ?: null;
    }

    /**
     * What a send window offers: the live templates of its category (the default first), then the general ones.
     * @return array<int, array<string, mixed>>
     */
    public function forWindow(string $category): array
    {
        if (!$this->available()) {
            return [];
        }
        return $this->db->query('SELECT id, name, category, subject, message, is_default FROM tf_email_template WHERE archived_at IS NULL AND category IN (?, ?) ORDER BY category = ? DESC, is_default DESC, sort_order, name', [$category, 'general', $category])->getResultArray();
    }

    /** The template a window starts with: the category's default, else its first, else the general default. */
    public static function starting(array $list, string $category): ?array
    {
        foreach ($list as $t) {
            if ($t['category'] === $category && (int) $t['is_default'] === 1) {
                return $t;
            }
        }
        foreach ($list as $t) {
            if ($t['category'] === $category) {
                return $t;
            }
        }
        return $list[0] ?? null;
    }

    /** The subject and the message with the window's words filled in; a word the window has no value for is left blank. */
    public static function fill(string $text, array $vars): string
    {
        $out = (string) preg_replace_callback('/\{([a-z_]+)\}/', static function ($m) use ($vars) {
            if (!array_key_exists($m[1], self::WORDS)) {
                return $m[0];   // not one of ours: left as typed
            }
            return (string) ($vars[$m[1]] ?? '');
        }, $text);
        return self::tidy($out);
    }

    /**
     * tf-supmail-r2 (Shantha, 4 Nov 2026): the words a supplier cannot act without.
     *
     * A letter missing one of these is HELD: the supplier would not know what to hold, for whom, or when,
     * so sending it would be worse than not sending it. Everything else — the tour's name, the agent, the
     * place — is still named as missing on the screen, but it does not stop a letter going: a desk should
     * not be unable to book a table because a tour has no name.
     */
    public const MUST_HAVE = [
        'activity_time', 'meal_time',          // the hour: without it nothing is actually held
        'activity_date', 'meal_date', 'dates', // the day
        'activity', 'restaurant', 'supplier',  // what is being reserved, and from whom
        'pax', 'adults', 'children',           // for how many
        'tour_no',                             // our own reference, so their reply can be filed
    ];

    /** Of the words this text could not fill, the ones that must stop it going out. */
    public static function missingRequired(string $text, array $vars): array
    {
        $out = [];
        if (preg_match_all('/\{([a-z_]+)\}/', $text, $m)) {
            foreach (array_unique($m[1]) as $w) {
                if (in_array($w, self::MUST_HAVE, true) && array_key_exists($w, self::WORDS)
                    && trim((string) ($vars[$w] ?? '')) === '') {
                    $out[] = self::WORDS[$w];
                }
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * tf-supmail-r1: what a blank word leaves behind. "at {time}, {location}, for" with neither on the record
     * reads "at , , for"; nobody writes that to a supplier. This takes out the stranded joining words and the
     * doubled punctuation ONLY — never a word of the sentence itself, and the missing-information check still
     * names what was not filled, so nothing is quietly hidden.
     */
    public static function tidy(string $text): string
    {
        $out = $text;
        $out = (string) preg_replace('/\b(?:at|on|in|from|to)\s*(?=,)/iu', '', $out);   // a joining word with nothing after it
        $out = (string) preg_replace('/,(?:[ \t]*,)+/u', ',', $out);                      // repeated commas
        $out = (string) preg_replace('/[ \t]+,/u', ',', $out);                            // a space before a comma
        $out = (string) preg_replace('/,[ \t]*([.;:!?])/u', '$1', $out);                  // a comma against a full stop
        $out = (string) preg_replace('/\(\s*,\s*/u', '(', $out);                          // a bracket opening on a comma
        $out = (string) preg_replace('/\s*,\s*\)/u', ')', $out);                          // a bracket closing on a comma
        $out = (string) preg_replace('/\(\s*\)/u', '', $out);                            // an empty bracket
        $out = (string) preg_replace('/[ \t]{2,}/u', ' ', $out);                          // doubled spaces
        $out = (string) preg_replace('/[ \t]+$/mu', '', $out);                            // trailing space on a line
        return $out;
    }

    /**
     * tf-mailtpl-r1: the words this text asks for that the window has no value for — named plainly so the
     * screen can say what is missing instead of sending a sentence with a hole in it.
     * @return array<int, string> e.g. ["the agent's name", 'the arrival and departure dates']
     */
    public static function missing(string $text, array $vars): array
    {
        $out = [];
        if (preg_match_all('/\{([a-z_]+)\}/', $text, $m)) {
            foreach (array_unique($m[1]) as $w) {
                if (!array_key_exists($w, self::WORDS)) {
                    continue;
                }
                if (trim((string) ($vars[$w] ?? '')) === '') {
                    $out[] = self::WORDS[$w];
                }
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * tf-mailtpl-r1: a message that is only a greeting and a signature says nothing. True when every line
     * left after the greeting, the signature and the company lines is blank.
     */
    public static function saysNothing(string $body, string $signature = ''): bool
    {
        $text = str_replace("\r", '', $body);
        if ($signature !== '') {
            $text = str_replace(str_replace("\r", '', $signature), '', $text);
        }
        $keep = [];
        foreach (explode("\n", $text) as $line) {
            $l = trim($line);
            if ($l === '') {
                continue;
            }
            // a greeting, a sign-off, the company's own lines and its contact lines are not the message
            if (preg_match('/^(dear\b|hi\b|hello\b|good (morning|afternoon|evening)\b)/i', $l)
                || preg_match('/^(kind regards|best regards|regards|yours (sincerely|faithfully)|thanks|thank you|sincerely)[,.]?$/i', $l)
                || preg_match('/^(avenir holidays|avenir holidays \(pvt\) ltd)/i', $l)
                || preg_match('/^(no\.|tel\b|fax\b|e-?mail\b|web\b|www\.)/i', $l)
                || preg_match('/@/', $l)) {
                continue;
            }
            $keep[] = $l;
        }
        return $keep === [];
    }

    /**
     * tf-mailtpl-r2: the message without its own closing sign-off.
     *
     * A send window that adds the company signature itself (Communications does) would otherwise sign the
     * letter twice: once from the template's own "Kind regards, …" and once from the signature underneath.
     * This takes the closing lines off the END only — the greeting, the body and anything in the middle are
     * untouched, and the SAVED template is not changed, only the copy this one window starts with.
     */
    public static function withoutSignOff(string $text): string
    {
        $lines = explode("\n", rtrim(str_replace("\r", '', $text)));
        $cut = count($lines);
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $l = trim($lines[$i]);
            if ($l === '') {
                continue;
            }
            // the company's own closing lines, its contact lines, and the sign-off itself
            if (preg_match('/^(kind regards|best regards|warm regards|regards|yours (sincerely|faithfully)|sincerely|thanks|thank you)[,.]?$/i', $l)) {
                $cut = $i;
                break;
            }
            if (preg_match('/^(no\.|tel\b|fax\b|e-?mail\b|web\b|www\.)/i', $l) || preg_match('/@/', $l) || mb_strlen($l) < 60) {
                continue;   // a name, a company line, an address line: keep looking upwards for the sign-off
            }
            break;          // a real sentence: there is no sign-off block to take off
        }
        return rtrim(implode("\n", array_slice($lines, 0, $cut)));
    }

    /** The window's list with every template filled in, ready for the screen's selector. */
    public static function filled(array $list, array $vars): array
    {
        $out = [];
        foreach ($list as $t) {
            $out[] = ['id' => (int) $t['id'], 'name' => (string) $t['name'], 'category' => (string) $t['category'], 'category_label' => self::CATEGORIES[$t['category']] ?? $t['category'],
                'is_default' => (int) $t['is_default'] === 1, 'subject' => trim(self::fill((string) $t['subject'], $vars)), 'message' => self::fill((string) $t['message'], $vars),
                // tf-mailtpl-r1: what this template asks for and the window could not supply
                'missing' => self::missing((string) $t['subject'] . ' ' . (string) $t['message'], $vars)];
        }
        return $out;
    }

    /**
     * Create (no id) or change a template. Management only; the caller checks the privilege.
     * @return array{template: array, changes: array}
     */
    public function save(array $t, string $user): array
    {
        if (!$this->available()) {
            throw new \RuntimeException('This install has no email templates yet: press Install now on Tour Inquiry first.');
        }
        $id       = (int) ($t['id'] ?? 0);
        $name     = mb_substr(trim((string) ($t['name'] ?? '')), 0, 120);
        $category = trim((string) ($t['category'] ?? ''));
        $subject  = mb_substr(trim(str_replace(["\r", "\n"], ' ', (string) ($t['subject'] ?? ''))), 0, 255);
        $message  = mb_substr(trim(str_replace("\r", '', (string) ($t['message'] ?? ''))), 0, 8000);
        $default  = !empty($t['is_default']) ? 1 : 0;
        if ($name === '') {
            throw new \InvalidArgumentException('Give the template a name (e.g. Quotation – Standard).');
        }
        if (!isset(self::CATEGORIES[$category])) {
            throw new \InvalidArgumentException('The category is one of: ' . implode(', ', self::CATEGORIES) . '.');
        }
        if ($subject === '') {
            throw new \InvalidArgumentException('The template needs a default subject.');
        }
        if ($message === '') {
            throw new \InvalidArgumentException('The template needs a default message.');
        }
        foreach (['subject' => $subject, 'message' => $message] as $k => $v) {
            if (preg_match_all('/\{([a-z_]+)\}/', $v, $mm)) {
                $bad = array_values(array_diff(array_unique($mm[1]), array_keys(self::WORDS)));
                if ($bad) {
                    throw new \InvalidArgumentException('The ' . $k . ' carries a word that is not filled in: {' . implode('}, {', $bad) . '}. The words are ' . implode(', ', array_map(static fn ($w) => '{' . $w . '}', array_keys(self::WORDS))) . '.');
                }
            }
        }
        $dup = $this->db->query('SELECT id FROM tf_email_template WHERE name = ? AND archived_at IS NULL AND id <> ?', [$name, $id])->getRowArray();
        if ($dup) {
            throw new \InvalidArgumentException('There is already a template called "' . $name . '". Give this one another name.');
        }
        $now = date('Y-m-d H:i:s');
        $was = $id ? $this->get($id) : null;
        if ($id && !$was) {
            throw new \RuntimeException('No such template.');
        }
        if ($default) {
            // one default per category among the live templates
            $this->db->query('UPDATE tf_email_template SET is_default = 0 WHERE category = ? AND id <> ?', [$category, $id]);
        }
        if ($was) {
            $this->db->query('UPDATE tf_email_template SET name = ?, category = ?, subject = ?, message = ?, is_default = ?, updated_at = ?, updated_by = ? WHERE id = ?', [$name, $category, $subject, $message, $default, $now, mb_substr($user, 0, 100), $id]);
        } else {
            $sort = (int) ($this->db->query('SELECT COALESCE(MAX(sort_order), 0) + 10 AS s FROM tf_email_template WHERE category = ?', [$category])->getRowArray()['s'] ?? 10);
            $this->db->query('INSERT INTO tf_email_template (name, category, subject, message, is_default, sort_order, created_at, created_by) VALUES (?,?,?,?,?,?,?,?)', [$name, $category, $subject, $message, $default, $sort, $now, mb_substr($user, 0, 100)]);
            $id = (int) $this->db->insertID();
        }
        $now_t = $this->get($id);
        $changes = [];
        foreach (['name' => 'name', 'category' => 'category', 'subject' => 'subject', 'message' => 'message', 'is_default' => 'default'] as $k => $label) {
            $from = $was[$k] ?? null;
            $to   = $now_t[$k] ?? null;
            if ((string) $from !== (string) $to) {
                $changes[] = ['what' => $label, 'from' => $was ? $from : null, 'to' => $to];
            }
        }
        return ['template' => $now_t, 'changes' => $changes];
    }

    /** Off every send window; kept whole so a send that used it keeps its trail. */
    public function archive(int $id, string $user): array
    {
        $t = $this->get($id);
        if (!$t) {
            throw new \RuntimeException('No such template.');
        }
        if ($t['archived_at'] !== null) {
            return $t;
        }
        $this->db->query('UPDATE tf_email_template SET archived_at = ?, archived_by = ?, is_default = 0 WHERE id = ?', [date('Y-m-d H:i:s'), mb_substr($user, 0, 100), $id]);
        return $this->get($id);
    }

    public function restore(int $id): array
    {
        $t = $this->get($id);
        if (!$t) {
            throw new \RuntimeException('No such template.');
        }
        $dup = $this->db->query('SELECT id FROM tf_email_template WHERE name = ? AND archived_at IS NULL AND id <> ?', [$t['name'], $id])->getRowArray();
        if ($dup) {
            throw new \RuntimeException('A live template is already called "' . $t['name'] . '"; rename that one first.');
        }
        $this->db->query('UPDATE tf_email_template SET archived_at = NULL, archived_by = NULL WHERE id = ?', [$id]);
        return $this->get($id);
    }
}
