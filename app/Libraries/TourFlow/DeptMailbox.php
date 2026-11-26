<?php

namespace App\Libraries\TourFlow;

/**
 * tf-deptmail-r1 (26 Nov 2026, Shantha): the two departmental mailboxes.
 *
 *   agent  land@avenirholidays.com  agent / customer enquiries, quotations and their replies  → Inquiry Communications
 *   hotel  htl@avenirholidays.com   hotel reservation requests and hotel correspondence       → Hotel Reservations
 *
 * Each department is ONE mailbox, set up once under Tour Desk Admin → Settings → Mail Administration → Incoming
 * mailboxes (Agent inquiries = agent, Hotel reservations = hotel): the same address and password collect its replies
 * (IMAP) and send its e-mails (SMTP). An e-mail of the department goes From that address, with Reply-To that address,
 * and the signed-in person's name in the signature. Until the department's mailbox has a password saved, its e-mails go
 * the way they went before (the person's own mailbox) and the screen says so. The password is never shown or returned.
 */
final class DeptMailbox
{
    public const DEPTS = [
        'agent' => ['prefix' => 'inbox_',  'default' => 'land@avenirholidays.com', 'label' => 'Agent & customer enquiries'],
        'hotel' => ['prefix' => 'inboxh_', 'default' => 'htl@avenirholidays.com',  'label' => 'Hotel reservations'],
    ];

    /** The department's mailbox as set up, without the password: ['email', 'ready', 'label'] (ready = a readable password is saved). */
    public static function info($db, string $dept): array
    {
        $d = self::DEPTS[$dept] ?? null;
        if ($d === null) {
            return ['email' => null, 'ready' => false, 'label' => ''];
        }
        $m = self::meta($db, $d['prefix']);
        $email = trim((string) ($m['email'] ?? ''));
        $ready = $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && Secrets::reveal((string) ($m['pass'] ?? '')) !== '';
        return ['email' => $email !== '' ? $email : $d['default'], 'ready' => (bool) $ready, 'label' => $d['label']];
    }

    /**
     * tf-deptmail-r2 (26 Nov 2026, Shantha): no department e-mail ever goes from a personal mailbox. Until the department's
     * mailbox has its own password saved, sending is refused with this message.
     */
    public static function setupMessage($db, string $dept): string
    {
        $i = self::info($db, $dept);
        return 'Department mailbox setup required: ' . ($i['email'] ?: (self::DEPTS[$dept]['default'] ?? 'the department mailbox')) . ' (' . ($i['label'] ?: $dept)
            . ') is not connected with its own password yet. The System Administrator sets it under Tour Desk Admin → Settings → Mail Administration → Incoming mailboxes. Nothing was sent — e-mails of this department never go from a personal mailbox.';
    }

    /** For sending, this request only: ['email', 'password'] — or null when the department has no usable mailbox yet. */
    public static function credentials($db, string $dept): ?array
    {
        $d = self::DEPTS[$dept] ?? null;
        if ($d === null) {
            return null;
        }
        $m = self::meta($db, $d['prefix']);
        $email = trim((string) ($m['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $pw = Secrets::reveal((string) ($m['pass'] ?? ''));
        return $pw === '' ? null : ['email' => $email, 'password' => $pw];
    }

    private static function meta($db, string $prefix): array
    {
        $out = [];
        try {
            foreach ($db->query('SELECT k, v FROM tf_meta WHERE k IN (?, ?)', [$prefix . 'email', $prefix . 'pass'])->getResultArray() as $r) {
                $out[substr((string) $r['k'], strlen($prefix))] = (string) $r['v'];
            }
        } catch (\Throwable $e) {
            // no settings table yet: no department mailbox
        }
        return $out;
    }
}
