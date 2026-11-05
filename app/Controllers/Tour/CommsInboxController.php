<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\CommsInbox;
use App\Libraries\TourFlow\CommsQuoteDraft;
use App\Libraries\TourFlow\Staff;

/**
 * tf-comms-r3 (27 Oct 2026, Shantha) — incoming e-mail: the "Unmatched incoming e-mails" queue, the mailbox checks,
 * and the scheduled-task entry point. URL tour/intake/comms/inbox (under the Tour Inquiry privilege), and
 * api/tourflow/mail_poll/<token> for the scheduled task (no sign-in: the token alone, over the local network / HTTPS).
 */
class CommsInboxController extends BaseController
{
    public const URL = 'tour/intake/comms/inbox';

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

    private function inbox(): CommsInbox
    {
        return new CommsInbox($this->db(), $this->user(), $this->userId(), $this->actorName());
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

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        if (stripos((string) $this->request->getHeaderLine('Content-Type'), 'application/json') !== false) {
            try {
                $j = $this->request->getJSON(true);
                if (is_array($j)) {
                    return $j;
                }
            } catch (\Throwable $e) {
            }
        }
        return $this->request->getPost() ?: [];
    }

    private function fail(\Throwable $e)
    {
        return $this->json(['status' => 400, 'message' => $e->getMessage()]);
    }

    // GET tour/intake/comms/inbox — the queue page
    public function index()
    {
        $data = $this->menuData();
        $data['user'] = $this->user();
        $data['actor_name'] = $this->actorName();
        $data['inbox_url'] = self::URL;
        $data['comms_url'] = CommsController::URL;
        $data['intake_url'] = 'tour/intake';
        $data['installed'] = CommsInbox::ready($this->db());
        $data['settings'] = CommsInbox::settings($this->db());
        $data['sysadmin'] = (int) ($data['user_role'] ?? 0) === 1;
        return view('pages/tour/comms_inbox', $data);
    }

    // GET inbox/data?status=unmatched|matched|discarded|skipped|all
    public function data()
    {
        try {
            $status = (string) ($this->request->getGet('status') ?: 'unmatched');
            $ib = $this->inbox();
            return $this->json(['status' => 200, 'data' => ['rows' => $ib->queue($status), 'counts' => $ib->counts(), 'settings' => CommsInbox::settings($this->db())]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET inbox/message/{id}
    public function message($id)
    {
        $m = $this->inbox()->message((int) $id);
        return $m ? $this->json(['status' => 200, 'data' => $m]) : $this->json(['status' => 404, 'message' => 'Message not found.']);
    }

    // GET inbox/attachment/{attId}
    public function attachment($attId)
    {
        $a = $this->inbox()->attachmentPath((int) $attId);
        if (!$a || !is_file($a['path'])) {
            return $this->json(['status' => 404, 'message' => 'Attachment not found.'], 404);
        }
        $inline = in_array(strtolower((string) pathinfo($a['original_name'], PATHINFO_EXTENSION)), ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'txt'], true);
        return $this->response->setHeader('Content-Type', $a['mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $a['original_name']) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) file_get_contents($a['path']));
    }

    // POST inbox/check — "Check mailbox now"
    public function check()
    {
        try {
            @set_time_limit(300);
            $r = $this->inbox()->check('manual');
            return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message'], 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST inbox/auto — the quiet check a Communications page triggers (only when the last check is old enough)
    public function auto()
    {
        try {
            @set_time_limit(120);
            $r = $this->inbox()->autoCheck();
            return $this->json(['status' => 200, 'ran' => $r !== null, 'message' => $r['message'] ?? '', 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->json(['status' => 200, 'ran' => false, 'message' => $e->getMessage()]);
        }
    }

    // POST api/tourflow/mail_poll with header X-TourFlow-Token — the scheduled task's web entry, OFF by default (Tour Desk Admin →
    // Settings → Incoming mailbox); the preferred way is `php spark tourflow:inbox` from the server's own scheduler. The token travels
    // in a header (never in the URL, so never in a web-server access log), is compared against its hash, and is never logged here.
    public function poll()
    {
        $db = $this->db();
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setHeader('Allow', 'POST')->setJSON(['status' => 405, 'message' => 'POST only.']);
        }
        $token = trim((string) $this->request->getHeaderLine('X-TourFlow-Token'));
        if (!CommsInbox::pollTokenOk($db, $token)) {
            usleep(1500000);
            return $this->response->setStatusCode(403)->setHeader('Cache-Control', 'no-store')->setJSON(['status' => 403, 'message' => 'Forbidden.']);
        }
        @set_time_limit(300);
        $r = (new CommsInbox($db, 'scheduled task'))->check('scheduled');
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message'], 'data' => ['new' => $r['new'], 'filed' => $r['filed'], 'unmatched' => $r['unmatched'], 'duplicates' => $r['duplicates'], 'skipped' => $r['skipped'], 'drafts' => $r['drafts']]]);
    }

    // POST inbox/{id}/attach {intake_id, comm_id?, reason}
    public function attach($id)
    {
        try {
            $b = $this->body();
            $reason = trim((string) ($b['reason'] ?? ''));
            if (mb_strlen($reason) < 5) {
                throw new \InvalidArgumentException('Say why this message belongs to that record (at least five characters).');
            }
            $c = $this->inbox()->attachTo((int) $id, (int) ($b['intake_id'] ?? 0), !empty($b['comm_id']) ? (int) $b['comm_id'] : null, $reason, 'staff');
            return $this->json(['status' => 200, 'message' => 'Filed on the record. It is on that inquiry\'s Communications & History now.', 'data' => $c]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST inbox/{id}/discard {why}
    public function discard($id)
    {
        try {
            $b = $this->body();
            $this->inbox()->discard((int) $id, trim((string) ($b['why'] ?? '')));
            return $this->json(['status' => 200, 'message' => 'Discarded from the queue (the message stays on file and in the mailbox).']);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST inbox/{id}/draft — read as a new inquiry and prepare a quotation draft for review
    public function draft($id)
    {
        try {
            @set_time_limit(300);
            $r = (new CommsQuoteDraft($this->db(), $this->user(), $this->userId(), $this->actorName()))->fromMail((int) $id, false);
            return $this->json(['status' => 200, 'message' => $r['message'], 'data' => $r]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // GET inbox/find?q= — inquiries / tours to attach a message to (number, name, agent)
    public function find()
    {
        try {
            $q = trim((string) $this->request->getGet('q'));
            if ($q === '') {
                return $this->json(['status' => 200, 'data' => []]);
            }
            $like = '%' . $q . '%';
            $rows = $this->db()->query('SELECT i.id, i.inquiry_no, i.tour_name, i.agent_name, i.agent_email, i.arrival_date, i.status, t.tour_no FROM tf_intake i LEFT JOIN tf_tour t ON t.intake_id = i.id
                WHERE i.archived_at IS NULL AND (i.inquiry_no LIKE ? OR t.tour_no LIKE ? OR i.tour_name LIKE ? OR i.agent_name LIKE ? OR i.agent_email LIKE ?) ORDER BY i.id DESC LIMIT 20', [$like, $like, $like, $like, $like])->getResultArray();
            return $this->json(['status' => 200, 'data' => $rows]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
