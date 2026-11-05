<?= view('layouts/head') ?>

<style>
    @page {
        size: auto;
        margin: 10mm;
    }

    @media print {

        #layout-wrapper>*:not(.main-content),
        .app-menu,
        .navbar-header,
        .vertical-overlay,
        .no-print,
        .btn,
        footer {
            display: none !important;
        }

        html,
        body,
        #layout-wrapper,
        .main-content,
        .page-content,
        .container-fluid,
        .row,
        .col,
        .h-100,
        #invoice_area,
        #invoice_area>.col-md-12,
        .card,
        .card>.body {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            flex: 0 0 100% !important;
        }

        .main-content {
            margin-left: 0 !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        body {
            font-size: 13px;
            color: #000;
        }

        table {
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        .table-bordered,
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000 !important;
        }
    }

    .invoice-table.table-bordered,
    .invoice-table.table-bordered th,
    .invoice-table.table-bordered td {
        border: 1px solid #ccc !important;
    }

    .invoice-header {
        text-align: center;
        border-bottom: 3px double #000;
        margin-bottom: 10px;
        padding-bottom: 6px;
    }

    .invoice-header h2 {
        margin: 0;
        font-weight: 700;
        letter-spacing: 1px;
        font-size: 1.55rem;
    }

    .invoice-header h4 {
        margin: 2px 0 0;
        font-weight: 600;
        color: #555;
        font-size: 1.1rem;
    }

    .invoice-meta-table {
        table-layout: fixed;
        width: 100%;
    }

    .invoice-meta-table td {
        padding: 2px 8px;
        vertical-align: top;
        width: 25%;
        font-size: 1em;
    }

    .invoice-meta-table td.label {
        width: 18%;
    }

    .invoice-meta-table td.label {
        font-weight: 600;
        color: #444;
    }

    .invoice-section-title {
        background-color: #2c3e50;
        color: #fff;
        padding: 4px 10px;
        font-weight: 700;
        margin-top: 10px;
        margin-bottom: 0;
        font-size: 1.05em;
    }

    .invoice-subsection-title {
        font-weight: 700;
        margin-top: 6px;
        margin-bottom: 3px;
        border-bottom: 1px solid #ccc;
        padding-bottom: 2px;
        font-size: 1em;
    }

    .invoice-table th {
        background-color: #f1f3f5;
        font-weight: 600;
    }

    .invoice-table th,
    .invoice-table td {
        padding: 3px 8px;
        font-size: 0.95em;
    }

    .fill-line {
        display: inline-block;
        min-width: 160px;
        border-bottom: 1px solid #999;
    }

    .signature-table td {
        text-align: center;
        padding: 6px 5px 4px;
        border-top: 1px solid #000;
        font-size: 0.9em;
        font-weight: 600;
    }

    .signature-row-spacer td {
        border-top: none;
        height: 45px;
    }
</style>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row no-print">
                        <div class="col-md-10 d-flex">
                            <h4 class="p-title">Driver Settlement Invoice</h4>
                        </div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div class="alert alert-warning"><?= esc($error) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($invoice)): ?>
                        <?php $inv = $invoice; ?>

                        <div class="row clearfix mt-3" id="invoice_area">
                            <div class="col-md-12">
                                <div class="card p-4">
                                    <div class="body">

                                        <div class="d-flex justify-content-end mb-1 no-print">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                                                <i class="bi bi-printer me-1"></i> Print Invoice
                                            </button>
                                        </div>

                                        <div class="invoice-header">
                                            <h2>AVENIR HOLIDAYS (PVT) LTD.</h2>
                                            <h4>Driver Settlement Invoice</h4>
                                        </div>

                                        <table class="table table-borderless invoice-meta-table mb-2">
                                            <tbody>
                                                <tr>
                                                    <td class="label">Invoice No:</td>
                                                    <td><?= esc($inv['invoice_no']) ?></td>
                                                    <td class="label">Invoice Date:</td>
                                                    <td><?= esc(date('d / m / Y', strtotime($inv['invoice_date']))) ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="label">Tour Period:</td>
                                                    <td colspan="3">
                                                         <?= $inv['start_date'] ? esc(date('d M', strtotime($inv['start_date']))) : '<span class="fill-line">&nbsp;</span>' ?>
                                                        &nbsp;-&nbsp;
                                                        <?= $inv['end_date'] ? esc(date('d M Y', strtotime($inv['end_date']))) : '<span class="fill-line">&nbsp;</span>' ?>
                                                    </td>
                                                </tr>
                                                <tr>
                                                     <td class="label">Driver Name:</td>
                                                    <td><?= esc($inv['driver_name'] ?: '-') ?></td>
                                                </tr>
                                                <tr>
                                                    <td class="label">Booking No:</td>
                                                    <td><?= esc($inv['manual_id'] ?: $inv['tour_id']) ?></td>
                                                                                                        <td class="label">Vehicle No:</td>
                                                    <td><?= $inv['vehicle_name'] ? esc($inv['vehicle_name']) : '<span class="fill-line">&nbsp;</span>' ?></td>
                                                   
                                                </tr>
                                                 <tr>
 <td class="label">Client Name:</td>
                                                    <td><?= esc($inv['consignee'] ?: '-') ?></td>
                                                    <td class="label">Contact No:</td>
                                                    <td><?= $inv['driver_contact'] ? esc($inv['driver_contact']) : '<span class="fill-line">&nbsp;</span>' ?></td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <!-- Trip Details + Trip Summary side by side -->
                                        <div class="d-flex flex-wrap gap-3 align-items-start">
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <p class="invoice-section-title">Trip Details</p>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm align-middle invoice-table mt-2">
                                                        <thead>
                                                            <tr>
                                                                <th>Date</th>
                                                                <th class="text-end">Odometer Start (km)</th>
                                                                <th class="text-end">Odometer End (km)</th>
                                                                <th class="text-end">Total Mileage (km)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($inv['trip_rows'])): ?>
                                                                <?php foreach ($inv['trip_rows'] as $row): ?>
                                                                    <tr>
                                                                        <td><?= esc(date('d M Y', strtotime($row['date']))) ?></td>
                                                                        <td class="text-end"><?= $row['odometer_start'] !== null ? number_format($row['odometer_start'], 0) : '<span class="text-muted">-</span>' ?></td>
                                                                        <td class="text-end"><?= $row['odometer_end'] !== null ? number_format($row['odometer_end'], 0) : '<span class="text-muted">-</span>' ?></td>
                                                                        <td class="text-end"><?= $row['mileage'] !== null ? number_format($row['mileage'], 0) : '<span class="text-muted">-</span>' ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr><td colspan="4" class="text-center text-muted">No trip data</td></tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div style="width:220px;flex:0 0 auto;">
                                                <p class="invoice-section-title">Trip Summary</p>
                                                <table class="table table-bordered table-sm invoice-table mb-2">
                                                    <tbody>
                                                        <tr>
                                                            <td>Total Tour Days</td>
                                                            <td class="text-end"><?= (int) $inv['day_count'] ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td>Total Mileage (km)</td>
                                                            <td class="text-end"><?= number_format($inv['distance_total'], 2) ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Reimbursable Expenses -->
                                        <p class="invoice-section-title">Reimbursable Expenses</p>

                                        <div class="d-flex flex-wrap gap-3 align-items-start">
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <p class="invoice-subsection-title">Entrance Fees</p>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm invoice-table mb-2">
                                                        <thead>
                                                            <tr>
                                                                <th>Description</th>
                                                                <th class="text-end" style="width:150px;">Amount (LKR)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($inv['entrance_fees'])): ?>
                                                                <?php foreach ($inv['entrance_fees'] as $fee): ?>
                                                                    <tr>
                                                                        <td><?= esc($fee['description']) ?></td>
                                                                        <td class="text-end"><?= number_format($fee['amount'], 2) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr><td colspan="2" class="text-center text-muted">No entrance fees recorded</td></tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <p class="invoice-subsection-title">Travel & Other Expenses</p>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm invoice-table mb-2">
                                                        <thead>
                                                            <tr>
                                                                <th>Description</th>
                                                                <th class="text-end" style="width:150px;">Amount (LKR)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if (!empty($inv['other_expenses'])): ?>
                                                                <?php foreach ($inv['other_expenses'] as $exp): ?>
                                                                    <tr>
                                                                        <td><?= esc($exp['description']) ?></td>
                                                                        <td class="text-end"><?= number_format($exp['amount'], 2) ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else: ?>
                                                                <tr><td colspan="2" class="text-center text-muted">No other expenses recorded</td></tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex flex-wrap gap-3 align-items-start">
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <!-- Settlement Summary -->
                                                <p class="invoice-section-title">Settlement Summary</p>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm invoice-table mb-2">
                                                        <tbody>
                                                            <tr>
                                                                <td>Total Driver Earnings</td>
                                                                <td class="text-end"><?= number_format($inv['total_driver_earnings'], 2) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td>(+) Reimbursable Expenses</td>
                                                                <td class="text-end"><?= number_format($inv['total_reimbursable'], 2) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td>(-) Deductions (Outstanding Advances)</td>
                                                                <td class="text-end"><?= number_format($inv['total_deductions'], 2) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td>(-) Amount Already Settled</td>
                                                                <td class="text-end"><?= number_format($inv['already_settled'], 2) ?></td>
                                                            </tr>
                                                            <tr class="fw-bold">
                                                                <td>Net Amount Payable to Driver</td>
                                                                <td class="text-end">LKR <?= number_format($inv['net_amount_payable'], 2) ?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1" style="min-width:0;">
                                                <!-- Payment Details -->
                                                <p class="invoice-section-title">Payment Details</p>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-sm invoice-table mb-2">
                                                    <tbody>
                                                        <tr>
                                                            <td class="label">Payment Method</td>
                                                            <td>Cash / Bank Transfer</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="label">Bank Name</td>
                                                            <td><?= $inv['bank_name'] !== '' ? esc($inv['bank_name']) : '<span class="fill-line">&nbsp;</span>' ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="label">Branch</td>
                                                            <td><?= ($inv['branch_name'] !== '' && $inv['branch_name'] !== 'Select Branch') ? esc($inv['branch_name']) : '<span class="fill-line">&nbsp;</span>' ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="label">Account Name</td>
                                                            <td><?= $inv['bank_account_holder'] !== '' ? esc($inv['bank_account_holder']) : '<span class="fill-line">&nbsp;</span>' ?></td>
                                                        </tr>
                                                        <tr>
                                                            <td class="label">Account Number</td>
                                                            <td><?= $inv['bank_account_no'] !== '' ? esc($inv['bank_account_no']) : '<span class="fill-line">&nbsp;</span>' ?></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Declaration -->
                                        <p class="invoice-section-title">Declaration</p>
                                        <p class="mt-1 mb-2" style="font-size:0.85em;">
                                            I certify that the above tour details, mileage, expenses, and settlement information
                                            are true and accurate. All supporting receipts have been attached where required.
                                        </p>


                                        <!-- Signatures -->
                                        <table class="table table-borderless signature-table mt-0" style="border-collapse:separate;border-spacing:12px 0;">
                                            <tr class="signature-row-spacer">
                                                <td style="width:20%;"></td>
                                                <td style="width:20%;"></td>
                                                <td style="width:20%;"></td>
                                                <td style="width:20%;"></td>
                                                <td style="width:20%;"></td>
                                            </tr>
                                            <tr>
                                                <td>Driver Signature</td>
                                                <td>Operations Officer</td>
                                                <td>Accounts Officer</td>
                                                <td>Finance Manager</td>
                                                <td>Approved By</td>
                                            </tr>
                                        </table>


                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <?= view('layouts/brand') ?>

        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
</body>

</html>
