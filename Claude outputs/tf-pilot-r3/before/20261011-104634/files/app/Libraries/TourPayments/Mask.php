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

    public static function bank($data)
    {
        try {
            if (Perms::bankDetails()) {
                return $data;
            }
        } catch (\Throwable $e) {
        }
        return self::walk($data);
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
