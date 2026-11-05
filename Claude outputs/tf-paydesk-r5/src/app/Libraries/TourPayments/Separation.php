<?php

namespace App\Libraries\TourPayments;

/**
 * tf-paydesk-r5 — separation of duties, enforced on the server at every step:
 *   a user never approves, batches, initiates or finally authorises (Paid) a payment they REQUESTED themselves;
 *   a user never finally authorises (Paid) a payment they INITIATED themselves.
 * The System Administrator is not exempt. Every refusal is written to the audit record (separation_blocked).
 */
final class Separation
{
    public const STEPS = ['approve' => 'approve', 'batch' => 'batch', 'bulk' => 'batch (bulk payment)', 'initiate' => 'initiate the bank payment for', 'paid' => 'finally authorise (Paid)'];

    /** The requester of each request id: [pr_id => ['user' => …, 'manual_id' => …]]. */
    private static function requesters($db, array $prIds): array
    {
        $prIds = array_values(array_unique(array_filter(array_map('intval', $prIds))));
        if (!$prIds) {
            return [];
        }
        $out = [];
        foreach ($db->query("SELECT id, manual_id, payment_request_user FROM payment_request WHERE id IN (" . implode(',', $prIds) . ")")->getResultArray() as $r) {
            $out[(int) $r['id']] = ['user' => trim((string) $r['payment_request_user']), 'manual_id' => (string) $r['manual_id']];
        }
        return $out;
    }

    /** Returns the refusal message, or null when the step is allowed for this user. */
    public static function check($db, string $step, array $prIds, ?int $vnum = null, ?string $user = null): ?string
    {
        $me = trim((string) ($user ?? Perms::user()));
        if ($me === '') {
            return null;
        }
        $mine = [];
        foreach (self::requesters($db, $prIds) as $id => $r) {
            if ($r['user'] !== '' && strcasecmp($r['user'], $me) === 0) {
                $mine[] = $r['manual_id'] . ' (request #' . $id . ')';
            }
        }
        $why = null;
        if ($mine) {
            $why = 'Separation of duties: you made the payment request for ' . implode(', ', array_unique($mine)) . ' yourself, so you cannot ' . (self::STEPS[$step] ?? $step) . ' it. Another authorised user must do this step.';
        } elseif ($step === 'paid' && $vnum) {
            $init = $db->query("SELECT initiated_by FROM tpd_bank_initiation WHERE vnum = ?", [$vnum])->getRowArray();
            if ($init && strcasecmp(trim((string) $init['initiated_by']), $me) === 0) {
                $why = 'Separation of duties: you initiated the bank payment for Payment Voucher No. ' . $vnum . ' yourself, so you cannot finally authorise (Paid) it. The bank authoriser must be a different user.';
            }
        }
        if ($why !== null) {
            try {
                Audit::log($db, ['action' => 'separation_blocked', 'user' => $me, 'ref_type' => $vnum ? 'voucher' : 'request', 'ref_id' => $vnum ?: implode(',', $prIds), 'vnum' => $vnum, 'reason' => $why, 'new' => ['step' => $step, 'pr_ids' => array_values($prIds)]]);
            } catch (\Throwable $e) {
            }
        }
        return $why;
    }

    /** The request ids behind a voucher (its batch's items, or the desk's request record). */
    public static function requestsOfVoucher($db, int $vnum): array
    {
        try {
            $ids = array_map(static fn ($r) => (int) $r['pr_id'], $db->query("SELECT pr_id FROM tpd_request_ext WHERE voucher_vnum = ?", [$vnum])->getResultArray());
            if (!$ids) {
                $b = Batches::byVnum($db, $vnum);
                if ($b) {
                    $ids = array_map(static fn ($r) => (int) $r['pr_id'], $db->query("SELECT pr_id FROM tpd_request_ext WHERE batch_id = ?", [(int) $b['id']])->getResultArray());
                }
            }
            return $ids;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
