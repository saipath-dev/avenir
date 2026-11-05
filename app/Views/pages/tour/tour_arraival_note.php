<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
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
                                        <h4 class="p-title">Arrival Notification</h4><br>
                                    </div>
                                    <div class="col-md-2 d-flex">
                                        <h6 class="p-title"><?= date('Y-m-d') ?></h6>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-6">
                                        <div class="card p-4" style="height: 400px; overflow-y: auto;">
                                            <div class="body">
                                                <h4 class="text-center">Today</h4>
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="mainTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Manual Id</th>
                                                                <th>T Name</th>
                                                                <th>Start & End Date</th>
                                                                <th>Booking</th>
                                                                <th>Hotel Reservation</th>
                                                                <th>Transport</th>
                                                                <th>View Tour</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($consignee)) : ?>
                                                                <?php $i = 1; ?>
                                                                <?php foreach ($consignee as $tour) :
                                                                    $hotel_only = $tour['hotel_only'];
                                                                    $transport_only = $tour['transport_only'];

                                                                    $total_doller = $tour['total_hotel_doller'];
                                                                    $total_paid_doller = $tour['pay_doller_count'];
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $tour['manual_id']; ?></td>
                                                                        <td><?= $tour['consignee']; ?></td>
                                                                        <td><?= $tour['start_date'] . "<br> " . $tour['end_date']; ?></td>
                                                                        <td>
                                                                            <?php if ($tour['Confirm'] == 1) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px" class="me-2">
                                                                            <?php } else if ($tour['Confirm'] == 0) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2"><?php } else if ($tour['Confirm'] == 3) { ?>
                                                                                <button type="button"
                                                                                    class="btn btn-danger btn-sm"
                                                                                    title="Booking Status">
                                                                                    Tour Cancelled
                                                                                </button>
                                                                            <?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($transport_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if ($transport_only == 1) {
                                                                                    } else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }
                                                                                } else {
                                                                                    $set_flag_booking = $set_flag_confirm = $set_flag_payment = 0;


                                                                                    $ok_img = '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';

                                                                                    if (isset($tour['quotation_days'])) {
                                                                                        // if (isset($tour['hotel_booking_cnt']) && $tour['quotation_days'] == $tour['hotel_booking_cnt']) {
                                                                                        $set_flag_booking = 1;
                                                                                        // echo $ok_img;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_booking_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                        // } else {
                                                                                        //     $set_flag_booking = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)"><i class="bi bi-x-circle" title="Hotel Booking Not Completed" ></i></button>';
                                                                                        // }

                                                                                        // $set_flag_booking = 1;
                                                                                        //     echo '<button class="btn btn-sm btn-info me-2" onclick="hotelReserve(' . $row->tour_id . ',\'' . addslashes($row->start_date) . '\',\'' . addslashes($row->end_date) . '\', 1)">';
                                                                                        //     echo  $row->hotel_booking_cnt . '/' . intval($row->hotel_count_new);
                                                                                        //     echo '</button>';


                                                                                        // if (isset($tour['booking_confirmed_cnt']) && $tour['quotation_days'] == $tour['booking_confirmed_cnt']) {
                                                                                        //     $set_flag_confirm = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-check-circle"></i></button>';
                                                                                        // } else {
                                                                                        //     $set_flag_confirm = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ', \' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-x-circle" title="Booking Confirmed Not Completed" ></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['booking_confirmed_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';

                                                                                        // if (isset($tour['hotel_payment_cnt']) && $tour['quotation_days'] == $tour['hotel_payment_cnt']) {
                                                                                        //     $set_flag_payment = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     if ($total_doller == $total_paid_doller) {
                                                                                        //         echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-check-circle"></i></button>';
                                                                                        //     } else {
                                                                                        //         echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        //     }
                                                                                        // } else {
                                                                                        //     $set_flag_payment = 0;

                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ' ,\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_payment_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($hotel_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if ($hotel_only == 1) {
                                                                                    } else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }
                                                                                } else {
                                                                                    $trns_advance = $trns_expenses = $trns_settled = 0;

                                                                                    if (isset($tour['transport_advance']) && $tour['transport_advance'] == 1) {
                                                                                        $trns_advance = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                        // echo $ok_img;
                                                                                    } else {
                                                                                        $trns_advance = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-x-circle-fill" title="Transport Advance Not Completed"></i></button>';
                                                                                    }

                                                                                    // if (isset($tour['transport_advance) && $tour['transport_advance == 1) {
                                                                                    //     $trns_advance = 1;
                                                                                    //     echo $ok_img;
                                                                                    // } else {
                                                                                    // $trns_expenses = 0;
                                                                                    // echo '<button class="btn btn-sm btn-soft-info me-2" onclick="transportPayment(' .
                                                                                    //     $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                    //     addslashes($tour['end_date']) . '\', \'' .
                                                                                    //     addslashes($tour['consignee']) . '\', ' .
                                                                                    //     $tour['pax_count'] . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    // }

                                                                                    if (isset($tour['t_expense_added']) && $tour['t_expense_added']) {
                                                                                        $trns_expenses = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    } else {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-x-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    }

                                                                                    if (isset($tour['transport_settled']) && $tour['transport_settled'] == 1) {
                                                                                        $trns_settled = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                    } else {
                                                                                        $trns_settled = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-x-circle-fill" title="Transport Settled Not Completed"></i></button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>

                                                                        </td>
                                                                        <td>
                                                                            <button class="btn btn-primary btn-sm" onclick="window.open('<?= base_url('tour/print?tour_id=' . $tour['tour_id']); ?>', '_blank')">
                                                                                <i class="bi bi-eye"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card p-4" style="height: 400px; overflow-y: auto;">
                                            <div class="body">
                                                <h4 class="text-center">1 Day</h4>
                                                <h6 style="text-align: center;"><?= $day1; ?></h6>
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="mainTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Manual Id</th>
                                                                <th>T Name</th>
                                                                <th>Start & End Date</th>
                                                                <th>Booking</th>
                                                                <th>Hotel Reservation</th>
                                                                <th>Transport</th>
                                                                <th>View Tour</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($oneday)) : ?>
                                                                <?php $i = 1; ?>
                                                                <?php foreach ($oneday as $tour) :
                                                                    $hotel_only = $tour['hotel_only'];
                                                                    $transport_only = $tour['transport_only'];

                                                                    $total_doller = $tour['total_hotel_doller'];
                                                                    $total_paid_doller = $tour['pay_doller_count'];
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $tour['manual_id']; ?></td>
                                                                        <td><?= $tour['consignee']; ?></td>
                                                                        <td><?= $tour['start_date'] . "<br> " . $tour['end_date']; ?></td>
                                                                        <td>
                                                                            <?php if ($tour['Confirm'] == 1) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px" class="me-2">
                                                                            <?php } else if ($tour['Confirm'] == 0) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2"><?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($transport_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if ($transport_only == 1) {
                                                                                    } else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }
                                                                                } else {
                                                                                    $set_flag_booking = $set_flag_confirm = $set_flag_payment = 0;


                                                                                    $ok_img = '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';

                                                                                    if (isset($tour['quotation_days'])) {
                                                                                        // if (isset($tour['hotel_booking_cnt']) && $tour['quotation_days'] == $tour['hotel_booking_cnt']) {
                                                                                        $set_flag_booking = 1;
                                                                                        // echo $ok_img;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_booking_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                        // } else {
                                                                                        //     $set_flag_booking = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)"><i class="bi bi-x-circle" title="Hotel Booking Not Completed" ></i></button>';
                                                                                        // }

                                                                                        // $set_flag_booking = 1;
                                                                                        //     echo '<button class="btn btn-sm btn-info me-2" onclick="hotelReserve(' . $row->tour_id . ',\'' . addslashes($row->start_date) . '\',\'' . addslashes($row->end_date) . '\', 1)">';
                                                                                        //     echo  $row->hotel_booking_cnt . '/' . intval($row->hotel_count_new);
                                                                                        //     echo '</button>';


                                                                                        // if (isset($tour['booking_confirmed_cnt']) && $tour['quotation_days'] == $tour['booking_confirmed_cnt']) {
                                                                                        //     $set_flag_confirm = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-check-circle"></i></button>';
                                                                                        // } else {
                                                                                        //     $set_flag_confirm = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ', \' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-x-circle" title="Booking Confirmed Not Completed" ></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['booking_confirmed_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';

                                                                                        // if (isset($tour['hotel_payment_cnt']) && $tour['quotation_days'] == $tour['hotel_payment_cnt']) {
                                                                                        //     $set_flag_payment = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     if ($total_doller == $total_paid_doller) {
                                                                                        //         echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-check-circle"></i></button>';
                                                                                        //     } else {
                                                                                        //         echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        //     }
                                                                                        // } else {
                                                                                        //     $set_flag_payment = 0;

                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ' ,\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_payment_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($hotel_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if ($hotel_only == 1) {
                                                                                    } else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }
                                                                                } else {
                                                                                    $trns_advance = $trns_expenses = $trns_settled = 0;

                                                                                    if (isset($tour['transport_advance']) && $tour['transport_advance'] == 1) {
                                                                                        $trns_advance = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                        // echo $ok_img;
                                                                                    } else {
                                                                                        $trns_advance = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-x-circle-fill" title="Transport Advance Not Completed"></i></button>';
                                                                                    }

                                                                                    // if (isset($tour['transport_advance) && $tour['transport_advance == 1) {
                                                                                    //     $trns_advance = 1;
                                                                                    //     echo $ok_img;
                                                                                    // } else {
                                                                                    if (isset($tour['t_expense_added']) && $tour['t_expense_added']) {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-info me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    } else {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-x-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    }
                                                                                    // }

                                                                                    if (isset($tour['transport_settled']) && $tour['transport_settled'] == 1) {
                                                                                        $trns_settled = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                    } else {
                                                                                        $trns_settled = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-x-circle-fill" title="Transport Settled Not Completed"></i></button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <button class="btn btn-primary btn-sm" onclick="window.open('<?= base_url('tour/print?tour_id=' . $tour['tour_id']); ?>', '_blank')">
                                                                                <i class="bi bi-eye"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-6">
                                        <div class="card p-4" style="height: 400px; overflow-y: auto;">
                                            <div class="body">
                                                <h4 class="text-center">3 Days</h4>
                                                <h6 style="text-align: center;"><?= $day2 . " / " . $day3; ?></h6>
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="mainTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Manual Id</th>
                                                                <th>T Name</th>
                                                                <th>Start & End Date</th>
                                                                <th>Booking</th>
                                                                <th>Hotel Reservation</th>
                                                                <th>Transport</th>
                                                                <th>View Tour</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($threedays)) : ?>
                                                                <?php $i = 1; ?>
                                                                <?php foreach ($threedays as $tour) :
                                                                    $hotel_only = $tour['hotel_only'];
                                                                    $transport_only = $tour['transport_only'];

                                                                    $total_doller = $tour['total_hotel_doller'];
                                                                    $total_paid_doller = $tour['pay_doller_count'];
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $tour['manual_id']; ?></td>
                                                                        <td><?= $tour['consignee']; ?></td>
                                                                        <td><?= $tour['start_date'] . "<br> " . $tour['end_date']; ?></td>
                                                                        <td>
                                                                            <?php if ($tour['Confirm'] == 1) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px" class="me-2">
                                                                            <?php } else if ($tour['Confirm'] == 0) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2"><?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($transport_only == 1 || $tour['Confirm'] == 3) {
                                                                                    if($transport_only == 1) {} else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }   
                                                                                } else {
                                                                                    $set_flag_booking = $set_flag_confirm = $set_flag_payment = 0;


                                                                                    $ok_img = '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';

                                                                                    if (isset($tour['quotation_days'])) {
                                                                                        if (isset($tour['hotel_booking_cnt']) && $tour['quotation_days'] == $tour['hotel_booking_cnt']) {
                                                                                            $set_flag_booking = 1;
                                                                                            // echo $ok_img;
                                                                                            echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)"><i class="bi bi-check-circle"></i></button>';
                                                                                        } else {
                                                                                            $set_flag_booking = 0;
                                                                                            echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)"><i class="bi bi-x-circle" title="Hotel Booking Not Completed" ></i></button>';
                                                                                        }

                                                                                        if (isset($tour['booking_confirmed_cnt']) && $tour['quotation_days'] == $tour['booking_confirmed_cnt']) {
                                                                                            $set_flag_confirm = 1;
                                                                                            // echo $ok_img;
                                                                                            echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-check-circle"></i></button>';
                                                                                        } else {
                                                                                            $set_flag_confirm = 0;
                                                                                            echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ', \' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-x-circle" title="Booking Confirmed Not Completed" ></i></button>';
                                                                                        }

                                                                                        if (isset($tour['hotel_payment_cnt']) && $tour['quotation_days'] == $tour['hotel_payment_cnt']) {
                                                                                            $set_flag_payment = 1;
                                                                                            // echo $ok_img;
                                                                                            if ($total_doller == $total_paid_doller) {
                                                                                                echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-check-circle"></i></button>';
                                                                                            } else {
                                                                                                echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                            }
                                                                                        } else {
                                                                                            $set_flag_payment = 0;

                                                                                            echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ' ,\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        }
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($hotel_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if ($hotel_only == 1) {
                                                                                    } else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }
                                                                                } else {
                                                                                    $trns_advance = $trns_expenses = $trns_settled = 0;

                                                                                    if (isset($tour['transport_advance']) && $tour['transport_advance'] == 1) {
                                                                                        $trns_advance = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                        // echo $ok_img;
                                                                                    } else {
                                                                                        $trns_advance = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-x-circle-fill" title="Transport Advance Not Completed"></i></button>';
                                                                                    }

                                                                                    // if (isset($tour['transport_advance) && $tour['transport_advance == 1) {
                                                                                    //     $trns_advance = 1;
                                                                                    //     echo $ok_img;
                                                                                    // } else {
                                                                                    if (isset($tour['t_expense_added']) && $tour['t_expense_added']) {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-info me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    } else {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-x-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    }
                                                                                    // }

                                                                                    if (isset($tour['transport_settled']) && $tour['transport_settled'] == 1) {
                                                                                        $trns_settled = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                    } else {
                                                                                        $trns_settled = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-x-circle-fill" title="Transport Settled Not Completed"></i></button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <button class="btn btn-primary btn-sm" onclick="window.open('<?= base_url('tour/print?tour_id=' . $tour['tour_id']); ?>', '_blank')">
                                                                                <i class="bi bi-eye"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card p-4" style="height: 400px; overflow-y: auto;">
                                            <div class="body">
                                                <h4 class="text-center">1st Weeks </h4>
                                                <h6 style="text-align: center;"><?= $day4 . " / " . $week1; ?></h6>
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="mainTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Manual Id</th>
                                                                <th>T Name</th>
                                                                <th>Start & End Date</th>
                                                                <th>Booking</th>
                                                                <th>Hotel Reservation</th>
                                                                <th>Transport</th>
                                                                <th>View Tour</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($oneweek)) : ?>
                                                                <?php $i = 1; ?>
                                                                <?php foreach ($oneweek as $tour) :
                                                                    $hotel_only = $tour['hotel_only'];
                                                                    $transport_only = $tour['transport_only'];

                                                                    $total_doller = $tour['total_hotel_doller'];
                                                                    $total_paid_doller = $tour['pay_doller_count'];
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $tour['manual_id']; ?></td>
                                                                        <td><?= $tour['consignee']; ?></td>
                                                                        <td><?= $tour['start_date'] . "<br> " . $tour['end_date']; ?></td>
                                                                        <td>
                                                                            <?php if ($tour['Confirm'] == 1) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px" class="me-2">
                                                                            <?php } else if ($tour['Confirm'] == 0) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2"><?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($transport_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if($transport_only == 1) {} else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }   
                                                                                } else {
                                                                                    $set_flag_booking = $set_flag_confirm = $set_flag_payment = 0;


                                                                                    $ok_img = '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';

                                                                                    if (isset($tour['quotation_days'])) {
                                                                                        // if (isset($tour['hotel_booking_cnt']) && $tour['quotation_days'] == $tour['hotel_booking_cnt']) {
                                                                                        $set_flag_booking = 1;
                                                                                        // echo $ok_img;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_booking_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                        // } else {
                                                                                        //     $set_flag_booking = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)"><i class="bi bi-x-circle" title="Hotel Booking Not Completed" ></i></button>';
                                                                                        // }

                                                                                        // $set_flag_booking = 1;
                                                                                        //     echo '<button class="btn btn-sm btn-info me-2" onclick="hotelReserve(' . $row->tour_id . ',\'' . addslashes($row->start_date) . '\',\'' . addslashes($row->end_date) . '\', 1)">';
                                                                                        //     echo  $row->hotel_booking_cnt . '/' . intval($row->hotel_count_new);
                                                                                        //     echo '</button>';


                                                                                        // if (isset($tour['booking_confirmed_cnt']) && $tour['quotation_days'] == $tour['booking_confirmed_cnt']) {
                                                                                        //     $set_flag_confirm = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-check-circle"></i></button>';
                                                                                        // } else {
                                                                                        //     $set_flag_confirm = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ', \' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-x-circle" title="Booking Confirmed Not Completed" ></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['booking_confirmed_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';

                                                                                        // if (isset($tour['hotel_payment_cnt']) && $tour['quotation_days'] == $tour['hotel_payment_cnt']) {
                                                                                        //     $set_flag_payment = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     if ($total_doller == $total_paid_doller) {
                                                                                        //         echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-check-circle"></i></button>';
                                                                                        //     } else {
                                                                                        //         echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        //     }
                                                                                        // } else {
                                                                                        //     $set_flag_payment = 0;

                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ' ,\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_payment_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($hotel_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if ($hotel_only == 1) {
                                                                                    } else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }
                                                                                } else {
                                                                                    $trns_advance = $trns_expenses = $trns_settled = 0;

                                                                                    if (isset($tour['transport_advance']) && $tour['transport_advance'] == 1) {
                                                                                        $trns_advance = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                        // echo $ok_img;
                                                                                    } else {
                                                                                        $trns_advance = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-x-circle-fill" title="Transport Advance Not Completed"></i></button>';
                                                                                    }

                                                                                    // if (isset($tour['transport_advance) && $tour['transport_advance == 1) {
                                                                                    //     $trns_advance = 1;
                                                                                    //     echo $ok_img;
                                                                                    // } else {
                                                                                    if (isset($tour['t_expense_added']) && $tour['t_expense_added']) {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-info me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    } else {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-x-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    }
                                                                                    // }                                                                                   

                                                                                    if (isset($tour['transport_settled']) && $tour['transport_settled'] == 1) {
                                                                                        $trns_settled = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                    } else {
                                                                                        $trns_settled = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-x-circle-fill" title="Transport Settled Not Completed"></i></button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <button class="btn btn-primary btn-sm" onclick="window.open('<?= base_url('tour/print?tour_id=' . $tour['tour_id']); ?>', '_blank')">
                                                                                <i class="bi bi-eye"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-6">
                                        <div class="card p-4" style="height: 400px; overflow-y: auto;">
                                            <div class="body">
                                                <h4 class="text-center">2nd Weeks</h4>
                                                <h6 style="text-align: center;"><?= $weekplus1 . " / " . $week2; ?></h6>
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="mainTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Manual Id</th>
                                                                <th>T Name</th>
                                                                <th>Start & End Date</th>
                                                                <th>Booking</th>
                                                                <th>Hotel Reservation</th>
                                                                <th>Transport</th>
                                                                <td>View Tour</td>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($twoweeks)) : ?>
                                                                <?php $i = 1; ?>
                                                                <?php foreach ($twoweeks as $tour) :
                                                                    $hotel_only = $tour['hotel_only'];
                                                                    $transport_only = $tour['transport_only'];

                                                                    $total_doller = $tour['total_hotel_doller'];
                                                                    $total_paid_doller = $tour['pay_doller_count'];
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $tour['manual_id']; ?></td>
                                                                        <td><?= $tour['consignee']; ?></td>
                                                                        <td><?= $tour['start_date'] . "<br> " . $tour['end_date']; ?></td>
                                                                        <td>
                                                                            <?php if ($tour['Confirm'] == 1) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px" class="me-2">
                                                                            <?php } else if ($tour['Confirm'] == 0) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2"><?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($transport_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if($transport_only == 1) {} else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }   
                                                                                } else {
                                                                                    $set_flag_booking = $set_flag_confirm = $set_flag_payment = 0;


                                                                                    $ok_img = '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';

                                                                                    if (isset($tour['quotation_days'])) {
                                                                                        // if (isset($tour['hotel_booking_cnt']) && $tour['quotation_days'] == $tour['hotel_booking_cnt']) {
                                                                                        $set_flag_booking = 1;
                                                                                        // echo $ok_img;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_booking_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                        // } else {
                                                                                        //     $set_flag_booking = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)"><i class="bi bi-x-circle" title="Hotel Booking Not Completed" ></i></button>';
                                                                                        // }

                                                                                        // $set_flag_booking = 1;
                                                                                        //     echo '<button class="btn btn-sm btn-info me-2" onclick="hotelReserve(' . $row->tour_id . ',\'' . addslashes($row->start_date) . '\',\'' . addslashes($row->end_date) . '\', 1)">';
                                                                                        //     echo  $row->hotel_booking_cnt . '/' . intval($row->hotel_count_new);
                                                                                        //     echo '</button>';


                                                                                        // if (isset($tour['booking_confirmed_cnt']) && $tour['quotation_days'] == $tour['booking_confirmed_cnt']) {
                                                                                        //     $set_flag_confirm = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-check-circle"></i></button>';
                                                                                        // } else {
                                                                                        //     $set_flag_confirm = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ', \' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-x-circle" title="Booking Confirmed Not Completed" ></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['booking_confirmed_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';

                                                                                        // if (isset($tour['hotel_payment_cnt']) && $tour['quotation_days'] == $tour['hotel_payment_cnt']) {
                                                                                        //     $set_flag_payment = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     if ($total_doller == $total_paid_doller) {
                                                                                        //         echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-check-circle"></i></button>';
                                                                                        //     } else {
                                                                                        //         echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        //     }
                                                                                        // } else {
                                                                                        //     $set_flag_payment = 0;

                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ' ,\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_payment_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($hotel_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if ($hotel_only == 1) {
                                                                                    } else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }
                                                                                } else {
                                                                                    $trns_advance = $trns_expenses = $trns_settled = 0;

                                                                                    if (isset($tour['transport_advance']) && $tour['transport_advance'] == 1) {
                                                                                        $trns_advance = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                        // echo $ok_img;
                                                                                    } else {
                                                                                        $trns_advance = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-x-circle-fill" title="Transport Advance Not Completed"></i></button>';
                                                                                    }

                                                                                    // if (isset($tour['transport_advance) && $tour['transport_advance == 1) {
                                                                                    //     $trns_advance = 1;
                                                                                    //     echo $ok_img;
                                                                                    // } else {
                                                                                    if (isset($tour['t_expense_added']) && $tour['t_expense_added']) {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-info me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    } else {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-x-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    }
                                                                                    // }

                                                                                    if (isset($tour['transport_settled']) && $tour['transport_settled'] == 1) {
                                                                                        $trns_settled = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                    } else {
                                                                                        $trns_settled = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-x-circle-fill" title="Transport Settled Not Completed"></i></button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <button class="btn btn-primary btn-sm" onclick="window.open('<?= base_url('tour/print?tour_id=' . $tour['tour_id']); ?>', '_blank')">
                                                                                <i class="bi bi-eye"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card p-4" style="height: 400px; overflow-y: auto;">
                                            <div class="body">
                                                <h4 class="text-center">3rd Weeks</h4>
                                                <h6 style="text-align: center;"><?= $week2plus1 . " / " . $week3; ?></h6>
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="mainTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Manual Id</th>
                                                                <th>T Name</th>
                                                                <th>Start & End Date</th>
                                                                <th>Booking</th>
                                                                <th>Hotel Reservation</th>
                                                                <th>Transport</th>
                                                                <th>View Tour</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($threeweeks)) : ?>
                                                                <?php $i = 1; ?>
                                                                <?php foreach ($threeweeks as $tour) :
                                                                    $hotel_only = $tour['hotel_only'];
                                                                    $transport_only = $tour['transport_only'];

                                                                    $total_doller = $tour['total_hotel_doller'];
                                                                    $total_paid_doller = $tour['pay_doller_count'];
                                                                ?>
                                                                    <tr>
                                                                        <td><?= $i++; ?></td>
                                                                        <td><?= $tour['manual_id']; ?></td>
                                                                        <td><?= $tour['consignee']; ?></td>
                                                                        <td><?= $tour['start_date'] . "<br> " . $tour['end_date']; ?></td>
                                                                        <td>
                                                                            <?php if ($tour['Confirm'] == 1) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px" class="me-2">
                                                                            <?php } else if ($tour['Confirm'] == 0) { ?>
                                                                                <img src="<?php echo base_url(); ?>assets/images/close.png" alt="..." width="20px" class="me-2"><?php } ?>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($transport_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if($transport_only == 1) {} else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }   
                                                                                } else {
                                                                                    $set_flag_booking = $set_flag_confirm = $set_flag_payment = 0;


                                                                                    $ok_img = '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';

                                                                                    if (isset($tour['quotation_days'])) {
                                                                                        // if (isset($tour['hotel_booking_cnt']) && $tour['quotation_days'] == $tour['hotel_booking_cnt']) {
                                                                                        $set_flag_booking = 1;
                                                                                        // echo $ok_img;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_booking_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                        // } else {
                                                                                        //     $set_flag_booking = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)"><i class="bi bi-x-circle" title="Hotel Booking Not Completed" ></i></button>';
                                                                                        // }

                                                                                        // $set_flag_booking = 1;
                                                                                        //     echo '<button class="btn btn-sm btn-info me-2" onclick="hotelReserve(' . $row->tour_id . ',\'' . addslashes($row->start_date) . '\',\'' . addslashes($row->end_date) . '\', 1)">';
                                                                                        //     echo  $row->hotel_booking_cnt . '/' . intval($row->hotel_count_new);
                                                                                        //     echo '</button>';


                                                                                        // if (isset($tour['booking_confirmed_cnt']) && $tour['quotation_days'] == $tour['booking_confirmed_cnt']) {
                                                                                        //     $set_flag_confirm = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-check-circle"></i></button>';
                                                                                        // } else {
                                                                                        //     $set_flag_confirm = 0;
                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ', \' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 2)"><i class="bi bi-x-circle" title="Booking Confirmed Not Completed" ></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['booking_confirmed_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';

                                                                                        // if (isset($tour['hotel_payment_cnt']) && $tour['quotation_days'] == $tour['hotel_payment_cnt']) {
                                                                                        //     $set_flag_payment = 1;
                                                                                        //     // echo $ok_img;
                                                                                        //     if ($total_doller == $total_paid_doller) {
                                                                                        //         echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-check-circle"></i></button>';
                                                                                        //     } else {
                                                                                        //         echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        //     }
                                                                                        // } else {
                                                                                        //     $set_flag_payment = 0;

                                                                                        //     echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $tour['tour_id'] . ' ,\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                        // }
                                                                                        $set_flag_payment = 0;
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $tour['tour_id'] . ',\' ' . addslashes($tour['start_date']) . '\',\' ' . addslashes($tour['end_date']) . '\', 1)">';
                                                                                        echo  $tour['hotel_payment_cnt'] . '/' . intval($tour['hotel_count_new']);
                                                                                        echo '</button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="d-flex align-items-center">
                                                                                <?php
                                                                                if ($hotel_only == 1 || $tour['Confirm'] == 3) {

                                                                                    if ($hotel_only == 1) {
                                                                                    } else if ($tour['Confirm'] == 3) {
                                                                                        echo '<button type="button" class="btn btn-danger btn-sm" title="Tour Cancelled">Tour Cancelled</button>';
                                                                                    }
                                                                                } else {
                                                                                    $trns_advance = $trns_expenses = $trns_settled = 0;

                                                                                    if (isset($tour['transport_advance']) && $tour['transport_advance'] == 1) {
                                                                                        $trns_advance = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                        // echo $ok_img;
                                                                                    } else {
                                                                                        $trns_advance = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 1)"><i class="bi bi-x-circle-fill" title="Transport Advance Not Completed"></i></button>';
                                                                                    }

                                                                                    // if (isset($tour['transport_advance) && $tour['transport_advance == 1) {
                                                                                    //     $trns_advance = 1;
                                                                                    //     echo $ok_img;
                                                                                    // } else {
                                                                                    if (isset($tour['t_expense_added']) && $tour['t_expense_added']) {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-info me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    } else {
                                                                                        $trns_expenses = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 2)"><i class="bi bi-x-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                                    }
                                                                                    // }

                                                                                    if (isset($tour['transport_settled']) && $tour['transport_settled'] == 1) {
                                                                                        $trns_settled = 1;
                                                                                        echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                    } else {
                                                                                        $trns_settled = 0;
                                                                                        echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                            $tour['tour_id'] . ', \'' . addslashes($tour['start_date']) . '\', \'' .
                                                                                            addslashes($tour['end_date']) . '\', \'' .
                                                                                            addslashes($tour['consignee']) . '\', ' .
                                                                                            $tour['pax_count'] . ', 3)"><i class="bi bi-x-circle-fill" title="Transport Settled Not Completed"></i></button>';
                                                                                    }
                                                                                }
                                                                                ?>

                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <button class="btn btn-primary btn-sm" onclick="window.open('<?= base_url('tour/print?tour_id=' . $tour['tour_id']); ?>', '_blank')">
                                                                                <i class="bi bi-eye"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>

                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


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
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        // var report_title = 'Supplier Report';

        // $('#mainTable').DataTable({
        //     "aaSorting": [],

        //     rowReorder: {
        //         selector: 'td:nth-child(2)'
        //     },
        //     responsive: 'true',
        //     dom: "Bfrtip",
        //     buttons: [{
        //             extend: 'csvHtml5',
        //             text: 'CSV',
        //             titleAttr: 'CSV',
        //             title: report_title,
        //             exportOptions: {
        //                 columns: [0, 1, 2, 3]
        //             }
        //         },
        //         {
        //             extend: 'pdfHtml5',
        //             text: 'PDF',
        //             titleAttr: 'PDF',
        //             title: report_title,
        //             orientation: 'portrait', // Set orientation to landscape
        //             pageSize: 'A4', // Set page size to A4
        //             exportOptions: {
        //                 columns: [0, 1, 2, 3]
        //             },
        //             customize: function(win) {}
        //         },
        //         {
        //             extend: 'print',
        //             text: 'Print',
        //             titleAttr: 'Print',
        //             title: report_title,
        //             pageSize: 'A4',
        //             orientation: 'portrait',
        //             exportOptions: {
        //                 columns: [0, 1, 2, 3]
        //             }

        //         },
        //     ]
        // });
    </script>
    <script>
        function isNumberKey(evt, obj) {
            var charCode = (evt.which) ? evt.which : event.keyCode
            var value = obj.value;
            var dotcontains = value.indexOf(".") != -1;
            if (dotcontains)
                if (charCode == 46) return false;
            if (charCode == 46) return true;
            if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;
            return true;
        }

        $(function() {
            $(':input[required=""],:input[required]').bind('focusout', function() {
                if ($(this).val() == "") {
                    $(this).css("border-color", "red");
                } else {
                    $(this).css("border-color", "#ccc");
                }
            });

            $('.txt_nav:first').focus();
            var $inp = $('.txt_nav');
            $inp.bind('keydown', function(e) {
                var n = $inp.length;
                var key = e.which;
                if (key == 13) {
                    e.preventDefault();
                    var nxtIdx = $inp.index(this) + 1;
                    if (nxtIdx < n) {
                        $('.txt_nav')[nxtIdx].focus();
                    } else {
                        $('.txt_nav')[nxtIdx - 1].blur();
                        $('#submit_btn').focus();
                    }
                }
            });


            $("#submit_btn").keypress(function(event) {
                if (event.which == 13) {
                    req_input_check();
                }
            });

        });

        function req_input_check() {
            $(':input[required=""],:input[required]').bind('keypress', function(event) {
                if (event.which == 13) {
                    if ($(this).val() == "") {
                        $(this).css("border-color", "red");
                    } else {
                        $(this).css("border-color", "#ccc");
                        $('#form_submit').submit();
                    }
                }
            });
        }

        $('#exampleCheck').change(function() {
            if ($(this).is(':checked')) {
                $("#Services").removeAttr("hidden");
                $("#Services1").removeAttr("hidden");
            } else {
                $("#Services").attr('hidden', true);
                $("#Services1").attr('hidden', true);
            }
        });

        $("#date_form").on('submit', function(e) {
            e.preventDefault();

            var sup_cat = $("#sup_cat").val();

            if (sup_cat) {
                window.location.href = "<?php echo base_url(); ?>tour/tour_arrival?cat=" + sup_cat;
                return false;
            }

        });

        /**************************** INSERT  ****************************/

        $("#form_submit").on('submit', (function(e) {
            e.preventDefault();
            $.ajax({
                url: "<?php echo base_url() ?>master_files/supplier/create",
                type: "POST",
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    console.log(response);
                    if (response.status == 201) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    }
                },
                error: function(e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                    });
                }
            });
        }));

        // .delete-menu-item-btn click event
        $(document).on('click', '.delete-menu-item-btn', function() {
            var acc_no = $(this).data('menu-item-id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this supplier!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?php echo base_url() ?>master_files/supplier/delete",
                        type: "GET",
                        data: {
                            Acc_No: acc_no
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                            });
                        }
                    });
                }
            });
        });
    </script>
</body>




</html>