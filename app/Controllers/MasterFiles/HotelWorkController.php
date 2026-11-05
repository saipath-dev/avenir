<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\HotelRates\HotelRateService;
use App\Libraries\HotelRates\WorkQueueService;
use App\Libraries\MenuLoader;

/**
 * Hotel Incomplete Work — master_files/hotel_work
 *
 * The permanent queue of what the system found missing or doubtful around hotel
 * rates and payments: confirmed nights with no contract rate, hotels missing from
 * the panel, drafts waiting for approval, rate sheets without a supplier record,
 * unusual margins, unmapped countries, merges to confirm, rate-sheet queries.
 * Items are detected by WorkQueueService::scan() (on panel open at most every
 * Settings › work_scan_minutes, after costing, after approvals, from Refresh)
 * and closed by the next scan that no longer finds them, or by a person here.
 *
 * One menu entry = one privilege (UserPermissionFilter): everyone whose group
 * holds "Hotel Incomplete Work" may view, refresh, add, close and reopen items.
 */
class HotelWorkController extends BaseController
{
    public const URL = 'master_files/hotel_work';

    private ?array $menuData = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    private function user(): string
    {
        return (string) session()->get('username');
    }

    private function svc(): HotelRateService
    {
        return new HotelRateService(\Config\Database::connect(), $this->user());
    }

