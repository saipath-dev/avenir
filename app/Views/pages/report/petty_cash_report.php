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
                                    <div class="col-md-12 d-flex">
                                        
                                        <h4 class="p-title">Petty Cash Report</h4>
                                                <div>
            <button class="btn btn-outline-dark" style="margin-left: 820px;" onclick="window.location.href='<?php echo base_url(); ?>dashboard2/tiles'">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
                                    </div>
                                    
                                    <div class="col-md-2 text-end">
                                        <?php if ($is_search && !empty($resultlist)) { ?>
                                            <button onclick="printReport()" class="btn btn-primary">
                                                <i class="bi bi-printer"></i> Print
                                            </button>
                                        <?php } ?>
                                    </div>

                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form id="petty_cash_form" accept-charset="utf-8">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange1">Start Date</label>
                                                                <input class="form-control" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange1" name="drange1" placeholder="Select Date" value="<?php echo $selected_startdate ?? ''; ?>" required />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label class="form-label" for="drange2">End Date</label>
                                                                <input class="form-control" type="text" data-provide="datepicker" data-date-format="yyyy-mm-dd" id="drange2" name="drange2" placeholder="Select Date" value="<?php echo $selected_enddate ?? ''; ?>" required />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3" style="padding-top: 30px;">
                                                                <button type="submit" class="btn btn-success w-100px me-5px">
                                                                    <i class="bi bi-search"></i> Search
                                                                </button>
                                                                <button type="reset" class="btn btn-secondary w-100px" onclick="window.location.href='<?php echo base_url(); ?>report/petty_cash'">
                                                                    Clear
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (isset($resultlist) && !empty($resultlist) && $is_search) { ?>
                                    <div class="row clearfix mt-3" id="printable-area">
                                        <div class="col-md-12">
                                            <div class="card p-4">
                                                <div class="body">
                                                    <div class="mb-3">
                                                        <h5 class="text-center">Petty Cash Report</h5>
                                                        <h6 class="text-center">Account: <?php echo $account_name; ?> (1-2-3-10900)</h6>
                                                        <p class="text-center">Period: <?php echo $selected_startdate; ?> to <?php echo $selected_enddate; ?></p>
                                                    </div>

                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-hover table-custom align-middle">
                                                            <thead class="thead-dark">
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Date</th>
                                                                    <th>Receipt No</th>
                                                                    <th>Description</th>
                                                                    <th>Invoice No</th>
                                                                    <th class="text-end">Debit (Dr)</th>
                                                                    <th class="text-end">Credit (Cr)</th>
                                                                    <th class="text-end">Balance</th>
                                                                    <th>Cashier</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $count = 0;
                                                                $running_balance = 0;

                                                                foreach ($resultlist as $value) {
                                                                    $count++;
                                                                    $dr_amount = floatval($value['Dr']);
                                                                    $cr_amount = floatval($value['Cr']);
                                                                    $running_balance += ($dr_amount - $cr_amount);
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $count; ?></td>
                                                                        <td><?php echo date('Y-m-d', strtotime($value['transaction_date'])); ?></td>
                                                                        <td><?php echo $value['Recpt_No'] ?? '-'; ?></td>
                                                                        <td><?php echo $value['Discription'] ?? '-'; ?></td>
                                                                        <td><?php echo $value['Invoice_Num'] ?? '-'; ?></td>
                                                                        <td class="text-end">
                                                                            <?php echo $dr_amount > 0 ? number_format($dr_amount, 2) : '-'; ?>
                                                                        </td>
                                                                        <td class="text-end">
                                                                            <?php echo $cr_amount > 0 ? number_format($cr_amount, 2) : '-'; ?>
                                                                        </td>
                                                                        <td class="text-end <?php echo $running_balance < 0 ? 'text-danger' : 'text-success'; ?>">
                                                                            <?php echo number_format($running_balance, 2); ?>
                                                                        </td>
                                                                        <td><?php echo $value['Cashier'] ?? '-'; ?></td>
                                                                    </tr>
                                                                <?php
                                                                }
                                                                ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr class="fw-bold">
                                                                    <td colspan="5" class="text-end">Total:</td>
                                                                    <td class="text-end text-primary">
                                                                        <?php echo number_format($summary['total_debit'], 2); ?>
                                                                    </td>
                                                                    <td class="text-end text-danger">
                                                                        <?php echo number_format($summary['total_credit'], 2); ?>
                                                                    </td>
                                                                    <td class="text-end <?php echo $summary['balance'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                                                        <?php echo number_format($summary['balance'], 2); ?>
                                                                    </td>
                                                                    <td></td>
                                                                </tr>
                                                                <tr class="fw-bold">
                                                                    <td colspan="8" class="text-end">Closing Balance:</td>
                                                                    <td class="text-end <?php echo $summary['balance'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                                                        <?php echo number_format($summary['balance'], 2); ?>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <?php if ($is_search) { ?>
                                        <div class="row clearfix mt-3">
                                            <div class="col-md-12">
                                                <div class="alert alert-warning" role="alert">
                                                    <strong>Warning!</strong> No petty cash transactions found for the selected date range.
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
        $("#petty_cash_form").on('submit', function(e) {
            e.preventDefault();

            var startdate = $("#drange1").val();
            var enddate = $("#drange2").val();

            if (!startdate || !enddate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Dates',
                    text: 'Please select both start and end dates.',
                    showConfirmButton: true
                });
                return;
            }

            if (new Date(startdate) > new Date(enddate)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'Start date cannot be after end date.',
                    showConfirmButton: true
                });
                return;
            }

            window.location.href = "<?php echo base_url(); ?>report/petty_cash?startdate=" + startdate + "&enddate=" + enddate;
        });

        function printReport() {
            var printContents = document.getElementById('printable-area').innerHTML;
            var originalContents = document.body.innerHTML;

            // Create a new window for printing
            var printWindow = window.open('', '_blank', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Petty Cash Report</title>');
            printWindow.document.write('<link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet">');
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: Arial, sans-serif; }');
            printWindow.document.write('.table { width: 100%; margin-bottom: 1rem; color: #212529; }');
            printWindow.document.write('.table th, .table td { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }');
            printWindow.document.write('.table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; }');
            printWindow.document.write('.table tbody + tbody { border-top: 2px solid #dee2e6; }');
            printWindow.document.write('.text-end { text-align: right; }');
            printWindow.document.write('.text-center { text-align: center; }');
            printWindow.document.write('.fw-bold { font-weight: bold; }');
            printWindow.document.write('.text-primary { color: #007bff; }');
            printWindow.document.write('.text-danger { color: #dc3545; }');
            printWindow.document.write('.text-success { color: #78b848; }');
            printWindow.document.write('@media print { body { -webkit-print-color-adjust: exact; } }');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContents);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();

            setTimeout(function() {
                printWindow.print();
                printWindow.close();
            }, 250);
        }
    </script>

</body>

</html>
