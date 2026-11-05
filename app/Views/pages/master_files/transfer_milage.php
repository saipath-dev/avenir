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
                                    <div class="col-md-12 d-flex">
                                        <h4 class="p-title">Routing Registry</h4>
                                        <!-- <button type="button" class="btn btn-primary btn-sm ms-auto" data-bs-toggle="offcanvas" data-bs-target="#menuItemOffcanvas" aria-controls="menuItemOffcanvas">
                                            <i class="bi bi-file-arrow-up-fill"></i> Add Excel
                                        </button> -->
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-4">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>
                                                        <input type="hidden" id="t_id">
                                                        <div class="mb-3">
                                                            <label class="form-label" for="type">Transfer Code <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="transfer_code" name="transfer_code" placeholder="Enter Transfer Code" required />
                                                        </div>
                                                        <!-- <div class="mb-3">
                                                            <label class="form-label" for="type">Transfer <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="tranfer" name="tranfer" placeholder="Enter Transfer" required />
                                                        </div> -->
                                                        <div class="mb-3">
                                                            <label class="form-label" for="type">From<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="from" name="from" placeholder="Enter From Location" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="type">To<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="to" name="to" placeholder="Enter To Location" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="type">Estimated Distance<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="estimated" name="estimated" placeholder="Enter Estimated Distance" required readonly />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="type">Actual Distance<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="actual" name="actual" placeholder="Enter Actual Distance" required />
                                                        </div>
                                                        <br>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                                                        <button type="button" id="cancel_btn" class="btn btn-default w-100px me-5px">Cancel</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Transfer Code</th>
                                                                <th>From</th>
                                                                <th>To</th>
                                                                <th>Est. M.</th>
                                                                <th>Actual M.</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($transfer_milage as $milage) {
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 5%;"><?php echo $x; ?></td>
                                                                    <td><?php echo $milage['transfer_code']; ?></td>
                                                                    <td><?php echo $milage['from']; ?></td>
                                                                    <td><?php echo $milage['to']; ?></td>
                                                                    <td><?php echo $milage['estimated_mileage']; ?></td>
                                                                    <td><?php echo $milage['actual_mileage']; ?></td>

                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="#" class="dropdown-item edit-item-btn" data-item-id="<?php echo $milage['id']; ?>">Edit</a>
                                                                                </li>
                                                                                <li>
                                                                                    <a class="dropdown-item text-danger delete-item-btn" href="#" data-item-id="<?php echo $milage['id']; ?>">Delete</a>
                                                                                </li>
                                                                            </ul>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                                $x++;
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
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

                <div class="offcanvas offcanvas-end" id="menuItemOffcanvas" aria-labelledby="menuitemOffcanvasLabel">
                    <div class="offcanvas-header">
                        <h5 id="menuitemOffcanvasLabel">Add Excel File</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <input class="form-control" type="file" id="excel-file" accept=".xlsx">
                        <button class="btn btn-primary px-5 my-3" id="read-button">Add To Database</button>
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
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBW7n1LFvH3Gjn3gWTXzmuBDePpLeBNFIk&callback=initMap&libraries=places"></script>


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

                const from = document.getElementById('from');
                const to = document.getElementById('to');

                var options = {
                    componentRestrictions: {
                        country: 'LK'
                    }
                };

                // Create the autocomplete objects
                const pickAutocomplete = new google.maps.places.Autocomplete(from, options);
                const dropAutocomplete = new google.maps.places.Autocomplete(to, options);

                // Listen for place changes on both pick and drop inputs
                pickAutocomplete.addListener('place_changed', function() {
                    handlePlaceChange(pickAutocomplete, from, 'pick_lat', 'pick_long');
                });

                dropAutocomplete.addListener('place_changed', function() {
                    handlePlaceChange(dropAutocomplete, to, 'drop_lat', 'drop_long');
                });
            }

            function handlePlaceChange(autocomplete, input, latId, lngId) {
                const place = autocomplete.getPlace();
                if (place.geometry) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    // document.getElementById(latId).value = lat;
                    // document.getElementById(lngId).value = lng;

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

                // console.log('calculateAndDisplayRoute()');

                if (pickLat && pickLng && dropLat && dropLng) {

                    const origin = new google.maps.LatLng(pickLat, pickLng);
                    const destination = new google.maps.LatLng(dropLat, dropLng);

                    directionsService.route({
                        origin: origin,
                        destination: destination,
                        travelMode: google.maps.TravelMode.DRIVING
                    }, (response, status) => {

                        if (status === 'OK') {
                            directionsRenderer.setDirections(response);
                            const distance = response.routes[0].legs[0].distance.text;
                            document.getElementById('estimated').value = distance; // Update distance here
                            document.getElementById('actual').value = distance; // Update distance here
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

    <script lang="javascript" src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>

    <script>
        $(document).ready(function() {

            const excelFile = $('#excel-file');
            const readButton = $('#read-button');

            readButton.click(function() {
                const file = excelFile.prop('files')[0];

                if (!file) {
                    console.error('Please select an Excel file');
                    return;
                }

                const reader = new FileReader();

                reader.readAsArrayBuffer(file);

                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const workbook = XLSX.read(arrayBuffer, {
                        type: 'array'
                    });
                    const sheetName = workbook.SheetNames[0];
                    const worksheet = workbook.Sheets[sheetName];

                    const data = [];
                    const range = XLSX.utils.decode_range(worksheet['!ref']);

                    for (let rowNum = 2; rowNum <= range.e.r + 1; rowNum++) {
                        const transfer_codeCell = worksheet['A' + rowNum];
                        const transferCell = worksheet['B' + rowNum];
                        const milageCell = worksheet['C' + rowNum];
                        const milage2Cell = worksheet['D' + rowNum];

                        const transfer_code = transfer_codeCell ? transfer_codeCell.v : '';
                        const transfer = transferCell ? transferCell.v : '';
                        const milage = milageCell ? milageCell.v : '';
                        const milage2 = milage2Cell ? milage2Cell.v : '';

                        if (transfer_code !== '' && transfer !== '') {
                            console.log(`Row ${rowNum}: ${transfer_code}, ${transfer}, ${milage}, ${milage2}`);

                            data.push({
                                transfer_code,
                                transfer,
                                milage,
                                milage2
                            });
                        }
                    }

                    $.ajax({
                        url: 'transfer_milages/bulk_add', // replace with your endpoint URL
                        type: 'POST',
                        data: JSON.stringify(data),
                        contentType: 'application/json',
                        success: function(response) {
                            //if response 200, 201 , 207
                            if (response.status == 207) {
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
                        error: function(error) {
                            console.error('Error sending data');
                            console.error(error);
                        }
                    });
                };
            });
        });

        $(document).ready(function() {
            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();
                var url = '<?php echo base_url(); ?>master_files/transfer_milages/create';
                if ($('#t_id').val() != '') {
                    url = '<?php echo base_url(); ?>master_files/transfer_milages/update/' + $('#t_id').val();
                }
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: $(this).serialize(),
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
                                title: 'Error',
                                text: response.message,
                            });
                        }
                    }
                });
            });
        });

        // .edit-btn click
        $('.edit-item-btn').click(function() {
            // get profile id
            var id = $(this).attr('data-item-id');
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>master_files/transfer_milages/show/' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        $('#t_id').val(response.data.id);
                        $('#transfer_code').val(response.data.transfer_code);
                        // $('#tranfer').val(response.data.transfer);
                        $('#from').val(response.data.from);
                        $('#to').val(response.data.to);
                        $('#estimated').val(response.data.estimated_mileage);
                        $('#actual').val(response.data.actual_mileage);
                        // change button text
                        $('#transfer_update_form button[type="submit"]').html('Update');
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

        // delete-btn click
        $('.delete-item-btn').click(function() {
            Swal.fire({
                title: 'Are you sure ?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // get profile id
                    var id = $(this).attr('data-item-id');
                    // ajax call
                    $.ajax({
                        url: '<?php echo base_url(); ?>master_files/transfer_milages/delete',
                        type: 'GET',
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $('.delete-item-btn').attr('disabled', true);
                            $('.delete-item-btn').html('<i class="fa fa-spinner fa-spin"></i>');
                        },
                        success: function(data) {
                            if (data.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: data.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(function() {
                                    window.location.href = "<?php echo base_url(); ?>master_files/transfer_milages";
                                });
                            } else if (data.status == 500) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: data.message,
                                });
                            } else if (data.status == 400) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: data.message,
                                });
                            }
                            $('.delete-item-btn').attr('disabled', false);
                            $('.delete-item-btn').html('<i class="fa fa-trash"></i>');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                            $('.delete-item-btn').attr('disabled', false);
                            $('.delete-item-btn').html('<i class="fa fa-trash"></i>');
                        }
                    });
                }
            })

        });

        // .cancel-btn click
        $('#cancel_btn').click(function() {
            // refresh page
            window.location.href = "<?php echo base_url(); ?>master_files/transfer_milages";
        });
    </script>
</body>


</html>