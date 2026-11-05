<?= view('layouts/head') ?>
<style type="text/css">
    body {
        background: #f4f6fb;
    }

    .pr-page-title {
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .pr-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 6px 20px rgba(17, 24, 39, 0.06);
    }

    .pr-search-bar {
        background: #f8fafc;
        border: 1px solid #eef1f6;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
    }

    .pr-search-bar .form-group label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 10px;
        overflow: hidden;
    }

    .table thead th {
        background: #111827;
        color: #f9fafb;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
        border: none;
        white-space: nowrap;
        padding: 0.85rem 0.75rem;
    }

    .table tbody td {
        vertical-align: middle;
        font-size: 0.9rem;
        border-bottom: 1px solid #f1f3f7;
        padding: 0.7rem 0.75rem;
    }

    .table-hover tbody tr:hover {
        background-color: #f5f8ff;
    }

    .pr-amount {
        font-weight: 700;
        color: #111827;
    }

    .pr-badge-type {
        display: inline-block;
        background: #eef2ff;
        color: #4338ca;
        border-radius: 20px;
        padding: 0.25em 0.75em;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .btn-sm {
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.78rem;
    }

    .pr-section-header {
        padding: 1rem 1.35rem;
        border-bottom: 1px solid #eef1f6;
        background: linear-gradient(120deg, #f8fafc, #eef1f6);
        border-radius: 14px 14px 0 0;
    }

    .pr-section-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1rem;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.55rem;
    }

    .pr-copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        vertical-align: middle;
        line-height: 1;
        color: #fff;
        background-color: #2563eb;
        border: none;
        border-radius: 8px;
        padding: 0.4rem 0.7rem;
        font-size: 0.95rem;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.35);
    }

    .pr-copy-btn:hover,
    .pr-copy-btn:focus {
        color: #fff;
        background-color: #1d4ed8;
    }

    .pr-copy-btn.pr-copy-done {
        background-color: #16a34a;
        box-shadow: 0 2px 6px rgba(22, 163, 74, 0.35);
    }
</style>

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
                                <div class="row mb-2">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="pr-page-title mb-0">Payment Release</h4>
                                    </div>
                                </div>


                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card pr-card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table id="paymentReleaseTable" class="table table-hover js-basic-example dataTable table-custom align-middle">
                                                        <thead>
                                                            <tr>
                                                                <th>Voucher Date</th>
                                                                <th>Voucher Number</th>
                                                                <th>Tour ID</th>
                                                                <th>Type</th>
                                                                <!-- <th>Division</th> -->
                                                                <th>Pay</th>
                                                                <th>Bank</th>
                                                                <th>Bank Description</th>
                                                                <th>Refference Number</th>
                                                                <th>Amount</th>
                                                                <th>Approval Request From</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            foreach ($voucher_request as $value) {

                                                                $dateTime = new DateTime($value->W_Date);
                                                                $date = $dateTime->format('Y-m-d');
                                                                $pay_type_label = ($value->Pay_Type === 'Bank Transfer') ? 'BT' : $value->Pay_Type;
                                                            ?>
                                                                <tr class="gradeX">
                                                                    <td> <?php echo $date; ?></td>
                                                                    <td> <?php echo $value->Voucher; ?></td>
                                                                    <td> <?php echo $value->manual_id; ?></td>
                                                                    <td><span class="pr-badge-type"><?php echo $pay_type_label; ?></span></td>
                                                                    <!-- <td> <?php //echo $value->dv; ?></td> -->
                                                                    <td> <?php echo $value->Pay; ?></td>
                                                                    <td> <?php echo $value->Bank; ?></td>
                                                                    <?php $bank_description = $value->Voucher . ' - ' . $value->manual_id . ' - ' . $value->Pay . ' - ' . $pay_type_label; ?>
                                                                    <td>
                                                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                                                            <span class="pr-copy-text"><?php echo $bank_description; ?></span>
                                                                            <button type="button" class="pr-copy-btn" data-copy-text="<?php echo esc($bank_description); ?>" title="Copy">
                                                                                <i class="bi bi-clipboard"></i> Copy
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                    <td> <?php echo $value->Chq_No; ?></td>
                                                                    <td class="pr-amount"> <?php echo $value->Amount; ?></td>
                                                                    <td> <?php echo $value->login; ?></td>
                                                                </tr>
                                                            <?php
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
        $('#paymentReleaseTable').DataTable({
            dom: '<"d-flex justify-content-end mb-3"B>rtip',
            buttons: [
                { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel me-1"></i>Excel', className: 'btn btn-success btn-sm', title: 'Payment Release' },
                { extend: 'csvHtml5', text: '<i class="bi bi-filetype-csv me-1"></i>CSV', className: 'btn btn-outline-secondary btn-sm', title: 'Payment Release' },
                { extend: 'print', text: '<i class="bi bi-printer me-1"></i>Print', className: 'btn btn-outline-secondary btn-sm', title: 'Payment Release' }
            ],
            pageLength: 25
        });

        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        $(".btn_update").click(function() {
            var id = $(this).val();
            var status = $(this).attr('data-status');
            var bank_acc = $(this).attr('data-bank');
            var type = $(this).attr('data-type');
            var amount = $(this).attr('data-amnt');
            var sup = $(this).attr('data-sup');
            var payee = $(this).attr('data-pay');
            var v_token = $(this).attr('data-tkn');
            var tourid = $(this).attr('data-tourid');
            var pp = payee.replace("&", "and");

            var url = "<?php echo base_url() ?>accounts/voucher_print/" + v_token +
                "?id=" + encodeURIComponent(id) +
                "&status=" + encodeURIComponent(status) +
                "&bank_acc=" + encodeURIComponent(bank_acc) +
                "&type=" + encodeURIComponent(type) +
                "&amount=" + encodeURIComponent(amount) +
                "&sup=" + encodeURIComponent(sup) +
                "&payee=" + encodeURIComponent(pp) +
                "&tourid=" + encodeURIComponent(tourid);

            window.location.href = url;

        });

        // Copy Bank Description text
        function fallbackCopyText(text) {
            var temp = document.createElement('textarea');
            temp.value = text;
            temp.setAttribute('readonly', '');
            temp.style.position = 'fixed';
            temp.style.top = '-1000px';
            temp.style.left = '-1000px';
            document.body.appendChild(temp);
            temp.focus();
            temp.select();
            temp.setSelectionRange(0, text.length);

            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            document.body.removeChild(temp);
            return ok;
        }

        $(document).on('click', '.pr-copy-btn', function() {
            var text = $(this).data('copy-text');
            var btn = $(this);
            var originalHtml = btn.html();

            function showCopied() {
                btn.addClass('pr-copy-done').html('<i class="bi bi-check2-circle"></i> Copied');
                setTimeout(function() {
                    btn.removeClass('pr-copy-done').html(originalHtml);
                }, 1500);
            }

            function showFailed() {
                Swal.fire({
                    icon: 'error',
                    title: 'Copy failed',
                    text: 'Could not copy automatically. Please select and copy the text manually.'
                });
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(showCopied, function() {
                    if (fallbackCopyText(text)) {
                        showCopied();
                    } else {
                        showFailed();
                    }
                });
            } else {
                if (fallbackCopyText(text)) {
                    showCopied();
                } else {
                    showFailed();
                }
            }
        });
    </script>



</body>

</html>