<?php

namespace App\Libraries;

/**
 * tf-pilot-r1 — the common starting page ("Pilot", dashboard2/tiles) for every department: what the signed-in user's
 * department may work on, the tasks waiting for them, and their shortcuts and reports. Everything is derived from the
 * privileges the user's group holds (Settings → Group Privileges); nothing here grants anything. Every figure is a
 * count of records the user may open anyway; each query is independent and a failure only hides its own line.
 */
final class Pilot
{
    /** Shortcut candidates in display order: menu url => [label, icon]. Only the ones the user holds are shown. */
    private const SHORTCUTS = [
        'tour/tour_payment_dashboard'               => ['Tour Payment Dashboard', 'bi-cash-stack'],
        'tour/tour_accounts/payment_approval_v2'    => ['Payment approval', 'bi-shield-check'],
        'tour/tour_payment_dashboard/payables'      => ['Supplier Payables', 'bi-stack'],
        'report/bank_payment_check'                 => ['Bank Payment Check', 'bi-bank'],
        'report/bank_payment_check/finalized_records' => ['Finalized payments', 'bi-check2-circle'],
        'master_files/supplier_bank'                => ['Supplier bank details', 'bi-credit-card-2-front'],
        'master_files/bank_accounts'                => ['Avenir bank accounts', 'bi-safe'],
        'master_files/daily_usd_rate'               => ["Today's USD rate", 'bi-currency-dollar'],
        'tour/intake'                               => ['Tour Inquiry', 'bi-chat-left-text'],
        'tour/pricing'                              => ['Pricing', 'bi-calculator'],
        'tour/board'                                => ['Tour Board', 'bi-kanban'],
        'tour/hotel_bookings'                       => ['Hotel Bookings', 'bi-building'],
        'master_files/hotel_rate_v2'                => ['Hotel Rates', 'bi-tags'],
        'master_files/hotel_rate_approval'          => ['Hotel Rate Approval', 'bi-patch-check'],
        'master_files/hotel_work'                   => ['Hotel incomplete work', 'bi-list-task'],
        'tour/transport_ops'                        => ['Transport Operations', 'bi-truck'],
        'tour/transport_ops/driver_advance'         => ['Driver advance', 'bi-wallet2'],
        'dashboard2/transport/transport_ongoing'    => ['Ongoing tours', 'bi-signpost-2'],
        'master_files/crew_people'                  => ['Drivers & Guides', 'bi-people'],
        'accounts/bank_reconciliation'              => ['Bank Reconciliation', 'bi-journal-check'],
        'accounts/journal_entry'                    => ['Journal Entry', 'bi-journal-text'],
        'accounts/voucher_payments/0'               => ['Voucher Payments', 'bi-receipt'],
        'tour/tour_dashboard'                       => ['Tour Dashboard', 'bi-speedometer2'],
        'settings/user_account'                     => ['User Accounts', 'bi-person-gear'],
        'settings/group_privilege/0'                => ['Group Privileges', 'bi-key'],
    ];

    public static function forUser($db, array $menu): array
    {
        $held = [];
        $byUrl = [];
        foreach ((array) ($menu['allowed_menu_list'] ?? []) as $m) {
            $u = trim((string) (is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '')), '/');
            if ($u === '') {
                continue;
            }
            $held[$u] = true;
            $byUrl[$u] = ['name' => is_array($m) ? ($m['menu'] ?? $u) : ($m->menu ?? $u), 'cat_id' => (int) (is_array($m) ? ($m['cat_id'] ?? 0) : ($m->cat_id ?? 0))];
        }
        $admin = (int) ($menu['user_role'] ?? 0) === 1;
        $has = static fn (string ...$urls): bool => $admin || (bool) array_filter($urls, static fn ($u) => isset($held[$u]));
        $me = (string) session()->get('username');
        $today = date('Y-m-d');
        $soon = date('Y-m-d', strtotime('+14 days'));
        $count = static function (string $sql, array $bind = []) use ($db): ?int {
            try {
                $r = $db->query($sql, $bind)->getRowArray();
                return $r ? (int) reset($r) : 0;
            } catch (\Throwable $e) {
                return null;   // the line is simply not shown
            }
        };
        $tasks = [];
        $add = static function (?int $n, string $label, string $url, string $tone = 'pending') use (&$tasks): void {
            if ($n === null) {
                return;
            }
            $tasks[] = ['count' => $n, 'label' => $label, 'url' => $url, 'tone' => $n > 0 ? $tone : 'done'];
        };

