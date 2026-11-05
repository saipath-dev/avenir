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
                                    <div class="col-md-12">
                                        <div class="col-md-12" id="consignee_form">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                        <fieldset>
                                                            <?php if ($rowCount) { ?>
                                                                <div class="row">
                                                                    <div class="mb-3 col-md-6">
                                                                        <label class="form-label" for="pendings">Pending Quotations</label>
                                                                        <select name="pendings" id="pendings" class="js-example-basic-single form-control" onchange="load_data(this.value)">
                                                                            <option value=""></option>
                                                                            <?php
                                                                            foreach ($pending_quotations as $id) {
                                                                                echo '<option value="' . $id['tour_id'] . '">' . $id['consignee'] . '(' . $id['start_date'] . ' -> ' . $id['end_date'] . ')' . '</option>';
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="consignee">Consignee Name<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="consignee" name="consignee" required />
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="email">Email<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="email" name="email" />
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="country">Country<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="country" id="country" class="js-example-basic-single form-control" style="width: 100%;" required>
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
                                                                        <select name="t_code_1" id="t_code_1" class="form-control" required>
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
                                                                        <input type="text" class="form-control" placeholder="" id="tel_mobile" name="tel_mobile" value="" required />
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="vehicle">Vehicle<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <!-- <input class="form-control" type="text" id="vehicle" name="vehicle" placeholder="" /> -->
                                                                    <select name="vehicle" id="vehicle" class="js-example-basic-single form-control" required>
                                                                        <option value=""></option>
                                                                        <?php
                                                                        foreach ($vehicles as $id) {
                                                                            foreach ($vehicles as $id) {
                                                                                echo '<option value="' . $id['id'] . '" data-rate="' . $id['km_rate'] . '">' . $id['type'] . '</option>';
                                                                            }
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="passenger_count">No of Passengers<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="passenger_count" name="passenger_count" placeholder="" required />
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

                                        <!-- <div class="col-md-4 " > -->
                                        <div class="card p-4 col-md-4" id="transport_quotation" style="display:none">
                                            <form action="javascript:void(0);" id="transport_details">
                                                <input type="hidden" name="trans_tour_id" id="trans_tour_id">
                                                <input type="hidden" name="trans_row_id" id="trans_row_id">
                                                <input type="hidden" name="trans_date" id="trans_date">
                                                <input type="hidden" id="flg" value="1">
                                                <div class="row g-3">
                                                    <p id="modal-date-info1"></p>
                                                    <div class="col-md-12">
                                                        <div class="row">
                                                            <label class="form-label" for="code">Distance Type <font color="#FF0000"><strong>*</strong></font></label>
                                                            <div class="mt-4 mt-lg-0">
                                                                <div class="col-lg-12">
                                                                    <div class="d-flex mt-lg-0">
                                                                        <div class="form-check form-check-inline">
                                                                            <input class="form-check-input" type="radio" name="distance_type1" id="manual" value="1" checked>
                                                                            <label class="form-check-label" for="inlineRadio1">Manual</label>
                                                                        </div>
                                                                        <div class="form-check form-check-inline">
                                                                            <input class="form-check-input" type="radio" name="distance_type1" id="google" value="2">
                                                                            <label class="form-check-label" for="inlineRadio2">Google</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <br>
                                                        <!-- <br> -->
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


                                                        <div id="map" class="rounded d-none"></div>
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
                                                                    <input class="form-control" type="text" id="gdistance" name="gdistance" placeholder="" />
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

                                    <input type="hidden" class="form-control" value="<?= $quotation_id ?>" id="tid">
                                    <input type="hidden" class="form-control" value="<?= $start_date ?>" id="tstartdate">
                                    <input type="hidden" class="form-control" value="<?= $end_date ?>" id="tenddate">
                                    <div class="col-md-12" id="table_detail" style="display: none;">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="d-flex">
                                                    <div class="me-auto">
                                                        <h4 class="p-title">Tour Details</h4>
                                                        <p>Selected Vehicle : <strong class="me-3" id="vehicle_name"></strong> Rate per One KM : <strong id="rate"></strong></p>
                                                    </div>
                                                    <div>
                                                        <button class="btn btn-dark email_btn">Email</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle text-center mt-3" id="date_table" data-tourid="" style="border: 0px solid #e2e2e2;">
                                                    <thead class="thead-dark">
                                                        <tr>
                                                            <th style="width: 5%;">#</th>
                                                            <th style="width: 5%;">Date</th>
                                                            <!-- <th>Distance Type</th> -->
                                                            <th style="width: 5%;">Transfer Code</th>
                                                            <th style="width: 25%;">Transfer(From Location-To Location)</th>
                                                            <th style="width: 5%;">Mileage</th>
                                                            <th style="width: 5%;">Transport</th>
                                                            <th style="width: 10%;">Hotel</th>
                                                            <th style="width: 5%;">Hotel code</th>
                                                            <th style="width: 10%;">Room Type</th>
                                                            <th style="width: 5%;">Meal Code</th>
                                                            <th style="width: 5%;">SGL</th>
                                                            <th style="width: 5%;">DBL</th>
                                                            <th style="width: 5%;">TPL</th>
                                                            <th style="width: 5%;">Details</th>
                                                            <!-- <th>Remove</th> -->
                                                        </tr>
                                                    </thead>
                                                    <tbody>

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="col-md-12" id="table_detail_report" style="display:none">
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
                                        </div> -->
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
                        <input type="hidden" name="up_flag" id="up_flag" value="0">
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
                                <!-- <div class="col-md-6">
                                    <div class="mb-3 hstack gap-2 " style="padding-top: 28px;">
                                        <button type="button" class="btn btn-success search_loc" id="search_loc">
                                            Search All
                                        </button>
                                    </div>
                                </div> -->

                            </div>

                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="hotel">Hotel <font color="#FF0000"><strong>*</strong></font></label>
                                    <select name="hotel" id="hotel" class="js-example-basic-single_m form-control" style="width: 100%;" onchange="hotelChanged(this.value);">
                                        <option value="0">Select a Hotel...</option>
                                        <?php
                                        foreach ($hotel as $id) {
                                            echo '<option value="' . $id['Sup_Acc_No'] . '" >' . $id['Name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span class="text_info">Selected Hotel: &nbsp;<code id="select_h"></code></span>
                                </div>
                            </div>
                            <input type="hidden" name="slct_htl" id="slct_htl">
                            <!--end col-->
                            <div class="col-xxl-6">
                                <div>
                                    <label class="form-label" for="hotel_code">Hotel Code <font color="#FF0000"><strong>*</strong></font></label>
                                    <select name="hotel_code" id="hotel_code" class="js-example-basic-single_m form-control" style="width: 100%;" onchange="hotelCodeChanged(this.value);">
                                        <option value=""></option>

                                    </select>
                                    <span class="text_info">Selected Hotel Code:&nbsp;<code id="select_h_code"></code></span>
                                </div>
                            </div>
                            <input type="hidden" name="slct_htl_c" id="slct_htl_c">
                            <div class="col-xxl-6" style="display:none;">
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
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>

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

                var options = {
                    componentRestrictions: {
                        country: 'LK'
                    }
                };

                const pickInput = document.getElementById('pick_location');
                const dropInput = document.getElementById('drop_location');

                // Create the autocomplete objects
                const pickAutocomplete = new google.maps.places.Autocomplete(pickInput, options);
                const dropAutocomplete = new google.maps.places.Autocomplete(dropInput, options);

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

            }
        });

        // Start Of Table Creation
        function creatTable(Tour_Id, start_date, end_date) {

            document.getElementById("consignee_form").style.display = "none";

            $('#date_table').attr('data-tourid', Tour_Id);

            // Clear the table before adding new data
            $('#date_table tbody').empty();

            var quotationDetails = '';
            var q_det = {};

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/get_tour_data/' + Tour_Id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {

                },
                success: function(response) {
                    quotationDetails = response.data;

                    $('#date_table tbody').empty();
                    $('#date_table tfoot').empty();

                    // Ensure quotationDetails is an array if it's not empty
                    if (Array.isArray(quotationDetails)) {
                        quotationDetails.forEach(function(detail) {
                            q_det[detail.tour_date] = detail;
                        });
                    }

                    // Parse dates
                    var startDate = new Date(start_date);
                    var endDate = new Date(end_date);

                    var totalSgl = 0; // Variable to store total sgl
                    var totalDbl = 0; // Variable to store total dbl
                    var totalTpl = 0; // Variable to store total dbl

                    // Calculate date difference
                    var currentDate = startDate;
                    var index = 1;
                    var total_mileage = tot_price = rate = 0;
                    while (currentDate <= endDate) {
                        $("#table_detail").removeAttr('style');

                        var formattedDate = currentDate.toISOString().split('T')[0];

                        var row = '<tr data-dateid="Tour-' + Tour_Id + '-' + index + '" data-realdate="' + formattedDate + '">';

                        var value = q_det[formattedDate] || {};

                        var transf, dis;
                        var d_type = value.distance_type || 0;

                        if (d_type == 1) {
                            transf = value.transfer;
                            dis = value.distance;
                        } else if (d_type == 2) {
                            transf = value.from_location + " To " + value.to_location;
                            dis = value.gdistance;
                        } else if (d_type == 0) {
                            transf = '';
                            dis = '';
                        }

                        // Calculate totals
                        totalSgl += value.sgl ? parseFloat(value.sgl) : 0.00;
                        totalDbl += value.dbl ? parseFloat(value.dbl) : 0.00;
                        totalTpl += value.tpl ? parseFloat(value.tpl) : 0.00;

                        row += '<td>' + index + '</td>';
                        row += '<td>' + formattedDate + '</td>';

                        // row += '<td>' + (value.distance_type ? value.distance_type : '') + '</td>';
                        row += '<td>' + (value.code ? value.code : '') + '</td>';
                        row += '<td>' + (transf) + '</td>'
                        row += '<td>' + (dis) + '</td>';

                        if (dis != '' && !isNaN(dis)) {
                            total_mileage += parseFloat(dis); // Add mileage to total mileage
                        }
                        tot_price = total_mileage * rate;

                        if (value.trans_flag == 1) {
                            row += '<td><button type="button" class="btn btn-sm btn-info edit-transport" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="1" data-distype="' + d_type + '" data-distance="' + dis + '" data-transfer_code="' + value.transfer_code + '" data-transfer="' + value.transfer + '" data-from="' + value.from_location + '" data-to="' + value.to_location + '"> <i class="bi bi-pen"></i></button></td>';
                        } else {
                            row += '<td><button type="button" class="btn btn-sm btn-info add-transport" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="1"> <i class="bi bi-plus"></i></button></td>';
                        }
                        row += '<td>' + (value.hotel_name ? value.hotel_name : '') + '</td>';
                        row += '<td>' + (value.hotel_code ? value.hotel_code : '') + '</td>';
                        row += '<td>' + (value.room_type ? value.room_type : '') + '</td>';
                        row += '<td>' + (value.meal_code ? value.meal_code : '') + '</td>';
                        row += '<td>' + (value.sgl ? value.sgl : '') + '</td>';
                        row += '<td>' + (value.dbl ? value.dbl : '') + '</td>';
                        row += '<td>' + (value.tpl ? value.tpl : '') + '</td>';

                        if (value.det_flag == 1) {
                            row += '<td><button type="button" class="btn btn-sm btn-success edit-info" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="2"> <i class="bi bi-pen"></i></button></td>';
                        } else {
                            row += '<td><button type="button" class="btn btn-sm btn-success add-info" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="2"> <i class="bi bi-plus"></i></button></td>';
                        }
                        row += '</tr>';

                        $('#date_table tbody').append(row);

                        currentDate.setDate(currentDate.getDate() + 1);
                        index++;

                        if (index == 2) {
                            if (value.consignee) {
                                $('#vehicle_name').text(value.consignee.vehicle_name);
                                $('#rate').text(value.consignee.vehicle_rate);
                                rate = value.consignee.vehicle_rate;
                            }
                        }

                    }

                    var tfoot = '';
                    tfoot += '<tfoot>';
                    tfoot += '<tr>';
                    tfoot += '<td colspan="4"> Total Mile <br> Total Transport Cost'
                    tfoot += '</td>';
                    tfoot += '<td>' + parseFloat(total_mileage) + '<br>' + parseFloat(tot_price);
                    tfoot += '</td>';
                    tfoot += '<td colspan="5"> Total </td>';
                    tfoot += '<td>' + parseFloat(totalSgl) + '</td>';
                    tfoot += '<td>' + parseFloat(totalDbl) + '</td>';
                    tfoot += '<td>' + parseFloat(totalTpl) + '</td>';
                    tfoot += '<td>';
                    tfoot += '<button type="button"  class="btn btn-sm btn-warning btn-add" data-tour="' + Tour_Id + '" data-start="' + start_date + '" data-end="' + end_date + '"">';
                    tfoot += '<i class="bi bi-calendar-plus"></i>';
                    tfoot += '</button>';
                    tfoot += '</td>';
                    tfoot += '</tr>';
                    tfoot += '<tr>';
                    tfoot += '<td colspan="11"></td>';
                    tfoot += '<td colspan="3">';
                    tfoot += '<button type="button"  class="btn btn-md btn-danger btn-finish" data-tour="' + Tour_Id + '" data-start="' + start_date + '" data-end="' + end_date + '"">';
                    tfoot += '<i class="bi bi-file-lock2-fill"></i>Finalize';
                    tfoot += '</button>';
                    tfoot += '</td>';

                    tfoot += '</tr>';
                    tfoot += '</tfoot>';

                    $('#date_table tfoot').empty();
                    $('#date_table').append(tfoot);

                    // if ($.fn.dataTable.isDataTable('#date_table')) {

                    //     table = $('#date_table').DataTable();
                    //     table.destroy();
                    // }

                    $('#date_table').DataTable({
                        "destroy": true,
                        "paging": false,
                        "info": false,
                        "searching": false,
                        "ordering": false,
                        // "scrollY": "300px",
                        "scrollCollapse": true,
                        "scrollX": true,
                        "autoWidth": false,
                        "responsive": true,
                        "dom": 'Bfrtip',
                        "buttons": [{
                                extend: 'csv',
                                text: '<i class="bi bi-file-earmark-spreadsheet-fill"></i> CSV',
                                className: 'btn btn-primary border-0'
                            },
                            {
                                extend: 'print',
                                text: '<i class="bi bi-printer-fill"></i> Print',
                                className: 'btn btn-primary border-0'
                            }
                        ]
                    });


                    // Attach click event to the buttons (after table is fully created)
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
                        var date = $(this).data('date');
                        var tour = $(this).data('tour');
                        var row = $(this).data('row');
                        var dis_type = $(this).data('distype');
                        var dist = $(this).data('distance');
                        var transfer_code = $(this).data('transfer_code');
                        var transfer = $(this).data('transfer');
                        var froml = $(this).data('from');
                        var tol = $(this).data('to');
                        $('#modal-date-info1').text('Selected date: ' + date);
                        $("#trans_date").val(date);
                        $("#trans_tour_id").val(tour);
                        $("#trans_row_id").val(row);
                        $("#flg").val(2);

                        $("#transport_quotation").show();
                        // Check which radio button to display based on dis_type
                        toggleElements_new(dis_type);

                        if (dis_type == 1) {
                            $("#manual").prop('checked', true); // Show manual radio button
                            $('#distance').val(dist);
                            $('#transfer_code').val(transfer_code).trigger('change');
                            $('#transfer').val(transfer);
                        } else if (dis_type == 2) {
                            $("#google").prop('checked', true); // Show google radio button
                            $('#gdistance').val(dist);
                            $('#pick_location').val(froml);
                            $('#drop_location').val(tol);
                        }
                    });

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
                        searchHotel(tour, row, date);
                        searchHotelCode(tour, row, date);
                    });


                    $('.edit-info').on('click', function() {

                        var date = $(this).data('date');
                        var tour = $(this).data('tour');
                        var row = $(this).data('row');
                        $('#modal-date-info').text('Selected date: ' + date);
                        $('#date').val(date);
                        $("#tour_id").val(tour);
                        $("#row_id").val(row);
                        $('#dateModal').modal('show');
                        $("#up_flag").val(1);
                        // Call functions related to adding info (if needed)
                        searchHotel(tour, row, date);
                        searchHotelCode(tour, row, date);

                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/tour/edit_info/' + date + '/' + tour + '/' + row,
                            type: 'GET',
                            dataType: 'json',
                            beforeSend: function() {

                            },
                            success: function(response) {
                                if (response.status == 200) {
                                    console.log(response.data);
                                    $("#select_h").text(response.data.hotel_name);
                                    $("#select_h_code").text(response.data.hotel_code);
                                    $("#slct_htl").val(response.data.hotel_acc);
                                    $("#slct_htl_c").val(response.data.hotel_code);
                                    $("#room_type").val(response.data.rtype);
                                    $('#room_type_id').val(response.data.room_type_id);
                                    $("#meal_code").val(response.data.meal_code);
                                    $("#sgl").val(response.data.sgl);
                                    $("#dbl").val(response.data.dbl);
                                    $("#tpl").val(response.data.tpl);

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

                    $(".btn-finish").on('click', function() {
                        var tour = $(this).data('tour');
                        var start_date = $(this).data('start');
                        var end_date = $(this).data('end');

                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/tour/finalize/' + tour,
                            type: 'GET',
                            dataType: 'json',
                            beforeSend: function() {

                            },
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = "<?php echo base_url(); ?>tour/tour";
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

                    });

                },

            });

        }
        // End of Table Creation

        // var hotelCodeChangedCalled = false;

        function resetFormSelections() {

            // var hotelCodeChangedCalled = false;
            var initialHotelOptions = $("#hotel").html();
            $("#hotel").html(initialHotelOptions);

            $("#hotel_code").empty().append('<option value=""></option>');
            $("#meal_code").prop('selectedIndex', 0);

        }

        function hotelChanged(selectedval) {

            // console.log("hotel_id : " + selectedval);

            // if (hotelCodeChangedCalled) {
            //     return;
            // }

            var id = selectedval;
            $("#slct_htl").val(id);
            var date = $("#date").val();

            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/show_hotel/' + id + '/' + date,
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
                        if (response.length === 1) {
                            $('#hotel_code').append(
                                '<option value="' + value.hotel_code + '" selected>' + value.hotel_code + '</option>'
                            );
                            $("#room_type").val(value.rtype);
                            $("#room_type_id").val(value.id);
                            $("#slct_htl_c").val(value.hotel_code);
                        } else {
                            $('#hotel_code').append(
                                '<option value="' + value.hotel_code + '">' + value.hotel_code + '</option>'
                            );
                        }
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
            $("#slct_htl_c").val(id);
            // hotelCodeChangedCalled = true;
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/show/' + id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {

                },
                success: function(response) {
                    if (response.status == 200) {

                        // $('#hotel').css('background-color', '#f0f0f0');
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

            $(".text_info").text('');
            $('#quotation_details')[0].reset();
            resetFormSelections();
            $('#dateModal').modal('hide');
        });

        // Document Ready Add Quatation Details
        $(document).ready(function() {

            $("#quotation_details").submit(function(e) {
                e.preventDefault();
                var hotel = $("#hotel").val();
                var start_date = $("#start_date").val();
                var end_date = $("#end_date").val();

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

                                // location.reload();
                                creatTable(t_id, start_date, end_date);

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
        });

        function transferChanged(selectedTransfer) {

            var id = selectedTransfer;
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/show_transfer/' + id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {

                },
                success: function(response) {
                    if (response.status == 200) {

                        $('#transfer').val(response.data.transfer);
                        $('#distance').val(response.data.actual_mileage);

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
            var slct_h_code = $("#slct_htl_c").val();
            var room_type = $('#room_type_id').val();
            var h_code = "";

            // if any of above is empty, return

            if (hotel_code == '' || hotel_code == null) {
                h_code = slct_h_code;
            } else {
                h_code = hotel_code;
            }

            if (h_code == '' || room_type == 0 || h_code == null || h_code == 0) {
                return;
            }

            $('#loadingModal').modal('show');
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

                        },
                        success: function(response) {
                            if (response.status == 200) {

                                var Tour_Id = response.data;
                                window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + Tour_Id + "&start=" + start_date + "&end=" + end_date;

                                consignee_details(Tour_Id, start_date, end_date);


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

        function consignee_details(Tour_Id, start_date, end_date) {
            var Tour_Id = Tour_Id;
            var start_date = start_date;
            var end_date = end_date;
            var consignee = $("#consignee").val();
            var email = $("#email").val();
            var country = $("#country").val();
            var con_code = $("#t_code_1").val();
            var contact = $("#tel_mobile").val();
            var vehicle = $("#vehicle").val();
            var vehicleRate = $("#vehicle option:selected").data('rate');
            var vehicle_name = $("#vehicle option:selected").text();
            var pass_count = $("#passenger_count").val();

            // alert(vehicleRate);

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/insertConsignee',
                type: 'POST',
                data: {
                    Tour_Id: Tour_Id,
                    start_date: start_date,
                    end_date: end_date,
                    consignee: consignee,
                    email: email,
                    country: country,
                    con_code: con_code,
                    contact: contact,
                    vehicle: vehicle,
                    vehicle_name: vehicle_name,
                    vehicle_rate: vehicleRate,
                    pass_count: pass_count
                },
                dataType: 'json',
                beforeSend: function() {

                },
                success: function(response) {
                    if (response.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                        }).then((result) => {
                            // $("#consignee").val(response.consignee);
                            // $("#email").val(response.email);
                            // $("#country").val(response.country);
                            // $("#t_code_1").val(response.k_code);
                            // $("#tel_mobile").val(response.mobile);
                            // $("#vehicle").val(response.vehicle);
                            // $("#passenger_count").val(response.passenger_count);
                            // if (result.isConfirmed) {
                            //     // location.reload();
                            // }
                        });
                    } else if (response.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
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

        }

        function searchHotel(selecttour, selectrow, selectdate) {

            var tour = selecttour;
            var row = selectrow;
            var date = selectdate;

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/search_hotel/' + tour + '/' + row + '/' + date,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {

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

        function searchHotelCode(selecttour, selectrow, selectdate) {
            var tour = selecttour;
            var row = selectrow;
            var date = selectdate;

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/search_hotel_code/' + tour + '/' + row + '/' + date,
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

        // Add Transport adetsils for Quotation
        // $(document).ready(function() {
        var initialTransCode = $("#transfer_code").html();
        $("#transport_details").submit(function(e) {
            e.preventDefault();

            var stat = $("#flg").val();
            var start_date = $("#start_date").val();
            var end_date = $("#end_date").val();

            if (stat == 1) {
                endpoint = '<?php echo base_url(); ?>tour/tour/insertTransportQuatation';
            } else {
                tro_id = $('#trans_row_id').val();
                trour_id = $('#trans_tour_id').val();
                endpoint = '<?php echo base_url(); ?>tour/tour/editTransportQuatation/' + trour_id + '/' + tro_id;
            }

            $.ajax({
                url: endpoint,
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                beforeSend: function() {

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

                            creatTable(t_id, start_date, end_date);

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

        // Function to show/hide elements based on distance type
        function toggleElements() {
            distanceType = document.querySelector('input[name="distance_type1"]:checked').value;

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

        function toggleElements_new(d_type) {
            var distanceType = d_type;
            // // if (d_type == null) {
            //     distanceType = document.querySelector('input[name="distance_type1"]:checked').value;
            // // }
            // alert(distanceType);
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
        var distanceTypeRadios = document.querySelectorAll('input[name="distance_type1"]');
        distanceTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', toggleElements);
            // alert(distanceTypeRadios);
        });

        // Initial call to toggleElements to set initial state
        toggleElements();



        $("#search_location").on('change', function(e) {
            e.preventDefault();

            var search_location = $("#search_location").val();
            var date = $("#date").val();


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

                        // Swal.fire({
                        //     icon: 'success',
                        //     title: 'Success',
                        //     text: 'Successfully, hotels and hotel codes found near to ' + capitalizeFirstLetter(search_location) + ' .',
                        // });

                    } else {
                        // Swal.fire({
                        //     icon: 'error',
                        //     title: 'Error',
                        //     text: data.message ? data.message : 'An error occured while processing the request',
                        // });
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

        // .country on change - change the country code as well t_code_1
        $("#country").on('change', function(e) {
            var phonecode = $(this).find(':selected').data('phonecode');
            $("#t_code_1").val(phonecode).change();
        });

        function load_data(tourId) {

            $.ajax({
                url: '<?php echo base_url('tour/tour/load_details') ?>',
                type: 'GET',
                data: {
                    tourId: tourId,
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {

                        window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + response.data.tour_id + "&start=" + response.data.start_date + "&end=" + response.data.end_date;

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
    </script>

</body>

</html>