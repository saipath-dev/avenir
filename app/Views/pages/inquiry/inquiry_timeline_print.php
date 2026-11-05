<?= view('layouts/head') ?>

<body>
    <div id="layout-wrapper">
        <?= view('layouts/header') ?>

        <style>
            /* Clean Professional Print Styles */
            @media print {
                @page {
                    size: A4 landscape;
                    margin: 12mm;
                }

                body {
                    margin: 0;
                    padding: 0;
                }

                .no-print {
                    display: none !important;
                }

                .main-content {
                    width: 100%;
                }
            }

            /* Typography */
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #333;
            }

            /* Header */
            .print-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 15px 0;
                border-bottom: 2px solid #2C5F8D;
                margin-bottom: 20px;
            }

            .print-header h1 {
                font-size: 24px;
                font-weight: 600;
                color: #2C5F8D;
                margin: 0;
                letter-spacing: 1px;
            }

            .print-header img {
                height: 60px;
            }

            /* Print Button */
            .btn-print {
                background-color: #2C5F8D;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: background-color 0.3s;
            }

            .btn-print:hover {
                background-color: #1e4467;
            }

            /* Information Table */
            .info-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
                background: white;
                border: 1px solid #ddd;
            }

            .info-table th {
                background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
                vertical-align: top;
                font-weight: normal;
            }

            .info-table .label {
                font-size: 9px;
                color: #666;
                font-weight: 600;
                text-transform: uppercase;
                display: block;
                margin-bottom: 4px;
                letter-spacing: 0.3px;
            }

            .info-table .value {
                font-size: 11px;
                color: #333;
                font-weight: 500;
            }

            .info-table .value code {
                background-color: #e8f4f8;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                color: #2C5F8D;
            }

            /* Timeline Table */
            .timeline-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
                font-size: 10px;
            }

            .timeline-table thead {
                background-color: #2C5F8D;
                color: white;
            }

            .timeline-table thead th {
                padding: 10px 6px;
                text-align: center;
                font-weight: 600;
                font-size: 10px;
                border: 1px solid #1e4467;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }

            .timeline-table tbody tr:nth-child(odd) {
                background-color: #f8f9fa;
            }

            .timeline-table tbody tr:nth-child(even) {
                background-color: white;
            }

            .timeline-table tbody td {
                padding: 8px 6px;
                border: 1px solid #ddd;
                vertical-align: top;
                line-height: 1.4;
            }

            .timeline-table tbody td.center {
                text-align: center;
            }

            .timeline-table .hotel-name {
                font-weight: 600;
                color: #333;
            }

            .timeline-table .hotel-category {
                font-size: 9px;
                color: #457900;
                margin-top: 2px;
            }

            /* Status Styling */
            .status-confirmed {
                color: #155724;
                font-weight: 600;
            }

            .status-pending {
                color: #856404;
                font-weight: 600;
            }

            /* Routes and Activities */
            .route-label,
            .activity-label {
                font-weight: 600;
                font-size: 9px;
                color: #666;
                text-transform: uppercase;
                margin-top: 4px;
                margin-bottom: 2px;
            }

            .route-item,
            .activity-item {
                font-size: 9px;
                line-height: 1.5;
                margin: 1px 0;
            }

            /* Summary Row */
            .summary-row {
                background: linear-gradient(to right, #2C5F8D, #4A90E2);
                color: white;
                font-weight: 700;
            }

            .summary-row td {
                border: 2px solid #1e4467 !important;
                padding: 10px 6px !important;
                color: white !important;
            }

            /* Passengers Section */
            .passengers-section {
                margin-top: 15px;
                padding: 15px;
                background-color: #f8f9fa;
                border: 1px solid #ddd;
            }

            .passengers-section h5 {
                font-size: 14px;
                font-weight: 600;
                color: #2C5F8D;
                margin-bottom: 10px;
                padding-bottom: 6px;
                border-bottom: 1px solid #2C5F8D;
            }

            .passengers-table {
                width: 100%;
                background-color: white;
                border-collapse: collapse;
            }

            .passengers-table thead {
                background-color: #e8f4f8;
            }

            .passengers-table thead th {
                padding: 8px;
                text-align: left;
                font-size: 10px;
                font-weight: 600;
                color: #2C5F8D;
                border: 1px solid #ddd;
            }

            .passengers-table tbody td {
                padding: 6px 8px;
                font-size: 10px;
                border: 1px solid #ddd;
            }
        </style>

        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col">
                            <div class="h-100">
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <!-- Print Header -->
                                                <div class="print-header">
                                                    <h1>INQUIRY QUOTATION</h1>
                                                    <img src="<?= base_url() ?>assets/images/Logo-new.png" alt="Logo">
                                                    <button class="btn-print no-print" onclick="window.print()">Print</button>
                                                </div>

                                                <?php
                                                $consignee = [];
                                                foreach ($consignee_details as $detail) {
                                                    $consignee = $detail;
                                                }
                                                ?>

                                                <!-- Information Table -->
                                                <table class="info-table">
                                                    <tr>
                                                        <th>
                                                            <span class="label">Inquiry Reference</span>
                                                            <span class="value"><code>INQ-<?= $consignee['inquiry_id'] ?? '' ?></code></span>
                                                        </th>
                                                        <th>
                                                            <span class="label">No of Passengers</span>
                                                            <span class="value"><?= $consignee['passenger_count'] ?? '0' ?> Pax</span>
                                                        </th>
                                                        <th>
                                                            <span class="label">Client Name / Agent</span>
                                                            <span class="value"><?= $consignee['consignee'] ?? '' ?> / <?= $consignee['agent_name'] ?? '' ?></span>
                                                        </th>
                                                        <th>
                                                            <span class="label">Chauffeur / Guide</span>
                                                            <span class="value"><?= $consignee['transport_agent_name'] ?? 'TBA' ?> / <?= $consignee['guide_name'] ?? 'TBA' ?></span>
                                                        </th>
                                                    </tr>
                                                    <tr>
                                                        <th>
                                                            <span class="label">Arrival Flight & Time</span>
                                                            <span class="value"><?= $consignee['arrival_flight'] ?? 'TBA' ?> - <?= $consignee['arrival_flight_time'] ?? 'TBA' ?></span>
                                                        </th>
                                                        <th>
                                                            <span class="label">Departure Flight & Time</span>
                                                            <span class="value"><?= $consignee['departure_flight'] ?? 'TBA' ?> - <?= $consignee['departure_flight_time'] ?? 'TBA' ?></span>
                                                        </th>
                                                        <th colspan="2">
                                                            <span class="label">Vehicle Type</span>
                                                            <span class="value"><?= $consignee['vehicle_name'] ?? 'TBA' ?></span>
                                                        </th>
                                                    </tr>
                                                </table>

                                                <!-- Timeline Table -->
                                                <table class="timeline-table">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 35px;">Day</th>
                                                            <th style="width: 85px;">Date</th>
                                                            <th style="width: 170px;">Hotel / Category</th>
                                                            <th style="width: 90px;">Room Type</th>
                                                            <th style="width: 60px;">Meal</th>
                                                            <th style="width: 75px;">Status</th>
                                                            <th>Routes / Activities</th>
                                                            <th style="width: 55px;">KM</th>
                                                            <th style="width: 55px;">Extra</th>
                                                            <th style="width: 140px;">Remarks</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $day_num = 1;
                                                        $total_km = 0;
                                                        $total_extra_km = 0;

                                                        foreach ($timeline_details as $row) {
                                                            $meal = match (strtolower($row['meal_code'] ?? '')) {
                                                                'fb' => 'FB',
                                                                'hb' => 'HB',
                                                                'bb' => 'BB',
                                                                'ro' => 'RO',
                                                                default => '-'
                                                            };

                                                            $total_km += $row['distance'] ?? 0;
                                                            $total_extra_km += $row['extra_kilo'] ?? 0;
                                                        ?>
                                                            <tr>
                                                                <td class="center"><strong><?= $day_num++ ?></strong></td>
                                                                <td class="center"><?= date('M d, Y', strtotime($row['inquiry_date'])) ?></td>
                                                                <td>
                                                                    <div class="hotel-name"><?= $row['hotel_name'] ?? 'TBA' ?></div>
                                                                    <div class="hotel-category"><?= $row['rtype'] ?? '' ?></div>
                                                                </td>
                                                                <td class="center"><?= $row['rtype'] ?? '-' ?></td>
                                                                <td class="center"><strong><?= $meal ?></strong></td>
                                                                <td class="center">
                                                                    <span class="<?= $row['saved'] == 1 ? 'status-confirmed' : 'status-pending' ?>">
                                                                        <?= $row['saved'] == 1 ? 'Confirmed' : 'Pending' ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    // Routes
                                                                    $routes = [];
                                                                    foreach ($sub_locations as $subloc) {
                                                                        if ($subloc['date_id'] == $row['inquiry_date']) {
                                                                            $routes[] = $subloc;
                                                                        }
                                                                    }
                                                                    if (!empty($routes)): ?>
                                                                        <div class="route-label">Routes:</div>
                                                                        <?php foreach ($routes as $route): ?>
                                                                            <div class="route-item"><?= $route['from'] ?> - <?= $route['to'] ?></div>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>

                                                                    <?php
                                                                    // Activities
                                                                    $activities = [];
                                                                    foreach ($activity_details as $activity) {
                                                                        if ($activity['inquiry_date'] == $row['inquiry_date']) {
                                                                            $activities[] = $activity;
                                                                        }
                                                                    }
                                                                    if (!empty($activities)): ?>
                                                                        <div class="activity-label">Activities:</div>
                                                                        <?php foreach ($activities as $activity): ?>
                                                                            <div class="activity-item"><?= $activity['location_name'] ?? $activity['name'] ?></div>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="center"><?= number_format($row['distance'] ?? 0, 1) ?></td>
                                                                <td class="center"><?= number_format($row['extra_kilo'] ?? 0, 1) ?></td>
                                                                <td>
                                                                    <?php
                                                                    if (isset($itinerary[$row['inquiry_date']])) {
                                                                        $itin_items = [];
                                                                        foreach ($itinerary[$row['inquiry_date']]['details'] as $itin) {
                                                                            if (!empty($itin['description'])) {
                                                                                $itin_items[] = $itin['description'];
                                                                            }
                                                                        }
                                                                        echo implode('<br>', $itin_items);
                                                                    }
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>

                                                        <!-- Summary Row -->
                                                        <tr class="summary-row">
                                                            <td colspan="7" style="text-align: right;">TOTAL DISTANCE:</td>
                                                            <td class="center"><?= number_format($total_km, 1) ?></td>
                                                            <td class="center"><?= number_format($total_extra_km, 1) ?></td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                </table>

                                                <!-- Passenger Details -->
                                                <?php if (!empty($passengers)): ?>
                                                    <div class="passengers-section">
                                                        <h5>Passenger Details</h5>
                                                        <table class="passengers-table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 50px;">#</th>
                                                                    <th>Passenger Name</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $pax_num = 1;
                                                                foreach ($passengers as $passenger):
                                                                ?>
                                                                    <tr>
                                                                        <td class="center"><?= $pax_num++ ?></td>
                                                                        <td><?= $passenger['pass_name'] ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= view('layouts/brand') ?>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

</body>
</html>
