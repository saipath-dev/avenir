<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\AccessPolicy;
use Config\AccessPolicyRoutes;

/**
 * tf-e8-access-r1 — Accounts access control (E8).
 *
 * For every address the registry manages (phase 1: accounts/), in enforce mode, in this order:
 *   1. the address (method + full path, exact; placeholders only where Routes.php has them) is not in the registry → 403
 *   2. it is registered for another method only → 405
 *   3. the user's group is refused the module (the Admin group, X11) → 403
 *   4. the group is read-only and the address is not on the Auditor allowlist (or is a change) → 403
 *   5. the group holds none of the entry's privileges (menu addresses or act: action privileges) → 403
 *      — the System Administrator holds every privilege, as before; steps 2, 6 and 7 still apply
 *   6. a change (POST) without the right security token → 403 "Security token missing or expired — reload the page."
 *   7. a delete or correction without a reason of at least 5 characters → 422
 * Every refusal is audited (tpd_audit, access_policy_denied). Every change that runs is audited afterwards
 * (accounts_action: who, group, address, answer, privilege, record identifiers, reason — never amounts or account numbers).
 * Daily Bank Reconciliation and the retired tool check their own token and audit themselves ('csrf' => 'controller').
 * Report mode: nothing is refused here; what would be refused is written as access_policy_shadow.
 * Legacy mode: this filter does nothing.
 */
final class AccessPolicyFilter implements FilterInterface
{
    public const TOKEN_MESSAGE = 'Security token missing or expired — reload the page. Nothing was changed.';
    public const REASON_MESSAGE = 'Please give a reason of at least 5 characters. Nothing was changed.';

    /** Request values recorded as record identifiers in the audit row (never amounts, bank details or free text). */
    private const ID_KEYS = ['id', 'Id', 'tour_id', 'inv', 'invoice_no', 'manual_id', 'customer_id', 'voucher_no', 'expense_id', 'rowId',
                             'TCode', 'HCode', 'PCode', 'T_Code', 'H_Code', 'status', 'type', 'flag'];

    /** The decision for this request, kept for after(). */
    private static ?array $current = null;
    /** Compiled patterns of the registry entries with placeholders. */
    private static ?array $patterns = null;

    // ------------------------------------------------------------------ configuration helpers

    public static function mode(): string
    {
        $m = strtolower(trim((string) config(AccessPolicy::class)->mode));
        return in_array($m, ['legacy', 'report', 'enforce'], true) ? $m : 'enforce';
    }

    private static function ids(string $csv): array
    {
        return array_values(array_filter(array_map('intval', explode(',', $csv)), static fn ($v) => $v > 0));
    }

    /** tf-e8-access-r2: the groups refused every managed address (decision X11) — UserPermissionFilter applies it in every mode. */
    public static function deniedGroupIds(): array
    {
        return self::ids((string) config(AccessPolicy::class)->deniedGroups);
    }

    /** True when the registry manages this address (phase 1: accounts and everything under it). */
    public static function handles(string $path): bool
    {
        $p = strtolower(trim($path, '/'));
        foreach (explode(',', (string) config(AccessPolicy::class)->managed) as $m) {
            $m = strtolower(trim($m));
            if ($m !== '' && ($p === $m || str_starts_with($p, $m . '/'))) {
                return true;
            }
        }
        return false;
    }

    /**
     * The registry entry for METHOD + path: exact key first, then the entries with placeholders.
     * Returns [entry|null, key|null, registeredForAnotherMethod].
     */
    public static function lookup(string $method, string $path): array
    {
        $routes = AccessPolicyRoutes::ROUTES;
        $path = trim($path, '/');
        $key = $method . ' ' . $path;
        if (isset($routes[$key])) {
            return [$routes[$key], $key, false];
        }
        if (self::$patterns === null) {
            self::$patterns = [];
            $ph = ['(:any)' => '.*', '(:segment)' => '[^/]+', '(:num)' => '[0-9]+', '(:alpha)' => '[a-zA-Z]+', '(:alphanum)' => '[a-zA-Z0-9]+', '(:hash)' => '[^/]+'];
            foreach ($routes as $k => $_) {
                [$m, $p] = explode(' ', $k, 2);
                if (!str_contains($p, '(:')) {
                    continue;
                }
                $rx = preg_quote($p, '#');
                foreach ($ph as $tok => $re) {
                    $rx = str_replace(preg_quote($tok, '#'), '(' . $re . ')', $rx);
                }
                self::$patterns[$k] = [$m, '#^' . $rx . '$#u'];
            }
        }
        $other = false;
        foreach (self::$patterns as $k => [$m, $rx]) {
            if (preg_match($rx, $path)) {
                if ($m === $method) {
                    return [$routes[$k], $k, false];
                }
                $other = true;
            }
        }
        foreach ($routes as $k => $_) {
            [$m, $p] = explode(' ', $k, 2);
            if ($p === $path && $m !== $method) {
                $other = true;
            }
        }
        return [null, null, $other];
    }

