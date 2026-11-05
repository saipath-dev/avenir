<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>
        <style>
            /* Custom class for document thumbnails - easily adjustable */
            .doc-thumbnail {
                width: 100%;
                max-width: 180px;
                height: 180px;
                object-fit: cover;
                margin: 0 auto;
                display: block;
                /* border: 1px solid #e9e9e9; */
                border-radius: 2px;
            }

            /* Container for thumbnails */
            .doc-preview-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-bottom: 10px;
            }

            .doc-card {
                transition: all 0.3s ease;
                margin-bottom: 15px;
            }

            .doc-card:hover {
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }

            /* Button group styling */
            .doc-actions {
                display: flex;
                justify-content: center;
                gap: 5px;
                flex-wrap: wrap;
                margin-top: 10px;
            }
        </style>
        <?php
        // $reservation = $confirm = $payment = '';
        // $reservation_body = $confirm_body = $payment_body = '';
        // if ($type == 1) {
        //     $reservation = 'active';
        //     $reservation_body = 'show active';
        // } else if ($type == 2) {
        //     $confirm = 'active';
        //     $confirm_body = 'show active';
        // } else {
        //     $payment = 'active';
        //     $payment_body = 'show active';
        // }

        ?>

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
                                        <h4 class="p-title">Documentation Review</h4>
                                    </div>
                                </div>


                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="col-md-12">
                                            <div class="card p-3">
                                                <div class="body">
                                                    <div class="row">
                                                        <?php
                                                        $consignee = [];
                                                        foreach ($tour_main_details as $detail) {
                                                            $consignee = $detail;
                                                        ?>

                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Tour Name</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_consignee"><?php echo $detail['consignee'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Agent</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_agent_name"><?php echo $detail['agent_name'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Passengers</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_passenger_count"><?php echo $detail['passenger_count'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Start Date</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_start_date"><?php echo $detail['start_date'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">End Date</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_end_date"><?php echo $detail['end_date'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-muted">Manual ID</span>
                                                                    <h6 class="fw-bold"
                                                                        id="t_manual_id"><?php echo $detail['manual_id'] ?></h6>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" name="finalize_txt" id="finalize_txt"
                                                                value="<?php echo $detail['finalize_txt'] ?>">
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-xxl-12">
                                        <!-- <h5 class="mb-3">Custom Hover Tabs</h5> -->
                                        <div class="card">
                                            <!-- <div class="card-body">
                                        <p class="text-muted"></p>
                                    </div> -->
                                            <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $tour_id ?>">
                                            <div class="border">
                                                <ul class="nav nav-pills custom-hover-nav-tabs">
                                                    <li class="nav-item">
                                                        <a href="#hotel-booking" data-bs-toggle="tab" aria-expanded="false"
                                                            class="nav-link active">
                                                            <i class="bi bi-file-earmark-image nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Log Sheet</h5>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="#booking-confirm" data-bs-toggle="tab" aria-expanded="true"
                                                            class="nav-link">
                                                            <i class="bi bi-image-fill nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Tour Settlement</h5>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="#hotel-payment" data-bs-toggle="tab" aria-expanded="false"
                                                            class="nav-link">
                                                            <i class="bi bi-images nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Multiple option ....</h5>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="card-body">
                                                <div class="tab-content text-muted">
                                                    <!-- Log Sheet Tab -->
                                                    <div class="tab-pane show active" id="hotel-booking">
                                                        <div class="card shadow-sm">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0 fw-bold">Log Sheet</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <!-- Image display section -->
                                                                <div class="row g-3">
                                                                    <?php foreach ($log_sheet as $log):
                                                                        $id = $log['id']; ?>
                                                                        <div class="col-md-4 col-lg-3">
                                                                            <div class="card border doc-card">
                                                                                <div class="card-body p-2">
                                                                                    <div class="doc-preview-container">
                                                                                        <img src="" alt="log sheet" data-id="<?= $id ?>" data-type="1" class="doc-thumbnail base64-img">
                                                                                    </div>
                                                                                    <div class="doc-actions">
                                                                                        <?php if ($log['status'] == 0) { ?>
                                                                                            <button type="button" class="btn btn-success btn-sm" onclick="acceptimage(<?= $id ?>,1)">
                                                                                                <i class="bi bi-check-circle me-1"></i>Accept
                                                                                            </button>
                                                                                            <button type="button" class="btn btn-danger btn-sm" onclick="rejectimage(<?= $id ?>,1)">
                                                                                                <i class="bi bi-x-circle me-1"></i>Reject
                                                                                            </button>
                                                                                        <?php }
                                                                                        if ($log['status'] == 1) { ?>
                                                                                            <button type="button" class="btn btn-success btn-sm" disabled>
                                                                                                <i class="bi bi-check-circle me-1"></i>Accept
                                                                                            </button>
                                                                                        <?php }
                                                                                        if ($log['status'] == 2) { ?>
                                                                                            <button type="button" class="btn btn-danger btn-sm" disabled>
                                                                                                <i class="bi bi-x-circle me-1"></i>Reject
                                                                                            </button>
                                                                                        <?php } ?>
                                                                                        <button type="button" class="btn btn-primary btn-sm" onclick="viewimage(<?= $id ?>, 1)">
                                                                                            <i class="bi bi-eye me-1"></i>View
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Tour Settlement Tab -->
                                                    <div class="tab-pane" id="booking-confirm">
                                                        <div class="card shadow-sm">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0 fw-bold">Tour Settlement</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <!-- Image display section -->
                                                                <div class="row g-3">
                                                                    <?php foreach ($tour_settlement as $settlement):
                                                                        $id = $settlement['id']; ?>
                                                                        <div class="col-md-4 col-lg-3">
                                                                            <div class="card border doc-card">
                                                                                <div class="card-body p-2">
                                                                                    <div class="doc-preview-container">
                                                                                        <img src="" alt="tour settlement" data-id="<?= $id ?>" data-type="2" class="doc-thumbnail base64-img">
                                                                                    </div>
                                                                                    <div class="doc-actions">
                                                                                        <?php if ($settlement['status'] == 0) { ?>
                                                                                            <button type="button" class="btn btn-success btn-sm" onclick="acceptimage(<?= $id ?>,2)">
                                                                                                <i class="bi bi-check-circle me-1"></i>Accept
                                                                                            </button>
                                                                                            <button type="button" class="btn btn-danger btn-sm" onclick="rejectimage(<?= $id ?>,2)">
                                                                                                <i class="bi bi-x-circle me-1"></i>Reject
                                                                                            </button>
                                                                                        <?php }
                                                                                        if ($settlement['status'] == 1) { ?>
                                                                                            <button type="button" class="btn btn-success btn-sm" disabled>
                                                                                                <i class="bi bi-check-circle me-1"></i>Accept
                                                                                            </button>
                                                                                        <?php }
                                                                                        if ($settlement['status'] == 2) { ?>
                                                                                            <button type="button" class="btn btn-danger btn-sm" disabled>
                                                                                                <i class="bi bi-x-circle me-1"></i>Reject
                                                                                            </button>
                                                                                        <?php } ?>
                                                                                        <button type="button" class="btn btn-primary btn-sm" onclick="viewimage(<?= $id ?>, 2)">
                                                                                            <i class="bi bi-eye me-1"></i>View
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Multiple Options Tab -->
                                                    <div class="tab-pane" id="hotel-payment">
                                                        <div class="card shadow-sm">
                                                            <div class="card-header bg-light">
                                                                <h6 class="mb-0 fw-bold">Multiple Options for Tour Invoice</h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <!-- Image display section -->
                                                                <div class="row g-3">
                                                                    <?php foreach ($multiple_option as $option):
                                                                        $id = $option['id']; ?>
                                                                        <div class="col-md-4 col-lg-3">
                                                                            <div class="card border doc-card">
                                                                                <div class="card-body p-2">
                                                                                    <div class="doc-preview-container">
                                                                                        <img src="" alt="multiple option" data-id="<?= $id ?>" data-type="3" class="doc-thumbnail base64-img">
                                                                                    </div>
                                                                                    <div class="doc-actions">
                                                                                    <?php if ($option['status'] == 0) { ?>
                                                                                        <button type="button" class="btn btn-success btn-sm" onclick="acceptimage(<?= $id ?>,3)">
                                                                                            <i class="bi bi-check-circle me-1"></i>Accept
                                                                                        </button>
                                                                                        <button type="button" class="btn btn-danger btn-sm" onclick="rejectimage(<?= $id ?>,3)">
                                                                                            <i class="bi bi-x-circle me-1"></i>Reject
                                                                                        </button>
                                                                                    <?php }
                                                                                    if ($option['status'] == 1) { ?>
                                                                                        <button type="button" class="btn btn-success btn-sm" disabled>
                                                                                            <i class="bi bi-check-circle me-1"></i>Accept
                                                                                        </button>
                                                                                    <?php }
                                                                                    if ($option['status'] == 2) { ?>
                                                                                        <button type="button" class="btn btn-danger btn-sm" disabled>
                                                                                        <i class="bi bi-x-circle me-1"></i>Reject
                                                                                    </button>
                                                                                <?php } ?>
                                                                                        <button type="button" class="btn btn-primary btn-sm" onclick="viewimage(<?= $id ?>, 3)">
                                                                                            <i class="bi bi-eye me-1"></i>View
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div><!-- end card-body -->
                                        </div>
                                    </div>
                                    <!--end col-->

                                    <!--end col-->

                                </div>


                            </div> <!-- end .h-100-->

                        </div> <!-- end col -->
                    </div>

                </div>
                <!-- container-fluid -->

                <div class="email-detail-content">
                    <div class="p-4 d-flex flex-column h-100">
                        <div id="email-data-panel">

                        </div>
                    </div>
                </div>

            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>

        <!-- END layout-wrapper -->

        <?= view('layouts/inpage_footer') ?>
        <?= view('layouts/theme_settings') ?>
        <?= view('layouts/scripts') ?>


        <script>
            $(document).ready(function() {
                $('.base64-img').each(function() {
                    var img = $(this);
                    var id = img.data('id');
                    var type = img.data('type');
                    $.ajax({
                        url: '<?= base_url('tour/tour_dashboard/documentation/get_image_base64/') ?>' + id + '/' + type,
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                img.attr('src', 'data:image/png;base64,' + response.base64);
                            } else {
                                console.error('Failed to load image: ' + response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Error fetching image: ' + textStatus);
                        }
                    });
                });
            });
        </script>


        <script>
            $('.js-example-basic-single').select2({
                placeholder: " Select",
                allowClear: true
            });

            $('.select_location').select2({
                placeholder: " Select",
                allowClear: true,
                dropdownParent: $("#HotelConfirmedModal")
            });

            // view image
            function viewimage(id, type) {
                $.ajax({
                    url: '<?= base_url('tour/tour_dashboard/documentation/get_image_base64/') ?>' + id + '/' + type,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            Swal.fire({
                                title: 'Image',
                                html: '<img src="data:image/png;base64,' + response.base64 + '" class="img-fluid">',
                                showCloseButton: true,
                                showConfirmButton: false,
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
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });
                    }
                });
            }

            // accept image
            function acceptimage(id, type) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to accept this image!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, accept it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?= base_url('tour/tour_dashboard/documentation/documentation_report/accept_image/') ?>' + id + '/' + type,
                            type: 'GET',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                    }).then((result) => {
                                        location.reload();
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
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });
                            }
                        });
                    }
                });
            }

            // reject image
            function rejectimage(id, type) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to reject this image!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, reject it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?= base_url('tour/tour_dashboard/documentation/documentation_report/reject_image/') ?>' + id + '/' + type,
                            type: 'GET',
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                    }).then((result) => {
                                        location.reload();
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
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });
                            }
                        });
                    }
                });
            }
        </script>

</body>


</html>