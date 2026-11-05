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
                                        <h4 class="p-title">Staff Directory</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th style="width: 5%;">#</th>
                                                                <th>Employee</th>
                                                                <th style="width: 15%;">Contact No</th>
                                                                <th style="width: 15%;">Email</th>
                                                                <th style="width: 5%;">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $i = 1;
                                                            foreach ($members as $row) {
                                                            ?>
                                                                <tr>
                                                                    <td><?= $i++; ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <span><strong><?= $row['Name']; ?></strong></span>
                                                                            <code><strong><?= $row['empno']; ?></strong></code>
                                                                        </div>
                                                                    </td>
                                                                    <td><?= $row['Tp']; ?></td>
                                                                    <td><?= $row['email']; ?></td>
                                                                    <td>
                                                                        <a href="<?= base_url('staff/registration?id=' . $row['ID']); ?>" class="btn btn-light btn-sm"><i class="bi bi-pen-fill"></i></a>
                                                                    </td>
                                                                </tr>
                                                            <?php
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

    <script>
        // submit profile_create_form
        $('#profile_insert_form').submit(function(e) {
            e.preventDefault();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/group_profile/create',
                type: 'POST',
                data: new FormData(this),
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#profile_insert_form button[type="submit"]').attr('disabled', true);
                    $('#profile_insert_form button[type="submit"]').html('Please wait...');
                },
                success: function(data) {
                    if (data.status == 201) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(function() {
                            window.location.href = "<?php echo base_url(); ?>settings/group_profile";
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
                    $('#profile_insert_form button[type="submit"]').attr('disabled', false);
                    $('#profile_insert_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#profile_insert_form button[type="submit"]').attr('disabled', false);
                    $('#profile_insert_form button[type="submit"]').html('Create');
                }
            });
        });

        // .edit-profile-btn click
        $('.edit-profile-btn').click(function() {
            // get profile id
            var profile_id = $(this).attr('data-profile-id');
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/group_profile/show/' + profile_id,
                type: 'GET',
                data: {},
                dataType: 'json',
                beforeSend: function() {
                    $('#profileEditOffcanvas button[type="submit"]').attr('disabled', true);
                    $('#profileEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {
                        // set profile id
                        $('#profile_id_edit').val(response.data.profile.id);
                        // set profile name
                        $('#profile_name_edit').val(response.data.profile.type);
                        $('#profile_name_old_edit').val(response.data.profile.type);
                        // open offcanvas
                        $('#profileEditOffcanvas').offcanvas('show');
                        $('#profileEditOffcanvas button[type="submit"]').attr('disabled', false);
                        $('#profileEditOffcanvas button[type="submit"]').html('Update');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Not Found',
                            text: response.message,
                        });
                        $('#profileEditOffcanvas button[type="submit"]').attr('disabled', true);
                        $('#profileEditOffcanvas button[type="submit"]').html('Update');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#profileEditOffcanvas button[type="submit"]').attr('disabled', false);
                    $('#profileEditOffcanvas button[type="submit"]').html('Update');
                }
            });
        });

        // submit profile_update_form
        $('#profile_update_form').submit(function(e) {
            e.preventDefault();
            // get profile id
            var profile_id = $('#profile_id_edit').val();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/group_profile/update/' + profile_id,
                type: 'PUT',
                data: $(this).serialize(),
                cache: false,
                processData: true,
                beforeSend: function() {
                    $('#profile_update_form button[type="submit"]').attr('disabled', true);
                    $('#profile_update_form button[type="submit"]').html('Please wait...');
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
                            window.location.href = "<?php echo base_url(); ?>settings/group_profile";
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
                    $('#profile_update_form button[type="submit"]').attr('disabled', false);
                    $('#profile_update_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#profile_update_form button[type="submit"]').attr('disabled', false);
                    $('#profile_update_form button[type="submit"]').html('Create');
                }
            });
        });

        // delete-profile-btn click
        $('.delete-profile-btn').click(function() {
            Swal.fire({
                title: 'Are you sure ?',
                html: "<label class='data-text'>Users who registered under this profile won't be able to logged in to the system once you delete this! Please note that this action cannot be undone, so proceed with caution.</label>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // get profile id
                    var profile_id = $(this).attr('data-profile-id');
                    // ajax call
                    $.ajax({
                        url: '<?php echo base_url(); ?>settings/group_profile/delete/' + profile_id,
                        type: 'DELETE',
                        data: {},
                        dataType: 'json',
                        beforeSend: function() {
                            $('.delete-profile-btn').attr('disabled', true);
                            $('.delete-profile-btn').html('<i class="fa fa-spinner fa-spin"></i>');
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
                                    window.location.href = "<?php echo base_url(); ?>settings/group_profile";
                                });
                            } else if (data.status == 500) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: data.message,
                                });
                            }
                            $('.delete-profile-btn').attr('disabled', false);
                            $('.delete-profile-btn').html('<i class="fa fa-trash"></i>');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                            $('.delete-profile-btn').attr('disabled', false);
                            $('.delete-profile-btn').html('<i class="fa fa-trash"></i>');
                        }
                    });
                }
            })

        });
    </script>
</body>




</html>