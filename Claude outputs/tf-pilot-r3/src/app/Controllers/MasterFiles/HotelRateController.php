<?php

namespace App\Controllers\MasterFiles;

use App\Controllers\BaseController;

use App\Libraries\HotelRateExtractor;
use App\Libraries\MenuLoader;
use App\Models\AccountNumbersModel;
use App\Models\AccountPostingModel;
use App\Models\AccountRunningModel;
use App\Models\CountryModel;
use App\Models\CustomerModel;
use App\Models\EntranceFeeModel;
use App\Models\RoomTypeModel;
use App\Models\SupplierModel;
use App\Models\HotelRateModel;
use App\Models\TourQuotationsModel;
use App\Libraries\HotelRates\LegacyLock;

class HotelRateController extends BaseController
{
    private ?LegacyLock $legacyLock = null;

    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    /**
     * Hotel Rates (new) lock: once a hotel's rates are managed in the new panel
     * (master_files/hotel_rate_v2) its hotel_rate rows are rebuilt from the
     * approved plans, so this screen must not change them. See
     * App\Libraries\HotelRates\LegacyLock and Settings (legacy_screen).
     */
    private function lock(): LegacyLock
    {
        return $this->legacyLock ??= new LegacyLock(\Config\Database::connect());
    }

    private function lockedResponse(?string $hotel, ?string $name = null)
    {
        return $this->response->setJSON([
            'status' => 400,
            'locked' => true,
            'message' => $this->lock()->message($hotel, $name),
            'panel_url' => base_url('master_files/hotel_rate_v2') . ($hotel ? '?hotel=' . rawurlencode((string) $hotel) : ''),
        ]);
    }

    // route : base_url/master_files/customer
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Load the supplier data
        $supplierModel = new SupplierModel();
        $data['load_data'] = $supplierModel->where('sup_cat', 1)->findAll();

        $roomTypeModel = new RoomTypeModel();
        $data['roomtype'] = $roomTypeModel->findAll();

        $hotelRateModel = new HotelRateModel();
        $id = $this->request->getGet('id');
        if (!empty($id)) {
            $data['hotelrate'] = $hotelRateModel->select('hotel_rate.*, supplier.Name, room_type.rtype')->join('supplier', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->join('room_type', 'hotel_rate.room_type = room_type.id')->where('hotel_rate.id', $id)->first();
        }



        $data['id'] = $this->request->getGet('id');
        $data['prefill_hotel'] = esc($this->request->getGet('hotel') ?? '', 'attr');
        $data['hr2_lock'] = $this->lock()->forView(base_url('master_files/hotel_rate_v2'));

        return view('pages/master_files/hotel_rate', $data);
    }

