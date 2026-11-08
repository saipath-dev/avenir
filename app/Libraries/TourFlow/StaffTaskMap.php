<?php

namespace App\Libraries\TourFlow;

/**
 * tf-panel2-r1 (8 Nov 2026, Shantha) — the Staff Access Control Panel's work map.
 *
 * Every page and action in menu_list is placed in exactly one work category and one task, with a plain work description:
 *   Travel Sales · Ground Handling · Accounts, and a fourth section "Shared & Administration" for what fits none of them.
 * The map only ARRANGES the rows for the screen. It grants nothing: a person holds exactly the Open rows the Super Admin set for
 * them, one row at a time, with a reason (StaffAssignment). A screen the map does not name is still shown — in its category's
 * "Other screens" task — so nothing in the menu table is ever hidden from the panel.
 */
final class StaffTaskMap
{
    public const CATEGORIES = [
        'sales'  => ['title' => 'Travel Sales',    'desc' => 'Inquiries, quotations, costing, issuing quotations and confirmed tours'],
        'ground' => ['title' => 'Ground Handling', 'desc' => 'Hotels, transport, drivers and guides, tickets, safaris, excursions and arrivals'],
        'acc'    => ['title' => 'Accounts',        'desc' => 'Payables, payment requests, approvals, bank authorisation, accounting, reconciliation and financial reports'],
        'shared' => ['title' => 'Shared & Administration', 'desc' => 'Tools every desk may use, and the System Administrator\'s own screens'],
    ];

    /** The issue-quotation action right (tf-panel2-r1): checked by the server when a quotation version is issued or e-mailed. */
    public const ISSUE_URL  = 'act:quote.issue';
    public const ISSUE_NAME = 'Action: Quotation - issue to the agent';

