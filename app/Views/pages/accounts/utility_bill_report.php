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
                                        <h4 class="p-title">Utility Bills Report</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="<?php echo base_url('accounts/utility_bill/report') ?>" method="GET" accept-charset="utf-8">
                                                    <fieldset>

                                                        <div class="row clearfix">
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">From</label>
                                                                    <input data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="from" name="from" value="<?php echo isset($from) ? $from : ""; ?>" />
                                                                </div>
                                                            </div>
                                                            <div class="col-sm-6">
                                                                <div class="form-group">
                                                                    <label class="form-label">To</label>
                                                                    <input data-provide="datepicker" data-date-format="yyyy-mm-dd" class="form-control" id="to" name="to" value="<?php echo isset($to) ? $to : ""; ?>" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div style="padding-top: 10px;">
                                                            <button type="submit" class="btn btn-success  me-5px">Generate</button>
                                                            <!-- <button type="reset" class="btn btn-info reset">Cancel</button> -->
                                                        </div>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (isset($transactions) && !empty($transactions)) {
                                ?>
                                    <div class="row clearfix mt-3">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="table-responsive">
                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th style="width: 5%;" scope="col">#</th>
                                                                    <th style="width: 5%;" scope="col">Row Id</th>
                                                                    <th style="width: 10%;" scope="col">Date</th>
                                                                    <th style="width: 15%;" scope="col">Cashier</th>
                                                                    <th style="width: 20%;" scope="col">Account</th>
                                                                    <th style="width: 15%;" scope="col">Description</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">DR</th>
                                                                    <th class="text-end" style="width: 10%;" scope="col">CR</th>
                                                                    <th style="width: 5%;" scope="col">Action</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $x = 1;
                                                                $daterange_total = 0;
                                                                foreach ($transactions as $transaction) {
                                                                ?>
                                                                    <tr>
                                                                        <td scope="row"><?= $x; ?></td>
                                                                        <td><?php echo $transaction['ID'] ? $transaction['ID'] : 'N/A'; ?></td>
                                                                        <td><?= $transaction['Date']; ?></td>
                                                                        <td><?= $transaction['name'] . ' - ' . $transaction['user']; ?></td>
                                                                        <td><?= $transaction['PName'] . ' ( ' . $transaction['BName'] . ' - ' . $transaction['TName'] . ' ) '; ?></td>
                                                                        <td><?= $transaction['Discription']; ?></td>
                                                                        <td class="text-end"><?= $transaction['Dr']; ?></td>
                                                                        <td class="text-end"><?= $transaction['Cr']; ?></td>
                                                                        <?php if ($transaction['Dr'] == 0) { ?>
                                                                            <td>
                                                                                <button type="button" class="btn btn-sm btn-danger" value="<?= $transaction['ID']; ?>">
                                                                                    <i class="bi bi-trash"></i>
                                                                                </button>
                                                                            </td>
                                                                        <?php } else { ?>
                                                                            <td></td>
                                                                        <?php } ?>
                                                                    </tr>
                                                                <?php
                                                                    $x++;
                                                                } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else {
                                ?>
                                    <?php if ($is_search) { ?>
                                        <div class="row clearfix mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-warning" role="alert">
                                                    <strong>Warning!</strong> No data found.
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>
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

        // delete transaction
        $('.btn-danger').on('click', function() {

            var id = $(this).val();

            tfConfirmReason({ // tf-e8-access-r1: reason required
                title: 'Are you sure?',
                text: "You want to delete this Records!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST", // tf-e8-access-r1: was GET
                        url: "<?php echo base_url(); ?>accounts/utility_bill/delete",
                        data: {
                            id: id,
                            reason: result.value // tf-e8-access-r1
                        },
                        dataType: "json",
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
                                    title: 'Oops...',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {

                        }
                    });
                }
            });
        });
    </script>

</body>

</html>