<?php

namespace App\Libraries\TourPayments;

use CodeIgniter\HTTP\IncomingRequest;

/**
 * tf-paydesk-r7d (17 Oct 2026) — the Payment Desk's own CSRF check.
 *
 * The application's global CSRF filter is off (app/Config/Filters.php); this does not turn it on. The two desk controllers
 * (tour/tour_payment_dashboard/... and tour/payment_desk/...) call it for every state-changing request (POST, PUT, PATCH,
 * DELETE) before anything else runs. The request must carry the token the page was given (csrf_hash(): form field
 * csrf_test_name, header X-CSRF-TOKEN, or the JSON body), and it must equal the token CodeIgniter's Security service holds
 * for this browser (cookie or session, as configured in app/Config/Security.php).
 *
 * The token is compared, never regenerated here, so one page can send several requests — one after the other or at the
 * same moment — with the token it was given; a page left open after the token expired is told to reload. Token values are
 * never written to the audit trail or the log.
 *
 * tf-paydesk-r7e (17 Oct 2026): the same check, through guard(), as the first step of the payment endpoints outside the
 * two desk controllers — tour/tour_payment_dashboard/save_payment and /recalc_hotel_cost; tour/tour_accounts/payment_request
 * and /driver_advance_payment_request (the payment-request form); master_files/offer_selection/choose, /reviewed, /release
 * and /return (the hotel-offer actions); report/bank_payment_check/mark_finalized and /bulk_finalize. Each endpoint keeps
 * its own privilege checks, and Bank Payment Check keeps its closure while the desk is installed, after this check.
 *
 * tf-paydesk-r7f (17 Oct 2026): the same guard() as the first step of the remaining payment-chain actions — accounts/voucher_auth/
 * update (authorisation and its journals); tour/tour_accounts/update_payment_request_status, /bulk_update_payment_request_status,
 * /create_bulk_voucher (Payment approval); /payment_request_final, /cancel_payment_request, /transport_payment_request,
 * /lunch_pay, /accept_approved_expenses, /reject_approved_expenses, /finalize_driver_expenses. The Payment Desk runs some of
 * these in-process (TourPaymentDeskController::callExisting); forward() hands them the token the desk request carried.
 */
final class Csrf
{
    public const METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** null when the request may go on; otherwise 'missing', 'mismatch' or 'no_token' (the browser holds no token). */
    public static function problem(IncomingRequest $req): ?string
    {
        if (!in_array(strtoupper($req->getMethod()), self::METHODS, true)) {
            return null;
        }
        $cfg = config(\Config\Security::class);
        $posted = self::posted($req, (string) $cfg->tokenName, (string) $cfg->headerName);
        if ($posted === null || $posted === '') {
            return 'missing';
        }
        try {
            $expected = (string) (service('security')->getHash() ?? '');
        } catch (\Throwable $e) {
            $expected = '';
        }
        if (!empty($cfg->tokenRandomize)) {
            $expected = (string) (self::derandomize($expected) ?? '');
            $posted = self::derandomize($posted);
            if ($posted === null) {
                return 'mismatch';
            }
        }
        if ($expected === '') {
            return 'no_token';
        }
        return hash_equals($expected, $posted) ? null : 'mismatch';
    }

    /** The token the request carries: form field, then header, then a JSON body. Anything that is not a plain string counts as missing. */
    private static function posted(IncomingRequest $req, string $name, string $header): ?string
    {
        $v = $req->getPost($name);
        if (is_string($v) && $v !== '') {
            return $v;
        }
        $h = $req->getHeaderLine($header);
        if ($h !== '') {
            return $h;
        }
        if (stripos($req->getHeaderLine('Content-Type'), 'json') !== false) {
            try {
                $j = json_decode((string) $req->getBody(), true);
            } catch (\Throwable $e) {
                $j = null;
            }
            if (is_array($j) && isset($j[$name]) && is_string($j[$name]) && $j[$name] !== '') {
                return $j[$name];
            }
        }
        return null;
    }

