<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\ConsigneeModel;
use App\Models\TourQuotationsModel;
use App\Models\AccountRunningModel;
use App\Models\InvoiceNumberModel;
use App\Models\TourActivityModel;
use App\Models\TourSubLocationsModel;
use App\Models\PassengerDetailsModel;
use App\Models\TourItineraryModel;

class TourDetailsController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/tour_details
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $consigneeModel = new ConsigneeModel();
        $data['tour_years'] = $consigneeModel->select('YEAR(start_date) as year')
            ->distinct()
            ->where('start_date IS NOT NULL')
            ->where('start_date !=', '0000-00-00')
            ->orderBy('year', 'DESC')
            ->findAll();

        $selectedYear = $this->request->getGet('year');

        if (!empty($selectedYear)) {
            $year = $selectedYear;
        } else {
            $year = date('Y');
        }

        $data['s_year'] = $year;

        $data['tour_details'] = $consigneeModel->select('MONTH(start_date) as month, MONTHNAME(start_date) as month_name')
            ->distinct()->where('YEAR(start_date)', $year)->orderBy('month', 'DESC')->findAll();

        // Search
        $keyword = $this->request->getGet('keyword');

        // uncode the keyword
        $keyword = urldecode($keyword);

        $TourQuotationsModel = new TourQuotationsModel();

        $tourDetailsQuery = $TourQuotationsModel->select('tour_quotations.quotation_id AS tour_id, Max(consignee.end_date) AS end_date, Min(tour_quotations.tour_date) AS start_date, Max(tour_quotations.confirm) As Confirm, Max(tour_quotations.finalize) As Finalize ,Max(consignee.consignee) AS consignee, Max(consignee.agent_name) AS agent_name,Max(consignee.create_user) AS create_user,Max(consignee.create_time) AS create_time, consignee.mobile as mobile,consignee.email AS email, consignee.passenger_count AS passenger_count, Sum(tour_quotations.extra_act_fee) as activity_fee, Sum(tour_quotations.extra_kilo) as extra_km, Sum(tour_quotations.distance) As fullkm, Max(consignee.vehicle_rate) AS vehicle_rate, consignee.manual_id, consignee.create_user')->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')->where('tour_quotations.confirm !=', 0)->groupBy('tour_quotations.quotation_id');

        $queries = [];

        if (!empty($keyword)) {
            $keywordQuery = clone $tourDetailsQuery;
            $keywordQuery = $keywordQuery
                ->orWhere('consignee.tour_id like', "%$keyword%")
                ->orWhere('consignee.consignee like', "%$keyword%")
                ->orWhere('consignee.manual_id like', "%$keyword%");
            $queries[] = $keywordQuery;
        }

        if (!empty($queries)) {
            $tourDetailsQuery = array_shift($queries);
            foreach ($queries as $query) {
                $tourDetailsQuery = $tourDetailsQuery->union($query, false);
            }
            $tourDetails = $tourDetailsQuery->get()->getResult();
            $tourid = array_column($tourDetails, 'tour_id');
            // add if condition to check if the tour id is not empty
            if (!empty($tourid)) {
                $passengerDetailsModel = new PassengerDetailsModel();
                $passengerDetails = $passengerDetailsModel->whereIn('tour_id', $tourid)->findAll();
                $data['pax_details'] = $passengerDetails;
            } else {
                $tourDetails = [];
            }
        } else {
            $tourDetails = [];
        }

        $data['tour_details_search'] = $tourDetails;


        return view('pages/tour/tour_details', $data);
    }

    //route : base_url/tour/tour_dashboard/monthly_dashboard

    public function monthly_dashboard()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $year = $this->request->getGet('year');
        $month = $this->request->getGet('month');
        $tour = $this->request->getGet('tour_id');

        // if(empty($tour) ){
        //     $data['year'] = $year;
        //     $data['month'] = $month;
        // } else {
        //     $data['year'] = $year;
        //     $data['month'] = $month;
        //     $data['tour_id'] = $tour;
        // }

        $data['year'] = $year;
        $data['month'] = $month;

        // Initialize the TourQuotationsModel
        $TourQuotationsModel = new TourQuotationsModel();

        // Create the query
        if (empty($tour)) {
            $tourDetailsQuery = $TourQuotationsModel->db->table('tour_quotations')
                ->select([
                    'tour_quotations.quotation_id AS tour_id',
                    'consignee.agent',
                    'MAX(consignee.end_date) AS end_date',
                    'MIN(tour_quotations.tour_date) AS start_date',
                    'MAX(consignee.consignee) AS consignee',
                    'MAX(consignee.passenger_count) AS pax_count',
                    'MAX(consignee.manual_id) AS manual_id',
                    'MAX(consignee.transport_advance) AS transport_advance',
                    'MAX(consignee.transport_settled) AS transport_settled',
                    'MAX(tour_quotations.confirm) AS Confirm',
                    'MAX(tour_quotations.finalize) AS Finalize',
                    'MAX(tour_quotations.quotation_day) AS quotation_days',
                    'SUM(tour_quotations.hotel_booking_flag = 1) AS hotel_booking_cnt',
                    'SUM(tour_quotations.booking_confirmed_flag = 1) AS booking_confirmed_cnt',
                    'SUM(tour_quotations.hotel_payment_flag = 1) AS hotel_payment_cnt',
                    'MAX(consignee.identical) AS identical',
                    'MAX(consignee.verify) AS verify',
                    'MAX(consignee.create_user) AS create_user',
                    'MAX(consignee.amend_inv) AS amend_inv',
                ])
                ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
                ->where('YEAR(tour_quotations.tour_date)', $year)
                ->where('MONTH(tour_quotations.tour_date)', $month)
                ->where('consignee.is_template', 0)
                ->groupBy('tour_quotations.quotation_id');

            // Execute the query and get the result
            $tourDetails = $tourDetailsQuery->get()->getResult();

            $consigneeModel = new ConsigneeModel();
            $tourConsigneeQuery = $consigneeModel->select([
                'tour_id',
                'agent',
                'end_date',
                'start_date',
                'consignee',
                'passenger_count AS pax_count',
                'manual_id',
                'transport_advance',
                'transport_settled',
                '0 AS Confirm',
                'finalize AS Finalize',
                '0 AS hotel_booking_cnt',
                '0 AS booking_confirmed_cnt',
                '0 AS hotel_payment_cnt',
                'DATEDIFF(end_date, start_date) AS quotation_days',
                'identical',
                'verify',
                'create_user'
            ])->where('YEAR(start_date)', $year)->where('MONTH(start_date)', $month)->where('is_template', 0)->groupBy('tour_id');

            $tourConsignee = $tourConsigneeQuery->get()->getResult();
        } else {
            $tourDetailsQuery = $TourQuotationsModel->db->table('tour_quotations')
                ->select([
                    'tour_quotations.quotation_id AS tour_id',
                    'consignee.agent',
                    'MAX(consignee.end_date) AS end_date',
                    'MIN(tour_quotations.tour_date) AS start_date',
                    'MAX(consignee.consignee) AS consignee',
                    'MAX(consignee.passenger_count) AS pax_count',
                    'MAX(consignee.manual_id) AS manual_id',
                    'MAX(consignee.transport_advance) AS transport_advance',
                    'MAX(consignee.transport_settled) AS transport_settled',
                    'MAX(tour_quotations.confirm) AS Confirm',
                    'MAX(tour_quotations.finalize) AS Finalize',
                    'MAX(tour_quotations.quotation_day) AS quotation_days',
                    'SUM(tour_quotations.hotel_booking_flag = 1) AS hotel_booking_cnt',
                    'SUM(tour_quotations.booking_confirmed_flag = 1) AS booking_confirmed_cnt',
                    'SUM(tour_quotations.hotel_payment_flag = 1) AS hotel_payment_cnt',
                    'MAX(consignee.identical) AS identical',
                    'MAX(consignee.verify) AS verify',
                    'MAX(consignee.create_user) AS create_user',
                    'MAX(consignee.amend_inv) AS amend_inv',
                ])
                ->join('consignee', 'consignee.tour_id = tour_quotations.quotation_id', 'LEFT')
                ->where('consignee.tour_id', $tour)
                ->groupBy('tour_quotations.quotation_id');

            $tourDetails = $tourDetailsQuery->get()->getResult();

            // log_message('critical', $TourQuotationsModel->getLastQuery());

            $consigneeModel = new ConsigneeModel();
            $tourConsigneeQuery = $consigneeModel->select([
                'tour_id',
                'agent',
                'end_date',
                'start_date',
                'consignee',
                'passenger_count AS pax_count',
                'manual_id',
                'transport_advance',
                'transport_settled',
                '0 AS Confirm',
                'finalize AS Finalize',
                '0 AS hotel_booking_cnt',
                '0 AS booking_confirmed_cnt',
                '0 AS hotel_payment_cnt',
                'DATEDIFF(end_date, start_date) AS quotation_days',
                'identical',
                'verify',
                'create_user'
            ])->where('tour_id', $tour)->where('is_template', 0)->groupBy('tour_id');

            $tourConsignee = $tourConsigneeQuery->get()->getResult();
        }
        // log_message('critical', $TourQuotationsModel->getLastQuery());

        $data['tour_details'] = $tourDetails;

        // Check if $data['tour_details'] is set for each tour_id in $tourConsignee
        foreach ($tourConsignee as $consignee) {
            $found = false;
            foreach ($data['tour_details'] as $detail) {
                if ($detail->tour_id == $consignee->tour_id) {
                    $found = true;
                    break;
                }
            }
            // If not found, insert the consignee details into $data['tour_details']
            if (!$found) {
                $data['tour_details'][] = $consignee;
            }
        }

        // Initialize arrays
        $reorderedTourDetails = [];
        $identicalMap = [];

        // Create a map of identical tour_ids
        foreach ($data['tour_details'] as $detail) {
            if (!empty($detail->identical)) {
                $identicalMap[$detail->identical][] = $detail;
            } else {
                $reorderedTourDetails[] = $detail;
            }
        }

        // Initialize an array to track added tour IDs
        $addedTourIds = [];

        // Function to add tours recursively
        function addToursRecursively($tourId, &$finalList, &$addedIds, $identicalMap)
        {
            if (isset($identicalMap[$tourId])) {
                foreach ($identicalMap[$tourId] as $identicalTour) {
                    if (!in_array($identicalTour->tour_id, $addedIds)) {
                        $addedIds[] = $identicalTour->tour_id;
                        $finalList[] = $identicalTour; // Add the tour to the final list
                        // Recurse to find further connections
                        addToursRecursively($identicalTour->tour_id, $finalList, $addedIds, $identicalMap);
                    }
                }
            }
        }

        // Prepare the final reordered details
        $finalReorderedTourDetails = [];
        foreach ($reorderedTourDetails as $detail) {
            if (!in_array($detail->tour_id, $addedTourIds)) {
                $addedTourIds[] = $detail->tour_id; // Mark the main tour as added
                $finalReorderedTourDetails[] = $detail; // Add the main tour
                addToursRecursively($detail->tour_id, $finalReorderedTourDetails, $addedTourIds, $identicalMap);
            }
        }

        // Set the final result
        $data['tour_details'] = $finalReorderedTourDetails;

        // Initialize an empty array for tour_id
        $tourid = [];

        // Check if $data['tour_details'] is not empty and contains valid tour_id
        if (!empty($data['tour_details'])) {
            // Extract tour_id from $data['tour_details']
            $tourid = array_column($data['tour_details'], 'tour_id');

            // If any tour_id is null, filter it out
            $tourid = array_filter($tourid, function ($value) {
                return !is_null($value);
            });
        }



        // If tour_id array is empty, handle it gracefully
        if (!empty($tourid)) {
            $passengerDetailsModel = new PassengerDetailsModel();
            $passengerDetails = $passengerDetailsModel->whereIn('tour_id', $tourid)->findAll();
            $tourPassengerCount = [];

            foreach ($tourid as $id) {
                $count = 0;
                foreach ($passengerDetails as $detail) {
                    if ($detail['tour_id'] == $id) {
                        $count++;
                    }
                }
                $tourPassengerCount[] = [
                    'tour_id' => $id,
                    'count' => $count
                ];
            }

            $differecePassengerCount = [];

            if (!empty($data['tour_details'])) {
                foreach ($data['tour_details'] as $key => $value) {
                    foreach ($tourPassengerCount as $pax) {
                        if ($value->tour_id == $pax['tour_id']) {
                            if ($pax['count'] != $value->pax_count) {
                                $differecePassengerCount[] = [
                                    'tour_id' => $value->tour_id,
                                    'count' => $pax['count'],
                                    'pax_count' => $value->pax_count
                                ];
                            }
                        }
                    }
                }
            }

            $data['differecePassengerCount'] = $differecePassengerCount;

            $data['pax_details'] = $passengerDetails;
        } else {
            // If no tour_id found or all are null, return an empty pax_details array
            $data['pax_details'] = [];
        }

        return view('pages/tour/monthly_dashboard_new', $data);
    }

    //route : base_url/tour/tour_details/override_tour
    public function overrideTour()
    {

        $tour_id = $this->request->getPost('tour_id');

        try {

            $TourQuotationsModel = new TourQuotationsModel();
            $ConsigneeModel = new ConsigneeModel();
            $tourActivityModel = new TourActivityModel();
            $tourItineraryModel = new TourItineraryModel();
            $tourSubLocationsModel = new TourSubLocationsModel();
            $passengerDetailsModel = new PassengerDetailsModel();

            $invoiceNumberModel = new InvoiceNumberModel();
            $new_tour_id = $invoiceNumberModel->incrementTourNo();


            $rowcount_tour = $this->countResultAndInsert($TourQuotationsModel, $tour_id, 'quotation_id', $new_tour_id);
            $rowcount_consignee = $this->countResultAndInsert($ConsigneeModel, $tour_id, 'tour_id', $new_tour_id);
            $rowcount_activity = $this->countResultAndInsert($tourActivityModel, $tour_id, 'tour_id', $new_tour_id);
            $rowcount_itinerary = $this->countResultAndInsert($tourItineraryModel, $tour_id, 'tour_id', $new_tour_id);
            $rowcount_sublocations = $this->countResultAndInsert($tourSubLocationsModel, $tour_id, 'quotation_id', $new_tour_id);
            $rowcount_passenger = $this->countResultAndInsert($passengerDetailsModel, $tour_id, 'tour_id', $new_tour_id);

            if ($rowcount_tour > 0 || $rowcount_consignee > 0 || $rowcount_activity > 0 || $rowcount_itinerary > 0 || $rowcount_sublocations > 0 || $rowcount_passenger > 0) {

                return $this->response->setJSON(["status" => 200, "message" => "Successfully Overrided", "data" => $new_tour_id]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }


    public function countResultAndInsert($model, $tour_id, $column, $new_tour_id)
    {
        $rowcount = 0;

        $result = $model->where($column, $tour_id)->findAll();
        if (!empty($result)) {
            foreach ($result as $row) {
                $row[$column] = $new_tour_id;

                if ($model->table == 'consignee') {
                    $row['identical'] = $tour_id;
                }

                $model->insert($row);

                $rowcount++;
            }
        }

        return $rowcount;
    }

    //routes: base_url/tour/tour_details/verify_tour
    public function verifyTour()
    {
        $tour_id = $this->request->getPost('tour_id');

        try {

            $ConsigneeModel = new ConsigneeModel();
            $rowcount = $ConsigneeModel->where('tour_id', $tour_id)->set(['verify' => 1])->update();

            $TourQuotationsModel = new TourQuotationsModel();
            $rowcount_tour = $TourQuotationsModel->where('quotation_id', $tour_id)->set(['verify' => 1])->update();

            if ($rowcount > 0 && $rowcount_tour > 0) {
                return $this->response->setJSON(["status" => 200, "message" => "Successfully Verified"]);
            } else {
                return $this->response->setJSON(["status" => 500, "message" => "Something went wrong"]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 400, 'message' => $e->getMessage()]);
        }
    }
}
