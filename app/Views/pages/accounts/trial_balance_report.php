<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <style>
            .fs-mons {
                font-size: 12px;
                font-family: 'Courier New', Courier, monospace;
                font-weight: 600;
                color: rgb(100, 100, 100);
            }

            .fs-mons-bold {
                font-size: 12px;
                font-family: 'Courier New', Courier, monospace;
                font-weight: 800;
                color: rgb(0, 0, 0);
            }

            td a:hover {
                text-decoration: underline;
                color: dodgerblue !important;
            }
        </style>

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
                                        <h4 class="p-title">Trial Balance Report</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                           <div class="body p-4">
                                            <form method="GET" accept-charset="utf-8" id="date_form">
                                                <fieldset>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="date1">Start Date</label>
                                                                <input class="form-control txt_nav date"
                                                                type="text"
                                                                data-provide="datepicker"
                                                                data-date-format="yyyy-mm-dd"
                                                                data-date-autoclose="true"
                                                                id="date1"
                                                                name="date1"
                                                                placeholder="Select Date"
                                                                value="<?= esc($date1 ?? '') ?>" />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="date2">End Date</label>
                                                                <input class="form-control txt_nav default-date"
                                                                type="text"
                                                                data-provide="datepicker"
                                                                data-date-format="yyyy-mm-dd"
                                                                data-date-autoclose="true"
                                                                id="date2"
                                                                name="date2"
                                                                placeholder="Select Date"
                                                                value="<?= esc($date2 ?? date('Y-m-d')) ?>" />
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <button type="submit" class="btn btn-primary me-2" id="search_data">Generate</button>
                                                    <button type="reset" class="btn btn-secondary reset">Cancel</button>
                                                </fieldset>
                                            </form>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="row clearfix mt-3">
                                <div class="col-md-12">
                                    <div class="mb-3 text-end">
                                        <button class="btn btn-outline-primary btn-sm" onclick="printReport()">
                                            <i class="bi bi-printer"></i> Print Report
                                        </button>
                                    </div>

                                    <?php if (!empty($load_data)) { ?>
                                        <div id="print-area">
                                            <div class="card shadow-sm border-0 rounded-3">
                                                <div class="card-body p-4">
                                                    <!-- Company Header -->
                                                    <div class="text-center mb-4">
                                                        <h4 class="mb-1 fw-bold">Avenir Holidays (Pvt) Ltd</h4>
                                                        <p class="mb-0 text-muted">No. 94/2, Lauries Rd, Colombo 4</p>
                                                        <p class="mb-2 text-muted">Tel: +94-77-395-7956, +94 112 551 880</p>
                                                        <h5 class="fw-semibold mt-3">Trial Balance Report</h5>
                                                        <span class="badge bg-secondary">As at <?= $date2 ?></span>

                                                    </div>

                                                    <!-- Table -->
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped table-hover align-middle mb-0">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th>Account Name</th>
                                                                    <th class="text-end">Debit (Dr)</th>
                                                                    <th class="text-end">Credit (Cr)</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $totalDr = 0;
                                                                $totalCr = 0;
                                                                foreach ($load_data as $row) {
                                                                    $totalDr += $row['DR'];
                                                                    $totalCr += $row['CR'];
                                                                    ?>
                                                                    <tr>
                                                                        <td><?= esc($row['HName']) ?></td>
                                                                        <td class="text-end text-success"><?= number_format($row['DR'], 2) ?></td>
                                                                        <td class="text-end text-danger"><?= number_format($row['CR'], 2) ?></td>
                                                                    </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                            <tfoot class="table-light">
                                                                <tr>
                                                                    <th class="text-end">Total</th>
                                                                    <th class="text-end text-success"><?= number_format($totalDr, 2) ?></th>
                                                                    <th class="text-end text-danger"><?= number_format($totalCr, 2) ?></th>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class="alert alert-warning shadow-sm" role="alert">
                                            <strong>No data found</strong> for the selected date range.
                                        </div>
                                    <?php } ?>
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
<script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>

<script>
    $('.js-example-basic-single').select2({
        placeholder: " Select",
        allowClear: true
    });

    function printContent(el) {
        var restorepage = $('body').html();
        var printcontent = $('#' + el).clone();
        $('body').empty().html(printcontent);
        window.print();
        $('body').html(restorepage);
    }

        //.reset on click
        $('.reset').on('click', function() {
            window.location.href = "<?php echo base_url('accounts/balance_sheet'); ?>";
        });
    </script>
    <?= view('pages/accounts/balance_details'); ?>

    <script>
        function printReport() {
            const printContent = document.getElementById('print-area').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;

        // Optional: Reload to restore event listeners
        location.reload();
    }
</script>



</body>




</html>