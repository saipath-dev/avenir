<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Libraries\TourFlow\StaffProfiles;

/**
 * Pilot → Staff Work Descriptions — report/staff_work_descriptions (tf-staffno-r1, 7 Nov 2026).
 * System Administrator only, unless the Super Admin opens this exact address for someone on the Staff Access Control Panel.
 * The route filter checks the menu privilege; every method here checks it again, the export included, so a direct address
 * or a download is refused exactly as the page is.
 */
class StaffWorkDescriptionsController extends BaseController
{
    private function db()
    {
        return \Config\Database::connect();
    }

    private function refusal()
    {
        $uid = (int) session()->get('user_id');
        if (StaffProfiles::mayView($this->db(), $uid ?: null, (int) session()->get('group_id'))) {
            return null;
        }
        if ($this->request->isAJAX() || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json')) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 403, 'message' => 'Staff Work Descriptions is restricted to the System Administrator.']);
        }
        return redirect()->to(base_url('403'));
    }

    public function index()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $data = (new MenuLoader())->loadMenuData();
        $data['rows'] = StaffProfiles::rows($this->db());
        $data['export_url'] = base_url(StaffProfiles::REPORT_URL . '/export');
        return view('pages/report/staff_work_descriptions', $data);
    }

    public function export()
    {
        if ($r = $this->refusal()) {
            return $r;
        }
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['Staff no', 'Employee name', 'Job title', 'Group', 'Work description', 'Computer account', 'Account status']);
        foreach (StaffProfiles::rows($this->db()) as $r) {
            fputcsv($fh, [$r['staff_no'], $r['name'], $r['title'], $r['group'], $r['duties'], $r['account'] ?: '', $r['account'] ? ((int) $r['account_enabled'] === 1 ? 'enabled' : 'disabled') : '']);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="staff-work-descriptions-' . date('Ymd') . '.csv"')
            ->setHeader('Cache-Control', 'no-store')->setBody($csv);
    }
}
