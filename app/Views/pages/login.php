<?= view('layouts/head') ?>

<body>

    <style>
        @import url('https://fonts.cdnfonts.com/css/skid-rock');

        .login-container {
            min-height: 100vh;
            width: 100%;
        }

        .login-form-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, #e90c8b 0%, #8f2a8d 100%);
            /* Alternative solid colors you can use:
            background-color: #4e54c8;
            background-color: #6c63ff;
            background-color: #5a67d8;
            */
        }

        .login-image-section {
            min-height: 100vh;
            background-image: url('<?= base_url() ?>assets/images/login_image.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .login-form-wrapper {
            width: 100%;
            max-width: 350px;
        }

        .brand-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-section h1 {
            color: #ffffff !important;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .brand-section p {
            color: #f0f0f0 !important;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
        }

        @media (max-width: 768px) {

            .login-form-section,
            .login-image-section {
                min-height: 50vh;
            }
        }
    </style>

    <div class="container-fluid p-0">
        <div class="row g-0 login-container">
            <!-- Login Form Section (col-3) -->
            <div class="col-12 col-md-3">
                <div class="login-form-section">
                    <div class="login-form-wrapper">
                        <div class="brand-section">
                            <h1 class="text-primary" style="font-family: 'Skid Rock', sans-serif; font-size: 36px;">Avenir Holidays</h1>
                            <p class="text-muted fs-14 fw-medium">Management System</p>
                        </div>

                        <div class="card shadow-sm">
                            <div class="card-body p-3">
                                <div class="text-center mt-2">
                                    <h5 class="text-primary">Welcome Back!</h5>
                                    <p class="text-muted small">First thing's first, please enter your credentials</p>
                                </div>
                                <div class="p-2 mt-3">
                                    <form id="login_form" action="javascript:void(0)">

                                        <div class="mb-3">
                                            <label for="username" class="form-label small">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label small" for="password-input">Password</label>
                                            <div class="position-relative auth-pass-inputgroup mb-3">
                                                <input type="password" class="form-control pe-5 password-input" placeholder="Enter password" id="password-input" name="password-input">
                                                <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                            </div>
                                        </div>

                                        <div class="mt-4 mb-2">
                                            <button class="btn btn-success w-100" type="submit" id="login_btn">Sign In</button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end card -->
                    </div>
                </div>
            </div>

            <!-- Background Image Section (col-9) -->
            <div class="col-12 col-md-9">
                <div class="login-image-section"></div>
            </div>
        </div>
    </div>


    <!-- footer -->
    <!-- <footer class="footer">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center">
                            <p class="mb-0 text-muted">&copy;
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> Developed by Cyclomax International
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer> -->
    <!-- end Footer -->
    </div>
    <!-- end auth-page-wrapper -->

    <?= view('layouts/scripts') ?>
    <script>
        $(document).ready(function() {
            // get redirect param from url
            var url_string = window.location.href;
            var url = new URL(url_string);
            var redirect = url.searchParams.get("redirect");
            // Login form submit
            $("#login_form").on('submit', (function(e) {
                e.preventDefault();
                var formData = new FormData();
                formData.append('username', $("#username").val());
                formData.append('password', $("#password-input").val());
                $.ajax({
                    url: '<?php echo base_url() ?>login',
                    type: "POST",
                    data: formData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    beforeSend: function() {
                        $("#login_btn").prop("disabled", true);
                        $("#login_btn").html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Authenticating...');
                    },
                    success: function(data) {
                        var response = data;
                        if (response.status == 200) {

                            function goToDashboard() {
                                if (redirect) {
                                    window.location.href = "<?php echo base_url() ?>" + redirect;
                                } else if (response.redirect) {
                                    window.location.href = "<?php echo base_url() ?>" + response.redirect;
                                } else {
                                    window.location.href = "<?php echo base_url() ?>dashboard";
                                }
                            }

                            function promptUsdRateIfNeeded() {
                                if (!response.needs_usd_rate) {
                                    goToDashboard();
                                    return;
                                }
                                Swal.fire({
                                    icon: "info",
                                    title: "Today's USD Rate",
                                    html: "Please set today's USD rate before continuing.",
                                    input: "number",
                                    inputAttributes: {
                                        step: "0.01",
                                        min: "0"
                                    },
                                    inputPlaceholder: "Enter today's USD rate",
                                    confirmButtonText: "Save",
                                    showCancelButton: true,
                                    cancelButtonText: "Skip for now",
                                    allowOutsideClick: false,
                                    preConfirm: (value) => {
                                        if (!value || isNaN(value) || parseFloat(value) <= 0) {
                                            Swal.showValidationMessage("Please enter a valid rate");
                                            return false;
                                        }
                                        return new Promise((resolve) => {
                                            $.post("<?php echo base_url() ?>master_files/daily_usd_rate/quick_save", {
                                                rate_date: new Date().toISOString().split('T')[0],
                                                rate: value
                                            }, function(resp) {
                                                if (resp && resp.status == 200) {
                                                    resolve(resp);
                                                } else {
                                                    Swal.showValidationMessage((resp && resp.message) || "Could not save the rate. Please try again.");
                                                    resolve(false);
                                                }
                                            }, 'json').fail(function(xhr) {
                                                var msg = "Could not save the rate. Please try again.";
                                                if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                                                    msg = xhr.responseJSON.msg;
                                                } else if (xhr && xhr.status === 403) {
                                                    msg = "You don't have permission to save the rate.";
                                                }
                                                Swal.showValidationMessage(msg);
                                                resolve(false);
                                            });
                                        });
                                    }
                                }).then(() => {
                                    goToDashboard();
                                });
                            }

                            let timerInterval;
                            Swal.fire({
                                icon: "success",
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
                                if (result.dismiss === Swal.DismissReason.timer) {
                                    promptUsdRateIfNeeded();
                                }
                            });

                        } else if (response.status == 400 || response.status == 401) {
                            Swal.fire({
                                title: "Warning",
                                text: response.msg,
                                icon: "warning"
                            });
                        } else if (response.status == 500) {
                            Swal.fire({
                                title: "Error",
                                text: response.msg,
                                icon: "error"
                            });
                        }
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;
                        if (response && (response.status == 401 || response.status == 400)) {
                            Swal.fire({
                                title: "Warning",
                                text: response.msg,
                                icon: "warning"
                            });
                        } else if (response && response.status == 429) {
                            Swal.fire({
                                title: "Too Many Attempts",
                                text: response.msg,
                                icon: "warning",
                                confirmButtonColor: "#d33"
                            });
                        } else {
                            Swal.fire({
                                title: "Error",
                                text: "An unexpected error occurred. Please try again.",
                                icon: "error"
                            });
                        }
                    },
                    complete: function() {
                        $("#login_btn").prop("disabled", false);
                        $("#login_btn").html('Sign Me In');
                    }
                });
            }));
        });
    </script>
</body>




</html>