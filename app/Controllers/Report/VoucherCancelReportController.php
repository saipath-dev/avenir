<?php

namespace App\Controllers\Report;

use App\Controllers\BaseController;


use App\Libraries\MenuLoader;
use App\Models\SupplierModel;
use App\Models\VoucherPaymentModel;
use App\Models\AccountPostingModel;
use App\Models\VoucherInvoiceModel;
use App\Models\AccountRunningModel;
use App\Models\AccountRunningDelModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourQuotationsModel;

class VoucherCancelReportController extends BaseController
{
	public function __construct()
	{
		date_default_timezone_set('Asia/Colombo');
	}

	// route : base_url/report/daterange_price_report


	public function index()
	{
		$data = array();

		$menuLoader = new MenuLoader();
		$data = $menuLoader->loadMenuData();
		$VoucherPaymentModel = new VoucherPaymentModel();


		$startdate = $this->request->getGet('startdate');
		$data['date1'] = $startdate;

		$enddate = $this->request->getGet('enddate');
		$data['date2'] = $enddate;


		// if (!empty($startdate) || !empty($enddate)) {


		// }


		$result = $VoucherPaymentModel->select('voucher.C_Number, consignee.consignee, consignee.agent_name, consignee.tour_id,consignee.manual_id');

		$result->join('consignee', 'consignee.tour_id = voucher.C_Number',);
		$result->groupStart()
		->where('voucher.Aflag', 3)
		// ->orWhere('voucher.Aflag', 3)
		// ->orWhere('voucher.Aflag', 2)
		->groupEnd();
		$result->groupBy('voucher.C_Number');


		$data['payments'] = $result->findAll();
		$voucherpaymentmodel  = new VoucherPaymentModel();

		foreach ($data['payments'] as &$payment) {
			$tour_id = $payment['tour_id'];

			$income = $voucherpaymentmodel->select('SUM(Amount) as tot_amount,COUNT(ID) as v_count,MAX(W_Date) as W_Date')
			->where('C_Number', $tour_id)
				->groupStart() // Start grouping for Aflag
				// ->where('voucher.Aflag', 1)
				->Where('voucher.Aflag', 3)
				// ->orWhere('voucher.Aflag', 2)
				->groupEnd(); // End grouping for Aflag
				// ->where('account_runing.L_Code', '5-7-7-10388')

				if (!empty($startdate) && !empty($enddate)) {
					$income->where('voucher.W_Date >=', $startdate);
					$income->where('voucher.W_Date <=', $enddate);
				}
				$income = $income->first();


				$payment['tot_amount'] = $income['tot_amount'] ?? 0;
				$payment['v_count'] = $income['v_count'] ?? 0;
				$payment['W_Date'] = $income['W_Date'] ?? 0;
			}
			$data['other_vcount'] =  $voucherpaymentmodel->select('count(ID) as other_count,SUM(Amount) as g_tot')->where('Aflag',3)->where('is_expenses !=',1)->first();


			return view('pages/report/voucher_cancel_details', $data);
		}







		public function voucher_details()
		{
			$data = array();

		// Load menu data using the custom library
			$menuLoader = new MenuLoader();
			$data = $menuLoader->loadMenuData();

			$tour_id = $this->request->getGet('tour_id');

			$accountposting = new AccountPostingModel();
			$data['load_bank'] = $accountposting->select('PName, L_Code')
			->where('B_Code', 1)
			->where('T_Code', 6)
			->where('H_Code', 8)
			->findAll();

			$bank = $this->request->getGet('bank');
			$data['bank'] = $bank;

			$startdate = $this->request->getGet('startdate');
			$data['date1'] = $startdate;

			$enddate = $this->request->getGet('enddate');
			$data['date2'] = $enddate;

			$data['is_search'] = false;
			$data['resultlist'] = [];

			$vouchermodel = new VoucherPaymentModel();

		// if (!empty($bank) || !empty($startdate) || !empty($enddate)) {
			$data['is_search'] = true;

			$result = $vouchermodel->select("*");

			if (!empty($bank)) {
				$result->where('Bank_acc', $bank);
				$data['selected_bank'] = $bank;
			}

			if (!empty($startdate) && !empty($enddate)) {
				$result->where("W_Date >=", $startdate)
				->where("W_Date <=", $enddate);
				$data['selected_startdate'] = $startdate;
				$data['selected_enddate'] = $enddate;
			}

		// Adjusting the logic based on the tour type

		// Grouping Aflag conditions
			$result->groupStart()
			// ->where('Aflag', 1)
			->Where('Aflag', 3)
			// ->orWhere('Aflag', 2)
			->groupEnd();

			if ($tour_id == 'other') {
				$result->where('voucher.is_expenses !=', 1);
			// $result->where('C_Number', $tour_id);
			} else {
				$result->where('C_Number', $tour_id);
			}

			$data['resultlist'] = $result->findAll();
		// }

			return view('pages/report/voucher_cancel_report', $data);
		}



