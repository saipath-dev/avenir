<?= view('layouts/head') ?>

<body>

    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">
                            <div class="h-100">

                                <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">Service Provider Payment Report</h4>
                                    </div>
                                </div>

                                <!-- Filter Card -->
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-3">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Start Date</label>
                                                            <input class="form-control" type="text"
                                                                   data-provide="datepicker"
                                                                   data-date-format="yyyy-mm-dd"
                                                                   data-date-autoclose="true"
                                                                   id="date1" name="date1"
                                                                   value="<?= esc($date1 ?? date('Y-m-d')) ?>" />
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">End Date</label>
                                                            <input class="form-control" type="text"
                                                                   data-provide="datepicker"
                                                                   data-date-format="yyyy-mm-dd"
                                                                   data-date-autoclose="true"
                                                                   id="date2" name="date2"
                                                                   value="<?= esc($date2 ?? date('Y-m-d')) ?>" />
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12 d-flex">
                                                        <button class="btn btn-success px-3 me-2 btn-filter">Filter</button>
                                                        <button class="btn btn-default px-3 btn-reset">Reset</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Results -->
                                <?php if ($is_search ?? false): ?>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-3">
                                                <div class="table-responsive">
                                                    <table id="mainTable" class="table table-hover">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Payment Date</th>
                                                                <th>Invoice No</th>
                                                                <th>Invoice Date</th>
                                                                <th>Description</th>
                                                                <th>Payment Method</th>
                                                                <th class="text-end">Pay JPY</th>
                                                                <th class="text-end">Pay Rate</th>
                                                                <th class="text-end">Pay LKR</th>
                                                                <th>Reference</th>
                                                                <th>Created By</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $row = 1;
                                                            if (!empty($records)):
                                                                foreach ($records as $r):
                                                            ?>
                                                            <tr>
                                                                <td><?= $row++ ?></td>
                                                                <td><?= esc($r['payment_date']) ?></td>
                                                                <td><?= esc($r['invoice_no']) ?></td>
                                                                <td><?= esc($r['invoice_date']) ?></td>
                                                                <td><?= esc($r['description']) ?></td>
                                                                <td><?= esc($r['payment_method_name']) ?></td>
                                                                <td class="text-end"><?= number_format((float)$r['pay_jpy_amount'], 2) ?></td>
                                                                <td class="text-end"><?= esc($r['pay_rate']) ?></td>
                                                                <td class="text-end"><?= number_format((float)$r['pay_lkr_amount'], 2) ?></td>
                                                                <td><?= esc($r['ref']) ?></td>
                                                                <td><?= esc($r['created_by']) ?></td>
                                                            </tr>
                                                            <?php
                                                                endforeach;
                                                            else:
                                                            ?>
                                                            <tr>
                                                                <td colspan="11" class="text-center">No records found for the selected date range.</td>
                                                            </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                        <?php if (!empty($records)): ?>
                                                        <tfoot>
                                                            <tr>
                                                                <td colspan="6" class="text-end"><strong>Total:</strong></td>
                                                                <td class="text-end"><strong><?= number_format($total_jpy ?? 0, 2) ?></strong></td>
                                                                <td></td>
                                                                <td class="text-end"><strong><?= number_format($total_lkr ?? 0, 2) ?></strong></td>
                                                                <td colspan="2"></td>
                                                            </tr>
                                                        </tfoot>
                                                        <?php endif; ?>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?= view('layouts/brand') ?>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

    <script>
        var report_title = 'Service Provider Payment Report';

        <?php if ($is_search ?? false): ?>
        $('#mainTable').DataTable({
            "aaSorting": [],
            responsive: true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    title: report_title,
                    footer: true
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    title: report_title,
                    orientation: 'landscape',
                    pageSize: 'A4',
                    footer: true
                },
                {
                    extend: 'print',
                    text: 'Print',
                    title: report_title,
                    footer: true,
                    customize: function(win) {
                        $(win.document.body).append('<style>@page { size: landscape; }</style>');
                    }
                },
                {
                    extend: 'colvis',
                    text: 'Visibility',
                    postfixButtons: ['colvisRestore']
                }
            ]
        });
        <?php endif; ?>

        $('.btn-filter').click(function() {
            var date1 = $('#date1').val();
            var date2 = $('#date2').val();
            if (date1 && date2) {
                window.location.href = '<?= base_url('accounts/service_provider_payment/report') ?>?date1=' + date1 + '&date2=' + date2;
            } else {
                Swal.fire({ icon: 'warning', title: 'Required', text: 'Please select both start and end dates.' });
            }
        });

        $('.btn-reset').click(function() {
            window.location.href = '<?= base_url('accounts/service_provider_payment/report') ?>';
        });
    </script>
</body>

</html>