    /** CodeIgniter's randomised token form (tokenRandomize = true): hex of (hash XOR key) . key. Off on both systems today. */
    private static function derandomize(string $token): ?string
    {
        if ($token === '' || strlen($token) % 2 !== 0 || !ctype_xdigit($token)) {
            return null;
        }
        $bin = hex2bin($token);
        if ($bin === false || strlen($bin) < 2 || strlen($bin) % 2 !== 0) {
            return null;
        }
        $half = intdiv(strlen($bin), 2);
        return bin2hex(substr($bin, 0, $half) ^ substr($bin, $half));
    }

    /**
     * tf-paydesk-r7e: the check for one endpoint outside the two desk controllers, called as the action's first statement.
     * Returns null when the request may go on, otherwise the refusal (HTTP 403, JSON) after its audit line.
     */
    public static function guard(IncomingRequest $req, \CodeIgniter\HTTP\ResponseInterface $res, string $method)
    {
        $why = self::problem($req);
        if ($why === null) {
            return null;
        }
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            $db = null;
        }
        return self::refuse($db, $req, $res, $why, $method);
    }

    /** The answer a desk endpoint gives to a refused request (HTTP 403, JSON), after one audit line without the token. */
    public static function refuse($db, IncomingRequest $req, \CodeIgniter\HTTP\ResponseInterface $res, string $why, string $method)
    {
        try {
            Audit::log($db, ['action' => 'csrf_refused', 'ref_type' => 'action', 'ref_id' => mb_substr((string) service('uri')->getRoutePath(), 0, 80),
                'reason' => 'tf-paydesk-r7e: security token ' . ($why === 'missing' ? 'missing' : ($why === 'no_token' ? 'not held by this browser (expired or never issued)' : 'does not match')) . ' — nothing was run',
                'new' => ['method' => strtoupper($req->getMethod()), 'action' => $method, 'vnum' => self::field($req, 'vnum'), 'pr_id' => self::field($req, 'pr_id'), 'bulk_id' => self::field($req, 'bulk_id'),
                    'tour_id' => self::field($req, 'tour_id') ?? self::field($req, 'tourId'), 'hold_id' => self::field($req, 'hold_id'),
                    'vnums' => is_array($req->getPost('vnums')) ? count($req->getPost('vnums')) : null]]);
        } catch (\Throwable $e) {
        }
        return $res->setStatusCode(403)->setJSON(['status' => 403, 'csrf' => true,
            'message' => 'This page\'s security token is missing or out of date. Reload the page and try again. Nothing was written.']);
    }

    /**
     * tf-paydesk-r7f: the POST fields for an in-process call of an existing action (the desk replaces the POST fields, so the
     * token the page sent would otherwise be lost). The token the OUTER request carried is added only when that request is a
     * state-changing one and passes this check itself; otherwise the fields are returned unchanged and the called action refuses.
     */
    public static function forward(IncomingRequest $outer, array $post): array
    {
        if (!in_array(strtoupper($outer->getMethod()), self::METHODS, true) || self::problem($outer) !== null) {
            return $post;
        }
        $cfg = config(\Config\Security::class);
        $t = self::posted($outer, (string) $cfg->tokenName, (string) $cfg->headerName);
        if ($t !== null && $t !== '') {
            $post[(string) $cfg->tokenName] = $t;
        }
        return $post;
    }

    /** tf-paydesk-r7e: one plain field of the request for the audit line (form field, else the JSON body) — never the token. */
    private static function field(IncomingRequest $req, string $name): ?string
    {
        $v = $req->getPost($name);
        if ($v === null && stripos($req->getHeaderLine('Content-Type'), 'json') !== false) {
            try {
                $j = json_decode((string) $req->getBody(), true);
                $v = is_array($j) ? ($j[$name] ?? null) : null;
            } catch (\Throwable $e) {
                $v = null;
            }
        }
        return self::scalar($v);
    }

    private static function scalar($v): ?string
    {
        return is_scalar($v) ? mb_substr((string) $v, 0, 20) : null;
    }
}