    /** Menu addresses (and act: action privileges) the group holds → menu name. */
    private static function held(int $groupId): array
    {
        $out = [];
        $rows = \Config\Database::connect()->query(
            'SELECT DISTINCT ml.url, ml.name FROM user_privileges up JOIN menu_list ml ON ml.id = up.menu_id WHERE up.group_id = ? AND ml.del = 0',
            [$groupId]
        )->getResultArray();
        foreach ($rows as $r) {
            $out[(string) $r['url']] = (string) $r['name'];
        }
        return $out;
    }

    private static function postedToken(RequestInterface $request): ?string
    {
        $sec = config('Security');
        $v = $request->getPost($sec->tokenName);
        if (is_string($v) && $v !== '') {
            return $v;
        }
        $h = $request->getHeaderLine($sec->headerName);
        if ($h !== '') {
            return $h;
        }
        $j = self::jsonBody($request);
        return isset($j[$sec->tokenName]) && is_string($j[$sec->tokenName]) ? $j[$sec->tokenName] : null;
    }

    private static function jsonBody(RequestInterface $request): array
    {
        try {
            $body = (string) $request->getBody();
        } catch (\Throwable $e) {
            return [];
        }
        if ($body === '' || ($body[0] !== '{' && $body[0] !== '[')) {
            return [];
        }
        $j = json_decode($body, true);
        return is_array($j) ? $j : [];
    }

    private static function param(RequestInterface $request, string $name)
    {
        $v = $request->getPost($name);
        if ($v === null) {
            $j = self::jsonBody($request);
            $v = $j[$name] ?? null;
        }
        return $v;
    }

    // ------------------------------------------------------------------ the filter

    public function before(RequestInterface $request, $arguments = null)
    {
        self::$current = null;
        $mode = self::mode();
        if ($mode === 'legacy') {
            return null;
        }
        $path = trim((string) service('uri')->getRoutePath(), '/');
        if (!self::handles($path)) {
            return null;
        }
        $method = strtoupper($request->getMethod());
        $cfg = config(AccessPolicy::class);
        $session = session();
        $gid = (int) $session->get('group_id');
        [$entry, $key, $other] = self::lookup($method, $path);
        if ($entry !== null && ($entry['c'] ?? '') === 't') {
            return null;   // a tombstone: its controller answers 405, changes nothing and audits the call
        }
        $ctx = ['mode' => $mode, 'method' => $method, 'path' => $path, 'key' => $key, 'group' => $gid];

        $d = $this->decide($request, $entry, $other, $gid, $cfg, $ctx);   // [code, rule, message, privilege used]
        self::$current = ['ctx' => $ctx, 'entry' => $entry, 'decision' => $d];

        $tfDry = $cfg->dryRunHeader && $request->getHeaderLine('X-Access-Policy-Dry-Run') === '1';   // test-only
        if ($d[0] !== 200) {
            if ($mode === 'report') {
                self::audit($request, 'access_policy_shadow', $ctx + ['would' => $d[0], 'rule' => $d[1]], $d[1]);
                if ($tfDry) {   // tf-e8-access-r3: test-only report-mode dry-run — stop here, nothing runs
                    self::$current = null;
                    return service('response')->setStatusCode(204)->setHeader('X-Access-Policy', 'report-dry; would ' . $d[0] . '; ' . $d[1]);
                }
                return null;   // today's rule decides
            }
            self::audit($request, 'access_policy_denied', $ctx + ['status' => $d[0], 'rule' => $d[1]], $d[1]);
            return self::refuse($request, $d[0], $d[1], $d[2]);
        }
        if ($mode === 'report' && $tfDry) {   // tf-e8-access-r3: test-only report-mode dry-run of an allowed request — nothing runs
            self::$current = null;
            return service('response')->setStatusCode(204)->setHeader('X-Access-Policy', 'report-dry; allow; ' . ($d[3] ?? ''));
        }
        if ($mode === 'enforce') {
            if ($tfDry) {
                self::$current = null;
                return service('response')->setStatusCode(204)->setHeader('X-Access-Policy', 'allow; ' . ($d[3] ?? ''))
                    ->setHeader('X-CSRF-TOKEN', (string) csrf_hash());
            }
            if ($gid !== 1) {
                self::visitLog($request, $d[3] ?? $path, $d[4] ?? '');
            }
        }
        return null;
    }

