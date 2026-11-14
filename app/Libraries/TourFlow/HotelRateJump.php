<?php

namespace App\Libraries\TourFlow;

/**
 * Edit hotel on a night of the Tour Inquiry's Hotels tab (tf-ratejump-r1, 14 Nov 2026).
 *
 * It opens the Hotel Rates master on the rate that night uses: the hotel, the stay date, the room type, the meal plan
 * and the occupancy. Nothing is changed here — this only works out WHERE to go. Any change to a rate is then made in
 * the Hotel Rates panel itself, through its own save, approval and history.
 *
 * Super Admin only. The rule is enforced here, on the server; hiding the button is only a convenience.
 */
final class HotelRateJump
{
    public const PANEL = 'master_files/hotel_rate_v2';

    /** the rate column each room kind is priced from on a rate row */
    private const COLUMN = ['sgl' => 'sgl', 'dbl' => 'dbl', 'twn' => 'dbl', 'tpl' => 'tpl'];
    private const WORD   = ['sgl' => 'Single', 'dbl' => 'Double', 'twn' => 'Twin', 'tpl' => 'Triple'];

    /**
     * @param int        $role     the signed-in person's role (1 = Super Admin)
     * @param array      $it       the inquiry (IntakeService::get)
     * @param int        $dayId    the night's id
     * @param array|null $costLine that night's line in the costing (QuoteService::costing()['hotels']), for the reason a rate is missing
     */
    public static function resolve(int $role, array $it, int $dayId, ?array $costLine = null): array
    {
        if ($role !== 1) {
            throw new SuperAdminOnly('Only the Super Admin can open the Hotel Rates master from an inquiry night.');
        }
        $day = null;
        foreach ((array) ($it['days'] ?? []) as $d) {
            if ((int) $d['id'] === $dayId) {
                $day = $d;
                break;
            }
        }
        if (!$day) {
            throw new \InvalidArgumentException('That night is not on this inquiry.');
        }
        $acc = trim((string) ($day['hotel_acc'] ?? ''));
        if ($acc === '') {
            throw new \InvalidArgumentException('This night has no hotel from the Hotel Master yet. Choose the hotel first (Change hotel or meal plan).');
        }
        $mix  = (array) ($day['room_mix'] ?? []);
        $occ  = [];
        $cols = [];
        foreach (self::COLUMN as $kind => $col) {
            if ((int) ($mix[$kind] ?? 0) > 0) {
                $occ[]  = (int) $mix[$kind] . ' ' . self::WORD[$kind];
                $cols[] = $col;
            }
        }
        $cols   = array_values(array_unique($cols));
        $planId = (int) ($day['rate_plan_id'] ?? 0);
        $hasAll = $cols !== [];
        foreach ($cols as $col) {
            $v = $day['rate_' . $col] ?? null;
            if ($v === null || $v === '') {
                $hasAll = false;
            }
        }
        $found = ($day['rate_state'] ?? '') === 'rate' && $planId > 0 && $hasAll;
        $why   = '';
        if (!$found) {
            $why = trim((string) ($costLine['why'] ?? ''));
            if ($why === '') {
                $why = ($day['rate_state'] ?? '') === 'rate' ? 'The plan has no figure for this room type, meal plan and occupancy.' : (trim((string) ($day['rate_note'] ?? '')) ?: 'No approved rate plan covers this night.');
            }
        }
        $room = trim((string) ($day['room_type_name'] ?? ''));
        $q = array_filter([
            'hotel'    => $acc,
            'contract' => $planId > 0 ? $planId : null,
            'date'     => (string) ($day['day_date'] ?? ''),
            'room'     => $room,
            'meal'     => (string) ($day['meal_plan'] ?? ''),
            'occ'      => implode(',', $cols),
            'occw'     => implode(', ', $occ),
            'inq'      => (int) $it['id'],
            'inqno'    => (string) ($it['inquiry_no'] ?? ''),
            'night'    => (int) $day['day_no'],
            'found'    => $found ? '1' : '0',
            'why'      => $why,
        ], static fn ($v) => $v !== null && $v !== '');
        return [
            'path'    => self::PANEL . '?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986),
            'found'   => $found,
            'why'     => $why,
            'plan_id' => $planId ?: null,
            'night'   => (int) $day['day_no'],
            'hotel'   => (string) ($day['hotel_name_raw'] ?? ''),
            'summary' => trim(($day['hotel_name_raw'] ?? '') . ' · ' . ($day['day_date'] ?? '') . ' · ' . ($room !== '' ? $room : 'room type not set') . ' · ' . ($day['meal_plan'] ?? 'meal plan not set') . ' · ' . (implode(', ', $occ) ?: 'rooms not set')),
        ];
    }
}
