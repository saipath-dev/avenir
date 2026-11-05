<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * tf-e8-access-r1 — Accounts access control (E8). The route registry is in AccessPolicyRoutes.php.
 *
 * $mode — the first rollback lever (one line):
 *   legacy   today's behaviour: the old "starts with" menu rule decides; this filter does nothing.
 *   report   today's behaviour, plus one access_policy_shadow audit row for every request the new rules WOULD refuse,
 *            and one accounts_action audit row for every change. Nothing is refused by the new rules.
 *   enforce  the new rules decide every accounts/ address: deny by default, page and action privileges, POST + security
 *            token for every change, reason for deletes and corrections, Auditor read-only allowlist, audit.
 *   Any other value is treated as enforce (fail closed).
 *
 * A test copy may override any value from its .env (accesspolicy.mode = enforce, …); development and live use the lines below.
 */
class AccessPolicy extends BaseConfig
{
    public string $mode = 'report';

    /** Groups that may only read, and only addresses on the Auditor allowlist ('ro' in the registry). */
    public string $readOnlyGroups = '17,34';

    /** Groups refused every managed address (decision X11: the Admin group has no Accounts access). */
    public string $deniedGroups = '25';

    /** Modules (first path segment) the registry manages. Phase 1: accounts only. */
    public string $managed = 'accounts';

    /**
     * Test copies only: a request carrying "X-Access-Policy-Dry-Run: 1" gets the decision (204 allowed / 4xx refused)
     * without the action running. Always false on development and live.
     */
    public bool $dryRunHeader = false;
}
