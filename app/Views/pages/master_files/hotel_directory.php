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
                                        <h4 class="p-title">Hotel Directory</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="hotelDirectoryTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Hotel Name</th>
                                                                <th>Contact Person</th>
                                                                <th>Contact Number</th>
                                                                <th>Sales Person</th>
                                                                <th>Sales Person Number</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $x = 1;
                                                            foreach ($hotels as $hotel) {
                                                                $contact_number_val = ($hotel['TP'] !== null && $hotel['TP'] !== '0') ? $hotel['TP'] : '';
                                                                $sales_number_val = ($hotel['rep_mobile'] !== null && $hotel['rep_mobile'] !== '0') ? $hotel['rep_mobile'] : '';
                                                                $contact_person_val = $hotel['contact_person'] ?? '';
                                                                $sales_person_val = $hotel['sales_person'] ?? '';
                                                            ?>
                                                                <tr>
                                                                    <td style="width: 5%;"><?php echo $x; ?></td>
                                                                    <td>
                                                                        <strong><?php echo esc($hotel['Name']); ?></strong>
                                                                    </td>
                                                                    <td><?php echo esc($contact_person_val); ?></td>
                                                                    <td><?php echo esc($contact_number_val); ?></td>
                                                                    <td><?php echo esc($sales_person_val); ?></td>
                                                                    <td><?php echo esc($sales_number_val); ?></td>
                                                                    <td style="width: 8%;">
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-primary btn-sm edit-hotel-contact-btn"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editHotelContactModal"
                                                                            data-id="<?php echo $hotel['ID']; ?>"
                                                                            data-name="<?php echo esc($hotel['Name'], 'attr'); ?>"
                                                                            data-contact-person="<?php echo esc($contact_person_val, 'attr'); ?>"
                                                                            data-contact-number="<?php echo esc($contact_number_val, 'attr'); ?>"
                                                                            data-sales-person="<?php echo esc($sales_person_val, 'attr'); ?>"
                                                                            data-sales-number="<?php echo esc($sales_number_val, 'attr'); ?>">
                                                                            <i class="bi bi-pencil-square"></i> Edit
                                                                        </button>
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

    <!-- Edit Hotel Contact Modal -->
    <div class="modal fade" id="editHotelContactModal" tabindex="-1" aria-labelledby="editHotelContactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editHotelContactModalLabel">Edit Hotel Contact</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editHotelContactForm" autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" name="hotel_id" id="edit_hotel_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Hotel</label>
                            <input type="text" class="form-control" id="edit_hotel_name" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_contact_person">Contact Person</label>
                            <input type="text" class="form-control" id="edit_contact_person" name="contact_person" maxlength="100">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_contact_number">Contact Number</label>
                            <input type="text" class="form-control" id="edit_contact_number" name="contact_number" maxlength="50">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_sales_person">Sales Person</label>
                            <input type="text" class="form-control" id="edit_sales_person" name="sales_person" maxlength="100">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="edit_sales_person_number">Sales Person Number</label>
                            <input type="text" class="form-control" id="edit_sales_person_number" name="sales_person_number" maxlength="50">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
        var report_title = 'Hotel Directory';

        $('#hotelDirectoryTable').DataTable({
            "aaSorting": [],
            responsive: true,
            dom: "Bfrtip",
            buttons: [{
                    extend: 'csvHtml5',
                    text: 'CSV',
                    titleAttr: 'CSV',
                    title: report_title,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'portrait',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    titleAttr: 'Print',
                    title: report_title,
                    pageSize: 'A4',
                    orientation: 'portrait',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5]
                    }
                },
            ]
        });

        // Populate modal from row data attributes
        $(document).on('click', '.edit-hotel-contact-btn', function() {
            var $btn = $(this);
            $('#edit_hotel_id').val($btn.data('id'));
            $('#edit_hotel_name').val($btn.data('name'));
            $('#edit_contact_person').val($btn.data('contact-person'));
            $('#edit_contact_number').val($btn.data('contact-number'));
            $('#edit_sales_person').val($btn.data('sales-person'));
            $('#edit_sales_person_number').val($btn.data('sales-number'));
        });

        // Submit update
        $('#editHotelContactForm').on('submit', function(e) {
            e.preventDefault();
            var id = $('#edit_hotel_id').val();
            if (!id) return;

            $.ajax({
                url: "<?php echo base_url(); ?>master_files/supplier/update_hotel_contact/" + id,
                type: "POST",
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                        }).then(function(result) {
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
                        text: (e.responseJSON && e.responseJSON.message) ? e.responseJSON.message : 'An error occurred!',
                    });
                }
            });
        });
    </script>
</body>

</html>
