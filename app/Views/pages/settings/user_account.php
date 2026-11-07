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
                                        <h4 class="p-title">User Accounts</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="d-flex border-bottom pb-3">
                                                <div class="me-auto">
                                                    <div class="header">
                                                        <span class="badge bg-dark fs-6 mt-2"><?php echo count($user_accounts) . " items found." ?></span>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end align-items-center me-3">
                                                    <button data-bs-toggle="offcanvas"
                                                        data-bs-target="#userAccountOffcanvas"
                                                        aria-controls="userAccountOffcanvas" class="btn btn-primary"
                                                        style="height: fit-content;">+ New User Account
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="body">
                                                <div class="table-responsive mt-4">
                                                    <table class="table js-basic-example table-hover dataTable table-custom">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th style="width: 5%;">#</th>
                                                                <th style="width: 20%;">Profile Type</th>
                                                                <th>Employee</th>
                                                                <th style="width: 15%;">Username</th>
                                                                <th style="width: 10%;">Access</th>
                                                                <th style="width: 5%;">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($user_accounts as $account) {
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $x; ?></td>
                                                                    <td>
                                                                        <span class="badge text-bg-success"><?php echo $account['profile_type']; ?></span>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $account['employee_name']; ?></strong>
                                                                            <?php if ($account['group_id'] == 14) {
                                                                                echo '<span class="badge bg-info" style="width: fit-content;">' . $account['agent_name'] . '</span>';
                                                                            } else {
                                                                                echo '<small>' . $account['emp_id'] . '</small>';
                                                                            } ?>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-info"><?php echo $account['user']; ?></td>
                                                                    <td>
                                                                        <?php
                                                                        $checked = '';
                                                                        if ($account['access'] == 1) {
                                                                            $checked = 'checked';
                                                                        }
                                                                        ?>
                                                                        <div class="form-check form-switch">
                                                                            <input
                                                                                data-user-account-id="<?php echo $account['id']; ?>" class="form-check-input access_check" type="checkbox" role="switch"
                                                                                <?= $checked ?>>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle"
                                                                                type="button" data-bs-toggle="dropdown"
                                                                                aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="#"
                                                                                        class="dropdown-item edit-user-account-btn"
                                                                                        data-user-account-id="<?php echo $account['id']; ?>">Edit</a>
                                                                                </li>
                                                                                <?php
                                                                                // check if the user is an admin, then show delete btn
                                                                                //if ($user_role == 1) {
                                                                                ?>
                                                                                <li>
                                                                                    <a class="dropdown-item text-danger delete-user-account-btn"
                                                                                        href="#"
                                                                                        data-user-account-id="<?php echo $account['id']; ?>">Delete</a>
                                                                                </li>
                                                                                <?php //}
                                                                                ?>
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
        <!-- user accounts Create offcanvas -->
        <div class="offcanvas offcanvas-end" id="userAccountOffcanvas" aria-labelledby="userAccountOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="userAccountOffcanvasLabel">Create User Account</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="user_account_insert_form">
                            <div class="row clearfix">
                                <div class="">
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Select Group Profile<font color="#FF0000"><strong>*</strong></font></label>
                                                <select name="group_profile" id="group_profile"
                                                    class="js-example-basic-single form-control" style="width: 100%;"
                                                    required>
                                                    <option value="0">Select a Profile...</option>
                                                    <?php
                                                    foreach ($group_profiles as $profile) {
                                                        echo '<option value="' . $profile['id'] . '">' . $profile['type'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3 agent_div d-none">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Select Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                <select name="agent" id="agent"
                                                    class="js-example-basic-single form-control" style="width: 100%;"
                                                    required>
                                                    <option value="0">Select a Agent...</option>
                                                    <?php
                                                    foreach ($agents as $agent) {
                                                        echo '<option value="' . $agent['Sup_Acc_No'] . '">' . $agent['Name'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Select Employee<font
                                                        color="#FF0000"><strong>*</strong></font></label>
                                                <select name="employee_id" id="employee_id"
                                                    class="js-example-basic-single form-control" style="width: 100%;"
                                                    required>
                                                    <option value="0">Select an Employee...</option>
                                                    <?php
                                                    foreach ($members as $member) {
                                                        echo '<option value="' . $member['emp_num'] . '">' . $member['Name'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Username<font
                                                        color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="username" id="username" class="form-control"
                                                    placeholder="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Password<font
                                                        color="#FF0000"><strong>*</strong></font></label>
                                                <input type="password" name="password" id="password" class="form-control"
                                                    placeholder="" autocomplete="new-password" data-eye>
                                            </div>
                                        </div>
                                    </div>
                                    <!--email data section -->
                                    <h5 class="mt-5 mb-3">Email Data</h5>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Host<font
                                                        color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="host" id="host" class="form-control"
                                                    placeholder="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Email<font
                                                        color="#FF0000"><strong>*</strong></font></label>
                                                <input type="email" name="email" id="email" class="form-control"
                                                    placeholder="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Email's Password<font
                                                        color="#FF0000"><strong>*</strong></font></label>
                                                <input type="password" name="email_password" id="email_password"
                                                    class="form-control" placeholder="" autocomplete="new-password" data-eye>
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

        <!-- menu_item Edit offcanvas -->
        <div class="offcanvas offcanvas-end" id="userAccountEditOffcanvas" aria-labelledby="userAccountEditOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="userAccountEditOffcanvasLabel">Update User Account Details</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="user_account_update_form">
                            <div class="row clearfix">
                                <div class="">
                                    <!-- user_account id -->
                                    <input type="hidden" name="user_account_id_edit" id="user_account_id_edit"
                                        class="form-control" required>
                                    <!-- user_account id end -->
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Select Group Profile<font color="#FF0000"><strong>*</strong></font></label>
                                                <select name="group_profile_edit" id="group_profile_edit"
                                                    class="js-example-basic-single form-control" style="width: 100%;"
                                                    required>
                                                    <option value="0">Select a Profile...</option>
                                                    <?php
                                                    foreach ($group_profiles as $profile) {
                                                        echo '<option value="' . $profile['id'] . '">' . $profile['type'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Employee Name<font
                                                        color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="employee_id_edit_name" id="employee_id_edit_name"
                                                    class="form-control" placeholder="" readonly>
                                                <input type="text" name="employee_id_edit" id="employee_id_edit"
                                                    class="form-control" placeholder="" readonly hidden>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (session()->get('group_id') == 1) { ?>
                                        <div class="row clearfix mb-3">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label class="form-label">Password<font
                                                            color="#FF0000"><strong>*</strong></font></label>
                                                    <input type="password" name="password" id="employee_password_edit"
                                                        class="form-control" placeholder="" autocomplete="new-password" data-eye><!-- tf-eye-r1: was type="text", which showed the new password as it was typed -->
                                                </div>
                                            </div>
                                        </div>

                                        <!--email data section -->
                                        <h5 class="mt-5 mb-3">Email Data</h5>
                                        <div class="row clearfix mb-3">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label class="form-label">Host<font
                                                            color="#FF0000"><strong>*</strong></font></label>
                                                    <input type="text" name="host" id="host_edit" class="form-control"
                                                        placeholder="">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row clearfix mb-3">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label class="form-label">Email<font
                                                            color="#FF0000"><strong>*</strong></font></label>
                                                    <input type="email" name="email" id="email_edit" class="form-control"
                                                        placeholder="">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row clearfix mb-3">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label class="form-label">Email's Password<font
                                                            color="#FF0000"><strong>*</strong></font></label>
                                                    <input type="password" name="email_password" id="email_password_edit"
                                                        class="form-control" placeholder="" autocomplete="new-password" data-eye>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

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
    <?= view('layouts/password_toggle') ?><!-- tf-eye-r1 -->

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });
        // submit user_account_insert_form
        $('#user_account_insert_form').submit(function(e) {
            e.preventDefault();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/user_account/create',
                type: 'POST',
                data: new FormData(this),
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#user_account_insert_form button[type="submit"]').attr('disabled', true);
                    $('#user_account_insert_form button[type="submit"]').html('Please wait...');
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
                            window.location.href = "<?php echo base_url(); ?>settings/user_account";
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
                            title: 'Conflict',
                            text: data.message,
                        });
                    } else if (data.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        });
                    }
                    $('#user_account_insert_form button[type="submit"]').attr('disabled', false);
                    $('#user_account_insert_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#user_account_insert_form button[type="submit"]').attr('disabled', false);
                    $('#user_account_insert_form button[type="submit"]').html('Create');
                }
            });
        });

        // .edit-user-account-btn click
        $('.edit-user-account-btn').click(function() {
            // get menu_item id
            var user_account_id = $(this).attr('data-user-account-id');
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/user_account/show/' + user_account_id,
                type: 'GET',
                data: {},
                dataType: 'json',
                beforeSend: function() {
                    $('#userAccountEditOffcanvas button[type="submit"]').attr('disabled', true);
                    $('#userAccountEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {
                        // set user_account id
                        $('#user_account_id_edit').val(response.data.id);
                        // set username_edit
                        // $('#username_edit').val(response.data.user);
                        // set group_profile_edit
                        $('#group_profile_edit').val(response.data.group_id).trigger('change');
                        // set employee_id_edit
                        $('#employee_id_edit').val(response.data.emp_id);
                        // set employee_id_edit_name
                        $('#employee_id_edit_name').val(response.data.emp_id + ' - ' + response.employee_name);
                        // email data
                        $('#host_edit').val(response.data.host);
                        $('#email_edit').val(response.data.email);
                        $('#email_password_edit').val(response.data.email_password);
                        // login_type
                        if (response.data.login_type == 1) {
                            $('#hrmLogin_edit').attr('checked', true);
                            $('#adminLogin_edit').attr('checked', false);
                        } else {
                            $('#adminLogin_edit').attr('checked', true);
                            $('#hrmLogin_edit').attr('checked', false);
                        }

                        // open offcanvas
                        $('#userAccountEditOffcanvas').offcanvas('show');
                        $('#userAccountEditOffcanvas button[type="submit"]').attr('disabled', false);
                        $('#userAccountEditOffcanvas button[type="submit"]').html('Update');
                    } else if (response.status == 404) {
                        // not found
                        Swal.fire({
                            icon: 'warning',
                            title: 'Not Found',
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
                    $('#userAccountEditOffcanvas button[type="submit"]').attr('disabled', false);
                    $('#userAccountEditOffcanvas button[type="submit"]').html('Update');
                }
            });
        });

        // submit menu_item_update_form
        $('#user_account_update_form').submit(function(e) {
            e.preventDefault();
            var id = $('#user_account_id_edit').val();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/user_account/update/' + id,
                type: 'PUT',
                data: $(this).serialize(),
                cache: false,
                processData: false,
                beforeSend: function() {
                    $('#user_account_update_form button[type="submit"]').attr('disabled', true);
                    $('#user_account_update_form button[type="submit"]').html('Please wait...');
                },
                success: function(data) {
                    if (data.status == 201) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                            showConfirmButton: true,
                        }).then(function() {
                            window.location.href = "<?php echo base_url(); ?>settings/user_account";
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
                            title: 'Duplicate menu_item Name',
                            text: data.message,
                        });
                    } else if (data.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        });
                    }
                    $('#user_account_update_form button[type="submit"]').attr('disabled', false);
                    $('#user_account_update_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#user_account_update_form button[type="submit"]').attr('disabled', false);
                    $('#user_account_update_form button[type="submit"]').html('Create');
                }
            });
        });

        // delete-user-account-btn click
        $('.delete-user-account-btn').click(function() {
            Swal.fire({
                title: 'Please Confirm',
                html: "<label class='data-text'>Are you sure you want to delete this User Account ? Please note that this action cannot be undone, so proceed with caution.</label>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    // get menu_item id
                    var user_account_id = $(this).attr('data-user-account-id');
                    // ajax call
                    $.ajax({
                        url: '<?php echo base_url(); ?>settings/user_account/delete/' + user_account_id,
                        type: 'DELETE',
                        data: {},
                        dataType: 'json',
                        beforeSend: function() {
                            $('.delete-user-account-btn').attr('disabled', true);
                            $('.delete-user-account-btn').html('<i class="fa fa-spinner fa-spin"></i>');
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
                                    window.location.href = "<?php echo base_url(); ?>settings/user_account";
                                });
                            } else if (data.status == 500) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: data.message,
                                });
                            }
                            $('.delete-user-account-btn').attr('disabled', false);
                            $('.delete-user-account-btn').html('<i class="fa fa-trash"></i>');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                            $('.delete-user-account-btn').attr('disabled', false);
                            $('.delete-user-account-btn').html('<i class="fa fa-trash"></i>');
                        }
                    });
                }
            })

        });

        // .host_edit change
        $('#host_edit').focusout(function() {
            var host = $(this).val();
            var regex = /^(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?$/;
            if (!regex.test(host)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Host',
                    text: 'Please enter a valid host name. Ex: smtp.gmail.com',
                });
                $(this).val('');
            }
        });

        // .access_check change
        $('.access_check').change(function() {
            var check = $(this);
            var user_account_id = $(this).attr('data-user-account-id');
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/user_account/update_access/' + user_account_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $(check).attr('disabled', true);
                },
                success: function(data) {
                    if (data.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                            showConfirmButton: true,
                        }).then(function() {
                            location.reload();
                        });
                    } else if (data.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        });
                    }
                    $(check).attr('disabled', false);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $(check).attr('disabled', false);
                }
            });
        });

        // .group_profile change
        $('#group_profile').change(function() {
            var group_profile = $(this).val();
            if (group_profile == 14) {
                $('.agent_div').removeClass('d-none');
            } else {
                $('.agent_div').addClass('d-none');
            }
        });

        // hrmLogin click
        $('#hrmLogin').click(function() {
            $('#uname_pword-div').addClass('d-none');
            $('#username').removeAttr('required');
            $('#password').removeAttr('required');
            $('#hrm_crd-div').removeClass('d-none');
        });
        // adminLogin click
        $('#adminLogin').click(function() {
            $('#uname_pword-div').removeClass('d-none');
            $('#username').attr('required', true);
            $('#password').attr('required', true);
            $('#hrm_crd-div').addClass('d-none');
        });
    </script>
</body>


</html>