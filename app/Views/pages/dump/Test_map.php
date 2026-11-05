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

        .s1:hover ~ a {
            color: gold;
        }

        .s2:hover ~ a {
            color: gold;
        }

        .s3:hover ~ a {
            color: gold;
        }

        .s4:hover ~ a {
            color: gold;
        }

        .s5:hover ~ a {
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

                <div class="col-md-4 ">
                    <!--<form action="javascript:void(0);" id="transport_details">-->
                    <input type="hidden" name="trans_tour_id" id="trans_tour_id">
                    <input type="hidden" name="trans_row_id" id="trans_row_id">
                    <div class="row g-3">
                        <p id="modal-date-info"></p>
                        <div class="col-xxl-6">

                            <br>
                            <br>
                            <div id="map" class="rounded" style="height: 400px; width: 100%;"></div>
                            <div id="map_div">
                                <div class="my-3">
                                    <div class="mb-3">
                                        <label for="pick_location" class="form-label">Pick Up
                                            Location</label>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control text-dark fw-bold"
                                                   id="pick_location" name="pick_location"
                                                   placeholder="Search">
                                            <button class="btn btn-dark" type="button" id="locate-me"><i
                                                        class="bi bi-geo-alt-fill"></i></button>
                                        </div>
                                        <input type="hidden" id="pick_lat">
                                        <input type="hidden" id="pick_long">
                                    </div>
                                    <div class="mb-3">
                                        <label for="drop_location" class="form-label">Drop Off
                                            Location</label>
                                        <input id="drop_location" name="drop_location" type="text"
                                               class="form-control text-dark fw-bold"
                                               placeholder="Search">
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
                                <div id="map" class="rounded"></div>

                            </div>


                            <!--end col-->
                            <div class="col-lg-12">
                                <div class="hstack gap-2 justify-content-end">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </div>
                            </div>
                            <!--end col-->
                        </div>

                        <!--end row-->
                        <!--</form>-->
                    </div>
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <!-- Loading Modal -->

    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEg8Qki_788QHwsbPoh8N2EW_TiWrb0Rg&loading=async&libraries=places&callback=myMap"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let pickLat = 0, pickLng = 0, dropLat = 0, dropLng = 0;
            let map, directionsService, directionsRenderer;

            function initMap() {
                directionsService = new google.maps.DirectionsService();
                directionsRenderer = new google.maps.DirectionsRenderer();
                map = new google.maps.Map(document.getElementById('map'), {
                    center: {lat: 7.8731, lng: 80.7718},
                    zoom: 8
                });
                directionsRenderer.setMap(map);

                const pickInput = document.getElementById('pick_location');
                const dropInput = document.getElementById('drop_location');

                // Create the autocomplete objects
                const pickAutocomplete = new google.maps.places.Autocomplete(pickInput);
                const dropAutocomplete = new google.maps.places.Autocomplete(dropInput);

                // Listen for place changes on both pick and drop inputs
                pickAutocomplete.addListener('place_changed', function () {
                    handlePlaceChange(pickAutocomplete, pickInput, 'pick_lat', 'pick_long');
                });

                dropAutocomplete.addListener('place_changed', function () {
                    handlePlaceChange(dropAutocomplete, dropInput, 'drop_lat', 'drop_long');
                });
            }

            // Helper function to handle place changes
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
                            document.getElementById('distance').innerText = distance; // Update distance here
                            calculatePrice(distance);
                            document.getElementById('findTaxi').disabled = false;
                        } else {
                            console.error('Directions request failed due to ' + status);
                        }
                    });
                }
            }

            function calculatePrice(distance) {
                let price = 0;
                const distanceInKm = parseFloat(distance.split(' ')[0]);
                if (distanceInKm <= 5) {
                    price = distanceInKm * 100;
                } else if (distanceInKm <= 10) {
                    price = (5 * 100) + ((distanceInKm - 5) * 80);
                } else {
                    price = (5 * 100) + (5 * 80) + ((distanceInKm - 10) * 60);
                }
                document.getElementById('price').innerText = 'LKR ' + price.toFixed(2);
            }



            initMap();
        });
    </script>

</body>

</html>