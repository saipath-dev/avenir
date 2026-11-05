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
                                        <h4 class="p-title">Job Roles</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-4">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="job_role">Job Role <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="job_role" name="job_role" placeholder="Enter Job Role" required />
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
                                                                <th>Job Role</th>
                                                                <th>Created By</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $x = 1;
                                                            foreach ($job_roles as $role) {
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 5%;"><?php echo $x; ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $role['job_role']; ?></strong>
                                                                        </div>
                                                                    </td>
                                                                    <td><?php echo $role['created_by'] ?? '-'; ?></td>
                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="#" class="dropdown-item edit-item-btn" data-item-id="<?php echo $role['id']; ?>">Edit</a>
                                                                                </li>
                                                                                <li>
                                                                                    <a class="dropdown-item text-danger delete-item-btn" href="#" data-item-id="<?php echo $role['id']; ?>">Delete</a>
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

                <div class="offcanvas offcanvas-end" id="itemEditOffcanvas" aria-labelledby="itemEditOffcanvasLabel">
                    <div class="offcanvas-header">
                        <h5 id="itemEditOffcanvasLabel">Update Job Role</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <div class="">

                            <div class="">

                                <form id="job_role_update_form">
                                    <div class="row clearfix">
                                        <div class="">
                                            <!-- job role id -->
                                            <input type="hidden" name="job_role_id_edit" id="job_role_id_edit" class="form-control" required>
                                            <!-- job role id end -->
                                            <div class="row clearfix mb-3">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label class="form-label">Job Role<font color="#FF0000"><strong>*</strong></font></label>
                                                        <input type="text" name="job_role_edit" id="job_role_edit" class="form-control" placeholder="" required>
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
        $(document).ready(function() {
            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: '<?= base_url('master_files/job_role/create') ?>',
                    type: 'POST',
                    data: $(this).serialize(),
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
            // get job role id
            var id = $(this).attr('data-item-id');
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>master_files/job_role/show/' + id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    $('#itemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {

                        $('#job_role_id_edit').val(response.data.id);
                        $('#job_role_edit').val(response.data.job_role);

                        // open offcanvas
                        $('#itemEditOffcanvas').offcanvas('show');
                        $('#itemEditOffcanvas button[type="submit"]').attr('disabled', false);
                        $('#itemEditOffcanvas button[type="submit"]').html('Update');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Not Found',
                            text: response.message,
                        });
                        $('#itemEditOffcanvas button[type="submit"]').attr('disabled', true);
                        $('#itemEditOffcanvas button[type="submit"]').html('Update');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#itemEditOffcanvas button[type="submit"]').attr('disabled', false);
                    $('#itemEditOffcanvas button[type="submit"]').html('Update');
                }
            });
        });

        // submit job_role_update_form
        $('#job_role_update_form').submit(function(e) {
            e.preventDefault();
            // get job role id
            var id = $('#job_role_id_edit').val();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>master_files/job_role/update/' + id,
                type: 'POST',
                data: {
                    job_role: $('#job_role_edit').val()
                },
                cache: false,
                processData: true,
                beforeSend: function() {
                    $('#job_role_update_form button[type="submit"]').attr('disabled', true);
                    $('#job_role_update_form button[type="submit"]').html('Please wait...');
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
                            window.location.href = "<?php echo base_url(); ?>master_files/job_role";
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
                            title: 'Duplicate Job Role',
                            text: data.message,
                        });
                    } else if (data.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        });
                    }
                    $('#job_role_update_form button[type="submit"]').attr('disabled', false);
                    $('#job_role_update_form button[type="submit"]').html('Update');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#job_role_update_form button[type="submit"]').attr('disabled', false);
                    $('#job_role_update_form button[type="submit"]').html('Update');
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
                    // get job role id
                    var id = $(this).attr('data-item-id');
                    // ajax call
                    $.ajax({
                        url: '<?php echo base_url(); ?>master_files/job_role/delete',
                        type: 'GET',
                        data: {
                            id: id
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $('.delete-item-btn').attr('disabled', true);
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
                                    window.location.href = "<?php echo base_url(); ?>master_files/job_role";
                                });
                            } else if (data.status == 400) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Cannot Delete',
                                    text: data.message,
                                });
                            } else if (data.status == 500) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: data.message,
                                });
                            }
                            $('.delete-item-btn').attr('disabled', false);
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                            $('.delete-item-btn').attr('disabled', false);
                        }
                    });
                }
            })

        });
    </script>
</body>


</html>
