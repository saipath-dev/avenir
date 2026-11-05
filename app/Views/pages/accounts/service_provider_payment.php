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
                                        <h4 class="p-title">Service Provider Payment</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="body p-4">

                                                <?php if (!empty($invoices)): ?>

                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <div class="card border-0 bg-light">
                                                            <div class="card-body py-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="me-auto">
                                                                        <p class="text-muted mb-1 small">Total Outstanding (JPY)</p>
                                                                        <h4 class="mb-0">¥ <?= number_format($total_outstanding_jpy ?? 0, 2) ?></h4>
                                                                    </div>
                                                                    <div class="text-muted">
                                                                        <?php $tc = $total_invoices ?? 0; ?>
                                                                        <span class="badge bg-warning text-dark fs-6"><?= $tc ?> Invoice<?= $tc > 1 ? 's' : '' ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="card border-0 bg-light">
                                                            <div class="card-body py-3">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="me-auto">
                                                                        <p class="text-muted mb-1 small">Total Outstanding (LKR)</p>
                                                                        <h4 class="mb-0">Rs. <?= number_format($total_outstanding_lkr ?? 0, 2) ?></h4>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="bg-dark text-light rounded w-100 p-2 mb-2">Outstanding Other Invoices</div>
                                                <table class="table table-striped" id="invoices_table">
                                                    <thead>
                                                        <tr>
                                                            <th style="width:10%;">Date</th>
                                                            <th style="width:13%;">Invoice No</th>
                                                            <th>Description</th>
                                                            <th class="text-end" style="width:9%;">Inv JPY</th>
                                                            <th class="text-end" style="width:7%;">Inv Rate</th>
                                                            <th class="text-end" style="width:10%;">Inv LKR</th>
                                                            <th class="text-end" style="width:10%;">Balance (JPY)</th>
                                                            <th class="text-end" style="width:11%;">Pay JPY</th>
                                                            <th class="text-end" style="width:9%;">Pay Rate</th>
                                                            <th class="text-end" style="width:11%;">LKR Amount</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($invoices as $key => $inv): ?>
                                                        <tr>
                                                            <td><?= esc($inv['date']) ?></td>
                                                            <td><?= esc($inv['invoice_no']) ?></td>
                                                            <td><?= esc($inv['description']) ?></td>
                                                            <td class="text-end"><?= number_format((float)$inv['amount_jpy'], 2) ?></td>
                                                            <td class="text-end"><?= esc($inv['conversion_rate']) ?></td>
                                                            <td class="text-end"><?= number_format((float)$inv['amount_lkr'], 2) ?></td>
                                                            <td class="text-end bal"><?= number_format((float)$inv['amount_jpy'] - (float)($inv['pay_jpy_amount'] ?? 0), 2) ?></td>
                                                            <td class="text-end">
                                                                <input type="number" step="0.01" min="0"
                                                                       class="form-control pay_jpy"
                                                                       data-invoice="<?= esc($inv['invoice_no']) ?>"
                                                                       data-index="<?= $key ?>"
                                                                       placeholder="0.00" />
                                                            </td>
                                                            <td class="text-end">
                                                                <input type="number" step="0.0001" min="0"
                                                                       class="form-control pay_rate"
                                                                       data-index="<?= $key ?>"
                                                                       placeholder="0.0000" />
                                                            </td>
                                                            <td class="text-end">
                                                                <input type="number" step="0.01" min="0"
                                                                       class="form-control cr"
                                                                       data-invoice="<?= esc($inv['invoice_no']) ?>"
                                                                       data-index="<?= $key ?>"
                                                                       placeholder="0.00"
                                                                       style="background-color:#e9ecef;" readonly />
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>

                                                <div class="px-2">
                                                    <div class="d-flex mt-3">
                                                        <div class="me-auto">
                                                            <h4><strong>Total Payment Amount</strong></h4>
                                                        </div>
                                                        <div>
                                                            <h4><strong><code id="total_pay">0.00</code></strong></h4>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-end mt-3">
                                                    <button type="button" class="btn btn-success"
                                                            data-bs-toggle="modal" data-bs-target="#paymentDetailsModal">
                                                        Add Payment Details
                                                    </button>

                                                    <!-- Payment Details Modal -->
                                                    <div class="modal fade" id="paymentDetailsModal" tabindex="-1"
                                                         aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="paymentDetailsModalLabel">Payment Details</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <form id="form_submit" accept-charset="utf-8">

                                                                        <div class="row clearfix mb-3">
                                                                            <div class="col-sm-12">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Date <font color="#FF0000"><strong>*</strong></font></label>
                                                                                    <input class="form-control default-date"
                                                                                           data-provide="datepicker"
                                                                                           data-date-autoclose="true"
                                                                                           data-date-format="yyyy-mm-dd"
                                                                                           id="payment_date" name="payment_date"
                                                                                           value="<?= date('Y-m-d') ?>" required />
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row clearfix mb-3">
                                                                            <div class="col-sm-12">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Reference</label>
                                                                                    <input class="form-control" type="text" id="ref" name="ref" />
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row clearfix mb-3">
                                                                            <div class="col-sm-12">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Payment Method <font color="#FF0000"><strong>*</strong></font></label>
                                                                                    <select name="payment_method" id="payment_method"
                                                                                            class="js-example-basic-single form-control"
                                                                                            style="width: 100%;" required>
                                                                                        <option value="0">Select a Method...</option>
                                                                                        <?php foreach (($methods ?? []) as $method): ?>
                                                                                        <option value="<?= esc($method['L_Code']) ?>"><?= esc($method['PName']) ?></option>
                                                                                        <?php endforeach; ?>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="row clearfix mb-3">
                                                                            <div class="col-sm-12">
                                                                                <div class="form-group">
                                                                                    <label class="form-label">Amount <font color="#FF0000"><strong>*</strong></font></label>
                                                                                    <input class="form-control" type="text" id="amount" name="amount" readonly />
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <br>
                                                                        <button type="submit" class="btn btn-success me-5px">Complete Payment</button>

                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php else: ?>
                                                <div class="alert alert-info" role="alert">
                                                    No outstanding other invoices found.
                                                </div>
                                                <?php endif; ?>


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

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        // Auto-calculate LKR when Pay JPY or Pay Rate changes
        $(document).on('keyup input', '.pay_jpy, .pay_rate', function() {
            var index   = $(this).data('index');
            var pay_jpy = parseFloat($('.pay_jpy[data-index="' + index + '"]').val()) || 0;
            var pay_rate = parseFloat($('.pay_rate[data-index="' + index + '"]').val()) || 0;

            if (pay_jpy > 0 && pay_rate > 0) {
                var lkr = (pay_jpy * pay_rate).toFixed(2);
                $('.cr[data-index="' + index + '"]').val(lkr);
            } else {
                $('.cr[data-index="' + index + '"]').val('');
            }
            updateTotal();
        });

        function updateTotal() {
            var total = 0;
            $('.cr').each(function() {
                var val = parseFloat($(this).val()) || 0;
                total += val;
            });
            $('#total_pay').text(total.toFixed(2));
            $('#amount').val(total.toFixed(2));
        }

        // Sync total when modal opens
        $('#paymentDetailsModal').on('show.bs.modal', function() {
            updateTotal();
        });

        // Block enter key from submitting the modal form accidentally
        $(window).keydown(function(event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });

        // Form submit
        $('#form_submit').submit(function(e) {
            e.preventDefault();

            var payment_method = $('#payment_method').val();
            var date           = $('#payment_date').val();
            var ref            = $('#ref').val();
            var amount         = parseFloat($('#amount').val()) || 0;

            if (!payment_method || payment_method == '0') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a payment method' });
                return;
            }
            if (!date) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a date' });
                return;
            }
            if (amount <= 0) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter JPY amount and rate for at least one invoice' });
                return;
            }

            var transactions = [];
            $('#invoices_table tbody tr').each(function() {
                var cr = $(this).find('.cr').val();
                if (cr && parseFloat(cr) > 0) {
                    transactions.push({
                        invoice_no:     $(this).find('.cr').data('invoice'),
                        pay_jpy_amount: $(this).find('.pay_jpy').val(),
                        pay_rate:       $(this).find('.pay_rate').val(),
                        cr:             cr
                    });
                }
            });

            if (transactions.length === 0) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please enter JPY amount and rate for at least one invoice' });
                return;
            }

            $('button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: '<?= base_url('accounts/service_provider_payment/complete_payment') ?>',
                type: 'POST',
                data: {
                    payment_method: payment_method,
                    date: date,
                    ref: ref,
                    amount: amount,
                    transactions: transactions
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message
                        }).then(function() {
                            location.reload();
                        });
                    } else {
                        $('button[type="submit"]').prop('disabled', false);
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'An error occurred' });
                    }
                },
                error: function() {
                    $('button[type="submit"]').prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while processing the request' });
                }
            });
        });

        // DataTable - outstanding
        $('#invoices_table').DataTable({
            "paging": false,
            "info": false,
            "searching": true,
            "ordering": true,
            "scrollX": true,
            "autoWidth": false,
            "dom": 'Bfrtip',
            "buttons": [{
                extend: 'csv',
                text: '<i class="bi bi-file-earmark-spreadsheet-fill"></i> Export to CSV',
                className: 'btn btn-primary border-0'
            }]
        });

    </script>
</body>

</html>
