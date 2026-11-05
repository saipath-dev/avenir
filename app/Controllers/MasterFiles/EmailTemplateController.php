<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Activity;
use App\Libraries\TourFlow\EmailTemplates;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Email Templates — master_files/email_templates (tf-mailtpl-r1, 4 Nov 2026)
 *
 * The saved wording of every email the system sends: the quotation and itinerary to the agent, the
 * final tour confirmation, the hotel reservation request and voucher, the restaurant reservation
 * request and voucher, the driver, guide and transport request, and the activity and other supplier
 * request. Each template has a clear name, the window it belongs to, a subject and a message. Words
 * in braces — {agent}, {tour_no}, {dates}, {hotels} … — are filled from the tour when the email is
 * prepared; a word the tour cannot fill is left empty and named on the screen, never invented.
 *
 * Staff edit the prepared message for one email on the send window itself; that never touches the
 * saved template. Only this page changes the saved wording, and only whoever holds this menu URL
 * reaches it (one menu URL = one privilege, UserPermissionFilter). A template is archived, never
 * deleted, so an email that used it keeps its trail; what was actually sent is kept on the tour's
 * communication record as it went and is not touched by a later change here.
 */
class EmailTemplateController extends BaseController
{
    public const URL = 'master_files/email_templates';

    private ?array $menuData = null;

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
        return Staff::displayName($this->db(), $this->userId(), $this->user());
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

    /** Whether the signed-in group holds this page's own privilege — the same one the sidebar reads. */
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

    private function denied()
    {
        return $this->json(['status' => 403, 'message' => 'Changing the saved email wording needs the Email Templates privilege. Your own copy of an email can still be edited on the send window.'], 403);
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

    private function payload(): array
    {
        $t = new EmailTemplates($this->db());
        return [
            'rows'       => $t->all(),
            'categories' => EmailTemplates::CATEGORIES,
            'words'      => EmailTemplates::WORDS,
            'available'  => $t->available(),
            'can_edit'   => $this->allowed(),
            'me'         => $this->actorName(),
        ];
    }

    // GET master_files/email_templates
    public function index()
    {
        $data = $this->menuData();
        $data['user']     = $this->user();
        $data['page_url'] = self::URL;
        $data['can_edit'] = $this->allowed();
        return view('pages/master_files/email_templates', $data);
    }

    // GET master_files/email_templates/data
    public function data()
    {
        try {
            return $this->json(['status' => 200, 'data' => $this->payload()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/email_templates/save {id?, name, category, subject, message, is_default}
    public function save()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $r = (new EmailTemplates($this->db()))->save($this->body(), $this->user());
            if ($r['changes']) {
                $new = count(array_filter($r['changes'], static fn ($c) => $c['from'] === null)) === count($r['changes']);
                Activity::master($this->db(), $this->user(), 'email_template', (int) $r['template']['id'],
                    'Email template "' . $r['template']['name'] . '" ' . ($new ? 'created' : 'changed'), $r['changes'], $this->userId(), $this->actorName());
            }
            return $this->json(['status' => 200,
                'message' => 'Template "' . $r['template']['name'] . '" saved. Every send window of its group offers it from now on; emails already sent are unchanged.',
                'data' => $this->payload()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/email_templates/(:num)/(archive|restore)
    public function action($id, $what)
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $t = new EmailTemplates($this->db());
            if ((string) $what === 'archive') {
                $row = $t->archive((int) $id, $this->user());
                Activity::master($this->db(), $this->user(), 'email_template', (int) $id, 'Email template "' . $row['name'] . '" archived', [['what' => 'archived', 'from' => 'no', 'to' => 'yes']], $this->userId(), $this->actorName());
                return $this->json(['status' => 200, 'message' => 'Template "' . $row['name'] . '" is off the send windows. It is kept whole, and can be put back.', 'data' => $this->payload()]);
            }
            if ((string) $what === 'restore') {
                $row = $t->restore((int) $id);
                Activity::master($this->db(), $this->user(), 'email_template', (int) $id, 'Email template "' . $row['name'] . '" restored', [['what' => 'archived', 'from' => 'yes', 'to' => 'no']], $this->userId(), $this->actorName());
                return $this->json(['status' => 200, 'message' => 'Template "' . $row['name'] . '" is back on the send windows.', 'data' => $this->payload()]);
            }
            return $this->json(['status' => 404, 'message' => 'Unknown action.'], 404);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST master_files/email_templates/seed — write the standard wording for any group that has none
    public function seed()
    {
        if (!$this->allowed()) {
            return $this->denied();
        }
        try {
            $t = new EmailTemplates($this->db());
            $n = $t->seed($this->user());
            if ($n === 0) {
                $n = $t->seedMissing($this->user());
            }
            return $this->json(['status' => 200,
                'message' => $n > 0
                    ? $n . ' standard template' . ($n === 1 ? '' : 's') . ' written for the groups that had none. Nothing already here was changed.'
                    : 'Every group already has its wording. Nothing was changed.',
                'data' => $this->payload()]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }
}
