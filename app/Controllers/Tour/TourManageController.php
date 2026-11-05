<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Activity;
use App\Libraries\TourFlow\CommsInbox;
use App\Libraries\TourFlow\CommsMail;
use App\Libraries\TourFlow\Secrets;
use App\Libraries\TourFlow\ItineraryWriter;
use App\Libraries\TourFlow\Desks;
use App\Libraries\TourFlow\EditRequests;
use App\Libraries\TourFlow\EmailTemplates;
use App\Libraries\TourFlow\QuoteService;
use App\Libraries\TourFlow\Reauth;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\IntakeService;
use App\Libraries\TourFlow\Staff;
use App\Libraries\TourFlow\TourService;

/**
 * Tour Desk Manage — tour/manage
 *
 * The one privilege behind every ⋯ Manage menu on the Tour Desk: reassign a handling
 * agent, archive or restore an inquiry or a card, correct a hotel card in place, remove an
 * archived inquiry that has nothing attached, archive a hotel in the master. Its own screen
 * is the archive and the activity log across every tour.
 *
 * One menu entry = one privilege, as everywhere else: every action here sits under
 * tour/manage, so the route filter lets a group in only when Settings → Group Privileges
 * says so. The screens that show the ⋯ ask the same question (holds 'tour/manage') and
 * show nothing otherwise. Every desk still sees the handling agent and filters by it —
 * seeing whose tour it is needs no privilege; changing it does.
 *
 * Nothing here deletes by itself. Archive hides and keeps; restore brings back; the one
 * permanent removal refuses anything with a tour, an issued quotation or a legacy link,
 * and asks for the inquiry number to be typed.
 */
class TourManageController extends BaseController
{
    public const URL = Desks::MANAGE_URL;

    private ?array $menuData = null;
    private ?string $actorName = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function userId(): ?int
    {
        $id = (int) session()->get('user_id');
        return $id > 0 ? $id : null;
    }

