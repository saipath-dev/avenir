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
                                        <h4 class="p-title">Inquiries</h4>
                                        <a href="<?= base_url('inquiries') ?>" class="btn btn-primary ms-auto">
                                            + New Inquiries
                                        </a>
                                    </div>
                                </div>


                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">
                                                    System Inquiries
                                                    <span class="badge text-bg-light border rounded">
                                                        <?php
                                                        $count = 0;
                                                        foreach ($inquiries as $item) {
                                                            if ($item['is_accepted'] == 0) {
                                                                $count++;
                                                            }
                                                        }
                                                        echo $count;
                                                        ?>
                                                    </span>
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile" type="button" role="tab" aria-controls="pills-profile" aria-selected="false">
                                                    Website Inquiries
                                                    <span class="badge text-bg-light border rounded">
                                                        <?php
                                                        $count = 0;
                                                        foreach ($api_inquiries as $item) {
                                                            if (isset($item['responded']) && $item['responded'] == 0) {
                                                                $count++;
                                                            }
                                                        }
                                                        echo $count;
                                                        ?>
                                                    </span>
                                                </button>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="pills-tabContent">
                                            <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab" tabindex="0">

                                                <div class="card p-4">
                                                    <div class="body">
                                                        <div class="table-responsive">
                                                            <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                                <thead class="thead-dark">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Code</th>
                                                                        <th>Tour Name</th>
                                                                        <th>Agent</th>
                                                                        <th>From - To</th>
                                                                        <th>Room Count</th>
                                                                        <th>Message</th>
                                                                        <th></th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <!-- itertate through $admin array -->
                                                                    <?php
                                                                    $x = 1;
                                                                    foreach ($inquiries as $item) {
                                                                    ?>
                                                                        <tr style="width: 100%;">
                                                                            <td style="width: 5%;"><?php echo $x; ?></td>
                                                                            <td>
                                                                                <?php if (!empty($item['inquiry_code'])) { ?>
                                                                                    <span class="badge bg-primary"><?php echo $item['inquiry_code']; ?></span>
                                                                                <?php } else { ?>
                                                                                    <span class="badge bg-secondary">No Code</span>
                                                                                <?php } ?>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex flex-column">
                                                                                    <strong><?php echo $item['name']; ?></strong>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex flex-column">
                                                                                    <span>
                                                                                        Agent : <strong><?php echo $item['agent']; ?></strong>
                                                                                    </span>
                                                                                    <span>
                                                                                        Phone : <strong><?php echo $item['phone']; ?></strong>
                                                                                    </span>
                                                                                    <span>
                                                                                        Email : <strong><?php echo $item['email']; ?></strong>
                                                                                    </span>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex flex-column">
                                                                                    <span>
                                                                                        From : <strong><?php echo $item['start_date']; ?></strong>
                                                                                    </span>
                                                                                    <span>
                                                                                        To : <strong><?php echo $item['end_date']; ?></strong>
                                                                                    </span>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex flex-column">
                                                                                    <span>
                                                                                        SGL : <strong><?php echo $item['roomsgl']; ?></strong>
                                                                                    </span>
                                                                                    <span>
                                                                                        DBL : <strong><?php echo $item['roomdbl']; ?></strong>
                                                                                    </span>
                                                                                    <span>
                                                                                        TPL : <strong><?php echo $item['roomtpl']; ?></strong>
                                                                                    </span>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <p>
                                                                                    <?php echo $item['message']; ?>
                                                                                </p>
                                                                            </td>
                                                                            <td>
                                                                                <?php if ($item['is_accepted'] == 0) { ?>
                                                                                    <div class="d-flex flex-column">
                                                                                        <button class="btn btn-sm btn-success accept_btn" data-id="<?php echo $item['id']; ?>">Accept</button>
                                                                                        <button class="btn btn-sm btn-danger reject_btn mt-1" data-id="<?php echo $item['id']; ?>">Reject</button>
                                                                                    </div>
                                                                                <?php } else if ($item['is_accepted'] == 1) {
                                                                                    echo '<span class="badge bg-success">Accepted</span>';
                                                                                } else if ($item['is_accepted'] == 2) {
                                                                                    echo '<span class="badge bg-danger">Rejected</span>';
                                                                                } ?>
                                                                            </td>
                                                                            <td style="width: 5%;">
                                                                                <div class="dropup">
                                                                                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                        <i class="bi bi-three-dots-vertical"></i>
                                                                                    </button>
                                                                                    <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                        <li>
                                                                                            <a class="dropdown-item text-danger delete-item-btn" href="#" data-item-id="<?php echo $item['id']; ?>">Delete</a>
                                                                                        </li>
                                                                                        <?php if ($item['is_accepted'] == 1) {
                                                                                        ?>
                                                                                            <li>
                                                                                                <button class="dropdown-item reject_btn" data-id="<?php echo $item['id']; ?>">Reject</button>
                                                                                            </li>
                                                                                        <?php
                                                                                        } ?>
                                                                                        <?php
                                                                                        if ($item['is_accepted'] == 2) {
                                                                                        ?>
                                                                                            <button class="dropdown-item accept_btn" data-id="<?php echo $item['id']; ?>">Accept</button>
                                                                                        <?php
                                                                                        } ?>
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
                                            <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab" tabindex="0">

                                                <div class="card p-4">
                                                    <div class="body">
                                                        <div class="table-responsive">
                                                            <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                                <thead class="thead-dark">
                                                                    <tr>
                                                                        <th>#</th>
                                                                        <th>Code</th>
                                                                        <th>Name</th>
                                                                        <th>Inquiry</th>
                                                                        <th>Place</th>
                                                                        <th>Inquiry Time</th>
                                                                        <th>Status</th>
                                                                        <th>Action</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <!-- itertate through $admin array -->
                                                                    <?php
                                                                    $x = 1;
                                                                    foreach ($api_inquiries as $item) {
                                                                    ?>
                                                                        <tr style="width: 100%;">
                                                                            <td style="width: 5%;"><?php echo $x; ?></td>
                                                                            <td>
                                                                                <?php if (!empty($item['inquiry_code'])) { ?>
                                                                                    <span class="badge bg-success"><?php echo $item['inquiry_code']; ?></span>
                                                                                <?php } else { ?>
                                                                                    <span class="badge bg-secondary">No Code</span>
                                                                                <?php } ?>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex flex-column">
                                                                                    <span>
                                                                                        Name : <strong><?php echo $item['Name'] ?? 'N/A'; ?></strong>
                                                                                    </span>
                                                                                    <span>
                                                                                        Address : <strong><?php echo $item['Address'] ?? 'N/A'; ?></strong>
                                                                                    </span>
                                                                                    <span>
                                                                                        Phone : <strong><?php echo $item['Mobilenum'] ?? 'N/A'; ?></strong>
                                                                                    </span>
                                                                                    <span>
                                                                                        Email : <strong><?php echo $item['Email'] ?? 'N/A'; ?></strong>
                                                                                    </span>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <p class="text-wrap">
                                                                                    <?php echo $item['Inquiry'] ?? 'N/A'; ?>
                                                                                </p>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $item['Place'] ?? 'N/A'; ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $item['Rooms'] ?? 'N/A'; ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $item['Date'] ?? 'N/A'; ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo !empty($item['created_at']) ? date('Y-m-d h:i A', strtotime($item['created_at'])) : 'N/A'; ?>
                                                                            </td>
                                                                            <td>
                                                                                <?php 
                                                                                if (isset($item['responded'])) {
                                                                                    if ($item['responded'] == 0) {
                                                                                        echo '<span class="badge bg-warning">Pending</span>';
                                                                                    } else {
                                                                                        echo '<span class="badge bg-success">Responded</span>';
                                                                                    }
                                                                                } else {
                                                                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                                                                }
                                                                                ?>
                                                                            </td>
                                                                            <td>

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

    <script>
        // on delete item
        $('.delete-item-btn').click(function() {
            var item_id = $(this).data('item-id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this inquiry!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('inquiries/delete') ?>/' + item_id,
                        type: 'DELETE',
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Inquiry has been deleted successfully',
                            }).then((result) => {
                                location.reload();
                            });
                        },
                        error: function(response) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong',
                            });
                        }
                    });
                }
            });
        });

        // on accept inquiry
        $('.accept_btn').click(function() {
            var item_id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to accept this inquiry!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, accept it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('inquiries/accept') ?>/' + item_id,
                        type: 'GET',
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Inquiry has been accepted successfully',
                            }).then((result) => {
                                location.reload();
                            });
                        },
                        error: function(response) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong',
                            });
                        }
                    });
                }
            });
        });

        // on reject inquiry
        $('.reject_btn').click(function() {
            var item_id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You want to reject this inquiry!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, reject it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= base_url('inquiries/reject') ?>/' + item_id,
                        type: 'GET',
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Inquiry has been rejected successfully',
                            }).then((result) => {
                                location.reload();
                            });
                        },
                        error: function(response) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Something went wrong',
                            });
                        }
                    });
                }
            });
        });
    </script>
</body>




</html>