        // --- requests (anyone who may make them: the r5 request privilege, or the dashboard itself before r5) ---
        $requestMenuExists = false;
        try {
            $requestMenuExists = (bool) $db->query("SELECT id FROM menu_list WHERE url = 'tour/tour_payment_dashboard/request' AND del = 0 LIMIT 1")->getRowArray();
        } catch (\Throwable $e) {
        }
        if ($has('tour/tour_payment_dashboard/request') || (!$requestMenuExists && $has('tour/tour_payment_dashboard'))) {
            $add($count("SELECT COUNT(*) FROM payment_request WHERE payment_request = 1 AND payment_request_user = ?", [$me]), 'My payment requests awaiting approval', 'tour/tour_payment_dashboard');
            $add($count("SELECT COUNT(*) FROM payment_request WHERE payment_request = 3 AND payment_request_user = ? AND payment_request_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)", [$me]), 'My requests rejected in the last 30 days (to revise)', 'tour/tour_payment_dashboard', 'blocked');
        }
        // --- management approval ---
        if ($has('tour/tour_payment_dashboard/approve', 'tour/tour_accounts/payment_approval_v2')) {
            $add($count("SELECT COUNT(*) FROM payment_request WHERE payment_request = 1"), 'Payment requests awaiting approval', $has('tour/tour_accounts/payment_approval_v2') ? 'tour/tour_accounts/payment_approval_v2' : 'tour/tour_payment_dashboard', 'action');
        }
        if ($has('tour/tour_payment_dashboard/batch')) {
            $add($count("SELECT COUNT(DISTINCT pr.hotel_acc) FROM payment_request pr JOIN tpd_request_ext x ON x.pr_id = pr.id WHERE pr.payment_request IN (4,6) AND pr.voucher_created = 0 AND x.batch_id IS NULL AND x.voucher_vnum IS NULL"), 'Suppliers with approved payments awaiting a batch', 'tour/tour_payment_dashboard/payables', 'action');
        }
        if ($has('master_files/hotel_rate_approval')) {
            $add($count("SELECT COUNT(*) FROM hr2_payment_hold WHERE status = 'awaiting'"), 'Hotel rate approvals waiting', 'master_files/hotel_rate_approval', 'action');
        }
        // --- accounts ---
        if ($has('report/bank_payment_check/initiate')) {
            $add($count("SELECT COUNT(*) FROM tpd_batch WHERE status = 'issued' AND parent_id IS NULL AND (mode IS NULL OR mode <> 'bulk_parent')"), 'Payment vouchers awaiting bank initiation', 'report/bank_payment_check', 'action');
            $add($count("SELECT COUNT(*) FROM tpd_batch WHERE status = 'issued' AND mode = 'bulk_parent'"), 'Bulk transfers awaiting initiation', 'tour/tour_payment_dashboard/payables?tab=batches', 'action');
        }
        if ($has('master_files/supplier_bank', 'master_files/supplier_bank/verify')) {
            $add($count("SELECT COUNT(DISTINCT pr.hotel_acc) FROM payment_request pr LEFT JOIN tpd_supplier_bank b ON CONVERT(b.sup_acc USING utf8mb4) COLLATE utf8mb4_bin = CONVERT(pr.hotel_acc USING utf8mb4) COLLATE utf8mb4_bin WHERE pr.payment_request IN (1,4,6) AND pr.voucher_created = 0 AND pr.type <> 'Transport Advance' AND (b.id IS NULL OR b.status <> 'verified')"), 'Suppliers with payments waiting whose bank account is not verified', 'master_files/supplier_bank', 'pending');
        }
        // --- bank authoriser ---
        if ($has('report/bank_payment_check/approve')) {
            $add($count("SELECT COUNT(*) FROM tpd_batch WHERE status = 'initiated' AND parent_id IS NULL"), 'Bank payments initiated, awaiting final authorisation (Paid)', 'report/bank_payment_check', 'action');
        }
        // --- tour reservations ---
        if ($has('tour/intake', 'inquiries')) {
            $add($count("SELECT COUNT(*) FROM inquiry WHERE reply_flag = 0"), 'Inquiries awaiting a reply', $has('tour/intake') ? 'tour/intake' : 'inquiries', 'action');
        }
        if ($has('tour/tour_dashboard', 'tour/pricing', 'tour/tour')) {
            $add($count("SELECT COUNT(DISTINCT q.quotation_id) FROM tour_quotations q JOIN consignee c ON c.tour_id = q.quotation_id WHERE q.confirm = 0 AND q.finalize = 1 AND c.start_date >= ? AND (c.tour_cancel IS NULL OR c.tour_cancel = 0)", [$today]), 'Upcoming quotations finalised, awaiting confirmation', $has('tour/tour_dashboard') ? 'tour/tour_dashboard' : 'tour/board', 'pending');
        }
        // --- hotel reservations ---
        if ($has('tour/hotel_bookings', 'dashboard2/hotel_management/hotel_reservation')) {
            $add($count("SELECT COUNT(DISTINCT c.tour_id) FROM consignee c JOIN tour_quotations q ON q.quotation_id = c.tour_id WHERE c.start_date BETWEEN ? AND ? AND (c.tour_cancel IS NULL OR c.tour_cancel = 0) AND q.hotel_acc IS NOT NULL AND q.hotel_acc <> '' AND (q.booking_confirmed_flag IS NULL OR q.booking_confirmed_flag <> 1)", [$today, $soon]), 'Tours starting within 14 days with hotel nights not yet confirmed', $has('tour/hotel_bookings') ? 'tour/hotel_bookings' : 'dashboard2/hotel_management/hotel_reservation', 'action');
        }
        // --- operations allocation ---
        if ($has('dashboard2/transport/driver_allow/tour_transport', 'tour/transport_ops')) {
            $add($count("SELECT COUNT(*) FROM consignee c WHERE c.start_date BETWEEN ? AND ? AND (c.tour_cancel IS NULL OR c.tour_cancel = 0) AND NOT EXISTS (SELECT 1 FROM driver_allocation d WHERE d.tour_id = c.tour_id)", [$today, $soon]), 'Tours starting within 14 days without a driver allocated', $has('tour/transport_ops') ? 'tour/transport_ops' : 'tour/board', 'action');   // the legacy Driver Allocation page errors on the development system (pre-existing), so the link goes to Transport Operations
        }
        if ($has('dashboard2/transport/transport_ongoing', 'tour/transport_ops', 'tour/board')) {
            $add($count("SELECT COUNT(*) FROM consignee c WHERE c.start_date <= ? AND c.end_date >= ? AND (c.tour_cancel IS NULL OR c.tour_cancel = 0)", [$today, $today]), 'Tours on the road today', $has('dashboard2/transport/transport_ongoing') ? 'dashboard2/transport/transport_ongoing' : 'tour/board', 'info');
        }
        // --- everyone with a tour view ---
        if ($has('tour/board', 'tour/tour_dashboard', 'tour/tour_details', 'dashboard2/tiles')) {
            $add($count("SELECT COUNT(*) FROM consignee c WHERE c.start_date BETWEEN ? AND ? AND (c.tour_cancel IS NULL OR c.tour_cancel = 0)", [$today, date('Y-m-d', strtotime('+7 days'))]), 'Tours starting in the next 7 days', $has('tour/board') ? 'tour/board' : 'dashboard2/tiles', 'info');
        }