    // create new customer - route : base_url/master_files/room_rate/create
    public function create()
    {
        try {
            // validation
            $validation = \Config\Services::validation();
            $validation->setRules([
                'hotel' => 'required|strip_tags',
                'location' => 'required|strip_tags',
                'roomtype' => 'required|strip_tags'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
            }

            if ($this->lock()->isLocked($this->request->getVar('hotel'))) {
                return $this->lockedResponse($this->request->getVar('hotel'));
            }

            $data = array(
                'hotel' => $this->request->getVar('hotel'),
                'location' => $this->request->getVar('location'),
                'hotel_code' => $this->request->getVar('code'),
                'room_type' => $this->request->getVar('roomtype'),
                'sro' => $this->request->getVar('sro'),
                'sbb' => $this->request->getVar('sbb'),
                'shb' => $this->request->getVar('shb'),
                'sfb' => $this->request->getVar('sfb'),
                'sdate' => $this->request->getVar('sdate'),
                'edate' => $this->request->getVar('edate'),
                'dro' => $this->request->getVar('dro'),
                'dbb' => $this->request->getVar('dbb'),
                'dhb' => $this->request->getVar('dhb'),
                'dfb' => $this->request->getVar('dfb'),
                'dsdate' => $this->request->getVar('sdate'),
                'dedate' => $this->request->getVar('edate'),
                'tro' => $this->request->getVar('tro'),
                'tbb' => $this->request->getVar('tbb'),
                'thb' => $this->request->getVar('thb'),
                'tfb' => $this->request->getVar('tfb'),
                'tsdate' => $this->request->getVar('sdate'),
                'tedate' => $this->request->getVar('edate')
            );

            $hotelrateMOdel = new HotelRateModel();
            // Check if the Entrance fee already exists
            if ($hotelrateMOdel->where(['hotel' => $this->request->getVar('hotel'), 'location' => $this->request->getVar('location'), 'room_type' => $this->request->getVar('roomtype'), 'sdate' => $this->request->getVar('sdate'), 'edate' => $this->request->getVar('edate')])->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Hotel Rate already exists']);
            }

            $hotelrate = $hotelrateMOdel->insert($data);
            if ($hotelrate) {
                return $this->response->setJSON(["status" => 201, "message" => "Hotel Rate Added successfully!"]);
            }
            // throw new \Exception("Customer creation failed!");
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // bulk add - route : base_url/master_files/room_rate/bulk_add
    public function bulk_add()
    {

        //return "Hii";

        try {
            $data = $this->request->getJSON(true); // Get the JSON payload as an associative array

            if (!is_array($data)) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 400, 'message' => 'Invalid input data format']);
            }

            $supplierModel = new SupplierModel();
            $accountNumbersModel = new AccountNumbersModel();
            $accountPostingModel = new AccountPostingModel();
            $roomTypeModel = new RoomTypeModel();
            $hotelRateModel = new HotelRateModel();
            $lockedSkipped = [];   // hotels managed in Hotel Rates (new): rows are not written here

            if ($this->lock()->mode() === 'all' && $this->lock()->active()) {
                return $this->response->setJSON(['status' => 400, 'locked' => true, 'message' => 'Excel uploads to this screen are switched off: rates are managed in Master Files → Hotel Rates (new), which reads the same Excel layout (Import Excel).', 'panel_url' => base_url('master_files/hotel_rate_v2')]);
            }

            foreach ($data as $item) {

                $item['Room Type'] = trim($item['Room Type'] ?? '');

                if (empty($item['Room Type']) || empty($item['Hotel']) || empty($item['location']) || empty($item['Start Date']) || empty($item['End Date'])) {
                    continue;
                }

                // Get hotel code from the hotel name and location
                $supplier = $supplierModel->where('Name', $item['Hotel'])->where('Address', $item['location'])->first();

                if (!$supplier) {
                    $nextPRCode = $accountNumbersModel->incrementPRCode();

                    // Create new supplier and get the supplier code
                    $supplierModel->insert([
                        'Name' => $item['Hotel'],
                        'Address' => $item['location'],
                        'Sup_Acc_No' => '6-9-11-' . $nextPRCode,
                        'sup_cat' => 1
                    ]);

                    // Add to account posting
                    $accountPostingModel->insert([
                        'P_Code' => $nextPRCode,
                        'PName' => $item['Hotel'],
                        'B_Code' => 6,
                        'T_Code' => 9,
                        'H_Code' => 11,
                        'L_Code' => '6-9-11-' . $nextPRCode
                    ]);

                    $supplier = $supplierModel->where('Name', $item['Hotel'])->where('Address', $item['location'])->first();
                }

                $item['Hotel'] = $supplier['Sup_Acc_No'];

                if ($this->lock()->isLocked($item['Hotel'])) {
                    $lockedSkipped[$supplier['Name']] = true;
                    continue;
                }

                // Get room type id from the room type name
                $roomType = $roomTypeModel->where('rtype', $item['Room Type'])->first();

                if (!$roomType) {
                    $roomTypeModel->insert([
                        'rtype' => $item['Room Type']
                    ]);

                    $roomType = $roomTypeModel->where('rtype', $item['Room Type'])->first();
                }

                $item['Room Type'] = $roomType['id'];

                // Check if the entrance fee already exists
                if ($hotelRateModel->where([
                    'hotel' => $item['Hotel'],
                    'location' => $item['location'],
                    'hotel_code' => '',
                    'room_type' => $item['Room Type']
                ])->first()) {
                    continue;
                }

                $start_date = $item['Start Date'] ?? 0;
                if ($start_date != 0 && is_numeric($start_date)) {
                    $item['Start Date'] = date('Y-m-d', strtotime('1899-12-30 +' . ($start_date) . ' days'));
                }
                $end_date = $item['End Date'] ?? 0;
                if ($end_date != 0 && is_numeric($end_date)) {
                    $item['End Date'] = date('Y-m-d', strtotime('1899-12-30 +' . ($end_date) . ' days'));
                }

                $n_item = [
                    'hotel' => $item['Hotel'] ?? '',
                    'location' => $item['location'] ?? '',
                    'hotel_code' => '',
                    'room_type' => $item['Room Type'] ?? '',
                    'sro' => $item['SRO'] ?? '',
                    'sbb' => $item['SBB'] ?? '',
                    'shb' =>  $item['SHB'] ?? '',
                    'sfb' => $item['SFB'] ?? '',
                    'sdate' => $item['Start Date'] ?? '',
                    'edate' => $item['End Date'] ?? '',
                    'dro' => $item['DRO'] ?? '',
                    'dbb' => $item['DBB'] ?? '',
                    'dhb' => $item['DHB'] ?? '',
                    'dfb' => $item['DFB'] ?? '',
                    'dsdate' => $item['Start Date'] ?? '',
                    'dedate' => $item['End Date'] ?? '',
                    'tro' => $item['TRO'] ?? '',
                    'tbb' => $item['TBB'] ?? '',
                    'thb' => $item['THB'] ?? '',
                    'tfb' => $item['TFB'] ?? '',
                    'tsdate' => $item['Start Date'] ?? '',
                    'tedate' => $item['End Date'] ?? ''
                ];

                $hotelRateModel->insert($n_item);
            }

            // If all data inserted successfully
            $msg = "Room Rates added successfully!";
            if ($lockedSkipped) {
                $msg .= ' Skipped (managed in Hotel Rates (new) — import them there): ' . implode(', ', array_keys($lockedSkipped)) . '.';
            }
            return $this->response->setJSON(["status" => 201, "message" => $msg, "skipped_locked" => array_keys($lockedSkipped)]);
        } catch (\Exception $e) {
            // Log the exception
            log_message('error', 'Exception: ' . $e);

            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

        // all room rates - route : base_url/master_files/hotel_rate/all
public function all()
{
    $data = array();

    // Load menu data using the custom library
    $menuLoader = new MenuLoader();
    $data = $menuLoader->loadMenuData();

    $hotel = $this->request->getGet('hotel');
    $location = $this->request->getGet('location');
    $start_date = $this->request->getGet('start_date');
    $end_date = $this->request->getGet('end_date');
    $mode = $this->request->getGet('mode');


    $data['search_hotel'] = $hotel ?? '';
    $data['search_location'] = $location ?? '';
    $data['search_start_date'] = $start_date ?? '';
    $data['search_end_date'] = $end_date ?? '';
    $data['mode'] = $mode ?? '';



    /*
    |--------------------------------------------------------------------------
    | Missing Rates Mode
    |--------------------------------------------------------------------------
    */
    if ($mode == 'missing_rates') {


        $supplierModel = new SupplierModel();

        $builder = $supplierModel
            ->select('
                supplier.Name,
                supplier.Address as location,
                supplier.Sup_Acc_No,
                supplier.Sup_Acc_No as hotel
            ')
            ->where('supplier.sup_cat', 1);



        // Hotel filter
        if (!empty($hotel)) {
            $builder->where(
                'supplier.Sup_Acc_No',
                $hotel
            );
        }



        // Location filter
        if (!empty($location)) {
            $builder->like(
                'supplier.Address',
                $location
            );
        }



        // Exclude hotels having rates (a row only counts if it has at least one non-zero rate)
        $builder->whereNotIn(
            'supplier.Sup_Acc_No',
            function ($subQuery) use ($start_date, $end_date) {

                $subQuery
                    ->select('hotel_rate.hotel')
                    ->from('hotel_rate')
                    ->groupStart()
                        ->where('hotel_rate.sro >', 0)
                        ->orWhere('hotel_rate.sbb >', 0)
                        ->orWhere('hotel_rate.shb >', 0)
                        ->orWhere('hotel_rate.sfb >', 0)
                        ->orWhere('hotel_rate.dro >', 0)
                        ->orWhere('hotel_rate.dbb >', 0)
                        ->orWhere('hotel_rate.dhb >', 0)
                        ->orWhere('hotel_rate.dfb >', 0)
                        ->orWhere('hotel_rate.tro >', 0)
                        ->orWhere('hotel_rate.tbb >', 0)
                        ->orWhere('hotel_rate.thb >', 0)
                        ->orWhere('hotel_rate.tfb >', 0)
                    ->groupEnd();


                if (!empty($start_date) && !empty($end_date)) {

                    $subQuery
                        ->where('hotel_rate.sdate <=', $end_date)
                        ->where('hotel_rate.edate >=', $start_date);

                }

            }
        );


        $builder->orderBy(
            'supplier.Name',
            'ASC'
        );


        $data['roomdata'] = $builder->findAll();



    } else {


        /*
        |--------------------------------------------------------------------------
        | Normal Hotel Rate Search
        |--------------------------------------------------------------------------
        | Driven from supplier (LEFT JOIN hotel_rate) so every hotel appears even
        | when it has no qualifying rate — a hotel with zero matches still shows
        | one row with null rate columns, handled by the view as "missing rate".
        */


        $supplierModel = new SupplierModel();


        // Build the hotel_rate join condition as a single raw string: only
        // non-zero, real rate rows attach. The 2026 cutoff only applies to the
        // default "browse everything" case (no hotel picked AND no explicit
        // date range) — it declutters stale rates there, but must never
        // override an explicit historical date search like 2025-01-01..2025-12-31.
        $rateJoinConditions = [
            'hotel_rate.hotel = supplier.Sup_Acc_No',
            '(hotel_rate.sro > 0 OR hotel_rate.sbb > 0 OR hotel_rate.shb > 0 OR hotel_rate.sfb > 0'
                . ' OR hotel_rate.dro > 0 OR hotel_rate.dbb > 0 OR hotel_rate.dhb > 0 OR hotel_rate.dfb > 0'
                . ' OR hotel_rate.tro > 0 OR hotel_rate.tbb > 0 OR hotel_rate.thb > 0 OR hotel_rate.tfb > 0)',
        ];

        $hasDateRange = !empty($start_date) && !empty($end_date);

        if ($hasDateRange) {
            $rateJoinConditions[] = "hotel_rate.sdate <= '{$end_date}'";
            $rateJoinConditions[] = "hotel_rate.edate >= '{$start_date}'";
        }

        if (empty($hotel) && !$hasDateRange) {
            $rateJoinConditions[] = "hotel_rate.edate >= '2026-01-01'";
        }

        // When the attached rate row is all-zero (no real price), fall back to
        // any hotel_rate row's own dates for this hotel within the same search
        // window — so Start/End Date still shows something instead of blank.
        $fallbackDateConditions = ['fallback_hr.hotel = supplier.Sup_Acc_No'];
        if ($hasDateRange) {
            $fallbackDateConditions[] = "fallback_hr.sdate <= '{$end_date}'";
            $fallbackDateConditions[] = "fallback_hr.edate >= '{$start_date}'";
        }

        $hotelRates = $supplierModel
            ->select('
                hotel_rate.*,
                supplier.Name,
                supplier.Sup_Acc_No,
                room_type.rtype,
                COALESCE(hotel_rate.location, supplier.Address) as location,
                COALESCE(hotel_rate.hotel, supplier.Sup_Acc_No) as hotel,
                COALESCE(hotel_rate.sdate, (
                    SELECT fallback_hr.sdate FROM hotel_rate fallback_hr
                    WHERE ' . implode(' AND ', $fallbackDateConditions) . '
                    ORDER BY fallback_hr.sdate DESC LIMIT 1
                )) as available_sdate,
                COALESCE(hotel_rate.edate, (
                    SELECT fallback_hr.edate FROM hotel_rate fallback_hr
                    WHERE ' . implode(' AND ', $fallbackDateConditions) . '
                    ORDER BY fallback_hr.edate DESC LIMIT 1
                )) as available_edate
            ')
            ->where('supplier.sup_cat', 1)
            ->join(
                'hotel_rate',
                implode(' AND ', $rateJoinConditions),
                'left',
                false
            )
            ->join(
                'room_type',
                'hotel_rate.room_type = room_type.id',
                'left'
            );



        // Hotel filter
        if (!empty($hotel)) {

            $hotelRates->where(
                'supplier.Sup_Acc_No',
                $hotel
            );

        }



        // Location filter
        if (!empty($location)) {

            $hotelRates->like(
                'supplier.Address',
                $location
            );

        }



        $today = date('Y-m-d');

        $hotelRates
            // keep every rate for the same hotel adjacent
            ->orderBy('supplier.Sup_Acc_No', 'ASC')
            // within a hotel, the rate covering today comes first...
            ->orderBy("(hotel_rate.sdate <= '{$today}' AND hotel_rate.edate >= '{$today}')", 'DESC', false)
            // ...otherwise the most recent rate first
            ->orderBy('hotel_rate.sdate', 'DESC')
            ->orderBy('hotel_rate.edate', 'DESC');


        $data['roomdata'] = $hotelRates->findAll();

    }



    /*
    |--------------------------------------------------------------------------
    | "Up to date today" lookup — for the Location accordion's
    | "to be updated" count. A hotel counts as up to date only if it has a
    | hotel_rate row covering TODAY where ALL 9 of RO/HB/FB across
    | Single/Double/Triple are non-zero. BB fields are intentionally excluded.
    | If even one of those 9 fields is zero/blank, the hotel needs updating.
    |--------------------------------------------------------------------------
    */

    $hotelCodesInResults = array_unique(array_filter(array_column($data['roomdata'], 'hotel')));

    $data['hotelsUpToDate'] = [];

    if (!empty($hotelCodesInResults)) {
        $today = date('Y-m-d');

        $upToDateRows = (new HotelRateModel())
            ->select('hotel')
            ->whereIn('hotel', $hotelCodesInResults)
            ->where('sdate <=', $today)
            ->where('edate >=', $today)
            ->where('sro >', 0)
            ->where('shb >', 0)
            ->where('sfb >', 0)
            ->where('dro >', 0)
            ->where('dhb >', 0)
            ->where('dfb >', 0)
            ->where('tro >', 0)
            ->where('thb >', 0)
            ->where('tfb >', 0)
            ->findAll();

        foreach ($upToDateRows as $row) {
            $data['hotelsUpToDate'][$row['hotel']] = true;
        }
    }



    /*
    |--------------------------------------------------------------------------
    | Dropdown Data
    |--------------------------------------------------------------------------
    */

    $supplierModel = new SupplierModel();


    // Locations dropdown
    $data['hotel_location'] = $supplierModel
        ->select('Address')
        ->where('sup_cat', 1)
        ->groupBy('Address')
        ->orderBy('Address', 'ASC')
        ->findAll();



    // Hotels dropdown
    $data['hotels'] = $supplierModel
        ->where('supplier.sup_cat', 1)
        ->orderBy('supplier.Name', 'ASC')
        ->findAll();



    // Room types dropdown (for the Add Rate modal)
    $roomTypeModel = new RoomTypeModel();
    $data['roomtype'] = $roomTypeModel->findAll();



    return view(
        'pages/master_files/all_room_rate',
        $data
    );
}


    // update - route : base_url/master_files/room_rate/update/{id}
    public function update($id = null)
    {
        try {
            // validation
            $validation = \Config\Services::validation();
            $validation->setRules([
                'hotel' => 'required|strip_tags',
                'location' => 'required|strip_tags',
                // 'code' => 'required|strip_tags',
                'roomtype' => 'required|strip_tags'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
            }

            if ($this->lock()->isLocked($this->request->getVar('hotel'))) {
                return $this->lockedResponse($this->request->getVar('hotel'));
            }

            $data = array(
                'hotel' => $this->request->getVar('hotel'),
                'location' => $this->request->getVar('location'),
                // 'hotel_code' => $this->request->getVar('code'),
                'room_type' => $this->request->getVar('roomtype'),
                'sro' => $this->request->getVar('sro'),
                'sbb' => $this->request->getVar('sbb'),
                'shb' => $this->request->getVar('shb'),
                'sfb' => $this->request->getVar('sfb'),
                'sdate' => $this->request->getVar('sdate'),
                'edate' => $this->request->getVar('edate'),
                'dro' => $this->request->getVar('dro'),
                'dbb' => $this->request->getVar('dbb'),
                'dhb' => $this->request->getVar('dhb'),
                'dfb' => $this->request->getVar('dfb'),
                'dsdate' => $this->request->getVar('sdate'),
                'dedate' => $this->request->getVar('edate'),
                'tro' => $this->request->getVar('tro'),
                'tbb' => $this->request->getVar('tbb'),
                'thb' => $this->request->getVar('thb'),
                'tfb' => $this->request->getVar('tfb'),
                'tsdate' => $this->request->getVar('sdate'),
                'tedate' => $this->request->getVar('edate')
            );

            $hotelrateMOdel = new HotelRateModel();

            if ($hotelrateMOdel->where([
                'hotel' => $this->request->getVar('hotel'),
                'location' => $this->request->getVar('location'),
                'room_type' => $this->request->getVar('roomtype'),
                'sdate' => $this->request->getVar('sdate'),
                'edate' => $this->request->getVar('edate')
            ])->where('id !=', $id)->first()) {
                // Return an error response
                return $this->response->setJSON(['status' => 400, 'message' => 'Hotel Rate already exists']);
            }

            if ($hotelrateMOdel->update($id, $data)) {
                return $this->response->setJSON(["status" => 200, "message" => "Hotel Rate updated successfully!"]);
            }
            throw new \Exception("Room Rate update failed!");
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // delete - route : base_url/master_files/room_rate/delete
    public function delete()
    {
        try {
            $id = $this->request->getGet('id');
            $hotel = $this->request->getGet('hotel');

            if ($this->lock()->isLocked($hotel)) {
                return $this->lockedResponse($hotel);
            }

            $tourquotationsModel = new TourQuotationsModel();
            $count = $tourquotationsModel->where('hotel_acc', $hotel)->findAll();

            $accountrunningModel = new AccountRunningModel();
            $acc_count = $accountrunningModel->where('L_Code', $hotel)->findAll();

            if (count($count) > 0 || count($acc_count) > 0) {
                return $this->response->setJSON(["status" => 400, "message" => "This Hotel Rate is already used in Tour Quotations!"]);
            } else {

                $hotelrateMOdel = new HotelRateModel();
                if ($hotelrateMOdel->delete($id)) {
                    return $this->response->setJSON(["status" => 200, "message" => "Room Details Delete successfully!"]);
                }
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/hotel_rate/get_location
    public function get_location()
    {

        $supplierModel = new SupplierModel();
        $hotel = $supplierModel->select("Address")->where('Sup_Acc_No', $this->request->getGet('hotel'))->find();


        if (!$hotel) {
            return $this->response->setJSON(["status" => 404, "message" => "Location not found!"]);
        }
        return $this->response->setJSON(["status" => 200, "data" => $hotel]);
    }

    // route : base_url/master_files/room_rate/show/{id}
    public function show($id)
    {
        try {
            // Create a new EntranceFeeModel instance
            $supplierModel = new SupplierModel();
            $supplier_location = $supplierModel->where('Sup_Acc_No', $id)->first();

            // Return the entrance fee data — tf-pilot-r3: the supplier's bank account number to its last four digits for a sign-in that may not see it
            return $this->response->setJSON(['status' => 200, 'data' => \App\Libraries\TourPayments\Mask::bank($supplier_location)]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/hotel_rate/old_hotel_rates
    public function old_hotel_rates($hotel)
    {
        try {

            // Create a new EntranceFeeModel instance
            $hotelrateMOdel = new HotelRateModel();
            $old_rate_data = $hotelrateMOdel->select('hotel_rate.*, supplier.Name, room_type.rtype')->join('supplier', 'hotel_rate.hotel = supplier.Sup_Acc_No', 'left')->join('room_type', 'hotel_rate.room_type = room_type.id')->where('hotel', $hotel)->orderBy('hotel_rate.sdate', 'ASC')->orderBy('hotel_rate.edate', 'ASC')->findAll();
            // log_message('critical', $hotelrateMOdel->getLastQuery());

            // Return the entrance fee data
            return $this->response->setJSON(['status' => 200, 'data' =>  $old_rate_data]);
        } catch (\Exception $e) {
            // Return an error response
            return $this->response->setJSON(['status' => 500, 'message' => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/hotel_rate/get_hotels
    public function get_hotels()
    {
        $destination = $this->request->getGet('search_location');


        if (empty($destination)) {
            return $this->response->setJSON(['status' => 400, 'message' => 'Search location is required']);
        }

        $hotelrateModel = new HotelRateModel();
        $data['hotel_code'] = $hotelrateModel->select('hotel_code')->like('location', $destination)->findAll();

        $supplierModel = new SupplierModel();
        $data['hotels'] = $supplierModel->where('supplier.sup_cat', 1)->like('supplier.Address', $destination)->orderBy('supplier.Name', 'ASC')->findAll();

        if (!empty($data)) {
            // tf-pilot-r3: the hotels' bank account numbers to their last four digits for a sign-in that may not see them
            return $this->response->setJSON(['status' => 200, 'data' => \App\Libraries\TourPayments\Mask::bank($data)]);
        } else {
            return $this->response->setJSON(['status' => 404, 'message' => 'No data found']);
        }
    }

    public function date_overlap()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'hotel' => 'required|strip_tags',
            'roomtype' => 'required|strip_tags',
            'startdate' => 'required|strip_tags',
            'enddate' => 'required|strip_tags'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
        }

        $hotel = $this->request->getGet('hotel');
        $roomtype = $this->request->getGet('roomtype');
        $startdate = $this->request->getGet('startdate');
        $enddate = $this->request->getGet('enddate');

        $hotelrateModel = new HotelRateModel();
        $date_details = $hotelrateModel->select('*')->where('hotel', $hotel)->where('room_type', $roomtype)->findAll();
        // log_message('critical', $hotelrateModel->getLastQuery());

        foreach ($date_details as $val) {
            $sdate = $val['sdate'];
            $edate = $val['edate'];

            if (($startdate >= $sdate && $startdate <= $edate) || ($enddate >= $sdate && $enddate <= $edate)) {

                return $this->response->setJSON(['status' => 202, "message" => "A Hotel Code Has been Already Created for this Date range,Room Type for this Hotel."]);
            }
        }
    }

    public function get_start_date()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'hotel' => 'required|strip_tags',
            'roomtype' => 'required|strip_tags',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON(["status" => 400, "message" => "Validation Failed! Please check the input fields.", "errors" => $validation->getErrors()]);
        }

        $hotel = $this->request->getGet('hotel');
        $roomtype = $this->request->getGet('roomtype');

        $hotelrateModel = new HotelRateModel();
        $result = $hotelrateModel->select('max(edate) as max_enddate')->where('hotel', $hotel)->where('room_type', $roomtype)->groupBy('hotel,room_type')->first();

        if ($result) {
            $max_enddate = $result['max_enddate'];
            $date = new \DateTime($max_enddate);
            $date->modify('+1 day');
            $next_date = $date->format('Y-m-d');
            $data['next_date'] = $next_date;

            return $this->response->setJSON(["status" => 200, "message" => "", 'data' =>  $data['next_date']]);
        }
    }

    // route : base_url/master_files/hotel_rate/ai_extract
    // Upload a contract PDF/image, extract rate rows via OpenAI, return them for preview (no DB write yet)
    public function ai_extract()
    {
        try {
            $file = $this->request->getFile('contract_file');

            if (!$file || !$file->isValid()) {
                return $this->response->setJSON(["status" => 400, "message" => "No valid file uploaded."]);
            }

            $allowedMimes = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ];

            $mimeType = $file->getMimeType();

            if (!array_key_exists($mimeType, $allowedMimes)) {
                return $this->response->setJSON(["status" => 400, "message" => "Invalid file type. Only PDF, JPG, and PNG files are allowed."]);
            }

            if ($file->getSizeByUnit('mb') > 15) {
                return $this->response->setJSON(["status" => 400, "message" => "File is too large. Maximum size is 15MB."]);
            }

            $originalName = $file->getName();
            $storedName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads', $storedName);
            $storedPath = WRITEPATH . 'uploads/' . $storedName;

            try {
                $extractor = new HotelRateExtractor();
                $rows = $extractor->extract($storedPath, $mimeType, $originalName);
            } finally {
                if (is_file($storedPath)) {
                    unlink($storedPath);
                }
            }

            if (empty($rows)) {
                return $this->response->setJSON(["status" => 404, "message" => "No rate rows could be found in this document."]);
            }

            $roomTypeModel = new RoomTypeModel();

            foreach ($rows as &$row) {
                $roomTypeName = trim($row['room_type'] ?? '');

                $roomType = $roomTypeModel->where('rtype', $roomTypeName)->first();

                if (!$roomType && $roomTypeName !== '') {
                    $roomTypeModel->insert(['rtype' => $roomTypeName]);
                    $roomType = $roomTypeModel->where('rtype', $roomTypeName)->first();
                }

                $row['room_type_id'] = $roomType['id'] ?? null;
                $row['room_type_name'] = $roomTypeName;
            }
            unset($row);

            return $this->response->setJSON(["status" => 200, "message" => "Extracted " . count($rows) . " rate row(s).", "data" => $rows]);
        } catch (\Exception $e) {
            log_message('error', 'HotelRateExtractor Exception: ' . $e->getMessage());
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }

    // route : base_url/master_files/hotel_rate/ai_confirm
    // Insert user-confirmed AI-extracted rows into hotel_rate
    public function ai_confirm()
    {
        try {
            $payload = $this->request->getJSON(true);

            $hotel = trim($payload['hotel'] ?? '');
            $location = trim($payload['location'] ?? '');
            $rows = $payload['rows'] ?? [];

            if (empty($hotel) || empty($location) || !is_array($rows) || empty($rows)) {
                return $this->response->setJSON(["status" => 400, "message" => "Hotel, location, and at least one rate row are required."]);
            }

            if ($this->lock()->isLocked($hotel)) {
                return $this->lockedResponse($hotel);
            }

            $hotelRateModel = new HotelRateModel();

            $inserted = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $roomTypeId = $row['room_type_id'] ?? null;
                $sdate = $row['sdate'] ?? '';
                $edate = $row['edate'] ?? '';

                if (empty($roomTypeId) || empty($sdate) || empty($edate)) {
                    $skipped++;
                    continue;
                }

                $exists = $hotelRateModel->where([
                    'hotel' => $hotel,
                    'location' => $location,
                    'room_type' => $roomTypeId,
                    'sdate' => $sdate,
                    'edate' => $edate,
                ])->first();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $hotelRateModel->insert([
                    'hotel' => $hotel,
                    'location' => $location,
                    'hotel_code' => '',
                    'room_type' => $roomTypeId,
                    'sro' => $row['sro'] ?? 0,
                    'sbb' => $row['sbb'] ?? 0,
                    'shb' => $row['shb'] ?? 0,
                    'sfb' => $row['sfb'] ?? 0,
                    'sdate' => $sdate,
                    'edate' => $edate,
                    'dro' => $row['dro'] ?? 0,
                    'dbb' => $row['dbb'] ?? 0,
                    'dhb' => $row['dhb'] ?? 0,
                    'dfb' => $row['dfb'] ?? 0,
                    'dsdate' => $sdate,
                    'dedate' => $edate,
                    'tro' => $row['tro'] ?? 0,
                    'tbb' => $row['tbb'] ?? 0,
                    'thb' => $row['thb'] ?? 0,
                    'tfb' => $row['tfb'] ?? 0,
                    'tsdate' => $sdate,
                    'tedate' => $edate,
                ]);

                $inserted++;
            }

            return $this->response->setJSON([
                "status" => 201,
                "message" => "Added {$inserted} rate row(s)." . ($skipped > 0 ? " Skipped {$skipped} (duplicate or incomplete)." : ""),
                "inserted" => $inserted,
                "skipped" => $skipped,
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
        }
    }
}
