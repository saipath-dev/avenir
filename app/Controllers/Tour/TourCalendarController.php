<?php

namespace App\Controllers\Tour;

use App\Libraries\MenuLoader;
use App\Controllers\BaseController;

class TourCalendarController extends BaseController
{

    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // $data['quotation_id'] = $quotation_id;
        // $data['dates'] = array();

        // if ($quotation_id > 0) {
        //     $tourQuotationModel = new \App\Models\TourQuotationsModel();
        //     $data['dates'] = $tourQuotationModel->where('quotation_id', $quotation_id)->findAll();

        //     foreach ($data['dates'] as $key => $date) {
        //         $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();
        //         $data['dates'][$key]['sub_locations'] = $tourSubLocationsModel->where('date_id', $date['id'])->findAll();
        //     }
        // }

        return view('pages/tour/calendar', $data);
    }

    public function get_tour_data()
    {
        // Get the form data
        $consigneeModel = new \App\Models\ConsigneeModel();
        $tour_data = $consigneeModel->select('consignee.*')->join('tour_quotations', 'consignee.tour_id = tour_quotations.quotation_id')->where('tour_quotations.confirm',1)->where('tour_quotations.finalize',1)->groupBy('consignee.tour_id')->findAll();
        // log_message('critical', $consigneeModel->getLastQuery());

        if (!empty($tour_data)) {
            foreach ($tour_data as $key => $tour) {
                $eventdata[] = array(
                    'title' => $tour['consignee'],
                    'start' => $tour['start_date'],
                    'end' => $tour['end_date'] . "T23:59:00",
                    'tour_id' => $tour['tour_id'],
                    'manu_id' => $tour['manual_id'],
                    'color' => '#3d83cf',
                    'agent' => $tour['agent_name'],
                );
            }
        }
        return json_encode($eventdata);
    }

    // route : base_url/tour/tour/url_call
    public function details()
    {
        $tour = $this->request->getGet('tour');
        $consigneeModel = new \App\Models\ConsigneeModel();
        $tour_date = $consigneeModel->where('tour_id', $tour)->first();
        return $this->response->setJSON(['status' => 200, 'data' => $tour_date]);
    }

    // public function location_delete()
    // {
    //     try {
    //         $id = $this->request->getGet('id');

    //         $tourSubLocationsModel = new \App\Models\TourSubLocationsModel();

    //         $sub_locations = $tourSubLocationsModel->where('id', $id)->findAll();

    //         $km = $sub_locations[0]['km'];
    //         $extra_km = $sub_locations[0]['extra_km'];
    //         $date_id = $sub_locations[0]['date_id'];
    //         $quotation_id = $sub_locations[0]['quotation_id'];
    //         $tour_row = $sub_locations[0]['tour_row'];


    //         $tourQuotationModel = new \App\Models\TourQuotationsModel();
    //         $quotation = $tourQuotationModel->select('distance,extra_kilo')->where('quotation_id', $quotation_id)->where('quotation_day', $tour_row)->where('tour_date', $date_id)->findAll();

    //         $total_km = $quotation[0]['distance'] - $km;
    //         $extra_kilo = $quotation[0]['extra_kilo'] - $extra_km;

    //         $tourQuotationModel->where('quotation_id', $quotation_id)->where('quotation_day', $tour_row)->where('tour_date', $date_id)->set(['distance' => $total_km, 'extra_kilo' => $extra_kilo])->update();

    //         if ($tourSubLocationsModel->delete($id)) {
    //             return $this->response->setJSON(["status" => 200, "message" => "Location Delete successfully!"]);
    //         }
    //     } catch (\Exception $e) {
    //         return $this->response->setJSON(["status" => 500, "message" => $e->getMessage()]);
    //     }
    // }
}
