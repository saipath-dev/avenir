<?php

namespace App\Controllers;

use App\Controllers\Tour\printController;
use App\Libraries\MenuLoader;
use App\Libraries\ItineraryFormatter;
use App\Models\ConsigneeModel;
use App\Models\CountryCodeModel;
use App\Models\CustomerModel;
use App\Models\DriverAllocationAuditLogModel;
use App\Models\HotelRateModel;
use App\Models\InquiryModel;
use App\Models\RoomTypeModel;
use App\Models\SupplierModel;
use App\Models\TourQuotationsModel;
use App\Models\TransferMilageModel;
use App\Models\DriverAllocationModel;
use App\Models\LogSheetMileModel;
use App\Models\VoucherPaymentModel;
use App\Models\UserPrevQModel;
use App\Models\InquiryConsigneeModel;
use App\Models\EntranceFeeModel;

class NewDashboardController extends BaseController
{

    public function index()
    {
        $data = [];

        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $data['tile_dashboard'] = true;

        // Load user privileges
        $emp_id = session()->get('emp_id');
        $user_id = session()->get('user_id');

        $userPrevQModel = new UserPrevQModel();
        $userPrivileges = $userPrevQModel
            ->select('url')
            ->where('emp_id', $emp_id)
            ->where('user_id', $user_id)
            ->findAll();

        // Extract URLs into a simple array for JavaScript
        $data['user_privilege_urls'] = array_column($userPrivileges, 'url');

        $consigneeModel = new ConsigneeModel();
        $db = \Config\Database::connect();

        // Fetch initial tours with payment status
        $builder = $db->table('consignee');
        $builder->select([
            'consignee.*',
            'MAX(tour_quotations.confirm) AS confirm',
            'MAX(tour_quotations.finalize) AS finalize',
            'MAX(tour_quotations.quotation_day) AS quotation_days',
            'SUM(CASE WHEN tour_quotations.hotel_booking_flag = 1 THEN 1 ELSE 0 END) AS hotel_booking_cnt',
            'SUM(CASE WHEN tour_quotations.booking_confirmed_flag = 1 THEN 1 ELSE 0 END) AS booking_confirmed_cnt',
            'SUM(CASE WHEN tour_quotations.hotel_payment_flag = 1 THEN 1 ELSE 0 END) AS hotel_payment_cnt',
            'COUNT(CASE WHEN tour_quotations.hotel_acc IS NOT NULL AND tour_quotations.hotel_acc <> "" THEN 1 ELSE NULL END) AS hotel_count_new',
            'MAX(consignee.transport_advance) AS transport_advance',
            'MAX(consignee.transport_settled) AS driver_settled',
            'MAX(debtor_invoice_log.invoice_no) AS agent_invoice_no',
            'MAX(consignee.create_user) AS create_user',
            'GROUP_CONCAT(DISTINCT driver_allocation.driver_name ORDER BY driver_allocation.id SEPARATOR ", ") AS allocated_driver_names',
        ])
        ->join('tour_quotations', 'tour_quotations.quotation_id = consignee.tour_id', 'LEFT')
        ->join('(SELECT tour_id, invoice_no FROM debtor_invoice_log WHERE id IN (SELECT MAX(id) FROM debtor_invoice_log GROUP BY tour_id)) AS debtor_invoice_log', 'debtor_invoice_log.tour_id = consignee.tour_id', 'LEFT')
        ->join('driver_allocation', 'driver_allocation.tour_id = consignee.tour_id', 'LEFT')

        ->where('consignee.start_date <=', date('Y-m-d'))
        ->groupBy('consignee.tour_id')
        ->orderBy('consignee.start_date', 'DESC')
        ->limit(10);

        $data['recent_tours'] = $builder->get()->getResultArray();

        // log_message('critical', 'Initial Tours Query: ' . $db->getLastQuery());

        // Add quotation data for backward compatibility
        if (!empty($data['recent_tours'])) {
            foreach ($data['recent_tours'] as &$tour) {
                $tour['quotation'] = [[
                    'confirm' => $tour['confirm'] ?? 0,
                    'finalize' => $tour['finalize'] ?? 0,
                    'agent_invoice_no' => $tour['agent_invoice_no'] ?? null
                ]];
            }
        }

        // Get total count for pagination (only today onwards)
        $data['total_tours'] = $consigneeModel->where('start_date <=', date('Y-m-d'))->countAllResults(false);

        // Pilot Report sub-cards (menu_category id = 27), admin only
        $data['pilot_report_items'] = [];
        if (session()->get('group_id') == 1) {
            $data['pilot_report_items'] = $db->table('menu_list')
                ->select('id, name, url')
                ->where('cat_id', 27)
                ->where('del', 0)
                ->orderBy('order_id', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();
        }

        // tf-cockpit-r1: today's USD rate for the Super Admin's cockpit strip
        if (session()->get('group_id') == 1) {
            try {
                $data['usd'] = \App\Libraries\TourFlow\UsdRate::status($db);
            } catch (\Throwable $e) {
                $data['usd'] = null;
            }
        }

        // tf-pilot-r1: the department panel (tasks waiting, shortcuts, reports) — derived from the user's own privileges
        try {
            $data['pilot'] = \App\Libraries\Pilot::forUser($db, $data);
        } catch (\Throwable $e) {
            $data['pilot'] = null;
            log_message('error', 'Pilot panel: ' . $e->getMessage());
        }
        return view('pages/dashboard2/tile_dashboard', $data);
    }

    public function getRecentTours()
    {
        $page = $this->request->getVar('page') ?? 1;
        $search = $this->request->getVar('search') ?? '';
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $consigneeModel = new ConsigneeModel();
        $db = \Config\Database::connect();

        // Fetch tours with payment status using JOIN
        $builder = $db->table('consignee');
        $builder->select([
            'consignee.*',
            'MAX(tour_quotations.confirm) AS confirm',
            'MAX(tour_quotations.finalize) AS finalize',
            'MAX(tour_quotations.quotation_day) AS quotation_days',
            'SUM(CASE WHEN tour_quotations.hotel_booking_flag = 1 THEN 1 ELSE 0 END) AS hotel_booking_cnt',
            'SUM(CASE WHEN tour_quotations.booking_confirmed_flag = 1 THEN 1 ELSE 0 END) AS booking_confirmed_cnt',
            'SUM(CASE WHEN tour_quotations.hotel_payment_flag = 1 THEN 1 ELSE 0 END) AS hotel_payment_cnt',
            'COUNT(CASE WHEN tour_quotations.hotel_acc IS NOT NULL AND tour_quotations.hotel_acc <> "" THEN 1 ELSE NULL END) AS hotel_count_new',
            'MAX(consignee.transport_advance) AS transport_advance',
            'MAX(consignee.transport_settled) AS driver_settled',
            'MAX(debtor_invoice_log.invoice_no) AS agent_invoice_no',
            'GROUP_CONCAT(DISTINCT driver_allocation.driver_name ORDER BY driver_allocation.id SEPARATOR ", ") AS allocated_driver_names',
        ])
        ->join('tour_quotations', 'tour_quotations.quotation_id = consignee.tour_id', 'LEFT')
        ->join('(SELECT tour_id, invoice_no FROM debtor_invoice_log WHERE id IN (SELECT MAX(id) FROM debtor_invoice_log GROUP BY tour_id)) AS debtor_invoice_log', 'debtor_invoice_log.tour_id = consignee.tour_id', 'LEFT')
        ->join('driver_allocation', 'driver_allocation.tour_id = consignee.tour_id', 'LEFT');

        // Apply search filter if provided
        if (!empty($search)) {
            $builder->groupStart()
                ->like('consignee.manual_id', $search)
                ->orLike('consignee.agent_name', $search)
                ->orLike('consignee.consignee', $search)
                ->groupEnd();
        }

        // Apply date filter only when not searching
        if (empty($search)) {
            // $builder->where('consignee.start_date <=', date('Y-m-d'));
            $builder->where('consignee.start_date <=', date('Y-m-d', strtotime('+2 weeks')));
        }

        $builder->where('consignee.marked_complete', 0)
        ->groupBy('consignee.tour_id')
        ->orderBy('consignee.start_date', 'DESC')
        ->limit($perPage, $offset);

        $tours = $builder->get()->getResultArray();

        // Count total records
        $countBuilder = $db->table('consignee');
        if (empty($search)) {
            // $countBuilder->where('consignee.start_date <=', date('Y-m-d'));
            $countBuilder->where('consignee.start_date <=', date('Y-m-d', strtotime('+2 weeks')));
        }
        $countBuilder->where('consignee.marked_complete', 0);
        if (!empty($search)) {
            $countBuilder->groupStart()
                ->like('consignee.manual_id', $search)
                ->orLike('consignee.agent_name', $search)
                ->orLike('consignee.consignee', $search)
                ->groupEnd();
        }
        $total = $countBuilder->countAllResults(false);
        $totalPages = ceil($total / $perPage);

        return $this->response->setJSON([
            'status' => 200,
            'data' => $tours,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => $totalPages,
                'per_page' => $perPage,
                'total' => $total
            ]
        ]);
    }

