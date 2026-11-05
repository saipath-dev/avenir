<?= view('layouts/head') ?>

<body>

    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 20mm;
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

        .table-custom tr {
            height: 2% !important;
        }

        .table th,
        .table td {
            padding: 1px !important;
            margin: 0;
        }
    </style>

    <?= view('layouts/topbar', ['topbar_back' => 'close', 'topbar_home' => false, 'topbar_logout' => false]) ?>

    <div class="container-fluid p-4 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <h2 class="p-title" style="text-align: center;">Hotel Payment</h2>
                </div>
            </div>
        </div>

        <div class="row clearfix mt-3 justify-content-center">
            <div class="col-md-8">
                <div class="card p-4">
                    <div class="body">
                        <div class="row">
                            <?php
                            $consignee = [];
                            foreach ($tour_main_details as $detail) {
                                $consignee = $detail;
                            ?>

                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Tour Id</span>
                                        <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $tour_id ?>">
                                        <h6 class="fw-bold" id="t_id"><?php echo $tour_id ?></h6>
                                        <input type="hidden" name="is_settled" id="is_settled" value="<?php echo $detail['transport_settled'] ?>">
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Manual Id</span>
                                        <input type="hidden" name="manual_id" id="manual_id" value="<?php echo $detail['manual_id'] ?>">
                                        <h6 class="fw-bold" id="t_manual_id"><?php echo $detail['manual_id'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Tour Name</span>
                                        <h6 class="fw-bold" id="t_consignee"><?php echo $detail['consignee'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Tour Agent</span>
                                        <h6 class="fw-bold" id="t_agent_name"><?php echo $detail['agent_name'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Transport Agent</span>
                                        <span id="t_transportagent" class="d-none"><?php echo $detail['transport_agent'] ?></span>
                                        <h6 class="fw-bold" id="t_transportagent_name">
                                            <?php echo $detail['transport_agent_name'] ?>
                                        </h6>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Guide</span>
                                        <span id="t_guide" class="d-none"><?php echo $detail['guide_name'] ?></span>
                                        <h6 class="fw-bold" id="guide_name">
                                            <?php echo $detail['guide_name'] ?>
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Passengers</span>
                                        <h6 class="fw-bold" id="t_passenger_count"><?php echo $detail['passenger_count'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Start Date</span>
                                        <h6 class="fw-bold" id="t_start_date"><?php echo $detail['start_date'] ?></h6>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">End Date</span>
                                        <h6 class="fw-bold" id="t_end_date"><?php echo $detail['end_date'] ?></h6>
                                    </div>
                                </div>
                                <input type="hidden" name="finalize_txt" id="finalize_txt" value="<?php echo $detail['finalize_txt'] ?>">
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            --carolina-blue: #64ADD4;
            --mantis: #7ABC49;
            --text-dark: #2c3e50;
            --text-muted: #6c757d;
        }

        .hotel-reservation-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: none;
            overflow: hidden;
        }

        .hotel-reservation-header {
            background: linear-gradient(135deg, var(--carolina-blue) 0%, #5a9ec8 100%);
            color: white;
            padding: 1rem 1.5rem;
        }

        .hotel-reservation-table {
            margin-bottom: 0;
        }

        .hotel-reservation-table thead th {
            background-color: rgba(100, 173, 212, 0.08);
            color: var(--text-dark);
            font-weight: 600;
            border-color: rgba(100, 173, 212, 0.15);
            padding: 12px;
            font-size: 0.9rem;
        }

        .hotel-reservation-table tbody tr {
            transition: background-color 0.2s ease;
            border-color: rgba(100, 173, 212, 0.15);
        }

        .hotel-reservation-table tbody tr:nth-of-type(odd) {
            background-color: rgba(100, 173, 212, 0.03);
        }

        .hotel-reservation-table tbody tr:hover {
            background-color: rgba(100, 173, 212, 0.12) !important;
        }

        .hotel-reservation-table tbody td {
            padding: 12px;
            vertical-align: middle;
            color: var(--text-dark);
            border-color: rgba(100, 173, 212, 0.15);
        }

        .badge-meal-plan {
            background: linear-gradient(135deg, var(--mantis) 0%, #84C155 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .room-count-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .room-count-label {
            font-weight: 600;
            color: var(--text-muted);
            min-width: 40px;
        }

        .room-count-value {
            background-color: rgba(100, 173, 212, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            color: var(--carolina-blue);
        }

        .btn-email-hotel {
            background: linear-gradient(135deg, var(--carolina-blue) 0%, #5a9ec8 100%);
            color: white;
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 6px 16px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(100, 173, 212, 0.25);
        }

        .btn-email-hotel:hover {
            background: linear-gradient(135deg, #5a9ec8 0%, var(--carolina-blue) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(100, 173, 212, 0.4);
        }

        .hotel-name-cell {
            font-weight: 600;
            color: var(--text-dark);
        }

        .date-range-cell {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .section-title {
            background-color: var(--carolina-blue);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            width: fit-content;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>

    <div class="row mb-4 justify-content-center">
        <div class="col-xxl-10">
            <div class="card hotel-reservation-card">
                <div class="card-header hotel-reservation-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-building me-2"></i>Hotel Payment</h5>
                        <span class="badge bg-light text-dark"><?= count($tour_details2) ?> Reservation<?= count($tour_details2) > 1 ? 's' : '' ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table hotel-reservation-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 3%;">#</th>
                                    <th scope="col" style="width: 15%;">Day</th>
                                    <th scope="col" style="width: 25%;">Hotel Name</th>
                                    <th scope="col" style="width: 25%;">Payment</th>
                                    <th scope="col" style="width: 12%;">$ Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($tour_details as $row) {
                                    $min = $row['start_date'];
                                    $max = $row['end_date'];
                                    if ($min == $max) {
                                        $daterang = $row['min_date'] = $min;
                                    } else {
                                        $daterang = $row['min_date'] = $min . "  -  " . $max;
                                    }

                                    $total_hotel_doller = $row['total_hotel_doller'];
                                    $pay_doller_count = $row['pay_doller_count'];

                                    $status = $row['hotel_payment_flag'];

                                    $bal_amt = $total_hotel_doller - $pay_doller_count;

                                    if ($total_hotel_doller == $pay_doller_count) {
                                        $bal = true;
                                    } else {
                                        $bal = false;
                                    }
                                ?>
                                    <tr>
                                        <td><strong><?= $i++; ?></strong></td>
                                        <td class="date-range-cell">
                                            <i class="bi bi-calendar-range me-2 text-muted"></i><?= $daterang ?>
                                        </td>
                                        <td class="hotel-name-cell">
                                            <i class="bi bi-building me-2 text-muted"></i><?= $row['hotel_name'] ?>
                                        </td>
                                        <td>
                                            <?php
                                            $is_target_hotel = empty($filter_hotel_id) || $row['hotel_acc'] === $filter_hotel_id;
                                            ?>
                                            <?php if ($row['hotel_payment_flag'] == 0) {
                                            ?>
                                                <button type="button"
                                                    class="btn btn btn-info btn-sm hotel_payment_modal"
                                                    data-tour="<?= $row['quotation_id'] ?>"
                                                    data-tour-date="<?= $row['date_range'] ?>"
                                                    data-date-number="<?= $row['quotation_day'] ?>"
                                                    data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                    data-existhotel="<?= $row['hotel_acc'] ?>"
                                                    data-tolocation="<?= $row['to_location'] ?>"
                                                    data-bal="<?= $bal_amt ?>"
                                                    data-manu="<?= $detail['manual_id'] ?>"
                                                    data-status="<?= $status ?>"
                                                    <?= $is_target_hotel ? '' : 'disabled' ?>>
                                                    <i class="bi bi-cash-coin me-1"></i>Pay
                                                </button>
                                                <?php
                                            } else if ($row['hotel_payment_flag'] == 1) {
                                                if ($bal != 1) {
                                                ?>
                                                    <button type="button"
                                                        class="btn btn btn-info btn-sm hotel_payment_modal"
                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                        data-tour-date="<?= $row['date_range'] ?>"
                                                        data-date-number="<?= $row['quotation_day'] ?>"
                                                        data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                        data-tolocation="<?= $row['to_location'] ?>"
                                                        data-bal="<?= $bal_amt ?>"
                                                        data-manu="<?= $detail['manual_id'] ?>"
                                                        data-status="<?= $status ?>"
                                                        <?= $is_target_hotel ? '' : 'disabled' ?>>
                                                        <i class="bi bi-cash-coin me-1"></i>Pay
                                                    </button>
                                                <?php } ?>
                                                <button type="button"
                                                    class="btn btn btn-success btn-sm"
                                                    style="pointer-events: none;">
                                                    <i class="bi bi-check-circle-fill me-1"></i>
                                                    Paid
                                                </button>
                                            <?php } else
                                                                            if ($row['hotel_payment_flag'] == 2) {
                                            ?>
                                                <button type="button"
                                                    class="btn btn btn-info btn-sm hotel_payment_modal"
                                                    data-tour="<?= $row['quotation_id'] ?>"
                                                    data-tour-date="<?= $row['date_range'] ?>"
                                                    data-date-number="<?= $row['quotation_day'] ?>"
                                                    data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                    data-existhotel="<?= $row['hotel_acc'] ?>"
                                                    data-tolocation="<?= $row['to_location'] ?>"
                                                    data-bal="<?= $bal_amt ?>"
                                                    data-manu="<?= $detail['manual_id'] ?>"
                                                    data-status="<?= $status ?>"
                                                    <?= $is_target_hotel ? '' : 'disabled' ?>>
                                                    <i class="bi bi-cash-coin me-1"></i>Pay
                                                </button>
                                                <button type="button"
                                                    class="btn btn btn-danger btn-sm"
                                                    style="pointer-events: none;">
                                                    <i class="bi bi-x-circle-fill me-1"></i>
                                                    Rejected
                                                </button>
                                            <?php
                                            } else
                                                                                if ($row['hotel_payment_flag'] == 3) { ?>
                                                <button type="button"
                                                    class="btn btn btn-info btn-sm hotel_payment_modal"
                                                    data-tour="<?= $row['quotation_id'] ?>"
                                                    data-tour-date="<?= $row['date_range'] ?>"
                                                    data-date-number="<?= $row['quotation_day'] ?>"
                                                    data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                    data-existhotel="<?= $row['hotel_acc'] ?>"
                                                    data-tolocation="<?= $row['to_location'] ?>"
                                                    data-bal="<?= $bal_amt ?>"
                                                    data-manu="<?= $detail['manual_id'] ?>"
                                                    data-status="<?= $status ?>"
                                                    <?= $is_target_hotel ? '' : 'disabled' ?>>
                                                    <i class="bi bi-cash-coin me-1"></i>Pay
                                                </button>
                                                <button type="button"
                                                    class="btn btn btn-warning btn-sm"
                                                    style="pointer-events: none;">
                                                    <i class="bi bi-exclamation-circle-fill me-1"></i>
                                                    Cancelled
                                                </button>
                                                <?php } else
                                                                                    if ($row['hotel_payment_flag'] == 10) {
                                                if ($bal != 1) {
                                                ?>
                                                    <button type="button"
                                                        class="btn btn btn-info btn-sm hotel_payment_modal"
                                                        data-tour="<?= $row['quotation_id'] ?>"
                                                        data-tour-date="<?= $row['date_range'] ?>"
                                                        data-date-number="<?= $row['quotation_day'] ?>"
                                                        data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                        data-existhotel="<?= $row['hotel_acc'] ?>"
                                                        data-tolocation="<?= $row['to_location'] ?>"
                                                        data-bal="<?= $bal_amt ?>"
                                                        data-manu="<?= $detail['manual_id'] ?>"
                                                        data-status="<?= $status ?>"
                                                        <?= $is_target_hotel ? '' : 'disabled' ?>>
                                                        <i class="bi bi-cash-coin me-1"></i>Pay
                                                    </button>
                                                <?php } ?>
                                                <button type="button"
                                                    class="btn btn btn-warning btn-sm"
                                                    disabled>
                                                    <i class="bi bi-hourglass-split me-1"></i>
                                                    Pending
                                                </button>
                                            <?php
                                            } ?>
                                        </td>
                                        <td><?php if ($row['hotel_payment_flag'] != 0) { ?>
                                                <strong class="text-success"><?= $row['pay_doller_count'] ?></strong>
                                            <?php } ?></td>
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
                                <input type="hidden" name="tour_id" id="p_tour_id" readonly>
                                <input type="hidden" name="m_tour_id" id="m_tour_id" readonly>
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

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
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

        function confirm_calculation() {
            var extra = $('#p_extra').val();

            var sgl_total = $('#sgl').val();
            var sgl_cunt = $('#sglcount').val();
            var sgl_foc = $('#sglfoc').val();

            var dbl_total = $('#dbl').val();
            var dbl_cunt = $('#dblcount').val();
            var dbl_foc = $('#dblfoc').val();

            var tpl_total = $('#tpl').val();
            var tpl_cunt = $('#tplcount').val();
            var tpl_foc = $('#tplfoc').val();

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

            extra = parseFloat(extra);
            sgl_total = parseFloat(sgl_total) * (1 - parseFloat(sgl_foc)) * sgl_cunt;
            dbl_total = parseFloat(dbl_total) * (1 - parseFloat(dbl_foc)) * dbl_cunt;
            tpl_total = parseFloat(tpl_total) * (1 - parseFloat(tpl_foc)) * tpl_cunt;

            var total_room_amount = (sgl_total + dbl_total + tpl_total);

            var total_amount = parseFloat(total_room_amount) + extra;

            if ($('#confirm_doller_is_edit').val() == 0) {
                $('#confirm_doller').val(parseFloat(total_amount).toFixed(2));

                var total_advance_payment = total_amount * d_rate;
                $('#confirm_lkr').val(parseFloat(total_advance_payment).toFixed(2));

            } else {

                var total_amount = $('#confirm_doller').val();
                total_amount = parseFloat(total_amount);
                var total_advance_payment = total_amount * d_rate;
                $('#confirm_lkr').val(parseFloat(total_advance_payment).toFixed(2));
            }

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

            $('#p_dates').val(p_dates);


            $.ajax({
                url: '<?php echo base_url(); ?>dashboard2/hotel_management/hotel_payment/get_room_rates',
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


                        $('#HotelPaymentModal').modal('show');
                        $('#pay_date').datepicker({
                            format: 'yyyy-mm-dd',
                            autoclose: true,
                            todayHighlight: true
                        });

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

        //confirm_doller keyup
        $('#confirm_doller').on('keyup', function() {
            $('#confirm_doller_is_edit').val(1);
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
                            url: '<?php echo base_url(); ?>dashboard2/hotel_management/hotel_payment/hotel_pay',
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


    </script>

</body>




</html>