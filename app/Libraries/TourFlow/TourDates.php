<?php

namespace App\Libraries\TourFlow;

/**
 * The one rule for a tour's shape (schema 32): the arrival date and the departure date decide
 * everything else, for every inquiry, read from an email or typed by hand.
 *
 *   nights = the departure date minus the arrival date
 *   days   = nights + 1
 *   night N is dated the arrival date + N - 1, from night 1 to the night before departure
 *   day nights + 1 is the departure day: the transfer from the last night's place to the airport,
 *   and whatever else is on that day (a late check-out, lunch, a visit on the way) — never a hotel
 *
 * A line of the itinerary dated on or after the departure date, or numbered past the nights, is
 * not a night. It is kept exactly as it is and shown as "falls on the departure day" until a
 * person says what it is: the departure day (its services and wording move there) or an extra
 * night (the departure date moves). Nothing here writes; it only reads and says.
 *
 * The same line — "Arrival Mon 26 Oct 2026 · Departure Mon 2 Nov 2026 · 7 nights · 8 days" — is
 * printed wherever a tour's dates are shown, from line() below, so every screen says it the same.
 */
final class TourDates
{
    /** A Y-m-d date, or null. */
    public static function iso($v): ?string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }
        return $s;
    }

    /** Nights between the two dates; null unless both are set and the departure is after the arrival. */
    public static function nights($arrival, $departure): ?int
    {
        $a = self::iso($arrival);
        $d = self::iso($departure);
        if ($a === null || $d === null || $d <= $a) {
            return null;
        }
        return (int) round((strtotime($d . ' 12:00:00') - strtotime($a . ' 12:00:00')) / 86400);
    }

    /** "Mon 26 Oct 2026". */
    public static function day($iso): string
    {
        $d = self::iso($iso);
        return $d === null ? '' : date('D j M Y', strtotime($d . ' 12:00:00'));
    }

    /** The date of night (or day) N: the arrival plus N - 1 days. */
    public static function dateOf($arrival, int $n): ?string
    {
        $a = self::iso($arrival);
        return ($a === null || $n < 1) ? null : date('Y-m-d', strtotime($a . ' 12:00:00 +' . ($n - 1) . ' days'));
    }

    /**
     * The line every screen prints: "Arrival Mon 26 Oct 2026 · Departure Mon 2 Nov 2026 · 7 nights · 8 days".
     * A date not set yet says so; the nights and days appear once both dates are set.
     */
    public static function line($arrival, $departure, ?int $nights = null): string
    {
        $a = self::iso($arrival);
        $d = self::iso($departure);
        $n = $nights ?? self::nights($a, $d);
        $bits = [
            $a !== null ? 'Arrival ' . self::day($a) : 'Arrival not set',
            $d !== null ? 'Departure ' . self::day($d) : 'Departure not set',
        ];
        if ($a !== null && $d !== null && $d <= $a) {
            $bits[] = 'the departure is not after the arrival';
        } elseif ($n !== null) {
            $bits[] = $n . ' night' . ($n === 1 ? '' : 's');
            $bits[] = ($n + 1) . ' days';
        }
        return implode(' · ', $bits);
    }

    /**
     * The tour's shape from its dates: arrival, departure, nights, days, the departure day's number
     * and date, and the line. The nights come from the dates alone; with a date missing there are none.
     *
     * @return array{arrival:?string, departure:?string, nights:?int, days:?int, departure_day_no:?int, departure_date:?string, line:string, complete:bool}
     */
    public static function of(array $it): array
    {
        $a = self::iso($it['arrival_date'] ?? null);
        $d = self::iso($it['departure_date'] ?? null);
        $n = self::nights($a, $d);
        return [
            'arrival'          => $a,
            'departure'        => $d,
            'nights'           => $n,
            'days'             => $n !== null ? $n + 1 : null,
            'departure_day_no' => $n !== null ? $n + 1 : null,
            'departure_date'   => $n !== null ? $d : null,
            'line'             => self::line($a, $d, $n),
            'complete'         => $n !== null,
        ];
    }

    /**
     * Whether a line of the itinerary falls past the stay: numbered after the last night, or dated
     * on or after the departure date. With the dates not both set nothing can be said, so nothing is.
     */
    public static function beyond(array $row, ?int $nights, $departure): bool
    {
        if ($nights === null) {
            return false;
        }
        if ((int) ($row['day_no'] ?? 0) > $nights) {
            return true;
        }
        $dep  = self::iso($departure);
        $date = self::iso($row['day_date'] ?? null);
        return $dep !== null && $date !== null && $date >= $dep;
    }

    /**
     * The itinerary's lines split by the rule: the nights of the stay, the lines that fall on the
     * departure day (or later), and the night numbers the dates give that have no line at all.
     *
     * @return array{nights: array<int, array>, departure_rows: array<int, array>, missing: array<int, int>}
     */
    public static function split(array $rows, ?int $nights, $departure): array
    {
        usort($rows, static fn ($x, $y) => [(int) ($x['day_no'] ?? 0), (int) ($x['id'] ?? 0)] <=> [(int) ($y['day_no'] ?? 0), (int) ($y['id'] ?? 0)]);
        $in = $out = [];
        $have = [];
        foreach ($rows as $r) {
            if (self::beyond($r, $nights, $departure)) {
                $r['beyond'] = true;
                $out[] = $r;
            } else {
                $r['beyond'] = false;
                $in[] = $r;
                $have[(int) ($r['day_no'] ?? 0)] = true;
            }
        }
        $missing = [];
        if ($nights !== null) {
            for ($i = 1; $i <= min($nights, 120); $i++) {
                if (!isset($have[$i])) {
                    $missing[] = $i;
                }
            }
        }
        return ['nights' => $in, 'departure_rows' => $out, 'missing' => $missing];
    }

    /** "3", "3 and 4", "1, 2, 4 and 5", "3 to 12" — night numbers in words; three or more in a row are a run. */
    public static function runs(array $numbers): string
    {
        $numbers = array_values(array_unique(array_map('intval', $numbers)));
        sort($numbers);
        $parts = [];
        $i = 0;
        while ($i < count($numbers)) {
            $j = $i;
            while ($j + 1 < count($numbers) && $numbers[$j + 1] === $numbers[$j] + 1) {
                $j++;
            }
            if ($j >= $i + 2) {
                $parts[] = $numbers[$i] . ' to ' . $numbers[$j];
            } else {
                for ($x = $i; $x <= $j; $x++) {
                    $parts[] = (string) $numbers[$x];
                }
            }
            $i = $j + 1;
        }
        if (count($parts) <= 1) {
            return $parts[0] ?? '';
        }
        $last = array_pop($parts);
        return implode(', ', $parts) . ' and ' . $last;
    }

    /** The pair of dates a confirmation was given for: it lapses the moment either pair changes. */
    public static function confirmKey($arrival, $departure, $emailArrival, $emailDeparture): string
    {
        return substr(sha1(implode('|', [self::iso($arrival) ?? '-', self::iso($departure) ?? '-', self::iso($emailArrival) ?? '-', self::iso($emailDeparture) ?? '-'])), 0, 40);
    }
}