    private function actorName(): string
    {
        return $this->actorName ??= Staff::displayName($this->db(), $this->userId(), $this->user());
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    private function take(): IntakeService
    {
        $db = $this->db();
        return (new IntakeService($db, $this->user(), new HotelRateService($db, $this->user())))->withActor($this->userId(), $this->actorName());
    }

    private function tours(): TourService
    {
        return new TourService($this->db(), $this->user(), $this->take());
    }

    private function activity(): Activity
    {
        return new Activity($this->db(), $this->user(), $this->userId(), $this->actorName());
    }

    private function menuData(): array
    {
        if ($this->menuData === null) {
            try {
                $this->menuData = (new MenuLoader())->loadMenuData();
            } catch (\Throwable $e) {
                $this->menuData = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
            }
        }
        return $this->menuData;
    }

    /** The route filter has already let the request in; this asks once more, so a mis-set route can never open the door. */
    private function allowed(): bool
    {
        $md = $this->menuData();
        if ((int) ($md['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($md['allowed_menu_list'] ?? []) as $m) {
            $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
            if (trim((string) $u, '/') === self::URL) {
                return true;
            }
        }
        return false;
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    private function fail(\Throwable $e, int $status = 400)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    private function denied()
    {
        return $this->json(['status' => 403, 'message' => 'This needs the Tour Desk Manage privilege (tour/manage), which Management holds. Ask an administrator to grant it under Settings → Group Privileges.']);
    }

    // =====================================================================
    //  The screen
    // =====================================================================

    // GET tour/manage
    public function index()
    {
        $data = $this->menuData();
        $data['user']       = $this->user();
        $data['page_url']   = self::URL;
        $data['intake_url'] = TourIntakeController::URL;
        $data['hotel_url']  = TourDeskController::HOTEL;
        $data['transport_url'] = TourDeskController::TRANSPORT;
        $data['board_url']  = TourDeskController::BOARD;
        $data['installed']  = false;
        $data['handlers']   = [];
        $data['me_name']    = $this->actorName();   // the name the final warnings show as the person acting
        try {
            $version = Installer::installedVersion($this->db());
            $data['installed'] = $version !== null && $version >= Installer::SCHEMA_VERSION;
            if ($data['installed']) {
                $data['handlers'] = Staff::handlers($this->db());
            }
        } catch (\Throwable $e) {
            $data['error'] = $e->getMessage();
        }
        return view('pages/tour/manage', $data);
    }

    // GET tour/manage/data?tab=archived|activity&by=&handler=&entity=&action=&q=&from=&to=
    public function data()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $g   = fn (string $k) => (string) ($this->request->getGet($k) ?? '');
            $tab = $g('tab') ?: 'archived';
            if ($tab === 'activity') {
                return $this->json(['status' => 200, 'data' => [
                    'rows' => $this->activity()->recent(['by' => $g('by'), 'handler' => $g('handler'), 'entity' => $g('entity'), 'action' => $g('action'), 'q' => $g('q'), 'from' => $g('from'), 'to' => $g('to')], 300),
                ]]);
            }
            if ($tab === 'settings') {
                return $this->json(['status' => 200, 'data' => ['numbering' => $this->numbering(), 'company' => $this->company(), 'mailbox' => $this->mailbox(), 'inbox' => $this->inboxSettings(), 'secrets' => $this->secretsState(), 'sightseeing' => $this->sightseeing(), 'itinerary' => $this->itinerary(), 'email_templates' => $this->emailTemplates()]]);
            }
            if ($tab === 'edit_requests') {
                return $this->json(['status' => 200, 'data' => $this->editRequestsData()]);
            }
            $take  = $this->take();
            $tours = $this->tours();
            $f     = ['handler' => $g('handler'), 'q' => $g('q'), 'from' => $g('from'), 'to' => $g('to')];
            if ($tab === 'tours') {
                // every tour on the desks, live and cancelled, with both desks' standing: the admin's one list to act from
                $rows = array_map(static fn ($t) => [
                    'id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'intake_id' => (int) $t['intake_id'], 'tour_name' => $t['tour_name'], 'agent_name' => $t['agent_name'],
                    'handler_name' => $t['handler_name'], 'arrival_date' => $t['arrival_date'], 'departure_date' => $t['departure_date'], 'pax_total' => $t['pax_total'],
                    'stage' => $t['stage'], 'amending' => (int) $t['amending'], 'confirmed_at' => $t['confirmed_at'], 'confirmed_by' => $t['confirmed_by'],
                    'cancelled_at' => $t['cancelled_at'], 'cancelled_by' => $t['cancelled_by'], 'cancel_why' => $t['cancel_why'], 'strip' => $t['strip'], 'problems' => $t['problems'],
                ], $tours->board($f + ['which' => 'all']));
                return $this->json(['status' => 200, 'data' => ['tours' => $rows]]);
            }
            $db    = $this->db();
            // everything that was put away somewhere in the system, in one place, each with its way back
            $q = trim($g('q'));
            $like = '%' . $q . '%';
            $hotels = $q === ''
                ? $db->query("SELECT id, name, primary_sup_acc_no AS acc, status, updated_at FROM hr2_hotel WHERE status = 'inactive' ORDER BY name LIMIT 300")->getResultArray()
                : $db->query("SELECT id, name, primary_sup_acc_no AS acc, status, updated_at FROM hr2_hotel WHERE status = 'inactive' AND (name LIKE ? OR primary_sup_acc_no LIKE ?) ORDER BY name LIMIT 300", [$like, $like])->getResultArray();
            $excursions = $db->query("SELECT code, name, rate, currency, updated_by, updated_at FROM tf_excursion_rate WHERE active = 0" . ($q !== '' ? ' AND (code LIKE ? OR name LIKE ?)' : '') . ' ORDER BY name', $q !== '' ? [$like, $like] : [])->getResultArray();
            $sizes = $db->query("SELECT code, name, max_pax FROM tf_vehicle_class WHERE active = 0 ORDER BY sort_order, code")->getResultArray();
            $cancelled = $tours->board($f + ['which' => 'cancelled']);
            $deleted   = $tours->board($f + ['which' => 'deleted']);
            return $this->json(['status' => 200, 'data' => [
                'deleted_tours' => array_map(static fn ($t) => ['id' => $t['id'], 'tour_no' => $t['tour_no'], 'intake_id' => (int) $t['intake_id'], 'tour_name' => $t['tour_name'], 'agent_name' => $t['agent_name'], 'handler_name' => $t['handler_name'], 'arrival_date' => $t['arrival_date'], 'archived_at' => $t['archived_at'] ?? null, 'archived_by' => $t['archived_by'] ?? null, 'archive_note' => $t['archive_note'] ?? null, 'hotel_cards' => (int) ($t['lanes']['hotel']['total'] ?? 0) + (int) ($t['lanes']['hotel']['archived'] ?? 0), 'transport_cards' => (int) ($t['lanes']['transport']['total'] ?? 0) + (int) ($t['lanes']['transport']['archived'] ?? 0)], $deleted),
                'inquiries' => $take->list($f + ['status' => 'archived', 'limit' => 300]),
                'hotel_cards' => $tours->hotelQueue($f + ['status' => 'archived']),
                'transport_cards' => $tours->transportQueue($f + ['status' => 'archived']),
                'service_cards' => $tours->serviceQueue($f + ['status' => 'archived']),
                'hotels' => $hotels,
                'excursions' => $excursions,
                'vehicle_classes' => $sizes,
                'tours' => array_map(static fn ($t) => ['id' => $t['id'], 'tour_no' => $t['tour_no'], 'tour_name' => $t['tour_name'], 'agent_name' => $t['agent_name'], 'handler_name' => $t['handler_name'], 'arrival_date' => $t['arrival_date'], 'cancelled_at' => $t['cancelled_at'], 'cancelled_by' => $t['cancelled_by'], 'cancel_why' => $t['cancel_why']], $cancelled),
                'counts' => $take->counts(),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    // =====================================================================
    //  Edit Requests (schema 33): who holds the authority, and what waits for an amendment
    // =====================================================================

    private function editRequestsData(): array
    {
        $db = $this->db();
        if (!EditRequests::installed($db)) {
            return ['installed' => false, 'people' => [], 'waiting' => [], 'recent' => []];
        }
        $recent = $db->query('SELECT r.id, r.intake_id, r.seq, r.status, r.pasted_text, r.change_to, r.reason, r.was_locked, r.requested_by, r.requested_at, r.confirmed_by, r.confirmed_at, r.cancelled_by, r.cancelled_at, i.inquiry_no, i.tour_name
                              FROM ' . EditRequests::TABLE . ' r JOIN tf_intake i ON i.id = r.intake_id ORDER BY COALESCE(r.confirmed_at, r.cancelled_at, r.requested_at) DESC, r.id DESC LIMIT 100')->getResultArray();
        return [
            'installed' => true,
            'people'    => (new EditRequests($db, $this->take()))->authorityList(),
            'waiting'   => EditRequests::waiting($db),
            'recent'    => $recent,
        ];
    }

    // POST tour/manage/reauth {password, purpose} — the signed-in manager's password, checked and nothing else: the first
    // step before a tour is cancelled or deleted from Admin or a desk's ⋯ menu
    public function reauth()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b = $this->body();
            $r = Reauth::verify($this->db(), $this->userId(), isset($b['password']) ? (string) $b['password'] : '', mb_substr(trim((string) ($b['purpose'] ?? 'Tour Desk Admin')), 0, 80), $this->activity());
            return $this->json($r['ok'] ? ['status' => 200, 'ok' => true, 'message' => $r['message']] : ['status' => 403, 'ok' => false, 'message' => $r['message']]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/authority/(:num) {on, note} — Edit Request Authority for one person, on or off
    public function authority($userId)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b  = $this->body();
            $on = filter_var($b['on'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $er = new EditRequests($this->db(), $this->take());
            $er->setAuthority((int) $userId, $on, isset($b['note']) ? mb_substr(trim((string) $b['note']), 0, 255) : null);
            $who = \App\Libraries\TourFlow\Staff::byId($this->db(), (int) $userId);
            return $this->json(['status' => 200, 'message' => 'Edit Request Authority is ' . ($on ? 'ON' : 'OFF') . ' for ' . ($who['name'] ?? 'that account') . '. It is written to the activity log.', 'data' => $this->editRequestsData()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/manage/activity.csv?by=&handler=&entity=&action=&q=&from=&to= — the log, to take away
    public function export()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $g   = fn (string $k) => (string) ($this->request->getGet($k) ?? '');
            $csv = $this->activity()->export(['by' => $g('by'), 'handler' => $g('handler'), 'entity' => $g('entity'), 'action' => $g('action'), 'q' => $g('q'), 'from' => $g('from'), 'to' => $g('to')], 5000);
            $this->activity()->record('log', 0, null, null, 'exported', 'Activity log exported (' . max(0, substr_count($csv, "\n") - 1) . ' line(s))');
            return $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8')
                ->setHeader('Content-Disposition', 'attachment; filename="activity-log-' . date('Y-m-d') . '.csv"')
                ->setBody("\xEF\xBB\xBF" . $csv);
        } catch (\Throwable $e) {
            return $this->fail($e, 500);
        }
    }

    /** The tour numbering, from tf_meta: prefix, the year the count is in, the next count, the digits. */
    private function numbering(): array
    {
        $out = ['tour_prefix' => 'AVH', 'tour_year' => '', 'tour_next' => '1', 'tour_pad' => '5'];
        foreach ($this->db()->query("SELECT k, v FROM tf_meta WHERE k IN ('tour_prefix','tour_year','tour_next','tour_pad')")->getResultArray() as $r) {
            $out[$r['k']] = (string) $r['v'];
        }
        $n = max(1, (int) $out['tour_next']);
        $out['example'] = ($out['tour_prefix'] !== '' ? $out['tour_prefix'] . '-' : '') . ($out['tour_year'] !== '' ? $out['tour_year'] : date('Y')) . '-' . str_pad((string) $n, max(1, min(8, (int) $out['tour_pad'])), '0', STR_PAD_LEFT);
        $out['last_tour_no'] = (string) ($this->db()->query('SELECT tour_no FROM tf_tour ORDER BY id DESC LIMIT 1')->getRowArray()['tour_no'] ?? '');
        return $out;
    }

    /** The company's own lines on hotel booking requests and vouchers, from tf_meta. */
    private function company(): array
    {
        $out = ['name' => '', 'address' => '', 'phone' => '', 'email' => '', 'always_cc' => ''];
        foreach ($this->db()->query("SELECT k, v FROM tf_meta WHERE k IN ('company_name','company_address','company_phone','company_email','company_always_cc')")->getResultArray() as $r) {
            $out[substr((string) $r['k'], 8)] = (string) $r['v'];
        }
        return $out;
    }

    /** tf-comms-r2: the company system mailbox and the standard signature (never the password). */
    private function mailbox(): array
    {
        $db = $this->db();
        $sys = CommsMail::systemMailbox($db);
        $tpl = CommsMail::signatureTemplate($db);
        return ['email' => $sys['email'] ?? '', 'name' => $sys['name'] ?? 'Avenir Holidays', 'has_password' => (bool) ($sys['has_password'] ?? false), 'password_state' => $sys['password_state'] ?? 'none',
            'server' => $sys ? $sys['server']['host'] . ' · port ' . $sys['server']['port'] . ' · ' . strtoupper($sys['server']['secure']) : '',
            'signature' => $tpl, 'signature_default' => CommsMail::DEFAULT_SIGNATURE, 'signature_preview' => CommsMail::signature($db, $this->userId(), $this->user(), $tpl),
            'sysadmin' => (int) ($this->menuData()['user_role'] ?? 0) === 1];
    }

    /** tf-comms-r3: the incoming (IMAP) mailbox, without its password. */
    private function inboxSettings(): array
    {
        $s = CommsInbox::settings($this->db());
        $s['sysadmin'] = (int) ($this->menuData()['user_role'] ?? 0) === 1;
        $s['poll_url'] = base_url('api/tourflow/mail_poll');
        $s['spark'] = 'php spark tourflow:inbox';
        return $s;
    }

    /** tf-comms-r3: the state of secrets at rest (never a value). */
    private function secretsState(): array
    {
        return ['key_ready' => Secrets::ready(), 'users' => Secrets::userMailboxState($this->db()), 'sysadmin' => (int) ($this->menuData()['user_role'] ?? 0) === 1];
    }

    // POST tour/manage/inbox {host, port, secure, email, folder, password?, auto_quote, enabled, new_token} — System Administrator only
    public function inbox_save()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        if ((int) ($this->menuData()['user_role'] ?? 0) !== 1) {
            return $this->json(['status' => 403, 'message' => 'Only the System Administrator sets the incoming mailbox.'], 403);
        }
        try {
            $b = $this->body();
            $r = CommsInbox::saveSettings($this->db(), $b);
            if ($r['changes']) {
                Activity::master($this->db(), $this->user(), 'company', 0, 'Incoming mailbox settings changed', $r['changes'], $this->userId(), $this->actorName());
            }
            $s = $r['settings'];
            if (!empty($r['new_token'])) {
                // shown once, in this answer only; never logged, never kept in clear
                return $this->json(['status' => 200, 'message' => 'Saved. A new scheduled-task token was made — copy it now; it is not shown again.', 'data' => ['inbox' => $this->inboxSettings(), 'new_token' => $r['new_token']]]);
            }
            $msg = 'Saved.' . ($s['configured'] ? ' The mailbox ' . $s['email'] . ' is read from ' . $s['host'] . ' (' . $s['folder'] . ').' : ' The incoming mailbox is not complete yet' . ($s['password_state'] === 'unreadable' ? ' — its password was saved under another server\'s key; enter it again.' : ' — enter the server, the address and the password.'));
            return $this->json(['status' => 200, 'message' => $msg, 'data' => ['inbox' => $this->inboxSettings()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/secrets {action: install_key | migrate_users | show_token} — System Administrator only
    public function secrets()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        if ((int) ($this->menuData()['user_role'] ?? 0) !== 1) {
            return $this->json(['status' => 403, 'message' => 'Only the System Administrator does this.'], 403);
        }
        try {
            $b = $this->body();
            $db = $this->db();
            $action = (string) ($b['action'] ?? '');
            if ($action === 'install_key') {
                $r = Secrets::installKey();
                Activity::master($db, $this->user(), 'company', 0, 'Encryption key for this install ' . ($r['ok'] ? 'generated' : 'NOT generated') . ' — ' . $r['message'], [], $this->userId(), $this->actorName());
                return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message'], 'data' => ['secrets' => $this->secretsState()]]);
            }
            if ($action === 'migrate_users') {
                $n = Secrets::migrateUserMailboxes($db);
                $msg = $n['sealed'] . ' staff mailbox password(s) encrypted; ' . $n['already'] . ' already encrypted; ' . $n['empty'] . ' account(s) with none' . ($n['failed'] ? '; ' . $n['failed'] . ' could not be encrypted and were left as they were' : '') . '.';
                Activity::master($db, $this->user(), 'company', 0, 'Staff mailbox passwords encrypted at rest — ' . $msg, [], $this->userId(), $this->actorName());
                return $this->json(['status' => 200, 'message' => $msg, 'data' => ['secrets' => $this->secretsState()]]);
            }
            return $this->json(['status' => 404, 'message' => 'Unknown action.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/mailbox {email, name, password?, signature, clear?} — System Administrator only. The password is
    // checked with the mail server before it is saved (a wrong one is never kept) and is never shown or returned.
    public function mailbox_save()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        if ((int) ($this->menuData()['user_role'] ?? 0) !== 1) {
            return $this->json(['status' => 403, 'message' => 'Only the System Administrator sets the company system mailbox.'], 403);
        }
        try {
            $b = $this->body();
            $db = $this->db();
            $was = $this->mailbox();
            $email = trim((string) ($b['email'] ?? $was['email']));
            $name = mb_substr(trim((string) ($b['name'] ?? $was['name'])), 0, 80) ?: 'Avenir Holidays';
            $sig = array_key_exists('signature', $b) ? mb_substr(trim(str_replace("\r", '', (string) $b['signature'])), 0, 2000) : $was['signature'];
            $pw = (string) ($b['password'] ?? '');
            $changes = [];
            if (!empty($b['clear'])) {
                foreach ([CommsMail::META_EMAIL, CommsMail::META_PASS, CommsMail::META_NAME] as $k) {
                    $db->query('DELETE FROM tf_meta WHERE k = ?', [$k]);
                }
                $changes[] = ['what' => 'system mailbox', 'from' => $was['email'], 'to' => '(removed — e-mails go from each person\'s own mailbox)'];
                Activity::master($db, $this->user(), 'company', 0, 'Company system mailbox removed', $changes, $this->userId(), $this->actorName());
                return $this->json(['status' => 200, 'message' => 'The system mailbox is removed. E-mails from Communications & History go from each person\'s own mailbox until one is set again.', 'data' => ['mailbox' => $this->mailbox()]]);
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('"' . $email . '" is not an e-mail address.');
            }
            if ($pw !== '') {
                if ($email === '') {
                    throw new \InvalidArgumentException('Enter the mailbox address first.');
                }
                if (strlen($pw) > 200) {
                    throw new \InvalidArgumentException('That password is too long.');
                }
                $r = \App\Libraries\TourFlow\MailServer::checkLogin($email, $pw, null);
                if (!$r['ok']) {
                    throw new \RuntimeException($r['message'] . ' Nothing was saved.');
                }
                $db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [CommsMail::META_PASS, Secrets::seal($pw)]);   // tf-comms-r3: encrypted at rest
                $pw = '';
                $changes[] = ['what' => 'mailbox password', 'from' => $was['has_password'] ? '(set)' : '(none)', 'to' => '(set — accepted by ' . $r['server']['host'] . ', encrypted at rest)'];
            } elseif ($email !== '' && strcasecmp($email, $was['email']) !== 0 && $was['has_password']) {
                // a new address needs its own password: the old one cannot be right for it
                $db->query('DELETE FROM tf_meta WHERE k = ?', [CommsMail::META_PASS]);
                $changes[] = ['what' => 'mailbox password', 'from' => '(set)', 'to' => '(cleared — the address changed; enter the password for the new mailbox)'];
            }
            foreach ([CommsMail::META_EMAIL => $email, CommsMail::META_NAME => $name, CommsMail::META_SIGN => $sig] as $k => $v) {
                $db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
            }
            foreach (['email' => [$was['email'], $email], 'name' => [$was['name'], $name], 'signature' => [$was['signature'], $sig]] as $k => [$from, $to]) {
                if ((string) $from !== (string) $to) {
                    $changes[] = ['what' => 'system mailbox ' . $k, 'from' => $from, 'to' => $to];
                }
            }
            if ($changes) {
                Activity::master($db, $this->user(), 'company', 0, 'Company system mailbox / signature changed', $changes, $this->userId(), $this->actorName());
            }
            $now = $this->mailbox();
            $msg = 'Saved.' . ($now['email'] !== '' ? ($now['has_password'] ? ' E-mails from Communications & History go from ' . $now['email'] . ' with the signature below.' : ' The mailbox ' . $now['email'] . ' has no password yet, so e-mails still go from each person\'s own mailbox until it is entered and accepted.') : ' No system mailbox: e-mails go from each person\'s own mailbox.');
            return $this->json(['status' => 200, 'message' => $msg, 'data' => ['mailbox' => $now]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** Sightseeing with nothing to pay, and the priced list's other names (schema 25), from tf_meta. */
    private function sightseeing(): array
    {
        $out = ['no_charge_words' => '', 'aliases' => '', 'master' => false, 'master_url' => \App\Libraries\TourFlow\EntranceMaster::URL];
        foreach ($this->db()->query("SELECT k, v FROM tf_meta WHERE k IN ('service_no_charge_words','service_aliases')")->getResultArray() as $r) {
            $out[$r['k'] === 'service_aliases' ? 'aliases' : 'no_charge_words'] = (string) $r['v'];
        }
        // schema 39: entrances and safaris are the Place to Visit / Entrance Fee Master's (with their own other names); these
        // settings go on serving the lines outside it — meals, services, activities and other stops — exactly as before
        try {
            $out['master'] = \App\Libraries\TourFlow\EntranceMaster::installed($this->db());
        } catch (\Throwable $e) {
            $out['master'] = false;
        }
        return $out;
    }

    // POST tour/manage/sightseeing {no_charge_words, aliases} — the words that make a stop no charge, and "phrase => CODE" lines
    public function sightseeing_save()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $was = $this->sightseeing();
            $words   = array_key_exists('no_charge_words', $b) ? mb_substr(trim((string) $b['no_charge_words']), 0, 2000) : $was['no_charge_words'];
            $aliases = array_key_exists('aliases', $b) ? mb_substr(trim(str_replace("\r", '', (string) $b['aliases'])), 0, 8000) : $was['aliases'];
            $bad = [];
            foreach (preg_split('/\n/', $aliases) ?: [] as $line) {
                if (trim($line) !== '' && !str_contains($line, '=>')) {
                    $bad[] = trim($line);
                }
            }
            if ($bad) {
                throw new \InvalidArgumentException('Each alias line is "phrase => CODE" (the code from the priced list). Not understood: ' . implode('; ', array_slice($bad, 0, 3)));
            }
            $db = $this->db();
            $db->query("INSERT INTO tf_meta (k, v) VALUES ('service_no_charge_words', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [$words]);
            $db->query("INSERT INTO tf_meta (k, v) VALUES ('service_aliases', ?) ON DUPLICATE KEY UPDATE v = VALUES(v)", [$aliases]);
            Activity::master($db, $this->user(), 'sightseeing', 0, 'Sightseeing settings changed: the no-charge words and the priced list\'s other names',
                [['what' => 'no-charge words', 'from' => $was['no_charge_words'], 'to' => $words], ['what' => 'aliases', 'from' => $was['aliases'], 'to' => $aliases]], $this->userId(), $this->actorName());
            return $this->json(['status' => 200, 'message' => 'Saved. An open inquiry with saved figures shows any resulting change for review the next time it is opened — the figures move only when Recalculate is pressed; a quotation version already issued keeps its figures.', 'data' => ['sightseeing' => $this->sightseeing()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** The sentences the itinerary is written with (schema 28): the ones in force, the defaults, and what each is for. */
    private function itinerary(): array
    {
        $w = new ItineraryWriter($this->db());
        return ['values' => $w->settings(), 'defaults' => ItineraryWriter::DEFAULTS, 'labels' => ItineraryWriter::LABELS, 'multiline' => ItineraryWriter::MULTILINE];
    }

    /** The ready-made emails (schema 34): every template, live and archived, with the categories and the words. */
    private function emailTemplates(): array
    {
        $t = new EmailTemplates($this->db());
        return ['rows' => $t->all(), 'categories' => EmailTemplates::CATEGORIES, 'words' => EmailTemplates::WORDS, 'available' => $t->available()];
    }

    // GET tour/manage/email_templates
    public function emailTemplatesList()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        return $this->json(['status' => 200, 'data' => $this->emailTemplates()]);
    }

    // POST tour/manage/email_template {id?, name, category, subject, message, is_default} — create or change one (Management)
    public function emailTemplateSave()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $r = (new EmailTemplates($this->db()))->save($this->body(), $this->user());
            if ($r['changes']) {
                Activity::master($this->db(), $this->user(), 'email_template', (int) $r['template']['id'], 'Email template "' . $r['template']['name'] . '" ' . (count(array_filter($r['changes'], static fn ($c) => $c['from'] === null)) === count($r['changes']) ? 'created' : 'changed'), $r['changes'], $this->userId(), $this->actorName());
            }
            return $this->json(['status' => 200, 'message' => 'Template "' . $r['template']['name'] . '" saved. Every send window of its category offers it from now on.', 'data' => $this->emailTemplates()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/email_template/(:num)/(archive|restore) — off the send windows (kept whole), or back on them
    public function emailTemplateAction($id, $action)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $t = new EmailTemplates($this->db());
            if ($action === 'archive') {
                $row = $t->archive((int) $id, $this->user());
                Activity::master($this->db(), $this->user(), 'email_template', (int) $id, 'Email template "' . $row['name'] . '" archived', [['what' => 'archived', 'from' => 'no', 'to' => 'yes']], $this->userId(), $this->actorName());
                return $this->json(['status' => 200, 'message' => 'Template "' . $row['name'] . '" archived: no send window offers it now. It is kept, and can be restored.', 'data' => $this->emailTemplates()]);
            }
            if ($action === 'restore') {
                $row = $t->restore((int) $id);
                Activity::master($this->db(), $this->user(), 'email_template', (int) $id, 'Email template "' . $row['name'] . '" restored', [['what' => 'archived', 'from' => 'yes', 'to' => 'no']], $this->userId(), $this->actorName());
                return $this->json(['status' => 200, 'message' => 'Template "' . $row['name'] . '" is back on the send windows.', 'data' => $this->emailTemplates()]);
            }
            return $this->json(['status' => 404, 'message' => 'Unknown action.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/itinerary {itin_arrival: "...", ...} — the sentences the itinerary is written with; a blank one goes back to the default
    public function itinerary_save()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $was = $this->itinerary()['values'];
            $db  = $this->db();
            $changes = [];
            foreach (ItineraryWriter::DEFAULTS as $k => $default) {
                if (!array_key_exists($k, $b)) {
                    continue;
                }
                $v = in_array($k, ItineraryWriter::MULTILINE, true)
                    ? mb_substr(trim(str_replace("\r", '', (string) $b[$k])), 0, 4000)
                    : mb_substr(trim(str_replace(["\r", "\n"], ' ', (string) $b[$k])), 0, 500);
                if ($v === '') {
                    $v = $default;
                }
                if (preg_match_all('/\{([a-z_]+)\}/', $v, $m)) {
                    $allowed = preg_match_all('/\{([a-z_]+)\}/', $default, $dm) ? $dm[1] : [];
                    $bad = array_diff($m[1], $allowed);
                    if ($bad) {
                        throw new \InvalidArgumentException('"' . ItineraryWriter::LABELS[$k] . '" can carry ' . ($allowed ? implode(', ', array_map(static fn ($a) => '{' . $a . '}', $allowed)) : 'no placeholder') . ' — not {' . implode('}, {', $bad) . '}.');
                    }
                }
                if ($v !== ($was[$k] ?? $default)) {
                    $changes[] = ['what' => ItineraryWriter::LABELS[$k], 'from' => $was[$k] ?? $default, 'to' => $v];
                }
                $db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
            }
            if ($changes) {
                Activity::master($db, $this->user(), 'itinerary', 0, 'Itinerary wording changed: ' . implode('; ', array_map(static fn ($c) => $c['what'], array_slice($changes, 0, 3))) . (count($changes) > 3 ? ' and more' : ''), $changes, $this->userId(), $this->actorName());
            }
            return $this->json(['status' => 200, 'message' => $changes ? 'Saved. Every quotation previewed from now on reads this way; a version already issued keeps the wording it went out with.' : 'Nothing changed.', 'data' => ['itinerary' => $this->itinerary()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/manage/backups — the copies of the TourFlow tables kept on the server (writable/tf_backups), newest first
    public function backups()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            return $this->json(['status' => 200, 'data' => ['backups' => array_slice(Installer::snapshots(), 0, 30), 'folder' => 'writable/tf_backups']]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/backup — a copy of every TourFlow table taken now; the file stays on the server
    public function backup()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $snap = Installer::snapshot($this->db(), 'manual', $this->user());
            Activity::master($this->db(), $this->user(), 'backup', 0, 'TourFlow tables copied to writable/tf_backups/' . $snap['file'] . ' (' . $snap['rows'] . ' rows)', [['what' => 'copy of the TourFlow tables', 'from' => null, 'to' => $snap['file']]], $this->userId(), $this->actorName());
            unset($snap['path']);
            return $this->json(['status' => 200, 'message' => 'A copy of the TourFlow tables was written to writable/tf_backups/' . $snap['file'] . '.', 'data' => $snap]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/company {name, address, phone, email} — the lines on the documents; every change is on the log
    public function company_save()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $was = $this->company();
            $new = [];
            foreach (['name' => 120, 'address' => 255, 'phone' => 120, 'email' => 255, 'always_cc' => 500] as $k => $len) {
                $new[$k] = array_key_exists($k, $b) ? mb_substr(trim((string) $b[$k]), 0, $len) : $was[$k];
            }
            // tf-mailcc-r1: "Copy every e-mail to" — e-mail addresses only, written one way (comma and space)
            $list = array_values(array_filter(array_map('trim', preg_split('/[;,\s]+/', (string) $new['always_cc']) ?: [])));
            foreach ($list as $a) {
                if (!filter_var($a, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('"' . $a . '" is not an e-mail address (Copy every e-mail to). Nothing was saved.');
                }
            }
            $new['always_cc'] = implode(', ', array_values(array_unique($list)));
            if ($new['name'] === '') {
                throw new \InvalidArgumentException('The company name cannot be blank; it heads every booking request.');
            }
            $db = $this->db();
            $changes = [];
            foreach ($new as $k => $v) {
                $db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', ['company_' . $k, $v]);
                $changes[] = ['what' => $k, 'from' => $was[$k], 'to' => $v];
            }
            Activity::master($db, $this->user(), 'company', 0, 'Company lines on hotel documents changed', $changes, $this->userId(), $this->actorName());
            return $this->json(['status' => 200, 'message' => 'Saved. Every booking request and voucher prepared from now on carries these lines.', 'data' => ['company' => $this->company()]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/settings {tour_prefix, tour_next, tour_pad} — the numbering; every change is on the log
    public function settings()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $was = $this->numbering();
            $prefix = strtoupper(trim((string) ($b['tour_prefix'] ?? $was['tour_prefix'])));
            if ($prefix === '' || !preg_match('/^[A-Z0-9]{1,8}$/', $prefix)) {
                throw new \InvalidArgumentException('The prefix is one to eight letters or digits, e.g. AVH.');
            }
            $next = (int) ($b['tour_next'] ?? $was['tour_next']);
            if ($next < 1 || $next > 99999999) {
                throw new \InvalidArgumentException('The next number has to be 1 or more.');
            }
            $pad = (int) ($b['tour_pad'] ?? $was['tour_pad']);
            if ($pad < 1 || $pad > 8) {
                throw new \InvalidArgumentException('The count is padded to between 1 and 8 digits.');
            }
            $db = $this->db();
            foreach (['tour_prefix' => $prefix, 'tour_next' => (string) $next, 'tour_pad' => (string) $pad] as $k => $v) {
                $db->query('INSERT INTO tf_meta (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
            }
            $now = $this->numbering();
            Activity::master($db, $this->user(), 'tour_numbering', 0, 'Tour numbering: next number will be ' . $now['example'], [
                ['what' => 'prefix', 'from' => $was['tour_prefix'], 'to' => $prefix], ['what' => 'next count', 'from' => $was['tour_next'], 'to' => (string) $next], ['what' => 'digits', 'from' => $was['tour_pad'], 'to' => (string) $pad],
            ], $this->userId(), $this->actorName());
            return $this->json(['status' => 200, 'message' => 'Numbering saved. The next tour confirmed will be ' . $now['example'] . '.', 'data' => ['numbering' => $now]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/excursion/(:segment)/restore — a switched-off excursion rate back on
    public function excursionRestore($code)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $db = $this->db();
            $x = $db->query('SELECT * FROM tf_excursion_rate WHERE code = ?', [(string) $code])->getRowArray();
            if (!$x) {
                throw new \RuntimeException('No such excursion rate.');
            }
            $db->query('UPDATE tf_excursion_rate SET active = 1, updated_by = ?, updated_at = ? WHERE code = ?', [$this->user(), date('Y-m-d H:i:s'), (string) $code]);
            Activity::master($db, $this->user(), 'excursion_rate', 0, 'Excursion Rates: ' . $x['code'] . ' (' . $x['name'] . ') switched back on', [['what' => $x['code'] . ' active', 'from' => 0, 'to' => 1]], $this->userId(), $this->actorName());
            return $this->json(['status' => 200, 'message' => $x['name'] . ' is back on the Excursion Rates list.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/vehicle_class/(:segment)/restore — a retired vehicle size reinstated
    public function vehicleClassRestore($code)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $rates = new \App\Libraries\TourFlow\TransportRates($this->db(), $this->user());
            $rates->setActive((string) $code, true);
            return $this->json(['status' => 200, 'message' => (string) $code . ' is offered on new inquiries again.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/tour/(:num)/cancel {why} — the same cancel the Board offers, from the Admin screen
    public function tourCancel($tourId)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $why = trim((string) ($b['why'] ?? ''));
            if ($why === '') {
                return $this->json(['status' => 400, 'message' => 'Say why the tour is cancelled; the reason is required and stays on the trail. Nothing was changed.']);
            }
            $no   = $this->db()->query('SELECT tour_no FROM tf_tour WHERE id = ?', [(int) $tourId])->getRowArray()['tour_no'] ?? ('#' . (int) $tourId);
            $auth = Reauth::verify($this->db(), $this->userId(), isset($b['password']) ? (string) $b['password'] : '', 'Cancel tour ' . $no, $this->activity());
            if (!$auth['ok']) {
                return $this->json(['status' => 403, 'message' => $auth['message']]);
            }
            $t = $this->tours()->cancel((int) $tourId, $why);
            $this->activity()->record('tour', (int) $tourId, (int) $t['intake_id'], (int) $tourId, 'cancelled', 'Tour ' . $t['tour_no'] . ' cancelled from Admin by ' . $this->actorName() . ' (Management, password checked) — ' . $why, [['what' => 'stage', 'from' => 'live', 'to' => 'cancelled']]);
            return $this->json(['status' => 200, 'message' => 'Tour ' . $t['tour_no'] . ' cancelled by ' . $this->actorName() . '. Every card is marked cancelled for the hotel and transport desks, the trail is kept, and it counts for nothing on either desk.', 'data' => ['tour_id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'stage' => $t['stage']]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/tour/(:num)/delete {why} — deleted the safe way: cancelled, archived with its inquiry, restorable
    public function tourDelete($tourId)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $why = trim((string) ($b['why'] ?? ''));
            if ($why === '') {
                return $this->json(['status' => 400, 'message' => 'Say why the tour is deleted; the reason is required and stays on the trail. Nothing was changed.']);
            }
            $no   = $this->db()->query('SELECT tour_no FROM tf_tour WHERE id = ?', [(int) $tourId])->getRowArray()['tour_no'] ?? ('#' . (int) $tourId);
            $auth = Reauth::verify($this->db(), $this->userId(), isset($b['password']) ? (string) $b['password'] : '', 'Delete tour ' . $no, $this->activity());
            if (!$auth['ok']) {
                return $this->json(['status' => 403, 'message' => $auth['message']]);
            }
            $t = $this->tours()->deleteTour((int) $tourId, $why);
            return $this->json(['status' => 200, 'message' => 'Tour ' . $t['tour_no'] . ' deleted. It is off Inquiries, Hotel Bookings, Transport, the Board and every count, kept whole under Admin → Deleted / archived, and restorable from there.', 'data' => ['tour_id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'stage' => $t['stage']]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/tour/(:num)/restore — back from Deleted / archived (still cancelled; reopen puts it on the desks)
    public function tourRestore($tourId)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $t = $this->tours()->restoreTour((int) $tourId);
            return $this->json(['status' => 200, 'message' => 'Tour ' . $t['tour_no'] . ' restored with its inquiry. It is still cancelled: reopen it to put it back on the desks.', 'data' => ['tour_id' => (int) $t['id'], 'tour_no' => $t['tour_no'], 'stage' => $t['stage']]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/manage/tour/(:num)/removal — what removing the tour for good would take with it
    public function tourRemoval($tourId)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            return $this->json(['status' => 200, 'data' => $this->tours()->removalOf((int) $tourId)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/tour/(:num)/remove {confirm: the tour number, with_inquiry: 1|0} — gone for good, with one line on the log
    public function tourRemove($tourId)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b = $this->body();
            $r = $this->tours()->removeTourPermanently((int) $tourId, (string) ($b['confirm'] ?? ''), !empty($b['with_inquiry']));
            $msg = 'Tour ' . $r['tour_no'] . ' removed for good with its cards, letters and trail lines; the Activity log keeps the record.';
            if (!empty($b['with_inquiry'])) {
                $msg .= $r['inquiry_removed'] ? ' The inquiry ' . ($r['inquiry']['inquiry_no'] ?? '') . ' is removed too.' : ' The inquiry stays: ' . ($r['inquiry_note'] ?? 'it could not be removed') . '.';
            }
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/tour/(:num)/reopen {why, password} — a cancelled tour back on the desks: Management only, with a
    // reason and their own password (the same three things the Board asks for; a cancellation is undone only this way)
    public function tourReopen($tourId)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $why = trim((string) ($b['why'] ?? ''));
            if ($why === '') {
                return $this->json(['status' => 400, 'message' => 'Say why the tour is reopened; the reason is required and stays on the trail. Nothing was changed.']);
            }
            $no   = $this->db()->query('SELECT tour_no FROM tf_tour WHERE id = ?', [(int) $tourId])->getRowArray()['tour_no'] ?? ('#' . (int) $tourId);
            $auth = Reauth::verify($this->db(), $this->userId(), isset($b['password']) ? (string) $b['password'] : '', 'Reopen tour ' . $no, $this->activity());
            if (!$auth['ok']) {
                return $this->json(['status' => 403, 'message' => $auth['message']]);
            }
            $t = $this->tours()->reopen((int) $tourId, $why);
            $this->activity()->record('tour', (int) $tourId, (int) $t['intake_id'], (int) $tourId, 'reopened', 'Tour ' . $t['tour_no'] . ' reopened from Admin by ' . $this->actorName() . ' (Management, password checked) — ' . $why, [['what' => 'stage', 'from' => 'cancelled', 'to' => 'confirmed']]);
            return $this->json(['status' => 200, 'message' => 'Tour ' . $t['tour_no'] . ' reopened by ' . $this->actorName() . '; its cards are back at their first step.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET tour/manage/handlers
    public function handlers()
    {
        try {
            return $this->json(['status' => 200, 'data' => Staff::handlers($this->db())]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    // =====================================================================
    //  Inquiries
    // =====================================================================

    // GET tour/manage/intake/(:num)/dependencies — what hangs off it, before archiving or removing
    public function dependencies($id)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            return $this->json(['status' => 200, 'data' => $this->take()->dependencies((int) $id)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/intake/(:num)/reassign {user_id | name, note}
    public function reassign($id)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b = $this->body();
            $uid = isset($b['user_id']) && $b['user_id'] !== '' && $b['user_id'] !== null ? (int) $b['user_id'] : null;
            $item = $this->take()->setHandler((int) $id, $uid, $b['name'] ?? null, $b['note'] ?? null);
            return $this->json(['status' => 200, 'message' => 'Handling agent is now ' . $item['handler_name'] . '. The change is on the history.', 'data' => $item]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/intake/(:num)/archive {note}
    public function archive($id)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b = $this->body();
            $item = $this->take()->archive((int) $id, $b['note'] ?? null);
            return $this->json(['status' => 200, 'message' => $item['inquiry_no'] . ' archived. It is out of every list and count; restore it from the Archived list.', 'data' => $item]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/intake/(:num)/restore
    public function restore($id)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $item = $this->take()->restore((int) $id);
            return $this->json(['status' => 200, 'message' => $item['inquiry_no'] . ' restored.', 'data' => $item]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/intake/(:num)/delete {confirm: the inquiry number}
    public function delete($id)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b = $this->body();
            $dep = $this->take()->deletePermanently((int) $id, (string) ($b['confirm'] ?? ''));
            return $this->json(['status' => 200, 'message' => $dep['inquiry_no'] . ' removed for good. Its history is kept.', 'data' => $dep]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // =====================================================================
    //  Cards on the desks
    // =====================================================================

    // POST tour/manage/hotel_card/(:num)/(edit|archive|restore)
    public function hotelCard($jobId, $action)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $svc = $this->tours();
            switch ((string) $action) {
                case 'edit':
                    $card = $svc->editHotelJob((int) $jobId, (array) ($b['fields'] ?? $b), $b['note'] ?? null);
                    return $this->json(['status' => 200, 'message' => 'Card corrected. The quotation is unchanged; the change is on the history.', 'data' => $card]);
                case 'archive':
                    $card = $svc->archiveHotelJob((int) $jobId, $b['note'] ?? null);
                    return $this->json(['status' => 200, 'message' => 'Card archived. It is off the Hotel Desk; restore it from Manage → Archived.', 'data' => $card]);
                case 'restore':
                    $card = $svc->restoreHotelJob((int) $jobId);
                    return $this->json(['status' => 200, 'message' => 'Card restored to the Hotel Desk.', 'data' => $card]);
                default:
                    return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
            }
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/transport_card/(:num)/(archive|restore)
    public function transportCard($jobId, $action)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $svc = $this->tours();
            switch ((string) $action) {
                case 'archive':
                    $card = $svc->archiveTransportJob((int) $jobId, $b['note'] ?? null);
                    return $this->json(['status' => 200, 'message' => 'Card archived. It is off the Transport Desk; restore it from Manage → Archived.', 'data' => $card]);
                case 'restore':
                    $card = $svc->restoreTransportJob((int) $jobId);
                    return $this->json(['status' => 200, 'message' => 'Card restored to the Transport Desk.', 'data' => $card]);
                default:
                    return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
            }
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/service_card/(:num)/(:segment)  archive | restore — an activity or ticket line (schema 20)
    public function serviceCard($jobId, $action)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $svc = $this->tours();
            switch ((string) $action) {
                case 'archive':
                    $card = $svc->archiveServiceJob((int) $jobId, $b['note'] ?? null);
                    return $this->json(['status' => 200, 'message' => 'Line archived. It is off the desk; restore it from Manage → Archived.', 'data' => $card]);
                case 'restore':
                    $card = $svc->restoreServiceJob((int) $jobId);
                    return $this->json(['status' => 200, 'message' => 'Line restored to the desk.', 'data' => $card]);
                default:
                    return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
            }
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // =====================================================================
    //  The hotel master
    // =====================================================================

    /**
     * What depends on a hotel in the master: its rate plans, the inquiry nights that name
     * it, the cards on the Hotel Desk, and the legacy tour nights. Archiving a hotel sets it
     * inactive — it leaves the inquiry pickers, its rates stay readable for the nights that
     * already carry it, and nothing is deleted.
     */
    private function hotelDependencies(int $hotelId): array
    {
        $db = $this->db();
        $h  = $db->query('SELECT id, name, status, primary_sup_acc_no FROM hr2_hotel WHERE id = ?', [$hotelId])->getRowArray();
        if (!$h) {
            throw new \RuntimeException('Hotel not found.');
        }
        $n = static function (string $sql, array $b) use ($db): int {
            try {
                return (int) ($db->query($sql, $b)->getRowArray()['n'] ?? 0);
            } catch (\Throwable $e) {
                return 0;
            }
        };
        $out = [
            'id' => (int) $h['id'], 'name' => $h['name'], 'status' => $h['status'], 'sup_acc' => $h['primary_sup_acc_no'],
            'plans_approved' => $n("SELECT COUNT(*) n FROM hr2_contract WHERE hotel_id = ? AND status = 'approved'", [$hotelId]),
            'plans_draft' => $n("SELECT COUNT(*) n FROM hr2_contract WHERE hotel_id = ? AND status = 'draft'", [$hotelId]),
            'open_inquiry_nights' => $n("SELECT COUNT(*) n FROM tf_intake_day d JOIN tf_intake i ON i.id = d.intake_id WHERE d.hotel_id = ? AND i.archived_at IS NULL AND i.status IN ('new','working','quoted')", [$hotelId]),
            'desk_cards_open' => $n("SELECT COUNT(*) n FROM tf_hotel_job j JOIN tf_tour t ON t.id = j.tour_id WHERE j.hotel_id = ? AND j.archived_at IS NULL AND t.stage IN ('confirmed','operating') AND j.status IN ('to_book','requested','confirmed','problem')", [$hotelId]),
            'legacy_nights_year' => 0,
            'blocks_archive' => [],
        ];
        $accs = [];
        try {
            $accs = array_column($db->query('SELECT sup_acc_no FROM hr2_hotel_account WHERE hotel_id = ?', [$hotelId])->getResultArray(), 'sup_acc_no');
        } catch (\Throwable $e) {
            // no accounts table on this install
        }
        if ($accs) {
            $out['legacy_nights_year'] = $n('SELECT COUNT(*) n FROM tour_quotations WHERE hotel_acc IN (' . implode(',', array_fill(0, count($accs), '?')) . ') AND tour_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)', $accs);
        }
        if ($out['desk_cards_open'] > 0) {
            $out['blocks_archive'][] = $out['desk_cards_open'] . ' stay(s) at this hotel are open on the Hotel Desk';
        }
        return $out;
    }

    // GET tour/manage/hotel/(:num)/dependencies
    public function hotelDeps($hotelId)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            return $this->json(['status' => 200, 'data' => $this->hotelDependencies((int) $hotelId)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST tour/manage/hotel/(:num)/(archive|restore) {note}
    public function hotel($hotelId, $action)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $b   = $this->body();
            $dep = $this->hotelDependencies((int) $hotelId);
            $svc = new HotelRateService($this->db(), $this->user());
            if ((string) $action === 'archive') {
                if ($dep['status'] === 'merged') {
                    throw new \RuntimeException('This hotel was merged into another; it is not archived separately.');
                }
                if ($dep['blocks_archive']) {
                    throw new \RuntimeException('This hotel cannot be archived: ' . implode('; ', $dep['blocks_archive']) . '. Finish or archive those cards first.');
                }
                if ($dep['status'] !== 'inactive') {
                    $svc->setHotelArchived((int) $hotelId, true, trim((string) ($b['note'] ?? '')));   // hotel-dup-r1: audited as 'archive'
                    $this->activity()->record('hotel', (int) $hotelId, null, null, 'archived', trim('Hotel ' . $dep['name'] . ' set inactive' . (!empty($b['note']) ? ' — ' . trim((string) $b['note']) : '')) . " ({$dep['plans_approved']} approved plan(s), {$dep['open_inquiry_nights']} open inquiry night(s) keep their rates)", [['what' => 'status', 'from' => $dep['status'], 'to' => 'inactive']]);
                }
                return $this->json(['status' => 200, 'message' => $dep['name'] . ' is inactive: it no longer appears when a hotel is chosen on an inquiry. Its rate plans and every night already on file are untouched.', 'data' => $this->hotelDependencies((int) $hotelId)]);
            }
            if ((string) $action === 'restore') {
                if ($dep['status'] === 'inactive') {
                    $svc->setHotelArchived((int) $hotelId, false);   // hotel-dup-r1: audited as 'restore'
                    $this->activity()->record('hotel', (int) $hotelId, null, null, 'restored', 'Hotel ' . $dep['name'] . ' set active again', [['what' => 'status', 'from' => 'inactive', 'to' => 'active']]);
                }
                return $this->json(['status' => 200, 'message' => $dep['name'] . ' is active again.', 'data' => $this->hotelDependencies((int) $hotelId)]);
            }
            return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
