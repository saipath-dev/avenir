<?= view('layouts/head') ?>
<style type="text/css">
    body {
        background-color: #f8f9fa;
    }

    .card-header {
        color: white;
        font-weight: bold;
        border-bottom: 2px solid #f8f9fa;
    }

    .table {
        border-collapse: collapse;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
        cursor: pointer;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }

    .btn {
        transition: 0.3s ease;
    }

    .btn:hover {
        background-color: #0056b3;
        color: white;
    }
</style>

<body class="bg-light">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <!-- Start right Content here -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row mb-4 align-items-center">
                        <div class="col-md-auto">
                            <h4 class="text-primary font-weight-bold mb-0">Hotel Rate Confirmation</h4>
                        </div>
                        <div class="col-md-auto text-end ms-auto">
                            <h6 class="text-secondary mb-0"><?= date('Y-m-d') ?></h6>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow-lg border-0 rounded">
                                    <div class="card-header bg-gradient-light text-dark d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Hotel Payment Requests</h5>
                                    </div>

                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover align-middle mb-0" id="mainTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Hotel Name</th>
                                                        <th class="text-end">Quoted Amount ($)</th>
                                                        <th class="text-end">Requested Amount ($)</th>
                                                        <th>Requested Date</th>
                                                        <th>Confirm</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($paymentRequests)) : ?>
                                                        <?php
                                                        $i = 1;
                                                        $current_tour = null;
                                                        ?>
                                                        <?php foreach ($paymentRequests as $request) : ?>
                                                            <?php if ($request['tour_id'] !== $current_tour) : ?>
                                                                <?php
                                                                $current_tour = $request['tour_id'];
                                                                ?>
                                                                <tr class="table-primary">
                                                                    <td colspan="6">
                                                                        <strong><?= esc($request['tour_id']) ?></strong>
                                                                        <?php if (!empty($request['tour_manual_id'])) : ?>
                                                                            &nbsp;·&nbsp;<span class="text-dark"><?= esc($request['tour_manual_id']) ?></span>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($request['tour_name'])) : ?>
                                                                            &nbsp;·&nbsp;<?= esc($request['tour_name']) ?>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($request['agent_name'])) : ?>
                                                                            &nbsp;<span class="text-muted small">(<?= esc($request['agent_name']) ?>)</span>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($request['tour_start_date'])) : ?>
                                                                            &nbsp;<span class="text-muted small"><?= esc($request['tour_start_date']) ?> - <?= esc($request['tour_end_date']) ?></span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endif; ?>
                                                            <tr>
                                                                <td><?= $i++; ?></td>
                                                                <td><?= esc($request['hotel_name']); ?></td>
                                                                <td class="text-end text-success fw-semibold">
                                                                    <?= number_format((float)($request['total_hotel_pay'] ?? 0), 2) ?>
                                                                </td>
                                                                <td class="text-end text-primary fw-semibold">
                                                                    <span class="editable-amount" data-id="<?= esc($request['id']) ?>" data-max="<?= (float)($request['total_hotel_pay'] ?? 0) ?>" style="cursor:pointer; border-bottom: 1px dashed #0d6efd;" title="Click to edit">
                                                                        <?= number_format((float)($request['requestAmount'] ?? 0), 2) ?>
                                                                    </span>
                                                                </td>
                                                                <td><?= $request['payment_request_date'] ? date('Y-m-d', strtotime($request['payment_request_date'])) : '-' ?></td>
                                                                <td>
                                                                    <button type="button"
                                                                        class="btn btn-info btn-sm hotel_confirm_modal_btn"
                                                                        data-tour="<?= esc($request['tour_id']) ?>"
                                                                        data-tour-date="<?= esc($request['date_range']) ?>"
                                                                        data-existhotel="<?= esc($request['hotel_acc']) ?>">
                                                                        <i class="bi bi-check-lg me-1"></i>Confirm
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else : ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center text-muted">No hotel payment requests found</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                                <?php if (!empty($paymentRequests)) : ?>
                                                    <tfoot class="table-light fw-semibold">
                                                        <tr>
                                                            <td colspan="2" class="text-end">Grand Total</td>
                                                            <td class="text-end text-success">
                                                                <?= number_format(array_sum(array_map('floatval', array_column($paymentRequests, 'total_hotel_pay'))), 2) ?>
                                                            </td>
                                                            <td class="text-end text-primary">
                                                                <?= number_format(array_sum(array_column($paymentRequests, 'requestAmount')), 2) ?>
                                                            </td>
                                                            <td colspan="2"></td>
                                                        </tr>
                                                    </tfoot>
                                                <?php endif; ?>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                <input type="hidden" name="date_number" id="date_number">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div>
                                            <label class="form-label" for="meal_code">Meal Code</label>
                                            <select name="meal_code" id="meal_code" class="form-control"
                                                style="width: 100%;" required disabled>
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
                                            <label class="form-label" for="confirm_doller">Total Quotated Amount($)</label>
                                            <input class="form-control txt_nav" type="text" id="confirm_doller"
                                                name="confirm_doller" placeholder="" readonly />
                                        </div>
                                        <div class="col-md-6" style="padding-left: 0;">
                                            <label class="form-label" for="req_doller">Requested Total Amount($)</label>
                                            <input class="form-control txt_nav" type="text" id="req_doller" name="req_doller" placeholder="" readonly />
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
    </div>

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
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

            $('#confirm_doller').val(parseFloat(total_amount).toFixed(2));

            var req_doller = parseFloat($('#req_doller').val()) || 0;
            var lkr_base = req_doller > 0 ? req_doller : total_amount;
            $('#confirm_lkr').val(parseFloat(lkr_base * d_rate).toFixed(2));
        }

        // .hotel_confirm_modal_btn click
        $(document).on('click', '.hotel_confirm_modal_btn', function(e) {
            e.preventDefault();

            var tour = $(this).data('tour');
            var date = $(this).data('tour-date');
            var existhotel = $(this).data('existhotel');

            $("#c_tour_id").val(tour);
            $("#c_tour_date").val(date);
            $("#c_old_hotel").val(existhotel);

            var safeDates = date.replace(/,/g, '.');

            $.ajax({
                url: '<?php echo base_url(); ?>dashboard2/hotel_management/hotel_booking/getAllDetails/' + tour + '/' + safeDates + '/' + existhotel + '/0',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {

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

                        $('#c_dates').val(date);
                        $('#c_dates_count').val(1);

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

        // #confirm_p_rate keyup
        $('#confirm_p_rate').on('keyup', function() {
            confirm_calculation();
        });


        // Inline edit for Requested Amount
        $(document).on('click', '.editable-amount', function() {
            var $span = $(this);
            if ($span.find('input').length) return;
            var currentVal = $span.text().trim().replace(/,/g, '');
            var id = $span.data('id');
            var $input = $('<input type="number" step="0.01" class="form-control form-control-sm text-end" style="min-width:100px;">').val(currentVal);
            $span.html($input);
            $input.focus().select();

            function saveAmount() {
                var newVal = parseFloat($input.val());
                if (isNaN(newVal) || newVal < 0) {
                    $span.html(parseFloat(currentVal).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                    return;
                }
                var maxVal = parseFloat($span.data('max')) || 0;
                if (maxVal > 0 && newVal > maxVal) {
                    Swal.fire({ icon: 'error', title: 'Amount Exceeded!', text: 'Requested amount ($' + newVal.toFixed(2) + ') cannot exceed the quoted amount ($' + maxVal.toFixed(2) + ').' });
                    $span.html(parseFloat(currentVal).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                    return;
                }
                $.ajax({
                    url: '<?php echo base_url(); ?>tour/tour_accounts/hotel_rate_confirm/update_request_amount',
                    type: 'POST',
                    data: { id: id, amount: newVal.toFixed(2) },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            $span.html(newVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                            Swal.fire({ icon: 'success', title: 'Updated', text: 'Requested amount updated successfully.', timer: 1500, showConfirmButton: false }).then(() => { location.reload(); });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                            $span.html(parseFloat(currentVal).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong!' });
                        $span.html(parseFloat(currentVal).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                    }
                });
            }

            $input.on('blur', saveAmount);
            
            $input.on('keydown', function(e) {
                if (e.key === 'Enter') { $input.blur(); }
                if (e.key === 'Escape') { $span.html(parseFloat(currentVal).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')); }
            });
        });

        // Confirm Hotel
        $("#confirm_hotel_form").on('submit', (function(e) {
            e.preventDefault();

            var pay_request_amount = parseFloat($("#req_doller").val());
            var actual_amount      = parseFloat($("#confirm_doller").val());

            if (!isNaN(pay_request_amount) && pay_request_amount > 0 && actual_amount > pay_request_amount) {
                Swal.fire({ icon: 'error', title: 'Amount Exceeded!', text: 'Confirm amount ($' + actual_amount.toFixed(2) + ') cannot exceed the requested amount ($' + pay_request_amount.toFixed(2) + ').' });
                return;
            }

            if (!isNaN(pay_request_amount) && actual_amount != pay_request_amount) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Requested amount and actual amount do not match.',
                });
                return;
            }

            var formdata = new FormData(this);

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
    </script>

</body>

</html>
