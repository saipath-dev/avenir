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
                                        <h4 class="p-title">Staff Registration</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit">

                                                    <?php
                                                    $name = '';
                                                    $surname = '';
                                                    $fullname = '';
                                                    $other_name = '';
                                                    $nic = '';
                                                    $emp_no = '';
                                                    $tp = '';
                                                    $email = '';
                                                    $emp_no_disabled = '';
                                                    if (isset($member) && !empty($member)) {
                                                        $name = $member['Name'];
                                                        $surname = $member['surname'];
                                                        $fullname = $member['fullname'];
                                                        $other_name = $member['othr_nm'];
                                                        $nic = $member['Nic'];
                                                        $emp_no = $member['empno'];
                                                        $tp = $member['Tp'];
                                                        $email = $member['email'];
                                                        $emp_no_disabled = 'disabled';
                                                    } elseif (isset($next_emp_no)) {
                                                        $emp_no = $next_emp_no;
                                                    }
                                                    ?>

                                                    <fieldset>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="name">First Name<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="name" name="name" placeholder="Enter First Name" autocomplete="off" value="<?php echo $name; ?>" required />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="surname">Surname<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="surname" name="surname" placeholder="Enter Surname" value="<?php echo $surname; ?>" autocomplete="off" required />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="fullname">Full Name:<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="fullname" name="fullname" placeholder="Enter Full Name" value="<?php echo $fullname; ?>" autocomplete="off" required />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="other_name">Other Name</label>
                                                                    <input class="form-control" type="text" id="other_name" name="other_name" placeholder="Enter Other Name" value="<?php echo $other_name; ?>" autocomplete="off" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="nic">NIC</label>
                                                                    <input class="form-control" type="text" id="nic" name="nic" placeholder="Enter NIC" autocomplete="off" value="<?php echo $nic; ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="emp_no">Employee No<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="emp_no" name="emp_no" placeholder="Enter Employee no" autocomplete="off" value="<?php echo $emp_no; ?>" readonly />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="tp">Contact No</label>
                                                                    <input class="form-control" type="text" id="tp" name="tp" placeholder="Enter Contact No" autocomplete="off" value="<?php echo $tp ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label class="form-label" for="email">Email</label>
                                                                    <input class="form-control" type="email" id="email" name="email" placeholder="Enter Email" autocomplete="off" value="<?php echo $email; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row col-md-12 d-flex my-2">
                                                            <button class="btn btn-primary" style="width: fit-content;" type="submit">Save</button>
                                                            <a href="<?php echo base_url('staff/registration'); ?>" style="width: fit-content;" class="btn btn-default ms-2">Cancel</a>
                                                        </div>
                                                    </fieldset>
                                                </form>
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
        $('#form_submit').on('submit', function(e) {
            e.preventDefault();

            var name = $('#name').val();
            var surname = $('#surname').val();
            var fullname = $('#fullname').val();
            var other_name = $('#other_name').val();
            var emp_no = $('#emp_no').val();
            var tp = $('#tp').val();
            var email = $('#email').val();
            var nic = $('#nic').val();

            var url = '<?= base_url('staff/create') ?>';
            var is_update = '<?= $type ?>';
            if (is_update == 'update') {
                url = '<?= base_url('staff/update') ?>';
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    name: name,
                    surname: surname,
                    fullname: fullname,
                    other_name: other_name,
                    emp_no: emp_no,
                    tp: tp,
                    email: email,
                    nic: nic
                },
                success: function(response) {
                    if (response.status == 201 || response.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                        }).then((result) => {
                            window.location.href = '<?= base_url('staff/registration') ?>';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    }
                },
                error: function(response) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong!',
                    });
                }
            });
        });
    </script>
</body>




</html>