    /**
     * category => [ task key => [title, description, [url => work description]] ]. Order is the order on screen.
     * A URL ending in '*' matches that prefix (used only for the Super Admin strip and inventory).
     */
    public const TASKS = [
        'sales' => [
            'inquiries' => ['Tour inquiries', 'Record and manage inquiries from agents and clients', [
                'tour/intake' => 'Open the Tour Inquiry workspace: record inquiries, read e-mails into them and build the itinerary',
                'inquiries'   => 'Start a new inquiry from the main menu (older inquiry list)',
                'booking'     => 'Booking entry screen (older booking form)',
                'booking_dashboard' => 'Booking dashboard: bookings in progress (older screen)',
            ]],
            'quotes' => ['Quotations and costing', 'Prepare itineraries, costings and quotation versions', [
                'tour/pricing'          => 'See and set the markup and selling price of an inquiry',
                'tour/tour_costing'     => 'Tour costing sheet: cost lines behind a quotation',
                'tour/intake/mileage'   => 'Override the kilometres used for a quotation (Management)',
                'tour/intake/recalculate' => 'Recalculate a saved costing at today\'s master rates',
                'dashboard2/tour_search/new_quotation' => 'Start a new quotation or booking from Ground Handling (older screen)',
                'tour/quotation'        => 'New quotation form (older screen)',
                'tour/tour'             => 'Tour quotation form (older screen)',
                'tour/tour_copy'        => 'Copy an existing tour into a new quotation',
                'reports/change-log'    => 'Quotation change log: who changed what on a quotation',
                'tour/pricing/discount' => 'Give a discount on a tour price (restricted)',
            ]],
            'issue' => ['Issue quotations', 'Issue an approved quotation version to the agent (by e-mail or marked as sent)', [
                self::ISSUE_URL => 'Issue a quotation version: it becomes the quotation on record and the inquiry is marked quoted',
            ]],
            'customers' => ['Customers and agents', 'Keep the agent and customer list used on inquiries', [
                'master_files/customer' => 'Customers and agents (consignees): add and correct agent details',
            ]],
            'confirmed' => ['Confirmed tours', 'Confirm tours and follow them until departure', [
                'tour/manage'          => 'Tour Desk Admin: confirmed tours and their desks',
                'tour/booking_confirm' => 'Tour management: confirm bookings (older screen)',
                'tour/board'           => 'Tour Board: every confirmed tour and its open jobs',
                'tour/calendar'        => 'Tour calendar: tours by date',
                'report/confirmed_tours' => 'Confirmed tour report',
                'tour/tour_dashboard'  => 'Tour dashboard (older screen)',
                'tour/tour_details'    => 'Working dashboard: tours being worked on (older screen)',
                'tour/tour_details_search' => 'Search tours (older screen)',
                'tour/timeline'        => 'Tour timeline preview',
                'dashboard2/tour_search/tour_quotation' => 'Tour details view from Ground Handling',
                'dashboard2/tour_search/reservation_card' => 'Reservation card of a tour',
                'dashboard2/tour_search/itinerary_print' => 'Print the itinerary list',
                'dashboard2/tour_search/timeline' => 'Edit a confirmed tour (Tour Edit)',
            ]],
            'changes' => ['Tour changes and cancellations', 'Ask for changes, cancellations and invoice amendments on a sold tour', [
                'dashboard2/tour_search/inv_amendment'   => 'Request an invoice amendment on a tour',
                'dashboard2/tour_search/inv_cancellation' => 'Request an invoice cancellation on a tour',
                'act:amendment.request' => 'Action: request a tour amendment (approval is in Accounts)',
                'tour/tour_accounts/inv_send_report' => 'Accepted invoices: invoices sent to agents',
            ]],
        ],
        'ground' => [
            'home' => ['Ground Handling home', 'The Ground Handling tiles: Tour Search, Hotel Management and Transport', [
                'dashboard2/tiles' => 'Ground Handling tiles (Tour Search, Hotel Management, Transport) — one address for all three',
            ]],
            'hotels' => ['Hotel bookings', 'Arrange and track accommodation for confirmed tours', [
                'tour/hotel_bookings' => 'Hotel Bookings desk: book, confirm and track every night of a tour',
                'dashboard2/hotel_management/hotel_reservation' => 'Hotel reservation (older screen)',
                'dashboard2/hotel_management/hotel_booking' => 'Hotel confirmation (older screen)',
                'master_files/hotel_master' => 'Hotel master: hotels, contacts and room details',
                'master_files/hotel_work' => 'Hotel incomplete work: hotels missing rates or details',
                'master_files/room_type' => 'Room types',
                'tour/tour_accounts/hotel_rate_confirm' => 'Confirm the hotel rate used on a tour',
            ]],
            'rates' => ['Hotel rates and offers', 'Maintain and approve hotel rates and special offers (restricted)', [
                'master_files/hotel_rate_v2'      => 'Enter and change hotel rates',
                'master_files/hotel_rate_approval' => 'Approve hotel rates before they are used',
                'master_files/offer_selection'    => 'Tactical / early-bird offer selection',
            ]],
            'transport' => ['Transport, drivers and guides', 'Arrange vehicles and allocate drivers and guides', [
                'tour/transport_ops' => 'Transport Operations desk: vehicles, drivers and guides for each tour',
                'tour/transport_ops/crew_change' => 'Replace a driver or guide on a tour',
                'dashboard2/transport/driver_allow/tour_transport' => 'Driver allocation (older screen)',
                'dashboard2/transport/driver_allow/pending_tours' => 'Tours waiting for a driver',
                'dashboard2/transport/driver_allow/allocated_tours' => 'Tours with a driver allocated',
                'dashboard2/transport/transport_ongoing' => 'Ongoing tours on the road',
                'dashboard2/transport/transport_portal' => 'Driver / transport portal',
                'dashboard2/transport/driver_logsheet' => 'Driver log sheets',
                'dashboard2/transport/driver_report' => 'Driver log report',
                'dashboard2/transport/shop_allow' => 'Shop allocation for a tour',
                'master_files/crew_people' => 'Drivers and guides: the people, their licences and languages',
                'master_files/supplier/new_driver_index' => 'Driver master (older screen)',
                'master_files/supplier/all_drivers' => 'Driver report',
                'master_files/vehicle' => 'Vehicles',
            ]],
            'transport_rates' => ['Transport rates and crew costing', 'Rates, distances and allowances used to cost transport', [
                'master_files/transport_rates' => 'Transport rates per vehicle type',
                'master_files/mileage_allowances' => 'Mileage and allowances',
                'master_files/crew_bata' => 'Crew roles and quotation costing (bata)',
                'master_files/transfer_milages' => 'Routing registry: distances between places',
                'master_files/extra_kilometers' => 'Extra kilometres',
                'master_files/extra_charges' => 'Extra charge types',
                'master_files/carrier' => 'Carriers',
            ]],
            'driver_money' => ['Driver advances and settlements', 'Request driver advances and settle driver expenses (paid through Accounts)', [
                'tour/transport_ops/driver_advance' => 'Driver advance on the Transport desk',
                'dashboard2/transport/driver_allow/advance_request' => 'Driver advance request (older screen)',
                'dashboard2/transport/driver_advance' => 'Driver advances (older screen)',
                'dashboard2/transport/driver_expense' => 'Driver expenses',
                'dashboard2/transport/driver_settlement' => 'Driver settlement',
                'dashboard2/transport/driver_settlement/driver_settlement_request' => 'Driver settlement request',
                'dashboard2/transport/fund_return' => 'Fund returned by a driver',
            ]],
            'tickets' => ['Tickets and activities', 'Buy and allocate attraction tickets and paid activities', [
                'tour/ticket_stock' => 'Ticket stock: tickets bought, allocated and used',
                'master_files/paid_services' => 'Paid activities and services master',
            ]],
            'safaris' => ['Safaris, excursions and places', 'Places to visit, safaris, excursions and meals', [
                'master_files/entrance_master' => 'Place to Visit / entrance fee master (safaris, jeeps, child rules)',
                'master_files/entrance_fee' => 'Place to visit (older screen)',
                'master_files/excursion_rates' => 'Excursion rates',
                'master_files/free_visits' => 'Free visits',
                'master_files/meal_master' => 'Meal master',
                'master_files/shop_registration' => 'Shop registration',
            ]],
            'arrivals' => ['Arrival and ground arrangements', 'Coordinate arrivals, tour documents and photos', [
                'tour/tour_arrival' => 'Arrival notification',
                'tour/tour_dashboard/documentation/documentation_report' => 'Tour documentation',
                'master_files/tour_photos' => 'Tour photos',
            ]],
            'suppliers' => ['Suppliers', 'The supplier list (bank details are in Accounts)', [
                'master_files/supplier' => 'Suppliers: add and correct supplier details',
                'master_files/supplier/all' => 'Supplier report',
            ]],
        ],
        'acc' => [
            'payables' => ['Supplier payables', 'Review supplier balances, forecasts and what is due', [
                'tour/tour_payment_dashboard' => 'Tour payment dashboard: every payment of a tour and its status',
                'tour/tour_payment_dashboard/payables' => 'Supplier payables: what is owed to each supplier',
                'tour/tour_accounts' => 'Payment forecast report',
                'tour/tour_payment_forcast' => 'Payment forecast',
            ]],
            'prep' => ['Payment preparation', 'Prepare payment requests and supplier payment batches', [
                'tour/tour_payment_dashboard/request' => 'Make or cancel a tour payment request',
                'tour/tour_payment_dashboard/batch' => 'Create a supplier payment batch (management)',
                'tour/special_payment_requests' => 'Special tour payment requests',
                'tour/tour_accounts/payment_requested_dashboard' => 'Payment request report',
                'dashboard2/hotel_management/hotel_payment' => 'Hotel payment request (older screen)',
            ]],
            'approval' => ['Management approval', 'Approve payments — payment role A, held by one person only', [
                'tour/tour_accounts/payment_approval_v2' => 'Payment approval: approve or reject requested payments',
                'tour/tour_payment_dashboard/approve' => 'Approve a tour payment (management)',
                'tour/tour_accounts/payment_requested_dashboard_new' => 'Payment approval (older version 1)',
            ]],
            'initiation' => ['Bank payment initiation', 'Initiate approved payments at the bank — payment role B, held by one person only', [
                'report/bank_payment_check/initiate' => 'Initiate a bank payment (accountant)',
                'report/bank_payment_check' => 'Bank payment check report',
                'report/bank_payment_check/finalized_records' => 'Finalised bank payments',
            ]],
            'authorise' => ['Bank authorisation', 'Final authorisation of bank payments (Paid) — payment role C, held by one person only', [
                'report/bank_payment_check/approve' => 'Authorise a bank payment as Paid',
                'accounts/creditor_invoice/voucher_authentication' => 'Payment release / voucher authentication',
            ]],
            'vouchers' => ['Vouchers and postings', 'Create vouchers and post supplier, service-provider and expense entries', [
                'accounts/voucher_payments/0' => 'Voucher payments',
                'act:vouchers.create' => 'Action: create vouchers',
                'accounts/creditor_payment' => 'Creditor payment',
                'act:creditor_payment.post' => 'Action: post a creditor payment',
                'accounts/creditor_invoice' => 'Payment amendment (creditor invoice)',
                'act:creditor_invoice.post' => 'Action: post a payment amendment',
                'accounts/other_invoice' => 'Service provider invoice',
                'act:other_invoice.post' => 'Action: post a service provider invoice',
                'accounts/service_provider_payment' => 'Service provider payment',
                'act:service_provider_payment.post' => 'Action: post a service provider payment',
                'accounts/advance_payment' => 'Advance payments',
                'act:advance_payment.post' => 'Action: post an advance payment',
                'accounts/utility_bill' => 'Utility bill payment',
                'act:utility_bill.post' => 'Action: post / pay a utility bill',
                'accounts/utility_bill_entertainment' => 'Entertainment expenses',
                'act:entertainment.post' => 'Action: post an entertainment expense',
                'accounts/usd_withdrawal' => 'USD withdrawal',
                'act:usd_withdrawal.post' => 'Action: post a USD withdrawal',
                'accounts/visa_fees' => 'Visa fees',
            ]],
            'ledger' => ['Journals and the chart of accounts', 'Journal entries, notes and account maintenance', [
                'accounts' => 'Manage accounts',
                'accounts/journal_entry' => 'Journal entry',
                'act:journal.post' => 'Action: post a journal',
                'act:journal.correct' => 'Action: reverse a journal or change its date (reason required)',
                'act:records.delete' => 'Action: delete a posted record (reason required)',
                'accounts/debit_note' => 'Debit note',
                'act:debit_note.post' => 'Action: post a debit note',
                'accounts/credit_note' => 'Credit note',
                'act:credit_note.post' => 'Action: post a credit note',
            ]],
            'receivables' => ['Customer invoices and receipts', 'Invoice agents and record what they pay', [
                'accounts/debtor_invoice' => 'Debtor invoice',
                'act:debtor_invoice.post' => 'Action: post a debtor invoice / send the receipt',
                'accounts/debtor_payment' => 'Debtor payment (receipt)',
                'act:debtor_payment.post' => 'Action: post a debtor receipt',
            ]],
            'amend_approve' => ['Tour amendment approval', 'Approve and finalise amendments and cancellations asked for by sales', [
                'accounts/debtor_invoice/get_cancellation_request' => 'Tour amendment approval',
                'accounts/debtor_invoice/get_cancellation_request_final' => 'Tour amendment finalise',
                'accounts/debtor_invoice/get_cancellation' => 'Tour amendment report',
                'act:amendment.decide' => 'Action: approve / finalise a tour amendment',
            ]],
            'bank_details' => ['Bank accounts and bank details', 'Company bank accounts, supplier and crew bank details and their verification', [
                'master_files/bank_accounts' => 'Avenir bank accounts',
                'master_files/supplier_bank' => 'Supplier bank details',
                'master_files/supplier_bank/verify' => 'Verify a supplier bank account',
                'master_files/supplier_accounts' => 'Supplier bank account registry',
                'master_files/crew_people/bank' => 'Driver and guide bank details',
            ]],
            'reconcile' => ['Bank reconciliation', 'Reconcile bank statements (the new daily reconciliation is the next phase)', [
                'accounts/daily_bank_rec' => 'Daily bank reconciliation',
                'accounts/daily_bank_rec/prepare' => 'Daily bank reconciliation — prepare (import, match, propose)',
                'accounts/daily_bank_rec/review' => 'Daily bank reconciliation — review (confirm, approve, sign off)',
                'accounts/bank_reconcil' => 'Bank reconciliation (older screen)',
                'accounts/bank_reconcil/details' => 'Bank reconciliation details',
                'accounts/bank_reconcil/summary' => 'Bank reconciliation summary',
                'act:old_bankrec.change' => 'Action: change an old bank reconciliation',
                'accounts/bank_statement' => 'Bank statement',
            ]],
            'reports' => ['Financial reports', 'Ledgers, balances, voucher and expense reports', [
                'accounts/trial_balance' => 'Trial balance', 'accounts/trial_balance/report' => 'Trial balance by account', 'accounts/trial_balance/load_accountant_report' => 'Trial balance (new)',
                'accounts/profit_loss' => 'Profit and loss', 'accounts/balance_sheet' => 'Balance sheet', 'accounts/creditor_invoice/expenses_report' => 'Operational profit and loss',
                'accounts/cash_book' => 'Cash in hand', 'accounts/account_filter' => 'Ledger accounts', 'accounts/liability' => 'Liability report',
                'accounts/age_analysis' => 'Age analysis', 'accounts/age_analysis/creditor' => 'Creditor age analysis', 'accounts/outstanding_report' => 'Outstanding report',
                'accounts/journal_entry/report' => 'Journal entry report', 'accounts/journal_entry/edit_report' => 'Edit journal entry report',
                'report/voucher_report' => 'Voucher report', 'report/voucher_report/voucher_details_new' => 'New voucher report', 'report/voucher_report/cancel' => 'Voucher cancellation',
                'report/voucher_cancel_report' => 'Cancelled voucher report', 'accounts/voucher_payments/voucher_cancel' => 'Pending voucher report', 'accounts/voucher_audit' => 'Voucher audit',
                'accounts/other_invoice/report' => 'Service provider invoice report', 'accounts/service_provider_payment/report' => 'Service provider payment receipt',
                'accounts/creditor_invoice/report' => 'Creditor invoice report', 'accounts/debtor_invoice/report' => 'Debtor invoice report', 'accounts/debtor_payment/report' => 'Debtor receipt report',
                'accounts/advance_payment/report' => 'Advance payments report', 'accounts/utility_bill/report' => 'Utility bills report', 'accounts/utility_bill_entertainment/report' => 'Entertainment expense report',
                'accounts/expenses_chart' => 'Expenses chart', 'accounts/expenses_chart/report' => 'Expenses report', 'accounts/usd_withdrawal/history' => 'USD withdrawal history',
                'report/driver_ledger' => 'Driver ledger', 'report/petty_cash' => 'Petty cash report', 'report/acc_reservation_card_report' => 'Account reservation card',
                'accounts/debtor_invoice/arrival_report' => 'Arrival report (invoicing)',
            ]],
        ],
        'shared' => [
            'tools' => ['Shared tools', 'Used by every desk', [
                'dashboard' => 'Home dashboard',
                'chat' => 'Open chat',
                'master_files/daily_usd_rate' => 'Daily USD rate list',
                'home/usd_rate' => 'Enter the daily USD rate',
                'master_files/email_templates' => 'E-mail templates',
                'report/staff_work_descriptions' => 'Staff work descriptions report (Super Admin unless opened here)',
            ]],
            'inventory' => ['Inventory', 'Office stock: items, goods received and usage', [
                'inventory/*' => 'Inventory screen',
            ]],
        ],
    ];

