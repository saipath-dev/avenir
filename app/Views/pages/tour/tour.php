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

                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="col-md-12" id="consignee_form">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                        <fieldset>

                                                            <div class="row">
                                                                <?php if ($rowCount) { ?>
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
                                                                <?php } ?>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="inquiries">
                                                                        Inquiries
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
                                                            <div class="row pb-2">
                                                                <div class="col-md-12">
                                                                    <div class="form-check form-switch">
                                                                        <input class="form-check-input" type="checkbox" id="hotel_only" name="hotel_only" value="1">
                                                                        <label class="form-check
                                                                    -label" for="hotel_only">Hotel Only</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="consignee">Tour Name<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="consignee" name="consignee" required />
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="manual_id">Manual Quotation Id</label>
                                                                    <input class="form-control" type="text" id="manual_id" name="manual_id" />
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="agent">Agent Name<font color="#FF0000"><strong>*</strong></font>
                                                                        <?php $agents_marked = (int) ($agents_marked ?? 0); if ($agents_marked > 0) { ?>
                                                                            <small class="text-muted ms-1" id="agent_scope">— the <?= $agents_marked ?> marked agents. <a href="#" id="agent_scope_link">Not here? Show all customers</a></small>
                                                                        <?php } ?>
                                                                    </label>
                                                                    <!-- <input class="form-control" type="text" id="agent" name="agent" required /> -->
                                                                    <!-- the agents marked under Master Files → Consignees; the whole ledger behind the link above -->
                                                                    <select name="agent" id="agent" class="js-example-basic-single form-control" style="width: 100%;" required onchange="agent_detail(this.value)">
                                                                        <option value="">Select...</option>
                                                                        <?php
                                                                        foreach ($agents as $agent) {
                                                                            // $selected = $countryx['iso'] == $country ? 'selected' : '';
                                                                            echo '<option value="' . $agent['Sup_Acc_No'] . '">' . $agent['Name'] . '</option>';
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="email">Email<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="email" name="email" readonly />
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
                                                                <div class="mb-3 col-md-6">
                                                                    <label class="form-label" for="vehicle">Vehicle<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="vehicle" id="vehicle" class="js-example-basic-single form-control" required>
                                                                        <option value=""></option>

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

                                                                <div class="row">
                                                                    <label class="form-label">Room Count</label>
                                                                    <div class="col-md-4">
                                                                        <label class="form-label" for="sgl">Single</label>
                                                                        <input class="form-control" type="text" id="roomsgl" name="roomsgl" placeholder="" />
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <label class="form-label" for="dbl">Double</label>
                                                                        <input class="form-control" type="text" id="roomdbl" name="roomdbl" placeholder="" />
                                                                    </div>
                                                                    <div class="col-md-1 mt-4">
                                                                        <label class="form-check-label" for="twin">Twin</label>
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input" type="checkbox" id="twin" name="twin">
                                                                        </div>
                                                                        <input type="hidden" id="is_twin" name="is_twin" value="0">
                                                                        <input type="hidden" id="is_twin2" name="is_twin2">
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <label class="form-label" for="tpl">Triple</label>
                                                                        <input class="form-control" type="text" id="roomtpl" name="roomtpl" placeholder="" />
                                                                    </div>
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
                                    <!-- <div class="col-md-4 " > -->
                                    <div class="col-md-12" id="transport_quotation_all" style="display: none;">
                                        <!-- <div class="row"> -->
                                        <div class="col-md-12 card p-4 py-3" id="">
                                            <form action="javascript:void(0);" id="quotation_details_all">
                                                <div>
                                                    <input type="hidden" name="trans_tour_id" id="trans_tour_id" value="<?php echo $quotation_id ?>">
                                                    <input type="hidden" name="trans_row_id" id="trans_row_id">
                                                    <input type="hidden" name="t_date" id="t_date" value="<?php echo $start_date ?>">
                                                    <input type="hidden" id="flg" value="1">
                                                    <input type="hidden" id="start_d" value="<?php echo $start_date ?>">
                                                    <input type="hidden" id="end_d" value="<?php echo $end_date ?>">
                                                    <input type="hidden" name="up_flag" id="up_flag" value="0">

                                                    <!-- <input type="hidden" id="count_sgl" name="count_sgl">
                                                    <input type="hidden" id="count_dbl" name="count_dbl">
                                                    <input type="hidden" id="count_tpl" name="count_tpl"> -->
                                                </div>
                                                <div>
                                                    <div class="row pb-2">
                                                        <div class="col-md-12">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" id="hotel_only_q" name="hotel_only_q">
                                                                <label class="form-check
                                                                    -label" for="hotel_only">Hotel Only</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row g-3">
                                                        <!-- <p id="modal-date-info1"></p> -->
                                                        <div class="row pb-2 border-bottom" style="padding-top: 10px;">

                                                            <div class="col-md-3">
                                                                <label class="form-label"> Quotation Id : <span id=tournum></span></label>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label"> Tour Name : <span id=tourname></span></label>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label"> Manual Quotation Id : <span id=mtournum></span></label>
                                                            </div>

                                                            <div class="col-md-3">
                                                                <label class="form-label"> Date : <span id=tdate></span></label>
                                                            </div>

                                                            <label class="form-label" style="display:none; " id="display_destination"> Last Destination :<span id="prev_destin" style="color:#0f58dffa;"></span></label>

                                                        </div>
                                                        <div class="row border-bottom" style="padding-top: 10px; padding-bottom: 10px;">
                                                            <!-- <form id="room_count" action="javascript:void(0);"> -->
                                                            <div class="col-md-3">
                                                                <label class="form-label" for="sgl">Single Room Count</label>
                                                                <input class="form-control" type="text" id="count_sgl" name="count_sgl" placeholder="" />
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label" for="dbl">Double Room Count</label>
                                                                <input class="form-control" type="text" id="count_dbl" name="count_dbl" placeholder="" />
                                                            </div>
                                                            <div class="col-md-1 mt-4">
                                                                <label class="form-check-label" for="count_twin">Twin</label>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" id="count_twin" name="count_twin">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label" for="tpl">Triple Room Count</label>
                                                                <input class="form-control" type="text" id="count_tpl" name="count_tpl" placeholder="" />
                                                            </div>
                                                            <div class="col-md-2 mt-4">
                                                                <label class="form-label"></label>
                                                                <button type="button" class="btn btn-success" id="add_room">Add Room</button>
                                                            </div>
                                                            <!-- </form> -->
                                                        </div>
                                                        <!-- hotel only checkbox -->
                                                        <div class="col-md-4 pe-3">
                                                            <div class="row" style="display: none;">
                                                                <label class="form-label" for="code">Distance Type <font color="#FF0000"><strong>*</strong></font></label>
                                                                <div class="mt-4 mt-lg-0">
                                                                    <div class="col-lg-12">
                                                                        <div class="d-flex mt-lg-0">
                                                                            <div class="form-check form-check-inline">
                                                                                <input class="form-check-input" type="radio" name="distance_type1" id="manual" value="1">
                                                                                <label class="form-check-label" for="inlineRadio1">Manual</label>
                                                                            </div>
                                                                            <div class="form-check form-check-inline">
                                                                                <input class="form-check-input" type="radio" name="distance_type1" id="google" value="2" checked>
                                                                                <label class="form-check-label" for="inlineRadio2">Google</label>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div id="map" class="rounded d-none"></div>
                                                            <div id="map_div">
                                                                <div class="my-3">
                                                                    <input type="hidden" id="is_hotel" name="is_hotel" value="0">
                                                                    <div class="mb-3">
                                                                        <label for="pick_location" class="form-label">From
                                                                            Location</label>
                                                                        <div class="input-group mb-3">
                                                                            <input type="text" class="form-control text-dark fw-bold" id="pick_location" name="pick_location" placeholder="Search" required>
                                                                            <button class="btn btn-dark" type="button" id="locate-me"><i class="bi bi-geo-alt-fill"></i></button>
                                                                        </div>
                                                                        <input type="hidden" id="pick_lat">
                                                                        <input type="hidden" id="pick_long">
                                                                        <input type="hidden" id="pick_city" value="">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="drop_location" class="form-label">To
                                                                            Location</label>
                                                                        <div class="input-group mb-3">
                                                                            <input id="drop_location" name="drop_location" type="text" class="form-control text-dark fw-bold" placeholder="Search" required>
                                                                            <button class="btn btn-dark" type="button" id="locate-me"><i class="bi bi-geo-alt-fill"></i></button>
                                                                        </div>
                                                                        <input type="hidden" id="drop_lat">
                                                                        <input type="hidden" id="drop_long">
                                                                        <input type="hidden" id="drop_city" name="drop_city" value="">
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-12" id="gmanudis">
                                                                    <div>
                                                                        <label class="form-label" for="gdistance">Google Distance<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control" type="text" id="gdistance" name="gdistance" placeholder="" onchange="hotelload();" readonly />
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-12 mt-3" id="gmanudis">
                                                                    <div>
                                                                        <label class="form-label" for="distance">Distance<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control" type="text" id="distance" name="distance" placeholder="" required />
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12 mt-3">
                                                                    <div>
                                                                        <label class="form-label" for="extrakm">Extra KM</label>
                                                                        <input class="form-control" type="text" id="extrakm" name="extrakm" placeholder="" />
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-12 mt-3">
                                                                    <button type="button" class="btn btn-warning text-dark" id="sub_location">Add Next Location</button>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-7 border-start ps-3 pt-3">
                                                            <div class="row g-3">
                                                                <div class="row">
                                                                    <!-- <div class="col-md-6">
                                                                    <p id="modal-date-info"></p>
                                                                </div> -->
                                                                    <div class="col-md-12">
                                                                        <h5>Destination : &nbsp;<code><span id="final_destination"></span></code></h5>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                                                            <label class="form-label" for="search_location">Search Destination</label>
                                                                            <select name="search_location" id="search_location" class="js-example-basic-single form-control" style="width: 100%;">
                                                                                <option value="">Select a Destination...</option>
                                                                                <?php
                                                                                foreach ($hotel_location as $id) {
                                                                                    echo '<option value="' . $id['Address'] . '" >' . $id['Address'] . '</option>';
                                                                                }
                                                                                ?>
                                                                            </select>

                                                                        </div>
                                                                    </div>
                                                                    <div class="col-xxl-6">
                                                                        <div>
                                                                            <label class="form-label" for="hotel">Hotel <font color="#FF0000"><strong>*</strong></font></label>
                                                                            <select name="hotel" id="hotel" class="js-example-basic-single form-control" style="width: 100%;" onchange="assign_hotel(this.value)">
                                                                                <option value="0">Select a Hotel...</option>
                                                                                <?php
                                                                                // foreach ($hotel as $id) {
                                                                                //     echo '<option value="' . $id['Sup_Acc_No'] . '" >' . $id['Name'] . '</option>';
                                                                                // }
                                                                                ?>
                                                                            </select>
                                                                            <span class="text_info" style="text-align: left;">Selected Hotel: &nbsp;<code id="select_h"></code></span>
                                                                        </div>
                                                                    </div>
                                                                    <input type="hidden" name="slct_htl" id="slct_htl">

                                                                    <input type="hidden" name="slct_htl_c" id="slct_htl_c">

                                                                    <div class="col-xxl-6">
                                                                        <div class="mt-3">
                                                                            <label class="form-label" for="room_type">Room Type<font color="#FF0000"><strong>*</strong></font></label>
                                                                            <!-- <input class="form-control" type="text" id="room_type" name="room_type" placeholder="" required /> -->
                                                                            <select class="js-example-basic-single form-control" style="width: 100%;" id="room_type" name="room_type">
                                                                            </select>
                                                                            <span class="text_info" style="text-align: left;">Selected Room Type: &nbsp;<code id="select_rt"></code></span>
                                                                            <input type="hidden" id="room_type_id" name="room_type_id" placeholder="" />
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-xxl-6">
                                                                        <div class="mt-3">
                                                                            <label class="form-label" for="meal_code">Meal Code <font color="#FF0000"><strong>*</strong></font></label>
                                                                            <select name="meal_code" id="meal_code" class="form-control" style="width: 100%;" onchange="mealCodeChanged(this.value);">
                                                                                <option value="0"></option>
                                                                                <option value="bb">BB</option>
                                                                                <option value="hb">HB</option>
                                                                                <option value="fb">FB</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <!--end col-->
                                                                    <!--end col-->
                                                                    <div class="row mt-3">

                                                                        <div class="col-md-4">
                                                                            <label class="form-label" for="sgl">SGL</label>
                                                                            <input class="form-control" type="text" id="sgl" name="sgl" placeholder="" />
                                                                        </div>
                                                                        <!--end col-->
                                                                        <div class="col-md-4">
                                                                            <label class="form-label" for="dbl">DBL</label>
                                                                            <input class="form-control" type="text" id="dbl" name="dbl" placeholder="" />
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label class="form-label" for="tpl">TPL</label>
                                                                            <input class="form-control" type="text" id="tpl" name="tpl" placeholder="" />
                                                                        </div>
                                                                    </div>
                                                                    <!--end col-->
                                                                    <div class="col-lg-12 mt-3">
                                                                        <div class="hstack gap-2 justify-content-end" style="padding-top: 10px;">
                                                                            <!-- <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button> -->
                                                                            <button type="submit" class="btn btn-success">Submit</button>
                                                                            <button type="button" class="btn btn-info" id="previous"><i class="bi bi-arrow-left-square"></i></button>
                                                                            <b><span id="real-date"></span></b>
                                                                            <button type="button" class="btn btn-info" id="next"><i class="bi bi-arrow-right-square"></i></button>
                                                                        </div>

                                                                    </div>
                                                                    <!--end col-->
                                                                </div>
                                                            </div>

                                                        </div>



                                                        <!--end row-->
                                            </form>
                                        </div>
                                    </div>

                                </div>
                                <!--   -->

                                <!-- <div class="col-md-12">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="col-md-12 mt-3">
                                                    <table class="table table-hover align-middle text-center mt-3">
                                                        <thead>
                                                            <tr>
                                                                <th>From</th>
                                                                <th>To</th>
                                                                <th>Distance</th>
                                                                <th>Extra KM</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="sub_location_table">
                                                        </tbody>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->

                                <div class="col-md-12 d-none" id="table_sub_location">
                                    <input type="hidden" class="form-control" value="<?= $quotation_id ?>" id="tid">
                                    <input type="hidden" class="form-control" value="<?= $start_date ?>" id="tstartdate">
                                    <input type="hidden" class="form-control" value="<?= $end_date ?>" id="tenddate">
                                    <div class="card p-4">
                                        <div class="d-flex flex-column" id="sl_tbls">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle text-center mt-3" id="date_table_location" data-tourid="" style="border: 0px solid #e2e2e2;">
                                                    <thead class="thead-dark">
                                                        <tr>
                                                            <th>From</th>
                                                            <th>To</th>
                                                            <th>Distance</th>
                                                            <th>Extra KM</th>
                                                            <th>Activity</th>
                                                            <th>Action</th>
                                                            <th>Location Wise Activity</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="sub_location_table">
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tou Details Table -->
                                <div class="col-md-12" id="table_detail" style="display: none;">
                                    <input type="hidden" class="form-control" value="<?= $quotation_id ?>" id="tid">
                                    <input type="hidden" class="form-control" value="<?= $start_date ?>" id="tstartdate">
                                    <input type="hidden" class="form-control" value="<?= $end_date ?>" id="tenddate">
                                    <div class="card p-4">
                                        <div class="body">
                                            <div class="d-flex">
                                                <div class="me-auto">
                                                    <h4 class="p-title">Tour Details</h4>
                                                    <p>Selected Vehicle : <strong class="me-3" id="vehicle_name"></strong> Rate per One KM : <strong id="rate"></strong></p>
                                                </div>
                                                <!-- <div>
                                                    <?php //if ($quotation_id > 0) { 
                                                    ?>
                                                        <a href="<? //= base_url('tour/sub_locations/' . $quotation_id) 
                                                                    ?>" target="_blank" class="btn btn-warning text-dark">Add Sub Locations for Tour</a>
                                                    <?php //} 
                                                    ?>
                                                </div> -->
                                                <div>
                                                    <button id="printBtn" class="btn btn-primary d-none"><i class="bi bi-printer-fill"></i> &nbsp;Resevation Print</button>
                                                    <button id="itineraryPrintBtn" class="btn btn-primary d-none"><i class="bi bi-file-earmark-spreadsheet-fill"></i>&nbsp;Itinerary Print</button>
                                                    <!-- <button id="email_btn" class="btn btn-dark email_btn d-none"><i class="bi bi-envelope"></i>&nbsp;Email</button> -->
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
                                                        <!-- <th style="width: 5%;">Transfer Code</th> -->
                                                        <th style="width: 25%;">From Location</th>
                                                        <th style="width: 25%;">To Location</th>
                                                        <th style="width: 5%;">KM</th>
                                                        <th style="width: 5%;">Extra KM</th>
                                                        <th style="width: 10%;">Hotel</th>
                                                        <!-- <th style="width: 5%;">Hotel code</th> -->
                                                        <th style="width: 10%;">Room Type</th>
                                                        <th style="width: 5%;">Meal Code</th>
                                                        <th style="width: 5%;">SGL</th>
                                                        <th style="width: 5%;">DBL</th>
                                                        <th style="width: 5%;">TPL</th>
                                                        <th style="width: 5%;">Extra Atc. Fee</th>
                                                        <th style="width: 5%;">Action</th>
                                                        <th style="width: 5%;">Activity</th>
                                                        <th style="width: 5%;">Itinerary</th>
                                                        <th>Record Delete</th>
                                                        <!-- <th>Remove</th> -->
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                </tbody>
                                                <tfoot>

                                                </tfoot>

                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div> <!-- end .h-100-->

            <div class="modal fade" id="AddmemberModal" tabindex="-1" aria-labelledby="AddmemberModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="AddmemberModalLabel">Add Pax Details</h1>
                            <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            <input type="hidden" name="modal_tour_id" id="modal_tour_id">
                            <input type="hidden" name="pax_cc" id="pax_cc">
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="mb-4">
                                    <label class="form-label" for="passenger">Passenger Name<font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control txt_nav" type="text" id="passenger" name="passenger" placeholder="" required />
                                    <button type="button" class="btn btn-success mt-2" id="add_passenger">Save</button>
                                </div>
                                <div class="px-3">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Passenger Name</th>
                                                <!-- <th scope="col">Action</th> -->
                                            </tr>
                                        </thead>
                                        <tbody id="passenger_details">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok">Ok</button>
                            <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="AddactivityModal" tabindex="-1" aria-labelledby="AddactivityModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="AddactivityModalLabel">Add Activity</h1>
                            <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            <input type="hidden" name="activity_tour_id" id="activity_tour_id">
                            <input type="hidden" name="activity_date" id="activity_date">
                            <input type="hidden" name="activity_row" id="activity_row">
                            <input type="hidden" name="pax_cc_activity" id="pax_cc_activity">
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="mb-4">
                                    <label class="form-label" for="placetovisit">Select Place<font color="#FF0000"><strong>*</strong></font></label>
                                    <!-- <input class="form-control txt_nav" type="text" id="passenger" name="passenger" placeholder="" required /> -->
                                    <select name="placetovisit" id="placetovisit" class="js-example-basic-single_ma form-control" style="width: 100%;" required onchange="ratedisplay(this.value)">
                                        <option value="">Select...</option>
                                        <?php
                                        foreach ($places as $place) {
                                            // $selected = $countryx['iso'] == $country ? 'selected' : '';
                                            echo '<option value="' . $place['id'] . '" data-fee="' . $place['entranceFee'] . '" data-code="' . $place['code'] . '">' . $place['name'] . ' - ' . $place['code'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <!-- <button type="button" class="btn btn-success mt-2 add_activity">Save</button> -->
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" for="activity_rate">Rate <font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control txt_nav" type="text" id="activity_rate" name="activity_rate" placeholder="" required />
                                    <button type="button" class="btn btn-success mt-2 add_activity">Save</button>
                                </div>
                                <div class="px-3">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Place Name</th>
                                                <th scope="col">Place Code</th>
                                                <th scope="col">Rate</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activity_details">

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok-activity">Ok</button>
                            <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="AddactivitylocationModal" tabindex="-1" aria-labelledby="AddactivitylocationModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="AddactivitylocationModalLabel">Add Activity</h1>
                            <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            <input type="hidden" name="activity_location_tour_id" id="activity_location_tour_id">
                            <input type="hidden" name="activity_location_date" id="activity_location_date">
                            <input type="hidden" name="activity_location_row" id="activity_location_row">
                            <input type="hidden" name="loc_ref" id="loc_ref">
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="mb-4">
                                    <label class="form-label" for="placetovisitloc">Select Place<font color="#FF0000"><strong>*</strong></font></label>
                                    <!-- <input class="form-control txt_nav" type="text" id="passenger" name="passenger" placeholder="" required /> -->
                                    <select name="placetovisitloc" id="placetovisitloc" class="js-example-basic-single_loc form-control" style="width: 100%;" required onchange="ratedisplay(this.value)">
                                        <option value="">Select...</option>
                                        <?php
                                        foreach ($places as $place) {
                                            // $selected = $countryx['iso'] == $country ? 'selected' : '';
                                            echo '<option value="' . $place['id'] . '" data-fee="' . $place['entranceFee'] . '" data-code="' . $place['code'] . '">' . $place['name'] . ' - ' . $place['code'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <!-- <button type="button" class="btn btn-success mt-2 add_activity">Save</button> -->
                                </div>
                                <div class="mb-4">
                                    <!-- <label class="form-label" for="activity_rate">Rate <font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control txt_nav" type="text" id="activity_rate" name="activity_rate" placeholder="" required /> -->
                                    <button type="button" class="btn btn-success mt-2 add_activity_loc">Save</button>
                                </div>
                                <!-- <div class="px-3">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Place Name</th>
                                                <th scope="col">Place Code</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activity_details">

                                        </tbody>
                                    </table>
                                </div> -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok-activity">Ok</button>
                            <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="AdditineraryModal" tabindex="-1" aria-labelledby="AdditineraryModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="AdditineraryModalLabel">Itinerary Details</h1>
                            <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            <input type="hidden" name="itinerary_tour_id" id="itinerary_tour_id">
                            <input type="hidden" name="itinerary_date" id="itinerary_date">
                            <input type="hidden" name="itinerary_row" id="itinerary_row">
                            <input type="hidden" name="pax_cc_itinerary" id="pax_cc_itinerary">
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <!-- <div class="mb-4">
                                    <label class="form-label" for="itinerary">Description <font color="#FF0000"><strong>*</strong></font></label>
                                    <textarea class="form-control txt_nav" name="itinerary" id="itinerary" placeholder="" required></textarea>
                                    <button type="button" class="btn btn-success mt-2 add_itinerary">Save</button>
                                </div> -->
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
                            <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="Additinerary" tabindex="-1" aria-labelledby="AdditineraryLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="AdditineraryLabel">Add Itinerary</h1>
                            <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                            <input type="hidden" name="i_tour_id" id="i_tour_id">
                            <input type="hidden" name="i_date" id="i_date">
                            <input type="hidden" name="i_row" id="i_row">
                            <input type="hidden" name="i_order_id" id="i_order_id">
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="mb-4">
                                    <label class="form-label" for="itinerary">Description <font color="#FF0000"><strong>*</strong></font></label>
                                    <textarea class="form-control txt_nav" name="itinerary" id="itinerary" placeholder="" required></textarea>
                                    <button type="button" class="btn btn-success mt-2 save_itinerary">Save</button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok-itinerary">Close</button>
                            <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                        </div>
                    </div>
                </div>
            </div>

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
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBW7n1LFvH3Gjn3gWTXzmuBDePpLeBNFIk&libraries=places&"></script>



    <script>
        let pickLat = 0,
            pickLng = 0,
            dropLat = 0,
            dropLng = 0;

        document.addEventListener('DOMContentLoaded', function() {

            let map, directionsRenderer;

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
                console.log('PLACE : ');
                console.log(place);
                if (place.geometry) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    document.getElementById(latId).value = lat;
                    document.getElementById(lngId).value = lng;

                    // Extract the city name from the place details
                    var city = '';
                    for (var i = 0; i < place.address_components.length; i++) {
                        var component = place.address_components[i];
                        if (component.types.includes('locality')) {
                            city = component.long_name;
                            break;
                        }
                    }

                    // Update the pickLat, pickLng, dropLat, and dropLng variables
                    if (latId === 'pick_lat') {
                        pickLat = lat;
                        pickLng = lng;
                        document.getElementById('pick_city').value = city;
                    } else {
                        dropLat = lat;
                        dropLng = lng;
                        document.getElementById('drop_city').value = city;
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

                    directionsService = new google.maps.DirectionsService();
                    directionsService.route({
                        origin: origin,
                        destination: destination,
                        travelMode: google.maps.TravelMode.DRIVING
                    }, (response, status) => {

                        console.log(response);

                        if (status === 'OK') {
                            directionsRenderer.setDirections(response);
                            const distance = response.routes[0].legs[0].distance.text;

                            // Get Actual Distance
                            var pick = $('#pick_location').val();
                            if (pick.indexOf(', Sri Lanka') !== -1) {
                                frompick = pick.replace(', Sri Lanka', '');
                            }

                            var drop = $('#drop_location').val();
                            if (drop.indexOf(', Sri Lanka') !== -1) {
                                todrop = drop.replace(', Sri Lanka', '');
                            }
                            var from = $('#pick_city').val();
                            var to = $('#drop_city').val();
                            var hotelonly = $('#is_hotel').val();
                            $.ajax({
                                url: '<?php echo base_url(); ?>tour/tour/actual_distance',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    from: from,
                                    to: to,
                                    frompick: frompick,
                                    todrop: todrop
                                },
                                beforeSend: function() {

                                },
                                success: function(response) {
                                    if (response.status == 200) {

                                        $('#distance').val('');
                                        $('#extrakm').val('');

                                        var dd = response.data.Full_details[0];
                                        var kk = response.data.Extra_details[0];

                                        if (hotelonly == 1) {
                                            $('#distance').val(0);
                                            $('#extrakm').val(0);
                                        } else {
                                            if (dd?.actual_mileage != null) {
                                                $('#distance').val(dd.actual_mileage);
                                            }
                                            if (kk?.kilometers != null) {
                                                $('#extrakm').val(kk.kilometers);
                                            }
                                        }
                                        document.getElementById('gdistance').value = distance; // Update distance here
                                        hotelload();
                                    }
                                },
                                error: function(jqXHR, textStatus, errorThrown) {}
                            });

                            // alert(from);
                        } else {
                            console.error('Directions request failed due to ' + status);
                        }
                    });
                }
            }

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

        $('.js-example-basic-single_ma').select2({
            placeholder: " Select",
            allowClear: true,
            dropdownParent: $("#AddactivityModal")
        });

        $('.js-example-basic-single_loc').select2({
            placeholder: " Select",
            allowClear: true,
            dropdownParent: $("#AddactivitylocationModal")
        });

        var row = 1;

        var unfilledDate = '';
        var unfilledDateSearchInc = 0;

        // get the place of the date between the start and end date
        function get_date_place(start_date, end_date, date) {
            var start = new Date(start_date);
            var end = new Date(end_date);
            var current = new Date(date);

            var diff = Math.abs(current - start);
            var diffDays = Math.ceil(diff / (1000 * 60 * 60 * 24));

            return diffDays + 1;
        }


        function processNext(unfilled_date = '') {

            var st_date = $('#start_d').val();
            var en_date = $('#end_d').val();

            var tu_date_text = $('#tdate').text();

            if (unfilled_date != '') {
                tu_date_text = new Date(unfilled_date);
                tu_date_text.setDate(tu_date_text.getDate() - 1);
            }

            var tu_date = new Date(tu_date_text);
            var end_date = new Date(en_date);

            var tour = $("#trans_tour_id").val();
            var r_id = $("#trans_row_id").val();

            if (tu_date < end_date) {
                tu_date.setDate(tu_date.getDate() + 1);
                r_id = parseInt(r_id, 10) + 1;
                var incremented_date = tu_date.toISOString().split('T')[0];
                $('#tdate').text(incremented_date);
                $("#real-date").text(incremented_date);
                $('#t_date').val(incremented_date);
                $('#t_date').text(incremented_date);
                $('#trans_row_id').val(get_date_place(st_date, en_date, incremented_date));

                searchFullDetails(tour, incremented_date);

            } else {
                window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + st_date + "&end=" + en_date;
            }

        }

        $(document).ready(function() {

            var tour = $("#tid").val();
            var tour_start = $("#tstartdate").val();
            var tour_end = $("#tenddate").val();
            var pax_count = $("#passenger_count").val();

            if (tour != '') {
                $.ajax({
                    url: '<?php echo base_url(); ?>tour/tour/url_call',
                    type: 'GET',
                    data: {
                        tour: tour,
                    },
                    dataType: 'json',
                    beforeSend: function() {},
                    success: function(response) {
                        var end_date = response.data.end_date;
                        var start_date = response.data.start_date;
                        if (tour_end != end_date) {
                            window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + start_date + "&end=" + end_date;
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {

                    }
                });
            }

            if (tour != '' && tour_start != '' && tour_end != '') {
                // if (pax_count != '') {
                // } else {
                // alert(pax_count);
                $.ajax({
                    url: '<?php echo base_url(); ?>tour/tour/getPassengerDetails/' + tour,
                    type: 'GET',
                    dataType: 'json',
                    beforeSend: function() {},
                    success: function(response) {
                        var passengerList = response.data.passenger;
                        var paxCount = response.data.pax_count.passenger_count;
                        var manu_id = response.data.pax_count.manual_id;

                        if (passengerList !== paxCount) {
                            $('#AddmemberModal').modal('show');
                            $("#modal_tour_id").val(tour);
                            $("#pax_cc").val(paxCount);
                            load_passenger(tour);

                        } else {

                            creatTable(tour, tour_start, tour_end, true);
                            // nextprevious(tour, tour_start, tour_end);
                            // searchFullDetails(tour, tour_start, manu_id);
                            $("#pax_cc_activity").val(paxCount);
                            $("#trans_row_id").val(1);

                        }
                        $('#count_sgl').val(response.data.pax_count.sgl);
                        $('#count_dbl').val(response.data.pax_count.dbl);
                        $('#count_tpl').val(response.data.pax_count.tpl);
                        $('#is_hotel').val(response.data.pax_count.hotel_only);
                        $('#tournum').text(response.data.pax_count.tour_id);
                        $('#tourname').text(response.data.pax_count.consignee);
                        $('#mtournum').text(response.data.pax_count.manual_id);
                        var is_t = response.data.pax_count.dbl_twin
                        if (is_t == 0 || is_t == null) {
                            // count_twin checkbox is not checked
                            $('#count_twin').prop('checked', false);
                        } else {
                            // count_twin checkbox is checked
                            $('#count_twin').prop('checked', true);
                        }

                        var is_hotel = response.data.pax_count.hotel_only
                        if (is_hotel == 0 || is_hotel == null) {
                            // count_twin checkbox is not checked
                            $('#hotel_only_q').prop('checked', false);
                        } else {
                            // count_twin checkbox is checked
                            $('#hotel_only_q').prop('checked', true);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {

                    }
                });

                // }
            }

            $('#btn-ok').on('click', function() {
                location.reload();
                // creatTable(tour, tour_start, tour_end);
                // // nextprevious(tour, tour_start, tour_end);
                // searchFullDetails(tour, tour_start);
                // $("#trans_row_id").val(1);
            });

            $('#btn-ok-activity').on('click', function() {
                location.reload();
            });

        });


        var quote_finalized = 0;
        // Start Of Table Creation
        function creatTable(Tour_Id, start_date, end_date, jumpToUnfilledPage = false) {

            document.getElementById("consignee_form").style.display = "none";
            $('#transport_quotation_all').removeAttr('style');

            $('#date_table').attr('data-tourid', Tour_Id);

            // Clear the table before adding new data
            $('#date_table tbody').empty();

            var quotationDetails = '';
            var q_det = {};
            var t_fin = {};

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/get_tour_data/' + Tour_Id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {},
                success: function(response) {
                    quotationDetails = response.data;

                    var maxFinalize = -Infinity;
                    var t_fin = {};

                    $('#date_table tbody').empty();
                    $('#date_table tfoot').empty();

                    $('#printBtn').addClass('d-none');
                    $('#itineraryPrintBtn').addClass('d-none');
                    $('#email_btn').addClass('d-none');

                    // Ensure quotationDetails is an array if it's not empty
                    if (Array.isArray(quotationDetails)) {

                        quotationDetails.forEach(function(detail) {
                            q_det[detail.tour_date] = detail;

                            // Assuming 'finalize' is a key in each detail object
                            if (detail.finalize !== undefined) {
                                t_fin['finalize'] = detail.finalize;

                                // Check if this 'finalize' value is the maximum we've seen
                                if (detail.finalize > maxFinalize) {
                                    maxFinalize = detail.finalize;
                                }
                            }

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
                    var total_mileage = tot_price = rate = total_extra_mileage = tot_extra_price = tot_extra_activity = tot_km = 0;

                    var formattedDate = currentDate.toISOString().split('T')[0];
                    // $('#tdate').text(formattedDate);
                    // $('#real-date').text(formattedDate);
                    // $('#t_date').text(formattedDate);
                    // $('#t_date').val(formattedDate);

                    while (currentDate <= endDate) {
                        // alert(endDate);
                        // hotelload();
                        var row = '';
                        $("#table_detail").removeAttr('style');

                        var formattedDate = currentDate.toISOString().split('T')[0];

                        row = '<tr data-dateid="Tour-' + Tour_Id + '-' + index + '" data-realdate="' + formattedDate + '">';

                        var value = q_det[formattedDate] || {};

                        var transf, dis, extra_km;
                        var d_type = value.distance_type || 0;

                        if (d_type == 1) {
                            transf = value.transfer;
                            dis = value.distance;
                            extra_km = value.extra_kilo;
                        } else if (d_type == 2) {
                            transf = value.from_location;
                            to = value.to_location;
                            dis = value.distance;
                            extra_km = value.extra_kilo;
                        } else if (d_type == 0) {
                            transf = '';
                            to = '';
                            dis = '';
                            extra_km = '';
                        }

                        // Track the last transf record
                        // var lastTransfRow = null
                        if (transf !== '') {
                            lastTransfDate = formattedDate;
                            lastTransfRow = row;
                            lastTransfIndex = index;
                            lastTransfFound = true;
                        }

                        if (transf == '') {
                            if (unfilledDateSearchInc == 0) {
                                unfilledDate = formattedDate;
                                if (jumpToUnfilledPage) {
                                    processNext(unfilledDate);
                                    // break;
                                }
                            }
                            unfilledDateSearchInc++;
                        }

                        // Calculate totals
                        totalSgl += value.sgl ? parseFloat(value.sgl) : 0.00;
                        totalDbl += value.dbl ? parseFloat(value.dbl) : 0.00;
                        totalTpl += value.tpl ? parseFloat(value.tpl) : 0.00;

                        totalroomsqlrate = (totalSgl * roomcount_sgl);
                        totalroomdblrate = (totalDbl * roomcount_dbl);
                        totalroomtplrate = (totalTpl * roomcount_tpl);


                        row += '<td>' + index + '</td>';
                        row += '<td>' + formattedDate + '</td>';

                        // row += '<td>' + (value.distance_type ? value.distance_type : '') + '</td>';
                        // row += '<td>' + (value.code ? value.code : '') + '</td>';
                        row += '<td>' + (transf) + '</td>'
                        row += '<td>' + (to) + '</td>'
                        row += '<td>' + (dis) + '</td>';
                        row += '<td>' + (extra_km) + '</td>';

                        if (dis != '' && !isNaN(dis)) {
                            total_mileage += parseFloat(dis); // Add mileage to total mileage
                        }
                        if (extra_km != '' && !isNaN(extra_km)) {
                            total_extra_mileage += parseFloat(extra_km); // Add mileage to total mileage
                        }

                        if (value.extra_act_fee != '' && !isNaN(value.extra_act_fee)) {
                            tot_extra_activity += parseFloat(value.extra_act_fee); // Add mileage to total mileage
                        }
                        totalhotelcost = totalroomsqlrate + totalroomdblrate + totalroomtplrate + tot_extra_activity;

                        tot_price = (total_extra_mileage + total_mileage) * rate;
                        tot_km = (total_extra_mileage + total_mileage);
                        tot_extra_price = total_extra_mileage * rate;

                        // total = totalhotelcost + tot_price;

                        row += '<td>' + (value.hotel_name ? value.hotel_name : '') + '</td>';
                        // row += '<td>' + (value.hotel_code ? value.hotel_code : '') + '</td>';
                        row += '<td>' + (value.rtype ? value.rtype : '') + '</td>';
                        row += '<td>' + (value.meal_code ? value.meal_code : '') + '</td>';
                        row += '<td>' + (value.sgl ? value.sgl : '') + '</td>';
                        row += '<td>' + (value.dbl ? value.dbl : '') + '</td>';
                        row += '<td>' + (value.tpl ? value.tpl : '') + '</td>';

                        row += '<td>' + ((value.extra_act_fee) || 0.00) + '</td>';
                        if (value.finalize != 1) {
                            if (value.det_flag == 1) {
                                row += '<td><button type="button" class="btn btn-sm btn-success edit-info" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="2"> <i class="bi bi-pen"></i></button></td>';
                            } else {
                                row += '<td><button type="button" class="btn btn-sm btn-success add-info" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-type="2"> <i class="bi bi-plus"></i></button></td>';
                            }
                        } else {
                            row += '<td></td>';
                        }
                        if (value.finalize != 1) {
                            if (transf != '') {
                                row += '<td><button type="button" class="btn btn-sm btn-info add-activity" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-bs-toggle="modal" data-bs-target="#AddactivityModal"> <i class="bi bi-activity"></i></button></td>';
                            } else {
                                row += '<td></td>';
                            }
                        } else {
                            row += '<td></td>';
                        }

                        if (value.finalize != 1) {
                            if (transf != '') {
                                row += '<td><button type="button" class="btn btn-sm btn-info add-itinerary" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-bs-toggle="modal" data-bs-target="#AdditineraryModal"> <i class="bi bi-card-list"></i></button></td>';
                            } else {
                                row += '<td></td>';
                            }
                        } else {
                            row += '<td></td>';
                        }

                        if (value.finalize != 1) {
                            if (transf != '') {
                                row += '<td><button type="button" class="btn btn-sm btn-danger delete-record" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '"> <i class="bi bi-eraser"></i></button></td>';
                            } else {
                                row += '<td></td>';
                            }
                        } else {
                            row += '<td></td>';
                        }

                        if (currentDate.getTime() === endDate.getTime() && value.finalize != 1) {
                            row += '<td>';
                            row += '<button type="button" class="btn btn-sm btn-danger btn-remove" data-date="' + formattedDate + '" data-tour="' + Tour_Id + '" data-row="' + index + '" data-start="' + start_date + '" data-end="' + end_date + '">';
                            row += '<i class="bi bi-dash"> Row </i>';
                            row += '</button>';
                            row += '</td>';
                        } else {
                            row += '<td></td>';
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

                                var roomcount_sgl = value.consignee.sgl;
                                var roomcount_dbl = value.consignee.dbl;
                                var roomcount_tpl = value.consignee.tpl;
                            }
                        }

                        if (value.finalize == 1) {
                            quote_finalized++;
                        }
                    }

                    // Add the btn-copy button to the last transf record only if currentDate is NOT equal to endDate
                    if ((lastTransfRow) && value.finalize != 1) {
                        if (lastTransfDate !== end_date) {
                            var lastRow = $('#date_table tbody tr[data-dateid="Tour-' + Tour_Id + '-' + lastTransfIndex + '"]');
                            lastRow.find('td').last().prepend('<button type="button" class="btn btn-sm btn-warning btn-copy" data-date="' + lastTransfDate + '" data-tour="' + Tour_Id + '" data-row="' + lastTransfIndex + '" data-start="' + start_date + '" data-end="' + end_date + '"><i class="bi bi-copy"></i></button>');
                        } else {

                        }
                    }

                    // if quote_finalized == length of quotationDetails, then all quotes are finalized, enable printBtn, csvBtn, emailBtn
                    if (quote_finalized == quotationDetails.length && quotationDetails.length > 0) {
                        $('#printBtn').removeClass('d-none');
                        $('#itineraryPrintBtn').removeClass('d-none');
                        $('#email_btn').removeClass('d-none');
                    }

                    var tfoot = '';
                    // tfoot += '<tfoot>';
                    tfoot += '<tr>';
                    tfoot += '<td></td>'; // Merge rows for the first column
                    tfoot += '<td></td>'; // Merge rows for the first column
                    tfoot += '<td></td>'; // Merge rows for the first column
                    tfoot += '<td>KM & Extra KM </td>';
                    tfoot += '<td>' + parseFloat(total_mileage).toFixed(2) + '</td>';
                    tfoot += '<td>' + parseFloat(total_extra_mileage).toFixed(2) + '</td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td>Total</td>';
                    tfoot += '<td>' + parseFloat(totalSgl).toFixed(2) + '</td>';
                    tfoot += '<td>' + parseFloat(totalDbl).toFixed(2) + '</td>';
                    tfoot += '<td>' + parseFloat(totalTpl).toFixed(2) + '</td>';
                    tfoot += '<td>' + parseFloat(tot_extra_activity).toFixed(2) + '</td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '</tr>';

                    tfoot += '<tr>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td> Total Mile</td>';
                    tfoot += '<td>' + parseFloat(tot_km).toFixed(2) + '</td>';
                    tfoot += '<td>Total Transport Cost</td>';
                    tfoot += '<td>' + parseFloat(tot_price).toFixed(2) + '</td>';
                    tfoot += '<td></td>';
                    tfoot += '<td> Total </td>';
                    tfoot += '<td></td>';
                    tfoot += '<td>' + parseFloat(totalroomsqlrate).toFixed(2) + '</td>';
                    tfoot += '<td>' + parseFloat(totalroomdblrate).toFixed(2) + '</td>';
                    tfoot += '<td>' + parseFloat(totalroomtplrate).toFixed(2) + '</td>';
                    tfoot += '<td>' + parseFloat(tot_extra_activity).toFixed(2) + '</td>';
                    tfoot += '<td><button type="button" class="btn btn-sm btn-warning btn-add" data-tour="' + Tour_Id + '" data-start="' + start_date + '" data-end="' + end_date + '"';
                    tfoot += maxFinalize == 1 ? ' style="display:none;">' : '>';
                    tfoot += '<i class="bi bi-plus"> Row</i></button></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '</tr>';

                    tfoot += '<tr>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td> Transport Cost (LKR) </td>';
                    tfoot += '<td>' + parseFloat(tot_price).toFixed(2) + '</td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td> Total Cost ($) </td>';
                    tfoot += '<td>' + parseFloat(totalhotelcost).toFixed(2) + '</td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td><button type="button" class="btn btn-md btn-danger btn-finish" data-tour="' + Tour_Id + '" data-start="' + start_date + '" data-end="' + end_date + '"';
                    tfoot += maxFinalize == 1 ? ' style="display:none;">' : '>';
                    tfoot += 'Finalize</button></td>';
                    tfoot += '<td></td>';
                    tfoot += '<td></td>';
                    tfoot += '</tr>';

                    // tfoot += '</tfoot>';

                    $('#date_table tfoot').empty();
                    $('#date_table tbody').append(tfoot);

                    if (maxFinalize == 1) {
                        if ($.fn.dataTable.isDataTable('#date_table')) {
                            table = $('#date_table').DataTable();
                            table.destroy();
                        }

                        // var table = $('#date_table').DataTable({

                        //     "paging": false,
                        //     "info": false,
                        //     "searching": false,
                        //     "ordering": false,
                        //     "scrollCollapse": true,
                        //     "scrollX": true,
                        //     "autoWidth": false,
                        //     "responsive": true,
                        //     "dom": 'Bfrtip',
                        //     "buttons": [{
                        //             extend: 'csvHtml5',
                        //             text: '<i class="bi bi-file-earmark-spreadsheet-fill"></i> CSV',
                        //             className: 'btn btn-primary border-0',
                        //             title: 'Quotation ' + Tour_Id,
                        //             attr: {
                        //                 id: 'hiddenCsvBtn'
                        //             },
                        //             footer: true,
                        //             exportOptions: {
                        //                 columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
                        //             }
                        //         },
                        //         {
                        //             extend: 'print',
                        //             text: '<i class="bi bi-printer-fill"></i> Print',
                        //             className: 'btn btn-primary border-0',
                        //             attr: {
                        //                 id: 'hiddenPrintBtn'
                        //             },
                        //             footer: true,
                        //             exportOptions: {
                        //                 columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
                        //             }
                        //         }
                        //     ],

                        // });

                        // Hide the buttons
                        // table.buttons().container().css('display', 'none');
                        // table.buttons().enable();

                        // $('#csvBtn').on('click', function() {
                        //     table.button('.buttons-csv').trigger();
                        // });

                        // $('#printBtn').on('click', function() {
                        //     table.button('.buttons-print').trigger();
                        // });

                        // add Resevation card print button
                        $('#printBtn').on('click', function() {
                            var tour_id = $('#tournum').text();
                            var tour_name = $('#tourname').text();
                            var manual_id = $('#mtournum').text();
                            var start_date = $('#start_d').val();
                            var end_date = $('#end_d').val();
                            var pax_count = $('#pax_cc').val();
                            var sgl = $('#count_sgl').val();
                            var dbl = $('#count_dbl').val();
                            var tpl = $('#count_tpl').val();
                            var twin = $('#count_twin').is(':checked') ? 1 : 0;
                            var hotel_only = $('#hotel_only_q').is(':checked') ? 1 : 0;

                            var url = "<?php echo base_url(); ?>tour/print?tour_id=" + tour_id;
                            window.location.href = url;
                        });

                        // add Itinerary print button
                        $('#itineraryPrintBtn').on('click', function() {
                            var tour_id = $('#tournum').text();
                            var tour_name = $('#tourname').text();
                            var manual_id = $('#mtournum').text();
                            var start_date = $('#start_d').val();
                            var end_date = $('#end_d').val();
                            var pax_count = $('#pax_cc').val();
                            var sgl = $('#count_sgl').val();
                            var dbl = $('#count_dbl').val();
                            var tpl = $('#count_tpl').val();
                            var twin = $('#count_twin').is(':checked') ? 1 : 0;
                            var hotel_only = $('#hotel_only_q').is(':checked') ? 1 : 0;

                            var url = "<?php echo base_url(); ?>tour/print/itinerary?tour_id=" + tour_id;
                            window.location.href = url;
                        });
                    }

                    // Attach click event to the buttons (after table is fully created)
                    // $('.add-transport').on('click', function() {
                    //     var date = $(this).data('date');
                    //     var tour = $(this).data('tour');
                    //     var row = $(this).data('row');
                    //     $('#modal-date-info1').text('Selected date: ' + date);
                    //     $("#trans_date").val(date);
                    //     $("#trans_tour_id").val(tour);
                    //     $("#trans_row_id").val(row);

                    //     $("#transport_quotation").show();
                    // });

                    // $('.edit-transport').on('click', function() {
                    //     var date = $(this).data('date');
                    //     var tour = $(this).data('tour');
                    //     var row = $(this).data('row');
                    //     var dis_type = $(this).data('distype');
                    //     var dist = $(this).data('distance');
                    //     // var transfer_code = $(this).data('transfer_code');
                    //     var transfer = $(this).data('transfer');
                    //     var froml = $(this).data('from');
                    //     var tol = $(this).data('to');
                    //     $('#modal-date-info1').text('Selected date: ' + date);
                    //     $("#trans_date").val(date);
                    //     $("#trans_tour_id").val(tour);
                    //     $("#trans_row_id").val(row);
                    //     $("#flg").val(2);

                    //     $("#transport_quotation").show();
                    //     // Check which radio button to display based on dis_type
                    //     toggleElements_new(dis_type);

                    // });

                    $('.add-info').on('click', function() {
                        var date = $(this).data('date');
                        var tour = $(this).data('tour');
                        var add_row = $(this).data('row');


                        $("#trans_row_id").val(add_row);

                        searchFullDetails(tour, date);


                    });


                    $('.edit-info').on('click', function() {

                        var date = $(this).data('date');
                        var tour = $(this).data('tour');
                        var edit_row = $(this).data('row');

                        $("#trans_row_id").val(edit_row);
                        // Scroll to the top of the page
                        $("html, body").animate({
                            scrollTop: 0
                        }, "fast");

                        searchFullDetails(tour, date);
                        hotelload();

                    });

                    $('.add-activity').on('click', function() {

                        var date = $(this).data('date');
                        var tour = $(this).data('tour');
                        var row = $(this).data('row');

                        $("#activity_tour_id").val(tour);
                        $("#activity_date").val(date);
                        $("#activity_row").val(row);

                        load_activity(tour, row, date);

                    });

                    $('.add-itinerary').on('click', function() {

                        var date = $(this).data('date');
                        var tour = $(this).data('tour');
                        var row = $(this).data('row');

                        $("#itinerary_tour_id").val(tour);
                        $("#itinerary_date").val(date);
                        $("#itinerary_row").val(row);

                        load_itinerary(tour, row, date);

                    });

                    $('.delete-record').on('click', function() {
                        var date = $(this).data('date');
                        var tour = $(this).data('tour');
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
                                    url: "<?php echo base_url(); ?>tour/tour/delete",
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
                                                    location.reload();
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
                                        // Swal.fire({
                                        //     icon: 'error',
                                        //     title: 'Oops...',
                                        //     text: 'Something went wrong!',
                                        // });
                                    }
                                });
                            }
                        });

                    });

                    $('.btn-copy').on('click', function() {
                        var date = $(this).data('date');
                        var tour = $(this).data('tour');
                        var start_date = $(this).data('start');
                        var end_date = $(this).data('end');
                        var row = $(this).data('row');

                        var start, end, nextDate;

                        var curDate = new Date(date);

                        // Initialize nextDate as a new Date object
                        nextDate = new Date(curDate);
                        nextDate.setDate(curDate.getDate() + 1);
                        var nextd = nextDate.toISOString().split('T')[0];

                        var endDate = new Date(end_date);
                        endDate.setDate(endDate.getDate() + 1);
                        var formattedEndDate = endDate.toISOString().split('T')[0];

                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/tour/tour_details_copy',
                            type: 'POST',
                            data: {
                                tour: tour,
                                row: row,
                                date: date,
                                nextd: nextd
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
                                        if (result.isConfirmed) {
                                            window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + start_date + "&end=" + end_date;
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Not Possible',
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

                        var endDate = new Date(end_date);
                        endDate.setDate(endDate.getDate() - 1);

                        var formattedEndDate = endDate.toISOString().split('T')[0];

                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/tour/updateEndDate/' + formattedEndDate + '/' + tour,
                            type: 'POST',
                            dataType: 'json',
                            beforeSend: function() {

                            },
                            success: function(response) {
                                if (response.status == 200) {
                                    window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + start_date + "&end=" + formattedEndDate;
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Not Possible',
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

                    $(".btn-add").on('click', function() {
                        var tour = $(this).data('tour');
                        var start_date = $(this).data('start');
                        var end_date = $(this).data('end');

                        var endDate = new Date(end_date);
                        endDate.setDate(endDate.getDate() + 1);

                        var formattedEndDate = endDate.toISOString().split('T')[0];

                        $.ajax({
                            url: '<?php echo base_url(); ?>tour/tour/updateEndDate/' + formattedEndDate + '/' + tour,
                            type: 'POST',
                            dataType: 'json',
                            beforeSend: function() {

                            },
                            success: function(response) {
                                if (response.status == 200) {
                                    window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + start_date + "&end=" + formattedEndDate;
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Not Possible',
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
                                            window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + start_date + "&end=" + end_date;
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

        $('#sub_location').on('click', function() {
            var tour = $("#trans_tour_id").val();
            var row = $("#trans_row_id").val();
            var date = $("#t_date").val();

            var pick_location = $("#pick_location").val();
            var pick_lat = $("#pick_lat").val();
            var pick_long = $("#pick_long").val();
            var pick_city = $("#pick_city").val();

            var drop_location = $("#drop_location").val();
            var drop_lat = $("#drop_lat").val();
            var drop_long = $("#drop_long").val();
            var drop_city = $("#drop_city").val();

            var gdistance = $("#gdistance").val();
            var distance = $("#distance").val();
            var extrakm = $("#extrakm").val();

            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>tour/sub_locations/create",
                data: {
                    tour: tour,
                    row: row,
                    date: date,
                    pick_location: pick_location,
                    pick_lat: pick_lat,
                    pick_long: pick_long,
                    drop_location: drop_location,
                    drop_lat: drop_lat,
                    drop_long: drop_long,
                    gdistance: gdistance,
                    distance: distance,
                    extrakm: extrakm
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
                                load_next_location(tour, row, date);
                                location.reload();
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

        });

        function load_next_location(tour, row, date) {
            flag = $("#up_flag").val();

            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>tour/sub_locations/get_next_location",
                data: {
                    tour: tour,
                    row: row,
                    date: date
                },
                dataType: "json",
                success: function(response) {
                    if (response.status == 200) {

                        // var dates = response.dates;
                        // var rows = response.rows;
                        var sub_locations = response.sub_locations;

                        // console.log(rows);
                        // console.log(sub_locations);
                        // append new tables to the sl_tbls div for each day

                        $('#sub_location_table').empty();

                        if (sub_locations.length > 0) {
                            $('#table_sub_location').removeClass('d-none');
                        } else {
                            $('#table_sub_location').addClass('d-none');
                        }

                        $.each(sub_locations, function(index, value) {
                            var date_sub_locations = '';

                            var table_date = value.date_id;
                            var last_to = '';
                            var ii = 0;

                            // Create the main table row for sub_location
                            date_sub_locations += `
        <tr>
            <td>${value.from}</td>
            <td>${value.to}</td>
            <td>${value.km}</td>
            <td>${value.extra_km || '--'}</td>
            <td>
                <button type="button" class="btn btn-sm btn-warning" onClick="add_act(${value.ref_no})">
                    <i class="bi bi-activity"></i>
                </button>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger" onClick="delete_location(${value.id},${value.ref_no})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
            <td>`
                            // Check if the activities array is not empty before creating the nested table
                            if (value.activity && value.activity.length > 0) {
                                var activity_table = `
                                            <table class="sub-table" style="width: 100%">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;

                                // Iterate over the activities for each sub_location and add them to the nested table
                                $.each(value.activity, function(actIndex, actValue) {
                                    activity_table += `
                                        <tr>
                                            <td style="width: 80%">${actValue.description}</td>
                                            <td>
                                            <button type="button" class="btn btn-sm btn-light" style="background-color: #98bee3;" onClick="delete_loc_activity(${actValue.id},${actValue.ref_no})">
                                            <i class="bi bi-trash"></i>
                                            </button>
                                            </td>
                                        </tr>`;
                                });

                                activity_table += `
                                        </tbody>
                                    </table>
                                `;
                            } else { // If no activities are found, set the activity_table to an empty string
                                activity_table = '';
                            }
                            date_sub_locations += activity_table;
                            `</td> 
        </tr>`;
                            ii++;
                            last_to = value.to;
                            // alert(last_to);
                            // console.log(last_to);

                            // Append the constructed rows to the main table
                            $('#sub_location_table').append(date_sub_locations);
                            $('#pick_location').val(last_to).change();
                            setTimeout(function() {
                                $('#pick_location').focus();
                            }, 200);

                            // Handle flag logic for resetting input fields
                            if (flag == 0) {
                                $('#drop_location').val('');
                                $('#gdistance').val('');
                                $('#distance').val('');
                                $('#extrakm').val('');
                                $('#pick_city').val('');
                                $('#drop_city').val('');
                            }
                        });


                        if (sub_locations.length == 0) {
                            var prev_desting = $('#prev_destin').text();
                            $('#pick_location').val(prev_desting).focus();
                            // $('#table_sub_location').addClass('d-none');
                        }


                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.message,
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Swal.fire({
                    //     icon: 'error',
                    //     title: 'Oops...',
                    //     text: 'Something went wrong!',
                    // });
                }
            });

        }

        function delete_location(id, ref_no) {

            var id = id;
            var modal_tour_id = $('#modal_tour_id').val();
            var tour = $('#trans_tour_id').val();
            var row = $('#trans_row_id').val();
            var date = $('#t_date').val();


            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this Location!",
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
                                        // load_passenger(modal_tour_id);
                                        // $('#placetovisit').empty();
                                        // $('#activity_rate').val('');
                                        load_next_location(tour, row, date)
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
                            // Swal.fire({
                            //     icon: 'error',
                            //     title: 'Oops...',
                            //     text: 'Something went wrong!',
                            // });
                        }
                    });
                }
            });
        }


        function add_act(ref) {

            var tour = $('#trans_tour_id').val();
            var row = $('#trans_row_id').val();
            var date = $('#t_date').val();

            $('#activity_location_tour_id').val(tour);
            $('#activity_location_date').val(date);
            $('#activity_location_row').val(row);
            $('#loc_ref').val(ref);

            // load_activity(tour, row, date);
            $('#AddactivitylocationModal').modal('show');
        }

        // room_type enter press
        $('#passenger').keydown(function(e) {
            if (e.keyCode == 13) {
                $('#add_passenger').click();
            }
        });

        // add_ship button
        $('#add_passenger').on('click', function() {
            var passenger = $('#passenger').val();
            var modal_tour_id = $('#modal_tour_id').val();
            var pax_coun = $('#pax_cc').val();

            if (passenger == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Name is required!',
                });
            } else {
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>tour/tour/pax_create",
                    data: {
                        passenger: passenger,
                        modal_tour_id: modal_tour_id,
                        pax_coun: pax_coun
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
                                    load_passenger(modal_tour_id);
                                    $('#passenger').val('');
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

        function load_passenger(modal_tour_id) {

            $.ajax({
                type: "GET",
                url: "<?php echo base_url(); ?>tour/tour/pax_all/" + modal_tour_id,
                dataType: "json",
                success: function(response) {

                    $('#passenger_details').empty();
                    $.each(response, function(index, value) {
                        $('#passenger_details').append(
                            '<tr>' +
                            '<th scope="row">' + (index + 1) + '</th>' +
                            '<td>' + value.pass_name + '</td>' +
                            '<td style="width: 25%;">' +
                            '<button type="button" class="btn btn-sm btn-danger" onClick="delete_pax(' + value.id + ')"><i class="bi bi-trash"></i></button>' +
                            // '<button type="button" class="btn btn-sm btn-warning" onClick="edit_pax(' + value.id + ')"><i class="bi bi-pencil"></i></button>' +
                            '</td>' +
                            '</tr>'
                        );
                        // $('#roomtype').append(
                        //     '<option value="' + value.id + '">' + value.rtype + '</option>'
                        // );
                    });
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

        function delete_pax(id) {

            var pax_id = id;
            var modal_tour_id = $('#modal_tour_id').val();

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this Passenger!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "get",
                        url: "<?php echo base_url(); ?>tour/tour/pax_delete",
                        data: {
                            id: id
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
                                        load_passenger(modal_tour_id);
                                        // $('#placetovisit').empty();
                                        // $('#activity_rate').val('');
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
        }

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
            // hotel_only();
        });

        // Hotel Only change function 
        $('#hotel_only_q').on('change', function() {
            // is checked or not in the hotel only chechbox
            if ($(this).is(':checked')) {
                // $('#hotel_only').val(1);
                $('#is_hotel').val(1);
                $('#vehicle').removeAttr('required');
            } else {
                // $('#hotel_only').val(0);
                $('#is_hotel').val(0);
            }
            hotel_only();
        });

        // Hotel Only Update
        function hotel_only() {
            var tour_id = $('#trans_tour_id').val();
            var start_date = $('#t_date').val();
            var end_date = $('#end_d').val();
            var hotel_only = $('#is_hotel').val();
            alert(hotel_only);

            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>tour/tour/hotel_only",
                data: {
                    tour_id: tour_id,
                    hotel_only: hotel_only
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
                                creatTable(tour_id, start_date, end_date);
                                processNext();
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

        $('#twin').on('change', function() {
            // is checked or not in the hotel only chechbox
            if ($(this).is(':checked')) {
                $('#is_twin').val(1);
            } else {
                $('#is_twin').val(0);
            }
        });

        $('#count_twin').on('change', function() {
            // is checked or not in the hotel only chechbox
            if ($(this).is(':checked')) {
                $('#is_twin2').val(1);
            } else {
                $('#is_twin2').val(0);
            }
        });

        // Add Room Button click
        $('#add_room').on('click', function() {

            var tour_id = $('#trans_tour_id').val();
            var sgl_room = $('#count_sgl').val();
            var dbl_room = $('#count_dbl').val();
            var tpl_room = $('#count_tpl').val();
            var is_twn = $('#is_twin2').val();

            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>tour/tour/room_count_add",
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
                                location.reload();
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

        // room_type enter press
        // $('#passenger').keydown(function(e) {
        //     if (e.keyCode == 13) {
        //         $('#add_passenger').click();
        //     }
        // });
        function ratedisplay(activity) {
            var place_rate = $('#placetovisit option:selected').data('fee');
            $('#activity_rate').val(place_rate);
        }

        // add activity button
        $(document).on('click', '.add_activity', function() {

            var place = $('#placetovisit').val();
            var place_name = $('#placetovisit option:selected').text();
            var place_fee = $('#activity_rate').val();
            var place_code = $('#placetovisit option:selected').data('code');
            var pax_counts = $('#pax_cc_activity').val();
            // alert(place_fee);
            var activity_tour_id = $('#activity_tour_id').val();
            var activity_date = $('#activity_date').val();
            var activity_row = $('#activity_row').val();
            var tou_start = $('#t_date').val();
            var tou_end = $('#end_d').val();


            if (place == '' || activity_tour_id == '' || activity_date == '' || activity_row == '' || pax_counts == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Missing Important Value!',
                });
            } else {
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>tour/tour/activity_create",
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
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    load_activity(activity_tour_id, activity_row, activity_date);
                                    $("#placetovisit").val('').change();
                                    $('#activity_rate').val('');
                                    // creatTable(activity_tour_id, tou_start, tou_end);
                                    // location.reload();
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

        $(document).on('click', '.add_activity_loc', function() {

            var place = $('#placetovisitloc').val();
            var place_name = $('#placetovisitloc option:selected').text();
            var place_code = $('#placetovisitloc option:selected').data('code');
            var location_ref = $('#loc_ref').val();
            // alert(place_fee);
            var activity_tour_id = $('#activity_location_tour_id').val();
            var activity_date = $('#activity_location_date').val();
            var activity_row = $('#activity_location_row').val();
            var tou_start = $('#t_date').val();
            var tou_end = $('#end_d').val();


            if (place == '' || activity_tour_id == '' || activity_date == '' || activity_row == '' || location_ref == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Missing Important Value!',
                });
            } else {
                $.ajax({
                    type: "POST",
                    url: "<?php echo base_url(); ?>tour/tour/activity_create_loc",
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
                        if (response.status == 201) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // load_activity(activity_tour_id, activity_row, activity_date);
                                    // $("#placetovisit").val('').change();
                                    // $('#activity_rate').val('');
                                    // creatTable(activity_tour_id, tou_start, tou_end);
                                    location.reload();

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

        function load_activity(activity_tour_id, activity_row, activity_date) {

            $.ajax({
                type: "GET",
                url: "<?php echo base_url(); ?>tour/tour/activity_all/" + activity_tour_id + '/' + activity_row + '/' + activity_date,
                dataType: "json",
                success: function(response) {

                    $('#activity_details').empty();
                    $.each(response, function(index, value) {
                        $('#activity_details').append(
                            '<tr>' +
                            '<th scope="row">' + (index + 1) + '</th>' +
                            '<td>' + value.place_name + '</td>' +
                            '<td>' + (value.code || '') + '</td>' +
                            '<td>' + value.fee + '</td>' +
                            '<td style="width: 25%;">' +
                            '<button type="button" class="btn btn-sm btn-danger" onClick="delete_act(' + value.id + ')"><i class="bi bi-trash"></i></button>&nbsp;&nbsp;' +
                            // '<button type="button" class="btn btn-sm btn-warning" onClick="edit_pax(' + value.id + ')"><i class="bi bi-pencil"></i></button>' +
                            '</td>' +
                            '</tr>'
                        );
                        // $('#roomtype').append(
                        //     '<option value="' + value.id + '">' + value.rtype + '</option>'
                        // );
                    });
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

        function delete_act(id) {

            var activity_tour_id = $('#activity_tour_id').val();
            var activity_date = $('#activity_date').val();
            var activity_row = $('#activity_row').val();

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
                        url: "<?php echo base_url(); ?>tour/tour/activity_delete",
                        data: {
                            id: id
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
                                        load_activity(activity_tour_id, activity_row, activity_date);
                                        $('#placetovisit').empty();
                                        $('#activity_rate').val('');
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
        }

        function delete_loc_activity(id, ref_no) {

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
                        url: "<?php echo base_url(); ?>tour/tour/activity_loc_delete",
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
                                        // load_passenger(modal_tour_id);
                                        // $('#placetovisit').empty();
                                        // $('#activity_rate').val('');
                                        // load_next_location(tour, row, date)
                                        location.reload();
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
                            // Swal.fire({
                            //     icon: 'error',
                            //     title: 'Oops...',
                            //     text: 'Something went wrong!',
                            // });
                        }
                    });
                }
            });
        }


        // add itinerary button
        function add_itinerary(order_id, tour, row) {

            $('#Additinerary').modal('show');

            $('#i_order_id').val(order_id);
            $('#i_tour_id').val(tour);
            $('#i_row').val(row);
            $('#i_date').val($('#itinerary_date').val());
        }

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
                    text: 'Missing Important Value!',
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

        // $(document).on('click', '.add_itinerary_table', function() {
        //     var index = $(this).data('index');
        //     var time = $('.time_input[data-index="' + index + '"]').val();
        //     var itinerary = $(this).data('itinerary');
        //     // alert(place_fee);
        //     var itinerary_tour_id = $('#itinerary_tour_id').val();
        //     var itinerary_date = $('#itinerary_date').val();
        //     var itinerary_row = $('#itinerary_row').val();
        //     alert(itinerary);
        //     alert(time);
        //     if (time == '' || itinerary == '') {
        //         Swal.fire({
        //             icon: 'error',
        //             title: 'Oops...',
        //             text: 'Missing Important Value!',
        //         });
        //     } else {
        //         $.ajax({
        //             type: "POST",
        //             url: "<?php echo base_url(); ?>tour/tour/itinerary_create",
        //             data: {
        //                 time: time,
        //                 itinerary: itinerary,
        //                 itinerary_tour_id: itinerary_tour_id,
        //                 itinerary_date: itinerary_date,
        //                 itinerary_row: itinerary_row
        //             },
        //             dataType: "json",
        //             success: function(response) {
        //                 if (response.status == 201) {
        //                     Swal.fire({
        //                         icon: 'success',
        //                         title: 'Success',
        //                         text: response.message,
        //                     }).then((result) => {
        //                         if (result.isConfirmed) {
        //                             load_itinerary(itinerary_tour_id, itinerary_row, itinerary_date);

        //                         }
        //                     });
        //                 } else {
        //                     Swal.fire({
        //                         icon: 'error',
        //                         title: 'Oops...',
        //                         text: response.message,
        //                     });
        //                 }
        //             },
        //             error: function(jqXHR, textStatus, errorThrown) {
        //                 Swal.fire({
        //                     icon: 'error',
        //                     title: 'Oops...',
        //                     text: 'Something went wrong!',
        //                 });
        //             }
        //         });
        //     }
        // });


        function load_itinerary(tour, row, date) {

            $.ajax({
                type: "GET",
                url: "<?php echo base_url(); ?>tour/tour/itinerary_all/" + tour + '/' + row + '/' + date,
                dataType: "json",
                success: function(response) {

                    $('#itinerary_details').empty();
                    var i = 1;
                    $.each(response, function(index, value) {
                        var timeInputId = 'time-input';
                        // Handle case when itinerary is empty or does not exist
                        if (value.description != '') {
                            // alert(date);
                            $('#itinerary_details').append(
                                '<tr>' +
                                '<th scope="row">' + i + '</th>' +
                                '<td>' + value.tour_date + '</td>' +
                                '<td>' + (value.description) + '</td>' +
                                '<td>' + (value.area) + '</td>' +
                                '<td>' + (value.bf) + '</td>' +
                                '<td>' + (value.lunch) + '</td>' +
                                '<td>' + (value.dinner) + '</td>' +
                                '<td>' +
                                '<button type="button" class="btn btn-sm btn-danger" onClick="delete_itinerary(' + value.id + ')"><i class="bi bi-trash"></i></button>&nbsp;&nbsp;' +
                                '<button type="button" class="btn btn-sm btn-success" onClick="add_itinerary(' + value.order_id + ', ' + tour + ',' + row + ')"><i class="bi bi-plus"></i></button>' +
                                '</td>' +
                                '</tr>'
                            );

                            i++;
                        }
                    });
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

        // delete itinerary
        function delete_itinerary(id) {

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
                            // Swal.fire({
                            //     icon: 'error',
                            //     title: 'Oops...',
                            //     text: 'Something went wrong!',
                            // });
                        }
                    });
                }
            });
        }



        function resetFormSelections() {

            // var hotelCodeChangedCalled = false;
            var initialHotelOptions = $("#hotel").html();
            $("#hotel").html(initialHotelOptions);

            $("#hotel_code").empty().append('<option value=""></option>');
            $("#meal_code").prop('selectedIndex', 0);

        }

        function assign_hotel(hotelId) {

            $("#slct_htl").val(hotelId);
            var date = $("#t_date").val();

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

        $("#next").click(function() {
            processNext();
        });

        $("#previous").click(function() {
            var st_date = $('#start_d').val();
            var en_date = $('#end_d').val();
            var tu_date_text = $('#tdate').text();

            var tu_date = new Date(tu_date_text);
            var start_date = new Date(st_date);

            var tour = $("#trans_tour_id").val();
            var r_id = $("#trans_row_id").val();

            if (tu_date > start_date) {
                tu_date.setDate(tu_date.getDate() - 1);
                r_id = parseInt(r_id, 10) - 1;
                var incremented_date = tu_date.toISOString().split('T')[0];
                $('#tdate').text(incremented_date);
                $("#real-date").text(incremented_date);
                $('#t_date').val(incremented_date);
                $('#t_date').text(incremented_date);
                $('#trans_row_id').val(get_date_place(st_date, en_date, incremented_date));

                searchFullDetails(tour, incremented_date);


            } else {
                window.location.href = "<?php echo base_url(); ?>tour/tour?tour_id=" + tour + "&start=" + st_date + "&end=" + en_date;
            }

        });

        // Document Ready Add Quatation Details
        // $(document).ready(function() {

        $("#quotation_details_all").submit(function(e) {
            e.preventDefault();
            var hotel = $("#hotel").val();
            var start_date = $("#start_date").val();
            var end_date = $("#end_date").val();

            var t_date = $("#t_date").val();

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/Quatation',
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


                            var row_id = parseInt(r_id, 10) + 1;

                            $("#trans_row_id").val(row);
                            // var button = $('button[data-tour="' + t_id + '"][data-row="' + r_id + '"][data-type="2"]');
                            location.reload();

                            creatTable(t_id, start_date, end_date);
                            processNext();
                        });


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


        function mealCodeChanged(mplan) {

            var id = mplan;
            // var hotel_code = $('#hotel_code').val();
            // var slct_h_code = $("#slct_htl_c").val();
            var room_type = $('#room_type').val();
            var hotel = $('#hotel').val();
            var select_h = $('#select_h').val();
            var h_code = "";

            if (hotel == '' || hotel == null) {
                h_code = select_h;
            } else {
                h_code = hotel;
            }
            // alert(room_type);
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


        // Submit form data via Ajax
        $('#form_submit').submit(function(e) {
            e.preventDefault();

            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var Tour_Id = 0;

            // if start_date is > end_date, return
            if (start_date > end_date) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Start Date should be less than End Date',
                });
                return;
            }
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
        // });

        function hotelload() {
            var t = $('#trans_tour_id').val();
            var drop = $('#drop_location').val();
            drop = drop.replace(', Sri Lanka', '').trim();
            var words = drop.split(" ");
            var lastWord = $('#drop_city').val();
            // alert(lastWord);
            if (lastWord == '') {
                lastWor = words;
            } else {
                lastWor = lastWord;
            }
            var selectdate = $('#tdate').text();
            var select_date = new Date(selectdate);
            var selectrow = '';
            // alert(lastWor);
            searchHotellocation(t, selectdate, lastWor);
            // searchHotelCodelocation(t, selectdate, lastWord);
        }

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

            var manual_tour_id = $("#manual_id").val();
            var agent = $("#agent").val();
            var agent_name = $("#agent option:selected").text();
            var arrival_flight = $("#arrival_flight").val();
            var arrival_flight_time = $("#arrival_flight_time").val();
            var departure_flight = $("#departure_flight").val();
            var departure_flight_time = $("#departure_flight_time").val();
            var hotel_only = $("#is_hotel").val();
            var csgl = $("#roomsgl").val();
            var cdbl = $("#roomdbl").val();
            var ctpl = $("#roomtpl").val();
            var is_twin = $("#is_twin").val();

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
                    pass_count: pass_count,
                    manual_tour_id: manual_tour_id,
                    agent: agent,
                    agent_name: agent_name,
                    arrival_flight: arrival_flight,
                    departure_flight: departure_flight,
                    arrival_flight_time: arrival_flight_time,
                    departure_flight_time: departure_flight_time,
                    hotel_only: hotel_only,
                    sgl: csgl,
                    dbl: cdbl,
                    tpl: ctpl
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

        function searchHotellocation(selecttour, selectdate, location) {

            var tour = selecttour;
            var date = selectdate;
            var loc = location;

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/searchHotellocation/' + tour + '/' + loc + '/' + date,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {

                },
                success: function(response) {

                    $("#search_location").val(loc).trigger('change');
                    $("#final_destination").text(loc);

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
                    // Swal.fire({
                    //     icon: 'error',
                    //     title: 'Oops...',
                    //     text: 'Did not find any hotels for the selected location! Please find it manually.',
                    // });

                }
            });
        }

        function searchHotelCodelocation(selecttour, selectdate, location) {
            var tour = selecttour;
            var date = selectdate;
            var loc = location;

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/search_hotel_code_loc/' + tour + '/' + loc + '/' + date,
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


        // Function to show/hide elements based on distance type
        function toggleElements() {
            distanceType = document.querySelector('input[name="distance_type1"]:checked').value;
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
                // document.getElementById('transfer_code').removeAttribute('required');

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

        });

        // Initial call to toggleElements to set initial state
        toggleElements();



        $("#search_location").on('change', function(e) {
            e.preventDefault();

            var search_location = $("#search_location").val();
            var date = $("#t_date").val();

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

                        $("#select_h").text("");
                        // $("#select_h_code").text("");
                        document.getElementById('select_h').parentElement.style.display = 'none';
                        // document.getElementById('select_h_code').parentElement.style.display = 'none';

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

                        // $('#hotel_code').empty();

                        // // Append empty option
                        // $('#hotel_code').append('<option value="">Select a hotel code</option>');

                        // // Append new options
                        // $.each(data.data.hotel_code, function(index, value) {
                        //     $('#hotel_code').append(
                        //         '<option value="' + value.hotel_code + '">' + value.hotel_code + '</option>'
                        //     );
                        // });


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

        //passenger_count change
        function load_vehicles(count) {

            $.ajax({
                url: '<?php echo base_url('tour/tour/load_vehicles') ?>',
                type: 'GET',
                data: {
                    count: count,
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {

                        $('#vehicle').empty();

                        // Append empty option
                        $('#vehicle').append('<option value="">Select</option>');


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



        //edit or load data
        function searchFullDetails(tour, selectDate, manual_id) {

            // Validate tour and selectDate
            if (!tour || !selectDate) {
                return;
            }

            $("#tournum").text(tour);
            $("#mtournum").text(manual_id);
            $("#tdate").text(selectDate);
            $("#real-date").text(selectDate);

            $('#t_date').val(selectDate);
            $('#t_date').text(selectDate);



            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/searchFull/' + tour + '/' + selectDate,
                type: 'POST',
                dataType: 'json',
                beforeSend: function() {
                    // Optional: Add a loading spinner or disable inputs
                },
                success: function(response) {
                    if (response.status === 200) {
                        if (response.data[0]) {
                            const data = response.data[0];

                            if (data.hotel_acc != "") {
                                // $("#transfer_code").val(data.transfer_code).trigger('change');
                                $("#pick_location").val(data.from_location || '');
                                $("#drop_location").val(data.to_location || '');
                                // $("#drop_city").val(data.to_location || '');
                                $("#gdistance").val(data.gdistance || '');
                                $("#distance").val(data.distance || '');
                                $("#hotel").val(data.hotel_acc || '');
                                $("#select_h").text(data.hotel_name || '');
                                $("#hotel_code").val(data.hotel_code || '');
                                $("#select_h_code").text(data.hotel_code || '');
                                $("#slct_htl").val(data.hotel_acc || '');
                                $("#slct_htl_c").val(data.hotel_code || '');
                                $("#room_type").val(data.rtype || '');
                                $("#select_rt").text(data.rtype || '');
                                $('#room_type_id').val(data.room_type || '');
                                $("#meal_code").val(data.meal_code || '');
                                $("#sgl").val(data.sgl || '');
                                $("#dbl").val(data.dbl || '');
                                $("#tpl").val(data.tpl || '');
                                $("#up_flag").val(1);
                                $("#final_destination").text(data.to_location || '');
                                $("#extrakm").text(data.extra_kilo || '');

                            }
                        } else {
                            // $("#pick_location").val(response.data.previous_destination || '');
                            // focus pick_city

                            $("#prev_destin").text(response.data.previous_destination);

                            document.getElementById('display_destination').style.display = 'block';
                            clearFields();
                        }

                        var tour = $("#tid").val();
                        var date = $("#t_date").val();
                        var row = $("#trans_row_id").val();
                        load_next_location(tour, row, date);
                        $("#search_location").val('').trigger('change');

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
                },
                complete: function() {
                    // Optional: Hide loading spinner or re-enable inputs
                }
            });
        }

        function clearFields() {
            // $("#transfer_code").val(0);
            $("#drop_location").val('');
            $("#gdistance").val('');
            $("#distance").val('');
            $("#hotel").val('');
            $("#select_h").text('');
            $("#hotel_code").val('');
            $("#select_h_code").text('');
            $("#slct_htl").val('');
            $("#slct_htl_c").val('');
            $("#room_type").empty();
            $('#room_type_id').val('');
            $("#select_rt").text('');
            $("#meal_code").val('');
            $("#sgl").val('');
            $("#dbl").val('');
            $("#tpl").val('');
            $("#up_flag").val(0);
            $("#final_destination").text('');
            $('#hotel_code').empty();
            $('#hotel').empty();
            $('#hotel').val('');
            $('#extrakm').val('');
        }

        // The agent list is the customers marked as agents; the whole ledger (ticketing
        // customers included) is a click away, and an inquiry that names an unmarked
        // customer switches to it by itself so the selection is never silently lost.
        var AGENTS_MARKED = <?= json_encode(array_map(static fn ($a) => ['acc' => (string) $a['Sup_Acc_No'], 'name' => (string) $a['Name']], $agents)) ?>;
        var AGENTS_ALL = <?= json_encode(array_map(static fn ($a) => ['acc' => (string) $a['Sup_Acc_No'], 'name' => (string) $a['Name'], 'agent' => (int) ($a['agent'] ?? 0)], $agents_all ?? [])) ?>;
        var agentShowingAll = false;
        function agentOptions(list, all) {
            var html = '<option value="">Select...</option>';
            list.forEach(function(a) {
                html += '<option value="' + $('<div>').text(a.acc).html() + '">' + $('<div>').text(a.name).html() + (all && a.agent !== 1 ? ' (not marked as an agent)' : '') + '</option>';
            });
            return html;
        }
        function agentShowAll(all, keep) {
            if (!AGENTS_ALL.length) return;
            agentShowingAll = !!all;
            var sel = $('#agent');
            var current = keep !== undefined ? keep : sel.val();
            sel.html(agentOptions(agentShowingAll ? AGENTS_ALL : AGENTS_MARKED, agentShowingAll));
            sel.val(current || '').trigger('change.select2');
            $('#agent_scope').html(agentShowingAll
                ? '— every customer (' + AGENTS_ALL.length + '), the ones not marked as agents included. <a href="#" id="agent_scope_link">Back to the ' + AGENTS_MARKED.length + ' marked agents</a>'
                : '— the ' + AGENTS_MARKED.length + ' marked agents. <a href="#" id="agent_scope_link">Not here? Show all customers</a>');
        }
        $(document).on('click', '#agent_scope_link', function(e) {
            e.preventDefault();
            agentShowAll(!agentShowingAll);
        });
        // a value set from an inquiry (or by hand) that the marked list does not carry: show everyone, then set it
        function agentSelect(value) {
            var sel = $('#agent');
            if (value && !sel.find('option[value="' + String(value).replace(/"/g, '\\"') + '"]').length && AGENTS_ALL.length && !agentShowingAll) {
                agentShowAll(true, value);
            }
            sel.val(value).trigger('change');
        }

        function agent_detail(agent) {

            $.ajax({
                url: '<?php echo base_url(); ?>tour/tour/getConsigneeDetails/' + agent,
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

        // .inquiries on change
        $("#inquiries").on('change', function(e) {
            var id = $(this).val();

            $.ajax({
                url: '<?php echo base_url('inquiries/get_inquiry') ?>/' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log(response.data);
                    if (response.status == 200) {
                        $('#consignee').val(response.data.name);
                        agentSelect(response.data.agent_name);
                        $('#tel_mobile').val(response.data.phone);
                        $('#email').val(response.data.email);
                        $('#start_date').val(response.data.start_date);
                        $('#end_date').val(response.data.end_date);
                        $('#inquiry_id').val(response.data.id);
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
    </script>

</body>

</html>