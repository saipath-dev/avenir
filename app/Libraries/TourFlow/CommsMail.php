<?php

namespace App\Libraries\TourFlow;

/**
 * tf-comms-r2 (27 Oct 2026, Shantha) — e-mails composed and sent from Communications & History.
 *
 * The message goes from the company's system mailbox when one is set up (Tour Desk Admin → Company → System
 * mailbox: address, password checked with the server and kept in tf_meta, never shown again) and otherwise from
 * the signed-in person's own mailbox (My Profile → Mailbox), exactly as quotations and hotel letters go. The
 * sender's name is always the company's; the staff member's own address goes on Reply-To when they have one, so a
 * reply lands with them. Every message carries the standard signature (the template in tf_meta company_signature,
 * filled with the sender's name and the company lines) and the addresses in "Copy every e-mail to".
 *
 * $transport is the seam for a test host that must send nothing: a callable taking the prepared message array
 * and returning ['ok' => bool, 'error' => ?string]. Left null on the real site. No password is ever logged,
 * returned or shown.
 */
final class CommsMail
{
    public const META_EMAIL = 'company_mailbox_email';
    public const META_PASS  = 'company_mailbox_pass';
    public const META_NAME  = 'company_mailbox_name';
    public const META_SIGN  = 'company_signature';

    public const DEFAULT_SIGNATURE = "Kind regards,\n{me}\n{company}\n{address}\nTel {phone} · {email}\n{web}";

    /** @var callable|null */
    public static $transport = null;

    /** The system mailbox as set up, without the password: ['email','name','has_password','server'] (or null). */
    public static function systemMailbox($db): ?array
    {
        try {
            $m = [];
            foreach ($db->query('SELECT k, v FROM tf_meta WHERE k IN (?, ?, ?)', [self::META_EMAIL, self::META_NAME, self::META_PASS])->getResultArray() as $r) {
                $m[$r['k']] = (string) $r['v'];
            }
        } catch (\Throwable $e) {
            return null;
        }
        $email = trim($m[self::META_EMAIL] ?? '');
        if ($email === '') {
            return null;
        }
        $server = MailServer::forAddress($email, null);
        $state = Secrets::state($m[self::META_PASS] ?? '');
        return ['email' => $email, 'name' => trim($m[self::META_NAME] ?? '') ?: 'Avenir Holidays', 'has_password' => $state === 'sealed' || $state === 'plain', 'password_state' => $state, 'server' => $server];
    }

    /**
     * Which mailbox a message from this person goes through, and why: the system mailbox when it is set up with a
     * password; otherwise the person's own. ['kind' => 'system'|'own'|'none', 'email', 'name', 'reply_to', 'note'].
     */
    public static function senderFor($db, ?int $userId, string $username): array
    {
        $who = Staff::contact($db, $userId, $username);
        $sys = self::systemMailbox($db);
        if ($sys && $sys['has_password']) {
            return ['kind' => 'system', 'email' => $sys['email'], 'name' => $sys['name'], 'reply_to' => $who['email'] && strcasecmp($who['email'], $sys['email']) !== 0 ? $who['email'] : null,
                'staff' => $who, 'note' => 'Sent from the company mailbox ' . $sys['email'] . ($who['email'] && strcasecmp($who['email'], $sys['email']) !== 0 ? '; replies go to ' . $who['email'] : '')];
        }
        if ($who['can_send']) {
            return ['kind' => 'own', 'email' => $who['email'], 'name' => 'Avenir Holidays', 'reply_to' => null, 'staff' => $who,
                'note' => ($sys ? 'The company system mailbox ' . $sys['email'] . ' has no password saved yet (Tour Desk Admin → Company)' : 'No company system mailbox is set up (Tour Desk Admin → Company)') . '; this goes from your own mailbox ' . $who['email'] . '.'];
        }
        return ['kind' => 'none', 'email' => $who['email'], 'name' => 'Avenir Holidays', 'reply_to' => null, 'staff' => $who,
            'note' => $sys ? 'The company system mailbox ' . $sys['email'] . ' has no password saved yet, and your account has no mailbox of its own. Ask the System Administrator to finish the mailbox under Tour Desk Admin → Company.'
                : 'No mailbox is set up to send from: neither a company system mailbox (Tour Desk Admin → Company) nor one on your account (My Profile → Mailbox).'];
    }

