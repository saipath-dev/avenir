<?php echo view('layouts/header'); ?>

<body>

<div class="p-5">
    <div class="row">
        <div class="my-4">
            <h5 class="card-title fw-bolder font-playfair text-dark" style="font-size: 64px;">Always the ride you want
            </h5>
            <p class="card-text mt-3">Reserve a ride, hop in, and go.</p>
        </div>
        <div class="col-md-5 col-sm-12">
            <div class="card mt-3">
                <div class="card-body p-4">
                    <div id="loading_div" class="d-flex justify-content-center">
                        <div id="loading_spin" class="spinner-grow p-4 text-info" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="map_div" style="display: none;">
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
                        <div class="float-end">
                            <?php if ($logged_in) { ?>
                                <button class="btn btn-dark mt-3 p-3 px-4" id="findTaxi" disabled>Find a Taxi</button>
                                <button class="btn btn-primary mt-3 p-3 px-4" id="login" hidden>Login & Find a Taxi</button>
                            <?php } else { ?>
                                <button class="btn btn-dark mt-3 p-3 px-4" id="findTaxi" hidden>Find a Taxi</button>
                                <button class="btn btn-primary mt-3 p-3 px-4" id="login">Login & Find a Taxi</button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7 col-sm-12 d-flex justify-content-center">
            <img src="/images/ride.webp" alt="Rating Img" width="400px" height="400px" class="">
        </div>
    </div>
</div>
<?php echo view('layouts/footer'); ?>
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
        document.getElementById('loading_spin').style.display = 'block';
        document.getElementById('map_div').style.display = 'none';

        // Try to get the user's current location
        navigator.geolocation.getCurrentPosition(
            (position) => {
                // The location of the user
                const userPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };

                getAddressFromLatLng(userPosition.lat, userPosition.lng, 'pick_location');
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

                document.getElementById('loading_spin').style.display = 'none';
                document.getElementById('map_div').style.display = 'block';
            },
            () => {
                document.getElementById('loading_spin').style.display = 'none';
                document.getElementById('map_div').style.display = 'block';
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
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAF6DmICYAwskmjHJVMC_2LzCSnsgnogwg&loading=async&libraries=places&callback=myMap"></script>
</body>

</html>