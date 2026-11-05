<?php

namespace App\Libraries\TourPayments;

/**
 * tf-pilot-r2 — bank account numbers in data sent to a browser, masked to their last four digits for every sign-in that may not
 * see them (Perms::bankDetails(): Accounts, the Bank Authoriser, the Supplier Bank Details master, the verifier, the Avenir
 * bank-account master and the administrator see them in full). Applied to the JSON of the Tour Payment Dashboard (the desk and
 * the legacy tour search), where suppliers' and drivers' accounts travel with the payment lines.
 */
final class Mask
{
    public const KEYS = ['supplier_bank_acc', 'sup_bank_acc', 'supBankAcc', 'bank_account_no', 'account_no', 'bank_acc_no'];

    /**
     * tf-pilot-r3 — the profiles that existed before the department structure (group ids up to this one: Operational,
     * Maintenance, Sales, the legacy Auditor, Admin, Management, the Transport desks…) keep the access to supplier bank
     * details they had before tf-pilot-r2 on the legacy screens (the Supplier master, the Hotel Rate master, the Tour
     * Payment Dashboard's JSON). The department profiles (29 onwards) and any profile created later follow the
     * New Generation rule above. To retire when the real staff move to department profiles.
     */
    public const LEGACY_LAST_GROUP_ID = 28;

    public static function legacyFull(): bool
    {
        try {
            $g = (int) (Perms::menu()['user_role'] ?? 0);
        } catch (\Throwable $e) {
            $g = (int) (session()->get('group_id') ?? 0);
        }
        return $g > 1 && $g <= self::LEGACY_LAST_GROUP_ID;
    }

    /** May this sign-in see full supplier bank account numbers on the legacy screens? ($strict: the New Generation rule only.) */
    public static function fullAllowed(bool $strict = false): bool
    {
        try {
            if (Perms::bankDetails()) {
                return true;
            }
        } catch (\Throwable $e) {
        }
        return !$strict && self::legacyFull();
    }

    public static function bank($data, bool $strict = false)
    {
        if (self::fullAllowed($strict)) {
            return $data;
        }
        return self::walk($data);
    }

    /** One number to its last four digits, whoever asks (for what is written to the audit trail). */
    public static function one($no): string
    {
        $no = (string) $no;
        return trim($no) === '' || strpos($no, '•') !== false ? $no : BankAccounts::mask($no, 4);
    }

    private static function walk($v)
    {
        if (!is_array($v)) {
            return $v;
        }
        foreach ($v as $k => $x) {
            if (is_string($k) && in_array($k, self::KEYS, true) && is_scalar($x) && trim((string) $x) !== '' && strpos((string) $x, '•') === false) {
                $v[$k] = BankAccounts::mask((string) $x, 4);
            } elseif (is_array($x)) {
                $v[$k] = self::walk($x);
            }
        }
        return $v;
    }
}
