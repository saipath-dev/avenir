<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Comms;
use App\Libraries\TourFlow\EmailTemplates;
use App\Libraries\TourFlow\MailWords;
use App\Libraries\TourFlow\CommsMail;
use App\Libraries\TourFlow\Installer;
use App\Libraries\TourFlow\Staff;

/**
 * Communications & History — tf-comms-r1 (27 Oct 2026, Shantha)   URL tour/intake/comms[/{intake id}]
 *
 * Sits under the Tour Inquiry privilege (the tour/intake menu entry): whoever may open an inquiry may read its
 * record and add to it. Corrections and removals never delete anything and always carry a reason.
 */
class CommsController extends BaseController
{
    public const URL = 'tour/intake/comms';

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

    private function db()
    {
        return \Config\Database::connect();
    }

    private function actorName(): string
    {
        return $this->actorName ??= Staff::displayName($this->db(), $this->userId(), $this->user());
    }

    private function comms(): Comms
    {
        return new Comms($this->db(), $this->user(), $this->userId(), $this->actorName());
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

    /**
     * tf-mailtpl-r2 (Shantha, 4 Nov 2026): which groups of saved wording this person may READ here.
     * It follows the work they are authorised to do, not the whole catalogue: the hotel desk is offered the
     * hotel wording, the transport desk the transport wording, and so on. 'general' is offered to everybody,
     * because every desk writes the occasional plain letter. Nobody CHANGES saved wording from this screen;
     * that is the Email Templates page, and its own privilege.
     *
     * @return array<int, string> the category keys this person may be offered
     */
    private function myGroups(): array
    {
        $md = $this->menuData();
        if ((int) ($md['user_role'] ?? 0) === 1) {
            return array_keys(EmailTemplates::CATEGORIES);
        }
        $mine = ['general'];
        // one menu URL = one privilege; each desk's URL brings the wording that desk actually sends
        $byUrl = [
            'tour/intake'          => ['quotation', 'follow_up', 'confirmation', 'final_confirmation'],
            'tour/pricing'         => ['quotation'],
            'tour/hotel_bookings'  => ['hotel_request', 'hotel_voucher', 'restaurant_request', 'restaurant_voucher', 'activity_request'],
            'tour/transport_ops'   => ['transport_request'],
            'master_files/meal_master' => ['restaurant_request', 'restaurant_voucher'],
        ];
        foreach ($byUrl as $url => $cats) {
            if ($this->holds($url)) {
                $mine = array_merge($mine, $cats);
            }
        }
        // the invoice and the payment reminder are money letters: only whoever may see the invoice work
        if ($this->holds('tour/manage') || $this->holds('master_files/supplier_accounts')) {
            $mine = array_merge($mine, ['invoice', 'payment_reminder']);
        }
        return array_values(array_unique($mine));
    }

    /** Whether the signed-in group holds a menu URL — the same privileges the sidebar reads. */
    private function holds(string $url): bool
    {
        $md = $this->menuData();
        if ((int) ($md['user_role'] ?? 0) === 1) {
            return true;
        }
        foreach ((array) ($md['allowed_menu_list'] ?? []) as $m) {
            $u = is_array($m) ? ($m['url'] ?? '') : ($m->url ?? '');
            if (trim((string) $u, '/') === $url) {
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
        if (stripos((string) $this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            try {
                $json = $this->request->getJSON(true);
                if (is_array($json)) {
                    return $json;
                }
            } catch (\Throwable $e) {
                // not JSON after all — fall through to the form fields
            }
        }
        return $this->request->getPost() ?: [];
    }

    private function fail(\Throwable $e, int $status = 400)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    private function installed(): bool
    {
        try {
            $v = Installer::installedVersion($this->db());
            return $v !== null && $v >= 45 && Comms::ready($this->db());
        } catch (\Throwable $e) {
            return false;
        }
    }

    // GET tour/intake/comms            the search page across every inquiry / tour
    // GET tour/intake/comms/{id}       one inquiry's Communications & History
    public function index($id = null)
    {
        $data = $this->menuData();
        $data['user'] = $this->user();
        $data['user_id'] = $this->userId();
        $data['actor_name'] = $this->actorName();
        $data['comms_url'] = self::URL;
        $data['intake_url'] = 'tour/intake';
        $data['board_url'] = 'tour/board';
        $data['installed'] = $this->installed();
        $data['intake_id'] = (int) $id;
        $data['channels'] = Comms::CHANNELS;
        $data['tags'] = Comms::TAGS;
        $data['allowed_ext'] = Comms::ALLOWED_EXT;
        $data['staff'] = [];
        try {
            $data['staff'] = Staff::handlers($this->db());
        } catch (\Throwable $e) {
        }
        return view('pages/tour/comms', $data);
    }

    // GET comms/{id}/data   {summary, timeline, followups, staff}
    public function data($id)
    {
        try {
            $c = $this->comms();
            return $this->json(['status' => 200, 'data' => ['summary' => $c->summary((int) $id), 'timeline' => $c->timeline((int) $id), 'followups' => $c->followups((int) $id, true), 'me' => ['id' => $this->userId(), 'name' => $this->actorName()]]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET comms/search?q=&from=&to=&handler=&open_only=1
    public function search()
    {
        try {
            $f = ['q' => (string) $this->request->getGet('q'), 'from' => (string) $this->request->getGet('from'), 'to' => (string) $this->request->getGet('to'), 'handler' => (string) $this->request->getGet('handler'), 'open_only' => (bool) $this->request->getGet('open_only')];
            return $this->json(['status' => 200, 'data' => $this->comms()->search($f)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET comms/due?mine=1
    public function due()
    {
        try {
            $mine = (bool) $this->request->getGet('mine');
            return $this->json(['status' => 200, 'data' => $this->comms()->dueFollowups($mine ? $this->userId() : null)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** The files of a multipart request as [{tmp,name,size,mime}]. */
    private function uploads(): array
    {
        $out = [];
        $files = $this->request->getFileMultiple('files') ?: [];
        $one = $this->request->getFile('file');
        if ($one) {
            $files[] = $one;
        }
        foreach ($files as $f) {
            if ($f && $f->isValid()) {
                $out[] = ['tmp' => $f->getTempName(), 'name' => $f->getClientName(), 'size' => (int) $f->getSize(), 'mime' => $f->getClientMimeType()];
            }
        }
        return $out;
    }

    // POST comms/{id}/add   multipart or JSON: direction, channel, tag, occurred_at, from_addr, to_addr, cc_addr, subject, body, links(json), files[]
    public function add($id)
    {
        try {
            $b = $this->body();
            if (isset($b['links']) && is_string($b['links'])) {
                $b['links'] = json_decode($b['links'], true) ?: [];
            }
            $b['source'] = 'manual';
            $c = $this->comms();
            $m = $c->add((int) $id, $b);
            $warn = [];
            foreach ($this->uploads() as $f) {
                try {
                    $c->attach((int) $m['id'], $f);
                } catch (\Throwable $e) {
                    $warn[] = $e->getMessage();
                }
            }
            return $this->json(['status' => 200, 'message' => 'Recorded.' . ($warn ? ' ' . implode(' ', $warn) : ''), 'data' => $c->get((int) $m['id'])]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST comms/{id}/message/{cid}/edit {…fields, why}
    public function edit($id, $cid)
    {
        try {
            $b = $this->body();
            $m = $this->comms()->edit((int) $cid, $b, trim((string) ($b['why'] ?? '')));
            return $this->json(['status' => 200, 'message' => 'Corrected — the previous version is kept.', 'data' => $m]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST comms/{id}/message/{cid}/remove {why}
    public function remove($id, $cid)
    {
        try {
            $b = $this->body();
            $this->comms()->remove((int) $cid, trim((string) ($b['why'] ?? '')));
            return $this->json(['status' => 200, 'message' => 'Removed from view; kept in the record with your reason.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST comms/{id}/message/{cid}/attach   multipart files[]
    public function attach($id, $cid)
    {
        try {
            $c = $this->comms();
            $n = 0; $warn = [];
            foreach ($this->uploads() as $f) {
                try {
                    $c->attach((int) $cid, $f);
                    $n++;
                } catch (\Throwable $e) {
                    $warn[] = $e->getMessage();
                }
            }
            return $this->json(['status' => $n || !$warn ? 200 : 400, 'message' => "{$n} file(s) attached." . ($warn ? ' ' . implode(' ', $warn) : ''), 'data' => $c->attachments((int) $cid)]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET comms/attachment/{attId}   the file, to the signed-in user only
    public function attachment($attId)
    {
        $a = $this->comms()->attachment((int) $attId);
        if (!$a || !is_file($a['path'])) {
            return $this->json(['status' => 404, 'message' => 'Attachment not found.'], 404);
        }
        $inline = in_array(strtolower((string) pathinfo($a['original_name'], PATHINFO_EXTENSION)), ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt'], true);
        return $this->response->setHeader('Content-Type', $a['mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', ($inline && !$this->request->getGet('download') ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $a['original_name']) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) file_get_contents($a['path']));
    }

    // POST comms/attachment/{attId}/remove {why}
    public function removeAttachment($attId)
    {
        try {
            $b = $this->body();
            $this->comms()->removeAttachment((int) $attId, trim((string) ($b['why'] ?? '')));
            return $this->json(['status' => 200, 'message' => 'Attachment removed from view; the file is kept.']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // ------------------------------------------------------------------ tf-comms-r2: compose and send an e-mail from here

    // GET comms/{id}/compose[?reply=commId]   what the Compose form starts with: the sender, signature, addresses, documents
    public function compose($id)
    {
        try {
            $db = $this->db();
            $c = $this->comms();
            $it = $db->query('SELECT id, inquiry_no, tour_name, agent_name, agent_email, arrival_date, departure_date, pax_total FROM tf_intake WHERE id = ?', [(int) $id])->getRowArray();
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.']);
            }
            $tour = $db->query('SELECT id, tour_no FROM tf_tour WHERE intake_id = ?', [(int) $id])->getRowArray();
            $sender = CommsMail::senderFor($db, $this->userId(), $this->user());
            unset($sender['staff']);
            $ref = $it['inquiry_no'] . ($tour ? ' / ' . $tour['tour_no'] : '');
            $dates = $it['arrival_date'] ? date('j M Y', strtotime((string) $it['arrival_date'])) . ($it['departure_date'] ? ' – ' . date('j M Y', strtotime((string) $it['departure_date'])) : '') : '';
            $reply = null;
            $replyId = (int) $this->request->getGet('reply');
            if ($replyId > 0) {
                $reply = $c->replyDefaults($replyId);
            }
            // tf-mailtpl-r1 (4 Nov 2026): the saved wording this window offers, filled from the tour itself.
            // Which group it starts in: a confirmed tour is a confirmation, an inquiry still quoting is a quotation.
            $vars = (new MailWords($db))->forIntake((int) $id, $sender);
            // tf-mailtpl-r2: read access follows the person's own work, not the whole catalogue. A desk is
            // offered the wording its own privilege covers, plus the general group everybody may use; the
            // System Administrator sees them all. Changing any of them still needs the Email Templates page.
            $mine = $this->myGroups();
            // the stage the tour is actually at decides what the window starts with: a tour the handling agent
            // has not yet Final-confirmed is an ACCEPTANCE, never a confirmation that services are secured
            $finalAt = null;
            if ($tour) {
                try {
                    $finalAt = $db->query('SELECT final_confirmed_at FROM tf_tour WHERE id = ?', [(int) $tour['id']])->getRowArray()['final_confirmed_at'] ?? null;
                } catch (\Throwable $e) {
                    $finalAt = null;
                }
            }
            $group = $this->request->getGet('group');
            $group = is_string($group) && isset(EmailTemplates::CATEGORIES[$group]) && in_array($group, $mine, true)
                ? $group
                : ($tour ? ($finalAt ? 'final_confirmation' : 'confirmation') : 'quotation');
            if (!in_array($group, $mine, true)) {
                $group = 'general';
            }
            $tplList = [];
            $start = null;
            try {
                $et = new EmailTemplates($db);
                $tplList = EmailTemplates::filled($et->forWindow($group), $vars);
                // this window signs the letter itself, so the template's own closing sign-off comes off the
                // copy it starts with — the saved template is not touched
                foreach ($tplList as $i => $t) {
                    $tplList[$i]['message'] = EmailTemplates::withoutSignOff((string) $t['message']);
                }
                $start = EmailTemplates::starting($tplList, $group);
            } catch (\Throwable $e) {
                $tplList = [];
            }
            $groupList = [];
            foreach (EmailTemplates::CATEGORIES as $k => $label) {
                if (in_array($k, $mine, true)) {
                    $groupList[$k] = $label;
                }
            }
            return $this->json(['status' => 200, 'data' => [
                'sender' => $sender,
                'signature' => CommsMail::signature($db, $this->userId(), $this->user()),
                'always_cc' => \App\Libraries\TourFlow\MailServer::alwaysCc(),
                'defaults' => ['to' => $reply ? $reply['to'] : (string) ($it['agent_email'] ?? ''), 'cc' => $reply ? $reply['cc'] : '',
                    'subject' => $reply ? $reply['subject'] : trim(($it['tour_name'] ? $it['tour_name'] . ' — ' : '') . $dates . ' (' . $ref . ')'),
                    'greeting' => 'Dear ' . ($it['agent_name'] ?: 'Sir / Madam') . ",\n\n", 'quoted' => $reply ? $reply['quoted'] : ''],
                'reply' => $reply,
                // the saved wording, the group it starts in, and where to change it
                'groups' => $groupList,
                'stage' => $tour ? ($finalAt ? 'final' : 'accepted') : 'quoting',
                'stage_note' => $tour
                    ? ($finalAt
                        ? 'This tour was finally confirmed on ' . substr((string) $finalAt, 0, 16) . ', so the wording may say the services are secured.'
                        : 'This tour is accepted but not finally confirmed, so the wording says the reservations have started — not that the services are secured. The final confirmation is sent from Confirmed Tours once every desk has finished.')
                    : 'This inquiry is still being quoted.',
                'group' => $group,
                'templates' => $tplList,
                'template_id' => $start ? (int) $start['id'] : null,
                'can_manage_templates' => $this->holds('master_files/email_templates'),
                'templates_url' => base_url('master_files/email_templates'),
                'documents' => $c->documents((int) $id),
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    /** A document key from the Compose form as a file to attach: [path, name, temp(bool)] — or an explanation why not. */
    private function document(int $intakeId, string $key): array
    {
        [$kind, $ref] = array_pad(explode(':', $key, 2), 2, '');
        $ref = (int) $ref;
        $db = $this->db();
        if ($kind === 'att') {
            $a = $this->comms()->attachment($ref);
            if (!$a || (int) $a['intake_id'] !== $intakeId || !is_file($a['path'])) {
                throw new \RuntimeException('That file is no longer on this record.');
            }
            return ['path' => $a['path'], 'name' => $a['original_name'], 'mime' => $a['mime'], 'temp' => false];
        }
        if ($kind === 'quote') {
            $q = $db->query('SELECT id, intake_id, version FROM tf_quote WHERE id = ?', [$ref])->getRowArray();
            if (!$q || (int) $q['intake_id'] !== $intakeId) {
                throw new \RuntimeException('That quotation is not on this inquiry.');
            }
            $dir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tf_mail';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $tmp = $dir . DIRECTORY_SEPARATOR . 'cq' . $ref . '-' . bin2hex(random_bytes(5)) . '.pdf';
            $name = (new TourIntakeController())->quotePdfToFile($ref, $tmp);
            if (!is_string($name) || !is_file($tmp) || filesize($tmp) < 100) {
                throw new \RuntimeException('The quotation PDF for version ' . $q['version'] . ' could not be made (is the PDF library installed?).');
            }
            return ['path' => $tmp, 'name' => $name, 'mime' => 'application/pdf', 'temp' => true];
        }
        if ($kind === 'hotel') {
            $m = $db->query('SELECT m.id, m.kind, m.subject, m.body, m.`at`, j.hotel_name, t.intake_id, t.tour_no FROM tf_hotel_message m JOIN tf_hotel_job j ON j.id = m.job_id JOIN tf_tour t ON t.id = m.tour_id WHERE m.id = ?', [$ref])->getRowArray();
            if (!$m || (int) $m['intake_id'] !== $intakeId || trim((string) $m['body']) === '') {
                throw new \RuntimeException('That hotel letter is not on this tour.');
            }
            $pdf = CommsMail::htmlToPdf((string) $m['body'], ($m['kind'] === 'voucher' ? 'Voucher ' : 'Booking request ') . $m['hotel_name'], ($m['kind'] === 'voucher' ? 'Hotel-voucher-' : 'Hotel-request-') . $m['tour_no'] . '-' . $m['hotel_name']);
            if (!$pdf) {
                throw new \RuntimeException('The hotel letter could not be turned into a PDF (is the PDF library installed?).');
            }
            return $pdf + ['mime' => 'application/pdf', 'temp' => true];
        }
        if ($kind === 'voucher') {
            $v = $db->query('SELECT id, intake_id, voucher_no, restaurant, mail_body FROM tf_meal_voucher WHERE id = ?', [$ref])->getRowArray();
            if (!$v || (int) $v['intake_id'] !== $intakeId || trim((string) $v['mail_body']) === '') {
                throw new \RuntimeException('That restaurant voucher is not on this tour.');
            }
            $pdf = CommsMail::htmlToPdf((string) $v['mail_body'], 'Booking voucher ' . $v['voucher_no'], 'Voucher-' . $v['voucher_no'] . '-' . $v['restaurant']);
            if (!$pdf) {
                throw new \RuntimeException('The voucher could not be turned into a PDF (is the PDF library installed?).');
            }
            return $pdf + ['mime' => 'application/pdf', 'temp' => true];
        }
        if ($kind === 'hvoucher') {
            // tf-confirmed-r3: a hotel voucher that has been ISSUED on the desk but never e-mailed, rendered now
            // from the record so the agent can attach it to the confirmation. Reached only through this inquiry's
            // own tour, and only while the stay actually holds an issued voucher — nothing else can be named here.
            $j = $db->query('SELECT j.id, j.status, j.voucher_no, j.hotel_name, t.intake_id
                               FROM tf_hotel_job j JOIN tf_tour t ON t.id = j.tour_id
                              WHERE j.id = ? AND j.archived_at IS NULL', [$ref])->getRowArray();
            if (!$j || (int) $j['intake_id'] !== $intakeId) {
                throw new \RuntimeException('That voucher is not on this inquiry\'s tour.');
            }
            if ((string) $j['status'] !== 'voucher_issued') {
                throw new \RuntimeException('No voucher has been issued for ' . $j['hotel_name'] . ' yet; the Hotel desk issues it once the hotel has confirmed.');
            }
            $html = (new TourDeskController())->voucherHtml((int) $ref);
            $pdf  = CommsMail::htmlToPdf($html, 'Voucher ' . $j['voucher_no'],
                'Voucher-' . preg_replace('/[^A-Za-z0-9._-]/', '-', (string) ($j['voucher_no'] ?: $ref)));
            if (!$pdf) {
                throw new \RuntimeException('The voucher could not be turned into a PDF (is the PDF library installed?).');
            }
            return $pdf + ['mime' => 'application/pdf', 'temp' => true];
        }
        if ($kind === 'invoice') {
            // tf-guided-r3: the customer invoice of this inquiry's own tour, rendered from the
            // application's own invoice print view — the same document the Accounts screen prints.
            // It is reached only through this inquiry's tour; no other invoice can be named here.
            $inv = $db->query('SELECT * FROM debtor_invoice_log WHERE id = ?', [$ref])->getRowArray();
            $mine = $inv ? $db->query("SELECT 1 ok
                                         WHERE ? = (SELECT i.tour_id FROM tf_intake i WHERE i.id = ?)
                                            OR ? = (SELECT t.legacy_tour_id FROM tf_tour t WHERE t.intake_id = ? LIMIT 1)",
                                        [$inv['tour_id'], $intakeId, $inv['tour_id'], $intakeId])->getRowArray() : null;
            if (!$inv || !$mine) {
                throw new \RuntimeException('That invoice is not on this inquiry\'s tour.');
            }
            $cons = $db->query("SELECT c.*, cu.* FROM consignee c LEFT JOIN customers cu ON cu.Sup_Acc_No = c.agent WHERE c.tour_id = ? LIMIT 1", [$inv['tour_id']])->getRowArray() ?: [];
            $extra = $db->query("SELECT manual_id, agent_ref FROM consignee WHERE tour_id = ? LIMIT 1", [$inv['tour_id']])->getRowArray() ?: [];
            $html = view('pages/accounts/debtor_invoice_print', [
                'invoice_details'   => $inv + $extra,
                'consignee_details' => $cons,
                'user_role'         => session()->get('group_id'),
                'allowed_menu_list' => [],
            ]);
            // the screen's own buttons and scripts do not belong in a PDF copy
            $html = (string) preg_replace(['~<script\b[^>]*>.*?</script>~is', '~<div[^>]*class="[^"]*no-print[^"]*"[^>]*>.*?</div>~is'], '', $html);
            $pdf = CommsMail::htmlToPdf($html, 'Invoice ' . $inv['invoice_no'], 'Invoice-' . preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $inv['invoice_no']));
            if (!$pdf) {
                throw new \RuntimeException('The invoice could not be turned into a PDF (is the PDF library installed?).');
            }
            return $pdf + ['mime' => 'application/pdf', 'temp' => true];
        }
        throw new \RuntimeException('Unknown document "' . $key . '".');
    }

    // GET comms/{id}/doc?key=...  — tf-guided-r3: the attachment itself, exactly as it would be attached,
    // shown in the browser so it can be checked before anything is sent. Read-only; it sends nothing.
    public function documentView($id)
    {
        $temp = null;
        try {
            $key = (string) $this->request->getGet('key');
            $d   = $this->document((int) $id, $key);
            if (!empty($d['temp'])) {
                $temp = $d['path'];
            }
            $body = (string) @file_get_contents($d['path']);
            if ($temp !== null) {
                @unlink($temp);
            }
            return $this->response->setHeader('Content-Type', $d['mime'] ?: 'application/octet-stream')
                ->setHeader('Content-Disposition', 'inline; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $d['name']) . '"')
                ->setBody($body);
        } catch (\Throwable $e) {
            if ($temp !== null) {
                @unlink($temp);
            }
            return $this->response->setStatusCode(400)->setBody(esc($e->getMessage()));
        }
    }

    // POST comms/{id}/send   multipart: to, cc, bcc, subject, body, tag, in_reply_to, links(json), docs(json array of keys), files[]
    /**
     * POST comms/(:num)/preview — tf-mailtpl-r1: exactly what would go, and nothing sent.
     *
     * It returns the whole letter the way it leaves the building: who it is from, who it goes to, every
     * copy including the one the company setting adds by itself, the subject, the message with the tour's
     * own details already in it, the signature underneath, and each attachment by name and version with
     * the address that opens the document itself. It also says what is wrong: a missing address, a missing
     * subject, a word the tour could not fill, or a message that is only a greeting and a signature. While
     * anything in `stops` stands, the screen will not send.
     */
    public function preview($id)
    {
        try {
            $db = $this->db();
            $b  = $this->body();
            $it = $db->query('SELECT id, inquiry_no, agent_name, agent_email FROM tf_intake WHERE id = ?', [(int) $id])->getRowArray();
            if (!$it) {
                return $this->json(['status' => 404, 'message' => 'Inquiry not found.']);
            }
            $sender    = CommsMail::senderFor($db, $this->userId(), $this->user());
            $signature = CommsMail::signature($db, $this->userId(), $this->user());
            $to      = trim((string) ($b['to'] ?? ''));
            $cc      = trim((string) ($b['cc'] ?? ''));
            $subject = trim(str_replace(["\r", "\n"], ' ', (string) ($b['subject'] ?? '')));
            $message = rtrim(str_replace("\r", '', (string) ($b['body'] ?? '')));
            $alwaysCc = \App\Libraries\TourFlow\MailServer::alwaysCc();

            // the attachments exactly as they are ticked, each with the address that opens it
            $docs = $b['docs'] ?? [];
            if (is_string($docs)) {
                $docs = json_decode($docs, true) ?: [];
            }
            $known = [];
            foreach ($this->comms()->documents((int) $id) as $d) {
                $known[$d['key']] = $d;
            }
            $attachments = [];
            $stops = [];
            foreach (array_values(array_unique(array_filter((array) $docs, 'is_string'))) as $key) {
                if (!isset($known[$key])) {
                    $stops[] = 'One of the ticked documents is no longer on this record. Untick it and pick it again.';
                    continue;
                }
                $attachments[] = [
                    'key'   => $key,
                    'group' => $known[$key]['group'],
                    'label' => $known[$key]['label'],
                    'open'  => base_url('tour/intake/comms/' . (int) $id . '/doc?key=' . rawurlencode($key)),
                ];
            }

            // what is still wrong — said plainly, in the order staff would fix it
            $warnings = [];
            if ($to === '') {
                $stops[] = 'There is no address in To. Type the address it goes to, or put the agent\'s address on the inquiry.';
            } else {
                foreach (preg_split('/[;,\s]+/', $to) ?: [] as $one) {
                    if (trim($one) !== '' && !filter_var(trim($one), FILTER_VALIDATE_EMAIL)) {
                        $stops[] = '"' . trim($one) . '" in To is not an email address.';
                    }
                }
            }
            if ($subject === '') {
                $stops[] = 'The email has no subject.';
            }
            if (trim($message) === '') {
                $stops[] = 'The message is empty.';
            } elseif (EmailTemplates::saysNothing($message, $signature)) {
                $stops[] = 'The message is only a greeting and a signature: it does not say what is being sent. Choose a template above, or write the message.';
            }
            // a word the tour could not fill, named rather than guessed
            $vars = (new MailWords($db))->forIntake((int) $id, $sender);
            foreach (EmailTemplates::missing($subject . ' ' . $message, $vars) as $w) {
                $stops[] = 'The letter asks for ' . $w . ', and the record has none. Put it on the inquiry first, or take that word out.';
            }
            if (preg_match_all('/\{([a-z_]+)\}/', $subject . ' ' . $message, $mm)) {
                $left = array_values(array_unique($mm[1]));
                if ($left) {
                    $stops[] = 'The letter still carries {' . implode('}, {', $left) . '}. Those words were not filled in; take them out or pick the template again.';
                }
            }
            if (!$attachments) {
                $warnings[] = 'Nothing is attached. If the itinerary or a voucher should go with this, tick it above.';
            }
            if (($sender['can_send'] ?? false) === false) {
                $warnings[] = 'This mailbox is not set up to send yet. Send now will say so, and the attempt is kept on the record.';
            }

            return $this->json(['status' => 200, 'data' => [
                'from'       => trim(($sender['name'] ?? 'Avenir Holidays') . ' <' . ($sender['email'] ?? '') . '>'),
                'to'         => $to,
                'cc'         => $cc,
                'always_cc'  => $alwaysCc,
                'cc_all'     => trim(implode(', ', array_filter(array_merge($cc !== '' ? [$cc] : [], $alwaysCc)))),
                'subject'    => $subject,
                'message'    => $message,
                'signature'  => $signature,
                'attachments'=> $attachments,
                'stops'      => array_values(array_unique($stops)),
                'warnings'   => array_values(array_unique($warnings)),
                'can_send'   => $stops === [],
                'sent'       => false,
            ]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function send($id)
    {
        $temps = [];
        try {
            @set_time_limit(180);
            $b = $this->body();
            if (isset($b['links']) && is_string($b['links'])) {
                $b['links'] = json_decode($b['links'], true) ?: [];
            }
            $docs = $b['docs'] ?? [];
            if (is_string($docs)) {
                $docs = json_decode($docs, true) ?: [];
            }
            $files = [];
            foreach (array_values(array_unique(array_filter((array) $docs, 'is_string'))) as $key) {
                $d = $this->document((int) $id, $key);
                if (!empty($d['temp'])) {
                    $temps[] = $d['path'];
                }
                $files[] = ['path' => $d['path'], 'name' => $d['name'], 'mime' => $d['mime'] ?? null];
            }
            foreach ($this->uploads() as $f) {
                $files[] = $f;   // tmp, name, size, mime — kept on the record by attach()
            }
            if (count($files) > 10) {
                return $this->json(['status' => 400, 'message' => 'Ten attachments at most on one e-mail.']);
            }
            $total = 0;
            foreach ($files as $f) {
                $total += (int) ($f['size'] ?? (is_file($f['path'] ?? '') ? filesize($f['path']) : 0));
            }
            if ($total > 25 * 1048576) {
                return $this->json(['status' => 400, 'message' => 'The attachments come to more than 25 MB together; most mail servers refuse that. Send fewer at a time.']);
            }
            $sender = CommsMail::senderFor($this->db(), $this->userId(), $this->user());
            $r = $this->comms()->sendEmail((int) $id, $b, $files, $sender);
            $to = $r['comm']['to_addr'] ?? '';
            return $this->json(['status' => $r['ok'] ? 200 : 400, 'ok' => $r['ok'],
                'message' => $r['ok'] ? 'Sent to ' . $to . ' from ' . ($r['comm']['sent_from'] ?? '') . ($r['cc_added'] ? ' (copied to ' . implode(', ', $r['cc_added']) . ' as the company setting says)' : '') . '. It is on the record below.'
                    : 'NOT sent — ' . $r['error'] . ' The attempt is kept on the record below; correct the problem and send again.',
                'data' => $r['comm']]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        } finally {
            foreach ($temps as $t) {
                if (is_file($t)) {
                    @unlink($t);
                }
            }
        }
    }

    // POST comms/{id}/followup {title, detail, owner_id, owner_name, due_on, comm_id}
    // POST comms/{id}/followup/{fid} {status, title, detail, owner_id, owner_name, due_on, done_note}
    public function followup($id, $fid = null)
    {
        try {
            $b = $this->body();
            $c = $this->comms();
            $r = $fid ? $c->setFollowup((int) $fid, $b) : $c->addFollowup((int) $id, $b);
            return $this->json(['status' => 200, 'message' => $fid ? 'Follow-up updated.' : 'Follow-up added.', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
