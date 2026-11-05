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
                                        <h4 class="p-title">Group Profiles</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="d-flex border-bottom pb-3">
                                                <div class="me-auto">
                                                    <div class="header">
                                                        <span class="badge bg-dark fs-6 mt-2"><?php echo count($profiles) . " items found." ?></span>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end align-items-center me-3">
                                                    <button data-bs-toggle="offcanvas" data-bs-target="#profileOffcanvas" aria-controls="profileOffcanvas" class="btn btn-primary px-4" style="height: fit-content;">+ New Profile</button>
                                                </div>
                                            </div>
                                            <div class="body">
                                                <div class="table-responsive mt-4">
                                                    <table class="table table-hover js-basic-example dataTable table-custom">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Profile Name</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($profiles as $profile) {
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 10%;"><?php echo $x; ?></td>
                                                                    <td><?php echo $profile['type']; ?></td>
                                                                    <td style="width: 10%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="#" class="dropdown-item edit-profile-btn" data-profile-id="<?php echo $profile['id']; ?>">Edit</a>
                                                                                </li>
                                                                                <?php
                                                                                // check if the user is an admin, then show delete btn
                                                                                if ($user_role == 1) {
                                                                                ?>
                                                                                    <li><a class="dropdown-item text-danger delete-profile-btn" href="#" data-profile-id="<?php echo $profile['id']; ?>">Delete</a></li>
                                                                                <?php } ?>
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
        <!-- Profile Create offcanvas -->
        <div class="offcanvas offcanvas-end" id="profileOffcanvas" aria-labelledby="profileOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="profileOffcanvasLabel">Create New Profile</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="profile_insert_form">
                            <div class="row clearfix">
                                <div class="header" style="margin-top: -10px;">
                                    <h2 class="text-primary">Enter Profile Details</h2>
                                </div>
                                <div class="">
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Profile Name<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="profile_name" id="profile_name" class="form-control" placeholder="" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix">
                                        <div class="col-sm-12">
                                            <button type="submit" class="btn btn-primary px-5 my-3">Create</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <!-- Profile Edit offcanvas -->
        <div class="offcanvas offcanvas-end" id="profileEditOffcanvas" aria-labelledby="profileEditOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="profileEditOffcanvasLabel">Update Profile Name</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="profile_update_form">
                            <div class="row clearfix">
                                <div class="">
                                    <!-- profile id -->
                                    <input type="hidden" name="profile_id_edit" id="profile_id_edit" class="form-control" required>
                                    <!-- profile id end -->
                                    <!-- old profile name -->
                                    <input type="hidden" name="profile_name_old_edit" id="profile_name_old_edit" class="form-control" required>
                                    <!-- old profile name end -->
                                    <div class="row clearfix">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Profile Name<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="profile_name_edit" id="profile_name_edit" class="form-control" placeholder="" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix">
                                        <div class="col-sm-12">
                                            <button type="submit" class="btn btn-primary px-5 my-3">Update</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
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