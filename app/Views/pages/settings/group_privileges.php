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
                                        <h4 class="p-title">Group Privileges</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="header mb-2">
                                                <h6>Assigned Privileges</h6>
                                            </div>
                                            <div class="body">
                                                <div>
                                                    <div class="row clearfix mb-2">
                                                        <div class="col-sm-12">
                                                            <div class="form-group">
                                                                <select name="group_profile" id="group_profile" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                    <option value="0">Select a Profile...</option>
                                                                    <?php
                                                                    foreach ($group_profiles as $profile) {
                                                                        // if $group_id == $profile->id, then set selected
                                                                        $selected = "";
                                                                        if ($group_id == $profile['id']) {
                                                                            $selected = "selected";
                                                                        }
                                                                        echo '<option value="' . $profile['id'] . '" ' . $selected . ' >' . $profile['type'] . '</option>';
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row clearfix mt-1 mb-3">
                                                        <div class="col-sm-12">
                                                            <button type="button" id="filter_btn" class="btn btn-primary px-5"><i class="bi bi-funnel-fill"></i>&nbsp;&nbsp;Filter</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php if (isset($privileges)) { ?>
                                                    <?php foreach ($categories as $category) { ?>
                                                        <div class="card bordered shadow">
                                                            <div class="card-header bg-light d-flex py-2">
                                                                <div class="d-flex mr-auto" style="font-weight: 700; font-size: 13px;">
                                                                    <?php echo $category['category']; ?>
                                                                </div>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="table-responsive">
                                                                    <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                                        <thead class="thead-light">
                                                                            <tr>
                                                                                <th>#</th>
                                                                                <th>Name</th>
                                                                                <th>Status</th>
                                                                                <th>Action</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php
                                                                            $x = 1;
                                                                            foreach ($menu_list as $menu_item) {
                                                                                if ($menu_item['cat_id'] == $category['id']) {
                                                                            ?>
                                                                                    <tr style="width: 100%;">
                                                                                        <td style="width: 10%;"><?php echo $x; ?></td>
                                                                                        <td><?php echo $menu_item['name']; ?></td>

                                                                                        <?php
                                                                                        // check if user has privilege, for this menu_item
                                                                                        $has_privilege = false;
                                                                                        $privilege_id = 0;
                                                                                        foreach ($privileges as $privilege) {
                                                                                            if ($privilege['menu_id'] == $menu_item['id']) {
                                                                                                $has_privilege = true;
                                                                                                $privilege_id = $privilege['id'];
                                                                                                break;
                                                                                            }
                                                                                        }
                                                                                        ?>


                                                                                        <td style="width: 10%;" class="text-info">
                                                                                            <div class="d-flex justify-content-center">
                                                                                                <img src="<?php echo base_url(); ?>assets/images/<?php echo ($has_privilege) ? "ok" : "close"; ?>.png" alt="..." width="20px">
                                                                                            </div>
                                                                                        </td>
                                                                                        <td style="width: 10%;">
                                                                                            <div class="d-flex justify-content-center pe-2">
                                                                                                <?php
                                                                                                if ($has_privilege) {
                                                                                                ?>
                                                                                                    <button class="btn btn-danger btn-sm remove-privilege-btn" data-privilege-id="<?php echo $privilege_id; ?>">Deny</button>
                                                                                                <?php } else { ?>
                                                                                                    <button class="btn btn-success btn-sm add-privilege-btn" data-profile-id="<?php echo $group_id; ?>" data-menu-id="<?php echo $menu_item['id']; ?>">Allow</button>
                                                                                                <?php } ?>
                                                                                            </div>
                                                                                        </td>
                                                                                    </tr>
                                                                            <?php $x++;
                                                                                }
                                                                            } ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                <?php } ?>
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
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        document.addEventListener("DOMContentLoaded", function(event) {
            var scrollpos = localStorage.getItem('scrollpos');
            if (scrollpos) window.scrollTo(0, scrollpos);
        });

        window.onbeforeunload = function(e) {
            localStorage.setItem('scrollpos', window.scrollY);
        };


        // filter button click
        $('#filter_btn').click(function() {
            var group_profile = $('#group_profile').val();
            window.location.href = "<?php echo base_url(); ?>settings/group_privilege/" + group_profile;
        });

        // add privilege button click
        $('.add-privilege-btn').click(function() {
            var group_profile = $(this).data('profile-id');
            var group_id = group_profile;
            var menu_id = $(this).data('menu-id');
            // alert(privilege_id + " " + group_id + " " + menu_id + " " + action);
            $.ajax({
                url: "<?php echo base_url(); ?>/settings/group_privilege/add/" + group_profile + "/" + menu_id,
                method: "GET",
                data: {},
                dataType: 'json',
                success: function(data) {
                    console.log(data);
                    if (data.status == 201) {
                        location.reload();
                    } else if (data.status == 400) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Warning',
                            text: 'Bad Request. Please try again.',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                }
            });
        });

        // remove privilege button click
        $('.remove-privilege-btn').click(function() {
            var privilege_id = $(this).data('privilege-id');
            // alert(privilege_id + " " + group_id + " " + menu_id + " " + action);
            $.ajax({
                url: "<?php echo base_url(); ?>/settings/group_privilege/remove/" + privilege_id,
                method: "GET",
                data: {},
                dataType: 'json',
                success: function(data) {
                    console.log(data);
                    if (data.status == 200) {
                        location.reload();
                    } else if (data.status == 400) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Warning',
                            text: 'Bad Request. Please try again.',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong!',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                }
            });
        });
    </script>
</body>




</html>