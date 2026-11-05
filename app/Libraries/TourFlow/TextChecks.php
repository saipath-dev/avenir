<?php

namespace App\Libraries\TourFlow;

/**
 * What can be checked about an email's dates before anything reads it — by rule, not by
 * model, so it says the same thing every time and costs nothing.
 *
 * The one job: catch the dates a reader would otherwise have to guess at. An agent writes
 * "17JAN" and "24JAN" in September; whether that is next January is obvious to a person
 * and unknowable to a program without assuming. So the text is scanned for every date
 * mention, the ones that carry a year are told from the ones that do not, and the review
 * step shows the result: no year anywhere, no date at all, a departure before the arrival,
 * a date already gone by. Staff correct the text and the flags clear; nothing here fills a
 * year in. The same scan is what the reader's output is held to afterwards.
 *
 * Forward headers ("Sent: 21 September 2026", "Date:", "On … wrote:") are dates about the
 * email, not the tour, so they are recognised and left out of the travel dates.
 */
final class TextChecks
{
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'sept' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    /** Lines that belong to the mail client, not the agent: their dates are not travel dates. */
    private const HEADER_LINE = '/^\s*(?:>+\s*)?(?:from|sent|date|to|cc|bcc|subject|received|送信日時|差出人|宛先|件名|日時)\s*[:：]/iu';
    private const WROTE_LINE  = '/^\s*(?:>+\s*)?(?:on\s.+\swrote\s*:|-{2,}\s*(?:original|forwarded)\s+message\s*-{2,}|_{5,}|\d{4}年\d{1,2}月\d{1,2}日.*(?:wrote|書きました)\s*[:：]?\s*$)/iu';

    /** tf-intake-r1: a line about a person's age or birth — "+1 Child (20JAN2025)", "DOB 12 May 2019", "born" — carries
     *  a date of birth, not a travel date. It is kept in the list but left out of the travel checks. */
    private const BIRTH_LINE = '/\b(?:child(?:ren)?|infant|baby|kid|d\.?o\.?b\.?|date\s+of\s+birth|birth\s*(?:day|date)?|born|y\/?o|years?[\s-]*old|yrs?)\b|生年月日|歳|才|子供|幼児/iu';

    /** A line that talks about arriving or leaving, for the order check. */
    private const ARRIVE = '/\b(?:arriv\w*|arr\.?|check[\s-]?in|in\s*bound|eta)\b|到着|入国/iu';
    private const DEPART = '/\b(?:depart\w*|dep\.?|check[\s-]?out|out\s*bound|etd|leave|leaving)\b|出発|出国/iu';

