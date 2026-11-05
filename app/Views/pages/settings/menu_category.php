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
                                        <h4 class="p-title">Menu Categories</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="d-flex border-bottom pb-3">
                                                <div class="me-auto">
                                                    <div class="header">
                                                        <span class="badge bg-dark fs-6 mt-2"><?php echo count($categories) . " items found." ?></span>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end align-items-center me-3">
                                                    <button data-bs-toggle="offcanvas" data-bs-target="#categoryOffcanvas" aria-controls="categoryOffcanvas" class="btn btn-primary" style="height: fit-content;">+ New Menu Category</button>
                                                </div>
                                            </div>
                                            <div class="body">
                                                <div class="table-responsive mt-4">
                                                    <table class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Category Name</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $categories array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($categories as $category) {
                                                            ?>
                                                                <tr style="width: 100%;">
                                                                    <td style="width: 10%;"><?php echo $x; ?></td>
                                                                    <td><?php echo $category['category']; ?></td>
                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="#" class="dropdown-item edit-category-btn" data-category-id="<?php echo $category['id']; ?>">Edit</a>
                                                                                </li>
                                                                                <?php
                                                                                // check if the user is an admin, then show delete btn
                                                                                if ($user_role == 1) {
                                                                                ?>
                                                                                    <li><a class="dropdown-item text-danger delete-category-btn" href="#" data-category-id="<?php echo $category['id']; ?>">Delete</a></li>
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
        <!-- category Create offcanvas -->
        <div class="offcanvas offcanvas-end" id="categoryOffcanvas" aria-labelledby="categoryOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="categoryOffcanvasLabel">Create Menu Category</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="category_insert_form">
                            <div class="">
                                <div class="">
                                    <div class="row clearfix">
                                        <div class="col-md-12">
                                            <div class="row clearfix">
                                                <div class="form-group">
                                                    <label class="form-label">Category Name<font color="#FF0000"><strong>*</strong></font></label>
                                                    <input type="text" name="category_name" id="category_name" class="form-control" placeholder="" required>
                                                </div>
                                            </div>
                                            <div class="row clearfix">
                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-primary my-3">Create</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <!-- category Edit offcanvas -->
        <div class="offcanvas offcanvas-end" id="categoryEditOffcanvas" aria-labelledby="categoryEditOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 id="categoryEditOffcanvasLabel">Update Category Details</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="">

                    <div class="">

                        <form id="category_update_form">
                            <div class="">
                                <div class="">
                                    <div class="row clearfix">
                                        <div class="col-md-12">

                                            <!-- category id -->
                                            <input type="hidden" name="category_id_edit" id="category_id_edit" class="form-control" required>
                                            <!-- category id end -->
                                            <!-- old category name -->
                                            <input type="hidden" name="category_name_old_edit" id="category_name_old_edit" class="form-control" required>
                                            <!-- old category name end -->
                                            <div class="row clearfix mb-3">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label class="form-label">Category Name<font color="#FF0000"><strong>*</strong></font></label>
                                                        <input type="text" name="category_name_edit" id="category_name_edit" class="form-control" placeholder="" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row clearfix">
                                                <div class="col-sm-12">
                                                    <div class="form-group">
                                                        <label class="form-label">Order<font color="#FF0000"><strong>*</strong></font></label>
                                                        <input type="number" name="order" id="order" class="form-control" placeholder="" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row clearfix">
                                                <div class="col-sm-12">
                                                    <button type="submit" class="btn btn-primary px-5 my-3"><i class="bi bi-check-circle-fill"></i>&nbsp;&nbsp;Update</button>
                                                </div>
                                            </div>
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
        // submit category_create_form
        $('#category_insert_form').submit(function(e) {
            e.preventDefault();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/menu_category/create',
                type: 'POST',
                data: new FormData(this),
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#category_insert_form button[type="submit"]').attr('disabled', true);
                    $('#category_insert_form button[type="submit"]').html('Please wait...');
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
                            window.location.href = "<?php echo base_url(); ?>settings/menu_category";
                        });
                    } else if (data.status == 400 || data.status == 409) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Warning',
                            text: data.message,
                        });
                    } else if (data.status == 500) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                        });
                    }
                    $('#category_insert_form button[type="submit"]').attr('disabled', false);
                    $('#category_insert_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#category_insert_form button[type="submit"]').attr('disabled', false);
                    $('#category_insert_form button[type="submit"]').html('Create');
                }
            });
        });

        // .edit-category-btn click
        $('.edit-category-btn').click(function() {
            // get category id
            var category_id = $(this).attr('data-category-id');
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/menu_category/show/' + category_id,
                type: 'GET',
                data: {},
                dataType: 'json',
                beforeSend: function() {
                    $('#categoryEditOffcanvas button[type="submit"]').attr('disabled', true);
                    $('#categoryEditOffcanvas button[type="submit"]').html('Please wait...');
                },
                success: function(response) {
                    // response status == 200
                    if (response.status == 200) {
                        // set category id
                        $('#category_id_edit').val(response.data.id);
                        // set category id
                        $('#order').val(response.data.order_id);
                        // set category name
                        $('#category_name_edit').val(response.data.category);
                        $('#category_name_old_edit').val(response.data.category);
                        // open offcanvas
                        $('#categoryEditOffcanvas').offcanvas('show');
                        $('#categoryEditOffcanvas button[type="submit"]').attr('disabled', false);
                        $('#categoryEditOffcanvas button[type="submit"]').html('Update');

                    } else if (response.status == 404) {
                        Swal.fire({
                            icon: 'Warning',
                            title: 'Not Found',
                            text: response.message,
                        });
                        $('#categoryEditOffcanvas button[type="submit"]').attr('disabled', false);
                        $('#categoryEditOffcanvas button[type="submit"]').html('Update');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#categoryEditOffcanvas button[type="submit"]').attr('disabled', false);
                    $('#categoryEditOffcanvas button[type="submit"]').html('Update');
                }
            });
        });

        // submit category_update_form
        $('#category_update_form').submit(function(e) {
            e.preventDefault();
            // get category id
            var category_id = $('#category_id_edit').val();
            // ajax call
            $.ajax({
                url: '<?php echo base_url(); ?>settings/menu_category/update/' + category_id,
                type: 'PUT',
                data: $(this).serialize(),
                cache: false,
                processData: false,
                beforeSend: function() {
                    $('#category_update_form button[type="submit"]').attr('disabled', true);
                    $('#category_update_form button[type="submit"]').html('Please wait...');
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
                            window.location.href = "<?php echo base_url(); ?>settings/menu_category";
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
                    $('#category_update_form button[type="submit"]').attr('disabled', false);
                    $('#category_update_form button[type="submit"]').html('Create');
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong! Please try again later.',
                    });
                    $('#category_update_form button[type="submit"]').attr('disabled', false);
                    $('#category_update_form button[type="submit"]').html('Create');
                }
            });
        });

        // delete-category-btn click
        $('.delete-category-btn').click(function() {
            Swal.fire({
                title: 'Are you sure ?',
                html: "<label class='data-text'>if you proceed, Sub menu items under this menu category will also be deleted! Please note that this action cannot be undone, so proceed with caution.</label>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // get category id
                    var category_id = $(this).attr('data-category-id');
                    // ajax call
                    $.ajax({
                        url: '<?php echo base_url(); ?>settings/menu_category/delete/' + category_id,
                        type: 'DELETE',
                        data: {},
                        dataType: 'json',
                        beforeSend: function() {
                            $('.delete-category-btn').attr('disabled', true);
                            $('.delete-category-btn').html('<i class="fa fa-spinner fa-spin"></i>');
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
                                    window.location.href = "<?php echo base_url(); ?>settings/menu_category";
                                });
                            } else if (data.status == 500) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: data.message,
                                });
                            }
                            $('.delete-category-btn').attr('disabled', false);
                            $('.delete-category-btn').html('<i class="fa fa-trash"></i>');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Something went wrong! Please try again later.',
                            });
                            $('.delete-category-btn').attr('disabled', false);
                            $('.delete-category-btn').html('<i class="fa fa-trash"></i>');
                        }
                    });
                }
            })

        });
    </script>
</body>




</html>