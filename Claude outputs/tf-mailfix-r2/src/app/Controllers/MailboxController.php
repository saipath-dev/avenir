<?php

namespace App\Controllers;

use App\Libraries\TourFlow\MailServer;
use App\Models\UserModel;

/**
 * tf-mailfix-r2 (10 Oct 2026) — My Profile → Mailbox: the signed-in person's own sending mailbox.
 *
 * The person types the mailbox password themselves. It is checked with the mail server first (connect, log in,
 * say goodbye — no message is sent) and saved on their own user account only if the server accepts it. The
 * password is never sent back, logged or shown. This is the only way the System Administrator account's
 * mailbox can be set (Settings → User Accounts does not edit that account), and it works the same for everyone.
 */
class MailboxController extends BaseController
{
    /** Login checks per signed-in session in 10 minutes — a mail server locks a mailbox after many failures. */
    private const MAX_CHECKS = 6;

    private function json(array $d)
    {
        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON($d);
    }

    private function me(): ?array
    {
        $id = (int) session()->get('user_id');
        return $id > 0 ? ((new UserModel())->find($id) ?: null) : null;
    }

    private function body(): array
    {
        $j = $this->request->getJSON(true);
        return is_array($j) ? $j : (array) $this->request->getPost();
    }

    private function serverText(array $s): string
    {
        return $s['host'] === '' ? 'not set' : $s['host'] . ' · port ' . $s['port'] . ' · ' . strtoupper($s['secure']) . ($s['company'] ? ' (the company mail server)' : ' (from your user account)');
    }

    /** Too many checks? Counted per session; the oldest drop out after 10 minutes. */
    private function throttled(): ?string
    {
        $now = time();
        $list = array_values(array_filter((array) session()->get('mailbox_checks'), static fn ($t) => $t > $now - 600));
        if (count($list) >= self::MAX_CHECKS) {
            return 'Too many login checks in a short time — the mail server may lock the mailbox. Wait 10 minutes and try again.';
        }
        $list[] = $now;
        session()->set('mailbox_checks', $list);
        return null;
    }

    // GET my_profile/mailbox — the mailbox as set (address, server, whether a password is saved); never the password
    public function show()
    {
        $u = $this->me();
        if (!$u) {
            return $this->json(['status' => 401, 'message' => 'Please log in again.']);
        }
        $s = MailServer::forAddress((string) ($u['email'] ?? ''), (string) ($u['host'] ?? ''));
        return $this->json(['status' => 200, 'data' => [
            'email' => (string) ($u['email'] ?? ''), 'server' => $this->serverText($s), 'company' => $s['company'],
            'has_password' => trim((string) ($u['email_password'] ?? '')) !== '',
        ]]);
    }

    // POST my_profile/mailbox/check — does the server accept the login saved now? Sends nothing.
    public function check()
    {
        $u = $this->me();
        if (!$u) {
            return $this->json(['status' => 401, 'message' => 'Please log in again.']);
        }
        $email = trim((string) ($u['email'] ?? ''));
        $pw = (string) ($u['email_password'] ?? '');
        if ($email === '' || $pw === '') {
            return $this->json(['status' => 400, 'message' => 'No mailbox password is saved yet. Type it below and click Check and save.']);
        }
        if ($t = $this->throttled()) {
            return $this->json(['status' => 429, 'message' => $t]);
        }
        $r = MailServer::checkLogin($email, $pw, (string) ($u['host'] ?? ''));
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['ok'] ? $r['message'] . ' Sending will work.' : $r['message']]);
    }

    // POST my_profile/mailbox/save {email, password} — checked with the server first; saved only when it is accepted
    public function save()
    {
        $u = $this->me();
        if (!$u) {
            return $this->json(['status' => 401, 'message' => 'Please log in again.']);
        }
        $b = $this->body();
        $email = trim((string) ($b['email'] ?? ''));
        $pw = (string) ($b['password'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['status' => 400, 'message' => 'Enter the mailbox address (for example traveljp@avenirholidays.com).']);
        }
        if ($pw === '') {
            return $this->json(['status' => 400, 'message' => 'Type the mailbox password.']);
        }
        if (strlen($pw) > 200) {
            return $this->json(['status' => 400, 'message' => 'That password is too long.']);
        }
        if ($t = $this->throttled()) {
            return $this->json(['status' => 429, 'message' => $t]);
        }
        $r = MailServer::checkLogin($email, $pw, (string) ($u['host'] ?? ''));
        if (!$r['ok']) {
            return $this->json(['status' => 400, 'message' => $r['message'] . ' Nothing was saved.']);
        }
        $data = ['email_password' => $pw];
        $changedAddress = strtolower($email) !== strtolower(trim((string) ($u['email'] ?? '')));
        if ($changedAddress) {
            $data['email'] = $email;
        }
        if (trim((string) ($u['host'] ?? '')) === '' && !empty($r['server']['company'])) {
            $data['host'] = $r['server']['host'];   // so the account no longer reads "no mailbox set up"
        }
        (new UserModel())->update((int) $u['id'], $data);
        log_message('info', 'Mailbox for user ' . (int) $u['id'] . ' saved after a successful login check (' . $email . ' at ' . $r['server']['host'] . ').');
        return $this->json(['status' => 200, 'message' => 'Saved. ' . $r['message'] . ($changedAddress ? ' Your e-mails now go from ' . $email . '.' : '') . ' Sending will work.']);
    }
}
