<?php

namespace App\Libraries\TourFlow;

/**
 * tf-autosend-r1 (3 Nov 2026) — the one switch that decides whether the system may send a supplier's letter BY
 * ITSELF: the hotels' booking requests and the restaurants' vouchers at Confirm, and the transport requests when
 * a vehicle, a driver or a guide is assigned.
 *
 * It is OFF unless tf_meta.auto_send_suppliers holds '1', so a development copy — which carries the same mailbox
 * settings as the live system — never writes to a real hotel, restaurant, driver or supplier on its own. With it
 * off the letters are still written and kept with their reason, the desks can send each one by hand as always,
 * and nothing is ever recorded as sent that did not go.
 *
 * A person pressing Send on a desk is not automatic sending and is never affected by this switch.
 */
final class AutoSend
{
    public const KEY = 'auto_send_suppliers';

    public const HELD = 'Automatic sending is switched off on this installation. The letter is ready — send it from the desk when you are ready.';

    public static function on($db): bool
    {
        try {
            $r = $db->query('SELECT v FROM tf_meta WHERE k = ?', [self::KEY])->getRowArray();
            if ($r !== null) {
                return trim((string) $r['v']) === '1';
            }
            // the earlier transport-only key, so an installation that set that one keeps its answer
            $old = $db->query("SELECT v FROM tf_meta WHERE k = 'transport_autosend'")->getRowArray();
            return $old !== null && trim((string) $old['v']) === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }
}
