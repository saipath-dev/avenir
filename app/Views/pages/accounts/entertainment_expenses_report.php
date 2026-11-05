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
                                        <h4 class="p-title">Entertainment Invoice Report</h4>
                                    </div>
                                </div>
                                <?php if (isset($entertainment) && !empty($entertainment)) {
                                ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>Date</th>
                                                                    <!-- <th>Ref No</th> -->
                                                                    <th>Description</th>
                                                                    <th>Amount</th>
                                                                    <th>Paid By</th>
                                                                    <th>Document</th>
                                                                    <th>User</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($entertainment as $item): ?>
                                                                    <tr>
                                                                        <td><?= date('Y-m-d', strtotime($item['Date'])) ?></td>
                                                                        <!-- <td><?//= $item['ref_no'] ?></td> -->
                                                                        <td><?= $item['Description'] ?></td>
                                                                        <td><?= number_format($item['Amount'], 2) ?></td>
                                                                        <td><?= $item['card_name'] ?></td>
                                                                        <td>
                                                                            <?php if (!empty($item['invoice_img_path'])): ?>
                                                                                <a href="<?= base_url($item['invoice_img_path']) ?>" target="_blank" class="btn btn-sm btn-info">
                                                                                    <i class="ri-file-text-line"></i> View
                                                                                </a>
                                                                            <?php else: ?>
                                                                                <span class="text-muted">N/A</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td><?= $item['username'] ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else {
                                ?>
                                    <?php if ($is_search ?? true) { ?><?php /* tf-e8-access-r2: this report has no search form — an empty list now shows "No data found" instead of an error */ ?>
                                        <div class="row clearfix mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-warning" role="alert">
                                                    <strong>Warning!</strong> No data found.
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
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

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        // delete transaction
        $('.btn-danger').on('click', function() {

            var id = $(this).val();

            tfConfirmReason({ // tf-e8-access-r1: reason required
                title: 'Are you sure?',
                text: "You want to delete this Records!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST", // tf-e8-access-r1: was GET
                        url: "<?php echo base_url(); ?>accounts/utility_bill/delete",
                        data: {
                            id: id,
                            reason: result.value // tf-e8-access-r1
                        },
                        dataType: "json",
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
                                    title: 'Oops...',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {

                        }
                    });
                }
            });
        });

        // Check for flash messages or error parameters
        $(document).ready(function() {
            // Check if there's an error in query parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('error')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Input Error',
                    text: decodeURIComponent(urlParams.get('error'))
                });
            }
            
            // Check if there's a flashdata error from the server
            <?php if(session()->getFlashdata('error')): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Input Error',
                    text: '<?= session()->getFlashdata('error') ?>'
                });
            <?php endif; ?>
        });
    </script>

</body>

</html>