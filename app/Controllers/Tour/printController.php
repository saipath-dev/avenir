<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Controllers\Tour\ItineraryController;

use App\Models\TourQuotationsModel;
use App\Models\ConsigneeModel;
use App\Models\PassengerDetailsModel;
use App\Models\TourActivityModel;
use App\Models\TourSubLocationsModel;
use App\Models\TourItineraryModel;
use App\Models\DebtorInvoiceLogModel;
use App\Models\AccountRunningModel;
use App\Models\DriverAllocationModel;
use App\Models\SupplierModel;

class printController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/tour
    public function index()
    {
        $tour_id = $this->request->getGet('tour_id');
        $data = $this->get_print_view_data($tour_id);
        return view('pages/tour/reservation_card_print', $data);
    }

    public function print_quotation_with_amounts()
    {
        $tour_id = $this->request->getGet('tour_id');
        $data = $this->get_print_view_data($tour_id);
        return view('pages/tour/quotation/quotation_print', $data);
    }

    public function itinerary_print()
    {
        $tour_id = $this->request->getGet('tour_id');
        $data = $this->get_print_view_data($tour_id);
        return view('pages/tour/itinerary_print', $data);
    }

    public function account_reservation_card()
    {
        $tour_id = $this->request->getGet('tour_id');
        $data = $this->get_view_data($tour_id);
        return view('pages/tour/acc_reservation_card_print', $data);
    }

    public function get_print_view_data($tour_id, $is_json = 0)
    {
        $data = array();

        if ($is_json == 0) {
            // Load menu data using the custom library
            $menuLoader = new MenuLoader();
            $data = $menuLoader->loadMenuData();
        }

        $tourQuotationsModel = new TourQuotationsModel();
        $consigneeModel = new ConsigneeModel();
        $tourActivityModel = new TourActivityModel();
        $tourSubLocationsModel = new TourSubLocationsModel();
        $debtorInvioceLogModel = new DebtorInvoiceLogModel();
        $tourItineraryModel = new TourItineraryModel();

        $tour_details = $tourQuotationsModel->select('tour_quotations.*, room_type.rtype')->join('room_type', 'tour_quotations.room_type = room_type.id', 'left')->where('quotation_id', $tour_id)->orderBy('quotation_day', 'ASC')->findAll();

        // Get consignee details
        $consignee_details = $consigneeModel->where('tour_id', $tour_id)->findAll();

        // Get Invoice details
        $invoice_details = $debtorInvioceLogModel->where('tour_id', $tour_id)->first();

        // Get Sub locations details
        $sub_locations = $tourSubLocationsModel->where('quotation_id', $tour_id)->findAll();

        // Get Activity details
        $activity_details = $tourActivityModel->select('tour_activity.*, entrance_fee.name as locatonname')->join('entrance_fee', 'entrance_fee.id = tour_activity.name')->where('tour_id', $tour_id)->findAll();

        $itinerary = $tourItineraryModel->where('tour_id', $tour_id)->groupBy('tour_date')->findAll();
        // loop through the itinerary and get the details
        $data_array = array();
        foreach ($itinerary as $key => $value) {
            $data_array[$value['tour_date']]['details'] = $tourItineraryModel->where('tour_id', $tour_id)->where('tour_date', $value['tour_date'])->orderBy('order_id')->findAll();
            // log_message('critical', $tourItineraryModel->getLastQuery());
        }

        // Get driver details with supplier information (phone, language)
        $driverAllocationModel = new DriverAllocationModel();
        $driver_details = $driverAllocationModel
            ->select('driver_allocation.*, supplier.Name as driver_name, supplier.Mobile as driver_phone, supplier.language as driver_language')
            ->join('supplier', 'supplier.Sup_Acc_No = driver_allocation.driver AND supplier.sup_cat = 2', 'left')
            ->where('driver_allocation.tour_id', $tour_id)
            ->findAll();

        // log_message('critical', $consigneeModel->getLastQuery());

        // Fetch driver and guide supplier details (Mobile, language)
        $db = \Config\Database::connect();
        $driver_supplier = [];
        $guide_supplier  = [];
        if (!empty($consignee_details[0]['transport_agent'])) {
            $driver_supplier = $db->query(
                'SELECT Mobile, language FROM supplier WHERE Sup_Acc_No = ? LIMIT 1',
                [$consignee_details[0]['transport_agent']]
            )->getRowArray() ?? [];
        }
        if (!empty($consignee_details[0]['guide'])) {
            $guide_supplier = $db->query(
                'SELECT Mobile, language FROM supplier WHERE Sup_Acc_No = ? LIMIT 1',
                [$consignee_details[0]['guide']]
            )->getRowArray() ?? [];
        }

        $data['tour_details'] = $tour_details;
        $data['consignee_details'] = $consignee_details;
        $data['sub_locations'] = $sub_locations;
        $data['activity_details'] = $activity_details;
        $data['invoice_details'] = $invoice_details;
        $data['itinerary'] = $data_array;
        $data['driver_details'] = $driver_details;

        if ($is_json == 0) return $data;
        else return json_encode([
            'tour_details' => $tour_details,
            'consignee_details' => $consignee_details,
            'sub_locations' => $sub_locations,
            'activity_details' => $activity_details,
            'invoice_details' => $invoice_details,
            'itinerary' => $data_array,
            'driver_details' => $driver_details
        ]);
    }

    public function get_view_data($tour_id, $is_json = 0)
    {
        $data = array();

        if ($is_json == 0) {
            $menuLoader = new MenuLoader();
            $data = $menuLoader->loadMenuData();
        }

        $tourQuotationsModel = new TourQuotationsModel();
        $consigneeModel = new ConsigneeModel();
        $tourActivityModel = new TourActivityModel();
        $tourSubLocationsModel = new TourSubLocationsModel();
        $debtorInvioceLogModel = new DebtorInvoiceLogModel();
        $tourItineraryModel = new TourItineraryModel();
        $accountrunningModel = new AccountRunningModel();

        $db = \Config\Database::connect();
        $sql = 'SELECT
            MIN(id) as id,
            quotation_id,
            hotel_acc,
            hotel_code,
            hotel_name,
            MIN(tour_date) as start_date,
            MAX(tour_date) as end_date,
            COUNT(*) as nights,
            GROUP_CONCAT(tour_date ORDER BY tour_date) as date_range,
            from_location,
            to_location,
            SUM(sglcount) as sglcount,
            SUM(dblcount) as dblcount,
            SUM(tplcount) as tplcount,
            AVG(sgl) as sgl,
            AVG(dbl) as dbl,
            AVG(tpl) as tpl,
            meal_code,
            booking_confirmed_flag,
            hotel_payment_flag,
            rtype,
            transfer_name,
            total_hotel_doller as total_hotel_doller,
            pay_doller_count as pay_doller_count,
            quotation_day,
            hotel_dollar_rate
        FROM (
            SELECT
                tq.*,
                s.Name as hotel_names,
                rt.rtype,
                tm.transfer_code as transfer_name,
                ROW_NUMBER() OVER (PARTITION BY tq.quotation_id ORDER BY tq.tour_date) -
                ROW_NUMBER() OVER (PARTITION BY tq.quotation_id, tq.hotel_acc ORDER BY tq.tour_date) as consecutive_group
            FROM tour_quotations tq
            JOIN supplier s ON s.Sup_Acc_No = tq.hotel_acc
            LEFT JOIN room_type rt ON rt.id = tq.room_type
            LEFT JOIN transfer_milage tm ON tm.id = tq.transfer_code
            WHERE tq.quotation_id = ?
        ) grouped_data
        GROUP BY quotation_id, hotel_acc, consecutive_group
        ORDER BY MIN(tour_date)';

        $tour_details = $db->query($sql, [$tour_id])->getResultArray();

        $consignee_details = $consigneeModel->where('tour_id', $tour_id)->findAll();

        $invoice_details = $debtorInvioceLogModel->where('tour_id', $tour_id)->first();

        $sub_locations = $tourSubLocationsModel->where('quotation_id', $tour_id)->findAll();
        $activity_details = $tourActivityModel->select('tour_activity.*, entrance_fee.name as locatonname')->join('entrance_fee', 'entrance_fee.id = tour_activity.name')->where('tour_id', $tour_id)->findAll();

        $itinerary = $tourItineraryModel->where('tour_id', $tour_id)->groupBy('tour_date')->findAll();

        $data_array = array();
        foreach ($itinerary as $key => $value) {
            $data_array[$value['tour_date']]['details'] = $tourItineraryModel->where('tour_id', $tour_id)->where('tour_date', $value['tour_date'])->orderBy('order_id')->findAll();
        }

        // Get all hotel accounts from tour details
        $hotel_accounts = array_column($tour_details, 'hotel_acc');
        $hotel_accounts = array_unique($hotel_accounts);

        // Query voucher table directly to get payment details (both authenticated and unauthenticated)
        $placeholders = implode(',', array_fill(0, count($hotel_accounts), '?'));
        $account_running_details = $db->query("
            SELECT
                v.W_Date as Date,
                v.Vnum as Recpt_No,
                v.Acc_No as L_Code,
                v.Vnum as V_tkn,
                v.doller_amt as balance
            FROM voucher v
            WHERE v.C_Number = ?
                AND v.advances_pay = 2
                AND v.Acc_No IN ({$placeholders})
            GROUP BY v.Acc_No
        ", array_merge([$tour_id], $hotel_accounts))->getResultArray();

            // log_message('critical', print_r($account_running_details, true));

        // Fetch driver advance payment details (similar to hotel payments above)
        $driver_advances = [];
        $driver_settlements = [];
        // Note: $consignee_details is an array (findAll returns array), access first element
        if (!empty($consignee_details) && !empty($consignee_details[0]['transport_agent'])) {
            // Get advance payments (advances_pay = 1) - Show ALL vouchers regardless of Aflag status
            $driver_advances = $db->query("
                SELECT
                    v.W_Date as Date,
                    v.Vnum as Recpt_No,
                    v.Acc_No as L_Code,
                    v.Vnum as V_tkn,
                    v.doller_amt as amount,
                    v.Aflag
                FROM voucher v
                WHERE v.C_Number = ?
                    AND v.advances_pay = 1
                    AND v.Acc_No = ?
                ORDER BY v.W_Date
            ", [$tour_id, $consignee_details[0]['transport_agent']])->getResultArray();

            // Get settlement payments (advances_pay = 11) - Show ALL vouchers regardless of Aflag status
            $driver_settlements = $db->query("
                SELECT
                    v.W_Date as Date,
                    v.Vnum as Recpt_No,
                    v.Acc_No as L_Code,
                    v.Vnum as V_tkn,
                    v.doller_amt as amount,
                    v.Aflag
                FROM voucher v
                WHERE v.C_Number = ?
                    AND v.advances_pay = 11
                    AND v.Acc_No = ?
                ORDER BY v.W_Date
            ", [$tour_id, $consignee_details[0]['transport_agent']])->getResultArray();
        }
        $data['driver_advances'] = $driver_advances;
        $data['driver_settlements'] = $driver_settlements;

        $data['tour_details'] = $tour_details;
        $data['consignee_details'] = $consignee_details;
        $data['sub_locations'] = $sub_locations;
        $data['activity_details'] = $activity_details;
        $data['invoice_details'] = $invoice_details;
        $data['itinerary'] = $data_array;
        $data['account_running_details'] = $account_running_details;

        // log_message('critical', print_r($tour_details, true));

        return $data;
    }

    // public function itinerary_print()
    // {
    //     $data = array();

    //     // Load menu data using the custom library
    //     $menuLoader = new MenuLoader();
    //     $data = $menuLoader->loadMenuData();

    //     $tour_id = $this->request->getGet('tour_id');

    //     $tourItineraryModel = new TourItineraryModel();
    //     $consigneeModel = new ConsigneeModel();

    //     // Get consignee details
    //     $consignee_details = $consigneeModel->where('tour_id', $tour_id)->findAll();

    //     $itinerary = $tourItineraryModel->where('tour_id', $tour_id)->groupBy('tour_date')->findAll();
    //     // loop through the itinerary and get the details
    //     $data_array = array();
    //     foreach ($itinerary as $key => $value) {
    //         $data_array[$value['tour_date']]['details'] = $tourItineraryModel->where('tour_id', $tour_id)->where('tour_date', $value['tour_date'])->orderBy('order_id')->findAll();
    //         // log_message('critical', $tourItineraryModel->getLastQuery());
    //     }


    //     $data['consignee_details'] = $consignee_details;
    //     $data['itinerary'] = $data_array;

    //     return view('pages/tour/itinerary_print', $data);
    // }
}