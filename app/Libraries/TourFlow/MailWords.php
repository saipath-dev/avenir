<?php

namespace App\Libraries\TourFlow;

/**
 * tf-mailtpl-r1 (4 Nov 2026) — the words a ready-made email is filled with, read from the record.
 *
 * One place reads the inquiry, the tour it became, its hotels and its activities and hands back the
 * bag of words EmailTemplates::fill() puts into a template. Every window that writes to an agent, a
 * hotel, a restaurant, a driver or any other supplier fills from here, so the same tour reads the
 * same way whichever screen wrote the letter.
 *
 * What it will NOT put in a word: a cost, a markup, a margin or a profit. The only figures it carries
 * are the ones the client is quoted (the quotation's own price and price per person) and a supplier's
 * own agreed amount on that supplier's own voucher. Nothing read here reaches a client-facing email
 * that the client may not see.
 *
 * A word it cannot fill is left empty, never invented: EmailTemplates::missing() then names it so the
 * screen can say what is missing before anything is sent.
 */
final class MailWords
{
    /** the words that must never carry an internal figure, whatever a template asks for */
    public const NEVER = ['cost', 'margin', 'markup', 'profit', 'nett', 'net_cost', 'supplier_cost'];

    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    private static function d($v): string
    {
        return $v ? date('j M Y', strtotime((string) $v)) : '';
    }

