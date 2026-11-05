<?= view('layouts/head') ?>

<body>

    <style>
        @import url('https://fonts.cdnfonts.com/css/skid-rock');
    </style>

    <div class="auth-page-wrapper" style="min-height: 100vh;">
        <div class="row g-0" style="min-height: 100vh;">
            <!-- Login Form Section -->
            <div class="col-lg-3 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
                <div class="auth-page-content w-100">
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-md-10 col-lg-8 col-xl-8">
                                <!-- Logo and Title -->
                                <div class="text-center mb-5 text-white">
                                    <div>
                                        <a href="index.html" class="d-inline-block auth-logo">
                                            <h1 class="text-white" style="font-family: 'Skid Rock', sans-serif; font-size: 48px;">Avenir Holidays</h1>
                                        </a>
                                    </div>
                                    <p class="mt-3 fs-15 fw-medium">Management System</p>
                                </div>

                                <!-- Login Card -->
                                <div class="card">
                                    <div class="card-body p-4">
                                        <div class="text-center mt-2">
                                            <h5 class="text-primary">Welcome Back !</h5>
                                            <p class="text-muted">Sign in to continue to Avenir.</p>
                                        </div>
                                        <div class="p-2 mt-4">
                                            <form id="login_form" action="javascript:void(0)">

                                                <div class="mb-3">
                                                    <label for="username" class="form-label">Username</label>
                                                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter username">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="password-input">Password</label>
                                                    <div class="position-relative auth-pass-inputgroup mb-3">
                                                        <input type="password" class="form-control pe-5 password-input" placeholder="Enter password" id="password-input" name="password-input">
                                                        <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                                    </div>
                                                </div>

                                                <!-- <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="auth-remember-check">
                                            <label class="form-check-label" for="auth-remember-check">Remember me</label>
                                        </div> -->

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
                </div>
                <!-- end login form section -->
            </div>
            <!-- Background Image Section -->
            <div class="col-lg-9" style="background-image: url('<?= base_url() ?>assets/images/login_image.jpeg'); background-size: cover; background-position: center; background-repeat: no-repeat; min-height: 100vh;">
            </div>
            <!-- end background image section -->

            <!-- end row -->

            <!-- footer -->
            <footer class="footer">
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
            </footer>
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
                                        if (redirect) {
                                            window.location.href = "<?php echo base_url() ?>" + redirect;
                                        } else {
                                            window.location.href = "<?php echo base_url() ?>dashboard";
                                        }
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
                        error: function(e) {
                            Swal.fire({
                                title: "Error",
                                text: "",
                                icon: "error"
                            });
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