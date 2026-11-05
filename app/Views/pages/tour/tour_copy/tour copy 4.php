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
                                            <h4 class="p-title">Tour</h4>
                                        </div>
                                        <div>
                                            <!-- <button id="finalize_btn" class="btn btn-success waves-effect waves-light fw-bold"> Finalize
                                            </button> -->
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-8">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                        <fieldset>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-4">
                                                                    <label class="form-label" for="start_date">Start
                                                                        Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="start_date" name="start_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?= $start_date ?>" />
                                                                </div>
                                                                <div class="mb-3 col-md-4">
                                                                    <label class="form-label" for="end_date">End Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="end_date" name="end_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?= $end_date ?>" />
                                                                </div>
                                                                <div class="mb-3 col-md-4" style="padding-top: 27px;">
                                                                    <button type="submit" class="btn btn-success w-100px me-5px">CREATE
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </fieldset>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" class="form-control" value="<?= $quotation_id ?>" id="tid">
                                        <input type="hidden" class="form-control" value="<?= $start_date ?>" id="tstartdate">
                                        <input type="hidden" class="form-control" value="<?= $end_date ?>" id="tenddate">
                                        <div class="col-md-12" id="table_detail" style="display: none;">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover align-middle text-center" id="date_table" data-tourid="">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Date</th>
                                                                    <th>Transport</th>
                                                                    <th>Details</th>
                                                                    <th>Remove</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>

                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12" id="table_detail_report" style="display:none">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table class="table table-hover align-middle text-center table-bordered" id="date_table_report" data-tourid="">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th rowspan="2">#</th>
                                                                    <th rowspan="2">Date</th>
                                                                    <th colspan="3">Transport</th>
                                                                    <th colspan="3">Details</th>
                                                                </tr>
                                                                <tr>
                                                                    <th>Transfer</th>
                                                                    <th>Milage</th>
                                                                    <th>Edit</th>
                                                                    <th>Hodel</th>
                                                                    <th>Hodel Code</th>
                                                                    <th>Edit</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>

                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 " id="transport_quotation" style="display:none">
                                        <div class="card p-4">
                                            <form action="javascript:void(0);" id="transport_details">
                                                <input type="hidden" name="trans_tour_id" id="trans_tour_id">
                                                <input type="hidden" name="trans_row_id" id="trans_row_id">
                                                <input type="hidden" name="trans_date" id="trans_date">
                                                <div class="row g-3">
                                                    <p id="modal-date-info1"></p>
                                                    <div class="col-md-12">
                                                        <div class="row">
                                                            <label class="form-label" for="code">Distance Type <font color="#FF0000"><strong>*</strong></font></label>
                                                            <div class="mt-4 mt-lg-0">
                                                                <div class="col-lg-12">
                                                                    <div class="d-flex mt-lg-0">
                                                                        <div class="form-check form-check-inline">
                                                                            <input class="form-check-input" type="radio" name="distance_type" id="manual" value="1" checked>
                                                                            <label class="form-check-label" for="inlineRadio1">Manual</label>
                                                                        </div>
                                                                        <div class="form-check form-check-inline">
                                                                            <input class="form-check-input" type="radio" name="distance_type" id="google" value="2">
                                                                            <label class="form-check-label" for="inlineRadio2">Google</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <br>
                                                        <br>
                                                        <div class="col-md-12" id="trans_code">
                                                            <div>
                                                                <label class="form-label" for="code">Transfer Code <font color="#FF0000"><strong>*</strong></font></label>
                                                                <select name="transfer_code" id="transfer_code" class="js-example-basic-single form-control" style="width: 100%;" required onchange="transferChanged(this.value);">
                                                                    <option value="0">Select a Transfer...</option>
                                                                    <?php
                                                                    foreach ($transfer_code as $id) {
                                                                        echo '<option value="' . $id['id'] . '" >' . $id['transfer_code'] . " - " . $id['transfer'] . '</option>';
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <!--end col-->

                                                        <div class="col-md-12" id="trans">
                                                            <div>
                                                                <label class="form-label" for="code">Transfer <font color="#FF0000"><strong>*</strong></font></label>
                                                                <input type="text" name="transfer" id="transfer" class="form-control" required style="width: 100%;">
                                                            </div>
                                                        </div>

                                                        <div class="col-md-12" id="manudis">
                                                            <div>
                                                                <label class="form-label" for="date">Distance<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="distance" name="distance" required />
                                                            </div>
                                                        </div>


                                                        <div id="map" class="rounded" style="height: 400px; width: 100%;"></div>
                                                        <div id="map_div">
                                                            <div class="my-3">
                                                                <div class="mb-3">
                                                                    <label for="pick_location" class="form-label">From
                                                                        Location</label>
                                                                    <div class="input-group mb-3">
                                                                        <input type="text" class="form-control text-dark fw-bold" id="pick_location" name="pick_location" placeholder="Search">
                                                                        <button class="btn btn-dark" type="button" id="locate-me"><i class="bi bi-geo-alt-fill"></i></button>
                                                                    </div>
                                                                    <input type="hidden" id="pick_lat">
                                                                    <input type="hidden" id="pick_long">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="drop_location" class="form-label">To
                                                                        Location</label>
                                                                    <input id="drop_location" name="drop_location" type="text" class="form-control text-dark fw-bold" placeholder="Search">
                                                                    <input type="hidden" id="drop_lat">
                                                                    <input type="hidden" id="drop_long">
                                                                </div>
                                                            </div>
                                                            <!-- <div class="alert alert-info" role="alert">
                                                                <div class="d-flex flex-column">
                                                                    <span class="fw-bold text-dark">Distance</span>
                                                                    <span id="gdistance">---</span>
                                                                    <span class="fw-bold text-dark mt-3">Average Price</span>
                                                                <span id="gprice">---</span>
                                                                </div>
                                                            </div> -->

                                                            <div class="col-md-12" id="gmanudis">
                                                                <div>
                                                                    <label class="form-label" for="gdistance">Distance<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="gdistance" name="gdistance" placeholder="" required readonly />
                                                                </div>
                                                            </div>

                                                        </div>


                                                        <!--end col-->
                                                        <div class="col-lg-12">
                                                            <div class="hstack gap-2 justify-content-end" style="padding-top : 10px;">
                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="close_card">
                                                                    Close
                                                                </button>
                                                                <button type="submit" class="btn btn-primary">Submit</button>
                                                            </div>
                                                        </div>
                                                        <!--end col-->
                                                    </div>

                                                    <!--end row-->
                                            </form>
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


    <!-- Modal -->
    <div class="modal fade" id="dateModal" tabindex="-1" aria-labelledby="exampleModalgridLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalgridLabel">Quotation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0);" id="quotation_details">
                        <input type="hidden" name="tour_id" id="tour_id">
                        <input type="hidden" name="row_id" id="row_id">
                        <div class="row g-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p id="modal-date-info"></p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Destination : &nbsp;<code><b><span id="final_destination"></span></b></code></h5>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="search_location">Search Destination</label>
                                        <select name="search_location" id="search_location" class="js-example-basic-single_m form-control" style="width: 100%;">
                                            <option value="">Select a Destination...</option>
                                            <?php
                                            foreach ($hotel_location as $id) {
                                                echo '<option value="' . $id['Address'] . '" >' . $id['Address'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <!-- <input class="form-control js-example-basic-single_m" type="text" id="search_location" name="search_location" placeholder=""  /> -->
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3 hstack gap-2 " style="padding-top: 28px;">
                                        <button type="button" class="btn btn-success search_loc" id="search_loc">
                                            Search All
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="hotel">Hotel <font color="#FF0000"><strong>*</strong></font></label>
                                    <select name="hotel" id="hotel" class="js-example-basic-single_m form-control" style="width: 100%;" required onchange="hotelChanged(this.value);">
                                        <option value="0">Select a Hotel...</option>
                                        <?php
                                        foreach ($hotel as $id) {
                                            echo '<option value="' . $id['Sup_Acc_No'] . '" >' . $id['Name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="hotel_code">Hotel Code <font color="#FF0000"><strong>*</strong></font></label>
                                    <select name="hotel_code" id="hotel_code" class="js-example-basic-single_m form-control" style="width: 100%;" required onchange="hotelCodeChanged(this.value);">
                                        <option value=""></option>

                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="date">Tour Date<font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control" type="text" id="date" name="date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" readonly />
                                </div>
                            </div>
                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="roomtype">Room Type<font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control" type="text" id="room_type" name="room_type" placeholder="" required readonly />
                                    <input class="form-control" type="hidden" id="room_type_id" name="room_type_id" placeholder="" />
                                </div>
                            </div>
                            <div class="col-xxl-6">
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
                            <!--end col-->
                            <!--end col-->
                            <div class="col-xxl-6">
                                <label class="form-label" for="sgl">SGL</label>
                                <input class="form-control" type="text" id="sgl" name="sgl" placeholder="" />
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6">
                                <label class="form-label" for="dbl">DBL</label>
                                <input class="form-control" type="text" id="dbl" name="dbl" placeholder="" />
                            </div>
                            <div class="col-xxl-6">
                                <label class="form-label" for="tpl">TPL</label>
                                <input class="form-control" type="text" id="tpl" name="tpl" placeholder="" />
                            </div>

                            <!--end col-->
                            <div class="col-lg-12">
                                <div class="hstack gap-2 justify-content-end" style="padding-top: 10px;">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                            <!--end col-->
                        </div>
                        <!--end row-->
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="dateModal_edit" tabindex="-1" aria-labelledby="exampleModalgridLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalgridLabel">Quotation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0);" id="quotation_details">
                        <input type="hidden" name="tour_id" id="tour_id">
                        <input type="hidden" name="row_id" id="row_id">
                        <div class="row g-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <p id="modal-date-info"></p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Destination : &nbsp;<code><b><span id="final_destination"></span></b></code></h5>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="search_location">Search Destination</label>
                                        <select name="search_location" id="search_location" class="js-example-basic-single_m form-control" style="width: 100%;">
                                            <option value="">Select a Destination...</option>
                                            <?php
                                            foreach ($hotel_location as $id) {
                                                echo '<option value="' . $id['Address'] . '" >' . $id['Address'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <!-- <input class="form-control js-example-basic-single_m" type="text" id="search_location" name="search_location" placeholder=""  /> -->
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3 hstack gap-2 " style="padding-top: 28px;">
                                        <button type="button" class="btn btn-success search_loc" id="search_loc">
                                            Search All
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="hotel">Hotel <font color="#FF0000"><strong>*</strong></font></label>
                                    <select name="hotel" id="hotel" class="js-example-basic-single_m form-control" style="width: 100%;" required onchange="hotelChanged(this.value);">
                                        <option value="0">Select a Hotel...</option>
                                        <?php
                                        foreach ($hotel as $id) {
                                            echo '<option value="' . $id['Sup_Acc_No'] . '" >' . $id['Name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="hotel_code">Hotel Code <font color="#FF0000"><strong>*</strong></font></label>
                                    <select name="hotel_code" id="hotel_code" class="js-example-basic-single_m form-control" style="width: 100%;" required onchange="hotelCodeChanged(this.value);">
                                        <option value=""></option>

                                    </select>
                                </div>
                            </div>
                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="date">Tour Date<font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control" type="text" id="date" name="date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" readonly />
                                </div>
                            </div>
                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="roomtype">Room Type<font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control" type="text" id="room_type" name="room_type" placeholder="" required readonly />
                                    <input class="form-control" type="hidden" id="room_type_id" name="room_type_id" placeholder="" />
                                </div>
                            </div>
                            <div class="col-xxl-6">
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
                            <!--end col-->
                            <!--end col-->
                            <div class="col-xxl-6">
                                <label class="form-label" for="sgl">SGL</label>
                                <input class="form-control" type="text" id="sgl" name="sgl" placeholder="" />
                            </div>
                            <!--end col-->
                            <div class="col-xxl-6">
                                <label class="form-label" for="dbl">DBL</label>
                                <input class="form-control" type="text" id="dbl" name="dbl" placeholder="" />
                            </div>
                            <div class="col-xxl-6">
                                <label class="form-label" for="tpl">TPL</label>
                                <input class="form-control" type="text" id="tpl" name="tpl" placeholder="" />
                            </div>

                            <!--end col-->
                            <div class="col-lg-12">
                                <div class="hstack gap-2 justify-content-end" style="padding-top: 10px;">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                            <!--end col-->
                        </div>
                        <!--end row-->
                    </form>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEg8Qki_788QHwsbPoh8N2EW_TiWrb0Rg&loading=async&libraries=places&callback=myMap"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let pickLat = 0,
                pickLng = 0,
                dropLat = 0,
                dropLng = 0;
            let map, directionsService, directionsRenderer;

            function initMap() {
                directionsService = new google.maps.DirectionsService();
                directionsRenderer = new google.maps.DirectionsRenderer();
                map = new google.maps.Map(document.getElementById('map'), {
                    center: {
                        lat: 7.8731,
                        lng: 80.7718
                    },
                    zoom: 8
                });
                directionsRenderer.setMap(map);

                const pickInput = document.getElementById('pick_location');
                const dropInput = document.getElementById('drop_location');

                // Create the autocomplete objects
                const pickAutocomplete = new google.maps.places.Autocomplete(pickInput);
                const dropAutocomplete = new google.maps.places.Autocomplete(dropInput);

                // Listen for place changes on both pick and drop inputs
                pickAutocomplete.addListener('place_changed', function() {
                    handlePlaceChange(pickAutocomplete, pickInput, 'pick_lat', 'pick_long');
                });

                dropAutocomplete.addListener('place_changed', function() {
                    handlePlaceChange(dropAutocomplete, dropInput, 'drop_lat', 'drop_long');
                });
            }

            function handlePlaceChange(autocomplete, input, latId, lngId) {
                const place = autocomplete.getPlace();
                if (place.geometry) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    document.getElementById(latId).value = lat;
                    document.getElementById(lngId).value = lng;

                    // Update the pickLat, pickLng, dropLat, and dropLng variables
                    if (latId === 'pick_lat') {
                        pickLat = lat;
                        pickLng = lng;
                    } else {
                        dropLat = lat;
                        dropLng = lng;
                    }

                    calculateAndDisplayRoute();
                }
            }

            function calculateAndDisplayRoute() {

                console.log('calculateAndDisplayRoute()');

                if (pickLat && pickLng && dropLat && dropLng) {

                    console.log('Inside if condition')

                    const origin = new google.maps.LatLng(pickLat, pickLng);
                    const destination = new google.maps.LatLng(dropLat, dropLng);

                    console.log("origin: ");
                    console.log(origin);

                    directionsService.route({
                        origin: origin,
                        destination: destination,
                        travelMode: google.maps.TravelMode.DRIVING
                    }, (response, status) => {

                        console.log(response);

                        if (status === 'OK') {
                            directionsRenderer.setDirections(response);
                            const distance = response.routes[0].legs[0].distance.text;
                            document.getElementById('gdistance').value = distance; // Update distance here
                            // calculatePrice(distance);
                            // document.getElementById('findTaxi').disabled = false;
                        } else {
                            console.error('Directions request failed due to ' + status);
                        }
                    });
                }
            }

            // function calculatePrice(distance) {
            //     let price = 0;
            //     const distanceInKm = parseFloat(distance.split(' ')[0]);
            //     if (distanceInKm <= 5) {
            //         price = distanceInKm * 100;
            //     } else if (distanceInKm <= 10) {
            //         price = (5 * 100) + ((distanceInKm - 5) * 80);
            //     } else {
            //         price = (5 * 100) + (5 * 80) + ((distanceInKm - 10) * 60);
            //     }
            //     document.getElementById('gprice').innerText = 'LKR ' + price.toFixed(2);
            // }

            initMap();
        });
    </script>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        $('.js-example-basic-single_m').select2({
            placeholder: " Select",
            allowClear: true,
            dropdownParent: $("#dateModal")
        });


        $(document).ready(function() {
            var tour = $("#tid").val();
            var tour_start = $("#tstartdate").val();
            var tour_end = $("#tenddate").val();
            if (tour != '' && tour_start != '' && tour_end != '') {
                creatTable(tour, tour_start, tour_end);
                create_details_table(tour);
            }

        });

        function creatTable(Tour_Id, start_date, end_date) {
            $('#date_table').attr('data-tourid', Tour_Id);

            // Clear the table before adding new data
            $('#date_table tbody').empty();

            var quotationDetails = <?php echo isset($quotation_details) ? json_encode($quotation_details) : json_encode(''); ?>;
            var q_det = {};

            // Ensure quotationDetails is an array if it's not empty
            if (Array.isArray(quotationDetails)) {
                quotationDetails.forEach(function(detail) {
                    var quotation_id = detail.quotation_id;

                    // If quotation_id does not exist in q_det, create an empty array
                    if (!q_det[quotation_id]) {
                        q_det[quotation_id] = [];
                    }

                    // Push an object with tour_date, trans_flag, and det_flag to the array
                    q_det[quotation_id].push({
                        tour_date: detail.tour_date,
                        trans_flag: detail.trans_flag,
                        det_flag: detail.det_flag
                    });
                });
            }

            console.log(q_det);

            // Parse dates
            var startDate = new Date(start_date);
            var endDate = new Date(end_date);

            // Calculate date difference
            var currentDate = startDate;
            var index = 1;
            while (currentDate <= endDate) {
                $("#table_detail").removeAttr('style');
                var formattedDate = currentDate.toISOString().split('T')[0];
                var row = '<tr data-dateid="Tour-' + Tour_Id + '-' + index + '" data-realdate="' + formattedDate + '">';

                row += '<td>' + index + '</td>';
                row += '<td>' + formattedDate + '</td>';

                var transButtonShown = false;
                var detButtonShown = false;

                if (q_det) {
                    for (var quotation_id in q_det) {
                        q_det[quotation_id].forEach(value => {
                            if (value.tour_date === formattedDate) {
                                transButtonShown = true;
                                detButtonShown = true;

                                if (value.trans_flag == 1) {
                                    row += '<td><img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px"></td>';
                                } else {
                                    row += '<td><button type="button" class="btn btn-sm btn-info add-transport" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="1"> <i class="bi bi-plus"></i></button></td>';
                                }

                                if (value.det_flag == 1) {
                                    row += '<td><img src="<?php echo base_url(); ?>assets/images/ok.png" alt="..." width="20px"></td>';
                                } else {
                                    row += '<td><button type="button" class="btn btn-sm btn-success add-info" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="2"> <i class="bi bi-plus"></i></button></td>';
                                }
                            }
                        });
                    }
                }

                // Add buttons if no matching tour_date was found
                if (!transButtonShown) {
                    row += '<td><button type="button" class="btn btn-sm btn-info add-transport" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="1"> <i class="bi bi-plus"></i></button></td>';
                }
                if (!detButtonShown) {
                    row += '<td><button type="button" class="btn btn-sm btn-success add-info" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="2"> <i class="bi bi-plus"></i></button></td>';
                }

                if (index === 1 || currentDate.getTime() === endDate.getTime()) {
                    row += '<td>';
                    row += '<button type="button" class="btn btn-sm btn-danger btn-remove" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-start="' + start_date + '" data-end="' + end_date + '">';
                    row += '<i class="bi bi-trash"></i>';
                    row += '</button>';
                    row += '</td>';
                } else {
                    row += '<td></td>';
                }
                row += '</tr>';

                $('#date_table tbody').append(row);

                currentDate.setDate(currentDate.getDate() + 1);
                index++;
            }


            var tfoot;
            tfoot += '<tfoot>';
            tfoot += '<tr>';
            tfoot += '<td></td>';
            tfoot += '<td></td>';
            tfoot += '<td></td>';
            tfoot += '<td></td>';
            tfoot += '<td>';
            tfoot += '<button type="button"  class="btn btn-sm btn-warning btn-add" data-tour="' + Tour_Id + '" data-start="' + start_date + '" data-end="' + end_date + '"">';
            tfoot += '<i class="bi bi-calendar-plus"></i>';
            tfoot += '</button>';
            tfoot += '</td>';
            tfoot += '</tr>';
            tfoot += '</tfoot>';

            $('#date_table').append(tfoot);

            // Attach click event to the buttons (after table is fully created)
            $('.add-info').on('click', function() {
                var date = $(this).data('date');
                var tour = $(this).data('tour');
                var row = $(this).data('row');
                $('#modal-date-info').text('Selected date: ' + date);
                $('#date').val(date);
                $("#tour_id").val(tour);
                $("#row_id").val(row);
                $('#dateModal').modal('show');

                // Call functions related to adding info (if needed)
                searchHotel(tour, row);
                searchHotelCode(tour, row);
            });

            $('.add-transport').on('click', function() {
                var date = $(this).data('date');
                var tour = $(this).data('tour');
                var row = $(this).data('row');
                $('#modal-date-info1').text('Selected date: ' + date);
                $("#trans_date").val(date);
                $("#trans_tour_id").val(tour);
                $("#trans_row_id").val(row);

                $("#transport_quotation").show();
            });

            $('.edit-transport').on('click', function() {
                // var date = $(this).data('date');
                // var tour = $(this).data('tour');
                // var row = $(this).data('row');
                // $('#modal-date-info1').text('Selected date: ' + date);
                // $("#trans_date").val(date);
                // $("#trans_tour_id").val(tour);
                // $("#trans_row_id").val(row);

                $("#transport_quotation").show();
            });
            $('.edit-info').on('click', function() {
                alert();
                var date = $(this).data('date');
                var tour = $(this).data('tour');
                var row = $(this).data('row');
                $('#modal-date-info').text('Selected date: ' + date);
                $('#date').val(date);
                $("#tour_id").val(tour);
                $("#row_id").val(row);
                $('#dateModal_edit').modal('show');

                // Call functions related to adding info (if needed)
                searchHotel(tour, row);
                searchHotelCode(tour, row);
            });

            $(".btn-remove").on('click', function() {
                var date = $(this).data('date');
                var tour = $(this).data('tour');
                var start_date = $(this).data('start');
                var end_date = $(this).data('end');
                var row = $(this).data('row');

                var start, end;

                if (row === 1) {

                    var date_id = 'Tour-' + tour + '-' + (row + 1);
                    start = $('#date_table').find('tr[data-dateid="' + date_id + '"]').data('realdate');

                    end = end_date;
                } else {

                    var date_id = 'Tour-' + tour + '-' + (row - 1);
                    end = $('#date_table').find('tr[data-dateid="' + date_id + '"]').data('realdate');
                    start = start_date;

                }
                window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + start + "&end=" + end;

            });

            $(".btn-add").on('click', function() {
                var tour = $(this).data('tour');
                var start_date = $(this).data('start');
                var end_date = $(this).data('end');

                var endDate = new Date(end_date);
                endDate.setDate(endDate.getDate() + 1);

                var formattedEndDate = endDate.toISOString().split('T')[0];

                window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + start_date + "&end=" + formattedEndDate;
            });
        }


        var hotelCodeChangedCalled = false;

        function resetFormSelections() {

            var hotelCodeChangedCalled = false;
            var initialHotelOptions = $("#hotel").html();
            $("#hotel").html(initialHotelOptions);


            $("#hotel_code").empty().append('<option value=""></option>');
            $("#meal_code").prop('selectedIndex', 0);

        }

        function hotelChanged(selectedval) {

            console.log("hotel_id : " + selectedval);

            if (hotelCodeChangedCalled) {
                return;
            }
            console.log("correct");

            var id = selectedval;

            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/show_hotel/' + id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    // $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    // $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {

                    $('#hotel_code').empty();

                    // Append empty option
                    $('#hotel_code').append('<option value="">Select a hotel code</option>');

                    // Append new options
                    $.each(response, function(index, value) {
                        $('#hotel_code').append(
                            '<option value="' + value.hotel_code + '">' + value.hotel_code + '</option>'
                        );
                    });

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

        function hotelCodeChanged(selectedHotel) {

            var id = selectedHotel;

            hotelCodeChangedCalled = true;
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/show/' + id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    // $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    // $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {

                        $('#hotel').css('background-color', '#f0f0f0');
                        $('#hotel').val(response.data.Sup_Acc_No).trigger('change');
                        // $('#hotel').prop('disabled', true);


                        // $('#hotel_acc').val(response.data.Sup_Acc_No);
                        $('#room_type').val(response.data.rtype);
                        $('#room_type_id').val(response.data.id);

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

        $(".btn-close, .btn-light").click(function() {

            $('#quotation_details')[0].reset();
            resetFormSelections();
            $('#dateModal').modal('hide');
        });

        // Document Ready
        // $(document).ready(function() {

        $("#quotation_details").submit(function(e) {
            e.preventDefault();
            var hotel = $("#hotel").val();

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/insertQuatation',
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                beforeSend: function() {
                    // $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    // $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then((result) => {
                            var t_id = response.tour_id;
                            var r_id = response.row_id;

                            var button = $('button[data-tour="' + t_id + '"][data-row="' + r_id + '"][data-type="2"]');

                            if (button.length > 0) {
                                button.parent().html('<i class="bi bi-check-lg"></i>');
                                // disable the button
                                button.prop('disabled', true);
                                create_details_table(t_id);
                            }
                        });


                        $('#quotation_details')[0].reset();
                        resetFormSelections();
                        $('#dateModal').modal('hide');


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
        });
        // });

        function transferChanged(selectedTransfer) {

            var id = selectedTransfer;
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/show_transfer/' + id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    // $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    // $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {

                        $('#transfer').val(response.data.transfer);
                        $('#distance').val(response.data.milage);

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

        function mealCodeChanged(mplan) {

            var id = mplan;
            var hotel_code = $('#hotel_code').val();
            var room_type = $('#room_type_id').val();
            // alert();
            // if any of above is empty, return
            if (hotel_code == 0 || room_type == 0) {
                return;
            }
            $('#loadingModal').modal('show');
            // ajax call
            $.ajax({
                url: '<?php echo base_url('tour/tour/get_meal_prices') ?>',
                type: 'GET',
                data: {
                    room_type: room_type,
                    hotel_code: hotel_code,
                    meal_plan: id
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


        $(document).ready(function() {

            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();

                var start_date = $('#start_date').val();
                var end_date = $('#end_date').val();
                var Tour_Id = 0;

                // if start_date, end_date is empty, return
                if (start_date == '' || end_date == '') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please fill all the required fields',
                    });
                    return;
                } else {
                    $.ajax({
                        url: '<?php echo base_url(); ?>tour/tour/getTourId',
                        type: 'GET',
                        dataType: 'json',
                        beforeSend: function() {
                            // $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                            // $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                        },
                        success: function(response) {
                            if (response.status == 200) {


                                var Tour_Id = response.data;
                                window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + Tour_Id + "&start=" + start_date + "&end=" + end_date;

                                // populateListInTable();

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

        function searchHotel(selecttour, selectrow) {

            var tour = selecttour;
            var row = selectrow;

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/search_hotel/' + tour + '/' + row,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    // $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    // $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {

                    $("#search_location").val(response.search_location).trigger('change');
                    $("#final_destination").text(response.search_location);


                    $('#hotel').empty();

                    $('#hotel').append('<option value="">Select a hotel</option>');

                    if (response.hotels && Array.isArray(response.hotels)) {
                        $.each(response.hotels, function(index, value) {
                            if (value && value.Sup_Acc_No && value.Name) {
                                $('#hotel').append(
                                    '<option value="' + value.Sup_Acc_No + '">' + value.Name + '</option>'
                                );
                            } else {
                                console.warn('Invalid entry in response:', value);
                            }
                        });
                    } else {
                        console.error('Response is not an array:', response);
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

        function searchHotelCode(selecttour, selectrow) {
            var tour = selecttour;
            var row = selectrow;

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/search_hotel_code/' + tour + '/' + row,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    // Add any loading indicators if needed
                },
                success: function(response) {
                    $('#hotel_code').empty().append('<option value="">Select a hotel code</option>');

                    if (Array.isArray(response)) {
                        response.forEach(function(value) {
                            if (value && value.hotel_code) {
                                $('#hotel_code').append(
                                    '<option value="' + value.hotel_code + '">' + value.hotel_code + '</option>'
                                );
                            } else {
                                console.warn('Invalid entry in response:', value);
                            }
                        });
                    } else {
                        console.error('Response is not an array:', response);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error fetching hotel codes:', textStatus, errorThrown);
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                }
            });
        }

        // $(document).ready(function() {
        var initialTransCode = $("#transfer_code").html();
        $("#transport_details").submit(function(e) {
            e.preventDefault();

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/insertTransportQuatation',
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                beforeSend: function() {
                    // $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    // $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then((result) => {
                            var t_id = response.tour_id;
                            var r_id = response.row_id;

                            var button = $('button[data-tour="' + t_id + '"][data-row="' + r_id + '"][data-type="1"]');

                            // console.log(button);
                            // console.log(r_id);

                            if (button.length > 0) {
                                button.parent().html('<i class="bi bi-check-lg"></i>');
                                // disable the button
                                button.prop('disabled', true);
                                create_details_table(t_id);
                            }
                        });
                        $("#transport_details")[0].reset();
                        $("#transfer_code").html(initialTransCode);
                        toggleElements();
                        $("#transport_quotation").hide();

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
        });

        $("#close_card").click(function(e) {
            $("#transport_details")[0].reset();
            $("#transfer_code").html(initialTransCode);
            toggleElements();
            $("#transport_quotation").hide();

        });
        // });

        function create_details_table(t_id) {
            var to_id = t_id;

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/get_tour_data/' + to_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    // $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    // $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    $("#table_detail_report").removeAttr('style');
                    $('#table_detail_report tbody').empty();

                    var row = '';
                    response.data.forEach(function(item, index) {
                        var d_type = item.distance_type;
                        var transf = '';
                        var dis = '';

                        if (d_type == 1) {
                            transf = item.transfer;
                            dis = item.distance;
                        } else {
                            transf = item.from_location + " To " + item.to_location;
                            dis = item.gdistance;
                        }

                        row = '<tr>';
                        row += '<td>' + (index + 1) + '</td>';
                        row += '<td>' + item.tour_date + '</td>';
                        row += '<td>' + transf + '</td>';
                        row += '<td>' + dis + '</td>';
                        row += '<td><button type="button" class="btn btn-sm btn-info edit-transport" data-date="' + item.tour_date + '" data-tour="' + item.quotation_id + '" data-row="' + item.quotation_day + '" data-type="1"> <i class="bi bi-pen"></i></button></td>';
                        row += '<td>' + (item.hotel_name ? item.hotel_name : '') + '</td>';
                        row += '<td>' + (item.hotel_code ? item.hotel_code : '') + '</td>';
                        row += '<td><button type="button" class="btn btn-sm btn-success edit-info" data-date="' + item.tour_date + '" data-tour="' + item.quotation_id + '" data-row="' + item.quotation_day + '" data-type="2"> <i class="bi bi-pen"></i></button></td>';
                        row += '</tr>';
                        $('#table_detail_report tbody').append(row);
                    });

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
        // Function to show/hide elements based on distance type
        function toggleElements() {
            var distanceType = document.querySelector('input[name="distance_type"]:checked').value;
            if (distanceType == 2) {
                document.getElementById('trans_code').style.display = 'none';
                document.getElementById('trans').style.display = 'none';
                document.getElementById('manudis').style.display = 'none';
                document.getElementById('map_div').style.display = 'block';
                document.getElementById('map').style.display = 'block';
                document.getElementById('gmanudis').style.display = 'block';
                // document.getElementById('distance').classList.remove('form-control');
                // document.getElementById('transfer').classList.remove('form-control');
                document.getElementById('distance').removeAttribute('required');
                document.getElementById('transfer').removeAttribute('required');
                document.getElementById('transfer_code').removeAttribute('required');

            } else if (distanceType == 1) {
                document.getElementById('trans_code').style.display = 'block';
                document.getElementById('trans').style.display = 'block';
                document.getElementById('manudis').style.display = 'block';
                document.getElementById('map_div').style.display = 'none';
                document.getElementById('map').style.display = 'none';
                document.getElementById('gmanudis').style.display = 'none';
            }
        }

        // Event listener for distance type radio buttons
        var distanceTypeRadios = document.querySelectorAll('input[name="distance_type"]');
        distanceTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', toggleElements);
        });

        // Initial call to toggleElements to set initial state
        toggleElements();



        $("#search_loc").on('click', function(e) {
            e.preventDefault();

            var search_location = $("#search_location").val();


            $.ajax({
                url: '<?php echo base_url('tour/tour/get_hotel_details') ?>',
                type: 'GET',
                data: {
                    search_location: search_location,
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {

                        $('#hotel').empty();

                        $('#hotel').append('<option value="">Select a hotel</option>');

                        if (data.data.hotels && Array.isArray(data.data.hotels)) {
                            $.each(data.data.hotels, function(index, value) {
                                if (value && value.Sup_Acc_No && value.Name) {
                                    $('#hotel').append(
                                        '<option value="' + value.Sup_Acc_No + '">' + value.Name + '</option>'
                                    );
                                } else {
                                    console.warn('Invalid entry in response:', value);
                                }
                            });
                        } else {
                            console.error('Response is not an array:', data.data);
                        }

                        $('#hotel_code').empty();

                        // Append empty option
                        $('#hotel_code').append('<option value="">Select a hotel code</option>');

                        // Append new options
                        $.each(data.data.hotel_code, function(index, value) {
                            $('#hotel_code').append(
                                '<option value="' + value.hotel_code + '">' + value.hotel_code + '</option>'
                            );
                        });

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Successfully, hotels and hotel codes found near to ' + capitalizeFirstLetter(search_location) + ' .',
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message ? data.message : 'An error occured while processing the request',
                        });
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

        function capitalizeFirstLetter(str) {
            if (str.length === 0) {
                return str; // Return the empty string if input is empty
            }
            return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
        }
    </script>

</body>

</html>