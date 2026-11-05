<?php

namespace App\Libraries\TourFlow;

/**
 * Sending the client quotation to the agent, with the PDF attached (schema 34).
 *
 * The email goes the way hotel letters go (HotelMail): through the signed-in person's own
 * mailbox as set up on their account (App\Controllers\Mail\SendController), which takes a
 * list of attachments as file paths. Nothing here holds a password or a key; if the account
 * has no mailbox set up the answer says so, and the quotation is downloaded and sent by hand.
 *
 * $transport is a seam for the test host, which must never send mail: a callable
 * (to, subject, body, cc, attachments) returning 200 or an error string, exactly as
 * SendController does. Left null on the real site.
 */
final class QuoteMail
{
    /** @var callable|null */
    public static $transport = null;

    /**
     * @param string[] $cc
     * @param array<int, array{path:string, name:string}> $attachments
     * @return array{ok:bool, error:?string}
     */
    public static function send(string $to, string $subject, string $body, array $cc = [], array $attachments = []): array
    {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Enter the agent\'s email address.'];
        }
        $cc = array_values(array_filter(array_map('trim', $cc), static fn ($x) => $x !== '' && filter_var($x, FILTER_VALIDATE_EMAIL)));
        if (trim($subject) === '') {
            return ['ok' => false, 'error' => 'The email needs a subject.'];
        }
        foreach ($attachments as $a) {
            if (empty($a['path']) || !is_file($a['path'])) {
                return ['ok' => false, 'error' => 'The quotation PDF could not be made, so nothing was sent.'];
            }
        }
        try {
            if (self::$transport !== null) {
                $r = (self::$transport)($to, $subject, $body, $cc, $attachments);
            } elseif (class_exists('\App\Controllers\Mail\SendController')) {
                $r = (new \App\Controllers\Mail\SendController())->send($to, $subject, $body, $cc ?: null, $attachments);
            } else {
                return ['ok' => false, 'error' => 'Email sending is not set up on this install. Download the PDF and send it from your mail program.'];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Could not send: ' . $e->getMessage()];
        }
        if ($r === 200 || $r === '200') {
            return ['ok' => true, 'error' => null];
        }
        if ($r === 404 || $r === '404') {
            return ['ok' => false, 'error' => 'Your account has no mailbox set up to send from (email, password and host on your user profile). Download the PDF and send it from your mail program, or ask Management to set the mailbox up.'];
        }
        return ['ok' => false, 'error' => is_string($r) && $r !== '' ? $r : 'The mail server did not accept the message.'];
    }

    /** The message as it goes: the typed text as paragraphs, nothing else — no tracking, no pictures. */
    public static function html(string $message): string
    {
        $paras = preg_split('/\n{2,}/', str_replace("\r", '', trim($message))) ?: [];
        $out   = '';
        foreach ($paras as $p) {
            $out .= '<p style="margin:0 0 12px 0;">' . nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
        return '<div style="font-family:Segoe UI, Helvetica, Arial, sans-serif; font-size:14px; color:#2c3e50; line-height:1.5;">' . $out . '</div>';
    }
}
