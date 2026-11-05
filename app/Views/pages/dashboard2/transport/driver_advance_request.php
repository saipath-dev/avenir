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
                    <h2 class="p-title" style="text-align: center;">DRIVER ADVANCE REQUEST</h2>
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
                                        <input type="hidden" name="manual_id" id="manual_id" value="<?php echo $detail['manual_id'] ?>">
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
        <div class="col-xxl-10">
            <div class="row g-3">
                <!-- Left: form -->
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="mb-3 text-muted" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">New Advance Request</h6>
                            <form action="javascript:void(0);" id="form_submit_advance_request" accept-charset="utf-8">
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
                                                echo "<option value='" . $agent['driver'] . "' " . $selected . " >" . $agent['driver_name'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="advance_amount">Advance Amount<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="advance_amount" name="advance_amount" placeholder="" required />
                                    </div>

                                    <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Right: suggested advance breakdown -->
                <div class="col-lg-7">
                    <div class="card h-100" id="suggested_advance_box" style="display:none;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 text-muted" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;">Suggested Advance</h6>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="apply_suggested_advance">Use This Amount</button>
                            </div>
                            <div class="fw-bold text-primary" style="font-size:1.6rem;" id="suggested_advance_value">LKR 0.00</div>
                            <div class="text-muted mb-3" style="font-size:0.76rem;" id="suggested_advance_note">30% of transport cost</div>

                            <table class="table table-sm mb-0" style="font-size:0.85rem;">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Transport Advance (30% of transport cost)</td>
                                        <td class="text-end">LKR <span id="sa_transport">0.00</span></td>
                                    </tr>
                                </tbody>
                            </table>

                            <hr class="my-3">
                            <h6 class="text-muted mb-2" style="font-size:0.76rem; text-transform:uppercase; letter-spacing:0.05em;">Activity Breakdown</h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" style="font-size:0.82rem;">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Activity</th>
                                            <th class="text-end">LKR</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sa_activity_lines"></tbody>
                                </table>
                            </div>

                            <hr class="my-3">
                            <table class="table table-sm mb-0" style="font-size:0.85rem;">
                                <tbody>
                                    <tr id="sa_activity_row">
                                        <td class="text-muted">Driver-Borne Activity Cost</td>
                                        <td class="text-end">LKR <span id="sa_activity">0.00</span></td>
                                    </tr>
                                    <tr id="sa_misc_buffer_row">
                                        <td class="text-muted">
                                            Parking/Toll &amp; Airport Expense Buffer
                                            <i class="bi bi-info-circle text-muted" style="font-size:0.7rem;" title="Flat estimate — these costs are usually only recorded after the tour and aren't itemized in the activity list."></i>
                                        </td>
                                        <td class="text-end">LKR <span id="sa_misc_buffer">0.00</span></td>
                                    </tr>
                                    <tr id="sa_extras_skipped_row" style="display:none;">
                                        <td class="text-muted">
                                            Total (Activity + Buffer)
                                            <span class="badge bg-light text-secondary border ms-1" style="font-size:0.65rem;">Not added</span>
                                        </td>
                                        <td class="text-end">LKR <span id="sa_extras_amount">0.00</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table mb-0 d-none history_table" style="color: #212529;" id="history_table">
                            <h6 class="table_title d-none">Advance Request History</h6>
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Driver</th>
                                    <th scope="col">Advance Amount</th>
                                    <th scope="col">Requested User</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Payment</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="payment_history">

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
            var tour_id = $("#tour_id").val();
            load_advance_request_history();
            load_suggested_advance();
        });

        function load_suggested_advance() {
            var tour_id = $("#tour_id").val();

            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/driver_allow/suggested_advance') ?>',
                type: 'GET',
                data: {
                    tour_id: tour_id
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        $("#sa_transport").text(parseFloat(data.transport_advance).toFixed(2));
                        $("#sa_activity").text(parseFloat(data.driver_borne_activity_cost).toFixed(2));
                        $("#sa_misc_buffer").text(parseFloat(data.misc_expense_buffer).toFixed(2));

                        var extras = parseFloat(data.driver_borne_activity_cost) + parseFloat(data.misc_expense_buffer);
                        if (data.extras_included) {
                            $("#sa_extras_skipped_row").hide();
                            $("#suggested_advance_note").text('30% of transport cost + driver-borne activity cost + parking/toll/airport buffer');
                        } else {
                            $("#sa_extras_amount").text(extras.toFixed(2));
                            $("#sa_extras_skipped_row").show();
                            $("#suggested_advance_note").text('30% of transport cost');
                        }

                        $("#suggested_advance_value").text('LKR ' + parseFloat(data.suggested_advance).toFixed(2));
                        $("#apply_suggested_advance").attr('data-amount', data.suggested_advance);

                        var linesHtml = '';
                        if (data.activity_lines && data.activity_lines.length) {
                            data.activity_lines.forEach(function(line) {
                                var statusBadge = line.excluded ?
                                    '<span class="badge bg-secondary" title="' + line.exclude_reason + '">Excluded</span>' :
                                    '<span class="badge bg-success">Driver-Borne</span>';
                                linesHtml += '<tr' + (line.excluded ? ' class="text-muted"' : '') + '>';
                                linesHtml += '<td>' + line.tour_date + '</td>';
                                linesHtml += '<td>' + line.place_name + '</td>';
                                linesHtml += '<td class="text-end">' + parseFloat(line.lkr_fee).toFixed(2) + '</td>';
                                linesHtml += '<td>' + statusBadge + '</td>';
                                linesHtml += '</tr>';
                            });
                        } else {
                            linesHtml = '<tr><td colspan="4" class="text-muted text-center py-2">No activities recorded for this tour.</td></tr>';
                        }
                        $("#sa_activity_lines").html(linesHtml);

                        $("#suggested_advance_box").show();
                    }
                },
                error: function() {
                    // Suggested advance is a convenience feature — fail silently, the
                    // amount field still works as a plain manual entry either way.
                }
            });
        }

        $("#apply_suggested_advance").on('click', function() {
            var amount = $(this).attr('data-amount');
            $("#advance_amount").val(amount);
        });

        $("#agent").on('change', function(e) {
            load_advance_request_history();
        });

        function load_advance_request_history() {
            var agent = $("#agent").val();
            var tour_id = $("#tour_id").val();

            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/driver_allow/advance_request_history') ?>',
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

                        if (data.data && Array.isArray(data.data)) {
                            data.data.forEach(function(value, key) {
                                flag = value.flag;
                                if(flag == 0){
                                    flag_text = 'PENDING';
                                } else if(flag == 1){
                                    flag_text = 'APPROVED';
                                } else if(flag == 2){
                                    flag_text = 'REJECTED';
                                } else {
                                    flag_text = '';
                                }

                                html += '<tr>';
                                html += '<td>' + i + '</td>';
                                html += '<td>' + value.date + '</td>';
                                html += '<td>' + value.driver_name + '</td>';
                                html += '<td id="req_amount_' + value.id + '">' + value.amount + '</td>';
                                html += '<td>' + value.username + '</td>';
                                html += '<td>' + flag_text + '</td>';
                                if (value.voucher_no) {
                                    html += '<td><span class="badge bg-success">Voucher #' + value.voucher_no + '</span></td>';
                                } else {
                                    html += '<td><span class="badge bg-danger">Not Paid</span></td>';
                                }
                                html += '<td>';
                                if (flag == 0) {
                                    html += '<button class="btn btn-sm btn-primary me-1 edit-request" data-request-id="' + value.id + '" data-amount="' + value.amount + '"><i class="bi bi-pencil"></i> Edit</button>';
                                    html += '<button class="btn btn-sm btn-danger delete-request" data-request-id="' + value.id + '"><i class="bi bi-trash"></i> Delete</button>';
                                } else {
                                    html += '<span class="text-muted">No actions</span>';
                                }
                                html += '</td>';
                                html += '</tr>';
                                i++;
                            });
                        }

                        $("#payment_history").html(html);

                        // check tour_details length
                        if (data.data && data.data.length > 0) {
                            $(".history_table").removeClass('d-none');
                            $(".table_title").removeClass('d-none');
                        } else {
                            $(".history_table").addClass('d-none');
                            $(".table_title").addClass('d-none');
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

        // Add Advance Payment
        $("#form_submit_advance_request").on('submit', function(e) {

            e.preventDefault();

            var formData = new FormData(this);
            var tour_id = $('#tour_id').val();
            var agent = $('#agent').val();
            var agent_name = $('#agent option:selected').text();
            var ad_request_amt = $('#advance_amount').val();
            var manu_id = $('#manual_id').val();
            // var t_consignee = $('#t_consignee').text();

            // check required fields
            if (agent == '' || ad_request_amt == '') {
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
            formData.append('payment', ad_request_amt);
            formData.append('manu_id', manu_id);

            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/driver_allow/advance_request_add') ?>',
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

        // Edit Advance Request
        $(document).on('click', '.edit-request', function() {
            var requestId = $(this).data('request-id');
            var currentAmount = $(this).data('amount');
            var tourId = $('#tour_id').val();

            Swal.fire({
                title: 'Update Advance Request Amount',
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
                        url: '<?php echo base_url('dashboard2/transport/driver_allow/update_advance_request') ?>',
                        type: 'POST',
                        data: {
                            request_id: requestId,
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
                                    text: response.message ? response.message : 'Failed to update advance request',
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while updating the request',
                            });
                        }
                    });
                }
            });
        });

        // Delete Advance Request
        $(document).on('click', '.delete-request', function() {
            var requestId = $(this).data('request-id');
            var tourId = $('#tour_id').val();

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this pending advance request?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?php echo base_url('dashboard2/transport/driver_allow/delete_advance_request') ?>',
                        type: 'POST',
                        data: {
                            request_id: requestId,
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
                                    text: response.message ? response.message : 'Failed to delete request',
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while deleting the request',
                            });
                        }
                    });
                }
            });
        });
    </script>

</body>




</html>