    public function searchTours()
    {
        $keyword = $this->request->getVar('keyword');
        $startDate = $this->request->getVar('start_date');
        $endDate = $this->request->getVar('end_date');

        if (empty($keyword) && empty($startDate) && empty($endDate)) {
            return json_encode(['status' => 400, 'message' => 'Keyword or date range is required']);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('consignee');

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('manual_id', $keyword)
                ->orLike('consignee', $keyword)
                ->groupEnd();
        }

        if (!empty($startDate)) {
            $builder->where('start_date >=', $startDate);
        }

        if (!empty($endDate)) {
            $builder->where('start_date <=', $endDate);
        }

        $builder = $builder->get();

            // log_message('critical', 'Search Query: ' . $db->getLastQuery());

        $results = $builder->getResultArray();

        if (empty($results)) {
            return json_encode(['status' => 404, 'message' => 'No tours found']);
        }

        foreach ($results as &$result) {
            $id = $result['tour_id'];
            $builder = $db->table('tour_quotations')->where('quotation_id', $id)->get();
            $result['quotation'] = $builder->getResultArray();

            // Check if this tour has a driver allocated
            $driverRow = $db->table('driver_allocation')->where('tour_id', $id)->get()->getRowArray();
            $result['has_driver_allocation'] = !empty($driverRow);
            $result['allocated_driver'] = $driverRow['driver'] ?? null;
        }

        return json_encode(['status' => 200, 'data' => $results]);
    }

