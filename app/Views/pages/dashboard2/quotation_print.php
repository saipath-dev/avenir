<?= view('layouts/head') ?>

<body>

    <style>
        @media print {
            @page {
                size: A4 landscape;
                /* Set A4 size and landscape orientation */
                margin: 20mm;
                /* Adjust the margin as needed */
            }

            body {
                margin: 0;
                /* Remove margins for the body */
                padding: 0;
                /* Remove padding for the body */
            }

            .no-print {
                display: none !important;
                /* Hide elements that should not be printed */
            }

            .main-content {
                width: 100%;
                /* Add any other print-specific styles here */
            }

        }

        .table-custom tr {
            height: 2% !important;
            /* Adjust the height as needed */
        }

        .table th,
        .table td {
            padding: 1px !important;
            /* Adjust padding to a smaller value */
            margin: 0;
            /* Ensure there's no margin */
        }

        .fixed-top {
            /* display: none !important; */
        }
    </style>

    <div class="d-flex justify-content-between align-items-center bg-white p-2 px-4 shadow fixed-top" style="display: flex;">
        <div>
            <img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="" width="120px">
        </div>
        <div>
            <button class="btn btn-outline-dark" onclick="window.close()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
    </div>
    <div class="container-fluid p-4 mt-5">
        <?php
        //if (!empty($tour_details)) {
        ?>
        <div class="row clearfix mt-3">
            <div class="col-md-12">
                <div class="card p-4">
                    <div class="body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-9">
                                        <div class="form-group">
                                            <h2 class="p-title" style="text-align: center;">TOUR QUOTATION</h2>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <img class="rounded-5" src="<?= base_url() ?>assets/images/Logo-new.png" alt="" height="50">
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="form-group">
                                            <button class="btn btn-primary no-print" onclick="window.print()">Print</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <style>
                            .sm-text {
                                font-size: 10px;
                                color: grey;
                            }
                        </style>
                        <div class="table-responsive">
                            <!-- <thead class="thead-dark"> -->
                            <?php
                            $i = 1;
                            $consignee = [];
                            foreach ($consignee_details as $detail) {
                                $consignee = $detail;
                                $twintype = $detail['dbl_twin'];
                                if ($twintype == null || $twintype == 0) {
                                    $twintype = '';
                                } else {
                                    $twintype = '*';
                                }
                            ?>
                                <table class="table js-basic-example1 table-hover dataTable table-custom align-middle" style="table-layout: fixed;">

                                    <tr>
                                        <th>
                                            <span class="fw-normal">
                                                Tour Ref / Manual Id
                                            </span>
                                            <br>
                                            <?= $detail['tour_id'] . ' - ' . '<code>' . $detail['manual_id'] . '</code>' ?>
                                        </th>
                                        <th>
                                            <span class="fw-normal">
                                                No of Pax
                                            </span>
                                            <br>
                                            <?= $detail['passenger_count'] ?>
                                        </th>
                                        <th>
                                            <span class="fw-normal">
                                                Name / Agent
                                            </span>
                                            <br>
                                            <?= $detail['consignee'] . ' / ' . $detail['agent_name'] ?>
                                        </th>
                                        <th>
                                            <span class="fw-normal">
                                                Chauffeur / Guide
                                            </span>
                                            <br>
                                            <?= $detail['transport_agent_name'] . ' / ' . $detail['guide_name'] ?>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>
                                            <span class="fw-normal">
                                                Arrival / Date & Flight
                                            </span>
                                            <br>
                                            <?= $detail['arrival_flight'] . ' - ' . $detail['arrival_flight_time'] ?>
                                        </th>
                                        <th>
                                            <span class="fw-normal">
                                                Departure / Date & Flight
                                            </span>
                                            <br>
                                            <?= $detail['departure_flight'] . ' - ' . $detail['departure_flight_time'] ?>
                                        </th>
                                        <th>
                                            <span class="fw-normal">
                                                Vehicle Type
                                            </span>
                                            <br>
                                            <?= $detail['vehicle_name'] ?>
                                        </th>
                                        <th>
                                            <span class="fw-normal">
                                                Invoice Amount
                                            </span>
                                            <br>
                                            <?= empty($invoice_details['dollar_amount']) ? '-' : '$' . $invoice_details['dollar_amount'] . '<br>LKR: ' . number_format($invoice_details['credit_balance'], 2) ?>
                                        </th>
                                    </tr>
                                </table>
                            <?php
                            }
                            ?>
                            <!-- </thead>
                                                        <tbody> -->
                            <table class="table table-bordered align-middle table-custom">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Date</th>
                                        <th>Hotel / Category</th>
                                        <th>SGL</th>
                                        <th>DBL</th>
                                        <th>Twin</th>
                                        <th>TPL</th>
                                        <th>Basic</th>
                                        <th>Status</th>
                                        <th>Visits / Sightseeing</th>
                                        <th>Total KM</th>
                                        <th>Extra KM</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    $total_sgl = 0;
                                    $total_dbl = 0;
                                    $total_tpl = 0;
                                    $total_km = 0;
                                    $total_extra_km = 0;
                                    foreach ($tour_details as $row) {
                                        $meal = $row['meal_code'];
                                        if ($meal == 'fb') {
                                            $meal = 'FB';
                                        } else if ($meal == 'hb') {
                                            $meal = 'HB';
                                        } else if ($meal == 'bb') {
                                            $meal = 'BB';
                                        } else {
                                            $meal = '';
                                        }

                                        // room rates
                                        $total_sgl += $row['sgl'] * $consignee['sgl'];
                                        $total_dbl += $row['dbl'] * $consignee['dbl'];
                                        $total_tpl += $row['tpl'] * $consignee['tpl'];

                                        // km
                                        $total_km += $row['distance'];
                                        $total_extra_km += $row['extra_kilo'];

                                    ?>
                                        <tr>
                                            <td><?= $i++; ?></td>
                                            <td><?= $row['tour_date'] ?></td>
                                            <td><?= $row['hotel_name'] ?><br>
                                                <p style="font-size: small; color:#457900;"><?= $row['rtype'] ?></p>
                                            </td>
                                            <td>
                                                <?php
                                                if (!$consignee['sgl'] == '0') {
                                                ?>
                                                    <div class="d-flex flex-column">
                                                        <small class="sm-text">Count</small>
                                                        <span>
                                                            <?= (empty($consignee['sgl']) || $consignee['sgl'] == '0') ? '' : $consignee['sgl']; ?>
                                                        </span>
                                                        <small class="sm-text">Rate</small>
                                                        <span>
                                                            <?= (empty($row['sgl']) || $row['sgl'] == '0') ? '' : $row['sgl']; ?>
                                                        </span>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if (!$consignee['dbl'] == '0') {
                                                ?>
                                                    <div class="d-flex flex-column">
                                                        <small class="sm-text">Count</small>
                                                        <span>
                                                            <?= (empty($consignee['dbl']) || $consignee['dbl'] == '0') ? '' : $consignee['dbl']; ?>
                                                        </span>
                                                        <small class="sm-text">Rate</small>
                                                        <span>
                                                            <?= (empty($row['dbl']) || $row['dbl'] == '0') ? '' : $row['dbl']; ?>
                                                        </span>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <td><?= $twintype ?></td>
                                            <td>
                                                <?php
                                                if (!$consignee['tpl'] == '0') {
                                                ?>
                                                    <div class="d-flex flex-column">
                                                        <small class="sm-text">Count</small>
                                                        <span>
                                                            <?= (empty($consignee['tpl']) || $consignee['tpl'] == '0') ? '' : $consignee['tpl']; ?>
                                                        </span>
                                                        <small class="sm-text">Rate</small>
                                                        <span>
                                                            <?= (empty($row['tpl']) || $row['tpl'] == '0') ? '' : $row['tpl']; ?>
                                                        </span>
                                                    </div>
                                                <?php
                                                }
                                                ?>
                                            </td>
                                            <td><?= $meal ?></td>
                                            <td></td>
                                            <td> <?php
                                                    $loc = array();
                                                    foreach ($sub_locations as $row1) {
                                                        if ($row1['date_id'] == $row['tour_date']) {
                                                            $loc[] = '* ' . $row1['from'] . ' - ' . $row1['to'];
                                                        }
                                                    }
                                                    echo implode('<br> ', $loc);
                                                    ?>
                                            </td>
                                            <td>
                                                <?= $row['distance']; ?>
                                            </td>
                                            <td>
                                                <?= $row['extra_kilo']; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $activities = array();
                                                foreach ($activity_details as $activity) {
                                                    if ($activity['tour_date'] == $row['tour_date']) {
                                                        $activities[] = '* ' . $activity['locatonname'];
                                                    }
                                                }
                                                echo implode('<br>', $activities);
                                                ?>

                                            </td>
                                        </tr>
                                    <?php
                                    }
                                    ?>
                                    <tr class="table-warning">
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <?php
                                            if ($total_sgl > 0) {
                                                echo number_format($total_sgl, 2);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($total_dbl > 0) {
                                                echo number_format($total_dbl, 2);
                                            }
                                            ?>
                                        </td>
                                        <td></td>
                                        <td>
                                            <?php
                                            if ($total_tpl > 0) {
                                                echo number_format($total_tpl, 2);
                                            }
                                            ?>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <?php
                                            if ($total_km > 0) {
                                                echo number_format($total_km, 1);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($total_extra_km > 0) {
                                                echo number_format($total_extra_km, 1);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            echo number_format($consignee['activity_fee_total'], 2);
                                            ?>
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <td></td>
                                        <td>TOUR BATA</td>
                                        <td></td>
                                        <td>
                                        </td>
                                        <td>
                                            No. of Days
                                        </td>
                                        <td></td>
                                        <td>
                                            <?=
                                            $consignee['bata_days'];
                                            ?>
                                        </td>
                                        <td>X</td>
                                        <td>
                                            Day Rate
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($consignee['bata_day_rate'])) {
                                                echo $consignee['bata_day_rate'];
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            =
                                        </td>
                                        <td>
                                            <?php
                                            $bata_total = 0;
                                            if (!empty($consignee['bata_day_rate'])) {
                                                $bata_total = $consignee['bata_day_rate'] * $consignee['bata_days'];
                                                echo number_format($bata_total, 2);
                                            }
                                            ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-light">
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                        </td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            Vehicle Rate
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($consignee['vehicle_rate'])) {
                                                echo $consignee['vehicle_rate'] . ' X ' . ($total_km + $total_extra_km);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            =
                                        </td>
                                        <td>
                                            <?php
                                            $total_km_cost = 0;
                                            if (!empty($consignee['vehicle_rate'])) {
                                                $total_km_cost = $consignee['vehicle_rate'] * ($total_km + $total_extra_km);
                                                echo number_format($total_km_cost, 2);
                                            }
                                            ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="table-success">
                                        <td></td>
                                        <td></td>
                                        <td colspan="3">
                                            Total Room Cost<br> + <br>Activity Fee
                                        </td>
                                        <td>
                                            =
                                        </td>
                                        <td>
                                            <?php
                                            echo number_format($total_sgl + $total_dbl + $total_tpl, 2);
                                            ?><br> + <br>
                                            <?php
                                            echo number_format($consignee['activity_fee_total'], 2);
                                            ?><br>
                                            -------<br>
                                            <?php echo number_format($total_sgl + $total_dbl + $total_tpl + $consignee['activity_fee_total'], 2); ?>
                                        </td>
                                        <td></td>
                                        <td colspan="2">
                                            Total Transport Cost
                                        </td>
                                        <td>
                                            =
                                        </td>
                                        <td>
                                            <?php
                                            echo number_format($total_km_cost + $bata_total, 2);
                                            ?>
                                        </td>
                                        <td>

                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="8"></td>
                                        <td colspan="2">
                                            <span class="fw-normal">
                                                Total Amount
                                            </span>
                                        </td>
                                        <td>=</td>
                                        <td colspan="2">
                                            <?= empty($invoice_details['dollar_amount']) ? '-' : '$ ' . $invoice_details['dollar_amount'] . '<br>LKR: ' . number_format($invoice_details['credit_balance'], 2) ?>
                                        </td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php //} 
        ?>
    </div>

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
    <script src="<?php echo base_url(); ?>assets/bundles/morrisscripts.bundle.js"></script>
    <script src="<?php echo base_url(); ?>assets/vendor/echart/echarts.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/pages/charts/echart.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr("#drange", {
                mode: "range",
                dateFormat: "Y-m-d"
            });
        });
    </script>
</body>


</html>