		public function voucher_cancel()
		{
			$data = array();

		// Load menu data using the custom library
			$menuLoader = new MenuLoader();
			$data = $menuLoader->loadMenuData();

			$accountposting = new AccountPostingModel();
			$data['load_bank'] =  $accountposting->select('PName,L_Code')
			->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)
			->findAll();

			$bank = $this->request->getGet('bank');
			$data['bank'] = $bank;

			$startdate = $this->request->getGet('startdate');
			$data['date1'] = $startdate;

			$enddate = $this->request->getGet('enddate');
			$data['date2'] = $enddate;

			$data['is_search'] = false;

			$data['resultlist'] = [];

			$vouchermodel = new VoucherPaymentModel();
			if (!empty($bank) || !empty($startdate) || !empty($enddate)) {

				$data['is_search'] = true;
				$result = $vouchermodel->select("*")
				->where('Aflag', 1);

				if (!empty($bank)) {
					$result->where('Bank_acc', $bank);

					$data['selected_bank'] = $bank;
				}

				if (!empty($startdate) && !empty($enddate)) {

					$result->where("C_Date >= '{$startdate}' AND C_Date <= '{$enddate}'");

					$data['selected_startdate'] = $startdate;
					$data['selected_enddate'] = $enddate;
				}


				$results = $result->findAll();
				$data['resultlist'] = $results;
			}



			return view('pages/report/voucher_cancel', $data);
		}




		/******************** SAVE ***********************/
		public function update()
		{
			$id = $this->request->getVar('id');
			$supplier = $this->request->getVar('supplier');

		// Load models
			$vouchermodel = new VoucherPaymentModel();
			$voucherInvoiceModel = new VoucherInvoiceModel();
			$accountRunningModel = new AccountRunningModel();
			$accountRunningDelModel = new AccountRunningDelModel();
			$tourquotationsModel = new TourQuotationsModel();

		// Begin a database transaction
		// $db = \Config\Database::connect();
		// $db->transStart();

		// Update flag in voucher table
			$data = ['flag' => 1, 'Aflag' => 3];

			$voucherUpdate = $vouchermodel->set($data)->where('Vnum', $id)->update();

			$vouchers_record = $vouchermodel->where('Vnum', $id)->first();
			$voucher_num =  $vouchers_record['Vnum'];

			$record = $vouchermodel->select('ref_po, V_num, doller_amt, advances_pay')->where('Vnum', $voucher_num)->first();

			$datesArray = explode(',', $record['V_num']);
			$tour = $record['ref_po'];
			$status = $record['advances_pay'];
			if ($status == 2 || $status == 22 || $status == 222) {
				foreach ($datesArray as $date) {
					$date = date('Y-m-d', strtotime(trim($date)));

				// get the quotatiomodel record
					$tourrec = $tourquotationsModel->where('tour_date', $date)->where('quotation_id', $tour)->first();
					$pay_doller_amt = $tourrec['pay_doller_count'];
					$pay_doller = $pay_doller_amt - $record['doller_amt'];

					$data1 = [
						// 'booking_confirmed_flag' => 3,
						'hotel_payment_flag' => 3,
						'pay_doller_count' => $pay_doller
					];
					$tourquotationsModel->set($data1)->where('tour_date', $date)->where('quotation_id', $tour)->update();
				}
			}
		// Check if the updates were successful
			if ($voucherUpdate) {

				$accountRunningEntries = $accountRunningModel->where('Recpt_No', $id)->findAll();

				$invoiceNoModel = new InvoiceNumberModel();
				$acc_id = $invoiceNoModel->incrementAccId();

				$lCodes = [];
				foreach ($accountRunningEntries as $entry) {
					$lCodes[] = $entry['L_Code'];
				}

				if (count($lCodes) > 1) {

					$newLCode1 = $lCodes[1];
					$newLCode2 = $lCodes[0];

					foreach ($accountRunningEntries as $index => $entry) {
						if ($index == 0) {
							$entry['L_Code'] = $newLCode1;
							$entry['ID'] = $acc_id;
							$entry['Discription'] = 'Voucher Cancel for supplier-' . $supplier;
						} elseif ($index == 1) {
							$entry['L_Code'] = $newLCode2;
							$entry['ID'] = $acc_id;
							$entry['Discription'] = 'Voucher Cancel for supplier-' . $supplier;
						}
					// unset($entry['ID']);

						$accountRunningModel->insert($entry);
					}
				} else {
					echo "Not enough entries to swap L_Code values.";
				}

				return $this->response->setJSON(["status" => 200, "message" => "Voucher and related records processed successfully!"]);
			// }
			} else {
			// Rollback transaction if updates fail
				return $this->response->setJSON(["status" => 400, "message" => "Update Error!"]);
			}
		}
	}
