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
                                            <button id="finalize_btn" class="btn btn-success waves-effect waves-light fw-bold"> Finalize </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-8">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="row">
                                                            <div class="mb-3 col-md-4">
                                                                <label class="form-label" for="start_date">Start Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="start_date" name="start_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                            </div>
                                                            <div class="mb-3 col-md-4">
                                                                <label class="form-label" for="end_date">End Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control" type="text" id="end_date" name="end_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                                            </div>
                                                            <div class="mb-3 col-md-4" style="padding-top: 27px;">
                                                                <button type="submit" class="btn btn-success w-100px me-5px">CREATE</button>
                                                            </div>
                                                        </div>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8" id="table_detail" style="display: none;">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover align-middle" id="date_table" data-tourid="">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Date</th>
                                                                <th>Details</th>
                                                                <th>Transport</th>
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
        <div class="modal fade" id="dateModalcgcf" role="dialog" aria-labelledby="dateModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dateModalLabel">Date Information</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">

                        </div>
                        <div class="mb-3">

                        </div>
                        <div class="mb-3">

                        </div>
                        <div class="mb-3">

                        </div>
                        <!-- <div class="mb-3">
                        <label class="form-label" for="hotel">Hotel <font color="#FF0000"><strong>*</strong></font></label>
                        <input class="form-control" type="text" id="hotel" name="hotel" placeholder="" required readonly />
                        <input class="form-control" type="hidden" id="hotel_acc" name="hotel_acc" placeholder="" />
                    </div> -->
                        <div class="mb-3">

                        </div>
                        <hr>
                        <!-- <div class="mb-3">
                        <label class="form-label" for="start_date">Start Date<font color="#FF0000"><strong>*</strong></font></label>
                        <input class="form-control" type="text" id="start_date" name="start_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="end_date">End Date<font color="#FF0000"><strong>*</strong></font></label>
                        <input class="form-control" type="text" id="end_date" name="end_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                    </div> -->
                        <div class="mb-3">

                        </div>
                        <div class="mb-3">

                        </div>
                        <div class="mb-3">

                        </div>
                        <br>
                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="dateModal" tabindex="-1" aria-labelledby="exampleModalgridLabel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalgridLabel">Quotation Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" id="quotation_details">
                            <div class="row g-3">
                                <p id="modal-date-info"></p>
                                <div class="col-xxl-6">
                                    <div>
                                        <label class="form-label" for="code">Hotel <font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="hotel" id="hotel" class="js-example-basic-single form-control" style="width: 100%;" required onchange="hotelChanged(this.value);">
                                            <option value="0">Select a Hotel...</option>
                                            <?php
                                            foreach ($hotel as $id) {
                                                echo '<option value="' . $id['hotel'] . '" >' . $id['Name'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <!--end col-->
                                <div class="col-xxl-6">
                                    <div>
                                        <label class="form-label" for="code">Hotel Code <font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="hotel_code" id="hotel_code" class="js-example-basic-single form-control" style="width: 100%;" required onchange="hotelCodeChanged(this.value);">
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
                                    <label class="form-label" for="sgl">SGL<font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control" type="text" id="sgl" name="sgl" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                </div>
                                <!--end col-->
                                <div class="col-xxl-6">
                                    <label class="form-label" for="dbl">DBL<font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control" type="text" id="dbl" name="dbl" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                </div>
                                <div class="col-xxl-6">
                                    <label class="form-label" for="tpl">TPL<font color="#FF0000"><strong>*</strong></font></label>
                                    <input class="form-control" type="text" id="tpl" name="tpl" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" />
                                </div>

                                <!--end col-->
                                <div class="col-lg-12">
                                    <div class="hstack gap-2 justify-content-end">
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
        <div class="modal fade" id="transportModal" tabindex="-1" aria-labelledby="exampleModalgridLabel">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalgridLabel">Transport Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" id="transport_details">
                            <div class="row g-3">
                                <p id="modal-date-info"></p>
                                <div class="col-xxl-6">
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
                                    <div class="col-xxl-6" id="trans_code">
                                        <div>
                                            <label class="form-label" for="code">Transfer Code <font color="#FF0000"><strong>*</strong></font></label>
                                            <select name="transfer_code" id="transfer_code" class="js-example-basic-single form-control" style="width: 100%;" required onchange="transferChanged(this.value);">
                                                <option value="0">Select a Hotel...</option>
                                                <?php
                                                foreach ($transfer_code as $id) {
                                                    echo '<option value="' . $id['id'] . '" >' . $id['transfer_code'] . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <!--end col-->

                                    <div class="col-xxl-6" id="trans">
                                        <div>
                                            <label class="form-label" for="code">Transfer <font color="#FF0000"><strong>*</strong></font></label>
                                            <input type="text" name="transfer" id="transfer" class="form-control" style="width: 100%;" required>
                                        </div>
                                    </div>

                                    <div class="col-xxl-6" id="manudis">
                                        <div>
                                            <label class="form-label" for="date">Distance<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="distance" name="distance" placeholder="" required />
                                        </div>
                                    </div>

                                    <div id="map" class="rounded" style="height: 400px; width: 100%;"></div>

                                    <div id="map_div">
                                        <div class="my-3">
                                            <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label">Pick Up Location</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control text-dark fw-bold" id="pick_location" name="pick_location" placeholder="Search">
                                                    <button class="btn btn-dark" type="button" id="locate-me"><i class="bi bi-geo-alt-fill"></i></button>
                                                </div>
                                                <input type="text" name="pl_hidden" id="pl_hidden" hidden>
                                                <input type="hidden" id="pick_lat">
                                                <input type="hidden" id="pick_long">
                                            </div>
                                            <div class="mb-3">
                                                <label for="exampleFormControlInput1" class="form-label">Drop Off Location</label>
                                                <input id="drop_location" name="drop_location" type="text" class="form-control text-dark fw-bold" placeholder="Search">
                                                <input type="text" name="dl_hidden" id="dl_hidden" hidden>
                                                <input type="hidden" id="drop_lat">
                                                <input type="hidden" id="drop_long">
                                            </div>
                                        </div>
                                        <div class="alert alert-info" role="alert">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-dark">Distance</span>
                                                <span id="distance">---</span>
                                                <span class="fw-bold text-dark mt-3">Average Price</span>
                                                <span id="price">---</span>
                                            </div>
                                        </div>
                                        <div id="map" class="rounded" style="height: 400px; width: 100%;"></div>
                                    </div>

                                </div>
                                <!--end col-->
                                <div class="col-lg-12">
                                    <div class="hstack gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                                <!--end col-->
                            </div>
                    </div>
                    <!--end row-->
                    </form>
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


    <script>
        var pl = null;
        var dl = null;
        var plt = 0;
        var dlt = 0;
        var plg = 0;
        var dlg = 0;
        var dist = 0;
        var prc = 0;

        function getAddressFromLatLng(lat, lng, elementId) {
            const geocoder = new google.maps.Geocoder();

            const latlng = {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            };

            geocoder.geocode({
                location: latlng
            }, (results, status) => {
                if (status === "OK") {
                    if (results[0]) {
                        const address = results[0].formatted_address;
                        console.log("Address:", address);

                        document.getElementById(elementId).value = address;
                        pl = address;
                    } else {
                        console.error("No results found");
                    }
                } else {
                    console.error("Geocoder failed due to:", status);
                }
            });
        }

        function calculateDistanceAndRoute(pickupLat, pickupLng, dropoffLat, dropoffLng) {
            const origin = new google.maps.LatLng(pickupLat, pickupLng);
            const destination = new google.maps.LatLng(dropoffLat, dropoffLng);
            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer();

            const mapProp = {
                center: origin,
                zoom: 14,
            };

            const map = new google.maps.Map(document.getElementById("map"), mapProp);
            directionsRenderer.setMap(map);

            const request = {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode.DRIVING
            };

            directionsService.route(request, (response, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(response);

                    // Display the distance
                    const distance = response.routes[0].legs[0].distance.text;
                    console.log('Distance:', distance);
                    // distance is like 36.5 KM
                    // check if distance greater than 0
                    if (parseFloat(distance.split(' ')[0]) > 0) {
                        // calculate the price
                        document.getElementById('distance').innerText = distance;
                        // calculate price;
                        // LKR 100 for each in first 5 KMs
                        // LKR 80 for each in next 5 KMs
                        // LKR 60 for each KM after that
                        let price = 0;
                        const distanceInKMs = parseFloat(distance.split(' ')[0]);
                        if (distanceInKMs <= 5) {
                            price = distanceInKMs * 100;
                        } else if (distanceInKMs <= 10) {
                            price = (5 * 100) + ((distanceInKMs - 5) * 80);
                        } else {
                            price = (5 * 100) + (5 * 80) + ((distanceInKMs - 10) * 60);
                        }
                        document.getElementById('price').innerText = 'LKR ' + price.toFixed(2);

                        dist = distance;
                        prc = price;
                        plt = pickupLat;
                        dlt = dropoffLat;
                        plg = pickupLng;
                        dlg = dropoffLng;
                        // set the pickup and dropoff locations
                        pl = document.getElementById('pl_hidden').value;
                        dl = document.getElementById('dl_hidden').value;
                        // enable the find taxi button
                        document.getElementById('findTaxi').disabled = false;
                    } else {
                        document.getElementById('distance').innerText = '---';
                        document.getElementById('price').innerText = '---';

                        dist = 0;
                        prc = 0;
                        plt = 0;
                        dlt = 0;
                        plg = 0;
                        dlg = 0;
                        pl = '';
                        dl = '';
                        // disable the find taxi button
                        document.getElementById('findTaxi').disabled = true;
                    }

                    // Display the route on the map
                    const route = response.routes[0].overview_path;
                    const polyline = new google.maps.Polyline({
                        path: route,
                        geodesic: true,
                        strokeColor: '#FF0000',
                        strokeOpacity: 1.0,
                        strokeWeight: 2
                    });
                    polyline.setMap(map);

                    // document.getElementById('distance').innerText = distance;
                } else {
                    console.error('Error:', status);
                }
            });
        }

        async function myMap() {

            // Try to get the user's current location
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    // The location of the user
                    const userPosition = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };

                    getAddressFromLatLng(userPosition.lat, userPosition.lng, 'flocation');
                    console.log("User's location: ", userPosition);

                    var mapProp = {
                        center: new google.maps.LatLng(userPosition.lat, userPosition.lng),
                        zoom: 14,
                    };
                    var map = new google.maps.Map(document.getElementById("map"), mapProp);
                    var marker = new google.maps.Marker({
                        position: userPosition
                    });
                    marker.setMap(map);
                },
                () => {
                    // Handle errors or default to a specific location
                    console.error("Error getting user location. Defaulting to Kurunegala, Sri Lanka.");
                    // The location of Kurunegala, Sri Lanka
                    const defaultPosition = {
                        lat: 7.4712,
                        lng: 80.3548
                    };

                    var mapProp = {
                        center: new google.maps.LatLng(defaultPosition.lat, defaultPosition.lng),
                        zoom: 5,
                    };
                    var map = new google.maps.Map(document.getElementById("map"), mapProp);
                    var marker = new google.maps.Marker({
                        position: defaultPosition
                    });
                    marker.setMap(map);
                }
            );

            var input = document.getElementById('drop_location');
            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                document.getElementById('drop_lat').value = place.geometry.location.lat();
                document.getElementById('drop_long').value = place.geometry.location.lng();

                const pickupLat = parseFloat(document.getElementById('pick_lat').value);
                const pickupLng = parseFloat(document.getElementById('pick_long').value);
                const dropoffLat = parseFloat(place.geometry.location.lat());
                const dropoffLng = parseFloat(place.geometry.location.lng());

                // set pl_hidden and dl_hidden
                document.getElementById('pl_hidden').value = document.getElementById('pick_location').value;
                document.getElementById('dl_hidden').value = place.formatted_address;
                calculateDistanceAndRoute(pickupLat, pickupLng, dropoffLat, dropoffLng);
            });

            var input2 = document.getElementById('pick_location');
            var autocomplete2 = new google.maps.places.Autocomplete(input2);
            autocomplete2.addListener('place_changed', function() {
                var place2 = autocomplete2.getPlace();
                document.getElementById('pick_lat').value = place2.geometry.location.lat();
                document.getElementById('pick_long').value = place2.geometry.location.lng();
            });
        }

        // find taxi button click event
        $('#findTaxi').click(function() {
            if (plt == 0 || dlt == 0 || plg == 0 || dlg == 0 || dist == 0 || prc == 0 || pl == '' || dl == '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong! Please select pickup and dropoff locations!',
                });
            } else {
                window.location.href = '<?= base_url(); ?>ride/available?pl=' + pl + '&dl=' + dl + '&plt=' + plt + '&dlt=' + dlt + '&plg=' + plg + '&dlg=' + dlg + '&dist=' + dist + '&prc=' + prc;
            }
        });

        // #login button click event
        $('#login').click(function() {
            window.location.href = '<?= base_url(); ?>auth/signin';
        });
    </script>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        function hotelChanged(selectedval) {

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

                    $.each(response, function(index, value) {
                        // if (value.transport_flag == 0 && value.Chassis_no == chass_no) {
                        // states.push(value.Stock_no);
                        // apend to select
                        $('#hotel_code').append(
                            '<option value="' + value.hotel_code + '">' + value.hotel_code + '</option>'
                        );
                        // }
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

                        $('#hotel').val(response.data.Name);
                        $('#hotel_acc').val(response.data.Sup_Acc_No);
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
                        $('#gdistance').val(response.data.milage);

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

                                Tour_Id = response.data;
                                $("#date_table").data("tourid", Tour_Id);

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

                // Clear the table before adding new data
                $('#date_table tbody').empty();

                // Parse dates
                var startDate = new Date(start_date);
                var endDate = new Date(end_date);

                // Calculate date difference
                var currentDate = startDate;
                var index = 1;
                while (currentDate <= endDate) {
                    $("#table_detail").removeAttr('style');
                    var formattedDate = currentDate.toISOString().split('T')[0];
                    var row = '<tr data-dateid = "Tour-' + Tour_Id + '-' + index + '">';
                    row += '<td>' + index + '</td>';
                    row += '<td>' + formattedDate + '</td>';
                    row += '<td><button type="button" class="btn btn-sm btn-success add-info" data-date="' + formattedDate + '"> <i class="bi bi-plus"></i></button></td>';
                    row += '<td><button type="button" class="btn btn-sm btn-info add-transport" data-date="' + formattedDate + '"> <i class="bi bi-plus"></i></button></td>';
                    row += '</tr>';
                    $('#date_table tbody').append(row);

                    // Move to the next day
                    currentDate.setDate(currentDate.getDate() + 1);
                    index++;
                }

                // Attach click event to the buttons
                $('.add-info').on('click', function() {
                    var date = $(this).data('date');
                    $('#modal-date-info').text('Selected date: ' + date);
                    $('#date').val(date);
                    $('#dateModal').modal('show');
                });

                $('.add-transport').on('click', function() {
                    var date = $(this).data('date');
                    $('#modal-date-info1').text('Selected date: ' + date);
                    $('#transportModal').modal('show');
                });
                // populateListInTable();
            });
        });
    </script>

    <script>
        // Function to show/hide elements based on distance type
        function toggleElements() {
            var distanceType = document.querySelector('input[name="distance_type"]:checked').value;
            if (distanceType == 2) {
                document.getElementById('trans_code').style.display = 'none';
                document.getElementById('trans').style.display = 'none';
                document.getElementById('manudis').style.display = 'none';
                document.getElementById('frloc').style.display = 'block';
                document.getElementById('toloc').style.display = 'block';
                document.getElementById('gdis').style.display = 'block';
            } else if (distanceType == 1) {
                document.getElementById('trans_code').style.display = 'block';
                document.getElementById('trans').style.display = 'block';
                document.getElementById('manudis').style.display = 'block';
                document.getElementById('frloc').style.display = 'none';
                document.getElementById('toloc').style.display = 'none';
                document.getElementById('gdis').style.display = 'none';
            }
        }

        // Event listener for distance type radio buttons
        var distanceTypeRadios = document.querySelectorAll('input[name="distance_type"]');
        distanceTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', toggleElements);
        });

        // Initial call to toggleElements to set initial state
        toggleElements();
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEg8Qki_788QHwsbPoh8N2EW_TiWrb0Rg&loading=async&libraries=places&callback=myMap"></script>
</body>

</html>