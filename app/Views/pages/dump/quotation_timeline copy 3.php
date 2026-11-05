<?= view('layouts/head') ?>
<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <style>
            .star-wrapper {
                transform: translate(-50%, -50%);
                position: absolute;
                direction: rtl;
            }

            .star-wrapper a {
                font-size: 4em;
                color: #fff;
                text-decoration: none;
                transition: all 0.5s;
                margin: 4px;
            }

            .star-wrapper a:hover {
                color: gold;
                transform: scale(1.3);
            }

            .s1:hover~a {
                color: gold;
            }

            .s2:hover~a {
                color: gold;
            }

            .s3:hover~a {
                color: gold;
            }

            .s4:hover~a {
                color: gold;
            }

            .s5:hover~a {
                color: gold;
            }

            .wraper {
                position: absolute;
                bottom: 30px;
                right: 50px;
            }

            #map {
                height: 400px;
                width: 100%;
            }

            .pac-container {
                z-index: 1051 !important;
                /* Ensure the auto-suggest dropdown is above the modal */
            }

            .vl {
                border-left: 2px solid gray;
                height: 100%;
            }

            .sub_loc_item {
                border: 3px solid #ccc;
                padding-left: 3px;
                border-top: 0;
                border-right: 0;
                border-bottom: 0;
            }
        </style>

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
                                            <h4 class="p-title">Tour Timeline</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="col-md-12">
                                            <div class="card p-3">
                                                <div class="body">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Tour Name</span>
                                                                <h6 class="fw-bold" id="t_consignee"></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Agent</span>
                                                                <h6 class="fw-bold" id="t_agent_name"></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Passengers</span>
                                                                <h6 class="fw-bold" id="t_passenger_count"></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Start Date</span>
                                                                <h6 class="fw-bold" id="t_start_date"></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">End Date</span>
                                                                <h6 class="fw-bold" id="t_end_date"></h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row my-4 mt-2">
                                    <div class="col-lg-12 d-flex flex-column">
                                        <style>
                                            .t-fc {
                                                /* padding: 5px; */
                                                width: 180px;
                                            }

                                            .t-fc-sm {
                                                /* padding: 5px; */
                                                width: 100px;
                                            }
                                        </style>
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="table-responsive" style="height: calc(100vh - 300px);">
                                                    <table class="table table-striped table-hover align-middle">
                                                        <thead>
                                                            <th>Day</th>
                                                            <th>Date</th>
                                                            <th>Hotel</th>
                                                            <th>Room Type</th>
                                                            <th>Meal Code</th>
                                                            <th>Rates</th>
                                                            <th>Journey</th>
                                                            <th>Distance (KM)</th>
                                                            <th></th>
                                                            <th>Sub Locations</th>
                                                            <th></th>
                                                        </thead>
                                                        <tbody class="dates">
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="map" class="rounded d-none"></div>
                                    <!--end col-->
                                </div>
                                <!--   -->

                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end .h-100-->

        </div> <!-- end col -->
    </div>

    <style>
        .pac-container {
            z-index: 99999 !important;
        }

        .modal {
            z-index: 2000;
        }

        .modal-backdrop {
            z-index: 1000;
        }
    </style>

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group me-2">
                        <small class="form-label fw-normal text-muted mb-1">From</small>
                        <input type="text" data-date="" class="form-control from_location t-fc" value="">
                    </div>
                    <div class="form-group">
                        <small class="form-label fw-normal text-muted mb-1">To</small>
                        <input type="text" data-date="" class="form-control to_location t-fc" value="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </div>
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
                        <small class="form-label">Just a moment...</small>
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
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/timeline.init.js"></script>
    <script src="<?= base_url(); ?>assets/js/jquery.doubleScroll.js"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEg8Qki_788QHwsbPoh8N2EW_TiWrb0Rg&libraries=places&"></script>



    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let map, directionsRenderer, saved_data = [];

            function initMap() {
                map = new google.maps.Map(document.getElementById('map'), {
                    center: {
                        lat: 7.8731,
                        lng: 80.7718
                    },
                    zoom: 8
                });

                directionsRenderer = new google.maps.DirectionsRenderer();
                directionsRenderer.setMap(map);

                var options = {
                    componentRestrictions: {
                        country: 'LK'
                    }
                };

                var pickInput = document.getElementsByClassName('from_location');
                var dropInput = document.getElementsByClassName('to_location');

                var pickAutocomplete = null;
                for (i = 0; i < pickInput.length; i++) {
                    pickAutocomplete = new google.maps.places.Autocomplete(pickInput[i], options);
                }

                var dropAutocomplete = null;
                for (i = 0; i < dropInput.length; i++) {
                    dropAutocomplete = new google.maps.places.Autocomplete(dropInput[i], options);
                }
            }

            $(document).on('focus', '.actual_km', function() {
                var date = $(this).data('date');
                // get from and to locations from the same row or with the same date
                var from = $(`.from_location[data-date="${date}"]`).val();
                var to = $(`.to_location[data-date="${date}"]`).val();

                if (from == "" || to == "") {
                    toastr.error('Please fill the from and to locations first!');
                    return;
                }

                var google_km = $(`.google_km[data-date="${date}"]`);
                var actual_km = $(`.actual_km[data-date="${date}"]`);

                get_distance_from_distance_metrix_api(from, to, google_km, actual_km);
            });

            function get_distance_from_distance_metrix_api(from, to, google_km, actual_km) {

                $('#loadingModal').modal('show');
                var distance = 0;
                var service = new google.maps.DistanceMatrixService();
                service.getDistanceMatrix({
                    origins: [from],
                    destinations: [to],
                    travelMode: google.maps.TravelMode.DRIVING,
                    unitSystem: google.maps.UnitSystem.METRIC,
                    avoidHighways: false,
                    avoidTolls: false
                }, function(response, status) {
                    $('#loadingModal').modal('hide');
                    if (status == 'OK' && response.rows[0]?.elements[0]?.status != "ZERO_RESULTS") {
                        distance = response.rows[0].elements[0].distance.value / 1000;
                        distance = distance.toFixed(1);
                        google_km.text(distance)
                        actual_km.val(distance).change();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Sorry... Unable to find the distance between the locations!',
                        });
                    }
                });
            }

            function get_hotel_cities() {
                var cities = <?= json_encode($hotel_location) ?>;
                return cities;
            }

            function get_sub_locations_div(sub_locations) {
                var sub_loc_div = '';
                sub_locations.forEach(sub_loc => {
                    sub_loc_div += `
                    <div class="d-flex flex-column sub_loc_item pb-0">
                        <small class="form-label fw-normal text-dark">${sub_loc.from} ~ ${sub_loc.to}</small>
                        <small class="form-label fw-normal text-muted" style="margin-top: -10px;">${sub_loc.km} KM</small>
                    </div>
                    `;
                });

                return sub_loc_div;
            }

            function populate_table_rows(start_date, end_date) {

                var start = new Date(start_date);
                var end = new Date(end_date);

                var hotel_cities = get_hotel_cities();

                var dates = [];
                var currentDate = start;
                var addDays = function(days) {
                    var date = new Date(this.valueOf());
                    date.setDate(date.getDate() + days);
                    return date;
                };

                while (currentDate <= end) {
                    dates.push(currentDate);
                    currentDate = addDays.call(currentDate, 1);
                }

                // previous date's to location is the next date's from location
                var previous_day_to_location = '';

                var tbody = $('.dates');
                tbody.empty();

                var no = 1;
                dates.forEach(date => {

                    var day = date.getDate();
                    day = day < 10 ? '0' + day : day;
                    var month = date.getMonth() + 1;
                    month = month < 10 ? '0' + month : month;
                    var year = date.getFullYear();
                    var hyper_date = year + '-' + month + '-' + day;

                    var is_saved = false;
                    var saved = {};
                    // saved is an array of objects. each object has a date property. if the date property is equal to hyper_date, then the object is the saved data for that date
                    saved_data.forEach(s_data => {
                        if (s_data.tour_date == hyper_date) {
                            is_saved = true;
                            saved = s_data;
                        }
                    });

                    console.log('Date : ' + hyper_date);
                    console.log('Is saved : ' + is_saved);
                    console.log(saved);
                    console.log('----------------------');

                    var formatted_date = year + '_' + month + '_' + day;
                    var from_location = saved.from_location || '';
                    var to_location = saved.to_location || '';
                    var google_km = saved.gdistance || '0';
                    var actual_km = saved.distance || '0';
                    var extra_km = saved.extra_kilo || '0';
                    var hotel_city = saved.Address || '';
                    var hotel = saved.hotel_acc || '';
                    var room_type = saved.room_type || '';
                    var meal_code = saved.meal_code || '';
                    var sgl = saved.sgl || '';
                    var dbl = saved.dbl || '';
                    var tpl = saved.tpl || '';

                    // selected meal_code
                    var bb_selected = meal_code == 'bb' ? 'selected' : '';
                    var hb_selected = meal_code == 'hb' ? 'selected' : '';
                    var fb_selected = meal_code == 'fb' ? 'selected' : '';
                    var sub_locations = saved.sub_locations || [];

                    var row = `
                    <tr data-date="${formatted_date}">
                        <td>
                            <span class="row_day" data-date="${formatted_date}">
                                ${no}
                            </span>
                        </td>
                        <td>
                            <strong class="row_date" data-date="${formatted_date}">
                                ${year}-${month}-${day}
                            </strong>
                        </td>
                        <td style="min-width: 250px;">
                            <div class="d-flex flex-column">
                                <div class="form-group">
                                    <small class="form-label fw-normal text-muted mb-1">Hotel Name</small>
                                    <select data-date="${formatted_date}" class="form-control t-fc hotel w-100">
                                        <option value="0">Select...</option>
                                    </select>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <div class="form-group">
                                    <small class="form-label fw-normal text-muted mb-1">Room Type</small>
                                    <select data-date="${formatted_date}" class="form-control t-fc room_type" style="width: 100px;">
                                        <option value="0">Select...</option>
                                    </select>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <div class="form-group">
                                    <small class="form-label fw-normal text-muted">Meal Code</small>
                                    <select data-date="${formatted_date}" class="form-control t-fc meal_code" style="width: 100px;">
                                        <option value="0">Select...</option>
                                        <option value="bb" ${bb_selected}>BB</option>
                                        <option value="hb" ${hb_selected}>HB</option>
                                        <option value="fb" ${fb_selected}>FB</option>
                                    </select>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex">
                                <div class="form-group me-2">
                                    <small class="form-label fw-normal text-muted mb-1">SGL</small>
                                    <input data-date="${formatted_date}" type="text" class="form-control sgl t-fc-sm" value="${sgl}" style="width: 70px;">
                                </div>
                                <div class="form-group me-2">
                                    <small class="form-label fw-normal text-muted mb-1">DBL</small>
                                    <input data-date="${formatted_date}" type="text" class="form-control dbl t-fc-sm" value="${dbl}" style="width: 70px;">
                                </div>
                                <div class="form-group">
                                    <small class="form-label fw-normal text-muted mb-1">TPL</small>
                                    <input data-date="${formatted_date}" type="text" class="form-control tpl t-fc-sm" value="${tpl}" style="width: 70px;">
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex">
                                <div class="form-group me-2">
                                    <small class="form-label fw-normal text-muted mb-1">From</small>
                                    <input type="text" data-date="${formatted_date}" class="form-control from_location t-fc" value="${from_location}">
                                </div>
                                <div class="form-group">
                                    <small class="form-label fw-normal text-muted mb-1">To</small>
                                    <input type="text" data-date="${formatted_date}" class="form-control to_location t-fc" value="${to_location}">
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex">
                                <div class="form-group me-3">
                                    <small class="form-label fw-normal text-muted">Google</small>
                                    <div class="pt-2">
                                        <strong data-date="${formatted_date}" class="google_km">${google_km}</strong>
                                    </div>
                                </div>
                                <div class="form-group me-1">
                                    <small class="form-label fw-normal text-muted mb-1">Actual</small>
                                    <input type="text" data-date="${formatted_date}" class="form-control actual_km t-fc-sm" style="width: 70px;" value="${actual_km}">
                                </div>
                                <div class="form-group">
                                    <small class="form-label fw-normal text-muted mb-1">Extra</small>
                                    <input type="text" data-date="${formatted_date}" class="form-control extra_km t-fc-sm" style="width: 70px;" value="${extra_km}">
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <div class="form-group mt-3">
                                    <small class="form-label fw-normal text-muted"></small>
                                    <button class="btn btn-sm btn-secondary fw-bold save_sub_location" data-date="${formatted_date}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Save As a Sub Location">
                                        <i class="bi bi-geo-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td style="min-width: 200px;">
                            <div class="d-flex flex-column">
                                ` + get_sub_locations_div(sub_locations) + `
                            </div>
                        </td>                             
                        <td>
                            <div class="d-flex flex-column">
                                <button data-date="${formatted_date}" class="btn btn-sm btn-success save_btn" data-flag="${is_saved ? '1' : '0'}">
                                    <i class="bi bi-check-square-fill"></i>
                                </button>
                            </div>
                        </td>                             
                    </tr>
                    `;

                    tbody.append(row);
                    no++;

                    if (is_saved) {
                        // trigger the change event of actual_km to populate the hotels
                        $('.actual_km[data-date="' + formatted_date + '"]').val(actual_km).change();

                        // set previous day's to location as the from location of the current day
                        previous_day_to_location = to_location;
                    } else {
                        // set previous day's to location as the from location of the current day
                        $('.from_location[data-date="' + formatted_date + '"]').val(previous_day_to_location);

                        previous_day_to_location = '';
                    }

                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
                    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
                });

                initMap();

                $('.hotel_city').select2({
                    placeholder: " Select",
                    allowClear: false,
                });

                $('.hotel').select2({
                    placeholder: " Select",
                    allowClear: false,
                });


                $('#loadingModal').modal('hide');
            }

            function get_tour_details() {
                var tour_id = <?= $tour_id ?>;

                $('#loadingModal').modal('show');
                $.ajax({
                    url: '<?php echo base_url('tour/quotation/get_tour_details') ?>/' + tour_id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 200) {
                            var consignee = data.data;

                            $('#t_consignee').text(consignee.consignee);
                            $('#t_agent_name').text(consignee.agent_name);
                            $('#t_passenger_count').text(consignee.passenger_count);
                            $('#t_start_date').text(consignee.start_date);
                            $('#t_end_date').text(consignee.end_date);

                            $.ajax({
                                url: '<?php echo base_url('tour/quotation/get_tour_data') ?>/' + tour_id,
                                type: 'GET',
                                dataType: 'json',
                                success: function(data) {
                                    saved_data = data.data;
                                    populate_table_rows(consignee.start_date, consignee.end_date);
                                },
                                error: function() {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'An error occured while processing the request',
                                    });
                                }
                            });
                        } else {
                            toastr.error(data.message || 'An error occured while processing the request');
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

            function get_hotels(date) {

                var hyper_date = date.replace(/_/g, '-');

                var saved_hotel = '';
                saved_data.forEach(data => {
                    if (data.tour_date == hyper_date) {
                        saved_hotel = data.hotel_acc;
                    }
                });

                $.ajax({
                    url: '<?php echo base_url('tour/quotation/get_hotel_details') ?>',
                    type: 'GET',
                    data: {
                        destination_city: '',
                        date: date,
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 200) {

                            $('.hotel[data-date="' + date + '"]').empty();
                            $('.hotel[data-date="' + date + '"]').append('<option value="0">Select...</option>');

                            if (data.data.hotels && Array.isArray(data.data.hotels)) {
                                $.each(data.data.hotels, function(index, value) {
                                    if (value && value.Sup_Acc_No && value.Name) {
                                        $('.hotel[data-date="' + date + '"]').append(
                                            '<option value="' + value.Sup_Acc_No + '">' + value.Name + '</option>'
                                        );
                                    }
                                });

                                $('.hotel[data-date="' + date + '"]').val(saved_hotel).change();
                            }
                        } else {
                            toastr.error(data.message || 'An error occured while processing the request');
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

            // when .actual_km is changed, get the to location of that date and set it as the from location of the next date
            $(document).on('change', '.actual_km', function() {
                var tour_id = <?= $tour_id ?>;
                var date = $(this).data('date');
                var to_location = $(`.to_location[data-date="${date}"]`).val();

                // search_hotel_location(tour_id, date, to_location);
                get_hotels(date);

                var next_date = $(`.row_date[data-date="${date}"]`).closest('tr').next().find('.row_date').data('date');
                $(`.from_location[data-date="${next_date}"]`).val(to_location);
            });

            $(document).on('change', '.hotel', function(e) {

                var date = $(this).data('date');
                var hotelId = $(this).val();
                var hyper_date = date.replace(/_/g, '-');

                if (hotelId == null || hotelId == '' || hotelId == '0' || date == null) {
                    return;
                }

                var saved_room_type = '';
                saved_data.forEach(data => {
                    if (data.tour_date == hyper_date) {
                        saved_room_type = data.room_type;
                    }
                });

                $.ajax({
                    url: '<?php echo base_url('tour/quotation/get_hotel_type') ?>',
                    type: 'GET',
                    data: {
                        hotelId: hotelId,
                        date: date,
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 200) {

                            $('.room_type[data-date="' + date + '"]').empty();
                            $('.room_type[data-date="' + date + '"]').append('<option value="0">Select...</option>');

                            if (data.data.hotels_type && Array.isArray(data.data.hotels_type)) {
                                $.each(data.data.hotels_type, function(index, value) {
                                    if (value && value.id && value.rtype) {
                                        $('.room_type[data-date="' + date + '"]').append(
                                            '<option value="' + value.id + '">' + value.rtype + '</option>'
                                        );
                                    }
                                });

                                $('.room_type[data-date="' + date + '"]').val(saved_room_type).change();
                            }
                        } else {
                            toastr.error(data.message ? data.message : 'An error occured while processing the request');
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
            });

            $(document).on('change', '.meal_code', function(e) {

                var id = $(this).val();
                var date = $(this).data('date');

                var h_code = $(`.hotel[data-date="${date}"]`).val();
                var room_type = $(`.room_type[data-date="${date}"]`).val();

                if (h_code == '' || h_code == '0' || room_type == '0' || h_code == null || h_code == 0) {
                    return;
                }

                $('#loadingModal').modal('show');
                // ajax call
                $.ajax({
                    url: '<?php echo base_url('tour/quotation/get_meal_prices') ?>',
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
                            $('.sgl[data-date="' + date + '"]').val(sgl);
                            $('.dbl[data-date="' + date + '"]').val(dbl);
                            $('.tpl[data-date="' + date + '"]').val(tpl);
                        } else {
                            toastr.error(data.message ? data.message : 'An error occured while processing the request');
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
            });

            function search_hotel_location(tour_id, selectdate, location) {

                var tour = tour_id;
                var date = selectdate;
                var loc = location;

                // location is like Kurunegala Town Central, Kurunegala, Sri Lanka. we have to split it by comma and get the city name. city's index is the second last element of the array
                var location_parts = loc.split(',');

                if (location_parts.length < 2) {
                    toastr.error('Did not find any hotels for the selected location! Please find it manually.');
                    return;
                }

                var city = location_parts[location_parts.length - 2];
                loc = city.trim();

                // $('.hotel_city[data-date="' + date + '"]') has a option with the value of loc, select it
                var all_cities = [];
                $('.hotel_city option').each(function() {
                    all_cities.push($(this).val());
                });
                if (all_cities.includes(loc)) {
                    $('.hotel_city[data-date="' + date + '"]').val(loc).change();
                } else {
                    toastr.error('Did not find any hotels for the selected location! Please find it manually.');
                }
            }

            // save button click event
            $(document).on('click', '.save_btn', function(e) {
                e.preventDefault();
                var date = $(this).data('date');
                var type = $(this).data('flag') == '1' ? 'update' : 'insert';
                save_date(date, type, false);
            });

            function save_date(date, save_type, is_save_all) {
                date = date.trim();
                var no = $('.row_day[data-date="' + date + '"]').text();
                var from = $('.from_location[data-date="' + date + '"]').val();
                var to = $('.to_location[data-date="' + date + '"]').val();
                var google_km = $('.google_km[data-date="' + date + '"]').text();
                var actual_km = $('.actual_km[data-date="' + date + '"]').val();
                var extra_km = $('.extra_km[data-date="' + date + '"]').val();
                // var hotel_city = $('.hotel_city[data-date="' + date + '"]').val();
                var hotel = $('.hotel[data-date="' + date + '"]').val();
                var hotel_name = $('.hotel[data-date="' + date + '"] option:selected').text();
                var room_type = $('.room_type[data-date="' + date + '"]').val();
                var meal_code = $('.meal_code[data-date="' + date + '"]').val();
                var sgl = $('.sgl[data-date="' + date + '"]').val();
                var dbl = $('.dbl[data-date="' + date + '"]').val();
                var tpl = $('.tpl[data-date="' + date + '"]').val();

                date = date.replace(/-/g, '_');

                $.ajax({
                    url: '<?php echo base_url('tour/quotation/save_date') ?>',
                    type: 'POST',
                    data: {
                        trans_tour_id: <?= $tour_id ?>,
                        trans_row_id: no.trim(),
                        t_date: date,
                        pick_location: from,
                        drop_location: to,
                        gdistance: google_km,
                        distance: actual_km,
                        extrakm: extra_km,
                        // hotel_city: hotel_city,
                        hotel: hotel,
                        hotel_name: hotel_name,
                        room_type: room_type,
                        meal_code: meal_code,
                        sgl: sgl,
                        dbl: dbl,
                        tpl: tpl,
                        is_hotel: 1,
                        up_flag: save_type == 'insert' ? 0 : 1
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        // disable the save button
                        $('.save_btn[data-date="' + date + '"]').prop('disabled', true);
                    },
                    success: function(data) {

                        // enable the save button
                        $('.save_btn[data-date="' + date + '"]').prop('disabled', false);

                        if (data.status == 200) {
                            if (!is_save_all) {
                                date = date.replace(/_/g, '-');
                                toastr.success(date + ' saved successfully!');
                                // get_tour_details();
                            }
                        } else {
                            toastr.error(data.message || 'An error occured while processing the request');
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occured while processing the request',
                        });
                    },
                    complete: function() {
                        // enable the save button
                        $('.save_btn[data-date="' + date + '"]').prop('disabled', false);
                    }
                });
            }

            get_tour_details();

            $(document).on('click', '.save_sub_location', function() {

                var date = $(this).data('date');

                var tour = <?= $tour_id ?>;
                var row = $('.row_day[data-date="' + date + '"]').text();
                var pick_location = $('.from_location[data-date="' + date + '"]').val();
                var drop_location = $('.to_location[data-date="' + date + '"]').val();
                var gdistance = $('.google_km[data-date="' + date + '"]').text();
                var distance = $('.actual_km[data-date="' + date + '"]').val();
                var extrakm = $('.extra_km[data-date="' + date + '"]').val();
                date = date.replace(/_/g, '-');

                if (pick_location == '' || drop_location == '' || gdistance == '' || distance == '' || extrakm == '') {
                    toastr.error('Required fields are missing!');
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>tour/sub_locations/create",
                    data: {
                        tour: tour,
                        row: row.trim(),
                        date: date,
                        pick_location: pick_location,
                        drop_location: drop_location,
                        gdistance: gdistance,
                        distance: distance,
                        extrakm: extrakm
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.status == 200) {
                            console.log(response);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: response.message,
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                        });
                    }
                });

            });

        });
    </script>

    <script>
        function moveTableRight() {
            $('.table-responsive').animate({
                scrollLeft: 1000
            }, 500);
        }

        function moveTableLeft() {
            $('.table-responsive').animate({
                scrollLeft: 0
            }, 500);
        }

        // .extra_km on focus, scroll the table to the right with animation
        $(document).on('focus', '.extra_km', function() {
            moveTableRight();
        });

        // .hotel_city on change, scroll the table to the right with animation
        $(document).on('change', '.hotel_city', function() {
            moveTableRight();
        });

        // .hotel on change, scroll the table to the right with animation
        $(document).on('change', '.hotel', function() {
            moveTableRight();
        });

        // .meal_code on change, scroll the table to the right with animation
        $(document).on('change', '.meal_code', function() {
            moveTableRight();
        });

        // .sgl on focus, scroll the table to the right with animation
        $(document).on('focus', '.sgl', function() {
            moveTableRight();
        });

        // .dbl on focus, scroll the table to the right with animation
        $(document).on('focus', '.dbl', function() {
            moveTableRight();
        });

        // .tpl on focus, scroll the table to the right with animation
        $(document).on('focus', '.tpl', function() {
            moveTableRight();
        });

        // .from_location on focus, scroll the table to the left with animation
        $(document).on('focus', '.from_location', function() {
            moveTableLeft();
        });

        // .to_location on focus, scroll the table to the left with animation
        $(document).on('focus', '.to_location', function() {
            moveTableLeft();
        });

        // .google_km on focus, scroll the table to the left with animation
        $(document).on('focus', '.google_km', function() {
            moveTableLeft();
        });

        // .actual_km on focus, scroll the table to the left with animation
        $(document).on('focus', '.actual_km', function() {
            moveTableLeft();
        });
    </script>

</body>

</html>