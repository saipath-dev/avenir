<?php

namespace App\Libraries\TourFlow;

/**
 * Sending a letter to a hotel from the Hotel Desk.
 *
 * The email goes the way the Hotel Reservation screen has always sent it: through the
 * signed-in user's own mailbox, as set up on their account (App\Controllers\Mail\
 * SendController). Nothing here holds a password or a key; if the account has no
 * mailbox set up the answer says so, and the desk prints the letter instead.
 *
 * $transport is a seam for the test host, which must never send mail: a callable
 * (to, subject, body, cc) returning 200 or an error string, exactly as SendController
 * does. Left null on the real site.
 */
final class HotelMail
{
    /** @var callable|null */
    public static $transport = null;

    /**
     * @param string[] $cc
     * @return array{ok:bool, error:?string}
     */
    public static function send(string $to, string $subject, string $body, array $cc = []): array
    {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Enter the hotel\'s email address.'];
        }
        $cc = array_values(array_filter(array_map('trim', $cc), static fn ($x) => $x !== '' && filter_var($x, FILTER_VALIDATE_EMAIL)));
        try {
            if (self::$transport !== null) {
                $r = (self::$transport)($to, $subject, $body, $cc);
            } elseif (class_exists('\App\Controllers\Mail\SendController')) {
                $r = (new \App\Controllers\Mail\SendController())->send($to, $subject, $body, $cc ?: null);
            } else {
                return ['ok' => false, 'error' => 'Email sending is not set up on this install. Print the letter and send it as you do today.'];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => MailServer::readable('Could not send: ' . $e->getMessage())];   // tf-mailfix-r1: UTF-8, plain words
        }
        if ($r === 200 || $r === '200') {
            return ['ok' => true, 'error' => null];
        }
        if ($r === 404 || $r === '404') {
            return ['ok' => false, 'error' => 'Your account has no mailbox set up to send from (email, password and host on your user profile). Print the letter, or ask Management to set it up.'];
        }
        // tf-mailfix-r1: whatever the mail library said, as UTF-8 (Japanese Windows text broke the screen's answer)
        return ['ok' => false, 'error' => is_string($r) && $r !== '' ? MailServer::readable($r) : 'The mail server did not accept the message.'];
    }
}
