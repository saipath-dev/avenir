<?php

namespace App\Libraries\TourFlow;

/**
 * tf-mailredirect-r1 (19 Nov 2026, Shantha: "I'm still on developing process. If you want to test email, please send to …").
 *
 * While the system is being developed, NO e-mail may reach a hotel, a supplier, a driver or a customer. On the UAT copy
 * (the site under /UAT/) and on a developer's own machine (localhost), every e-mail the system sends is sent to the one
 * test address below INSTEAD of its real recipients: To is replaced, every copy (Cc, Bcc, the company's "always copy")
 * is dropped, the subject is marked, and a line at the top of the message says who it was really addressed to.
 *
 * The live site (the same host WITHOUT /UAT/) is never affected: there this class does nothing at all.
 */
class MailRedirect
{
    public const TO = 'saipath29@gmail.com';

    /**
     * tf-mailallow-r1 (26 Nov 2026, Shantha: "I will give you permission to send mails to samafonso11@gmail.com for workflow check
     * purpose"): test addresses Shantha has approved — a message addressed ONLY to these is delivered to them on UAT (still marked
     * [UAT TEST], never with copies). Anything else on UAT still goes to TO alone.
     */
    public const ALLOWED = ['samafonso11@gmail.com'];

    /** Where a UAT message goes: its own address when every recipient is an approved test address, else TO. */
    public static function target($to): string
    {
        $list = array_values(array_filter(array_map(static fn ($x) => strtolower(trim((string) $x)), is_array($to) ? $to : (preg_split('/[;,]+/', (string) $to) ?: []))));
        if ($list !== [] && !array_diff($list, self::ALLOWED)) {
            return implode(',', $list);
        }
        return self::TO;
    }

    public static function active(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }
        $host = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''))));
        if (in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
            return true;
        }
        $path = (string) ($_SERVER['REQUEST_URI'] ?? '') . ' ' . (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        return stripos($path, '/UAT/') !== false;
    }

    public static function subject(string $subject): string
    {
        return strpos($subject, '[UAT TEST]') === 0 ? $subject : '[UAT TEST] ' . $subject;
    }

    /** the line at the top of a redirected message: who it was really for */
    public static function banner($to, $cc = null): string
    {
        $list = static fn ($v) => implode(', ', array_filter(array_map('trim', is_array($v) ? $v : (preg_split('/[;,]+/', (string) $v) ?: []))));
        $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        return '<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;background:#FFF3CD;border:1px solid #E0C36A;color:#4A3B00;padding:10px 14px;margin:0 0 12px">'
            . (self::target($to) !== self::TO
                ? '<b>TEST E-MAIL from the Avenir UAT (test) system — workflow check, not a real booking or quotation.</b> '   // tf-mailallow-r1
                : '<b>TEST E-MAIL — not sent to the supplier.</b> The system is still being developed, so this message was sent to ' . $e(self::TO) . ' only. ')
            . 'It was addressed to: <b>' . $e($list($to) ?: '—') . '</b>' . ($list($cc) !== '' ? ' · copy: ' . $e($list($cc)) : '') . '.</div>';
    }
}
