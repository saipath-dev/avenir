<?php

namespace App\Libraries\TourFlow;

/**
 * tf-resdesk-r2 (13 Nov 2026) — the rooming list of one hotel stay: the guests room by room.
 *
 * Read from the records, never typed here: the room mix of the stay (its rooms_note, e.g. "5 TWN — Superior", as the
 * Hotel Desk card carries it), the passengers in their saved order with the room type the agent gave each (SGL/DBL/TWN/TPL).
 * Each passenger goes into the first room of the type the agent named that has a free bed, otherwise the next room with a free
 * bed; a passenger the agent gave no room type fills the rooms in order. A child marked "no bed" shares a room without taking
 * a bed. Nobody is left out: a passenger who fits nowhere is listed as "to allocate" so the desk sees it before sending.
 * Allocation numbers (01, 02 …) are the list's own; the hotel writes its room numbers in the blank column.
 */
final class RoomingList
{
    public const BEDS = ['SGL' => 1, 'DBL' => 2, 'TWN' => 2, 'TPL' => 3, 'QUAD' => 4];
    public const WORD = ['SGL' => 'Single', 'DBL' => 'Double', 'TWN' => 'Twin', 'TPL' => 'Triple', 'QUAD' => 'Quad'];

    /** "5 TWN · 1 SGL — Superior" → [['TWN',5],['SGL',1]], 'Superior' */
    public static function mix(?string $note): array
    {
        $note = (string) $note;
        $type = '';
        if (preg_match('/[—–-]\s*([^—–]+)$/u', $note, $m) && !preg_match('/\d+\s*(SGL|DBL|TWN|TPL)/i', $m[1])) {
            $type = trim($m[1]);
        }
        $out = [];
        if (preg_match_all('/(\d+)\s*(SGL|DBL|TWN|TPL|QUAD)\b/i', $note, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $x) {
                $out[] = [strtoupper($x[2]), (int) $x[1]];
            }
        }
        return [$out, $type];
    }

    private static function code(?string $t): ?string
    {
        $t = strtoupper(trim((string) $t));
        foreach (['SGL' => ['SGL', 'SINGLE'], 'DBL' => ['DBL', 'DOUBLE'], 'TWN' => ['TWN', 'TWIN'], 'TPL' => ['TPL', 'TRIPLE'], 'QUAD' => ['QUAD']] as $c => $words) {
            foreach ($words as $w) {
                if ($t !== '' && str_contains($t, $w)) {
                    return $c;
                }
            }
        }
        return null;
    }

    private static function noBed(array $p): bool
    {
        return (bool) preg_match('/no[\s-]*bed/i', (string) ($p['note'] ?? '') . ' ' . (string) ($p['room_type'] ?? ''));
    }

    /**
     * @param array $stay  hotelDocument()['stay'] (rooms_note, meal_plan)
     * @param array $pax   [['full_name','room_type','note']]
     * @return array{rooms: array, unallocated: array, total_rooms:int, total_guests:int, room_type_text:string, summary:string, meals: string[]}
     */
    public static function build(array $stay, array $pax, ?string $roomTypeText = null): array
    {
        [$mix, $typeFromNote] = self::mix($stay['rooms_note'] ?? null);
        $typeText = trim((string) ($roomTypeText ?: $typeFromNote));
        $meal = (string) ($stay['meal_plan'] ?? '');
        $rooms = [];
        foreach ($mix as [$c, $n]) {
            for ($i = 0; $i < $n; $i++) {
                $rooms[] = ['code' => $c, 'beds' => self::BEDS[$c] ?? 2, 'guests' => [], 'meal' => $meal];
            }
        }
        $pax = array_values(array_filter($pax, static fn ($p) => trim((string) ($p['full_name'] ?? '')) !== ''));
        if (!$rooms) {
            // no mix on the stay: the rooms come from the passengers' own room types, consecutively
            foreach ($pax as $p) {
                $c = self::code($p['room_type'] ?? null) ?? 'DBL';
                $last = $rooms ? count($rooms) - 1 : -1;
                if ($last < 0 || $rooms[$last]['code'] !== $c || self::used($rooms[$last]) >= $rooms[$last]['beds']) {
                    $rooms[] = ['code' => $c, 'beds' => self::BEDS[$c] ?? 2, 'guests' => [], 'meal' => $meal];
                }
                $rooms[count($rooms) - 1]['guests'][] = ['name' => trim($p['full_name']), 'no_bed' => self::noBed($p)];
            }
            $pax = [];
        }
        $un = [];
        // first those the agent gave a room type, then the rest, each in the saved order
        $typed = array_filter($pax, static fn ($p) => self::code($p['room_type'] ?? null) !== null);
        $untyped = array_filter($pax, static fn ($p) => self::code($p['room_type'] ?? null) === null);
        foreach ([$typed, $untyped] as $list) {
            foreach ($list as $p) {
                $g = ['name' => trim($p['full_name']), 'no_bed' => self::noBed($p)];
                $want = self::code($p['room_type'] ?? null);
                $placed = false;
                if ($g['no_bed']) {
                    // a child without a bed joins the room of the person listed before it, else the first room
                    foreach (array_reverse(array_keys($rooms)) as $k) {
                        if ($rooms[$k]['guests']) {
                            $rooms[$k]['guests'][] = $g;
                            $placed = true;
                            break;
                        }
                    }
                }
                foreach ([true, false] as $strict) {
                    if ($placed) {
                        break;
                    }
                    foreach ($rooms as $k => $r) {
                        if (($strict && $want !== null && $r['code'] !== $want) || ($strict && $want === null)) {
                            continue;
                        }
                        if (self::used($r) < $r['beds']) {
                            $rooms[$k]['guests'][] = $g;
                            $placed = true;
                            break;
                        }
                    }
                }
                if (!$placed) {
                    $un[] = $g['name'];
                }
            }
        }
        $guests = 0;
        foreach ($rooms as $r) {
            $guests += count($r['guests']);
        }
        $counts = [];
        foreach ($rooms as $r) {
            $counts[$r['code']] = ($counts[$r['code']] ?? 0) + 1;
        }
        $summary = implode(', ', array_map(static fn ($c, $n) => $n . ' ' . (self::WORD[$c] ?? $c), array_keys($counts), $counts)) . ($typeText !== '' ? ' – ' . $typeText : '');
        return [
            'rooms' => $rooms, 'unallocated' => $un, 'total_rooms' => count($rooms), 'total_guests' => $guests + count($un),
            'room_type_text' => $typeText, 'summary' => $summary,
            'meals' => array_values(array_unique(array_filter(array_map(static fn ($r) => (string) $r['meal'], $rooms)))),
        ];
    }

    private static function used(array $r): int
    {
        return count(array_filter($r['guests'], static fn ($g) => !$g['no_bed']));
    }

    public static function roomLabel(array $r, string $typeText): string
    {
        return trim((self::WORD[$r['code']] ?? $r['code']) . ' ' . $typeText);
    }
}
