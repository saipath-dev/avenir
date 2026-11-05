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
                                <div class="row">
                                    <div class="col-md-10 d-flex align-items-center justify-content-between">
                                        <h4 class="p-title">New Driver</h4>
                                        <a href="<?= base_url('master_files/supplier/all_drivers') ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-people-fill me-1"></i>All Drivers
                                        </a>
                                    </div>
                                </div>

                                <?php
                                $category = '';
                                $name = '';
                                $address = '';
                                $nic = '';
                                $email = '';
                                $tel_mobile = '';
                                $tel_home = '';
                                $rep_office = '';
                                $rep_mobile = '';
                                $credit_days = '';
                                $credit_limit = '';
                                $cheque = '';
                                $cash = '';
                                $hotel = '';
                                $sup_bank_acc = '';
                                $bank = '';
                                $branch = '';
                                $remark = '';
                                $dled = '';
                                $gled = '';
                                $dlimg = '';
                                $glimg = '';
                                $language = '';
                                $acc_holder = '';
                                $type = '';
                                $passenger_count = '';
                                $km_rate = '';
                                $v_num = '';
                                $bata_per_day = '';
                                $rate_vehicle = '';
                                $vimg = '';
                                $vlimg = '';
                                $vled = '';
                                $viimg = '';
                                $vied = '';
                                if (isset($supplier) && !empty($supplier)) {
                                    $category = $supplier['sup_cat'];
                                    $name = $supplier['Name'];
                                    $address = $supplier['Address'];
                                    $nic = $supplier['NIC'];
                                    $email = $supplier['email'];
                                    $tel_mobile = $supplier['Mobile'];
                                    $tel_home = $supplier['TP'];
                                    $rep_office = $supplier['rep_offce'];
                                    $rep_mobile = $supplier['rep_mobile'];
                                    $credit_days = $supplier['cr_dyz'];
                                    $credit_limit = $supplier['cr_lmt'];
                                    $cheque = $supplier['chq'];
                                    $cash = $supplier['csh'];
                                    $hotel = $supplier['Sup_Acc_No'];
                                    $sup_bank_acc = $supplier['sup_bank_acc'];
                                    $bank = $supplier['bank_id'];
                                    $branch = $supplier['branch_id'];
                                    $remark = $supplier['remark'];
                                    $dled = $supplier['dled'];
                                    $gled = $supplier['gled'];
                                    $dlimg = $supplier['dlimg'];
                                    $glimg = $supplier['glimg'];
                                    $language = $supplier['language'];
                                    $acc_holder = $supplier['acc_holder'];
                                    $type = $supplier['type'];
                                    $passenger_count = $supplier['passenger_count'];
                                    $km_rate = $supplier['km_rate'];
                                    $v_num = $supplier['v_num'];
                                    $bata_per_day = $supplier['bata'];
                                    $rate_vehicle = $supplier['action'];
                                    $vimg = $supplier['vimg'];
                                    $vlimg = $supplier['vlimg'];
                                    $vled = $supplier['vehi_license_expire_date'];
                                    $viimg = $supplier['viimg'];
                                    $vied = $supplier['vehi_insurance_expire_date'];
                                }
                                ?>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8" enctype="multipart/form-data">
                                                    <fieldset>
                                                        <div class="mb-3" hidden>
                                                            <label class="form-label" for="sup_cat">Supplier Catogory<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select class="form-control txt_nav" name="sup_cat" id="sup_cat" disabled>
                                                                <option value="2" selected>Transport Agency</option>
                                                            </select>
                                                        </div>
                                                        <input type="hidden" name="Acc_No" id="Acc_No" value="<?php echo $hotel; ?>">

                                                        <h6 class="text-uppercase text-muted mb-3">Driver Information</h6>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="supplier_name">Driver Name<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control ui-autocomplete-input txt_nav" type="text" id="supplier_name" name="supplier_name" placeholder="" value="<?php echo $name; ?>" required />
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="address">Location<font color="#FF0000"><strong>*</strong></font></label>
                                                                <input class="form-control txt_nav" type="text" id="address" name="address" placeholder="" value="<?php echo $address; ?>" required />
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="nic">NIC/Br No</label>
                                                                <input class="form-control txt_nav" type="text" id="nic" name="nic" placeholder="" value="<?php echo $nic; ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="email">Email</label>
                                                                <input class="form-control txt_nav" type="email" id="email" name="email" value="<?php echo $email; ?>" placeholder="" />
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="tel_mobile">Tel No (Mobile)</label>
                                                                <input type="text" class="form-control txt_nav" placeholder="" id="tel_mobile" name="tel_mobile" value="<?php echo $tel_mobile; ?>" />
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="tel_home">Tel No (Home)</label>
                                                                <input type="text" class="form-control txt_nav" id="tel_home" name="tel_home" placeholder="" value="<?php echo $tel_home; ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="remark">Remark</label>
                                                                <textarea class="form-control txt_nav" id="remark" name="remark" placeholder=""><?php echo $remark; ?></textarea>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="language">Language</label>
                                                                <?php $selectedLanguages = array_filter(array_map('trim', explode(',', $language))); ?>
                                                                <select name="language[]" id="language" class="js-example-basic-multiple form-control" multiple style="width: 100%;">
                                                                    <?php foreach (['Sinhala', 'Japanese', 'English', 'Hindi', 'Tamil'] as $lang) { ?>
                                                                        <option value="<?php echo $lang; ?>" <?php echo in_array($lang, $selectedLanguages) ? 'selected' : ''; ?>><?php echo $lang; ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-3" hidden>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Rep Office</label>
                                                                <input type="text" class="form-control txt_nav" placeholder="" id="rep_office" name="rep_office" value="<?php echo $rep_office; ?>" />

                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">Rep Mobile</label>
                                                                <input type="text" class="form-control txt_nav" placeholder="" id="rep_mobile" name="rep_mobile" value="<?php echo $rep_mobile; ?>" />

                                                            </div>
                                                        </div>

                                                        <hr class="my-4">
                                                        <h6 class="text-uppercase text-muted mb-3">Bank Details</h6>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Bank</label>
                                                                <select name="bank" id="bank" class="js-example-basic-single form-control" style="width: 100%;">
                                                                    <?php
                                                                    echo "<option></option>";
                                                                    foreach ($banks as $data) {
                                                                        $selected = $bank == $data['bank_id'] ? 'selected' : '';
                                                                        echo "<option value='" . $data['bank_id'] . "' " . $selected . " >" . $data['name'] . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Branch</label>
                                                                <select name="branch" id="branch" class="js-example-basic-single form-control" style="width: 100%;">
                                                                    <?php
                                                                    // echo "<option></option>";
                                                                    // foreach ($branches as $data) {
                                                                    //     $selected = $branch == $data['branch_id'] ? 'selected' : '';
                                                                    //     echo "<option value='" . $data['branch_id'] . "' " . $selected . " >" . $data['name'] . "</option>";
                                                                    // }
                                                                    ?>
                                                                </select>
                                                                <input type="hidden" name="branchedi" id="branchedi" value="<?php echo $branch; ?>">
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Account Holder</label>
                                                                <input type="text" class="form-control txt_nav" placeholder="" id="bank_acc_holder" name="bank_acc_holder" value="<?php echo $acc_holder; ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Account Number</label>
                                                                <input type="text" class="form-control txt_nav" placeholder="" id="sup_bank_acc" name="sup_bank_acc" value="<?php echo $sup_bank_acc; ?>" />
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Credit Days</label>
                                                                <input type="text" class="form-control txt_nav" placeholder="" id="credit_days" name="credit_days" value="<?php echo $credit_days; ?>" onkeypress="return isNumberKey(event, this)" />
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Credit Limit</label>
                                                                <input type="text" class="form-control txt_nav" id="credit_limit" name="credit_limit" placeholder="" value="<?php echo $credit_limit; ?>" onkeypress="return isNumberKey(event, this)" />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Payment Method</label>
                                                                <div>
                                                                    <input class="form-check-input txt_nav" type="checkbox" name="cheque" id="cheque" value="1" <?php echo $cheque == 1 ? 'checked' : ''; ?>>
                                                                    <label class="form-label me-3" for="cheque">
                                                                        &nbsp;&nbsp;&nbsp;BT
                                                                    </label>
                                                                    <input class="form-check-input ms-4 txt_nav" type="checkbox" name="cash" id="cash" value="1" <?php echo $cash == 1 ? 'checked' : ''; ?>>
                                                                    <label class="form-label" for="cash">
                                                                        &nbsp;&nbsp;&nbsp;Cash
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <hr class="my-4">
                                                        <h6 class="text-uppercase text-muted mb-3">Vehicle Information</h6>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="type">Vehicle Type</label>
                                                                <select name="type" id="type" class="js-example-tags form-control" style="width: 100%;">
                                                                    <?php
                                                                    $vehicleTypes = ['Standard Sedan', 'Luxury Sedan', 'Van', 'Luxury Coach', 'AC Bus'];
                                                                    if (!empty($type) && !in_array($type, $vehicleTypes)) {
                                                                        $vehicleTypes[] = $type;
                                                                    }
                                                                    echo "<option></option>";
                                                                    foreach ($vehicleTypes as $vt) {
                                                                        $selected = $type == $vt ? 'selected' : '';
                                                                        echo "<option value='" . $vt . "' " . $selected . ">" . $vt . "</option>";
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="v_num">Vehicle Number</label>
                                                                <input class="form-control txt_nav" type="text" id="v_num" name="vnu" placeholder="" value="<?php echo $v_num; ?>" />
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="passenger_count">No. of Passengers</label>
                                                                <input class="form-control txt_nav" type="text" id="passenger_count" name="passenger_count" placeholder="" value="<?php echo $passenger_count; ?>" onkeypress="return isNumberKey(event, this)" />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="km_rate">Km/Hour Rate</label>
                                                                <input class="form-control txt_nav" type="text" id="km_rate" name="km_rate" placeholder="" value="<?php echo $km_rate; ?>" onkeypress="return isNumberKey(event, this)" />
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="bata_per_day">Bata Per Day</label>
                                                                <input class="form-control txt_nav" type="text" id="bata_per_day" name="bata_per_day" placeholder="" value="<?php echo $bata_per_day; ?>" onkeypress="return isNumberKey(event, this)" />
                                                            </div>
                                                            <div class="col-md-4 mb-3 d-flex align-items-center">
                                                                <div class="form-check mt-4">
                                                                    <input class="form-check-input" type="checkbox" name="rate_vehicle" id="rate_vehicle" value="1" <?php echo $rate_vehicle == 1 ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="rate_vehicle">Is Rate Vehicle</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label" for="vimg">Vehicle Image</label>
                                                                <input class="form-control" type="file" id="vimg" name="vimg" placeholder="" />
                                                                <?php if ($vimg) { ?>
                                                                    <img src="<?php echo base_url('uploads/vehicle/' . $vimg); ?>" class="mt-2" alt="Vehicle Image" width="100" height="100" />
                                                                <?php } ?>
                                                            </div>
                                                        </div>

                                                        <hr class="my-4">
                                                        <h6 class="text-uppercase text-muted mb-3">Documents</h6>
                                                        <div class="row">
                                                            <div class="col-md-5 mb-3">
                                                                <label class="form-label" for="dlimg">Driving License Image</label>
                                                                <input class="form-control" type="file" id="dlimg" name="dlimg" placeholder="" value="<?= $dlimg; ?>" />
                                                                <?php if ($dlimg) { ?>
                                                                    <img src="<?php echo base_url('uploads/driver/license/' . $dlimg); ?>" class="mt-2" alt="Driving License Image" width="100" height="100" />
                                                                <?php } ?>
                                                            </div>
                                                            <div class="col-md-5 mb-3">
                                                                <label class="form-label" for="dled">Driving License Expire Date</label>
                                                                <input class="form-control" type="date" id="dled" name="dled" placeholder="" value="<?= $dled; ?>" />
                                                            </div>

                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-5 mb-3">
                                                                <label class="form-label" for="glimg">Guide License Image</label>
                                                                <input class="form-control" type="file" id="glimg" name="glimg" placeholder="" value="<?= $glimg; ?>" />
                                                                <?php if ($glimg) { ?>
                                                                    <img src="<?php echo base_url('uploads/driver/license/guide/' . $glimg); ?>" class="mt-2" alt="Guide License Image" width="100" height="100" />
                                                                <?php } ?>
                                                            </div>
                                                            <div class="col-md-5 mb-3">
                                                                <label class="form-label" for="gled">Guide License Expire Date</label>
                                                                <input class="form-control" type="date" id="gled" name="gled" placeholder="" value="<?= $gled; ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-5 mb-3">
                                                                <label class="form-label" for="vlimg">Vehicle License Image</label>
                                                                <input class="form-control" type="file" id="vlimg" name="vlimg" placeholder="" />
                                                                <?php if ($vlimg) { ?>
                                                                    <img src="<?php echo base_url('uploads/vehicle/license/' . $vlimg); ?>" class="mt-2" alt="Vehicle License Image" width="100" height="100" />
                                                                <?php } ?>
                                                            </div>
                                                            <div class="col-md-5 mb-3">
                                                                <label class="form-label" for="vled">Vehicle License Expire Date</label>
                                                                <input class="form-control" type="date" id="vled" name="vled" placeholder="" value="<?= $vled; ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-5 mb-3">
                                                                <label class="form-label" for="viimg">Vehicle Insurance Image</label>
                                                                <input class="form-control" type="file" id="viimg" name="viimg" placeholder="" />
                                                                <?php if ($viimg) { ?>
                                                                    <img src="<?php echo base_url('uploads/vehicle/insurance/' . $viimg); ?>" class="mt-2" alt="Vehicle Insurance Image" width="100" height="100" />
                                                                <?php } ?>
                                                            </div>
                                                            <div class="col-md-5 mb-3">
                                                                <label class="form-label" for="vied">Vehicle Insurance Expire Date</label>
                                                                <input class="form-control" type="date" id="vied" name="vied" placeholder="" value="<?= $vied; ?>" />
                                                            </div>
                                                        </div>

                                                        <hr class="my-4">
                                                        <button type="submit" id="submit_btn" class="btn btn-success  me-5px">Save</button>
                                                        <button type="reset" class="btn btn-default ">Cancel</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end .h-100-->
                            <div id="map" class="rounded d-none"></div>

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
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
    <!-- <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAEg8Qki_788QHwsbPoh8N2EW_TiWrb0Rg&loading=async&libraries=places"></script> -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBW7n1LFvH3Gjn3gWTXzmuBDePpLeBNFIk&callback=initMap&libraries=places"></script>

    <script>
        // document.addEventListener('DOMContentLoaded', function() {
        //     let pickLat = 0,
        //         pickLng = 0,
        //         dropLat = 0,
        //         dropLng = 0;
        //     let map, directionsService, directionsRenderer;

        //     function initMap() {
        //         directionsService = new google.maps.DirectionsService();
        //         directionsRenderer = new google.maps.DirectionsRenderer();
        //         map = new google.maps.Map(document.getElementById('map'), {
        //             center: {
        //                 lat: 7.8731,
        //                 lng: 80.7718
        //             },
        //             zoom: 8
        //         });
        //         directionsRenderer.setMap(map);

        //         var options = {
        //             componentRestrictions: {
        //                 country: 'LK'
        //             }
        //         };
        //         const pickInput = document.getElementById('address');
        //         // const dropInput = document.getElementById('drop_location');

        //         // Create the autocomplete objects
        //         const pickAutocomplete = new google.maps.places.Autocomplete(pickInput, options);
        //         // const dropAutocomplete = new google.maps.places.Autocomplete(dropInput);

        //         // // Listen for place changes on both pick and drop inputs
        //         // pickAutocomplete.addListener('place_changed', function() {
        //         //     handlePlaceChange(pickAutocomplete, pickInput, 'pick_lat', 'pick_long');
        //         // });

        //         // dropAutocomplete.addListener('place_changed', function() {
        //         //     handlePlaceChange(dropAutocomplete, dropInput, 'drop_lat', 'drop_long');
        //         // });
        //     }

        //     // function handlePlaceChange(autocomplete, input, latId, lngId) {
        //     //     const place = autocomplete.getPlace();
        //     //     if (place.geometry) {
        //     //         const lat = place.geometry.location.lat();
        //     //         const lng = place.geometry.location.lng();
        //     //         document.getElementById(latId).value = lat;
        //     //         document.getElementById(lngId).value = lng;

        //     //         // Update the pickLat, pickLng, dropLat, and dropLng variables
        //     //         if (latId === 'pick_lat') {
        //     //             pickLat = lat;
        //     //             pickLng = lng;
        //     //         } else {
        //     //             dropLat = lat;
        //     //             dropLng = lng;
        //     //         }

        //     //         // calculateAndDisplayRoute();
        //     //     }
        //     // }

        //     // function calculateAndDisplayRoute() {

        //     //     console.log('calculateAndDisplayRoute()');

        //     //     if (pickLat && pickLng && dropLat && dropLng) {

        //     //         console.log('Inside if condition')

        //     //         const origin = new google.maps.LatLng(pickLat, pickLng);
        //     //         const destination = new google.maps.LatLng(dropLat, dropLng);

        //     //         console.log("origin: ");
        //     //         console.log(origin);

        //     //         directionsService.route({
        //     //             origin: origin,
        //     //             destination: destination,
        //     //             travelMode: google.maps.TravelMode.DRIVING
        //     //         }, (response, status) => {

        //     //             console.log(response);

        //     //             if (status === 'OK') {
        //     //                 directionsRenderer.setDirections(response);
        //     //                 const distance = response.routes[0].legs[0].distance.text;
        //     //                 document.getElementById('gdistance').value = distance; // Update distance here
        //     //                 // calculatePrice(distance);
        //     //                 // document.getElementById('findTaxi').disabled = false;
        //     //             } else {
        //     //                 console.error('Directions request failed due to ' + status);
        //     //             }
        //     //         });
        //     //     }
        //     // }

        //     // function calculatePrice(distance) {
        //     //     let price = 0;
        //     //     const distanceInKm = parseFloat(distance.split(' ')[0]);
        //     //     if (distanceInKm <= 5) {
        //     //         price = distanceInKm * 100;
        //     //     } else if (distanceInKm <= 10) {
        //     //         price = (5 * 100) + ((distanceInKm - 5) * 80);
        //     //     } else {
        //     //         price = (5 * 100) + (5 * 80) + ((distanceInKm - 10) * 60);
        //     //     }
        //     //     document.getElementById('gprice').innerText = 'LKR ' + price.toFixed(2);
        //     // }

        //     initMap();


        // });

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
                const pickInput = document.getElementById('address');
                const pickAutocomplete = new google.maps.places.Autocomplete(pickInput, options);


            }
            // Initialize the map
            initMap();
        });
    </script>
    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });
        $('.js-example-basic-multiple').select2({
            placeholder: "Select language(s)",
            allowClear: true
        });
        $('.js-example-tags').select2({
            placeholder: "Select or type vehicle type",
            allowClear: true,
            tags: true
        });
    </script>
    <script>
        // document ready function
        $(document).ready(function() {
            // get the bank id
            var bank_id = $('#bank').val();
            // get the branch id
            var branch_id = $('#branchedi').val();
            // get the supplier id
            var supplier_id = "<?php echo isset($supplier) && !empty($supplier) ? $supplier['ID'] : ''; ?>";

            // if supplier id is not empty
            if (supplier_id != '') {
                // get the branches of the bank
                $.ajax({
                    url: "<?php echo base_url('master_files/supplier/get_branches'); ?>",
                    type: "GET",
                    data: {
                        bank_id: bank_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            var branches = response.data;

                            var options = "<option>Select Branch</option>";
                            for (var i = 0; i < branches.length; i++) {
                                var selected = branches[i].id == branch_id ? 'selected' : '';
                                options += "<option value='" + branches[i].id + "' " + selected + ">" + branches[i].name + "</option>";
                            }
                            $('#branch').html(options); // Populate the branch dropdown
                        } else {
                            $('#branch').val('No branches found or error in response.'); // Clear the branch dropdown
                        }
                    },
                    error: function(e) {
                        console.log(e);
                    }
                });
            }
        });

        function isNumberKey(evt, obj) {
            var charCode = (evt.which) ? evt.which : event.keyCode
            var value = obj.value;
            var dotcontains = value.indexOf(".") != -1;
            if (dotcontains)
                if (charCode == 46) return false;
            if (charCode == 46) return true;
            if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;
            return true;
        }

        $(function() {
            $(':input[required=""],:input[required]').bind('focusout', function() {
                if ($(this).val() == "") {
                    $(this).css("border-color", "red");
                } else {
                    $(this).css("border-color", "#ccc");
                }
            });

            $('.txt_nav:first').focus();
            var $inp = $('.txt_nav');
            $inp.bind('keydown', function(e) {
                var n = $inp.length;
                var key = e.which;
                if (key == 13) {
                    e.preventDefault();
                    var nxtIdx = $inp.index(this) + 1;
                    if (nxtIdx < n) {
                        $('.txt_nav')[nxtIdx].focus();
                    } else {
                        $('.txt_nav')[nxtIdx - 1].blur();
                        $('#submit_btn').focus();
                    }
                }
            });

            $("#submit_btn").keypress(function(event) {
                if (event.which == 13) {
                    event.preventDefault();
                    req_input_check();
                }
            });

        });

        function req_input_check() {
            var allValid = true;
            $(':input[required=""],:input[required]').each(function() {
                if ($(this).val() == "") {
                    $(this).css("border-color", "red");
                    allValid = false;
                } else {
                    $(this).css("border-color", "#ccc");
                }
            });
            if (allValid) {
                $('#form_submit').submit();
            }
        }

        $('#exampleCheck').change(function() {
            if ($(this).is(':checked')) {
                $("#Services").removeAttr("hidden");
                $("#Services1").removeAttr("hidden");
            } else {
                $("#Services").attr('hidden', true);
                $("#Services1").attr('hidden', true);
            }
        });

        /**************************** INSERT  ****************************/

        $("#form_submit").on('submit', (function(e) {
            e.preventDefault();

            var bank_name = $('#bank option:selected').text();
            var branch_name = $('#branch option:selected').text();
            var sup_cat = $('#sup_cat').val();

            // append to FormData
            var formData = new FormData(this);
            formData.append('bank_name', bank_name);
            formData.append('branch_name', branch_name);
            formData.append('sup_cat', sup_cat);


            var endpoint = "<?php echo base_url('master_files/supplier/create'); ?>";
            var id = "<?php echo isset($supplier) && !empty($supplier) ? $supplier['ID'] : ''; ?>";
            if (id != '') {
                endpoint = "<?php echo base_url('master_files/supplier/update/'); ?>" + id;
            }
            console.log(formData);

            $.ajax({
                url: endpoint,
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                beforeSend: function() {
                    // Show loader
                    $('#submit_btn').html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
                },
                success: function(response) {
                    $('#submit_btn').html('Save').prop('disabled', false);
                    console.log(response);
                    if (response.status == 201 || response.status == 200) {
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
                            title: 'Error',
                            text: response.message,
                        });
                    }
                },
                error: function(e) {
                    $('#submit_btn').html('Save').prop('disabled', false);
                    console.log(e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                    });
                }
            });
        }));

        // load branch data change bank
        $('#bank').on('change', function() {
            var bank_id = $(this).val();
            $.ajax({
                url: "<?php echo base_url('master_files/supplier/get_branches'); ?>",
                type: "GET",
                data: {
                    bank_id: bank_id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        var branches = response.data;

                        var options = "<option>Select Branch</option>";
                        for (var i = 0; i < branches.length; i++) {
                            options += "<option value='" + branches[i].id + "'>" + branches[i].name + "</option>";
                        }
                        $('#branch').html(options); // Populate the branch dropdown
                    } else {
                        $('#branch').val('No branches found or error in response.'); // Clear the branch dropdown
                    }
                },
                error: function(e) {
                    console.log(e);
                }
            });
        });
    </script>
</body>


</html>