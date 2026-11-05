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
                    <h2 class="p-title" style="text-align: center;">Hotel Confirmation</h2>
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
                        <h5 class="mb-0"><i class="bi bi-building me-2"></i>Hotel Reservations</h5>
                        <span class="badge bg-light text-dark"><?= count($tour_details2) ?> Reservation<?= count($tour_details2) > 1 ? 's' : '' ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table hotel-reservation-table">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 3%;">#</th>
                                    <th scope="col" style="width: 20%;">Day</th>
                                    <th scope="col" style="width: 25%;">Hotel Name</th>
                                    <th scope="col" style="width: 18%;">Confirm</th>
                                    <th scope="col" style="width: 12%;">Cancel</th>
                                    <th scope="col" style="width: 10%;">$ Amount</th>
                                    <th scope="col" style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($tour_details as $row) {
                                    $min = $row['start_date'];
                                    $max = $row['end_date'];
                                    if ($min == $max) {
                                        $daterang = $row['start_date'] = $min;
                                    } else {
                                        $daterang = $row['start_date'] = $min . "  -  " . $max;
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
                                            <?php if ($row['booking_confirmed_flag'] == 0) { ?>
                                                <button type="button"
                                                    class="btn btn btn-info btn-sm <?= $isActiveHotel ? 'hotel_confirm_modal_btn' : '' ?>"
                                                    <?= $isActiveHotel ? '' : 'disabled' ?>
                                                    data-tour="<?= $row['quotation_id'] ?>"
                                                    data-tour-date="<?= $row['date_range'] ?>"
                                                    data-date-number="<?= $row['quotation_day'] ?>"
                                                    data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                    data-existhotel="<?= $row['hotel_acc'] ?>"
                                                    data-tolocation="<?= $row['to_location'] ?>"
                                                    <?= $is_target_hotel ? '' : 'disabled' ?>>
                                                    <i class="bi bi-check-lg me-1"></i>Confirm
                                                </button>
                                            <?php } else if ($row['booking_confirmed_flag'] == 1) { ?>
                                                <button type="button"
                                                    class="btn btn btn-success btn-sm"
                                                    style="pointer-events: none;">
                                                    <i class="bi bi-check-circle-fill me-1"></i>
                                                    Confirmed
                                                </button>
                                            <?php } else if ($row['booking_confirmed_flag'] == 2) { ?>
                                                <button type="button"
                                                    class="btn btn btn-info btn-sm <?= $isActiveHotel ? 'hotel_confirm_modal_btn' : '' ?>"
                                                    <?= $isActiveHotel ? '' : 'disabled' ?>
                                                    data-tour="<?= $row['quotation_id'] ?>"
                                                    data-tour-date="<?= $row['date_range'] ?>"
                                                    data-date-number="<?= $row['quotation_day'] ?>"
                                                    data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                    data-existhotel="<?= $row['hotel_acc'] ?>"
                                                    data-tolocation="<?= $row['to_location'] ?>"
                                                    <?= $is_target_hotel ? '' : 'disabled' ?>>
                                                    <i class="bi bi-check-lg me-1"></i>Confirm
                                                </button>
                                                <button type="button"
                                                    class="btn btn btn-danger btn-sm"
                                                    style="pointer-events: none;">
                                                    <i class="bi bi-x-circle-fill me-1"></i>
                                                    Rejected
                                                </button>
                                            <?php } else if ($row['booking_confirmed_flag'] == 3) { ?>
                                                <button type="button"
                                                    class="btn btn btn-info btn-sm <?= $isActiveHotel ? 'hotel_confirm_modal_btn' : '' ?>"
                                                    <?= $isActiveHotel ? '' : 'disabled' ?>
                                                    data-tour="<?= $row['quotation_id'] ?>"
                                                    data-tour-date="<?= $row['date_range'] ?>"
                                                    data-date-number="<?= $row['quotation_day'] ?>"
                                                    data-existhotelcode="<?= $row['hotel_code'] ?>"
                                                    data-existhotel="<?= $row['hotel_acc'] ?>"
                                                    data-tolocation="<?= $row['to_location'] ?>"
                                                    <?= $is_target_hotel ? '' : 'disabled' ?>>
                                                    <i class="bi bi-check-lg me-1"></i>Confirm
                                                </button>
                                                <button type="button"
                                                    class="btn btn btn-warning btn-sm"
                                                    style="pointer-events: none;">
                                                    <i class="bi bi-exclamation-circle-fill me-1"></i>
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
                                                    data-tour-date="<?= $row['date_range'] ?>"
                                                    data-date-number="<?= $row['quotation_day'] ?>">
                                                    <i class="bi bi-x-lg me-1"></i>Cancel
                                                </button>
                                            <?php } ?>
                                        </td>
                                        <td><?php if ($row['booking_confirmed_flag'] == 1) { ?>
                                                <strong class="text-success"><?= $row['total_hotel_doller'] ?></strong>
                                            <?php } ?></td>
                                        <td>
                                            <?php if (!empty($filter_hotel_id)) { ?>
                                                <a href="<?= base_url('dashboard2/hotel_management/hotel_payment?tour_id=' . $row['quotation_id'] . '&hotel_id=' . urlencode($row['hotel_acc'])) ?>"
                                                    class="btn btn-warning btn-sm <?= ($row['hotel_acc'] !== $filter_hotel_id) ? 'disabled' : '' ?>"
                                                    <?= ($row['hotel_acc'] !== $filter_hotel_id) ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                                                    <i class="bi bi-cash-coin me-1"></i>Payment
                                                </a>
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
            </div>
        </div>
    </div>
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
                                <input type="hidden" name="confirm_doller_is_edit" id="confirm_doller_is_edit" value="0">
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
                                        <div class="row">
                                            <div class="col-6" style="padding-right: 0;">
                                                <input class="form-control" type="text" id="sglcount" name="sglcount"
                                                    placeholder="Count" readonly />
                                            </div>
                                            <div class="col-6" style="padding-left: 0;">
                                                <input class="form-control" type="text" id="sglfoc" name="sglfoc"
                                                    placeholder="SGL FOC" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted" for="">DBL</label>
                                        <div class="row">
                                            <div class="col-6" style="padding-right: 0;">
                                                <input class="form-control" type="text" id="dblcount" name="dblcount"
                                                    placeholder="Count" readonly />
                                            </div>
                                            <div class="col-6" style="padding-left: 0;">
                                                <input class="form-control" type="text" id="dblfoc" name="dblfoc"
                                                    placeholder="DBL FOC" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted" for="">TPL</label>
                                        <div class="row">
                                            <div class="col-6" style="padding-right: 0;">
                                                <input class="form-control" type="text" id="tplcount" name="tplcount"
                                                    placeholder="Count" readonly />
                                            </div>
                                            <div class="col-6" style="padding-left: 0;">
                                                <input class="form-control" type="text" id="tplfoc" name="tplfoc"
                                                    placeholder="TPL FOC" />
                                            </div>
                                        </div>
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
                                    <div class="row">
                                        <div class="col-md-6" style="padding-right: 0;">
                                            <label class="form-label" for="confirm_doller">Total Amount($)<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control txt_nav" type="text" id="confirm_doller"
                                                name="confirm_doller" placeholder="" required />
                                        </div>
                                        <div class="col-md-6" style="padding-left: 0;">
                                            <label class="form-label" for="req_doller">Requested Total Amount($)</label>
                                            <input class="form-control txt_nav" type="text" id="req_doller" name="req_doller" placeholder="" />
                                        </div>
                                    </div>
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

        $(".hotel_confirm_modal_btn").on('click', function(e) {
            e.preventDefault();

            var tour = $(this).data('tour');
            var date = $(this).data('tour-date');
            var date_number = $(this).data('date-number');
            var existhotel = $(this).data('existhotel');
            var existhotelcode = $(this).data('existhotelcode');
            var to_location = $(this).data('tolocation');
            let parts = to_location.split(','); // Split the string by commas
            let lastWord = parts[parts.length - 1].trim();
            // date = from_date

            $("#c_tour_id").val(tour);
            $("#c_tour_date").val(date);
            $("#c_old_hotel").val(existhotel);
            $("#old_hotel_code").val(existhotelcode);

            // alert('Please wait while we fetch the hotel details...');
            var safeDates = date.replace(/,/g, '.');

            $.ajax({
                url: '<?php echo base_url(); ?>dashboard2/hotel_management/hotel_booking/getAllDetails/' + tour + '/' + safeDates + '/' + existhotel + '/' + date_number,
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
                        $("#sglfoc").val(response.data.quotationDetails.sglfoc || 0);
                        $("#dblfoc").val(response.data.quotationDetails.dblfoc || 0);
                        $("#tplfoc").val(response.data.quotationDetails.tplfoc || 0);
                        $("#req_doller").val(response.data.quotationDetails.pay_request_amount);
                        // $("#finalize_txt").val(response.data.quotationDetails.finalize_txt);

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


            $("#HotelConfirmedModal").modal('show');

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

        // Confirm Hotel
        $("#confirm_hotel_form").on('submit', (function(e) {
            e.preventDefault();

            var pay_request_amount = parseFloat($("#req_doller").val());
            var actual_amount = parseFloat($("#confirm_doller").val());

            if (actual_amount != pay_request_amount) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Requested amount and actual amount do not match.',
                });
                return;
            }

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
                        url: '<?php echo base_url(); ?>dashboard2/hotel_management/hotel_booking/confirm_reservation',
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
                            url: '<?php echo base_url(); ?>dashboard2/hotel_management/hotel_booking/cancel_booking',
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