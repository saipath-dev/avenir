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
                                    <div class="col-md-12 d-flex">
                                        <div class="me-auto">
                                            <h4 class="p-title">Inquiries</h4>
                                        </div>

                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                        <fieldset>

                                                            <?php if ($rowCountitinerary) { ?>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="inquiries">
                                                                        Inquiry Templates
                                                                    </label>
                                                                    <select name="inquiries" id="inquiries" class="js-example-basic-single form-control">
                                                                        <option value=""></option>
                                                                        <?php
                                                                        foreach ($inquiries as $inquiry) {
                                                                            echo '<option value="' . $inquiry['id'] . '">' . $inquiry['name'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <?php } ?>
                                                            <input type="hidden" id="inquiry_id" name="inquiry_id" value="0">
                                                            <div class="row pb-2">
                                                                <div class="col-md-2">
                                                                    <input type="hidden" id="is_hotel" name="is_hotel" value="0">
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox" id="hotel_only" name="hotel_only" value="1">
                                                                        <label class="form-check-label" for="hotel_only">Hotel Only</label>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox" id="transport_only" name="transport_only" value="1">
                                                                        <label class="form-check-label" for="transport_only">Transport Only</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="consignee">Inquiry Name<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="consignee" name="consignee" required />
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="manual_id">Manual Inquiry ID<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="manual_id" name="manual_id" readonly disabled/>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-4">
                                                                    <label class="form-label" for="agent">Agent Name<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <!-- <input class="form-control" type="text" id="agent" name="agent" required /> -->
                                                                    <select name="agent" id="agent" class="js-example-basic-single form-control" style="width: 100%;" required onchange="agent_detail(this.value)">
                                                                        <option value="">Select...</option>
                                                                        <?php
                                                                        foreach ($agents as $agent) {
                                                                            // $selected = $countryx['iso'] == $country ? 'selected' : '';
                                                                            echo '<option value="' . $agent['Sup_Acc_No'] . '" data-prefix="' . $agent['prefix'] . '"> ' . $agent['Name'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3 col-md-4">
                                                                    <label class="form-label" for="email">Email<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="email" name="email" readonly />
                                                                </div>
                                                                <div class="mb-3 col-md-4">
                                                                    <label class="form-label" for="email">Agent Reference<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="a_ref" name="a_ref" require />
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="country">Country<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="country" id="country" class="js-example-basic-single form-control" style="width: 100%;" required readonly>
                                                                        <option value="">Select...</option>
                                                                        <?php
                                                                        foreach ($countries as $countryx) {
                                                                            // $selected = $countryx['iso'] == $country ? 'selected' : '';
                                                                            echo '<option data-phonecode="' . $countryx['phonecode'] . '" value="' . $countryx['iso'] . '">' . $countryx['nicename'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="tel_mobile">Contact No<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <div class="input-group w-100">
                                                                        <select name="t_code_1" id="t_code_1" class="form-control" required readonly>
                                                                            <option value="">Code</option>
                                                                            <?php
                                                                            foreach ($countries as $t_code) {
                                                                                $selected = $t_code['phonecode'] == 94 ? 'selected' : '';
                                                                                if (!empty($t_code_1)) {
                                                                                    $selected = $t_code['phonecode'] == $t_code_1 ? 'selected' : '';
                                                                                }
                                                                                echo '<option value="' . $t_code['phonecode'] . '"' . $selected . '>' .  '+' . $t_code['phonecode'] . ' - ' . $t_code['nicename'] . '</option>';
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                        <input type="text" class="form-control" placeholder="" id="tel_mobile" name="tel_mobile" value="" required readonly />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="arrival_flight">Arrival Flight Details</label>
                                                                    <input class="form-control" type="text" id="arrival_flight" name="arrival_flight" placeholder="" />
                                                                </div>

                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="arrival_flight_time">Arrival Flight Time</label>
                                                                    <input class="form-control" type="time" id="arrival_flight_time" name="arrival_flight_time" placeholder="" />
                                                                </div>

                                                            </div>

                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="departure_flight">Departure Flight Details</label>
                                                                    <input class="form-control" type="text" id="departure_flight" name="departure_flight" placeholder="" />
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="departure_flight_time">Departure Flight Time</label>
                                                                    <input class="form-control" type="time" id="departure_flight_time" name="departure_flight_time" placeholder="" />
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="passenger_count">No of Pax<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="passenger_count" name="passenger_count" placeholder="" required oninput="load_vehicles(this.value)" />
                                                                </div>
                                                                <div class="mb-3 col-md-3" style="display: none;">
                                                                    <label class="form-label" for="t_agent">Transport Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="t_agent" id="t_agent" class="js-example-basic-single form-control" >
                                                                        <option value=""></option>
                                                                        <?php foreach ($transport_agents as $t_agent) { ?>
                                                                            <option value="<?= $t_agent['Sup_Acc_No'] ?>"><?= $t_agent['Name'] ?></option>
                                                                        <?php } ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3 col-md-3" style="display: none;">
                                                                    <label class="form-label" for="t_agent">Guide</label>
                                                                    <select name="guide" id="guide" class="js-example-basic-single form-control">
                                                                        <option value=""></option>
                                                                        <?php foreach ($transport_agents as $t_agent) { ?>
                                                                            <option value="<?= $t_agent['Sup_Acc_No'] ?>"><?= $t_agent['Name'] ?></option>
                                                                        <?php } ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="vehicle">Vehicle<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="vehicle" id="vehicle" class="js-example-basic-single form-control" >
                                                                        <!-- <option value=""></option> -->

                                                                    </select>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="start_date">Start
                                                                        Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="start_date" name="start_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?= $start_date ?>" required />
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="end_date">End Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="end_date" name="end_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?= $end_date ?>" required />
                                                                </div>
                                                            </div>

                                                            <div class="mb-3" style="padding-top: 27px; float:right;">
                                                                <button type="submit" class="btn btn-success w-100px me-5px">CREATE
                                                                </button>
                                                            </div>
                                                        </fieldset>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--   -->

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
    <!-- Loading Modal -->


    </div>

    <div class="modal" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="width: 300px; height: 80px; margin-left: 100px; margin-bottom: 100px;">
                <div class="modal-body">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <img src="<?php echo base_url() ?>assets/images/loading.gif" width="30px">
                        <label class="form-label">Just a moment...</label>
                    </div>
                </div>
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
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        // Hotel Only change function
        $('#hotel_only').on('change', function() {
            // is checked or not in the hotel only chechbox
            if ($(this).is(':checked')) {
                // $('#hotel_only').val(1);
                $('#is_hotel').val(1);
                $('#vehicle').removeAttr('required');
            } else {
                // $('#hotel_only').val(0);
                $('#is_hotel').val(0);
            }
        });

        function agent_detail(agent) {

            $.ajax({
                url: '<?php echo base_url(); ?>inquiry/get_consignee_details/' + agent,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {},
                success: function(response) {
                    if (response.status == 200) {
                        // $('#consignee').val(response.data.label);
                        $('#email').val(response.data.email);
                        $("#country").val(response.data.country).trigger('change');
                        $("#t_code_1").val(response.data.t_code_1);
                        $("#tel_mobile").val(response.data.Mobile);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {

                }
            });
        }

        $("#country").on('change', function(e) {
            var phonecode = $(this).find(':selected').data('phonecode');
            $("#t_code_1").val(phonecode).change();
        });

        function load_vehicles(count) {

            $.ajax({
                url: '<?php echo base_url('inquiry/load_vehicles') ?>',
                type: 'GET',
                data: {
                    count: count,
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {

                        $('#vehicle').empty();

                        // Append empty option
                        // $('#vehicle').append('<option value="">Select</option>');


                        // Append new options
                        $.each(response.data, function(index, value) {

                            if (value.passenger_count == count) {
                                var selected = 'selected';
                            } else {
                                var selected = '';
                            }
                            $('#vehicle').append(
                                '<option value="' + value.id + '" data-rate="' + value.km_rate + '" ' + selected + '>' + value.type + '</option>'
                            );
                        });


                    } else {
                        console.error('Error:', data.message ? data.message : 'An error occured while processing the request');
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

        $('#form_submit').submit(function(e) {
            e.preventDefault();

            var inquiry_id = $('#inquiry_id').val();

            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var consignee = $("#consignee").val();
            var email = $("#email").val();
            var agent_ref = $("#a_ref").val();
            var country = $("#country").val();
            var con_code = $("#t_code_1").val();
            var contact = $("#tel_mobile").val();

            // var t_agent = $("#t_agent").val();
            // var t_agent_name = $("#t_agent option:selected").text();
            // var guide = $("#guide").val();
            // var guide_name = $("#guide option:selected").text();
            var vehicle = $("#vehicle").val();
            var vehicleRate = $("#vehicle option:selected").data('rate');
            var vehicle_name = $("#vehicle option:selected").text();
            var pass_count = $("#passenger_count").val();

            var manual_tour_id = $("#manual_id").val();
            var agent = $("#agent").val();
            var agent_name = $("#agent option:selected").text();
            var agent_prefix = $("#agent option:selected").data('prefix');
            var arrival_flight = $("#arrival_flight").val();
            var arrival_flight_time = $("#arrival_flight_time").val();
            var departure_flight = $("#departure_flight").val();
            var departure_flight_time = $("#departure_flight_time").val();
            var hotel_only = $("#is_hotel").val();
            var transport_only = $("#transport_only").is(':checked') ? 1 : 0;

            $.ajax({
                url: '<?php echo base_url(); ?>inquiry/insert_consignee',
                type: 'POST',
                data: {
                    inquiry_id: inquiry_id,
                    start_date: start_date,
                    end_date: end_date,
                    consignee: consignee,
                    email: email,
                    agent_ref: agent_ref,
                    country: country,
                    con_code: con_code,
                    contact: contact,
                    // t_agent: t_agent,
                    // t_agent_name: t_agent_name,
                    // guide: guide,
                    // guide_name: guide_name,
                    vehicle: vehicle,
                    vehicle_name: vehicle_name,
                    vehicle_rate: vehicleRate,
                    pass_count: pass_count,
                    manual_tour_id: manual_tour_id,
                    agent: agent,
                    agent_name: agent_name,
                    agent_prefix: agent_prefix,
                    arrival_flight: arrival_flight,
                    departure_flight: departure_flight,
                    arrival_flight_time: arrival_flight_time,
                    departure_flight_time: departure_flight_time,
                    hotel_only: hotel_only,
                    transport_only: transport_only
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        // a swal with 2 buttons. one for view inquiry and another for new inquiry. the view inquiry button should redirect to the inquiry timeline page
                        Swal.fire({
                            title: 'Success',
                            text: response.message,
                            icon: 'success',
                            showConfirmButton: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                var inquiry_id = response.inquiry_id;
                                window.location.href = '<?php echo base_url('inquiry/timeline/') ?>' + inquiry_id;
                            }
                        });
                    } else if (response.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'An error occured while processing the request',
                        });
                    } else if (response.status == 400) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Warning',
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
        });

        // .pending change
        $('#pendings').on('change', function() {
            var tour_id = $(this).val();
            if (tour_id) {
                window.location.href = '<?php echo base_url('inquiry/timeline/') ?>' + tour_id;
            }
        });

        // .inquiries on change - for templates
        $("#inquiries").on('change', function(e) {
            var id = $(this).val();

            $.ajax({
                url: '<?php echo base_url('inquiry/get_template') ?>/' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log(response.data);
                    if (response.status == 200) {
                        $('#consignee').val(response.data.consignee);
                        $('#agent').val(response.data.agent).trigger('change');
                        $('#tel_mobile').val(response.data.mobile);
                        $('#email').val(response.data.email);
                        $('#start_date').val(response.data.start_date);
                        $('#end_date').val(response.data.end_date);
                    } else {
                        console.error('Error: Could not load template data');
                    }
                },
                error: function() {
                    console.error('Error loading template');
                }
            });
        });

        // get manual id on page load
        $.ajax({
            url: '<?php echo base_url(); ?>inquiry/get_manual_id',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status == 200) {
                    $('#manual_id').val(response.manual_id);
                }
            }
        });
    </script>

</body>

</html>