<?= view('layouts/head') ?>

<body>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Calibri:wght@400;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Cambria, sans-serif;
            font-size: 10pt;
            color: #111;
            background: #d0d0d0;
        }

        /* ── PAGE SHELL ── */
        .page {
            width: 794px;
            min-height: 1123px;
            margin: 32px auto;
            padding: 48px 56px 56px 56px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .18);
        }

        /* ── HEADER ── */
        .hdr {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 22px;
        }

        .hdr-logo img {
            height: 80px;
            align-items: left;
            margin-right: 400px;
            margin-bottom: 10px;
        }

        .hdr-main-title {
            font-size: 20pt;
            font-weight: 600;
            color: #af1e73ff;
            letter-spacing: 1px;
            text-align: center;
            line-height: 1.25;
        }

        .hdr-sub-title {
            font-size: 10pt;
            color: #444;
            font-weight: 580;
            font-style: italic;
            margin-top: 4px;
            text-align: center;
        }

        /* ── TOP DIVIDER ── */
        .top-divider {
            border: none;
            border-top: 2px solid #1a3c5e;
            margin: 14px 0 18px 0;
        }

        /* ── DOC LABEL (TOUR ITINERARY) ── */
        .doc-label {
            font-size: 13pt;
            font-weight: 700;
            color: #1a5276;
            margin-bottom: 8px;
        }

        /* ── BOOKING REF ROW ── */
        .ref-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
            font-size: 11pt;
            margin-bottom: 6px;
        }

        .ref-row .lbl {
            font-weight: 700;
            white-space: nowrap;
        }

        .ref-row .uline {
            flex: 1;
            /* border-bottom: 1.2px solid #333; */
            min-width: 220px;
            height: 18px;
        }

        /* ── TOUR TYPE ROW ── */
        .tour-type-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11pt;
            margin-bottom: 22px;
        }

        .tour-type-row .lbl {
            font-weight: 700;
        }

        .chk-group {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-left: 6px;
        }

        .chk-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 10.5pt;
        }

        .chk-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border: 1.5px solid #333;
            font-size: 9pt;
            font-weight: 700;
            color: #1a3c5e;
            flex-shrink: 0;
        }

        /* ── SECTION HEADER (blue bold title style, like PDF) ── */
        .sec-hdr {
            font-size: 11.5pt;
            font-weight: 700;
            color: #1a5276;
            padding-bottom: 3px;
            /* border-bottom: 1.8px solid #1a5276; */
            margin-top: 16px;
            margin-bottom: 0;
            letter-spacing: 0.3px;
        }

        /* ── GENERIC INFO TABLE ── */
        .info-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            margin-top: 1px;
        }

        .info-tbl td {
            border: 1px solid #bbb;
            padding: 5px 9px;
            vertical-align: middle;
        }

        .info-tbl td.lbl {
            background: #dce9f5;
            font-weight: 700;
            color: #1a3c5e;
            width: 30%;
            white-space: nowrap;
        }

        /* ── FLIGHT TABLE ── */
        .flight-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            margin-top: 1px;
        }

        .flight-tbl th {
            background: #1a5276;
            color: #fff;
            padding: 5px 9px;
            font-size: 10pt;
            font-weight: 700;
            border: 1px solid #1a5276;
            text-align: left;
        }

        .flight-tbl td {
            border: 1px solid #bbb;
            padding: 5px 9px;
            vertical-align: middle;
        }

        .flight-tbl td.lbl {
            background: #dce9f5;
            font-weight: 700;
            color: #1a3c5e;
            width: 20%;
            white-space: nowrap;
        }

        /* airport sub-row */
        .airport-row {
            display: flex;
            gap: 40px;
            font-size: 10.5pt;
            margin-top: 6px;
            padding: 5px 0;
        }

        .airport-row .ap-item {
            display: flex;
            align-items: baseline;
            gap: 6px;
            flex: 1;
        }

        .airport-row .ap-lbl {
            font-weight: 700;
            color: #1a3c5e;
            white-space: nowrap;
        }

        .airport-row .ap-val {
            flex: 1;
            /* border-bottom: 1px solid #555; */
            min-width: 120px;
            height: 18px;
        }

        /* ── STAFF TABLE ── */
        .staff-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            margin-top: 1px;
        }

        .staff-tbl th {
            background: #1a5276;
            color: #fff;
            padding: 5px 9px;
            font-size: 10pt;
            font-weight: 700;
            border: 1px solid #1a5276;
            text-align: left;
        }

        .staff-tbl td {
            border: 1px solid #bbb;
            padding: 5px 9px;
            vertical-align: middle;
        }

        .staff-tbl td.lbl {
            background: #dce9f5;
            font-weight: 700;
            color: #1a3c5e;
            width: 30%;
        }

        /* ── HOTEL TABLE ── */
        .hotel-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5pt;
            margin-top: 1px;
        }

        .hotel-tbl th {
            background: #1a5276;
            color: #fff;
            padding: 5px 9px;
            font-size: 10pt;
            font-weight: 700;
            border: 1px solid #1a5276;
            text-align: left;
        }

        .hotel-tbl td {
            border: 1px solid #bbb;
            padding: 5px 9px;
            vertical-align: middle;
        }

        .hotel-tbl td.day-lbl {
            background: #dce9f5;
            font-weight: 700;
            color: #1a3c5e;
            width: 14%;
            text-align: center;
        }

        /* ── ITINERARY ── */
        .itin-section-hdr {
            font-size: 11.5pt;
            font-weight: 700;
            color: #1a5276;
            padding-bottom: 3px;
            border-bottom: 1.8px solid #1a5276;
            margin-top: 20px;
            margin-bottom: 10px;
            letter-spacing: 0.3px;
        }

        .itin-block {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        .itin-day-hdr {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11pt;
            font-weight: 700;
            color: #1a3c5e;
            background: #dce9f5;
            padding: 5px 10px;
            border-left: 4px solid #1a5276;
            border-top: 1px solid #bbb;
            border-right: 1px solid #bbb;
            border-bottom: 1px solid #bbb;
            margin-bottom: 0;
        }

        .itin-day-hdr .day-date {
            font-weight: 400;
            font-size: 9.5pt;
            color: #555;
            margin-left: 8px;
        }

        /* meal badges */
        .meal-badges {
            display: flex;
            gap: 4px;
        }

        .meal-badge {
            font-size: 8pt;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 2px;
            color: #fff;
            background: #1a5276;
            letter-spacing: 0.5px;
        }

        .meal-badge.off {
            background: #bbb;
        }

        .itin-body {
            border: 1px solid #bbb;
            border-top: none;
            padding: 8px 14px 10px 14px;
            font-size: 10.5pt;
            line-height: 1.75;
        }

        .itin-body ul {
            margin: 2px 0 4px 20px;
        }

        .itin-body ul li {
            list-style: disc;
        }

        .places-label {
            font-weight: 700;
            color: #1a3c5e;
            display: block;
            margin-top: 6px;
        }

        .place-uline {
            display: block;
            border-bottom: 1px solid #555;
            min-width: 260px;
            width: 70%;
            height: 18px;
            margin: 2px 0 2px 20px;
        }

        .overnight-line {
            margin-top: 8px;
            font-size: 10.5pt;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .overnight-line .o-lbl {
            font-weight: 700;
            color: #c0392b;
            white-space: nowrap;
        }

        .overnight-line .o-hotel-lbl {
            font-weight: 700;
            white-space: nowrap;
        }

        .overnight-line .o-val {
            flex: 1;
            /* border-bottom: 1px solid #555; */
            min-width: 160px;
            height: 18px;
        }

        /* ── NOTES ── */
        .notes-block {
            border: 1px solid #bbb;
            padding: 8px 14px;
            font-size: 10.5pt;
            line-height: 1.8;
            margin-top: 1px;
        }

        .notes-block li {
            margin-left: 18px;
            list-style: disc;
        }

        /* ── CONTACT ── */
        .contact-block {
            border: 1px solid #bbb;
            padding: 8px 14px;
            font-size: 10.5pt;
            line-height: 1.9;
            margin-top: 1px;
        }

        .contact-block .co-name {
            font-size: 12pt;
            font-weight: 700;
            color: #1a5276;
        }

        /* ── PRINT / NO-PRINT ── */
        .no-print {
            display: block;
        }

        .btn-row {
            text-align: right;
            margin-bottom: 14px;
        }

        #layout-wrapper,
        .right-bar,
        #themeSettings,
        .theme-setting-toggle,
        .vertical-menu,
        .navbar-header,
        footer.footer,
        .page-title-box {
            display: none !important;
        }

        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 14mm;
            }

            body {
                font-size: 10pt;
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .page {
                margin: 0;
                padding: 0;
                width: 100%;
                box-shadow: none;
            }

            .itin-block {
                page-break-inside: avoid;
            }
        }
    </style>

    <div class="page">

        <?php
        $consignee_details ??= [];
        $invoice_details   ??= [];
        $itinerary         ??= [];
        $tour_details      ??= [];
        $ai_itinerary      ??= [];

        $d = !empty($consignee_details) ? $consignee_details[0] : [];

        $nights = 0;
        if (!empty($d['start_date']) && !empty($d['end_date'])) {
            $nights = (int) round((strtotime($d['end_date']) - strtotime($d['start_date'])) / 86400);
        }

        $arr_str  = trim($d['arrival_flight'] ?? '');
        $dep_str  = trim($d['departure_flight'] ?? '');
        $arr_time = !empty($d['arrival_flight_time'])   ? date('H:i', strtotime($d['arrival_flight_time']))   : '';
        $dep_time = !empty($d['departure_flight_time']) ? date('H:i', strtotime($d['departure_flight_time'])) : '';
        $arr_parts = preg_split('/\s+/', $arr_str);
        $dep_parts = preg_split('/\s+/', $dep_str);
        $arr_flight_no   = isset($arr_parts[0], $arr_parts[1]) ? $arr_parts[0] . ' ' . $arr_parts[1] : $arr_str;
        $arr_flight_date = $arr_parts[3] ?? '';
        $arr_airport_to  = $arr_parts[5] ?? '';
        $dep_flight_no   = isset($dep_parts[0], $dep_parts[1]) ? $dep_parts[0] . ' ' . $dep_parts[1] : $dep_str;
        $dep_flight_date = $dep_parts[3] ?? '';
        $dep_airport_to  = $dep_parts[5] ?? '';

        // Convert country code to full country name
        $country_code = $d['country'] ?? '';
        $nationality = ($country_code === 'JP') ? 'Japan' : ($country_code ?: 'Japanese');
        ?>

        <!-- BACK / PRINT BUTTONS -->
        <div class="btn-row no-print">
            <button class="btn btn-sm btn-outline-secondary" onclick="window.location.href='<?= base_url() ?>dashboard2/tiles'">
                <i class="bi bi-arrow-left"></i> Back
            </button>
            &nbsp;
            <button class="btn btn-sm btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print
            </button>
        </div>

        <!-- ═══ HEADER ═══ -->
        <div class="hdr">
            <div class="hdr-logo">
                <img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="Avenir Holidays">
            </div>
            <div class="hdr-main-title">SRI LANKAN TOUR ITINERARY</div>
            <div class="hdr-sub-title">(For Japanese Market)</div>
        </div>



        <!-- DOC LABEL -->
        <div class="doc-label">TOUR ITINERARY</div>

        <!-- BOOKING REFERENCE -->
        <div class="ref-row">
            <span class="lbl">Booking Reference :</span>
            <span class="uline"><?= htmlspecialchars($d['manual_id']) ?></span>
        </div>

        <!-- TOUR CODE -->

        <div class="ref-row">
            <span class="lbl">Tour Code :</span>
            <span class="uline"><?= htmlspecialchars($d['tour_code']) ?></span>
        </div>


        <!-- TOUR TYPE -->
        <div class="tour-type-row">
            <span class="lbl">Tour Type :</span>
           <?= $nights ?> Night<?= $nights != 1 ? 's' : '' ?> / <?= $nights + 1 ?> Day<?= ($nights + 1) != 1 ? 's' : '' ?>
        </div>

        <!-- ═══ CLIENT INFORMATION ═══ -->
        <div class="sec-hdr">CLIENT INFORMATION</div>
        <table class="info-tbl">
            <tr>
                <td class="lbl">Client Name</td>
                <td colspan="3"><?= htmlspecialchars($d['consignee'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="lbl">Number of Passengers</td>
                <td style="width:20%;"><?= htmlspecialchars($d['passenger_count'] ?? '') ?></td>
                <td class="lbl" style="width:22%;">Nationality</td>
                <td><?= htmlspecialchars($nationality) ?></td>
            </tr>
            <tr>
                <td class="lbl">Tour Coordinator</td>
                <td><?= htmlspecialchars($d['create_user'] ?? '') ?></td>
                <td class="lbl">Emergency Contact</td>
                <td><?= htmlspecialchars($d['mobile'] ?? '') ?></td>
            </tr>
        </table>

        <!-- ═══ FLIGHT DETAILS ═══ -->
        <div class="sec-hdr">FLIGHT DETAILS</div>
        <table class="flight-tbl">
            <thead>
                <tr>
                    <th style="width:20%;">Sector</th>
                    <th style="width:25%;">Flight No</th>
                    <th style="width:25%;">Date</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="lbl">Arrival Flight</td>
                    <td><?= htmlspecialchars($arr_flight_no) ?></td>
                    <td><?= htmlspecialchars($arr_flight_date) ?></td>
                    <td><?= htmlspecialchars($arr_time) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Departure Flight</td>
                    <td><?= htmlspecialchars($dep_flight_no) ?></td>
                    <td><?= htmlspecialchars($dep_flight_date) ?></td>
                    <td><?= htmlspecialchars($dep_time) ?></td>
                </tr>
            </tbody>
        </table>
        <div class="airport-row">
            <div class="ap-item">
                <span class="ap-lbl">Arrival Airport :</span>
                <span class="ap-val">&nbsp;<?= htmlspecialchars($arr_airport_to) ?></span>
            </div>
            <div class="ap-item">
                <span class="ap-lbl">Departure Airport :</span>
                <span class="ap-val">&nbsp;<?= htmlspecialchars($dep_airport_to) ?></span>
            </div>
        </div>

        <!-- ═══ TOUR STAFF ═══ -->
        <div class="sec-hdr">TOUR STAFF DETAILS</div>
        <table class="staff-tbl">
            <thead>
                <tr>
                    <th style="width:25%;">Position</th>
                    <th style="width:30%;">Name</th>
                    <th style="width:23%;">Contact Number</th>
                    <th style="width:22%;">Language</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $driver_details ??= [];
                if (!empty($driver_details)):
                    foreach ($driver_details as $driver): ?>
                        <tr>
                            <td class="lbl">Chauffeur Driver</td>
                            <td><?= htmlspecialchars($driver['driver_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($driver['driver_phone'] ?? '') ?></td>
                            <td><?= htmlspecialchars($driver['driver_language'] ?? '') ?></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td class="lbl">Chauffeur Driver</td>
                        <td><?= htmlspecialchars($d['transport_agent_name'] ?? '') ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td class="lbl">National Guide</td>
                    <td><?= htmlspecialchars($d['guide_name'] ?? '') ?></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="lbl">Japanese Speaking Guide</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- ═══ HOTEL DETAILS ═══ -->
        <div class="sec-hdr">HOTEL DETAILS</div>
        <table class="hotel-tbl">
            <thead>
                <tr>
                    <th style="width:14%;">Date</th>
                    <th>Hotel Name</th>
                    <th style="width:22%;">Room Type</th>
                    <th style="width:16%;">Meal Plan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tour_details)):
                    foreach ($tour_details as $hotel):
                        if (empty($hotel['hotel_name'])) continue; ?>
                        <tr>
                            <td class="day-lbl"><?= htmlspecialchars($hotel['tour_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($hotel['hotel_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($hotel['rtype'] ?? '') ?></td>
                            <td><?= htmlspecialchars($hotel['meal_code'] ?? '') ?></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center;color:#888;padding:10px;">No hotel details available</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ═══ DETAILED TOUR ITINERARY ═══ -->
        <div class="itin-section-hdr">DETAILED TOUR ITINERARY</div>

        <?php
        $groupedItinerary = [];
        if (!empty($itinerary)) {
            foreach ($itinerary as $grp) {
                if (isset($grp['details']) && is_array($grp['details'])) {
                    foreach ($grp['details'] as $row) {
                        $groupedItinerary[$row['tour_date']][] = $row;
                    }
                }
            }
        }
        ksort($groupedItinerary, SORT_NATURAL);

        $dayNum = 1;
        $total  = count($groupedItinerary);

        foreach ($groupedItinerary as $tour_date => $details):
            $isFirst = ($dayNum === 1);
            $isLast  = ($dayNum === $total);

            if ($isLast) {
                $dayLabel = 'FINAL DAY – DEPARTURE';
            } else {
                $dayLabel = 'DAY ' . str_pad($dayNum, 2, '0', STR_PAD_LEFT);
                if ($isFirst) $dayLabel .= ' – ARRIVAL IN SRI LANKA';
            }

            // $hasBF  = !empty(array_filter(array_column($details, 'bf')));
            // $hasLUN = !empty(array_filter(array_column($details, 'lunch')));
            // $hasDIN = !empty(array_filter(array_column($details, 'dinner')));

            $overnightHotel = '';
            foreach ($tour_details as $h) {
                if (($h['tour_date'] ?? '') === $tour_date) {
                    $overnightHotel = $h['hotel_name'] ?? '';
                    break;
                }
            }
        ?>
            <div class="itin-block">
                <div class="itin-day-hdr">
                    <span>
                        <?= htmlspecialchars($dayLabel) ?>
                        <span class="day-date"><?= htmlspecialchars($tour_date) ?></span>
                    </span>

                </div>
                <div class="itin-body">
                    <?php if ($isFirst): ?>
                        <ul>
                            <li>Arrival at Bandaranaike International Airport</li>
                            <li>Meet &amp; Assist by AVENIR HOLIDAYS Representative</li>
                            <li>Introduction to Driver / Guide</li>
                            <li>Transfer to Hotel</li>
                            <li>Optional sightseeing en route</li>
                        </ul>
                    <?php endif; ?>
                    <?php if ($isLast): ?>
                        <ul>
                            <li>Transfer to Airport</li>
                            <li>Departure Assistance by AVENIR HOLIDAYS</li>
                        </ul>
                    <?php endif; ?>

                    <?php if (!$isFirst && !$isLast): ?>
                        <span class="places-label">Days' Highlights</span>
                        <ul>
                            <?php if (!empty($ai_itinerary[$tour_date])): ?>
                                <?php foreach ($ai_itinerary[$tour_date] as $bullet): ?>
                                    <li><?= htmlspecialchars($bullet) ?></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php foreach ($details as $row): ?>
                                    <?php if (!empty($row['description'])):
                                        // Replace @ with 'at' in description
                                        $description = str_replace('@', 'at', $row['description']);
                                    ?>
                                        <li><?= htmlspecialchars($description) ?><?= !empty($row['area']) ? ' – ' . htmlspecialchars($row['area']) : '' ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!$isLast): ?>
                        <div class="overnight-line">
                            <span class="o-lbl">Overnight Stay :</span>
                            <span class="o-hotel-lbl"></span>
                            <span class="o-val"><?= htmlspecialchars($overnightHotel) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php $dayNum++;
        endforeach; ?>

        <!-- ═══ IMPORTANT NOTES ═══ -->
        <div class="sec-hdr" style="margin-top:20px;">IMPORTANT NOTES</div>
        <div class="notes-block">
            <ul>
                <li>Check-in time : 14:00 hrs / Check-out time : 12:00 hrs</li>
                <li>Early check-in and late check-out subject to availability</li>
                <li>Program may change depending on weather and road conditions</li>
                <li>Clients are advised to carry passports during travel</li>
                <li>Japanese speaking guide subject to availability</li>
            </ul>
        </div>

        <!-- ═══ CONTACT DETAILS ═══ -->
        <div class="sec-hdr" style="margin-top:16px;">CONTACT DETAILS</div>
        <div class="contact-block">
            <span class="co-name">AVENIR HOLIDAYS PVT LTD</span><br>
            No. 980/4A, Level, 4 Wickramasinghe Pl, Sri Jayawardanapura Kotte<br>
            +94 77 729 5473 &nbsp;/&nbsp; +94 114 215 977/&nbsp;+94 77 583 7441 <br>
            land@avenirholidays.com &nbsp;&bull;&nbsp;traveljp@avenirholidays.com &nbsp;&bull;&nbsp;www.avenirholidays.com
        </div>

    </div><!-- /.page -->

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
</body>

</html>