    private function queue(?HotelRateService $svc = null): WorkQueueService
    {
        return new WorkQueueService(\Config\Database::connect(), $this->user(), $svc ?? $this->svc());
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

    private function isSysAdmin(): bool
    {
        return (int) ($this->menuData()['user_role'] ?? 0) === 1;
    }

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }
        return $this->request->getPost() ?: [];
    }

    private function fail(\Throwable $e, int $status = 500)
    {
        return $this->json(['status' => $status, 'message' => $e->getMessage()]);
    }

    // GET master_files/hotel_work[?kind=&status=&q=&hotel=<acc>&tour=<id>]
    public function index()
    {
        $data = $this->menuData();
        $data['user'] = $this->user();
        $data['is_sysadmin'] = $this->isSysAdmin();
        $data['panel_url'] = HotelRateV2Controller::PANEL_URL;
        $data['work_url'] = self::URL;
        $data['kinds'] = WorkQueueService::KINDS;
        $data['installed'] = false;
        $data['scan'] = null;
        $data['counts'] = [];
        $data['preselect'] = [
            'kind' => (string) $this->request->getGet('kind'),
            'status' => (string) ($this->request->getGet('status') ?: 'open'),
            'q' => (string) $this->request->getGet('q'),
            'hotel' => (string) $this->request->getGet('hotel'),
            'tour' => (string) $this->request->getGet('tour'),
        ];
        try {
            $db = \Config\Database::connect();
            $version = \App\Libraries\HotelRates\Installer::installedVersion($db);
            $data['installed'] = $version !== null && $version >= \App\Libraries\HotelRates\Installer::SCHEMA_VERSION;
            if ($data['installed']) {
                $svc = $this->svc();
                $q = $this->queue($svc);
                $data['scan'] = $q->scanIfDue((int) ($svc->settings()->get('work_scan_minutes') ?? 10));
                $data['counts'] = $q->counts();
                $data['last_scan'] = $svc->meta('work_scan_at');
            }
        } catch (\Throwable $e) {
            $data['installed'] = false;
            $data['error'] = $e->getMessage();
        }
        return view('pages/master_files/hotel_work', $data);
    }

    // GET list?status=open|done|ignored|all&kind=&q=&hotel_id=&tour_id=
    public function list()
    {
        try {
            $f = [
                'status' => (string) ($this->request->getGet('status') ?: 'open'),
                'kind' => (string) $this->request->getGet('kind'),
                'q' => (string) $this->request->getGet('q'),
                'hotel_id' => (int) $this->request->getGet('hotel_id'),
                'tour_id' => (string) $this->request->getGet('tour_id'),
                'limit' => (int) ($this->request->getGet('limit') ?: 500),
            ];
            $acc = trim((string) $this->request->getGet('hotel'));
            if ($acc !== '' && !$f['hotel_id']) {
                $h = $this->svc()->findHotelByAcc($acc);
                if ($h) {
                    $f['hotel_id'] = (int) $h['id'];
                } else {
                    $f['q'] = $acc;
                }
            }
            $q = $this->queue();
            return $this->json(['status' => 200, 'data' => ['items' => $q->list($f), 'counts' => $q->counts(), 'open' => $q->openCount(), 'last_scan' => $this->svc()->meta('work_scan_at')]]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST scan — run every detector now
    public function scan()
    {
        try {
            $res = $this->queue()->scan();
            $msg = "Scan finished: {$res['new']} new, {$res['reopened']} reopened, {$res['closed']} closed by the system, {$res['open']} open.";
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST item — add an item by hand {kind, level, title, detail, hotel_acc, manual_id|tour_id, ref_date, ref_date_to, amount}
    public function add()
    {
        try {
            $b = $this->body();
            $id = $this->queue()->register($b);
            return $this->json(['status' => 200, 'message' => 'Item added.', 'data' => $this->queue()->get($id)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST item/(:num)/status {status: open|done|ignored, note, scope?: 'hotel'} — scope 'hotel' takes the hotel's other
    // open items of the same kind along (same note); nothing is deleted, each can be reopened
    public function status($id)
    {
        try {
            $b = $this->body();
            $note = isset($b['note']) ? (string) $b['note'] : null;
            $item = $this->queue()->resolve((int) $id, (string) ($b['status'] ?? ''), $note);
            $also = (($b['scope'] ?? '') === 'hotel') ? $this->queue()->resolveHotelSiblings((int) $id, (string) $item['status'], $note) : 0;
            $msg = 'Item ' . $item['status'] . '.' . ($also ? " {$also} other open item" . ($also === 1 ? '' : 's') . ' of ' . ($item['hotel_name'] ?: 'this hotel') . ' ' . $item['status'] . ' with it.' : '');
            return $this->json(['status' => 200, 'message' => $msg, 'data' => $item, 'also' => $also]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET suppliers?q= — hotel suppliers by name, hotel code (HTL-017 / H017) or account, for the "Attach supplier account" dialog
    public function suppliers()
    {
        try {
            $q = (string) $this->request->getGet('q');
            return $this->json(['status' => 200, 'data' => $this->queue()->searchSuppliers($q, 15)]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST item/(:num)/attach_supplier {acc} — rate sheet without a supplier account → hotel (with its code) + draft plan
    public function attachSupplier($id)
    {
        try {
            $b = $this->body();
            $res = $this->queue()->attachSupplierAndLoad((int) $id, (string) ($b['acc'] ?? ''));
            return $this->json(['status' => 200, 'message' => $res['note'], 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // POST item/(:num)/supplier_details {chain, address, contact_person, phone, email, notes}
    // Stages what is already known about a missing supplier — never a code, never guessed —
    // so it survives until the real record exists and "Attach supplier account" finishes the job.
    public function supplierDetails($id)
    {
        try {
            $b = $this->body();
            $item = $this->queue()->saveSupplierDetails((int) $id, $b);
            return $this->json(['status' => 200, 'message' => 'Supplier details saved.', 'data' => $item]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }

    // GET export?format=text|json&kind=&q= — the open items, to hand to Claude or a colleague
    public function export()
    {
        try {
            $f = ['kind' => (string) $this->request->getGet('kind'), 'q' => (string) $this->request->getGet('q'), 'status' => 'open', 'limit' => 2000];
            $q = $this->queue();
            if (($this->request->getGet('format') ?: 'text') === 'json') {
                return $this->response->setHeader('Content-Disposition', 'attachment; filename="hotel_incomplete_work_' . date('Ymd_Hi') . '.json"')
                    ->setJSON(['exported_at' => date('c'), 'items' => $q->list($f)]);
            }
            return $this->response->setContentType('text/plain; charset=UTF-8')->setBody($q->exportText($f));
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    // POST load_seed — System Administrator: register the items in writable/hotel_rates_entry/work_items.json
    public function loadSeed()
    {
        if (!$this->isSysAdmin()) {
            return $this->json(['status' => 403, 'message' => 'Only the System Administrator loads seed files.']);
        }
        try {
            $path = WRITEPATH . 'hotel_rates_entry/work_items.json';
            $res = $this->queue()->loadSeedFile($path);
            return $this->json(['status' => 200, 'message' => "{$res['loaded']} item(s) registered from work_items.json" . ($res['failed'] ? ', ' . count($res['failed']) . ' failed' : '') . '.', 'data' => $res]);
        } catch (\Throwable $e) {
            return $this->fail($e, 400);
        }
    }
}
