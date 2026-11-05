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
                                <div class="row ">
                                    <div class="col-md-12 d-flex">
                                        <h4 class="p-title">Tour Sub Locations</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-body">

                                                <form action="javascript:void(0)" id="form_submit">
                                                    <div class="d-flex flex-column">
                                                        <div class="col-md-12 mb-3">
                                                            <label for="date" class="form-label">Date</label>
                                                            <select class="form-control" name="t_date" id="t_date">
                                                                <option value="">Select Date</option>
                                                                <?php
                                                                foreach ($dates as $date) {
                                                                    echo '<option value="' . $date['id'] . '">' . $date['tour_date'] . '</option>';
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="d-flex">
                                                            <div class="col-md-5 me-2">
                                                                <label for="from" class="form-label">From</label>
                                                                <input class="form-control" type="text" name="from" id="from">
                                                                <input type="hidden" id="pick_lat">
                                                                <input type="hidden" id="pick_long">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label for="to" class="form-label">To</label>
                                                                <input class="form-control" type="text" name="to" id="to">
                                                                <input type="hidden" id="drop_lat">
                                                                <input type="hidden" id="drop_long">
                                                            </div>
                                                        </div>
                                                        <div class="d-flex mt-3">
                                                            <div class="col-md-5 me-2">
                                                                <label for="to" class="form-label">Estimated KM</label>
                                                                <input class="form-control" type="text" name="estimated_km" id="estimated_km">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label for="to" class="form-label">Actual KM</label>
                                                                <input class="form-control" type="text" name="actual_km" id="actual_km">
                                                            </div>
                                                        </div>
                                                        <div class="mt-3">
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="bi bi-plus
                                                                            "></i> Add Sub Location
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <?php
                                                if ($quotation_id == 0 || count($dates) == 0) {
                                                    // echo alert div
                                                    echo '<div class="alert alert-danger" role="alert">No data found!</div>';
                                                } else {
                                                    foreach ($dates as $date) {
                                                ?>
                                                        <table class="table table-sm table-bordered table-striped align-middle mb-3">
                                                            <thead>
                                                                <tr class="table-secondary">
                                                                    <td colspan="4">
                                                                        <div class="d-flex flex-column">
                                                                            <div>
                                                                                <h5 class="fw-bold"><?= $date['tour_date'] ?></h5>
                                                                            </div>
                                                                            <div>
                                                                                <span>From : <strong><?= $date['from_location'] ?></strong></span>
                                                                            </div>
                                                                            <div>
                                                                                <span>Final Destination : <strong><?= $date['to_location'] ?></strong></span>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $sub_locations = $date['sub_locations'];
                                                                foreach ($sub_locations as $location) { ?>
                                                                    <tr>
                                                                        <td><?= $location['from'] ?></td>
                                                                        <td><?= $location['to'] ?></td>
                                                                        <td><?= $location['km'] ?></td>
                                                                        <td>
                                                                            <button class="btn btn-danger">
                                                                                <i class="bi bi-trash"></i>
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                <?php }
                                                                if (empty($sub_locations)) {
                                                                    echo '<tr><td colspan="4">No sub locations found!</td></tr>';
                                                                }
                                                                ?>
                                                            </tbody>
                                                        </table>
                                                <?php
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end .h-100-->
                            <div id="map" class="rounded d-none"></div>

                        </div> <!-- end col -->
                    </div>

                </div>

            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <!-- <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEg8Qki_788QHwsbPoh8N2EW_TiWrb0Rg&loading=async&libraries=places"></script> -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBW7n1LFvH3Gjn3gWTXzmuBDePpLeBNFIk&libraries=places"></script>

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

                const pickInput = document.getElementById('from');
                const dropInput = document.getElementById('to');

                // Create the autocomplete objects
                const pickAutocomplete = new google.maps.places.Autocomplete(pickInput, options);
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

                if (pickLat && pickLng && dropLat && dropLng) {

                    const origin = new google.maps.LatLng(pickLat, pickLng);
                    const destination = new google.maps.LatLng(dropLat, dropLng);

                    directionsService.route({
                        origin: origin,
                        destination: destination,
                        travelMode: google.maps.TravelMode.DRIVING
                    }, (response, status) => {

                        console.log(response);

                        if (status === 'OK') {
                            directionsRenderer.setDirections(response);
                            var distance = response.routes[0].legs[0].distance.text;
                            distance = distance.replace(' km', '');
                            document.getElementById('estimated_km').value = distance; // Update distance here
                            document.getElementById('actual_km').value = distance; // Update distance here
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

        $(document).ready(function() {


            // .submit btn click
            $('#form_submit').submit(function() {
                let from = $('#from').val();
                let to = $('#to').val();
                let estimated_km = $('#estimated_km').val();
                let actual_km = $('#actual_km').val();
                let date_id = $('#t_date').val();
                let quotation_id = <?= $quotation_id ?>;

                if (from == '' || to == '' || actual_km == '') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Please fill all the fields!',
                    });
                    return;
                }

                $.ajax({
                    url: '<?= base_url('tour/sub_locations/create') ?>',
                    type: 'POST',
                    data: {
                        from: from,
                        to: to,
                        actual_km: actual_km,
                        date_id: date_id,
                        quotation_id: quotation_id
                    },
                    success: function(response) {
                        response = JSON.parse(response);
                        if (response.status == 200) {
                            swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                        } else {
                            swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: response.message,
                            });
                        }
                    },
                    error: function(err) {
                        console.log(err);
                    }
                });
            });
        });
    </script>
</body>




</html>