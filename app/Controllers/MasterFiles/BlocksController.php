<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\Blocks;
use App\Libraries\TourFlow\Staff;

/**
 * Master Files → Blocks & Investigations — master_files/blocks (tf-stage8, 30 Oct 2026).
 * Super Admin only (Shantha, 30 Oct 2026): every method checks the signed-in group itself, over and above the menu privilege
 * (granted to the System Administrator alone at install) — a direct URL or request from any other account answers 403.
 * The Super Admin sees the list, the restricted notes and the log, pick the exact record (searched in the masters), see what it
 * touches, apply with reason and reference, add notes, and lift with a reason. Nobody else reaches this page; every
 * other route only meets the refusal.
 */
class BlocksController extends BaseController
{
    public const URL = Blocks::URL;

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

    private function name(): string
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

    private function json(array $payload, int $code = 200)
    {
        return $this->response->setStatusCode($code)->setJSON($payload);
    }

    /** The one gate: only the System Administrator group (the Super Admin) — never by menu grant alone. */
    private function refusal()
    {
        if ((int) session()->get('group_id') === 1) {
            return null;
        }
        return $this->json(['status' => 403, 'message' => 'Blocks & Investigations is for the Super Admin only.'], 403);
    }

    private function body(): array
    {
        $json = $this->request->getJSON(true);
        return is_array($json) ? $json : ($this->request->getPost() ?: []);
    }

    // GET master_files/blocks
    public function index()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $data = $this->menuData();
        $data['page_url']  = self::URL;
        $data['installed'] = Blocks::installed($this->db());
        $data['kinds']     = Blocks::KINDS;
        $data['types']     = Blocks::TYPES;
        $data['open_id']   = (int) ($this->request->getGet('id') ?? 0);
        return view('pages/master_files/blocks', $data);
    }

    // GET master_files/blocks/data
    public function data()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        return $this->json(['status' => 200, 'data' => Blocks::all($this->db())]);
    }

    // GET master_files/blocks/one?id=
    public function one()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = Blocks::get($this->db(), (int) ($this->request->getGet('id') ?? 0));
        return $b ? $this->json(['status' => 200, 'data' => $b]) : $this->json(['status' => 404, 'message' => 'No such block.'], 404);
    }

    // GET master_files/blocks/find?type=&q= — the exact record, searched in its master
    public function find()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $db = $this->db();
        $type = (string) ($this->request->getGet('type') ?? '');
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if (mb_strlen($q) < 2) {
            return $this->json(['status' => 200, 'data' => []]);
        }
        $like = '%' . $q . '%';
        $rows = [];
        try {
            if ($type === 'supplier') {
                foreach ($db->query("SELECT s.Sup_Acc_No k, s.Name n, (SELECT GROUP_CONCAT(c.kind) FROM tf_crew_person c WHERE c.supplier_acc = s.Sup_Acc_No AND c.archived_at IS NULL) crew FROM supplier s WHERE s.Name LIKE ? OR s.Sup_Acc_No LIKE ? ORDER BY s.Name LIMIT 12", [$like, $like])->getResultArray() as $r) {
                    $rows[] = ['key' => $r['k'], 'label' => $r['n'] . ' (' . $r['k'] . ')' . ($r['crew'] ? ' — ' . $r['crew'] : '')];
                }
            } elseif ($type === 'hotel') {
                foreach ($db->query("SELECT id, name, hotel_code, primary_sup_acc_no FROM hr2_hotel WHERE name LIKE ? OR hotel_code LIKE ? OR primary_sup_acc_no LIKE ? ORDER BY name LIMIT 12", [$like, $like, $like])->getResultArray() as $r) {
                    $rows[] = ['key' => (string) $r['id'], 'label' => $r['name'] . ' (' . $r['hotel_code'] . ($r['primary_sup_acc_no'] ? ' · ' . $r['primary_sup_acc_no'] : '') . ')'];
                }
            } elseif ($type === 'customer') {
                foreach ($db->query("SELECT Sup_Acc_No k, Name n, long_name l, agent FROM customers WHERE Name LIKE ? OR long_name LIKE ? OR Sup_Acc_No LIKE ? ORDER BY agent DESC, Name LIMIT 12", [$like, $like, $like])->getResultArray() as $r) {
                    $rows[] = ['key' => $r['k'], 'label' => ($r['l'] ?: $r['n']) . ' (' . $r['k'] . ')' . ((int) $r['agent'] === 1 ? ' — agent' : '')];
                }
            } elseif ($type === 'account') {
                foreach ($db->query("SELECT a.id, a.bank_name, a.holder_name, a.account_no, a.sup_acc, s.Name FROM tpd_supplier_account a LEFT JOIN supplier s ON s.Sup_Acc_No = a.sup_acc WHERE a.active = 1 AND (s.Name LIKE ? OR a.holder_name LIKE ? OR a.sup_acc LIKE ? OR a.account_no LIKE ?) ORDER BY s.Name LIMIT 12", [$like, $like, $like, $like])->getResultArray() as $r) {
                    $rows[] = ['key' => (string) $r['id'], 'label' => ($r['Name'] ?: $r['sup_acc']) . ' — ' . ($r['bank_name'] ?: 'bank?') . ' · ' . ($r['holder_name'] ?: 'holder?') . ' · …' . substr(preg_replace('/\s+/', '', (string) $r['account_no']), -4)];
                }
            }
        } catch (\Throwable $e) {
            return $this->json(['status' => 400, 'message' => $e->getMessage()]);
        }
        return $this->json(['status' => 200, 'data' => $rows]);
    }

    // GET master_files/blocks/preview?type=&key=
    public function preview()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        return $this->json(['status' => 200, 'data' => Blocks::preview($this->db(), (string) ($this->request->getGet('type') ?? ''), (string) ($this->request->getGet('key') ?? ''))]);
    }

    // POST master_files/blocks/apply {target_type, target_key, kind, reason, reference, notes}
    public function apply()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $r = Blocks::apply($this->db(), $this->body(), $this->user(), $this->name());
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message'], 'id' => $r['id'] ?? null]);
    }

    // POST master_files/blocks/lift {id, reason}
    public function lift()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        $r = Blocks::lift($this->db(), (int) ($b['id'] ?? 0), (string) ($b['reason'] ?? ''), $this->user(), $this->name());
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }

    // POST master_files/blocks/note {id, text}
    public function note()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $b = $this->body();
        $r = Blocks::note($this->db(), (int) ($b['id'] ?? 0), (string) ($b['text'] ?? ''), $this->user(), $this->name());
        return $this->json(['status' => $r['ok'] ? 200 : 400, 'message' => $r['message']]);
    }
}
