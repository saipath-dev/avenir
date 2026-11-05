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
                                        <h4 class="p-title">Menu List</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="d-flex border-bottom pb-3">
                                                <div class="me-auto">
                                                    <div class="header">
                                                        <span class="badge bg-dark fs-6 mt-2"><?php echo count($menu_list) . " items found." ?></span>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end align-items-center me-3">
                                                    <button data-bs-toggle="offcanvas" data-bs-target="#menuItemOffcanvas" aria-controls="menuItemOffcanvas" class="btn btn-primary" style="height: fit-content;">+ New Menu Item</button>
                                                </div>
                                            </div>
                                            <div class="body">
                                                <div class="table-responsive mt-4">
                                                    <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Category</th>
                                                                <th>Name</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($menu_list as $menu_item) {
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 10%;"><?php echo $x; ?></td>
                                                                    <td><?php echo $menu_item['category']; ?></td>
                                                                    <td><?php echo $menu_item['name']; ?></td>
                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="#" class="dropdown-item edit-menu-item-btn" data-menu-item-id="<?php echo $menu_item['id']; ?>">Edit</a>
                                                                                </li>
                                                                                <?php
                                                                                // check if the user is an admin, then show delete btn
                                                                                if ($user_role == 1) {
                                                                                ?>
                                                                                    <li><a class="dropdown-item text-danger delete-menu-item-btn" href="#" data-menu-item-id="<?php echo $menu_item['id']; ?>">Delete</a></li>
                                                                                <?php } ?>
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
        <!-- menu_item Create offcanvas -->
        <div class="offcanvas offcanvas-end" id="menuItemOffcanvas" aria-labelledby="menuitemOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="menuitemOffcanvasLabel">Create Menu Item</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <form id="menu_item_insert_form">
                    <div class="">
                        <div class="">
                            <div class="row clearfix">
                                <div class="col-md-12">
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Menu Category<font color="#FF0000"><strong>*</strong></font></label>
                                                <select name="menu_item_category" id="menu_item_category" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                    <option value="0">Select a Category...</option>
                                                    <?php
                                                    foreach ($categories as $category) {
                                                        echo '<option value="' . $category['id'] . '">' . $category['category'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Item Name<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="menu_item_name" id="menu_item_name" class="form-control" placeholder="" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Page URL<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="menu_item_url" id="menu_item_url" class="form-control" placeholder="" required>
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
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- menu_item Edit offcanvas -->
        <div class="offcanvas offcanvas-end" id="menuItemEditOffcanvas" aria-labelledby="menuItemEditOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="menuItemEditOffcanvasLabel">Update Menu Item Details</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="menu_item_update_form">
                            <div class="row clearfix">
                                <div class="">
                                    <!-- menu_item id -->
                                    <input type="hidden" name="menu_item_id_edit" id="menu_item_id_edit" class="form-control" required>
                                    <!-- menu_item id end -->
                                    <!-- old menu_item name -->
                                    <input type="hidden" name="menu_item_name_old_edit" id="menu_item_name_old_edit" class="form-control" required>
                                    <!-- old menu_item name end -->
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Menu Category<font color="#FF0000"><strong>*</strong></font></label>
                                                <select name="menu_item_category_edit" id="menu_item_category_edit" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                    <option value="0">Select a Category...</option>
                                                    <?php
                                                    foreach ($categories as $category) {
                                                        echo '<option value="' . $category['id'] . '">' . $category['category'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Item Name<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="menu_item_name_edit" id="menu_item_name_edit" class="form-control" placeholder="" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Page URL<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="text" name="menu_item_url_edit" id="menu_item_url_edit" class="form-control" placeholder="" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row clearfix mb-3">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Order<font color="#FF0000"><strong>*</strong></font></label>
                                                <input type="number" name="menu_item_order" id="menu_item_order" class="form-control" placeholder="" required>
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
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });
        // submit category_create_form
        $('#menu_item_insert_form').submit(function(e) {
            e.preventDefault();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/menu_list/create',
                type: 'POST',
                data: new FormData(this),
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#menu_item_insert_form button[type="submit"]').attr('disabled', true);
                    $('#menu_item_insert_form button[type="submit"]').html('Please wait...');
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
                            window.location.href = "<?php echo base_url(); ?>settings/menu_list";
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
                            title: 'Duplicate Category Name',
                            text: data.message,
                        });
                    } else if (data.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        });
                    }
                    $('#menu_item_insert_form button[type="submit"]').attr('disabled', false);
                    $('#menu_item_insert_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#menu_item_insert_form button[type="submit"]').attr('disabled', false);
                    $('#menu_item_insert_form button[type="submit"]').html('Create');
                }
            });
        });

        // .edit-menu_item-btn click
        $('.edit-menu-item-btn').click(function() {
            // get menu_item id
            var menu_item_id = $(this).attr('data-menu-item-id');
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/menu_list/show/' + menu_item_id,
                type: 'GET',

                dataType: 'json',
                beforeSend: function() {
                    $('#menuItemEditOffcanvas button[type="submit"]').attr('disabled', true);
                    $('#menuItemEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    if (response.status == 200) {
                        // set menu_item id
                        $('#menu_item_id_edit').val(response.data.id);
                        // set menu_item id
                        $('#menu_item_order').val(response.data.order_id);
                        // set menu_item name
                        $('#menu_item_name_edit').val(response.data.name);
                        $('#menu_item_name_old_edit').val(response.data.name);
                        // set menu_item url
                        $('#menu_item_url_edit').val(response.data.url);
                        // set menu_item category - select option
                        $('#menu_item_category_edit').val(response.data.cat_id).trigger('change');
                        // open offcanvas
                        $('#menuItemEditOffcanvas').offcanvas('show');
                        $('#menuItemEditOffcanvas button[type="submit"]').attr('disabled', false);
                        $('#menuItemEditOffcanvas button[type="submit"]').html('Update');

                    } else if (response.status == 404) {

                        $('#menuItemEditOffcanvas button[type="submit"]').attr('disabled', false);
                        $('#menuItemEditOffcanvas button[type="submit"]').html('Update');

                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#menuItemEditOffcanvas button[type="submit"]').attr('disabled', false);
                    $('#menuItemEditOffcanvas button[type="submit"]').html('Update');
                }
            });
        });

        // submit menu_item_update_form
        $('#menu_item_update_form').submit(function(e) {
            e.preventDefault();

            var menu_item_id_edit = $('#menu_item_id_edit').val();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/menu_list/update/' + menu_item_id_edit,
                type: 'PUT',
                data: $(this).serialize(),
                cache: false,
                processData: false,
                beforeSend: function() {
                    $('#menu_item_update_form button[type="submit"]').attr('disabled', true);
                    $('#menu_item_update_form button[type="submit"]').html('Please wait...');
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
                            window.location.href = "<?php echo base_url(); ?>settings/menu_list";
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
                            title: 'Duplicate Name',
                            text: data.message,
                        });
                    } else if (data.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        });
                    }
                    $('#menu_item_update_form button[type="submit"]').attr('disabled', false);
                    $('#menu_item_update_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#menu_item_update_form button[type="submit"]').attr('disabled', false);
                    $('#menu_item_update_form button[type="submit"]').html('Create');
                }
            });
        });

        // delete-menu-item-btn click
        $('.delete-menu-item-btn').click(function() {
            Swal.fire({
                title: 'Please Confirm',
                html: "<label class='data-text'>Are you sure you want to delete this menu item ? Please note that this action cannot be undone, so proceed with caution.</label>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {

                    // get menu_item id
                    var menu_item_id = $(this).attr('data-menu-item-id');
                    // ajax call
                    $.ajax({
                        url: '<?php echo base_url(); ?>settings/menu_list/delete/' + menu_item_id,
                        type: 'DELETE',
                        data: {},
                        dataType: 'json',
                        beforeSend: function() {
                            $('.delete-menu-item-btn').attr('disabled', true);
                            $('.delete-menu-item-btn').html('<i class="fa fa-spinner fa-spin"></i>');
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
                                    window.location.href = "<?php echo base_url(); ?>settings/menu_list";
                                });
                            } else if (data.status == 500) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: data.message,
                                });
                            }
                            $('.delete-menu-item-btn').attr('disabled', false);
                            $('.delete-menu-item-btn').html('<i class="fa fa-trash"></i>');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                            $('.delete-menu-item-btn').attr('disabled', false);
                            $('.delete-menu-item-btn').html('<i class="fa fa-trash"></i>');
                        }
                    });
                }
            })

        });
    </script>
</body>




</html>