    /** Shown in the locked strip: Super Admin only, never opened for staff (StaffAssignment::isProtected decides; this is the label). */
    public const ADMIN_TASK = ['admin', 'System Administrator only', 'Accounts, groups, menus, backups, Staff Assignment, Blocks & Investigations — never granted to staff'];

    /** Where a menu category's unnamed screens go ("Other screens" task in that work category). */
    public const FALLBACK = [
        'Tour' => 'sales', 'Ground Handling' => 'ground', 'Transport & Guide Services' => 'ground', 'Master Files' => 'ground',
        'Accounts' => 'acc', 'Accounts Reports' => 'acc', 'Pilot Report' => 'acc',
    ];

    /**
     * The whole map built from the live menu table: categories → tasks → screens (menu ids). Every live menu row appears once.
     * Rows that share one URL are one permission on the server; they are listed once, with the other menu ids in same_ids.
     */
    public static function build($db): array
    {
        $menus = StaffAssignment::menus($db);
        $byUrl = [];
        foreach ($menus as $m) {
            $byUrl[$m['url']][] = $m;
        }
        $index = [];   // url => [cat, task, description]
        $rank = [];    // url => position in the map (main screens first, as listed)
        $prefix = [];  // prefix => [cat, task, description]
        foreach (self::TASKS as $cat => $tasks) {
            foreach ($tasks as $tk => $t) {
                foreach ($t[2] as $url => $d) {
                    if (str_ends_with($url, '*')) {
                        $prefix[rtrim($url, '*')] = [$cat, $tk, $d];
                    } else {
                        $index[$url] = [$cat, $tk, $d];
                        $rank[$url] = count($rank);
                    }
                }
            }
        }
        $out = [];
        foreach (self::CATEGORIES as $ck => $c) {
            $out[$ck] = ['key' => $ck, 'title' => $c['title'], 'desc' => $c['desc'], 'tasks' => []];
            foreach (self::TASKS[$ck] ?? [] as $tk => $t) {
                $out[$ck]['tasks'][$tk] = ['key' => $tk, 'title' => $t[0], 'desc' => $t[1], 'screens' => []];
            }
        }
        $out['shared']['tasks']['admin'] = ['key' => 'admin', 'title' => self::ADMIN_TASK[1], 'desc' => self::ADMIN_TASK[2], 'locked' => true, 'screens' => []];
        foreach ($byUrl as $url => $rows) {
            $m = $rows[0];
            $names = array_values(array_unique(array_map(static fn ($r) => $r['name'], $rows)));
            if ($m['protected']) {
                [$cat, $tk, $desc] = ['shared', 'admin', $m['name'] . ' — System Administrator only'];
            } elseif (isset($index[$url])) {
                [$cat, $tk, $desc] = $index[$url];
            } else {
                $hit = null;
                foreach ($prefix as $p => $v) {
                    if (str_starts_with($url, $p)) {
                        $hit = $v;
                        $hit[2] = $m['name'];
                        break;
                    }
                }
                if ($hit) {
                    [$cat, $tk, $desc] = $hit;
                } else {
                    $cat = self::FALLBACK[$m['category']] ?? 'shared';
                    $tk = 'other';
                    $desc = $m['name'] . ' (not yet described — placed from its menu heading "' . $m['category'] . '")';
                    if (!isset($out[$cat]['tasks']['other'])) {
                        $out[$cat]['tasks']['other'] = ['key' => 'other', 'title' => 'Other screens', 'desc' => 'Screens from the menu table not yet placed in a task — shown so none is hidden', 'screens' => []];
                    }
                }
            }
            $out[$cat]['tasks'][$tk]['screens'][] = [
                'id' => $m['id'], 'same_ids' => array_values(array_map(static fn ($r) => $r['id'], array_slice($rows, 1))),
                'name' => implode(' / ', $names), 'url' => $url, 'desc' => $desc, 'menu_heading' => $m['category'],
                'role' => $m['role'], 'protected' => $m['protected'], 'right' => str_starts_with($url, 'act:'),
            ];
        }
        foreach ($out as &$c0) {
            foreach ($c0['tasks'] as &$t0) {
                usort($t0['screens'], static fn ($x, $y) => ($rank[$x['url']] ?? 9999) <=> ($rank[$y['url']] ?? 9999) ?: strcmp($x['name'], $y['name']));
            }
            unset($t0);
        }
        unset($c0);
        // drop tasks the live menu table has no screen for (e.g. an action not installed yet); keep the locked strip
        foreach ($out as $ck => &$c) {
            foreach ($c['tasks'] as $tk => $t) {
                if (!$t['screens'] && $tk !== 'admin') {
                    unset($c['tasks'][$tk]);
                }
            }
            $c['tasks'] = array_values($c['tasks']);
        }
        unset($c);
        return array_values($out);
    }

