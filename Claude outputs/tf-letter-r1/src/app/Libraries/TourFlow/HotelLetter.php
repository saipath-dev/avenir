<?php

namespace App\Libraries\TourFlow;

/**
 * tf-letter-r1 (10 Oct 2026) — what a letter to a hotel says about the rate, and the logo it carries.
 *
 * Rates are confidential: a booking request or voucher never shows a figure. It names the basis instead —
 * "As per agreed contract rates", or, when a tactical rate or an early bird offer has been applied to the
 * stay (Tactical / Early Bird rate selection, hr2_offer_choice), "As per agreed tactical rate" / "As per
 * agreed early bird offer". Reads only.
 */
final class HotelLetter
{
    public const CONTRACT = 'As per agreed contract rates';

    /** The rate basis for a stay (a Hotel Desk card: tour_id, hotel_acc). */
    public static function rateBasis($db, array $job): string
    {
        try {
            $acc = trim((string) ($job['hotel_acc'] ?? ''));
            if ($acc === '' || !$db->query("SHOW TABLES LIKE 'hr2_offer_choice'")->getResultArray()) {
                return self::CONTRACT;
            }
            $ids = [];
            $tour = $db->query('SELECT tour_no, legacy_tour_id FROM tf_tour WHERE id = ?', [(int) ($job['tour_id'] ?? 0)])->getRowArray();
            foreach ([$tour['legacy_tour_id'] ?? null, $tour['tour_no'] ?? null, $job['tour_no'] ?? null] as $v) {
                if (trim((string) $v) !== '') {
                    $ids[] = trim((string) $v);
                }
            }
            $ids = array_values(array_unique($ids));
            if (!$ids) {
                return self::CONTRACT;
            }
            $c = $db->query('SELECT choice, offer_kind FROM hr2_offer_choice WHERE hotel_acc = ? AND active = 1 AND tour_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY id DESC LIMIT 1', array_merge([$acc], $ids))->getRowArray();
            if (!$c || (string) $c['choice'] !== 'offer') {
                return self::CONTRACT;
            }
            return match ((string) $c['offer_kind']) {
                'early_bird'    => 'As per agreed early bird offer',
                'special_offer' => 'As per agreed special offer',
                default         => 'As per agreed tactical rate',
            };
        } catch (\Throwable $e) {
            return self::CONTRACT;
        }
    }

    /** The logo for letters: the e-mail logo made from the company logo, else the old one; null if neither is there. */
    public static function logoUrl(): ?string
    {
        try {
            foreach (['assets/images/email/avenir-logo-email.png', 'assets/images/email/logo.png'] as $rel) {
                if (defined('FCPATH') && is_file(FCPATH . $rel)) {
                    return base_url($rel);
                }
            }
        } catch (\Throwable $e) {
        }
        return null;
    }
}
