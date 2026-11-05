<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r1 — who may do what on the Tour Payment Dashboard. A privilege is a menu entry granted to the user's
 * group (Settings → Group Privileges), the way every other privilege in Avenir works; the System Administrator
 * group (1) holds them all.
 *
 *   viewer         sees the dashboard (any payment role)                                tour/tour_payment_dashboard
 *   requester      makes / cancels payment requests, adds expenses (tf-paydesk-r5)       tour/tour_payment_dashboard/request
 *   approver       management: approves a request → it waits under its supplier in Supplier Payables
 *                  tour/tour_payment_dashboard/approve, or the Payment approval page itself (tour/tour_accounts/payment_approval_v2)
 *   payables       sees Supplier Payables (the approved payments waiting, and the batches)   tour/tour_payment_dashboard/payables (or any payment role)
 *   batch          management: selects a supplier's approved payments → batch → voucher      tour/tour_payment_dashboard/batch
 *   accountant     prepares and initiates the bank payment, records the bank reference
 *                  report/bank_payment_check/initiate, or the Bank Payment Check page itself (report/bank_payment_check)
 *   bank_approver  the only one who may click Paid                                   report/bank_payment_check/approve
 *   rate           enters / confirms Today's USD Exchange Rate                          master_files/daily_usd_rate (or Admin group 25)
 *   bank_master    manages the Avenir Bank Account Master                               master_files/bank_accounts
 *   supplier_bank  manages suppliers' bank details                                      master_files/supplier_bank
 *   verifier       verifies a supplier's bank details / confirms an unverified account  master_files/supplier_bank/verify
 *   offer          decides tactical / early bird rates at payment                        master_files/offer_selection (OfferRates)
 */
final class Perms
{
    private static ?array $menu = null;

    public const URLS = [
        // tf-paydesk-r5: seeing the dashboard (viewer) is separate from making / cancelling payment requests (requester)
        'viewer'        => ['tour/tour_payment_dashboard', 'tour/tour_payment_dashboard/request', 'tour/tour_payment_dashboard/approve', 'tour/tour_accounts/payment_approval_v2', 'tour/tour_payment_dashboard/payables', 'tour/tour_payment_dashboard/batch', 'report/bank_payment_check', 'report/bank_payment_check/initiate', 'report/bank_payment_check/approve'],
        'requester'     => ['tour/tour_payment_dashboard/request'],
        'approver'      => ['tour/tour_payment_dashboard/approve', 'tour/tour_accounts/payment_approval_v2'],
        // tf-paydesk-r2: Supplier Payables (approved payments waiting under their supplier) and batch creation (management)
        'payables'      => ['tour/tour_payment_dashboard/payables', 'tour/tour_payment_dashboard/batch', 'tour/tour_payment_dashboard/approve', 'tour/tour_accounts/payment_approval_v2', 'report/bank_payment_check', 'report/bank_payment_check/initiate', 'report/bank_payment_check/approve'],
        'batch'         => ['tour/tour_payment_dashboard/batch'],
        'accountant'    => ['report/bank_payment_check/initiate', 'report/bank_payment_check'],
        'bank_approver' => ['report/bank_payment_check/approve'],
        'rate'          => ['master_files/daily_usd_rate'],
        'bank_master'   => ['master_files/bank_accounts'],
        'supplier_bank' => ['master_files/supplier_bank', 'master_files/supplier'],
        'verifier'      => ['master_files/supplier_bank/verify'],
        'offer'         => ['master_files/offer_selection'],
    ];

    public static function menu(): array
    {
        if (self::$menu === null) {
            try {
                self::$menu = (new \App\Libraries\MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                self::$menu = ['user_role' => (int) session()->get('group_id'), 'allowed_menu_list' => []];
            }
        }
        return self::$menu;
    }

    public static function isAdmin(): bool
    {
        return (int) (self::menu()['user_role'] ?? 0) === 1;
    }

    public static function can(string $what): bool
    {
        if (self::isAdmin()) {
            return true;
        }
        $urls = self::URLS[$what] ?? [];
        if ($what === 'rate' && (int) (self::menu()['user_role'] ?? 0) === 25) {
            return true;   // the Admin group is asked for the daily rate at login
        }
        foreach ((array) (self::menu()['allowed_menu_list'] ?? []) as $m) {
            $u = trim((string) (is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '')), '/');
            if (in_array($u, $urls, true)) {
                return true;
            }
        }
        return false;
    }

    /** Every answer at once, for the page. */
    public static function all(): array
    {
        $out = [];
        foreach (array_keys(self::URLS) as $k) {
            $out[$k] = self::can($k);
        }
        $out['admin'] = self::isAdmin();
        return $out;
    }

    public static function user(): string
    {
        return (string) (session()->get('username') ?? '');
    }

    public static function userId(): ?int
    {
        $id = session()->get('user_id');
        return $id === null ? null : (int) $id;
    }
}
