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

            .vl {
                border-left: 2px solid gray;
                height: 100%;
            }

            .sub_loc_item {
                /* background-color: #f4f4f5; */
                border: 3px solid #e2e2e8;
                padding: 2px;
                padding-left: 5px;
                border-top: 0;
                border-right: 0;
                border-bottom: 0;
            }

            .sub_loc_item:hover {
                background-color: #e9e9f0;
                border-radius: 5px;
                border: 4px solid lightskyblue;
                border-top: 0;
                border-right: 0;
                border-bottom: 0;
            }

            .sub_loc_item2 {
                background-color: transparent;
            }

            .sub_loc_item2 .btn {
                border: 1px solid #ccc;
            }

            .sub_loc_item2:hover {
                background-color: transparent;
                border: 4px solid lightskyblue;
                border-top: 0;
                border-right: 0;
                border-bottom: 0;
            }

            .txt_lbl {
                cursor: pointer;
            }

            .new_sub_activity {
                color: dodgerblue !important;
            }

            .new_sub_activity:hover {
                color: dodgerblue;
                cursor: pointer;
                text-decoration: underline;
            }

            .save_btn_bg {
                background-color: #e3e3e6;
            }

            .td-anchor {
                cursor: pointer;
                color: grey;
                font-size: 10px;
                text-decoration: underline;
            }

            .td-anchor:hover {
                color: dodgerblue;
                text-decoration: underline;
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

                                <?php if ($valid_tour) { ?>
                                    <div class="row">
                                        <div class="col-md-12 d-flex">
                                            <div class="me-auto">
                                                <h4 class="p-title">Inquiry Timeline</h4>
                                            </div>
                                            <div class="d-flex">
                                                <a href="<?= base_url('inquiry/print?tour_id=' . $tour_id); ?>" class="btn btn-dark finalized_print me-1 d-none" target="_blank" data-toggle="tooltip" title="Print">
                                                    <i class="bi bi-printer-fill me-1"></i>
                                                    Print Inquiry Details
                                                </a>
                                                <a href="<?= base_url('inquiry/print/print_inquiry?tour_id=' . $tour_id); ?>" class="btn btn-warning text-black pre_finalized_print" target="_blank" data-toggle="tooltip" title="Print">
                                                    <i class="bi bi-printer-fill me-1"></i>
                                                    Print Inquiry
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="col-md-12">
                                                <div class="card p-3">
                                                    <div class="body">
                                                        <h6>General Inquiry Details</h6>
                                                        <input type="hidden" id="t_finalize">
                                                        <div class="row">
                                                            <div class="col-md-1">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Inquiry ID</span>
                                                                    <h6 class="fw-bold" id="t_manual_id"></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Inquiry Name</span>
                                                                    <h6 class="fw-bold" id="t_consignee"></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Agent</span>
                                                                    <h6 class="fw-bold" id="t_agent_name"></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Passengers</span>
                                                                    <?php if ((int)$last_segment === 2): ?>
                                                                        <input type="number" class="form-control" id="t_passenger_count_input" style="width: 80px;" min="1">
                                                                        <button type="button" class="btn btn-sm btn-success mt-1" id="update_passenger_count">Update</button>
                                                                    <?php else: ?>
                                                                        <h6 class="fw-bold" id="t_passenger_count"></h6>
                                                                    <?php endif; ?>
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
                                                            <div class="col-md-1">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Created User</span>
                                                                    <h6 class="fw-bold" id="t_user"></h6>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Vehicle</span>
                                                                    <h6 class="fw-bold" id="t_vehicle"></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Hotel Only</span>
                                                                    <div class="pt-0">
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input" type="checkbox" role="switch" id="hotel_only">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-1 me-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Transport Only</span>
                                                                    <div class="pt-0">
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input" type="checkbox" role="switch" id="transport_only">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="d-flex">
                                                                    <input type="text" class="form-control add_remove_days_txt" value="1" style="width: 50px;">
                                                                    <span class="text-muted ms-2 pt-2 me-2">Days</span>
                                                                    <button class="btn btn-sm btn-secondary me-1 px-3 add_days">+</button>
                                                                    <button class="btn btn-sm btn-secondary px-3 remove_days">-</button>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Save as Template</span>
                                                                    <div class="pt-0">
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input" type="checkbox" role="switch" id="is_template">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="finalize_txt" id="finalize_txt">
                                                            <input type="hidden" name="debtor_invoice_row_id" id="debtor_invoice_row_id">
                                                            <input type="hidden" name="debtor_invoice_number" id="debtor_invoice_number">
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
                                                        <table class="table table-striped table-hover">
                                                            <thead>
                                                                <th>Day</th>
                                                                <th>Date</th>
                                                                <th>Hotel</th>
                                                                <th>Room Type</th>
                                                                <th>Meal Code</th>
                                                                <th>Mileage</th>
                                                                <th>Total KM</th>
                                                                <th>Extra KM</th>
                                                                <th>Activities</th>
                                                                <th>Itenaries</th>
                                                                <th>Delete</th>
                                                                <th></th>
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
                                <?php } else { ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-danger" role="alert">
                                                <h4 class="alert-heading">Invalid Inquiry!</h4>
                                                <p>This inquiry is not valid or not found in the system. Please check the inquiry ID and try again.</p>
                                                <hr>
                                                <p class="mb-0">If you think this is a mistake, please contact the system administrator.</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>

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
    <!-- end main content-->

    </div>

    <!-- Add Main Activity Modal -->
    <div class="modal fade" id="addMainActivityModal" tabindex="-1" aria-labelledby="addMainActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addMainActivityModalLabel">Add Activity</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="mainActivityForm">
                        <div class="mb-4">
                            <label class="form-label" for="main_act_location">Activity Location <font color="#FF0000"><strong>*</strong></font></label>
                            <select class="main_act_location form-control select2-hidden-accessible" style="width: 100%;" id="main_act_location" required>
                                <option value="">Select...</option>
                                <?php
                                if (!empty($activity_places)) {
                                    foreach ($activity_places as $place) {
                                        echo '<option value="' . $place['name'] . '" data-fee="' . $place['entranceFee'] . '">' . $place['name'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="main_activity_date">Date <font color="#FF0000"><strong>*</strong></font></label>
                            <input class="form-control txt_nav" type="text" id="main_activity_date" name="main_activity_date" disabled />
                            <input type="hidden" id="activity_location_row" name="activity_location_row" value="">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary add_main_activity">Add Activity</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Sub Location Activity Modal -->
    <div class="modal fade" id="addSubLocationActivityModal" tabindex="-1" aria-labelledby="addSubLocationActivityModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addSubLocationActivityModalLabel">Add Activity to Location</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="subLocationActivityForm">
                        <div class="mb-4">
                            <label class="form-label" for="sub_act_location">Activity Location <font color="#FF0000"><strong>*</strong></font></label>
                            <select class="sub_act_location form-control select2-hidden-accessible" style="width: 100%;" id="sub_act_location" required>
                                <option value="">Select...</option>
                                <?php
                                if (!empty($activity_places)) {
                                    foreach ($activity_places as $place) {
                                        echo '<option value="' . $place['name'] . '" data-fee="' . $place['entranceFee'] . '">' . $place['name'] . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="sub_location_date">Date <font color="#FF0000"><strong>*</strong></font></label>
                            <input class="form-control txt_nav" type="text" id="sub_location_date" name="sub_location_date" disabled />
                            <input type="hidden" id="sub_act_loc_ref" name="sub_act_loc_ref" value="">
                            <input type="hidden" id="sub_activity_location_row" name="sub_activity_location_row" value="">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary add_sub_location_activity">Add Activity</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add From & To Modal -->
    <div class="modal fade" id="addFromToModal" tabindex="-1" aria-labelledby="addFromToModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addFromToModalLabel">Add Locations</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="fromToForm">
                        <div class="row">
                            <div class="mb-4 col-md-6">
                                <label class="form-label" for="from_location">From <font color="#FF0000"><strong>*</strong></font></label>
                                <input class="form-control txt_nav" type="text" id="from_location" name="from_location" placeholder="" required autocomplete="off" />
                            </div>
                            <div class="mb-4 col-md-6">
                                <label class="form-label" for="to_location">To <font color="#FF0000"><strong>*</strong></font></label>
                                <input class="form-control txt_nav" type="text" id="to_location" name="to_location" placeholder="" required autocomplete="off" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="mb-4 col-md-4">
                                <label class="form-label" for="g_distance">Google Distance</label>
                                <input class="form-control txt_nav" type="text" id="g_distance" name="g_distance" readonly />
                            </div>
                            <div class="mb-4 col-md-4">
                                <label class="form-label" for="distance">Actual Distance <font color="#FF0000"><strong>*</strong></font></label>
                                <input class="form-control txt_nav" type="number" id="distance" name="distance" required />
                            </div>
                            <div class="mb-4 col-md-4">
                                <label class="form-label" for="extrakm">Extra KM</label>
                                <input class="form-control txt_nav" type="number" id="extrakm" name="extrakm" />
                            </div>
                        </div>
                        <input type="hidden" id="from_to_date" name="from_to_date" value="">
                        <input type="hidden" id="from_to_row" name="from_to_row" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary add_from_to">Add Location</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Modal -->
    <div class="modal fade" id="saveModal" tabindex="-1" aria-labelledby="saveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="saveModalLabel">Save Date</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Hotel Name</label>
                                <input type="text" class="form-control save_hotel_name" value="">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Room Type</label>
                                <input type="text" class="form-control save_room_type" value="">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Meal Code</label>
                                <input type="text" class="form-control save_meal_code" value="">
                            </div>
                        </div>
                    </div>
                    <input type="hidden" class="save_hyper_date" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary save_date_btn">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
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

    <!-- Request Modal -->
    <div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="requestModalLabel">Request Actions</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Request Type</label>
                        <div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="requestType" id="cancelRequest" value="cancel">
                                <label class="form-check-label" for="cancelRequest">Cancel Request</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="requestType" id="amendmentRequest" value="amendment">
                                <label class="form-check-label" for="amendmentRequest">Amendment Request</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="requestReason" class="form-label">Reason</label>
                        <textarea class="form-control" id="requestReason" rows="4" placeholder="Please provide a reason for your request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitRequest">Submit Request</button>
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
        $('.main_act_location').select2({
            placeholder: " Select",
            allowClear: false,
            dropdownParent: $('#addMainActivityModal')
        });

        $('.js-example-basic-single1').select2({
            placeholder: " Select",
            allowClear: false,
            dropdownParent: $('#generalDataUpdateModal')
        });
        $('.sub_act_location').select2({
            placeholder: " Select",
            allowClear: false,
            dropdownParent: $('#addSubLocationActivityModal')
        });
    </script>



    <script>
        <?php if ($valid_tour) { ?>
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

                }

                initMap();

                // Initialize Google Places Autocomplete
                const fromInput = document.getElementById('from_location');
                const toInput = document.getElementById('to_location');

                const fromAutocomplete = new google.maps.places.Autocomplete(fromInput, {
                    types: ['geocode'],
                    componentRestrictions: {
                        country: 'lk'
                    }
                });

                const toAutocomplete = new google.maps.places.Autocomplete(toInput, {
                    types: ['geocode'],
                    componentRestrictions: {
                        country: 'lk'
                    }
                });

                // Calculate distance when both locations are selected
                function calculateDistance() {
                    const fromPlace = fromAutocomplete.getPlace();
                    const toPlace = toAutocomplete.getPlace();

                    if (fromPlace && toPlace && fromPlace.geometry && toPlace.geometry) {
                        const service = new google.maps.DistanceMatrixService();
                        service.getDistanceMatrix({
                            origins: [fromPlace.geometry.location],
                            destinations: [toPlace.geometry.location],
                            travelMode: google.maps.TravelMode.DRIVING,
                            unitSystem: google.maps.UnitSystem.METRIC,
                            avoidHighways: false,
                            avoidTolls: false
                        }, (response, status) => {
                            if (status === 'OK' && response.rows[0].elements[0].status === 'OK') {
                                const distance = response.rows[0].elements[0].distance;
                                const distanceInKm = (distance.value / 1000).toFixed(1);
                                document.getElementById('g_distance').value = distanceInKm;
                                document.getElementById('distance').value = distanceInKm;
                            }
                        });
                    }
                }

                fromAutocomplete.addListener('place_changed', calculateDistance);
                toAutocomplete.addListener('place_changed', calculateDistance);

                // Clear activities div
                function clearActivitiesDiv(day) {
                    $('.main_activities_div[data-date="' + day + '"]').empty();
                }

                function set_copy_button() {
                    var copy_buttons = `
                <div class="d-flex">
                    <button class="btn btn-secondary btn-sm me-2 copy_prev_day" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy from previous day">
                        <i class="bi bi-arrow-up"></i>
                    </button>
                    <button class="btn btn-secondary btn-sm copy_next_day" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy to next day">
                        <i class="bi bi-arrow-down"></i>
                    </button>
                </div>
            `;

                    $('.copy_buttons').each(function() {
                        $(this).html(copy_buttons);
                    });
                }

                function setup_hotel_and_meal(data) {
                    clearActivitiesDiv(data.tour_date);

                    var saved_data = data;
                    var saved = saved_data.data;

                    var date_str = saved_data.tour_date;
                    var date = new Date(date_str);
                    var year = date.getFullYear();
                    var month = String(date.getMonth() + 1).padStart(2, '0');
                    var day = String(date.getDate()).padStart(2, '0');
                    var formatted_date = year + '-' + month + '-' + day;

                    var underscore_date = date_str.replace(/-/g, '_');

                    clearActivitiesDiv(underscore_date);

                    if (saved !== undefined && saved !== null) {
                        if (saved.length > 0) {
                            var hotel = saved[0].hotel_name || '---';
                            var hotel_code = saved[0].hotel_code || '---';
                            var room_type = saved[0].room_type || '---';
                            var room_type_id = saved[0].room_type_id || '---';
                            var meal_code = saved[0].meal_code || '---';

                            $('.hotel[data-date="' + formatted_date + '"]').val(hotel_code).change();
                            $('.hotel[data-date="' + formatted_date + '"]').data('hotel-name', hotel);
                            $('.room_type[data-date="' + formatted_date + '"]').val(room_type_id).change();
                            $('.room_type[data-date="' + formatted_date + '"]').data('room-type', room_type);
                            $('.meal_code[data-date="' + formatted_date + '"]').val(meal_code).change();
                        }
                    }

                    set_copy_button();

                    $('.hotel').select2({
                        placeholder: " Select",
                        allowClear: true
                    });
                    $('.room_type').select2({
                        placeholder: " Select",
                        allowClear: true
                    });
                    $('.meal_code').select2({
                        placeholder: " Select",
                        allowClear: true
                    });
                }

                function get_tour_details() {
                    var tour_id = <?= $tour_id ?>;
                    // ajax call to get tour details by tour id
                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/get_tour_details/' + tour_id,
                        type: 'GET',
                        dataType: 'json',
                        beforeSend: function() {
                            $('#loadingModal').modal('show');
                        },
                        success: function(response) {
                            $('#loadingModal').modal('hide');
                            if (response.status == 200) {
                                var consignee = response.data;
                                $('#t_manual_id').text(consignee.manual_id);
                                $('#t_consignee').text(consignee.consignee);
                                $('#t_agent_name').text(consignee.agent_name);
                                $('#t_passenger_count').text(consignee.passenger_count);
                                $('#t_passenger_count_input').val(consignee.passenger_count);
                                $('#t_start_date').text(consignee.start_date);
                                $('#t_end_date').text(consignee.end_date);
                                $('#t_finalize').val(consignee.finalize);
                                $('#t_user').text(consignee.create_user);
                                $('#t_vehicle').text(consignee.vehicle_name);

                                $('#consignee').val(consignee.consignee);
                                $('#email').val(consignee.email);
                                $('#tel_mobile').val(consignee.mobile);
                                $('#t_code_1').val(consignee.k_code);
                                $('#country').val(consignee.country);
                                $('#passenger_count').val(consignee.passenger_count);
                                $('#vehicle').val(consignee.vehicle);
                                $('#t_agent').val(consignee.transport_agent);
                                $('#guide').val(consignee.guide);
                                $('#manual_id').val(consignee.manual_id);
                                $('#agent').val(consignee.agent);
                                $('#agent_prefix').val(consignee.agent_prefix);
                                $('#arrival_flight').val(consignee.arrival_flight);
                                $('#departure_flight').val(consignee.departure_flight);
                                $('#arrival_flight_time').val(consignee.arrival_flight_time);
                                $('#departure_flight_time').val(consignee.departure_flight_time);

                                var hotel_only = consignee.hotel_only;
                                if (hotel_only == 1) {
                                    $('#hotel_only').prop('checked', true);
                                } else {
                                    $('#hotel_only').prop('checked', false);
                                }
                                var transport_only = consignee.transport_only;
                                if (transport_only == 1) {
                                    $('#transport_only').prop('checked', true);
                                } else {
                                    $('#transport_only').prop('checked', false);
                                }
                                var is_template = consignee.is_template;
                                if (is_template == 1) {
                                    $('#is_template').prop('checked', true);
                                } else {
                                    $('#is_template').prop('checked', false);
                                }
                                $('#finalize_txt').val(consignee.finalize_txt);
                                $('#debtor_invoice_row_id').val(consignee.debtor_invoice_row_id);
                                $('#debtor_invoice_number').val(consignee.debtor_invoice_number);

                                if (consignee.finalize == 1 && consignee.finalize_txt == 1) {
                                    $('.finalized_print').removeClass('d-none');
                                    $('.pre_finalized_print').addClass('d-none');
                                    $('.finalize_btn').prop('disabled', true);
                                    $('.finalize_btn').text('Finalized');
                                    $('.edit_date').addClass('d-none');
                                    $('.save_date').addClass('d-none');
                                    $('.delete_date').addClass('d-none');
                                    $('.add_activity').addClass('d-none');
                                    $('.add_location').addClass('d-none');
                                    $('.del_location').addClass('d-none');
                                    $('.delete_activity').addClass('d-none');
                                    $('.add_remove_days_txt').prop('disabled', true);
                                    $('.add_days').prop('disabled', true);
                                    $('.remove_days').prop('disabled', true);
                                    $('#update_passenger_count').prop('disabled', true);
                                }
                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            $('#loadingModal').modal('hide');
                            toastr.error('An error occured while processing the request. ERR-INIT-01');
                        }
                    });
                }

                function get_tour_data(date = null) {
                    var tour_id = <?= $tour_id ?>;

                    var start_date = $('#t_start_date').text();
                    var end_date = $('#t_end_date').text();

                    var start = new Date(start_date);
                    var end = new Date(end_date);
                    var diff = end - start;
                    var diffDays = Math.ceil(diff / (1000 * 60 * 60 * 24));

                    // ajax call to get tour details by tour id
                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/get_tour_data/' + tour_id,
                        type: 'GET',
                        dataType: 'json',
                        beforeSend: function() {},
                        success: function(response) {
                            // console.log(response);
                            if (response.status == 200) {
                                saved_data = response.data;

                                saved_data.forEach(s_data => {

                                    var hyper_date = s_data.tour_date;

                                    var date_str = s_data.tour_date;
                                    var date = new Date(date_str);
                                    var year = date.getFullYear();
                                    var month = String(date.getMonth() + 1).padStart(2, '0');
                                    var day = String(date.getDate()).padStart(2, '0');
                                    var formatted_date = year + '-' + month + '-' + day;

                                    var underscore_date = date_str.replace(/-/g, '_');

                                    // Create day row
                                    var day_no = s_data.quotation_day;
                                    var day_text = "Day " + day_no;

                                    var is_saved_date = s_data.det_flag;
                                    var saved = s_data;

                                    var saved_btn_bg = '';
                                    if (is_saved_date == 0 || is_saved_date == null) {
                                        saved_btn_bg = 'save_btn_bg';
                                    }

                                    var hotel = '---';
                                    var hotel_code = '---';
                                    var room_type = '---';
                                    var room_type_id = '---';
                                    var meal_code = '---';
                                    var distance = s_data.distance || '';
                                    var extra_km = s_data.extra_kilo || '';

                                    if (saved !== undefined && saved !== null) {
                                        hotel = saved.hotel_name || '---';
                                        hotel_code = saved.hotel_code || '---';
                                        room_type = saved.room_type || '---';
                                        room_type_id = saved.room_type_id || '---';
                                        meal_code = saved.meal_code || '---';
                                    }

                                    // get sub locations
                                    var sub_locations = s_data.sub_locations || [];
                                    var tour_activities = s_data.activities || [];
                                    var tour_itineraries = s_data.itineraries || [];

                                    // Sub locations div
                                    var sub_location_div = '';
                                    if (sub_locations.length > 0) {
                                        sub_locations.forEach(sub_loc => {
                                            var sub_activities = sub_loc.sub_activities || [];
                                            var sub_activities_text = '';

                                            if (sub_activities.length > 0) {
                                                sub_activities.forEach(sub_act => {
                                                    sub_activities_text += `
                                                <div class="d-flex justify-content-between sub_loc_activity_item">
                                                    <small style="font-size: 10px;">• ${sub_act.name}</small>
                                                    <button class="btn btn-sm delete_sub_location_activity" data-id="${sub_act.id}" data-ref="${sub_act.loc_ref}" data-date="${underscore_date}">
                                                        <i class="bi bi-x text-danger"></i>
                                                    </button>
                                                </div>
                                            `;
                                                });
                                            }

                                            sub_location_div += `
                                        <div class="d-flex flex-column sub_loc_item">
                                            <div class="d-flex justify-content-between">
                                                <span class="form-label">${sub_loc.from} → ${sub_loc.to}</span>
                                                <button class="btn btn-sm del_location" data-id="${sub_loc.id}" data-ref="${sub_loc.ref_no}" data-date="${underscore_date}">
                                                    <i class="bi bi-x text-danger"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted" style="font-size: 10px;">${sub_loc.km} km ${sub_loc.extra_km > 0 ? '+ ' + sub_loc.extra_km + ' km (extra)' : ''}</small>
                                            ${sub_activities_text}
                                            <a class="new_sub_activity" data-ref="${sub_loc.ref_no}" data-date="${underscore_date}" data-row="${day_no}" style="font-size: 10px;">+ Add Activity</a>
                                        </div>
                                    `;
                                        });
                                    }

                                    // Activities div
                                    var activity_div = '';
                                    if (is_saved_date) {
                                        tour_activities.forEach(act => {
                                            activity_div += `
                                        <div class="d-flex flex-column sub_loc_item sub_loc_item2">
                                            <div class="d-flex justify-content-between">
                                                <span class="form-label">${act.name}</span>
                                                <button class="btn btn-sm delete_activity" data-id="${act.id}" data-date="${underscore_date}">
                                                    <i class="bi bi-x text-danger"></i>
                                                </button>
                                            </div>
                                        </div>
                                    `;
                                        });
                                    }

                                    // Itineraries div
                                    var itinerary_div = '';
                                    if (tour_itineraries.length > 0) {
                                        tour_itineraries.forEach(itin => {
                                            itinerary_div += `
                                        <div class="d-flex flex-column">
                                            <span style="font-size: 12px;">${itin.description || ''}</span>
                                        </div>
                                    `;
                                        });
                                    }

                                    var row_html = `
                                <tr data-date="${underscore_date}" data-hyper-date="${hyper_date}">
                                    <td class="align-middle">${day_text}</td>
                                    <td class="align-middle">${formatted_date}</td>
                                    <td class="align-middle">
                                        <select class="hotel form-control t-fc" data-date="${formatted_date}" data-hotel-name="${hotel}">
                                            <option value="">Select</option>
                                            <?php foreach ($hotels as $h) { ?>
                                                <option value="<?= $h['Hotel_Code'] ?>" ${hotel_code == '<?= $h['Hotel_Code'] ?>' ? 'selected' : ''}><?= $h['Hotel_Name'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                    <td class="align-middle">
                                        <select class="room_type form-control t-fc-sm" data-date="${formatted_date}" data-room-type="${room_type}">
                                            <option value="">Select</option>
                                            <?php foreach ($room_types as $rt) { ?>
                                                <option value="<?= $rt['id'] ?>" ${room_type_id == '<?= $rt['id'] ?>' ? 'selected' : ''}><?= $rt['rtype'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                    <td class="align-middle">
                                        <select class="meal_code form-control" style="width: 90px;" data-date="${formatted_date}">
                                            <option value="">Select</option>
                                            <?php foreach ($meal_codes as $mc) { ?>
                                                <option value="<?= $mc['meal_code'] ?>" ${meal_code == '<?= $mc['meal_code'] ?>' ? 'selected' : ''}><?= $mc['meal_code'] ?></option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                    <td class="align-middle">
                                        <button class="btn btn-sm btn-secondary add_location" data-date="${underscore_date}" data-row="${day_no}">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                        <div class="sub_locations_div" data-date="${underscore_date}">
                                            ${sub_location_div}
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="actual_km_txt" data-date="${underscore_date}">${distance}</span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="extra_km_txt" data-date="${underscore_date}">${extra_km}</span>
                                    </td>
                                    <td class="align-middle">
                                        <button class="btn btn-sm btn-secondary add_activity" data-date="${underscore_date}" data-row="${day_no}">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                        <div class="main_activities_div" data-date="${underscore_date}">
                                            ${activity_div}
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <div class="itinerary_div" data-date="${underscore_date}">
                                            ${itinerary_div}
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <button class="btn btn-sm btn-danger delete_date" data-date="${underscore_date}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                    <td class="align-middle">
                                        <button class="btn btn-sm btn-primary save_date ${saved_btn_bg}" data-hyper-date="${hyper_date}" data-date="${underscore_date}">
                                            Save
                                        </button>
                                    </td>
                                    <td class="align-middle copy_buttons"></td>
                                </tr>
                            `;

                                    $('.dates').append(row_html);
                                });

                                set_copy_button();

                                $('.hotel').select2({
                                    placeholder: " Select",
                                    allowClear: true
                                });
                                $('.room_type').select2({
                                    placeholder: " Select",
                                    allowClear: true
                                });
                                $('.meal_code').select2({
                                    placeholder: " Select",
                                    allowClear: true
                                });

                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            toastr.error('An error occured while processing the request. ERR-INIT-02');
                        }
                    });
                }

                // Initialize tour details and data
                get_tour_details();
                get_tour_data();

                // Save date button click handler
                $(document).on('click', '.save_date', function() {
                    var date = $(this).data('date');
                    var hyper_date = $(this).data('hyper-date');
                    date = date.replace(/_/g, '-');

                    var hotel = $('.hotel[data-date="' + date + '"]').val();
                    var hotel_name = $('.hotel[data-date="' + date + '"] option:selected').text();
                    var room_type_id = $('.room_type[data-date="' + date + '"]').val();
                    var room_type = $('.room_type[data-date="' + date + '"] option:selected').text();
                    var meal_code = $('.meal_code[data-date="' + date + '"]').val();

                    // Open save modal with populated values
                    $('#saveModal').modal('show');
                    $('.save_hotel_name').val(hotel_name);
                    $('.save_room_type').val(room_type);
                    $('.save_meal_code').val(meal_code);
                    $('.save_hyper_date').val(hyper_date);
                });

                // Save date modal save button
                $(document).on('click', '.save_date_btn', function() {
                    var tour_id = <?= $tour_id ?>;
                    var hyper_date = $('.save_hyper_date').val();
                    var date = hyper_date;

                    var hotel = $('.hotel[data-date="' + date + '"]').val();
                    var hotel_name = $('.save_hotel_name').val();
                    var room_type_id = $('.room_type[data-date="' + date + '"]').val();
                    var room_type = $('.save_room_type').val();
                    var meal_code = $('.save_meal_code').val();

                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/save_date',
                        type: 'POST',
                        data: {
                            tour_id: tour_id,
                            date: hyper_date,
                            hotel: hotel,
                            hotel_name: hotel_name,
                            room_type_id: room_type_id,
                            room_type: room_type,
                            meal_code: meal_code
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                toastr.success('Date saved successfully');
                                $('#saveModal').modal('hide');
                                var underscore_date = hyper_date.replace(/-/g, '_');
                                $('.save_date[data-date="' + underscore_date + '"]').removeClass('save_btn_bg');
                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function() {
                            toastr.error('An error occured while processing the request');
                        }
                    });
                });

                // Delete date
                $(document).on('click', '.delete_date', function() {
                    var date = $(this).data('date');
                    date = date.replace(/_/g, '-');
                    var tour_id = <?= $tour_id ?>;

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?php echo base_url(); ?>inquiry/delete',
                                type: 'POST',
                                data: {
                                    tour_id: tour_id,
                                    date: date
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        toastr.success('Date deleted successfully');
                                        location.reload();
                                    } else {
                                        toastr.error(response.message || 'An error occured while processing the request');
                                    }
                                },
                                error: function() {
                                    toastr.error('An error occured while processing the request');
                                }
                            });
                        }
                    });
                });

                // Add location button click
                $(document).on('click', '.add_location', function() {
                    var date = $(this).data('date');
                    var row = $(this).data('row');
                    date = date.replace(/_/g, '-');

                    $('#from_to_date').val(date);
                    $('#from_to_row').val(row);
                    $('#from_location').val('');
                    $('#to_location').val('');
                    $('#g_distance').val('');
                    $('#distance').val('');
                    $('#extrakm').val('');

                    $('#addFromToModal').modal('show');
                });

                // Add from to location
                $(document).on('click', '.add_from_to', function() {
                    var tour_id = <?= $tour_id ?>;
                    var date = $('#from_to_date').val();
                    var row = $('#from_to_row').val();
                    var from = $('#from_location').val();
                    var to = $('#to_location').val();
                    var gdistance = $('#g_distance').val();
                    var distance = $('#distance').val();
                    var extrakm = $('#extrakm').val();

                    if (from == '' || to == '' || distance == '') {
                        toastr.error('Please fill all required fields');
                        return;
                    }

                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/sub_locations/add_sub_location',
                        type: 'POST',
                        data: {
                            tour: tour_id,
                            date: date,
                            row: row,
                            pick_location: from,
                            drop_location: to,
                            gdistance: gdistance,
                            distance: distance,
                            extrakm: extrakm
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                toastr.success('Location added successfully');
                                $('#addFromToModal').modal('hide');

                                // Reload sub locations
                                var underscore_date = date.replace(/-/g, '_');
                                var sub_location_div = '';
                                var sub_locations = response.sub_locations || [];

                                if (sub_locations.length > 0) {
                                    sub_locations.forEach(sub_loc => {
                                        var sub_activities = sub_loc.sub_activities || [];
                                        var sub_activities_text = '';

                                        if (sub_activities.length > 0) {
                                            sub_activities.forEach(sub_act => {
                                                sub_activities_text += `
                                            <div class="d-flex justify-content-between sub_loc_activity_item">
                                                <small style="font-size: 10px;">• ${sub_act.name}</small>
                                                <button class="btn btn-sm delete_sub_location_activity" data-id="${sub_act.id}" data-ref="${sub_act.loc_ref}" data-date="${underscore_date}">
                                                    <i class="bi bi-x text-danger"></i>
                                                </button>
                                            </div>
                                        `;
                                            });
                                        }

                                        sub_location_div += `
                                    <div class="d-flex flex-column sub_loc_item">
                                        <div class="d-flex justify-content-between">
                                            <span class="form-label">${sub_loc.from} → ${sub_loc.to}</span>
                                            <button class="btn btn-sm del_location" data-id="${sub_loc.id}" data-ref="${sub_loc.ref_no}" data-date="${underscore_date}">
                                                <i class="bi bi-x text-danger"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted" style="font-size: 10px;">${sub_loc.km} km ${sub_loc.extra_km > 0 ? '+ ' + sub_loc.extra_km + ' km (extra)' : ''}</small>
                                        ${sub_activities_text}
                                        <a class="new_sub_activity" data-ref="${sub_loc.ref_no}" data-date="${underscore_date}" data-row="${row}" style="font-size: 10px;">+ Add Activity</a>
                                    </div>
                                `;
                                    });
                                }

                                $('.sub_locations_div[data-date="' + underscore_date + '"]').html(sub_location_div);

                                // Update mileage
                                var total_km = 0;
                                var total_extra_km = 0;
                                sub_locations.forEach(sub_loc => {
                                    total_km += parseFloat(sub_loc.km || 0);
                                    total_extra_km += parseFloat(sub_loc.extra_km || 0);
                                });
                                $('.actual_km_txt[data-date="' + underscore_date + '"]').text(total_km.toFixed(1));
                                $('.extra_km_txt[data-date="' + underscore_date + '"]').text(total_extra_km.toFixed(1));

                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function() {
                            toastr.error('An error occured while processing the request');
                        }
                    });
                });

                // Delete location
                $(document).on('click', '.del_location', function() {
                    var id = $(this).data('id');
                    var ref_no = $(this).data('ref');
                    var date = $(this).data('date');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "This will delete the location and all its activities!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?php echo base_url(); ?>inquiry/sub_locations/location_delete',
                                type: 'GET',
                                data: {
                                    id: id,
                                    ref_no: ref_no
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        toastr.success('Location deleted successfully');
                                        location.reload();
                                    } else {
                                        toastr.error(response.message || 'An error occured while processing the request');
                                    }
                                },
                                error: function() {
                                    toastr.error('An error occured while processing the request');
                                }
                            });
                        }
                    });
                });

                // Add activity button click
                $(document).on('click', '.add_activity', function() {
                    var date = $(this).data('date');
                    var row = $(this).data('row');
                    date = date.replace(/_/g, '-');

                    $('#main_activity_date').val(date);
                    $('#activity_location_row').val(row);
                    $('#main_act_location').val('').trigger('change');

                    $('#addMainActivityModal').modal('show');
                });

                // Add main activity
                $(document).on('click', '.add_main_activity', function() {
                    var tour_id = <?= $tour_id ?>;
                    var activity_date = $('#main_activity_date').val();
                    var activity_row = $('#activity_location_row').val();
                    var place_name = $('#main_act_location option:selected').text();
                    var pax_counts = $('#t_passenger_count_input').length ? $('#t_passenger_count_input').val() : $('#t_passenger_count').text();

                    if (place_name == '' || place_name == 'Select...') {
                        toastr.error('Please select an activity');
                        return;
                    }

                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/activity/create_activity',
                        type: 'POST',
                        data: {
                            tour: tour_id,
                            row: activity_row,
                            date: activity_date,
                            place_name: place_name,
                            pax_count: pax_counts
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                toastr.success('Activity added successfully');
                                $('#addMainActivityModal').modal('hide');

                                // Reload activities
                                var underscore_date = activity_date.replace(/-/g, '_');
                                var activity_div = '';
                                var activities = response.data || [];

                                activities.forEach(act => {
                                    activity_div += `
                                <div class="d-flex flex-column sub_loc_item sub_loc_item2">
                                    <div class="d-flex justify-content-between">
                                        <span class="form-label">${act.name}</span>
                                        <button class="btn btn-sm delete_activity" data-id="${act.id}" data-date="${underscore_date}">
                                            <i class="bi bi-x text-danger"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                                });

                                $('.main_activities_div[data-date="' + underscore_date + '"]').html(activity_div);

                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function() {
                            toastr.error('An error occured while processing the request');
                        }
                    });
                });

                // Delete activity
                $(document).on('click', '.delete_activity', function() {
                    var id = $(this).data('id');
                    var date = $(this).data('date');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?php echo base_url(); ?>inquiry/activity/delete_activity',
                                type: 'GET',
                                data: {
                                    id: id
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        toastr.success('Activity deleted successfully');
                                        location.reload();
                                    } else {
                                        toastr.error(response.message || 'An error occured while processing the request');
                                    }
                                },
                                error: function() {
                                    toastr.error('An error occured while processing the request');
                                }
                            });
                        }
                    });
                });

                // Add sub activity
                $(document).on('click', '.new_sub_activity', function() {
                    var ref = $(this).data('ref');
                    var date = $(this).data('date');
                    var row = $(this).data('row');
                    date = date.replace(/_/g, '-');

                    $('#sub_location_date').val(date);
                    $('#sub_act_loc_ref').val(ref);
                    $('#sub_activity_location_row').val(row);
                    $('#sub_act_location').val('').trigger('change');

                    $('#addSubLocationActivityModal').modal('show');
                });

                // Add sub location activity
                $(document).on('click', '.add_sub_location_activity', function() {
                    var tour_id = <?= $tour_id ?>;
                    var activity_date = $('#sub_location_date').val();
                    var activity_row = $('#sub_activity_location_row').val();
                    var place_name = $('#sub_act_location option:selected').text();
                    var loc_ref = $('#sub_act_loc_ref').val();
                    var pax_counts = $('#t_passenger_count_input').length ? $('#t_passenger_count_input').val() : $('#t_passenger_count').text();

                    if (place_name == '' || place_name == 'Select...') {
                        toastr.error('Please select an activity');
                        return;
                    }

                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/activity/create_sub_location_activity',
                        type: 'POST',
                        data: {
                            tour: tour_id,
                            row: activity_row,
                            date: activity_date,
                            place_name: place_name,
                            pax_count: pax_counts,
                            loc_ref: loc_ref
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                toastr.success('Activity added successfully');
                                $('#addSubLocationActivityModal').modal('hide');
                                location.reload();
                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function() {
                            toastr.error('An error occured while processing the request');
                        }
                    });
                });

                // Delete sub location activity
                $(document).on('click', '.delete_sub_location_activity', function() {
                    var id = $(this).data('id');
                    var ref = $(this).data('ref');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?php echo base_url(); ?>inquiry/activity/delete_sub_activity',
                                type: 'GET',
                                data: {
                                    id: id
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        toastr.success('Activity deleted successfully');
                                        location.reload();
                                    } else {
                                        toastr.error(response.message || 'An error occured while processing the request');
                                    }
                                },
                                error: function() {
                                    toastr.error('An error occured while processing the request');
                                }
                            });
                        }
                    });
                });

                // Add/Remove days
                $('.add_days').on('click', function() {
                    var days = $('.add_remove_days_txt').val();
                    var tour_id = <?= $tour_id ?>;

                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/add_days',
                        type: 'POST',
                        data: {
                            tour_id: tour_id,
                            days: days
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                toastr.success('Days added successfully');
                                location.reload();
                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function() {
                            toastr.error('An error occured while processing the request');
                        }
                    });
                });

                $('.remove_days').on('click', function() {
                    var days = $('.add_remove_days_txt').val();
                    var tour_id = <?= $tour_id ?>;

                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/remove_days',
                        type: 'POST',
                        data: {
                            tour_id: tour_id,
                            days: days
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                toastr.success('Days removed successfully');
                                location.reload();
                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function() {
                            toastr.error('An error occured while processing the request');
                        }
                    });
                });

                // Update passenger count
                $('#update_passenger_count').on('click', function() {
                    var tour_id = <?= $tour_id ?>;
                    var pax_count = $('#t_passenger_count_input').val();

                    $.ajax({
                        url: '<?php echo base_url(); ?>inquiry/update_passenger_count',
                        type: 'POST',
                        data: {
                            tour_id: tour_id,
                            pax_count: pax_count
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                toastr.success('Passenger count updated successfully');
                                $('#t_passenger_count').text(pax_count);
                            } else {
                                toastr.error(response.message || 'An error occured while processing the request');
                            }
                        },
                        error: function() {
                            toastr.error('An error occured while processing the request');
                        }
                    });
                });

                // Finalize inquiry
                $(document).on('click', '#finalize_btn', function() {
                    var tour_id = <?= $tour_id ?>;
                    var _is_template = $('#is_template').is(':checked') ? 1 : 0;

                    Swal.fire({
                        title: 'Finalize Inquiry?',
                        text: "This action will finalize the inquiry. You won't be able to make changes after finalization!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, finalize it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?php echo base_url(); ?>inquiry/finalize',
                                type: 'POST',
                                data: {
                                    tour_id: tour_id,
                                    is_template: _is_template
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        toastr.success('Inquiry finalized successfully');
                                        location.reload();
                                    } else {
                                        toastr.error(response.message || 'An error occured while processing the request');
                                    }
                                },
                                error: function() {
                                    toastr.error('An error occured while processing the request');
                                }
                            });
                        }
                    });
                });

            });
        <?php } ?>
    </script>

</body>

</html>