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
                    <h2 class="p-title" style="text-align: center;">Hotel Reservation</h2>
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
                                    <th scope="col" style="width: 15%;">Check-in / Check-out</th>
                                    <th scope="col" style="width: 25%;">Hotel Name</th>
                                    <th scope="col" style="width: 12%;">Room Type</th>
                                    <th scope="col" style="width: 15%;">Meal Plan</th>
                                    <th scope="col" style="width: 15%;">Room Count</th>
                                    <th scope="col" style="width: 10%;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($tour_details2 as $row) {
                                    $min = $row['start_date'];
                                    $max = $row['end_date'];

                                    $meal_code = $row['meal_code'];
                                    if ($meal_code == 'bb') {
                                        $meal = 'BB';
                                    } else if ($meal_code == 'hb') {
                                        $meal = 'HB';
                                    } else if ($meal_code == 'fb') {
                                        $meal = 'FB';
                                    } else {
                                        $meal = 'N/A';
                                    }
                                ?>
                                    <tr>
                                        <td><strong><?= $i++; ?></strong></td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <small class="text-muted mb-1"><i class="bi bi-box-arrow-in-right me-1"></i>Check-in:</small>
                                                <strong class="date-range-cell"><?= $min ?></strong>
                                                <small class="text-muted mt-2 mb-1"><i class="bi bi-box-arrow-right me-1"></i>Check-out:</small>
                                                <strong class="date-range-cell"><?= $max ?></strong>
                                            </div>
                                        </td>
                                        <td class="hotel-name-cell">
                                            <i class="bi bi-building me-2 text-muted"></i><?= $row['hotel_name'] ?>
                                        </td>
                                        <td><?= $row['rtype'] ?></td>
                                        <td>
                                            <span class="badge-meal-plan"><?php echo $meal; ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <?php if ($consignee['sgl'] > 0): ?>
                                                <div class="room-count-item">
                                                    <span class="room-count-label">SGL:</span>
                                                    <span class="room-count-value"><?php echo $consignee['sgl']; ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($consignee['dbl'] > 0): ?>
                                                <div class="room-count-item">
                                                    <span class="room-count-label">DBL:</span>
                                                    <span class="room-count-value"><?php echo $consignee['dbl']; ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($consignee['dbl_twin'] > 0): ?>
                                                <div class="room-count-item">
                                                    <span class="room-count-label">TDBL:</span>
                                                    <span class="room-count-value"><?php echo $consignee['dbl_twin']; ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($consignee['tpl'] > 0): ?>
                                                <div class="room-count-item">
                                                    <span class="room-count-label">TPL:</span>
                                                    <span class="room-count-value"><?php echo $consignee['tpl']; ?></span>
                                                </div>
                                                <?php endif; ?>
                                                
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-email-hotel btn-sm open_em_modal"
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
                                                data-dbl_twin="<?= $consignee['dbl_twin'] ?>"
                                                data-tpl="<?= $consignee['tpl'] ?>"
                                                data-sgl-rate="<?= $row['sgl'] ?>"
                                                data-dbl-rate="<?= $row['dbl'] ?>"
                                                data-tpl-rate="<?= $row['tpl'] ?>"
                                                data-room-type="<?= $row['rtype'] ?>">
                                                <i class="bi bi-envelope me-1"></i>Send Email
                                            </button>
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

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
            var tdbl = $(this).data('dbl_twin');
            var tpl = $(this).data('tpl');
            var room_type = $(this).data('room-type');
            var room_count = '';
            if (sgl > 0) {
                room_count += sgl + ' Single ';
            }
            // If twin is selected, show only twin; otherwise show double
            if (tdbl > 0) {
                room_count += tdbl + ' Double Twin ';
            } else if (dbl > 0) {
                room_count += dbl + ' Double ';
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
                            <td style="border: 1px solid black; padding: 8px;">${rates.replace(/(\d+\.\d{0,2})\d*/g, (m) => parseFloat(m).toFixed(2))}</td>
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
                                 <span style="font-family: Arial, sans-serif;"><?= $user_email ?? 'traveljp@avenirholidays.com , land@avenirholidays.com' ?></span>
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
                        url: '<?php echo base_url(); ?>dashboard2/hotel_management/hotel_reservation/send_email',
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
    </script>

</body>




</html>