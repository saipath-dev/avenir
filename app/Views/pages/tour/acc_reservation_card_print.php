<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <style>
            @media print {
                @page {
                    size: A4 landscape;
                    margin: 4mm;
                }

                body,
                .main-content,
                .page-content,
                .container-fluid {
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                }

                .no-print,
                #page-topbar,
                .app-menu.navbar-menu,
                .vertical-menu,
                .footer {
                    display: none !important;
                }

                #layout-wrapper {
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .card {
                    box-shadow: none !important;
                    border: none !important;
                }

                table {
                    width: 100% !important;
                }

                body,
                table,
                th,
                td,
                p {
                    font-size: 10px !important;
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
                                                                    <h2 class="p-title" style="text-align: center;">ACCOUNT RESERVATION CARD</h2>
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
                                                                <th>Invoice Amount (Agent)</th>
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
                                                            <th>#</th>
                                                            <th>Date</th>
                                                            <th>Hotel / Category</th>
                                                            <th>Confirmed $ Count</th>
                                                            <th>Paid $ Count</th>
                                                            <th>$ Rate</th>
                                                            <th>LKR Amount</th>
                                                            <th>Paid Date</th>
                                                            <th>PV</th>
                                                            <!-- <th>Visits / Sightseeing</th> -->
                                                            <!-- <th>Remarks</th> -->
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

                                                            $min = $row['start_date'];
                                                            $max = $row['end_date'];
                                                            if ($min == $max) {
                                                                $daterang = $min;
                                                            } else {
                                                                $daterang = $min . "  -  " . $max;
                                                            }
                                                        ?>
                                                            <tr>
                                                                <td><?= $i++; ?></td>
                                                                <td><?= $daterang ?></td>
                                                                <td><?= $row['hotel_name'] ?><br>
                                                                    <p style="font-size: small; "><?= $row['rtype'] ?><?= empty($meal) ? '' : ' - ' . $meal ?></p>
                                                                </td>
                                                                <td><?= $row['total_hotel_doller'] . $twintype ?></td>
                                                                <td><?= $row['pay_doller_count'] . $twintype ?></td>
                                                                <td><?= empty($row['hotel_dollar_rate']) ? '-' : '$' . $row['hotel_dollar_rate'] ?></td>
                                                                <td>
                                                                    <?php
                                                                    $lkrAmount = '-';
                                                                    if (!empty($row['pay_doller_count']) && !empty($row['hotel_dollar_rate'])) {
                                                                        $lkrAmountValue = floatval($row['pay_doller_count']) * floatval($row['hotel_dollar_rate']);
                                                                        $lkrAmount = 'LKR ' . number_format($lkrAmountValue, 2);
                                                                    }
                                                                    echo $lkrAmount;
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $paidDate = '-';

                                                                    if (!empty($account_running_details)) {
                                                                        foreach ($account_running_details as $account_row) {
                                                                            if (isset($account_row['L_Code']) && $account_row['L_Code'] == $row['hotel_acc']) {
                                                                                $paidDate = empty($account_row['Date']) ? '-' : date('Y-m-d', strtotime($account_row['Date']));
                                                                                break;
                                                                            }
                                                                        }
                                                                    }
                                                                    echo $paidDate;
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $voucherToken = '-';

                                                                    if (!empty($account_running_details)) {
                                                                        foreach ($account_running_details as $account_row) {
                                                                            if (isset($account_row['L_Code']) && $account_row['L_Code'] == $row['hotel_acc']) {
                                                                                // Show V_tkn (Voucher Token) if available, otherwise show Recpt_No
                                                                                $voucherToken = !empty($account_row['V_tkn'])
                                                                                    ? $account_row['V_tkn']
                                                                                    : (!empty($account_row['Recpt_No']) ? $account_row['Recpt_No'] : '-');
                                                                                break;
                                                                            }
                                                                        }
                                                                    }
                                                                    echo $voucherToken;
                                                                    ?>
                                                                </td>
                                                                <!-- <td></td> -->
                                                                <!-- <td> <?php
                                                                            //$loc = array();
                                                                            //foreach ($sub_locations as $row1) {
                                                                            //if ($row1['date_id'] == $row['tour_date']) {
                                                                            //$loc[] = '* ' . $row1['from'] . ' - ' . $row1['to'];
                                                                            //}
                                                                            //}
                                                                            //echo implode('<br> ', $loc);
                                                                            ?>
                                                                </td> -->
                                                                <!-- <td>
                                                                    <?php
                                                                    //$activities = array();
                                                                    //foreach ($activity_details as $activity) {
                                                                    //if ($activity['tour_date'] == $row['tour_date']) {
                                                                    //$activities[] = '* ' . $activity['locatonname'];
                                                                    //}
                                                                    //}
                                                                    //echo implode('<br>', $activities);
                                                                    ?>

                                                                </td> -->
                                                            </tr>
                                                        <?php
                                                        }

                                                        // Add lunch payment rows
                                                        if (!empty($lunch_payments)) {
                                                            foreach ($lunch_payments as $lunch) {
                                                        ?>
                                                            <tr>
                                                                <td><?= $i++; ?></td>
                                                                <td><?= empty($lunch['payment_request_date']) ? '-' : date('Y-m-d', strtotime($lunch['payment_request_date'])) ?></td>
                                                                <td><?= esc($lunch['supplier_name']) ?><br>
                                                                    <p style="font-size: small; ">Lunch/Activities</p>
                                                                </td>
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td>LKR <?= number_format((float) $lunch['requestAmount'], 2) ?></td>
                                                                <td><?= empty($lunch['complete_date']) ? (empty($lunch['accept_date']) ? '-' : date('Y-m-d', strtotime($lunch['accept_date']))) : date('Y-m-d', strtotime($lunch['complete_date'])) ?></td>
                                                                <td><?= empty($lunch['pv_number']) ? '-' : $lunch['pv_number'] ?></td>
                                                            </tr>
                                                        <?php
                                                            }
                                                        }

                                                        // Add driver advance payment row
                                                        if (!empty($detail['transport_agent']) && !empty($detail['transport_advance'])) {
                                                        ?>
                                                            <tr style=" font-weight: 600;">
                                                                <td colspan="2" style="text-align: center;">TRANSPORT</td>
                                                                <td><?= ($detail['transport_agent_name'] ?? 'N/A') . ' - Driver Advance' ?></td>
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td>
                                                                    <?php
                                                                    // Display actual payment amount from voucher, not from consignee.transport_advance
                                                                    if (!empty($driver_advances)) {
                                                                        $totalAmount = array_sum(array_column($driver_advances, 'amount'));
                                                                        echo 'LKR ' . number_format($totalAmount, 2);
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    if (!empty($driver_advances)) {
                                                                        $dates = array_column($driver_advances, 'Date');
                                                                        echo implode(', ', array_map(function($d) {
                                                                            return date('Y-m-d', strtotime($d));
                                                                        }, $dates));
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    if (!empty($driver_advances)) {
                                                                        $vouchers = [];
                                                                        foreach ($driver_advances as $adv) {
                                                                            $status = '';
                                                                            if ($adv['Aflag'] == 0) $status = '';
                                                                            elseif ($adv['Aflag'] == 2) $status = ' (Rejected)';
                                                                            elseif ($adv['Aflag'] == 3) $status = ' (Cancelled)';
                                                                            $vouchers[] = $adv['V_tkn'] . $status;
                                                                        }
                                                                        echo implode(', ', $vouchers);
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php
                                                        }

                                                        // Add driver settlement payment row
                                                        if (!empty($detail['transport_agent']) && !empty($driver_settlements)) {
                                                        ?>
                                                            <tr style="background-color: #d1ecf1; font-weight: 600;">
                                                                <td colspan="2" style="text-align: center;">TRANSPORT</td>
                                                                <td><?= ($detail['transport_agent_name'] ?? 'N/A') . ' - Settlement' ?></td>
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td>
                                                                    <?php
                                                                    // Display settlement amount from voucher
                                                                    $totalSettlement = array_sum(array_column($driver_settlements, 'amount'));
                                                                    echo 'LKR ' . number_format($totalSettlement, 2);
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $settlementDates = array_column($driver_settlements, 'Date');
                                                                    echo implode(', ', array_map(function($d) {
                                                                        return date('Y-m-d', strtotime($d));
                                                                    }, $settlementDates));
                                                                    ?>
                                                                </td>
                                                                <td>
                                                                    <?php
                                                                    $settlementVouchers = [];
                                                                    foreach ($driver_settlements as $settle) {
                                                                        $status = '';
                                                                        if ($settle['Aflag'] == 0) $status = ' (Pending)';
                                                                        elseif ($settle['Aflag'] == 2) $status = ' (Rejected)';
                                                                        elseif ($settle['Aflag'] == 3) $status = ' (Cancelled)';
                                                                        $settlementVouchers[] = $settle['V_tkn'] . $status;
                                                                    }
                                                                    echo implode(', ', $settlementVouchers);
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