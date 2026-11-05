<?php

namespace App\Controllers\Accounts;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\AccountRunningQ2Model;
use App\Models\VoucherPaymentModel;
use App\Models\AccountPostingModel;

class AccountFilterController extends BaseController
{
	protected $AccountRunningQ2Model;

	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
		$this->AccountRunningQ2Model = new AccountRunningQ2Model();
	}

	// route : base_url/accounts/account_filter
	public function index()
	{
		$data = array();

		// Load menu data using the custom library
		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();

		$pname = $this->request->getGet('acc');
		$date1 = $this->request->getGet('date1');
		$date2 = $this->request->getGet('date2');

		$data['is_search'] = false;

		if (!empty($pname) && !empty($date1) && !empty($date2)) {

			$data['is_search'] = true;

			$data['PName'] = $pname;
			$data['date1'] = $date1;
			$data['date2'] = $date2;

			$data['load_data'] = $this->load_data($pname, $date1, $date2);
			$method = $this->get_type($pname);
			$data['type'] = $method[0]['balflag'];

			$data['balance'] = $this->get_opening_balance($pname, $date1, $data['type']);

			// check the opening balance is null or not
			if ($data['balance'] == null) {
				$data['balance'] = 0;
			} else {
				$data['balance'] = $data['balance'][0]['opening_balance'];
			}

			// var_dump($data['opn_balance']);
			// die();
		}

		return view('pages/accounts/account_filter', $data);
	}

	// route : base_url/accounts/account_filter/fetch
	public function fetch()
	{
		$uri = service('uri');

		$segment4 = $uri->getSegment(4);


		$vouchermode = new VoucherPaymentModel();

		// Assuming your model method returns data
		$data = $vouchermode->fetch_filter_data($segment4);

		// Convert data to JSON and output it
		echo json_encode($data);
	}

	public function load_data($pname, $date1, $date2)
	{
		$AccountRunningQ2Model = new AccountRunningQ2Model();
		$result = $AccountRunningQ2Model->select('TourId, BName, TName, HName, PName, Dr, Cr, Date, Discription, Recpt_No')
			->where('PName', $pname)
			->where("date(Date) BETWEEN '$date1' AND '$date2'")
			->orderBy('Date', 'ASC')
			->findAll();

		return $result;

		// $AccountRunningQ2Model = new AccountRunningQ2Model();
		// $result = $AccountRunningQ2Model->select('consignee.manual_id, BName, TName, HName, PName, Dr, Cr, Date, Discription, Recpt_No')
		// 	->join('consignee', 'consignee.tour_id = account_running_q2.TourId', 'left')
		// 	->where('PName', $pname)
		// 	->where("date(Date) BETWEEN '$date1' AND '$date2'")
		// 	->orderBy('Date', 'ASC')
		// 	->findAll();

		// return $result;
	}

	public function get_opening_balance($pname, $date, $type)
	{

		$AccountRunningQ2Model = new AccountRunningQ2Model();

		if ($type === 1) {
			$result = $AccountRunningQ2Model->select('SUM(Dr) - SUM(Cr) as opening_balance')
				->where('PName', $pname)
				->where("date(Date) < '$date'")
				->groupBy('PName')
				->find();
		} else {
			$result = $AccountRunningQ2Model->select('SUM(Cr) - SUM(Dr) as opening_balance')
				->where('PName', $pname)
				->where("date(Date) < '$date'")
				->groupBy('PName')
				->find();
		}

		return $result;
	}

	public function get_type($pname)
	{
		$AccountPostingModel = new AccountPostingModel();
		$result = $AccountPostingModel->select('account_basement.balflag')
			->join('account_basement', 'account_basement.B_Code = account_posting.B_Code')
			->where('account_posting.PName', $pname)
			->findAll();

		return $result;
	}
}
