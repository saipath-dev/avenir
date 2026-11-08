<?php

namespace App\Libraries;

/**
 * tf-home-r1 (22 Oct 2026) — the five desks of the Home dashboard (Avenir Holidays Handling System).
 *
 * Nothing here grants anything. Each desk lists EXISTING screens by their menu URL; an entry is shown only when the
 * signed-in group holds that menu (Settings → Group Privileges), or the privilege it rides on ('gate'), or any one of
 * the privileges named in 'any' — the same rule UserPermissionFilter applies on every request, so a hidden link that is
 * typed in directly is still refused by the filter. The System Administrator (group 1) is never filtered, as before.
 * A desk with nothing the user may open is shown greyed with "No access yet — the Super Admin assigns it".
 *
 * Entry keys: url (the page), label, icon (Bootstrap Icons), sub (one line of help), gate (the privilege a page without
 * its own menu entry rides on), any (any one of these privileges), items (a sub-group, e.g. Inquiry).
 */
final class HomeDesks
{
    public const DESKS = [
        'tour' => [
            'name' => 'Tour Desk', 'tone' => 'green', 'icon' => 'tour',
            'items' => [
                ['label' => 'Inquiry', 'icon' => 'bi-inbox', 'items' => [
                    ['url' => 'tour/intake', 'label' => 'Tour inquiries', 'icon' => 'bi-inbox', 'sub' => 'every inquiry: hotels, transport and activities are quoted inside it'],
                    ['url' => 'tour/intake?start=manual', 'gate' => 'tour/intake', 'label' => 'New inquiry', 'icon' => 'bi-plus-circle', 'sub' => 'type it in'],
                    ['url' => 'tour/intake?start=paste', 'gate' => 'tour/intake', 'label' => 'New inquiry from an e-mail', 'icon' => 'bi-clipboard-plus', 'sub' => 'paste the agent\'s mail'],
                    ['url' => 'tour/intake/confirmed', 'gate' => 'tour/intake', 'label' => 'Confirmed tours', 'icon' => 'bi-calendar2-check'],
                ]],
                ['url' => 'tour/board', 'label' => 'Tour Board', 'icon' => 'bi-kanban'],
                ['url' => 'tour/pricing', 'label' => 'Pricing', 'icon' => 'bi-percent', 'sub' => 'margins and the selling price'],
                ['url' => 'tour/pricing/discount', 'label' => 'Tour Discounts', 'icon' => 'bi-receipt-cutoff', 'sub' => 'a discount on an invoice already issued, recorded and approved by a second person'],
                ['url' => 'tour/manage', 'label' => 'Tour Desk admin', 'icon' => 'bi-shield-lock', 'sub' => 'cancel, restore, remove'],
                ['label' => 'Tour masters', 'icon' => 'bi-collection', 'items' => [
                    ['url' => 'master_files/entrance_master', 'label' => 'Places & entrance fees', 'icon' => 'bi-bank'],
                    ['url' => 'master_files/free_visits', 'label' => 'Free visits', 'icon' => 'bi-geo-alt'],
                    ['url' => 'master_files/paid_services', 'label' => 'Paid activities & services', 'icon' => 'bi-ticket-perforated'],
                    ['url' => 'master_files/meal_master', 'label' => 'Meal master', 'icon' => 'bi-cup-hot'],
                    ['url' => 'master_files/tour_photos', 'label' => 'Tour photos', 'icon' => 'bi-images'],
                ]],
            ],
        ],
        'reservation' => [
            'name' => 'Reservation Desk', 'tone' => 'burgundy', 'icon' => 'reservation',
            'items' => [
                ['url' => 'tour/hotel_bookings', 'label' => 'Hotel reservations & bookings', 'icon' => 'bi-door-open', 'sub' => 'request, confirm and voucher every night of a tour'],
                ['url' => 'tour/ticket_stock', 'label' => 'Ticket stock', 'icon' => 'bi-ticket-detailed', 'sub' => 'entrance tickets on hand'],
                ['url' => 'master_files/hotel_rate_v2', 'label' => 'Hotel rates', 'icon' => 'bi-building', 'sub' => 'contracts, seasons, what a night costs'],
                ['url' => 'master_files/hotel_work', 'label' => 'Hotel incomplete work', 'icon' => 'bi-list-check'],
                ['url' => 'master_files/room_type', 'label' => 'Room types', 'icon' => 'bi-grid-3x2-gap'],
                // the earlier hotel screens stay reachable for whoever still holds them (tours begun before Hotel Bookings)
                ['label' => 'Earlier hotel screens', 'icon' => 'bi-clock-history', 'items' => [
                    ['url' => 'dashboard2/hotel_management/hotel_reservation', 'label' => 'Hotel reservation (earlier screen)', 'icon' => 'bi-calendar-plus'],
                    ['url' => 'dashboard2/hotel_management/hotel_booking', 'label' => 'Hotel booking confirmation (earlier screen)', 'icon' => 'bi-calendar-check'],
                ]],
            ],
        ],
        'transport' => [
            'name' => 'Transport Desk', 'tone' => 'green', 'icon' => 'transport',
            'items' => [
                ['url' => 'tour/transport_ops', 'label' => 'Transport operations', 'icon' => 'bi-truck', 'sub' => 'vehicles, drivers and guides per tour'],
                ['url' => 'dashboard2/transport/driver_allow/pending_tours', 'gate' => 'dashboard2/transport/driver_allow/tour_transport', 'label' => 'Driver allocation', 'icon' => 'bi-person-badge', 'sub' => 'tours waiting for a driver'],
                ['url' => 'dashboard2/transport/transport_ongoing', 'label' => 'Ongoing tours', 'icon' => 'bi-signpost-2'],
                ['label' => 'Transport masters', 'icon' => 'bi-collection', 'items' => [
                    ['url' => 'master_files/crew_people', 'label' => 'Drivers & guides', 'icon' => 'bi-people'],
                    ['url' => 'master_files/transport_rates', 'label' => 'Transport rates', 'icon' => 'bi-cash-coin'],
                    ['url' => 'master_files/mileage_allowances', 'label' => 'Mileage & allowances', 'icon' => 'bi-signpost-2'],
                    ['url' => 'master_files/crew_bata', 'label' => 'Crew roles & quotation costing', 'icon' => 'bi-calculator'],
                ]],
            ],
        ],
        'finance' => [
            'name' => 'Finance Desk', 'tone' => 'green', 'icon' => 'finance',
            'items' => [
                ['url' => 'tour/payment_desk', 'label' => 'Payment Desk', 'icon' => 'bi-wallet2', 'sub' => 'requests, batches, bank payments',
                    'any' => ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/request', 'tour/tour_payment_dashboard/approve', 'tour/tour_accounts/payment_approval_v2', 'tour/tour_payment_dashboard/payables', 'tour/tour_payment_dashboard/batch', 'report/bank_payment_check', 'report/bank_payment_check/initiate', 'report/bank_payment_check/approve']],
                ['url' => 'tour/tour_payment_dashboard', 'label' => 'Tour payment dashboard', 'icon' => 'bi-cash-stack'],
                ['url' => 'tour/tour_payment_dashboard/payables', 'label' => 'Supplier payables', 'icon' => 'bi-stack'],
                ['url' => 'report/bank_payment_check', 'label' => 'Bank payment check', 'icon' => 'bi-bank'],
                ['url' => 'report/bank_payment_check/finalized_records', 'label' => 'Finalized payments', 'icon' => 'bi-check2-circle'],
                ['url' => 'accounts/daily_bank_rec', 'label' => 'Daily bank reconciliation', 'icon' => 'bi-journal-check'],
                ['url' => 'accounts/bank_reconcil', 'label' => 'Bank reconciliation (earlier screen)', 'icon' => 'bi-journal-check'],
                ['url' => 'accounts/voucher_payments/0', 'label' => 'Voucher payments', 'icon' => 'bi-receipt'],
                ['url' => 'accounts/journal_entry', 'label' => 'Journal entry', 'icon' => 'bi-journal-text'],
                ['label' => 'Finance masters', 'icon' => 'bi-collection', 'items' => [
                    ['url' => 'master_files/supplier_bank', 'label' => 'Supplier bank details', 'icon' => 'bi-credit-card-2-front'],
                    ['url' => 'master_files/bank_accounts', 'label' => 'Avenir bank accounts', 'icon' => 'bi-safe'],
                    ['url' => 'master_files/daily_usd_rate', 'label' => "Today's USD rate", 'icon' => 'bi-currency-dollar'],
                ]],
            ],
        ],
        // tf-home-r3: the Master Files desk - its dropdown is the sidebar's own "Master Files" menu for this user
        // (same entries, same links, same privileges), built in forUser() from the menu rows the user holds
        'masters' => [
            'name' => 'Master Files', 'tone' => 'darkburgundy', 'icon' => 'masters', 'category' => 'Master Files',
            'items' => [],
        ],
        'approver' => [
            'name' => 'Approver Desk', 'tone' => 'burgundy', 'icon' => 'approver',
            'items' => [
                ['url' => 'tour/tour_accounts/payment_approval_v2', 'label' => 'Payment approval', 'icon' => 'bi-shield-check', 'sub' => 'approve or reject payment requests'],
                ['url' => 'master_files/hotel_rate_approval', 'label' => 'Hotel rate approval', 'icon' => 'bi-patch-check'],
            ],
        ],
    ];

    /** The links under the desks: the Pilot task dashboard for everybody, the rest by privilege. */
    public const LINKS = [
        ['url' => 'dashboard2/tiles', 'label' => 'Assigned Functions', 'icon' => 'bi-list-task', 'open' => true],
    ];

    /**
     * @param array $menu MenuLoader::loadMenuData() — allowed_menu_list and user_role
     * @return array ['desks' => [...], 'links' => [...]] with only the entries the user may open
     */
    public static function forUser(array $menu): array
    {
        $held = [];
        foreach ((array) ($menu['allowed_menu_list'] ?? []) as $m) {
            $u = trim((string) (is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '')), '/ ');
            if ($u !== '') {
                $held[$u] = true;
            }
        }
        $admin = (int) ($menu['user_role'] ?? 0) === 1;
        $may = static function (array $it) use ($held, $admin): bool {
            if ($admin) {
                return true;
            }
            $page = trim(strtok((string) ($it['url'] ?? ''), '?'), '/');
            foreach (array_merge(isset($it['gate']) ? [$it['gate']] : [], $it['any'] ?? []) as $u) {
                if (isset($held[trim($u, '/')])) {
                    return true;
                }
            }
            // the route filter's own rule: the page itself, or a page under a held menu URL
            foreach ($held as $u => $_) {
                if ($page === $u || strpos($page, $u . '/') === 0) {
                    return true;
                }
            }
            return false;
        };
        $filter = static function (array $items) use (&$filter, $may): array {
            $out = [];
            foreach ($items as $it) {
                if (isset($it['items'])) {
                    $kids = $filter($it['items']);
                    if ($kids) {
                        $it['items'] = $kids;
                        $out[] = $it;
                    }
                } elseif ($may($it)) {
                    $out[] = $it;
                }
            }
            return $out;
        };
        $desks = [];
        foreach (self::DESKS as $key => $d) {
            $d['key'] = $key;
            if (isset($d['category'])) {
                $d['items'] = self::fromCategory($menu, $d['category']);   // already only what the user holds
                if ($key === 'masters') {
                    $d['items'] = self::hotelMasterGroup($d['items'], $may);   // tf-resdesk-r1: Masters → Hotel Master
                }
            } else {
                $d['items'] = $filter($d['items']);
            }
            $desks[] = $d;
        }
        // display order: Tour, Reservation, Transport on top; Master Files, Finance, Approver below
        $order = ['tour' => 1, 'reservation' => 2, 'transport' => 3, 'masters' => 4, 'finance' => 5, 'approver' => 6];
        usort($desks, static fn ($x, $y) => ($order[$x['key']] ?? 9) <=> ($order[$y['key']] ?? 9));
        $links = [];
        foreach (self::LINKS as $l) {
            if (!empty($l['open']) || $may($l)) {
                $links[] = $l;
            }
        }
        if ($admin) {
            $links[] = ['url' => 'dashboard', 'label' => 'Administration dashboard', 'icon' => 'bi-speedometer2'];
        }
        return ['desks' => $desks, 'links' => $links];
    }

