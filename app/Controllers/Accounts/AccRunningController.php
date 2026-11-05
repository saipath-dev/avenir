<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\JournalEntryModel;

class AccRunningController extends BaseController
{
    protected $BankReconcilModel;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        return view('pages/accounts/acc_running_records', $data);
    }

    public function fetch($segment4)
    {
        $journalentrymodel = new JournalEntryModel();
        $data = $journalentrymodel->fetch_data($segment4);
        echo json_encode($data);
    }
}
