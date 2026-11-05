<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Itinerary - Avenir Holidays</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            background: #FFFFFF;
            line-height: 1.6;
        }

        .document-container {
            max-width: 794px;
            
            border: 2px solid #2A3158;
            margin: 15px auto;
            background: #FFFFFF;
        }

        /* Header */
        .header {
            background: #FFFFFF;
            padding: 22px;
            margin-top: 5px;
            text-align: center;
            position: relative;

          
        }

      
.header-logo-left
 {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    height: 60px;
    max-width: 180px;
}
        .header-logo-right {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
              height: 60px;
    max-width: 180px;
        }

        .header h1 {
            font-size: 38px;
            font-weight: 800;
            color: #2A3158;
            letter-spacing: 0.01em;
            margin-bottom: 10px;
        }

        .header h2 {
            font-size: 19px;
            font-weight: 700;
            color: #2A3158;
            letter-spacing: 0.05em;
        }

        /* Meta Information Table */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #7122e7ff;
        }

        .meta-table td {
            padding: 10px 10px;
            background: #2A3158;
            border: 1px solid #C8C0D4;
        }

        .meta-label {
            background: #1C2340;
            color: #FFFFFF;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            width: 20%;
        }

        .meta-value {
            background: #F5F0F3;
            color: #2C2C3A;
            font-size: 13px;
            font-weight: 600;
        }

        .meta-value-alt {
            background: #EDE8F0;
            color: #2C2C3A;
            font-size: 13px;
            font-weight: 600;
        }

        /* Tour Details Table */
        .details-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 2px;
            margin: 10px 0;
        }

        .details-table td {
            padding: 12px 16px;
            border: none;
        }

        .details-label {
            background: #237FC0;
            color: #FFFFFF;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            width: 20%;
        }

        .details-value {
            background: #E8E8E8;
            color: #2C2C3A;
            font-size: 13px;
            font-weight: 600;
            width: 30%;
        }

        /* Day Itinerary Table */
        .itinerary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 3px;
            margin-top: 20px;
        }

        .day-row {

        }

        .day-row td {

        }

        .day-label-cell {
            background: #2A3158;
            width: 110px;
            border: 1px solid #2A3158;
            padding: 16px 12px;
            text-align: center;
            vertical-align: top;
        }

        .day-word {
            color: #FFFFFF;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            display: block;
            margin-bottom: 6px;
        }

        .day-number {
            color: #FFFFFF;
            font-size: 46px;
            font-weight: 800;
            line-height: 1;
            display: block;
            margin-bottom: 6px;
        }

        .day-date {
            color: #E6F2FA;
            font-size: 12px;
            font-weight: 600;
            display: block;
        }

        .day-activities-cell {
            background: #FFFFFF;
            padding: 0;
            border: 1px solid #0c0c0cff;
            vertical-align: top;
        }

        .hotel-info {
            background: #300c2aab;
            padding: 8px 8px;
            margin: 0;
            border-bottom: 1px solid #D4CCE0;
        }

        .hotel-label {
            font-size: 10px;
            font-weight: 700;
            color: #E6F2FA;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 2px;
            display: block;
        }

        .hotel-name {
            font-size: 13px;
            font-weight: 600;
            color: #FFFFFF;
        }

        .activities-container {
            padding: 12px 16px;
        }

        .activity-row {
            margin-bottom: 6px;
            line-height: 1.5;
        }

        .activity-row:last-child {
            margin-bottom: 0;
        }

        .star-icon {
            color: #FAAE43;
            font-size: 13px;
            margin-right: 8px;
        }

        .time-stamp {
            color: #2C2C3A;
            font-weight: 700;
            font-size: 12px;
            margin-right: 6px;
        }

        .activity-text {
            color: #0f0f0fff;
            font-weight: 400;
            font-size: 14px;
        }

        .activity-area {
            color: #718096;
            font-style: italic;
            font-size: 11px;
        }

        /* Important Information Section */
        .important-info {
            background: #F0EBF4;
            border: 1px solid #D0C8DC;
            border-left: 4px solid #237FC0;
            border-spacing: 8px 3px;
            padding: 18px 24px;
            margin-top: 2px;
        }

        .important-title {
            color: #1C2340;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 10px;
        }

        .important-list {
            list-style-type: disc;
            margin-left: 20px;
            color: #3A3A4A;
        }

        .important-list li {
            font-size: 11px;
            font-weight: 400;
            line-height: 1.6;
            margin-bottom: 6px;
        }

        /* Download Section */
        .download-section {
            padding: 20px;
            text-align: center;
            background: #FFFFFF;
        }

        .btn-download {
            background: #FAAE43;
            color: #FFFFFF;
            border: 1px solid #D4CCE0;
            padding: 12px 32px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-download:hover {
            background: #E09A32;
        }

        .btn-download svg {
            vertical-align: middle;
            margin-right: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 0;
                margin: 0;
            }

            .document-container {
                max-width: 100%;
                padding: 10px;
                 border: 2px solid #2A3158;
                margin: 0;
            }

            .header {
                padding: 16px 8px;
            margin-bottom:10px;
                 border: 2px solid #2A3158;
                 /* border-right: none;
                                  border-left: none; */
              
            }

            .header h1 {
                font-size: 18px;
                margin: 0;
                padding: 0 80px;
            }

            .header h2 {
                font-size: 14px;
            }

            .header-logo-left,
            .header-logo-right {
                height: 28px;
                max-width: 70px;
            }

            .header-logo-left {
                left: 8px;
            }

            .header-logo-right {
                right: 8px;
            }

            /* Details table - compact grid in mobile */
            .details-table {
                margin: 0;
                padding: 5px;
         
                width: 100%;
            }

            .details-table tr {
                display: table-row;
            }

            .details-table td {
                display: table-cell !important;
                padding: 6px 8px !important;
                border: 1px solid #D4CCE0 !important;
            }

            .details-label {
                background: #237FC0 !important;
                color: #FFFFFF !important;
                font-size: 8px !important;
                padding: 6px 8px !important;
                font-weight: 700 !important;
                width: 15% !important;
            }

            .details-value {
                background: #915679ff !important;
                color: #2C2C3A !important;
                font-size: 10px !important;
                padding: 6px 8px !important;
                width: 35% !important;
            }

            /* Flight row - full width */
            .details-table tr:last-child .details-label {
                width: 15% !important;
            }

            .details-table tr:last-child .details-value {
                width: 35% !important;
            }

            /* Itinerary table */
            .itinerary-table {
                border-spacing: 0;
                border: none;
                margin-top: 15px;
            }

            .day-row {
                display: block;
                padding: 15px;
                margin-bottom: 15px;
            }

            .day-label-cell {
                display: block;
                width: 100% !important;
                padding: 12px 16px;
                text-align: left;
                border: 1px solid #2A3158 !important;
                border-radius: 0;
                margin-bottom: 0;
            }

            .day-word {
                font-size: 24px;
                margin-right: 8px;
                 margin-bottom: 0px;
            }

            .day-number {
                font-size: 24px;
                margin-right: 8px;
                   margin-bottom: 0px;
            }

            .day-date {
                font-size: 18px;
                 margin-bottom: -2px;
            }

            .day-word,
            .day-number,
            .day-date {
                display: inline-block;
                vertical-align: middle;
            }

            .important-info {
    background: #F0EBF4;
    border: 1px solid #D0C8DC;
    border-spacing: 8px 3px;
    padding: 18px 24px;
    margin-top: 2px;
}

            .day-activities-cell {
                display: block;
                width: 100% !important;
             
                border-top: none !important;
                margin-top: 0;
            }

            .hotel-info {
                padding: 2px 8px;
                background: #915679ff;
            }

            .hotel-label {
                font-size: 9px;
                color: #E6F2FA;
                margin-bottom: 4px;
            }

            .hotel-name {
                font-size: 12px;
                color: #FFFFFF;
            }

            .activities-container {
                padding: 12px 16px;
            }

            .activity-row {
                margin-bottom: 10px;
                line-height: 1.6;
            }

            .star-icon {
                font-size: 11px;
                margin-right: 6px;
            }

            .time-stamp {
                font-size: 11px;
                font-weight: 700;
                margin-right: 6px;
            }

            .activity-text {
                font-size: 11px;
                line-height: 1.5;
            }

            /* Important info */
            .important-info {
                padding: 12px 16px;
                margin: 15px 0;
            }

            .important-title {
                font-size: 10px;
                margin-bottom: 8px;
            }

            .important-list {
                padding-left: 16px;
            }

            .important-list li {
                margin-bottom: 6px;
                font-size: 10px;
                line-height: 1.5;
            }

            /* Download button */
            .download-section {
                padding: 15px;
            }

            .btn-download {
                padding: 12px 20px;
                font-size: 12px;
                width: 100%;
                max-width: 250px;
            }
        }

        @media print {
            .download-section {
                display: none;
            }

            body {
                background: #FFFFFF;
                margin: 0;
                padding: 0;
            }

            .document-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: 2px solid #2A3158 !important;
            }

            /* Ensure colors print correctly */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            /* Header */
            .header {
                background: #FFFFFF !important;
                border-bottom: 2px solid #E5E7EB !important;
                page-break-inside: avoid;
            }

            /* Details Table */
            .details-table {
                border-collapse: separate !important;
                border-spacing: 0 2px !important;
                margin: 10px 0 !important;
                page-break-inside: avoid;
            }

            .details-table td {
                border: none !important;
                padding: 12px 16px !important;
            }

            .details-label {
                background: #237FC0 !important;
                color: #FFFFFF !important;
            }

            .details-value {
                background: #E8E8E8 !important;
            }

            /* Itinerary Table */
            .itinerary-table {
                border-collapse: separate !important;
                border-spacing: 8px 3px !important;
            }

            .day-row {
                page-break-inside: avoid;
            }

            .day-label-cell {
                background: #2A3158 !important;
                border: 1px solid #2A3158 !important;
            }

            .day-activities-cell {
                background: #FFFFFF !important;
                border: 1px solid #C8C0D4 !important;
            }

            .hotel-info {
                background: #2A3158 !important;
                border-bottom: 1px solid #D4CCE0 !important;
                page-break-inside: avoid;
            }

            .star-icon {
                color: #FAAE43 !important;
            }

            /* Important Info */
            .important-info {
                background: #F0EBF4 !important;
                border: 1px solid #D0C8DC !important;
                border-left: 4px solid #237FC0 !important;
                page-break-inside: avoid;
            }

            /* Preserve spacing */
            .activities-container {
                padding: 12px 16px !important;
            }

            .activity-row {
                margin-bottom: 6px !important;
            }
        }
    </style>
