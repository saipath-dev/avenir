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
                                        <h4 class="p-title">Place to Visit</h4>
                                        <!--Scan excel file-->
                                        <!-- <button type="button" class="btn btn-primary btn-sm ms-auto" data-bs-toggle="offcanvas" data-bs-target="#menuItemOffcanvas" aria-controls="menuItemOffcanvas">
                                            <i class="bi bi-file-arrow-up-fill"></i> Add Excel
                                        </button> -->
                                    </div>
                                </div>


                                <div class="row clearfix mt-3">
                                    <div class="col-md-4">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" enctype="multipart/form-data" accept-charset="utf-8">
                                                    <fieldset>
                                                        <input type="hidden" id="t_id">
                                                        <div class="mb-3">
                                                            <label class="form-label" for="name">Place Name <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="name" name="name" placeholder="Enter Place Name" required />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="code">Code </label>
                                                            <input class="form-control" type="text" id="code" name="code" placeholder="Enter Code" />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="location">Google Location<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="location" name="location" placeholder="" value="" required />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="descript">Description</label>
                                                            <input class="form-control" type="text" id="descript" name="descript" placeholder="" value="" />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="entrance_fee">Fee($)</label>
                                                            <input class="form-control" type="text" id="entrance_fee" name="entrance_fee" placeholder="" value="0.00" />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="entrance_fee_lkr">Fee (LKR)</label>
                                                            <input class="form-control" type="text" id="entrance_fee_lkr" name="entrance_fee_lkr" placeholder="" value="0.00" />
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="img">Image</label>
                                                            <input class="form-control" type="file" id="img" name="img" placeholder="" value="" />
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input me-3" type="checkbox" role="switch" id="is_a_place" checked>
                                                                <label class="form-check-label" for="is_a_place">Is a Place</label>
                                                            </div>
                                                            <div class="form-check form-switch mt-2">
                                                                <input class="form-check-input me-3" type="checkbox" role="switch" id="direct_payment">
                                                                <label class="form-check-label" for="direct_payment">Direct Payment</label>
                                                            </div>
                                                            <div class="form-check form-switch mt-2">
                                                                <input class="form-check-input me-3" type="checkbox" role="switch" id="is_prepaid">
                                                                <label class="form-check-label" for="is_prepaid">Prepaid (default, excluded from driver advance)</label>
                                                            </div>
                                                        </div>

                                                        <br>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
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
                                                                <th>Place Name</th>
                                                                <th>Code</th>
                                                                <th>Location</th>
                                                                <th>Description</th>
                                                                <th>Fee($)</th>
                                                                <th>Fee(LKR)</th>
                                                                <th>Is a Place</th>
                                                                <th>Direct Payment</th>
                                                                <th>Prepaid</th>
                                                                <th>Image</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($entrance_fee as $fee) {

                                                                $place = $fee['type'] == 1 ? 'assets/images/ok.png' : 'assets/images/close.png';
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 5%;"><?php echo $x; ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $fee['name']; ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $fee['code']; ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $fee['location']; ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $fee['description']; ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo number_format($fee['entranceFee'], 2); ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo number_format($fee['entranceFeeLkr'] ?? 0, 2); ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><img src="<?php echo base_url() ?><?php echo $place ?>" alt="..." width="20px" class="me-2"></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($fee['direct_payment'] == 1) { ?>
                                                                            <span class="badge bg-success">Yes</span>
                                                                        <?php } else { ?>
                                                                            <span class="badge bg-secondary">No</span>
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (($fee['is_prepaid'] ?? 0) == 1) { ?>
                                                                            <span class="badge bg-success">Yes</span>
                                                                        <?php } else { ?>
                                                                            <span class="badge bg-secondary">No</span>
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <?php if ($fee['img'] == '') { ?>
                                                                                <img src="<?php echo base_url("uploads/places/no-image.png"); ?>" style="max-width: 100px; max-height: 100px;">
                                                                            <?php } else { ?>
                                                                                <img src="<?php echo base_url("uploads/places/" . $fee['img']); ?>" alt="<?php echo $fee['name']; ?>" style="max-width: 100px; max-height: 100px;">
                                                                            <?php } ?>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="#" class="dropdown-item edit-item-btn" data-item-id="<?php echo $fee['id']; ?>">Edit</a>
                                                                                </li>
                                                                                <li>
                                                                                    <a class="dropdown-item text-danger delete-item-btn" href="#" data-item-id="<?php echo $fee['id']; ?>" data-activity-id="<?php echo $fee['name']; ?>">Delete</a>
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

                <!-- <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="editModalLabel">Edit Place To Visit</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="javascript:void(0);" id="update_form" accept-charset="utf-8">
                                    <input type="hidden" name="row_id" id="row_id">
                                    <div class="row">
                                        <div class="mb-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="name">Place Name <font color="#FF0000"><strong>*</strong></font></label>
                                                <input class="form-control" type="text" id="name_up" name="name_up" placeholder="Enter Place Name" required />
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="code">Code <font color="#FF0000"><strong>*</strong></font></label>
                                                <input class="form-control" type="text" id="code_up" name="code_up" placeholder="Enter Code" required />
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="location">Google Location<font color="#FF0000"><strong>*</strong></font></label>
                                                <input class="form-control" type="text" id="location_up" name="location_up" placeholder="" value="" required />
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="descript">Description</label>
                                                <input class="form-control" type="text" id="descript" name="descript" placeholder="" value="" />
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="entrance_fee">Fee</label>
                                                <input class="form-control" type="text" id="entrance_fee" name="entrance_fee" placeholder="" value="" />
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="img">Image</label>
                                                <input class="form-control" type="file" id="img" name="img" placeholder="" value="" />
                                            </div>

                                            <button type="submit" class="btn btn-success mt-2 update_vehicle" id="update_vehicle">Save</button>
                                        </div>

                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div> -->

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

                const pickInput = document.getElementById('location');
                // const dropInput = document.getElementById('drop_location');

                // Create the autocomplete objects
                const pickAutocomplete = new google.maps.places.Autocomplete(pickInput, options);
                // const dropAutocomplete = new google.maps.places.Autocomplete(dropInput);

            }

            initMap();


        });
    </script>

    <!-- use xlsx.full.min.js from version 0.20.2 -->
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
                        const codeCell = worksheet['A' + rowNum];
                        const locationCell = worksheet['B' + rowNum];
                        const entranceFeeCell = worksheet['C' + rowNum] || worksheet['D' + rowNum] || worksheet['E' + rowNum];

                        const code = codeCell ? codeCell.v : '';
                        const location = locationCell ? locationCell.v : '';
                        let entranceFee = '';

                        if (entranceFeeCell && entranceFeeCell.v) {
                            entranceFee = parseFloat(entranceFeeCell.v.toString().replace(/[^\d.-]/g, ''));
                        }

                        if (code || location || entranceFee) {
                            data.push({
                                code,
                                location,
                                entranceFee
                            });
                        }
                    }

                    $.ajax({
                        url: 'entrance_fee/bulk_add', // replace with your endpoint URL
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

            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var is_a_place = $('#is_a_place').is(':checked') ? 1 : 2;
                formData.append('type', is_a_place);
                var direct_payment = $('#direct_payment').is(':checked') ? 1 : 0;
                formData.append('direct_payment', direct_payment);
                var is_prepaid = $('#is_prepaid').is(':checked') ? 1 : 0;
                formData.append('is_prepaid', is_prepaid);

                var url = '<?= base_url('master_files/entrance_fee/create') ?>';
                if ($('#t_id').val() != '') {
                    url = '<?php echo base_url(); ?>master_files/entrance_fee/update/' + $('#t_id').val();
                }
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 201) {
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
                url: '<?php echo base_url(); ?>master_files/entrance_fee/show/' + id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    // $('#menuItemOffcanvas button[type="submit"]').attr('disabled', true);
                    // $('#menuItemOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {
                        $('#t_id').val(response.data.id);
                        $('#name').val(response.data.name);
                        $('#code').val(response.data.code);
                        $('#location').val(response.data.location);
                        $('#entrance_fee').val(response.data.entranceFee);
                        $('#entrance_fee_lkr').val(response.data.entranceFeeLkr);
                        $('#descript').val(response.data.description);
                        $('#img_up').val(response.data.img);

                        if (response.data.type == 1) {
                            $('#is_a_place').prop('checked', true);
                        } else {
                            $('#is_a_place').prop('checked', false);
                        }

                        $('#direct_payment').prop('checked', response.data.direct_payment == 1);
                        $('#is_prepaid').prop('checked', response.data.is_prepaid == 1);

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Not Found',
                            text: response.message,
                        });
                        $('#menuItemOffcanvas button[type="submit"]').attr('disabled', true);
                        $('#menuItemOffcanvas button[type="submit"]').html('Update');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#menuItemOffcanvas button[type="submit"]').attr('disabled', false);
                    $('#menuItemOffcanvas button[type="submit"]').html('Update');
                }
            });
        });

        // submit entry_fee_update_form
        $('#entry_fee_update_form').submit(function(e) {
            e.preventDefault();
            // get entry_fee id
            var id = $('#entry_fee_id_edit').val();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>master_files/entrance_fee/update',
                type: 'POST',
                data: {
                    id: id,
                    code: $('#entry_fee_code_edit').val(),
                    location: $('#entry_fee_location_edit').val(),
                    entrance_fee: $('#entry_fee_edit').val(),
                },
                cache: false,
                processData: true,
                beforeSend: function() {
                    $('#entry_fee_update_form button[type="submit"]').attr('disabled', true);
                    $('#entry_fee_update_form button[type="submit"]').html('Please wait...');
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
                            window.location.href = "<?php echo base_url(); ?>master_files/entrance_fee";
                        });
                    } else if (data.status == 400) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Missing Data',
                            text: data.message,
                        });
                    } else if (data.status == 409) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Duplicate Profile Name',
                            text: data.message,
                        });
                    } else if (data.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        });
                    }
                    $('#entry_fee_update_form button[type="submit"]').attr('disabled', false);
                    $('#entry_fee_update_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#entry_fee_update_form button[type="submit"]').attr('disabled', false);
                    $('#entry_fee_update_form button[type="submit"]').html('Create');
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
                    var activity_id = $(this).attr('data-activity-id');
                    // ajax call
                    $.ajax({
                        url: '<?php echo base_url(); ?>master_files/entrance_fee/delete',
                        type: 'GET',
                        data: {
                            id: id,
                            activity_id: activity_id
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
                                    window.location.href = "<?php echo base_url(); ?>master_files/entrance_fee";
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
    </script>
</body>




</html>