    /** Install: the issue-quotation action right, and carry-over for everyone who could issue before it existed. */
    public static function ensure($db): array
    {
        $notes = [];
        $m = $db->query('SELECT id FROM menu_list WHERE url = ? AND del = 0 LIMIT 1', [self::ISSUE_URL])->getRowArray();
        if (!$m) {
            $cat = $db->query("SELECT id FROM menu_category WHERE category = 'Tour' LIMIT 1")->getRowArray();
            if (!$cat) {
                return ['The Tour menu category was not found; the issue-quotation action was not added.'];
            }
            $order = (int) ($db->query('SELECT COALESCE(MAX(order_id), 0) + 1 AS n FROM menu_list WHERE cat_id = ?', [(int) $cat['id']])->getRowArray()['n'] ?? 1);
            $db->query('INSERT INTO menu_list (cat_id, name, user, del, url, order_id, show_in_menu_bar) VALUES (?, ?, 1, 0, ?, ?, 1)', [(int) $cat['id'], self::ISSUE_NAME, self::ISSUE_URL, $order]);
            $m = ['id' => (int) $db->insertID()];
            $notes[] = 'Action right "' . self::ISSUE_NAME . '" added (not shown in the menu bar).';
            if (!$db->query('SELECT id FROM user_privileges WHERE group_id = 1 AND menu_id = ? LIMIT 1', [(int) $m['id']])->getRowArray()) {
                $db->query('INSERT INTO user_privileges (group_id, user_id, menu_id) VALUES (1, 1, ?)', [(int) $m['id']]);
            }
            // carry-over: whoever could issue before (held Tour Inquiry on the panel) keeps issuing until the Super Admin changes it
            if (StaffAssignment::installed($db)) {
                $rows = $db->query("SELECT DISTINCT p.staff_id FROM tf_staff_permission p JOIN menu_list ml ON ml.id = p.menu_id
                                    WHERE p.state = 'open' AND TRIM(BOTH '/' FROM ml.url) = 'tour/intake'")->getResultArray();
                $now = date('Y-m-d H:i:s');
                foreach ($rows as $r) {
                    $db->query("INSERT IGNORE INTO tf_staff_permission (staff_id, menu_id, state, reason, set_by, set_at) VALUES (?, ?, 'open', ?, 'system', ?)",
                        [(int) $r['staff_id'], (int) $m['id'], 'Carried over: held Tour Inquiry before issuing became a separate permission', $now]);
                    StaffAssignment::log($db, (int) $r['staff_id'], null, 'opened', (int) $m['id'], 'not set', 'open', 'Carried over when issuing became a separate permission', 'Held Tour Inquiry, which issued quotations until now.', 'system', 'system');
                }
                if ($rows) {
                    $notes[] = count($rows) . ' staff member(s) holding Tour Inquiry keep issuing quotations (carried over, logged).';
                }
            }
        }
        return $notes ?: ['The issue-quotation action right is present.'];
    }
}
