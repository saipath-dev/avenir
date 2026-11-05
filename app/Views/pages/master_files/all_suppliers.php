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
                                        <h4 class="p-title">All Suppliers</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="date_form" accept-charset="utf-8" action="javascript:void(0)">
                                                    <div class="row">
                                                        <div class="mb-3 col-md-12">
                                                            <label class="form-label" for="sup_cat">Supplier Catogory</label>
                                                            <select name="sup_cat" id="sup_cat" class="js-example-basic-single form-control" style="width: 100%;">
                                                                <option value="">Select a Supplier...</option>
                                                                <option value="1">Hotel</option>
                                                                <option value="3">Utility Bills</option>
                                                                <option value="4">Restaurant & Other Suppliers</option>
                                                                <option value="99">All</option>
                                                            </select>
                                                        </div>

                                                    </div>

                                                </form>
                                            </div>
                                        </div>
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
                                                                <th>Category</th>
                                                                <th>Name</th>
                                                                <th>Hotel Code</th>
                                                                <th>Location</th>
                                                                <th>Email</th>
                                                                <th>Contact No</th>
                                                                <th>Bank Details</th>
                                                                <th>DL Expire Date</th>
                                                                <th>DL Image</th>
                                                                <th>GL Expire Date</th>
                                                                <th>GL Image</th>
                                                                <th>Available</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($suppliers as $supplier) {

                                                                // get supplier category
                                                                $supplier_category = '';
                                                                if ($supplier['sup_cat'] == 1) $supplier_category = 'Hotel';
                                                                else if ($supplier['sup_cat'] == 2) $supplier_category = 'Transport Agency';
                                                                else if ($supplier['sup_cat'] == 3) $supplier_category = 'Utility Bills';
                                                                else if ($supplier['sup_cat'] == 99) $supplier_category = 'All';

                                                                if ($supplier['Mobile'] == 0) {
                                                                    $supplier['Mobile'] = '';
                                                                } else {
                                                                    $supplier['Mobile'] = $supplier['Mobile'];
                                                                }
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 5%;"><?php echo $x; ?></td>
                                                                    <td style="width: 20%;"><?php echo $supplier_category; ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $supplier['Name']; ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width: 15%;"><?php echo $supplier['hotel_code']; ?></td>
                                                                    <td style="width: 15%;"><?php echo $supplier['Address']; ?></td>
                                                                    <td style="width: 15%;"><?php echo $supplier['email']; ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><small class="text-secondary"><?php echo $supplier['TP']; ?></small><br>
                                                                                <small class="text-secondary"><?php echo $supplier['Mobile']; ?></small></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong>
                                                                                <small class="text-secondary"><?php echo $supplier['bank_name']; ?></small><br>
                                                                                <small class="text-secondary"><?php echo $supplier['branch_name']; ?></small><br>
                                                                                <small class="text-secondary"><?php /* tf-stage5: last four digits unless this sign-in holds bank details (full numbers live in the Supplier Bank Account Registry) */ echo \App\Libraries\TourPayments\Perms::bankDetails() ? $supplier['sup_bank_acc'] : \App\Libraries\TourPayments\BankAccounts::mask((string) $supplier['sup_bank_acc'], 4); ?></small>
                                                                            </strong>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width: 15%;"><?php echo $supplier['dled'] ?? ""; ?></td>
                                                                    <td>
                                                                        <?php if ($supplier['dlimg'] == '') { ?>
                                                                            <img src="<?php echo base_url("uploads/places/no-image.png"); ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } else { ?>
                                                                            <img src="<?php echo base_url("uploads/driver/license/" . $supplier['dlimg']); ?>" alt="<?php echo $supplier['dlimg']; ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td style="width: 15%;"><?php echo $supplier['gled'] ?? ""; ?></td>
                                                                    <td>
                                                                        <?php if ($supplier['glimg'] == '') { ?>
                                                                            <img src="<?php echo base_url("uploads/places/no-image.png"); ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } else { ?>
                                                                            <img src="<?php echo base_url("uploads/driver/license/guide/" . $supplier['glimg']); ?>" alt="<?php echo $supplier['glimg']; ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input toggle-availability-btn" type="checkbox" role="switch" data-supplier-id="<?php echo $supplier['ID']; ?>" <?php echo (($supplier['is_avalible'] ?? 1) == 1) ? 'checked' : ''; ?>>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="<?php echo base_url('master_files/supplier?id=' . $supplier['ID']) ?>" class="dropdown-item edit-menu-item-btn" data-menu-item-id="<?php echo $supplier['ID']; ?>" data-hotel-id="<?php echo $supplier['Sup_Acc_No']; ?>">Edit</a>
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

        var report_title = 'Supplier Report';

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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 9]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'portrait', // Set orientation to landscape
                    pageSize: 'A4', // Set page size to A4
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 9]
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
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 9]
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
<script>
/* tf-stage3 — a ?q= in the address (from the Master Files search) pre-fills the table search */
(function () {
    var q = new URLSearchParams(location.search).get('q') || '';
    if (!q || !window.jQuery) return;
    var tries = 0, t = setInterval(function () {
        var $t = jQuery('#mainTable');
        if ($t.length && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable($t[0])) { clearInterval(t); $t.DataTable().search(q).draw(); }
        else if (++tries > 50) { clearInterval(t); }
    }, 100);
})();
</script>
</body>




</html>