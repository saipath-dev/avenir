<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\TourQuotationsModel;
use App\Models\PassengerDetailsModel;
use App\Models\ConsigneeModel;
use App\Models\DebtorInvoiceLogModel;
use App\Models\SupplierModel;
use App\Models\AccountPostingModel;
use App\Models\StaffMemberModel;
use App\Models\AccountRunningModel;


class TourPaymentForcastController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/tour_dashboard
    public function index()
    {

        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $user_emp_id = session()->get('emp_id');
        $staffMemberModel = new StaffMemberModel();
        $staff = $staffMemberModel->where('emp_num', $user_emp_id)->first();
        $data['user_fullname'] = $staff['fullname'];

        $supplierModel = new SupplierModel();
        $data['hotel'] = $supplierModel->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();
        $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

        $AccountPostingModel = new AccountPostingModel();
        $data['bank_acc'] = $AccountPostingModel->where('B_Code', 1)->where('T_Code', 6)->where('H_Code', 8)->findAll();

        $TourQuotationsModel = new TourQuotationsModel();
        $tourConsigneeModel = new ConsigneeModel();
        $accRunninfModel = new AccountRunningModel();

        $tour_id = $this->request->getGet('id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');
        $type = $this->request->getGet('type');

        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        $tour_condition = $tourConsigneeModel->select('transport_only')->where('tour_id', $tour_id)->findAll();
        $data['tour_condition'] = $tour_condition;

        $tour_saved = $TourQuotationsModel->select('MAX(saved) AS save_flag')->where('quotation_id', $tour_id)->groupBy('quotation_id')->findAll();
        $data['tour_saved'] = $tour_saved;


        if (!empty($start_date) && !empty($end_date)) {
            $tour_details = $TourQuotationsModel->select('(consignee.total_room_cost) as room_cost, consignee.tour_id, consignee.manual_id, consignee.consignee, consignee.start_date, consignee.end_date')
                ->where('consignee.start_date >=', $start_date)
                ->where('consignee.end_date <=', $end_date)
                ->where('consignee.is_template', 0)
                ->where('tour_quotations.finalize', 1)
                ->where('tour_quotations.confirm', 1)
                ->where('tour_quotations.saved', 1)
                ->where('consignee.verify', 1)
                ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id')
                ->groupBy('consignee.tour_id')
                ->findAll();

            $tour_ids = array();
            foreach ($tour_details as $key => $value) {
                $tour_ids[] = $value['tour_id'];
            }

            foreach ($tour_ids as $key => $value) {
                $tour_details[$key]['total_paid'] = $TourQuotationsModel->select('Max(total_hotel_doller) as total_amount, Max(pay_doller_count) as total_paid_amount, Max(extra_act_fee) as extra_act_fee')
                    ->where('tour_quotations.quotation_id', $value)
                    ->where('tour_quotations.finalize', 1)
                    ->where('tour_quotations.confirm', 1)
                    ->where('tour_quotations.saved', 1)
                    ->where('tour_quotations.verify', 1)
                    ->groupBy('hotel_acc')
                    ->findAll();

                    // log_message('critical', $TourQuotationsModel->getLastQuery());
                // get sum of total paid amount for each tour
                $total_paid_amount = 0;
                foreach ($tour_details[$key]['total_paid'] as $key1 => $value1) {
                    $total_paid_amount += $value1['total_paid_amount'];
                }
                $tour_details[$key]['total_paid_amount'] = $total_paid_amount;

                // get sum of total amount for each tour
                $total_amount = 0;
                foreach ($tour_details[$key]['total_paid'] as $key1 => $value1) {
                    $total_amount += $value1['total_amount'];
                }
                $tour_details[$key]['total_amount'] = $total_amount;

                // get sum of extra activity fee for each tour
                $extra_act_fee = 0;
                foreach ($tour_details[$key]['total_paid'] as $key1 => $value1) {
                    $extra_act_fee += $value1['extra_act_fee'];
                }
                $tour_details[$key]['extra_act_fee'] = $extra_act_fee;
            }

            $tour_transport_details = $TourQuotationsModel->select('consignee.tour_id as tid, consignee.manual_id, consignee.consignee, consignee.start_date, consignee.end_date,consignee.total_transport_cost, consignee.log_sheet_mileage, log_sheet_mile.*')
                ->where('consignee.start_date >=', $start_date)
                ->where('consignee.end_date <=', $end_date)
                ->where('consignee.is_template', 0)
                ->where('tour_quotations.finalize', 1)
                ->where('tour_quotations.confirm', 1)
                ->where('tour_quotations.saved', 1)
                ->where('consignee.verify', 1)
                ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id')
                ->join('log_sheet_mile', 'log_sheet_mile.tour_id = consignee.tour_id', 'left')
                ->groupBy('tour_quotations.quotation_id')
                ->findAll();

                $trans_tour_ids = array();
                foreach ($tour_transport_details as $key => $value) {
                    $trans_tour_ids[] = $value['tid'];
                }

                foreach ($trans_tour_ids as $key => $value) {
                    $tour_transport_details[$key]['acc_record_advance'] = $accRunninfModel->select('Dr')
                        ->where('index_no', $value)
                        ->where('advances_pay', 1)
                        ->findAll();

                    $advance = 0;
                    foreach ($tour_transport_details[$key]['acc_record_advance']as $key1 => $value1) {
                        $advance += $value1['Dr'];
                    }
                    $tour_transport_details[$key]['advance'] = $advance;
                }

                foreach ($trans_tour_ids as $key => $value) {
                    $tour_transport_details[$key]['acc_record_setted'] = $accRunninfModel->select('Dr')
                        ->where('index_no', $value)
                        ->where('advances_pay', 11)
                        ->findAll();

                    $setted = 0;
                    foreach ($tour_transport_details[$key]['acc_record_setted'] as $key1 => $value1) {
                        $setted += $value1['Dr'];
                    }
                    $tour_transport_details[$key]['setted'] = $setted;
                }

                // log_message('critical', $accRunninfModel->getLastQuery());
        } else {
            // empty $tour_details
            $tour_details = array();
            $tour_transport_details = array();
        }
        // log_message('critical', $TourQuotationsModel->getLastQuery());

        $data['tour_details'] =  $tour_details;

        // log_message('critical', print_r($data['tour_details'], true));
        
        $data['transport_details'] = $tour_transport_details;
        // log_message('critical', print_r($data['transport_details'], true));

        return view('pages/tour/tour_pay_forcast_dashboard', $data);
    }
}