    /**
     * tf-resdesk-r1 (25 Oct 2026, Shantha): on the Master Files desk the hotel masters sit together under one "Hotel Master"
     * group — Hotel Rates (hotel_rate_v2) and Room Types (room_type) among them, moved here from the Reservation Desk — each
     * with its own URL and privilege as before. Navigation only: no page, route, record or privilege changes.
     */
    private static function hotelMasterGroup(array $items, \Closure $may): array
    {
        $hotel = ['master_files/hotel_rate_v2', 'master_files/room_type', 'master_files/hotel_rate', 'master_files/hotel_rate/all',
                  'master_files/hotel_rate_approval', 'master_files/hotel_work'];
        $labels = ['master_files/hotel_rate_v2' => 'Hotel Rates', 'master_files/room_type' => 'Room Types'];
        $group = ['label' => 'Hotel Master', 'icon' => 'bi-building', 'items' => []];
        $out = [];
        $at = null;
        foreach ($items as $it) {
            $u = trim(strtok((string) ($it['url'] ?? ''), '?'), '/');
            if (in_array($u, $hotel, true)) {
                $it['label'] = $labels[$u] ?? $it['label'];
                $group['items'][$u] = $it;
                $at ??= count($out);
                continue;
            }
            $out[] = $it;
        }
        foreach (['master_files/hotel_rate_v2' => ['label' => 'Hotel Rates', 'icon' => 'bi-building', 'sub' => 'contracts, seasons, what a night costs'],
                  'master_files/room_type' => ['label' => 'Room Types', 'icon' => 'bi-grid-3x2-gap']] as $u => $it) {
            if (!isset($group['items'][$u]) && $may(['url' => $u])) {
                $group['items'][$u] = $it + ['url' => $u];
            }
        }
        if (!$group['items']) {
            return $out;
        }
        // Hotel Rates and Room Types first, then the rest in the order the sidebar had them
        uksort($group['items'], static fn ($a, $b) => array_search($a, $hotel, true) <=> array_search($b, $hotel, true));
        $group['items'] = array_values($group['items']);
        array_splice($out, $at ?? 0, 0, [$group]);
        return $out;
    }

