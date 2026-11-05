<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <?php
        $reservation = $confirm = $payment = '';
        $reservation_body = $confirm_body = $payment_body = '';
        if ($type == 1) {
            $reservation = 'active';
            $reservation_body = 'show active';
        } else if ($type == 2) {
            $confirm = 'active';
            $confirm_body = 'show active';
        } else {
            $payment = 'active';
            $payment_body = 'show active';
        }

        ?>
        <script>
            // $(document).ready(function() {
            //     var tourCondition = <?php //echo json_encode($tour_condition);
                                        ?>;
            //     var tourSaved = <?php //echo json_encode($tour_saved);
                                    ?>;


            //     var urlParams = new URLSearchParams(window.location.search);
            //     var startParam = urlParams.get('start');

            //     var start = new Date(startParam);

            //     var year = start.getFullYear();
            //     var month = start.getMonth() + 1;

            //     var message = '';

            //     if(tourCondition[0]['transport_only'] == 1){
            //         message = 'Please note that this tour only includes transport facilities.';
            //     }

            //     if(tourSaved[0]['save_flag'] == 0){
            //         message = 'The tour is currently not planned as expected. Please review the itinerary and make the necessary adjustments to ensure it aligns with the intended schedule.';
            //     }

            //     if((tourCondition[0]['transport_only'] == 1) || (tourSaved[0]['save_flag'] == 0)){
            //         Swal.fire({
            //             icon: 'error',
            //             title: 'Oops...',
            //             text: message,
            //         });

            //         setTimeout(() => {
            //             window.location.href = '<?php //echo base_url();
                                                    ?>tour/tour_dashboard/monthly_dashboard?year=' + year + '&month=' + month;
            //         }, 2000);
            //     }
            // });
        </script>

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
                                        <h4 class="p-title">Booking Details</h4>
                                    </div>
                                </div>


                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="col-md-12">
                                            <div class="card p-3">
                                                <div class="body">
                                                    <div class="row">
                                                        <?php
                                                        $consignee = [];
                                                        foreach ($tour_main_details as $detail) {
                                                            $consignee = $detail;
                                                        ?>

                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Tour Name</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_consignee"><?php echo $detail['consignee'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Agent</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_agent_name"><?php echo $detail['agent_name'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Passengers</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_passenger_count"><?php echo $detail['passenger_count'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Start Date</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_start_date"><?php echo $detail['start_date'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">End Date</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_end_date"><?php echo $detail['end_date'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Manual ID</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_manual_id"><?php echo $detail['manual_id'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="finalize_txt" id="finalize_txt"
                                                                value="<?php echo $detail['finalize_txt'] ?>">
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-xxl-12">
                                        <!-- <h5 class="mb-3">Custom Hover Tabs</h5> -->
                                        <div class="card">
                                            <!-- <div class="card-body">
                                        <p class="text-muted"></p>
                                    </div> -->
                                            <div class="border">
                                                <ul class="nav nav-pills custom-hover-nav-tabs">
                                                    <li class="nav-item">
                                                        <a href="#hotel-booking" data-bs-toggle="tab" aria-expanded="false"
                                                            class="nav-link <?php echo $reservation ?>">
                                                            <i class="bi bi-envelope-arrow-up nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Reservation</h5>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="#booking-confirm" data-bs-toggle="tab" aria-expanded="true"
                                                            class="nav-link <?php echo $confirm ?>">
                                                            <i class="bi bi-check-circle nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Booking Confirm</h5>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="#hotel-payment" data-bs-toggle="tab" aria-expanded="false"
                                                            class="nav-link <?php echo $payment ?>">
                                                            <i class="bi bi-wallet2 nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Hotel Payment</h5>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="card-body">
                                                <div class="tab-content text-muted">
                                                    <div class="tab-pane <?php echo $reservation_body ?>"
                                                        id="hotel-booking">
                                                        <div class="table-responsive">
                                                            <table class="table mb-0" style="color: #212529;">
                                                                <h6>Reservation</h6>
                                                                <thead>
                                                                    <tr>
                                                                        <th scope="col">#</th>
                                                                        <th scope="col">Days</th>
                                                                        <th scope="col">Hotel Name</th>
                                                                        <th scope="col">Room Type</th>
                                                                        <th Scope="col">Meal / Room Count</th>
                                                                        <th scope="col">Email</th>
                                                                        <th scope="col">Inbox</th>

                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $i = 1;
                                                                    foreach ($tour_details2 as $row) {
                                                                        // $row['stay_dates'] is a string like '2025-08-20,2025-08-21,2025-08-24'
                                                                        $datesArr = explode(',', $row['stay_dates']);
                                                                        $min = $datesArr[0];
                                                                        $max = end($datesArr);

                                                                        // if ($min == $max) {
                                                                        //     $daterang = $min;
                                                                        // } else {
                                                                        //     $daterang = $min . "  -  " . $max;
                                                                        // }

                                                                        $meal_code = $row['meal_code'];
                                                                        if ($meal_code == 'bb') {
                                                                            $meal = 'BB';
                                                                        } else if ($meal_code == 'hb') {
                                                                            $meal = 'HB';
                                                                        } else if ($meal_code == 'fb') {
                                                                            $meal = 'FB';
                                                                        } else {
                                                                            $meal = '';
                                                                        }

                                                                    ?>
                                                                        <tr class="align-middle">
                                                                            <td><?= $i++; ?></td>
                                                                            
                                                                            <?php
                                                                                // Group successive dates in $row['stay_dates']
                                                                                $datesArr = explode(',', $row['stay_dates']);
                                                                                $groups = [];
                                                                                $start = $end = null;

                                                                                foreach ($datesArr as $date) {
                                                                                    if ($start === null) {
                                                                                        $start = $end = $date;
                                                                                    } elseif (date('Y-m-d', strtotime($end . ' +1 day')) === $date) {
                                                                                        $end = $date;
                                                                                    } else {
                                                                                        $groups[] = [$start, $end];
                                                                                        $start = $end = $date;
                                                                                    }
                                                                                }
                                                                                if ($start !== null) {
                                                                                    $groups[] = [$start, $end];
                                                                                }

                                                                                $display = [];
                                                                                foreach ($groups as $g) {
                                                                                    if ($g[0] === $g[1]) {
                                                                                        $display[] = date('d M Y', strtotime($g[0]));
                                                                                    } else {
                                                                                        $display[] = date('d M Y', strtotime($g[0])) . ' - ' . date('d M Y', strtotime($g[1]));
                                                                                    }
                                                                                }
                                                                            ?>
                                                                            <td><?= implode('<br>', $display) ?></td>
                                                                            <td><?= $row['hotel_name'] ?></td>
                                                                            <td><?= $row['rtype'] ?></td>
                                                                            <td style="width: 10%;">
                                                                                <div class="d-flex flex-column">
                                                                                    <strong><?php echo $meal; ?></strong>
                                                                                    <div class="d-flex justify-content-between">
                                                                                        <small class="text-secondary">
                                                                                            SGL </small>
                                                                                        <small class="text-secondary">
                                                                                            -> </small>
                                                                                        <small class="text-secondary"><?php echo $consignee['sgl']; ?></small>
                                                                                    </div>
                                                                                    <div class="d-flex justify-content-between">
                                                                                        <small class="text-secondary">
                                                                                            DBL </small>
                                                                                        <small class="text-secondary">
                                                                                            -> </small>
                                                                                        <small class="text-secondary"><?php echo $consignee['dbl']; ?></small>
                                                                                    </div>
                                                                                    <div class="d-flex justify-content-between">
                                                                                        <small class="text-secondary">
                                                                                            TPL </small>
                                                                                        <small class="text-secondary">
                                                                                            -> </small>
                                                                                        <small class="text-secondary"><?php echo $consignee['tpl']; ?></small>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                            <td style="width: 5%;">
                                                                                <button type="button"
                                                                                    class="btn btn btn-info btn-sm open_em_modal"
                                                                                    data-id="<?= $row['id'] ?>"
                                                                                    data-tour-id="<?= $row['quotation_id'] ?>"
                                                                                    data-manual-id="<?= $consignee['manual_id'] ?>"
                                                                                    data-consignee="<?= $consignee['consignee'] ?>"
                                                                                    data-from-date="<?= $min ?>"
                                                                                    data-to-date="<?= $max ?>"
                                                                                    data-hotel="<?= $row['hotel_name'] ?>"
                                                                                    data-hotel-code="<?= $row['hotel_acc'] ?>"
                                                                                    data-meal-code="<?= $meal ?>"
                                                                                    data-sgl="<?= $consignee['sgl'] ?>"
                                                                                    data-dbl="<?= $consignee['dbl'] ?>"
                                                                                    data-tpl="<?= $consignee['tpl'] ?>"
                                                                                    data-sgl-rate="<?= $row['sgl'] ?>"
                                                                                    data-dbl-rate="<?= $row['dbl'] ?>"
                                                                                    data-tpl-rate="<?= $row['tpl'] ?>"
                                                                                    data-room-type="<?= $row['rtype'] ?>">
                                                                                    Email
                                                                                </button>
                                                                            </td>
                                                                            <td style="width: 5%;">
                                                                                <!-- <button type="button"
                                                                                    class="btn btn btn-info <? //=
                                                                                                            //(!$row['unread_mails']) ? '' : 'btn-danger'
                                                                                                            ?> btn-sm read_mail"
                                                                                    data-id="<? //= $row['id'] 
                                                                                                ?>"
                                                                                    <? //=
                                                                                    //(!$row['unread_mails']) ? 'disabled' : ''
                                                                                    ?>>
                                                                                    Read
                                                                                </button> -->
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                    }
                                                                    ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane <?php echo $confirm_body ?>" id="booking-confirm">
                                                        <h6>Booking Confirm</h6>
                                                        <div class="table-responsive">
                                                            <table class="table mb-0" style="color: #212529;">
                                                                <thead>
                                                                    <tr>
                                                                        <th scope="col">#</th>
                                                                        <th scope="col">Day</th>
                                                                        <th scope="col">Hotel Name</th>
                                                                        <th scope="col">Confirm</th>
                                                                        <th scope="col">Cancel</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $i = 1;
                                                                    foreach ($tour_details as $row) {
                                                                        // $min = $row['min_date'];
                                                                        // $max = $row['max_date'];
                                                                        // if ($min == $max) {
                                                                        //     $daterang = $row['min_date'] = $min;
                                                                        // } else {
                                                                        //     $daterang = $row['min_date'] = $min . "  -  " . $max;
                                                                        // }
                                                                    ?>
                                                                        <tr>
                                                                            <td><?= $i++; ?></td>
                                                                            <td><?= $row['tour_date'] ?></td>
                                                                            <td><?= $row['hotel_name'] ?></td>
                                                                            <td>
                                                                                <?php if ($row['booking_confirmed_flag'] == 0) { ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-info btn-sm hotel_confirm_modal_btn"
                                                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                                                        data-tour-date="<?= $row['tour_date'] ?>"
                                                                                        data-date-number="<?= $row['quotation_day'] ?>"
                                                                                        data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                        data-tolocation="<?= $row['to_location'] ?>">
                                                                                        Confirm
                                                                                    </button>
                                                                                <?php } else if ($row['booking_confirmed_flag'] == 1) { ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-success btn-sm"
                                                                                        disabled>
                                                                                        <i class="bi bi-check-square-fill me-1"></i>
                                                                                        Confirmed
                                                                                    </button>
                                                                                <?php } else if ($row['booking_confirmed_flag'] == 2) { ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-info btn-sm hotel_confirm_modal_btn"
                                                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                                                        data-tour-date="<?= $row['tour_date'] ?>"
                                                                                        data-date-number="<?= $row['quotation_day'] ?>"
                                                                                        data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                        data-tolocation="<?= $row['to_location'] ?>">
                                                                                        Confirm
                                                                                    </button>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-danger btn-sm"
                                                                                        disabled>
                                                                                        <i class="bi bi-check-square-fill me-1"></i>
                                                                                        Rejected
                                                                                    </button>
                                                                                <?php } else if ($row['booking_confirmed_flag'] == 3) { ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-info btn-sm hotel_confirm_modal_btn"
                                                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                                                        data-tour-date="<?= $row['tour_date'] ?>"
                                                                                        data-date-number="<?= $row['quotation_day'] ?>"
                                                                                        data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                        data-tolocation="<?= $row['to_location'] ?>">
                                                                                        Confirm
                                                                                    </button>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-danger btn-sm"
                                                                                        disabled>
                                                                                        <i class="bi bi-check-square-fill me-1"></i>
                                                                                        Cancelled
                                                                                    </button>
                                                                                <?php } ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($row['booking_confirmed_flag'] != 0) { ?>
                                                                                    <button type="button"
                                                                                        class="btn btn-danger btn-sm cancel_booking_btn"
                                                                                        data-id="<?= $row['id'] ?>"
                                                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                        data-tour-date="<?= $row['tour_date'] ?>"
                                                                                        data-date-number="<?= $row['quotation_day'] ?>">
                                                                                        Cancel
                                                                                    </button>
                                                                                <?php } ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                    }
                                                                    ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane <?php echo $payment_body ?>" id="hotel-payment">
                                                        <h6>Hotel Payment</h6>
                                                        <div class="table-responsive">
                                                            <table class="table mb-0" style="color: #212529;">
                                                                <thead>
                                                                    <tr>
                                                                        <th scope="col">#</th>
                                                                        <th scope="col">Day</th>
                                                                        <th scope="col">Hotel Name</th>
                                                                        <th scope="col">Payment</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $i = 1;
                                                                    foreach ($tour_details as $row) {
                                                                        // $min = $row['min_date'];
                                                                        // $max = $row['max_date'];
                                                                        // if ($min == $max) {
                                                                        //     $daterang = $row['min_date'] = $min;
                                                                        // } else {
                                                                        //     $daterang = $row['min_date'] = $min . "  -  " . $max;
                                                                        // }

                                                                        $total_hotel_doller = $row['total_hotel_doller'];
                                                                        $pay_doller_count = $row['pay_doller_count'];

                                                                        $status = $row['hotel_payment_flag'];

                                                                        $bal_amt = $total_hotel_doller - $pay_doller_count;

                                                                        if ($total_hotel_doller == $pay_doller_count) {
                                                                            $bal = true;
                                                                        } else {
                                                                            $bal = false;
                                                                        }

                                                                        // if($total_hotel_doller <= $pay_doller_count){
                                                                        //     $text = 'Over Payment';
                                                                        // } else {
                                                                        //     $text = '';
                                                                        // }
                                                                    ?>
                                                                        <tr>
                                                                            <td><?= $i++; ?></td>
                                                                            <td><?= $row['tour_date'] ?></td>
                                                                            <td><?= $row['hotel_name'] ?></td>
                                                                            <td>
                                                                                <?php if ($row['hotel_payment_flag'] == 0) {
                                                                                ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-info btn-sm hotel_payment_modal"
                                                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                                                        data-tour-date="<?= $row['tour_date'] ?>"
                                                                                        data-date-number="<?= $row['quotation_day'] ?>"
                                                                                        data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                        data-tolocation="<?= $row['to_location'] ?>"
                                                                                        data-bal="<?= $bal_amt ?>"
                                                                                        data-manu="<?= $detail['manual_id'] ?>"
                                                                                        data-status="<?= $status ?>">
                                                                                        Pay
                                                                                    </button>
                                                                                    <?php
                                                                                } else if ($row['hotel_payment_flag'] == 1) {
                                                                                    if ($bal != 1) {
                                                                                    ?>
                                                                                        <button type="button"
                                                                                            class="btn btn btn-info btn-sm hotel_payment_modal"
                                                                                            data-tour="<?= $row['quotation_id'] ?>"
                                                                                            data-tour-date="<?= $row['tour_date'] ?>"
                                                                                            data-date-number="<?= $row['quotation_day'] ?>"
                                                                                            data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                                                            data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                            data-tolocation="<?= $row['to_location'] ?>"
                                                                                            data-bal="<?= $bal_amt ?>"
                                                                                            data-manu="<?= $detail['manual_id'] ?>"
                                                                                            data-status="<?= $status ?>">
                                                                                            Pay
                                                                                        </button>
                                                                                    <?php } ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-success btn-sm"
                                                                                        disabled>
                                                                                        <i class="bi bi-check-square-fill me-1"></i>
                                                                                        Paid
                                                                                    </button>
                                                                                <?php } else
                                                                            if ($row['hotel_payment_flag'] == 2) {
                                                                                ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-info btn-sm hotel_payment_modal"
                                                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                                                        data-tour-date="<?= $row['tour_date'] ?>"
                                                                                        data-date-number="<?= $row['quotation_day'] ?>"
                                                                                        data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                        data-tolocation="<?= $row['to_location'] ?>"
                                                                                        data-bal="<?= $bal_amt ?>"
                                                                                        data-manu="<?= $detail['manual_id'] ?>"
                                                                                        data-status="<?= $status ?>">
                                                                                        Pay
                                                                                    </button>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-danger btn-sm"
                                                                                        disabled>
                                                                                        <i class="bi bi-check-square-fill me-1"></i>
                                                                                        Rejected
                                                                                    </button>
                                                                                <?php
                                                                                } else
                                                                                if ($row['hotel_payment_flag'] == 3) { ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-info btn-sm hotel_payment_modal"
                                                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                                                        data-tour-date="<?= $row['tour_date'] ?>"
                                                                                        data-date-number="<?= $row['quotation_day'] ?>"
                                                                                        data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                        data-tolocation="<?= $row['to_location'] ?>"
                                                                                        data-bal="<?= $bal_amt ?>"
                                                                                        data-manu="<?= $detail['manual_id'] ?>"
                                                                                        data-status="<?= $status ?>">
                                                                                        Pay
                                                                                    </button>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-danger btn-sm"
                                                                                        disabled>
                                                                                        <i class="bi bi-check-square-fill me-1"></i>
                                                                                        Cancelled
                                                                                    </button>
                                                                                    <?php } else
                                                                                    if ($row['hotel_payment_flag'] == 10) {
                                                                                    if ($bal != 1) {
                                                                                    ?>
                                                                                        <button type="button"
                                                                                            class="btn btn btn-info btn-sm hotel_payment_modal"
                                                                                            data-tour="<?= $row['quotation_id'] ?>"
                                                                                            data-tour-date="<?= $row['tour_date'] ?>"
                                                                                            data-date-number="<?= $row['quotation_day'] ?>"
                                                                                            data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                                                            data-existhotel="<?= $row['hotel_acc'] ?>"
                                                                                            data-tolocation="<?= $row['to_location'] ?>"
                                                                                            data-bal="<?= $bal_amt ?>"
                                                                                            data-manu="<?= $detail['manual_id'] ?>"
                                                                                            data-status="<?= $status ?>">
                                                                                            Pay
                                                                                        </button>
                                                                                    <?php } ?>
                                                                                    <button type="button"
                                                                                        class="btn btn btn-warning btn-sm"
                                                                                        disabled>
                                                                                        <i class="bi bi-check-square-fill me-1"></i>
                                                                                        Pending
                                                                                    </button>
                                                                                <?php
                                                                                } ?>
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
                                            </div><!-- end card-body -->
                                        </div>
                                    </div>
                                    <!--end col-->

                                    <!--end col-->

                                </div>


                            </div> <!-- end .h-100-->

                        </div> <!-- end col -->
                    </div>

                </div>
                <!-- container-fluid -->

                <div class="email-detail-content">
                    <div class="p-4 d-flex flex-column h-100">
                        <div id="email-data-panel">

                        </div>
                    </div>
                </div>

            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <div class="modal fade" id="HotelConfirmedModal" tabindex="-1" aria-labelledby="HotelConfirmedModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="HotelConfirmedModalLabel">Confirm Hotel</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" class="confirm_hotel_form" id="confirm_hotel_form">
                            <div class="row">
                                <div>
                                    <span class="text-primary">Reservation Details</span>
                                    <hr>
                                </div>
                                <div class="mb-4">
                                    <input type="hidden" name="tour_id" id="c_tour_id">
                                    <input type="hidden" name="old_hotel" id="c_old_hotel">
                                    <input type="hidden" name="tour_date" id="c_tour_date">
                                    <!-- <input type="text" name="id" id="c_id"> -->
                                    <input type="hidden" name="date_number" id="date_number">
                                    <div class="row">
                                        <div class="col-md-6 d-none d-none">
                                            <label class="form-label d-none" for="select_location">Destination</label>
                                            <select name="select_location" id="select_location"
                                                class="select_location form-control mb-3 d-none" style="width: 100%;">
                                                <option value="0">Select a Destination...</option>
                                                <?php
                                                foreach ($hotel_location as $id) {
                                                    echo '<option value="' . $id['Address'] . '" >' . $id['Address'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="alert alert-warning py-1">
                                                <span class="text-black">Selected Destination : <code
                                                        id="slct_location"></code></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 d-none">
                                            <label class="form-label d-none" for="select_hotel">Hotel</label>
                                            <select name="select_hotel" id="select_hotel"
                                                class="select_hotel form-control d-none"
                                                style="width: 100%;">
                                                <!-- <select name="select_hotel" id="select_hotel" class="select_hotel form-control d-none"
                                            style="width: 100%;" onchange="assign_hotel(this.value)"> -->
                                                <option value="0">Select a Hotel...</option>
                                                <?php

                                                ?>
                                            </select>
                                            <input type="hidden" id="slct_hotel_id" name="slct_hotel_id">
                                            <input type="hidden" id="slct_hotel_name" name="slct_hotel_name">
                                        </div>
                                        <div class="col-md-12">
                                            <div class="alert alert-warning py-1">
                                                <span class="text-black">Selected Hotel : <code
                                                        id="slct_hotel"></code></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6 d-none">
                                            <div>
                                                <label class="form-label d-none" for="room_type">Room Type</label>
                                                <!-- <input class="form-control" type="text" id="room_type" name="room_type" placeholder="" required readonly /> -->
                                                <select class="room_type form-control d-none" style="width: 100%;"
                                                    id="room_type"
                                                    name="room_type">
                                                    <option value="">Select a Room Type...</option>
                                                </select>
                                                <input type="hidden" id="room_type_id" name="room_type_id" placeholder="" />
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="alert alert-warning py-1">
                                                <span class="text-black">Selected Room Type : <code
                                                        id="slct_room_type"></code></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div>
                                                <label class="form-label" for="meal_code">Meal Code</label>
                                                <select name="meal_code" id="meal_code" class="form-control"
                                                    style="width: 100%;" required
                                                    onchange="mealCodeChanged(this.value);" disabled>
                                                    <option value="0"></option>
                                                    <option value="bb">BB</option>
                                                    <option value="hb">HB</option>
                                                    <option value="fb">FB</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <span class="text-black">Room Rates</span>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted" for="sgl">SGL</label>
                                            <input class="form-control" type="text" id="sgl" name="sgl" placeholder=""
                                                readonly />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted" for="dbl">DBL</label>
                                            <input class="form-control" type="text" id="dbl" name="dbl" placeholder=""
                                                readonly />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted" for="tpl">TPL</label>
                                            <input class="form-control" type="text" id="tpl" name="tpl" placeholder=""
                                                readonly />
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <span class="text-black">Room Count</span>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted" for="">SGL</label>
                                            <input class="form-control" type="text" id="sglcount" name="sglcount"
                                                placeholder="" readonly />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted" for="">DBL</label>
                                            <input class="form-control" type="text" id="dblcount" name="dblcount"
                                                placeholder="" readonly />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-muted" for="">TPL</label>
                                            <input class="form-control" type="text" id="tplcount" name="tplcount"
                                                placeholder="" readonly />
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="extra">Extra Fee</label>
                                        <input class="form-control txt_nav" type="text" id="p_extra" name="extra"
                                            value="0.00" placeholder="" />
                                    </div>
                                    <input type="hidden" id="tot_doller" name="tot_doller">
                                    <div class="mb-3">
                                        <label class="form-label" for="no_of_room">Doller Rate<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="confirm_p_rate"
                                            name="confirm_p_rate" placeholder="" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="confirm_doller">Total Amount($)<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="confirm_doller"
                                            name="confirm_doller" placeholder="" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="confirm_lkr">Total Amount(LKR)<font
                                                color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="confirm_lkr" name="confirm_lkr"
                                            placeholder="" required />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="dates">Reserved For - Dates</label>
                                        <input class="form-control txt_nav" type="text" id="c_dates" name="dates"
                                            placeholder="" readonly />
                                        <input type="hidden" id="c_dates_count" name="c_dates_count" />
                                        <div id="datesContainer"></div>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-primary">Reservation Confirmation Details</span>
                                    <hr>
                                </div>
                                <div class="row">
                                    <div class="col-md-5">
                                        <label class="form-label" for="c_attachment">Attach Confirmation Email</label>
                                        <input class="form-control txt_nav" type="file" id="c_attachment"
                                            name="attachment" />
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label" for=""> -OR- </label>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label" for="c_reference">Confirmation Refereance No.</label>
                                        <input class="form-control txt_nav" type="text" id="c_reference" name="reference" />
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-success" id="add_amount" style="float: right;">
                                        Confirm
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- end main content-->
        </div>

        <div class="modal fade" id="HotelPaymentModal" tabindex="-1" aria-labelledby="HotelPaymentModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="HotelPaymentModalLabel">Hotel Payment</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" class="hotel_payment_form" id="hotel_payment_form">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label" for="">Room Rates (USD)</label>
                                    <div class="alert alert-warning py-1">
                                        <div class="d-flex">
                                            <span class="text-muted me-3">SGL : <strong id="p_sgl_txt"
                                                    class="text-black"></strong></span>
                                            <span class="text-muted me-3">DBL : <strong id="p_dbl_txt"
                                                    class="text-black"></strong></span>
                                            <span class="text-muted me-3">TPL : <strong id="p_tpl_txt"
                                                    class="text-black"></strong></span>
                                            <span class="text-muted me-3">Extra Fee : <strong id="p_extra_txt"
                                                    class="text-black"></strong></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <input type="text" name="tour_id" id="p_tour_id" readonly>
                                    <input type="text" name="m_tour_id" id="m_tour_id" readonly>
                                    <input type="hidden" name="p_status" id="p_status" readonly>
                                    <input type="hidden" id="p_sgl_total" readonly>
                                    <input type="hidden" id="p_dbl_total" readonly>
                                    <input type="hidden" id="p_tpl_total" readonly>
                                    <input type="hidden" name="hotel_acc" id="p_hotel_acc" readonly>
                                    <input type="hidden" name="hotel_name" id="p_hotel_name" readonly>
                                    <input type="hidden" name="total_d_amt" id="total_d_amt" readonly>
                                    <input type="hidden" name="total_d_pay" id="total_d_pay" readonly>
                                    <input type="hidden" name="p_date_number" id="p_date_number">
                                    <div class="mb-3">
                                        <label class="form-label" for="extra_val">Extra Fee</label>
                                        <input class="form-control txt_nav" type="text" id="extra_val" name="extra_val"
                                            value="0.00" placeholder="" />
                                    </div>
                                    <input type="hidden" id="tot_doller" name="tot_doller">
                                    <div class="mb-3">
                                        <label class="form-label" for="no_of_room">Doller Rate<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="p_rate" name="rate"
                                            placeholder="" required />
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <label class="form-label" for="advance_payment_doller">Hotel Payment ($)<font
                                                    color="#FF0000"><strong>*</strong></font></label>
                                            <span class="text-muted">Total Amount : <strong id="p_total_doller"
                                                    class="text-black"></strong></span>
                                            <span class="text-muted">Total Paid Amout : <strong id="total_d_paid"
                                                    class="text-black"></strong></span>
                                        </div>
                                        <input class="form-control txt_nav" type="text" id="p_advance_payment_doller_amt"
                                            name="part_pay" placeholder="" required />
                                        <input class="form-control txt_nav" type="hidden" id="p_advance_payment_doller"
                                            name="advance_payment_doller" placeholder="" readonly />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="advance_payment">Hotel Payment (LKR)<font
                                                color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="p_advance_payment"
                                            name="advance_payment" placeholder="" readonly required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bank Account<font
                                                color="#FF0000"><strong>*</strong></font></label>
                                        <select name="bank_acc" id="p_bank_acc" class="p_bank_acc form-control"
                                            style="width: 100%;" required>
                                            <option value="">Select a Bank...</option>
                                            <?php
                                            foreach ($bank_acc as $bank) {
                                                echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="hotel_ref">Reference No<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="p_hotel_ref" name="hotel_ref"
                                            placeholder="" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="pay_date">Date <font
                                                color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control" type="text" id="pay_date" name="pay_date" placeholder=""
                                            data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="dates">Reserved For - Dates</label>
                                        <input class="form-control txt_nav" type="text" id="p_dates" name="dates"
                                            placeholder="" readonly />
                                        <div id="datesContainer"></div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-success" id="add_amount" style="float: right;">
                                            Confirm
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- end main content-->
        </div>

        <div class="modal fade" id="hotelBookingEmailModal" tabindex="-1" aria-labelledby="hotelBookingEmailModalLabel"
            aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="hotelBookingEmailModallLabel">Send Booking Email</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" class="hotel_booking_email_form" id="hotel_booking_email_form">
                            <div class="row">
                                <div class="mb-4">
                                    <input type="hidden" name="tour_id" id="e_tour_id">
                                    <input type="hidden" name="dates" id="e_dates">
                                    <input type="hidden" name="id" id="e_id">
                                    <div class="mb-3">
                                        <label class="form-label" for="em_to">To</label>
                                        <input class="form-control txt_nav" type="email" id="em_to" name="em_to"
                                            placeholder="" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="em_cc">CC</label>
                                        <select name="em_cc[]" id="em_cc" class="form-control em_cc"></select>
                                        <select name="em_cc[]" id="em_cc" class="form-control em_cc"></select>
                                        <small>
                                            All emails are CC to <i>accounts@avenirholidays.com</i>, <i>nf@avenirholidays.com</i>
                                            by default.
                                        </small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="em_subject">Subject</label>
                                        <input class="form-control txt_nav" type="text" id="em_subject" name="em_subject"
                                            placeholder="" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="em_body">Subject</label>
                                        <textarea id="em_body" name="em_body"></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-success mt-2" id="btn_email" style="float: right;">
                                        Send
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- end main content-->
        </div>
        <!-- END layout-wrapper -->

        <?= view('layouts/inpage_footer') ?>
        <?= view('layouts/theme_settings') ?>
        <?= view('layouts/scripts') ?>

        <script>
            $('.em_cc').select2({
                placeholder: " Select",
                allowClear: true,
                dropdownParent: $("#hotelBookingEmailModal"),
                multiple: true,
                tags: true
            });

            $('#em_body').summernote({
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', []]
                ],
            });

            //add .email-detail-show to body tag when click on read_email class
            $(document).on('click', '.read_mail', function() {

                // Get the data-id of the clicked email
                var id = $(this).data('id');

                // Store the button reference and original text for restoration
                var button = $(this);
                var originalText = button.html(); // Store the original button text

                // Disable button
                button.prop('disabled', true);

                // Change button to show loading spinner
                button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

                // Send POST request to get email details
                $.ajax({
                    url: '<?= base_url('tour/reserve_hotel/get_mail') ?>',
                    type: 'POST',
                    data: {
                        id: id
                    }
                }).then(function(response) { // On success

                    if (response.status === 200 && response.emails.length > 0) {
                        // Fetch the first email from the response
                        var emailData = response.emails[0];

                        // Log the email data for debugging
                        console.log(emailData);

                        // Clear the current email content
                        // $('.email-detail-content').html('');
                        $('#email-data-panel').html('');

                        // Initialize attachmentsHtml
                        var attachmentsHtml = '';

                        // Check if there are attachments
                        if (emailData.attachments && emailData.attachments.length > 0) {
                            attachmentsHtml = '<h6>Attachments:</h6><ul>';
                            emailData.attachments.forEach(function(attachment) {
                                // Ensure 'emailUid' corresponds to 'emailData.uid'
                                var downloadUrl = '<?= base_url("mail/inbox/downloadAttachment"); ?>/' + emailData.uid + '/' + encodeURIComponent(attachment.part_number);
                                attachmentsHtml += '<li><a href="' + downloadUrl + '">' + attachment.filename + '</a></li>';
                            });
                            attachmentsHtml += '</ul>';
                        }

                        // Build the HTML for the email content dynamically
                        var emailHtml = `
                        <div class="pb-4 border-bottom border-bottom-dashed">
                            <div class="row">
                                <div class="col">
                                    <div class="">
                                        <button type="button" class="btn btn-soft-danger btn-icon btn-sm fs-16 close-btn-email" id="close-btn-email">
                                            <i class="ri-close-fill align-bottom"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mx-n4 px-4 email-detail-content-scroll" data-simplebar>
                            <div class="mt-4 mb-3">
                                <h5 class="fw-bold email-subject-title">${emailData.subject}</h5>
                            </div>

                            <div class="accordion accordion-flush">
                                <div class="accordion-item border-dashed left">
                                    <div class="accordion-header">
                                        <a role="button" class="btn w-100 text-start px-0 bg-transparent shadow-none collapsed">
                                            <div class="d-flex align-items-center text-muted">
                                                <div class="flex-shrink-0 avatar-xs me-3">
                                                    <img src="assets/images/users/avatar-3.jpg" alt="" class="img-fluid rounded-circle">
                                                </div>
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <h5 class="fs-14 text-truncate email-user-name mb-0">${emailData.from}</h5>
                                                    <div class="text-truncate fs-12">to: ${emailData.to}</div>
                                                </div>
                                                <div class="flex-shrink-0 align-self-start">
                                                    <div class="text-muted fs-12">${emailData.date}</div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <div id="email-collapseThree" class="accordion-collapse collapse show">
                                        <div class="accordion-body text-body px-0">
                                            <div>
                                                ${attachmentsHtml}

                                                ${(emailData.body || 'No content available').replace(/(?:\r\n|\r|\n)/g, '<br>')}
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- end accordion -->
                        </div>

                    `;

                        // Insert the dynamically generated email content into start of the dom
                        // $('.email-detail-content').html(emailHtml);
                        $('#email-data-panel').html(emailHtml);

                        $('#message_id').val(emailData.message_id);

                        $('body').addClass('email-detail-show');

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No Reply Found'
                        });
                        console.log('No email found for the given ID.');
                    }

                }, function(xhr, status, error) { // On error
                    console.error('Error fetching email details:', error);

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while fetching the email.'
                    });
                }).always(function() { // This is always executed, regardless of success or failure
                    // Re-enable button and restore its original text
                    button.prop('disabled', false);
                    button.html(originalText); // Restore original button text
                });
            });


            //remove .email-detail-show to body tag when click on #emailchat-btn-close
            $(document).on('click', '#close-btn-email', function() {
                $('body').removeClass('email-detail-show');
            });

            function get_dates_between(from, to) {
                var dates = [];
                var currentDate = new Date(from);
                var endDate = new Date(to);
                while (currentDate <= endDate) {
                    dates.push(new Date(currentDate));
                    currentDate.setDate(currentDate.getDate() + 1);
                }
                return dates;
            }

            function get_passengers() {
                try {
                    var passengers = '<?php echo json_encode($passengers) ?>';
                    passengers = JSON.parse(passengers);
                    var passengerNames = '';
                    if (passengers?.length > 0) {
                        passengers.forEach(function(passenger) {
                            passengerNames += passenger.pass_name + ' , ';
                        });
                    }
                    if (passengerNames.endsWith(', ')) {
                        passengerNames = passengerNames.slice(0, -2);
                    }

                    console.log(passengerNames);

                    return passengerNames;
                } catch (e) {
                    console.log(e);
                    return [];
                }
            }

            // .open_em_modal click
            // $(document).on('click', '.open_em_modal', function() {
            //     var id = $(this).data('id');
            //     var tour_id = $(this).data('tour-id');
            //     var manual_id = $(this).data('manual-id');
            //     var consignee = $(this).data('consignee');
            //     var from_date = $(this).data('tour-date');
            //     var to_date = $(this).data('tour-date');
            //     var hotel = $(this).data('hotel');
            //     var hotel_code = $(this).data('hotel-code');

            //     $.ajax({
            //         url: '<?= base_url('master_files/supplier/get_supplier_data/') ?>' + hotel_code,
            //         type: 'GET',
            //     }).then(function(response) {
            //         var to = response?.email || '';
            //         $('#em_to').val(to);
            //     });

            //     $('#e_tour_id').val(tour_id);
            //     $('#e_id').val(id);

            //     var year = '';
            //     var stay_dates = get_dates_between(from_date, to_date);
            //     // foreach stay_dates as date, get each like '01 January'
            //     var stay_dates_str = stay_dates.map(date => date.toLocaleDateString('en-GB', {
            //         day: 'numeric',
            //         month: 'long'
            //     }));
            //     // get year from first date
            //     if (stay_dates.length > 0) {
            //         year = stay_dates[0].getFullYear();
            //     }
            //     // seperate dates by comma
            //     var stay_dates_str_comma = stay_dates_str.join(', ');

            //     // comma seperated dates in format of 2024-08-01,2024-08-02,2024-08-03
            //     var e_dates = stay_dates.map(date => date.toLocaleDateString('si-LK', {
            //         year: 'numeric',
            //         month: '2-digit',
            //         day: '2-digit'
            //     })).join(',');
            //     $('#e_dates').val(e_dates);
            //     console.log(e_dates);

            //     // arrival date and departure date in format of 01 January 2021
            //     var arrival_date = stay_dates_str[0] + ' ' + year;
            //     // var departure_date = stay_dates[stay_dates.length - 1].toLocaleDateString('en-GB', {
            //     //     day: 'numeric',
            //     //     month: 'long',
            //     //     year: 'numeric'
            //     // });
            //     var departure_date = new Date(stay_dates[stay_dates.length - 1]);
            //     departure_date.setDate(departure_date.getDate() + 1);
            //     departure_date = departure_date.toLocaleDateString('en-GB', {
            //         day: 'numeric',
            //         month: 'long',
            //         year: 'numeric'
            //     });

            //     // get number of nights in stay_dates
            //     // var nights = stay_dates.length - 1;
            //     var nights = stay_dates.length;
            //     var meal_plan = $(this).data('meal-code');

            //     // room count and type
            //     var sgl = $(this).data('sgl');
            //     var dbl = $(this).data('dbl');
            //     var tpl = $(this).data('tpl');
            //     var room_type = $(this).data('room-type');
            //     var room_count = '';
            //     if (sgl > 0) {
            //         room_count += sgl + ' Single, ';
            //     }
            //     if (dbl > 0) {
            //         room_count += dbl + ' Double, ';
            //     }
            //     if (tpl > 0) {
            //         room_count += tpl + ' Triple';
            //     }
            //     room_count += ' - ' + room_type;

            //     // room rates
            //     var sgl_rate = $(this).data('sgl-rate');
            //     var dbl_rate = $(this).data('dbl-rate');
            //     var tpl_rate = $(this).data('tpl-rate');
            //     var rates = '';
            //     if (sgl > 0) {
            //         rates += 'USD ' + sgl_rate + ' Per Single Room Per Night, ';
            //     }
            //     if (dbl > 0) {
            //         rates += 'USD ' + dbl_rate + ' Per Double Room Per Night, ';
            //     }
            //     if (tpl > 0) {
            //         rates += 'USD ' + tpl_rate + ' Per Triple Room Per Night';
            //     }

            //     var logo = '<?= base_url() ?>' + 'assets/images/email/logo.png';
            //     var trademark = '<?= base_url() ?>' + 'assets/images/email/trademark.png';

            //     // subject to set to email body (em_body textarea-summernote)
            //     var body = `
            //         <p style="font-family: Arial, sans-serif;">Dear Team,</p>
            //         <p style="font-family: Arial, sans-serif;">Greetings from Avenir Holidays...!</p>
            //         <p style="font-family: Arial, sans-serif; font-weight: bold;">Kindly provide the confirmation number and the proforma invoice at your earliest convenience.</p>
            //         <p style="font-family: Arial, sans-serif;">Please find the details of the hotel reservation below:</p>
            //         <table style="width: 75%; border-collapse: collapse; border: 1px solid black; font-family: Arial, sans-serif; margin-bottom: 20px;">
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Name of Client</td>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">${consignee}</td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Passenger Names</td>
            //                 <td style="border: 1px solid black; padding: 8px;">${get_passengers()}</td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Hotel</td>
            //                 <td style="border: 1px solid black; padding: 8px;">${hotel}</td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Arrival Date</td>
            //                 <td style="border: 1px solid black; padding: 8px;">${arrival_date}</td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Departure Date</td>
            //                 <td style="border: 1px solid black; padding: 8px;">${departure_date}</td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Number of Nights</td>
            //                 <td style="border: 1px solid black; padding: 8px;">${nights} Night(s)</td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Meal Plan</td>
            //                 <td style="border: 1px solid black; padding: 8px;">${meal_plan}</td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Room Count</td>
            //                 <td style="border: 1px solid black; padding: 8px;">${room_count}</td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Remarks</td>
            //                 <td style="border: 1px solid black; padding: 8px;"></td>
            //             </tr>
            //             <tr>
            //                 <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Rate</td>
            //                 <td style="border: 1px solid black; padding: 8px;">${rates}</td>
            //             </tr>
            //         </table>
            //         <p style="font-family: Arial, sans-serif;">Best Regards</p>
            //             <table>
            //                 <tr>
            //                     <td style="font-family: Arial, sans-serif; font-weight: bold;">
            //                         <img src="${logo}" alt="Logo" />
            //                     </td>
            //                     <td>
            //                     <table>
            //                     <tr>

            //                 </tr>
            //                 <tr>

            //                 </tr>
            //                 <tr>
            //                      <td>
            //                      <span style="font-family: Arial, sans-serif;">No. 126/1, Park Road, Colombo 05, Sri Lanka</span>
            //                      </td>
            //                 </tr>
            //                 <tr>
            //                      <td>
            //                      <strong style="font-family: Arial, sans-serif;">Mobile:</strong>
            //                      </td>
            //                 </tr>
            //                 <tr>
            //                     <td>
            //                      <span style="font-family: Arial, sans-serif;"><?= $user_telephone ?? '775837441' ?></span>
            //                      </td>
            //                 </tr>
            //                 <tr>
            //                     <td>
            //                      <strong style="font-family: Arial, sans-serif;">Tel:</strong>
            //                      </td>
            //                 </tr>
            //                 <tr>
            //                     <td>
            //                      <span style="font-family: Arial, sans-serif;">(77) 583 7441</span>
            //                      </td>
            //                 </tr>
            //                 <tr>
            //                     <td>
            //                      <span style="font-family: Arial, sans-serif;">(+94) 114 215 977</span>
            //                      </td>
            //                 </tr>
            //                 <tr>
            //                      <td>
            //                      <strong style="font-family: Arial, sans-serif;">Email:</strong>
            //                      </td>
            //                 </tr>
            //                 <tr>
            //                     <td>
            //                      <span style="font-family: Arial, sans-serif;"><?= $user_email ?? 'traveljp@avenirholidays.com' ?></span>
            //                     </td>
            //                 </tr>
            //                 <tr>
            //                     <td>
            //                      <img src="${trademark}" alt="Trademark" />
            //                     </td>
            //                 </tr>
            //                     </table>
            //                     </td>
            //                 </tr>
            //             </table>
            //     `;

            //     if (manual_id == '') alert('Manual ID not found for this tour.');
            //     var subject = manual_id + ' - ' + consignee + ' - ' + hotel;
            //     $('#em_subject').val(subject);
            //     $('#em_body').summernote('code', body);

            //     $('#hotelBookingEmailModal').modal('show');
            // });

            $(document).on('click', '.open_em_modal', function() {
                var id = $(this).data('id');
                var tour_id = $(this).data('tour-id');
                var manual_id = $(this).data('manual-id');
                var consignee = $(this).data('consignee');
                var from_date = $(this).data('from-date');
                var to_date = $(this).data('to-date');
                var hotel = $(this).data('hotel');
                var hotel_code = $(this).data('hotel-code');

                $.ajax({
                    url: '<?= base_url('master_files/supplier/get_supplier_data/') ?>' + hotel_code,
                    type: 'GET',
                }).then(function(response) {
                    var to = response?.email || '';
                    $('#em_to').val(to);
                });

                $('#e_tour_id').val(tour_id);
                $('#e_id').val(id);

                var year = '';
                var stay_dates = get_dates_between(from_date, to_date);
                // foreach stay_dates as date, get each like '01 January'
                var stay_dates_str = stay_dates.map(date => date.toLocaleDateString('en-GB', {
                    day: 'numeric',
                    month: 'long'
                }));
                // get year from first date
                if (stay_dates.length > 0) {
                    year = stay_dates[0].getFullYear();
                }
                // seperate dates by comma
                var stay_dates_str_comma = stay_dates_str.join(', ');

                // comma seperated dates in format of 2024-08-01,2024-08-02,2024-08-03
                var e_dates = stay_dates.map(date => date.toLocaleDateString('si-LK', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit'
                })).join(',');
                $('#e_dates').val(e_dates);
                console.log(e_dates);

                // arrival date and departure date in format of 01 January 2021
                var arrival_date = stay_dates_str[0] + ' ' + year;
                // var departure_date = stay_dates[stay_dates.length - 1].toLocaleDateString('en-GB', {
                //     day: 'numeric',
                //     month: 'long',
                //     year: 'numeric'
                // });
                var departure_date = new Date(stay_dates[stay_dates.length - 1]);
                departure_date.setDate(departure_date.getDate() + 1);
                departure_date = departure_date.toLocaleDateString('en-GB', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });

                // get number of nights in stay_dates
                // var nights = stay_dates.length - 1;
                var nights = stay_dates.length;
                var meal_plan = $(this).data('meal-code');

                // room count and type
                var sgl = $(this).data('sgl');
                var dbl = $(this).data('dbl');
                var tpl = $(this).data('tpl');
                var room_type = $(this).data('room-type');
                var room_count = '';
                if (sgl > 0) {
                    room_count += sgl + ' Single, ';
                }
                if (dbl > 0) {
                    room_count += dbl + ' Double, ';
                }
                if (tpl > 0) {
                    room_count += tpl + ' Triple';
                }
                room_count += ' - ' + room_type;

                // room rates
                var sgl_rate = $(this).data('sgl-rate');
                var dbl_rate = $(this).data('dbl-rate');
                var tpl_rate = $(this).data('tpl-rate');
                var rates = '';
                if (sgl > 0) {
                    rates += 'USD ' + sgl_rate + ' Per Single Room Per Night, ';
                }
                if (dbl > 0) {
                    rates += 'USD ' + dbl_rate + ' Per Double Room Per Night, ';
                }
                if (tpl > 0) {
                    rates += 'USD ' + tpl_rate + ' Per Triple Room Per Night';
                }

                var logo = '<?= base_url() ?>' + 'assets/images/email/logo.png';
                var trademark = '<?= base_url() ?>' + 'assets/images/email/trademark.png';

                // subject to set to email body (em_body textarea-summernote)
                var body = `
                    <p style="font-family: Arial, sans-serif;">Dear Team,</p>
                    <p style="font-family: Arial, sans-serif;">Greetings from Avenir Holidays...!</p>
                    <p style="font-family: Arial, sans-serif; font-weight: bold;">Kindly provide the confirmation number and the proforma invoice at your earliest convenience.</p>
                    <p style="font-family: Arial, sans-serif;">Please find the details of the hotel reservation below:</p>
                    <table style="width: 75%; border-collapse: collapse; border: 1px solid black; font-family: Arial, sans-serif; margin-bottom: 20px;">
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Name of Client</td>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">${consignee}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Passenger Names</td>
                            <td style="border: 1px solid black; padding: 8px;">${get_passengers()}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Hotel</td>
                            <td style="border: 1px solid black; padding: 8px;">${hotel}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Arrival Date</td>
                            <td style="border: 1px solid black; padding: 8px;">${arrival_date}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Departure Date</td>
                            <td style="border: 1px solid black; padding: 8px;">${departure_date}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Number of Nights</td>
                            <td style="border: 1px solid black; padding: 8px;">${nights} Night(s)</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Meal Plan</td>
                            <td style="border: 1px solid black; padding: 8px;">${meal_plan}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Room Count</td>
                            <td style="border: 1px solid black; padding: 8px;">${room_count}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Remarks</td>
                            <td style="border: 1px solid black; padding: 8px;"></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px; font-weight: bold;">Rate</td>
                            <td style="border: 1px solid black; padding: 8px;">${rates}</td>
                        </tr>
                    </table>
                    <p style="font-family: Arial, sans-serif;">Best Regards</p>
                        <table>
                            <tr>
                                <td style="font-family: Arial, sans-serif; font-weight: bold;">
                                    <img src="${logo}" alt="Logo" />
                                </td>
                                <td>
                                <table>
                                <tr>
                                <td style="font-family: Arial, sans-serif;">
                                <strong style="font-family: Arial, sans-serif;"><?= $user_fullname ?></strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="font-family: Arial, sans-serif;">
                                <small style="font-family: Arial, sans-serif; color: #6c757d;">Tour Coordinator - Avenir Holidays (Pvt) Ltd</small>
                                </td>
                            </tr>
                            <tr>
                                 <td>
                                 <span style="font-family: Arial, sans-serif;">No. 126/1, Park Road, Colombo 05, Sri Lanka</span>
                                 </td>
                            </tr>
                            <tr>
                                 <td>
                                 <strong style="font-family: Arial, sans-serif;">Mobile:</strong>
                                 </td>
                            </tr>
                            <tr>
                                <td>
                                 <span style="font-family: Arial, sans-serif;"><?= $user_telephone ?? '775837441' ?></span>
                                 </td>
                            </tr>
                            <tr>
                                <td>
                                 <strong style="font-family: Arial, sans-serif;">Tel:</strong>
                                 </td>
                            </tr>
                            <tr>
                                <td>
                                 <span style="font-family: Arial, sans-serif;">(77) 583 7441</span>
                                 </td>
                            </tr>
                            <tr>
                                <td>
                                 <span style="font-family: Arial, sans-serif;">(+94) 114 215 977</span>
                                 </td>
                            </tr>
                            <tr>
                                 <td>
                                 <strong style="font-family: Arial, sans-serif;">Email:</strong>
                                 </td>
                            </tr>
                            <tr>
                                <td>
                                 <span style="font-family: Arial, sans-serif;"><?= $user_email ?? 'traveljp@avenirholidays.com' ?></span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                 <img src="${trademark}" alt="Trademark" />
                                </td>
                            </tr>
                                </table>
                                </td>
                            </tr>
                        </table>
                `;

                if (manual_id == '') alert('Manual ID not found for this tour.');
                var subject = manual_id + ' - ' + consignee + ' - ' + hotel;
                $('#em_subject').val(subject);
                $('#em_body').summernote('code', body);

                $('#hotelBookingEmailModal').modal('show');
            });


            function confirm_calculation() {
                var extra = $('#p_extra').val();

                var sgl_total = $('#sgl').val();
                var sgl_cunt = $('#sglcount').val();

                var dbl_total = $('#dbl').val();
                var dbl_cunt = $('#dblcount').val();

                var tpl_total = $('#tpl').val();
                var tpl_cunt = $('#tplcount').val();

                var d_rate = $('#confirm_p_rate').val();

                var date_count = $('#c_dates_count').val();
                // validate above values for empty or NaN
                // if(extra == ''){
                //     extra = 0.00;
                // }

                if (extra == '' || isNaN(extra)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter a valid extra fee',
                    });
                    return;
                }

                // if (d_rate == '' || isNaN(d_rate)) {
                //     Swal.fire({
                //         icon: 'error',
                //         title: 'Error',
                //         text: 'Please enter a valid doller rate',
                //     });
                //     return;
                // }

                extra = parseFloat(extra);
                sgl_total = parseFloat(sgl_total) * sgl_cunt;
                dbl_total = parseFloat(dbl_total) * dbl_cunt;
                tpl_total = parseFloat(tpl_total) * tpl_cunt;

                var total_room_amount = (sgl_total + dbl_total + tpl_total);

                var total_amount = parseFloat(total_room_amount) + extra;
                $('#confirm_doller').val(parseFloat(total_amount).toFixed(2));

                // var advance_payment = total_amount * d_rate;
                // $('#p_total_doller').text(parseFloat(total_amount).toFixed(2));
                // $('#total_d_amt').val(parseFloat(total_amount).toFixed(2));

                var total_advance_payment = total_amount * d_rate;
                $('#confirm_lkr').val(parseFloat(total_advance_payment).toFixed(2));
            }

            function calculate_advance_payment() {
                var extra = $('#extra_val').val();
                var sgl_total = $('#p_sgl_total').val();
                var dbl_total = $('#p_dbl_total').val();
                var tpl_total = $('#p_tpl_total').val();
                var d_rate = $('#p_rate').val();
                var part_pay = $('#p_advance_payment_doller_amt').val();

                // validate above values for empty or NaN
                if (extra == '' || isNaN(extra)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter a valid extra fee',
                    });
                    return;
                }

                if (d_rate == '' || isNaN(d_rate)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter a valid doller rate',
                    });
                    return;
                }

                extra = parseFloat(extra);
                sgl_total = parseFloat(sgl_total);
                dbl_total = parseFloat(dbl_total);
                tpl_total = parseFloat(tpl_total);

                var total_amount = sgl_total + dbl_total + tpl_total + extra;
                $('#p_advance_payment_doller').val(parseFloat(total_amount).toFixed(2));

                // var advance_payment = total_amount * d_rate;
                // $('#p_total_doller').text(parseFloat(total_amount).toFixed(2));
                // $('#total_d_amt').val(parseFloat(total_amount).toFixed(2));

                var total_advance_payment = part_pay * d_rate;
                $('#p_advance_payment').val(parseFloat(total_advance_payment).toFixed(2));
            }

            // .hotel_payment_modal click
            $(document).on('click', '.hotel_payment_modal', function() {

                var tour_id = $(this).data('tour');
                var p_dates = $(this).data('tour-date');
                var date_no = $(this).data('date-number');
                var hotel = $(this).data('existhotel');
                // var from_date = $(this).data('min-date');
                // var to_date = $(this).data('max-date');
                var balance = $(this).data('bal');
                var manual_id = $(this).data('manu');
                var status = $(this).data('status');
                // var date_count = $(this).data('date-count');

                $("#p_tour_id").val(tour_id);
                $("#m_tour_id").val(manual_id);
                $("#p_status").val(status);
                $("#p_advance_payment_doller").val('');
                $("#p_advance_payment").val('');
                $("#p_tot_doller").val('');
                $("#p_extra").val('0.00');
                $("#p_rate").val('');
                $("#p_hotel_ref").val('');
                $("#p_bank_acc").val('');
                $("#p_dates").val('');

                // var stay_dates = get_dates_between(from_date, to_date);
                // var p_dates = stay_dates.map(date => date.toLocaleDateString('en-GB', {
                //     year: 'numeric',
                //     month: '2-digit',
                //     day: '2-digit'
                // })).join(', ');

                // p_dates = p_dates.replace(/\//g, '-');
                $('#p_dates').val(p_dates);

                $.ajax({
                    url: '<?php echo base_url(); ?>tour/reserve_hotel/get_room_rates',
                    type: 'POST',
                    data: {
                        tour_id: tour_id,
                        dates: p_dates,
                        hotel: hotel,
                        date_no: date_no
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 200) {
                            $('#p_sgl_total').val(data.sgl_total);
                            $('#p_sgl_txt').text(parseFloat(data.sgl_total).toFixed(2));

                            $('#p_dbl_total').val(data.dbl_total);
                            $('#p_dbl_txt').text(parseFloat(data.dbl_total).toFixed(2));

                            $('#p_tpl_total').val(data.tpl_total);
                            $('#p_tpl_txt').text(parseFloat(data.tpl_total).toFixed(2));

                            $('#extra_val').val(data.extra_fee_hotel);
                            $('#p_extra_txt').text(parseFloat(data.extra_fee_hotel).toFixed(2));

                            var totalHotelDoller = parseFloat(data.total_hotel_doller);
                            // alert(totalHotelDoller);
                            if (!isNaN(totalHotelDoller)) {
                                $('#p_total_doller').text(parseFloat(totalHotelDoller).toFixed(2));
                                $('#total_d_amt').val(totalHotelDoller.toFixed(2));
                            } else {
                                $('#p_total_doller').text('0.00');
                                $('#total_d_amt').val('0.00');
                            }
                            var payDollerCount = parseFloat(data.pay_doller_count);
                            $('#total_d_pay').val(isNaN(payDollerCount) ? '0.00' : payDollerCount.toFixed(2));

                            if (payDollerCount) {
                                $('#total_d_paid').text(parseFloat(data.pay_doller_count).toFixed(2));
                            } else {
                                $('#total_d_paid').text(parseFloat(0).toFixed(2));
                            }

                            // $('#p_extra').val('0.00');
                            $('#p_rate').val('0.00');
                            calculate_advance_payment();

                            $('#p_hotel_acc').val(data.hotel_acc);
                            $('#p_hotel_name').val(data.hotel_name);
                            $('#p_date_number').val(date_no);

                            // if (($('#finalize_txt').val()) == 0) {

                            $('#HotelPaymentModal').modal('show');

                            // } else {
                            //     Swal.fire({
                            //         icon: 'error',
                            //         title: 'Error',
                            //         text: 'Please finalize the quotation before confirming the hotel reservation.',
                            //     });
                            // }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message ? data.message : 'An error occured while processing the request',
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occured while processing the request',
                        });
                    }
                });
            });

            // #p_extra keyup
            $('#p_extra').on('keyup', function() {
                confirm_calculation();
            });

            // #p_rate keyup
            $('#p_rate').on('keyup', function() {
                calculate_advance_payment();
            });

            // #confirm_p_rate keyup
            $('#confirm_p_rate').on('keyup', function() {
                confirm_calculation();
            });

            // #part_pay keyup
            $('#p_advance_payment_doller_amt').on('keyup', function() {
                calculate_advance_payment();
                // get #p_advance_payment_doller_amt value
                $type_amount = $(this).val();
                $total_d_amt = $('#total_d_amt').val();
                $total_d_pay = $('#total_d_pay').val();

                $doller_balance = parseFloat($total_d_amt) - parseFloat($total_d_pay);
                if ($doller_balance < $type_amount) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'You can not pay more than the balance amount',
                    });
                    $('#p_advance_payment_doller_amt').val('');
                }
            });
        </script>

        <script>
            $('.js-example-basic-single').select2({
                placeholder: " Select",
                allowClear: true
            });

            $('.select_location').select2({
                placeholder: " Select",
                allowClear: true,
                dropdownParent: $("#HotelConfirmedModal")
            });

            $('.select_hotel').select2({
                placeholder: " Select",
                allowClear: true,
                dropdownParent: $("#HotelConfirmedModal")
            });

            $('.room_type').select2({
                placeholder: " Select",
                allowClear: true,
                dropdownParent: $("#HotelConfirmedModal")
            });

            $('.p_bank_acc').select2({
                placeholder: " Select",
                allowClear: true,
                dropdownParent: $("#HotelPaymentModal")
            });

            $("#select_location").on('change', function(e) {
                // alert('ok');
                e.preventDefault();

                var search_location = $("#select_location").val();
                var date = $("#c_tour_date").val();

                if (search_location == null || search_location == '' || date == null) {
                    return;
                }

                $.ajax({
                    url: '<?php echo base_url('tour/tour/get_hotel_details') ?>',
                    type: 'GET',
                    data: {
                        search_location: search_location,
                        date: date,
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 200) {

                            // $("#select_h").text("");
                            // $("#select_h_code").text("");
                            // document.getElementById('select_h').parentElement.style.display = 'none';
                            // document.getElementById('select_h_code').parentElement.style.display = 'none';

                            $('#select_hotel').empty();

                            $('#select_hotel').append('<option value="">Select a hotel</option>');

                            if (data.data.hotels && Array.isArray(data.data.hotels)) {
                                $.each(data.data.hotels, function(index, value) {
                                    if (value && value.Sup_Acc_No && value.Name) {
                                        $('#select_hotel').append(
                                            '<option value="' + value.Sup_Acc_No + '">' + value.Name + '</option>'
                                        );
                                    } else {
                                        console.warn('Invalid entry in response:', value);
                                    }
                                });
                            } else {
                                console.error('Response is not an array:', data.data);
                            }

                        } else {

                            console.error(data.message ? data.message : 'An error occured while processing the request');

                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occured while processing the request',
                        });
                    }
                });
            });

            // function assign_hotel(hotelId) {

            //     // $("#slct_htl").val(hotelId);
            //     var date = $("#c_tour_date").val();

            //     if (hotelId == null || hotelId == '' || date == null) {
            //         return;
            //     }

            //     $.ajax({
            //         url: '<?php //echo base_url('tour/tour/get_hotel_type') 
                                ?>',
            //         type: 'GET',
            //         data: {
            //             hotelId: hotelId,
            //             date: date,
            //         },
            //         dataType: 'json',
            //         success: function(data) {
            //             if (data.status == 200) {

            //                 $('#room_type').empty();

            //                 $('#room_type').append('<option value="">Select a Room Type</option>');

            //                 $('#meal_code').val('');
            //                 $('#sgl').val('');
            //                 $('#dbl').val('');
            //                 $('#tpl').val('');

            //                 if (data.data.hotels_type && Array.isArray(data.data.hotels_type)) {
            //                     $.each(data.data.hotels_type, function(index, value) {
            //                         if (value && value.id && value.rtype) {
            //                             $('#room_type').append(
            //                                 '<option value="' + value.id + '">' + value.rtype + '</option>'
            //                             );
            //                         } else {
            //                             console.warn('Invalid entry in response:', value);
            //                         }
            //                     });
            //                 } else {
            //                     console.error('Response is not an array:', data.data);
            //                 }

            //             } else {

            //                 console.error(data.message ? data.message : 'An error occured while processing the request');

            //             }
            //         },
            //         error: function() {
            //             Swal.fire({
            //                 icon: 'error',
            //                 title: 'Error',
            //                 text: 'An error occured while processing the request',
            //             });
            //         }
            //     });

            // }

            $(".hotel_confirm_modal_btn").on('click', function(e) {
                e.preventDefault();

                var tour = $(this).data('tour');
                var date = $(this).data('tour-date');
                var date_number = $(this).data('date-number');
                var existhotel = $(this).data('existhotel');
                var existhotelcode = $(this).data('existhotelcode');
                var to_location = $(this).data('tolocation');
                // var from_date = $(this).data('min-date');
                // var to_date = $(this).data('max-date');
                let parts = to_location.split(','); // Split the string by commas
                let lastWord = parts[parts.length - 1].trim();
                // date = from_date

                $("#c_tour_id").val(tour);
                $("#c_tour_date").val(date);
                $("#c_old_hotel").val(existhotel);
                $("#old_hotel_code").val(existhotelcode);

                $.ajax({
                    url: '<?php echo base_url(); ?>tour/booking_confirm/getAllDetails/' + tour + '/' + date + '/' + existhotel + '/' + date_number,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {

                            $('#slct_hotel').text(response.data.quotationDetails.hotel_name);
                            $('#slct_hotel_name').val(response.data.quotationDetails.hotel_name);
                            $('#slct_hotel_id').val(response.data.quotationDetails.hotel_acc);
                            $('#slct_room_type').text(response.data.quotationDetails.room);
                            $('#slct_room_type_id').val(response.data.quotationDetails.room_type);
                            $('#slct_location').text(lastWord);
                            $('#room_type_id').val(response.data.quotationDetails.room_type);
                            $("#meal_code").val(response.data.quotationDetails.meal_code);
                            $("#sgl").val(response.data.quotationDetails.sgl);
                            $("#dbl").val(response.data.quotationDetails.dbl);
                            $("#tpl").val(response.data.quotationDetails.tpl);
                            $("#sglcount").val(response.data.quotationDetails.r_sgl);
                            $("#dblcount").val(response.data.quotationDetails.r_dbl);
                            $("#tplcount").val(response.data.quotationDetails.r_tpl);
                            // $("#finalize_txt").val(response.data.quotationDetails.finalize_txt);

                            // var stay_dates = get_dates_between(from_date, to_date);
                            // // foreach stay_dates as date, get each like '2024-08-30'
                            // var stay_dates_str = stay_dates.map(date => date.toLocaleDateString('en-GB', {
                            //     year: 'numeric',
                            //     month: '2-digit',
                            //     day: '2-digit'
                            // }));
                            // // comma separated dates
                            // var stay_dates_str = stay_dates_str.join(', ');
                            // // replace / with -
                            // $('#c_dates').val(stay_dates_str.replace(/\//g, '-'));
                            // // get count of stay_dates_str
                            // var count = stay_dates.length;

                            $('#c_dates').val(date);
                            $('#c_dates_count').val(1);
                            $('#date_number').val(date_number);


                            confirm_calculation();

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Not Found',
                                text: response.message,
                            });

                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });
                    }
                });

                // if (($('#finalize_txt').val()) == 0) {
                $("#HotelConfirmedModal").modal('show');
                // } else {
                //     Swal.fire({
                //         icon: 'error',
                //         title: 'Error',
                //         text: 'Please finalize the quotation before confirming the hotel reservation.',
                //     });
                // }

            });

            var preventChange = false;

            function mealCodeChanged(mplan) {
                // alert(mplan);

                var id = mplan;
                // var hotel_code = $('#select_hotel_code').val();
                // var slct_h_code = $("#old_hotel_code").val();
                // var room_type = $('#room_type_id').val();
                var select_hotel = $('#select_hotel').val();
                var room_type = $('#room_type').val();
                var h_code = "";

                // if any of above is empty, return

                if (select_hotel == '' || select_hotel == null) {
                    h_code = select_h;
                } else {
                    h_code = select_hotel;
                }
                // alert(room_type);
                if (h_code == '' || room_type == 0 || h_code == null || h_code == 0) {
                    return;
                }

                // ajax call
                $.ajax({
                    url: '<?php echo base_url('tour/tour/get_meal_prices') ?>',
                    type: 'GET',
                    data: {
                        room_type: room_type,
                        h_code: h_code,
                        meal_plan: id,
                    },
                    dataType: 'json',
                    success: function(data) {
                        $('#loadingModal').modal('hide');
                        if (data.status == 200) {
                            var sgl = data.SGL;
                            var dbl = data.DBL;
                            var tpl = data.TPL;
                            $('#sgl').val(sgl);
                            $('#dbl').val(dbl);
                            $('#tpl').val(tpl);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message ? data.message : 'An error occured while processing the request',
                            });
                        }
                    },
                    error: function() {
                        $('#loadingModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occured while processing the request',
                        });
                    }
                });

            }

            // Confirm Hotel
            $("#confirm_hotel_form").on('submit', (function(e) {
                e.preventDefault();
                var formdata = new FormData(this);

                // confirm swal
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to confirm this hotel reservation?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, confirm it!'
                }).then((result) => {
                    if (result.isConfirmed) {

                        formdata.append('new_hotel_name', $("#select_hotel option:selected").text());
                        formdata.append('selected_hotel_name', $("#slct_hotel").text());
                        formdata.append('room_type_name_new', $("#room_type option:selected").text());
                        formdata.append('slct_room_type', $("#slct_room_type").text());

                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/reserve_hotel/confirm_reservation',
                            type: 'POST',
                            data: formdata,
                            processData: false,
                            contentType: false,
                            cache: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                        showConfirmButton: true,
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            location.reload();
                                        }
                                    });
                                } else if (response.status == 400) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Not Valid Data!',
                                        text: response.message,
                                    });

                                } else if (response.status == 409) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Ooops...',
                                        text: response.message,
                                    });
                                } else if (response.status == 500) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Ooops...!',
                                        text: response.message,
                                    });
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });

                            }
                        });
                    }
                });
            }));

            // .hotel_booking_email_form submit
            $(".hotel_booking_email_form").on('submit', function(e) {
                e.preventDefault();
                var formdata = new FormData(this);

                // confirm swal
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to send this email?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, send it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/reserve_hotel/hotel_reservation',
                            type: 'POST',
                            data: formdata,
                            processData: false,
                            contentType: false,
                            cache: false,
                            dataType: 'json',
                            beforeSend: function() {
                                $('#btn_email').attr('disabled', 'disabled');
                                $('#btn_email').html('<i class="fa fa-spinner fa-spin"></i> Sending Email...');
                            },
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                        showConfirmButton: true,
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            location.reload();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Ooops...!',
                                        text: response.message ? response.message : 'An error occured while processing the request',
                                    });
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });
                            },
                            complete: function() {
                                $('#btn_email').removeAttr('disabled');
                                $('#btn_email').html('Send');
                            }
                        });
                    }
                });
            });

            // .hotel_booking_reply_form submit
            // Use event delegation for dynamically added forms
            $(document).on('submit', '#hotel_booking_reply_form', function(e) {
                console.log("hello world");
                e.preventDefault();
                var formdata = new FormData(this);

                // Confirm with the user before sending the email
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to send this email?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, send it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/reserve_hotel/reply',
                            type: 'POST',
                            data: formdata,
                            processData: false,
                            contentType: false,
                            cache: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                        showConfirmButton: true,
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            // location.reload();
                                            console.log("tatta")
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Ooops...!',
                                        text: response.message ? response.message : 'An error occurred while processing the request',
                                    });
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });
                            }
                        });
                    }
                });

            });

            $("#hotel_payment_form").on('submit', (function(e) {
                e.preventDefault();

                var formdata = new FormData(this);

                // confirm swal
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to confirm this hotel payment?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, confirm it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        formdata.append('hotel', $("#p_hotel_acc").val());
                        formdata.append('hotel_name', $("#p_hotel_name").val());
                        formdata.append('bank_name', $("#p_bank_acc option:selected").text());

                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/reserve_hotel/hotel_payment',
                            type: 'POST',
                            data: formdata,
                            processData: false,
                            contentType: false,
                            cache: false,
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                        showConfirmButton: true
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            location.reload();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Ooops...!',
                                        text: response.message,
                                    });
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });
                            }
                        });
                    }
                });
            }));

            // cancel_booking_btn click
            $(document).on('click', '.cancel_booking_btn', function() {


                var id = $(this).data('id');
                var tour = $(this).data('tour');
                // var minDate = $(this).data('min-date');
                // var maxDate = $(this).data('max-date');
                var hotel = $(this).data('existhotel');
                var date = $(this).data('tour-date');
                var dateNumber = $(this).data('date-number');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to cancel this booking?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, cancel it!'
                }).then((result) => {
                    if (result.isConfirmed) {

                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/reserve_hotel/cancel_booking',
                            type: 'POST',
                            data: {
                                id: id,
                                tour: tour,
                                hotel: hotel,
                                date: date,
                                date_number: dateNumber
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Cancelled',
                                        text: response.message,
                                        showConfirmButton: true
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
                            error: function(jqXHR, textStatus, errorThrown) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });
                            }
                        });
                    }
                });

            });
        </script>

</body>


</html>