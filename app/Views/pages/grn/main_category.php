<?= view('layouts/head'); ?>

<body class="theme-cyan">

    <!-- Page Loader -->
    <div class="page-loader-wrapper">
        <div class="loader">
            <div class="m-t-30 mb-3">
                <h3 style="color: white;"><strong>Lion Royal Car</strong></h3>
            </div>
            <p>Please wait...</p>
        </div>
    </div>
    <!-- Overlay For Sidebars -->

    <div id="wrapper">

        <?= view('layouts/header'); ?>
        <?= view('layouts/sidebar'); ?>

        <div id="main-content">
            <div class="container-fluid">

                <!-- <?php //echo $block_header; 
                        ?> -->

                <div class="row mt-4">
                    <div class="col-md-12 d-flex">
                        <div class="me-auto">
                            <h4 class="p-title">Main Categories</h4>
                        </div>
                        <div>
                            <button type="button" class="btn btn-success px-3" data-bs-toggle="modal" data-bs-target="#newItemModal">
                                <i class="bi bi-plus-lg"></i> New Category
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row clearfix mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-dark text-light py-2">
                                CATEGORY LIST
                            </div>
                            <div class="body">
                                <div class="table-responsive">
                                    <table class="table table-hover js-basic-example dataTable table-custom">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($stockBooks)) {
                                                foreach ($stockBooks as $item) { ?>
                                                    <tr>
                                                        <td style="width: 10%;"><?= $item['Id'] ?></td>
                                                        <td><?= $item['Name'] ?></td>
                                                        <td style="width: 10%;">
                                                            <button type="button" class="btn btn-sm btn-warning edit-menu-item-btn" data-menu-item-id="<?= $item['Id'] ?>" data-menu-item-name="<?= $item['Name'] ?>">
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger delete-menu-item-btn" data-menu-item-id="<?= $item['Id'] ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                            <?php }
                                            } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NEW ITEM -->
                <div class="modal fade" id="newItemModal" tabindex="-1" aria-labelledby="newItemModalLabel" data-bs-keyboard="false" data-bs-backdrop="static" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="newItemModalLabel">New Category</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="name">Name<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="name" name="name" placeholder="" required />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary save">Save</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EDIT ITEM -->
                <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel" data-bs-keyboard="false" data-bs-backdrop="static" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="editItemModalLabel">Edit Category</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input type="hidden" id="u_id">
                                            <label class="form-label" for="name">Name<font color="#FF0000"><strong>*</strong></font></label>
                                            <input class="form-control" type="text" id="u_name" name="name" placeholder="" required />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary update">Save</button>
                            </div>
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

    <script>
        $(document).ready(function() {

            // .code keyup - focus on .name
            $('#code').keyup(function() {
                // if enter
                if (event.keyCode === 13) {
                    $('#name').focus();
                }
            });

            // .name keyup - focus on .save
            $('#name').keyup(function() {
                // if enter
                if (event.keyCode === 13) {
                    $('.save').click();
                }
            });

            $('.save').click(function(e) {
                e.preventDefault();
                var name = $('#name').val();
                // all required
                if (name == '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required',
                        text: 'All fields are required!',
                    });
                    return;
                }
                $.ajax({
                    url: '<?= base_url('inventory/main_category/create') ?>',
                    type: 'POST',
                    data: {
                        Name: name,
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
            });

            // .edit-destination-btn click
            $('.edit-menu-item-btn').click(function() {
                var id = $(this).data('menu-item-id');
                var name = $(this).data('menu-item-name');

                $('#u_id').val(id);
                $('#u_name').val(name);

                $('#editItemModal').modal('show');
            });

            $('.update').click(function(e) {
                e.preventDefault();
                var id = $('#u_id').val();
                var name = $('#u_name').val();
                // all required
                if (name == '') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Required',
                        text: 'All fields are required!',
                    });
                    return;
                }
                $.ajax({
                    url: '<?= base_url('inventory/main_category/update/') ?>' + id,
                    type: 'POST',
                    data: {
                        Name: name,
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
            });

            // .delete-menu-item-btn click event
            $('.delete-menu-item-btn').click(function() {
                var id = $(this).data('menu-item-id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete this category?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '<?= base_url('inventory/main_category/delete/') ?>' + id,
                            type: "GET",
                            data: {},
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
                                } else if (response.status == 500) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message,
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Not Exist',
                                        text: response.message,
                                    });
                                }
                            },
                            error: function(e) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>

</body>

</html>