    private function meta(string $k): string
    {
        try {
            return (string) ($this->db->query('SELECT v FROM tf_meta WHERE k = ?', [$k])->getRowArray()['v'] ?? '');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * The words of one inquiry (and the tour it became, when it has one).
     * @param array $sender CommsMail::senderFor() / Staff::contact()
     */
    public function forIntake(int $intakeId, array $sender = []): array
    {
        $it = $this->db->query('SELECT id, inquiry_no, tour_name, agent_name, agent_email, arrival_date, departure_date,
                                       pax_total, pax_adults, pax_children
                                  FROM tf_intake WHERE id = ?', [$intakeId])->getRowArray() ?: [];
        $t = $this->db->query('SELECT id, tour_no, arrival_date, departure_date, pax_total FROM tf_tour WHERE intake_id = ? ORDER BY id DESC LIMIT 1', [$intakeId])->getRowArray();
        $arr = $t['arrival_date'] ?? ($it['arrival_date'] ?? null);
        $dep = $t['departure_date'] ?? ($it['departure_date'] ?? null);
        $nights = ($arr && $dep) ? max(0, (int) round((strtotime((string) $dep) - strtotime((string) $arr)) / 86400)) : null;
        $w = [
            'agent'      => (string) ($it['agent_name'] ?? ''),
            'agent_name' => (string) ($it['agent_name'] ?? ''),
            'inquiry_no' => (string) ($it['inquiry_no'] ?? ''),
            'tour_no'    => (string) ($t['tour_no'] ?? ''),
            'tour_name'  => (string) ($it['tour_name'] ?? ''),
            'dates'      => $arr ? self::d($arr) . ($dep ? ' – ' . self::d($dep) : '') : '',
            'arrival'    => self::d($arr),
            'departure'  => self::d($dep),
            'nights'     => $nights !== null ? (string) $nights : '',
            'pax'        => ($t['pax_total'] ?? $it['pax_total'] ?? null) !== null ? (string) ($t['pax_total'] ?? $it['pax_total']) : '',
            'adults'     => ($it['pax_adults'] ?? null) !== null ? (string) $it['pax_adults'] : '',
            'children'   => ($it['pax_children'] ?? null) !== null ? (string) $it['pax_children'] : '',
            'me'         => (string) ($sender['name'] ?? ''),
            'company'    => $this->meta('company_name') ?: 'Avenir Holidays',
            'today'      => date('j M Y'),
        ];
        $w['ref'] = trim($w['inquiry_no'] . ($w['tour_no'] !== '' ? ' / ' . $w['tour_no'] : ''));
        if ($t) {
            $w += $this->tourLines((int) $t['id']);
        }
        $w += $this->quoteWords($intakeId);
        return $w;
    }

    /** The hotels and the activities of a tour, one to a line — nothing priced, nothing internal. */
    public function tourLines(int $tourId): array
    {
        $hotels = [];
        try {
            foreach ($this->db->query("SELECT hotel_name, check_in, check_out, nights, meal_plan
                                         FROM tf_hotel_job
                                        WHERE tour_id = ? AND archived_at IS NULL AND status <> 'cancelled'
                                        ORDER BY check_in, id", [$tourId])->getResultArray() as $h) {
                $hotels[] = '· ' . ($h['hotel_name'] ?: 'hotel to be confirmed')
                    . ' — ' . self::d($h['check_in']) . ' to ' . self::d($h['check_out'])
                    . ', ' . (int) $h['nights'] . ' night' . ((int) $h['nights'] === 1 ? '' : 's')
                    . (trim((string) ($h['meal_plan'] ?? '')) !== '' ? ', ' . $h['meal_plan'] : '');
            }
        } catch (\Throwable $e) {
        }
        $services = [];
        try {
            foreach ($this->db->query("SELECT title, on_date FROM tf_service_job
                                        WHERE tour_id = ? AND archived_at IS NULL AND status <> 'cancelled'
                                        ORDER BY on_date, seq, id", [$tourId])->getResultArray() as $x) {
                $services[] = '· ' . $x['title'] . ($x['on_date'] ? ' — ' . self::d($x['on_date']) : '');
            }
        } catch (\Throwable $e) {
        }
        return ['hotels' => implode("\n", $hotels), 'services' => implode("\n", $services)];
    }

    /** The quotation figures the CLIENT is quoted — never a cost, a markup or a margin. */
    private function quoteWords(int $intakeId): array
    {
        try {
            $q = $this->db->query("SELECT version, sell_usd, per_person_usd, max_pax, valid_until
                                     FROM tf_quote WHERE intake_id = ? AND status = 'issued'
                                    ORDER BY version DESC LIMIT 1", [$intakeId])->getRowArray();
            if (!$q) {
                $q = $this->db->query('SELECT version, sell_usd, per_person_usd, max_pax, valid_until FROM tf_quote WHERE intake_id = ? ORDER BY version DESC LIMIT 1', [$intakeId])->getRowArray();
            }
            if (!$q) {
                return ['version' => '', 'price' => '', 'per_person' => '', 'max_pax' => '', 'valid_until' => ''];
            }
            return [
                'version'     => (string) $q['version'],
                'price'       => $q['sell_usd'] !== null ? 'USD ' . number_format((float) $q['sell_usd'], 2) : '',
                'per_person'  => $q['per_person_usd'] !== null ? 'USD ' . number_format((float) $q['per_person_usd'], 2) : '',
                'max_pax'     => ($q['max_pax'] ?? null) !== null ? (string) (int) $q['max_pax'] : '',
                'valid_until' => self::d($q['valid_until'] ?? null),
            ];
        } catch (\Throwable $e) {
            return ['version' => '', 'price' => '', 'per_person' => '', 'max_pax' => '', 'valid_until' => ''];
        }
    }

    /** The extra words of one transport party: the driver, the guide or the vehicle supplier. */
    public function transportWords(int $tourId, array $p): array
    {
        $j = !empty($p['job_id']) ? $this->db->query('SELECT * FROM tf_transport_job WHERE id = ?', [(int) $p['job_id']])->getRowArray() : null;
        $c = !empty($p['crew_id']) ? $this->db->query('SELECT * FROM tf_transport_crew WHERE id = ?', [(int) $p['crew_id']])->getRowArray() : null;
        $party = (string) ($p['party'] ?? '');
        $role = $party === 'supplier' ? 'vehicle' : ($party === 'guide' ? 'guide services' : 'driver services');
        if ($c && trim((string) ($c['role_name'] ?? '')) !== '') {
            $role = (string) $c['role_name'] . (trim((string) ($c['language'] ?? '')) !== '' ? ' (' . $c['language'] . ')' : '');
        }
        $veh = $j ? trim((string) (($j['vehicle_type'] ?? '') . ' ' . (!empty($j['vehicle_reg']) ? '(' . $j['vehicle_reg'] . ')' : ''))) : '';
        // a party with no crew line of its own — a vehicle supplier, say — serves the whole tour
        $tour = $this->db->query('SELECT arrival_date, departure_date FROM tf_tour WHERE id = ?', [$tourId])->getRowArray() ?: [];
        $days = ($c && !empty($c['date_from']))
            ? self::d($c['date_from']) . ' – ' . self::d($c['date_to'] ?: ($tour['departure_date'] ?? null))
            : (!empty($tour['arrival_date']) ? self::d($tour['arrival_date']) . ' – ' . self::d($tour['departure_date'] ?? null) : '');
        $driver = '';
        $guide = '';
        try {
            foreach ($this->db->query("SELECT kind, person_name FROM tf_transport_crew c JOIN tf_transport_job j ON j.id = c.job_id
                                        WHERE j.tour_id = ? AND c.status = 'engaged' AND c.person_name IS NOT NULL", [$tourId])->getResultArray() as $r) {
                if ((string) $r['kind'] === 'guide' && $guide === '') {
                    $guide = (string) $r['person_name'];
                } elseif ((string) $r['kind'] === 'driver' && $driver === '') {
                    $driver = (string) $r['person_name'];
                }
            }
        } catch (\Throwable $e) {
        }
        return [
            'supplier'     => (string) ($p['name'] ?? ''),
            'role'         => $role,
            'vehicle'      => $veh,
            'service_days' => $days,
            'driver'       => $driver,
            'guide'        => $guide,
        ];
    }
}
