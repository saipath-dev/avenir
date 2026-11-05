<?php

namespace App\Libraries\HotelRates;

/**
 * Date helpers for hotel rate sheets.
 *
 * Sri Lankan contracts write dates as 01.05.2026, 01/05/2026 or 1 May 2026;
 * Excel hands us serial numbers (45778); the system stores Y-m-d.
 */
final class Dates
{
    /**
     * Parse anything a rate sheet or Excel cell might contain into Y-m-d.
     * Returns null when the value cannot be read as a date.
     */
    public static function parse($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $s = trim((string) $value);
        if ($s === '' || $s === '0000-00-00') {
            return null;
        }

        // Excel serial number (days since 1899-12-30)
        if (is_numeric($s) && (float) $s > 20000 && (float) $s < 80000) {
            $d = new \DateTime('1899-12-30');
            $d->modify('+' . (int) floor((float) $s) . ' days');
            return $d->format('Y-m-d');
        }

        // ISO first: 2026-05-01 or 2026-05-01 00:00:00
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $s, $m)) {
            return self::build((int) $m[1], (int) $m[2], (int) $m[3]);
        }
        // Day-first with . / or - separators: 01.05.2026, 1/5/2026, 01-05-2026, 01.05.26
        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{2,4})$/', $s, $m)) {
            $y = (int) $m[3];
            if ($y < 100) {
                $y += 2000;
            }
            return self::build($y, (int) $m[2], (int) $m[1]);
        }
        // 1 May 2026, 01-May-2026, May 1, 2026, 1st May 2026
        $clean = preg_replace('/(\d)(st|nd|rd|th)/i', '$1', $s);
        $ts = strtotime($clean);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }
        return null;
    }

    private static function build(int $y, int $m, int $d): ?string
    {
        if (!checkdate($m, $d, $y)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    /** Inclusive overlap test on Y-m-d strings. */
    public static function overlaps(string $aFrom, string $aTo, string $bFrom, string $bTo): bool
    {
        return $bFrom <= $aTo && $aFrom <= $bTo;
    }

    /** Day after a Y-m-d date. */
    public static function next(string $date): string
    {
        return (new \DateTime($date))->modify('+1 day')->format('Y-m-d');
    }

    /** Day before a Y-m-d date. */
    public static function prev(string $date): string
    {
        return (new \DateTime($date))->modify('-1 day')->format('Y-m-d');
    }

    /** Number of days between two Y-m-d dates (b - a). */
    public static function diffDays(string $a, string $b): int
    {
        return (int) (new \DateTime($a))->diff(new \DateTime($b))->format('%r%a');
    }

    /**
     * Given a list of [from, to] ranges (inclusive, Y-m-d), return the gaps
     * inside [$from, $to] that no range covers.
     *
     * @param array<int, array{0:string,1:string}> $ranges
     * @return array<int, array{0:string,1:string}>
     */
    public static function gaps(array $ranges, string $from, string $to): array
    {
        usort($ranges, static fn ($x, $y) => strcmp($x[0], $y[0]));
        $gaps = [];
        $cursor = $from;
        foreach ($ranges as [$rf, $rt]) {
            if ($rt < $cursor) {
                continue;
            }
            if ($rf > $cursor) {
                $gapEnd = min(self::prev($rf), $to);
                if ($gapEnd >= $cursor) {
                    $gaps[] = [$cursor, $gapEnd];
                }
            }
            $cursor = max($cursor, self::next($rt));
            if ($cursor > $to) {
                break;
            }
        }
        if ($cursor <= $to) {
            $gaps[] = [$cursor, $to];
        }
        return $gaps;
    }

    /**
     * The nights inside [$from, $to] that fall on the given MM-DD list
     * (the gala nights from Settings, e.g. ['12-24', '12-31']).
     */
    public static function galaNights(string $from, string $to, array $monthDays = ['12-24', '12-31']): array
    {
        $out = [];
        for ($y = (int) substr($from, 0, 4); $y <= (int) substr($to, 0, 4); $y++) {
            foreach ($monthDays as $md) {
                $d = "{$y}-{$md}";
                if ($d >= $from && $d <= $to) {
                    $out[] = $d;
                }
            }
        }
        sort($out);
        return $out;
    }
}