</head>
<body>
    <div class="document-container">
        <?php
        $consignee = [];
        $consignee_details = $consignee_details ?? [];
        $invoice_details = $invoice_details ?? [];
        $itinerary = $itinerary ?? [];
        $hotels = $hotels ?? [];

        foreach ($consignee_details as $detail) {
            $consignee = $detail;
        ?>

        <!-- Header -->
        <div class="header">
            <img src="<?= base_url('public/assets/images/Logo-new.png') ?>" alt="Avenir Holidays" class="header-logo-left">
            <h1>TOUR ITINERARY</h1>
            <img src="<?= base_url('public/assets/images/Logo-new.png') ?>" alt="Avenir Holidays" class="header-logo-right">
        </div>

        <!-- Tour Details Grid -->
        <table class="details-table">
            <!-- Row 1 -->
            <tr>
                <td class="details-label">GUEST NAME</td>
                <td class="details-value"><?= htmlspecialchars($detail['consignee']) ?></td>
                <td class="details-label">NO OF PAX</td>
                <td class="details-value"><?= $detail['passenger_count'] ?> pax</td>
            </tr>
            <tr>
                <td class="details-label">TOUR START</td>
                <td class="details-value"><?= date('d M Y', strtotime($detail['start_date'])) ?></td>
                <td class="details-label">TOUR END</td>
                <td class="details-value"><?= date('d M Y', strtotime($detail['end_date'])) ?></td>
            </tr>
            <!-- Row 2 -->
            <tr>
                <td class="details-label">VEHICLE TYPE</td>
                <td class="details-value"><?= strtoupper($detail['vehicle_name'] ?? 'N/A') ?></td>
                <td class="details-label">CHAUFFEUR</td>
                <td class="details-value"><?= $detail['transport_agent_name'] ?? 'N/A' ?></td>
            </tr>
            <!-- Row 3 - Flights -->
            <?php if (!empty($detail['arrival_flight']) || !empty($detail['departure_flight'])): ?>
            <tr>
                <td class="details-label">ARRIVAL FLIGHT</td>
                <td class="details-value"><?= !empty($detail['arrival_flight']) ? htmlspecialchars($detail['arrival_flight']) : 'N/A' ?></td>
                <td class="details-label">DEPARTURE FLIGHT</td>
                <td class="details-value"><?= !empty($detail['departure_flight']) ? htmlspecialchars($detail['departure_flight']) : 'N/A' ?></td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
        }
        ?>

        <!-- Day-by-Day Itinerary -->
        <table class="itinerary-table">
            <?php
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

            // Helper function to extract and format time
            function extractTime($text) {
                if (preg_match('/\b(\d{4})\b/', $text, $matches)) {
                    $time = $matches[1];
                    return substr($time, 0, 2) . ':' . substr($time, 2, 2);
                }
                return null;
            }

            // Helper function to remove time from description
            function removeTimeFromText($text) {
                return preg_replace('/\b\d{4}\b/', '', $text);
            }

            // Display grouped itinerary
            if (!empty($groupedItinerary)) {
                $dayCounter = 1;
                foreach ($groupedItinerary as $tour_date => $details) {
                    $dateFormatted = date('M d', strtotime($tour_date));
            ?>
                    <tr class="day-row">
                        <!-- Day Label Column -->
                        <td class="day-label-cell">
                            <span class="day-word">DAY</span>
                            <span class="day-number"><?= str_pad($dayCounter, 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="day-date"><?= $dateFormatted ?></span>
                        </td>

                        <!-- Activities Column -->
                        <td class="day-activities-cell">
                            <?php
                            // Display hotel if available
                            if (isset($hotels[$tour_date]) && !empty($hotels[$tour_date])) {
                                echo '<div class="hotel-info">';
                                echo '<div class="hotel-label">ACCOMMODATION</div>';
                                echo '<div class="hotel-name">' . htmlspecialchars($hotels[$tour_date]) . '</div>';
                                echo '</div>';
                            }

                            // Start activities container
                            echo '<div class="activities-container">';

                            // Process activities
                            foreach ($details as $item) {
                                $description = trim($item['description'] ?? '');
                                $area = trim($item['area'] ?? '');

                                if (empty($description)) continue;

                                // Skip standalone breakfast entries
                                if (stripos(strtolower($description), 'breakfast at hotel') !== false ||
                                    stripos(strtolower($description), 'breakfast at a hotel') !== false) {
                                    continue;
                                }

                                // Extract time
                                $time = extractTime($description);
                                $cleanDescription = removeTimeFromText($description);
                                $cleanDescription = trim($cleanDescription);

                                echo '<div class="activity-row">';
                                echo '<span class="star-icon">★</span>';

                                if ($time) {
                                    echo '<span class="time-stamp">' . $time . '</span>';
                                }

                                echo '<span class="activity-text">' . htmlspecialchars($cleanDescription);

                                // Add area if different and not redundant
                                if (!empty($area) && $area !== $item['description'] &&
                                    stripos($item['description'], $area) === false) {
                                    echo ' <span class="activity-area">(' . htmlspecialchars($area) . ')</span>';
                                }

                                echo '</span>';
                                echo '</div>';
                            }

                            // Close activities container
                            echo '</div>';
                            ?>
                        </td>
                    </tr>
            <?php
                    $dayCounter++;
                }
            } else {
                echo '<tr><td colspan="2" style="padding: 30px; text-align: center; color: #999;">No itinerary details available</td></tr>';
            }
            ?>
        </table>

        <!-- Important Information -->
        <!-- <div class="important-info">
            <div class="important-title">IMPORTANT INFORMATION</div>
            <ul class="important-list">
                <li><strong>Safety Reminder:</strong> Don't forget to inform guest to put seatbelt during driving</li>
                <li><strong>Emergency Contacts:</strong> Police: 119 | Fire & Ambulance: 110 | Tourist Police: +94 11 242 1451</li>
                <li><strong>Avenir Holidays:</strong> For any inquiries or assistance, contact your tour coordinator</li>
                <li><strong>Travel Insurance:</strong> Ensure your travel insurance is valid for the entire duration of your stay</li>
                <li><strong>Useful Phrases:</strong> Hello (Ayubowan) • Thank you (Bohoma Isthuthi) • Yes (Ow) • No (Nehe)</li>
                <li><strong>Must-try Dishes:</strong> Rice & Curry, Kottu Roti, Hoppers, String Hoppers, Watalappan</li>
            </ul>
        </div> -->

        <!-- Download PDF Button -->
        <div class="download-section">
            <a href="javascript:window.print()" class="btn-download">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Download PDF
            </a>
        </div>
    </div>

    <script>
        // Check if print parameter is present in URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            // Wait for page to fully load including images and fonts
            window.addEventListener('load', function() {
                // Small delay to ensure everything is rendered
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        }
    </script>
</body>
</html>
