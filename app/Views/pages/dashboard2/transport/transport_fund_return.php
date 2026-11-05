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
                    <h2 class="p-title" style="text-align: center;">Driver Fund Return</h2>
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
                                                $selected = $driver_id;
                                                if ($agent['driver'] == $detail['transport_agent']) {
                                                    $selected = 'selected';
                                                }
                                                echo "<option value='" . $agent['driver'] . "' " . $selected . " >" . $agent['driver_name'] . "</option>";
                                            }
                                            ?>
                                        </select>

                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="ref">Payment Type<font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="payment_type" id="payment_type" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <option value=""></option>
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Over Payment">Over Payment</option>
                                        </select>
                                    </div>

                                    <div class="mb-3" style="display: none;">
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
                                        <label class="form-label" for="ref">Reference No</label>
                                        <input class="form-control txt_nav" type="text" id="ref" name="ref" placeholder="" />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="payment">Amount<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="payment" name="payment" placeholder="" required />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="advance_date">Paymet Date<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control" type="text" id="advance_date" name="advance_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                    </div>
                                     <div class="mb-3">
                                        <label class="form-label" for="reason">Reason<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control" type="text" id="reason" name="reason" placeholder="" required />
                                    </div>

                                    <br>
                                    <button type="submit" class="btn btn-success w-100px me-5px" id="add_transport">Save</button>
                                    <br>
                                    <br>
                                </fieldset>
                            </form>

                        </div>

                    </div>
                </div><!-- end card-body -->
            </div>
        </div>
        <!--end col-->

    </div>

    <!-- Refund History Table -->
    <div class="row mb-4 justify-content-center">
        <div class="col-xxl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Refund History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="refund_history_table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Agent</th>
                                    <th>Payment Type</th>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody id="refund_history_body">
                                <tr>
                                    <td colspan="6" class="text-center">Select a transport agent to view refund history</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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

            // Load refund history on page load if agent is already selected
            var initialAgent = $('#agent').val();
            var tour_id = $('#tour_id').val();
            if (initialAgent && tour_id) {
                loadRefundHistory(tour_id, initialAgent);
            }

            // Load refund history when agent changes
            $('#agent').on('change', function() {
                var tour_id = $('#tour_id').val();
                var agent = $(this).val();

                if (agent) {
                    loadRefundHistory(tour_id, agent);
                } else {
                    $('#refund_history_body').html('<tr><td colspan="6" class="text-center">Select a transport agent to view refund history</td></tr>');
                }
            });

            // Function to load refund history
            function loadRefundHistory(tour_id, agent) {
                $.ajax({
                    url: '<?php echo base_url(); ?>dashboard2/transport/fund_return/get_refund_history',
                    type: 'POST',
                    data: {
                        tour_id: tour_id,
                        agent: agent
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 200) {
                            var html = '';
                            if (response.data.length > 0) {
                                response.data.forEach(function(item) {
                                    var paymentType = '';
                                    if (item.advances_pay == 1111) {
                                        paymentType = item.Discription.includes('Cash') ? 'Cash' : 'Bank Transfer';
                                    } else if (item.advances_pay == 11111) {
                                        paymentType = 'Over Payment';
                                    }

                                    html += '<tr>';
                                    html += '<td>' + item.Date + '</td>';
                                    html += '<td>' + item.payee + '</td>';
                                    html += '<td>' + paymentType + '</td>';
                                    html += '<td>' + (item.Recpt_No || '-') + '</td>';
                                    html += '<td>' + parseFloat(item.Cr).toFixed(2) + '</td>';
                                    html += '<td>' + item.Discription + '</td>';
                                
                                    html += '</tr>';
                                });
                            } else {
                                html = '<tr><td colspan="6" class="text-center">No refund history found</td></tr>';
                            }
                            $('#refund_history_body').html(html);
                        } else {
                            $('#refund_history_body').html('<tr><td colspan="6" class="text-center text-danger">Error loading refund history</td></tr>');
                        }
                    },
                    error: function() {
                        $('#refund_history_body').html('<tr><td colspan="6" class="text-center text-danger">Error loading refund history</td></tr>');
                    }
                });
            }

            // Show/hide bank account dropdown based on payment type
            $('#payment_type').on('change', function() {
                var paymentType = $(this).val();
                if (paymentType === 'Bank Transfer') {
                    $('#bank_acc').closest('.mb-3').show();
                    $('#bank_acc').prop('required', true);
                } else {
                    $('#bank_acc').closest('.mb-3').hide();
                    $('#bank_acc').prop('required', false);
                }
            });

            // Form submission
            $('#form_submit_advance').on('submit', function(e) {
                e.preventDefault();

                var tour_id = $('#tour_id').val();
                var agent = $('#agent').val();
                var agent_name = $('#agent option:selected').text();
                var payment_type = $('#payment_type').val();
                var bank_acc = $('#bank_acc').val();
                var bank_name = $('#bank_acc option:selected').text();
                var ref = $('#ref').val();
                var reason = $('#reason').val();
                var payment = $('#payment').val();
                var advance_date = $('#advance_date').val();
                var manu_id = $('#t_id').text();

                // Validation
                if (!agent) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select a transport agent'
                    });
                    return;
                }

                if (!payment_type) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select a payment type'
                    });
                    return;
                }

                if (payment_type === 'Bank Transfer' && !bank_acc) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select a bank account'
                    });
                    return;
                }

                if (!payment || payment <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter a valid amount'
                    });
                    return;
                }

                if (!advance_date) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please select a date'
                    });
                    return;
                }

                // Submit data
                $.ajax({
                    url: '<?php echo base_url(); ?>dashboard2/transport/fund_return/submit_fund_return',
                    type: 'POST',
                    data: {
                        tour_id: tour_id,
                        agent: agent,
                        agent_name: agent_name,
                        payment_type: payment_type,
                        bank_acc: bank_acc,
                        bank_name: bank_name,
                        ref_no: ref,
                        payment: payment,
                        advance_date: advance_date,
                        reason: reason,
                        manu_id: manu_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while processing your request'
                        });
                    }
                });
            });
        });
    </script>

</body>




</html>