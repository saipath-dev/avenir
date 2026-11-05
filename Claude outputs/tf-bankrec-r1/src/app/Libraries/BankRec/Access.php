<?php

namespace App\Libraries\BankRec;

use App\Libraries\TourPayments\Perms;

/**
 * tf-bankrec-r1 — who may do what in the Daily Bank Reconciliation. A privilege is a menu entry granted to a group
 * (Settings → Group Privileges), as everywhere in Avenir; the System Administrator group holds them all.
 *
 *   view     sees accounts, statements, matches and records (read-only)     accounts/daily_bank_rec
 *   prepare  imports / enters statements, matches, classifies items,        accounts/daily_bank_rec/prepare
 *            enters rates and transfers, proposes mappings, opening items
 *            and corrections, prepares the daily record
 *   review   confirms ledger mappings, accepts opening items, approves or   accounts/daily_bank_rec/review
 *            rejects corrections, reviews the daily record, voids an
 *            accepted statement
 *
 * Four eyes: a reviewer never decides on something they proposed or prepared themselves (enforced per action).
 */
final class Access
{
    public const URL_VIEW = 'accounts/daily_bank_rec';
    public const URL_PREPARE = 'accounts/daily_bank_rec/prepare';
    public const URL_REVIEW = 'accounts/daily_bank_rec/review';

    public static function can(string $what): bool
    {
        if (Perms::isAdmin()) {
            return true;
        }
        return match ($what) {
            'view' => Perms::holds(self::URL_VIEW) || Perms::holds(self::URL_PREPARE) || Perms::holds(self::URL_REVIEW),
            'prepare' => Perms::holds(self::URL_PREPARE),
            'review' => Perms::holds(self::URL_REVIEW),
            'admin' => false,
            default => false,
        };
    }

    public static function all(): array
    {
        return ['view' => self::can('view'), 'prepare' => self::can('prepare'), 'review' => self::can('review'), 'admin' => Perms::isAdmin(), 'user' => Perms::user()];
    }

    public static function user(): string
    {
        return Perms::user() ?: 'unknown';
    }
}
