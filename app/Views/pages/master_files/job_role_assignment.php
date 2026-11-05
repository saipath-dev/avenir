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
                                        <h4 class="p-title">Assign Job Roles</h4>
                                    </div>
                                    <div class="col-md-2 d-flex justify-content-end">
                                        <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#assignmentCreateOffcanvas">
                                            + New Assignment
                                        </button>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>User</th>
                                                                <th>Job Role</th>
                                                                <th>Start Date</th>
                                                                <th>End Date</th>
                                                                <th>Status</th>
                                                                <th>Assigned By</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $x = 1;
                                                            foreach ($assignments as $assignment) {
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 3%;"><?php echo $x; ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $assignment['user_name']; ?></strong>
                                                                            <small class="text-muted"><?php echo $assignment['user']; ?></small>
                                                                        </div>
                                                                    </td>
                                                                    <td><?php echo $assignment['job_role_name']; ?></td>
                                                                    <td><?php echo date('d M Y', strtotime($assignment['start_date'])); ?></td>
                                                                    <td><?php echo date('d M Y', strtotime($assignment['end_date'])); ?></td>
                                                                    <td>
                                                                        <span class="badge bg-<?php echo $assignment['status_class']; ?>">
                                                                            <?php echo $assignment['status']; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td><?php echo $assignment['assigned_by'] ?? '-'; ?></td>
                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="#" class="dropdown-item edit-assignment-btn" data-item-id="<?php echo $assignment['id']; ?>">Edit</a>
                                                                                </li>
                                                                                <li>
                                                                                    <a class="dropdown-item text-danger delete-assignment-btn" href="#" data-item-id="<?php echo $assignment['id']; ?>">Delete</a>
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

                <!-- Create Assignment Offcanvas -->
                <div class="offcanvas offcanvas-end" id="assignmentCreateOffcanvas" aria-labelledby="assignmentCreateOffcanvasLabel">
                    <div class="offcanvas-header">
                        <h5 id="assignmentCreateOffcanvasLabel">Assign Job Role</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <form id="assignment_create_form">
                            <div class="row clearfix mb-3">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">User <font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="user_id" id="user_id" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <option value="">Select User...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix mb-3">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">Job Role(s) <font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="job_role_id[]" id="job_role_id" class="js-example-basic-single form-control" style="width: 100%;" multiple="multiple" required>
                                            <?php
                                            // Load job roles for dropdown
                                            $jobRoleModel = new \App\Models\JobRoleModel();
                                            $jobRoles = $jobRoleModel->where('del', 0)->findAll();
                                            foreach ($jobRoles as $role) {
                                                echo '<option value="' . $role['id'] . '">' . $role['job_role'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix mb-3">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">Start Date <font color="#FF0000"><strong>*</strong></font></label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix mb-3">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">End Date <font color="#FF0000"><strong>*</strong></font></label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix">
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary px-5 my-3">Assign</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Assignment Offcanvas -->
                <div class="offcanvas offcanvas-end" id="assignmentEditOffcanvas" aria-labelledby="assignmentEditOffcanvasLabel">
                    <div class="offcanvas-header">
                        <h5 id="assignmentEditOffcanvasLabel">Update Job Role Assignment</h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <form id="assignment_update_form">
                            <input type="hidden" name="assignment_id_edit" id="assignment_id_edit" required>

                            <div class="row clearfix mb-3">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">User <font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="user_id_edit" id="user_id_edit" class="js-example-basic-single form-control" style="width: 100%;" required>
                                            <option value="">Select User...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix mb-3">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">Job Role(s) <font color="#FF0000"><strong>*</strong></font></label>
                                        <select name="job_role_id_edit[]" id="job_role_id_edit" class="js-example-basic-single form-control" style="width: 100%;" multiple="multiple" required>
                                            <?php
                                            foreach ($jobRoles as $role) {
                                                echo '<option value="' . $role['id'] . '">' . $role['job_role'] . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix mb-3">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">Start Date <font color="#FF0000"><strong>*</strong></font></label>
                                        <input type="date" name="start_date_edit" id="start_date_edit" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix mb-3">
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">End Date <font color="#FF0000"><strong>*</strong></font></label>
                                        <input type="date" name="end_date_edit" id="end_date_edit" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row clearfix">
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary px-5 my-3">Update</button>
                                </div>
                            </div>
                        </form>
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
            // Initialize Select2
            $('.js-example-basic-single').select2({
                placeholder: "Select",
                allowClear: true,
                dropdownParent: $('.offcanvas.show').length ? $('.offcanvas.show') : $('body')
            });

            // Load users for dropdown
            function loadUsers(selectElementId) {
                $.ajax({
                    url: '<?= base_url('master_files/job_role/get_user_employees') ?>',
                    type: 'GET',
                    dataType: 'json',
                    success: function(users) {
                        var select = $('#' + selectElementId);
                        select.empty();
                        select.append('<option value="">Select User...</option>');
                        users.forEach(function(user) {
                            select.append('<option value="' + user.id + '">' + user.employee_name + '</option>');
                        });
                        select.trigger('change');
                    }
                });
            }

            // Load users when create offcanvas is shown
            $('#assignmentCreateOffcanvas').on('shown.bs.offcanvas', function() {
                loadUsers('user_id');
                // Reinitialize select2 for this offcanvas
                $('#user_id').select2({
                    placeholder: "Select User",
                    allowClear: true,
                    dropdownParent: $('#assignmentCreateOffcanvas')
                });

                $('#job_role_id').select2({
                    placeholder: "Select Job Role(s)",
                    allowClear: true,
                    dropdownParent: $('#assignmentCreateOffcanvas'),
                    closeOnSelect: false  // Keep dropdown open for multiple selections
                });
            });

            // Load users when edit offcanvas is shown
            $('#assignmentEditOffcanvas').on('shown.bs.offcanvas', function() {
                var assignmentId = $(this).data('assignment-id');

                if (!assignmentId) return;

                // First load users
                $.ajax({
                    url: '<?= base_url('master_files/job_role/get_user_employees') ?>',
                    type: 'GET',
                    dataType: 'json',
                    success: function(users) {
                        var select = $('#user_id_edit');
                        select.empty();
                        select.append('<option value="">Select User...</option>');
                        users.forEach(function(user) {
                            select.append('<option value="' + user.id + '">' + user.employee_name + '</option>');
                        });

                        // Reinitialize select2 for user dropdown
                        $('#user_id_edit').select2({
                            placeholder: "Select User",
                            allowClear: true,
                            dropdownParent: $('#assignmentEditOffcanvas')
                        });

                        // Reinitialize select2 for job role dropdown
                        $('#job_role_id_edit').select2({
                            placeholder: "Select Job Role(s)",
                            allowClear: true,
                            dropdownParent: $('#assignmentEditOffcanvas'),
                            closeOnSelect: false  // Keep dropdown open for multiple selections
                        });

                        // Now load the assignment data
                        $.ajax({
                            url: '<?= base_url() ?>master_files/job_role/assignments/show/' + assignmentId,
                            type: 'GET',
                            dataType: 'json',
                            beforeSend: function() {
                                $('#assignmentEditOffcanvas button[type="submit"]').attr('disabled', true).html('Please wait...');
                            },
                            success: function(response) {
                                if (response.status == 200) {
                                    $('#assignment_id_edit').val(response.data.id);
                                    $('#user_id_edit').val(response.data.user_id).trigger('change');
                                    $('#job_role_id_edit').val(response.data.job_role_id).trigger('change');
                                    $('#start_date_edit').val(response.data.start_date);
                                    $('#end_date_edit').val(response.data.end_date);

                                    $('#assignmentEditOffcanvas button[type="submit"]').attr('disabled', false).html('Update');
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Not Found',
                                        text: 'Assignment not found',
                                    });
                                    $('#assignmentEditOffcanvas').offcanvas('hide');
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to load assignment data',
                                });
                                $('#assignmentEditOffcanvas').offcanvas('hide');
                            }
                        });
                    }
                });
            });

            // Submit create form
            $('#assignment_create_form').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: '<?= base_url('master_files/job_role/assignments/create') ?>',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    beforeSend: function() {
                        $('#assignment_create_form button[type="submit"]').attr('disabled', true).html('Please wait...');
                    },
                    success: function(response) {
                        if (response.status == 201) {
                            // All assignments successful
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => location.reload());
                        } else if (response.status == 207) {
                            // Partial success - some roles assigned, some failed
                            Swal.fire({
                                icon: 'warning',
                                title: 'Partial Success',
                                html: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => location.reload());
                        } else if (response.status == 409) {
                            // All assignments failed due to conflicts
                            Swal.fire({
                                icon: 'error',
                                title: 'Assignment Conflict',
                                html: response.message,
                                confirmButtonText: 'OK'
                            });
                            $('#assignment_create_form button[type="submit"]').attr('disabled', false).html('Assign');
                        } else {
                            // Other errors
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                html: response.message,
                            });
                            $('#assignment_create_form button[type="submit"]').attr('disabled', false).html('Assign');
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });
                        $('#assignment_create_form button[type="submit"]').attr('disabled', false).html('Assign');
                    }
                });
            });

            // Edit assignment button click
            $('.edit-assignment-btn').click(function() {
                var id = $(this).attr('data-item-id');

                // Store the assignment ID temporarily
                $('#assignmentEditOffcanvas').data('assignment-id', id);

                // Show the offcanvas (this will trigger loading users)
                $('#assignmentEditOffcanvas').offcanvas('show');
            });

            // Submit update form
            $('#assignment_update_form').submit(function(e) {
                e.preventDefault();
                var id = $('#assignment_id_edit').val();
                $.ajax({
                    url: '<?= base_url() ?>master_files/job_role/assignments/update/' + id,
                    type: 'POST',
                    data: {
                        user_id: $('#user_id_edit').val(),
                        job_role_id: $('#job_role_id_edit').val(),
                        start_date: $('#start_date_edit').val(),
                        end_date: $('#end_date_edit').val()
                    },
                    beforeSend: function() {
                        $('#assignment_update_form button[type="submit"]').attr('disabled', true).html('Please wait...');
                    },
                    success: function(data) {
                        if (data.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => location.reload());
                        } else if (data.status == 409) {
                            // Conflict
                            Swal.fire({
                                icon: 'error',
                                title: 'Assignment Conflict',
                                html: data.message,
                                confirmButtonText: 'OK'
                            });
                            $('#assignment_update_form button[type="submit"]').attr('disabled', false).html('Update');
                        } else {
                            Swal.fire({
                                icon: data.status == 400 ? 'warning' : 'error',
                                title: data.status == 400 ? 'Validation Error' : 'Error',
                                html: data.message,
                            });
                            $('#assignment_update_form button[type="submit"]').attr('disabled', false).html('Update');
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });
                        $('#assignment_update_form button[type="submit"]').attr('disabled', false).html('Update');
                    }
                });
            });

            // Delete assignment button click
            $('.delete-assignment-btn').click(function() {
                var id = $(this).attr('data-item-id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?= base_url() ?>master_files/job_role/assignments/delete',
                            type: 'GET',
                            data: {
                                id: id
                            },
                            dataType: 'json',
                            success: function(data) {
                                if (data.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: data.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message,
                                    });
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>


</html>