    /**
     * Every date mention in the text, and what is wrong with them.
     *
     * @param string      $text       the email as it stands (the working copy)
     * @param string|null $receivedAt the email's own date, if known (Y-m-d…)
     * @param string|null $today      for tests; defaults to today
     * @return array{
     *   dates: array<int, array{text:string, day:int, month:int, year:?int, line:int, header:bool, iso:?string}>,
     *   travel: array<int, array{text:string, day:int, month:int, year:?int, line:int, header:bool, iso:?string}>,
     *   year_stated: bool,
     *   flags: array<int, array{code:string, level:string, label:string}>
     * }
     */
    public static function scan(string $text, ?string $receivedAt = null, ?string $today = null): array
    {
        $today = $today ?: date('Y-m-d');
        $sent  = null;
        if ($receivedAt !== null && preg_match('/^(\d{4}-\d{2}-\d{2})/', trim($receivedAt), $m)) {
            $sent = $m[1];
        }
        $lines  = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $dates  = [];
        $inHeaderBlock = false;
        foreach ($lines as $no => $line) {
            $header = (bool) preg_match(self::HEADER_LINE, $line) || (bool) preg_match(self::WROTE_LINE, $line);
            // a run of header lines (From / Sent / To / Subject) is one block; a blank line ends it
            if ($header) {
                $inHeaderBlock = true;
            } elseif (trim($line) === '') {
                $inHeaderBlock = false;
            }
            foreach (self::mentions($line) as $d) {
                $d['line']   = $no + 1;
                $d['header'] = $header || $inHeaderBlock || (bool) preg_match(self::BIRTH_LINE, $line);   // tf-intake-r1: a birth date is not a travel date
                $d['iso']    = $d['year'] !== null && checkdate($d['month'], $d['day'], $d['year']) ? sprintf('%04d-%02d-%02d', $d['year'], $d['month'], $d['day']) : null;
                $dates[] = $d;
            }
        }
        $travel = array_values(array_filter($dates, static fn (array $d) => !$d['header']));

        // A year written anywhere in the agent's own lines counts as stated — "in 2027", "2027年".
        $yearStated = (bool) array_filter($travel, static fn (array $d) => $d['year'] !== null);
        if (!$yearStated) {
            foreach ($lines as $no => $line) {
                if (preg_match(self::HEADER_LINE, $line) || preg_match(self::WROTE_LINE, $line) || preg_match(self::BIRTH_LINE, $line)) {
                    continue;   // tf-intake-r1: the year in a child's date of birth says nothing about the travel year
                }
                if (preg_match('/(?<![\d.\-\/])(20[2-3]\d)(?![\d.\-\/:])/u', $line)) {
                    $yearStated = true;
                    break;
                }
            }
        }

        $flags = [];
        if (trim($text) !== '' && $travel === []) {
            $flags[] = ['code' => 'no_dates', 'level' => 'red', 'label' => 'No travel date was found in the email. Add the arrival and departure dates (with the year) to the text before reading, or the itinerary will have no dates.'];
        }
        if ($travel !== [] && !$yearStated) {
            $seen = [];
            foreach ($travel as $d) {
                $k = $d['month'] . '-' . $d['day'];
                if (!isset($seen[$k])) {
                    $seen[$k] = self::dm($d);
                }
            }
            $list = array_values($seen);
            $more = count($list) > 6 ? ' and ' . (count($list) - 6) . ' more' : '';
            $flags[] = ['code' => 'year_missing', 'level' => 'red', 'label' => implode(', ', array_slice($list, 0, 6)) . $more . ': no year is written anywhere in the email. Add the year to the text (for example "17 Jan 2027") before reading — a date without a year is not recorded, and nothing is guessed.'];
        }

        // dates with a year that are already gone by the time the email was sent
        $ref = $sent ?? $today;
        $past = [];
        $far  = [];
        foreach ($travel as $d) {
            if ($d['iso'] === null) {
                continue;
            }
            if ($d['iso'] < $ref) {
                $past[$d['iso']] = self::dmy($d);
            } elseif ((int) substr($d['iso'], 0, 4) > (int) substr($today, 0, 4) + 3) {
                $far[$d['iso']] = self::dmy($d);
            }
        }
        if ($past !== []) {
            $flags[] = ['code' => 'date_past', 'level' => 'red', 'label' => implode(', ', array_values($past)) . ($sent ? ' is before the email was sent (' . self::pretty($sent) . ')' : ' has already passed') . ' — check the year in the text before reading.'];
        }
        if ($far !== []) {
            $flags[] = ['code' => 'date_far', 'level' => 'amber', 'label' => implode(', ', array_values($far)) . ' is more than three years away — check the year.'];
        }

        // the order: a departure before the arrival, or a range written backwards
        $arr = $dep = null;
        foreach ($lines as $no => $line) {
            if (preg_match(self::HEADER_LINE, $line)) {
                continue;
            }
            $here = array_values(array_filter($travel, static fn (array $d) => $d['line'] === $no + 1));
            if ($here === []) {
                continue;
            }
            if ($arr === null && preg_match(self::ARRIVE, $line) && !preg_match(self::DEPART, $line)) {
                $arr = $here[0];
            } elseif ($dep === null && preg_match(self::DEPART, $line) && !preg_match(self::ARRIVE, $line)) {
                $dep = $here[0];
            } elseif ($arr === null && $dep === null && count($here) >= 2 && preg_match(self::ARRIVE, $line) && preg_match(self::DEPART, $line)) {
                // "Arrival 17 Jan, departure 24 Jan" on one line: in the order written
                [$arr, $dep] = [$here[0], $here[1]];
            }
        }
        if ($arr !== null && $dep !== null) {
            $bad = false;
            if ($arr['iso'] !== null && $dep['iso'] !== null) {
                $bad = $dep['iso'] < $arr['iso'];
            } elseif ($arr['year'] === null && $dep['year'] === null && $arr['month'] === $dep['month']) {
                $bad = $dep['day'] < $arr['day'];
            }
            if ($bad) {
                $flags[] = ['code' => 'span', 'level' => 'red', 'label' => 'Departure ' . self::dmy($dep) . ' is before arrival ' . self::dmy($arr) . ' — correct the dates in the text before reading.'];
            }
        }
        foreach ($lines as $line) {
            // 24-17JAN, 24JAN-17JAN: a range written backwards
            if (preg_match('/(?<!\d)(\d{1,2})\s*[-–]\s*(\d{1,2})\s*([a-z]{3})/iu', $line, $m) && isset(self::MONTHS[strtolower($m[3])]) && (int) $m[1] > (int) $m[2] && (int) $m[1] <= 31) {
                $flags[] = ['code' => 'range', 'level' => 'red', 'label' => '"' . trim($m[0]) . '" runs backwards — check which day is the arrival and which the departure.'];
                break;
            }
        }
        return ['dates' => $dates, 'travel' => $travel, 'year_stated' => $yearStated, 'flags' => $flags];
    }