    /** @return array{0:int,1:string,2:string,3:?string,4:?string} */
    private function decide(RequestInterface $request, ?array $entry, bool $other, int $gid, AccessPolicy $cfg, array $ctx): array
    {
        if ($entry === null) {
            return $other ? [405, 'wrong_method', 'This address does not accept ' . $ctx['method'] . '. Nothing was changed.', null, null]
                          : [403, 'unregistered', 'This address is not available.', null, null];
        }
        if ($gid !== 1 && in_array($gid, self::ids($cfg->deniedGroups), true)) {
            return [403, 'denied_group', 'Your profile has no access to Accounts. The attempt was recorded.', null, null];
        }
        $isWrite = ($entry['c'] ?? '') === 'w';
        $used = null;
        $usedName = null;
        if ($gid === 1) {
            $used = 'System Administrator';
        } else {
            $ro = in_array($gid, self::ids($cfg->readOnlyGroups), true);
            if ($ro && ($isWrite || empty($entry['ro']))) {
                return [403, 'read_only', 'Your profile is read-only and this address is not on the read-only list. The attempt was recorded.', null, null];
            }
            $held = \App\Libraries\TourFlow\StaffAssignment::heldOverride(\Config\Database::connect(), (int) session()->get('user_id')) ?? [];   // tf-zeroperm-r1: off the panel = nothing; a group never grants   // tf-stage10: an account on the Staff Assignment panel holds exactly its Open rows
            $privs = $entry['priv'] ?? [];
            if ($ro) {
                $privs[] = 'accounts';   // a read-only group holding Manage Accounts keeps every allowlisted address, never more
            }
            foreach ($privs as $p) {
                if (isset($held[$p])) {
                    $used = $p;
                    $usedName = $held[$p];
                    break;
                }
            }
            if ($used === null) {
                return [403, 'no_privilege', 'You do not have the privilege for this action. The attempt was recorded.', null, null];
            }
        }
        if ($isWrite && ($entry['csrf'] ?? 'filter') === 'filter') {
            $tok = self::postedToken($request);
            $hash = (string) csrf_hash();
            if ($tok === null || $hash === '' || !hash_equals($hash, (string) $tok)) {
                return [403, 'token', self::TOKEN_MESSAGE, null, null];
            }
            if (!empty($entry['reason'])) {
                $r = self::param($request, 'reason');
                if (!is_string($r) || mb_strlen(trim($r)) < 5) {
                    return [422, 'reason_required', self::REASON_MESSAGE, null, null];
                }
            }
        }
        return [200, 'allow', '', $used, $usedName];
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $cur = self::$current;
        self::$current = null;
        if ($cur === null) {
            return null;
        }
        $response->setHeader('X-CSRF-TOKEN', (string) csrf_hash());
        $response->setHeader('X-Access-Policy', $cur['ctx']['mode'] . '; ' . $cur['decision'][1]);
        $e = $cur['entry'];
        if ($e !== null && ($e['c'] ?? '') === 'w' && ($e['csrf'] ?? 'filter') === 'filter'
            && ($cur['decision'][0] === 200 || $cur['ctx']['mode'] === 'report')) {
            $app = null;
            $body = (string) $response->getBody();
            if ($body !== '' && ($body[0] === '{' || str_contains($response->getHeaderLine('Content-Type'), 'json'))) {
                $j = json_decode($body, true);
                if (is_array($j) && isset($j['status']) && is_scalar($j['status'])) {
                    $app = (string) $j['status'];
                }
            }
            $ids = [];
            foreach (self::ID_KEYS as $k) {
                $v = self::param($request, $k);
                if (is_scalar($v) && (string) $v !== '' && mb_strlen((string) $v) <= 40 && preg_match('#^[A-Za-z0-9 _./\-]+$#', (string) $v)) {
                    $ids[$k] = (string) $v;
                }
            }
            $reason = self::param($request, 'reason');
            self::audit($request, 'accounts_action', $cur['ctx'] + [
                'http' => $response->getStatusCode(), 'app_status' => $app, 'privilege' => $cur['decision'][3], 'ids' => $ids,
            ], is_string($reason) ? trim($reason) : null, isset($ids['tour_id']) ? $ids['tour_id'] : null);
        }
        return null;
    }