        // shortcuts: the held entries of the curated list, in its order
        $shortcuts = [];
        foreach (self::SHORTCUTS as $url => [$label, $icon]) {
            if ($admin || isset($held[$url])) {
                $shortcuts[] = ['label' => $label, 'url' => $url, 'icon' => $icon];
            }
        }
        // reports: held menus in a report category or named as a report (the admin sees the list of every report)
        $reports = [];
        $reportCats = [];
        try {
            foreach ($db->query("SELECT id, category FROM menu_category")->getResultArray() as $c) {
                // "Pilot Report" is the payment desk's category, not a report category
                if (stripos((string) $c['category'], 'report') !== false && stripos((string) $c['category'], 'pilot') === false) {
                    $reportCats[(int) $c['id']] = true;
                }
            }
        } catch (\Throwable $e) {
        }
        foreach ($byUrl as $url => $m) {
            $isReportCat = isset($reportCats[$m['cat_id']]);
            if (($isReportCat || preg_match('/report|statement|ledger|audit|balance|trial|analysis|finalized/i', (string) $m['name'])) && !preg_match('/cancellation|release|amendment approval|finalize$|payment dashboard|payment requests/i', (string) $m['name'])) {
                $reports[] = ['label' => (string) $m['name'], 'url' => $url];
            }
        }
        usort($reports, static fn ($a, $b) => strcmp($a['label'], $b['label']));

        return [
            'department' => (string) ($menu['sidebar_employee_name_profile_name'] ?? ''),
            'user' => $me,
            'admin' => $admin,
            'tasks' => $tasks,
            'shortcuts' => array_slice($shortcuts, 0, 10),
            'reports' => $reports,
            'categories' => count((array) ($menu['allowed_categories'] ?? [])),
            'menus' => count($held),
        ];
    }
}
