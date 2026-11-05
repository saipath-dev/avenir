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
                                                                <h6 class="fw-bold"><?= $consignee['consignee'] ?></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Agent</span>
                                                                <h6 class="fw-bold"><?= $consignee['agent_name'] ?></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Passengers</span>
                                                                <h6 class="fw-bold"><?= $consignee['passenger_count'] ?></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">Start Date</span>
                                                                <h6 class="fw-bold"><?= $consignee['start_date'] ?></h6>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-muted">End Date</span>
                                                                <h6 class="fw-bold"><?= $consignee['end_date'] ?></h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row my-4">
                                    <div class="col-lg-12">
                                        <div>
                                            <h5 class="mb-4">Manage Timeline</h5>
                                            <!-- DATA -->
                                            <!-- ======================================= -->
                                            <style>
                                                .timeline-2 .timeline-box {
                                                    max-width: 850px;
                                                }

                                                .t-fc {
                                                    padding: 5px;
                                                }
                                            </style>

                                            <input type="hidden" id="pick_lat">
                                            <input type="hidden" id="pick_long">
                                            <input type="hidden" id="pick_city" value="">
                                            <input type="hidden" id="drop_lat">
                                            <input type="hidden" id="drop_long">
                                            <input type="hidden" id="drop_city" value="">
                                            <!-- ======================================= -->
                                            <!-- END DATA -->
                                            <div class="timeline-2">
                                                <div class="timeline-continue">
                                                    <div class="row timeline-right">
                                                        <div class="col-12">
                                                            <p class="timeline-date">
                                                                2024 September 10, Monday
                                                            </p>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="timeline-box w-100">
                                                                <div class="d-flex justify-content-between">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="text-muted">From</span>
                                                                        <input type="text" class="form-control text-dark fw-bold from_location" placeholder="Search" required>
                                                                    </div>
                                                                    <div class="d-flex align-items-center">
                                                                        <img src="<?= base_url('assets/images/quotation/right-arrow.png') ?>" alt="" width="32px">
                                                                    </div>
                                                                    <div class="d-flex flex-column">
                                                                        <span class="text-muted">To</span>
                                                                        <input type="text" class="form-control text-dark fw-bold to_location" placeholder="Search" required>
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-4">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Google Distance</span>
                                                                            <input type="text" class="form-control t-fc google_km">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Actual KM</span>
                                                                            <input type="text" class="form-control t-fc actual_km">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Extra KM</span>
                                                                            <input type="text" class="form-control t-fc extra_km">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row mb-3">
                                                                    <div class="col-md-4">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Hotel</span>
                                                                            <h6>Heritance Kandalama</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Room Type</span>
                                                                            <h6>BB</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Meal Code</span>
                                                                            <select class="form-control t-fc" name="meal_code" id="meal_code">
                                                                                <option value=""></option>
                                                                                <option value="BB">BB</option>
                                                                                <option value="HB">HB</option>
                                                                                <option value="FB">FB</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-3">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">SGL</span>
                                                                            <input type="text" class="form-control t-fc">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">DBL</span>
                                                                            <input type="text" class="form-control t-fc">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">TPL</span>
                                                                            <input type="text" class="form-control t-fc">
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Ex. Act Fees</span>
                                                                            <input type="text" class="form-control t-fc">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row timeline-right">
                                                        <div class="col-12">
                                                            <p class="timeline-date">
                                                                2024 September 10, Monday
                                                            </p>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="timeline-box w-100">
                                                                <div class="d-flex justify-content-between">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="text-muted">From</span>
                                                                        <h5>Colombo International Airport</h5>
                                                                    </div>
                                                                    <div class="d-flex align-items-center">
                                                                        <img src="<?= base_url('assets/images/quotation/right-arrow.png') ?>" alt="" width="32px">
                                                                    </div>
                                                                    <div class="d-flex flex-column">
                                                                        <span class="text-muted">To</span>
                                                                        <h5>Shangrila Hotel, Colombo</h5>
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <div class="row">
                                                                    <div class="col-md-2">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">KM</span>
                                                                            <h6>10.5</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Extra KM</span>
                                                                            <h6>2.0</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-4">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Hotel</span>
                                                                            <h6>Heritance Kandalama</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Room Type</span>
                                                                            <h6>BB</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Meal Code</span>
                                                                            <h6>BB</h6>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="col-md-2">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">SGL</span>
                                                                            <h6>0.00</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">DBL</span>
                                                                            <h6>0.00</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">TPL</span>
                                                                            <h6>0.00</h6>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <div class="d-flex flex-column">
                                                                            <span class="text-muted">Ex. Act Fees</span>
                                                                            <h6>0.00</h6>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <div class="d-flex">
                                                                    <div class="d-flex justify-content-between w-100">
                                                                        <div class="d-flex">
                                                                            <button class="btn btn-sm btn-primary me-1">
                                                                                <i class="bi bi-sunglasses me-1"></i> Extra Activities
                                                                            </button>
                                                                            <button class="btn btn-sm btn-info">
                                                                                <i class="bi bi-list-task me-1"></i> Itinerary
                                                                            </button>
                                                                        </div>
                                                                        <div class="d-flex">
                                                                            <button class="btn btn-sm btn-light me-1">
                                                                                <i class="bi bi-pen"></i>
                                                                            </button>
                                                                            <button class="btn btn-sm btn-danger">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
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
    <script src="<?= base_url(); ?>assets/js/pages/timeline.init.js"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEg8Qki_788QHwsbPoh8N2EW_TiWrb0Rg&libraries=places&"></script>



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

                var pickInput = document.getElementsByClassName('from_location');
                var dropInput = document.getElementsByClassName('to_location');

                var pickAutocomplete = null;
                for (i = 0; i < pickInput.length; i++) {
                    pickAutocomplete = new google.maps.places.Autocomplete(pickInput[i], options);
                    // pickAutocomplete.addListener('place_changed', function() {
                    //     handlePlaceChange(pickAutocomplete, pickInput[i], 'pick_lat', 'pick_long');
                    // });
                }

                var dropAutocomplete = null;
                for (i = 0; i < dropInput.length; i++) {
                    dropAutocomplete = new google.maps.places.Autocomplete(dropInput[i], options);
                    // dropAutocomplete.addListener('place_changed', function() {
                    //     handlePlaceChange(dropAutocomplete, dropInput[i], 'drop_lat', 'drop_long');
                    // });
                }
            }

            // function handlePlaceChange(autocomplete, input, latId, lngId) {
            //     console.log("Autocomplete :");
            //     console.log(autocomplete);
            //     var place = autocomplete.getPlace();
            //     console.log("Place :");
            //     console.log(place);
            //     if (place?.geometry) {
            //         const lat = place.geometry.location.lat();
            //         const lng = place.geometry.location.lng();
            //         document.getElementById(latId).value = lat;
            //         document.getElementById(lngId).value = lng;

            //         // Extract the city name from the place details
            //         var city = '';
            //         for (var i = 0; i < place.address_components.length; i++) {
            //             var component = place.address_components[i];
            //             if (component.types.includes('locality')) {
            //                 city = component.long_name;
            //                 break;
            //             }
            //         }

            //         // Update the pickLat, pickLng, dropLat, and dropLng variables
            //         if (latId === 'pick_lat') {
            //             pickLat = lat;
            //             pickLng = lng;
            //             document.getElementById('pick_city').value = city;
            //         } else {
            //             dropLat = lat;
            //             dropLng = lng;
            //             document.getElementById('drop_city').value = city;
            //         }

            //         calculateAndDisplayRoute();
            //     }
            //     else{
            //         console.log('Place not found');
            //     }
            // }

            // function calculateAndDisplayRoute() {

            //     console.log('calculateAndDisplayRoute()');

            //     if (pickLat && pickLng && dropLat && dropLng) {

            //         console.log('Inside if condition')

            //         const origin = new google.maps.LatLng(pickLat, pickLng);
            //         const destination = new google.maps.LatLng(dropLat, dropLng);

            //         console.log("origin: ");
            //         console.log(origin);

            //         directionsService = new google.maps.DirectionsService();
            //         directionsService.route({
            //             origin: origin,
            //             destination: destination,
            //             travelMode: google.maps.TravelMode.DRIVING
            //         }, (response, status) => {

            //             console.log(response);

            //             if (status === 'OK') {
            //                 directionsRenderer.setDirections(response);
            //                 const distance = response.routes[0].legs[0].distance.text;

            //                 console.log('Distance: ' + distance);

            //                 // Get Actual Distance
            //                 var pick = $('#from_location').val();
            //                 if (pick.indexOf(', Sri Lanka') !== -1) {
            //                     frompick = pick.replace(', Sri Lanka', '');
            //                 }

            //                 var drop = $('#to_location').val();
            //                 if (drop.indexOf(', Sri Lanka') !== -1) {
            //                     todrop = drop.replace(', Sri Lanka', '');
            //                 }

            //                 // alert(from);
            //             } else {
            //                 console.error('Directions request failed due to ' + status);
            //             }
            //         });
            //     }
            // }

            initMap();
        });
    </script>

    <script>
        $(document).ready(function() {
            // .from_location input
            // $(document).on('input', '.from_location', function() {
            //     var from = $(this).val();
            //     console.log('from : ' + from);
            // });

            // .to_location input
            // $(document).on('input', '.to_location', function() {
            //     var to = $(this).val();
            //     console.log('to : ' + to);
            //     var distance = get_distance_from_distance_metrix_api(from, to);
            //     console.log('distance : ' + distance);
            // });

            // .to_location focus
            $(document).on('focus', '.google_km', function() {
                var from = $(this).closest('.timeline-box').find('.from_location').val();
                var to = $(this).closest('.timeline-box').find('.to_location').val();
                get_distance_from_distance_metrix_api(from, to);
            });

            function get_distance_from_distance_metrix_api(from, to){
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
                    if (status == 'OK' && response.rows[0]?.elements[0]?.status != "ZERO_RESULTS") {
                        distance = response.rows[0].elements[0].distance.value / 1000;
                        distance = distance.toFixed(1);
                        $('.google_km').val(distance);
                        $('.actual_km').val(distance);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Sorry... Distance not found!',
                        });
                    }
                });
            }
        });
    </script>

</body>

</html>