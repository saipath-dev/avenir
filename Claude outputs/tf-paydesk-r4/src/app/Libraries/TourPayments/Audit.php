<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r1 — the audit trail: one row per action, appended, never updated or deleted from the application.
 * Values before and after are kept as text (JSON for structured values) so a reader sees exactly what changed.
 */
final class Audit
{
    public static function log($db, array $f): ?int
    {
        try {
            if (!$db->query("SHOW TABLES LIKE 'tpd_audit'")->getResultArray()) {
                return null;
            }
            $enc = static function ($v) {
                if ($v === null || $v === '') {
                    return null;
                }
                return is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            };
            $ip = null;
            try {
                $ip = service('request')->getIPAddress();
            } catch (\Throwable $e) {
                $ip = null;
            }
            $db->table('tpd_audit')->insert([
                'at'         => date('Y-m-d H:i:s') . '.' . substr((string) sprintf('%03d', (int) ((microtime(true) - floor(microtime(true))) * 1000)), 0, 3),
                'user'       => (string) ($f['user'] ?? Perms::user()),
                'user_id'    => $f['user_id'] ?? Perms::userId(),
                'tour_id'    => isset($f['tour_id']) ? (string) $f['tour_id'] : null,
                'manual_id'  => isset($f['manual_id']) ? (string) $f['manual_id'] : null,
                'action'     => (string) $f['action'],
                'ref_type'   => $f['ref_type'] ?? null,
                'ref_id'     => isset($f['ref_id']) ? (string) $f['ref_id'] : null,
                'vnum'       => isset($f['vnum']) && $f['vnum'] !== '' ? (int) $f['vnum'] : null,
                'invoice_no' => $f['invoice_no'] ?? null,
                'prev_value' => $enc($f['prev'] ?? null),
                'new_value'  => $enc($f['new'] ?? null),
                'reason'     => isset($f['reason']) ? mb_substr((string) $f['reason'], 0, 500) : null,
                'usd_rate'   => isset($f['usd_rate']) && $f['usd_rate'] !== '' && $f['usd_rate'] !== null ? (float) $f['usd_rate'] : null,
                'bank_account' => $f['bank_account'] ?? null,
                'bank_ref'   => $f['bank_ref'] ?? null,
                'ip'         => $ip,
            ]);
            return (int) $db->insertID();
        } catch (\Throwable $e) {
            log_message('error', 'tpd audit: ' . $e->getMessage());
            return null;
        }
    }

    /** The trail of one tour, newest first. */
    public static function forTour($db, string $tourId, int $limit = 500): array
    {
        try {
            return $db->query("SELECT * FROM tpd_audit WHERE tour_id = ? ORDER BY at DESC, id DESC LIMIT " . (int) $limit, [$tourId])->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function forVoucher($db, int $vnum): array
    {
        try {
            return $db->query("SELECT * FROM tpd_audit WHERE vnum = ? ORDER BY at ASC, id ASC", [$vnum])->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Plain words for an action code, for the history list. */
    public const LABELS = [
        'rate_confirmed'       => "Today's USD rate confirmed",
        'request_made'         => 'Payment requested',
        'request_balance'      => 'Balance payment requested',
        'request_cancelled'    => 'Payment request cancelled',
        'approved'             => 'Approved for payment (management)',
        'rejected'             => 'Payment request rejected',
        'voucher_issued'       => 'Payment voucher issued',
        'bank_initiated'       => 'Bank payment initiated',
        'paid'                 => 'Paid — final bank approval',
        'voucher_reversed'     => 'Voucher cancelled / reversed',
        'remittance_prepared'  => 'Remittance advice prepared',
        'remittance_sent'      => 'Remittance advice e-mailed to the supplier',
        'remittance_failed'    => 'Remittance e-mail failed',
        'expense_added'        => 'Expense added',
        'expense_cancelled'    => 'Expense cancelled',
        'document_added'       => 'Document attached',
        'invoice_set'          => 'Supplier invoice number recorded',
        'due_date_set'         => 'Due date set',
        'supplier_bank_saved'  => 'Supplier bank details saved',
        'supplier_bank_verified' => 'Supplier bank details verified',
        'supplier_bank_confirmed' => 'Unverified supplier bank account confirmed for this payment',
        'bank_account_saved'   => 'Avenir bank account saved',
        'offer_choice'         => 'Payment rate chosen (published / tactical / early bird)',
        'setup'                => 'Tour Payment Desk set up',
        // tf-paydesk-r2
        'batch_created'        => 'Supplier payment batch created (approved payments → Payment Voucher No.)',
        'fx_posted'            => 'Exchange-rate difference posted to the ledger (gain / loss accounts, per tour)',
        'rate_entered'         => 'Payment-date USD rate entered on the bank payment page',
        // tf-paydesk-r3
        'accumulation_bypassed' => 'Paid individually — the accumulation stage was bypassed',
        'bank_account_changed' => 'Avenir paying account changed (original → new, with the reason)',
        'account_confirmed'    => 'Avenir paying account confirmed',
        'fx_mapping_saved'     => 'Exchange gain / loss ledger accounts mapped',
        // tf-paydesk-r4
        'ledger_mapping_saved' => 'Ledger mapping saved (exchange gain / loss, bank charges)',
        'charges_posted'       => 'Bank charges posted to the ledger (Bank Charges account, per tour)',
        'ledger_reconciled'    => 'Ledger reconciled to the bank debit (payment + charges)',
        'voucher_issue_failed' => 'Voucher could not be issued (database refused the number) — this call\'s writes taken back',
    ];
}