    // ------------------------------------------------------------------ answers, audit, activity log

    private static function refuse(RequestInterface $request, int $code, string $rule, string $message)
    {
        $res = service('response');
        $res->setHeader('X-Access-Policy', 'enforce; ' . $rule)->setHeader('X-CSRF-TOKEN', (string) csrf_hash());
        if ($code === 405) {
            $res->setHeader('Allow', 'POST');
        }
        $ajax = $request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')
            || strtoupper($request->getMethod()) !== 'GET';
        if ($ajax) {
            return $res->setStatusCode($code)->setJSON(['status' => $code, 'msg' => $code === 403 ? 'Forbidden' : 'Refused', 'message' => $message, 'rule' => $rule]);
        }
        if ($code === 403) {
            return redirect()->to(base_url('403'))->setHeader('X-Access-Policy', 'enforce; ' . $rule);
        }
        return $res->setStatusCode($code)->setBody(esc($message));
    }

    /** One row in tpd_audit (or, without the payment desk tables, one a_log row marked DENIED / ACTION). */
    public static function audit(RequestInterface $request, string $action, array $ctx, ?string $reason, ?string $tourId = null): void
    {
        try {
            $db = \Config\Database::connect();
            $session = session();
            $user = (string) $session->get('username');
            if ($db->query("SHOW TABLES LIKE 'tpd_audit'")->getResultArray()) {
                $t = microtime(true);
                $db->table('tpd_audit')->insert([
                    'at'        => date('Y-m-d H:i:s', (int) $t) . '.' . sprintf('%03d', (int) (($t - floor($t)) * 1000)),
                    'user'      => $user,
                    'user_id'   => (int) $session->get('user_id') ?: null,
                    'tour_id'   => $tourId !== null ? mb_substr($tourId, 0, 50) : null,
                    'action'    => $action,
                    'ref_type'  => 'route',
                    'ref_id'    => mb_substr(($ctx['method'] ?? '') . ' ' . ($ctx['path'] ?? ''), 0, 80),
                    'new_value' => json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'reason'    => $reason !== null && $reason !== '' ? mb_substr($reason, 0, 500) : null,
                    'ip'        => $request->getIPAddress(),
                ]);
            } else {
                $db->table('a_log')->insert(['Usr' => $user, 'Url' => mb_substr(($ctx['path'] ?? ''), 0, 250), 'Panel' => $action === 'accounts_action' ? 'ACTION' : 'DENIED',
                    'Date' => date('Y-m-d H:i:s'), 'Tym' => date('Y-m-d H:i:s'), 'ip' => $request->getIPAddress()]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'access policy audit failed: ' . $action . ' ' . ($ctx['path'] ?? '') . ' — ' . $e->getMessage());
        }
    }

    /** The activity log row the old permission filter writes for an allowed visit (Url = the granting menu address). */
    private static function visitLog(RequestInterface $request, string $url, string $panel): void
    {
        try {
            \Config\Database::connect()->table('a_log')->insert([
                'Usr' => (string) session()->get('username'), 'Url' => mb_substr($url, 0, 250), 'Panel' => mb_substr($panel, 0, 250),
                'Date' => date('Y-m-d H:i:s'), 'Tym' => date('Y-m-d H:i:s'), 'ip' => $request->getIPAddress(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'access policy visit log: ' . $e->getMessage());
        }
    }
}
