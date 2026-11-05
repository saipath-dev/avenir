<?= view('layouts/head') ?>
<body>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Cambria&family=Calibri:wght@400;700&display=swap');

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Cambria, 'Times New Roman', serif;
        font-size: 10.5pt;
        color: #222;
        background: #fff;
    }

    .page {
        width: 794px;
        margin: 30px auto;
        padding: 36px 50px;
        background: #fff;
    }

    /* ---- HEADER ---- */
    .hdr {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .hdr-logo img { height: 70px; }
    .hdr-title {
        flex: 1;
        text-align: center;
        padding-top: 4px;
    }
    .hdr-title .main-title {
        font-size: 13pt;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: #1a3c5e;
        line-height: 1.3;
    }
    .hdr-title .sub-title {
        font-size: 9pt;
        color: #555;
        margin-top: 2px;
    }
    .hdr-title .doc-title {
        font-size: 15pt;
        font-weight: 700;
        color: #c0392b;
        letter-spacing: 3px;
        margin-top: 4px;
    }

    /* ---- BOOKING REF BAR ---- */
    .ref-bar {
        border: 1.5px solid #1a3c5e;
        padding: 5px 10px;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 16px;
        font-size: 10pt;
    }
    .ref-bar .ref-label { font-weight: 700; color: #1a3c5e; }
    .ref-bar .ref-val { flex: 1; border-bottom: 1px solid #333; min-width: 200px; }
    .tour-type-item {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 9.5pt;
        margin-right: 8px;
    }
    .chk-box {
        display: inline-block;
        width: 13px; height: 13px;
        border: 1.5px solid #333;
        text-align: center;
        line-height: 11px;
        font-size: 9pt;
        font-weight: 700;
        color: #1a3c5e;
    }

    /* ---- SECTION HEADERS ---- */
    .sec-hdr {
        background: #1a3c5e;
        color: #fff;
        font-size: 9.5pt;
        font-weight: 700;
        letter-spacing: 1.5px;
        padding: 4px 10px;
        margin-top: 8px;
        margin-bottom: 0;
    }

    /* ---- INFO TABLE ---- */
    .info-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    .info-tbl td {
        border: 1px solid #bbb;
        padding: 4px 8px;
        vertical-align: middle;
    }
    .info-tbl td.lbl {
        background: #e8eff7;
        font-weight: 700;
        color: #1a3c5e;
        width: 22%;
        white-space: nowrap;
    }
    .info-tbl td.val { width: 28%; }

    /* ---- FLIGHT TABLE ---- */
    .flight-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    .flight-tbl th {
        background: #2e6da4;
        color: #fff;
        font-size: 9pt;
        padding: 4px 8px;
        border: 1px solid #2e6da4;
        font-weight: 700;
    }
    .flight-tbl td {
        border: 1px solid #bbb;
        padding: 4px 8px;
        vertical-align: middle;
    }
    .flight-tbl td.lbl {
        background: #e8eff7;
        font-weight: 700;
        color: #1a3c5e;
        width: 18%;
    }
    .airport-row td {
        border: 1px solid #bbb;
        padding: 4px 8px;
        font-size: 10pt;
    }
    .airport-row td.lbl {
        background: #e8eff7;
        font-weight: 700;
        color: #1a3c5e;
        width: 18%;
    }

    /* ---- STAFF TABLE ---- */
    .staff-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    .staff-tbl th {
        background: #2e6da4;
        color: #fff;
        font-size: 9pt;
        padding: 4px 8px;
        border: 1px solid #2e6da4;
        font-weight: 700;
    }
    .staff-tbl td {
        border: 1px solid #bbb;
        padding: 5px 8px;
    }
    .staff-tbl td.lbl {
        background: #e8eff7;
        font-weight: 700;
        color: #1a3c5e;
        width: 30%;
    }

    /* ---- HOTEL TABLE ---- */
    .hotel-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
    }
    .hotel-tbl th {
        background: #2e6da4;
        color: #fff;
        font-size: 9pt;
        padding: 4px 8px;
        border: 1px solid #2e6da4;
        text-align: center;
        font-weight: 700;
    }
    .hotel-tbl td {
        border: 1px solid #bbb;
        padding: 4px 8px;
        text-align: center;
    }
    .hotel-tbl td.day-lbl {
        background: #e8eff7;
        font-weight: 700;
        color: #1a3c5e;
        text-align: center;
        width: 12%;
    }
    .hotel-tbl td.hotel-name { text-align: left; }

    /* ---- ITINERARY BLOCKS ---- */
    .itin-block {
        margin-top: 8px;
        page-break-inside: avoid;
    }
    .itin-day-hdr {
        background: #d6e4f0;
        color: #1a3c5e;
        font-weight: 700;
        font-size: 10.5pt;
        padding: 4px 10px;
        border-left: 4px solid #2e6da4;
        margin-bottom: 4px;
    }
    .itin-body {
        border: 1px solid #ccc;
        padding: 6px 12px 8px 12px;
        font-size: 10pt;
        line-height: 1.7;
    }
    .itin-body ul {
        margin: 2px 0 4px 18px;
        padding: 0;
    }
    .itin-body ul li { list-style: disc; }
    .itin-body .places-label {
        font-weight: 700;
        color: #1a3c5e;
        margin-top: 4px;
        display: block;
    }
    .itin-body .overnight {
        margin-top: 5px;
        font-weight: 700;
        color: #c0392b;
        font-size: 10pt;
    }
    .itin-body .overnight span {
        font-weight: 400;
        color: #222;
        border-bottom: 1px solid #333;
        display: inline-block;
        min-width: 160px;
    }
    .meal-badges {
        float: right;
        font-size: 8.5pt;
        color: #fff;
    }
    .meal-badge {
        display: inline-block;
        background: #2e6da4;
        padding: 1px 6px;
        border-radius: 2px;
        margin-left: 3px;
    }
    .meal-badge.off { background: #aaa; }

    /* ---- NOTES ---- */
    .notes-block {
        font-size: 10pt;
        line-height: 1.8;
        border: 1px solid #bbb;
        padding: 6px 12px;
    }
    .notes-block li { margin-left: 16px; list-style: disc; }

    /* ---- CONTACT ---- */
    .contact-block {
        font-size: 10pt;
        line-height: 1.8;
        border: 1px solid #bbb;
        padding: 6px 12px;
    }
    .contact-block strong { color: #1a3c5e; font-size: 11pt; }

    /* ---- NO-PRINT ---- */
    .no-print { display: block; }
    .btn-row { text-align: right; margin-bottom: 12px; }

    @media print {
        @page { size: A4 portrait; margin: 12mm 14mm; }
        body { font-size: 9.5pt; }
        .no-print { display: none !important; }
        .page { margin: 0; padding: 0; width: 100%; }
        .itin-block { page-break-inside: avoid; }
    }
</style>

<div class="page">

<?php
$consignee_details ??= [];
$invoice_details   ??= [];
$itinerary         ??= [];
$tour_details      ??= [];

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
// Format: "UL 455 Y 04JUL NRT CMB 1115 1700" → index 0=carrier, 1=flightno, 3=date, 4=from, 5=to
$arr_flight_no   = isset($arr_parts[0], $arr_parts[1]) ? $arr_parts[0].' '.$arr_parts[1] : $arr_str;
$arr_flight_date = $arr_parts[3] ?? '';
$arr_airport_to  = $arr_parts[5] ?? '';
$dep_flight_no   = isset($dep_parts[0], $dep_parts[1]) ? $dep_parts[0].' '.$dep_parts[1] : $dep_str;
$dep_flight_date = $dep_parts[3] ?? '';
$dep_airport_to  = $dep_parts[5] ?? '';
?>

<!-- Print btn -->
<div class="btn-row no-print">
    <button class="btn btn-sm btn-outline-secondary" onclick="window.history.back()"><i class="bi bi-arrow-left"></i> Back</button>
    &nbsp;
    <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<!-- ===== HEADER ===== -->
<div class="hdr">
    <div class="hdr-logo">
        <img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="Avenir Holidays">
    </div>
    <div class="hdr-title">
        <div class="main-title">SRI LANKA INBOUND TOUR ITINERARY FORMAT</div>
        <div class="sub-title">(For Japanese Tourist Handling &amp; Booking System)</div>
        <div class="doc-title">TOUR ITINERARY</div>
    </div>
</div>

<!-- Booking Reference bar -->
<div class="ref-bar">
    <span class="ref-label">Booking Reference :</span>
    <span class="ref-val">&nbsp;<?= htmlspecialchars(($d['tour_id'] ?? '') . ($d['manual_id'] ? ' – ' . $d['manual_id'] : '')) ?></span>
    <span style="margin-left:16px;font-weight:700;color:#1a3c5e;">Tour Type :</span>
    <span>
        <span class="tour-type-item"><span class="chk-box"><?= $nights == 3 ? '✓' : '' ?></span> 3 Nights / 4 Days</span>
        <span class="tour-type-item"><span class="chk-box"><?= $nights == 5 ? '✓' : '' ?></span> 5 Nights / 6 Days</span>
        <span class="tour-type-item"><span class="chk-box"><?= $nights == 7 ? '✓' : '' ?></span> 7 Nights / 8 Days</span>
    </span>
</div>

<!-- ===== CLIENT INFORMATION ===== -->
<div class="sec-hdr">CLIENT INFORMATION</div>
<table class="info-tbl">
    <tr>
        <td class="lbl">Client Name</td>
        <td class="val" colspan="3"><?= htmlspecialchars($d['consignee'] ?? '') ?></td>
    </tr>
    <tr>
        <td class="lbl">Number of Passengers</td>
        <td class="val"><?= htmlspecialchars($d['passenger_count'] ?? '') ?></td>
        <td class="lbl">Nationality</td>
        <td class="val"><?= htmlspecialchars($d['country'] ?? 'Japanese') ?></td>
    </tr>
    <tr>
        <td class="lbl">Tour Coordinator</td>
        <td class="val"><?= htmlspecialchars($d['create_user'] ?? '') ?></td>
        <td class="lbl">Emergency Contact</td>
        <td class="val"><?= htmlspecialchars($d['mobile'] ?? '') ?></td>
    </tr>
</table>

<!-- ===== FLIGHT DETAILS ===== -->
<div class="sec-hdr">FLIGHT DETAILS</div>
<table class="flight-tbl">
    <thead>
        <tr>
            <th style="width:18%;">Sector</th>
            <th style="width:22%;">Flight No</th>
            <th style="width:20%;">Date</th>
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
<table class="flight-tbl" style="margin-top:1px;">
    <tr>
        <td class="lbl" style="width:18%;">Arrival Airport</td>
        <td><?= htmlspecialchars($arr_airport_to) ?></td>
        <td class="lbl" style="width:18%;">Departure Airport</td>
        <td><?= htmlspecialchars($dep_airport_to) ?></td>
    </tr>
</table>

<!-- ===== TOUR STAFF ===== -->
<div class="sec-hdr">TOUR STAFF DETAILS</div>
<table class="staff-tbl">
    <thead>
        <tr>
            <th style="width:30%;">Position</th>
            <th>Name</th>
            <th style="width:25%;">Contact Number</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="lbl">Chauffeur Driver</td>
            <td><?= htmlspecialchars($d['transport_agent_name'] ?? '') ?></td>
            <td></td>
        </tr>
        <tr>
            <td class="lbl">National Guide</td>
            <td><?= htmlspecialchars($d['guide_name'] ?? '') ?></td>
            <td></td>
        </tr>
        <tr>
            <td class="lbl">Japanese Speaking Guide</td>
            <td></td>
            <td></td>
        </tr>
    </tbody>
</table>

<!-- ===== HOTEL DETAILS ===== -->
<div class="sec-hdr">HOTEL DETAILS</div>
<table class="hotel-tbl">
    <thead>
        <tr>
            <th style="width:12%;">Date</th>
            <th>Hotel Name</th>
            <th style="width:20%;">Room Type</th>
            <th style="width:15%;">Meal Plan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($tour_details)):
            foreach ($tour_details as $hotel): ?>
        <tr>
            <td class="day-lbl"><?= htmlspecialchars($hotel['tour_date'] ?? '') ?></td>
            <td class="hotel-name"><?= htmlspecialchars($hotel['hotel_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($hotel['rtype'] ?? '') ?></td>
            <td><?= htmlspecialchars($hotel['meal_code'] ?? '') ?></td>
        </tr>
        <?php endforeach;
        else: ?>
        <tr><td colspan="4" style="text-align:center;color:#888;padding:8px;">No hotel details available</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- ===== DETAILED TOUR ITINERARY ===== -->
<div class="sec-hdr" style="margin-top:12px;">DETAILED TOUR ITINERARY</div>

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
$dates  = array_keys($groupedItinerary);

foreach ($groupedItinerary as $tour_date => $details):
    $isFirst = ($dayNum === 1);
    $isLast  = ($dayNum === $total);
    $dayLabel = 'DAY ' . str_pad($dayNum, 2, '0', STR_PAD_LEFT);
    if ($isFirst)  $dayLabel .= ' – ARRIVAL IN SRI LANKA';
    if ($isLast)   $dayLabel .= ' – DEPARTURE';

    // Collect meals from all rows for this day
    $meals = ['bf' => [], 'lunch' => [], 'dinner' => []];
    foreach ($details as $row) {
        if (!empty($row['bf']))     $meals['bf'][]     = $row['bf'];
        if (!empty($row['lunch']))  $meals['lunch'][]  = $row['lunch'];
        if (!empty($row['dinner'])) $meals['dinner'][] = $row['dinner'];
    }
    $hasBF  = !empty($meals['bf']);
    $hasLUN = !empty($meals['lunch']);
    $hasDIN = !empty($meals['dinner']);

    // Hotel for overnight (match from tour_details by date)
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
        <?= htmlspecialchars($dayLabel) ?> &nbsp;
        <span style="font-weight:400;font-size:9pt;"><?= htmlspecialchars($tour_date) ?></span>
        <span class="meal-badges">
            <span class="meal-badge <?= $hasBF  ? '' : 'off' ?>">BF</span>
            <span class="meal-badge <?= $hasLUN ? '' : 'off' ?>">LUN</span>
            <span class="meal-badge <?= $hasDIN ? '' : 'off' ?>">DIN</span>
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
        <span class="places-label">Places to Visit :</span>
        <ul>
            <?php foreach ($details as $row): ?>
                <?php if (!empty($row['description'])): ?>
                <li><?= htmlspecialchars($row['description']) ?><?= !empty($row['area']) ? ' – ' . htmlspecialchars($row['area']) : '' ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <div class="overnight">Overnight Stay &nbsp;: &nbsp; Hotel : <span><?= htmlspecialchars($overnightHotel) ?></span></div>
    </div>
</div>
<?php $dayNum++; endforeach; ?>

<!-- ===== IMPORTANT NOTES ===== -->
<div class="sec-hdr" style="margin-top:14px;">IMPORTANT NOTES</div>
<div class="notes-block">
    <ul>
        <li>Check-in time : 14:00 hrs / Check-out time : 12:00 hrs</li>
        <li>Early check-in and late check-out subject to availability</li>
        <li>Program may change depending on weather and road conditions</li>
        <li>Clients are advised to carry passports during travel</li>
        <li>Japanese speaking guide subject to availability</li>
    </ul>
</div>

<!-- ===== CONTACT DETAILS ===== -->
<div class="sec-hdr" style="margin-top:10px;">CONTACT DETAILS</div>
<div class="contact-block">
    <strong>AVENIR HOLIDAYS PVT LTD</strong><br>
    Inbound Tour Operations – Sri Lanka<br>
    Green Lanka Tower, 46/46 Nawam Mawatha, Colombo 02<br>
    +94 77 729 5473 &nbsp;/&nbsp; +94 114 215 977<br>
    land@avenirholidays.com &nbsp;&bull;&nbsp; www.avenirholidays.com
</div>

</div><!-- /.page -->

<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>
</body>
</html>
