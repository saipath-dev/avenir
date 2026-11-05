<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * tf-payguard-r1.3 — settings of the voucher authorisation guard (App\Libraries\PayGuard\AuthGuard).
 *
 * Release build: the code of this release carries no test instrumentation (no forced exits or throws, no forced waits,
 * no test route). The flag below is kept for compatibility with the isolated test copy, where an instrumented build reads
 * it; it is inert in this release and must stay false.
 */
class PayGuard extends BaseConfig
{
    public bool $testHooks = false;
}
