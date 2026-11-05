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

    <script>
        $(document).ready(function() {
            var tourCondition = <?php echo json_encode($tour_condition); ?>;


            var urlParams = new URLSearchParams(window.location.search);
            var startParam = urlParams.get('start');

            var start = new Date(startParam);

            var year = start.getFullYear();
            var month = start.getMonth() + 1;

            var message = '';

            if (tourCondition[0]['hotel_only'] == 1) {
                message = 'Please note that this tour only includes hotel facilities.';
            }

            if ((tourCondition[0]['hotel_only'] == 1)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: message,
                });

                setTimeout(() => {
                    window.location.href = '<?php echo base_url(); ?>tour/tour_dashboard/monthly_dashboard?year=' + year + '&month=' + month;
                }, 2000);
            }
        });
    </script>

    <div class="container-fluid p-4 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <h2 class="p-title" style="text-align: center;">DRIVER SETTLEMENT REQUEST</h2>
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
                        <div class="tab-pane active" id="custom-hover-reviews">

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table mb-0" style="color: #212529;">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Total Trasport Amount</th>
                                                    <th scope="col">Advance Payment</th>
                                                    <th scope="col">Advance Total Expenses</th>
                                                    <th scope="col">Advance Remaining Balance</th>
                                                    <th scope="col">Total Payment</th>
                                                    <th scope="col">Paybal Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody id="balance_payment">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="card p-4">
                                    <!-- <div class="body"> -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <form action="javascript:void(0);" id="form_submit_balance" accept-charset="utf-8">
                                                <div class="mb-4">
                                                    <label class="form-label" for="tagent">Transport Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                    <select name="transagent" id="transagent" class="js-example-basic-single form-control" style="width: 100%;" required>
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
                                                    <input type="hidden" id="transagent_over_paid_input">
                                                    <div class="alert alert-primary py-2 mt-2">
                                                        Current Over Payment : <strong id="transagent_over_paid"></strong>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label" for="tot_trans_cost">Total Transport Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                        <input class="form-control txt_nav" type="text" id="tot_trans_cost" name="tot_trans_cost" placeholder="" required readonly />
                                                    </div>
                                                    <input type="hidden" id="remaining_balance" name="remaining_balance">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="from_overpaid_amount">Deduct from Over Paid Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                        <input class="form-control txt_nav" type="text" id="from_overpaid_amount" name="from_overpaid_amount" placeholder="" required />
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="cash">Paybal Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                        <input class="form-control txt_nav" type="text" id="cash" name="cash" placeholder="" required readonly />
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label" for="pay_date">Payment Date<font color="#FF0000"><strong>*</strong></font></label>
                                                        <input class="form-control" type="text" id="pay_date" name="pay_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?php echo date('Y-m-d'); ?>" required />
                                                    </div>

                                                    <button type="submit" class="btn btn-success mt-2" id="add_amount">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="col-md-6" style="border: 1px solid #000; padding-top: 15px;">
                                            <form action="javascript:void(0);" id="edit_total_transport" accept-charset="utf-8">
                                                <div class="mb-4">
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                            <label class="form-label pull-right" for="total_mile">Total Mileage</label>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label pull-right" for="total_emile">Estimate Mileage</label>
                                                        </div>
                                                        <div class="col-md-4" style="text-align: end;">
                                                            <span id="total_mile"></span>
                                                        </div>
                                                        <!-- <div class="col-md-4">
                                                            <button type="button" class="btn btn-primary btn-sm actual_mile" data-bs-toggle="modal" data-bs-target="#logSheettModal">
                                                                Log Sheet Mileage
                                                            </button>
                                                        </div> -->
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                            <label class="form-label pull-right"></label>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label pull-right" for="total_acmile">Log Sheet Mileage</label>
                                                        </div>
                                                        <div class="col-md-4" style="text-align: end;">
                                                            <span id="total_actual_mile"></span>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <!-- <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#logSheettModal">
                                                                Log Sheet Mileage
                                                            </button> -->
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <label class="form-label pull-right" for="vehicle_rate">Vehicle Rate</label>
                                                        </div>
                                                        <div class="col-md-4" style="text-align: end;">
                                                            <span id="vehicle_rate"></span>
                                                            <hr>
                                                        </div>
                                                        <div class="col-md-4" style="text-align: end;">
                                                            <!-- <input type="text" name="total_mile_rate" class="form-control  total_mile_rate txt_nav" id="total_mile_rate" placeholder="" value="70592.00"/> -->
                                                            <span id="total_mile_rate"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-4">
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                            <label class="form-label pull-right" for="tour_bata">Tour Bata</label>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label pull-right" for="per_dat_rate">Per Day Rate</label>
                                                        </div>
                                                        <div class="col-md-4" style="text-align: end;">
                                                            <span id="per_dat_rate"></span>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <!-- <button type="button" class="btn btn-primary btn-sm change_bata" data-bs-toggle="modal" data-bs-target="#changeBataModal">
                                                                Change Bata
                                                            </button> -->
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <div class="row">
                                                        <div class="col-md-2">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label pull-right" for="no_od_date">No of Dates</label>
                                                        </div>
                                                        <div class="col-md-4" style="text-align: end;">
                                                            <span id="no_od_date"></span>
                                                            <hr>
                                                        </div>
                                                        <div class="col-md-4" style="text-align: end;">
                                                            <!-- <input type="text" name="total_bata" class="form-control  total_bata txt_nav" id="total_bata" placeholder="" value="22500.00"/> -->
                                                            <span id="total_bata"></span>
                                                            <hr>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-4">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                        </div>
                                                        <div class="col-md-4">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <input type="text" name="total_transport_cost2" class="form-control  total_transport_cost2 txt_nav text-end" id="total_transport_cost2" placeholder="" required readonly />
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- <button type="submit" class="btn btn-info mt-2 float-end" id="edit_amount">Edit</button> -->
                                            </form>
                                        </div>
                                    </div>
                                    <!-- </div> -->
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="mt-2">Voucher Payment History</h6>
                                    <div class="table-responsive">
                                        <table class="table mb-0" style="color: #212529;">
                                            <thead>
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Pay</th>
                                                    <th scope="col">Discription</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody id="setted_pay_voucher_history">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });


        $(document).ready(function() {
            var tour_id = $("#tour_id").val();
            // populateAddedInvoice(tour_id);
            balance_payment(tour_id)
            // getAgentAbsoluteBalance(agent);
        });



        function get_logsheet_mileage(tour_id, agent) {

            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/driver_logsheet/get_logsheet_mileage/') ?>' + tour_id + '/' + agent,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data) {
                        $('#total_mile').text(data.estimate_mileage);
                        $('#vehicle_rate').text(data.vehicle_rate);
                        $('#total_actual_mile').text(parseFloat(data.log_sheet_mileage).toFixed(2));
                        $('#total_mile_rate').text(data.log_sheet_mileage * data.vehicle_rate);
                        $('#per_dat_rate').text(data.per_day_rate);
                        $('#no_od_date').text(data.no_of_dates);
                        $('#total_bata').text(data.per_day_rate * data.no_of_dates);

                        //  var noOfDates=parseInt(text(data.no_of_dates));
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
        }

        function get_nn(tour_id, agent, noOfDates) {
            noOfDates = parseInt(noOfDates);

            // alert("sdgfsdgdg");
            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/driver_logsheet/get_tot_day') ?>',
                type: 'POST',
                data: {
                    tour_id: tour_id,
                    agent: agent
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 200) {
                        var tot = parseInt(res.total_days);
                        var maxDays = noOfDates - tot;
                        $('#no_max_day').text(maxDays);

                    }
                }
            })
        }

        // change transagent
        $("#transagent").on('change', function(e) {
            var tour_id = $("#tour_id").val();
            var agent = $("#transagent").val();
            // populateAddedInvoice(tour_id);
            balance_payment(tour_id)
            get_logsheet_mileage(tour_id, agent);
            getAgentAbsoluteBalance(agent);
        });

        // Load Balance Payment
        function balance_payment(tour_id) {
            var agent = $('#transagent').val();
            var agent_name = $('#transagent option:selected').text();

            // alert(agent_name);
            $.ajax({
                url: "<?php echo base_url() ?>dashboard2/transport/driver_settlement/get_balance_payment_details",
                type: 'POST',
                data: {
                    tour_id: tour_id,
                    agent: agent,
                    agent_name: agent_name
                },
                dataType: 'json',
                success: function(response) {
                    // Handle the response data here
                    var dr = response.data.advance_dr ? response.data.advance_dr : 0; // 50000
                    var cr = response.data.expenses_cr ? response.data.expenses_cr : 0; // -55
                    var ad_remain_cr = response.data.ad_remain_cr ? response.data.ad_remain_cr : 0;
                    var total_transport_cost = response.data.total_transport_cost ? response.data.total_transport_cost : 0;
                    var total_pay_cr = response.data.total_pay_cr ? response.data.total_pay_cr : 0;
                    var totalAmount = parseFloat(dr) - (parseFloat(cr));
                    var total = parseFloat(totalAmount).toFixed(2);

                    $('#tot_trans_cost').val(total_transport_cost);
                    // $('#total_mile').text(response.data.estimate_mileage);
                    $('#total_mile').text(response.data.total_mileage);
                    $('#total_actual_mile').text(response.data.log_sheet_mileage);
                    $('#totalactualmileage').val(response.data.log_sheet_mileage);
                    $('#actualbata').val(response.data.bata_day_rate);
                    $('#nOfDate').val(response.data.bata_days);
                    $('#vehicleRate').val(response.data.vehicle_rate);

                    $('#vehicle_rate').text(response.data.vehicle_rate);

                    if (response.data.log_sheet_mileage !== null && response.data.log_sheet_mileage !== '') {
                        $('#total_mile_rate').text(Number(response.data.log_sheet_mileage) * Number(response.data.vehicle_rate));
                    } else {
                        $('#total_mile_rate').text(Number(response.data.total_mileage) * Number(response.data.vehicle_rate));
                    }

                    $('#per_dat_rate').text(response.data.bata_day_rate);
                    $('#no_od_date').text(response.data.bata_days);
                    $('#total_bata').text(response.data.bata_day_rate * response.data.bata_days);

                    var noOfDates = response.data.bata_days;
                    get_nn(tour_id, agent, noOfDates);

                    $('#total_transport_cost2').val(total_transport_cost);

                    var pay = (parseFloat(total_transport_cost) - parseFloat(dr)) + parseFloat(cr) - parseFloat(total_pay_cr);
                    var pay_bal_amt = parseFloat(pay).toFixed(2);
                    $('#cash').val(pay_bal_amt);

                    $('#remaining_balance').val(total);

                    $('#balance_payment').html('');

                    $('#balance_payment').append(
                        '<tr>' +
                        '<td> ' + total_transport_cost + '</td>' +
                        '<td>- (' + dr + '</td>' +
                        '<td> - ' + (parseFloat(cr)) + '</td>' +
                        '<td> = ' + total + ')</td>' +
                        '<td> - ' + parseFloat(total_pay_cr) + '</td>' +
                        '<td> = ' + pay_bal_amt + '</td>' +
                        '</tr>'
                    );

                    // setted_pay_voucher_history table
                    var html = '';
                    var i = 1;
                    $("#setted_pay_voucher_history").empty();

                    $.each(response.voucherpayment, function(key, value) {
                        var status = value.Aflag;
                        if (status == 0) {
                            status = '<span class="badge bg-warning text-dark">Pending</span>';
                        } else if (status == 1) {
                            status = '<span class="badge bg-success text-white">Paid</span>';
                        } else if (status == 2) {
                            status = '<span class="badge bg-danger text-white">Rejected</span>';
                        } else {
                            status = '<span class="badge bg-secondary text-white">Cancelled</span>';
                        }
                        html += '<tr>';
                        html += '<td>' + i + '</td>';
                        html += '<td>' + value.W_Date + '</td>';
                        html += '<td>' + value.Pay + '</td>';
                        html += '<td>' + value.Vnum + '</td>';
                        html += '<td>' + status + '</td>';
                        html += '<td>' + value.Amount + '</td>';
                        html += '</tr>';
                        i++;
                    });

                    $("#setted_pay_voucher_history").html(html);

                },
                error: function(xhr, status, error) {
                    // Handle any errors here
                }
            });
        }

        function getAgentAbsoluteBalance(transagent) {
            $.ajax({
                url: "<?php echo base_url() ?>dashboard2/transport/driver_logsheet/get_t_agent_absolute_balance",
                type: 'GET',
                data: {
                    tagent: transagent,
                },
                dataType: 'json',
                success: function(response) {
                    if (response) {
                        var amnt = response.data ? response.data : 0.00;
                        amnt = parseFloat(amnt).toFixed(2);
                        $('#transagent_over_paid_input').val(amnt);
                        $('#transagent_over_paid').text(parseFloat(amnt).toFixed(2));
                        $('#from_overpaid_amount').val(0);
                    }
                }
            });
        }

        // .from_overpaid_amount change - must be less than over paid amount
        $('#from_overpaid_amount').keyup(function() {
            var over_paid = parseFloat($('#transagent_over_paid_input').val());
            var from_overpaid_amount = parseFloat($(this).val());
            if (from_overpaid_amount > over_paid || isNaN(from_overpaid_amount)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Over paid amount must be less than or equal to ' + over_paid,
                });
                $(this).val(0);
            }
        });

        $("#form_submit_balance").on('submit', function(e) {
            // if (($('#finalize_txt').val()) == 0) {

            var formData = new FormData(this);
            var tour_id = $('#tour_id').val();
            // get transagent text value in selected option
            var transagent = $('#transagent').val();
            var transagent_name = $('#transagent option:selected').text();
            var pay_metghod = $('#bank_account option:selected').text();
            var t_consignee = $('#t_consignee').text();
            // append tour_id to form data
            formData.append('tour_id', tour_id);
            formData.append('transagent', transagent);
            formData.append('pay_metghod', pay_metghod);
            formData.append('transagent_name', transagent_name);
            formData.append('mamual_id', t_consignee);

            $.ajax({
                url: "<?php echo base_url(); ?>dashboard2/transport/driver_settlement/driver_settlement_request_send",
                type: "POST",
                dataType: "json",
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
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
                            text: data.message ? data.message : 'An error occured while processing the request',
                        });
                    }
                }
            });
            // } else {
            //     Swal.fire({
            //         icon: 'error',
            //         title: 'Error',
            //         text: 'Please finalize the quotation before confirming the hotel reservation.',
            //     });
            // }
        });

        // Edit Total Transport Cost
        $("#edit_total_transport").on('submit', function(e) {
            e.preventDefault();

            if ($('#tot_trans_cost').val() < $('#total_transport_cost2').val()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please enter total transport cost',
                });
                return false;
            }

            var formData = new FormData(this);
            var tour_id = $('#tour_id').val();
            formData.append('tour_id', tour_id);

            formData.append('total_mile', $('#total_mile').text());
            formData.append('vehicle_rate', $('#vehicle_rate').text());
            formData.append('total_mile_rate', $('#total_mile_rate').text());
            formData.append('per_dat_rate', $('#per_dat_rate').text());
            formData.append('no_od_date', $('#no_od_date').text());
            formData.append('total_bata', $('#total_bata').text());

            $.ajax({
                url: "<?php echo base_url(); ?>tour/booking_confirm/edit_total_transport",
                type: "POST",
                dataType: "json",
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
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
                            text: data.message ? data.message : 'An error occured while processing the request',
                        });
                    }
                }
            });
        });

        // Add Logsheet Mileage
        // $('#saveDetails').on('click', function() {
        //     var tour_id = $('#tour_id').val();
        //     var totalactualmileage = $('#totalactualmileage').val();
        //     var bata = $('#actualbata').val();
        //     var agent = $('#transagent').val();
        //     var nOfDate = $('#nOfDate').val();
        //     var v_rate = $('#vehicleRate').val();

        //     // alert(bata);

        //     if (bata != '') {
        //         ac_bata = bata;
        //     } else {
        //         ac_bata = $('#per_dat_rate').text();
        //     }

        //     if (totalactualmileage == '') {
        //         Swal.fire({
        //             icon: 'error',
        //             title: 'Error',
        //             text: 'Please enter actual mileage',
        //         });
        //         return false;
        //     }

        //     $('#total_acmile').text(totalactualmileage);
        //     $('#logSheettModal').modal('hide');

        //     $.ajax({
        //         url: "<?php echo base_url(); ?>dashboard2/transport/driver_logsheet/add_logsheet_mileage",
        //         type: "POST",
        //         dataType: "json",
        //         data: {
        //             tour_id: tour_id,
        //             agent: agent,
        //             estimatedmileage: $('#total_mile').text(),
        //             totalactualmileage: totalactualmileage,
        //             v_rate: $('#vehicle_rate').text(),
        //             dara_rate: ac_bata,
        //             noofdate: nOfDate,
        //             v_rate_new: v_rate,
        //         },
        //         success: function(data) {
        //             if (data.status == 200) {
        //                 Swal.fire({
        //                     icon: 'success',
        //                     title: 'Success',
        //                     text: data.message,
        //                 }).then((result) => {
        //                     if (result.isConfirmed) {
        //                         location.reload();
        //                     }
        //                 });
        //             } else {
        //                 Swal.fire({
        //                     icon: 'error',
        //                     title: 'Error',
        //                     text: data.message ? data.message : 'An error occured while processing the request',
        //                 });
        //             }
        //         }
        //     });
        // });
    </script>

</body>




</html>