    public function printQuotation()
    {
        $tour_id = $this->request->getGet('tour_id');
        $printController = new printController();
        $data = $printController->get_print_view_data($tour_id);
        return view('pages/dashboard2/tour_search/quotation_print', $data);
    }

    public function itineraryPrint()
    {
        $tour_id = $this->request->getGet('tour_id');
        $printController = new printController();
        $data = $printController->get_print_view_data($tour_id);

        try {
            $formatter = new ItineraryFormatter();
            $data['ai_itinerary'] = $formatter->format($tour_id, $data) ?? [];
        } catch (\Exception $e) {
            log_message('error', 'itineraryPrint: ItineraryFormatter unavailable - ' . $e->getMessage());
            $data['ai_itinerary'] = [];
        }

        return view('pages/dashboard2/tour_search/itinerary_print', $data);
    }

    public function reservationCard()
    {
        $tour_id = $this->request->getGet('tour_id');
        $printController = new printController();
        $data = $printController->get_print_view_data($tour_id);
        return view('pages/dashboard2/tour_search/reservation_card_print', $data);
    }

    public function newQuotation()
    {
        $consigneeModel = new ConsigneeModel();
        $consigneeModel->where('finalize', 0)->where('start_date !=', null)->where('end_date !=', null);
        $data['rowCount'] = $consigneeModel->countAllResults();

        $data['pending_quotations'] = $consigneeModel
            ->where('finalize', 0)
            ->where('start_date !=', null)
            ->where('end_date !=', null)
            ->orderBy('tour_id', 'ASC')->findAll();

        $data['templates'] = $consigneeModel
            ->where('is_template', 1)
            ->where('start_date !=', null)
            ->where('end_date !=', null)
            ->orderBy('tour_id', 'DESC')->findAll();

        $supplierModel = new SupplierModel();
        $data['hotel'] = $supplierModel->where('sup_cat', 1)->orderBy('Name', 'ASC')->findAll();

        $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

        $hotelRateModel = new HotelRateModel();
        $data['hotel_code'] = $hotelRateModel->select('hotel_code')->orderBy('hotel_code', 'ASC')->findAll();

        $roomTypeModel = new RoomTypeModel();
        $data['roomtype'] = $roomTypeModel->findAll();

        $transferMilageModel = new TransferMilageModel();
        $data['transfer_code'] = $transferMilageModel->findAll();

        $countryCodeModel = new CountryCodeModel();
        $data['countries'] = $countryCodeModel->findAll();

        $customerModel = new CustomerModel();
        $data['agents'] = $customerModel->findAll();

        $SupplierModel = new SupplierModel();
        $data['transport_agents'] = $SupplierModel->where('sup_cat', 2)->findAll();

        $model = new InquiryModel();
        $data['inquiries'] = $model->where('is_accepted', 1)->where('is_created', 0)->findAll();
        $data['rowCountitinerary'] = $model->countAllResults();

        // Load pending inquiries from inquiry_consignee table
        $inquiryConsigneeModel = new InquiryConsigneeModel();
        $data['pending_inquiries'] = $inquiryConsigneeModel
            ->where('finalize', 0)
            ->where('start_date !=', null)
            ->where('end_date !=', null)
            ->orderBy('inquiry_id', 'ASC')
            ->findAll();
        $data['pendingInquiryCount'] = count($data['pending_inquiries']);

        $id = $this->request->getGet('tour_id');
        $start_date = $this->request->getGet('start');
        $end_date = $this->request->getGet('end');

        $data['quotation_id'] = $id;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;

        if (!empty($id)) {
            $TourQuotationsModel = new TourQuotationsModel();
            $data['quotation_details'] = $TourQuotationsModel->select('tour_quotations.*, transfer_milage.transfer_code as code')->join('transfer_milage', 'transfer_milage.id = tour_quotations.transfer_code', 'left')->where('tour_quotations.quotation_id', $id)->findAll();
        }

        return view('pages/dashboard2/new_quotation', $data);
    }