    /** The company lines used in the signature and the letterhead. */
    public static function company($db): array
    {
        $out = ['name' => 'Avenir Holidays', 'address' => '', 'phone' => '', 'email' => '', 'web' => ''];
        try {
            foreach ($db->query("SELECT k, v FROM tf_meta WHERE k IN ('company_name','company_address','company_phone','company_email','company_web')")->getResultArray() as $r) {
                $out[substr((string) $r['k'], 8)] = trim((string) $r['v']);
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

    /** The signature template as saved (or the standard one). */
    public static function signatureTemplate($db): string
    {
        try {
            $r = $db->query('SELECT v FROM tf_meta WHERE k = ?', [self::META_SIGN])->getRowArray();
            if ($r && trim((string) $r['v']) !== '') {
                return str_replace("\r", '', (string) $r['v']);
            }
        } catch (\Throwable $e) {
        }
        return self::DEFAULT_SIGNATURE;
    }

    /**
     * The standard staff signature as plain text: the template with {me} (the sender's name), {mobile}, {my_email},
     * {company}, {address}, {phone}, {email}, {web} filled in; a line that would be left empty is dropped.
     */
    public static function signature($db, ?int $userId, string $username, ?string $template = null): string
    {
        $who = Staff::contact($db, $userId, $username);
        $co  = self::company($db);
        $w = ['{me}' => $who['name'], '{mobile}' => (string) ($who['phone'] ?? ''), '{my_email}' => (string) ($who['email'] ?? ''), '{company}' => $co['name'], '{address}' => $co['address'], '{phone}' => $co['phone'], '{email}' => $co['email'], '{web}' => $co['web']];
        $lines = [];
        foreach (explode("\n", $template ?? self::signatureTemplate($db)) as $line) {
            $filled = strtr($line, $w);
            // a line whose words were all empty placeholders is dropped (e.g. "Tel {phone} · {email}" with no phone and no mail)
            if (trim($line) !== '' && preg_match('/\{[a-z_]+\}/', $line) && trim(preg_replace('/[^\p{L}\p{N}]+/u', '', strtr($line, array_fill_keys(array_keys($w), '')))) === trim(preg_replace('/[^\p{L}\p{N}]+/u', '', $filled))) {
                continue;
            }
            $lines[] = rtrim($filled);
        }
        return trim(implode("\n", $lines));
    }

    /** The message as it goes: typed text as paragraphs, the signature after a rule — nothing else. */
    public static function html(string $message, string $signature, string $ref = ''): string
    {
        $para = static function (string $text): string {
            $out = '';
            foreach (preg_split('/\n{2,}/', str_replace("\r", '', trim($text))) ?: [] as $p) {
                if (trim($p) !== '') {
                    $out .= '<p style="margin:0 0 12px 0;">' . nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8')) . '</p>';
                }
            }
            return $out;
        };
        $sig = trim($signature) !== '' ? '<div style="margin-top:18px;padding-top:10px;border-top:1px solid #d9d9d9;color:#444;">' . nl2br(htmlspecialchars(trim($signature), ENT_QUOTES, 'UTF-8')) . '</div>' : '';
        // tf-comms-r3: the conversation key, kept in quoted replies, lets a reply be filed on the right record with certainty
        $refLine = trim($ref) !== '' ? '<div style="margin-top:14px;font-size:11px;color:#8a8a8a;">Ref: ' . htmlspecialchars(trim($ref), ENT_QUOTES, 'UTF-8') . ' — please keep this line in your reply.</div>' : '';
        return '<div style="font-family:Segoe UI, Helvetica, Arial, sans-serif; font-size:14px; color:#2c3e50; line-height:1.5;">' . $para($message) . $sig . $refLine . '</div>';
    }

    /** "a@x, b@y; c@z" → ['a@x','b@y','c@z'] (valid addresses only; the names around them dropped). */
    public static function addresses($v): array
    {
        $out = [];
        foreach (preg_split('/[;,\n]+/', (string) $v) ?: [] as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            if (preg_match('/<([^>]+)>/', $p, $m)) {
                $p = trim($m[1]);
            }
            if (filter_var($p, FILTER_VALIDATE_EMAIL) && !in_array(strtolower($p), array_map('strtolower', $out), true)) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /**
     * Send. $msg: to[], cc[], bcc[], subject, html, attachments [{path,name}], reply_to, from_email, from_name,
     * password (system mailbox) — or user_id/username to send through that person's own mailbox.
     * @return array{ok:bool, error:?string, from:string, cc_added:array}
     */
    public static function send($db, array $msg): array
    {
        $to = array_values(array_filter((array) ($msg['to'] ?? []), static fn ($x) => filter_var($x, FILTER_VALIDATE_EMAIL)));
        if (!$to) {
            return ['ok' => false, 'error' => 'Enter at least one e-mail address in To.', 'from' => '', 'cc_added' => []];
        }
        if (trim((string) ($msg['subject'] ?? '')) === '') {
            return ['ok' => false, 'error' => 'The e-mail needs a subject.', 'from' => '', 'cc_added' => []];
        }
        foreach ((array) ($msg['attachments'] ?? []) as $a) {
            if (empty($a['path']) || !is_file($a['path'])) {
                return ['ok' => false, 'error' => 'An attachment could not be prepared (' . ($a['name'] ?? 'file') . '), so nothing was sent.', 'from' => '', 'cc_added' => []];
            }
        }
        $cc = array_values(array_filter((array) ($msg['cc'] ?? []), static fn ($x) => filter_var($x, FILTER_VALIDATE_EMAIL)));
        $bcc = array_values(array_filter((array) ($msg['bcc'] ?? []), static fn ($x) => filter_var($x, FILTER_VALIDATE_EMAIL)));
        $copied = array_map('strtolower', array_merge($to, $cc, $bcc));
        $always = [];
        foreach (MailServer::alwaysCc() as $a) {
            if (!in_array(strtolower($a), $copied, true)) {
                $always[] = $a;
                $copied[] = strtolower($a);
            }
        }
        $fromEmail = trim((string) ($msg['from_email'] ?? ''));
        $fromName  = trim((string) ($msg['from_name'] ?? '')) ?: 'Avenir Holidays';
        $password  = (string) ($msg['password'] ?? '');
        $userHost  = null;
        // tf-supmail-r2: with a test transport installed there is no mail server to authenticate to, so a
        // mailbox password is not asked for. This changes nothing on a real install: $transport is null there,
        // and the credential check below runs exactly as before.
        if (self::$transport !== null && $fromEmail === '') {
            $fromEmail = 'capture@localhost';
        }
        if (self::$transport === null && ($fromEmail === '' || $password === '')) {
            // the person's own mailbox (My Profile → Mailbox), as the hotel letters and quotations use it
            try {
                $u = $db->query('SELECT email, host, email_password FROM user_acc_tbl WHERE id = ? LIMIT 1', [(int) ($msg['user_id'] ?? 0)])->getRowArray();
            } catch (\Throwable $e) {
                $u = null;
            }
            if (!$u || trim((string) $u['email']) === '' || (string) $u['email_password'] === '') {
                return ['ok' => false, 'error' => 'No mailbox is set up to send from (neither the company system mailbox nor one on your account).', 'from' => '', 'cc_added' => []];
            }
            $fromEmail = trim((string) $u['email']);
            $password  = Secrets::reveal((string) $u['email_password']);   // tf-comms-r3: encrypted at rest
            if ($password === '') {
                return ['ok' => false, 'error' => 'The saved password for ' . $fromEmail . ' cannot be read on this install (it was saved under another server\'s key). Enter it again under My Profile → Mailbox.', 'from' => '', 'cc_added' => []];
            }
            $userHost  = (string) $u['host'];
        }
        $messageId = trim((string) ($msg['message_id'] ?? '')) ?: self::messageId($fromEmail, (int) ($msg['intake_id'] ?? 0));
        $prepared = ['from_email' => $fromEmail, 'from_name' => $fromName, 'reply_to' => $msg['reply_to'] ?? null, 'to' => $to, 'cc' => array_merge($cc, $always), 'bcc' => $bcc, 'subject' => (string) $msg['subject'], 'html' => (string) ($msg['html'] ?? ''), 'attachments' => (array) ($msg['attachments'] ?? []), 'message_id' => $messageId];
        if (self::$transport !== null) {
            $r = (self::$transport)($prepared);
            return ['ok' => !empty($r['ok']), 'error' => $r['error'] ?? null, 'from' => $fromEmail, 'cc_added' => $always, 'message_id' => $messageId];
        }
        // a TEST mail sink (App\Libraries\TourFlow\CommsMailSink — present on the development copy only, never part of a
        // release) takes the message instead, so no e-mail leaves the computer during tests
        if (class_exists('\\App\\Libraries\\TourFlow\\CommsMailSink')) {
            $r = \App\Libraries\TourFlow\CommsMailSink::take($prepared);
            return ['ok' => !empty($r['ok']), 'error' => $r['error'] ?? null, 'from' => $fromEmail, 'cc_added' => $always, 'message_id' => $messageId];
        }
        $server = MailServer::forAddress($fromEmail, $userHost);
        if ($server['host'] === '') {
            return ['ok' => false, 'error' => 'No mail server is set for ' . $fromEmail . '.', 'from' => $fromEmail, 'cc_added' => $always, 'message_id' => $messageId];
        }
        $dir = ROOTPATH . 'vendor/phpmailer/phpmailer/src/';
        foreach (['Exception', 'PHPMailer', 'SMTP'] as $f) {
            if (!class_exists('\\PHPMailer\\PHPMailer\\' . $f, false)) {
                require_once $dir . $f . '.php';
            }
        }
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Timeout = 45;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Host = $server['host'];
            $mail->Port = $server['port'];
            $mail->SMTPSecure = $server['secure'];
            $mail->SMTPAuth = true;
            $mail->Username = $fromEmail;
            $mail->Password = $password;
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];   // as SendController
            $mail->setFrom($fromEmail, $fromName);
            $mail->MessageID = $messageId;   // tf-comms-r3: the reply's In-Reply-To names this, and the reply is filed by it
            if (!empty($prepared['reply_to']) && filter_var($prepared['reply_to'], FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($prepared['reply_to']);
            }
            foreach ($prepared['to'] as $a) {
                $mail->addAddress($a);
            }
            foreach ($prepared['cc'] as $a) {
                $mail->addCC($a);
            }
            foreach ($prepared['bcc'] as $a) {
                $mail->addBCC($a);
            }
            foreach ($prepared['attachments'] as $a) {
                $mail->addAttachment($a['path'], (string) ($a['name'] ?? basename($a['path'])));
            }
            $mail->isHTML(true);
            $mail->Subject = $prepared['subject'];
            $mail->Body = MailServer::embedLocalImages($mail, $prepared['html']);
            $mail->AltBody = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>|<\/p>/i', "\n", $prepared['html'])), ENT_QUOTES, 'UTF-8'));
            if ($mail->send()) {
                try {
                    file_put_contents(WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'email_sents.log', date('Y-m-d H:i:s') . ' | Comms | To: ' . implode(', ', $to) . ' | Subject: ' . $prepared['subject'] . PHP_EOL, FILE_APPEND);
                } catch (\Throwable $e) {
                }
                return ['ok' => true, 'error' => null, 'from' => $fromEmail, 'cc_added' => $always, 'message_id' => $messageId];
            }
            return ['ok' => false, 'error' => MailServer::readable('Message could not be sent. Mailer Error: ' . $mail->ErrorInfo, $server['host'], $fromEmail), 'from' => $fromEmail, 'cc_added' => $always, 'message_id' => $messageId];
        } catch (\Throwable $e) {
            $info = trim((string) $mail->ErrorInfo) !== '' ? $mail->ErrorInfo : $e->getMessage();
            return ['ok' => false, 'error' => MailServer::readable('Message could not be sent. Mailer Error: ' . $info, $server['host'], $fromEmail), 'from' => $fromEmail, 'cc_added' => $always, 'message_id' => $messageId];
        }
    }

    /** The system mailbox password for sending, decrypted for this request only (never returned to a screen). */
    public static function systemPassword($db): string
    {
        try {
            $r = $db->query('SELECT v FROM tf_meta WHERE k = ?', [self::META_PASS])->getRowArray();
            return Secrets::reveal((string) ($r['v'] ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** A Message-ID for an e-mail sent from here: unique, carrying the inquiry id, on the sender's domain. */
    public static function messageId(string $fromEmail, int $intakeId): string
    {
        $domain = strtolower(trim((string) substr((string) strrchr($fromEmail, '@'), 1))) ?: 'avenirholidays.com';
        return '<avh-' . $intakeId . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '@' . $domain . '>';
    }

    /** A document kept as HTML (a hotel letter, a voucher) as a PDF file for attaching; null when the PDF library is missing. */
    public static function htmlToPdf(string $html, string $title, string $fileName): ?array
    {
        if (!class_exists('\TCPDF')) {
            $lib = ROOTPATH . 'vendor' . DIRECTORY_SEPARATOR . 'tecnickcom' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php';
            if (is_file($lib)) {
                require_once $lib;
            }
        }
        if (!class_exists('\TCPDF')) {
            return null;
        }
        try {
            $doc = new \TCPDF(defined('PDF_PAGE_ORIENTATION') ? PDF_PAGE_ORIENTATION : 'P', defined('PDF_UNIT') ? PDF_UNIT : 'mm', defined('PDF_PAGE_FORMAT') ? PDF_PAGE_FORMAT : 'A4', true, 'UTF-8', false);
            $doc->SetCreator('Avenir Holidays');
            $doc->SetAuthor('Avenir Holidays');
            $doc->SetTitle($title);
            $doc->setPrintHeader(false);
            $doc->setPrintFooter(false);
            $doc->SetMargins(14, 14, 14);
            $doc->SetAutoPageBreak(true, 14);
            $doc->SetFont('dejavusans', '', 10);
            $doc->AddPage();
            // a picture of this install's own (the logo) is read from disk; a localhost address cannot be fetched by the PDF library
            $html = (string) preg_replace_callback('/(<img\b[^>]*\bsrc=)(["\'])([^"\']+)\2/i', static function ($m) {
                if (defined('FCPATH') && preg_match('~^(?:https?:)?//[^/]+/(?:[^"\'?#]*/)?(assets/images/[A-Za-z0-9_./-]+\.(?:png|jpe?g|gif))~i', html_entity_decode($m[3], ENT_QUOTES, 'UTF-8'), $p) && !str_contains($p[1], '..') && is_file(FCPATH . $p[1])) {
                    return $m[1] . $m[2] . FCPATH . $p[1] . $m[2];
                }
                return $m[0];
            }, $html);
            $doc->writeHTML($html, true, false, true, false, '');
            $dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tf_mail';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $name = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', $fileName);
            if (!str_ends_with(strtolower($name), '.pdf')) {
                $name .= '.pdf';
            }
            $path = $dir . DIRECTORY_SEPARATOR . 'c' . bin2hex(random_bytes(5)) . '-' . $name;
            file_put_contents($path, $doc->Output($name, 'S'));
            return is_file($path) ? ['path' => $path, 'name' => $name] : null;
        } catch (\Throwable $e) {
            log_message('error', 'CommsMail::htmlToPdf: ' . $e->getMessage());
            return null;
        }
    }
}
