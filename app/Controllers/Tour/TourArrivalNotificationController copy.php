<?php

namespace App\Controllers\Tour;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\TourQuotationsModel;
use App\Models\PassengerDetailsModel;
use App\Models\ConsigneeModel;


class TourArrivalNotificationController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/tour/ArraivalNotification
    public function index()
    {

        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        $cat = $this->request->getGet('cat');

        $cat = urldecode($cat);
        $today = date('Y-m-d');

        $week3 = date('Y-m-d', strtotime($today . ' + 20 days'));
        $week2 = date('Y-m-d', strtotime($today . ' + 13 days'));
        $week1 = date('Y-m-d', strtotime($today . ' + 6 days'));
        $day4 = date('Y-m-d', strtotime($today . ' + 4 days'));
        $day3 = date('Y-m-d', strtotime($today . ' + 3 days'));
        $day2 = date('Y-m-d', strtotime($today . ' + 2 days'));
        $day1 = date('Y-m-d', strtotime($today . ' + 1 days'));

        $consigneeModel = new ConsigneeModel();

        $dateRanges = [
            'threeweeks' => [$week2, $week3, '>', '<='],
            'twoweeks' => [$week1, $week2, '>', '<='],
            'oneweek' => [$day4, $week1, '>=', '<='],
            'threedays' => [$day2, $day3, '>=', '<='],
            'twodays' => [$day1, $day2, '>=', '<='],
            'oneday' => [$day1, $day1, '=', '<='],
            'consignee' => [date('Y-m-d', strtotime($today)), date('Y-m-d', strtotime($today)), '=', '=']
        ];

        foreach ($dateRanges as $key => $range) {
            $data[$key] = $consigneeModel->select([
                'tour_quotations.quotation_id AS tour_id',
                'consignee.agent',
                'MAX(consignee.end_date) AS end_date',
                'MIN(consignee.start_date) AS start_date',
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
                'MAX(consignee.create_user) AS create_user',
                'MAX(consignee.hotel_only) AS hotel_only',
                'MAX(consignee.transport_only) AS transport_only',
                'SUM(tour_quotations.total_hotel_doller) AS total_hotel_doller',
                'SUM(tour_quotations.pay_doller_count) AS pay_doller_count'
            ])
                ->join('tour_quotations', 'tour_quotations.quotation_id = consignee.tour_id', 'LEFT')
                ->where('consignee.start_date'.  $range[2], $range[0])
                ->where('consignee.start_date'.  $range[3], $range[1])
                ->where('consignee.verify', 1)
                ->where('consignee.is_template', 0)
                ->groupBy('consignee.tour_id')
                ->orderBy('consignee.start_date', 'ASC')
                ->findAll();
                // log_message('critical',$consigneeModel->getLastQuery());
        }


        // append $data to $week3, $week2, $week1, $day3, $day2, $day1
        $data['week3'] = $week3;
        $data['week2'] = $week2;
        $data['week2plus1'] = date('Y-m-d', strtotime($week2 . ' + 1 days'));
        $data['week1'] = $week1;
        $data['weekplus1'] = date('Y-m-d', strtotime($week1 . ' + 1 days'));
        $data['day4'] = $day4;
        $data['day3'] = $day3;
        $data['day2'] = $day2;
        $data['day1'] = $day1;


        return view('pages/tour/tour_arraival_note', $data);
    }
}