    /** Is a year written for the travel dates? The reader's dates are dropped when it is not. */
    public static function yearStated(string $text): bool
    {
        return self::scan($text)['year_stated'];
    }

    /**
     * The date mentions on one line, in the forms agents write: 17JAN, 17 Jan 2027, 17JAN27,
     * 17-24JAN (the 24th), January 17(, 2027), 17/01/2027, 2027-01-17, 2027/1/17, 1月17日.
     *
     * @return array<int, array{text:string, day:int, month:int, year:?int}>
     */
    public static function mentions(string $line): array
    {
        $out = [];
        $add = static function (string $text, int $day, int $month, ?int $year) use (&$out): void {
            if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
                return;
            }
            // trailing punctuation and the full-width space Japanese keyboards insert are not part of the date
            $out[] = ['text' => trim(preg_replace('/[\s,;.\x{3000}]+$/u', '', preg_replace('/^[\s\x{3000}]+/u', '', $text))), 'day' => $day, 'month' => $month, 'year' => $year];
        };
        $yearOf = static function (?string $y): ?int {
            $y = trim((string) $y, "' ");
            if ($y === '') {
                return null;
            }
            if (strlen($y) === 2) {
                $n = (int) $y;
                return ($n >= 20 && $n <= 39) ? 2000 + $n : null;
            }
            $n = (int) $y;
            return ($n >= 2000 && $n <= 2099) ? $n : null;
        };
        // 17JAN, 17 Jan, 17-Jan-2027, 17JAN27, 17th January 2027
        if (preg_match_all('/(?<![\d:])(\d{1,2})(?:st|nd|rd|th)?\s*[-\/.]?\s*(jan|feb|mar|apr|may|jun|jul|aug|sept|sep|oct|nov|dec)[a-z]*\.?,?\s*[-\/.]?\s*(\'?\d{2}(?!\d)|\d{4}(?!\d))?/iu', $line, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                // a bare two-digit tail that is not a year (flight lines: "01SEP 2 NRTCMB") is left as no year
                $add($m[0], (int) $m[1], self::MONTHS[strtolower($m[2])], $yearOf($m[3] ?? null));
            }
        }
        // January 17, 2027 / Jan 17th / Sept 3
        if (preg_match_all('/\b(jan|feb|mar|apr|may|jun|jul|aug|sept|sep|oct|nov|dec)[a-z]*\.?\s+(\d{1,2})(?:st|nd|rd|th)?(?!\d)(?:\s*,?\s*(\d{4})(?!\d))?/iu', $line, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                // "SEP 2 NRTCMB" in a flight line is not "2 September"; skip when a day-month form already covered it
                if (preg_match('/\d\s*' . preg_quote($m[1], '/') . '/i', substr($line, 0, (int) strpos($line, $m[0]) + strlen($m[1])))) {
                    continue;
                }
                $add($m[0], (int) $m[2], self::MONTHS[strtolower($m[1])], $yearOf($m[3] ?? null));
            }
        }
        // 2027-01-17, 2027/1/17, 2027.01.17
        if (preg_match_all('/(?<!\d)(20[2-3]\d)[-\/.](\d{1,2})[-\/.](\d{1,2})(?!\d)/u', $line, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $add($m[0], (int) $m[3], (int) $m[2], (int) $m[1]);
            }
        }
        // 17/01/2027, 17.01.27 — day first, as the trade writes it; a year is required here, a bare
        // "4/5" is a room count or a fraction as often as a date
        if (preg_match_all('/(?<![\d\/.\-])(\d{1,2})[\/.](\d{1,2})[\/.](\d{2}|\d{4})(?![\d\/.])/u', $line, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $add($m[0], (int) $m[1], (int) $m[2], $yearOf($m[3]));
            }
        }
        // 2027年1月17日 / 1月17日
        if (preg_match_all('/(?:(20[2-3]\d)年)?\s*(\d{1,2})月\s*(\d{1,2})日/u', $line, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $add($m[0], (int) $m[3], (int) $m[2], $m[1] !== '' ? (int) $m[1] : null);
            }
        }
        return $out;
    }

    private static function dm(array $d): string
    {
        return $d['day'] . ' ' . date('M', mktime(0, 0, 0, $d['month'], 1, 2000));
    }

    private static function dmy(array $d): string
    {
        return self::dm($d) . ($d['year'] !== null ? ' ' . $d['year'] : '');
    }

    private static function pretty(string $iso): string
    {
        return date('j M Y', strtotime($iso));
    }
}
