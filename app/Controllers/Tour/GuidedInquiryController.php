<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;

/**
 * tf-guided-r1 (2 Nov 2026) — the Guided Inquiry panel, tour/intake/guided.
 *
 * A presentation layer over the EXISTING Tour Inquiry. It introduces no model, no table and no second
 * way to save anything: every read and every write goes to the endpoints TourIntakeController already
 * serves, so email reading, pasting, manual entry, quotations, bookings, confirmation emails,
 * automation and audit all keep working exactly as they do on the classic panel. The classic panel
 * stays reachable and unchanged at tour/intake?classic=1.
 *
 * The privilege is the Tour Inquiry privilege — this page grants nothing of its own.
 */
class GuidedInquiryController extends BaseController
{
    public const URL = 'tour/intake';          // the privilege this page rides on

    private function db()
    {
        return \Config\Database::connect();
    }

    private function userId(): ?int
    {
        $i = session()->get('user_id') ?? session()->get('id');
        return $i === null ? null : (int) $i;
    }

    /** The same check the classic panel makes: the Tour Inquiry privilege, on the server. */
    private function holds(string $url): bool
    {
        try {
            return (bool) $this->db()->query('SELECT 1 FROM user_priv_q WHERE user_id = ? AND url = ? LIMIT 1',
                [$this->userId(), $url])->getRowArray();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function index()
    {
        if (!$this->holds(self::URL)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 403,
                'message' => 'Tour Inquiry needs its own privilege, which Management grants under Settings → Group Privileges.',
            ]);
        }

        try {
            $data = (new MenuLoader())->loadMenuData();
        } catch (\Throwable $e) {
            $data = ['user_role' => session()->get('group_id'), 'allowed_menu_list' => []];
        }
        $data['user']          = (string) session()->get('username');
        $data['intake_url']    = self::URL;
        $data['classic_url']   = self::URL . '?classic=1';
        $data['guided_url']    = self::URL . '/guided';
        $data['pricing_url']   = TourIntakeController::PRICING_URL;
        $data['sees_pricing']  = $this->holds(TourIntakeController::PRICING_URL);
        $data['can_issue']     = $this->holds(\App\Libraries\TourFlow\StaffTaskMap::ISSUE_URL);   // tf-panel2-r1
        // tf-confirmux-r1: ?id= takes the row's id as before, and also the inquiry number as it is written
        // on the screen (1046, INQ 1046, inq-1046) — a bookmark or a typed link opens the right inquiry
        $data['open_id']       = $this->openId((string) ($this->request->getGet('id') ?? ''));

        return view('pages/tour/intake_guided', $data);
    }

    /** The inquiry a ?id= points at: its own id, or the inquiry number shown on the screen. 0 when there is none. */
    private function openId(string $raw): int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 0;
        }
        if (ctype_digit($raw)) {
            $n = (int) $raw;
            try {
                $r = db_connect()->query('SELECT id FROM tf_intake WHERE id = ? LIMIT 1', [$n])->getRowArray();
                if ($r) {
                    return (int) $r['id'];
                }
            } catch (\Throwable $e) {
                return $n;
            }
        }
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '' || $digits === null) {
            return 0;
        }
        try {
            $r = db_connect()->query("SELECT id FROM tf_intake WHERE inquiry_no = ? OR REPLACE(inquiry_no, ' ', '') = ? LIMIT 1",
                ['INQ ' . $digits, 'INQ' . $digits])->getRowArray();
            return $r ? (int) $r['id'] : (ctype_digit($raw) ? (int) $raw : 0);
        } catch (\Throwable $e) {
            return ctype_digit($raw) ? (int) $raw : 0;
        }
    }
}
