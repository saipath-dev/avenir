<?php

namespace App\Controllers;

class SmsController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    public function generateToken($tour_id, $driver_acc_no)
    {
        return hash('sha256', $tour_id . $driver_acc_no . time() . bin2hex(random_bytes(8)));
    }

    public function validateToken($token)
    {
        $db = \Config\Database::connect();
        $smsLog = $db->table('sms_log')
            ->where('token', $token)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->where('status', 'SUCCESS')
            ->get()
            ->getRowArray();

        return $smsLog ? $smsLog : false;
    }

    public function viewItinerary($token)
    {
        $tokenData = $this->validateToken($token);

        if (!$tokenData) {
            return view('errors/html/error_404');
        }

        $tour_id = $tokenData['tour_id'];

        // Fetch tour data directly without requiring session/menu data
        $db = \Config\Database::connect();

        // Get consignee details
        $consignee_details = $db->table('consignee')
            ->where('tour_id', $tour_id)
            ->get()
            ->getResultArray();

        // Get itinerary details - grouped by tour_date
        $itinerary_dates = $db->table('tour_itinerary')
            ->where('tour_id', $tour_id)
            ->groupBy('tour_date')
            ->orderBy('tour_date', 'ASC')
            ->get()
            ->getResultArray();

        // Build itinerary data structure
        $data_array = array();
        foreach ($itinerary_dates as $value) {
            $data_array[$value['tour_date']]['details'] = $db->table('tour_itinerary')
                ->where('tour_id', $tour_id)
                ->where('tour_date', $value['tour_date'])
                ->orderBy('order_id')
                ->get()
                ->getResultArray();
        }

        // Get invoice details if needed
        $invoice_details = $db->table('debtor_invoice_log')
            ->where('tour_id', $tour_id)
            ->get()
            ->getRowArray();

        // Get hotel information from quotations
        $hotel_data = [];
        $hotels = $db->table('tour_quotations')
            ->select('tour_date, hotel_name, quotation_day')
            ->where('quotation_id', $tour_id)
            ->where('hotel_name IS NOT NULL')
            ->where('hotel_name !=', '')
            ->orderBy('tour_date')
            ->get()
            ->getResultArray();

        foreach ($hotels as $hotel) {
            $hotel_data[$hotel['tour_date']] = $hotel['hotel_name'];
        }

        $data = [
            'consignee_details' => $consignee_details,
            'itinerary' => $data_array,
            'invoice_details' => $invoice_details,
            'hotels' => $hotel_data,
            'token' => $token
        ];

        return view('pages/driver/itinerary_view', $data);
    }

    public function downloadPdf($token)
    {
        // Simply redirect to the view page with a print parameter
        // The browser's print functionality will use the exact same styling
        return redirect()->to(base_url('driver/' . $token) . '?print=1');
    }

    private function buildPdfHtml($consignee_details, $itinerary, $invoice_details, $hotels = [])
    {
        // Helper function to highlight times
        $highlightTimes = function($text) {
            return preg_replace_callback('/\b(\d{4})\b/', function($matches) {
                $time = $matches[1];
                $formatted = substr($time, 0, 2) . ':' . substr($time, 2, 2);
                return '<span class="time-highlight">' . $formatted . '</span>';
            }, $text);
        };

        $html = '<style>
            body { font-family: helvetica, arial; }
            h2 { color: #1a1a1a; font-size: 16px; padding-bottom: 6px; margin-bottom: 15px; font-weight: bold; }
            h3 { color: #1a1a1a; font-size: 13px; padding-bottom: 6px; margin-top: 20px; margin-bottom: 15px; font-weight: bold; }
            .info-item { background-color: #f9f9f9; padding: 10px; vertical-align: top; }
            .info-label { font-weight: bold; color: #444; font-size: 7px; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.5px; display: block; }
            .info-value { color: #1a1a1a; font-size: 9px; font-weight: 500; display: block; }
            .day-card { padding: 14px; margin-bottom: 15px; background-color: #fafafa; }
            .day-header { font-size: 12px; font-weight: bold; color: #1a1a1a; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #2c5aa0; }
            .accommodation-section { background-color: #e8f0f8; color: #1a1a1a; padding: 8px 10px; margin-bottom: 12px; }
            .hotel-name-pdf { font-weight: bold; font-size: 11px; color: #1a1a1a; }
            .day-content { width: 100%; }
            .day-content table { width: 100%; border-collapse: collapse; }
            .day-content td { vertical-align: top; padding: 10px; background-color: white; }
            .day-content td:nth-child(1) { width: 40%; }
            .day-content td:nth-child(2) { width: 40%; }
            .day-content td:nth-child(3) { width: 20%; }
            .column-title { font-size: 9px; font-weight: bold; color: #2c5aa0; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; padding-bottom: 0px; display: block; }
            .meal-item { margin-bottom: 8px; padding: 8px 10px; background-color: #f0f8f0; }
            .meal-type { font-weight: bold; display: block; margin-bottom: 3px; color: #1b5e20; font-size: 8px; }
            .meal-location { font-size: 7px; color: #4a4a4a; display: block; }
            .activity-item { margin-bottom: 8px; line-height: 1.6; font-size: 9px; color: #1a1a1a; }
            .activity-location { color: #666; font-style: italic; font-size: 8px; }
            .activity-number { font-weight: bold; color: #2c5aa0; margin-right: 5px; font-size: 9px; }
            .entrance-badge { background-color: #fff3e0; color: #e65100; padding: 2px 6px; font-size: 6px; margin-left: 5px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
            .time-highlight { background-color: #e3f2fd; color: #0d47a1; padding: 2px 6px; font-weight: bold; font-size: 8px; }
            .footer-note { text-align: center; margin-top: 20px; padding-top: 15px; color: #666; font-size: 8px; }
            .logo { text-align: center; margin-bottom: 15px; padding: 15px; background: white; }
        </style>';

        // Logo
        $logoPath = FCPATH . 'assets/images/Logo-new.png';
        if (file_exists($logoPath)) {
            $html .= '<div class="logo"><img src="' . $logoPath . '" width="120"></div>';
        }

        $html .= '<h2>Tour Itinerary</h2>';

        // Tour details in table format (better for PDF)
        if (!empty($consignee_details)) {
            $detail = $consignee_details[0];

            $html .= '<table cellpadding="0" cellspacing="3" style="width: 100%; margin-bottom: 15px;">';

            // Row 1
            $html .= '<tr>';
            $html .= '<td class="info-item" style="width: 50%;"><div class="info-label">GUEST NAME</div><div class="info-value">' . htmlspecialchars($detail['consignee'] ?? 'N/A') . '</div></td>';
            $html .= '<td class="info-item" style="width: 50%;"><div class="info-label">NO OF PAX</div><div class="info-value">' . ($detail['passenger_count'] ?? 'N/A') . ' pax</div></td>';
            $html .= '</tr>';

            // Row 2
            $html .= '<tr>';
            $html .= '<td class="info-item"><div class="info-label">TOUR START</div><div class="info-value">' . date('d M Y', strtotime($detail['start_date'])) . '</div></td>';
            $html .= '<td class="info-item"><div class="info-label">TOUR END</div><div class="info-value">' . date('d M Y', strtotime($detail['end_date'])) . '</div></td>';
            $html .= '</tr>';

            // Row 3
            $html .= '<tr>';
            $html .= '<td class="info-item"><div class="info-label">VEHICLE TYPE</div><div class="info-value">' . ($detail['vehicle_name'] ?? 'N/A') . '</div></td>';
            $html .= '<td class="info-item"><div class="info-label">CHAUFFEUR</div><div class="info-value">' . ($detail['transport_agent_name'] ?? 'N/A') . '</div></td>';
            $html .= '</tr>';

            if (!empty($detail['arrival_flight']) || !empty($detail['departure_flight'])) {
                $html .= '<tr>';
                if (!empty($detail['arrival_flight'])) {
                    $html .= '<td class="info-item"><div class="info-label">ARRIVAL FLIGHT</div><div class="info-value">' . htmlspecialchars($detail['arrival_flight']) . '</div></td>';
                } else {
                    $html .= '<td style="background: transparent;"></td>';
                }

                if (!empty($detail['departure_flight'])) {
                    $html .= '<td class="info-item"><div class="info-label">DEPARTURE FLIGHT</div><div class="info-value">' . htmlspecialchars($detail['departure_flight']) . '</div></td>';
                } else {
                    $html .= '<td style="background: transparent;"></td>';
                }
                $html .= '</tr>';
            }

            $html .= '</table>';
        }

        // Itinerary in narrative format
        $html .= '<h3>Day-by-Day Itinerary</h3>';

        // Group itinerary by tour_date
        $groupedItinerary = [];
        if (!empty($itinerary)) {
            foreach ($itinerary as $detail) {
                if (isset($detail['details']) && is_array($detail['details'])) {
                    foreach ($detail['details'] as $value) {
                        $groupedItinerary[$value['tour_date']][] = $value;
                    }
                }
            }
        }

        // Sort by date
        ksort($groupedItinerary);

        if (empty($groupedItinerary)) {
            $html .= '<p style="text-align: center; color: #999;">No itinerary details available</p>';
        } else {
            $dayCounter = 1;
            foreach ($groupedItinerary as $tour_date => $details) {
                $dateFormatted = date('d M Y (D)', strtotime($tour_date));

                $html .= '<div class="day-card">';
                $html .= '<div class="day-header">Day ' . $dayCounter . ') ' . $dateFormatted . '</div>';

                // Display hotel accommodation if available
                if (isset($hotels[$tour_date]) && !empty($hotels[$tour_date])) {
                    $html .= '<div class="accommodation-section">';
                    $html .= '<strong style="font-size: 7px; text-transform: uppercase; color: #2c5aa0; letter-spacing: 0.8px;">ACCOMMODATION:</strong> ';
                    $html .= '<span class="hotel-name-pdf">' . htmlspecialchars($hotels[$tour_date]) . '</span>';
                    $html .= '</div>';
                }

                // Collect meals and separate activities
                $dayMeals = [];
                $regularActivities = [];
                $entranceActivities = [];

                foreach ($details as $item) {
                    $description = trim($item['description'] ?? '');
                    $area = trim($item['area'] ?? '');
                    $bf = trim($item['bf'] ?? '');
                    $lunch = trim($item['lunch'] ?? '');
                    $dinner = trim($item['dinner'] ?? '');

                    // Collect meal info
                    if (!empty($bf)) $dayMeals['BF'] = $bf;
                    if (!empty($lunch)) $dayMeals['Lunch'] = $lunch;
                    if (!empty($dinner)) $dayMeals['Dinner'] = $dinner;

                    // Categorize activity
                    $desc_lower = strtolower($description);

                    // Skip breakfast entries
                    $isBreakfast = stripos($desc_lower, 'breakfast') !== false;
                    if ($isBreakfast) {
                        continue;
                    }

                    // Check if entrance/sightseeing
                    $isEntrance = (stripos($desc_lower, 'entrance') !== false || preg_match('/\b(ENT|SIG|DAM|KDY|MIN|SPG)\b/i', $description));

                    // Separate entrance activities from regular activities
                    if ($isEntrance) {
                        $entranceActivities[] = [
                            'description' => $description,
                            'area' => $area
                        ];
                    } else {
                        $regularActivities[] = [
                            'description' => $description,
                            'area' => $area
                        ];
                    }
                }

                // Three column layout
                $html .= '<div class="day-content"><table><tr>';

                // Left column: Regular activities
                $html .= '<td><div class="column-title">Itinerary</div>';
                $activityNumber = 1;
                foreach ($regularActivities as $activity) {
                    $description = $highlightTimes($activity['description']);
                    $area = !empty($activity['area']) ? $highlightTimes($activity['area']) : '';

                    // All activities displayed uniformly (hotels shown in separate section above)
                    $html .= '<div class="activity-item"><span class="activity-number">' . $activityNumber . '.</span> ' . $description;
                    if (!empty($area) && $activity['area'] !== $activity['description'] && stripos($activity['description'], $activity['area']) === false) {
                        $html .= ' <span class="activity-location">(' . $area . ')</span>';
                    }
                    $html .= '</div>';
                    $activityNumber++;
                }
                if (empty($regularActivities)) {
                    $html .= '<p style="color: #999; font-size: 8px; font-style: italic;">No activities</p>';
                }
                $html .= '</td>';

                // Middle column: Entrances
                $html .= '<td><div class="column-title">Entrances & Sightseeing</div>';
                foreach ($entranceActivities as $activity) {
                    $description = $highlightTimes($activity['description']);
                    $area = !empty($activity['area']) ? $highlightTimes($activity['area']) : '';

                    $html .= '<div class="activity-item">• ' . $description;
                    if (!empty($area) && $activity['area'] !== $activity['description'] && stripos($activity['description'], $activity['area']) === false) {
                        $html .= ' <span class="activity-location">(' . $area . ')</span>';
                    }
                    $html .= ' <span class="entrance-badge">Entrance</span></div>';
                }
                if (empty($entranceActivities)) {
                    $html .= '<p style="color: #999; font-size: 8px; font-style: italic;">No entrances</p>';
                }
                $html .= '</td>';

                // Right column: Meals
                $html .= '<td><div class="column-title">Meals</div>';
                $mealNames = [
                    'BF' => 'Breakfast',
                    'Lunch' => 'Lunch',
                    'Dinner' => 'Dinner'
                ];

                if (!empty($dayMeals)) {
                    foreach ($dayMeals as $mealType => $mealValue) {
                        $displayName = $mealNames[$mealType] ?? $mealType;

                        // Expand HTL to "at Hotel"
                        $mealLocation = (strtoupper($mealValue) === 'HTL') ? 'at Hotel' : htmlspecialchars($mealValue);

                        $html .= '<div class="meal-item">';
                        $html .= '<div class="meal-type">' . $displayName . '</div>';
                        $html .= '<div class="meal-location">' . $mealLocation . '</div>';
                        $html .= '</div>';
                    }
                } else {
                    $html .= '<p style="color: #999; font-size: 7px; font-style: italic;">No meals</p>';
                }
                $html .= '</td>';

                $html .= '</tr></table></div>';

                $html .= '</div>';
                $dayCounter++;
            }
        }

        // Footer
        $html .= '<div class="footer-note">';
        $html .= '<p>This is a secure tour itinerary provided by Avenir Holidays<br/>For any inquiries, please contact your tour coordinator</p>';
        $html .= '</div>';

        return $html;
    }

    public function sendSms($to, $message, $type, $tour_id = null, $token = null, $expires_at = null)
    {
        // Get SMS credentials from environment variables for security
        $smsSenderId = getenv('SMS_SENDER_ID') ?: 'AVENIR HLDS';
        $smsEmail = getenv('SMS_EMAIL') ?: 'info@avenirholidays.com';
        $smsPassword = getenv('SMS_PASSWORD') ?: 'Ave66556';

        $client = \Config\Services::curlrequest();
        $response = $client->get('https://smsserver.textorigins.com/Send_sms', [
            'query' => [
                'src' => $smsSenderId,
                'email' => $smsEmail,
                'pwd' => $smsPassword,
                'msg' => $message,
                'dst' => '0' . $to,
            ],
            'verify' => false
        ]);

        $responseBody = $response->getBody();

        $db = \Config\Database::connect();
        $smsLogModel = $db->table('sms_log');

        // if ($responseBody['status'] == 1601) {
        if (strpos($responseBody, '1601') !== false) {
            $smsLogModel->insert([
                'to' => $to,
                'message' => $message,
                'type' => $type,
                'status' => 'SUCCESS',
                'system' => 'BACKEND',
                'time' => date('Y-m-d H:i:s'),
                'response' => json_encode($responseBody),
                'token' => $token,
                'tour_id' => $tour_id,
                'expires_at' => $expires_at
            ]);
            return true;
        }

        $smsLogModel->insert([
            'to' => $to,
            'message' => $message,
            'type' => $type,
            'status' => 'FAILED',
            'system' => 'BACKEND',
            'time' => date('Y-m-d H:i:s'),
            'response' => json_encode($responseBody),
            'token' => $token,
            'tour_id' => $tour_id,
            'expires_at' => $expires_at
        ]);

        return false;
    }

}
