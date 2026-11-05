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
                        <div class="row">
                            <div class="col-md-4">
                                <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title" style="font-size: 18px;">Chart of Accounts</h4>
                                    </div>
                                </div>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body" style="padding: 0px;">
                                                <div class="example-warp">
                                                    <div id="treeview8"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-12 d-flex">
                                        <div class="me-auto">
                                            <h4 class="p-title" style="font-size: 18px;">
                                                <?php
                                                if ($level == "base_account") {
                                                    echo 'Title Accounts';
                                                } else if ($level == "title_account") {
                                                    echo 'Heading Accounts';
                                                } else if ($level == "heading_account") {
                                                    echo 'Posting Accounts';
                                                } else {
                                                    echo 'Base Accounts';
                                                }
                                                ?>
                                            </h4>
                                        </div>
                                        <div>
                                            <?php
                                            if ($level == "base_account" || $level == "title_account" || $level == "heading_account") {
                                            ?>
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                                                    <i class="bi bi-plus"></i> Create New Account
                                                </button>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body">
                                                <div class="mb-3 p-3 border-bottom">
                                                    <span>
                                                        <?php
                                                        echo "<strong><a href='" . base_url() . "accounts'><i class='bi bi-house-door'></i></a></strong>";
                                                        $codes_array = array();
                                                        $names = array();
                                                        if (isset($path) && isset($codes)) {
                                                            $names = explode("//", $path);
                                                            $codes_array = explode("//", $codes);
                                                            $x = 1;
                                                            foreach ($names as $name) {
                                                                if ($x == 1) {
                                                                    echo " → " . "<a class='text-primary' href='" . base_url() . "accounts?BCode=" . $codes_array[0] . "&path=" . $names[0] . "&codes=" . $codes_array[0] . "'>" . $name . "</a>";
                                                                } else if ($x == 2) {
                                                                    echo " → " . "<a class='text-primary' href='" . base_url() . "accounts/title_accounts?BCode=" . $codes_array[0] . "&TCode=" . $codes_array[1] . "&path=" . $names[0] . "//" . $names[1] . "&codes=" . $codes_array[0] . "//" . $codes_array[1] . "'>" . $name . "</a>";
                                                                } else if ($x == 3) {
                                                                    echo " → " . "<a class='text-primary' href='" . base_url() . "accounts/heading_accounts?BCode=" . $codes_array[0] . "&TCode=" . $codes_array[1] . "&HCode=" . $codes_array[2] . "&path=" . $names[0] . "//" . $names[1] . "//" . $names[2] . "&codes=" . $codes_array[0] . "//" . $codes_array[1] . "//" . $codes_array[2] . "'>" . $name . "</a>";
                                                                }
                                                                $x++;
                                                            }
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                                <div class="table-responsive p-4 pt-2">
                                                    <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Account Name</th>
                                                                <?php
                                                                if ($level == "heading_account") {
                                                                    echo '<th>L - Code</th>';
                                                                }
                                                                ?>
                                                                <?php
                                                                if ($level == "base_account" || $level == "heading_account" || $level == "title_account") {
                                                                    echo '<th>Actions</th>';
                                                                }
                                                                ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            if (isset($page_data)) {
                                                                $x = 1;
                                                                foreach ($page_data as $account) {
                                                            ?>
                                                                    <tr style="width: 100%;">
                                                                        <td style="width: 10%;"><?php echo $x; ?></td>
                                                                        <td>
                                                                            <?php
                                                                            if ($level == "base_account") {
                                                                                // echo $account['TName'];
                                                                                echo "<a class='text-primary fw-bold' href='" . base_url() . "accounts/title_accounts?BCode=" . $account['B_Code'] . "&TCode=" . $account['T_Code'] . "&path=" . $names[0] . "//" . $account['TName'] . "&codes=" . $codes_array[0] . "//" . $account['T_Code'] . "'>" . $account['TName'] . "</a>";
                                                                            } else if ($level == "title_account") {
                                                                                // echo $account['HName'];
                                                                                echo "<a class='text-primary fw-bold' href='" . base_url() . "accounts/heading_accounts?BCode=" . $account['B_Code'] . "&TCode=" . $account['T_Code'] . "&HCode=" . $account['H_Code'] . "&path=" . $names[0] . "//" . $names[1] . "//" . $account['HName'] . "&codes=" . $codes_array[0] . "//" . $codes_array[1] . "//" . $account['H_Code'] . "'>" . $account['HName'] . "</a>";
                                                                            } else if ($level == "heading_account") {
                                                                                echo $account['PName'];
                                                                            }
                                                                            ?>
                                                                        </td>
                                                                        <?php
                                                                        if ($level == "heading_account") {
                                                                            echo '<td>' . $account['L_Code'] . '</td>';
                                                                        }
                                                                        ?>
                                                                        <td style="width: 5%;">
                                                                            <div class="dropup">
                                                                                <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                    <i class="bi bi-three-dots-vertical"></i>
                                                                                </button>
                                                                                <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                    <?php
                                                                                    $edit_type = "";
                                                                                    $acc_name = "";
                                                                                    $acc_code = "";
                                                                                    if ($level == "base_account") {
                                                                                        $edit_type = "title_account";
                                                                                        $acc_name = $account['TName'];
                                                                                        $acc_code = $account['T_Code'];
                                                                                    } else if ($level == "title_account") {
                                                                                        $edit_type = "heading_account";
                                                                                        $acc_name = $account['HName'];
                                                                                        $acc_code = $account['H_Code'];
                                                                                    } else if ($level == "heading_account") {
                                                                                        $edit_type = "posting_account";
                                                                                        $acc_name = $account['PName'];
                                                                                        $acc_code = $account['P_Code'];
                                                                                    }
                                                                                    ?>
                                                                                    <li>
                                                                                        <a href="#" data-acc-type="<?php echo $edit_type; ?>" data-acc-id="<?php echo $account['ID']; ?>" data-acc-name="<?php echo $acc_name; ?>" class="dropdown-item edit-account-btn">Edit</a>
                                                                                    </li>
                                                                                    <li><a class="dropdown-item text-danger delete-account-btn" data-acc-type="<?php echo $edit_type; ?>" data-acc-code="<?php echo $acc_code; ?>" href="#">Delete</a></li>
                                                                                </ul>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                <?php
                                                                    $x++;
                                                                }
                                                            } else {
                                                                $x = 1;
                                                                foreach ($base_accounts as $account) {
                                                                ?>
                                                                    <tr style="width: 100%;">
                                                                        <td style="width: 20%;"><?php echo $x; ?></td>
                                                                        <td>
                                                                            <?php
                                                                            // echo $account['BName']
                                                                            echo "<a class='text-primary fw-bold' href='" . base_url() . "accounts?BCode=" . $account['B_Code'] . "&path=" . $account['BName'] . "&codes=" . $account['B_Code'] . "'>" . $account['BName'] . "</a>";
                                                                            ?>
                                                                        </td>
                                                                    </tr>
                                                            <?php
                                                                    $x++;
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- container-fluid -->

                <?php
                $modal_title = "";
                $edit_title = "";
                $acc_type = "";
                $acc_b_code = "";
                $acc_t_code = "";
                $acc_h_code = "";
                if ($level == "base_account") {
                    $modal_title = "Create Title Account";
                    $edit_title = "Edit Title Account";
                    $acc_type = "title_account";
                    $acc_b_code = $codes_array[0];
                } else if ($level == "title_account") {
                    $modal_title = "Create Heading Account";
                    $edit_title = "Edit Heading Account";
                    $acc_type = "heading_account";
                    $acc_b_code = $codes_array[0];
                    $acc_t_code = $codes_array[1];
                } else if ($level == "heading_account") {
                    $modal_title = "Create Posting Account";
                    $edit_title = "Edit Posting Account";
                    $acc_type = "posting_account";
                    $acc_b_code = $codes_array[0];
                    $acc_t_code = $codes_array[1];
                    $acc_h_code = $codes_array[2];
                }
                ?>

                <div class="modal fade" id="editAccountModal" tabindex="-1" aria-labelledby="editAccountModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="edit_account_form">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">
                                        <?php echo $edit_title; ?>
                                    </h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="edit_account_name" class="form-label">Account Name<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" class="form-control" id="edit_account_name" name="edit_account_name" placeholder="Enter...">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" id="edit_account_form_btn" class="btn btn-primary" data-acc-type="" data-acc-id="">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="createAccountModal" tabindex="-1" aria-labelledby="createAccountModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="new_account_form">
                                <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="exampleModalLabel">
                                        <?php echo $modal_title; ?>
                                    </h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="new_account" class="form-label">New Account Name<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" class="form-control" id="new_account" name="new_account" placeholder="Enter New Account Name">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" id="new_account_form_btn" class="btn btn-primary" data-acc-type="<?php echo $acc_type; ?>" data-b-code="<?php echo $acc_b_code; ?>" data-t-code="<?php echo $acc_t_code; ?>" data-h-code="<?php echo $acc_h_code; ?>">Save</button>
                                </div>
                            </form>
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
    <script src="<?= base_url(); ?>assets/vendor/bootstrap-treeview/bootstrap-treeview.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jstree/jstree.min.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/treeview/jstree.js"></script>
    <script src="<?= base_url(); ?>assets/js/pages/treeview/bootstrap-treeview.js"></script>
    <script>
        // .btn-toggle-fullwidth click after 2s
        // setTimeout(function() {
        //     $('.btn-toggle-fullwidth').click();
        // }, 1000);

        // new account form submit
        $('#new_account_form').submit(function(e) {
            e.preventDefault();
            var new_account = $('#new_account').val();
            var acc_type = $('#new_account_form_btn').attr('data-acc-type');
            var b_code = $('#new_account_form_btn').attr('data-b-code');
            var t_code = $('#new_account_form_btn').attr('data-t-code');
            var h_code = $('#new_account_form_btn').attr('data-h-code');
            if (acc_type == "title_account") {
                $.ajax({
                    url: '<?php echo base_url(); ?>accounts/create/title_account',
                    type: 'POST',
                    data: {
                        TName: new_account,
                        B_Code: b_code
                    },
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
            } else if (acc_type == "heading_account") {
                $.ajax({
                    url: '<?php echo base_url(); ?>accounts/create/heading_account',
                    type: 'POST',
                    data: {
                        HName: new_account,
                        B_Code: b_code,
                        T_Code: t_code
                    },
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
            } else if (acc_type == "posting_account") {
                $.ajax({
                    url: '<?php echo base_url(); ?>accounts/create/posting_account',
                    type: 'POST',
                    data: {
                        PName: new_account,
                        B_Code: b_code,
                        T_Code: t_code,
                        H_Code: h_code
                    },
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
            }
        });

        // .edit-account-btn click event
        $('.edit-account-btn').click(function() {
            var acc_type = $(this).data('acc-type');
            var acc_id = $(this).data('acc-id');
            var acc_name = $(this).data('acc-name');
            $('#edit_account_form_btn').attr('data-acc-type', acc_type);
            $('#edit_account_form_btn').attr('data-acc-id', acc_id);
            $('#edit_account_name').val(acc_name);
            $('#editAccountModal').modal('show');
        });

        // edit account form submit
        $('#edit_account_form').submit(function(e) {
            e.preventDefault();
            var edit_account_name = $('#edit_account_name').val();
            var acc_type = $('#edit_account_form_btn').attr('data-acc-type');
            var acc_id = $('#edit_account_form_btn').attr('data-acc-id');
            if (acc_type == "title_account") {
                $.ajax({
                    url: '<?php echo base_url(); ?>accounts/update/title_account',
                    type: 'POST',
                    data: {
                        TName: edit_account_name,
                        Id: acc_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
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
            } else if (acc_type == "heading_account") {
                $.ajax({
                    url: '<?php echo base_url(); ?>accounts/update/heading_account',
                    type: 'POST',
                    data: {
                        HName: edit_account_name,
                        Id: acc_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
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
            } else if (acc_type == "posting_account") {
                $.ajax({
                    url: '<?php echo base_url(); ?>accounts/update/posting_account',
                    type: 'POST',
                    data: {
                        PName: edit_account_name,
                        Id: acc_id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
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
            }
        });

        // .delete-account-btn click event
        $('.delete-account-btn').click(function() {
            var acc_type = $(this).data('acc-type');
            var acc_code = $(this).data('acc-code');
            tfConfirmReason({ // tf-e8-access-r1: reason required
                title: 'Are you sure?',
                text: "You want to delete this account!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (acc_type == "title_account") {
                        $.ajax({
                            url: '<?php echo base_url(); ?>accounts/delete/title_account',
                            type: 'POST',
                            data: {
                                TCode: acc_code,
                                reason: result.value // tf-e8-access-r1
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
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
                    } else if (acc_type == "heading_account") {
                        $.ajax({
                            url: '<?php echo base_url(); ?>accounts/delete/heading_account',
                            type: 'POST',
                            data: {
                                HCode: acc_code,
                                reason: result.value // tf-e8-access-r1
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
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
                    } else if (acc_type == "posting_account") {
                        $.ajax({
                            url: '<?php echo base_url(); ?>accounts/delete/posting_account',
                            type: 'POST',
                            data: {
                                PCode: acc_code,
                                reason: result.value // tf-e8-access-r1
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status == 200) {
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
                    }
                }
            });
        });
    </script>
</body>




</html>