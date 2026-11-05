<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <style>
            .fs-mons {
                font-size: 11px;
                font-family: 'Courier New', Courier, monospace;
                font-weight: 600;
                color: rgb(100, 100, 100);
            }

            .fs-mons-bold {
                font-size: 11px;
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
                                <!-- <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">Trial Balance Report</h4>
                                    </div>
                                </div> -->

                                <!-- <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                         <div class="body p-4">
                                            <form method="GET" accept-charset="utf-8" id="date_form">
                                                <fieldset>
                                                    <div class="row">
                                                        <div class="col-md-6" style="display: none;">
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
                                                        <div class="col-md-12">
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
                            </div> -->
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
                                                        <h5 class="fw-semibold mt-3">Trial Balance Details</h5>
                                                         <span class="badge bg-secondary">As at <?= $date2 ?></span>
                                                       

                                                    </div>

                                                    <!-- Table -->
                                                    <!-- Trial Balance Table -->
                                                    <div class="table-responsive mt-4">
                                                        <table class="table table-bordered table-striped table-hover align-middle mb-0" id="mainTable">
                                                            <thead class="table-dark">
                                                                <tr>
                                                                    <th>Account Name</th>
                                                                     <th class="text-end">Date</th>
                                                                     <th class="text-end">Discription</th>
                                                                    <th class="text-end">Debit</th>
                                                                    <th class="text-end">Credit</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody style="font-size:11px;">
                                                                <?php
                                                                $totalDr = 0;
                                                                $totalCr = 0;

                                                                $drRows = [];
                                                                $crRows = [];

                                                                if (!empty($load_data)) {
                                                                    foreach ($load_data as $row) {
                                                                        $dr = floatval($row['DR']);
                                                                        $cr = floatval($row['CR']);
                                                                        $totalDr += $dr;
                                                                        $totalCr += $cr;

                                                                        // Display each row with BName, TName, HName, PName, DR, CR, bal
                                                                        echo '<tr>';
                                                                        echo '<td style="padding-top:2px;padding-bottom:2px;">'
                                                                        
                                                                            . esc($row['PName']) .
                                                                            '</td>';
                                                                        echo '<td style="padding-top:2px;padding-bottom:2px;">' . (!empty($row['Date']) ? esc($row['Date']) : '') . '</td>';
                                                                        echo '<td style="padding-top:2px;padding-bottom:2px;">' . (!empty($row['Discription']) ? esc($row['Discription']) : '') . '</td>';
                                                                        echo '<td class="text-end text-success" style="padding-top:2px;padding-bottom:2px;">' . ($dr != 0 ? number_format($dr, 2) : '') . '</td>';
                                                                        echo '<td class="text-end text-danger" style="padding-top:2px;padding-bottom:2px;">' . ($cr != 0 ? number_format($cr, 2) : '') . '</td>';
                                                                        echo '</tr>';
                                                                    }
                                                                } else { ?>
                                                                    <tr>
                                                                        <td colspan="4" class="text-center" style="padding-top:2px;padding-bottom:2px;">No data found for the selected date range.</td>
                                                                    </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                            <tfoot class="table-light fw-bold">
                                                                <tr>
                                                                    <th class="text-end" colspan="3">Total</th>
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
                                            <strong>No data found</strong> for the selected date.
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <!-- Accounts Receivable Modal -->
                            <div class="modal fade" id="receivableModal" tabindex="-1" aria-labelledby="receivableModalLabel" aria-hidden="true">
                              <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title" id="receivableModalLabel">Accounts Receivable Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-sm table-bordered">
                                      <thead class="table-light">
                                        <tr>
                                          <th>Customer Name</th>
                                          <th class="text-end">Debit</th>
                                          <th class="text-end">Credit</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                    <?php if (isset($ar_details) && !empty($ar_details)): ?>
                                    <?php foreach ($ar_details as $row): ?>
                                      <tr>
                                        <td><?= esc($row['PName']) ?></td>
                                        <td class="text-end text-success"><?= number_format($row['DR'], 2) ?></td>
                                        <td class="text-end text-danger"><?= number_format($row['CR'], 2) ?></td>
                                    </tr>
                                <?php endforeach;   endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accounts Payable Modal -->
        <div class="modal fade" id="payableModal" tabindex="-1" aria-labelledby="payableModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="payableModalLabel">Accounts Payable Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-bordered">
                  <thead class="table-light">
                    <tr>
                      <th>Supplier Name</th>
                      <th class="text-end">Debit</th>
                      <th class="text-end">Credit</th>
                  </tr>
              </thead>
              <tbody>
               <?php if (isset($ap_details) && !empty($ap_details)): ?>
               <?php foreach ($ap_details as $row): ?>
                  <tr>
                    <td><?= esc($row['PName']) ?></td>
                    <td class="text-end text-success"><?= number_format($row['DR'], 2) ?></td>
                    <td class="text-end text-danger"><?= number_format($row['CR'], 2) ?></td>
                </tr>
            <?php endforeach;  endif;  ?>
        </tbody>
    </table>
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
<script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
<script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>
<!-- Bootstrap 5 JS (if not already included) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


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



      var report_title = 'Trial Balance Report - Avenir Holidays (Pvt) Ltd';

        $('#mainTable').DataTable({
            "aaSorting": [],
            "paging": false, // Disable pagination
            "info": false,   // Hide table info
            "searching": false, // Hide search box

            rowReorder: {
                selector: 'td:nth-child(2)'
            },
            responsive: true,
            dom: "Bfrtip",
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'CSV',
                    titleAttr: 'CSV',
                    title: report_title,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':not(:hidden)'
                    },
                    customize: function(win) {}
                }
            ]
        });
</script>



</body>




</html>