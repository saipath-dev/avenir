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
                    <h2 class="p-title" style="text-align: center;">TRANSPORT ADVANCE</h2>
                </div>
            </div>
        </div>

        <div class="row clearfix mt-3 justify-content-center">
            <div class="col-md-8">
                <div class="card p-4">
                    <div class="body">
                        <div class="row">
                            <?php foreach ($tour_main_details as $detail) { ?>

                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Tour Id</span>
                                        <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $tour_id ?>">
                                        <h6 class="fw-bold" id="t_id"><?php echo $tour_id ?></h6>
                                        <input type="hidden" name="is_settled" id="is_settled" value="<?php echo $detail['transport_settled'] ?>">
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Manual Id</span>
                                        <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $detail['manual_id'] ?>">
                                        <h6 class="fw-bold" id="t_id"><?php echo $detail['manual_id'] ?></h6>
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

    <div class="row mb-4 justify-content-center">
        <div class="col-xxl-8">
            <div class="card">
                <!-- <div class="border">
                    <ul class="nav nav-pills custom-hover-nav-tabs">
                        <li class="nav-item">
                            <a href="#custom-hover-customere" data-bs-toggle="tab" aria-expanded="false" class="nav-link active">
                                <i class="bi bi-wallet nav-icon nav-tab-position"></i>
                                <h5 class="nav-titl nav-tab-position m-0">Advance Payment</h5>
                            </a>
                        </li>
                    </ul>
                </div> -->
                <div class="card-body">
                    <div class="tab-content text-muted">
                        <div class="tab-pane active" id="custom-hover-customere">

                            <form action="javascript:void(0);" id="form_submit_advance" accept-charset="utf-8">
                                <fieldset>
                                    <div class="mb-3">
                                        <label class="form-label">Transport Agent<font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="agent" id="agent" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <?php
                                            echo "<option></option>";
                                            foreach ($drivers as $agent) {
                                                $selected = '';
                                                if ($agent['driver'] == $detail['transport_agent']) {
                                                    $selected = 'selected';
                                                }
                                                echo "<option value='" . esc($agent['driver']) . "' " . $selected . " >" . esc($agent['driver_name']) . "</option>";
                                            }
                                            ?>
                                        </select>

                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>

                                        <select name="bank_acc" id="bank_acc" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <?php echo "<option></option>";
                                            foreach ($bank_acc as $bank) {
                                                echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="ref">Reference No<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="ref" name="ref" placeholder="" required />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="payment">Advance Payment<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="payment" name="payment" placeholder="" required/>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="advance_date">Advance Payment Date<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control" type="text" id="advance_date" name="advance_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                    </div>

                                    <br>
                                    <button type="submit" class="btn btn-success w-100px me-5px" id="add_transport">Save</button>
                                    <br>
                                    <br>
                                </fieldset>
                            </form>

                            <div class="table-responsive">
                                <table class="table mb-0 d-none history_table" style="color: #212529;" id="history_table">
                                    <h6 class="table_title d-none">Payment History (Accepted Vouchers)</h6>
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Date</th>
                                            <th scope="col">Pay</th>
                                            <th scope="col">Voucher No</th>
                                            <th scope="col">Discription</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="payment_history">

                                    </tbody>
                                </table>
                            </div>

                            <br>
                            <div class="table-responsive">
                                <table class="table mb-0 d-none pending_table" style="color: #212529;" id="pending_table">
                                    <h6 class="table_text_voucher d-none">Vouchers Status (Pending, Reject, Cancel)</h6>
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Pay</th>
                                            <th scope="col">Bank</th>
                                            <th scope="col">Voucher Number</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pending_vouchers">

                                    </tbody>
                                </table>
                            </div>
                            <br>
                            <div class="table-responsive">
                                <table class="table mb-0 d-none pending_table" style="color: #212529;" id="pending_table">
                                    <h6 class="table_text_voucher d-none">Advance Requests</h6>
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Pay</th>
                                            <!-- <th scope="col">Bank</th>
                                            <th scope="col">Voucher Number</th> -->
                                            <th scope="col">Amount</th>
                                            <th scope="col">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="advance_requests">

                                    </tbody>
                                </table>
                            </div>

                            <br>
                            <div class="table-responsive">
                                <table class="table mb-0 d-none transfer_history_table" style="color: #212529;" id="transfer_history_table">
                                    <h6 class="table_title_transfer d-none">Transfer History</h6>
                                    <thead class="table-info">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Date</th>
                                            <th scope="col">From Driver</th>
                                            <th scope="col">To Driver</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Voucher No</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transfer_history">

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


    </div>

    <!-- Transfer Modal -->
    <div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferModalLabel">Transfer Amount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="transferForm">
                        <input type="hidden" id="transfer_voucher_no" name="voucher_no">
                        <div class="mb-3">
                            <label class="form-label">Allocated Driver<font color="#FF0000"><strong>*</strong></font></label>
                            <select name="allocated_driver" id="allocated_driver" class="form-control" required>
                                <option value="">Select Driver</option>
                                <?php
                                foreach ($drivers as $driver) {
                                    echo "<option value='" . $driver['driver'] . "'>" . $driver['driver_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transfer Amount<font color="#FF0000"><strong>*</strong></font></label>
                            <input type="number" class="form-control" id="transfer_amount" name="transfer_amount" step="0.01" required>
                            <input type="hidden" id="current_amount" name="current_amount">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmTransfer">Confirm Transfer</button>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });


        $(document).ready(function() {
            var tour_id = $("#tour_id").val();
            load_advance_payment_history();
            // populateAddedInvoice(tour_id);
            // balance_payment(tour_id)
            // getAgentAbsoluteBalance(agent);
        });

        // change agent
        $("#agent").on('change', function(e) {
            load_advance_payment_history();
            // getAgentAbsoluteBalance(agent);
        });

        // change tagent
        $("#tagent").on('change', function(e) {
            var tour_id = $("#tour_id").val();
            populateAddedInvoice(tour_id);
            // getAgentAbsoluteBalance(agent);
        });

        // change transagent
        $("#transagent").on('change', function(e) {
            var tour_id = $("#tour_id").val();
            var agent = $("#transagent").val();
            populateAddedInvoice(tour_id);
            // getAgentAbsoluteBalance(agent);
            balance_payment(tour_id)
            // get_logsheet_mileage(tour_id, agent);
            getAgentAbsoluteBalance(agent);
        });

        // Load Advance Payment History
        function load_advance_payment_history() {
            var agent = $("#agent").val();
            var tour_id = $("#tour_id").val();

            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/driver_advance/payment_history') ?>',
                type: 'GET',
                data: {
                    tour_id: tour_id,
                    agent: agent
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        var html = '';
                        var i = 1;
                        $("#payment_history").empty();
                        data.data.tour_details.forEach(function(value, key) {
                            value = value[0] || null;
                            // console.log(value);
                            if (value != null) {

                                var agent = value.New_L_Code ? value.New_L_Code : '';
                                html += '<tr>';
                                html += '<td>' + i + '</td>';
                                html += '<td>' + value.Date + '</td>';
                                html += '<td>' + agent + '</td>';
                                html += '<td>' + value.Recpt_No + '</td>';
                                html += '<td>' + value.Discription + '</td>';
                                html += '<td>' + value.Dr + '</td>';
                                html += '<td><button class="btn btn-sm btn-primary transfer-btn" data-voucher="' + value.Recpt_No + '" data-amount="' + value.Dr + '">Transfer</button></td>';
                                html += '</tr>';
                                i++;
                            }
                        });

                        $("#payment_history").html(html);

                        // check tour_details length
                        if (data.data.tour_details.length > 0) {
                            $(".history_table").removeClass('d-none');
                            $(".table_title").removeClass('d-none');
                        } else {
                            $(".history_table").addClass('d-none');
                            $(".table_title").addClass('d-none');
                        }

                        var htmlp = '';
                        var j = 1;
                        $("#pending_vouchers").empty();
                        $.each(data.data.voucherpayment, function(key, value) {
                            var status = value.Aflag;
                            var statusText = '';
                            if (status == 0) {
                                statusText = 'Pending';
                            } else if (status == 1) {
                                statusText = 'Paid';
                            } else if (status == 2) {
                                statusText = 'Rejected';
                            } else {
                                statusText = 'Cancelled';
                            }
                            htmlp += '<tr>';
                            htmlp += '<td>' + j + '</td>';
                            htmlp += '<td>' + value.Pay + '</td>';
                            htmlp += '<td>' + value.Bank + '</td>';
                            htmlp += '<td>' + value.Vnum + '</td>';
                            htmlp += '<td id="amount_' + value.Vnum + '">' + value.Amount + '</td>';
                            htmlp += '<td>' + statusText + '</td>';
                            htmlp += '<td>';
                            if (status == 0) {
                                htmlp += '<button class="btn btn-sm btn-primary me-1 edit-advance" data-voucher-id="' + value.Vnum + '" data-amount="' + value.Amount + '"><i class="bi bi-pencil"></i> Edit</button>';
                                htmlp += '<button class="btn btn-sm btn-danger delete-advance" data-voucher-id="' + value.Vnum + '"><i class="bi bi-trash"></i> Delete</button>';
                            } else {
                                htmlp += '<span class="text-muted">No actions</span>';
                            }
                            htmlp += '</td>';
                            htmlp += '</tr>';
                            j++;
                        });

                        $("#pending_vouchers").html(htmlp);
                        // check voucherpayment length
                        if (data.data.voucherpayment.length > 0) {
                            $(".pending_table").removeClass('d-none');
                            $(".table_text_voucher").removeClass('d-none');
                        } else {
                            $(".pending_table").addClass('d-none');
                            $(".table_text_voucher").addClass('d-none');
                        }

                        var htmla = '';
                        var k = 1;
                        $("#advance_requests").empty();
                        $.each(data.data.advance_payment_requests, function(key, value) {
                            // console.log(value);
                            var status = value.flag;
                            if (status == 0) {
                                status = 'Pending';
                            } else if (status == 1) {
                                status = 'Approved';
                            } else {
                                status = 'Rejected';
                            }
                            htmla += '<tr>';
                            htmla += '<td>' + k + '</td>';
                            htmla += '<td>' + value.driver_name + '</td>';
                            // htmla += '<td>' + value.bank + '</td>';
                            // htmla += '<td>' + value.voucher_no + '</td>';
                            htmla += '<td>' + value.amount + '</td>';
                            htmla += '<td>' + status + '</td>';
                            htmla += '</tr>';
                            k++;
                            $('#payment').val(value.amount);
                        });

                        $("#advance_requests").html(htmla);
                     
                        // check advance_payment_requests length
                        if (data.data.advance_payment_requests.length > 0) {
                            $("#advance_requests").closest('table').removeClass('d-none');
                            $("#advance_requests").closest('table').prev('h6').removeClass('d-none');
                        } else {
                            $("#advance_requests").closest('table').addClass('d-none');
                            $("#advance_requests").closest('table').prev('h6').addClass('d-none');
                        }

                        // Populate Transfer History
                        var htmlt = '';
                        var t = 1;
                        $("#transfer_history").empty();
                        $.each(data.data.transfer_history, function(key, value) {
                            htmlt += '<tr>';
                            htmlt += '<td>' + t + '</td>';
                            htmlt += '<td>' + value.date + '</td>';
                            if (value.type === 'out') {
                                htmlt += '<td><span class="badge bg-danger">' + value.from_driver_name + '</span></td>';
                                htmlt += '<td><span class="badge bg-success">' + value.to_driver_name + '</span></td>';
                                htmlt += '<td class="text-danger fw-bold">- Rs. ' + parseFloat(value.amount).toFixed(2) + '</td>';
                            } else {
                                htmlt += '<td><span class="badge bg-secondary">Other Driver</span></td>';
                                htmlt += '<td><span class="badge bg-success">' + value.to_driver_name + '</span></td>';
                                htmlt += '<td class="text-success fw-bold">+ Rs. ' + parseFloat(value.amount).toFixed(2) + '</td>';
                            }
                            htmlt += '<td>' + value.voucher_no + '</td>';
                            htmlt += '</tr>';
                            t++;
                        });

                        $("#transfer_history").html(htmlt);

                        // Show/hide transfer history table
                        if (data.data.transfer_history.length > 0) {
                            $(".transfer_history_table").removeClass('d-none');
                            $(".table_title_transfer").removeClass('d-none');
                        } else {
                            $(".transfer_history_table").addClass('d-none');
                            $(".table_title_transfer").addClass('d-none');
                        }


                    } else {

                        console.error(data.message ? data.message : 'An error occured while processing the request');

                    }
                },
                error: function() {
                    // Swal.fire({
                    //     icon: 'error',
                    //     title: 'Error',
                    //     text: 'An error occured while processing the request',
                    // });
                }
            });

            // populateAddedInvoice(tour_id);
            // balance_payment(tour_id)
            // getAgentAbsoluteBalance(agent);
        }

        // Transfer Button Click Handler
        $(document).on('click', '.transfer-btn', function() {
            var voucherNo = $(this).data('voucher');
            var amount = $(this).data('amount');

            $('#transfer_voucher_no').val(voucherNo);
            $('#transfer_amount').val(amount);
            $('#current_amount').val(amount);
            $('#allocated_driver').val('');

            // tf-payguard-r1: one operation key per opened transfer form; a repeated submission of the same form is refused by the server
            window.pgTransferOpKey = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : ('op-' + Date.now() + '-' + Math.random().toString(16).slice(2));
            $('#transferModal').modal('show');
        });

        // Confirm Transfer
        $('#confirmTransfer').on('click', function() {
            var allocatedDriver = $('#allocated_driver').val();
            var transferAmount = $('#transfer_amount').val();
            var voucherNo = $('#transfer_voucher_no').val();
            var currentAmount = $('#current_amount').val();
            var tourId = $('#tour_id').val();

            // Validation
            if (!allocatedDriver) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select an allocated driver',
                });
                return;
            }

            if (!transferAmount || transferAmount <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please enter a valid transfer amount',
                });
                return;
            }

            // AJAX call to process transfer
            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/driver_advance/transfer_amount') ?>',
                type: 'POST',
                data: {
                    tour_id: tourId,
                    voucher_no: voucherNo,
                    allocated_driver: allocatedDriver,
                    transfer_amount: transferAmount,
                    current_driver: $('#agent').val(),
                    driver_name: $('#allocated_driver option:selected').text(),
                    current_amount: currentAmount,
                    op_key: window.pgTransferOpKey || ''
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        // console.log(data);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $('#transferModal').modal('hide');
                                load_advance_payment_history();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message ? data.message : 'An error occurred while processing the transfer',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing the transfer',
                    });
                }
            });
        });

        // Add Advance Payment
        $("#form_submit_advance").on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);
            var tour_id = $('#tour_id').val();
            var agent = $('#agent').val();
            var agent_name = $('#agent option:selected').text();
            var ref_no = $('#ref').val();
            var payment = $('#payment').val();
            var bank_acc = $('#bank_acc').val();
            var bank_name = $('#bank_acc option:selected').text();
            var advance_date = $('#advance_date').val();
            // get t_consignee value
            var t_consignee = $('#t_consignee').text();

            // check required fields
            if (agent == '' || ref_no == '' || payment == '' || bank_acc == '' || advance_date == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please fill all required fields',
                });
                return;
            }

            formData.append('tour_id', tour_id);
            formData.append('agent', agent);
            formData.append('agent_name', agent_name);
            formData.append('ref_no', ref_no);
            formData.append('payment', payment);
            formData.append('bank_acc', bank_acc);
            formData.append('bank_name', bank_name);
            formData.append('advance_date', advance_date);
            formData.append('manu_id', t_consignee);

            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/driver_advance/tour_transport') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message ? data.message : 'An error occurred while processing the request',
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing the request',
                    });
                }
            });
        });

        // Edit Advance Payment
        $(document).on('click', '.edit-advance', function() {
            var voucherId = $(this).data('voucher-id');
            var currentAmount = $(this).data('amount');
            var tourId = $('#tour_id').val();

            Swal.fire({
                title: 'Update Advance Amount',
                html: '<input type="number" id="swal-new-amount" class="swal2-input" placeholder="Enter new amount" value="' + currentAmount + '" step="0.01" min="0.01">',
                showCancelButton: true,
                confirmButtonText: 'Update',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                preConfirm: () => {
                    const newAmount = document.getElementById('swal-new-amount').value;
                    if (!newAmount || parseFloat(newAmount) <= 0) {
                        Swal.showValidationMessage('Please enter a valid amount greater than zero');
                        return false;
                    }
                    return newAmount;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    var newAmount = result.value;

                    $.ajax({
                        url: '<?php echo base_url('dashboard2/transport/driver_advance/update_advance') ?>',
                        type: 'POST',
                        data: {
                            voucher_id: voucherId,
                            new_amount: newAmount,
                            tour_id: tourId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message ? response.message : 'Failed to update advance amount',
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while updating the advance',
                            });
                        }
                    });
                }
            });
        });

        // Delete Advance Payment
        $(document).on('click', '.delete-advance', function() {
            var voucherId = $(this).data('voucher-id');
            var tourId = $('#tour_id').val();

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this pending advance payment?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?php echo base_url('dashboard2/transport/driver_advance/delete_advance') ?>',
                        type: 'POST',
                        data: {
                            voucher_id: voucherId,
                            tour_id: tourId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message ? response.message : 'Failed to delete advance',
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while deleting the advance',
                            });
                        }
                    });
                }
            });
        });
    </script>

</body>




</html>