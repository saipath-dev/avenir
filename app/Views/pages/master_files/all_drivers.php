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
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">All Drivers</h4>
                                    </div>
                                </div>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="mainTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Name</th>
                                                                <th>Location</th>
                                                                <th>Languages</th>
                                                                <th>Contact Number</th>
                                                                <th>Vehicle Type</th>
                                                                <th>No of Passengers</th>
                                                                <th>Bata</th>
                                                                <th>Login Enable/Disable</th>
                                                                <th>Available</th>
                                                                <th>Remark</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($suppliers as $supplier) {
                                                                if ($supplier['Mobile'] == 0) {
                                                                    $supplier['Mobile'] = '';
                                                                } else {
                                                                    $supplier['Mobile'] = $supplier['Mobile'];
                                                                }
                                                                $driverLanguages = array_filter(array_map('trim', explode(',', $supplier['language'] ?? '')));
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 5%;"><?php echo $x; ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $supplier['Name']; ?></strong>
                                                                            <small class="text-secondary"><?php echo $supplier['NIC']; ?></small>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width: 15%;"><?php echo $supplier['Address']; ?></td>
                                                                    <td>
                                                                        <?php foreach ($driverLanguages as $lang) { ?>
                                                                            <span class="badge bg-info text-dark me-1"><?php echo $lang; ?></span>
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><small class="text-secondary"><?php echo $supplier['TP']; ?></small><br>
                                                                                <small class="text-secondary"><?php echo $supplier['Mobile']; ?></small></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td><?php echo $supplier['type'] ?? ''; ?></td>
                                                                    <td><?php echo $supplier['passenger_count'] ?? ''; ?></td>
                                                                    <td><?php echo $supplier['bata'] ?? ''; ?></td>
                                                                    <td>
                                                                        <?php if ($supplier['is_login_enabled'] == 1) { ?>
                                                                            <button class="btn btn-sm btn-warning ms-2 toggle-login-btn" data-supplier-id="<?php echo $supplier['ID']; ?>" data-status="0" data-nic="<?php echo $supplier['NIC']; ?>">Disable</button>
                                                                        <?php } else { ?>
                                                                            <button class="btn btn-sm btn-success ms-2 toggle-login-btn" data-supplier-id="<?php echo $supplier['ID']; ?>" data-status="1" data-nic="<?php echo $supplier['NIC']; ?>">Enable</button>
                                                                        <?php } ?>

                                                                    </td>
                                                                    <td>
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input toggle-availability-btn" type="checkbox" role="switch" data-supplier-id="<?php echo $supplier['ID']; ?>" <?php echo (($supplier['is_avalible'] ?? 1) == 1) ? 'checked' : ''; ?>>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width: 15%;"><?php echo $supplier['remark']; ?></td>
                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="<?php echo base_url('master_files/supplier/new_driver_index?id=' . $supplier['ID']) ?>" class="dropdown-item edit-menu-item-btn" data-menu-item-id="<?php echo $supplier['ID']; ?>" data-hotel-id="<?php echo $supplier['Sup_Acc_No']; ?>">Edit</a>
                                                                                </li>
                                                                                <li>
                                                                                    <a class="dropdown-item text-danger delete-menu-item-btn" href="#" data-menu-item-id="<?php echo $supplier['Sup_Acc_No']; ?>">Delete</a>
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
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        var report_title = 'Driver Report';

        $('#mainTable').DataTable({
            "aaSorting": [],

            rowReorder: {
                selector: 'td:nth-child(2)'
            },
            responsive: 'true',
            dom: "Bfrtip",
            buttons: [{
                    extend: 'csvHtml5',
                    text: 'CSV',
                    titleAttr: 'CSV',
                    title: report_title,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'landscape', // Set orientation to landscape
                    pageSize: 'A4', // Set page size to A4
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    },
                    customize: function(win) {}
                },
                {
                    extend: 'print',
                    text: 'Print',
                    titleAttr: 'Print',
                    title: report_title,
                    pageSize: 'A4',
                    orientation: 'portrait',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }

                },
            ]
        });
    </script>
    <script>
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
                    req_input_check();
                }
            });

        });

        function req_input_check() {
            $(':input[required=""],:input[required]').bind('keypress', function(event) {
                if (event.which == 13) {
                    if ($(this).val() == "") {
                        $(this).css("border-color", "red");
                    } else {
                        $(this).css("border-color", "#ccc");
                        $('#form_submit').submit();
                    }
                }
            });
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

        $("#date_form").on('submit', function(e) {
            e.preventDefault();

            var sup_cat = $("#sup_cat").val();

            if (sup_cat) {
                window.location.href = "<?php echo base_url(); ?>master_files/supplier/all?cat=" + sup_cat;
                return false;
            }

        });

        /**************************** INSERT  ****************************/

        $("#form_submit").on('submit', (function(e) {
            e.preventDefault();
            $.ajax({
                url: "<?php echo base_url() ?>master_files/supplier/create",
                type: "POST",
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    console.log(response);
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
                },
                error: function(e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                    });
                }
            });
        }));

        // .delete-menu-item-btn click event
        $(document).on('click', '.delete-menu-item-btn', function() {
            var acc_no = $(this).data('menu-item-id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this supplier!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?php echo base_url() ?>master_files/supplier/delete",
                        type: "GET",
                        data: {
                            Acc_No: acc_no
                        },
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
                        },
                        error: function(e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                            });
                        }
                    });
                }
            });
        });

        // .toggle-login-btn click event
        $(document).on('click', '.toggle-login-btn', function() {
            var supplierId = $(this).data('supplier-id');
            var status = $(this).data('status');
            var nic = $(this).data('nic');
            var actionText = status == 1 ? 'enable' : 'disable';

            // If enabling login, check if NIC exists first
            if (status == 1) {
                // Check if NIC number is empty
                if (!nic || nic == '' || nic == null) {
                    Swal.fire({
                        icon: 'error',
                        title: 'NIC Required',
                        text: 'Cannot enable login. Driver NIC number is empty. Please update the driver details first.',
                    });
                    return false;
                }

                Swal.fire({
                    title: 'Enable Login',
                    html: '<p class="mb-3">Username will be set to the driver\'s NIC number: <strong>' + nic + '</strong></p>' +
                        '<input id="swal-password" type="password" class="form-control" placeholder="Enter Password" required>',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Enable Login',
                    preConfirm: () => {
                        const password = document.getElementById('swal-password').value;
                        if (!password) {
                            Swal.showValidationMessage('Password is required!');
                            return false;
                        }
                        return password;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "<?php echo base_url() ?>master_files/supplier/toggle_login",
                            type: "POST",
                            data: {
                                supplier_id: supplierId,
                                status: status,
                                username: 'nic', // Will be replaced with actual NIC in controller
                                password: result.value
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                    }).then(() => {
                                        location.reload();
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
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: e.responseJSON && e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                                });
                            }
                        });
                    }
                });
            } else {
                // If disabling login, just confirm
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to disable login for this driver!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, disable it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "<?php echo base_url() ?>master_files/supplier/toggle_login",
                            type: "POST",
                            data: {
                                supplier_id: supplierId,
                                status: status
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                    }).then(() => {
                                        location.reload();
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
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: e.responseJSON && e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                                });
                            }
                        });
                    }
                });
            }
        });

        // .toggle-availability-btn change event
        $(document).on('change', '.toggle-availability-btn', function() {
            var checkbox = $(this);
            var supplierId = checkbox.data('supplier-id');
            var status = checkbox.is(':checked') ? 1 : 0;

            $.ajax({
                url: "<?php echo base_url() ?>master_files/supplier/toggle_availability",
                type: "POST",
                data: {
                    supplier_id: supplierId,
                    status: status
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status != 200) {
                        checkbox.prop('checked', !checkbox.is(':checked'));
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    }
                },
                error: function(e) {
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: e.responseJSON && e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                    });
                }
            });
        });
    </script>
</body>




</html>