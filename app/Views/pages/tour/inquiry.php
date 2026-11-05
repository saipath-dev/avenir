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
                                        <a href="<?= base_url('inquiries/all') ?>" class="btn btn-primary ms-auto">
                                            All Inquiries
                                        </a>
                                    </div>
                                </div>


                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
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
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });
        // on form submit
        $('#form_submit').submit(function() {
            var name = $('#name').val();
            var email = $('#email').val();
            var phone = $('#phone').val();
            var message = $('#message').val();
            var agent_name = $('#agent').val();
            var start_date = $('#start_date').val();
            var end_date = $('#end_date').val();
            var roomsgl = $('#roomsgl').val();
            var roomdbl = $('#roomdbl').val();
            var roomtpl = $('#roomtpl').val();

            $.ajax({
                url: '<?= base_url('inquiries/new') ?>',
                type: 'POST',
                data: {
                    name: name,
                    email: email,
                    phone: phone,
                    message: message,
                    agent_name: agent_name,
                    start_date: start_date,
                    end_date: end_date,
                    roomsgl: roomsgl,
                    roomdbl: roomdbl,
                    roomtpl: roomtpl
                },
                success: function(response) {
                    if (response.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Inquiry has been saved successfully',
                        }).then((result) => {
                            location.reload();
                        });
                    } else if (response.status == 400) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Required fields are missing or invalid',
                        });
                    }
                },
                error: function(response) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong. Please re-check the form and try again.',
                    });
                }
            });
        });
    </script>
</body>




</html>