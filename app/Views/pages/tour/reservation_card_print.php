<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <style>
            @media print {
                @page {
                    size: A4 landscape;
                    /* Set A4 size and landscape orientation */
                    margin: 10%;
                    /* Adjust the margin as needed */
                }

                body {
                    margin: 5%;
                    /* Remove margins for the body */
                    padding: 0%;
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
                height: 10% !important;
                /* Adjust the height as needed */
            }

            .table th,
            .table td {
                padding: 4px !important;
                /* Adjust padding to a smaller value */
                margin: 0;
                /* Ensure there's no margin */
            }
        </style>
        <?= view('layouts/sidebar') ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">

                            <div class="h-100">
                                <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <!-- <h2 class="p-title" style="text-align: center;">RESERVATION CARD</h2> -->
                                    </div>
                                </div>


                                <?php
                                //if (!empty($tour_details)) {
                                ?>
                                <div class="row clearfix mt-3">
                                    <!-- <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="form-group">
                                                                    fsfsgd
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div> -->
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <div class="form-group">
                                                                    <h2 class="p-title" style="text-align: center;">RESERVATION CARD</h2>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
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
                                                    foreach ($consignee_details as $detail) {
                                                        $twintype = $detail['dbl_twin'];
                                                        if ($twintype == null || $twintype == 0) {
                                                            $twintype = '';
                                                        } else {
                                                            $twintype = '*';
                                                        }
                                                    ?>
                                                        <table class="table table-custom align-middle table-bordered">
                                                            <tr>
                                                                <th>Tour Ref / Manual Id</th>
                                                                <td><?= $detail['tour_id'] . ' - ' . $detail['manual_id'] ?></td>
                                                                <th>No of Pax</th>
                                                                <td><?= $detail['passenger_count'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Name Of Pax / Agent</th>
                                                                <td><?= $detail['consignee'] . ' / ' . $detail['agent_name'] ?></td>
                                                                <th>Chauffeur / Guide</th>
                                                                <td><?= $detail['transport_agent_name'] . ' / ' . $detail['guide_name'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Arrival / Date & Flight</th>
                                                                <td><?= $detail['arrival_flight'] . ' - ' . $detail['arrival_flight_time'] ?></td>
                                                                <th>Vehicle Type</th>
                                                                <td><?= $detail['vehicle_name'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Departure / Date & Flight</th>
                                                                <td><?= $detail['departure_flight'] . ' - ' . $detail['departure_flight_time'] ?></td>
                                                                <th>Invoice Amount</th>
                                                                <td><?= empty($invoice_details['dollar_amount']) ? '-' : '$' . $invoice_details['dollar_amount'] . '<br>LKR: ' . number_format($invoice_details['credit_balance'], 2) ?></td>
                                                            </tr>
                                                        </table>
                                                    <?php
                                                    }
                                                    ?>
                                                    <!-- </thead>
                                                        <tbody> -->
                                                    <table class="table table-bordered align-middle">
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
                                                        <!-- </tbody> -->
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php //} 
                                ?>
                            </div> <!-- end .h-100-->

                        </div> <!-- end col -->
                    </div>

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr("#drange", {
                mode: "range",
                dateFormat: "Y-m-d"
            });
        });

        $(document).ready(function() {
            // i wanna landscape print & a4 page 
            // window.print();
        });
    </script>

</body>

</html>