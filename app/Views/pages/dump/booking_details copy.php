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
                                                        <?php foreach ($tour_main_details as $detail) { ?>
                                                           
                                                        <div class="col-md-3">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Tour Name</span>
                                                                <h6 class="fw-bold" id="t_consignee"><?php echo $detail['consignee']?></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Agent</span>
                                                                <h6 class="fw-bold" id="t_agent_name"><?php echo $detail['agent_name']?></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Passengers</span>
                                                                <h6 class="fw-bold" id="t_passenger_count"><?php echo $detail['passenger_count']?></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Start Date</span>
                                                                <h6 class="fw-bold" id="t_start_date"><?php echo $detail['start_date']?></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">End Date</span>
                                                                <h6 class="fw-bold" id="t_end_date"><?php echo $detail['end_date']?></h6>
                                                            </div>
                                                        </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th style="width: 5%;">#</th>
                                                                <th style="width: 10%;">Date</th>
                                                                <!-- <th style="width: 10%;">Hotel Code</th> -->
                                                                <th style="width: 15%;">Hotel Name</th>
                                                                <th style="width: 10%;">Start Date</th>
                                                                <th style="width: 10%;">End Date</th>
                                                                <th style="width: 10%;">From Location</th>
                                                                <th style="width: 10%;">To Location</th>
                                                                <th style="width: 10%;">Reservation</th>
                                                                <th style="width: 10%;">Hotel Confirm</th>
                                                                <th style="width: 10%;">Hotel Payment</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $i = 1;
                                                            foreach ($tour_details as $row) {

                                                            ?>
                                                                <tr>
                                                                    <td><?= $i++; ?></td>
                                                                    <td><?= $row['tour_date'] ?></td>

                                                                    <td><?= $row['hotel_name'] ?></td>
                                                                    <td><?= $start_date ?></td>
                                                                    <td><?= $end_date ?></td>
                                                                    <td><?= $row['from_location'] ?></td>
                                                                    <td><?= $row['to_location'] ?></td>
                                                                    <td><button type="button" class="btn btn btn-info btn-sm reservation" data-bs-toggle='modal' data-bs-target='#hotelreservationModal' data-tour="<?= $row['quotation_id'] ?>" data-date="<?= $row['tour_date'] ?>" data-existhotelcode="<?= $row['hotel_code'] ?>" data-existhotel="<?= $row['hotel_acc'] ?>" data-tolocation="<?= $row['to_location'] ?>">Email</button></td>
                                                                    <td> <button type="button" class="btn btn btn-info btn-sm hotel_advance" data-bs-toggle='modal' data-bs-target='#HotelConfirmModal' data-tour="<?= $row['quotation_id'] ?>" data-date="<?= $row['tour_date'] ?>" data-existhotelcode="<?= $row['hotel_code'] ?>" data-existhotel="<?= $row['hotel_acc'] ?>" data-tolocation="<?= $row['to_location'] ?>">Hotel</button></td>


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


                            </div> <!-- end .h-100-->

                        </div> <!-- end col -->
                    </div>

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <div class="modal fade" id="HotelConfirmModal" tabindex="-1" aria-labelledby="HotelConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="HotelConfirmModalLabel">Confirm Hotel</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" class="confirm_hotel_form" id="confirm_hotel_form">
                            <div class="row">
                                <div class="mb-4">
                                    <input type="hidden" name="tour_id" id="tour_id">
                                    <input type="hidden" name="old_hotel" id="old_hotel">
                                    <input type="hidden" name="tour_date" id="tour_date">
                                    <div class="mb-3">
                                        <label class="form-label" for="select_location">Destination<font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="select_location" id="select_location" class="js-example-basic-single_m form-control" style="width: 100%;">
                                            <option value="0">Select a Destination...</option>
                                            <?php
                                            foreach ($hotel_location as $id) {
                                                echo '<option value="' . $id['Address'] . '" >' . $id['Address'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <span>Selected Destination : <code id="slct_location"></code></span>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="select_hotel">Hotel<font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="select_hotel" id="select_hotel" class="js-example-basic-single_m form-control" style="width: 100%;" onchange="assign_hotel(this.value)">
                                            <option value="0">Select a Hotel...</option>
                                            <?php

                                            ?>
                                        </select>
                                        <span>Selected Hotel : <code id="slct_hotel"></code></span>
                                        <input type="hidden" id="slct_hotel_id" name="slct_hotel_id">
                                        <input type="hidden" id="slct_hotel_name" name="slct_hotel_name">
                                    </div>
                                    <!-- <div class="mb-3">
                                        <label class="form-label" for="select_hotel_code">Hotel Code<font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="select_hotel_code" id="select_hotel_code" class="js-example-basic-single_m form-control" style="width: 100%;">
                                            <option value="">Select</option>
                                        </select>
                                    </div> -->
                                    <div class="mb-3">
                                        <div>
                                            <label class="form-label" for="room_type">Room Type<font color="#FF0000"><strong>*</strong></font></label>
                                            <!-- <input class="form-control" type="text" id="room_type" name="room_type" placeholder="" required readonly /> -->
                                            <select class="js-example-basic-single_m form-control" style="width: 100%;" id="room_type" name="room_type">
                                                <option value="">Select a Room Type...</option>
                                            </select>
                                            <input type="hidden" id="room_type_id" name="room_type_id" placeholder="" />
                                            <span>Selected Room Type : <code id="slct_room_type"></code></span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div>
                                            <label class="form-label" for="meal_code">Meal Code <font color="#FF0000"><strong>*</strong></font></label>
                                            <select name="meal_code" id="meal_code" class="form-control" style="width: 100%;" required onchange="mealCodeChanged(this.value);">
                                                <option value="0"></option>
                                                <option value="bb">BB</option>
                                                <option value="hb">HB</option>
                                                <option value="fb">FB</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label" for="sgl">SGL</label>
                                            <input class="form-control" type="text" id="sgl" name="sgl" placeholder="" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="dbl">DBL</label>
                                            <input class="form-control" type="text" id="dbl" name="dbl" placeholder="" />
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="tpl">TPL</label>
                                            <input class="form-control" type="text" id="tpl" name="tpl" placeholder="" />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <label class="form-label" for="">No of Rooms</label>
                                        <div class="col-md-4">
                                            <input class="form-control" type="text" id="sglcount" name="sglcount" placeholder="" />
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="text" id="dblcount" name="dblcount" placeholder="" />
                                        </div>
                                        <div class="col-md-4">
                                            <input class="form-control" type="text" id="tplcount" name="tplcount" placeholder="" />
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="extra">Extra Fee</label>
                                        <input class="form-control txt_nav" type="text" id="extra" name="extra" placeholder="" />
                                    </div>
                                    <input type="hidden" id="tot_doller" name="tot_doller">
                                    <div class="mb-3">
                                        <label class="form-label" for="no_of_room">Doller Rate<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="rate" name="rate" placeholder="" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="advance_payment_doller">Advance Payment ($)<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="advance_payment_doller" name="advance_payment_doller" placeholder="" readonly />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="advance_payment">Advance Payment (LKR)<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control txt_nav" type="text" id="advance_payment" name="advance_payment" placeholder="" readonly />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>

                                        <select name="bank_acc" id="bank_acc" class="js-example-basic-single form-control" style="width: 100%;" required>
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
                                        <input class="form-control txt_nav" type="text" id="hotel_ref" name="hotel_ref" placeholder="" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="dates">Same Dates</label>
                                        <input class="form-control txt_nav" type="text" id="dates" name="dates" placeholder="" readonly />
                                        <div id="datesContainer"></div>
                                    </div>

                                    <button type="submit" class="btn btn-success mt-2" id="add_amount" style="float: right;">Confirm</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- end main content-->
        </div>

        <div class="modal fade" id="hotelreservationModal" tabindex="-1" aria-labelledby="hotelreservationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="hotelreservationModallLabel">Send Email</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" class="email_send_form" id="email_send_form">
                            <div class="row">
                                <div class="mb-4">
                                    <input type="hidden" name="tour_id" id="tour_id">
                                    <input type="hidden" name="tour_date" id="tour_date">
                                    
                                    <div class="mb-3">
                                        <label class="form-label" for="email">Email Address</label>
                                        <input class="form-control txt_nav" type="text" id="email" name="email" placeholder="" />
                                    </div>

                                    <button type="submit" class="btn btn-success mt-2" id="btn_email" style="float: right;">Send</button>
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
            $('.js-example-basic-single').select2({
                placeholder: " Select",
                allowClear: true
            });

            $('.js-example-basic-single_m').select2({
                placeholder: " Select",
                allowClear: true,
                dropdownParent: $("#HotelConfirmModal")
            });

            $("#select_location").on('change', function(e) {
                // alert('ok');
                e.preventDefault();

                var search_location = $("#select_location").val();
                var date = $("#tour_date").val();

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

            function assign_hotel(hotelId) {

                // $("#slct_htl").val(hotelId);
                var date = $("#tour_date").val();

                if (hotelId == null || hotelId == '' || date == null) {
                    return;
                }

                $.ajax({
                    url: '<?php echo base_url('tour/tour/get_hotel_type') ?>',
                    type: 'GET',
                    data: {
                        hotelId: hotelId,
                        date: date,
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 200) {

                            // $("#select_t").text("");
                            // $("#select_h_code").text("");
                            // document.getElementById('select_y').parentElement.style.display = 'none';
                            // document.getElementById('select_h_code').parentElement.style.display = 'none';

                            $('#room_type').empty();

                            $('#room_type').append('<option value="">Select a Room Type</option>');

                            $('#meal_code').val('');
                            $('#sgl').val('');
                            $('#dbl').val('');
                            $('#tpl').val('');

                            if (data.data.hotels_type && Array.isArray(data.data.hotels_type)) {
                                $.each(data.data.hotels_type, function(index, value) {
                                    if (value && value.id && value.rtype) {
                                        $('#room_type').append(
                                            '<option value="' + value.id + '">' + value.rtype + '</option>'
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

            }

            $(".hotel_advance").on('click', function(e) {
                e.preventDefault();

                var tour = $(this).data('tour');
                var date = $(this).data('date');
                var existhotel = $(this).data('existhotel');
                var existhotelcode = $(this).data('existhotelcode');
                var to_location = $(this).data('tolocation');
                let parts = to_location.split(','); // Split the string by commas
                let lastWord = parts[parts.length - 1].trim();
                // alert(lastWord);

                $("#HotelConfirmModal").modal('show');

                $("#tour_id").val(tour);
                $("#tour_date").val(date);
                // $("#select_hotel").val(existhotel);
                $("#old_hotel").val(existhotel);
                // $("#select_hotel_code").val(existhotelcode);
                $("#old_hotel_code").val(existhotelcode);

                $.ajax({
                    url: '<?php echo base_url(); ?>tour/booking_confirm/getAllDetails/' + tour + '/' + date,
                    type: 'GET',
                    dataType: 'json',
                    beforeSend: function() {

                    },
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

                            const same_hotel_date = response.data.same_hotel_date;
                            // Extract the tour_date values
                            const tourDates = same_hotel_date.map(item => item.tour_date);
                            // Join the dates into a single string separated by commas
                            const commaSeparatedTourDates = tourDates.join(',');

                            $('#dates').val(commaSeparatedTourDates);

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

            // var totaldbl = totalsgl = totaltpl = 0;
            $("#sgl").keyup(function() {
                total_room_amount();
            });
            $("#sglcount").keyup(function() {
                total_room_amount();
            });

            $("#dbl").keyup(function() {
                total_room_amount();
            });
            $("#dblcount").keyup(function() {
                total_room_amount();
            });

            $("#tpl").keyup(function() {
                total_room_amount();
            });
            $("#tplcount").keyup(function() {
                total_room_amount();
            });

            $("#extra").keyup(function() {
                total_room_amount();
            });

            function total_room_amount() {
                var totaldbl = totalsgl = totaltpl = totextra = 0;

                // Retrieve and convert values from input fields
                var sglCount = parseFloat($('#sglcount').val()) || 0;
                var sgl_rate = parseFloat($('#sgl').val()) || 0;
                totalsgl = sglCount * sgl_rate;

                var DblCount = parseFloat($('#dblcount').val()) || 0;
                var dbl_rate = parseFloat($('#dbl').val()) || 0;
                totaldbl = DblCount * dbl_rate;

                var TplCount = parseFloat($('#tplcount').val()) || 0;
                var tpl_rate = parseFloat($('#tpl').val()) || 0;
                totaltpl = TplCount * tpl_rate;

                var extra = parseFloat($('#extra').val()) || 0;
                totextra = extra;

                var total_room_amount = totalsgl + totaldbl + totaltpl + totextra;
                // alert(total_room_amount);
                $('#tot_doller').val(total_room_amount);

                var rate = parseFloat($('#rate').val()) || 0;
                var total_lkr = total_room_amount * rate
                $('#advance_payment').val(total_lkr);
                $('#advance_payment_doller').val(total_room_amount);
            }

            $("#rate").keyup(function() {
                total_room_amount();
                // var rate = $('#rate').val();
                // var total_room_amount = $('#tot_doller').val();
                // var total_lkr = total_room_amount * rate
                // $('#advance_payment').val(total_lkr);

            });

            // Confirm Hotel
            $("#confirm_hotel_form").on('submit', (function(e) {
                e.preventDefault();

                var formdata = new FormData(this);

                formdata.append('new_hotel_name', $("#select_hotel option:selected").text());
                formdata.append('selected_hotel_name', $("#slct_hotel").text());
                formdata.append('room_type_name_new', $("#room_type option:selected").text());
                formdata.append('slct_room_type', $("#slct_room_type").text());
                formdata.append('bank_name', $("#bank_acc option:selected").text());

                $.ajax({
                    url: '<?php echo base_url(); ?>tour/booking_confirm/hotelAdvanced',
                    type: 'POST',
                    data: formdata,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    beforeSend: function() {},
                    success: function(response) {
                        if (response.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then((result) => {
                                // location.reload();
                            });

                            // $('#quotation_details_all')[0].reset();
                            // resetFormSelections();
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
            }));
        </script>

</body>




</html>