    public function timeline()
    {
        $tour_id = $this->request->getGet('tour_id');
        $data = array();

        $data['tour_id'] = $tour_id;
        $data['valid_tour'] = false;

        // get url last segment
        $last_segment = $this->request->getVar('edit');
        // $last_segment = end($url_segments);

        if (!empty($tour_id) && $tour_id != 0) {

            $data['valid_tour'] = true;

            $consigneeModel = new ConsigneeModel();
            $data['consignee'] = $consigneeModel->where('tour_id', $tour_id)->first();
            // log_message('critical', 'consignee: ' . json_encode($data['consignee']));

            if (!empty($data['consignee'])) {
                $start_date = $data['consignee']['start_date'];
                $end_date = $data['consignee']['end_date'];

                // log_message('critical', 'start date: ' . $start_date . ' end date: ' . $end_date);
                // if start date > end date
                if ($start_date > $end_date) {
                    $data['valid_tour'] = false;
                }
            } else {
                $data['valid_tour'] = false;
            }

            $entranceFeeModel = new EntranceFeeModel();
            $data['places'] = $entranceFeeModel->findAll();

            $supplierModel = new SupplierModel();
            $data['hotel_location'] = $supplierModel->select('Address')->where('sup_cat', 1)->groupBy('Address')->orderBy('Address', 'ASC')->findAll();

            $countryCodeModel = new CountryCodeModel();
            $data['countries'] = $countryCodeModel->findAll();

            $customerModel = new CustomerModel();
            $data['agents'] = $customerModel->findAll();

            $SupplierModel = new SupplierModel();
            $data['transport_agents'] = $SupplierModel->where('sup_cat', 2)->findAll();

            $data['last_segment'] = $last_segment;



            // log_message('critical', $debtorInvoiceLogModel->getLastQuery());
        }

        return view('pages/dashboard2/tour_search/timeline', $data);

    }

    public function deleteTour()
    {
        $tour_id = $this->request->getPost('tour_id');

        if (empty($tour_id)) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Tour ID is required'
            ]);
        }

        $db = \Config\Database::connect();

        // Check if tour has an invoice
        $invoice = $db->table('debtor_invoice_log')
            ->where('tour_id', $tour_id)
            ->where('cancel', 0)
            ->get()
            ->getRow();

        if ($invoice) {
            return $this->response->setJSON([
                'status' => 400,
                'message' => 'Cannot delete tour with confirmed invoice'
            ]);
        }

        // Start transaction
        $db->transStart();

        try {
            // Delete from tour_quotations
            $db->table('tour_quotations')->where('quotation_id', $tour_id)->delete();

            // Delete from consignee
            $db->table('consignee')->where('tour_id', $tour_id)->delete();

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'status' => 500,
                    'message' => 'Failed to delete tour'
                ]);
            }

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Tour deleted successfully'
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON([
                'status' => 500,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
