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
                                                <h4 class="p-title">Tour Timeline</h4>
                                            </div>
                                            <div class="d-flex">
                                                <a href="<?= base_url('tour/print?tour_id=' . $tour_id); ?>" class="btn btn-dark finalized_print me-1 d-none" target="_blank" data-toggle="tooltip" title="Print">
                                                    <i class="bi bi-printer-fill me-1"></i>
                                                    Print Reservation Card
                                                </a>
                                                <a href="<?= base_url('tour/print/print_quotation_with_amounts?tour_id=' . $tour_id); ?>" class="btn btn-warning text-black pre_finalized_print" target="_blank" data-toggle="tooltip" title="Print">
                                                    <i class="bi bi-printer-fill me-1"></i>
                                                    Print Quotation
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="col-md-12">
                                                <div class="card p-3">
                                                    <div class="body">
                                                        <h6>General Tour Details</h6>
                                                        <input type="hidden" id="t_finalize">
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
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Vehicle</span>
                                                                    <h6 class="fw-bold" id="t_vehicle"></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Vehicle Rate</span>
                                                                    <h6 class="fw-bold" id="t_vehicle_rate"></h6>
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
                                                            <div class="col-md-1 me-3">
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
                                                            <div class="col-md-1">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Save as Template</span>
                                                                    <div class="pt-0">
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input" type="checkbox" role="switch" id="is_template">
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

                                    <div class="row clearfix mt-2">
                                        <div class="col-md-12">
                                            <div class="col-md-12">
                                                <div class="card p-3">
                                                    <div class="body">
                                                        <h6>Room Count</h6>
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">SGL</span>
                                                                    <input type="text" class="form-control sgl_count" id="sgl_count" value="">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3 d-flex">
                                                                <div class="d-flex flex-column">
                                                                    <div class="d-flex justify-content-between">
                                                                        <div>
                                                                            <span class="text-muted">DBL</span>
                                                                        </div>
                                                                        <div class="d-flex">
                                                                            <span class="text-muted me-2">Is Twin</span>
                                                                            <div class="">
                                                                                <div class="form-check form-switch">
                                                                                    <input class="form-check-input" type="checkbox" role="switch" id="is_twin" checked>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <input type="text" class="form-control dbl_count" id="dbl_count" value="">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">TPL</span>
                                                                    <input type="text" class="form-control tpl_count" id="tpl_count" value="">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <button class="btn btn-primary update_room_count mt-3">Update Room Count</button>
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
                                                        <table class="table table-striped table-hover">
                                                            <thead>
                                                                <th>Day</th>
                                                                <th>Date</th>
                                                                <th>Hotel</th>
                                                                <th>Room Type</th>
                                                                <th>Meal Code</th>
                                                                <th>SGL</th>
                                                                <th>DBL</th>
                                                                <th>TPL</th>
                                                                <th>Mileage</th>
                                                                <th>Total KM</th>
                                                                <th>Extra KM</th>
                                                                <th></th>
                                                                <th>Activities</th>
                                                                <th>Itenaries</th>
                                                                <th>Delete</th>
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
                                                <h4 class="alert-heading">Invalid Tour!</h4>
                                                <p>This tour is not valid or not found in the system. Please check the tour ID and try again.</p>
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

        .select2-container--open {
            z-index: 4000 !important;
        }

        /* INCREASE SWAL CONTAINER Z-INDEX THAN MODAL */

        .swal2-container {
            z-index: 999999 !important;
        }
    </style>

    <!-- Modal -->
    <div class="modal fade" id="subLocationModal" tabindex="-1" aria-labelledby="subLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="subLocationModalLabel">Add Sub Locations</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <small class="form-label fw-normal text-muted mb-1">Start</small>
                            <input type="text" class="form-control from_location" value="">
                        </div>
                        <div class="col-md-5">
                            <small class="form-label fw-normal text-muted mb-1">End</small>
                            <input type="text" class="form-control to_location" value="">
                        </div>
                        <div class="col-md-2">
                            <small class="form-label fw-normal text-muted mb-1" style="visibility: hidden;">Dist</small>
                            <button class="btn btn-dark cal_distance w-100">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <small class="form-label fw-normal text-muted">Google</small>
                            <input type="text" class="form-control google_km" disabled value="">
                        </div>
                        <div class="col-md-4">
                            <small class="form-label fw-normal text-muted mb-1">Actual KM</small>
                            <input type="text" class="form-control actual_km" value="">
                        </div>
                        <div class="col-md-4">
                            <small class="form-label fw-normal text-muted mb-1">Extra KM</small>
                            <input type="text" class="form-control extra_km" value="">
                        </div>
                    </div>
                </div>
                <div class="w-100 px-3 pb-4 pt-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-secondary set_dist_0 d-none">Set Distance to 0</button>
                        </div>
                        <div class="d-flex">
                            <button type="button" class="btn btn-light me-1" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success save_sub_location" data-date="">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addMainActivityModal" tabindex="-1" aria-labelledby="addMainActivityModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addMainActivityModalLabel">Add Activity</h1>
                    <input type="hidden" name="activity_location_tour_id" id="activity_location_tour_id" value="<?= $tour_id ?>">
                    <input type="hidden" name="activity_location_date" id="activity_location_date">
                    <input type="hidden" name="activity_location_row" id="activity_location_row">
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="mb-4">
                            <label class="form-label" for="main_act_location">Select Place<font color="#FF0000"><strong>*</strong></font></label>
                            <select name="main_act_location" id="main_act_location" class="main_act_location form-control" style="width: 100%;" required>
                                <option value="">Select...</option>
                                <?php
                                foreach ($places as $place) {
                                    echo '<option value="' . $place['id'] . '" data-fee="' . $place['entranceFee'] . '" data-code="' . $place['code'] . '">' . $place['name'] . ' - ' . $place['code'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="activity_rate">Rate <font color="#FF0000"><strong>*</strong></font></label>
                            <input class="form-control txt_nav" type="text" id="activity_rate" name="activity_rate" placeholder="" required />
                        </div>
                        <div class="mb-4">
                            <button type="button" class="btn btn-success mt-2 save_main_activity">Save</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addSubLocationActivityModal" tabindex="-1" aria-labelledby="addSubLocationActivityModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addSubLocationActivityModalLabel">Add Visit/Sightseeing Activity</h1>
                    <input type="hidden" name="activity_sub_location_tour_id" id="activity_sub_location_tour_id" value="<?= $tour_id ?>">
                    <input type="hidden" name="activity_sub_location_date" id="activity_sub_location_date">
                    <input type="hidden" name="activity_sub_location_row" id="activity_sub_location_row">
                    <input type="hidden" name="loc_ref" id="loc_ref">
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="mb-4">
                            <label class="form-label" for="sub_act_location">Select Place<font color="#FF0000"><strong>*</strong></font></label>
                            <select name="sub_act_location" id="sub_act_location" class="sub_act_location form-control" style="width: 100%;" required>
                                <option value="">Select...</option>
                                <?php
                                foreach ($places as $place) {
                                    echo '<option value="' . $place['id'] . '" data-fee="' . $place['entranceFee'] . '" data-code="' . $place['code'] . '">' . $place['name'] . ' - ' . $place['code'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <button type="button" class="btn btn-success mt-2 save_sub_activity">Save</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ItineraryModal" tabindex="-1" aria-labelledby="ItineraryModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="ItineraryModalLabel">Itinerary Details</h1>
                    <input type="hidden" name="itinerary_tour_id" id="itinerary_tour_id">
                    <input type="hidden" name="itinerary_date" id="itinerary_date">
                    <input type="hidden" name="itinerary_row" id="itinerary_row">
                    <input type="hidden" name="pax_cc_itinerary" id="pax_cc_itinerary">
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="px-12">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Day</th>
                                        <th scope="col">Itinerary</th>
                                        <th scope="col">Area</th>
                                        <th scope="col">BF</th>
                                        <th scope="col">LUN</th>
                                        <th scope="col">DIN</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="itinerary_details">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok-itinerary">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="Additinerary" tabindex="-1" aria-labelledby="AdditineraryLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #f4f4f6; border-color: grey;">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="AdditineraryLabel">Add Itinerary</h1>
                    <input type="hidden" name="i_tour_id" id="i_tour_id">
                    <input type="hidden" name="i_date" id="i_date">
                    <input type="hidden" name="i_row" id="i_row">
                    <input type="hidden" name="i_order_id" id="i_order_id">
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="mb-4">
                            <label class="form-label" for="itinerary">Description <font color="#FF0000"><strong>*</strong></font></label>
                            <textarea class="form-control txt_nav" name="itinerary" id="itinerary" placeholder="" rows="4" required></textarea>
                            <button type="button" class="btn btn-success mt-2 save_itinerary">Save</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok-itinerary">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="EditItineraryModal" tabindex="-1" aria-labelledby="EditItineraryLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: #f4f4f6; border-color: grey;">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Edit Itinerary</h1>
                    <input type="hidden" id="ii_id">
                    <input type="hidden" id="ii_tour">
                    <input type="hidden" id="ii_row">
                    <input type="hidden" id="ii_date">
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="mb-4">
                            <label class="form-label" for="edit_itinerary_desc">Description <font color="#FF0000"><strong>*</strong></font></label>
                            <textarea class="form-control txt_nav" name="edit_itinerary_desc" id="edit_itinerary_desc" placeholder="" rows="4" required></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="edit_itinerary_area">Area <font color="#FF0000"><strong>*</strong></font></label>
                            <input type="text" class="form-control" name="edit_itinerary_area" id="edit_itinerary_area">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success mt-2 edit_itinerary">Save</button>
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
        $('.main_act_location').select2({
            placeholder: " Select",
            allowClear: false,
            dropdownParent: $('#addMainActivityModal')
        });
        $('.sub_act_location').select2({
            placeholder: " Select",
            allowClear: false,
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

                $(document).on('click', '.cal_distance', function() {
                    var date = $(this).data('date');
                    // get from and to locations from the same row or with the same date
                    var from = $(`.from_location`).val();
                    var to = $(`.to_location`).val();

                    if (from == "" || to == "") {
                        toastr.error('Please fill the from and to locations first!');
                        return;
                    }

                    var google_km = $(`.google_km`);
                    var actual_km = $(`.actual_km`);
                    var extra_km = $(`.extra_km`);

                    get_distance_from_distance_metrix_api(from, to, google_km, actual_km, extra_km);
                    $(`.to_location`).focus();
                });

                function get_city_from_address(address) {
                    address = remove_sri_lanka_from_address(address);
                    // split the address by ',' and get the last part
                    var city = address.split(',').pop();
                    return city.trim();
                }

                function remove_sri_lanka_from_address(address) {
                    return address.replace(', Sri Lanka', '');
                }

                function get_distance_from_db(from, to, frompick, todrop, actual_km, extra_km) {
                    extra_km.val(0);
                    frompick = remove_sri_lanka_from_address(frompick);
                    todrop = remove_sri_lanka_from_address(todrop);
                    $.ajax({
                        url: '<?php echo base_url(); ?>tour/quotation/actual_distance',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            from: from,
                            to: to,
                            frompick: frompick,
                            todrop: todrop
                        },
                        success: function(response) {
                            if (response.status == 200) {

                                var dd = response.data.Full_details[0];
                                var kk = response.data.Extra_details[0];

                                // if (hotelonly == 1) {
                                //     $('#distance').val(0);
                                //     $('#extrakm').val(0);
                                // } else {
                                //     if (dd?.actual_mileage != null) {
                                //         $('#distance').val(dd.actual_mileage);
                                //     }
                                //     if (kk?.kilometers != null) {
                                //         $('#extrakm').val(kk.kilometers);
                                //     }
                                // }
                                if (dd?.actual_mileage != null) {
                                    actual_km.val(dd.actual_mileage);
                                }
                                if (kk?.kilometers != null) {
                                    extra_km.val(kk.kilometers);
                                }
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.log(textStatus, errorThrown);
                        }
                    });
                }

                function get_distance_from_distance_metrix_api(from, to, google_km, actual_km, extra_km) {

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
                            google_km.val(distance);
                            actual_km.val(distance);
                            // get city name from the to location
                            var city = get_city_from_address(to);
                            get_distance_from_db(from, city, from, to, actual_km, extra_km);
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

                function get_sub_locations_activity_div(activities, date, row) {
                    var activity_div = '';
                    var i = 0;
                    console.log(activities);
                    activities?.forEach(act => {
                        i++;
                        activity_div += `
        <div class="d-flex flex-column sub_loc_item ms-3 mb-1 pb-0">
            <div class="d-flex justify-content-between w-100">
                <div>
                    <small class="form-label fw-normal text-dark">${i}. ${act.description}</small>
                </div>
                <div>
                    <a href="#" class="link delete_activity" data-id="${act.id}" data-row="${row}" data-ref="${act.ref_no}" data-date="${act.tour_date}" data-tour="${act.tour_id}" data-bs-toggle="tooltip" data-bs-title="Delete">
                        <i class="bi bi-trash text-danger"></i>
                    </a>
                </div>
            </div>
        </div>
        `;
                    });

                    return activity_div;
                }

                function get_sub_locations_div(sub_locations, date, row) {
                    var sub_loc_div = '';
                    var the_last_location = '';
                    var i = 0;
                    sub_locations.forEach(sub_loc => {
                        var sub_activities = sub_loc.sub_activities || [];
                        i++;
                        sub_loc_div += `
        <div class="d-flex flex-column sub_loc_item mb-1 pb-0">
            <small class="form-label fw-normal text-dark">${i}. ${sub_loc.from} ~ ${sub_loc.to}</small>
            <div class="d-flex justify-content-between" style="margin-top: -10px;">
                <div>
                    <small class="form-label fw-normal text-muted me-3">${sub_loc.km} KM</small>
                    ${sub_loc.extra_km > 0 ? '<small class="form-label fw-normal text-muted">Extra : '+ sub_loc.extra_km + ' KM</small>' : ''}
                    <br>
                    <a data-ref="${sub_loc.ref_no}" data-date="${sub_loc.date_id}" data-tour="${sub_loc.quotation_id}" data-row="${row}" href="javascript:void(0)" class="new_sub_activity">
                        + New Activity
                    </a>
                </div>
                <div class="delete_sub_location_div">
                    <a href="#" class="link delete_sub_location" data-id="${sub_loc.id}" data-ref="${sub_loc.ref_no}" data-row="${row}" data-date="${sub_loc.date_id}" data-tour="${sub_loc.quotation_id}" data-bs-toggle="tooltip" data-bs-title="Delete">
                        <i class="bi bi-trash text-danger"></i>
                    </a>
                </div>
            </div>
            <div class="mt-1 w-100">
                ${get_sub_locations_activity_div(sub_activities, date, row)}
            </div>
        </div>
        `;
                        the_last_location = sub_loc.to;
                    });


                    // add a new sub location button
                    sub_loc_div += `
    <div class="d-flex flex-column sub_loc_item sub_loc_item2 mt-2">
        <button data-date="${date}" data-to="${the_last_location}" class="btn btn-sm btn-light add_sub_location" style="width: fit-content;">
            <i class="bi bi-plus"></i> Add Mileage
        </button>
    </div>
    `;

                    previous_date_last_sublocation = the_last_location;

                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
                    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

                    return sub_loc_div;
                }

                function get_tour_activity_div(tour_activities, date, row, is_saved_date = false) {
                    var activity_div = '';

                    if (is_saved_date) {
                        var i = 0;
                        var total_fee = 0;
                        tour_activities.forEach(act => {
                            if (act.fee != NaN && act.fee != null) {
                                total_fee += parseFloat(act.fee);
                                i++;
                                activity_div += `
                <div class="d-flex flex-column sub_loc_item mb-1 pb-0">
                    <small class="form-label fw-normal text-dark">${i}. ${act.place_name} ~ ${act.code}</small>
                    <div class="d-flex justify-content-between" style="margin-top: -10px;">
                        <div>
                            <small class="form-label fw-normal text-muted me-3">$ ${parseFloat(act.fee).toFixed(2)} </small>
                        </div>
                        <div>
                            <a href="#" class="link delete_activity" data-id="${act.id}" data-row="${row}" data-ref="${act.ref_no}" data-date="${act.tour_date}" data-tour="${act.tour_id}" data-bs-toggle="tooltip" data-bs-title="Delete">
                                <i class="bi bi-trash text-danger"></i>
                            </a>
                        </div>
                    </div>
                </div>
                `;
                            }
                        });

                        // total fee
                        if (tour_activities.length > 0) {
                            activity_div += `
            <div class="d-flex flex-column sub_loc_item sub_loc_item2 mt-2">
                <span class="form-label fw-normal text-dark">Total Fee : $ <strong class="total_act_fee">${total_fee.toFixed(2)}</strong></span>
            </div>
        `;
                        }

                        // add a new activity button
                        activity_div += `
        <div class="d-flex flex-column sub_loc_item sub_loc_item2 mt-2">
            <button data-date="${date}" data-row="${row}" class="btn btn-sm btn-light add_new_main_activity" style="width: fit-content;">
                <i class="bi bi-plus"></i> Add Activity
            </button>
        </div>
        `;

                        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
                        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
                    } else {
                        activity_div += `
        <div class="d-flex flex-column sub_loc_item sub_loc_item2 mt-2">
            <small class="text-muted">
                No data saved for this date. You must save the date first to add activities.
            </small>
        </div>
        `;
                    }

                    return activity_div;
                }

                function get_copy_button(formatted_date, hyper_date, saved, row, start_date, end_date) {

                    if (hyper_date == end_date) {
                        return '';
                    }

                    var copy_btn = `
        <div class="d-flex flex-column">
            <button data-date="${formatted_date}" data-hyper-date="${hyper_date}"  data-row="${row}" data-start-date="${start_date}" data-end-date="${end_date}" class="btn btn-sm btn-warning text-dark copy_btn" data-bs-toggle="tooltip" data-bs-title="Copy Record to Next Day" style="width:fit-content">
                <i class="bi bi-clipboard-fill"></i>
            </button>
        </div>
    `;

                    return copy_btn;
                }

                function get_total_transport_cost() {
                    var _t_mileage = parseFloat($('.t_mileage').text());
                    var _t_days = parseFloat($('.t_days').val());
                    var _t_bata_rate = parseFloat($('.t_bata_rate').val());
                    var vehicle_rate = $('#t_vehicle_rate').text();

                    if (isNaN(_t_mileage) || isNaN(_t_days) || isNaN(_t_bata_rate)) {
                        toastr.error('Please fill the correct values for total transport cost calculation!');
                        return;
                    }

                    var total_transport_cost = (_t_days * _t_bata_rate) + (_t_mileage * vehicle_rate);
                    $('.t_bata_cost').text((_t_days * _t_bata_rate).toFixed(2));
                    $('.t_transport_cost').text(total_transport_cost.toFixed(2));

                    // console.log(`
                    //     Mileage : ${_t_mileage}
                    //     Days : ${_t_days}
                    //     Bata Rate : ${_t_bata_rate}
                    //     Total Transport Cost : ${total_transport_cost}
                    //     Bata Cost : ${_t_days * _t_bata_rate}
                    //     Mileage Cost : ${_t_mileage * vehicle_rate}
                    // `);
                }

                // .t_days on change
                $(document).on('change', '.t_days', function() {
                    get_total_transport_cost();
                });

                // .t_bata_rate on change
                $(document).on('change', '.t_bata_rate', function() {
                    get_total_transport_cost();
                });

                var total_tour_days = 0;

                function get_tour_bata_tr() {
                    var tour_bata_tr = `
    <tr class="summary_tr_4 table-warning align-middle">
        <td></td>
        <td></td>
        <td></td>
        <td>TOUR BATA</td>
        <td></td>
        <td></td>
        <td>No. of Days</td>
        <td>
            <input type="text" class="form-control t_days" style="width: 50px;" value="${total_tour_days}">
        </td>
        <td> X </td>
        <td>Per Day Rate</td>
        <td>
            <input type="text" class="form-control t_bata_rate" value="4500">
        </td>
        <td> = </td>
        <td>
            <span class="t_bata_cost">0.00</span>
        </td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    `;

                    // trigger change event to calculate the total transport cost
                    $('.t_days').trigger('change');
                    $('.t_bata_rate').trigger('change');

                    return tour_bata_tr;
                }

                function get_summary_tr() {
                    var total_sgl = 0;
                    var total_dbl = 0;
                    var total_tpl = 0;
                    var total_km = 0;
                    var total_extra_km = 0;
                    var total_act_fee = 0;

                    // loop through all the rows and get the total values
                    $('.dates tr').each(function() {

                        // if tr class is summary_tr_1, then skip
                        if ($(this).hasClass('summary_tr_1') || $(this).hasClass('summary_tr_2') || $(this).hasClass('summary_tr_3') || $(this).hasClass('summary_tr_4')) {
                            return;
                        }

                        var date = $(this).data('date');
                        var sgl = $(this).find('.sgl').val();
                        var dbl = $(this).find('.dbl').val();
                        var tpl = $(this).find('.tpl').val();
                        var km = $(this).find('.actual_km_txt').text();
                        var extra_km = $(this).find('.extra_km_txt').text();
                        var act_fee = $(this).find('.total_act_fee').text();

                        if (sgl == '---' || sgl == undefined || sgl == isNaN || sgl == '') sgl = 0;
                        if (dbl == '---' || dbl == undefined || dbl == isNaN || dbl == '') dbl = 0;
                        if (tpl == '---' || tpl == undefined || tpl == isNaN || tpl == '') tpl = 0;

                        // console.log('Date : ' + date);
                        // console.log('SGL : ' + sgl);
                        // console.log('DBL : ' + dbl);
                        // console.log('TPL : ' + tpl);
                        // console.log('KM : ' + km.trim());
                        // console.log('Extra KM : ' + extra_km.trim());
                        // console.log('Act Fee : ' + act_fee.trim());
                        // console.log('----------------------');

                        total_sgl += parseInt((sgl == '' ? 0 : sgl));
                        total_dbl += parseInt((dbl == '' ? 0 : dbl));
                        total_tpl += parseInt((tpl == '' ? 0 : tpl));
                        total_km += parseFloat(km.trim() == '' ? 0 : km.trim());
                        total_extra_km += parseFloat(extra_km.trim() == '' ? 0 : extra_km.trim());
                        total_act_fee += parseFloat(act_fee.trim() == '' ? 0 : act_fee.trim());
                    });

                    var summary_tr = `
    <tr class="summary_tr_1 table-warning">
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>${parseFloat(total_sgl).toFixed(2)}</td>
        <td>${parseFloat(total_dbl).toFixed(2)}</td>
        <td>${parseFloat(total_tpl).toFixed(2)}</td>
        <td></td>
        <td>${parseFloat(total_km).toFixed(1)}</td>
        <td>${parseFloat(total_extra_km).toFixed(1)}</td>
        <td></td>
        <td>Activity Fees : $ <span class="t_activity_fee">${parseFloat(total_act_fee).toFixed(2)}</span></td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    `;

                    var sgl_count = $('#sgl_count').val();
                    var dbl_count = $('#dbl_count').val();
                    var tpl_count = $('#tpl_count').val();
                    var vehicle_rate = $('#t_vehicle_rate').text();

                    var total_sgl_count = total_sgl * sgl_count;
                    var total_dbl_count = total_dbl * dbl_count;
                    var total_tpl_count = total_tpl * tpl_count;
                    var total_mileage = total_km + total_extra_km;

                    var summary_tr_2 = `
    <tr class="summary_tr_2 align-middle table-warning">
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>${parseFloat(total_sgl_count).toFixed(2)}</td>
        <td>${parseFloat(total_dbl_count).toFixed(2)}</td>
        <td>${parseFloat(total_tpl_count).toFixed(2)}</td>
        <td></td>
        <td>Total Mile</td>
        <td>
            <span class="t_mileage">${parseFloat(total_mileage).toFixed(1)}</span>
            <br>
            <span> x ${vehicle_rate}</span>
        </td>
        <td> = </td>
        <td> ${parseFloat(total_mileage * vehicle_rate).toFixed(2)} </td>
        <td></td>
        <td></td>
        <td></td>
    </tr>
    `;

                    var summary_tr_3 = `
    <tr class="summary_tr_3 align-middle table-success">
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>Total Cost ($)</td>
        <td><span class="t_room_cost">${parseFloat((total_sgl_count + total_dbl_count + total_tpl_count + total_act_fee)).toFixed(2)}</span></td>
        <td></td>
        <td>Total Transport Cost (LKR)</td>
        <td><span class="t_transport_cost">0.00</span></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>
            <button class="btn btn-danger finalize_btn mt-3" id="finalize_btn">Finalize</button>
        </td>
    </tr>
    `;

                    var tour_bata_tr = get_tour_bata_tr();

                    return summary_tr + tour_bata_tr + summary_tr_2 + summary_tr_3;
                }

                function set_summary_row() {
                    var summary_tr = get_summary_tr();
                    $('.summary_tr_1').remove();
                    $('.summary_tr_2').remove();
                    $('.summary_tr_3').remove();
                    $('.summary_tr_4').remove();
                    $('.dates').append(summary_tr);
                    get_total_transport_cost();
                }

                function populate_table_rows(start_date, end_date) {

                    var tour_id = <?= $tour_id ?>;

                    total_tour_days = 0;
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

                    var tbody = $('.dates');
                    tbody.empty();

                    var no = 1;
                    dates.forEach(date => {

                        total_tour_days++;
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

                        // console.log('Date : ' + hyper_date);
                        // console.log('Is saved : ' + is_saved);
                        // console.log(saved);
                        // console.log('----------------------');

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
                        var ro_selected = meal_code == 'ro' ? 'selected' : '';
                        var sub_locations = saved.sub_locations || [];
                        var tour_activities = saved.tour_activities || [];
                        var itenaries = saved.itenaries || [];

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
                <span data-date="${formatted_date}" class="hotel_txt txt_lbl">
                </span>
                <div data-date="${formatted_date}" class="hotel_div">
                    <select data-date="${formatted_date}" class="form-control t-fc hotel w-100 d-none">
                        <option value="0">Select...</option>
                    </select>
                </div>
                <br>
                <a href="<?= base_url('master_files/supplier') ?>" class="td-anchor" target="_blank"><i class="bi bi-box-arrow-up-right"></i> New Hotel</a>
            </td>
            <td>
                <span data-date="${formatted_date}" class="room_type_txt txt_lbl">
                </span>
                <div data-date="${formatted_date}" class="room_type_div">
                    <select data-date="${formatted_date}" class="form-control t-fc room_type d-none" style="width: 100px;">
                        <option value="0">Select...</option>
                    </select>
                </div>
                <a data-date="${formatted_date}" href="javascript:void(0)" class="td-anchor new_room_type"><i class="bi bi-box-arrow-up-right"></i> New Type</a>
            </td>
            <td>
                <div class="d-flex flex-column">
                    <div class="form-group">
                        <span data-date="${formatted_date}" class="meal_code_txt txt_lbl">
                        </span>
                        <select data-date="${formatted_date}" class="form-control t-fc meal_code d-none" style="width: 100px;">
                            <option value="0">Select...</option>
                            <option value="bb" ${bb_selected}>BB</option>
                            <option value="hb" ${hb_selected}>HB</option>
                            <option value="fb" ${fb_selected}>FB</option>
                            <option value="ro" ${ro_selected}>RO</option>
                        </select>
                    </div>
                </div>
            </td>
            <td>
                <span data-date="${formatted_date}" class="sgl_txt txt_lbl">
                    ${sgl}
                </span>
                <input data-date="${formatted_date}" type="text" class="form-control sgl t-fc-sm d-none" value="${sgl}" style="width: 70px;">
            </td>
            <td>
                <span data-date="${formatted_date}" class="dbl_txt txt_lbl">
                    ${dbl}
                </span>
                <input data-date="${formatted_date}" type="text" class="form-control dbl t-fc-sm d-none" value="${dbl}" style="width: 70px;">
            </td>
            <td>
                <span data-date="${formatted_date}" class="tpl_txt txt_lbl">
                    ${tpl}
                </span>
                <input data-date="${formatted_date}" type="text" class="form-control tpl t-fc-sm d-none" value="${tpl}" style="width: 70px;">
            </td>
            <td style="min-width: 200px;">
                <div class="d-flex flex-column sub_locations_div" data-date="${formatted_date}"></div>
            </td>   
            <td style="min-width: 50px;">
                <span data-date="${formatted_date}" class="actual_km_txt">
                    ${parseFloat(actual_km).toFixed(1)}
                </span>
            </td>   
            <td style="min-width: 85px;">
                <span data-date="${formatted_date}" class="extra_km_txt">
                    ${parseFloat(extra_km).toFixed(1)}
                </span>
            </td>   
            <td>
                <div class="d-flex flex-column">
                    <button data-date="${formatted_date}" class="btn btn-sm btn-success save_btn" data-flag="${is_saved ? '1' : '0'}" data-bs-toggle="tooltip" data-bs-title="Save Date">
                        <i class="bi bi-check-square-fill"></i>
                    </button>
                </div>
            </td>                          
            <td style="min-width: 200px;">
                <div class="d-flex flex-column main_activities_div" data-date="${formatted_date}">
                    ` + get_tour_activity_div(tour_activities, formatted_date, no, is_saved) + `
                </div>
            </td>                     
            <td style="min-width: 50px;">
                <div class="d-flex flex-column">
                    <button data-date="${formatted_date}" data-hyper-date="${hyper_date}"  data-row="${no}" data-start-date="${start_date}" data-end-date="${end_date}" class="btn btn-sm btn-secondary show-itinerary" data-bs-toggle="tooltip" data-bs-title="View Itenary" style="width: fit-content;">
                        <i class="bi bi-list-task"></i>
                    </button>
                </div>
            </td>                     
            <td>
                <div class="d-flex flex-column">
                    <button data-date="${formatted_date}" data-hyper-date="${hyper_date}"  data-row="${no}" data-start-date="${start_date}" data-end-date="${end_date}" class="btn btn-sm btn-danger delete_btn" data-bs-toggle="tooltip" data-bs-title="Delete Record" style="width: fit-content;">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>  
            <td class="copy_td" data-date="${formatted_date}" data-hyper-date="${hyper_date}"  data-row="${no}" data-start-date="${start_date}" data-end-date="${end_date}">
            </td>                     
        </tr>
        `;

                        //get_copy_button(hyper_date, start_date, end_date, saved_data)

                        tbody.append(row);

                        // get hotels for the date
                        get_hotels(formatted_date);

                        sgl = saved.sgl || '---';
                        dbl = saved.dbl || '---';
                        tpl = saved.tpl || '---';

                        // show rates labels
                        // console.log('Formatted Date : ' + formatted_date);
                        $('.sgl[data-date="' + formatted_date + '"]').val(sgl).change();
                        $('.dbl[data-date="' + formatted_date + '"]').val(dbl).change();
                        $('.tpl[data-date="' + formatted_date + '"]').val(tpl).change();

                        // show meal code label
                        show_meal_code_txt(formatted_date);

                        // show room type label
                        // show_room_type_txt(formatted_date);

                        load_sub_locations(tour_id, hyper_date, no);

                        no++;

                    });

                    initMap();

                    // set copy button
                    set_copy_button();

                    // set summary row
                    set_summary_row();

                    $('.hotel').select2({
                        placeholder: " Select",
                        allowClear: false,
                    });

                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
                    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

                    $('#loadingModal').modal('hide');
                }

                function clear_all_copy_tds(date) {
                    // clear all copy_td where data-hyper-date is not equal to the date
                    $(`.copy_td`).each(function() {
                        var hyper_date = $(this).data('hyper-date');
                        if (hyper_date != date) {
                            $(this).empty();
                        }
                    });
                }

                function set_copy_button() {
                    var tour_id = <?= $tour_id ?>;

                    $.ajax({
                        url: '<?php echo base_url('tour/quotation/get_last_saved_date') ?>/' + tour_id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            // console.log("copy btn date : " + data);
                            if (data) {
                                // get copy_td where data-hyper-date is equal to the last saved date
                                var copy_td = $(`.copy_td[data-hyper-date="${data}"]`);

                                var formatted_date = copy_td.data('date');
                                var hyper_date = copy_td.data('hyper-date');
                                var row = copy_td.data('row');
                                var start_date = copy_td.data('start-date');
                                var end_date = copy_td.data('end-date');

                                var copy_btn = get_copy_button(formatted_date, hyper_date, null, row, start_date, end_date);
                                copy_td.empty();
                                copy_td.append(copy_btn);

                                clear_all_copy_tds(data);
                                disable_buttons($('#t_finalize').text());

                                const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
                                const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-COPY-BTN',
                            });
                        }
                    });
                }

                function disable_buttons(finalize) {
                    if (finalize == 1) {

                        // check if query string has edit=2
                        const urlParams = new URLSearchParams(window.location.search);
                        const edit = urlParams.get('edit');

                        if (edit != 2) {
                            $('.save_btn').prop('disabled', true);
                            $('.copy_btn').prop('disabled', true);
                            $('.delete_btn').prop('disabled', true);
                            // $('.show-itinerary').prop('disabled', true);
                            $('.add_sub_location').prop('disabled', true);
                            $('.new_sub_activity').addClass('d-none');
                            $('.add_new_main_activity').prop('disabled', true);
                            $('.delete_sub_location_div').addClass('d-none');
                            $('.delete_activity').addClass('d-none');
                            $('.t_days').prop('disabled', true);
                            $('.t_bata_rate').prop('disabled', true);
                            $('.add_remove_days_txt').prop('disabled', true);
                            $('.add_days').prop('disabled', true);
                            $('.remove_days').prop('disabled', true);
                            $('.update_room_count').prop('disabled', true);
                            $('#hotel_only').prop('disabled', true);
                            $('#transport_only').prop('disabled', true);
                            $('#is_twin').prop('disabled', true);
                            $('#is_template').prop('disabled', true);
                            $('#sgl_count').prop('disabled', true);
                            $('#dbl_count').prop('disabled', true);
                            $('#tpl_count').prop('disabled', true);
                        }
                        $('#finalize_btn').prop('disabled', finalize);
                    }
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
                                $('#t_vehicle').text(consignee.vehicle_name);
                                $('#t_vehicle_rate').text(consignee.vehicle_rate);

                                $('#t_finalize').text(consignee.finalize);
                                if (consignee.finalize == 1) {
                                    // .finalized_print is hidden
                                    $('.finalized_print').removeClass('d-none');
                                }

                                // room count
                                $('#sgl_count').val(consignee.sgl);
                                $('#dbl_count').val(consignee.dbl);
                                $('#tpl_count').val(consignee.tpl);

                                // hotel only
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

                                // if consignee.dbl_twin == 1, check the is_twin checkbox
                                if (consignee.dbl_twin == 1) {
                                    $('#is_twin').prop('checked', true);
                                } else {
                                    $('#is_twin').prop('checked', false);
                                }

                                var is_template = consignee.is_template;
                                if (is_template == 1) {
                                    $('#is_template').prop('checked', true);
                                } else {
                                    $('#is_template').prop('checked', false);
                                }

                                //add_remove_days_txt
                                $('.add_remove_days_txt').val("1");

                                $.ajax({
                                    url: '<?php echo base_url('tour/quotation/get_tour_data') ?>/' + tour_id,
                                    type: 'GET',
                                    dataType: 'json',
                                    success: function(data) {
                                        saved_data = data.data;
                                        populate_table_rows(consignee.start_date, consignee.end_date);
                                        disable_buttons(consignee.finalize);
                                    },
                                    error: function() {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'An error occured while processing the request. ERR-GET-TOUR-DATA',
                                        });
                                    }
                                });
                            } else {
                                toastr.error(data.message || 'An error occured while processing the request. ERR-GET-TOUR-DATA');
                            }
                        },
                        error: function() {

                            $('#loadingModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-GET-TOUR-DATA',
                            });
                        }
                    });
                }

                function get_hotels(date) {

                    var hyper_date = date.replace(/_/g, '-');

                    var saved_hotel = '0';
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

                                    $('.hotel[data-date="' + date + '"]').val((saved_hotel || 0)).change();
                                }
                            } else {
                                toastr.error(data.message || 'An error occured while processing the request. ERR-GET-HOTELS');
                            }
                        },
                        error: function() {
                            $('#loadingModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-GET-HOTELS',
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
                        // reset room type select
                        $('.room_type[data-date="' + date + '"]').empty();
                        $('.room_type[data-date="' + date + '"]').append('<option value="0">Select...</option>').change();
                        return;
                    }

                    var saved_room_type = '0';
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

                                    // check if saved_room_type is in the list of room types
                                    if (saved_room_type && saved_room_type != '0') {
                                        saved_room_type_in_list = false;
                                        data.data.hotels_type.forEach(room_type => {
                                            if (room_type.id == saved_room_type) {
                                                saved_room_type_in_list = true;
                                                return;
                                            }
                                        });

                                        if (!saved_room_type_in_list) {
                                            saved_room_type = '0';
                                        }
                                    }

                                    if (saved_room_type == '' || saved_room_type == null || saved_room_type == 0) {
                                        saved_room_type = '0';
                                    }

                                    $('.room_type[data-date="' + date + '"]').val(saved_room_type).change();
                                }
                            } else {
                                toastr.error(data.message ? data.message : 'An error occured while processing the request. ERR-HOTEL-CHANGED');
                            }
                        },
                        error: function() {
                            $('#loadingModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-HOTEL-CHANGED',
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

                    var hyper_date = date.replace(/_/g, '-');

                    $('#loadingModal').modal('show');
                    // ajax call
                    $.ajax({
                        url: '<?php echo base_url('tour/quotation/get_meal_prices') ?>',
                        type: 'GET',
                        data: {
                            room_type: room_type,
                            h_code: h_code,
                            meal_plan: id,
                            date: hyper_date
                        },
                        dataType: 'json',
                        success: function(data) {
                            $('#loadingModal').modal('hide');
                            if (data.status == 200) {
                                var sgl = data.SGL;
                                var dbl = data.DBL;
                                var tpl = data.TPL;
                                $('.sgl[data-date="' + date + '"]').val(sgl).change();
                                $('.dbl[data-date="' + date + '"]').val(dbl).change();
                                $('.tpl[data-date="' + date + '"]').val(tpl).change();
                            } else {
                                toastr.error(data.message ? data.message : 'An error occured while processing the request. ERR-MEALCODE-CHANGED');
                            }
                        },
                        error: function() {
                            $('#loadingModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-MEALCODE-CHANGED',
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

                function load_sub_locations(tour_id, date, row) {
                    $.ajax({
                        url: '<?php echo base_url('tour/sub_locations/get_sub_locations/') ?>' + tour_id + '/' + date,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data) {
                                var sub_locations = data;
                                var underscore_date = date.replace(/-/g, '_');
                                var sub_loc_div = get_sub_locations_div(sub_locations, underscore_date, row);
                                $('.sub_locations_div[data-date="' + underscore_date + '"]').empty();
                                $('.sub_locations_div[data-date="' + underscore_date + '"]').append(sub_loc_div);
                                disable_buttons($('#t_finalize').text());
                            } else {
                                toastr.error(data.message || 'An error occured while processing the request. ERR-LOAD-SUB-LOCATIONS');
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-LOAD-SUB-LOCATIONS',
                            });
                        }
                    });
                }

                function load_mileage(date) {
                    var tour_id = <?= $tour_id ?>;

                    $.ajax({
                        url: '<?php echo base_url('tour/quotation/get_mileage') ?>',
                        type: 'GET',
                        data: {
                            tour_id: tour_id,
                            date: date
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status == 200) {
                                var actual_km = data.data[0]?.distance || 0;
                                var extra_km = data.data[0]?.extra_kilo || 0;
                                date = date.replace(/-/g, '_');
                                $('.actual_km_txt[data-date="' + date + '"]').text(parseFloat(actual_km).toFixed(1));
                                $('.extra_km_txt[data-date="' + date + '"]').text(parseFloat(extra_km).toFixed(1));

                                // set summary row
                                set_summary_row();
                            } else {
                                toastr.error(data.message || 'An error occured while processing the request. ERR-LOAD-MILEAGE');
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-LOAD-MILEAGE',
                            });
                        }
                    });
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
                    // var hotel_city = $('.hotel_city[data-date="' + date + '"]').val();
                    var hotel = $('.hotel[data-date="' + date + '"]').val();
                    var hotel_name = $('.hotel[data-date="' + date + '"] option:selected').text();
                    var room_type = $('.room_type[data-date="' + date + '"]').val();
                    var meal_code = $('.meal_code[data-date="' + date + '"]').val();
                    var sgl = $('.sgl[data-date="' + date + '"]').val();
                    var dbl = $('.dbl[data-date="' + date + '"]').val();
                    var tpl = $('.tpl[data-date="' + date + '"]').val();

                    var hotel_only = $('#hotel_only').is(':checked') ? 1 : 0;

                    date = date.replace(/-/g, '_');

                    $.ajax({
                        url: '<?php echo base_url('tour/quotation/save_date') ?>',
                        type: 'POST',
                        data: {
                            trans_tour_id: <?= $tour_id ?>,
                            trans_row_id: no.trim(),
                            t_date: date,
                            hotel: hotel,
                            hotel_name: hotel_name,
                            room_type: room_type,
                            meal_code: meal_code,
                            sgl: sgl,
                            dbl: dbl,
                            tpl: tpl,
                            is_hotel: hotel_only,
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
                                    // show hotel label
                                    show_hotel_txt(date, hotel_name);

                                    // update the save button data-flag to 0
                                    $('.save_btn[data-date="' + date + '"]').data('flag', '1');

                                    // get_tour_details();
                                    date = date.replace(/_/g, '-');
                                    load_sub_locations(<?= $tour_id ?>, date, no);
                                    load_mileage(date);
                                    load_activities(<?= $tour_id ?>, date, no);
                                    set_copy_button();

                                    // // replace date - with _
                                    // date = date.replace(/-/g, '_');
                                    // // remove the 'btn-success' class
                                    // $('.save_btn[data-date="' + date + '"]').removeClass('btn-success');
                                    // // set the 'save_btn_bg' class to save_btn with data-date=date
                                    // $('.save_btn[data-date="' + date + '"]').addClass('save_btn_bg');

                                    toastr.success(date + ' saved successfully!');
                                }
                            } else {
                                toastr.error(data.message || 'An error occured while processing the request. ERR-SAVE-DATE');
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-SAVE-DATE',
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
                    var pick_location = $('.from_location').val();
                    var drop_location = $('.to_location').val();
                    var gdistance = $('.google_km').val();
                    var distance = $('.actual_km').val();
                    var extrakm = $('.extra_km').val();
                    date = date.replace(/_/g, '-');

                    if (pick_location == '' || drop_location == '' || gdistance == '' || distance == '') {
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
                            extrakm: extrakm || 0
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status == 200) {
                                var underscore_date = date.replace(/-/g, '_');
                                load_sub_locations(tour, date, row);
                                // clear the fields
                                $('.from_location').val('');
                                $('.to_location').val('');
                                $('.google_km').val('');
                                $('.actual_km').val('');
                                $('.extra_km').val('');
                                $('#subLocationModal').modal('hide');
                                toastr.success("Sightseeing saved successfully! The mileage will be calculated and updated once you saved the date.");

                                var save_type = $('.save_btn[data-date="' + underscore_date + '"]').data('flag') == '1' ? 'update' : 'insert';

                                save_date(underscore_date, save_type, false);
                                trigger_to_save(date);
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

                // .add_sub_location button click event
                $(document).on('click', '.add_sub_location', function() {
                    var date = $(this).data('date');
                    var to = $(this).data('to');
                    $('.save_sub_location').data('date', date);

                    var hyper_date = date.replace(/_/g, '-');
                    var tour_id = <?= $tour_id ?>;

                    if (to == '' || to == null) {
                        $.ajax({
                            url: '<?php echo base_url('tour/sub_locations/get_last_sublocation_of_date') ?>' + '/' + tour_id + '/' + hyper_date,
                            type: 'GET',
                            dataType: 'json',
                            success: function(data) {
                                if (data) {
                                    $('.from_location').val(data.to || '');
                                } else {
                                    $('.from_location').val('');
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'An error occured while processing the request. ERR-GET-LOCATIONS',
                                });
                            }
                        });
                    } else {
                        $('.from_location').val(to);
                    }

                    // open model
                    $('#subLocationModal').modal('show');
                });

                // delete sub location click event
                $(document).on('click', '.delete_sub_location', function(e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    var ref_no = $(this).data('ref');
                    var date = $(this).data('date');
                    var tour = $(this).data('tour');
                    var row = $(this).data('row');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to delete this Sub Location!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: "get",
                                url: "<?php echo base_url(); ?>tour/sub_locations/location_delete",
                                data: {
                                    id: id,
                                    ref_no: ref_no
                                },
                                dataType: "json",
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                load_sub_locations(tour, date, row);
                                            }
                                        });
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
                        }
                    });
                });

                // .add_new_main_activity button click event
                $(document).on('click', '.add_new_main_activity', function() {
                    var date = $(this).data('date');
                    var row = $(this).data('row');
                    $('#activity_location_date').val(date);
                    $('#activity_location_row').val(row);
                    // open model
                    $('#addMainActivityModal').modal('show');
                });

                // .main_activity_location on change
                $(document).on('change', '#main_act_location', function() {
                    var place_fee = $('#main_act_location option:selected').data('fee');
                    $('#activity_rate').val(place_fee);
                });

                function load_activities(tour_id, date, row) {
                    $.ajax({
                        url: '<?php echo base_url('tour/quotation/activity/get_activities') ?>/' + tour_id + '/' + date,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data && data.status == 200) {
                                var activities = data.activities || [];
                                var underscore_date = date.replace(/-/g, '_');
                                var activity_div = get_tour_activity_div(activities, underscore_date, row, true);
                                $('.main_activities_div[data-date="' + underscore_date + '"]').empty();
                                $('.main_activities_div[data-date="' + underscore_date + '"]').append(activity_div);
                                // set summary row
                                set_summary_row();
                            } else {
                                toastr.error(data.message || 'An error occured while processing the request. ERR-LOAD-ACTIVITIES');
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occured while processing the request. ERR-LOAD-ACTIVITIES',
                            });
                        }
                    });
                }

                $(document).on('click', '.save_main_activity', function() {

                    var place = $('#main_act_location').val();
                    var place_name = $('#main_act_location option:selected').text();
                    var place_code = $('#main_act_location option:selected').data('code');
                    var activity_tour_id = <?= $tour_id ?>;
                    var activity_date = $('#activity_location_date').val();
                    activity_date = activity_date.replace(/_/g, '-');
                    var activity_row = $('#activity_location_row').val();
                    var place_fee = $('#activity_rate').val();
                    var pax_counts = $('#t_passenger_count').text();


                    if (place == '' || activity_tour_id == '' || activity_date == '' || activity_row == '' || pax_counts == '') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Missing Required Values!',
                        });
                    } else {
                        $.ajax({
                            type: "POST",
                            url: "<?php echo base_url(); ?>tour/quotation/activity/create",
                            data: {
                                place: place,
                                place_name: place_name,
                                place_fee: place_fee,
                                place_code: place_code,
                                activity_tour_id: activity_tour_id,
                                activity_date: activity_date,
                                activity_row: activity_row,
                                pax_counts: pax_counts
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.status == 201) {
                                    $('#addMainActivityModal').modal('hide');
                                    load_activities(activity_tour_id, activity_date, activity_row);
                                    toastr.success(response.message || 'Activity saved successfully!');
                                } else {
                                    toastr.error(response.message || 'An error occured while saving the activity.');
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
                    }
                });

                // .delete_activity click event
                $(document).on('click', '.delete_activity', function(e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    var row = $(this).data('row');
                    var date = $(this).data('date');
                    var tour = $(this).data('tour');
                    var ref = $(this).data('ref');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to delete this Activity!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: "get",
                                url: "<?php echo base_url(); ?>tour/quotation/activity/delete_activity",
                                data: {
                                    id: id,
                                    ref_no: ref,
                                    tour_id: tour,
                                    tour_row: row,
                                    tour_date: date
                                },
                                dataType: "json",
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                load_activities(tour, date, row);
                                            }
                                        });
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
                        }
                    });
                });

                // .new_sub_activity button click event
                $(document).on('click', '.new_sub_activity', function() {
                    var date = $(this).data('date');
                    var ref = $(this).data('ref');
                    var row = $(this).data('row');
                    $('#activity_sub_location_date').val(date);
                    $('#activity_sub_location_row').val(row);
                    $('#loc_ref').val(ref);
                    // open model
                    $('#addSubLocationActivityModal').modal('show');
                });

                $(document).on('click', '.save_sub_activity', function() {

                    var place = $('#sub_act_location').val();
                    var place_name = $('#sub_act_location option:selected').text();
                    var place_code = $('#sub_act_location option:selected').data('code');
                    var activity_tour_id = <?= $tour_id ?>;
                    var activity_date = $('#activity_sub_location_date').val();
                    activity_date = activity_date.replace(/_/g, '-');
                    var activity_row = $('#activity_sub_location_row').val();
                    var location_ref = $('#loc_ref').val();

                    if (place == '' || activity_tour_id == '' || activity_date == '' || activity_row == '' || location_ref == '' || activity_row == '0') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Missing Required Values!',
                        });
                    } else {
                        // disable save_sub_activity
                        $('.save_sub_activity').prop('disabled', true);
                        $.ajax({
                            type: "POST",
                            url: "<?php echo base_url(); ?>tour/quotation/activity/sub_location_activity/create",
                            data: {
                                place: place,
                                place_name: place_name,
                                place_code: place_code,
                                activity_tour_id: activity_tour_id,
                                activity_date: activity_date,
                                activity_row: activity_row,
                                location_ref: location_ref
                            },
                            dataType: "json",
                            success: function(response) {
                                console.log(response);
                                // enable save_sub_activity
                                $('.save_sub_activity').prop('disabled', false);
                                if (response.status == 200) {
                                    load_sub_locations(activity_tour_id, activity_date, activity_row);
                                    // clear the fields
                                    $('#sub_act_location').val('');
                                    toastr.success(response.message || 'Activity saved successfully!');
                                    // close modal
                                    $('#addSubLocationActivityModal').modal('hide');
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Oops...',
                                        text: response.message,
                                    });
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                // enable save_sub_activity
                                $('.save_sub_activity').prop('disabled', false);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong!',
                                });
                            }
                        });
                    }
                });

                // .copy_btn click event
                $(document).on('click', '.copy_btn', function() {

                    var date = $(this).data('date');
                    var hyper_date = $(this).data('hyper-date');
                    var tour_id = <?= $tour_id ?>;
                    var row = $(this).data('row');
                    var start_date = $(this).data('start-date');
                    var end_date = $(this).data('end-date');

                    // get the next date
                    var next_date = $(`.row_date[data-date="${date}"]`).closest('tr').next().find('.row_date').data('date');
                    next_date = next_date.replace(/_/g, '-');

                    $.ajax({
                        type: "POST",
                        url: "<?php echo base_url(); ?>tour/quotation/tour_copy",
                        data: {
                            tour: tour_id,
                            date: hyper_date,
                            row: row,
                            nextd: next_date
                        },
                        dataType: "json",
                        success: function(response) {
                            console.log(response);
                            if (response.status == 200) {
                                get_tour_details();
                                toastr.success(response.message || 'Activity saved successfully!');
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

                $(document).on('click', '.delete_btn', function() {
                    var date = $(this).data('hyper-date');
                    var tour = <?= $tour_id ?>;
                    var row = $(this).data('row');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to delete this Date Record!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: "get",
                                url: "<?php echo base_url(); ?>tour/quotation/delete",
                                data: {
                                    date: date,
                                    tour: tour,
                                    row: row
                                },
                                dataType: "json",
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                get_tour_details();
                                            }
                                        });
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
                        }
                    });

                });

                // delete itinerary
                $(document).on('click', '.delete_itinerary', function() {

                    var id = $(this).data('id');
                    var itinerary_tour_id = $('#itinerary_tour_id').val();
                    var itinerary_date = $('#itinerary_date').val();
                    var itinerary_row = $('#itinerary_row').val();

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to delete this Itinerary!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                type: "post",
                                url: "<?php echo base_url(); ?>tour/itinerary/delete",
                                data: {
                                    id: id,
                                },
                                dataType: "json",
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                load_itinerary(itinerary_tour_id, itinerary_row, itinerary_date);
                                            }
                                        });
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
                        }
                    });
                });

                // add itinerary button
                $(document).on('click', '.add_itinerary', function() {

                    var order_id = $(this).data('order');
                    var tour = $(this).data('tour');
                    var row = $(this).data('row');

                    $('#i_order_id').val(order_id);
                    $('#i_tour_id').val(tour);
                    $('#i_row').val(row);
                    $('#i_date').val($('#itinerary_date').val());

                    $('#Additinerary').modal('show');
                });

                $(document).on('click', '.save_itinerary', function() {

                    var itinerary = $('#itinerary').val();
                    // alert(place_fee);
                    var itinerary_tour_id = $('#i_tour_id').val();
                    var itinerary_date = $('#i_date').val();
                    var itinerary_row = $('#i_row').val();
                    var itinerary_order_id = $('#i_order_id').val();

                    if (itinerary == '' || itinerary_tour_id == '' || itinerary_date == '' || itinerary_row == '' || itinerary_order_id == '') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Missing Required Values!',
                        });
                    } else {
                        $.ajax({
                            type: "POST",
                            url: "<?php echo base_url(); ?>tour/itinerary/create",
                            data: {
                                itinerary: itinerary,
                                itinerary_tour_id: itinerary_tour_id,
                                itinerary_date: itinerary_date,
                                itinerary_row: itinerary_row,
                                itinerary_order_id: itinerary_order_id
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.status == 201) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            load_itinerary(itinerary_tour_id, itinerary_row, itinerary_date);
                                            $('#Additinerary').modal('hide');
                                        }
                                    });
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
                    }
                });

                function load_itinerary(tour, row, date) {

                    $.ajax({
                        type: "GET",
                        url: "<?php echo base_url(); ?>tour/quotation/itinerary_all/" + tour + '/' + row + '/' + date,
                        dataType: "json",
                        success: function(response) {
                            $('#itinerary_details').empty();
                            if (response && response.length >= 0) {
                                var i = 1;
                                $.each(response, function(index, value) {
                                    var timeInputId = 'time-input';
                                    // Handle case when itinerary is empty or does not exist
                                    if (value.description != '') {
                                        // alert(date);
                                        $('#itinerary_details').append(
                                            `<tr>
                            <th scope="row">${i}</th>
                            <td>${value.tour_date}</td>
                            <td>${value.description}</td>
                            <td>${value.area}</td>
                            <td>${value.bf}</td>
                            <td>${value.lunch}</td>
                            <td>${value.dinner}</td>
                            <td>
                                <div class="d-flex">
                                    <button type="button" class="btn btn-sm btn-secondary me-1 edit_itinerary_btn" data-id="${value.id}" data-tour="${value.tour_id}" data-row="${value.tour_row}" data-date="${value.tour_date}" data-description="${value.description}" data-area="${value.area}"><i class="bi bi-pen"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger me-1 delete_itinerary" data-id="${value.id}"><i class="bi bi-trash"></i></button>
                                    <button type="button" class="btn btn-sm btn-success add_itinerary" data-order="${value.order_id}" data-tour="${tour}" data-row="${row}"><i class="bi bi-plus"></i></button>
                                </div>
                            </td>
                        </tr>`
                                        );

                                        i++;
                                    }
                                });

                            }

                            $('#ItineraryModal').modal('show');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                            });
                        }
                    });

                }

                $(document).on('click', '.show-itinerary', function() {

                    var date = $(this).data('hyper-date');
                    var tour = <?= $tour_id ?>;
                    var row = $(this).data('row');

                    $("#itinerary_tour_id").val(tour);
                    $("#itinerary_date").val(date);
                    $("#itinerary_row").val(row);

                    load_itinerary(tour, row, date);

                });

                $('.update_room_count').on('click', function() {

                    var tour_id = <?= $tour_id ?>;
                    var sgl_room = $('#sgl_count').val();
                    var dbl_room = $('#dbl_count').val();
                    var tpl_room = $('#tpl_count').val();
                    var is_twn = $('#is_twin').is(':checked') ? 1 : 0;

                    $.ajax({
                        type: "POST",
                        url: "<?php echo base_url(); ?>tour/quotation/room_count_add",
                        data: {
                            room_tour_id: tour_id,
                            sgl_room: sgl_room,
                            dbl_room: dbl_room,
                            tpl_room: tpl_room,
                            is_twn: is_twn
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        get_tour_details();
                                    }
                                });
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
                    })
                });

                $(document).on('click', '.finalize_btn', function() {

                    // confirm swal
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to finalize this tour!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, finalize it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            var tour = <?= $tour_id ?>;
                            var start_date = $('#t_start_date').val();
                            var end_date = $('#t_end_date').val();

                            var room_cost = $('.t_room_cost').text();
                            var transport_cost = $('.t_transport_cost').text();
                            var total_mileage = $('.t_mileage').text();
                            var _t_days = parseFloat($('.t_days').val());
                            var _t_bata_rate = parseFloat($('.t_bata_rate').val());
                            var _t_activity_fee = $('.t_activity_fee').text();
                            var _is_template = $('#is_template').is(':checked') ? 1 : 0;

                            $.ajax({
                                url: '<?php echo base_url(); ?>tour/quotation/finalize/' + tour,
                                type: 'POST',
                                data: {
                                    room_cost: room_cost,
                                    transport_cost: transport_cost,
                                    total_mileage: total_mileage,
                                    t_days: _t_days,
                                    t_bata_rate: _t_bata_rate,
                                    activity_fee_total: _t_activity_fee || 0,
                                    is_template: _is_template
                                },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                location.reload();
                                            }
                                        });
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
                        }
                    });

                });

                //.hotel_only on change
                $(document).on('change', '#hotel_only', function() {
                    var hotel_only = $('#hotel_only').is(':checked') ? 1 : 0;
                    // confirm swal
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to change the hotel only status!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, change it!'
                    }).then((result) => {
                        if (result.isConfirmed) {

                            $.ajax({
                                url: '<?php echo base_url(); ?>tour/quotation/update_hotel_only/' + <?= $tour_id ?> + '/' + hotel_only,
                                type: 'POST',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        });
                                    } else { // if checked, uncheck
                                        $('#hotel_only').prop('checked', !hotel_only);
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
                        } else {
                            // if checked, uncheck
                            $('#hotel_only').prop('checked', !hotel_only);
                        }
                    });
                });

                //.transport_only on change
                $(document).on('change', '#transport_only', function() {
                    var transport_only = $('#transport_only').is(':checked') ? 1 : 0;
                    // confirm swal
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to change the transport only status!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, change it!'
                    }).then((result) => {
                        if (result.isConfirmed) {

                            $.ajax({
                                url: '<?php echo base_url(); ?>tour/quotation/update_transport_only/' + <?= $tour_id ?> + '/' + transport_only,
                                type: 'POST',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        });
                                    } else { // if checked, uncheck
                                        $('#transport_only').prop('checked', !transport_only);
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
                        } else {
                            // if checked, uncheck
                            $('#transport_only').prop('checked', !transport_only);
                        }
                    });
                });

                // .add_days btn
                $(document).on('click', '.add_days', function() {
                    var tour = <?= $tour_id ?>;
                    var days = $('.add_remove_days_txt').val();

                    // days is not numberic and less than 1
                    if (isNaN(days) || days < 1) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Invalid days!',
                        });
                        return;
                    }

                    // confirm
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to add " + days + " days to this tour!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, add it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?php echo base_url(); ?>tour/quotation/add_days/' + tour + '/' + days,
                                type: 'POST',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                get_tour_details();
                                            }
                                        });
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
                        }
                    });
                });

                // .remove_days btn
                $(document).on('click', '.remove_days', function() {
                    var tour = <?= $tour_id ?>;
                    var days = $('.add_remove_days_txt').val();

                    // days is not numberic and less than 1
                    if (isNaN(days) || days < 1) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Invalid days!',
                        });
                        return;
                    }

                    // confirm
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You want to remove " + days + " days from this tour!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, remove it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?php echo base_url(); ?>tour/quotation/remove_days/' + tour + '/' + days,
                                type: 'POST',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.status == 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: response.message,
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                get_tour_details();
                                            }
                                        });
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
                        }
                    });
                });

                // .new_room_type click
                $(document).on('click', '.new_room_type', function() {
                    var date = $(this).data('date');
                    // get .hotel value with same data-date
                    var hotel = $('.hotel[data-date="' + date + '"]').val();
                    window.open('<?= base_url('master_files/hotel_rate?hotel=') ?>' + hotel, '_blank');
                });

                // .set_dist_0 btn
                $(document).on('click', '.set_dist_0', function() {
                    $('.actual_km').val('0');
                    $('.extra_km').val('0');
                });

                // .edit_itinerary_btn
                $(document).on('click', '.edit_itinerary_btn', function() {
                    var id = $(this).data('id');
                    var description = $(this).data('description');
                    var area = $(this).data('area');
                    var tour_id = $(this).data('tour');
                    var row = $(this).data('row');
                    var date = $(this).data('date');

                    $('#ii_id').val(id);
                    $('#ii_tour').val(tour_id);
                    $('#ii_row').val(row);
                    $('#ii_date').val(date);
                    $('#edit_itinerary_desc').val(description);
                    $('#edit_itinerary_area').val(area);

                    $('#EditItineraryModal').modal('show');
                });

                // .edit_itinerary btn
                $(document).on('click', '.edit_itinerary', function() {
                    var id = $('#ii_id').val();
                    var description = $('#edit_itinerary_desc').val();
                    var area = $('#edit_itinerary_area').val();
                    var tour_id = $('#ii_tour').val();
                    var row = $('#ii_row').val();
                    var date = $('#ii_date').val();

                    if (description == '') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Missing Required Values!',
                        });
                    } else {
                        $.ajax({
                            type: "POST",
                            url: "<?php echo base_url(); ?>tour/quotation/edit_itenary",
                            data: {
                                id: id,
                                description: description,
                                area: area
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            load_itinerary(tour_id, row, date);
                                            $('#EditItineraryModal').modal('hide');
                                        }
                                    });
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
                    }
                });

            });
        <?php } ?>
    </script>

    <script>
        <?php if ($valid_tour) { ?>

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

            function trigger_to_save(date) {
                return;
                // set the 'btn-success' class to save_btn with data-date=date
                $('.save_btn[data-date="' + date + '"]').addClass('btn-success');
                // and remove the 'save_btn_bg' class
                $('.save_btn[data-date="' + date + '"]').removeClass('save_btn_bg');
            }

            function show_sgl_input(date) {
                $('.sgl_txt[data-date="' + date + '"]').addClass('d-none');
                $('.sgl[data-date="' + date + '"]').removeClass('d-none');
                $('.sgl[data-date="' + date + '"]').focus();
            }

            function show_dbl_input(date) {
                $('.dbl_txt[data-date="' + date + '"]').addClass('d-none');
                $('.dbl[data-date="' + date + '"]').removeClass('d-none');
                $('.dbl[data-date="' + date + '"]').focus();
            }

            function show_tpl_input(date) {
                $('.tpl_txt[data-date="' + date + '"]').addClass('d-none');
                $('.tpl[data-date="' + date + '"]').removeClass('d-none');
                $('.tpl[data-date="' + date + '"]').focus();
            }

            function show_sgl_txt(date) {
                var value = $('.sgl[data-date="' + date + '"]').val();
                $('.sgl_txt[data-date="' + date + '"]').text(get_txt_lbl_value(value));
                $('.sgl_txt[data-date="' + date + '"]').removeClass('d-none');
                $('.sgl[data-date="' + date + '"]').addClass('d-none');
            }

            function show_dbl_txt(date) {
                var value = $('.dbl[data-date="' + date + '"]').val();
                $('.dbl_txt[data-date="' + date + '"]').text(get_txt_lbl_value(value));
                $('.dbl_txt[data-date="' + date + '"]').removeClass('d-none');
                $('.dbl[data-date="' + date + '"]').addClass('d-none');
            }

            function show_tpl_txt(date) {
                var value = $('.tpl[data-date="' + date + '"]').val();
                $('.tpl_txt[data-date="' + date + '"]').text(get_txt_lbl_value(value));
                $('.tpl_txt[data-date="' + date + '"]').removeClass('d-none');
                $('.tpl[data-date="' + date + '"]').addClass('d-none');
            }

            function show_meal_code_txt(date) {
                var value = $('.meal_code[data-date="' + date + '"]').val();
                value = value == '0' ? '---' : value.toUpperCase();
                $('.meal_code_txt[data-date="' + date + '"]').text(value.toUpperCase());
                $('.meal_code_txt[data-date="' + date + '"]').removeClass('d-none');
                $('.meal_code[data-date="' + date + '"]').addClass('d-none');
            }

            function show_meal_code_select(date) {
                $('.meal_code_txt[data-date="' + date + '"]').addClass('d-none');
                $('.meal_code[data-date="' + date + '"]').removeClass('d-none');
                $('.meal_code[data-date="' + date + '"]').focus();
            }

            function show_room_type_txt(date) {
                var value = $('.room_type[data-date="' + date + '"] option:selected').text();
                value = value == 'Select...' ? '---' : value.toUpperCase();
                $('.room_type_txt[data-date="' + date + '"]').text(value.toUpperCase());
                $('.room_type_txt[data-date="' + date + '"]').removeClass('d-none');
                $('.room_type[data-date="' + date + '"]').addClass('d-none');
            }

            function show_room_type_select(date) {
                $('.room_type_txt[data-date="' + date + '"]').addClass('d-none');
                $('.room_type[data-date="' + date + '"]').removeClass('d-none');
                $('.room_type[data-date="' + date + '"]').focus();
            }

            function show_hotel_txt(date, hotel = '') {
                var value = $('.hotel[data-date="' + date + '"] option:selected').text();
                value = value == 'Select...' ? '---' : value;
                if (hotel != '') value = hotel;
                $('.hotel_txt[data-date="' + date + '"]').text(value);
                $('.hotel_txt[data-date="' + date + '"]').removeClass('d-none');
                $('.hotel_div[data-date="' + date + '"]').addClass('d-none');
            }

            function show_hotel_select(date) {
                $('.hotel_txt[data-date="' + date + '"]').addClass('d-none');
                $('.hotel_div[data-date="' + date + '"]').removeClass('d-none');
                $('.hotel[data-date="' + date + '"]').focus();
            }

            function get_txt_lbl_value(value) {
                if (value == '') {
                    return '---';
                }
                return value;
            }

            // .hotel on change, scroll the table to the right with animation
            $(document).on('change', '.hotel', function() {
                moveTableLeft();
            });

            // .meal_code on change, scroll the table to the right with animation
            $(document).on('change', '.meal_code', function() {
                moveTableLeft();
            });

            // .sgl on focus, scroll the table to the right with animation
            $(document).on('focus', '.sgl', function() {
                moveTableLeft();
            });

            // .dbl on focus, scroll the table to the right with animation
            $(document).on('focus', '.dbl', function() {
                moveTableLeft();
            });

            // .tpl on focus, scroll the table to the right with animation
            $(document).on('focus', '.tpl', function() {
                moveTableLeft();
            });

            function change_room_rate_based_on_room_count(room_type, date) {
                var room_type_rate = $('.' + room_type + '[data-date="' + date + '"]').val();
                var room_type_count = $('#' + room_type + '_count').val();

                if (room_type_rate == '' || isNaN(room_type_rate) || room_type_rate == '---') {
                    room_type_rate = 0;
                }

                if (room_type_count == '' || isNaN(room_type_count)) {
                    room_type_count = 0;
                }

                // var total_room_type_rate = room_type_rate * room_type_count;
                var total_room_type_rate = room_type_rate * 1;

                // console.log('room_type : ' + room_type);
                // console.log('room_type_rate : ' + room_type_count);
                // console.log('room_type_count : ' + room_type_rate);
                // console.log('total_room_type_rate : ' + total_room_type_rate);
                // console.log('==========================================');
                // console.log('total_room_type_rate', total_room_type_rate);

                $('.' + room_type + '[data-date="' + date + '"]').val(parseFloat(total_room_type_rate).toFixed(2));
            }

            // if .sgl_txt is clicked, show the .sgl input field
            $(document).on('click', '.sgl_txt', function() {
                var date = $(this).data('date');
                show_sgl_input(date);
            });

            // if .sgl enter key is pressed, set the value to the .sgl_txt and hide the .sgl input field
            $(document).on('keypress', '.sgl', function(e) {
                if (e.which == 13) {
                    var date = $(this).data('date');
                    change_room_rate_based_on_room_count('sgl', date);
                    show_sgl_txt(date);
                }
            });
            $(document).on('focusout', '.sgl', function(e) {
                var date = $(this).data('date');
                change_room_rate_based_on_room_count('sgl', date);
                show_sgl_txt(date);
            });
            $(document).on('change', '.sgl', function(e) {
                var date = $(this).data('date');
                change_room_rate_based_on_room_count('sgl', date);
                show_sgl_txt(date);
                trigger_to_save(date);
            });

            // if .dbl_txt is clicked, show the .dbl input field
            $(document).on('click', '.dbl_txt', function() {
                var date = $(this).data('date');
                show_dbl_input(date);
            });

            // if .dbl enter key is pressed, set the value to the .dbl_txt and hide the .dbl input field
            $(document).on('keypress', '.dbl', function(e) {
                if (e.which == 13) {
                    var date = $(this).data('date');
                    change_room_rate_based_on_room_count('dbl', date);
                    show_dbl_txt(date);
                }
            });
            $(document).on('focusout', '.dbl', function(e) {
                var date = $(this).data('date');
                change_room_rate_based_on_room_count('dbl', date);
                show_dbl_txt(date);
            });
            $(document).on('change', '.dbl', function(e) {
                var date = $(this).data('date');
                change_room_rate_based_on_room_count('dbl', date);
                show_dbl_txt(date);
                trigger_to_save(date);
            });

            // if .tpl_txt is clicked, show the .tpl input field
            $(document).on('click', '.tpl_txt', function() {
                var date = $(this).data('date');
                show_tpl_input(date);
            });

            // if .tpl enter key is pressed, set the value to the .tpl_txt and hide the .tpl input field
            $(document).on('keypress', '.tpl', function(e) {
                if (e.which == 13) {
                    var date = $(this).data('date');
                    change_room_rate_based_on_room_count('tpl', date);
                    show_tpl_txt(date);
                }
            });
            $(document).on('focusout', '.tpl', function(e) {
                var date = $(this).data('date');
                change_room_rate_based_on_room_count('tpl', date);
                show_tpl_txt(date);
            });
            $(document).on('change', '.tpl', function(e) {
                var date = $(this).data('date');
                change_room_rate_based_on_room_count('tpl', date);
                show_tpl_txt(date);
                trigger_to_save(date);
            });

            // .meal_code_txt click event
            $(document).on('click', '.meal_code_txt', function() {
                var date = $(this).data('date');
                show_meal_code_select(date);
            });

            // .meal_code value change event and focus out event
            $(document).on('change', '.meal_code', function() {
                var date = $(this).data('date');
                show_meal_code_txt(date);
                trigger_to_save(date);
            });
            $(document).on('focusout', '.meal_code', function() {
                var date = $(this).data('date');
                show_meal_code_txt(date);
            });

            // .room_type_txt click event
            $(document).on('click', '.room_type_txt', function() {
                var date = $(this).data('date');
                show_room_type_select(date);
            });

            // .room_type value change event and focus out event
            $(document).on('change', '.room_type', function() {
                var date = $(this).data('date');
                show_room_type_txt(date);
                trigger_to_save(date);
            });
            $(document).on('focusout', '.room_type', function() {
                var date = $(this).data('date');
                show_room_type_txt(date);
            });

            // .hotel_txt click event
            $(document).on('click', '.hotel_txt', function() {
                var date = $(this).data('date');
                show_hotel_select(date);
            });

            // .hotel value change event and focus out event
            $(document).on('change', '.hotel', function() {
                var date = $(this).data('date');
                show_hotel_txt(date);

                trigger_to_save(date);
            });
        <?php } ?>
    </script>

</body>

</html>