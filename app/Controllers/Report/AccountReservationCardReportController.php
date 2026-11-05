<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;

class AccountReservationCardReportController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route: base_url/report/acc_reservation_card_report
    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $manual_id = $this->request->getGet('manual_id');
        $manual_id = $manual_id ? trim($manual_id) : null;

        $data['manual_id']  = $manual_id;
        $data['is_search']  = false;
        $data['resultlist'] = [];

        if (!empty($manual_id)) {
            $data['is_search'] = true;

            $db = \Config\Database::connect();

            $rows = $db->query("
                SELECT
                    c.tour_id,
                    c.manual_id,
                    c.consignee     AS pax_name,
                    c.agent_name,
                    c.agent,
                    c.create_user   AS created_by,
                    c.create_time,
                    c.start_date,
                    c.end_date,
                    c.passenger_count
                FROM consignee c
                WHERE c.manual_id = ?
                LIMIT 1
            ", [$manual_id])->getResultArray();

            $data['resultlist'] = $rows;
        }

        return view('pages/report/acc_reservation_card_report', $data);
    }
}
