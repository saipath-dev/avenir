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
                                            <h2 class="p-title" style="text-align: center;">RESERVATION CARD</h2>
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
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
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

                                    ?>
                                        <tr>
                                            <td><?= $i++; ?></td>
                                            <td><?= $row['tour_date'] ?></td>
                                            <td><?= $row['hotel_name'] ?><br>
                                                <p style="font-size: small; color:#457900;"><?= $row['rtype'] ?></p>
                                            </td>
                                            <td><?= (empty($consignee['sgl']) || $consignee['sgl'] == '0') ? '' : $consignee['sgl']; ?></td>
                                            <td><?= (empty($consignee['dbl']) || $consignee['dbl'] == '0') ? '' : $consignee['dbl']; ?></td>
                                            <td><?= $twintype ?></td>
                                            <td><?= (empty($consignee['tpl']) || $consignee['tpl'] == '0') ? '' : $consignee['tpl']; ?></td>
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