    /** The sidebar's entries of one menu category for this user: the rows the user holds that the sidebar shows. */
    private static function fromCategory(array $menu, string $category): array
    {
        $ids = [];
        foreach ((array) ($menu['allowed_categories'] ?? []) as $c) {
            $c = (array) $c;
            if (strcasecmp(trim((string) ($c['category'] ?? '')), $category) === 0) {
                $ids[(string) ($c['cat_id'] ?? '')] = true;
            }
        }
        $out = [];
        foreach ((array) ($menu['allowed_menu_list'] ?? []) as $m) {
            $m = (array) $m;
            if (!isset($ids[(string) ($m['cat_id'] ?? '')]) || (int) ($m['show_in_menu_bar'] ?? 0) === 1) {
                continue;   // another category, or an action right the sidebar does not show
            }
            $url = trim((string) ($m['url'] ?? ''), '/ ');
            if ($url !== '') {
                $out[] = ['url' => $url, 'label' => (string) ($m['menu'] ?? $url), 'icon' => 'bi-folder2-open'];
            }
        }
        return $out;
    }

    /** Every page URL a desk can link to (for the coverage check). */
    public static function allUrls(): array
    {
        $out = [];
        $walk = static function (array $items) use (&$walk, &$out): void {
            foreach ($items as $it) {
                isset($it['items']) ? $walk($it['items']) : $out[] = $it['url'];
            }
        };
        foreach (self::DESKS as $d) {
            $walk($d['items']);
        }
        foreach (self::LINKS as $l) {
            $out[] = $l['url'];
        }
        return $out;
    }
}
