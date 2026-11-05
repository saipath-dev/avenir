<!-- Mirrored from themesbrand.com/velzon/html/default/index.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 21 May 2024 07:37:28 GMT -->
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
                    <div class="profile-foreground position-relative mx-n4 mt-n4">
                        <div class="profile-wid-bg" style="height:380px">
                            <img src="assets/images/profile-bg.jpg" alt="" class="profile-wid-img" />
                        </div>
                    </div>
                    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper pt-5">
                        <div class="row g-4 pt-3">
                            <div class="col-auto">
                                <div class="avatar-lg position-relative" style="width: 100px; height: 105px;">
                                    <img id="profile_picture_preview" src="<?= $profile_picture; ?>" alt="user-img" class="img-thumbnail rounded-circle" style="width: 100%; height: 100%;" />
                                    <label for="profile_picture" class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle">
                                        <i class="ri-camera-line"></i>
                                    </label>
                                    <input type="file" id="profile_picture" name="profile_picture" class="d-none" accept="image/*">
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col">
                                <div class="p-2 pt-1">
                                    <h1 class="text-white mb-1"><?= $sidebar_employee_name ?></h1>
                                    <p class="text-white text-opacity-75"><?= $sidebar_employee_name_profile_name ?></p>
                                    <div class="hstack text-white-50 gap-1">
                                        <div class="me-2"><i class="ri-map-pin-user-line me-1 text-white text-opacity-75 fs-16 align-middle"></i>Employee Number : <?= $sidebar_employee_code; ?></div>
                                        <!-- <div>
                                            <i class="ri-building-line me-1 text-white text-opacity-75 fs-16 align-middle"></i>Themesbrand
                                        </div> -->
                                    </div>

                                    <!--two textbox to update employee name and phoe number-->
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="employee_name" class="form-label text-white">Name</label>
                                                <input type="text" class="form-control" id="employee_name" name="employee_name" value="<?= $employee_name ?>" placeholder="Enter Name">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="employee_phone" class="form-label text-white">Phone Number</label>
                                                <input type="text" class="form-control" id="employee_phone" name="employee_phone" value="<?= $employee_phone ?>" placeholder="Enter Phone Number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex mt-4">
                                        <button class="btn btn-danger me-2" onclick="logout()">Logout</button>
                                        <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                                        <button class="btn btn-primary" id="update_contact_info">Update Contact Info</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <!--end row-->
                    </div>
                    <!--end row-->

                </div><!-- container-fluid -->
            </div><!-- End Page-content -->
            <!-- End Page-content -->

            <!-- Change Password Modal -->
            <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="change_password_form">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="exampleModalLabel">Change Password</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="current_password" class="form-label">Current Password<font color="#FF0000"><strong>*</strong></font></label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter Current Password">
                                            <div class="invalid-feedback" id="current_password_error"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="new_password" class="form-label">New Password<font color="#FF0000"><strong>*</strong></font></label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter New Password">
                                            <div class="invalid-feedback" id="new_password_error"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="confirm_password" class="form-label">Confirm Password<font color="#FF0000"><strong>*</strong></font></label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm New Password">
                                            <div class="invalid-feedback" id="confirm_password_error"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

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
            $('#profile_picture').on('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#profile_picture_preview').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            });

            $('#profile_picture').on('change', function() {
                const file = this.files[0];
                if (!file) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No File Selected',
                        text: 'Please select a file to upload.',
                    });
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to update your profile picture?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Update!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('profile_picture', file);

                        $.ajax({
                            url: "<?php echo base_url(); ?>my_profile/upload_profile_picture",
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Uploading...',
                                    text: 'Uploading your profile picture, please wait...',
                                    allowOutsideClick: false,
                                    showConfirmButton: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function(response) {
                                Swal.close();
                                if (response.status == 201) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Profile Picture Updated',
                                        text: response.message,
                                        showConfirmButton: true,
                                        confirmButtonText: 'OK',
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Update Failed',
                                        text: response.message,
                                    });
                                }
                            },
                            error: function() {
                                Swal.close();
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong while uploading the profile picture!',
                                });
                            }
                        });
                    }
                });
            });
            // change password form
            $('#change_password_form').on('submit', function(e) {
                e.preventDefault();
                // get form data
                var form_data = $(this).serialize();
                // submit form data to api
                $.ajax({
                    url: "<?php echo base_url(); ?>my_profile/change_password",
                    type: 'POST',
                    dataType: 'json',
                    data: form_data,
                    success: function(response) {
                        // check if the response has success
                        if (response.status == 201) {
                            // hide modal
                            $('#changePasswordModal').modal('hide');
                            // show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                            }).then(function() {
                                if (response.redirect) {
                                    window.location.href = "<?php echo base_url(); ?>";
                                }
                            });
                        } else {
                            // show swal
                            Swal.fire({
                                icon: 'warning',
                                title: 'Warning',
                                text: response.message,
                            });
                        }
                    },
                    error: function(response) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                        });
                    }
                });
            });

            // sign out
            $('#sign_out').on('click', function() {
                logout();
            });

            function logout() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to logout from the system?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#00a65a',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Logout!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "<?php echo base_url(); ?>logout",
                            method: "GET",
                            dataType: null,
                            success: function(response) {
                                if (response.status == 200) {
                                    let timerInterval;
                                    Swal.fire({
                                        title: "Please Wait...",
                                        html: response.msg,
                                        timer: 2000,
                                        timerProgressBar: false,
                                        didOpen: () => {
                                            Swal.showLoading();
                                        },
                                        willClose: () => {
                                            clearInterval(timerInterval);
                                        }
                                    }).then((result) => {
                                        // remove local storge items
                                        localStorage.removeItem('previousNotifications');
                                        // redirect to login page
                                        if (result.dismiss === Swal.DismissReason.timer) {
                                            var is_admin = "<?php echo session()->get('is_admin'); ?>"
                                            if (is_admin == 1) {
                                                window.location.href = "<?php echo base_url() ?>admin";
                                            } else {
                                                window.location.href = "<?php echo base_url() ?>";
                                            }
                                        }
                                    });

                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: errorThrown,
                                    icon: 'error',
                                    confirmButtonColor: '#00a65a',
                                });
                            }
                        });
                    }
                });
            }

            // update contact info
            $('#update_contact_info').on('click', function() {
                // get employee Name and phone number
                var employee_name = $('#employee_name').val();
                var employee_phone = $('#employee_phone').val();
                // check if name and phone number are not empty
                if (employee_name != '' && employee_phone != '') {
                    // submit form data to api
                    $.ajax({
                        url: "<?php echo base_url(); ?>my_profile/update_contact_info",
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            employee_name: employee_name,
                            employee_phone: employee_phone
                        },
                        success: function(response) {
                            // check if the response has success
                            if (response.status == 201) {
                                // show success message
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message,
                                });

                                //reload the page
                                location.reload();
                                de

                            } else {
                                // show swal
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Warning',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(response) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                            });
                        }
                    });
                } else {
                    // show swal
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please fill all the required fields!',
                    });
                }
            });
        });
    </script>
</body>


<!-- Mirrored from themesbrand.com/velzon/html/default/index.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 21 May 2024 07:38:25 GMT -->

</html>